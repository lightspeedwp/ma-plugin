<?php
/**
 * WebinarJam Helper Functions
 *
 * Utility functions for WebinarJam integration.
 *
 * @package ma_plugin
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get WebinarJam API key.
 *
 * @since 1.0.0
 * @return string API key or empty string if not set.
 */
function ma_get_webinarjam_api_key() {
	if ( ! function_exists( 'get_field' ) ) {
		return '';
	}

	return get_field( 'webinarjam_api_key', 'option' ) ?: '';
}

/**
 * Get a WebinarJam setting.
 *
 * @since 1.0.0
 * @param string $key Setting key.
 * @param mixed  $default Default value if setting not found.
 * @return mixed Setting value or default.
 */
function ma_get_webinarjam_setting( $key, $default = '' ) {
	if ( ! function_exists( 'get_field' ) ) {
		return $default;
	}

	$value = get_field( "webinarjam_{$key}", 'option' );

	return $value !== false && $value !== null ? $value : $default;
}

/**
 * Check if WebinarJam is configured.
 *
 * @since 1.0.0
 * @return bool True if API key is set.
 */
function ma_is_webinarjam_configured() {
	$api_key = ma_get_webinarjam_api_key();
	return ! empty( $api_key );
}

/**
 * Log WebinarJam debug message.
 *
 * @since 1.0.0
 * @param string $message Log message.
 * @param string $level Log level (info, warning, error).
 * @return void
 */
function ma_log_webinarjam_debug( $message, $level = 'info' ) {
	// Check if debug mode is enabled.
	$debug_enabled = ma_get_webinarjam_setting( 'debug_mode', false );

	if ( ! $debug_enabled ) {
		return;
	}

	// Prepare log entry.
	$timestamp = gmdate( 'Y-m-d H:i:s' );
	$log_entry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;

	// Get log file path.
	$upload_dir = wp_upload_dir();
	$log_file   = trailingslashit( $upload_dir['basedir'] ) . 'webinarjam-debug.log';

	// Write to log file.
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	file_put_contents( $log_file, $log_entry, FILE_APPEND );

	// Also use WordPress debug log if WP_DEBUG_LOG is enabled.
	if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( "WebinarJam: {$message}" );
	}
}

/**
 * Get webinar course by WebinarJam ID.
 *
 * @since 1.0.0
 * @param string $webinar_id WebinarJam webinar ID.
 * @return int|false Course ID or false if not found.
 */
function ma_get_course_by_webinarjam_id( $webinar_id ) {
	if ( empty( $webinar_id ) ) {
		return false;
	}

	$args = array(
		'post_type'      => 'sfwd-courses',
		'posts_per_page' => 1,
		'meta_query'     => array(
			array(
				'key'   => 'webinarjam_webinar_id',
				'value' => $webinar_id,
			),
		),
		'fields'         => 'ids',
	);

	$query = new WP_Query( $args );

	if ( $query->have_posts() ) {
		return $query->posts[0];
	}

	return false;
}

/**
 * Get event by course ID.
 *
 * @since 1.0.0
 * @param int $course_id LearnDash course ID.
 * @return int|false Event ID or false if not found.
 */
function ma_get_event_by_course_id( $course_id ) {
	if ( empty( $course_id ) ) {
		return false;
	}

	$args = array(
		'post_type'      => 'tribe_events',
		'posts_per_page' => 1,
		'meta_query'     => array(
			array(
				'key'   => 'course',
				'value' => $course_id,
			),
		),
		'fields'         => 'ids',
	);

	$query = new WP_Query( $args );

	if ( $query->have_posts() ) {
		return $query->posts[0];
	}

	return false;
}

/**
 * Get webinar status.
 *
 * @since 1.0.0
 * @param int $course_id LearnDash course ID.
 * @return string Webinar status (upcoming, live, replay).
 */
function ma_get_webinar_status( $course_id ) {
	if ( empty( $course_id ) || ! function_exists( 'get_field' ) ) {
		return 'upcoming';
	}

	return get_field( 'webinarjam_status', $course_id ) ?: 'upcoming';
}

/**
 * Update webinar status.
 *
 * @since 1.0.0
 * @param int    $course_id LearnDash course ID.
 * @param string $status New status (upcoming, live, replay).
 * @return bool True on success, false on failure.
 */
function ma_update_webinar_status( $course_id, $status ) {
	if ( empty( $course_id ) || ! in_array( $status, array( 'upcoming', 'live', 'replay' ), true ) ) {
		return false;
	}

	if ( ! function_exists( 'update_field' ) ) {
		return false;
	}

	return update_field( 'webinarjam_status', $status, $course_id );
}

/**
 * Check if course is a webinar.
 *
 * @since 1.0.0
 * @param int $course_id LearnDash course ID.
 * @return bool True if course is a webinar.
 */
