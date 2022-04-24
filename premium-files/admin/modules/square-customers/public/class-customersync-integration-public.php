<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://godardcreative.com/
 * @since      1.0.0
 *
 * @package    Customer_Sync_Integration
 * @subpackage Customer_Sync_Integration/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Customer_Sync_Integration
 * @subpackage Customer_Sync_Integration/public
 * @author     Godardcreative <service@daedalushouse.com>
 */
class Customer_Sync_Integration_Public {

	private $plugin_name;
	private $version;
	private $logger;

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
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/customersync-integration-public.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/customersync-integration-public.js', array( 'jquery' ), $this->version, false );
	}

    public function addCreditCardTabToMyAccount($items)
    {
        $items['squareCreditCard'] = 'Credit cards';
        return $items;
	}

    public function addCreditCardEndPoint()
    {
		
		flush_rewrite_rules();
        add_rewrite_endpoint( 'squareCreditCard', EP_PAGES );
    }

    public function addCreditCardEndPointContent()
    {
		
		$woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
		$location = get_option('woo_square_location_id');
		$token           = get_option( 'woo_square_access_token' );
		if($woocommerce_square_plus_settings['enable_sandbox'] == 'yes'){
			$endpoint = 'squareupsandbox';
			$token = $woocommerce_square_plus_settings['sandbox_access_token'];
			$location = $woocommerce_square_plus_settings['sandbox_location_id'];
		} else {
			$endpoint = 'squareup';
		}
		
        wp_register_script( 'square', 'https://js.'.$endpoint.'.com/v2/paymentform', '', '0.0.2', true );
        wp_register_script( 'woocommerce-square', plugin_dir_url( __FILE__ ) . 'js/securePayments.js', array( 'jquery', 'square' ));
        wp_localize_script( 'woocommerce-square', 'square_params', array(
            'application_id'               => WOOSQU_PLUS_APPID,
            'environment'                  =>  'production' ,
			'get_woocommerce_currency'	   =>  get_woocommerce_currency(),
            'location_id'                  => $location,
            'custom_form_trigger_element'  => apply_filters( 'woocommerce_square_payment_form_trigger_element', esc_js( '' ) ),
        ) );
        wp_enqueue_script( 'woocommerce-square' );

        do_action('checkForAddingCreditCards');


        $user = wp_get_current_user();
		$api_client = '';
		# setup authorization 
		$api_config = new \SquareConnect\Configuration();
		$api_config->setHost("https://connect.".$endpoint.".com");
		$api_config->setAccessToken($token);
		$api_client = new \SquareConnect\ApiClient($api_config);
        $api = new \SquareConnect\Api\CustomersApi($api_client);

        $squareId = get_user_meta($user->ID, '_square_customer_id', true);

        $e = $this->checkIfCreditCardNeedsToBeDeleted($api, $squareId);
		
							
        if($squareId) {
            try {
                $userSquare = $api->retrieveCustomer($squareId);
            } catch (\SquareConnect\ApiException $e) {
                if($e->getResponseBody()->errors[0]->code == 'NOT_FOUND') {
					$plugin = new Customer_Sync_Integration();
					$plugin_admin = new Customer_Sync_Integration_Admin( $plugin->get_plugin_name(), $plugin->get_version() );
                    $plugin_admin->createCustomerToSquare($user, $api);
                } else {
                    $this->displaySquareException($e);
                }
            }
        }

        include_once( 'partials/showCreditCards.php' );
	}
	
	
    private function displaySquareException(\SquareConnect\ApiException $e) {
        $errors = '';
        foreach ($e->getResponseBody()->errors as $error) {
            $errors .= $error->detail.'<br>';
        }
        echo '<div id="message" class="updated notice is-dismissible">'.$errors.'<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button ></div>';
    }

    /**
     * @param $api
     * @param $squareId
     * @return Exception|\SquareConnect\ApiException
     * @since 1.0.0
     */
    private function checkIfCreditCardNeedsToBeDeleted($api, $squareId)
    {
        if (isset($_GET['action']) && isset($_GET['cardId']) && $_GET['action'] == 'deleteCreditCard') {
            try {
                $response = $api->deleteCustomerCard($squareId, $_GET['cardId']);
            } catch (\SquareConnect\ApiException $e) {
            }

            echo '<p class="square-success"><strong>Credit card successfully deleted.</strong></p>';
        }
        return @$e;
    }

    /**
     * It adds credit card if needed
     *
     * @since 1.0.0
     */
    public function addCreditCard()
    {
        if(!isset($_POST['card-nonce']) || $_POST['card-nonce'] == '') {
            return;
        }
		
        $retrieved_nonce = $_REQUEST['_wpnonce'];
        if (!wp_verify_nonce($retrieved_nonce, 'squareAddCreditCard' ) ) die( 'Failed security check' );


		$woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
		$token           = get_option( 'woo_square_access_token' );
		if($woocommerce_square_plus_settings['enable_sandbox'] == 'yes'){
			$endpoint = 'squareupsandbox';
			$token = $woocommerce_square_plus_settings['sandbox_access_token'];
		} else {
			$endpoint = 'squareup';
		}
		$api_client = '';
		# setup authorization 
		$api_config = new \SquareConnect\Configuration();
		$api_config->setHost("https://connect.".$endpoint.".com");
		$api_config->setAccessToken($token);
		$api_client = new \SquareConnect\ApiClient($api_config);
		
        \SquareConnect\Configuration::getDefaultConfiguration()->setAccessToken($token);
        $api = new \SquareConnect\Api\CustomersApi($api_client);
        
        $user = wp_get_current_user();

        try {
            $response = $api->createCustomerCard($user->_square_customer_id, [
                'card_nonce' => $_POST['card-nonce'],
                'verification_token' => $_POST['buyerVerification_token'],
                'billing_address' => array(
                    'country' => ($user->billing_country) ? $user->billing_country : 'US',
                    'administrative_district_level_1' => $user->billing_state,
                    'postal_code' => $user->billing_postcode,
                    'locality' => $user->billing_city,
                    'address_line_2' => $user->billing_address_2,
                    'address_line_1' => $user->billing_address_1
                ),
                'cardholder_name' => $user->first_name .' '.$user->last_name
            ]);
            echo '<p class="square-success"><strong>Credit card is successfully added.</strong></p>';
        } catch (\SquareConnect\ApiException $e) {
            $errors = '';
            foreach ($e->getResponseBody()->errors as $error) {
                $errors .= $error->detail.'<br>';
            }
            echo '<p class="square-success"><strong>'.$errors.'</strong></p>';
        }
    }
}
