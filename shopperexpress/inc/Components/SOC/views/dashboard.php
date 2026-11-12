<?php
/**
 * SOC Dashboard
 *
 * @package Shopperexpress
 */

defined( 'ABSPATH' ) || exit; ?>
<div class="wrap soc-wrap">
	<div class="soc-shell">

		<header class="soc-topbar">
			<div class="soc-topbar__brand">
				<span class="soc-topbar__mark dashicons dashicons-screenoptions" aria-hidden="true"></span>
				<div class="soc-topbar__titles">
					<span class="soc-topbar__title"><?php esc_html_e( 'Operation Center', 'shopperexpress' ); ?></span>
					<span class="soc-topbar__sub">
						<?php
						printf(
							/* translators: %s: theme version */
							esc_html__( 'Theme version %s', 'shopperexpress' ),
							esc_html( wp_get_theme()->get( 'Version' ) )
						);
						?>
					</span>
				</div>
			</div>
			<div class="soc-topbar__meta">
				<span class="soc-topbar__meta-label"><?php esc_html_e( 'Mode', 'shopperexpress' ); ?></span>
				<span class="soc-badge soc-badge--ok">
					<?php echo \App\is_api_mode() ? esc_html__( 'API', 'shopperexpress' ) : esc_html__( 'CPT', 'shopperexpress' ); ?>
				</span>
			</div>
		</header>

		<div class="soc-body">

			<aside class="soc-nav" id="soc-nav">
				<?php foreach ( $nav_groups['groups'] as $group ) : ?>
					<?php if ( empty( $group['items'] ) ) continue; ?>
					<div class="soc-nav__group<?php echo $group['active'] ? ' is-open' : ''; ?>" data-soc-nav-group>
						<button type="button" class="soc-nav__group-toggle" aria-expanded="<?php echo $group['active'] ? 'true' : 'false'; ?>">
							<span class="dashicons <?php echo esc_attr( $group['icon'] ); ?>" aria-hidden="true"></span>
							<span class="soc-nav__group-label"><?php echo esc_html( $group['label'] ); ?></span>
							<span class="dashicons dashicons-arrow-down-alt2 soc-nav__group-chevron" aria-hidden="true"></span>
						</button>
						<div class="soc-nav__group-items">
							<?php foreach ( $group['items'] as $item ) : ?>
								<a href="<?php echo esc_url( $item['url'] ); ?>"
									class="soc-nav__item<?php echo $item['active'] ? ' is-active' : ''; ?>"
									data-soc-nav-item
									data-label="<?php echo esc_attr( strtolower( $item['label'] ) ); ?>">
									<?php echo esc_html( $item['label'] ); ?>
								</a>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</aside>

			<main class="soc-main" id="soc-panel" data-module="<?php echo esc_attr( $active_slug ); ?>">
				<div class="soc-main__header">
					<div class="soc-main__titles">
						<nav class="soc-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'shopperexpress' ); ?>">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=soc-' . self::GROUPS['core']['modules'][0] ) ); ?>"><?php esc_html_e( 'Operation Center', 'shopperexpress' ); ?></a>
							<span aria-hidden="true">/</span>
							<span><?php echo esc_html( self::GROUPS[ $active_group ]['label'] ); ?></span>
						</nav>
						<h1>
							<span class="dashicons <?php echo esc_attr( $module->get_icon() ); ?>" aria-hidden="true"></span>
							<?php echo esc_html( $module->get_label() ); ?>
						</h1>
					</div>
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
	</div>
</div>
