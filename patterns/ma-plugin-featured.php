<?php
/**
 * ExamplePlugin Featured Pattern
 *
 * @package ma_plugin
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'title'       => __( 'Medical Academic Enhancements Featured Items', 'ma-plugin' ),
	'slug'        => 'ma-plugin/item-featured',
	'description' => __( 'Display featured items.', 'ma-plugin' ),
	'categories'  => array( 'ma-plugin' ),
	'keywords'    => array(
		__( 'featured', 'ma-plugin' ),
		__( 'ma-plugin', 'ma-plugin' ),
		__( 'highlight', 'ma-plugin' ),
	),
	'blockTypes'  => array( 'core/group', 'core/query' ),
	'postTypes'   => array( 'item' ),
	'viewportWidth' => 1200,
	'content'     => '<!-- wp:ma-plugin/item-featured {"count":3,"layout":"featured-first"} /-->',
);
