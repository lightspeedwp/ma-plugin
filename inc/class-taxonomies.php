<?php
namespace MaPlugin\classes;

/**
 * Custom Taxonomy Registration.
 *
 * @package {{namespace|lowerCase}}
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Taxonomies class.
 */
class Taxonomies {

	/**
	 * Taxonomy slug.
	 *
	 * @var string
	 */
	const TAXONOMY = '{{slug}}_category';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_taxonomies' ) );
	}

	/**
	 * Register custom taxonomies.
	 *
	 * @return void
	 */
	public function register_taxonomies() {
		$labels = array(
			'name'                       => _x( 'Categories', 'Taxonomy general name', 'ma-plugin' ),
			'singular_name'              => _x( 'Category', 'Taxonomy singular name', 'ma-plugin' ),
			'search_items'               => __( 'Search Categories', 'ma-plugin' ),
			'popular_items'              => __( 'Popular Categories', 'ma-plugin' ),
			'all_items'                  => __( 'All Categories', 'ma-plugin' ),
			'edit_item'                  => __( 'Edit Category', 'ma-plugin' ),
			'update_item'                => __( 'Update Category', 'ma-plugin' ),
			'add_new_item'               => __( 'Add New Category', 'ma-plugin' ),
			'new_item_name'              => __( 'New Category Name', 'ma-plugin' ),
			'separate_items_with_commas' => __( 'Separate categories with commas', 'ma-plugin' ),
			'add_or_remove_items'        => __( 'Add or remove categories', 'ma-plugin' ),
			'choose_from_most_used'      => __( 'Choose from the most used categories', 'ma-plugin' ),
			'not_found'                  => __( 'No categories found.', 'ma-plugin' ),
			'menu_name'                  => __( 'Categories', 'ma-plugin' ),
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_in_rest'      => true, // Required for block editor.
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => '{{slug}}-category' ),
		);

		register_taxonomy(
			self::TAXONOMY,
			Post_Types::POST_TYPE,
			$args
		);
	}
}
