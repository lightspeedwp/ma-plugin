# WebinarJam Integration Testing

This directory contains testing resources for the WebinarJam integration.

## Testing Documentation

- **[TESTING-CHECKLIST.md](TESTING-CHECKLIST.md)** - Comprehensive manual testing checklist with 150+ test cases

## Manual Testing

Follow the [TESTING-CHECKLIST.md](TESTING-CHECKLIST.md) for step-by-step manual testing instructions covering:

- API configuration
- Webinar sync (initial import, updates, scheduled)
- Status management (transitions, badges)
- Event integration
- User registration flow
- Attendance & auto-completion
- Admin interface (columns, filters, bulk actions)
- User dashboard & shortcodes
- Error handling & edge cases
- Frontend rendering
- Logging system
- Performance & security
- Browser & plugin compatibility

## Automated Testing (Future Implementation)

### Unit Tests

Unit tests should cover individual class methods in isolation:

```php
// Example: Test API client methods with mocked responses
class WebinarJam_API_Client_Test extends WP_UnitTestCase {
    public function test_get_all_webinars_returns_array() {
        // Mock API response
        // Assert return type is array
    }
    
    public function test_get_webinar_with_invalid_id_returns_error() {
        // Mock error response
        // Assert WP_Error returned
    }
}
```

**Classes to test:**
- `WebinarJam_API_Client` - API communication
- `WebinarJam_Data_Transformer` - Data transformation
- `WebinarJam_Error_Handler` - Error handling and validation
- Helper functions from `helper-functions.php`

### Integration Tests

Integration tests should verify class interactions and workflows:

```php
// Example: Test full sync workflow
class WebinarJam_Sync_Integration_Test extends WP_UnitTestCase {
    public function test_sync_creates_new_courses() {
        // Mock API with test webinar data
        // Trigger sync
        // Assert courses created with correct data
        // Assert custom fields populated
        // Assert taxonomy terms assigned
    }
    
    public function test_sync_updates_existing_courses() {
        // Create existing course with webinar ID
        // Mock API with updated data
        // Trigger sync
        // Assert course updated
        // Assert last sync timestamp updated
    }
}
```

**Workflows to test:**
- Full webinar sync (new + updates)
- Course publish → event creation
- User registration → WebinarJam API
- Attendance check → course completion
- Status transitions
- Error recovery and retries

### End-to-End Tests

E2E tests should simulate real user interactions using browser automation:

**Framework Options:**
- WordPress Playwright (preferred) - See plugin docs: [/.github/instructions/playwright-tests.instructions.md](../../.github/instructions/playwright-tests.instructions.md)
- Codeception
- Cypress

**Example E2E Tests:**
```javascript
// Example Playwright test
test('User can register for webinar', async ({ page }) => {
  // Login as student
  await page.goto('/wp-login.php');
  await page.fill('#user_login', 'student@example.com');
  await page.fill('#user_pass', 'password');
  await page.click('#wp-submit');
  
  // Navigate to webinar course
  await page.goto('/courses/test-webinar/');
  
  // Click register button
  await page.click('.ma-webinar-register-btn');
  
  // Verify success message
  await expect(page.locator('.ma-registration-success')).toBeVisible();
});
```

**Scenarios to test:**
- Admin configures API credentials and triggers sync
- Admin filters and bulk syncs webinar courses
- Student views course and registers for webinar
- Student joins live webinar
- Student watches replay
- Attendance check auto-completes course

## Test Environment Setup

### Requirements
- WordPress test environment (wp-env or Local by Flywheel)
- Test database (separate from production)
- WebinarJam sandbox/test API credentials
- Test user accounts (admin, instructor, student)

### Setup Instructions

1. **Install WordPress Test Framework** (for unit/integration tests)
   ```bash
   bin/install-wp-tests.sh wordpress_test root '' localhost latest
   ```

2. **Install PHPUnit**
   ```bash
   composer require --dev phpunit/phpunit ^9.5
   ```

3. **Install Playwright** (for E2E tests)
   ```bash
   npm install --save-dev @wordpress/e2e-test-utils-playwright
   npx playwright install
   ```

4. **Configure Test Constants**
   Create `tests/bootstrap.php`:
   ```php
   <?php
   define('WEBINARJAM_TEST_API_KEY', 'test_key_here');
   define('WEBINARJAM_TEST_MODE', true);
   
   // Load WordPress test environment
   require_once '/tmp/wordpress-tests-lib/includes/bootstrap.php';
   ```

## Running Tests

### Manual Tests
```bash
# Follow TESTING-CHECKLIST.md
# Open in browser and check off each test
```

### Unit Tests (when implemented)
```bash
composer test
# or
vendor/bin/phpunit
```

### Integration Tests (when implemented)
```bash
vendor/bin/phpunit --group integration
```

### E2E Tests (when implemented)
```bash
npm run test:e2e
# or
npx playwright test
```

## Test Data

### Sample Webinar Data

Use this sample data for manual testing:

```json
{
  "webinar_id": "12345",
  "name": "Test Webinar: Introduction to Medicine",
  "description": "This is a test webinar for manual testing purposes.",
  "schedules": [
    {
      "date": "2026-03-15",
      "time": "14:00:00",
      "timezone": "America/New_York"
    }
  ],
  "presenters": [
    {
      "name": "Dr. Test Presenter",
      "email": "presenter@example.com",
      "bio": "Test presenter bio",
      "image_url": "https://via.placeholder.com/300"
    }
  ],
  "settings": {
    "registration_url": "https://example.webinarjam.com/register/12345",
    "thank_you_url": "https://example.com/thanks",
    "replay_available": true,
    "replay_url": "https://example.webinarjam.com/replay/12345"
  }
}
```

### Test User Accounts

Create these test users:
- **Admin:** `admin@test.local` (Administrator)
- **Instructor:** `instructor@test.local` (Instructor/Group Leader)
- **Student:** `student@test.local` (Subscriber)

## Continuous Integration (Future)

Consider setting up GitHub Actions workflow for automated testing:

```yaml
# .github/workflows/test-webinarjam.yml
name: WebinarJam Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.0'
      - name: Install dependencies
        run: composer install
      - name: Run tests
        run: composer test
```

## Coverage Reports

When unit tests are implemented, generate coverage reports:

```bash
vendor/bin/phpunit --coverage-html coverage/
```

View report at `coverage/index.html`

## Contributing

When adding new features to the WebinarJam integration:

1. Update [TESTING-CHECKLIST.md](TESTING-CHECKLIST.md) with new test cases
2. Create unit tests for new classes/methods
3. Add integration tests for new workflows
4. Update E2E tests if user-facing features added
5. Run full test suite before submitting PR

## Resources

- [WordPress PHPUnit Testing](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/)
- [Playwright for WordPress](https://github.com/WordPress/gutenberg/tree/trunk/packages/e2e-test-utils-playwright)
- [LightSpeed Testing Instructions](/.github/instructions/playwright-tests.instructions.md)
- [WebinarJam API Documentation](https://documentation.webinarjam.com/)

---

**Last Updated:** 2026-02-17  
**Version:** 1.0.0
