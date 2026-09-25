<?php
/**
 * WebinarJam Admin Interface
 *
 * Handles admin list columns, filters, and bulk actions.
 *
 * @package ma_plugin
 * @since 1.0.0
 */

namespace ma_plugin\classes\WebinarJam;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WebinarJam Admin Interface class.
 *
 * Adds custom admin columns, filters, and bulk actions
 * for webinar course management.
 *
 * @since 1.0.0
 */
class WebinarJam_Admin {

	/**
	 * API Client instance.
	 *
	 * @since 1.0.0
	 * @var WebinarJam_API_Client
	 */
	private $api_client;

	/**
	 * Scheduler instance.
	 *
	 * @since 1.0.0
	 * @var WebinarJam_Scheduler
	 */
	private $scheduler;

	/**
	 * Sync handler instance.
	 *
	 * @since 1.0.0
	 * @var WebinarJam_Sync
	 */
	private $sync_handler;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @param WebinarJam_API_Client $api_client API client instance.
	 * @param WebinarJam_Scheduler  $scheduler Scheduler instance.
	 * @param WebinarJam_Sync       $sync_handler Sync handler instance.
	 */
	public function __construct( $api_client, $scheduler, $sync_handler ) {
		$this->api_client   = $api_client;
		$this->scheduler    = $scheduler;
		$this->sync_handler = $sync_handler;
		$this->setup_hooks();
	}

	/**
	 * Setup WordPress hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function setup_hooks() {
		// Add custom columns.
		add_filter( 'manage_sfwd-courses_posts_columns', array( $this, 'add_custom_columns' ) );
		add_action( 'manage_sfwd-courses_posts_custom_column', array( $this, 'render_custom_columns' ), 10, 2 );
		add_filter( 'manage_edit-sfwd-courses_sortable_columns', array( $this, 'make_columns_sortable' ) );

		// Add filters.
		add_action( 'restrict_manage_posts', array( $this, 'add_admin_filters' ) );
		add_filter( 'parse_query', array( $this, 'filter_courses_by_webinar' ) );

		// Add bulk actions.
		add_filter( 'bulk_actions-edit-sfwd-courses', array( $this, 'add_bulk_actions' ) );
		add_filter( 'handle_bulk_actions-edit-sfwd-courses', array( $this, 'handle_bulk_actions' ), 10, 3 );

		// Bulk action admin notices.
		add_action( 'admin_notices', array( $this, 'bulk_action_notices' ) );

		// AJAX handlers for inline actions.
		add_action( 'wp_ajax_ma_force_sync_webinar', array( $this, 'ajax_force_sync' ) );
		add_action( 'wp_ajax_ma_test_import_webinar', array( $this, 'ajax_test_import' ) );

		// Add row actions.
		add_filter( 'post_row_actions', array( $this, 'add_row_actions' ), 10, 2 );

		// Enqueue admin scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Add custom columns to courses list.
	 *
	 * @since 1.0.0
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function add_custom_columns( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;

			// Add webinar columns after title.
			if ( 'title' === $key ) {
				$new_columns['webinar_id']     = __( 'Webinar ID', 'ma-plugin' );
				$new_columns['webinar_status'] = __( 'Status', 'ma-plugin' );
				$new_columns['next_date']      = __( 'Next Date', 'ma-plugin' );
				$new_columns['last_sync']      = __( 'Last Sync', 'ma-plugin' );
				$new_columns['registered']     = __( 'Registered', 'ma-plugin' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render custom column content.
	 *
	 * @since 1.0.0
	 * @param string $column Column name.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function render_custom_columns( $column, $post_id ) {
		// Only for webinar courses.
		if ( ! ma_is_webinar_course( $post_id ) ) {
			echo '<span style="color: #999;">—</span>';
			return;
		}

		switch ( $column ) {
			case 'webinar_id':
				$webinar_id = get_field( 'webinarjam_webinar_id', $post_id );
				if ( $webinar_id ) {
					echo '<code>' . esc_html( $webinar_id ) . '</code>';
				} else {
					echo '<span style="color: #999;">—</span>';
				}
				break;

			case 'webinar_status':
				$status = ma_get_webinar_status( $post_id );
				$this->render_status_badge( $status );
				break;

			case 'next_date':
				$next_date = ma_get_next_webinar_date( $post_id );
				if ( $next_date ) {
					$timestamp = strtotime( $next_date );
					$now       = current_time( 'timestamp' );

					echo '<strong>' . esc_html( wp_date( 'M j, Y', $timestamp ) ) . '</strong><br>';
					echo '<small>' . esc_html( wp_date( 'g:i A', $timestamp ) ) . '</small><br>';

					if ( $timestamp > $now ) {
						$time_diff = human_time_diff( $now, $timestamp );
						echo '<small style="color: #999;">in ' . esc_html( $time_diff ) . '</small>';
					} else {
						echo '<small style="color: #d63638;">Past</small>';
					}
				} else {
					echo '<span style="color: #999;">No schedule</span>';
				}
				break;

			case 'last_sync':
				$last_sync = get_post_meta( $post_id, '_webinarjam_last_sync', true );
				if ( $last_sync ) {
					$timestamp = strtotime( $last_sync );
					echo '<span title="' . esc_attr( wp_date( 'F j, Y g:i A', $timestamp ) ) . '">';
					echo esc_html( human_time_diff( $timestamp, current_time( 'timestamp' ) ) ) . ' ago';
					echo '</span>';
				} else {
					echo '<span style="color: #999;">Never</span>';
				}
				break;

			case 'registered':
				$registered_count = $this->get_registered_count( $post_id );
				if ( $registered_count > 0 ) {
					echo '<strong>' . absint( $registered_count ) . '</strong>';
				} else {
					echo '<span style="color: #999;">0</span>';
				}
				break;
		}
	}

	/**
	 * Make custom columns sortable.
	 *
	 * @since 1.0.0
	 * @param array $columns Sortable columns.
	 * @return array Modified columns.
	 */
	public function make_columns_sortable( $columns ) {
		$columns['webinar_status'] = 'webinar_status';
		$columns['next_date']      = 'next_date';
		$columns['last_sync']      = 'last_sync';
		return $columns;
	}

