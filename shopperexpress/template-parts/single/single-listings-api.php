<?php

/**
 * VDP template for Intice Nexus API mode.
 *
 * Loaded by Intice_VDP::maybe_serve_vdp() when the URL matches
 * /listings/{slug} or /used-listings/{slug}.
 *
 * Mirrors the HTML structure of single-listings.php so existing CSS/JS
 * continues to work. Data comes from Intice_Api_Client instead of ACF.
 *
 * @package Shopperexpress
 */

use App\Components\Api\Intice_Api_Client;
use App\Components\Api\Intice_VDP;

global $wp;
$vin       = strtoupper( $wp->query_vars[ Intice_VDP::QUERY_VAR_VIN ] ?? '' );
$post_type = sanitize_key( $wp->query_vars[ Intice_VDP::QUERY_VAR_POST_TYPE ] ?? 'listings' );

if ( ! $vin ) {
	wp_redirect( home_url( $post_type ), 302 );
	exit;
}

// ── Fetch vehicle from API ────────────────────────────────────────────────────
$client  = Intice_Api_Client::instance();
$api_res = $client->get_vehicle( $vin );

if ( is_wp_error( $api_res ) || empty( $api_res['data'] ) ) {
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	get_template_part( '404' );
	exit;
}

$v       = $api_res['data'];
$payload = $v['payload'] ?? array();

$year        = $v['year'] ?? '';
$make        = $v['make'] ?? '';
$model       = $v['model'] ?? '';
$trim        = $v['trim'] ?? '';
$stock       = $v['stock'] ?? '';
$vin_number  = $vin;
$sold        = ! empty( $v['sold'] );
$certified   = ! empty( $v['certified'] );
$images      = $v['images'] ?? ( ! empty( $v['image'] ) ? array( $v['image'] ) : array() );
$features    = $v['features'] ?? array();
$condition   = $v['condition'] ?? '';
$status      = $payload['special field 3'] ?? '';
$dealer_name = $payload['dealer name'] ?? '';


$page_title   = trim( implode( ' ', array_filter( array( $year, $make, $model, $trim ) ) ) );
$archive_link = home_url( $post_type );

$comment_footer = ( 'used-listings' === $post_type )
	? get_field( 'used_listings_comment_footer', 'options' )
	: get_field( 'comment_footer', 'options' );

// ── Yoast SEO overrides for API VDP (no real WP post) ────────────────────────
$_seo_site_name = get_bloginfo( 'name' );

// payload.seo_title overrides the auto-generated title.
if ( ! empty( $payload['seo_title'] ) ) {
	$_seo_title = $payload['seo_title'];
} else {
	$_seo_title = $page_title ? $page_title . ' | ' . $_seo_site_name : $_seo_site_name;
}

// payload.seo_description overrides the auto-generated meta description.
if ( ! empty( $payload['seo_description'] ) ) {
	$_seo_desc = $payload['seo_description'];
} else {
	$_seo_desc_parts = array_filter(
		array(
			$page_title,
			$v['condition'] ?? '',
			$v['mileage'] ? number_format( (int) $v['mileage'] ) . ' mi' : '',
			$v['exterior_color'] ?? '',
			$v['price'] ? '$' . number_format( (int) $v['price'] ) : '',
		)
	);
	$_seo_desc       = implode( ' · ', $_seo_desc_parts );
}

// payload.seo_image overrides the primary image used for OG/Twitter cards.
$_seo_image = ! empty( $payload['seo_image'] )
	? $payload['seo_image']
	: ( ! empty( $v['images'][0] ) ? $v['images'][0] : ( $v['image'] ?? '' ) );
$_seo_url   = \App\Components\Api\Intice_VDP::vdp_url( $vin, $post_type );

add_filter( 'wpseo_title', fn() => $_seo_title, 20 );
add_filter( 'wpseo_metadesc', fn() => $_seo_desc, 20 );
add_filter( 'wpseo_opengraph_title', fn() => $_seo_title, 20 );
add_filter( 'wpseo_opengraph_desc', fn() => $_seo_desc, 20 );
add_filter( 'wpseo_twitter_title', fn() => $_seo_title, 20 );
add_filter( 'wpseo_twitter_desc', fn() => $_seo_desc, 20 );
add_filter( 'wpseo_canonical', fn() => $_seo_url, 20 );

