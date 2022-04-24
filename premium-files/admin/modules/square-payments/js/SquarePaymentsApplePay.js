(function ( $ ) {
	'use strict';

	
	var paymentForm;

	// create namespace to avoid any possible conflicts
	$.WooSquare_Apple_Pay_payments = {
		init: function() {
			// Checkout page
			$( document.body ).on( 'updated_checkout', function() {
				$.WooSquare_Apple_Pay_payments.loadForm();	
			});

			// Pay order form page
			if ( $( 'form#order_review' ).length ) {
				$.WooSquare_Apple_Pay_payments.loadForm();
			}

			var custom_element = square_params.custom_form_trigger_element;

			// custom click trigger for 3rd party forms that initially hides the payment form
			// such as multistep checkout plugins
			if ( custom_element.length ) {
				$( document.body ).on( 'click', custom_element, function() {
					$.WooSquare_Apple_Pay_payments.loadForm();		
				});
			}

			// work around for iFrame not loading if elements being replaced is hidden
			$( document.body ).on( 'click', '#WooSquare_Apple_Pay_payments', function() {
				$( '.payment_box.WooSquare_Apple_Pay_payments' ).css( { 'display': 'block', 'visibility': 'visible', 'height': 'auto' } );	
			});
		},
		loadForm: function() {
			
			if ( $( '#payment_method_square_apple_pay' ).length ) {
				// work around for iFrame not loading if elements being replaced is hidden
				if ( ! $( '#payment_method_square_apple_pay' ).is( ':checked' ) ) {
					$( '.payment_box.payment_method_square_apple_pay' ).css( { 'display': 'block', 'visibility': 'hidden', 'height': '0' } );
				}

				
                // destroy the form and rebuild on each init
				if ( 'object' === $.type( paymentForm ) ) {
					paymentForm.destroy();
				}
							
				// Create and initialize a payment form object
                var paymentForm = new SqPaymentForm({
                env: squaregpay_params.environment,
                applicationId: squaregpay_params.application_id,
                locationId: squaregpay_params.lid,
                inputClass: 'sq-input',
                // Initialize apple Pay placeholder ID
                applePay: {
                    elementId: 'sq-apple-pay'
                },
                callbacks: {
						cardNonceResponseReceived: function(errors, nonce, cardData, billingContact, shippingContact) {
						    $( 'input.square-nonce' ).remove();
							if ( errors ) {
								var html = '';

								html += '<ul class="woocommerce_error woocommerce-error">';

								// handle errors
								$( errors ).each( function( index, error ) { 
									html += '<li>' + error.message + '</li>';
								});

								html += '</ul>';

								// append it to DOM
								$( '.payment_method_square_apple_pay fieldset' ).eq(0).prepend( html );
								var $form = $( 'form.woocommerce-checkout, form#order_review' );
								$form.append( '<input type="hidden" class="square_submit_error" name="square_submit_error" value="' + html + '" />' );
							} else if (nonce) {
                    				var noncedatatype = typeof nonce;
                    			
							    if(nonce || typeof nonce !== 'undefined'){
    								var $form = $( 'form.woocommerce-checkout, form#order_review' );
    								// inject nonce to a hidden field to be submitted
    								$form.append( '<input type="hidden" class="errors" name="errors" value="' + errors + '" />' );
    								$form.append( '<input type="hidden" class="noncedatatype" name="noncedatatype" value="' + noncedatatype + '" />' );
    								$form.append( '<input type="hidden" class="cardData" name="cardData" value="' + cardData + '" />' );
    								$form.append( '<input type="hidden" class="square-nonce" name="square_nonce" value="' + nonce + '" />' );
									
    								$form.submit();
						        }
							}
						},

						paymentFormLoaded: function() {
						//	paymentForm.setPostalCode( $( '#billing_postcode' ).val() );
						},

						unsupportedBrowserDetected: function() {
							var html = '';

							html += '<ul class="woocommerce_error woocommerce-error">';
							html += '<li>' + squaregpay_params.unsupported_browser + '</li>';
							html += '</ul>';

							// append it to DOM
							$( '.payment_method_square_apple_pay fieldset' ).eq(0).prepend( html );
						},
                    	/*
                         * callback function: createPaymentRequest
                         * Triggered when: a digital wallet payment button is clicked.
                         */
                        createPaymentRequest: function () {
                          var paymentRequestJson = {
                            requestShippingAddress: true,
                            requestBillingInfo: true,
                            currencyCode: squaregpay_params.currency_code,
                            countryCode: squaregpay_params.country_code,
                            total: {
                              label: squaregpay_params.merchant_name,
                              amount: squaregpay_params.order_total,
                              pending: false
                            },
                          };
                    
                          return paymentRequestJson;
                        },
                        methodsSupported: function (methods , unsupportedReason) {
							
							var applePayBtn = document.getElementById('sq-apple-pay');    
				
							if (methods.applePay === true) {
								applePayBtn.style.display = 'inline-block';
							} else {
								if (methods.applePay === false) {
								applePayBtn.style.display = 'none';
								$( "#browser_support_msg" ).text("Apple Pay is not available on this browser.");
								console.log(unsupportedReason);
								
								}
							  }
                        }
					},
					
                });

			
				paymentForm.build();
				
				
				
				// when checkout form is submitted on checkout page
				$( 'form.woocommerce-checkout' ).on( 'checkout_place_order_square', function( event ) {
					// remove any error messages first
					$( '.payment_method_square .woocommerce-error' ).remove();

					if ( $( '#payment_method_square' ).is( ':checked' ) && $( 'input.square-nonce' ).size() === 0 ) {
						wcSquarePaymentForm.requestCardNonce();

						return false;
					}

					return true;
				});


				$("#sq-apple-pay").click(function(event){
					event.preventDefault();
				});


				// work around for iFrame not loading if elements being replaced is hidden
				setTimeout( function() {
					if ( ! $( '#payment_method_square_apple_pay' ).is( ':checked' ) ) {
						$( '.payment_box.payment_method_square_apple_pay' ).css( { 'display': 'none', 'visibility': 'visible', 'height': 'auto' } );
					}
				}, 1000 );
			}
		}
	}; // close namespace

	 $.WooSquare_Apple_Pay_payments.init();
}( jQuery ) );


