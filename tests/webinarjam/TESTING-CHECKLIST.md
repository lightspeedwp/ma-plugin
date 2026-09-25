# WebinarJam Integration Testing Checklist

**Version:** 1.0.0  
**Last Updated:** 2026-02-17

This document provides a comprehensive manual testing checklist for the WebinarJam integration.

---

## Pre-Testing Setup

### Requirements
- [ ] WordPress 6.5+ installed
- [ ] LearnDash LMS active
- [ ] Events Calendar Pro active
- [ ] Advanced Custom Fields or Secure Custom Fields active
- [ ] ma-plugin active
- [ ] Valid WebinarJam API credentials
- [ ] Test user accounts created (admin, instructor, student)

### Test Data Preparation
- [ ] Backup database before testing
- [ ] Note: 635 webinars available in production API
- [ ] Consider using test/sandbox API credentials if available
- [ ] Create test email addresses for registration testing

---

## 1. API Configuration Testing

### 1.1 API Credentials
- [ ] Navigate to MA Settings → WebinarJam → API Credentials tab
- [ ] Enter valid API key
- [ ] Click "Test Connection" - should show ✅ success message
- [ ] Clear API key and test - should show ❌ error message
- [ ] Enter invalid API key and test - should show ❌ error message
- [ ] Save valid credentials - should show success notice

### 1.2 Sync Settings
- [ ] Navigate to Sync Settings tab
- [ ] Enable "Auto Sync New Webinars" - save and verify
- [ ] Enable "Auto Update Existing Webinars" - save and verify
- [ ] Toggle "Sync Disabled" checkbox on different options - verify saves
- [ ] Set sync schedule to "Daily" - verify cron scheduled (use WP Crontrol plugin)
- [ ] Set sync schedule to "Twice Daily" - verify cron updated
- [ ] Set sync schedule to "Hourly" - verify cron updated

### 1.3 Advanced Settings
- [ ] Enable Debug Mode - verify debug logs appear
- [ ] Set cache duration to 6 hours - save and verify
- [ ] Set cache duration to 24 hours - save and verify
- [ ] Disable Debug Mode - verify logs stop appearing

---

## 2. Webinar Sync Testing

### 2.1 Initial Import
- [ ] Navigate to MA Settings → WebinarJam → Statistics tab
- [ ] Click "Force Sync Now" button
- [ ] Verify sync progress (may take several minutes for 635 webinars)
- [ ] Check WordPress admin → Courses → verify new webinar courses created
- [ ] Verify courses have taxonomy term "webinar" assigned
- [ ] Check logs at MA Settings → WebinarJam → Logs
- [ ] Verify sync log entries created
- [ ] Check for any error log entries

**Expected Results:**
- Courses created for all active webinars
- WebinarJam ID stored in post meta
- All custom fields populated (General, Schedule, Presenters, Settings, Registration)
- Last sync timestamp recorded

### 2.2 Update Existing Webinars
- [ ] In WebinarJam dashboard, modify a webinar (title, description, schedule)
- [ ] In WordPress, click "Force Sync Now"
- [ ] Verify webinar course updated with new data
- [ ] Verify last sync timestamp updated
- [ ] Check logs for update entry

### 2.3 Manual Edit Protection
- [ ] Edit a webinar course manually in WordPress
- [ ] Check "Prevent sync from overwriting" checkbox
- [ ] Run sync again
- [ ] Verify manual edits preserved (not overwritten)
- [ ] Check logs for "skipped" entry

### 2.4 Scheduled Sync
- [ ] Use WP-CLI to trigger cron: `wp cron event run ma_webinarjam_daily_sync`
- [ ] Verify sync executed
- [ ] Check logs for sync entries
- [ ] Use WP Crontrol to trigger next scheduled event
- [ ] Verify automatic execution

---

## 3. Status Management Testing

### 3.1 Status Transitions
- [ ] Create/identify webinar with schedule date in future - verify status "upcoming"
- [ ] Create/identify webinar with schedule date in past (within 7 days) - verify status "replay"
- [ ] Create/identify webinar with schedule date in past (>7 days) - verify status "replay"
- [ ] Use WP-CLI: `wp cron event run ma_webinarjam_status_check`
- [ ] Verify statuses updated correctly

### 3.2 Status Badges
- [ ] View webinar course in course list - verify status badge displays
- [ ] Badge colors: Upcoming (blue), Live (green), Replay (gray)
- [ ] View course single page - verify status notice displays above content
- [ ] Verify course grid shows ribbon badge

---

## 4. Event Integration Testing

