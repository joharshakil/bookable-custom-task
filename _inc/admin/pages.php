<?php
// don't load directly
if ( !defined('ABSPATH') )
	die('-1');

/**
 * settings page
 */
function woo_square_settings_page() {
    add_menu_page('Woo Square Settings', 'WooSquare', 'manage_options', 'square-settings', 'square_settings_page', plugin_dir_url( __FILE__ ) . "../images/square.png");
    if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))){
        add_submenu_page('square-settings', "Square-Plus-Page", "Square Payment ", 'manage_options', 'Square-Payment', 'square_payment_page_redirection');
    }
    if 	(in_array('mycred/mycred.php', apply_filters('active_plugins', get_option('active_plugins')))){
        add_submenu_page('square-settings', "Square-Plus-Page", "My Cred Square Payment ", 'manage_options', 'mycred-gateways', 'mycredsquare_payment_page_redirection');
    }
    add_submenu_page('square-settings', "Square-Plus-Page", "Square <span class='ws-pro-tag'>PLUS</span>", 'manage_options', 'Square-Plus-Page', 'square_payment_plugin_page');
    add_submenu_page('square-settings', "Logs", "Logs", 'manage_options', 'square-logs', 'logs_plugin_page_woosquare');
    add_submenu_page('square-settings', "Documentation Plus", "Documentation", 'manage_options', 'square-documentation', 'documentation_plugin_page');

}

/**
 * Settings page action
 */
function square_settings_page() {
    

    $square = new Square(get_option('woo_square_access_token_free'), get_option('woo_square_location_id_free'));

    $errorMessage = '';
    $successMessage = '';
    
    if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['terminate_sync'])) {
        
        //clear session variables if exists
        if (isset($_SESSION["square_to_woo"])){ unset($_SESSION["square_to_woo"]); };
        if (isset($_SESSION["woo_to_square"])){ unset($_SESSION["woo_to_square"]); };
        
        update_option('woo_square_running_sync', false);
        update_option('woo_square_running_sync_time', 0);
        Helpers::debug_log('info', "Synchronization terminated due to admin request");

        $successMessage = 'Sync terminated Successfully!';
    }
	
	if(
		!empty($_REQUEST['access_token']) and 
		!empty($_REQUEST['token_type']) and 
		$_REQUEST['token_type'] == 'bearer' 
		){
			
		
			if ( function_exists( 'wp_verify_nonce' ) && ! wp_verify_nonce( $_GET['wc_woosquare_token_nonce'], 'connect_woosquare' ) ) {
				wp_die( __( 'Cheatin&#8217; huh?', 'woosquare-square' ) );
			}
			
			$existing_token = get_option( 'woo_square_access_token_free' );

			// if token already exists, don't continue
			// if (  empty( $existing_token ) OR empty(get_option('woo_square_access_token_cauth')) ) {
				update_option('woo_square_auth_response',$_REQUEST);
				update_option('woo_square_access_token_free',$_REQUEST['access_token']);
				update_option('woo_square_access_token_cauth',$_REQUEST['access_token']);
				update_option('woo_square_update_msg_dissmiss','connected');
				delete_option('woo_square_auth_notice');
			// }
			wp_redirect(add_query_arg(
				array(
					'page'    => 'square-settings',
				),
				admin_url( 'admin.php' )
			));
			exit;
		}
	if(
		!empty($_REQUEST['disconnect_woosquare']) and 
		!empty($_REQUEST['wc_woosquare_token_nonce']) 
	){
		if ( function_exists( 'wp_verify_nonce' ) && ! wp_verify_nonce( $_GET['wc_woosquare_token_nonce'], 'disconnect_woosquare' ) ) {
			wp_die( __( 'Cheatin&#8217; huh?', 'woocommerce-square' ) );
		}
		
		
		
		//revoke token
		$oauth_connect_url = WOOSQU_CONNECTURL;
		$headers = array(
			'Authorization' => 'Bearer '.get_option('woo_square_access_token_free'), // Use verbose mode in cURL to determine the format you want for this header
			'Content-Type'  => 'application/json;',
		);		
		$redirect_url = add_query_arg(
			array(
				'app_name'    => WOOSQU_APPNAME,
				'plug'    => WOOSQU_PLUGIN_NAME,
			),
			admin_url( 'admin.php' )
		);
		$redirect_url = wp_nonce_url( $redirect_url, 'connect_wcsrs', 'wc_wcsrs_token_nonce' );
		$site_url = ( urlencode( $redirect_url ) );
		$args_renew = array(
			'body' => array(
				'header' => $headers,
				'action' => 'revoke_token',
				'site_url'    => $site_url,
			),
			'timeout' => 45,
		);
			
		$oauth_response = wp_remote_post( $oauth_connect_url, $args_renew );

		$decoded_oauth_response = json_decode( wp_remote_retrieve_body( $oauth_response ) );
		
		delete_option('woo_square_access_token_free');
		delete_option('woo_square_location_id_free');
		delete_option('woo_square_access_token_cauth');
		delete_option('woo_square_locations_free');
		delete_option('woo_square_business_name_free');
		delete_option('woo_square_auth_response');
		wp_redirect(add_query_arg(
			array(
				'page'    => 'square-settings',
			),
			admin_url( 'admin.php' )
		));
			exit;
	}
    
    // check if the location is not setuped
    if (get_option('woo_square_access_token_free') && !get_option('woo_square_location_id_free')) {
        $square->authorize();
    }
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // setup account
        if (isset($_POST['woo_square_access_token_free'])) {
            $square->setAccessToken(sanitize_text_field($_POST['woo_square_access_token_free']));
            if ($square->authorize()) {
                $successMessage = 'Settings updated Successfully!';
            } else {
                $errorMessage = 'Square Account Not Authorized';
            }
        }
        // save settings
        if (isset($_POST['woo_square_settings'])) {
			
            update_option('sync_on_add_edit', intval($_POST['sync_on_add_edit']));
            update_option('html_sync_des', intval($_POST['html_sync_des']));
            //update location id
            if( !empty($_POST['woo_square_location_id_free'])){
				// its a textformated like 123abc456 that why we used sanitize_text_field :) 
                $location_id = sanitize_text_field($_POST['woo_square_location_id_free']);
                update_option('woo_square_location_id_free', $location_id);               
                $square->setLocationId($location_id);
                $square->getCurrencyCode();
            }

            $successMessage = 'Settings updated Successfully!';
        }
    }
    $wooCurrencyCode    = get_option('woocommerce_currency');
    $squareCurrencyCode = get_option('woo_square_account_currency_code');
    
    if(!$squareCurrencyCode){
        $square->getCurrencyCode();
        $squareCurrencyCode = get_option('woo_square_account_currency_code');
    }
    if ( $currencyMismatchFlag = ($wooCurrencyCode != $squareCurrencyCode) ){
        Helpers::debug_log('info', "Currency code mismatch between Square [$squareCurrencyCode] and WooCommerce [$wooCurrencyCode]");

    }
    include WOO_SQUARE_PLUGIN_PATH . 'views/settings.php';
}


function square_payment_page_redirection(){
	wp_redirect(admin_url().'admin.php?page=wc-settings&tab=checkout&section=square');
	wp_die();
}

function documentation_plugin_page(){
	wp_redirect('https://apiexperts.io/documentation/topics/woosquare/');
	wp_die();
}

/**
 * Logs page action
 * @global type $wpdb
 */
