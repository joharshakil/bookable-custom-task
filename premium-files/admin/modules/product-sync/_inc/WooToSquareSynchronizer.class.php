<?php

/**
 * Synchronize From WooCommerce To Square Class
 */

class WooToSquareSynchronizer {
	/*
	* @var square square class instance
	*/

	protected $square;

	/**
	 *
	 * @param object $square object of square class
	 */
	public function __construct($square) {

		// require_once WOO_SQUARE_PLUGIN_PATH . '_inc/Helpers.class.php';
		$this->square = $square;

	}

	/*
	* Automatic Sync All products, categories from Woo-Commerce to Square
	*/

    public function paymentReportingWooToSquare($product){


        $checkSKU = $product->get_sku();

        $checkType = $product->get_type();
        $token           = get_option( 'woo_square_access_token' );
        $location_id = get_option('woo_square_location_id');
        $url = 'https://connect.squareup.com/v2/catalog/search';

        $woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
        if(@$woocommerce_square_plus_settings['enable_sandbox'] == 'yes'){
            $token = $woocommerce_square_plus_settings['sandbox_access_token'];
            $location_id = $woocommerce_square_plus_settings['sandbox_location_id'];
            $url = 'https://connect.squareupsandbox.com/v2/catalog/search';
        }

      //  $square = new Square(get_option('woo_square_access_token'), get_option('woo_square_location_id'),WOOSQU_PLUS_APPID);
        $square = new Square($token, $location_id,WOOSQU_PLUS_APPID);

        $headers = array(
            'Authorization' => 'Bearer '.$token, // Use verbose mode in cURL to determine the format you want for this header
            'Content-Type'  => 'application/json;',
        );
        $method = "POST";
        $response = array();
        $args = array (
            'object_types' =>
                array (
                    0 => 'ITEM',
                    1 => 'ITEM_VARIATION'
                ),
            'include_related_objects' => true,
            'include_deleted_objects' => false,
            'query' =>  array (
                'exact_query' =>  array (
                    //$checkSKU 'water-bottle'
                     'attribute_name' => 'sku' ,
                     'attribute_value' => $checkSKU
                    ),
                ),
            );

        $response = $square->wp_remote_woosquare($url,$args,$method,$headers,$response);

        $squareProduct = json_decode($response['body'], false);


        if(!empty($response['response'])) {
            if ($response['response']['code'] == 200 and $response['response']['message'] == 'OK') {

                if(empty($squareProduct->objects)){

                        $product_square_id = '';

                        $product_id = $product->get_parent_id();

                        $args = array(
                            'post_type' => 'product',
                            'posts_per_page' => -1,
                            'include' => $product_id
                        );


                    $woocommerce_product = get_posts($args);
                    $_product = wc_get_product( $woocommerce_product[0]->ID );

                    $result = $this->addProduct($woocommerce_product[0], $product_square_id);
                   
                    if ($result['response']['code'] == 200 and $result['response']['message'] == 'OK') {
                        $result = json_decode($result['body'], false);
                        update_post_meta($woocommerce_product[0]->ID, 'is_square_sync', 1);
                        return $result->catalog_object->item_data->variations[0]->id;

                    } else {
                        return $result;
                    }
                } else {

                  return  $squareProduct->objects[0]->id;
                }
            } else {

               return $squareProduct;
            }
        } else {
            return $squareProduct;
        }
    }


	public function syncFromWooToSquare() {

		
		$square = new Square(get_option('woo_square_access_token'), get_option('woo_square_location_id'),WOOSQU_PLUS_APPID);
		$SquareToWooSynchronizer = new SquareToWooSynchronizer($square);
		$squareItems = $SquareToWooSynchronizer->getSquareItems();
        $woo_square_location_id = get_option('woo_square_location_id');
        $token           = get_option( 'woo_square_access_token' );
        $end_point = 'squareup';

        $woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
        if(@$woocommerce_square_plus_settings['enable_sandbox'] == 'yes'){
            $token = $woocommerce_square_plus_settings['sandbox_access_token'];
            $woo_square_location_id = $woocommerce_square_plus_settings['sandbox_location_id'];
            $end_point = 'squareupsandbox';
        }

		// $squareItems = $this->getSquareItems();
		if($squareItems){
			$squareItems = $this->simplifySquareItemsObject($squareItems);
		}else{
			$squareItems= [];
		}

		//1-get unsynchronized categories (add/update)
		$categories = $this->getUnsynchronizedCategories();
		$squareCategories = $this->getCategoriesSquareIds($categories);
		$woo_square_sync_preference = get_option('woo_square_sync_preference');
		$woo_square_auto_sync = get_option('woo_square_auto_sync');
		$woo_square_listsaved_categories_wooco = get_option('woo_square_listsaved_categories_wooco');
		foreach ($categories as $cat) {

			$squareId= NULL;


			if($woo_square_sync_preference == 0 and $woo_square_auto_sync == 1){
				if(in_array($cat->term_id, $woo_square_listsaved_categories_wooco)){
					if (isset($squareCategories[$cat->term_id])) {      //update
						$squareId = $squareCategories[$cat->term_id];
						$result = $this->editCategory($cat, $squareId);
						
					}else{                                         //add
						$result = $this->addCategory($cat);
						
					}
				}
			} else {

				if (isset($squareCategories[$cat->term_id])) {      //update
					$squareId = $squareCategories[$cat->term_id];
					$result = $this->editCategory($cat, $squareId);
					
				}else{                                         //add
					$result = $this->addCategory($cat);
					
				}

			}

			if ($result===TRUE) {
				update_option("is_square_sync_{$cat->term_id}", 1);
			}

			//check if response returned is bool or error response message
			$message = NULL;
			if (!is_bool($result)){
				$message = $result['message'];
				$result = FALSE;
			}

			
		}

		//2-get unsynchronized products (add/update)
		$unsyncProducts = $this->getUnsynchronizedProducts();
		$this->getProductsSquareIds($unsyncProducts, $excludedProducts);
		$productIds = array(0);

		foreach ($unsyncProducts as $product){
			if(in_array($product->ID, $excludedProducts)){
				continue;
			}
			$productIds[] = $product->ID;
		}




		$posts_per_page = -1;
		if($woo_square_sync_preference == 0 and $woo_square_auto_sync == 1){
			$woo_square_listsaved_products_wooco = get_option('woo_square_listsaved_products_wooco');
			$productIds = $woo_square_listsaved_products_wooco;
		}

		/* get all products from woocommerce */
		$args = array(
				'post_type' => 'product',
				'posts_per_page' => $posts_per_page,
				'include' => $productIds
		);


		$woocommerce_products = get_posts($args);


		if($woo_square_sync_preference == 0 and $woo_square_auto_sync == 1){
			if(empty($woo_square_listsaved_products_wooco)){
				$woocommerce_products = array();
			}
		}

		// Update Square with products from WooCommerce
		if ($woocommerce_products) {

			foreach ($woocommerce_products as $woocommerce_product) {
				// sleep(2);
				//check if woocommerce product sku is exists in square product sku
				$product_square_id = $this->checkSkuInSquare($woocommerce_product, $squareItems);
				
				
				
				if(!$product_square_id){
						// not exist in square so check in woo this product already updated
						$product_square_id = get_post_meta($woocommerce_product->ID, 'square_id', true);
						if($product_square_id){
							$exploded_product_square_id = explode('-',$product_square_id);
							if(count($exploded_product_square_id) == 5){
								
								$product = wc_get_product( $woocommerce_product->ID );
								
								$response = array();
								
								$method = "POST";
								$url = "https://connect.".$end_point.".com/v2/catalog/search";

								$headers = array(
									'Authorization' => 'Bearer '.$token, // Use verbose mode in cURL to determine the format you want for this header
									'Content-Type'  => 'application/json;',
									'Square-Version'  => '2020-12-16'
								);
						
						$args = array (
						  'object_types' => 
						  array (
							0 => 'ITEM',
							1 => 'ITEM_VARIATION'
						  ),
						  'include_related_objects' => true,
						  'query' => 
						  array (
							'text_query' => 
							array (
							  'keywords' => 
							  array (
								0 => $product->get_sku(),
							  ),
							),
						  ),
						);
						 $response = $square->wp_remote_woosquare($url,$args,$method,$headers,$response);
							if(!empty($response['response'])){
							if($response['response']['code'] == 200 and $response['response']['message'] == 'OK'){
								$squareProduct = json_decode($response['body'], false);
								}
							}
								if(!empty($squareProduct->related_objects)){
									foreach($squareProduct->related_objects as $obj){
									if($obj->type == 'ITEM'){
											$product_square_id = $obj;
										}
									}
								}
								
									
							}else {
								$product_square_id = '';
							}
							
						}
					} 

					$result = $this->addProduct($woocommerce_product, $product_square_id);
					
					   //Sync modifier  woo into square


                $modifier_value = get_post_meta($woocommerce_product->ID, 'product_modifier_group_name', true);

                $modifier_set_name = array();

                if (!empty($modifier_value)) {
                    
                       session_start();
                  
                   $_SESSION["productid"] = $woocommerce_product->ID;
                   
                   $_SESSION["product_loop_id"] = $woocommerce_product->ID;
                   
                    $kkey = 0;

                    foreach ($modifier_value as $mod) {


                        $mod = (explode("_", $mod));

                        if (!empty($mod[2])) {


                            global $wpdb;
                            $rcount = $wpdb->get_var("SELECT modifier_set_unique_id FROM " . $wpdb->prefix . "woosquare_modifier WHERE modifier_id = '$mod[2]' ");

                            $raw_modifier = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}woosquare_modifier WHERE modifier_id = '$mod[2]'");
                         
                            foreach ($raw_modifier as $raw) {
                                $mod_ids = '';
                                if(!empty($raw->modifier_set_unique_id)){
                                    $mod_ids = $raw->modifier_set_unique_id;
                                }else{
                                    $mod_ids = $raw->modifier_id;
                                }
                                
                                if(empty($raw->modifier_set_unique_id)){
                                    $modifier_set_name = $raw->modifier_set_name . "_" . $raw->modifier_set_unique_id . "_" . $raw->modifier_id . "_" . $raw->modifier_public. "_" . $raw->modifier_version."_".$raw->modifier_slug."_add_modifier";
                                } else {
                                    $modifier_set_name = $raw->modifier_set_name . "_" . $raw->modifier_set_unique_id . "_" . $raw->modifier_id . "_" . $raw->modifier_public. "_" . $raw->modifier_version."_".$raw->modifier_slug."_modifier";
                                }
                                  
                            }
                              $modifier_result  =  $this->woo_square_plugin_sync_woo_modifier_to_square_modifier($modifier_set_name);       
                
                        }
                    }
                      unset($_SESSION["session_key_count"]);
                     unset($_SESSION["product_loop_id"]);
                }
				
				/* if(!$product_square_id){
					// not exist in square so check in woo this product already updated
					$product_square_id = get_post_meta($woocommerce_product->ID, 'square_id', true);
					if($product_square_id){
						$result = true;
					} else {
						// add as new product in square bcox not exist in woo 
						$result = $this->addProduct($woocommerce_product, $product_square_id);
					}
				} else {
					// update product in square which is exist through sku
					$result = $this->addProduct($woocommerce_product, $product_square_id);
				}
				 */

				//update square sync post meta 
				if ($result===TRUE) {
					update_post_meta($woocommerce_product->ID, 'is_square_sync', 1);
				}
				
				//log the process
				//check if response returned is bool or error response message
				$message = NULL;
				if (!is_bool($result)){
					$message = $result['message'];
					$result = FALSE;
				}
				


			}
		}

