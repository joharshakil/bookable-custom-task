<?php

function square_order_sync_add_on($order,$woo_square_locations,$currency,$uid,$token,$endpoint,$square_customer_id){
	$WooSquare_Plus_Gateway = new WooSquare_Plus_Gateway();
	$line_items_array	 = array();
	$order_shipping =  $order->get_data(); // The Order data
	$discounted_amount = null;

	$totalcartitems = count($order->get_items());
	$total_order_item_qty = null;
	foreach ($order->get_items() as $item_id => $item_data) {
		$total_order_item_qty +=  $item_data->get_quantity();
	}

	// Coupons used in the order LOOP (as they can be multiple)
	if(!empty($order->get_used_coupons())){
		foreach( $order->get_used_coupons() as $coupon_name ){

			// Retrieving the coupon ID
			$coupon_post_obj = get_page_by_title($coupon_name, OBJECT, 'shop_coupon');
			$coupon_id = $coupon_post_obj->ID;

			// Get an instance of WC_Coupon object in an array(necesary to use WC_Coupon methods)
			$coupons_obj = new WC_Coupon($coupon_id);

			if(!empty($coupons_obj)){
				if($coupons_obj->get_discount_type() == "fixed_product" ){
					$discounted_amount_fixed_product = round($coupons_obj->get_amount(),2);
				}
				if($coupons_obj->get_discount_type() == "percent" ){

					$discounted_amount_for_fixed_cart = round((($order->get_discount_total()+($order->get_total() - $order->get_total_tax()))*$coupons_obj->get_amount())/100,2);
					//  $discounted_amount_for_fixed_cart = round((($order->get_discount_total()+$order->get_total())*$coupons_obj->get_amount())/100,2);
				}
				if( $coupons_obj->get_discount_type() == "fixed_cart"){
					$discounted_amount_for_fixed_cart = ($coupons_obj->get_amount());
				}
			}
		}
	}

	if(!empty($discounted_amount_fixed_product)){
		$discounts_for_fixed_product = ',"discounts": [
									{
									    "name":"Discount",
									   "amount_money": {
										  "amount": '.(int) $WooSquare_Plus_Gateway->format_amount( $discounted_amount_fixed_product, $currency ).',
										  "currency": "'.$currency.'"
									   },
									   "scope": "ORDER"
									}
								 ]';
		$discounts = '';

	} else {
		$discounts_for_fixed_product = '';
	}
	if(!empty($discounted_amount_for_fixed_cart)){

		$discounts_for_fixed_cart = ',"discounts": [
									{
									   "name":"Discount",
									   "amount_money": {
										  "amount": '.(int) $WooSquare_Plus_Gateway->format_amount( $discounted_amount_for_fixed_cart, $currency ).',
										  "currency": "'.$currency.'"
									   },
									   "scope": "ORDER"
									}
								 ]';
		$discounts = '';
	} else {
		$discounts_for_fixed_cart = '';
	}



	$iteration = 0;

	foreach ($order->get_items() as $item_id => $item_data) {

		$discounted_amount = null;
		// Get an instance of corresponding the WC_Product object


		$product = $item_data->get_product();
		$get_id = $product->get_id();
		$product_name = $product->get_name(); // Get the product name

		$item_quantity = $item_data->get_quantity(); // Get the item quantity

		$item_total = $product->get_price(); // Get the item line total
		$tax_rates = WC_Tax::get_rates( $product->get_tax_class() );

		$tax_data = $item_data->get_data();

		$itemname = str_replace('"', '',$product_name);
		// price without tax - price with tax = xxxx /  price without tax *100
		if(!empty($tax_data['taxes']['total'])){
			$pricewithouttax = $tax_data['total'];
			$pricewithtax = $tax_data['total'] + round($tax_data['taxes']['total'][key($tax_data['taxes']['total'])],2);

			//$pricewithtax = $tax_data['total'] + round($tax_data['taxes']['total'][key($tax_data['taxes']['total'])],2);
			$res = $pricewithtax - $pricewithouttax;
			if (!empty($tax_rates)) {
				$perc = reset($tax_rates);
				$perc =   $perc['rate'];
			} else {
				$perc = ($res/$pricewithouttax )*100;
				$perc = round($perc,2);
			}

			if($pricewithouttax > 0){
				$item_tax = ',"taxes": [
    									{
    									   "name": "TAX-1",
    									   "type": "ADDITIVE",
    									   "percentage": "'.$perc.'"
    									}
    								 ]';
			}else {
				$item_tax = '';
			}
		} else {
			$item_tax = '';
		}

		//Script Start



		//if oorder error store in meta


		$amount = (float) $WooSquare_Plus_Gateway->format_amount( $item_total, $currency );

		if($product->get_type() === "subscription"){
			$_subscription_trial_length = get_post_meta($get_id,'_subscription_trial_length',true);
			if(
					!empty($_subscription_trial_length)
					and
					is_numeric($_subscription_trial_length)
			){
				$amount = (int) $WooSquare_Plus_Gateway->format_amount( 0, $currency );
				$_subscription_trial_period = get_post_meta($get_id,'_subscription_trial_period',true);
				$itemname = $itemname.' with a '.$_subscription_trial_length.'-'.$_subscription_trial_period.' free trial ';
			}

		}

		$note = '';

		$first_name = version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_first_name : $order->get_billing_first_name();
		$last_name = version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_last_name : $order->get_billing_last_name();
		if(empty($first_name) and empty($last_name)){
			$first_name = $last_name = null;
		}

		if(($product->get_type() === "booking")  ){

			$column_data = '';
			$booking_data = new WC_Booking_Data_Store();
			$booking_ids = $booking_data->get_booking_ids_from_order_id( $order->get_id() );
			$booking = new WC_Booking($booking_ids[$iteration]);
			$booker_date = $booking->get_start_date();
			$note .= " " . $booker_date . ", " ;

			foreach ($booking->get_persons() as $id => $qty) {
				$note .= get_the_title($id) . ": " . $qty . ", ";
			}

			$note .= "Order #" . $order->get_order_number();


			if (!empty($order->get_customer_note())) {
				$remove_special_ch =  preg_replace('/[^A-Za-z0-9. -]/', '', $order->get_customer_note());
				$note .= " Order Note: " . $remove_special_ch;
			}


			if (get_option('woocommerce_square_payment_reporting') == 'yes') {
				$amount_total = (int) round($WooSquare_Plus_Gateway->format_amount( $order->get_total(), $currency ),1);
				if (class_exists('WooToSquareSynchronizer')) {
					//  entry
					$square = new Square(get_option('woo_square_access_token'), get_option('woo_square_location_id'), WOOSQU_PLUS_APPID);
					$wooToSquareSynchronizer = new WooToSquareSynchronizer($square);
					$wooToSquareSynchronizerObject = $wooToSquareSynchronizer->paymentReportingWooToSquare($product);
					update_post_meta((string) $order->get_order_number(),'WooSquare_Order_wooToSquareSynchronizerObject',json_encode($wooToSquareSynchronizerObject));

					if($wooToSquareSynchronizerObject->errors or $wooToSquareSynchronizerObject == 1){
						$line_items_array[] = 	'{
									 "name": "'.$itemname.'",
									 "note": "'.$note.'",
                                      "quantity": "'.$item_quantity.'",
                                      "base_price_money": {
							            "amount": '.$amount.',
						             	"currency": "'.$currency.'"
						                }'.$item_tax.'
                                        '.$discounts_for_fixed_product.'
				                     	}';
					} else {
						//"WooCommerce: Order #' . (string) $order->get_order_number().' Customer Name: '. $first_name .' '.$last_name.'"
						$base_price = $product->get_price();
						$product_data = $product->get_data();
						$total_each_product = $item_data->get_total();
						$amount = $tax_data['subtotal'];
						//$amount = (float) $WooSquare_Plus_Gateway->format_amount( $total_each_product, $currency );
						$amount = (float) $WooSquare_Plus_Gateway->format_amount( $amount, $currency );
						$line_items_array[] = 	'{
									"catalog_object_id": "'.$wooToSquareSynchronizerObject.'",
									"note": "'.$note.'",
                                      "quantity": "'.$item_quantity.'",
                                      "base_price_money": {
							            "amount": '.$amount.',
						             	"currency": "'.$currency.'"
						                }'.$item_tax.'
                                        '.$discounts_for_fixed_product.'
				                     	}';
					}
				}
			}
		} else {
			if (get_option('woocommerce_square_payment_reporting') == 'yes') {
				$amount_total = (int)round($WooSquare_Plus_Gateway->format_amount($order->get_total(), $currency), 1);
				if (class_exists('WooToSquareSynchronizer')) {
					//  entry
					$square = new Square(get_option('woo_square_access_token'), get_option('woo_square_location_id'), WOOSQU_PLUS_APPID);
					$wooToSquareSynchronizer = new WooToSquareSynchronizer($square);
					$wooToSquareSynchronizerObject = $wooToSquareSynchronizer->paymentReportingWooToSquare($product);

					update_post_meta((string) $order->get_order_number(),'WooSquare_Order_wooToSquareSynchronizerObject',json_encode($wooToSquareSynchronizerObject));

					$note .= " ";

					$note .= "Order #" . $order->get_order_number();

					if (!empty($order->get_customer_note())) {
						$remove_special_ch =  preg_replace('/[^A-Za-z0-9. -]/', '', $order->get_customer_note());
						$note .= " Order Note: " . $remove_special_ch;
					}

					if($wooToSquareSynchronizerObject->errors or $wooToSquareSynchronizerObject == 1){
						$line_items_array[] = 	'{
									 "name": "'.$itemname.'",
									 "note": "'.$note.'",
                                      "quantity": "'.$item_quantity.'",
                                      "base_price_money": {
							            "amount": '.$amount.',
						             	"currency": "'.$currency.'"
						                }'.$item_tax.'
                                        '.$discounts_for_fixed_product.'
				                     	}';
					} else {
						//"WooCommerce: Order #' . (string) $order->get_order_number().' Customer Name: '. $first_name .' '.$last_name.'"
						$base_price = $product->get_price();
						$product_data = $product->get_data();
						$total_each_product = $item_data->get_total();
						$amount = $tax_data['subtotal'];
						//$amount = (float) $WooSquare_Plus_Gateway->format_amount( $total_each_product, $currency );
						$amount = (float) $WooSquare_Plus_Gateway->format_amount( $amount, $currency );
						$line_items_array[] = 	'{
									"catalog_object_id": "'.$wooToSquareSynchronizerObject.'",
									"note": "'.$note.'",
                                      "quantity": "'.$item_quantity.'",
                                      "base_price_money": {
							            "amount": '.$amount.',
						             	"currency": "'.$currency.'"
						                }'.$item_tax.'
                                        '.$discounts_for_fixed_product.'
				                     	}';
					}

					/*$line_items_array[] = '{
                         "name": "' . $itemname . '",
                          "note": "WooCommerce: Order #' . (string)$order->get_order_number() . ' Customer Name: ' . $first_name . ' ' . $last_name . '",
						"quantity": "' . $item_quantity . '",
						"base_price_money": {
							"amount": ' . $amount . ',
							"currency": "' . $currency . '"
						}' . $item_tax . '

						 ' . $discounts_for_fixed_product . '
					}';*/
				}
			}
		}

		$discounts_for_fixed_product = '';

		if($product->get_type() === "subscription"){
			$_subscription_sign_up_fee = get_post_meta($get_id,'_subscription_sign_up_fee',true);

			if(
					!empty($_subscription_sign_up_fee)
					and
					is_numeric($_subscription_sign_up_fee)
			){
				$line_items_array[] = 	'{
							"name": "Sign-up fee for '.str_replace('"', '',$product_name).'",
							"note": "",
							"quantity": "'.$item_quantity.'",
							"base_price_money": {
								"amount": '.(int) $WooSquare_Plus_Gateway->format_amount( $_subscription_sign_up_fee, $currency ).',
								"currency": "'.$currency.'"
							}
						}';
			}
		}

		$line_items = implode( ', ', $line_items_array );
		$iteration++;
	}

	// Iterating through order shipping items
	foreach( $order->get_items( 'shipping' ) as $item_id => $shipping_item_obj ){
		// Get the data in an unprotected array
		$shipping_item_data = $shipping_item_obj->get_data();

		$shipping_data_id           = $shipping_item_data['id'];
		$shipping_data_order_id     = $shipping_item_data['order_id'];
		$shipping_data_name         = $shipping_item_data['name'];
		$shipping_data_method_title = $shipping_item_data['method_title'];
		$shipping_data_method_id    = $shipping_item_data['method_id'];
		$shipping_data_instance_id  = $shipping_item_data['instance_id'];
		$shipping_data_total        = $shipping_item_data['total'];
		$shipping_data_total_tax    = $shipping_item_data['total_tax'];
		$shipping_data_taxes        = $shipping_item_data['taxes'];


	}

	if(empty($shipping_data_method_title)){
		if(!empty($order->get_shipping_company )|| !empty($order->get_billing_company)) {
			$shipping_data_method_title = empty($order->get_shipping_company) ? $order->get_billing_company : $order->get_shipping_company();
			if(empty($shipping_data_method_title)){
				$shipping_data_method_title = 'No Shipping Selected';
			}
		}
	}

	if(@$_POST['ship_to_different_address'] == "1"){
		//shipping

		$fulfillments[] = '{
								"shipment_details": {
								"recipient": {
									"address": {
                                    "carrier":  "'.$shipping_data_method_title.'",
									"address_line_1": "'.$order->get_shipping_address_1().'",
									"country":  "'.$order->get_shipping_country().'",
									"first_name": "'.$order->get_shipping_first_name().'",
									"last_name": "'.$order->get_shipping_last_name().'",
									"locality":  "'.$order->get_shipping_city().'",
									"postal_code":  "'.$order->get_shipping_postcode().'"
								  },
								  "display_name": "'.$order->get_shipping_first_name().'",
								  "email_address": "'.$order->get_billing_email().'",
								  "phone_number": "'.$order->get_billing_phone().'"
								}
							  },
							  "state": "PROPOSED",
							  "type": "SHIPMENT"
							}';

	}else{
		//billing
		$fulfillments[] = '{
								"shipment_details":{
								"recipient": {
									"address": {
                                    "carrier":  "'.$shipping_data_method_title.'",
									"address_line_1": "'.$order->get_billing_address_1().'",
									"country":  "'.$order->get_billing_country().'",
									"first_name": "'.$order->get_billing_first_name().'",
									"last_name": "'.$order->get_billing_last_name().'",
									"locality":  "'.$order->get_billing_city().'",
									"postal_code":  "'.$order->get_billing_postcode().'"
								  },
								  "display_name": "'.$order->get_billing_first_name().'",
								  "email_address": "'.$order->get_billing_email().'",
								  "phone_number": "'.$order->get_billing_phone().'"
								}
							  },
							  "state": "PROPOSED",
							  "type": "SHIPMENT"
							}';

	}

	$fulfillmentss = implode( ', ', $fulfillments );


	update_post_meta((string) $order->get_order_number(),'WooSquare_Order_lineitems_request',$line_items);
	update_post_meta((string) $order->get_order_number(),'WooSquare_Order_linediscounts_request',json_encode($discounts_for_fixed_cart));

	$webSource[] = '{"name": "Woosquare Plus"}' ;
	$source = implode( ', ', $webSource);
	;

	//coupon applied on whole cart
	$order_create = '{  "idempotency_key": "'.$uid.'",
						"order": {
						"reference_id": "'.(string) $order->get_order_number().'",
						"location_id": "'.$woo_square_locations.'",
                        "source": '.$source.',
						"fulfillments": ['.$fulfillmentss.'],
						"line_items": ['.$line_items.']
                     	'.$discounts_for_fixed_cart.'
							}
						}';


	update_post_meta((string) $order->get_order_number(),'WooSquare_Order_request',$order_create);
	$order_forcustomer = json_decode($order_create);
	update_post_meta((string) $order->get_order_number(),'WooSquare_Order_customer',$square_customer_id);
	if(!empty($square_customer_id)){
		$order_forcustomer->order->customer_id = $square_customer_id;
		$order_create = json_encode($order_forcustomer);
	}


	// $order_create = apply_filters('woosquare_addon_add_shipping',$order_create,$order,$currency,$WooSquare_Plus_Gateway);


	if(!empty($shipping_data_total) and !empty($shipping_data_method_title))
	{
		$add_shipping = json_decode($order_create);

		$shiping_item_array = (object) array(
				'name' => $shipping_data_method_title,
				'note' => 'Shipping Cost',
				'quantity' => '1',
				'base_price_money' => (object) array(
						'amount' =>  round( $WooSquare_Plus_Gateway->format_amount( $shipping_data_total , $currency ),2),
						'currency' => $currency
				),
		);

		// price without tax - price with tax = xxxx /  price without tax *100
		if($shipping_data_total_tax > 0 ){
			$pricewithouttax = $shipping_data_total;

			$pricewithtax = $shipping_data_total + round($shipping_data_total_tax,2);
			$res = $pricewithtax - $pricewithouttax;
			$perc =   ( $res/$pricewithouttax  )*100;
			$shiping_item_array->taxes = json_decode(
					'[
        									{
        									   "name": "TAX-2",
        									   "type": "ADDITIVE",
        									   "percentage": "'.round($perc,2).'"
        									}
        								 ]'
			);


		}
		end($add_shipping->order->line_items);// move the internal pointer to the end of the array
		$key = key($add_shipping->order->line_items)+1;
		$add_shipping->order->line_items[$key] = $shiping_item_array;




		if(!empty($add_shipping->order->discounts)){

			if($coupons_obj->get_discount_type() == "percent" ){
				$shiptotal = round( $shipping_data_total*$coupons_obj->get_amount()/100 ,2);
				$add_shipping->order->discounts[0]->amount_money->amount = $WooSquare_Plus_Gateway->format_amount( $discounted_amount_for_fixed_cart - $shiptotal , $currency );
			}

		}

		$order_create = json_encode($add_shipping);
	}

	if (class_exists( 'WC_Pre_Orders_Order' )){
		if(get_post_meta( $get_id, '_wc_pre_orders_enabled' ,true ) && get_post_meta( $get_id, '_wc_pre_orders_fee' ,true ) > 0) {


			$pre_order_before = json_decode($order_create);

			$pre_order_before_add = '';
			$pre_order_fee = get_post_meta($get_id, '_wc_pre_orders_fee', true);



			$gettax_rate = (reset(WC_Tax::get_rates())['rate']);


			if ($gettax_rate > 0) {
				$preorder_with_tax = ($gettax_rate / 100) * $pre_order_fee;
				$finalamount = $preorder_with_tax + $pre_order_fee;


				$pre_order_before->order->service_charges[0] = (object)array(

						'name' => 'Pre Order Price',
						'note' => 'Preorder Cost',
						'calculation_phase' => 'SUBTOTAL_PHASE',
						'taxable' => true,
						'amount_money' => (object)array(
								'amount' => round($WooSquare_Plus_Gateway->format_amount($finalamount, $currency), 2),
								'currency' => $currency
						),


				);



				$order_create = json_encode($pre_order_before);

			}else{

				$pre_order_before->order->service_charges[0] = (object)array(
						'name' => 'Pre Order Price',
						'note' => 'Preorder Cost',
					//'calculation_phase' => 'SUBTOTAL_PHASE',
					//'taxable' => true,
						'amount_money' => (object)array(
								'amount' => round($WooSquare_Plus_Gateway->format_amount($pre_order_fee, $currency), 2),
								'currency' => $currency
						),


				);

				$order_create = json_encode($pre_order_before);
			}

		}
	}

	update_post_meta((string) $order->get_order_number(),'WooSquare_Order_create_request',$order_create);

	$square = new Square(get_option('woo_square_access_token'), get_option('woo_square_location_id'),WOOSQU_PLUS_APPID);
	$url = "https://connect.".$endpoint.".com/v2/orders";
	$method = "POST";
	$response = array();
	$headers = array(
			'Authorization' => 'Bearer '.$token, // Use verbose mode in cURL to determine the format you want for this header
			'cache-control'  => 'no-cache',
			'Content-Type'  => 'application/json'
	);
	// $response = array();
	$response = $square->wp_remote_woosquare($url,json_decode($order_create),$method,$headers,$response);



	if($response['response']['code'] == 200 and $response['response']['message'] == 'OK'){
		$orderresponse = json_decode( $response['body'], false );
		$order_created = sprintf( __( 'Square order created ( Order ID : %s )', 'wpexpert-square' ), $orderresponse->order->id );
		$order->add_order_note( $order_created );
		update_post_meta((string) $order->get_order_number(),'WooSquare_Order_create_response',$response['body']);
		//update_post_meta((string) $order->get_order_number(),'WooSquare_Order_create_response',$response);
	} else {
		$order_created = sprintf( __( 'Square order created error ( response : %s )', 'wpexpert-square' ), $response );
		$order->add_order_note( $order_created );
		update_post_meta((string) $order->get_order_number(),'WooSquare_Order_create_response',$response['body']);
		//update_post_meta((string) $order->get_order_number(),'WooSquare_Order_create_response_error',$response);
	}

	//check if WooCommerce order total is not equal with Square order total skip this order.

	$amount = (int) $WooSquare_Plus_Gateway->format_amount( $order->get_total(), $currency );

	if(!empty($orderresponse->order)){

		if($amount == $orderresponse->order->total_money->amount){
			return $orderresponse->order->id;
		} else {

			if($amount > $orderresponse->order->total_money->amount){
				$adjustment =  (($amount/100) - ($orderresponse->order->total_money->amount/100));
				$idempotencyKey = (string)rand(10000,200000);
				$order_adjustment ='{
							"idempotency_key": "'.$idempotencyKey.'",
							"order": {
                                       "version": '.$orderresponse->order->version.',
                                         "line_items": [
                                          {
                                           "name":"Adjustment",
                                            "quantity": "1",
									         "base_price_money":{
										     "amount":'.(int)$WooSquare_Plus_Gateway->format_amount( $adjustment, $currency ).',
										     "currency": "'.$currency.'"
									       }
                                        }
                                     ]
                                   }
						        }';


				$square = new Square(get_option('woo_square_access_token'), get_option('woo_square_location_id'),WOOSQU_PLUS_APPID);
				$url = "https://connect.".$endpoint.".com/v2/orders/".$orderresponse->order->id;
				$method = "PUT";
				$response = array();
				$headers = array(
						'Authorization' => 'Bearer '.$token, // Use verbose mode in cURL to determine the format you want for this header
						'cache-control'  => 'no-cache',
						'Content-Type'  => 'application/json'
				);
				$response = $square->wp_remote_woosquare($url,json_decode($order_adjustment),$method,$headers,$response);
				$orderresponse = json_decode( $response['body'], false );


				if($response['response']['code'] == 200 and $response['response']['message'] == 'OK'){
					update_post_meta((string) $order->get_order_number(),'WooSquare_Order_create_response_discount_adjustment',$response['body']);
					return $orderresponse->order->id;

				} else {
					update_post_meta((string) $order->get_order_number(),'WooSquare_Order_create_response_adjustment_failed',$response['body']);
					update_post_meta((string) $order->get_order_number(),'WooSquare_Order_create_status','The Square payment total does not match the WooCommerce order total.');
					return ;
				}

			}
			else if($amount < $orderresponse->order->total_money->amount){

				$adjustment =  number_format((($orderresponse->order->total_money->amount) / 100) - ($amount/100) , 2 );
				$idempotencyKey = (string)rand(10000,200000);

				$order_adjustment = '{
							"idempotency_key": "'.$idempotencyKey.'",
							"order": {
							"version": '.$orderresponse->order->version.',
                                  "discounts": [
                                          {
                                            "name":"Adjustment",
                                            "type":"FIXED_AMOUNT",
									         "amount_money": {
										     "amount": '.(int) $WooSquare_Plus_Gateway->format_amount( $adjustment, $currency )  .',
										     "currency": "'.$currency.'"
									           },
									          "scope": "ORDER"
                                             }
                                        ]
                                   }
						      }';

				$square = new Square(get_option('woo_square_access_token'), get_option('woo_square_location_id'),WOOSQU_PLUS_APPID);
				$url = "https://connect.".$endpoint.".com/v2/orders/".$orderresponse->order->id;
				$method = "PUT";
				$response = array();
				$headers = array(
						'Authorization' => 'Bearer '.$token, // Use verbose mode in cURL to determine the format you want for this header
						'cache-control'  => 'no-cache',
						'Content-Type'  => 'application/json'
				);


				$response = $square->wp_remote_woosquare($url,json_decode($order_adjustment),$method,$headers,$response);
				$orderresponse = json_decode( $response['body'], false );

				if($amount > $orderresponse->order->total_money->amount){

					$adjustment =  (($amount/100) - ($orderresponse->order->total_money->amount/100));

					$idempotencyKey = (string)rand(10000,200000);

					$order_adjustment ='{
							"idempotency_key": "'.$idempotencyKey.'",
							"order": {
                                       "version": '.$orderresponse->order->version.',
                                         "line_items": [
                                          {
                                           "name":"Adjustment",
                                            "quantity": "1",
									         "base_price_money":{
										     "amount":'.(int)$WooSquare_Plus_Gateway->format_amount( $adjustment, $currency ).',
										     "currency": "'.$currency.'"
									       }
                                        }
                                     ]
                                   }
						        }';


					$square = new Square(get_option('woo_square_access_token'), get_option('woo_square_location_id'),WOOSQU_PLUS_APPID);
					$url = "https://connect.".$endpoint.".com/v2/orders/".$orderresponse->order->id;
					$method = "PUT";
					$response = array();
					$headers = array(
							'Authorization' => 'Bearer '.$token, // Use verbose mode in cURL to determine the format you want for this header
							'cache-control'  => 'no-cache',
							'Content-Type'  => 'application/json'
					);
					$response = $square->wp_remote_woosquare($url,json_decode($order_adjustment),$method,$headers,$response);
					$orderresponse = json_decode( $response['body'], false );


					if($response['response']['code'] == 200 and $response['response']['message'] == 'OK'){
						update_post_meta((string) $order->get_order_number(),'WooSquare_Order_create_response_discount_adjustment',$response['body']);
						return $orderresponse->order->id;

					} else {
						update_post_meta((string) $order->get_order_number(),'WooSquare_Order_create_response_adjustment_failed',$response['body']);
						update_post_meta((string) $order->get_order_number(),'WooSquare_Order_create_status','The Square payment total does not match the WooCommerce order total.');
						return ;
					}
				} else{
					if($response['response']['code'] == 200 and $response['response']['message'] == 'OK'){
						update_post_meta((string) $order->get_order_number(),'WooSquare_Order_create_response_discount_adjustment',$response['body']);
						return $orderresponse->order->id;

					} else {
						update_post_meta((string) $order->get_order_number(),'WooSquare_Order_create_response_adjustment_failed',$response['body']);
						update_post_meta((string) $order->get_order_number(),'WooSquare_Order_create_status','The Square payment total does not match the WooCommerce order total.');
						return ;
					}
				}
			}
		}
	}
}

?>