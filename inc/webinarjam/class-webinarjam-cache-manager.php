<?php
/**
 * WebinarJam Cache Manager
 *
 * Manages caching strategies for WebinarJam integration.
 *
 * @package ma_plugin
 * @since 1.0.0
 */

namespace ma_plugin\classes\WebinarJam;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WebinarJam Cache Manager class.
 *
 * Handles cache warming, invalidation, and optimization strategies.
 *
 * @since 1.0.0
 */
class WebinarJam_Cache_Manager {

	/**
	 * Cache group name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const CACHE_GROUP = 'ma_webinarjam';

	/**
	 * Initialize cache manager.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function init() {
		// Clear cache on manual sync.
		add_action( 'ma_webinarjam_before_sync_all', array( __CLASS__, 'invalidate_all_cache' ) );
		add_action( 'ma_webinarjam_after_sync_webinar', array( __CLASS__, 'invalidate_webinar_cache' ), 10, 2 );

		// Warm cache after sync.
		add_action( 'ma_webinarjam_after_sync_all', array( __CLASS__, 'warm_cache' ) );
	}

	/**
	 * Invalidate all WebinarJam caches.
	 *
	 * @since 1.0.0
	 * @return int Number of caches cleared.
	 */
	public static function invalidate_all_cache() {
		global $wpdb;

		// Delete all WebinarJam transients.
		$deleted = $wpdb->query(
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_ma_webinarjam_%' 
			OR option_name LIKE '_transient_timeout_ma_webinarjam_%'"
		);

		// Clear object cache.
		wp_cache_flush();

		do_action( 'ma_webinarjam_cache_invalidated', 'all' );

		return $deleted;
	}

	/**
	 * Invalidate cache for a specific webinar.
	 *
	 * @since 1.0.0
	 * @param string $webinar_id WebinarJam webinar ID.
	 * @param mixed  $result Sync result (optional).
	 * @return void
	 */
	public static function invalidate_webinar_cache( $webinar_id, $result = null ) {
		// Delete webinar-specific transient.
		delete_transient( 'ma_webinarjam_webinar_' . $webinar_id );

		// Delete all webinars list cache.
		delete_transient( 'ma_webinarjam_all_webinars' );

		do_action( 'ma_webinarjam_cache_invalidated', $webinar_id );
	}

	/**
	 * Warm cache by pre-loading common data.
	 *
	 * Runs after sync to pre-populate caches for better performance.
	 *
	 * @since 1.0.0
	 * @param array $results Optional sync results.
	 * @return array Warming results.
	 */
	public static function warm_cache( $results = array() ) {
		$warmed = array();

		// Get API client.
		$api_client = new WebinarJam_API_Client();

		// Warm all webinars cache.
		$webinars = $api_client->get_all_webinars( false ); // Force fresh fetch.
		if ( ! is_wp_error( $webinars ) ) {
			$warmed['all_webinars'] = count( $webinars );

			// Warm individual webinar caches (limit to upcoming and live).
			$count = 0;
			foreach ( $webinars as $webinar ) {
				if ( ! isset( $webinar['webinar_id'] ) ) {
					continue;
				}

				// Only warm cache for upcoming/live webinars.
				$status = isset( $webinar['status'] ) ? $webinar['status'] : '';
				if ( in_array( $status, array( 'upcoming', 'live' ), true ) ) {
					$api_client->get_webinar( $webinar['webinar_id'], false );
					$count++;

					// Limit to 20 to avoid performance issues.
					if ( $count >= 20 ) {
						break;
					}
				}
			}
			$warmed['individual_webinars'] = $count;
		}

		// Warm course query cache.
		$courses = \ma_get_webinar_courses(
			array(
				'posts_per_page' => 20,
				'status'         => 'upcoming',
			)
		);
		$warmed['courses'] = is_array( $courses ) ? count( $courses ) : 0;

		do_action( 'ma_webinarjam_cache_warmed', $warmed );

		return $warmed;
	}

	/**
	 * Get cache statistics.
	 *
	 * @since 1.0.0
	 * @return array Cache stats.
	 */
	public static function get_cache_stats() {
		global $wpdb;

		$stats = array();

		// Count transients.
		$stats['total_transients'] = $wpdb->get_var(
			"SELECT COUNT(*) 
			FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_ma_webinarjam_%'"
		);

		// Count expired transients.
		$stats['expired_transients'] = $wpdb->get_var(
			"SELECT COUNT(*) 
			FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_timeout_ma_webinarjam_%' 
			AND option_value < UNIX_TIMESTAMP()"
		);

		// Get cache size estimate.
		$cache_data = $wpdb->get_results(
			"SELECT option_value 
			FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_ma_webinarjam_%'",
			ARRAY_A
		);

		$total_size = 0;
		foreach ( $cache_data as $row ) {
			$total_size += strlen( $row['option_value'] );
		}
		$stats['cache_size_bytes'] = $total_size;
		$stats['cache_size_kb']    = round( $total_size / 1024, 2 );

		return $stats;
	}

	/**
	 * Clear expired transients.
	 *
	 * @since 1.0.0
	 * @return int Number of transients deleted.
	 */
	public static function clear_expired_transients() {
		global $wpdb;

		// Get expired transient names.
		$expired = $wpdb->get_col(
			"SELECT option_name 
			FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_timeout_ma_webinarjam_%' 
			AND option_value < UNIX_TIMESTAMP()"
		);

		$deleted = 0;
		foreach ( $expired as $timeout_name ) {
			// Extract transient name.
			$transient_name = str_replace( '_timeout', '', $timeout_name );

			// Delete both timeout and transient.
			if ( delete_option( $timeout_name ) && delete_option( $transient_name ) ) {
				$deleted++;
			}
		}

		return $deleted;
	}

	/**
	 * Set cache duration.
	 *
	 * @since 1.0.0
	 * @param int $hours Cache duration in hours.
	 * @return int Cache duration in seconds.
	 */
	public static function set_cache_duration( $hours ) {
		$seconds = absint( $hours ) * HOUR_IN_SECONDS;
		update_option( 'ma_webinarjam_cache_duration', $seconds );
		return $seconds;
	}

	/**
	 * Get cache duration.
	 *
	 * @since 1.0.0
	 * @return int Cache duration in seconds.
	 */
	public static function get_cache_duration() {
		return absint( get_option( 'ma_webinarjam_cache_duration', 12 * HOUR_IN_SECONDS ) );
	}
}

// Initialize cache manager.
WebinarJam_Cache_Manager::init();
