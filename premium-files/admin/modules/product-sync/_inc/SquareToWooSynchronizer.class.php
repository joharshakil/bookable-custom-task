<?php

/**
 * Synchronize From Square To WooCommerce Class
 */
class SquareToWooSynchronizer {
    /*
     * @var square square class instance
     */

    protected $square;

    /**
     *
     * @param object $square object of square class
     */
    public function __construct($square) {

        require_once plugin_dir_path( dirname( __FILE__ ) ) . '_inc/Helpers.class.php';
        $this->square = $square;
    }

    /*
     * Sync All products, categories from Square to Woo-Commerce
     */

    public function syncFromSquareToWoo() {

        $syncType = Helpers::SYNC_TYPE_AUTOMATIC;
        $syncDirection = Helpers::SYNC_DIRECTION_SQUARE_TO_WOO;
        //add start sync log record
        $logId = Helpers::sync_db_log(Helpers::ACTION_SYNC_START,
            date("Y-m-d H:i:s"), $syncType, $syncDirection);


        /* get all categories */
        $squareCategories = $this->getSquareCategories();

        /* get all items */
        $squareItems = $this->getSquareItems();

       
        //1- Update WooCommerce with categories from Square
        $synchSquareIds = [];
        if(!empty($squareCategories)){
            //get previously linked categories to woo
            $wooSquareCats = $this->getUnsyncWooSquareCategoriesIds($squareCategories, $synchSquareIds);
        }else{
            $squareCategories = $wooSquareCats = [];
        }

        $woo_square_sync_preference = get_option('woo_square_sync_preference');
        $woo_square_auto_sync = get_option('woo_square_auto_sync');

        $woo_square_listsaved_categories_square = get_option('woo_square_listsaved_categories_square');

        if(empty($woo_square_listsaved_categories_square)){
            $woo_square_listsaved_categories_square = array();
        }


        //add/update square categories
        foreach ($squareCategories as $cat){


            if($woo_square_sync_preference == 0 and $woo_square_auto_sync == 1){
                if(!in_array($cat->id, $woo_square_listsaved_categories_square)){
                    continue;
                }
            }

            if (isset( $wooSquareCats[$cat->id] )) {  //update

                //do not update if it is already updated ( its id was returned
                //in $synchSquareIds array )
                if(in_array($wooSquareCats[$cat->id][0], $synchSquareIds)){
                    continue;
                }

                $result = $this->updateWooCategory($cat,
                    $wooSquareCats[$cat->id][0]);
                if ($result!==FALSE) {
                    update_option("is_square_sync_{$result}", 1);
                }
                $target_id = $wooSquareCats[$cat->id][0];
                $action = Helpers::ACTION_UPDATE;

            }else{          //add
                $result = $this->addCategoryToWoo($cat);
                if ($result!==FALSE) {
                    update_option("is_square_sync_{$result}", 1);
                    $target_id = $result;
                    $result= TRUE;

                }
                $action = Helpers::ACTION_ADD;
            }
            //log category action
            Helpers::sync_db_log(
                $action,
                date("Y-m-d H:i:s"),
                $syncType,
                $syncDirection,
                $target_id,
                Helpers::TARGET_TYPE_CATEGORY,
                $result?Helpers::TARGET_STATUS_SUCCESS:Helpers::TARGET_STATUS_FAILURE,
                $logId,
                $cat->category_data->name,
                $cat->id
            );
        }



        $woo_square_listsaved_products_square = get_option('woo_square_listsaved_products_square');

        if(empty($woo_square_listsaved_products_square)){
            $woo_square_listsaved_products_square = array();
        }
        // 2-Update WooCommerce with products from Square

        if ($squareItems) {
            foreach ($squareItems as $squareProduct) {

                if($woo_square_sync_preference == 0 and $woo_square_auto_sync == 1){
                    if(!in_array($squareProduct->id, $woo_square_listsaved_products_square)){
                        continue;
                    }
                }
                 /* get Inventory of all items */
                 
                 
                 $array = json_decode(json_encode( $squareProduct->variations),true);
                    $squareInventory = $this->getSquareInventory($array);
                    $squareInventoryArray = [];
                    if (!empty($squareInventory)){
                        $squareInventoryArray = $this->convertSquareInventoryToAssociative($squareInventory->counts);
                    }
            

                $action = NULL;
                
                $id = $this->addProductToWoo($squareProduct, $squareInventoryArray, $action);

                if(is_null($action)){
                    continue;
                }
                $result = ($id !== FALSE) ? Helpers::TARGET_STATUS_SUCCESS : Helpers::TARGET_STATUS_FAILURE;

                if (!empty($id) && is_numeric($id)){
                    update_post_meta($id, 'is_square_sync', 1);
                }

                //log
                Helpers::sync_db_log(
                    $action,
                    date("Y-m-d H:i:s"),
                    Helpers::SYNC_TYPE_MANUAL,
                    Helpers::SYNC_DIRECTION_SQUARE_TO_WOO,
                    is_numeric($id) ? $id : NULL,
                    Helpers::TARGET_TYPE_PRODUCT,
                    $result,
                    $logId,
                    $squareProduct->name,
                    $squareProduct->id
                );
            }

        }
    }

    /*
     * update WooCommerce with categoreis from Square
     */

    public function insertCategoryToWoo($category) {
        $product_categories = get_terms('product_cat', 'hide_empty=0');
        foreach ($product_categories as $categoryw) {
            $wooCategories[] = array('square_id' => get_option('category_square_id_' . $categoryw->term_id), 'name' => $categoryw->name, 'term_id' => $categoryw->term_id);
        }

        $wooCategory = Helpers::searchInMultiDimensionArray($wooCategories, 'square_id', $category->id);
        $slug =  $category->name;
        remove_action('edited_product_cat', 'woo_square_edit_category');
        remove_action('create_product_cat', 'woo_square_add_category');

        if ($wooCategory) {
            wp_update_term($wooCategory['term_id'], 'product_cat', array('name' => $category->name, 'slug' => $slug));
            update_option('category_square_id_' . $wooCategory['term_id'], $category->id);
        } else {
            $result = wp_insert_term($category->name, 'product_cat', array('slug' => $slug));
            if (!is_wp_error($result) && isset($result['term_id'])) {
                update_option('category_square_id_' . $result['term_id'], $category->id);
            }
        }
        add_action('edited_product_cat', 'woo_square_edit_category');
        add_action('create_product_cat', 'woo_square_add_category');
    }


    /**
     * Add WooCommerce category from Square
     * @param object $category category square object
     * @return int|false created category id, false in case of error
     */

    public function addCategoryToWoo($category) {

        $retVal = FALSE;
        $slug = $category->category_data->name;
        remove_action('edited_product_cat', 'woo_square_edit_category');
        remove_action('create_product_cat', 'woo_square_add_category');
        $result = wp_insert_term($category->category_data->name, 'product_cat', array('slug' => $slug));
        if (!is_wp_error($result) && isset($result['term_id'])) {
            update_option('category_square_id_' . $result['term_id'], $category->id);
            update_option('category_square_version_' . $result['term_id'], $category->version);
            $retVal = $result['term_id'];
        } else {
            if(is_numeric($result->error_data['term_exists'])){
                $retVal = $result->error_data['term_exists'];

            }
        }
        add_action('edited_product_cat', 'woo_square_edit_category');
        add_action('create_product_cat', 'woo_square_add_category');

        return $retVal;
    }

    /*
     * update WooCommerce with categoreis from Square
     */