function logs_plugin_page_woosquare(){
        
      
        global $wpdb;
        
        $query = "
        SELECT log.id as log_id,log.action as log_action, log.date as log_date,log.sync_type as log_type,log.sync_direction as log_direction, children.*
        FROM ".$wpdb->prefix.WOO_SQUARE_TABLE_SYNC_LOGS." AS log
        LEFT JOIN ".$wpdb->prefix.WOO_SQUARE_TABLE_SYNC_LOGS." AS children
            ON ( log.id = children.parent_id )
        WHERE log.action = %d ";
              
        $parameters = [Helpers::ACTION_SYNC_START];
        
        //get the post params if sent or 'any' option was not chosen
        $sync_type = (isset($_POST['log_sync_type']) && strcmp($_POST['log_sync_type'],'any')) ? sanitize_text_field($_POST['log_sync_type']):null;
        $sync_direction = (isset($_POST['log_sync_direction']) && strcmp($_POST['log_sync_direction'],'any'))? sanitize_text_field($_POST['log_sync_direction']):null;
        $sync_date = isset($_POST['log_sync_date'])?
            (strcmp($_POST['log_sync_date'],'any')? sanitize_text_field($_POST['log_sync_date']):null):1;

        
        if (!is_null($sync_type)){
            $query.=" AND log.sync_type = %d ";
            $parameters[] = $sync_type; 
        }
        if (!is_null($sync_direction)){
           $query.=" AND log.sync_direction = %d ";
           $parameters[] = $sync_direction;  
        }
        if (!is_null($sync_date)){
           $query.=" AND log.date > %s ";
           $parameters[] = date("Y-m-d H:i:s", strtotime("-{$sync_date} days"));
        }
        
        
        $query.="
            ORDER BY log.id DESC,
                     id ASC";

        $sql =$wpdb->prepare($query, $parameters);
        $results = $wpdb->get_results($sql);
        $helper = new Helpers();
        
        include WOO_SQUARE_PLUGIN_PATH . 'views/logs.php';
       
}

/**
 * square payment plugin pro page action
 * @global type $wpdb
 */
