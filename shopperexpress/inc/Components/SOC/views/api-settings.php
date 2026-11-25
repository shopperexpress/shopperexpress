<?php
/**
 * SOC API Settings view
 *
 * @package Shopperexpress
 */

defined( 'ABSPATH' ) || exit;

$mode_enabled         = ! empty( $data['api_mode_enabled'] );
$team_enabled         = ! empty( $data['team_mode_enabled'] );
$cache_enabled        = isset( $data['cache_enabled'] ) ? (bool) $data['cache_enabled'] : true;
$llm_inventory_enabled = \App\Components\Base\Llm_Inventory::is_enabled();
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

	<div class="soc-toggle-list">
		<?php
		$soc_row = array(
			'id'       => 'soc-api-mode-toggle',
			'title'    => __( 'API Mode', 'shopperexpress' ),
			'on'       => $mode_enabled,
			'note_on'  => __( 'Source: Intice Nexus', 'shopperexpress' ),
			'note_off' => __( 'Source: WordPress posts', 'shopperexpress' ),
			'desc_on'  => __( 'Listings, Used listings for SRP and VDP pages pull data from Intice Nexus. WordPress posts are used as stubs only.', 'shopperexpress' ),
			'desc_off' => __( 'Listings, Used listings for SRP and VDP pages use standard WordPress posts with ACF fields. Intice Nexus is not used for front-end rendering.', 'shopperexpress' ),
		);
		require __DIR__ . '/partials/toggle-row.php';

		$soc_row = array(
			'id'       => 'soc-team-mode-toggle',
			'title'    => __( 'Team Members', 'shopperexpress' ),
			'on'       => $team_enabled,
			'note_on'  => __( 'Source: Intice', 'shopperexpress' ),
			'note_off' => __( 'Source: ACF block', 'shopperexpress' ),
			'desc_on'  => __( 'The "Team Section (Intice)" block pulls its members live from Intice Nexus (Content → Team Members). Independent of the API Mode toggle.', 'shopperexpress' ),
			'desc_off' => __( 'The "Team Section (Intice)" block is inactive and renders nothing. Use the regular "Team Section" ACF block for manually-entered members instead.', 'shopperexpress' ),
		);
		require __DIR__ . '/partials/toggle-row.php';

		$soc_row = array(
			'id'       => 'soc-llm-inventory-toggle',
			'title'    => __( 'AI Inventory Page', 'shopperexpress' ),
			'on'       => $llm_inventory_enabled,
			'note_on'  => __( '/llm/inventory/ is live', 'shopperexpress' ),
			'note_off' => __( 'Page disabled', 'shopperexpress' ),
			'desc_on'  => __( 'Serves a server-rendered, JS-free /llm/inventory/ page listing current new/used/certified inventory for AI assistants and search crawlers (incl. OAI-SearchBot), plus /llms.txt and a child sitemap referenced from the main Yoast sitemap.', 'shopperexpress' ),
		);
		$soc_row['desc_off'] = $soc_row['desc_on'];
		require __DIR__ . '/partials/toggle-row.php';
		?>
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

<?php if ( $mode_enabled || $team_enabled ) : ?>

