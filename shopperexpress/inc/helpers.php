<?php
/**
 * Theme related helper functions.
 *
 * @package Shopperexpress
 */

namespace App;

use Auryn\Injector;

/**
 * Get theme injector.
 *
 * @return Injector Auryn DI instance.
 */
function injector(): Injector {
	static $injector;

	if ( ! $injector ) {
		$injector = new Injector();
	}

	return $injector;
}

/**
 * Helper function. Returns the URL of a build asset under assets/.
 *
 * @param  string $asset_path asset path, relative to assets/.
 * @return string
 */
function asset_url( string $asset_path ): string {
	return get_template_directory_uri() . '/assets/' . $asset_path;
}


/**
 * Returns true when the Intice API mode is active.
 *
 * Use this in templates to switch between WordPress data and the external API:
 *   if ( \App\is_api_mode() ) { ... }
 *
 * @return bool
 */
function is_api_mode(): bool {
	static $mode;

	if ( null === $mode ) {
		$mode = (bool) get_option(
			\App\Components\SOC\Modules\Api_Settings::OPTION_API_MODE,
			false
		);
	}

	return $mode;
}

/**
 * Returns the site's active video provider: 'wistia' (default/legacy) or 'vista'.
 *
 * Always falls back to 'wistia' — a missing/unset option, or any value other
 * than 'vista', resolves to legacy Wistia behaviour so deploying this never
 * changes a site's embeds until its setting is explicitly changed.
 *
 * @return string 'wistia'|'vista'.
 */
function get_video_provider(): string {
	static $provider;

	if ( null === $provider ) {
		$stored   = get_option( \App\Components\SOC\Modules\Video_Provider::OPTION_VIDEO_PROVIDER, 'wistia' );
		$provider = 'vista' === $stored ? 'vista' : 'wistia';
	}

	return $provider;
}

/**
 * Build the iframe-embed URL for a stored Wistia/VISTA media ID, honoring the
 * site's active video provider. Media IDs are shared between both platforms
 * (VISTA preserves Wistia's original IDs on migrated videos), so the same
 * `$video_id` value works for either provider's URL shape.
 *
 * @param string $video_id Wistia media ID / VISTA video ID.
 * @return string
 */
function get_video_embed_url( string $video_id ): string {
	if ( 'vista' === get_video_provider() ) {
		return \App\Components\SOC\Modules\Video_Provider::VISTA_BASE_URL . '/embed/iframe/' . $video_id;
	}

	return '//fast.wistia.net/embed/iframe/' . $video_id;
}

/**
 * Resolve a vehicle's active image gallery straight from payload.use_images_list,
 * independent of Nexus's own (sometimes stale) active_image_list resolution.
 *
 * Nexus exposes both pre-normalized lists on every vehicle response —
 * images_primary / images_srp — each shaped as { url, is_background, is_reverse }.
 * We just pick between them ourselves using the raw import value instead of
 * trusting the server-resolved `images` field.
 *
 * @param array $vehicle Intice API vehicle object (list or single response).
 * @return array List of { url, is_background, is_reverse } items.
 */
function resolve_vehicle_gallery( array $vehicle ): array {
	$use = $vehicle['payload']['use_images_list'] ?? ( $vehicle['use_images_list'] ?? 'primary' );

	$list = in_array( $use, array( 'alternative', 'srp' ), true )
		? ( $vehicle['images_srp'] ?? array() )
		: ( $vehicle['images_primary'] ?? array() );

	if ( ! empty( $list ) ) {
		return $list;
	}

	// Fall back to Nexus's own resolved list, then a single thumb/image.
	if ( ! empty( $vehicle['images'] ) ) {
		return $vehicle['images'];
	}

	$fallback_url = $vehicle['image'] ?? ( $vehicle['thumb'] ?? '' );

	return $fallback_url ? array( array( 'url' => $fallback_url, 'is_background' => false, 'is_reverse' => false ) ) : array();
}

