<?php
/**
 * WebinarJam Scheduler
 *
 * Manages all scheduled events for WebinarJam integration.
 *
 * @package ma_plugin
 * @since 1.0.0
 */

namespace ma_plugin\classes\WebinarJam;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WebinarJam Scheduler class.
 *
 * Handles scheduling and unscheduling of WebinarJam-related cron events.
 *
 * @since 1.0.0
 */
class WebinarJam_Scheduler {

	/**
	 * API Client instance.
	 *
	 * @since 1.0.0
	 * @var WebinarJam_API_Client
	 */
	private $api_client;

	/**
	 * Sync handler instance.
	 *
	 * @since 1.0.0
	 * @var WebinarJam_Sync
	 */
	private $sync_handler;

	/**
	 * Importer instance.
	 *
	 * @since 1.0.0
	 * @var WebinarJam_Importer
	 */
	private $importer;

	/**
	 * Status handler instance.
	 *
	 * @since 1.0.0
	 * @var WebinarJam_Status
	 */
	private $status_handler;

	/**
	 * Attendance handler instance.
	 *
	 * @since 1.0.0
	 * @var WebinarJam_Attendance
	 */
	private $attendance_handler;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param WebinarJam_API_Client $api_client API client instance.
	 * @param WebinarJam_Sync       $sync_handler Sync handler instance.
	 * @param WebinarJam_Importer   $importer Importer instance.
	 * @param WebinarJam_Status     $status_handler Status handler instance.
	 * @param WebinarJam_Attendance $attendance_handler Attendance handler instance.
	 */
	public function __construct( $api_client, $sync_handler, $importer, $status_handler, $attendance_handler ) {
		$this->api_client          = $api_client;
		$this->sync_handler        = $sync_handler;
		$this->importer            = $importer;
		$this->status_handler      = $status_handler;
		$this->attendance_handler  = $attendance_handler;
		$this->setup_hooks();
	}

	/**
	 * Setup WordPress hooks for scheduled events.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function setup_hooks() {
		// Register scheduled event hooks (will be implemented in Section 5).
		add_action( 'ma_webinarjam_daily_sync', array( $this, 'run_daily_sync' ) );
		add_action( 'ma_webinarjam_import_webinar', array( $this, 'run_webinar_import' ), 10, 1 );
		add_action( 'ma_webinarjam_update_status', array( $this, 'run_status_update' ), 10, 1 );
		add_action( 'ma_webinarjam_check_attendance', array( $this, 'run_attendance_check' ), 10, 1 );
	}

	/**
	 * Schedule daily sync event.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function schedule_daily_sync() {
		$hook = 'ma_webinarjam_daily_sync';

		// Check if already scheduled.
		if ( wp_next_scheduled( $hook ) ) {
			ma_log_webinarjam_debug( 'Daily sync already scheduled.' );
			return;
		}

		// Get sync frequency from settings.
		$frequency = ma_get_webinarjam_setting( 'sync_frequency', 'daily' );

		// Schedule the event.
		$scheduled = wp_schedule_event( time(), $frequency, $hook );

		if ( false === $scheduled ) {
			ma_log_webinarjam_debug( 'Failed to schedule daily sync.', 'error' );
		} else {
			ma_log_webinarjam_debug( "Daily sync scheduled with frequency: {$frequency}." );
		}
	}

	/**
	 * Schedule individual webinar import.
	 *
	 * @since 1.0.0
	 * @param string $webinar_id WebinarJam webinar ID.
	 * @return void
	 */
	public function schedule_webinar_import( $webinar_id ) {
		if ( empty( $webinar_id ) ) {
			ma_log_webinarjam_debug( 'Cannot schedule import: Invalid webinar ID.', 'error' );
			return;
		}

		$hook = 'ma_webinarjam_import_webinar';

		// Schedule to run in 1 minute.
		$timestamp = time() + 60;

		// Check if already scheduled for this webinar.
		$scheduled = wp_next_scheduled( $hook, array( $webinar_id ) );

		if ( $scheduled ) {
			ma_log_webinarjam_debug( "Import already scheduled for webinar {$webinar_id}." );
			return;
		}

		// Schedule the import.
		wp_schedule_single_event( $timestamp, $hook, array( $webinar_id ) );

		ma_log_webinarjam_debug( "Scheduled import for webinar {$webinar_id} at " . gmdate( 'Y-m-d H:i:s', $timestamp ) . ' UTC.' );
	}