    public function updateWooCategory($category, $catId) {

        $slug = $category->category_data->name;
        remove_action('edited_product_cat', 'woo_square_edit_category');
        remove_action('create_product_cat', 'woo_square_add_category');

        wp_update_term($catId, 'product_cat', array('name' => $category->category_data->name, 'slug' => $slug));
        update_option('category_square_id_' .$catId, $category->id);
		
        update_option('category_square_version_' .$catId, $category->version);

        add_action('edited_product_cat', 'woo_square_edit_category');
        add_action('create_product_cat', 'woo_square_add_category');

        return TRUE;
    }

    /*
     * update WooCommerce with products from Square
     */

    public function addProductToWoo($squareProduct, $squareInventory, &$action = FALSE) {

        //Simple square product

        if (count($squareProduct->variations) <= 1) {
            if (isset($squareProduct->variations[0]) && isset($squareProduct->variations[0]->item_variation_data->sku) && $squareProduct->variations[0]->item_variation_data->sku) {
                $square_product_sku = $squareProduct->variations[0]->item_variation_data->sku;
                $product_id_with_sku_exists = $this->checkIfProductWithSkuExists($square_product_sku, array("product", "product_variation"));



                if ($product_id_with_sku_exists) { // SKU already exists in other product
                    $product = get_post($product_id_with_sku_exists[0]);
                    $parent_id = $product->post_parent;

                    $id = $this->insertSimpleProductToWoo($squareProduct, $squareInventory, $product_id_with_sku_exists[0]);

                    if ($parent_id) {
                        if(get_option('disable_auto_delete') != 1){
                            $this->deleteProductFromWoo($product->post_parent);
                        }

                    }
                    $action = Helpers::ACTION_UPDATE;
                } else {
                    $id = $this->insertSimpleProductToWoo($squareProduct, $squareInventory);
                    $action = Helpers::ACTION_ADD;
                }
            } else {

                $id = FALSE;
                $action = NULL;

                /* //log
                Helpers::sync_db_log(
                    $action,
                    date("Y-m-d H:i:s"),
                    Helpers::SYNC_TYPE_MANUAL,
                    Helpers::SYNC_DIRECTION_SQUARE_TO_WOO,
                    is_numeric($id) ? $id : NULL,
                    Helpers::TARGET_TYPE_PRODUCT,
                    $result,
                    $logId,
                    $squareProduct->name,
                    $squareProduct->id
                ); */

            }
        }  else {
            //Variable square product
            $id = $this->insertVariableProductToWoo($squareProduct, $squareInventory, $action);
        }

        ///  if($squareProduct->modifier_list_id)
        
  
        
        
        if(!empty($squareProduct->modifier_list_info)){
			
			if(count($squareProduct->modifier_list_info) >= 1){

				woo_square_plugin_sync_square_modifier_to_woo($id,$squareProduct);

			}
        }





        return $id;
    }

