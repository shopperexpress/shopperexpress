<?php
/**
 * SOC AI VDP Log — log table partial.
 *
 * Required in scope: $logs (array from AI_Vdp_Log::fetch_logs()).
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
	$cls = 'success' === $status ? 'soc-badge--ok' : 'soc-badge--fail';
	return '<span class="soc-badge ' . esc_attr( $cls ) . '">' . esc_html( ucfirst( $status ) ) . '</span>';
};
?>

<?php if ( empty( $rows ) ) : ?>
	<p class="soc-lead-empty"><?php esc_html_e( 'No AI VDP generation records found.', 'shopperexpress' ); ?></p>
<?php else : ?>

<table class="soc-table">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Date', 'shopperexpress' ); ?></th>
			<th><?php esc_html_e( 'Vehicle', 'shopperexpress' ); ?></th>
			<th><?php esc_html_e( 'VIN', 'shopperexpress' ); ?></th>
			<th><?php esc_html_e( 'Post Type', 'shopperexpress' ); ?></th>
			<th><?php esc_html_e( 'Trigger', 'shopperexpress' ); ?></th>
			<th><?php esc_html_e( 'Status', 'shopperexpress' ); ?></th>
			<th><?php esc_html_e( 'Reason', 'shopperexpress' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $rows as $row ) : ?>
			<tr>
				<td><?php echo esc_html( $row['logged_at'] ?? '' ); ?></td>
				<td>
					<?php
					$post_id = (int) ( $row['post_id'] ?? 0 );
					$label   = $row['vehicle'] ?? '';
					if ( $post_id && get_post( $post_id ) ) {
						printf(
							'<a href="%s" target="_blank">%s</a>',
							esc_url( get_edit_post_link( $post_id ) ),
							esc_html( $label ?: ( '#' . $post_id ) )
						);
					} else {
						echo esc_html( $label ?: '—' );
					}
					?>
				</td>
				<td><code><?php echo esc_html( $row['vin'] ?? '' ); ?></code></td>
				<td><?php echo esc_html( $row['post_type'] ?? '' ); ?></td>
				<td><?php echo esc_html( $row['trigger_source'] ?? '' ); ?></td>
				<td><?php echo $status_badge( $row['status'] ?? 'error' ); // phpcs:ignore ?></td>
				<td><?php echo esc_html( $row['reason'] ?? '' ) ?: '—'; ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<!-- Pagination -->
<?php if ( $pages > 1 ) : ?>
<div class="soc-lead-pagination">
	<button type="button"
		class="button soc-ai-vdp-page-btn"
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
		class="button soc-ai-vdp-page-btn"
		data-page="<?php echo min( $pages, $page + 1 ); ?>"
		<?php disabled( $page >= $pages ); ?>>
		<?php esc_html_e( 'Next', 'shopperexpress' ); ?> &raquo;
	</button>
</div>
<?php endif; ?>

<?php endif; ?>
