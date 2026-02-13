# WebinarJam Integration - Implementation Task List

**Project:** Medical Academic Plugin - WebinarJam Integration  
**Created:** 2026-02-13  
**Plugin:** ma-plugin  
**Status:** Planning

---

## Overview

Integration of WebinarJam API with LearnDash courses and The Events Calendar to automate webinar registration, attendance tracking, and course completion.

**API Details:** 635 webinars available via WebinarJam API

**Dependencies:**
- LearnDash
- LearnDash Events Calendar Integration
- The Events Calendar
- The Events Calendar Pro
- Events Tickets
- Events Tickets Pro

---

## Section 1: Project Setup & Infrastructure ✅

### 1.1 File Structure Setup
- [x] Create `bin/` directory for task management
- [x] Create task list document
- [x] Create `inc/webinarjam/` directory for integration classes
- [x] Create `scf-json/webinarjam/` subdirectory for related JSON files

### 1.2 Core Integration Class
- [x] Create `inc/webinarjam/class-webinarjam-integration.php` - Main integration class
- [x] Create `inc/webinarjam/class-webinarjam-api-client.php` - API client wrapper
- [x] Create `inc/webinarjam/class-webinarjam-scheduler.php` - Scheduled events manager
- [x] Register classes in Core loader
- [x] Add WebinarJam integration initialization

---

## Section 2: Backend - API Configuration & Settings ✅

### 2.1 Options Page for API Credentials
- [x] Create WebinarJam options sub-page in existing Options class
- [x] Add secure fields for API credentials:
  - API Key (password field)
  - API URL (URL field)
  - Sync frequency (select: hourly/daily)
  - Debug mode toggle
- [x] Create `inc/webinarjam/class-webinarjam-options.php`
- [x] Register fields via SCF programmatically (with tabs)
- [x] Add API connection test functionality
- [x] Add "Test Connection" button with AJAX handler
- [x] Add "Clear Cache" button functionality
- [x] Add "Force Sync" button functionality
- [x] Add Statistics display

### 2.2 Helper Functions
- [x] Create `inc/webinarjam/helper-functions.php`
- [x] Add `ma_get_webinarjam_api_key()` - Retrieve API key
- [x] Add `ma_get_webinarjam_setting()` - Generic settings getter
- [x] Add `ma_is_webinarjam_configured()` - Check if configured
- [x] Add `ma_log_webinarjam_debug()` - Debug logging function
- [x] Add helper functions for course/event lookups
- [x] Add helper functions for status management
- [x] Add helper functions for user access checks

---

## Section 3: Backend - Custom Post Types & Taxonomies ✅

### 3.1 Course Type Taxonomy
- [x] Verified existing `taxonomy-course_type` (taxonomy_697c89b061b5c.json)
- [x] Created taxonomy manager class for term creation
- [x] Add "Webinar" term automatically (on init)
- [x] Add "Replay" term automatically (on init)

### 3.2 Webinar Status Management
- [x] Decided on Meta field approach (more appropriate for dynamic status)
- [x] Implemented status field in webinar fields group
- [x] Created helper functions for status management

### 3.3 Webinar Custom Fields
- [x] Created `scf-json/webinarjam/group_webinarjam_course_fields.json`
- [x] Organized fields into tabs: General, URLs, Presenters, Schedule, Sync Settings
- [x] Added fields to LearnDash courses (conditional: Course Type = Webinar):
  - `webinarjam_webinar_id` (text) - WebinarJam unique ID
  - `webinarjam_status` (select) - upcoming/live/replay
  - `webinarjam_import_status` (select) - pending/imported/synced/error
  - `webinarjam_presenters` (repeater) - Presenter details
    - presenter_name (text)
    - presenter_email (email)
    - presenter_photo (image)
    - presenter_bio (textarea)
  - `webinarjam_schedule` (repeater) - Multiple schedule dates
    - schedule_date (date_time_picker)
    - schedule_timezone (text)
    - schedule_duration (number)
  - `webinarjam_registration_url` (URL)
  - `webinarjam_replay_url` (URL)
  - `webinarjam_last_sync` (date_time_picker) - Read-only
  - `cpd_points` (number) - Manual entry
  - `webinarjam_sync_enabled` (true_false) - Enable/disable auto-sync
  - `webinarjam_auto_complete` (true_false) - Enable/disable auto-complete
  - `webinarjam_notify_users` (true_false) - Send notifications
  - `webinarjam_sync_log` (textarea) - Sync activity log

