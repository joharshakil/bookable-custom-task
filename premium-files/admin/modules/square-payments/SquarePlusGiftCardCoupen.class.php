								<?php 


             session_start();
			
		
			$id = 'square_gift_card_coupen_pay';
			global $token   ;
			$token           = get_option( 'woo_square_access_token' );
			global $amount;
			$woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
			if(!empty($woocommerce_square_plus_settings['enable_sandbox'])  && $woocommerce_square_plus_settings['enable_sandbox'] == 'yes' ){		
				$token = $woocommerce_square_plus_settings['sandbox_access_token'];
			}	
			
			add_action( 'wp_enqueue_scripts', 'payment_scripts_giftcardpay' );
			add_action( 'admin_notices',  'admin_notices_giftcardpay'  );
			add_action( 'woocommerce_update_options_payment_gateways_' . $id,  'process_admin_options' );
			add_action( 'woocommerce_review_order_before_payment', 'woosquare_display_form' , 3 );
			add_action('wp_ajax_sqaure_redeem_coupen_code',  'woosqaure_redeem_coupen_code',5);
			add_action('wp_ajax_nopriv_sqaure_redeem_coupen_code', 'woosqaure_redeem_coupen_code',5);
			
			add_action('wp_ajax_sqaure_redeem_coupen_code_cancel_payment',  'sqaure_redeem_coupen_code_cancel_payment');
			add_action('wp_ajax_nopriv_sqaure_redeem_coupen_code_cancel_payment', 'sqaure_redeem_coupen_code_cancel_payment');

			add_action( 'woocommerce_order_status_on-hold_to_cancelled', 'woosquare_gift_Cancel_payment' );
			add_action( 'woocommerce_order_status_processing_to_cancelled', 'woosquare_gift_Cancel_payment');
			add_action( 'woocommerce_order_status_on-hold_to_refunded', 'woosquare_gift_Refund_payment' );
			add_action( 'woocommerce_order_status_processing_to_refunded', 'woosquare_gift_Refund_payment');
	    	add_action('woocommerce_checkout_order_processed', 'woosquare_checkout_order_processed_square_capture', 10, 1);


			function sqaure_redeem_coupen_code_cancel_payment()
	    	{ 

          if($_POST['action'] == 'sqaure_redeem_coupen_code_cancel_payment'){
			
				
			if($_POST['paymentID']){
			  $trans_id = $_POST['paymentID'];
			} else {
			$trans_id = get_option('gift_card_create_order'.$_POST['orderID']);
			}
			
			if(!empty($trans_id)){	
					$token    = get_option( 'woo_square_access_token' );
					$woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
					if(!empty($woocommerce_square_plus_settings['enable_sandbox'])  && $woocommerce_square_plus_settings['enable_sandbox'] == 'yes' ){
						$token = $woocommerce_square_plus_settings['sandbox_access_token'];
					}
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

            
				update_option('check_result_'.mktime(),$result->payment->id);
			
						if ( is_wp_error( $result ) ) {
							update_option('cancel_payment_gf_card'.$trans_id,$result->get_error_message() );
							$result = json_encode($result);
							unset($_SESSION["sq_square_gift_amount"]);
					    unset($_SESSION['orderID']);
					    unset($_SESSION['payment_id']);
					    unset($_SESSION['balance']);
					    unset($_SESSION['amountToPay']);
						unset($_SESSION['paidamount']);
							echo  $result;
				
						} elseif ( ! empty( $result->errors ) ) {
							update_option('cancel_payment_gf_card'.$trans_id,$result->errors() );
							$result = json_encode($result);
					unset($_SESSION["sq_square_gift_amount"]);
					unset($_SESSION['orderID']);
					unset($_SESSION['payment_id']);
					unset($_SESSION['balance']);
					unset($_SESSION['amountToPay']);
					unset($_SESSION['paidamount']);
							echo  $result ;
						} else if ( 'VOIDED' === $result->payment->card_details->status )  {
							update_option('square_gift_card_charge'.$trans_id,$result);
							$result = json_encode($result);
				delete_transient( 'squ_giftfee' );
					unset($_SESSION["sq_square_gift_amount"]);
					unset($_SESSION['orderID']);
					unset($_SESSION['payment_id']);
					unset($_SESSION['balance']);
					unset($_SESSION['amountToPay']);
			    	unset($_SESSION['paidamount']);
			
							echo $result;
					
						}	
				  }
			
			}
			
			die();
		}

			function woosqaure_redeem_coupen_code()
	    	{ 

			if(!empty($_POST['nonce'])){
				
				$token           = get_option( 'woo_square_access_token' );
		
				$woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
				if(!empty($woocommerce_square_plus_settings['enable_sandbox'])  && $woocommerce_square_plus_settings['enable_sandbox'] == 'yes' ){		
					$token = $woocommerce_square_plus_settings['sandbox_access_token'];
				}	
				
				global $woocommerce;
				$amountToPay = (int)format_amount(  $woocommerce->cart->total, $_POST['currency_code'] );
				$woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');	
				$nonce = $_POST['nonce'];
				if($woocommerce_square_plus_settings['enable_sandbox'] == 'yes'){
				 $nonce = 'cnon:gift-card-nonce-ok';
				}
				$endpoint = 'squareup';
			    $location_id = get_option('woo_square_location_id');
			   if($woocommerce_square_plus_settings['enable_sandbox'] == 'yes'){
				$msg = ' via Sandbox ';
				$location_id = $woocommerce_square_plus_settings['sandbox_location_id'];
				$endpoint = 'squareupsandbox';
			}

				$idempotency_key = uniqid();
				$data = array(
					'idempotency_key' => $idempotency_key,
					'amount_money'    => array( 
						'amount'   => $amountToPay,
						'currency' => $_POST['currency_code'],
					),
					'reference_id'       => (string)$_POST['orderID'],
					'delay_duration'    => 'PT10M',
					'autocomplete'       => false,
					'accept_partial_authorization' => true,
					'source_id'          => $nonce,
					'location_id'        => $location_id,
					"note"               => "Square Gift Card Coupen Code",
				);


				$url = "https://connect.".WC_SQUARE_STAGING_URL.".com/v2/payments";
				$headers = array(
					'Accept' => 'application/json',
					'Authorization' => 'Bearer ' . $token,
					'Content-Type' => 'application/json',
					'Cache-Control' => 'no-cache'
				);
			
				update_option('gift_card_request_'.date('m/d/Y h:i:s a', time()), $data);
				$result = json_decode(wp_remote_retrieve_body(wp_remote_post($url, array(
						'method' => 'POST',
						'headers' => $headers,
						'httpversion' => '1.0',
						'sslverify' => false,
						'body' => json_encode($data)
						)
						)
					)
				);

				
				update_option('gift_card_response_'.date('m/d/Y h:i:s a', time()), $result);
				$balance = 0;
			
				$paidamount = ($result->payment->amount_money->amount / 100);
				
				$balance = ($amountToPay /100) - $paidamount;

				$result->payment->balance = $balance;	
				$_SESSION['orderID'] = $_POST['orderID'];
				$_SESSION['payment_id'] = $result->payment->id;
				$_SESSION['balance'] = $balance;
				$_SESSION['amountToPay'] = $amountToPay;
				$_SESSION['paidamount'] = $paidamount;
                set_transient( 'squ_giftfee_session', $result, 600 );
				update_option('gift_card_create_order'.$_POST['orderID'], $result->payment->id);
				$result = json_encode($result);
				echo $result;
			}
			
	    	die();
		 }

			
	    add_action( 'wp_footer', 'woocommerce_add_square_gift_box');
	    function woocommerce_add_square_gift_box() {
		if (is_checkout()) {
		?>
		<script type="text/javascript">
		jQuery( document ).ready(function( $ ) {
			$('#add_gift_box').click(function(){
				jQuery('body').trigger('update_checkout');
			});
		});
		</script>
		<?php
		}
	}

		add_action( 'woocommerce_cart_calculate_fees', 'woo_add_square_giftcard_cart_fee' );
		function woo_add_square_giftcard_cart_fee( $cart ){

				if ( ! $_POST || ( is_admin() && ! is_ajax()) || empty($_SESSION) ) {
				return;
			}

	
			if ( isset( $_POST['post_data'] ) ) {
				parse_str( $_POST['post_data'], $post_data );
			} else {
				$post_data = $_POST; // fallback for final checkout (non-ajax)
			}


    if (isset($post_data['add_gift_box'])) {
        $extracost = $post_data['add_gift_box']; // not sure why you used intval($_POST['state']) ?
      	WC()->cart->add_fee( 'Gift Card', - $extracost );
      	
      
      	set_transient( 'squ_giftfee', $post_data, 600 ); // Site Transient

    } else {
        
        
       delete_transient( 'squ_giftfee' );
    }
    
    
    
     

		
			
			// if (isset($post_data['add_gift_box'])) {
			// 	$extracost = $post_data['add_gift_box']; // not sure why you used intval($_POST['state']) ?
			// 	WC()->cart->add_fee( 'Square Gift Card ', -$extracost );
			// }
		
		}

		 function admin_notices_giftcardpay() {
			// Show message if enabled and FORCE SSL is disabled and WordpressHTTPS plugin is not detected
			if ( ! WC_SQUARE_ENABLE_STAGING && get_option( 'woocommerce_force_ssl_checkout' ) === 'no' && ! class_exists( 'WordPressHTTPS' ) ) {
				echo '<div class="error"><p>' . sprintf( __( 'Square is enabled, but the <a href="%s">force SSL option</a> is disabled; your checkout is not secured! Please enable SSL and ensure your server has a valid SSL certificate.', 'wpexpert-square' ), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) . '</p></div>';
			}
		}

		/**
		 * Check if this gateway is enabled
		 */
		 function is_available() {

			$is_available = true;
			
			if ( $enabled == 'yes' ) {
				if ( ! WC_SQUARE_ENABLE_STAGING && ! wc_checkout_is_https() ) {
					$is_available = false;
				}

				if ( ! WC_SQUARE_ENABLE_STAGING && empty( $token ) ) {
					$is_available = true;
				}
				

				if ( !get_option( 'woo_square_access_token_cauth' )) {
					$is_available = false;
				}
				

				// Square only supports US, Canada and Australia for now.
				if ( ( 
					'US' !== WC()->countries->get_base_country() && 
					'CA' !== WC()->countries->get_base_country() && 
					'GB' !== WC()->countries->get_base_country() &&
					'JP' !== WC()->countries->get_base_country() &&
					'AU' !== WC()->countries->get_base_country() ) || ( 
					'USD' !== get_woocommerce_currency() && 
					'CAD' !== get_woocommerce_currency() && 
					'JPY' !== get_woocommerce_currency() && 
					'AUD' !== get_woocommerce_currency() && 
					'GBP' !== get_woocommerce_currency() ) 
					) {
					$is_available = false;
				}
				
				
				// if enabled and sandbox credentials not setup.
				$woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
				if($woocommerce_square_plus_settings['enable_sandbox'] == 'yes'){
					if(
						empty(WOOSQU_PLUS_APPID)
						||
						empty(get_option('woo_square_access_token_sandbox'))
						||
						empty(get_option('woo_square_location_id_sandbox'))
					){
						$is_available = false;
					}
				}
							
				
			} else {
				$is_available = false;
			}
			
			return apply_filters( 'woocommerce_square_payment_giftcardpay_gateway_is_available', $is_available );
		}
		
		function woosquare_display_form() { ?>

           <div class="" id="sq_amount_result"></div>
			<div class="add_woosquare_gift_card_form">

			<h4><?php esc_html_e( 'Have a gift card?', 'woosquare-plus' ); ?></h4>
			<div id="wc_woosquare_gc_cart_redeem_form">
				<div class="woowoosquare_gift_card_coupen_code_notices"></div>
				<label for="sq-gift-card-coupen"><?php esc_html_e( 'Enter your gift card code&hellip;', 'woosquare' );  ?>	
				<div id="sq-gift-card-coupen"></div>
				<button type="button" name="woosquare_get_cart_redeem_send" id="woosquare_get_cart_redeem_send"><?php esc_html_e( 'Apply', 'woosquare-plus' ); ?></button>
		
				</div>
			</div>
			<br>
			<br>
	  
	  <?php	}

	      /**
	 * get_country_code_scripts function.
	 *
	 *
	 * @access public
	 */
    
     function get_country_codes( $currency_code ) {

		$currency_symbol = '';

		switch( $currency_code ) {
			case 'USD':
				$currency_symbol = 'US';
				break;
			case 'CAD':
				$currency_symbol = 'CA';
				break;
			case 'GBP':
				$currency_symbol = 'GB';
				break;
		}
	
		return $currency_symbol;
	
	}

		/**
		 * payment_scripts function.
		 *
		 *
		 * @access public
		 */

		 function payment_scripts_giftcardpay() {
			if ( ! is_checkout() ) {
				return;
			}
			$location = get_option('woo_square_location_id');
			$woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
			
			
			global $woocommerce;
			$woocommerce_square_settings = get_option('woocommerce_square_settings');
			//need to add condition square payment enable so disable below script.
			if($woocommerce_square_plus_settings['enable_sandbox'] == 'yes'){
				$endpoint = 'squareupsandbox';
				$location = $woocommerce_square_plus_settings['sandbox_location_id'];
				$environment = 'sandbox';
			} else {
			    $endpoint = 'squareup';
				$environment = 'production';
			}

			$woocommerce_square_settings = get_option('woocommerce_square_settings');
			$currency_cod  = get_option('woocommerce_currency');
			$country_code = get_country_codes($currency_cod);
			
			
			
            if(empty(get_transient( 'squ_giftfee_session' ))){
                unset($_SESSION["sq_square_gift_amount"]);
        	    unset($_SESSION['orderID']);
        	    unset($_SESSION['payment_id']);
        	    unset($_SESSION['balance']);
        	    unset($_SESSION['amountToPay']);
        		unset($_SESSION['paidamount']);    
            }
            if(!empty(get_transient( 'squ_giftfee' ))){
                $squ_giftfee = true;
            } else { 
               $squ_giftfee = false;
                
            }
			if(!empty($_SESSION['payment_id'])){
			 $session_payment_id =	$_SESSION['payment_id'];
			} else {
				$session_payment_id =	'' ;
			}

			if(!empty($_SESSION['paidamount'])){
				$session_payment =	$_SESSION['paidamount'];
			   } else {
				   $session_payment =	'' ;
			   }

		   
			wp_register_script( 'square', 'https://js.'.$endpoint.'.com/v2/paymentform', '', '0.0.2', true );
			wp_register_script( 'woosquare-gift-coupen-card-pay', WooSquare_PLUGIN_URL_PAYMENT . '/js/SquarePaymentsGiftCardCoupenPay.js', array( 'jquery', 'square' ), WooSquare_VERSION, true );
			wp_localize_script( 'woosquare-gift-coupen-card-pay', 'squaregiftcardcoupenpay_params', array(
				'application_id'               =>   WOOSQU_PLUS_APPID,
				'lid'						   =>   $location,
				'order_total'				   =>   $woocommerce->cart->total,
				'environment'                  =>   $environment,
				'currency_code'                =>   $currency_cod, 
				'country_code'                 => 	$country_code,
				'ajax_url'                     =>   admin_url( 'admin-ajax.php' ),
				'unique_id'                    =>   uniqid(),
				'get_amount_store'             =>   $session_payment,
				'squ_giftfee'             =>        $squ_giftfee,
				'square_payment_id'            =>   $session_payment_id,
				 'currency_symbol'            =>     get_woocommerce_currency_symbol() ,
			) );

			wp_enqueue_script( 'woosquare-gift-coupen-card-pay' );
			wp_enqueue_style( 'woocommerce-square-giftcardoupenpay-styles', WooSquare_PLUGIN_URL_PAYMENT . '/css/SquareFrontendStyles_giftcardcoupen_pay.css' );
	
			return true;
		}
 

		/**
		 * Process amount to be passed to Square.
		 * @return float
		 */
		 function format_amount( $total, $currency = '' ) {
		
			if ( ! $currency ) {
				$currency = get_woocommerce_currency();
			}

			switch ( strtoupper( $currency ) ) {
				// Zero decimal currencies
				case 'BIF' :
				case 'CLP' :
				case 'DJF' :
				case 'GNF' :
				case 'JPY' :
				case 'KMF' :
				case 'KRW' :
				case 'MGA' :
				case 'PYG' :
				case 'RWF' :
				case 'VND' :
				case 'VUV' :
				case 'XAF' :
				case 'XOF' :
				case 'XPF' :
					$total = absint( $total );
					break;
				default :
					$total = round( $total, 2 ) * 100; // In cents
					break;
			}
  
			return $total;
		}
  
	
		function woosquare_checkout_order_processed_square_capture( $order_id ) {
		$order = wc_get_order( $order_id );
		if(!empty($_POST['sq_payment_id_box']) && !empty($_POST['add_gift_box'])){
		    $payment_id	 =  $_POST['sq_payment_id_box'];
			update_post_meta($order_id, 'square_gift_card_coupen_payment_id', $_POST['sq_payment_id_box']);
			update_post_meta($order_id, 'square_gift_card_coupen_payment_amount', $_POST['add_gift_box']);
		
			$token = get_option( 'woo_square_access_token' );

			$woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
				if(!empty($woocommerce_square_plus_settings['enable_sandbox'])  && $woocommerce_square_plus_settings['enable_sandbox'] == 'yes' ){
					$token = $woocommerce_square_plus_settings['sandbox_access_token'];
				}

				$url = "https://connect.".WC_SQUARE_STAGING_URL.".com/v2/payments/$payment_id/complete";
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
							
						$order->add_order_note( sprintf( __( 'Square charge complete '.$msg.' (Charge ID: %s)', 'woosquare' ), $payment_id ) );
						update_post_meta( $order->id, 'square_gift_card_charge_captured', 'yes' );
						
					}		

	    	}

		
		}


		//Cancel Payment

	     function woosquare_gift_Cancel_payment( $order_id ) {

			$order = wc_get_order( $order_id );
			$trans_id = get_post_meta( $order_id, 'square_gift_card_coupen_payment_id', true );
			$captured = get_post_meta( $order_id, 'square_gift_card_charge_captured', true );
			
			if(!empty($trans_id) && $captured != 'yes'){	
					$token    = get_option( 'woo_square_access_token' );
					$woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
					if(!empty($woocommerce_square_plus_settings['enable_sandbox'])  && $woocommerce_square_plus_settings['enable_sandbox'] == 'yes' ){
						$token = $woocommerce_square_plus_settings['sandbox_access_token'];
					}
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

						if ( is_wp_error( $result ) ) {
							$order->add_order_note( __( 'Unable to void charge!', 'woosquare' ) . ' ' . $result->get_error_message() );
							throw new Exception( $result->get_error_message() );
						} elseif ( ! empty( $result->errors ) ) {
							$order->add_order_note( __( 'Unable to void charge!', 'woosquare' ) . ' ' . print_r( $result->errors, true ) );
							throw new Exception( print_r( $result->errors, true ) );
						} else if ( 'VOIDED' === $result->payment->card_details->status )  {
							$order->add_order_note( sprintf( __( 'Square charge voided! (Charge ID: %s)', 'woosquare' ), $trans_id ) );
							delete_post_meta( $order_id, 'square_gift_card_charge_captured' );
							delete_post_meta( $order_id, 'square_gift_card_coupen_payment_id' );
						}	
				  }
				
			}

			
	     	//Refund Payment
			 function woosquare_gift_Refund_payment( $order_id ) {

				$order = wc_get_order( $order_id );
				$trans_id = get_post_meta( $order_id, 'square_gift_card_coupen_payment_id', true );
				$captured = get_post_meta( $order_id, 'square_gift_card_charge_captured', true );
				$get_amount = (get_post_meta($order_id, 'square_gift_card_coupen_payment_amount', true));
				$token    = get_option( 'woo_square_access_token' );
				$woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
				if(!empty($woocommerce_square_plus_settings['enable_sandbox'])  && $woocommerce_square_plus_settings['enable_sandbox'] == 'yes' ){
					$token = $woocommerce_square_plus_settings['sandbox_access_token'];
				}

				if ( !empty($order) || !empty($trans_id) ) {
				
					
							$body = array();
							$currency = $order->get_order_currency();
							$body['idempotency_key'] = uniqid();
							
							if ( ! is_null( $get_amount ) ) {
								$body['amount_money'] = array(
									'amount'   =>  (int)format_amount( $get_amount , $currency ),
									'currency' => $currency,
								);
								$body['payment_id'] = $trans_id;
							}


						
							$url = "https://connect.".WC_SQUARE_STAGING_URL.".com/v2/refunds";
			
			
			
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
				    	'body' => json_encode($body)
				        	   )
			     	         )
		                    )
			              );
			
		
							if ( is_wp_error( $result ) ) {
								throw new Exception( $result->get_error_message() );
		
							} elseif ( ! empty( $result->errors ) ) {
								throw new Exception( "Error: " . print_r( $result->errors, true ) );
								
							} else {
								if ( 'APPROVED' === $result->refund->status || 'PENDING' === $result->refund->status ) {
									$refund_message = sprintf( __( 'Refunded %s - Refund ID: %s - Reason: %s', 'wpexpert-square' ), wc_price( $result->refund->amount_money->amount / 100 ), $result->refund->id, $reason );
								
									$order->add_order_note( $refund_message );
									return true;
								}
							}
						}
				   }
			
		
		
			