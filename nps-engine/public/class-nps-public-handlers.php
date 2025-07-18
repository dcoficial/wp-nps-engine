<?php
/**
 * Gerencia os handlers públicos (front-end) para o plugin NPS Engine.
 * Inclui a lógica para o endpoint da pesquisa NPS e a página de agradecimento.
 *
 * @package NPS_Engine
 * @subpackage Public
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Sair se acessado diretamente
}

class NPS_Public_Handlers {

    /**
     * Registra a regra de reescrita para o endpoint da pesquisa NPS.
     */
    public function register_nps_rewrite_rule() {
        add_rewrite_rule(
            'nps-survey/([a-zA-Z0-9]+)/?$',
            'index.php?nps_survey_hash=$matches[1]',
            'top'
        );
        add_rewrite_tag( '%nps_survey_hash%', '([a-zA-Z0-9]+)' );
    }

    /**
     * Garante que as regras de reescrita sejam atualizadas.
     */
    public function flush_rewrite_rules_on_load() {
        if ( ! wp_doing_ajax() && ! wp_doing_cron() && get_option( 'nps_rewrite_rules_flushed' ) !== 'yes' ) {
            flush_rewrite_rules();
            update_option( 'nps_rewrite_rules_flushed', 'yes' );
        }
    }

    /**
     * Lida com a resposta da pesquisa NPS quando o usuário clica no link.
     */
    public function handle_nps_survey_response() {
        if ( preg_match( '#/nps-survey/([a-zA-Z0-9]{32})/?#', $_SERVER['REQUEST_URI'], $matches ) ) {
            global $wpdb;

            $unique_hash = sanitize_text_field( $matches[1] );
            $score = isset( $_GET['score'] ) ? intval( $_GET['score'] ) : null;

            if ( is_null( $score ) || $score < 0 || $score > 10 ) {
                wp_redirect( home_url() );
                exit;
            }

            $table_name_instances = $wpdb->prefix . 'nps_survey_instances';

            $instance = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name_instances WHERE unique_hash = %s", $unique_hash ), ARRAY_A );

            if ( ! $instance ) {
                wp_die( __( 'Link de pesquisa inválido ou expirado.', 'nps-engine' ), __( 'Erro na Pesquisa', 'nps-engine' ) );
            }

            if ( $instance['responded'] == 1 ) {
                wp_redirect( home_url( '/nps-survey-thank-you/' ) );
                exit;
            }

            $updated = $wpdb->update(
                $table_name_instances,
                array( 'responded' => 1, 'response_timestamp' => current_time( 'mysql' ), 'score' => $score, 'comment' => null ),
                array( 'id' => $instance['id'] ),
                array( '%d', '%s', '%d', '%s' ),
                array( '%d' )
            );

            if ( $updated !== false ) {
                wp_redirect( home_url( '/nps-survey-thank-you/' ) );
                exit;
            } else {
                error_log( 'NPS Engine DB Error (handle_nps_survey_response): ' . $wpdb->last_error . ' for hash ' . $unique_hash );
                wp_die( __( 'Ocorreu um erro ao registrar sua resposta.', 'nps-engine' ), __( 'Erro na Resposta', 'nps-engine' ) );
            }
        }
    }

    /**
     * Cria a página de agradecimento automaticamente na ativação.
     */
    public function create_thank_you_page() {
        $page_title = __( 'Obrigado pela sua resposta!', 'nps-engine' );
        $page_content = '';
        $page_slug = 'nps-survey-thank-you';

        $page_args = array( 'post_title' => $page_title, 'post_content' => $page_content, 'post_status' => 'publish', 'post_type' => 'page', 'post_name' => $page_slug );
        $existing_page = get_page_by_path( $page_slug );

        if ( ! $existing_page ) {
            wp_insert_post( $page_args );
        }
    }

    /**
     * Função para renderizar a página de agradecimento simples no front-end.
     */
    public function nps_survey_thank_you_page_template() {
        if ( is_page( 'nps-survey-thank-you' ) ) {
            ?>
            <!DOCTYPE html>
            <html <?php language_attributes(); ?>>
            <head>
                <meta charset="<?php bloginfo( 'charset' ); ?>">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title><?php _e( 'Obrigado pela sua resposta!', 'nps-engine' ); ?></title>
                <?php wp_head(); ?>
                <style>
                    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; background-color: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; flex-direction: column; }
                    .nps-thank-you-container { background-color: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); text-align: center; max-width: 500px; width: 90%; }
                    .nps-thank-you-container h1 { color: #28a745; margin-bottom: 20px; font-size: 2.2em; }
                    .nps-thank-you-container p { color: #555; font-size: 1.1em; line-height: 1.6; }
                    .nps-thank-you-container a { display: inline-block; margin-top: 25px; background-color: #0073aa; color: #fff; padding: 10px 20px; border-radius: 5px; text-decoration: none; transition: background-color 0.2s ease-in-out; }
                    .nps-thank-you-container a:hover { background-color: #005177; }
                    .nps-powered-by { margin-top: 20px; font-size: 0.8em; color: #888; }
                    .nps-powered-by a { color: #555; text-decoration: none; }
                    .nps-powered-by a:hover { text-decoration: underline; }
                </style>
            </head>
            <body>
                <div class="nps-thank-you-container">
                    <h1><?php _e( 'Obrigado!', 'nps-engine' ); ?></h1>
                    <p><?php _e( 'Sua resposta foi registrada com sucesso. Agradecemos muito seu feedback!', 'nps-engine' ); ?></p>
                    <a href="<?php echo esc_url( home_url() ); ?>"><?php _e( 'Voltar para o site', 'nps-engine' ); ?></a>
                </div>
                
                <div class="nps-powered-by">
                    <p>Powered by <a href="https://cabeza.com.br/nps-engine" target="_blank">NPS Engine for Wordpress</a></p>
                </div>

                <?php wp_footer(); ?>
            </body>
            </html>
            <?php
            exit;
        }
    }
}