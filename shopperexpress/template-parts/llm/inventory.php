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

$site_name = wp_strip_all_tags( get_bloginfo( 'name' ) );
$page      = (int) $data['page'];
$total_pg  = (int) $data['total_pages'];

$field_labels = array(
	'year'           => 'Year',
	'make'           => 'Make',
	'model'          => 'Model',
	'trim'           => 'Trim',
	'condition'      => 'Condition',
	'vin'            => 'VIN',
	'stock'          => 'Stock #',
	'mileage'        => 'Mileage',
	'body_style'     => 'Body Style',
	'drivetrain'     => 'Drivetrain',
	'transmission'   => 'Transmission',
	'fuel_type'      => 'Fuel Type',
	'exterior_color' => 'Exterior Color',
	'interior_color' => 'Interior Color',
	'price'          => 'Price',
	'availability'   => 'Availability',
);

$page_url = static function ( int $n ): string {
	return 1 === $n ? home_url( '/llm/inventory/' ) : home_url( '/llm/inventory/page/' . $n . '/' );
};
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="index,follow">
	<title><?php echo esc_html( $site_name ); ?> — Vehicle Inventory (page <?php echo esc_html( $page ); ?> of <?php echo esc_html( $total_pg ); ?>)</title>
	<link rel="canonical" href="<?php echo esc_url( $page_url( $page ) ); ?>">
	<?php if ( $page > 1 ) : ?>
		<link rel="prev" href="<?php echo esc_url( $page_url( $page - 1 ) ); ?>">
	<?php endif; ?>
	<?php if ( $page < $total_pg ) : ?>
		<link rel="next" href="<?php echo esc_url( $page_url( $page + 1 ) ); ?>">
	<?php endif; ?>
	<script type="application/ld+json">
	<?php
	echo wp_json_encode(
		array(
			'@context'        => 'https://schema.org',
			'@type'           => 'CollectionPage',
			'name'            => $site_name . ' Vehicle Inventory',
			'url'             => $page_url( $page ),
			'mainEntity'      => array(
				'@type'           => 'ItemList',
				'numberOfItems'   => $data['total'],
				'itemListElement' => array_values(
					array_filter(
						array_map(
							static function ( $vehicle, $i ) use ( $page, $data ) {
								if ( empty( $vehicle['vdp_url'] ) ) {
									return null;
								}
								return array(
									'@type'    => 'ListItem',
									'position' => ( $page - 1 ) * $data['per_page'] + $i + 1,
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
	<style>
		body { font-family: -apple-system, Arial, sans-serif; max-width: 1100px; margin: 2rem auto; padding: 0 1rem; color: #1a1a1a; }
		h1 { font-size: 1.4rem; }
		.llm-meta { color: #555; font-size: .9rem; margin-bottom: 1.5rem; }
		.llm-stale { background: #fff3cd; border: 1px solid #ffe69c; padding: .75rem 1rem; margin-bottom: 1.5rem; }
		.llm-vehicle { border: 1px solid #ddd; border-radius: 6px; padding: 1rem; margin-bottom: 1rem; display: flex; gap: 1rem; }
		.llm-vehicle img { max-width: 220px; height: auto; border-radius: 4px; }
		.llm-vehicle dl { display: grid; grid-template-columns: max-content 1fr; gap: .15rem 1rem; margin: 0 0 .75rem; }
		.llm-vehicle dt { font-weight: 600; }
		.llm-pagination { margin-top: 2rem; }
		.llm-pagination a { margin-right: 1rem; }
	</style>
</head>
<body>
	<h1><?php echo esc_html( $site_name ); ?> — Vehicle Inventory</h1>

	<p class="llm-meta">
		<?php if ( ! empty( $data['last_updated'] ) ) : ?>
			Inventory last updated: <?php echo esc_html( $data['last_updated'] ); ?>.
		<?php endif; ?>
		Page <?php echo esc_html( $page ); ?> of <?php echo esc_html( $total_pg ); ?>
		(<?php echo esc_html( number_format_i18n( $data['total'] ) ); ?> total vehicles).
	</p>

	<?php if ( ! empty( $data['stale'] ) ) : ?>
		<p class="llm-stale">
			The inventory feed could not be refreshed recently — prices and availability below
			may not reflect the current website. Please verify against individual vehicle pages.
		</p>
	<?php endif; ?>

	<?php if ( empty( $data['vehicles'] ) ) : ?>
		<p>No vehicles currently available.</p>
	<?php else : ?>
		<?php foreach ( $data['vehicles'] as $vehicle ) : ?>
			<article class="llm-vehicle">
				<?php if ( ! empty( $vehicle['images'][0] ) ) : ?>
					<img src="<?php echo esc_url( $vehicle['images'][0] ); ?>" alt="<?php echo esc_attr( trim( ( $vehicle['year'] ?? '' ) . ' ' . ( $vehicle['make'] ?? '' ) . ' ' . ( $vehicle['model'] ?? '' ) ) ); ?>" loading="lazy">
				<?php endif; ?>
				<div>
					<h2>
						<?php
						echo esc_html(
							trim( ( $vehicle['year'] ?? '' ) . ' ' . ( $vehicle['make'] ?? '' ) . ' ' . ( $vehicle['model'] ?? '' ) . ' ' . ( $vehicle['trim'] ?? '' ) )
						);
						?>
					</h2>
					<dl>
						<?php foreach ( $field_labels as $key => $label ) : ?>
							<?php if ( ! isset( $vehicle[ $key ] ) || '' === $vehicle[ $key ] ) : ?>
								<?php continue; ?>
							<?php endif; ?>
							<dt><?php echo esc_html( $label ); ?></dt>
							<dd>
								<?php
								if ( 'price' === $key && is_numeric( $vehicle[ $key ] ) ) {
									echo '$' . esc_html( number_format_i18n( (float) $vehicle[ $key ] ) );
								} elseif ( 'mileage' === $key && is_numeric( $vehicle[ $key ] ) ) {
									echo esc_html( number_format_i18n( (float) $vehicle[ $key ] ) ) . ' mi';
								} else {
									echo esc_html( (string) $vehicle[ $key ] );
								}
								?>
							</dd>
						<?php endforeach; ?>
					</dl>
					<?php if ( ! empty( $vehicle['vdp_url'] ) ) : ?>
						<a href="<?php echo esc_url( $vehicle['vdp_url'] ); ?>">View details</a>
					<?php endif; ?>
				</div>
			</article>
		<?php endforeach; ?>
	<?php endif; ?>

	<nav class="llm-pagination">
		<?php if ( $page > 1 ) : ?>
			<a href="<?php echo esc_url( $page_url( $page - 1 ) ); ?>">&larr; Previous page</a>
		<?php endif; ?>
		<?php if ( $page < $total_pg ) : ?>
			<a href="<?php echo esc_url( $page_url( $page + 1 ) ); ?>">Next page &rarr;</a>
		<?php endif; ?>
	</nav>
</body>
</html>
