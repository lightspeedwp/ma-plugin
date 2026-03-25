# Changelog

All notable changes to the Multi-Block Plugin Scaffold will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

#### WebinarJam Integration v1.0.0

Complete WebinarJam API v2 integration with 21 core classes providing automated webinar sync, course creation, and user management.

**Core Features:**
- **API Integration**: RESTful API client with authentication, caching (12-hour default), error handling, and rate limiting
- **Data Synchronization**: Automated sync of webinars, schedules, and registration data (configurable: daily/hourly/twice-daily/weekly)
- **LearnDash Integration**: Automatic course creation with customizable field mapping
- **Events Calendar Integration**: Automatic event creation with webinar schedules and metadata
- **Frontend Features**: Webinar registration forms, user dashboard, and webinar listings
- **Admin Interface**: Settings page with API configuration, field mapping, sync controls, and manual operations

**Performance & Optimization:**
- Database indexing: 4 custom indexes on webinar metadata for high-performance queries
- Cache management: Intelligent invalidation and warming strategies
- Batch processing: Memory-efficient processing (50 courses per batch) to prevent timeouts
- Query optimization: Optimized database queries with index usage

**Security Hardening:**
- AES-256-CBC encryption for API credentials at rest
- Recursive input sanitization of all API responses
- Email validation with disposable domain blocking (mailinator, guerrillamail, etc.)
- HTTPS-only URL enforcement
- AJAX nonce verification for all admin operations
- Rate limiting: 60 requests/hour per user
- Security headers: X-Frame-Options, X-XSS-Protection, X-Content-Type-Options, Referrer-Policy

**Error Handling & Logging:**
- Centralized error handler with automatic retries and exponential backoff
- Database-backed logging with admin interface
- 7-level logging (emergency/alert/critical/error/warning/notice/info/debug)
- Admin log viewer with filtering and search

**Health Monitoring:**
- Proactive system health checks with email alerts
- API connectivity monitoring
- Sync health tracking (48-hour threshold)
- Cron job verification (daily_sync, status_check, attendance_check)
- Database health checks (orphaned metadata detection, threshold: 100)
- Cache health monitoring (expired transient tracking, threshold: 50)
- Error rate tracking (10 errors/day threshold)
- API response time tracking (min/max/avg per endpoint)
- Public health check endpoint with key-based authentication

**Deployment & Lifecycle:**
- Activation handler with dependency checks (LearnDash, Events Calendar Pro, ACF/SCF)
- Automatic database table/index creation on activation
- Cron job scheduling (5 events: daily_sync, import, status_check, attendance_check, cleanup)
- Default settings configuration
- Clean deactivation (unschedule all cron events)
- Optional complete data removal on uninstall (options, transients, tables, metadata)

**User Features:**
- Status tracking: Real-time webinar status updates (upcoming/live/replay/completed)
- Attendance tracking: Automatic attendance recording via webhooks
- User dashboard: Personal webinar schedule and registration management
- Course integration: Seamless LearnDash enrollment from webinar registration
- Taxonomy support: Custom taxonomies for webinar categorization

**Classes (21 total):**
1. `WebinarJam_Integration` - Main coordinator
2. `WebinarJam_API_Client` - API communication with caching
3. `WebinarJam_Scheduler` - WP-Cron management
4. `WebinarJam_Sync_Handler` - Data synchronization
5. `WebinarJam_Importer` - Bulk import
6. `WebinarJam_Status_Handler` - Status tracking
7. `WebinarJam_Attendance_Handler` - Attendance recording
8. `WebinarJam_Taxonomy` - Custom taxonomies
9. `WebinarJam_Data_Transformer` - Data mapping
10. `WebinarJam_Frontend` - Frontend forms/displays
11. `WebinarJam_Course_Integration` - LearnDash course creation
12. `WebinarJam_User_Dashboard` - User dashboard
13. `WebinarJam_Options` - Settings management
14. `WebinarJam_Admin` - Admin interface
15. `WebinarJam_Logger` - Database logging
16. `WebinarJam_Error_Handler` - Error handling
17. `WebinarJam_Database_Optimizer` - Performance optimization
18. `WebinarJam_Cache_Manager` - Cache management
19. `WebinarJam_Security_Manager` - Security hardening
20. `WebinarJam_Activation_Handler` - Lifecycle management
21. `WebinarJam_Health_Monitor` - System monitoring

