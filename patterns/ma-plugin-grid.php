<?php
/**
 * ExamplePlugin Grid Pattern
 *
 * @package ma_plugin
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'title'       => __( 'Medical Academic Enhancements Grid', 'ma-plugin' ),
	'slug'        => 'ma-plugin/item-grid',
	'description' => __( 'A grid of items.', 'ma-plugin' ),
	'categories'  => array( 'ma-plugin' ),
	'keywords'    => array(
		__( 'grid', 'ma-plugin' ),
		__( 'ma-plugin', 'ma-plugin' ),
		__( 'collection', 'ma-plugin' ),
	),
	'blockTypes'  => array( 'core/group', 'core/query' ),
	'postTypes'   => array( 'item' ),
	'viewportWidth' => 1200,
	'content'     => '<!-- wp:ma-plugin/item-collection {"layout":"grid","columns":3,"query":{"postType":"item","perPage":6}} /-->',
);
