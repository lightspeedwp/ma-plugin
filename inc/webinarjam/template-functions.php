<?php
/**
 * WebinarJam Template Functions
 *
 * Template tags for displaying webinar information in themes.
 *
 * @package ma_plugin
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display webinar button.
 *
 * @since 1.0.0
 * @param int    $course_id Course ID (default: current post).
 * @param string $class Additional CSS classes.
 * @param string $text Custom button text.
 * @return void
 */
function ma_webinar_button( $course_id = 0, $class = '', $text = '' ) {
	if ( empty( $course_id ) ) {
		$course_id = get_the_ID();
	}

	echo do_shortcode( sprintf(
		'[webinar_button course_id="%d" class="%s" text="%s"]',
		absint( $course_id ),
		esc_attr( $class ),
		esc_attr( $text )
	) );
}

/**
 * Display webinar status badge.
 *
 * @since 1.0.0
 * @param int $course_id Course ID (default: current post).
 * @return void
 */
function ma_webinar_status( $course_id = 0 ) {
	if ( empty( $course_id ) ) {
		$course_id = get_the_ID();
	}

	echo do_shortcode( sprintf(
		'[webinar_status course_id="%d"]',
		absint( $course_id )
	) );
}

/**
 * Display countdown timer.
 *
 * @since 1.0.0
 * @param int $course_id Course ID (default: current post).
 * @return void
 */
function ma_webinar_countdown( $course_id = 0 ) {
	if ( empty( $course_id ) ) {
		$course_id = get_the_ID();
	}

	echo do_shortcode( sprintf(
		'[webinar_countdown course_id="%d"]',
		absint( $course_id )
	) );
}

/**
 * Display webinar presenters.
 *
 * @since 1.0.0
 * @param int $course_id Course ID (default: current post).
 * @return void
 */
