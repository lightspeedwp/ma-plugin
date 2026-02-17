<?php
/**
 * WebinarJam Activation Handler
 *
 * Handles plugin activation, deactivation, and uninstall routines.
 *
 * @package ma_plugin
 * @since 1.0.0
 */

namespace ma_plugin\classes\WebinarJam;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WebinarJam Activation Handler class.
 *
 * Manages activation hooks, dependency checks, and cleanup routines.
 *
 * @since 1.0.0
 */
class WebinarJam_Activation_Handler {

	/**
	 * Plugin version.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const VERSION = '1.0.0';

	/**
	 * Run on plugin activation.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function activate() {
		// Check dependencies.
		$missing = self::check_dependencies();

		if ( ! empty( $missing ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die(
				sprintf(
					/* translators: %s: list of missing plugins */
					__( 'WebinarJam Integration requires the following plugins to be active: %s', 'ma-plugin' ),
					implode( ', ', $missing )
				),
				__( 'Plugin Activation Error', 'ma-plugin' ),
				array( 'back_link' => true )
			);
		}

		// Set plugin version.
		update_option( 'ma_webinarjam_version', self::VERSION );

		// Create database tables.
		self::create_tables();

		// Create database indexes.
		WebinarJam_Database_Optimizer::create_indexes();

		// Schedule cron events.
		self::schedule_events();

		// Set default options.
		self::set_default_options();

		// Flush rewrite rules.
		flush_rewrite_rules();

		// Fire activation action.
		do_action( 'ma_webinarjam_activated' );
	}

	/**
	 * Run on plugin deactivation.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function deactivate() {
		// Unschedule all cron events.
		self::unschedule_events();

		// Fire deactivation action.
		do_action( 'ma_webinarjam_deactivated' );
	}

	/**
	 * Run on plugin uninstall.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function uninstall() {
		// Check if user wants to preserve data.
		$preserve_data = get_option( 'ma_webinarjam_preserve_data_on_uninstall', false );

		if ( ! $preserve_data ) {
			// Delete all options.
			self::delete_options();

			// Delete all transients.
			self::delete_transients();

			// Drop database tables.
			self::drop_tables();

			// Remove database indexes.
			WebinarJam_Database_Optimizer::remove_indexes();

			// Delete all post meta.
			self::delete_post_meta();
		}

		// Fire uninstall action.
		do_action( 'ma_webinarjam_uninstalled' );
	}

	/**
	 * Check for required plugin dependencies.
	 *
	 * @since 1.0.0
	 * @return array Array of missing plugin names.
	 */
	private static function check_dependencies() {
		$missing = array();

		// Check LearnDash.
		if ( ! function_exists( 'learndash_get_post_types' ) ) {
			$missing[] = 'LearnDash LMS';
		}

		// Check Events Calendar Pro.
		if ( ! class_exists( 'Tribe__Events__Pro__Main' ) ) {
			$missing[] = 'The Events Calendar Pro';
		}

		// Check ACF/SCF.
		if ( ! function_exists( 'acf' ) && ! function_exists( 'get_field' ) ) {
			$missing[] = 'Advanced Custom Fields or Secure Custom Fields';
		}

		return $missing;
	}

	/**
	 * Create database tables.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function create_tables() {
		// Create logs table.
		$logger = new WebinarJam_Logger();
		$logger->create_table();
	}

	/**
	 * Drop database tables.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function drop_tables() {
		global $wpdb;

		$logs_table = $wpdb->prefix . 'webinarjam_logs';
		$wpdb->query( "DROP TABLE IF EXISTS {$logs_table}" );
	}

	/**
	 * Schedule cron events.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function schedule_events() {
		// Get sync schedule setting.
		$schedule = get_option( 'ma_webinarjam_sync_schedule', 'daily' );

		// Schedule daily sync.
		if ( ! wp_next_scheduled( 'ma_webinarjam_daily_sync' ) ) {
			wp_schedule_event( time(), $schedule, 'ma_webinarjam_daily_sync' );
		}

		// Schedule import.
		if ( ! wp_next_scheduled( 'ma_webinarjam_import_webinars' ) ) {
			wp_schedule_event( time(), $schedule, 'ma_webinarjam_import_webinars' );
		}

		// Schedule status check (twice daily).
		if ( ! wp_next_scheduled( 'ma_webinarjam_status_check' ) ) {
			wp_schedule_event( time(), 'twicedaily', 'ma_webinarjam_status_check' );
		}

		// Schedule attendance check (daily).
		if ( ! wp_next_scheduled( 'ma_webinarjam_attendance_check' ) ) {
			wp_schedule_event( time(), 'daily', 'ma_webinarjam_attendance_check' );
		}

		// Schedule cleanup (weekly).
		if ( ! wp_next_scheduled( 'ma_webinarjam_cleanup' ) ) {
			wp_schedule_event( time(), 'weekly', 'ma_webinarjam_cleanup' );
		}
	}

	/**
	 * Unschedule all cron events.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function unschedule_events() {
		$events = array(
			'ma_webinarjam_daily_sync',
			'ma_webinarjam_import_webinars',
			'ma_webinarjam_status_check',
			'ma_webinarjam_attendance_check',
			'ma_webinarjam_cleanup',
		);

		foreach ( $events as $event ) {
			$timestamp = wp_next_scheduled( $event );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $event );
			}
		}
	}

	/**
	 * Set default options.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function set_default_options() {
		// Sync settings.
		$default_sync_settings = array(
			'auto_sync_new'      => true,
			'auto_update_existing' => true,
			'sync_schedule'      => 'daily',
		);

		if ( ! get_option( 'ma_webinarjam_sync_settings' ) ) {
			update_option( 'ma_webinarjam_sync_settings', $default_sync_settings );
		}

		// Advanced settings.
		$default_advanced_settings = array(
			'debug_mode'      => false,
			'cache_duration'  => 12 * HOUR_IN_SECONDS,
		);

		if ( ! get_option( 'ma_webinarjam_advanced_settings' ) ) {
			update_option( 'ma_webinarjam_advanced_settings', $default_advanced_settings );
		}
	}

	/**
	 * Delete all plugin options.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function delete_options() {
		global $wpdb;

		// Delete all WebinarJam options.
		$wpdb->query(
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE 'ma_webinarjam_%'"
		);
	}

	/**
	 * Delete all plugin transients.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function delete_transients() {
		WebinarJam_Cache_Manager::invalidate_all_cache();
	}

	/**
	 * Delete all WebinarJam post meta.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private static function delete_post_meta() {
		global $wpdb;

		// Delete all WebinarJam meta keys.
		$wpdb->query(
			"DELETE FROM {$wpdb->postmeta} 
			WHERE meta_key LIKE '_webinarjam_%'"
		);
	}

	/**
	 * Run upgrade routines.
	 *
	 * Called when plugin version changes.
	 *
	 * @since 1.0.0
	 * @param string $old_version Previous version.
	 * @param string $new_version New version.
	 * @return void
	 */
	public static function upgrade( $old_version, $new_version ) {
		// Future upgrade routines go here.
		
		// Example: if ( version_compare( $old_version, '1.1.0', '<' ) ) {
		//     self::upgrade_to_1_1_0();
		// }

		// Update version.
		update_option( 'ma_webinarjam_version', $new_version );

		do_action( 'ma_webinarjam_upgraded', $old_version, $new_version );
	}

	/**
	 * Check if upgrade is needed.
	 *
	 * @since 1.0.0
	 * @return bool True if upgrade needed.
	 */
	public static function needs_upgrade() {
		$current_version = get_option( 'ma_webinarjam_version', '0.0.0' );
		return version_compare( $current_version, self::VERSION, '<' );
	}

	/**
	 * Get system health status.
	 *
	 * @since 1.0.0
	 * @return array Health status information.
	 */
	public static function get_health_status() {
		$status = array(
			'version'       => self::VERSION,
			'dependencies'  => array(),
			'cron'          => array(),
			'database'      => array(),
			'cache'         => array(),
			'overall'       => 'healthy',
		);

		// Check dependencies.
		$missing = self::check_dependencies();
		$status['dependencies']['missing'] = $missing;
		$status['dependencies']['status']  = empty( $missing ) ? 'ok' : 'error';

		// Check cron events.
		$events = array(
			'ma_webinarjam_daily_sync',
			'ma_webinarjam_status_check',
			'ma_webinarjam_attendance_check',
		);

		foreach ( $events as $event ) {
			$next = wp_next_scheduled( $event );
			$status['cron'][ $event ] = $next ? date( 'Y-m-d H:i:s', $next ) : 'not scheduled';
		}

		// Check database.
		$stats = WebinarJam_Database_Optimizer::get_query_stats();
		$status['database'] = $stats;

		// Check cache.
		$cache_stats = WebinarJam_Cache_Manager::get_cache_stats();
		$status['cache'] = $cache_stats;

		// Determine overall status.
		if ( ! empty( $missing ) ) {
			$status['overall'] = 'error';
		} elseif ( $cache_stats['expired_transients'] > 100 ) {
			$status['overall'] = 'warning';
		}

		return $status;
	}
}