    function create_variable_woo_product($title, $desc, $cats = array(), $variations, $variations_key, $product_square_id = null,$master_image = NULL, $parent_id = null) {
        $varkey = explode('[',$variations[0]['name'] );
        $variations_key  = $varkey[0];
        $post = array(
            'post_title' => $title,
            'post_content' => $desc,
            'post_status' => "publish",
            'post_name' => sanitize_title($title), //name/slug
            'post_type' => "product"
        );


        if ($parent_id) {
            $post['ID'] = $parent_id;
        }
        //Create product/post:
        remove_action('save_post', 'woo_square_add_edit_product');
        $new_prod_id = wp_insert_post($post);
        add_action('save_post', 'woo_square_add_edit_product', 10, 3);
        //make product type be variable:
        wp_set_object_terms($new_prod_id, 'variable', 'product_type');
        //add category to product:
        wp_set_object_terms($new_prod_id, $cats, 'product_cat');
        //################### Add size attributes to main product: ####################
        //Array for setting attributes
        $var_keys = array();
        $total_qty = 0;
        foreach ($variations as $variation) {
            $variation['name'] =  $variation['name'];
            $variationsexploded = explode(',',$variation['name']);
            if(is_array($variationsexploded)){


                foreach($variationsexploded as $attrnames){
                    $varkeys = explode('[',$attrnames );
                    $variation['name']  = $varkeys[1];
                    $variation['name']  = str_replace(']','',$attrnames);
                    $total_qty += (int) isset($variation["qty"]) ? $variation["qty"] : 0;
                    $varkeys = explode('[',$variation['name'] );


                    $var_keyss[] = $varkeys[0];
                    $variatioskeys[$varkeys[0]][] = $varkeys[1];
                }


                $var_keyss=array_unique($var_keyss,SORT_REGULAR);

                $var_keyss['variations_keys'] = $variatioskeys;
                $var_keys = array();
                $var_keys = $var_keyss;
            } else {
                $varkeys = explode('[',$variation['name'] );
                $variation['name']  = $varkeys[1];
                $variation['name']  = str_replace(']','',$variation['name']);
                $total_qty += (int) isset($variation["qty"]) ? $variation["qty"] : 0;
                $var_keys[] = $variation['name'];
            }

        }




        wp_set_object_terms($new_prod_id, $var_keys, $variations_key);
        foreach($var_keys as $key => $attrkeys){
            if(is_numeric($key)){
                global $wpdb;
                $term_query = $wpdb->get_results( "SELECT * FROM `".$wpdb->prefix."term_taxonomy` WHERE `taxonomy` = 'pa_".strtolower($attrkeys)."'" );
                $attr = $wpdb->get_results( "SELECT * FROM `".$wpdb->prefix."woocommerce_attribute_taxonomies` WHERE `attribute_name` = '".strtolower($attrkeys)."'");
            }


            if(!empty($attrkeys) and !is_array($attrkeys)){

                $variations_keys = array_unique($var_keys['variations_keys'][$attrkeys]);
                if ( ! empty( $term_query ) and !empty($attr) and is_numeric($key) ) {
                    $thedata['pa_'.$attrkeys] =  Array(
                        'name' => 'pa_'.$attrkeys,
                        'value' => '',
                        'is_visible' => 1,
                        'is_variation' => 1,
                        'position' => 1,
                        'is_taxonomy' => 1
                    );

                    $terms_name = array();
                    foreach($term_query as $key => $variations_value){
                        $term_data = get_term_by('id', $variations_value->term_id, 'pa_'.strtolower($attrkeys));
                        if(!empty($term_data)){
                            $terms_name[] = strtolower($term_data->name);
                        }
                    }
                    foreach($variations_keys as $termname){
                        $termname = strtolower($termname);

                        if(!empty($terms_name)){
                            if(!in_array($termname,$terms_name) and !empty($termname)){
                                $term = wp_insert_term(
                                    $termname, // the term
                                    'pa_'.strtolower($attrkeys), // the taxonomy
                                    array(
                                        'description'=> '',
                                        'slug' => strtolower($termname),
                                        'parent'=> ''
                                    )
                                );
                                if(!empty($term)){
                                    $terms_name[] = strtolower($termname);
                                }
                                if ( !is_wp_error($term) ){
                                    $add_term_meta = add_term_meta($term['term_id'], 'order_pa_'.strtolower($attrkeys), '', true);
                                }
                            }
                        }
                    }
                    $global_attr[] = $attrkeys;
                    if(!empty($variations_keys)){
                        foreach($variations_keys as $Arry){
                            $var_ontersect[] = strtolower($Arry);
                        }
                    }
                    $terms_name=array_intersect($terms_name,$var_ontersect);
                    wp_set_object_terms( $new_prod_id, $terms_name,'pa_'.strtolower($attrkeys));
                } else {
                    $variations_keys = array_unique($var_keys['variations_keys'][$attrkeys]);
                    $thedata[$attrkeys] =  Array(
                        'name' => $attrkeys,
                        'value' => implode('|', $variations_keys),
                        'is_visible' => 1,
                        'is_variation' => 1,
                        'position' => '0',
                        'is_taxonomy' => 0
                    );
                }
            }

        }

        update_post_meta($new_prod_id, '_product_attributes', $thedata);

        // wp_set_object_terms( $new_prod_id, array(16,15,17), 'pa_color');
        //########################## Done adding attributes to product #################
        //set product values:
        //update_post_meta($new_prod_id, '_stock_status', ( (int) $total_qty > 0) ? 'instock' : 'outofstock');
        update_post_meta($new_prod_id, '_stock_status', 'instock');
        // update_post_meta($new_prod_id, '_stock', $total_qty);
        $woocmmerce_instance = new WC_Product( $new_prod_id );
        wc_update_product_stock( $woocmmerce_instance, $total_qty);
        update_post_meta($new_prod_id, '_visibility', 'visible');
        update_post_meta($new_prod_id, 'square_id', $product_square_id);
        update_post_meta($new_prod_id, '_default_attributes', array());
        //###################### Add Variation post types for sizes #############################
        $i = 1;
        $var_prices = array();
        //set IDs for product_variation posts:
        $args = array(
            'post_type'     => 'product_variation',
            'post_status'   => array( 'private', 'publish' ),
            'numberposts'   => -1,
            'orderby'       => 'menu_order',
            'order'         => 'asc',
            'post_parent'   => $new_prod_id // $post->ID
        );
        $variation_already_exist = get_posts( $args );
        if(!empty($variation_already_exist)){
            foreach ($variation_already_exist as $variation_exi) {
                $variation_already_exist_arr[] = $variation_exi->ID;
            }
        }
        foreach ($variations as $variation) {
            $variation_forsetobj = 	$variation;
            $variation['name'] =  $variation['name'];
            $varkeys = explode('[',$variation['name'] );
            $variation['name']  = $varkeys[1];
            $variation['name']  = str_replace(']','',$variation['name']);
            $my_post = array(
                'post_title' => 'Variation #' . $i . ' of ' . count($variations) . ' for product#' . $new_prod_id,
                'post_name' => 'product-' . $new_prod_id . '-variation-' . $i,
                'post_status' => 'publish',
                'post_parent' => $new_prod_id, //post is a child post of product post
                'post_type' => 'product_variation', //set post type to product_variation
                'guid' => home_url() . '/?product_variation=product-' . $new_prod_id . '-variation-' . $i
            );
            if (isset($variation['product_id'])) {
                $my_post['ID'] = $variation['product_id'];
            }
            if(!empty($variation_already_exist_arr)){
                if(!empty($variation['product_id'])){
                    $proid[] = $variation['product_id'];
                }
            }
            //Insert ea. post/variation into database:
            remove_action('save_post', 'woo_square_add_edit_product');
            $attID = wp_insert_post($my_post);
            add_action('save_post', 'woo_square_add_edit_product', 10, 3);
            //Create 2xl variation for ea product_variation:
            $variation_forsetobj['name'] =  $variation_forsetobj['name'];
            $variation_values = explode(',',$variation_forsetobj['name']);
            foreach($variation_values as $values){
                $getting_attr_n_variation_name = explode('[',$values);
                if(@in_array( $getting_attr_n_variation_name[0],$global_attr)){
                    $pa = 'pa_';
                } else {
                    $pa='';
                }
                update_post_meta($attID, 'attribute_' .$pa.$getting_attr_n_variation_name[0], sanitize_title(str_replace(']','',$getting_attr_n_variation_name[1])));
            }
            update_post_meta($attID, '_regular_price', floatval($variation["price"]));
            update_post_meta($attID, '_price', floatval($variation["price"]));
            $var_prices[$i - 1]['id'] = $attID;
            $var_prices[$i - 1]['regular_price'] = sanitize_title($variation['price']);
            //add size attributes to this variation:
            wp_set_object_terms($attID, $var_keys, 'pa_' . sanitize_title($variation['name']));
            update_post_meta($attID, '_sku', $variation["sku"]);
            update_post_meta($attID, '_manage_stock', isset($variation["qty"]) ? 'yes' : 'no');
            update_post_meta($attID, 'variation_square_id', $variation["variation_id"]);
            if (isset($variation["qty"])) {
                update_post_meta($attID, '_stock_status', ( (int) $variation["qty"] > 0) ? 'instock' : 'outofstock');
                update_post_meta($attID, '_stock', $variation["qty"]);

            } else {
                update_post_meta($attID, '_stock_status', 'instock');
            }
            $i++;
        }
        //delete those variation that delete from square..
        if(!empty($proid) and !empty($variation_already_exist_arr)){
            $inter = array_diff($variation_already_exist_arr,$proid);
            if(!empty($inter)){
                foreach($inter as $key){
                    wp_delete_post($key,true);
                    //delete_post_meta($key);
                }
            }
        }
        $i = 0;
        foreach ($var_prices as $var_price) {
            $regular_prices[] = $var_price['regular_price'];
            $sale_prices[] = $var_price['regular_price'];
        }
        update_post_meta($new_prod_id, '_price', min($sale_prices));
        update_post_meta($new_prod_id, '_min_variation_price', min($sale_prices));
        update_post_meta($new_prod_id, '_max_variation_price', max($sale_prices));
        update_post_meta($new_prod_id, '_min_variation_regular_price', min($regular_prices));
        update_post_meta($new_prod_id, '_max_variation_regular_price', max($regular_prices));
        update_post_meta($new_prod_id, '_min_price_variation_id', $var_prices[array_search(min($regular_prices), $regular_prices)]['id']);
        update_post_meta($new_prod_id, '_max_price_variation_id', $var_prices[array_search(max($regular_prices), $regular_prices)]['id']);
        update_post_meta($new_prod_id, '_min_regular_price_variation_id', $var_prices[array_search(min($regular_prices), $regular_prices)]['id']);
        update_post_meta($new_prod_id, '_max_regular_price_variation_id', $var_prices[array_search(max($regular_prices), $regular_prices)]['id']);
        //for refreshing transient.
        $children_transient_name = 'wc_product_children_' . $new_prod_id;
        delete_transient( $children_transient_name );

        if (isset($master_image) && !empty($master_image->url)){
            //if square img id not found, download new image
            if (strcmp(get_post_meta( $new_prod_id, 'square_master_img_id',TRUE),$master_image->url)){
                $this->uploadFeaturedImage($new_prod_id, $master_image);
            }
        }
        return $new_prod_id;
    }

    /*
     * Insert variable product to woo-commerece
     */

