<?php
/**
 * WebinarJam Health Monitor
 *
 * Monitors system health and integration status.
 *
 * @package ma_plugin
 * @since 1.0.0
 */

namespace ma_plugin\classes\WebinarJam;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WebinarJam Health Monitor class.
 *
 * Provides health checks, monitoring, and alerting capabilities.
 *
 * @since 1.0.0
 */
class WebinarJam_Health_Monitor {

	/**
	 * Logger instance.
	 *
	 * @since 1.0.0
	 * @var WebinarJam_Logger
	 */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param WebinarJam_Logger $logger Logger instance.
	 */
	public function __construct( $logger ) {
		$this->logger = $logger;

		// Schedule health checks.
		add_action( 'ma_webinarjam_daily_sync', array( $this, 'run_health_check' ) );
	}

	/**
	 * Run comprehensive health check.
	 *
	 * @since 1.0.0
	 * @return array Health check results.
	 */
	public function run_health_check() {
		$health = array(
			'timestamp'    => current_time( 'mysql' ),
			'status'       => 'healthy',
			'checks'       => array(),
			'warnings'     => array(),
			'errors'       => array(),
		);

		// API connectivity check.
		$api_check = $this->check_api_connectivity();
		$health['checks']['api'] = $api_check;
		if ( ! $api_check['passed'] ) {
			$health['errors'][] = $api_check['message'];
			$health['status'] = 'error';
		}

		// Sync health check.
		$sync_check = $this->check_sync_health();
		$health['checks']['sync'] = $sync_check;
		if ( ! $sync_check['passed'] ) {
			$health['warnings'][] = $sync_check['message'];
			if ( $health['status'] !== 'error' ) {
				$health['status'] = 'warning';
			}
		}

		// Cron jobs check.
		$cron_check = $this->check_cron_jobs();
		$health['checks']['cron'] = $cron_check;
		if ( ! $cron_check['passed'] ) {
			$health['errors'][] = $cron_check['message'];
			$health['status'] = 'error';
		}

		// Database check.
		$db_check = $this->check_database_health();
		$health['checks']['database'] = $db_check;
		if ( ! $db_check['passed'] ) {
			$health['warnings'][] = $db_check['message'];
		}

		// Cache check.
		$cache_check = $this->check_cache_health();
		$health['checks']['cache'] = $cache_check;
		if ( ! $cache_check['passed'] ) {
			$health['warnings'][] = $cache_check['message'];
		}

		// Error rate check.
		$error_check = $this->check_error_rate();
		$health['checks']['error_rate'] = $error_check;
		if ( ! $error_check['passed'] ) {
			$health['warnings'][] = $error_check['message'];
		}

		// Log health check results.
		$this->logger->log(
			'health_check',
			sprintf( 'Health check completed: %s', $health['status'] ),
			$health
		);

		// Send alert if critical issues found.
		if ( $health['status'] === 'error' ) {
			$this->send_health_alert( $health );
		}

		// Store latest health check.
		update_option( 'ma_webinarjam_last_health_check', $health );

		do_action( 'ma_webinarjam_health_check_completed', $health );

		return $health;
	}

	/**
	 * Check API connectivity.
	 *
	 * @since 1.0.0
	 * @return array Check result.
	 */
	private function check_api_connectivity() {
		$api_client = new WebinarJam_API_Client();
		$result = $api_client->test_connection();

		return array(
			'passed'  => ! is_wp_error( $result ),
			'message' => is_wp_error( $result ) ? $result->get_error_message() : 'API connection successful',
			'details' => array(
				'last_check' => current_time( 'mysql' ),
			),
		);
	}

	/**
	 * Check sync health.
	 *
	 * @since 1.0.0
	 * @return array Check result.
	 */
	private function check_sync_health() {
		// Get last sync time.
		$stats = get_option( 'ma_webinarjam_sync_stats', array() );
		$last_sync = isset( $stats['last_sync'] ) ? $stats['last_sync'] : 0;
		$hours_since = $last_sync ? ( time() - $last_sync ) / HOUR_IN_SECONDS : 999;

		$passed = $hours_since < 48; // Alert if no sync in 48 hours.

		return array(
			'passed'  => $passed,
			'message' => $passed 
				? sprintf( 'Last sync: %s ago', human_time_diff( $last_sync ) )
				: 'No sync in last 48 hours',
			'details' => array(
				'last_sync'    => $last_sync ? date( 'Y-m-d H:i:s', $last_sync ) : 'Never',
				'hours_since'  => round( $hours_since, 1 ),
				'success_rate' => isset( $stats['success_rate'] ) ? $stats['success_rate'] : 0,
			),
		);
	}

