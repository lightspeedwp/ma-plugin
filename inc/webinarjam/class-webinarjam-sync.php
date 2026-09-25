<?php
/**
 * WebinarJam Sync Handler
 *
 * Handles daily synchronization of webinars from WebinarJam API.
 *
 * @package ma_plugin
 * @since 1.0.0
 */

namespace ma_plugin\classes\WebinarJam;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WebinarJam Sync class.
 *
 * Manages the daily synchronization process, comparing API webinars
 * with existing courses and scheduling imports/updates as needed.
 *
 * @since 1.0.0
 */
class WebinarJam_Sync {

	/**
	 * API Client instance.
	 *
	 * @since 1.0.0
	 * @var WebinarJam_API_Client
	 */
	private $api_client;

	/**
	 * Scheduler instance.
	 *
	 * @since 1.0.0
	 * @var WebinarJam_Scheduler
	 */
	private $scheduler;

	/**
	 * Sync results.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $results = array(
		'new'     => 0,
		'updated' => 0,
		'errors'  => 0,
		'skipped' => 0,
	);

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param WebinarJam_API_Client $api_client API client instance.
	 * @param WebinarJam_Scheduler  $scheduler Scheduler instance.
	 */
	public function __construct( $api_client, $scheduler ) {
		$this->api_client = $api_client;
		$this->scheduler  = $scheduler;
	}

	/**
	 * Run the daily sync process.
	 *
	 * @since 1.0.0
	 * @return array Sync results.
	 */
	public function run() {
		ma_log_webinarjam_debug( 'Starting daily webinar sync...' );

		// Check if API is configured.
		if ( ! ma_is_webinarjam_configured() ) {
			ma_log_webinarjam_debug( 'API not configured, aborting sync.', 'error' );
			return $this->results;
		}

		// Fetch all webinars from API.
		$api_webinars = $this->api_client->get_all_webinars( false );

		if ( is_wp_error( $api_webinars ) ) {
			ma_log_webinarjam_debug( 'Failed to fetch webinars: ' . $api_webinars->get_error_message(), 'error' );
			$this->results['errors']++;
			return $this->results;
		}

		ma_log_webinarjam_debug( 'Fetched ' . count( $api_webinars ) . ' webinars from API.' );

		// Get existing webinar courses.
		$existing_courses = $this->get_existing_webinar_courses();

		// Extract webinar IDs from API.
		$api_webinar_ids = WebinarJam_Transformer::extract_webinar_ids( $api_webinars );

		// Identify new webinars.
		$new_webinar_ids = array_diff( $api_webinar_ids, array_keys( $existing_courses ) );

		// Identify webinars to update.
		$update_webinar_ids = array_intersect( $api_webinar_ids, array_keys( $existing_courses ) );

		// Schedule imports for new webinars.
		foreach ( $new_webinar_ids as $webinar_id ) {
			$this->scheduler->schedule_webinar_import( $webinar_id );
			$this->results['new']++;
		}

		// Update existing webinars.
		foreach ( $update_webinar_ids as $webinar_id ) {
			$this->update_existing_webinar( $webinar_id, $existing_courses[ $webinar_id ] );
		}

		// Log completion.
		$this->log_sync_completion();

		// Update last sync timestamp.
		update_option( 'webinarjam_last_sync', time() );

		// Send admin notification if enabled.
		$this->maybe_send_admin_notification();

		return $this->results;
	}

	/**
	 * Get existing webinar courses mapped by WebinarJam ID.
	 *
	 * @since 1.0.0
	 * @return array Array of course IDs keyed by webinar ID.
	 */
	private function get_existing_webinar_courses() {
		$courses = ma_get_webinar_courses();

		$mapped = array();

		foreach ( $courses as $course_id ) {
			$webinar_id = get_field( 'webinarjam_webinar_id', $course_id );

			if ( $webinar_id ) {
				$mapped[ $webinar_id ] = $course_id;
			}
		}

		return $mapped;
	}

