<?php
/**
 * ExamplePlugin Meta Pattern
 *
 * @package ma_plugin
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'title'       => __( 'Medical Academic Enhancements Meta', 'ma-plugin' ),
	'slug'        => 'ma-plugin/item-meta',
	'description' => __( 'Display item metadata and taxonomies.', 'ma-plugin' ),
	'categories'  => array( 'ma-plugin' ),
	'keywords'    => array(
		__( 'meta', 'ma-plugin' ),
		__( 'ma-plugin', 'ma-plugin' ),
		__( 'taxonomy', 'ma-plugin' ),
	),
	'blockTypes'  => array( 'core/post-template', 'core/group' ),
	'postTypes'   => array( 'item' ),
	'viewportWidth' => 720,
	'content'     => '<!-- wp:group {"className":"ma_plugin-item-meta","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"},"margin":{"top":"var:preset|spacing|40"}},"backgroundColor":"contrast","layout":{"type":"constrained"}} -->\n<div class="wp-block-group ma_plugin-item-meta has-contrast-background-color has-background" style="margin-top:var(--wp--preset--spacing--40);padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30)">\n\t<!-- wp:heading {"level":4} -->\n\t<h4>' . esc_html__( 'Details', 'ma-plugin' ) . '</h4>\n\t<!-- /wp:heading -->\n\n\t<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap"}} -->\n\t<div class="wp-block-group">\n\t\t<!-- wp:post-terms {"term":"category","prefix":"' . esc_attr__( 'Category: ', 'ma-plugin' ) . '"} /-->\n\t</div>\n\t<!-- /wp:group -->\n</div>\n<!-- /wp:group -->',
);
