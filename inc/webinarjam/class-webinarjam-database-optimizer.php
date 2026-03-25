<?php
/**
 * WebinarJam Database Optimization
 *
 * Handles database optimizations including indexes and query improvements.
 *
 * @package ma_plugin
 * @since 1.0.0
 */

namespace ma_plugin\classes\WebinarJam;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WebinarJam Database Optimization class.
 *
 * Manages database indexes, query optimization, and performance improvements.
 *
 * @since 1.0.0
 */
class WebinarJam_Database_Optimizer {

	/**
	 * Initialize database optimizations.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function init() {
		// Run optimization on activation.
		add_action( 'ma_webinarjam_activated', array( __CLASS__, 'create_indexes' ) );
	}

	/**
	 * Create database indexes for WebinarJam meta queries.
	 *
	 * Improves performance for queries filtering by WebinarJam fields.
	 *
	 * @since 1.0.0
	 * @return bool True on success, false on failure.
	 */
	public static function create_indexes() {
		global $wpdb;

		$indexes_created = array();

		// Index for webinar ID lookups.
		$result = $wpdb->query(
			"CREATE INDEX IF NOT EXISTS idx_webinarjam_id 
			ON {$wpdb->postmeta} (meta_key, meta_value(191)) 
			WHERE meta_key = '_webinarjam_webinar_id'"
		);

		if ( false !== $result ) {
			$indexes_created[] = 'idx_webinarjam_id';
		}

		// Index for status queries.
		$result = $wpdb->query(
			"CREATE INDEX IF NOT EXISTS idx_webinarjam_status 
			ON {$wpdb->postmeta} (meta_key, meta_value(50)) 
			WHERE meta_key = '_webinarjam_status'"
		);

		if ( false !== $result ) {
			$indexes_created[] = 'idx_webinarjam_status';
		}

		// Index for date-based queries.
		$result = $wpdb->query(
			"CREATE INDEX IF NOT EXISTS idx_webinarjam_next_date 
			ON {$wpdb->postmeta} (meta_key, meta_value(20)) 
			WHERE meta_key = '_webinarjam_next_date'"
		);

		if ( false !== $result ) {
			$indexes_created[] = 'idx_webinarjam_next_date';
		}

		// Index for last sync timestamp.
		$result = $wpdb->query(
			"CREATE INDEX IF NOT EXISTS idx_webinarjam_last_sync 
			ON {$wpdb->postmeta} (meta_key, meta_value(20)) 
			WHERE meta_key = '_webinarjam_last_sync'"
		);

		if ( false !== $result ) {
			$indexes_created[] = 'idx_webinarjam_last_sync';
		}

		// Log results.
		if ( ! empty( $indexes_created ) ) {
			error_log( 'WebinarJam: Created database indexes: ' . implode( ', ', $indexes_created ) );
			return true;
		}

		return false;
	}

	/**
	 * Remove database indexes.
	 *
	 * Called on plugin deactivation/uninstall if needed.
	 *
	 * @since 1.0.0
	 * @return bool True on success.
	 */
	public static function remove_indexes() {
		global $wpdb;

		$indexes = array(
			'idx_webinarjam_id',
			'idx_webinarjam_status',
			'idx_webinarjam_next_date',
			'idx_webinarjam_last_sync',
		);

		foreach ( $indexes as $index ) {
			$wpdb->query( "DROP INDEX IF EXISTS {$index} ON {$wpdb->postmeta}" );
		}

		return true;
	}