jQuery( window  ).load(function() {
    jQuery(".woocommerce-checkout-payment").on('change', '.input-radio', function(){
        setTimeout(explode, 300);   
    });
   setTimeout(explode, 1000);   
});


 jQuery( function($){
        $('form.checkout').on('change', '.woocommerce-checkout-payment input', function(){
           hideunhide();
        });
    });

function hideunhide(){
	   if( jQuery('.woocommerce-checkout-payment .input-radio:checked').val() == 'square_google_pay' ){
        	jQuery('#place_order').css('display', 'none');
        } else if(jQuery('.woocommerce-checkout-payment .input-radio:checked').val() == 'square_gift_card_pay' ) {
             jQuery('#place_order').css('display', 'block');
        } else if(jQuery('.woocommerce-checkout-payment .input-radio:checked').val() == 'square_plus' ){
                jQuery('#place_order').css('display', 'block');
        } else if(jQuery('.woocommerce-checkout-payment .input-radio:checked').val() == 'square_apple_pay' ){
			jQuery('#place_order').css('display', 'none');
	    } else {
                jQuery('#place_order').css('display', 'block');
        }
    }
	
function explode(){ 
	jQuery('.woocommerce-checkout-payment .input-radio').change(function() {
		console.log(jQuery('.woocommerce-checkout-payment .input-radio:checked').val());
        hideunhide();
    });
    
   console.log(jQuery('.woocommerce-checkout-payment .input-radio:checked').val());
    hideunhide();
   
}
