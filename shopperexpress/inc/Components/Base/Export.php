<?php
/**
 * WordPress Export and Import.
 *
 * @package Shopperexpress
 */

namespace App\Components\Base;

use App\Components\Theme_Component;

class Export implements Theme_Component {


	/**
	 * Register hooks for export and import.
	 */
	public function register(): void {
		add_action( 'add_meta_boxes_page', array( $this, 'register_meta_box' ) );
		add_action( 'admin_post_export_acf_flexible_page', array( $this, 'handle_export' ) );
		add_action( 'admin_post_import_acf_flexible_page', array( $this, 'handle_import' ) );
	}

	/**
	 * Register export/import meta box in page editor.
	 *
	 * @param \WP_Post $post The current post.
	 */
	public function register_meta_box( $post ): void {
		if (
			! current_user_can( 'manage_options' ) ||
			get_page_template_slug( $post->ID ) !== 'pages/template-flexible.php'
		) {
			return;
		}

		add_meta_box(
			'acf_flexible_export',
			'Export/Import page content',
			array( $this, 'render_meta_box' ),
			'page',
			'side',
			'default'
		);
	}

	/**
	 * Render the meta box with export and import form.
	 *
	 * @param \WP_Post $post The current post.
	 */
	public function render_meta_box( $post ): void {
		$export_url = add_query_arg(
			array(
				'action'   => 'export_acf_flexible_page',
				'post_id'  => (int) $post->ID,
				'_wpnonce' => wp_create_nonce( 'export_acf_flexible_page_' . $post->ID ),
			),
			admin_url( 'admin-post.php' )
		);

		?>
		<a href="<?php echo esc_url( $export_url ); ?>" class="button button-secondary" style="margin-bottom:8px;">
			<?php esc_html_e( 'Export to JSON', 'shopperexpress' ); ?>
		</a>
		<input type="file" id="import_json_<?php echo (int) $post->ID; ?>" accept=".json,application/json" style="margin-top:8px;">
		<button type="button" class="button button-secondary" style="margin-top:8px;" onclick="shopperImportACFFlexible_<?php echo (int) $post->ID; ?>()"> <?php esc_html_e( 'Import from JSON', 'shopperexpress' ); ?> </button>
		<script>
			function shopperImportACFFlexible_<?php echo (int) $post->ID; ?>() {

				const fileInput = document.getElementById('import_json_<?php echo (int) $post->ID; ?>');
				if (!fileInput.files.length) {
					alert('<?php echo esc_js( __( 'Please select a JSON file to import.', 'shopperexpress' ) ); ?>');
					return;
				}

				const file = fileInput.files[0];
				const formData = new FormData();
				formData.append('import_json', file);
				formData.append('action', 'import_acf_flexible_page');
				formData.append('post_id', '<?php echo (int) $post->ID; ?>');
				formData.append('_wpnonce', '<?php echo esc_js( wp_create_nonce( 'import_acf_flexible_page_' . $post->ID ) ); ?>');
				formData.append('redirect_to', '<?php echo esc_url( get_edit_post_link( $post->ID, '' ) ); ?>');

				const btn = event.target;
				btn.disabled = true;

				fetch('<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>', {
						method: 'POST',
						credentials: 'same-origin',
						body: formData,
					})
					.then(response => response.json())
					.then(data => {
						if (data.success && data.data.redirect) {
							window.location = data.data.redirect;
						} else {
							throw new Error('No redirect URL returned');
						}
					})
					.catch(err => {
						alert(<?php echo json_encode( __( 'Import failed: ', 'shopperexpress' ) ); ?> + err.message);
						btn.disabled = false;
					});
			}
		</script>
		<?php
	}


