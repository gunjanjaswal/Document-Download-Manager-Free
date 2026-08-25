<div align="center">

# 📄 Document Download Manager

### Manage Excel & PDF downloads with a lead-capture popup form — free and open source.

[![Version](https://img.shields.io/badge/version-1.2.3-2ea44f?style=for-the-badge)](https://wordpress.org/plugins/document-download-manager/)
[![WordPress](https://img.shields.io/badge/WordPress-5.0%20–%207.0-21759b?style=for-the-badge&logo=wordpress&logoColor=white)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-%E2%89%A5%207.4-8892BF?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-GPLv2-orange?style=for-the-badge)](https://www.gnu.org/licenses/gpl-2.0.html)

**[🌐 WordPress.org](https://wordpress.org/plugins/document-download-manager/)** • **[⭐ Upgrade to Pro](https://checkout.freemius.com/plugin/19168/plan/31773/)** • **[☕ Support on Ko-fi](https://ko-fi.com/gunjanjaswal)**

</div>

---

## ✨ What it does

Visitors click a download button → a popup form collects their details → on submit, the file downloads and the lead is logged in your WordPress database. Simple, responsive, and GDPR-friendly.

## 🚀 Features

- 📁 **Multiple Document Types** — Excel (.xlsx, .xls, .csv) and PDF (.pdf)
- 📝 **Lead Generation** — Collect user information before allowing downloads
- 🔗 **Shortcode Support** — Easy integration with any page or post
- 📱 **Responsive Design** — Works on all devices
- 🔒 **GDPR Compliant** — Clear consent checkbox for data collection

## ⭐ Pro Version Features

> Want Mailchimp sync, CSV export, record deletion, and appearance customization? **[Upgrade to Pro →](https://checkout.freemius.com/plugin/19168/plan/31773/)**

### 🚀 Advanced Lead Management
- **Mailchimp Integration** - Seamlessly connect with Mailchimp email marketing service
- **Automatic List Building** - Automatically add document downloaders to your email lists
- **One-Click Sync** - Sync existing download records to your Mailchimp list with one click

### 🎨 Enhanced Customization
- **Button Color Customization** - Change download button colors to match your brand
- **Custom Button Text** - Personalize button text for different documents
- **Modal Title Customization** - Customize the popup form title text
- **Advanced Form Styling** - Complete control over form appearance

### 📊 Advanced Analytics & Management
- **Delete Records** - Ability to delete individual download records for GDPR compliance
- **Export CSV** - Export all download records to CSV file for analysis
- **Download Statistics** - Detailed analytics on download performance
- **User Management** - Advanced user data management tools

### 🛠️ Premium Features
- **Priority Support** - Get help from our expert support team
- **Regular Updates** - Access to new features and improvements
- **Advanced Documentation** - Comprehensive guides and tutorials
- **White-label Options** - Remove plugin branding (Enterprise plan)

[Upgrade to Pro](https://checkout.freemius.com/plugin/19168/plan/31773/) | [Support on Ko-fi](https://ko-fi.com/gunjanjaswal)

## Installation

1. Upload the `document-download-manager` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to 'Document Downloads' in your admin menu to add documents
4. Use the shortcodes to display download buttons on your site

## Usage

Use the shortcode `[docdownman_document_download]` to display a download button for your documents.

**Basic Usage:**
```
[docdownman_document_download id="document-1"]
```

**Custom Button Text:**
```
[docdownman_document_download id="your-document-id" text="Get Your Free Copy"]
```

## How It Works

1. Upload your Excel or PDF documents
2. Add the shortcode to any page or post
3. When users click the download button, they'll see a popup form
4. After submitting their information, they'll get access to the document
5. All download information is stored in your WordPress database

## Supported File Types

- **Excel Files:** .xlsx, .xls, .xlsm, .xlsb, .csv
- **PDF Files:** .pdf


## Author

**Gunjan Jaswal**
- Website: [www.gunjanjaswal.me](https://www.gunjanjaswal.me)
- Email: hello@gunjanjaswal.me

## Support

- [Support on Ko-fi](https://ko-fi.com/gunjanjaswal)
- [Upgrade to Premium](https://checkout.freemius.com/plugin/19168/plan/31773/)

## Changelog

### Version 1.2.4
- Fixed a stray closing div in the shortcode markup that closed the surrounding page wrapper too early and broke the layout of everything after the download button. This was most visible on pages built with Elementor and other page builders.
- Removed an unclosed output buffer in the shortcode render that could interfere with page builders' own output buffering.

### Version 1.2.3
- WordPress 7.0 iframed-editor hygiene: scoped admin CSS/JS enqueues to the plugin's own admin pages.
- Shortcode-driven plugin with no editor-canvas integration, so the WP 7.0 iframed editor has no functional impact.

### Version 1.2.2
- Updated "Tested up to" to WordPress 7.0
- Added direct file access protection (ABSPATH guard) to all include files
- Replaced Buy Me a Coffee links with Ko-fi (https://ko-fi.com/gunjanjaswal)

### Version 1.2.1
- Fixed WordPress coding standards: Added proper prefixes to all global variables in uninstall.php
- Variable names now use 'docdownman_' prefix for compliance
- Improved code quality and WordPress.org plugin check compatibility

### Version 1.2.0
- Updated for WordPress 6.9 compatibility
- Updated minimum PHP requirement to 7.4
- Added proper plugin headers (Requires at least, Requires PHP, Tested up to)
- Enhanced WordPress coding standards compliance
- Verified compatibility with WordPress 6.9 features

### Version 1.1.0
- Added Upgrade to Premium and donation links in plugin admin page
- Added plugin action links on wp-admin/plugins.php page
- Updated author URL to www.gunjanjaswal.me
- Updated plugin URL to GitHub repository
- Updated upgrade links to use Freemius checkout URL
- Removed external services section for WordPress.org compliance
- Cleaned up documentation and removed legacy shortcode references
- Removed unused premium files for WordPress.org compliance
- Fixed WordPress coding standards warnings and nonce verification issues
- Enhanced plugin home page with prominent support and upgrade links

### Version 1.0.0
- Initial release

## License

This plugin is licensed under the GPL v2 or later.
