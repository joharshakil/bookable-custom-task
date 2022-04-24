<?php

// don't load directly
if ( !defined('ABSPATH') )
    die('-1');


function checkSyncStartConditions(){

    if(!get_option('woo_square_access_token')){
        return "Invalid square access token";
    }

    if(get_option('woo_square_running_sync') && (time()-(int)get_option('woo_square_running_sync_time')) < (20*60) ){
        return 'There is another Synchronization process running. Please try again later. Or <a href="'. admin_url('admin.php?page=square-item-sync&terminate_sync=true').'" > terminate now </a>';
    }

    return TRUE;

}


//woo -> square
function woo_square_plugin_get_non_sync_woo_data() {

    $checkFlag = checkSyncStartConditions();
    if ($checkFlag !== TRUE){ die(json_encode(['error'=>$checkFlag])); }

    $square = new Square(get_option('woo_square_access_token'), get_option('woo_square_location_id'),WOOSQU_PLUS_APPID);
    $synchronizer = new WooToSquareSynchronizer($square);
    $SquareToWooSynchronizer = new SquareToWooSynchronizer($square);

    //for display
    $addProducts = $updateProducts = $deleteProducts = $addCategories
        = $updateCategories = $deleteCategories = [];
    //display all products in update
    $oneProductsUpdateCheckbox = FALSE;


    //1-get un-syncronized categories ( having is_square_sync = 0 or key not exists )
    $categories = $synchronizer->getUnsynchronizedCategories();
    $squareCategories = $synchronizer->getCategoriesSquareIds($categories);

    $targetCategories = $excludedProducts = [];

    //merge add and update categories
    foreach ($categories as $cat) {
        if (!isset($squareCategories[$cat->term_id])) {      //add
            $targetCategories[$cat->term_id]['action'] = 'add';
            $targetCategories[$cat->term_id]['square_id'] = NULL;
            $addCategories[] = [
                'woo_id'=> $cat->term_id,
                'checkbox_val'=> $cat->term_id,
                'name'=> $cat->name
            ];
        }else{                                          //update
            $targetCategories[$cat->term_id]['action'] = 'update';
            $targetCategories[$cat->term_id]['square_id'] = $squareCategories[$cat->term_id];
            $updateCategories[] = [
                'woo_id'=> $cat->term_id,
                'checkbox_val'=> $cat->term_id,
                'name'=> $cat->name
            ];
        }

        $targetCategories[$cat->term_id]['name'] = $cat->name;
    }
		$squareCategories = $SquareToWooSynchronizer->getSquareCategories();
		
		//check for new category that not exist in square.
		if(!empty($targetCategories)){
			foreach($targetCategories as $cats){
				if(!empty($cats['square_id'])){
					$woosyncsquid[] = $cats['square_id'];
				}
			}
			if(!empty($squareCategories)){
				foreach($squareCategories as $squcat){
					$squsync[] = $squcat->id;
				}
			}
		}
	
	if(!empty($woosyncsquid) and !empty($squsync)){
		foreach(array_diff($woosyncsquid, $squsync) as $unique){
			foreach($targetCategories as $ky =>$trcat){
				if($trcat['square_id'] == $unique){
					$targetCategories[$ky]['square_id'] = '';
					$targetCategories[$ky]['action'] = 'add';
				}
			}
		}
	}
	
    //2-get un-syncronized products ( having is_square_sync = 0 or key not exists )
    $products = $synchronizer->getUnsynchronizedProducts();
    $squarePoducts = $synchronizer->getProductsSquareIds($products, $excludedProducts);

    $targetProducts = [];
	
	
	$total = count( $products );
	session_start();
	if(empty( $_GET['page'] )){
		$_SESSION = [];
	}
	if($total > 999){
		$page = ! empty( $_GET['page'] ) ? (int) $_GET['page'] : 1;
		$total = count( $products ); //total items in array    
		$limit = 999; //per page    
		$totalPages = ceil( $total/ $limit ); //calculate total pages
		$page = max($page, 1); //get 1 page when $_GET['page'] <= 0
		$page = min($page, $totalPages); //get last page when $_GET['page'] > $totalPages
		$offset = ($page - 1) * $limit;
		if( $offset < 0 ) $offset = 0;

		$products = array_slice( $products, $offset, $limit );
	}
	
	
	
    //merge add and update items
    foreach ($products as $product) {

        //skip simple products with empty sku


        $product_id = $product->ID; // the ID of the product to check
        $_product = wc_get_product( $product_id );
        if( $_product->is_type( 'simple' ) ) {
            // do stuff for simple products
            $sku = get_post_meta( $product->ID , '_sku', true );
            if(empty($sku)){
                $sku_missin_inside_product[] = [
                    'woo_id'=> $product->ID,
                    'checkbox_val'=> $product->ID,
                    'name'=> $product->post_title,
                    'sku_missin_inside_product'=> 'sku_missin_inside_product'
                ];



            }
        } else if( $_product->is_type( 'variable' )  ) {
            $tickets = new WC_Product_Variable( $product_id );
            $variables = $tickets->get_available_variations();

            if(!empty($variables)){
                foreach($variables as $var_checkin){

                    if(empty($var_checkin['sku'])){
                        $sku_missin_inside_product[] = [
                            'woo_id'=> $product->ID,
                            'checkbox_val'=> $product->ID,
                            'name'=> $product->post_title.' variations of "'.$var_checkin['attributes']['attribute_var1'].'" sku missing kindly click here update it.',
                            'sku_missin_inside_product'=> 'sku_missin_inside_product'
                        ];
                        break;
                    }

                }
            }

            // do stuff for variable
        }


        if (in_array($product->ID, $excludedProducts)){
            continue;
        }


        $modifier_value = get_post_meta($product->ID, 'product_modifier_group_name', true);


        $modifier_set_name = array();

        if(!empty($modifier_value)) {


            if (!empty($modifier_value)) {
                $kkey = 0;

                foreach ($modifier_value as $mod) {


                    $mod = (explode("_", $mod));

                    if (!empty($mod[2])) {


                        global $wpdb;
                        $rcount = $wpdb->get_var("SELECT modifier_set_unique_id FROM " . $wpdb->prefix . "woosquare_modifier WHERE modifier_id = '$mod[2]' ");



                        //if(empty($rcount)){
                        $raw_modifier = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}woosquare_modifier WHERE modifier_id = '$mod[2]';");



                        foreach ($raw_modifier as $raw) {
                            $mod_ids = '';
                            if(!empty($raw->modifier_set_unique_id)){
                                $mod_ids = $raw->modifier_set_unique_id;
                            }else{
                                $mod_ids = $raw->modifier_id;
                            }



                            $modifier_set_name[$kkey] = $raw->modifier_set_name . "|" . $raw->modifier_set_unique_id . "|" . $raw->modifier_id . "|" . $raw->modifier_public. "|" . $raw->modifier_version."|".$raw->modifier_slug;
                            $kkey++;


                        }
                    }
                }
            }

        }else{
            $modifier_set_name = array();
        }


        if (isset($squarePoducts[$product->ID])) {     //update
            $targetProducts[$product->ID]['action'] = 'update';
            $targetProducts[$product->ID]['square_id'] = $squarePoducts[$product->ID];
            $updateProducts[] = [
                'woo_id'=> $product->ID,
                'checkbox_val'=> $product->ID,
                'name'=> $product->post_title,
                'modifier_set_name' =>   $modifier_set_name,
                'direction' => 'woo_to_square'
            ];

        }else{                                       //add
            $targetProducts[$product->ID]['action'] = 'add';
            $targetProducts[$product->ID]['square_id'] = NULL;
            $addProducts[] = [
                'woo_id'=> $product->ID,
                'checkbox_val'=> $product->ID,
                'name'=> $product->post_title,
                'modifier_set_name' => $modifier_set_name,
                'direction' => 'woo_to_square'
            ];
        }




        $targetProducts[$product->ID]['name'] = $product->post_title;


    }




    //3-get deleted elements failed to be synchronized
    $deletedElms = $synchronizer->getUnsynchronizedDeletedElements();

    //merge deleted items and categories with their corresponding arrays
    foreach ($deletedElms as $elm) {

        if ($elm->target_type == Helpers::TARGET_TYPE_PRODUCT) {   //PRODUCT
            $targetProducts[$elm->target_id]['square_id'] = $elm->square_id;
            $targetProducts[$elm->target_id]['action'] = 'delete';
            $targetProducts[$elm->target_id]['name'] = $elm->name;

            //for display
            $deleteProducts[] = [
                'woo_id'=> NULL,
                'checkbox_val'=> $elm->target_id,
                'name'=> $elm->name
            ];
        } else {                                                                  //CATEGORY
            $targetCategories[$elm->target_id]['square_id'] = $elm->square_id;
            $targetCategories[$elm->target_id]['action'] = 'delete';
            $targetCategories[$elm->target_id]['name'] = $elm->name;
            $deleteCategories[] = [
                'woo_id'=> NULL,
                'checkbox_val'=> $elm->target_id,
                'name'=> $elm->name
            ];
        }
    }


    //4-get all square items simplified

    $SquareToWooSynchronizer = new SquareToWooSynchronizer($square);
    $squareItems = $SquareToWooSynchronizer->getSquareItems();


    $squareItemsModified = [];
    if ($squareItems){
        $squareItemsModified = $synchronizer->simplifySquareItemsObject($squareItems);
    }
   

    //construct session array	
	if(!isset($_SESSION["woo_to_square"]["target_products"])){
		 $_SESSION["woo_to_square"]["target_products"] = [];
	}
	
	if(isset($_SESSION["woo_to_square"]["target_products"])){
		
		
		
		if(!empty($_SESSION["woo_to_square"]["target_products"])){
			foreach($_SESSION["woo_to_square"]["target_products"] as $kys => $ses){
				$targetProducts[$kys] = $_SESSION["woo_to_square"]["target_products"];
			}
		}
		
		$_SESSION["woo_to_square"]["target_products"] = $targetProducts;
	}
      
    $_SESSION["woo_to_square"]["target_categories"] = $targetCategories;
    //add simplified object to session
    $_SESSION["woo_to_square"]["suqare_items"] = $squareItemsModified;

    ob_start();
    include plugin_dir_path( dirname( __FILE__ ) ) . '../views/partials/pop-up.php';
    $data = ob_get_clean();
	if(empty($offset)){
		$offset = 0;
	}
     echo json_encode(array(
		'data' => $data ,
		'offset' => $offset ,
		'totalPages' => $totalPages ,
		'targetProducts' => ! empty( $_SESSION["woo_to_square"]["target_products"] ) ? count($_SESSION["woo_to_square"]["target_products"]) : '',
		'limit' => $limit 
		));
    die();
}



