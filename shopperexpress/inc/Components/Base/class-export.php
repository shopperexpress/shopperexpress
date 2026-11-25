<?php
/**
 * WordPress Export and Import.
 *
 * @package Shopperexpress
 */

namespace App\Components\Base;

use App\Components\Theme_Component;

/**
 * WordPress Export and Import.
 */
class Export implements Theme_Component {


	/**
	 * Register hooks for export and import.
	 */
	public function register(): void {
		add_action( 'add_meta_boxes_page', array( $this, 'register_meta_box' ) );
		add_action( 'admin_post_export_acf_flexible_page', array( $this, 'handle_export' ) );
		add_action( 'admin_post_import_acf_flexible_page', array( $this, 'handle_import' ) );
		add_action( 'admin_post_flatten_page_shortcodes', array( $this, 'handle_flatten_shortcodes' ) );

		add_action( 'acf/render_field/key=field_660eebbf15403', array( $this, 'render_shortcodes_export_import' ) );
		add_action( 'admin_post_export_theme_shortcodes', array( $this, 'handle_shortcodes_export' ) );
		add_action( 'admin_post_import_theme_shortcodes', array( $this, 'handle_shortcodes_import' ) );
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
		$id = (int) $post->ID;

		$export_url = add_query_arg(
			array(
				'action'   => 'export_acf_flexible_page',
				'post_id'  => $id,
				'_wpnonce' => wp_create_nonce( 'export_acf_flexible_page_' . $id ),
			),
			admin_url( 'admin-post.php' )
		);

		$flatten_url = add_query_arg(
			array(
				'action'   => 'flatten_page_shortcodes',
				'post_id'  => $id,
				'_wpnonce' => wp_create_nonce( 'flatten_page_shortcodes_' . $id ),
			),
			admin_url( 'admin-post.php' )
		);

		$confirm = __( 'Replace shortcodes in the page title, excerpt and SEO title/description with their rendered text and reload the page? Unsaved changes in the editor will be lost. This cannot be undone.', 'shopperexpress' );
		?>
		<style>
			#acf_flexible_export .inside { margin: 0; padding: 0; }
			.se-xi-section { padding: 14px 12px; display: flex; flex-direction: column; gap: 10px; border-bottom: 1px solid #f0f0f1; }
			.se-xi-section:last-child { border-bottom: 0; }
			.se-xi-label { font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: #50575e; }
			.se-xi-section p { margin: 0; color: #50575e; line-height: 1.5; }
			.se-xi-section p.se-xi-note { font-size: 12px; }
			.se-xi-section .button { display: flex; align-items: center; justify-content: center; gap: 8px; height: 36px; margin: 0; text-align: center; }
			.se-xi-section .button { line-height: 1; padding-top: 0; padding-bottom: 0; }
			.se-xi-section .button .dashicons { display: block; flex: none; margin: 0; font-size: 16px; width: 16px; height: 16px; line-height: 16px; vertical-align: top; }
			.se-xi-section .button .dashicons::before { display: block; line-height: 16px; }
			.se-xi-drop { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 18px 12px; border: 1px dashed #8c8f94; border-radius: 3px; background: #f6f7f7; text-align: center; cursor: pointer; position: relative; }
			.se-xi-drop.is-over, .se-xi-drop:focus-within { border-color: #2271b1; background: #f0f6fc; }
			.se-xi-drop .dashicons { color: #2271b1; font-size: 22px; width: 22px; height: 22px; }
			.se-xi-drop input[type=file] { position: absolute; width: 1px; height: 1px; opacity: 0; }
			.se-xi-drop u { color: #2271b1; }
			.se-xi-file { word-break: break-all; font-weight: 600; }
		</style>

		<div class="se-xi-section">
			<div class="se-xi-label"><?php esc_html_e( 'Export', 'shopperexpress' ); ?></div>
			<p><?php esc_html_e( 'Download title, SEO fields and all blocks of this page as a JSON file.', 'shopperexpress' ); ?></p>
			<a href="<?php echo esc_url( $export_url ); ?>" class="button button-primary">
				<span class="dashicons dashicons-download" aria-hidden="true"></span>
				<?php esc_html_e( 'Export to JSON', 'shopperexpress' ); ?>
			</a>
		</div>

		<div class="se-xi-section">
			<div class="se-xi-label"><?php esc_html_e( 'Import', 'shopperexpress' ); ?></div>
			<label class="se-xi-drop" id="se-xi-drop-<?php echo $id; ?>">
				<span class="dashicons dashicons-upload" aria-hidden="true"></span>
				<span class="se-xi-text"><?php esc_html_e( 'Drop .json file here', 'shopperexpress' ); ?></span>
				<span class="se-xi-sub" style="font-size:12px;color:#50575e;">
					<?php esc_html_e( 'or', 'shopperexpress' ); ?> <u><?php esc_html_e( 'choose file', 'shopperexpress' ); ?></u>
				</span>
				<input type="file" id="import_json_<?php echo $id; ?>" accept=".json,application/json">
			</label>
			<button type="button" class="button" id="se-xi-import-<?php echo $id; ?>" disabled><?php esc_html_e( 'Import from JSON', 'shopperexpress' ); ?></button>
			<p class="se-xi-note"><?php esc_html_e( 'Import replaces the current title, SEO fields and content.', 'shopperexpress' ); ?></p>
		</div>

		<div class="se-xi-section">
			<div class="se-xi-label"><?php esc_html_e( 'Tools', 'shopperexpress' ); ?></div>
			<a href="<?php echo esc_url( $flatten_url ); ?>" class="button" onclick="return confirm(<?php echo esc_attr( wp_json_encode( $confirm ) ); ?>);">
				<?php esc_html_e( 'Convert title/SEO shortcodes to text', 'shopperexpress' ); ?>
			</a>
			<p class="se-xi-note"><?php esc_html_e( 'Replaces shortcodes in the title and SEO fields with their current values.', 'shopperexpress' ); ?></p>
		</div>

		<script>
			(function () {
				const input = document.getElementById('import_json_<?php echo $id; ?>');
				const drop = document.getElementById('se-xi-drop-<?php echo $id; ?>');
				const btn = document.getElementById('se-xi-import-<?php echo $id; ?>');
				const text = drop.querySelector('.se-xi-text');
				const sub = drop.querySelector('.se-xi-sub');

				function sync() {
					const has = input.files.length > 0;
					btn.disabled = !has;
					btn.classList.toggle('button-primary', has);
					if (has) {
						text.textContent = input.files[0].name;
						text.classList.add('se-xi-file');
						sub.style.display = 'none';
					}
				}

				input.addEventListener('change', sync);
				['dragenter', 'dragover'].forEach(function (e) {
					drop.addEventListener(e, function (ev) { ev.preventDefault(); drop.classList.add('is-over'); });
				});
				['dragleave', 'drop'].forEach(function (e) {
					drop.addEventListener(e, function (ev) { ev.preventDefault(); drop.classList.remove('is-over'); });
				});
				drop.addEventListener('drop', function (ev) {
					if (ev.dataTransfer && ev.dataTransfer.files.length) {
						input.files = ev.dataTransfer.files;
						sync();
					}
				});

				btn.addEventListener('click', function () {
					if (!input.files.length) {
						alert(<?php echo wp_json_encode( __( 'Please select a JSON file to import.', 'shopperexpress' ) ); ?>);
						return;
					}

					const formData = new FormData();
					formData.append('import_json', input.files[0]);
					formData.append('action', 'import_acf_flexible_page');
					formData.append('post_id', '<?php echo $id; ?>');
					formData.append('_wpnonce', <?php echo wp_json_encode( wp_create_nonce( 'import_acf_flexible_page_' . $id ) ); ?>);
					formData.append('redirect_to', <?php echo wp_json_encode( get_edit_post_link( $id, '' ) ); ?>);

					btn.disabled = true;

					fetch(<?php echo wp_json_encode( admin_url( 'admin-post.php' ) ); ?>, {
						method: 'POST',
						credentials: 'same-origin',
						body: formData,
					})
						.then(function (response) { return response.json(); })
						.then(function (data) {
							if (data.success && data.data.redirect) {
								window.location = data.data.redirect;
							} else {
								throw new Error('No redirect URL returned');
							}
						})
						.catch(function (err) {
							alert(<?php echo wp_json_encode( __( 'Import failed: ', 'shopperexpress' ) ); ?> + err.message);
							btn.disabled = false;
						});
				});
			})();
		</script>
		<?php
	}

	/**
	 * Render Export/Import buttons below the Shortcodes repeater on the Theme Options page.
	 */
	public function render_shortcodes_export_import(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$export_url = add_query_arg(
			array(
				'action'   => 'export_theme_shortcodes',
				'_wpnonce' => wp_create_nonce( 'export_theme_shortcodes' ),
			),
			admin_url( 'admin-post.php' )
		);
		?>
		<div class="acf-shortcodes-export-import" style="margin-top:12px; padding-top:12px; border-top:1px solid #dedede;">
			<a href="<?php echo esc_url( $export_url ); ?>" class="button button-secondary" style="margin-right:8px;">
				<?php esc_html_e( 'Export shortcodes to JSON', 'shopperexpress' ); ?>
			</a>
			<input type="file" id="import_theme_shortcodes_file" accept=".json,application/json">
			<button type="button" class="button button-secondary" onclick="shopperImportThemeShortcodes()">
				<?php esc_html_e( 'Import from JSON', 'shopperexpress' ); ?>
			</button>
		</div>
		<script>
			function shopperImportThemeShortcodes() {
				const fileInput = document.getElementById('import_theme_shortcodes_file');
				if (!fileInput.files.length) {
					alert('<?php echo esc_js( __( 'Please select a JSON file to import.', 'shopperexpress' ) ); ?>');
					return;
				}

				const file = fileInput.files[0];
				const formData = new FormData();
				formData.append('import_json', file);
				formData.append('action', 'import_theme_shortcodes');
				formData.append('_wpnonce', '<?php echo esc_js( wp_create_nonce( 'import_theme_shortcodes' ) ); ?>');

				const btn = event.target;
				btn.disabled = true;

				fetch('<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>', {
						method: 'POST',
						credentials: 'same-origin',
						body: formData,
					})
					.then(response => response.json())
					.then(data => {
						if (data.success) {
							window.location.reload();
						} else {
							throw new Error(data.data && data.data.message ? data.data.message : 'Import failed');
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
	 * Export the Shortcodes repeater field (Theme Options) as a downloadable JSON file.
	 */
	public function handle_shortcodes_export(): void {
		if (
			! current_user_can( 'manage_options' ) ||
			! isset( $_REQUEST['_wpnonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'export_theme_shortcodes' )
		) {
			wp_die( 'Access denied' );
		}

		$rows = function_exists( 'get_field' ) ? get_field( 'shortcodes', 'option' ) : array();
		$data = array(
			'field'     => 'shortcodes',
			'exported'  => gmdate( 'c' ),
			'shortcodes' => is_array( $rows ) ? $rows : array(),
		);

		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=theme-shortcodes-export-' . gmdate( 'Y-m-d' ) . '.json' );

		echo wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		exit;
	}

	/**
	 * Import the Shortcodes repeater field (Theme Options) from an uploaded JSON file.
	 */
	public function handle_shortcodes_import(): void {
		if (
			! current_user_can( 'manage_options' ) ||
			! isset( $_POST['_wpnonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'import_theme_shortcodes' ) ||
			! isset( $_FILES['import_json'] )
		) {
			wp_send_json_error( array( 'message' => __( 'Access denied.', 'shopperexpress' ) ) );
		}

		$file = $_FILES['import_json'];
		if ( empty( $file['size'] ) || $file['error'] !== UPLOAD_ERR_OK ) {
			wp_send_json_error( array( 'message' => __( 'File upload error.', 'shopperexpress' ) ) );
		}

		$file_contents = file_get_contents( $file['tmp_name'] );
		$data          = $file_contents ? json_decode( $file_contents, true ) : null;

		if ( ! is_array( $data ) || ! isset( $data['shortcodes'] ) || ! is_array( $data['shortcodes'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid shortcodes JSON file.', 'shopperexpress' ) ) );
		}

		$rows = array();
		foreach ( $data['shortcodes'] as $row ) {
			if ( ! is_array( $row ) || ! isset( $row['shortcode_name'] ) ) {
				continue;
			}
			$rows[] = array(
				'shortcode_name'  => sanitize_text_field( $row['shortcode_name'] ),
				'shortcode_value' => isset( $row['shortcode_value'] ) ? wp_kses_post( $row['shortcode_value'] ) : '',
			);
		}

		if ( function_exists( 'update_field' ) ) {
			update_field( 'shortcodes', $rows, 'option' );
		}

		wp_send_json_success( array( 'imported' => count( $rows ) ) );
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
	 * Replace shortcodes with their rendered text in the post title, excerpt and
	 * Yoast SEO title / meta description (+ social variants), then return to the editor.
	 * The post content is left untouched.
	 */
	public function handle_flatten_shortcodes(): void {
		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;

		if (
			! $post_id ||
			! current_user_can( 'manage_options' ) ||
			! isset( $_GET['_wpnonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'flatten_page_shortcodes_' . $post_id )
		) {
			wp_die( esc_html__( 'Access denied.', 'shopperexpress' ) );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			wp_die( esc_html__( 'Post not found.', 'shopperexpress' ) );
		}

		// Shortcodes such as [year] / [make] read the current post.
		$GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		setup_postdata( $post );

		$postarr = array( 'ID' => $post_id );
		foreach ( array( 'post_title', 'post_excerpt' ) as $field ) {
			if ( false !== strpos( $post->$field, '[' ) ) {
				$postarr[ $field ] = trim( wp_strip_all_tags( do_shortcode( $post->$field ) ) );
			}
		}
		if ( count( $postarr ) > 1 ) {
			wp_update_post( wp_slash( $postarr ) );
		}

		foreach ( array(
			'_yoast_wpseo_title',
			'_yoast_wpseo_metadesc',
			'_yoast_wpseo_opengraph-title',
			'_yoast_wpseo_opengraph-description',
			'_yoast_wpseo_twitter-title',
			'_yoast_wpseo_twitter-description',
		) as $key ) {
			$value = (string) get_post_meta( $post_id, $key, true );
			if ( false !== strpos( $value, '[' ) ) {
				update_post_meta( $post_id, $key, trim( wp_strip_all_tags( do_shortcode( $value ) ) ) );
			}
		}

		wp_safe_redirect( get_edit_post_link( $post_id, 'raw' ) );
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
