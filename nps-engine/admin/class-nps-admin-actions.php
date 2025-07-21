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

    private $reports_manager;
    private $contacts_manager;
    private $email_settings_manager;
    private $trigger_settings_manager;
    private $db_manager;

    public function __construct(NPS_Reports $reports, NPS_Contacts_Manager $contacts, NPS_Email_Settings $email_settings, NPS_Trigger_Settings $trigger_settings, NPS_Database_Manager $db_manager) {
        $this->reports_manager = $reports;
        $this->contacts_manager = $contacts;
        $this->email_settings_manager = $email_settings;
        $this->trigger_settings_manager = $trigger_settings;
        $this->db_manager = $db_manager;
    }

    public function handle_admin_form_submissions() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Exportação CSV (continua igual)
        if ( isset( $_GET['action'], $_GET['_wpnonce'] ) && $_GET['action'] == 'nps_export_csv' && wp_verify_nonce( $_GET['_wpnonce'], 'nps_export_csv_nonce' ) ) {
            $this->reports_manager->export_responses_to_csv();
        }

        // Gestão de Contatos (ações via GET)
        if ( isset( $_GET['page'] ) && $_GET['page'] == 'nps-engine' && isset( $_GET['action'], $_GET['contact_id'], $_GET['_wpnonce'] ) ) {
            $contact_id = intval( $_GET['contact_id'] );
            if ( $_GET['action'] == 'toggle_status' && wp_verify_nonce( $_GET['_wpnonce'], 'nps_toggle_status_' . $contact_id ) ) {
                $this->contacts_manager->toggle_contact_status( $contact_id );
            } elseif ( $_GET['action'] == 'delete_contact' && wp_verify_nonce( $_GET['_wpnonce'], 'nps_delete_contact_' . $contact_id ) ) {
                $this->contacts_manager->delete_contact( $contact_id );
            }
        }

        // Submissões de Formulários (POST)
        // Adicionar novo contato
        if ( isset( $_POST['nps_add_contact_submit'] ) && isset( $_POST['nps_add_contact_nonce'] ) && wp_verify_nonce( $_POST['nps_add_contact_nonce'], 'nps_add_contact_action' ) ) {
            $this->contacts_manager->add_new_contact();
        }

        // Importar usuários WP
        if ( isset( $_POST['nps_import_users_submit'] ) && isset( $_POST['nps_import_users_nonce'] ) && wp_verify_nonce( $_POST['nps_import_users_nonce'], 'nps_import_users_action' ) ) {
            $this->contacts_manager->import_wp_users_as_contacts();
        }

        // Salvar configurações de e-mail
        if ( isset( $_POST['nps_save_email_settings_submit'] ) && isset( $_POST['nps_save_email_settings_nonce'] ) && wp_verify_nonce( $_POST['nps_save_email_settings_nonce'], 'nps_save_email_settings_action' ) ) {
            $this->email_settings_manager->save_email_settings();
        }

        // Enviar e-mail de teste
        if ( isset( $_POST['nps_test_email_submit'] ) && isset( $_POST['nps_test_email_nonce'] ) && wp_verify_nonce( $_POST['nps_test_email_nonce'], 'nps_test_email_action' ) ) {
            $this->email_settings_manager->send_test_email();
        }

        // Salvar configurações de gatilhos
        if ( isset( $_POST['nps_save_trigger_settings_submit'] ) && isset( $_POST['nps_save_trigger_settings_nonce'] ) && wp_verify_nonce( $_POST['nps_save_trigger_settings_nonce'], 'nps_save_trigger_settings_action' ) ) {
            $this->trigger_settings_manager->save_settings();
        }

        // Ferramentas (Reset)
        if ( isset( $_POST['nps_reset_plugin_submit'] ) && isset( $_POST['nps_reset_plugin_nonce'] ) && wp_verify_nonce( $_POST['nps_reset_plugin_nonce'], 'nps_reset_plugin_action' ) ) {
            $this->db_manager->reset_plugin_data();
        }
    }
}
