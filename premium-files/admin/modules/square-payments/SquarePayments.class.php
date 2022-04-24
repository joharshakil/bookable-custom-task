<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

include_once( dirname( __FILE__ ) . '/SquarePaymentsConnect.class.php' );

class WooSquare_Payments {
	protected $connect;
	public $logging;

	/**
	 * Constructor
	 */
	public function __construct( WooSquare_Payments_Connect $connect ) {
		$this->init();
		$this->connect = $connect;

		add_filter( 'woocommerce_payment_gateways', array( $this, 'register_gateway' ) );

		add_action( 'woocommerce_order_status_on-hold_to_processing', array( $this, 'capture_payment' ) );
		add_action( 'woocommerce_order_status_on-hold_to_completed', array( $this, 'capture_payment' ) );
		add_action( 'woocommerce_order_status_on-hold_to_cancelled', array( $this, 'cancel_payment' ) );
		add_action( 'woocommerce_order_status_on-hold_to_refunded', array( $this, 'cancel_payment' ) );
		add_action( 'admin_init', array( $this, 'wooplus_apple_pay_domain_verification' ) );

		if ( is_admin() ) {
			add_filter( 'woocommerce_order_actions', array( $this, 'add_capture_charge_order_action' ) );
			add_action( 'woocommerce_order_action_square_capture_charge', array( $this, 'maybe_capture_charge' ) );
			add_action( 'admin_post_add_foobar', array( $this, 'prefix_admin_Square_payment_settings_save' ) );
			add_action( 'admin_post_nopriv_add_foobar', array( $this, 'prefix_admin_Square_payment_settings_save' ) );
		}

		$gateway_settings = get_option( 'woocommerce_square_plus_settings' );

		$this->logging = ! empty( $gateway_settings['logging'] ) ? true : false;

		return true;
	}

	/**
	 * Init
	 */
	public function init() {
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}

		// live/production app id from Square account
		
		$tokenn = get_option('woo_square_access_token');
		$woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
		if(!empty($tokenn) and !empty($woocommerce_square_plus_settings['enable_sandbox'])  and $woocommerce_square_plus_settings['enable_sandbox'] == 'yes' ){
			if (!defined('SQUARE_APPLICATION_ID')) define('SQUARE_APPLICATION_ID',WOOSQU_PLUS_APPID);
			if (!defined('WC_SQUARE_ENABLE_STAGING')) define('WC_SQUARE_ENABLE_STAGING',true);
		} elseif(@$woocommerce_square_plus_settings['enable_sandbox'] == 'no' ) {
			if (!defined('SQUARE_APPLICATION_ID')) define('SQUARE_APPLICATION_ID',WOOSQU_PLUS_APPID);
			if (!defined('WC_SQUARE_ENABLE_STAGING')) define('WC_SQUARE_ENABLE_STAGING',false);
		}
		
		//include square lib
		
		// die();
		require_once( dirname( __FILE__ ) . '/../square-customers/vendor/square/connect/autoload.php' );
		
		// Includes
		include_once( dirname( __FILE__ ) . '/SquarePlusGateway.class.php' );
		// Includes
		include_once( dirname( __FILE__ ) . '/SquarePlusGateway-Recurring.class.php' );

