<?php
/**
 * Medical Academic Enhancements Slider Pattern
 *
 * @package ma_plugin
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'title'       => __( 'Medical Academic Enhancements Slider', 'ma-plugin' ),
	'slug'        => 'ma-plugin/item-slider',
	'description' => __( 'A slider/carousel of items.', 'ma-plugin' ),
	'categories'  => array( 'ma-plugin' ),
	'keywords'    => array(
		__( 'slider', 'ma-plugin' ),
		__( 'ma-plugin', 'ma-plugin' ),
		__( 'carousel', 'ma-plugin' ),
	),
	'blockTypes'  => array( 'core/group', 'core/query' ),
	'postTypes'   => array( 'item' ),
	'viewportWidth' => 1200,
	'content'     => '<!-- wp:ma_plugin/item-slider {"source":"posts","autoplay":true,"autoplaySpeed":5000,"showDots":true,"showArrows":true} /-->',
);
