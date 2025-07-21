=== NPS Engine ===
Contributors: Cabeza Marketing
Donate link: https://cabeza.com.br/
Tags: nps, survey, feedback, customer, automation
Requires at least: 5.8
Tested up to: 6.8
Stable tag: 1.1.0
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A complete and automated solution for managing Net Promoter Score (NPS) surveys directly within your WordPress environment.

== Description ==

The NPS Engine is a comprehensive and modular WordPress plugin that provides a robust solution for managing Net Promoter Score (NPS) surveys. It allows you to manage contacts, configure emails and SMTP, define trigger and frequency rules based on time or events, and automatically capture user responses, all within your WordPress environment.

Gain valuable insights into customer loyalty and satisfaction in an automated and efficient manner.

**Key Features:**

* **Contact Management**: Add contacts manually or import existing WordPress users.
* **Email and SMTP Settings**: Configure sender name, subject, email body, and use custom SMTP for reliable delivery.
* **Trigger Control**: Define flexible frequency rules and triggers (based on time or specific events, such as purchase completion).
* **Automatic Response Capture**: Responses are automatically attributed to the contact, with no manual identification required.
* **Basic Reports**: View overall NPS score and detailed responses for quick insights.

== Installation ==

**Installation via WordPress Dashboard:**

1.  Download the plugin's .zip file.
2.  In your WordPress admin dashboard, go to Plugins > Add New.
3.  Click Upload Plugin and select the .zip file you downloaded.
4.  Click Install Now.
5.  After installation, click Activate Plugin.

**Manual Installation (FTP):**

1.  Download the plugin's .zip file and unzip it.
2.  Connect to your WordPress server via FTP or file manager.
3.  Navigate to the `wp-content/plugins/` directory.
4.  Upload the `nps-engine` folder to this directory.
5.  In your WordPress admin dashboard, go to Plugins > Installed Plugins.
6.  Locate "NPS Engine" and click Activate.

**Important**: After initial activation or any significant updates, it is recommended to go to Settings > Permalinks and click Save Changes (even without making any modifications) to ensure the plugin's rewrite rules are updated correctly.

== Frequently Asked Questions ==

**Q: Where can I find the plugin settings?**
A: After activating the plugin, you will find a new menu item called "NPS Engine" in your WordPress admin dashboard.

**Q: My survey emails are not being sent. What should I do?**
A: Check the SMTP settings under NPS Engine > Email Settings. Ensure your SMTP server details are correct and try sending a test email. Also, check the `debug.log` file in the `wp-content/` folder for detailed error messages.

**Q: The database tables were not created. How do I fix this?**
A: Try deactivating and reactivating the plugin under Plugins > Installed Plugins. This will force the table creation routine to run. If the problem persists, check your database user's write permissions and the `debug.log`.

== Screenshots ==

1.  Email and SMTP settings page.
2.  Contact management page with success message.
3.  Survey trigger settings page.

== Changelog ==

= 1.1.0 =
* Security Hardening: Added nonce verification to all forms.
* Security Hardening: Escaped all output to prevent XSS vulnerabilities.
* Security Hardening: Improved sanitization of all inputs.
* Bugfix: Corrected several internationalization (i18n) issues, including missing text domains and translator comments.
* Bugfix: Ensured all database queries are prepared correctly using $wpdb->prepare().
* Enhancement: Updated plugin headers and readme.txt to be compliant with WordPress.org standards.

= 1.0.0 =
* Initial release of the NPS Engine plugin.
* Contact management, email/SMTP settings, trigger rules, response capture, and basic reporting functionalities.
* Modularized code structure.