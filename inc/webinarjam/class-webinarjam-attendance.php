<?php
/**
 * WebinarJam Attendance Handler
 *
 * Handles automatic course completion for webinar attendees.
 *
 * @package ma_plugin
 * @since 1.0.0
 */

namespace ma_plugin\classes\WebinarJam;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WebinarJam Attendance class.
 *
 * Fetches webinar attendees from WebinarJam API and marks
 * corresponding LearnDash courses as complete.
 *
 * @since 1.0.0
 */
class WebinarJam_Attendance {

	/**
	 * API Client instance.
	 *
	 * @since 1.0.0
	 * @var WebinarJam_API_Client
	 */
	private $api_client;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param WebinarJam_API_Client $api_client API client instance.
	 */
	public function __construct( $api_client ) {
		$this->api_client = $api_client;
	}

	/**
	 * Check attendance for a specific webinar course.
	 *
	 * @since 1.0.0
	 * @param int $course_id Course ID.
	 * @return array|WP_Error Results array on success, WP_Error on failure.
	 */
	public function check_attendance( $course_id ) {
		// Get webinar ID.
		$webinar_id = get_field( 'webinarjam_webinar_id', $course_id );

		if ( ! $webinar_id ) {
			return new \WP_Error( 'no_webinar_id', __( 'Course has no webinar ID.', 'ma-plugin' ) );
		}

		ma_log_webinarjam_debug( "Checking attendance for course {$course_id} (webinar {$webinar_id})..." );

		// Fetch attendees from API.
		$attendees = $this->api_client->get_attendees( $webinar_id );

		if ( is_wp_error( $attendees ) ) {
			ma_log_webinarjam_debug( "Failed to fetch attendees: " . $attendees->get_error_message(), 'error' );
			return $attendees;
		}

		$results = array(
			'completed' => 0,
			'skipped'   => 0,
			'errors'    => 0,
		);

		// Process each attendee.
		foreach ( $attendees as $attendee ) {
			$result = $this->process_attendee( $attendee, $course_id );

			if ( true === $result ) {
				$results['completed']++;
			} elseif ( false === $result ) {
				$results['skipped']++;
			} else {
				$results['errors']++;
			}
		}

		// Add sync log entry.
		ma_add_webinar_sync_log(
			$course_id,
			sprintf(
				'Attendance check completed. Marked %d users as complete.',
				$results['completed']
			)
		);

		ma_log_webinarjam_debug(
			sprintf(
				'Attendance check for course %d: Completed: %d, Skipped: %d, Errors: %d',
				$course_id,
				$results['completed'],
				$results['skipped'],
				$results['errors']
			)
		);

		// Update last attendance check timestamp.
		update_field( 'webinarjam_last_attendance_check', current_time( 'mysql' ), $course_id );

		return $results;
	}

	/**
	 * Process individual attendee.
	 *
	 * @since 1.0.0
	 * @param array $attendee Attendee data from API.
	 * @param int   $course_id Course ID.
	 * @return bool|WP_Error True if completed, false if skipped, WP_Error on error.
	 */
	private function process_attendee( $attendee, $course_id ) {
		// Transform attendee data.
		$transformed = WebinarJam_Transformer::transform_attendee_data( $attendee );

		if ( is_wp_error( $transformed ) ) {
			return $transformed;
		}

		// Match to WordPress user.
		$user_id = $this->match_user( $transformed['email'] );

		if ( ! $user_id ) {
			ma_log_webinarjam_debug( "No user found for email: {$transformed['email']}" );
			return false;
		}

		// Check if user is enrolled.
		$is_enrolled = sfwd_lms_has_access( $course_id, $user_id );

		if ( ! $is_enrolled ) {
			ma_log_webinarjam_debug( "User {$user_id} not enrolled in course {$course_id}, skipping." );
			return false;
		}

		// Check if already completed.
		$already_completed = learndash_course_completed( $user_id, $course_id );

		if ( $already_completed ) {
			ma_log_webinarjam_debug( "User {$user_id} already completed course {$course_id}, skipping." );
			return false;
		}

		// Mark course as complete.
		$completed = learndash_process_mark_complete( $user_id, $course_id );

		if ( $completed ) {
			ma_log_webinarjam_debug( "Marked course {$course_id} complete for user {$user_id}." );

			// Add user meta for tracking.
			update_user_meta( $user_id, "webinarjam_attended_{$course_id}", time() );

			// Fire action for extensions.
			do_action( 'ma_webinarjam_attendance_marked', $user_id, $course_id, $transformed );

			return true;
		}

		ma_log_webinarjam_debug( "Failed to mark course {$course_id} complete for user {$user_id}.", 'error' );
		return new \WP_Error( 'completion_failed', __( 'Failed to mark course complete.', 'ma-plugin' ) );
	}