function woo_square_listsaved(){

    delete_option('woo_square_listsaved_products_'.$_REQUEST['saveto']);
    delete_option('woo_square_listsaved_categories_'.$_REQUEST['saveto']);
    if(!empty($_REQUEST['products'])){
        update_option('woo_square_listsaved_products_'.$_REQUEST['saveto'],json_decode(stripslashes($_REQUEST['products'])));
    }
    if(!empty($_REQUEST['categories'])){
        update_option('woo_square_listsaved_categories_'.$_REQUEST['saveto'],json_decode(stripslashes($_REQUEST['categories'])));
    }

    echo '1';
    die();
}
function woo_square_plugin_start_manual_woo_to_square_sync(){


    $checkFlag = checkSyncStartConditions();
    if ($checkFlag !== TRUE){ die($checkFlag); }

    update_option('woo_square_running_sync', 'manual');
    update_option('woo_square_running_sync_time', time());

    session_start();
    $_SESSION["woo_to_square"]["target_products"]['parent_id'] =
    $_SESSION["woo_to_square"]["target_categories"]['parent_id']
        = Helpers::sync_db_log(
        Helpers::ACTION_SYNC_START,
        date("Y-m-d H:i:s"),
        Helpers::SYNC_TYPE_MANUAL,
        Helpers::SYNC_DIRECTION_WOO_TO_SQUARE
    );

    echo '1';
    die();

}