<!-- 4. Intice Nexus Cache -->
<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Intice Nexus Cache', 'shopperexpress' ); ?></div>

	<?php if ( wp_using_ext_object_cache() ) : ?>
	<p style="margin:0 0 12px;padding:10px 14px;background:#e5f5fa;border-left:4px solid #2271b1;border-radius:4px;font-size:13px;">
		<?php esc_html_e( 'A persistent object cache (Redis/Memcached) is active on this server. Cache reads/writes go through it instead of the database — status and flush below account for this automatically.', 'shopperexpress' ); ?>
	</p>
	<?php endif; ?>

	<div class="soc-toggle-list" style="margin-bottom:16px;">
		<?php
		$soc_row = array(
			'id'       => 'soc-intice-cache-toggle',
			'title'    => __( 'Response Cache', 'shopperexpress' ),
			'on'       => $cache_enabled,
			'note_on'  => __( 'Transients, stale copy served while rebuilding', 'shopperexpress' ),
			'note_off' => __( 'Every request hits Intice Nexus', 'shopperexpress' ),
			'desc_on'  => __( 'API responses are cached in WordPress transients. Old cache is served while a fresh one is being generated.', 'shopperexpress' ),
			'desc_off' => __( 'Caching is disabled. Every request fetches data directly from the Intice Nexus API.', 'shopperexpress' ),
		);
		require __DIR__ . '/partials/toggle-row.php';
		?>
	</div>

	<p style="margin:0 0 16px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
		<label for="soc-vdp-ttl"><strong><?php esc_html_e( 'Vehicle page (VDP) cache lifetime', 'shopperexpress' ); ?></strong></label>
		<select id="soc-vdp-ttl">
			<?php foreach ( \App\Components\SOC\Modules\Api_Settings::get_vdp_ttl_options() as $soc_ttl => $soc_ttl_label ) : ?>
				<option value="<?php echo esc_attr( $soc_ttl ); ?>" <?php selected( (int) ( $data['vdp_ttl'] ?? 900 ), $soc_ttl ); ?>><?php echo esc_html( $soc_ttl_label ); ?></option>
			<?php endforeach; ?>
		</select>
		<span style="color:#666;font-size:12px;"><?php esc_html_e( 'How long a vehicle page is served from cache before it is re-fetched. Applies to entries written from now on; rebuilds and the Nexus refresh webhook keep it current regardless.', 'shopperexpress' ); ?></span>
	</p>

	<?php if ( ! empty( $data['api_cache'] ) ) : ?>
	<table class="soc-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Cache Group', 'shopperexpress' ); ?></th>
				<th><?php esc_html_e( 'Status', 'shopperexpress' ); ?></th>
				<th><?php esc_html_e( 'Cleared By', 'shopperexpress' ); ?></th>
				<th><?php esc_html_e( 'Cached At', 'shopperexpress' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'shopperexpress' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			$status_map = array(
				'valid'   => array( 'class' => 'soc-badge--ok',      'label' => 'Valid' ),
				'pending' => array( 'class' => 'soc-badge--warn',    'label' => 'Pending' ),
				'missing' => array( 'class' => 'soc-badge--neutral', 'label' => 'Empty' ),
			);

			$group_keys = array( 'vehicles', 'vehicle-new', 'vehicle-used', 'meta', 'team', 'new', 'used', 'new-custom', 'used-custom', 'feed' );

			// Plain-language explanation of each row, shown when its info icon is clicked.
			// Keyed by position — must stay in the same order as collect_api_cache()'s $groups.
			$group_info = array(
				__( 'Caches the raw Nexus API response for vehicle list requests (GET /vehicles) — the data other lookups build on. Filled whenever any page requests a list of vehicles.', 'shopperexpress' ),
				__( 'Caches the raw Nexus response for each NEW vehicle\'s detail page (GET /vehicles/{vin}), one entry per VIN. Filled automatically by every cache rebuild and by the Nexus per-VIN refresh webhook. "Build Cache" fills every VIN from one fresh vehicle-list request; "Flush" drops the live copies (old ones keep serving) and refills them the same way.', 'shopperexpress' ),
				__( 'Same as New Vehicle Pages, but for USED vehicles.', 'shopperexpress' ),
				__( 'Caches GET /meta — the filter reference data (makes, models, year/price ranges, conditions) used to render the SRP filter UI.', 'shopperexpress' ),
				__( 'Caches GET /team-members — the Team Section (Intice) block\'s member list and categories. Nexus pings this site to flush just this group the moment a member or category is saved/reordered — vehicle/SRP caches are never touched by a team change.', 'shopperexpress' ),
				__( 'Caches the fully assembled New Vehicles (listings) results — after filters, trim/drivetrain/photo resolution and default sorting are applied. This is what the New Vehicles SRP actually renders.', 'shopperexpress' ),
				__( 'Same as New Listings, but for Used Vehicles (used-listings).', 'shopperexpress' ),
				__( 'Same as New Listings, but for the "Custom sort" mode (Theme Options → Custom sort) — kept separate because the sort order differs from the default.', 'shopperexpress' ),
				__( 'Same as Used Listings, but for the "Custom sort" mode.', 'shopperexpress' ),
				__( 'Caches the assembled "vehicles-feed" output — the full vehicle list with all term/field data, used by feed/sitemap-style integrations rather than the paginated SRP.', 'shopperexpress' ),
			);

			foreach ( $data['api_cache'] as $i => $row ) :
				$st    = $row['status'] ?? 'missing';
				$badge = $status_map[ $st ] ?? $status_map['missing'];
				$group = $group_keys[ $i ] ?? '';
				$info  = $group_info[ $i ] ?? '';
				?>
				<tr>
					<td>
						<strong><?php echo esc_html( $row['label'] ); ?></strong>
						<?php if ( $info ) : ?>
							<button
								type="button"
								class="soc-cache-info-toggle"
								aria-expanded="false"
								aria-label="<?php esc_attr_e( 'What does this cache group do?', 'shopperexpress' ); ?>"
							>&#9432;</button>
						<?php endif; ?>
					</td>
					<td>
						<span class="soc-badge <?php echo esc_attr( $badge['class'] ); ?>">
							<?php echo esc_html( $badge['label'] ); ?>
						</span>
						<?php if ( ! empty( $row['vdp_detail'] ) ) : ?>
							<br /><small class="soc-vdp-detail" style="color:#666;"><?php echo esc_html( $row['vdp_detail'] ); ?></small>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( ! empty( $row['cleared_by'] ) ) : ?>
							<?php echo esc_html( $row['cleared_by'] ); ?>
							<?php if ( ! empty( $row['cleared_at'] ) ) : ?>
								<br /><small style="color:#666;"><?php echo esc_html( $row['cleared_at'] ); ?></small>
							<?php endif; ?>
						<?php else : ?>
							—
						<?php endif; ?>
					</td>
					<td><?php echo $row['cached_at'] ? esc_html( $row['cached_at'] ) : '—'; ?></td>
					<td>
						<?php if ( ! empty( $row['vdp'] ) ) : ?>
							<?php // VDP rows: Build fills the missing VINs, Flush drops live copies and refills. ?>
							<?php if ( 'missing' === $st || ! empty( $row['vdp_gap'] ) ) : ?>
								<button
									class="button button-small button-primary soc-warm-api-cache-group"
									data-group="<?php echo esc_attr( $group ); ?>"
								>
									<?php esc_html_e( 'Build Cache', 'shopperexpress' ); ?>
								</button>
							<?php endif; ?>
							<button
								class="button button-small soc-flush-api-cache-group"
								data-group="<?php echo esc_attr( $group ); ?>"
								<?php echo 'missing' === $st ? 'disabled' : ''; ?>
							>
								<?php esc_html_e( 'Flush', 'shopperexpress' ); ?>
							</button>
						<?php elseif ( $st === 'missing' ) : ?>
							<button
								class="button button-small button-primary soc-warm-api-cache-group"
								data-group="<?php echo esc_attr( $group ); ?>"
							>
								<?php esc_html_e( 'Build Cache', 'shopperexpress' ); ?>
							</button>
						<?php else : ?>
							<button
								class="button button-small soc-flush-api-cache-group"
								data-group="<?php echo esc_attr( $group ); ?>"
								<?php echo $st === 'missing' ? 'disabled' : ''; ?>
							>
								<?php esc_html_e( 'Flush', 'shopperexpress' ); ?>
							</button>
						<?php endif; ?>
					</td>
				</tr>
				<?php if ( $info ) : ?>
				<tr class="soc-cache-info-row" style="display:none;">
					<td colspan="5" style="background:#f6f7f7;color:#3c434a;font-size:13px;padding:10px 14px;">
						<?php echo esc_html( $info ); ?>
					</td>
				</tr>
				<?php endif; ?>
			<?php endforeach; ?>
		</tbody>
	</table>

	<div class="soc-action-bar" style="margin-top:12px;">
		<button type="button" id="soc-flush-all-api-cache" class="button button-primary">
			<?php esc_html_e( 'Flush All Intice Cache', 'shopperexpress' ); ?>
		</button>
	</div>
	<p style="margin:8px 0 0;color:#666;font-size:12px;">
		<?php esc_html_e( 'Also clears any active page-cache plugin (WP Rocket, LiteSpeed, W3 Total Cache, etc.) and OPcache. If a persistent object cache (Redis/Memcached) is active, this flushes it entirely — not just Intice data — since most backends have no way to clear a single plugin\'s keys.', 'shopperexpress' ); ?>
	</p>
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
