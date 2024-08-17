<?php
/**
 * PayPal Settings
 *
 * @package   templify-paypal-pro
 * @copyright Copyright (c) 2021, Sandhills Development, LLC
 * @license   GPL2+
 */

namespace EDD_PayPal_Commerce_Pro\Advanced\Admin;

add_filter( 'edd_paypal_settings', function ( $settings ) {
	/*
	 * If the user was not onboarded for PPCP then we won't show
	 * them this option, as they're not eligible to use it anyway.
	 */
	if ( ! \EDD_PayPal_Commerce_Pro\Advanced\is_merchant_account_eligible_for_advanced_payments() ) {
		return $settings;
	}

	$desc = __( 'If enabled, eligible customers will be able to pay with a credit or debit card directly on your site.', 'templify-paypal-pro' );
	if ( ! \EDD_PayPal_Commerce_Pro\Advanced\is_merchant_account_ready() ) {
		$desc .= '<br>' . __( 'Warning: Your PayPal account is not ready to support this feature. Please reach out to PayPal to enable PPCP_CUSTOM for your account.', 'templify-paypal-pro' );
	}

	$settings['paypal_advanced_card_payments'] = array(
		'id'   => 'paypal_advanced_card_payments',
		'name' => __( 'Enabled Advanced Credit and Debit Card Payments', 'templify-paypal-pro' ),
		'desc' => $desc,
		'type' => 'checkbox',
	);

	return $settings;
} );

/**
 * Allows store owners to disable specific payment options.
 *
 * @since 1.0.1
 * @link https://developer.paypal.com/docs/checkout/reference/customize-sdk/#disable-funding
 */
add_filter( 'edd_paypal_settings', function( $settings ) {
	$settings['paypal_disable_funding'] = array(
		'id'      => 'paypal_disable_funding',
		'name'    => __( 'Disable Funding Sources', 'templify-paypal-pro' ),
		'desc'    => __( 'Any funding sources selected are not displayed in the Smart Payment Buttons. Even if a source is not checked, it may not display, as source eligibility is decided by PayPal based on a variety of factors, such as country and currency.', 'templify-paypal-pro' ),
		'type'    => 'multicheck',
		'options' => array(
			'card'        => __( 'Credit or debit cards', 'templify-paypal-pro' ),
			'credit'      => __( 'PayPal Credit', 'templify-paypal-pro' ),
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
