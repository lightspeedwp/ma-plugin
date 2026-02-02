<?php
/**
 * Render callback for the digital-magazine-collection block.
 *
 * @package ma_plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'ma_plugin_render_digital_magazine_collection' ) ) {
	function ma_plugin_render_digital_magazine_collection( $attributes, $content, $block ) {
		// Output markup for the CPT1 collection block.
		return '<div class="wp-block-ma_plugin-digital-magazine-collection">' .
			'<p>' . esc_html__( 'CPT1 collection block output.', 'ma-plugin' ) . '</p>' .
		'</div>';
	}
}
