<?php
/**
 * Full Access Status Functions. This file contains all functions that *trigger* activating or deactivating an Full Access pass.
 *
 * @package     EDD Full Access
 * @since       1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check for any Full Access Payments whose time period have passed and need to be expired.
 *
 * @since       1.0.0
 * @return      void
 */
function edd_all_access_check_expired_periods() {

	// Get all Payments with Full Access products listed as active.
	$payments                                 = new EDD_Payments_Query( array( 'meta_key' => '_edd_aa_active_ids' ) );
	$payments_with_active_aa                  = $payments->get_payments();
	$payments_to_check_for_expired_all_access = $payments_with_active_aa;

	// Loop through each given payment so we can check each one's expiry date.
	foreach ( $payments_to_check_for_expired_all_access as $payment ) {

		if ( empty( $payment->downloads ) ) {
			continue;
		}

		// Loop through all downloads purchased in this payment.
		foreach ( $payment->downloads as $purchased_download ) {

			if ( isset( $purchased_download['options']['price_id'] ) ) {
				$price_id = $purchased_download['options']['price_id'];
			} else {
				$price_id = 0;
			}

			edd_all_access_get_pass( $payment->ID, $purchased_download['id'], $price_id );
		}
	}
}

/**
 * When an Full Access payment is freshly purchased, add it to the list of payments that need to be checked for expiration later.
 *
 * @since       1.0.0
 * @param       int         $payment_id The Payment ID being saved.
 * @param       EDD_Payment $payment    The Payment object being saved.
 * @todo deprecate when EDD minimum requirement is 3.0
 *
 * @return      void
 */
function edd_all_access_check_updated_payment( $payment_id, $payment ) {

	if ( function_exists( 'edd_get_order' ) ) {
		return;
	}

	// If we were not passed a payment.
	if ( ! $payment instanceof EDD_Payment && is_int( $payment ) ) {
		$payment = edd_get_payment( $payment );
	}

	// Return early if we don't have a valid EDD_Payment object.
	if ( empty( $payment ) ) {
		return;
	}

	if ( empty( $payment->downloads ) ) {
		return;
	}

	// Loop through all downloads purchased in this payment.
	foreach ( $payment->downloads as $purchased_download ) {

		$all_access_enabled = edd_all_access_enabled_for_download( $purchased_download['id'] );

		// If the product being purchased/saved has Full Access enabled, attempt to activate an Full Access Pass for it.
		if ( $all_access_enabled ) {
			$price_id = isset( $purchased_download['options']['price_id'] ) ? $purchased_download['options']['price_id'] : 0;
			edd_all_access_get_and_activate_pass( $payment->ID, $purchased_download['id'], $price_id );
		}
	}
}
add_action( 'edd_payment_saved', 'edd_all_access_check_updated_payment', 10, 2 );

/**
 * When an order item transitions status, attempt to activate the related AA pass.
 *
 * @since 1.2
 * @param string $old_status
 * @param string $new_status
 * @param int    $order_id
 * @return void
 */
function edd_all_access_maybe_activate_pass_on_order_item_transition( $old_status, $new_status, $order_id ) {

	if ( ! in_array( $new_status, edd_all_access_valid_order_statuses(), true ) ) {
		return;
	}

	$order = edd_get_order( $order_id );
	if ( ! $order instanceof EDD\Orders\Order ) {
		return;
	}

	foreach ( $order->get_items() as $order_item ) {
		$all_access_enabled = edd_all_access_enabled_for_download( $order_item->product_id );
		if ( $all_access_enabled ) {
			$activated = edd_all_access_get_and_activate_pass( $order_item->order_id, $order_item->product_id, $order_item->price_id );
		}
	}
}
add_action( 'edd_transition_order_status', 'edd_all_access_maybe_activate_pass_on_order_item_transition', 101, 3 );

/**
 * When trashing an order in EDD 3.0, update the customer metadata as well.
 *
 * @param string $old_status The previous order status.
 * @param string $new_status The new order status.
 * @param int    $order_id   The order ID.
 * @return void
 */
function edd_all_access_update_meta_on_trash( $old_status, $new_status, $order_id ) {
	if ( 'trash' !== $new_status ) {
		return;
	}
	edd_all_access_delete_pass_by_payment( $order_id );
}
add_action( 'edd_transition_order_status', 'edd_all_access_update_meta_on_trash', 10, 3 );

