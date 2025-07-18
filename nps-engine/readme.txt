NPS Engine
Contributors: Cabeza Marketing
Donate link: https://cabeza.com.br/
Tags: nps, survey, feedback, satisfaction, customer, marketing, automation
Requires at least: 5.8
Tested up to: 6.5
Stable tag: 1.0.0
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Description
The NPS Engine is a comprehensive and modular WordPress plugin that provides a robust solution for managing Net Promoter Score (NPS) surveys. It allows you to manage contacts, configure emails and SMTP, define trigger and frequency rules based on time or events, and automatically capture user responses, all within your WordPress environment. Gain valuable insights into customer loyalty and satisfaction in an automated and efficient manner.

Key Features:
Contact Management: Add contacts manually or import existing WordPress users.

Email and SMTP Settings: Configure sender name, subject, email body, and use custom SMTP for reliable delivery.

Trigger Control: Define flexible frequency rules and triggers (based on time or specific events, such as purchase completion).

Automatic Response Capture: Responses are automatically attributed to the contact, with no manual identification required.

Basic Reports: View overall NPS score and detailed responses for quick insights.

Installation
Installation via WordPress Dashboard:
Download the plugin's .zip file.

In your WordPress admin dashboard, go to Plugins > Add New.

Click Upload Plugin and select the .zip file you downloaded.

Click Install Now.

After installation, click Activate Plugin.

Manual Installation (FTP):
Download the plugin's .zip file and unzip it.

Connect to your WordPress server via FTP or file manager.

Navigate to the wp-content/plugins/ directory.

Upload the nps-engine folder (which contains all plugin files) to this directory.

In your WordPress admin dashboard, go to Plugins > Installed Plugins.

Locate "NPS Engine" and click Activate.

Important: After initial activation or any significant updates, it is recommended to go to Settings > Permalinks and click Save Changes (even without making any modifications) to ensure the plugin's rewrite rules are updated correctly.

Plugin File Structure
The "NPS Engine" plugin is modularized for better organization and maintainability. Below is the folder and file structure:

nps-engine/
├── nps-engine.php                      # Main plugin file (header, activation/deactivation, module loading)
├── includes/                           # Contains core business logic (management classes)
│   ├── class-nps-database-manager.php  # Manages the creation and removal of database tables
│   ├── class-nps-contacts-manager.php  # Logic for managing contacts (add, import, status, delete)
│   ├── class-nps-email-settings.php    # Logic for saving email and SMTP settings, and sending test emails
│   ├── class-nps-trigger-settings.php  # Logic for managing trigger rules and global frequency
│   ├── class-nps-survey-dispatcher.php # Logic for the cron job that dispatches surveys and sends survey emails
│   ├── class-nps-reports.php           # Logic for calculating and retrieving NPS report data
│   └── class-nps-helper-functions.php  # General helper functions (e.g., redirect with messages)
├── admin/                              # Contains files related to the WordPress admin dashboard
│   ├── class-nps-admin-pages.php       # Logic for rendering admin menu pages (HTML of forms and tables)
│   └── class-nps-admin-actions.php     # Logic for handling form submissions and actions (CRUD) in the admin
├── public/                             # Contains files related to the website's front-end
│   └── class-nps-public-handlers.php   # Logic for the NPS survey endpoint (receiving responses) and the thank you page
├── assets/                             # Contains static files (CSS, JavaScript, images)
│   ├── css/                            # For future custom CSS files
│   └── js/                             # For future custom JavaScript files
└── languages/                          # For translation files (.pot, .po, .mo)


Frequently Asked Questions
Q: Where can I find the plugin settings?
A: After activating the plugin, you will find a new menu item called "NPS Engine" in your WordPress admin dashboard.

Q: My survey emails are not being sent. What should I do?
A: Check the SMTP settings under NPS Engine > Email Settings. Ensure your SMTP server details are correct and try sending a test email. Also, check the debug.log file in the wp-content/ folder for detailed error messages.

Q: The database tables were not created. How do I fix this?
A: Try deactivating and reactivating the plugin under Plugins > Installed Plugins. This will force the table creation routine to run. If the problem persists, check your database user's write permissions and the debug.log.

Screenshots
(assets/images/screenshot-1.png)

(assets/images/screenshot-2.png)

(assets/images/screenshot-3.png)

Changelog
1.0.0
Initial release of the NPS Engine plugin.

Contact management, email/SMTP settings, trigger rules, response capture, and basic reporting functionalities.

Modularized code structure.

Update Notice
1.0.0
This is the initial version of the NPS Engine. Be sure to perform a full backup of your site before installing or updating any plugin.