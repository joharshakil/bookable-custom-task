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

		require_once WOO_SQUARE_PLUGIN_PATH . '_inc/Helpers.class.php';
		$this->square = $square;

	}

	/*
	* Automatic Sync All products, categories from Woo-Commerce to Square
	*/

	public function syncFromWooToSquare() {

		Helpers::debug_log('info', "-------------------------------------------------------------------------------");
		Helpers::debug_log('info', "Start Auto Sync from Woo-commerce to Square");
		$syncType = Helpers::SYNC_TYPE_AUTOMATIC;
		$syncDirection = Helpers::SYNC_DIRECTION_WOO_TO_SQUARE;
		$logId = Helpers::sync_db_log(Helpers::ACTION_SYNC_START, date("Y-m-d H:i:s"), $syncType, $syncDirection);
		$squareItems = $this->getSquareItems();
		if($squareItems){
			$squareItems = $this->simplifySquareItemsObject($squareItems);
		}else{
			$squareItems= [];
		}        
		
		//1-get unsynchronized categories (add/update)
		Helpers::debug_log('info', "1- Synchronizing categories (add/update)");
		$categories = $this->getUnsynchronizedCategories();
		$squareCategories = $this->getCategoriesSquareIds($categories);
		foreach ($categories as $cat) {                
			$squareId= NULL;
			if (isset($squareCategories[$cat->term_id])) {      //update
				$squareId = $squareCategories[$cat->term_id];
				$result = $this->editCategory($cat, $squareId);
				$action= Helpers::ACTION_UPDATE;
			}else{                                         //add
				$result = $this->addCategory($cat);
				$action= Helpers::ACTION_ADD;
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

			//log category action
			Helpers::sync_db_log(
			$action,
			date("Y-m-d H:i:s"),
			$syncType,
			$syncDirection,
			$cat->term_id,
			Helpers::TARGET_TYPE_CATEGORY,
			$result?Helpers::TARGET_STATUS_SUCCESS:Helpers::TARGET_STATUS_FAILURE,
			$logId,
			$cat->name,
			$squareId,
			$message
			);
		}
		
		//2-get unsynchronized products (add/update)
		Helpers::debug_log('info', "2- Synchronizing products (add/update)");
		$unsyncProducts = $this->getUnsynchronizedProducts();
		$this->getProductsSquareIds($unsyncProducts, $excludedProducts);
		$productIds = array(0);
		foreach ($unsyncProducts as $product){
			if(in_array($product->ID, $excludedProducts)){
				continue;
			}
			$productIds[] = $product->ID;
		}      
		
		/* get all products from woocommerce */
		$args = array(
		'post_type' => 'product',
		'posts_per_page' => 999999,
		'include' => $productIds

		);
		$woocommerce_products = get_posts($args);
		// Update Square with products from WooCommerce
		if ($woocommerce_products) {
			foreach ($woocommerce_products as $woocommerce_product) {
				//check if woocommerce product sku is exists in square product sku
				$product_square_id = $this->checkSkuInSquare($woocommerce_product, $squareItems);
				$result = $this->addProduct($woocommerce_product, $product_square_id);
				//update square sync post meta 
				if ($result===TRUE) {
					update_post_meta($woocommerce_product->ID, 'is_square_sync', 1);

				} 
				$action= empty($product_square_id)?Helpers::ACTION_ADD:Helpers::ACTION_UPDATE;
				//log the process
				//check if response returned is bool or error response message
				$message = NULL;
				if (!is_bool($result)){
					$message = $result['message'];
					$result = FALSE;
				}
				Helpers::sync_db_log($action,
				date("Y-m-d H:i:s"), 
				$syncType,
				$syncDirection,
				$woocommerce_product->ID,
				Helpers::TARGET_TYPE_PRODUCT,
				$result?Helpers::TARGET_STATUS_SUCCESS:Helpers::TARGET_STATUS_FAILURE,
				$logId,
				$woocommerce_product->post_title,
				$product_square_id,
				$message
				);
			}
		}
		
		//3-get deleted categories/products
		Helpers::debug_log('info', "3- Synchronizing deleted items");
		$deletedElms = $this->getUnsynchronizedDeletedElements();
		$action = Helpers::ACTION_DELETE;
		foreach ($deletedElms as $delElement){
			if ($delElement->square_id) {
				if($delElement->target_type == Helpers::TARGET_TYPE_CATEGORY){     //category
					$result = $this->deleteCategory($delElement->square_id);
				}elseif($delElement->target_type == Helpers::TARGET_TYPE_PRODUCT){ //product                                                       //product
					$result = $this->deleteProductOrGet($delElement->square_id,"DELETE");
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
				Helpers::sync_db_log(

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
		Helpers::debug_log('info', "End Auto Sync from Woo-commerce to Square");
		Helpers::debug_log('info', "-------------------------------------------------------------------------------");

	}


	/*
	* Add new category to Square and return the returned id from Square
	*/

	public function addCategory($category) {
		
		$catarray = array('name' => $category->name);
		$cat_json = json_encode($catarray);
		Helpers::debug_log('info', "Adding category: ".$cat_json);
		$url = $this->square->getSquareURL() . "/categories";
		$headers = array(
			'Authorization' => 'Bearer '.$this->square->getAccessToken(), // Use verbose mode in cURL to determine the format you want for this header
			'Content-Length'  => strlen($cat_json),
			'Content-Type'  => 'application/json'
		);
		$method = "POST";
		$args = $catarray;
		$square = new Square(get_option('woo_square_access_token_free'), get_option('woo_square_location_id_free'));
		$response = $square->wp_remote_woosquare($url,$args,$method,$headers);
		Helpers::debug_log('info', "The response of adding new category curl request " . json_encode($response));
		if($response['response']['code'] == 200 and $response['response']['message'] == 'OK'){
			$result = json_decode($response['body'], true);
			if( !empty($result['id'])){
				update_option('category_square_id_' . $category->term_id, $result['id']);
			}
			return ($response['response']['code']==200)?true:$result;
		}

	}


	/*
	* update category to Square and return the returned id from Square
	*/

	public function editCategory($category,$category_square_id) {
		$cat_json = array('name' => $category->name);
		Helpers::debug_log('info', "Editing category: ".json_encode($cat_json));
			
		$url = $this->square->getSquareURL() . "/categories/" .$category_square_id;
		$headers = array(
			'Authorization' => 'Bearer '.$this->square->getAccessToken(),
			'Content-Length'  => strlen(json_encode($cat_json)),
			'Content-Type'  => 'application/json'
		);
		$method = "PUT";
		$args = $cat_json;
		$square = new Square(get_option('woo_square_access_token_free'), get_option('woo_square_location_id_free'));
		$response = $square->wp_remote_woosquare($url,$args,$method,$headers);
		Helpers::debug_log('info', "The response of edit category curl request " .  json_encode($response));
		if(!empty($response['response'])){
			if($response['response']['code'] == 200 and $response['response']['message'] == 'OK'){
				$response = json_decode($response['body'], true);
				if( !empty($response['id'])){
					update_option('category_square_id_' . $category->term_id,$response['id']);
					return true;
				} else {
					return $response; 
				}
			}else {
				return $response; 
			}
		} else {
			return $response; 
		}
		
	}

	/*
	* Delete Category from Square
	*/

	public function deleteCategory($category_square_id) {
		
		Helpers::debug_log('info', "Deleting category with square id: ".$category_square_id);
		$url = $this->square->getSquareURL() . "/categories/" . $category_square_id;
		$headers = array(
			'Authorization' => 'Bearer '.$this->square->getAccessToken(), // Use verbose mode in cURL to determine the format you want for this header
			'Content-Type'  => 'application/json;',
		);
		$method = "DELETE";
		$args = array('');
		$square = new Square(get_option('woo_square_access_token_free'), get_option('woo_square_location_id_free'));
		$response = $square->wp_remote_woosquare($url,$args,$method,$headers);
		Helpers::debug_log('info', "The response of deleteing category curl request ". json_encode($response));
		if($response['response']['code'] == 200 and $response['response']['message'] == 'OK'){
			return $response = json_decode($response['body'], true);
		}
		
	}
	
	/*
	* Add new Product to Square
	*/

	public function addProduct($product, $product_square_id) {       
		Helpers::debug_log('info', "Adding/Updating  product: {$product->post_title} [ id={$product->ID} ]");
		$data = array();

		$categories = get_the_terms($product, 'product_cat');
		if (!$categories)
		$categories = array();
		$category_square_id = null;
		$squareCategories = $this->getSquareCategories();
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
			$data['id'] = $product_square_id;

		}
		$data['name'] = $product->post_title;
		if(get_option('html_sync_des') == "1"){
		    $data['description'] = $product->post_content;
	    } else {
	        $data['description'] = strip_tags($product->post_content);
        }
		$data['category_id'] = $category_square_id;
		$data['visibility'] = ($product->post_status == "publish") ? "PUBLIC" : "PRIVATE";
		//check if there are attributes
		$product_variations = unserialize($productDetails['_product_attributes'][0]);
		$_product = wc_get_product( $product->ID );
		if( $_product->is_type( 'variable' )  ) {   //Variable Product
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
							Helpers::debug_log('info', "Variable product $product->ID ['$product->post_title'] variation '$variation_name' skipped from synch ( woo->square ): no SKU found");
							continue;
						}
						$data['variations'][$child_product_meta['_sku'][0]][] = array(
						'name' => $product_variation['name'].'['.$variation_name.']',
						'sku' => $child_product_meta['_sku'][0],
						'track_inventory' => ($child_product_meta['_manage_stock'][0] == "yes" or $child_product_meta['_manage_stock'][0] == "1") ? true : false,
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
					'track_inventory' => ($productDetails['_manage_stock'][0] == "yes" or $productDetails['_manage_stock'][0] == "1") ? true : false,
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
				Helpers::debug_log('info', "Simple product $product->ID ['$product->post_title'] skipped from synch ( woo->square ): no SKU found");
				return false;

			} else {
				delete_post_meta($product->ID, 'admin_notice_square', 'Product unable to sync to Square due to Sku missing ');
			}

        //check if there are attributes
        $product_variations = unserialize($productDetails['_product_attributes'][0]);
		
						
        // $product_variations = unserialize($productDetails['_product_attributes']);
		
		if(!empty($product_variations)){
			foreach($product_variations as $variations){
				$variat = explode('_',$variations['name']);
				if($variat[0] == 'pa'){
					$variatio = ( wc_get_product_terms( $product->ID, $variations['name'], array( 'fields' => 'names' ) ) );
					$pa .= $variat[1].'['.implode('|',$variatio).'],';
				} else {
					$pa .= $variations['name'].'['.preg_replace('/\s+/', '',($variations['value'])).'],';
				}
					
			}
			$pa = rtrim($pa,',');
		} else {
			$pa = 'Regular';
		}

			$data['variations'][] = array(
			'name' => $pa,
			'sku' => $productDetails['_sku'][0],
			'track_inventory' => ($productDetails['_manage_stock'][0] == "yes" or $productDetails['_manage_stock'][0] == "1") ? true : false,
			'price_money' => array(
			"currency_code" => get_option('woocommerce_currency'),
			"amount" => 100 * $productDetails['_price'][0]
			)
			);
		}
		// Connect to Square to add this item
			    
		 if ($product_square_id) {
			$exist_in_square = $this->deleteProductOrGet($product_square_id,"GET");
			if(!empty($exist_in_square['id'])){
				if(!empty($exist_in_square['variations'])){
					foreach($exist_in_square['variations'] as $variation_upd){
					foreach($data['variations'] as $variation_data){
						if($variation_upd['sku'] == $variation_data['sku']){
							$this->update_variation($product_square_id,$variation_upd['id'],json_encode($variation_data));
						}
						}
					}
				}
				$request = "PUT";
				$item_id = $exist_in_square['id'];
			} else {
				$request = "POST";
				$item_id = "";
			}
		} 
		
		$url = $this->square->getSquareURL() . "/items/".$item_id;
		$headers = array(
			'Authorization' => 'Bearer '.$this->square->getAccessToken(),
			'Content-Length'  => strlen(json_encode($data)),
			'Content-Type'  => 'application/json'
		);
		$method = $request;
		$args = $data;
		$square = new Square(get_option('woo_square_access_token_free'), get_option('woo_square_location_id_free'));
		$response = $square->wp_remote_woosquare($url,$args,$method,$headers);
		Helpers::debug_log('info', "The response of adding new product curl request " . json_encode($response));
		if(!empty($response['response'])){
			$http_status = $response['response']['code'];
			if($response['response']['code'] == 200 and $response['response']['message'] == 'OK'){
				$response = json_decode($response['body'], true);
			}
		}
		if (empty($response)) {
			Helpers::debug_log('error', "The response of adding new product curl request is empty");
		} else {
			// $response = json_decode($response, true);
			// curl_close($curl);
			// Update product id with square id
			if (isset($response['id'])){
				update_post_meta($product->ID, 'square_id', $response['id']);
		
				Helpers::debug_log('info', "calling Manage_stock_from_square for".$product->ID);
				do_action('Manage_stock_from_square',$response['variations'],$product->ID,$all_square_variation);
				if($request == "PUT"){
					$square = new Square(get_option('woo_square_access_token_free'), get_option('woo_square_location_id_free'));
					$synchronizer = new SquareToWooSynchronizer($square);
					$squareInventory = $synchronizer->getSquareInventory();
				}
				// Update product variations ids with square ids
				if (isset($child_products)) {
					Helpers::debug_log('info', "Updating product inventory for variable product (" . count($response['variations']) . " variations)");
					foreach ($child_products as $child_product) {
						foreach ($response['variations'] as $variation) {
							$child_product_meta = get_post_meta($child_product->ID);
							//$variation_name = (isset($child_product_meta['attribute_size'])) ? $child_product_meta['attribute_size'][0] : $child_product_meta['attribute_variation'][0];
							$variation_sku = $child_product_meta['_sku'][0];
							if ($variation['sku'] == $variation_sku) {
								update_post_meta($child_product->ID, 'variation_square_id', $variation['id']);
								if ($child_product_meta['_manage_stock'][0] == "yes" or $child_product_meta['_manage_stock'][0] == "1") {
									if(!empty($squareInventory) and $request == "PUT"){
										foreach($squareInventory as $varid){
											if($varid->variation_id == $variation['id']){
												if($varid->quantity_on_hand < $child_product_meta['_stock'][0]){
													$stock = $child_product_meta['_stock'][0]-$varid->quantity_on_hand;
													$adjtype = 'RECEIVE_STOCK';	
												} else if ($varid->quantity_on_hand > $child_product_meta['_stock'][0]){
													$adjtype = 'SALE';
													$stock = $child_product_meta['_stock'][0]-$varid->quantity_on_hand;
												}
											}
										}
										$this->updateInventory($variation['id'],$stock,$adjtype);
									} else {
										$this->updateInventory($variation['id'], $child_product_meta['_stock'][0]);
									}
								}
							}
						}
					}
				} else {
					Helpers::debug_log('info', "Updating product inventory for simple product (" . count($response['variations']) . " variations)");
						
					foreach ($response['variations'] as $variation) {
						update_post_meta($product->ID, 'variation_square_id', $variation['id']);
						$productDetails = get_post_meta($product->ID);
						if ($productDetails['_manage_stock'][0] == "yes" or $productDetails['_manage_stock'][0] == "1") {
							if(!empty($squareInventory) and $request == "PUT"){
								foreach($squareInventory as $varid){
									if($varid->variation_id == $variation['id']){
										if($varid->quantity_on_hand < $productDetails['_stock'][0]){
											$stock = $productDetails['_stock'][0]-$varid->quantity_on_hand;
											$adjtype = 'RECEIVE_STOCK';	
										} else if ($varid->quantity_on_hand > $productDetails['_stock'][0]){
											$adjtype = 'SALE';
											$stock = $productDetails['_stock'][0]-$varid->quantity_on_hand;
										}
									}
								}
								$this->updateInventory($variation['id'],$stock,$adjtype);
							} else {
								$this->updateInventory($variation['id'], $productDetails['_stock'][0]);
							}
						}
					}
				}


				if (has_post_thumbnail($product->ID)) {
					$product_square_id = $response['id'];
					$image_file = get_attached_file(get_post_thumbnail_id($product->ID));
					$result = $this->uploadImage($product_square_id, $image_file,$product->ID);
					//make the response equal image response to be logged in error
					//message field 
					if ($result!==TRUE) {
						@$http_status == 400;
						$response = $result;
					} 
				}
			}
			return ($http_status==200)?true:$response;
		}
	}

	/*
	* update variation to square
	*/

	public function update_variation($item_id,$variation_id,$POSTFIELDS){
		
		$url = $this->square->getSquareURL() . "/items/".$item_id."/variations/".$variation_id;
		$headers = array(
			'Authorization' => 'Bearer '.$this->square->getAccessToken(),
			'Content-Length'  => strlen($POSTFIELDS),
			'Content-Type'  => 'application/json'
		);
		$method = "PUT";
		$args = json_decode($POSTFIELDS);
		$square = new Square(get_option('woo_square_access_token_free'), get_option('woo_square_location_id_free'));
		$response = $square->wp_remote_woosquare($url,$args,$method,$headers);
		Helpers::debug_log('info', "The response of updating inventory curl request" . json_encode($response));
		if(!empty($response['response'])){
			if($response['response']['code'] == 200 and $response['response']['message'] == 'OK'){
				return $response = json_decode($response['body'], true);
			} else {
				Helpers::debug_log('error', "The response of updating inventory curl request !200 " .json_encode($response));
			} 
		} else {
			Helpers::debug_log('error', "The response of updating inventory curl request " .json_encode($response));
		} 
		
		
	}

	/*
	* Delete product from Square
	*/

	public function deleteProductOrGet($product_square_id,$Req) {
		
		Helpers::debug_log('info', "$Req  product with square_id = {$product_square_id}");
		$url = $this->square->getSquareURL() . "/items/" . $product_square_id;
		$headers = array(
			'Authorization' => 'Bearer '.$this->square->getAccessToken(), // Use verbose mode in cURL to determine the format you want for this header
			'Content-Type'  => 'application/json;',
		);
		$method = $Req;
		$args = array('');
		$square = new Square(get_option('woo_square_access_token_free'), get_option('woo_square_location_id_free'));
		$response = $square->wp_remote_woosquare($url,$args,$method,$headers);
		
		if(!empty($response['response'])){
			$http_status  = $response['response']['code'];
			if($response['response']['code'] == 200 and $response['response']['message'] == 'OK'){
				Helpers::debug_log('info', "The response of current location curl request" . json_encode($response));
				$responses = json_decode($response['body'], true);
				if($Req == 'GET'){
					return $responses;
				}else {
					return ($response['response']['code']==200)?true:$response;
				}
			} else {
				Helpers::debug_log('error', "The response of current deleteProductOrGet request " .json_encode($response));
			} 
		}  else {
			Helpers::debug_log('error', "The response of current deleteProductOrGet request " .json_encode($response));
		} 
		Helpers::debug_log('info', "The response of $Req product curl request {$response} CODE:[ {$http_status} ]");
		
	}
	
	/*
	* Upload image to Square
	*/


	public function uploadImage($product_square_id, $image_file, $product_woo_id) {
		
		$boundary = hash('sha256', uniqid('', true));
		
		$payload = '';
		$file_path = $image_file;
		$fileContents = file_get_contents( $file_path );
		$file_info = new finfo(FILEINFO_MIME_TYPE);
		$mime_type = $file_info->buffer($fileContents);
		if($fileContents) {
			// Upload the file
			if ( $file_path ) {
				$payload .= '--' . $boundary;
				$payload .= "\r\n";
				$payload .= 'Content-Disposition: form-data; name="image_data"; filename="' . basename( $file_path ) . '"' . "\r\n";
				$payload .= 'Content-Type: '. $mime_type . "\r\n";
				$payload .= "\r\n";
				$payload .= $fileContents;
				$payload .= "\r\n";
			}

			$payload .= '--' . $boundary . '--';
			$response = wp_remote_request( $this->square->getSquareURL() . '/items/' . $product_square_id . '/image', array(
				'body'    => $payload,
				'method' => 'POST',
				'headers' => array(
					'Authorization' => 'Bearer '.$this->square->getAccessToken(),
					'content-type' => 'multipart/form-data; boundary=' . $boundary
				),
			));
			
			if(!empty($response['response'])){
				if($response['response']['code'] == 200 and $response['response']['message'] == 'OK'){
					$response = json_decode($response['body'], true);
					update_post_meta($product_woo_id,'square_master_img_id',$response['url']);
					return $response;
				}
			}
			
		}
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
			Helpers::debug_log('info', "Product id: " . $woocommerce_product->ID . " is Variable Product");
			foreach ($child_products as $product) {
				$sku = get_post_meta($product->ID, '_sku', true);
				Helpers::debug_log('info', "checking exists of Woocommerce sku '" . $sku . "' on Square items");
				if ($sku) {
					if(isset($squareItems[$sku])){
						//value is the item id
						Helpers::debug_log('info', "SKU found, and the square product id: " . $squareItems[$sku]);
						return $squareItems[$sku];
						
					}
				}
			}
			Helpers::debug_log('info', "SKU Not found.");
			return false;
		} else { //simple
			Helpers::debug_log('info', "Product id: " . $woocommerce_product->ID . " is Simple Product");
			$sku = get_post_meta($woocommerce_product->ID, '_sku', true);
			Helpers::debug_log('info', "checking exists of Woocommerce sku '" . $sku . "' on Square items");
			if (!$sku) {
				return false;
			}
			if(isset($squareItems[$sku])){
				//value is the item id
				Helpers::debug_log('info', "SKU found. and the square product id: " . $squareItems[$sku]);
				return $squareItems[$sku];

			}
			Helpers::debug_log('info', "SKU Not found.");
			return false;
		}
	}
	
	
	
	/*
	* Update Inventory with stock amount
	*/

	public function updateInventory($variation_id, $stock, $adjustment_type = "RECEIVE_STOCK") {
		if(empty($stock)){
			$stock = 0;
		}
		$data_string = '{"quantity_delta":'.$stock.',"adjustment_type":"'.$adjustment_type.'"}';
		
		$url = $this->square->getSquareURL() . '/inventory/' . $variation_id;
		$headers = array(
			'Authorization' => 'Bearer '.$this->square->getAccessToken(), // Use verbose mode in cURL to determine the format you want for this header
			'Content-Length'  => strlen($data_string),
			'Content-Type'  => 'application/json'
		);
		$method = "POST";
		$args = json_decode($data_string);
		$square = new Square(get_option('woo_square_access_token_free'), get_option('woo_square_location_id_free'));
		$response = $square->wp_remote_woosquare($url,$args,$method,$headers);
		
		Helpers::debug_log('info', "The response of updating inventory curl request" . json_encode($response));
		if(!empty($response['response'])){
			if($response['response']['code'] == 200 and $response['response']['message'] == 'OK'){
				return $response = json_decode($response['body'], true);
			}  else {
				Helpers::debug_log('error', "The response of updating inventory curl request " .json_encode($response));
			} 
		} else {
			Helpers::debug_log('error', "The response of updating inventory curl request " .json_encode($response));
		} 
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
		GROUP BY posts.ID ORDER BY posts.post_title";
		
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
	public function getSquareItems(){
		/* get all items from square */
		
		$url = $this->square->getSquareURL() . '/items';
		$headers = array(
			'Authorization' => 'Bearer '.$this->square->getAccessToken(), // Use verbose mode in cURL to determine the format you want for this header
			'Content-Type'  => 'application/json;',
		);
		$method = "GET";
		$args = array('');
		$square = new Square(get_option('woo_square_access_token_free'), get_option('woo_square_location_id_free'));
		$response = $square->wp_remote_woosquare($url,$args,$method,$headers);
		if(!empty($response['response'])){
			if($response['response']['code'] == 200 and $response['response']['message'] == 'OK'){
				return $response = json_decode($response['body'], false);
			} else {
				Helpers::debug_log('error', "Error in getting all products curl request " .json_encode($response));
			} 
		}  else {
			Helpers::debug_log('error', "Error in getting all products curl request " .json_encode($response));
		} 
	}
	/**
	* Get all square categories
	* @return object|false the square response object, false if error occurs
	*/    
	public function getSquareCategories(){
		/* get all categories */
						
		$url = $this->square->getSquareURL() . '/categories';
		$headers = array(
			'Authorization' => 'Bearer '.$this->square->getAccessToken(), // Use verbose mode in cURL to determine the format you want for this header
			'Content-Type'  => 'application/json;',
		);
		$method = "GET";
		$args = array('');
		$square = new Square(get_option('woo_square_access_token_free'), get_option('woo_square_location_id_free'));
		$response = $square->wp_remote_woosquare($url,$args,$method,$headers);
		if(!empty($response['response'])){
			if($response['response']['code'] == 200 and $response['response']['message'] == 'OK'){
				return $response = json_decode($response['body'], false);
			} else {
				Helpers::debug_log('error', "Error in getting all categories curl request " . json_encode($response));
				return false;
			}
		} else {
			Helpers::debug_log('error', "Error in getting all categories curl request " . json_encode($response));
			return false;
		}
		// return $squareCategories;
	}
	
	/**
	* Get simplified square items object key: sku id, value: item square id
	* @param array

	*/
	public function simplifySquareItemsObject($squareItems){

		$squareItemsModified = [];
		foreach ($squareItems as $item) {
			foreach ($item->variations as $variation) {
				if (isset($variation->sku)){
					$squareItemsModified[$variation->sku]= $variation->item_id;
				}
			}
		}
		Helpers::debug_log('info', "The Simplified curl object" . json_encode($squareItemsModified));
		return $squareItemsModified;

	}
}

