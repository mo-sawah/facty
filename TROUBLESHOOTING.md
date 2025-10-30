# Facty Plugin - Troubleshooting Guide

## 🔧 Common Issues & Solutions

This guide addresses the issues you're experiencing and how to fix them.

---

## Issue #1: Email Requirement Not Working

### Problem
- Email form appears every time
- After entering email, it still asks again
- Cookie not being saved/read properly

### Solutions

#### Solution A: Check WordPress Constants
Add these to your `wp-config.php` if not already there:

```php
define('COOKIEPATH', '/');
define('COOKIE_DOMAIN', '');
```

#### Solution B: Browser Cookie Settings
1. Check browser allows cookies
2. Clear all cookies for your site
3. Try in incognito mode
4. Disable any cookie-blocking extensions

#### Solution C: Check Database
Run this query in phpMyAdmin:

```sql
SELECT * FROM wp_facty_users WHERE email = 'your@email.com';
```

If no results, the email isn't being saved.

#### Solution D: Debug Cookie
Add this code temporarily to see cookies:

```php
// In wp-content/themes/your-theme/functions.php
add_action('wp_footer', function() {
    if (current_user_can('manage_options')) {
        echo '<pre>Cookies: ';
        print_r($_COOKIE);
        echo '</pre>';
    }
});
```

---

## Issue #2: Analysis Failed / Timeout

### Problem
- Loads for 1-2 minutes then shows "Analysis Failed"
- Getting charged by Firecrawl but no results
- 404 errors in console

### Root Causes
1. **WordPress Cron Not Working** - Most common issue
2. **Firecrawl Returning Too Much Data** - AI overload
3. **API Timeout** - Request takes too long
4. **Missing Resource** - 404 error

### Solutions

#### Solution A: Fix WordPress Cron

**Check if cron is working:**
```php
// Add to functions.php temporarily
add_action('init', function() {
    if (isset($_GET['test_cron']) && current_user_can('manage_options')) {
        error_log('CRON TEST: ' . date('Y-m-d H:i:s'));
        wp_schedule_single_event(time(), 'test_cron_event');
        add_action('test_cron_event', function() {
            error_log('CRON FIRED: ' . date('Y-m-d H:i:s'));
        });
        spawn_cron();
        die('Check debug.log in wp-content');
    }
});
```

Visit: `yoursite.com/?test_cron=1`

**If cron doesn't work, add to wp-config.php:**
```php
define('DISABLE_WP_CRON', true);
```

Then setup a real cron job with your host:
```bash
*/5 * * * * cd /path/to/wordpress && php wp-cron.php > /dev/null 2>&1
```

#### Solution B: Reduce Firecrawl Data Load

In **Facty → Settings → API Configuration:**
- Set "Searches Per Claim" to **2** (instead of 3)
- Set "Maximum Claims" to **5** (instead of 10)

This reduces API load and processing time.

#### Solution C: Switch to OpenRouter Mode

OpenRouter is faster and more reliable:
1. Go to **Facty → Settings**
2. Change "Fact Check Mode" to **OpenRouter**
3. Save settings
4. Test again

#### Solution D: Increase PHP Limits

Add to `.htaccess`:
```apache
php_value max_execution_time 300
php_value max_input_time 300
php_value memory_limit 256M
```

Or in `wp-config.php`:
```php
@ini_set('max_execution_time', 300);
@ini_set('memory_limit', '256M');
```

---

## Issue #3: 404 Error in Console

### Problem
Console shows: `Failed to load resource: the server responded with a status of 404 ()`

### Cause
Missing file or incorrect path

### Solution

**Check which file is 404ing:**
1. Open browser console (F12)
2. Look at Network tab
3. Find the 404 request
4. Share the exact URL

**Common fixes:**
- Clear WordPress permalinks (Settings → Permalinks → Save)
- Check plugin is activated
- Verify all files uploaded correctly

---

## Issue #4: Progress Stuck at 0%

### Problem
- Shows "Analyzing Article... 0%"
- All steps show "Waiting..."
- Never progresses

### Solutions

#### Check WordPress Debug Log

Enable debug logging in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Then check: `wp-content/debug.log` for errors

#### Manual Cron Trigger

