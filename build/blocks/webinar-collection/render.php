<?php
/**
 * Render callback for the webinar-collection block.
 *
 * @package ma_plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'ma_plugin_render_webinar_collection' ) ) {
	function ma_plugin_render_webinar_collection( $attributes, $content, $block ) {
		// Output markup for the CPT1 collection block.
		return '<div class="wp-block-ma_plugin-webinar-collection">' .
			'<p>' . esc_html__( 'CPT1 collection block output.', 'ma-plugin' ) . '</p>' .
		'</div>';
	}
}
