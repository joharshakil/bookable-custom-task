<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       wpexperts.io
 * @since      1.0.0
 *
 * @package    Woosquare_Plus
 * @subpackage Woosquare_Plus/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Woosquare_Plus
 * @subpackage Woosquare_Plus/admin
 * @author     Wpexpertsio <support@wpexperts.io>
 */
class Woosquare_Plus_Admin {

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
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/woosquare-plus-admin.css', array(), $this->version, 'all' );

		// <!-- Font Awesome -->
		wp_enqueue_style( "wosquareplus_font_awesome" , 'https://use.fontawesome.com/releases/v5.8.2/css/all.css', array(), $this->version, 'all' );
		// <!-- Bootstrap core CSS -->
		wp_enqueue_style( "wosquareplus_bootstrap" , plugin_dir_url( __FILE__ ) . 'css/material/css/bootstrap.min.css', array(), $this->version, 'all' );
		//  material style
		wp_enqueue_style( "wosquareplus_js_scrolltab" , 'https://rawgit.com/mikejacobson/jquery-bootstrap-scrolling-tabs/master/dist/jquery.scrolling-tabs.min.css', array(), $this->version, 'all' );
		//  Custom css for admin
		wp_enqueue_style( "woosquare_plus_admin_custom" , plugin_dir_url( __FILE__ ) . 'css/woosquare-plus-admin-custom.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/woosquare-plus-admin.js', array( 'jquery' ), $this->version, false );
		$localize_array = array(
				'ajax_url' => admin_url('admin-ajax.php'),
		);
		wp_localize_script($this->plugin_name, 'my_ajax_backend_scripts',  $localize_array  );


		// <!-- Bootstrap tooltips -->
		wp_enqueue_script( "wosquareplus_bootstrap_tooltips_js", 'https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.4/umd/popper.min.js', array( 'jquery' ), $this->version, false );
		// <!-- Bootstrap core JavaScript -->
		wp_enqueue_script( "wosquareplus_bootstrap_js", 'https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.4.1/js/bootstrap.min.js', array( 'jquery' ), $this->version, false );
		// <!-- MDB core JavaScript -->

		// <!-- Scrolltab JavaScript -->
		wp_enqueue_script( "wosquareplus_scrolltab_js", 'https://rawgit.com/mikejacobson/jquery-bootstrap-scrolling-tabs/master/dist/jquery.scrolling-tabs.min.js', array( 'jquery' ), $this->version, false );

		// <!-- waves JavaScript -->
		wp_enqueue_script( "wosquareplus_waves_js", plugin_dir_url( __FILE__ ) . 'js/waves.js', array( 'jquery' ), $this->version, false );

