<?php
/**
 * SOC Google Reviews view
 *
 * @package Shopperexpress
 */

defined( 'ABSPATH' ) || exit;

$client_id     = $data['client_id'] ?? '';
$client_secret = $data['client_secret'] ?? '';
$has_secret    = ! empty( $data['has_secret'] );
$account_id    = $data['account_id'] ?? '';
$location_id   = $data['location_id'] ?? '';
$place_id      = $data['place_id'] ?? '';
$is_connected  = ! empty( $data['is_connected'] );
$can_connect   = '' !== $client_id && $has_secret;
$oauth_url     = $data['oauth_start_url'] ?? '';
$redirect_uri  = $data['redirect_uri'] ?? '';
$places_set    = ! empty( $data['places_key_set'] );
$places_masked = $data['places_key_masked'] ?? '';
?>

<div id="soc-action-notice" class="soc-notice" role="alert"></div>

<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Why this exists', 'shopperexpress' ); ?></div>
	<p class="soc-api-mode-card__desc">
		<?php esc_html_e( 'The Google Places API only returns up to 5 reviews with no pagination — that is a Google platform limit, not something this integration can work around. Connecting a Business Profile account below unlocks real pagination (up to 50 reviews per page) via the Business Profile API. Without a connection, the Places API key is used as a fallback (capped at 5 reviews per place).', 'shopperexpress' ); ?>
	</p>
</div>

<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Business Profile — OAuth Client', 'shopperexpress' ); ?></div>

	<table class="soc-table" style="max-width:640px;">
		<tr>
			<td style="width:180px;"><strong><?php esc_html_e( 'Client ID', 'shopperexpress' ); ?></strong></td>
			<td><input type="text" id="soc-gr-client-id" class="regular-text" value="<?php echo esc_attr( $client_id ); ?>" /></td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'Client Secret', 'shopperexpress' ); ?></strong></td>
			<td>
				<input
					type="text"
					id="soc-gr-client-secret"
					class="regular-text"
					value="<?php echo esc_attr( $client_secret ); ?>"
					placeholder="<?php esc_attr_e( 'Paste client secret…', 'shopperexpress' ); ?>"
				/>
			</td>
		</tr>
	</table>

	<div class="soc-action-bar" style="margin-top:12px;">
		<button type="button" id="soc-gr-save" class="button button-primary">
			<?php esc_html_e( 'Save Client', 'shopperexpress' ); ?>
		</button>
	</div>
</div>

<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Business Profile — Connection', 'shopperexpress' ); ?></div>

	<?php if ( $is_connected ) : ?>
		<p>
			<span class="soc-badge soc-badge--ok"><?php esc_html_e( 'Connected', 'shopperexpress' ); ?></span>
			<?php if ( $account_id && $location_id ) : ?>
				<span class="description" style="margin-left:8px;">
					<?php echo esc_html( $account_id . ' / ' . $location_id ); ?>
					<?php if ( $place_id ) : ?>
						(<?php esc_html_e( 'Place ID', 'shopperexpress' ); ?>: <?php echo esc_html( $place_id ); ?>)
					<?php else : ?>
						&mdash; <?php esc_html_e( 'Place ID could not be resolved; "Review us on Google" / "Show More Reviews" links will be missing.', 'shopperexpress' ); ?>
					<?php endif; ?>
				</span>
			<?php endif; ?>
		</p>

		<table class="soc-table" style="max-width:640px;">
			<tr>
				<td style="width:180px;"><strong><?php esc_html_e( 'Account', 'shopperexpress' ); ?></strong></td>
				<td>
					<div class="soc-gr-inline-field">
						<select id="soc-gr-account-select" class="regular-text">
							<?php if ( $account_id ) : ?>
								<option value="<?php echo esc_attr( $account_id ); ?>" selected><?php echo esc_html( $account_id ); ?></option>
							<?php endif; ?>
						</select>
						<button type="button" id="soc-gr-load-accounts" class="button button-small">
							<?php esc_html_e( 'Load Accounts', 'shopperexpress' ); ?>
						</button>
					</div>
				</td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'Location', 'shopperexpress' ); ?></strong></td>
				<td>
					<div class="soc-gr-inline-field">
						<select id="soc-gr-location-select" class="regular-text">
							<?php if ( $location_id ) : ?>
								<option value="<?php echo esc_attr( $location_id ); ?>" selected><?php echo esc_html( $location_id ); ?></option>
							<?php endif; ?>
						</select>
						<button type="button" id="soc-gr-load-locations" class="button button-small">
							<?php esc_html_e( 'Load Locations', 'shopperexpress' ); ?>
						</button>
					</div>
				</td>
			</tr>
		</table>

		<div class="soc-action-bar" style="margin-top:12px;">
			<button type="button" id="soc-gr-save-account" class="button button-primary">
				<?php esc_html_e( 'Save Account/Location', 'shopperexpress' ); ?>
			</button>
			<button type="button" id="soc-gr-disconnect" class="button" style="margin-left:8px;">
				<?php esc_html_e( 'Disconnect', 'shopperexpress' ); ?>
			</button>
		</div>

		<p class="description" style="margin-top:8px;">
			<?php esc_html_e( 'Click "Load Accounts" to fetch the Business Profile accounts reachable by this connection, pick one, then "Load Locations" to fetch that account\'s locations — no need to type IDs by hand.', 'shopperexpress' ); ?>
		</p>
	<?php elseif ( $can_connect ) : ?>
		<p>
			<a class="button button-primary" href="<?php echo esc_url( $oauth_url ); ?>">
				<?php esc_html_e( 'Connect with Google', 'shopperexpress' ); ?>
			</a>
		</p>
		<p class="description">
			<?php
			printf(
				/* translators: %s: redirect URI to whitelist in Google Cloud Console. */
				esc_html__( 'Before connecting, add this exact URL as an Authorized redirect URI on the OAuth client in Google Cloud Console: %s', 'shopperexpress' ),
				'<code>' . esc_html( $redirect_uri ) . '</code>'
			);
			?>
		</p>
	<?php else : ?>
		<p class="description"><?php esc_html_e( 'Save the Client ID and Client Secret above first.', 'shopperexpress' ); ?></p>
	<?php endif; ?>
