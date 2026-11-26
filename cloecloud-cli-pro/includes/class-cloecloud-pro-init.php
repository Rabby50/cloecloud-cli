<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Cloecloud_CLI_Pro_Init {
    protected $modules = array();

    public function run() {
        add_filter( 'cloecloud_cli_is_pro_active', '__return_true' );
        add_action( 'cloecloud_cli_register_services', array( $this, 'register_services' ) );
        add_filter( 'cloecloud_cli_register_commands', array( $this, 'register_commands' ) );
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
        add_filter( 'cloecloud_cli_settings_payload', array( $this, 'extend_settings_payload' ) );
    }

    public function register_services( Cloecloud_CLI_Service_Registry $services ) {
        require_once CLOECLOUD_CLI_PRO_DIR . 'includes/modules/class-module-woo.php';
        require_once CLOECLOUD_CLI_PRO_DIR . 'includes/modules/class-module-builder.php';
        require_once CLOECLOUD_CLI_PRO_DIR . 'includes/modules/class-module-webhooks.php';
        require_once CLOECLOUD_CLI_PRO_DIR . 'includes/modules/class-module-cloudflare.php';

        $this->modules['woo'] = new Cloecloud_CLI_Pro_Module_Woo();
        $this->modules['builder'] = new Cloecloud_CLI_Pro_Module_Builder( $services );
        $this->modules['webhooks'] = new Cloecloud_CLI_Pro_Module_Webhooks();
        $this->modules['cloudflare'] = new Cloecloud_CLI_Pro_Module_Cloudflare();

        foreach ( $this->modules as $module ) {
            $module->register_services( $services );
        }
    }

    public function register_rest_routes() {
        if ( isset( $this->modules['builder'] ) ) {
            $this->modules['builder']->register_routes();
        }

        if ( isset( $this->modules['cloudflare'] ) ) {
            $this->modules['cloudflare']->register_routes();
        }
    }

    public function extend_settings_payload( $settings ) {
        $macros = get_option( 'cloecloud_cli_macros', array() );
        $cloudflare = get_option( 'cloecloud_cli_cloudflare', array() );

        $settings['pro'] = array(
            'macros_count'          => is_array( $macros ) ? count( $macros ) : 0,
            'cloudflare_configured' => ! empty( $cloudflare['zone_id'] ) && ! empty( $cloudflare['api_token'] ),
        );

        return $settings;
    }

    public function register_commands( $commands ) {
        $commands[] = array(
            'id'          => 'woo.refund',
            'label'       => 'Refund WooCommerce Order',
            'description' => 'Refunds an order by ID.',
            'tier'        => 'pro',
        );
        $commands[] = array(
            'id'          => 'builder.run',
            'label'       => 'Run Custom Macro',
            'description' => 'Executes a user-defined macro.',
            'tier'        => 'pro',
        );
        $commands[] = array(
            'id'          => 'webhook.trigger',
            'label'       => 'Trigger Webhook',
            'description' => 'Triggers a configured webhook.',
            'tier'        => 'pro',
        );
        $commands[] = array(
            'id'          => 'cloudflare.purge',
            'label'       => 'Purge Cloudflare Cache',
            'description' => 'Purges the Cloudflare zone cache.',
            'tier'        => 'pro',
        );

        return $commands;
    }
}
