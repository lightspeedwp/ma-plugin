<?php
namespace ma_plugin\classes;

/**
 * Core Class initiating the rest of the classes.
 *
 * @package ma_plugin
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 */
class Core {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->load_classes();

		add_action( 'init', array( $this, 'init' ) );
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		// Initialize components.
		new Repeater_Fields();
		new Options();
		new SCF_JSON();
		new SCF_JSON_Validator();
		new Block_Bindings();
		new Block_Styles();
		new Patterns();
	}

	/**
	 * Load the plugin classes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function load_classes() {
		// Include core classes.
		require_once MA_PLUGIN_DIR . 'inc/class-repeater-fields.php';
		require_once MA_PLUGIN_DIR . 'inc/class-options.php';
		require_once MA_PLUGIN_DIR . 'inc/class-scf-json-validator.php';
		require_once MA_PLUGIN_DIR . 'inc/class-scf-json.php';
		require_once MA_PLUGIN_DIR . 'inc/class-block-bindings.php';
		require_once MA_PLUGIN_DIR . 'inc/class-block-styles.php';
		require_once MA_PLUGIN_DIR . 'inc/class-patterns.php';
	}

	/**
	 * Initialize the plugin.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		// Register block category.
		add_filter( 'block_categories_all', array( $this, 'register_block_category' ) );
	}

	/**
	 * Register blocks from the blocks directory.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_blocks() {
		// Auto-register all blocks in build/blocks/ (filtered for flexibility).
		$default_dir = MA_PLUGIN_DIR . 'build/blocks/';
		$blocks_dir = apply_filters( 'example-plugin_blocks_dir', $default_dir );

		if ( ! is_dir( $blocks_dir ) ) {
			return;
		}

		$blocks = glob( $blocks_dir . '*/block.json' );

		if ( ! is_array( $blocks ) ) {
			return;
		}

		foreach ( $blocks as $block_json ) {
			register_block_type( dirname( $block_json ) );
		}
	}

	/**
	 * Enqueue editor assets.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_editor_assets() {
		// Enqueue paragraph prefix script.
		$prefix_script = MA_PLUGIN_DIR . 'build/js/blocks/paragraph-prefix.js';
		
		if ( file_exists( $prefix_script ) ) {
			wp_enqueue_script(
				'ma-plugin-paragraph-prefix',
				MA_PLUGIN_URL . 'build/js/blocks/paragraph-prefix.js',
				array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-compose', 'wp-hooks' ),
				MA_PLUGIN_VERSION,
				true
			);
		}
	}

	/**
	 * Register custom block category.
	 *
	 * @since 1.0.0
	 * @param array $categories Existing block categories.
	 * @return array Modified block categories.
	 */
	public function register_block_category( $categories ) {
		return array_merge(
			array(
				array(
					'slug'  => 'ma-plugin',
					'title' => __( 'Medical Academic Enhancements', 'ma-plugin' ),
					'icon'  => 'admin-generic',
				),
			),
			$categories
		);
	}

	/**
	 * Load plugin text domain for translations.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'ma-plugin',
			false,
			dirname( MA_PLUGIN_BASENAME ) . '/languages'
		);
	}
}
