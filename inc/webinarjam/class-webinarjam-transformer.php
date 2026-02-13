<?php
/**
 * WebinarJam Data Transformer
 *
 * Transforms WebinarJam API data into WordPress/LearnDash format.
 *
 * @package ma_plugin
 * @since 1.0.0
 */

namespace ma_plugin\classes\WebinarJam;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WebinarJam Data Transformer class.
 *
 * Handles transformation of WebinarJam API responses into formats
 * suitable for WordPress posts, custom fields, and taxonomies.
 *
 * @since 1.0.0
 */
class WebinarJam_Transformer {

	/**
	 * Transform webinar API data to course data structure.
	 *
	 * Converts raw WebinarJam API response into format suitable
	 * for creating/updating a LearnDash course.
	 *
	 * @since 1.0.0
	 * @param array $webinar Raw webinar data from API.
	 * @return array|WP_Error Transformed course data or WP_Error on validation failure.
	 */
	public static function transform_webinar_to_course_data( $webinar ) {
		// Validate input.
		$validation = self::validate_webinar_data( $webinar );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Extract basic course information.
		$course_data = array(
			'post_title'   => self::sanitize_text( $webinar['title'] ?? __( 'Untitled Webinar', 'ma-plugin' ) ),
			'post_content' => self::sanitize_html( $webinar['description'] ?? '' ),
			'post_status'  => self::determine_post_status( $webinar ),
			'post_type'    => 'sfwd-courses',
			'meta_input'   => array(),
		);

		// Add WebinarJam-specific metadata.
		$course_data['meta_input']['webinarjam_webinar_id'] = self::sanitize_text( $webinar['webinar_id'] ?? '' );
		$course_data['meta_input']['webinarjam_status']      = self::determine_webinar_status( $webinar );
		$course_data['meta_input']['webinarjam_import_status'] = 'imported';
		$course_data['meta_input']['webinarjam_last_sync']   = current_time( 'mysql' );

		// Add URLs.
		if ( ! empty( $webinar['registration_url'] ) ) {
			$course_data['meta_input']['webinarjam_registration_url'] = esc_url_raw( $webinar['registration_url'] );
		}

		if ( ! empty( $webinar['replay_url'] ) ) {
			$course_data['meta_input']['webinarjam_replay_url'] = esc_url_raw( $webinar['replay_url'] );
		}

		// Transform and add presenters.
		if ( ! empty( $webinar['presenters'] ) && is_array( $webinar['presenters'] ) ) {
			$course_data['meta_input']['webinarjam_presenters'] = self::transform_presenter_data( $webinar['presenters'] );
		}

		// Transform and add schedule.
		if ( ! empty( $webinar['schedules'] ) && is_array( $webinar['schedules'] ) ) {
			$course_data['meta_input']['webinarjam_schedule'] = self::transform_schedule_data( $webinar['schedules'] );
		}

		// Add default sync settings.
		$course_data['meta_input']['webinarjam_sync_enabled']   = true;
		$course_data['meta_input']['webinarjam_auto_complete']  = true;
		$course_data['meta_input']['webinarjam_notify_users']   = false;

		// Add initial sync log entry.
		$course_data['meta_input']['webinarjam_sync_log'] = sprintf(
			'[%s] Imported from WebinarJam',
			current_time( 'mysql' )
		);

		return apply_filters( 'ma_webinarjam_transformed_course_data', $course_data, $webinar );
	}

