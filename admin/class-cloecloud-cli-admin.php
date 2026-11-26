<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Cloecloud_CLI_Admin {
    public function register_menu() {
        add_menu_page(
            'Cloecloud CLI',
            'Cloecloud CLI',
            'manage_options',
            'cloecloud-cli',
            array( $this, 'render_app' ),
            'dashicons-hammer'
        );

        if ( ! (bool) apply_filters( 'cloecloud_cli_is_pro_active', false ) ) {
            add_submenu_page(
                'cloecloud-cli',
                'Upgrade to Pro',
                'Upgrade to Pro',
                'manage_options',
                'cloecloud-cli-upgrade',
                array( $this, 'render_upgrade' )
            );
        }
    }

    public function render_app() {
        require CLOECLOUD_CLI_PLUGIN_DIR . 'admin/partials/display-admin-settings.php';
    }

    public function render_upgrade() {
        require CLOECLOUD_CLI_PLUGIN_DIR . 'admin/partials/display-admin-upgrade.php';
    }

    public function enqueue_assets( $hook ) {
        if ( 'toplevel_page_cloecloud-cli' !== $hook ) {
            return;
        }

        $asset_path = CLOECLOUD_CLI_PLUGIN_DIR . 'build/index.asset.php';
        $asset_data = array(
            'dependencies' => array( 'wp-element', 'wp-components', 'wp-i18n', 'wp-api-fetch', 'wp-data' ),
            'version'      => CLOECLOUD_CLI_VERSION,
        );

        if ( file_exists( $asset_path ) ) {
            $asset_data = include $asset_path;
        }

        wp_enqueue_script(
            'cloecloud-cli-admin',
            CLOECLOUD_CLI_PLUGIN_URL . 'build/index.js',
            $asset_data['dependencies'],
            $asset_data['version'],
            true
        );

        wp_enqueue_style(
            'cloecloud-cli-admin',
            CLOECLOUD_CLI_PLUGIN_URL . 'build/index.css',
            array(),
            $asset_data['version']
        );

        wp_localize_script(
            'cloecloud-cli-admin',
            'cloecloudCliSettings',
            array(
                'restUrl' => esc_url_raw( rest_url( 'cloecloud/v1' ) ),
                'nonce'   => wp_create_nonce( 'wp_rest' ),
            )
        );
    }
}
