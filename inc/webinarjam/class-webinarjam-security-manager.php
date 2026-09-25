<?php
/**
 * WebinarJam Security Manager
 *
 * Handles security aspects of the WebinarJam integration.
 *
 * @package ma_plugin
 * @since 1.0.0
 */

namespace ma_plugin\classes\WebinarJam;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WebinarJam Security Manager class.
 *
 * Provides encryption, validation, sanitization, and security hardening.
 *
 * @since 1.0.0
 */
class WebinarJam_Security_Manager {

	/**
	 * Encryption key option name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const KEY_OPTION = 'ma_webinarjam_encryption_key';

	/**
	 * Initialize security manager.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function init() {
		// Add security headers.
		add_action( 'send_headers', array( __CLASS__, 'add_security_headers' ) );

		// Verify nonces on AJAX requests.
		add_action( 'wp_ajax_ma_webinarjam_register', array( __CLASS__, 'verify_ajax_nonce' ), 1 );
		add_action( 'wp_ajax_ma_force_sync_webinar', array( __CLASS__, 'verify_ajax_nonce' ), 1 );

		// Rate limit API calls.
		add_filter( 'ma_webinarjam_before_api_request', array( __CLASS__, 'check_rate_limit' ) );
	}

	/**
	 * Encrypt API credentials.
	 *
	 * Uses WordPress salts and AES-256 encryption.
	 *
	 * @since 1.0.0
	 * @param string $value Value to encrypt.
	 * @return string Encrypted value.
	 */
	public static function encrypt( $value ) {
		if ( empty( $value ) ) {
			return '';
		}

		$key    = self::get_encryption_key();
		$iv     = openssl_random_pseudo_bytes( openssl_cipher_iv_length( 'aes-256-cbc' ) );
		$encrypted = openssl_encrypt( $value, 'aes-256-cbc', $key, 0, $iv );

		// Prepend IV to encrypted data.
		return base64_encode( $iv . $encrypted );
	}

	/**
	 * Decrypt API credentials.
	 *
	 * @since 1.0.0
	 * @param string $encrypted_value Encrypted value.
	 * @return string Decrypted value.
	 */
	public static function decrypt( $encrypted_value ) {
		if ( empty( $encrypted_value ) ) {
			return '';
		}

		$key      = self::get_encryption_key();
		$data     = base64_decode( $encrypted_value );
		$iv_length = openssl_cipher_iv_length( 'aes-256-cbc' );
		$iv        = substr( $data, 0, $iv_length );
		$encrypted = substr( $data, $iv_length );

		return openssl_decrypt( $encrypted, 'aes-256-cbc', $key, 0, $iv );
	}

	/**
	 * Get or generate encryption key.
	 *
	 * @since 1.0.0
	 * @return string Encryption key.
	 */
	private static function get_encryption_key() {
		$key = get_option( self::KEY_OPTION );

		if ( ! $key ) {
			// Generate new key using WordPress salts.
			$key = hash( 'sha256', AUTH_KEY . SECURE_AUTH_KEY . LOGGED_IN_KEY . NONCE_KEY );
			update_option( self::KEY_OPTION, $key, false );
		}

		return $key;
	}

	/**
	 * Sanitize API response data.
	 *
	 * Recursively sanitizes all data from API responses.
	 *
	 * @since 1.0.0
	 * @param mixed $data Data to sanitize.
	 * @return mixed Sanitized data.
	 */
	public static function sanitize_api_data( $data ) {
		if ( is_string( $data ) ) {
			return sanitize_text_field( $data );
		}

		if ( is_array( $data ) ) {
			return array_map( array( __CLASS__, 'sanitize_api_data' ), $data );
		}

		return $data;
	}

	/**
	 * Validate email address.
	 *
	 * @since 1.0.0
	 * @param string $email Email address to validate.
	 * @return bool|WP_Error True if valid, WP_Error otherwise.
	 */
	public static function validate_email( $email ) {
		if ( empty( $email ) ) {
			return new \WP_Error( 'invalid_email', __( 'Email address is required.', 'ma-plugin' ) );
		}

		$email = sanitize_email( $email );

		if ( ! is_email( $email ) ) {
			return new \WP_Error( 'invalid_email', __( 'Invalid email address format.', 'ma-plugin' ) );
		}

		// Check for disposable email domains (optional).
		$disposable_domains = apply_filters( 'ma_webinarjam_disposable_email_domains', array(
			'mailinator.com',
			'guerrillamail.com',
			'10minutemail.com',
		) );

		$domain = substr( strrchr( $email, '@' ), 1 );
		if ( in_array( $domain, $disposable_domains, true ) ) {
			return new \WP_Error( 'disposable_email', __( 'Disposable email addresses are not allowed.', 'ma-plugin' ) );
		}

		return true;
	}

	/**
	 * Validate URL.
	 *
	 * @since 1.0.0
	 * @param string $url URL to validate.
	 * @return bool|WP_Error True if valid, WP_Error otherwise.
	 */
	public static function validate_url( $url ) {
		if ( empty( $url ) ) {
			return true; // URLs are optional in most cases.
		}

		$url = esc_url_raw( $url );

		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return new \WP_Error( 'invalid_url', __( 'Invalid URL format.', 'ma-plugin' ) );
		}