	/**
	 * Add admin filters.
	 *
	 * @since 1.0.0
	 * @param string $post_type Current post type.
	 * @return void
	 */
	public function add_admin_filters( $post_type ) {
		if ( 'sfwd-courses' !== $post_type ) {
			return;
		}

		// Webinar status filter.
		$current_status = isset( $_GET['webinar_status'] ) ? sanitize_text_field( wp_unslash( $_GET['webinar_status'] ) ) : '';
		?>
		<select name="webinar_status">
			<option value=""><?php esc_html_e( 'All Webinar Statuses', 'ma-plugin' ); ?></option>
			<option value="upcoming" <?php selected( $current_status, 'upcoming' ); ?>><?php esc_html_e( 'Upcoming', 'ma-plugin' ); ?></option>
			<option value="live" <?php selected( $current_status, 'live' ); ?>><?php esc_html_e( 'Live', 'ma-plugin' ); ?></option>
			<option value="completed" <?php selected( $current_status, 'completed' ); ?>><?php esc_html_e( 'Completed', 'ma-plugin' ); ?></option>
		</select>
		<?php

		// Sync status filter.
		$current_sync = isset( $_GET['sync_status'] ) ? sanitize_text_field( wp_unslash( $_GET['sync_status'] ) ) : '';
		?>
		<select name="sync_status">
			<option value=""><?php esc_html_e( 'All Sync Statuses', 'ma-plugin' ); ?></option>
			<option value="synced" <?php selected( $current_sync, 'synced' ); ?>><?php esc_html_e( 'Synced', 'ma-plugin' ); ?></option>
			<option value="pending" <?php selected( $current_sync, 'pending' ); ?>><?php esc_html_e( 'Pending Sync', 'ma-plugin' ); ?></option>
			<option value="error" <?php selected( $current_sync, 'error' ); ?>><?php esc_html_e( 'Sync Error', 'ma-plugin' ); ?></option>
		</select>
		<?php
	}

	/**
	 * Filter courses by webinar status.
	 *
	 * @since 1.0.0
	 * @param \WP_Query $query Query object.
	 * @return void
	 */
	public function filter_courses_by_webinar( $query ) {
		global $pagenow;

		if ( ! is_admin() || 'edit.php' !== $pagenow || ! isset( $_GET['post_type'] ) || 'sfwd-courses' !== $_GET['post_type'] ) {
			return;
		}

		// Filter by webinar status.
		if ( ! empty( $_GET['webinar_status'] ) ) {
			$webinar_status = sanitize_text_field( wp_unslash( $_GET['webinar_status'] ) );
			$meta_query     = $query->get( 'meta_query' ) ?: array();

			$meta_query[] = array(
				'key'   => 'webinarjam_status',
				'value' => $webinar_status,
			);

			$query->set( 'meta_query', $meta_query );
		}

		// Filter by sync status.
		if ( ! empty( $_GET['sync_status'] ) ) {
			$sync_status = sanitize_text_field( wp_unslash( $_GET['sync_status'] ) );
			$meta_query  = $query->get( 'meta_query' ) ?: array();

			$meta_query[] = array(
				'key'   => 'webinarjam_import_status',
				'value' => $sync_status,
			);

			$query->set( 'meta_query', $meta_query );
		}
	}

