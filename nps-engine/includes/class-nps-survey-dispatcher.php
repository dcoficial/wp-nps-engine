<?php
/**
 * Gerencia o disparo de pesquisas NPS para o plugin NPS Engine.
 *
 * @package NPS_Engine
 * @subpackage Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Sair se acessado diretamente
}

class NPS_Survey_Dispatcher {

    /**
     * Função cron que verifica e envia pesquisas NPS.
     */
    public function nps_dispatch_cron_job() {
        global $wpdb;
        $table_name_contacts = $wpdb->prefix . 'nps_contacts';
        $table_name_rules = $wpdb->prefix . 'nps_rules';
        
        // Política de Quarentena
        $quarantine_days = get_option( 'nps_global_min_frequency', 90 );

        // Pega a única regra ativa
        $rule = $wpdb->get_row( "SELECT * FROM $table_name_rules WHERE status = 1 LIMIT 1", ARRAY_A );

        if ( ! $rule ) {
            error_log( 'NPS Engine Dispatch: Nenhuma regra de gatilho ativa encontrada.' );
            return;
        }

        $contacts_to_survey = array();
        $current_time = current_time( 'mysql' );

        if ( $rule['trigger_type'] === 'time' ) {
            // AJUSTE DE LÓGICA: A consulta agora verifica tanto o intervalo da regra quanto a quarentena.
            $interval_days = $rule['interval_days'];
            
            $eligible_contacts = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM $table_name_contacts
                WHERE status = 1
                AND (
                    -- Contatos que nunca receberam uma pesquisa são sempre elegíveis
                    last_survey_sent IS NULL
                    OR 
                    -- Contatos que já receberam devem atender a AMBAS as condições
                    (last_survey_sent <= DATE_SUB(%s, INTERVAL %d DAY) AND last_survey_sent <= DATE_SUB(%s, INTERVAL %d DAY))
                )",
                $current_time,
                $interval_days, // Frequência da regra
                $current_time,
                $quarantine_days // Política de Quarentena
            ), ARRAY_A );
            
            $contacts_to_survey = $eligible_contacts;

        } elseif ( $rule['trigger_type'] === 'event' ) {
            $delay_days = $rule['delay_days'];

            // AJUSTE DE LÓGICA: A consulta também verifica a quarentena para eventos.
            $eligible_contacts = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM $table_name_contacts
                WHERE status = 1
                AND last_event_trigger IS NOT NULL
                AND last_event_trigger <= DATE_SUB(%s, INTERVAL %d DAY)
                AND (
                    last_survey_sent IS NULL
                    OR last_survey_sent <= DATE_SUB(%s, INTERVAL %d DAY)
                )",
                $current_time,
                $delay_days,
                $current_time,
                $quarantine_days // Política de Quarentena
            ), ARRAY_A );

            $contacts_to_survey = $eligible_contacts;
        }

        // Envia as pesquisas para os contatos elegíveis
        foreach ( $contacts_to_survey as $contact ) {
            $this->send_nps_survey_email( $contact );
            
            // Se o gatilho foi por evento, reseta o 'last_event_trigger' para não enviar de novo pelo mesmo evento.
            if ($rule['trigger_type'] === 'event') {
                 $wpdb->update(
                    $table_name_contacts,
                    array( 'last_event_trigger' => null ),
                    array( 'id' => $contact['id'] ),
                    array( '%s' ),
                    array( '%d' )
                );
            }
        }
    }

    /**
     * Envia o e-mail da pesquisa NPS para um contato específico.
     */
    private function send_nps_survey_email( $contact ) {
        global $wpdb;
        $table_name_instances = $wpdb->prefix . 'nps_survey_instances';
        $table_name_contacts = $wpdb->prefix . 'nps_contacts';

        $email_settings = get_option( 'nps_email_settings', array() );
        $from_name = isset( $email_settings['from_name'] ) ? $email_settings['from_name'] : get_bloginfo( 'name' );
        $from_email = isset( $email_settings['from_email'] ) ? $email_settings['from_email'] : get_bloginfo( 'admin_email' );
        $reply_to_email = isset( $email_settings['reply_to_email'] ) ? $email_settings['reply_to_email'] : '';
        $subject = isset( $email_settings['email_subject'] ) ? $email_settings['email_subject'] : __( 'Sua opinião é importante para nós!', 'nps-engine' );
        $body = isset( $email_settings['email_body'] ) ? $email_settings['email_body'] : '';

        $unique_hash = md5( $contact['email'] . uniqid( '', true ) . time() . wp_rand() );

        $inserted_instance = $wpdb->insert(
            $table_name_instances,
            array( 'contact_id' => $contact['id'], 'unique_hash' => $unique_hash, 'send_timestamp' => current_time( 'mysql' ), 'responded' => 0 ),
            array( '%d', '%s', '%s', '%d' )
        );

        if ( ! $inserted_instance ) {
            error_log( 'NPS Engine Survey: Erro ao registrar instância da pesquisa para ' . $contact['email'] . '. Detalhes: ' . $wpdb->last_error );
            return false;
        }

        $nps_score_links = '<div style="text-align: center; margin: 20px 0;">';
        for ( $i = 0; $i <= 10; $i++ ) {
            $link_url = add_query_arg( array( 'score' => $i ), home_url( '/nps-survey/' . $unique_hash . '/' ) );
            $bg_color = ( $i <= 6 ) ? '#ff3342' : ( ( $i <= 8 ) ? '#ffb301' : '#00ad4e' );
            $text_color = '#ffffff';
            $nps_score_links .= '<a href="' . esc_url( $link_url ) . '" style="display: inline-block; width: 40px; height: 40px; line-height: 40px; border: 1px solid #e0e0e0; border-radius: 5px; font-size: 1.1em; font-weight: bold; color: ' . $text_color . '; text-decoration: none; margin: 0 3px; background-color: ' . $bg_color . '; transition: all 0.2s ease-in-out; text-align: center;">' . $i . '</a>';
        }
        $nps_score_links .= '</div>';

        $subject = str_replace( '[contact_name]', $contact['name'], $subject );
        $subject = str_replace( '[site_name]', get_bloginfo( 'name' ), $subject );

        $body = nl2br($body);
        $body = str_replace( '[contact_name]', $contact['name'], $body );
        $body = str_replace( '[site_name]', get_bloginfo( 'name' ), $body );
        $body = str_replace( '[nps_score_links]', $nps_score_links, $body );

        $headers = array( 'Content-Type: text/html; charset=UTF-8', 'From: ' . $from_name . ' <' . $from_email . '>' );
        if ( ! empty( $reply_to_email ) ) {
            $headers[] = 'Reply-To: ' . $from_name . ' <' . $reply_to_email . '>';
        }

        $sent = wp_mail( $contact['email'], $subject, $body, $headers );

        if ( $sent ) {
            $wpdb->update(
                $table_name_contacts,
                array( 'last_survey_sent' => current_time( 'mysql' ) ),
                array( 'id' => $contact['id'] ),
                array( '%s' ),
                array( '%d' )
            );
            error_log( 'NPS Engine Survey: Pesquisa enviada para ' . $contact['email'] );
            return true;
        } else {
            global $phpmailer;
            $error_details = ( isset( $phpmailer->ErrorInfo ) && ! empty( $phpmailer->ErrorInfo ) ) ? ' Detalhes do erro: ' . $phpmailer->ErrorInfo : '';
            error_log( 'NPS Engine Survey: Falha ao enviar pesquisa para ' . $contact['email'] . '.' . $error_details );
            return false;
        }
    }
}