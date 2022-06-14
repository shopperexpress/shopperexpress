<?php
add_action( 'wps_update_listings', 'wps_update_listings_function' );

function wps_update_listings_function() {

	$Automotive_Plugin = Automotive_Plugin();
	$WP_Auto_Import = new WP_Auto_Import();

	if ( have_rows( 'csv_import', 'options' ) ) :
		while ( have_rows( 'csv_import', 'options' ) ) : the_row(); 
			$file = get_sub_field( 'url', 'options' );

			$file_content = $WP_Auto_Import->get_file_contents("url", $file);
			$file_type  = $WP_Auto_Import->get_file_type($file_content[1]);
			$file_array = $WP_Auto_Import->convert_file_content_to_array($file_type, $file_content[1]);

			$is_cron            = (isset($_GET['cron']) ? true : false);
			$listing_categories = $Automotive_Plugin->get_listing_categories();
			$_POST = [];

			$extra_spots = array(
				"vehicle_overview"          => array(__("Vehicle Overview", "listings"), 0),
				"technical_specifications"  => array(__("Technical Specifications", "listings"), 0),
				"other_comments"            => array(__("Other Comments", "listings"), 0),
				"gallery_images"            => array(__("Gallery Images", "listings"), 0),
				"price"                     => array(__("Price", "listings"), 1),
				"original_price"            => array(__("Original Price", "listings"), 1),
				"city_mpg"                  => array(__("City MPG", "listings"), 1),
				"highway_mpg"               => array(__("Highway MPG", "listings"), 1),
				"video"                     => array(__("Video", "listings"), 1),
				"features_and_options"      => array(__("Features and Options", "listings"), 0),
				"secondary_title"           => array(__("Secondary Title", "listings"), 1),
				"permalink"                 => array(__("Permalink", "listings"), 1),
				"latitude"                  => array(__("Latitude", "listings"), 1),
				"longitude"                 => array(__("Longitude", "listings"), 1),
				"listing_badge"             => array(__("Badge", "listings"), 1),
				"tax_label_listing"         => array(__("Tax Label on Listing Page", "listings"), 1),
				"tax_label_inventory"       => array(__("Tax Label on Inventory Page", "listings"), 1),
				"car_sold"                  => array(__("Car Sold", "listings"), 1),
				"pdf"                       => array(__("PDF", "listings"), 1)
			);

			$assoc_val              = get_option("file_import_associations" . ($is_cron ? "_" . (int)$_GET['cron'] : ""));
			$associations           = ($assoc_val ? $assoc_val : array());
			$duplicate_check_val    = (isset($associations['duplicate_check']) && !empty($associations['duplicate_check']) ? $associations['duplicate_check'] : "");
			$overwrite_existing_val = (isset($associations['overwrite_existing']) && !empty($associations['overwrite_existing']) ? $associations['overwrite_existing'] : "");
			$dont_overwrite_empty   = (isset($associations['dont_overwrite_empty']) && !empty($associations['dont_overwrite_empty']) ? $associations['dont_overwrite_empty'] : "");

			$overwrite_existing_listing_images = (isset($associations['overwrite_existing_listing_images']) && !empty($associations['overwrite_existing_listing_images']) ? $associations['overwrite_existing_listing_images'] : "" );

			foreach($listing_categories as $key => $option) {
				$needle         = $option['slug'];
				$is_association = ( isset( $associations['csv'] ) && is_array( $associations['csv'] ) && array_search( $needle, $associations['csv'] ) ? true : false );

				if(!isset($option['link_value']) || (isset($option['link_value']) && $option['link_value'] == "none")){ 
					if ( $is_association ) {
						$values = array_keys( $associations['csv'], $needle );

						if ( ( isset( $rows[0][ $values[0] ] ) || array_search( $values[0], $titles ) !== false || isset($titles[$values[0]]) ) ) {
							$label = (isset($_SESSION['auto_csv']['file_type']) && $_SESSION['auto_csv']['file_type'] == "xml" && isset($titles[$values[0]]) ? $titles[$values[0]] : $values[0]);

							$_POST['csv'][$values[0]] = $needle;
						}
					} 
				}
			}

			if($Automotive_Plugin->is_yoast_active()){
				$extra_spots['_yoast_wpseo_metadesc'] = array(__("Yoast SEO Description", "listings"), 1);
				$extra_spots['_yoast_wpseo_focuskw']  = array(__("Yoast Focus Keyword", "listings"), 1);
			}

			foreach($extra_spots as $key => $option){
				$needle         = $key;
				$is_association = (isset($associations['csv']) && is_array($associations['csv']) && array_search($needle, $associations['csv']) ? true : false); ?>
				<?php
				if($is_association){
					$safe_val = $needle;
					$values   = array_keys($associations['csv'], $safe_val);

					foreach($values as $val_key => $val_val){
						if( (isset($rows[0][$val_val])  || array_search($val_val, $titles) !== false  || isset($titles[$val_val])) ){
							$label = (isset($_SESSION['auto_csv']['file_type']) && $_SESSION['auto_csv']['file_type'] == "xml" && isset($titles[$val_val]) ? $titles[$val_val] : $val_val);
							$_POST['csv'][$val_val] = $safe_val;
						}
					}
				} 
			} 

			if(isset($_SESSION['auto_csv']['titles']) && !empty($_SESSION['auto_csv']['titles'])){
				$titles = $yoast_titles = $_SESSION['auto_csv']['titles'];
			}


			if(!isset($associations['title_from_values']) || empty($associations['title_from_values'])){
				$associations['title_from_values'] = array();
			}

			if(!empty($associations['title_from_values'])){
				foreach($associations['title_from_values'] as $title_value){
					$_POST['title_from_values'][] = $title_value;
				}
			}


			if($Automotive_Plugin->is_yoast_active()){ 


				if(!isset($associations['yoast_title_from_values']) || empty($associations['yoast_title_from_values'])){
					$associations['yoast_title_from_values'] = array();
				}

				            // do associations first if they exist so the order is preserved
				if(!empty($associations['yoast_title_from_values'])){
					foreach($associations['yoast_title_from_values'] as $title_value){

						$_POST['yoast_title_from_values'][] = $title_value;
						$title_index = array_search( $title_value, $yoast_titles );
						if($title_index !== false) {
							unset( $yoast_titles[ $title_index ] );
						}
					}
				}

			}

			if(isset($file_array) && !empty($file_array)){

				$rows = $_SESSION['auto_csv']['file_row'];

				if(!isset($rows[0])){
					$temp_rows = $rows;

					$rows = array(
						$temp_rows
					);
				}

				$additional_options = array();

				$additional_options['overwrite_existing_listing_images'] =  true;
				$additional_options['dont_overwrite_empty']              = false;

				$_SESSION['auto_csv']['ajax_import'] = true;
				$_SESSION['auto_csv']['ajax_post']   = $_POST;

				update_option('automotive_next_import_info', array(
					'rows' 								=> automotive_array_map_recursive('automotive_sanitize_utf8', $rows),
					'additional_options' 	=> $additional_options
				));

			}


			$_POST['duplicate_check'] = $duplicate_check_val;
			$_POST['overwrite_existing'] = 'on';

			$_POST = array_merge(['action' => 'automotive_import_listing'],['post' => $_POST]);

			$WP_Auto_Import->automotive_import_listing($_POST);
		endwhile;
	else:
		exit;
	endif;
}

