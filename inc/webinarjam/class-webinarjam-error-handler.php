<?php
/**
 * WebinarJam Error Handler
 *
 * Centralized error handling and recovery mechanisms.
 *
 * @package ma_plugin
 * @since 1.0.0
 */

namespace ma_plugin\classes\WebinarJam;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WebinarJam Error Handler class.
 *
 * Provides centralized error handling, logging, notifications,
 * and recovery mechanisms for the WebinarJam integration.
 *
 * @since 1.0.0
 */
class WebinarJam_Error_Handler {

	/**
	 * Logger instance.
	 *
	 * @since 1.0.0
	 * @var WebinarJam_Logger
	 */
	private $logger;

	/**
	 * Maximum retry attempts.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	private $max_retries = 3;

	/**
	 * Retry delay in seconds.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	private $retry_delay = 5;

	/**
	 * Critical error threshold.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	private $critical_threshold = 5;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param WebinarJam_Logger $logger Logger instance.
	 */
	public function __construct( $logger ) {
		$this->logger = $logger;
	}

	/**
	 * Handle an error.
	 *
	 * @since 1.0.0
	 * @param string $type Error type (api, sync, import, validation, etc).
	 * @param string $message Error message.
	 * @param array  $context Additional context.
	 * @param bool   $is_critical Whether this is a critical error.
	 * @return void
	 */
	public function handle_error( $type, $message, $context = array(), $is_critical = false ) {
		// Log the error.
		$this->logger->log( 'error', $message, array_merge( $context, array( 'error_type' => $type ) ) );

		// Check if we should send notification.
		if ( $is_critical || $this->should_notify( $type ) ) {
			$this->send_error_notification( $type, $message, $context );
		}

		// Store error for tracking.
		$this->track_error( $type );

		// Log to WordPress debug if enabled.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( sprintf( '[WebinarJam Error][%s] %s', strtoupper( $type ), $message ) );
		}
	}

	/**
	 * Execute with retry logic.
	 *
	 * @since 1.0.0
	 * @param callable $callback Function to execute.
	 * @param array    $args Arguments for callback.
	 * @param int      $max_retries Maximum retry attempts.
	 * @return mixed Callback result or WP_Error on failure.
	 */
	public function execute_with_retry( $callback, $args = array(), $max_retries = null ) {
		if ( null === $max_retries ) {
			$max_retries = $this->max_retries;
		}

		$attempt = 0;

		while ( $attempt < $max_retries ) {
			try {
				$result = call_user_func_array( $callback, $args );

				// If result is WP_Error, retry.
				if ( is_wp_error( $result ) ) {
					$attempt++;

					if ( $attempt >= $max_retries ) {
						$this->handle_error(
							'retry',
							sprintf( 'Max retries (%d) reached: %s', $max_retries, $result->get_error_message() ),
							array( 'attempts' => $attempt ),
							true
						);
						return $result;
					}

					// Exponential backoff.
					$delay = $this->retry_delay * pow( 2, $attempt - 1 );
					$this->logger->log(
						'retry',
						sprintf( 'Retrying after %d seconds (attempt %d/%d)', $delay, $attempt, $max_retries ),
						array( 'delay' => $delay )
					);

					sleep( $delay );
					continue;
				}

				// Success.
				return $result;

			} catch ( \Exception $e ) {
				$attempt++;

				if ( $attempt >= $max_retries ) {
					$this->handle_error(
						'exception',
						$e->getMessage(),
						array(
							'file'     => $e->getFile(),
							'line'     => $e->getLine(),
							'trace'    => $e->getTraceAsString(),
							'attempts' => $attempt,
						),
						true
					);

					return new \WP_Error(
						'webinarjam_exception',
						$e->getMessage(),
						array( 'exception' => $e )
					);
				}

				// Exponential backoff.
				$delay = $this->retry_delay * pow( 2, $attempt - 1 );
				sleep( $delay );
			}
		}

		return new \WP_Error( 'webinarjam_max_retries', 'Maximum retry attempts reached' );
	}

	/**
	 * Validate API response.
	 *
	 * @since 1.0.0
	 * @param mixed  $response API response.
	 * @param string $endpoint Endpoint name for context.
	 * @return true|WP_Error True if valid, WP_Error otherwise.
	 */
	public function validate_api_response( $response, $endpoint = '' ) {
		// Check if response is valid.
		if ( empty( $response ) ) {
			return new \WP_Error(
				'webinarjam_empty_response',
				sprintf( 'Empty response from API endpoint: %s', $endpoint )
			);
		}

		// Check for API error message.
		if ( is_array( $response ) && isset( $response['error'] ) ) {
			return new \WP_Error(
				'webinarjam_api_error',
				$response['error'],
				array( 'endpoint' => $endpoint )
			);
		}

		// Check for HTTP error.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;
	}

	/**
	 * Validate webinar data.
	 *
	 * @since 1.0.0
	 * @param array $webinar Webinar data.
	 * @return true|WP_Error True if valid, WP_Error otherwise.
	 */
	public function validate_webinar_data( $webinar ) {
		$errors = new \WP_Error();

		// Required fields.
		$required = array( 'webinar_id', 'title' );

		foreach ( $required as $field ) {
			if ( empty( $webinar[ $field ] ) ) {
				$errors->add(
					'missing_field',
					sprintf( 'Required field missing: %s', $field ),
					array( 'field' => $field )
				);
			}
		}

		// Validate webinar_id format.
		if ( ! empty( $webinar['webinar_id'] ) && ! preg_match( '/^[a-zA-Z0-9_-]+$/', $webinar['webinar_id'] ) ) {
			$errors->add(
				'invalid_webinar_id',
				'Invalid webinar ID format',
				array( 'webinar_id' => $webinar['webinar_id'] )
			);
		}

		// Validate schedules if present.
		if ( ! empty( $webinar['schedules'] ) && is_array( $webinar['schedules'] ) ) {
			foreach ( $webinar['schedules'] as $index => $schedule ) {
				if ( empty( $schedule['date'] ) ) {
					$errors->add(
						'missing_schedule_date',
						sprintf( 'Schedule %d missing date', $index ),
						array( 'schedule_index' => $index )
					);
				} elseif ( strtotime( $schedule['date'] ) === false ) {
					$errors->add(
						'invalid_schedule_date',
						sprintf( 'Schedule %d has invalid date: %s', $index, $schedule['date'] ),
						array( 'schedule_index' => $index, 'date' => $schedule['date'] )
					);
				}
			}
		}

		// Validate emails if presenters exist.
		if ( ! empty( $webinar['presenters'] ) && is_array( $webinar['presenters'] ) ) {
			foreach ( $webinar['presenters'] as $index => $presenter ) {
				if ( ! empty( $presenter['email'] ) && ! is_email( $presenter['email'] ) ) {
					$errors->add(
						'invalid_presenter_email',
						sprintf( 'Presenter %d has invalid email: %s', $index, $presenter['email'] ),
						array( 'presenter_index' => $index, 'email' => $presenter['email'] )
					);
				}
			}
		}

		// Validate URLs.
		$url_fields = array( 'registration_url', 'replay_url', 'thank_you_url' );
		foreach ( $url_fields as $field ) {
			if ( ! empty( $webinar[ $field ] ) && ! filter_var( $webinar[ $field ], FILTER_VALIDATE_URL ) ) {
				$errors->add(
					'invalid_url',
					sprintf( 'Invalid URL in field: %s', $field ),
					array( 'field' => $field, 'value' => $webinar[ $field ] )
				);
			}
		}

		if ( $errors->has_errors() ) {
			return $errors;
		}

		return true;
	}

	/**
	 * Handle duplicate webinar ID.
	 *
	 * @since 1.0.0
	 * @param string $webinar_id Webinar ID.
	 * @param array  $webinar_data New webinar data.
	 * @return int|WP_Error Existing course ID or WP_Error.
	 */
	public function handle_duplicate_webinar( $webinar_id, $webinar_data ) {
		$existing_course_id = ma_get_course_by_webinarjam_id( $webinar_id );

		if ( ! $existing_course_id ) {
			return new \WP_Error(
				'webinarjam_duplicate_check_failed',
				'Duplicate check failed but no existing course found'
			);
		}

		// Log the duplicate.
		$this->logger->log(
			'sync',
			sprintf( 'Duplicate webinar ID found: %s (Course ID: %d)', $webinar_id, $existing_course_id ),
			array(
				'webinar_id' => $webinar_id,
				'course_id'  => $existing_course_id,
			)
		);

		// Check if manual edits were made.
		if ( $this->has_manual_edits( $existing_course_id ) ) {
			$this->logger->log(
				'sync',
				sprintf( 'Skipping update for course %d due to manual edits', $existing_course_id ),
				array( 'course_id' => $existing_course_id )
			);

			return new \WP_Error(
				'webinarjam_manual_edits',
				'Course has manual edits and will not be overwritten',
				array( 'course_id' => $existing_course_id )
			);
		}

		return $existing_course_id;
	}

	/**
	 * Handle archived/deleted webinar.
	 *
	 * @since 1.0.0
	 * @param int $course_id Course ID.
	 * @return void
	 */
	public function handle_archived_webinar( $course_id ) {
		// Mark as archived instead of deleting.
		update_field( 'webinarjam_status', 'archived', $course_id );
		update_field( 'webinarjam_import_status', 'archived', $course_id );

		// Add to sync log.
		ma_add_webinar_sync_log( $course_id, 'Webinar no longer available in API (archived)' );

		// Log.
		$this->logger->log(
			'sync',
			sprintf( 'Webinar archived (no longer in API): Course ID %d', $course_id ),
			array( 'course_id' => $course_id )
		);

		// Set course to draft status.
		wp_update_post( array(
			'ID'          => $course_id,
			'post_status' => 'draft',
		) );
	}

	/**
	 * Handle API rate limit.
	 *
	 * @since 1.0.0
	 * @param int $retry_after Seconds to wait before retry.
	 * @return void
	 */
	public function handle_rate_limit( $retry_after = null ) {
		if ( null === $retry_after ) {
			$retry_after = 60; // Default 1 minute.
		}

		$this->logger->log(
			'api',
			sprintf( 'API rate limit hit. Waiting %d seconds.', $retry_after ),
			array( 'retry_after' => $retry_after )
		);

		// Store rate limit transient.
		set_transient( 'webinarjam_rate_limited', time() + $retry_after, $retry_after );

		// Send notification if severe.
		if ( $retry_after > 300 ) { // More than 5 minutes.
			$this->send_error_notification(
				'rate_limit',
				sprintf( 'WebinarJam API rate limit exceeded. Waiting %d seconds.', $retry_after ),
				array( 'retry_after' => $retry_after )
			);
		}
	}

	/**
	 * Check if API is rate limited.
	 *
	 * @since 1.0.0
	 * @return bool True if rate limited.
	 */
	public function is_rate_limited() {
		return (bool) get_transient( 'webinarjam_rate_limited' );
	}

	/**
	 * Handle timezone mismatch.
	 *
	 * @since 1.0.0
	 * @param string $date Date string.
	 * @param string $timezone Timezone string.
	 * @return string|WP_Error Converted date or WP_Error.
	 */
	public function handle_timezone_conversion( $date, $timezone ) {
		try {
			$wp_timezone = wp_timezone_string();
			$dt          = new \DateTime( $date, new \DateTimeZone( $timezone ) );
			$dt->setTimezone( new \DateTimeZone( $wp_timezone ) );

			return $dt->format( 'Y-m-d H:i:s' );

		} catch ( \Exception $e ) {
			$this->handle_error(
				'timezone',
				sprintf( 'Timezone conversion failed: %s', $e->getMessage() ),
				array(
					'date'     => $date,
					'timezone' => $timezone,
				)
			);

			return new \WP_Error( 'timezone_conversion_failed', $e->getMessage() );
		}
	}

	/**
	 * Check if course has manual edits.
	 *
	 * @since 1.0.0
	 * @param int $course_id Course ID.
	 * @return bool True if manual edits detected.
	 */
	private function has_manual_edits( $course_id ) {
		$sync_enabled = get_field( 'webinarjam_sync_enabled', $course_id );

		// If sync is disabled, consider it manually edited.
		if ( false === $sync_enabled || '0' === $sync_enabled ) {
			return true;
		}

		// Check if post was modified after last sync.
		$last_sync     = get_post_meta( $course_id, '_webinarjam_last_sync', true );
		$last_modified = get_post_modified_time( 'U', false, $course_id );

		if ( $last_sync && $last_modified > strtotime( $last_sync ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Track error occurrences.
	 *
	 * @since 1.0.0
	 * @param string $type Error type.
	 * @return void
	 */
	private function track_error( $type ) {
		$error_count = get_transient( 'webinarjam_error_count_' . $type ) ?: 0;
		$error_count++;

		set_transient( 'webinarjam_error_count_' . $type, $error_count, HOUR_IN_SECONDS );
	}

	/**
	 * Check if we should notify admin.
	 *
	 * @since 1.0.0
	 * @param string $type Error type.
	 * @return bool True if should notify.
	 */
	private function should_notify( $type ) {
		$error_count = get_transient( 'webinarjam_error_count_' . $type ) ?: 0;

		// Notify if errors exceed threshold.
		if ( $error_count >= $this->critical_threshold ) {
			return true;
		}

		// Check notification cooldown.
		$last_notification = get_transient( 'webinarjam_last_notification_' . $type );
		if ( $last_notification ) {
			return false; // Don't spam notifications.
		}

		return false;
	}

	/**
	 * Send error notification email.
	 *
	 * @since 1.0.0
	 * @param string $type Error type.
	 * @param string $message Error message.
	 * @param array  $context Error context.
	 * @return void
	 */
	private function send_error_notification( $type, $message, $context = array() ) {
		// Check if notifications are enabled.
		$notifications_enabled = ma_get_webinarjam_setting( 'error_notifications', true );

		if ( ! $notifications_enabled ) {
			return;
		}

		// Set cooldown to prevent spam.
		set_transient( 'webinarjam_last_notification_' . $type, time(), HOUR_IN_SECONDS );

		// Get admin email.
		$admin_email = get_option( 'admin_email' );

		// Prepare email.
		$subject = sprintf( '[%s] WebinarJam Integration Error: %s', get_bloginfo( 'name' ), ucfirst( $type ) );

		$body = sprintf(
			"A critical error occurred in the WebinarJam integration:\n\n" .
			"Error Type: %s\n" .
			"Message: %s\n" .
			"Time: %s\n\n",
			strtoupper( $type ),
			$message,
			current_time( 'mysql' )
		);

		if ( ! empty( $context ) ) {
			$body .= "Context:\n";
			foreach ( $context as $key => $value ) {
				$body .= sprintf( "  %s: %s\n", $key, is_scalar( $value ) ? $value : wp_json_encode( $value ) );
			}
			$body .= "\n";
		}

		$body .= sprintf(
			"Please review the WebinarJam logs in the WordPress admin:\n%s\n",
			admin_url( 'admin.php?page=ma-webinarjam-logs' )
		);

		// Send email.
		wp_mail( $admin_email, $subject, $body );
	}
}