### 3.4 Event Custom Fields
- [x] Created `scf-json/webinarjam/group_webinarjam_event_fields.json`
- [x] Added fields to Events (when connected to webinar course):
  - `event_button_text` (text) - Dynamic button text
  - `event_button_url` (URL) - Dynamic button URL
  - `event_webinar_status` (select) - Synced from course
  - `event_sync_enabled` (true_false) - Enable/disable auto-sync
  - `event_last_sync` (date_time_picker) - Last sync timestamp

### 3.5 Taxonomy Manager Class
- [x] Created `class-webinarjam-taxonomy.php`
- [x] Automatic term creation on plugin activation/init
- [x] Static helper methods for term operations
- [x] Term assignment methods
- [x] Term checking methods
- [x] Cleanup methods for deactivation

### 3.6 Additional Helper Functions
- [x] `ma_get_webinar_courses()` - Get all webinar courses
- [x] `ma_get_webinar_courses_by_status()` - Get courses by status
- [x] `ma_sync_event_from_course()` - Sync event data
- [x] `ma_get_webinar_presenters()` - Get presenters
- [x] `ma_get_webinar_schedule()` - Get schedule
- [x] `ma_get_next_webinar_date()` - Get next date
- [x] `ma_is_user_registered_for_webinar()` - Check registration
- [x] `ma_add_webinar_sync_log()` - Add log entries

---

## Section 4: Backend - API Client & Data Handling ✅

### 4.1 API Client Class
- [x] Created `class-webinarjam-api-client.php` (Section 1)
- [x] Implement API authentication
- [x] Add method: `get_all_webinars()` - Fetch all webinars
- [x] Add method: `get_webinar($webinar_id)` - Fetch single webinar
- [x] Add method: `get_webinar_registrants($webinar_id)` - Get registrants
- [x] Add method: `register_user($webinar_id, $email, $name)` - Register user
- [x] Add method: `get_attendees($webinar_id)` - Get attendance data
- [x] Add error handling and logging
- [x] Add rate limiting/caching logic
- [x] Add transient caching for API responses (12-hour cache)

### 4.2 Data Transformation
- [x] Create `inc/webinarjam/class-webinarjam-transformer.php`
- [x] Add method: `transform_webinar_to_course_data($webinar)` - Map API data
- [x] Add method: `transform_schedule_data($schedules)` - Format schedules
- [x] Add method: `transform_presenter_data($presenters)` - Format presenters
- [x] Add method: `transform_attendee_data($attendees)` - Format attendees
- [x] Add validation for required fields
- [x] Add data sanitization methods
- [x] Add date parsing with timezone conversion
- [x] Add image sideloading for presenter photos
- [x] Add merge method for updating existing courses
- [x] Add status determination logic
- [x] Add helper methods for IDs, duration calculation

---

## Section 5: Backend - Scheduled Events System ✅

### 5.1 Scheduler Base Class
- [ ] Create `class-webinarjam-scheduler.php`
- [ ] Register all scheduled events on plugin activation
- [ ] Add method: `schedule_daily_sync()` - Main sync event
- [ ] Add method: `schedule_webinar_import($webinar_id)` - Import action
- [ ] Add method: `schedule_webinar_status_update($course_id, $timestamp)` - Status update
- [ ] Add method: `schedule_attendance_check($course_id, $timestamp)` - Attendance check
- [ ] Add unschedule methods for cleanup

### 5.2 Daily Webinar Sync Event
- [ ] Create `inc/webinarjam/class-webinarjam-sync.php`
- [ ] Hook: `ma_webinarjam_daily_sync`
- [ ] Fetch all webinars from API
- [ ] Get all existing webinar courses (by webinarjam_webinar_id)
- [ ] Compare and identify:
  - New webinars (schedule import)
  - Existing webinars (check for schedule updates)
  - Removed webinars (optional: mark as archived)
- [ ] Schedule individual import actions for new webinars
- [ ] Update schedule dates for existing upcoming webinars
- [ ] Update corresponding Events if dates changed
- [ ] Add admin notice for sync results
- [ ] Log sync activity

