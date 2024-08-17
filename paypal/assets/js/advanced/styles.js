/**
 * Styles the PayPal input fields, using the card holder name field as a guide.
 * This ensures that the fields PayPal builds in the iframe match the existing
 * input fields on the site.
 *
 * @returns {{}}
 */
export default function buildInputStyles() {
	const cardNameEl = document.getElementById( 'card-holder-name' );
	if ( !cardNameEl ) {
		return {};
	}

	const inputStyles = window.getComputedStyle( cardNameEl );

	const styleTag = document.createElement( 'style' );

	styleTag.innerHTML = `
			#card-number,
			#expiration-date,
			#cvv {
				background-color: ${inputStyles.getPropertyValue( 'background-color' )};
				height: ${cardNameEl.offsetHeight}px;

				${
		['top', 'right', 'bottom', 'left']
			.map( ( dir ) => (
				`border-${dir}-color: ${inputStyles.getPropertyValue( `border-${dir}-color` )};
							 border-${dir}-width: ${inputStyles.getPropertyValue( `border-${dir}-width` )};
							 border-${dir}-style: ${inputStyles.getPropertyValue( `border-${dir}-style` )};
							 padding-${dir}: ${inputStyles.getPropertyValue( `padding-${dir}` )};`
			) )
			.join( '' )
	}
				${
		['top-right', 'bottom-right', 'bottom-left', 'top-left']
			.map( ( dir ) => (
				`border-${dir}-radius: ${inputStyles.getPropertyValue( 'border-top-right-radius' )};`
			) )
			.join( '' )
	}
			}`
		// Remove whitespace.
		.replace( /\s/g, '' );

	styleTag.id = 'edd-paypal-pro-element-styles';

	document.body.appendChild( styleTag );

	return {
		color: inputStyles.getPropertyValue( 'color' ),
		'font-family': inputStyles.getPropertyValue( 'font-family' ),
		'font-size': inputStyles.getPropertyValue( 'font-size' ),
		'font-weight': inputStyles.getPropertyValue( 'font-weight' ),
		'letter-spacing': inputStyles.getPropertyValue( 'letter-spacing' ),
		'line-height': inputStyles.getPropertyValue( 'line-height' )
	};
}
