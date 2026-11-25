<?php
/**
 * SOC Overview view
 *
 * @package Shopperexpress
 */

defined( 'ABSPATH' ) || exit;

$leads  = $data['leads'] ?? array();
$alerts = $data['alerts'] ?? array();
$link   = static fn( string $slug ): string => admin_url( 'admin.php?page=soc-' . $slug );
?>

<div id="soc-action-notice" class="soc-notice" role="alert"></div>

<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Needs Attention', 'shopperexpress' ); ?></div>
	<?php if ( empty( $alerts ) ) : ?>
		<p>
			<span class="soc-badge soc-badge--ok"><?php esc_html_e( 'All clear', 'shopperexpress' ); ?></span>
			<?php esc_html_e( 'No issues detected.', 'shopperexpress' ); ?>
		</p>
	<?php else : ?>
		<table class="soc-table">
			<tbody>
				<?php foreach ( $alerts as $alert ) : ?>
					<tr>
						<td style="width:90px">
							<span class="soc-badge <?php echo 'fail' === $alert['level'] ? 'soc-badge--fail' : 'soc-badge--warn'; ?>">
								<?php echo 'fail' === $alert['level'] ? esc_html__( 'Issue', 'shopperexpress' ) : esc_html__( 'Warning', 'shopperexpress' ); ?>
							</span>
						</td>
						<td><?php echo esc_html( $alert['text'] ); ?></td>
						<td style="text-align:right"><a href="<?php echo esc_url( $alert['url'] ); ?>"><?php esc_html_e( 'Open', 'shopperexpress' ); ?> →</a></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>

<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'At a Glance', 'shopperexpress' ); ?></div>
	<div class="soc-grid" style="grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));">
		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Listings Source', 'shopperexpress' ); ?></div>
			<div class="soc-card__value" style="font-size:18px;"><?php echo $data['api_mode'] ? 'Intice API' : 'WordPress'; ?></div>
			<div class="soc-card__sub"><a href="<?php echo esc_url( $link( 'api-settings' ) ); ?>"><?php esc_html_e( 'API Settings', 'shopperexpress' ); ?></a></div>
		</div>
		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Leads (24h)', 'shopperexpress' ); ?></div>
			<div class="soc-card__value"><?php echo esc_html( $leads['total_24h'] ?? 0 ); ?></div>
			<div class="soc-card__sub"><?php echo esc_html( sprintf( '%d failed · %d in 7d', $leads['failed_24h'] ?? 0, $leads['total_7d'] ?? 0 ) ); ?></div>
		</div>
		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Lead Delivery', 'shopperexpress' ); ?></div>
			<div class="soc-card__value" style="font-size:18px;"><?php echo esc_html( strtoupper( (string) $data['delivery_method'] ) ); ?></div>
			<div class="soc-card__sub"><a href="<?php echo esc_url( $link( 'lead-delivery' ) ); ?>"><?php esc_html_e( 'Lead Delivery', 'shopperexpress' ); ?></a></div>
		</div>
		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Cron Events', 'shopperexpress' ); ?></div>
			<div class="soc-card__value"><?php echo esc_html( $data['cron_total'] ); ?></div>
			<div class="soc-card__sub"><?php echo esc_html( sprintf( '%d overdue', $data['cron_overdue'] ) ); ?></div>
		</div>
		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Nexus Cache', 'shopperexpress' ); ?></div>
			<div class="soc-card__value" style="font-size:18px;"><?php echo $data['cache_enabled'] ? esc_html__( 'On', 'shopperexpress' ) : esc_html__( 'Off', 'shopperexpress' ); ?></div>
			<div class="soc-card__sub"><a href="<?php echo esc_url( $link( 'cache-manager' ) ); ?>"><?php esc_html_e( 'Cache', 'shopperexpress' ); ?></a></div>
		</div>
		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Environment', 'shopperexpress' ); ?></div>
			<div class="soc-card__value" style="font-size:16px;"><?php echo esc_html( 'PHP ' . $data['php_version'] ); ?></div>
			<div class="soc-card__sub"><?php echo esc_html( 'WP ' . $data['wp_version'] . ' · MySQL ' . $data['mysql_version'] ); ?></div>
		</div>
	</div>
</div>

<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Quick Links', 'shopperexpress' ); ?></div>
	<p>
		<?php
		$quick_links = array(
			'developer-tools' => 'VIN Checker',
			'cache-manager'   => 'Cache',
			'import-monitor'  => 'Import Monitor',
			'lead-delivery'   => 'Lead Delivery',
			'system-status'   => 'System Status',
		);
		foreach ( $quick_links as $slug => $label ) :
			?>
			<a class="button" href="<?php echo esc_url( $link( $slug ) ); ?>"><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</p>
</div>
