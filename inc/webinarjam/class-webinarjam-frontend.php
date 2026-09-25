<?php
/**
 * WebinarJam Frontend Handler
 *
 * Handles frontend registration, button rendering, and user interactions.
 *
 * @package ma_plugin
 * @since 1.0.0
 */

namespace ma_plugin\classes\WebinarJam;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WebinarJam Frontend class.
 *
 * Manages user registration flow, dynamic button rendering,
 * and frontend webinar interactions.
 *
 * @since 1.0.0
 */
class WebinarJam_Frontend {

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
		$this->setup_hooks();
	}

	/**
	 * Setup WordPress hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function setup_hooks() {
		// Shortcodes.
		add_shortcode( 'webinar_button', array( $this, 'render_button_shortcode' ) );
		add_shortcode( 'webinar_status', array( $this, 'render_status_shortcode' ) );
		add_shortcode( 'webinar_countdown', array( $this, 'render_countdown_shortcode' ) );

		// AJAX handlers.
		add_action( 'wp_ajax_ma_register_webinar', array( $this, 'ajax_register_webinar' ) );
		add_action( 'wp_ajax_nopriv_ma_register_webinar', array( $this, 'ajax_handle_no_access' ) );

		// Scripts and styles.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Handle return from purchase.
		add_action( 'woocommerce_thankyou', array( $this, 'handle_purchase_return' ), 10, 1 );
		add_action( 'template_redirect', array( $this, 'handle_access_granted' ) );
	}

	/**
	 * Enqueue frontend scripts and styles.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_scripts() {
		// Only enqueue on relevant pages.
		if ( ! $this->should_load_scripts() ) {
			return;
		}

		wp_enqueue_style(
			'ma-webinarjam-frontend',
			MA_PLUGIN_URL . 'assets/css/webinarjam-frontend.css',
			array(),
			MA_PLUGIN_VERSION
		);

		wp_enqueue_script(
			'ma-webinarjam-frontend',
			MA_PLUGIN_URL . 'assets/js/webinarjam-frontend.js',
			array( 'jquery' ),
			MA_PLUGIN_VERSION,
			true
		);

		wp_localize_script(
			'ma-webinarjam-frontend',
			'maWebinarJam',
			array(
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'ma_webinarjam_nonce' ),
				'strings'        => array(
					'registering'    => __( 'Registering...', 'ma-plugin' ),
					'error'          => __( 'Registration failed. Please try again.', 'ma-plugin' ),
					'success'        => __( 'Successfully registered!', 'ma-plugin' ),
					'redirecting'    => __( 'Redirecting to purchase...', 'ma-plugin' ),
					'accessRequired' => __( 'Access required to register for this webinar.', 'ma-plugin' ),
				),
			)
		);
	}

	/**
	 * Check if scripts should be loaded on current page.
	 *
	 * @since 1.0.0
	 * @return bool True if should load.
	 */
	private function should_load_scripts() {
		// Load on single courses and events.
		if ( is_singular( 'sfwd-courses' ) || is_singular( 'tribe_events' ) ) {
			return true;
		}

		// Load on pages with webinar shortcodes.
		global $post;
		if ( $post && has_shortcode( $post->post_content, 'webinar_button' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Render webinar button shortcode.
	 *
	 * @since 1.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string Button HTML.
	 */
	public function render_button_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'course_id' => get_the_ID(),
				'event_id'  => 0,
				'class'     => 'ma-webinar-button',
				'text'      => '',
			),
			$atts,
			'webinar_button'
		);

		// If event ID provided, get the linked course.
		if ( ! empty( $atts['event_id'] ) ) {
			$atts['course_id'] = get_field( 'course', $atts['event_id'] );
		}

		if ( empty( $atts['course_id'] ) ) {
			return '';
		}

		return $this->render_button( $atts['course_id'], $atts['class'], $atts['text'] );
	}

	/**
	 * Render webinar status shortcode.
	 *
	 * @since 1.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string Status HTML.
	 */
	public function render_status_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'course_id' => get_the_ID(),
			),
			$atts,
			'webinar_status'
		);

		if ( empty( $atts['course_id'] ) ) {
			return '';
		}

		$status = ma_get_webinar_status( $atts['course_id'] );

		$status_labels = array(
			'upcoming'  => __( 'Upcoming', 'ma-plugin' ),
			'live'      => __( 'Live Now', 'ma-plugin' ),
			'completed' => __( 'Replay Available', 'ma-plugin' ),
		);

		$label = isset( $status_labels[ $status ] ) ? $status_labels[ $status ] : $status;

		return sprintf(
			'<span class="ma-webinar-status ma-webinar-status-%s">%s</span>',
			esc_attr( $status ),
			esc_html( $label )
		);
	}

	/**
	 * Render countdown timer shortcode.
	 *
	 * @since 1.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string Countdown HTML.
	 */
	public function render_countdown_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'course_id' => get_the_ID(),
			),
			$atts,
			'webinar_countdown'
		);

		if ( empty( $atts['course_id'] ) ) {
			return '';
		}

		$next_date = ma_get_next_webinar_date( $atts['course_id'] );

		if ( ! $next_date ) {
			return '';
		}

		$timestamp = strtotime( $next_date );

		return sprintf(
			'<div class="ma-webinar-countdown" data-timestamp="%d">
				<div class="countdown-timer">
					<div class="countdown-item">
						<span class="countdown-value days">0</span>
						<span class="countdown-label">%s</span>
					</div>
					<div class="countdown-item">
						<span class="countdown-value hours">0</span>
						<span class="countdown-label">%s</span>
					</div>
					<div class="countdown-item">
						<span class="countdown-value minutes">0</span>
						<span class="countdown-label">%s</span>
					</div>
					<div class="countdown-item">
						<span class="countdown-value seconds">0</span>
						<span class="countdown-label">%s</span>
					</div>
				</div>
			</div>',
			esc_attr( $timestamp ),
			esc_html__( 'Days', 'ma-plugin' ),
			esc_html__( 'Hours', 'ma-plugin' ),
			esc_html__( 'Minutes', 'ma-plugin' ),
			esc_html__( 'Seconds', 'ma-plugin' )
		);
	}

	/**
	 * Render webinar button.
	 *
	 * @since 1.0.0
	 * @param int    $course_id Course ID.
	 * @param string $class Additional CSS classes.
	 * @param string $custom_text Custom button text (optional).
	 * @return string Button HTML.
	 */
	public function render_button( $course_id, $class = '', $custom_text = '' ) {
		if ( ! ma_is_webinar_course( $course_id ) ) {
			return '';
		}

		$user_id    = get_current_user_id();
		$status     = ma_get_webinar_status( $course_id );
		$has_access = ma_user_has_webinar_access( $user_id );
		$is_registered = ma_is_user_registered_for_webinar( $user_id, $course_id );

		// Get button data.
		$button_data = ma_get_webinar_button_data( $course_id );

		// Use custom text if provided.
		if ( ! empty( $custom_text ) ) {
			$button_data['text'] = $custom_text;
		}

		// Determine button action.
		$action = $this->get_button_action( $course_id, $user_id, $status, $has_access, $is_registered );

		// Build button attributes.
		$button_atts = array(
			'class'           => 'ma-webinar-button ma-webinar-status-' . $status . ' ' . $class,
			'data-course-id'  => $course_id,
			'data-action'     => $action,
			'data-status'     => $status,
			'data-has-access' => $has_access ? 'true' : 'false',
		);

		// Add URL for direct links.
		if ( in_array( $action, array( 'watch', 'replay', 'purchase' ), true ) && ! empty( $button_data['url'] ) ) {
			$button_atts['href'] = $button_data['url'];
		} else {
			$button_atts['href'] = '#';
		}

		// Build attributes string.
		$atts_string = '';
		foreach ( $button_atts as $key => $value ) {
			$atts_string .= sprintf( ' %s="%s"', esc_attr( $key ), esc_attr( $value ) );
		}

		return sprintf(
			'<a%s><span class="button-text">%s</span><span class="button-loader" style="display:none;">%s</span></a>',
			$atts_string,
			esc_html( $button_data['text'] ),
			esc_html__( 'Loading...', 'ma-plugin' )
		);
	}

	/**
	 * Determine button action based on context.
	 *
	 * @since 1.0.0
	 * @param int    $course_id Course ID.
	 * @param int    $user_id User ID.
	 * @param string $status Webinar status.
	 * @param bool   $has_access User has access.
	 * @param bool   $is_registered User is registered.
	 * @return string Action type.
	 */
	private function get_button_action( $course_id, $user_id, $status, $has_access, $is_registered ) {
		// Not logged in - require purchase.
		if ( empty( $user_id ) ) {
			return 'purchase';
		}

		// Live webinar.
		if ( 'live' === $status ) {
			return $is_registered ? 'watch' : 'register';
		}

		// Completed webinar with replay.
		if ( 'completed' === $status ) {
			return 'replay';
		}

		// Upcoming webinar.
		if ( 'upcoming' === $status ) {
			if ( ! $has_access ) {
				return 'purchase';
			}

			return $is_registered ? 'registered' : 'register';
		}

		return 'register';
	}

	/**
	 * AJAX handler for webinar registration (authenticated users).
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_register_webinar() {
		// Verify nonce.
		check_ajax_referer( 'ma_webinarjam_nonce', 'nonce' );

		// Check user is logged in.
		$user_id = get_current_user_id();
		if ( empty( $user_id ) ) {
			wp_send_json_error( array(
				'message' => __( 'You must be logged in to register.', 'ma-plugin' ),
			) );
		}

		// Get course ID.
		$course_id = isset( $_POST['course_id'] ) ? absint( $_POST['course_id'] ) : 0;

		if ( empty( $course_id ) ) {
			wp_send_json_error( array(
				'message' => __( 'Invalid course ID.', 'ma-plugin' ),
			) );
		}

		// Check user has access.
		$has_access = ma_user_has_webinar_access( $user_id );

		if ( ! $has_access ) {
			$purchase_url = apply_filters( 'ma_webinar_purchase_url', home_url( '/subscribe/' ) );

			wp_send_json_error( array(
				'message'      => __( 'You need an active subscription to register for webinars.', 'ma-plugin' ),
				'redirect_url' => $purchase_url,
				'action'       => 'purchase_required',
			) );
		}

		// Register the user.
		$result = $this->register_user_for_webinar( $user_id, $course_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array(
				'message' => $result->get_error_message(),
			) );
		}

		wp_send_json_success( array(
			'message'      => __( 'Successfully registered for webinar!', 'ma-plugin' ),
			'redirect_url' => get_permalink( $course_id ),
		) );
	}

	/**
	 * AJAX handler for non-authenticated registration attempts.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_handle_no_access() {
		// Get course ID.
		$course_id = isset( $_POST['course_id'] ) ? absint( $_POST['course_id'] ) : 0;

		// Store referring course for post-purchase redirect.
		if ( ! empty( $course_id ) ) {
			$this->store_referring_course( $course_id );
		}

		$purchase_url = apply_filters( 'ma_webinar_purchase_url', home_url( '/subscribe/' ) );

		wp_send_json_error( array(
			'message'      => __( 'Please subscribe to access webinars.', 'ma-plugin' ),
			'redirect_url' => $purchase_url,
			'action'       => 'login_required',
		) );
	}

	/**
	 * Register user for webinar.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @param int $course_id Course ID.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	private function register_user_for_webinar( $user_id, $course_id ) {
		// Check if already registered.
		if ( ma_is_user_registered_for_webinar( $user_id, $course_id ) ) {
			return new \WP_Error( 'already_registered', __( 'You are already registered for this webinar.', 'ma-plugin' ) );
		}

		// Enroll in LearnDash course.
		if ( function_exists( 'ld_update_course_access' ) ) {
			ld_update_course_access( $user_id, $course_id );
		}

		// Get user data.
		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return new \WP_Error( 'invalid_user', __( 'Invalid user.', 'ma-plugin' ) );
		}

		// Get webinar ID.
		$webinar_id = get_field( 'webinarjam_webinar_id', $course_id );

		if ( empty( $webinar_id ) ) {
			// Course enrolled but no WebinarJam registration needed.
			ma_log_webinarjam_debug( "User {$user_id} enrolled in course {$course_id} (no webinar ID)." );
			return true;
		}

		// Register with WebinarJam API.
		$api_result = $this->api_client->register_user(
			$webinar_id,
			$user->user_email,
			$user->display_name
		);

		if ( is_wp_error( $api_result ) ) {
			ma_log_webinarjam_debug(
				"Failed to register user {$user_id} for webinar {$webinar_id}: " . $api_result->get_error_message(),
				'error'
			);

			// Still consider it a success since they're enrolled in the course.
			// The registration can be retried later.
			return true;
		}

		// Update user meta.
		$registered_webinars = get_user_meta( $user_id, 'registered_webinars', true );
		if ( ! is_array( $registered_webinars ) ) {
			$registered_webinars = array();
		}

		if ( ! in_array( $course_id, $registered_webinars, true ) ) {
			$registered_webinars[] = $course_id;
			update_user_meta( $user_id, 'registered_webinars', $registered_webinars );
		}

		ma_log_webinarjam_debug( "User {$user_id} registered for webinar {$webinar_id} (course {$course_id})." );

		// Fire action for extensions.
		do_action( 'ma_webinarjam_user_registered', $user_id, $course_id, $webinar_id );

		return true;
	}

	/**
	 * Store referring course in session/cookie.
	 *
	 * @since 1.0.0
	 * @param int $course_id Course ID.
	 * @return void
	 */
	private function store_referring_course( $course_id ) {
		// Use WordPress transient for logged-in users.
		$user_id = get_current_user_id();
		if ( $user_id ) {
			set_transient( 'ma_webinar_referring_course_' . $user_id, $course_id, DAY_IN_SECONDS );
		}

		// Also use cookie for non-logged-in users.
		setcookie( 'ma_webinar_referring_course', $course_id, time() + DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
	}

	/**
	 * Get referring course from session/cookie.
	 *
	 * @since 1.0.0
	 * @return int|false Course ID or false.
	 */
	private function get_referring_course() {
		$user_id = get_current_user_id();

		// Check transient first.
		if ( $user_id ) {
			$course_id = get_transient( 'ma_webinar_referring_course_' . $user_id );
			if ( $course_id ) {
				return absint( $course_id );
			}
		}

		// Check cookie.
		if ( isset( $_COOKIE['ma_webinar_referring_course'] ) ) {
			return absint( $_COOKIE['ma_webinar_referring_course'] );
		}

		return false;
	}

	/**
	 * Clear referring course from session/cookie.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function clear_referring_course() {
		$user_id = get_current_user_id();

		if ( $user_id ) {
			delete_transient( 'ma_webinar_referring_course_' . $user_id );
		}

		setcookie( 'ma_webinar_referring_course', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN );
	}

	/**
	 * Handle return from purchase page.
	 *
	 * @since 1.0.0
	 * @param int $order_id WooCommerce order ID.
	 * @return void
	 */
	public function handle_purchase_return( $order_id ) {
		$course_id = $this->get_referring_course();

		if ( ! $course_id ) {
			return;
		}

		// Store for template_redirect processing.
		set_transient( 'ma_webinar_pending_registration_' . get_current_user_id(), $course_id, 300 );

		$this->clear_referring_course();
	}

	/**
	 * Handle access granted after purchase.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_access_granted() {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return;
		}

		$course_id = get_transient( 'ma_webinar_pending_registration_' . $user_id );

		if ( ! $course_id ) {
			return;
		}

		delete_transient( 'ma_webinar_pending_registration_' . $user_id );

		// Check if user now has access.
		$has_access = ma_user_has_webinar_access( $user_id );

		if ( ! $has_access ) {
			return;
		}

		// Auto-register for webinar.
		$result = $this->register_user_for_webinar( $user_id, $course_id );

		if ( ! is_wp_error( $result ) ) {
			// Redirect to course page with success message.
			wp_safe_redirect(
				add_query_arg(
					'webinar_registered',
					'1',
					get_permalink( $course_id )
				)
			);
			exit;
		}
	}
}
