<?php

/**
 * @package Incoin
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

final class Incoin {

	/**
	 * The single instance of the class.
	 *
	 * @var Incoin
	 */
	protected static $_instance = null;

    /**
	 * @since 1.0.0
     *
	 * @return Incoin
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

    /**
	 * Constructor
     *
     * @since 1.0.0
     *
     * @return void
	 */
	public function __construct() {
		$this->define_constants();
		$this->includes();
		$this->init_hooks();
	}

    /**
	 * Define constants
     *
     * @since 1.0.0
     *
     * @return void
	 */
	private function define_constants() {

        $plugin_data = get_file_data( INCOIN_PLUGIN_FILE, array(
			'Version' => 'Version',
		) );

		$this->define( 'INCOIN_ABSPATH', dirname( INCOIN_PLUGIN_FILE ) . '/' );
		$this->define( 'INCOIN_VERSION', $plugin_data['Version'] );

	}

    /**
	 * Include required core files used in admin and on the frontend.
     *
     * @since 1.0.0
     *
     * @return void
	 */
	public function includes() {

		/**
		 * Core classes
		 */
		include_once INCOIN_ABSPATH . 'includes/incoin-core-functions.php';
		include_once INCOIN_ABSPATH . 'includes/class-incoin-install.php';
		include_once INCOIN_ABSPATH . 'includes/class-incoin-woocommerce.php';

		if ( is_admin() ) {
			include_once INCOIN_ABSPATH . 'includes/admin/class-incoin-admin.php';
		}

    }

    /**
     * Hook into actions and filters.
     *
     * @since 1.0.0
     *
     * @return void
	 */
	private function init_hooks() {
		register_activation_hook( INCOIN_PLUGIN_FILE, array( 'Incoin_Install', 'install' ) );

		add_action( 'init', array( $this, 'init' ) );
	}

    /**
     * Define constant if not already set.
     *
     * @since 1.0.0
     *
     * @param string $name
	 * @param mixed  $value
     * @return void
     */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Init Incoin when WordPress Initialises
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init() {

		// Set up localisation.
		$this->load_plugin_textdomain();

	}

	/**
	 * Load Localisation files
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'incoin', false, plugin_basename( dirname( INCOIN_PLUGIN_FILE ) ) . '/i18n/languages' );
	}

	/**
	 * Get the plugin url
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', INCOIN_PLUGIN_FILE ) );
	}

}