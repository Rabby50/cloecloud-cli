<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Cloecloud_CLI_Pro_Module_Builder {
    const OPTION_KEY = 'cloecloud_cli_macros';

    /**
     * @var Cloecloud_CLI_Service_Registry
     */
    protected $services;

    public function __construct( Cloecloud_CLI_Service_Registry $services ) {
        $this->services = $services;
    }

    public function register_services( Cloecloud_CLI_Service_Registry $services ) {
        $services->register( 'builder.run', array( $this, 'run_macro' ), 'manage_options' );
    }

    public function register_routes() {
        register_rest_route(
            'cloecloud/v1',
            '/macros',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'list_macros' ),
                    'permission_callback' => array( $this, 'can_manage' ),
                ),
                array(
                    'methods'             => 'POST',
                    'callback'            => array( $this, 'create_macro' ),
                    'permission_callback' => array( $this, 'can_manage' ),
                ),
            )
        );

        register_rest_route(
            'cloecloud/v1',
            '/macros/(?P<id>[a-zA-Z0-9-_]+)',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'get_macro' ),
                    'permission_callback' => array( $this, 'can_manage' ),
                ),
                array(
                    'methods'             => 'PUT',
                    'callback'            => array( $this, 'update_macro' ),
                    'permission_callback' => array( $this, 'can_manage' ),
                ),
                array(
                    'methods'             => 'DELETE',
                    'callback'            => array( $this, 'delete_macro' ),
                    'permission_callback' => array( $this, 'can_manage' ),
                ),
            )
        );

        register_rest_route(
            'cloecloud/v1',
            '/macros/(?P<id>[a-zA-Z0-9-_]+)/run',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'run_macro_by_id' ),
                'permission_callback' => array( $this, 'can_manage' ),
            )
        );
    }

    public function run_macro( $args ) {
        $macro_id = isset( $args['macro_id'] ) ? sanitize_text_field( $args['macro_id'] ) : '';
        return $this->execute_macro( $macro_id );
    }

    public function run_macro_by_id( WP_REST_Request $request ) {
        return $this->execute_macro( $request['id'] );
    }

    public function list_macros() {
        return rest_ensure_response( $this->get_macros() );
    }

    public function get_macro( WP_REST_Request $request ) {
        $macros = $this->get_macros();
        $id = $request['id'];

        if ( ! isset( $macros[ $id ] ) ) {
            return new WP_Error( 'cloecloud_cli_macro_missing', 'Macro not found.', array( 'status' => 404 ) );
        }

        return rest_ensure_response( $macros[ $id ] );
    }

    public function create_macro( WP_REST_Request $request ) {
        $macros = $this->get_macros();
        $macro = $this->sanitize_macro( $request->get_json_params() );

        if ( is_wp_error( $macro ) ) {
            return $macro;
        }

        $id = wp_generate_uuid4();
        $macro['id'] = $id;
        $macros[ $id ] = $macro;
        $this->save_macros( $macros );

        return rest_ensure_response( $macro );
    }

    public function update_macro( WP_REST_Request $request ) {
        $macros = $this->get_macros();
        $id = $request['id'];

        if ( ! isset( $macros[ $id ] ) ) {
            return new WP_Error( 'cloecloud_cli_macro_missing', 'Macro not found.', array( 'status' => 404 ) );
        }

        $macro = $this->sanitize_macro( $request->get_json_params() );

        if ( is_wp_error( $macro ) ) {
            return $macro;
        }

        $macro['id'] = $id;
        $macros[ $id ] = $macro;
        $this->save_macros( $macros );

        return rest_ensure_response( $macro );
    }

    public function delete_macro( WP_REST_Request $request ) {
        $macros = $this->get_macros();
        $id = $request['id'];

        if ( ! isset( $macros[ $id ] ) ) {
            return new WP_Error( 'cloecloud_cli_macro_missing', 'Macro not found.', array( 'status' => 404 ) );
        }

        unset( $macros[ $id ] );
        $this->save_macros( $macros );

        return rest_ensure_response( array( 'deleted' => true ) );
    }

    private function execute_macro( $macro_id ) {
        $macros = $this->get_macros();

        if ( empty( $macro_id ) || ! isset( $macros[ $macro_id ] ) ) {
            return new WP_Error( 'cloecloud_cli_macro_missing', 'Macro not found.', array( 'status' => 404 ) );
        }

        $macro = $macros[ $macro_id ];
        $results = array();

        foreach ( $macro['steps'] as $step ) {
            $command_id = $step['command'];
            $service = $this->services->get_service( $command_id );

            if ( ! $service ) {
                return new WP_Error( 'cloecloud_cli_macro_service_missing', 'Macro step is not registered.' );
            }

            if ( ! defined( 'WP_CLI' ) && ! current_user_can( $service['capability'] ) ) {
                return new WP_Error( 'cloecloud_cli_macro_forbidden', 'Insufficient permissions for macro step.' );
            }

            $result = call_user_func( $service['callback'], $step['args'] );

            if ( is_wp_error( $result ) ) {
                return $result;
            }

            $results[] = array(
                'command' => $command_id,
                'result'  => $result,
            );
        }

        return array(
            'message' => 'Macro executed successfully.',
            'results' => $results,
        );
    }

    private function sanitize_macro( $payload ) {
        $payload = is_array( $payload ) ? $payload : array();
        $name = isset( $payload['name'] ) ? sanitize_text_field( $payload['name'] ) : '';
        $steps = isset( $payload['steps'] ) && is_array( $payload['steps'] ) ? $payload['steps'] : array();

        if ( empty( $name ) ) {
            return new WP_Error( 'cloecloud_cli_macro_name', 'Macro name is required.' );
        }

        $normalized_steps = array();

        foreach ( $steps as $step ) {
            if ( ! is_array( $step ) || empty( $step['command'] ) ) {
                continue;
            }

            $normalized_steps[] = array(
                'command' => sanitize_text_field( $step['command'] ),
                'args'    => isset( $step['args'] ) && is_array( $step['args'] ) ? $step['args'] : array(),
            );
        }

        if ( empty( $normalized_steps ) ) {
            return new WP_Error( 'cloecloud_cli_macro_steps', 'Macro must contain at least one step.' );
        }

        return array(
            'name'  => $name,
            'steps' => $normalized_steps,
        );
    }

    private function get_macros() {
        $macros = get_option( self::OPTION_KEY, array() );

        return is_array( $macros ) ? $macros : array();
    }

    private function save_macros( $macros ) {
        update_option( self::OPTION_KEY, $macros );
    }

    public function can_manage() {
        return current_user_can( 'manage_options' );
    }
}
