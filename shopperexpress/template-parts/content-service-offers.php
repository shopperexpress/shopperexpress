<?php
/**
 * Template for displaying vehicle offer card.
 *
 * @param array $args Array of arguments containing post ID and other data.
 *
 * @package Shopperexpress
 */

$post_id        = ! empty( $args['post_id'] ) ? $args['post_id'] : get_the_id();
$dept           = get_field( 'department', $post_id );
$type           = get_field( 'type', $post_id );
$title          = get_field( 'title', $post_id );
$expirationdate = get_field( 'expirationdate', $post_id );
?>
<div class="col-sm-6 col-lg-4 col-xxl-3">
	<div class="card">
		<div class="card-body">
			<a href="<?php echo esc_url( get_the_permalink( $post_id ) ); ?>">
				<?php if ( $dept || $type ) : ?>
					<div class="card-head">
						<?php if ( $dept ) : ?>
							<span class="card-brand"><?php echo $dept; ?></span>
							<?php
						endif;
						if ( $type ) :
							?>
							<strong class="card-model"><?php echo $type; ?></strong>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<div class="detail-slider-holder">
					<?php
					$image = get_field( 'offerimage', $post_id );
					if ( $image ) :
						?>
						<img src="<?php echo esc_url( $image ); ?>" srcset="<?php echo esc_url( $image ); ?> 2x" alt="image description">
						<?php
					elseif ( function_exists( 'default_image' ) ) :
							echo default_image();
					endif;
					?>
				</div>
				<dl class="card-detail service-offers">
					<dt><?php esc_html_e( 'TITLE:', 'shopperexpress' ); ?></dt>
					<dd><?php echo $title; ?></dd>
					<dt><?php esc_html_e( 'OFFER:', 'shopperexpress' ); ?></dt>
					<dd><?php echo get_field( 'offertext', $post_id ); ?></dd>
					<dt><?php esc_html_e( 'DEPT:', 'shopperexpress' ); ?></dt>
					<dd><?php echo $dept; ?></dd>
					<dt><?php esc_html_e( 'TYPE:', 'shopperexpress' ); ?></dt>
					<dd><?php echo $type; ?></dd>
					<dt><?php esc_html_e( 'EXP. DATE:', 'shopperexpress' ); ?></dt>
					<dd><?php echo $expirationdate; ?></dd>
					<dt><?php esc_html_e( 'ADD\'L INFO:', 'shopperexpress' ); ?></dt>
					<dd><?php echo get_field( 'additioninfo', $post_id ); ?></dd>
				</dl>
			</a>
			<a href="<?php echo get_permalink( $post_id ); ?>" class="btn btn-primary btn-custom btn-block"><?php esc_html_e( 'Offer Details', 'shopperexpress' ); ?></a>
			<?php
			$buttontext = get_field( 'buttontext', $post_id );
			$buttonurl  = get_field( 'buttonurl', $post_id );
			if ( $buttontext && $buttonurl ) :
				?>
				<a href="<?php echo esc_url( $buttonurl ); ?>" target="_blank" class="btn btn-primary btn-custom btn-block"><?php echo $buttontext; ?></a>
				<?php
			endif;
			$loged = ! empty( $args['loged'] ) ? $args['loged'] : '';
			get_template_part(
				'template-parts/unlock',
				'button',
				array(
					'post_id' => $post_id,
					'loged'   => $loged,
				)
			);
			?>
		</div>
	</div>
</div>
