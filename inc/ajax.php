<?php
add_action('wp_ajax_register_user', 'register_user', 0);
add_action('wp_ajax_nopriv_register_user', 'register_user');
function register_user() {
	$first_name = stripcslashes($_POST['first-name']);
	$last_name = stripcslashes($_POST['last-name']);
	$email = strtolower($_POST['email']);
	$phone = clean_phone($_POST['phone']);
	$zip = $_POST['zip'];

	$responce = [];

	$user_data = array(
		'user_pass'       => wp_generate_password(12),
		'user_login'      => $email,
		'user_email'      => $email,
		'first_name'      => $first_name,
		'last_name'       => $last_name,
	);

	$user_id = wp_insert_user($user_data);
	if (!is_wp_error($user_id)) {
		update_field( 'phone', $phone, 'user_' . $user_id );
		update_field( 'zip', $zip, 'user_' . $user_id );

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
		<comments>' . home_url() . '</comments>
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


		auto_login_new_user($user_id, $_POST['permalink']);
	} else {
		if (isset($user_id->errors['empty_user_login'])) {
			$responce['error'] = __('All fields mandatory.','shopperexpress');
		} elseif (isset($user_id->errors['existing_user_login'])) {
			$responce['error'] = __('Email already exists.','shopperexpress');
		} else {
			$responce['error'] = __('Error Occured please fill up the sign up form carefully.','shopperexpress');
		}
		header('Content-Type: application/json');
		echo json_encode($responce);
	}

	die;
}

function auto_login_new_user( $user, $permalink ) {
	$user = get_user_by( 'ID', $user );
	wp_set_current_user( $user->ID, $user->data->user_login );
	wp_set_auth_cookie( $user->ID );
	echo wp_new_user_notification($user->ID, null, 'user' );
	echo $permalink;
	exit;
}


add_action( 'template_redirect', 'wps_load_more');
function wps_load_more(){

	if (!empty($_GET['ajax']) ) {
		
		$permalink = get_the_permalink();
		$posts = [];
		$search = !empty($_GET['search']) ? $_GET['search'] : null;
		$_post_type = isset($_GET['ptype']) && !empty($_GET['ptype']) ? $_GET['ptype'] : 'listings';
		$args = array(
			'post_type'   => $_post_type,
			'post_status' => 'publish',
			'ignore_sticky_posts' => true,
			'posts_per_page'         => -1,
		);
		if($_post_type == 'listings'){
			$args['meta_query'] = [
				[
					'key' => 'sold',
					'value' => 'Yes',
					'compare' => '!='
				]
			];
		} 
		$query = new WP_Query( $args );
		while ( $query->have_posts() ) : $query->the_post();

			$loan_payment = get_field( 'loan_payment' );
			$lease_payment = get_field( 'lease_payment' );

			if ( is_user_logged_in() ) {
				$original = get_field( 'price' );
				$term_price =  $original;
			}else{
				$value = get_field( 'original_price' );
				$term_price =  $value;
			}

			if ( !empty($_GET['autocomplete']) ) {
				echo '<li><a href="#">' . get_the_title() . '</a></li>';
			}

			if ( is_user_logged_in() ) {
				if ( !empty($_GET['value']) ) {

					$price = explode(',',$_GET['value']);

					if ( intval($price[0]) <= intval($term_price) && intval($price[1]) >= intval($term_price) ) {
						$posts[] = get_the_id();
					}else{
						$posts[] = null;
					}
				}
			}else{

				if ( !empty($_GET['value']) ) {

					$price = explode(',',$_GET['value']);

					if ( intval($price[0]) <= intval($term_price) && intval($price[1]) >= intval($term_price) ) {
						$posts[] = get_the_id();
					}else{
						$posts[] = null;
					}
				}
			}

		endwhile;

		wp_reset_query();

		$args = array(
			'post_type'   => $_post_type,
			'post_status' => 'publish',
			'ignore_sticky_posts' => true,
			'posts_per_page'         => -1,
		);

		if( !empty($posts) ) $args['post__in'] = $posts;

		$query = new WP_Query( $args );
		if ( $query->have_posts() && !empty($_GET['payment']) ) {
			$posts = [];
			$payment = explode(',',$_GET['payment']);
			while ( $query->have_posts() ) : $query->the_post();

				$lease_payment = wps_get_term( get_the_id(), 'lease-payment');
				$loan_payment = wps_get_term( get_the_id(), 'loan-payment');
				
				if ( (intval($payment[0]) <= intval($loan_payment) && intval($payment[1]) >= intval($loan_payment))  ) {
					$posts[] = get_the_id();
				}else{
					$posts[] = null;
				}
				if ( intval($payment[0]) <= intval($lease_payment) && intval($payment[1]) >= intval($lease_payment) ) {
					$posts[] = get_the_id();
				}else{
					$posts[] = null;
				}

			endwhile;
		}
		wp_reset_query();

		if ( empty($_GET['autocomplete']) ) {

			$next = !empty($_GET['next']) ? $_GET['next'] : 1;
			$args = null;
			$args = array(
				'post_type'   		  => $_post_type,
				'post_status' 		  => 'publish',
				'ignore_sticky_posts' => true,
				'posts_per_page'      => -1,
				'post__in'     		  => $posts,
				's' => $search
			);

			if ( !empty($_GET['sort']) ) {
				switch ( $_GET['sort'] ) {
					case 'highest':
					$args['order'] = 'DESC';
					$args['orderby'] = 'meta_value_num';
					$args['meta_key'] = 'price';
					break;
					case 'lowest':
					$args['order'] = 'ASC';
					$args['orderby'] = 'meta_value_num';
					$args['meta_key'] = 'price';
					break;
				}
			}

			if ( !empty(filter_args()) ) {
				$args['tax_query'] = filter_args();
			}

			$terms = ['year','body-style' , 'make', 'model','drivetrain', 'trim' , 'engine' , 'transmission' , 'exterior-color'];
			$filter = [];
			$query1 = new WP_Query( $args );
			
			while ( $query1->have_posts() ) : $query1->the_post();
				foreach ( $terms as $term ) {
					$taxonomy = get_the_terms( get_the_id(), $term );
					if ( !empty( $taxonomy ) ){
						$taxonomy = array_shift( $taxonomy );
						$slug = $term == 'year' ? 'yr' : $term;
						$filter[$slug][$taxonomy->slug] = $taxonomy->name;
					}
				}
			endwhile;
			wp_reset_query();

			echo '<div class="json-data" style="display: none;">' . json_encode([$filter]) . '</div>';

			$args['posts_per_page']	= 24;
			$args['paged'] = $next;
			
			$query = new WP_Query( $args );
			$pname = isset($_GET['ptype']) && !empty($_GET['ptype']) ? $_GET['ptype'] : 'listing';
			
			while ( $query->have_posts() ) : $query->the_post();
				get_template_part( 'blocks/content-'.$pname);
			endwhile;
			
			if ( $query->max_num_pages > $next):
				?>
				<a href="<?php echo add_query_arg(['next' => $next + 1] , $permalink); ?>" class="btn-more"></a>
				<?php
			endif;
		}
		exit;
	}
}

