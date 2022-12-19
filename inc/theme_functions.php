<?php
if(!function_exists("vehicle_slider")){
	function vehicle_slider($title = "Shop other Models", $limit = -1, $sort = null, $listings = null){

		$Automotive_Plugin = Automotive_Plugin();
		$Listing_Template  = Automotive_Plugin_Template();

		$other_options = null;

		$recent_related_vehicle_value = automotive_listing_get_option('recent_related_vehicle_value', true);

		$args = automotive_scroller_args(array(
			'limit'         => $limit,
			'sort'          => $sort,
			'listings'      => $listings,
			'other_options' => $other_options
		));

		$args_new = $args;

		$args_new['meta_key'] = 'condition';
		$args_new['meta_value'] = 'Brand New';
		$args_new['meta_compare']   = '=';


		
		$query_new = new WP_Query( $args_new );

		// allow to customize width
		$other_options['slide-width'] = apply_filters('automotive_listing_scroller_item_width', 167);

		$has_description = ( !empty($title) && !empty($description) );

		ob_start(); ?>
		<section class="shop-section">
			<div class="container-fluid">
				<h2 class="text-center decor-title"><?php echo esc_html( $title ); ?></h2>
				<div class="model-slider">
					<?php
					while ( $query_new->have_posts() ) : $query_new->the_post();
						$Automotive_Listing  = new Automotive_Listing(get_the_id());
						$term_data = $Automotive_Listing->get_listing_term_data()['listing_terms'];
						?>
						<div>
							<a class="model-card" href="<?php the_permalink(); ?>">
								<span class="brand"><?php echo $term_data['year'] ?> <?php echo $term_data['make'] ?></span>
								<strong class="model"><?php echo $term_data['model']; ?></strong>
								<?php the_post_thumbnail('post-thumbnail', ['class' => 'img-fluid']); ?>
							</a>
						</div>
						<?php
						$args['post__not_in'][] = get_the_ID();
					endwhile;
					$query = new WP_Query( $args );
					while ( $query->have_posts() ) : $query->the_post();
						$Automotive_Listing  = new Automotive_Listing(get_the_id());
						$term_data = $Automotive_Listing->get_listing_term_data()['listing_terms'];
						?>
						<div>
							<a class="model-card" href="<?php the_permalink(); ?>">
								<span class="brand"><?php echo $term_data['year'] ?> <?php echo $term_data['make'] ?></span>
								<strong class="model"><?php echo $term_data['model']; ?></strong>
								<?php the_post_thumbnail('post-thumbnail', ['class' => 'img-fluid']); ?>
							</a>
						</div>
						<?php
						$args['post__not_in'][] = get_the_ID();
					endwhile;
					?>
				</div>
			</div>
		</section>
		<?php

		wp_reset_query();

		return ob_get_clean();

	}
}

function wps_get_link($link,$class = null,$before = null, $attr = null){
	if ($link) {

		$title = $link['url'];
		$url = $link['url'];
		$target = null;

		if (isset($link['title']) and !empty($link['title'])) {
			$title = $link['title'];
		}

		if ($target = $link['target']) {
			$target = ' target="'.$target.'" ';
		}

		if (!empty($class)) {
			$class = 'class="'.$class.'" ';
		}

		return '<a href="'.esc_url($url).'" '.$class.$target.$attr.'>'.$before.$title.'</a>';
	}
}

add_action( 'wp_print_styles', function()
{
	if ( is_singular('listings') || is_page_template() == 'archive-listings' ) {
		wp_styles()->add_data( 'style', 'after', '' );
	} 

} );

function card_detail( $post_id = null){
	$terms = [
		'mileage' 		  => __('Mileage','shopperexpress'),
		'engine' 		  => __('Engine','shopperexpress'),
		'transmission'   => __('Transmission','shopperexpress'),
		'drivetrain' 	  => __('Drivetrain','shopperexpress'),
		'exterior-color' => __('Exterior color','shopperexpress'),
		'interior-color' => __('Interior color','shopperexpress'),
		'trim' 			  => __('Trim','shopperexpress')

	];

	foreach ($terms as $key => $value):
		?>
		<dt><?php echo $value; ?>:</dt>
		<dd><?php echo wps_get_term($post_id, $key); ?></dd>
		<?php
	endforeach;
}