### 4.1 Automatic Event Creation
- [ ] Publish a webinar course
- [ ] Check Events Calendar → verify event auto-created
- [ ] Verify event has same title as course
- [ ] Verify event date/time matches course schedule
- [ ] Verify event linked to course (custom field)
- [ ] Verify course linked to event (custom field)

### 4.2 Event Updates
- [ ] Update webinar course schedule fields
- [ ] Click "Sync Event" button (or trigger via code)
- [ ] Verify event date/time updated
- [ ] Verify event details updated

---

## 5. User Registration Testing

### 5.1 Registration with Access
- [ ] Log in as test user
- [ ] Ensure user has access to webinar course (enrolled or free access)
- [ ] View webinar course single page
- [ ] Verify registration button displays
- [ ] Click "Register for Webinar" button
- [ ] Verify AJAX success message
- [ ] Check WebinarJam dashboard - verify user registered
- [ ] Verify button changes to "You're Registered" (disabled)
- [ ] Check logs for registration entry

### 5.2 Registration without Access
- [ ] Log out or use different user
- [ ] User NOT enrolled in course
- [ ] View webinar course single page
- [ ] Verify registration button does NOT display
- [ ] OR verify message: "Enroll in course to register"

### 5.3 Join Live Webinar
- [ ] Create webinar with schedule date NOW (or very soon)
- [ ] Register test user for webinar
- [ ] Wait for webinar to go "live" (status transition)
- [ ] View course page
- [ ] Verify "Join Live Webinar" button displays
- [ ] Click button - verify opens WebinarJam room in new tab

### 5.4 Watch Replay
- [ ] Create webinar with schedule date in PAST
- [ ] Verify status "replay"
- [ ] View course page
- [ ] Verify "Watch Replay" button displays
- [ ] Click button - verify opens replay URL

---

## 6. Attendance & Auto-Completion Testing

### 6.1 Attendance Check
- [ ] Register test user for webinar
- [ ] Manually mark user as "attended" in WebinarJam
- [ ] Run attendance check: `wp cron event run ma_webinarjam_attendance_check`
- [ ] Verify attendance recorded in post meta
- [ ] Check logs for attendance entry

### 6.2 Auto-Complete Course
- [ ] Ensure "Auto complete course on attendance" enabled for webinar
- [ ] Attendance recorded for test user
- [ ] Run attendance check again
- [ ] Verify LearnDash course marked complete for user
- [ ] Verify completion certificate available (if configured)
- [ ] Check logs for completion entry

### 6.3 Auto-Complete Disabled
- [ ] Ensure "Auto complete course on attendance" DISABLED for webinar
- [ ] New user attends webinar
- [ ] Run attendance check
- [ ] Verify course NOT auto-completed
- [ ] User must complete manually

---

## 7. Admin Interface Testing

### 7.1 Custom Columns
- [ ] Navigate to WordPress admin → Courses
- [ ] Verify custom columns display: WebinarJam ID, Status, Next Date, Last Sync, Registered
- [ ] Verify status column shows colored badges
- [ ] Verify next date shows countdown (if upcoming)
- [ ] Verify last sync shows human time diff
- [ ] Verify registered shows user count

### 7.2 Admin Filters
- [ ] Use "Webinar Status" dropdown filter
- [ ] Filter by "Upcoming" - verify only upcoming webinars shown
- [ ] Filter by "Live" - verify only live webinars shown
- [ ] Filter by "Replay" - verify only replay webinars shown
- [ ] Use "Sync Status" dropdown filter
- [ ] Filter by "Synced" - verify only synced courses shown
- [ ] Filter by "Pending Sync" - verify only pending courses shown
- [ ] Filter by "Sync Error" - verify only error courses shown

### 7.3 Bulk Actions
- [ ] Select multiple webinar courses
- [ ] Choose "Force Sync Webinar" bulk action
- [ ] Click Apply
- [ ] Verify all selected courses synced
- [ ] Check logs for bulk sync entries
- [ ] Select courses and choose "Test Import"
- [ ] Verify test import executed (check logs)

### 7.4 Quick Sync
- [ ] Hover over a webinar course in list
- [ ] Click "Quick Sync" row action
- [ ] Verify AJAX success message appears
- [ ] Verify course data updated
- [ ] Check logs for sync entry

---

## 8. User Dashboard Testing

### 8.1 Dashboard Widget
- [ ] Log in as student user
- [ ] Navigate to LearnDash dashboard or WordPress admin
- [ ] Verify "My Upcoming Webinars" widget displays
- [ ] Verify shows webinars user registered for
- [ ] Verify shows "Join Now" button for live webinars
- [ ] Verify shows countdown for upcoming webinars

