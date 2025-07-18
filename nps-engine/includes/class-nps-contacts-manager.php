<?php
/**
 * Gerencia os contatos para o plugin NPS Engine.
 *
 * @package NPS_Engine
 * @subpackage Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Sair se acessado diretamente
}

class NPS_Contacts_Manager {

    public function add_new_contact() {
        global $wpdb; $table_name_contacts = $wpdb->prefix . 'nps_contacts'; $name = sanitize_text_field( $_POST['nps_contact_name'] ); $email = sanitize_email( $_POST['nps_contact_email'] ); $status = intval( $_POST['nps_contact_status'] );
        if ( ! is_email( $email ) ) { NPS_Helper_Functions::redirect_with_message( 'nps-survey', __( 'Erro: O endereço de e-mail fornecido é inválido.', 'nps-engine' ), 'error' ); return; }
        $existing_contact = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM $table_name_contacts WHERE email = %s", $email ) );
        if ( $existing_contact ) { NPS_Helper_Functions::redirect_with_message( 'nps-survey', __( 'Erro: Este e-mail já existe na lista de contatos.', 'nps-engine' ), 'error' ); return; }
        $inserted = $wpdb->insert( $table_name_contacts, array( 'name' => $name, 'email' => $email, 'status' => $status, 'origin' => 'manual', ), array( '%s', '%s', '%d', '%s' ) );
        if ( $inserted ) { NPS_Helper_Functions::redirect_with_message( 'nps-survey', __( 'Contato adicionado com sucesso!', 'nps-engine' ), 'success' );
        } else { $error_message = __( 'Erro ao tentar adicionar o novo contato no banco de dados.', 'nps-engine' ); if ( ! empty( $wpdb->last_error ) ) { $error_message .= ' ' . sprintf( __( 'Detalhes: %s', 'nps-engine' ), $wpdb->last_error ); error_log( 'NPS Engine DB Error (add_new_contact): ' . $wpdb->last_error ); } NPS_Helper_Functions::redirect_with_message( 'nps-survey', $error_message, 'error' ); }
    }

    public function import_wp_users_as_contacts() {
        global $wpdb; $table_name_contacts = $wpdb->prefix . 'nps_contacts';
        if ( ! isset( $_POST['nps_import_users'] ) || ! is_array( $_POST['nps_import_users'] ) || empty( $_POST['nps_import_users'] ) ) { NPS_Helper_Functions::redirect_with_message( 'nps-survey', __( 'Nenhum usuário do WordPress foi selecionado para importação.', 'nps-engine' ), 'error' ); return; }
        $imported_count = 0; $failed_imports = 0; $skipped_existing = 0;
        foreach ( $_POST['nps_import_users'] as $user_id ) {
            $user_data = get_userdata( intval( $user_id ) );
            if ( $user_data ) {
                $existing_contact = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM $table_name_contacts WHERE email = %s", $user_data->user_email ) );
                if ( $existing_contact ) { $updated = $wpdb->update( $table_name_contacts, array( 'origin' => 'wp_user', 'wp_user_id' => $user_data->ID ), array( 'id' => $existing_contact->id ), array( '%s', '%d' ), array( '%d' ) ); if ( $updated !== false ) { $skipped_existing++; } else { $failed_imports++; error_log( 'NPS Engine DB Error (import_wp_users - update): ' . $wpdb->last_error . ' for user ' . $user_data->user_email ); }
                } else { $inserted = $wpdb->insert( $table_name_contacts, array( 'name' => $user_data->display_name, 'email' => $user_data->user_email, 'status' => 1, 'origin' => 'wp_user', 'wp_user_id' => $user_data->ID, ), array( '%s', '%s', '%d', '%s', '%d' ) ); if ( $inserted ) { $imported_count++; } else { $failed_imports++; error_log( 'NPS Engine DB Error (import_wp_users - insert): ' . $wpdb->last_error . ' for user ' . $user_data->user_email ); } }
            } else { $failed_imports++; error_log( 'NPS Engine: Tentativa de importar usuário WP com ID inválido: ' . $user_id ); }
        }
        $message = ''; $message_type = 'success';
        if ( $imported_count > 0 ) { $message .= sprintf( __( '%d novos usuários importados com sucesso.', 'nps-engine' ), $imported_count ); }
        if ( $skipped_existing > 0 ) { if ( $imported_count > 0 ) $message .= ' '; $message .= sprintf( __( '%d usuários já existentes foram atualizados.', 'nps-engine' ), $skipped_existing ); }
        if ( $failed_imports > 0 ) { if ( $imported_count > 0 || $skipped_existing > 0 ) $message .= ' '; $message .= sprintf( __( '%d usuários falharam na importação. Verifique os logs.', 'nps-engine' ), $failed_imports ); $message_type = 'error'; }
        if ( empty( $message ) ) { $message = __( 'Nenhuma ação de importação foi concluída.', 'nps-engine' ); $message_type = 'error'; }
        NPS_Helper_Functions::redirect_with_message( 'nps-survey', $message, $message_type );
    }

    public function toggle_contact_status( $contact_id ) {
        global $wpdb; $table_name_contacts = $wpdb->prefix . 'nps_contacts';
        $current_status = $wpdb->get_var( $wpdb->prepare( "SELECT status FROM $table_name_contacts WHERE id = %d", $contact_id ) );
        if ( is_null( $current_status ) ) { NPS_Helper_Functions::redirect_with_message( 'nps-survey', __( 'Erro: Contato não encontrado.', 'nps-engine' ), 'error' ); return; }
        $new_status = ( $current_status == 1 ) ? 0 : 1;
        $updated = $wpdb->update( $table_name_contacts, array( 'status' => $new_status ), array( 'id' => $contact_id ), array( '%d' ), array( '%d' ) );
        if ( $updated !== false ) { $message = ( $new_status == 1 ) ? __( 'Status do contato alterado para ATIVO.', 'nps-engine' ) : __( 'Status do contato alterado para INATIVO.', 'nps-engine' ); NPS_Helper_Functions::redirect_with_message( 'nps-survey', $message, 'success' );
        } else { $error_message = __( 'Erro ao tentar atualizar o status do contato.', 'nps-engine' ); if ( ! empty( $wpdb->last_error ) ) { $error_message .= ' ' . sprintf( __( 'Detalhes: %s', 'nps-engine' ), $wpdb->last_error ); error_log( 'NPS Engine DB Error (toggle_contact_status): ' . $wpdb->last_error ); } NPS_Helper_Functions::redirect_with_message( 'nps-survey', $error_message, 'error' ); }
    }

    /**
     * Exclui um contato e todas as suas respostas associadas.
     *
     * @param int $contact_id O ID do contato.
     */
    public function delete_contact( $contact_id ) {
        global $wpdb;
        $table_name_contacts = $wpdb->prefix . 'nps_contacts';
        $table_name_instances = $wpdb->prefix . 'nps_survey_instances';

        // AJUSTE: Deleta primeiro as respostas associadas ao contato.
        $wpdb->delete( $table_name_instances, array( 'contact_id' => $contact_id ), array( '%d' ) );

        // Agora deleta o contato.
        $deleted = $wpdb->delete( $table_name_contacts, array( 'id' => $contact_id ), array( '%d' ) );

        if ( $deleted ) {
            NPS_Helper_Functions::redirect_with_message( 'nps-survey', __( 'Contato e suas respostas foram excluídos com sucesso!', 'nps-engine' ), 'success' );
        } else {
            $error_message = __( 'Erro ao tentar excluir o contato do banco de dados.', 'nps-engine' );
            if ( ! empty( $wpdb->last_error ) ) {
                $error_message .= ' ' . sprintf( __( 'Detalhes: %s', 'nps-engine' ), $wpdb->last_error );
                error_log( 'NPS Engine DB Error (delete_contact): ' . $wpdb->last_error );
            }
            NPS_Helper_Functions::redirect_with_message( 'nps-survey', $error_message, 'error' );
        }
    }

    public function record_event_for_contact( $event_data, $event_slug, $email ) {
        global $wpdb; $table_name_contacts = $wpdb->prefix . 'nps_contacts';
        $contact = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM $table_name_contacts WHERE email = %s", $email ) );
        if ( $contact ) {
            $wpdb->update( $table_name_contacts, array( 'last_event_trigger' => current_time( 'mysql' ) ), array( 'id' => $contact->id ), array( '%s' ), array( '%d' ) );
            error_log( 'NPS Engine Event Recorded: ' . $event_slug . ' for ' . $email );
        } else { error_log( 'NPS Engine Event Not Recorded: Contact not found for ' . $email ); }
    }
}