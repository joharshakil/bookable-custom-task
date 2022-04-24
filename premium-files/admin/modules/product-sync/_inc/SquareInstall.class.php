<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Installation/Activation Class.
 *
 * Handles the activation/installation of the plugin.
 *
 * @category Installation
 * @package  WooCommerce Square/Install
 * @version  1.0.0
 */
class WooSquare_Install {

	/**
	 * Intialize
	 *
	 * @access public
	 * @version 1.0.0
	 * @since 1.0.0
	 * @return bool
	 */
	public static function init() {

		add_action( 'admin_init', array( __CLASS__, 'check_version' ), 5 );
		return true;

	}

	/**
	 * Checks the plugin version
	 *
	 * @access public
	 * @since 1.0.0
	 * @version 1.0.0
	 * @return bool
	 */
	public static function check_version() {

		if ( ! defined( 'IFRAME_REQUEST' ) && ( get_option( 'WooSquare_version' ) != WooSquare_VERSION ) ) {
			self::install();
			do_action( 'WooSquare_updated' );
		}
		
		return true;
	}

	/**
	 * Do installs.
	 *
	 * @access public
	 * @since 1.0.0
	 * @version 1.0.0
	 * @return bool
	 */
	public static function install() {
		self::update_plugin_version();

		return true;
	}

	/**
	 * Updates the plugin version in db
	 *
	 * @access public
	 * @since 1.0.0
	 * @version 1.0.0
	 * @return bool
	 */
	private static function update_plugin_version() {
		delete_option( 'WooSquare_version' );
		add_option( 'WooSquare_version', WooSquare_VERSION );

		return true;
	}
}

WooSquare_Install::init();