    public function insertVariableProductToWoo($squareProduct, $squareInventory, &$action= FALSE) {

        $term_id = 0;
        if (isset ($squareProduct->category)){
            $wp_category = get_term_by('name', $squareProduct->category->name, 'product_cat');
            $term_id = isset($wp_category->term_id) ? $wp_category->term_id : 0;
        }

        //Try to get the product id from the SKU if set.
        $productIds = array();
        $product_id_with_sku_exists = false;

        foreach ($squareProduct->variations as $variation) {
            $square_product_sku = $variation->item_variation_data->sku;
            if ($square_product_sku) {
                $product_id_with_sku_exists = $this->checkIfProductWithSkuExists($square_product_sku, array("product", "product_variation"));
            }
            if ($product_id_with_sku_exists) {
                $productIds[$square_product_sku] = $product_id_with_sku_exists[0];
            }
        }

        if ($productIds) {

            //SKU already exits
            $product = get_post(reset($productIds));
            $parent_id = $product->post_parent;
			$woo_square_location_id = get_option('woo_square_location_id');
            if ($parent_id) { // woo product is variable
                $variations = array();
                foreach ($squareProduct->variations as $variation) {


                    //don't add product variaton that doesn't have SKU
                    if (empty($variation->item_variation_data->sku)) {
                        continue;
                    }
                    $price = isset($variation->item_variation_data->price_money->amount)?($variation->item_variation_data->price_money->amount/100):'';
                    $data = array('variation_id' => $variation->id, 'sku' => $variation->item_variation_data->sku, 'name' => $variation->item_variation_data->name, 'price' => $price );

                    //put variation product id in variation data to be updated
                    //instead of created
                    if (isset($productIds[$variation->item_variation_data->sku] )){
                        $data['product_id'] = $productIds[$variation->item_variation_data->sku];
                    }

                   /*  if (isset($variation->track_inventory) && $variation->track_inventory) {
                        if (isset($squareInventory[$variation->id])){
                            $data['qty'] = $squareInventory[$variation->id];
                        }
                    } */
							
					foreach($variation->item_variation_data->location_overrides as $location_overrides){
						if($location_overrides->location_id == $woo_square_location_id){
							if (isset($location_overrides->track_inventory) && $location_overrides->track_inventory) {
								if (isset($squareInventory[$variation->id])){
									$data['qty'] = $squareInventory[$variation->id];
								}
							}
						}
						
					}
					
                    $variations[] = $data;
                }
                $prodDescription = isset($squareProduct->description)?$squareProduct->description:' ';
                $prodImg = isset($squareProduct->image_data->image_data)?$squareProduct->image_data->image_data:NULL;
				$id = $this->create_variable_woo_product($squareProduct->name, $prodDescription, array($term_id), $variations, "variation", $squareProduct->id,$prodImg, $parent_id);
            } else { // woo product is simple
                $variations = array();

                foreach ($squareProduct->variations as $variation) {

                    //don't add product variaton that doesn't have SKU
                    if (empty($variation->item_variation_data->sku)) {
                        continue;
                    }
                    $price = isset($variation->item_variation_data->price_money->amount)?($variation->item_variation_data->price_money->amount / 100):'';
                    $data = array('variation_id' => $variation->id, 'sku' => $variation->item_variation_data->sku, 'name' => $variation->item_variation_data->name, 'price' => $price);
                    if (isset($productIds[$variation->item_variation_data->sku] )){
                        $data['product_id'] = $productIds[$variation->item_variation_data->sku];
                    }
					
					foreach($variation->item_variation_data->location_overrides as $location_overrides){
						if($location_overrides->location_id == $woo_square_location_id){
							if (isset($location_overrides->track_inventory) && $location_overrides->track_inventory) {
								if (isset($squareInventory[$variation->id])){
									$data['qty'] = $squareInventory[$variation->id];
								}
							}
						}
						
					}
                    /* if (isset($variation->track_inventory) && $variation->track_inventory) {
                        if (isset($squareInventory[$variation->id])){
                            $data['qty'] = $squareInventory[$variation->id];
                        }
                    } */
                    $variations[] = $data;
                }
                $prodDescription = isset($squareProduct->description)?$squareProduct->description:' ';
                $prodImg = isset($squareProduct->image_data->image_data)?$squareProduct->image_data->image_data:NULL;
            $id = $this->create_variable_woo_product($squareProduct->name, $prodDescription, array($term_id), $variations, "variation", $squareProduct->id, $prodImg);
            }
            $action = Helpers::ACTION_UPDATE;
        } else { //SKU not exists
            $variations = array();
            $noSkuCount = 0;
            foreach ($squareProduct->variations as $variation) {

                //don't add product variaton that doesn't have SKU
                if (empty($variation->sku)) {
                    $noSkuCount ++;
                    continue;
                }
                $price = isset($variation->price_money->amount)?($variation->price_money->amount / 100):'';
                $data = array('variation_id' => $variation->id, 'sku' => $variation->sku, 'name' => $variation->name, 'price' => $price);
                if (isset($variation->track_inventory) && $variation->track_inventory) {
                    if (isset($squareInventory[$variation->id])){
                        $data['qty'] = $squareInventory[$variation->id];
                    }
                }
                $variations[] = $data;
            }
            if ($noSkuCount == count($squareProduct->variations)){
                return FALSE;
            }
            $prodDescription = isset($squareProduct->description)?$squareProduct->description:' ';
            $prodImg = isset($squareProduct->image_data->image_data->url)?$squareProduct->image_data->image_data:NULL;
            $id = $this->create_variable_woo_product($squareProduct->name, $prodDescription, array($term_id), $variations, "variation", $squareProduct->id, $prodImg);
            $action = Helpers::ACTION_ADD;
        }
        return $id;
    }

    /*
     * insert simple product to woo-commerce
     */
    public function process_add_attribute($attribute)
    {

        global $wpdb;
        //      check_admin_referer( 'woocommerce-add-new_attribute' );

        if (empty($attribute['attribute_type'])) { $attribute['attribute_type'] = 'text';}
        if (empty($attribute['attribute_orderby'])) { $attribute['attribute_orderby'] = 'menu_order';}

        if (empty($attribute['attribute_public'])) { $attribute['attribute_public'] = 0 ;}

        if ( empty( $attribute['attribute_name'] ) || empty( $attribute['attribute_label'] ) ) {
            return new WP_Error( 'error', __( 'Please, provide an attribute name and slug.', 'woocommerce' ) );
        } elseif ( ( $valid_attribute_name = $this->valid_attribute_name( $attribute['attribute_name'] ) ) && is_wp_error( $valid_attribute_name ) ) {
            return $valid_attribute_name;
        } elseif ( taxonomy_exists( wc_attribute_taxonomy_name( $attribute['attribute_name'] ) ) ) {
            return new WP_Error( 'error', sprintf( __( 'Slug "%s" is already in use. Change it, please.', 'woocommerce' ), sanitize_title( $attribute['attribute_name'] ) ) );
        }

        $wpdb->insert( $wpdb->prefix . 'woocommerce_attribute_taxonomies', $attribute );

        do_action( 'woocommerce_attribute_added', $wpdb->insert_id, $attribute );

        flush_rewrite_rules();
        delete_transient( 'wc_attribute_taxonomies' );

        return true;
    }

