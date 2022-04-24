<?php
/**
 * Stripe Gateway.
 *
 * @package cartflows
 */

/**
 * Class Cartflows_Pro_Gateway_WooSquare.
 */
class Cartflows_Pro_Gateway_WooSquare {

	/**
	 * Member Variable
	 *
	 * @var instance
	 */
	private static $instance;

	/**
	 * Key name variable
	 *
	 * @var key
	 */
	public $key = 'square_plus';

	/**
	 * Refund supported variable
	 *
	 * @var is_api_refund
	 */
	public $is_api_refund = true;


	/**
	 *  Initiator
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Check if token is present.
	 *
	 * @param array $order order data.
	 */
	public function has_token( $order ) {

		$is_available = true;
		$gateway               = $this->get_wc_gateway();
		
		if ( $gateway->enabled == 'yes' ) {
			// Square only supports US, Canada and Australia for now.
			if ( ( 
				'US' !== WC()->countries->get_base_country() && 
				'CA' !== WC()->countries->get_base_country() && 
				'GB' !== WC()->countries->get_base_country() &&
				'JP' !== WC()->countries->get_base_country() &&
				'AU' !== WC()->countries->get_base_country() ) || ( 
				'USD' !== get_woocommerce_currency() && 
				'CAD' !== get_woocommerce_currency() && 
				'JPY' !== get_woocommerce_currency() && 
				'AUD' !== get_woocommerce_currency() && 
				'GBP' !== get_woocommerce_currency() ) 
				) {
				$is_available = false;
			}
			
						
		} else {
			$is_available = false;
		}
			
		return apply_filters( 'woocommerce_square_payment_gateway_is_available', $is_available );

		return false;
	}

	/**
	 * Get WooCommerce payment geteways.
	 *
	 * @return array
	 */
	public function get_wc_gateway() {

		global $woocommerce;

		$gateways = $woocommerce->payment_gateways->payment_gateways();
		if(class_exists('WooSquare_Gateway')){
			$this->key = 'square';
		} else if(class_exists('WooSquare_Plus_Gateway')) {
			$this->key = 'square_plus';
		}
		return $gateways[ $this->key ];
	}

