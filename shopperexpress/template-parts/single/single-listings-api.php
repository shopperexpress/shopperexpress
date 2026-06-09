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

$v          = $api_res['data'];
$payload    = $v['payload']    ?? array();
$enrichment = $v['enrichment'] ?? array();

$year       = $v['year']  ?? '';
$make       = $v['make']  ?? '';
$model      = $v['model'] ?? '';
$trim       = $v['trim']  ?? '';
$stock      = $v['stock'] ?? '';
$vin_number = $vin;
$sold       = ! empty( $v['sold'] );
$certified  = ! empty( $v['certified'] );
$images     = $v['images'] ?? ( ! empty( $v['image'] ) ? array( $v['image'] ) : array() );
$features   = $v['features'] ?? array();

$page_title   = trim( implode( ' ', array_filter( array( $year, $make, $model, $trim ) ) ) );
$archive_link = home_url( $post_type );

$comment_footer = ( 'used-listings' === $post_type )
	? get_field( 'used_listings_comment_footer', 'options' )
	: get_field( 'comment_footer', 'options' );

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

					<!-- Status badge -->
					<div class="badges-wrapp">
						<?php if ( $sold ) : ?>
							<div class="badges-list"><span class="card-badge-status"><?php esc_html_e( 'Sold', 'shopperexpress' ); ?></span></div>
						<?php elseif ( $certified ) : ?>
							<div class="badges-list"><span class="card-badge-status"><?php esc_html_e( 'Certified', 'shopperexpress' ); ?></span></div>
						<?php elseif ( ! empty( $v['status'] ) ) : ?>
							<div class="badges-list"><span class="card-badge-status"><?php echo esc_html( $v['status'] ); ?></span></div>
						<?php endif; ?>
					</div>

					<?php
					get_template_part(
						'template-parts/api/gallery',
						null,
						array( 'vehicle' => $v )
					);

					get_template_part(
						'template-parts/api/specs',
						null,
						array( 'vehicle' => $v )
					);
					?>

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
					get_template_part( 'template-parts/components/anchor', 'copy', array( 'favorite' => true ) );
					?>
				</div>

				<h2><?php echo esc_html( $page_title ); ?></h2>

				<!-- Disclosure -->
				<div class="detail-info-row">
					<?php if ( get_field( 'comment_footer', 'options' ) && ! get_field( 'hide_disclosure_vdp', 'option' ) ) : ?>
						<button class="btn-disclosure" data-toggle="modal" data-target="#detailModal">
							<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
								<path d="M480-280q17 0 28.5-11.5T520-320v-160q0-17-11.5-28.5T480-520q-17 0-28.5 11.5T440-480v160q0 17 11.5 28.5T480-280Zm0-320q17 0 28.5-11.5T520-640q0-17-11.5-28.5T480-680q-17 0-28.5 11.5T440-640q0 17 11.5 28.5T480-600Zm0 520q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
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

				<!-- Features -->
				<?php if ( ! empty( $features ) ) : ?>
					<div class="info-block">
						<div class="heading">
							<h3><?php esc_html_e( 'Features', 'shopperexpress' ); ?></h3>
						</div>
						<ul class="feature-list">
							<?php foreach ( $features as $feature ) : ?>
								<li><?php echo esc_html( $feature ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

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
			<?php echo $comment_footer; // phpcs:ignore ?>
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

if ( ! empty( $v ) ) :
	$GLOBALS['intice_vehicle'] = $v;
endif;