		// Only allow HTTPS URLs.
		if ( strpos( $url, 'https://' ) !== 0 ) {
			return new \WP_Error( 'insecure_url', __( 'Only HTTPS URLs are allowed.', 'ma-plugin' ) );
		}

		return true;
	}

	/**
	 * Verify AJAX nonce.
	 *
	 * @since 1.0.0
	 * @return void Dies if nonce verification fails.
	 */
	public static function verify_ajax_nonce() {
		if ( ! check_ajax_referer( 'ma_webinarjam_nonce', 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Security verification failed. Please refresh and try again.', 'ma-plugin' ),
				),
				403
			);
		}
	}

	/**
	 * Verify user capability.
	 *
	 * @since 1.0.0
	 * @param string $capability Required capability.
	 * @return bool|WP_Error True if user has capability, WP_Error otherwise.
	 */
	public static function verify_capability( $capability = 'manage_options' ) {
		if ( ! current_user_can( $capability ) ) {
			return new \WP_Error(
				'insufficient_permissions',
				__( 'You do not have permission to perform this action.', 'ma-plugin' )
			);
		}

		return true;
	}

	/**
	 * Check API rate limit.
	 *
	 * Prevents excessive API calls.
	 *
	 * @since 1.0.0
	 * @return bool|WP_Error True if within limit, WP_Error if rate limited.
	 */
	public static function check_rate_limit() {
		$transient_key = 'ma_webinarjam_api_rate_limit_' . get_current_user_id();
		$requests      = get_transient( $transient_key );

		// Allow 60 requests per hour.
		$max_requests = apply_filters( 'ma_webinarjam_api_rate_limit', 60 );

		if ( false === $requests ) {
			// First request in this hour.
			set_transient( $transient_key, 1, HOUR_IN_SECONDS );
			return true;
		}

		if ( $requests >= $max_requests ) {
			return new \WP_Error(
				'rate_limit_exceeded',
				sprintf(
					/* translators: %d: maximum number of requests */
					__( 'Rate limit exceeded. Maximum %d requests per hour.', 'ma-plugin' ),
					$max_requests
				)
			);
		}

		// Increment counter.
		set_transient( $transient_key, $requests + 1, HOUR_IN_SECONDS );

		return true;
	}

	/**
	 * Add security headers.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function add_security_headers() {
		// Only add headers on WebinarJam pages.
		if ( ! self::is_webinarjam_page() ) {
			return;
		}

		// Prevent clickjacking.
		header( 'X-Frame-Options: SAMEORIGIN' );

		// XSS protection.
		header( 'X-XSS-Protection: 1; mode=block' );

		// Prevent MIME type sniffing.
		header( 'X-Content-Type-Options: nosniff' );

		// Referrer policy.
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	}

	/**
	 * Check if current page is a WebinarJam page.
	 *
	 * @since 1.0.0
	 * @return bool True if WebinarJam page.
	 */
	private static function is_webinarjam_page() {
		if ( is_admin() ) {
			$screen = get_current_screen();
			return $screen && strpos( $screen->id, 'webinarjam' ) !== false;
		}

		global $post;
		if ( $post ) {
			return get_post_meta( $post->ID, '_webinarjam_webinar_id', true ) !== '';
		}

		return false;
	}

	/**
	 * Sanitize user input.
	 *
	 * @since 1.0.0
	 * @param mixed  $value Value to sanitize.
	 * @param string $type Type of sanitization (text, email, url, int, bool).
	 * @return mixed Sanitized value.
	 */
	public static function sanitize_input( $value, $type = 'text' ) {
		switch ( $type ) {
			case 'email':
				return sanitize_email( $value );

			case 'url':
				return esc_url_raw( $value );

			case 'int':
				return absint( $value );

			case 'bool':
				return (bool) $value;

			case 'html':
				return wp_kses_post( $value );

			case 'text':
			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Escape output for safe display.
	 *
	 * @since 1.0.0
	 * @param mixed  $value Value to escape.
	 * @param string $context Context (html, attr, url, js).
	 * @return mixed Escaped value.
	 */
	public static function escape_output( $value, $context = 'html' ) {
		switch ( $context ) {
			case 'attr':
				return esc_attr( $value );

			case 'url':
				return esc_url( $value );

			case 'js':
				return esc_js( $value );

			case 'html':
			default:
				return esc_html( $value );
		}
	}

	/**
	 * Generate secure nonce.
	 *
	 * @since 1.0.0
	 * @param string $action Action name.
	 * @return string Nonce value.
	 */
	public static function create_nonce( $action = 'ma_webinarjam_nonce' ) {
		return wp_create_nonce( $action );
	}

	/**
	 * Verify nonce.
	 *
	 * @since 1.0.0
	 * @param string $nonce Nonce value.
	 * @param string $action Action name.
	 * @return bool True if valid.
	 */
	public static function verify_nonce( $nonce, $action = 'ma_webinarjam_nonce' ) {
		return wp_verify_nonce( $nonce, $action ) !== false;
	}
}

// Initialize security manager.
WebinarJam_Security_Manager::init();