/**
 * Resolve a CPT/ACF vehicle's active image gallery, mirroring resolve_vehicle_gallery()'s
 * fallback chain for API mode: try the list picked by `use_images_list`, and if it's empty
 * (e.g. only one of the two repeaters was ever synced by the feed import), fall back to the
 * other one instead of rendering no images at all.
 *
 * @param int         $post_id         Vehicle post ID.
 * @param string|null $use_images_list Raw `use_images_list` field value ('primary'|'alternative').
 * @return array List of ACF `gallery`/`gallery_srp` repeater rows ({image_url, image_background, image_reverse}).
 */
function resolve_cpt_vehicle_gallery( int $post_id, ?string $use_images_list = null ): array {
	$primary   = get_field( 'gallery', $post_id );
	$alternate = get_field( 'gallery_srp', $post_id );

	$primary   = is_array( $primary ) ? $primary : array();
	$alternate = is_array( $alternate ) ? $alternate : array();

	$list = 'alternative' === $use_images_list ? $alternate : $primary;

	if ( ! empty( $list ) ) {
		return $list;
	}

	$other = 'alternative' === $use_images_list ? $primary : $alternate;

	return ! empty( $other ) ? $other : array();
}

/**
 * Resolve a scalar field from a raw Intice vehicle array, checking the
 * dealer-mapped `payload` bag first (and its underscore variant) — dealer
 * edits/overrides are merged into `payload` server-side (see
 * Intice_Api_Client::update_vehicle()) — then falling back to the raw
 * top-level Nexus field for vehicles with no payload override.
 *
 * @param array  $vehicle Intice API vehicle object (list or single response).
 * @param string $key     Field key to resolve.
 * @return string
 */
function resolve_vehicle_field( array $vehicle, string $key ): string {
	$payload        = $vehicle['payload'] ?? array();
	$key_underscore = str_replace( '-', '_', $key );

	$value = $payload[ $key ]
		?? $payload[ $key_underscore ]
		?? $vehicle[ $key ]
		?? $vehicle[ $key_underscore ]
		?? '';

	return (string) $value;
}

/**
 * Resolve a status badge's display text (with optional emoji prefix) and inline
 * color style, based on the `badge_color_rules` repeater (ACF, Options: Listings).
 *
 * Rules are matched case-insensitively as a substring of $text, in the order they
 * were entered — the first match wins. A rule's `show_on` choices (srp/vdp) and
 * `post_types` choices (listings/used-listings) can both restrict where it applies;
 * leaving either empty applies the rule everywhere. When nothing matches, the badge
 * falls back to its default CSS styling (empty style).
 *
 * @param string $text      Raw badge text, e.g. "In Stock", "Honda Certified".
 * @param string $context   'srp' or 'vdp', or '' to ignore each rule's `show_on`.
 * @param string $post_type 'listings' or 'used-listings', or '' to ignore each rule's `post_types`.
 * @return array{text: string, style: string}
 */
function resolve_badge_style( string $text, string $context = '', string $post_type = '' ): array {
	$text = trim( $text );

	if ( '' === $text ) {
		return array(
			'text'  => '',
			'style' => '',
		);
	}

	$rules = get_field( 'badge_color_rules', 'options' );

	if ( ! empty( $rules ) && is_array( $rules ) ) {
		foreach ( $rules as $rule ) {
			$match = trim( (string) ( $rule['match_text'] ?? '' ) );

			if ( '' === $match || false === stripos( $text, $match ) ) {
				continue;
			}

			$show_on = (array) ( $rule['show_on'] ?? array() );

			if ( ! empty( $show_on ) && $context && ! in_array( $context, $show_on, true ) ) {
				continue;
			}

			$post_types = (array) ( $rule['post_types'] ?? array() );

			if ( ! empty( $post_types ) && $post_type && ! in_array( $post_type, $post_types, true ) ) {
				continue;
			}

			$emoji = trim( (string) ( $rule['emoji'] ?? '' ) );
			$bg    = (string) ( $rule['bg_color'] ?? '' );
			$color = (string) ( $rule['text_color'] ?? '' );
			$style = '';

			if ( $bg ) {
				$style .= 'background-color:' . $bg . ';';
			}

			if ( $color ) {
				$style .= 'color:' . $color . ';';
			}

			return array(
				'text'  => $emoji ? $emoji . ' ' . $text : $text,
				'style' => $style,
			);
		}
	}

	return array(
		'text'  => $text,
		'style' => '',
	);
}

