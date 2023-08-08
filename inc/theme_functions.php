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

function card_detail( $post_id = null, $post_type = '' ){

	$post_type = !empty( $post_type ) ? '_' . $post_type : null;

	$terms = [
		'mileage' 		  . $post_type => __('Mileage','shopperexpress'),
		'engine' 		  . $post_type => __('Engine','shopperexpress'),
		'transmission'   . $post_type => __('Transmission','shopperexpress'),
		'drivetrain' 	  . $post_type => __('Drivetrain','shopperexpress'),
		'exterior-color' . $post_type => __('Exterior color','shopperexpress'),
		'interior-color' . $post_type => __('Interior color','shopperexpress'),
		'trim' 			  . $post_type => __('Trim','shopperexpress')

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

function wps_get_term( $post, $taxonomy, $field = 'name' ){

	if ( $term = get_the_terms( $post, $taxonomy ) ) {
		$value = $term[0]->name;
	}else{
		$value = null;
	}
	
	return $value;
}

function shortcode_callback_page_id( $atts = array() ) {
	global $post;
	return $post->ID;
}
add_shortcode( 'page_id', 'shortcode_callback_page_id' );

add_action( 'wpcf7_mail_sent', 'wps_wpcf7_mail_sent_function' ); 
function wps_wpcf7_mail_sent_function( $contact_form ) {
	$submission = WPCF7_Submission::get_instance();
	$contact_form = WPCF7_ContactForm::get_current();
	$contact_form_id = $contact_form->id;

	if ( $submission ) {
		$posted_data = $submission->get_posted_data();
	}

	$first_name = $posted_data['firstName'];
	$last_name = $posted_data['lastName'];
	$email = $posted_data['your-email'];
	$phone = !empty( $posted_data['phone'] ) ? $posted_data['phone'] : '';
	$zip = !empty( $posted_data['zip'] ) ? $posted_data['zip'] : '';
	$comments = !empty( $posted_data['message'] ) ? $posted_data['message'] : '';

	if ( !empty( $posted_data['get_page_id'] ) ) {
		$comments = get_the_title( $posted_data['get_page_id'] );
	}

	$message = '<?xml version="1.0" encoding="utf-8"?>
	<?ADF version="1.0"?>
	<adf>
	<prospect>
	<id source="shopperexpress" sequence="1"></id>
	<requestdate>' . date( 'm-d-Y' ) . '</requestdate>
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
	
	if ( !is_admin() ){
		$meta_tags = array();
		$icon_32   = get_site_icon_url( 32 );
		if ( empty( $icon_32 ) && is_customize_preview() ) {
			$icon_32 = '/favicon.ico';
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

		$meta_tags = apply_filters( 'site_icon_meta_tags', $meta_tags );
		$meta_tags = array_filter( $meta_tags );

		foreach ( $meta_tags as $meta_tag ) {
			echo "$meta_tag\n";
		}

	}
}
apply_filters( 'site_icon_meta_tags', function(){} );
add_action('wp_head', 'wps_site_icon');

#add_filter( 'wpcf7_load_js', '__return_false' );

function shortcode_callback_offer_payment( $atts = array() ) {
	global $post;
	$post_id = $post->ID;

	$location = wps_get_term( $post_id, 'location');
	$condition = wps_get_term( $post_id, 'condition');
	$loanterm = get_field('loanterm', $post_id);
	$loanapr = get_field('loanapr', $post_id);
	$down_payment = wps_get_term( $post_id, 'down-payment');
	$lease_payment = wps_get_term( $post_id, 'lease-payment');
	$loan_payment = wps_get_term( $post_id, 'loan-payment');
	$leaseterm = wps_get_term( $post_id, 'leaseterm');
	while ( have_rows('offers_flexible_content' , 'options' ) ) : the_row();
		if ( get_row_layout() == 'payment' && have_rows( 'payment_list' ) ){
			while ( have_rows( 'payment_list' ) ) : the_row();
				$link = get_sub_field( 'link' );
				$lock = get_sub_field( 'lock' );
				$show_payment = $lock  ? get_sub_field( 'show_payment' ) : false;
				$show_event = get_sub_field('show_event');

				$down_payment = !empty($down_payment) ? $down_payment : number_format($price);

				switch ( $atts['type'] ) {
					case 'lease-payment':
					if ( $down_payment && $lease_payment ) {
						$lease_payment = !empty($lease_payment) ? '$' . number_format($lease_payment) : null;
						$output = !empty($lease_payment) ? '<span class="savings">$' . $down_payment . ' ' . __('DOWN' , 'shopperexpress') .'</span>' . $lease_payment . ' <sub>/mo</sub>' : null;
					}else{
						$output = null;
					}

					break;

					case 'Disclosure_loan':
					if ( $condition != 'Slightly Used' && $condition != 'Used' ) {
						$output = $loanterm ? '<span class="savings">' . $loanterm . ' ' . __('mos.' , 'shopperexpress') .'</span>' : '';
						if($loanapr) $output .= $loanapr . '% <sub>APR</sub>';
					}else{
						$output = 2;
					}
					break;

					case 'Disclosure_lease':
					if ( $down_payment && $lease_payment ) {
						$lease_payment = !empty($lease_payment) && $lease_payment != 'None' && $lease_payment>0 ? '$' . number_format($lease_payment) : null;
						$output = !empty($lease_payment) ? '<span class="savings">$' . $down_payment . ' ' . __('DOWN' , 'shopperexpress').' '. $leaseterm . ' ' . __('mos.' , 'shopperexpress') . '</span>' . $lease_payment . ' <sub>/mo</sub>' : null;
					}else{
						$output = null;
					}
					break;
					case 'Disclosure_Cash':
					if ( $condition != 'Slightly Used' && $condition != 'Used' ) {
						$cash_offer = get_field('cash_offer');
						$cash_offer = is_int( $cash_offer ) ? '$'. number_format($cash_offer) : $cash_offer;
						$cash_offer_label = get_field('cash_offer_label');
						$output = !empty($cash_offer) ? '<span class="savings">' . $cash_offer_label . '</span>' . $cash_offer : null;
					}else{
						$output = null;
					}
					break;

					default:
					$loan_payment = !empty($loan_payment) && $loan_payment != 'None' ? '$' . number_format($loan_payment) . ' <sub>/mo</sub>' : null;
					$output = !empty($loan_payment) ? '<span class="savings">$' . $down_payment . ' ' . __('DOWN' , 'shopperexpress') .'</span>' . $loan_payment : null;
					break;
				}
				
			endwhile;
		}
	endwhile;

	return strip_tags($output);
}
add_shortcode( 'offer_payment', 'shortcode_callback_offer_payment' );


function shortcode_callback_offer_content( $atts = array() ) {
	global $post;
	$post_id = $post->ID;
	switch ( $atts['type'] ) {
		case 'lease':
		$output = get_field( 'disclosure_lease', $post_id );
		break;
		case 'loan':
		$output = get_field( 'disclosure_finance', $post_id );
		break;
		case 'cash':
		$output = get_field( 'disclosure_cash', $post_id );
		break;
	}

	return strip_tags($output);
}
add_shortcode( 'offer_content', 'shortcode_callback_offer_content' );

add_action( 'pre_get_posts', function ($query){
	if ( ! is_admin() && $query->is_main_query() && is_post_type_archive('listings')) {
		$query->set( 'order', 'ASC' );
		$query->set( 'orderby', 'meta_value_num' );
		$query->set( 'meta_key', 'price' );
	} 
	return $query;
});

function wps_get_icon( $icon = '' ){
	return '<i class="material-icons">' . str_replace( ' ', '_', $icon) . '</i>';
}

add_shortcode( 'stock', function ( $atts = array() ) {

	$condition = !empty( $atts['condition'] ) ? strtolower($atts['condition']) : 'new';
	$post_type = $condition == 'used' ? 'used-listings' : 'listings';

	$args = array(
		'post_type'   => $post_type,
		'post_status' => 'publish',
		'posts_per_page' => -1,
		'fields' 	=> 'ids',
	);

	$query = new WP_Query( $args );

	return $query->found_posts;
} );

function CallAPI($url, $data, $post_id)
{
	if ( !have_rows( 'features_list', $post_id ) ) {

		$curl = curl_init();

		$url = sprintf("%s?%s", $url, http_build_query($data));

		$mt = explode(' ', microtime());

		$chromedata_timestamp = ((int)$mt[1]) * 1000 + ((int)round($mt[0] * 1000));

		$chromedata_noonce = substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(32))), 0, 32);
		$realm = 'http://chromedata.com';

		$chromedata_app_id = get_field( 'chromedata_app_id', 'options' );
		$shared_secret = get_field( 'shared_secret', 'options' );
		$chromedata_secret_digest_original = $chromedata_noonce . $chromedata_timestamp . $shared_secret;
		$chromedata_secret_digest = base64_encode(sha1($chromedata_secret_digest_original,true));
		$token = "Atmosphere realm=\"{$realm}\",";
		$token .= "chromedata_app_id=\"{$chromedata_app_id}\",";
		$token .= "chromedata_nonce=\"{$chromedata_noonce}\",";
		$token .= "chromedata_secret_digest=\"{$chromedata_secret_digest}\",";
		$token .= "chromedata_digest_method=SHA1,";
		$token .= "chromedata_version=1.0,";
		$token .= "chromedata_timestamp=\"{$chromedata_timestamp}\"";

		$headers = array(
			"Accept: application/json",
			"Content-Type: application/json",
			"Authorization: {$token}",
		);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode( $data ) );
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_HTTPHEADER , $headers);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_VERBOSE, 1);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		$result = curl_exec($curl);
		curl_close($curl);

		$result = json_decode( $result, true );

		if ( !empty( $result['result'] ) ) {
			foreach ( $result['result']['features'] as $item ) {
				$output[$item['sectionName']][] = $item;
			}
		}
		foreach ( $output as $index => $item ) {
			$list = [];
			foreach( $item as $value){
				$description = $value['description'] != $value['nameNoBrand'] ? $value['description'] . ': ' . $value['nameNoBrand'] : $value['description'];
				$list[] = [ 'feature' => $description ];
			}
			add_row( 'features_list', [ 'heading' => $index, 'features' => $list ], $post_id);
		}
		

	}
}
