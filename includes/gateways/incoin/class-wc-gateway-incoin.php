<?php

/**
 * @package Incoin
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

class WC_Gateway_Incoin extends WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
     *
     * @since 1.0.0
     *
     * @return void
	 */
    public function __construct() {
        $this->id                 = 'incoin';
        $this->method_title       = __( 'inCoin', 'incoin' );
        $this->method_description = __( 'Take payments in crypto via inCoin', 'incoin' );

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

		// Define user set variables.
        $this->enabled     = $this->get_option( 'enabled' );
        $this->title       = $this->get_option( 'title' );
        $this->description = $this->get_option( 'description' );
        $this->sandbox     = 'yes' === $this->get_option( 'sandbox' );
        $this->endpoint    = $this->sandbox ? 'https://payment.sandbox.incoin.biz' : 'https://payment.incoin.biz';
        $this->api_key     = $this->sandbox ? $this->get_option( 'sandbox_api_key' ) : $this->get_option( 'live_api_key' );
        $this->api_secret  = $this->sandbox ? $this->get_option( 'sandbox_api_secret' ) : $this->get_option( 'live_api_secret' );

        // Actions
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
        add_action( 'woocommerce_api_incoin', array( $this, 'webhook' ) );
        add_filter( 'woocommerce_gateway_title', array( $this, 'change_incoin_gateway_title' ), 10, 2 );
    }

    /**
	 * Initialise Gateway Settings Form Fields.
     *
     * @since 1.0.0
     *
     * @return void
	 */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => __( 'Enable/Disable', 'incoin' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable inCoin', 'incoin' ),
                'default' => 'no',
            ),
            'title' => array(
                'title'   => __( 'Title', 'incoin' ),
                'type'    => 'safe_text',
                'default' => __( 'Pay in crypto', 'incoin' ),
            ),
            'description' => array(
                'title'   => __( 'Description', 'incoin' ),
                'type'    => 'textarea',
                'default' => __( 'Cryptocurrency payment gateway', 'incoin' ),
            ),
            'live_api_key' => array(
                'title' => __( 'Live API key', 'incoin' ),
                'type'  => 'password',
            ),
            'live_api_secret' => array(
                'title' => __( 'Live API secret key (for callback verification)', 'incoin' ),
                'type'  => 'password',
            ),
            'sandbox' => array(
                'title'   => __( 'Sandbox', 'incoin' ),
                'label'   => __( 'Enable sandbox', 'incoin' ),
                'type'    => 'checkbox',
                'default' => 'yes',
            ),
            'sandbox_api_key' => array(
                'title' => __( 'Sandbox API key', 'incoin' ),
                'type'  => 'password',
            ),
            'sandbox_api_secret' => array(
                'title' => __( 'Sandbox API secret key (for callback verification)', 'incoin' ),
                'type'  => 'password',
            ),
        );
    }

    /**
	 * Process the payment and return the result.
	 *
     * @since 1.0.0
     *
	 * @param  int $order_id
	 * @return array|null
	 */
    public function process_payment( $order_id ) {
        global $woocommerce;

        $order = new WC_Order( $order_id );

        // Setup params
        $body = array(
            'amount'               => $order->get_total(),
            'currency'             => get_woocommerce_currency(),
            'customerEmailAddress' => $order->get_billing_email(),
            'clientReferenceId'    => $order->get_id(),
            'returnUrl'            => $this->get_return_url( $order ),
            'callbackUrl'          => site_url( '/wc-api/incoin' ),
        );

        // Setup args
        $args = array(
            'body'    => wp_json_encode( $body ),
            'headers' => array(
                'x-api-key'    => $this->api_key,
                'Content-Type' => 'application/json',
            ),
        );

        // Post to the API.
        $response = wp_remote_post( $this->endpoint . '/api/checkout-v0/createsession', $args );

        if( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
            $body = json_decode( $response['body'] );

            // Empty the cart.
            $woocommerce->cart->empty_cart();

            // Return the URL of inCoin to which the customer will be redirected.
            return array(
                'result'   => 'success',
                'redirect' => $body->sessionUrl,
            );
        } else {
            wc_add_notice( __( 'Oops, looks like something went wrong. Please choose another payment method or try again later.', 'incoin' ), 'error' );
            return;
        }
    }

    /**
     * Handle webhook.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function webhook() {
        $raw_data = file_get_contents( 'php://input' );

        // Get signature info. Format is: t=XXX,v=XXX
        $callback_signature = explode( ',', sanitize_text_field( $_SERVER['HTTP_CALLBACK_SIGNATURE'] ?? '' ) );
        $timestamp          = substr( $callback_signature[0] ?? '', 2 );
        $value              = substr( $callback_signature[1] ?? '', 2 );

        // Create our own hash which we use to verify against the given hash.
        $hash = hash_hmac( 'sha256', $timestamp . '.' . $raw_data, $this->api_secret );

        // If the hash we created with our secret key matches the hash value of the callback signature we know it's authentic.
        if( $value !== $hash || ! $raw_data ) {
            return;
        }

        $data  = json_decode( $raw_data );
        $order = wc_get_order( absint( $data->ClientReferenceId ) );

        if( $order && 'SUCCESS' === $data->Status ) {
            $order->payment_complete();
        }
    }

    /**
     * Prepend 'inCoin' to the start of the payment gateway title in the checkout.
     *
     * @since 1.0.0
     *
     * @param  string $title
     * @param  string $gateway_id
     * @return string
     */
    public function change_incoin_gateway_title( $title, $gateway_id ) {
        if( is_checkout() && 'incoin' === $gateway_id ) {
            $title = 'inCoin &#8211; ' . $title;
        }

        return $title;
    }

    /**
	 * Load admin scripts.
	 *
	 * @since 1.0.0
     *
     * @return void
	 */
	public function admin_scripts() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		if ( 'woocommerce_page_wc-settings' !== $screen_id || ( isset( $_GET['section'] ) && 'incoin' !== $_GET['section'] ) ) {
			return;
		}

		wp_enqueue_style( 'incoin_gateway_admin', incoin()->plugin_url() . '/includes/gateways/incoin/assets/css/incoin-admin.css', array(), INCOIN_VERSION );
	}

}