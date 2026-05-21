=== Document Download Manager ===
Contributors: gunjanjaswal
Donate link: https://ko-fi.com/gunjanjaswal
Tags: document, download, pdf, form, lead-generation
Requires at least: 5.0
Tested up to: 7.0
Stable tag: 1.2.2
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage Excel and PDF document downloads with user information collection via popup form.

== Description ==

Document Download Manager is a powerful yet simple plugin that helps you manage your downloadable documents (Excel and PDF files) while collecting user information before allowing downloads.

### Key Features

* **Multiple Document Types** - Support for Excel (.xlsx, .xls, .csv) and PDF (.pdf) files
* **Lead Generation** - Collect user information before allowing downloads
* **Shortcode Support** - Easy integration with shortcodes
* **Responsive Design** - Works on all devices
* **GDPR Compliant** - Clear consent for data collection


### Pro Version Features

* **Mailchimp Integration** - Seamlessly connect with Mailchimp email marketing service
* **Automatic List Building** - Automatically add document downloaders to your email lists
* **One-Click Sync** - Sync existing download records to your Mailchimp list with one click
* **Button Color Customization** - Change download button colors to match your brand
* **Custom Button Text** - Personalize button text for different documents
* **Modal Title Customization** - Customize the popup form title text
* **Delete Records** - Ability to delete individual download records for GDPR compliance
* **Export CSV** - Export all download records to CSV file for analysis
* **Premium Support** - Priority support from our team

[Upgrade to Pro](https://checkout.freemius.com/plugin/19168/plan/31773/) | [Support on Ko-fi](https://ko-fi.com/gunjanjaswal)


### How It Works

1. Upload your Excel or PDF documents
2. Add the shortcode to any page or post
3. When users click the download button, they'll see a popup form
4. After submitting their information, they'll get access to the document
5. All download information is stored in your WordPress database

### Shortcode Usage

Use the shortcode `[docdownman_document_download]` to display a download button for your documents.

**Basic Usage:**
`[docdownman_document_download id="document-1"]`

**Custom Button Text:**
`[docdownman_document_download id="your-document-id" text="Get Your Free Copy"]`

== Installation ==

1. Upload the `document-download-manager` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to 'Document Downloads' in your admin menu to add documents
4. Use the shortcodes to display download buttons on your site

== Frequently Asked Questions ==

= What file types are supported? =

The plugin supports Excel files (.xlsx, .xls, .csv) and PDF files (.pdf).

= How do I add a new document? =

Go to Document Downloads in your WordPress admin menu, enter the document title and URL, then save.

= Can I customize the form fields? =

The current version uses a standard form with name, email, and phone fields. Future versions will include form customization.

= Is this plugin GDPR compliant? =

Yes, the plugin includes a consent checkbox and clear information about how the data will be used.

== Screenshots ==

1. Admin interface for managing documents
2. Download button on the front-end
3. User information popup form
4. Download records page


== Changelog ==

= 1.2.2 =
* Updated "Tested up to" to WordPress 7.0
* Added direct file access protection (ABSPATH guard) to all include files
* Replaced Buy Me a Coffee links with Ko-fi (https://ko-fi.com/gunjanjaswal)

= 1.2.1 =
* Fixed WordPress coding standards: Added proper prefixes to all global variables in uninstall.php
* Variable names now use 'docdownman_' prefix for compliance
* Improved code quality and WordPress.org plugin check compatibility

= 1.2.0 =
* Updated for WordPress 6.9 compatibility
* Updated minimum PHP requirement to 7.4
* Added proper plugin headers (Requires at least, Requires PHP, Tested up to)
* Enhanced WordPress coding standards compliance
* Verified compatibility with WordPress 6.9 features

= 1.1.0 =
* Added Upgrade to Premium and donation links in plugin admin page
* Added plugin action links on wp-admin/plugins.php page
* Updated author URL to www.gunjanjaswal.me
* Updated plugin URL to GitHub repository (https://github.com/gunjanjaswal/Document-Download-Manager-Free)
* Updated upgrade links to use Freemius checkout URL
* Removed external services section for WordPress.org compliance
* Cleaned up documentation and removed legacy shortcode references
* Removed unused premium files for WordPress.org compliance
* Fixed WordPress coding standards warnings and nonce verification issues
* Enhanced plugin home page with prominent support and upgrade links

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.2.2 =
Compatibility with WordPress 7.0, security hardening, and donation link updated to Ko-fi.

= 1.2.1 =
Coding standards fix for WordPress.org plugin check compliance.

= 1.2.0 =
Compatibility update for WordPress 6.9. Requires PHP 7.4 or higher.

= 1.1.0 =
Added support links and updated URLs. Enhanced admin interface with upgrade and donate options.

= 1.0.0 =
Initial release of Document Download Manager.

== Privacy Policy ==

This plugin collects user information (name, email, phone) when they request to download a document. This information is stored in your WordPress database and is not shared with any third parties. The plugin includes a consent checkbox to ensure GDPR compliance.
