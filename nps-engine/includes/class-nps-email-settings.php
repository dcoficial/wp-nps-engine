<?php
/**
 * Gerencia as configurações de e-mail para o plugin NPS Engine.
 *
 * @package NPS_Engine
 * @subpackage Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Sair se acessado diretamente
}

class NPS_Email_Settings {

    /**
     * Salva as configurações de e-mail.
     */
    public function save_email_settings() {
        $settings = array(
            'from_name'         => sanitize_text_field( $_POST['nps_from_name'] ),
            'from_email'        => sanitize_email( $_POST['nps_from_email'] ),
            'reply_to_email'    => sanitize_email( $_POST['nps_reply_to_email'] ),
            'email_subject'     => sanitize_text_field( $_POST['nps_email_subject'] ),
            'email_body'        => wp_kses_post( $_POST['nps_email_body'] ), // Permite HTML básico
        );

        update_option( 'nps_email_settings', $settings );
        NPS_Helper_Functions::redirect_with_message( 'email_settings', __( 'Configurações de e-mail salvas com sucesso!', 'nps-engine' ), 'success' );
    }

    /**
     * Envia um e-mail de teste com as configurações atuais.
     */
    public function send_test_email() {
        $to = sanitize_email( $_POST['nps_test_email_address'] );
        if ( ! is_email( $to ) ) {
            NPS_Helper_Functions::redirect_with_message( 'email_settings', __( 'Erro: Endereço de e-mail de teste inválido.', 'nps-engine' ), 'error' );
            return;
        }

        $email_settings = get_option( 'nps_email_settings', array() );
        $from_name = isset( $email_settings['from_name'] ) ? $email_settings['from_name'] : get_bloginfo( 'name' );
        $from_email = isset( $email_settings['from_email'] ) ? $email_settings['from_email'] : get_bloginfo( 'admin_email' );

        $subject = __( 'Teste de E-mail do Plugin NPS Engine', 'nps-engine' );
        $body = __( 'Este é um e-mail de teste enviado do seu Plugin NPS Engine no WordPress. Se você recebeu este e-mail, suas configurações de e-mail estão funcionando corretamente.', 'nps-engine' );

        // Define o cabeçalho do remetente, essencial para o envio funcionar corretamente
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>',
        );

        $sent = wp_mail( $to, $subject, $body, $headers );

        if ( $sent ) {
            /* translators: %s: Recipient's email address */
            NPS_Helper_Functions::redirect_with_message( 'email_settings', sprintf( __( 'E-mail de teste enviado com sucesso para %s!', 'nps-engine' ), $to ), 'success' );
        } else {
            global $phpmailer;
            $error_message = __( 'Erro ao enviar e-mail de teste. Verifique suas configurações e logs do servidor.', 'nps-engine' );
            if ( isset( $phpmailer->ErrorInfo ) && ! empty( $phpmailer->ErrorInfo ) ) {
                /* translators: %s: Error details from the PHPMailer library */
                $error_message .= ' ' . sprintf( __( 'Detalhes do erro: %s', 'nps-engine' ), $phpmailer->ErrorInfo );
                error_log( 'NPS Engine Test Email Error: ' . $phpmailer->ErrorInfo );
            }
            NPS_Helper_Functions::redirect_with_message( 'email_settings', $error_message, 'error' );
        }
    }
}
