<?php

if (!defined('ABSPATH'))
    exit;

class WooSquare_Plus_Gateway_Recurring_Renew extends WooSquare_Plus_Gateway {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();

        if (class_exists('WC_Subscriptions_Order')) {
            add_action('woocommerce_scheduled_subscription_payment_' . $this->id, array($this, 'scheduled_subscription_payment'), 10, 2);
        }
    }

    /**
     * scheduled_subscription_payment function.
     *
     * @param $amount_to_charge float The amount to charge.
     * @param $renewal_order WC_Order A WC_Order object created to record the renewal payment.
     */
    public function scheduled_subscription_payment($amount_to_charge, $renewal_order) {
        $renewal_order_id = $renewal_order->get_id();
        $token = get_option( 'woo_square_access_token' );
		$woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
		$location_id = get_option('woo_square_location_id');
		$endpoint = 'squareup';
		
		if($woocommerce_square_plus_settings['enable_sandbox'] == 'yes'){
			$location_id = $woocommerce_square_plus_settings['sandbox_location_id'];
			$token = $woocommerce_square_plus_settings['sandbox_access_token'];
			$endpoint = 'squareupsandbox';
		}
        try {
            //get subscription
            if (wcs_order_contains_subscription($renewal_order_id, array('parent', 'renewal'))) {
                $subscriptions = wcs_get_subscriptions_for_order($renewal_order_id, array('order_type' => array('parent', 'renewal')));
                //get parent order
                $parent_order_id = null;
                $parent_order = null;
                foreach ($subscriptions as $subscription) {
                    if ($subscription->get_parent_id()) {
                        $parent_order = $subscription->get_parent();
                    }
                }

                if ($parent_order) {
                    //shipping address
                    $shipping_address = array(
                        'address_line_1' => $renewal_order->get_shipping_address_1() ? $renewal_order->get_shipping_address_1() : $renewal_order->get_billing_address_1(),
                        'address_line_2' => $renewal_order->get_shipping_address_2() ? $renewal_order->get_shipping_address_2() : $renewal_order->get_billing_address_2(),
                        'locality' => $renewal_order->get_shipping_city() ? $renewal_order->get_shipping_city() : $renewal_order->get_billing_city(),
                        'administrative_district_level_1' => $renewal_order->get_shipping_state() ? $renewal_order->get_shipping_state() : $renewal_order->get_billing_state(),
                        'postal_code' => $renewal_order->get_shipping_postcode() ? $renewal_order->get_shipping_postcode() : $renewal_order->get_billing_postcode(),
                        'country' => $renewal_order->get_shipping_country() ? $renewal_order->get_shipping_country() : $renewal_order->get_billing_country()
                    );

                    //billing address
                    $billing_address = array(
                        'address_line_1' => $renewal_order->get_billing_address_1(),
                        'address_line_2' => $renewal_order->get_billing_address_2(),
                        'locality' => $renewal_order->get_billing_city(),
                        'administrative_district_level_1' => $renewal_order->get_billing_state(),
                        'postal_code' => $renewal_order->get_billing_postcode(),
                        'country' => $renewal_order->get_billing_country() ? $renewal_order->get_billing_country() : $renewal_order->get_shipping_country()
                    );

                    $parent_order_id = $parent_order->get_id();
                    $currency = $parent_order->get_currency();
                    $customer_card_id = get_post_meta($parent_order_id, '_woos_plus_customer_card_id', true);
                    $square_customer_id = null;
                    $customer_id = $parent_order->get_customer_id();
                    
                    if(empty($square_customer_id)){
                        $square_customer_id = get_user_meta($customer_id, '_square_customer_id', true);
                    }
                    
                    if(empty($square_customer_id)){
                        $square_customer_id = get_post_meta($parent_order_id, '_square_customer_id', true);
                    }   

                    if ($square_customer_id && $customer_card_id) {
                        
                        $idempotencyKey = (string) $renewal_order_id;
						
						$fields = array(
							"idempotency_key" => $idempotencyKey,
							"location_id" => $location_id,
							"amount_money" => array(
								  "amount" =>  (int) $this->format_amount($amount_to_charge, $currency),
								  "currency" => $currency
								),
							"source_id" => $customer_card_id,
							'customer_id' => $square_customer_id,
							'shipping_address' => $shipping_address,
							'billing_address' => $billing_address,
							'reference_id' => (string) $renewal_order->get_order_number(),
                            'note' => 'Order #' . (string) $renewal_order->get_order_number()
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
                            add_post_meta($renewal_order_id, 'woosquare_transaction_id', $transactionId);
							add_post_meta($renewal_order_id, '_transaction_id', $transactionId);
                            add_post_meta($order_id, 'woosquare_transaction_location_id', $location_id);
							//if sandbox enable add sandbox prefix.
							$sandbox_prefix = $this->test_mode == 'yes' ? 'through sandbox' : '';
                            // Mark as processing
                            $message = sprintf(__('Customer card successfully charged %s (Transaction ID: %s).', 'wcsrs-payment'),$sandbox_prefix, $transactionId);
                            $renewal_order->update_status('processing', $message);
                        } else {														
                            $renewal_order->add_order_note( 'Errors: ' . json_encode($transactionData->errors) . ' </br><a target="_blank" href="https://developer.squareup.com/docs/payments-api/error-codes#createpayment-errors"> ERROR CODE REFERENCES </a>');
							$renewal_order->update_status('failed');
						}
                    }
                }
            }
        } catch (Exception $ex) {
            $renewal_order->update_status('failed', $ex->getMessage());
        }
    }

}

$instance = new WooSquare_Plus_Gateway_Recurring_Renew();