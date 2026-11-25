<?php
/**
 * /llm/inventory/ — AI-readable inventory page.
 *
 * Included directly by Llm_Inventory::maybe_serve() (not a normal WP template —
 * no get_header()/get_footer(), no enqueued scripts). $data is the array
 * returned by \App\get_llm_inventory_page().
 *
 * @package Shopperexpress
 * @var array $data
 */

defined( 'ABSPATH' ) || exit;

$site_name    = wp_strip_all_tags( get_bloginfo( 'name' ) );
$site_host    = (string) wp_parse_url( home_url(), PHP_URL_HOST );
$current_page = (int) $data['page'];
$total_pg     = max( 1, (int) $data['total_pages'] );
$total        = (int) $data['total'];
$page_size    = (int) $data['per_page'];
$first        = $total ? ( $current_page - 1 ) * $page_size + 1 : 0;
$last         = min( $total, $current_page * $page_size );

$page_url = static function ( int $n ): string {
	return 1 === $n ? home_url( '/llm/inventory/' ) : home_url( '/llm/inventory/page/' . $n . '/' );
};

$not_found     = ! empty( $data['not_found'] );
$updated_label = ! empty( $data['last_updated'] ) ? (string) $data['last_updated'] : '';
$updated_attr  = ! empty( $data['last_updated_ts'] ) ? wp_date( 'c', (int) $data['last_updated_ts'] ) : '';

$condition_labels = array(
	'new'       => 'New',
	'used'      => 'Used',
	'certified' => 'Certified',
);

$spec_labels = array(
	'mileage'        => 'Mileage',
	'body_style'     => 'Body style',
	'drivetrain'     => 'Drivetrain',
	'transmission'   => 'Transmission',
	'fuel_type'      => 'Fuel type',
	'exterior_color' => 'Exterior color',
	'interior_color' => 'Interior color',
);

// Numbered pager: first, last, and two either side of the current page.
$pager_items = array();
for ( $n = 1; $n <= $total_pg; $n++ ) {
	if ( 1 === $n || $total_pg === $n || abs( $n - $current_page ) <= 2 ) {
		$pager_items[] = $n;
	} elseif ( end( $pager_items ) !== '…' ) {
		$pager_items[] = '…';
	}
}