**Hooks & Filters (40+ total):**
- Actions: `ma_webinarjam_before_sync_all`, `ma_webinarjam_after_sync_all`, `ma_webinarjam_after_sync_webinar`, `ma_webinarjam_course_created`, `ma_webinarjam_registration_complete`, `ma_webinarjam_activated`, `ma_webinarjam_deactivated`, `ma_webinarjam_uninstalled`, and 5 cron events
- Filters: `ma_webinarjam_api_cache_duration`, `ma_webinarjam_field_mapping`, `ma_webinarjam_course_defaults`, `ma_webinarjam_disposable_email_domains`, `ma_webinarjam_api_rate_limit`, `ma_webinarjam_preserve_data_on_uninstall`

**Documentation:**
- Setup guide: Complete installation and configuration (650+ lines)
- Usage guide: Administrator/instructor/student workflows with 4 common scenarios (850+ lines)
- Developer guide: Architecture, hooks, code examples, testing (1,100+ lines)
- Testing documentation: 150+ manual test cases across 15 categories, unit/integration/E2E framework guidance

**Dependencies:**
- WordPress 5.8+
- LearnDash LMS 4.0+
- The Events Calendar 6.0+
- The Events Calendar Pro 6.0+
- ACF or SCF for custom fields
- PHP 7.4+, MySQL 5.7+, OpenSSL, cURL

#### Shared Components

- Added new and updated shared components in `src/components/`:
  - `BackToTopButton` (accessible scroll-to-top button)
  - `ScrollDownArrow` (decorative scroll-down arrow)
  - `Logo` (accessible logo component)
  - `Typography` helpers (Heading, Text)
  - `SocialShare` (accessible social sharing buttons)
  - `LoadingSpinner` (accessible loading spinner)
  - `ErrorBoundary` (accessible error boundary)
  - `SkipLink` (skip to main content link)
  - `VisuallyHidden` (screen reader-only utility)
  - `Divider` (accessible divider)

All components use mustache placeholders and follow WordPress accessibility and code structure standards.

- `docs/FRONTMATTER_SCHEMA.md` now explains the agent frontmatter contract, points to `scripts/validation/audit-frontmatter.js`, and keeps schema updates in sync with `.github/schemas/frontmatter.schema.json`.
- Canonical schema assets live under `.github/schemas/` (block 6.9 reference, mustache registries, plugin config, plus example configs) and are verified by `scripts/validation/__tests__/validate-schemas.test.js`.
- Validation tools now centralise their naming conventions in `scripts/validation/README.md` and keep all `validate-*`, `audit-*`, `test-*`, and `define-*` scripts within `scripts/validation/`.
- `scripts/utils/dry-run-release.js` (and its test) produce sanitized copies of the release docs/agents so dry-runs can exercise templated `` values without parser failures.

### Changed

- `docs/RELEASE_PROCESS.md` now merges the previous release playbooks, documents reporting/planning folder rules, and highlights the `scripts/utils/dry-run-release.js` helper before `release.agent.js` runs against templated files.
- `package.json` validation scripts and `docs/GENERATE_PLUGIN.md` now call `scripts/validation/validate-plugin-config.js`, keeping CLI validation aligned with the action-first naming scheme.
- Instructions and prompts reference `.github/reports/`, `.github/projects/plans/`, and `tmp/` for reporting, planning, and temporary data, and the new frontmatter doc is linked from the docs index.

## [1.0.1] - 2025-12-15

### Fixed

#### Block Editor Compatibility (Phase 5)

- **Critical Fix:** Replaced 50+ hardcoded class names across all block types (card, collection, featured, slider)
  - Changed `wp-block-example_plugin-example_plugin-*` to `wp-block-ma_plugin-ma-plugin-*`
  - Updated all block edit.js files with proper mustache placeholders
  - Fixed all block render.php files with correct ACF field naming
  - Updated all block view.js files with dynamic CSS selectors
- **Pattern System:** Updated all 7 pattern files to use proper `ma_plugin_` prefixes
- **ESLint Compliance:** Fixed `@wordpress/no-unused-vars-before-return` warnings in generated slider view files
  - Removed duplicate early return checks
  - Ensured proper variable declaration order