### 5.3 Webinar Import Action
- [ ] Create `inc/webinarjam/class-webinarjam-importer.php`
- [ ] Hook: `ma_webinarjam_import_webinar`
- [ ] Check if webinar already exists (by webinarjam_webinar_id)
- [ ] Create new LearnDash course (status: pending)
- [ ] Set course type to "Webinar"
- [ ] Import and update:
  - Course title (from API)
  - Course description (from API)
  - Presenters (repeater field)
  - Schedule dates (repeater field)
  - Registration URL
  - WebinarJam ID
  - Status: "upcoming"
- [ ] Create corresponding Event (Virtual type)
- [ ] Link Event to Course (relationship field)
- [ ] Set Event date to first schedule date
- [ ] Set Event status to "pending"
- [ ] Do NOT auto-assign CPD points (manual)
- [ ] Set import_status to "imported"
- [ ] Log import success/failure

### 5.4 Webinar Status Update Action
- [ ] Create `inc/webinarjam/class-webinarjam-status.php`
- [ ] Hook: `ma_webinarjam_update_status`
- [ ] Trigger: On webinar publish OR scheduled at webinar start time
- [ ] Update course meta: status = "live"
- [ ] Update Event button text: "Watch"
- [ ] Update Event button URL: webinar registration/join URL
- [ ] Schedule attendance check at webinar end time (+buffer)
- [ ] Send notification to registered users (optional)
- [ ] Log status change

### 5.5 Attendance Check Action
- [ ] Create `inc/webinarjam/class-webinarjam-attendance.php`
- [ ] Hook: `ma_webinarjam_check_attendance`
- [ ] Trigger: Scheduled at webinar end time
- [ ] Fetch webinar details from API (including registrants/attendance)
- [ ] Loop through registrants:
  - Check attendance parameter
  - Find matching WordPress user by email
  - If attended: Auto-complete LearnDash course
- [ ] Update course status to "replay"
- [ ] Update Event button text: "Watch Replay"
- [ ] Update Event button URL: replay URL
- [ ] Log completion for each user
- [ ] Send completion notification (optional)

---

## Section 6: Frontend - User Registration Flow ✅

### 6.1 Event Registration - Without Access
- [x] Create `inc/webinarjam/class-webinarjam-frontend.php`
- [x] Detect user without subscription/access
- [x] Hook event "Register" button click
- [x] Redirect to subscription/purchase page
- [x] Store referring event ID in session/cookie
- [x] After purchase: Redirect back to original event
- [x] Show "Register" button with access
- [x] Handle WooCommerce purchase flow integration
- [x] Cookie and transient fallback for non-authenticated users

### 6.2 Event Registration - With Access
- [x] Detect user with active subscription
- [x] On "Register" button click:
  - Auto-enroll user in LearnDash course
  - Call WebinarJam API: register user
  - Pass: email, name, course ID
- [x] Handle API registration errors
- [x] Show success message
- [x] Update user meta: registered_webinars array
- [x] AJAX-based registration flow
- [x] Error handling with user feedback
- [x] Action hooks for extension integration

### 6.3 Dynamic Button Rendering
- [x] Create shortcode for event buttons (`[webinar_button]`)
- [x] Check webinar status (upcoming/live/completed)
- [x] Check user access level
- [x] Render appropriate button:
  - "Register" (upcoming, no access) → purchase page
  - "Register" (upcoming, with access) → register & enroll
  - "Watch" (live, registered) → join URL
  - "Watch Replay" (completed) → replay URL
- [x] Add frontend styles for buttons
- [x] Add JavaScript for AJAX interactions
- [x] Add countdown timer shortcode (`[webinar_countdown]`)
- [x] Add status badge shortcode (`[webinar_status]`)
- [x] Create template functions for theme integration
- [x] Add accessibility features (ARIA, keyboard navigation)
- [x] Responsive design implementation

---

## Section 7: Frontend - Course & Publish Integration ✅

### 7.1 Publish Workflow
- [x] Hook: `transition_post_status` for webinar courses
- [x] When course published:
  - Find connected Event
  - Auto-publish Event
  - Schedule status update at webinar start time
  - Sync webinar status

### 7.2 Course Complete Button Customization
- [x] Hook: `learndash_course_complete_button` filter (or equivalent)
- [x] For webinar courses:
  - Hide/disable complete button for non-admin users on frontend
  - Show message: "Completion tracked automatically via attendance"
