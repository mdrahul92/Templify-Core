export function isPayPalGateway() {
	let chosenGateway = '';
	const gatewayInput = document.querySelector( 'input.edd-gateway:checked' );
	if ( gatewayInput ) {
		chosenGateway = gatewayInput.value;
	} else {
		const chosenGatewayMeta = document.querySelector( 'meta[name="edd-chosen-gateway"]' );
		if ( chosenGatewayMeta ) {
			chosenGateway = chosenGatewayMeta.getAttribute( 'content' );
		}
	}

	return chosenGateway === 'paypal_commerce';
}