    public function valid_attribute_name( $attribute_name ) {
        if ( strlen( $attribute_name ) >= 28 ) {
            return new WP_Error( 'error', sprintf( __( 'Slug "%s" is too long (28 characters max). Shorten it, please.', 'woocommerce' ), sanitize_title( $attribute_name ) ) );
        } elseif ( wc_check_if_attribute_name_is_reserved( $attribute_name ) ) {
            return new WP_Error( 'error', sprintf( __( 'Slug "%s" is not allowed because it is a reserved term. Change it, please.', 'woocommerce' ), sanitize_title( $attribute_name ) ) );
        }

        return true;
    }
    public function insertSimpleProductToWoo($squareProduct, $squareInventory, $productId = null) {


        $term_id = 0;
        if (isset($squareProduct->category)){
            $wp_category = get_term_by('name', $squareProduct->category->name, 'product_cat');
            $term_id = $wp_category->term_id ? $wp_category->term_id : 0;
        }

        $post_title = $squareProduct->name;
        $post_content = isset($squareProduct->description) ? $squareProduct->description : '';

        $my_post = array(
            'post_title' => $post_title,
            'post_content' => $post_content,
            'post_status' => 'publish',
            'post_author' => 1,
            'post_type' => 'product'
        );

        //check if product id provided to the function
        if ($productId) {
            $my_post['ID'] = $productId;
        } else {
        }

        // Insert the post into the database

        remove_action('save_post', 'woo_square_add_edit_product');
        $id = wp_insert_post($my_post, true);

        wp_set_object_terms( $id, $term_id, 'product_cat' );
        add_action('save_post', 'woo_square_add_edit_product', 10, 3);

        $is_attr_vari  = explode(',',$squareProduct->variations[0]->item_variation_data->name);

        if(is_array($is_attr_vari) and strpos($squareProduct->variations[0]->item_variation_data->name, ',') !== false){
            foreach($is_attr_vari as $attrr){
                $attrname = explode('[',$attrr);
                $attrterms = str_replace(']','',$attrname[1]);
                $tername = explode('|',$attrterms);

                $attrexpl = explode('[',$attrr);
                global $wpdb;
                $attr = $wpdb->get_results( "SELECT * FROM `".$wpdb->prefix."woocommerce_attribute_taxonomies` WHERE `attribute_name` = '".strtolower($attrexpl[0])."'");

                if(!empty($attr[0])){

                    $insert = $this->process_add_attribute(
                        array(
                            'attribute_name' => strtolower($attrname[0]),
                            'attribute_label' => strtolower($attrname[0]),
                            'attribute_type' => 'select',
                            'attribute_orderby' => 'menu_order',
                            'attribute_public' => 1
                        )
                    );
                    sleep(1);
                    $varis = array();
                    foreach($tername as $ternameval){
                        $varis[] = strtolower($ternameval);
                        wp_insert_term(
                            strtolower($ternameval),  // the term
                            'pa_'.strtolower($attrname[0]),  // the taxonomy
                            array(
                                'description'=> '',
                                'slug' => strtolower($ternameval),
                            )
                        );
                        $thedata['pa_'.strtolower($attrname[0])] =  Array(
                            'name' => 'pa_'.strtolower($attrname[0]),
                            'value' => '',
                            'is_visible' => 1,
                            'is_variation' => 0,
                            'position' => '0',
                            'is_taxonomy' => 1
                        );


                        global $wpdb;
                        $get_resul  = $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."terms` WHERE `slug` = '".strtolower($ternameval)."' ORDER BY `name` ASC",true);

                        if(!empty($get_resul[0])){
                            // INSERT INTO wp_term_relationships (object_id,term_taxonomy_id) VALUES ([the_id_of_above_post],1)
                            $pref = $wpdb->prefix;
                            $get_term_relationships  = $wpdb->get_results("SELECT * FROM `".$pref."term_relationships` WHERE `object_id` = '".$id."' AND `term_taxonomy_id` = '".$get_resul[0]->term_id."' AND `term_order` = '0'",true);
                            if(empty($get_term_relationships[0])){
                                $wpdb->insert($pref.'term_relationships', array(
                                    'object_id' => $id,
                                    'term_taxonomy_id' => $get_resul[0]->term_id,
                                    'term_order' => '0',
                                ));
                            }


                        }


                    }
                    wp_set_object_terms( $id, $varis,'pa_'.strtolower($attrname[0]));
                    update_post_meta($id, '_product_attributes', $thedata);
                } else {
                    $varis = array();
                    $varis[] = strtolower($ternameval);
                    $thedata[strtolower($attrname[0])] =  Array(
                        'name' => strtolower($attrname[0]),
                        'value' => $attrterms,
                        'is_visible' => 1,
                        'is_variation' => 0,
                        'position' => '0',
                        'is_taxonomy' => 0
                    );
                    wp_set_object_terms( $id, $varis,strtolower($attrname[0]));
                    update_post_meta($id, '_product_attributes', $thedata);
                }

            }




        } else {

            // for single global attribute
            if(!empty($is_attr_vari[0])){
                $attrexpl = explode('[',$is_attr_vari[0]);
                global $wpdb;
                $attr = $wpdb->get_results( "SELECT * FROM `".$wpdb->prefix."woocommerce_attribute_taxonomies` WHERE `attribute_name` = '".strtolower($attrexpl[0])."'");
                if(!empty($attr[0])){
                    $thedata['pa_'.$attr[0]->attribute_name] =  Array(
                        'name' => 'pa_'.$attr[0]->attribute_name,
                        'value' => '',
                        'is_visible' => 1,
                        'is_variation' => 1,
                        'position' => 1,
                        'is_taxonomy' => 1
                    );
                    update_post_meta($id, '_product_attributes', $thedata);
                    $attrexprepla = str_replace(']','',$attrexpl[1]);
                    $square_variation = explode('|',$attrexprepla);
                    foreach($square_variation as $keys => $variation){
                        $square_variation[$keys] = strtolower(trim($variation));
                    }
                    $term_query = $wpdb->get_results( "SELECT * FROM `".$wpdb->prefix."term_taxonomy` WHERE `taxonomy` = 'pa_".strtolower($attr[0]->attribute_name)."'" );
                    foreach($term_query as $key => $variations_value){
                        $term_data = get_term_by('id', $variations_value->term_id, 'pa_'.strtolower($attr[0]->attribute_name));
                        if(!empty($term_data->name)){
                            $site_exist_variations[] = strtolower($term_data->name);
                        }
                    }


                    foreach($square_variation as $keys => $variation){
                        if(in_array($variation,$site_exist_variations)){
                            $simple_variations[] = $variation;
                        } else {
                            $simple_variations[] = $variation;
                            $term = wp_insert_term(
                                $variation, // the term
                                'pa_'.strtolower($attr[0]->attribute_name), // the taxonomy
                                array(
                                    'description'=> '',
                                    'slug' => strtolower($variation),
                                    'parent'=> ''
                                )
                            );

                            if(!empty($term)){
                                $add_term_meta = add_term_meta($term['term_id'], 'order_pa_'.strtolower($attr[0]->attribute_name), '', true);
                            }
                        }
                    }
                    wp_set_object_terms( $id, $simple_variations,'pa_'.strtolower($attr[0]->attribute_name));
                } else {


                    $attrexplsing = explode('[',$is_attr_vari[0]);
                    if(!empty($attrexplsing[1])){
                        $variaarry = str_replace(']','',$attrexplsing[1]);
                        $variaarryimpl = explode('|',$variaarry);
                        $thedata[strtolower($attrexplsing[0])] =  Array(
                            'name' => strtolower($attrexplsing[0]),
                            'value' => str_replace(']','',$attrexplsing[1]),
                            'is_visible' => 1,
                            'is_variation' => 0,
                            'position' => '0',
                            'is_taxonomy' => 0
                        );
                        wp_set_object_terms( $id, $variaarryimpl,strtolower($attrexplsing[0]));
                        update_post_meta($id, '_product_attributes', $thedata);
                    }

                }

            }

        }

