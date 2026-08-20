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
		<?php if ( isset( $group_tabs ) && count( $group_tabs ) > 1 ) : ?>
			<nav class="soc-tabs nav-tab-wrapper">
				<?php foreach ( $group_tabs as $tab_module ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=soc-' . $tab_module->get_slug() ) ); ?>"
						class="nav-tab soc-tab<?php echo $tab_module->get_slug() === $active_slug ? ' nav-tab-active' : ''; ?>">
						<span class="dashicons <?php echo esc_attr( $tab_module->get_icon() ); ?>"></span>
						<?php echo esc_html( $tab_module->get_label() ); ?>
					</a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>
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
