<?php
/**
 * WordPress JSON LD.
 *
 * @package Shopperexpress
 */

namespace App\Components\Base;

use App\Components\Theme_Component;

/**
 * Class JSON_LD
 *
 * @package App\Base\Component
 */
class JSON_LD implements Theme_Component {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_head', array( $this, 'render_json' ) );
	}

	/**
	 * Render JSON LD.
	 *
	 * @return void
	 */
	public function render_json(): void {

		$singular_types  = array( 'listings', 'used-listings' );
		$is_singular_vdp = is_singular( $singular_types );
		$is_page_with_pt = is_page() && get_field( 'post_type' );
		$is_archive_pt   = is_post_type_archive();

		if ( ! $is_singular_vdp && ! $is_page_with_pt && ! $is_archive_pt ) {
			return;
		}
		// phpcs:ignore
		echo $this->get_json();
	}

	/**
	 * Get JSON LD.
	 *
	 * @return string
	 */
	public function get_json(): string {

		$singular_types = array( 'listings', 'used-listings' );

		$post_id = get_the_ID();

		$acf_post_type = function_exists( 'get_field' )
		? get_field( 'post_type', $post_id )
		: null;

		$is_vdp_single =
		is_singular( $singular_types )
		&& ! is_post_type_archive();

		$is_page_archive =
		is_page()
		&& in_array( $acf_post_type, $singular_types, true );

		$is_archive =
		is_post_type_archive()
		|| $is_page_archive;

		if ( $is_vdp_single ) {
			return $this->get_single_vehicle_json();
		}

		if ( $is_archive ) {
			return $this->get_collection_json();
		}

		return '';
	}

	/**
	 * Build Vehicle JSON-LD for a single VDP.
	 *
	 * @return string
	 */
	private function get_single_vehicle_json(): string {
		$post_id   = get_the_ID();
		$post_type = get_post_type( $post_id );
		$permalink = get_permalink( $post_id );

		$schema = array(
			'@context' => 'https://schema.org',
			'@type'    => 'Vehicle',
			'@id'      => esc_url( $permalink ) . '/#vehicle',
			'url'      => esc_url( $permalink ),
		);

		$title = wp_strip_all_tags( get_the_title( $post_id ) );
		if ( $title ) {
			$schema['name'] = $title;
		}

		$ai_desc = get_field( 'ai_vdp_description', $post_id );
		if ( $ai_desc ) {
			$schema['description'] = wp_strip_all_tags( $ai_desc );
		}

		$thumbnail = get_the_post_thumbnail_url( $post_id, 'full' );
		if ( $thumbnail ) {
			$schema['image'] = esc_url( $thumbnail );
		}

		$make = wp_strip_all_tags( (string) get_field( 'make', $post_id ) );
		if ( $make ) {
			$schema['brand'] = array(
				'@type' => 'Brand',
				'name'  => $make,
			);
		}

		$model = wp_strip_all_tags( (string) get_field( 'model', $post_id ) );
		if ( $model ) {
			$schema['model'] = $model;
		}

		$trim = wp_strip_all_tags( (string) get_field( 'trim', $post_id ) );
		if ( $trim ) {
			$schema['vehicleConfiguration'] = $trim;
		}

		$year = get_field( 'year', $post_id );
		if ( $year ) {
			$schema['vehicleModelDate'] = (string) $year;
		}

		$mileage = get_field( 'mileage', $post_id );
		if ( $mileage ) {
			$schema['mileageFromOdometer'] = array(
				'@type'    => 'QuantitativeValue',
				'value'    => (int) $mileage,
				'unitCode' => 'SMI',
			);
		}

		$vin = wp_strip_all_tags( (string) get_field( 'vin', $post_id ) );
		if ( $vin ) {
			$schema['vehicleIdentificationNumber'] = $vin;
		}

		$engine = wp_strip_all_tags( (string) get_field( 'engine', $post_id ) );
		if ( $engine ) {
			$schema['vehicleEngine'] = array(
				'@type'       => 'EngineSpecification',
				'description' => $engine,
			);
		}

		$fuel = wp_strip_all_tags( (string) get_field( 'fuel_type', $post_id ) );
		if ( $fuel ) {
			$schema['fuelType'] = $fuel;
		}

		$transmission = wp_strip_all_tags( (string) get_field( 'transmission', $post_id ) );
		if ( $transmission ) {
			$schema['vehicleTransmission'] = $transmission;
		}

		$condition = ( 'used-listings' === $post_type )
			? 'https://schema.org/UsedCondition'
			: 'https://schema.org/NewCondition';
		$offer     = array(
			'@type'         => 'Offer',
			'priceCurrency' => 'USD',
			'availability'  => 'https://schema.org/InStock',
			'itemCondition' => $condition,
			'url'           => esc_url( $permalink ),
		);

		$price = get_field( 'price', $post_id );
		if ( $price ) {
			$offer['price'] = (string) $price;
		}

		$dealer_name = wp_strip_all_tags( (string) get_field( 'dealer_name', $post_id ) );
		$dealer_url  = get_field( 'dealer_url', $post_id );
		if ( $dealer_name ) {
			$seller = array(
				'@type' => 'AutoDealer',
				'name'  => $dealer_name,
			);
			if ( $dealer_url ) {
				$seller['url'] = esc_url( $dealer_url );
			}
			$offer['seller'] = $seller;
		}

		$schema['offers'] = $offer;

		$json = wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );

		return '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>' . "\n";
	}

	/**
	 * Build CollectionPage + ItemList JSON-LD for archive / page-with-post-type.
	 *
	 * @return string
	 */
	private function get_collection_json(): string {
		ob_start();

		if ( is_post_type_archive() ) {
			$pt_object = get_queried_object();
			$post_type = $pt_object->name;
			$title     = $pt_object->labels->singular_name;
			$permalink = get_post_type_archive_link( $post_type );
		} else {
			$post_id   = get_the_ID();
			$post_type = get_field( 'post_type', $post_id );
			$title     = get_the_title( $post_id );
			$permalink = get_permalink( $post_id );
		}

		$i             = 1;
		$transient     = get_field( $post_type . '_transient', 'option' );
		$get_transient = get_transient( $transient );

		if ( $get_transient ) :
			$get_transient['vehicles'] = array_slice( $get_transient['vehicles'], 0, 24 );
			?>
		<script type="application/ld+json">
		{
			"@context": "https://schema.org",
			"@type": "CollectionPage",
			"@id": "<?php echo esc_url( $permalink ); ?>/#collection",
			"name": "<?php echo esc_html( $title ); ?>",
			"url": "<?php echo esc_url( $permalink ); ?>",
			"mainEntity": {
			"@type": "ItemList",
			"numberOfItems": <?php echo count( $get_transient['vehicles'] ); ?>,
			"itemListElement": [
			<?php foreach ( $get_transient['vehicles'] as $item ) : ?>
			{
				"@type": "ListItem",
				"position": <?php echo esc_attr( $i ); ?>,
				"url": "<?php echo esc_url( $item['link'] ); ?>",
				"item": {
					"@type": "Vehicle",
					"name": "<?php echo esc_attr( $item['title'] ); ?>",

					<?php if ( ! empty( $item['ai_vdp_description'] ) ) : ?>
						"description": "<?php echo esc_attr( trim( wp_strip_all_tags( $item['ai_vdp_description'] ) ) ); ?>",
					<?php endif; ?>

					"url": "<?php echo esc_url( $item['link'] ); ?>",

					<?php if ( ! empty( $item['photo'] ) ) : ?>
						"image": "<?php echo esc_url( $item['photo'] ); ?>",
					<?php endif; ?>

					<?php if ( ! empty( $item['terms']['make'][0] ) ) : ?>
						"brand": {
							"@type": "Brand",
							"name": "<?php echo esc_attr( $item['terms']['make'][0] ); ?>"
						},
					<?php endif; ?>

					<?php if ( ! empty( $item['terms']['model'][0] ) ) : ?>
						"model": "
						<?php
							echo esc_attr( $item['terms']['model'][0] );
						if ( ! empty( $item['terms']['trim'][0] ) ) {
							echo ' ' . esc_attr( $item['terms']['trim'][0] );
						}
						?>
						",
					<?php endif; ?>

					<?php if ( ! empty( $item['year'] ) ) : ?>
						"vehicleModelDate": "<?php echo esc_attr( $item['year'] ); ?>",
					<?php endif; ?>

					<?php if ( ! empty( $item['terms']['vin'][0] ) ) : ?>
						"vehicleIdentificationNumber": "<?php echo esc_attr( $item['terms']['vin'][0] ); ?>",
					<?php endif; ?>

					"offers": {
						"@type": "Offer",

						<?php if ( ! empty( $item['price'] ) ) : ?>
							"price": "<?php echo esc_attr( $item['price'] ); ?>",
						<?php endif; ?>

						"priceCurrency": "USD",
						"availability": "https://schema.org/InStock",
						"itemCondition": "https://schema.org/NewCondition",

						<?php if ( ! empty( $item['dealer_name'] ) ) : ?>
							"seller": {
								"@type": "AutoDealer",
								"name": "<?php echo esc_attr( $item['dealer_name'] ); ?>",
								"url": "<?php echo esc_url( $item['link'] ); ?>"
							},
						<?php endif; ?>

						"url": "<?php echo esc_url( $item['link'] ); ?>"
					}
				}
			}
				<?php
				if ( $i < count( $get_transient['vehicles'] ) ) :
					echo ',';
			endif;
				++$i;
			endforeach;
			?>
			]
			}
		}
		</script>
			<?php
		endif;

		$output = ob_get_clean();
		return $output;
	}
}
