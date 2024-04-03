<?php
/**
 * Payment method filters for the main PayPal gateway
 *
 * @package   templify-paypal-pro
 * @copyright Copyright (c) 2021, Sandhills Development, LLC
 * @license   GPL2+
 * @since     1.0
 */

namespace EDD_PayPal_Commerce_Pro;

/**
 * Enables all funding sources, and then selectively disables funding sources based on the store settings.
 *
 * @since 1.0
 */
add_filter( 'edd_paypal_js_sdk_query_args', function ( $args ) {
	unset( $args['disable-funding'] );

	$disable_funding_sources = edd_get_option( 'paypal_disable_funding', array() );
	if ( ! empty( $disable_funding_sources ) ) {
		$sources                 = array_keys( $disable_funding_sources );
		$args['disable-funding'] = implode( ',', $sources );
	}

	return $args;
} );
