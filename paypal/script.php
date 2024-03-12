<?php
/**
 * Scripts
 *
 * @package   edd-paypal-commerce-pro
 * @copyright Copyright (c) 2021, Sandhills Development, LLC
 * @license   GPL2+
 * @since     1.0
 */

namespace EDD_PayPal_Commerce_Pro\Advanced;

use EDD\Utils\Tokenizer;
use EDD\Gateways\PayPal\API;
use EDD\Gateways\PayPal\Exceptions\Authentication_Exception;

/**
 * Enqueues checkout JS & CSS if advanced credit/debit is enabled.
 *
 * @since 1.0
 */
add_action( 'wp_enqueue_scripts', function () {

	if ( ! edd_is_gateway_active( 'paypal_commerce' ) ) {
		return;
	}

	if ( ! advanced_payments_enabled() ) {
		return;
	}

	try {
		$api = new API();
	} catch ( Authentication_Exception $e ) {
		return;
	}

	wp_register_script(
		'edd-paypal-pro-checkout',
		EDD_PAYPAL_PRO_URL . 'assets/build/index.js',
		array(
			'sandhills-paypal-js-sdk',
			'edd-ajax',
		),
		EDD_PAYPAL_PRO_VERSION,
		true
	);

	wp_register_style(
		'edd-paypal-pro-checkout',
		EDD_PAYPAL_PRO_URL . 'assets/build/style-frontend.css',
		array(),
		EDD_PAYPAL_PRO_VERSION
	);

	if ( edd_is_checkout() ) {
		wp_enqueue_script( 'edd-paypal-pro-checkout' );
		wp_enqueue_style( 'edd-paypal-pro-checkout' );

		$timestamp = time();
		wp_localize_script( 'edd-paypal-pro-checkout', 'eddPayPalPro', array(
			'error'             => esc_html__( 'Error', 'edd-paypal-commerce-pro' ),
			'prefixCardNumber'  => esc_html__( 'Card Number', 'edd-paypal-commerce-pro' ),
			'prefixCvv'         => esc_html__( 'CVV', 'edd-paypal-commerce-pro' ),
			'prefixExpiration'  => esc_html__( 'Expiration Date', 'edd-paypal-commerce-pro' ),
			'threeDSecureError' => esc_html__( 'An error occurred while authenticating your card. Please try again or use a different payment method.', 'edd-paypal-commerce-pro' ),
			'timestamp'         => $timestamp,
			'token'             => \EDD\Utils\Tokenizer::tokenize( $timestamp ),
		) );
	}
} );

/**
 * Loads hosted fields if Advanced Credit/Debit is enabled.
 *
 * @since 1.0
 */
add_filter( 'edd_paypal_js_sdk_query_args', function ( $args ) {
	if ( advanced_payments_enabled() ) {
		$args['components'] = 'buttons,hosted-fields';
	}

	return $args;
} );

/**
 * Adds a unique client token to the script tag. This has to be unique for each buyer.
 *
 * @link  https://developer.paypal.com/docs/business/checkout/advanced-card-payments/#2-generate-client-token
 *
 * @since 1.0
 */
add_filter( 'edd_paypal_js_sdk_data_attributes', function ( $data ) {
	if ( ! advanced_payments_enabled() ) {
		return $data;
	}

	try {
		$api      = new API();
		$response = $api->make_request( 'v1/identity/generate-token' );

		if ( ! empty( $response->client_token ) ) {
			$data['client-token'] = $response->client_token;
		}
	} catch ( \Exception $e ) {

	}

	return $data;
} );
