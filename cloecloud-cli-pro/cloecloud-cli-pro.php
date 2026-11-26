<?php
/**
 * Plugin Name: Cloecloud CLI Pro
 * Description: Advanced command modules for Cloecloud CLI.
 * Version: 0.1.0
 * Author: Cloecloud
 * Text Domain: cloecloud-cli-pro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'is_plugin_active' ) ) {
    include_once ABSPATH . 'wp-admin/includes/plugin.php';
}

if ( ! is_plugin_active( 'cloecloud-cli/cloecloud-cli.php' ) ) {
    add_action( 'admin_notices', function () {
        echo '<div class="notice notice-error"><p>Cloecloud CLI Pro requires Cloecloud CLI to be active.</p></div>';
    } );
    return;
}

define( 'CLOECLOUD_CLI_PRO_VERSION', '0.1.0' );
define( 'CLOECLOUD_CLI_PRO_DIR', plugin_dir_path( __FILE__ ) );
define( 'CLOECLOUD_CLI_PRO_URL', plugin_dir_url( __FILE__ ) );

require_once CLOECLOUD_CLI_PRO_DIR . 'includes/class-cloecloud-pro-init.php';

function run_cloecloud_cli_pro() {
    $plugin = new Cloecloud_CLI_Pro_Init();
    $plugin->run();
}

run_cloecloud_cli_pro();
