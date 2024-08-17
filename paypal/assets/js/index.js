import setupHostedFields from "./advanced/hosted-fields";
import { setErrorHtml } from "./advanced/errors";

document.addEventListener( 'edd_paypal_buttons_mounted', () => {
	const hostedFieldsWrap = document.getElementById( 'edd-paypal-pro-cc-fields' );
	if ( ! hostedFieldsWrap ) {
		return;
	}

	if ( paypal.HostedFields.isEligible() ) {
		jQuery( document.body ).on( 'edd_checkout_error', ( e, data ) => {
			if ( data.data ) {
				setErrorHtml( data.data );
			}
		} );

		setupHostedFields();
	} else {
		// Hide card fields if the merchant isn't eligible.
		hostedFieldsWrap.style = 'display: none';
	}
} );
