<?php
/**
 * Template for displaying vehicle offer card.
 *
 * @param array $args Array of arguments containing post ID and other data.
 *
 * @package Shopperexpress
 */

$post_id = ! empty( $args['post_id'] ) ? absint( $args['post_id'] ) : get_the_id();
$year    = get_field( 'year', $post_id );
$make    = get_field( 'make', $post_id );
$model   = get_field( 'model', $post_id );
$trim    = get_field( 'trim', $post_id );
$title   = get_field( 'title', $post_id );
$vehicle = get_field( 'vehicle', $post_id );
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
		$payment = get_field( 'payment', $post_id );

		$conditional_description = get_field( 'conditional_description', $post_id );
		$term                    = get_field( 'term', $post_id );

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
		$apr             = get_field( 'apr', $post_id );
		$apr_description = get_field( 'apr_description', $post_id );
		$term            = get_field( 'term', $post_id );

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
		$conditional_description = get_field( 'conditional_description', $post_id );
		$conditional_cash        = get_field( 'conditional_cash', $post_id );

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
$action     = ! empty( $args['action'] ) ? $args['action'] : '';
$aria_label = array( esc_html__( 'Go to', 'shopperexpress' ), esc_html( $year ), esc_html( $make ), esc_html( $model ), esc_html( $trim ), 'page' );
if ( $action ) :
	?>
	<div class="col-sm-6 col-lg-4 col-xxl-3">
	<?php endif; ?>
	<div class="card card-offer">
		<a class="ghost-link" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" aria-label="<?php echo esc_attr( implode( ' ', $aria_label ) ); ?>"></a>
		<div class="card-head">
			<div class="card-head__holder">
				<?php if ( ! empty( $title ) ) : ?>
					<div class="badges-list">
						<span class="card-badge-offer"><?php echo $title; ?></span>
					</div>
				<?php	endif; ?>
				<?php if ( ! empty( $year ) || ! empty( $make ) ) : ?>
					<span class="card-brand"><?php echo $year . ' ' . $make; ?></span>
				<?php endif; ?>
				<button class="compare__btn" type="button" aria-label="<?php esc_attr_e( 'Compare', 'shopperexpress' ); ?>" data-postid="<?php echo esc_attr( $post_id ); ?>" data-posttype="<?php echo esc_attr( get_post_type( $post_id ) ); ?>" data-toggle="tooltip" data-placement="top" title="+Compare">
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
						<path d="M10.2666626,4.0002441h-3.7297363c-.3166504,0-.604126.0916748-.8624878.2749634-.2583008.1833496-.4375.4249878-.5374756.7250366l-2.1000366,6v8c0,.2833252.0958252.520813.2875366.7124634.1916504.1916504.4291382.2875366.7124634.2875366h1c.2833252,0,.520874-.0958862.7125244-.2875366s.2874756-.4291382.2874756-.7124634v-1h4.2297363v4.600647h1.5408936V.7614746h-1.5408936v3.2387695ZM6.8869629,6.0002441h3.3796997v3h-4.4297485l1.0500488-3ZM5.0369263,16.0002441v-5h5.2297363v5h-5.2297363ZM9.0369263,13.5002441c0,.416626-.145813.770813-.4375,1.0625-.291626.291626-.645813.4375-1.0625.4375-.416626,0-.770813-.145874-1.0625-.4375-.291626-.291687-.4375-.645874-.4375-1.0625,0-.416687.145874-.770874.4375-1.0625.291687-.291687.645874-.4375,1.0625-.4375.416687,0,.770874.145813,1.0625.4375.291687.291626.4375.645813.4375,1.0625ZM21.0369263,11.0002441v8c0,.2833252-.0958252.520813-.2874756.7124634s-.4291992.2875366-.7125244.2875366h-1c-.2833252,0-.520813-.0958862-.7124634-.2875366-.1917114-.1916504-.2875366-.4291382-.2875366-.7124634v-1h-4.9998169v-2h5.9998169v-5h-5.9998169v-2h5.1998291l-1.0499878-3h-4.1498413v-2h4.4998169c.3167114,0,.604187.0916748.8624878.2749634.2583618.1833496.4375.4249878.5375366.7250366l2.0999756,6ZM16.5369263,15.0002441c-.416626,0-.770813-.145874-1.0625-.4375-.291626-.291687-.4375-.645874-.4375-1.0625,0-.416687.145874-.770874.4375-1.0625.291687-.291687.645874-.4375,1.0625-.4375.416687,0,.770874.145813,1.0625.4375.291687.291626.4375.645813.4375,1.0625,0,.416626-.145813.770813-.4375,1.0625-.291626.291626-.645813.4375-1.0625.4375Z"></path>
					</svg>
				</button>
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
		<?php if ( ! empty( $vehicle ) ) :
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
