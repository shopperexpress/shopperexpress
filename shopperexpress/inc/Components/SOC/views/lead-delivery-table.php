<?php
/**
 * SOC Lead Delivery — log table partial.
 *
 * Required in scope: $logs (array from Lead_Delivery::fetch_logs()).
 *
 * @package Shopperexpress
 */

defined( 'ABSPATH' ) || exit;

$rows     = $logs['rows']     ?? array();
$total    = (int) ( $logs['total']    ?? 0 );
$per_page = (int) ( $logs['per_page'] ?? 25 );
$page     = (int) ( $logs['page']     ?? 1 );
$pages    = $total > 0 ? (int) ceil( $total / $per_page ) : 1;

$status_badge = static function ( string $status ): string {
	$map = array(
		'success' => 'soc-badge--ok',
		'failed'  => 'soc-badge--fail',
		'pending' => 'soc-badge--warn',
	);
	$cls = $map[ $status ] ?? 'soc-badge--neutral';
	return '<span class="soc-badge ' . esc_attr( $cls ) . '">' . esc_html( ucfirst( $status ) ) . '</span>';
};
?>

<?php if ( empty( $rows ) ) : ?>
	<p class="soc-lead-empty"><?php esc_html_e( 'No lead records found.', 'shopperexpress' ); ?></p>
<?php else : ?>

<table class="soc-table soc-lead-log-table">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Date', 'shopperexpress' ); ?></th>
			<th><?php esc_html_e( 'Site', 'shopperexpress' ); ?></th>
			<th><?php esc_html_e( 'Form', 'shopperexpress' ); ?></th>
			<th><?php esc_html_e( 'Name', 'shopperexpress' ); ?></th>
			<th><?php esc_html_e( 'Email', 'shopperexpress' ); ?></th>
			<th><?php esc_html_e( 'Phone', 'shopperexpress' ); ?></th>
			<th><?php esc_html_e( 'Method', 'shopperexpress' ); ?></th>
			<th><?php esc_html_e( 'Code', 'shopperexpress' ); ?></th>
			<th><?php esc_html_e( 'Status', 'shopperexpress' ); ?></th>
			<th><?php esc_html_e( 'Retries', 'shopperexpress' ); ?></th>
			<th><?php esc_html_e( 'Actions', 'shopperexpress' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $rows as $row ) : ?>
			<tr data-log-id="<?php echo (int) $row['id']; ?>">
				<td><?php echo esc_html( $row['submitted_at'] ?? '' ); ?></td>
				<td><?php echo esc_html( $row['site_name'] ?? '' ); ?></td>
				<td><?php echo esc_html( $row['form_name'] ?? '' ); ?></td>
				<td><?php echo esc_html( trim( ( $row['first_name'] ?? '' ) . ' ' . ( $row['last_name'] ?? '' ) ) ); ?></td>
				<td><?php echo esc_html( $row['email'] ?? '' ); ?></td>
				<td><?php echo esc_html( $row['phone'] ?? '' ); ?></td>
				<td>
					<span class="soc-badge soc-badge--neutral">
						<?php echo esc_html( strtoupper( $row['delivery_method'] ?? 'email' ) ); ?>
					</span>
				</td>
				<td><?php echo (int) ( $row['response_code'] ?? 0 ) ?: '—'; ?></td>
				<td><?php echo $status_badge( $row['status'] ?? 'pending' ); // phpcs:ignore ?></td>
				<td><?php echo (int) ( $row['retry_count'] ?? 0 ); ?></td>
				<td class="soc-lead-actions-cell">
					<?php if ( 'failed' === ( $row['status'] ?? '' ) ) : ?>
						<button type="button"
							class="button button-small soc-lead-retry-btn"
							data-log-id="<?php echo (int) $row['id']; ?>">
							<?php esc_html_e( 'Retry', 'shopperexpress' ); ?>
						</button>
					<?php endif; ?>
					<?php if ( ! empty( $row['error_message'] ) || ! empty( $row['response_body'] ) || ! empty( $row['adfxml_payload'] ) ) : ?>
						<button type="button"
							class="button button-small soc-lead-detail-btn"
							data-log-id="<?php echo (int) $row['id']; ?>"
							data-error="<?php echo esc_attr( $row['error_message'] ?? '' ); ?>"
							data-response="<?php echo esc_attr( $row['response_body'] ?? '' ); ?>"
							data-payload="<?php echo esc_attr( $row['adfxml_payload'] ?? '' ); ?>"
							data-code="<?php echo (int) ( $row['response_code'] ?? 0 ); ?>"
							data-name="<?php echo esc_attr( trim( ( $row['first_name'] ?? '' ) . ' ' . ( $row['last_name'] ?? '' ) ) ); ?>"
							data-time="<?php echo esc_attr( $row['submitted_at'] ?? '' ); ?>">
							<?php esc_html_e( 'Details', 'shopperexpress' ); ?>
						</button>
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
		class="button soc-lead-page-btn"
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
		class="button soc-lead-page-btn"
		data-page="<?php echo min( $pages, $page + 1 ); ?>"
		<?php disabled( $page >= $pages ); ?>>
		<?php esc_html_e( 'Next', 'shopperexpress' ); ?> &raquo;
	</button>
</div>
<?php endif; ?>

<?php endif; ?>
