<?php
/**
 * Template part for displaying disclosure button
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Shopperexpress
 */

$rand_id    = uniqid();
$disclosure = ! empty( $args['disclosure'] ) ? $args['disclosure'] : '';
?>
<button class="btn-disclosure btn-fill" data-toggle="modal" data-target="#detailModal-<?php echo esc_attr( $rand_id ); ?>">
	<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
		<path
			d="M480-280q17 0 28.5-11.5T520-320v-160q0-17-11.5-28.5T480-520q-17 0-28.5 11.5T440-480v160q0 17 11.5 28.5T480-280Zm0-320q17 0 28.5-11.5T520-640q0-17-11.5-28.5T480-680q-17 0-28.5 11.5T440-640q0 17 11.5 28.5T480-600Zm0 520q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"></path>
	</svg>
	Disclosure
</button>
<?php
add_action(
	'wp_footer',
	function () use ( $rand_id, $disclosure ) {
		?>
	<div class="modal fade" id="detailModal-<?php echo esc_attr( $rand_id ); ?>" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h3 class="modal-title">DETAILS</h3>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000">
							<path
								d="M480-424 284-228q-11 11-28 11t-28-11q-11-11-11-28t11-28l196-196-196-196q-11-11-11-28t11-28q11-11 28-11t28 11l196 196 196-196q11-11 28-11t28 11q11 11 11 28t-11 28L536-480l196 196q11 11 11 28t-11 28q-11 11-28 11t-28-11L480-424Z" />
						</svg>
					</button>
				</div>
				<div class="modal-body-wrap">
					<div class="modal-body">
						<div class="content-holder">
							<?php echo wp_kses_post( $disclosure ); ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
		<?php
	}
);
?>