	/**
	 * Check cron jobs status.
	 *
	 * @since 1.0.0
	 * @return array Check result.
	 */
	private function check_cron_jobs() {
		$required_events = array(
			'ma_webinarjam_daily_sync',
			'ma_webinarjam_status_check',
			'ma_webinarjam_attendance_check',
		);

		$missing = array();
		foreach ( $required_events as $event ) {
			if ( ! wp_next_scheduled( $event ) ) {
				$missing[] = $event;
			}
		}

		$passed = empty( $missing );

		return array(
			'passed'  => $passed,
			'message' => $passed 
				? 'All cron jobs scheduled' 
				: sprintf( 'Missing cron jobs: %s', implode( ', ', $missing ) ),
			'details' => array(
				'missing_events' => $missing,
			),
		);
	}

	/**
	 * Check database health.
	 *
	 * @since 1.0.0
	 * @return array Check result.
	 */
	private function check_database_health() {
		$stats = WebinarJam_Database_Optimizer::get_query_stats();

		// Check for orphaned meta.
		global $wpdb;
		$orphaned = $wpdb->get_var(
			"SELECT COUNT(*) 
			FROM {$wpdb->postmeta} pm
			LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			WHERE pm.meta_key LIKE '_webinarjam_%'
			AND p.ID IS NULL"
		);

		$passed = $orphaned < 100; // Alert if many orphaned records.

		return array(
			'passed'  => $passed,
			'message' => $passed 
				? 'Database healthy' 
				: sprintf( '%d orphaned meta records found', $orphaned ),
			'details' => array(
				'total_courses' => $stats['total_courses'],
				'orphaned_meta' => $orphaned,
				'total_logs'    => $stats['total_logs'],
			),
		);
	}

	/**
	 * Check cache health.
	 *
	 * @since 1.0.0
	 * @return array Check result.
	 */
	private function check_cache_health() {
		$cache_stats = WebinarJam_Cache_Manager::get_cache_stats();

		// Alert if too many expired transients accumulating.
		$passed = $cache_stats['expired_transients'] < 50;

		return array(
			'passed'  => $passed,
			'message' => $passed 
				? 'Cache healthy' 
				: sprintf( '%d expired transients need cleanup', $cache_stats['expired_transients'] ),
			'details' => $cache_stats,
		);
	}

	/**
	 * Check error rate.
	 *
	 * @since 1.0.0
	 * @return array Check result.
	 */
	private function check_error_rate() {
		// Get errors from last 24 hours.
		$errors = $this->logger->get_logs(
			array(
				'type'  => 'error',
				'since' => date( 'Y-m-d H:i:s', strtotime( '-24 hours' ) ),
			)
		);

		$error_count = count( $errors );
		$passed      = $error_count < 10; // Alert if > 10 errors per day.

		return array(
			'passed'  => $passed,
			'message' => $passed 
				? sprintf( '%d errors in last 24 hours', $error_count )
				: sprintf( 'High error rate: %d errors in last 24 hours', $error_count ),
			'details' => array(
				'error_count' => $error_count,
				'recent_errors' => array_slice( $errors, 0, 5 ), // Include 5 most recent.
			),
		);
	}

