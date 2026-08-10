<?php
/**
 * SOC API Settings view
 *
 * @package Shopperexpress
 */

defined( 'ABSPATH' ) || exit;

$mode_enabled  = ! empty( $data['api_mode_enabled'] );
$cache_enabled = isset( $data['cache_enabled'] ) ? (bool) $data['cache_enabled'] : true;
?>

<div id="soc-action-notice" class="soc-notice" role="alert"></div>

<div class="soc-subtabs">
	<button type="button" class="soc-subtabs__btn soc-subtabs__btn--active" data-subtab-btn="connection">
		<?php esc_html_e( 'Connection & Cache', 'shopperexpress' ); ?>
	</button>
	<button type="button" class="soc-subtabs__btn" data-subtab-btn="fields">
		<?php esc_html_e( 'ACF Fields Reference', 'shopperexpress' ); ?>
	</button>
	<button type="button" class="soc-subtabs__btn" data-subtab-btn="filters">
		<?php esc_html_e( 'Filters', 'shopperexpress' ); ?>
	</button>
</div>

<div data-subtab-panel="connection">

<!-- 1. Global mode toggle -->
<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Listings Data Source', 'shopperexpress' ); ?></div>

	<div class="soc-api-mode-card <?php echo $mode_enabled ? 'soc-api-mode-card--api' : 'soc-api-mode-card--wp'; ?>">
		<div class="soc-api-mode-card__status">
			<span class="soc-api-mode-indicator <?php echo $mode_enabled ? 'soc-api-mode-indicator--on' : ''; ?>"></span>
			<strong id="soc-api-mode-label">
				<?php echo $mode_enabled ? esc_html__( 'API Mode (Intice)', 'shopperexpress' ) : esc_html__( 'WordPress Mode', 'shopperexpress' ); ?>
			</strong>
			<span class="soc-badge <?php echo $mode_enabled ? 'soc-badge--ok' : 'soc-badge--neutral'; ?>" id="soc-api-mode-badge">
				<?php echo $mode_enabled ? esc_html__( 'ACTIVE', 'shopperexpress' ) : esc_html__( 'INACTIVE', 'shopperexpress' ); ?>
			</span>
		</div>

		<p class="soc-api-mode-card__desc">
			<?php if ( $mode_enabled ) : ?>
				<?php esc_html_e( 'Listings, Used listings for SRP and VDP pages pull data from Intice Nexus. WordPress posts are used as stubs only.', 'shopperexpress' ); ?>
			<?php else : ?>
				<?php esc_html_e( 'Listings, Used listings for SRP and VDP pages use standard WordPress posts with ACF fields. Intice Nexus is not used for front-end rendering.', 'shopperexpress' ); ?>
			<?php endif; ?>
		</p>

		<label class="soc-toggle" title="<?php esc_attr_e( 'Toggle API mode', 'shopperexpress' ); ?>">
			<input
				type="checkbox"
				id="soc-api-mode-toggle"
				<?php checked( $mode_enabled ); ?>
			/>
			<span class="soc-toggle__slider"></span>
		</label>
	</div>
</div>

<!-- 2. Credentials -->
<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Intice API Credentials', 'shopperexpress' ); ?></div>

	<table class="soc-table" style="max-width:640px;">
		<tr>
			<td style="width:140px;"><strong><?php esc_html_e( 'API URL', 'shopperexpress' ); ?></strong></td>
			<td>
				<input
					type="url"
					id="soc-intice-api-url"
					class="regular-text"
					value="<?php echo esc_attr( $data['api_url'] ?? '' ); ?>"
					placeholder="https://intice.local"
				/>
			</td>
		</tr>
		<tr>
			<td><strong><?php esc_html_e( 'API Key', 'shopperexpress' ); ?></strong></td>
			<td>
				<?php if ( $data['api_key_set'] ) : ?>
					<code class="soc-masked-key"><?php echo esc_html( $data['api_key_masked'] ); ?></code>
					<button type="button" id="soc-api-key-edit" class="button button-small" style="margin-left:8px;">
						<?php esc_html_e( 'Change', 'shopperexpress' ); ?>
					</button>
					<input
						type="password"
						id="soc-intice-api-key"
						class="regular-text"
						placeholder="<?php esc_attr_e( 'Enter new key…', 'shopperexpress' ); ?>"
						style="display:none;margin-top:6px;"
					/>
				<?php else : ?>
					<input
						type="password"
						id="soc-intice-api-key"
						class="regular-text"
						placeholder="<?php esc_attr_e( 'Paste API key…', 'shopperexpress' ); ?>"
					/>
				<?php endif; ?>
			</td>
		</tr>
	</table>

	<div class="soc-action-bar" style="margin-top:12px;">
		<button type="button" id="soc-save-api-credentials" class="button button-primary">
			<?php esc_html_e( 'Save Credentials', 'shopperexpress' ); ?>
		</button>
		<button type="button" id="soc-test-intice-api" class="button" style="margin-left:8px;">
			<?php esc_html_e( 'Test Connection', 'shopperexpress' ); ?>
		</button>
		<span id="soc-connection-result" style="margin-left:12px;font-size:13px;"></span>
	</div>
