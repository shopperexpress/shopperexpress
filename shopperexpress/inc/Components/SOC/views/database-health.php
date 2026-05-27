<?php
/**
 * SOC Database Health
 *
 * @package Shopperexpress
 */

defined( 'ABSPATH' ) || exit; ?>

<div id="soc-action-notice" class="soc-notice" role="alert"></div>

<!-- 1. Overview Cards -->
<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Database Overview', 'shopperexpress' ); ?></div>
	<div class="soc-grid">
		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'DB Size', 'shopperexpress' ); ?></div>
			<div class="soc-card__value"><?php echo esc_html( number_format( (float) ( $data['total_size_mb'] ?? 0 ), 2 ) ); ?></div>
			<div class="soc-card__sub">MB</div>
		</div>
		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Autoload Size', 'shopperexpress' ); ?></div>
			<div class="soc-card__value"><?php echo esc_html( number_format( (float) ( $data['autoload_size_kb'] ?? 0 ), 2 ) ); ?></div>
			<div class="soc-card__sub">KB</div>
		</div>
		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Orphan Transients', 'shopperexpress' ); ?></div>
			<div class="soc-card__value"><?php echo esc_html( $data['orphan_transients'] ?? 0 ); ?></div>
		</div>
		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Post Revisions', 'shopperexpress' ); ?></div>
			<div class="soc-card__value"><?php echo esc_html( $data['post_revisions_count'] ?? 0 ); ?></div>
		</div>
		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Spam Comments', 'shopperexpress' ); ?></div>
			<div class="soc-card__value"><?php echo esc_html( $data['spam_count'] ?? 0 ); ?></div>
		</div>
		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Trash Posts', 'shopperexpress' ); ?></div>
			<div class="soc-card__value"><?php echo esc_html( $data['trash_count'] ?? 0 ); ?></div>
			<?php if ( ! empty( $data['trash_by_type'] ) ) : ?>
				<div class="soc-card__sub">
					<?php
					$parts = array();
					foreach ( $data['trash_by_type'] as $pt => $cnt ) {
						$parts[] = esc_html( $pt ) . ':&nbsp;' . esc_html( $cnt );
					}
					echo implode( ', ', $parts ); // phpcs:ignore WordPress.Security.EscapeOutput
					?>
				</div>
			<?php endif; ?>
		</div>
		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Auto-Drafts', 'shopperexpress' ); ?></div>
			<div class="soc-card__value"><?php echo esc_html( $data['auto_drafts_count'] ?? 0 ); ?></div>
		</div>
		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Orphan Postmeta', 'shopperexpress' ); ?></div>
			<div class="soc-card__value"><?php echo esc_html( $data['orphan_postmeta_count'] ?? 0 ); ?></div>
		</div>
		<div class="soc-card">
			<div class="soc-card__label"><?php esc_html_e( 'Orphan Term Rels', 'shopperexpress' ); ?></div>
			<div class="soc-card__value"><?php echo esc_html( $data['orphan_term_rels_count'] ?? 0 ); ?></div>
		</div>
	</div>
</div>