	/**
	 * Match attendee email to WordPress user.
	 *
	 * @since 1.0.0
	 * @param string $email Email address.
	 * @return int|false User ID on success, false on failure.
	 */
	private function match_user( $email ) {
		// Validate email.
		if ( ! is_email( $email ) ) {
			return false;
		}

		// Get user by email.
		$user = get_user_by( 'email', $email );

		if ( ! $user ) {
			return false;
		}

		return $user->ID;
	}

	/**
	 * Batch check attendance for all webinar courses.
	 *
	 * @since 1.0.0
	 * @return array Results summary.
	 */
	public function batch_check_attendance() {
		ma_log_webinarjam_debug( 'Starting batch attendance check...' );

		$totals = array(
			'courses'   => 0,
			'completed' => 0,
			'skipped'   => 0,
			'errors'    => 0,
		);

		// Get all completed webinar courses that haven't been checked recently.
		$courses = $this->get_courses_needing_attendance_check();

		foreach ( $courses as $course_id ) {
			$totals['courses']++;

			$results = $this->check_attendance( $course_id );

			if ( ! is_wp_error( $results ) ) {
				$totals['completed'] += $results['completed'];
				$totals['skipped']   += $results['skipped'];
				$totals['errors']    += $results['errors'];
			} else {
				$totals['errors']++;
			}
		}

		ma_log_webinarjam_debug( sprintf(
			'Batch attendance check completed. Courses: %d, Users completed: %d, Skipped: %d, Errors: %d',
			$totals['courses'],
			$totals['completed'],
			$totals['skipped'],
			$totals['errors']
		) );

		return $totals;
	}

	/**
	 * Get courses needing attendance check.
	 *
	 * @since 1.0.0
	 * @return array Array of course IDs.
	 */
	private function get_courses_needing_attendance_check() {
		$courses = ma_get_webinar_courses();

		$needing_check = array();

		foreach ( $courses as $course_id ) {
			// Only check completed webinars.
			$status = ma_get_webinar_status( $course_id );

			if ( 'completed' !== $status ) {
				continue;
			}

			// Check if already checked recently (within 24 hours).
			$last_check = get_field( 'webinarjam_last_attendance_check', $course_id );

			if ( $last_check ) {
				$last_check_timestamp = strtotime( $last_check );
				$hours_since_check    = ( time() - $last_check_timestamp ) / 3600;

				// Skip if checked within last 24 hours.
				if ( $hours_since_check < 24 ) {
					continue;
				}
			}

			// Get webinar end date.
			$schedules = ma_get_webinar_schedule( $course_id );

			if ( empty( $schedules ) ) {
				continue;
			}

			// Get last schedule.
			$last_schedule = end( $schedules );
			$end_timestamp = strtotime( $last_schedule['schedule_date'] );

			// Get duration.
			$duration = ! empty( $last_schedule['schedule_duration'] ) ? absint( $last_schedule['schedule_duration'] ) : 60;

			$end_timestamp += ( $duration * 60 );

			// Only check if webinar ended at least 30 minutes ago.
			if ( time() < ( $end_timestamp + ( 30 * 60 ) ) ) {
				continue;
			}

			$needing_check[] = $course_id;
		}

		return $needing_check;
	}
}
