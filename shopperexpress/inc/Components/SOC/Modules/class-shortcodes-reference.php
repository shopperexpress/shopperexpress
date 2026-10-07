<?php
/**
 * SOC Shortcodes Reference Module
 *
 * Read-only documentation panel listing every shortcode registered by the
 * theme — usage, parameters, and a working example — plus the current list
 * of admin-defined custom shortcodes (Theme Options → Shortcodes repeater).
 *
 * @package Shopperexpress
 */

namespace App\Components\SOC\Modules;

use App\Components\SOC\Contracts\SOC_Module;

/**
 * Class Shortcodes_Reference
 */
class Shortcodes_Reference implements SOC_Module {

	/**
	 * @return string
	 */
	public function get_slug(): string {
		return 'shortcodes-reference';
	}

	/**
	 * @return string
	 */
	public function get_label(): string {
		return 'Shortcodes';
	}

	/**
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-editor-code';
	}

	/**
	 * @param bool $force_refresh Unused — this panel has nothing to cache, it only
	 *                            reflects hardcoded docs plus the live options repeater.
	 * @return array
	 */
	public function collect( bool $force_refresh = false ): array {
		return array(
			'shortcodes' => $this->get_registered_shortcodes(),
			'custom'     => $this->get_custom_shortcodes(),
		);
	}

	/**
	 * @param array $data
	 * @return void
	 */
	public function render( array $data ): void {
		require get_template_directory() . '/inc/Components/SOC/views/shortcodes-reference.php';
	}

