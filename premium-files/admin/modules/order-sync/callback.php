<?php

square_woo_debug_log('info', "Callback page called.");

require(dirname(__FILE__) . '/../../../../../../../wp-blog-header.php');

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    square_woo_debug_log('info', "Callback page called via get request.");
    echo die('Callback request working!');
}

$post_data = json_decode(file_get_contents("php://input"));

if (!$post_data) {
    square_woo_debug_log('info', "Callback page called via POST request. but there is no post data.");
    echo die('Callback request working with no post data');
}

//square_woo_debug_log('info', "Callback page called via POST with post data (json format) " . $HTTP_RAW_POST_DATA);

if (isset($post_data->event_type) && $post_data->event_type == "TEST_NOTIFICATION") {
    square_woo_debug_log('error', "This is a test notifications from Square ");
    header("HTTP/1.1 200 OK");
	die();
}
if(empty(get_option('square_payment_begin_time'))){
	// 2013-01-15T00:00:00Z
	update_option('square_payment_begin_time',date("Y-m-d")."T00:00:00Z");
}

	if(get_option('square_calback_server') == 'busy'){
		square_woo_debug_log('info', "Callback page return server busy.");
		delete_option('square_calback_server');
		return false;
	}
	
	update_option('square_calback_server','busy');
	$woo_square_location_id_for_callback = get_option('woo_square_location_id_for_callback');
	$woo_square_access_token_for_callback = get_option('woo_square_access_token_for_callback');
	$square = new Square($woo_square_access_token_for_callback, $woo_square_location_id_for_callback,WOOSQU_PLUS_APPID);
	$url = "https://connect.squareup.com/v1/".$woo_square_location_id_for_callback."/payments?begin_time=".get_option('square_payment_begin_time')."&end_time=".date('Y-m-d', strtotime(' + 1 days'))."T00:00:00Z&order=DESC";

	$headers = array( 
		'Authorization' => 'Bearer '.$woo_square_access_token_for_callback, // Use verbose mode in cURL to determine the format you want for this header
		'Content-Type'  => 'application/json;'
	);

	$response = array();
	$method = "GET";
	$args = array('');   

	$interval = 30; 
	if (get_option('_transient_timeout_' .  $woo_square_location_id_for_callback.'transient_callback_square' ) > time()){
		
		$response = get_transient( $woo_square_location_id_for_callback.'transient_callback_square'  );
		
	} else {
		
		$response = $square->wp_remote_woosquare($url,$args,$method,$headers,$response);			
		set_transient( $woo_square_location_id_for_callback.'transient_callback_square', $response, $interval );
	}
	// $response = $square->wp_remote_woosquare($url,$args,$method,$headers,$response);
	if(!empty($response['response'])){ 
		if($response['response']['code'] == 200 and $response['response']['message'] == 'OK'){
			$payment_obj = json_decode($response['body'], false);
		} else {
			return false;
		}
	} else {
		return false;
	}
	
square_woo_debug_log('info', "All transaction response" .json_encode( $response ));
square_woo_debug_log('info', "All transaction request : https://connect.squareup.com/v1/".$woo_square_location_id_for_callback."/payments?begin_time=".get_option('square_payment_begin_time')."&end_time=".date('Y-m-d', strtotime(' + 1 days'))."T00:00:00Z&order=DESC");

// $payment_obj = json_decode($response);