function ma_is_webinar_course( $course_id ) {
	if ( empty( $course_id ) ) {
		return false;
	}

	// Check if course has webinar course type.
	$course_types = wp_get_post_terms( $course_id, 'course_type', array( 'fields' => 'slugs' ) );

	return in_array( 'webinar', $course_types, true );
}

/**
 * Check if user has webinar access.
 *
 * @since 1.0.0
 * @param int $user_id WordPress user ID.
 * @return bool True if user has active subscription/access.
 */
function ma_user_has_webinar_access( $user_id = 0 ) {
	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	if ( empty( $user_id ) ) {
		return false;
	}

	// Check if user has active subscription (placeholder - implement based on subscription system).
	// This should check WooCommerce Subscriptions or other membership system.
	return apply_filters( 'ma_user_has_webinar_access', false, $user_id );
}

/**
 * Get webinar button text and URL based on status.
 *
 * @since 1.0.0
 * @param int $course_id LearnDash course ID.
 * @return array Array with 'text' and 'url' keys.
 */
function ma_get_webinar_button_data( $course_id ) {
	$status     = ma_get_webinar_status( $course_id );
	$user_id    = get_current_user_id();
	$has_access = ma_user_has_webinar_access( $user_id );

	$button_data = array(
		'text' => __( 'Register', 'ma-plugin' ),
		'url'  => '',
	);

	if ( ! function_exists( 'get_field' ) ) {
		return $button_data;
	}

	switch ( $status ) {
		case 'live':
			$button_data['text'] = __( 'Watch', 'ma-plugin' );
			$button_data['url']  = get_field( 'webinarjam_registration_url', $course_id ) ?: '';
			break;

		case 'replay':
			$button_data['text'] = __( 'Watch Replay', 'ma-plugin' );
			$button_data['url']  = get_field( 'webinarjam_replay_url', $course_id ) ?: '';
			break;

		case 'upcoming':
		default:
			if ( $has_access ) {
				$button_data['text'] = __( 'Register', 'ma-plugin' );
				$button_data['url']  = get_permalink( $course_id );
			} else {
				$button_data['text'] = __( 'Get Access', 'ma-plugin' );
				$button_data['url']  = apply_filters( 'ma_webinar_purchase_url', home_url( '/subscribe/' ) );
			}
			break;
	}

	return apply_filters( 'ma_webinar_button_data', $button_data, $course_id, $status );
}

/**
 * Get all webinar courses.
 *
 * @since 1.0.0
 * @param array $args Additional query arguments.
 * @return array Array of course IDs.
 */
function ma_get_webinar_courses( $args = array() ) {
	$default_args = array(
		'post_type'      => 'sfwd-courses',
		'posts_per_page' => -1,
		'tax_query'      => array(
			array(
				'taxonomy' => 'course_type',
				'field'    => 'slug',
				'terms'    => 'webinar',
			),
		),
		'fields'         => 'ids',
	);

	$args = wp_parse_args( $args, $default_args );

	return get_posts( $args );
}

/**
 * Get webinar courses by status.
 *
 * @since 1.0.0
 * @param string $status Webinar status (upcoming, live, replay).
 * @return array Array of course IDs.
 */
function ma_get_webinar_courses_by_status( $status ) {
	if ( ! in_array( $status, array( 'upcoming', 'live', 'replay' ), true ) ) {
		return array();
	}

	$args = array(
		'meta_query' => array(
			array(
				'key'   => 'webinarjam_status',
				'value' => $status,
			),
		),
	);

	return ma_get_webinar_courses( $args );
}

/**
 * Sync event data from course.
 *
 * @since 1.0.0
 * @param int $event_id Event ID.
 * @param int $course_id Course ID.
 * @return bool True on success, false on failure.
 */
function ma_sync_event_from_course( $event_id, $course_id ) {
	if ( empty( $event_id ) || empty( $course_id ) ) {
		return false;
	}

	if ( ! function_exists( 'update_field' ) ) {
		return false;
	}

	// Get webinar status.
	$status = ma_get_webinar_status( $course_id );

	// Get button data.
	$button_data = ma_get_webinar_button_data( $course_id );

	// Update event fields.
	update_field( 'event_webinar_status', $status, $event_id );
	update_field( 'event_button_text', $button_data['text'], $event_id );
	update_field( 'event_button_url', $button_data['url'], $event_id );
	update_field( 'event_last_sync', current_time( 'mysql' ), $event_id );

	ma_log_webinarjam_debug( "Synced event {$event_id} from course {$course_id}." );

	return true;
}

/**
 * Get webinar presenters.
 *
 * @since 1.0.0
 * @param int $course_id Course ID.
 * @return array Array of presenter data.
 */
