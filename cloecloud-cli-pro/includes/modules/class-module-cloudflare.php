<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Cloecloud_CLI_Pro_Module_Cloudflare {
    const OPTION_KEY = 'cloecloud_cli_cloudflare';

    public function register_services( Cloecloud_CLI_Service_Registry $services ) {
        $services->register( 'cloudflare.purge', array( $this, 'purge_cache' ), 'manage_options' );
    }

    public function register_routes() {
        register_rest_route(
            'cloecloud/v1',
            '/cloudflare',
            array(
                array(
                    'methods'             => 'GET',
                    'callback'            => array( $this, 'get_settings' ),
                    'permission_callback' => array( $this, 'can_manage' ),
                ),
                array(
                    'methods'             => 'POST',
                    'callback'            => array( $this, 'save_settings' ),
                    'permission_callback' => array( $this, 'can_manage' ),
                ),
            )
        );
    }

    public function purge_cache( $args ) {
        $settings = $this->get_option();

        $api_token = isset( $args['api_token'] ) ? sanitize_text_field( $args['api_token'] ) : $settings['api_token'];
        $zone_id = isset( $args['zone_id'] ) ? sanitize_text_field( $args['zone_id'] ) : $settings['zone_id'];
        $purge_everything = isset( $args['purge_everything'] ) ? (bool) $args['purge_everything'] : true;
        $files = isset( $args['files'] ) && is_array( $args['files'] ) ? array_map( 'esc_url_raw', $args['files'] ) : array();

        if ( empty( $api_token ) || empty( $zone_id ) ) {
            return new WP_Error( 'cloecloud_cli_cloudflare_missing', 'Cloudflare API token and zone ID are required.' );
        }

        $endpoint = sprintf( 'https://api.cloudflare.com/client/v4/zones/%s/purge_cache', $zone_id );
        $body = $purge_everything ? array( 'purge_everything' => true ) : array( 'files' => $files );

        $response = wp_remote_post(
            $endpoint,
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_token,
                    'Content-Type'  => 'application/json',
                ),
                'body'    => wp_json_encode( $body ),
                'timeout' => 15,
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $status = wp_remote_retrieve_response_code( $response );
        $payload = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 200 !== $status || empty( $payload['success'] ) ) {
            return new WP_Error( 'cloecloud_cli_cloudflare_failed', 'Cloudflare purge failed.', array( 'status' => $status, 'details' => $payload ) );
        }

        return array(
            'message' => 'Cloudflare cache purged.',
            'result'  => $payload,
        );
    }

    public function get_settings() {
        return rest_ensure_response( $this->get_option() );
    }

    public function save_settings( WP_REST_Request $request ) {
        $payload = $request->get_json_params();

        $settings = array(
            'api_token' => isset( $payload['api_token'] ) ? sanitize_text_field( $payload['api_token'] ) : '',
            'zone_id'   => isset( $payload['zone_id'] ) ? sanitize_text_field( $payload['zone_id'] ) : '',
        );

        update_option( self::OPTION_KEY, $settings );

        return rest_ensure_response( $settings );
    }

    private function get_option() {
        $settings = get_option( self::OPTION_KEY, array() );
        return wp_parse_args(
            is_array( $settings ) ? $settings : array(),
            array(
                'api_token' => '',
                'zone_id'   => '',
            )
        );
    }

    public function can_manage() {
        return current_user_can( 'manage_options' );
    }
}