- **ACF Integration:** Fixed hardcoded field names in card block render.php

### Added

#### Enhanced Validation & Logging

- **Per-Project Logging:** Implemented JSON-based logging system
  - Log files: `logs/generate-plugin-ma-plugin.log`
  - Structured entries with timestamp, level, message, and optional data
  - 190+ log entries per generation for comprehensive audit trail
- **Mustache Registry Schema:** Created JSON schema for validating mustache variables registry
  - Schema file: `.github/schemas/mustache-variables-registry.schema.json`
  - Validates 142 unique variables across 5,185 occurrences
- **Enhanced Validation Script:** Implemented comprehensive validation checks
  - Duplicate variable name detection
  - Count vs files.length mismatch detection
  - Category distribution analysis
  - File existence sampling
  - Detailed error and warning reporting

#### Documentation Updates

- Added logging documentation to all agent, instruction, and prompt files
- Created comprehensive release preparation report template
- Updated release scaffold agent for plugin-specific workflow
- Added debugging section to user-facing prompts

### Changed

#### Code Quality Improvements

- Applied consistent formatting across 20+ script files
- Added ESLint directives for CLI scripts allowing necessary console.log usage
- Standardized code style with Prettier configuration
- Updated instruction files with minimal reference links

#### Reference Cleanup

- Cleaned reference links in 5 instruction files
  - `javascript-react-development.instructions.md`
  - `markdown.instructions.md`
  - `wpcs-css.instructions.md`
  - `wpcs-html.instructions.md`
  - `wpcs-js-docs.instructions.md`
- Removed third-party references from References/See Also sections
- Identified 4 circular reference chains for future resolution

### Integration Testing

- ✅ Test plugin generation successful with example configuration
- ✅ All 142 mustache variables correctly replaced in generated output
- ✅ Generated blocks have proper CSS classes and ACF integration
- ✅ Blocks register correctly with `namespace/block-slug` format
- ✅ No unreplaced mustache variables in generated plugins
- ✅ All generated files pass ESLint validation

### Metrics

- **Mustache Variables:** 142 unique variables preserved (5,185 total occurrences)
- **Block Types Fixed:** 4 (card, collection, featured, slider)
- **Hardcoded Classes Replaced:** 50+ instances
- **Files Modified:** 27 core files + 2 reports
- **Code Quality:** 100% ESLint compliant
- **Test Coverage:** Plugin generation end-to-end validated

### Risk Assessment

- **Risk Level:** LOW ✅
- **Confidence:** 98% - Exceptional release readiness
- **Breaking Changes:** None - all changes are fixes and enhancements
- **Backward Compatibility:** Maintained

## [1.0.0] - 2024-12-10

Initial release of the Multi-Block Plugin Scaffold - a comprehensive WordPress plugin scaffold with dual-mode generation, mustache templating, and complete development infrastructure.

### Added

#### Core Generator System

- Dual-mode generator supporting both template mode (`--in-place`) and output folder mode (default `generated-plugins/`)
- Interactive confirmation prompt for template mode with safe default "No" to prevent accidental scaffold destruction
- Mustache template system with 6 transformation filters: `upper`, `lower`, `pascalCase`, `camelCase`, `kebabCase`, `snakeCase`
- CLI agent interface with JSON mode for programmatic plugin generation
- Comprehensive schema validation for plugin configuration via JSON Schema
- Template variable validation system ensuring correct mustache usage throughout

#### Example Blocks

**Block templates removed** - Blocks should now be implemented as patterns or custom code. The scaffold focuses on providing robust CPT, taxonomy, and field generation.

#### Architecture & Infrastructure

- Custom post type and taxonomy scaffolding with full WordPress registration
- Secure Custom Fields (SCF) integration with local JSON sync
- Block patterns system with 7 pre-built patterns
- Block template system with automatic assignment
- Repeater fields support with nested data structures
- Block bindings API integration (WordPress 6.5+)
- Block styles registration system
- Options pages with settings API integration

#### Development Tools

