<?php
/**
 * Template for displaying vehicle offer card.
 *
 * @param array $args Array of arguments containing post ID and other data.
 */

$post_id = ! empty( $args['post_id'] ) ? absint( $args['post_id'] ) : get_the_id();
$year    = wps_get_term( $post_id, 'year', '', 'field' );
$make    = wps_get_term( $post_id, 'make', '', 'field' );
$model   = wps_get_term( $post_id, 'model', '', 'field' );
$trim    = wps_get_term( $post_id, 'trim', '', 'field' );
$title   = wps_get_term( $post_id, 'title', '', 'field' );
$vehicle = wps_get_term( $post_id, 'vehicle', '', 'field' );
$text_1  = $text_2 = '';

$data_id = get_posts(
	array(
		'post_type'      => 'append-data',
		'meta_query'     => array(
			array(
				'key'     => 'year',
				'value'   => $year,
				'compare' => '=',
			),
			array(
				'key'     => 'make',
				'value'   => $make,
				'compare' => '=',
			),
			array(
				'key'     => 'model',
				'value'   => $model,
				'compare' => '=',
			),
		),
		'posts_per_page' => 1,
		'fields'         => 'ids',
	)
);

$data_id = ! empty( $data_id ) ? $data_id[0] : '';

switch ( get_post_type( $post_id ) ) {
	case 'lease-offers':
		$payment = wps_get_term( $post_id, 'payment', '', 'field' );

		$conditional_description = wps_get_term( $post_id, 'conditional_description', '', 'field' );
		$term                    = wps_get_term( $post_id, 'term', '', 'field' );

		if ( ! empty( $payment ) && ! empty( $term ) ) {
			$text_1 = sprintf(
				esc_html__( '$%1$s/mo for %2$d mos.', 'shopperexpress' ),
				number_format_i18n( $payment, 0 ),
				absint( $term )
			);
		}

		if ( ! empty( $conditional_description ) ) {
			$text_2 = esc_html( $conditional_description );
		}

		break;

	case 'finance-offers':
		$apr             = wps_get_term( $post_id, 'apr', '', 'field' );
		$apr_description = wps_get_term( $post_id, 'apr_description', '', 'field' );
		$term            = wps_get_term( $post_id, 'term', '', 'field' );

		if ( ( ! empty( $apr ) || $apr >= 0 ) && ! empty( $term ) ) {
			$text_1 = sprintf(
				esc_html__( '%1$s%% APR for %2$d mos.', 'shopperexpress' ),
				number_format_i18n( (float) $apr, 2 ),
				absint( $term )
			);
		}

		if ( ! empty( $apr_description ) ) {
			$text_2 = esc_html( $apr_description );
		}

		break;

	case 'conditional-offers':
		$conditional_description = wps_get_term( $post_id, 'conditional_description', '', 'field' );
		$conditional_cash        = wps_get_term( $post_id, 'conditional_cash', '', 'field' );

		if ( ! empty( $conditional_cash ) ) {
			$text_1 = sprintf(
				esc_html__( '$%1$s %2$s', 'shopperexpress' ),
				esc_html( $conditional_cash ),
				esc_html__( 'Special Offer', 'shopperexpress' )
			);
		}

		if ( ! empty( $conditional_description ) ) {
			$text_2 = esc_html( $conditional_description );
		}

		break;
}
$action = ! empty( $args['action'] ) ? $args['action'] : '';
if ( $action ) :
	?>
	<div class="col-sm-6 col-lg-4 col-xxl-3">
	<?php endif; ?>
	<div class="card card-offer">
		<div class="card-head">
			<div class="card-head__holder">
				<?php if ( ! empty( $year ) || ! empty( $make ) ) : ?>
					<span class="card-brand"><?php echo $year . ' ' . $make; ?></span>
				<?php endif; ?>
				<?php
				if ( shortcode_exists( 'favorite_button' ) ) {
					echo do_shortcode( '[favorite_button post_id="' . $post_id . '"]' );}
				?>
			</div>
			<?php
			if ( ! empty( $model ) || ! empty( $trim ) ) :
				?>
				<strong class="card-model"><?php echo $model . ' ' . $trim; ?></strong>
			<?php endif; ?>
		</div>
		<?php
		get_template_part(
			'template-parts/gallery',
			null,
			array(
				'post_type' => get_post_type( $post_id ),
				'post_id'   => $post_id,
				'data_id'   => $data_id,
			)
		);
		?>
		<?php if ( ! empty( $title ) ) : ?>
			<div class="badges-list">
				<span class="card-badge-offer"><?php echo $title; ?></span>
			</div>
			<?php
		endif;
		if ( ! empty( $vehicle ) ) :
			?>
			<span class="card-offer-subtitle"><?php echo $vehicle; ?></span>
			<?php
		endif;
		if ( ! empty( $text_1 ) ) :
			?>
			<strong class="card-offer-price">
				<?php echo $text_1; ?>
			</strong>
			<?php
		endif;
		if ( ! empty( $text_2 ) ) :
			?>
			<span class="card-offer-desc"><?php echo $text_2; ?></span>
		<?php endif; ?>
		<?php
		$ConversionBlock = new ConversionBlock( 0, get_post_type( $post_id ), $post_id );
		echo $ConversionBlock->render();
		?>
	</div>
	<?php if ( $action ) : ?>
	</div>
<?php endif; ?>
<!-- Details Modal -->
<div class="modal fade" id="detailModal-offers-<?php echo $post_id; ?>" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h3 class="modal-title"><?php esc_html_e( 'DETAILS', 'shopperexpress' ); ?></h3>
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
						<?php echo wp_kses_post( get_field( 'custom_content', $post_id ) ); ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
