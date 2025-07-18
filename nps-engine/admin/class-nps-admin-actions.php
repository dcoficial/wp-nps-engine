<?php
/**
 * Gerencia as ações e submissões de formulário no painel administrativo para o NPS Engine.
 *
 * @package NPS_Engine
 * @subpackage Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Sair se acessado diretamente
}

class NPS_Admin_Actions {

    public function handle_admin_form_submissions() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( isset( $_GET['action'] ) && $_GET['action'] == 'nps_export_csv' ) {
            $reports_manager = new NPS_Reports();
            $reports_manager->export_responses_to_csv();
        }

        $contacts_manager = new NPS_Contacts_Manager();
        $email_settings_manager = new NPS_Email_Settings();
        $trigger_settings_manager = new NPS_Trigger_Settings();

        if ( isset( $_GET['page'] ) && $_GET['page'] == 'nps-survey' ) {
            if ( isset( $_POST['nps_add_contact_nonce'] ) && wp_verify_nonce( $_POST['nps_add_contact_nonce'], 'nps_add_contact_action' ) ) {
                $contacts_manager->add_new_contact();
            } elseif ( isset( $_POST['nps_import_users_nonce'] ) && wp_verify_nonce( $_POST['nps_import_users_nonce'], 'nps_import_users_action' ) ) {
                $contacts_manager->import_wp_users_as_contacts();
            } elseif ( isset( $_GET['action'] ) && isset( $_GET['contact_id'] ) ) {
                if ( $_GET['action'] == 'toggle_status' && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'nps_toggle_status_' . $_GET['contact_id'] ) ) {
                    $contacts_manager->toggle_contact_status( intval( $_GET['contact_id'] ) );
                } elseif ( $_GET['action'] == 'delete_contact' && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'nps_delete_contact_' . $_GET['contact_id'] ) ) {
                    $contacts_manager->delete_contact( intval( $_GET['contact_id'] ) );
                }
            }
        }

        if ( isset( $_GET['page'] ) && $_GET['page'] == 'nps-survey-email-settings' ) {
            if ( isset( $_POST['nps_save_email_settings_nonce'] ) && wp_verify_nonce( $_POST['nps_save_email_settings_nonce'], 'nps_save_email_settings_action' ) ) {
                $email_settings_manager->save_email_settings();
            } elseif ( isset( $_POST['nps_test_email_nonce'] ) && wp_verify_nonce( $_POST['nps_test_email_nonce'], 'nps_test_email_action' ) ) {
                $email_settings_manager->send_test_email();
            }
        }

        if ( isset( $_GET['page'] ) && $_GET['page'] == 'nps-survey-trigger-settings' ) {
            if ( isset( $_POST['nps_save_trigger_settings_nonce'] ) && wp_verify_nonce( $_POST['nps_save_trigger_settings_nonce'], 'nps_save_trigger_settings_action' ) ) {
                $trigger_settings_manager->save_settings();
            }
        }

        // AJUSTE: Lida com a ação de reset da página de ferramentas.
        if ( isset( $_GET['page'] ) && $_GET['page'] == 'nps-survey-tools' ) {
            if ( isset( $_POST['nps_reset_plugin_nonce'] ) && wp_verify_nonce( $_POST['nps_reset_plugin_nonce'], 'nps_reset_plugin_action' ) ) {
                $db_manager = new NPS_Database_Manager();
                $db_manager->reset_plugin_data();
            }
        }
    }
}