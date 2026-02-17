# WebinarJam Integration Developer Documentation

**Version:** 1.0.0  
**Last Updated:** 2026-02-17  
**Plugin:** Medical Academic (ma-plugin)

This guide provides technical documentation for developers working with or extending the WebinarJam integration.

---

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Class Reference](#class-reference)
3. [Hooks & Filters](#hooks--filters)
4. [Data Flow](#data-flow)
5. [Database Schema](#database-schema)
6. [API Integration](#api-integration)
7. [Customization Examples](#customization-examples)
8. [Testing](#testing)

---

## Architecture Overview

### Directory Structure

```
inc/webinarjam/
├── class-webinarjam-integration.php       # Main coordinator
├── class-webinarjam-api-client.php        # API communication
├── class-webinarjam-options.php           # Settings page
├── class-webinarjam-scheduler.php         # Cron management
├── class-webinarjam-sync-handler.php      # Sync operations
├── class-webinarjam-data-transformer.php  # Data mapping
├── class-webinarjam-importer.php          # Import logic
├── class-webinarjam-status-handler.php    # Status management
├── class-webinarjam-attendance-handler.php # Attendance tracking
├── class-webinarjam-taxonomy-manager.php  # Taxonomy operations
├── class-webinarjam-frontend-handler.php  # Frontend display
├── class-webinarjam-course-integration.php # Course workflows
├── class-webinarjam-user-dashboard.php    # User interface
├── class-webinarjam-admin.php             # Admin interface
├── class-webinarjam-logger.php            # Logging system
├── class-webinarjam-error-handler.php     # Error management
├── helper-functions.php                   # Utility functions
└── template-functions.php                 # Template tags

scf-json/webinarjam/
├── course-webinar-fields.json             # Course custom fields
└── event-webinar-fields.json              # Event custom fields

assets/
├── css/webinarjam-frontend.css            # Frontend styles
└── js/webinarjam-frontend.js              # Frontend scripts

docs/
├── WEBINARJAM-SETUP.md                    # Setup guide
├── WEBINARJAM-USAGE.md                    # Usage guide
└── WEBINARJAM-DEVELOPER.md                # This file

tests/webinarjam/
├── README.md                              # Testing documentation
└── TESTING-CHECKLIST.md                   # Manual testing checklist
```

### Class Hierarchy

```
WebinarJam_Integration (Main Coordinator)
├── WebinarJam_API_Client (API Communication)
│   └── WebinarJam_Error_Handler (Error Management)
├── WebinarJam_Scheduler (Cron Events)
├── WebinarJam_Sync_Handler (Sync Operations)
│   └── WebinarJam_Data_Transformer (Data Mapping)
├── WebinarJam_Importer (Import Logic)
├── WebinarJam_Status_Handler (Status Updates)
├── WebinarJam_Attendance_Handler (Attendance Tracking)
├── WebinarJam_Taxonomy_Manager (Taxonomies)
├── WebinarJam_Frontend_Handler (Frontend Display)
├── WebinarJam_Course_Integration (Course Workflows)
├── WebinarJam_User_Dashboard (User Interface)
├── WebinarJam_Options (Settings Page)
├── WebinarJam_Admin (Admin Interface)
└── WebinarJam_Logger (Logging System)
```

### Design Patterns

**Dependency Injection:**
- All classes receive dependencies via constructor
- Promotes testability and loose coupling

**Singleton (via Core_Loader):**
- Main integration class initialized once
- Accessed via Core instance

**Observer Pattern:**
- WordPress hooks for event-driven architecture
- Classes observe WordPress actions/filters

**Strategy Pattern:**
- Different handlers for sync, import, status, attendance
- Swappable implementations

---

## Class Reference

### WebinarJam_Integration

**Purpose:** Main coordinator class that initializes and manages all integration components.

**Namespace:** `ma_plugin\classes\WebinarJam`

**Properties:**
```php
private WebinarJam_API_Client $api_client;
private WebinarJam_Scheduler $scheduler;
private WebinarJam_Sync_Handler $sync_handler;
private WebinarJam_Importer $importer;
private WebinarJam_Status_Handler $status_handler;
private WebinarJam_Options $options;
private WebinarJam_Taxonomy_Manager $taxonomy_manager;
private WebinarJam_Frontend_Handler $frontend;
private WebinarJam_Attendance_Handler $attendance_handler;
private WebinarJam_Course_Integration $course_integration;
private WebinarJam_User_Dashboard $user_dashboard;
private WebinarJam_Logger $logger;
private WebinarJam_Admin $admin;
private WebinarJam_Error_Handler $error_handler;
```

**Key Methods:**
```php
public function __construct()              // Initialize all components
public function init()                     // Hook into WordPress
private function load_dependencies()       // Load class files
private function init_components()         // Instantiate classes
```

**Usage:**
```php
// Accessed via Core instance
$core = Core::get_instance();
$webinarjam = $core->get_webinarjam_integration();
```

---

### WebinarJam_API_Client

**Purpose:** Handles all communication with WebinarJam API.

**Key Methods:**
```php
public function get_all_webinars(): array|WP_Error
public function get_webinar(string $webinar_id): array|WP_Error
public function register_user(string $webinar_id, array $user_data): array|WP_Error
public function get_attendees(string $webinar_id): array|WP_Error
public function test_connection(): bool|WP_Error
private function make_request(string $endpoint, array $params = [], string $method = 'GET'): array|WP_Error
private function is_rate_limited(): bool
```

**Error Handling:**
- Returns `WP_Error` on failures
- Automatic retry with exponential backoff (3 attempts)
- Rate limit detection and cooldown
- Response validation

**Usage:**
```php
$api_client = new WebinarJam_API_Client();
$webinars = $api_client->get_all_webinars();

if (is_wp_error($webinars)) {
    // Handle error
    error_log($webinars->get_error_message());
} else {
    // Process webinars
    foreach ($webinars as $webinar) {
        // ...
    }
}
```

---

### WebinarJam_Data_Transformer

**Purpose:** Transforms WebinarJam API data to WordPress/LearnDash format.

**Key Methods:**
```php
public static function transform_webinar_to_course_data(array $webinar): array
public static function transform_presenters_data(array $presenters): array
public static function transform_schedule_data(array $schedules): array
public static function transform_attendees_data(array $attendees): array
public static function determine_webinar_status(array $schedule): string
private static function validate_and_sanitize_data(array $data): array
```

**Returns:**
```php
[
    'post_title' => string,
    'post_content' => string,
    'post_status' => string,
    'meta_fields' => [
        'webinarjam_webinar_id' => string,
        'webinarjam_description' => string,
        'webinarjam_status' => string,
        // ... all custom fields
    ]
]
```

**Usage:**
```php
$webinar_data = $api_client->get_webinar('12345');
$course_data = WebinarJam_Data_Transformer::transform_webinar_to_course_data($webinar_data);

// Create or update course
$course_id = wp_insert_post([
    'post_type' => 'sfwd-courses',
    'post_title' => $course_data['post_title'],
    'post_content' => $course_data['post_content'],
    'post_status' => $course_data['post_status'],
]);

// Update custom fields
foreach ($course_data['meta_fields'] as $key => $value) {
    update_field($key, $value, $course_id);
}
```

---

### WebinarJam_Sync_Handler

**Purpose:** Manages synchronization of webinars from WebinarJam to WordPress.

**Key Methods:**
```php
public function sync_all_webinars(): array
public function sync_single_webinar(string $webinar_id): bool|WP_Error
private function should_sync_webinar(int $course_id, array $webinar_data): bool
private function sync_course_from_webinar(array $webinar_data): int|WP_Error
```

**Returns:**
```php
// sync_all_webinars returns:
[
    'success' => int,      // Number of successful syncs
    'failed' => int,       // Number of failures
    'skipped' => int,      // Number skipped (manual edit protection)
    'errors' => array      // Error details
]
```

**Usage:**
```php
$sync_handler = new WebinarJam_Sync_Handler($api_client, $importer, $logger);
$result = $sync_handler->sync_all_webinars();

echo "Synced: {$result['success']}, Failed: {$result['failed']}, Skipped: {$result['skipped']}";
```

---

### WebinarJam_Error_Handler

**Purpose:** Centralized error handling, validation, and recovery.

**Key Methods:**
```php
public function handle_error(string $type, string $message, array $context = [], bool $is_critical = false): void
public function execute_with_retry(callable $callback, array $args = [], int $max_retries = 3): mixed
public function validate_api_response($response, string $endpoint): array|WP_Error
public function validate_webinar_data(array $webinar): true|WP_Error
public function handle_duplicate_webinar(string $webinar_id, array $data): int|false
public function handle_rate_limit(int $retry_after = 3600): void
public function is_rate_limited(): bool
```

**Error Types:**
- `api_error` - API communication failures
- `validation_error` - Data validation failures
- `import_error` - Import process errors
- `sync_error` - Sync operation errors

**Usage:**
```php
$error_handler = new WebinarJam_Error_Handler($logger);

// Execute with automatic retry
$result = $error_handler->execute_with_retry(function() use ($api_client) {
    return $api_client->get_all_webinars();
});

// Validate webinar data
$webinar = $api_client->get_webinar('12345');
$validation = $error_handler->validate_webinar_data($webinar);

if (is_wp_error($validation)) {
    $error_handler->handle_error('validation_error', $validation->get_error_message(), ['webinar_id' => '12345']);
}
```

---

### WebinarJam_Logger

**Purpose:** Database-backed logging system for all integration activity.

**Key Methods:**
```php
public function log(string $type, string $message, array $context = []): void
public function get_logs(array $args = []): array
public function clear_logs(string $type = ''): int
public static function create_log_table(): void
```

**Log Types:**
- `sync` - Synchronization events
- `import` - Import operations
- `attendance` - Attendance checks
- `status` - Status updates
- `error` - Error events
- `api` - API calls

**Usage:**
```php
$logger = new WebinarJam_Logger();

// Log sync event
$logger->log('sync', 'Webinar synced successfully', [
    'webinar_id' => '12345',
    'course_id' => 456,
    'action' => 'update'
]);

// Get recent error logs
$errors = $logger->get_logs([
    'type' => 'error',
    'limit' => 50,
    'order' => 'DESC'
]);

// Clear old logs
$deleted = $logger->clear_logs('sync');
```

---

## Hooks & Filters

### Actions

#### Integration Lifecycle

```php
/**
 * Fires after WebinarJam integration initializes
 *
 * @param WebinarJam_Integration $integration The integration instance
 */
do_action('ma_webinarjam_integration_init', $integration);
```

#### Sync Operations

```php
/**
 * Fires before syncing all webinars
 */
do_action('ma_webinarjam_before_sync_all');

/**
 * Fires after syncing all webinars
 *
 * @param array $results Sync results (success, failed, skipped counts)
 */
do_action('ma_webinarjam_after_sync_all', $results);

/**
 * Fires before syncing a single webinar
 *
 * @param string $webinar_id The WebinarJam webinar ID
 */
do_action('ma_webinarjam_before_sync_webinar', $webinar_id);

/**
 * Fires after syncing a single webinar
 *
 * @param string $webinar_id The WebinarJam webinar ID
 * @param int|WP_Error $result The course ID or error
 */
do_action('ma_webinarjam_after_sync_webinar', $webinar_id, $result);

/**
 * Fires when a webinar course is created
 *
 * @param int $course_id The course post ID
 * @param string $webinar_id The WebinarJam webinar ID
 */
do_action('ma_webinarjam_course_created', $course_id, $webinar_id);

/**
 * Fires when a webinar course is updated
 *
 * @param int $course_id The course post ID
 * @param string $webinar_id The WebinarJam webinar ID
 */
do_action('ma_webinarjam_course_updated', $course_id, $webinar_id);
```

#### Registration

```php
/**
 * Fires before registering a user for a webinar
 *
 * @param int $user_id WordPress user ID
 * @param int $course_id Course post ID
 * @param string $webinar_id WebinarJam webinar ID
 */
do_action('ma_webinarjam_before_registration', $user_id, $course_id, $webinar_id);

/**
 * Fires after successful registration
 *
 * @param int $user_id WordPress user ID
 * @param int $course_id Course post ID
 * @param string $webinar_id WebinarJam webinar ID
 * @param array $response API response data
 */
do_action('ma_webinarjam_after_registration', $user_id, $course_id, $webinar_id, $response);

/**
 * Fires when registration fails
 *
 * @param int $user_id WordPress user ID
 * @param int $course_id Course post ID  
 * @param string $webinar_id WebinarJam webinar ID
 * @param WP_Error $error The error object
 */
do_action('ma_webinarjam_registration_failed', $user_id, $course_id, $webinar_id, $error);
```

#### Attendance

```php
/**
 * Fires before attendance check
 *
 * @param string $webinar_id WebinarJam webinar ID
 */
do_action('ma_webinarjam_before_attendance_check', $webinar_id);

/**
 * Fires after attendance check
 *
 * @param string $webinar_id WebinarJam webinar ID
 * @param int $course_id Course post ID
 * @param array $attendees List of attendee data
 */
do_action('ma_webinarjam_after_attendance_check', $webinar_id, $course_id, $attendees);

/**
 * Fires when user attendance is recorded
 *
 * @param int $user_id WordPress user ID
 * @param int $course_id Course post ID
 * @param string $webinar_id WebinarJam webinar ID
 */
do_action('ma_webinarjam_attendance_recorded', $user_id, $course_id, $webinar_id);

/**
 * Fires when course auto-completed on attendance
 *
 * @param int $user_id WordPress user ID
 * @param int $course_id Course post ID
 */
do_action('ma_webinarjam_course_auto_completed', $user_id, $course_id);
```

#### Status Changes

```php
/**
 * Fires when webinar status changes
 *
 * @param int $course_id Course post ID
 * @param string $old_status Previous status
 * @param string $new_status New status
 */
do_action('ma_webinarjam_status_changed', $course_id, $old_status, $new_status);
```

#### Cron Actions

```php
/**
 * Daily sync cron action
 */
do_action('ma_webinarjam_daily_sync');

/**
 * Webinar import cron action
 */
do_action('ma_webinarjam_import_webinars');

/**
 * Status check cron action
 */
do_action('ma_webinarjam_status_check');

/**
 * Attendance check cron action
 */
do_action('ma_webinarjam_attendance_check');
```

### Filters

#### Data Transformation

```php
/**
 * Filter transformed course data before creating/updating
 *
 * @param array $course_data Transformed course data
 * @param array $webinar_data Raw WebinarJam data
 */
$course_data = apply_filters('ma_webinarjam_course_data', $course_data, $webinar_data);

/**
 * Filter webinar status determination
 *
 * @param string $status Determined status (upcoming, live, replay, completed)
 * @param array $schedule Schedule data
 */
$status = apply_filters('ma_webinarjam_determine_status', $status, $schedule);

/**
 * Filter presenter data transformation
 *
 * @param array $presenters Transformed presenter data
 * @param array $raw_presenters Raw API presenter data
 */
$presenters = apply_filters('ma_webinarjam_transform_presenters', $presenters, $raw_presenters);
```

#### Sync Behavior

```php
/**
 * Filter whether a webinar should be synced
 *
 * @param bool $should_sync Whether to sync (default based on settings)
 * @param int $course_id Course post ID (0 if new)
 * @param array $webinar_data WebinarJam data
 */
$should_sync = apply_filters('ma_webinarjam_should_sync', $should_sync, $course_id, $webinar_data);

/**
 * Filter sync settings
 *
 * @param array $settings Current sync settings
 */
$settings = apply_filters('ma_webinarjam_sync_settings', $settings);
```

#### Registration

```php
/**
 * Filter user data before registration
 *
 * @param array $user_data User data for API
 * @param WP_User $user WordPress user object
 * @param int $course_id Course post ID
 */
$user_data = apply_filters('ma_webinarjam_registration_user_data', $user_data, $user, $course_id);

/**
 * Filter whether user can register
 *
 * @param bool $can_register Whether user can register (default: has course access)
 * @param int $user_id WordPress user ID
 * @param int $course_id Course post ID
 */
$can_register = apply_filters('ma_webinarjam_can_register', $can_register, $user_id, $course_id);
```

#### Frontend Display

```php
/**
 * Filter button HTML
 *
 * @param string $button_html Button HTML markup
 * @param int $course_id Course post ID
 * @param string $button_type Type: register, join, replay
 */
$button_html = apply_filters('ma_webinarjam_button_html', $button_html, $course_id, $button_type);

/**
 * Filter button text
 *
 * @param string $text Button text
 * @param string $button_type Button type
 * @param int $course_id Course post ID
 */
$text = apply_filters('ma_webinarjam_button_text', $text, $button_type, $course_id);

/**
 * Filter status badge HTML
 *
 * @param string $badge_html Badge HTML markup
 * @param string $status Current status
 * @param int $course_id Course post ID
 */
$badge_html = apply_filters('ma_webinarjam_status_badge', $badge_html, $status, $course_id);
```

#### Admin Interface

```php
/**
 * Filter admin list columns
 *
 * @param array $columns Column definitions
 */
$columns = apply_filters('ma_webinarjam_admin_columns', $columns);

/**
 * Filter admin bulk actions
 *
 * @param array $actions Bulk action definitions
 */
$actions = apply_filters('ma_webinarjam_bulk_actions', $actions);
```

---

## Data Flow

### Sync Workflow

```
WebinarJam API
    ↓
API Client (get_all_webinars)
    ↓
Error Handler (validate_api_response)
    ↓
Data Transformer (transform_webinar_to_course_data)
    ↓
Sync Handler (sync_course_from_webinar)
    ↓
Importer (import_or_update_course)
    ↓
WordPress Database (wp_posts, wp_postmeta)
    ↓
Event Creation (if course published)
    ↓
Logger (log sync event)
```

### Registration Workflow

```
User clicks "Register" button
    ↓
Frontend AJAX (ma_webinarjam_register)
    ↓
Capability check (user logged in)
    ↓
Access check (user has course access)
    ↓
Frontend Handler (register_user_for_webinar)
    ↓
API Client (register_user)
    ↓
WebinarJam API (register endpoint)
    ↓
Update course meta (add user to registered list)
    ↓
Logger (log registration)
    ↓
Return JSON response to frontend
    ↓
Update button state (disabled, "You're Registered")
```

### Attendance Workflow

```
Cron: ma_webinarjam_attendance_check
    ↓
Attendance Handler (check_all_attendance)
    ↓
Get all webinar courses
    ↓
For each course:
    API Client (get_attendees)
        ↓
    Match attendees to WordPress users by email
        ↓
    Update course meta (add to attended list)
        ↓
    If auto-complete enabled:
        LearnDash (learndash_process_mark_complete)
            ↓
        Fire action: ma_webinarjam_course_auto_completed
        ↓
    Logger (log attendance)
```

---

## Database Schema

### Custom Tables

#### wp_webinarjam_logs

```sql
CREATE TABLE wp_webinarjam_logs (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    log_type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    context LONGTEXT,
    created_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY log_type (log_type),
    KEY created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Post Meta Keys

Stored in `wp_postmeta` for `sfwd-courses` post type:

```php
// Core fields
'_webinarjam_webinar_id'             // string - Unique WebinarJam ID
'_webinarjam_description'            // string - Full description
'_webinarjam_registration_url'        // string - Registration URL
'_webinarjam_thank_you_url'          // string - Thank you page URL
'_webinarjam_status'                 // string - upcoming|live|replay|completed

// Schedule fields
'_webinarjam_next_date'              // string - Y-m-d H:i:s
'_webinarjam_timezone'               // string - Timezone identifier
'_webinarjam_duration'               // int - Minutes
'_webinarjam_is_recurring'           // bool - 0|1
'_webinarjam_all_schedules'          // array - All schedule dates

// Presenter fields
'_webinarjam_presenter_name'         // string
'_webinarjam_presenter_email'        // string
'_webinarjam_presenter_bio'          // string
'_webinarjam_presenter_image'        // int - Attachment ID
'_webinarjam_additional_presenters'  // array - Repeater field

// Settings fields
'_webinarjam_replay_available'       // bool - 0|1
'_webinarjam_replay_url'             // string
'_webinarjam_auto_complete'          // bool - 0|1
'_webinarjam_sync_disabled'          // bool - 0|1
'_webinarjam_last_sync'              // string - Y-m-d H:i:s

// Registration fields
'_webinarjam_registered_users'       // array - User IDs
'_webinarjam_registration_count'     // int
'_webinarjam_attended_users'         // array - User IDs
'_webinarjam_attendance_count'       // int
```

### WordPress Options

Stored in `wp_options`:

```php
'ma_webinarjam_api_key'              // string - Encrypted API key
'ma_webinarjam_sync_settings'        // array - Sync configuration
'ma_webinarjam_advanced_settings'    // array - Advanced configuration
```

### Transients

Temporary cached data:

```php
'ma_webinarjam_webinars'             // array - All webinars (12 hours)
'ma_webinarjam_webinar_{id}'         // array - Single webinar (6 hours)
'ma_webinarjam_rate_limit'           // bool - Rate limit flag
'ma_webinarjam_error_count_{type}'   // int - Error threshold tracking
```

---

## API Integration

### WebinarJam API Endpoints

**Base URL:** `https://api.webinarjam.com/webinarjam/`

#### Get All Webinars

```http
GET /webinars
```

**Parameters:**
- `api_key` (required) - Your API key

**Response:**
```json
{
  "webinars": [
    {
      "webinar_id": "12345",
      "name": "Webinar Title",
      "description": "Description text",
      "schedules": [...],
      "presenters": [...],
      "settings": {...}
    }
  ]
}
```

#### Get Single Webinar

```http
GET /webinar
```

**Parameters:**
- `api_key` (required)
- `webinar_id` (required)

#### Register User

```http
POST /register
```

**Parameters:**
- `api_key` (required)
- `webinar_id` (required)
- `first_name` (required)
- `last_name` (required)
- `email` (required)
- `phone` (optional)

#### Get Attendees

```http
GET /attendees
```

**Parameters:**
- `api_key` (required)
- `webinar_id` (required)

**Response:**
```json
{
  "attendees": [
    {
      "email": "user@example.com",
      "first_name": "John",
      "last_name": "Doe",
      "attended_at": "2026-02-15 14:30:00"
    }
  ]
}
```

### Rate Limiting

WebinarJam API rate limits:
- **100 requests per hour** per API key
- HTTP 429 response when exceeded
- `Retry-After` header indicates seconds to wait

**Handling in Code:**
```php
// Automatic rate limit detection
if ($error_handler->is_rate_limited()) {
    return new WP_Error('rate_limited', 'API rate limit exceeded');
}

// Automatic retry with backoff
$result = $error_handler->execute_with_retry(function() {
    return $api_client->get_all_webinars();
});
```

---

## Customization Examples

### Example 1: Custom Button Styling

```php
/**
 * Customize registration button appearance
 */
add_filter('ma_webinarjam_button_html', function($button_html, $course_id, $button_type) {
    if ($button_type === 'register') {
        $button_html = str_replace(
            'ma-webinar-register-btn',
            'ma-webinar-register-btn custom-class',
            $button_html
        );
    }
    return $button_html;
}, 10, 3);

/**
 * Change button text
 */
add_filter('ma_webinarjam_button_text', function($text, $button_type, $course_id) {
    if ($button_type === 'register') {
        return 'Reserve Your Spot';
    }
    return $text;
}, 10, 3);
```

### Example 2: Custom Post-Registration Action

```php
/**
 * Send custom email after successful registration
 */
add_action('ma_webinarjam_after_registration', function($user_id, $course_id, $webinar_id, $response) {
    $user = get_userdata($user_id);
    $course = get_post($course_id);
    
    $subject = sprintf('Confirmed: %s', $course->post_title);
    $message = sprintf(
        "Hi %s,\n\nYou're registered for: %s\n\nSee you there!",
        $user->first_name,
        $course->post_title
    );
    
    wp_mail($user->user_email, $subject, $message);
}, 10, 4);
```

### Example 3: Custom Sync Filtering

```php
/**
 * Only sync webinars with "Medical" in the title
 */
add_filter('ma_webinarjam_should_sync', function($should_sync, $course_id, $webinar_data) {
    if (stripos($webinar_data['name'], 'Medical') === false) {
        return false;
    }
    return $should_sync;
}, 10, 3);
```

### Example 4: Award Points on Attendance

```php
/**
 * Award GamiPress points when user attends webinar
 */
add_action('ma_webinarjam_attendance_recorded', function($user_id, $course_id, $webinar_id) {
    if (function_exists('gamipress_award_points_to_user')) {
        gamipress_award_points_to_user(
            $user_id,
            100, // 100 points
            'points', // points type
            array('log_type' => 'webinar_attended')
        );
    }
}, 10, 3);
```

### Example 5: Custom Dashboard Widget

```php
/**
 * Add custom widget to admin dashboard
 */
add_action('wp_dashboard_setup', function() {
    wp_add_dashboard_widget(
        'ma_webinar_stats',
        'Webinar Statistics',
        'render_webinar_stats_widget'
    );
});

function render_webinar_stats_widget() {
    $courses = \ma_get_webinar_courses([
        'status' => 'upcoming',
        'meta_key' => '_webinarjam_next_date',
        'orderby' => 'meta_value',
        'order' => 'ASC',
        'posts_per_page' => 5
    ]);
    
    echo '<ul>';
    foreach ($courses as $course) {
        $next_date = get_post_meta($course->ID, '_webinarjam_next_date', true);
        $registered = get_post_meta($course->ID, '_webinarjam_registration_count', true);
        
        printf(
            '<li>%s - %s (%d registered)</li>',
            $course->post_title,
            date('M j, Y', strtotime($next_date)),
            $registered
        );
    }
    echo '</ul>';
}
```

### Example 6: Custom Validation

```php
/**
 * Add custom validation for webinar data
 */
add_filter('ma_webinarjam_course_data', function($course_data, $webinar_data) {
    // Require minimum description length
    if (strlen($course_data['post_content']) < 100) {
        // Log warning
        error_log('Webinar description too short: ' . $webinar_data['webinar_id']);
        
        // Append disclaimer
        $course_data['post_content'] .= "\n\nMore details coming soon.";
    }
    
    return $course_data;
}, 10, 2);
```

---

## Testing

### Unit Testing

Create unit tests for individual class methods:

```php
class WebinarJam_Data_Transformer_Test extends WP_UnitTestCase {
    
    public function test_transform_webinar_to_course_data() {
        $webinar = [
            'webinar_id' => '12345',
            'name' => 'Test Webinar',
            'description' => 'Test description',
            'schedules' => [
                [
                    'date' => '2026-03-15',
                    'time' => '14:00:00',
                    'timezone' => 'America/New_York'
                ]
            ],
            'settings' => [
                'registration_url' => 'https://example.com/register'
            ]
        ];
        
        $course_data = WebinarJam_Data_Transformer::transform_webinar_to_course_data($webinar);
        
        $this->assertEquals('Test Webinar', $course_data['post_title']);
        $this->assertEquals('12345', $course_data['meta_fields']['webinarjam_webinar_id']);
        $this->assertArrayHasKey('webinarjam_registration_url', $course_data['meta_fields']);
    }
}
```

### Integration Testing

Test full workflows:

```php
class WebinarJam_Sync_Integration_Test extends WP_UnitTestCase {
    
    public function test_full_sync_workflow() {
        // Mock API client
        $api_client = $this->createMock(WebinarJam_API_Client::class);
        $api_client->method('get_all_webinars')->willReturn([
            [
                'webinar_id' => '12345',
                'name' => 'Test Webinar',
                // ... full webinar data
            ]
        ]);
        
        // Create sync handler with mock
        $sync_handler = new WebinarJam_Sync_Handler($api_client, $importer, $logger);
        
        // Run sync
        $result = $sync_handler->sync_all_webinars();
        
        // Assert course created
        $courses = get_posts(['post_type' => 'sfwd-courses', 'meta_key' => '_webinarjam_webinar_id', 'meta_value' => '12345']);
        $this->assertCount(1, $courses);
        
        // Assert fields populated
        $this->assertEquals('12345', get_post_meta($courses[0]->ID, '_webinarjam_webinar_id', true));
    }
}
```

See [Testing Documentation](../tests/webinarjam/README.md) for complete testing guide.

---

## Troubleshooting Development Issues

### Debugging API Calls

Enable debug mode and check logs:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Enable WebinarJam debug mode
update_option('ma_webinarjam_advanced_settings', [
    'debug_mode' => true,
    'cache_duration' => 0 // Disable caching while debugging
]);

// View logs
$logger = new WebinarJam_Logger();
$api_logs = $logger->get_logs(['type' => 'api', 'limit' => 20]);
```

### Inspecting Data Transformations

```php
// Add temporary filter to log transformed data
add_filter('ma_webinarjam_course_data', function($course_data, $webinar_data) {
    error_log('Webinar Data: ' . print_r($webinar_data, true));
    error_log('Course Data: ' . print_r($course_data, true));
    return $course_data;
}, 10, 2);
```

### Clearing Caches

```php
// Clear all WebinarJam transients
global $wpdb;
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_ma_webinarjam_%' OR option_name LIKE '_transient_timeout_ma_webinarjam_%'");

// Clear object cache
wp_cache_flush();
```

---

## Performance Optimization

### Batch Processing

For large webinar lists, process in batches:

```php
add_filter('ma_webinarjam_sync_batch_size', function() {
    return 50; // Process 50 webinars at a time
});
```

### Database Indexing

Add custom indexes for frequent queries:

```sql
ALTER TABLE wp_postmeta ADD INDEX webinarjam_id (_meta_key, _meta_value(191)) WHERE _meta_key = '_webinarjam_webinar_id';
ALTER TABLE wp_postmeta ADD INDEX webinarjam_status (_meta_key, _meta_value(191)) WHERE _meta_key = '_webinarjam_status';
```

### Caching Strategy

```php
// Customize cache duration
add_filter('ma_webinarjam_cache_duration', function($duration) {
    return 6 * HOUR_IN_SECONDS; // 6 hours instead of 12
});
```

---

## Contributing

When contributing to the WebinarJam integration:

1. Follow [LightSpeed Coding Standards](/.github/instructions/coding-standards.instructions.md)
2. Add PHPDoc blocks to all classes and methods
3. Create tests for new features
4. Update this documentation
5. Add entries to changelog

---

## Additional Resources

- **[Setup Guide](WEBINARJAM-SETUP.md)** - Installation and configuration
- **[Usage Guide](WEBINARJAM-USAGE.md)** - Day-to-day usage
- **[Testing Guide](../tests/webinarjam/README.md)** - Testing documentation
- **[LightSpeed Instructions](/.github/instructions/)** - Org-wide coding standards
- **[WebinarJam API Docs](https://documentation.webinarjam.com/)** - Official API documentation

---

**Document Version:** 1.0.0  
**Last Updated:** 2026-02-17  
**Plugin Version:** 1.0.0  
**Maintained By:** LightSpeed WordPress Agency
