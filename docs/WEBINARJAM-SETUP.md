# WebinarJam Integration Setup Guide

**Version:** 1.0.0  
**Last Updated:** 2026-02-17  
**Plugin:** Medical Academic (ma-plugin)

This guide will walk you through setting up and configuring the WebinarJam integration for your Medical Academic WordPress site.

---

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Installation](#installation)
3. [API Configuration](#api-configuration)
4. [Initial Sync](#initial-sync)
5. [Field Configuration](#field-configuration)
6. [Sync Settings](#sync-settings)
7. [Troubleshooting](#troubleshooting)

---

## Prerequisites

Before setting up the WebinarJam integration, ensure you have:

### Required Software
- ✅ WordPress 6.5 or higher
- ✅ PHP 8.0 or higher
- ✅ MySQL 5.7 or higher

### Required Plugins
- ✅ **LearnDash LMS** - Course management system
- ✅ **The Events Calendar Pro** - Event management
- ✅ **Advanced Custom Fields (ACF)** or **Secure Custom Fields (SCF)** - Custom field management
- ✅ **Medical Academic Plugin (ma-plugin)** - Core plugin with WebinarJam integration

### WebinarJam Account
- ✅ Active WebinarJam account
- ✅ API access enabled
- ✅ API key generated (see [Getting Your API Key](#getting-your-api-key))

---

## Installation

### Step 1: Install Required Plugins

1. Navigate to **Plugins → Add New** in WordPress admin
2. Search for and install:
   - LearnDash LMS
   - The Events Calendar Pro
   - Advanced Custom Fields or Secure Custom Fields
3. Click **Activate** for each plugin

### Step 2: Install Medical Academic Plugin

The WebinarJam integration is built into the Medical Academic plugin. If not already installed:

1. Upload `ma-plugin` folder to `/wp-content/plugins/`
2. Navigate to **Plugins → Installed Plugins**
3. Find "Medical Academic" and click **Activate**

### Step 3: Verify Installation

After activation, you should see:
- **MA Settings** menu item in WordPress admin sidebar
- **WebinarJam** tab under MA Settings
- Custom fields registered for courses and events

---

## API Configuration

### Getting Your API Key

1. Log in to your [WebinarJam account](https://home.webinarjam.com/)
2. Navigate to **Account Settings → API Keys**
3. Click **Generate New API Key** or copy existing key
4. Save this key securely - you'll need it for WordPress configuration

### Configuring WordPress

1. In WordPress admin, navigate to **MA Settings → WebinarJam**
2. Click the **API Credentials** tab
3. Enter your WebinarJam API key in the **API Key** field
4. Click **Test Connection** button
   - ✅ **Success:** "API connection successful! Found X webinars."
   - ❌ **Error:** "Invalid API key" or "Connection failed"
5. If successful, click **Save Changes**

**Troubleshooting Connection Issues:**
- Verify API key is copied correctly (no extra spaces)
- Ensure your server can make outbound HTTPS requests
- Check if firewall is blocking WebinarJam API endpoints
- Contact WebinarJam support to verify API access is enabled

---

## Initial Sync

After configuring your API credentials, perform the initial synchronization to import your webinars.

### Step 1: Navigate to Statistics Tab

1. Go to **MA Settings → WebinarJam**
2. Click the **Statistics** tab
3. Review the sync statistics:
   - Total webinars in API
   - Total synced courses
   - Last sync time
   - Sync status

### Step 2: Force Initial Sync

1. Click the **Force Sync Now** button
2. Wait for the sync to complete (may take several minutes for large webinar lists)
3. You'll see a success message: "Webinar sync completed successfully"

**What happens during sync:**
- Fetches all webinars from WebinarJam API
- Creates WordPress LearnDash courses for each webinar
- Populates custom fields with webinar data
- Assigns "webinar" taxonomy term
- Creates/updates linked events in Events Calendar
- Records sync timestamp

### Step 3: Verify Sync Results

1. Navigate to **LearnDash LMS → Courses**
2. You should see new courses created for your webinars
3. Check the **WebinarJam ID** column to verify
4. Open a course and verify custom fields are populated

### Sync Duration Estimates

- **1-50 webinars:** 1-2 minutes
- **51-200 webinars:** 2-5 minutes
- **201-500 webinars:** 5-15 minutes
- **500+ webinars:** 15-30 minutes

**Note:** Large syncs may trigger server timeouts. If this occurs:
- Increase PHP `max_execution_time` to 300 seconds
- Or sync in smaller batches using admin filters

---

## Field Configuration

After initial sync, webinar courses will have custom field groups for detailed configuration.

### General Information Fields

Located in the **General** tab of course edit screen:

- **WebinarJam ID** - Unique webinar identifier (auto-populated, read-only)
- **Webinar Description** - Full description from WebinarJam
- **Registration URL** - Link for users to register
- **Thank You URL** - Redirect after registration
- **Status** - Current webinar status (upcoming, live, replay, completed)

### Schedule Fields

Located in the **Schedule** tab:

- **Next Webinar Date** - Date and time of next session
- **Timezone** - Timezone for the webinar
- **Duration** - Length in minutes
- **Is Recurring** - Whether webinar repeats
- **All Schedule Dates** - Full schedule for recurring webinars

### Presenter Fields

Located in the **Presenters** tab:

- **Presenter Name** - Name of host/presenter
- **Presenter Email** - Contact email
- **Presenter Bio** - Short biography
- **Presenter Image** - Headshot or profile photo
- **Additional Presenters** - For multi-presenter webinars (repeater field)

### Settings Fields

Located in the **Settings** tab:

- **Replay Available** - Whether replay is available after live session
- **Replay URL** - Link to replay video
- **Auto Complete on Attendance** - Auto-mark course complete when user attends
- **Prevent Sync from Overwriting** - Protect manual edits from sync
- **Last Sync Date** - Timestamp of most recent sync

### Registration Fields

Located in the **Registration** tab:

- **Registered Users** - List of WordPress users registered for webinar
- **Registration Count** - Total number of registrants
- **Attended Users** - List of users who attended
- **Attendance Count** - Total number of attendees

**Note:** Most fields are auto-populated during sync. Manual edits are preserved if "Prevent sync from overwriting" is enabled.

---

## Sync Settings

Configure automatic synchronization behavior.

### Navigate to Sync Settings

1. Go to **MA Settings → WebinarJam**
2. Click the **Sync Settings** tab

### Sync Options

#### Auto Sync New Webinars
- **Enabled (recommended):** Automatically import new webinars during scheduled sync
- **Disabled:** Only sync webinars that already exist in WordPress

#### Auto Update Existing Webinars
- **Enabled (recommended):** Update existing courses with latest data from WebinarJam
- **Disabled:** Only create new courses, don't update existing ones

**Warning:** Disabling this may cause data to become stale if webinars are updated in WebinarJam.

#### Sync Schedule
Choose how often automatic sync runs:

- **Disable** - No automatic sync (manual only)
- **Hourly** - Sync every hour (high frequency, recommended for time-sensitive webinars)
- **Twice Daily** - Sync at 12am and 12pm (moderate frequency)
- **Daily** - Sync once per day at midnight (low frequency, recommended for most sites)
- **Weekly** - Sync once per week (minimal frequency)

**Recommendation:** Use "Daily" for most sites. Use "Hourly" if webinars change frequently.

#### Per-Webinar Sync Control

You can disable sync for individual webinars:

1. Edit the webinar course in WordPress
2. Navigate to **Settings** tab
3. Check **Prevent sync from overwriting**
4. Save the course

This is useful for:
- Webinars with custom WordPress content
- Courses that have been manually edited
- Archived webinars you want to preserve as-is

---

## Advanced Settings

Configure advanced integration behavior.

### Navigate to Advanced Settings

1. Go to **MA Settings → WebinarJam**
2. Click the **Advanced** tab

### Debug Mode

- **Enabled:** Log all API requests and responses for troubleshooting
- **Disabled:** Log only errors and important events

**When to enable:**
- Troubleshooting sync issues
- Debugging registration problems
- Working with WebinarJam support

**Note:** Disable debug mode in production to reduce log bloat.

### Cache Duration

Controls how long API responses are cached:

- **6 hours** - Aggressive caching, fewer API calls
- **12 hours (recommended)** - Balanced caching
- **24 hours** - Maximum caching, minimal API calls
- **0 hours** - No caching, always fetch fresh data

**Recommendation:** Use 12 hours. Only reduce if you need near real-time data.

### Clear Cache

Click **Clear Cache Now** to:
- Delete all cached API responses
- Force fresh data fetch on next sync
- Useful after making changes in WebinarJam

---

## Post-Setup Configuration

### Step 1: Configure Course Access

WebinarJam courses are LearnDash courses, so configure access:

1. Edit a webinar course
2. Navigate to **Settings → Course Access Settings**
3. Choose access mode:
   - **Open** - Free access for all users
   - **Free** - Requires enrollment but free
   - **Buy Now** - Paid access
   - **Recurring** - Subscription access
4. Save course

**Recommendation:** Most medical webinars use "Free" or "Buy Now" access.

### Step 2: Configure Registration Behavior

1. Edit webinar course
2. Navigate to **Settings** tab (WebinarJam fields)
3. Review **Registration URL** - this is where users will register
4. Review **Thank You URL** - where users go after registration
5. Enable/disable **Auto Complete on Attendance**

### Step 3: Link to Events Calendar

Webinar courses auto-create linked events:

1. Navigate to **Events → All Events**
2. Find the event matching your webinar
3. Edit event and verify:
   - Date/time matches course schedule
   - Event linked to course (custom field)
   - Event location set to "Online"

### Step 4: Test User Registration

1. Log in as a test student user
2. Navigate to a webinar course
3. Verify you can register via the registration button
4. Check WebinarJam dashboard to confirm registration

---

## Troubleshooting

### API Connection Fails

**Symptoms:** "Invalid API key" or "Connection failed" error

**Solutions:**
1. Verify API key is correct (no spaces before/after)
2. Check WebinarJam account status is active
3. Verify API access is enabled in WebinarJam settings
4. Test your server's outbound HTTPS connectivity
5. Check firewall rules for blocking WebinarJam domains
6. Contact hosting provider about API request restrictions

### Sync Completes But Creates No Courses

**Symptoms:** "Sync successful" but no courses appear

**Solutions:**
1. Check if you have any webinars in WebinarJam
2. Verify LearnDash LMS is active
3. Check WordPress error logs for PHP errors
4. Navigate to **MA Settings → WebinarJam → Logs** and check for errors
5. Verify ACF/SCF is active and field groups registered
6. Try clicking "Force Sync Now" again

### Sync Times Out

**Symptoms:** White screen, "504 Gateway Timeout", or incomplete sync

**Solutions:**
1. Increase PHP `max_execution_time` to 300 seconds:
   - Add to wp-config.php: `set_time_limit(300);`
   - Or contact hosting provider to increase limit
2. Increase PHP `memory_limit` to 256M or higher
3. Sync smaller batches using admin filters:
   - Filter by status (upcoming only)
   - Then sync other statuses separately

### Custom Fields Don't Display

**Symptoms:** Field tabs missing on course edit screen

**Solutions:**
1. Verify ACF or SCF is active
2. Re-save permalinks (Settings → Permalinks → Save)
3. Check that field JSON files exist in `scf-json/webinarjam/`
4. Deactivate and reactivate ma-plugin
5. Clear field cache if using ACF Pro

### Registration Button Doesn't Appear

**Symptoms:** No registration button on course page

**Solutions:**
1. Verify user is logged in
2. Verify user has access to the course (enrolled or open access)
3. Check that registration URL is populated in course fields
4. Verify webinar status is "upcoming" or "live" (not completed)
5. Clear browser cache and WordPress object cache
6. Check browser console for JavaScript errors

### Users Can't Register

**Symptoms:** Registration button appears but doesn't work

**Solutions:**
1. Check browser console for JavaScript errors
2. Verify AJAX requests are sent successfully (Network tab)
3. Check for nonce validation errors in PHP error logs
4. Verify WebinarJam API is accessible
5. Check WebinarJam API rate limits
6. Navigate to **Logs** page and filter by "Registration" type

### Attendance Not Recording

**Symptoms:** Users attend but course doesn't auto-complete

**Solutions:**
1. Verify "Auto Complete on Attendance" is enabled for course
2. Check that attendance check cron is scheduled (use WP Crontrol plugin)
3. Manually trigger attendance check: `wp cron event run ma_webinarjam_attendance_check`
4. Verify user email in WordPress matches WebinarJam registration email
5. Check **Logs** page filtered by "Attendance" type

### Events Not Created

**Symptoms:** Course synced but no linked event appears

**Solutions:**
1. Verify Events Calendar Pro is active
2. Verify course is published (not draft)
3. Check that schedule date field is populated
4. Manually re-publish the course to trigger event creation
5. Check **Logs** page for event creation errors

---

## Getting Help

If you continue to experience issues:

1. **Check Logs:** Navigate to **MA Settings → WebinarJam → Logs** and review error entries
2. **Enable Debug Mode:** Turn on debug mode to capture detailed information
3. **Collect Information:**
   - WordPress version
   - PHP version
   - Plugin versions
   - Error log entries
   - Screenshots of issue
4. **Contact Support:**
   - Email: support@lightspeedwp.agency
   - Include all information collected above
   - Provide steps to reproduce issue

---

## Additional Resources

- **[Usage Guide](WEBINARJAM-USAGE.md)** - Day-to-day usage instructions
- **[Developer Documentation](WEBINARJAM-DEVELOPER.md)** - For developers and customization
- **[Testing Checklist](../tests/webinarjam/TESTING-CHECKLIST.md)** - Comprehensive testing guide
- **[WebinarJam API Documentation](https://documentation.webinarjam.com/)** - Official API docs

---

**Document Version:** 1.0.0  
**Last Updated:** 2026-02-17  
**Plugin Version:** 1.0.0