	/**
	 * After payment process.
	 *
	 * @param array $order order data.
	 * @param array $product product data.
	 * @return array
	 */
	 
	 
	public function get_square_cartflowkeys($gateway){
		
		if(!empty($gateway)){
			
			$keys = array();
			if($gateway->id == 'square_plus'){
				$woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
				if($woocommerce_square_plus_settings['enable_sandbox'] == 'yes'){
					$keys['locid'] = $woocommerce_square_plus_settings['sandbox_location_id'];
					
					$keys['acctoken'] = $woocommerce_square_plus_settings['sandbox_access_token'];
					
					$keys['application_id'] = $woocommerce_square_plus_settings['sandbox_application_id'];
					
					$keys['endpoint'] = 'squareupsandbox';
				} else {
					$keys['locid'] = get_option('woo_square_location_id');
					
					$keys['acctoken'] = get_option( 'woo_square_access_token' );
					
					$keys['application_id'] = WOOSQU_PLUS_APPID;
					
					$keys['endpoint'] = 'squareup';
				}
				
			} elseif($gateway->id == 'square') {
				$woocommerce_square_settings = get_option('woocommerce_square_settings');
				if($woocommerce_square_settings['enable_sandbox'] == 'yes'){
					$keys['locid'] = $woocommerce_square_settings['sandbox_location_id'];
					
					$keys['acctoken'] = $woocommerce_square_settings['sandbox_access_token'];
					
					$keys['application_id'] = $woocommerce_square_settings['sandbox_application_id'];
					
					$keys['endpoint'] = 'squareupsandbox';
				} else {
					$keys['locid'] = get_option('woo_square_location_id_free');
					
					$keys['acctoken'] = get_option( 'woo_square_access_token_free' );
					
					$keys['application_id'] = SQUARE_APPLICATION_ID;
					
					$keys['endpoint'] = 'squareup';
				}
				
			}
			
		}
		return $keys;
		
	} 
	public function process_offer_payment( $order, $product ) {
			
		$is_successful = false;
  
			
			
		if ( ! $this->has_token( $order ) ) {
			
			
			return $is_successful;
		}
		
			$gateway = $this->get_wc_gateway();
			
			$keys = $this->get_square_cartflowkeys($gateway);
			
			$location_id = $keys['locid'];
			$token = $keys['acctoken'];
			$endpoint = $keys['endpoint'];
        try {
                if ($order) {
                    //shipping address
                    $shipping_address = array(
                        'address_line_1' => $order->get_shipping_address_1() ? $order->get_shipping_address_1() : $order->get_billing_address_1(),
                        'address_line_2' => $order->get_shipping_address_2() ? $order->get_shipping_address_2() : $order->get_billing_address_2(),
                        'locality' => $order->get_shipping_city() ? $order->get_shipping_city() : $order->get_billing_city(),
                        'administrative_district_level_1' => $order->get_shipping_state() ? $order->get_shipping_state() : $order->get_billing_state(),
                        'postal_code' => $order->get_shipping_postcode() ? $order->get_shipping_postcode() : $order->get_billing_postcode(),
                        'country' => $order->get_shipping_country() ? $order->get_shipping_country() : $order->get_billing_country()
                    );

                    //billing address
                    $billing_address = array(
                        'address_line_1' => $order->get_billing_address_1(),
                        'address_line_2' => $order->get_billing_address_2(),
                        'locality' => $order->get_billing_city(),
                        'administrative_district_level_1' => $order->get_billing_state(),
                        'postal_code' => $order->get_billing_postcode(),
                        'country' => $order->get_billing_country() ? $order->get_billing_country() : $order->get_shipping_country()
                    );

                    $order_id = $order->get_id();
                    $currency = $order->get_currency();
                    $customer_card_id = get_post_meta($order_id, '_woos_plus_customer_card_id', true);
                    $square_customer_id = null;
                    $customer_id = $order->get_customer_id();
					
					if(empty($customer_card_id)){
						$_customer_user = get_post_meta($order_id, '_customer_user', true);
						$customer_card_id = get_user_meta($_customer_user, '_wcsr_square_customer_card_id', true);
					}   

					
                    
                    if(empty($square_customer_id)){
                        $square_customer_id = get_user_meta($customer_id, '_square_customer_id', true);
                    }
                    
                    if(empty($square_customer_id)){
                        $square_customer_id = get_post_meta($order_id, '_square_customer_id', true);
                    }   
					
                    if ($square_customer_id && $customer_card_id) {
                        
                        $idempotencyKey = (string) $order_id;
						
						$fields = array(
							"idempotency_key" => $idempotencyKey,
							"location_id" => $location_id,
							"amount_money" => array(
								  "amount" =>  (int) $gateway->format_amount($product['price'], $currency),
								  "currency" => $currency
								),
							"source_id" => $customer_card_id,
							'customer_id' => $square_customer_id,
							'shipping_address' => $shipping_address,
							'billing_address' => $billing_address,
							'reference_id' => (string) $order->get_order_number(),
                            'note' => 'Order #' . (string) $order->get_order_number() .' Upsell/Downsell '
						);
						
						$url = "https://connect.".$endpoint.".com/v2/payments";
						
						$headers = array(
							'Accept' => 'application/json',
							'Authorization' => 'Bearer '.$token,
							'Content-Type' => 'application/json',
							'Cache-Control' => 'no-cache'
						);
						
						$transactionData = json_decode(wp_remote_retrieve_body(wp_remote_post($url, array(
								'method' => 'POST',
								'headers' => $headers,
								'httpversion' => '1.0',
								'sslverify' => false,
								'body' => json_encode($fields)
								)
							)
						)
						);
						
						
                        if (isset($transactionData->payment->id) and $transactionData->payment->card_details->status == 'CAPTURED') {
                            $transactionId = $transactionData->payment->id;
                            add_post_meta($order_id, 'woosquare_transaction_id_upsell/downsell', $transactionId);
							
							add_post_meta($order_id, '_transaction_id_upsell/downsell', $transactionId);
                            add_post_meta($order_id, 'woosquare_transaction_location_id_upsell/downsell', $location_id);
							//if sandbox enable add sandbox prefix.
							$sandbox_prefix = $gateway->test_mode == 'yes' ? 'through sandbox' : '';
                            // Mark as processing
                            $message = sprintf(__('Customer card successfully charged %s (Transaction ID: %s).', 'wcsrs-payment'),$sandbox_prefix, $transactionId);
                            // $order->update_status('processing', $message);
							$this->store_offer_transaction( $order, $transactionId, $product );
							$is_successful = true;
                        } else {														
                            $order->add_order_note( 'Errors: ' . json_encode($transactionData->errors) . ' </br><a target="_blank" href="https://developer.squareup.com/docs/payments-api/error-codes#createpayment-errors"> ERROR CODE REFERENCES </a>');
							$order->update_status('failed');
							$is_successful = false;
						}
                    }
                }
            
        } catch (Exception $ex) {
            $order->update_status('failed', $ex->getMessage());
        }
		
		
		return $is_successful;
	}

