<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Cloecloud_CLI {
    /**
     * @var Cloecloud_CLI_Loader
     */
    protected $loader;

    /**
     * @var Cloecloud_CLI_Service_Registry
     */
    protected $services;

    /**
     * @var Cloecloud_CLI_Activity
     */
    protected $activity;

    public function __construct() {
        $this->load_dependencies();
        $this->set_locale();
        $this->register_services();
        $this->define_admin_hooks();
        $this->define_rest_hooks();
        $this->define_cli_hooks();
    }

    private function load_dependencies() {
        require_once CLOECLOUD_CLI_PLUGIN_DIR . 'includes/class-cloecloud-cli-loader.php';
        require_once CLOECLOUD_CLI_PLUGIN_DIR . 'includes/class-cloecloud-cli-i18n.php';
        require_once CLOECLOUD_CLI_PLUGIN_DIR . 'includes/class-cloecloud-cli-activity.php';
        require_once CLOECLOUD_CLI_PLUGIN_DIR . 'includes/class-cloecloud-cli-rest.php';
        require_once CLOECLOUD_CLI_PLUGIN_DIR . 'includes/class-cloecloud-cli-cli.php';
        require_once CLOECLOUD_CLI_PLUGIN_DIR . 'includes/services/class-command-service-registry.php';
        require_once CLOECLOUD_CLI_PLUGIN_DIR . 'includes/services/class-cache-service.php';
        require_once CLOECLOUD_CLI_PLUGIN_DIR . 'includes/services/class-maintenance-service.php';
        require_once CLOECLOUD_CLI_PLUGIN_DIR . 'admin/class-cloecloud-cli-admin.php';

        $this->loader = new Cloecloud_CLI_Loader();
        $this->services = new Cloecloud_CLI_Service_Registry();
        $this->activity = new Cloecloud_CLI_Activity();
    }

    private function set_locale() {
        $i18n = new Cloecloud_CLI_i18n();
        $this->loader->add_action( 'plugins_loaded', $i18n, 'load_plugin_textdomain' );
    }

    private function register_services() {
        $this->services->register_default_services();

        /**
         * Allow extensions to register additional services.
         *
         * @param Cloecloud_CLI_Service_Registry $services
         */
        do_action( 'cloecloud_cli_register_services', $this->services );
    }

    private function define_admin_hooks() {
        $admin = new Cloecloud_CLI_Admin();
        $this->loader->add_action( 'admin_menu', $admin, 'register_menu' );
        $this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_assets' );
    }

    private function define_rest_hooks() {
        $rest = new Cloecloud_CLI_REST( $this->services, $this->activity );
        $this->loader->add_action( 'rest_api_init', $rest, 'register_routes' );
    }

    private function define_cli_hooks() {
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            $cli = new Cloecloud_CLI_CLI( $this->services, $this->activity );
            $this->loader->add_action( 'cli_init', $cli, 'register_commands' );
        }
    }

    public function run() {
        $this->loader->run();
    }
}
