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
 * Class Shortcode
 *
 * @package App\Components\Base
 */
class Shortcode implements Theme_Component {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		$methods = get_class_methods( $this );

		foreach ( $methods as $method ) {
			if ( in_array( $method, array( 'register', '__construct' ), true ) ) {
				continue;
			}

			add_shortcode( $method, array( $this, $method ) );
		}
	}
	/**
	 * Show
	 *
	 * @param array $atts Attributes.
	 *
	 * @return string
	 */
	public function stock( $atts = array() ) {
		$condition = ! empty( $atts['condition'] ) ? strtolower( $atts['condition'] ) : 'new';
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
	 * Show
	 *
	 * @param array $atts Attributes.
	 *
	 * @return string
	 */
	public function show( $atts = array() ) {

		if ( ! is_singular() ) {
			return '';
		}

		$post_id = get_the_ID();

		if ( ! empty( $atts['tax'] ) ) {
			return get_field( $atts['tax'], $post_id );
		}

		if ( ! empty( $atts['field'] ) ) {

			$output = get_field( $atts['field'], $post_id );

			if ( is_array( $output ) ) {
				return '';
			}

			return (string) $output;
		}

		return '';
	}
	/**
	 * Price
	 *
	 * @param array $atts Attributes.
	 *
	 * @return string
	 */
	public function price( $atts = array() ) {
		$post_id = ! empty( $atts['id'] ) && 'post_id' !== $atts['id'] ? $atts['id'] : get_the_ID();

		$output = "<span class='js-is-empty'>";
		$price  = get_field( 'price', $post_id );
		if ( $price ) {
			$output .= $price;
		}
		$output .= '</span>';
		return $output;
	}
	/**
	 * Loan Term
	 *
	 * @param array $atts Attributes.
	 *
	 * @return string
	 */
	public function loan_term( $atts = array() ) {
		$post_id = ! empty( $atts['id'] ) && 'post_id' !== $atts['id'] ? $atts['id'] : get_the_ID();

		$output = "<span class='js-is-empty'>";
		$price  = get_field( 'loanterm', $post_id );
		if ( $price ) {
			$output .= $price;
		}
		$output .= '</span>';
		return $output;
	}
	/**
	 * Lease Term
	 *
	 * @param array $atts Attributes.
	 *
	 * @return string
	 */
	public function lease_term( $atts = array() ) {
		$post_id = ! empty( $atts['id'] ) && 'post_id' !== $atts['id'] ? $atts['id'] : get_the_ID();

		$output = "<span class='js-is-empty'>";
		$price  = get_field( 'leaseterm', $post_id );
		if ( $price ) {
			$output .= $price;
		}
		$output .= '</span>';
		return $output;
	}
	/**
	 * Down Payment
	 *
	 * @param array $atts Attributes.
	 *
	 * @return string
	 */
	public function due_at_signing( $atts = array() ) {
		$post_id = ! empty( $atts['id'] ) && 'post_id' !== $atts['id'] ? $atts['id'] : get_the_ID();

		$output = "<span class='js-is-empty'>";
		$price  = get_field( 'down_payment', $post_id );
		if ( $price ) {
			$output .= $price;
		}
		$output .= '</span>';
		return $output;
	}
	/**
	 * Total of Payments
	 *
	 * @param array $atts Attributes.
	 *
	 * @return string
	 */
	public function total_of_payments( $atts = array() ) {
		$post_id = ! empty( $atts['id'] ) && 'post_id' !== $atts['id'] ? $atts['id'] : get_the_ID();

		$output = "<span class='js-is-empty'>";
		$price  = get_field( 'totalofpmts', $post_id );
		if ( $price ) {
			$output .= $price;
		}
		$output .= '</span>';
		return $output;
	}
	/**
	 * Offer Payment
	 *
	 * @param array $atts Attributes.
	 *
	 * @return string
	 */
	public function offer_payment( $atts = array() ) {
		$acf_prefix = 'service-';

		global $post;
		$post_id = $post->ID;

		$location      = wps_get_term( $post_id, 'location' );
		$condition     = wps_get_term( $post_id, 'condition' );
		$loanterm      = get_field( 'loanterm', $post_id );
		$loanapr       = get_field( 'loanapr', $post_id );
		$down_payment  = wps_get_term( $post_id, 'down-payment' );
		$lease_payment = wps_get_term( $post_id, 'lease-payment' );
		$loan_payment  = wps_get_term( $post_id, 'loan-payment' );
		$leaseterm     = wps_get_term( $post_id, 'leaseterm' );
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
							if ( $condition != 'Slightly Used' && $condition != 'Used' ) {
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
							if ( $condition != 'Slightly Used' && $condition != 'Used' ) {
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
	 *
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
	 *
	 * @return string
	 */
	public function site_url( $atts = array() ) {
		return get_template_directory_uri() . '/assets/dist/';
	}
}
