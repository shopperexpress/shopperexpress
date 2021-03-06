<?php $post_id = get_the_id(); ?>
<div class="col-sm-6 col-lg-4 col-xxl-3">
	<div class="card">
		<div class="card-body">
			<div class="card-head">
				<span class="card-brand"><?php echo wps_get_term($post_id, 'year'); ?> <?php echo wps_get_term($post_id, 'make'); ?></span>
				<strong class="card-model"><?php echo wps_get_term($post_id, 'model'); ?> <?php echo wps_get_term($post_id, 'trim'); ?></strong>
			</div>
			<a href="<?php the_permalink(); ?>">
				<div class="card-img">
					<?php
					$gallery = get_field( 'gallery' );
					if ( !empty( $gallery ) && count($gallery) >= 1 ) {
						$gallery =  isset($gallery[0]) && !empty($gallery[0]['image_url']) ? $gallery[0]['image_url'] : $gallery[1]['image_url'];
					}else{
						$gallery = null;
					}

					if ( $gallery ):
							echo '<img src="'.$gallery.'" alt="image">';
					else:
						$def_img = get_field('default_image', 'option') ? wp_get_attachment_image_url(get_field('default_image', 'option'), 'full') : get_stylesheet_directory_uri().'/images/image-placeholder.png';
						?>
						<img src="<?php echo $def_img; ?>" alt="" class="img-fluid">
					<?php endif; ?>
				</div>
			</a>
			<?php if ( function_exists('card_detail') ): ?>
				<dl class="card-detail"><?php echo card_detail($post_id); ?></dl>
			<?php endif; ?>
			<dl class="card-detail">
				<dt><?php _e('VIN','shopperexpress'); ?>:</dt>
				<dd><?php echo wps_get_term($post_id, 'vin-number'); ?></dd>
				<dt><?php _e('STOCK','shopperexpress'); ?>:</dt>
				<dd><?php echo wps_get_term($post_id, 'stock-number'); ?></dd>
			</dl>
			<?php 
			switch (wps_get_term($post_id, 'condition')) {
				case 'Slightly Used':
				$text = __('Market Value','shopperexpress');
				break;
				case 'Used':
				$text = __('Market Value','shopperexpress');
				break;
				default:
				$text = __('MSRP','shopperexpress');
				break;
			}
			$price = get_field( 'price' );
			$msrp = get_field( 'original_price' ) ? get_field( 'original_price' ) : $price;
			$_price_text = $msrp > 0 ? '<span>'.$text.'</span> <span class="st">$'.number_format($msrp).'</span>' : __('Contact for Price', 'shopperexpress');
			?>
			<strong class="card-price"><?php echo $_price_text; ?></strong>
			<?php
			if( is_user_logged_in() && $price > 0 ) :
				?>
			<strong class="card-price price-current"><span><?php _e('PRICE','shopperexpress'); ?></span> <?php echo '$'.number_format($price); ?></strong>
			<?php endif; ?>
			<span  class='intice_bFrame' data-vdp-vin='<?php echo wps_get_term($post_id, 'vin-number'); ?>'>&nbsp;</span>
		</div>
	</div>
</div>