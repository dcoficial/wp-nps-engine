<?php
/**
 * Gerencia a criação e remoção das tabelas do banco de dados para o NPS Engine.
 *
 * @package NPS_Engine
 * @subpackage Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Sair se acessado diretamente
}

class NPS_Database_Manager {

    /**
     * Hook de ativação do plugin.
     * Cria as tabelas do banco de dados e agenda o cron job.
     */
    public function activate_nps_engine() {
        $this->create_nps_tables();

        if ( ! wp_next_scheduled( 'nps_survey_dispatch_cron' ) ) {
            wp_schedule_event( time(), 'daily', 'nps_survey_dispatch_cron' );
        }

        // Garante que as regras de reescrita sejam atualizadas.
        if ( class_exists('NPS_Public_Handlers') ) {
            $public_handlers = new NPS_Public_Handlers();
            $public_handlers->register_nps_rewrite_rule();
            flush_rewrite_rules();
        }
        update_option( 'nps_rewrite_rules_flushed', 'yes' );
    }

    /**
     * Hook de desativação do plugin.
     * Limpa o cron job e as regras de reescrita.
     */
    public function deactivate_nps_engine() {
        wp_clear_scheduled_hook( 'nps_survey_dispatch_cron' );
        flush_rewrite_rules();
        delete_option( 'nps_rewrite_rules_flushed' );
    }

    public function create_nps_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name_contacts = $wpdb->prefix . 'nps_contacts';
        $sql_contacts = "CREATE TABLE $table_name_contacts ( id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, name varchar(255) DEFAULT '' NOT NULL, email varchar(255) DEFAULT '' NOT NULL, status tinyint(1) DEFAULT 1 NOT NULL, origin varchar(50) DEFAULT 'manual' NOT NULL, wp_user_id bigint(20) UNSIGNED NULL, last_survey_sent datetime NULL, last_event_trigger datetime NULL, PRIMARY KEY (id), UNIQUE KEY email (email), KEY wp_user_id (wp_user_id) ) $charset_collate;";

        $table_name_instances = $wpdb->prefix . 'nps_survey_instances';
        $sql_instances = "CREATE TABLE $table_name_instances ( id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, contact_id bigint(20) UNSIGNED NOT NULL, unique_hash varchar(64) DEFAULT '' NOT NULL, send_timestamp datetime NOT NULL, responded tinyint(1) DEFAULT 0 NOT NULL, response_timestamp datetime NULL, score tinyint(2) NULL, comment text NULL, PRIMARY KEY (id), UNIQUE KEY unique_hash (unique_hash), KEY contact_id (contact_id) ) $charset_collate;";

        $table_name_rules = $wpdb->prefix . 'nps_rules';
        $sql_rules = "CREATE TABLE $table_name_rules ( id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, rule_name varchar(255) DEFAULT '' NOT NULL, trigger_type varchar(50) DEFAULT 'time' NOT NULL, event_slug varchar(100) NULL, delay_days int(11) DEFAULT 0 NOT NULL, interval_days int(11) DEFAULT 90 NOT NULL, status tinyint(1) DEFAULT 1 NOT NULL, PRIMARY KEY (id) ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql_contacts );
        dbDelta( $sql_instances );
        dbDelta( $sql_rules );
    }

    /**
     * Apaga todos os contatos e respostas do plugin.
     * Esta ação é irreversível.
     */
    public function reset_plugin_data() {
        global $wpdb;
        $table_name_contacts = $wpdb->prefix . 'nps_contacts';
        $table_name_instances = $wpdb->prefix . 'nps_survey_instances';

        // TRUNCATE é mais eficiente para limpar tabelas inteiras.
        $wpdb->query( "TRUNCATE TABLE $table_name_contacts" );
        $wpdb->query( "TRUNCATE TABLE $table_name_instances" );

        // Redireciona com uma mensagem de sucesso.
        NPS_Helper_Functions::redirect_with_message(
            'support_tools',
            __( 'Todos os contatos e respostas foram apagados com sucesso.', 'nps-engine' ),
            'success'
        );
    }
}
