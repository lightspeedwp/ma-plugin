<?php
/**
 * WebinarJam Status Manager
 *
 * Handles automatic webinar status updates.
 *
 * @package ma_plugin
 * @since 1.0.0
 */

namespace ma_plugin\classes\WebinarJam;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WebinarJam Status class.
 *
 * Manages automatic status transitions for webinars
 * (upcoming -> live -> completed) based on scheduled times.
 *
 * @since 1.0.0
 */
class WebinarJam_Status {

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
	 * Update status for a specific webinar course.
	 *
	 * @since 1.0.0
	 * @param int $course_id Course ID.
	 * @return bool True on success.
	 */
	public function update_status( $course_id ) {
		// Get webinar ID.
		$webinar_id = get_field( 'webinarjam_webinar_id', $course_id );

		if ( ! $webinar_id ) {
			ma_log_webinarjam_debug( "Course {$course_id} has no webinar ID.", 'warning' );
			return false;
		}

		ma_log_webinarjam_debug( "Updating status for course {$course_id} (webinar {$webinar_id})..." );

		// Get current status.
		$current_status = ma_get_webinar_status( $course_id );

		// Determine new status.
		$new_status = $this->determine_status( $course_id );

		// Only update if status changed.
		if ( $current_status === $new_status ) {
			ma_log_webinarjam_debug( "Status unchanged ({$current_status}) for course {$course_id}." );
			return true;
		}

		// Update status.
		$updated = ma_update_webinar_status( $course_id, $new_status );

		if ( $updated ) {
			ma_log_webinarjam_debug( "Updated course {$course_id} status from {$current_status} to {$new_status}." );

			// Add sync log entry.
			ma_add_webinar_sync_log( $course_id, "Status changed from {$current_status} to {$new_status}" );

			// Handle status-specific actions.
			$this->handle_status_change( $course_id, $new_status, $current_status );

			// Sync event status if exists.
			$event_id = ma_get_event_by_course_id( $course_id );
			if ( $event_id ) {
				$this->sync_event_status( $event_id, $new_status );
			}
		}

		return $updated;
	}

	/**
	 * Batch update status for all webinar courses.
	 *
	 * @since 1.0.0
	 * @return array Results array with counts.
	 */
	public function batch_update_status() {
		ma_log_webinarjam_debug( 'Starting batch status update...' );

		$results = array(
			'updated' => 0,
			'skipped' => 0,
			'errors'  => 0,
		);

		// Get all webinar courses.
		$courses = ma_get_webinar_courses();

		foreach ( $courses as $course_id ) {
			try {
				$updated = $this->update_status( $course_id );

				if ( $updated ) {
					$results['updated']++;
				} else {
					$results['skipped']++;
				}
			} catch ( \Exception $e ) {
				ma_log_webinarjam_debug( "Error updating status for course {$course_id}: " . $e->getMessage(), 'error' );
				$results['errors']++;
			}
		}

		ma_log_webinarjam_debug( sprintf(
			'Batch status update completed. Updated: %d, Skipped: %d, Errors: %d',
			$results['updated'],
			$results['skipped'],
			$results['errors']
		) );

		return $results;
	}

	/**
	 * Determine webinar status based on schedule.
	 *
	 * @since 1.0.0
	 * @param int $course_id Course ID.
	 * @return string Status (upcoming, live, completed).
	 */
	private function determine_status( $course_id ) {
		$schedules = ma_get_webinar_schedule( $course_id );

		if ( empty( $schedules ) ) {
			return 'completed';
		}

		$now = current_time( 'timestamp' );

		// Sort schedules by date.
		usort( $schedules, function( $a, $b ) {
			return strtotime( $a['schedule_date'] ) - strtotime( $b['schedule_date'] );
		} );

		// Check each schedule.
		$has_future = false;
		$has_live   = false;

		foreach ( $schedules as $schedule ) {
			$schedule_timestamp = strtotime( $schedule['schedule_date'] );

			// Get duration (default 60 minutes).
			$duration = ! empty( $schedule['schedule_duration'] ) ? absint( $schedule['schedule_duration'] ) : 60;

			$end_timestamp = $schedule_timestamp + ( $duration * 60 );

			// Check if currently live.
			if ( $now >= $schedule_timestamp && $now <= $end_timestamp ) {
				$has_live = true;
				break;
			}

			// Check if future.
			if ( $now < $schedule_timestamp ) {
				$has_future = true;
			}
		}

		if ( $has_live ) {
			return 'live';
		}

		if ( $has_future ) {
			return 'upcoming';
		}

		return 'completed';
	}

	/**
	 * Handle status change actions.
	 *
	 * @since 1.0.0
	 * @param int    $course_id Course ID.
	 * @param string $new_status New status.
	 * @param string $old_status Old status.
	 * @return void
	 */
	private function handle_status_change( $course_id, $new_status, $old_status ) {
		// When webinar goes live.
		if ( 'live' === $new_status && 'upcoming' === $old_status ) {
			// Schedule attendance check 30 minutes after webinar end.
			$schedules = ma_get_webinar_schedule( $course_id );

			if ( ! empty( $schedules ) ) {
				$current_schedule = $this->get_current_schedule( $schedules );

				if ( $current_schedule ) {
					$end_time = strtotime( $current_schedule['schedule_date'] );
					$duration = ! empty( $current_schedule['schedule_duration'] ) ? absint( $current_schedule['schedule_duration'] ) : 60;

					$check_time = $end_time + ( $duration * 60 ) + ( 30 * 60 ); // 30 minutes after end.

					$this->scheduler->schedule_attendance_check( $course_id, $check_time );
				}
			}

			// Fire action for extensions.
			do_action( 'ma_webinarjam_status_live', $course_id );
		}

		// When webinar completes.
		if ( 'completed' === $new_status ) {
			// Fire action for extensions.
			do_action( 'ma_webinarjam_status_completed', $course_id );
		}

		// General status change action.
		do_action( 'ma_webinarjam_status_changed', $course_id, $new_status, $old_status );
	}

	/**
	 * Get current schedule from array.
	 *
	 * @since 1.0.0
	 * @param array $schedules Array of schedules.
	 * @return array|null Current schedule or null.
	 */
	private function get_current_schedule( $schedules ) {
		$now = current_time( 'timestamp' );

		foreach ( $schedules as $schedule ) {
			$schedule_timestamp = strtotime( $schedule['schedule_date'] );
			$duration           = ! empty( $schedule['schedule_duration'] ) ? absint( $schedule['schedule_duration'] ) : 60;
			$end_timestamp      = $schedule_timestamp + ( $duration * 60 );

			if ( $now >= $schedule_timestamp && $now <= $end_timestamp ) {
				return $schedule;
			}
		}

		return null;
	}

	/**
	 * Sync event status.
	 *
	 * @since 1.0.0
	 * @param int    $event_id Event ID.
	 * @param string $status Webinar status.
	 * @return void
	 */
	private function sync_event_status( $event_id, $status ) {
		// Update event webinar status field.
		update_field( 'webinarjam_status', $status, $event_id );

		// Maybe update event post status.
		if ( 'completed' === $status ) {
			$post_status = get_post_status( $event_id );

			// Only auto-update if currently published.
			if ( 'publish' === $post_status ) {
				wp_update_post( array(
					'ID'          => $event_id,
					'post_status' => 'draft',
				) );

				ma_log_webinarjam_debug( "Moved completed event {$event_id} to draft." );
			}
		}
	}
}
