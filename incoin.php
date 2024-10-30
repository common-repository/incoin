<?php

/**
 * Plugin Name:       inCoin
 * Description:       Crypto payment gateway for WooCommerce.
 * Version:           1.0.0
 * Requires at least: 5.3
 * Requires PHP:      7.4
 * Author:            inCoin
 * Author URI:        https://incoin.biz/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       incoin
 * Domain Path:       /i18n/languages/
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'INCOIN_PLUGIN_FILE' ) ) {
	define( 'INCOIN_PLUGIN_FILE', __FILE__ );
}

// Include the main Incoin class.
if ( ! class_exists( 'Incoin', false ) ) {
	include_once dirname( INCOIN_PLUGIN_FILE ) . '/includes/class-incoin.php';
}

if ( ! function_exists( 'incoin' ) ) {
    /**
     * Returns the main instance of Incoin.
     *
     * @since  1.0.0
     *
     * @return Incoin
     */
    function incoin() {
        return Incoin::instance();
    }
}

include_once ABSPATH . 'wp-admin/includes/plugin.php';

// Run only if WooCommerce is active. Else we display a notice.
if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
    incoin();
} else {
    add_action( 'admin_notices', function() {
        ?>
        <div class="notice notice-error">
            <p><strong><?php _e( 'WooCommerce inCoin requires WooCommerce to be installed and active.', 'incoin' ); ?></strong></p>
        </div>
        <?php
    } );
}