</div>

<!-- 3. Status summary -->
<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Configuration Status', 'shopperexpress' ); ?></div>
	<table class="soc-table" style="max-width:480px;">
		<tr>
			<td><?php esc_html_e( 'API Mode', 'shopperexpress' ); ?></td>
			<td>
				<?php if ( $mode_enabled ) : ?>
					<span class="soc-badge soc-badge--ok"><?php esc_html_e( 'Enabled', 'shopperexpress' ); ?></span>
				<?php else : ?>
					<span class="soc-badge soc-badge--neutral"><?php esc_html_e( 'Disabled', 'shopperexpress' ); ?></span>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td><?php esc_html_e( 'API URL', 'shopperexpress' ); ?></td>
			<td>
				<?php if ( ! empty( $data['api_url'] ) ) : ?>
					<span class="soc-badge soc-badge--ok"><?php esc_html_e( 'Set', 'shopperexpress' ); ?></span>
					<code style="margin-left:6px;"><?php echo esc_html( $data['api_url'] ); ?></code>
				<?php else : ?>
					<span class="soc-badge soc-badge--fail"><?php esc_html_e( 'Not set', 'shopperexpress' ); ?></span>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td><?php esc_html_e( 'API Key', 'shopperexpress' ); ?></td>
			<td>
				<?php if ( $data['api_key_set'] ) : ?>
					<span class="soc-badge soc-badge--ok"><?php esc_html_e( 'Set', 'shopperexpress' ); ?></span>
				<?php else : ?>
					<span class="soc-badge soc-badge--fail"><?php esc_html_e( 'Not set', 'shopperexpress' ); ?></span>
				<?php endif; ?>
			</td>
		</tr>
	</table>
</div>

<?php if ( $mode_enabled ) : ?>

