<?php
/**
 * Plugin Name:       Medical Academic Enhancements
 * Plugin URI:        
 * Description:       WordPress content model for Medical Academic CPD platform with custom post types for articles, webinars, magazines, research papers, and learning journeys with comprehensive taxonomy and field support.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.0
 * Requires Plugins:  secure-custom-fields
 * Author:            LightSpeed
 * Author URI:        https://developer.lsdev.biz
 * License:           GPL-2.0-or-later
 * License URI:       
 * Text Domain:       ma-plugin
 * Domain Path:       /languages
 *
 * @package ma_plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'MA_PLUGIN_VERSION', '1.0.0' );
define( 'MA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MA_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Include the Core class.
require_once MA_PLUGIN_DIR . 'inc/class-core.php';

/**
 * Initialise the plugin and return the main instance.
 *
 * @return \ma_plugin\classes\Core Main plugin instance.
 */
function ma_plugin_init() {
       global $ma_plugin;
       if ( null === $ma_plugin ) {
	       $ma_plugin = new \ma_plugin\classes\Core();
       }
       return $ma_plugin;
}

// Initialize the plugin.
ma_plugin_init();