function woo_square_plugin_sync_woo_category_to_square() {

    session_start();

    $catId = sanitize_text_field($_POST['id']);
    if (!isset($_SESSION["woo_to_square"]["target_categories"][$catId])) {
       
    }


    $actionType = $_SESSION["woo_to_square"]["target_categories"][$catId]['action'];
	
    $square = new Square(get_option('woo_square_access_token'), get_option('woo_square_location_id'),WOOSQU_PLUS_APPID);
    $squareSynchronizer = new WooToSquareSynchronizer($square);
    $result = FALSE;
    
    switch ($actionType) {
        case 'add':
            $category = get_term_by('id', $catId, 'product_cat');
            $result = $squareSynchronizer->addCategory($category);
		
            if ($result===TRUE) {
                update_option("is_square_sync_{$catId}", 1);
            }
            $action = Helpers::ACTION_ADD;
            break;

        case 'update':
            $category = get_term_by('id', $catId, 'product_cat');
			
            $category->term_id = $catId;
            $result = $squareSynchronizer->editCategory($category, $_SESSION["woo_to_square"]["target_categories"][$catId]['square_id']);

            if ($result===TRUE) {
                update_option("is_square_sync_{$catId}", 1);
            }
            $action = Helpers::ACTION_UPDATE;
            break;

        case 'delete':
            $item_square_id = isset($_SESSION["woo_to_square"]["target_categories"][$catId]['square_id']) ?
                $_SESSION["woo_to_square"]["target_categories"][$catId]['square_id'] : null;

            if ($item_square_id) {
                $result = $squareSynchronizer->deleteCategory($item_square_id);

                //delete category from plugin delete table
                if ($result===TRUE OR $result['errors'][0]['code'] == 'NOT_FOUND') {
                    global $wpdb;
                    $result = $wpdb->delete($wpdb->prefix . WOO_SQUARE_TABLE_DELETED_DATA, ['square_id' => $item_square_id]
                    );
					
					if($result == 1){
						$result = TRUE;
					}

                }
            }

            $action = Helpers::ACTION_DELETE;
            break;
    }

    //log
    //check if response returned is bool or error response message
    $message = NULL;
    if (!is_bool($result)){
        $message = $result['message'];
        $result = FALSE;
		
    }

    Helpers::sync_db_log(
        $action,
        date("Y-m-d H:i:s"),
        Helpers::SYNC_TYPE_MANUAL,
        Helpers::SYNC_DIRECTION_WOO_TO_SQUARE,
        $catId,
        Helpers::TARGET_TYPE_CATEGORY,
        $result?Helpers::TARGET_STATUS_SUCCESS:Helpers::TARGET_STATUS_FAILURE,
        $_SESSION["woo_to_square"]["target_categories"]['parent_id'],
        $_SESSION["woo_to_square"]["target_categories"][$catId]['name'],
        $_SESSION["woo_to_square"]["target_categories"][$catId]['square_id'],
        $message
    );
    echo $result;
    die();
}

function woo_square_plugin_sync_woo_product_to_square() {

    session_start();
    $productId = sanitize_text_field($_POST['id']);
    $square = new Square(get_option('woo_square_access_token'), get_option('woo_square_location_id'),WOOSQU_PLUS_APPID);
    $squareSynchronizer = new WooToSquareSynchronizer($square);
	
    if(!strcmp($productId, 'modifier_set_end')){


        // if(!empty($_SESSION["session_key_count"]) && !empty($_SESSION["modifier_name_array"]) && !empty($_SESSION["product_loop_id"])){

        //update_post_meta( $_SESSION["product_loop_id"], 'product_modifier_group_name', $_SESSION["modifier_name_array"] );
        // }
        unset($_SESSION["modifier_name_array"]);
        unset($_SESSION["session_key_count"]);
        unset($_SESSION["product_loop_id"]);
    }

    if(strcmp($productId, 'modifier_set_end')) {
        if(strpos($productId, 'add_modifier')){
            //create modifier

            $result  =  $squareSynchronizer->woo_square_plugin_sync_woo_modifier_to_square_modifier($productId);
          
        }elseif(strpos($productId, '_modifier')){
            //if update modifier
            $result  =  $squareSynchronizer->woo_square_plugin_sync_woo_modifier_to_square_modifier($productId);
        }else{


            if (!isset($_SESSION["woo_to_square"]["target_products"][$productId])) {
                $result = FALSE;
            }
            $actionType = $_SESSION["woo_to_square"]["target_products"][$productId]['action'];

            $result = FALSE;

            if ( !strcmp($actionType, 'delete')) {

                //delete
                $item_square_id = isset($_SESSION["woo_to_square"]["target_products"][$productId]['square_id']) ?
                    $_SESSION["woo_to_square"]["target_products"][$productId]['square_id'] : null;

                if ($item_square_id) {
                    if(get_option('disable_auto_delete') != 1){
                        $result = $squareSynchronizer->deleteProductOrGet($item_square_id,"DELETE");
                    }
                    //delete product from plugin delete table
                    if ($result===TRUE) {
                        global $wpdb;
                        $wpdb->delete($wpdb->prefix . WOO_SQUARE_TABLE_DELETED_DATA, ['square_id' => $item_square_id]
                        );
                    }
                }
                $_SESSION["productid"] = $productId;
                $_SESSION["product_loop_id"] = $productId;
                $action = Helpers::ACTION_DELETE;


            } else {   //add/update
                $post = get_post($productId);
				
				
                if (!strpos($productId, 'modifier') && !strpos($productId, 'add_modifier')) {
                    $product_square_id = $squareSynchronizer->checkSkuInSquare($post, $_SESSION["woo_to_square"]["suqare_items"]);
					
                    if(!$product_square_id){
						// not exist in square so check in woo this product already updated
						$product_square_id = get_post_meta($post->ID, 'square_id', true);
						if($product_square_id){
							$exploded_product_square_id = explode('-',$product_square_id);
							if(count($exploded_product_square_id) == 5){
								
								$product = wc_get_product( $post->ID );
								
								$response = array();
								
								$method = "POST";
								$url = 'https://connect.squareup.com/v2/catalog/search';

								$headers = array(
									'Authorization' => 'Bearer '.get_option('woo_square_access_token'), // Use verbose mode in cURL to determine the format you want for this header
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
										if($obj->type == 'ITEM' and $obj->catalog_v1_ids[0]->catalog_v1_id == $product_square_id){
											$product_square_id = $obj->id;
										}
									}
								}
								
									
							} else {
								$product_square_id = '';
							}
							
						}
					} 

					$result = $squareSynchronizer->addProduct($post, $product_square_id);
                    //update post meta
                    if ($result===TRUE) {
                        update_post_meta($productId, 'is_square_sync', 1);
                    }
                    $action = (!strcmp($actionType, 'update'))?Helpers::ACTION_UPDATE:
                        Helpers::ACTION_ADD;

                    $_SESSION["productid"] = $productId;
                    $_SESSION["product_loop_id"] = $productId;
                }

            }

        }

        ///log the process
        //check if response returned is bool or error response message
        $message = NULL;
        if (!is_bool($result)){
            $message = $result['message'];
            $result = FALSE;
        }
        Helpers::sync_db_log($action,
            date("Y-m-d H:i:s"),
            Helpers::SYNC_TYPE_MANUAL,
            Helpers::SYNC_DIRECTION_WOO_TO_SQUARE,
            $productId,
            Helpers::TARGET_TYPE_PRODUCT,
            $result?Helpers::TARGET_STATUS_SUCCESS:Helpers::TARGET_STATUS_FAILURE,
            $_SESSION["woo_to_square"]["target_products"]['parent_id'],
            $_SESSION["woo_to_square"]["target_products"][$productId]['name'],
            $product_square_id,
            $message
        );

    }



    echo $result;
    die();
}