		return true;
	}

	/**
	 * Register the gateway for use
	 */
	public function register_gateway( $methods ) {
		$methods[] = 'WooSquare_Plus_Gateway';
		$methods[] = 'WooSquareGooglePay_Gateway';
		//$methods[] = 'WooSquareGiftPay_Gateway';
    	if((get_option('woo_square_plus_apple_pay_domain_registered') == 'yes') && (wc_clean( wp_unslash( $_SERVER['HTTP_HOST'] ) ) == get_option('woo_square_plus_apple_pay_domain_registered_url') ) ){	
		$methods[] = 'WooSquareApplePay_Gateway';
	    }
		return $methods;
	}

	public function wooplus_apple_pay_domain_verification() {
				 
		$token    = get_option( 'woo_square_access_token' );
		$woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
		if(!empty($woocommerce_square_plus_settings['enable_sandbox'])  && $woocommerce_square_plus_settings['enable_sandbox'] == 'yes' ){
			$token = $woocommerce_square_plus_settings['sandbox_access_token'];
		}

		$domain_name  = ! empty( $_SERVER['HTTP_HOST'] ) ? wc_clean( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
		if ( empty( $domain_name ) ) {
			throw new \Exception( 'Unable to verify domain with Apple Pay - no domain found in $_SERVER[\'HTTP_HOST\'].' );
		}

		if ( ! $this->woo_square_check_apple_pay_verification_file() ) {
			update_option('woo_square_plus_apple_pay_domain_registered', 'no' );
			delete_option('woo_square_plus_apple_pay_domain_registered_url');
			return false;
		}


		$recently_registered = get_transient( 'woo_square_check_apple_pay_domain_registration' );
		if(!$recently_registered) {
			$url = "https://connect.".WC_SQUARE_STAGING_URL.".com/v2/apple-pay/domains";

			
			$response = wp_remote_post(
				$url,
				array(
					'headers' => array(
						'Authorization'  => 'Bearer ' . $token,
						'Content-Type'   => 'application/json',
					),
					'body'    => wp_json_encode(
						array(
							'domain_name' => $domain_name,
						)
					),
				)
			);


		if ( is_wp_error( $response ) ) {
		throw new \Exception( sprintf( 'Unable to verify domain %s - %s', $domain_name, $response ) );
		}

		$parsed_response = json_decode( $response['body'], true );
		if ( 200 == $response['response']['code'] || !empty( $parsed_response['status'] ) || 'VERIFIED' == @$parsed_response['status'] ) {
			update_option( 'woo_square_plus_apple_pay_domain_registered', 'yes' );
			update_option( 'woo_square_plus_apple_pay_domain_registered_url', $domain_name );
			$this->log( 'Your domain has been verified with Apple Pay!' );
     		set_transient( 'woo_square_check_apple_pay_domain_registration', true, HOUR_IN_SECONDS );
		} 
	 }
	}

	public function woo_square_check_apple_pay_verification_file() {
		if ( empty( $_SERVER['DOCUMENT_ROOT'] ) ) {
			return false;
		}

		$path              = untrailingslashit( wc_clean( wp_unslash( $_SERVER['DOCUMENT_ROOT'] ) ) );
		$dir               = '.well-known';
		$file              = 'apple-developer-merchantid-domain-association';
		$fullpath          = $path . '/' . $dir . '/' . $file;
		$plugin_path       = WOO_SQUARE_PLUS_PLUGIN_PATH."/admin/modules/square-payments/verification" ;
		$existing_contents = @file_get_contents( $fullpath );                  
		$new_contents      = @file_get_contents( $plugin_path . '/' . $file ); 

		if ( $existing_contents && $existing_contents === $new_contents ) {
			return true;
		}

		if ( ! file_exists( $path . '/' . $dir ) ) {
			if ( ! @mkdir( $path . '/' . $dir, 0755 ) ) { 
				$this->log( 'Unable to create domain association folder to domain root.' );
				return false;
			}
		}

		if ( ! @copy( $plugin_path . '/' . $file, $fullpath ) ) { 
			$this->log( 'Unable to copy domain association file to domain root.' );
			return false;
		}

		$this->log( 'Apple Pay Domain association file updated.' );
		return true;
	}

	public function add_capture_charge_order_action( $actions ) {
		if ( ! isset( $_REQUEST['post'] ) ) {
			return $actions;
		}

		$order = wc_get_order( $_REQUEST['post'] );

		// bail if the order wasn't paid for with this gateway
		if ( 'square_plus' !== ( version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->payment_method : $order->get_payment_method() ) ) {
			return $actions;
		}

		// bail if charge was already captured
		if ( 'yes' === get_post_meta( version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->id : $order->get_id(), '_square_charge_captured', true ) ) {
			return $actions;
		}

		$actions['square_capture_charge'] = esc_html__( 'Capture Charge', 'woosquare' );

		return $actions;
	}
	
	





	/*
    * form submit to save data of payment settings
    */



	public function prefix_admin_Square_payment_settings_save() {
			// Handle request then generate response using echo or leaving PHP and using HTML
			$arraytosave = array(
					'enabled' => ($_POST['woocommerce_square_enabled'] == 1 ? 'yes' : 'no'),
					'title' => (!empty($_POST['woocommerce_square_title']) ? $_POST['woocommerce_square_title'] : ''),
					'description' => (!empty($_POST['woocommerce_square_description']) ? $_POST['woocommerce_square_description'] : ''),
					'capture' => ($_POST['woocommerce_square_capture'] == 1 ? 'yes' : 'no'),
					'create_customer' => ($_POST['woocommerce_square_create_customer'] == 1 ? 'yes' : 'no'),
					'google_pay_enabled' => ($_POST['woocommerce_square_google_pay_enabled'] == 1 ? 'yes' : 'no'),
					'gift_card_enabled' => ($_POST['woocommerce_square_gift_card_pay_enabled'] == 1 ? 'yes' : 'no'),
					'gift_card_taxable' => ($_POST['woocommerce_square_gift_taxable'] == 1 ? 'yes' : 'no'),
					'logging' => ($_POST['woocommerce_square_logging'] == 1 ? 'yes' : 'no'),
					'Send_customer_info' => ($_POST['Send_customer_info'] == 1 ? 'yes' : 'no'),
					'enable_avs_check' => ($_POST['enable_avs_check'] == 1 ? 'yes' : 'no'),
					'enable_sandbox' => ($_POST['enable_sandbox'] == 1 ? 'yes' : 'no'),
					'sandbox_application_id' => ($_POST['enable_sandbox'] == 1 ? sanitize_text_field($_POST['sandbox_application_id']) : ''),
					'sandbox_access_token' => ($_POST['enable_sandbox'] == 1 ? sanitize_text_field($_POST['sandbox_access_token']) : ''),
					'sandbox_location_id' => ($_POST['enable_sandbox'] == 1 ? sanitize_text_field($_POST['sandbox_location_id']) : ''),

			);
			$arraytosave_serialize =  ($arraytosave);
			update_option( 'woocommerce_square_plus_settings', $arraytosave_serialize );
			
			$woocommerce_square_google_pay_settings = get_option('woocommerce_square_google_pay_settings');
			if(!empty($_POST['woocommerce_square_google_pay_enabled']) && $_POST['woocommerce_square_google_pay_enabled'] == 1){
				$woocommerce_square_google_pay_settings['enabled'] =  'yes';
			   
			} elseif(empty($_POST['woocommerce_square_google_pay_enabled'])) {
				$woocommerce_square_google_pay_settings['enabled'] =  'no';
			}
			update_option( 'woocommerce_square_google_pay_settings', $woocommerce_square_google_pay_settings );
			
			
//			$woocommerce_square_gift_card_pay_settings = get_option('woocommerce_square_gift_card_pay_settings');
//			if(!empty($_POST['woocommerce_square_gift_card_pay_enabled']) && $_POST['woocommerce_square_gift_card_pay_enabled'] == 1){
//				$woocommerce_square_gift_card_pay_settings['enabled'] =  'yes';
//			} elseif(empty($_POST['woocommerce_square_gift_card_pay_enabled'])) {
//				$woocommerce_square_gift_card_pay_settings['enabled'] =  'no';
//			}

//			$woocommerce_square_gift_card_coupen_code_pay_enabled = $_POST['woocommerce_square_gift_card_coupen_code_pay_enabled'] == 1 ? 'yes' : 'no';
//			update_option( 'woocommerce_square_gift_card_pay_settings', $woocommerce_square_gift_card_pay_settings );
//			update_option( 'woocommerce_square_gift_card_coupen_code_pay_settings', $woocommerce_square_gift_card_coupen_code_pay_enabled );
			
			$woocommerce_square_gift_card_pay_enabled = get_option('woocommerce_square_gift_card_pay_enabled');
			if(!empty($_POST['woocommerce_square_gift_card_pay_enabled']) && $_POST['woocommerce_square_gift_card_pay_enabled'] == 1){
				$woocommerce_square_gift_card_pay_enabled =  'yes';
			} elseif(empty($_POST['woocommerce_square_gift_card_pay_enabled'])) {
				$woocommerce_square_gift_card_pay_enabled =  'no';
			}
			update_option( 'woocommerce_square_gift_card_pay_enabled', $woocommerce_square_gift_card_pay_enabled );


	    	$woocommerce_square_payment_reporting  = $_POST['woocommerce_square_payment_reporting'] == 1 ? 'yes' : 'no';
		    update_option( 'woocommerce_square_payment_reporting', $woocommerce_square_payment_reporting );

			$msg = json_encode(array(
					'status' => true,

					'msg' => 'Settings updated successfully!',
			));
			set_transient( 'woosquare_plus_notification', $msg, 12 * HOUR_IN_SECONDS );
			wp_redirect(get_admin_url( ).'admin.php?page=square-payment-gateway');
		}

	public function maybe_capture_charge( $order ) {
		if ( ! is_object( $order ) ) {
			$order = wc_get_order( $order );
		}

		$this->capture_payment( version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->id : $order->get_id() );

		return true;
	}

	/**
	 * Capture payment when the order is changed from on-hold to complete or processing
	 *
	 * @param int $order_id
	 */
	public function capture_payment( $order_id ) {
		$order = wc_get_order( $order_id );


 


		if (in_array(( version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->payment_method : $order->get_payment_method() ), array('square_plus','square_google_pay','square_gift_card_pay'))) {
			try {
				$this->log( "Info: Begin capture for order {$order_id}" );

				$trans_id = get_post_meta( $order_id, 'woosquare_transaction_id', true );
				 
				$token    = get_option( 'woo_square_access_token' );
				$woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
				if(!empty($woocommerce_square_plus_settings['enable_sandbox'])  && $woocommerce_square_plus_settings['enable_sandbox'] == 'yes' ){
					$token = $woocommerce_square_plus_settings['sandbox_access_token'];
					
				}
				$this->connect->set_access_token( $token );

				$transaction_status = $this->connect->get_transaction_status( $trans_id );
    
				if ( 'AUTHORIZED' === $transaction_status ) {
					
					
					$url = "https://connect.".WC_SQUARE_STAGING_URL.".com/v2/payments/$trans_id/complete";
					$headers = array(
						'Accept' => 'application/json',
						'Authorization' => 'Bearer '.$token,
						'Content-Type' => 'application/json',
						'Cache-Control' => 'no-cache'
					);
					
					$result = json_decode(wp_remote_retrieve_body(wp_remote_post($url, array(
							'method' => 'POST',
							'headers' => $headers,
							'httpversion' => '1.0',
							'sslverify' => false,
							'body' => ""
							)
						)
					)
					);
										
					if ( is_wp_error( $result ) ) {
						$order->add_order_note( __( 'Unable to capture charge!', 'woosquare' ) . ' ' . $result->get_error_message() );

						throw new Exception( $result->get_error_message() );
					} elseif ( ! empty( $result->errors ) ) {
						$order->add_order_note( __( 'Unable to capture charge!', 'woosquare' ) . ' ' . print_r( $result->errors, true ) );

						throw new Exception( print_r( $result->errors, true ) );
					} else {
						
						$woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
							
						if(!empty($woocommerce_square_plus_settings['enable_sandbox'])  && $woocommerce_square_plus_settings['enable_sandbox'] == 'yes' ){
							$msg = ' via Sandbox ';
						} else {
							$msg = '';
						}
							
						$order->add_order_note( sprintf( __( 'Square charge complete '.$msg.' (Charge ID: %s)', 'woosquare' ), $trans_id ) );
						update_post_meta( $order->id, '_square_charge_captured', 'yes' );
						$this->log( "Info: Capture successful for {$order_id}" );
					}
				}
			} catch ( Exception $e ) {
				$this->log( sprintf( __( 'Error unable to capture charge: %s', 'woosquare' ), $e->getMessage() ) );
			}
		}
	}

	/**
	 * Cancel authorization
	 *
	 * @param  int $order_id
	 */

	public function cancel_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( 'square_plus' === ( version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->payment_method : $order->get_payment_method() ) ) {
			try {
				$this->log( "Info: Cancel payment for order {$order_id}" );
				$trans_id = get_post_meta( $order_id, 'woosquare_transaction_id', true );
				$captured = get_post_meta( $order_id, '_square_charge_captured', true );
				
				$token    = get_option( 'woo_square_access_token' );
				$woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
				if(!empty($woocommerce_square_plus_settings['enable_sandbox'])  && $woocommerce_square_plus_settings['enable_sandbox'] == 'yes' ){
					$token = $woocommerce_square_plus_settings['sandbox_access_token'];
					
				}
				$this->connect->set_access_token( $token );
				
				$transaction_status = $this->connect->get_transaction_status( $trans_id );

				if ( 'AUTHORIZED' === $transaction_status ) {
					// $result = $this->connect->void_transaction( $location, $trans_id ); // returns empty object
					$url = "https://connect.".WC_SQUARE_STAGING_URL.".com/v2/payments/$trans_id/cancel";
					$headers = array(
						'Accept' => 'application/json',
						'Authorization' => 'Bearer '.$token,
						'Content-Type' => 'application/json',
						'Cache-Control' => 'no-cache'
					);
					
					$result = json_decode(wp_remote_retrieve_body(wp_remote_post($url, array(
							'method' => 'POST',
							'headers' => $headers,
							'httpversion' => '1.0',
							'sslverify' => false,
							'body' => ""
							)
						)
					)
					);
					$transaction_status = $this->connect->get_transaction_status( $trans_id );
					if ( is_wp_error( $result ) ) {
						$order->add_order_note( __( 'Unable to void charge!', 'woosquare' ) . ' ' . $result->get_error_message() );
						throw new Exception( $result->get_error_message() );
					} elseif ( ! empty( $result->errors ) ) {
						$order->add_order_note( __( 'Unable to void charge!', 'woosquare' ) . ' ' . print_r( $result->errors, true ) );
						throw new Exception( print_r( $result->errors, true ) );
					} else if ( 'VOIDED' === $transaction_status )  {
						$order->add_order_note( sprintf( __( 'Square charge voided! (Charge ID: %s)', 'woosquare' ), $trans_id ) );
						delete_post_meta( $order_id, '_square_charge_captured' );
						delete_post_meta( $order_id, 'woosquare_transaction_id' );
					}	
				}
			} catch ( Exception $e ) {
				$this->log( sprintf( __( 'Unable to void charge!: %s', 'woosquare' ), $e->getMessage() ) );
			}
		}
	}

	/**
	 * Logs
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 *
	 * @param string $message
	 */
	public function log( $message ) {
		if ( $this->logging ) {
			WooSquare_Payment_Logger::log( $message );
		}
	}
}

new WooSquare_Payments( new WooSquare_Payments_Connect() );
