<?php
/**
 * Custom Post Type Registration.
 *
 * @package example_plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post Types class.
 */
class Post_Types {

	/**
	 * Post type slug.
	 *
	 * @var string
	 */
	const POST_TYPE = 'ma_plugin';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_types' ) );
	}

	/**
	 * Register custom post types.
	 *
	 * @return void
	 */
	public function register_post_types() {
		$labels = array(
			'name'                  => _x( 'Items', 'Post type general name', 'ma-plugin' ),
			'singular_name'         => _x( 'Item', 'Post type singular name', 'ma-plugin' ),
			'menu_name'             => _x( 'Items', 'Admin Menu text', 'ma-plugin' ),
			'add_new'               => __( 'Add New', 'ma-plugin' ),
			'add_new_item'          => __( 'Add New Item', 'ma-plugin' ),
			'edit_item'             => __( 'Edit Item', 'ma-plugin' ),
			'new_item'              => __( 'New Item', 'ma-plugin' ),
			'view_item'             => __( 'View Item', 'ma-plugin' ),
			'view_items'            => __( 'View Items', 'ma-plugin' ),
			'search_items'          => __( 'Search Items', 'ma-plugin' ),
			'not_found'             => __( 'No items found.', 'ma-plugin' ),
			'not_found_in_trash'    => __( 'No items found in Trash.', 'ma-plugin' ),
			'all_items'             => __( 'All Items', 'ma-plugin' ),
			'archives'              => __( 'Item Archives', 'ma-plugin' ),
			'attributes'            => __( 'Item Attributes', 'ma-plugin' ),
			'insert_into_item'      => __( 'Insert into item', 'ma-plugin' ),
			'uploaded_to_this_item' => __( 'Uploaded to this item', 'ma-plugin' ),
			'filter_items_list'     => __( 'Filter items list', 'ma-plugin' ),
			'items_list_navigation' => __( 'Items list navigation', 'ma-plugin' ),
			'items_list'            => __( 'Items list', 'ma-plugin' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_rest'       => true, // Required for block editor.
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'ma_plugin' ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 20,
			'menu_icon'          => 'dashicons-admin-generic',
			'supports'           => array(
				'title',
				'editor',
				'author',
				'thumbnail',
				'excerpt',
				'custom-fields',
				'revisions',
			),
			'template'           => array(
				array( 'example_plugin/example-plugin-single' ),
			),
			'template_lock'      => false,
		);

		register_post_type( self::POST_TYPE, $args );
	}
}