function woo_square_plugin_terminate_manual_woo_sync(){

    //stop synchronization if only started manually
    if ( !strcmp( get_option('woo_square_running_sync'), 'manual')){
        update_option('woo_square_running_sync', false);
        update_option('woo_square_running_sync_time', 0);
    }

    session_start();

    //ensure function is not called twice
    if (!isset($_SESSION["woo_to_square"])){
        return;
    }

    unset($_SESSION["woo_to_square"]);

    echo "1";
    die();



}


//square -> woo
function woo_square_plugin_get_non_sync_square_data(){
	
    $checkFlag = checkSyncStartConditions();
    if ($checkFlag !== TRUE){ die(json_encode(['error'=>$checkFlag])); }


    $square = new Square(get_option('woo_square_access_token'), get_option('woo_square_location_id'),WOOSQU_PLUS_APPID);


    $synchronizer = new SquareToWooSynchronizer($square);



    //for display
    $addProducts = $updateProducts = $deleteProducts = $addCategories
        = $updateCategories = $deleteCategories = [];
    //display only one checkbox in update
    $oneProductsUpdateCheckbox = true;

    //1-get all square categories ( having is_square_sync = 0 or key not exists )
    $squareCategories = $synchronizer->getSquareCategories();



	

    $synchSquareIds = [];
    if(!empty($squareCategories)){
        //get previously linked categories to woo
        $wooSquareCats = $synchronizer->getUnsyncWooSquareCategoriesIds($squareCategories, $synchSquareIds);
    }else{
        $squareCategories = $wooSquareCats = [];
    }

    $targetCategories = [];
	
    //merge add and update categories
    foreach ($squareCategories as $cat) {
        if (!isset($wooSquareCats[$cat->id])) {      //add
            $targetCategories[$cat->id]['action'] = 'add';
            $targetCategories[$cat->id]['woo_id'] = NULL;
            $targetCategories[$cat->id]['name'] = $cat->category_data->name;
            $targetCategories[$cat->id]['version'] = $cat->version;

            //for display
            $addCategories[] = [
                'woo_id'=> NULL,
                'checkbox_val' => $cat->id,
                'name'=> $cat->category_data->name
            ];

        }else{                                       //update
            //if category has square id but already synchronized, no need to synch again
            if(in_array($wooSquareCats[$cat->id][0], $synchSquareIds)){
                continue;
            }
            $targetCategories[$cat->id]['action'] = 'update';
            $targetCategories[$cat->id]['woo_id'] = $wooSquareCats[$cat->id][0];
            $targetCategories[$cat->id]['name'] = $wooSquareCats[$cat->id][1];
            $targetCategories[$cat->id]['new_name'] = $cat->category_data->name;
            $targetCategories[$cat->id]['version'] = $cat->version;
			
		
            //for display

            if(is_array($wooSquareCats[$cat->id])){
                $updateCategories[] = [
                    'woo_id'=> $wooSquareCats[$cat->id][0],
                    'checkbox_val' => $cat->id,
                    'arrayyy' => $wooSquareCats[$cat->id],
                    'name'=> $wooSquareCats[$cat->id][1]
                ];
            }


        }

    }

    //2-get square products

		
			
    $targetProducts = $sessionProducts = [];
    $squareItems = $synchronizer->getSquareItems();
	
    $skippedProducts = $newSquareProducts = [];
    if ($squareItems){
        //get new square products and an array of products skipped from add/update actions
        $newSquareProducts = $synchronizer->getNewProducts($squareItems, $skippedProducts);
    }


    if(isset($_REQUEST['optionsaved'])){
        $newSquareProducts = $squareItems;
    }
	
    $sessionProducts = [];
    if(!empty($newSquareProducts['sku_misin_squ_woo_pro'])){
        foreach($newSquareProducts['sku_misin_squ_woo_pro'] as $sku_missin){
            $sku_missin_inside_product[] = [
                'woo_id'=> NULL,
                'name'=> '"'.$sku_missin->name.'" from square',
                'sku_misin_squ_woo_pro_variable'=> 'sku_misin_squ_woo_pro_variable',
                'checkbox_val' => $sku_missin->id
            ];
        }
        unset($newSquareProducts['sku_misin_squ_woo_pro']);
        
    }
    if(!empty($newSquareProducts['sku_misin_squ_woo_pro_variable'])){
        foreach($newSquareProducts['sku_misin_squ_woo_pro_variable'] as $sku_missin){
            $sku_missin_inside_product[] = [
                'woo_id'=> NULL,
                'name'=> '"'.$sku_missin->name.'" from square variations',
                'checkbox_val' => $sku_missin->id,
                'sku_misin_squ_woo_pro_variable' => 'sku_misin_squ_woo_pro_variable'
            ];
        }
        unset($newSquareProducts['sku_misin_squ_woo_pro_variable']);
    }
	$variats_ids = $newSquareProducts['variats_ids'];
	unset($newSquareProducts['variats_ids']);

    foreach ($newSquareProducts as $key => $product) {

        $targetProducts[$product->id]['action'] = 'add';
        $targetProducts[$product->id]['woo_id'] = NULL;
        $targetProducts[$product->id]['name'] = $product->name;
        //  if ((in_array('woosquare-modifier/woosquare-modifier.php', apply_filters('active_plugins', get_option('active_plugins'))))) {
        if (!empty($product->modifier_list_info)) {
            foreach ($product->modifier_list_info as $key => $mod_val) {

                $targetProducts[$product->id]['modifier_set_name'][$key] = $mod_val['mod_sets']['name'];

            }
            //    }
        }


        //store whole returned response in session
        $sessionProducts[$product->id] = $product;

        //if ((in_array('woosquare-modifier/woosquare-modifier.php', apply_filters('active_plugins', get_option('active_plugins'))))) {


        if(!empty($product->modifier_list_info)) {
            $kkey= 0;
            $modifier_set_name = array();
            foreach ($product->modifier_list_info as  $mod_val) {
                $modifier_set_name[$kkey] = $mod_val['mod_sets']['name'] . "|" . $mod_val['modifier_list_id']."|".$mod_val['version'];
                $kkey++;

            }

        }

        //  }

        //&& ((in_array('woosquare-modifier/woosquare-modifier.php', apply_filters('active_plugins', get_option('active_plugins')))))
        if(!empty($product->modifier_list_info) ) {
            //for display
            $addProducts[] = [
                'woo_id' => NULL,
                'name' => $product->name,
                'checkbox_val' => $product->id,
                'modifier_set_name' => $modifier_set_name

            ];
        } else {
			;
            $addProducts[] = [
                'woo_id' => NULL,
                'name' => $product->name,
                'checkbox_val' => $product->id,

            ];
        }


    }



    //construct session array
    session_start();
    $_SESSION["square_to_woo"] = [];
    $_SESSION["square_to_woo"]["target_categories"] = $targetCategories;
    $_SESSION["square_to_woo"]["target_products"] = $sessionProducts;
    $_SESSION["square_to_woo"]["target_products"]["skipped_products"] = $skippedProducts;

    $squareInventoryArray=[];
    $squareInventory = $synchronizer->getSquareInventory($variats_ids);
	
    if (!empty($squareInventory->counts)){
        $squareInventoryArray = $synchronizer->convertSquareInventoryToAssociative($squareInventory->counts);
    }
	
    $_SESSION["square_to_woo"]["target_products"]["products_inventory"] = $squareInventoryArray;

    //  do_action('add_modifier_list_sync');

    ob_start();
    include plugin_dir_path( dirname( __FILE__ ) ) . '../views/partials/pop-up.php';
    $data = ob_get_clean();
    echo json_encode(['data' => $data ]);


    die();

}