<!-- 4. Intice Nexus Cache -->
<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Intice Nexus Cache', 'shopperexpress' ); ?></div>

	<?php if ( wp_using_ext_object_cache() ) : ?>
	<p style="margin:0 0 12px;padding:10px 14px;background:#e5f5fa;border-left:4px solid #2271b1;border-radius:4px;font-size:13px;">
		<?php esc_html_e( 'A persistent object cache (Redis/Memcached) is active on this server. Cache reads/writes go through it instead of the database — status and flush below account for this automatically.', 'shopperexpress' ); ?>
	</p>
	<?php endif; ?>

	<!-- Cache enable / disable toggle -->
	<div class="soc-api-mode-card <?php echo $cache_enabled ? 'soc-api-mode-card--api' : 'soc-api-mode-card--wp'; ?>" style="margin-bottom:16px;">
		<div class="soc-api-mode-card__status">
			<span class="soc-api-mode-indicator <?php echo $cache_enabled ? 'soc-api-mode-indicator--on' : ''; ?>" id="soc-cache-indicator"></span>
			<strong id="soc-cache-label">
				<?php echo $cache_enabled ? esc_html__( 'Cache Enabled', 'shopperexpress' ) : esc_html__( 'Cache Disabled', 'shopperexpress' ); ?>
			</strong>
			<span class="soc-badge <?php echo $cache_enabled ? 'soc-badge--ok' : 'soc-badge--neutral'; ?>" id="soc-cache-badge">
				<?php echo $cache_enabled ? esc_html__( 'ON', 'shopperexpress' ) : esc_html__( 'OFF', 'shopperexpress' ); ?>
			</span>
		</div>
		<p class="soc-api-mode-card__desc">
			<?php if ( $cache_enabled ) : ?>
				<?php esc_html_e( 'API responses are cached in WordPress transients. Old cache is served while a fresh one is being generated.', 'shopperexpress' ); ?>
			<?php else : ?>
				<?php esc_html_e( 'Caching is disabled. Every request fetches data directly from the Intice Nexus API.', 'shopperexpress' ); ?>
			<?php endif; ?>
		</p>
		<label class="soc-toggle" title="<?php esc_attr_e( 'Toggle cache', 'shopperexpress' ); ?>">
			<input
				type="checkbox"
				id="soc-intice-cache-toggle"
				<?php checked( $cache_enabled ); ?>
			/>
			<span class="soc-toggle__slider"></span>
		</label>
	</div>

	<?php if ( ! empty( $data['api_cache'] ) ) : ?>
	<table class="soc-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Cache Group', 'shopperexpress' ); ?></th>
				<th><?php esc_html_e( 'Entries', 'shopperexpress' ); ?></th>
				<th><?php esc_html_e( 'Status', 'shopperexpress' ); ?></th>
				<th><?php esc_html_e( 'Expires', 'shopperexpress' ); ?></th>
				<th><?php esc_html_e( 'Default TTL', 'shopperexpress' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'shopperexpress' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			$status_map = array(
				'valid'   => array( 'class' => 'soc-badge--ok',      'label' => 'Valid' ),
				'stale'   => array( 'class' => 'soc-badge--warn',    'label' => 'Stale' ),
				'expired' => array( 'class' => 'soc-badge--fail',    'label' => 'Expired' ),
				'missing' => array( 'class' => 'soc-badge--neutral', 'label' => 'Empty' ),
			);

			$group_keys = array( 'vehicles', 'vehicle', 'meta', 'new', 'used', 'new-custom', 'used-custom' );

			foreach ( $data['api_cache'] as $i => $row ) :
				$st    = $row['status'] ?? 'missing';
				$badge = $status_map[ $st ] ?? $status_map['missing'];
				$group = $group_keys[ $i ] ?? '';
				?>
				<tr>
					<td><strong><?php echo esc_html( $row['label'] ); ?></strong></td>
					<td><?php echo esc_html( $row['count'] ); ?></td>
					<td>
						<span class="soc-badge <?php echo esc_attr( $badge['class'] ); ?>">
							<?php echo esc_html( $badge['label'] ); ?>
						</span>
						<?php if ( $st === 'stale' ) : ?>
							<small style="margin-left:4px;color:#856404;"><?php esc_html_e( 'regen pending…', 'shopperexpress' ); ?></small>
						<?php endif; ?>
					</td>
					<td><?php echo $row['expires_at'] ? esc_html( $row['expires_at'] ) : '—'; ?></td>
					<td><code><?php echo esc_html( $row['ttl_label'] ); ?></code></td>
					<td>
						<button
							class="button button-small soc-flush-api-cache-group"
							data-group="<?php echo esc_attr( $group ); ?>"
							<?php echo $row['count'] === 0 ? 'disabled' : ''; ?>
						>
							<?php esc_html_e( 'Flush', 'shopperexpress' ); ?>
						</button>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<div class="soc-action-bar" style="margin-top:12px;">
		<button type="button" id="soc-flush-all-api-cache" class="button button-primary">
			<?php esc_html_e( 'Flush All Intice Cache', 'shopperexpress' ); ?>
		</button>
	</div>
	<?php endif; ?>
</div>

<?php endif; ?>

</div><!-- [data-subtab-panel="connection"] -->

<div data-subtab-panel="fields" style="display:none">
	<?php require __DIR__ . '/api-settings-fields.php'; ?>
</div>

<div data-subtab-panel="filters" style="display:none">
	<?php require __DIR__ . '/api-settings-filters.php'; ?>
</div>

<p class="soc-footer-note">
	<em><?php printf( esc_html__( 'Last collected: %s', 'shopperexpress' ), esc_html( $data['collected_at'] ?? 'N/A' ) ); ?></em>
</p>
