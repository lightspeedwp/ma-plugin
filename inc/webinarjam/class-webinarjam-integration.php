<?php
/**
 * WebinarJam Integration Main Class
 *
 * Coordinates the WebinarJam integration with LearnDash and The Events Calendar.
 *
 * @package ma_plugin
 * @since 1.0.0
 */

namespace ma_plugin\classes\WebinarJam;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WebinarJam Integration class.
 *
 * Main coordinator for WebinarJam API integration, course synchronization,
 * and event management.
 *
 * @since 1.0.0
 */
class WebinarJam_Integration {

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
	 * Options instance.
	 *
	 * @since 1.0.0
	 * @var WebinarJam_Options
	 */
	private $options;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->init_components();
		$this->setup_hooks();
	}

	/**
	 * Load required dependencies.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function load_dependencies() {
		require_once MA_PLUGIN_DIR . 'inc/webinarjam/helper-functions.php';
		require_once MA_PLUGIN_DIR . 'inc/webinarjam/class-webinarjam-api-client.php';
		require_once MA_PLUGIN_DIR . 'inc/webinarjam/class-webinarjam-scheduler.php';
		require_once MA_PLUGIN_DIR . 'inc/webinarjam/class-webinarjam-options.php';
		require_once MA_PLUGIN_DIR . 'inc/webinarjam/class-webinarjam-taxonomy.php';
	}

	/**
	 * Initialize component instances.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function init_components() {
		$this->api_client = new WebinarJam_API_Client();
		$this->scheduler  = new WebinarJam_Scheduler( $this->api_client );
		$this->options    = new WebinarJam_Options();

		// Initialize taxonomy manager.
		new WebinarJam_Taxonomy();
	}

	/**
	 * Setup WordPress hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function setup_hooks() {
		// Plugin activation/deactivation.
		register_activation_hook( MA_PLUGIN_BASENAME, array( $this, 'activate' ) );
		register_deactivation_hook( MA_PLUGIN_BASENAME, array( $this, 'deactivate' ) );

		// Admin notices.
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}

	/**
	 * Plugin activation hook.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function activate() {
		// Check dependencies.
		if ( ! $this->check_dependencies() ) {
			deactivate_plugins( MA_PLUGIN_BASENAME );
			wp_die(
				esc_html__( 'WebinarJam integration requires LearnDash, The Events Calendar, and The Events Calendar Pro to be active.', 'ma-plugin' ),
				esc_html__( 'Plugin Dependency Error', 'ma-plugin' ),
				array( 'back_link' => true )
			);
		}

		// Schedule initial daily sync.
		$this->scheduler->schedule_daily_sync();

		// Set activation flag.
		update_option( 'ma_webinarjam_activated', time() );
	}

	/**
	 * Plugin deactivation hook.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function deactivate() {
		// Unschedule all events.
		$this->scheduler->unschedule_all();

		// Set deactivation flag.
		update_option( 'ma_webinarjam_deactivated', time() );
	}

	/**
	 * Check if required dependencies are active.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	private function check_dependencies() {
		$dependencies = array(
			'learndash/learndash.php',                      // LearnDash.
			'the-events-calendar/the-events-calendar.php',  // The Events Calendar.
			'events-calendar-pro/events-calendar-pro.php',  // Events Calendar Pro.
		);

		foreach ( $dependencies as $plugin ) {
			if ( ! is_plugin_active( $plugin ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Display admin notices.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function admin_notices() {
		// Check if WebinarJam is configured.
		if ( ! ma_is_webinarjam_configured() ) {
			?>
			<div class="notice notice-warning is-dismissible">
				<p>
					<?php
					printf(
						/* translators: %s: Settings page URL */
						esc_html__( 'WebinarJam integration is not configured. Please add your API credentials in the %s.', 'ma-plugin' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=ma_plugin_webinarjam' ) ) . '">' . esc_html__( 'settings page', 'ma-plugin' ) . '</a>'
					);
					?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Get API Client instance.
	 *
	 * @since 1.0.0
	 * @return WebinarJam_API_Client
	 */
	public function get_api_client() {
		return $this->api_client;
	}

	/**
	 * Get Scheduler instance.
	 *
	 * @since 1.0.0
	 * @return WebinarJam_Scheduler
	 */
	public function get_scheduler() {
		return $this->scheduler;
	}

	/**
	 * Get Options instance.
	 *
	 * @since 1.0.0
	 * @return WebinarJam_Options
	 */
	public function get_options() {
		return $this->options;
	}
}
