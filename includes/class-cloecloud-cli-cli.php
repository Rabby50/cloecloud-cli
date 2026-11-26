<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Cloecloud_CLI_CLI {
    /**
     * @var Cloecloud_CLI_Service_Registry
     */
    protected $services;

    /**
     * @var Cloecloud_CLI_Activity
     */
    protected $activity;

    public function __construct( Cloecloud_CLI_Service_Registry $services, Cloecloud_CLI_Activity $activity ) {
        $this->services = $services;
        $this->activity = $activity;
    }

    public function register_commands() {
        if ( ! class_exists( 'WP_CLI' ) ) {
            return;
        }

        WP_CLI::add_command( 'cloecloud-cli run', array( $this, 'handle_run' ) );
    }

    public function handle_run( $args, $assoc_args ) {
        $command_id = isset( $args[0] ) ? $args[0] : '';

        if ( empty( $command_id ) ) {
            WP_CLI::error( 'Missing command id.' );
            return;
        }

        $service = $this->services->get_service( $command_id );

        if ( ! $service ) {
            WP_CLI::error( 'Unknown command id.' );
            return;
        }

        $result = call_user_func( $service['callback'], $assoc_args );

        if ( is_wp_error( $result ) ) {
            $this->activity->log( $command_id, $command_id, 'error' );
            WP_CLI::error( $result->get_error_message() );
            return;
        }

        $this->activity->log( $command_id, $command_id, 'success' );

        WP_CLI::success( wp_json_encode( $result ) );
    }
}
