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
            'email_body'        => wp_kses_post( $_POST['nps_email_body'] ),
            // AJUSTE: Salva o estado da checkbox. Se não estiver marcada, salva um valor vazio.
            'show_powered_by'   => isset( $_POST['nps_show_powered_by'] ) ? 1 : 0,
        );

        update_option( 'nps_email_settings', $settings );
        NPS_Helper_Functions::redirect_with_message( 'nps-survey-email-settings', __( 'Email settings saved successfully!', 'nps-engine' ), 'success' );
    }

    /**
     * Envia um e-mail de teste com as configurações atuais.
     */
    public function send_test_email() {
        $to = sanitize_email( $_POST['nps_test_email_address'] );
        if ( ! is_email( $to ) ) {
            NPS_Helper_Functions::redirect_with_message( 'nps-survey-email-settings', __( 'Error: Invalid test email address.', 'nps-engine' ), 'error' );
            return;
        }

        $email_settings = get_option( 'nps_email_settings', array() );
        $from_name = isset( $email_settings['from_name'] ) ? $email_settings['from_name'] : get_bloginfo( 'name' );
        $from_email = isset( $email_settings['from_email'] ) ? $email_settings['from_email'] : get_bloginfo( 'admin_email' );

        $subject = __( 'NPS Engine Plugin Email Test', 'nps-engine' );
        $body = __( 'This is a test email sent from your NPS Engine Plugin on WordPress. If you received this email, your email settings are working correctly.', 'nps-engine' );
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>',
        );

        $sent = wp_mail( $to, $subject, $body, $headers );

        if ( $sent ) {
            NPS_Helper_Functions::redirect_with_message( 'nps-survey-email-settings', sprintf( __( 'Test email successfully sent to %s!', 'nps-engine' ), $to ), 'success' );
        } else {
            global $phpmailer;
            $error_message = __( 'Error sending test email. Check your settings and server logs.', 'nps-engine' );
            if ( isset( $phpmailer->ErrorInfo ) && ! empty( $phpmailer->ErrorInfo ) ) {
                $error_message .= ' ' . sprintf( __( 'Details: %s', 'nps-engine' ), $phpmailer->ErrorInfo );
                error_log( 'NPS Engine Test Email Error: ' . $phpmailer->ErrorInfo );
            }
            NPS_Helper_Functions::redirect_with_message( 'nps-survey-email-settings', $error_message, 'error' );
        }
    }
}