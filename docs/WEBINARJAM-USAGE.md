# WebinarJam Integration Usage Guide

**Version:** 1.0.0  
**Last Updated:** 2026-02-17  
**Plugin:** Medical Academic (ma-plugin)

This guide covers day-to-day usage of the WebinarJam integration for administrators, instructors, and students.

---

## Table of Contents

1. [Administrator Tasks](#administrator-tasks)
2. [Instructor Tasks](#instructor-tasks)
3. [Student Experience](#student-experience)
4. [Common Workflows](#common-workflows)
5. [Best Practices](#best-practices)

---

## Administrator Tasks

### Managing Webinar Courses

#### Viewing All Webinar Courses

1. Navigate to **LearnDash LMS → Courses**
2. Use the **Webinar Status** filter:
   - **All** - Show all courses
   - **Upcoming** - Show webinars not yet started
   - **Live** - Show currently running webinars
   - **Replay** - Show completed webinars with replay available
   - **Completed** - Show finished webinars
3. Use the **Sync Status** filter:
   - **All** - Show all courses
   - **Synced** - Show courses successfully synced
   - **Pending Sync** - Show courses awaiting sync
   - **Sync Error** - Show courses with sync failures

#### Custom Columns

The course list displays WebinarJam-specific columns:

- **WebinarJam ID** - Unique identifier from WebinarJam
- **Status** - Current status with colored badge
  - 🔵 Blue = Upcoming
  - 🟢 Green = Live
  - ⚫ Gray = Replay
- **Next Date** - Next scheduled session with countdown
- **Last Sync** - How long ago the course was last synced
- **Registered** - Number of registered users

### Syncing Webinars

#### Manual Sync (Single Course)

**Option 1: Quick Sync**
1. Navigate to **LearnDash LMS → Courses**
2. Hover over a webinar course
3. Click **Quick Sync** row action
4. Wait for AJAX confirmation message

**Option 2: Full Edit Sync**
1. Edit the webinar course
2. Scroll to **Settings** tab
3. Click **Sync Now** button
4. Save course

#### Manual Sync (Bulk)

1. Navigate to **LearnDash LMS → Courses**
2. Select multiple webinar courses (checkboxes)
3. Choose **Force Sync Webinar** from bulk actions dropdown
4. Click **Apply**
5. Wait for sync to complete

#### Manual Sync (All Courses)

1. Navigate to **MA Settings → WebinarJam**
2. Click **Statistics** tab
3. Click **Force Sync Now** button
4. Wait for completion message

**When to use manual sync:**
- After making changes in WebinarJam
- When course data appears outdated
- After fixing sync errors
- Before major webinar launches

### Managing Registration

#### Viewing Registered Users

1. Edit a webinar course
2. Navigate to **Registration** tab
3. View **Registered Users** field - shows all registered users
4. View **Registration Count** - total number

#### Manually Registering Users

WebinarJam registration should happen via the frontend, but you can manually register:

1. User must have course access (enrolled)
2. User clicks registration button on course page
3. Registration sent to WebinarJam API
4. User added to course registration list

Alternatively, register directly in WebinarJam dashboard, then sync attendance.

### Monitoring Attendance

#### Checking Attendance

1. Edit a webinar course
2. Navigate to **Registration** tab
3. View **Attended Users** field - shows users who attended
4. View **Attendance Count** - total attendees

#### Triggering Attendance Check

Attendance is checked automatically via scheduled task, but can trigger manually:

**Via WordPress Admin:**
1. Use WP Crontrol plugin
2. Find `ma_webinarjam_attendance_check` event
3. Click **Run Now**

**Via WP-CLI:**
```bash
wp cron event run ma_webinarjam_attendance_check
```

**What happens during attendance check:**
- Fetches attendee list from WebinarJam
- Matches attendees to WordPress users by email
- Records attendance in course meta
- Auto-completes course for attended users (if enabled)

### Auto-Completion Settings

Configure whether course auto-completes when user attends webinar:

1. Edit a webinar course
2. Navigate to **Settings** tab
3. Toggle **Auto Complete on Attendance** checkbox:
   - ✅ **Enabled** - Course auto-completes after attendance recorded
   - ⬜ **Disabled** - Users must complete course manually
4. Save course

**Use cases for auto-completion:**
- ✅ CPD/CME courses where attendance = completion
- ✅ Live training sessions
- ⬜ Courses with additional lessons/quizzes
- ⬜ Courses requiring homework assignments

### Protecting Manual Edits

If you've manually customized a webinar course and don't want sync to overwrite:

1. Edit the webinar course
2. Navigate to **Settings** tab
3. Check **Prevent sync from overwriting**
4. Save course

**What this protects:**
- Custom course title
- Custom course description
- Custom course content
- Custom field values
- All manual changes

**What still updates:**
- Nothing - course completely excluded from sync

**When to use:**
- Course heavily customized in WordPress
- Course used for custom content beyond webinar
- Archived webinars you want to preserve

### Viewing Logs

Access detailed logs of all WebinarJam integration activity:

1. Navigate to **MA Settings → WebinarJam → Logs**
   - Or directly: `wp-admin/admin.php?page=ma-webinarjam-logs`
2. Use **Log Type** filter:
   - **All** - Show all logs
   - **Sync** - Synchronization events
   - **Import** - Webinar imports
   - **Attendance** - Attendance checks
   - **Status** - Status updates
   - **Error** - Error messages
   - **API** - API calls and responses
3. Click **Export CSV** to download logs
4. Click **Clear Logs** to delete (by type or all)

**Log information:**
- **Type** - Category of event
- **Message** - Description of what happened
- **Context** - Additional data (JSON formatted)
- **Date** - When event occurred

---

## Instructor Tasks

### Creating Webinar Content

While webinar metadata comes from WebinarJam, you can add supplementary content:

1. Edit a webinar course
2. Add course content in the editor:
   - Pre-webinar instructions
   - Required readings
   - Downloadable resources
   - Post-webinar assignments
3. Add LearnDash lessons/topics if needed
4. Add quizzes for knowledge checks
5. Publish course

**Note:** Core webinar details (schedule, presenters, etc.) sync from WebinarJam and should not be manually edited unless "Prevent sync" is enabled.

### Managing Course Access

Configure who can access the webinar:

1. Edit webinar course
2. Navigate to **Settings → Course Access Settings**
3. Choose access mode:
   - **Open** - Anyone can access (recommended for free webinars)
   - **Free** - Users must enroll but it's free
   - **Buy Now** - Paid one-time purchase
   - **Recurring** - Subscription-based
   - **Closed** - Only specific users/groups
4. Configure pricing if applicable
5. Save course

### Adding Users to Course

For closed access courses:

1. Edit webinar course
2. Navigate to **LearnDash Course Settings**
3. Use **Course Access List** to add users
4. Or use **Course Groups** to add entire groups
5. Save course

### Monitoring Enrollments

1. Navigate to **LearnDash LMS → Courses**
2. Click on a webinar course
3. View **Enrolled** column for enrollment count
4. Click course → **Users** tab to see all enrolled users

---

## Student Experience

### Discovering Webinars

Students can find webinar courses through:

**Course Catalog:**
- Navigate to courses page (e.g., `/courses/`)
- Filter by "Webinar" category
- Look for status badges (Upcoming, Live, Replay)

**Dashboard Widget:**
- Login and view WordPress dashboard
- "My Upcoming Webinars" widget shows registered webinars

**LearnDash Profile:**
- Navigate to user profile
- "Upcoming Webinars" section lists registered webinars

**Shortcodes:**
Pages using webinar shortcodes display relevant webinars

### Enrolling in Course

1. Navigate to webinar course page
2. Click enrollment button (if required):
   - "Take This Course" for open access
   - "Buy Now" for paid courses
   - "Enroll" for free courses
3. Complete enrollment process
4. Return to course page

### Registering for Webinar

After enrolling in course:

1. Navigate to course page
2. View webinar details:
   - Schedule date/time
   - Presenter information
   - Description
3. Click **Register for Webinar** button
4. Confirm registration in modal/popup
5. Registration sent to WebinarJam
6. Receive confirmation email from WebinarJam
7. Button changes to **You're Registered** (disabled)

**Note:** You must be enrolled in the course to register for the webinar.

### Attending Live Webinar

On the day of the webinar:

1. Navigate to course page
2. When webinar goes live, status changes to "Live"
3. **Join Live Webinar** button appears (green)
4. Click button to open WebinarJam room in new tab
5. Attend webinar
6. Attendance automatically recorded

**Tips:**
- Join a few minutes early to test audio/video
- Check email for WebinarJam join link (backup)
- Ensure stable internet connection

### Watching Replay

After webinar completes:

1. Navigate to course page
2. If replay available, status shows "Replay"
3. **Watch Replay** button appears
4. Click button to open replay in new window
5. Watch at your convenience

**Note:** Replay availability depends on webinar settings. Not all webinars offer replays.

### Course Completion

If **Auto Complete on Attendance** is enabled:

1. Attend live webinar
2. Attendance recorded automatically
3. Course marked complete (next day after attendance check runs)
4. Certificate available (if configured)
5. Course appears in "Completed Courses" section

If auto-complete is disabled:

1. Attend live webinar
2. Complete any additional course lessons/quizzes
3. Click "Mark Complete" when done
4. Certificate available (if configured)

### Viewing Course Progress

1. Navigate to **LearnDash Dashboard** or user profile
2. View "Courses" section
3. Webinar courses show:
   - Enrollment date
   - Completion status
   - Certificate (if complete)
   - Registration status

---

## Common Workflows

### Workflow 1: Launching New Webinar

**WebinarJam (Done by marketing/admin):**
1. Create new webinar in WebinarJam
2. Configure schedule, presenters, settings
3. Set registration page
4. Set thank you page

**WordPress (Done by admin):**
1. Navigate to **MA Settings → WebinarJam → Statistics**
2. Click **Force Sync Now** to import new webinar
3. Check **Courses** list to verify import
4. Edit webinar course:
   - Set course access (open, free, paid)
   - Add supplementary content if needed
   - Configure auto-completion setting
   - Verify linked event created
5. Publish course (if not already)
6. Promote via email/social media

**Students:**
1. Enroll in course
2. Register for webinar
3. Receive WebinarJam confirmation
4. Add to calendar

### Workflow 2: Updating Existing Webinar

**WebinarJam:**
1. Edit webinar details (schedule, description, etc.)
2. Save changes

**WordPress:**
1. Wait for automatic sync (runs daily)
   - Or trigger manual sync for immediate update
2. Verify course updated with new details
3. Check linked event updated
4. Notify enrolled users of changes (if significant)

### Workflow 3: Webinar Day Operations

**Before Webinar:**
1. Verify webinar status changed to "Live"
2. Check registered user count
3. Send reminder email to registered users

**During Webinar:**
1. Host webinar in WebinarJam
2. Record session (if offering replay)
3. Take notes for follow-up

**After Webinar:**
1. End webinar in WebinarJam
2. Wait for attendance check (runs automatically)
   - Or trigger manually: `wp cron event run ma_webinarjam_attendance_check`
3. Verify attendance recorded in WordPress
4. Verify courses auto-completed (if enabled)
5. Upload replay video if available
6. Sync to update replay URL
7. Status updates to "Replay"

### Workflow 4: Managing Registrations

**Viewing Registrations:**
1. Navigate to **Courses** list
2. Check **Registered** column for counts
3. Open course → **Registration** tab for user list

**Bulk Registration Management:**
1. Export registration list (CSV from logs or custom export)
2. Filter by course/status
3. Use for reporting or external tools

**Troubleshooting Registration:**
1. User reports can't register
2. Check user is logged in
3. Verify user enrolled in course
4. Check registration URL populated
5. Verify webinar status (not "Completed")
6. Check browser console for JS errors
7. Check **Logs** page for error entries

---

## Best Practices

### Sync Management

✅ **Do:**
- Run daily automatic sync (recommended)
- Manually sync before major launches
- Monitor logs for sync errors
- Fix errors promptly

❌ **Don't:**
- Sync more frequently than hourly (unnecessary API calls)
- Edit core webinar fields manually (use "Prevent sync" if you must)
- Ignore sync errors for extended periods

### Course Configuration

✅ **Do:**
- Set appropriate course access (open for free, buy now for paid)
- Configure auto-completion based on course type
- Add supplementary content beyond webinar
- Link related courses/resources
- Use clear, descriptive course titles

❌ **Don't:**
- Make webinars paid if WebinarJam registration is free (confusing)
- Enable auto-complete for courses with required homework
- Forget to link events

### Registration Management

✅ **Do:**
- Send reminder emails before webinars
- Verify registration counts before events
- Check attendance after webinars
- Follow up with non-attendees (offer replay)

❌ **Don't:**
- Assume all enrolled users registered
- Forget to verify attendance syncs properly
- Ignore registration errors in logs

### Event Integration

✅ **Do:**
- Verify events created for all webinars
- Sync course and event schedules
- Use event calendar for promotional purposes
- Link events to courses clearly

❌ **Don't:**
- Manually edit event dates (should sync from course)
- Delete linked events (breaks connection)
- Create duplicate events manually

### User Communication

✅ **Do:**
- Send pre-webinar instructions
- Include WebinarJam join link in reminders
- Notify users of schedule changes
- Follow up with replay links
- Request feedback after webinars

❌ **Don't:**
- Assume users know how to join
- Forget to test registration flow
- Ignore user registration issues

### Logging and Monitoring

✅ **Do:**
- Review logs regularly (weekly)
- Filter by error type
- Address recurring errors
- Export logs for long-term records
- Clear old logs periodically (monthly)

❌ **Don't:**
- Ignore error logs
- Let logs grow indefinitely
- Disable logging entirely
- Delete logs before reviewing

---

## Keyboard Shortcuts

### Admin Course List

- **Quick Sync:** Hover over course → Click "Quick Sync"
- **Bulk Select:** Shift+Click to select multiple courses
- **Filter:** Use keyboard to navigate filter dropdowns

### Course Editor

- **Save:** Cmd/Ctrl + S
- **Navigate Tabs:** Tab key to move through fields
- **Toggle Checkboxes:** Space bar

---

## Mobile Usage

The WebinarJam integration is responsive and works on mobile:

**Students can:**
- ✅ Browse webinar courses
- ✅ Enroll in courses
- ✅ Register for webinars
- ✅ Join live webinars (via mobile browser or WebinarJam app)
- ✅ Watch replays

**Admins can:**
- ✅ View course lists with responsive columns
- ✅ Quick sync courses (touch-friendly)
- ✅ View logs
- ⚠️ Full course editing better on desktop

---

## Frequently Asked Questions

### Can I manually create webinar courses?

Yes, but not recommended. Courses created manually won't sync with WebinarJam. Instead:
1. Create webinar in WebinarJam first
2. Sync to WordPress
3. Then add supplementary content

### What if I delete a webinar in WebinarJam?

The course will be marked as "archived" (draft status) in WordPress during next sync. It won't be deleted, preserving student progress and enrollment.

### Can I override webinar fields in WordPress?

Yes, but changes will be overwritten on next sync unless you enable "Prevent sync from overwriting" for that course.

### Do students need a WebinarJam account?

No. Registration is handled via email. Students receive WebinarJam join link via email.

### Can I customize the registration button?

Yes. Use CSS to style `.ma-webinar-register-btn` class, or use custom templates in your theme.

### How often should I sync?

Daily is sufficient for most sites. Use hourly if webinars change frequently or you need real-time updates.

### What happens if sync fails?

- Error logged in **Logs** page
- Admin notification email sent (if critical)
- Retry attempted automatically
- Manual sync can force retry

---

## Additional Resources

- **[Setup Guide](WEBINARJAM-SETUP.md)** - Initial configuration instructions
- **[Developer Documentation](WEBINARJAM-DEVELOPER.md)** - Customization and hooks
- **[Testing Checklist](../tests/webinarjam/TESTING-CHECKLIST.md)** - QA testing guide
- **[WebinarJam Support](https://support.webinarjam.com/)** - External WebinarJam help

---

**Document Version:** 1.0.0  
**Last Updated:** 2026-02-17  
**Plugin Version:** 1.0.0