if ( $_seo_image ) {
	add_filter( 'wpseo_opengraph_image', fn() => $_seo_image, 20 );
}

// ── Vehicle JSON-LD for API VDP ──────────────────────────────────────────────
add_action(
	'wp_head',
	function () use ( $v, $post_type, $_seo_url, $_seo_desc ) {
		$permalink = $_seo_url;
		$schema    = array(
			'@context' => 'https://schema.org',
			'@type'    => 'Vehicle',
			'@id'      => esc_url( $permalink ) . '/#vehicle',
			'url'      => esc_url( $permalink ),
		);

		$name = trim( implode( ' ', array_filter( array( $v['year'] ?? '', $v['make'] ?? '', $v['model'] ?? '', $v['trim'] ?? '' ) ) ) );
		if ( $name ) {
			$schema['name'] = $name;
		}

		if ( $_seo_desc ) {
			$schema['description'] = wp_strip_all_tags( $_seo_desc );
		}

		if ( ! empty( $v['images'][0] ) ) {
			$schema['image'] = esc_url( $v['images'][0] );
		} elseif ( ! empty( $v['image'] ) ) {
			$schema['image'] = esc_url( $v['image'] );
		}

		if ( ! empty( $v['make'] ) ) {
			$schema['brand'] = array( '@type' => 'Brand', 'name' => wp_strip_all_tags( $v['make'] ) );
		}
		if ( ! empty( $v['model'] ) ) {
			$schema['model'] = wp_strip_all_tags( $v['model'] );
		}
		if ( ! empty( $v['trim'] ) ) {
			$schema['vehicleConfiguration'] = wp_strip_all_tags( $v['trim'] );
		}
		if ( ! empty( $v['year'] ) ) {
			$schema['vehicleModelDate'] = (string) $v['year'];
		}
		if ( ! empty( $v['mileage'] ) ) {
			$schema['mileageFromOdometer'] = array(
				'@type'    => 'QuantitativeValue',
				'value'    => (int) $v['mileage'],
				'unitCode' => 'SMI',
			);
		}
		if ( ! empty( $v['vin'] ) ) {
			$schema['vehicleIdentificationNumber'] = wp_strip_all_tags( $v['vin'] );
		}
		if ( ! empty( $v['engine'] ) ) {
			$schema['vehicleEngine'] = array( '@type' => 'EngineSpecification', 'description' => wp_strip_all_tags( $v['engine'] ) );
		}
		if ( ! empty( $v['fuel_type'] ) ) {
			$schema['fuelType'] = wp_strip_all_tags( $v['fuel_type'] );
		}
		if ( ! empty( $v['transmission'] ) ) {
			$schema['vehicleTransmission'] = wp_strip_all_tags( $v['transmission'] );
		}

		$condition = ( 'used-listings' === $post_type ) ? 'https://schema.org/UsedCondition' : 'https://schema.org/NewCondition';
		$offer     = array(
			'@type'         => 'Offer',
			'priceCurrency' => 'USD',
			'availability'  => 'https://schema.org/InStock',
			'itemCondition' => $condition,
			'url'           => esc_url( $permalink ),
		);
		if ( ! empty( $v['price'] ) ) {
			$offer['price'] = (string) $v['price'];
		}
		$dealer_name = $v['payload']['dealer name'] ?? ( $v['dealer_name'] ?? '' );
		if ( $dealer_name ) {
			$offer['seller'] = array( '@type' => 'AutoDealer', 'name' => wp_strip_all_tags( $dealer_name ) );
		}
		$schema['offers'] = $offer;

		// Highlighted Features → additionalProperty.
		$flat_for_schema = array();
		foreach ( $v['features'] ?? array() as $group ) {
			foreach ( $group['features'] ?? array() as $row ) {
				$flat_for_schema[] = $row;
			}
		}

		if ( ! empty( $flat_for_schema ) ) {
			usort( $flat_for_schema, fn( $a, $b ) => (int) ( $b['ranking'] ?? 0 ) - (int) ( $a['ranking'] ?? 0 ) );

			$overrides = array();
			while ( have_rows( 'feature_list_chromedata', 'options' ) ) {
				the_row();
				$overrides[ (string) get_sub_field( 'id' ) ] = (string) get_sub_field( 'text' );
			}

			$limit      = (int) get_field( 'limit_feature_list', 'options' );
			$additional = array();

			foreach ( $flat_for_schema as $i => $feat ) {
				if ( $limit > 0 && $i >= $limit ) {
					break;
				}
				$id   = (string) ( $feat['id'] ?? '' );
				$text = ! empty( $overrides[ $id ] ) ? $overrides[ $id ] : ( $feat['feature'] ?? '' );
				if ( $text ) {
					$additional[] = array(
						'@type' => 'PropertyValue',
						'name'  => 'Feature',
						'value' => wp_strip_all_tags( $text ),
					);
				}
			}

			if ( ! empty( $additional ) ) {
				$schema['additionalProperty'] = $additional;
			}
		}

		$json = wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
		echo '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>' . "\n"; // phpcs:ignore
	},
	5
);
// ─────────────────────────────────────────────────────────────────────────────

