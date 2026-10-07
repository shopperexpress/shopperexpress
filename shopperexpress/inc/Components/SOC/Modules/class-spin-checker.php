<?php
/**
 * SOC 360° Spin Checker Module
 *
 * Lets a dealer/developer test whether 360° spin data exists for a given VIN,
 * against every supported spin provider, without having to open a live VDP.
 * Mirrors the client-side checks performed by SpinPopup
 * (assets/src/js/static/app.js) but runs server-side via wp_remote_*.
 *
 * @package Shopperexpress
 */

namespace App\Components\SOC\Modules;

use App\Components\SOC\Contracts\SOC_Module;

/**
 * Class Spin_Checker
 */
class Spin_Checker implements SOC_Module {

	/**
	 * All providers this checker knows how to test.
	 *
	 * @var string[]
	 */
	private const PROVIDERS = array( 'impel', 'autoexact', 'lesa', 'dealerimage', 'autoport' );

	/**
	 * Get the unique slug for this module.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return 'spin-checker';
	}

	/**
	 * Get the human-readable label for the module.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return '360° Spin Checker';
	}

	/**
	 * Get the Dashicon class name for this module.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-image-rotate';
	}

	/**
	 * Collect data needed to render the panel.
	 *
	 * @param bool $force_refresh Unused — settings are always read live.
	 * @return array
	 */
	public function collect( bool $force_refresh = false ): array {
		return array(
			'active_provider' => get_field( 'spin_data_provider', 'option' ) ?: '',
			'has_api_key'     => (bool) get_field( 'spin_data_api_key', 'option' ),
			'has_client_id'   => (bool) get_field( 'spin_data_id', 'option' ),
		);
	}

	/**
	 * Render the module view.
	 *
	 * @param array $data Collected data.
	 * @return void
	 */
	public function render( array $data ): void {
		require get_template_directory() . '/inc/Components/SOC/views/spin-checker.php';
	}

	// ─── Spin provider checks (called by SOC_Ajax) ────────────────────────────

	/**
	 * Check 360° spin availability for a VIN across every supported provider.
	 *
	 * @param string $vin Validated 17-char VIN (uppercase).
	 * @return array{provider: string, results: array<int, array>}
	 */
	public function check_vin( string $vin ): array {
		$results = array();

		foreach ( self::PROVIDERS as $provider ) {
			$results[] = $this->check_provider( $provider, $vin );
		}

		return array(
			'vin'     => $vin,
			'results' => $results,
		);
	}

	/**
	 * Run the availability check for a single provider.
	 *
	 * @param string $provider Provider key.
	 * @param string $vin      VIN to check.
	 * @return array
	 */
	private function check_provider( string $provider, string $vin ): array {
		switch ( $provider ) {
			case 'impel':
				return $this->check_impel( $vin );
			case 'autoexact':
				return $this->check_url_provider(
					$provider,
					sprintf(
						'https://s3.amazonaws.com/photos.autoexact.com/photos/%s/%s_data.json',
						substr( $vin, 0, 9 ),
						$vin
					)
				);
			case 'lesa':
				return $this->check_url_provider(
					$provider,
					'https://player1.lesautomotive.com/?mode=vdp&vin=' . rawurlencode( $vin ) . '&full_size=1'
				);
			case 'dealerimage':
				return $this->check_dealerimage( $vin );
			case 'autoport':
				return $this->check_autoport( $vin );
			default:
				return $this->result( $provider, false, 'Unknown provider.', '' );
		}
	}

	/**
	 * Impel (spincar.com) — requires the dealer API key + client ID configured
	 * in Theme Options (spin_data_api_key / spin_data_id). Success = response
	 * JSON contains a non-empty `url`.
	 *
	 * @param string $vin VIN to check.
	 * @return array
	 */
	private function check_impel( string $vin ): array {
		$api_key   = get_field( 'spin_data_api_key', 'option' );
		$client_id = get_field( 'spin_data_id', 'option' );

		if ( ! $api_key || ! $client_id ) {
			return $this->result( 'impel', false, 'Not configured (missing API key or Client ID in Theme Options).', '' );
		}

		$auth = hash( 'sha512', $api_key . $client_id . $vin );
		$url  = sprintf(
			'https://wa-detection-api.spincar.com/?auth=%s&cid=%s&vin=%s',
			rawurlencode( $auth ),
			rawurlencode( $client_id ),
			rawurlencode( $vin )
		);

		$response = wp_remote_get( $url, array( 'timeout' => 10 ) );

		if ( is_wp_error( $response ) ) {
			return $this->result( 'impel', false, $response->get_error_message(), $url );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! empty( $body['url'] ) ) {
			return $this->result( 'impel', true, 'Spin data found.', $url, $code );
		}

		return $this->result( 'impel', false, 'No spin data for this VIN.', $url, $code );
	}