</div>

<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Places API Key (fallback)', 'shopperexpress' ); ?></div>

	<table class="soc-table" style="max-width:640px;">
		<tr>
			<td style="width:180px;"><strong><?php esc_html_e( 'API Key', 'shopperexpress' ); ?></strong></td>
			<td>
				<?php if ( $places_set ) : ?>
					<code class="soc-masked-key"><?php echo esc_html( $places_masked ); ?></code>
					<button type="button" id="soc-gr-places-key-edit" class="button button-small" style="margin-left:8px;">
						<?php esc_html_e( 'Change', 'shopperexpress' ); ?>
					</button>
					<input
						type="password"
						id="soc-gr-places-api-key"
						class="regular-text"
						placeholder="<?php esc_attr_e( 'Enter new key…', 'shopperexpress' ); ?>"
						style="display:none;margin-top:6px;"
					/>
				<?php else : ?>
					<input
						type="password"
						id="soc-gr-places-api-key"
						class="regular-text"
						placeholder="<?php esc_attr_e( 'Paste Places API key…', 'shopperexpress' ); ?>"
					/>
				<?php endif; ?>
			</td>
		</tr>
	</table>

	<p class="description">
		<?php esc_html_e( 'Used only when no Business Profile connection is configured above. Must have the "Places API (New)" restriction and be restricted to this server\'s IP — this call is server-side only.', 'shopperexpress' ); ?>
	</p>

	<div class="soc-action-bar" style="margin-top:12px;">
		<button type="button" id="soc-gr-save-places-key" class="button button-primary">
			<?php esc_html_e( 'Save Key', 'shopperexpress' ); ?>
		</button>
	</div>
</div>

<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Test Connection', 'shopperexpress' ); ?></div>

	<table class="soc-table" style="max-width:640px;">
		<tr>
			<td style="width:180px;"><strong><?php esc_html_e( 'Place ID', 'shopperexpress' ); ?></strong></td>
			<td>
				<input
					type="text"
					id="soc-gr-test-place-id"
					class="regular-text"
					value="<?php echo esc_attr( $place_id ); ?>"
					placeholder="ChIJpyiwa4Zw44kRBQSGWKv4wgA"
				/>
				<p class="description"><?php esc_html_e( 'Leave as-is to test against the resolved location above, or override to test a different Place ID.', 'shopperexpress' ); ?></p>
			</td>
		</tr>
	</table>

	<div class="soc-action-bar" style="margin-top:12px;">
		<button type="button" id="soc-gr-test" class="button">
			<?php esc_html_e( 'Test Connection', 'shopperexpress' ); ?>
		</button>
		<span id="soc-gr-test-result" style="margin-left:12px;font-size:13px;"></span>
	</div>
</div>
