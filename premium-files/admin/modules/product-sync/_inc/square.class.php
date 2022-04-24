<?php

class Square {

    //Class properties.
    protected $accessToken;
    protected $app_id;
    protected $squareURL;
	protected  $squareV2URL;
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
		$this->squareV2URL = "https://connect.squareup.com/v2/";
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

	public function getSquareV2URL(){
		return $this->squareV2URL;
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
		delete_option('woo_square_app_id');
		delete_option('woo_square_locations');
		delete_option('woo_square_business_name');
		
		$woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
		if(!empty($woocommerce_square_plus_settings['enable_sandbox'])  && $woocommerce_square_plus_settings['enable_sandbox'] != 'yes' ){
			
			// live/production app id from Square account
		if (!defined('SQUARE_APPLICATION_ID')) define('SQUARE_APPLICATION_ID',$this->app_id );
			if (!defined('WC_SQUARE_ENABLE_STAGING')) define('WC_SQUARE_ENABLE_STAGING',false );
		} else {
			// live/production app id from Square account
			if (!defined('SQUARE_APPLICATION_ID')) define('SQUARE_APPLICATION_ID',$this->app_id );
			if (!defined('WC_SQUARE_ENABLE_STAGING')) define('WC_SQUARE_ENABLE_STAGING',true );
			update_option('woo_square_account_type', 'BUSINESS');
			update_option('woo_square_account_currency_code',get_option('woocommerce_currency'));
		}  
		
		$url = "https://connect.squareup.com/v2/locations";
		$headers = array(
			'Authorization' => 'Bearer '.$this->accessToken, // Use verbose mode in cURL to determine the format you want for this header
			'cache-control'  => 'no-cache',
			'postman-token'  => 'f39c2840-20f3-c3ba-554c-a1474cc80f12'
		);
		$method = "GET";
			
		$response = array();
		$args = array('');
		$response = $this->wp_remote_woosquare($url,$args,$method,$headers,$response);
		if($response['response']['code'] == 200 and $response['response']['message'] == 'OK'){
			$response = json_decode($response['body'], true);
			$response = @$response['locations'][0];
		}
			if (isset($response['id'])) {
				update_option('wc_square_version', '1.0.11', 'yes');
				update_option('woo_square_access_token', $this->accessToken);
				update_option('woo_square_app_id', WOOSQU_PLUS_APPID);
				update_option('woo_square_account_type', @$response['type']);
				update_option('woo_square_account_currency_code', @$response['currency']);
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
					
				}
				
				return true;
			} else {
				return false;
			}
    }
	
	
	public function wp_remote_woosquare($url,$args,$method,$headers,$respons_body){
		
		$request = array(
			'headers' => $headers,
			'method'  => $method,
		);
		if ( $method == 'GET' && ! empty( $args ) && is_array( $args ) ) {
			$url = add_query_arg( $args, $url );
		} else {
		    if(!empty($args)){
		        $request['body'] = json_encode( $args );
	        }
		}
		$response = wp_remote_request( $url, $request );
		
		$decoded_response = json_decode( wp_remote_retrieve_body( $response ) );
			
		if(!empty(json_decode( wp_remote_retrieve_body( $response ) )->cursor)){
		  
			$respons_body[] = json_encode(json_decode(wp_remote_retrieve_body(  $response ))->objects);
			
		} else if(!empty(json_decode( wp_remote_retrieve_body( $response ) )->objects)) {
		  
			$respons_body[] = json_encode(json_decode(wp_remote_retrieve_body(  $response ))->objects);
	
		} elseif(!empty(wp_remote_retrieve_body( $response ))) {
		
		  $respons_body[] = wp_remote_retrieve_body( $response );      
	  
		}
		        
		if ( $method == 'GET' ){
			 $postheaders = '';
			$wp_remote_retrieve_header = wp_remote_retrieve_headers( $response );
			foreach($wp_remote_retrieve_header as $w_header){
				$postheaders .= esc_html($w_header);
			}
			
		
    		if(!empty(json_decode( wp_remote_retrieve_body( $response ) )->cursor)){
			    
				$args = array(
					'cursor' => json_decode( wp_remote_retrieve_body( $response ) )->cursor,
				);
				
				if(!empty($args)){
				    $response = $this->wp_remote_woosquare($url,$args,$method,$headers,$respons_body);

				}
				    		
			} else if( false !== strpos( $postheaders, 'batch_token' ) ){
				$batch_token = explode('batch_token',$postheaders);
				$batch_token = explode('>',$batch_token[1]);
				$batch_token = str_replace('=','',$batch_token[0]);
				$args = array(
					'batch_token' => $batch_token,
				);
				
				if(!empty($batch_token)){
					$response = $this->wp_remote_woosquare($url,$args,$method,$headers,$respons_body);
				}
			} else {
	             	$merge = array();
	             	
			    	foreach($respons_body as $formerge){
					if(!empty($merge)){
						$merge = array_merge(json_decode($formerge),$merge);
					} else {
						$merge = json_decode(($formerge));
					}		
				}
				
				if ( !is_wp_error( $response ) ) {
				    	$response['body'] = json_encode($merge);
				    
			        	return $response;
			    } else {
        			update_option('wp_remote_woosquare_get_error_message_'.date("Y-m-d H:i:s"),$response->get_error_message());
        			return false;
		        }  
				
			}
		}
		if ( !is_wp_error( $response ) ) {
		    	return $response;
	    } else {
			update_option('wp_remote_woosquare_get_error_message_'.date("Y-m-d H:i:s"),$response->get_error_message());
			return false;
        }
	}
    
    /*
     * get currency code by location id
     */
    public function getCurrencyCode(){
		
	
		$url = "https://connect.squareup.com/v2/locations/".$this->locationId;
		$method = "GET";
		$headers = array(
			'Authorization' => 'Bearer '.$this->accessToken, 
			'Content-Type'  => 'application/json'
		);
		$response = array();
		$args = array('');
		$response = $this->wp_remote_woosquare($url,$args,$method,$headers,$response);
		$response = json_decode($response['body'], true);
        if (isset($response['location']['id'])) {
            update_option('woo_square_account_currency_code', $response['location']['currency']);
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
		$response = json_decode($response['body'], true);

		return $response;
    }

    /*
     * setup webhook with Square
     */

    public function setupWebhook($type,$accessToken,$woocommerce_square_location_id) {
        // setup notifications
		
         $data_json = json_encode(array($type));
		 $url = "https://connect.squareup.com/v1/".$woocommerce_square_location_id."/webhooks";
		 $method = "PUT";
		 $headers = array(
			'Authorization' => 'Bearer '.$accessToken, // Use verbose mode in cURL to determine the format you want for this header
			'Content-Length'  =>  strlen($data_json),
			'Content-Type'  => 'application/json'
		);

		$response = array();
		$response = $this->wp_remote_woosquare($url,$data_json,$method,$headers,$response);
		$objectResponse =  json_decode($response['body'], true);
		if($response['response']['code'] == 200 and $response['response']['message'] == 'OK'){
			update_option('Woosquare_webhook_response',json_encode($objectResponse).' : '.get_option('woo_square_location_id'));
		} else {
			update_option('Woosquare_webhook_response_error',json_encode($objectResponse).' : '.get_option('woo_square_location_id'));
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
		$woo_square_location_id = get_option('woo_square_location_id');	
		// $square = new Square(get_option('woo_square_access_token'),get_option('woo_square_location_id'),WOOSQU_PLUS_APPID);
		$squareSynchronizer = new WooToSquareSynchronizer($this);
		
        foreach ($items as $item) {
            if ($item['variation_id']) {
                if (get_post_meta($item['variation_id'], '_manage_stock', true) == 'yes') {
                    $product_variation_id = get_post_meta($item['variation_id'], 'variation_square_id', true);
					$variation['id'] = $product_variation_id;
					$variation['updated_at'] =  date('Y-m-d').'T'.date("H:i:s").'.'.date("v").'Z';
					
					$current_stock = get_post_meta($item['variation_id'], '_stock', true);
					$total_stock = $current_stock+$item['qty'];
					$_stock = $total_stock - $current_stock;
                    $squareSynchronizer->updateInventory($variation, $_stock, 'SALE',$woo_square_location_id);
                }
            } else {
                if (get_post_meta($item['product_id'], '_manage_stock', true) == 'yes') {
                    $product_variation_id = get_post_meta($item['product_id'], 'variation_square_id', true);
					
					$variation['id'] = $product_variation_id;
					$variation['updated_at'] =  date('Y-m-d').'T'.date("H:i:s").'.'.date("v").'Z';
					
					$current_stock = get_post_meta($item['product_id'], '_stock', true);
					$total_stock = $current_stock+$item['qty'];
					$_stock = $total_stock - $current_stock;
                    $squareSynchronizer->updateInventory($variation, $_stock, 'SALE',$woo_square_location_id);
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
		$squareSynchronizer = new WooToSquareSynchronizer($this);
		$woo_square_location_id = get_option('woo_square_location_id');	
        foreach ($items as $item) {
            if ($item['variation_id']) {
                if (get_post_meta($item['variation_id'], '_manage_stock', true) == 'yes') {
                    $product_variation_id = get_post_meta($item['variation_id'], 'variation_square_id', true);
					
					$variation['id'] = $product_variation_id;
					$variation['updated_at'] =  date('Y-m-d').'T'.date("H:i:s").'.'.date("v").'Z';
					
					$current_stock = get_post_meta($item['variation_id'], '_stock', true);
					$total_stock = $current_stock+$item['qty'];
					
                    $squareSynchronizer->updateInventory($variation, 1 * $item['qty'], 'RECEIVE_STOCK',$woo_square_location_id);
					$product = wc_get_product( $item['variation_id'] );
					wc_update_product_stock( $product, $total_stock);
                }
            } else {
                if (get_post_meta($item['product_id'], '_manage_stock', true) == 'yes') {
                    $product_variation_id = get_post_meta($item['product_id'], 'variation_square_id', true);
					$variation['id'] = $product_variation_id;
					$variation['updated_at'] =  date('Y-m-d').'T'.date("H:i:s").'.'.date("v").'Z';
                    $squareSynchronizer->updateInventory($variation, 1 * $item['qty'], 'RECEIVE_STOCK',$woo_square_location_id);
					
					
					$current_stock = get_post_meta($item['product_id'], '_stock', true);
					$total_stock = $current_stock+$item['qty'];
					
					$product = wc_get_product( $item['product_id'] );
					wc_update_product_stock( $product, $total_stock);
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
		
        // $refund_obj = json_decode($response);
		if('APPROVED' === $refund_obj->refund->status || 'PENDING' === $refund_obj->refund->status ){
			$refund_message = sprintf( __( 'Refunded %s - Refund ID: %s ', 'wpexpert-square' ), wc_price( $refund_obj->refund->amount_money->amount / 100 ), $refund_obj->refund->id);
			update_post_meta($order_id, "refund_created_at", $refund_obj->refund->created_at);
			update_post_meta($order_id, "refund_created_id", $refund_obj->refund->id);
			$order->add_order_note( $refund_message );
		}
		 */
    }

   
	
    
}