	/**
	 * Add bulk actions.
	 *
	 * @since 1.0.0
	 * @param array $actions Existing bulk actions.
	 * @return array Modified bulk actions.
	 */
	public function add_bulk_actions( $actions ) {
		$actions['force_sync_webinar'] = __( 'Force Sync Webinar', 'ma-plugin' );
		$actions['test_import']        = __( 'Test Import', 'ma-plugin' );
		return $actions;
	}

	/**
	 * Handle bulk actions.
	 *
	 * @since 1.0.0
	 * @param string $redirect_to Redirect URL.
	 * @param string $action Action name.
	 * @param array  $post_ids Post IDs.
	 * @return string Modified redirect URL.
	 */
	public function handle_bulk_actions( $redirect_to, $action, $post_ids ) {
		if ( 'force_sync_webinar' === $action ) {
			$synced = 0;

			foreach ( $post_ids as $post_id ) {
				if ( ! ma_is_webinar_course( $post_id ) ) {
					continue;
				}

				$webinar_id = get_field( 'webinarjam_webinar_id', $post_id );
				if ( ! $webinar_id ) {
					continue;
				}

				// Fetch fresh data from API.
				$webinar_data = $this->api_client->get_webinar( $webinar_id );
				if ( ! $webinar_data || isset( $webinar_data['error'] ) ) {
					continue;
				}

				// Transform and update.
				$transformer = new WebinarJam_Transformer();
				$course_data = $transformer::transform_webinar_to_course_data( $webinar_data );

				if ( $course_data && ! is_wp_error( $course_data ) ) {
					\ma_update_webinar_course_from_data( $post_id, $course_data );
					update_post_meta( $post_id, '_webinarjam_last_sync', current_time( 'mysql' ) );
					$synced++;
				}
			}

			$redirect_to = add_query_arg( 'webinar_synced', $synced, $redirect_to );
		}

		if ( 'test_import' === $action ) {
			$imported = 0;

			foreach ( $post_ids as $post_id ) {
				if ( ! ma_is_webinar_course( $post_id ) ) {
					continue;
				}

				$webinar_id = get_field( 'webinarjam_webinar_id', $post_id );
				if ( ! $webinar_id ) {
					continue;
				}

				// Test import via importer.
				$importer = new WebinarJam_Importer( $this->api_client, $this->scheduler );
				$result   = $importer->import( $webinar_id );

				if ( $result ) {
					$imported++;
				}
			}

			$redirect_to = add_query_arg( 'webinar_imported', $imported, $redirect_to );
		}

		return $redirect_to;
	}