function woo_square_plugin_start_manual_square_to_woo_sync(){


    $checkFlag = checkSyncStartConditions();
    if ($checkFlag !== TRUE){ die($checkFlag); }

    update_option('woo_square_running_sync', 'manual');
    update_option('woo_square_running_sync_time', time());



    session_start();

    $_SESSION["square_to_woo"]["target_products"]['parent_id'] = $_SESSION["square_to_woo"]["target_categories"]['parent_id']
        = Helpers::sync_db_log(
        Helpers::ACTION_SYNC_START,
        date("Y-m-d H:i:s"),
        Helpers::SYNC_TYPE_MANUAL,
        Helpers::SYNC_DIRECTION_SQUARE_TO_WOO
    );

    echo '1';
    die();
}

function woo_square_plugin_sync_square_category_to_woo(){

    session_start();

    $catId = sanitize_text_field($_POST['id']);

    if (!isset($_SESSION["square_to_woo"]["target_categories"][$catId])) {
        die();
    }
    $actionType = $_SESSION["square_to_woo"]["target_categories"][$catId]['action'];

    $square = new Square(get_option('woo_square_access_token'), get_option('woo_square_location_id'),WOOSQU_PLUS_APPID);
    $squareSynchronizer = new SquareToWooSynchronizer($square);
    $result = FALSE;

    switch ($actionType) {
        case 'add':
            $category = new stdClass();
            $category->id = $catId;
            $category->name = $_SESSION["square_to_woo"]["target_categories"][$catId]['name'];
            $category->version = $_SESSION["square_to_woo"]["target_categories"][$catId]['version'];
            $result = $squareSynchronizer->addCategoryToWoo($category);
            if ($result!==FALSE) {
                update_option("is_square_sync_{$result}", 1);
                $target_id = $result;
                $result= TRUE;

            }
            $action = Helpers::ACTION_ADD;
            break;

        case 'update':
            $category = new stdClass();
            $category->id = $catId;
            $category->name = $_SESSION["square_to_woo"]["target_categories"][$catId]['new_name'];
            $category->version = $_SESSION["square_to_woo"]["target_categories"][$catId]['version'];
            $result = $squareSynchronizer->updateWooCategory($category,
                $_SESSION["square_to_woo"]["target_categories"][$catId]['woo_id']);
            if ($result!==FALSE) {
                update_option("is_square_sync_{$result}", 1);
            }
            $target_id = $_SESSION["square_to_woo"]["target_categories"][$catId]['woo_id'];
            $action = Helpers::ACTION_UPDATE;
            break;
    }

    //log
    Helpers::sync_db_log(
        $action,
        date("Y-m-d H:i:s"),
        Helpers::SYNC_TYPE_MANUAL,
        Helpers::SYNC_DIRECTION_SQUARE_TO_WOO,
        isset($target_id)?$target_id:NULL,
        Helpers::TARGET_TYPE_CATEGORY,
        $result?Helpers::TARGET_STATUS_SUCCESS:Helpers::TARGET_STATUS_FAILURE,
        $_SESSION["square_to_woo"]["target_categories"]['parent_id'],
        $_SESSION["square_to_woo"]["target_categories"][$catId]['name'],
        $catId
    );
    echo $result;
    die();
}


