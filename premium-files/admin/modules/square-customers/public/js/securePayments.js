jQuery(document).ready(function ($) {
    $('.button-add-card').click(function () {
       $('.addCardsWrapper').slideToggle()
    })
})

/*
 * function: requestCardNonce
 *
 * requestCardNonce is triggered when the "Pay with credit card" button is
 * clicked
 *
 * Modifying this function is not required, but can be customized if you
 * wish to take additional action when the form button is clicked.
 */
function requestCardNonce(event) {

    // Don't submit the form until SqPaymentForm returns with a nonce
    event.preventDefault();

    // Request a nonce from the SqPaymentForm object
    paymentForm.requestCardNonce();
}
// Create and initialize a payment form object
var paymentForm = new SqPaymentForm({

    // Initialize the payment form elements
    applicationId: square_params.application_id,
    locationId: square_params.location_id,
    inputClass: 'sq-input',

    // Customize the CSS for SqPaymentForm iframe elements
    inputStyles: [{
        fontSize: '.9em'
    }],

    // Initialize Apple Pay placeholder ID
    applePay: {
        elementId: 'sq-apple-pay'
    },

    // Initialize Masterpass placeholder ID
    masterpass: {
        elementId: 'sq-masterpass'
    },

    // Initialize the credit card placeholders
    cardNumber: {
        elementId: 'sq-card-number',
        placeholder: '•••• •••• •••• ••••'
    },
    cvv: {
        elementId: 'sq-cvv',
        placeholder: 'CVV'
    },
    expirationDate: {
        elementId: 'sq-expiration-date',
        placeholder: 'MM/YY'
    },
    postalCode: {
        elementId: 'sq-postal-code',
        placeholder: 'Postal code'
    },

    // SqPaymentForm callback functions
    callbacks: {

        /*
         * callback function: methodsSupported
         * Triggered when: the page is loaded.
         */
        methodsSupported: function (methods) {

        },

        /*
         * callback function: createPaymentRequest
         * Triggered when: a digital wallet payment button is clicked.
         */
        createPaymentRequest: function () {

            var paymentRequestJson ;
            /* ADD CODE TO SET/CREATE paymentRequestJson */
            return paymentRequestJson ;
        },

        /*
         * callback function: validateShippingContact
         * Triggered when: a shipping address is selected/changed in a digital
         *                 wallet UI that supports address selection.
         */
        validateShippingContact: function (contact) {

            var validationErrorObj ;
            /* ADD CODE TO SET validationErrorObj IF ERRORS ARE FOUND */
            return validationErrorObj ;
        },

        /*
         * callback function: cardNonceResponseReceived
         * Triggered when: SqPaymentForm completes a card nonce request
         */
        cardNonceResponseReceived: function(errors, nonce, cardData) {
			
            jQuery( '.addCardErrors' ).eq(0).html('')
            if (errors) {
                var html = '';
                html += '<div id="message" class="error notice is-dismissible"><ul class="woocommerce_error woocommerce-error">';
                // handle errors
                jQuery( errors ).each( function( index, error ) {
                    html += '<li>' + error.message + '</li>';
                });
                html += '</ul></div>';
                jQuery( '.addCardErrors' ).eq(0).html( html );
                return;
            }
			
            document.getElementById('card-nonce').value = nonce;
			const verificationDetails = { 
				intent: 'STORE', 
				amount: '0', 
				currencyCode: square_params.get_woocommerce_currency, 
				billingContact: {}
			  }; 
			 try {
				paymentForm.verifyBuyer(
				  nonce,
				  verificationDetails,
				  function(err,verification) {
					if (err == null) {
						jQuery('.buyerVerification-token').val(verification.token);
						
						jQuery(document).ready(function ($) {
							$('#addCardForm').submit();
						});
					}
				});
				// POST the nonce form to the payment processing page
				// document.getElementById('nonce-form').submit();
			  } catch (typeError) {
				//TypeError thrown if illegal arguments are passed
			  }
           
        },

        /*
         * callback function: unsupportedBrowserDetected
         * Triggered when: the page loads and an unsupported browser is detected
         */
        unsupportedBrowserDetected: function() {
            alert('Unsupported Browser Detected');
        },

        /*
         * callback function: inputEventReceived
         * Triggered when: visitors interact with SqPaymentForm iframe elements.
         */
        inputEventReceived: function(inputEvent) {

        },

        /*
         * callback function: paymentFormLoaded
         * Triggered when: SqPaymentForm is fully loaded
         */
        paymentFormLoaded: function() {
            /* HANDLE AS DESIRED */
        }
    }
});