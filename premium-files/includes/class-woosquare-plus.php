<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       wpexperts.io
 * @since      1.0.0
 *
 * @package    Woosquare_Plus
 * @subpackage Woosquare_Plus/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Woosquare_Plus
 * @subpackage Woosquare_Plus/includes
 * @author     Wpexpertsio <support@wpexperts.io>
 */
class Woosquare_Plus {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Woosquare_Plus_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'PLUGIN_NAME_VERSION_WOOSQUARE_PLUS' ) ) {
			$this->version = PLUGIN_NAME_VERSION_WOOSQUARE_PLUS;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'woosquare-plus';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->get_access_token_woosquare_plus();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Woosquare_Plus_Loader. Orchestrates the hooks of the plugin.
	 * - Woosquare_Plus_i18n. Defines internationalization functionality.
	 * - Woosquare_Plus_Admin. Defines all hooks for the admin area.
	 * - Woosquare_Plus_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-woosquare-plus-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-woosquare-plus-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-woosquare-plus-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		//import woosquare classes
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/modules/product-sync/_inc/Helpers.class.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/modules/product-sync/_inc/square.class.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/modules/product-sync/_inc/SquareToWooSynchronizer.class.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/modules/product-sync/_inc/WooToSquareSynchronizer.class.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/modules/product-sync/_inc/admin/ajax.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/modules/product-sync/_inc/admin/pages.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/modules/product-sync/_inc/SquareClient.class.php' ;
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/modules/product-sync/_inc/SquareSyncLogger.class.php' ;
		$activate_modules_woosquare_plus = get_option('activate_modules_woosquare_plus',true);
		if($activate_modules_woosquare_plus['woosquare_payment']['module_activate']){
			//define( 'WooSquare_PLUGIN_URL_PAYMENT', untrailingslashit( plugins_url( 'admin/modules/square-payments', dirname(__FILE__) )) );
			if (!defined('WooSquare_PLUGIN_URL_PAYMENT')) define( 'WooSquare_PLUGIN_URL_PAYMENT', untrailingslashit( plugins_url( 'admin/modules/square-payments', dirname(__FILE__) )) );
			// echo ;
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/modules/square-payments/SquarePaymentLogger.class.php' ;
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/modules/square-payments/SquarePayments.class.php' ;
		}
		
		if($activate_modules_woosquare_plus['customer_sync']['module_activate']){
			
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/modules/square-customers/customersync-integration.php' ;
		}
		

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-woosquare-plus-public.php';

		$this->loader = new Woosquare_Plus_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Woosquare_Plus_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Woosquare_Plus_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Woosquare_Plus_Admin( $this->get_plugin_name(), $this->get_version() );

        $page = @$_GET['page'];
        if(!empty($page)){
            $explode = explode('-',$page);
            if($explode[0] == 'woosquare' OR $explode[0] == 'square'){
                $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
                $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
            }
        }

		$this->loader->add_action( 'admin_menu', $plugin_admin, 'woosquare_plus_menus' );
		$this->loader->add_action( 'wp_ajax_en_plugin', $plugin_admin, 'en_plugin_act' );
		$this->loader->add_action( 'wp_ajax_nopriv_en_plugin', $plugin_admin, 'en_plugin_act' );
		
		if(!get_option('woosquare_plus_reauth_notification') and get_option('woo_square_access_token')){
			$msg = json_encode(array(
					'status' => true,
					'msg' => 'ReConnect through auth square to make system more smooth.!',
			));
			set_transient( 'woosquare_plus_notification', $msg, 12 * HOUR_IN_SECONDS );
		}
		
		$woosquare_plus_notification = get_transient( 'woosquare_plus_notification' );
		
		if(!empty(json_decode($woosquare_plus_notification))){
			$this->loader->add_action( 'admin_notices', $plugin_admin, 'woosquare_plus_notify' );
		}
		$this->loader->add_action( 'admin_notices', $plugin_admin, 'woosquare_plus_payment_order_check',999 );
		
		$activate_modules_woosquare_plus = get_option('activate_modules_woosquare_plus',true);
		//square sync module.
		
