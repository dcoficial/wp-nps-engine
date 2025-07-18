<?php
/**
 * Gerencia a renderização das páginas do painel administrativo para o NPS Engine.
 *
 * @package NPS_Engine
 * @subpackage Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Sair se acessado diretamente
}

class NPS_Admin_Pages {

    public function add_admin_menu_pages() {
        add_menu_page( __( 'NPS Engine', 'nps-engine' ), __( 'NPS Engine', 'nps-engine' ), 'manage_options', 'nps-survey', array( $this, 'render_contacts_page' ), 'dashicons-feedback', 80 );
        add_submenu_page( 'nps-survey', __( 'Contacts', 'nps-engine' ), __( 'Contacts', 'nps-engine' ), 'manage_options', 'nps-survey', array( $this, 'render_contacts_page' ) );
        add_submenu_page( 'nps-survey', __( 'Layout', 'nps-engine' ), __( 'Layout', 'nps-engine' ), 'manage_options', 'nps-survey-email-settings', array( $this, 'render_email_settings_page' ) );
        add_submenu_page( 'nps-survey', __( 'Triggers', 'nps-engine' ), __( 'Triggers', 'nps-engine' ), 'manage_options', 'nps-survey-trigger-settings', array( $this, 'render_trigger_settings_page' ) );
        add_submenu_page( 'nps-survey', __( 'Reports', 'nps-engine' ), __( 'Reports', 'nps-engine' ), 'manage_options', 'nps-survey-reports', array( $this, 'render_reports_page' ) );
        add_submenu_page( 'nps-survey', __( 'Tools', 'nps-engine' ), __( 'Tools', 'nps-engine' ), 'manage_options', 'nps-survey-tools', array( $this, 'render_tools_page' ) );
    }

    public function enqueue_admin_styles() {
        wp_add_inline_style( 'wp-admin', '
            .nps-container { max-width: 1000px; margin-top: 20px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            .nps-container h2 { border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px; }
            .nps-form-table th { width: 250px; padding-right: 20px; }
            .nps-form-table td { padding-bottom: 10px; }
            .nps-form-table input[type="text"], .nps-form-table input[type="email"], .nps-form-table input[type="number"], .nps-form-table input[type="password"], .nps-form-table select, .nps-form-table textarea { width: 100%; max-width: 400px; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
            .nps-form-table textarea { min-height: 100px; }
            .nps-form-table .button-primary { margin-top: 10px; }
            .nps-contacts-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            .nps-contacts-table th, .nps-contacts-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            .nps-contacts-table th { background-color: #f2f2f2; }
            .nps-contacts-table .status-active { color: green; font-weight: bold; }
            .nps-contacts-table .status-inactive { color: red; font-weight: bold; }
            .nps-import-users-section { margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }
            .nps-import-users-section ul { list-style: none; padding: 0; }
            .nps-import-users-section li { margin-bottom: 5px; }
            .nps-messages { margin-bottom: 15px; padding: 10px; border-radius: 4px; }
            .nps-message-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
            .nps-message-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
            .nps-reports-filter-form { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 20px; }
            .nps-reset-section { border: 2px solid #dc3232; padding: 20px; background-color: #f8d7da; }
        ' );
    }

    public function render_contacts_page() {
        global $wpdb; $table_name_contacts = $wpdb->prefix . 'nps_contacts'; $contacts = $wpdb->get_results( "SELECT * FROM $table_name_contacts ORDER BY name ASC", ARRAY_A ); $wp_users = get_users( array( 'fields' => array( 'ID', 'display_name', 'user_email' ) ) ); $imported_wp_user_ids = $wpdb->get_col( "SELECT wp_user_id FROM $table_name_contacts WHERE origin = 'wp_user'" );
        ?>
        <div class="wrap nps-container">
            <h1><?php _e( 'Manage NPS Contacts', 'nps-engine' ); ?></h1>
            <?php if ( isset( $_GET['message'] ) ) { $message_type = ( $_GET['message_type'] == 'success' ) ? 'nps-message-success' : 'nps-message-error'; echo '<div class="nps-messages ' . esc_attr( $message_type ) . '">' . esc_html( urldecode( $_GET['message'] ) ) . '</div>'; } ?>
            <h2><?php _e( 'Add New Contact Manually', 'nps-engine' ); ?></h2>
            <form method="post" action=""><?php wp_nonce_field( 'nps_add_contact_action', 'nps_add_contact_nonce' ); ?><table class="form-table nps-form-table"><tr><th scope="row"><label for="nps_contact_name"><?php _e( 'Name', 'nps-engine' ); ?></label></th><td><input type="text" id="nps_contact_name" name="nps_contact_name" required /></td></tr><tr><th scope="row"><label for="nps_contact_email"><?php _e( 'Email', 'nps-engine' ); ?></label></th><td><input type="email" id="nps_contact_email" name="nps_contact_email" required /></td></tr><tr><th scope="row"><label for="nps_contact_status"><?php _e( 'Status', 'nps-engine' ); ?></label></th><td><select id="nps_contact_status" name="nps_contact_status"><option value="1"><?php _e( 'Active', 'nps-engine' ); ?></option><option value="0"><?php _e( 'Inactive', 'nps-engine' ); ?></option></select></td></tr></table><p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Add Contact', 'nps-engine' ); ?>"></p></form>
            <div class="nps-import-users-section">
                <h2><?php _e( 'Import WordPress Users', 'nps-engine' ); ?></h2>
                <form method="post" action=""><?php wp_nonce_field( 'nps_import_users_action', 'nps_import_users_nonce' ); ?><p><?php _e( 'Select the WordPress users you want to add as NPS contacts:', 'nps-engine' ); ?></p><ul><?php $has_unimported_users = false; foreach ( $wp_users as $user ) { if ( ! in_array( $user->ID, $imported_wp_user_ids ) ) { $has_unimported_users = true; ?><li><label><input type="checkbox" name="nps_import_users[]" value="<?php echo esc_attr( $user->ID ); ?>" /> <?php echo esc_html( $user->display_name ); ?> (<?php echo esc_html( $user->user_email ); ?>)</label></li><?php } } if ( ! $has_unimported_users ) { echo '<p>' . esc_html__( 'All WordPress users have already been imported or there are no users.', 'nps-engine' ) . '</p>'; } ?></ul><?php if ( $has_unimported_users ) : ?><p class="submit"><input type="submit" name="submit" id="submit" class="button button-secondary" value="<?php _e( 'Import Selected', 'nps-engine' ); ?>"></p><?php endif; ?></form>
            </div>
            <h2><?php _e( 'Contacts List', 'nps-engine' ); ?></h2>
            <table class="wp-list-table widefat fixed striped nps-contacts-table"><thead><tr><th><?php _e( 'Name', 'nps-engine' ); ?></th><th><?php _e( 'Email', 'nps-engine' ); ?></th><th><?php _e( 'Status', 'nps-engine' ); ?></th><th><?php _e( 'Origin', 'nps-engine' ); ?></th><th><?php _e( 'Last Survey', 'nps-engine' ); ?></th><th><?php _e( 'Actions', 'nps-engine' ); ?></th></tr></thead><tbody><?php if ( ! empty( $contacts ) ) : ?><?php foreach ( $contacts as $contact ) : ?><tr><td><?php echo esc_html( $contact['name'] ); ?></td><td><?php echo esc_html( $contact['email'] ); ?></td><td><?php $status_class = ( $contact['status'] == 1 ) ? 'status-active' : 'status-inactive'; $status_text = ( $contact['status'] == 1 ) ? __( 'Active', 'nps-engine' ) : __( 'Inactive', 'nps-engine' ); echo '<span class="' . esc_attr( $status_class ) . '">' . esc_html( $status_text ) . '</span>'; ?></td><td><?php echo ( $contact['origin'] == 'wp_user' ) ? __( 'WP User', 'nps-engine' ) : __( 'Manual', 'nps-engine' ); ?></td><td><?php echo ( $contact['last_survey_sent'] ) ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $contact['last_survey_sent'] ) ) : __( 'Never', 'nps-engine' ); ?></td><td><?php $toggle_nonce = wp_create_nonce( 'nps_toggle_status_' . $contact['id'] ); $delete_nonce = wp_create_nonce( 'nps_delete_contact_' . $contact['id'] ); ?><a href="<?php echo esc_url( admin_url( 'admin.php?page=nps-survey&action=toggle_status&contact_id=' . $contact['id'] . '&_wpnonce=' . $toggle_nonce ) ); ?>" class="button button-small"><?php echo ( $contact['status'] == 1 ) ? __( 'Deactivate', 'nps-engine' ) : __( 'Activate', 'nps-engine' ); ?></a> <a href="<?php echo esc_url( admin_url( 'admin.php?page=nps-survey-tools&action=delete_contact&contact_id=' . $contact['id'] . '&_wpnonce=' . $delete_nonce ) ); ?>" class="button button-small button-danger" onclick="return confirm('<?php _e( 'Are you sure you want to delete this contact and all their responses?', 'nps-engine' ); ?>');"><?php _e( 'Delete', 'nps-engine' ); ?></a></td></tr><?php endforeach; ?><?php else : ?><tr><td colspan="6"><?php _e( 'No contacts found.', 'nps-engine' ); ?></td></tr><?php endif; ?></tbody></table>
        </div>
        <?php
    }

    public function render_email_settings_page() {
        $email_settings = get_option( 'nps_email_settings', array() ); 
        $defaults = array( 
            'from_name' => get_bloginfo( 'name' ), 
            'from_email' => get_bloginfo( 'admin_email' ), 
            'reply_to_email' => '', 
            'email_subject' => __( 'Your opinion is important to us!', 'nps-engine' ), 
            'email_body' => __( "Hello [contact_name],\n\nWe would like to hear your opinion about your recent experience with us.\n\nOn a scale of 0 to 10, how likely are you to recommend [site_name] to a friend or colleague?\n\n[nps_score_links]\n\nIf you have any additional comments, please feel free to reply to this email.\n\nWe appreciate your time!\n\nSincerely,\nThe [site_name] Team", 'nps-engine' ),
            'show_powered_by' => '' // Padrão é desligado
        ); 
        $email_settings = wp_parse_args( $email_settings, $defaults );
        ?>
        <div class="wrap nps-container">
            <h1><?php _e( 'Email Layout Settings', 'nps-engine' ); ?></h1>
            <?php if ( isset( $_GET['message'] ) ) { $message_type = ( $_GET['message_type'] == 'success' ) ? 'nps-message-success' : 'nps-message-error'; echo '<div class="nps-messages ' . esc_attr( $message_type ) . '">' . esc_html( urldecode( $_GET['message'] ) ) . '</div>'; } ?>
            <form method="post" action=""><?php wp_nonce_field( 'nps_save_email_settings_action', 'nps_save_email_settings_nonce' ); ?>
                <h2><?php _e( 'Email Content', 'nps-engine' ); ?></h2>
                <table class="form-table nps-form-table">
                    <tr><th scope="row"><label for="nps_from_name"><?php _e( 'Sender Name', 'nps-engine' ); ?></label></th><td><input type="text" id="nps_from_name" name="nps_from_name" value="<?php echo esc_attr( $email_settings['from_name'] ); ?>" required /></td></tr>
                    <tr><th scope="row"><label for="nps_from_email"><?php _e( 'Sender Email', 'nps-engine' ); ?></label></th><td><input type="email" id="nps_from_email" name="nps_from_email" value="<?php echo esc_attr( $email_settings['from_email'] ); ?>" required /></td></tr>
                    <tr><th scope="row"><label for="nps_reply_to_email"><?php _e( 'Reply-To Email (Optional)', 'nps-engine' ); ?></label></th><td><input type="email" id="nps_reply_to_email" name="nps_reply_to_email" value="<?php echo esc_attr( $email_settings['reply_to_email'] ); ?>" /></td></tr>
                    <tr><th scope="row"><label for="nps_email_subject"><?php _e( 'Email Subject', 'nps-engine' ); ?></label></th><td><input type="text" id="nps_email_subject" name="nps_email_subject" value="<?php echo esc_attr( $email_settings['email_subject'] ); ?>" required /></td></tr>
                    <tr><th scope="row"><label for="nps_email_body"><?php _e( 'Email Body', 'nps-engine' ); ?></label></th><td><textarea id="nps_email_body" name="nps_email_body" rows="10"><?php echo esc_textarea( $email_settings['email_body'] ); ?></textarea><p class="description"><?php _e( 'Use <code>[contact_name]</code>, <code>[site_name]</code>, and <code>[nps_score_links]</code>.', 'nps-engine' ); ?></p></td></tr>
                    <tr><th scope="row"><label for="nps_show_powered_by"><?php _e( 'Plugin Branding', 'nps-engine' ); ?></label></th><td><label><input type="checkbox" id="nps_show_powered_by" name="nps_show_powered_by" value="1" <?php checked( $email_settings['show_powered_by'], 1 ); ?> /> <?php _e( 'Show "Powered by" link on the thank you page.', 'nps-engine' ); ?></label></td></tr>
                </table>
                <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Save Layout', 'nps-engine' ); ?>"></p>
            </form>
            <hr />
            <h2><?php _e( 'Test Send', 'nps-engine' ); ?></h2>
            <form method="post" action=""><?php wp_nonce_field( 'nps_test_email_action', 'nps_test_email_nonce' ); ?>
                <table class="form-table nps-form-table"><tr><th scope="row"><label for="nps_test_email_address"><?php _e( 'Send Test Email To:', 'nps-engine' ); ?></label></th><td><input type="email" id="nps_test_email_address" name="nps_test_email_address" required /></td></tr></table>
                <p class="submit"><input type="submit" name="submit" id="submit" class="button button-secondary" value="<?php _e( 'Send Test', 'nps-engine' ); ?>"></p>
            </form>
        </div>
        <?php
    }

    public function render_trigger_settings_page() {
        global $wpdb; $table_name_rules = $wpdb->prefix . 'nps_rules'; $rule = $wpdb->get_row( "SELECT * FROM $table_name_rules LIMIT 1", ARRAY_A ); if ( ! $rule ) { $rule = array( 'trigger_type' => 'time', 'interval_days' => 90, 'event_slug' => 'woocommerce_order_status_completed', 'delay_days' => 15 ); }
        $global_min_frequency = get_option( 'nps_global_min_frequency', 90 ); $available_events = array( 'woocommerce_order_status_completed' => __( 'WooCommerce Order Completed', 'nps-engine' ) );
        ?>
        <div class="wrap nps-container">
            <h1><?php _e( 'Survey Triggers', 'nps-engine' ); ?></h1>
            <?php if ( isset( $_GET['message'] ) ) { $message_type = ( $_GET['message_type'] == 'success' ) ? 'nps-message-success' : 'nps-message-error'; echo '<div class="nps-messages ' . esc_attr( $message_type ) . '">' . esc_html( urldecode( $_GET['message'] ) ) . '</div>'; } ?>
            <form method="post" action=""><?php wp_nonce_field( 'nps_save_trigger_settings_action', 'nps_save_trigger_settings_nonce' ); ?>
                <h2><?php _e( 'Quarantine Policy', 'nps-engine' ); ?></h2>
                <p><?php _e( 'Defines the minimum period a contact must wait before they can receive a new survey, regardless of the trigger.', 'nps-engine' );?></p>
                <table class="form-table nps-form-table">
                    <tr><th scope="row"><label for="nps_global_min_frequency"><?php _e( 'Minimum interval between surveys (days):', 'nps-engine' ); ?></label></th><td><input type="number" id="nps_global_min_frequency" name="nps_global_min_frequency" value="<?php echo esc_attr( $global_min_frequency ); ?>" min="1" required /></td></tr>
                </table>
                <hr>
                <h2><?php _e( 'Trigger Configuration', 'nps-engine' ); ?></h2>
                <p><?php _e( 'Define the condition that will trigger sending the survey to eligible contacts.', 'nps-engine' );?></p>
                <table class="form-table nps-form-table">
                    <tr><th scope="row"><label for="nps_trigger_type"><?php _e( 'Trigger', 'nps-engine' ); ?></label></th><td><select id="nps_trigger_type" name="nps_trigger_type"><option value="time" <?php selected( $rule['trigger_type'], 'time' ); ?>><?php _e( 'Time-Based', 'nps-engine' ); ?></option><option value="event" <?php selected( $rule['trigger_type'], 'event' ); ?>><?php _e( 'Event-Based', 'nps-engine' ); ?></option></select></td></tr>
                    <tr class="nps-trigger-time-field"><th scope="row"><label for="nps_interval_days"><?php _e( 'Send a survey every (days):', 'nps-engine' ); ?></label></th><td><input type="number" id="nps_interval_days" name="nps_interval_days" value="<?php echo esc_attr( $rule['interval_days'] ); ?>" min="1" /><p class="description"><?php _e( 'The survey will only be sent if the contact is also outside the Quarantine Policy.', 'nps-engine' ); ?></p></td></tr>
                    <tr class="nps-trigger-event-field"><th scope="row"><label for="nps_event_slug"><?php _e( 'When the event occurs:', 'nps-engine' ); ?></label></th><td><select id="nps_event_slug" name="nps_event_slug"><?php foreach ( $available_events as $slug => $label ) : ?><option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $rule['event_slug'], $slug ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select><p class="description"><?php _e( 'Requires integration with the corresponding plugin (e.g., WooCommerce).', 'nps-engine' ); ?></p></td></tr>
                    <tr class="nps-trigger-event-field"><th scope="row"><label for="nps_delay_days"><?php _e( 'Wait (days) before sending the survey:', 'nps-engine' ); ?></label></th><td><input type="number" id="nps_delay_days" name="nps_delay_days" value="<?php echo esc_attr( $rule['delay_days'] ); ?>" min="0" /></td></tr>
                </table>
                <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Save Trigger Settings', 'nps-engine' ); ?>"></p>
            </form>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const triggerTypeSelect = document.getElementById('nps_trigger_type');
                const timeFields = document.querySelectorAll('.nps-trigger-time-field');
                const eventFields = document.querySelectorAll('.nps-trigger-event-field');
                function toggleTriggerFields() {
                    const selectedType = triggerTypeSelect.value;
                    timeFields.forEach(field => { field.style.display = (selectedType === 'time') ? 'table-row' : 'none'; });
                    eventFields.forEach(field => { field.style.display = (selectedType === 'event') ? 'table-row' : 'none'; });
                }
                if (triggerTypeSelect) {
                    triggerTypeSelect.addEventListener('change', toggleTriggerFields);
                    toggleTriggerFields();
                }
            });
        </script>
        <?php
    }

    public function render_reports_page() {
        $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : ''; $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';
        $reports_manager = new NPS_Reports();
        $report_data = $reports_manager->get_nps_report_data($start_date, $end_date);
        $detailed_responses = $reports_manager->get_detailed_responses($start_date, $end_date);
        ?>
        <div class="wrap nps-container">
            <h1><?php _e( 'NPS Reports', 'nps-engine' ); ?></h1>
            <form method="GET" class="nps-reports-filter-form">
                <input type="hidden" name="page" value="nps-survey-reports">
                <label for="start_date"><?php _e('From:', 'nps-engine'); ?></label><input type="date" id="start_date" name="start_date" value="<?php echo esc_attr($start_date); ?>">
                <label for="end_date"><?php _e('To:', 'nps-engine'); ?></label><input type="date" id="end_date" name="end_date" value="<?php echo esc_attr($end_date); ?>">
                <button type="submit" class="button"><?php _e('Filter', 'nps-engine'); ?></button>
                <a href="<?php echo esc_url(add_query_arg(['action' => 'nps_export_csv', 'start_date' => $start_date, 'end_date' => $end_date])); ?>" class="button button-secondary"><?php _e('Export to CSV', 'nps-engine'); ?></a>
            </form>
            <?php if ($report_data['total_responses'] > 0) : ?>
                <h2><?php _e( 'NPS Overview', 'nps-engine' ); ?></h2>
                <p><strong><?php _e( 'NPS Score:', 'nps-engine' ); ?></strong> <?php echo $report_data['nps_score']; ?></p>
                <p><strong><?php _e( 'Total Responses:', 'nps-engine' ); ?></strong> <?php echo $report_data['total_responses']; ?></p>
                <p><strong><?php _e( 'Promoters (9-10):', 'nps-engine' ); ?></strong> <?php echo $report_data['promoters']; ?> (<?php echo round(($report_data['promoters'] / $report_data['total_responses']) * 100, 2); ?>%)</p>
                <p><strong><?php _e( 'Passives (7-8):', 'nps-engine' ); ?></strong> <?php echo $report_data['passives']; ?> (<?php echo round(($report_data['passives'] / $report_data['total_responses']) * 100, 2); ?>%)</p>
                <p><strong><?php _e( 'Detractors (0-6):', 'nps-engine' ); ?></strong> <?php echo $report_data['detractors']; ?> (<?php echo round(($report_data['detractors'] / $report_data['total_responses']) * 100, 2); ?>%)</p>
            <?php else : ?><p><?php _e( 'No responses found for the selected period.', 'nps-engine' ); ?></p><?php endif; ?>
            <h2><?php _e( 'Detailed Responses', 'nps-engine' ); ?></h2>
            <table class="wp-list-table widefat fixed striped nps-contacts-table">
                <thead><tr><th><?php _e( 'Contact (Email)', 'nps-engine' ); ?></th><th><?php _e( 'Score', 'nps-engine' ); ?></th><th><?php _e( 'Response Date', 'nps-engine' ); ?></th></tr></thead>
                <tbody>
                    <?php if ( ! empty( $detailed_responses ) ) : ?><?php foreach ( $detailed_responses as $response ) : ?>
                        <tr><td><?php echo esc_html( $response['contact_email'] ); ?></td><td><?php echo esc_html( $response['score'] ); ?></td><td><?php echo date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $response['response_timestamp'] ) ); ?></td></tr>
                    <?php endforeach; ?><?php else : ?><tr><td colspan="3"><?php _e( 'No detailed responses found for the selected period.', 'nps-engine' ); ?></td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_tools_page() {
        ?>
        <div class="wrap nps-container">
            <h1><?php _e( 'Tools', 'nps-engine' ); ?></h1>
            <?php if ( isset( $_GET['message'] ) ) { $message_type = ( $_GET['message_type'] == 'success' ) ? 'nps-message-success' : 'nps-message-error'; echo '<div class="nps-messages ' . esc_attr( $message_type ) . '">' . esc_html( urldecode( $_GET['message'] ) ) . '</div>'; } ?>
            <div class="nps-reset-section">
                <h2><?php _e( 'Reset Plugin Data', 'nps-engine' ); ?></h2>
                <p><strong><?php _e( 'WARNING:', 'nps-engine' ); ?></strong> <?php _e( 'This action is irreversible. It will permanently delete ALL contacts and ALL survey responses from the database. Use with extreme caution.', 'nps-engine' ); ?></p>
                <form method="post" action="" onsubmit="return confirm('<?php _e( 'Are you ABSOLUTELY SURE you want to delete all contacts and responses? This action cannot be undone.', 'nps-engine' ); ?>');">
                    <?php wp_nonce_field( 'nps_reset_plugin_action', 'nps_reset_plugin_nonce' ); ?>
                    <p class="submit"><input type="submit" name="submit" class="button button-danger" value="<?php _e( 'Delete All Contacts and Responses', 'nps-engine' ); ?>"></p>
                </form>
            </div>
        </div>
        <?php
    }
}