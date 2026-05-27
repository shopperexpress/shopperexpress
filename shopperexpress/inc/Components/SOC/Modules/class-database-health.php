<?php
/**
 * SOC Database Health Module
 *
 * Provides diagnostics and cleanup operations for the WordPress database,
 * including table usage, autoloaded options, transients, revisions, spam, and trash.
 *
 * @package Shopperexpress
 */

namespace App\Components\SOC\Modules;

use App\Components\SOC\Contracts\SOC_Module;
use App\Components\SOC\Support\SOC_Cache;
use App\Components\SOC\Support\SOC_Logger;

/**
 * Class Database_Health
 *
 * Collects and presents WordPress database health metrics and provides cleanup methods.
 */
class Database_Health implements SOC_Module {

	/**
	 * Returns the module slug used for cache and lookup.
	 *
	 * @return string
	 */
	public function get_slug(): string {
		return 'database-health';
	}

	/**
	 * Returns the human-readable label for admin display.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return 'Database';
	}

	/**
	 * Returns the Dashicon identifier representing this module.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'dashicons-database';
	}

	/**
	 * Collects and caches database stats for the dashboard, with optional cache bypass.
	 *
	 * @param bool $force_refresh Whether to force a fresh collection, bypassing cache.
	 * @return array Collected database metrics.
	 */
	public function collect( bool $force_refresh = false ): array {
		if ( $force_refresh ) {
			SOC_Cache::forget( $this->get_slug(), 'data' );
		}

		$cached = SOC_Cache::get( $this->get_slug(), 'data' );

		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;

		$tables        = $this->get_tables_info();
		$total_kb      = array_sum( array_column( $tables, 'total_kb' ) );
		$total_size_mb = round( $total_kb / 1024, 2 );

		$autoload_count = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->options} WHERE autoload = 'yes'"
		);

		$autoload_size_kb = round(
			(float) $wpdb->get_var(
				"SELECT SUM(LENGTH(option_value)) / 1024 FROM {$wpdb->options} WHERE autoload = 'yes'"
			),
			2
		);

		// Orphan transients: value rows without a matching timeout row.
		$orphan_transients = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->options}
			 WHERE option_name LIKE '_transient_%'
			 AND option_name NOT LIKE '_transient_timeout_%'
			 AND REPLACE(option_name, '_transient_', '_transient_timeout_') NOT IN (
				SELECT option_name FROM {$wpdb->options}
			 )"
		);

		$post_revisions_count = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision'"
		);

		$spam_count = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'spam'"
		);

		// Trash count and breakdown by post type (all types, not just 'post').
		$trash_count = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'trash'"
		);

		$trash_by_type_rows = $wpdb->get_results(
			"SELECT post_type, COUNT(*) AS cnt
			 FROM {$wpdb->posts}
			 WHERE post_status = 'trash'
			 GROUP BY post_type
			 ORDER BY cnt DESC",
			ARRAY_A
		);

		$trash_by_type = array();
		foreach ( (array) $trash_by_type_rows as $row ) {
			$trash_by_type[ $row['post_type'] ] = (int) $row['cnt'];
		}

		// Auto-drafts (all post types) — WordPress creates these whenever editing starts.
		$auto_drafts_count = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'auto-draft'"
		);

		// Orphaned postmeta: meta rows whose post no longer exists.
		// LEFT JOIN is used instead of NOT IN to handle large tables efficiently.
		$orphan_postmeta_count = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->postmeta} pm
			 LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			 WHERE p.ID IS NULL"
		);

		// Orphaned term relationships: rows whose object (post) no longer exists.
		$orphan_term_rels_count = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->term_relationships} tr
			 LEFT JOIN {$wpdb->posts} p ON tr.object_id = p.ID
			 WHERE p.ID IS NULL"
		);

		$data = array(
			'tables'                 => $tables,
			'total_size_mb'          => $total_size_mb,
			'autoload_count'         => $autoload_count,
			'autoload_size_kb'       => $autoload_size_kb,
			'orphan_transients'      => $orphan_transients,
			'post_revisions_count'   => $post_revisions_count,
			'spam_count'             => $spam_count,
			'trash_count'            => $trash_count,
			'trash_by_type'          => $trash_by_type,
			'auto_drafts_count'      => $auto_drafts_count,
			'orphan_postmeta_count'  => $orphan_postmeta_count,
			'orphan_term_rels_count' => $orphan_term_rels_count,
			'collected_at'           => current_time( 'mysql' ),
		);

		SOC_Cache::set( $this->get_slug(), 'data', $data, 10 * MINUTE_IN_SECONDS );

		return $data;
	}

	/**
	 * Renders the database health template in the admin UI.
	 *
	 * @param array $data Collected module data.
	 * @return void
	 */
	public function render( array $data ): void {
		require get_template_directory() . '/inc/Components/SOC/views/database-health.php';
	}

	// ─── Cleanup Methods ────────────────────────────────────────────────────────

	/**
	 * Deletes all post revisions plus their associated postmeta and term_relationships.
	 * Covers ALL post types — there is no post_type = 'post' filter.
	 *
	 * @return int Number of wp_posts rows deleted.
	 */
	public function cleanup_revisions(): int {
		global $wpdb;

		$wpdb->query(
			"DELETE tr FROM {$wpdb->term_relationships} tr
			 INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
			 WHERE p.post_type = 'revision'"
		);

		$wpdb->query(
			"DELETE pm FROM {$wpdb->postmeta} pm
			 INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			 WHERE p.post_type = 'revision'"
		);

		$deleted = (int) $wpdb->query(
			"DELETE FROM {$wpdb->posts} WHERE post_type = 'revision'"
		);

		SOC_Logger::write( 'general', sprintf( 'DB cleanup: revisions | deleted: %d', $deleted ) );
		SOC_Cache::forget( $this->get_slug(), 'data' );

		return $deleted;
	}

	/**
	 * Deletes all spam comments and their associated commentmeta.
	 *
	 * @return int Number of wp_comments rows deleted.
	 */
	public function cleanup_spam(): int {
		global $wpdb;

		$wpdb->query(
			"DELETE cm FROM {$wpdb->commentmeta} cm
			 INNER JOIN {$wpdb->comments} c ON cm.comment_id = c.comment_ID
			 WHERE c.comment_approved = 'spam'"
		);

		$deleted = (int) $wpdb->query(
			"DELETE FROM {$wpdb->comments} WHERE comment_approved = 'spam'"
		);

		SOC_Logger::write( 'general', sprintf( 'DB cleanup: spam | deleted: %d', $deleted ) );
		SOC_Cache::forget( $this->get_slug(), 'data' );

		return $deleted;
	}

	/**
	 * Permanently deletes ALL trashed posts across every post type,
	 * plus their associated postmeta and term_relationships.
	 *
	 * @return int Number of wp_posts rows deleted.
	 */
	public function cleanup_trash(): int {
		global $wpdb;

		$wpdb->query(
			"DELETE tr FROM {$wpdb->term_relationships} tr
			 INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
			 WHERE p.post_status = 'trash'"
		);

		$wpdb->query(
			"DELETE pm FROM {$wpdb->postmeta} pm
			 INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			 WHERE p.post_status = 'trash'"
		);

		$deleted = (int) $wpdb->query(
			"DELETE FROM {$wpdb->posts} WHERE post_status = 'trash'"
		);

		SOC_Logger::write( 'general', sprintf( 'DB cleanup: trash | deleted: %d', $deleted ) );
		SOC_Cache::forget( $this->get_slug(), 'data' );

		return $deleted;
	}

	/**
	 * Removes orphaned transients (value rows without a matching timeout row).
	 *
	 * @return int Number of rows deleted.
	 */
	public function cleanup_orphan_transients(): int {
		global $wpdb;

		$deleted = (int) $wpdb->query(
			"DELETE FROM {$wpdb->options}
			 WHERE option_name LIKE '_transient_%'
			 AND option_name NOT LIKE '_transient_timeout_%'
			 AND REPLACE(option_name, '_transient_', '_transient_timeout_') NOT IN (
				SELECT option_name FROM {$wpdb->options}
			 )"
		);

		SOC_Logger::write( 'general', sprintf( 'DB cleanup: orphan_transients | deleted: %d', $deleted ) );
		SOC_Cache::forget( $this->get_slug(), 'data' );

		return $deleted;
	}

	/**
	 * Deletes all auto-draft posts across every post type,
	 * plus their associated postmeta and term_relationships.
	 *
	 * WordPress creates auto-drafts whenever a new editing session starts;
	 * old ones accumulate and are never automatically purged.
	 *
	 * @return int Number of wp_posts rows deleted.
	 */
	public function cleanup_auto_drafts(): int {
		global $wpdb;

		$wpdb->query(
			"DELETE tr FROM {$wpdb->term_relationships} tr
			 INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
			 WHERE p.post_status = 'auto-draft'"
		);

		$wpdb->query(
			"DELETE pm FROM {$wpdb->postmeta} pm
			 INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			 WHERE p.post_status = 'auto-draft'"
		);

		$deleted = (int) $wpdb->query(
			"DELETE FROM {$wpdb->posts} WHERE post_status = 'auto-draft'"
		);

		SOC_Logger::write( 'general', sprintf( 'DB cleanup: auto_drafts | deleted: %d', $deleted ) );
		SOC_Cache::forget( $this->get_slug(), 'data' );

		return $deleted;
	}

	/**
	 * Deletes wp_postmeta rows whose post_id no longer has a matching row in wp_posts.
	 *
	 * Uses LEFT JOIN instead of NOT IN to avoid performance issues on large tables.
	 *
	 * @return int Number of rows deleted.
	 */
	public function cleanup_orphan_postmeta(): int {
		global $wpdb;

		$deleted = (int) $wpdb->query(
			"DELETE pm FROM {$wpdb->postmeta} pm
			 LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			 WHERE p.ID IS NULL"
		);

		SOC_Logger::write( 'general', sprintf( 'DB cleanup: orphan_postmeta | deleted: %d', $deleted ) );
		SOC_Cache::forget( $this->get_slug(), 'data' );

		return $deleted;
	}

	/**
	 * Deletes wp_term_relationships rows whose object_id no longer exists in wp_posts.
	 *
	 * Uses LEFT JOIN instead of NOT IN for efficiency on large tables.
	 *
	 * @return int Number of rows deleted.
	 */
	public function cleanup_orphan_term_rels(): int {
		global $wpdb;

		$deleted = (int) $wpdb->query(
			"DELETE tr FROM {$wpdb->term_relationships} tr
			 LEFT JOIN {$wpdb->posts} p ON tr.object_id = p.ID
			 WHERE p.ID IS NULL"
		);

		SOC_Logger::write( 'general', sprintf( 'DB cleanup: orphan_term_rels | deleted: %d', $deleted ) );
		SOC_Cache::forget( $this->get_slug(), 'data' );

		return $deleted;
	}

	// ─── Private helpers ───────────────────────────────────────────────────────

	/**
	 * Gets a list of all database tables in the current schema, with size info.
	 *
	 * @return array Array of table info: name, row count, size, and engine.
	 */
	private function get_tables_info(): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT
					TABLE_NAME       AS name,
					TABLE_ROWS       AS `rows`,
					DATA_LENGTH      AS data_length,
					INDEX_LENGTH     AS index_length,
					ENGINE           AS engine
				FROM information_schema.TABLES
				WHERE table_schema = %s
				ORDER BY (data_length + index_length) DESC',
				DB_NAME
			),
			ARRAY_A
		);

		if ( ! $rows ) {
			return array();
		}

		return array_map(
			static function ( $row ) {
				$data_kb  = round( (int) $row['data_length'] / 1024, 2 );
				$index_kb = round( (int) $row['index_length'] / 1024, 2 );

				return array(
					'name'     => $row['name'],
					'rows'     => (int) $row['rows'],
					'data_kb'  => $data_kb,
					'index_kb' => $index_kb,
					'total_kb' => round( $data_kb + $index_kb, 2 ),
					'engine'   => $row['engine'],
				);
			},
			$rows
		);
	}
}