### 8.2 Profile Section
- [ ] Navigate to LearnDash user profile page
- [ ] Verify "Upcoming Webinars" section displays
- [ ] Verify lists registered webinars
- [ ] Verify shows schedule dates
- [ ] Verify shows registration status

### 8.3 Shortcodes
- [ ] Create test page
- [ ] Add `[my_webinars]` shortcode
- [ ] View page as logged-in user
- [ ] Verify shows user's registered webinars
- [ ] Add `[upcoming_webinars]` shortcode to another page
- [ ] Verify shows all upcoming webinars
- [ ] Test shortcode attributes: `[upcoming_webinars limit="5"]`

---

## 9. Error Handling Testing

### 9.1 API Errors
- [ ] Temporarily break API credentials (wrong key)
- [ ] Trigger sync
- [ ] Verify error logged (not fatal error)
- [ ] Verify admin notification email sent (check inbox)
- [ ] Check logs for API error entry
- [ ] Restore valid credentials

### 9.2 Rate Limiting
- [ ] Trigger multiple rapid API calls (script or manual)
- [ ] Verify rate limit detection
- [ ] Verify requests paused/queued
- [ ] Check logs for rate limit entries
- [ ] Verify automatic retry after cooldown

### 9.3 Data Validation Errors
- [ ] Create malformed webinar data (simulate bad API response)
- [ ] Trigger import
- [ ] Verify validation catches errors
- [ ] Verify error logged
- [ ] Verify course not created/updated with bad data

### 9.4 Duplicate Webinars
- [ ] Manually create course with same WebinarJam ID
- [ ] Trigger sync
- [ ] Verify duplicate detection
- [ ] Verify existing course used (not duplicate created)
- [ ] Check logs for duplicate handling entry

### 9.5 Archived Webinars
- [ ] Archive a webinar in WebinarJam
- [ ] Trigger sync
- [ ] Verify WordPress course status changed to "archived" (draft)
- [ ] Verify course not deleted (preserved)
- [ ] Check logs for archive entry

### 9.6 Timezone Conversion
- [ ] Create webinar with different timezone (e.g., PST)
- [ ] WordPress timezone set to different zone (e.g., EST)
- [ ] Trigger sync
- [ ] Verify schedule dates converted correctly to WordPress timezone
- [ ] Verify event dates match WordPress timezone

---

## 10. Frontend Testing

### 10.1 Button Rendering
- [ ] View upcoming webinar course page
- [ ] Verify "Register for Webinar" button displays (if user has access)
- [ ] Verify button styled correctly (plugin CSS)
- [ ] View live webinar course page
- [ ] Verify "Join Live Webinar" button displays (if registered)
- [ ] View replay webinar course page
- [ ] Verify "Watch Replay" button displays

### 10.2 Countdown Timer
- [ ] View upcoming webinar with schedule date set
- [ ] Verify countdown timer displays
- [ ] Verify countdown updates in real-time (JavaScript)
- [ ] Verify shows days, hours, minutes format

### 10.3 Responsive Design
- [ ] View webinar course page on mobile device (or browser resize)
- [ ] Verify buttons responsive
- [ ] Verify countdown timer responsive
- [ ] Verify widgets responsive

---

## 11. Logging Testing

### 11.1 Log Viewer
- [ ] Navigate to MA Settings → WebinarJam → Logs (or admin.php?page=ma-webinarjam-logs)
- [ ] Verify log entries display in table
- [ ] Verify columns: Type, Message, Context, Date
- [ ] Verify pagination works (if >50 entries)

### 11.2 Log Filtering
- [ ] Use log type dropdown filter
- [ ] Filter by "Sync" - verify only sync logs shown
- [ ] Filter by "Import" - verify only import logs shown
- [ ] Filter by "Error" - verify only error logs shown
- [ ] Filter by "API" - verify only API logs shown

### 11.3 Log Export
- [ ] Click "Export CSV" button
- [ ] Verify CSV file downloads
- [ ] Open CSV - verify columns and data correct
- [ ] Verify UTF-8 encoding (special characters display correctly)

### 11.4 Clear Logs
- [ ] Click "Clear All Logs" button
- [ ] Confirm action
- [ ] Verify all logs deleted
- [ ] Trigger new sync to regenerate logs
- [ ] Filter by type and clear - verify only that type deleted

---

## 12. Performance Testing

