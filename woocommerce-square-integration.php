<?php 
/*
  Plugin Name: WooSquare
  Plugin URI: https://wpexperts.io/products/woosquare/
  Description: WooSquare purpose is to migrate & synchronize data (sales customers-invoices-products inventory) between Square system point of sale & Woo commerce plug-in. 
  Version: 3.9
  Author: Wpexpertsio
  Author URI: https://wpexperts.io/
  License: GPLv2 or later
  @fs_premium_only /premium-files/
 */

require_once( '_inc/square_freemius.php' );
global $woosquare_fs;

if( !function_exists('get_plugin_data') ){
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}
$plugin_data = get_plugin_data( __FILE__ );
if (woosquare_fs()->can_use_premium_code()) {
	$WOOSQU_PLUS_PLUGIN_NAME = $plugin_data['Name'];
	if (!defined('WOOSQU_PLUS_PLUGIN_NAME')) define('WOOSQU_PLUS_PLUGIN_NAME',$WOOSQU_PLUS_PLUGIN_NAME);
	
	 define( 'WooSquare_VERSION',$plugin_data['Version']);
	$filename = dirname(__FILE__) . '/premium-files/woosquare-plus.php';
	if (file_exists($filename)) {
		require_once($filename);
	} else { 
		$class = 'notice notice-error';
		$message = __( ' To start using WooSquare Plus, please <a href="https://users.freemius.com" target="_blank"> login to your freemius account</a> in order to download the pro version <br /> For more details <a target="_blank" href="https://apiexperts.io/woosquare-plus-documentation/#11-upgrade-woosquare-plus/">Click here</a>!', 'woosquare' );
		printf( '<br><div class="%1$s"><p>%2$s</p> <a href='.admin_url().'>%3$s</a></div>', esc_attr( $class ),  $message , esc_html( 'Back!' ) ); 
		include_once(ABSPATH . 'wp-includes/pluggable.php');
		deactivate_plugins('woosquare-premium/woocommerce-square-integration.php');   
		deactivate_plugins('woosquare/woocommerce-square-integration.php');   
		wp_die();
		
	} 
} else {

	if (!defined('ABSPATH')) {
		exit; // Exit if accessed directly
	}


	function report_error() {

		$class = 'notice notice-error ';
		if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) && (!in_array('mycred/mycred.php', apply_filters('active_plugins', get_option('active_plugins'))))) {

				$message = __( 'To use "WooSquare -  Square Integration <a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a> or <a href="https://wordpress.org/plugins/mycred/" target="_blank">Mycred</a> must be activated or installed!', 'woosquare' );
				printf( '<br><div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message ,  esc_html( 'Back!' ) );

		}

		if (version_compare( PHP_VERSION, '5.5.0', '<' )) {
			$message = __( 'To use "WooSquare - WooCommerce Square Integration" PHP version 5.5.0+, Current version is: ' . PHP_VERSION . "\n", 'woosquare' );
			printf( '<br><div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );

		}

		if(in_array('woosquare-pro/woocommerce-square-integration.php', apply_filters('active_plugins', get_option('active_plugins')))){
			$message = __( 'To use "WooSquare - WooCommerce Square Integration Free deactivate Pro version!', 'woosquare' );
			printf( '<br><div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );

		}

		deactivate_plugins('woosquare/woocommerce-square-integration.php');
		deactivate_plugins('woosquare-premium/woocommerce-square-integration.php');
		wp_die('','Plugin Activation Error',  array( 'response'=>200, 'back_link'=>TRUE ) );

	}



	if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))
		and
			(!in_array('mycred/mycred.php', apply_filters('active_plugins', get_option('active_plugins'))))
			or
		version_compare( PHP_VERSION, '5.5.0', '<' )
		or
			(in_array('woosquare-pro/woocommerce-square-integration.php', apply_filters('active_plugins', get_option('active_plugins'))))
		) {

			add_action( 'admin_notices', 'report_error' );

	} else {
	define('WOO_SQUARE_VERSION','3.0');	
	define('WOO_SQUARE_PLUGIN_URL_FREE', plugin_dir_url(__FILE__));
	define('WOO_SQUARE_PLUGIN_PATH', plugin_dir_path(__FILE__));

	define('WOO_SQUARE_TABLE_DELETED_DATA','woo_square_integration_deleted_data');
	define('WOO_SQUARE_TABLE_SYNC_LOGS','woo_square_integration_logs');
	define( 'WooSquare_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );


	$WOOSQU_PLUGIN_NAME = $plugin_data['Name'];
	if (!defined('WOOSQU_PLUGIN_NAME')) define('WOOSQU_PLUGIN_NAME',$WOOSQU_PLUGIN_NAME);
	if (!defined('WOOSQU_CONNECTURL')) define('WOOSQU_CONNECTURL','http://connect.apiexperts.io');
	if (!defined('WOOSQU_APPID')) define('WOOSQU_APPID','sq0idp-OkzqrnM_vuWKYJUvDnwT-g');
	if (!defined('WOOSQU_APPNAME')) define('WOOSQU_APPNAME','API Experts');

	$woocommerce_square_settings = get_option('woocommerce_square_settings');
	if(!empty(get_option('woocommerce_square_settings'))){
		if($woocommerce_square_settings['enable_sandbox'] == 'yes'){
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
	}

	//max sync running time
	define('WOO_SQUARE_MAX_SYNC_TIME',600*200);
	define( 'WooSquare_VERSION', '1.0.11' );
	add_action('admin_menu', 'woo_square_settings_page');
	add_action('admin_enqueue_scripts', 'woo_square_script');
	add_action('wp_ajax_manual_sync', "woo_square_manual_sync");

	add_action( 'init', 'public_init_woosquare',1 );

	$sync_on_add_edit = get_option( 'sync_on_add_edit', $default = false ) ;
	if($sync_on_add_edit == '1'){
		add_action('save_post', 'woo_square_add_edit_product', 10, 3);
		add_action('before_delete_post', 'woo_square_delete_product');
		add_action('delete_product_cat', 'woo_square_delete_category',10,3);
		add_action('create_product_cat', 'woo_square_add_category');
		add_action('edited_product_cat', 'woo_square_edit_category');
	}

	add_action('woocommerce_order_refunded', 'woo_square_create_refund', 10, 2);
	add_action('woocommerce_order_status_processing', 'woo_square_complete_order');

	add_action( 'wp_loaded','woo_square_post_savepage_load_admin_notice' );
	add_action( 'admin_notices', 'admin_notice_square' );
	add_action( 'init', 'get_access_token_woosquare_renewed' );
	add_action('admin_footer','woosquare_notice_script');
	add_action('wp_ajax_dismiss_woosquare_notice', 'dismiss_woosquare_notice_action');												  
																				   
 
 
	register_activation_hook(__FILE__, 'square_plugin_activation_free');

	//import classes

	require_once WOO_SQUARE_PLUGIN_PATH . '_inc/square.class.php';
	require_once WOO_SQUARE_PLUGIN_PATH . '_inc/Helpers.class.php';
	require_once WOO_SQUARE_PLUGIN_PATH . '_inc/WooToSquareSynchronizer.class.php';
	require_once WOO_SQUARE_PLUGIN_PATH . '_inc/SquareToWooSynchronizer.class.php';
	require_once WOO_SQUARE_PLUGIN_PATH . '/_inc/admin/ajax.php';
	require_once WOO_SQUARE_PLUGIN_PATH . '/_inc/admin/pages.php';


	add_action( 'plugins_loaded', 'payment_init' );
	function payment_init(){
		require_once WOO_SQUARE_PLUGIN_PATH . '/_inc/SquareClient.class.php' ;
		require_once WOO_SQUARE_PLUGIN_PATH . '/_inc/SquareSyncLogger.class.php' ;
		require_once WOO_SQUARE_PLUGIN_PATH . '/_inc/payment/SquarePaymentLogger.class.php' ;
		require_once WOO_SQUARE_PLUGIN_PATH . '/_inc/payment/SquarePayments.class.php' ;
	}



	function admin_notice_square() {
		
		if (  get_option('square_notice_dismiss') == 'no' ) {
			return;
		}
		
		$class = 'notice notice-info is-dismissible square-first-notice';
		$heading = __( 'Good News For Square Users', 'woosquare' );
		$message = __( 'Now we have launched square as <a target="_blank" href="https://goo.gl/s74bht"><b>Payment gateway for Gravity Forms</b></a>, <a  target="_blank" href="https://goo.gl/6jcD3E"><b>GiveWP </b></a>, <a  target="_blank" href="https://goo.gl/A8Yb3P"><b>Square Integration With Ninja Forms </b></a>, <a  target="_blank" href="https://goo.gl/28JCa6"><b>Square Recurring Payments for WooCommerce Subscriptions</b></a>, <a  target="_blank" href="https://goo.gl/USTSa7"><b>Contact Form 7 Square Payment Addon</b></a>, <a  target="_blank" href="https://goo.gl/fL2uPT"><b>Pay With Square In Caldera Form</b></a>, <a  target="_blank" href="https://goo.gl/Ztafpp"><b>Easy Digital Downloads With Square</b></a>, <a  target="_blank" href="https://goo.gl/44XrSX"><b>Wp Easy Pay For Wordpress</b></a>, <a  target="_blank" href="https://goo.gl/CofmvH"><b>Manage Inventory, Auto Sync & Accept Online Payments</b></a>, <a  target="_blank" href="https://goo.gl/nRdRCD"><b>Woocommerce Square Payment Gateway</b></a> and <a  target="_blank" href="https://goo.gl/mquNkq"><b>WooCommerce To Square Order Sync Add-On</b></a> Check out <a  target="_blank" href="https://apiexperts.io/"><b>apiexperts.com</b></a> for more info.' );
		if(@$_GET['page'] != 'Square-Plus-Page'){
			printf( '<div data-dismissible="notice-one-forever-woosquare" class="%1$s"><h2>%2$s</h2><p>%3$s</p></div>', esc_attr( $class ), esc_html( $heading ) ,  $message  ); 
	 
		}
		
		if(version_compare( WOO_SQUARE_VERSION, '3.0', '=' ) 
			and 
		empty(get_option('woo_square_update_msg_dissmiss'))
			and @$_GET['page'] != 'square-settings'
		){
			if(!empty(get_option('woo_square_account_type')) and !empty(get_option('woo_square_account_currency_code'))){
				echo '<div class="error"><p>' . sprintf( __( 'WooSquare is updated successfully. To Contino your product sync and to use the latest sdk connect your Square account , %1$sconnect your Square Account.%2$s', 'woocommerce-square' ), '<a href="' . admin_url( 'admin.php?page=square-settings' ) . '">', '</a>' ) . '</p></div>';
			}
		}
													   
		if(!empty(get_option('woo_square_auth_notice'))){
			$class = 'notice error square-first-notice';
			// $heading = __( '', 'woosquare' );
			$message = __( get_option('woo_square_auth_notice') );

			printf( '<div data-dismissible="notice-one-forever-woosquare" class="%1$s"><p>%2$s %3$sConnect your Square Account.%4$s</p> <p>Click here to see %5$sinstructions.%6$s </p></div>', esc_attr( $class ),   $message,  '<a href="' . admin_url( 'admin.php?page=square-settings' ) . '">', '</a>' ,  '<a href="https://apiexperts.io/documentation/faq/install-activate-woosquare/">', '</a>'  ); 
			
		}
		
		
		if(!get_transient('hold_woosquare_plus_notice')){
			$woosquare_admin_promo_notices = get_option('woosquare_admin_promo_notices');
			$woo_plus_notice_index = get_option('woo_plus_notice_index');
			if($woo_plus_notice_index >= 5 ){
				return;
			}
			if( !$woosquare_admin_promo_notices[$woo_plus_notice_index]['is_dismiss'] == true){
				$trans = $woosquare_admin_promo_notices[$woo_plus_notice_index];
				
				
				if(empty($trans)){
				    set_transient( 'hold_woosquare_plus_notice', array('promo_notice'), 864000 ); 
            		update_option('woo_plus_notice_index',0);
            		
            		$admin_promo_notices = get_admin_promo_notices();
            		update_option('woosquare_admin_promo_notices',$admin_promo_notices);
            		return;
			    }
				
				// notice for promotions...
			
				$addons_url = $trans['addons_url']; 
				$class = 'notice notice-info is-dismissible woosquare-first-notice woo_plus_notice_index_'.$woo_plus_notice_index;
				
				$heading = __( '' , 'woosquare' );
				$message = __( '<div class="woosquare_notice">   
								<div class="wp-6-s">
								<div class="logo-woosquare">
									  <img src="'. plugins_url( 'woosquare/_inc/images/'.$trans['img'], dirname(__FILE__) ). '" alt=""/>
								</div>
								<div class="content-woosquare">
								<h2>'.$trans["h2"].'</h2>
								<p>'.$trans["p"].'</p>
								<p class="woosquare_coupon_line_wrap">
									<span class="woosquare_coupon_line">
										Use Coupon code:
											<span class="woosquare_coupon_code">
												Plus20 
											</span> 
										to get <b>Flat 20%Off </b> 
										on 1 Year Plan
									</span>
								</p>
								<a class="button button-primary button-hero woosquare_get woo_plus_notice_index_'.$woo_plus_notice_index.'" target="_blank" href="'.$addons_url.'">GET WOOSQUARE PLUS</a>
								</div>
								</div>
							   
								</div>' , 'woosquare');

				printf( '<div data-dismissible="notice-one-forever-woosquare" class="%1$s"><h2 style="font-size: 20px;font-weight: 800;" >%2$s</h2>%3$s</div>', esc_attr( $class ), esc_html( $heading ) ,  $message  );
				
			} else {
				update_option('woo_plus_notice_index',$woo_plus_notice_index+1);
				set_transient( 'hold_woosquare_plus_notice',array('promo_notice'), 864000 ); 
			}
			
		} 
			
		
	}
	
	function get_access_token_woosquare_renewed(){
		// get it from where it save and check is expired than provide. 
			
			if(get_option('woo_square_access_token_free')){
				$woo_square_auth_response = get_option('woo_square_auth_response');
				if(
					!empty($woo_square_auth_response)
					and
					(strtotime($woo_square_auth_response['expires_at']) - 300) <= time()
				){ 
				
					$headers = array(
						'refresh_token' => $woo_square_auth_response['refresh_token'], // Use verbose mode in cURL to determine the format you want for this header
						'Content-Type'  => 'application/json;'
					);
					$oauth_connect_url = WOOSQU_CONNECTURL;
					$redirect_url = add_query_arg(
						array(
							'app_name'    => WOOSQU_APPNAME,
							'plug'    => WOOSQU_PLUGIN_NAME,
						),
						admin_url( 'admin.php' )
					);

					$redirect_url = wp_nonce_url( $redirect_url, 'connect_wooplus', 'wc_wooplus_token_nonce' );
					$site_url = ( urlencode( $redirect_url ) );
					$args_renew = array(
						'body' => array(
							'header' => $headers,
							'action' => 'renew_token',
							'site_url'    => $site_url,
						),
						'timeout' => 45,
					);
					
					$oauth_response = wp_remote_post( $oauth_connect_url, $args_renew );

					$decoded_oauth_response = json_decode( wp_remote_retrieve_body( $oauth_response ) );
					
					if(!empty($decoded_oauth_response->access_token)){
						$woo_square_auth_response['expires_at'] = $decoded_oauth_response->expires_at;
						$woo_square_auth_response['refresh_token'] = $decoded_oauth_response->refresh_token;
						$woo_square_auth_response['access_token'] = $decoded_oauth_response->access_token;
						update_option('woo_square_auth_response',$woo_square_auth_response);
						update_option('woo_square_access_token',$woo_square_auth_response['access_token']);
						update_option('woo_square_access_token_free',$woo_square_auth_response['access_token']);
						update_option('woocommerce_square_merchant_access_token',$woo_square_auth_response['access_token']);
						update_option('woo_square_access_token_cauth',$woo_square_auth_response['access_token']);
						
					}
					
				}
			}
			
			
	}
	function dismiss_woosquare_notice_action(){
		
		if(
			is_numeric($_POST['promo_id'])
			and 
			$_POST['action'] == 'dismiss_woosquare_notice'
			){
			$woosquare_admin_promo_notices = get_option('woosquare_admin_promo_notices');
			$woosquare_admin_promo_notices[$_POST['promo_id']]['is_dismiss'] = true;
			update_option('woosquare_admin_promo_notices',$woosquare_admin_promo_notices);
		}
		die();
	}
	
	function woosquare_notice_script(){

		?>
		<script>
			jQuery(document).on( 'click', '.woosquare-first-notice .notice-dismiss', function() {
				
				var splitted  = jQuery('.woosquare-first-notice').attr('class').split(' ')[4].split('_');
				console.log(splitted[4]);
				jQuery.ajax({
					url: ajaxurl,
					type : 'post',
					data: {
						action: 'dismiss_woosquare_notice',
						promo_id: splitted[4]
					}
				})
				
			})
			jQuery(document).on( 'click', '.woosquare_get', function(e) {
				e.preventDefault();
				
				var splitted  = jQuery('.woosquare_get').attr('class').split(' ')[4].split('_')
				
				var splitted  = jQuery('.woosquare-first-notice').attr('class').split(' ')[4].split('_');
				console.log(splitted[4]);
				jQuery.ajax({
					url: ajaxurl,
					type : 'post',
					data: {
						action: 'dismiss_woosquare_notice',
						promo_id: splitted[4]
					}
				}) .done(function() {
					window.open(
					  jQuery('.woosquare_get').attr('href'),
					  '_blank' // <- This is what makes it open in a new window.
					);


				})
				
			})
		</script>
		<style>
			.woosquare-first-notice {
				padding: 0;
			}
			.wp-6-s {
				width: 99%;
				display: inline-block;
			}
			.wp-6-s img {
				width: auto;
				height: 128px;
				float: left;
				margin-bottom: -4px;
			}
			.content-woosquare {
				float: left;
				width: 74%;
			}
			.logo-woosquare {
				float: left;
			}
			.content-woosquare h2 {
				padding: 0!important;
				font-size: 16px;
				margin: 7px 0 0 0!important;
				line-height: 24px;
				padding-left: 14px!important;
			}
			.content-woosquare p {
				line-height: 16px;
				margin: 0;
				padding-left: 16px;
			}
			.content-woosquare .button.button-primary.button-hero {
				box-shadow: 0 2px 0 #006799;
				margin: 8px 0 0px 16px;
				height: auto;
				line-height: 34px;
				font-weight: bold;
			}
			
			a.button.button-primary.button-hero {
				min-height: 0px;
			}
			.woosquare-first-notice > h2 {
				position: absolute;
				top: -61px;
				display: none;
				font-weight: bold;
			}
			.woosquare_coupon_code{
				font-weight:bold;
			}
			.woosquare_coupon_line{
				background: #dd3333;
				padding: 0px 10px 3px 5px !important;
				color: white;         
			}
			/* CSS For Discount Animation */
			.woosquare_coupon_line_wrap {
				margin:0;
				overflow:hidden;
				}

				/* blue bar */
				.woosquare_coupon_line_wrap {
				position: relative;
				}

				/* text */
				.woosquare_coupon_line {
				color:#fff;
				left:50%;
				transform:translate(-50%,-50%);
				}
				.woosquare_coupon_line {
				top:50%;
				}

				/* Shine */
				.woosquare_coupon_line_wrap:after {
					content:'';
				top:0;
					transform:translateX(100%);
					width:100%;
					height:220px;
					position: absolute;
					z-index:1;
					animation: slide 1s infinite;
					
				/* 
				CSS Gradient - complete browser support from http://www.colorzilla.com/gradient-editor/ 
				*/
				background: -moz-linear-gradient(left, rgba(255,255,255,0) 0%, rgba(255,255,255,0.8) 50%, rgba(128,186,232,0) 99%, rgba(125,185,232,0) 100%); /* FF3.6+ */
					background: -webkit-gradient(linear, left top, right top, color-stop(0%,rgba(255,255,255,0)), color-stop(50%,rgba(255,255,255,0.8)), color-stop(99%,rgba(128,186,232,0)), color-stop(100%,rgba(125,185,232,0))); /* Chrome,Safari4+ */
					background: -webkit-linear-gradient(left, rgba(255,255,255,0) 0%,rgba(255,255,255,0.8) 50%,rgba(128,186,232,0) 99%,rgba(125,185,232,0) 100%); /* Chrome10+,Safari5.1+ */
					background: -o-linear-gradient(left, rgba(255,255,255,0) 0%,rgba(255,255,255,0.8) 50%,rgba(128,186,232,0) 99%,rgba(125,185,232,0) 100%); /* Opera 11.10+ */
					background: -ms-linear-gradient(left, rgba(255,255,255,0) 0%,rgba(255,255,255,0.8) 50%,rgba(128,186,232,0) 99%,rgba(125,185,232,0) 100%); /* IE10+ */
					background: linear-gradient(to right, rgba(255,255,255,0) 0%,rgba(255,255,255,0.8) 50%,rgba(128,186,232,0) 99%,rgba(125,185,232,0) 100%); /* W3C */
					filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#00ffffff', endColorstr='#007db9e8',GradientType=1 ); /* IE6-9 */
				}
				/* animation */
				@keyframes slide {
					0% {transform:translateX(-100%);}
					100% {transform:translateX(100%);}
				}

			@media only screen and (max-width: 1252px) {

				.content-woosquare .button.button-primary.button-hero {
					margin: 3px 0 0px 16px;
				}
				.wp-6-s img {
					height: 100px;
				}
				.content-woosquare p {
					margin: 5px 0 0 0;
				}
				.woosquare_notice {
					margin-top: 65px;
				}
				.woosquare-first-notice > h2 {
					display: block;
				}
				.content-woosquare h2{
					display: none;
				}
			}
			@media only screen and (max-width: 1024px) {
				.wp-6-s {
					width: 100%;
				}
			}
		</style>
		<?php
  
	}
 
 



	function woo_square_checkOrAddPluginTables(){
		//create tables
		require_once  ABSPATH . '/wp-admin/includes/upgrade.php' ;
		global $wpdb;
		
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

	/*
	 * square activation
	 */

	function square_plugin_activation_free() {
		$user_id = username_exists('square_user');
		if (!$user_id) {
			$random_password = wp_generate_password(12);
			$user_id = wp_create_user('square_user', $random_password);
			wp_update_user(array('ID' => $user_id, 'first_name' => 'Square', 'last_name' => 'User'));
		}
		
		//create plugin tables when plugin activate..
		woo_square_checkOrAddPluginTables();
		// update_option('woo_square_merging_option', 1);
		update_option('sync_on_add_edit', 1);
		update_option('html_sync_des','');

		if(empty(get_option('square_notice_dismiss'))){
			update_option('square_notice_dismiss','yes');	
		}
		set_transient( 'hold_woosquare_plus_notice', array('promo_notice'), 864000 ); 
		update_option('woo_plus_notice_index',0);
		
		    $admin_promo_notices = get_admin_promo_notices();
			update_option('woosquare_admin_promo_notices',$admin_promo_notices);
				
	}

	/**
	 * include script
	 */
	function woo_square_script() {
		wp_enqueue_script('woo_square_script', WOO_SQUARE_PLUGIN_URL_FREE . '_inc/js/script.js', array('jquery')); 
		wp_localize_script('woo_square_script', 'myAjax', array('ajaxurl' => admin_url('admin-ajax.php')));
		wp_enqueue_script( "wosquareplus_slick_js", 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.5.8/slick.min.js' );
		// <!-- waves JavaScript -->
		wp_enqueue_script( "wosquareplus_waves_js", WOO_SQUARE_PLUGIN_URL_FREE . '_inc/js/waves.js', array( 'jquery' ));
		// <!-- custom JavaScript -->
		wp_enqueue_script( "wosquareplus_custom_js", WOO_SQUARE_PLUGIN_URL_FREE . '_inc/js/custom.min.js', array( 'jquery' ));	
		// SCSS
		wp_enqueue_style( "woosquare_plus_admin_custom" , WOO_SQUARE_PLUGIN_URL_FREE . '_inc/css/woosquare-free-admin-custom.css');
		wp_enqueue_style('woo_square_pop-up', WOO_SQUARE_PLUGIN_URL_FREE . '_inc/css/pop-up.css');
		wp_enqueue_style('woo_square_synchronization', WOO_SQUARE_PLUGIN_URL_FREE . '_inc/css/synchronization.css');
		wp_enqueue_style( "wosquareplus_slick_theme" , 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.5.8/slick.min.css' );
		wp_enqueue_style( "wosquareplus_slick" , 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.5.8/slick-theme.min.css' );
		
	}

	/*
	 * Ajax action to execute manual sync
	 */

	function woo_square_manual_sync() {
		
		ini_set('max_execution_time', 0);
		
		if(!get_option('woo_square_access_token_free')){
			return;
		}
		
		if(get_option('woo_square_running_sync') && (time()-(int)get_option('woo_square_running_sync_time')) < (WOO_SQUARE_MAX_SYNC_TIME) ){
			Helpers::debug_log('error',"Manual Sync Request: There is already sync running");
			echo 'There is another Synchronization process running. Please try again later. Or <a href="'. admin_url('admin.php?page=square-settings&terminate_sync=true').'" > terminate now </a>';
			die();
		}
		
		update_option('woo_square_running_sync', true);
		update_option('woo_square_running_sync_time', time());
		
		if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {

			$sync_direction = sanitize_text_field($_GET['way']);
			$square = new Square(get_option('woo_square_access_token_free'),get_option('woo_square_location_id_free'));
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
	
	function get_admin_promo_notices(){
	    $admin_promo_notices = array(
				array(
					'addons_url' => 'https://apiexperts.io/solutions/woosquare-plus/?utm_source=Woo&utm_medium=Square&utm_campaign=Customer%20Sync',
						'img' => 'CustomerSync.jpg',
						'h2' => 'Sync your customers between WooCommerce and Square using WooSquare Plus.',
						'p' => 'Unlock the Customer Synchronization feature by purchasing WooSquare Plus. Click here to Explore this premium feature.',
						'is_dismiss' => false
					),
				array(
					'addons_url' => 'https://apiexperts.io/solutions/woosquare-plus/?utm_source=Woo&utm_medium=Square&utm_campaign=Save%20Cards',
						'img' => 'SavedCards.jpg',
						'h2' => 'Let customers save their card info on file for future purchases.',
						'p' => 'Unlock the Save Card on File feature by purchasing WooSquare Plus. Click here to Explore this premium feature.',
						'is_dismiss' => false
					),
				array(
					'addons_url' => 'https://apiexperts.io/solutions/woosquare-plus/?utm_source=Woo&utm_medium=Square&utm_campaign=Order%20Sync',
						'img' => 'Order-Synchronization.jpg',
						'h2' => 'Easily follow up on orders from WooCommerce or Square from a single platform. ',
						'p' => 'Unlock the Order Synchronization on Square feature by purchasing WooSquare Plus. Click here to Explore this premium feature.',
						'is_dismiss' => false
					),	
				array(
					'addons_url' => 'https://apiexperts.io/solutions/woosquare-plus/?utm_source=Woo&utm_medium=Square&utm_campaign=Square%20Payment',
						'img' => 'WooCommerce-Subscription.jpg',
						'h2' => 'Process recurring payments with WooCommerce Subscriptions using Square payment gateway.',
						'p' => 'Unlock the WooCommerce Subscription feature by purchasing WooSquare Plus. Click here to Explore this premium feature.',
						'is_dismiss' => false
						
					),
				array(
					'addons_url' => 'https://apiexperts.io/solutions/woosquare-plus/?utm_source=Woo&utm_medium=Square&utm_campaign=Sync%20Products',
						'img' => 'Unlimited-Products.jpg',
						'h2' => 'Add unlimited products in your inventory - Absolutely no limitations.',
						'p' => 'Unlock the feature to Add Unlimited Products by purchasing WooSquare Plus. Click here to Explore this premium feature.',
						'is_dismiss' => false
					)
			);
			return $admin_promo_notices;
    }


	function public_init_woosquare(){
		 
		if(!empty(get_option('woo_square_access_token_free')) and empty(get_option('woo_square_access_token_cauth'))){
			// delete_option('woo_square_merging_option');
			delete_option('woo_square_access_token_free');
			delete_option('woo_square_account_type');
			delete_option('woo_square_account_currency_code');
			delete_option('woo_square_locations_free');
			delete_option('woo_square_business_name_free');
			delete_option('woo_square_location_id_free');
			update_option('woo_square_auth_notice','WooSquare is updated successfully. In order to sync products and use the latest SDK connect your Square account ,');
		}
	}
		
		
		function wp_remote_post_for_mps($url,$body,$contenttype=null){
			if($contenttype == 'form'){
				$contype='application/x-www-form-urlencoded'; 
			} else {
				$contype='application/json';
			}
			if($contenttype != 'array'){
				$headers = array(
					"accept" => "application/json",
					"cache-control" => "no-cache",
					"content-type" => $contype
				);
			} else {
				$headers = array();
			}
			$response = wp_remote_post( $url, array(
				'method' => 'POST',
				'timeout' => 45,
				'redirection' => 5,
				'httpversion' => '1.1',
				'blocking' => true,
				'headers' => $headers,
				'body' => $body,
				'cookies' => array()
				)
			);
			return $response;
		}		
		
	function woo_square_post_savepage_load_admin_notice() {
		// Use html_compress($html) function to minify html codes.
		if(!empty($_GET['post'])){
			$Gpost = sanitize_text_field($_GET['post']);
		
			$admin_notice_square = get_post_meta($Gpost, 'admin_notice_square', true);
			if(!empty($admin_notice_square)){
				echo '<div class="notice notice-error"><p>'.$admin_notice_square.'</p></div>';
				delete_post_meta($Gpost, 'admin_notice_square', 'Product unable to sync to Square due to Sku missing ');
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
				Helpers::debug_log('info',"[add_update_product_hook] Start updating product on Square");

				if(!get_option('woo_square_access_token_free')){
					return;
				}
				
				$product_square_id = get_post_meta($post_id, 'square_id', true);
				$square = new Square(get_option('woo_square_access_token_free'),get_option('woo_square_location_id_free'));
				
				$squareSynchronizer = new WooToSquareSynchronizer($square);       
				$result = $squareSynchronizer->addProduct($post, $product_square_id);

				$termid = get_post_meta($post_id, '_termid', true);
				if ($termid == '') {//new product
					$termid = 'update';
				}
				update_post_meta($post_id, '_termid', $termid);
				
				if( $result===TRUE ){
					update_post_meta($post_id, 'is_square_sync', 1);  
				}

				Helpers::debug_log('info',"[add_update_product_hook] End updating product on Square");
			}
		} else {
			update_post_meta($post_id, 'is_square_sync', 0);  
		}
	}

	/*
	 * Deleting product 
	 */
	function woo_square_delete_product($post_id) {
		
		//Avoid auto save from calling Square APIs.
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}
		$product_square_id = get_post_meta($post_id, 'square_id', true);
		$product= get_post($post_id);
		if ($product->post_type == "product" && !empty($product_square_id)) {
			
			Helpers::debug_log('info',"[delete_product_hook] Start deleting product {$post_id} [square:{$product_square_id}] from Square");

			global $wpdb;

			$wpdb->insert($wpdb->prefix.WOO_SQUARE_TABLE_DELETED_DATA,
					[
						'square_id'  => $product_square_id,
						'target_id'  => $post_id,
						'target_type'=> Helpers::TARGET_TYPE_PRODUCT,
						'name'       => $product->post_title
					]
			);
					
			if(!get_option('woo_square_access_token_free')){
				return;
			}

			$square = new Square(get_option('woo_square_access_token_free'),get_option('woo_square_location_id_free'));
			$squareSynchronizer = new WooToSquareSynchronizer($square);       
			$result = $squareSynchronizer->deleteProductOrGet($product_square_id,"DELETE");
			
			//delete product from plugin delete table
			if($result===TRUE){
				$wpdb->delete($wpdb->prefix.WOO_SQUARE_TABLE_DELETED_DATA,
					['square_id'=> $product_square_id ]
				);
				Helpers::debug_log('info',"[delete_product_hook] Product {$post_id} deleted successfully from Square");
			}
			Helpers::debug_log('info',"[delete_product_hook] End deleting product {$post_id} [square:{$product_square_id}] from Square");
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
		Helpers::debug_log('info',"[add_category_hook] Start adding category to Square: {$category_id}");
	   
		if(!get_option('woo_square_access_token_free')){
			return;
		}
		
		$square = new Square(get_option('woo_square_access_token_free'),get_option('woo_square_location_id_free'));
		
		$squareSynchronizer = new WooToSquareSynchronizer($square);
		$result = $squareSynchronizer->addCategory($category);
		
		if( $result===TRUE ){
			update_option("is_square_sync_{$category_id}", 1);
		}
		Helpers::debug_log('info',"[add_category_hook] End adding category {$category_id} to Square");
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
	   
		if(!get_option('woo_square_access_token_free')){
			return;
		}
		$category = get_term_by('id', $category_id, 'product_cat');
		$categorySquareId = get_option('category_square_id_' . $category->term_id);
		Helpers::debug_log('info',"[edit_category_hook] Start updating category on Square: {$category_id} [square:{$categorySquareId}]");

		$square = new Square(get_option('woo_square_access_token_free'),get_option('woo_square_location_id_free'));
		$squareSynchronizer = new WooToSquareSynchronizer($square);
		
		//add category if not already linked to square, else update
		if( empty($categorySquareId )){
			$result = $squareSynchronizer->addCategory($category);
		}else{
			$result = $squareSynchronizer->editCategory($category,$categorySquareId);
		}
		
		if( $result===TRUE ){
			update_option("is_square_sync_{$category_id}", 1);
			Helpers::debug_log('info',"[edit_category_hook] category {$category_id} updated successfully");
		}
		Helpers::debug_log('info',"[edit_category_hook] End updating category on square: {$category_id} [square:{$categorySquareId}]");
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

		Helpers::debug_log('info',"[delete_category_hook] Start deleting category {$category_id} [square:{$category_square_id}] from Square");
		global $wpdb;

		$wpdb->insert($wpdb->prefix.WOO_SQUARE_TABLE_DELETED_DATA,
				[
					'square_id'  => $category_square_id,
					'target_id'  => $category_id,
					'target_type'=> Helpers::TARGET_TYPE_CATEGORY,
					'name'       => $deleted_category->name
				]
		);

		if(!get_option('woo_square_access_token_free')){
			return;
		}

		$square = new Square(get_option('woo_square_access_token_free'),get_option('woo_square_location_id_free'));
		$squareSynchronizer = new WooToSquareSynchronizer($square); 
		$result = $squareSynchronizer->deleteCategory($category_square_id);
		
		//delete product from plugin delete table
		if($result===TRUE){
			$wpdb->delete($wpdb->prefix.WOO_SQUARE_TABLE_DELETED_DATA,
				['square_id'=> $category_square_id ]
			);
			Helpers::debug_log('info',"[delete_category_hook] Category {$category_id} deleted successfully from Square");

		}
		Helpers::debug_log('info',"[delete_category_hook] End deleting category {$category_id} [square:{$category_square_id}] from Square");
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
		if (get_post_meta($order_id, 'square_payment_id', true)) {
			
				$order = wc_get_order( $order_id );
			
				$square = new Square(get_option('woo_square_access_token_free'),get_option('woo_square_location_id_free'));
				$square->refund($order_id, $refund_id);
			
			// Get an instance of the order object
		
		// Iterating though each order items
		foreach ( $order->get_items() as $item_id => $item_values ) {

			// Item quantity
			$item_qty = $item_values['qty'];

			// getting the product ID (Simple and variable products)
			$product_id = $item_values['variation_id'];
			if( $product_id == 0 || empty($product_id) ) $product_id = $item_values['product_id'];

			// Get an instance of the product object
			$product = wc_get_product( $product_id );

			// Get the stock quantity of the product
			$product_stock = $product->get_stock_quantity();

			// Increase back the stock quantity
			
			
			wc_update_product_stock( $product, $item_qty, 'increase' );
			
			
		}
			
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

		$square = new Square(get_option('woo_square_access_token_free'),get_option('woo_square_location_id_free'));
		$square->completeOrder($order_id);
	}



		
	function payment_gateway_disable_country( $available_gateways ) {
		global $woocommerce;

	 
		if (isset( $available_gateways['square'] ) && !is_ssl()) {
			unset( $available_gateways['square'] );
			
		}
		
		$woocommerce_square_settings = get_option('woocommerce_square_settings');

		if($woocommerce_square_settings['enabled'] != 'yes'){
			unset( $available_gateways['square'] );
		} else if($woocommerce_square_settings['enable_sandbox'] == 'yes'){
			$current_user = wp_get_current_user();
			if(user_can( $current_user, 'administrator' ) != 1){
					// user is an admin
					unset( $available_gateways['square'] );
			} 
		}


		return $available_gateways;
	}
	add_action( 'admin_notices', 'admin_notice_square_for_ssl' );
	function admin_notice_square_for_ssl(){
		
		$woocommerce_square_settings = get_option('woocommerce_square_settings');
		if ($woocommerce_square_settings['enabled'] == 'yes' && !is_ssl() && !wc_checkout_is_https()) {
			$class = 'notice notice-info is-dismissible square-first-notice';
			// $heading = __( 'Good News For Square Users', 'woosquare' );
			$message = __( 'Square is enabled, but a SSL certificate is not detected. Your checkout may not be secure! Please ensure your server has a valid <a href="https://en.wikipedia.org/wiki/Transport_Layer_Security" target="_blank">SSL certificate</a>' );

			// printf( '<div data-dismissible="notice-one-forever-woosquare" class="%1$s"><h2>%2$s</h2><p>%3$s</p></div>', esc_attr( $class ), esc_html( $heading ) ,  $message  ); 
		
			// printf( __( '<div data-dismissible="notice-one-forever-woosquare" class="%1$s">Square is enabled, but a SSL certificate is not detected. Your checkout may not be secure! Please ensure your server has a valid <a href="%1$s" target="_blank">SSL certificate</a></div>', 'woocommerce-square' ), esc_attr( $class ), 'https://en.wikipedia.org/wiki/Transport_Layer_Security' ); 
			printf( __( '<div data-dismissible="notice-one-forever-woosquare" class="%s"><p>%s</p></div>', 'woocommerce-square' ), esc_attr( $class ),$message); 
		}
	}
	add_filter( 'woocommerce_available_payment_gateways', 'payment_gateway_disable_country' );	
	}
}

add_action('admin_init','redirect_woosquare_support');
function redirect_woosquare_support(){
	if(@$_GET['page'] == 'square-settings-wp-support-forum'){
		wp_redirect('https://apiexperts.io/woosquare-plus-support/');
		exit();
	}
}

add_filter( 'cartflows_offer_supported_payment_gateway_slugs', 'cartflows_square_cpayment_gateway_slugs',20 );
function cartflows_square_cpayment_gateway_slugs($_gateways){
	if(in_array('stripe', $_gateways )){
		$_gateways[] = 'square_plus';
		$_gateways[] = 'square';
	}
	return $_gateways;
} 
add_filter( 'cartflows_offer_supported_payment_gateways', 'cartflows_offer_supported_payment_gateways_function' );
function cartflows_offer_supported_payment_gateways_function($supported_gateways){
			$supported_gateways['square_plus'] = array(
				'class' => 'Cartflows_Pro_Gateway_WooSquare',
				'path' => plugin_dir_path( __FILE__ ) . '_inc/class-cartflows-pro-gateway-woosquare.php',
			);
			$supported_gateways['square'] = array(
				'class' => 'Cartflows_Pro_Gateway_WooSquare',
				'path' => plugin_dir_path( __FILE__ ) . '_inc/class-cartflows-pro-gateway-woosquare.php',
			);
		return $supported_gateways;
}


if(!empty(fs_request_get( 'license_id' ))){
    header("Location: ".get_admin_url().'admin.php?page=square-settings-account');
}