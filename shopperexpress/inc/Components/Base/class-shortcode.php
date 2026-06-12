<?php
/**
 * WordPress Ajax.
 *
 * @package Shopperexpress
 */

namespace App\Components\Base;

use App\Components\Api\Intice_Api_Client;
use App\Components\Api\Intice_VDP;
use App\Components\Theme_Component;
use WP_Query;

/**
 * Class Shortcode
 *
 * @package App\Components\Base
 */
class Shortcode implements Theme_Component {

	/** @var array|null Per-request API vehicle cache. */
	private ?array $_api_vehicle_cache = null;

	/** @var bool Whether api_vehicle() has been called this request. */
	private bool $_api_vehicle_fetched = false;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action(
			'init',
			function () {

				$methods = get_class_methods( $this );

				foreach ( $methods as $method ) {
					if ( in_array( $method, array( 'register', '__construct' ), true ) ) {
						continue;
					}

					add_shortcode( $method, array( $this, $method ) );
				}
			}
		);
	}

	/**
	 * Fetch the API vehicle for the current VDP page.
	 *
	 * VIN resolution order:
	 *  1. $vin argument (explicit override, e.g. from ConversionBlock context)
	 *  2. intice_vin query var set by Intice_VDP::maybe_serve_vdp()
	 *
	 * Result is cached for the request lifetime; the API client also caches via transients.
	 * get_vehicle() returns { data: {...} } — we unwrap and cache only the inner vehicle object.
	 *
	 * @param string|null $vin Optional VIN override.
	 * @return array|null Vehicle array from Intice Nexus API, or null on miss/error.
	 */
	private function api_vehicle( int|string $post_id = 0 ): ?array {
		if ( $this->_api_vehicle_fetched ) {
			return $this->_api_vehicle_cache;
		}

		$this->_api_vehicle_fetched = true;

		// If a VIN string was passed directly (17-char alphanum), use it as-is.
		if ( is_string( $post_id ) && preg_match( '/^[A-HJ-NPR-Z0-9]{17}$/i', $post_id ) ) {
			$vin = strtoupper( $post_id );
		} else {
			$vin = get_query_var( Intice_VDP::QUERY_VAR_VIN );

			if ( ! $vin && $post_id ) {
				$vin = get_post_meta( (int) $post_id, 'vin_number', true );
			}
		}

		if ( ! $vin ) {
			return null;
		}

		$result = Intice_Api_Client::instance()->get_vehicle( $vin );

		// API returns { "data": { ...vehicle fields... } } — unwrap the data key.
		$vehicle = ( ! is_wp_error( $result ) && ! empty( $result['data'] ) && is_array( $result['data'] ) )
			? $result['data']
			: null;

		$this->_api_vehicle_cache = $vehicle;

		return $this->_api_vehicle_cache;
	}

	/**
	 * Lease Payment shortcode.
	 *
	 * Usage: [lease_payment id="POST_ID"]
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function lease_payment( $atts = array() ) {
		$post_id = ! empty( $atts['id'] ) ? $atts['id'] : get_the_ID();

		if ( \App\is_api_mode() ) {
			$vehicle = $this->api_vehicle( $post_id );
			$value   = $vehicle['payload']['lease_payment'] ?? '';
			return $value ? esc_html( $value ) : '';
		}

		$post_id = ! empty( $atts['id'] ) ? $atts['id'] : get_the_ID();
		$value   = get_field( 'lease_payment', $post_id );
		return $value ? esc_html( $value ) : '';
	}

	/**
	 * Year shortcode.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function year( $atts = array() ) {
		$post_id = ! empty( $atts['id'] ) ? $atts['id'] : get_the_ID();

		if ( \App\is_api_mode() ) {
			$vehicle = $this->api_vehicle( $post_id );
			$value   = $vehicle['year'] ?? '';
			return $value ? esc_html( $value ) : '';
		}

		$post_id = ! empty( $atts['id'] ) ? (int) $atts['id'] : get_the_ID();
		return esc_html( get_field( 'year', $post_id ) ?: '' );
	}

	/**
	 * Make shortcode.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function make( $atts = array() ) {
		$post_id = ! empty( $atts['id'] ) ? $atts['id'] : get_the_ID();

		if ( \App\is_api_mode() ) {
			$vehicle = $this->api_vehicle( $post_id );
			$value   = $vehicle['make'] ?? '';
			return $value ? esc_html( $value ) : '';
		}

		$post_id = ! empty( $atts['id'] ) ? (int) $atts['id'] : get_the_ID();
		return esc_html( get_field( 'make', $post_id ) ?: '' );
	}

	/**
	 * Model shortcode.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function model( $atts = array() ) {
		$post_id = ! empty( $atts['id'] ) ? $atts['id'] : get_the_ID();

		if ( \App\is_api_mode() ) {
			$vehicle = $this->api_vehicle( $post_id );
			$value   = $vehicle['model'] ?? '';
			return $value ? esc_html( $value ) : '';
		}

		$post_id = ! empty( $atts['id'] ) ? (int) $atts['id'] : get_the_ID();
		return esc_html( get_field( 'model', $post_id ) ?: '' );
	}

	/**
	 * Trim shortcode.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function trim( $atts = array() ) {
		$post_id = ! empty( $atts['id'] ) ? $atts['id'] : get_the_ID();

		if ( \App\is_api_mode() ) {
			$vehicle = $this->api_vehicle( $post_id );
			$value   = $vehicle['trim'] ?? '';
			return $value ? esc_html( $value ) : '';
		}

		$post_id = ! empty( $atts['id'] ) ? (int) $atts['id'] : get_the_ID();
		return esc_html( get_field( 'trim', $post_id ) ?: '' );
	}

	/**
	 * Stock count shortcode.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function stock( $atts = array() ) {
		$condition = ! empty( $atts['condition'] ) ? strtolower( $atts['condition'] ) : 'new';

		if ( \App\is_api_mode() ) {
			$result = Intice_Api_Client::instance()->get_vehicles( array( 'condition' => $condition ) );
			if ( ! is_wp_error( $result ) ) {
				return (string) ( $result['meta']['total'] ?? count( $result['data'] ?? array() ) );
			}
			return '0';
		}

		$post_type = $condition == 'used' ? 'used-listings' : 'listings';

		$args = array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		);

		$query = new WP_Query( $args );

		return $query->post_count;
	}

	/**
	 * Show — generic field/taxonomy reader.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function show( $atts = array() ) {

		if ( ! is_singular() ) {
			return '';
		}

		if ( \App\is_api_mode() ) {
			$post_id = ! empty( $atts['id'] ) ? $atts['id'] : get_the_ID();
			$vehicle = $this->api_vehicle( $post_id );

			if ( $vehicle ) {
				$api_field_map = array(
					'year'         => 'year',
					'make'         => 'make',
					'model'        => 'model',
					'trim'         => 'trim',
					'vin'          => 'vin',
					'vin_number'   => 'vin',
					'stock_number' => 'stock',
					'stock'        => 'stock',
					'condition'    => 'condition',
					'mileage'      => 'mileage',
					'price'        => 'price',
					'msrp'         => 'msrp',
					'body_style'   => 'body_style',
					'drivetrain'   => 'drivetrain',
					'fuel_type'    => 'fuel_type',
					'certified'    => 'certified',
					'sold'         => 'sold',
				);

				$key = ! empty( $atts['tax'] ) ? $atts['tax'] : ( $atts['field'] ?? '' );

				if ( $key ) {
					$api_key = $api_field_map[ $key ] ?? null;

					if ( $api_key && isset( $vehicle[ $api_key ] ) ) {
						$output = $vehicle[ $api_key ];
						return is_array( $output ) ? '' : esc_html( (string) $output );
					}

					if ( isset( $vehicle['payload'][ $key ] ) ) {
						$output = $vehicle['payload'][ $key ];
						return is_array( $output ) ? '' : esc_html( (string) $output );
					}
				}

				return '';
			}
		}

		$post_id = get_the_ID();

		if ( ! empty( $atts['tax'] ) ) {
			return get_field( $atts['tax'], $post_id );
		}

		if ( ! empty( $atts['field'] ) ) {
			$output = get_field( $atts['field'], $post_id );
			return is_array( $output ) ? '' : (string) $output;
		}

		return '';
	}

	/**
	 * Price
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function price( $atts = array() ) {
		$output = "<span class='js-is-empty'>";

		$post_id = ! empty( $atts['id'] ) ? $atts['id'] : get_the_ID();

		if ( \App\is_api_mode() ) {
			$vehicle = $this->api_vehicle( $post_id );
			$price   = $vehicle['price'] ?? '';
			if ( $price ) {
				$output .= esc_html( $price );
			}
		} else {
			$post_id = ! empty( $atts['id'] ) ? $atts['id'] : get_the_ID();
			$price   = get_field( 'price', $post_id );
			if ( $price ) {
				$output .= $price;
			}
		}

		$output .= '</span>';
		return $output;
	}

	/**
	 * Loan Term
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function loan_term( $atts = array() ) {
		$output  = "<span class='js-is-empty'>";
		$post_id = ! empty( $atts['id'] ) ? $atts['id'] : get_the_ID();

		if ( \App\is_api_mode() ) {
			$vehicle = $this->api_vehicle( $post_id );
			$value   = $vehicle['payload']['loanterm'] ?? '';
		} else {
			$post_id = ! empty( $atts['id'] ) ? (int) $atts['id'] : get_the_ID();
			$value   = get_field( 'loanterm', $post_id );
		}

		if ( $value ) {
			$output .= $value;
		}

		$output .= '</span>';
		return $output;
	}

	/**
	 * Loan APR
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function loan_apr( $atts = array() ) {
		$output  = "<span class='js-is-empty'>";
		$post_id = ! empty( $atts['id'] ) ? $atts['id'] : get_the_ID();

		if ( \App\is_api_mode() ) {
			$vehicle = $this->api_vehicle( $post_id );
			$value   = $vehicle['payload']['loanapr'] ?? '';
		} else {
			$post_id = ! empty( $atts['id'] ) ? (int) $atts['id'] : get_the_ID();
			$value   = get_field( 'loanapr', $post_id );
		}

		if ( $value ) {
			$output .= $value;
		}

		$output .= '</span>';
		return $output;
	}

	/**
	 * Lease Term
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function lease_term( $atts = array() ) {
		$output  = "<span class='js-is-empty'>";
		$post_id = ! empty( $atts['id'] ) ? $atts['id'] : get_the_ID();

		if ( \App\is_api_mode() ) {
			$vehicle = $this->api_vehicle( $post_id );
			$value   = $vehicle['payload']['leaseterm'] ?? '';
		} else {
			$post_id = ! empty( $atts['id'] ) ? (int) $atts['id'] : get_the_ID();
			$value   = get_field( 'leaseterm', $post_id );
		}

		if ( $value ) {
			$output .= $value;
		}

		$output .= '</span>';
		return $output;
	}

	/**
	 * Down Payment
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function due_at_signing( $atts = array() ) {
		$output  = "<span class='js-is-empty'>";
		$post_id = ! empty( $atts['id'] ) ? $atts['id'] : get_the_ID();

		if ( \App\is_api_mode() ) {
			$vehicle = $this->api_vehicle( $post_id );
			$value   = $vehicle['payload']['down_payment'] ?? '';
		} else {
			$post_id = ! empty( $atts['id'] ) ? (int) $atts['id'] : get_the_ID();
			$value   = get_field( 'down_payment', $post_id );
		}

		if ( $value ) {
			$output .= $value;
		}

		$output .= '</span>';
		return $output;
	}

	/**
	 * Total of Payments
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function total_of_payments( $atts = array() ) {
		$output  = "<span class='js-is-empty'>";
		$post_id = ! empty( $atts['id'] ) ? $atts['id'] : get_the_ID();

		if ( \App\is_api_mode() ) {
			$vehicle = $this->api_vehicle( $post_id );
			$value   = $vehicle['payload']['totalofpmts'] ?? '';
		} else {
			$post_id = ! empty( $atts['id'] ) ? (int) $atts['id'] : get_the_ID();
			$value   = get_field( 'totalofpmts', $post_id );
		}

		if ( $value ) {
			$output .= $value;
		}

		$output .= '</span>';
		return $output;
	}

	/**
	 * Offer Payment
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function offer_payment( $atts = array() ) {
		$acf_prefix = 'service-';
		$post_id    = ! empty( $atts['id'] ) ? $atts['id'] : get_the_ID();

		if ( \App\is_api_mode() ) {
			$vehicle       = $this->api_vehicle( $post_id );
			$payload       = $vehicle['payload'] ?? array();
			$condition     = $vehicle['condition'] ?? '';
			$loanterm      = $payload['loanterm'] ?? '';
			$loanapr       = $payload['loanapr'] ?? '';
			$down_payment  = $payload['down_payment'] ?? '';
			$lease_payment = $payload['lease_payment'] ?? '';
			$loan_payment  = $payload['loan_payment'] ?? '';
			$leaseterm     = $payload['leaseterm'] ?? '';
			// API condition is lowercase: 'new', 'used', 'certified'.
			$is_used = $condition === 'used';
		} else {
			global $post;
			$post_id       = $post->ID;
			$condition     = wps_get_term( $post_id, 'condition' );
			$loanterm      = get_field( 'loanterm', $post_id );
			$loanapr       = get_field( 'loanapr', $post_id );
			$down_payment  = wps_get_term( $post_id, 'down-payment' );
			$lease_payment = wps_get_term( $post_id, 'lease-payment' );
			$loan_payment  = wps_get_term( $post_id, 'loan-payment' );
			$leaseterm     = wps_get_term( $post_id, 'leaseterm' );
			// WP condition values: 'New', 'Slightly Used', 'Used'.
			$is_used = in_array( $condition, array( 'Slightly Used', 'Used' ), true );
		}

		while ( have_rows( $acf_prefix . 'offers_flexible_content', 'options' ) ) :
			the_row();
			if ( get_row_layout() == 'payment' && have_rows( 'payment_list' ) ) {
				while ( have_rows( 'payment_list' ) ) :
					the_row();
					$link         = get_sub_field( 'link' );
					$lock         = get_sub_field( 'lock' );
					$show_payment = $lock ? get_sub_field( 'show_payment' ) : false;
					$show_event   = get_sub_field( 'show_event' );

					$down_payment = ! empty( $down_payment ) ? $down_payment : number_format( $price );

					switch ( $atts['type'] ) {
						case 'lease-payment':
							if ( $down_payment && $lease_payment ) {
								$lease_payment = ! empty( $lease_payment ) ? '$' . number_format( $lease_payment ) : null;
								$output        = ! empty( $lease_payment ) ? '<span class="savings">$' . $down_payment . ' ' . __( 'DOWN', 'shopperexpress' ) . '</span>' . $lease_payment . ' <sub>/mo</sub>' : null;
							} else {
								$output = null;
							}
							break;

						case 'Disclosure_loan':
							if ( ! $is_used ) {
								$output = $loanterm ? '<span class="savings">' . $loanterm . ' ' . __( 'mos.', 'shopperexpress' ) . '</span>' : '';
								if ( $loanapr ) {
									$output .= $loanapr . '% <sub>APR</sub>';
								}
							} else {
								$output = 2;
							}
							break;

						case 'Disclosure_lease':
							if ( $down_payment && $lease_payment ) {
								$lease_payment = ! empty( $lease_payment ) && $lease_payment != 'None' && $lease_payment > 0 ? '$' . number_format( $lease_payment ) : null;
								$output        = ! empty( $lease_payment ) ? '<span class="savings">$' . $down_payment . ' ' . __( 'DOWN', 'shopperexpress' ) . ' ' . $leaseterm . ' ' . __( 'mos.', 'shopperexpress' ) . '</span>' . $lease_payment . ' <sub>/mo</sub>' : null;
							} else {
								$output = null;
							}
							break;

						case 'Disclosure_Cash':
							if ( ! $is_used ) {
								$cash_offer       = get_field( 'cash_offer' );
								$cash_offer       = is_int( $cash_offer ) ? '$' . number_format( $cash_offer ) : $cash_offer;
								$cash_offer_label = get_field( 'cash_offer_label' );
								$output           = ! empty( $cash_offer ) ? '<span class="savings">' . $cash_offer_label . '</span>' . $cash_offer : null;
							} else {
								$output = null;
							}
							break;

						default:
							$loan_payment = ! empty( $loan_payment ) && $loan_payment != 'None' ? '$' . number_format( $loan_payment ) . ' <sub>/mo</sub>' : null;
							$output       = ! empty( $loan_payment ) ? '<span class="savings">$' . $down_payment . ' ' . __( 'DOWN', 'shopperexpress' ) . '</span>' . $loan_payment : null;
							break;
					}

				endwhile;
			}
		endwhile;

		return strip_tags( $output );
	}

	/**
	 * Offer Content
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function offer_content( $atts = array() ) {
		global $post;
		$post_id = $post->ID;
		switch ( $atts['type'] ) {
			case 'lease':
				$output = get_field( 'disclosure_lease', $post_id );
				break;
			case 'loan':
				$output = get_field( 'disclosure_finance', $post_id );
				break;
			case 'cash':
				$output = get_field( 'disclosure_cash', $post_id );
				break;
		}

		return strip_tags( $output );
	}

	/**
	 * Site URL
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function site_url( $atts = array() ) {
		return get_template_directory_uri() . '/assets/dist/';
	}
}