	/**
	 * Transform presenter data from API format.
	 *
	 * @since 1.0.0
	 * @param array $presenters Raw presenter data from API.
	 * @return array Transformed presenter data for ACF repeater.
	 */
	public static function transform_presenter_data( $presenters ) {
		if ( empty( $presenters ) || ! is_array( $presenters ) ) {
			return array();
		}

		$transformed = array();

		foreach ( $presenters as $presenter ) {
			if ( empty( $presenter['name'] ) ) {
				continue;
			}

			$presenter_data = array(
				'presenter_name'  => self::sanitize_text( $presenter['name'] ),
				'presenter_email' => '',
				'presenter_bio'   => '',
				'presenter_photo' => '',
			);

			// Add email if provided.
			if ( ! empty( $presenter['email'] ) && is_email( $presenter['email'] ) ) {
				$presenter_data['presenter_email'] = sanitize_email( $presenter['email'] );
			}

			// Add bio if provided.
			if ( ! empty( $presenter['bio'] ) ) {
				$presenter_data['presenter_bio'] = self::sanitize_text( $presenter['bio'] );
			}

			// Handle photo - could be URL or attachment ID.
			if ( ! empty( $presenter['photo_url'] ) ) {
				// Try to sideload the image.
				$attachment_id = self::sideload_image( $presenter['photo_url'], $presenter['name'] );
				if ( $attachment_id && ! is_wp_error( $attachment_id ) ) {
					$presenter_data['presenter_photo'] = $attachment_id;
				}
			}

			$transformed[] = $presenter_data;
		}

		return apply_filters( 'ma_webinarjam_transformed_presenters', $transformed, $presenters );
	}

	/**
	 * Transform schedule data from API format.
	 *
	 * @since 1.0.0
	 * @param array $schedules Raw schedule data from API.
	 * @return array Transformed schedule data for ACF repeater.
	 */
	public static function transform_schedule_data( $schedules ) {
		if ( empty( $schedules ) || ! is_array( $schedules ) ) {
			return array();
		}

		$transformed = array();

		foreach ( $schedules as $schedule ) {
			if ( empty( $schedule['date'] ) ) {
				continue;
			}

			// Parse and convert date.
			$date = self::parse_date( $schedule['date'], $schedule['timezone'] ?? 'UTC' );

			if ( ! $date ) {
				continue;
			}

			$schedule_data = array(
				'schedule_date'     => $date,
				'schedule_timezone' => self::sanitize_text( $schedule['timezone'] ?? 'UTC' ),
				'schedule_duration' => absint( $schedule['duration'] ?? 60 ),
			);

			$transformed[] = $schedule_data;
		}

		// Sort schedules by date.
		usort(
			$transformed,
			function ( $a, $b ) {
				return strtotime( $a['schedule_date'] ) - strtotime( $b['schedule_date'] );
			}
		);

		return apply_filters( 'ma_webinarjam_transformed_schedules', $transformed, $schedules );
	}

	/**
	 * Transform attendee data from API format.
	 *
	 * @since 1.0.0
	 * @param array $attendees Raw attendee data from API.
	 * @return array Transformed attendee data with WordPress user mapping.
	 */
	public static function transform_attendee_data( $attendees ) {
		if ( empty( $attendees ) || ! is_array( $attendees ) ) {
			return array();
		}

		$transformed = array();

		foreach ( $attendees as $attendee ) {
			if ( empty( $attendee['email'] ) ) {
				continue;
			}

			$email = sanitize_email( $attendee['email'] );

			// Try to find WordPress user by email.
			$user = get_user_by( 'email', $email );

			$attendee_data = array(
				'email'      => $email,
				'name'       => self::sanitize_text( $attendee['name'] ?? '' ),
				'attended'   => ! empty( $attendee['attended'] ),
				'join_time'  => ! empty( $attendee['join_time'] ) ? self::parse_date( $attendee['join_time'] ) : '',
				'leave_time' => ! empty( $attendee['leave_time'] ) ? self::parse_date( $attendee['leave_time'] ) : '',
				'duration'   => absint( $attendee['duration'] ?? 0 ),
				'user_id'    => $user ? $user->ID : 0,
			);

			$transformed[] = $attendee_data;
		}

		return apply_filters( 'ma_webinarjam_transformed_attendees', $transformed, $attendees );
	}

