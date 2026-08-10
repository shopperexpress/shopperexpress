<?php
/**
 * SOC Google Reviews Module
 *
 * Settings UI for the Google Business Profile OAuth connection (primary,
 * paginated reviews) and the Places API (New) key (fallback, capped at 5
 * reviews). See App\Components\Base\Google_Business_Reviews.
 *
 * @package Shopperexpress
 */

namespace App\Components\SOC\Modules;

use App\Components\Base\Google_Business_Reviews;
use App\Components\SOC\Contracts\SOC_Module;
use App\Components\SOC\Support\SOC_Cache;

/**
 * Class Google_Reviews
 */
class Google_Reviews implements SOC_Module {

	/**
	 * @return string
	 */
	public function get_slug(): string {
		return 'google-reviews';
	}

	/**
	 * @return string
	 */
	public function get_label(): string {
		return 'Google Reviews';
	}

	/**
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-star-filled';
	}

	/**
	 * @param bool $force_refresh
	 * @return array
	 */
	public function collect( bool $force_refresh = false ): array {
		if ( $force_refresh ) {
			SOC_Cache::forget( $this->get_slug(), 'data' );
		}

		$cached = SOC_Cache::get( $this->get_slug(), 'data' );
		if ( false !== $cached ) {
			return $cached;
		}

		$data = $this->client()->get_settings();

		SOC_Cache::set( $this->get_slug(), 'data', $data, 1 * MINUTE_IN_SECONDS );

		return $data;
	}

	/**
	 * @param array $data
	 * @return void
	 */
	public function render( array $data ): void {
		require get_template_directory() . '/inc/Components/SOC/views/google-reviews.php';
	}

	// ─── Public helpers (called from SOC_Ajax) ─────────────────────────────────

	/**
	 * @param string $client_id
	 * @param string $client_secret
	 * @return void
	 */
	public function save_oauth_client( string $client_id, string $client_secret ): void {
		$this->client()->save_oauth_client( $client_id, $client_secret );
		SOC_Cache::forget( $this->get_slug(), 'data' );
	}

	/**
	 * @param string $account_id
	 * @param string $location_id
	 * @return void
	 */
	public function save_account_location( string $account_id, string $location_id ): void {
		$this->client()->save_account_location( $account_id, $location_id );
		SOC_Cache::forget( $this->get_slug(), 'data' );
	}

	/**
	 * @param string $api_key
	 * @return void
	 */
	public function save_places_api_key( string $api_key ): void {
		$this->client()->save_places_api_key( $api_key );
		SOC_Cache::forget( $this->get_slug(), 'data' );
	}

	/**
	 * @return void
	 */
	public function disconnect(): void {
		$this->client()->disconnect();
		SOC_Cache::forget( $this->get_slug(), 'data' );
	}

	/**
	 * @param string $place_id
	 * @return array
	 */
	public function test_connection( string $place_id ): array {
		return $this->client()->test_connection( $place_id );
	}

	/**
	 * @return array<int, array{id: string, name: string}>|\WP_Error
	 */
	public function list_accounts() {
		return $this->client()->list_accounts();
	}

	/**
	 * @param string $account_id
	 * @return array<int, array{id: string, name: string}>|\WP_Error
	 */
	public function list_locations( string $account_id ) {
		return $this->client()->list_locations( $account_id );
	}

	/**
	 * @return Google_Business_Reviews
	 */
	private function client(): Google_Business_Reviews {
		return new Google_Business_Reviews();
	}
}