$sitemap_url = defined( 'WPSEO_VERSION' ) ? home_url( '/sitemap_index.xml' ) : home_url( '/wp-sitemap.xml' );
$time_tag    = $updated_attr
	? '<time datetime="' . esc_attr( $updated_attr ) . '">' . esc_html( $updated_label ) . '</time>'
	: esc_html( $updated_label );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="<?php echo $not_found ? 'noindex,follow' : 'index,follow'; ?>">
	<?php if ( $not_found ) : ?>
		<title>Page not found — <?php echo esc_html( $site_name ); ?> vehicle inventory</title>
	<?php else : ?>
		<title><?php echo esc_html( $site_name ); ?> — Vehicle inventory<?php echo $current_page > 1 ? ' (page ' . esc_html( $current_page ) . ' of ' . esc_html( $total_pg ) . ')' : ''; ?></title>
		<meta name="description" content="<?php echo esc_attr( sprintf( 'Current new, used and certified vehicle inventory at %s, with prices, availability and links to each vehicle’s details page.', $site_name ) ); ?>">
		<link rel="canonical" href="<?php echo esc_url( $page_url( $current_page ) ); ?>">
		<?php if ( $current_page > 1 ) : ?>
			<link rel="prev" href="<?php echo esc_url( $page_url( $current_page - 1 ) ); ?>">
		<?php endif; ?>
		<?php if ( $current_page < $total_pg ) : ?>
			<link rel="next" href="<?php echo esc_url( $page_url( $current_page + 1 ) ); ?>">
		<?php endif; ?>
		<script type="application/ld+json">
		<?php
		echo wp_json_encode(
			array(
				'@context'   => 'https://schema.org',
				'@type'      => 'CollectionPage',
				'name'       => $site_name . ' Vehicle Inventory',
				'url'        => $page_url( $current_page ),
				'mainEntity' => array(
					'@type'           => 'ItemList',
					'numberOfItems'   => $total,
					'itemListElement' => array_values(
						array_filter(
							array_map(
								static function ( $vehicle, $i ) use ( $current_page, $page_size ) {
									if ( empty( $vehicle['vdp_url'] ) ) {
										return null;
									}
									return array(
										'@type'    => 'ListItem',
										'position' => ( $current_page - 1 ) * $page_size + $i + 1,
										'url'      => $vehicle['vdp_url'],
									);
								},
								$data['vehicles'],
								array_keys( $data['vehicles'] )
							)
						)
					),
				),
			)
		);
		?>
		</script>
	<?php endif; ?>
	<style>
		*,*::before,*::after{box-sizing:border-box}
		body{margin:0;background:#f6f7f8;color:#1f2328;font:15px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif}
		a{color:#0b57d0}a:hover{color:#0842a0}
		a:focus-visible{outline:2px solid #0b57d0;outline-offset:2px;border-radius:3px}
		.wrap{max-width:1040px;margin:0 auto;padding:32px 20px 48px;display:flex;flex-direction:column;gap:20px}
		.muted{color:#59636e}
		.back{font-size:13px;font-weight:600;text-decoration:none;color:#59636e}
		h1{margin:6px 0;font-size:28px;line-height:1.2;letter-spacing:-.01em}
		.meta{display:flex;flex-wrap:wrap;gap:6px 16px;margin:0;padding:0;list-style:none;font-size:13px;color:#59636e}
		.notice{display:flex;gap:12px;align-items:flex-start;padding:14px 16px;border:1px solid #e8c15a;border-radius:8px;background:#fff8e1;color:#5c4400;font-size:14px}
		.notice svg{flex-shrink:0;margin-top:1px}
		.empty{padding:16px;border:1px solid #d1d9e0;border-radius:10px;background:#fff}
		.pager{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;font-size:14px}
		.pager ol{display:flex;flex-wrap:wrap;gap:6px;margin:0;padding:0;list-style:none}
		.pager a,.pager span.off{display:inline-block;padding:6px 12px;border:1px solid #d1d9e0;border-radius:6px;background:#fff;text-decoration:none}
		.pager span.off{color:#8c959f}
		.pager a[aria-current="page"]{background:#1f2328;border-color:#1f2328;color:#fff;font-weight:600}
		.vehicle{display:flex;flex-wrap:wrap;gap:20px;padding:16px;border:1px solid #d1d9e0;border-radius:10px;background:#fff}
		.vehicle img{flex:0 0 300px;max-width:100%;height:250px;object-fit:contain;border-radius:6px;background:#e6e9ed}
		.body{flex:999 1 320px;min-width:0;display:flex;flex-direction:column;gap:12px}
		.top{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:flex-start;gap:8px 24px}
		h2{margin:0;font-size:19px;line-height:1.3}
		h2 a{text-decoration:none}h2 a:hover{text-decoration:underline}
		.ids{margin-top:4px;display:flex;flex-wrap:wrap;gap:4px 12px;font-size:13px;color:#59636e}
		.ids .cond{font-weight:600;color:#1f2328}
		.vin{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}
		.price{text-align:right}
		.price .amt{font-size:22px;font-weight:700;line-height:1.2}
		.price .lbl{font-size:12px;color:#59636e}
		.price .na{font-size:14px;font-weight:600;color:#59636e}
		.badge{display:inline-block;margin-top:4px;padding:1px 8px;border-radius:999px;font-size:12px;font-weight:600;background:#eaeef2;color:#59636e}
		.in-stock{background:#e6f4ea;color:#146c2e}
		.in-transit{background:#fff4e5;color:#8a4b00}
		dl{margin:0;display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:10px 20px;padding-top:12px;border-top:1px solid #eaeef2}
		dt{font-size:12px;color:#59636e}
		dd{margin:0;font-size:14px}
		.details{align-self:flex-start;font-size:14px;font-weight:600}
		footer{padding-top:16px;border-top:1px solid #d1d9e0;font-size:13px;color:#59636e}
		@media (max-width:560px){
			.wrap{padding:20px 14px 36px;gap:16px}
			h1{font-size:23px}
			.vehicle{padding:12px;gap:14px}
			.vehicle img{flex-basis:100%;height:auto;aspect-ratio:4/3}
			.price{text-align:left}
			dl{grid-template-columns:repeat(2,minmax(0,1fr))}
			.pager a,.pager span.off{padding:8px 12px}
		}
	</style>
</head>
<body>
<main class="wrap">

	<header>
		<a class="back" href="<?php echo esc_url( home_url( '/' ) ); ?>">Back to <?php echo esc_html( $site_host ); ?></a>
		<h1><?php echo esc_html( $site_name ); ?> — Vehicle inventory</h1>
		<ul class="meta">
			<?php if ( $updated_label ) : ?>
				<li>Last updated: <?php echo $time_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped when built. ?></li>
			<?php endif; ?>
			<li><?php echo esc_html( number_format_i18n( $total ) ); ?> <?php echo 1 === $total ? 'vehicle' : 'vehicles'; ?></li>
			<?php if ( ! $not_found ) : ?>
				<li>Page <?php echo esc_html( $current_page ); ?> of <?php echo esc_html( $total_pg ); ?></li>
			<?php endif; ?>
			<li>New, used and certified</li>
		</ul>
	</header>

	<?php if ( ! $not_found && ! empty( $data['stale'] ) ) : ?>
		<div class="notice" role="status">
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
			<div>
				<strong>Inventory may be out of date.</strong>
				<?php if ( $updated_label ) : ?>
					The feed could not be refreshed since <?php echo $time_tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped when built. ?>.
				<?php else : ?>
					The feed could not be refreshed recently.
				<?php endif; ?>
				Prices and availability may differ from the website — check each vehicle’s details page.
			</div>
		</div>
	<?php endif; ?>

	<?php if ( ! $not_found && $total_pg > 1 ) : ?>
		<nav class="pager" aria-label="Pagination">
			<span class="muted">Showing <?php echo esc_html( number_format_i18n( $first ) . '–' . number_format_i18n( $last ) ); ?> of <?php echo esc_html( number_format_i18n( $total ) ); ?></span>
			<ol>
				<li>
					<?php if ( $current_page > 1 ) : ?>
						<a href="<?php echo esc_url( $page_url( $current_page - 1 ) ); ?>" rel="prev">Previous</a>
					<?php else : ?>
						<span class="off">Previous</span>
					<?php endif; ?>
				</li>
				<?php foreach ( $pager_items as $item ) : ?>
					<li>
						<?php if ( '…' === $item ) : ?>
							<span class="off" aria-hidden="true">…</span>
						<?php else : ?>
							<a href="<?php echo esc_url( $page_url( $item ) ); ?>"<?php echo $item === $current_page ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $item ); ?></a>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
				<li>
					<?php if ( $current_page < $total_pg ) : ?>
						<a href="<?php echo esc_url( $page_url( $current_page + 1 ) ); ?>" rel="next">Next</a>
					<?php else : ?>
						<span class="off">Next</span>
					<?php endif; ?>
				</li>
			</ol>
		</nav>
	<?php endif; ?>

	<?php if ( $not_found ) : ?>
		<p class="empty">
			This inventory page doesn’t exist — the inventory now has <?php echo esc_html( $total_pg ); ?> <?php echo 1 === $total_pg ? 'page' : 'pages'; ?>.
			<a href="<?php echo esc_url( $page_url( 1 ) ); ?>">Go to the first page</a>.
		</p>
	<?php elseif ( empty( $data['vehicles'] ) ) : ?>
		<p class="empty">No vehicles are currently available.</p>
	<?php else : ?>
		<?php foreach ( $data['vehicles'] as $vehicle ) : ?>
			<?php
			$vehicle_title = trim( implode( ' ', array_filter( array( $vehicle['year'] ?? '', $vehicle['make'] ?? '', $vehicle['model'] ?? '', $vehicle['trim'] ?? '' ) ) ) );
			$alt           = $vehicle_title . ( ! empty( $vehicle['exterior_color'] ) ? ', ' . $vehicle['exterior_color'] : '' );
			$vdp           = $vehicle['vdp_url'] ?? '';
			$stock         = isset( $vehicle['stock'] ) && '' !== $vehicle['stock'] ? (string) $vehicle['stock'] : '';
			$cond          = $condition_labels[ $vehicle['condition'] ?? '' ] ?? ( ! empty( $vehicle['condition'] ) ? ucfirst( (string) $vehicle['condition'] ) : '' );
			$price         = isset( $vehicle['price'] ) && '' !== $vehicle['price'] ? $vehicle['price'] : null;

			$availability = isset( $vehicle['availability'] ) ? (string) $vehicle['availability'] : '';
			$avail_slug   = sanitize_title( $availability );
			if ( in_array( $avail_slug, array( 'available', 'in-stock', 'instock' ), true ) ) {
				$avail_label = 'In stock';
				$avail_class = 'in-stock';
			} elseif ( false !== strpos( $avail_slug, 'transit' ) ) {
				$avail_label = 'In transit';
				$avail_class = 'in-transit';
			} else {
				$avail_label = ucfirst( str_replace( array( '_', '-' ), ' ', $availability ) );
				$avail_class = '';
			}
			?>
			<article class="vehicle">
				<?php if ( ! empty( $vehicle['images'][0] ) ) : ?>
					<img src="<?php echo esc_url( $vehicle['images'][0] ); ?>" alt="<?php echo esc_attr( $alt ); ?>" width="300" height="250" loading="lazy">
				<?php endif; ?>
				<div class="body">
					<div class="top">
						<div>
							<h2>
								<?php if ( $vdp ) : ?>
									<a href="<?php echo esc_url( $vdp ); ?>"><?php echo esc_html( $vehicle_title ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $vehicle_title ); ?>
								<?php endif; ?>
							</h2>
							<div class="ids">
								<?php if ( $cond ) : ?>
									<span class="cond"><?php echo esc_html( $cond ); ?></span>
								<?php endif; ?>
								<?php if ( $stock ) : ?>
									<span>Stock # <?php echo esc_html( $stock ); ?></span>
								<?php endif; ?>
								<?php if ( ! empty( $vehicle['vin'] ) ) : ?>
									<span>VIN <span class="vin"><?php echo esc_html( $vehicle['vin'] ); ?></span></span>
								<?php endif; ?>
							</div>
						</div>
						<div class="price">
							<?php if ( null !== $price ) : ?>
								<div class="amt"><?php echo is_numeric( $price ) ? '$' . esc_html( number_format_i18n( (float) $price ) ) : esc_html( (string) $price ); ?></div>
								<div class="lbl">Advertised price</div>
							<?php else : ?>
								<div class="na">Price not listed</div>
								<div class="lbl">See details page</div>
							<?php endif; ?>
							<?php if ( $avail_label ) : ?>
								<span class="badge<?php echo $avail_class ? ' ' . esc_attr( $avail_class ) : ''; ?>"><?php echo esc_html( $avail_label ); ?></span>
							<?php endif; ?>
						</div>
					</div>
					<?php
					$specs = array();
					foreach ( $spec_labels as $key => $label ) {
						if ( ! isset( $vehicle[ $key ] ) || '' === $vehicle[ $key ] ) {
							continue;
						}
						$specs[ $label ] = 'mileage' === $key && is_numeric( $vehicle[ $key ] )
							? number_format_i18n( (float) $vehicle[ $key ] ) . ' mi'
							: (string) $vehicle[ $key ];
					}
					?>
					<?php if ( $specs ) : ?>
						<dl>
							<?php foreach ( $specs as $label => $value ) : ?>
								<div><dt><?php echo esc_html( $label ); ?></dt><dd><?php echo esc_html( $value ); ?></dd></div>
							<?php endforeach; ?>
						</dl>
					<?php endif; ?>
					<?php if ( $vdp ) : ?>
						<a class="details" href="<?php echo esc_url( $vdp ); ?>">View vehicle details<?php echo $stock ? ' (Stock # ' . esc_html( $stock ) . ')' : ''; ?></a>
					<?php endif; ?>
				</div>
			</article>
		<?php endforeach; ?>
	<?php endif; ?>

	<?php if ( ! $not_found && $total_pg > 1 ) : ?>
		<nav class="pager" aria-label="Pagination, bottom">
			<span class="muted">Page <?php echo esc_html( $current_page ); ?> of <?php echo esc_html( $total_pg ); ?></span>
			<ol>
				<?php if ( $current_page > 1 ) : ?>
					<li><a href="<?php echo esc_url( $page_url( $current_page - 1 ) ); ?>" rel="prev">Previous page</a></li>
				<?php endif; ?>
				<?php if ( $current_page < $total_pg ) : ?>
					<li><a href="<?php echo esc_url( $page_url( $current_page + 1 ) ); ?>" rel="next">Next page</a></li>
				<?php endif; ?>
			</ol>
		</nav>
	<?php endif; ?>

	<footer>
		Machine-readable inventory for <?php echo esc_html( $site_name ); ?>.
		<?php if ( \App\is_api_mode() ) : ?>
			Refreshed from the dealer inventory feed after every inventory import.
		<?php else : ?>
			Generated live from the website’s current inventory.
		<?php endif; ?>
		Prices and availability on each vehicle’s details page are authoritative.
		<a href="<?php echo esc_url( $sitemap_url ); ?>">Sitemap</a>, <a href="<?php echo esc_url( home_url( '/llms.txt' ) ); ?>">llms.txt</a>
	</footer>

</main>
</body>
</html>