	/**
	 * Update existing webinar course.
	 *
	 * @since 1.0.0
	 * @param string $webinar_id WebinarJam webinar ID.
	 * @param int    $course_id WordPress course ID.
	 * @return bool True on success, false on failure.
	 */
	private function update_existing_webinar( $webinar_id, $course_id ) {
		// Check if sync is enabled for this course.
		$sync_enabled = get_field( 'webinarjam_sync_enabled', $course_id );

		if ( ! $sync_enabled ) {
			ma_log_webinarjam_debug( "Sync disabled for course {$course_id}, skipping." );
			$this->results['skipped']++;
			return false;
		}

		// Fetch webinar data from API.
		$webinar = $this->api_client->get_webinar( $webinar_id, false );

		if ( is_wp_error( $webinar ) ) {
			ma_log_webinarjam_debug( "Failed to fetch webinar {$webinar_id}: " . $webinar->get_error_message(), 'error' );
			$this->results['errors']++;
			return false;
		}

		// Check if schedule has changed.
		$schedule_changed = $this->has_schedule_changed( $course_id, $webinar );

		if ( $schedule_changed ) {
			// Update schedule.
			$transformed_data = WebinarJam_Transformer::transform_webinar_to_course_data( $webinar );

			if ( is_wp_error( $transformed_data ) ) {
				ma_log_webinarjam_debug( "Failed to transform webinar {$webinar_id}: " . $transformed_data->get_error_message(), 'error' );
				$this->results['errors']++;
				return false;
			}

			// Merge with existing course data.
			$merged_data = WebinarJam_Transformer::merge_with_existing_course( $course_id, $transformed_data );

			if ( empty( $merged_data ) ) {
				$this->results['skipped']++;
				return false;
			}

			// Update the course.
			$updated = wp_update_post( $merged_data, true );

			if ( is_wp_error( $updated ) ) {
				ma_log_webinarjam_debug( "Failed to update course {$course_id}: " . $updated->get_error_message(), 'error' );
				$this->results['errors']++;
				return false;
			}

			// Update custom fields.
			if ( ! empty( $merged_data['meta_input'] ) ) {
				foreach ( $merged_data['meta_input'] as $key => $value ) {
					update_field( $key, $value, $course_id );
				}
			}

			// Add sync log entry.
			ma_add_webinar_sync_log( $course_id, 'Schedule updated from WebinarJam' );

			// Update connected event if exists.
			$event_id = ma_get_event_by_course_id( $course_id );
			if ( $event_id ) {
				$this->update_event_dates( $event_id, $course_id );
			}

			ma_log_webinarjam_debug( "Updated course {$course_id} for webinar {$webinar_id}." );
			$this->results['updated']++;
		} else {
			$this->results['skipped']++;
		}

		return true;
	}

	/**
	 * Check if webinar schedule has changed.
	 *
	 * @since 1.0.0
	 * @param int   $course_id Course ID.
	 * @param array $webinar_data Webinar data from API.
	 * @return bool True if schedule changed.
	 */
	private function has_schedule_changed( $course_id, $webinar_data ) {
		if ( empty( $webinar_data['schedules'] ) ) {
			return false;
		}

		// Get existing schedule.
		$existing_schedule = ma_get_webinar_schedule( $course_id );

		// Transform new schedule.
		$new_schedule = WebinarJam_Transformer::transform_schedule_data( $webinar_data['schedules'] );

		// Compare counts.
		if ( count( $existing_schedule ) !== count( $new_schedule ) ) {
			return true;
		}

		// Compare dates.
		foreach ( $new_schedule as $index => $schedule ) {
			if ( ! isset( $existing_schedule[ $index ] ) ) {
				return true;
			}

			if ( $schedule['schedule_date'] !== $existing_schedule[ $index ]['schedule_date'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Update event dates based on course schedule.
	 *
	 * @since 1.0.0
	 * @param int $event_id Event ID.
	 * @param int $course_id Course ID.
	 * @return bool True on success.
	 */
	private function update_event_dates( $event_id, $course_id ) {
		$next_date = ma_get_next_webinar_date( $course_id );

		if ( ! $next_date ) {
			return false;
		}

		// Update event start date.
		$start_timestamp = strtotime( $next_date );

		// Get duration from schedule.
		$schedules = ma_get_webinar_schedule( $course_id );
		$duration  = 60; // Default 60 minutes.

		if ( ! empty( $schedules[0]['schedule_duration'] ) ) {
			$duration = absint( $schedules[0]['schedule_duration'] );
		}

		// Calculate end time.
		$end_timestamp = $start_timestamp + ( $duration * 60 );

		// Update event meta.
		update_post_meta( $event_id, '_EventStartDate', gmdate( 'Y-m-d H:i:s', $start_timestamp ) );
		update_post_meta( $event_id, '_EventEndDate', gmdate( 'Y-m-d H:i:s', $end_timestamp ) );

		// Sync other event data.
		ma_sync_event_from_course( $event_id, $course_id );

		ma_log_webinarjam_debug( "Updated event {$event_id} dates from course {$course_id}." );

		return true;
	}

	/**
	 * Log sync completion.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function log_sync_completion() {
		$message = sprintf(
			'Daily sync completed. New: %d, Updated: %d, Skipped: %d, Errors: %d',
			$this->results['new'],
			$this->results['updated'],
			$this->results['skipped'],
			$this->results['errors']
		);

		ma_log_webinarjam_debug( $message );

		// Store results in option for admin display.
		update_option( 'webinarjam_last_sync_results', $this->results );
	}

	/**
	 * Send admin notification if enabled.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function maybe_send_admin_notification() {
		// Check if notifications are enabled.
		$notifications_enabled = apply_filters( 'ma_webinarjam_sync_notifications', false );

		if ( ! $notifications_enabled ) {
			return;
		}

		// Only send if there were changes or errors.
		if ( $this->results['new'] === 0 && $this->results['updated'] === 0 && $this->results['errors'] === 0 ) {
			return;
		}

		$admin_email = get_option( 'admin_email' );
		$subject     = __( 'WebinarJam Sync Report', 'ma-plugin' );

		$message = sprintf(
			__( "Daily webinar sync completed:\n\nNew webinars: %d\nUpdated webinars: %d\nSkipped: %d\nErrors: %d", 'ma-plugin' ),
			$this->results['new'],
			$this->results['updated'],
			$this->results['skipped'],
			$this->results['errors']
		);

		wp_mail( $admin_email, $subject, $message );
	}
}