	/**
	 * Optimize webinar courses table.
	 *
	 * Runs table optimization for posts and postmeta tables.
	 *
	 * @since 1.0.0
	 * @return array Optimization results.
	 */
	public static function optimize_tables() {
		global $wpdb;

		$results = array();

		// Optimize posts table.
		$result = $wpdb->query( "OPTIMIZE TABLE {$wpdb->posts}" );
		$results['posts'] = $result;

		// Optimize postmeta table.
		$result = $wpdb->query( "OPTIMIZE TABLE {$wpdb->postmeta}" );
		$results['postmeta'] = $result;

		// Optimize logs table.
		$logs_table = $wpdb->prefix . 'webinarjam_logs';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$logs_table}'" ) === $logs_table ) {
			$result = $wpdb->query( "OPTIMIZE TABLE {$logs_table}" );
			$results['logs'] = $result;
		}

		return $results;
	}

	/**
	 * Get query performance statistics.
	 *
	 * Returns statistics about database queries for monitoring.
	 *
	 * @since 1.0.0
	 * @return array Query statistics.
	 */
	public static function get_query_stats() {
		global $wpdb;

		$stats = array();

		// Count webinar courses.
		$stats['total_courses'] = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT p.ID) 
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				WHERE p.post_type = %s
				AND pm.meta_key = '_webinarjam_webinar_id'",
				'sfwd-courses'
			)
		);

		// Count by status.
		foreach ( array( 'upcoming', 'live', 'replay', 'completed' ) as $status ) {
			$stats[ "status_{$status}" ] = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) 
					FROM {$wpdb->postmeta}
					WHERE meta_key = '_webinarjam_status'
					AND meta_value = %s",
					$status
				)
			);
		}

		// Average registered users per webinar.
		$stats['avg_registered'] = $wpdb->get_var(
			"SELECT AVG(meta_value) 
			FROM {$wpdb->postmeta}
			WHERE meta_key = '_webinarjam_registration_count'"
		);

		// Total log entries.
		$logs_table = $wpdb->prefix . 'webinarjam_logs';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$logs_table}'" ) === $logs_table ) {
			$stats['total_logs'] = $wpdb->get_var( "SELECT COUNT(*) FROM {$logs_table}" );
		}

		return $stats;
	}

	/**
	 * Clean up old data.
	 *
	 * Removes old logs and transients to keep database lean.
	 *
	 * @since 1.0.0
	 * @param int $days_old Delete data older than this many days.
	 * @return array Cleanup results.
	 */
	public static function cleanup_old_data( $days_old = 30 ) {
		global $wpdb;

		$results = array();
		$date_threshold = date( 'Y-m-d H:i:s', strtotime( "-{$days_old} days" ) );

		// Clean up old logs.
		$logs_table = $wpdb->prefix . 'webinarjam_logs';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$logs_table}'" ) === $logs_table ) {
			$deleted = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$logs_table} WHERE created_at < %s",
					$date_threshold
				)
			);
			$results['logs_deleted'] = $deleted;
		}

		// Clean up expired transients.
		$deleted = $wpdb->query(
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_timeout_ma_webinarjam_%' 
			AND option_value < UNIX_TIMESTAMP()"
		);
		$results['transients_deleted'] = $deleted;

		return $results;
	}

	/**
	 * Batch process courses.
	 *
	 * Process courses in batches to avoid memory/timeout issues.
	 *
	 * @since 1.0.0
	 * @param callable $callback Function to call for each course.
	 * @param array    $args Query args for get_posts.
	 * @param int      $batch_size Number of courses per batch.
	 * @return array Processing results.
	 */
	public static function batch_process_courses( $callback, $args = array(), $batch_size = 50 ) {
		$results = array(
			'processed' => 0,
			'failed'    => 0,
			'batches'   => 0,
		);

		$defaults = array(
			'post_type'      => 'sfwd-courses',
			'posts_per_page' => $batch_size,
			'paged'          => 1,
			'meta_key'       => '_webinarjam_webinar_id',
			'meta_compare'   => 'EXISTS',
		);

		$query_args = wp_parse_args( $args, $defaults );

		do {
			$posts = get_posts( $query_args );

			if ( empty( $posts ) ) {
				break;
			}

			foreach ( $posts as $post ) {
				$result = call_user_func( $callback, $post->ID );

				if ( $result && ! is_wp_error( $result ) ) {
					$results['processed']++;
				} else {
					$results['failed']++;
				}
			}

			$results['batches']++;
			$query_args['paged']++;

			// Prevent infinite loops.
			if ( $results['batches'] > 100 ) {
				break;
			}

			// Clear local cache between batches.
			wp_cache_flush();

		} while ( count( $posts ) === $batch_size );

		return $results;
	}
}

// Initialize optimizer.
WebinarJam_Database_Optimizer::init();
