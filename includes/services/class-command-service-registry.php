<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Cloecloud_CLI_Service_Registry {
    protected $services = array();

    public function register( $command_id, $callback, $capability = 'manage_options' ) {
        $this->services[ $command_id ] = array(
            'callback'   => $callback,
            'capability' => $capability,
        );
    }

    public function get_service( $command_id ) {
        return isset( $this->services[ $command_id ] ) ? $this->services[ $command_id ] : null;
    }

    public function register_default_services() {
        $cache_service = new Cloecloud_CLI_Cache_Service();
        $maintenance_service = new Cloecloud_CLI_Maintenance_Service();

        $this->register( 'cache.clear', array( $cache_service, 'clear_all' ), 'manage_options' );
        $this->register( 'maintenance.toggle', array( $maintenance_service, 'toggle' ), 'manage_options' );
    }
}
