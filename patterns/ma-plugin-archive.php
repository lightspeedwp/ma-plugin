<?php
/**
 * ExamplePlugin Archive Pattern
 *
 * @package ma_plugin
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'title'       => __( 'Medical Academic Enhancements Archive', 'ma-plugin' ),
	'slug'        => 'ma-plugin/item-archive',
	'description' => __( 'Displays an archive grid of items.', 'ma-plugin' ),
	'categories'  => array( 'ma-plugin' ),
	'keywords'    => array(
		__( 'archive', 'ma-plugin' ),
		__( 'ma-plugin', 'ma-plugin' ),
		__( 'grid', 'ma-plugin' ),
	),
	'blockTypes'  => array( 'core/query' ),
	'postTypes'   => array( 'item' ),
	'templateTypes' => array( 'archive', 'archive-example-plugin' ),
	'viewportWidth' => 1200,
	'content'     => '<!-- wp:query {"queryId":1,"query":{"postType":"item","perPage":12,"inherit":true},"layout":{"type":"constrained"}} -->\n<div class="wp-block-query">\n\t<!-- wp:post-template {"layout":{"type":"grid","columnCount":3}} -->\n\t\t<!-- wp:pattern {"slug":"ma-plugin/item-card"} /-->\n\t<!-- /wp:post-template -->\n\n\t<!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"}} -->\n\t\t<!-- wp:query-pagination-previous /-->\n\t\t<!-- wp:query-pagination-numbers /-->\n\t\t<!-- wp:query-pagination-next /-->\n\t<!-- /wp:query-pagination -->\n\n\t<!-- wp:query-no-results -->\n\t\t<!-- wp:paragraph {"align":"center"} -->\n\t\t<p class="has-text-align-center">' . esc_html__( 'No items found.', 'ma-plugin' ) . '</p>\n\t\t<!-- /wp:paragraph -->\n\t<!-- /wp:query-no-results -->\n</div>\n<!-- /wp:query -->',
);