function ma_webinar_presenters( $course_id = 0 ) {
	if ( empty( $course_id ) ) {
		$course_id = get_the_ID();
	}

	$presenters = ma_get_webinar_presenters( $course_id );

	if ( empty( $presenters ) ) {
		return;
	}

	echo '<div class="webinar-presenters">';

	foreach ( $presenters as $presenter ) {
		?>
		<div class="webinar-presenter">
			<?php if ( ! empty( $presenter['presenter_photo'] ) ) : ?>
				<div class="presenter-photo">
					<?php echo wp_get_attachment_image( $presenter['presenter_photo'], 'thumbnail' ); ?>
				</div>
			<?php endif; ?>

			<div class="presenter-info">
				<?php if ( ! empty( $presenter['presenter_name'] ) ) : ?>
					<h4><?php echo esc_html( $presenter['presenter_name'] ); ?></h4>
				<?php endif; ?>

				<?php if ( ! empty( $presenter['presenter_email'] ) ) : ?>
					<a href="mailto:<?php echo esc_attr( $presenter['presenter_email'] ); ?>" class="presenter-email">
						<?php echo esc_html( $presenter['presenter_email'] ); ?>
					</a>
				<?php endif; ?>

				<?php if ( ! empty( $presenter['presenter_bio'] ) ) : ?>
					<div class="presenter-bio">
						<?php echo wp_kses_post( wpautop( $presenter['presenter_bio'] ) ); ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	echo '</div>';
}

/**
 * Display webinar schedule.
 *
 * @since 1.0.0
 * @param int $course_id Course ID (default: current post).
 * @return void
 */
function ma_webinar_schedule( $course_id = 0 ) {
	if ( empty( $course_id ) ) {
		$course_id = get_the_ID();
	}

	$schedules = ma_get_webinar_schedule( $course_id );

	if ( empty( $schedules ) ) {
		return;
	}

	echo '<ul class="webinar-schedule">';

	foreach ( $schedules as $schedule ) {
		$date     = ! empty( $schedule['schedule_date'] ) ? $schedule['schedule_date'] : '';
		$duration = ! empty( $schedule['schedule_duration'] ) ? absint( $schedule['schedule_duration'] ) : 60;
		$timezone = ! empty( $schedule['schedule_timezone'] ) ? $schedule['schedule_timezone'] : 'UTC';

		if ( empty( $date ) ) {
			continue;
		}

		$formatted_date = wp_date( 'F j, Y \a\t g:i A', strtotime( $date ) );

		?>
		<li>
			<div class="schedule-date">
				<?php echo esc_html( $formatted_date ); ?>
				<?php if ( $timezone !== 'UTC' ) : ?>
					<span class="schedule-timezone">(<?php echo esc_html( $timezone ); ?>)</span>
				<?php endif; ?>
			</div>
			<div class="schedule-duration">
				<?php
				/* translators: %d: Duration in minutes */
				echo esc_html( sprintf( __( 'Duration: %d minutes', 'ma-plugin' ), $duration ) );
				?>
			</div>
		</li>
		<?php
	}

	echo '</ul>';
}

/**
 * Display webinar info box.
 *
 * @since 1.0.0
 * @param int $course_id Course ID (default: current post).
 * @return void
 */
function ma_webinar_info( $course_id = 0 ) {
	if ( empty( $course_id ) ) {
		$course_id = get_the_ID();
	}

	if ( ! ma_is_webinar_course( $course_id ) ) {
		return;
	}

	$status    = ma_get_webinar_status( $course_id );
	$next_date = ma_get_next_webinar_date( $course_id );

	?>
	<div class="webinar-info">
		<h3><?php esc_html_e( 'Webinar Information', 'ma-plugin' ); ?></h3>

		<div class="webinar-meta">
			<div class="webinar-meta-item">
				<span class="meta-label"><?php esc_html_e( 'Status:', 'ma-plugin' ); ?></span>
				<?php ma_webinar_status( $course_id ); ?>
			</div>

			<?php if ( $next_date && 'upcoming' === $status ) : ?>
				<div class="webinar-meta-item">
					<span class="meta-label"><?php esc_html_e( 'Next Session:', 'ma-plugin' ); ?></span>
					<span class="meta-value">
						<?php echo esc_html( wp_date( 'F j, Y \a\t g:i A', strtotime( $next_date ) ) ); ?>
					</span>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( 'upcoming' === $status ) : ?>
			<?php ma_webinar_countdown( $course_id ); ?>
		<?php endif; ?>

		<?php ma_webinar_button( $course_id ); ?>
	</div>
	<?php
}

/**
 * Check if current page is a webinar.
 *
 * @since 1.0.0
 * @return bool True if current page is a webinar course or event.
 */
function ma_is_webinar() {
	if ( is_singular( 'sfwd-courses' ) ) {
		return ma_is_webinar_course( get_the_ID() );
	}

	if ( is_singular( 'tribe_events' ) ) {
		$course_id = get_field( 'course', get_the_ID() );
		if ( $course_id ) {
			return ma_is_webinar_course( $course_id );
		}
	}

	return false;
}

/**
 * Get webinar registration count.
 *
 * @since 1.0.0
 * @param int $course_id Course ID.
 * @return int Number of registered users.
 */
function ma_get_webinar_registration_count( $course_id ) {
	if ( empty( $course_id ) ) {
		return 0;
	}

	// Query users enrolled in the course.
	$enrolled_users = learndash_get_users_for_course( $course_id, array(), false );

	return is_array( $enrolled_users ) ? count( $enrolled_users ) : 0;
}

/**
 * Display registration count.
 *
 * @since 1.0.0
 * @param int $course_id Course ID (default: current post).
 * @return void
 */
function ma_webinar_registration_count( $course_id = 0 ) {
	if ( empty( $course_id ) ) {
		$course_id = get_the_ID();
	}

	$count = ma_get_webinar_registration_count( $course_id );

	printf(
		'<div class="webinar-registration-count">%s</div>',
		esc_html( sprintf(
			/* translators: %d: Number of registrations */
			_n( '%d person registered', '%d people registered', $count, 'ma-plugin' ),
			$count
		) )
	);
}