/**
 * When a payment status changes, check to see if any of it's purchased products should have Full Access enabled or disabled.
 *
 * Note: Priority is set to 101 to ensure it triggers after the `edd_complete_purchase()` function in EDD 3.0.
 * That's the function that sets the payment `date_completed`, which is used in the AAP activation process.
 *
 * @see edd_complete_purchase()
 *
 * @since  1.0.0
 * @param  int    $payment_id The ID of the payment being saved.
 * @param  string $new_status The new status of the payment.
 * @param  string $old_status The old status of the payment.
 * @return mixed
 */
function edd_all_access_check_updated_payment_on_status_change( $payment_id, $new_status, $old_status ) {
	// Full Access will neither activate or expire unless the status is valid.
	if ( function_exists( 'edd_get_order' ) || ! in_array( $new_status, edd_all_access_valid_order_statuses(), true ) ) {
		// Come back later when your status is valid (pending becomes complete sometimes after a Payment Gateway finalizes payments).
		return false;
	}

	$payment = edd_get_payment( $payment_id );
	edd_all_access_check_updated_payment( $payment_id, $payment );
}
add_action( 'edd_update_payment_status', 'edd_all_access_check_updated_payment_on_status_change', 101, 3 );

/**
 * Deletes the related Full Access Pass(es) when a payment is deleted.
 *
 * @since 1.1.5
 * @param int $payment_id The ID of the payment being deleted.
 * @return void
 */
function edd_all_access_delete_pass_by_payment( $payment_id ) {

	$customer_id = edd_get_payment_customer_id( $payment_id );
	if ( ! $customer_id ) {
		return;
	}

	$customer                   = new EDD_Customer( $customer_id );
	$customer_all_access_passes = edd_all_access_get_customer_passes( $customer );

	if ( empty( $customer_all_access_passes ) ) {
		return;
	}

	$passes_to_delete = array();

	// Get the list of ids in this payment that have previously been set to "active" (if any).
	$all_access_active_ids = edd_get_payment_meta( $payment_id, '_edd_aa_active_ids', true );

	// Get the list of ids in this payment that have previously been set to "expired" (if any).
	$all_access_expired_ids = edd_get_payment_meta( $payment_id, '_edd_aa_expired_ids', true );

	foreach ( $customer_all_access_passes as $pass => $data ) {
		// The customer meta keys differ from the payment meta keys so we have to update the string to compare.
		$dashed = str_replace( '_', '-', $pass );
		if ( is_array( $all_access_active_ids ) && array_key_exists( $dashed, $all_access_active_ids ) ) {
			$passes_to_delete[] = $pass;
		}
		if ( is_array( $all_access_expired_ids ) && array_key_exists( $dashed, $all_access_expired_ids ) ) {
			$passes_to_delete[] = $pass;
		}
	}
	if ( empty( $passes_to_delete ) ) {
		return;
	}
	foreach ( $passes_to_delete as $key ) {
		unset( $customer_all_access_passes[ $key ] );
	}

	if ( $customer_all_access_passes ) {
		$customer->update_meta( 'all_access_passes', $customer_all_access_passes );
	} else {
		$customer->delete_meta( 'all_access_passes' );
	}
}
add_action( 'edd_payment_delete', 'edd_all_access_delete_pass_by_payment' );

/**
 * Once a day, check for any Full Access Payments that may have expired.
 *
 * @since       1.0.0
 * @return      void
 */
function edd_all_access_check_expired_periods_via_cron() {

	edd_all_access_check_expired_periods();

}
add_action( 'edd_daily_scheduled_events', 'edd_all_access_check_expired_periods_via_cron' );

/**
 * If edd_all_access_force_check_expirations is in the URL, do a check for expired payments.
 *
 * @since  1.0.0
 * @return void
 */
function edd_all_access_check_expired_periods_via_url() {

	if ( ! isset( $_GET['edd_all_access_force_check_expirations'] ) ) {
		return;
	}

	edd_all_access_check_expired_periods();

}
add_action( 'admin_init', 'edd_all_access_check_expired_periods_via_url' );