- [x] For admin users:
  - Keep manual complete functionality
  - Show admin notice about manual override

### 7.3 User Dashboard Enhancements
- [x] Add "Upcoming Webinars" section to user dashboard
- [x] Show registered webinars with dates
- [x] Add "Join" buttons for live webinars
- [x] Add countdown timer (optional)

---

## Section 8: Admin Interface Enhancements

### 8.1 Webinar Admin List Columns
- [ ] Add custom columns to course list (admin):
  - WebinarJam ID
  - Status (upcoming/live/replay)
  - Next Schedule Date
  - Last Sync Date
  - Registered Count
- [ ] Add filters: Status, Sync Status
- [ ] Add bulk actions: Force Sync, Test Import

### 8.2 Manual Sync Tools
- [ ] Add "Sync Now" button to options page
- [ ] Add "Force Re-import" per course (admin only)
- [ ] Add "Test Single Webinar" import tool

### 8.3 Debug & Logging
- [ ] Create admin page: "WebinarJam Logs"
- [ ] Display sync logs (last 100 entries)
- [ ] Display import logs
- [ ] Display attendance check logs
- [ ] Display API errors
- [ ] Add log export (CSV)
- [ ] Add log clear function

---

## Section 9: Error Handling & Edge Cases

### 9.1 Error Handling
- [ ] Add try-catch blocks to all API calls
- [ ] Create `class-webinarjam-error-handler.php`
- [ ] Log all errors to custom log file or database
- [ ] Send admin email notification on critical errors
- [ ] Add error recovery mechanisms (retry logic)

### 9.2 Edge Cases
- [ ] Handle webinar no longer in API (archived/deleted)
- [ ] Handle duplicate webinar IDs
- [ ] Handle missing presenter information
- [ ] Handle timezone mismatches
- [ ] Handle user without email address
- [ ] Handle API rate limits (implement exponential backoff)
- [ ] Handle conflicting event dates
- [ ] Handle manual course edits (prevent overwrite)

### 9.3 Data Validation
- [ ] Validate API responses before processing
- [ ] Validate required fields before import
- [ ] Validate dates and timezones
- [ ] Validate email addresses
- [ ] Add schema validation for API responses

---

## Section 10: Testing

### 10.1 Unit Tests
- [ ] Test API Client methods (mocked responses)
- [ ] Test Data Transformer methods
- [ ] Test Helper functions
- [ ] Test Error Handler methods

### 10.2 Integration Tests
- [ ] Test full webinar sync flow
- [ ] Test webinar import flow
- [ ] Test status update flow
- [ ] Test attendance check flow
- [ ] Test user registration flow
- [ ] Test publish workflow

### 10.3 Manual Testing Checklist
- [ ] Test API connection with valid credentials
- [ ] Test API connection with invalid credentials
- [ ] Test daily sync (force run via WP-CLI)
- [ ] Test new webinar import
- [ ] Test existing webinar update
- [ ] Test course publish → event publish
- [ ] Test user registration (with access)
- [ ] Test user registration (without access)
- [ ] Test attendance check and auto-complete
- [ ] Test status transitions (upcoming → live → replay)
- [ ] Test button rendering for each status
- [ ] Test admin columns and filters
- [ ] Test manual sync tools
- [ ] Test error logging

---

## Section 11: Documentation

### 11.1 Code Documentation
- [ ] Add PHPDoc blocks to all classes
- [ ] Add PHPDoc blocks to all methods
- [ ] Add inline comments for complex logic
- [ ] Document hooks and filters

### 11.2 User Documentation
- [ ] Create `docs/WEBINARJAM-SETUP.md` - Setup guide
- [ ] Create `docs/WEBINARJAM-USAGE.md` - Usage guide
- [ ] Document API credential acquisition
- [ ] Document webinar workflow
- [ ] Document troubleshooting steps
- [ ] Create admin guide for manual operations

### 11.3 Developer Documentation
- [ ] Document architecture and class structure
- [ ] Document available hooks and filters
- [ ] Document data flow diagrams
- [ ] Document API integration patterns
- [ ] Add code examples for customization

---

## Section 12: Optimization & Performance

