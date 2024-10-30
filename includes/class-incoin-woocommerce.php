<?php

/**
 * @package Incoin
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

class Incoin_Woocommerce {

	/**
	 * Hook into actions and filters.
     *
     * @since 1.0.0
     *
     * @return void
	 */
	public static function init() {
        add_filter( 'woocommerce_payment_gateways', array( __CLASS__, 'add_payment_gateways' ) );
	}

    /**
     * Add payment gateways.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public static function add_payment_gateways( $gateways ) {
        include_once INCOIN_ABSPATH . 'includes/gateways/incoin/class-wc-gateway-incoin.php';

        $gateways[] = 'WC_Gateway_Incoin';

        return $gateways;
    }

}

Incoin_Woocommerce::init();