<?php 
$_id = get_the_ID();
$large_button = get_field( 'large_buttons', 'options' );
$hide = get_field( 'hide_header_section', 'options' );
$model = get_the_terms( $_id, 'model');
$make = get_the_terms( $_id, 'make');

if(!in_array( $_id, $hide)){ ?>
<div class="intro text-white" data-theme>
	<?php if ( get_field( 'intro_style', 'options' ) == 1 ): ?>
		<div class="container-fluid">
			<div class="row">
				<div class="col-lg-3 item-col item-left">
					<?php if ( is_singular('listings') && !empty($model) ): ?>
						<p><a href="<?php echo esc_url(add_query_arg('model[]',$model[0]->slug ,home_url('listings/'))); ?>"><?php _e('All','shopperexpress'); ?> <?php echo $model[0]->name; ?> <?php _e('Inventory','shopperexpress'); ?></a></p>
					<?php endif; ?>
					<?php if ( is_singular('offers') && !empty($model) ): ?>
						<p><a href="<?php echo esc_url(home_url('offers/')); ?>"><?php _e('All Offers','shopperexpress'); ?></a></p>
					<?php endif; ?>
				</div>
				<div class="col-lg-6 text-center">
					<div class="text-holder<?php if( is_array($large_button) ) if(in_array($_id, $large_button)) echo 'btns-large'; ?>">
						<?php $pid = get_page_by_title('Special Offers');
						if(is_post_type_archive('offers')){
							the_field('information', $pid->ID);
						}else{
							the_field( 'information' , 'options' ); 
						} ?>
						<h1><?php if( is_single() && !empty($make) && !empty($model) ): echo $make[0]->name . ' ' . $model[0]->name; 
						else:
							if(is_post_type_archive('offers')){
								the_field('title', $pid->ID);
							}else{
								the_field( 'title' , 'options' ); 
							} 
						endif; ?></h1>
					</div>
				</div>
				<?php
				if ( is_user_logged_in() ):
					$user = wp_get_current_user();
					?>
					<div class="col-lg-3 item-col item-right">
						<span class="customer"><?php echo $user->user_email; ?> <strong class="customer-name"><?php echo the_field( 'zip' , 'user_' . $user->ID ); ?></strong></span>
					</div>
				<?php endif; ?>
				<div class="col-12 btn-holder <?php if( is_array($large_button) ) if(in_array($_id, $large_button)) echo 'btns-large'; ?> text-center">
					<?php
					if( $link = get_field( 'link_header', 'options' ) ){ echo $link; }
					if ( $video_id = get_field( 'video_id', 'options' ) ):
						?>
						<span class="wistia_embed wistia_async_<?php echo $video_id; ?> popover=true popoverContent=link" style="display: inline; position: relative;">
							<a class="btn btn-link" href="#"><i class="material-icons"><?php _e('play_circle','shopperexpress'); ?></i> <?php _e('watch video','shopperexpress'); ?></a>
						</span>
					<?php endif; ?>
				</div>
			</div>
			<?php if ( $powered = get_field( 'powered', 'options' ) ): ?>
				<div class="col-12">
					<span class="by"><?php echo $powered; ?></span>
				</div>
			<?php endif;?>
		</div>
	<?php else: ?>
		<div class="container">
			<div class="row">
				<div class="col-md-10 col-lg-9 m-auto text-center">
					<div class="text-holder<?php if(in_array($_id, $large_button)) echo 'btns-large'; ?>">
						<?php the_field( 'information' , 'options' ); ?>
						<h1><?php if( is_single() ): echo $term_data['make'] . ' ' . $term_data['model']; else: the_field( 'title' , 'options' ); endif; ?></h1>
					</div>
				</div>
				<div class="col-12 btn-holder<?php if(in_array($_id, $large_button)) echo ' btns-large';?> text-center">
					<?php
					if( $link = get_field( 'link_header', 'options' ) ){ echo $link; }
					if ( $video_id = get_field( 'video_id', 'options' ) ):
						?>
						<span class="wistia_embed wistia_async_<?php echo $video_id; ?> popover=true popoverAnimateThumbnail=true videoFoam=true popoverContent=link" style="display: inline; position: relative;">
							<a class="btn btn-link" href="#"><i class="material-icons"><?php _e('play_circle','shopperexpress'); ?></i> <?php _e('watch video','shopperexpress'); ?></a>
						</span>
					<?php endif; ?>
				</div>
			</div>
		</div>
	<?php endif ?>
</div>
<?php } ?>