	/**
	 * Handle the export of page data as JSON.
	 */
	public function handle_export(): void {
		remove_all_shortcodes();
		$post_id = isset( $_REQUEST['post_id'] ) ? (int) $_REQUEST['post_id'] : 0;

		if (
			! $post_id ||
			! current_user_can( 'manage_options' ) ||
			! isset( $_REQUEST['_wpnonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'export_acf_flexible_page_' . $post_id )
		) {
			wp_die( 'Access denied' );
		}

		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== 'page' ) {
			wp_die( 'Invalid page' );
		}

		// Gather all post info.
		$data = array();

		// Basic post fields.
		$data['ID']           = $post->ID;
		$data['post_title']   = $post->post_title;
		$data['post_content'] = $post->post_content;
		$data['post_excerpt'] = $post->post_excerpt;
		$data['post_status']  = $post->post_status;
		$data['post_author']  = $post->post_author;
		$data['post_date']    = $post->post_date;
		$data['post_type']    = $post->post_type;
		$data['post_name']    = $post->post_name;
		$data['post_parent']  = $post->post_parent;

		// Meta fields.
		$raw_meta = get_post_meta( $post->ID );
		$meta     = array();
		if ( is_array( $raw_meta ) ) {
			foreach ( $raw_meta as $key => $val ) {
				// get_post_meta returns arrays for every key, usually value in [0].
				if ( is_array( $val ) && count( $val ) === 1 ) {
					$meta[ $key ] = maybe_unserialize( $val[0] );
				} else {
					$meta[ $key ] = array_map( 'maybe_unserialize', $val );
				}
			}
		}
		$data['meta'] = $meta;

		// ACF fields (e.g. via get_fields()).
		if ( function_exists( 'get_fields' ) ) {
			$acf = get_fields( $post->ID );
			if ( is_array( $acf ) ) {
				$data['acf'] = $acf;
			} else {
				$data['acf'] = array();
			}
		} else {
			$data['acf'] = array();
		}

		// Optionally, include taxonomies (tags, categories).
		$taxes              = get_object_taxonomies( $post->post_type );
		$data['taxonomies'] = array();
		if ( is_array( $taxes ) ) {
			foreach ( $taxes as $taxonomy ) {
				$terms = get_the_terms( $post->ID, $taxonomy );
				if ( ! is_wp_error( $terms ) && is_array( $terms ) ) {
					$data['taxonomies'][ $taxonomy ] = array();
					foreach ( $terms as $term ) {
						$data['taxonomies'][ $taxonomy ][] = array(
							'term_id'     => $term->term_id,
							'name'        => $term->name,
							'slug'        => $term->slug,
							'taxonomy'    => $term->taxonomy,
							'description' => $term->description,
						);
					}
				}
			}
		}

		// Output as downloadable JSON.
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=page-' . $post_id . '-export.json' );

		echo wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		exit;
	}

	/**
	 * Handle the import of JSON data and update all post fields.
	 */
	public function handle_import(): void {

		if ( ! isset( $_POST['post_id'], $_POST['_wpnonce'] ) || ! isset( $_FILES['import_json'] ) ) {
			wp_die( __( 'Invalid import request.', 'shopperexpress' ) );
		}

		$post_id = (int) $_POST['post_id'];

		if (
			! $post_id ||
			! current_user_can( 'manage_options' ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'import_acf_flexible_page_' . $post_id )
		) {
			wp_die( __( 'Access denied.', 'shopperexpress' ) );
		}

		// Check uploaded file.
		$file = $_FILES['import_json'];
		if ( empty( $file['size'] ) || $file['error'] !== UPLOAD_ERR_OK ) {
			wp_die( __( 'File upload error.', 'shopperexpress' ) );
		}

		$file_contents = file_get_contents( $file['tmp_name'] );
		if ( ! $file_contents ) {
			wp_die( __( 'Failed to read import file.', 'shopperexpress' ) );
		}

		$data = json_decode( $file_contents, true );
		if ( ! is_array( $data ) ) {
			wp_die( __( 'Invalid JSON in import file.', 'shopperexpress' ) );
		}

		$postarr = array(
			'ID' => $post_id,
		);

		// Update main post fields.
		foreach (
			array(
				'post_title',
				'post_content',
				'post_excerpt',
				'post_status',
				'post_author',
				'post_date',
				'post_type',
				'post_name',
				'post_parent',
			) as $field
		) {
			if ( isset( $data[ $field ] ) ) {
				$postarr[ $field ] = do_shortcode( $data[ $field ] );
			}
		}

		// Only update post type if page, for safety.
		$postarr['post_type'] = 'page';

		wp_update_post( $postarr );

		// Update post meta.
		if ( ! empty( $data['meta'] ) && is_array( $data['meta'] ) ) {
			foreach ( $data['meta'] as $key => $val ) {
				delete_post_meta( $post_id, $key );

				if ( is_array( $val ) && ( array_keys( $val ) === range( 0, count( $val ) - 1 ) ) ) {
					foreach ( $val as $item ) {
						add_post_meta( $post_id, $key, $item );
					}
				} else {
					update_post_meta( $post_id, $key, $val );
				}
			}
		}

		// Update ACF fields (if present).
		if ( ! empty( $data['acf'] ) && is_array( $data['acf'] ) && function_exists( 'update_field' ) ) {
			foreach ( $data['acf'] as $acf_key => $acf_val ) {
				update_field( $acf_key, $acf_val, $post_id );
			}
		}

		// Update taxonomies.
		/*
		if ( ! empty( $data['taxonomies'] ) && is_array( $data['taxonomies'] ) ) {
			foreach ( $data['taxonomies'] as $tax => $terms ) {
				if ( taxonomy_exists( $tax ) ) {

					$slugs = array();

					foreach ( $terms as $term_arr ) {
						if ( ! empty( $term_arr['slug'] ) ) {
							$slugs[] = $term_arr['slug'];

							if ( ! get_term_by( 'slug', $term_arr['slug'], $tax ) ) {
								wp_insert_term(
									$term_arr['name'],
									$tax,
									array(
										'slug'        => $term_arr['slug'],
										'description' => isset( $term_arr['description'] ) ? $term_arr['description'] : '',
									)
								);
							}
						}
					}

					if ( ! empty( $slugs ) ) {
						wp_set_object_terms( $post_id, $slugs, $tax );
					} else {
						wp_set_object_terms( $post_id, array(), $tax );
					}
				}
			}
		}
		*/
		// Redirect back to the edit page for this post
		$redirect_to = isset( $_POST['redirect_to'] ) ? sanitize_text_field( wp_unslash( $_POST['redirect_to'] ) ) : '';

		if ( empty( $redirect_to ) ) {
			$redirect_to = add_query_arg(
				array(
					'post'   => $post_id,
					'action' => 'edit',
				),
				admin_url( 'post.php' )
			);
		}

		wp_send_json_success(
			array(
				'redirect' => $redirect_to,
			)
		);
		exit;
	}
}