	/**
	 * DealerImage (dealerimagepro.com) — success is any non-error JSON response
	 * from the v4 vdpdata endpoint.
	 *
	 * @param string $vin VIN to check.
	 * @return array
	 */
	private function check_dealerimage( string $vin ): array {
		$dealer_id = get_field( 'spin_data_id', 'option' );

		if ( ! $dealer_id ) {
			return $this->result( 'dealerimage', false, 'Not configured (missing Client/Dealer ID in Theme Options).', '' );
		}

		$url = 'https://photon360.dealerimagepro.com/v4/vdpdata';

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 10,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode(
					array(
						'dealer'     => (string) $dealer_id,
						'vin'        => $vin,
						'viewer'     => 'slider',
						'activeView' => 'images',
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $this->result( 'dealerimage', false, $response->get_error_message(), $url );
		}

		$code = wp_remote_retrieve_response_code( $response );

		if ( $code >= 200 && $code < 300 ) {
			return $this->result( 'dealerimage', true, 'Spin data found.', $url, $code );
		}

		return $this->result( 'dealerimage', false, 'No spin data for this VIN.', $url, $code );
	}

	/**
	 * Autoport (dealerimagepro.com sandbox) — currently ships in the theme JS
	 * with a hardcoded placeholder bearer token, so this is expected to fail
	 * until a real token/ID is wired up; still run it so the real HTTP
	 * response is visible for diagnostics.
	 *
	 * @param string $vin VIN to check.
	 * @return array
	 */
	private function check_autoport( string $vin ): array {
		$url = 'https://dev-api.dealerimagepro.com/sandbox/assets';

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 10,
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer YOUR_ACCESS_TOKEN',
				),
				'body'    => wp_json_encode(
					array(
						'autoport_id' => 37,
						'vin'         => $vin,
						'limit'       => 50,
						'offset'      => 0,
						'image_type'  => 'webp',
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $this->result( 'autoport', false, $response->get_error_message(), $url );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 200 && $code < 300 && ! empty( $body['data'][0]['insta360'] ) ) {
			return $this->result( 'autoport', true, 'Spin data found.', $url, $code );
		}

		return $this->result(
			'autoport',
			false,
			'No spin data for this VIN (note: this integration ships with a placeholder access token).',
			$url,
			$code
		);
	}

	/**
	 * Shared "HTTP 404 = no data" check used by AutoExact and LESA.
	 *
	 * @param string $provider Provider key.
	 * @param string $url      URL to probe.
	 * @return array
	 */
	private function check_url_provider( string $provider, string $url ): array {
		$response = wp_remote_get( $url, array( 'timeout' => 10 ) );

		if ( is_wp_error( $response ) ) {
			return $this->result( $provider, false, $response->get_error_message(), $url );
		}

		$code = wp_remote_retrieve_response_code( $response );

		if ( 404 === $code ) {
			return $this->result( $provider, false, 'No spin data for this VIN (404).', $url, $code );
		}

		return $this->result( $provider, true, 'Spin data found.', $url, $code );
	}

	/**
	 * Build a uniform result row.
	 *
	 * @param string   $provider Provider key.
	 * @param bool     $found    Whether spin data was found.
	 * @param string   $message  Human-readable message.
	 * @param string   $url      URL that was checked.
	 * @param int|null $code     HTTP response code, if any.
	 * @return array
	 */
	private function result( string $provider, bool $found, string $message, string $url, ?int $code = null ): array {
		return array(
			'provider' => $provider,
			'found'    => $found,
			'message'  => $message,
			'url'      => $url,
			'code'     => $code,
		);
	}
}