function offers_card_detail( $post_id = null){
	$terms = [
		'year' 	  => __('Year','shopperexpress'),
		'make' => __('Make','shopperexpress'),
		'model' => __('Model','shopperexpress'),
		'trim' => __('Trim','shopperexpress')

	];

	foreach ($terms as $key => $value):
		?>
		<dt><?php echo $value; ?>:</dt>
		<dd><?php echo wps_get_term($post_id, $key); ?></dd>
		<?php 
	endforeach;
	if($add_info = get_field('addinfo')){ ?>
		<dt><?php echo _e('Add`l Info', 'shopperexpress'); ?>:</dt>
		<dd><?php echo $add_info; ?></dd>
	<?php }
}

register_nav_menus( array(
	'drop-down' => __( 'Drop-down', 'shopperexpress' ),
	'header' => __( 'Header Navigation', 'shopperexpress' ),
) );


add_image_size( "auto_portfolio_slider", '500', '375', true );

function seoUrl($string) {
	$string = strtolower($string);
	$string = preg_replace("/[^a-z0-9_\s-]/", "", $string);
	$string = preg_replace("/[\s-]+/", " ", $string);
	$string = preg_replace("/[\s_]/", "-", $string);
	return $string;
}

function hexToRgb($hex, $alpha = false) {
	$hex      = str_replace('#', '', $hex);
	$length   = strlen($hex);
	$rgb['r'] = hexdec($length == 6 ? substr($hex, 0, 2) : ($length == 3 ? str_repeat(substr($hex, 0, 1), 2) : 0));
	$rgb['g'] = hexdec($length == 6 ? substr($hex, 2, 2) : ($length == 3 ? str_repeat(substr($hex, 1, 1), 2) : 0));
	$rgb['b'] = hexdec($length == 6 ? substr($hex, 4, 2) : ($length == 3 ? str_repeat(substr($hex, 2, 1), 2) : 0));
	if ( $alpha ) {
		$rgb['a'] = $alpha;
	}
	return implode(',',$rgb);
}

add_action('wp_logout','auto_redirect_after_logout');

function auto_redirect_after_logout(){
	wp_safe_redirect( home_url() );
	exit;
}

add_action( 'wps_update_listing_trigger', 'wps_update_listing_trigger_function' );

function wps_update_listing_trigger_function(){
	if ( $trigger_url = get_field( 'trigger_url', 'options' ) ) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $trigger_url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_exec($ch);
		curl_close($ch);
	}
}

add_action( 'wps_update_listing_processing', 'wps_update_listing_processing_fucntion' );

function wps_update_listing_processing_fucntion(){
	if ( $processing_url = get_field( 'processing_url', 'options' ) ) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $processing_url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_exec($ch);
		curl_close($ch);
	}
}

function wps_get_term( $post, $taxonomy, $field = 'name' ){

	if ( $term = get_the_terms( $post, $taxonomy ) ) {
		$value = $term[0]->name;
	}else{
		$value = null;
	}
	
	return $value;
}

