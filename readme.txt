=== Document Download Manager With Mailchimp - WordPress.org Edition ===
Contributors: gunjanjaswal
Donate link: https://gunjanjaswal.me/
Tags: document, download, excel, pdf, form, lead generation
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.0.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage Excel and PDF document downloads with user information collection via popup form. Pro version available with Mailchimp integration for email marketing.

== Description ==

Document Download Manager With Mailchimp is a powerful yet simple plugin that helps you manage your downloadable documents (Excel and PDF files) while collecting user information before allowing downloads.

This is the free version available on WordPress.org. A Pro version is available that seamlessly integrates with Mailchimp to grow your email list with every document download.

### Key Features

* **Multiple Document Types** - Support for Excel (.xlsx, .xls, .csv) and PDF (.pdf) files
* **Lead Generation** - Collect user information before allowing downloads
* **Download Tracking** - Track all downloads with user details and timestamps
* **Shortcode Support** - Easy integration with shortcodes
* **Responsive Design** - Works on all devices
* **GDPR Compliant** - Clear consent for data collection

### Pro Features

* **Mailchimp Integration** - Automatically add document downloaders to your Mailchimp audience
* **Document-based Tags** - Tag subscribers based on which documents they download
* **Sync Existing Records** - One-click sync of all existing download records to Mailchimp
* **Premium Support** - Priority support for all your questions and issues

### Mailchimp Integration Disclaimer

*This plugin is not affiliated with, endorsed, or sponsored by Mailchimp®. Mailchimp® is a registered trademark of The Rocket Science Group LLC. This plugin uses the Mailchimp API but is not certified or officially tested by Mailchimp. All Mailchimp® logos and trademarks displayed on this plugin are property of The Rocket Science Group LLC.*

### How It Works

1. Upload your Excel or PDF documents
2. Add the shortcode to any page or post
3. When users click the download button, they'll see a popup form
4. After submitting their information, they'll get access to the document
5. All download information is stored in your WordPress database

### Shortcode Usage

Use any of these shortcode formats:

`[document_download id="your-document-id"]`
`[excel_download id="your-document-id"]`
`[excel_download your-document-id]`

You can also customize the button text:

`[document_download id="your-document-id" text="Get Your Free Copy"]`

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

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release of Document Download Manager.

== Privacy Policy ==

This plugin collects user information (name, email, phone) when they request to download a document. This information is stored in your WordPress database and is not shared with any third parties. The plugin includes a consent checkbox to ensure GDPR compliance.
