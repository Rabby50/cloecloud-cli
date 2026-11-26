<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Cloecloud_CLI_Pro_Module_Woo {
    public function register_services( Cloecloud_CLI_Service_Registry $services ) {
        $services->register( 'woo.refund', array( $this, 'refund_order' ), 'manage_woocommerce' );
    }

    public function refund_order( $args ) {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return new WP_Error( 'cloecloud_cli_woo_missing', 'WooCommerce is not active.' );
        }

        $order_id = isset( $args['order_id'] ) ? absint( $args['order_id'] ) : 0;

        if ( ! $order_id ) {
            return new WP_Error( 'cloecloud_cli_missing_order', 'Order ID is required.' );
        }

        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return new WP_Error( 'cloecloud_cli_invalid_order', 'Order not found.' );
        }

        $amount = isset( $args['amount'] ) ? wc_format_decimal( $args['amount'] ) : $order->get_total();
        $reason = isset( $args['reason'] ) ? sanitize_text_field( $args['reason'] ) : '';
        $restock = isset( $args['restock_items'] ) ? (bool) $args['restock_items'] : true;

        $refund = wc_create_refund(
            array(
                'amount'         => $amount,
                'reason'         => $reason,
                'order_id'       => $order_id,
                'refund_payment' => true,
                'restock_items'  => $restock,
            )
        );

        if ( is_wp_error( $refund ) ) {
            return $refund;
        }

        return array(
            'message'   => 'Refund created successfully.',
            'refund_id' => $refund->get_id(),
            'amount'    => $amount,
        );
    }
}