function square_payment_plugin_page(){
    $html1 = '<h1 class="ws-heading-pro">Woo Square PRO</h1>';
    $html1 .= '<h2 class="ws-pro-ver">Why Use Pro Version?</h3>';
    $html1 .= '<div class="ws-pro-describe"><div class="ws-descrive-para">Need for that to simplify the process of selling data and integration between woo commerce and customers who use square point of sale at their transactions without need to adjust the inventory at both sides Synchronize products categories-products-products variations-discounts –quantity –price between square & woo commerce.
Synchronize Any updates at products details.Synchronize Customers create orders ,all orders details at square must be synchronized at woo commerce with products quantity deduction
There will be options if the system contain same products SKUs ,available options:
- Woo commerce product Override square product – Square product Override Woo commerce product<div class="ws-download-buy"><a href="https://codecanyon.net/item/woosquare/14663170">Download Now</a></div></div><div class="ws-pro-img"><img src="'.WOO_SQUARE_PLUGIN_URL_FREE.'_inc/images/woo-square-pro.png" ></div>';
    
    $html = '<div class="bodycontainerWrap bodycontainerWrapFeature landingProduct" style="max-width:100%;">';
    
    $html .= '<div id="woosquare_integration" class="ws-pro-wrapper">
            <div class="pro_features_list">
            <div class="ws-head-txt">



            
            
            <div class="headerBlock">
            <h2>Switch to <strong><a href="https://apiexperts.io/solutions/woosquare-plus/?utm_source=WordPress&utm_medium=PluginSale&utm_campaign=InStore">WooSquare Plus</a></strong> for more features..</h2>

            <div class="btnWrap">
                <a href="https://apiexperts.io/solutions/woosquare-plus" target="_blank" class="btn waves-effect waves-light btn-rounded btn-danger">Get Started Now</a>
                <a href="https://apiexperts.io/documentation/woosquare-plus/" class="btn waves-effect waves-light btn-rounded btn-outline-danger btnline">Documentation</a>
            </div>

                <div class="iframewrap">
                    <span class="tag1"></span>
                    <span class="tag2"></span>
                    <span class="tag3"></span>
                    <iframe width="100%" height="630" src="https://www.youtube.com/embed/TEAQ-H65inE" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>

                </div>
            </div>

			
			
			<div  class="ws-download-buy">
                
            <p class="about-text">WooSquare Plus is a robust and secure automation solution that allows Square users to synchronize their <br> online stores with the Square platform.</p></div>
<div id="pro_feature_list" class="productFeature clearfix">

    <div class="carousel">


        <div>
            <div class="ws-pro-box ws-pro-box-f">
                <div class="ws-pro-box-hold">
                    <div class="ws-pro-box-img"><span class="iconbox">
                        <svg id="mug" enable-background="new 0 0 300 300" height="512" viewBox="0 0 300 300" width="512" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><linearGradient id="SVGID_1_" gradientUnits="userSpaceOnUse" x1="76.327" x2="256.895" y1="239.673" y2="59.106"><stop offset="0" stop-color="#107eff"/><stop offset="1" stop-color="#8f16ff"/></linearGradient><g><path d="m196 42.005c-.002-2.209 1.786-4.002 3.995-4.005s4.002 1.786 4.005 3.995c.002 2.209-1.786 4.002-3.995 4.005-2.21.002-4.003-1.786-4.005-3.995zm-128 11.995c2.211 0 4-1.791 4-4v-8c0-2.209-1.789-4-4-4s-4 1.791-4 4v8c0 2.209 1.789 4 4 4zm0 8c-2.211 0-4 1.791-4 4v8c0 2.209 1.789 4 4 4s4-1.791 4-4v-8c0-2.209-1.789-4-4-4zm-16 0h8c2.211 0 4-1.791 4-4s-1.789-4-4-4h-8c-2.211 0-4 1.791-4 4s1.789 4 4 4zm24 0h8c2.211 0 4-1.791 4-4s-1.789-4-4-4h-8c-2.211 0-4 1.791-4 4s1.789 4 4 4zm172-6c0 7.719-6.281 14-14 14s-14-6.281-14-14 6.281-14 14-14 14 6.281 14 14zm-8 0c0-3.309-2.691-6-6-6s-6 2.691-6 6 2.691 6 6 6 6-2.691 6-6zm-4 202c0 2.209-1.789 4-4 4h-96c-28.156 0-51.458-20.907-55.361-48h-16.83c-17.539 0-31.809-14.268-31.809-31.807v-32.387c0-17.538 14.27-31.806 31.809-31.806h16.191v-16c0-2.209 1.789-4 4-4h144c2.211 0 4 1.791 4 4v104c0 20.383-10.981 38.204-27.305 48h27.305c2.211 0 4 1.791 4 4zm-156-116h-16.191c-4.305 0-7.809 3.502-7.809 7.807v32.387c0 4.305 3.504 7.807 7.809 7.807h16.191zm96 112c26.469 0 48-21.533 48-48v-100h-136v100c0 26.467 21.531 48 48 48zm88 0h-16c-2.211 0-4 1.791-4 4s1.789 4 4 4h16c2.211 0 4-1.791 4-4s-1.789-4-4-4zm-132-116v16c0 2.209-1.789 4-4 4h-16c-2.211 0-4-1.791-4-4v-16c0-2.209 1.789-4 4-4h16c2.211 0 4 1.791 4 4zm-8 4h-8v8h8zm56 12v-16c0-2.209 1.789-4 4-4h16c2.211 0 4 1.791 4 4v16c0 2.209-1.789 4-4 4h-16c-2.211 0-4-1.791-4-4zm8-4h8v-8h-8zm-56 56v16c0 2.209-1.789 4-4 4h-16c-2.211 0-4-1.791-4-4v-16c0-2.209 1.789-4 4-4h16c2.211 0 4 1.791 4 4zm-8 4h-8v8h8zm56 12v-16c0-2.209 1.789-4 4-4h16c2.211 0 4 1.791 4 4v16c0 2.209-1.789 4-4 4h-16c-2.211 0-4-1.791-4-4zm8-4h8v-8h-8zm-40-76h-8c-2.211 0-4 1.791-4 4s1.789 4 4 4h8c2.211 0 4-1.791 4-4s-1.789-4-4-4zm16 8h8c2.211 0 4-1.791 4-4s-1.789-4-4-4h-8c-2.211 0-4 1.791-4 4s1.789 4 4 4zm-16 60h-8c-2.211 0-4 1.791-4 4s1.789 4 4 4h8c2.211 0 4-1.791 4-4s-1.789-4-4-4zm24 0h-8c-2.211 0-4 1.791-4 4s1.789 4 4 4h8c2.211 0 4-1.791 4-4s-1.789-4-4-4zm24-44c0-2.209-1.789-4-4-4s-4 1.791-4 4v8c0 2.209 1.789 4 4 4s4-1.791 4-4zm-4 16c-2.211 0-4 1.791-4 4v8c0 2.209 1.789 4 4 4s4-1.791 4-4v-8c0-2.209-1.789-4-4-4zm-72-4c2.211 0 4-1.791 4-4v-8c0-2.209-1.789-4-4-4s-4 1.791-4 4v8c0 2.209 1.789 4 4 4zm-4 16c0 2.209 1.789 4 4 4s4-1.791 4-4v-8c0-2.209-1.789-4-4-4s-4 1.791-4 4z" fill="url(#SVGID_1_)"/></g></svg>
                    </span></div>
                    <div class="ws-pro-title">
                        <h3>Order Synchronization from Square to WooCommerce</h3>
                    </div>
        
                </div>
                
                <div class="ws-pro-para">
                    <p>In WooSquare Plus, your orders, refunds, and inventory will synchronize from Square to WooCommerce with ease. </p>
                </div>
            </div>
        </div>

    <div> <div class="ws-pro-box ws-pro-box-f">

        <div class="ws-pro-box-hold">
            <div class="ws-pro-box-img"><span class="iconbox">
                <svg id="box" enable-background="new 0 0 300 300" height="32px" viewBox="0 0 300 300" width="32px" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><linearGradient id="SVGID_1_" gradientUnits="userSpaceOnUse" x1="64.171" x2="257.484" y1="264.657" y2="71.344"><stop offset="0" stop-color="#107eff"/><stop offset="1" stop-color="#8f16ff"/></linearGradient><g><path d="m213.516 55.656 2.828-2.828-2.828-2.828c-1.562-1.562-1.562-4.094 0-5.656s4.094-1.562 5.656 0l2.828 2.828 2.828-2.828c1.562-1.562 4.094-1.562 5.656 0s1.562 4.094 0 5.656l-2.828 2.828 2.828 2.828c1.562 1.562 1.562 4.094 0 5.656-.781.781-1.805 1.172-2.828 1.172s-2.047-.391-2.828-1.172l-2.828-2.828-2.828 2.828c-.781.781-1.805 1.172-2.828 1.172s-2.047-.391-2.828-1.172c-1.563-1.562-1.563-4.093 0-5.656zm51.982 83.11c-.705 1.273-2.043 2.062-3.498 2.062h-16v112c0 2.211-1.791 4-4 4h-184c-2.209 0-4-1.789-4-4v-112h-16c-1.455 0-2.793-.789-3.498-2.062-.705-1.27-.664-2.824.105-4.059l20-32c.732-1.168 2.014-1.879 3.393-1.879h184c1.379 0 2.66.711 3.393 1.879l20 32c.769 1.234.81 2.789.105 4.059zm-220.281-5.938h118.566l15-24h-118.566zm16.783 116h120v-130.051l-12.607 20.172c-.732 1.168-2.014 1.879-3.393 1.879h-104zm192.783-116-15-24h-46.566l15 24zm-156.783 20v16c0 2.211-1.791 4-4 4h-16c-2.209 0-4-1.789-4-4v-16c0-2.211 1.791-4 4-4h16c2.209 0 4 1.789 4 4zm-8 4h-8v8h8zm80-4v16c0 2.211-1.791 4-4 4h-16c-2.209 0-4-1.789-4-4v-16c0-2.211 1.791-4 4-4h16c2.209 0 4 1.789 4 4zm-8 4h-8v8h8zm-64 64v16c0 2.211-1.791 4-4 4h-16c-2.209 0-4-1.789-4-4v-16c0-2.211 1.791-4 4-4h16c2.209 0 4 1.789 4 4zm-8 4h-8v8h8zm80-4v16c0 2.211-1.791 4-4 4h-16c-2.209 0-4-1.789-4-4v-16c0-2.211 1.791-4 4-4h16c2.209 0 4 1.789 4 4zm-8 4h-8v8h8zm-48-68h-8c-2.209 0-4 1.789-4 4s1.791 4 4 4h8c2.209 0 4-1.789 4-4s-1.791-4-4-4zm24 0h-8c-2.209 0-4 1.789-4 4s1.791 4 4 4h8c2.209 0 4-1.789 4-4s-1.791-4-4-4zm-24 68h-8c-2.209 0-4 1.789-4 4s1.791 4 4 4h8c2.209 0 4-1.789 4-4s-1.791-4-4-4zm24 0h-8c-2.209 0-4 1.789-4 4s1.791 4 4 4h8c2.209 0 4-1.789 4-4s-1.791-4-4-4zm20-32c2.209 0 4-1.789 4-4v-8c0-2.211-1.791-4-4-4s-4 1.789-4 4v8c0 2.211 1.791 4 4 4zm-4 16c0 2.211 1.791 4 4 4s4-1.789 4-4v-8c0-2.211-1.791-4-4-4s-4 1.789-4 4zm-68-16c2.209 0 4-1.789 4-4v-8c0-2.211-1.791-4-4-4s-4 1.789-4 4v8c0 2.211 1.791 4 4 4zm-4 16c0 2.211 1.791 4 4 4s4-1.789 4-4v-8c0-2.211-1.791-4-4-4s-4 1.789-4 4zm28-144c0-6.617 5.383-12 12-12s12 5.383 12 12-5.383 12-12 12-12-5.383-12-12zm8 0c0 2.207 1.795 4 4 4s4-1.793 4-4-1.795-4-4-4-4 1.793-4 4zm62 10.529c2.501 0 4.53-2.028 4.53-4.529 0-2.502-2.028-4.53-4.53-4.53s-4.53 2.028-4.53 4.53 2.029 4.529 4.53 4.529z" fill="url(#SVGID_1_)"/></g></svg>
            </span></div>
        <div class="ws-pro-title">
            <h3>Order Synchronization from WooCommerce to Square</h3>
        </div>
        </div>
        
        <div class="ws-pro-para">
            <p>In WooSquare Plus, your orders, refunds, and inventory will synchronize from WooCommerce to Square with ease.</p>
       </div>
    </div></div>

    <div><div class="ws-pro-box ws-pro-box-f">
        <div class="ws-pro-box-hold">
            <div class="ws-pro-box-img"><span class="iconbox">
                <svg id="cube-target" enable-background="new 0 0 300 300" height="512" viewBox="0 0 300 300" width="512" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><linearGradient id="SVGID_1_" gradientUnits="userSpaceOnUse" x1="72.295" x2="255.83" y1="227.224" y2="43.689"><stop offset="0" stop-color="#107eff"/><stop offset="1" stop-color="#8f16ff"/></linearGradient><g><path d="m265.656 64.828c1.562 1.562 1.562 4.094 0 5.656-.781.781-1.805 1.172-2.828 1.172s-2.047-.391-2.828-1.172l-2.828-2.828-2.828 2.828c-.781.781-1.805 1.172-2.828 1.172s-2.047-.391-2.828-1.172c-1.562-1.562-1.562-4.094 0-5.656l2.828-2.828-2.828-2.828c-1.562-1.562-1.562-4.094 0-5.656s4.094-1.562 5.656 0l2.828 2.828 2.828-2.828c1.562-1.562 4.094-1.562 5.656 0s1.562 4.094 0 5.656l-2.828 2.828zm-8.484 173.172c-2.209 0-4 1.791-4 4s1.791 4 4 4 4-1.791 4-4-1.791-4-4-4zm-208-188c0-6.617 5.383-12 12-12s12 5.383 12 12-5.383 12-12 12-12-5.383-12-12zm8 0c0 2.205 1.793 4 4 4s4-1.795 4-4-1.793-4-4-4-4 1.795-4 4zm129.941 127.496-36 20c-.602.336-1.273.504-1.941.504s-1.34-.168-1.941-.504l-36-20c-1.27-.705-2.059-2.043-2.059-3.496v-44c0-1.453.789-2.791 2.059-3.496l36-20c1.203-.672 2.68-.672 3.883 0l36 20c1.27.705 2.059 2.043 2.059 3.496v44c-.001 1.453-.79 2.791-2.06 3.496zm-65.706-47.496 27.764 15.423 27.765-15.423-27.764-15.424zm-4.235 41.646 28 15.555v-34.849l-28-15.556zm2.09-83.156c.555 0 1.121-.115 1.66-.363 4.574-2.092 9.391-3.674 14.32-4.699 2.16-.449 3.551-2.568 3.102-4.73-.453-2.164-2.547-3.557-4.734-3.102-5.512 1.146-10.898 2.916-16.016 5.258-2.008.918-2.891 3.293-1.973 5.301.672 1.47 2.121 2.335 3.641 2.335zm-36.871 74.383c-.809-4.205-1.219-8.535-1.219-12.873 0-.725.012-1.445.035-2.162.066-2.207-1.668-4.053-3.875-4.121-2.242-.078-4.055 1.664-4.125 3.875-.023.799-.035 1.602-.035 2.408 0 4.842.457 9.682 1.359 14.381.371 1.916 2.047 3.246 3.926 3.246.25 0 .504-.023.758-.072 2.172-.416 3.59-2.514 3.176-4.682zm-3.926-27.084c.336.086.672.127 1.004.127 1.777 0 3.402-1.195 3.867-2.998 1.262-4.873 3.07-9.609 5.383-14.078 1.012-1.961.246-4.375-1.719-5.389-1.961-1.018-4.375-.244-5.391 1.717-2.582 4.998-4.605 10.295-6.016 15.746-.554 2.139.731 4.32 2.872 4.875zm71.785-53.781c5.055.078 10.086.715 14.957 1.893.316.076.633.111.945.111 1.805 0 3.441-1.229 3.883-3.061.52-2.146-.801-4.309-2.945-4.828-5.449-1.316-11.07-2.027-16.715-2.115-.023 0-.043 0-.062 0-2.18 0-3.965 1.75-4 3.938-.036 2.208 1.73 4.027 3.937 4.062zm29.121 7.051c4.492 2.23 8.758 4.977 12.672 8.158.742.604 1.633.896 2.52.896 1.164 0 2.316-.504 3.109-1.477 1.391-1.715 1.133-4.234-.582-5.627-4.375-3.557-9.141-6.623-14.164-9.115-1.977-.99-4.375-.174-5.359 1.803-.985 1.981-.176 4.381 1.804 5.362zm-92.512 88.212c-.887-2.023-3.234-2.939-5.27-2.059-2.023.889-2.945 3.246-2.059 5.27 2.254 5.141 5.094 10.049 8.441 14.586.785 1.064 1.996 1.627 3.223 1.627.824 0 1.656-.254 2.371-.781 1.777-1.311 2.156-3.814.844-5.592-2.995-4.062-5.534-8.453-7.55-13.051zm30.196 32.684c-4.43-2.379-8.605-5.258-12.41-8.559-1.668-1.443-4.191-1.271-5.645.4-1.445 1.67-1.266 4.195.402 5.643 4.254 3.688 8.918 6.906 13.871 9.562.602.324 1.25.477 1.887.477 1.426 0 2.805-.764 3.527-2.107 1.047-1.947.317-4.371-1.632-5.416zm-9.067-114.07c1.758-1.34 2.094-3.85.754-5.605-1.332-1.758-3.84-2.096-5.605-.758-4.469 3.408-8.57 7.32-12.188 11.627-1.422 1.691-1.203 4.215.488 5.635.75.631 1.664.938 2.57.938 1.145 0 2.277-.486 3.066-1.428 3.239-3.856 6.911-7.358 10.915-10.409zm91.871 99.447c-3.363 3.756-7.145 7.143-11.238 10.062-1.797 1.283-2.215 3.781-.93 5.58.777 1.094 2.008 1.676 3.258 1.676.805 0 1.617-.242 2.32-.744 4.57-3.262 8.793-7.041 12.551-11.234 1.473-1.645 1.336-4.174-.312-5.648-1.637-1.475-4.168-1.335-5.649.308zm15.657-60.32c1.098 4.887 1.656 9.93 1.656 14.979-.004 1.453-.047 2.895-.137 4.324-.137 2.205 1.539 4.104 3.742 4.24.086.006.172.008.254.008 2.094 0 3.855-1.631 3.988-3.752.098-1.586.148-3.188.152-4.811 0-5.648-.621-11.281-1.852-16.742-.484-2.156-2.609-3.508-4.781-3.025-2.151.484-3.507 2.624-3.022 4.779zm-40.067 77.724c-4.645 1.947-9.512 3.375-14.465 4.242-2.176.381-3.629 2.453-3.25 4.629.34 1.943 2.031 3.311 3.938 3.311.227 0 .461-.02.695-.061 5.539-.969 10.98-2.564 16.176-4.742 2.035-.855 2.996-3.199 2.141-5.236-.852-2.035-3.204-2.99-5.235-2.143zm43.953-46.291c-2.121-.625-4.344.596-4.965 2.713-1.41 4.818-3.371 9.494-5.824 13.898-1.078 1.93-.383 4.365 1.547 5.441.613.344 1.281.506 1.941.506 1.402 0 2.766-.74 3.496-2.053 2.746-4.924 4.938-10.152 6.52-15.543.621-2.118-.594-4.341-2.715-4.962zm-16.109-64.097c-1.734 1.367-2.031 3.883-.664 5.617 3.121 3.963 5.797 8.27 7.961 12.803.684 1.439 2.117 2.279 3.613 2.279.574 0 1.164-.125 1.719-.389 1.992-.951 2.84-3.338 1.887-5.332-2.414-5.07-5.41-9.885-8.898-14.311-1.364-1.732-3.876-2.038-5.618-.667zm-57.359 115.576c-5.039-.234-10.047-1.031-14.887-2.365-2.105-.59-4.328.658-4.918 2.791-.59 2.131.66 4.332 2.793 4.92 5.406 1.492 11.004 2.383 16.637 2.646.062.002.129.004.191.004 2.121 0 3.891-1.67 3.992-3.812.102-2.208-1.601-4.081-3.808-4.184zm20.55-166.43c40.496 7.064 72.98 38.922 80.84 79.271.371 1.91 2.047 3.234 3.922 3.234.254 0 .512-.023.77-.074 2.168-.422 3.582-2.521 3.16-4.691-8.488-43.58-43.578-77.99-87.316-85.623-2.207-.377-4.25 1.08-4.629 3.254-.379 2.176 1.078 4.248 3.253 4.629zm-120.078 82.432c.258.051.516.074.77.074 1.875 0 3.551-1.324 3.922-3.234 7.863-40.35 40.348-72.207 80.84-79.271 2.176-.381 3.633-2.453 3.254-4.629-.379-2.174-2.414-3.629-4.629-3.254-43.734 7.633-78.824 42.041-87.316 85.623-.423 2.169.991 4.269 3.159 4.691zm205.61 33.701c-2.172-.426-4.27.992-4.691 3.162-7.859 40.348-40.344 72.205-80.84 79.27-2.176.381-3.633 2.453-3.254 4.629.34 1.943 2.027 3.312 3.938 3.312.227 0 .457-.02.691-.059 43.738-7.633 78.828-42.043 87.316-85.623.422-2.17-.992-4.269-3.16-4.691zm-120.078 82.432c-40.492-7.064-72.977-38.922-80.84-79.271-.422-2.17-2.535-3.586-4.691-3.16-2.168.422-3.582 2.521-3.16 4.691 8.492 43.582 43.582 77.99 87.316 85.623.234.039.465.059.691.059 1.906 0 3.598-1.369 3.938-3.312.378-2.177-1.079-4.25-3.254-4.63zm133.234-100.059c0 2.209-1.789 4-4 4h-20.101c-2.046 47.551-40.346 85.853-87.899 87.899v20.101c0 2.209-1.789 4-4 4s-4-1.791-4-4v-20.101c-47.553-2.046-85.853-40.348-87.899-87.899h-20.101c-2.211 0-4-1.791-4-4s1.789-4 4-4h20.101c2.046-47.551 40.346-85.853 87.899-87.899v-20.101c0-2.209 1.789-4 4-4s4 1.791 4 4v20.101c47.553 2.046 85.853 40.348 87.899 87.899h20.101c2.211 0 4 1.791 4 4zm-32 0c0-46.318-37.684-84-84-84s-84 37.682-84 84 37.684 84 84 84 84-37.682 84-84z" fill="url(#SVGID_1_)"/></g></svg>
            </span></div>
    <div class="ws-pro-title">
        <h3>Square Payment with WooCommerce Subscription</h3>
    </div>
        </div>
       
        <div class="ws-pro-para">
            <p>Process simple/recurring payments with WooCommerce with subscriptions using the Square payment form.</p>
        </div>
    </div></div>


    <div><div class="ws-pro-box ws-pro-box-f">
        <div class="ws-pro-box-hold">
            <div class="ws-pro-box-img"><span class="iconbox">
                <svg id="magic-wand" enable-background="new 0 0 300 300" height="512" viewBox="0 0 300 300" width="512" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><linearGradient id="SVGID_1_" gradientUnits="userSpaceOnUse" x1="47.242" x2="243.386" y1="261.208" y2="65.064"><stop offset="0" stop-color="#107eff"/><stop offset="1" stop-color="#8f16ff"/></linearGradient><g><path d="m186.901 217.549c0 2.209-1.791 4-4 4s-4-1.791-4-4 1.791-4 4-4 4 1.791 4 4zm24-72c-2.209 0-4 1.791-4 4s1.791 4 4 4 4-1.791 4-4-1.791-4-4-4zm-52 52c-2.209 0-4 1.791-4 4s1.791 4 4 4 4-1.791 4-4-1.791-4-4-4zm-48-120c0 2.209 1.791 4 4 4s4-1.791 4-4-1.791-4-4-4-4 1.791-4 4zm0-36c2.209 0 4-1.791 4-4s-1.791-4-4-4-4 1.791-4 4 1.791 4 4 4zm122.828 136.484c.781.781 1.805 1.172 2.828 1.172s2.047-.391 2.828-1.172c1.562-1.562 1.562-4.094 0-5.656l-2.828-2.828 2.828-2.828c1.562-1.562 1.562-4.094 0-5.656s-4.094-1.562-5.656 0l-2.828 2.828-2.828-2.828c-1.562-1.562-4.094-1.562-5.656 0s-1.562 4.094 0 5.656l2.828 2.828-2.828 2.828c-1.562 1.562-1.562 4.094 0 5.656.781.781 1.805 1.172 2.828 1.172s2.047-.391 2.828-1.172l2.828-2.828zm-111.312-56c.781.781 1.805 1.172 2.828 1.172s2.047-.391 2.828-1.172l2.828-2.828 2.828 2.828c.781.781 1.805 1.172 2.828 1.172s2.047-.391 2.828-1.172c1.562-1.562 1.562-4.094 0-5.656l-2.828-2.828 2.828-2.828c1.562-1.562 1.562-4.094 0-5.656s-4.094-1.562-5.656 0l-2.828 2.828-2.828-2.828c-1.562-1.562-4.094-1.562-5.656 0s-1.562 4.094 0 5.656l2.828 2.828-2.828 2.828c-1.563 1.562-1.563 4.094 0 5.656zm99.312-56c.781.781 1.805 1.172 2.828 1.172s2.047-.391 2.828-1.172c1.562-1.562 1.562-4.094 0-5.656l-2.828-2.828 2.828-2.828c1.562-1.562 1.562-4.094 0-5.656s-4.094-1.562-5.656 0l-2.828 2.828-2.828-2.828c-1.562-1.562-4.094-1.562-5.656 0s-1.562 4.094 0 5.656l2.828 2.828-2.828 2.828c-1.562 1.562-1.562 4.094 0 5.656.781.781 1.805 1.172 2.828 1.172s2.047-.391 2.828-1.172l2.828-2.828zm-66.828-4.484c2.211 0 4-1.791 4-4v-12c0-2.209-1.789-4-4-4s-4 1.791-4 4v12c0 2.209 1.789 4 4 4zm0 8c-2.211 0-4 1.791-4 4v12c0 2.209 1.789 4 4 4s4-1.791 4-4v-12c0-2.209-1.789-4-4-4zm-20 0h12c2.211 0 4-1.791 4-4s-1.789-4-4-4h-12c-2.211 0-4 1.791-4 4s1.789 4 4 4zm28 0h12c2.211 0 4-1.791 4-4s-1.789-4-4-4h-12c-2.211 0-4 1.791-4 4s1.789 4 4 4zm-72 48c6.617 0 12 5.383 12 12s-5.383 12-12 12-12-5.383-12-12 5.383-12 12-12zm0 8c-2.207 0-4 1.795-4 4s1.793 4 4 4 4-1.795 4-4-1.793-4-4-4zm120 56c0 6.617-5.383 12-12 12s-12-5.383-12-12 5.383-12 12-12 12 5.383 12 12zm-8 0c0-2.205-1.793-4-4-4s-4 1.795-4 4 1.793 4 4 4 4-1.795 4-4zm52 36c0 6.617-5.383 12-12 12s-12-5.383-12-12 5.383-12 12-12 12 5.383 12 12zm-8 0c0-2.205-1.793-4-4-4s-4 1.795-4 4 1.793 4 4 4 4-1.795 4-4zm-176-144c0-6.617 5.383-12 12-12s12 5.383 12 12-5.383 12-12 12-12-5.383-12-12zm8 0c0 2.205 1.793 4 4 4s4-1.795 4-4-1.793-4-4-4-4 1.795-4 4zm128.828 44.969-30.504 30.504-110.242 114.085c-2.141 2.215-5.039 3.344-7.949 3.344-2.473 0-4.949-.816-6.977-2.477l-10.918-8.945c-2.461-2.016-3.93-4.996-4.035-8.176-.102-3.178 1.172-6.246 3.496-8.418l116.926-109.307 30.406-30.41c3.344-3.348 9.188-3.348 12.531 0l7.266 7.268c1.672 1.674 2.594 3.898 2.594 6.266s-.918 4.592-2.594 6.266zm-38.939 27.724-8.629-8.63-114.1 106.669c-.875.818-.977 1.801-.961 2.312.016.514.184 1.488 1.109 2.248l10.918 8.945c1.227 1.004 2.992.906 4.105-.24zm74.111-36.693c2.211 0 4-1.791 4-4v-8c0-2.209-1.789-4-4-4s-4 1.791-4 4v8c0 2.209 1.789 4 4 4zm0 8c-2.211 0-4 1.791-4 4v8c0 2.209 1.789 4 4 4s4-1.791 4-4v-8c0-2.209-1.789-4-4-4zm-20-4c0 2.209 1.789 4 4 4h8c2.211 0 4-1.791 4-4s-1.789-4-4-4h-8c-2.211 0-4 1.791-4 4zm36-4h-8c-2.211 0-4 1.791-4 4s1.789 4 4 4h8c2.211 0 4-1.791 4-4s-1.789-4-4-4z" fill="url(#SVGID_1_)"/></g></svg>
            </span></div>
            <div class="ws-pro-title">
                <h3>Advance Attribute Support</h3>
            </div>
        </div>
        
        <div class="ws-pro-para">
            <p>WooSquare Plus allows you to sync single and multiple attributes for your simple or variable products.</p>
        </div>
    </div></div>


    <div>  <div class="ws-pro-box ws-pro-box-f">
        <div class="ws-pro-box-hold">
            <div class="ws-pro-box-img"><span class="iconbox">
                <svg id="arboards" enable-background="new 0 0 300 300" height="512" viewBox="0 0 300 300" width="512" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><linearGradient id="SVGID_1_" gradientUnits="userSpaceOnUse" x1="77.171" x2="226.828" y1="250.829" y2="101.172"><stop offset="0" stop-color="#107eff"/><stop offset="1" stop-color="#8f16ff"/></linearGradient><g><path d="m252.828 127.172-32-32c-.75-.75-1.767-1.172-2.828-1.172h-128c-2.209 0-4 1.789-4 4v160c0 2.211 1.791 4 4 4h160c2.209 0 4-1.789 4-4v-128c0-1.062-.422-2.078-1.172-2.828zm-158.828 126.828v-152h120v28c0 2.211 1.791 4 4 4h28v120zm-23.516-118.828-2.828 2.828 2.828 2.828c1.562 1.562 1.562 4.094 0 5.656-.781.781-1.805 1.172-2.828 1.172s-2.047-.391-2.828-1.172l-2.828-2.828-2.828 2.828c-.781.781-1.805 1.172-2.828 1.172s-2.047-.391-2.828-1.172c-1.562-1.562-1.562-4.094 0-5.656l2.828-2.828-2.828-2.828c-1.562-1.562-1.562-4.094 0-5.656s4.094-1.562 5.656 0l2.828 2.828 2.828-2.828c1.562-1.562 4.094-1.562 5.656 0s1.563 4.093 0 5.656zm71.516-73.172c6.617 0 12-5.383 12-12s-5.383-12-12-12-12 5.383-12 12 5.383 12 12 12zm0-16c2.205 0 4 1.793 4 4s-1.795 4-4 4-4-1.793-4-4 1.795-4 4-4zm32 32c0-2.209 1.791-4 4-4s4 1.791 4 4-1.791 4-4 4-4-1.791-4-4zm-88 8v-28c0-2.211 1.791-4 4-4s4 1.789 4 4v28c0 2.211-1.791 4-4 4s-4-1.789-4-4zm-4 12c0 2.211-1.791 4-4 4h-28c-2.209 0-4-1.789-4-4s1.791-4 4-4h28c2.209 0 4 1.789 4 4z" fill="url(#SVGID_1_)"/></g></svg>
            </span></div>
        <div class="ws-pro-title">
            <h3>Detail Documentation for Everything</h3>
        </div>
        </div>
        
        <div class="ws-pro-para">
            <p>WooSquare Plus features are extensively covered in the comprehensive guide and technical documentation.</p> </div>
    </div></div>


    <div> <div class="ws-pro-box ws-pro-box-f">
        <div class="ws-pro-box-hold">
            <div class="ws-pro-box-img"><span class="iconbox">
                <svg id="business-card" enable-background="new 0 0 300 300" height="512" viewBox="0 0 300 300" width="512" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><linearGradient id="SVGID_1_" gradientUnits="userSpaceOnUse" x1="74.125" x2="250.435" y1="242.492" y2="66.182"><stop offset="0" stop-color="#107eff"/><stop offset="1" stop-color="#8f16ff"/></linearGradient><g><path d="m134.001 222.739c-7.719 0-14 6.281-14 14s6.281 14 14 14 14-6.281 14-14-6.281-14-14-14zm0 20c-3.309 0-6-2.691-6-6s2.691-6 6-6 6 2.691 6 6-2.692 6-6 6zm120.276-6c0 2.362-1.915 4.276-4.276 4.276s-4.276-1.915-4.276-4.276 1.915-4.277 4.276-4.277 4.276 1.915 4.276 4.277zm-164.288-171.613 4.529-4.513-4.509-4.526c-1.562-1.566-1.555-4.098.008-5.656 1.566-1.562 4.098-1.559 5.656.008l4.511 4.528 4.528-4.512c1.566-1.562 4.098-1.559 5.656.008 1.562 1.566 1.555 4.098-.008 5.656l-4.531 4.515 4.511 4.528c1.562 1.566 1.555 4.098-.008 5.656-.781.781-1.805 1.168-2.824 1.168-1.027 0-2.051-.391-2.832-1.176l-4.513-4.53-4.526 4.51c-.781.777-1.805 1.168-2.824 1.168-1.027 0-2.051-.391-2.832-1.176-1.562-1.566-1.554-4.098.008-5.656zm173.473 17.613h-146.922c-4.707 0-8.539 3.832-8.539 8.539v43.461h-6.699c-.008 0-.414 0-.422 0-17.918.113-34.66 9.566-44.793 25.289l-8.09 12.555-18.008 10.477c-1.91 1.109-2.559 3.559-1.445 5.469 1.109 1.906 3.559 2.547 5.469 1.445l18.84-10.961c.547-.316 1.008-.758 1.352-1.289l8.609-13.359c8.66-13.445 22.91-21.531 38.488-21.625h77.293c4.695 0 7.707 2.336 8.152 4.535.57 2.816-2.762 6.203-8.492 8.633-3.625 1.535-10.512 2.832-15.039 2.832h-15.215c-8.262 0-16.039 9.723-16.039 17.262 0 8.125 6.457 14.738 14.395 14.738h21.762c11.379 0 22.477-4.965 30.445-13.625l43.809-47.59c2.934-3.184 7.781-3.703 10.82-1.16 1.621 1.359 2.609 3.27 2.781 5.379.176 2.121-.488 4.168-1.863 5.77l-47.043 54.66c-6.715 7.801-16.426 12.273-26.641 12.273h-66.664c-7.137 0-14.32 1.621-20.781 4.688-4.711 2.234-19.371 16.449-23.754 20.75-1.574 1.551-1.598 4.082-.051 5.66.785.797 1.82 1.195 2.855 1.195 1.012 0 2.023-.383 2.805-1.145 8.52-8.371 19.191-18.102 21.574-19.234 5.395-2.559 11.395-3.914 17.352-3.914h66.664c12.547 0 24.465-5.488 32.703-15.055l17.774-20.652h32.558c4.707 0 8.539-3.832 8.539-8.539v-78.923c0-4.707-3.832-8.539-8.539-8.539zm.539 87.461c0 .297-.242.539-.539.539h-25.673l22.384-26.008c2.785-3.238 4.125-7.371 3.777-11.641-.352-4.258-2.348-8.113-5.625-10.859-6.262-5.242-16.055-4.402-21.836 1.871l-42.928 46.637h-51.427c1.671-2.221 3.987-4 5.866-4h15.215c4.906 0 13.039-1.297 18.16-3.465 14.34-6.078 13.738-15.004 13.211-17.594-1.309-6.441-7.887-10.941-15.992-10.941h-62.594v-43.461c0-.297.242-.539.539-.539h146.922c.297 0 .539.242.539.539v78.922zm-140-67.461c0-2.211 1.789-4 4-4h56c2.211 0 4 1.789 4 4s-1.789 4-4 4h-56c-2.211 0-4-1.789-4-4zm32 12c0 2.211-1.789 4-4 4h-24c-2.211 0-4-1.789-4-4s1.789-4 4-4h24c2.211 0 4 1.789 4 4zm56 0c0 2.211-1.789 4-4 4h-40c-2.211 0-4-1.789-4-4s1.789-4 4-4h40c2.211 0 4 1.789 4 4z" fill="url(#SVGID_1_)"/></g></svg>
            </span></div>
    <div class="ws-pro-title">
        <h3>Save Cards</h3>
    </div>
        </div>
        
        <div class="ws-pro-para">
            <p>Users can save their card details at the time of checkout, and use them in the future whenever they want.</p>
        </div>
    </div></div>


    <div>
        <div class="ws-pro-box ws-pro-box-f">
            <div class="ws-pro-box-hold">
                <div class="ws-pro-box-img"><span class="iconbox">
                   <svg id="flyer" enable-background="new 0 0 300 300" height="512" viewBox="0 0 300 300" width="512" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><linearGradient id="SVGID_1_" gradientUnits="userSpaceOnUse" x1="44.345" x2="246.002" y1="238.831" y2="37.173"><stop offset="0" stop-color="#107eff"/><stop offset="1" stop-color="#8f16ff"/></linearGradient><g><path d="m264 56.828h-120c-2.211 0-4 1.791-4 4v36h-68c-2.211 0-4 1.791-4 4v160c0 2.209 1.789 4 4 4h120c2.211 0 4-1.791 4-4v-36h68c2.211 0 4-1.791 4-4v-160c0-2.209-1.789-4-4-4zm-116 12.541 32.951 27.459h-32.951zm68 23.459c0-8.822 7.176-16 16-16s16 7.178 16 16-7.176 16-16 16-16-7.178-16-16zm-28 164h-112v-152h112zm72-40h-64v-28h28c2.211 0 4-1.791 4-4s-1.789-4-4-4h-28v-36h48c2.211 0 4-1.791 4-4s-1.789-4-4-4h-48v-21.425c2.556.906 5.242 1.425 8 1.425 5.036 0 9.885-1.667 13.949-4.584 3.955 2.87 8.801 4.584 14.051 4.584 13.234 0 24-10.766 24-24s-10.766-24-24-24c-5.251 0-10.098 1.715-14.053 4.586-4.064-2.917-8.903-4.586-13.947-4.586-10.997 0-20.265 7.442-23.094 17.548l-25.857-21.548h104.951zm-12-32c0 2.209-1.789 4-4 4h-8c-2.211 0-4-1.791-4-4s1.789-4 4-4h8c2.211 0 4 1.791 4 4zm-144.484-137.172 2.828-2.828-2.828-2.828c-1.562-1.562-1.562-4.094 0-5.656s4.094-1.562 5.656 0l2.828 2.828 2.828-2.828c1.562-1.562 4.094-1.562 5.656 0s1.562 4.094 0 5.656l-2.828 2.828 2.828 2.828c1.562 1.562 1.562 4.094 0 5.656-.781.781-1.805 1.172-2.828 1.172s-2.047-.391-2.828-1.172l-2.828-2.828-2.828 2.828c-.781.781-1.805 1.172-2.828 1.172s-2.047-.391-2.828-1.172c-1.563-1.562-1.563-4.093 0-5.656zm-59.516-10.828c-6.617 0-12 5.383-12 12s5.383 12 12 12 12-5.383 12-12-5.383-12-12-12zm0 16c-2.207 0-4-1.795-4-4s1.793-4 4-4 4 1.795 4 4-1.793 4-4 4zm32 24c0-2.209 1.791-4 4-4s4 1.791 4 4-1.791 4-4 4-4-1.791-4-4zm12 48c0-2.209 1.789-4 4-4h32c2.211 0 4 1.791 4 4s-1.789 4-4 4h-32c-2.211 0-4-1.791-4-4zm0 12c0-2.209 1.789-4 4-4h56c2.211 0 4 1.791 4 4s-1.789 4-4 4h-56c-2.211 0-4-1.791-4-4zm0 32c0-2.209 1.789-4 4-4h20c2.211 0 4 1.791 4 4s-1.789 4-4 4h-20c-2.211 0-4-1.791-4-4zm36-4h48c2.211 0 4 1.791 4 4s-1.789 4-4 4h-48c-2.211 0-4-1.791-4-4s1.789-4 4-4zm32 48h-16v-20c0-2.209-1.789-4-4-4h-20c-2.211 0-4 1.791-4 4v12h-16c-2.211 0-4 1.791-4 4v28c0 2.209 1.789 4 4 4h60c2.211 0 4-1.791 4-4v-20c0-2.209-1.789-4-4-4zm-56 0h12v20h-12zm52 20h-12v-12h12zm20-36v40c0 2.209-1.789 4-4 4s-4-1.791-4-4v-40c0-2.209 1.789-4 4-4s4 1.791 4 4z" fill="url(#SVGID_1_)"/></g></svg>
                </span></div>
                <div class="ws-pro-title">
                    <h3>Transaction Notes</h3>
                </div>
            </div>
            
            <div class="ws-pro-para">
                <p>Send dynamic Transaction notes with WooCommerce checkout fields & Order ID after every transaction.</p>
            </div>
    
        </div>
    </div>

    <div>  <div class="ws-pro-box ws-pro-box-f">
        <div class="ws-pro-box-hold">
            <div class="ws-pro-box-img"><span class="iconbox">
                <svg id="multiply" enable-background="new 0 0 300 300" height="512" viewBox="0 0 300 300" width="512" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><linearGradient id="SVGID_1_" gradientUnits="userSpaceOnUse" x1="32.572" x2="240.229" y1="248.23" y2="40.573"><stop offset="0" stop-color="#107eff"/><stop offset="1" stop-color="#8f16ff"/></linearGradient><g><path d="m186.1 244c0 2.264-1.836 4.099-4.1 4.099s-4.1-1.835-4.1-4.099 1.836-4.099 4.1-4.099 4.1 1.835 4.1 4.099zm46.728-71.516c.781.781 1.805 1.172 2.828 1.172s2.047-.391 2.828-1.172c1.562-1.562 1.562-4.094 0-5.656l-2.828-2.828 2.828-2.828c1.562-1.562 1.562-4.094 0-5.656s-4.094-1.562-5.656 0l-2.828 2.828-2.828-2.828c-1.562-1.562-4.094-1.562-5.656 0s-1.562 4.094 0 5.656l2.828 2.828-2.828 2.828c-1.562 1.562-1.562 4.094 0 5.656.781.781 1.805 1.172 2.828 1.172s2.047-.391 2.828-1.172l2.828-2.828zm-92-127.312c.75.75 1.172 1.767 1.172 2.828v84c0 2.209-1.789 4-4 4h-48c-2.211 0-4-1.791-4-4s1.789-4 4-4h44v-76h-16c-2.211 0-4-1.791-4-4v-16h-48v74.119c0 2.209-1.789 4-4 4s-4-1.791-4-4v-78.119c0-2.209 1.789-4 4-4h56c1.062 0 2.078.422 2.828 1.172zm-90.828 90.828h8v8c0 2.209 1.789 4 4 4s4-1.791 4-4v-8h8c2.211 0 4-1.791 4-4s-1.789-4-4-4h-8v-8c0-2.209-1.789-4-4-4s-4 1.791-4 4v8h-8c-2.211 0-4 1.791-4 4s1.789 4 4 4zm112-29.881v-78.119c0-2.209 1.789-4 4-4h56c1.062 0 2.078.422 2.828 1.172l20 20c.75.75 1.172 1.767 1.172 2.828v84c0 2.209-1.789 4-4 4h-48c-2.211 0-4-1.791-4-4s1.789-4 4-4h44v-76h-16c-2.211 0-4-1.791-4-4v-16h-48v74.119c0 2.209-1.789 4-4 4s-4-1.791-4-4zm16 29.881c2.211 0 4-1.791 4-4s-1.789-4-4-4h-8v-8c0-2.209-1.789-4-4-4s-4 1.791-4 4v8h-8c-2.211 0-4 1.791-4 4s1.789 4 4 4h8v8c0 2.209 1.789 4 4 4s4-1.791 4-4v-8zm-37.172 37.172c.75.75 1.172 1.768 1.172 2.828v84c0 2.209-1.789 4-4 4h-48c-2.211 0-4-1.791-4-4s1.789-4 4-4h44v-76h-16c-2.211 0-4-1.791-4-4v-16h-48v74.119c0 2.209-1.789 4-4 4s-4-1.791-4-4v-78.119c0-2.209 1.789-4 4-4h56c1.062 0 2.078.422 2.828 1.172zm-66.828 82.828h-8v-8c0-2.209-1.789-4-4-4s-4 1.791-4 4v8h-8c-2.211 0-4 1.791-4 4s1.789 4 4 4h8v8c0 2.209 1.789 4 4 4s4-1.791 4-4v-8h8c2.211 0 4-1.791 4-4s-1.789-4-4-4zm94.102-63.402c-1.18-.732-2.652-.795-3.891-.176l-8 4c-1.977.988-2.777 3.391-1.789 5.367.992 1.979 3.391 2.781 5.367 1.789l2.211-1.105v21.527c0 2.209 1.789 4 4 4s4-1.791 4-4v-28c0-1.387-.719-2.674-1.898-3.402zm29.898 7.482v19.84c0 4.455-3.625 8.08-8.078 8.08h-7.844c-4.453 0-8.078-3.625-8.078-8.08v-19.84c0-4.455 3.625-8.08 8.078-8.08h7.844c4.453 0 8.078 3.625 8.078 8.08zm-8 0-7.922-.08-.078 19.92 7.922.08c.039 0 .078-.031.078-.08zm36 0v19.84c0 4.455-3.625 8.08-8.078 8.08h-7.844c-4.453 0-8.078-3.625-8.078-8.08v-19.84c0-4.455 3.625-8.08 8.078-8.08h7.844c4.453 0 8.078 3.625 8.078 8.08zm-8 0-7.922-.08-.078 19.92 7.922.08c.039 0 .078-.031.078-.08zm36 0v19.84c0 4.455-3.625 8.08-8.078 8.08h-7.844c-4.453 0-8.078-3.625-8.078-8.08v-19.84c0-4.455 3.625-8.08 8.078-8.08h7.844c4.453 0 8.078 3.625 8.078 8.08zm-8 0-7.922-.08-.078 19.92 7.922.08c.039 0 .078-.031.078-.08z" fill="url(#SVGID_1_)"/></g></svg>
            </span></div>
    <div class="ws-pro-title">
        <h3>Customer Synchronization</h3>
    </div>
        </div>
        
        <div class="ws-pro-para">
            <p>Sync your customers between the two and link them to their orders appearing in WooCommerce from Square.</p>
        </div>
    </div></div>

    </div>


   
    
    
  
   
    
  
</div>
		
	
<div  class="ws-download-buy ws-download-buy-wrap text-center">
    <a target="_blank" href="https://apiexperts.io/solutions/woosquare-plus/?utm_source=WordPress&utm_medium=PluginSale&utm_campaign=InStore" class="btn btn-rounded waves-effect waves-light btn-primary width">Buy WooSquare Plus</a>
    
<a target="_blank" href="https://apiexperts.io/woosquare-plus-documentation/?utm_source=WordPress&utm_medium=PluginSale&utm_campaign=InStore" class="btn btn-rounded waves-effect waves-light btn-danger width">WooSquare Plus Documentation</a>

</div>
		
	</div></div>';
	$html .= '<div class="buyBoxWrapper clearfix">
        <div id="woosquare_integration_more" class="ws-pro-wrapper">
            <div class="ws-head-txt"><h1> <span class="smalltext">Looking For More</span> <a href="https://apiexperts.io/solutions/">Square Integrations</a></h1>
            </div>
        <div id="main_box_row" class="clearfix">
		
        <div class="ws-pro-box">
            <div class="ws-pro-box-hold">
                <div class="ws-pro-box-img"><img src="https://apiexperts.io/wp-content/uploads/2018/01/give-quare.jpg" alt="SQUARE FOR GIVEWP"></div>
            <div class="ws-pro-title"><h3>Square for GiVEWP</h3></div>
            <div class="ws-pro-para"><p>WordPress plugin that allows users to pay for their donations using Square payment gateway through GiveWP..</p></div>

            </div>
                    <div class="clearfix"><a class="btn btn-rounded waves-effect waves-light btn-block btn-danger" target="_blank" href="https://apiexperts.io/solutions/square-for-givewp/?utm_source=WordPress&utm_medium=PluginSale&utm_campaign=InStore">Buy Now</a></div>
        </div>
        


        <div class="ws-pro-box">
            <div class="ws-pro-box-hold">
                <div class="ws-pro-box-img"><img src="https://apiexperts.io/wp-content/uploads/2018/01/gravity-forms-square-payment-gateway.png" alt="SQUARE FOR
                    GRAVITY FORMS"></div>
                                <div class="ws-pro-title"><h3>Square for Gravity forms</h3></div>
                                <div class="ws-pro-para"><p>WordPress plugin that allows users to pay from their gravity form using Square payment gateway..</p></div>
                            
            </div>

        
            <div class="clearfix"><a class="btn btn-rounded waves-effect waves-light btn-block btn-danger" target="_blank"  href="https://apiexperts.io/solutions/pay-with-square-in-gravity-forms/?utm_source=WordPress&utm_medium=PluginSale&utm_campaign=InStore">Buy Now</a></div>
		</div>
		<div class="ws-pro-box">

            <div class="ws-pro-box-hold">
                <div class="ws-pro-box-img"><img src="https://apiexperts.io/wp-content/uploads/2018/04/square-payment-getway-for-ninja-forms.png" alt="Square for Ninja forms"></div>
                <div class="ws-pro-title"><h3>Square integration with Ninja forms</h3></div>
                <div class="ws-pro-para"><p>Ninja Forms Square plugin is a WordPress plugin that allow customers to pay for..</p></div>
            
            </div>


            <div class="clearfix"><a class="btn btn-rounded waves-effect waves-light btn-block btn-danger" target="_blank"  href="https://apiexperts.io/solutions/square-integration-with-ninja-forms/?utm_source=WordPress&utm_medium=PluginSale&utm_campaign=InStore">Buy Now</a></div>
		</div>
		        <div class="ws-pro-box">
        
                    <div class="ws-pro-box-hold">
                        <div class="ws-pro-box-img"><img src="https://apiexperts.io/wp-content/uploads/2018/05/gravity-forms-square-recurring-1-300x300.png" alt="SQUARE FOR
                            GRAVITY FORMS"></div>
                                        <div class="ws-pro-title"><h3>Gravity forms with Square Recurring Payment</h3></div>
                                        <div class="ws-pro-para"><p>Gravity Form Square Recurring plugin is a WordPress plugin that allow customers to..</p></div>
                                    

                    </div>
        
       
        
        
            <div class="clearfix"><a class="btn btn-rounded waves-effect waves-light btn-block btn-danger" target="_blank"  href="https://apiexperts.io/solutions/gravity-forms-with-square-recurring-payment/?utm_source=WordPress&utm_medium=PluginSale&utm_campaign=InStore">Buy Now</a></div>
		</div>
		        <div class="ws-pro-box">

                    <div class="ws-pro-box-hold">

                        <div class="ws-pro-box-img"><img src="https://apiexperts.io/wp-content/uploads/2018/04/caldera-forms-with-square.png" alt="SQUARE FOR
                            GRAVITY FORMS"></div>
                                        <div class="ws-pro-title"><h3 >Pay with Square in Caldera form</h3></div>
                                        <div class="ws-pro-para"><p>Square For Caldera Form plugin is a WordPress plugin that allows users to pay for their caldera form using Square payment..</p></div>
                                   

                    </div>

  
       
            <div class="clearfix"><a class="btn btn-rounded waves-effect waves-light btn-block btn-danger" target="_blank"  href="https://apiexperts.io/solutions/pay-with-square-in-caldera-form/?utm_source=WordPress&utm_medium=PluginSale&utm_campaign=InStore">Buy Now</a></div>
		</div>		
				        <div class="ws-pro-box">

                            <div class="ws-pro-box-hold">

                                <div class="ws-pro-box-img"><img src="http://apiexperts.io/wp-content/uploads/2018/08/easy-digital-downloads-with-square-getway-1.png" alt="SQUARE FOR
                                    GRAVITY FORMS"></div>
                                                <div class="ws-pro-title"><h3>Easy Digital downloads with Square</h3></div>
                                                <div class="ws-pro-para"><p>Square Easy Digital Downloads EDD Payment plugin is a WordPress plugin that allows you to pay for your digital downloads..</p></div>
                                            
                            </div>


        
            <div class="clearfix"><a class="btn btn-rounded waves-effect waves-light btn-block btn-danger" target="_blank"  href="https://apiexperts.io/solutions/easy-digital-downloads-with-square/?utm_source=WordPress&utm_medium=PluginSale&utm_campaign=InStore">Buy Now</a></div>
		</div>	
		<div class="ws-pro-box">

            <div class="ws-pro-box-hold">

                <div class="ws-pro-box-img"><img src="https://apiexperts.io/wp-content/uploads/2018/04/wp-easy-pay-for-square-1-285x300.png" alt="SQUARE FOR
                    GRAVITY FORMS"></div>
                                <div class="ws-pro-title"><h3>WP EasyPay for Wordpress</h3></div>
                                <div class="ws-pro-para"><p>WordPress Easy Pay plugin allows you to easily create Buy Now, Donation or Subscription type buttons. It generates dynamic buttons using shortcodes that..</p></div>
                            
            </div>

        
            <div class="clearfix"><a class="btn btn-rounded waves-effect waves-light btn-block btn-danger" target="_blank"  href="https://apiexperts.io/solutions/wp-easy-pay-wordpress/?utm_source=WordPress&utm_medium=PluginSale&utm_campaign=InStore">Buy Now</a></div>
		</div>	
		
		<div class="ws-pro-box">

            <div class="ws-pro-box-hold">

                <div class="ws-pro-box-img"><img style="width: 23%;" src="https://apiexperts.io/wp-content/uploads/2019/12/square-payment-getway-contact-form7-300x259-1.png" alt="SQUARE FOR
                    GRAVITY FORMS"></div>
                            <div class="ws-pro-title"><h3>Contact form 7 Square Payment addon</h3></div>
                            <div class="ws-pro-para"><p>Contact Form 7 Square plugin is a WordPress plugin that allows users to pay from their..</p></div>
                    

            </div>


    
            <a class="btn btn-rounded waves-effect waves-light btn-block btn-danger" target="_blank"  href="http://wpexperts.io/link/contact-form-7-square-payment-addon/?utm_source=WordPress&utm_medium=PluginSale&utm_campaign=InStore">Buy Now</a>
		</div>
        </div>
    </div>
    </div></div>   
    <div style="display: none;" class="modal fade videoWrap" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
          <div class="modal-body">
              <iframe width="100%" height="250" src="https://www.youtube.com/embed/TEAQ-H65inE" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
  
          </div>
          
        </div>
      </div>
    </div>';   
    echo $html;
}