		if( $activate_modules_woosquare_plus['items_sync']['module_activate'] ){
    		require_once plugin_dir_path( __FILE__ ) . '../admin/modules/product-sync/product_sync.php';
    		//register ajax actions
    		//woo->square
    		require_once plugin_dir_path( __FILE__ ) . '../admin/modules/product-sync/_inc/admin/ajax.php';
    		add_action('wp_ajax_get_non_sync_woo_data',  'woo_square_plugin_get_non_sync_woo_data');
    		add_action('wp_ajax_start_manual_woo_to_square_sync',  'woo_square_plugin_start_manual_woo_to_square_sync');
    		add_action('wp_ajax_listsaved','woo_square_listsaved');
    		add_action('wp_ajax_sync_woo_category_to_square', 'woo_square_plugin_sync_woo_category_to_square');
    		add_action('wp_ajax_sync_woo_product_to_square', 'woo_square_plugin_sync_woo_product_to_square');
    		add_action('wp_ajax_terminate_manual_woo_sync', 'woo_square_plugin_terminate_manual_woo_sync');
    
    		//square->woo
    		add_action('wp_ajax_get_non_sync_square_data', 'woo_square_plugin_get_non_sync_square_data');
    		add_action('wp_ajax_start_manual_square_to_woo_sync',  'woo_square_plugin_start_manual_square_to_woo_sync');
    		add_action('wp_ajax_sync_square_category_to_woo', 'woo_square_plugin_sync_square_category_to_woo');
    		add_action('wp_ajax_sync_square_product_to_woo','woo_square_plugin_sync_square_product_to_woo');
    		add_action('wp_ajax_update_square_to_woo','update_square_to_woo_action');
    		add_action('wp_ajax_terminate_manual_square_sync',  'woo_square_plugin_terminate_manual_square_sync');
    		add_action('auto_sync_cron_job_hook', 'auto_sync_cron_job');
    		// add_action('plugins_loaded', 'auto_sync_cron_job');
		}
		
		
			
		if(
			@$activate_modules_woosquare_plus['customer_sync']['module_activate']
				OR
			$activate_modules_woosquare_plus['woosquare_card_on_file']['module_activate']
			){
			//square customer sync module.
			require_once plugin_dir_path( __FILE__ ) . '../admin/modules/square-customers/customersync-integration.php';
			require_once plugin_dir_path( __FILE__ ) . '../admin/modules/square-customers/admin/class-customersync-integration-admin.php';
			if( @$activate_modules_woosquare_plus['customer_sync']['module_activate']){
				$plugin_admin = new Customer_Sync_Integration_Admin( $this->get_plugin_name(), $this->get_version() );
				// $this->loader->add_action( 'init', $plugin_admin, 'syncAllCustomerToSquare');
				if(get_option('woo_square_customer_merging_option') == "1"){
					// Woo commerce customer Override square customer
					$this->loader->add_action( 'auto_sync_customer_cron_job_hook', $plugin_admin, 'syncAllCustomerToSquare' );
				} else if(get_option('woo_square_customer_merging_option') == "2") {
					//Square customer Override Woo commerce customer
					$this->loader->add_action( 'auto_sync_customer_cron_job_hook', $plugin_admin, 'syncCustomerDataFromSquare' );
				}
			}
		}
		
		if( $activate_modules_woosquare_plus['sales_sync']['module_activate'] ){
			require_once plugin_dir_path( __FILE__ ) . '../admin/modules/order-sync/order-sync.php';
		}

		if( $activate_modules_woosquare_plus['woosquare_transaction_addon']['module_activate'] ){
			require_once plugin_dir_path( __FILE__ ) . '../admin/modules/transaction-notes/transaction-notes.php';
			add_filter( 'woosquare_payment_order_note', 'woosquare_transaction_note_modified',10,2 );
		}

		if( $activate_modules_woosquare_plus['woosquare_modifiers']['module_activate'] ){
			require_once plugin_dir_path( __FILE__ ) . '../admin/modules/woosquare-modifier/woosquare-modifier.php';
			
        global $wpdb;

        $db_table_name = $wpdb->prefix . 'woosquare_modifier';  // table name
        $charset_collate = $wpdb->get_charset_collate();

        //Check to see if the table exists already, if not, then create it
        if($wpdb->get_var( "show tables like '$db_table_name'" ) != $db_table_name )
        {
                $sql = "CREATE TABLE $db_table_name (
                modifier_id BIGINT UNSIGNED NOT NULL auto_increment,
                modifier_set_name varchar(200) NOT NULL,
                modifier_slug varchar(200) NOT NULL,
                modifier_option varchar(200) NOT NULL,
                modifier_public int(1) NOT NULL DEFAULT 1,
                modifier_set_unique_id varchar(200),
                modifier_version varchar(200),
                PRIMARY KEY  (modifier_id),
                KEY modifier_set_name (modifier_set_name(20))
                ) $charset_collate;";
                
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                dbDelta( $sql );
                
                }
                
                $db_table_product_set_required  = $wpdb->prefix . 'woosquare_modifier_required';  // table name
                $charset_collate = $wpdb->get_charset_collate();
                