	/**
	 * Display bulk action notices.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function bulk_action_notices() {
		if ( ! empty( $_GET['webinar_synced'] ) ) {
			$synced = absint( $_GET['webinar_synced'] );
			?>
			<div class="notice notice-success is-dismissible">
				<p>
					<?php
					printf(
						/* translators: %d: number of webinars synced */
						esc_html( _n( '%d webinar synced successfully.', '%d webinars synced successfully.', $synced, 'ma-plugin' ) ),
						absint( $synced )
					);
					?>
				</p>
			</div>
			<?php
		}

		if ( ! empty( $_GET['webinar_imported'] ) ) {
			$imported = absint( $_GET['webinar_imported'] );
			?>
			<div class="notice notice-success is-dismissible">
				<p>
					<?php
					printf(
						/* translators: %d: number of webinars imported */
						esc_html( _n( '%d webinar imported successfully.', '%d webinars imported successfully.', $imported, 'ma-plugin' ) ),
						absint( $imported )
					);
					?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Add row actions.
	 *
	 * @since 1.0.0
	 * @param array    $actions Existing actions.
	 * @param \WP_Post $post Post object.
	 * @return array Modified actions.
	 */
	public function add_row_actions( $actions, $post ) {
		if ( 'sfwd-courses' !== $post->post_type || ! ma_is_webinar_course( $post->ID ) ) {
			return $actions;
		}

		$webinar_id = get_field( 'webinarjam_webinar_id', $post->ID );
		if ( ! $webinar_id ) {
			return $actions;
		}

		// Add quick sync action.
		$actions['sync_webinar'] = sprintf(
			'<a href="#" class="ma-quick-sync" data-course-id="%d" data-nonce="%s">%s</a>',
			$post->ID,
			wp_create_nonce( 'ma_webinarjam_sync' ),
			__( 'Quick Sync', 'ma-plugin' )
		);

		return $actions;
	}

	/**
	 * AJAX handler for force sync.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_force_sync() {
		check_ajax_referer( 'ma_webinarjam_sync', 'nonce' );

		if ( ! current_user_can( 'edit_courses' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ma-plugin' ) ) );
		}

		$course_id = isset( $_POST['course_id'] ) ? absint( $_POST['course_id'] ) : 0;

		if ( ! $course_id || ! ma_is_webinar_course( $course_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid course.', 'ma-plugin' ) ) );
		}

		$webinar_id = get_field( 'webinarjam_webinar_id', $course_id );
		if ( ! $webinar_id ) {
			wp_send_json_error( array( 'message' => __( 'No webinar ID found.', 'ma-plugin' ) ) );
		}

		// Fetch from API.
		$webinar_data = $this->api_client->get_webinar( $webinar_id );
		if ( ! $webinar_data || isset( $webinar_data['error'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Failed to fetch webinar data.', 'ma-plugin' ) ) );
		}

		// Transform and update.
		$transformer = new WebinarJam_Transformer();
		$course_data = $transformer::transform_webinar_to_course_data( $webinar_data );

		if ( ! $course_data || is_wp_error( $course_data ) ) {
			wp_send_json_error( array( 'message' => __( 'Failed to transform webinar data.', 'ma-plugin' ) ) );
		}

		\ma_update_webinar_course_from_data( $course_id, $course_data );
		update_post_meta( $course_id, '_webinarjam_last_sync', current_time( 'mysql' ) );

		wp_send_json_success( array(
			'message' => __( 'Webinar synced successfully.', 'ma-plugin' ),
		) );
	}

	/**
	 * AJAX handler for test import.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_test_import() {
		check_ajax_referer( 'ma_webinarjam_sync', 'nonce' );

		if ( ! current_user_can( 'edit_courses' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'ma-plugin' ) ) );
		}

		$course_id = isset( $_POST['course_id'] ) ? absint( $_POST['course_id'] ) : 0;

		if ( ! $course_id || ! ma_is_webinar_course( $course_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid course.', 'ma-plugin' ) ) );
		}

		$webinar_id = get_field( 'webinarjam_webinar_id', $course_id );
		if ( ! $webinar_id ) {
			wp_send_json_error( array( 'message' => __( 'No webinar ID found.', 'ma-plugin' ) ) );
		}

		$importer = new WebinarJam_Importer( $this->api_client, $this->scheduler );
		$result   = $importer->import( $webinar_id );

		if ( $result ) {
			wp_send_json_success( array(
				'message' => __( 'Test import completed successfully.', 'ma-plugin' ),
			) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Test import failed.', 'ma-plugin' ) ) );
		}
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @since 1.0.0
	 * @param string $hook Current admin hook.
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( 'edit.php' !== $hook ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'sfwd-courses' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_script(
			'ma-webinarjam-admin',
			MA_PLUGIN_URL . 'assets/js/webinarjam-admin.js',
			array( 'jquery' ),
			MA_PLUGIN_VERSION,
			true
		);

		wp_localize_script(
			'ma-webinarjam-admin',
			'maWebinarJam',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'strings' => array(
					'syncing'    => __( 'Syncing...', 'ma-plugin' ),
					'syncFailed' => __( 'Sync failed. Please try again.', 'ma-plugin' ),
				),
			)
		);
	}

	/**
	 * Render status badge.
	 *
	 * @since 1.0.0
	 * @param string $status Status value.
	 * @return void
	 */
	private function render_status_badge( $status ) {
		$colors = array(
			'upcoming'  => '#2271b1',
			'live'      => '#d63638',
			'completed' => '#50575e',
		);

		$labels = array(
			'upcoming'  => __( 'Upcoming', 'ma-plugin' ),
			'live'      => __( 'Live', 'ma-plugin' ),
			'completed' => __( 'Completed', 'ma-plugin' ),
		);

		$color = isset( $colors[ $status ] ) ? $colors[ $status ] : '#999';
		$label = isset( $labels[ $status ] ) ? $labels[ $status ] : ucfirst( $status );

		printf(
			'<span style="display: inline-block; padding: 3px 8px; background: %s; color: white; border-radius: 3px; font-size: 11px; font-weight: 600;">%s</span>',
			esc_attr( $color ),
			esc_html( $label )
		);
	}

	/**
	 * Get registered count for a course.
	 *
	 * @since 1.0.0
	 * @param int $course_id Course ID.
	 * @return int Registered count.
	 */
	private function get_registered_count( $course_id ) {
		global $wpdb;

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT user_id) 
				FROM {$wpdb->usermeta} 
				WHERE meta_key = %s 
				AND meta_value LIKE %s",
				'registered_webinars',
				'%"' . $course_id . '"%'
			)
		);

		return absint( $count );
	}
}
