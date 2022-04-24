<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       wpexperts.io
 * @since      1.0.0
 *
 * @package    Woosquare_Plus
 * @subpackage Woosquare_Plus/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Woosquare_Plus
 * @subpackage Woosquare_Plus/public
 * @author     Wpexpertsio <support@wpexperts.io>
 */
class Woosquare_Plus_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woosquare_Plus_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woosquare_Plus_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/woosquare-plus-public.css', array(), $this->version, 'all' );

	}
	
	public function wp_remote_post_for_mps($url,$body,$contenttype=null){
		if($contenttype == 'form'){
			$contype='application/x-www-form-urlencoded'; 
		} else {
			$contype='application/json';
		}
		if($contenttype != 'array'){
			$headers = array(
				"accept" => "application/json",
				"cache-control" => "no-cache",
				"content-type" => $contype
			);
		} else {
			$headers = array();
		}
		$response = wp_remote_post( $url, array(
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking' => true,
			'headers' => $headers,
			'body' => $body,
			'cookies' => array()
			)
		);
		return $response;
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woosquare_Plus_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woosquare_Plus_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/woosquare-plus-public.js', array( 'jquery' ), $this->version, false );

	}

}
