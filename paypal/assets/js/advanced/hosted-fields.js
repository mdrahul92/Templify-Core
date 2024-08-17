/* global eddPayPalVars, edd_global_vars, eddPayPalPro */

import {setErrorHtml, clearErrors, resetLoadingState, buildErrorHtml} from "./errors";
import buildInputStyles from "./styles";
import { hasLiabilityShifted, isThreeDSecureAuthenticated } from "./3ds";
import {isPayPalGateway} from "./utils";

export default function setupHostedFields() {
	const form = document.getElementById( 'edd_purchase_form' );
	const nonceEl = form.querySelector( 'input[name="edd_process_paypal_nonce"]' );

	paypal.HostedFields.render( {
		createOrder: () => {
			return fetch( edd_scripts.ajaxurl, {
				method: 'POST',
				body: new FormData( form )
			} ).then( function ( response ) {
				return response.json();
			} ).then( function ( orderData ) {
				if ( orderData.data && orderData.data.paypal_order_id ) {
					// Add the nonce to the form so we can validate it later.
					if ( orderData.data.nonce ) {
						nonceEl.value = orderData.data.nonce;
					}

					return orderData.data.paypal_order_id;
				} else {
					// Error message.
					var errorHtml = eddPayPalVars.defaultError;
					if ( orderData.data && 'string' === typeof orderData.data ) {
						errorHtml = orderData.data;
					} else if ( 'string' === typeof orderData ) {
						errorHtml = orderData;
					}

					return new Promise( function ( resolve, reject ) {
						reject( new Error( errorHtml ) );
					} );
				}
			} );
		},
		styles: {
			input: buildInputStyles(),
			'.invalid': {
				'color': 'red'
			}
		},
		fields: {
			number: {
				selector: '#card-number',
				placeholder: ''
			},
			cvv: {
				selector: '#cvv',
				placeholder: ''
			},
			expirationDate: {
				selector: '#expiration-date',
				placeholder: 'MM/YY'
			}
		}
	} ).then( cardFields => {
		// This has to be jQuery because that's what EDD core uses.
		jQuery( '#edd_purchase_form' ).on( 'submit', e => {
			if ( ! isPayPalGateway() ) {
				return;
			}

			e.preventDefault();

			clearErrors();

			cardFields.submit( {
				cardholderName: document.getElementById( 'card-holder-name' ).value,
				contingencies: ['3D_SECURE'] // SCA_WHEN_REQUIRED
			} ).then( ( payload ) => {
				if ( isThreeDSecureAuthenticated( payload ) && ! hasLiabilityShifted( payload ) ) {
					console.log( '3DS Authentication Error', payload );

					throw new Error( eddPayPalPro.threeDSecureError );
				}

				const formData = new FormData( form );

				formData.delete( 'action' );
				formData.delete( 'edd_action' );
				formData.append( 'action', 'edd_capture_paypal_order' );
				formData.append( 'token', eddPayPalPro.token );
				formData.append( 'timestamp', eddPayPalPro.timestamp );

				if ( payload.orderId ) {
					formData.append( 'paypal_order_id', payload.orderId );
				}

				return fetch( edd_scripts.ajaxurl, {
					method: 'POST',
					body: formData
				} );
			} ).then( function ( response ) {
				return response.json();
			} ).then( function ( responseData ) {
				if ( responseData.success && responseData.data.redirect_url ) {
					window.location = responseData.data.redirect_url;
				} else {
					resetLoadingState();

					const errorMessage = responseData.data.message ? responseData.data.message : eddPayPalVars.defaultError;

					setErrorHtml( errorMessage );
				}
			} ).catch( err => {
				console.log( 'Payment Error', err );
				resetLoadingState();

				if ( err.details && Array.isArray( err.details ) ) {
					const errors = [];

					err.details.forEach( errorDetail => {
						if ( errorDetail.field && errorDetail.description ) {
							let errorMessage = '';

							/*
							 * PayPal's provided error descriptions do not clearly specify which field it's talking
							 * about. For example: the message might read, "The value of a field is either too short
							 * or too long". That's not helpful to the end user. So, depending on the `field` value,
							 * we prefix the error description with the name of the field. So that error message
							 * becomes: "Expiration Date: The value of a field is either too short or too long."
							 * Still not great, but at least it more clearly points to which field it is.
							 */
							if ( -1 !== errorDetail.field.indexOf( 'expiry' ) ) {
								errorMessage += eddPayPalPro.prefixExpiration;
							} else if ( -1 !== errorDetail.field.indexOf( 'number' ) ) {
								errorMessage += eddPayPalPro.prefixCardNumber;
							} else if ( -1 !== errorDetail.field.indexOf( 'security_code' ) ) {
								errorMessage += eddPayPalPro.prefixCvv;
							}

							if ( errorMessage ) {
								errorMessage += ': ' + errorDetail.description;
							}

							errors.push( errorMessage );
						}
					} );

					if ( errors.length ) {
						setErrorHtml( buildErrorHtml( errors ) );
						return;
					}
				}

				setErrorHtml( eddPayPalVars.defaultError );
			} );
		} );
	} );
}
