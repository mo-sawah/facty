# Facty Plugin - Installation & Setup Guide

## 📦 Complete Package Contents

Your Facty plugin is now **100% complete** with all required files:

### Core Files
- ✅ `facty.php` - Main plugin loader
- ✅ `README.md` - Documentation

### Backend Classes (includes/)
- ✅ `class-facty-core.php` - Core functionality
- ✅ `class-facty-admin.php` - **NEW** - Admin settings page
- ✅ `class-facty-ajax.php` - AJAX handlers
- ✅ `class-facty-analyzer.php` - OpenRouter fact-checking
- ✅ `class-facty-firecrawl.php` - Firecrawl deep research
- ✅ `class-facty-users.php` - User management
- ✅ `class-facty-cache.php` - Caching system

### Frontend Assets (assets/)
- ✅ `assets/css/facty.css` - Widget styling
- ✅ `assets/css/admin.css` - **NEW** - Admin styling
- ✅ `assets/js/facty.js` - Frontend JavaScript
- ✅ `assets/js/admin.js` - **NEW** - Admin JavaScript

### Templates (templates/)
- ✅ `templates/fact-checker-widget.php` - **NEW** - Frontend widget HTML

---

## 🚀 Installation Steps

### Step 1: Create Directory Structure
```
wp-content/plugins/facty/
├── facty.php
├── README.md
├── includes/
│   ├── class-facty-core.php
│   ├── class-facty-admin.php          ← NEW
│   ├── class-facty-ajax.php
│   ├── class-facty-analyzer.php
│   ├── class-facty-firecrawl.php
│   ├── class-facty-users.php
│   └── class-facty-cache.php
├── templates/
│   └── fact-checker-widget.php        ← NEW
└── assets/
    ├── css/
    │   ├── facty.css
    │   └── admin.css                  ← NEW
    └── js/
        ├── facty.js
        └── admin.js                   ← NEW
```

### Step 2: Upload Files
1. Upload the entire `facty` folder to `wp-content/plugins/`
2. Ensure all file permissions are correct (644 for files, 755 for directories)

### Step 3: Activate Plugin
1. Go to WordPress Admin → Plugins
2. Find "Facty" in the plugin list
3. Click "Activate"
4. Database tables will be created automatically:
   - `wp_facty_cache` - Stores fact-check results
   - `wp_facty_users` - Tracks user usage

---

## ⚙️ Configuration

### Step 1: Access Settings
- Navigate to: **WordPress Admin → Facty → Settings**

### Step 2: API Configuration

#### Option A: OpenRouter Mode (Recommended for Quick Start)
1. **Get API Key:**
   - Visit https://openrouter.ai
   - Sign up for an account
   - Generate an API key
   
2. **Configure Plugin:**
   - Mode: Select "OpenRouter (Quick Web Search)"
   - API Key: Enter your OpenRouter key
   - Model: Select "OpenAI GPT-4o" (recommended)
   - Click "Test API Connection" to verify

#### Option B: Firecrawl Mode (For Deep Research)
1. **Get Both API Keys:**
   - OpenRouter: https://openrouter.ai (for AI analysis)
   - Firecrawl: https://firecrawl.dev (for source scraping)
   
2. **Configure Plugin:**
   - Mode: Select "Firecrawl (Deep Research)"
   - OpenRouter API Key: Enter key
   - Firecrawl API Key: Enter key
   - Searches Per Claim: 3 (recommended)
   - Maximum Claims: 10 (recommended)

### Step 3: Customize Appearance
1. **Theme Mode:**
   - Light Theme (default)
   - Dark Theme

2. **Color Customization:**
   - Primary Color: #3b82f6 (Blue)
   - Success Color: #059669 (Green)
   - Warning Color: #f59e0b (Orange)
   - Background Color: #f8fafc (Light Gray)

### Step 4: User Management
1. **Require Email:** ☑ Checked (recommended)
2. **Free Usage Limit:** 5 checks per visitor
3. **Terms & Privacy URLs:** Add your policy pages

### Step 5: Save Settings
- Click "Save Settings"
- Plugin is now ready to use!

---

## 🎯 How It Works

### For Visitors (Frontend)
1. Visitor reads an article on your site
2. They see the "AI Fact Checker" widget at the bottom
3. They click "Check Facts"
4. If email required: Enter email → Get report
5. Real-time progress shows analysis stages
6. Detailed results appear with:
   - Accuracy score (0-100)
   - Issues identified
   - Verified claims
   - Sources used
7. Can download or share results

### For You (Admin)
1. **Dashboard:** View statistics
   - Total users
   - Registered vs free users
   - Cache statistics

2. **Users Page:** Monitor user activity
   - Email addresses
   - Usage counts
   - Registration status
   - Last activity

3. **Cache Page:** Manage cached results
   - View cache size
   - Clear cache if needed
   - Automatic 24-hour expiration

---

## 🔒 Security Features

### Built-in Protection
- ✅ Nonce validation on all AJAX requests
- ✅ Input sanitization and validation
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS protection (proper escaping)
- ✅ CSRF token verification
- ✅ Capability checks (admin only for settings)

### Rate Limiting
- Free users: Limited to configured number
- Registered users: Unlimited
- IP-based tracking for anonymous users

---

## 📊 API Cost Estimates

