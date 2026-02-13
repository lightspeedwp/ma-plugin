<?php
/**
 * WebinarJam Course Integration
 *
 * Handles course-specific integrations and workflows.
 *
 * @package ma_plugin
 * @since 1.0.0
 */

namespace ma_plugin\classes\WebinarJam;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WebinarJam Course Integration class.
 *
 * Manages course publish workflow, complete button customization,
 * and LearnDash integration hooks.
 *
 * @since 1.0.0
 */
class WebinarJam_Course_Integration {

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
	 * @param WebinarJam_Scheduler $scheduler Scheduler instance.
	 */
	public function __construct( $scheduler ) {
		$this->scheduler = $scheduler;
		$this->setup_hooks();
	}

	/**
	 * Setup WordPress hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function setup_hooks() {
		// Course publish workflow.
		add_action( 'transition_post_status', array( $this, 'handle_course_publish' ), 10, 3 );

		// Complete button customization.
		add_filter( 'learndash_course_completion_button', array( $this, 'customize_completion_button' ), 10, 2 );
		add_filter( 'learndash_show_course_complete_button', array( $this, 'maybe_hide_complete_button' ), 10, 2 );

		// Course content restrictions.
		add_filter( 'learndash_content', array( $this, 'add_webinar_notice' ), 10, 2 );

		// Admin notices.
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );

		// Course list customization.
		add_filter( 'learndash_course_grid_course_data', array( $this, 'customize_course_grid_data' ), 10, 2 );
	}

	/**
	 * Handle course publish workflow.
	 *
	 * @since 1.0.0
	 * @param string   $new_status New post status.
	 * @param string   $old_status Old post status.
	 * @param \WP_Post $post Post object.
	 * @return void
	 */
	public function handle_course_publish( $new_status, $old_status, $post ) {
		// Only process course posts.
		if ( 'sfwd-courses' !== $post->post_type ) {
			return;
		}

		// Only process when publishing.
		if ( 'publish' !== $new_status || 'publish' === $old_status ) {
			return;
		}

		// Only process webinar courses.
		if ( ! ma_is_webinar_course( $post->ID ) ) {
			return;
		}

		ma_log_webinarjam_debug( "Course {$post->ID} published, processing webinar workflow..." );

		// Find connected event.
		$event_id = ma_get_event_by_course_id( $post->ID );

		if ( $event_id ) {
			$this->publish_event( $event_id, $post->ID );
		}

		// Schedule status update for webinar start time.
		$next_date = ma_get_next_webinar_date( $post->ID );

		if ( $next_date ) {
			$start_timestamp = strtotime( $next_date );

			// Only schedule if in the future.
			if ( $start_timestamp > time() ) {
				$this->scheduler->schedule_status_update( $post->ID, $start_timestamp );
				ma_log_webinarjam_debug( "Scheduled status update for course {$post->ID} at " . gmdate( 'Y-m-d H:i:s', $start_timestamp ) );
			}
		}

		// Sync webinar status.
		$this->sync_webinar_status( $post->ID );
	}

	/**
	 * Publish connected event.
	 *
	 * @since 1.0.0
	 * @param int $event_id Event ID.
	 * @param int $course_id Course ID.
	 * @return void
	 */
	private function publish_event( $event_id, $course_id ) {
		// Get current event status.
		$event_status = get_post_status( $event_id );

		// Only auto-publish if currently draft or pending.
		if ( ! in_array( $event_status, array( 'draft', 'pending', 'auto-draft' ), true ) ) {
			ma_log_webinarjam_debug( "Event {$event_id} status is {$event_status}, not auto-publishing." );
			return;
		}

		// Update event to published.
		$result = wp_update_post( array(
			'ID'          => $event_id,
			'post_status' => 'publish',
		), true );

		if ( is_wp_error( $result ) ) {
			ma_log_webinarjam_debug( "Failed to publish event {$event_id}: " . $result->get_error_message(), 'error' );
			return;
		}

		// Sync event data from course.
		ma_sync_event_from_course( $event_id, $course_id );

		ma_log_webinarjam_debug( "Auto-published event {$event_id} for course {$course_id}." );
	}

	/**
	 * Sync webinar status based on schedule.
	 *
	 * @since 1.0.0
	 * @param int $course_id Course ID.
	 * @return void
	 */
	private function sync_webinar_status( $course_id ) {
		$schedules = ma_get_webinar_schedule( $course_id );

		if ( empty( $schedules ) ) {
			return;
		}

		$now        = current_time( 'timestamp' );
		$has_future = false;

		foreach ( $schedules as $schedule ) {
			if ( empty( $schedule['schedule_date'] ) ) {
				continue;
			}

			$schedule_timestamp = strtotime( $schedule['schedule_date'] );

			if ( $schedule_timestamp > $now ) {
				$has_future = true;
				break;
			}
		}

		// Update status to upcoming if has future dates.
		if ( $has_future ) {
			$current_status = ma_get_webinar_status( $course_id );

			if ( 'upcoming' !== $current_status ) {
				ma_update_webinar_status( $course_id, 'upcoming' );
				ma_log_webinarjam_debug( "Updated course {$course_id} status to upcoming." );
			}
		}
	}

