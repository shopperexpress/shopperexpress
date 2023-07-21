<?php

//Custom Menu Walker
class Custom_Walker_Nav_Menu extends Walker_Nav_Menu {
	function start_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat( "\t", $depth );
		$output .= "\n$indent<div class=\"drop-holder\"><ul>\n";
	}

	function end_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat( "\t", $depth );
		$output .= "$indent</ul></div>\n";
	}

	function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
		global $wp_query;
		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

		$class_names = $value = '';

		$classes = empty( $item->classes ) ? array() : (array) $item->classes;
		$classes[] = 'menu-item-' . $item->ID;

		$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args ) );
		$class_names = ' class="' . esc_attr( $class_names ) . '"';

		$id = apply_filters( 'nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args );
		$id = strlen( $id ) ? ' id="' . esc_attr( $id ) . '"' : '';

		$output .= $indent . '<li' . $id . $value . $class_names . '>';

		$attributes  = ! empty( $item->attr_title ) ? ' title="'  . esc_attr( $item->attr_title ) . '"' : '';
		$attributes .= ! empty( $item->target )     ? ' target="' . esc_attr( $item->target     ) . '"' : '';
		$attributes .= ! empty( $item->xfn )        ? ' rel="'    . esc_attr( $item->xfn        ) . '"' : '';
		$attributes .= ! empty( $item->url )        ? ' href="'   . esc_attr( $item->url        ) . '"' : '';

		$item_output = $args->before;
		$item_output .= '<a' . $attributes . '>' . wps_get_icon( get_field( 'icon', $item->ID ) );
		$item_output .= $args->link_before . apply_filters( 'the_title', $item->title, $item->ID ) . $args->link_after;
		$item_output .= '</a>';
		$item_output .= $args->after;

		$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
	}

	function end_el( &$output, $item, $depth = 0, $args = array() ) {
		$output .= "</li>\n";
	}
}

class Header_Walker_Nav_Menu extends Walker_Nav_Menu {
	function start_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat( "\t", $depth );
		$output .= "\n$indent<div class=\"drop slide\"><ul>\n";
	}

	function end_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat( "\t", $depth );
		$output .= "$indent</ul></div>\n";
	}

	function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
		global $wp_query;
		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

		$class_names = $value = '';

		$classes = empty( $item->classes ) ? array() : (array) $item->classes;
		$classes[] = 'menu-item-' . $item->ID;

		$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args ) );
		$class_names = ' class="' . esc_attr( $class_names ) . '"';

		$id = apply_filters( 'nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args );
		$id = strlen( $id ) ? ' id="' . esc_attr( $id ) . '"' : '';

		$output .= $indent . '<li' . $id . $value . $class_names . '>';

		$attributes  = ! empty( $item->attr_title ) ? ' title="'  . esc_attr( $item->attr_title ) . '"' : '';
		$attributes .= ! empty( $item->target )     ? ' target="' . esc_attr( $item->target     ) . '"' : '';
		$attributes .= ! empty( $item->xfn )        ? ' rel="'    . esc_attr( $item->xfn        ) . '"' : '';
		$attributes .= ! empty( $item->url )        ? ' href="'   . esc_attr( $item->url        ) . '"' : '';

		if ( $depth == 0 ) {
			$attributes .= ' class="drop-opener"';
		}

		$item_output = $args->before;
		$item_output .= '<a' . $attributes . '>';
		$item_output .= $args->link_before . apply_filters( 'the_title', $item->title, $item->ID ) . $args->link_after;

		if ( $icon = get_field( 'icon', $item->ID ) ) {
			$item_output .= wps_get_icon( $icon );
		}

		$item_output .= '</a>';
		$item_output .= $args->after;

		$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
	}

	function end_el( &$output, $item, $depth = 0, $args = array() ) {
		$output .= "</li>\n";
	}
}

class Drop_Down_Walker_Nav_Menu extends Walker_Nav_Menu {

	function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
		global $wp_query;

		$attributes  = ! empty( $item->attr_title ) ? ' title="'  . esc_attr( $item->attr_title ) . '"' : '';
		$attributes .= ! empty( $item->target )     ? ' target="' . esc_attr( $item->target     ) . '"' : '';
		$attributes .= ! empty( $item->xfn )        ? ' rel="'    . esc_attr( $item->xfn        ) . '"' : '';
		$attributes .= ! empty( $item->url )        ? ' href="'   . esc_attr( $item->url        ) . '"' : '';
		$attributes .= ' class="dropdown-item"';

		$item_output = $args->before;
		$item_output .= '<a' . $attributes . '>';
		$item_output .= $args->link_before . apply_filters( 'the_title', $item->title, $item->ID ) . $args->link_after;
		$item_output .= '</a>';
		$item_output .= $args->after;

		$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
	}
}

