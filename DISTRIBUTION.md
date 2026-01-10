# Distribution Guide

## Creating a Release Package

1. **Navigate to the addon directory:**
   ```bash
   cd /path/to/whmcs/modules/addons/ai_ticket_assistant
   ```

2. **Create a ZIP file:**
   ```bash
   zip -r ai_ticket_assistant_v1.0.0.zip . -x "*.git*" "*.DS_Store" ".license_cache"
   ```

3. **Upload to your website:**
   - Upload the ZIP file to your downloads page
   - Example: `https://yourdomain.com/downloads/ai_ticket_assistant_v1.0.0.zip`

## WHMCS Marketplace Submission (Free Product)

Update your marketplace submission:

**Payment Type:** Free

**Download URL:**
```
https://yourdomain.com/downloads/ai_ticket_assistant_v1.0.0.zip
```

Or use GitHub releases:
```
https://github.com/yourusername/ai-ticket-assistant/releases/download/v1.0.0/ai_ticket_assistant_v1.0.0.zip
```

## GitHub Release (Recommended)

1. **Create GitHub repository:**
   ```bash
   git init
   git add .
   git commit -m "Initial release v1.0.0"
   git remote add origin https://github.com/yourusername/ai-ticket-assistant.git
   git push -u origin main
   ```

2. **Create a release:**
   - Go to: https://github.com/yourusername/ai-ticket-assistant/releases/new
   - Tag: `v1.0.0`
   - Title: `AI Ticket Assistant v1.0.0`
   - Upload the ZIP file
   - Publish release

3. **Get download URL:**
   ```
   https://github.com/yourusername/ai-ticket-assistant/releases/download/v1.0.0/ai_ticket_assistant_v1.0.0.zip
   ```

## Files to Include in Distribution

✅ **Include:**
- `ai_ticket_assistant.php` (main file)
- `hooks.php`
- `lib/` (all files)
- `README.md`
- `LICENSE`

❌ **Exclude:**
- `.git/`
- `.gitignore`
- `.DS_Store`
- `.license_cache`
- `DISTRIBUTION.md` (this file)
- Any test files

## Version Updates

When releasing updates:

1. Update version number in `ai_ticket_assistant.php`
2. Update `README.md` changelog
3. Create new ZIP: `ai_ticket_assistant_v1.1.0.zip`
4. Upload to your site
5. Update WHMCS Marketplace listing
6. Create GitHub release (if using GitHub)

## Support

Since this is free and open source, consider:
- GitHub Issues for bug reports
- GitHub Discussions for support questions
- Or provide email support if you prefer
