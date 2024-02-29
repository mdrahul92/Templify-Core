<?php
add_filter( 'edd_paypal_settings', function( $settings ) {
	$settings['paypal_disable_funding'] = array(
		'id'      => 'paypal_disable_funding',
		'name'    => __( 'Disable Funding Sources', 'edd-paypal-commerce-pro' ),
		'desc'    => __( 'Any funding sources selected are not displayed in the Smart Payment Buttons. Even if a source is not checked, it may not display, as source eligibility is decided by PayPal based on a variety of factors, such as country and currency.', 'edd-paypal-commerce-pro' ),
		'type'    => 'multicheck',
		'options' => array(
			'card'        => __( 'Credit or debit cards', 'edd-paypal-commerce-pro' ),
			'credit'      => __( 'PayPal Credit', 'edd-paypal-commerce-pro' ),
			'bancontact'  => 'Bancontact',
			'blik'        => 'BLIK',
			'eps'         => 'eps',
			'giropay'     => 'giropay',
			'ideal'       => 'iDEAL',
			'mercadopago' => 'Mercado Pago',
			'mybank'      => 'MyBank',
			'p24'         => 'Przelewy24',
			'sepa'        => 'SEPA-Lastschrift',
			'sofort'      => 'Sofort',
			'venmo'       => 'Venmo',
		),
	);

	return $settings;
}, 20 );