	/**
	 * Send health alert email.
	 *
	 * @since 1.0.0
	 * @param array $health Health check results.
	 * @return bool True if sent successfully.
	 */
	private function send_health_alert( $health ) {
		$admin_email = get_option( 'admin_email' );
		$site_name   = get_bloginfo( 'name' );

		$subject = sprintf( '[%s] WebinarJam Integration Health Alert', $site_name );

		$message = sprintf(
			"Health Check Status: %s\n\n",
			strtoupper( $health['status'] )
		);

		if ( ! empty( $health['errors'] ) ) {
			$message .= "ERRORS:\n";
			foreach ( $health['errors'] as $error ) {
				$message .= "- {$error}\n";
			}
			$message .= "\n";
		}

		if ( ! empty( $health['warnings'] ) ) {
			$message .= "WARNINGS:\n";
			foreach ( $health['warnings'] as $warning ) {
				$message .= "- {$warning}\n";
			}
			$message .= "\n";
		}

		$message .= sprintf(
			"View details: %s\n\n",
			admin_url( 'admin.php?page=ma-settings&tab=webinarjam' )
		);

		$message .= sprintf( "Timestamp: %s\n", $health['timestamp'] );

		return wp_mail( $admin_email, $subject, $message );
	}

	/**
	 * Get sync success rate.
	 *
	 * @since 1.0.0
	 * @param int $days Number of days to analyze.
	 * @return float Success rate percentage.
	 */
	public function get_sync_success_rate( $days = 7 ) {
		$logs = $this->logger->get_logs(
			array(
				'type'  => 'sync',
				'since' => date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) ),
			)
		);

		if ( empty( $logs ) ) {
			return 100.0;
		}

		$total   = count( $logs );
		$success = 0;

		foreach ( $logs as $log ) {
			if ( stripos( $log['message'], 'success' ) !== false || stripos( $log['message'], 'completed' ) !== false ) {
				$success++;
			}
		}

		return ( $success / $total ) * 100;
	}

	/**
	 * Track API response time.
	 *
	 * @since 1.0.0
	 * @param string $endpoint API endpoint.
	 * @param float  $duration Request duration in seconds.
	 * @return void
	 */
	public function track_api_response_time( $endpoint, $duration ) {
		$stats = get_option( 'ma_webinarjam_api_stats', array() );

		if ( ! isset( $stats[ $endpoint ] ) ) {
			$stats[ $endpoint ] = array(
				'total_calls'    => 0,
				'total_duration' => 0,
				'avg_duration'   => 0,
				'min_duration'   => 999,
				'max_duration'   => 0,
			);
		}

		$stats[ $endpoint ]['total_calls']++;
		$stats[ $endpoint ]['total_duration'] += $duration;
		$stats[ $endpoint ]['avg_duration']    = $stats[ $endpoint ]['total_duration'] / $stats[ $endpoint ]['total_calls'];
		$stats[ $endpoint ]['min_duration']    = min( $stats[ $endpoint ]['min_duration'], $duration );
		$stats[ $endpoint ]['max_duration']    = max( $stats[ $endpoint ]['max_duration'], $duration );

		update_option( 'ma_webinarjam_api_stats', $stats );
	}

	/**
	 * Get API performance statistics.
	 *
	 * @since 1.0.0
	 * @return array API stats.
	 */
	public function get_api_stats() {
		return get_option( 'ma_webinarjam_api_stats', array() );
	}

	/**
	 * Get health check endpoint URL.
	 *
	 * @since 1.0.0
	 * @return string Health check URL.
	 */
	public function get_health_check_url() {
		return add_query_arg(
			array(
				'ma_webinarjam_health_check' => 1,
				'key' => $this->get_health_check_key(),
			),
			home_url()
		);
	}

	/**
	 * Get or generate health check key.
	 *
	 * @since 1.0.0
	 * @return string Health check key.
	 */
	private function get_health_check_key() {
		$key = get_option( 'ma_webinarjam_health_check_key' );

		if ( ! $key ) {
			$key = wp_generate_password( 32, false );
			update_option( 'ma_webinarjam_health_check_key', $key );
		}

		return $key;
	}

	/**
	 * Handle public health check endpoint.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_health_check_endpoint() {
		if ( ! isset( $_GET['ma_webinarjam_health_check'] ) ) {
			return;
		}

		// Verify key.
		$provided_key = isset( $_GET['key'] ) ? sanitize_text_field( $_GET['key'] ) : '';
		if ( $provided_key !== $this->get_health_check_key() ) {
			wp_die( 'Unauthorized', 'Access Denied', array( 'response' => 403 ) );
		}

		// Run health check.
		$health = $this->run_health_check();

		// Return JSON response.
		header( 'Content-Type: application/json' );
		echo wp_json_encode( $health );
		exit;
	}
}
