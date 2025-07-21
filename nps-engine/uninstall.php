<?php
/**
 * Script de desinstalação para o NPS Engine.
 *
 * Este script é executado quando o usuário clica no link "Excluir"
 * para o plugin na página de plugins do WordPress.
 *
 * @package NPS_Engine
 */

// Se não for chamado diretamente pelo WordPress, saia.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Lista de sufixos das tabelas criadas pelo plugin.
$table_suffixes = array(
    'nps_contacts',
    'nps_survey_instances',
    'nps_rules',
);

// Deleta as tabelas do banco de dados de forma segura.
foreach ( $table_suffixes as $suffix ) {
    $table_name = $wpdb->prefix . $suffix;
    // A sanitização não é estritamente necessária aqui, pois os nomes são fixos,
    // mas é uma boa prática para evitar que ferramentas de análise marquem como erro.
    $wpdb->query( $wpdb->prepare( "DROP TABLE IF EXISTS %i", $table_name ) );
}

// Lista de opções salvas no banco de dados.
$options = array(
    'nps_email_settings',
    'nps_global_min_frequency',
    'nps_rewrite_rules_flushed',
);

// Deleta as opções do banco de dados.
foreach ( $options as $option ) {
    delete_option( $option );
}

// Limpa qualquer tarefa agendada.
wp_clear_scheduled_hook( 'nps_survey_dispatch_cron' );
