<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Cloecloud_CLI_Pro_Module_Webhooks {
    public function register_services( Cloecloud_CLI_Service_Registry $services ) {
        $services->register( 'webhook.trigger', array( $this, 'trigger_webhook' ), 'manage_options' );
    }

    public function trigger_webhook( $args ) {
        return array(
            'message' => 'Webhook trigger stubbed. Implement endpoint storage in Pro.',
            'args'    => $args,
        );
    }
}