function woo_square_plugin_sync_square_product_to_woo() {



    session_start();
    $result = FALSE;  //default value for returned response

    $prodSquareId = sanitize_text_field($_POST['id']);



    if(!strcmp($prodSquareId, 'modifier_set_end')){


        if(!empty($_SESSION["session_key_count"]) && !empty($_SESSION["modifier_name_array"]) && !empty($_SESSION["product_loop_id"])){

        
            update_post_meta( $_SESSION["product_loop_id"], 'product_modifier_group_name', $_SESSION["modifier_name_array"] );
        

        }
        unset($_SESSION["modifier_name_array"]);
        unset($_SESSION["session_key_count"]);
        unset($_SESSION["product_loop_id"]);
        echo "1";
        die();
    }

    if(strcmp($prodSquareId, 'modifier_set_end')) {


        if (!strcmp($prodSquareId, 'update_products')) {

            $square = new Square(get_option('woo_square_access_token'), get_option('woo_square_location_id'), WOOSQU_PLUS_APPID);
            $synchronizer = new SquareToWooSynchronizer($square);
            $squareItems = $synchronizer->getSquareItems();

            //get all woocommerce products.
            $posts_per_page = -1;
            $args = array(
                'post_type' => 'product',
                'posts_per_page' => $posts_per_page
            );
            // delete those product which is not exist square but exist in woocommerce.....
            $woocommerce_products = get_posts($args);
            if ($woocommerce_products) {
                foreach ($woocommerce_products as $product) {

                    $square_id = get_post_meta($product->ID, 'square_id', true);
                    session_start();
                    $_SESSION["productid"] = $product->ID;
                    woo_square_plugin_sync_square_modifier_to_woo($product->ID,$squareItems);

                    //////
                    if (!empty($square_id) and !empty($squareItems)) {

                        $product->existinsquare = false;
                        foreach ($squareItems as $square_item) {
                            if ($square_id == $square_item->id) {
                                $product->existinsquare = true;
                            }
                        }
                        if (!$product->existinsquare) {
                            if (get_option('disable_auto_delete') != 1) {
                                wp_delete_post($product->ID, true);
                            }
                        }
                    }
                }
            }



            // $result = TRUE;
            if ($squareItems) {

                $squareItems[count($squareItems)] = $_SESSION["square_to_woo"];
                $new_modifier = array();

                foreach($squareItems as $val){
					array_push($new_modifier,$val);
                }
				
                echo ( (json_encode($new_modifier)));
                die();
                foreach ($squareItems as $squareProduct) {

                    //if not a new product or skipped product (has no skus)
                    if ((!isset($_SESSION["square_to_woo"]["target_products"][$squareProduct->id]))
                        && (!in_array($squareProduct->id, $_SESSION["square_to_woo"]["target_products"]["skipped_products"]))
                    ) {
                        $id = $synchronizer->addProductToWoo($squareProduct, $_SESSION["square_to_woo"]["target_products"]["products_inventory"]);

                        if (!empty($id) && is_numeric($id)) {
                            update_post_meta($id, 'is_square_sync', 1);
                            $resultStat = Helpers::TARGET_STATUS_SUCCESS;
                        } else {
                            $resultStat = Helpers::TARGET_STATUS_FAILURE;
                            $result = FALSE;
                        }

                    }

                }
            }

        }



        if (!strpos($prodSquareId, 'modifier')) {

            //  die();
            //add product action
            if (!isset($_SESSION["square_to_woo"]["target_products"][$prodSquareId])) {
                die();
            }


            $square = new Square(get_option('woo_square_access_token'), get_option('woo_square_location_id'), WOOSQU_PLUS_APPID);
            $squareSynchronizer = new SquareToWooSynchronizer($square);

            if (count($_SESSION["square_to_woo"]["target_products"][$prodSquareId]->variations) <= 1) {  //simple product
                $id = $squareSynchronizer->insertSimpleProductToWoo($_SESSION["square_to_woo"]["target_products"][$prodSquareId], $_SESSION["square_to_woo"]["target_products"]["products_inventory"]);

            } else {
                $id = $squareSynchronizer->insertVariableProductToWoo($_SESSION["square_to_woo"]["target_products"][$prodSquareId], $_SESSION["square_to_woo"]["target_products"]["products_inventory"]);
            }

            $action = Helpers::ACTION_ADD;
            $result = ($id !== FALSE) ? Helpers::TARGET_STATUS_SUCCESS : Helpers::TARGET_STATUS_FAILURE;


            if (!empty($id) && is_numeric($id)) {
                update_post_meta($id, 'is_square_sync', 1);
            }
            session_start();
            $_SESSION["productid"] = $id;
            $_SESSION["product_loop_id"] = $id;


            //log
            Helpers::sync_db_log(
                $action,
                date("Y-m-d H:i:s"),
                Helpers::SYNC_TYPE_MANUAL,
                Helpers::SYNC_DIRECTION_SQUARE_TO_WOO,
                is_numeric($id) ? $id : NULL,
                Helpers::TARGET_TYPE_PRODUCT,
                $result,
                $_SESSION["square_to_woo"]["target_categories"]['parent_id'],
                $_SESSION["square_to_woo"]["target_products"][$prodSquareId]->name,
                $prodSquareId
            );

        } else {

            session_start();
            $current_product_id = $_SESSION["productid"];
            if (!empty($current_product_id) && !empty($prodSquareId)) {
                $id =   woo_square_plugin_sync_square_modifier_to_woo($current_product_id, $prodSquareId);
                $_SESSION["product_loop_id"] = $current_product_id;
            }

            $action = Helpers::ACTION_ADD;
            $result = "1";

        }

    }


    echo $result;
    die();

}