get_header();
?>

<div class="detail-section">
	<div class="container">
		<?php get_template_part( 'template-parts/banner', 'single', array( 'post_type' => $post_type ) ); ?>
		<div class="row">
			<!-- ── Left column: gallery + details ─────────────────────────────── -->
			<div class="col-sm-6">
				<div class="sticky-box">
					<?php
					get_template_part(
						'template-parts/api/breadcrumbs',
						null,
						array(
							'vehicle'   => $v,
							'post_type' => $post_type,
						)
					);
					?>
					<div class="detail-top-row">
						<ol class="breadcrumbs">
							<li><a href="<?php echo esc_url( add_query_arg( 'year', $year, $archive_link ) ); ?>"><?php echo esc_html( $year ); ?></a></li>
							<li><a href="<?php echo esc_url( add_query_arg( 'condition', $condition, $archive_link ) ); ?>"><?php echo esc_html( $condition ); ?></a></li>
							<li><a href="<?php echo esc_url( add_query_arg( 'make', $make, $archive_link ) ); ?>"><?php echo esc_html( mb_strimwidth( $make, 0, 10, '...' ) ); ?></a></li>
							<li><a href="<?php echo esc_url( add_query_arg( 'model', $model, $archive_link ) ); ?>"><?php echo esc_html( mb_strimwidth( $model, 0, 15, '...' ) ); ?></a></li>
						</ol>
						<?php if ( have_rows( 'text_list', 'options' ) ) : ?>
							<ul class="code-list text-right list-unstyled text-capitalize">
								<?php
								while ( have_rows( 'text_list', 'options' ) ) :
									the_row();
									$text   = array();
									$text[] = get_sub_field( 'text' );
									while ( have_rows( 'fields' ) ) :
										the_row();
										$field_slug = get_sub_field( 'field_slug' );
										if ( $field_slug ) {
											$text[] = $v[ $field_slug ] ?? '';
										}
									endwhile;

									$text = array_filter( $text );
									if ( $text ) :
										?>
										<li><?php echo esc_html( implode( ' ', $text ) ); ?></li>
										<?php
									endif;
								endwhile;
								?>
							</ul>
						<?php endif; ?>
					</div> <!-- Status badge -->
					<div class="badges-wrapp">
						<?php if ( $status ) : ?>
							<div class="badges-list">
								<span class="card-badge-status"><?php echo $status; ?></span>
							</div>
						<?php endif; ?>
						<?php if ( wps_check_current_usser() ) : ?>
							<button class="btn btn-primary btn-edit" type="button" data-toggle="modal" data-target="#editModal">
								<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#ffffff">
									<path
										d="M200-200h57l391-391-57-57-391 391v57Zm-40 80q-17 0-28.5-11.5T120-160v-97q0-16 6-30.5t17-25.5l505-504q12-11 26.5-17t30.5-6q16 0 31 6t26 18l55 56q12 11 17.5 26t5.5 30q0 16-5.5 30.5T817-647L313-143q-11 11-25.5 17t-30.5 6h-97Zm600-584-56-56 56 56Zm-141 85-28-29 57 57-29-28Z" />
								</svg>
								<?php esc_html_e( 'Edit', 'shopperexpress' ); ?>

							</button>
						<?php endif; ?>
					</div>

					<?php
					get_template_part(
						'template-parts/api/gallery',
						null,
						array( 'vehicle' => $v )
					);

					// ── Specs table ───────────────────────────────────────────
					$specs = array(
						__( 'Mileage', 'shopperexpress' ) => $v['mileage'] ? number_format( (int) $v['mileage'] ) . ' mi' : '',
						__( 'Condition', 'shopperexpress' ) => $v['condition'] ?? '',
						__( 'Drivetrain', 'shopperexpress' ) => $v['drivetrain'] ?? '',
						__( 'Body Style', 'shopperexpress' ) => $v['body_style'] ?? '',
						__( 'Fuel Type', 'shopperexpress' ) => $v['fuel_type'] ?? '',
						__( 'Transmission', 'shopperexpress' ) => $v['transmission'] ?? '',
						__( 'Ext. Color', 'shopperexpress' ) => $v['exterior_color'] ?? '',
						__( 'Int. Color', 'shopperexpress' ) => $v['interior_color'] ?? '',
						__( 'Stock #', 'shopperexpress' ) => $v['stock'] ?? '',
						__( 'VIN', 'shopperexpress' )     => strtoupper( $v['vin'] ?? '' ),
					);
					$specs = array_filter( $specs );
					if ( ! empty( $specs ) ) :
						?>
						<dl class="detail-info">
							<?php foreach ( $specs as $label => $value ) : ?>
								<dt><?php echo esc_html( $label ); ?></dt>
								<dd><?php echo esc_html( $value ); ?></dd>
							<?php endforeach; ?>
						</dl>
						<?php
					endif;
					$badges_html = '';

						$certified_custom_url = get_field( 'certified_custom_url' );
					if ( have_rows( 'certified_badge', 'options' ) ) :
						while ( have_rows( 'certified_badge', 'options' ) ) :
							the_row();
							$image          = get_sub_field( 'image' );
							$show_badges_on = get_sub_field( 'show_badges_on' );
							$show_badges_on = is_array( $show_badges_on ) ? $show_badges_on : array( $show_badges_on );

							if ( $certified_custom_url && get_sub_field( 'show' ) && $image && in_array( $post_type, $show_badges_on ) ) {
								$badges_html .= '<li><a href="' . esc_url( $certified_custom_url ) . '" target="_blank">' . get_attachment_image( $image ) . '</a></li>';
							}
							endwhile;
						endif;

					if ( have_rows( 'additional_custom_badges', 'options' ) ) :
						while ( have_rows( 'additional_custom_badges', 'options' ) ) :
							the_row();
							$show_badges_on = get_sub_field( 'show_badges_on' );
							$show_badges_on = is_array( $show_badges_on ) ? $show_badges_on : array( $show_badges_on );
							$action         = get_sub_field( 'action' );
							if ( get_sub_field( 'show' ) && in_array( $post_type, $show_badges_on ) ) {
								$image = get_sub_field( 'image' );
								$url   = $action == 'api' ? add_query_arg(
									array(
										'action'      => 'get_pdf',
										'vin_number'  => $vin_number,
										'dealer_name' => $dealer_name,
									),
									admin_url( 'admin-ajax.php' )
								) : str_replace( '{VIN}', $vin_number, get_sub_field( 'url' ) );
								if ( $url && $image ) :
									if ( $action == 'api' ) :
										if ( ! get_transient( 'vdr_error_' . $vin_number ) ) :
											$ga_label   = esc_attr( get_sub_field( 'ga_event_label' ) );
											$error_msg  = esc_attr( get_field( 'vdr_error_message', 'options' ) );
											$data_attrs = ' data-pdf';
											if ( $ga_label ) {
												$data_attrs .= ' data-ga-label="' . $ga_label . '"';
											}
											if ( $error_msg ) {
												$data_attrs .= ' data-error-message="' . $error_msg . '"';
											}
											$badges_html .= '<li><a href="' . esc_url( $url ) . '"' . $data_attrs . '>' . get_attachment_image( $image ) . '</a></li>';
										endif;
									else :
										$badges_html .= '<li><a href="' . esc_url( $url ) . '" target="_blank">' . get_attachment_image( $image ) . '</a></li>';
									endif;
								endif;
							}
							endwhile;
						endif;

					if ( ! empty( $badges_html ) ) :
						?>
							<div class="details-html">
								<ul class="details-badges">
								<?php echo $badges_html; ?>
								</ul>
							</div>
						<?php endif; ?>
					<!-- See Details / Features modals trigger -->
					<ul class="details-list list-inline">
						<li class="list-inline-item">
							<a href="#" data-toggle="modal" data-target="#overviewModal">+<?php esc_html_e( 'See Details', 'shopperexpress' ); ?></a>
						</li>
						<li class="list-inline-item">
							<a href="#" data-toggle="modal" data-target="#featuresAndOptionsModal">+<?php esc_html_e( 'Features & Options', 'shopperexpress' ); ?></a>
						</li>
					</ul>
				</div>
			</div>

			<!-- ── Right column: pricing + tools ──────────────────────────────── -->
			<div class="col-sm-6">

				<div class="anchors-holder">
					<?php
					get_template_part(
						'template-parts/components/anchor',
						'list',
						array(
							'post_type'  => $post_type,
							'location'   => $payload['location'] ?? '',
							'vin_number' => $vin_number,
						)
					);
					get_template_part(
						'template-parts/components/anchor',
						'copy',
						array(
							'favorite'  => true,
							'vin'       => $vin_number,
							'post_type' => $post_type,
						)
					);
					?>
				</div>

				<h2><?php echo esc_html( $page_title ); ?></h2>

				<!-- Disclosure -->
				<div class="detail-info-row">
					<?php if ( get_field( 'comment_footer', 'options' ) && ! get_field( 'hide_disclosure_vdp', 'option' ) ) : ?>
						<button class="btn-disclosure" data-toggle="modal" data-target="#detailModal">
							<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
								<path d="M480-280q17 0 28.5-11.5T520-320v-160q0-17-11.5-28.5T480-520q-17 0-28.5 11.5T440-480v160q0 17 11.5 28.5T480-280Zm0-320q17 0 28.5-11.5T520-640q0-17-11.5-28.5T480-680q-17 0-28.5 11.5T440-640q0 17 11.5 28.5T480-600Zm0 520q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z" />
							</svg>
							<?php esc_html_e( 'Disclosure', 'shopperexpress' ); ?>
						</button>
					<?php endif; ?>
				</div>

				<!-- Pricing block -->
				<div class="info-block info-block--top">
					<ul class="payment-info">
						<?php
						get_template_part(
							'template-parts/api/payment_list',
							null,
							array(
								'vehicle'   => $v,
								'post_type' => $post_type,
								'style'     => 'single',
							)
						);
						get_template_part(
							'template-parts/api/payment_list_new',
							null,
							array(
								'vehicle'   => $v,
								'post_type' => $post_type,
								'style'     => '',
								'is_single' => true,
							)
						);
						?>
					</ul>

					<?php
					get_template_part(
						'template-parts/api/description',
						null,
						array(
							'vehicle'   => $v,
							'post_type' => $post_type,
							'type'      => 'vdp',
						)
					);
					get_template_part(
						'template-parts/api/unlock',
						null,
						array(
							'post_type' => $post_type,
							'permalink' => $archive_link,
							'is_single' => true,
							'post_id'   => $vin_number,
						)
					);
					?>
				</div>

				<!-- Shopping Tools -->
				<div class="info-block">
					<div class="heading">
						<h3><?php esc_html_e( 'Shopping Tools', 'shopperexpress' ); ?></h3>
					</div>
					<?php
					get_template_part(
						'template-parts/api/conversion_block',
						null,
						array(
							'vehicle'   => $v,
							'post_type' => $post_type,
						)
					);
					?>
				</div>
				<?php
				// API payment values for flexible_content payment blocks.
				$fc_lease_payment  = ! empty( $payload['lease_payment'] ) && $payload['lease_payment'] !== 'None' ? $payload['lease_payment'] : null;
				$fc_loan_payment   = ! empty( $payload['loan_payment'] ) && $payload['loan_payment'] !== 'None' ? $payload['loan_payment'] : null;
				$fc_down_payment   = ! empty( $payload['down_payment'] ) ? $payload['down_payment'] : null;
				$location          = $payload['location'] ?? '';
				$is_used_condition = in_array( strtolower( $condition ), array( 'used', 'certified' ), true );

				while ( have_rows( 'flexible_content', 'options' ) ) :
					the_row();
					?>
					<div class="info-block" id="block-<?php echo get_row_index(); ?>">
						<div class="heading">
							<?php if ( $image = get_sub_field( 'image' ) ) : ?>
								<span class="icon"><?php echo wp_get_attachment_image( $image['id'], 'full' ); ?></span>
							<?php endif; ?>
							<?php if ( $block_title = get_sub_field( 'title' ) ) : ?>
								<h3><?php echo $block_title; ?></h3>
							<?php endif; ?>
						</div>
						<?php
						the_sub_field( 'description' );
						if ( get_row_layout() === 'payment' && have_rows( 'payment_list' ) ) :
							?>
							<ul class="payment-info">
								<?php
								while ( have_rows( 'payment_list' ) ) :
									the_row();
									$show_payment = get_sub_field( 'show_payment' );
									$lock         = get_sub_field( 'lock' );
									$event        = get_event_script( get_sub_field( 'event' ), $location, $vin_number );
									$row_title    = get_sub_field( 'title' );

									switch ( $show_payment ) {
										case 'lease-payment':
											if ( ! $is_used_condition && $fc_lease_payment ) {
												$lease_fmt = '$' . number_format( floatval( $fc_lease_payment ) );
												$text      = $fc_down_payment
													? '<span class="savings">$' . $fc_down_payment . ' ' . __( 'DOWN', 'shopperexpress' ) . '</span>' . $lease_fmt . ' <sub>/mo</sub>'
													: $lease_fmt . ' <sub>/mo</sub>';
											} else {
												$text = null;
											}
											break;

										default:
											if ( $fc_loan_payment ) {
												$loan_fmt = '$' . number_format( floatval( $fc_loan_payment ) ) . ' <sub>/mo</sub>';
												$text     = $fc_down_payment
													? '<span class="savings">$' . $fc_down_payment . ' ' . __( 'DOWN', 'shopperexpress' ) . '</span>' . $loan_fmt
													: $loan_fmt;
											} else {
												$text = null;
											}
											break;
									}

									// Skip "Lease" row for used/certified vehicles.
									if ( $is_used_condition && $row_title === 'Lease' ) {
										continue;
									}

									if ( $lock && ! empty( $text ) ) :
										?>
										<li>
											<?php if ( wps_auth() ) : ?>
												<a href="#" onclick="javascript:inticeAllEvents.<?php echo $event; ?>">
												<?php endif; ?>
												<div class="text-holder">
													<?php if ( $row_title ) : ?>
														<h4 class="h3"><?php echo esc_html( $row_title ); ?></h4>
													<?php endif; ?>
													<?php the_sub_field( 'description' ); ?>
												</div>
												<?php if ( ! wps_auth() ) : ?>
													<span class="unlock-item" data-toggle="modal" data-target="#unlockSavingsModal">
														<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
															<path d="M840-640q32 0 56 24t24 56v80q0 7-1.5 15t-4.5 15L794-168q-9 20-30 34t-44 14H400q-33 0-56.5-23.5T320-200v-407q0-16 6.5-30.5T344-663l217-216q15-14 35.5-17t39.5 7q19 10 27.5 28t3.5 37l-45 184h218ZM160-120q-33 0-56.5-23.5T80-200v-360q0-33 23.5-56.5T160-640q33 0 56.5 23.5T240-560v360q0 33-23.5 56.5T160-120Z" />
														</svg>
														<?php esc_html_e( 'UNLOCK PAYMENT', 'shopperexpress' ); ?>
													</span>
												<?php elseif ( wps_auth() ) : ?>
													<strong class="price"><?php echo $text; ?></strong>
												<?php endif; ?>
												<?php if ( wps_auth() ) : ?>
												</a>
											<?php endif; ?>
										</li>
									<?php elseif ( ! $lock ) : ?>
										<li>
											<div class="text-holder">
												<?php if ( $row_title ) : ?>
													<h4 class="h3"><?php echo esc_html( $row_title ); ?></h4>
												<?php endif; ?>
												<?php the_sub_field( 'description' ); ?>
											</div>
											<a href="#" onclick="javascript:inticeAllEvents.<?php echo $event; ?>" class="btn btn-primary"><?php the_sub_field( 'text' ); ?></a>
										</li>
									<?php endif; ?>
								<?php endwhile; ?>
							</ul>
						<?php elseif ( get_row_layout() === 'video' && have_rows( 'video_list' ) ) : ?>
							<ul class="payment-video">
								<?php
								while ( have_rows( 'video_list' ) ) :
									the_row();
									?>
									<li>
										<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000">
											<path d="m424-424-86-86q-11-11-28-11t-28 11q-11 11-11 28t11 28l114 114q12 12 28 12t28-12l226-226q11-11 11-28t-11-28q-11-11-28-11t-28 11L424-424ZM200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Z" />
										</svg>
										<?php if ( $vid_title = get_sub_field( 'title' ) ) : ?>
											<strong class="title"><?php echo esc_html( $vid_title ); ?></strong>
										<?php endif; ?>
										<?php the_sub_field( 'description' ); ?>
										<?php if ( $video_id = get_sub_field( 'video_id' ) ) : ?>
											<p><span class="wistia_embed wistia_async_<?php echo $video_id; ?> popover=true popoverContent=link" style="display:inline;position:relative;"><a class="btn-get-started" href="#video-<?php echo get_row_index(); ?>"><?php esc_html_e( 'Watch Video', 'shopperexpress' ); ?></a></span></p>
											<div style="display:none;" id="video-<?php echo get_row_index(); ?>">
												<script src="https://fast.wistia.com/embed/medias/<?php echo $video_id; ?>.jsonp" async></script>
												<div class="wistia_responsive_padding" style="padding:56.25% 0 0 0;position:relative;">
													<div class="wistia_responsive_wrapper" style="height:100%;left:0;position:absolute;top:0;width:100%;"><span class="wistia_embed wistia_async_<?php echo $video_id; ?> popover=true popoverAnimateThumbnail=true videoFoam=true" style="display:inline-block;height:100%;position:relative;width:100%">&nbsp;</span></div>
												</div>
											</div>
										<?php endif; ?>
									</li>
								<?php endwhile; ?>
							</ul>
						<?php endif; ?>
					</div>
				<?php endwhile; ?>
				<!-- Sticky summary -->
				<div class="info-block sticky-summary">
					<div class="summary-block text-center">
						<div class="summary-holder">
							<h3><?php echo esc_html( $page_title ); ?></h3>
							<ul class="summary-list">
								<?php
								get_template_part(
									'template-parts/api/payment_list',
									null,
									array(
										'vehicle'       => $v,
										'post_type'     => $post_type,
										'style'         => 'single',
										'single-bottom' => true,
									)
								);
								?>
							</ul>
							<?php
							get_template_part(
								'template-parts/api/unlock',
								null,
								array(
									'post_type'  => $post_type,
									'permalink'  => $archive_link,
									'is_single'  => true,
									'show-image' => 'false',
									'post_id'    => $vin_number,
								)
							);
							?>
						</div>
					</div>
				</div>

			</div><!-- .col-sm-6 -->
		</div><!-- .row -->
	</div><!-- .container -->
