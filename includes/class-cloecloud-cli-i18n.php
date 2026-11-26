<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Cloecloud_CLI_i18n {
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            CLOECLOUD_CLI_TEXT_DOMAIN,
            false,
            dirname( CLOECLOUD_CLI_PLUGIN_BASENAME ) . '/languages/'
        );
    }
}
