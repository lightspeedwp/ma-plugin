<?php
/**
 * Render callback for the webinar-slider block.
 *
 * @package ma_plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'ma_plugin_render_webinar_slider' ) ) {
	function ma_plugin_render_webinar_slider( $attributes, $content, $block ) {
		// Output markup for the slider block.
		return '<div class="wp-block-ma_plugin-webinar-slider">' .
			'<p>' . esc_html__( 'Slider block output.', 'ma-plugin' ) . '</p>' .
		'</div>';
	}
}
