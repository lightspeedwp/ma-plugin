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
