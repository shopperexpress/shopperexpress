<?php
/**
 * SOC Log Viewer
 *
 * @package Shopperexpress
 */

defined( 'ABSPATH' ) || exit; ?>

<div id="soc-action-notice" class="soc-notice" role="alert"></div>

<!-- 1. Log Type Tabs -->
<?php if ( ! empty( $data['log_types'] ) ) : ?>
	<div class="soc-action-bar" style="border-bottom: 1px solid #c3c4c7; margin-bottom: 16px; padding-bottom: 0; gap: 0;">
		<?php
		foreach ( $data['log_types'] as $type_slug => $type_label ) :
			$is_active = ( $type_slug === ( $data['selected_type'] ?? '' ) );
			$tab_url   = add_query_arg( 'log_type', $type_slug );
			?>
			<a href="<?php echo esc_url( $tab_url ); ?>"
				style="display:inline-block; padding:8px 16px; text-decoration:none; font-size:13px; border-bottom: 3px solid <?php echo $is_active ? '#2271b1' : 'transparent'; ?>; color:<?php echo $is_active ? '#2271b1' : 'inherit'; ?>; margin-bottom:-1px;">
				<?php echo esc_html( $type_label ); ?>
			</a>
		<?php endforeach; ?>
	</div>
<?php endif; ?>

<!-- 2. Log File Info -->
<?php if ( ! empty( $data['active_logs'] ) ) : ?>
	<div class="soc-section">
		<div class="soc-section__title"><?php esc_html_e( 'Log File Info', 'shopperexpress' ); ?></div>
		<div class="soc-grid" style="grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));">
			<div class="soc-card">
				<div class="soc-card__label"><?php esc_html_e( 'File Size', 'shopperexpress' ); ?></div>
				<div class="soc-card__value" style="font-size:18px;">
					<?php echo esc_html( number_format( (float) ( $data['active_logs']['size_kb'] ?? 0 ), 2 ) ); ?> KB
				</div>
			</div>
			<div class="soc-card">
				<div class="soc-card__label"><?php esc_html_e( 'Lines', 'shopperexpress' ); ?></div>
				<div class="soc-card__value" style="font-size:18px;">
					<?php echo esc_html( $data['active_logs']['line_count'] ?? 0 ); ?>
				</div>
			</div>
		</div>
	</div>
<?php endif; ?>

<!-- 3. Action Bar -->
<div class="soc-action-bar">
	<button class="button soc-export-log"
			data-log-type="<?php echo esc_attr( $data['selected_type'] ?? '' ); ?>">
		<?php esc_html_e( 'Export Log', 'shopperexpress' ); ?>
	</button>
	<button class="button soc-clear-log"
			data-log-type="<?php echo esc_attr( $data['selected_type'] ?? '' ); ?>"
			style="color:#d63638;">
		<?php esc_html_e( 'Clear Log', 'shopperexpress' ); ?>
	</button>
</div>

<!-- Search -->
<input type="search"
		class="soc-log-search"
		placeholder="<?php esc_attr_e( 'Filter log entries…', 'shopperexpress' ); ?>" />

<!-- 4. Log Entries -->
<?php if ( ! empty( $data['entries'] ) ) : ?>
	<pre class="soc-log-output">
	<?php
	foreach ( (array) $data['entries'] as $line ) {
		echo '<span class="soc-log-line">' . esc_html( $line ) . '</span>' . "\n";
	}
	?>
	</pre>
<?php else : ?>
	<div style="padding:20px; text-align:center; color:#8c8f94;">
		<?php esc_html_e( 'No log entries found.', 'shopperexpress' ); ?>
	</div>
<?php endif; ?>

<p class="soc-footer-note" style="margin-top:12px;">
	<em>
		<?php
		printf(
			/* translators: %s: collection timestamp */
			esc_html__( 'Last collected: %s', 'shopperexpress' ),
			esc_html( $data['collected_at'] ?? 'N/A' )
		);
		?>
	</em>
</p>
