<?php
namespace ma_plugin\classes;

/**
 * WooCommerce My Account Customizations.
 *
 * @package ma_plugin
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * My Account class.
 */
class My_Account {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Rename and hide menu items.
		add_filter( 'woocommerce_account_menu_items', array( $this, 'customize_menu_items' ), 10, 1 );

		// Register custom endpoints.
		add_action( 'init', array( $this, 'register_custom_endpoints' ) );

		// Add content to custom endpoints.
		add_action( 'woocommerce_account_cpd-points_endpoint', array( $this, 'cpd_points_content' ) );
		add_action( 'woocommerce_account_multi-page-report_endpoint', array( $this, 'multi_page_report_content' ) );

		// Flush rewrite rules on plugin activation (use WordPress activation hooks).
		add_action( 'activated_plugin', array( $this, 'flush_rewrite_rules' ) );
	}

	/**
	 * Customize WooCommerce My Account menu items.
	 *
	 * @since 1.0.0
	 * @param array $items Existing menu items.
	 * @return array Modified menu items.
	 */
	public function customize_menu_items( $items ) {
		// Remove unwanted endpoints.
		unset( $items['edit-address'] );
		unset( $items['downloads'] );
		unset( $items['orders'] );

		// Rename existing endpoints.
		if ( isset( $items['dashboard'] ) ) {
			$items['dashboard'] = __( 'Dashboard Overview', 'ma-plugin' );
		}

		if ( isset( $items['subscriptions'] ) ) {
			$items['subscriptions'] = __( 'Subscriptions & Payments', 'ma-plugin' );
		}

		if ( isset( $items['edit-account'] ) ) {
			$items['edit-account'] = __( 'Profile & Professional Details', 'ma-plugin' );
		}

		// Add custom endpoints.
		$custom_items = array(
			'cpd-points'        => __( 'CPD Points and Certificates', 'ma-plugin' ),
			'multi-page-report' => __( 'Multi Page Report', 'ma-plugin' ),
		);

		// Insert custom items before logout.
		$logout = isset( $items['customer-logout'] ) ? array( 'customer-logout' => $items['customer-logout'] ) : array();
		unset( $items['customer-logout'] );

		return array_merge( $items, $custom_items, $logout );
	}

	/**
	 * Register custom endpoints.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_custom_endpoints() {
		add_rewrite_endpoint( 'cpd-points', EP_ROOT | EP_PAGES );
		add_rewrite_endpoint( 'multi-page-report', EP_ROOT | EP_PAGES );
	}

	/**
	 * Display content for CPD Points and Certificates endpoint.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function cpd_points_content() {
		?>
		<div class="woocommerce-cpd-points">
			<h2><?php esc_html_e( 'CPD Points and Certificates', 'ma-plugin' ); ?></h2>
			<?php
			/**
			 * Action hook to add content to CPD Points and Certificates page.
			 *
			 * @since 1.0.0
			 */
			do_action( 'ma_plugin_cpd_points_content' );
			?>
		</div>
		<?php
	}

	/**
	 * Display content for Multi Page Report endpoint.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function multi_page_report_content() {
		?>
		<div class="woocommerce-multi-page-report">
			<h2><?php esc_html_e( 'Multi Page Report', 'ma-plugin' ); ?></h2>
			<?php
			/**
			 * Action hook to add content to Multi Page Report page.
			 *
			 * @since 1.0.0
			 */
			do_action( 'ma_plugin_multi_page_report_content' );
			?>
		</div>
		<?php
	}

	/**
	 * Flush rewrite rules when plugin is activated.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function flush_rewrite_rules() {
		$this->register_custom_endpoints();
		flush_rewrite_rules();
	}
}