                //Check to see if the table exists already, if not, then create it
                /*  if($wpdb->get_var( "show tables like '$db_table_product_set_required'" ) != $db_table_name ) {
                $setsql = "CREATE TABLE $db_table_product_set_required (
                product_required_id BIGINT UNSIGNED NOT NULL auto_increment,
                product_required_name varchar(200) NOT NULL,
                produc_required_product_id varchar(200) NOT NULL,
                product_required_modifier_id int(1) NOT NULL DEFAULT 1,
                PRIMARY KEY  (product_required_id),
                KEY product_required_name (product_required_name(20))
                ) $charset_collate;";
                
                require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
                dbDelta($setsql);
                }*/

		}

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Woosquare_Plus_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Woosquare_Plus_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
	
	public function get_access_token_woosquare_plus(){
		// get it from where it save and check is expired than provide. 
			
			
			if(get_option('woo_square_access_token')){
				$woo_square_auth_response = get_option('woo_square_auth_response');
				if (is_object($woo_square_auth_response)) {
					$woo_square_auth_response = (array) $woo_square_auth_response;
				}
				if(
					!empty($woo_square_auth_response)
					and
					(strtotime($woo_square_auth_response['expires_at']) - 300) <= time()
				){ 
				
					$headers = array(
						'refresh_token' => $woo_square_auth_response['refresh_token'], // Use verbose mode in cURL to determine the format you want for this header
						'Content-Type'  => 'application/json;'
					);
					$oauth_connect_url = WOOSQU_PLUS_CONNECTURL;
					$redirect_url = add_query_arg(
						array(
							'app_name'    => WOOSQU_PLUS_APPNAME,
							'plug'    => WOOSQU_PLUS_PLUGIN_NAME,
						),
						admin_url( 'admin.php' )
					);

					$redirect_url = wp_nonce_url( $redirect_url, 'connect_wooplus', 'wc_wooplus_token_nonce' );
					$site_url = ( urlencode( $redirect_url ) );
					$args_renew = array(
						'body' => array(
							'header' => $headers,
							'action' => 'renew_token',
							'site_url'    => $site_url,
						),
						'timeout' => 45,
					);
					
					
					$oauth_response = wp_remote_post( $oauth_connect_url, $args_renew );

					$decoded_oauth_response = json_decode( wp_remote_retrieve_body( $oauth_response ) );
					
					if(!empty($decoded_oauth_response->access_token)){
						$woo_square_auth_response['expires_at'] = $decoded_oauth_response->expires_at;
						$woo_square_auth_response['access_token'] = $decoded_oauth_response->access_token;
						update_option('woo_square_auth_response',$woo_square_auth_response);
						update_option('woo_square_access_token',$woo_square_auth_response['access_token']);
						update_option('woo_square_access_token_cauth',$woo_square_auth_response['access_token']);
						
					}
					
				}
			}
			
			
	}
	
	
	public function wooplus_get_toptabs(){
		$tablist = '' ;
		$plugin_modules = get_option('activate_modules_woosquare_plus',true);
		if(!empty($plugin_modules['module_page'])){
			foreach($plugin_modules as $key => $value){
				if($value['module_activate']){
					if(!empty(get_option('woo_square_access_token_cauth')) and !empty(get_option('woo_square_location_id'))){
						$navactive = '';
						if($_GET['page'] == $value['module_menu_details']['menu_slug']){
							$navactive = 'active';
						}
						if(!empty($value['module_menu_details']['menu_slug']) and $value['module_menu_details']['menu_slug'] != 'square-modifiers'):
						$tablist .= '<li class="nav-item">
										<a class="nav-link '.$navactive.'" href="'.get_admin_url( ).'admin.php?page='.$value['module_menu_details']['menu_slug'].'" role="tab">
											<i class="'.$value['module_menu_details']['tab_html_class'].'" aria-hidden="true"></i> '.$value['module_menu_details']['menu_title'].'
										</a>
									</li>';
								endif;		
					}
				}
			}
		}
		/* if(!empty($plugin_modules['module_page'])){
			foreach($plugin_modules as $key => $value){
				if($value['module_activate']){
					if(!empty(get_option('woo_square_access_token_cauth')) and !empty(get_option('woo_square_location_id'))){
						add_submenu_page($value['module_menu_details']['parent_slug'],$value['module_menu_details']['page_title'], $value['module_menu_details']['menu_title'], $value['module_menu_details']['capability'], $value['module_menu_details']['menu_slug'], array(&$this,$value['module_menu_details']['function_callback']));
					}
				}
			}
			add_submenu_page('square-settings', "Documentation Plus", "Documentation", 'manage_options', 'square-documentation', array(&$this,'documentation_plugin_page'));
		} */
		
		return $tabs_html = '
						<ul class="nav nav-tabs" role="tablist">
							'.$tablist.'
						</ul>';
	}
	

}
