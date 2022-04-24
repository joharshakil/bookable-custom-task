<?php

/**
 * Fired during plugin activation
 *
 * @link       wpexperts.io
 * @since      1.0.0
 *
 * @package    Woosquare_Plus
 * @subpackage Woosquare_Plus/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Woosquare_Plus
 * @subpackage Woosquare_Plus/includes
 * @author     Wpexpertsio <support@wpexperts.io>
 */
class Woosquare_Plus_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {


		$activate_modules_woosquare_plus = get_option('activate_modules_woosquare_plus');

		

		if(empty($activate_modules_woosquare_plus) and !empty(get_option('woo_square_access_token_cauth'))){
			delete_option('woo_square_access_token_cauth');
		}
		$plugin_modules = array(
				'items_sync' => array(
						'module_img' => plugin_dir_url( __FILE__ ).'../admin/img/itemsyncs.png',
						'module_title' => 'Synchronization of Products',
						'module_short_excerpt' => 'Helps you to synchronize products between Square and WooCommerce, in the direction of your preference.',
						'module_redirect' => 'https://apiexperts.io/link/synchronization-of-products/',
						'module_video' => 'https://www.youtube.com/embed/E-gVN51P9lk',
						'module_activate' => ($activate_modules_woosquare_plus['items_sync']['module_activate'])?true:false,
						'module_menu_details' => array(
								'menu_title' => 'Sync Products',
								'parent_slug' => 'square-settings',
								'page_title' => 'Woosquare Item Sync',
								'capability' => 'manage_options',
								'menu_slug' => 'square-item-sync',
								'tab_html_class' => 'fa fa-retweet',
								'function_callback' => 'square_item_sync_page',
						)
				),
				'sales_sync' => array(
						'module_img' => plugin_dir_url( __FILE__ ).'../admin/img/ordersyn.png',
						'module_title' => 'Order Synchronization',
						'module_short_excerpt' => 'Automate the process to synchronize orders between WooCommerce and Square.',
						'module_redirect' => 'https://apiexperts.io/link/order-synchronization/',
						'module_video' => 'https://www.youtube.com/embed/bDzRLARmRzQ',
						'module_activate' => ($activate_modules_woosquare_plus['sales_sync']['module_activate'])?true:false,
						'module_menu_details' => array(
								'menu_title' => 'Order Sync',
								'parent_slug' => 'square-settings',
								'page_title' => 'WooCommerce to Square Order Sync',
								'capability' => 'manage_options',
								'menu_slug' => 'order-sync',
								'tab_html_class' => 'fa fa-list-ul',
								'function_callback' => 'square_order_sync_page',
						)
				),
				'customer_sync' => array(
						'module_img' => plugin_dir_url( __FILE__ ).'../admin/img/Cust-info-Sync.png',
						'module_title' => 'Customers Synchronization',
						'module_short_excerpt' => 'Easily keep your Square and WooCommerce customers in sync, and link them to the orders appearing in WooCommerce from Square.',
						'module_redirect' => 'https://apiexperts.io/link/customers-synchronization/',
						'module_activate' => ($activate_modules_woosquare_plus['customer_sync']['module_activate'])?true:false,
						'module_menu_details' => array(
								'menu_title' => 'Customers Sync',
								'parent_slug' => 'square-settings',
								'page_title' => 'Woosquare Customer Sync',
								'capability' => 'manage_options',
								'menu_slug' => 'square-customers',
								'tab_html_class' => 'fa fa-users',
								'function_callback' => 'square_customer_sync_page',
						)
				),
				'woosquare_payment' => array(
						'module_img' => plugin_dir_url( __FILE__ ).'../admin/img/forpayment.png',
						'module_title' => 'Square Payment Gateway',
						'module_short_excerpt' => 'Collect payments with Square Payment processor at WooCommerce checkout and manage sales and refunds easily.',
						'module_redirect' => 'https://apiexperts.io/link/square-payment-gateway/',
						'module_video' => 'https://www.youtube.com/embed/-uYI_a-k9Eo',
						'module_activate' => ($activate_modules_woosquare_plus['woosquare_payment']['module_activate'])?true:false,
						'module_menu_details' => array(
								'menu_title' => 'Payment Settings',
								'parent_slug' => 'square-settings',
								'page_title' => 'WooCommerce Square Up Payment Gateway',
								'capability' => 'manage_options',
								'menu_slug' => 'square-payment-gateway',
								'tab_html_class' => 'fa fa-square',
								'function_callback' => 'square_payment_sync_page',
						)
				),
				'woosquare_transaction_addon' => array(
						'module_img' => plugin_dir_url( __FILE__ ).'../admin/img/transactionnote.png',
						'module_title' => 'Transaction notes',
						'module_short_excerpt' => 'Manage information to be displayed in Square transaction notes for the payments made at WooCommerce checkout.',
						'module_redirect' => 'https://apiexperts.io/link/transaction-notes/',
						'module_video' => 'https://www.youtube.com/embed/s2inxilrncc',
						'module_activate' => ($activate_modules_woosquare_plus['woosquare_transaction_addon']['module_activate'])?true:false,
						'module_menu_details' => array(
								'menu_title' => 'Transaction Notes',
								'parent_slug' => 'square-settings',
								'page_title' => 'Woosquare Transaction Sync',
								'capability' => 'manage_options',
								'menu_slug' => 'square-transaction-sync',
								'tab_html_class' => 'fa fa-bell',
								'function_callback' => 'square_transaction_sync_page',
						)
				),
				'woosquare_card_on_file' => array(
						'module_img' => plugin_dir_url( __FILE__ ).'../admin/img/cardonfile2.png',
						'module_title' => 'Save cards at checkout',
						'module_short_excerpt' => 'Users can save their cards at the time of checkout in WooCommerce, and can use them in future easily.',
						'module_redirect' => 'https://apiexperts.io/link/save-cards-at-checkout/',
						'module_video' => 'https://www.youtube.com/embed/YVnjPEUWg8U',
						'module_activate' => ($activate_modules_woosquare_plus['woosquare_card_on_file']['module_activate'])?true:false,
						'module_menu_details' => array(
								'menu_title' => 'Save cards',
								'parent_slug' => 'square-settings',
								'page_title' => 'Woosquare Payment With Card on File',
								'capability' => 'manage_options',
								'menu_slug' => 'square-card-sync',
								'tab_html_class' => 'fa fa-credit-card',
								'function_callback' => 'square_card_sync_page',
						)
				),
			
				'woosquare_modifiers' => array(
					'module_img' => plugin_dir_url( __FILE__ ).'../admin/img/cardonfile2.png',
					'module_title' => 'Square Modifiers',
					'module_short_excerpt' => 'mods content.',
					'module_redirect' => 'https://apiexperts.io/link/save-cards-at-checkout/',
					'module_video' => 'https://www.youtube.com/embed/YVnjPEUWg8U',
					'module_activate' => ($activate_modules_woosquare_plus['woosquare_modifiers']['module_activate'])?true:false,
					'module_menu_details' => array(
							'menu_title' => 'Square Modifiers',
							'parent_slug' => 'square-modifiers',
							'page_title' => 'Square Modifiers',
							'capability' => 'manage_options',
							'menu_slug' => 'square-modifiers',
							'tab_html_class' => 'fa fa-credit-card',
							'function_callback' => 'square_modifiers_sync_page',
				     	)
					),	
			);


