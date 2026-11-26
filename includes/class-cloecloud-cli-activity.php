<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Cloecloud_CLI_Activity {
    const OPTION_KEY = 'cloecloud_cli_activity';
    const MAX_ENTRIES = 200;

    public function log( $command_id, $label, $status = 'success' ) {
        $entries = $this->get_entries();

        $entries[] = array(
            'id'         => wp_generate_uuid4(),
            'command_id' => sanitize_text_field( $command_id ),
            'label'      => sanitize_text_field( $label ),
            'status'     => sanitize_text_field( $status ),
            'user_id'    => get_current_user_id(),
            'timestamp'  => time(),
        );

        $entries = array_slice( $entries, - self::MAX_ENTRIES );
        update_option( self::OPTION_KEY, $entries, false );
    }

    public function get_recent( $limit = 50 ) {
        $entries = $this->get_entries();
        $entries = array_reverse( $entries );

        return array_slice( $entries, 0, $limit );
    }

    public function count() {
        return count( $this->get_entries() );
    }

    private function get_entries() {
        $entries = get_option( self::OPTION_KEY, array() );
        return is_array( $entries ) ? $entries : array();
    }
}
