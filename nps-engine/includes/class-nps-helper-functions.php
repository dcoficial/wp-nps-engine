<?php
/**
 * Funções auxiliares para o plugin NPS Engine.
 *
 * @package NPS_Engine
 * @subpackage Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Sair se acessado diretamente
}

class NPS_Helper_Functions {

    /**
     * Redireciona para uma página de administração com uma mensagem.
     *
     * @param string $page A slug da página para redirecionar.
     * @param string $message A mensagem a ser exibida.
     * @param string $type O tipo de mensagem ('success' ou 'error').
     */
    public static function redirect_with_message( $page, $message, $type = 'success' ) {
        $redirect_url = add_query_arg(
            array(
                'page'       => $page,
                'message'    => urlencode( $message ),
                'message_type' => $type,
            ),
            admin_url( 'admin.php' )
        );
        wp_redirect( $redirect_url );
        exit;
    }
}
