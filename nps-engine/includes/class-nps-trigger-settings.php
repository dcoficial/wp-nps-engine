<?php
/**
 * Gerencia as regras de disparo e a frequência para o plugin NPS Engine.
 *
 * @package NPS_Engine
 * @subpackage Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Sair se acessado diretamente
}

class NPS_Trigger_Settings {

    /**
     * Salva as configurações de Quarentena e do Gatilho único.
     */
    public function save_settings() {
        global $wpdb;
        $table_name_rules = $wpdb->prefix . 'nps_rules';

        // Salva a hora de execução do Cron e reagenda o evento
        if ( isset( $_POST['nps_cron_run_time'] ) && preg_match( '/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $_POST['nps_cron_run_time'] ) ) {
            $cron_time = sanitize_text_field( $_POST['nps_cron_run_time'] );
            update_option( 'nps_cron_run_time', $cron_time );

            // Limpa o agendamento antigo para garantir que não haja duplicatas
            wp_clear_scheduled_hook( 'nps_survey_dispatch_cron' );
            
            // Calcula o timestamp para a próxima execução no fuso horário do site
            // A função strtotime usa o timezone do WordPress, configurado em 'Settings > General'
            $next_run = strtotime( 'today ' . $cron_time );
            if ( $next_run < time() ) {
                $next_run = strtotime( 'tomorrow ' . $cron_time );
            }

            // Reagenda o evento. wp_schedule_event espera um timestamp UTC, mas lida com a conversão a partir do timezone do site.
            wp_schedule_event( $next_run, 'daily', 'nps_survey_dispatch_cron' );

        } else {
            NPS_Helper_Functions::redirect_with_message( 'nps-survey-trigger-settings', __( 'Erro: O formato da hora de execução do cron é inválido. Use HH:MM.', 'nps-engine' ), 'error' );
            return;
        }

        // Salva a Política de Quarentena
        $global_min_frequency = intval( $_POST['nps_global_min_frequency'] );
        if ( $global_min_frequency < 1 ) {
            NPS_Helper_Functions::redirect_with_message( 'nps-survey-trigger-settings', __( 'Erro: O intervalo da política de quarentena deve ser um número positivo.', 'nps-engine' ), 'error' );
            return;
        }
        update_option( 'nps_global_min_frequency', $global_min_frequency );
        
        // Prepara os dados da regra única
        $trigger_type = sanitize_text_field( $_POST['nps_trigger_type'] );
        $data = array(
            'trigger_type' => $trigger_type,
            'status'     => 1, // AJUSTE 8: Regra está sempre ativa.
            'rule_name'  => 'Regra Única', // AJUSTE 5: Nome fixo, não visível ao usuário.
        );
        $format = array( '%s', '%d', '%s' );

        if ( $trigger_type === 'time' ) {
            $interval_days = intval( $_POST['nps_interval_days'] );
            if ( $interval_days < 1 ) {
                NPS_Helper_Functions::redirect_with_message( 'nps-survey-trigger-settings', __( 'Erro: O intervalo de dias para o gatilho de tempo deve ser positivo.', 'nps-engine' ), 'error' );
                return;
            }
            $data['interval_days'] = $interval_days;
            $format[] = '%d';
        } elseif ( $trigger_type === 'event' ) {
            $event_slug = sanitize_text_field( $_POST['nps_event_slug'] );
            $delay_days = intval( $_POST['nps_delay_days'] );
            if ( $delay_days < 0 ) {
                NPS_Helper_Functions::redirect_with_message( 'nps-survey-trigger-settings', __( 'Erro: O atraso em dias após o evento não pode ser negativo.', 'nps-engine' ), 'error' );
                return;
            }
            if ( empty( $event_slug ) ) {
                NPS_Helper_Functions::redirect_with_message( 'nps-survey-trigger-settings', __( 'Erro: Um evento deve ser selecionado.', 'nps-engine' ), 'error' );
                return;
            }
            $data['event_slug'] = $event_slug;
            $data['delay_days'] = $delay_days;
            $format[] = '%s';
            $format[] = '%d';
        }

        // Verifica se a regra já existe para decidir entre INSERT e UPDATE
        $existing_rule_id = $wpdb->get_var( "SELECT id FROM $table_name_rules LIMIT 1" );

        if ( $existing_rule_id ) {
            // Atualiza a regra existente
            $wpdb->update( $table_name_rules, $data, array( 'id' => $existing_rule_id ), $format, array( '%d' ) );
        } else {
            // Insere a primeira e única regra
            $wpdb->insert( $table_name_rules, $data, $format );
        }

        if ( ! empty( $wpdb->last_error ) ) {
            $error_message = __( 'Erro ao salvar as configurações no banco de dados.', 'nps-engine' ) . ' ' . sprintf( __( 'Detalhes: %s', 'nps-engine' ), $wpdb->last_error );
            error_log( 'NPS Engine DB Error (save_settings): ' . $wpdb->last_error );
            NPS_Helper_Functions::redirect_with_message( 'nps-survey-trigger-settings', $error_message, 'error' );
        } else {
            NPS_Helper_Functions::redirect_with_message( 'nps-survey-trigger-settings', __( 'Configurações de gatilhos salvas com sucesso!', 'nps-engine' ), 'success' );
        }
    }
}