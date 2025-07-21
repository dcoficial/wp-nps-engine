<?php
/**
 * Plugin Name: NPS Engine
 * Plugin URI:  https://cabeza.com.br/nps-engine
 * Description: An all-in-one WordPress plugin that provides a complete solution for NPS surveys, contact management, form submissions, trigger and frequency controls, and automatic response capture.
 * Version:     1.1.0
 * Author:      Cabeza Marketing
 * Author URI:  https://cabeza.com.br
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: nps-engine
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Define a constante para o caminho do diretório do plugin.
 */
if ( ! defined( 'NPS_ENGINE_PLUGIN_DIR' ) ) {
    define( 'NPS_ENGINE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

/**
 * Classe principal do Plugin NPS Engine.
 * Responsável por carregar todos os módulos e gerenciar os hooks de ativação/desativação.
 */
final class NPS_Engine {

    /**
     * Instância única da classe.
     * @var NPS_Engine
     */
    private static $instance = null;

    /**
     * Módulos do plugin.
     * @var array
     */
    public $modules = [];

    /**
     * Garante que apenas uma instância da classe seja criada.
     * @return NPS_Engine
     */
    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Construtor da classe.
     * Carrega os arquivos de dependência e inicializa os módulos.
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_modules();
        $this->define_hooks();

        register_activation_hook( __FILE__, array( $this->modules['database_manager'], 'activate_nps_engine' ) );
        register_deactivation_hook( __FILE__, array( $this->modules['database_manager'], 'deactivate_nps_engine' ) );
    }

    /**
     * Carrega todos os arquivos de classe necessários.
     */
    private function load_dependencies() {
        // Core Logic
        require_once NPS_ENGINE_PLUGIN_DIR . 'includes/class-nps-database-manager.php';
        require_once NPS_ENGINE_PLUGIN_DIR . 'includes/class-nps-contacts-manager.php';
        require_once NPS_ENGINE_PLUGIN_DIR . 'includes/class-nps-email-settings.php';
        require_once NPS_ENGINE_PLUGIN_DIR . 'includes/class-nps-trigger-settings.php';
        require_once NPS_ENGINE_PLUGIN_DIR . 'includes/class-nps-survey-dispatcher.php';
        require_once NPS_ENGINE_PLUGIN_DIR . 'includes/class-nps-reports.php';
        require_once NPS_ENGINE_PLUGIN_DIR . 'includes/class-nps-helper-functions.php';

        // Admin
        require_once NPS_ENGINE_PLUGIN_DIR . 'admin/class-nps-admin-pages.php';
        require_once NPS_ENGINE_PLUGIN_DIR . 'admin/class-nps-admin-actions.php';

        // Public
        require_once NPS_ENGINE_PLUGIN_DIR . 'public/class-nps-public-handlers.php';
    }

    /**
     * Inicializa os módulos, aplicando injeção de dependência.
     */
    private function init_modules() {
        // Instancia os managers primeiro
        $this->modules['database_manager'] = new NPS_Database_Manager();
        $this->modules['contacts_manager'] = new NPS_Contacts_Manager();
        $this->modules['email_settings'] = new NPS_Email_Settings();
        $this->modules['trigger_settings'] = new NPS_Trigger_Settings();
        $this->modules['reports'] = new NPS_Reports();
        $this->modules['survey_dispatcher'] = new NPS_Survey_Dispatcher();

        // Injeta os managers nas classes que os utilizam
        $this->modules['admin_pages'] = new NPS_Admin_Pages(); // Não tem dependências diretas
        $this->modules['admin_actions'] = new NPS_Admin_Actions(
            $this->modules['reports'],
            $this->modules['contacts_manager'],
            $this->modules['email_settings'],
            $this->modules['trigger_settings'],
            $this->modules['database_manager']
        );
        $this->modules['public_handlers'] = new NPS_Public_Handlers();
    }

    /**
     * Define todos os hooks do plugin.
     */
    private function define_hooks() {
        // Admin Hooks
        add_action( 'admin_menu', array( $this->modules['admin_pages'], 'add_admin_menu_pages' ) );
        add_action( 'admin_init', array( $this->modules['admin_actions'], 'handle_admin_form_submissions' ) );
        add_action( 'admin_enqueue_scripts', array( $this->modules['admin_pages'], 'enqueue_admin_styles' ) );

        // Public Hooks
        add_action( 'init', array( $this->modules['public_handlers'], 'register_nps_rewrite_rule' ) );
        add_action( 'wp_loaded', array( $this->modules['public_handlers'], 'flush_rewrite_rules_on_load' ) );
        add_action( 'init', array( $this->modules['public_handlers'], 'handle_nps_survey_response' ) );
        add_action( 'init', array( $this->modules['public_handlers'], 'create_thank_you_page' ) );
        add_action( 'template_redirect', array( $this->modules['public_handlers'], 'nps_survey_thank_you_page_template' ) );

        // Cron Hooks
        add_action( 'nps_survey_dispatch_cron', array( $this->modules['survey_dispatcher'], 'nps_dispatch_cron_job' ) );
    }

    /**
     * Previne a clonagem da instância.
     */
    private function __clone() {}

    /**
     * Previne a desserialização da instância.
     */
    public function __wakeup() {}
}

// Inicia o plugin
function nps_engine_run() {
    return NPS_Engine::get_instance();
}
nps_engine_run();