<!-- 2. Cleanup Section -->
<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Cleanup Tools', 'shopperexpress' ); ?></div>
	<table class="soc-table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Item', 'shopperexpress' ); ?></th>
				<th><?php esc_html_e( 'Scope', 'shopperexpress' ); ?></th>
				<th><?php esc_html_e( 'Count', 'shopperexpress' ); ?></th>
				<th><?php esc_html_e( 'Action', 'shopperexpress' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr class="soc-cleanup-row">
				<td><?php esc_html_e( 'Post Revisions', 'shopperexpress' ); ?></td>
				<td><span class="soc-badge soc-badge--neutral"><?php esc_html_e( 'wp_posts + postmeta + term_rels', 'shopperexpress' ); ?></span></td>
				<td><span class="soc-cleanup-count"><?php echo esc_html( $data['post_revisions_count'] ?? 0 ); ?></span></td>
				<td>
					<button class="button soc-db-cleanup" data-cleanup-type="revisions">
						<?php esc_html_e( 'Clean Up', 'shopperexpress' ); ?>
					</button>
				</td>
			</tr>
			<tr class="soc-cleanup-row">
				<td><?php esc_html_e( 'Spam Comments', 'shopperexpress' ); ?></td>
				<td><span class="soc-badge soc-badge--neutral"><?php esc_html_e( 'wp_comments + commentmeta', 'shopperexpress' ); ?></span></td>
				<td><span class="soc-cleanup-count"><?php echo esc_html( $data['spam_count'] ?? 0 ); ?></span></td>
				<td>
					<button class="button soc-db-cleanup" data-cleanup-type="spam">
						<?php esc_html_e( 'Clean Up', 'shopperexpress' ); ?>
					</button>
				</td>
			</tr>
			<tr class="soc-cleanup-row">
				<td>
					<?php esc_html_e( 'Trash Posts', 'shopperexpress' ); ?>
					<?php if ( ! empty( $data['trash_by_type'] ) ) : ?>
						<div style="margin-top:3px; font-size:11px; color:#8c8f94;">
							<?php
							foreach ( $data['trash_by_type'] as $pt => $cnt ) {
								echo '<span style="margin-right:6px;">' . esc_html( $pt ) . ': <strong>' . esc_html( $cnt ) . '</strong></span>';
							}
							?>
						</div>
					<?php endif; ?>
				</td>
				<td><span class="soc-badge soc-badge--neutral"><?php esc_html_e( 'all post types, postmeta + term_rels', 'shopperexpress' ); ?></span></td>
				<td><span class="soc-cleanup-count"><?php echo esc_html( $data['trash_count'] ?? 0 ); ?></span></td>
				<td>
					<button class="button soc-db-cleanup" data-cleanup-type="trash">
						<?php esc_html_e( 'Clean Up', 'shopperexpress' ); ?>
					</button>
				</td>
			</tr>
			<tr class="soc-cleanup-row">
				<td><?php esc_html_e( 'Auto-Drafts', 'shopperexpress' ); ?></td>
				<td><span class="soc-badge soc-badge--neutral"><?php esc_html_e( 'all post types, postmeta + term_rels', 'shopperexpress' ); ?></span></td>
				<td><span class="soc-cleanup-count"><?php echo esc_html( $data['auto_drafts_count'] ?? 0 ); ?></span></td>
				<td>
					<button class="button soc-db-cleanup" data-cleanup-type="auto_drafts">
						<?php esc_html_e( 'Clean Up', 'shopperexpress' ); ?>
					</button>
				</td>
			</tr>
			<tr class="soc-cleanup-row">
				<td><?php esc_html_e( 'Orphan Transients', 'shopperexpress' ); ?></td>
				<td><span class="soc-badge soc-badge--neutral"><?php esc_html_e( 'wp_options', 'shopperexpress' ); ?></span></td>
				<td><span class="soc-cleanup-count"><?php echo esc_html( $data['orphan_transients'] ?? 0 ); ?></span></td>
				<td>
					<button class="button soc-db-cleanup" data-cleanup-type="orphan">
						<?php esc_html_e( 'Clean Up', 'shopperexpress' ); ?>
					</button>
				</td>
			</tr>
			<tr class="soc-cleanup-row">
				<td><?php esc_html_e( 'Orphaned Postmeta', 'shopperexpress' ); ?></td>
				<td><span class="soc-badge soc-badge--neutral"><?php esc_html_e( 'wp_postmeta (deleted posts)', 'shopperexpress' ); ?></span></td>
				<td><span class="soc-cleanup-count"><?php echo esc_html( $data['orphan_postmeta_count'] ?? 0 ); ?></span></td>
				<td>
					<button class="button soc-db-cleanup" data-cleanup-type="orphan_postmeta">
						<?php esc_html_e( 'Clean Up', 'shopperexpress' ); ?>
					</button>
				</td>
			</tr>
			<tr class="soc-cleanup-row">
				<td><?php esc_html_e( 'Orphaned Term Relationships', 'shopperexpress' ); ?></td>
				<td><span class="soc-badge soc-badge--neutral"><?php esc_html_e( 'wp_term_relationships (deleted posts)', 'shopperexpress' ); ?></span></td>
				<td><span class="soc-cleanup-count"><?php echo esc_html( $data['orphan_term_rels_count'] ?? 0 ); ?></span></td>
				<td>
					<button class="button soc-db-cleanup" data-cleanup-type="orphan_term_rels">
						<?php esc_html_e( 'Clean Up', 'shopperexpress' ); ?>
					</button>
				</td>
			</tr>
		</tbody>
	</table>
</div>

<!-- 3. Tables List -->
<div class="soc-section">
	<div class="soc-section__title"><?php esc_html_e( 'Top Tables by Size', 'shopperexpress' ); ?></div>

	<?php if ( ! empty( $data['tables'] ) ) : ?>
		<table class="soc-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Table Name', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'Rows', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'Data (KB)', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'Index (KB)', 'shopperexpress' ); ?></th>
					<th><?php esc_html_e( 'Engine', 'shopperexpress' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$tables = array_slice( (array) $data['tables'], 0, 20 );
				foreach ( $tables as $table ) :
					?>
					<tr>
						<td><code><?php echo esc_html( $table['name'] ?? '' ); ?></code></td>
						<td><?php echo esc_html( number_format( (int) ( $table['rows'] ?? 0 ) ) ); ?></td>
						<td><?php echo esc_html( number_format( (float) ( $table['data_kb'] ?? 0 ), 2 ) ); ?></td>
						<td><?php echo esc_html( number_format( (float) ( $table['index_kb'] ?? 0 ), 2 ) ); ?></td>
						<td><?php echo esc_html( $table['engine'] ?? 'N/A' ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php else : ?>
		<p><?php esc_html_e( 'No table data available.', 'shopperexpress' ); ?></p>
	<?php endif; ?>
</div>
