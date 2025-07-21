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

		// Verifica o nonce de segurança.
		if ( ! isset( $_POST['nps_save_trigger_settings_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nps_save_trigger_settings_nonce'] ), 'nps_save_trigger_settings_action' ) ) {
			NPS_Helper_Functions::redirect_with_message( 'trigger_settings', __( 'Erro: Verificação de segurança falhou.', 'nps-engine' ), 'error' );
			return;
		}

		// Salva a Política de Quarentena.
		if ( isset( $_POST['nps_global_min_frequency'] ) ) {
			$global_min_frequency = intval( wp_unslash( $_POST['nps_global_min_frequency'] ) );
			if ( $global_min_frequency < 1 ) {
				NPS_Helper_Functions::redirect_with_message( 'trigger_settings', __( 'Erro: O intervalo da política de quarentena deve ser um número positivo.', 'nps-engine' ), 'error' );
				return;
			}
			update_option( 'nps_global_min_frequency', $global_min_frequency );
		}

		// Prepara os dados da regra única.
		if ( isset( $_POST['nps_trigger_type'] ) ) {
			$trigger_type = sanitize_text_field( wp_unslash( $_POST['nps_trigger_type'] ) );
			$data         = array(
				'trigger_type' => $trigger_type,
				'status'       => 1,
				'rule_name'    => 'Regra Única',
			);
			$format       = array( '%s', '%d', '%s' );

			if ( 'time' === $trigger_type && isset( $_POST['nps_interval_days'] ) ) {
				$interval_days = intval( wp_unslash( $_POST['nps_interval_days'] ) );
				if ( $interval_days < 1 ) {
					NPS_Helper_Functions::redirect_with_message( 'trigger_settings', __( 'Erro: O intervalo de dias para o gatilho de tempo deve ser positivo.', 'nps-engine' ), 'error' );
					return;
				}
				$data['interval_days'] = $interval_days;
				$format[]              = '%d';
			} elseif ( 'event' === $trigger_type && isset( $_POST['nps_event_slug'], $_POST['nps_delay_days'] ) ) {
				$event_slug = sanitize_text_field( wp_unslash( $_POST['nps_event_slug'] ) );
				$delay_days = intval( wp_unslash( $_POST['nps_delay_days'] ) );
				if ( $delay_days < 0 ) {
					NPS_Helper_Functions::redirect_with_message( 'trigger_settings', __( 'Erro: O atraso em dias após o evento não pode ser negativo.', 'nps-engine' ), 'error' );
					return;
				}
				if ( empty( $event_slug ) ) {
					NPS_Helper_Functions::redirect_with_message( 'trigger_settings', __( 'Erro: Um evento deve ser selecionado.', 'nps-engine' ), 'error' );
					return;
				}
				$data['event_slug'] = $event_slug;
				$data['delay_days'] = $delay_days;
				$format[]           = '%s';
				$format[]           = '%d';
			}

			// Verifica se a regra já existe para decidir entre INSERT e UPDATE.
			$existing_rule_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM %i LIMIT 1", $table_name_rules ) );

			if ( $existing_rule_id ) {
				// Atualiza a regra existente.
				$wpdb->update( $table_name_rules, $data, array( 'id' => $existing_rule_id ), $format, array( '%d' ) );
			} else {
				// Insere a primeira e única regra.
				$wpdb->insert( $table_name_rules, $data, $format );
			}

			if ( ! empty( $wpdb->last_error ) ) {
				/* translators: %s: Database error details */
				$error_message = sprintf( __( 'Erro ao salvar as configurações no banco de dados. Detalhes: %s', 'nps-engine' ), $wpdb->last_error );
				error_log( 'NPS Engine DB Error (save_settings): ' . $wpdb->last_error );
				NPS_Helper_Functions::redirect_with_message( 'trigger_settings', $error_message, 'error' );
			} else {
				NPS_Helper_Functions::redirect_with_message( 'trigger_settings', __( 'Configurações de gatilhos salvas com sucesso!', 'nps-engine' ), 'success' );
			}
		}
	}
}
