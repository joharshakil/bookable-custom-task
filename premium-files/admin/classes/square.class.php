<?php

class Square {

    //Class properties.
    protected $accessToken;
    protected $app_id;
    protected $squareURL;
    protected $locationId;
    protected $mainSquareURL;

    /**
     * Constructor
     *
     * @param object $accessToken
     *
     */
    public function __construct($accessToken, $locationId="me",$app_id) {
        $this->accessToken = $accessToken;
        $this->app_id = $app_id;
        if(empty($locationId)){ $locationId = 'me'; }
        $this->locationId = $locationId;
        $this->squareURL = "https://connect.squareup.com/v1/" . $this->locationId;
        $this->mainSquareURL = "https://connect.squareup.com/v1/me";
    }

    
    public function getAccessToken(){
        return $this->accessToken;
    }
    
    public function setAccessToken($access_token){
        $this->accessToken = $access_token;
    }
    
    public function getapp_id(){
        return $this->app_id;
    }
    
    public function setapp_id($app_id){
        $this->app_id = $app_id;
    }
    
    public function getSquareURL(){
        return $this->squareURL;
    }
    

    public function setLocationId($location_id){        
        $this->locationId = $location_id;
        $this->squareURL = "https://connect.squareup.com/v1/".$location_id;
    }
    
    public function getLocationId(){
        return $this->locationId;
    }
    
    /*
     * authoirize the connect to Square with the given token
     */

