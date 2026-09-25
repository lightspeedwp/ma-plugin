<?php
/**
 * WebinarJam Options Page
 *
 * Manages WebinarJam settings and API configuration.
 *
 * @package ma_plugin
 * @since 1.0.0
 */

namespace ma_plugin\classes\WebinarJam;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WebinarJam Options class.
 *
 * Registers options page and fields for WebinarJam API configuration.
 *
 * @since 1.0.0
 */
class WebinarJam_Options {

	/**
	 * Options page slug.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const OPTIONS_PAGE = 'ma_plugin_webinarjam';

	/**
	 * Field group key.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const FIELD_GROUP = 'group_ma_webinarjam_options';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'acf/init', array( $this, 'register_options_page' ) );
		add_action( 'acf/init', array( $this, 'register_options_fields' ) );
		add_action( 'wp_ajax_ma_test_webinarjam_connection', array( $this, 'ajax_test_connection' ) );
		add_action( 'wp_ajax_ma_clear_webinarjam_cache', array( $this, 'ajax_clear_cache' ) );
		add_action( 'wp_ajax_ma_force_webinarjam_sync', array( $this, 'ajax_force_sync' ) );
	}

	/**
	 * Check if Secure Custom Fields is active.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private function is_scf_active() {
		return function_exists( 'acf_add_options_sub_page' );
	}

	/**
	 * Register WebinarJam options sub-page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_options_page() {
		if ( ! $this->is_scf_active() ) {
			return;
		}

		acf_add_options_sub_page(
			array(
				'page_title'  => __( 'WebinarJam Settings', 'ma-plugin' ),
				'menu_title'  => __( 'WebinarJam', 'ma-plugin' ),
				'menu_slug'   => self::OPTIONS_PAGE,
				'parent_slug' => \ma_plugin\classes\Options::OPTIONS_PAGE,
				'capability'  => 'manage_options',
			)
		);
	}

	/**
	 * Register WebinarJam options fields.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_options_fields() {
		if ( ! $this->is_scf_active() ) {
			return;
		}

		acf_add_local_field_group(
			array(
				'key'      => self::FIELD_GROUP,
				'title'    => __( 'WebinarJam API Configuration', 'ma-plugin' ),
				'fields'   => array(
					// API Credentials Tab.
					array(
						'key'   => 'field_wj_credentials_tab',
						'label' => __( 'API Credentials', 'ma-plugin' ),
						'type'  => 'tab',
					),
					array(
						'key'          => 'field_wj_api_key',
						'label'        => __( 'API Key', 'ma-plugin' ),
						'name'         => 'webinarjam_api_key',
						'type'         => 'password',
						'instructions' => __( 'Enter your WebinarJam API key. This is required for the integration to work.', 'ma-plugin' ),
						'required'     => 0,
					),
					array(
						'key'          => 'field_wj_api_url',
						'label'        => __( 'API URL', 'ma-plugin' ),
						'name'         => 'webinarjam_api_url',
						'type'         => 'url',
						'instructions' => __( 'WebinarJam API base URL. Leave default unless instructed otherwise.', 'ma-plugin' ),
						'default_value' => 'https://api.webinarjam.com/webinarjam',
						'required'     => 0,
					),
					array(
						'key'     => 'field_wj_test_connection',
						'label'   => __( 'Connection Test', 'ma-plugin' ),
						'type'    => 'message',
						'message' => $this->get_connection_test_html(),
						'esc_html' => 0,
					),

					// Sync Settings Tab.
					array(
						'key'   => 'field_wj_sync_tab',
						'label' => __( 'Sync Settings', 'ma-plugin' ),
						'type'  => 'tab',
					),
					array(
						'key'           => 'field_wj_sync_frequency',
						'label'         => __( 'Sync Frequency', 'ma-plugin' ),
						'name'          => 'webinarjam_sync_frequency',
						'type'          => 'select',
						'instructions'  => __( 'How often should webinars be synchronized from WebinarJam?', 'ma-plugin' ),
						'choices'       => array(
							'hourly'     => __( 'Hourly', 'ma-plugin' ),
							'twicedaily' => __( 'Twice Daily', 'ma-plugin' ),
							'daily'      => __( 'Daily', 'ma-plugin' ),
						),
						'default_value' => 'daily',
						'required'      => 0,
					),
					array(
						'key'           => 'field_wj_auto_publish',
						'label'         => __( 'Auto-Publish Courses', 'ma-plugin' ),
						'name'          => 'webinarjam_auto_publish',
						'type'          => 'true_false',
						'instructions'  => __( 'Automatically publish imported webinar courses instead of keeping them as drafts.', 'ma-plugin' ),
						'default_value' => 0,
						'ui'            => 1,
					),
					array(
						'key'     => 'field_wj_last_sync',
						'label'   => __( 'Last Sync', 'ma-plugin' ),
						'name'    => 'webinarjam_last_sync',
						'type'    => 'date_time_picker',
						'instructions' => __( 'Last successful synchronization timestamp (automatically updated).', 'ma-plugin' ),
						'readonly' => 1,
						'disabled' => 1,
					),
					array(
						'key'     => 'field_wj_manual_sync',
						'label'   => __( 'Manual Sync', 'ma-plugin' ),
						'type'    => 'message',
						'message' => $this->get_manual_sync_html(),
						'esc_html' => 0,
					),

					// Advanced Settings Tab.
					array(
						'key'   => 'field_wj_advanced_tab',
						'label' => __( 'Advanced', 'ma-plugin' ),
						'type'  => 'tab',
					),
					array(
						'key'           => 'field_wj_debug_mode',
						'label'         => __( 'Debug Mode', 'ma-plugin' ),
						'name'          => 'webinarjam_debug_mode',
						'type'          => 'true_false',
						'instructions'  => __( 'Enable debug logging for WebinarJam integration. Logs will be saved to wp-content/uploads/webinarjam-debug.log', 'ma-plugin' ),
						'default_value' => 0,
						'ui'            => 1,
					),
					array(
						'key'           => 'field_wj_cache_duration',
						'label'         => __( 'Cache Duration (hours)', 'ma-plugin' ),
						'name'          => 'webinarjam_cache_duration',
						'type'          => 'number',
						'instructions'  => __( 'How long to cache API responses (in hours). Default is 12 hours.', 'ma-plugin' ),
						'default_value' => 12,
						'min'           => 1,
						'max'           => 48,
					),
					array(
						'key'     => 'field_wj_clear_cache',
						'label'   => __( 'Clear Cache', 'ma-plugin' ),
						'type'    => 'message',
						'message' => $this->get_clear_cache_html(),
						'esc_html' => 0,
					),

					// Statistics Tab.
					array(
						'key'   => 'field_wj_stats_tab',
						'label' => __( 'Statistics', 'ma-plugin' ),
						'type'  => 'tab',
					),
					array(
						'key'     => 'field_wj_statistics',
						'label'   => __( 'Integration Statistics', 'ma-plugin' ),
						'type'    => 'message',
						'message' => $this->get_statistics_html(),
						'esc_html' => 0,
					),
				),
				'location' => array(
					array(
						array(
							'param'    => 'options_page',
							'operator' => '==',
							'value'    => self::OPTIONS_PAGE,
						),
					),
				),
			)
		);
	}

	/**
	 * Get connection test HTML.
	 *
	 * @since 1.0.0
	 * @return string HTML for connection test button.
	 */
	private function get_connection_test_html() {
		ob_start();
		?>
		<div id="ma-wj-connection-test">
			<button type="button" class="button button-secondary" id="ma-test-wj-connection">
				<?php esc_html_e( 'Test API Connection', 'ma-plugin' ); ?>
			</button>
			<span class="spinner" style="float: none; margin: 0 10px;"></span>
			<div id="ma-wj-test-result" style="margin-top: 10px;"></div>
		</div>
		<script>
		jQuery(document).ready(function($) {
			$('#ma-test-wj-connection').on('click', function() {
				var $button = $(this);
				var $spinner = $button.next('.spinner');
				var $result = $('#ma-wj-test-result');
				
				$button.prop('disabled', true);
				$spinner.addClass('is-active');
				$result.html('');
				
				$.ajax({
					url: ajaxurl,
					method: 'POST',
					data: {
						action: 'ma_test_webinarjam_connection',
						nonce: '<?php echo esc_js( wp_create_nonce( 'ma_wj_test_connection' ) ); ?>'
					},
					success: function(response) {
						if (response.success) {
							$result.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
						} else {
							$result.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
						}
					},
					error: function() {
						$result.html('<div class="notice notice-error inline"><p><?php esc_html_e( 'Connection test failed.', 'ma-plugin' ); ?></p></div>');
					},
					complete: function() {
						$button.prop('disabled', false);
						$spinner.removeClass('is-active');
					}
				});
			});
		});
		</script>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get manual sync HTML.
	 *
	 * @since 1.0.0
	 * @return string HTML for manual sync button.
	 */
	private function get_manual_sync_html() {
		ob_start();
		?>
		<div id="ma-wj-manual-sync">
			<button type="button" class="button button-primary" id="ma-force-wj-sync">
				<?php esc_html_e( 'Force Sync Now', 'ma-plugin' ); ?>
			</button>
			<span class="spinner" style="float: none; margin: 0 10px;"></span>
			<div id="ma-wj-sync-result" style="margin-top: 10px;"></div>
		</div>
		<script>
		jQuery(document).ready(function($) {
			$('#ma-force-wj-sync').on('click', function() {
				if (!confirm('<?php esc_html_e( 'This will sync all webinars from WebinarJam. This may take several minutes. Continue?', 'ma-plugin' ); ?>')) {
					return;
				}
				
				var $button = $(this);
				var $spinner = $button.next('.spinner');
				var $result = $('#ma-wj-sync-result');
				
				$button.prop('disabled', true);
				$spinner.addClass('is-active');
				$result.html('');
				
				$.ajax({
					url: ajaxurl,
					method: 'POST',
					data: {
						action: 'ma_force_webinarjam_sync',
						nonce: '<?php echo esc_js( wp_create_nonce( 'ma_wj_force_sync' ) ); ?>'
					},
					success: function(response) {
						if (response.success) {
							$result.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
						} else {
							$result.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
						}
					},
					error: function() {
						$result.html('<div class="notice notice-error inline"><p><?php esc_html_e( 'Sync failed.', 'ma-plugin' ); ?></p></div>');
					},
					complete: function() {
						$button.prop('disabled', false);
						$spinner.removeClass('is-active');
					}
				});
			});
		});
		</script>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get clear cache HTML.
	 *
	 * @since 1.0.0
	 * @return string HTML for clear cache button.
	 */
	private function get_clear_cache_html() {
		ob_start();
		?>
		<div id="ma-wj-clear-cache">
			<button type="button" class="button button-secondary" id="ma-clear-wj-cache">
				<?php esc_html_e( 'Clear API Cache', 'ma-plugin' ); ?>
			</button>
			<span class="spinner" style="float: none; margin: 0 10px;"></span>
			<div id="ma-wj-cache-result" style="margin-top: 10px;"></div>
		</div>
		<script>
		jQuery(document).ready(function($) {
			$('#ma-clear-wj-cache').on('click', function() {
				var $button = $(this);
				var $spinner = $button.next('.spinner');
				var $result = $('#ma-wj-cache-result');
				
				$button.prop('disabled', true);
				$spinner.addClass('is-active');
				$result.html('');
				
				$.ajax({
					url: ajaxurl,
					method: 'POST',
					data: {
						action: 'ma_clear_webinarjam_cache',
						nonce: '<?php echo esc_js( wp_create_nonce( 'ma_wj_clear_cache' ) ); ?>'
					},
					success: function(response) {
						if (response.success) {
							$result.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
						} else {
							$result.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
						}
					},
					complete: function() {
						$button.prop('disabled', false);
						$spinner.removeClass('is-active');
					}
				});
			});
		});
		</script>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get statistics HTML.
	 *
	 * @since 1.0.0
	 * @return string HTML for statistics display.
	 */
	private function get_statistics_html() {
		// Count webinar courses.
		$webinar_count = wp_count_posts( 'sfwd-courses' );
		
		// Get courses with webinar type.
		$args = array(
			'post_type'      => 'sfwd-courses',
			'posts_per_page' => -1,
			'tax_query'      => array(
				array(
					'taxonomy' => 'course_type',
					'field'    => 'slug',
					'terms'    => 'webinar',
				),
			),
			'fields'         => 'ids',
		);
		$webinar_courses = get_posts( $args );
		$total_webinars  = count( $webinar_courses );

		// Count by status.
		$status_counts = array(
			'upcoming' => 0,
			'live'     => 0,
			'replay'   => 0,
		);

		foreach ( $webinar_courses as $course_id ) {
			$status = ma_get_webinar_status( $course_id );
			if ( isset( $status_counts[ $status ] ) ) {
				$status_counts[ $status ]++;
			}
		}

		$last_sync = get_option( 'webinarjam_last_sync', __( 'Never', 'ma-plugin' ) );
		if ( is_numeric( $last_sync ) ) {
			$last_sync = gmdate( 'Y-m-d H:i:s', $last_sync );
		}

		ob_start();
		?>
		<table class="widefat">
			<tbody>
				<tr>
					<td><strong><?php esc_html_e( 'Total Webinar Courses', 'ma-plugin' ); ?>:</strong></td>
					<td><?php echo esc_html( $total_webinars ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Upcoming Webinars', 'ma-plugin' ); ?>:</strong></td>
					<td><?php echo esc_html( $status_counts['upcoming'] ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Live Webinars', 'ma-plugin' ); ?>:</strong></td>
					<td><?php echo esc_html( $status_counts['live'] ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Replay Webinars', 'ma-plugin' ); ?>:</strong></td>
					<td><?php echo esc_html( $status_counts['replay'] ); ?></td>
				</tr>
				<tr>
					<td><strong><?php esc_html_e( 'Last Successful Sync', 'ma-plugin' ); ?>:</strong></td>
					<td><?php echo esc_html( $last_sync ); ?></td>
				</tr>
			</tbody>
		</table>
		<?php
		return ob_get_clean();
	}

	/**
	 * AJAX handler for testing API connection.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_test_connection() {
		check_ajax_referer( 'ma_wj_test_connection', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ma-plugin' ) ) );
		}

		$api_client = new WebinarJam_API_Client();
		$result     = $api_client->test_connection();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'API connection successful!', 'ma-plugin' ) ) );
	}

	/**
	 * AJAX handler for clearing cache.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_clear_cache() {
		check_ajax_referer( 'ma_wj_clear_cache', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ma-plugin' ) ) );
		}

		$api_client = new WebinarJam_API_Client();
		$api_client->clear_cache();

		wp_send_json_success( array( 'message' => __( 'Cache cleared successfully!', 'ma-plugin' ) ) );
	}

	/**
	 * AJAX handler for forcing sync.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_force_sync() {
		check_ajax_referer( 'ma_wj_force_sync', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ma-plugin' ) ) );
		}

		// Trigger the daily sync action immediately.
		do_action( 'ma_webinarjam_daily_sync' );

		// Update last sync time.
		update_option( 'webinarjam_last_sync', time() );

		wp_send_json_success( array( 'message' => __( 'Sync triggered successfully! Check back in a few minutes.', 'ma-plugin' ) ) );
	}
}