                  /*
			$plugin_modules['module_page'] = array(
					'module_activate' => true,
					'module_menu_details' => array(
							'menu_title' => 'Plugin Module',
							'parent_slug' => 'square-settings',
							'page_title' => 'WooSquare Module',
							'capability' => 'manage_options',
							'menu_slug' => 'woosquare-plus-module',
							'tab_html_class' => '',
							'function_callback' => 'woosquare_plus_module_page',
					)
			);*/


			// if(empty(get_option('activate_modules_woosquare_plus',true))){
			//update_option('activate_modules_woosquare_plus', $plugin_modules);
			// }


			$plugin_modules['module_page'] = array(
					'module_activate' => true,
					'module_menu_details' => array(
							'menu_title' => 'Plugin Module',
							'parent_slug' => 'square-settings',
							'page_title' => 'WooSquare Module',
							'capability' => 'manage_options',
							'menu_slug' => 'woosquare-plus-module',
							'tab_html_class' => '',
							'function_callback' => 'woosquare_plus_module_page',
					)
			);


			// if(empty(get_option('activate_modules_woosquare_plus',true))){
			update_option('activate_modules_woosquare_plus', $plugin_modules);



		/*
		* square activation
		*/

		$user_id = username_exists('square_user');
		if (!$user_id) {
			$random_password = wp_generate_password(12);
			$user_id = wp_create_user('square_user', $random_password);
			wp_update_user(array('ID' => $user_id, 'first_name' => 'Square', 'last_name' => 'User'));
		}
		//check begin time exist for payment.
		if(!get_option('square_payment_begin_time')){
			// 2013-01-15T00:00:00Z
			update_option('square_payment_begin_time',date("Y-m-d")."T00:00:00Z");
		}



		// update_option('woo_square_merging_option', 1);
		// update_option('sync_on_add_edit', 1);
		// update_option('sync_square_order_notify','');
		// update_option('html_sync_des','');

		// deactivate_plugins('woosquare/woocommerce-square-integration.php');
		deactivate_plugins('woosquare-pro/woocommerce-square-integration.php');
		deactivate_plugins('woosquare-payment/woosquare-payment.php');
		deactivate_plugins('wc-square-recurring-premium/wc-square-recuring.php');


	}

}