</div><!-- .detail-section -->

<?php
get_template_part(
	'template-parts/api/vdp_description',
	null,
	array( 'vehicle' => $v )
);

if ( ! empty( $features ) ) :
	// Flatten all features across groups and sort by ranking desc for Highlighted Features.
	$flat_features = array();
	foreach ( $features as $group ) {
		foreach ( $group['features'] ?? array() as $row ) {
			$flat_features[] = $row;
		}
	}
	usort( $flat_features, fn( $a, $b ) => (int) ( $b['ranking'] ?? 0 ) - (int) ( $a['ranking'] ?? 0 ) );

	// Chrome icon/text overrides keyed by chrome_category_id.
	$feature_images = array();
	while ( have_rows( 'feature_list_chromedata', 'options' ) ) :
		the_row();
		$feature_images[ get_sub_field( 'id' ) ] = array(
			'icon' => get_sub_field( 'icon' ),
			'text' => get_sub_field( 'text' ),
		);
	endwhile;
	?>

	<?php if ( get_field( 'show_feature_list', 'options' ) ) : ?>
		<section class="section-key-features">
			<div class="container">
				<div class="heading">
					<h3><?php esc_html_e( 'Highlighted Features', 'shopperexpress' ); ?></h3>
				</div>
				<ul class="key-features-list list-unstyled">
					<?php
					$limit = (int) get_field( 'limit_feature_list', 'options' );
					foreach ( $flat_features as $index => $feature ) :
						if ( $index >= $limit ) {
							break;
						}
						$id   = $feature['id'] ?? '';
						$icon = $feature_images[ $id ]['icon'] ?? null;
						$text = $feature_images[ $id ]['text'] ?? $feature['feature'];
						?>
						<li data-id="<?php echo esc_attr( $id ); ?>">
							<?php
							if ( $icon ) {
								echo '<span class="icon">' . get_attachment_image( $icon ) . '</span>';
							}
							echo wpautop( esc_html( $text ) );
							?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<div class="accordion-detail-info">
		<div class="container">
			<ul class="accordion-detail" id="accordionDetail">
				<?php
				$i = 1;
				foreach ( $features as $group ) :
					$heading   = $group['heading'] ?? '';
					$aria      = $i === 1 ? 'true' : 'false';
					$collapsed = $i === 1 ? '' : 'collapsed';
					?>
					<li>
						<div id="heading-<?php echo $i; ?>">
							<h3>
								<button class="accordion-detail-opener <?php echo esc_attr( $collapsed ); ?>" type="button" data-toggle="collapse" data-target="#collapse-<?php echo esc_attr( $i ); ?>" aria-expanded="<?php echo esc_attr( $aria ); ?>" aria-controls="collapse-<?php echo esc_attr( $i ); ?>">
									<?php echo esc_html( $heading ); ?>
									<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="40px" viewBox="0 -960 960 960" width="40px" fill="#000000">
										<path d="m587-481.33-308-308q-12.33-12.34-12.17-30.17.17-17.83 12.5-30.83 13-13 30.84-13 17.83 0 30.83 13l321.67 322q10 10 14.66 22.33 4.67 12.33 4.67 24.67 0 12.33-4.67 24.66-4.66 12.34-14.66 22.34L340-111.67q-13 13-30.33 12.5-17.34-.5-30.34-13.5-12.33-13-12.66-30.5-.34-17.5 12.66-30.5L587-481.33Z" />
									</svg>
								</button>
							</h3>
						</div>
						<div id="collapse-<?php echo $i; ?>" class="collapse<?php echo $i === 1 ? ' show' : ''; ?>" aria-labelledby="heading-<?php echo $i; ?>" data-parent="#accordionDetail">
							<div class="card-body">
								<ul class="options-list">
									<?php foreach ( $group['features'] ?? array() as $row ) : ?>
										<li><?php echo esc_html( $row['feature'] ); ?></li>
									<?php endforeach; ?>
								</ul>
							</div>
						</div>
					</li>
					<?php
					++$i;
				endforeach;
				?>
			</ul>
		</div>
	</div>

	<?php
