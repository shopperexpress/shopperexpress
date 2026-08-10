<?php
/**
 * OpenAI Ads pixel bootstrap.
 *
 * Loads the OpenAI Ads JavaScript pixel near the top of <head> for dealers
 * that have it enabled and configured, and exposes the config the
 * ASC → OpenAI translation layer (asc-openai-ads.js) reads at runtime.
 *
 * @package Shopperexpress
 */

namespace App\Components\Base;

use App\Components\Theme_Component;

/**
 * Class OpenAI_Ads
 *
 * @package App\Components\Base
 */
class OpenAI_Ads implements Theme_Component {

	/**
	 * ACF field name for the Conversions API key (write-only, encrypted).
	 */
	const CAPI_KEY_FIELD = 'openai_ads_capi_key';

	/**
	 * Client for reading dealer-level OpenAI Ads configuration.
	 *
	 * @var OpenAI_Ads_Client
	 */
	private OpenAI_Ads_Client $client;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->client = new OpenAI_Ads_Client();
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		// Priority 2: right after ASC_Datalayer (priority 1), still near the top of <head>.
		add_action( 'wp_head', array( $this, 'render_pixel_bootstrap' ), 2 );

		// Encrypt the Conversions API key on save; never return it to the admin UI.
		add_filter( 'acf/update_value/name=' . self::CAPI_KEY_FIELD, array( $this, 'encrypt_capi_key_on_save' ), 10, 1 );
		add_filter( 'acf/load_value/name=' . self::CAPI_KEY_FIELD, array( $this, 'blank_capi_key_on_load' ), 10, 1 );
	}

	/**
	 * Whether measurement consent has been granted for this request.
	 *
	 * Defaults to true (no consent-management platform wired up yet). Sites
	 * running a CMP should hook this filter to reflect the visitor's actual
	 * advertising/measurement consent state.
	 *
	 * @return bool
	 */
	private function consent_granted(): bool {
		return (bool) apply_filters( 'shopperexpress_measurement_consent_granted', true );
	}

	/**
	 * Output the OpenAI Ads pixel bootstrap script and runtime config.
	 *
	 * Uses the official oaiq() queueing stub from the OpenAI Ads Measurement
	 * Pixel docs (developers.openai.com/ads/measurement-pixel) verbatim, so
	 * early oaiq('measure', ...) calls made before the SDK finishes loading
	 * are queued rather than lost, and the w.oaiq guard inside the stub
	 * itself prevents double-initialization on repeated wp_head firing.
	 *
	 * @return void
	 */
	public function render_pixel_bootstrap(): void {
		if ( ! $this->client->is_configured() ) {
			return;
		}

		if ( ! $this->consent_granted() ) {
			return;
		}

		$config = array(
			'enabled'  => true,
			'pixelId'  => $this->client->get_pixel_id(),
			'dealerId' => $this->client->get_dealer_id(),
			'debug'    => $this->client->is_debug(),
		);
		?>
		<script>
		window.ascOpenAiAdsConfig = <?php echo wp_json_encode( $config ); ?>;
		(function (w, d, s, u, cfg) {
			if (!cfg || !cfg.enabled) return;
			if (w.oaiq) return;

			var q = function () { q.q.push(arguments); };
			q.q = [];
			w.oaiq = q;

			var js = d.createElement(s);
			js.async = true;
			js.src = u;
			var f = d.getElementsByTagName(s)[0];
			f.parentNode.insertBefore(js, f);

			var initOptions = { pixelId: cfg.pixelId };
			if (cfg.debug) initOptions.debug = true;
			w.oaiq('init', initOptions);
		}(window, document, 'script', 'https://bzrcdn.openai.com/sdk/oaiq.min.js', window.ascOpenAiAdsConfig));
		</script>
		<?php
	}

	/**
	 * Encrypt the Conversions API key before ACF persists it.
	 *
	 * An empty submitted value keeps the previously saved (encrypted) key
	 * so the write-only field can be left blank on subsequent saves.
	 *
	 * @param mixed $value Raw value submitted from the admin form.
	 * @return string
	 */
	public function encrypt_capi_key_on_save( $value ) {
		$value = trim( (string) $value );

		if ( '' === $value ) {
			return get_option( OpenAI_Ads_Client::OPTION_CAPI_KEY, '' );
		}

		$this->client->save_capi_key( $value );

		return get_option( OpenAI_Ads_Client::OPTION_CAPI_KEY, '' );
	}

	/**
	 * Never return the stored key value to the admin UI.
	 *
	 * @return string
	 */
	public function blank_capi_key_on_load(): string {
		return '';
	}
}