### 12.1 Large Dataset
- [ ] Initial import of 635 webinars
- [ ] Monitor execution time (should complete without timeout)
- [ ] Check memory usage
- [ ] Verify no PHP errors or warnings
- [ ] Check server logs for issues

### 12.2 Caching
- [ ] Trigger API call (get all webinars)
- [ ] Check if transient created (use Transients Manager plugin or query)
- [ ] Verify cache key: `ma_webinarjam_webinars`
- [ ] Trigger same call again - verify uses cache (faster, no API call)
- [ ] Wait 12 hours (or delete transient) - verify fresh API call

### 12.3 Concurrent Users
- [ ] Simulate multiple users registering simultaneously
- [ ] Verify all registrations processed correctly
- [ ] Check for race conditions or conflicts
- [ ] Verify logs show all registration attempts

---

## 13. Security Testing

### 13.1 Capability Checks
- [ ] Log in as subscriber (low privilege user)
- [ ] Attempt to access MA Settings → WebinarJam
- [ ] Verify access denied (redirect or error)
- [ ] Attempt to trigger AJAX sync action (manually craft request)
- [ ] Verify capability check fails

### 13.2 Nonce Validation
- [ ] Inspect AJAX request for sync action
- [ ] Verify nonce included in request
- [ ] Manually remove or change nonce
- [ ] Attempt request - verify fails with error
- [ ] Send expired nonce - verify fails

### 13.3 Input Sanitization
- [ ] Enter malicious input in API key field (e.g., `<script>alert('xss')</script>`)
- [ ] Save settings
- [ ] Verify input sanitized/escaped
- [ ] View page source - verify no script tags rendered
- [ ] Test SQL injection in search/filter fields (e.g., `' OR '1'='1`)
- [ ] Verify queries use prepared statements (no injection)

---

## 14. Compatibility Testing

### 14.1 Plugin Conflicts
- [ ] Test with other popular WordPress plugins active
- [ ] Test with caching plugins (WP Rocket, W3 Total Cache)
- [ ] Test with security plugins (Wordfence, Sucuri)
- [ ] Verify no JavaScript errors in console
- [ ] Verify no CSS conflicts

### 14.2 Theme Compatibility
- [ ] Test with default WordPress theme (Twenty Twenty-Four)
- [ ] Test with custom theme
- [ ] Verify buttons render correctly
- [ ] Verify widgets display correctly
- [ ] Verify shortcodes render correctly

### 14.3 Browser Compatibility
- [ ] Test in Chrome - verify all features work
- [ ] Test in Firefox - verify all features work
- [ ] Test in Safari - verify all features work
- [ ] Test in Edge - verify all features work
- [ ] Check browser console for errors

---

## 15. Edge Cases & Stress Testing

### 15.1 No Webinars
- [ ] API returns empty array (no webinars)
- [ ] Trigger sync
- [ ] Verify no errors
- [ ] Verify message: "No webinars found"

### 15.2 API Timeout
- [ ] Simulate slow API response (use testing proxy/tool)
- [ ] Trigger sync
- [ ] Verify timeout handled gracefully
- [ ] Verify retry logic kicks in
- [ ] Check logs for timeout entry

### 15.3 Database Errors
- [ ] Simulate database connection issue (temporarily break DB)
- [ ] Trigger sync
- [ ] Verify errors caught and logged
- [ ] Restore DB connection
- [ ] Verify sync resumes normally

### 15.4 Missing Dependencies
- [ ] Deactivate LearnDash
- [ ] Trigger sync
- [ ] Verify dependency check fails gracefully
- [ ] Verify admin notice shown
- [ ] Reactivate LearnDash

---

## Test Results Summary

**Test Date:** _______________  
**Tested By:** _______________  
**Environment:** Production / Staging / Local  

### Results
- Total Tests: ~150+
- Passed: _____
- Failed: _____
- Blocked: _____

### Critical Issues
1. _____________________________________
2. _____________________________________
3. _____________________________________

### Non-Critical Issues
1. _____________________________________
2. _____________________________________
3. _____________________________________

### Notes
_____________________________________
_____________________________________
_____________________________________

---

## Automated Testing Notes

While this checklist covers manual testing, consider implementing:

1. **Unit Tests** - Test individual class methods in isolation
2. **Integration Tests** - Test class interactions and full workflows
3. **End-to-End Tests** - Test complete user workflows with Playwright or Selenium

See `tests/webinarjam/README.md` for automated testing setup instructions.

---

**Document Version:** 1.0.0  
**Last Updated:** 2026-02-17  
**Status:** Ready for Testing