if ( ! class_exists( "WP_Auto_Import" ) ) {
	class WP_Auto_Import {

		public function __construct(){
			add_action( "wp_ajax_automotive_import_listing_wps", array($this, "automotive_import_listing"));
			add_action( "wp_ajax_nopriv_automotive_import_listing_wps", array($this, "automotive_import_listing"));
		}

		/**
		 * Generic function to deny access to guests (or hackers)
		 */
		public function deny_access_to_guest() {
			die( "Access Denied" );
		}

		/**
		 * Gets the file content for import
		 *
		 * @param $type
		 * @param $file
		 *
		 * @return array
		 */
		public function get_file_contents( $type, $file ) {
			$return_messages = array();

			if ( $type == "url" ) {
				// if valid URL
				if (strpos($file, 'ftp://') === 0) {
					$handle = fopen($file, "r");
					$file_content = stream_get_contents($handle);
					fclose($handle);

					$return_messages = array( "success", $file_content );
				} elseif ( filter_var( $file, FILTER_VALIDATE_URL ) ) {
					// wp_remote_get
					$wp_get = wp_remote_get( $file, array( "timeout" => 30 ) );

					// test for error
					if ( ! is_wp_error( $wp_get ) ) {

						// generate temp file to process
						$file_content = $wp_get['body'];

						$return_messages = array( "success", $file_content );

						$_SESSION['auto_csv']['file_content'] = $file_content;
					} else {
						$return_messages[] = array( "error", $wp_get->get_error_message() );
					}
				} else {
					$return_messages[] = array( "error", __( "Not a valid URL", "listings" ) );
				}

				// if uploaded file
			} elseif ( $type == "file" ) {
				$empty_file_string = __( "There was no file uploaded", "listings" );

				if ( empty( $file ) ) {
					$return_messages[] = array( "error", $empty_file_string );
				}

				// check file for upload error codes
				switch ( $file['error'] ) {
					case 0:
					$file_content = file_get_contents( $file['tmp_name'] );

					$return_messages                      = array( "success", $file_content );
					$_SESSION['auto_csv']['file_content'] = $file_content;
					break;
					case 1:
					$return_messages[] = array(
						"error",
						__( "Your file exceeded your servers maximum upload size", "listings" )
					);
					break;
					case 2:
					$return_messages[] = array(
						"error",
						__( "Your file exceeded the form maximum upload size", "listings" )
					);
					break;
					case 3:
					$return_messages[] = array( "error", __( "The file was only partially uploaded", "listings" ) );
					break;
					case 4:
					$return_messages[] = array( "error", $empty_file_string );
					break;
					case 6:
					$return_messages[] = array(
						"error",
						__( "Your server is missing a temporary folder", "listings" )
					);
					break;
					case 7:
					$return_messages[] = array(
						"error",
						__( "Your server cannot write the file to the disk", "listings" )
					);
					break;
					case 8:
					$return_messages[] = array(
						"error",
						__( "A PHP extension installed on your server has stopped the upload", "listings" )
					);
					break;
				}
			}

			// if not success then add error to sesh
			if ( $return_messages[0] != "success" ) {
				$_SESSION['auto_csv']['error'] = $return_messages;
			}

			return $return_messages;

		}


		/**
		 * Get file type based on content, if not valid XML then its assumed CSV
		 *
		 * @param $file_content
		 *
		 * @return string
		 */
		public function get_file_type( $file_content ) {
			// test if XML, if not assume it is CSV
			libxml_use_internal_errors( true );

			$doc = simplexml_load_string( $file_content, null, LIBXML_NOCDATA );

			if ( $doc ) {
				$file_type = "xml";
			} else {
				$file_type = "csv";
			}

			$_SESSION['auto_csv']['file_type'] = $file_type;

			return $file_type;
		}


		/**
		 * Validates the array doesn't contain any integer column names which can derp the import process
		 *
		 * @param $array
		 *
		 * @return bool
		 */
		private function validate_array( $array ) {
			$valid_array = true;

			if ( ! empty( $array ) ) {
				foreach ( $array[0] as $key => $value ) {
					if ( is_int( $key ) ) {
						$valid_array = false;
						break;
					}
				}
			} else {
				$valid_array = false;
			}

			return $valid_array;
		}

		/**
		 * @param array $array
		 *
		 * @return array
		 */
		public function array_keys_multi( array $array ) {
			$keys = array();

			foreach ( $array as $key => $value ) {
				$keys[] = $key;

				if ( is_array( $array[ $key ] ) ) {
					$keys = array_merge( $keys, $this->array_keys_multi( $array[ $key ] ) );
				}
			}

			return $keys;
		}

		/**
		 * Converts XML/CSV file content into a usable array
		 *
		 * @param $file_type
		 * @param $file_content
		 * @param string $xml_parent
		 *
		 * @return array
		 */
		public function convert_file_content_to_array( $file_type, $file_content, $xml_parent = "" ) {
			global $lwp_options;

			$return = array();

			if ( $file_type == "csv" ) {
				// load CSV parser
				if ( ! class_exists( "parseCSV" ) ) {
					include( LISTING_HOME . "/classes/" . "parsecsv.lib.php" );
				}

				if(substr($file_content, -1) == '"'){
					$file_content = $file_content . "\r\n";
				}

				$csv            = new parseCSV();
				$csv->delimiter = ( isset( $lwp_options['csv_delimiter'] ) && ! empty( $lwp_options['csv_delimiter'] ) ? $lwp_options['csv_delimiter'] : "," );
				$csv->parse( $file_content );

				$rows   = array_values( $this->array_remove_empty( $csv->data ) );
				$titles = $csv->titles;

				if ( $this->validate_array( $rows ) ) {
					$_SESSION['auto_csv']['file_row'] = $rows;
					$_SESSION['auto_csv']['titles']   = $titles;

					$return = array( $rows, $titles );
				} elseif( empty($rows) ) {
					$return = array(
						"error",
						"empty"
					);
				} else {
					$return = array(
						"error",
						__( "The file must not contain numbers as the column title", "listings" )
					);
				}

			} elseif ( $file_type == "xml" ) {
				$xml  = simplexml_load_string( $file_content, null, LIBXML_NOCDATA );
				$json = json_encode( $xml );
				$rows = json_decode( $json, true );

				$single_value_check = apply_filters( 'import_single_value_check', '' );

				// instance of XML and having only 1 node on a value
				if ( ! empty( $single_value_check ) ) {
					$paths = explode( "|", $single_value_check );

					if ( ! empty( $xml_parent ) ) {
						//if has pipe
						if ( strstr( $xml_parent, "|" ) ) {
							$loop_pipe   = explode( "|", $xml_parent );
							$temp_parent = &$rows;

							if ( ! empty( $loop_pipe ) ) {
								foreach ( $loop_pipe as $loop ) {
									$temp_parent = &$temp_parent[ $loop ];
								}
							}
						} else {
							$temp_parent = &$rows[ $xml_parent ];
						}
					} else {
						$temp_parent = &$rows;
					}

					foreach ( $temp_parent as $item_key => $item ) {
						$check_item = &$item;

						foreach ( $paths as $single_path ) {
							if ( isset( $check_item[ $single_path ] ) ) {
								$check_item = &$check_item[ $single_path ];
							} else {
								break;
							}
						}

						if ( ! empty( $check_item ) && is_string( $check_item ) ) {

							$check_item = array( $check_item );
							unset( $check_item );
						}


						//if has pipe
						if ( strstr( $xml_parent, "|" ) ) {
							$loop_pipe   = explode( "|", $xml_parent );
							$temp_parent = &$rows;

							if ( ! empty( $loop_pipe ) ) {
								foreach ( $loop_pipe as $loop ) {
									$temp_parent = &$temp_parent[ $loop ];
								}
							}

							$temp_parent[ $item_key ] = $item;
						} else {
							$rows[ $xml_parent ][ $item_key ] = $item;
						}
					}
				}

				// $xml_parent
				$_SESSION['auto_csv']['file_row'] = $rows;

				$return = array( $rows );

				if ( ! empty( $xml_parent ) ) {
					update_option( "file_import_xml_key", $xml_parent );

					//if has pipe
					if ( strstr( $xml_parent, "|" ) ) {
						$loop_pipe = explode( "|", $xml_parent );

						if ( ! empty( $loop_pipe ) ) {
							foreach ( $loop_pipe as $loop ) {
								$rows = $rows[ $loop ];
							}
						}

						$_SESSION['auto_csv']['file_row'] = $rows;

						$titles = $this->array_keys_multi( $rows );
					} else {

						if ( isset( $rows[ $xml_parent ][0] ) && ! empty( $rows[ $xml_parent ][0] ) ) {
							$titles = $this->array_keys_multi( $rows[ $xml_parent ][0] );
						} else {
							$titles = $this->array_keys_multi( $rows[ $xml_parent ] ); // single xml row
						}

						$_SESSION['auto_csv']['file_row'] = $rows[ $xml_parent ];
					}

					$_SESSION['auto_csv']['titles'] = $titles;

					$return[] = $titles;
				}
			}

			return $return;
		}

		/**
		 * @param $subject
		 * @param $array
		 *
		 * @return array|null
		 */
		public function key_get_parents( $subject, $array, $exact_val = "" ) {

			if ( ! empty( $array ) ) {
				foreach ( $array as $key => $value ) {

					if ( is_array( $value ) ) {

						if ( in_array( $subject, array_keys( $value ), true ) && ( isset( $exact_val ) && in_array( $exact_val, array_values( $value ) ) ) ) {
							return array( $key );
						} else {
							$chain = $this->key_get_parents( $subject, $value, $exact_val );

							if ( ! is_null( $chain ) ) {
								return array_merge( array( $key ), $chain );
							}
						}
					}
				}
			}

			return null;
		}

		public function multiarray_keys( $ar ) {

			foreach ( $ar as $k => $v ) {
				if ( is_array( $v ) ) {
					$keys[] = $k;
				}

				if ( is_array( $ar[ $k ] ) && is_array( $this->multiarray_keys( $ar[ $k ] ) ) ) {
					$keys = array_merge( $keys, $this->multiarray_keys( $ar[ $k ] ) );
				}
			}

			return ( isset( $keys ) && ! empty( $keys ) ? $keys : "" );
		}

		public function auto_add_http( $url ) {
			if ( ! preg_match( "~^(?:f|ht)tps?://~i", $url ) ) {
				$url = "http://" . $url;
			}

			return $url;
		}

		public function get_upload_image( $image_url ) {
			$image = $image_use = str_replace( "\"", "", $image_url );
			$get   = wp_remote_get( $image );

			if ( ! is_wp_error( $get ) && $get['response']['code'] == 200 ) {
				$type = wp_remote_retrieve_header( $get, 'content-type' );

				$allowed_images = array( "image/jpg", "image/jpeg", "image/png", "image/gif", "application/pdf" );
				$extension      = pathinfo( $image, PATHINFO_EXTENSION );

				// try to determine type if not set
				if ( empty( $type ) ) {
					if ( $extension == "jpg" || $extension == "jpeg" ) {
						$type = "image/jpg";
					} elseif ( $extension == "png" ) {
						$type = "image/png";
					} elseif ( $extension == "gif" ) {
						$type = "image/gif";
					} elseif ( $extension == "pdf" ) {
						$type = "application/pdf";
					}
				}

				if ( ! $type && in_array( $type, $allowed_images ) ) {
					return false;
				}

				if ( empty( $extension ) || ! in_array( $extension, array( 'jpg', 'jpeg', 'gif', 'png', 'pdf' ) ) ) {
					$content_type = strtolower($type);

					// check if content type is even set...
					if ( strstr( $content_type, "image/jpg" ) || strstr( $content_type, "image/jpeg" ) ) {
						$image_use = $image . ".jpg";
						$type      = "image/jpg";
					} elseif ( strstr( $content_type, "image/png" ) ) {
						$image_use = $image . ".png";
						$type      = "image/png";
					} elseif ( strstr( $content_type, "image/gif" ) ) {
						$image_use = $image . ".gif";
						$type      = "image/gif";
					} elseif ( strstr( $content_type, "application/pdf" ) ) {
						$image_use = $image . "pdf";
					}
				}

				$mirror = wp_upload_bits( sanitize_file_name(basename( $image_use )), '', wp_remote_retrieve_body( $get ) );

				$attachment = array(
					'post_title'     => basename( $image ),
					'post_mime_type' => $type
				);

				if ( isset( $mirror ) && ! empty( $mirror ) ) {
					$attach_id = wp_insert_attachment( $attachment, $mirror['file'] );

					require_once( ABSPATH . 'wp-admin/includes/image.php' );

					$attach_data = wp_generate_attachment_metadata( $attach_id, $mirror['file'] );

					wp_update_attachment_metadata( $attach_id, $attach_data );
				} else {
					$attach_id = "";
				}

				return $attach_id;
			} else {
				return "";
			}
		}

		public function search_array_keys( $array, $term, $references, $title_val = "" ) {
			$count  = array_count_values( $references );
			$return = "";

			// if variable has more than a single value
			if ( isset( $count[ $term ] ) && $count[ $term ] >= 2 ) {
				$keys = array_keys( $references, $term );

				foreach ( $keys as $key ) {
					if ( strpos( $key, "|" ) !== false ) {
						$paths = explode( "|", $key );
						$items = $array;

						foreach ( $paths as $ndx ) {
							if ( isset( $items[ $ndx ] ) ) {
								$items = $items[ $ndx ];
							}
						}

						if ( ! is_array( $items ) ) {
							$return .= $items . "<br>";
						}
					} else {
						$return .= ( isset( $array[ $key ] ) && ! empty( $array[ $key ] ) ? $array[ $key ] : "" ) . "<br>";
					}
				}
			} else {
				$key = array_search( $term, $references );

				if ( strpos( $key, "|" ) !== false ) {
					$paths = explode( "|", $key );
					$items = $array;

					foreach ( $paths as $ndx ) {
						$items = $items[ $ndx ];
					}

					$return .= $items;
				} else {
					if ( ! empty( $title_val ) ) {
						if ( strpos( $term, "|" ) !== false ) {
							$paths = explode( "|", $term );
							$items = $array;

							foreach ( $paths as $ndx ) {
								$items = $items[ $ndx ];
							}

							$value = ( isset( $items ) && ! empty( $items ) ? $items : "" );
						} else {
							$value = ( isset( $array[ $term ] ) && ! empty( $array[ $term ] ) ? $array[ $term ] : "" );
						}

						$return .= $value;
					} else {
						$return .= ( isset( $array[ $key ] ) && ! empty( $array[ $key ] ) ? $array[ $key ] : "" );
					}
				}
			}

			return trim( $return );
		}

		function array_remove_empty( $haystack ) {
			foreach ( $haystack as $key => $value ) {
				if ( is_array( $value ) ) {
					$haystack[ $key ] = $this->array_remove_empty( $haystack[ $key ] );
				}

				if ( empty( $haystack[ $key ] ) ) {
					unset( $haystack[ $key ] );
				}
			}

			return $haystack;
		}

		public function recursive_sortable( $loop, $rows, $associations, $return = array() ) {
			foreach ( $loop as $key => $row ) {
				if ( ! is_array( $row ) ) {
					$parents = $this->key_get_parents( $key, $rows, $row );
					$label   = ( is_null( $parents ) ? $key : end( $parents ) . " " . $key );

					//echo "<li class='ui-state-default'><input type='hidden' name='csv[" . (is_null($parents) ? $key : implode("|", $parents) . "|" . $key ) . "]' > " . $label . "</li>";
					$return[ ( is_null( $parents ) ? $key : implode( "|", $parents ) . "|" . $key ) ] = $label;
				} else {
					$return = $this->recursive_sortable( $row, $rows, $associations, $return );
				}

			}

			return $return;
		}

		public function recursive_sortable_open( $loop, $rows, $return = array() ) {
			foreach ( $loop as $key => $row ) {
				if ( is_array( $row ) ) {
					$parents = $this->key_get_parents( $key, $rows, $row );
					//$label   = $key;//(is_null($parents) ? $key : end($parents) . " " . $key);

					//echo "<li class='ui-state-default'><input type='hidden' name='csv[" . (is_null($parents) ? $key : implode("|", $parents) . "|" . $key ) . "]' > " . $label . "</li>";
					$return[ ( is_null( $parents ) ? $key : implode( "|", $parents ) . "|" . $key ) ] = $key;

					$return = $this->recursive_sortable_open( $row, $rows, $return );
				} else {

				}

			}

			return $return;
		}

		public function title_recursive_sortable( $loop, $all_keys = array(), $original_loop = array() ) {

			if ( empty( $original_loop ) ) {
				$original_loop = $loop;
			}

			foreach ( $loop as $key => $loop_element ) {
				if ( ! is_array( $loop_element ) ) {
					$parents = $this->key_get_parents( $key, $original_loop, $loop_element );
					$label   = ( is_null( $parents ) ? $key : end( $parents ) . " " . $key );

					$all_keys[ ( is_null( $parents ) ? $key : implode( "|", $parents ) . "|" . $key ) ] = $label;
				} else {
					$all_keys = $this->title_recursive_sortable( $loop_element, $all_keys, $loop );
				}
			}

			return $all_keys;
		}

		public function recursive_all_keys( $input ) {
			$main = array();

			foreach ( $input as $key => $row ) {
				$main = $this->merge_arrays( $row, $main );
			}

			return $main;
		}

		public function merge_arrays( array &$array1, array &$array2 ) {
			$merged = $array1;

			foreach ( $array2 as $key => &$value ) {
				if ( is_array( $value ) && isset ( $merged [ $key ] ) && is_array( $merged [ $key ] ) ) {
					$merged [ $key ] = $this->merge_arrays( $merged [ $key ], $value );
				} else {
					$merged [ $key ] = $value;
				}
			}

			return $merged;
		}


		public function has_arrays( $element ) {
			$return = false;

			if ( isset( $element ) && is_array( $element ) && ! empty( $elemet ) ) {
				foreach ( $element as $element_row ) {
					if ( is_array( $element_row ) ) {
						$return = true;
						break;
					}
				}
			}

			return $return;
		}


		public function is_wpml_active() {
			return ( defined( "ICL_LANGUAGE_CODE" ) ? true : false );
		}

		public function encode_fix( $string ) {
			return $string;
		}

		public function get_listing_meta( &$listing_categories, &$listing_categories_safe, $dependancy_categories, $row, $csv ) {
			global $lwp_options, $awp_options;

			$post_meta         = array();
			$Automotive_Plugin = Automotive_Plugin();

			/* Default Latitude & Longitude */
			$default_latitude        = automotive_listing_get_option('default_value_lat', '43.653226');
			$default_longitude       = automotive_listing_get_option('default_value_long', '-79.3831843');
			$prices_include_decimals = automotive_listing_get_option('prices_include_decimals', false);
			$default_value_city      = automotive_listing_get_option('default_value_city', '');
			$default_value_hwy       = automotive_listing_get_option('default_value_hwy', '');

			// no header area
			if ( isset( $awp_options['no_header_area_default'] ) && $awp_options['no_header_area_default'] ) {
				$post_meta["no_header"] = "no_header";
			}

			// listing categories
			$listing_categories['Technical Specifications'] = array( "multiple" => true );
			$listing_categories['Other Comments']           = array( "multiple" => true );

			foreach ( $listing_categories as $key => $option ) {
				if ( isset( $option['multiple'] ) ) {
					// contains multiple values, concatenate them
					$key   = ( isset( $option['slug'] ) && ! empty( $option['slug'] ) ? $option['slug'] : str_replace( " ", "_", strtolower( $key ) ) );
					$value = $this->encode_fix( $this->search_array_keys( $row, $key, $csv ) );
				} else {
					$value = $this->encode_fix( $this->search_array_keys( $row, $key, $csv ) );

					//link_value
					if ( isset( $option['link_value'] ) && ! empty( $option['link_value'] ) ) {
						if ( $option['link_value'] == "price" ) {
							$value = $this->search_array_keys( $row, "price", $csv );

							// numbers
							if ( isset( $option['is_number'] ) && $option['is_number'] == 1 ) {
								$pattern = '/\D/';

								if($prices_include_decimals){
									$pattern = '/[^0-9.]*/';
								}

								$value = preg_replace( $pattern, '', $value );
							}

							$linked_price_value = $value;

							$post_meta[ $key ] = $linked_price_value;
						} else if ( $option['link_value'] == "mpg" ) {
							$city_value = $this->search_array_keys( $row, "city_mpg", $csv );
							$hwy_value  = $this->search_array_keys( $row, "highway_mpg", $csv );

							$value = $city_value . " " . $default_value_city . " / " . $hwy_value . " " . $default_value_hwy;

							$post_meta[ $key ] = $value;
						}
					}

					if ( empty( $value ) ) {
						$value = __( "None", "listings" );
					}

					$value = apply_filters('automotive_import_meta', $value, $key, $row, $csv);

					// add value if not already added
					$terms = ( isset( $listing_categories_safe[ $key ]['terms'] ) && ! empty( $listing_categories_safe[ $key ]['terms'] ) ? $listing_categories_safe[ $key ]['terms'] : array() );

					//compare_value
					if ( is_array( $terms ) && ! in_array( $value, $terms ) && ! empty( $value ) && isset( $option['compare_value'] ) && $option['compare_value'] == "=" ) {
						$listing_categories_safe[ $key ]['terms'][ $Automotive_Plugin->slugify( $value ) ] = $value;
					}
				}

				if ( ! empty( $value ) && $value != "n-a" ) {
					$post_meta[ $key ] = $value;

					if ( $value != __( "None", "listings" ) ) {
						$dependancy_categories[ $key ] = array( $Automotive_Plugin->slugify( $value ) => $value );
					}
				}
			}

			// gallery images
			$gallery_images = $this->import_gallery_images( $row, $csv );

			if ( ! empty( $gallery_images ) ) {
				$post_meta['_thumbnail_id']  = $gallery_images[0];
				$post_meta['gallery_images'] = $gallery_images;
			}

			// Features & Options
			$values               = $this->search_array_keys( $row, "features_and_options", $csv );
			$features_and_options = array();
			$dynamite             = automotive_listing_get_option('features_delimiter', '');

			$values = apply_filters('automotive_import_features_before', $values, $row, $csv);

			if ( ! empty( $values ) ) {
				if ( empty( $dynamite ) ) {
					if ( strstr( $values, "," ) ) {
						$dynamite = ",";
					} elseif ( strstr( $values, "<br>" ) ) {
						$dynamite = "<br>";
					} elseif ( strstr( $values, "|" ) ) {
						$dynamite = "|";
					} elseif ( strstr( $values, ";" ) ) {
						$dynamite = ";";
					}
				}

				if ( isset( $dynamite ) && ! empty( $dynamite ) ) {
					$values = explode( $dynamite, $values );

					foreach ( $values as $val ) {
						$features_and_options[] = $this->encode_fix( $val );
					}
				} else {
					$features_and_options[] = $this->encode_fix( $values );
				}
			}

			$features_and_options = apply_filters('automotive_import_features_after', $features_and_options, $row, $csv);

			if ( ! empty( $features_and_options ) ) {
				$post_meta["multi_options"] = $features_and_options;

				$options = (isset($listing_categories_safe['options']['terms']) && !empty($listing_categories_safe['options']['terms']) ? $listing_categories_safe['options']['terms'] : array());

				foreach ( $features_and_options as $option ) {
					$option = $this->encode_fix( trim( $option ) );
					if(function_exists('mb_detect_encoding')){
						$option = preg_replace( '/\x{EF}\x{BF}\x{BD}/u', '', automotive_iconv( mb_detect_encoding( $option ), 'UTF-8', $option ) );
					} else {
						$option = preg_replace( '/\x{EF}\x{BF}\x{BD}/u', '', automotive_iconv( 'ISO-8859-1', 'UTF-8', $option ) );
					}

					if ( is_array( $options ) && ! in_array( $option, $options ) ) {
						$listing_categories_safe['options']['terms'][] = $option;
					}
				}
			}

			// additional detail
			if ( ! empty( $lwp_options['additional_categories']['value'] ) ) {
				foreach ( $lwp_options['additional_categories']['value'] as $key => $additional_category ) {
					if ( isset( $lwp_options['additional_categories']['check'][ $key ] ) && $lwp_options['additional_categories']['check'][ $key ] == "on" ) {
						$safe_category = str_replace( " ", "_", strtolower( $additional_category ) );

						$post_meta[ $safe_category ] = 1;
					}
				}
			}

			// PDF
			$pdf = $this->search_array_keys( $row, "pdf", $csv );
			$pdf = apply_filters('automotive_import_pdf', $pdf, $row, $csv);

			if ( $pdf ) {
				$pdf_id = $this->get_upload_image($pdf);

				if(!empty($pdf_id)){
					$post_meta['pdf_brochure_input'] = $pdf_id;
				}
			}

			// map location
			$latitude  = $this->search_array_keys( $row, "latitude", $csv );
			$longitude = $this->search_array_keys( $row, "longitude", $csv );

			// map location
			$location = array(
				"latitude"  => ( empty( $latitude ) ? $default_latitude : $latitude ),
				"longitude" => ( empty( $longitude ) ? $default_longitude : $longitude ),
				"zoom"      => ( isset( $lwp_options['default_value_zoom'] ) && ! empty( $lwp_options['default_value_zoom'] ) ? $lwp_options['default_value_zoom'] : "10" )
			);

			$location = apply_filters('automotive_import_location', $location, $row, $csv);

			$post_meta["location_map"] = $location;

			$post_meta['latitude']  = $location['latitude'];
			$post_meta['longitude'] = $location['longitude'];

			// post options (city, hwy, video, listing_badge)
			$post_options = array(
				"video"             => apply_filters('automotive_import_video', $this->search_array_keys( $row, "video", $csv ), $row, $csv ),
				"custom_badge"      => apply_filters('automotive_import_listing_badge', $this->search_array_keys( $row, "listing_badge", $csv ), $row, $csv ),
				"custom_tax_inside" => apply_filters('automotive_import_tax_inside', $this->search_array_keys( $row, "tax_label_listing", $csv ), $row, $csv ),
				"custom_tax_page"   => apply_filters('automotive_import_tax_page', $this->search_array_keys( $row, "tax_label_inventory", $csv ), $row, $csv ),
				"price"             => array(
					"value" => apply_filters('automotive_import_price', ( isset( $linked_price_value ) ? $linked_price_value : $this->search_array_keys( $row, "price", $csv ) ), $row, $csv)
				),
				"city_mpg"          => array(
					"value" => apply_filters('automotive_import_city_mpg', $this->search_array_keys( $row, "city_mpg", $csv ), $row, $csv )
				),
				"highway_mpg"       => array(
					"value" => apply_filters('automotive_import_highway_mpg', $this->search_array_keys( $row, "highway_mpg", $csv ), $row, $csv )
				)
			);

			$original_price = apply_filters('automotive_import_original_price', $this->search_array_keys( $row, "original_price", $csv ), $row, $csv );
			$use_original_if_price_empty = (isset($lwp_options['use_original_price_empty']) && !empty($lwp_options['use_original_price_empty']) ? true : false);

			if($use_original_if_price_empty && empty($post_options['price']['value']) && !empty($original_price)){
				$post_options['price']['value'] = $original_price;
			} else {
				if ( ! empty( $original_price ) ) {
					$post_options['price']['original'] = $original_price;
				}
			}

			$post_meta["listing_options"] = serialize( $post_options );

			// default history image
			if ( isset( $lwp_options['default_vehicle_history']['on'] ) && $lwp_options['default_vehicle_history']['on'] == "1" ) {
				$post_meta["verified"] = "yes";
			}

			// update car_sold
			$post_meta["car_sold"] = 2;

			$car_sold_value = apply_filters('automotive_import_car_sold', $this->search_array_keys( $row, "car_sold", $csv ), $row, $csv);
			$car_sold_test  = ( isset( $lwp_options['sold_value'] ) && ! empty( $lwp_options['sold_value'] ) ? $lwp_options['sold_value'] : "" );

			if ( ! empty( $car_sold_test ) && ! empty( $car_sold_value ) && $car_sold_test == $car_sold_value ) {
				$post_meta["car_sold"] = 1;
			}

			// secondary title
			$secondary_title              = apply_filters('automotive_import_secondary_title', $this->search_array_keys( $row, "secondary_title", $csv ), $row, $csv);
			$post_meta["secondary_title"] = (string) esc_html( $secondary_title );

			// yoast
			if ( $Automotive_Plugin->is_yoast_active() ) {
				// meta desc
				$desc                               = apply_filters('automotive_import_yoast_metadesc', $this->search_array_keys( $row, "_yoast_wpseo_metadesc", $csv ), $row, $csv );
				$post_meta["_yoast_wpseo_metadesc"] = (string) esc_html( $desc );

				// focus keyword
				$keyword                           = apply_filters('automotive_import_yoast_focuskw', $this->search_array_keys( $row, "_yoast_wpseo_focuskw", $csv ), $row, $csv );
				$post_meta["_yoast_wpseo_focuskw"] = (string) esc_html( $keyword );

				// title
				if ( isset( $_POST['yoast_title_from_values'] ) && ! empty( $_POST['yoast_title_from_values'] ) ) {
					$title                           = apply_filters('automotive_import_yoast_title', $this->get_multiple_values( $_POST['yoast_title_from_values'], $row, $csv ), $row, $csv );
					$post_meta["_yoast_wpseo_title"] = (string) esc_html( $title );
				}
			}

			// newly imported page settings
			if ( isset( $lwp_options['file_import_header_image'] ) && ! empty( $lwp_options['file_import_header_image'] ) ) {
				$post_meta["header_image"] = $lwp_options['file_import_header_image']['id'];
			}

			if ( isset( $lwp_options['file_import_call_to_action'] ) && ! empty( $lwp_options['file_import_call_to_action'] ) ) {
				$post_meta["header_image"] = $lwp_options['file_import_call_to_action'];

				$post_meta["action_toggle"]      = "on";
				$post_meta["action_text"]        = (string) $lwp_options['file_import_call_to_action_text'];
				$post_meta["action_button_text"] = (string) $lwp_options['file_import_call_to_action_button_text'];
				$post_meta["action_link"]        = (string) $lwp_options['file_import_call_to_action_button_link'];
				$post_meta["action_class"]       = (string) $lwp_options['file_import_call_to_action_button_class'];
			}

			if ( isset( $lwp_options['file_import_footer_area'] ) && ! empty( $lwp_options['file_import_footer_area'] ) ) {
				$post_meta["footer_area"] = (string) $lwp_options['file_import_footer_area'];
			}

			if ( isset( $lwp_options['file_import_slideshow'] ) && ! empty( $lwp_options['file_import_slideshow'] ) ) {
				$post_meta["page_slideshow"] = (string) $lwp_options['file_import_slideshow'];
			}

			return $post_meta;
		}

		public function automotive_import_listing($array = []){
			$file_import_option = 'automotive_next_import_info';

			$return = array(
				'another_listing' => false
			);

			$automotive_next_import_info = get_option($file_import_option);

			if(!empty($automotive_next_import_info['rows'])){

				// simulate $_POST

				$_POST = $array;

				// force array output
				$automotive_next_import_info['additional_options']['output'] = 'array';

					$import_message = $this->insert_listings($automotive_next_import_info['rows'], $automotive_next_import_info['additional_options']);

					if(isset($import_message['import'])){
						reset($import_message['import']);

						$listing_id = key($import_message['import']);

						$return['message'] = '<a href="' . get_edit_post_link($listing_id) . '" target="_blank">' . $import_message['import'][$listing_id] . '</a>';
					}

					if(isset($import_message['duplicates']) && !empty($import_message['duplicates'])){
						$return['duplicates'] = array();

						foreach($import_message['duplicates'] as $duplicate_id => $title){
							$return['duplicates'][] = '<a href="' . get_edit_post_link($duplicate_id) . '" target="_blank">' . $title . '</a>';
						}
					}

			} else {
				delete_option($file_import_option);
				unset($_SESSION['auto_csv']);
			}

			echo wp_json_encode($return);

			die;
		}

		public function insert_listings( $rows, $additional_options = array() ) {
			global $Listing, $lwp_options;

			// additional options
			$output                    = ( isset( $additional_options['output'] ) && ! empty( $additional_options['output'] ) ? $additional_options['output'] : "" );
			$overwrite_listing_images  = ( isset( $additional_options['overwrite_existing_listing_images'] ) && ! empty( $additional_options['overwrite_existing_listing_images'] ) ? $additional_options['overwrite_existing_listing_images'] : "" );
			$remove_listings_not_found = ( isset( $additional_options['remove_listings_not_found'] ) && ! empty( $additional_options['remove_listings_not_found'] ) ? $additional_options['remove_listings_not_found'] : "false" );
			$args = array(
				'role'         => 'administrator',
				'orderby'      => 'registered', 
				'order'        => 'ASC', 
				'number'       => 1 
			);

			$users = get_users( $args );

			$user = get_field( 'author', 'options' ) ? get_field( 'author', 'options' ) : $users[0]->ID;
			$import_as_user            = ( isset( $additional_options['user'] ) && ! empty( $additional_options['user'] ) ? $additional_options['user'] : $user );

			$dont_overwrite_empty      = ( isset( $additional_options['dont_overwrite_empty'] ) && ! empty( $additional_options['dont_overwrite_empty'] ) ? $additional_options['dont_overwrite_empty'] : "" );

			/* Default Latitude & Longitude */
			$default_latitude  = ( isset( $lwp_options['default_value_lat'] ) && ! empty( $lwp_options['default_value_lat'] ) ? $lwp_options['default_value_lat'] : "43.653226" );
			$default_longitude = ( isset( $lwp_options['default_value_long'] ) && ! empty( $lwp_options['default_value_long'] ) ? $lwp_options['default_value_long'] : "-79.3831843" );
			$_POST = $_POST['post'];
			// if form submitted
			if ( isset( $_POST['csv'] ) && ! empty( $_POST['csv'] ) ) {

				$csv             = ( isset( $_POST['csv'] ) && ! empty( $_POST['csv'] ) ? $_POST['csv'] : "" );
				$duplicate_check = ( isset( $_POST['duplicate_check'] ) && ! empty( $_POST['duplicate_check'] ) && $_POST['duplicate_check'] !== 'none' ? $_POST['duplicate_check'] : false );

				$listing_categories_safe = $listing_categories = $Listing->get_listing_categories( true );

				$imported_listings = array();

				if ( ! empty( $csv ) ) {
					// duplicate check outside listings
					if($duplicate_check){
						$current_listings = get_posts( array(
							"post_type"      => "listings",
							"posts_per_page" => - 1,
							'post_status'    => 'any'
						) );
					} else {
						$current_listings = array();
					}

					$all_listings  = array();
					$current_check = array();
					$i             = 0;

					foreach ( $current_listings as $listing ) {
						$post_meta          = get_metadata( "post", $listing->ID );
						$post_meta['title'] = $listing->post_title;

						if ( isset( $post_meta[ $duplicate_check ] ) && is_array( $post_meta[ $duplicate_check ] ) && ! empty( $post_meta[ $duplicate_check ] ) ) {
							$check_label = ( isset( $post_meta[ $duplicate_check ][0] ) && ! empty( $post_meta[ $duplicate_check ][0] ) ? $post_meta[ $duplicate_check ][0] : "" );
							$i ++;
						} elseif ( isset( $post_meta[ $duplicate_check ] ) && ! is_array( $post_meta[ $duplicate_check ] ) && ! empty( $post_meta[ $duplicate_check ] ) ) {
							$check_label = ( isset( $post_meta[ $duplicate_check ] ) && ! empty( $post_meta[ $duplicate_check ] ) ? $post_meta[ $duplicate_check ] : "" );
						}

						if ( isset( $check_label ) ) {
							$current_check[ $listing->ID ] = $check_label;
							$all_listings[ $listing->ID ]  = $check_label;
						}
					}

					// fix for single XML
					if ( ! isset( $rows[0] ) && isset( $_SESSION['auto_csv']['file_type'] ) && $_SESSION['auto_csv']['file_type'] == "xml" ) {
						$temp_rows = $rows;
						$rows      = array();

						$rows[0] = $temp_rows;
					}

					foreach ( $rows as $key => $row ) {
						// generate post_title

						$post_title = $this->get_multiple_values( $_POST['title_from_values'], $row, $csv );

						// needs a title...
						if ( empty( $post_title ) ) {
							$post_title = "N/A";
						}

						// $post_title     = $this->search_array_keys($row, "title", $csv);
						$post_content = $this->search_array_keys( $row, "vehicle_overview", $csv );

						// update dependancies
						$dependancy_categories = array();

						$insert_info = array(
							'post_type'    => "listings",
							'post_title'   => apply_filters( 'automotive_import_title', $post_title, $row, $csv ),
							'post_content' => apply_filters( 'automotive_import_overview', $post_content, $row, $csv ),
							'post_status'  => ( isset( $lwp_options['import_post_status'] ) && ! empty( $lwp_options['import_post_status'] ) ? $lwp_options['import_post_status'] : "publish" )
						);

						$permalink = $this->search_array_keys($row, 'permalink', $csv);

						if($permalink){
							$insert_info['post_name'] = $permalink;
						}

						if ( ! empty( $import_as_user ) ) {
							$insert_info['post_author'] = apply_filters( 'automotive_import_user', $import_as_user, $row, $csv);
						}

						if ( $duplicate_check == "none" ) {
							$no_check = true;
						} elseif ( $duplicate_check != "title" ) {
							$search_value = $this->search_array_keys( $row, $duplicate_check, $csv );
						} else {
							$search_value = $post_title;
						}

						$car_sold_value = apply_filters('automotive_import_car_sold', $this->search_array_keys( $row, "car_sold", $csv ), $row, $csv);

						if ( $car_sold_value != 1 && $car_sold_value != 'Yes') {
							
							if ( ( isset( $current_check ) && ( ( isset( $search_value ) && ! in_array( $search_value, $current_check ) ) ) || ( isset( $no_check ) && $no_check ) ) ) {

								do_action( 'automotive_import_before_single_listing_import', true );

								$insert_info['meta_input'] = $this->get_listing_meta( $listing_categories, $listing_categories_safe, $dependancy_categories, $row, $csv );

								$insert_id = wp_insert_post( $insert_info );

								do_action( "wpml_admin_make_post_duplicates", $insert_id );

								/* Record inserted posts */
								$imported_listings[ $insert_id ] = ( $post_title );

								do_action( 'automotive_import_after_single_listing_import', $insert_id, true );
							}
						} else {
							$duplicate_ids = array_keys( $current_check, $search_value );

							$duplicate_ids_new = [];

							if ( ! empty( $duplicate_ids ) ) {

								foreach ( $duplicate_ids as $duplicate_id ) {

									$car_sold_value = apply_filters('automotive_import_car_sold', $this->search_array_keys( $row, "car_sold", $csv ), $row, $csv);

									if ( $car_sold_value == 1 || $car_sold_value != 'Yes' ) {
										wp_delete_post( $duplicate_id , false );
									}else{
										$duplicate_ids_new[] = $duplicate_id;
									}

								}
							}

							$duplicate_ids = $duplicate_ids_new;

							if ( ! empty( $duplicate_ids ) ) {

								foreach ( $duplicate_ids as $duplicate_id ) {
									do_action( 'automotive_import_before_single_listing_import', false );

									$update_post = get_post( $duplicate_id, ARRAY_A );

									$imported_listings['duplicate'][ $duplicate_id ] = $update_post['post_title'];

									if ( isset( $all_listings[ $duplicate_id ] ) ) {
										unset( $all_listings[ $duplicate_id ] );
									}

									// gallery images
									if ( isset( $overwrite_listing_images ) && ( $overwrite_listing_images == "true" || $overwrite_listing_images == "on" ) ) {
										$this->delete_listing_images( $duplicate_id );

										$new_gallery_images = $this->import_gallery_images( $row, $csv );

										if(!empty($new_gallery_images)){
											update_post_meta( $duplicate_id, "gallery_images", $new_gallery_images );
											update_post_meta( $duplicate_id, '_thumbnail_id', $new_gallery_images[0] );
										}
									}

									if ( ! empty( $update_post ) && isset( $_POST['overwrite_existing'] ) && $_POST['overwrite_existing'] == "on" ) {
										// generate post_title
										$new_post_title   = $update_post['post_title'];
										$new_post_content = $update_post['post_content'];

										if ( ! empty( $dont_overwrite_empty ) && ! empty( $new_post_title ) ) {
											$post_title = $new_post_title;
										} else {
											$post_title = $this->get_multiple_values( $_POST['title_from_values'], $row, $csv );

											// needs a title...
											if ( empty( $post_title ) ) {
												$post_title = "N/A";
											}
										}

										if ( ! empty( $dont_overwrite_empty ) && empty( $new_post_content ) ) {
											$post_content = $new_post_content;
										} else {
											$post_content = $this->search_array_keys( $row, "vehicle_overview", $csv );
										}

										$dependancy_categories = array();

										// update post title and content
										$update_post['post_title']   = apply_filters( 'automotive_import_title', $post_title, $row, $csv );
										$update_post['post_content'] = apply_filters( 'automotive_import_overview', $post_content, $row, $csv );
										$update_post['post_status']  = ( isset( $lwp_options['import_post_status'] ) && ! empty( $lwp_options['import_post_status'] ) ? $lwp_options['import_post_status'] : "publish" );

										$permalink = $this->search_array_keys($row, 'permalink', $csv);

										if($permalink){
											$update_post['post_name'] = $permalink;
										}

										$insert_id = $update_post['ID'];

										wp_update_post( $update_post );

										// update old information
										$listing_categories['Technical Specifications'] = array( "multiple" => true );
										$listing_categories['Other Comments']           = array( "multiple" => true );

										foreach ( $listing_categories as $key => $option ) {
											$key = ( isset( $option['slug'] ) && ! empty( $option['slug'] ) ? $option['slug'] : str_replace( " ", "_", strtolower( $key ) ) );

											if ( isset( $option['multiple'] ) ) {
												// contains multiple values, concatenate them
												$value = $this->encode_fix( $this->search_array_keys( $row, $key, $csv ) );
											} else {
												$value = $this->encode_fix( $this->search_array_keys( $row, $key, $csv ) );

												//link_value
												if ( isset( $option['link_value'] ) && ! empty( $option['link_value'] ) ) {
													if ( $option['link_value'] == "price" ) {
														$value = $this->search_array_keys( $row, "price", $csv );

														// numbers
														if ( isset( $option['is_number'] ) && $option['is_number'] == 1 ) {
															$pattern = '/\D/';

															if(isset($lwp_options['prices_include_decimals']) && $lwp_options['prices_include_decimals']){
																$pattern = '/[^0-9.]*/';
															}

															$value = preg_replace( $pattern, '', $value );
														}

														$linked_price_value = $value;
													} else if ( $option['link_value'] == "mpg" ) {
														$city_text = ( isset( $lwp_options['default_value_city'] ) && ! empty( $lwp_options['default_value_city'] ) ? $lwp_options['default_value_city'] : "" );
														$hwy_text  = ( isset( $lwp_options['default_value_hwy'] ) && ! empty( $lwp_options['default_value_hwy'] ) ? $lwp_options['default_value_hwy'] : "" );

														$city_value = $this->search_array_keys( $row, "city_mpg", $csv );
														$hwy_value  = $this->search_array_keys( $row, "highway_mpg", $csv );

														$value = $city_value . " " . $city_text . " / " . $hwy_value . " " . $hwy_text;
													}
												}

												// numbers
												if ( isset( $option['is_number'] ) && $option['is_number'] == 1 && ! isset( $linked_price_value ) ) {
													$value = preg_replace( '/\D/', '', $value );
												}

												$value = apply_filters( 'automotive_import_meta', $value, $row, $csv );

												// add value if not already added
												$terms = ( isset( $listing_categories_safe[ $key ]['terms'] ) && ! empty( $listing_categories_safe[ $key ]['terms'] ) ? $listing_categories_safe[ $key ]['terms'] : array() );
												//compare_value
												if ( is_array( $terms ) && ! in_array( $value, $terms ) && ! empty( $value ) && isset( $option['compare_value'] ) && $option['compare_value'] == "=" ) {
													$listing_categories_safe[ $key ]['terms'][ $Listing->slugify( $value ) ] = $value;
												}
											}

											if ( ( ! empty( $dont_overwrite_empty ) && ! empty( $value ) ) || empty( $dont_overwrite_empty ) ) {
												update_post_meta( $insert_id, $key, $value );

												$dependancy_categories[ $key ] = array( $Listing->slugify( $value ) => $value );
											}
										}

										// Features & Options
										$values               = $this->search_array_keys( $row, "features_and_options", $csv );
										$features_and_options = array();
										$dynamite             = ( isset( $lwp_options['features_delimiter'] ) && ! empty( $lwp_options['features_delimiter'] ) ? $lwp_options['features_delimiter'] : "" );

										$values = apply_filters('automotive_import_features_before', $values, $row, $csv);

										if ( ! empty( $values ) ) {
											if ( empty( $dynamite ) ) {
												if ( strstr( $values, "," ) ) {
													$dynamite = ",";
												} elseif ( strstr( $values, "<br>" ) ) {
													$dynamite = "<br>";
												} elseif ( strstr( $values, "|" ) ) {
													$dynamite = "|";
												}
											}

											if ( isset( $dynamite ) && ! empty( $dynamite ) ) {
												$values = explode( $dynamite, $values );

												foreach ( $values as $val ) {
													$features_and_options[] = $this->encode_fix( $val );
												}
											} else {
												$features_and_options[] = $this->encode_fix( $values );
											}
										}

										$features_and_options = apply_filters('automotive_import_features_after', $features_and_options, $row, $csv);

										if ( ! empty( $features_and_options ) ) {
											if ( ( ! empty( $dont_overwrite_empty ) && ! empty( $features_and_options ) ) || empty( $dont_overwrite_empty ) ) {
												update_post_meta( $insert_id, "multi_options", $features_and_options );
											}

											$options = (isset($listing_categories_safe['options']['terms']) && !empty($listing_categories_safe['options']['terms']) ? $listing_categories_safe['options']['terms'] : array());

											foreach ( $features_and_options as $option ) {
												if ( is_array( $options ) && ! in_array( $option, $options ) ) {
													$listing_categories_safe['options']['terms'][] = $option;
												}
											}
										}

										global $lwp_options;

										// additional detail
										if ( ! empty( $lwp_options['additional_categories']['value'] ) ) {
											foreach ( $lwp_options['additional_categories']['value'] as $key => $additional_category ) {
												if ( isset( $lwp_options['additional_categories']['check'][ $key ] ) && $lwp_options['additional_categories']['check'][ $key ] == "on" ) {
													$safe_category = str_replace( " ", "_", strtolower( $additional_category ) );

													if ( ( ! empty( $dont_overwrite_empty ) && ! empty( $safe_category ) ) || empty( $dont_overwrite_empty ) ) {
														update_post_meta( $insert_id, $safe_category, 1 );
													}
												}
											}
										}

										$latitude  = $this->search_array_keys( $row, "latitude", $csv );
										$longitude = $this->search_array_keys( $row, "longitude", $csv );

										if ( ( ! empty( $dont_overwrite_empty ) && ! empty( $latitude ) && ! empty( $longitude ) ) || empty( $dont_overwrite_empty ) ) {
											$latitude  = ( empty( $latitude ) ? $default_latitude : $latitude );
											$longitude = ( empty( $longitude ) ? $default_longitude : $longitude );

											// map location
											$location = array(
												"latitude"  => $latitude,
												"longitude" => $longitude,
												"zoom"      => ( isset( $lwp_options['default_value_zoom'] ) && ! empty( $lwp_options['default_value_zoom'] ) ? $lwp_options['default_value_zoom'] : "10" )
											);

											$location = apply_filters('automotive_import_location', $location, $row, $csv);

											update_post_meta( (int) $insert_id, "location_map", $location );

											update_post_meta( (int) $insert_id, "latitude", $location['latitude'] );
											update_post_meta( (int) $insert_id, "longitude", $location['longitude'] );
										}

										// post options (city, hwy, video)
										$video             = apply_filters('automotive_import_video', $this->search_array_keys( $row, "video", $csv ), $row, $csv);
										$custom_tax_inside = apply_filters('automotive_import_tax_inside', $this->search_array_keys( $row, "tax_label_listing", $csv ), $row, $csv);
										$custom_tax_page   = apply_filters('automotive_import_tax_page', $this->search_array_keys( $row, "tax_label_inventory", $csv ), $row, $csv);
										$city_mpg          = apply_filters('automotive_import_city_mpg', $this->search_array_keys( $row, "city_mpg", $csv ), $row, $csv);
										$highway_mpg       = apply_filters('automotive_import_highway_mpg', $this->search_array_keys( $row, "highway_mpg", $csv ), $row, $csv);

										// existing badge color and tax labels
										$get_post_options = get_post_meta( $insert_id, "listing_options" );
										$get_options      = @unserialize( $get_post_options[0] );

										$get_options['video']                = (isset($get_options['video']) ? $get_options['video'] : '');
										$get_options['custom_tax_inside']    = (isset($get_options['custom_tax_inside']) ? $get_options['custom_tax_inside'] : '');
										$get_options['custom_tax_page']      = (isset($get_options['custom_tax_page']) ? $get_options['custom_tax_page'] : '');
										$get_options['city_mpg']['value']    = (isset($get_options['city_mpg']['value']) ? $get_options['city_mpg']['value'] : '');
										$get_options['highway_mpg']['value'] = (isset($get_options['highway_mpg']['value']) ? $get_options['highway_mpg']['value'] : '');

										// dont overwrite emppty values
										$video             = ( ! empty( $dont_overwrite_empty ) && empty( $video ) ? $get_options['video'] : $video );
										$custom_tax_inside = ( ! empty( $dont_overwrite_empty ) && empty( $custom_tax_inside ) ? $get_options['custom_tax_inside'] : $custom_tax_inside );
										$custom_tax_page   = ( ! empty( $dont_overwrite_empty ) && empty( $custom_tax_page ) ? $get_options['custom_tax_page'] : $custom_tax_page );
										$city_mpg          = ( ! empty( $dont_overwrite_empty ) && empty( $city_mpg ) ? $get_options['city_mpg']['value'] : $city_mpg );
										$highway_mpg       = ( ! empty( $dont_overwrite_empty ) && empty( $highway_mpg ) ? $get_options['highway_mpg']['value'] : $highway_mpg );

										$post_options = array(
											"video"             => $video,
											"custom_tax_inside" => $custom_tax_inside,
											"custom_tax_page"   => $custom_tax_page,
											"price"             => array(
												"value" => apply_filters('automotive_import_price', ( isset( $linked_price_value ) ? $linked_price_value : $this->search_array_keys( $row, "price", $csv ) ), $row, $csv )
											),
											"city_mpg"          => array(
												"value" => $city_mpg
											),
											"highway_mpg"       => array(
												"value" => $highway_mpg
											)
										);

										$custom_badge = apply_filters('automotive_import_listing_badge', $this->search_array_keys( $row, "listing_badge", $csv ), $row, $csv);

										$post_options['custom_badge']      = ( ! empty( $custom_badge ) ? $custom_badge : (isset($get_options['custom_badge']) ? $get_options['custom_badge'] : '') );
										$post_options['custom_tax_inside'] = ( ! empty( $get_options['custom_tax_inside'] ) ? $get_options['custom_tax_inside'] : $post_options['custom_tax_inside'] );
										$post_options['custom_tax_page']   = ( ! empty( $get_options['custom_tax_page'] ) ? $get_options['custom_tax_page'] : $post_options['custom_tax_page'] );


										$original_price = apply_filters('automotive_import_original_price', $this->search_array_keys( $row, "original_price", $csv ), $row, $csv);

										if ( ! empty( $original_price ) ) {
											$post_options['price']['original'] = $original_price;
										}

										update_post_meta( $insert_id, "listing_options", serialize( $post_options ) );

										// default history image
										if ( isset( $lwp_options['default_vehicle_history']['on'] ) && $lwp_options['default_vehicle_history']['on'] == "1" ) {
											update_post_meta( $insert_id, "verified", "yes" );
										}

										// update car_sold
										$car_sold_value = apply_filters('automotive_import_car_sold', $this->search_array_keys( $row, "car_sold", $csv ), $row, $csv);
										$car_sold_test  = ( isset( $lwp_options['sold_value'] ) && ! empty( $lwp_options['sold_value'] ) ? $lwp_options['sold_value'] : "" );

										if ( ! empty( $car_sold_test ) && ! empty( $car_sold_value ) ) {
											if ( $car_sold_test == $car_sold_value ) {
												update_post_meta( $insert_id, "car_sold", 1 );
											} else {
												update_post_meta( $insert_id, "car_sold", 2 );
											}
										}

										$pdf = apply_filters('automotive_import_pdf', $this->search_array_keys( $row, "pdf", $csv ), $row, $csv);

										if ( $pdf ) {
											$pdf_id = $this->get_upload_image($pdf);

											if(!empty($pdf_id)){
												update_post_meta($insert_id, "pdf_brochure_input", $pdf_id);
											}
										}

										// secondary title
										$secondary_title = apply_filters('automotive_import_secondary_title', $this->search_array_keys( $row, "secondary_title", $csv ), $row, $csv);
										update_post_meta( $insert_id, "secondary_title", (string) esc_html( $secondary_title ) );

										// yoast
										if ( $Listing->is_yoast_active() ) {
											// meta desc
											$desc = apply_filters('automotive_import_yoast_metadesc', $this->search_array_keys( $row, "_yoast_wpseo_metadesc", $csv ), $row, $csv);

											if ( ( ! empty( $dont_overwrite_empty ) && ! empty( $desc ) ) || empty( $dont_overwrite_empty ) ) {
												update_post_meta( $insert_id, "_yoast_wpseo_metadesc", (string) esc_html( $desc ) );
											}

											// focus keyword
											$keyword = apply_filters('automotive_import_yoast_focuskw', $this->search_array_keys( $row, "_yoast_wpseo_focuskw", $csv ), $row, $csv);

											if ( ( ! empty( $dont_overwrite_empty ) && ! empty( $keyword ) ) || empty( $dont_overwrite_empty ) ) {
												update_post_meta( $insert_id, "_yoast_wpseo_focuskw", (string) esc_html( $keyword ) );
											}

											// title
											if ( isset( $_POST['yoast_title_from_values'] ) && ! empty( $_POST['yoast_title_from_values'] ) ) {
												$title = apply_filters('automotive_import_yoast_title', $this->get_multiple_values( $_POST['yoast_title_from_values'], $row, $csv ), $row, $csv);

												if ( ( ! empty( $dont_overwrite_empty ) && ! empty( $title ) ) || empty( $dont_overwrite_empty ) ) {
													update_post_meta( $insert_id, "_yoast_wpseo_title", (string) esc_html( $title ) );
												}
											}
										}
									}
								}

								do_action( 'automotive_import_after_single_listing_import', $insert_id, false );
							}
						}

						$Listing->update_listing_categories( $listing_categories_safe );
					}

					$return = ( ! empty( $output ) && $output == "array" ? array() : "" );

					$duplicates = ( isset( $imported_listings['duplicate'] ) && ! empty( $imported_listings['duplicate'] ) ? $imported_listings['duplicate'] : "" );
					unset( $imported_listings['duplicate'] );

					$no_title_available = __( "No Title Available", "listings" );
					$edit_label         = __( "Edit", "listings" );

					if ( ! empty( $imported_listings ) ) {

						if ( ! empty( $output ) && $output == "array" ) {
							$return['import'] = $imported_listings;
						} else {
							$return .= __( "Successfully imported these listings", "listings" ) . ":<br>";

							$return .= "<ul>";
							foreach ( $imported_listings as $key => $listing ) {
								if ( $key != "duplicate" ) {
									$return .= "<li><a href='" . get_permalink( $key ) . "'>" . ( ! empty( $listing ) ? $listing : $no_title_available ) . "</a> (<a href='" . get_edit_post_link( $key ) . "'>" . $edit_label . "</a>)</li>";
								}
							}
							$return .= "</ul>";
						}
					}

					if ( ! empty( $duplicates ) ) {
						if ( ! empty( $output ) && $output == "array" ) {
							$return['duplicates'] = $duplicates;
						} else {
							if ( isset( $_POST['overwrite_existing'] ) && $_POST['overwrite_existing'] == "on" ) {
								$return .= __( "These listings were updated with new information from the imported file", "listings" ) . ":<br>";
							} else {
								$return .= __( "These listings weren't imported because a duplicate listing was detected", "listings" ) . ":<br>";
							}

							$return .= "<ul>";
							foreach ( $duplicates as $key => $listing ) {
								$return .= "<li>" . ( ! empty( $listing ) ? $listing : $no_title_available ) . " (<a href='" . get_edit_post_link( $key ) . "'>" . $edit_label . "</a>)</li>";
							}
							$return .= "</ul>";
						}
					}

					if ( $remove_listings_not_found != "false" ) {
						$deleted_not_found = array();

						if ( isset( $duplicate_ids ) && ! empty( $duplicate_ids ) ) {
							$not_found_listings = array_diff( $all_listings, $duplicate_ids );

							if ( $Listing->is_wpml_active() ) {
								$languages = apply_filters( 'wpml_active_languages', null, 'orderby=id&order=desc' );
							}

							if ( ! empty( $not_found_listings ) ) {
								foreach ( $not_found_listings as $not_found_id => $not_found_title ) {

									if ( ! $Listing->is_wpml_active() ) {
										$this->delete_listing_images( $not_found_id );

										wp_delete_post( $not_found_id );
									} else {
										if ( ! empty( $languages ) ) {
											foreach ( $languages as $lang_code => $lang_info ) {
												$translated_id = apply_filters( 'wpml_object_id', $not_found_id, 'listings', false, $lang_code );

												if ( $translated_id ) {
													$this->delete_listing_images( $translated_id );

													wp_delete_post( $translated_id );
												}
											}
										}
									}

									$deleted_not_found[ $not_found_id ] = $not_found_title;
								}
							}

							if ( ! empty( $output ) && $output == "array" ) {
								$return['deleted'] = $deleted_not_found;
							} else {
								$return .= __( "These listings were deleted since they were not found in the import file", "listings" ) . ":<br>";

								$return .= "<ul>";
								foreach ( $deleted_not_found as $key => $listing ) {
									$return .= "<li>" . ( ! empty( $listing ) ? $listing : $no_title_available ) . " </li>";
								}
								$return .= "</ul>";
							}
						}
					}

					if ( empty( $output ) ) {
						$return .= "<a href='" . admin_url( "edit.php?post_type=listings&page=file-import" ) . "'><button class='button button-primary'>" . __( "Import more listings", "listings" ) . "</button></a>";
					}

					$Listing->generate_dependancy_option( true );

					return $return;
				}
			}
		}

		public function delete_listing_images( $listing_id ) {
			// remove images before deleting them...
			$gallery_images = get_post_meta( $listing_id, "gallery_images", true );

			if ( ! empty( $gallery_images ) ) {
				foreach ( $gallery_images as $image_id ) {
					$image_path = get_attached_file( $image_id );
					if ( $image_path ) {
						wp_delete_file( $image_path );
					}

					wp_delete_attachment( $image_id, true );
				}
			}
		}

		public function automotive_wpml_insert_post( $insert_info ) {
			$languages = apply_filters( 'wpml_active_languages', null, 'orderby=id&order=desc' );
			$listings  = array();

			if ( ! empty( $languages ) ) {
				foreach ( $languages as $lang_code => $lang_info ) {
					$listings[ $lang_code ] = wp_insert_post( $insert_info );
				}
			}

			// associate the posts together
			$main_translated_listing = $listings[ ICL_LANGUAGE_CODE ];
			unset( $listings[ ICL_LANGUAGE_CODE ] );

			if ( ! empty( $listings ) ) {
				foreach ( $listings as $lang_code => $listing_id ) {
					$wpml_element_type = apply_filters( 'wpml_element_type', 'listings' );
					$get_language_args = array(
						'element_id'   => $main_translated_listing,
						'element_type' => 'listings'
					);

					$original_post_language_info = apply_filters( 'wpml_element_language_details', null, $get_language_args );

					$set_language_args = array(
						'element_id'           => $listing_id,
						'element_type'         => $wpml_element_type,
						'trid'                 => $original_post_language_info->trid,
						'language_code'        => $lang_code,
						'source_language_code' => $original_post_language_info->language_code
					);

					do_action( 'wpml_set_element_language_details', $set_language_args );
				}
			}

			return $main_translated_listing;
		}

		public function get_multiple_values( $value, $row, $csv ) {
			$return = "";

			if ( ! empty( $value ) ) {
				foreach ( $value as $title_value ) {
					$return .= $this->search_array_keys( $row, $title_value, $csv, true ) . " ";
				}

				$return = rtrim( $return, " " );
			}

			return $return;
		}

		public function import_gallery_images( $row, $csv ) {
			global $Listing, $lwp_options;

			$values 				= $this->search_array_keys( $row, "gallery_images", $csv );
			$gallery_images = array();
			$dynamite       = ( isset( $lwp_options['gallery_delimiter'] ) && ! empty( $lwp_options['gallery_delimiter'] ) ? $lwp_options['gallery_delimiter'] : "" );

			if ( ! empty( $values ) ) {
				if ( empty( $dynamite ) ) {
					if ( strstr( $values, "," ) ) {
						$dynamite = ",";
					} elseif ( strstr( $values, "<br>" ) ) {
						$dynamite = "<br>";
					} elseif ( strstr( $values, "|" ) ) {
						$dynamite = "|";
					} elseif ( strstr( $values, ";" ) ) {
						$dynamite = ";";
					}
				}

				if ( isset( $dynamite ) && ! empty( $dynamite ) ) {
					$values = explode( $dynamite, $values );

					// gallery images
					$values = apply_filters( 'automotive_import_gallery_images', $values, $row, $csv );

					foreach ( $values as $val ) {
						if ( ! empty( $val ) ) {
							$val = $this->auto_add_http( trim( $val ) );

							if ( $val != "http://Array" && filter_var( $val, FILTER_VALIDATE_URL ) !== false ) {
								if ( ! isset( $lwp_options['remove_query_strings_import'] ) || ! $lwp_options['remove_query_strings_import'] ) {
									$val = preg_replace( '/\?.*/', '', $val );
								}

								if ( $Listing->is_hotlink() ) {
									$gallery_images[] = $val;
								} else {
									$upload_image = $this->get_upload_image( $val );

									if ( ! empty( $upload_image ) ) {
										$gallery_images[] = $upload_image;
									}
								}
							}
						}
					}
				} else {
					$values = $this->auto_add_http( trim( $values ) );

					if ( $values != "http://Array" && filter_var( $values, FILTER_VALIDATE_URL ) !== false ) {
						if ( ! isset( $lwp_options['remove_query_strings_import'] ) || ! $lwp_options['remove_query_strings_import'] ) {
							$values = preg_replace( '/\?.*/', '', $values );
						}

						if ( $Listing->is_hotlink() ) {
							$gallery_images[] = $values;
						} else {
							$upload_image = $this->get_upload_image( $values );

							if ( ! empty( $upload_image ) ) {
								$gallery_images[] = $upload_image;
							}
						}
					}
				}
			}

			return ( ! empty( $gallery_images ) ? $gallery_images : "" );
		}

	}
}