	/**
	 * Store Offer Trxn Charge.
	 *
	 * @param WC_Order $order    The order that is being paid for.
	 * @param Object   $response The response that is send from the payment gateway.
	 * @param array    $product  The product data.
	 */
	public function store_offer_transaction( $order, $response, $product ) {

		$order->update_meta_data( 'cartflows_offer_txn_resp_' . $product['step_id'], $response );  
		$order->save();
	}



	/**
	 * Process offer refund
	 *
	 * @param object $order Order Object.
	 * @param array  $offer_data offer data.
	 *
	 * @return string/bool.
	 */
	public function process_offer_refund( $order, $offer_data ) {

		$trans_id = $offer_data['transaction_id'];
		$gateway = $this->get_wc_gateway();
		
		$gateways = array('square_plus','square');
		
		
		if ( in_array( ( version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->payment_method : $order->get_payment_method() ), $gateways ) ) {
			
			
			
			try {
				$currency = version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->get_order_currency() : $order->get_currency();
				
				$location = get_option('woo_square_location_id');
				$woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
				if($woocommerce_square_plus_settings['enable_sandbox'] == 'yes'){
					$location = $woocommerce_square_plus_settings['sandbox_location_id'];
					$token = $woocommerce_square_plus_settings['sandbox_access_token'];
					// $gateway->connect->set_access_token( $woocommerce_square_plus_settings['sandbox_access_token'] );
				}
				$headers = array(
						'Accept' => 'application/json',
						'Authorization' => 'Bearer '.$token,
						'Content-Type' => 'application/json',
						'Cache-Control' => 'no-cache'
					);
				$url = 'https://connect.'.WC_SQUARE_STAGING_URL.'.com/v2/payments/'.$trans_id;
				$transaction_status = json_decode(wp_remote_retrieve_body(wp_remote_get($url, array(
							'method' => 'GET',
							'headers' => $headers,
							'httpversion' => '1.0',
							'sslverify' => false
							)
						)
					)
					);

				// $transaction_status = $gateway->connect->get_transaction_status(  $trans_id );
				
				if ( 'CAPTURED' === $transaction_status->payment->card_details->status ) {
					
					
					$amount = (int) $gateway->format_amount( $offer_data['refund_amount'] , $currency );
					$fields = array(
						"idempotency_key" => uniqid(),
						"payment_id" => $trans_id,
						"reason" => $offer_data['refund_reason'],
						"amount_money" => array(
							  "amount" => $amount,
							  "currency" => $currency,
							),
					);
					
					$url = "https://connect.".WC_SQUARE_STAGING_URL.".com/v2/refunds";
					
					$result = json_decode(wp_remote_retrieve_body(wp_remote_post($url, array(
							'method' => 'POST',
							'headers' => $headers,
							'httpversion' => '1.0',
							'sslverify' => false,
							'body' => json_encode($fields)
							)
						)
					)
					);
					
					
					if ( is_wp_error( $result ) ) {
						throw new Exception( $result->get_error_message() );

					} elseif ( ! empty( $result->errors ) ) {
						throw new Exception( "Error: " . print_r( $result->errors, true ) );
						
					} else {
						if ( 'APPROVED' === $result->refund->status || 'PENDING' === $result->refund->status ) {
							$refund_message = sprintf( __( 'Refunded %s - Refund ID: %s - Reason: %s', 'woosquare' ), wc_price( $result->refund->amount_money->amount / 100 ), $result->refund->id, $reason );
						
							// $order->add_order_note( $refund_message );
						
							$gateway->log( "Success: " . html_entity_decode( strip_tags( $refund_message ) ) );
							return $result->refund->id;
						}
					}
				}

			} catch ( Exception $e ) {
				$this->log( sprintf( __( 'Error: %s', 'woosquare' ), $e->getMessage() ) );
				return false;
			}
		}

		
	}

	/**
	 * Allow gateways to declare whether they support offer refund
	 *
	 * @return bool
	 */
	public function is_api_refund() {

		return $this->is_api_refund;
	}
}

/**
 *  Prepare if class 'Cartflows_Pro_Gateway_WooSquare' exist.
 *  Kicking this off by calling 'get_instance()' method
 */
Cartflows_Pro_Gateway_WooSquare::get_instance();
