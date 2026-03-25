=== Medical Academic Enhancements ===
Contributors: 
Donate link: 
Tags: {{tag1}}, {{tag2}}, {{tag3}}, {{tag4}}, {{tag5}}
Requires at least: 6.5
Tested up to: 
Stable tag: 1.0.0
Requires PHP: 8.0
License: GPL-2.0-or-later
License URI: 



== Description ==

WordPress content model for Medical Academic CPD platform with custom post types for articles, webinars, magazines, research papers, and learning journeys with comprehensive taxonomy and field support.

= Key Features =

* **Modern Block Editor Integration** - Built with the latest WordPress block editor standards
* **Multiple Blocks** - Provides a suite of related blocks for comprehensive functionality
* **Customizable Design** - Flexible styling options to match your theme
* **Performance Optimized** - Lightweight and fast-loading
* **Accessibility Ready** - WCAG 2.1 Level AA compliant
* **Translation Ready** - Fully internationalized and ready for translation
* **Developer Friendly** - Clean, well-documented code following WordPress coding standards

= Use Cases =

{{use_case_1}}

{{use_case_2}}

{{use_case_3}}

= Block Features =



= Requirements =

* WordPress 6.5 or higher
* PHP 8.0 or higher
* Modern browser with JavaScript enabled

=== Medical Academic Enhancements ===
Contributors: LightSpeed
Donate link: https://github.com/LightSpeed//donate
Tags: blocks, gutenberg, , Medical Academic Enhancements
Requires at least: 6.4
Tested up to: 6.7
Requires PHP: 7.4
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

== Description ==

Medical Academic Enhancements is a multi-block plugin scaffold for WordPress. It provides a starting point for building custom blocks, patterns, and templates. All code is namespaced and uses mustache placeholders for code generation.

== Installation ==

1. Download the plugin ZIP from [GitHub](https://github.com/LightSpeed//releases).
2. Upload to your WordPress site via Plugins > Add New > Upload Plugin.
3. Activate the plugin.

== Frequently Asked Questions ==

= Where can I find documentation? =-

See [USAGE.md](https://github.com/LightSpeed//blob/main/USAGE.md) for usage instructions.

= How do I report a bug? =-

Open an issue at [GitHub Issues](https://github.com/LightSpeed//issues).

= How do I contribute? =-

See [CONTRIBUTING.md](https://github.com/LightSpeed//blob/main/CONTRIBUTING.md).

== Changelog ==

= 1.0.0 =-
* Initial release.
= Configuration =

The plugin works out of the box with default settings. You can customize the blocks' appearance and behavior using the block settings panel in the editor.

== Frequently Asked Questions ==

= Is this plugin free? =

Yes! Medical Academic Enhancements is completely free and open source under the GPL-2.0-or-later license.

= Does this work with any theme? =

Yes! The plugin is designed to work with any properly coded WordPress theme that supports the block editor.

= Can I use this with the Classic Editor? =

This plugin requires the block editor (Gutenberg). It will not work with the Classic Editor plugin.

= Is this plugin translation ready? =

Yes! The plugin is fully internationalized and ready for translation. Translation files can be added to the `/languages` directory.

= How do I customize the blocks' appearance? =

You can customize the blocks using:
1. The block settings panel in the editor sidebar
2. Theme.json settings in your theme
3. Custom CSS in your theme's stylesheet
4. Block styles and variations

= Does this plugin affect my site's performance? =

No! The plugin is lightweight and optimized for performance. It only loads necessary assets when the blocks are used.

= Where can I report bugs or request features? =

Please create an issue on our [GitHub repository](/issues) or visit our [support forum]().

= Can I contribute to this plugin? =

Absolutely! We welcome contributions. Please see our [contributing guidelines](/blob/main/CONTRIBUTING.md).

= Is GDPR compliance required for this plugin? =

The plugin itself does not collect, process, or store any personal data, so no additional GDPR compliance measures are needed for the plugin itself. However, ensure your site's overall GDPR compliance based on your specific use case.

== Screenshots ==

1. Block in the editor - Shows the block interface in the WordPress block editor
2. Block settings panel - Configuration options available in the sidebar
3. Frontend display - How the block appears on your live site
4. Block variations - Different style options available
5. Multiple blocks - Example of using multiple instances together

== Changelog ==

= 1.0.0 =
Release Date: 

**New Features:**
* {{new_feature_1}}
* {{new_feature_2}}

**Improvements:**
* {{improvement_1}}
* {{improvement_2}}

**Bug Fixes:**
* {{bug_fix_1}}
* {{bug_fix_2}}

**Developer Notes:**
* {{dev_note_1}}
* {{dev_note_2}}

For detailed changelog history, see [CHANGELOG.md](/blob/main/CHANGELOG.md)

== Upgrade Notice ==

= 1.0.0 =


== Additional Information ==

= Technical Details =

* **Block Names:** `ma_plugin/{{block-slug-1}}`, `ma_plugin/{{block-slug-2}}`
* **Block Category:** 
* **Supports:** 
* **Text Domain:** ma-plugin
* **Domain Path:** /languages

= Links =

* [Plugin Homepage]()
* [Documentation]()
* [GitHub Repository]()
* [Support Forum]()
* [Changelog](/blob/main/CHANGELOG.md)
* [Report Issue](/issues)

= Credits =

Developed and maintained by [LightSpeed](https://developer.lsdev.biz).

Special thanks to all [contributors](/graphs/contributors).

== Development ==

= Building from Source =

```bash
# Install dependencies
npm install
composer install

# Start development
npm run start

# Build for production
npm run build

# Run tests
npm run test
composer run test

# Create distribution ZIP
npm run plugin-zip
```

= Developer Hooks =

**Filters:**

* `ma_plugin_block_attributes` - Modify block attributes
* `ma_plugin_block_output` - Filter block output HTML
* `ma_plugin_block_settings` - Modify block settings

**Actions:**

* `ma_plugin_before_block_render` - Fires before block renders
* `ma_plugin_after_block_render` - Fires after block renders
* `ma_plugin_enqueue_assets` - Hook for custom asset enqueuing

For full API documentation, visit our [developer documentation](/developers).

= Testing =

We maintain comprehensive test coverage:

* **PHP Tests:** PHPUnit for PHP code
* **JavaScript Tests:** Jest for JavaScript/React code
* **E2E Tests:** Playwright for end-to-end testing
* **Code Quality:** PHPCS, ESLint, and Stylelint

= Browser Compatibility =

* Chrome (latest 2 versions)
* Firefox (latest 2 versions)
* Safari (latest 2 versions)
* Edge (latest 2 versions)

= Accessibility =

This plugin aims to meet WCAG 2.1 Level AA standards. If you encounter any accessibility issues, please [report them](/issues).
