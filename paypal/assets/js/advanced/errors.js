/* global eddPayPalPro */

/**
 * Builds error HTML from an array of error messages.
 *
 * @param {string[]} errorMessages
 * @returns {string}
 */
export function buildErrorHtml( errorMessages ) {
	let errorHtml = '<div class="edd_errors edd-alert edd-alert-error">';
	errorMessages.forEach( errorMessage => {
		errorHtml += '<p class="edd_error"><strong>' + eddPayPalPro.error + '</strong>: ' + errorMessage + '</p>';
	} );
	errorHtml += '</div>';

	return errorHtml;
}

/**
 * Sets the error wrapper with the provided HTML.
 *
 * @param errorHtml
 */
export function setErrorHtml( errorHtml ) {
	const errorWrapper = document.getElementById( 'edd-paypal-pro-errors-wrap' );
	if ( errorWrapper ) {
		errorWrapper.innerHTML = errorHtml;
	}

	jQuery( document.body ).trigger( 'edd_checkout_error', [errorHtml] );
}

/**
 * Clears all form errors.
 */
export function clearErrors() {
	const errorWrapper = document.getElementById( 'edd-paypal-pro-errors-wrap' );
	if ( errorWrapper ) {
		errorWrapper.innerHTML = '';
	}
}

/**
 * Resets the "loading" form state.
 * 		- Removes the spinner.
 * 		- Re-enables the button.
 * 		- Sets the submit button text back to the original value.
 */
export function resetLoadingState() {
	// Remove the spinner.
	jQuery( '.edd-loading-ajax' ).remove();

	// Reset the purchase button.
	const purchaseButton = document.getElementById( 'edd-purchase-button' )
	if ( purchaseButton ) {
		purchaseButton.disabled = false;

		const originalValue = purchaseButton.getAttribute( 'data-original-value' );
		if ( originalValue ) {
			purchaseButton.value = originalValue;
		}
	}
}
