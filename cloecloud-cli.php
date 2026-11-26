<?php
/**
 * Plugin Name: Cloecloud CLI
 * Description: Command Palette enhancements and admin productivity tools.
 * Version: 0.1.0
 * Author: Cloecloud
 * Text Domain: cloecloud-cli
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CLOECLOUD_CLI_VERSION', '0.1.0' );
define( 'CLOECLOUD_CLI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CLOECLOUD_CLI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

define( 'CLOECLOUD_CLI_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

define( 'CLOECLOUD_CLI_TEXT_DOMAIN', 'cloecloud-cli' );

require_once CLOECLOUD_CLI_PLUGIN_DIR . 'includes/class-cloecloud-cli.php';

function run_cloecloud_cli() {
    $plugin = new Cloecloud_CLI();
    $plugin->run();
}

run_cloecloud_cli();
