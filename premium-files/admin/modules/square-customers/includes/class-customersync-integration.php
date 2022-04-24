<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://godardcreative.com/
 * @since      1.0.0
 *
 * @package    Customer_Sync_Integration
 * @subpackage Customer_Sync_Integration/includes
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
 * @package    Customer_Sync_Integration
 * @subpackage Customer_Sync_Integration/includes
 * @author     Godardcreative <service@daedalushouse.com>
 */
class Customer_Sync_Integration {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Customer_Sync_Integration_Loader    $loader    Maintains and registers all hooks for the plugin.
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
		if ( defined( 'PLUGIN_NAME_VERSION' ) ) {
			$this->version = PLUGIN_NAME_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'customersync-integration';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Customer_Sync_Integration_Loader. Orchestrates the hooks of the plugin.
	 * - Customer_Sync_Integration_i18n. Defines internationalization functionality.
	 * - Customer_Sync_Integration_Admin. Defines all hooks for the admin area.
	 * - Customer_Sync_Integration_Public. Defines all hooks for the public side of the site.
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
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-customersync-integration-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-customersync-integration-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-customersync-integration-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-customersync-integration-public.php';

		$this->loader = new Customer_Sync_Integration_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Customer_Sync_Integration_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Customer_Sync_Integration_i18n();

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

		$plugin_admin = new Customer_Sync_Integration_Admin( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
        // Check for notifications on profile update
		if(get_option('woo_square_customer_merging_option') == "1" and get_option('sync_on_customer_add_edit') == "1"){
			// Woo commerce customer Override square customer
			$this->loader->add_action( 'user_register', $plugin_admin, 'registerUserToSquare', 999);
			$this->loader->add_action('woocommerce_save_account_details', $plugin_admin, 'syncCustomerDataToSquare', 10, 1 );
			$this->loader->add_action('woocommerce_customer_save_address', $plugin_admin, 'syncCustomerDataToSquare', 10, 1 );
		}
		//below script for add and view credit card from admin edit user screen..
		$this->loader->add_action('edit_user_profile', $plugin_admin, 'syncCustomerDataToSquare', 10, 1 );
        $this->loader->add_action('edit_user_profile', $plugin_admin, 'paymentScripts');
        $this->loader->add_action('edit_user_profile_update', $plugin_admin, 'paymentScriptsUpdate');
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		$activate_modules_woosquare_plus = get_option('activate_modules_woosquare_plus',true);
		
		
		if(
		@$activate_modules_woosquare_plus['customer_sync']['module_activate']
			OR
		$activate_modules_woosquare_plus['woosquare_card_on_file']['module_activate']
		){
			$plugin_public = new Customer_Sync_Integration_Public( $this->get_plugin_name(), $this->get_version() );

			$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
			$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
			$this->loader->add_action( 'init', $plugin_public, 'addCreditCardEndPoint' );
			
			if(get_option('cust_add_myaccount',true) == 1){
				$this->loader->add_filter( 'woocommerce_account_menu_items', $plugin_public, 'addCreditCardTabToMyAccount', 9, 1);
				$this->loader->add_action( 'checkForAddingCreditCards', $plugin_public, 'addCreditCard' ); 
				$this->loader->add_action( 'woocommerce_account_squareCreditCard_endpoint', $plugin_public, 'addCreditCardEndPointContent' );
			}
		}
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
	 * @return    Customer_Sync_Integration_Loader    Orchestrates the hooks of the plugin.
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

}
