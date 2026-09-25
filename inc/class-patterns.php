<?php
namespace ma_plugin\classes;

/**
 * Block Patterns Registration.
 *
 * @package ma_plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Patterns class.
 *
 * @since 1.0.0
 */
class Patterns {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_pattern_category' ) );
		add_action( 'init', array( $this, 'register_patterns' ) );
	}

	/**
	 * Register pattern category.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_pattern_category() {
		register_block_pattern_category(
			'ma-plugin',
			array(
				'label' => __( 'Medical Academic Enhancements', 'ma-plugin' ),
			)
		);
	}

	/**
	 * Register patterns from patterns directory.
	 *
	 * Patterns return associative arrays with properties:
	 * - slug: Pattern identifier (required)
	 * - title: Display name (required)
	 * - description: Pattern description
	 * - categories: Array of category slugs
	 * - keywords: Array of search terms
	 * - viewportWidth: Preview width in pixels
	 * - blockTypes: Array of applicable block types
	 * - postTypes: Array of applicable post types
	 * - content: Block markup HTML (required)
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_patterns() {
		$patterns_dir = MA_PLUGIN_DIR . 'patterns/';

		if ( ! is_dir( $patterns_dir ) ) {
			return;
		}

		$pattern_files = glob( $patterns_dir . '*.php' );

		foreach ( $pattern_files as $pattern_file ) {
			$pattern = require $pattern_file;

			// Skip if pattern doesn't return an array.
			if ( ! is_array( $pattern ) ) {
				continue;
			}

			// Extract pattern slug from array or derive from filename.
			$slug = $pattern['slug'] ?? $this->get_pattern_slug_from_file( $pattern_file );

			// Skip if no slug available.
			if ( empty( $slug ) ) {
				continue;
			}

			// Register the pattern with WordPress.
			register_block_pattern( $slug, $pattern );
		}
	}

	/**
	 * Derive pattern slug from filename.
	 *
	 * Converts 'patterns/ma-plugin-tour-card.php' to 'ma-plugin/tour-card'
	 *
	 * @since 1.0.0
	 * @param string $pattern_file Full path to pattern file.
	 * @return string Pattern slug.
	 */
	private function get_pattern_slug_from_file( $pattern_file ) {
		$filename = basename( $pattern_file, '.php' );

		// Remove 'ma-plugin-' prefix if present, preserving post type and pattern purpose.
		if ( strpos( $filename, 'ma-plugin-' ) === 0 ) {
			$pattern_name = substr( $filename, strlen( 'ma-plugin-' ) );
		} else {
			$pattern_name = $filename;
		}

		// Return namespaced slug in the format 'ma-plugin/{post_type}-{pattern}'.
		return 'ma-plugin/' . $pattern_name;
	}
}
