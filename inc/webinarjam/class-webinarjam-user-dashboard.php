<?php
/**
 * WebinarJam User Dashboard
 *
 * Handles user dashboard enhancements for webinar display.
 *
 * @package ma_plugin
 * @since 1.0.0
 */

namespace ma_plugin\classes\WebinarJam;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WebinarJam User Dashboard class.
 *
 * Adds webinar sections to user dashboard with upcoming
 * webinars, registered webinars, and quick join functionality.
 *
 * @since 1.0.0
 */
class WebinarJam_User_Dashboard {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->setup_hooks();
	}

	/**
	 * Setup WordPress hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function setup_hooks() {
		// Add dashboard widgets.
		add_action( 'wp_dashboard_setup', array( $this, 'add_admin_dashboard_widget' ) );

		// Add to LearnDash profile.
		add_action( 'learndash_profile_after_profile_content', array( $this, 'render_learndash_profile_section' ) );

		// Shortcodes.
		add_shortcode( 'my_webinars', array( $this, 'render_my_webinars_shortcode' ) );
		add_shortcode( 'upcoming_webinars', array( $this, 'render_upcoming_webinars_shortcode' ) );

		// AJAX handlers for dashboard actions.
		add_action( 'wp_ajax_ma_dismiss_webinar_reminder', array( $this, 'ajax_dismiss_reminder' ) );
	}

	/**
	 * Add admin dashboard widget.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_admin_dashboard_widget() {
		// Only for non-admin users.
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'ma_webinarjam_dashboard',
			__( 'My Webinars', 'ma-plugin' ),
			array( $this, 'render_dashboard_widget' )
		);
	}

	/**
	 * Render dashboard widget.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_dashboard_widget() {
		$user_id = get_current_user_id();

		if ( empty( $user_id ) ) {
			return;
		}

		$upcoming = $this->get_user_upcoming_webinars( $user_id );
		$registered = $this->get_user_registered_webinars( $user_id );

		?>
		<div class="ma-dashboard-webinars">
			<?php if ( ! empty( $upcoming ) ) : ?>
				<div class="dashboard-section upcoming-webinars">
					<h3><?php esc_html_e( 'Upcoming Live Webinars', 'ma-plugin' ); ?></h3>
					<?php $this->render_webinar_list( $upcoming, true ); ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $registered ) ) : ?>
				<div class="dashboard-section registered-webinars">
					<h3><?php esc_html_e( 'My Registered Webinars', 'ma-plugin' ); ?></h3>
					<?php $this->render_webinar_list( $registered ); ?>
				</div>
			<?php endif; ?>

			<?php if ( empty( $upcoming ) && empty( $registered ) ) : ?>
				<p><?php esc_html_e( 'You have no upcoming webinars. Browse available webinars to register.', 'ma-plugin' ); ?></p>
			<?php endif; ?>
		</div>

		<style>
			.ma-dashboard-webinars .dashboard-section {
				margin-bottom: 20px;
			}
			.ma-dashboard-webinars h3 {
				margin-top: 0;
				border-bottom: 1px solid #ddd;
				padding-bottom: 10px;
			}
		</style>
		<?php
	}

	/**
	 * Render LearnDash profile section.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @return void
	 */
	public function render_learndash_profile_section( $user_id ) {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$upcoming = $this->get_user_upcoming_webinars( $user_id );

		if ( empty( $upcoming ) ) {
			return;
		}

		?>
		<div class="learndash-profile-webinars">
			<h2><?php esc_html_e( 'Upcoming Webinars', 'ma-plugin' ); ?></h2>
			<?php $this->render_webinar_list( $upcoming, true ); ?>
		</div>
		<?php
	}

	/**
	 * Render my webinars shortcode.
	 *
	 * @since 1.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function render_my_webinars_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'show_countdown' => 'yes',
				'limit'          => -1,
			),
			$atts,
			'my_webinars'
		);

		$user_id = get_current_user_id();

		if ( empty( $user_id ) ) {
			return '<p>' . esc_html__( 'Please log in to view your webinars.', 'ma-plugin' ) . '</p>';
		}

		$registered = $this->get_user_registered_webinars( $user_id, absint( $atts['limit'] ) );

		if ( empty( $registered ) ) {
			return '<p>' . esc_html__( 'You have no registered webinars.', 'ma-plugin' ) . '</p>';
		}

		ob_start();
		$this->render_webinar_list( $registered, 'yes' === $atts['show_countdown'] );
		return ob_get_clean();
	}

	/**
	 * Render upcoming webinars shortcode.
	 *
	 * @since 1.0.0
	 * @param array $atts Shortcode attributes.
	 * @return string Shortcode output.
	 */
	public function render_upcoming_webinars_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit'          => 5,
				'show_countdown' => 'no',
			),
			$atts,
			'upcoming_webinars'
		);

		$webinars = $this->get_upcoming_webinars( absint( $atts['limit'] ) );

		if ( empty( $webinars ) ) {
			return '<p>' . esc_html__( 'No upcoming webinars scheduled.', 'ma-plugin' ) . '</p>';
		}

		ob_start();
		$this->render_webinar_list( $webinars, 'yes' === $atts['show_countdown'] );
		return ob_get_clean();
	}

	/**
	 * Get user's upcoming webinars.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @param int $limit Maximum number to retrieve.
	 * @return array Array of course IDs with webinar data.
	 */
	private function get_user_upcoming_webinars( $user_id, $limit = 5 ) {
		// Get user's enrolled courses.
		$enrolled_courses = learndash_user_get_enrolled_courses( $user_id );

		if ( empty( $enrolled_courses ) ) {
			return array();
		}

		$webinars = array();
		$now      = current_time( 'timestamp' );

		foreach ( $enrolled_courses as $course_id ) {
			// Skip non-webinar courses.
			if ( ! ma_is_webinar_course( $course_id ) ) {
				continue;
			}

			$status = ma_get_webinar_status( $course_id );

			// Only upcoming or live webinars.
			if ( ! in_array( $status, array( 'upcoming', 'live' ), true ) ) {
				continue;
			}

			$next_date = ma_get_next_webinar_date( $course_id );

			if ( ! $next_date ) {
				continue;
			}

			$timestamp = strtotime( $next_date );

			// Skip past dates.
			if ( $timestamp < $now && 'upcoming' === $status ) {
				continue;
			}

			$webinars[] = array(
				'course_id' => $course_id,
				'timestamp' => $timestamp,
				'status'    => $status,
			);
		}

		// Sort by timestamp.
		usort( $webinars, function( $a, $b ) {
			return $a['timestamp'] - $b['timestamp'];
		});

		// Limit results.
		if ( $limit > 0 ) {
			$webinars = array_slice( $webinars, 0, $limit );
		}

		return $webinars;
	}

	/**
	 * Get user's registered webinars.
	 *
	 * @since 1.0.0
	 * @param int $user_id User ID.
	 * @param int $limit Maximum number to retrieve.
	 * @return array Array of course IDs with webinar data.
	 */
	private function get_user_registered_webinars( $user_id, $limit = -1 ) {
		$registered_webinars = get_user_meta( $user_id, 'registered_webinars', true );

		if ( empty( $registered_webinars ) || ! is_array( $registered_webinars ) ) {
			return array();
		}

		$webinars = array();

		foreach ( $registered_webinars as $course_id ) {
			// Verify course still exists.
			if ( ! get_post( $course_id ) ) {
				continue;
			}

			$status    = ma_get_webinar_status( $course_id );
			$next_date = ma_get_next_webinar_date( $course_id );

			$webinars[] = array(
				'course_id' => $course_id,
				'timestamp' => $next_date ? strtotime( $next_date ) : 0,
				'status'    => $status,
			);
		}

		// Sort by timestamp.
		usort( $webinars, function( $a, $b ) {
			return $a['timestamp'] - $b['timestamp'];
		});

		// Limit results.
		if ( $limit > 0 ) {
			$webinars = array_slice( $webinars, 0, $limit );
		}

		return $webinars;
	}

	/**
	 * Get all upcoming webinars (public).
	 *
	 * @since 1.0.0
	 * @param int $limit Maximum number to retrieve.
	 * @return array Array of course IDs with webinar data.
	 */
	private function get_upcoming_webinars( $limit = 5 ) {
		$course_ids = ma_get_webinar_courses_by_status( 'upcoming' );

		if ( empty( $course_ids ) ) {
			return array();
		}

		$webinars = array();
		$now      = current_time( 'timestamp' );

		foreach ( $course_ids as $course_id ) {
			$next_date = ma_get_next_webinar_date( $course_id );

			if ( ! $next_date ) {
				continue;
			}

			$timestamp = strtotime( $next_date );

			// Skip past dates.
			if ( $timestamp < $now ) {
				continue;
			}

			$webinars[] = array(
				'course_id' => $course_id,
				'timestamp' => $timestamp,
				'status'    => 'upcoming',
			);
		}

		// Sort by timestamp.
		usort( $webinars, function( $a, $b ) {
			return $a['timestamp'] - $b['timestamp'];
		});

		// Limit results.
		if ( $limit > 0 ) {
			$webinars = array_slice( $webinars, 0, $limit );
		}

		return $webinars;
	}

	/**
	 * Render webinar list.
	 *
	 * @since 1.0.0
	 * @param array $webinars Array of webinar data.
	 * @param bool  $show_countdown Whether to show countdown timer.
	 * @return void
	 */
	private function render_webinar_list( $webinars, $show_countdown = false ) {
		?>
		<div class="ma-webinar-list">
			<?php foreach ( $webinars as $webinar ) : ?>
				<?php
				$course_id = $webinar['course_id'];
				$course    = get_post( $course_id );
				$status    = $webinar['status'];
				$timestamp = $webinar['timestamp'];

				if ( ! $course ) {
					continue;
				}

				$formatted_date = $timestamp ? wp_date( 'F j, Y \a\t g:i A', $timestamp ) : '';
				$time_until     = $timestamp ? human_time_diff( current_time( 'timestamp' ), $timestamp ) : '';
				?>

				<div class="webinar-list-item webinar-status-<?php echo esc_attr( $status ); ?>">
					<div class="webinar-item-header">
						<h4>
							<a href="<?php echo esc_url( get_permalink( $course_id ) ); ?>">
								<?php echo esc_html( $course->post_title ); ?>
							</a>
						</h4>
						<?php ma_webinar_status( $course_id ); ?>
					</div>

					<?php if ( $formatted_date ) : ?>
						<div class="webinar-item-date">
							<strong><?php esc_html_e( 'Starts:', 'ma-plugin' ); ?></strong>
							<?php echo esc_html( $formatted_date ); ?>

							<?php if ( $timestamp > current_time( 'timestamp' ) ) : ?>
								<span class="time-until">(<?php echo esc_html( $time_until ); ?>)</span>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<?php if ( $show_countdown && $timestamp > current_time( 'timestamp' ) ) : ?>
						<div class="webinar-item-countdown">
							<?php ma_webinar_countdown( $course_id ); ?>
						</div>
					<?php endif; ?>

					<div class="webinar-item-actions">
						<?php if ( 'live' === $status ) : ?>
							<?php
							$join_url = get_field( 'webinarjam_registration_url', $course_id );
							if ( $join_url ) :
								?>
								<a href="<?php echo esc_url( $join_url ); ?>" class="button button-primary webinar-join-button" target="_blank">
									<?php esc_html_e( 'Join Now', 'ma-plugin' ); ?>
								</a>
							<?php endif; ?>
						<?php else : ?>
							<a href="<?php echo esc_url( get_permalink( $course_id ) ); ?>" class="button">
								<?php esc_html_e( 'View Details', 'ma-plugin' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<style>
			.ma-webinar-list {
				margin: 0;
			}
			.webinar-list-item {
				padding: 16px;
				margin-bottom: 16px;
				background-color: #f9f9f9;
				border-radius: 8px;
				border-left: 4px solid #0073aa;
			}
			.webinar-list-item.webinar-status-live {
				background-color: #fff5f5;
				border-left-color: #dc3232;
			}
			.webinar-item-header {
				display: flex;
				justify-content: space-between;
				align-items: center;
				margin-bottom: 10px;
			}
			.webinar-item-header h4 {
				margin: 0;
				font-size: 16px;
			}
			.webinar-item-header h4 a {
				text-decoration: none;
				color: #0073aa;
			}
			.webinar-item-header h4 a:hover {
				color: #005177;
			}
			.webinar-item-date {
				margin-bottom: 10px;
				font-size: 14px;
			}
			.webinar-item-date .time-until {
				color: #666;
				font-style: italic;
			}
			.webinar-item-countdown {
				margin: 15px 0;
			}
			.webinar-item-actions {
				margin-top: 12px;
			}
			.webinar-join-button {
				animation: pulse-button 2s infinite;
			}
			@keyframes pulse-button {
				0%, 100% { transform: scale(1); }
				50% { transform: scale(1.05); }
			}
		</style>
		<?php
	}

	/**
	 * AJAX handler for dismissing webinar reminder.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_dismiss_reminder() {
		check_ajax_referer( 'ma_webinarjam_nonce', 'nonce' );

		$user_id   = get_current_user_id();
		$course_id = isset( $_POST['course_id'] ) ? absint( $_POST['course_id'] ) : 0;

		if ( empty( $user_id ) || empty( $course_id ) ) {
			wp_send_json_error();
		}

		// Store dismissed reminder.
		$dismissed = get_user_meta( $user_id, 'dismissed_webinar_reminders', true );
		if ( ! is_array( $dismissed ) ) {
			$dismissed = array();
		}

		$dismissed[ $course_id ] = time();
		update_user_meta( $user_id, 'dismissed_webinar_reminders', $dismissed );

		wp_send_json_success();
	}
}
