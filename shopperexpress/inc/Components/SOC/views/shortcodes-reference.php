<?php
/**
 * SOC Shortcodes Reference
 *
 * @package Shopperexpress
 */

defined( 'ABSPATH' ) || exit; ?>

<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Theme Shortcodes', 'shopperexpress' ); ?></div>
	<p>
		<?php esc_html_e( 'Reference of every shortcode registered by the theme — usage, parameters, and a working example.', 'shopperexpress' ); ?>
	</p>

	<table class="soc-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Shortcode', 'shopperexpress' ); ?></th>
				<th><?php esc_html_e( 'Parameters', 'shopperexpress' ); ?></th>
				<th><?php esc_html_e( 'Example', 'shopperexpress' ); ?></th>
				<th><?php esc_html_e( 'Description', 'shopperexpress' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $data['shortcodes'] as $sc ) : ?>
				<tr>
					<td><code>[<?php echo esc_html( $sc['name'] ); ?>]</code></td>
					<td>
						<?php if ( empty( $sc['params'] ) ) : ?>
							<em><?php esc_html_e( 'None', 'shopperexpress' ); ?></em>
						<?php else : ?>
							<ul class="soc-plain-list">
								<?php foreach ( $sc['params'] as $param ) : ?>
									<li>
										<code><?php echo esc_html( $param['name'] ); ?></code>
										&mdash; <?php echo esc_html( $param['description'] ); ?>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</td>
					<td><code><?php echo esc_html( $sc['example'] ); ?></code></td>
					<td><?php echo esc_html( $sc['description'] ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Custom Shortcodes', 'shopperexpress' ); ?></div>
	<p>
		<?php
		printf(
			/* translators: %s: link to Theme Options */
			wp_kses(
				__( 'Defined by editors under <strong>Theme Options &rarr; Shortcodes</strong> (a repeater of name/value pairs). Each row registers <code>[sc_{name}]</code>, which prints its configured HTML/text value as-is.', 'shopperexpress' ),
				array(
					'strong' => array(),
					'code'   => array(),
				)
			)
		);
		?>
	</p>

	<?php if ( empty( $data['custom'] ) ) : ?>
		<p><em><?php esc_html_e( 'No custom shortcodes configured yet.', 'shopperexpress' ); ?></em></p>
	<?php else : ?>
		<table class="soc-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Shortcode', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'Current Value', 'shopperexpress' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $data['custom'] as $sc ) : ?>
					<tr>
						<td><code>[<?php echo esc_html( $sc['name'] ); ?>]</code></td>
						<td><code><?php echo esc_html( $sc['value'] ); ?></code></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