function woo_square_plugin_sync_square_modifier_to_woo( $current_product_id , $prodSquareId){


    global $wpdb;

    /*    */

    // Update Modifier

    if(!isset($_SESSION['modifier_name_array'])) {


        session_start();
        $_SESSION['modifier_name_array'] = array();
        $session_key_count = 0  ;
    }
    if($_SESSION['session_key_count'] > 0){
        $session_key_count =   $_SESSION['session_key_count'];
    }

    $kkey = 0;



    if(( count($prodSquareId->modifier_list_info) >= 1))
    {

        
        $modifier_update = array();

        foreach($prodSquareId->modifier_list_info as $key => $mod) {

               if(gettype($mod) == 'array'){
                $mod = json_decode(json_encode($mod));
               }
 

            if (!empty($mod)) {

                $rowcount = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->prefix . "woosquare_modifier WHERE modifier_set_unique_id = '$mod->modifier_list_id '");

                $modifier_public = '0';
                $modifier_option = '0';
                if (($rowcount < 1)) {
                    $mod_name = $mod->mod_sets->name;

                    $modifier = array(
                        'modifier_set_name' => $mod->mod_sets->name,
                        'modifier_slug' => $mod->mod_sets->name,
                        'modifier_public' => $modifier_public,
                        'modifier_option' => $modifier_option,
                        'modifier_set_unique_id' => $mod->modifier_list_id,
                        'modifier_version' => $mod->version
                    );
                    $wpdb->insert($wpdb->prefix . 'woosquare_modifier', $modifier);

                    $lastid = $wpdb->insert_id;
                    $methode = "inserted";

                    woo_square_plugin_sync_square_modifier_child_to_woo($lastid,$mod->mod_sets->name,$mod->modifier_list_id,$methode,$mod->mod_sets->name);
                   $mod_name =   str_replace(' ', '-', strtolower($mod->mod_sets->name));
                    $modifier_update[$kkey]  = "pm"._.$mod_name."_".$lastid;
                    $_SESSION['modifier_name_array'][$session_key_count] = "pm"._.$mod_name."_".$lastid;


                }  elseif ($rowcount >= 1) {

                    $modifer_change = $wpdb->get_row("SELECT modifier_set_name,modifier_version  FROM " . $wpdb->prefix . "woosquare_modifier WHERE modifier_set_unique_id = '$mod->modifier_list_id ' ");

                   
                    $mod_name  = $mod->mod_sets->name;
                    $mod_version = $mod->version;
                  
                    if ($mod_name != $modifer_change->modifier_set_name) {
                        $wpdb->query($wpdb->prepare("UPDATE ". $wpdb->prefix ."woosquare_modifier SET modifier_set_name='$mod_name', modifier_version='$mod_version' WHERE modifier_set_unique_id= '$mod->modifier_list_id' "));
                    }

                    $modifer_id =  $wpdb->get_row("SELECT modifier_id,modifier_slug FROM " . $wpdb->prefix . "woosquare_modifier WHERE modifier_set_unique_id = '$mod->modifier_list_id' ");
                    $methode = "insert_updated";

                    woo_square_plugin_sync_square_modifier_child_to_woo($modifer_id->modifier_id,$mod_name,$mod->modifier_list_id,$methode,$modifer_id->modifier_slug);
                   
                    $mod_name =   str_replace(' ', '-', strtolower($modifer_id->modifier_slug));
                    $modifier_update[$kkey]  = "pm"._.$mod_name."_".$modifer_id->modifier_id;
                    $_SESSION['modifier_name_array'][$session_key_count] = "pm"._.$mod_name."_".$modifer_id->modifier_id;
                }



               /* if(empty($lastid)){

                    $lastid =  $modifer_id->modifier_id;
                }

                if(empty($modifer_id->modifier_slug)){

                    $mod_name =  str_replace(' ', '-',strtolower($mod->mod_sets->name));

                }else{

                    $mod_name =   str_replace(' ', '-', strtolower($modifer_id->modifier_slug));
                    
                  
                }



                $_SESSION['modifier_name_array'][$session_key_count] = "pm"._.$mod_name."_".$lastid;*/

            }

            $kkey++;
        }

      
      
      
        update_post_meta($current_product_id, 'product_modifier_group_name', $modifier_update );

    }else {
        //Create Modifier

        $modifier_name = (explode("_", $prodSquareId));
               
       
     

        $modifier_set_name = str_replace('-', ' ', $modifier_name[0]);
        $modifier_set_id = $modifier_name[1];
       
        if (!empty($modifier_set_name) && !empty($modifier_set_id)) {
         
            $rowcount = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->prefix . "woosquare_modifier WHERE modifier_set_unique_id = '$modifier_set_id' ");
            $modifier_public = '0';
            $modifier_option = '0';


            if (($rowcount < 1)) {

                $modifier = array(
                    'modifier_set_name' => $modifier_set_name,
                    'modifier_slug' => $modifier_set_name,
                    'modifier_public' => $modifier_public,
                    'modifier_option' => $modifier_option,
                    'modifier_set_unique_id' => $modifier_set_id,
                    'modifier_version' => $modifier_name[2]
                );
                $wpdb->insert($wpdb->prefix . 'woosquare_modifier', $modifier);
                $lastid = $wpdb->insert_id;
                $methode = "inserted";

                woo_square_plugin_sync_square_modifier_child_to_woo($lastid,$modifier_set_name,$modifier_set_id,$methode,$modifier_set_name);
                $_SESSION['modifier_name_array'][$session_key_count] = "pm"._.str_replace(' ', '-',strtolower($modifier_set_name))."_".$lastid;
            } elseif ($rowcount >= 1) {

                $modifer_change = $wpdb->get_row("SELECT modifier_set_name,modifier_version FROM " . $wpdb->prefix . "woosquare_modifier WHERE modifier_set_unique_id = '$modifier_set_id' ");
                if ($modifier_set_name != $modifer_change->modifier_set_name) {
                    $wpdb->query($wpdb->prepare("UPDATE " . $wpdb->prefix . "woosquare_modifier SET modifier_set_name='$modifier_set_name', modifier_version='$modifier_name[2]'  WHERE modifier_set_unique_id='$modifier_set_id'"));
                }

                $modifer_id =  $wpdb->get_row("SELECT modifier_id,modifier_slug FROM " . $wpdb->prefix . "woosquare_modifier WHERE modifier_set_unique_id = '$modifier_set_id' ");
                          
                $methode = "insert_updated";
                woo_square_plugin_sync_square_modifier_child_to_woo($modifer_id->modifier_id,$modifier_set_name,$modifier_set_id,$methode,$modifer_id->modifier_slug);
                $_SESSION['modifier_name_array'][$session_key_count] = "pm"._.str_replace(' ', '-',strtolower($modifer_id->modifier_slug))."_".$modifer_id->modifier_id;
            }
    
        }
        $kkey++;
    }


    if( $session_key_count >= 0){
        $_SESSION['session_key_count'] = $session_key_count + 1;
    }

    wp_schedule_single_event( time(), 'woocommerce_flush_rewrite_rules' );
    delete_transient( 'wsm_modifier' );
    WC_Cache_Helper::invalidate_cache_group( 'woosquare-modifier' );

    return true;


}




// insert modifier inner
// methode is name insert or update
// last id is last inser id or term id


