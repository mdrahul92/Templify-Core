<?php
/**
 * Functions
 *
 * @package   templify-paypal-pro
 * @copyright Copyright (c) 2021, Sandhills Development, LLC
 * @license   GPL2+
 * @since     1.0
 */

namespace EDD_PayPal_Commerce_Pro\Advanced;

use EDD\Gateways\PayPal\API;
use EDD\Gateways\PayPal\MerchantAccount;

/**
 * Whether or not advanced payments are enabled, and the site is eligible to display them.
 *
 *      - Option must be enabled in settings; and
 *      - SSL must be enabled on the site; and
 *      - Cart must not contain any Recurring products; and
 *      - PayPal account must be enabled/approved for PPCP_CUSTOM. ( @see is_merchant_account_ready() )
 *
 * @since 1.0
 * @return bool
 */
function advanced_payments_enabled() {
	if ( ! edd_get_option( 'paypal_advanced_card_payments' ) || ! is_ssl() ) {
		return false;
	}

	// Recurring payments not supported. @todo Confirm this.
	if ( function_exists( 'EDD_Recurring' ) && EDD_Recurring()->cart_contains_recurring() ) {
		return false;
	}

	return is_merchant_account_ready();
}

/**
 * Whether or not the merchant account is even eligible for ACDC.
 * This just checks whether or not the merchant was onboarded for PPCP, based on
 * country and currency eligibility. It does not check if their merchant account is
 * ready for payments ( @see is_merchant_account_ready() ); it just checks if they were
 * onboarded for PPCP or not.
 *
 * @since 1.0
 * @return bool
 */
function is_merchant_account_eligible_for_advanced_payments() {
	$mode            = edd_is_test_mode() ? API::MODE_SANDBOX : API::MODE_LIVE;
	$connect_details = json_decode( get_option( 'edd_paypal_commerce_connect_details_' . $mode ) );

	return empty( $connect_details->product ) || 'PPCP' === $connect_details->product;
}

/**
 * Determines whether or not the merchant account is ready to accept
 * Advanced Credit and Debit payments.
 *
 * @return bool
 */
function is_merchant_account_ready() {
	try {
		$details = MerchantAccount::retrieve();
		if ( ! $details->is_account_ready() ) {
			return false;
		}

		$ppc_product = array_filter( $details->products, function ( $product ) {
			return ! empty( $product['name'] ) && 'PPCP_CUSTOM' === strtoupper( $product['name'] );
		} );

		return ! empty( $ppc_product[0]['vetting_status'] ) && 'SUBSCRIBED' === strtoupper( $ppc_product[0]['vetting_status'] );
	} catch ( \Exception $e ) {
		return false;
	}
}
