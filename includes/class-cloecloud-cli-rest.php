<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Cloecloud_CLI_REST {
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

    public function register_routes() {
        register_rest_route(
            'cloecloud/v1',
            '/execute',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'handle_execute' ),
                'permission_callback' => array( $this, 'can_execute' ),
                'args'                => array(
                    'command_id' => array(
                        'type'     => 'string',
                        'required' => true,
                    ),
                    'args'       => array(
                        'type'     => 'array',
                        'required' => false,
                    ),
                ),
            )
        );

        register_rest_route(
            'cloecloud/v1',
            '/settings',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'get_settings' ),
                    'permission_callback' => function () {
                        return current_user_can( 'manage_options' );
                    },
                ),
                array(
                    'methods'             => 'POST',
                    'callback'            => array( $this, 'save_settings' ),
                    'permission_callback' => function () {
                        return current_user_can( 'manage_options' );
                    },
                ),
            )
        );

        register_rest_route(
            'cloecloud/v1',
            '/activity',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_activity' ),
                'permission_callback' => function () {
                    return current_user_can( 'manage_options' );
                },
            )
        );
    }

    public function can_execute( WP_REST_Request $request ) {
        $command_id = $request->get_param( 'command_id' );
        $service = $this->services->get_service( $command_id );

        if ( ! $service ) {
            return new WP_Error( 'cloecloud_cli_missing_service', 'Unknown command.', array( 'status' => 404 ) );
        }

        return current_user_can( $service['capability'] );
    }

    public function handle_execute( WP_REST_Request $request ) {
        $command_id = $request->get_param( 'command_id' );
        $args       = (array) $request->get_param( 'args' );
        $service    = $this->services->get_service( $command_id );

        if ( ! $service ) {
            return new WP_Error( 'cloecloud_cli_missing_service', 'Unknown command.', array( 'status' => 404 ) );
        }

        if ( ! $this->is_command_enabled( $command_id ) ) {
            return new WP_Error( 'cloecloud_cli_command_disabled', 'Command is disabled.', array( 'status' => 403 ) );
        }

        $callback = $service['callback'];
        $result = call_user_func( $callback, $args );

        if ( is_wp_error( $result ) ) {
            $this->activity->log( $command_id, $this->get_command_label( $command_id ), 'error' );
            return $result;
        }

        $this->activity->log( $command_id, $this->get_command_label( $command_id ), 'success' );

        return rest_ensure_response(
            array(
                'success' => true,
                'data'    => $result,
            )
        );
    }

    public function get_settings() {
        $settings_data = $this->get_settings_option();
        $settings = array(
            'pro_active' => (bool) apply_filters( 'cloecloud_cli_is_pro_active', false ),
            'commands'   => $this->get_command_definitions(),
            'toggles'    => $settings_data['toggles'],
            'ui'         => $settings_data['ui'],
            'metrics'    => array(
                'total_commands' => $this->activity->count(),
                'last_activity'  => $this->activity->get_recent( 1 ),
            ),
        );

        return rest_ensure_response( apply_filters( 'cloecloud_cli_settings_payload', $settings ) );
    }

    public function save_settings( WP_REST_Request $request ) {
        $payload = $request->get_json_params();
        $settings = $this->get_settings_option();

        if ( isset( $payload['toggles'] ) && is_array( $payload['toggles'] ) ) {
            $settings['toggles'] = array_map( 'boolval', $payload['toggles'] );
        }

        if ( isset( $payload['ui'] ) && is_array( $payload['ui'] ) ) {
            $settings['ui'] = array_merge( $settings['ui'], array_map( 'sanitize_text_field', $payload['ui'] ) );
        }

        update_option( 'cloecloud_cli_settings', $settings );

        return rest_ensure_response( $settings );
    }

    public function get_activity() {
        return rest_ensure_response( $this->activity->get_recent( 50 ) );
    }

    private function get_command_definitions() {
        $commands = array(
            array(
                'id'          => 'cache.clear',
                'label'       => 'Clear All Caches',
                'description' => 'Flushes WordPress object cache and transients.',
                'tier'        => 'free',
            ),
            array(
                'id'          => 'maintenance.toggle',
                'label'       => 'Toggle Maintenance Mode',
                'description' => 'Enable or disable maintenance mode.',
                'tier'        => 'free',
            ),
        );

        return apply_filters( 'cloecloud_cli_register_commands', $commands );
    }

    private function get_settings_option() {
        $defaults = array(
            'toggles' => array(),
            'ui'      => array(
                'theme'      => 'light',
                'shortcut'   => 'cmd+k',
                'retention'  => '30',
            ),
        );

        $settings = get_option( 'cloecloud_cli_settings', array() );

        return wp_parse_args( is_array( $settings ) ? $settings : array(), $defaults );
    }

    private function is_command_enabled( $command_id ) {
        $settings = $this->get_settings_option();
        return ! isset( $settings['toggles'][ $command_id ] ) || (bool) $settings['toggles'][ $command_id ];
    }

    private function get_command_label( $command_id ) {
        $commands = $this->get_command_definitions();

        foreach ( $commands as $command ) {
            if ( $command['id'] === $command_id ) {
                return $command['label'];
            }
        }

        return $command_id;
    }
}