        if ($id) {
            $variation = $squareProduct->variations[0];
            $price = isset($variation->item_variation_data->price_money->amount)?($variation->item_variation_data->price_money->amount / 100):'';
            update_post_meta($id, '_visibility', 'visible');
            update_post_meta($id, '_stock_status', 'instock');
            update_post_meta($id, '_regular_price', $price );
            update_post_meta($id, '_price', $price);
            update_post_meta($id, '_sku', isset($variation->item_variation_data->sku) ? $variation->item_variation_data->sku : '');


			$woo_square_location_id = get_option('woo_square_location_id');
			if(!empty($squareProduct->variations[0]->item_variation_data->location_overrides)){
				foreach($squareProduct->variations[0]->item_variation_data->location_overrides as $location_overrides){
					if($location_overrides->location_id == $woo_square_location_id){
						if (isset($location_overrides->track_inventory) && $location_overrides->track_inventory) {
							update_post_meta($id, '_manage_stock', 'yes');
						} else {
							update_post_meta($id, '_manage_stock', 'no');
						}
					}
					
				}
			} else {
				if($squareProduct->variations[0]->item_variation_data->track_inventory){
					update_post_meta($id, '_manage_stock', 'yes');
				} else {
					update_post_meta($id, '_manage_stock', 'no');
				}
				
			}

            $this->addInventoryToWoo($id, $variation, $squareInventory);

            update_post_meta($id, 'square_id', $squareProduct->id);
            update_post_meta($id, 'variation_square_id', $variation->id);
            update_post_meta($id, '_termid', 'update');
            if (isset($squareProduct->master_image) && !empty($squareProduct->master_image->url)){

                //if square img id not found, download new image
                if (strcmp(get_post_meta( $id, 'square_master_img_id',TRUE),$squareProduct->master_image->url)){
                    $this->uploadFeaturedImage($id, $squareProduct->master_image);
                }
            }
            return $id;
        }
        return FALSE;
    }

    public function deleteProductFromWoo($product_id) {
        remove_action('before_delete_post', 'woo_square_delete_product');
        wp_delete_post($product_id, true);
        add_action('before_delete_post', 'woo_square_delete_product');
    }

    public function checkIfProductWithSkuExists($square_product_sku, $productType = 'product') {
        $args = array(
            'post_type' => $productType,
            'meta_query' => array(
                array(
                    'key' => '_sku',
                    'value' => $square_product_sku
                )
            ),
            'fields' => 'ids'
        );
        // perform the query
        $query = new WP_Query($args);

        $ids = $query->posts;

        // do something if the meta-key-value-pair exists in another post
        if (!empty($ids)) {
            return $ids;
        } else {
            return false;
        }
    }

    function uploadFeaturedImage($product_id, $master_image) {
		
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Add Featured Image to Post
		$image = $master_image->url; // Define the image URL here
        // magic sideload image returns an HTML image, not an ID
        $media = media_sideload_image($image, $product_id);

        // therefore we must find it so we can set it as featured ID
        if (!empty($media) && !is_wp_error($media)) {
            $args = array(
                'post_type' => 'attachment',
                'posts_per_page' => -1,
                'post_status' => 'any',
                'post_parent' => $product_id
            );

            $attachments = get_posts($args);

            if (isset($attachments) && is_array($attachments)) {
                foreach ($attachments as $attachment) {
                    // grab source of full size images (so no 300x150 nonsense in path)
                    $image = wp_get_attachment_image_src($attachment->ID, 'full');
                    // determine if in the $media image we created, the string of the URL exists
                    if (strpos($media, $image[0]) !== false) {
                        // if so, we found our image. set it as thumbnail
                        set_post_thumbnail($product_id, $attachment->ID);

                        //update square img id to prevent downloading it again each synch
						update_post_meta($product_id,'square_master_img_id',$master_image->id);
                        // only want one image
                        break;
                    }
                }
            }
        }
    }

    function addInventoryToWoo($productId, $variation, $inventoryArray) {

        if(isset($inventoryArray[$variation->id])){
            // update_post_meta($productId, '_stock', $inventoryArray[$variation->id]);
            $woocmmerce_instance = new WC_Product( $productId );
            wc_update_product_stock( $woocmmerce_instance,$inventoryArray[$variation->id]);
        }
    }

    public function getSquareCategories(){
        /* get all categories */
		
		$woo_square_location_id = get_option('woo_square_location_id');
        $token           = get_option( 'woo_square_access_token' );
        $end_point = 'squareup';

        $woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
        if(@$woocommerce_square_plus_settings['enable_sandbox'] == 'yes'){
            $token = $woocommerce_square_plus_settings['sandbox_access_token'];
            $woo_square_location_id = $woocommerce_square_plus_settings['sandbox_location_id'];
            $end_point = 'squareupsandbox';
        }
		
		$url = "https://connect.".$end_point.".com/v2/catalog/list";
        
        $headers = array(
            'Authorization' => 'Bearer ' .$token, // Use verbose mode in cURL to determine the format you want for this header
            'Content-Type' => 'application/json',
            'types' => 'CATEGORY',
        );


        $method = "GET";
        $args = array('types' => 'CATEGORY');
        $woo_square_location_id = get_option('woo_square_location_id');
		
		// delete_transient( $woo_square_location_id.'transient_'.__FUNCTION__);
        $square = new Square(get_option('woo_square_access_token'), $woo_square_location_id, WOOSQU_PLUS_APPID);

        //check transient expire or not..
        // delete_transient(  $woo_square_location_id.'transient_'.__FUNCTION__ );

        $response = array();
        $interval = 0;
        if (get_option('_transient_timeout_' .  $woo_square_location_id.'transient_'.__FUNCTION__ ) > time()){
			$response = get_transient( $woo_square_location_id.'transient_'.__FUNCTION__  );
		} else {
			$response = $square->wp_remote_woosquare($url,$args,$method,$headers,$response);

			//if elements upto 1000 take delay 5 min
			if(!empty($response['body'])){  
              @$count =  count(json_decode($response['body']));
				if($count > 999){
					$interval = 300;
				} else {
					$interval = 0;
				}	
			}
			
			set_transient( $woo_square_location_id.'transient_'.__FUNCTION__, $response, $interval );
		}
		
     
		/* $url = $this->square->getSquareURL() . '/categories';
		
		$headers = array(
			'Authorization' => 'Bearer '.$this->square->getAccessToken(), // Use verbose mode in cURL to determine the format you want for this header
			'Content-Type'  => 'application/json;',
		);
		$method = "GET";
		$args = array('');
		$response = array();
		$interval = 0;
		$woo_square_location_id = get_option('woo_square_location_id');	
		$square =  new Square(get_option('woo_square_access_token'), $woo_square_location_id,WOOSQU_PLUS_APPID);
		if (get_option('_transient_timeout_' .  $woo_square_location_id.'transient_'.__FUNCTION__ ) > time()){
			$response = get_transient( $woo_square_location_id.'transient_'.__FUNCTION__  );
		} else {
			$response = $square->wp_remote_woosquare($url,$args,$method,$headers,$response);
			//if elements upto 1000 take delay 5 min
			if(!empty($response['body'])){  
				if(count(json_decode($response['body'])) > 999){
					$interval = 300;
				} else {
					$interval = 0;
				}	
			}
			
			set_transient( $woo_square_location_id.'transient_'.__FUNCTION__, $response, $interval );
		} */
		
			
		if(!empty($response['response'])){ 
			if($response['response']['code'] == 200 and $response['response']['message'] == 'OK'){
				return json_decode($response['body'], false);
			} else {
				return false;
			}
		} else {
			return false;
		}
    }


    /**
     * Get categories ids linked to square if found from the given square
     * categories, and an array of the synchronized ones from those linked
     * categories
     * @global object $wpdb
     * @param object $squareCategories square categories object
     * @param array $syncSquareCats synchronized category ids
     * @return array Associative array with key: category square id ,
     *               value: array(category_id, category old name), and the
     *               square synchronized categories ids in the passed array
     */

    public function getUnsyncWooSquareCategoriesIds($squareCategories, &$syncSquareCats){

        global $wpdb;
        $wooSquareCategories = [];

        //return if empty square categories
        if (empty($squareCategories)){
            return $wooSquareCategories;
        }


        //get all square ids
        $optionValues =  ' ( ';
        foreach ($squareCategories as $squareCategory){
            $optionValues.= "'{$squareCategory->id}',";
            $originalSquareCategoriesArray[$squareCategory->id] = $squareCategory->name;
        }
        $optionValues = substr($optionValues, 0, strlen($optionValues) - 1);
        $optionValues .= " ) ";


        //get option keys for the given square id values
        $categoriesSquareIdsQuery = "
            SELECT option_name, option_value
            FROM {$wpdb->prefix}options
            WHERE option_value in {$optionValues}";

        $results = $wpdb->get_results($categoriesSquareIdsQuery, OBJECT);

        //select categories again to see if they need update
        $syncQuery = "
            SELECT term_id, name
            FROM {$wpdb->terms}
            WHERE term_id in ( ";
        $parameters = [];
        $addCondition = " %d ,";



        if (!is_wp_error($results)){
            foreach ($results as $row) {

                //get id from string
                preg_match('#category_square_id_(\d+)#is', $row->option_name, $matches);
                if (!isset($matches[1])) {
                    continue;
                }
                //add square id to array
                $wooSquareCategories[$row->option_value] = $matches[1];

            }
            if(!empty($wooSquareCategories)){
                foreach ($squareCategories as $sqCat){

                    if(isset($wooSquareCategories[$sqCat->id])){
                        //add id and name to be used in select synchronized categries query
                        $syncQuery.= $addCondition;
                        $parameters[] = $wooSquareCategories[$sqCat->id];
                    }
                }
            }


            if(!empty($parameters)){

                $syncQuery = substr($syncQuery, 0, strlen($syncQuery) - 1);
                $syncQuery.= ")";
                $sql =$wpdb->prepare($syncQuery, $parameters);
                $results = $wpdb->get_results($sql);
                foreach ($results as $row){

                    $key = array_search($row->term_id, $wooSquareCategories);

                    if ($key){
                        $wooSquareCategories[$key] = [ $row->term_id, $row->name];
                        if (!strcmp($row->name, $originalSquareCategoriesArray[$key])){
                            $syncSquareCats[] = $row->term_id;
                        }

                    }

                }

            }
        }

        //if category deleted but square id already added in option meta.
        $taxonomy     = 'product_cat';
        $orderby      = 'name';
        $show_count   = 0;      // 1 for yes, 0 for no
        $pad_counts   = 0;      // 1 for yes, 0 for no
        $hierarchical = 1;      // 1 for yes, 0 for no
        $title        = '';
        $empty        = 0;
        $args = array(
            'taxonomy'     => $taxonomy,
            'orderby'      => $orderby,
            'show_count'   => $show_count,
            'pad_counts'   => $pad_counts,
            'hierarchical' => $hierarchical,
            'title_li'     => $title,
            'hide_empty'   => $empty
        );
        $all_categories = get_categories( $args );

        if(!empty($all_categories)){
            foreach($all_categories as $keyscategories => $catsterms){
                $terms_id[] = $catsterms->term_id;
            }
            foreach($wooSquareCategories as $keys => $cats){

                if(in_array($cats[0],$terms_id)){

                    $returnarray[$keys] = $cats;
                }

            }
        }

        return $wooSquareCategories;

    }

    public function getNewProducts($squareItems, &$skippedProducts) {

        $newProducts = [];

        foreach ($squareItems as $squareProduct) {
            //Simple square product
            if (count($squareProduct->variations) <= 1) {

                if (isset($squareProduct->variations[0]) && isset($squareProduct->variations[0]->sku) && $squareProduct->variations[0]->sku) {
                    $square_product_sku = $squareProduct->variations[0]->sku;
                    $product_id_with_sku_exists = $this->checkIfProductWithSkuExists($square_product_sku, array("product", "product_variation"));
                    if (!$product_id_with_sku_exists) { // SKU already exists in other product
                        $newProducts[] = $squareProduct;
                    }
                } else {
				
					
                    $newProducts['sku_misin_squ_woo_pro'][] = $squareProduct;
                    $skippedProducts[] = $squareProduct->id;
                }
				
				$newProducts['variats_ids'][]['id'] = $squareProduct->variations[0]->id;
            } else {//Variable square product

                //if any sku was found linked to a woo product-> skip this product
                //as it's considered old
                $addFlag = TRUE; $noSkuCount = 0;
                foreach ($squareProduct->variations as $variation) {

                    if (isset($variation->sku) && (!empty($variation->sku))){
                        if($this->checkIfProductWithSkuExists($variation->sku, array("product", "product_variation"))){
                            //break loop as this product is not new
                            $addFlag = FALSE;
                            break;
                        }
                    }else{
                        $noSkuCount++;
                    }
					
					$newProducts['variats_ids'][]['id'] = $variation->id;
                }


                //return skipped product array
                foreach ($squareProduct->variations as $variation) {
                    if ((empty($variation->sku))){
                        $newProducts['sku_misin_squ_woo_pro_variable'][] = $squareProduct;
                        //if one sku missing break the loop
                        break;
                    }
                }



                //skip whole product if none of the variation has sku
                if ($noSkuCount == count($squareProduct->variations)){
                    $skippedProducts[] = $squareProduct->id;
                }elseif ($addFlag){             //sku exists but not found in woo
                    $newProducts[] = $squareProduct;
                }
            }
        }
        return $newProducts;
    }



    /**
     *
     * @return object|false the square response object, false if error occurs
     */

    public function getSquareModifier()
    {

        $url = esc_url("https://connect.squareup.com/v2/catalog/list");
        $headers = array(
            'Authorization' => 'Bearer ' . $this->square->getAccessToken(),
            'Content-Type' => 'application/json;',
            'types' => 'MODIFIER_LIST',
        );


        $method = "GET";
        $args = array('types' => 'MODIFIER_LIST');
        $woo_square_location_id = get_option('woo_square_location_id');
        $square = new Square(get_option('woo_square_access_token'), $woo_square_location_id, WOOSQU_PLUS_APPID);

        //check transient expire or not..
        // delete_transient(  $woo_square_location_id.'transient_'.__FUNCTION__ );

        $response = array();
        $response = $square->wp_remote_woosquare($url, $args, $method, $headers, $response);
        $objectModifier =  json_decode($response['body'], true);
        if($response['response']['code'] == 200 and $response['response']['message'] == 'OK'){
            return $objectModifier;
        } else {
            return false;
        }


    }

    public function getSquareItems()
    {
        /* get all items from square */
        //$url = $this->square->getSquareURL() . '/items';
        //$url = $this->square->getSquareURL() . '/v2/catalog/list';


        $woo_square_location_id = get_option('woo_square_location_id');
        $token           = get_option( 'woo_square_access_token' );
        $end_point = 'squareup';

        $woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
        if(@$woocommerce_square_plus_settings['enable_sandbox'] == 'yes'){
            $token = $woocommerce_square_plus_settings['sandbox_access_token'];
            $woo_square_location_id = $woocommerce_square_plus_settings['sandbox_location_id'];
            $end_point = 'squareupsandbox';
        }
		
		$url = "https://connect.".$end_point.".com/v2/catalog/list";
		
        $headers = array(
            'Authorization' => 'Bearer ' . $token, // Use verbose mode in cURL to determine the format you want for this header
            'Content-Type' => 'application/json',
			'types' => 'ITEM,MODIFIER_LIST,CATEGORY,IMAGE',
        );


        $method = "GET";
		$args = array('types' => 'ITEM,MODIFIER_LIST,CATEGORY,IMAGE');
        $woo_square_location_id = get_option('woo_square_location_id');
        $square = new Square(get_option('woo_square_access_token'), $woo_square_location_id, WOOSQU_PLUS_APPID);

        //check transient expire or not..
        // delete_transient(  $woo_square_location_id.'transient_'.__FUNCTION__ );

        $response = array();
        $interval = 0;
        if (get_option('_transient_timeout_' . $woo_square_location_id . 'transient_' . __FUNCTION__) > time()) {

            $response = get_transient($woo_square_location_id . 'transient_' . __FUNCTION__);
			$object_old = json_decode($response['body'], true);

        } else {
				
            $response = $square->wp_remote_woosquare($url, $args, $method, $headers, $response);
			$object_old = json_decode($response['body'], true);
			if(!empty($object_old)){
				if(count($object_old) > 999){
					$interval = 300;
				} else {
					$interval = 0;
				}
			}
			set_transient( $woo_square_location_id.'transient_'.__FUNCTION__, $response, $interval );
            
  
        }
	
            $object_new = array();
            $modifier_list = array();
            $category_list = array();
			$image_list = array();
            $ky = 0;
			
            foreach ($object_old as $vals) {	


                if ($vals['type'] == 'MODIFIER_LIST') {
                    $modifier_list[] = $vals;
                }

                if ($vals['type'] == 'CATEGORY') {
                    $category_list[] = $vals;
                }
				
				if ($vals['type'] == 'IMAGE') {
                    $image_list[] = $vals;
                }

                if ($vals['type'] == 'ITEM' and $vals['item_data']['product_type'] == 'REGULAR') {


                    @$object_new[$ky]->fees = array();

                    foreach ($vals['item_data']['variations'] as $kys => $vl) {


                        $vl['item_variation_data']['price_money']['currency_code'] = $vl['item_variation_data']['price_money']['currency'];
                        $vl['item_variation_data']['track_inventory'] = @$vl['item_variation_data']['location_overrides'][0]['track_inventory'];
                        $vl['item_variation_data']['inventory_alert_type'] = @$vl['item_variation_data']['location_overrides'][0]['inventory_alert_type'];

                        // pricing_type
                        unset($vl['item_variation_data']['price_money']['currency']);
                        $object_new[$ky]->variations[$kys] = (object)$vl['item_variation_data'];
						$object_new[$ky]->variations[$kys]->item_variation_data =  json_decode(json_encode($vl['item_variation_data']));
                        $object_new[$ky]->variations[$kys]->price_money = (object)$vl['item_variation_data']['price_money'];
                        $object_new[$ky]->variations[$kys]->version = $vl['version'];
						
						if(isset($vl['catalog_v1_ids'])){
							$object_new[$ky]->variations[$kys]->catalog_v1_ids = $vl['catalog_v1_ids'];
						}
                        

                        $object_new[$ky]->variations[$kys]->id = $vl['id'];
                        //  $vl['item_variation_data']['price_money'] = (object)$vl['item_variation_data']['item_id'];
                        //$object_new[$ky]->variations[$kys] = $vl;
                        // unset($vl['item_variation_data']);

                    }
					if (!empty($vals['item_data']['modifier_list_info']))
					{
						foreach ($vals['item_data']['modifier_list_info'] as $kys => $vl) {

							$object_new[$ky]->modifier_list_info[$kys] = $vl;

						}
                    }


                    $object_new[$ky]->id = $vals['id'];
                    $object_new[$ky]->version = $vals['version'];
					
					if(isset($vals['catalog_v1_ids'])){
						$object_new[$ky]->catalog_v1_ids = $vals['catalog_v1_ids'];
					}
                    $object_new[$ky]->name = $vals['item_data']['name'];
                    $object_new[$ky]->description = @$vals['item_data']['description'];
                    $object_new[$ky]->category_id = $vals['item_data']['category_id'];
                    $object_new[$ky]->visibility = $vals['item_data']['visibility'];
                    $object_new[$ky]->available_online = @$vals['item_data']['available_online'];
                    $object_new[$ky]->available_for_pickup = @$vals['item_data']['available_for_pickup'];

                    if (!empty($vals['image_id'])) {
                        $object_new[$ky]->master_image->id = $vals['image_id'];
                        $object_new[$ky]->master_image->url = $vals['item_data']['ecom_image_uris'][0];
                    }

                }


                $ky++;
            }
			foreach ($object_new as $kym => $image) {
				if (!empty($image->master_image->id) && empty($image->master_image->url) ) {
					foreach($image_list  as $imagelist){
						if($image->master_image->id == $imagelist['id']){
							$object_new[$kym]->master_image->url  = $imagelist['image_data']['url'];
						}
					}
				}
			}


        $keyyy = 0;
        foreach ($object_new as $kym => $cat) {
            if (!empty($cat->category_id)) {
              
                foreach ($category_list as $category) {

                    if((@$category['catalog_v1_ids'][$keyyy]['catalog_v1_id'] == $cat->category_id || $category['id']  == $cat->category_id  )){
                        if (empty($category['catalog_v1_ids'][$keyyy]['catalog_v1_id'])) {
                            @$object_new[$kym]->category->id = $cat->category_id;
                        }else {
                            $object_new[$kym]->category->id = $category['catalog_v1_ids'][$keyyy]['catalog_v1_id'];
                        }
                        $object_new[$kym]->category->name = $category['category_data']['name'];
                        $object_new[$kym]->category->v2_id = $cat->category_id;

                    }
                }

            }
            $keyyy++;
        }

        foreach ($object_new as $kym => $modadd) {
            if (!empty($modadd->modifier_list_info)) {
                foreach ($modadd->modifier_list_info as $keym => $modifier_list_info) {
                    foreach ($modifier_list as $modex) {

                        if ($modifier_list_info['modifier_list_id'] == $modex['id']) {
                            //$object_new[$kym]->modifier_list_info[$keym]['mod_name'] = $modex['modifier_list_data']['name'];
                            $object_new[$kym]->modifier_list_info[$keym]['mod_sets'] = $modex['modifier_list_data'];
                            $object_new[$kym]->modifier_list_info[$keym]['version'] = $modex['version'];
                        }
                    }

                }
            }
        }
        
  
        

           if(!empty($object_new)){
                   if($response['response']['code'] == 200 and $response['response']['message'] == 'OK'){
					
                       return $object_new;

                   } else {
                       return false;
                   }
               } else {
                   return false;
               }

    }

    public function getSquareInventory($variations){
        /* get Inventory of all items */
        /*  $url = $this->square->getSquareURL() . '/inventory';
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
         $error = curl_error($ch);
         curl_close($ch);
         $squareInventory = json_decode($response);




         if ($http_status !== 200) {
            return false;
         }
         // return $squareInventory; */
     
          
       
		if(!empty($variations)){
			foreach($variations as $variants){
				$variant_ids[] = $variants['id'];
			}
		}
		
        /* get Inventory of all items */
        $url = 'https://connect.squareup.com/v2/inventory/batch-retrieve-counts';

        $headers = array(
            'Authorization' => 'Bearer '.$this->square->getAccessToken(), // Use verbose mode in cURL to determine the format you want for this header
            'Content-Type'  => 'application/json;',
            'requesting'  => 'inventory',
        );
		
        $method = "POST";
        
        $woo_square_location_id = get_option('woo_square_location_id');
		$args = array (
				  'catalog_object_ids' => $variant_ids,
				  'location_ids' => 
				  array (
					0 => $woo_square_location_id,
				  ),
				);
				
        $square =  new Square(get_option('woo_square_access_token'), $woo_square_location_id,WOOSQU_PLUS_APPID);
		
		
        //check transient expire or not..
        // delete_transient( 'transient_'.__FUNCTION__ );
        // die();
        $response = array();
        $interval = 0;
		
        if (get_option('_transient_timeout_' .$woo_square_location_id.'transient_'.__FUNCTION__ ) > time()){

            $response = get_transient( $woo_square_location_id.'transient_'.__FUNCTION__  );
			
        } else {

            $response = $square->wp_remote_woosquare($url,$args,$method,$headers,$response);
            //if elements upto 1000 take delay 5 min
			
            if(!empty($response['body'])){
                if(count(json_decode($response['body'])) > 999){
                    $interval = 300;
                } else {
                    $interval = 0;
                }
            }

            set_transient( $woo_square_location_id.'transient_'.__FUNCTION__, $response, $interval );
        }
		 
        if(!empty($response['response'])){
            if($response['response']['code'] == 200 and $response['response']['message'] == 'OK'){
                return json_decode($response['body'], false);
            } else {
                return false;
            }
        } else {
            return false;
        }

    }


    /**
     * Convert square inventory objects to associative array
     * @return array key: inventory variation id, value: quantity_on_hand
     */
    public function convertSquareInventoryToAssociative($squareInventory) {

        $squareInventoryArray = [];
        foreach ($squareInventory as $inventory) {
            $squareInventoryArray[$inventory->catalog_object_id]
                = $inventory->quantity;
        }


        return $squareInventoryArray;
    }

}