	/**
	 * Customize completion button for webinar courses.
	 *
	 * @since 1.0.0
	 * @param string $button_html Button HTML.
	 * @param array  $context Context array with course_id and user_id.
	 * @return string Modified button HTML.
	 */
	public function customize_completion_button( $button_html, $context ) {
		$course_id = isset( $context['course_id'] ) ? absint( $context['course_id'] ) : 0;

		if ( empty( $course_id ) || ! ma_is_webinar_course( $course_id ) ) {
			return $button_html;
		}

		// Check if user is admin.
		if ( current_user_can( 'manage_options' ) ) {
			// Add admin notice but keep button functional.
			$admin_notice = sprintf(
				'<p class="webinar-admin-notice" style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin: 10px 0;">
					<strong>%s</strong><br>%s
				</p>',
				esc_html__( 'Admin Override:', 'ma-plugin' ),
				esc_html__( 'As an administrator, you can manually mark this webinar complete. However, completion is normally tracked automatically via attendance.', 'ma-plugin' )
			);

			return $admin_notice . $button_html;
		}

		// For non-admin users, replace with informational message.
		$auto_complete_enabled = get_field( 'webinarjam_auto_complete', $course_id );

		if ( ! $auto_complete_enabled ) {
			return $button_html;
		}

		return sprintf(
			'<div class="webinar-completion-info" style="background-color: #e7f3ff; border-left: 4px solid #0073aa; padding: 16px; margin: 20px 0; border-radius: 4px;">
				<h4 style="margin-top: 0; color: #0073aa;">%s</h4>
				<p style="margin-bottom: 0;">%s</p>
			</div>',
			esc_html__( 'Completion Tracked Automatically', 'ma-plugin' ),
			esc_html__( 'This course will be automatically marked as complete after you attend the live webinar session. Your attendance will be verified from WebinarJam records.', 'ma-plugin' )
		);
	}

	/**
	 * Maybe hide complete button for webinar courses.
	 *
	 * @since 1.0.0
	 * @param bool $show_button Whether to show button.
	 * @param int  $course_id Course ID.
	 * @return bool Modified show_button value.
	 */
	public function maybe_hide_complete_button( $show_button, $course_id ) {
		if ( ! ma_is_webinar_course( $course_id ) ) {
			return $show_button;
		}

		// Allow admins to see the button.
		if ( current_user_can( 'manage_options' ) ) {
			return $show_button;
		}

		// Check if auto-complete is enabled.
		$auto_complete_enabled = get_field( 'webinarjam_auto_complete', $course_id );

		if ( ! $auto_complete_enabled ) {
			return $show_button;
		}

		// Hide button for non-admin users when auto-complete is enabled.
		return false;
	}