### 12.1 Caching
- [ ] Implement transient caching for API responses
- [ ] Cache webinar list (12-hour expiry)
- [ ] Cache individual webinar data (6-hour expiry)
- [ ] Add cache invalidation on manual sync
- [ ] Add cache warming strategy

### 12.2 Database Optimization
- [ ] Add indexes for webinarjam_webinar_id meta queries
- [ ] Optimize queries for large datasets
- [ ] Implement pagination for admin lists
- [ ] Batch process large sync operations

### 12.3 Background Processing
- [ ] Use Action Scheduler for long-running tasks
- [ ] Queue individual imports (avoid timeout)
- [ ] Process attendance checks in batches
- [ ] Add progress indicators for admin

---

## Section 13: Security

### 13.1 API Security
- [ ] Encrypt API credentials in database
- [ ] Use nonces for all AJAX requests
- [ ] Validate and sanitize all user inputs
- [ ] Implement capability checks for admin functions
- [ ] Rate limit API calls

### 13.2 Data Security
- [ ] Sanitize all API responses
- [ ] Escape all output
- [ ] Validate email addresses before registration
- [ ] Implement CSRF protection
- [ ] Add security headers

---

## Section 14: Deployment & Maintenance

### 14.1 Activation/Deactivation
- [ ] Create activation hook: Schedule daily sync
- [ ] Create deactivation hook: Unschedule all events
- [ ] Create uninstall hook: Clean up options (optional)
- [ ] Add dependency checks on activation

### 14.2 Versioning & Updates
- [ ] Add version constant for integration
- [ ] Implement database migration system (if needed)
- [ ] Add upgrade routines for future versions
- [ ] Maintain changelog

### 14.3 Monitoring
- [ ] Add health check endpoint
- [ ] Monitor sync success rate
- [ ] Track API response times
- [ ] Alert on repeated failures

---

## Implementation Priority

### Phase 1: Core Foundation (Sections 1-4)
- Project setup
- API configuration
- Custom fields and taxonomies
- API client

### Phase 2: Automation (Section 5)
- Scheduled events system
- Sync, import, status, attendance

### Phase 3: Frontend (Sections 6-7)
- Registration flows
- Button rendering
- Course integration

### Phase 4: Polish (Sections 8-14)
- Admin interface
- Error handling
- Testing
- Documentation
- Security
- Optimization

---

## Notes & Considerations

1. **CPD Points**: Must be added manually to webinar courses (not automated)
2. **Existing Webinars**: 635 webinars available in API - initial import may take time
3. **Rate Limiting**: Consider API rate limits for bulk operations
4. **Timezone Handling**: Ensure proper timezone conversion for schedules
5. **User Matching**: Match WebinarJam registrants to WordPress users by email
6. **Manual Edits**: Protect manual course edits from being overwritten by sync
7. **Testing**: Use sandbox/test API credentials during development
8. **Dependencies**: Ensure all required plugins are active before operations

---

## Task Completion Tracking

- Total Tasks: ~180+
- Completed: 70+ (Sections 1-4)
- In Progress: 0
- Remaining: ~110+

**Last Updated:** 2026-02-13

---

## Completed Work

### Section 1 ✅ (2026-02-13)
- Created directory structure
- Created main integration class
- Created API client class with full CRUD operations
- Created scheduler class for event management
- Integrated with Core plugin loader

### Section 2 ✅ (2026-02-13)
- Created comprehensive options page with tabs
- Added API credentials management
- Added sync settings configuration
- Added advanced settings (debug mode, cache duration)
- Added statistics dashboard
- Created helper functions library
- Implemented AJAX handlers for testing and management

### Section 3 ✅ (2026-02-13)
- Created webinar course fields JSON (5 tabs, 20+ fields)
- Created webinar event fields JSON (5 fields)
- Created taxonomy manager class
- Implemented automatic term creation (webinar, replay)
- Added 8 additional helper functions
- Integrated taxonomy manager with main integration class

### Section 4 ✅ (2026-02-13)
- Created data transformer class (450+ lines)
- Implemented API-to-WordPress data mapping
- Added presenter data transformation with image sideloading
- Added schedule data transformation with timezone conversion
- Added attendee data transformation with user matching
- Implemented data validation and sanitization
- Created merge logic for updating existing courses
- Added automatic status determination
- Integrated transformer with main integration class