function ma_get_webinar_presenters( $course_id ) {
	if ( empty( $course_id ) || ! function_exists( 'get_field' ) ) {
		return array();
	}

	$presenters = get_field( 'webinarjam_presenters', $course_id );

	return is_array( $presenters ) ? $presenters : array();
}

/**
 * Get webinar schedule.
 *
 * @since 1.0.0
 * @param int $course_id Course ID.
 * @return array Array of schedule data.
 */
function ma_get_webinar_schedule( $course_id ) {
	if ( empty( $course_id ) || ! function_exists( 'get_field' ) ) {
		return array();
	}

	$schedule = get_field( 'webinarjam_schedule', $course_id );

	return is_array( $schedule ) ? $schedule : array();
}

/**
 * Get next webinar schedule date.
 *
 * @since 1.0.0
 * @param int $course_id Course ID.
 * @return string|false Next schedule date or false if none.
 */
function ma_get_next_webinar_date( $course_id ) {
	$schedules = ma_get_webinar_schedule( $course_id );

	if ( empty( $schedules ) ) {
		return false;
	}

	$now = current_time( 'timestamp' );
	$next_date = false;

	foreach ( $schedules as $schedule ) {
		if ( empty( $schedule['schedule_date'] ) ) {
			continue;
		}

		$schedule_timestamp = strtotime( $schedule['schedule_date'] );

		if ( $schedule_timestamp > $now ) {
			if ( false === $next_date || $schedule_timestamp < strtotime( $next_date ) ) {
				$next_date = $schedule['schedule_date'];
			}
		}
	}

	return $next_date;
}

/**
 * Check if user is registered for webinar.
 *
 * @since 1.0.0
 * @param int $user_id User ID.
 * @param int $course_id Course ID.
 * @return bool True if registered.
 */
function ma_is_user_registered_for_webinar( $user_id, $course_id ) {
	if ( empty( $user_id ) || empty( $course_id ) ) {
		return false;
	}

	// Check if user is enrolled in the course (LearnDash check).
	if ( function_exists( 'sfwd_lms_has_access' ) ) {
		return sfwd_lms_has_access( $course_id, $user_id );
	}

	return false;
}

/**
 * Add sync log entry to course.
 *
 * @since 1.0.0
 * @param int    $course_id Course ID.
 * @param string $message Log message.
 * @return bool True on success, false on failure.
 */
function ma_add_webinar_sync_log( $course_id, $message ) {
	if ( empty( $course_id ) || empty( $message ) || ! function_exists( 'get_field' ) ) {
		return false;
	}

	$current_log = get_field( 'webinarjam_sync_log', $course_id ) ?: '';
	$timestamp   = current_time( 'mysql' );
	$new_entry   = "[{$timestamp}] {$message}\n";

	// Limit log to last 50 entries.
	$log_lines = explode( "\n", $current_log );
	$log_lines = array_filter( $log_lines );

	if ( count( $log_lines ) >= 50 ) {
		$log_lines = array_slice( $log_lines, -49 );
	}

	$log_lines[] = rtrim( $new_entry );
	$new_log     = implode( "\n", $log_lines );

	return update_field( 'webinarjam_sync_log', $new_log, $course_id );
}

/**
 * Update webinar course from transformed data.
 *
 * Updates a course post with transformed webinar data including
 * all meta fields and custom fields.
 *
 * @since 1.0.0
 * @param int   $course_id Course post ID.
 * @param array $course_data Transformed course data from transformer.
 * @return bool True on success, false on failure.
 */
function ma_update_webinar_course_from_data( $course_id, $course_data ) {
	if ( empty( $course_id ) || empty( $course_data ) || ! function_exists( 'update_field' ) ) {
		return false;
	}

	// Update post fields if present.
	$post_updates = array();
	$post_fields  = array( 'post_title', 'post_content', 'post_excerpt', 'post_status' );

	foreach ( $post_fields as $field ) {
		if ( isset( $course_data[ $field ] ) ) {
			$post_updates[ $field ] = $course_data[ $field ];
		}
	}

	if ( ! empty( $post_updates ) ) {
		$post_updates['ID'] = $course_id;
		wp_update_post( $post_updates );
	}

	// Update meta fields.
	if ( ! empty( $course_data['meta_input'] ) && is_array( $course_data['meta_input'] ) ) {
		foreach ( $course_data['meta_input'] as $meta_key => $meta_value ) {
			// Use update_field for ACF fields (those starting with webinarjam_).
			if ( strpos( $meta_key, 'webinarjam_' ) === 0 ) {
				update_field( $meta_key, $meta_value, $course_id );
			} else {
				update_post_meta( $course_id, $meta_key, $meta_value );
			}
		}
	}

	// Update last sync timestamp.
	update_post_meta( $course_id, '_webinarjam_last_sync', current_time( 'mysql' ) );

	// Log the update.
	ma_add_webinar_sync_log( $course_id, 'Course updated from WebinarJam API' );

	return true;
}
