<?php
/**
 * WordPress Ajax.
 *
 * @package Shopperexpress
 */

namespace App\Components\Base;

use App\Components\Theme_Component;
use WP_Query;

/**
 * Class Ajax
 *
 * @package App\Base\Component
 */
class Ajax implements Theme_Component {


	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {

		foreach ( $this->wp_end_point() as $key => $array ) {
			if ( $key == 'ajax' ) {
				foreach ( $array as $action => $function ) {
					add_action( 'wp_ajax_' . $action, array( $this, $function ) );
					add_action( 'wp_ajax_nopriv_' . $action, array( $this, $function ) );
				}
			} elseif ( $key == 'admin-ajax' ) {
				foreach ( $array as $action => $function ) {
					add_action( 'wp_ajax_' . $action, array( $this, $function ) );
				}
			} elseif ( $key == 'template_redirect' ) {
				foreach ( $array as $function ) {
					add_action( 'template_redirect', array( $this, $function ) );
				}
			}
		}
	}

	public function wp_end_point() {
		return array(
			'ajax'              => array(
				'register_user'            => 'register_user',
				'ajax_login'               => 'ajax_login',
				'adf'                      => 'adf_action',
				'submit_adf_lead'          => 'submit_adf_lead',
				'favorite'                 => 'favorite_action',
				'get_pdf'                  => 'get_pdf',
				'wps_api_favorite_toggle'  => 'api_favorite_toggle',
				'wps_api_favorites_list'   => 'api_favorites_list',
				'wps_api_render_favorites' => 'api_render_favorites',
			),
			'admin-ajax'        => array(
				'save_listing'       => 'save_listing',
				'delete_listings'    => 'delete_listings',
				'clear'              => 'clear_action',
				'wps_api_save_vehicle'   => 'api_save_vehicle',
				'wps_api_delete_vehicle' => 'api_delete_vehicle',
			),
			'template_redirect' => array(
				'unlock_form',
				'auto_check',
				'offers_form',
			),
		);
	}

	/**
	 * Get PDF.
	 *
	 * @return void
	 */
	public function get_pdf() {

		if ( empty( $_REQUEST['vin_number'] ) && empty( $_REQUEST['dealer_name'] ) ) {
			wp_send_json_error( array( 'message' => 'VIN and dealer name is required' ) );

		}
		$vin        = sanitize_text_field( wp_unslash( $_REQUEST['vin_number'] ) );
		$dealerName = sanitize_text_field( wp_unslash( $_REQUEST['dealer_name'] ) );

		$upload_dir = wp_upload_dir();
		$vdr_dir    = trailingslashit( $upload_dir['basedir'] ) . 'vdr/';
		$vdr_url    = trailingslashit( $upload_dir['baseurl'] ) . 'vdr/';
		$file_path  = $vdr_dir . $vin . '.pdf';
		$file_url   = $vdr_url . $vin . '.pdf';

		if ( ! file_exists( $vdr_dir ) ) {
			wp_mkdir_p( $vdr_dir );
		}

		if ( file_exists( $file_path ) ) {
			$this->log_vdr_request( $vin, $dealerName, 'success', 200, true );
			wp_send_json_success(
				array(
					'url'    => $file_url,
					'cached' => true,
				)
			);
		}

		/**
		 * API ChromeData
		 */
		$url  = 'https://cvd-api.jdpower.com/CVD/v1.0/vehicledetailsreport';
		$data = array(
			'vin'        => $vin,
			'dealerName' => $dealerName,
		);

		$mt        = explode( ' ', microtime() );
		$timestamp = ( (int) $mt[1] ) * 1000 + (int) round( $mt[0] * 1000 );
		$nonce     = substr( str_replace( array( '+', '/', '=' ), '', base64_encode( random_bytes( 32 ) ) ), 0, 32 );

		$app_id = get_field( 'chromedata_app_id', 'options' );
		$secret = get_field( 'shared_secret', 'options' );

		$digest_raw = $nonce . $timestamp . $secret;
		$digest     = base64_encode( sha1( $digest_raw, true ) );

		$token = sprintf(
			'Atmosphere realm="http://chromedata.com",chromedata_app_id="%s",chromedata_nonce="%s",chromedata_secret_digest="%s",chromedata_digest_method=SHA1,chromedata_version=1.0,chromedata_timestamp="%s"',
			$app_id,
			$nonce,
			$digest,
			$timestamp
		);

		$ch = curl_init( $url );
		curl_setopt_array(
			$ch,
			array(
				CURLOPT_CUSTOMREQUEST  => 'PUT',
				CURLOPT_POSTFIELDS     => json_encode( $data ),
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HTTPHEADER     => array(
					'Content-Type: application/json',
					'Accept: application/pdf',
					"Authorization: {$token}",
				),
			)
		);

		$pdf       = curl_exec( $ch );
		$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );

		if ( $http_code !== 200 || empty( $pdf ) ) {
			$this->log_vdr_request( $vin, $dealerName, 'error', $http_code, false );
			set_transient( 'vdr_error_' . $vin, 1, DAY_IN_SECONDS );
			wp_send_json_error(
				array(
					'message'   => 'Failed to generate VDR',
					'http_code' => $http_code,
				)
			);
		}

		file_put_contents( $file_path, $pdf );
		delete_transient( 'vdr_error_' . $vin );

		$this->log_vdr_request( $vin, $dealerName, 'success', $http_code, false );
		wp_send_json_success(
			array(
				'url'    => $file_url,
				'cached' => false,
			)
		);
	}

	private function log_vdr_request( string $vin, string $dealer_name, string $result, int $http_code, bool $from_cache ): void {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'vdr_log',
			array(
				'vin'         => $vin,
				'dealer_name' => $dealer_name,
				'site_name'   => get_bloginfo( 'name' ),
				'result'      => $result,
				'http_code'   => $http_code,
				'from_cache'  => $from_cache ? 1 : 0,
			),
			array( '%s', '%s', '%s', '%s', '%d', '%d' )
		);
	}


	public function clear_action() {

		$post_type = sanitize_text_field( $_REQUEST['post_type'] ?? '' );
		if ( ! $post_type ) {
			wp_die( 'No post type.' );
		}

		$command = "wp api clear {$post_type} --clear=true";
		exec( escapeshellcmd( $command ) . ' > /dev/null 2>&1 &' );

		wp_send_json_success(
			array(
				'message' => 'The cache will be cleared in the background shortly.',
			)
		);

		wp_die();
	}

	public function delete_listings() {

		$post_id = intval( $_POST['post_id'] ?? 0 );
		if ( ! $post_id ) {
			wp_send_json_error( 'Invalid ID' );
		}
		$result = wp_delete_post( $post_id, true );
		if ( $result ) {
			wp_send_json_success();
		} else {
			wp_send_json_error( 'Delete failed' );
		}

		exit;
	}

	public function auto_check() {

		if ( ! empty( $_REQUEST['action'] ) && $_REQUEST['action'] == 'auto_check' ) {
			$vin = get_field( 'vin_number' );
			while ( have_rows( 'auto_check', 'options' ) ) :
				the_row();

				$post_data = array(
					'VIN' => $vin,
					'CID' => get_sub_field( 'cid' ),
					'PWD' => get_sub_field( 'pwd' ),
					'SID' => get_sub_field( 'sid' ),
				);

				$poststring = null;

				foreach ( $post_data as $key => $val ) {
					$poststring .= urlencode( $key ) . '=' . urlencode( $val ) . '&';
				}

				$poststring = substr( $poststring, 0, -1 );

				$ch = curl_init();

				curl_setopt( $ch, CURLOPT_POST, 1 );
				curl_setopt( $ch, CURLOPT_HEADER, 0 );
				curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
				curl_setopt( $ch, CURLOPT_URL, 'https://www.autocheck.com/DealerWebLink.jsp' );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $poststring );

				curl_exec( $ch );

				curl_close( $ch );
			endwhile;
			exit;
		}
	}


	public function register_user() {
		$first_name = stripcslashes( $_POST['first-name'] );
		$last_name  = stripcslashes( $_POST['last-name'] );
		$email      = strtolower( $_POST['email'] );
		$phone      = preg_replace( '/[^0-9]/', '', $_POST['phone'] );
		$zip        = $_POST['zip'];

		$responce = array();

		$user_data = array(
			'user_pass'  => wp_generate_password( 12 ),
			'user_login' => $email,
			'user_email' => $email,
			'first_name' => $first_name,
			'last_name'  => $last_name,
		);

		$user_id = get_user_by( 'email', $email );

		$user_id = ! empty( $user_id->ID ) ? $user_id->ID : wp_insert_user( $user_data );
		if ( ! is_wp_error( $user_id ) ) {
			update_field( 'phone', $phone, 'user_' . $user_id );
			update_field( 'zip', $zip, 'user_' . $user_id );

			if ( have_rows( 'email_notification', 'options' ) ) {

				$mail_to = array();

				while ( have_rows( 'email_notification', 'options' ) ) :
					the_row();
					$mail_to[] = get_sub_field( 'email' );
				endwhile;

				$headers = array(
					'content-type: text/plain',
				);

				foreach ( $_POST as $index => $value ) {
					$subject  = str_replace( '{{' . $index . '}}', $value, get_field( 'adf_subject_get_price', 'options' ) );
					$template = str_replace( '{{' . $index . '}}', $value, get_field( 'adf_template_get_price', 'options' ) );
				}

				if ( $subject && $template ) {
					wp_mail( implode( ', ', $mail_to ), 'intice360: ' . $first_name . ' ' . $last_name, $message, $headers );
				}
			}

			$this->auto_login_new_user( $user_id, $_POST['permalink'] );
		} else {
			if ( isset( $user_id->errors['empty_user_login'] ) ) {
				$responce['error'] = __( 'All fields mandatory.', 'shopperexpress' );
			} elseif ( isset( $user_id->errors['existing_user_login'] ) ) {
				$responce['error'] = __( 'Email already exists.', 'shopperexpress' );
			} else {
				$responce['error'] = __( 'Error Occured please fill up the sign up form carefully.', 'shopperexpress' );
			}
			header( 'Content-Type: application/json' );
			echo json_encode( $responce );
		}

		die;
	}

	public function auto_login_new_user( $user, $permalink ) {
		$user = get_user_by( 'ID', $user );
		wp_set_current_user( $user->ID, $user->data->user_login );
		wp_set_auth_cookie( $user->ID );
		echo wp_new_user_notification( $user->ID, null, 'user' );
		echo $permalink;
		exit;
	}

	public function ajax_login() {
		$username = $_POST['username'];
		$password = $_POST['password'];
		$nonce    = $_POST['nonce'];
		$remember = ( isset( $_POST['remember_me'] ) && ! empty( $_POST['remember_me'] ) ? $_POST['remember_me'] : '' );

		if ( wp_verify_nonce( $nonce, 'ajax_login_none' ) && ! empty( $username ) && ! empty( $password ) ) {
			$creds = array();

			$creds['user_login']    = sanitize_text_field( $username );
			$creds['user_password'] = sanitize_text_field( $password );
			$creds['remember_me']   = sanitize_text_field( ( $remember == 'yes' ? true : false ) );

			$user = wp_signon( $creds, false );

			if ( ! is_wp_error( $user ) ) {
				echo 'success';
			} elseif ( ! empty( $user->errors['invalid_username'] ) ) {

					echo $user->errors['invalid_username'][0];
			} elseif ( ! empty( $user->errors['invalid_email'] ) ) {
				echo $user->errors['invalid_email'][0];
			} elseif ( ! empty( $user->errors['incorrect_password'] ) ) {
				echo $user->errors['incorrect_password'][0];
			}
		}

		die;
	}

	public function adf_action() {

		$template = null;

		if ( ! empty( $_REQUEST['template'] ) ) {

			while ( have_rows( 'adf_templates', 'options' ) ) :
				the_row();
				if ( get_row_index() == $_REQUEST['template'] ) {
					$subject  = get_sub_field( 'subject' );
					$template = get_sub_field( 'template' );
					break;
				}
			endwhile;

			$template = str_replace( '{{date}}', date( 'm-d-Y' ), $template );

			if ( ! empty( $_REQUEST['userjourney'] ) ) {
				$userjourney = stripslashes( $_REQUEST['userjourney'] );
				$userjourney = json_decode( $userjourney, true );
				$vars        = array(
					'AdGroupId',
					'CampaignId',
					'MediaType',
					'ReferrerSite',
					'IPAddress',
				);
				foreach ( $vars as $var ) {
					$template = str_replace( '{{' . $var . '}}', $userjourney['value'][0][ $var ], $template );
				}
				$journey = '';
				foreach ( $userjourney['value'] as $index => $item ) {
					$journey .= '
					<id source="BrowseHistory" sequence="' . $index . '">' . $item['PageUrl'] . '</id>';
				}
				$template = str_replace( '{{BrowseHistory}}', $journey, $template );
			}

			foreach ( $_REQUEST as $index => $value ) {
				$subject  = str_replace( '{{' . $index . '}}', $value, $subject );
				$template = str_replace( '{{' . $index . '}}', $value, $template );
			}

			// Only forms in the "WP Forms — ADF Form IDs" whitelist (SOC → Lead Delivery)
			// are allowed to trigger the *API* leg. An empty whitelist leaves everything
			// exactly as it was before this check existed. Matches by form_id when the
			// webhook sends one, otherwise falls back to Form_Name — which every existing
			// webhook already sends — so no webhook reconfiguration is required.
			$form_id    = sanitize_text_field( wp_unslash( $_REQUEST['form_id'] ?? '' ) );
			$form_name  = sanitize_text_field( wp_unslash( $_REQUEST['Form_Name'] ?? '' ) );
			$is_tracked = wps_is_adf_tracked_form( $form_id, $form_name );

			if ( ! empty( $_REQUEST['delivery_address'] ) ) {
				$adf_method = get_option( 'adf_delivery_method', 'email' );

				// Downgrade api/both to email-only when this form isn't tracked — the
				// configured delivery method itself is otherwise left untouched.
				if ( ! $is_tracked && 'email' !== $adf_method ) {
					$adf_method = 'email';
				}

				// Email leg — unchanged legacy behavior for 'email' and 'both' delivery methods.
				if ( 'api' !== $adf_method ) {
					wp_mail( $_REQUEST['delivery_address'], $subject, $template, array( 'content-type: text/plain' ) );
				}

				// API leg — reuse the existing wps_dispatch_adf() API path (same code that
				// already sends/logs API leads elsewhere) for 'api' and 'both'. Force the
				// method to 'api' for this one call so it never also fires its own email
				// leg here — the email above already covers 'email' and 'both'.
				if ( 'api' === $adf_method || 'both' === $adf_method ) {
					$lead_fields = array(
						'first_name'  => sanitize_text_field( $_REQUEST['first_name'] ?? '' ),
						'last_name'   => sanitize_text_field( $_REQUEST['last_name'] ?? '' ),
						'email'       => sanitize_email( $_REQUEST['email'] ?? '' ),
						'phone'       => sanitize_text_field( $_REQUEST['phone'] ?? '' ),
						'form_name'   => sanitize_text_field( $_REQUEST['Form_Name'] ?? 'adf_action' ),
						'lead_source' => sanitize_text_field( $_REQUEST['prospect_source'] ?? '' ),
					);

					$force_api = static function () {
						return 'api';
					};
					add_filter( 'pre_option_adf_delivery_method', $force_api );
					wps_dispatch_adf( $template, $lead_fields );
					remove_filter( 'pre_option_adf_delivery_method', $force_api );
				}
			} else {
				$adf_method = get_option( 'adf_delivery_method', 'email' );

				if ( ! $is_tracked && ( 'api' === $adf_method || 'both' === $adf_method ) ) {
					$force_email = static function () {
						return 'email';
					};
					add_filter( 'pre_option_adf_delivery_method', $force_email );
				}

				$lead_fields = array(
					'first_name'  => sanitize_text_field( $_REQUEST['first_name'] ?? '' ),
					'last_name'   => sanitize_text_field( $_REQUEST['last_name'] ?? '' ),
					'email'       => sanitize_email( $_REQUEST['email'] ?? '' ),
					'phone'       => sanitize_text_field( $_REQUEST['phone'] ?? '' ),
					'form_name'   => sanitize_text_field( $_REQUEST['Form_Name'] ?? 'adf_action' ),
					'lead_source' => sanitize_text_field( $_REQUEST['prospect_source'] ?? '' ),
				);
				wps_dispatch_adf( $template, $lead_fields );

				if ( isset( $force_email ) ) {
					remove_filter( 'pre_option_adf_delivery_method', $force_email );
				}
			}

			wp_send_json_success(
				array(
					'message'   => 'Lead submitted.',
					'asc_event' => array(
						'event'       => 'asc_lead_submit',
						'event_owner' => 'intice',
						'lead'        => array(
							'first_name' => sanitize_text_field( $_REQUEST['first_name'] ?? '' ),
							'last_name'  => sanitize_text_field( $_REQUEST['last_name'] ?? '' ),
							'email'      => sanitize_email( $_REQUEST['email'] ?? '' ),
							'phone'      => sanitize_text_field( $_REQUEST['phone'] ?? '' ),
						),
					),
				)
			);
		}
		exit;
	}

	/**
	 * Accept raw lead data and dispatch it via adf_email().
	 *
	 * Expected POST params: first_name, last_name, email, phone, comments, zip, nonce.
	 *
	 * @return void
	 */
	public function submit_adf_lead(): void {
		if ( ! check_ajax_referer( 'submit_adf_lead', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce.' ), 403 );
		}

		$first_name = sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) );
		$last_name  = sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) );
		$email      = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$phone      = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );

		if ( '' === $first_name || '' === $last_name || ! is_email( $email ) || '' === $phone ) {
			wp_send_json_error( array( 'message' => 'Missing required fields.' ), 422 );
		}

		$lead_fields = array(
			'first_name' => $first_name,
			'last_name'  => $last_name,
			'email'      => $email,
			'phone'      => $phone,
			'comments'   => sanitize_text_field( wp_unslash( $_POST['comments'] ?? '' ) ),
			'zip'        => sanitize_text_field( wp_unslash( $_POST['zip'] ?? '' ) ),
		);

		$template_row = wps_resolve_adf_template();

		if ( null === $template_row ) {
			wp_send_json_error( array( 'message' => 'No ADF template is configured.' ), 500 );
		}

		$xml = wps_render_adf_template( $template_row['template'], $lead_fields );

		$result = wps_dispatch_adf(
			$xml,
			array(
				'first_name' => $first_name,
				'last_name'  => $last_name,
				'email'      => $email,
				'phone'      => $phone,
				'form_name'  => sanitize_text_field( wp_unslash( $_POST['form_name'] ?? 'submit_adf_lead' ) ),
			)
		);

		if ( $result['success'] ) {
			wp_send_json_success( array( 'message' => 'Lead submitted.' ) );
		} else {
			wp_send_json_error( array( 'message' => 'Lead delivery failed.' ), 500 );
		}
	}

	public function unlock_form() {
		if ( ! empty( $_REQUEST['action'] ) && $_REQUEST['action'] == 'unlock_form' ) {

			echo '<script src="' . home_url( '/wp-content/plugins/wpforms/assets/js/frontend/wpforms.min.js' ) . '"></script>';

			$raw_post_id = $_REQUEST['post_id'] ?? '';
			$form_id     = ! empty( $_REQUEST['form_id'] ) ? $_REQUEST['form_id'] : '';

			if ( empty( $form_id ) ) {
				if ( wps_auth() ) {
					$form_id = get_field( 'contact_savings_form', 'options' );
				} else {
					$form_id = get_field( 'unlock_savings_form', 'options' );
				}
			}

			// VIN (non-numeric) — API mode: no WP post context needed.
			if ( ! is_numeric( $raw_post_id ) ) {
				if ( $form_id ) {
					echo do_shortcode( '[wpforms id="' . $form_id . '" title="false"]' );
				}
				exit;
			} else {
				$query = new WP_Query(
					array(
						'post_type'      => 'any',
						'post__in'       => array( absint( $raw_post_id ) ),
						'posts_per_page' => 1,
					)
				);

				if ( $query->have_posts() ) {
					while ( $query->have_posts() ) {
						$query->the_post();
						if ( $form_id ) {
							echo do_shortcode( '[wpforms id="' . $form_id . '" title="false"]' );
						}
					}
					wp_reset_query();
				} elseif ( $form_id ) {
					echo do_shortcode( '[wpforms id="' . $form_id . '" title="false"]' );
				}
			}

			exit;
		}
	}

	public function save_listing() {
		// check_ajax_referer('shopperexpress_nonce', 'security');

		if ( empty( $_POST['acf'] ) || empty( $_POST['post_id'] ) ) {
			wp_send_json_error( array( 'message' => 'Missing required data' ) );
		}

		$post_id = absint( $_POST['post_id'] );

		acf_save_post( $post_id );

		wp_send_json_success(
			array(
				'message' => 'Fields saved successfully!',
			)
		);
	}

	public function offers_form() {
		if ( ! empty( $_REQUEST['action'] ) && $_REQUEST['action'] == 'offers_form' ) {

			echo '<script src="' . home_url( '/wp-content/plugins/wpforms/assets/js/frontend/wpforms.min.js' ) . '"></script>';

			$query   = new WP_Query(
				array(
					'post_type'      => 'any',
					'post__in'       => array( $_REQUEST['post_id'] ),
					'posts_per_page' => 1,
				)
			);
			$form_id = ! empty( $_REQUEST['form_id'] ) ? $_REQUEST['form_id'] : '';

			if ( ! empty( $_REQUEST['target'] ) ) {
				switch ( $_REQUEST['target'] ) {
					case '#Disclosure_lease-acf':
						$form_id = get_field( 'form_lease_special', 'options' );
						break;
					case '#Disclosure_loan-acf':
						$form_id = get_field( 'form_id_special_apr', 'options' );
						break;
					default:
						$form_id = get_field( 'offers_form_cash', 'options' );
						break;
				}
			} else {
				$form_id = get_field( 'form_id_special_apr', 'options' ) ? get_field( 'form_id_special_apr', 'options' ) : get_field( 'form_special_apr', 'options' );
			}

			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) {
					$query->the_post();
					if ( $form_id ) {
						echo do_shortcode( '[wpforms id="' . $form_id . '" title="false"]' );
					}
				}
				wp_reset_query();
			} elseif ( $form_id ) {
					echo do_shortcode( '[wpforms id="' . $form_id . '" title="false"]' );
			}

			exit;
		}
	}

	// ─── API Favorites (VIN-based) ────────────────────────────────────────────

	/**
	 * Toggle a VIN favorite for the current user (logged-in: user_meta, guest: cookie).
	 */
	public function api_favorite_toggle() {
		$vin       = strtoupper( sanitize_text_field( wp_unslash( $_POST['vin'] ?? '' ) ) );
		$post_type = sanitize_key( $_POST['post_type'] ?? 'listings' );
		$status    = ( isset( $_POST['status'] ) && 'active' === $_POST['status'] ) ? 'active' : 'inactive';

		$allowed_types = array( 'listings', 'used-listings' );
		if ( ! $vin || ! in_array( $post_type, $allowed_types, true ) ) {
			wp_send_json_error( array( 'message' => 'Invalid data' ) );
		}

		$favorites = $this->get_api_favorites();

		if ( 'active' === $status ) {
			if ( ! in_array( $vin, $favorites[ $post_type ] ?? array(), true ) ) {
				$favorites[ $post_type ][] = $vin;
			}
		} else {
			$favorites[ $post_type ] = array_values(
				array_filter( $favorites[ $post_type ] ?? array(), fn( $v ) => $v !== $vin )
			);
		}

		$this->save_api_favorites( $favorites );

		wp_send_json_success(
			array(
				'active' => 'active' === $status,
				'count'  => count( $favorites[ $post_type ] ),
			)
		);
	}

	/**
	 * Return all API VIN favorites grouped by post_type.
	 */
	public function api_favorites_list() {
		wp_send_json_success( $this->get_api_favorites() );
	}

	/**
	 * Read API favorites from user_meta (logged-in) or cookie (guest).
	 *
	 * @return array  e.g. [ 'listings' => ['VIN1'], 'used-listings' => ['VIN2'] ]
	 */
	private function get_api_favorites(): array {
		if ( is_user_logged_in() ) {
			$meta = get_user_meta( get_current_user_id(), 'wps_api_favorites', true );
			return is_array( $meta ) ? $meta : array();
		}

		$raw = isset( $_COOKIE['wps_api_favorites'] )
			? json_decode( stripslashes( $_COOKIE['wps_api_favorites'] ), true )
			: null;

		return is_array( $raw ) ? $raw : array();
	}

	/**
	 * Persist API favorites.
	 *
	 * @param array $favorites
	 */
	private function save_api_favorites( array $favorites ): void {
		if ( is_user_logged_in() ) {
			update_user_meta( get_current_user_id(), 'wps_api_favorites', $favorites );
		} else {
			setcookie(
				'wps_api_favorites',
				wp_json_encode( $favorites ),
				time() + 30 * DAY_IN_SECONDS,
				COOKIEPATH,
				COOKIE_DOMAIN,
				is_ssl(),
				false
			);
		}
	}

	/**
	 * Render saved API vehicles as HTML cards for the saved page.
	 */
	public function api_render_favorites() {
		$post_type = sanitize_key( $_POST['post_type'] ?? '' );
		$vins      = $_POST['vins'] ?? array();

		$allowed = array( 'listings', 'used-listings' );
		if ( ! in_array( $post_type, $allowed, true ) || ! is_array( $vins ) ) {
			wp_send_json_error( array( 'message' => 'Invalid data' ) );
		}

		if ( ! class_exists( '\App\Components\Api\Intice_Api_Client' ) ) {
			wp_send_json_error( array( 'message' => 'API client not available' ) );
		}

		$client = \App\Components\Api\Intice_Api_Client::instance();

		ob_start();
		foreach ( $vins as $vin ) {
			$vin     = strtoupper( sanitize_text_field( $vin ) );
			$vehicle = $client->get_vehicle( $vin );
			if ( is_wp_error( $vehicle ) || empty( $vehicle ) ) {
				continue;
			}
			$permalink = home_url( '/' . $post_type . '/' . $vin . '/' );
			get_template_part(
				'template-parts/content-listings-api',
				null,
				array(
					'vehicle'   => $vehicle,
					'post_type' => $post_type,
					'permalink' => $permalink,
					'loged'     => is_user_logged_in() ? 'true' : '',
				)
			);
		}
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * Save vehicle fields via Intice Nexus API (API mode VDP edit modal).
	 *
	 * Expects POST: vin, nonce, and any number of vehicle fields as flat keys.
	 * Top-level fields (year, make, model, trim, price, mileage, condition, etc.)
	 * are sent directly; all others go into the "payload" sub-object.
	 *
	 * @return void
	 */
	public function api_save_vehicle(): void {
		if ( ! wps_check_current_usser() ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}

		if ( ! check_ajax_referer( 'wps_api_save_vehicle', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ), 403 );
		}

		$vin = strtoupper( sanitize_text_field( wp_unslash( $_POST['vin'] ?? '' ) ) );

		if ( ! $vin || ! preg_match( '/^[A-HJ-NPR-Z0-9]{17}$/i', $vin ) ) {
			wp_send_json_error( array( 'message' => 'Invalid VIN' ) );
		}

		// Top-level API vehicle fields.
		$top_level_fields = array(
			'year', 'make', 'model', 'trim', 'condition', 'mileage',
			'price', 'msrp', 'price_sort', 'stock', 'exterior_color', 'interior_color',
			'body_style', 'drivetrain', 'fuel_type', 'transmission',
			'certified', 'sold', 'use_images_list',
			'primary_image_url', 'primary_thumb_url',
		);

		// Multi-line payload fields — preserve line breaks instead of
		// collapsing them with sanitize_text_field().
		$textarea_fields = array(
			'message', 'information', 'vehicle_overview',
		);

		$top    = array();
		$payload = array();

		foreach ( $_POST as $key => $value ) {
			if ( in_array( $key, array( 'action', 'vin', 'nonce' ), true ) ) {
				continue;
			}
			$clean_value = in_array( $key, $textarea_fields, true )
				? sanitize_textarea_field( wp_unslash( $value ) )
				: sanitize_text_field( wp_unslash( $value ) );
			if ( in_array( $key, $top_level_fields, true ) ) {
				$top[ $key ] = $clean_value;
			} else {
				$payload[ $key ] = $clean_value;
			}
		}

		$data = $top;
		if ( ! empty( $payload ) ) {
			$data['payload'] = $payload;
		}

		$result = \App\Components\Api\Intice_Api_Client::instance()->update_vehicle( $vin, $data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Data saved successfully!', 'shopperexpress' ) ) );
	}

	/**
	 * Trash a vehicle via Intice Nexus API (API mode VDP edit modal).
	 *
	 * Expects POST: vin, nonce. Soft-deletes the vehicle on the Nexus side
	 * (DELETE /api/v1/vehicles/{vin}) — it disappears from the site immediately
	 * but is restored automatically on the next re-sync if still in the source feed.
	 *
	 * @return void
	 */
	public function api_delete_vehicle(): void {
		if ( ! wps_check_current_usser() ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		}

		if ( ! check_ajax_referer( 'wps_api_delete_vehicle', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ), 403 );
		}

		$vin = strtoupper( sanitize_text_field( wp_unslash( $_POST['vin'] ?? '' ) ) );

		if ( ! $vin || ! preg_match( '/^[A-HJ-NPR-Z0-9]{17}$/i', $vin ) ) {
			wp_send_json_error( array( 'message' => 'Invalid VIN' ) );
		}

		if ( ! class_exists( '\App\Components\Api\Intice_Api_Client' ) ) {
			wp_send_json_error( array( 'message' => 'API client not available' ) );
		}

		$result = \App\Components\Api\Intice_Api_Client::instance()->delete_vehicle( $vin );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Vehicle trashed.', 'shopperexpress' ) ) );
	}
}