function filter_args(){

	$terms = list_taxonomies();
	$array = [];

	foreach( $terms as $term => $name ){

		$term_value = !empty($_GET[$term]) ? $_GET[$term] : null;

		if( !empty($term_value) && $term_value != 'all' ){

			$term_value = !empty($_GET['ajax']) ? $term_value : explode(',', $term_value[0]);

			if ( !in_array('all' , $term_value) && !empty( $term_value ) ) {

				$array[] = [
					'taxonomy' => $term,
					'field'    => 'slug',
					'terms'    => $term_value,
				];

			}
		}
	}

	if ( !empty($_GET['yr']) && $_GET['yr'] != 'all' ) {
		$array[] = [
			'taxonomy' => 'year',
			'field'    => 'slug',
			'terms'    => $_GET['yr'],
		];
	}

	return array_merge(['relation' => 'AND'], $array);
}


function child_automotive_listing_generate_search_dropdown( $items, $min_max, $options = array() ) {

	$return = "";
	$term_form = null;
	$min_max = explode( ",", $min_max );
	$current_category = null;

	$min_text = __( "Min", "listings" );
	$max_text = __( "Max", "listings" );

	if ( ! empty( $items ) ) {
		foreach ( $items as $column_item ) {

			$current_category = get_taxonomy($column_item);
			
			$prefix_text = (isset($options['prefix_text']) && !empty($options['prefix_text']) ? $options['prefix_text'] : "");
			$prefix_term = (!empty($prefix_text) ? $prefix_text . " " : "") . $current_category->label;

			$return .= '<div class="filter-row">';

			$other_options = array( "select_label" => $prefix_term );

			ob_start();

			wps_listing_dropdown( $current_category, $prefix_text, "css-dropdowns", $other_options );

			$return .= ob_get_clean();
			$return .= '</div>';
		}
	}

	return $return;
}


