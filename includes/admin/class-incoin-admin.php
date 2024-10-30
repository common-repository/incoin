<?php

/**
 * @package Incoin
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

class Incoin_Admin {

	/**
	 * Constructor
     *
     * @since 1.0.0
     *
     * @return void
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'includes' ) );
	}

    /**
	 * Include any classes we need within admin
     *
     * @since 1.0.0
     *
     * @return void
	 */
	public function includes() {
	}

}

new Incoin_Admin();