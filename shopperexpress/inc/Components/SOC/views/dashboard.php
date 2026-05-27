<?php
/**
 * SOC Dashboard
 *
 * @package Shopperexpress
 */

defined( 'ABSPATH' ) || exit; ?>
<div class="wrap soc-wrap">
	<h1 class="soc-title">
		<span class="dashicons dashicons-screenoptions"></span>
		<?php esc_html_e( 'Operation Center', 'shopperexpress' ); ?>
		<span class="soc-version"><?php echo esc_html( wp_get_theme()->get( 'Version' ) ); ?></span>
	</h1>

	<main class="soc-content" id="soc-panel" data-module="<?php echo esc_attr( $active_slug ); ?>">
		<div class="soc-panel-header">
			<h2>
				<span class="dashicons <?php echo esc_attr( $module->get_icon() ); ?>"></span>
				<?php echo esc_html( $module->get_label() ); ?>
			</h2>
			<button class="button soc-refresh-btn" data-module="<?php echo esc_attr( $active_slug ); ?>">
				<span class="dashicons dashicons-update"></span>
				<?php esc_html_e( 'Refresh', 'shopperexpress' ); ?>
			</button>
		</div>
		<div class="soc-panel-body" id="soc-panel-body">
			<?php $module->render( $data ); ?>
		</div>
	</main>
</div>
