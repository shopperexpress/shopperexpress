<?php
/**
 * ASC (Automotive Standards Council) Data Layer.
 *
 * @package Shopperexpress
 */

namespace App\Components\Base;

use App\Components\Theme_Component;

/**
 * Class ASC_Datalayer
 *
 * @package App\Components\Base
 */
class ASC_Datalayer implements Theme_Component {

	/**
	 * Vehicle post types used for inventory detection.
	 *
	 * @var array
	 */
	private array $vehicle_post_types = array(
		'research',
		'service-offers',
		'listings',
		'used-listings',
		'offers',
		'finance-offers',
		'lease-offers',
		'conditional-offers',
	);

	/**
	 * Cached list of valid manual page type values, sourced from the ACF field choices.
	 *
	 * @var array|null
	 */
	private ?array $manual_page_types = null;

	/**
	 * Slug fragments → ASC page type (system-detected, highest priority).
	 *
	 * @var array
	 */
	private array $slug_map = array(
		'research'           => 'showroom',
		'service-offers'     => 'specials',
		'service_offers'     => 'specials',
		'offers'             => 'specials',
		'finance-offers'     => 'specials',
		'finance_offers'     => 'specials',
		'lease-offers'       => 'specials',
		'lease_offers'       => 'specials',
		'conditional-offers' => 'specials',
		'conditional_offers' => 'specials',
	);

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_head', array( $this, 'output_datalayer' ), 1 );
	}

	/**
	 * Centralized ASC page type resolver.
	 *
	 * Resolution priority:
	 * 1. System-detected (post type, template, slug)
	 * 2. Manual page meta (asc_page_type)
	 * 3. Fallback: 'unknown'
	 *
	 * @return string
	 */
	public function intice_get_asc_page_type(): string {
		// 1a. Front page.
		if ( is_front_page() ) {
			return 'home';
		}

		// 1b. Single vehicle → item.
		if ( is_singular( $this->vehicle_post_types ) ) {
			return 'item';
		}

		// 1c. Vehicle archive → itemlist.
		if ( is_post_type_archive() ) {

			$current_post_type = get_query_var( 'post_type' );
			if ( empty( $current_post_type ) ) {
				$current_post_type = get_post_type();
			}
			if ( is_array( $current_post_type ) ) {
				$current_post_type = reset( $current_post_type );
			}

			if ( ! empty( $current_post_type ) && ! empty( $this->slug_map[ $current_post_type ] ) ) {
				return $this->slug_map[ $current_post_type ];
			} else {
				return 'itemlist';
			}
		}

		if ( is_page() ) {
			$template = (string) get_page_template_slug();
			$slug     = (string) get_post_field( 'post_name', get_the_ID() );

			// 1d. SRP template → itemlist.
			if ( 'pages/template-srp.php' === $template ) {
				return 'itemlist';
			}

			// 1e. Slug-based system mappings (exact slug or slug contains key).
			foreach ( $this->slug_map as $fragment => $type ) {
				if ( $slug === $fragment || false !== strpos( $slug, $fragment ) ) {
					return $type;
				}
			}

			// 1f. listings / used-listings slug fragments → itemlist.
			if ( false !== strpos( $slug, 'listings' ) ) {
				return 'itemlist';
			}

			// 1g. Template-based system mappings.
			if ( false !== strpos( $template, 'contact' ) ) {
				return 'contact';
			}

			if ( false !== strpos( $template, 'service' ) ) {
				return 'service';
			}

			// 1h. ACF post_type field (legacy system mapping).
			$acf_post_type = function_exists( 'get_field' ) ? (string) get_field( 'post_type' ) : '';

			if ( 'contact' === $acf_post_type ) {
				return 'contact';
			}

			if ( false !== strpos( $acf_post_type, 'service' ) ) {
				return 'service';
			}

			// 2. Manual asc_page_type meta (fallback for pages only when no system rule matched).
			$manual = $this->get_manual_page_type( get_the_ID() );
			if ( '' !== $manual ) {
				return $manual;
			}
		}

		// 1i. Non-page singular with service post type.
		if ( is_singular() ) {
			$post_type = (string) get_post_type();

			if ( false !== strpos( $post_type, 'service' ) ) {
				return 'service';
			}

			// 2. Manual meta on non-page singulars.
			$manual = $this->get_manual_page_type( get_the_ID() );
			if ( '' !== $manual ) {
				return $manual;
			}
		}

		// 3. Fallback.
		return 'unknown';
	}

	/**
	 * Read and return all ASC settings from ACF options.
	 *
	 * @return array
	 */
	public function get_asc_settings(): array {
		$measurement_ids = array();

		if ( function_exists( 'get_field' ) ) {
			$raw_ids = get_field( 'asc_measurement_ids', 'option' );

			if ( is_array( $raw_ids ) ) {
				foreach ( $raw_ids as $row ) {
					$id = strtolower( trim( $row['measurement_id'] ?? '' ) );
					if ( '' !== $id ) {
						$measurement_ids[] = $id;
					}
				}
			}
		}

		return array(
			'store_name'      => $this->clean_setting( get_field( 'asc_store_name', 'option' ) ),
			'oem_code'        => $this->clean_setting( get_field( 'asc_oem_code', 'option' ) ),
			'oem_brand'       => $this->clean_setting( get_field( 'asc_oem_brand', 'option' ) ),
			'affiliation'     => $this->clean_setting( get_field( 'asc_affiliation', 'option' ), 'intice' ),
			'language'        => $this->clean_setting( get_field( 'asc_language', 'option' ), 'en' ),
			'currency'        => $this->clean_setting( get_field( 'asc_currency', 'option' ), 'usd' ),
			'measurement_ids' => $measurement_ids,
		);
	}

	/**
	 * Build inventory items array for the current page.
	 *
	 * @return array
	 */
	public function get_asc_items(): array {
		if ( is_singular( $this->vehicle_post_types ) ) {
			return array( $this->build_item_from_post( get_the_ID() ) );
		}

		$is_archive  = is_post_type_archive( $this->vehicle_post_types );
		$is_srp_page = is_page() && get_page_template_slug() === 'pages/template-srp.php';

		if ( $is_archive || $is_srp_page ) {
			$post_type = 'listings';

			if ( $is_archive ) {
				$queried   = get_queried_object();
				$post_type = $queried->name ?? 'listings';
			} elseif ( $is_srp_page ) {
				$srp_post_type = get_field( 'post_type' );
				$post_type     = ( null !== $srp_post_type && '' !== $srp_post_type ) ? $srp_post_type : 'listings';
			}

			$query = new \WP_Query(
				array(
					'post_type'      => $post_type,
					'posts_per_page' => 20,
					'post_status'    => 'publish',
					'fields'         => 'ids',
				)
			);

			if ( empty( $query->posts ) ) {
				return array();
			}

			return array_values( array_map( array( $this, 'build_item_from_post' ), $query->posts ) );
		}

		return array();
	}

	/**
	 * Build ASC events array.
	 *
	 * @param string $page_type Current page type.
	 * @param array  $items     Inventory items.
	 * @return array
	 */
	public function get_asc_events( string $page_type, array $items ): array {
		$base_event = array(
			'event'         => 'asc_pageview',
			'event_owner'   => 'intice',
			'page_type'     => $page_type,
			'page_location' => '',
			'error_code'    => '',
		);

		$events = array( $base_event );

		if ( 'item' === $page_type && ! empty( $items ) ) {
			$events[] = array_merge(
				$base_event,
				array(
					'event' => 'asc_item_pageview',
					'items' => $items,
				)
			);
		}

		if ( 'itemlist' === $page_type ) {
			$events[] = array_merge(
				$base_event,
				array(
					'event' => 'asc_itemlist_pageview',
					'items' => $items,
				)
			);
		}

		return $events;
	}

	/**
	 * Build the full ASC data layer array.
	 *
	 * @return array
	 */
	public function get_asc_datalayer(): array {
		$settings  = $this->get_asc_settings();
		$page_type = $this->intice_get_asc_page_type();
		$items     = $this->get_asc_items();
		$events    = $this->get_asc_events( $page_type, $items );

		return array(
			'measurement_ids' => $settings['measurement_ids'],
			'store_name'      => $settings['store_name'],
			'oem_code'        => $settings['oem_code'],
			'oem_brand'       => $settings['oem_brand'],
			'affiliation'     => $settings['affiliation'],
			'language'        => $settings['language'],
			'currency'        => $settings['currency'],
			'page_type'       => $page_type,
			'items'           => $items,
			'events'          => $events,
		);
	}

	/**
	 * Build the phone → department lookup map from ACF options.
	 *
	 * @return array Keyed by normalized phone number, value is department string.
	 */
	public function get_phone_lookup(): array {
		$lookup = array();

		if ( ! function_exists( 'get_field' ) ) {
			return $lookup;
		}

		$rows = get_field( 'asc_phone_lookup', 'option' );

		if ( ! is_array( $rows ) ) {
			return $lookup;
		}

		foreach ( $rows as $row ) {
			$number     = \preg_replace( '/[^\d+]/', '', trim( (string) ( $row['phone_number'] ?? '' ) ) );
			$department = sanitize_text_field( $row['department'] ?? 'unknown' );

			if ( '' !== $number ) {
				$lookup[ $number ] = $department;
			}
		}

		return $lookup;
	}

	/**
	 * Output the ASC data layer script in <head>.
	 *
	 * @return void
	 */
	public function output_datalayer(): void {
		if ( ! get_field( 'asc_active', 'option' ) ) {
			return;
		}

		$datalayer    = $this->get_asc_datalayer();
		$phone_lookup = $this->get_phone_lookup();
		?>
		<script>
		window.asc_datalayer = <?php echo wp_json_encode( $datalayer ); ?>;
		window.asc_phone_lookup = <?php echo wp_json_encode( (object) $phone_lookup ); ?>;
		(function () {
			var dl = window.asc_datalayer;
			if (!dl) return;
			dl.page_type = <?php echo wp_json_encode( $datalayer['page_type'] ); ?>;
			if (!Array.isArray(dl.events)) dl.events = [];
			var loc = window.location.href;
			dl.events.forEach(function (e) {
				e.page_location = loc;
			});
		}());
		</script>
		<?php
	}

	/**
	 * Read manually assigned ASC page type from post meta (via ACF).
	 *
	 * @param int $post_id Post ID.
	 * @return string Empty string if not set or invalid.
	 */
	private function get_manual_page_type( int $post_id ): string {
		if ( ! function_exists( 'get_field' ) ) {
			return '';
		}

		$value = (string) get_field( 'asc_page_type', $post_id );

		if ( '' === $value || ! in_array( $value, $this->get_manual_page_types(), true ) ) {
			return '';
		}

		return $value;
	}

	/**
	 * Return valid manual page type values sourced from the ACF field choices.
	 *
	 * Falls back to an empty array if ACF is not available.
	 *
	 * @return array
	 */
	private function get_manual_page_types(): array {
		if ( null !== $this->manual_page_types ) {
			return $this->manual_page_types;
		}

		$this->manual_page_types = array();

		if ( function_exists( 'acf_get_field' ) ) {
			$field = acf_get_field( 'field_asc_page_type' );

			if ( is_array( $field ) && ! empty( $field['choices'] ) ) {
				$this->manual_page_types = array_keys( $field['choices'] );
			}
		}

		return $this->manual_page_types;
	}

	/**
	 * Build a single item array from a post ID.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	private function build_item_from_post( int $post_id ): array {
		$post_type = get_post_type( $post_id );

		return array(
			'item_id'             => $this->normalize( get_field( 'vin_number', $post_id ) ),
			'item_number'         => $this->normalize( get_field( 'stock_number', $post_id ) ),
			'item_price'          => $this->normalize_price( $post_id ),
			'item_condition'      => $this->normalize( get_field( 'condition', $post_id ) ),
			'item_year'           => $this->normalize( get_field( 'year', $post_id ) ),
			'item_make'           => $this->normalize( get_field( 'make', $post_id ) ),
			'item_model'          => $this->normalize( get_field( 'model', $post_id ) ),
			'item_variant'        => $this->normalize( get_field( 'trim', $post_id ) ),
			'item_color'          => $this->normalize( get_field( 'exterior_color', $post_id ) ),
			'item_type'           => $this->resolve_item_type( $post_type ),
			'item_category'       => $this->normalize( get_field( 'body_style', $post_id ) ),
			'item_fuel_type'      => $this->normalize( get_field( 'fuel_type', $post_id ) ),
			'item_inventory_date' => $this->normalize( get_field( 'dateinstock', $post_id ) ),
		);
	}

	/**
	 * Resolve item type from post type.
	 *
	 * @param string $post_type Post type slug.
	 * @return string
	 */
	private function resolve_item_type( string $post_type ): string {
		if ( 'used-listings' === $post_type ) {
			return 'used';
		}

		if ( 'listings' === $post_type ) {
			return 'new';
		}

		return $this->normalize( $post_type );
	}

	/**
	 * Resolve best price for a vehicle post.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private function normalize_price( int $post_id ): string {
		$price = get_field( 'price_sort', $post_id );

		if ( empty( $price ) ) {
			$price = get_field( 'msrp', $post_id );
		}

		if ( empty( $price ) ) {
			$price = get_field( 'price', $post_id );
		}

		return null !== $price && '' !== $price ? (string) $price : '';
	}

	/**
	 * Lowercase + spaces to underscores. Returns '' on empty.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private function normalize( $value ): string {
		if ( null === $value || false === $value || '' === trim( (string) $value ) ) {
			return '';
		}

		return strtolower( str_replace( ' ', '_', trim( (string) $value ) ) );
	}

	/**
	 * Sanitize and lowercase a settings string with optional default.
	 *
	 * @param mixed  $value   Raw value.
	 * @param string $default Fallback value.
	 * @return string
	 */
	private function clean_setting( $value, string $default = '' ): string {
		$clean_value = ( '' !== (string) $value ) ? $value : $default;
		$clean       = strtolower( trim( (string) $clean_value ) );

		return sanitize_text_field( $clean );
	}
}