	/**
	 * Static reference of every shortcode hardcoded into the theme.
	 *
	 * Grouped by where it's registered so the "Source" column points editors
	 * at the right file when a shortcode needs to change.
	 *
	 * @return array<int, array{name: string, source: string, params: array<int, array{name: string, description: string}>, example: string, description: string}>
	 */
	private function get_registered_shortcodes(): array {
		return array(
			array(
				'name'        => 'page_id',
				'source'      => 'inc/theme-functions.php',
				'params'      => array(),
				'example'     => '[page_id]',
				'description' => 'Outputs the current post/page ID.',
			),
			array(
				'name'        => 'show',
				'source'      => 'class-shortcode.php::show()',
				'params'      => array(
					array( 'name' => 'field', 'description' => 'ACF field name to read from the current singular post.' ),
					array( 'name' => 'tax', 'description' => 'Taxonomy/field name read via get_field() instead of "field".' ),
				),
				'example'     => '[show field="model"]',
				'description' => 'Generic ACF field/taxonomy reader. Only works on singular templates (is_singular()).',
			),
			array(
				'name'        => 'get_field',
				'source'      => 'class-shortcode.php::get_field()',
				'params'      => array(
					array( 'name' => 'field', 'description' => 'ACF field name (required).' ),
					array( 'name' => 'id', 'description' => 'Post ID to read from. Defaults to the current post.' ),
					array( 'name' => 'tag', 'description' => 'Optional HTML tag to wrap the output in (e.g. "strong").' ),
				),
				'example'     => '[get_field field="price" id="post_id"]Price:[/get_field]',
				'description' => 'Paired shortcode: prints its inner content followed by the field value ("Price: 25000"). Returns empty when the field has no value. This is the format used in the Conversion Block popup text / disclosure fields — "post_id" is replaced with the actual vehicle identifier before the shortcode runs.',
			),
			array(
				'name'        => 'price',
				'source'      => 'class-shortcode.php::price()',
				'params'      => array(
					array( 'name' => 'id', 'description' => 'Post ID. Defaults to the current post.' ),
				),
				'example'     => '[price id="post_id"]',
				'description' => 'Vehicle price, wrapped in <span class="js-is-empty">…</span>.',
			),
			array(
				'name'        => 'year',
				'source'      => 'class-shortcode.php::year()',
				'params'      => array(
					array( 'name' => 'id', 'description' => 'Post ID. Defaults to the current post.' ),
				),
				'example'     => '[year id="post_id"]',
				'description' => 'Vehicle year.',
			),
			array(
				'name'        => 'make',
				'source'      => 'class-shortcode.php::make()',
				'params'      => array(
					array( 'name' => 'id', 'description' => 'Post ID. Defaults to the current post.' ),
				),
				'example'     => '[make id="post_id"]',
				'description' => 'Vehicle make.',
			),
			array(
				'name'        => 'model',
				'source'      => 'class-shortcode.php::model()',
				'params'      => array(
					array( 'name' => 'id', 'description' => 'Post ID. Defaults to the current post.' ),
				),
				'example'     => '[model id="post_id"]',
				'description' => 'Vehicle model.',
			),
			array(
				'name'        => 'trim',
				'source'      => 'class-shortcode.php::trim()',
				'params'      => array(
					array( 'name' => 'id', 'description' => 'Post ID. Defaults to the current post.' ),
				),
				'example'     => '[trim id="post_id"]',
				'description' => 'Vehicle trim.',
			),
			array(
				'name'        => 'lease_payment',
				'source'      => 'class-shortcode.php::lease_payment()',
				'params'      => array(
					array( 'name' => 'id', 'description' => 'Post ID. Defaults to the current post.' ),
				),
				'example'     => '[lease_payment id="post_id"]',
				'description' => 'Monthly lease payment value (ACF field "lease_payment").',
			),
			array(
				'name'        => 'loan_term',
				'source'      => 'class-shortcode.php::loan_term()',
				'params'      => array(
					array( 'name' => 'id', 'description' => 'Post ID. Defaults to the current post.' ),
				),
				'example'     => '[loan_term id="post_id"]',
				'description' => 'Loan term in months (ACF field "loanterm"), wrapped in <span class="js-is-empty">…</span>.',
			),
			array(
				'name'        => 'loan_apr',
				'source'      => 'class-shortcode.php::loan_apr()',
				'params'      => array(
					array( 'name' => 'id', 'description' => 'Post ID. Defaults to the current post.' ),
				),
				'example'     => '[loan_apr id="post_id"]',
				'description' => 'Loan APR (ACF field "loanapr"), wrapped in <span class="js-is-empty">…</span>.',
			),
			array(
				'name'        => 'lease_term',
				'source'      => 'class-shortcode.php::lease_term()',
				'params'      => array(
					array( 'name' => 'id', 'description' => 'Post ID. Defaults to the current post.' ),
				),
				'example'     => '[lease_term id="post_id"]',
				'description' => 'Lease term in months (ACF field "leaseterm"), wrapped in <span class="js-is-empty">…</span>.',
			),
			array(
				'name'        => 'due_at_signing',
				'source'      => 'class-shortcode.php::due_at_signing()',
				'params'      => array(
					array( 'name' => 'id', 'description' => 'Post ID. Defaults to the current post.' ),
				),
				'example'     => '[due_at_signing id="post_id"]',
				'description' => 'Down payment / due-at-signing amount (ACF field "down_payment"), wrapped in <span class="js-is-empty">…</span>.',
			),
			array(
				'name'        => 'total_of_payments',
				'source'      => 'class-shortcode.php::total_of_payments()',
				'params'      => array(
					array( 'name' => 'id', 'description' => 'Post ID. Defaults to the current post.' ),
				),
				'example'     => '[total_of_payments id="post_id"]',
				'description' => 'Total of payments (ACF field "totalofpmts"), wrapped in <span class="js-is-empty">…</span>.',
			),
			array(
				'name'        => 'stock',
				'source'      => 'class-shortcode.php::stock()',
				'params'      => array(
					array( 'name' => 'condition', 'description' => '"new" or "used". Defaults to "new".' ),
					array( 'name' => 'status', 'description' => 'Shorthand for filtering by vehicle status (e.g. "In Stock", "In Transit", "On Order", "Sold" — match is case-insensitive). Mutually exclusive with field/value; wins if both are set.' ),
					array( 'name' => 'field', 'description' => 'Generic field name to filter by instead of status (make, model, year, body_style, drivetrain, fuel_type, …).' ),
					array( 'name' => 'value', 'description' => 'Value to match against "field".' ),
					array( 'name' => 'operator', 'description' => '=, !=, >=, <=, >, <. Defaults to "=".' ),
				),
				'example'     => '[stock condition="new" status="In Transit"]',
				'description' => 'Count of published listings for the given condition (respects Intice Nexus API mode + SOC vehicle filters when API mode is active), optionally narrowed further by vehicle status or any other field. Use two shortcodes side by side to show separate "In Stock" and "In Transit" counts.',
			),
			array(
				'name'        => 'offer_payment',
				'source'      => 'class-shortcode.php::offer_payment()',
				'params'      => array(
					array( 'name' => 'type', 'description' => 'One of: lease-payment, Disclosure_loan, Disclosure_lease, Disclosure_Cash, or omit for the default loan payment.' ),
				),
				'example'     => '[offer_payment type="lease-payment"]',
				'description' => 'Formatted payment/disclosure text for the Offers CPTs, sourced from the "service-offers_flexible_content" payment layout. Plain text only (tags stripped).',
			),
			array(
				'name'        => 'offer_content',
				'source'      => 'class-shortcode.php::offer_content()',
				'params'      => array(
					array( 'name' => 'type', 'description' => 'One of: lease, loan, cash.' ),
				),
				'example'     => '[offer_content type="loan"]',
				'description' => 'Prints the matching disclosure ACF field (disclosure_lease / disclosure_finance / disclosure_cash) for the current Offer post. Plain text only (tags stripped).',
			),
			array(
				'name'        => 'site_url',
				'source'      => 'class-shortcode.php::site_url()',
				'params'      => array(),
				'example'     => '[site_url]',
				'description' => 'Theme assets base URL (…/assets/dist/).',
			),
		);
	}

	/**
	 * Custom admin-defined shortcodes, sourced live from the Theme Options →
	 * Shortcodes repeater (see the `init` hook in inc/theme-functions.php).
	 * Each row registers `[sc_{name}]` returning its configured HTML/text value.
	 *
	 * @return array<int, array{name: string, value: string}>
	 */
	private function get_custom_shortcodes(): array {
		$rows = get_field( 'shortcodes', 'options' );

		if ( empty( $rows ) || ! is_array( $rows ) ) {
			return array();
		}

		$shortcodes = array();

		foreach ( $rows as $row ) {
			$name = strtolower( str_replace( ' ', '_', $row['shortcode_name'] ?? '' ) );

			if ( '' === $name ) {
				continue;
			}

			$shortcodes[] = array(
				'name'  => 'sc_' . $name,
				'value' => (string) ( $row['shortcode_value'] ?? '' ),
			);
		}

		return $shortcodes;
	}
}
