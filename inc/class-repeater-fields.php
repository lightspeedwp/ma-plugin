<?php
namespace MaPlugin\classes;

/**
 * Repeater and Flexible Content Fields using Secure Custom Fields.
 *
 * @package example_plugin
 * @see https://wordpress.org/plugins/secure-custom-fields/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Repeater Fields class.
 */
class Repeater_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'acf/init', array( $this, 'register_repeater_fields' ) );
	}

	/**
	 * Register repeater field groups.
	 *
	 * @return void
	 */
	public function register_repeater_fields() {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		// Slider/Gallery Repeater Field Group.
		acf_add_local_field_group(
			array(
				'key'      => 'group_example-plugin_slider',
				'title'    => __( 'Item Slider', 'ma-plugin' ),
				'fields'   => array(
					array(
						'key'          => 'field_example-plugin_slides',
						'label'        => __( 'Slides', 'ma-plugin' ),
						'name'         => 'example-plugin_slides',
						'type'         => 'repeater',
						'instructions' => __( 'Add slides to the slider.', 'ma-plugin' ),
						'min'          => 0,
						'max'          => 20,
						'layout'       => 'block',
						'button_label' => __( 'Add Slide', 'ma-plugin' ),
						'sub_fields'   => array(
							array(
								'key'           => 'field_example-plugin_slide_image',
								'label'         => __( 'Image', 'ma-plugin' ),
								'name'          => 'image',
								'type'          => 'image',
								'return_format' => 'array',
								'preview_size'  => 'medium',
								'library'       => 'all',
							),
							array(
								'key'   => 'field_example-plugin_slide_title',
								'label' => __( 'Title', 'ma-plugin' ),
								'name'  => 'title',
								'type'  => 'text',
							),
							array(
								'key'   => 'field_example-plugin_slide_caption',
								'label' => __( 'Caption', 'ma-plugin' ),
								'name'  => 'caption',
								'type'  => 'textarea',
								'rows'  => 2,
							),
							array(
								'key'   => 'field_example-plugin_slide_link',
								'label' => __( 'Link', 'ma-plugin' ),
								'name'  => 'link',
								'type'  => 'link',
							),
						),
					),
				),
				'location' => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => Post_Types::POST_TYPE,
						),
					),
				),
			)
		);

		// Flexible Content Field Group for sections.
		acf_add_local_field_group(
			array(
				'key'      => 'group_example-plugin_sections',
				'title'    => __( 'Item Sections', 'ma-plugin' ),
				'fields'   => array(
					array(
						'key'          => 'field_example-plugin_sections',
						'label'        => __( 'Content Sections', 'ma-plugin' ),
						'name'         => 'example-plugin_sections',
						'type'         => 'flexible_content',
						'instructions' => __( 'Add content sections.', 'ma-plugin' ),
						'button_label' => __( 'Add Section', 'ma-plugin' ),
						'layouts'      => array(
							'layout_text'    => array(
								'key'        => 'layout_example-plugin_text',
								'name'       => 'text_section',
								'label'      => __( 'Text Section', 'ma-plugin' ),
								'sub_fields' => array(
									array(
										'key'   => 'field_example-plugin_section_heading',
										'label' => __( 'Heading', 'ma-plugin' ),
										'name'  => 'heading',
										'type'  => 'text',
									),
									array(
										'key'   => 'field_example-plugin_section_content',
										'label' => __( 'Content', 'ma-plugin' ),
										'name'  => 'content',
										'type'  => 'wysiwyg',
									),
								),
							),
							'layout_gallery' => array(
								'key'        => 'layout_example-plugin_gallery',
								'name'       => 'gallery_section',
								'label'      => __( 'Gallery Section', 'ma-plugin' ),
								'sub_fields' => array(
									array(
										'key'           => 'field_example-plugin_section_gallery',
										'label'         => __( 'Gallery', 'ma-plugin' ),
										'name'          => 'gallery',
										'type'          => 'gallery',
										'return_format' => 'array',
									),
								),
							),
							'layout_cta'     => array(
								'key'        => 'layout_example-plugin_cta',
								'name'       => 'cta_section',
								'label'      => __( 'Call to Action', 'ma-plugin' ),
								'sub_fields' => array(
									array(
										'key'   => 'field_example-plugin_cta_text',
										'label' => __( 'CTA Text', 'ma-plugin' ),
										'name'  => 'cta_text',
										'type'  => 'text',
									),
									array(
										'key'   => 'field_example-plugin_cta_link',
										'label' => __( 'CTA Link', 'ma-plugin' ),
										'name'  => 'cta_link',
										'type'  => 'link',
									),
								),
							),
						),
					),
				),
				'location' => array(
					array(
						array(
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => Post_Types::POST_TYPE,
						),
					),
				),
			)
		);
	}
}