	/**
	 * Validate webinar data structure.
	 *
	 * @since 1.0.0
	 * @param array $webinar Webinar data to validate.
	 * @return bool|WP_Error True if valid, WP_Error if invalid.
	 */
	private static function validate_webinar_data( $webinar ) {
		if ( empty( $webinar ) || ! is_array( $webinar ) ) {
			return new \WP_Error( 'invalid_data', __( 'Invalid webinar data provided.', 'ma-plugin' ) );
		}

		// Check required fields.
		$required_fields = array( 'webinar_id', 'title' );

		foreach ( $required_fields as $field ) {
			if ( empty( $webinar[ $field ] ) ) {
				return new \WP_Error(
					'missing_required_field',
					sprintf(
						/* translators: %s: Field name */
						__( 'Required field missing: %s', 'ma-plugin' ),
						$field
					)
				);
			}
		}

		return true;
	}

	/**
	 * Determine post status based on webinar data.
	 *
	 * @since 1.0.0
	 * @param array $webinar Webinar data.
	 * @return string Post status (publish, draft, pending).
	 */
	private static function determine_post_status( $webinar ) {
		// Check settings for auto-publish.
		$auto_publish = ma_get_webinarjam_setting( 'auto_publish', false );

		if ( $auto_publish ) {
			return 'publish';
		}

		// Check if webinar is active/live.
		if ( ! empty( $webinar['status'] ) && 'active' === $webinar['status'] ) {
			return 'publish';
		}

		return apply_filters( 'ma_webinarjam_course_post_status', 'pending', $webinar );
	}

	/**
	 * Determine webinar status based on API data.
	 *
	 * @since 1.0.0
	 * @param array $webinar Webinar data.
	 * @return string Webinar status (upcoming, live, replay).
	 */
	private static function determine_webinar_status( $webinar ) {
		// Check if webinar has ended.
		if ( ! empty( $webinar['ended'] ) || ! empty( $webinar['status'] ) && 'ended' === $webinar['status'] ) {
			return 'replay';
		}

		// Check if webinar is currently live.
		if ( ! empty( $webinar['is_live'] ) || ! empty( $webinar['status'] ) && 'live' === $webinar['status'] ) {
			return 'live';
		}

		// Check schedules to determine if upcoming.
		if ( ! empty( $webinar['schedules'] ) && is_array( $webinar['schedules'] ) ) {
			$now = current_time( 'timestamp' );

			foreach ( $webinar['schedules'] as $schedule ) {
				if ( ! empty( $schedule['date'] ) ) {
					$schedule_time = strtotime( $schedule['date'] );
					if ( $schedule_time > $now ) {
						return 'upcoming';
					}
				}
			}
		}

		// Default to upcoming.
		return apply_filters( 'ma_webinarjam_default_status', 'upcoming', $webinar );
	}

	/**
	 * Parse date from various formats.
	 *
	 * @since 1.0.0
	 * @param string $date Date string.
	 * @param string $timezone Timezone string (optional).
	 * @return string|false Formatted date string (Y-m-d H:i:s) or false on failure.
	 */
	private static function parse_date( $date, $timezone = 'UTC' ) {
		if ( empty( $date ) ) {
			return false;
		}

		try {
			// Create DateTime object with provided timezone.
			$dt = new \DateTime( $date, new \DateTimeZone( $timezone ) );

			// Convert to site timezone.
			$site_timezone = wp_timezone_string();
			$dt->setTimezone( new \DateTimeZone( $site_timezone ) );

			return $dt->format( 'Y-m-d H:i:s' );
		} catch ( \Exception $e ) {
			ma_log_webinarjam_debug( "Failed to parse date: {$date} - " . $e->getMessage(), 'error' );
			return false;
		}
	}