		// <!-- custom JavaScript -->
		wp_enqueue_script( "wosquareplus_custom_js", plugin_dir_url( __FILE__ ) . 'js/custom.min.js', array( 'jquery' ), $this->version, false );
	}

	/**
	 * Register the Menus for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function woosquare_plus_menus() {
		/* if(empty(get_option('woo_square_access_token_cauth')) and empty(get_option('woo_square_location_id'))){
			$msg = json_encode(array(
				'status' => false,
				
				'msg' => 'API Credentials!',
			));
			
			set_transient( 'woosquare_plus_notification', $msg, 12 * HOUR_IN_SECONDS );		
		} */

		$plugin_modules = get_option('activate_modules_woosquare_plus', true);

		add_menu_page('Woo Square Settings', 'WooSquare Plus', 'manage_options', 'square-settings', array(&$this, 'square_auth_page'), plugin_dir_url(__FILE__) . "../../_inc/images/square.png");
		$this->check_for_auth();
		if (!empty($plugin_modules['module_page'])) {
			foreach ($plugin_modules as $key => $value) {
				if ($value['module_activate']) {

					if (!empty(get_option('woo_square_access_token_cauth')) and !empty(get_option('woo_square_location_id'))) {
						if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
							$active_option = get_option('activate_modules_woosquare_plus');
							if($active_option['module_page']) {
								do_action('delete_option',$active_option['module_page']);
							}
						} else{
							add_submenu_page($value['module_menu_details']['parent_slug'], $value['module_menu_details']['page_title'], $value['module_menu_details']['menu_title'], $value['module_menu_details']['capability'], $value['module_menu_details']['menu_slug'], array(&$this, $value['module_menu_details']['function_callback']));
						}


					}
				}
			}
			add_submenu_page('square-settings', "Documentation Plus", "Documentation", 'manage_options', 'square-documentation', array(&$this, 'documentation_plugin_page'));
		}


	}
	
	public function check_for_auth(){
	    
	    	if(
				!empty($_REQUEST['access_token']) and
				!empty($_REQUEST['token_type']) and
				$_REQUEST['token_type'] == 'bearer'
		){

			if ( function_exists( 'wp_verify_nonce' ) && ! wp_verify_nonce( $_GET['wc_woosquare_token_nonce'], 'connect_woosquare' ) ) {
				wp_die( __( 'Cheatin&#8217; huh?', 'woosquare-square' ) );
			}

			$existing_token = get_option( 'woo_square_access_token' );
			// if token already exists, don't continue

			update_option('woo_square_auth_response',$_REQUEST);
			update_option('woo_square_access_token',$_REQUEST['access_token']);
			update_option('woosquare_plus_reauth_notification',$_REQUEST['access_token']);
			update_option('woo_square_refresh_token',$_REQUEST['refresh_token']);
			update_option('woo_square_access_token_cauth',$_REQUEST['access_token']);
			$square = new Square(get_option('woo_square_access_token'), get_option('woo_square_location_id'),WOOSQU_PLUS_APPID);

			$results = $square->getAllLocations();



			if(!empty($results['locations'])){
				foreach($results['locations'] as $result){
					$locations = $result;
					if(!empty($locations['capabilities'])){
						$caps = ' | '.implode(",",$locations['capabilities']).' ENABLED';
					}
					$location_id = ($locations['id']);
					$str[] = array(
							$location_id => $locations['name'].' '.str_replace("_"," ",$caps)
					);
				}
				update_option('woo_square_locations', $str);
				update_option('woo_square_business_name', $locations['name']);
				if(count($results['locations']) == 1){
					update_option('woo_square_location_id', $location_id);

				}
			}



			$square->authorize();
			wp_redirect(add_query_arg(
					array(
							'page'    => 'square-settings',
					),
					admin_url( 'admin.php' )
			));
			exit;
		}
		if(
				!empty($_REQUEST['disconnect_woosquare']) and
				!empty($_REQUEST['wc_woosquare_token_nonce'])
		){
			if ( function_exists( 'wp_verify_nonce' ) && ! wp_verify_nonce( $_GET['wc_woosquare_token_nonce'], 'disconnect_woosquare' ) ) {
				wp_die( __( 'Cheatin&#8217; huh?', 'woocommerce-square' ) );
			}

			//revoke token
			$oauth_connect_url = WOOSQU_PLUS_CONNECTURL;
			$headers = array(
					'Authorization' => 'Bearer '.get_option('woo_square_access_token'), // Use verbose mode in cURL to determine the format you want for this header
					'Content-Type'  => 'application/json;',
			);
			$redirect_url = add_query_arg(
					array(
							'page'    => 'wc-settings',
							'tab'    => 'checkout',
							'section'    => 'square-recurring',
							'app_name'    => WOOSQU_PLUS_APPNAME,
							'plug'    => WOOSQU_PLUS_PLUGIN_NAME,
					),
					admin_url( 'admin.php' )
			);

			$redirect_url = wp_nonce_url( $redirect_url, 'connect_wcsrs', 'wc_wcsrs_token_nonce' );
			$site_url = ( urlencode( $redirect_url ) );
			$args_renew = array(
					'body' => array(
							'header' => $headers,
							'action' => 'revoke_token',
							'site_url'    => $site_url,
					),
					'timeout' => 45,
					'sslverify' => FALSE,
			);

			$oauth_response = wp_remote_post( $oauth_connect_url, $args_renew );

			$decoded_oauth_response = json_decode( wp_remote_retrieve_body( $oauth_response ) );

			delete_option('woo_square_access_token');
			delete_option('woo_square_location_id');
			delete_option('woo_square_location_id_free');
			delete_option('woo_square_access_token_cauth');
			delete_option('woo_square_locations_free');
			delete_option('woo_square_business_name_free');
			wp_redirect(add_query_arg(
					array(
							'page'    => 'square-settings',
					),
					admin_url( 'admin.php' )
			));
			exit;
		}
    }

	function en_plugin_act(){

		$plugin_modules = get_option('activate_modules_woosquare_plus',true);

		if(!empty($_POST)
				and $_POST['action'] == 'en_plugin'
				and !empty($plugin_modules)
				and $_POST['status'] == 'enab'
		){
			$plugin_id = str_replace('myonoffswitch_','',$_POST['pluginid']);
			$plugin_modules[$plugin_id]['module_activate'] = false;
			update_option('activate_modules_woosquare_plus',$plugin_modules);

			//below condition for when payment gateway disabled sandbox condition also disabled so it will not conflicts with other features..
			if($plugin_id == "woosquare_payment"){
				$woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
				if($woocommerce_square_plus_settings['enabled'] == 'yes'){
					$woocommerce_square_plus_settings['enabled'] = 'no';
				}

				if($woocommerce_square_plus_settings['enable_sandbox'] == 'yes'){
					$woocommerce_square_plus_settings['enable_sandbox'] = 'no';
				}
				update_option('woocommerce_square_plus_settings',$woocommerce_square_plus_settings);
			}
			echo $msg = json_encode(array(
					'status' => true,
					'msg' => 'Addon Successfully Disabled!',
			));

		} else if (
				!empty($_POST)
				and $_POST['action'] == 'en_plugin'
				and !empty($plugin_modules)
				and $_POST['status'] == 'disab'){

			$plugin_id = str_replace('myonoffswitch_','',$_POST['pluginid']);
			$plugin_modules[$plugin_id]['module_activate'] = true;
			update_option('activate_modules_woosquare_plus',$plugin_modules);

			echo $msg = json_encode(array(
					'status' => true,

					'msg' => 'Addon Successfully Enabled!',
			));

		}
		set_transient( 'woosquare_plus_notification', $msg, 12 * HOUR_IN_SECONDS );
		die();

	}

	function woosquare_plus_notify(){
		$woosquare_plus_notification = json_decode(get_transient( 'woosquare_plus_notification' ));
		if($woosquare_plus_notification->status){
			$ss = 'success';
		} else {
			$ss = 'error';
		}
		$class = 'notice notice-'.$ss;
		$message = __( $woosquare_plus_notification->msg, 'sample-text-domain' );
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
		delete_transient( 'woosquare_plus_notification' );

	}

	function woosquare_plus_payment_order_check(){
		$woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
		$activate_modules_woosquare_plus = get_option('activate_modules_woosquare_plus',true);


		/*
    if($woocommerce_square_plus_settings['enable_sandbox'] == 'yes' and $activate_modules_woosquare_plus['sales_sync']['module_activate']){
        $class = 'notice notice-error';
        $message = __( 'WooCommerce / Square Order Sync work\'s on live transaction only.!', 'woosquare-square' );
        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
    }
     */




		if(
				empty(get_option('woo_square_access_token_cauth')) OR empty(get_option('woo_square_location_id'))
		){
			if(
					@$_POST['woo_square_settings'] != 1
			){
				$class = 'notice notice-error';
				$connectlink = get_admin_url().'admin.php?page=square-settings';
				$message = __('You must <a href="'.$connectlink.'">Connect your Square account</a> and select location in order to use WooSquare Plus functionality  ' , 'woosquare-square' );
				printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), ( $message ) );

			}
		}


	}


	/**
	 * Settings page action
	 */
	public function square_auth_page() {


		$this->checkOrAddPluginTables();
		$square = new Square(get_option('woo_square_access_token'), get_option('woo_square_location_id'),WOOSQU_PLUS_APPID);

		$errorMessage = '';
		$successMessage = '';

		// check if the location is not setuped
		if (get_option('woo_square_access_token') && !get_option('woo_square_location_id')) {
			$square->authorize();
		}

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			// setup account
			if (isset($_POST['woo_square_access_token'])) {
				$square->setAccessToken(sanitize_text_field($_POST['woo_square_access_token']));
				$square->setapp_id(sanitize_text_field($_POST['woo_square_app_id']));
				if ($square->authorize()) {
					$successMessage = 'Settings updated successfully!';
				} else {
					$errorMessage = 'Square Account Not Authorized';
				}
			}

			// save settings
			if (isset($_POST['woo_square_settings'])) {
				//update location id
				if( !empty($_POST['woo_square_location_id'])){
					$location_id = sanitize_text_field($_POST['woo_square_location_id']);
					@$woo_square_app_id = sanitize_text_field(WOOSQU_PLUS_APPID);
					update_option('woo_square_location_id', $location_id);
					$square->setLocationId($location_id);

				}
				$successMessage = 'Settings updated successfully!';
			}

		}
		$wooCurrencyCode    = get_option('woocommerce_currency');
		$squareCurrencyCode = get_option('woo_square_account_currency_code');

		if(!$squareCurrencyCode){
			$square->getCurrencyCode();
			$square->getapp_id();
			$squareCurrencyCode = get_option('woo_square_account_currency_code');
		}
		if ( $currencyMismatchFlag = ($wooCurrencyCode != $squareCurrencyCode) ){

		}

		include WOO_SQUARE_PLUS_PLUGIN_PATH . 'admin/partials/settings.php';
	}

	public function documentation_plugin_page(){
		wp_redirect('https://apiexperts.io/woosquare-plus-documentation/?utm_source=WordPress&utm_medium=PluginSale&utm_campaign=InStore');
		wp_die();
	}


	public function woosquare_plus_module_page(){
		$plugin_modules = get_option('activate_modules_woosquare_plus',true);
		unset($plugin_modules['module_page']);
		include WOO_SQUARE_PLUS_PLUGIN_PATH . 'admin/partials/module_views.php';
	}

	function checkOrAddPluginTables(){
		//create tables
		require_once  ABSPATH . '/wp-admin/includes/upgrade.php' ;
		global $wpdb;

		//deleted products table
		$del_prod_table = $wpdb->prefix.WOO_SQUARE_TABLE_DELETED_DATA;
		if ($wpdb->get_var("SHOW TABLES LIKE '$del_prod_table'") != $del_prod_table) {

			if (!empty($wpdb->charset))
				$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
			if (!empty($wpdb->collate))
				$charset_collate .= " COLLATE $wpdb->collate";


			$sql = "CREATE TABLE " . $del_prod_table . " (
				`square_id` varchar(50) NOT NULL,
							`target_id` bigint(20) NOT NULL,
							`target_type` tinyint(2) NULL,
							`name` varchar(255) NULL,
				PRIMARY KEY (`square_id`)
			) $charset_collate;";
			dbDelta($sql);
		}

		//logs table
		$sync_logs_table = $wpdb->prefix.WOO_SQUARE_TABLE_SYNC_LOGS;
		if ($wpdb->get_var("SHOW TABLES LIKE '$sync_logs_table'") != $sync_logs_table) {

			if (!empty($wpdb->charset))
				$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
			if (!empty($wpdb->collate))
				$charset_collate .= " COLLATE $wpdb->collate";


			$sql = "CREATE TABLE " . $sync_logs_table . " (
						`id` bigint(20) auto_increment NOT NULL,
						`target_id` bigint(20) NULL,
						`target_type` tinyint(2) NULL,
						`target_status` tinyint(1) NULL,
						`parent_id` bigint(20) NOT NULL default '0',
						`square_id` varchar(50) NULL,
						`action`  tinyint(3) NOT NULL,
						`date` TIMESTAMP NOT NULL,
						`sync_type` tinyint(1) NULL,
						`sync_direction` tinyint(1) NULL,
						`name` varchar(255) NULL,
						`message` text NULL,
						PRIMARY KEY (`id`)
				) $charset_collate;";
			dbDelta($sql);
		}
	}


	/* Callback Functions */

	public function square_item_sync_page(){
		if(function_exists('woo_square_script')){
			woo_square_script();
		}
		if(function_exists('square_settings_page')){
			square_settings_page();
		}
		
	}


	/* Callback Functions */

	public function square_payment_sync_page(){

		if(function_exists('woo_square_script')){
			woo_square_script();
		} 
		$this->square_payment_plugin_page();
		
	}
	
	/**
	 * square payment plugin page action
	 * @global type $wpdb
	 */
	function square_payment_plugin_page(){
		$square_payment_settin = get_option('woocommerce_square_plus_settings');
		 //$square_payment_setting_gift_card = get_option('woocommerce_square_gift_card_pay_settings');
		$square_payment_setting_google_pay= get_option('woocommerce_square_google_pay_settings');
		$woocommerce_square_gift_card_pay_enabled= get_option('woocommerce_square_gift_card_pay_enabled');
		$woocommerce_square_payment_reporting = get_option('woocommerce_square_payment_reporting');

		  include plugin_dir_path(__FILE__) . 'modules/square-payments/views/payment-settings.php';
		
	}

	public function square_order_sync_page(){
		$this->enqueue_styles();
		$this->enqueue_scripts();
		define('SQUARE_ORDER_SYNC_PLUGIN_URL', plugin_dir_path(__FILE__).'modules/order-sync');
		$errorMessage = '';
		$successMessage = '';
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			// save settings 
			update_option('squ_woo_order_sync', sanitize_text_field($_POST['squ_woo_order_sync']));
			update_option('sync_square_order_notify', sanitize_text_field(@$_POST['sync_square_order_notify']));
			
			if(sanitize_text_field($_POST['squ_woo_order_sync']) == 1){
				update_option('woo_square_application_id_for_callback', sanitize_text_field($_POST['woocommerce_square_application_id']));
				update_option('woo_square_access_token_for_callback', sanitize_text_field($_POST['woocommerce_square_access_token']));
				update_option('woo_square_location_id_for_callback', sanitize_text_field($_POST['woocommerce_square_location_id']));
				$square = new Square(get_option('woo_square_access_token'), get_option('woo_square_location_id'),WOOSQU_PLUS_APPID);
				$square->setupWebhook("PAYMENT_UPDATED",sanitize_text_field($_POST['woocommerce_square_access_token']),sanitize_text_field($_POST['woocommerce_square_location_id']));
			} 

		}
		include SQUARE_ORDER_SYNC_PLUGIN_URL . '/view/order_sync_settings.php';
	}

	public function square_customer_sync_page(){
		define('SQUARE_CUSTOMER_SYNC_PLUGIN_URL', plugin_dir_url(__FILE__).'modules/square-customers');
		wp_enqueue_script('woo_square_customer_script', SQUARE_CUSTOMER_SYNC_PLUGIN_URL . '/admin/js/customer-sync-integration-admin.js', array('jquery'));
		square_customer_sync_settings();
	}
	public function square_card_sync_page(){
		define('SQUARE_CUSTOMER_SYNC_PLUGIN_URL', plugin_dir_path(__FILE__).'modules/square-customers');
		$errorMessage = '';
		$successMessage = '';
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			// save settings 
			if (isset($_POST['woo_square_card_settings'])) {
				update_option('cust_add_myaccount', sanitize_text_field($_POST['cust_add_myaccount']));
				$successMessage = 'Settings updated successfully!';
			}
		}
		include SQUARE_CUSTOMER_SYNC_PLUGIN_URL . '/admin/partials/card_on_file_settings.php';
	}

	public function square_transaction_sync_page(){
		// define('SQUARE_CUSTOMER_SYNC_PLUGIN_URL', plugin_dir_path(__FILE__).'modules/transaction-notes');
		require_once plugin_dir_path( __FILE__ ) . '../admin/modules/transaction-notes/transaction-notes.php';
		$errorMessage = '';
		$successMessage = '';
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			// save settings 
			if (isset($_POST['selected_order_info'])) {
				update_option('selected_order_info', sanitize_text_field($_POST['selected_order_info']));
				$successMessage = 'Settings updated successfully!';
			}
		}

		$countries = new WC_Countries();
		$billing = $countries->get_address_fields($countries->get_base_country(), 'billing_');
		$keywords = null;
		if(!empty($billing) and is_array($billing)){
			$keywords  .= '{order_id} ';
			foreach($billing as $keys => $values){
				$keywords .= '{'.$keys.'} ';
			}
		}
		$selected_order_info = get_option('selected_order_info');
		if ($successMessage):
			$suc =	'<br/><div class="updated"><p>'.$successMessage.'</p></div>';
		endif;

		echo _get_transaction_note($selected_order_info,$keywords);


	}

}