jQuery(document).ready(function ($) {
    $('<h3 class="form-table-white-heading add-card-form" id="addCard">Square add credit card</h3>\n' +
        '<table class="form-table form-table-white add-card-form">\n' +
        '    <tbody>\n' +
        '        <tr class="addCardErrors"></tr>\n' +
        '        <tr>\n' +
        '            <th>\n' +
        '                <label>Credit card number:</label>\n' +
        '            </th>\n' +
        '            <td>\n' +
        '                <div id="sq-card-number"></div>\n' +
        '            </td>\n' +
        '        </tr>\n' +
        '        <tr>\n' +
        '            <th>\n' +
        '                <label>Exp. date:</label>\n' +
        '            </th>\n' +
        '            <td>\n' +
        '                <div id="sq-expiration-date"></div>\n' +
        '            </td>\n' +
        '        </tr>\n' +
        '        <tr>\n' +
        '            <th>\n' +
        '                <label>CCV:</label>\n' +
        '            </th>\n' +
        '            <td>\n' +
        '                <div id="sq-cvv"></div>\n' +
        '            </td>\n' +
        '        </tr>\n' +
        '        <tr>\n' +
        '            <th>\n' +
        '                <label>Postal code:</label>\n' +
        '            </th>\n' +
        '            <td>\n' +
        '                <div id="sq-postal-code"></div>\n' +
        '            </td>\n' +
        '        </tr>\n' +
        '        <tr>\n' +
        '            <th>\n' +
        '                 <button id="sq-creditcard" class="button-credit-card button button-primary" onclick="requestCardNonce(event)">\n' +
        '        \t\t\tAdd Card\n' +
        '        \t\t</button>\n' +
        '            </th>\n' +
        '            <th></th>\n' +
        '        </tr>\n' +
        '    </tbody>\n' +
        '</table>\n' +
        '\n' +
        '<div id="sq-walletbox" style="display: none;">\n' +
        '    Pay with a Digital Wallet\n' +
        '    <div id="sq-apple-pay-label" class="wallet-not-enabled">Apple Pay for Web not enabled</div>\n' +
        '    <!-- Placeholder for Apple Pay for Web button -->\n' +
        '    <button id="sq-apple-pay" class="button-apple-pay"></button>\n' +
        '\n' +
        '    <div id="sq-masterpass-label" class="wallet-not-enabled">Masterpass not enabled</div>\n' +
        '    <!-- Placeholder for Masterpass button -->\n' +
        '    <button id="sq-masterpass" class="button-masterpass"></button>\n' +
        '</div>').appendTo('#profile-page');
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
                html += '<td style="padding-bottom: 0;"><div style="margin: 0;" id="message" class="error notice is-dismissible"><ul class="woocommerce_error woocommerce-error">';
                // handle errors
                jQuery( errors ).each( function( index, error ) {
                    html += '<li>' + error.message + '</li>';
                });
                html += '</ul></div></td>';
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
							$('#submit').click();
						})
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