	/**
	 * Sideload image from URL into WordPress media library.
	 *
	 * @since 1.0.0
	 * @param string $image_url Image URL.
	 * @param string $description Image description.
	 * @return int|WP_Error Attachment ID or WP_Error on failure.
	 */
	private static function sideload_image( $image_url, $description = '' ) {
		if ( ! function_exists( 'media_sideload_image' ) ) {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		// Download image.
		$temp_file = download_url( $image_url );

		if ( is_wp_error( $temp_file ) ) {
			return $temp_file;
		}

		// Get file info.
		$file_array = array(
			'name'     => basename( $image_url ),
			'tmp_name' => $temp_file,
		);

		// Upload to media library.
		$attachment_id = media_handle_sideload( $file_array, 0, $description );

		// Clean up temp file.
		if ( ! is_wp_error( $attachment_id ) ) {
			@unlink( $temp_file );
		}

		return $attachment_id;
	}

	/**
	 * Sanitize text field.
	 *
	 * @since 1.0.0
	 * @param string $text Text to sanitize.
	 * @return string Sanitized text.
	 */
	private static function sanitize_text( $text ) {
		return sanitize_text_field( wp_unslash( $text ) );
	}

	/**
	 * Sanitize HTML content.
	 *
	 * @since 1.0.0
	 * @param string $html HTML to sanitize.
	 * @return string Sanitized HTML.
	 */
	private static function sanitize_html( $html ) {
		return wp_kses_post( wp_unslash( $html ) );
	}

	/**
	 * Calculate duration in minutes from timestamps.
	 *
	 * @since 1.0.0
	 * @param string $start_time Start timestamp.
	 * @param string $end_time End timestamp.
	 * @return int Duration in minutes.
	 */
	public static function calculate_duration( $start_time, $end_time ) {
		if ( empty( $start_time ) || empty( $end_time ) ) {
			return 0;
		}

		$start = strtotime( $start_time );
		$end   = strtotime( $end_time );

		if ( ! $start || ! $end ) {
			return 0;
		}

		$duration_seconds = $end - $start;
		return max( 0, round( $duration_seconds / 60 ) );
	}

	/**
	 * Merge updated webinar data with existing course data.
	 *
	 * Preserves manually edited fields while updating sync-enabled fields.
	 *
	 * @since 1.0.0
	 * @param int   $course_id Existing course ID.
	 * @param array $new_data New webinar data from API.
	 * @return array Merged course data.
	 */
	public static function merge_with_existing_course( $course_id, $new_data ) {
		if ( empty( $course_id ) ) {
			return $new_data;
		}

		// Check if sync is enabled for this course.
		$sync_enabled = get_field( 'webinarjam_sync_enabled', $course_id );

		if ( ! $sync_enabled ) {
			ma_log_webinarjam_debug( "Sync disabled for course {$course_id}, skipping update." );
			return array();
		}

		// Get existing course data.
		$existing_post = get_post( $course_id );

		if ( ! $existing_post ) {
			return $new_data;
		}

		// Preserve manually edited content unless force sync.
		$force_sync = apply_filters( 'ma_webinarjam_force_sync', false, $course_id );

		if ( ! $force_sync ) {
			// Check if title was manually edited.
			$manual_title = get_post_meta( $course_id, '_manual_title_edit', true );
			if ( $manual_title ) {
				unset( $new_data['post_title'] );
			}

			// Check if content was manually edited.
			$manual_content = get_post_meta( $course_id, '_manual_content_edit', true );
			if ( $manual_content ) {
				unset( $new_data['post_content'] );
			}
		}

		// Update post ID for updating existing post.
		$new_data['ID'] = $course_id;

		// Preserve post status unless specified.
		if ( empty( $new_data['post_status'] ) ) {
			$new_data['post_status'] = $existing_post->post_status;
		}

		// Update sync timestamp.
		if ( ! empty( $new_data['meta_input'] ) ) {
			$new_data['meta_input']['webinarjam_last_sync'] = current_time( 'mysql' );
		}

		return apply_filters( 'ma_webinarjam_merged_course_data', $new_data, $course_id, $existing_post );
	}

	/**
	 * Extract webinar IDs from API response.
	 *
	 * @since 1.0.0
	 * @param array $webinars Array of webinar data from API.
	 * @return array Array of webinar IDs.
	 */
	public static function extract_webinar_ids( $webinars ) {
		if ( empty( $webinars ) || ! is_array( $webinars ) ) {
			return array();
		}

		$ids = array();

		foreach ( $webinars as $webinar ) {
			if ( ! empty( $webinar['webinar_id'] ) ) {
				$ids[] = self::sanitize_text( $webinar['webinar_id'] );
			}
		}

		return array_unique( array_filter( $ids ) );
	}
}