endif;

get_template_part(
	'template-parts/accordion',
	null,
	array(
		'post_type' => $post_type,
		'type'      => 'random',
	)
);

if ( $comment_footer ) :
	?>
	<div class="description-box">
		<div class="container-fluid">
			<?php echo $comment_footer; // phpcs:ignore 
			?>
		</div>
	</div>
<?php endif; ?>

<?php
get_template_part(
	'template-parts/model',
	'slider',
	array(
		'title'      => get_field( 'title_slider', 'options' ),
		'section_bg' => get_field( 'section_bg', 'options' ),
		'slide_bg'   => get_field( 'slide_bg', 'options' ),
	)
);

if ( ! empty( $v ) ) :
	$GLOBALS['intice_vehicle'] = $v;
endif;

get_footer();

// ── Modals ────────────────────────────────────────────────────────────────────
get_template_part(
	'template-parts/copyLinkModal',
	null,
	array(
		'post_id'      => 0,
		'image'        => $images[0] ?? '',
		'title'        => $page_title,
		'vin'          => $vin_number,
		'stock_number' => $stock,
	)
);
get_template_part( 'template-parts/detail', 'modal' );
get_template_part(
	'template-parts/modal-edit-api',
	null,
	array(
		'vehicle'   => $v,
		'post_type' => $post_type,
	)
);