/**
 * Build the normalized vehicle list + pagination data for the /llm/inventory/
 * page, reusing the same data source and eligibility rules as the SRP so the
 * two never disagree (see Intice_Rest::apply_vehicle_filters() / WP post_status
 * for CPT mode).
 *
 * @param int $page     1-based page number.
 * @param int $per_page Vehicles per page.
 * @return array{vehicles: array[], total: int, total_pages: int, page: int, per_page: int, last_updated: ?string, last_updated_ts: ?int, stale: bool}
 */
function get_llm_inventory_page( int $page, int $per_page ): array {
	$page     = max( 1, $page );
	$per_page = max( 1, $per_page );

	if ( is_api_mode() ) {
		return get_llm_inventory_page_api( $page, $per_page );
	}

	return get_llm_inventory_page_cpt( $page, $per_page );
}

/**
 * API-mode data source — fetches the same filtered vehicle lists the SRP uses
 * (per post type, via Intice_Api_Client + Intice_Rest::apply_vehicle_filters()),
 * then paginates in PHP since Nexus's own condition fetch returns a full list.
 *
 * @param int $page
 * @param int $per_page
 * @return array
 */
function get_llm_inventory_page_api( int $page, int $per_page ): array {
	$client     = \App\Components\Api\Intice_Api_Client::instance();
	$vehicles   = array();
	$failed     = false;
	$fetched_at = array();

	foreach ( array(
		'listings'      => 'new',
		'used-listings' => 'used',
	) as $post_type => $condition ) {
		$response = $client->get_vehicles( array( 'condition' => $condition ) );

		if ( is_wp_error( $response ) ) {
			// Nexus failed and there's no cached copy — this condition is missing.
			$failed = true;
			continue;
		}

		$fetched_at[] = \App\Components\Api\Intice_Api_Client::get_vehicles_fetched_at( array( 'condition' => $condition ) );

		$raw = \App\Components\Api\Intice_Rest::apply_vehicle_filters( $response['data'] ?? array(), $post_type );

		foreach ( $raw as $vehicle ) {
			$vehicles[] = map_llm_inventory_vehicle_api( $vehicle, $post_type );
		}
	}

	$total       = count( $vehicles );
	$total_pages = max( 1, (int) ceil( $total / $per_page ) );
	$page        = min( $page, $total_pages );
	$slice       = array_slice( $vehicles, ( $page - 1 ) * $per_page, $per_page );

	// Age of the older of the two lists — the page is only as fresh as that.
	$updated = in_array( null, $fetched_at, true ) || ! $fetched_at ? null : min( $fetched_at );

	// Stale = the feed is actually failing, not just "the 10-minute cache
	// expired": a condition couldn't be loaded at all, the data is older than
	// Llm_Inventory::STALE_AFTER (normal traffic/imports keep it well under
	// that), or the last post-import rebuild failed after this data was fetched.
	$rebuild = \App\Components\Api\Intice_Rest::get_rebuild_state();
	$stale   = $failed
		|| null === $updated
		|| $updated < time() - \App\Components\Base\Llm_Inventory::STALE_AFTER
		|| ( 'failed' === ( $rebuild['status'] ?? '' ) && (int) ( $rebuild['finished_at'] ?? 0 ) > $updated );

	return array(
		'vehicles'        => $slice,
		'total'           => $total,
		'total_pages'     => $total_pages,
		'page'            => $page,
		'per_page'        => $per_page,
		'last_updated'    => $updated ? wp_date( 'M j, Y g:i A', $updated ) : null,
		'last_updated_ts' => $updated,
		'stale'           => $stale,
	);
}

