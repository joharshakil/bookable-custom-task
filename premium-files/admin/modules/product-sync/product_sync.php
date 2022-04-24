<?php

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}


function report_error_pro() {
	$class = 'notice notice-error';
	if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) && (!in_array('mycred/mycred.php', apply_filters('active_plugins', get_option('active_plugins')))))  {
		$message = __( 'To use "WooSquare Plus WooCommerce Square Integration" WooCommerce Or MYCRED must be activated or installed!', 'woosquare' );
		printf( '<br><div class="%1$s"><p>%2$s</p></div><script>setTimeout(function () {
       window.location.href = "plugins.php"; //will redirect to your blog page (an ex: blog.html)
    }, 2500);</script>', esc_attr( $class ), esc_html( $message ) );
	}
	if (version_compare( PHP_VERSION, '5.5.0', '<' )) {
		$message = __( 'To use "WooSquare Plus WooCommerce Square Integration" PHP version must be 5.5.0+, Current version is: ' . PHP_VERSION . ". Contact your hosting provider to upgrade your server PHP version.\n", 'woosquare' );
		printf( '<br><div class="%1$s"><p>%2$s</p></div><script>setTimeout(function () {
       window.location.href = "plugins.php"; //will redirect to your blog page (an ex: blog.html)
    }, 2500);</script>', esc_attr( $class ), esc_html( $message ) );
	}
	deactivate_plugins('woosquare-premium/woocommerce-square-integration.php');
	wp_die('','Plugin Activation Error',  array( 'response'=>200, 'back_link'=>TRUE ) );

}
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))
		and
		(!in_array('mycred/mycred.php', apply_filters('active_plugins', get_option('active_plugins'))))
		or
		version_compare( PHP_VERSION, '5.5.0', '<' )
) {
	add_action( 'admin_notices', 'report_error_pro' );
} else {

// if (!in_array('woosquare/woocommerce-square-integration.php', apply_filters('active_plugins', get_option('active_plugins')))) {
	define('WOO_SQUARE_PLUGIN_URL',plugin_dir_url(__FILE__));
	define('WOO_SQUARE_PLUGIN_PATH', plugin_dir_path(__FILE__));

	define( 'WooSquare_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );


	$woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
	if(@$woocommerce_square_plus_settings['enable_sandbox'] == 'yes'){
		if ( ! defined( 'WC_SQUARE_ENABLE_STAGING' ) ) {
			define( 'WC_SQUARE_ENABLE_STAGING', true );
			define( 'WC_SQUARE_STAGING_URL', 'squareupsandbox' );
		}
	} else {
		if ( ! defined( 'WC_SQUARE_ENABLE_STAGING' ) ) {
			define( 'WC_SQUARE_ENABLE_STAGING', false );
			define( 'WC_SQUARE_STAGING_URL', 'squareup' );
		}
	}

	add_action('wp_ajax_manual_sync', "woo_square_manual_sync");
	
	
	if(!get_option('v2_converted_cat')){
		add_action('plugins_loaded', "woo_square_v2_converted_cat");
	}	
	
	add_action('save_post', 'woo_square_add_edit_product', 10, 3);
	if(get_option('disable_auto_delete') != 1){
		add_action('before_delete_post', 'woo_square_delete_product');
	}
	add_action('create_product_cat', 'woo_square_add_category');
	add_action('edited_product_cat', 'woo_square_edit_category');
	add_action('delete_product_cat', 'woo_square_delete_category',10,3);
	add_action('woocommerce_order_refunded', 'woo_square_create_refund', 10, 2);
	add_action('woocommerce_order_status_processing', 'woo_square_complete_order');

	add_filter('cron_schedules', 'cron_add_3min');
	add_action( 'wp_loaded','post_savepage_load_admin_notice' );
	
// ADDED ACTION TO CATCH DUPLICATE PRODUCT AND REMOVE META DATA
	add_action("woocommerce_product_duplicate_before_save",'CatchDuplicateProduct',1, 2);
	function CatchDuplicateProduct($duplicate, $product){
		$duplicate->delete_meta_data( "square_id" );
		$duplicate->delete_meta_data( "_square_item_id" );
		$duplicate->delete_meta_data( "_square_item_variation_id" );
	}

// Change new order email recipient for registered customers
	function wc_change_admin_new_order_email_recipient( $recipient, $order ) {
		if($order){
			$customer_id  = get_post_meta($order->get_id(),'_customer_user',true);
			$user_info = get_userdata($customer_id);
			update_option('square_new_email',$user_info->user_nicename);
			// check if product in order
			if ( $user_info->user_nicename == "square_user" )  {
				$recipient = "";
			} else {
				$recipient = $recipient;
			}
		}
		return $recipient;
	}
	if(get_option('sync_square_order_notify') == 1){
		add_filter('woocommerce_email_recipient_new_order', 'wc_change_admin_new_order_email_recipient', 1, 2);
	}




	function checkOrAddPluginTables(){
		//create tables
		require_once  ABSPATH . '/wp-admin/includes/upgrade.php' ;
		global $wpdb;

		//deleted products table
		$del_prod_table = $wpdb->prefix.WOO_SQUARE_TABLE_DELETED_DATA;
		if ($wpdb->get_var("SHOW TABLES LIKE '$del_prod_table'") != $del_prod_table) {

			if (!empty($wpdb->charset))
				$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
			if (!empty($wpdb->collate))
				$charset_collate .= " COLLATE $wpdb->collate";


			$sql = "CREATE TABLE " . $del_prod_table . " (
			`square_id` varchar(50) NOT NULL,
                        `target_id` bigint(20) NOT NULL,
                        `target_type` tinyint(2) NULL,
                        `name` varchar(255) NULL,
			PRIMARY KEY (`square_id`)
		) $charset_collate;";
			dbDelta($sql);
		}

		//logs table
		$sync_logs_table = $wpdb->prefix.WOO_SQUARE_TABLE_SYNC_LOGS;
		if ($wpdb->get_var("SHOW TABLES LIKE '$sync_logs_table'") != $sync_logs_table) {

			if (!empty($wpdb->charset))
				$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
			if (!empty($wpdb->collate))
				$charset_collate .= " COLLATE $wpdb->collate";


			$sql = "CREATE TABLE " . $sync_logs_table . " (
                    `id` bigint(20) auto_increment NOT NULL,
                    `target_id` bigint(20) NULL,
                    `target_type` tinyint(2) NULL,
                    `target_status` tinyint(1) NULL,
                    `parent_id` bigint(20) NOT NULL default '0',
                    `square_id` varchar(50) NULL,
                    `action`  tinyint(3) NOT NULL,
                    `date` TIMESTAMP NOT NULL,
                    `sync_type` tinyint(1) NULL,
                    `sync_direction` tinyint(1) NULL,
                    `name` varchar(255) NULL,
                    `message` text NULL,
                    PRIMARY KEY (`id`)
            ) $charset_collate;";
			dbDelta($sql);
		}
	}

	/**
	 * include script
	 */
	function woo_square_script() {

		wp_enqueue_script('woo_square_script', WOO_SQUARE_PLUGIN_URL . '_inc/js/script.js', array('jquery'));
		wp_localize_script('woo_square_script', 'myAjax', array('ajaxurl' => admin_url('admin-ajax.php')));

		wp_enqueue_style('woo_square_pop-up', WOO_SQUARE_PLUGIN_URL . '_inc/css/pop-up.css');
		wp_enqueue_style('woo_square_synchronization', WOO_SQUARE_PLUGIN_URL . '_inc/css/synchronization.css');
		
	}
	
	function woo_square_v2_converted_cat() {
		if(!get_option('v2_converted_cat')){
			$square = new Square(get_option('woo_square_access_token'),get_option('woo_square_location_id'),WOOSQU_PLUS_APPID);
			$squareSynchronizer = new SquareToWooSynchronizer($square);
			$squareCategories = $squareSynchronizer->getSquareCategories();
			
			if(!empty($squareCategories)){
				global $wpdb;
				$sql = $wpdb->prepare(
					"SELECT
						*
					FROM
						`{$wpdb->base_prefix}options`
					WHERE
						option_name LIKE %s;",
					'%category_square_id_%'
				);
				
				$square_cat_option = $wpdb->get_results( $sql , ARRAY_A );
				foreach($squareCategories as $squarecategory){
					foreach($square_cat_option as $woocat){
						
						
						if(!empty($squarecategory->catalog_v1_ids)){
							if($squarecategory->catalog_v1_ids[0]->catalog_v1_id == $woocat['option_value']){
								// update_option($woocat['option_name'],$squarecategory->id);
								
								
							} else {
								$v2explodedform = explode('-',$woocat['option_value']);
									
								if(count($v2explodedform) > 1 ){
									delete_option($woocat['option_name']);
								}
								
							}
						}
						
						
					}
				}
				update_option('v2_converted_cat',true);
			}
			
		}
		
	}

	/*
     * Ajax action to execute manual sync
     */

	function woo_square_manual_sync() {

		ini_set('max_execution_time', 0);

		if(!get_option('woo_square_access_token')){
			return;
		}

		if(get_option('woo_square_running_sync') && (time()-(int)get_option('woo_square_running_sync_time')) < (WOO_SQUARE_MAX_SYNC_TIME) ){
			echo 'There is another Synchronization process running. Please try again later. Or <a href="'. admin_url('admin.php?page=square-item-sync&terminate_sync=true').'" > terminate now </a>';
			die();
		}

		update_option('woo_square_running_sync', true);
		update_option('woo_square_running_sync_time', time());

		if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {

			$sync_direction = $_GET['way'];

			$square = new Square(get_option('woo_square_access_token'),get_option('woo_square_location_id'),WOOSQU_PLUS_APPID);
			if ($sync_direction == 'wootosqu') {
				$squareSynchronizer = new WooToSquareSynchronizer($square);
				$squareSynchronizer->syncFromWooToSquare();
			} else if ($sync_direction == 'squtowoo') {
				$squareSynchronizer = new SquareToWooSynchronizer($square);
				$squareSynchronizer->syncFromSquareToWoo();
			}
		}
		update_option('woo_square_running_sync', false);
		update_option('woo_square_running_sync_time', 0);
		die();
	}


	function post_savepage_load_admin_notice() {
		// Use html_compress($html) function to minify html codes.

		if(!empty($_GET['post'])){
			$admin_notice_square = get_post_meta($_GET['post'], 'admin_notice_square', true);

			if(!empty($admin_notice_square)){
				ob_start();
				echo __('<div  id="message" class="notice notice-error  is-dismissible"><p>'.$admin_notice_square.'</p></div>','error-sql-syn-on-update');
				delete_post_meta($_GET['post'], 'admin_notice_square', 'Product unable to sync to Square due to Sku missing ');

			}
		}
   
		if(!empty(get_option('activate_modules_woosquare_plus'))){
				$activate_modules_woosquare_plus = get_option('activate_modules_woosquare_plus');	 
				if (!array_key_exists("woosquare_modifiers",$activate_modules_woosquare_plus)){
					$activate_modules_woosquare_plus['woosquare_modifiers'] = array(
					'module_img' => plugin_dir_url( __FILE__ ).'../admin/img/cardonfile2.png',
					'module_title' => 'Square Modifiers',
					'module_short_excerpt' => 'mods content.',
					'module_redirect' => 'https://apiexperts.io/link/save-cards-at-checkout/',
					'module_video' => 'https://www.youtube.com/embed/YVnjPEUWg8U',
					'module_activate' => (@$activate_modules_woosquare_plus['woosquare_modifiers']['module_activate'])?true:false,
					'module_menu_details' => array(
							'menu_title' => 'Square Modifiers',
							'parent_slug' => 'square-modifiers',
							'page_title' => 'Square Modifiers',
							'capability' => 'manage_options',
							'menu_slug' => 'square-modifiers',
							'tab_html_class' => 'fa fa-credit-card',
							'function_callback' => 'square_modifiers_sync_page',
					  )
					);
					update_option('activate_modules_woosquare_plus', $activate_modules_woosquare_plus);
				}
			}



	}



	/*
     * Adding and editing new product
     */

	function woo_square_add_edit_product($post_id, $post, $update) {
		// checking Would you like to synchronize your product on every product edit or update ?
		$sync_on_add_edit = get_option( 'sync_on_add_edit', $default = false ) ;
		if($sync_on_add_edit == '1'){


			//Avoid auto save from calling Square APIs.
			if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
				return;
			}


			if ($update && $post->post_type == "product" && $post->post_status == "publish") {

				update_post_meta($post_id, 'is_square_sync', 0);


				if(!get_option('woo_square_access_token')){
					return;
				}


				$product_square_id = get_post_meta($post_id, 'square_id', true);
				$square = new Square(get_option('woo_square_access_token'),get_option('woo_square_location_id'),WOOSQU_PLUS_APPID);

				$squareSynchronizer = new WooToSquareSynchronizer($square);
				
				$square = new Square(get_option('woo_square_access_token'), get_option('woo_square_location_id'),WOOSQU_PLUS_APPID);
				$SquareToWooSynchronizer = new SquareToWooSynchronizer($square);
				$squareItems = $SquareToWooSynchronizer->getSquareItems();

				// $squareItems = $this->getSquareItems();
				if($squareItems){
					$squareItems = $squareSynchronizer->simplifySquareItemsObject($squareItems);
				}else{
					$squareItems= [];
				}
				
				$product_square_id = $squareSynchronizer->checkSkuInSquare($post, $squareItems);
				
				
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
				
				// $result = $squareSynchronizer->addProduct($post, $product_square_id);

				$termid = get_post_meta($post_id, '_termid', true);
				if ($termid == '') {//new product
					$termid = 'update';
				}
				update_post_meta($post_id, '_termid', $termid);

				if( $result===TRUE ){
					update_post_meta($post_id, 'is_square_sync', 1);
				}



			}
		} else {
			update_post_meta($post_id, 'is_square_sync', 0);
		}
	}

	/*
     * Deleting product
     */

	function woo_square_delete_product($post_id) {
		$sync_on_add_edit = get_option( 'sync_on_add_edit', $default = false ) ;
		if($sync_on_add_edit == '1'){
			//Avoid auto save from calling Square APIs.
			if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
				return;
			}
			$product_square_id = get_post_meta($post_id, 'square_id', true);
			$product= get_post($post_id);
			if ($product->post_type == "product" && !empty($product_square_id)) {


				global $wpdb;

				$wpdb->insert($wpdb->prefix.WOO_SQUARE_TABLE_DELETED_DATA,
						[
								'square_id'  => $product_square_id,
								'target_id'  => $post_id,
								'target_type'=> Helpers::TARGET_TYPE_PRODUCT,
								'name'       => $product->post_title
						]
				);

				if(!get_option('woo_square_access_token')){
					return;
				}

				$square = new Square(get_option('woo_square_access_token'),get_option('woo_square_location_id'),WOOSQU_PLUS_APPID);
				$squareSynchronizer = new WooToSquareSynchronizer($square);
				if(get_option('disable_auto_delete') != 1){
					$result = $squareSynchronizer->deleteProductOrGet($product_square_id,"DELETE");
				}


				//delete product from plugin delete table
				if($result===TRUE){
					$wpdb->delete($wpdb->prefix.WOO_SQUARE_TABLE_DELETED_DATA,
							['square_id'=> $product_square_id ]
					);

				}


			}
		}
	}



	/*
     * Adding new Category
     */

	function woo_square_add_category($category_id) {

		//Avoid auto save from calling Square APIs.
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}

		$category = get_term_by('id', $category_id, 'product_cat');
		update_option("is_square_sync_{$category_id}", 0);

		if(!get_option('woo_square_access_token')){
			return;
		}


		$square = new Square(get_option('woo_square_access_token'),get_option('woo_square_location_id'),WOOSQU_PLUS_APPID);

		$squareSynchronizer = new WooToSquareSynchronizer($square);
		$result = $squareSynchronizer->addCategory($category);

		if( $result===TRUE ){
			update_option("is_square_sync_{$category_id}", 1);
		}
	}

	/*
     * Edit Category
     */

	function woo_square_edit_category($category_id) {

		//Avoid auto save from calling Square APIs.
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}


		update_option("is_square_sync_{$category_id}", 0);

		if(!get_option('woo_square_access_token')){
			return;
		}
		$category = get_term_by('id', $category_id, 'product_cat');
		$category->term_id = $category_id;
		$categorySquareId = get_option('category_square_id_' . $category->term_id);


		$square = new Square(get_option('woo_square_access_token'),get_option('woo_square_location_id'),WOOSQU_PLUS_APPID);
		$squareSynchronizer = new WooToSquareSynchronizer($square);

		//add category if not already linked to square, else update
		if( empty($categorySquareId )){
			$result = $squareSynchronizer->addCategory($category);
		}else{
			$result = $squareSynchronizer->editCategory($category,$categorySquareId);
		}


		if( $result===TRUE ){
			update_option("is_square_sync_{$category_id}", 1);
		}
	}

	/*
     * Delete Category ( called after the category is deleted )
     */

	function woo_square_delete_category($category_id,$term_taxonomy_id, $deleted_category) {

		//Avoid auto save from calling Square APIs.
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}
		$category_square_id = get_option('category_square_id_' . $category_id);

		//delete category options
		delete_option( "is_square_sync_{$category_id}" );
		delete_option( "category_square_id_{$category_id}" );

		//no need to call square
		if(empty($category_square_id)){
			return;
		}

		global $wpdb;

		$wpdb->insert($wpdb->prefix.WOO_SQUARE_TABLE_DELETED_DATA,
				[
						'square_id'  => $category_square_id,
						'target_id'  => $category_id,
						'target_type'=> Helpers::TARGET_TYPE_CATEGORY,
						'name'       => $deleted_category->name
				]
		);

		if(!get_option('woo_square_access_token')){
			return;
		}

		$square = new Square(get_option('woo_square_access_token'),get_option('woo_square_location_id'),WOOSQU_PLUS_APPID);
		$squareSynchronizer = new WooToSquareSynchronizer($square);
		$result = $squareSynchronizer->deleteCategory($category_square_id);

		//delete product from plugin delete table
		if($result===TRUE){
			$wpdb->delete($wpdb->prefix.WOO_SQUARE_TABLE_DELETED_DATA,
					['square_id'=> $category_square_id ]
			);

		}
	}

	/*
     * Create Refund
     */

	function woo_square_create_refund($order_id, $refund_id) {
		if(!get_option('woo_square_access_token')){
			return;
		}
		//Avoid auto save from calling Square APIs.
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}

		if (get_post_meta($order_id, 'woosquare_transaction_id', true)) {

			$square = new Square(get_option('woo_square_access_token'),get_option('woo_square_location_id'),WOOSQU_PLUS_APPID);
			$square->refund($order_id, $refund_id);
		}
	}

	/*
     * update square inventory on complete order
     */

	function woo_square_complete_order($order_id) {
		if(!get_option('woo_square_access_token')){
			return;
		}
		//Avoid auto save from calling Square APIs.
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}

		$square = new Square(get_option('woo_square_access_token'),get_option('woo_square_location_id'),WOOSQU_PLUS_APPID);
		$square->completeOrder($order_id);
	}

	/*
     *  Cron Jobs setup
     */

	function auto_sync_cron_job() {

		update_option('woo_square_auto_sync_'.date("Y-m-d H:i:s"), '');
		ini_set('max_execution_time', 0);

		if(!get_option('woo_square_access_token')){
			return;
		}

		update_option('woo_square_running_sync', true);
		update_option('woo_square_running_sync_time', time());


		$square = new Square(get_option('woo_square_access_token'),get_option('woo_square_location_id'),WOOSQU_PLUS_APPID);
		if (get_option('woo_square_merging_option') == '1') { //From Woo To Square
			$squareSynchronizer = new WooToSquareSynchronizer($square);
			$squareSynchronizer->syncFromWooToSquare();

		} else if (get_option('woo_square_merging_option') == '2') { //From Square To Woo
			$squareSynchronizer = new SquareToWooSynchronizer($square);
			$squareSynchronizer->syncFromSquareToWoo();
		}

		update_option('woo_square_running_sync', false);
		update_option('woo_square_running_sync_time', 0);
		// runkit_constant_remove("WOO_SQUARE_MAX_SYNC_TIME");



	}


	function cron_add_3min($schedules) {

		$schedules['3min'] = array(
				'interval' => 3 * 60,
				'display' => __('Once every three minutes')
		);
		$schedules[ 'weekly' ] = array(
				'interval' => 60 * 60 * 24 * 7, # 604,800, seconds in a week
				'display' => __( 'Weekly' )
		);
		return $schedules;
	}

	/**
	 * Check required environment
	 *
	 * @access public
	 * @since 1.0.10
	 * @version 1.0.10
	 * @return null
	 */
	add_action( 'admin_notices', 'check_environment' );

	function check_environment() {
		if ( ! is_allowed_countries() ) {
			if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
				$admin_page = 'wc-settings';
				echo '<div class="error">
					<p>' . sprintf(__('To enable payment gateway Square requires that the <a href="%s">base country/region</a> is the United States,United Kingdom,Japan, Canada or Australia.', 'woosquare'), admin_url('admin.php?page=' . $admin_page . '&tab=general')) . '</p>
				</div>';
			}elseif((in_array('mycred/mycred.php', apply_filters('active_plugins', get_option('active_plugins'))))){
				$admin_page = 'mycred-gateways';
				echo '<div class="error">
					<p>' . sprintf(__('To enable payment gateway Square requires that the <a href="%s">base country/region</a> is the United States,United Kingdom,Japan, Canada or Australia.', 'woosquare'), admin_url('admin.php?page=' . $admin_page)) . '</p>
				</div>';
			}
		}

		if ( ! is_allowed_currencies() ) {
			if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
				$admin_page = 'wc-settings';
				echo '<div class="error">
					<p>' . sprintf( __( 'To enable payment gateway Square requires that the <a href="%s">currency</a> is set to USD,GBP,JPY, CAD or AUD.', 'woosquare' ), admin_url( 'admin.php?page=' . $admin_page . '&tab=general' ) ) . '</p>
				</div>';
			}elseif((in_array('mycred/mycred.php', apply_filters('active_plugins', get_option('active_plugins'))))){
				$admin_page = 'mycred-gateways';
				echo '<div class="error">
					<p>' . sprintf(__('To enable payment gateway Square requires that the <a href="%s">currency</a> is set to USD,GBP,JPY, CAD or AUD ', 'woosquare'), admin_url('admin.php?page=' . $admin_page)) . '</p>
				</div>';
			}
		}
	}



	function is_allowed_countries() {


		if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			if (
					'US' !== WC()->countries->get_base_country() &&
					'CA' !== WC()->countries->get_base_country() &&
					'JP' !== WC()->countries->get_base_country() &&
					'AU' !== WC()->countries->get_base_country() &&
					'GB' !== WC()->countries->get_base_country()
			) {
				return false;
			}
		} elseif((in_array('mycred/mycred.php', apply_filters('active_plugins', get_option('active_plugins'))))) {
			$mycred_square_settings = get_option('mycred_pref_buycreds');
			if($mycred_square_settings) {

				if (
						'USD' !== $mycred_square_settings['gateway_prefs']['mycred_square']['currency'] &&
						'CAD' !== $mycred_square_settings['gateway_prefs']['mycred_square']['currency'] &&
						'JPY' !== $mycred_square_settings['gateway_prefs']['mycred_square']['currency'] &&
						'AUD' !== $mycred_square_settings['gateway_prefs']['mycred_square']['currency'] &&
						'GBP' !== $mycred_square_settings['gateway_prefs']['mycred_square']['currency']
				) {
					return false;
				}
			}

		} else {
			$class = 'notice notice-error';
			$message = __( 'To use Woosquare WooCommerce or MYCRED must be installed and activated!',  'woosquare');

			printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
			deactivate_plugins( plugin_basename( __FILE__ ) );
		}




		return true;
	}

	function is_allowed_currencies() {


		if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			if (
					'US' !== WC()->countries->get_base_country() &&
					'CA' !== WC()->countries->get_base_country() &&
					'JP' !== WC()->countries->get_base_country() &&
					'AU' !== WC()->countries->get_base_country() &&
					'GB' !== WC()->countries->get_base_country()
			) {
				return false;
			}
		}elseif((in_array('mycred/mycred.php', apply_filters('active_plugins', get_option('active_plugins')))) ) {

//get currency
			$mycred_square_settings = get_option('mycred_pref_buycreds');
			if($mycred_square_settings) {
				if (
						'USD' !== $mycred_square_settings['gateway_prefs']['mycred_square']['currency'] &&
						'CAD' !== $mycred_square_settings['gateway_prefs']['mycred_square']['currency'] &&
						'JPY' !== $mycred_square_settings['gateway_prefs']['mycred_square']['currency'] &&
						'AUD' !== $mycred_square_settings['gateway_prefs']['mycred_square']['currency'] &&
						'GBP' !== $mycred_square_settings['gateway_prefs']['mycred_square']['currency']
				) {
					return false;
				}
			}

		}

		else {
			$class = 'notice notice-error';
			$message = __( 'To use Woosquare. WooCommerce OR MYCRED Currency must be USD,CAD,AUD',  'woosquare');
			printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
			deactivate_plugins( plugin_basename( __FILE__ ) );
		}





		return true;
	}

	function payment_gateway_disable_country( $available_gateways ) {
		global $woocommerce;


		if ( isset( $available_gateways['square'] ) && !is_ssl()) {
			unset( $available_gateways['square'] );
		}

		$woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
		if($woocommerce_square_plus_settings['enabled'] == 'no'){
			unset( $available_gateways['square'] );
		} else if($woocommerce_square_plus_settings['enable_sandbox'] == 'yes'){
			$current_user = wp_get_current_user();
			if(user_can( $current_user, 'administrator' ) != 1){
				// user is an admin
				unset( $available_gateways['square'] );
			}
		}


		return $available_gateways;
	}

	add_filter( 'woocommerce_available_payment_gateways', 'payment_gateway_disable_country' );

	/*
    } else {
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        deactivate_plugins('woosquare/woocommerce-square-integration.php');
        activate_plugin('product-sync/woocommerce-square-integration.php');

    } */
} 