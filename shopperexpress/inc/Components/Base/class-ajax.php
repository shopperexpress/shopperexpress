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
				'register_user'   => 'register_user',
				'ajax_login'      => 'ajax_login',
				'adf'             => 'adf_action',
				'submit_adf_lead' => 'submit_adf_lead',
				'favorite'        => 'favorite_action',
				'get_pdf'         => 'get_pdf',
			),
			'admin-ajax'        => array(
				'save_listing'    => 'save_listing',
				'delete_listings' => 'delete_listings',
				'clear'           => 'clear_action',
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
			wp_send_json_error(
				array(
					'message'   => 'Failed to generate VDR',
					'http_code' => $http_code,
				)
			);
		}

		file_put_contents( $file_path, $pdf );

		wp_send_json_success(
			array(
				'url'    => $file_url,
				'cached' => false,
			)
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

			if ( ! empty( $_REQUEST['delivery_address'] ) ) {
				wp_mail( $_REQUEST['delivery_address'], $subject, $template, array( 'content-type: text/plain' ) );
			} elseif ( have_rows( 'email_notification', 'options' ) ) {

					$mail_to = array();

				while ( have_rows( 'email_notification', 'options' ) ) :
					the_row();
					$mail_to[] = get_sub_field( 'email' );
					endwhile;

					wp_mail( implode( ', ', $mail_to ), $subject, $template, array( 'content-type: text/plain' ) );
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

		adf_email(
			array(
				'first_name' => $first_name,
				'last_name'  => $last_name,
				'email'      => $email,
				'phone'      => $phone,
				'comments'   => sanitize_text_field( wp_unslash( $_POST['comments'] ?? '' ) ),
				'zip'        => sanitize_text_field( wp_unslash( $_POST['zip'] ?? '' ) ),
			)
		);

		wp_send_json_success( array( 'message' => 'Lead submitted.' ) );
	}

	public function unlock_form() {
		if ( ! empty( $_REQUEST['action'] ) && $_REQUEST['action'] == 'unlock_form' ) {

			echo '<script src="' . home_url( '/wp-content/plugins/wpforms/assets/js/frontend/wpforms.min.js' ) . '"></script>';

			$query   = new WP_Query(
				array(
					'post_type'      => 'any',
					'post__in'       => array( $_REQUEST['post_id'] ),
					'posts_per_page' => 1,
				)
			);
			$form_id = ! empty( $_REQUEST['form_id'] ) ? $_REQUEST['form_id'] : '';

			if ( empty( $form_id ) ) {
				if ( wps_auth() ) {
					$form_id = get_field( 'contact_savings_form', 'options' );
				} else {
					$form_id = get_field( 'unlock_savings_form', 'options' );
				}
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
}