	/**
	 * Add webinar notice to course content.
	 *
	 * @since 1.0.0
	 * @param string $content Course content.
	 * @param object $post Post object.
	 * @return string Modified content.
	 */
	public function add_webinar_notice( $content, $post ) {
		// Only process courses.
		if ( 'sfwd-courses' !== $post->post_type ) {
			return $content;
		}

		// Only process webinar courses.
		if ( ! ma_is_webinar_course( $post->ID ) ) {
			return $content;
		}

		// Get webinar info.
		$status    = ma_get_webinar_status( $post->ID );
		$next_date = ma_get_next_webinar_date( $post->ID );

		// Build notice HTML.
		$notice = '<div class="webinar-course-notice">';

		// Status-specific messages.
		if ( 'upcoming' === $status && $next_date ) {
			$formatted_date = wp_date( 'F j, Y \a\t g:i A', strtotime( $next_date ) );

			$notice .= sprintf(
				'<div class="webinar-notice webinar-notice-upcoming">
					<span class="webinar-notice-icon">📅</span>
					<div class="webinar-notice-content">
						<strong>%s</strong>
						<p>%s</p>
					</div>
				</div>',
				esc_html__( 'Upcoming Live Webinar', 'ma-plugin' ),
				sprintf(
					/* translators: %s: Formatted date and time */
					esc_html__( 'This webinar will be held live on %s. Register now to secure your spot!', 'ma-plugin' ),
					'<strong>' . esc_html( $formatted_date ) . '</strong>'
				)
			);
		} elseif ( 'live' === $status ) {
			$notice .= sprintf(
				'<div class="webinar-notice webinar-notice-live">
					<span class="webinar-notice-icon">🔴</span>
					<div class="webinar-notice-content">
						<strong>%s</strong>
						<p>%s</p>
					</div>
				</div>',
				esc_html__( 'Live Now!', 'ma-plugin' ),
				esc_html__( 'This webinar is currently in progress. Join now to participate in the live session!', 'ma-plugin' )
			);
		} elseif ( 'completed' === $status ) {
			$notice .= sprintf(
				'<div class="webinar-notice webinar-notice-completed">
					<span class="webinar-notice-icon">▶️</span>
					<div class="webinar-notice-content">
						<strong>%s</strong>
						<p>%s</p>
					</div>
				</div>',
				esc_html__( 'Replay Available', 'ma-plugin' ),
				esc_html__( 'This webinar has been recorded. Watch the replay at your convenience.', 'ma-plugin' )
			);
		}

		$notice .= '</div>';

		// Add styles.
		$notice .= '
		<style>
			.webinar-course-notice {
				margin: 0 0 30px 0;
			}
			.webinar-notice {
				display: flex;
				gap: 16px;
				padding: 20px;
				border-radius: 8px;
				border-left: 4px solid;
				align-items: flex-start;
			}
			.webinar-notice-icon {
				font-size: 32px;
				line-height: 1;
			}
			.webinar-notice-content {
				flex: 1;
			}
			.webinar-notice-content strong {
				display: block;
				font-size: 18px;
				margin-bottom: 8px;
			}
			.webinar-notice-content p {
				margin: 0;
				line-height: 1.6;
			}
			.webinar-notice-upcoming {
				background-color: #fff3cd;
				border-color: #ffc107;
			}
			.webinar-notice-live {
				background-color: #f8d7da;
				border-color: #dc3545;
				animation: pulse-border 2s infinite;
			}
			.webinar-notice-completed {
				background-color: #d4edda;
				border-color: #28a745;
			}
			@keyframes pulse-border {
				0%, 100% { border-left-width: 4px; }
				50% { border-left-width: 8px; }
			}
		</style>';

		return $notice . $content;
	}

	/**
	 * Display admin notices for webinar courses.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function admin_notices() {
		$screen = get_current_screen();

		if ( ! $screen || 'sfwd-courses' !== $screen->post_type ) {
			return;
		}

		// Check if editing a webinar course.
		if ( 'post' === $screen->base && ! empty( $_GET['post'] ) ) {
			$course_id = absint( $_GET['post'] );

			if ( ! ma_is_webinar_course( $course_id ) ) {
				return;
			}

			// Show sync status.
			$last_sync = get_field( 'webinarjam_last_sync', $course_id );

			if ( $last_sync ) {
				$sync_time = human_time_diff( strtotime( $last_sync ), current_time( 'timestamp' ) );

				printf(
					'<div class="notice notice-info">
						<p><strong>%s</strong> %s</p>
					</div>',
					esc_html__( 'WebinarJam Sync:', 'ma-plugin' ),
					sprintf(
						/* translators: %s: Time since last sync */
						esc_html__( 'Last synchronized %s ago.', 'ma-plugin' ),
						esc_html( $sync_time )
					)
				);
			}

			// Show status update schedule.
			$next_update = wp_next_scheduled( 'ma_webinarjam_update_status', array( $course_id ) );

			if ( $next_update ) {
				$update_time = human_time_diff( current_time( 'timestamp' ), $next_update );

				printf(
					'<div class="notice notice-info">
						<p><strong>%s</strong> %s</p>
					</div>',
					esc_html__( 'Status Update:', 'ma-plugin' ),
					sprintf(
						/* translators: %s: Time until status update */
						esc_html__( 'Scheduled to update in %s.', 'ma-plugin' ),
						esc_html( $update_time )
					)
				);
			}
		}
	}

	/**
	 * Customize course grid data for webinar courses.
	 *
	 * @since 1.0.0
	 * @param array $course_data Course data.
	 * @param int   $course_id Course ID.
	 * @return array Modified course data.
	 */
	public function customize_course_grid_data( $course_data, $course_id ) {
		if ( ! ma_is_webinar_course( $course_id ) ) {
			return $course_data;
		}

		// Add webinar badge.
		$status = ma_get_webinar_status( $course_id );

		$status_labels = array(
			'upcoming'  => __( 'Upcoming Webinar', 'ma-plugin' ),
			'live'      => __( 'Live Now', 'ma-plugin' ),
			'completed' => __( 'Replay', 'ma-plugin' ),
		);

		if ( isset( $status_labels[ $status ] ) ) {
			$course_data['ribbon_text'] = $status_labels[ $status ];
		}

		return $course_data;
	}
}
