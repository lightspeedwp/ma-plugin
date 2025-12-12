<?php
namespace MaPlugin\classes;

/**
 * Options Pages Registration using Secure Custom Fields.
 *
 * Creates global settings pages for site-wide configuration that is not
 * tied to individual posts, pages, or taxonomies.
 *
 * @package example_plugin
 * @see https://github.com/WordPress/secure-custom-fields/blob/trunk/docs/tutorials/first-options-page.md
 * @see https://github.com/WordPress/secure-custom-fields/blob/trunk/docs/code-reference/api/index.md
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Options class.
 *
 * Registers options pages and their associated field groups using SCF.
 */
class Options {

	/**
	 * Main options page slug.
	 *
	 * @var string
	 */
	const OPTIONS_PAGE = 'example-plugin-settings';

	/**
	 * Field group key for main settings.
	 *
	 * @var string
	 */
	const FIELD_GROUP = 'group_example-plugin_options';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'acf/init', array( $this, 'register_options_pages' ) );
		add_action( 'acf/init', array( $this, 'register_options_fields' ) );
	}

	/**
	 * Check if Secure Custom Fields is active.
	 *
	 * @return bool
	 */
	public function is_scf_active() {
		return function_exists( 'acf_add_options_page' );
	}

	/**
	 * Register options pages.
	 *
	 * Creates the main settings page and any sub-pages.
	 *
	 * @see https://github.com/WordPress/secure-custom-fields/blob/trunk/docs/tutorials/first-options-page.md
	 * @return void
	 */
	public function register_options_pages() {
		if ( ! $this->is_scf_active() ) {
			return;
		}

		// Main options page.
		acf_add_options_page(
			array(
				'page_title'      => __( 'MA Plugin Settings', 'ma-plugin' ),
				'menu_title'      => __( 'MA Plugin', 'ma-plugin' ),
				'menu_slug'       => self::OPTIONS_PAGE,
				'capability'      => 'manage_options',
				'icon_url'        => 'dashicons-admin-generic',
				'redirect'        => false,
				'position'        => 30,
				'update_button'   => __( 'Save Settings', 'ma-plugin' ),
				'updated_message' => __( 'Settings saved.', 'ma-plugin' ),
				'autoload'        => true,
			)
		);

		// General settings sub-page.
		acf_add_options_sub_page(
			array(
				'page_title'  => __( 'General Settings', 'ma-plugin' ),
				'menu_title'  => __( 'General', 'ma-plugin' ),
				'parent_slug' => self::OPTIONS_PAGE,
				'menu_slug'   => self::OPTIONS_PAGE . '-general',
				'capability'  => 'manage_options',
			)
		);

		// Display settings sub-page.
		acf_add_options_sub_page(
			array(
				'page_title'  => __( 'Display Settings', 'ma-plugin' ),
				'menu_title'  => __( 'Display', 'ma-plugin' ),
				'parent_slug' => self::OPTIONS_PAGE,
				'menu_slug'   => self::OPTIONS_PAGE . '-display',
				'capability'  => 'manage_options',
			)
		);

		// API settings sub-page.
		acf_add_options_sub_page(
			array(
				'page_title'  => __( 'API Settings', 'ma-plugin' ),
				'menu_title'  => __( 'API', 'ma-plugin' ),
				'parent_slug' => self::OPTIONS_PAGE,
				'menu_slug'   => self::OPTIONS_PAGE . '-api',
				'capability'  => 'manage_options',
			)
		);
	}

	/**
	 * Register options page fields.
	 *
	 * @return void
	 */
	public function register_options_fields() {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		// General Settings fields.
		acf_add_local_field_group(
			array(
				'key'             => self::FIELD_GROUP . '_general',
				'title'           => __( 'General Settings', 'ma-plugin' ),
				'fields'          => array(
					// Tab: Branding.
					array(
						'key'   => 'field_example-plugin_tab_branding',
						'label' => __( 'Branding', 'ma-plugin' ),
						'type'  => 'tab',
					),
					array(
						'key'           => 'field_example-plugin_logo',
						'label'         => __( 'Logo', 'ma-plugin' ),
						'name'          => 'example-plugin_logo',
						'type'          => 'image',
						'return_format' => 'array',
						'preview_size'  => 'medium',
						'library'       => 'all',
						'instructions'  => __( 'Upload your site logo.', 'ma-plugin' ),
					),
					array(
						'key'          => 'field_example-plugin_company_name',
						'label'        => __( 'Company Name', 'ma-plugin' ),
						'name'         => 'example-plugin_company_name',
						'type'         => 'text',
						'instructions' => __( 'Enter your company or organisation name.', 'ma-plugin' ),
					),
					array(
						'key'          => 'field_example-plugin_tagline',
						'label'        => __( 'Tagline', 'ma-plugin' ),
						'name'         => 'example-plugin_tagline',
						'type'         => 'text',
						'instructions' => __( 'Enter a short tagline or slogan.', 'ma-plugin' ),
					),
					// Tab: Contact.
					array(
						'key'   => 'field_example-plugin_tab_contact',
						'label' => __( 'Contact', 'ma-plugin' ),
						'type'  => 'tab',
					),
					array(
						'key'          => 'field_example-plugin_email',
						'label'        => __( 'Email Address', 'ma-plugin' ),
						'name'         => 'example-plugin_email',
						'type'         => 'email',
						'instructions' => __( 'Primary contact email address.', 'ma-plugin' ),
					),
					array(
						'key'          => 'field_example-plugin_phone',
						'label'        => __( 'Phone Number', 'ma-plugin' ),
						'name'         => 'example-plugin_phone',
						'type'         => 'text',
						'instructions' => __( 'Primary contact phone number.', 'ma-plugin' ),
					),
					array(
						'key'          => 'field_example-plugin_address',
						'label'        => __( 'Address', 'ma-plugin' ),
						'name'         => 'example-plugin_address',
						'type'         => 'textarea',
						'rows'         => 3,
						'instructions' => __( 'Physical address or mailing address.', 'ma-plugin' ),
					),
					// Tab: Social Media.
					array(
						'key'   => 'field_example-plugin_tab_social',
						'label' => __( 'Social Media', 'ma-plugin' ),
						'type'  => 'tab',
					),
					array(
						'key'          => 'field_example-plugin_social_links',
						'label'        => __( 'Social Links', 'ma-plugin' ),
						'name'         => 'example-plugin_social_links',
						'type'         => 'repeater',
						'layout'       => 'table',
						'button_label' => __( 'Add Social Link', 'ma-plugin' ),
						'sub_fields'   => array(
							array(
								'key'     => 'field_example-plugin_social_platform',
								'label'   => __( 'Platform', 'ma-plugin' ),
								'name'    => 'platform',
								'type'    => 'select',
								'choices' => array(
									'facebook'  => __( 'Facebook', 'ma-plugin' ),
									'twitter'   => __( 'Twitter/X', 'ma-plugin' ),
									'instagram' => __( 'Instagram', 'ma-plugin' ),
									'linkedin'  => __( 'LinkedIn', 'ma-plugin' ),
									'youtube'   => __( 'YouTube', 'ma-plugin' ),
									'tiktok'    => __( 'TikTok', 'ma-plugin' ),
									'other'     => __( 'Other', 'ma-plugin' ),
								),
							),
							array(
								'key'   => 'field_example-plugin_social_url',
								'label' => __( 'URL', 'ma-plugin' ),
								'name'  => 'url',
								'type'  => 'url',
							),
						),
					),
				),
				'location'        => array(
					array(
						array(
							'param'    => 'options_page',
							'operator' => '==',
							'value'    => self::OPTIONS_PAGE . '-general',
						),
					),
				),
				'menu_order'      => 0,
				'position'        => 'normal',
				'style'           => 'default',
				'label_placement' => 'top',
			)
		);

		// Display Settings fields.
		acf_add_local_field_group(
			array(
				'key'      => self::FIELD_GROUP . '_display',
				'title'    => __( 'Display Settings', 'ma-plugin' ),
				'fields'   => array(
					array(
						'key'          => 'field_example-plugin_items_per_page',
						'label'        => __( 'Items Per Page', 'ma-plugin' ),
						'name'         => 'example-plugin_items_per_page',
						'type'         => 'number',
						'default'      => 12,
						'min'          => 1,
						'max'          => 100,
						'instructions' => __( 'Number of items to display per page in archive views.', 'ma-plugin' ),
					),
					array(
						'key'          => 'field_example-plugin_layout',
						'label'        => __( 'Archive Layout', 'ma-plugin' ),
						'name'         => 'example-plugin_layout',
						'type'         => 'button_group',
						'choices'      => array(
							'grid'    => __( 'Grid', 'ma-plugin' ),
							'list'    => __( 'List', 'ma-plugin' ),
							'masonry' => __( 'Masonry', 'ma-plugin' ),
						),
						'default'      => 'grid',
						'instructions' => __( 'Choose the default layout for archive pages.', 'ma-plugin' ),
					),
					array(
						'key'          => 'field_example-plugin_show_sidebar',
						'label'        => __( 'Show Sidebar', 'ma-plugin' ),
						'name'         => 'example-plugin_show_sidebar',
						'type'         => 'true_false',
						'ui'           => 1,
						'default'      => 1,
						'instructions' => __( 'Display sidebar on archive and single views.', 'ma-plugin' ),
					),
					array(
						'key'          => 'field_example-plugin_featured_image_size',
						'label'        => __( 'Featured Image Size', 'ma-plugin' ),
						'name'         => 'example-plugin_featured_image_size',
						'type'         => 'select',
						'choices'      => array(
							'thumbnail' => __( 'Thumbnail (150x150)', 'ma-plugin' ),
							'medium'    => __( 'Medium (300x300)', 'ma-plugin' ),
							'large'     => __( 'Large (1024x1024)', 'ma-plugin' ),
							'full'      => __( 'Full Size', 'ma-plugin' ),
						),
						'default'      => 'medium',
						'instructions' => __( 'Image size for featured images in listings.', 'ma-plugin' ),
					),
				),
				'location' => array(
					array(
						array(
							'param'    => 'options_page',
							'operator' => '==',
							'value'    => self::OPTIONS_PAGE . '-display',
						),
					),
				),
			)
		);

		// API Settings fields.
		acf_add_local_field_group(
			array(
				'key'      => self::FIELD_GROUP . '_api',
				'title'    => __( 'API Settings', 'ma-plugin' ),
				'fields'   => array(
					array(
						'key'          => 'field_example-plugin_api_key',
						'label'        => __( 'API Key', 'ma-plugin' ),
						'name'         => 'example-plugin_api_key',
						'type'         => 'text',
						'instructions' => __( 'Enter your API key for external integrations.', 'ma-plugin' ),
					),
					array(
						'key'          => 'field_example-plugin_api_secret',
						'label'        => __( 'API Secret', 'ma-plugin' ),
						'name'         => 'example-plugin_api_secret',
						'type'         => 'password',
						'instructions' => __( 'Enter your API secret (stored securely).', 'ma-plugin' ),
					),
					array(
						'key'          => 'field_example-plugin_api_endpoint',
						'label'        => __( 'API Endpoint', 'ma-plugin' ),
						'name'         => 'example-plugin_api_endpoint',
						'type'         => 'url',
						'instructions' => __( 'Custom API endpoint URL.', 'ma-plugin' ),
					),
					array(
						'key'          => 'field_example-plugin_enable_api',
						'label'        => __( 'Enable API', 'ma-plugin' ),
						'name'         => 'example-plugin_enable_api',
						'type'         => 'true_false',
						'ui'           => 1,
						'default'      => 0,
						'instructions' => __( 'Enable external API integration.', 'ma-plugin' ),
					),
					array(
						'key'          => 'field_example-plugin_api_cache_duration',
						'label'        => __( 'Cache Duration', 'ma-plugin' ),
						'name'         => 'example-plugin_api_cache_duration',
						'type'         => 'number',
						'default'      => 3600,
						'min'          => 0,
						'append'       => __( 'seconds', 'ma-plugin' ),
						'instructions' => __( 'How long to cache API responses (0 to disable).', 'ma-plugin' ),
					),
				),
				'location' => array(
					array(
						array(
							'param'    => 'options_page',
							'operator' => '==',
							'value'    => self::OPTIONS_PAGE . '-api',
						),
					),
				),
			)
		);
	}

	/**
	 * Get an option value.
	 *
	 * Helper method to retrieve option values using get_field().
	 *
	 * @param string $field_name The field name without prefix.
	 * @param mixed  $default    Default value if field is empty.
	 * @return mixed The field value or default.
	 */
	public static function get_option( $field_name, $default = '' ) {
		if ( ! function_exists( 'get_field' ) ) {
			return $default;
		}

		$value = get_field( 'example-plugin_' . $field_name, 'option' );

		return $value ? $value : $default;
	}

	/**
	 * Update an option value.
	 *
	 * Helper method to update option values using update_field().
	 *
	 * @param string $field_name The field name without prefix.
	 * @param mixed  $value      The value to save.
	 * @return bool True on success, false on failure.
	 */
	public static function update_option( $field_name, $value ) {
		if ( ! function_exists( 'update_field' ) ) {
			return false;
		}

		return update_field( 'example-plugin_' . $field_name, $value, 'option' );
	}
}
