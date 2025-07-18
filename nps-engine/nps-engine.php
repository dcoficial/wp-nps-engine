<?php
/**
 * Plugin Name: NPS Engine
 * Plugin URI:  https://cabeza.com.br/nps-engine
 * Description: An all-in-one WordPress plugin that provides a complete solution for NPS surveys, contact management, form submissions, trigger and frequency controls, and automatic response capture.
 * Version:     1.0.0
 * Author:      Cabeza Marketing
 * Author URI:  https://cabeza.com.br
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: nps-engine
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Sair se acessado diretamente
}

/**
 * Define a constante para o caminho do diretório do plugin.
 */
if ( ! defined( 'NPS_ENGINE_PLUGIN_DIR' ) ) {
    define( 'NPS_ENGINE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

/**
 * Classe principal do Plugin NPS Engine.
 */
class NPS_Engine {

    /**
     * Construtor da classe.
     */
    public function __construct() {
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
        
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_cron_hooks();
        $this->init_modules();

        register_activation_hook( __FILE__, array( $this, 'activate_nps_engine' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate_nps_engine' ) );
    }

    /**
     * Carrega os arquivos de tradução do plugin.
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'nps-engine',
            false,
            dirname( plugin_basename( __FILE__ ) ) . '/languages/'
        );
    }
    
    private function load_dependencies() {
        require_once NPS_ENGINE_PLUGIN_DIR . 'includes/class-nps-database-manager.php';
        require_once NPS_ENGINE_PLUGIN_DIR . 'includes/class-nps-contacts-manager.php';
        require_once NPS_ENGINE_PLUGIN_DIR . 'includes/class-nps-email-settings.php';
        require_once NPS_ENGINE_PLUGIN_DIR . 'includes/class-nps-trigger-settings.php';
        require_once NPS_ENGINE_PLUGIN_DIR . 'includes/class-nps-survey-dispatcher.php';
        require_once NPS_ENGINE_PLUGIN_DIR . 'includes/class-nps-reports.php';
        require_once NPS_ENGINE_PLUGIN_DIR . 'includes/class-nps-helper-functions.php';
        require_once NPS_ENGINE_PLUGIN_DIR . 'admin/class-nps-admin-pages.php';
        require_once NPS_ENGINE_PLUGIN_DIR . 'admin/class-nps-admin-actions.php';
        require_once NPS_ENGINE_PLUGIN_DIR . 'public/class-nps-public-handlers.php';
    }

    private function define_admin_hooks() {
        $admin_pages = new NPS_Admin_Pages();
        $admin_actions = new NPS_Admin_Actions();
        add_action( 'admin_menu', array( $admin_pages, 'add_admin_menu_pages' ) );
        add_action( 'admin_init', array( $admin_actions, 'handle_admin_form_submissions' ) );
        add_action( 'admin_enqueue_scripts', array( $admin_pages, 'enqueue_admin_styles' ) );
    }

    private function define_public_hooks() {
        $public_handlers = new NPS_Public_Handlers();
        add_action( 'init', array( $public_handlers, 'register_nps_rewrite_rule' ) );
        add_action( 'wp_loaded', array( $public_handlers, 'flush_rewrite_rules_on_load' ) );
        add_action( 'init', array( $public_handlers, 'handle_nps_survey_response' ) );
        // A linha abaixo foi removida da função activate_nps_engine e movida para cá.
        add_action( 'init', array( $public_handlers, 'create_thank_you_page' ) );
        add_action( 'template_redirect', array( $public_handlers, 'nps_survey_thank_you_page_template' ) );
    }

    private function define_cron_hooks() {
        $survey_dispatcher = new NPS_Survey_Dispatcher();
        add_action( 'nps_survey_dispatch_cron', array( $survey_dispatcher, 'nps_dispatch_cron_job' ) );
    }

    private function init_modules() {
        new NPS_Contacts_Manager();
        new NPS_Trigger_Settings();
        new NPS_Reports();
        new NPS_Survey_Dispatcher();
        new NPS_Email_Settings();
    }

    public function activate_nps_engine() {
        $db_manager = new NPS_Database_Manager();
        $db_manager->create_nps_tables();
        if ( ! wp_next_scheduled( 'nps_survey_dispatch_cron' ) ) {
            wp_schedule_event( time(), 'daily', 'nps_survey_dispatch_cron' );
        }
        flush_rewrite_rules();
        update_option( 'nps_rewrite_rules_flushed', 'yes' );
    }

    public function deactivate_nps_engine() {
        wp_clear_scheduled_hook( 'nps_survey_dispatch_cron' );
        flush_rewrite_rules();
        delete_option( 'nps_rewrite_rules_flushed' );
    }
}

new NPS_Engine();