function wps_listing_dropdown( $category, $prefix_text, $select_class, $other_options = array() ) {

	$default_other_options = array(
		'current_option' => '',
		'select_name'    => $category->name,
		'select_label'   => $prefix_text . " " . $category->label,
		'show_amount'    => array()
	);

	extract(array_merge($default_other_options, $other_options));

	$no_options = __( "No options", "listings" );

	$select_attrs = array(
		'name'                => $select_name,
		'class'               => $select_class,
		'data-sort'           => $category->name,
		'data-prefix'         => $prefix_text,
		'data-label-singular' => $category->name,
		'data-label-plural'   => $category->name,
		'data-no-options'     => $no_options
	);

	switch ($category->name) {
		case 'exterior-color':
		$label = __('Color','shopperexpress');
		break;

		case 'body-style':
		$label = __('Body Type','shopperexpress');
		break;

		case 'engine':
		$label = __('Engine Type','shopperexpress');
		break;

		default:
		$label = $category->name;
		break;
	}

	$select_name = $select_name == 'year' ? 'yr' : $select_name;

	echo '<label for="select-'. $select_name .'" class="filter-title">' . $label . '</label>';
	echo'<select id="select-'. $select_name .'" multiple name="'. $select_name .'[]" class="filter" data-jcf=\'{"fakeDropInBody": false , "multipleCompactStyle": true}\'>';

	if ( $category->name != 'condition' ) {
		echo "<option value='all'>All</option>\n";
	}

	$args = ['taxonomy' => $category->name,'hide_empty' => true];
	if( $category->name == 'year' ) $args['order'] = 'DESC';
	$options = get_terms( $args );
	
	if ( ! empty( $options ) ) {

		foreach ( $options as $term ) {
			if(is_post_type_archive('offers')){
				$args = array(
					'post_type'   		  => 'offers',
					'post_status' 		  => 'publish',
					'ignore_sticky_posts' => true,
					'posts_per_page'      => 1,
					
				);
				$args['tax_query'] = [
					[
						'taxonomy' => $category->name,
						'field'    => 'slug',
						'terms'    => [$term->slug],
					]
				];
			}
			if(is_post_type_archive('listings')){
				$args = array(
					'post_type'   		  => 'listings',
					'post_status' 		  => 'publish',
					'ignore_sticky_posts' => true,
					'posts_per_page'      => 1,
					
				);
				$args['tax_query'] = [
					[
						'taxonomy' => $category->name,
						'field'    => 'slug',
						'terms'    => [$term->slug],
					]
				];
			}
			$query = new WP_Query( $args );
			if($query->have_posts()){
				if ( $term_key != "auto_term_order" ) {
					$term_value_safe = htmlentities( $term->slug, ENT_QUOTES, 'UTF-8' );
					
					$is_selected = in_array( $term->slug, explode(',', $_GET[$select_name][0])) ? true : false;
					
					echo "\t<option value='" . $term_value_safe . "'" . ($is_selected ? "selected='selected' class='checked'" : "") . " data-key='" . $term_key . "'>";
					echo htmlentities( $term->name, false, 'UTF-8' );
					
					if(!empty($show_amount)){
						echo " (" . (isset($show_amount[$term_key]) && !empty($show_amount[$term_key]) ? $show_amount[$term_key] : 0) . ")";
					}
					
					echo "</option>\n";
				}
			}
		}
	} else {
		echo "<option value=''>" . $no_options . "</option>\n";
	}

	echo "</select>\n\n";
}

//********************************************
//  Ajax Login
//***********************************************************
function ajax_login(){
	$username = $_POST['username'];
	$password = $_POST['password'];
	$nonce    = $_POST['nonce'];
	$remember = (isset($_POST['remember_me']) && !empty($_POST['remember_me']) ? $_POST['remember_me'] : "");

	if ( wp_verify_nonce( $nonce, 'ajax_login_none' ) && !empty($username) && !empty($password) ) {
		$creds = array();

		$creds['user_login']    = sanitize_text_field($username);
		$creds['user_password'] = sanitize_text_field($password);
		$creds['remember_me']   = sanitize_text_field(($remember == "yes" ? true : false));

		$user = wp_signon( $creds, false );

		if ( ! is_wp_error($user) ) {
			echo "success";
		}else{

			if ( !empty($user->errors['invalid_username']) ) {
				echo $user->errors['invalid_username'][0];
			}elseif( !empty($user->errors['invalid_email']) ){
				echo $user->errors['invalid_email'][0];
			}elseif( !empty($user->errors['incorrect_password']) ){
				echo $user->errors['incorrect_password'][0];
			}
		}
	}

	die;
}

add_action("wp_ajax_ajax_login", "ajax_login");
add_action("wp_ajax_nopriv_ajax_login", "ajax_login");