- Comprehensive unit test suite: 130 tests across 7 suites (Jest + @wordpress/scripts)
- Linting infrastructure: ESLint (JS), Stylelint (CSS), PHPCS (PHP), PHPStan (static analysis)
- Build system with webpack 5, Babel, and PostCSS
- Dry-run testing system for template validation without full generation
- Pre-commit hooks with Husky for code quality enforcement
- wp-env integration for local WordPress development environment

#### Documentation

- 15 comprehensive documentation files covering all aspects:
  - ARCHITECTURE.md - Repository structure and organization
  - GENERATE_PLUGIN.md - Complete plugin generation guide
  - BUILD-PROCESS.md - Build system documentation
  - TESTING.md - Testing strategies and setup
  - LINTING.md - Code quality standards
  - API_REFERENCE.md - PHP and JavaScript API documentation
- Added "Using This Scaffold" section to README.md with dual-mode workflows
- Created generated-plugins/README.md with usage warnings and cleanup instructions

### Changed

#### Repository Organization

- Reorganized directory structure:
  - `bin/` → `scripts/` for better clarity
  - `parts/` → `template-parts/` for WordPress standard naming
- Renamed agent specification:
  - `scaffold-generator.agent.md` → `generate-plugin.agent.md` for clarity
- Updated all documentation to reflect dual-mode operational model
- Consolidated GENERATE_PLUGIN.md from 4 methods to 2 operational modes

#### Code Quality

- Standardized all text domains to `ma-plugin` mustache template (474 instances)
- Corrected POST_TYPE constants to use `ma-plugin` instead of text domain
- Fixed default postType parameters in hooks to use `ma-plugin`
- Applied consistent mustache variable usage throughout codebase

### Fixed

- Resolved all circular dependency issues (madge check: 0 circular dependencies)
- Fixed 474 text domain consistency issues across PHP and JS files
- Corrected POST_TYPE constant misuse (was text domain, now correctly uses slug)
- Fixed default parameter usage in hooks (postType should be slug, not text domain)
- Ensured test fixtures in dry-run-config.js remain as test values, not templates

### Documentation

#### New Documentation Files

- `docs/ARCHITECTURE.md` - Complete repository structure guide
- `docs/BUILD-PROCESS.md` - Build system detailed documentation
- `docs/GENERATE_PLUGIN.md` - Plugin generation comprehensive guide
- `docs/TESTING.md` - Testing strategies and implementation
- `docs/LINTING.md` - Linting tools and standards
- `generated-plugins/README.md` - Output directory usage instructions

#### Enhanced Documentation

- README.md: Added "Using This Scaffold" section with workflow examples
- GENERATE_PLUGIN.md: Consolidated to 2 clear operational modes
- All docs include frontmatter metadata for better organization

### Technical Details

#### Supported Features

- WordPress 6.5+ (Block Bindings API, Plugin Dependencies)
- PHP 8.0+ requirement
- Node.js 18+ for build system
- Custom post types with full feature support
- Hierarchical and non-hierarchical taxonomies
- Secure Custom Fields integration with all field types
- Block patterns with multiple categories
- Block templates with automatic assignment
- Repeater fields with nested data
- Block styles registration
- Options pages with settings API

#### Build System

- Webpack 5 with optimized production builds
- Babel transpilation for modern JavaScript
- PostCSS with autoprefixer and cssnano
- SCSS compilation with WordPress design tokens
- Source maps for development
- Asset extraction and optimization

#### Testing Infrastructure

- Jest unit tests for JavaScript
- PHPUnit for PHP
- Playwright for E2E tests
- Code coverage reporting
- Dry-run validation system
- Pre-commit hook integration

### Breaking Changes

None - this is the initial release.

### Migration Guide

Not applicable for v1.0.0 (initial release).

### Known Issues

- Template mode (`--in-place`) is destructive and cannot be undone without version control
- Generated plugins require manual dependency installation (`npm install` and `composer install`)
- Block editor preview styles may need adjustment in different themes
- SCF field group sync requires plugin activation

### Upgrade Notes

Not applicable for v1.0.0 (initial release).

### Credits