if (empty($payment_obj)) {
    // some kind of an error happened
    square_woo_debug_log('error', "The response of payment details curl request " . json_encode($err));
    // curl_close($ch);
    return false;
} else {
    square_woo_debug_log('info', "The response of payment details curl request " . json_encode($response));
    // curl_close($ch);
	
	foreach($payment_obj as $payment){
    if (!empty($payment->itemizations) or !empty($payment->refunds)) {
        
       
		global $wpdb;
		$checkif_order_not_exist = $wpdb->get_results("SELECT * FROM `".$wpdb->postmeta."` WHERE meta_key='square_payment_id' AND meta_value='".$payment->id."'");
		if(empty($checkif_order_not_exist[0])){
			 foreach ($payment->itemizations as $item) {
				 if($item->name == "Custom Amount"){
					square_woo_debug_log('info', "Square Custom ammount not supported");
					continue 2;    
				 }
				  if(empty($item->item_detail->sku)){
					square_woo_debug_log('info', "Square item not found order break");
    				continue 2;    
				 }
			 }
            
            //if customer exist 
			$exploded_payment_url = explode('/',$payment->payment_url);
			 
			$url = "https://connect.squareup.com/v2/locations/".$woo_square_location_id_for_callback."/orders/batch-retrieve";

			$response = array();
			$method = "POST";
			$args =  array('order_ids'=> array($exploded_payment_url[6]));   

			$response = $square->wp_remote_woosquare($url,$args,$method,$headers,$response);
			if(!empty($response['response'])){ 
				if($response['response']['code'] == 200 and $response['response']['message'] == 'OK'){
					$payment_order_obj = json_decode($response['body'], false);
				} else {
					square_woo_debug_log('info', "Square order not found order response for ".$payment." and order error ".json_encode($response));
				}
			} else {
				square_woo_debug_log('info', "Square order not found order response for ".$payment." and order error ".json_encode($response));
			}
			if(!empty($payment_order_obj->orders[0]->customer_id)){
				
				$url = "https://connect.squareup.com/v2/customers/".$payment_order_obj->orders[0]->customer_id;
				
				$response = array();
				$method = "GET";
				$args =  array(''); 
				

				$response = $square->wp_remote_woosquare($url,$args,$method,$headers,$response);
				
				if(!empty($response['response'])){ 
					if($response['response']['code'] == 200 and $response['response']['message'] == 'OK'){
						$payment_customer_obj = json_decode($response['body'], false);
						if(!empty($payment_customer_obj->customer)){
							$email = $payment_customer_obj->customer->email_address;
							$billing_first_name = $payment_customer_obj->customer->given_name;
							$billing_last_name = $payment_customer_obj->customer->family_name;
							$user = get_user_by('email', $email);
							//check if email exist in woocommerce square just get id and link it else creating customer.
							if(empty($user->data->ID)){
								$user_id = wc_create_new_customer( $email, $billing_first_name, wp_generate_password(12) );
								update_user_meta( $user_id, "billing_first_name", $billing_first_name );
								update_user_meta( $user_id, "first_name", $billing_first_name );
								update_user_meta( $user_id, "billing_last_name", $billing_last_name );
								update_user_meta( $user_id, "last_name", $billing_last_name );
								update_user_meta( $user_id, "_square_customer_id", $payment_customer_obj->customer->id );
								update_user_meta( $user_id, "billing_phone", $payment_customer_obj->customer->phone_number );
								update_user_meta( $user_id, "billing_address_1", $payment_customer_obj->customer->address->address_line_1 );
								update_user_meta( $user_id, "billing_address_2", $payment_customer_obj->customer->address->address_line_2 );
								update_user_meta( $user_id, "billing_country", $payment_customer_obj->customer->address->country );
								update_user_meta( $user_id, "billing_postcode", $payment_customer_obj->customer->address->postal_code );
								update_user_meta( $user_id, "billing_state", $payment_customer_obj->customer->address->administrative_district_level_1 );
								
							} else {
								$user_id = $user->ID;
							}
						} else {
							$user = get_user_by('login', 'square_user');
							$user_id = $user->ID;
						}
					} else {
						square_woo_debug_log('info', "Square customer not found customer response for ".$payment." and customer error ".json_encode($response));
					}
				} else {
					square_woo_debug_log('info', "Square customer not found customer response for ".$payment." and customer error ".json_encode($response));
				}
				
				
				
			} else {
				$user = get_user_by('login', 'square_user');
				$user_id = $user->ID;
			}
				
				
            
            $args = array(
                'customer_id' => $user_id,
                'created_via' => 'Square',
            );
            $order = wc_create_order($args);
            square_woo_debug_log('info', "Creating new order for : ".$order->get_order_number()." and payment id is ".$payment->id);
			$result = '';
            foreach ($payment->itemizations as $item) {
                square_woo_debug_log('info', "Square item details: " . json_encode($item));

                $sku = $item->item_detail->sku;
				
				global $wpdb;

				$product_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $sku ) );

				
				
				
              
                square_woo_debug_log('info', "The result of searching for item on woocommerce: " . $product_id);
                // do something if the meta-key-value-pair exists in another post


                if (!empty($product_id)) {
                    $order->add_product(wc_get_product($product_id), $item->quantity); //(get_product with id and next is for quantity)
                } else {
                    square_woo_debug_log('info', "product not found on woocommerce.");

					/* get all items */
                 
					
					$WooToSquareSynchronizer = new WooToSquareSynchronizer($square);
					/* get Inventory of all items */
					$url = 'https://connect.squareup.com/v2/catalog/search';

					$headers = array(
						'Authorization' => 'Bearer '.$woo_square_access_token_for_callback, // Use verbose mode in cURL to determine the format you want for this header
						'Content-Type'  => 'application/json;',
						'Square-Version'  => '2020-12-16'
					);
				$method = "POST";
				$response = array();
				$woo_square_location_id = get_option('woo_square_location_id');
				$args = array (
				  'object_types' => 
				  array (
					0 => 'CATEGORY',
					1 => 'ITEM_VARIATION',
					2 => 'ITEM',
					3 => 'IMAGE'
				  ),
				  'include_related_objects' => true,
				  'query' => 
				  array (
					'text_query' => 
					array (
					  'keywords' => 
					  array (
						0 => $sku,
					  ),
					),
				  ),
				);
				 $response = $square->wp_remote_woosquare($url,$args,$method,$headers,$response);
           
				$squareSynchronizer = new SquareToWooSynchronizer($square);
					
				if(!empty($response['response'])){
					if($response['response']['code'] == 200 and $response['response']['message'] == 'OK'){
						$squareProduct = json_decode($response['body'], false);
						
						
					}
				}
				
					if(empty($squareProduct->objects)){
						//item product deleted in square as well so we will also add as trash in woocommerce.
						
						$my_post = array(
							'post_title' => $item->name,
							'post_status' => 'trash',
							'post_author' => 1,
							'post_type' => 'product'
						);
						$id = wp_insert_post($my_post, true);
						update_post_meta($id, '_visibility', 'visible');
						update_post_meta($id, '_regular_price', $item->single_quantity_money->amount / 100 );
						update_post_meta($id, '_price', $item->single_quantity_money->amount / 100 );
						update_post_meta($id, '_sku', $item->item_detail->sku );

						update_post_meta($id, 'square_id', $item->item_detail->item_id);
						update_post_meta($id, 'variation_square_id', $item->item_detail->item_variation_id );
						
						
						$sku = $item->item_detail->sku;
					   
						global $wpdb;

						$product_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $sku ) );

						square_woo_debug_log('info', "The result of searching AGAIN for item on woocommerce " . $product_id);

						
						$order->add_product(wc_get_product($product_id), $item->quantity); //(get_product with id and next is for quantity)
						
						
					} else {
						// product found in square so create missing product on woocommerce.
						
						square_woo_debug_log('info', "new category add if category not exist in woocommerce.");
						// $item->item_detail
						
						if(!empty($squareProduct->objects)){
							foreach($squareProduct->objects as $objs){
								if($objs->type == "ITEM"){
									foreach($objs->item_data->variations as $variation){
										if($variation->item_variation_data->sku == $sku){
											
											$squareInventory = $squareSynchronizer->getSquareInventory(json_decode(json_encode($objs->item_data->variations), true));
											
											
											foreach($squareProduct->related_objects as $cat_objs){
												if($cat_objs->type == "CATEGORY"){
													if($cat_objs->id == $objs->item_data->category_id){
														
														if(!empty($objs->item_data->category->name)){
															$objs->item_data->category->name = $cat_objs->category_data->name;
															
															
															$cat_objs->name = $cat_objs->category_data->name;
															$result = $squareSynchronizer->addCategoryToWoo($cat_objs);
														}
													}
													
												}
												//for image 
												if($cat_objs->type == "IMAGE"){
													if($cat_objs->id == $objs->image_id){
														
														if(!empty($objs->image_id)){
															$objs->item_data->image_data = $cat_objs;
														}
													}
													
												}
											}
											
											$fetchsquareProduct = $objs->item_data;
											$fetchsquareProduct->id = $objs->id;
											$fetchsquareProduct->image_id = $objs->image_id;
											$fetchsquareProduct->present_at_all_locations = $objs->present_at_all_locations;
											$fetchsquareProduct->version = $objs->version;
											$fetchsquareProduct->updated_at = $objs->updated_at;
											
											
										}
									}
								}
							}
							
							
						}
						
						
						if ($result!==FALSE){
							update_option("is_square_sync_{$result}", 1);           
						}
						
						square_woo_debug_log('info', "new product going to be add in woocommerce.");
						
						
						$squareInventory = $squareSynchronizer->convertSquareInventoryToAssociative($squareInventory->counts);
							
						$squareSynchronizer->addProductToWoo($fetchsquareProduct, $squareInventory);
						
						$sku = $item->item_detail->sku;
					   
						global $wpdb;

						$product_id =  $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 1", $sku ) );

						square_woo_debug_log('info', "The result of searching AGAIN for item on woocommerce " . $product_id);
						$current_stock = get_post_meta($product_id, '_stock', true);
						update_post_meta( $product_id, '_stock', $current_stock + $item->quantity );
						
						$order->add_product(wc_get_product($product_id), $item->quantity); //(get_product with id and next is for quantity)
					}
					
					
					
                }
            }
			
			
			
            $order_id = $order->get_order_number();
            
			add_post_meta($order_id, 'square_payment_id', $payment->id);
			//update order date according to square created date.
			// created_at
			
			
			
			$payment_date = $payment->created_at;
			$payment_date  = str_replace("Z","",$payment_date);
			$payment_date = explode("T",$payment_date);
			//2017-11-08 06:44:00
			$date = $payment_date[0]." ".$payment_date[1];
            //check if there is discount in the order
            if ($payment->discount_money->amount) {
				$total = $order->calculate_totals();
                $order->set_total(-1 * $payment->discount_money->amount / 100, 'cart_discount');
                $order->set_total($total + ($payment->discount_money->amount / 100), 'total');
				
            } else {
				$total = $order->calculate_totals();
			}
			
			$order->update_status('completed');	
			
			wc_reduce_stock_levels($order_id);
			
			
			global $wpdb;
			$sql ="UPDATE `wp_posts` SET `ID` = '".$order_id."', `post_date_gmt` = '".$date."', `post_date` = '".$date."' WHERE `ID` = '".$order_id."'";
			$rez = $wpdb->query($sql);
		}
		
		//Check if this request is "Refund Request"
		if (count($payment->refunds)){ 
            square_woo_debug_log('info', "Creating new refund and array of refund from square .".json_encode($payment->refunds));
            global $wpdb;
            $results = $wpdb->get_results("select post_id, meta_key from $wpdb->postmeta where meta_value = '$payment->id'", ARRAY_A);
            square_woo_debug_log('info', "refund query result :" . json_encode($results));
            if (count($results)) {
                $order_id = $results[0]['post_id'];
                $created_at = get_post_meta($order_id, "refund_created_at", true);
                if ($created_at != $payment->refunds[0]->created_at and !empty($payment->refunds[0]->created_at)) {// Avoid duplicate insert in case we refund from woo commerce which will fire payment update webhooks, so we need do nothing in this case to avoid dubplicate insertion
				$refund_obj = wc_create_refund(array('order_id' => $order_id, 'amount' => -1 * $payment->refunds[0]->refunded_money->amount / 100, 'reason' => $payment->refunds[0]->reason));
                $user = get_user_by('login', 'square_user');
                wp_update_post(array('ID' => $refund_obj->id, 'post_author' => $user->ID));
				update_post_meta($order_id, "refund_created_at", $payment->refunds[0]->created_at);
				//increase stock after refund.
				foreach ($payment->itemizations as $item) {
				$variation_id = $item->item_detail->item_variation_id;
				$args = array(
					'post_type' => array('product', 'product_variation'),
					'meta_query' => array(array('key' => 'variation_square_id', 'value' => $variation_id)),
					'fields' => 'ids'
				);
				$vid_query = new WP_Query($args);
				$vid_ids = $vid_query->posts;
				 if ($vid_ids) {
					$product_id = $vid_ids[0];
					$product  = get_product($product_id);
					if ( $product && $product->managing_stock()){ 
							$old_stock = $product->get_stock_quantity(); 
							$new_stock = wc_update_product_stock( $product, $item->quantity, 'increase' ); 
							$order = new WC_Order( $order_id );
							do_action( 'woocommerce_restock_refunded_item', $product->get_id(), $old_stock, $new_stock, $order, $product ); 
					  } 
					
				}
				}
                }
			}  
			
        }
        
    } 
}


//need to uncomment
update_option('square_payment_begin_time',date("Y-m-d")."T00:00:00Z");
delete_option('square_calback_server');
}

/**
 * @param type $data
 */
function square_woo_debug_log($type, $data) {
    error_log("[$type] [" . date("Y-m-d H:i:s") . "] " . print_r($data, true) . "\n", 3, dirname(__FILE__) . '/logs.log');
}