function woo_square_plugin_sync_square_modifier_child_to_woo( $lastid,$modifier_set_name,$modifier_set_id,$methode,$modifier_slug ){

    $square = new Square(get_option('woo_square_access_token'), get_option('woo_square_location_id'), WOOSQU_PLUS_APPID);
    $synchronizer = new SquareToWooSynchronizer($square);
    $squareModifier = $synchronizer->getSquareModifier();


    if(!empty($squareModifier)) {
        if ($methode == "inserted") {

            foreach ($squareModifier as $key => $modifier) {

                //Condition check   ID come from same
                if ($modifier['type'] == 'MODIFIER_LIST' && $modifier['id']  == $modifier_set_id ) {

                    foreach ($modifier['modifier_list_data'] as $key => $modex) {

                        //update condition
                        // if($modex['id'] == $modifier_set_id){

                        foreach ($modex as $mod) {

                            $texonomy = 'pm_' . strtolower(str_replace(' ', '-', $modifier_set_name)) ."_". ($lastid);
                            $parent_term = term_exists($modifier_set_name, $texonomy); // array is returned if taxonomy is given

                            register_taxonomy($texonomy, 'product', array('hierarchical' => false,));
                            $term = wp_insert_term(
                                $mod['modifier_data']['name'], // the term
                                $texonomy, array(
                                    'description' =>  $mod['id'],
                                )
                            );
                            $amount = $mod['modifier_data']['price_money']['amount'] / 100;
                            update_term_meta($term['term_id'], 'term_meta_price', sanitize_text_field($amount));
                            update_term_meta($term['term_id'], 'term_meta_version', sanitize_text_field($mod['version']));

                        }

                        //      }

                    }

                }

            }


        } elseif ($methode == "insert_updated") {


            global $wpdb;


            $texonomy = 'pm_' . strtolower(str_replace(' ', '-', $modifier_slug))."_".($lastid);

            $term_query =  $wpdb->get_results(("SELECT term_id FROM " . $wpdb->prefix . "term_taxonomy WHERE taxonomy = '$texonomy'"));

            if(!empty($term_query)) {

                foreach ($term_query as $term) {

                    $old_object = get_term_by( 'id', $term->term_id, $texonomy);

                    foreach ($squareModifier as $key => $modifier) {

                        if ($modifier['type'] == 'MODIFIER_LIST' && $modifier['id'] == $modifier_set_id) {

                            foreach ($modifier['modifier_list_data'] as $key => $modex) {

                                foreach ($modex as  $keyyy => $mod) {

                                    $mod_str = strtolower(str_replace(' ', '-', $mod['modifier_data']['name']));

                                    if(!empty($old_object)){
                                        if ($mod['id'] == $old_object->description) { //check modifier id
                                            if ($mod_str != $old_object->slug) {
                                                register_taxonomy($texonomy, 'product', array('hierarchical' => false,));

                                                $args = array('name' => $mod['modifier_data']['name'] ,  'description' =>  $mod['id'] );

                                                $term = wp_update_term(
                                                    $old_object->term_id,
                                                    $texonomy,
                                                    $args
                                                );

                                                $amount = ($mod['modifier_data']['price_money']['amount'] / 100);
                                                $old_amount = get_term_meta($old_object->term_id, 'term_meta_price' ,true);
                                                $old_version = get_term_meta($old_object->term_id, 'term_meta_version' ,true);

                                                if ($old_amount != $amount) {
                                                    update_term_meta($old_object->term_id, 'term_meta_price', sanitize_text_field($amount));
                                                }
                                              


                                                if ($old_version != $mod['version']) {
                                                    update_term_meta($old_object->term_id, 'term_meta_version', sanitize_text_field($mod['version']));
                                                }


                                            }
                                        }else{

                                            $mod_id = $mod["id"];
                                            $rowcount_child = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->prefix . "term_taxonomy WHERE description = '$mod_id' ");
                                             $texnomy = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->prefix . "term_taxonomy WHERE taxonomy = '$texonomy' ");
                                            if($texnomy >= 1){
                                               
                                                register_taxonomy($texonomy, 'product', array('hierarchical' => false,));

                                                $args = array('name' => $mod['modifier_data']['name'] ,  'description' =>  $mod['id']);

                                                $term = wp_update_term(
                                                    $old_object->term_id,
                                                    $texonomy,
                                                    $args
                                                );

                                                $amount = ($mod['modifier_data']['price_money']['amount'] / 100);
                                                $old_amount = get_term_meta($old_object->term_id, 'term_meta_price' ,true);
                                                $old_version = get_term_meta($old_object->term_id, 'term_meta_version' ,true);

                                                if ($old_amount != $amount) {
                                                    update_term_meta($old_object->term_id, 'term_meta_price', sanitize_text_field($amount));
                                                }
                                              


                                                if ($old_version != $mod['version']) {
                                                    update_term_meta($old_object->term_id, 'term_meta_version', sanitize_text_field($mod['version']));
                                                }

                                                
                                            }
                                            
                                          else if($rowcount_child < 1){

                                                register_taxonomy($texonomy, 'product', array('hierarchical' => false,));
                                                $term = wp_insert_term(
                                                    $mod['modifier_data']['name'], // the term
                                                    $texonomy, array(
                                                        'description' =>  $mod['id'],
                                                    )
                                                );
                                                //   echo "1";

                                                $amount = $mod['modifier_data']['price_money']['amount'] / 100;
                                                update_term_meta($term['term_id'], 'term_meta_price', sanitize_text_field($amount));
                                                update_term_meta($term['term_id'], 'term_meta_version', sanitize_text_field($mod['version']));

                                            }

                                        }

                                    }
                                }

                            }

                        }

                    } //insert if not exist

                }
            }

        }

        return true;

    }
}

function update_square_to_woo_action(){

    session_start();
    $json_items = json_decode(stripslashes($_POST['import_js_item']));
    $session_targets = json_decode(stripslashes($_POST['session_targets']));
    $session_targets = json_decode(json_encode($session_targets), True);

    $square = new Square(get_option('woo_square_access_token'), get_option('woo_square_location_id'),WOOSQU_PLUS_APPID);
    $synchronizer = new SquareToWooSynchronizer($square);
    //if not a new product or skipped product (has no skus)
    if ( (!isset($session_targets["target_products"][$json_items->id]) )
        && (!in_array($json_items->id,$session_targets["target_products"]["skipped_products"]))
    ){


        $id = $synchronizer->addProductToWoo($json_items,  $session_targets["target_products"]["products_inventory"]);
        echo $id;
        if (!empty($id) && is_numeric($id)){
            update_post_meta($id, 'is_square_sync', 1);
            $resultStat = Helpers::TARGET_STATUS_SUCCESS;
        }else{
            $resultStat = Helpers::TARGET_STATUS_FAILURE;
            $result = FALSE;
        }

    }

    die();
}

function woo_square_plugin_terminate_manual_square_sync(){

    //stop synchronization if only started manually
    if ( !strcmp( get_option('woo_square_running_sync'), 'manual')){
        update_option('woo_square_running_sync', false);
        update_option('woo_square_running_sync_time', 0);
    }

    session_start();

    //ensure function is not called twice
    if (!isset($_SESSION["square_to_woo"])){
        return;
    }

    unset($_SESSION["square_to_woo"]);
    echo "1";
    die();
}