Add this function to test:
```php
// In functions.php
add_action('init', function() {
    if (isset($_GET['run_facty_cron']) && current_user_can('manage_options')) {
        do_action('facty_process_background', $_GET['task_id']);
        die('Cron triggered - check results');
    }
});
```

When stuck, note the task_id from browser console, then visit:
`yoursite.com/?run_facty_cron=1&task_id=facty_task_XXX`

---

## Issue #5: Not Styled Properly

### Problem
Email form or results look unstyled

### Solutions

1. **Hard Refresh Browser:** Ctrl+Shift+R (Cmd+Shift+R on Mac)
2. **Clear Plugin Cache:** Facty → Cache → Clear All Cache
3. **Check CSS Loading:** View page source, search for "facty.css"
4. **Disable Conflicting Plugins:** Temporarily disable caching plugins

---

## Debugging Checklist

Run through this checklist:

- [ ] WordPress version 5.0+ ?
- [ ] PHP version 7.4+ ?
- [ ] Plugin activated successfully?
- [ ] API keys configured and tested?
- [ ] WordPress cron working? (use test above)
- [ ] PHP max_execution_time at least 120?
- [ ] PHP memory_limit at least 128M?
- [ ] No JavaScript errors in console?
- [ ] Cookies enabled in browser?
- [ ] Debug.log showing any errors?
- [ ] Tried switching to OpenRouter mode?

---

## Get Detailed Error Information

### Enable Full Logging

Add this to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', true);
```

### Check These Logs
1. `wp-content/debug.log` - WordPress errors
2. Browser Console (F12) - JavaScript errors
3. Browser Network tab - Failed requests
4. Server error logs - PHP fatal errors

### Share These for Support
When asking for help, provide:
1. WordPress version
2. PHP version
3. Contents of debug.log (last 50 lines)
4. Console errors (screenshot)
5. Network tab showing 404 (screenshot)
6. Which mode (OpenRouter or Firecrawl)

---

## Quick Fixes Summary

### Email Issue
```php
// wp-config.php
define('COOKIEPATH', '/');
define('COOKIE_DOMAIN', '');
```

### Cron Issue
```php
// wp-config.php
define('DISABLE_WP_CRON', true);
// Then setup real cron with host
```

### Timeout Issue
- Switch to OpenRouter mode
- Reduce Firecrawl limits (2 searches, 5 claims)
- Increase PHP timeout to 300

### 404 Issue
- Check which URL is 404ing
- Clear permalinks
- Verify all files uploaded

---

## Testing Commands

### Test Email System
```javascript
// In browser console on any post
jQuery.post(factChecker.ajaxUrl, {
    action: 'facty_email_submit',
    email: 'test@example.com',
    nonce: factChecker.nonce
}, function(response) {
    console.log('Email Response:', response);
});
```

### Test Fact Check
```javascript
// In browser console on any post
jQuery.post(factChecker.ajaxUrl, {
    action: 'facty_check_article',
    post_id: jQuery('.fact-check-container').data('post-id'),
    nonce: factChecker.nonce
}, function(response) {
    console.log('Fact Check Response:', response);
});
```

### Check Progress
```javascript
// Replace TASK_ID with actual task ID from above
jQuery.post(factChecker.ajaxUrl, {
    action: 'facty_check_progress',
    task_id: 'TASK_ID',
    nonce: factChecker.nonce
}, function(response) {
    console.log('Progress:', response);
});
```

---

## Still Not Working?

### Last Resort Options

1. **Disable ALL other plugins** - Test if conflict exists
2. **Switch to default theme** - Test if theme conflict
3. **Re-upload plugin files** - May be corrupted
4. **Check server requirements** - Contact hosting support
5. **Enable OpenRouter only** - Simpler, more reliable

### Contact Information

When contacting support, include:
- Screenshot of error
- Contents of debug.log
- Browser console errors
- Steps to reproduce
- WordPress/PHP/MySQL versions

---

## Prevention Tips

1. **Use OpenRouter for reliability** - Firecrawl is more complex
2. **Keep limits reasonable** - Don't max out settings
3. **Monitor debug.log regularly** - Catch issues early
4. **Test after plugin updates** - Ensure compatibility
5. **Backup before changes** - Always have a restore point

---

*Last Updated: October 30, 2025*
*Facty Plugin v4.1.1*
