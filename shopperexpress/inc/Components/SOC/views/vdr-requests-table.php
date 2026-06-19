<?php
/**
 * SOC VDR Requests — log table partial.
 *
 * Required in scope: $logs (array from VDR_Requests::fetch_logs()).
 *
 * @package Shopperexpress
 */

defined( 'ABSPATH' ) || exit;

$rows     = $logs['rows']     ?? array();
$total    = (int) ( $logs['total']    ?? 0 );
$per_page = (int) ( $logs['per_page'] ?? 25 );
$page     = (int) ( $logs['page']     ?? 1 );
$pages    = $total > 0 ? (int) ceil( $total / $per_page ) : 1;

$result_badge = static function ( string $result ): string {
	$cls = 'success' === $result ? 'soc-badge--ok' : 'soc-badge--fail';
	return '<span class="soc-badge ' . esc_attr( $cls ) . '">' . esc_html( ucfirst( $result ) ) . '</span>';
};
?>

<?php if ( empty( $rows ) ) : ?>
	<p class="soc-lead-empty"><?php esc_html_e( 'No VDR records found.', 'shopperexpress' ); ?></p>
<?php else : ?>

<table class="soc-table">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Date', 'shopperexpress' ); ?></th>
			<th><?php esc_html_e( 'VIN', 'shopperexpress' ); ?></th>
			<th><?php esc_html_e( 'Dealer', 'shopperexpress' ); ?></th>
			<th><?php esc_html_e( 'Site', 'shopperexpress' ); ?></th>
			<th><?php esc_html_e( 'Result', 'shopperexpress' ); ?></th>
			<th><?php esc_html_e( 'HTTP', 'shopperexpress' ); ?></th>
			<th><?php esc_html_e( 'Cache', 'shopperexpress' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $rows as $row ) : ?>
			<tr>
				<td><?php echo esc_html( $row['requested_at'] ?? '' ); ?></td>
				<td><code><?php echo esc_html( $row['vin'] ?? '' ); ?></code></td>
				<td><?php echo esc_html( $row['dealer_name'] ?? '' ); ?></td>
				<td><?php echo esc_html( $row['site_name'] ?? '' ); ?></td>
				<td><?php echo $result_badge( $row['result'] ?? 'error' ); // phpcs:ignore ?></td>
				<td><?php echo (int) ( $row['http_code'] ?? 0 ) ?: '—'; ?></td>
				<td>
					<?php if ( ! empty( $row['from_cache'] ) ) : ?>
						<span class="soc-badge soc-badge--warn"><?php esc_html_e( 'Cached', 'shopperexpress' ); ?></span>
					<?php else : ?>
						<span class="soc-badge soc-badge--neutral"><?php esc_html_e( 'Fresh', 'shopperexpress' ); ?></span>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<!-- Pagination -->
<?php if ( $pages > 1 ) : ?>
<div class="soc-lead-pagination">
	<button type="button"
		class="button soc-vdr-page-btn"
		data-page="<?php echo max( 1, $page - 1 ); ?>"
		<?php disabled( $page <= 1 ); ?>>
		&laquo; <?php esc_html_e( 'Prev', 'shopperexpress' ); ?>
	</button>
	<span class="soc-lead-page-info">
		<?php
		printf(
			/* translators: 1: current page, 2: total pages, 3: total rows */
			esc_html__( 'Page %1$d of %2$d (%3$d total)', 'shopperexpress' ),
			$page,
			$pages,
			$total
		);
		?>
	</span>
	<button type="button"
		class="button soc-vdr-page-btn"
		data-page="<?php echo min( $pages, $page + 1 ); ?>"
		<?php disabled( $page >= $pages ); ?>>
		<?php esc_html_e( 'Next', 'shopperexpress' ); ?> &raquo;
	</button>
</div>
<?php endif; ?>

<?php endif; ?>