/**
 * CPT-mode data source — plain WP_Query across both vehicle post types,
 * published only (the only eligibility signal that exists for these CPTs —
 * see inc/Components/Base/class-api.php, no "sold"/hidden meta applies here).
 *
 * @param int $page
 * @param int $per_page
 * @return array
 */
function get_llm_inventory_page_cpt( int $page, int $per_page ): array {
	$query = new \WP_Query(
		array(
			'post_type'      => array( 'listings', 'used-listings' ),
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	$vehicles = array();

	foreach ( $query->posts as $post ) {
		$vehicles[] = map_llm_inventory_vehicle_post( $post->ID, $post->post_type );
	}

	$last_updated    = null;
	$last_updated_ts = null;
	$latest_post_id  = get_posts(
		array(
			'post_type'      => array( 'listings', 'used-listings' ),
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'orderby'        => 'modified',
			'order'          => 'DESC',
			'fields'         => 'ids',
		)
	);

	if ( ! empty( $latest_post_id ) ) {
		$last_updated    = get_the_modified_date( 'M j, Y g:i A', $latest_post_id[0] );
		$last_updated_ts = (int) get_post_modified_time( 'U', true, $latest_post_id[0] );
	}

	return array(
		'vehicles'        => $vehicles,
		'total'           => (int) $query->found_posts,
		'total_pages'     => max( 1, (int) $query->max_num_pages ),
		'page'            => $page,
		'per_page'        => $per_page,
		'last_updated'    => $last_updated,
		'last_updated_ts' => $last_updated_ts,
		// CPT mode reads straight from the DB on every request — no cache layer to go stale.
		'stale'           => false,
	);
}

/**
 * Normalize one Intice API vehicle array into the flat shape the
 * /llm/inventory/ template renders. Missing fields are omitted, not invented.
 *
 * @param array  $vehicle   Raw Intice vehicle array.
 * @param string $post_type 'listings' or 'used-listings'.
 * @return array
 */
function map_llm_inventory_vehicle_api( array $vehicle, string $post_type ): array {
	$payload   = $vehicle['payload'] ?? array();
	$condition = 'used-listings' === $post_type ? 'used' : 'new';
	$certified = $payload['certified'] ?? ( $vehicle['certified'] ?? null );

	if ( null !== $certified && filter_var( $certified, FILTER_VALIDATE_BOOLEAN ) ) {
		$condition = 'certified';
	}

	$out = array( 'condition' => $condition );

	$vin = strtoupper( (string) ( $vehicle['vin'] ?? '' ) );
	if ( $vin ) {
		$out['vin'] = $vin;
	}

	$trim = resolve_vehicle_field( $vehicle, 'trim' );

	$fields = array(
		'year'           => $vehicle['year'] ?? null,
		'make'           => $vehicle['make'] ?? null,
		'model'          => $vehicle['model'] ?? null,
		'trim'           => '' !== $trim ? $trim : null,
		'stock'          => resolve_vehicle_field( $vehicle, 'stock' ),
		'mileage'        => resolve_vehicle_field( $vehicle, 'mileage' ),
		'body_style'     => resolve_vehicle_field( $vehicle, 'body_style' ),
		'drivetrain'     => resolve_vehicle_field( $vehicle, 'drivetrain' ),
		'transmission'   => resolve_vehicle_field( $vehicle, 'transmission' ),
		'fuel_type'      => resolve_vehicle_field( $vehicle, 'fuel_type' ),
		'exterior_color' => resolve_vehicle_field( $vehicle, 'exterior_color' ),
		'interior_color' => resolve_vehicle_field( $vehicle, 'interior_color' ),
	);

	foreach ( $fields as $key => $value ) {
		if ( null !== $value && '' !== $value ) {
			$out[ $key ] = $value;
		}
	}

	$price = $vehicle['price'] ?? ( $payload['price'] ?? null );
	if ( is_numeric( $price ) && (float) $price > 0 ) {
		$out['price'] = (float) $price;
	}

	$status              = $payload['vehicle_status'] ?? null;
	$out['availability'] = $status ? sanitize_text_field( (string) $status ) : 'available';

	if ( $vin ) {
		$out['vdp_url'] = \App\Components\Api\Intice_VDP::vdp_url( $vin, $post_type, $vehicle );
	}

	$images = array_values(
		array_filter(
			array_map(
				static function ( $image ) {
					return $image['url'] ?? '';
				},
				resolve_vehicle_gallery( $vehicle )
			)
		)
	);

	if ( ! empty( $images ) ) {
		$out['images'] = $images;
	}

	return $out;
}

/**
 * Normalize one `listings`/`used-listings` WP post into the same flat shape
 * as map_llm_inventory_vehicle_api(). Missing fields are omitted, not invented.
 *
 * @param int    $post_id
 * @param string $post_type 'listings' or 'used-listings'.
 * @return array
 */
function map_llm_inventory_vehicle_post( int $post_id, string $post_type ): array {
	$condition = 'used-listings' === $post_type ? 'used' : 'new';

	if ( get_field( 'certified', $post_id ) ) {
		$condition = 'certified';
	}

	$out = array(
		'condition'    => $condition,
		'availability' => 'available',
		'vdp_url'      => get_permalink( $post_id ),
	);

	$vin = get_field( 'vin_number', $post_id );
	if ( $vin ) {
		$out['vin'] = strtoupper( (string) $vin );
	}

	$acf_fields = array(
		'year'           => 'year',
		'make'           => 'make',
		'model'          => 'model',
		'trim'           => 'trim',
		'stock'          => 'stock_number',
		'mileage'        => 'mileage',
		'body_style'     => 'body_style',
		'drivetrain'     => 'drivetrain',
		'transmission'   => 'transmission',
		'fuel_type'      => 'fuel_type',
		'exterior_color' => 'exterior_color',
		'interior_color' => 'interior_color',
	);

	foreach ( $acf_fields as $out_key => $acf_key ) {
		$value = get_field( $acf_key, $post_id );
		if ( null !== $value && '' !== $value && false !== $value ) {
			$out[ $out_key ] = $value;
		}
	}

	$price = get_field( 'price', $post_id );
	if ( is_numeric( $price ) && (float) $price > 0 ) {
		$out['price'] = (float) $price;
	}

	$primary_image = get_field( 'primaryimageurl', $post_id );
	if ( $primary_image ) {
		$out['images'] = array( $primary_image );
	}

	return $out;
}

/**
 * Acf button helper.
 *
 * @param array  $button acf button array.
 * @param array  $classes array of button classes.
 * @param string $icon button icon html.
 */
function the_acf_button( array $button, array $classes = array(), string $icon = '' ) {
	$attributes  = ( $button['url'] ) ? ' href="' . $button['url'] . '"' : '';
	$attributes .= ( $button['target'] ) ? ' target="' . $button['target'] . '" rel="noreferrer"' : '';
	$attributes .= ( ! empty( $classes ) ) ? ' class="' . implode( ' ', $classes ) . '"' : '';

	$title = ( $button['title'] ) ? $button['title'] : '';

	$button = sprintf(
		'<a %s>%s %s</a>',
		$attributes,
		$title,
		$icon
	);

	echo wp_kses(
		$button,
		array(
			'a' => array(
				'href'   => true,
				'target' => true,
				'rel'    => true,
				'class'  => true,
			),
			'i' => array(
				'class' => true,
			),
		)
	);
}