Developed by [LightSpeed](https://lightspeedwp.agency) for the WordPress community.

### Links

- [GitHub Repository](https://github.com/lightspeedwp/multi-block-plugin-scaffold)
- [Documentation](https://github.com/lightspeedwp/multi-block-plugin-scaffold/tree/main/docs)
- [Issues](https://github.com/lightspeedwp/multi-block-plugin-scaffold/issues)
- [License](LICENSE)

---

## [Unreleased]

### Added

- Nothing yet

### Changed

- Nothing yet

### Fixed

- Nothing yet

---

## [1.1.0] - 2025-12-17

### Added

- **Block Style Variations System**: JSON-based style registration with automatic discovery
  - Enhanced `class-block-styles.php` with comprehensive style loading and validation
  - Support for block-scoped, color, and typography variations
  - 9 example style variation files across blocks, colors, presets, sections, and typography
- **SCSS Template System**: Shared mustache variables (`$namespace`, `$slug`) across stylesheets
  - New `src/scss/_template.scss` with template variables
  - Imported in all main SCSS files for consistency
- **Enhanced Testing Infrastructure**: 4 new test suites with fixtures
  - Block JSON validation tests
  - Entry point testing
  - Plugin generation tests
  - Config validation tests
- **Style Linting**: New `.stylelintignore` with comprehensive patterns
- **Phase 6 Documentation Standards**: Comprehensive JSDoc across JavaScript codebase
  - Complete JSDoc for all 4 block index files (83 lines added)
  - Complete JSDoc for all 9 component index files (90 lines added)
  - Enhanced JSDoc for all 7 custom hooks (185 lines added)
  - Added @package, @since, @see, @param, @return, @example tags
  - JavaScript documentation coverage increased from 30% to 95%
- **Phase 6 Instruction Improvements**: Critical fixes and validation checklists
  - Resolved PHP/JavaScript indentation contradictions
  - Added 106-line mustache placeholder preservation checklist
  - Updated WordPress 6.5+ file-based rendering standards
  - Standardized all text domain examples to ma-plugin

### Changed

- **Package Management**: Reorganized `package.json` with proper dependency sections
  - Added missing WordPress packages
  - Added testing utilities (Playwright, axe-core)
  - Updated version constraints for consistency
- **SCSS Architecture**: All block and component styles updated to use template variables
  - Improved organization and consistency
  - Better variable naming and indentation
- **Configuration Files**: Enhanced linting and testing configurations
  - Updated `.eslintignore`, `.eslintrc.cjs`
  - Improved `phpcs.xml`, `jest.config.js`
- **Pattern Files**: All 7 pattern files updated with improved formatting and accessibility
- **Instruction Files**: WordPress standards alignment
  - wpcs-php.instructions.md: Fixed indentation guidance (tabs for PHP)
  - wpcs-javascript.instructions.md: Clarified React (2 spaces) vs vanilla JS (tabs)
  - block-json.instructions.md: WordPress 6.5+ render property as PRIMARY method
  - scaffold-extensions.instructions.md: Added comprehensive validation checklist

### Fixed

- **Phase 6 Critical Issues Resolved**:
  - Issue #1: PHP/JS indentation contradictions (WPCS alignment)
  - Issue #3: Mustache placeholder preservation validation (106-line checklist)
  - Issue #4: WordPress 6.5+ block rendering standards (file-based rendering)
  - Issue #5: Text domain standardization (all examples use ma-plugin)
- **Linting Compliance**: Fixed 121 ESLint/Prettier formatting issues
  - Removed unused imports
  - Added necessary eslint directives
  - Auto-formatted test files

### Technical Metrics

- **Phase 5 + Style System**: 71 files (53 modified, 18 new), 2,520+ insertions
- **Phase 6 Documentation**: 20 files enhanced, 358 lines JSDoc added
- **Phase 6 Instructions**: 5 files improved, 200+ lines added
- **Test Coverage**: 138 tests passing ✅
- **Style Variations**: 9 JSON definitions (165 lines)
- **Documentation Coverage**: JavaScript 95% (up from 30%), PHP 92%
- **Quality Checks**: All linting passes ✅ (CSS, JS, Tests)

---

[1.0.0]: https://github.com/lightspeedwp/multi-block-plugin-scaffold/releases/tag/v1.0.0
[Unreleased]: https://github.com/lightspeedwp/multi-block-plugin-scaffold/compare/v1.0.0...HEAD