		//3-get deleted categories/products
		$deletedElms = $this->getUnsynchronizedDeletedElements();
		$action = Helpers::ACTION_DELETE;
		foreach ($deletedElms as $delElement){

			if ($delElement->square_id) {

				if($delElement->target_type == Helpers::TARGET_TYPE_CATEGORY){     //category
					$result = $this->deleteCategory($delElement->square_id);
				}elseif($delElement->target_type == Helpers::TARGET_TYPE_PRODUCT){ //product                                                       //product
					if(get_option('disable_auto_delete') != 1){
						$result = $this->deleteProductOrGet($delElement->square_id,"DELETE");
					}
				}

				//delete category from plugin delete table
				if ($result===TRUE) {
					global $wpdb;
					$wpdb->delete($wpdb->prefix . WOO_SQUARE_TABLE_DELETED_DATA, ['square_id' => $delElement->square_id]
					);
				}
				//log the process
				//check if response returned is bool or error response message
				$message = NULL;
				if (!is_bool($result)){
					$message = $result['message'];
					$result = FALSE;
				}
+				Helpers::sync_db_log(
						$action,
						date("Y-m-d H:i:s"),
						$syncType,
						$syncDirection,
						$delElement->target_id,
						$delElement->target_type,
						$result?Helpers::TARGET_STATUS_SUCCESS:Helpers::TARGET_STATUS_FAILURE,
						$logId,
						$delElement->name,
						$delElement->square_id,
						$message
				);
			}
		}

	}



   public function woo_square_plugin_sync_woo_modifier_to_square_modifier($product_id )
    {

        global $wpdb;
        $modifier_check = (explode("_", $product_id));
        $modifier_checker = $wpdb->get_row(("SELECT * FROM " . $wpdb->prefix . "woosquare_modifier WHERE modifier_id = '$modifier_check[2]'"));


        if(empty($modifier_checker->modifier_set_unique_id)) {
            if (strpos($product_id, 'add_modifier')) {

                //create
                $modifier_name = (explode("_", $product_id));
                $modifier_set_name = str_replace('-', ' ', $modifier_name[5]);

                if ($modifier_name[3] == 1) {
                    $selected_type = 'MULTIPLE';
                } else {
                    $selected_type = 'SINGLE';
                }

                $dynamic_arr = array();
                global $wpdb;


                if (!empty($modifier_name[2]) && !empty($modifier_set_name)) {
                    $texonomy = 'pm_' . strtolower(str_replace(' ', '-', $modifier_set_name)) . "_" . ($modifier_name[2]);


                    $term_query = $wpdb->get_results(("SELECT term_id FROM " . $wpdb->prefix . "term_taxonomy WHERE taxonomy = '$texonomy'"));

                    if (!empty($term_query)) {
                        $keyy = 0;
                        foreach ($term_query as $key => $term) {

                            $object = get_term_by('id', $term->term_id, $texonomy);
                            $amount = get_term_meta($object->term_id, 'term_meta_price', true) * 100;

                            if (empty($object->description)) {
                                if (!empty($object->name)) {
                                    $dynamic_arr[$key] = (object)array(
                                        'type' => "MODIFIER",
                                        'id' => '#' . rand(),
                                        'modifier_data' => (object)array(
                                            'name' => $object->name,
                                            //'ordinal' => $keyy,
                                            'price_money' => (object)array(
                                                'amount' => (int)$amount,
                                                'currency' => get_option('woocommerce_currency'),
                                            )
                                        ),
                                    );
                                }

                            } else {

                                $dynamic_arr[$key] = (object)array(
                                    'type' => "MODIFIER",
                                    'id' => '#' . rand(),
                                );

                            }

                            $keyy++;
                        }
                    } else {

                        $dynamic_arr[0] = (object)array(
                            'type' => "MODIFIER",
                            'id' => '#' . rand(),
                        );

                    }


                    $data = array();
                    $data['idempotency_key'] = uniqid();
                    $data['object'] = (object)array(
                        'type' => 'MODIFIER_LIST',
                        'id' => '#' . rand(),
                        'modifier_list_data' => (object)array(
                            'name' => $modifier_checker->modifier_set_name,
                            //'ordinal' => 1,
                            'selection_type' => $selected_type,
                            'modifiers' => $dynamic_arr

                        ),
                    );

                }


                $tquery = $wpdb->get_results(("SELECT modifier_set_unique_id FROM " . $wpdb->prefix . "woosquare_modifier WHERE modifier_id = '$modifier_name[2]'"));
                if (empty($tquery->modifier_set_unique_id)) {
                    $data_json = json_encode($data);
                    $url = $this->square->getSquareV2URL() . "catalog/object";
                    $result = wp_remote_post($url, array(
                        'method' => 'POST',
                        'headers' => array(
                            'Authorization' => 'Bearer ' . $this->square->getAccessToken(),
                            'Content-Type' => 'application/json',
                            'Content-Length' => strlen($data_json)
                        ),
                        'httpversion' => '1.0',
                        'sslverify' => true,
                        'body' => $data_json
                    ));


                    if ($result['response']['code'] == '200' && $result['response']['message'] == 'OK') {

                        $result = json_decode($result['body'], true);


                        if ($result['catalog_object']['type'] == 'MODIFIER_LIST') {


                            foreach ($result['catalog_object']['modifier_list_data'] as $keyy => $modifier) {


                                foreach ($result['id_mappings'] as $key => $map_id) {


                                    foreach ($result['catalog_object']['modifier_list_data']['modifiers'] as $kk => $mod) {


                                        if ($map_id['object_id'] == $result['catalog_object']['id']) {


                                            $modifier_name = $result['catalog_object']['modifier_list_data']['name'];
                                            global $wpdb;
                                            $modifier_id = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "woosquare_modifier WHERE modifier_set_name ='$modifier_name' OR modifier_slug = '$modifier_name'  AND modifier_set_unique_id IS NULL");

                                            if (!empty($modifier_id->modifier_id) && !empty($modifier_id->modifier_set_name) && empty($modifier_id->modifier_set_unique_id) && empty($modifier_id->modifier_version)) {
                                                $format = array('%s', '%d');
                                                $data = array(
                                                    'modifier_set_unique_id' => $result['catalog_object']['id'],
                                                    'modifier_version' => $result['catalog_object']['version'],

                                                );

                                                $wpdb->update($wpdb->prefix . 'woosquare_modifier', $data, array('modifier_id' => $modifier_id->modifier_id), $format, array('%d'));
                                                session_start();
                                                $_SESSION['modifier_id'] = $modifier_id->modifier_id;
                                                $_SESSION['modifier_slug'] = $modifier_id->modifier_slug;
                                            }

                                        }


                                        if ($map_id['object_id'] == $mod['id']) {
                                            global $wpdb;
                                            if (!empty($_SESSION['modifier_slug']) && !empty($_SESSION['modifier_id'])) {

                                                //new code

                                                $texonomy = 'pm_' . strtolower(str_replace(' ', '-', $_SESSION['modifier_slug'])) . "_" . ($_SESSION['modifier_id']);
                                                $term_query = $wpdb->get_results(("SELECT * FROM " . $wpdb->prefix . "term_taxonomy WHERE taxonomy = '$texonomy'"));


                                                foreach ($term_query as $kgs => $term) {

                                                    $midd = $result['catalog_object']['modifier_list_data']['modifiers'][$kgs]['id'];

                                                    $wpdb->query($wpdb->prepare("UPDATE " . $wpdb->prefix . "term_taxonomy SET description='$midd' WHERE  term_id= '$term->term_id'"));
                                                    update_term_meta($term->term_id, 'term_meta_version', sanitize_text_field($mod['version']));

                                                }

                                            }

                                        }

                                    }

                                }

                            }

                        }


                        if (!empty($_SESSION["productid"])) {

                            $square_id = get_post_meta($_SESSION["productid"], 'square_id', true);

                            $modifier_value = get_post_meta($_SESSION["productid"], 'product_modifier_group_name', true);

                            if (!empty($modifier_value)) {
                                $kkey = 0;
                                $mod_array = array();
                                foreach ($modifier_value as $keyy => $mod) {


                                    $mod = (explode("_", $mod));


                                    if (!empty($mod)) {
                                        global $wpdb;
                                        $rcount = $wpdb->get_var("SELECT modifier_set_unique_id FROM " . $wpdb->prefix . "woosquare_modifier WHERE modifier_id = '$mod[2]' ");
                                        $mod_array[$keyy] = $rcount;

                                    }
                                }

                                $data = array('item_ids' => array(
                                    $square_id
                                ),
                                    'modifier_lists_to_enable' => $mod_array
                                );

                                $data_json = json_encode($data);
                                $url = $this->square->getSquareV2URL() . "catalog/update-item-modifier-lists";
                                $result = wp_remote_post($url, array(
                                    'method' => 'POST',
                                    'headers' => array(
                                        'Authorization' => 'Bearer ' . $this->square->getAccessToken(),
                                        'Content-Type' => 'application/json',
                                        'Content-Length' => strlen($data_json)
                                    ),
                                    'httpversion' => '1.0',
                                    'sslverify' => true,
                                    'body' => $data_json
                                ));

                                if ($result['response']['code'] == '200' && $result['response']['message'] == 'OK') {
                                    update_post_meta($_SESSION["productid"], 'product_sync_square_id' . $_SESSION["productid"], $mod[2]);
                                }
                            }
                        }
                    }
                }
            }
        } else {
            if (strpos($product_id, '_modifier')) {

                global $wpdb;
                $modifier_name = (explode("_", $product_id));
                 $modifier_checker = $wpdb->get_row(("SELECT * FROM " . $wpdb->prefix . "woosquare_modifier WHERE modifier_id = '$modifier_name[2]'"));
                $modifier_set_name = str_replace('-', ' ', $modifier_name[5]);
                $mod_name = str_replace('-', ' ', $modifier_name[0]);
                
                    $url = esc_url("https://connect.squareup.com/v2/catalog/list");
                      $headers = array(
                    'Authorization' => 'Bearer ' . $this->square->getAccessToken(), // Use verbose mode in cURL to determine the format you want for this header
                    'Content-Type' => 'application/json',
		        	'types' => 'MODIFIER_LIST',
                );


                $method = "GET";
	        	$args = array('types' => 'MODIFIER_LIST');
                $woo_square_location_id = get_option('woo_square_location_id');
                $square = new Square(get_option('woo_square_access_token'), $woo_square_location_id, WOOSQU_PLUS_APPID);
          
		    	
	    	   if (get_option('_transient_timeout_' . $woo_square_location_id . 'modifier_transient_' . __FUNCTION__) > time()) {

                        $response = get_transient($woo_square_location_id . 'modifier_transient_' . __FUNCTION__);
		              	$modifier_object = json_decode($response['body'], true);

           } else {
				
            $response = $square->wp_remote_woosquare($url, $args, $method, $headers, $response);
			$modifier_object = json_decode($response['body'], true);
			if(!empty($modifier_object)){
				if(count($modifier_object) > 999){
					$interval = 300;
				} else {
					$interval = 0;
				}
			}
			set_transient( $woo_square_location_id.'modifier_transient_'.__FUNCTION__, $response, $interval );
            
  
        }
		    	
		    
                foreach($modifier_object as $mod_object) {
                        
                    if($mod_object['id'] == $modifier_name[1])    {
                  
                        
                if (!empty($modifier_name[1]) && !empty($modifier_set_name)) {


                   // $term_query_up = $wpdb->get_row(("SELECT * FROM " . $wpdb->prefix . "woosquare_modifier WHERE modifier_set_unique_id = '$modifier_name[1]'"));

                    if ($modifier_name[3] == 1) {
                        $selected_type = 'MULTIPLE';
                    } else {
                        $selected_type = 'SINGLE';
                    }

                    //$dynamic_arr = array();

                    $texonomy = 'pm_' . strtolower(str_replace(' ', '-', $modifier_name[5])) . "_" . ($modifier_name[2]);


                    $term_query = $wpdb->get_results(("SELECT term_id FROM " . $wpdb->prefix . "term_taxonomy WHERE taxonomy = '$texonomy'"));

                    if (!empty($term_query)) {

                        $keyy = 0;
                        $dynamic_arr = array();
                        foreach ($term_query as $key => $term) {
                            
                        $object = get_term_by('id', $term->term_id, $texonomy);
                         
			        	 $amount = get_term_meta($object->term_id, 'term_meta_price', true) * 100;

                            $version = get_term_meta($object->term_id, 'term_meta_version', true);

                        foreach($mod_object['modifier_list_data']['modifiers'] as $term_obj){
                            

                           if($term_obj['id'] == $object->description){
                           
                            if (!empty($object->description)) {

                                if (!empty($object->name)) {

                                    $dynamic_arr[$key] = (object)array(

                                        'type' => "MODIFIER",
                                        'id' => $object->description,
                                        //'version' => (int)$modifier_name[4],
                                        'version' => $term_obj['version'],
                                        'modifier_data' => (object)array(
                                            'name' => $object->name,
                                            //'ordinal' => $keyy,
                                            'price_money' => (object)array(
                                                'amount' => (int)$amount,
                                                'currency' => get_option('woocommerce_currency'),
                                            )
                                        ),
                                    );

                                } else {

                                    $dynamic_arr[$key] = (object)array(
                                        'type' => "MODIFIER",
                                        'id' => $object->description,
                                    );

                                }

                              }  
                           }
                          
                          }
                       if (empty($object->description)) {

                                if (!empty($object->name)) {
                                    
                                    $dynamic_arr[$key] = (object)array(

                                        'type' => "MODIFIER",
                                            'id' => '#' . rand(),
                                        //'version' => (int)$modifier_name[4],
                                       // 'version' => $term_obj['version'],
                                        'modifier_data' => (object)array(
                                            'name' => $object->name,
                                            //'ordinal' => $keyy,
                                            'price_money' => (object)array(
                                                'amount' => (int)$amount,
                                                'currency' => get_option('woocommerce_currency'),
                                            )
                                        ),
                                    );

                                }      
                        }
                        
                        }$keyy++;
                    }

                    $data = array();
                    $data['idempotency_key'] = uniqid();
                    $data['object'] = (object)array(
                        'type' => 'MODIFIER_LIST',
                        'id' => $modifier_name[1],
                        'version' => $mod_object['version'],
                        //	'version' => (int)$version,
                        'modifier_list_data' => (object)array(
                            'name' => $modifier_checker->modifier_set_name,
                            //'ordinal' => 1,
                            'selection_type' => $selected_type,
                            'modifiers' => $dynamic_arr

                        ),

                    );


                }
                    }
                }

        
                $data_json = json_encode($data);
                $url = $this->square->getSquareV2URL() . "catalog/object";
                $result = wp_remote_post($url, array(
                    'method' => 'POST',
                    'headers' => array(
                        'Authorization' => 'Bearer ' . $this->square->getAccessToken(),
                        'Content-Type' => 'application/json',
                        'Content-Length' => strlen($data_json)
                    ),
                    'httpversion' => '1.0',
                    'sslverify' => true,
                    'body' => $data_json
                ));

 
                if ($result['response']['code'] == '200' && $result['response']['message'] == 'OK') {

                    $result = json_decode($result['body'], true);

                    if ($result['catalog_object']['type'] == 'MODIFIER_LIST') {

                        foreach ($result['catalog_object']['modifier_list_data'] as $keyy => $modifier) {

                            $modifier_name = $result['catalog_object']['modifier_list_data']['name'];
                            $mod_id = $result['catalog_object']['id'];
                            global $wpdb;
                            $modifier_id = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "woosquare_modifier WHERE  modifier_set_unique_id = '$mod_id'");

                            if (!empty($modifier_id->modifier_id) && !empty($modifier_id->modifier_set_name) && !empty($modifier_id->modifier_set_unique_id) && !empty($modifier_id->modifier_version)) {

                                $mod_version = $result['catalog_object']['version'];
                                $wpdb->query($wpdb->prepare("UPDATE " . $wpdb->prefix . "woosquare_modifier SET modifier_version='$mod_version' WHERE  modifier_id= '$modifier_id->modifier_id'"));
                                session_start();
                                $_SESSION['modifier_id'] = $modifier_id->modifier_id;
                                $_SESSION['modifier_slug'] = $modifier_id->modifier_slug;


                            }


                            foreach ($modifier as $mod) {

                                global $wpdb;
                                if (!empty($_SESSION['modifier_slug']) && !empty($_SESSION['modifier_id'])) {
                                    $texonomy = 'pm_' . strtolower(str_replace(' ', '-', $_SESSION['modifier_slug'])) . "_" . ($_SESSION['modifier_id']);
                                    $term_query = $wpdb->get_results(("SELECT * FROM " . $wpdb->prefix . "term_taxonomy WHERE taxonomy = '$texonomy'"));
                                    foreach ($term_query as $kgs => $term) {
                                          if(empty($term->description)){
                                           $midd = $result['catalog_object']['modifier_list_data']['modifiers'][$kgs]['id'];
                                           $wpdb->query($wpdb->prepare("UPDATE " . $wpdb->prefix . "term_taxonomy SET description='$midd' WHERE  term_id= '$term->term_id'"));
                                        }
                                        update_term_meta($term->term_id, 'term_meta_version', sanitize_text_field($mod['version']));

                                    }

                                }

                            }

                        }

                    }
                    
                if (!empty($_SESSION["productid"])) {

                        $square_id = get_post_meta($_SESSION["productid"], 'square_id', true);

                        $modifier_value = get_post_meta($_SESSION["productid"], 'product_modifier_group_name', true);
                        //	$mod_id = preg_replace('/[^0-9]/', '', $modifier_value);

                        if (!empty($modifier_value)) {
                            $kkey = 0;
                            $mod_array = array();
                            foreach ($modifier_value as $keyy => $mod) {


                                $mod = (explode("_", $mod));


                                if (!empty($mod)) {
                                    global $wpdb;
                                    $rcount = $wpdb->get_var("SELECT modifier_set_unique_id FROM " . $wpdb->prefix . "woosquare_modifier WHERE modifier_id = '$mod[2]' ");
                                    $mod_array[$keyy] = $rcount;

                                }
                            }

                            $data = array('item_ids' => array(
                                $square_id
                            ),
                                'modifier_lists_to_enable' => $mod_array
                            );

                            $data_json = json_encode($data);
                            $url = $this->square->getSquareV2URL() . "catalog/update-item-modifier-lists";
                            $result = wp_remote_post($url, array(
                                'method' => 'POST',
                                'headers' => array(
                                    'Authorization' => 'Bearer ' . $this->square->getAccessToken(),
                                    'Content-Type' => 'application/json',
                                    'Content-Length' => strlen($data_json)
                                ),
                                'httpversion' => '1.0',
                                'sslverify' => true,
                                'body' => $data_json
                            ));
             
                            if ($result['response']['code'] == '200' && $result['response']['message'] == 'OK') {

                                update_post_meta($_SESSION["productid"], 'product_sync_square_id' . $_SESSION["productid"], $mod[2]);
                            }

                        }
                    }
                }               
            }
        }
    }


	/*
    * Add new category to Square and return the returned id from Square
    */

	public function addCategory($category) {

        $token         = get_option( 'woo_square_access_token' );
        $location_id = get_option('woo_square_location_id');
        $end_point = 'squareup';
        $woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
        if(@$woocommerce_square_plus_settings['enable_sandbox'] == 'yes'){
            $token = $woocommerce_square_plus_settings['sandbox_access_token'];
            $location_id = $woocommerce_square_plus_settings['sandbox_location_id'];
            $end_point = 'squareupsandbox';
        }

		$cat_json = (array(
			'idempotency_key' => uniqid(),
			'object' => array(
					'id' => '#'.$category->name,
					'type' => 'CATEGORY',
					'category_data' => array(
						'name' => $category->name
					),
				)
			)
		);
		

       $url = "https://connect.".$end_point.".com/v2/catalog/object";

	   $method = "POST";
	   $square = new Square(get_option('woo_square_access_token'), get_option('woo_square_location_id'),WOOSQU_PLUS_APPID);
	   $headers = array(
		'Authorization' => 'Bearer '.$token, // Use verbose mode in cURL to determine the format you want for this header
		'cache-control'  => 'no-cache',
		'Content-Type'  => 'application/json'
	);

	    $response = array();
		$response = $square->wp_remote_woosquare($url,$cat_json,$method,$headers,$response);

		$objectAddCategory=  json_decode($response['body'], true);

		if( !empty($objectAddCategory['catalog_object'])){
			update_option('category_square_id_' . $category->term_id, $objectAddCategory['catalog_object']['id']);
			update_option('category_square_version_' . $category->term_id, $objectAddCategory['catalog_object']['version']);
			
		}
       
		return ($response['response']['code']==200)?true:$objectAddCategory;
	}

	/*
    * update category to Square and return the returned id from Square
    */

	public function editCategory($category,$category_square_id) {
		
		
		$category_square_version_ = get_option('category_square_version_' . $category->term_id);
		
		$cat_json = (array(
				'idempotency_key' => uniqid(),
				'object' => array(
					'id' => $category_square_id,
					'version' => (int) $category_square_version_,
					'type' => 'CATEGORY',
					'category_data' => array(
						'name' => $category->name,
					)
				)
			));
		
		$square = new Square(get_option('woo_square_access_token'), get_option('woo_square_location_id'),WOOSQU_PLUS_APPID);

		$token         = get_option( 'woo_square_access_token' );
        $location_id = get_option('woo_square_location_id');
        $end_point = 'squareup';
        $woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
        if(@$woocommerce_square_plus_settings['enable_sandbox'] == 'yes'){
            $token = $woocommerce_square_plus_settings['sandbox_access_token'];
            $location_id = $woocommerce_square_plus_settings['sandbox_location_id'];
            $end_point = 'squareupsandbox';
        }     
		$url = "https://connect.".$end_point.".com/v2/catalog/object";	

		$headers = array(
			'Authorization' => 'Bearer '.$token, // Use verbose mode in cURL to determine the format you want for this header
			'Content-Type'  => 'application/json'
		);

		$method = "POST";
      

        $response = array();
		$response = $square->wp_remote_woosquare($url,$cat_json,$method,$headers,$response);

		$objectEditCategory =  json_decode($response['body'], true);


		$resultobj = $objectEditCategory['catalog_object'];

		if( !empty($resultobj['id'])){ 
			update_option('category_square_id_' . $category->term_id, $resultobj['id']);
			update_option('category_square_version_' . $category->term_id, $resultobj['version']);
		}
		return ($response['response']['code']==200)?true:$resultobj;
	}

	/*
    * Delete Category from Square
    */

	public function deleteCategory($category_square_id) {

		$square = new Square(get_option('woo_square_access_token'), get_option('woo_square_location_id'),WOOSQU_PLUS_APPID);
		$url = "https://connect.squareup.com/v2/catalog/object/" . $category_square_id;
		$method = 'DELETE';
		$headers = array(
			'Authorization' => 'Bearer '.$this->square->getAccessToken()
		);
		$args = array();
		$response = array();
		$response = $square->wp_remote_woosquare($url,$args,$method,$headers,$response);
		$objectResponse =  json_decode($response['body'], true);
        if($response['response']['code'] == 200 and $response['response']['message'] == 'OK'){
			return true;   
        } else {
			return $objectResponse;
        }
	}



	/*
	* Add new Product to Square
	*/

	public function addProduct($product, $product_square_id) {


		$data = array();
		$woo_square_location_id = get_option('woo_square_location_id');
        $token           = get_option( 'woo_square_access_token' );
        $end_point = 'squareup';

        $woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
        if(@$woocommerce_square_plus_settings['enable_sandbox'] == 'yes'){
            $token = $woocommerce_square_plus_settings['sandbox_access_token'];
            $woo_square_location_id = $woocommerce_square_plus_settings['sandbox_location_id'];
            $end_point = 'squareupsandbox';
        }
		$categories = get_the_terms($product, 'product_cat');
		if (!$categories)
		$categories = array();
		$category_square_id = null;
		$square = new Square($token, $woo_square_location_id,WOOSQU_PLUS_APPID);
		$SquareToWooSynchronizer = new SquareToWooSynchronizer($square);
		$squareCategories = $SquareToWooSynchronizer->getSquareCategories();
		//need to take version 
		$squcats = array();
		if(!empty($squareCategories)){
			foreach ($squareCategories as $square_category) {
				$squcats[] = $square_category->id;
			}
		}
		

		foreach ($categories as $category) {
			//check if category not added to Square .. then will add this category
			$catSquareId = get_option('category_square_id_' . $category->term_id);

			if (! $catSquareId or !in_array($catSquareId,$squcats)){
				$category_square_id = $this->addCategory($category);
				$catSquareId = get_option('category_square_id_' . $category->term_id);
			}
			
			
			$category_square_id = $catSquareId;
		}





		$productDetails = get_post_meta($product->ID);

		if ($product_square_id) {
			$data['id'] = $product_square_id->id;
		}
		$data['name'] = $product->post_title;
		if(get_option('html_sync_des') == "1"){
		    $data['description'] = $product->post_content;
	    } else {
	        $data['description'] = strip_tags($product->post_content);
        }
		
		 if(strlen($data['description']) >= 4095 ){
         
          $data['description'] = substr($data['description'],0,4096)  ;  
            
        }
		
		$data['category_id'] = $category_square_id;
		$data['visibility'] = ($product->post_status == "publish") ? "PUBLIC" : "PRIVATE";


		//check if there are attributes
		
		$_product = wc_get_product( $product->ID );


        if( $_product->is_type( 'variable' )  || $_product->is_type( 'booking' ) ) {   //Variable Product
			$product_variations = unserialize($productDetails['_product_attributes'][0]);
			
			foreach ($product_variations as $product_variation) {
				//check if there are variations with fees
				if ($product_variation['is_variation']) {
					
					$args = array(
					'post_parent' => $product->ID,
					'post_type' => 'product_variation');
					$child_products = get_children($args);

					$admin_msg = false;
					foreach ($child_products as $child_product) {
						$child_product_meta = get_post_meta($child_product->ID);
						

						$variation_name = $child_product_meta['attribute_'.strtolower($product_variation['name'])][0];
						if(empty($child_product_meta['_sku'][0])){
							//admin msg that variation sku empty not sync in sqaure
							$admin_msg = true;
						}
						if(empty($child_product_meta['_sku'][0])){
							//don't add product variaton that doesn't have SKU
							continue;
						}
						$data['variations'][$child_product_meta['_sku'][0]][] = array(
						'name' => $product_variation['name'].'['.$variation_name.']',
						'sku' => $child_product_meta['_sku'][0],
						'track_inventory' => ($child_product_meta['_manage_stock'][0] == "yes") ? true : false,
						'price_money' => array(
						"currency_code" => get_option('woocommerce_currency'),
						"amount" => $child_product_meta['_price'][0]
						)
						);
					}
					if($admin_msg){
						update_post_meta($product->ID, 'admin_notice_square', 'Product unable to sync to Square due to Sku missing ');
					} else {
						delete_post_meta($product->ID, 'admin_notice_square', 'Product unable to sync to Square due to Sku missing ');
					}
				} else {
					
					$data['variations'][] = array(
					'name' => "Regular",
					'sku' => $productDetails['_sku'][0],
					'track_inventory' => ($productDetails['_manage_stock'][0] == "yes") ? true : false,
					'price_money' => array(
					"currency_code" => get_option('woocommerce_currency'),
					"amount" => $productDetails['_price'][0]
					)
					);
				}
			}
			//[color:red,size:smal] sample than below for multiple attributes and variations
			//color[black],size[smal] sample
			$setvariationformultupleattr = $data['variations'];
			foreach($setvariationformultupleattr as $mult_attr){
				$getingattrname = "";
				foreach($mult_attr as $attr){
					$getingattrnamedata = explode('[',$attr['name']);
					$getingattrval = explode(']',$getingattrnamedata[1]);
					$getingattrname .= str_replace('pa_','',$getingattrnamedata[0]).'['.$getingattrval[0].'],';
				}
				$getingattrname = rtrim($getingattrname,',');
				$datavariations[] = array(
				'name' => $getingattrname,
				'sku' => $attr['sku'],
				'track_inventory' => $attr['track_inventory'],
				'price_money' => array(
				"currency_code" => get_option('woocommerce_currency'),
				"amount" =>  100 * $attr['price_money']['amount'],
				)
				);
			}
			$data['variations'] = array();
			$data['variations'] = $datavariations;
		} else if( $_product->is_type( 'simple' ) ) {   //Simple Product
			
			if(empty($productDetails['_sku'][0])){
				update_post_meta($product->ID, 'admin_notice_square', 'Product unable to sync to Square due to Sku missing ');
				//don't add product that doesn't have SKU
				return false;
			} else {
				delete_post_meta($product->ID, 'admin_notice_square', 'Product unable to sync to Square due to Sku missing ');
			}
        //check if there are attributes
		if(!empty($productDetails['_product_attributes'])){
        $product_variations = unserialize($productDetails['_product_attributes'][0]);
		
				
        // $product_variations = unserialize($productDetails['_product_attributes']);
		
		if(!empty($product_variations)){
			foreach($product_variations as $variations){
				$variat = explode('_',$variations['name']);
				if($variat[0] == 'pa'){
					$variatio = ( wc_get_product_terms( $product->ID, $variations['name'], array( 'fields' => 'names' ) ) );
					@$pa .= $variat[1].'['.implode('|',$variatio).'],';
				} else {
					@$pa .= $variations['name'].'['.$variations['value'].'],';
				}
					
			}
			$pa = rtrim($pa,',');
		} else {
			$pa = 'Regular';
		}

			$data['variations'][] = array(
			'name' => $pa,
			'sku' => $productDetails['_sku'][0],
			'track_inventory' => ($productDetails['_manage_stock'][0] == "yes") ? true : false,
			'price_money' => array(
			"currency_code" => get_option('woocommerce_currency'),
			"amount" => 100 * $productDetails['_price'][0]
			)
			);
		} else {
			$pa = 'Regular';
			$data['variations'][] = array(
				'name' => $pa,
				'sku' => $productDetails['_sku'][0],
				'track_inventory' => ($productDetails['_manage_stock'][0] == "yes") ? true : false,
				'price_money' => array(
				"currency_code" => get_option('woocommerce_currency'),
				"amount" => 100 * $productDetails['_price'][0]
			)
			);
			
		}
		}
		// Connect to Square to add this item
		
if(function_exists('Manage_stock_from_square_function')){

	$square = new Square($token, $woo_square_location_id,WOOSQU_PLUS_APPID);
    $url = "https://connect.".WC_SQUARE_STAGING_URL.".com/v1/".$woo_square_location_id."/inventory";
	$method = "GET";
	$headers = array(
		'Authorization' => 'Bearer '.$token, // Use verbose mode in cURL to determine the format you want for this header
		'cache-control'  => 'no-cache',
		'Content-Type'  => 'application/json'
	);
	
	$response = array();
		$response = $square->wp_remote_woosquare($url,$card_details,$method,$headers,$response);
	
	if ($response['response']['code'] == 200 and $response['response']['message'] == 'OK') {
	  $all_square_variation  = json_decode($response['body'], true);
	} 
}		

        $sync_on_add_edit = get_option('sync_on_add_edit');
		$woosquare_pro_edit_fields = get_option('woosquare_pro_edit_fields');
		$woo_square_sync_preference = get_option('woo_square_sync_preference');
		$woo_square_listsaved_products_wooco = get_option('woo_square_listsaved_products_wooco');
		$updateInventory = true;	
		$uploadImage = true;

/*
		if(empty($_SESSION)){
			if($woo_square_sync_preference == 0){
				if(!in_array($product->ID, $woo_square_listsaved_products_wooco)){
					return true;
				}
			}
		}*/

		 if ($product_square_id) {
			 // $exist_in_square = $this->deleteProductOrGet($product_square_id->id,"GET");
			$exist_in_square = $product_square_id;
			
			if(!empty($exist_in_square->id)){
				if(!empty($exist_in_square->variations) and !empty($data['variations'])){
					foreach($exist_in_square->variations as $variation_upd){
						
					
					foreach($data['variations'] as $variation_data){
						if($variation_upd->sku == $variation_data['sku']){
							
			
							$variation_ids[$variation_upd->sku] = $variation_upd->id;
							if(empty($_SESSION)){
								if($sync_on_add_edit == 1){
									if(!in_array("price", $woosquare_pro_edit_fields)){
										unset($variation_data['price_money']);
									}
								}
							}
							
							// $this->update_variation($product_square_id,$variation_upd['id'],json_encode($variation_data));
						}
						}
					}
				}
				$request = "PUT";
				$item_id = $product_square_id->id;
			} else {
				$request = "POST";
				$item_id = "#".$data['name'];
			}
		} else {
			$request = "POST";
			$item_id = "#".$data['name'];
		}
		
		
		if(empty($_SESSION)){
		if($sync_on_add_edit == 1){
			$woosquare_pro_edit_fields = get_option('woosquare_pro_edit_fields');
			
			if(!in_array("title", $woosquare_pro_edit_fields)){
				unset($data['name']);
			}
			if(!in_array("description", $woosquare_pro_edit_fields)){
				unset($data['description']);
			}
			if(!in_array("price", $woosquare_pro_edit_fields)){
				unset($data['variations'][0]['price_money']);
			}
			if(!in_array("stock", $woosquare_pro_edit_fields)){
				$updateInventory = false;
			}
			if(!in_array("category", $woosquare_pro_edit_fields)){
				unset($data['category_id']);
			}
			if(!in_array("pro_image", $woosquare_pro_edit_fields)){
				$uploadImage = false;
			}
		}
		}
		

		// $data_json = json_encode($data);
		
		$data_json = array();
		
		$forversion = get_post_meta($product->ID,'log_woosquare_update_items_response',true);
		
		$data_json['idempotency_key'] = uniqid();
		$data_json['object']['type'] = 'ITEM';
		
		
		$data_json['object']['id'] = $item_id;
		$data_json['object']['image_id'] = '';
		
		if(!empty($exist_in_square->version)){
			$data_json['object']['version'] = (int) $exist_in_square->version;
		} 
		$data_json['object']['item_data']['name'] = $data['name'];
		$data_json['object']['item_data']['product_type'] = 'REGULAR';
		$data_json['object']['item_data']['description'] = $data['description'];
		$data_json['object']['item_data']['visibility'] = $data['visibility'];
		$data_json['object']['item_data']['category_id'] = $data['category_id'];
		// $data_json['object']['item_data']['present_at_location_ids'] = array( $woo_square_location_id);
		// $data_json['object']['item_data']['present_at_all_locations'] = false;
		
		foreach($data['variations'] as $key => $variant) {
            $data_json['object']['item_data']['variations'][$key]['type'] = 'ITEM_VARIATION';

            if ($_product->is_type('booking')) {
                $data_json['object']['item_data']['variations'][$key]['id'] = '#' . $_product->get_sku();
            } else {
                if (!empty($variation_ids[$variant['sku']])) {
                    $data_json['object']['item_data']['variations'][$key]['id'] = $variation_ids[$variant['sku']];
                } else {
                    $data_json['object']['item_data']['variations'][$key]['id'] = '#' . $variant['sku'];
                }
            }

            if (!empty($exist_in_square->variations)) {
                foreach ($exist_in_square->variations as $variatversion) {
                    if ($variatversion->id == $variation_ids[$variant['sku']]) {
                        $data_json['object']['item_data']['variations'][$key]['version'] = (int)$variatversion->version;

                    }
                }
            }
            if ($_product->is_type('booking')) {
                $checkSKU = $_product->get_sku();
                $data_json['object']['item_data']['variations'][$key]['item_variation_data']['sku'] = $checkSKU;
                $data_json['object']['item_data']['variations'][$key]['item_variation_data']['price_money']['amount'] = 0;
                $data_json['object']['item_data']['variations'][$key]['item_variation_data']['price_money']['currency'] = $variant['price_money']['currency_code'];
                $data_json['object']['item_data']['variations'][$key]['item_variation_data']['pricing_type'] = 'FIXED_PRICING';
            } else {
                $data_json['object']['item_data']['variations'][$key]['item_variation_data']['name'] = $variant['name'];
                $data_json['object']['item_data']['variations'][$key]['item_variation_data']['sku'] = $variant['sku'];
                $data_json['object']['item_data']['variations'][$key]['item_variation_data']['track_inventory'] = $variant['track_inventory'];
                $data_json['object']['item_data']['variations'][$key]['item_variation_data']['price_money']['amount'] = (int)$variant['price_money']['amount'];
                $data_json['object']['item_data']['variations'][$key]['item_variation_data']['pricing_type'] = 'FIXED_PRICING';
                $data_json['object']['item_data']['variations'][$key]['item_variation_data']['price_money']['currency'] = $variant['price_money']['currency_code'];
            }
        }
		
		$data_json = ($data_json);



        $url = "https://connect.".WC_SQUARE_STAGING_URL.".com/v2/catalog/object";


		$headers = array(
			'Authorization' => 'Bearer '.$token,
			'Content-Type'  => 'application/json'
		);
		$method = "POST";
		$response = array();
		
		$response = $responsesync = $square->wp_remote_woosquare($url,$data_json,$method,$headers,$response);


		update_post_meta( $product->ID, 'log_woosquare_update_items_request', $data );
		
		$objectResponse =  json_decode($response['body'], true);
				
		if($response['response']['code'] != 200 and $response['response']['message'] != 'OK'){
			// some kind of an error happened
		
			update_post_meta( $product->ID, 'log_woosquare_update_items_response_error', $objectResponse );
			
			return $objectResponse;
		} else {

            if ($response['response']['code'] == 200) {
                update_post_meta($product->ID, 'log_woosquare_update_items_response', $objectResponse);
            }

            $response = $objectResponse['catalog_object'];

            // Update product id with square id
            if (isset($response['id'])) {
                update_post_meta($product->ID, 'square_id', $response['id']);
                //	update_post_meta($product->ID, 'square_v2_id', $response['id']);
                do_action('Manage_stock_from_square', $response['item_data']['variations'], $product->ID, @$all_square_variation);
                if ($request == "PUT") {
                    $square = new Square(get_option('woo_square_access_token'), $woo_square_location_id, WOOSQU_PLUS_APPID);
                    $synchronizer = new SquareToWooSynchronizer($square);

                    $squareInventory = $synchronizer->getSquareInventory($response['item_data']['variations']);
                    $inventorycount = count($response['item_data']['variations']);
                }

                // $squareInventorytmp = (array) $squareInventory;

                // Update product variations ids with square ids
                if (isset($child_products)) {

                    foreach ($child_products as $child_product) {
                        $cn = 1;
                        foreach ($response['item_data']['variations'] as $variation) {
                            $d = new DateTime();
                            $variation['updated_at'] = $d->format('Y-m-d\TH:i:s') . '.000Z';
                            $child_product_meta = get_post_meta($child_product->ID);

                            $variation_sku = $child_product_meta['_sku'][0];
                            if ($variation['item_variation_data']['sku'] == $variation_sku) {
                                update_post_meta($child_product->ID, 'variation_square_id', $variation['id']);
                                if ($updateInventory) {
                                    if ($child_product_meta['_manage_stock'][0] == "yes") {
                                        if (!empty($squareInventory->counts) and $request == "PUT") {
                                            foreach ($squareInventory->counts as $varid) {

                                                if ($varid->catalog_object_id == $variation['id']) {

                                                    if ($varid->quantity < $child_product_meta['_stock'][0]) {
                                                        $stock = $child_product_meta['_stock'][0] - $varid->quantity;
                                                        $adjtype = 'RECEIVE_STOCK';

                                                        $this->updateInventory($variation, $stock, $adjtype, $woo_square_location_id);
                                                    } else if ($varid->quantity > $child_product_meta['_stock'][0]) {
                                                        $adjtype = 'SALE';
                                                        $stock = $varid->quantity - $child_product_meta['_stock'][0];

                                                        $this->updateInventory($variation, $stock, $adjtype, $woo_square_location_id);
                                                    }
                                                    $matched_variants[] = $varid->catalog_object_id;
                                                } else {
                                                    $miss_matched_variants[] = $variation['id'];
                                                    $miss_matched_variantions[$variation['id']] = $variation;
                                                    $miss_matched_variantions[$variation['id']]['stock'] = $child_product_meta['_stock'][0];
                                                }
                                            }


                                            if ($inventorycount > count($squareInventory->counts) and $inventorycount == $cn) {
                                                $newly_variants = array_unique(array_diff($miss_matched_variants, $matched_variants));

                                                foreach ($newly_variants as $newvariat) {
                                                    $this->updateInventory($miss_matched_variantions[$newvariat], $miss_matched_variantions[$newvariat]['stock'], 'RECEIVE_STOCK', $woo_square_location_id);
                                                }

                                            }

                                        } else {
                                            //for first time update stock

                                            $this->updateInventory($variation, $child_product_meta['_stock'][0], 'RECEIVE_STOCK', $woo_square_location_id);
                                        }


                                    }
                                }
                            }
                            $cn++;
                        }
                    }
                } else {
                    //update simple product

                    foreach ($response['item_data']['variations'] as $variation) {

                        $d = new DateTime();
                        $variation['updated_at'] = $d->format('Y-m-d\TH:i:s') . '.000Z';
                        update_post_meta($product->ID, 'variation_square_id', $variation['id']);
                        $productDetails = get_post_meta($product->ID);
                        $product_obj = wc_get_product($product->ID);
                        $product_stock = $product_obj->get_stock_quantity();

                        if ($updateInventory) {
                            if ($productDetails['_manage_stock'][0] == "yes") {

                                if (!empty($squareInventory->counts) and $request == "PUT") {
                                    $varid = $squareInventory->counts;

                                    if ($varid[0]->catalog_object_id == $variation['id']) {
                                        if ($varid[0]->quantity < $product_stock) {
                                            $stock = $product_stock - $varid[0]->quantity;
                                            $adjtype = 'RECEIVE_STOCK';
                                            $this->updateInventory($variation, $stock, $adjtype, $woo_square_location_id);
                                        } else if ($varid[0]->quantity > $product_stock) {
                                            $adjtype = 'SALE';
                                            $stock = $varid[0]->quantity - $product_stock;
                                            $this->updateInventory($variation, $stock, $adjtype, $woo_square_location_id);
                                        }
                                    }


                                } else {
                                    $adjtype = 'RECEIVE_STOCK';

                                    $this->updateInventory($variation, $product_stock, $adjtype, $woo_square_location_id);
                                }


                            }
                        }
                    }
                }

                if ($uploadImage) {
                    if (has_post_thumbnail($product->ID)) {
                        $product_square_id = $response['id'];
                        $image_file = get_attached_file(get_post_thumbnail_id($product->ID));

                        $result = $this->uploadImage($product_square_id, $image_file, $product->ID);
                        //make the response equal image response to be logged in error
                        //message field
                        if ($result !== TRUE) {
                            $http_status == 400;
                            $response = $result;
                        }

                    }
                }
            }
            if ( $_product->is_type( 'booking' )) {
                return $responsesync;
            } else {
                return ($responsesync['response']['code'] == 200) ? true : $responsesync;
            }
        }
		
	}

	/*
    * update variation to square
    */

	public function update_variation($item_id,$variation_id,$POSTFIELDS){

		$square = new Square(get_option('woo_square_access_token'), get_option('woo_square_location_id'),WOOSQU_PLUS_APPID);
	 $url = $this->square->getSquareURL() . "/items/".$item_id."/variations/".$variation_id;
	 
	 $method = "PUT";
		
	 $headers = array(
		'Authorization' => 'Bearer '.$this->square->getAccessToken(), // Use verbose mode in cURL to determine the format you want for this header
		'cache-control'  => 'no-cache',
		'Content-Type'  => 'application/json'
	);

	$response = array();
	$response = $square->wp_remote_woosquare($url,$POSTFIELDS,$method,$headers,$response);
	$objectUpdateVariation =  json_decode($response['body'], true);
	if($response['response']['code'] == 200 and $response['response']['message'] == 'OK'){
     return true;
	} else {
		return $objectUpdateVariation;
	}
	
	}

	/*
    * Delete product from Square
    */

	public function deleteProductOrGet($product_square_id,$Req) {

		$square = new Square(get_option('woo_square_access_token'), get_option('woo_square_location_id'),WOOSQU_PLUS_APPID);

		$url =  "https://connect.".WC_SQUARE_STAGING_URL.".com/v2/catalog/object/".$product_square_id;

		$method = $Req;

		$headers = array(
			'Authorization' => 'Bearer '.$this->square->getAccessToken(), // Use verbose mode in cURL to determine the format you want for this header
		);
		$response = array();
		$args = array();
		$response = $square->wp_remote_woosquare($url,$args,$method,$headers,$response);

		$objectDeleteProductOrGet =  json_decode($response['body'], true);

		if($Req == 'GET'){
			return $objectDeleteProductOrGet;
		}else {
			return ($response['response']['code']==200)?true:$objectDeleteProductOrGet;
		}

	}

	/*
    * Upload image to Square
    */

	public function uploadImage($product_square_id, $image_file, $product_woo_id) {

		$image = file_get_contents( $image_file );

		$headers = array(
			'accept'         => 'application/json',
			'content-type'   => 'multipart/form-data; boundary="boundary"',
			'Square-Version' => '2019-05-08',
			'Authorization'  => 'Bearer '.$this->square->getAccessToken(),
		);

		$body  = '--boundary' . "\r\n";
		$body .= 'Content-Disposition: form-data; name="request"' . "\r\n";
		$body .= 'Content-Type: application/json' . "\r\n\r\n";

		$request = array(
			'idempotency_key' => uniqid(),
			'image'           => array(
				'type'       => 'IMAGE',
				'id'         => '#TEMP_ID',
				'image_data' => array(
					'caption' => '',
				),
			),
		);
		if ( $product_square_id ) {
			$request['object_id'] = $product_square_id;
		}
		$body .= json_encode( $request );
		$body .= "\r\n";
		$body .= '--boundary' . "\r\n";
		$body .= 'Content-Disposition: form-data; name="file"; filename="' . esc_attr( basename( $image_path ) ) . '"' . "\r\n";
		$body .= 'Content-Type: image/jpeg' . "\r\n\r\n";
		$body .= $image . "\r\n";
		$body .= '--boundary--';
		$url = 'https://connect.squareup.com/v2/catalog/images';
		$responses = wp_remote_post(
			$url,
			array(
				'headers' => $headers,
				'body'    => $body,
			)
		);
		$response = json_decode($responses['body'], true);
		curl_close($ch);
		if (isset($response['image']['id'])){
			update_post_meta($product_woo_id,'square_master_img_id',$response['image']['image_data']['id']);
		}
		return $responses['response']['code']==200?TRUE:$response;
	}



	public function checkSkuInSquare($woocommerce_product, $squareItems) {
		/* get all products from woocommerce */
		$args = array(
				'post_type' => 'product_variation',
				'post_parent' => $woocommerce_product->ID,
				'posts_per_page' => 999999
		);
		$child_products = get_posts($args);

		if ($child_products) { //variable
			foreach ($child_products as $product) {
				$sku = get_post_meta($product->ID, '_sku', true);
				if ($sku) {
					if(isset($squareItems[$sku])){
						//value is the item id
						return $squareItems[$sku];

					}
				}
			}
			return false;
		} else { //simple
			$sku = get_post_meta($woocommerce_product->ID, '_sku', true);

			if (!$sku) {
				return false;
			}

			if(isset($squareItems[$sku])){
				//value is the item id
				return $squareItems[$sku];

			}
			return false;
		}
	}



	/*
    * Update Inventory with stock amount
    */

	public function updateInventory($variations, $stock, $adjustment_type = "RECEIVE_STOCK",$woo_square_location_id) {
		
		
		
		/* $data_string = '{
		"quantity_delta": ' . $stock . ',
		"adjustment_type": "' . $adjustment_type . '"
		}'; */
		
		
		$data_string = array (
		  'idempotency_key' => uniqid(),
		  'changes' => 
		  array (
			0 => 
			array (
			  'adjustment' => 
			  array (
				'catalog_object_id' => $variations['id'],
				'quantity' => (string) $stock,
				'location_id' => $woo_square_location_id,
				'occurred_at' => $variations['updated_at'], 
			  ),
			  'type' => 'ADJUSTMENT',
			),
		  ),
		);
		if($adjustment_type == 'RECEIVE_STOCK'){
			$data_string['changes'][0]['adjustment']['from_state'] = 'NONE';
			$data_string['changes'][0]['adjustment']['to_state'] = 'IN_STOCK';	
		} elseif($adjustment_type == 'SALE'){
			$data_string['changes'][0]['adjustment']['from_state'] = 'IN_STOCK';
			$data_string['changes'][0]['adjustment']['to_state'] = 'SOLD';	
		}
		
		$data_string = ($data_string);
		$square = new Square(get_option('woo_square_access_token'), get_option('woo_square_location_id'),WOOSQU_PLUS_APPID);
		$method = "POST";
		$url = "https://connect.".WC_SQUARE_STAGING_URL.".com/v2/inventory/batch-change";
        $headers = array(
			'Authorization' => 'Bearer '.$this->square->getAccessToken(), // Use verbose mode in cURL to determine the format you want for this header
			'Content-Type'  => 'application/json'
		);
		$response = array();
		$response = $square->wp_remote_woosquare($url,$data_string,$method,$headers,$response);
		
		return $response;
	}


	/**
	 * Get unsynchronized categories having is_square_sync flag = 0 or
	 * doesn't have it
	 * @return object wpdb object having id and name and is_square_sync meta
	 *                value for each category
	 */
	public function getUnsynchronizedCategories(){

		global $wpdb;

		//1-get un-synchronized categories ( having is_square_sync = 0 or key not exists )
		$query = "
		SELECT tax.term_id AS term_id, term.name AS name, meta.option_value
		FROM {$wpdb->prefix}term_taxonomy as tax
		JOIN {$wpdb->prefix}terms as term ON (tax.term_id = term.term_id)
		LEFT JOIN {$wpdb->prefix}options AS meta ON (meta.option_name = concat('is_square_sync_',term.term_id))
		where tax.taxonomy = 'product_cat'
		AND ( (meta.option_value = '1') OR (meta.option_value is NULL) )
		GROUP BY tax.term_id";
		return $wpdb->get_results($query, OBJECT);
	}

	/**
	 * Get square ids of the given categories if found
	 * @global object $wpdb
	 * @param object $categories wpdb categories object
	 * @return array Associative array with key: category id, value: category square id
	 */

	public function getCategoriesSquareIds($categories){


		if (empty($categories)){
			return array();
		}
		global $wpdb;

		//get square ids
		$optionKeys = ' (';
		//get category ids and add category_square_id_ to it to form its key in
		//the options table
		foreach ($categories as $category) {
			$optionKeys.= "'category_square_id_{$category->term_id}',";
		}

		$optionKeys = substr($optionKeys, 0, strlen($optionKeys) - 1);
		$optionKeys .= " ) ";

		$categoriesSquareIdsQuery = "
			SELECT option_name, option_value
			FROM {$wpdb->prefix}options 
			WHERE option_name in {$optionKeys}";

		$results = $wpdb->get_results($categoriesSquareIdsQuery, OBJECT);

		$squareCategories = [];

		//item with square id
		foreach ($results as $row) {

			//get id from string
			preg_match('#category_square_id_(\d+)#is', $row->option_name, $matches);
			if (!isset($matches[1])) {
				continue;
			}
			//add square id to array
			$squareCategories[$matches[1]] = $row->option_value;

		}
		return $squareCategories;

	}


	/**
	 * get the un-syncronized products which have is_square_sync = 0 or
	 * key not exists
	 * @global object $wpdb
	 * @return object wpdb object having id and name and is_square_sync meta
	 *                value for each product
	 */
	public function getUnsynchronizedProducts(){

		global  $wpdb;
		$query = "
		SELECT *
		FROM {$wpdb->prefix}posts AS posts
		LEFT JOIN {$wpdb->prefix}postmeta AS meta ON (posts.ID = meta.post_id AND meta.meta_key = 'is_square_sync')
		where posts.post_type = 'product'
		AND posts.post_status = 'publish'
		AND ( (meta.meta_value = '0') OR (meta.meta_value = '1') OR (meta.meta_value is NULL) )
		GROUP BY posts.ID";

		return $wpdb->get_results($query, OBJECT);
	}

	/**
	 * Get square ids of the given products if found, optionaly return simple
	 * products ids that have empty sku's
	 * @global object $wpdb
	 * @param type $products  wpdb products object
	 * @param array $emptySkuSimpleProductsIds
	 * @return array Associative array with key: category id, value: category square id
	 */

	public function getProductsSquareIds($products, &$emptySkuSimpleProductsIds = []) {

		if (empty($products)){
			return array();
		}
		global $wpdb;

		//get square ids
		$ids = ' ( ';
		//get post ids
		foreach ($products as $product) {
			$ids.= $product->ID . ",";
		}

		$ids = substr($ids, 0, strlen($ids) - 1);
		$ids .= " ) ";

		$postsSquareIdsQuery = "
			SELECT post_id, meta_key, meta_value
			FROM {$wpdb->prefix}postmeta 
			WHERE post_id in {$ids}
			and meta_key in ('square_id', '_product_attributes','_sku')";

		$results = $wpdb->get_results($postsSquareIdsQuery, OBJECT);
		$squareIdsArray = $emptySkuArray = $emptyAttributesArray = [];

		//exclude simple products (empty _product_attributes) that have an empty sku
		foreach ($results as $row) {

			switch ($row->meta_key) {
				case '_sku':
					if (empty($row->meta_value)) {
						$emptySkuArray[] = $row->post_id;
					}
					break;

				case '_product_attributes':
					//check if empty attributes after unserialization
					$testvar = unserialize($row->meta_value);
					if (empty($testvar)) {
						$emptyAttributesArray[] = $row->post_id;
					}
					break;

				case 'square_id':
					//put all square_ids in asociative array with key= post_id
					$squareIdsArray[$row->post_id] = $row->meta_value;
					break;
			}
		}

		//get array of products having both empty sku and empty _product_variations
		$emptySkuSimpleProductsIds = array_intersect($emptyAttributesArray, $emptySkuArray);
		return $squareIdsArray;
	}

	/**
	 * Get unsynchronized deleted categories and products from deleted data
	 * table
	 * @global object $wpdb
	 * @return object wpdb object
	 */
	public function getUnsynchronizedDeletedElements(){

		global $wpdb;
		$query = "SELECT * FROM " . $wpdb->prefix . WOO_SQUARE_TABLE_DELETED_DATA;
		$deleted_elms = $wpdb->get_results($query, OBJECT);
		return $deleted_elms;
	}

	/**
	 *
	 * @return object|false the square response object, false if error occurs
	 */

	/* public function getSquareItems(){

        //get all items from square
        $url = $this->square->getSquareURL() . '/items';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $this->square->getAccessToken(), 'Accept: application/json'));
        $response = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $squareItems = json_decode($response);
        if($http_status!==200){
            return false;
        }

        return $squareItems;

    } */

	
	/**
	 * Get simplified square items object key: sku id, value: item square id
	 * @param array
	 */
	public function simplifySquareItemsObject($squareItems){

		$squareItemsModified = [];
		foreach ($squareItems as $item) {
			foreach ($item->variations as $variation) {
				if (isset($variation->sku)){
					$squareItemsModified[$variation->sku]= $item;
				}
			}
		}
		return $squareItemsModified;
	}
}
