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
	 * Offer post types that use Offer schema instead of Vehicle schema.
	 *
	 * @var string[]
	 */
	private array $offer_types = array( 'offers', 'lease-offers', 'finance-offers', 'conditional-offers', 'research' );

	/**
	 * Render JSON LD.
	 *
	 * @return void
	 */
	public function render_json(): void {

		$singular_types    = array( 'listings', 'used-listings' );
		$is_singular_vdp   = is_singular( $singular_types );
		$is_singular_offer = is_singular( $this->offer_types );
		$is_page_with_pt   = is_page() && get_field( 'post_type' );
		$is_archive_pt     = is_post_type_archive();

		if ( ! $is_singular_vdp && ! $is_singular_offer && ! $is_page_with_pt && ! $is_archive_pt ) {
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

		$is_offer_single =
		is_singular( $this->offer_types )
		&& ! is_post_type_archive();

		$is_page_archive =
		is_page()
		&& in_array( $acf_post_type, array_merge( $singular_types, $this->offer_types ), true );

		$is_archive =
		is_post_type_archive()
		|| $is_page_archive;

		if ( $is_vdp_single ) {
			return $this->get_single_vehicle_json();
		}

		if ( $is_offer_single ) {
			return $this->get_single_offer_json();
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

		$additional = $this->build_features_additional_property( $post_id );
		if ( ! empty( $additional ) ) {
			$schema['additionalProperty'] = $additional;
		}

		$json = wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );

		return '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>' . "\n";
	}

	/**
	 * Build additionalProperty array from ACF features_items for a post.
	 *
	 * @param int $post_id
	 * @return array
	 */
	private function build_features_additional_property( int $post_id ): array {
		$flat = array();

		while ( have_rows( 'features_items', $post_id ) ) {
			the_row();
			while ( have_rows( 'features' ) ) {
				the_row();
				$flat[] = array(
					'ranking' => (int) get_sub_field( 'ranking' ),
					'feature' => (string) get_sub_field( 'feature' ),
					'id'      => (string) get_sub_field( 'id' ),
				);
			}
		}

		if ( empty( $flat ) ) {
			return array();
		}

		usort( $flat, fn( $a, $b ) => $b['ranking'] - $a['ranking'] );

		$overrides = array();
		while ( have_rows( 'feature_list_chromedata', 'options' ) ) {
			the_row();
			$overrides[ (string) get_sub_field( 'id' ) ] = (string) get_sub_field( 'text' );
		}

		$limit      = (int) get_field( 'limit_feature_list', 'options' );
		$additional = array();

		foreach ( $flat as $i => $feat ) {
			if ( $limit > 0 && $i >= $limit ) {
				break;
			}
			$text = ! empty( $overrides[ $feat['id'] ] ) ? $overrides[ $feat['id'] ] : $feat['feature'];
			if ( $text ) {
				$additional[] = array(
					'@type' => 'PropertyValue',
					'name'  => 'Feature',
					'value' => wp_strip_all_tags( $text ),
				);
			}
		}

		return $additional;
	}

	/**
	 * Build Offer JSON-LD for a single offer (lease, finance, or conditional) page.
	 *
	 * @return string
	 */
	private function get_single_offer_json(): string {
		$post_id   = get_the_ID();
		$post_type = get_post_type( $post_id );
		$permalink = get_permalink( $post_id );
		$title     = wp_strip_all_tags( get_the_title( $post_id ) );

		$schema = array(
			'@context'     => 'https://schema.org',
			'@type'        => 'Offer',
			'@id'          => esc_url( $permalink ) . '/#offer',
			'name'         => $title,
			'url'          => esc_url( $permalink ),
			'availability' => 'https://schema.org/InStock',
		);

		switch ( $post_type ) {

			case 'lease-offers':
				$description = wp_strip_all_tags( (string) get_field( 'conditional_description', $post_id ) );
				if ( $description ) {
					$schema['description'] = $description;
				}

				$payment = get_field( 'payment', $post_id );
				if ( $payment ) {
					$schema['price']         = (string) $payment;
					$schema['priceCurrency'] = 'USD';
				}
				$prices = get_field( 'prices', $post_id );
				$price  = 0;
				if ( is_array( $prices ) && ! empty( $prices[0]['price'] ) ) {
					$price = $prices[0]['price'];
				}
				if ( ! empty( $price ) ) {
					$schema['price']         = (string) $price;
					$schema['priceCurrency'] = 'USD';
				}

				$imagelist = get_field( 'imagelistpath', $post_id );
				if ( is_array( $imagelist ) && ! empty( $imagelist[0]['image_url'] ) ) {
					$schema['photo'] = $imagelist[0]['image_url'];
				}

				$end_date = get_field( 'end_date', $post_id );
				if ( $end_date ) {
					$schema['validThrough'] = $end_date;
				}

				$additional = array();

				$term = get_field( 'term', $post_id );
				if ( $term ) {
					$additional[] = array(
						'@type' => 'PropertyValue',
						'name'  => 'Lease Term',
						'value' => $term . ' Months',
					);
				}

				$due_at_signing = get_field( 'due_at_signing', $post_id );
				if ( $due_at_signing ) {
					$additional[] = array(
						'@type' => 'PropertyValue',
						'name'  => 'Due at Signing',
						'value' => '$' . $due_at_signing,
					);
				}

				$yearly_mileage = get_field( 'yearly_excess_mileage', $post_id );
				if ( $yearly_mileage ) {
					$additional[] = array(
						'@type' => 'PropertyValue',
						'name'  => 'Mileage Allowance',
						'value' => number_format( (int) $yearly_mileage ) . ' miles/year',
					);
				}

				if ( ! empty( $additional ) ) {
					$schema['additionalProperty'] = $additional;
				}

				$car = $this->build_car_schema( $post_id );
				if ( ! empty( $car ) ) {
					$schema['itemOffered'] = $car;
				}
				break;

			case 'finance-offers':
				$description = wp_strip_all_tags( (string) get_field( 'apr_description', $post_id ) );
				if ( $description ) {
					$schema['description'] = $description;
				}

				$end_date = get_field( 'end_date', $post_id );
				if ( $end_date ) {
					$schema['validThrough'] = $end_date;
				}

				$additional = array();

				$apr = get_field( 'apr', $post_id );
				if ( $apr ) {
					$additional[] = array(
						'@type' => 'PropertyValue',
						'name'  => 'APR',
						'value' => $apr . '%',
					);
				}

				$term = get_field( 'term', $post_id );
				if ( $term ) {
					$additional[] = array(
						'@type' => 'PropertyValue',
						'name'  => 'Term',
						'value' => $term . ' Months',
					);
				}

				if ( ! empty( $additional ) ) {
					$schema['additionalProperty'] = $additional;
				}

				$car = $this->build_car_schema( $post_id );
				if ( ! empty( $car ) ) {
					$schema['itemOffered'] = $car;
				}
				break;

			case 'conditional-offers':
			case 'offers':
				$description = wp_strip_all_tags( (string) get_field( 'conditional_description', $post_id ) );
				if ( $description ) {
					$schema['description'] = $description;
				}

				$cash = get_field( 'conditional_cash', $post_id );
				if ( $cash ) {
					$schema['price']         = (string) (int) $cash;
					$schema['priceCurrency'] = 'USD';
				}

				$end_date = get_field( 'end_date', $post_id );
				if ( $end_date ) {
					$schema['validThrough'] = $end_date;
				}
				break;
		}

		$json = wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );

		return '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>' . "\n";
	}

	/**
	 * Build a Car schema fragment from ACF fields on a given post.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	private function build_car_schema( int $post_id ): array {
		$car = array();

		$year  = wp_strip_all_tags( (string) get_field( 'year', $post_id ) );
		$make  = wp_strip_all_tags( (string) get_field( 'make', $post_id ) );
		$model = wp_strip_all_tags( (string) get_field( 'model', $post_id ) );
		$trim  = wp_strip_all_tags( (string) get_field( 'trim', $post_id ) );

		if ( ! $make && ! $model && ! $year ) {
			return array();
		}

		$car['@type'] = 'Car';

		$name_parts = array_filter( array( $year, $make, $model, $trim ) );
		if ( ! empty( $name_parts ) ) {
			$car['name'] = implode( ' ', $name_parts );
		}

		if ( $make ) {
			$car['brand'] = array(
				'@type' => 'Brand',
				'name'  => $make,
			);
		}

		if ( $model ) {
			$car['model'] = $model;
		}

		if ( $year ) {
			$car['vehicleModelDate'] = $year;
		}

		if ( $trim ) {
			$car['vehicleConfiguration'] = $trim;
		}

		$gallery = get_field( 'gallery', $post_id );
		if ( ! empty( $gallery ) && is_array( $gallery ) ) {
			$first_image = reset( $gallery );
			$image_url   = $first_image['image_url'] ?? ( $first_image['image_background'] ?? '' );
			if ( $image_url ) {
				$car['image'] = esc_url( $image_url );
			}
		}

		return $car;
	}

	/**
	 * Build CollectionPage + ItemList JSON-LD for archive / page-with-post-type.
	 *
	 * @return string
	 */
	private function get_collection_json(): string {
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

		$transient     = get_field( $post_type . '_transient', 'option' );
		$get_transient = get_transient( $transient );

		if ( ! $get_transient ) {
			return '';
		}

		$get_transient['vehicles'] = array_slice( $get_transient['vehicles'], 0, 24 );
		$is_offer_archive          = in_array( $post_type, $this->offer_types, true );

		if ( $is_offer_archive ) :
			$items    = array();
			$position = 1;
			foreach ( $get_transient['vehicles'] as $item ) {
				$offer_item = array(
					'@type' => 'Offer',
					'name'  => esc_html( $item['title'] ),
					'url'   => esc_url( $item['link'] ),
				);

				if ( ! empty( $item['photo'] ) ) {
					$offer_item['image'] = esc_url( $item['photo'] );
				}
				if ( ! empty( $item['payment'] ) ) {
					$offer_item['price']         = (string) $item['payment'];
					$offer_item['priceCurrency'] = 'USD';
				}
				if ( ! empty( $item['price'] ) ) {
					$offer_item['price']         = (string) $item['price'];
					$offer_item['priceCurrency'] = 'USD';
				}
				$items[] = array(
					'@type'    => 'ListItem',
					'position' => $position,
					'item'     => $offer_item,
				);
				++$position;
			}

			$schema = array(
				'@context'   => 'https://schema.org',
				'@type'      => 'CollectionPage',
				'@id'        => esc_url( $permalink ) . '/#collection',
				'name'       => esc_html( $title ),
				'url'        => esc_url( $permalink ),
				'mainEntity' => array(
					'@type'           => 'ItemList',
					'numberOfItems'   => count( $items ),
					'itemListElement' => $items,
				),
			);

			$json = wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );

			echo '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>' . "\n"; // phpcs:ignore
		else :
			$items    = array();
			$position = 1;
			foreach ( $get_transient['vehicles'] as $item ) {
				$vehicle = array(
					'@type' => 'Vehicle',
					'name'  => esc_html( $item['title'] ),
					'url'   => esc_url( $item['link'] ),
				);

				if ( ! empty( $item['ai_vdp_description'] ) ) {
					$vehicle['description'] = wp_strip_all_tags( trim( $item['ai_vdp_description'] ) );
				}

				if ( ! empty( $item['photo'] ) ) {
					$vehicle['image'] = esc_url( $item['photo'] );
				}

				if ( ! empty( $item['terms']['make'][0] ) ) {
					$vehicle['brand'] = array(
						'@type' => 'Brand',
						'name'  => esc_html( $item['terms']['make'][0] ),
					);
				}

				if ( ! empty( $item['terms']['model'][0] ) ) {
					$model = esc_html( $item['terms']['model'][0] );
					if ( ! empty( $item['terms']['trim'][0] ) ) {
						$model .= ' ' . esc_html( $item['terms']['trim'][0] );
					}
					$vehicle['model'] = $model;
				}

				if ( ! empty( $item['year'] ) ) {
					$vehicle['vehicleModelDate'] = esc_html( $item['year'] );
				}

				if ( ! empty( $item['terms']['vin'][0] ) ) {
					$vehicle['vehicleIdentificationNumber'] = esc_html( $item['terms']['vin'][0] );
				}

				$offer = array(
					'@type'         => 'Offer',
					'priceCurrency' => 'USD',
					'availability'  => 'https://schema.org/InStock',
					'itemCondition' => 'https://schema.org/NewCondition',
					'url'           => esc_url( $item['link'] ),
				);

				if ( ! empty( $item['price'] ) || 0 == $item['price'] ) {
					$offer['price'] = (string) $item['price'];
				}

				if ( ! empty( $item['dealer_name'] ) ) {
					$offer['seller'] = array(
						'@type' => 'AutoDealer',
						'name'  => esc_html( $item['dealer_name'] ),
						'url'   => esc_url( $item['link'] ),
					);
				}

				$vehicle['offers'] = $offer;

				$items[] = array(
					'@type'    => 'ListItem',
					'position' => $position,
					'url'      => esc_url( $item['link'] ),
					'item'     => $vehicle,
				);
				++$position;
			}

			$schema = array(
				'@context'   => 'https://schema.org',
				'@type'      => 'CollectionPage',
				'@id'        => esc_url( $permalink ) . '/#collection',
				'name'       => esc_html( $title ),
				'url'        => esc_url( $permalink ),
				'mainEntity' => array(
					'@type'           => 'ItemList',
					'numberOfItems'   => count( $items ),
					'itemListElement' => $items,
				),
			);

			$json = wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
			echo '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>' . "\n"; // phpcs:ignore

		endif;

		return '';
	}
}