### OpenRouter Mode
- **Cost:** ~$0.005-$0.02 per fact check
- **Speed:** 15-30 seconds
- **Accuracy:** Very High

### Firecrawl Mode
- **Cost:** ~$0.05-$0.15 per fact check
- **Speed:** 30-60 seconds
- **Accuracy:** Exceptional (with source scraping)

*Note: Costs vary based on article length and model selected*

---

## 🐛 Troubleshooting

### Issue: "API key not configured"
**Solution:** 
1. Go to Facty → Settings
2. Enter your OpenRouter API key
3. Click "Test API Connection"
4. Save settings

### Issue: Widget not appearing
**Solution:**
1. Check if plugin is activated
2. Verify "Enable Fact Checker" is checked in settings
3. Test on a single post page (not homepage)
4. Clear cache if using caching plugin

### Issue: "Usage limit exceeded"
**Solution:**
- User has reached free limit
- They need to register for unlimited access
- Or admin can increase limit in settings

### Issue: Progress stuck
**Solution:**
1. Check browser console for errors
2. Verify API keys are valid
3. Check WordPress debug.log for PHP errors
4. Try clearing plugin cache

### Issue: Results not caching
**Solution:**
1. Check database table exists: `wp_facty_cache`
2. Verify PHP has write permissions
3. Go to Facty → Cache to check statistics

---

## 🔧 Developer Hooks & Filters

### Available Filters
```php
// Customize widget description
add_filter('facty_widget_description', function($description) {
    return 'Custom description text';
});

// Modify fact-check results
add_filter('facty_results', function($results, $post_id) {
    // Customize results
    return $results;
}, 10, 2);

// Change cache expiration (in seconds)
add_filter('facty_cache_expiration', function($seconds) {
    return 86400; // 24 hours default
});
```

### Available Actions
```php
// After fact check completes
add_action('facty_check_complete', function($post_id, $results) {
    // Do something with results
}, 10, 2);

// After user registers
add_action('facty_user_registered', function($user_id, $email) {
    // Send welcome email, etc.
}, 10, 2);
```

---

## 📈 Performance Optimization

### Caching
- Results cached for 24 hours
- Reduces API costs dramatically
- Automatic cache invalidation

### Database Indexes
The plugin creates optimized indexes on:
- `post_id` + `content_hash` (cache lookups)
- `email` (user lookups)
- `ip_address` (visitor tracking)

### Background Processing
- Fact-checking runs in background
- Non-blocking for users
- Progress updates via polling

---

## 🎨 Customization Examples

### Change Widget Position
```php
// In your theme's functions.php
add_filter('facty_widget_position', function() {
    return 'before_content'; // or 'after_content' (default)
});
```

### Custom Styling
```css
/* In your theme's style.css */
.fact-check-container {
    max-width: 800px;
    margin: 40px auto;
}

.fact-check-box {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
```

### Modify Free Limit Per User
```php
add_filter('facty_free_limit', function($limit, $user_email) {
    // VIP users get more checks
    $vip_users = ['vip@example.com', 'premium@example.com'];
    if (in_array($user_email, $vip_users)) {
        return 50;
    }
    return $limit;
}, 10, 2);
```

---

## 🔄 Updating the Plugin

### Manual Update
1. Backup current plugin folder
2. Backup database (especially `wp_facty_*` tables)
3. Upload new files
4. Deactivate and reactivate plugin if needed

### Database Migrations
- Plugin automatically updates database structure
- Uses WordPress `dbDelta()` for safe schema updates

---

## 📝 Changelog

### Version 4.1.0 (Current)
- ✨ **NEW:** Complete admin settings interface
- ✨ **NEW:** Frontend widget template
- ✨ **NEW:** Admin CSS and JavaScript
- ✅ Full OpenRouter integration
- ✅ Full Firecrawl deep research
- ✅ User management system
- ✅ Caching system
- ✅ Progress tracking
- ✅ Dark theme support
- ✅ Autoload compatibility

---

## 🆘 Support

### Getting Help
- **Documentation:** Review this guide and README.md
- **Website:** https://sawahsolutions.com
- **Plugin Page:** Check WordPress admin for system status

### Reporting Issues
When reporting issues, please include:
1. WordPress version
2. PHP version
3. Active theme name
4. Other active plugins
5. Error messages from browser console
6. Error messages from debug.log

---

## ✅ Post-Installation Checklist

- [ ] Plugin activated successfully
- [ ] Database tables created (check phpMyAdmin)
- [ ] API keys configured and tested
- [ ] Widget appears on single posts
- [ ] Test fact check completes successfully
- [ ] Results display correctly
- [ ] Email capture form works (if enabled)
- [ ] Admin pages accessible and styled
- [ ] Cache statistics showing correctly
- [ ] User registration working (if enabled)

---

## 🎉 Success!

Your Facty plugin is now fully installed and configured!

**Next Steps:**
1. Write a test article
2. Run a fact check to test functionality
3. Monitor user activity in admin dashboard
4. Adjust settings based on usage patterns

**Pro Tips:**
- Start with OpenRouter mode for faster setup
- Monitor API costs in first week
- Adjust free limit based on traffic
- Enable email requirement to build user database
- Use dark theme for better readability

---

*Facty Plugin v4.1.0*  
*Developer: Mohamed Sawah - Sawah Solutions*  
*Installation Guide Last Updated: October 30, 2025*
