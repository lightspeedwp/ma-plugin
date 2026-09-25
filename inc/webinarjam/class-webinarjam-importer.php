<?php
/**
 * WebinarJam Importer
 *
 * Handles importing individual webinars as LearnDash courses.
 *
 * @package ma_plugin
 * @since 1.0.0
 */

namespace ma_plugin\classes\WebinarJam;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WebinarJam Importer class.
 *
 * Creates new LearnDash courses from WebinarJam webinars
 * and creates corresponding Events Calendar events.
 *
 * @since 1.0.0
 */
class WebinarJam_Importer {

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
	 * Import a webinar as a LearnDash course.
	 *
	 * @since 1.0.0
	 * @param string $webinar_id WebinarJam webinar ID.
	 * @return int|WP_Error Course ID on success, WP_Error on failure.
	 */
	public function import( $webinar_id ) {
		ma_log_webinarjam_debug( "Starting import for webinar {$webinar_id}..." );

		// Check if webinar already exists.
		$existing_course_id = ma_get_course_by_webinarjam_id( $webinar_id );

		if ( $existing_course_id ) {
			ma_log_webinarjam_debug( "Webinar {$webinar_id} already exists as course {$existing_course_id}.", 'warning' );
			return new \WP_Error( 'webinar_exists', __( 'Webinar already imported.', 'ma-plugin' ) );
		}

		// Fetch webinar data from API.
		$webinar = $this->api_client->get_webinar( $webinar_id, false );

		if ( is_wp_error( $webinar ) ) {
			ma_log_webinarjam_debug( "Failed to fetch webinar {$webinar_id}: " . $webinar->get_error_message(), 'error' );
			return $webinar;
		}

		// Transform webinar data.
		$course_data = WebinarJam_Transformer::transform_webinar_to_course_data( $webinar );

		if ( is_wp_error( $course_data ) ) {
			ma_log_webinarjam_debug( "Failed to transform webinar {$webinar_id}: " . $course_data->get_error_message(), 'error' );
			return $course_data;
		}

		// Create the course.
		$course_id = $this->create_course( $course_data );

		if ( is_wp_error( $course_id ) ) {
			return $course_id;
		}

		// Assign webinar course type.
		WebinarJam_Taxonomy::assign_webinar_type( $course_id );

		// Create corresponding event for upcoming webinars only.
		$status = get_field( 'webinarjam_status', $course_id );

		if ( 'upcoming' === $status ) {
			$event_id = $this->create_event( $course_id );

			if ( is_wp_error( $event_id ) ) {
				ma_log_webinarjam_debug( "Failed to create event for course {$course_id}: " . $event_id->get_error_message(), 'error' );
			}
		}

		// Schedule status update if webinar is upcoming.
		if ( 'upcoming' === $status ) {
			$next_date = ma_get_next_webinar_date( $course_id );

			if ( $next_date ) {
				$start_timestamp = strtotime( $next_date );
				$this->scheduler->schedule_status_update( $course_id, $start_timestamp );
			}
		}

		ma_log_webinarjam_debug( "Successfully imported webinar {$webinar_id} as course {$course_id}." );

		return $course_id;
	}

	/**
	 * Create LearnDash course.
	 *
	 * @since 1.0.0
	 * @param array $course_data Course data.
	 * @return int|WP_Error Course ID on success, WP_Error on failure.
	 */
	private function create_course( $course_data ) {
		// Insert the post.
		$course_id = wp_insert_post( $course_data, true );

		if ( is_wp_error( $course_id ) ) {
			ma_log_webinarjam_debug( 'Failed to create course: ' . $course_id->get_error_message(), 'error' );
			return $course_id;
		}

		// Update custom fields.
		if ( ! empty( $course_data['meta_input'] ) ) {
			foreach ( $course_data['meta_input'] as $key => $value ) {
				update_field( $key, $value, $course_id );
			}
		}

		ma_log_webinarjam_debug( "Created course {$course_id}." );

		return $course_id;
	}

	/**
	 * Create Event Calendar event.
	 *
	 * @since 1.0.0
	 * @param int $course_id Course ID.
	 * @return int|WP_Error Event ID on success, WP_Error on failure.
	 */
	private function create_event( $course_id ) {
		// Get course title.
		$course = get_post( $course_id );

		if ( ! $course ) {
			return new \WP_Error( 'invalid_course', __( 'Invalid course ID.', 'ma-plugin' ) );
		}

		// Get next webinar date.
		$next_date = ma_get_next_webinar_date( $course_id );

		if ( ! $next_date ) {
			return new \WP_Error( 'no_schedule', __( 'No schedule found for webinar.', 'ma-plugin' ) );
		}

		// Get duration.
		$schedules = ma_get_webinar_schedule( $course_id );
		$duration  = 60; // Default 60 minutes.

		if ( ! empty( $schedules[0]['schedule_duration'] ) ) {
			$duration = absint( $schedules[0]['schedule_duration'] );
		}

		// Calculate timestamps.
		$start_timestamp = strtotime( $next_date );
		$end_timestamp   = $start_timestamp + ( $duration * 60 );

		// Create event post.
		$event_data = array(
			'post_title'   => $course->post_title,
			'post_content' => $course->post_content,
			'post_status'  => $course->post_status,
			'post_type'    => 'tribe_events',
		);

		$event_id = wp_insert_post( $event_data, true );

		if ( is_wp_error( $event_id ) ) {
			ma_log_webinarjam_debug( 'Failed to create event: ' . $event_id->get_error_message(), 'error' );
			return $event_id;
		}

		// Set event dates.
		update_post_meta( $event_id, '_EventStartDate', gmdate( 'Y-m-d H:i:s', $start_timestamp ) );
		update_post_meta( $event_id, '_EventEndDate', gmdate( 'Y-m-d H:i:s', $end_timestamp ) );
		update_post_meta( $event_id, '_EventTimezone', wp_timezone_string() );

		// Set as virtual event.
		update_post_meta( $event_id, '_tribe_events_is_virtual', 'yes' );
		update_post_meta( $event_id, '_tribe_events_virtual_url', get_field( 'webinarjam_registration_url', $course_id ) );

		// Link to course.
		update_field( 'course', $course_id, $event_id );

		// Sync event data from course.
		ma_sync_event_from_course( $event_id, $course_id );

		ma_log_webinarjam_debug( "Created event {$event_id} for course {$course_id}." );

		return $event_id;
	}
}
