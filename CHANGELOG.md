# Facty Plugin - Changelog

## Version 4.1.1 (October 30, 2025) - CRITICAL BUG FIXES

### 🐛 Critical Fixes
- **FIXED:** Background processing now truly runs in background using WordPress cron
- **FIXED:** Email cookie system properly saves and reads cookies with correct parameters
- **FIXED:** Firecrawl API data truncation to prevent AI overload (500 chars per source)
- **FIXED:** Timeout handling - increased from 45s to 60s for Firecrawl requests
- **FIXED:** Transient expiration increased from 5 minutes to 10 minutes
- **FIXED:** Better error handling and logging throughout
- **FIXED:** Proper HTTP status code checking for all API requests

### ✨ Improvements
- Added detailed error logging with trace information
- Added automatic cron spawning after scheduling tasks
- Added fallback error messages when task data expires
- Improved cookie setting with httponly and SameSite parameters
- Better JSON validation for Firecrawl API responses
- Enhanced progress tracking with more reliable transients

### 📝 Changes
- Email submit now returns full user status in response
- Background tasks now log start/completion to debug.log
- Firecrawl sources now limited to 500 characters each (prevents overload)
- All API timeouts standardized to 60 seconds
- Error responses now include detailed trace information

### 📚 Documentation
- Added comprehensive TROUBLESHOOTING.md guide
- Added debugging commands and test procedures
- Added server requirement checks
- Added cron testing instructions

---

## Version 4.1.0 (October 30, 2025) - INITIAL COMPLETE RELEASE

### ✨ New Features
- Complete admin settings interface with all configuration options
- User management dashboard with statistics
- Cache management page with size tracking
- API connection testing in admin
- Color customization with WordPress color pickers
- Email capture form with validation
- User signup form with WordPress integration
- Progress tracking with real-time updates
- Dark/light theme support
- Mobile responsive design

### 🎨 Frontend
- Professional widget HTML template
- Email capture form UI
- Signup form UI  
- Progress display with 6 stages
- Results container with animations
- Download/share functionality
- Autoload compatibility

### ⚙️ Backend
- Complete admin interface (600+ lines)
- All 7 PHP classes implemented
- OpenRouter API integration
- Firecrawl API integration
- User tracking system
- Result caching (24 hours)
- Background processing
- AJAX handlers

### 💅 Styling
- Complete frontend CSS (1207 lines)
- Complete admin CSS (400+ lines)
- Professional color scheme
- Responsive breakpoints
- Loading animations
- Form validation styling

### 🔧 JavaScript
- Complete frontend JS (933 lines)
- Complete admin JS (300+ lines)
- API testing functionality
- Real-time validation
- Progress polling
- Color picker integration

### 📊 Features
- Dual AI modes (OpenRouter + Firecrawl)
- Smart content detection (satire/opinion/news)
- User limits and registration
- Email-based tracking
- Cookie management
- Download reports as TXT
- Share results
- Keyboard shortcuts in admin

### 🔒 Security
- Nonce validation on all AJAX
- Input sanitization
- SQL injection prevention
- XSS protection
- CSRF tokens
- Capability checks
- httponly cookies

### 📚 Documentation
- START-HERE.md quick guide
- INSTALLATION.md complete setup
- facty-analysis.md technical deep-dive
- FILE-STRUCTURE.txt visual layout
- README.md original docs

---

## Development History

### Initial Development
- Plugin concept and architecture design
- Modular class structure implementation
- OpenRouter integration with web search
- Firecrawl integration with multi-step research
- Frontend widget development
- Admin interface creation
- Testing and refinement

### Code Statistics
- **Total Lines:** ~6,340 lines
- **PHP Code:** ~3,500 lines
- **JavaScript:** ~1,233 lines
- **CSS:** ~1,607 lines
- **Files:** 17 total (13 plugin + 4 documentation)

---

## Upgrade Notes

### From 4.1.0 to 4.1.1
**Important:** This is a critical bug fix release. Please update immediately if experiencing:
- Email form appearing repeatedly
- Fact checks timing out
- "Analysis Failed" errors
- 404 errors in console

**Upgrade Steps:**
1. Backup your current plugin folder
2. Backup database (wp_facty_cache and wp_facty_users tables)
3. Deactivate plugin
4. Delete old plugin folder
5. Upload new version
6. Activate plugin
7. Test on a sample article

**Database Changes:** None - no migration needed

**Settings:** All settings preserved automatically

**Cache:** Will be automatically rebuilt

---

## Known Issues

### WordPress Cron
Some hosting providers disable WordPress cron. If background processing doesn't work:
1. Add `define('DISABLE_WP_CRON', true);` to wp-config.php
2. Setup real cron job with your host
3. See TROUBLESHOOTING.md for details

### Firecrawl Costs
Firecrawl mode can be expensive ($0.05-$0.15 per check). Recommendations:
- Start with OpenRouter mode
- Keep "Searches Per Claim" at 2-3
- Keep "Maximum Claims" at 5-10
- Monitor API usage in Firecrawl dashboard

### Browser Cookies
Some browser extensions block cookies. If email form persists:
- Disable cookie-blocking extensions
- Try incognito mode
- Check browser cookie settings
- See TROUBLESHOOTING.md for fixes

---

## Roadmap

### Version 4.2.0 (Planned)
- Redis cache support
- REST API endpoints
- Webhook notifications
- PDF report generation
- Enhanced statistics dashboard

### Version 4.3.0 (Planned)
- Multi-language support
- Additional AI providers
- Custom prompt templates
- A/B testing framework
- Advanced analytics

### Version 5.0.0 (Future)
- White-label options
- Subscription tiers
- Mobile app
- Browser extension
- API marketplace

---

## Support & Feedback

### Reporting Bugs
Please include:
- WordPress version
- PHP version
- Error messages from debug.log
- Browser console errors
- Steps to reproduce

### Feature Requests
We welcome suggestions! Please describe:
- Use case and benefit
- Expected behavior
- Priority level

### Contributing
Code contributions welcome! Please:
- Follow WordPress coding standards
- Include PHPDoc comments
- Test thoroughly
- Submit pull requests

---

## Credits

### Development
**Mohamed Sawah** - Lead Developer
Sawah Solutions - https://sawahsolutions.com

### Technologies Used
- WordPress API
- OpenRouter API
- Firecrawl API
- jQuery
- WordPress Color Picker
- Inter Font Family

### Special Thanks
- WordPress community
- OpenRouter team
- Firecrawl team
- Beta testers
- Early adopters

---

## License

GPL v2 or later

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

---

*For the latest version and documentation, visit: https://sawahsolutions.com*