add_action( 'wpcf7_mail_sent', 'wps_wpcf7_mail_sent_function' ); 
function wps_wpcf7_mail_sent_function( $contact_form ) {
	$submission = WPCF7_Submission::get_instance();  
	if ( $submission ) {
		$posted_data = $submission->get_posted_data();
	}

	$first_name = $posted_data['firstName'];
	$last_name = $posted_data['lastName'];
	$email = $posted_data['your-email'];
	$phone = $posted_data['phone'];
	$zip = $posted_data['zip'];
	$comments = $posted_data['message'];

	$message = '<?xml version="1.0" encoding="utf-8"?>
	<?ADF version="1.0"?>
	<adf>
	<prospect>
	<id source="shopperexpress" sequence="1"></id>
	<requestdate>' . date() . '</requestdate>
	<customer>
	<contact primarycontact="1">
	<name part="first">' . $first_name . '</name>
	<name part="last">' . $last_name . '</name>
	<name part="full">' . $first_name . ' ' . $last_name . '</name>
	<email>' . $email . '</email>
	<phone time="day" type="voice">' . $phone . '</phone>
	<address>
	<street line="1"></street>
	<street line="2"></street>
	<city></city>
	<regioncode></regioncode>
	<postalcode>' . $zip . '</postalcode>
	<country></country>
	</address>
	</contact>
	<comments>' . $comments . '</comments>
	</customer>
	<provider>
	<name part="full">intice</name>
	<service>shopperexpress</service>
	<url> http://www.inticeinc.com</url>
	<email>support@inticeinc.com</email>
	<phone>855-747-7770</phone>
	<contact primarycontact="1">
	<name part="full">Intice Inc</name>
	<email>support@inticeinc.com</email>
	<phone time="day" type="voice">855-747-7770</phone>
	<phone time="day" type="fax">888-220-2913</phone>
	<address>
	<street line="1">2660 Cypress Ridge Blvd.</street>
	<street line="2">Suite 103</street>
	<city>Wesley Chapel</city>
	<regioncode>FL</regioncode>
	<postalcode>33544</postalcode>
	<country>USA</country>
	</address>
	</contact>
	</provider>
	</prospect>
	</adf>';

	if ( have_rows( 'email_notification', 'options' ) ) {

		$mail_to = [];

		while ( have_rows( 'email_notification', 'options' ) ) : the_row();
			$mail_to[] = get_sub_field( 'email' );
		endwhile;

		$headers = array(
			'content-type: text/plain',
		);

		wp_mail(implode(', ',$mail_to), "", $message, $headers);
	}
}

add_action( 'pmxi_after_xml_import ', function(){
	$posts = new WP_Query( 
		[
			'post_type'   => 'listings',
			'post_status' => 'publish',
			'numberposts' => -1,
			'meta_query' => [
				[
					'key' => 'sold',
					'value' => 'Yes'
				]
			]
		]
	);
	if ( $posts->have_posts() ) {
		while ( $posts->have_posts() ) {
			$posts->the_post();
			wp_delete_post( get_the_ID());
		}
	}  
}, 10 );

add_filter( 'auto_update_plugin', '__return_true' );
remove_action ('wp_head', 'wp_site_icon', 99);

function wps_site_icon() {
	if ( ! has_site_icon() && ! is_customize_preview() ) {
		return;
	}
	
	if (is_admin() || defined( 'DOING_AJAX' ))
		return;

	$meta_tags = array();
	$icon_32   = get_site_icon_url( 32 );
	if ( empty( $icon_32 ) && is_customize_preview() ) {
		$icon_32 = '/favicon.ico'; // Serve default favicon URL in customizer so element can be updated for preview.
	}

	$meta_tags[] = sprintf( '<link rel="icon" href="%s" type="image/png" />', esc_url( get_site_icon_url() ) );

	if ( $icon_32 ) {
		$meta_tags[] = sprintf( '<link rel="icon" href="%s" sizes="32x32" type="image/png" />', esc_url( $icon_32 ) );
	}
	$icon_192 = get_site_icon_url( 192 );
	if ( $icon_192 ) {
		$meta_tags[] = sprintf( '<link rel="icon" href="%s" sizes="192x192" type="image/png" />', esc_url( $icon_192 ) );
	}
	$icon_180 = get_site_icon_url( 180 );
	if ( $icon_180 ) {
		$meta_tags[] = sprintf( '<link rel="apple-touch-icon" href="%s" type="image/png" />', esc_url( $icon_180 ) );
	}
	$icon_270 = get_site_icon_url( 270 );
	if ( $icon_270 ) {
		$meta_tags[] = sprintf( '<meta name="msapplication-TileImage" content="%s" />', esc_url( $icon_270 ) );
	}

	/**
	 * Filters the site icon meta tags, so plugins can add their own.
	 *
	 * @since 4.3.0
	 *
	 * @param string[] $meta_tags Array of Site Icon meta tags.
	 */
	$meta_tags = apply_filters( 'site_icon_meta_tags', $meta_tags );
	$meta_tags = array_filter( $meta_tags );

	foreach ( $meta_tags as $meta_tag ) {
		echo "$meta_tag\n";
	}
}
apply_filters( 'site_icon_meta_tags', wps_site_icon() );