<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Cloecloud_CLI_Maintenance_Service {
    public function toggle() {
        $current = (bool) get_option( 'cloecloud_cli_maintenance_mode', false );
        $next = ! $current;

        update_option( 'cloecloud_cli_maintenance_mode', $next );

        return array(
            'enabled' => $next,
        );
    }
}