	/**
	 * Schedule webinar status update.
	 *
	 * @since 1.0.0
	 * @param int $course_id LearnDash course ID.
	 * @param int $timestamp Unix timestamp when status should update.
	 * @return void
	 */
	public function schedule_status_update( $course_id, $timestamp ) {
		if ( empty( $course_id ) || empty( $timestamp ) ) {
			ma_log_webinarjam_debug( 'Cannot schedule status update: Invalid course ID or timestamp.', 'error' );
			return;
		}

		$hook = 'ma_webinarjam_update_status';

		// Check if already scheduled.
		$scheduled = wp_next_scheduled( $hook, array( $course_id ) );

		if ( $scheduled ) {
			// Unschedule existing event.
			wp_unschedule_event( $scheduled, $hook, array( $course_id ) );
		}

		// Schedule the status update.
		wp_schedule_single_event( $timestamp, $hook, array( $course_id ) );

		ma_log_webinarjam_debug( "Scheduled status update for course {$course_id} at " . gmdate( 'Y-m-d H:i:s', $timestamp ) . ' UTC.' );
	}

	/**
	 * Schedule attendance check.
	 *
	 * @since 1.0.0
	 * @param int $course_id LearnDash course ID.
	 * @param int $timestamp Unix timestamp when attendance should be checked.
	 * @return void
	 */
	public function schedule_attendance_check( $course_id, $timestamp ) {
		if ( empty( $course_id ) || empty( $timestamp ) ) {
			ma_log_webinarjam_debug( 'Cannot schedule attendance check: Invalid course ID or timestamp.', 'error' );
			return;
		}

		$hook = 'ma_webinarjam_check_attendance';

		// Check if already scheduled.
		$scheduled = wp_next_scheduled( $hook, array( $course_id ) );

		if ( $scheduled ) {
			// Unschedule existing event.
			wp_unschedule_event( $scheduled, $hook, array( $course_id ) );
		}

		// Schedule the attendance check.
		wp_schedule_single_event( $timestamp, $hook, array( $course_id ) );

		ma_log_webinarjam_debug( "Scheduled attendance check for course {$course_id} at " . gmdate( 'Y-m-d H:i:s', $timestamp ) . ' UTC.' );
	}

	/**
	 * Unschedule all WebinarJam events.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function unschedule_all() {
		$hooks = array(
			'ma_webinarjam_daily_sync',
			'ma_webinarjam_import_webinar',
			'ma_webinarjam_update_status',
			'ma_webinarjam_check_attendance',
		);

		foreach ( $hooks as $hook ) {
			$timestamp = wp_next_scheduled( $hook );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $hook );
			}
		}

		ma_log_webinarjam_debug( 'Unscheduled all WebinarJam events.' );
	}

	/**
	 * Run daily sync.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function run_daily_sync() {
		ma_log_webinarjam_debug( 'Daily sync event triggered.' );

		// Run the sync.
		$results = $this->sync_handler->run();

		// Also batch update status.
		$this->status_handler->batch_update_status();

		ma_log_webinarjam_debug( 'Daily sync event completed.' );
	}

	/**
	 * Run webinar import.
	 *
	 * @since 1.0.0
	 * @param string $webinar_id WebinarJam webinar ID.
	 * @return void
	 */
	public function run_webinar_import( $webinar_id ) {
		ma_log_webinarjam_debug( "Import event triggered for webinar {$webinar_id}." );

		// Run the import.
		$result = $this->importer->import( $webinar_id );

		if ( is_wp_error( $result ) ) {
			ma_log_webinarjam_debug( "Import failed for webinar {$webinar_id}: " . $result->get_error_message(), 'error' );
		}
	}

	/**
	 * Run status update.
	 *
	 * @since 1.0.0
	 * @param int $course_id LearnDash course ID.
	 * @return void
	 */
	public function run_status_update( $course_id ) {
		ma_log_webinarjam_debug( "Status update event triggered for course {$course_id}." );

		// Update the status.
		$this->status_handler->update_status( $course_id );
	}

	/**
	 * Run attendance check.
	 *
	 * @since 1.0.0
	 * @param int $course_id LearnDash course ID.
	 * @return void
	 */
	public function run_attendance_check( $course_id ) {
		ma_log_webinarjam_debug( "Attendance check event triggered for course {$course_id}." );

		// Check attendance.
		$result = $this->attendance_handler->check_attendance( $course_id );

		if ( is_wp_error( $result ) ) {
			ma_log_webinarjam_debug( "Attendance check failed for course {$course_id}: " . $result->get_error_message(), 'error' );
		}
	}
}
