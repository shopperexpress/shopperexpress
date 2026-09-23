<?php
/**
 * Google Reviews — WP Cron full-history sync.
 *
 * Keeps a background snapshot of every Business Profile review so keyword
 * filtering (e.g. an Oil Change service page only wanting oil-change-related
 * reviews) can search the whole review history instead of just the newest
 * handful fetched on a page load.
 *
 * @package Shopperexpress
 */

namespace App\Components\Base;

use App\Components\Theme_Component;

/**
 * Class Google_Reviews_Cron
 *
 * @package App\Components\Base
 */
class Google_Reviews_Cron implements Theme_Component {

	const HOOK = 'google_reviews_sync_all';

	/**
	 * @return void
	 */
	public function register(): void {
		add_action( self::HOOK, array( $this, 'run' ) );
		add_action( 'after_setup_theme', array( $this, 'schedule' ) );
	}

	/**
	 * Ensure the cron event is scheduled (runs hourly) when a Business Profile is connected.
	 *
	 * @return void
	 */
	public function schedule(): void {
		if ( ! ( new Google_Business_Reviews() )->is_connected() ) {
			return;
		}

		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time(), 'hourly', self::HOOK );
		}
	}

	/**
	 * @return void
	 */
	public function run(): void {
		$client = new Google_Business_Reviews();
		$client->sync_all_reviews();
		delete_transient( Google_Business_Reviews::SYNC_LOCK_TRANSIENT );
	}
}
