<?php
/**
 * WebinarJam API Client
 *
 * Handles all communication with the WebinarJam API.
 *
 * @package ma_plugin
 * @since 1.0.0
 */

namespace ma_plugin\classes\WebinarJam;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WebinarJam API Client class.
 *
 * Provides methods to interact with the WebinarJam API including
 * authentication, fetching webinars, managing registrants, and tracking attendance.
 *
 * @since 1.0.0
 */
class WebinarJam_API_Client {

	/**
	 * API base URL.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $api_url;

	/**
	 * API key.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $api_key;

	/**
	 * Cache expiration time in seconds (12 hours).
	 *
	 * @since 1.0.0
	 * @var int
	 */
	private $cache_expiration = 43200;

	/**
	 * Error handler instance.
	 *
	 * @since 1.0.0
	 * @var WebinarJam_Error_Handler
	 */
	private $error_handler;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->api_url = ma_get_webinarjam_setting( 'api_url', 'https://api.webinarjam.com/webinarjam' );
		$this->api_key = ma_get_webinarjam_api_key();
	}

	/**
	 * Set error handler.
	 *
	 * @since 1.0.0
	 * @param WebinarJam_Error_Handler $error_handler Error handler instance.
	 * @return void
	 */
	public function set_error_handler( $error_handler ) {
		$this->error_handler = $error_handler;
	}

	/**
	 * Fetch all webinars from the API.
	 *
	 * @since 1.0.0
	 * @param bool $use_cache Whether to use cached data.
	 * @return array|WP_Error Array of webinars or WP_Error on failure.
	 */
	public function get_all_webinars( $use_cache = true ) {
		$cache_key = 'ma_webinarjam_all_webinars';

		// Check cache.
		if ( $use_cache ) {
			$cached = get_transient( $cache_key );
			if ( false !== $cached ) {
				ma_log_webinarjam_debug( 'Retrieved all webinars from cache.' );
				return $cached;
			}
		}

		// Make API request.
		$response = $this->make_request( 'webinars', array() );

		if ( is_wp_error( $response ) ) {
			ma_log_webinarjam_debug( 'Failed to fetch all webinars: ' . $response->get_error_message(), 'error' );
			return $response;
		}

		// Cache the response.
		set_transient( $cache_key, $response, $this->cache_expiration );

		ma_log_webinarjam_debug( 'Fetched ' . count( $response ) . ' webinars from API.' );

		return $response;
	}

	/**
	 * Fetch a single webinar by ID.
	 *
	 * @since 1.0.0
	 * @param string $webinar_id WebinarJam webinar ID.
	 * @param bool   $use_cache Whether to use cached data.
	 * @return array|WP_Error Webinar data or WP_Error on failure.
	 */
	public function get_webinar( $webinar_id, $use_cache = true ) {
		if ( empty( $webinar_id ) ) {
			return new \WP_Error( 'invalid_webinar_id', __( 'Invalid webinar ID provided.', 'ma-plugin' ) );
		}

		$cache_key = 'ma_webinarjam_webinar_' . $webinar_id;

		// Check cache.
		if ( $use_cache ) {
			$cached = get_transient( $cache_key );
			if ( false !== $cached ) {
				ma_log_webinarjam_debug( "Retrieved webinar {$webinar_id} from cache." );
				return $cached;
			}
		}

		// Make API request.
		$response = $this->make_request( 'webinar', array( 'webinar_id' => $webinar_id ) );

		if ( is_wp_error( $response ) ) {
			ma_log_webinarjam_debug( "Failed to fetch webinar {$webinar_id}: " . $response->get_error_message(), 'error' );
			return $response;
		}

		// Cache the response (6 hour expiry for individual webinars).
		set_transient( $cache_key, $response, $this->cache_expiration / 2 );

		ma_log_webinarjam_debug( "Fetched webinar {$webinar_id} from API." );

		return $response;
	}

	/**
	 * Get registrants for a specific webinar.
	 *
	 * @since 1.0.0
	 * @param string $webinar_id WebinarJam webinar ID.
	 * @return array|WP_Error Array of registrants or WP_Error on failure.
	 */
	public function get_webinar_registrants( $webinar_id ) {
		if ( empty( $webinar_id ) ) {
			return new \WP_Error( 'invalid_webinar_id', __( 'Invalid webinar ID provided.', 'ma-plugin' ) );
		}

		// Make API request.
		$response = $this->make_request( 'registrants', array( 'webinar_id' => $webinar_id ) );

		if ( is_wp_error( $response ) ) {
			ma_log_webinarjam_debug( "Failed to fetch registrants for webinar {$webinar_id}: " . $response->get_error_message(), 'error' );
			return $response;
		}

		ma_log_webinarjam_debug( "Fetched " . count( $response ) . " registrants for webinar {$webinar_id}." );

		return $response;
	}

	/**
	 * Register a user for a webinar.
	 *
	 * @since 1.0.0
	 * @param string $webinar_id WebinarJam webinar ID.
	 * @param string $email User email address.
	 * @param string $name User full name.
	 * @param array  $additional_fields Additional registration fields.
	 * @return array|WP_Error Registration response or WP_Error on failure.
	 */
	public function register_user( $webinar_id, $email, $name, $additional_fields = array() ) {
		if ( empty( $webinar_id ) || empty( $email ) || empty( $name ) ) {
			return new \WP_Error( 'missing_required_fields', __( 'Webinar ID, email, and name are required.', 'ma-plugin' ) );
		}

		// Validate email.
		if ( ! is_email( $email ) ) {
			return new \WP_Error( 'invalid_email', __( 'Invalid email address provided.', 'ma-plugin' ) );
		}

		// Prepare registration data.
		$data = array_merge(
			array(
				'webinar_id' => $webinar_id,
				'email'      => $email,
				'name'       => $name,
			),
			$additional_fields
		);

		// Make API request.
		$response = $this->make_request( 'register', $data, 'POST' );

		if ( is_wp_error( $response ) ) {
			ma_log_webinarjam_debug( "Failed to register user {$email} for webinar {$webinar_id}: " . $response->get_error_message(), 'error' );
			return $response;
		}

		ma_log_webinarjam_debug( "Successfully registered user {$email} for webinar {$webinar_id}." );

		return $response;
	}

	/**
	 * Get attendance data for a webinar.
	 *
	 * @since 1.0.0
	 * @param string $webinar_id WebinarJam webinar ID.
	 * @return array|WP_Error Array of attendees with attendance status or WP_Error on failure.
	 */
	public function get_attendees( $webinar_id ) {
		if ( empty( $webinar_id ) ) {
			return new \WP_Error( 'invalid_webinar_id', __( 'Invalid webinar ID provided.', 'ma-plugin' ) );
		}

		// Make API request.
		$response = $this->make_request( 'attendees', array( 'webinar_id' => $webinar_id ) );

		if ( is_wp_error( $response ) ) {
			ma_log_webinarjam_debug( "Failed to fetch attendees for webinar {$webinar_id}: " . $response->get_error_message(), 'error' );
			return $response;
		}

		ma_log_webinarjam_debug( "Fetched attendance data for webinar {$webinar_id}." );

		return $response;
	}

	/**
	 * Test API connection.
	 *
	 * @since 1.0.0
	 * @return bool|WP_Error True if connection successful, WP_Error on failure.
	 */
	public function test_connection() {
		$response = $this->make_request( 'test', array() );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;
	}

	/**
	 * Make an API request.
	 *
	 * @since 1.0.0
	 * @param string $endpoint API endpoint.
	 * @param array  $params Request parameters.
	 * @param string $method HTTP method (GET or POST).
	 * @return array|WP_Error Response data or WP_Error on failure.
	 */
	private function make_request( $endpoint, $params = array(), $method = 'GET' ) {
		// Check if API is configured.
		if ( empty( $this->api_key ) ) {
			return new \WP_Error( 'api_not_configured', __( 'WebinarJam API is not configured.', 'ma-plugin' ) );
		}

		// Check if rate limited.
		if ( $this->error_handler && $this->error_handler->is_rate_limited() ) {
			return new \WP_Error( 'api_rate_limited', __( 'API is currently rate limited. Please try again later.', 'ma-plugin' ) );
		}

		// Execute with retry logic if error handler is available.
		if ( $this->error_handler ) {
			$result = $this->error_handler->execute_with_retry(
				array( $this, 'execute_request' ),
				array( $endpoint, $params, $method )
			);
		} else {
			$result = $this->execute_request( $endpoint, $params, $method );
		}

		// Validate response if error handler is available.
		if ( $this->error_handler && ! is_wp_error( $result ) ) {
			$validation = $this->error_handler->validate_api_response( $result, $endpoint );
			if ( is_wp_error( $validation ) ) {
				return $validation;
			}
		}

		return $result;
	}

	/**
	 * Execute the actual API request.
	 *
	 * @since 1.0.0
	 * @param string $endpoint API endpoint.
	 * @param array  $params Request parameters.
	 * @param string $method HTTP method (GET or POST).
	 * @return array|WP_Error Response data or WP_Error on failure.
	 */
	public function execute_request( $endpoint, $params = array(), $method = 'GET' ) {

		// Build URL.
		$url = trailingslashit( $this->api_url ) . $endpoint;

		// Add API key to parameters.
		$params['api_key'] = $this->api_key;

		// Setup request arguments.
		$args = array(
			'timeout' => 30,
			'headers' => array(
				'Accept'       => 'application/json',
				'Content-Type' => 'application/json',
			),
		);

		// Make request based on method.
		if ( 'POST' === $method ) {
			$args['body'] = wp_json_encode( $params );
			$response     = wp_remote_post( $url, $args );
		} else {
			$url      = add_query_arg( $params, $url );
			$response = wp_remote_get( $url, $args );
		}

		// Check for errors.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// Get response code.
		$response_code = wp_remote_retrieve_response_code( $response );

		// Check response code.
		if ( 200 !== $response_code ) {
			$body = wp_remote_retrieve_body( $response );

			// Check for rate limit.
			if ( 429 === $response_code ) {
				$retry_after = wp_remote_retrieve_header( $response, 'retry-after' ) ?: 60;
				if ( $this->error_handler ) {
					$this->error_handler->handle_rate_limit( (int) $retry_after );
				}
				return new \WP_Error( 'api_rate_limit', __( 'API rate limit exceeded.', 'ma-plugin' ), array( 'retry_after' => $retry_after ) );
			}

			return new \WP_Error(
				'api_error',
				sprintf(
					/* translators: 1: Response code, 2: Response body */
					__( 'API request failed with code %1$s: %2$s', 'ma-plugin' ),
					$response_code,
					$body
				)
			);
		}

		// Parse response body.
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( null === $data ) {
			return new \WP_Error( 'json_decode_error', __( 'Failed to decode API response.', 'ma-plugin' ) );
		}

		return $data;
	}

	/**
	 * Clear all cached API data.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function clear_cache() {
		global $wpdb;

		// Delete all WebinarJam transients.
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ma_webinarjam_%'" );
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_ma_webinarjam_%'" );

		ma_log_webinarjam_debug( 'Cleared all WebinarJam API cache.' );
	}
}
