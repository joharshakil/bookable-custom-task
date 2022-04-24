<?php
require  plugin_dir_path( dirname( __FILE__ ) ) .'vendor/autoload.php';

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://godardcreative.com/
 * @since      1.0.0
 *
 * @package    Customer_Sync_Integration
 * @subpackage Customer_Sync_Integration/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Customer_Sync_Integration
 * @subpackage Customer_Sync_Integration/admin
 * @author     Godardcreative <service@daedalushouse.com>
 */
class Customer_Sync_Integration_Admin {

	private $plugin_name;
	private $version;
	private $logger;

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
		$this->logger = new \Katzgrau\KLogger\Logger(plugin_dir_path( dirname( __FILE__ ) ) .'logs', Psr\Log\LogLevel::DEBUG, ['filename' => 'log.txt']);
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/customersync-integration-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/customer-sync-integration-admin.js', array( 'jquery' ), $this->version, false );
	}

    /**
     * It updates wine club name in Square group
     *
     * @param $wineClubId
     * @param $oldName
     * @param $newWineClubName
     * @since    1.0.0
     */
    public function wineClubNameChanged($wineClubId, $oldName, $newWineClubName)
    {
        if($oldName == $newWineClubName) {
            return;
        }

        $users = get_users([
            'meta_key' => 'wineClubMembershipLevel',
            'meta_value' => $wineClubId
        ]);

        \SquareConnect\Configuration::getDefaultConfiguration()->setAccessToken(get_option('woo_square_access_token'));
        $api = new \SquareConnect\Api\CustomersApi();

        if($newWineClubName == null || $newWineClubName == 'null') {
            $newWineClubName = '';
        }

        $this->logger->info('Wine club ID: '. $wineClubId .' Changed name from: '. $oldName. ' to '. $newWineClubName);

        foreach ($users as $user) {
            try {
                $api->updateCustomer($user->_square_customer_id, [
                    'reference_id' => $newWineClubName
                ]);
                $this->logger->info('User: '. $user->first_name .' '. $user->last_name .' '. $user->user_email .' Square ID: '. $user->_square_customer_id .' - Wine club changed from: '. $oldName .' to '. $newWineClubName);
            } catch (\SquareConnect\ApiException $e) {
                $this->logException($e);
            }
        }
    }

    /**
     * It updates this user wine club in Square group
     *
     * @param $userId
     * @param $oldName
     * @param $newWineClubName
     * @since    1.0.0
     */
    public function updateSquareContactWineClub($userId, $oldName, $newWineClubName)
    {
        if($oldName == $newWineClubName) {
            return;
        }

        $user = get_userdata($userId);
        if($newWineClubName == null || $newWineClubName == 'null') {
            $newWineClubName = '';
        }

        try {
            \SquareConnect\Configuration::getDefaultConfiguration()->setAccessToken(get_option('woo_square_access_token'));
            $api = new \SquareConnect\Api\CustomersApi();
            $api->updateCustomer($user->_square_customer_id, [
                'reference_id' => $newWineClubName
            ]);
            $this->logger->info('User: '. $user->first_name .' '. $user->last_name .' '. $user->user_email .' Square ID: '. $user->_square_customer_id .' - Wine club changed from: '. $oldName .' to '. $newWineClubName);
        } catch (\SquareConnect\ApiException $e) {
            $this->logException($e);
        }

	}

    /**
     * It syncs customer data to square
     *
     * @param $userId
     *
     * @since 1.0.0
     */
    public function registerUserToSquare($userId)
    {
        // If Customer is registered thought checkout form
        // This is done by Woosquare - pro and
        // If you start using this if its not passing so check that :)
        
        if (!get_user_meta($userId, '_square_customer_id', true)) {
            $userInfo = get_userdata($userId);
            \SquareConnect\Configuration::getDefaultConfiguration()->setAccessToken(get_option('woo_square_access_token'));
            $api = new \SquareConnect\Api\CustomersApi();
			
            try {
                $response = $api->createCustomer([
                    'given_name' => $userInfo->first_name,
                    'family_name' => $userInfo->last_name,
                    'email_address' => $userInfo->user_email,
                    'address' => [
                        'address_line_1' => $_POST['billing_address_1'],
                        'address_line_2' => $_POST['billing_address_2'],
                        'locality' => $_POST['billing_city'],
                        'postal_code' => $_POST['billing_postcode'],
                        'country' => $_POST['billing_country'],
                        'administrative_district_level_1' => $_POST['billing_state']
                    ],
                    'phone_number' => null,
                    'note' => 'E-commerce customer',
                ]);
					
                update_user_meta($userId, '_square_customer_id', $response['customer']['id']);

		
            } catch (\SquareConnect\ApiException $e) {
                $this->displaySquareException($e);
                $this->logException($e);
            }
        }
    }

    /**
     * It syncs all customers to square
     *
     * @since 1.0.0
     */
    public function syncAllCustomerToSquare()
    {
		// update_option('running_syncAllCustomerToSquare_'.date("Y-m-d H:i:s"),'test');
        foreach(get_users(array('role' => 'customer')) as $user) {
            $this->syncCustomerDataToSquare($user);
        }
    }

    /**
     * It syncs customer data to square
     *
     * @param $user
     *
     * @since 1.0.0
     * @throws \SquareConnect\ApiException
     */
    public function syncCustomerDataToSquare($user)
    {
		
		
		
        if(is_numeric($user)) {
            $user = get_userdata($user);
        }
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
        $api = new \SquareConnect\Api\CustomersApi($api_client);

        $squareId = get_user_meta($user->ID, '_square_customer_id', true);

        $this->checkIfCreditCardNeedsToBeDeleted($api, $squareId);

		
        if($squareId) {
            try {
                $userSquare = $api->retrieveCustomer($squareId);
				
                if($this->ifWordpressDataChanged($user, $userSquare['customer']))
                {
                    $data = $this->getSquareUserData($user);
                    $this->logger->info('[Woocommerce -> Square update] User: '. $user->first_name.' '. $user->last_name. ' '. $user->user_email. ' Square ID: '. $squareId .' is being updated', $data);
                    $api->updateCustomer($squareId, json_encode($data));
                    $this->logger->info('[Woocommerce -> Square update] User: '. $user->first_name.' '. $user->last_name. ' '. $user->user_email. ' Square ID: '. $squareId . 'is updated');
                }
            } catch (\SquareConnect\ApiException $e) {
                if($e->getResponseBody()->errors[0]->code == 'NOT_FOUND') {
                    $this->createCustomerToSquare($user, $api);
                } else {
                    $this->displaySquareException($e);
                    $this->logException($e);
                }
            }
        } else {
            $this->createCustomerToSquare($user, $api);
        }
		
        include_once( 'partials/showCreditCards.php' );
    }

    /**
     * It syncs customer data from square
     *
     * @since 1.0.0
     */
    public function syncCustomerDataFromSquare()
    {
        $this->logger->info('Cron sync square -> woocommerce is triggered');

        \SquareConnect\Configuration::getDefaultConfiguration()->setAccessToken(get_option('woo_square_access_token'));
        $api = new \SquareConnect\Api\CustomersApi();
        try {
            foreach ($api->listCustomers()['customers'] as $customer) {
                if(email_exists($customer['email_address'])) {
                    $this->checkForCustomerDataChange($customer);
                } else {
                    $this->createNewCustomer($customer);
                }
            }
        } catch (\SquareConnect\ApiException $e) {
            $this->displaySquareException($e);
            $this->logException($e);
        }
    }

    /**
     * It adds scripts for adding credit card in user profile
     *
     * @since 1.0.0
     */
    public function paymentScripts()
    {
		$woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
		$location = get_option('woo_square_location_id');
		if($woocommerce_square_plus_settings['enable_sandbox'] == 'yes'){
			$endpoint = 'squareupsandbox';
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
    }

    /**
     * It adds credit card if needed
     *
     * @since 1.0.0
     */
    public function paymentScriptsUpdate($userId)
    {
	
        if(isset($_POST['card-nonce']) && $_POST['card-nonce'] != '') {
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
			$api = new \SquareConnect\Api\CustomersApi($api_client);
            $user = get_userdata($userId);
			if($user->_square_customer_id) {
				try {
					$userSquare = $api->retrieveCustomer($user->_square_customer_id);
					if($this->ifWordpressDataChanged($user, $userSquare['customer']))
					{
						$data = $this->getSquareUserData($user);
						$this->logger->info('[Woocommerce -> Square update] User: '. $user->first_name.' '. $user->last_name. ' '. $user->user_email. ' Square ID: '. $user->_square_customer_id .' is being updated', $data);
						$api->updateCustomer($user->_square_customer_id, json_encode($data));
						$this->logger->info('[Woocommerce -> Square update] User: '. $user->first_name.' '. $user->last_name. ' '. $user->user_email. ' Square ID: '. $user->_square_customer_id . 'is updated');
					}
				} catch (\SquareConnect\ApiException $e) {
					if($e->getResponseBody()->errors[0]->code == 'NOT_FOUND') {
						$this->createCustomerToSquare($user, $api);
					} else {
						$this->displaySquareException($e);
						$this->logException($e);
					}
				}
			} else {
				$this->createCustomerToSquare($user, $api);
				$user = get_userdata($userId);
			}
			
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
				
                $this->logger->info('User: '. $user->first_name .' '. $user->last_name .' '. $user->user_email .' Square ID: '. $user->_square_customer_id .' Has added new credit card', (array) $response);
            } catch (\SquareConnect\ApiException $e) {
                $this->displaySquareException($e);
                $errors = '';
				
                foreach ($e->getResponseBody()->errors as $error) {
                    $errors .= $error->detail.'<br>';
                }
                echo '<div id="message" class="updated notice is-dismissible">'.$errors.'<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button ></div>';
                $this->logException($e);
                die();
            }
        }
    }


    private function displaySquareException(\SquareConnect\ApiException $e) {
        $errors = '';
        foreach ($e->getResponseBody()->errors as $error) {
            $errors .= $error->detail.'<br>';
        }
        echo '<div id="message" class="updated notice is-dismissible">'.$errors.'<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button ></div>';
    }

    /**
     * @param \SquareConnect\ApiException $e
     *
     * @since 1.0.0
     */
    private function logException(\SquareConnect\ApiException $e) {
        $this->logger->error("Caught exception!");
        $this->logger->debug('Response body:',(array) $e->getResponseBody());
        $this->logger->debug('Response headers:',(array) $e->getResponseHeaders());
    }

    /**
     * Update customer if data changed
     *
     * @param $customer
     *
     * @since 1.0.0
     */
    private function checkForCustomerDataChange($customer)
    {
        $user = get_user_by_email($customer['email_address']);
        if($user->_square_customer_id != $customer['id']) {
         update_user_meta( $user->ID, '_square_customer_id', $customer['id']);
        }
        if($user->billing_email != $customer['email_address']) {
            $this->logger->info('[Square -> Woocmmerce update] User ID:'. $user->ID .' Changed email from: '. $user->billing_email. ' to '. $customer['email_address']);
            update_user_meta( $user->ID, 'billing_email', $customer['email_address']);
        }
        if($user->billing_phone != $customer['phone_number']) {
            $this->logger->info('[Square -> Woocmmerce update] User ID:'. $user->ID .' Changed phone from: '. $user->billing_phone. ' to '. $customer['phone_number']);
            update_user_meta( $user->ID, 'billing_phone', $customer['phone_number']);
        }
        if($user->billing_address_1 != $customer['address']['address_line_1']) {
            $this->logger->info('[Square -> Woocmmerce update] User ID:'. $user->ID .' Changed address 1 from: '. $user->billing_address_1. ' to '. $customer['address']['address_line_1']);
            update_user_meta( $user->ID, 'billing_address_1', $customer['address']['address_line_1']);
        }
        if($user->billing_address_2 != $customer['address']['address_line_2']) {
            $this->logger->info('[Square -> Woocmmerce update] User ID:'. $user->ID .' Changed address 2 from: '. $user->billing_address_2. ' to '. $customer['address']['address_line_2']);
            update_user_meta( $user->ID, 'billing_address_2', $customer['address']['address_line_2']);
        }
        if($user->billing_city != $customer['address']['locality']) {
            $this->logger->info('[Square -> Woocmmerce update] User ID:'. $user->ID .' Changed city from: '. $user->billing_city. ' to '. $customer['address']['locality']);
            update_user_meta( $user->ID, 'billing_city', $customer['address']['locality']);
        }
        if($user->billing_postcode != $customer['address']['postal_code']) {
            $this->logger->info('[Square -> Woocmmerce update] User ID:'. $user->ID .' Changed postal code from: '. $user->billing_postcode. ' to '. $customer['address']['postal_code']);
            update_user_meta( $user->ID, 'billing_postcode', $customer['address']['postal_code']);
        }
        if($user->billing_country != $customer['address']['country']) {
            $this->logger->info('[Square -> Woocmmerce update] User ID:'. $user->ID .' Changed county from: '. $user->billing_country. ' to '. $customer['address']['country']);
            update_user_meta( $user->ID, 'billing_country', $customer['address']['country']);
        }
        if($user->billing_state != $customer['address']['administrative_district_level_1']) {
            $this->logger->info('[Square -> Woocmmerce update] User ID:'. $user->ID .' Changed state from: '. $user->billing_state. ' to '. $customer['address']['administrative_district_level_1']);
            update_user_meta( $user->ID, 'billing_state', $customer['address']['administrative_district_level_1']);
        }
        if($user->billing_company != $customer['company_name']) {
            $this->logger->info('[Square -> Woocmmerce update] User ID:'. $user->ID .' Changed company from: '. $user->billing_company. ' to '. $customer['address']['company_name']);
            update_user_meta( $user->ID, 'billing_company', $customer['company_name']);
        }
        if($user->billing_last_name != $customer['family_name']) {
            $this->logger->info('[Square -> Woocmmerce update] User ID:'. $user->ID .' Changed last name from: '. $user->billing_last_name. ' to '. $customer['family_name']);
            update_user_meta( $user->ID, 'billing_last_name', $customer['family_name']);
        }
        if($user->billing_first_name != $customer['given_name']) {
            $this->logger->info('[Square -> Woocmmerce update] User ID:'. $user->ID .' Changed first name from: '. $user->billing_first_name. ' to '. $customer['given_name']);
            update_user_meta( $user->ID, 'billing_first_name', $customer['given_name']);
        }
        if($user->last_name != $customer['family_name']) {
             update_user_meta( $user->ID, 'last_name', $customer['family_name']);
        }
        if($user->first_name != $customer['given_name']) {
             update_user_meta( $user->ID, 'first_name', $customer['given_name']);
        }

        if($user->shipping_last_name != $customer['family_name']) {
            update_user_meta( $user->ID, 'shipping_last_name', $customer['family_name']);
        }
        if($user->shipping_first_name != $customer['given_name']) {
            update_user_meta( $user->ID, 'shipping_first_name', $customer['given_name']);
        }
        if($user->shipping_company != $customer['company_name']) {
            update_user_meta( $user->ID, 'shipping_company', $customer['company_name']);
        }
        if($user->shipping_address_1 != $customer['address']['address_line_1']) {
            update_user_meta( $user->ID, 'shipping_address_1', $customer['address']['address_line_1']);
        }
        if($user->shipping_address_2 != $customer['address']['address_line_2']) {
            update_user_meta( $user->ID, 'shipping_address_2', $customer['address']['address_line_2']);
        }
        if($user->shipping_city != $customer['address']['locality']) {
            update_user_meta( $user->ID, 'shipping_city', $customer['address']['locality']);
        }
        if($user->shipping_postcode != $customer['address']['postal_code']) {
            update_user_meta( $user->ID, 'shipping_postcode', $customer['address']['postal_code']);
        }
        if($user->shipping_country != $customer['address']['country']) {
            update_user_meta( $user->ID, 'shipping_country', $customer['address']['country']);
        }
        if($user->shipping_state != $customer['address']['administrative_district_level_1']) {
            update_user_meta( $user->ID, 'shipping_state', $customer['address']['administrative_district_level_1']);
        }

    }

    /**
     * Create new customer
     *
     * @param $customer
     *
     * @since 1.0.0
     */
    private function createNewCustomer($customer)
    {
        $user_id = wp_create_user( $customer['email_address'], wp_generate_password( $length=12, $include_standard_special_chars=false ), $customer['email_address']);
		$user = new WP_User($user_id);
		$user->set_role('customer');
        update_user_meta( $user_id, '_square_customer_id', $customer['id']);
        update_user_meta( $user_id, 'shipping_last_name', $customer['family_name']);
        update_user_meta( $user_id, 'shipping_first_name', $customer['given_name']);
        update_user_meta( $user_id, 'billing_email', $customer['email_address']);
        update_user_meta( $user_id, 'billing_phone', $customer['phone_number']);
        update_user_meta( $user_id, 'billing_address_1', $customer['address']['address_line_1']);
        update_user_meta( $user_id, 'billing_address_2', $customer['address']['address_line_2']);
        update_user_meta( $user_id, 'billing_city', $customer['address']['locality']);
        update_user_meta( $user_id, 'billing_postcode', $customer['address']['postal_code']);
        update_user_meta( $user_id, 'billing_country', $customer['address']['country']);
        update_user_meta( $user_id, 'billing_state', $customer['address']['administrative_district_level_1']);
        update_user_meta( $user_id, 'billing_company', $customer['company_name']);
        update_user_meta( $user_id, 'billing_last_name', $customer['family_name']);
        update_user_meta( $user_id, 'billing_first_name', $customer['given_name']);
        update_user_meta( $user_id, 'last_name', $customer['family_name']);
        update_user_meta( $user_id, 'first_name', $customer['given_name']);
        update_user_meta( $user_id, 'shipping_company', $customer['company_name']);
        update_user_meta( $user_id, 'shipping_address_1', $customer['address']['address_line_1']);
        update_user_meta( $user_id, 'shipping_address_2', $customer['address']['address_line_2']);
        update_user_meta( $user_id, 'shipping_city', $customer['address']['locality']);
        update_user_meta( $user_id, 'shipping_postcode', $customer['address']['postal_code']);
        update_user_meta( $user_id, 'shipping_country', $customer['address']['country']);
        update_user_meta( $user_id, 'shipping_state', $customer['address']['administrative_district_level_1']);

        $this->logger->info('User is created to wordpress from square', (array) $customer);
    }

    /**
     * Convert user to square data array
     *
     * @param $user
     * @return array
     * @since 1.0.0
     */
    private function getSquareUserData($user)
    {
        $data = [
            'given_name' => ($user->first_name) ? $user->first_name : '',
            'family_name' => ($user->last_name) ? $user->last_name : '',
            'email_address' => ($user->user_email) ? $user->user_email : '',
            'company_name' => ($user->billing_company) ? $user->billing_company : '',
            'address' => [
                'country' => ($user->billing_country) ? $user->billing_country : 'US',
                'administrative_district_level_1' => ($user->billing_state) ? $user->billing_state : '',
                'postal_code' => ($user->billing_postcode) ? $user->billing_postcode : '',
                'locality' => ($user->billing_city) ? $user->billing_city : '',
                'address_line_2' => ($user->billing_address_2) ? $user->billing_address_2 : '',
                'address_line_1' => ($user->billing_address_1) ? $user->billing_address_1 : ''
            ]
        ];

        if($user->billing_phone) {
            $data['phone_number'] = $user->billing_phone;
        }

        return $data;
    }

    /**
     * Checks if user data changed
     *
     * @param $user
     * @param $userSquare
     * @return bool
     * @since 1.0.0
     */
    private function ifWordpressDataChanged($user, $userSquare)
    {
        return $userSquare['phone_number'] != $user->billing_phone ||
            $userSquare['email_address'] != $user->user_email ||
            $userSquare['family_name'] != $user->last_name ||
            $userSquare['given_name'] != $user->first_name ||
            $userSquare['company_name'] != $user->billing_company ||
            $userSquare['address']['administrative_district_level_1'] != $user->billing_state ||
            $userSquare['address']['country'] != $user->billing_country ||
            $userSquare['address']['postal_code'] != $user->billing_postcode ||
            $userSquare['address']['locality'] != $user->billing_city ||
            $userSquare['address']['address_line_2'] != $user->billing_address_2 ||
            $userSquare['address']['address_line_1'] != $user->billing_address_1;
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

            echo '<div id="message" class="updated notice is-dismissible"> <p><strong>Credit card successfully deleted.</strong></p> <button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button ></div>';
        }
    }

    /**
     * @param $user
     * @param $api
     *
     * @since 1.0.0
     */
    public function createCustomerToSquare($user, $api)
    {
        try {
            $data = $this->getSquareUserData($user);
            $this->logger->info('User: ' . $user->first_name . ' ' . $user->last_name . ' ' . $user->user_email . ' is being created to square', $data);
            $userSquare = $api->createCustomer($data);
            $this->logger->info('User: ' . $user->first_name . ' ' . $user->last_name . ' ' . $user->user_email . ' is created to square with square id:' . $userSquare['customer']['id']);
            update_user_meta($user->ID, '_square_customer_id', $userSquare['customer']['id']);
        } catch (\SquareConnect\ApiException $e) {
            $this->displaySquareException($e);
            $this->logException($e);
        }
    }
}