    public function authorize() {
		$accessToken = explode('-',$this->accessToken);
		
		delete_option('woo_square_account_type' ); 
		delete_option('woo_square_account_currency_code' ); 
		delete_option('wc_square_version', '1.0.11', 'yes');
		delete_option('woo_square_access_token');
		delete_option('woo_square_access_token');
		delete_option('woo_square_app_id');
		delete_option('woo_square_locations');
		delete_option('woo_square_business_name');
		delete_option('woo_square_location_id');
		
		if($accessToken[0] != 'sandbox'){
		
			$url = $this->mainSquareURL;
			$headers = array(
				'Authorization' => 'Bearer '.$this->accessToken, 
				'Content-Type'  => 'application/json'
			);
			$method = "GET";
			$response = array(); 
			$args = array(''); 
			$response = $this->wp_remote_woosquare($url,$args,$method,$headers,$response);
			$response_v_1 =  json_decode($response['body'], true);
			update_option('woo_square_account_type', @$response_v_1['account_type']);
			update_option('woo_square_account_currency_code', @$response_v_1['currency_code']);
				// live/production app id from Square account
				if (!defined('SQUARE_APPLICATION_ID')) define('SQUARE_APPLICATION_ID',$this->app_id );
				
			} else {
				// live/production app id from Square account
				if (!defined('SQUARE_APPLICATION_ID')) define('SQUARE_APPLICATION_ID',$this->app_id );
				
				update_option('woo_square_account_type', 'BUSINESS');
				update_option('woo_square_account_currency_code',get_option('woocommerce_currency'));
			}
		
			$url = "https://connect.squareup.com/v2/locations";
			$method = "GET";
			$headers = array(
				'Authorization' => 'Bearer '.$this->accessToken, // Use verbose mode in cURL to determine the format you want for this header
				'cache-control'  => 'no-cache',
				'postman-token'  => 'f39c2840-20f3-c3ba-554c-a1474cc80f12'
			);
			 
			$response = array();
			$args = array('');
	    	$response = $this->wp_remote_woosquare($url,$args,$method,$headers,$response);

			if($response['response']['code'] == 200 and $response['response']['message'] == 'OK'){
				$response =  json_decode($response['body'], true);
				$response = @$response['locations'][0];
			}  else {

			}

			if (isset($response['id'])) {
				update_option('wc_square_version', '1.0.11', 'yes');
				update_option('woo_square_access_token', $this->accessToken);
				update_option('woo_square_app_id', WOOSQU_PLUS_APPID);
				$result = $this->getAllLocations();
				if(!empty($result['locations']) and is_array($result['locations'])){
					
					foreach($result['locations'] as $key => $value){
						if(!empty($value['capabilities']) 
							and 
							$value['status'] == 'ACTIVE'
							and 
							$accessToken[0] == 'sandbox'
							){
							$accurate_result['locations'][] =  $result['locations'][$key];
						} elseif($accessToken[0] != 'sandbox'){
							$accurate_result['locations'][] =  $result['locations'][$key];
						}
					}
				}
				$results =  $accurate_result['locations'];
				$caps = null;
				if(!empty($results)){
					foreach($results as $result){
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
					update_option('woo_square_location_id', $location_id);
				}
				$this->setupWebhook("PAYMENT_UPDATED",$this->accessToken);
				return true;
			} else {
				return false;
			}
    }
    /*
     * get currency code by location id
     */
    public function getCurrencyCode(){
        
		$url = $this->squareURL;
		$headers = array(
			'Authorization' => 'Bearer '.$this->accessToken, 
			'Content-Type'  => 'application/json'
		);
		$method = "GET";
		$response = array();
		$args = array('');
		$response = $square->wp_remote_woosquare($url,$args,$method,$headers,$response);
		$response = json_decode($response['body'], true);
		if (isset($response['id'])) {
            update_option('woo_square_account_currency_code', $response['currency_code']);
        }
    }
    
    
    
    
    /*
     * get all locations if account type is business
     */

    public function getAllLocations() {
      		
	  $url = "https://connect.squareup.com/v2/locations";
	  $method = "GET";
	  $headers = array(
		'Authorization' => 'Bearer '.$this->accessToken, // Use verbose mode in cURL to determine the format you want for this header
		'cache-control'  => 'no-cache',
		'postman-token'  => 'f39c2840-20f3-c3ba-554c-a1474cc80f12'
	);
	$response = array();
	$args = array('');
	$response = $this->wp_remote_woosquare($url,$args,$method,$headers,$response);
	if($response['response']['code'] == 200 and $response['response']['message'] == 'OK'){
		Helpers::debug_log('info', "The response of authorize  request" . $response);
	} else {
		Helpers::debug_log('info', "cURL Error #:" . $response);
	}
	
		
	return json_decode($response['body'], true);	
		
    }

    /*
     * setup webhook with Square
     */

    public function setupWebhook($type,$accessToken) {
        // setup notifications
        $data = array($type);
		
        $data_json = json_encode($data);
	  
		$url = $this->squareURL . "/webhooks";
		$method = "POST";
		$headers = array(
			'Authorization' => 'Bearer '.$accessToken, // Use verbose mode in cURL to determine the format you want for this header
			'Content-Length'  => strlen($data_json),
			'Content-Type'  => 'application/json'
		);
		$response = array();
		$response = $this->wp_remote_woosquare($url,$card_details,$method,$headers,$response);
		$response =  json_decode($response['body'], true);
		if($response['response']['code'] == 200 and $response['response']['message'] == 'OK'){
			update_option('Woosquare_webhook_response',json_encode($response).' : '.get_option('woo_square_location_id'));
		} else {
			update_option('Woosquare_webhook_response_error',json_encode($response).' : '.get_option('woo_square_location_id'));
		}
	

		
		
        return true;
    }

 
    /*
     * Update Square inventory based on this order 
     */

    public function completeOrder($order_id) {
       
        
        $order = new WC_Order($order_id);
        $items = $order->get_items();
 
        if ($order->get_created_via() == "Square")
            return;
 
        foreach ($items as $item) {
            if ($item['variation_id']) {
                if (get_post_meta($item['variation_id'], '_manage_stock', true) == 'yes') {
                    $product_variation_id = get_post_meta($item['variation_id'], 'variation_square_id', true);
                    $this->updateInventory($product_variation_id, -1 * $item['qty'], 'SALE');
                }
            } else {
                if (get_post_meta($item['product_id'], '_manage_stock', true) == 'yes') {
                    $product_variation_id = get_post_meta($item['product_id'], 'variation_square_id', true);
                    $this->updateInventory($product_variation_id, -1 * $item['qty'], 'SALE');
                }
            }
        }
    }

    

    /*
     * create a refund to Square
     */

     /*
     * create a refund to Square
     */
 
    public function refund($order_id, $refund_id) {
       
        $order = new WC_Order($order_id);
        $items = $order->get_items();
        foreach ($items as $item) {
            if ($item['variation_id']) {
                if (get_post_meta($item['variation_id'], '_manage_stock', true) == 'yes') {
                    $product_variation_id = get_post_meta($item['variation_id'], 'variation_square_id', true);
                    $this->updateInventory($product_variation_id, 1 * $item['qty'], 'RECEIVE_STOCK');
                }
            } else {
                if (get_post_meta($item['product_id'], '_manage_stock', true) == 'yes') {
                    $product_variation_id = get_post_meta($item['product_id'], 'variation_square_id', true);
                    $this->updateInventory($product_variation_id, 1 * $item['qty'], 'RECEIVE_STOCK');
                }
            }
        }
		/* 
		$woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
		$token           = get_option( 'woo_square_access_token' );
		if(@$woocommerce_square_plus_settings['enable_sandbox'] == 'yes'){
			$token = $woocommerce_square_plus_settings['sandbox_access_token'];
		}
		
		$fields = array(
			"idempotency_key" => uniqid(),
			"type" => "PARTIAL",
			"payment_id" => get_post_meta($order_id, 'woosquare_transaction_id', true),
			"reason" => "Returned Goods",
			"amount_money" => array(
				  "amount" => (get_post_meta($refund_id, '_refund_amount', true) * 100 ),
				  "currency" => get_post_meta($order_id, '_order_currency', true),
				),
		);
		
		$url = "https://connect.".WC_SQUARE_STAGING_URL.".com/v2/refunds";
		$headers = array(
			'Accept' => 'application/json',
			'Authorization' => 'Bearer '.$token,
			'Content-Type' => 'application/json',
			'Cache-Control' => 'no-cache'
		);
		
		$refund_obj = json_decode(wp_remote_retrieve_body(wp_remote_post($url, array(
				'method' => 'POST',
				'headers' => $headers, 
				'httpversion' => '1.0',
				'sslverify' => false,
				'body' => json_encode($fields)
				)
			)
		)
		);
		if('APPROVED' === $refund_obj->refund->status || 'PENDING' === $refund_obj->refund->status ){
			$refund_message = sprintf( __( 'Refunded %s - Refund ID: %s ', 'wpexpert-square' ), wc_price( $refund_obj->refund->amount_money->amount / 100 ), $refund_obj->refund->id);
			update_post_meta($order_id, "refund_created_at", $refund_obj->refund->created_at);
			update_post_meta($order_id, "refund_created_id", $refund_obj->refund->id);
			$order->add_order_note( $refund_message );
		} */
		
		
    }

   
	/*
	* Update Inventory with stock amount
	*/

	public function updateInventory($variation_id, $stock, $adjustment_type = "RECEIVE_STOCK") {
		$data_string = array(
			'quantity_delta' => $stock,
			'adjustment_type' => $adjustment_type
		);
		
		$url =  $this->getSquareURL() . '/inventory/' . $variation_id;
		$headers = array(
			'Authorization' => 'Bearer '.$this->accessToken, // Use verbose mode in cURL to determine the format you want for this header
			'Content-Length'  => strlen(json_encode($data_string)),
			'Content-Type'  => 'application/json'
		);
		$method = "POST";
		$args = ($data_string);
		$response = $this->wp_remote_woosquare($url,$args,$method,$headers);
		Helpers::debug_log('info', "The response of adding new category curl request " . json_encode($response));
		if($response['response']['code'] == 200 and $response['response']['message'] == 'OK'){
			$result = json_decode($response['body'], true);
			
			return ($response['response']['code']==200)?true:$result;
		}
    
}
