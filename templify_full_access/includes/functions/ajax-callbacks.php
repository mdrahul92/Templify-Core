<?php
/**
 * Ajax Callback Functions
 *
 * @package     EDD Full Access
 * @since       1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ajax callback for switching variable price IDs and putting the right download URL onto the "Download" button for Full Access.
 *
 * @since    1.0.0
 * @return   void
 */
function edd_all_access_update_download_url() {

	$download_id = ! empty( $_POST['edd_all_access_download_id'] ) ? absint( $_POST['edd_all_access_download_id'] ) : 0;
	$price_id    = isset( $_POST['edd_all_access_price_id'] ) ? absint( $_POST['edd_all_access_price_id'] ) : 0;
	$file_id     = isset( $_POST['edd_all_access_file_id'] ) && is_numeric( $_POST['edd_all_access_file_id'] ) ? absint( $_POST['edd_all_access_file_id'] ) : false;

	if ( false === $file_id ) {
		$files   = array_keys( edd_get_download_files( $download_id, $price_id ) );
		$file_id = reset( $files );
	}

	$return_array = array(
		'success'     => true,
		'button_html' => edd_get_purchase_link(
			array(
				'download_id' => $download_id,
				'price_id'    => $price_id,
				'file_id'     => $file_id,
			)
		),
	);

	echo wp_json_encode( $return_array );

	die();

}
add_action( 'wp_ajax_edd_all_access_update_download_url', 'edd_all_access_update_download_url' );
add_action( 'wp_ajax_nopriv_edd_all_access_update_download_url', 'edd_all_access_update_download_url' );

/**
 * Process Full Access Payments via ajax.
 *
 * There are 2 stages, or modes, which this function runs through.
 * The first stage is resetting all Full Access Customer meta to be blank.
 * The second stage is re-creating/activating/deactivating each payment's Full Access Passes.
 * The reason this is done is 2 stages, is because an Full Access pass might be upgraded, renewed, or it could be fresh.
 * By deleting the customer meta first, and then, in stage 2, re-creating all of the AAPs for that customer from the beginning of time,
 * each Full Access Pass will correctly use any prior passes to determine if it is a renewal or an upgrade, or if it is a fresh purchase.
 *
 * @since 1.0.0
 * @return void
 */
function edd_all_access_do_ajax_process() {

	if ( ! current_user_can( 'manage_shop_settings' ) ) {
		die( __( 'You do not have permission to perform this action.', 'edd-all-access' ) );
	}

	if ( ! isset( $_POST['form'] ) || ! isset( $_POST['step'] ) ) {
		die( __( 'Data Failure. Please try again.', 'edd-all-access' ) );
	}

	$form = $_POST['form'];

	if ( ! wp_verify_nonce( $form['nonce'], 'edd_all_access_ajax_process' ) ) {
		die( __( 'Nonce Failure. Please try again.', 'edd-all-access' ) );
	}

	$step = absint( $_POST['step'] );

	// If we are forcing regeneration and this is the first load, set the mode to "resetting_data" as that is the first mode/stage.
	if ( 'yes' === $form['force_regeneration'] && ( ! isset( $form['mode'] ) ) || ! $form['mode'] ) {
		$form['mode'] = 'resetting_data';
	} elseif ( 'no' === $form['force_regeneration'] ) {
		$form['mode'] = 'process_passes';
	}

	if ( 'resetting_data' === $form['mode'] ) {

		if ( isset( $form['customer_id'] ) ) {
			// Delete the AA meta for this customer.
			$customer = new EDD_Customer( absint( $form['customer_id'] ) );
			$customer->delete_meta( 'all_access_passes' );
		}
	}

	// Get all product/downloads which are Full Access-enabled.
	$all_access_products = edd_all_access_get_all_access_downloads();

	if ( class_exists( '\\EDD\\Database\\Queries\\Order' ) ) {
		/*
		 * For EDD 3.0+
		 *
		 * We're manually initializing the Order query instead of using `edd_get_orders()` in order to
		 * utilize `$order_query->found_items`
		 */

		$order_query = new \EDD\Database\Queries\Order();
		$args        = array(
			'type'          => 'sale',
			'offset'        => 10 * $step,
			'number'        => 10,
			'order'         => 'ASC',
			'fields'        => 'id',
			'no_found_rows' => false
		);

		// If this is a customer-specific query, add the customer as part of the query to narrow it down to only that customer's payments.
		if ( isset( $form['customer_id'] ) ) {
			$args['customer_id'] = intval( $form['customer_id'] );
		}

		$results              = $order_query->query( $args );
		$total_number_results = $order_query->found_items;
		$has_results          = ! empty( $results );

	} else {
		/*
		 * For EDD 2.9 and lower.
		 */

		// Get all payments in groups of 10 per step.
		$args = array(
			'post_type'      => 'edd_payment',
			'offset'         => 10 * $step, // Check 10 payments per step.
			'posts_per_page' => 10,
			'order'          => 'ASC',
		);

		// If this is a customer-specific query, add the customer as part of the query to narrow it down to only that customer's payments.
		if ( isset( $form['customer_id'] ) ) {
			$args['meta_query'][] = array(
				'key'   => '_edd_payment_customer_id',
				'value' => $form['customer_id'],
			);
		}

		// We would use EDD_Payments_Query but it doesn't give us the data we need like found_posts and also has some other bugs currently preventing proper usage:
		// For example: https://github.com/easydigitaldownloads/easy-digital-downloads/issues/5377.
		$query = new WP_Query( $args );

		$total_number_results = $query->found_posts;
		$has_results          = $query->have_posts();
		$results              = $query->posts;
	}

	$payments_completed_in_this_step = array();

	// Loop through the 10 payments we just queried.
	if ( $has_results ) {
		foreach ( $results as $order ) {
			/*
			 * $order could either be an order ID (EDD 3.0+), or a WP_Post object (EDD 2.9 and lower).
			 * We retrieve the EDD_Payment object in order to make all the inner logic the same between
			 * both versions.
			 */
			$payment_id = $order instanceof WP_Post ? $order->ID : $order;
			$payment    = edd_get_payment( $payment_id );

			$payments_completed_in_this_step[] = $payment->ID;

			// Check if we should delete the payment meta, which forces regeneration.
			if ( 'resetting_data' === $form['mode'] ) {

				// Delete the payment meta.
				$payment->delete_meta( '_edd_aa_active_ids' );
				$payment->delete_meta( '_edd_aa_expired_ids' );

			} elseif ( 'process_passes' === $form['mode'] ) {

				// Loop through all downloads purchased in this payment.
				foreach ( $payment->downloads as $purchased_download ) {

					if ( in_array( $purchased_download['id'], $all_access_products ) ) {

						$price_id = isset( $purchased_download['options']['price_id'] ) ? $purchased_download['options']['price_id'] : 0;

						// Set up the Full Access Pass object.
						$all_access_pass = edd_all_access_get_pass( $payment->ID, $purchased_download['id'], $price_id );

						// Run a check which fixes incorrect meta data due to issue 152 on GitHub.
						all_access_issue_152_check( $all_access_pass, $payment->ID, $purchased_download['id'], $price_id );

						// Attempt to activate the Full Access Pass.
						$all_access_pass->maybe_activate();

						// Attempt to expire the Full Access Pass.
						$all_access_pass->maybe_expire();

						// Run a check which fixes in correct start times due to issue 229 on Github (upgrades from non-aa to aa-enabled).
						all_access_issue_229_check( $all_access_pass, $payment, $purchased_download['id'], $price_id );

					}

					$payment->add_note( __( 'This payment was processed by the Full Access processing tool.', 'edd-all-access' ) );
				}
			}
		}

		// If we are forcing regeneration.
		if ( 'yes' === $form['force_regeneration'] ) {

			// There are double the number of steps if we are forcing regeneration.
			$total_steps                 = $total_number_results * 2;
			$if_less_than_ten_percentage = 50;

		} else {
			$total_steps                 = $total_number_results;
			$if_less_than_ten_percentage = 100;
		}

		if ( $total_steps <= 10 ) {
			$percentage = $if_less_than_ten_percentage;
		} else {
			$percentage = ( ( $step * 10 ) / $total_steps ) * 100;
		}

		// If we are on the "process_passes" stage/mode, add 50 to the percentage.
		if ( 'process_passes' === $form['mode'] && 'yes' === $form['force_regeneration'] ) {
			$percentage = $percentage + 50;
		}

		$step++;

		echo wp_json_encode(
			array(
				'step'                            => $step,
				'mode'                            => $form['mode'],
				'force_regeneration'              => $form['force_regeneration'],
				'percentage'                      => $percentage,
				'payments_completed_in_this_step' => $payments_completed_in_this_step,
			)
		);

		exit;

	} else {

		// If we are here, no more payments are left.

		// If the mode is "resetting_data", switch the mode to "process_passes" and start the process again.
		if ( 'resetting_data' === $form['mode'] ) {

			$total_steps = $total_number_results * 2;

			if ( $total_steps <= 10 ) {
				$percentage = 50;
			} else {
				$percentage = ( ( $step * 10 ) / $total_steps ) * 100;
			}

			$message = __( 'Full Access Customer meta reset. Beginning regeneration of passes.', 'edd-all-access' );

			echo wp_json_encode(
				array(
					'step'               => 0,
					'mode'               => 'process_passes',
					'force_regeneration' => $form['force_regeneration'],
					'message'            => $message,
					'percentage'         => $percentage,
				)
			);

		} elseif ( 'process_passes' === $form['mode'] ) {

			$message = __( 'Full Access passes successfully processed.', 'edd-all-access' );

			echo wp_json_encode(
				array(
					'success' => true,
					'message' => $message,
				)
			);

		}

		exit;

	}

}
add_action( 'wp_ajax_edd_all_access_do_ajax_process', 'edd_all_access_do_ajax_process' );

/**
 * Check if a being-updated Full Access would would be set to expire so we know whether to show the "Are you sure" popup.
 *
 * @since 1.0.0
 * @return void
 */
function edd_all_access_expiration_check() {

	$would_be_duration_number = isset( $_POST['duration_number'] ) ? intval( $_POST['duration_number'] ) : null;
	$would_be_duration_unit   = isset( $_POST['duration_unit'] ) ? sanitize_text_field( wp_unslash( $_POST['duration_unit'] ) ) : null;
	$all_access_pass_id       = isset( $_POST['all_access_pass_id'] ) ? sanitize_text_field( wp_unslash( $_POST['all_access_pass_id'] ) ) : null;

	// Lets set up the Full Access Pass object using the id from the url.
	$aa_data     = explode( '_', $all_access_pass_id );
	$payment_id  = intval( $aa_data[0] );
	$download_id = intval( $aa_data[1] );
	$price_id    = intval( $aa_data[2] );

	// If the required values are blank.
	if ( empty( $payment_id ) || empty( $download_id ) ) {
		$return_array = array(
			'error' => 'no_all_access_pass_provided',
		);

		echo wp_json_encode( $return_array );
		die();
	}

	// Set up an Full Access Pass using the passed-in values.
	$all_access_pass = edd_all_access_get_pass( $payment_id, $download_id, $price_id );

	// If this pass is not active, this check is irrelevant.
	if ( 'active' !== $all_access_pass->status ) {
		$return_array = array(
			'error' => 'invalid_all_access_pass_provided',
		);

		echo wp_json_encode( $return_array );
		die();
	}

	// Check if this pass is set to never expire.
	if ( 'never' === $would_be_duration_unit ) {
		$return_array = array(
			'end_time'     => 'never',
			'would_expire' => false,
		);

		echo wp_json_encode( $return_array );

		die();
	}

	$start_date                       = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : null;
	$would_be_end_time_wrong_timezone = strtotime( $start_date ) + strtotime( $would_be_duration_number . ' ' . $would_be_duration_unit, 0 );

	$would_be_end_time_correct_timezone = edd_all_access_wp_timestamp_to_utc_timestamp( $would_be_end_time_wrong_timezone );

	$would_be_end_time = apply_filters( 'edd_all_access_pass_would_be_end_time', $would_be_end_time_correct_timezone, $would_be_duration_number, $would_be_duration_unit, $all_access_pass );

	// Check if the would-be end time is "never" after running through the filter.
	if ( 'never' === $would_be_end_time ) {
		$return_array = array(
			'end_time'     => 'never',
			'would_expire' => false,
		);

		echo wp_json_encode( $return_array );

		die();
	}

	// If this would expire.
	if ( time() > $would_be_end_time ) {
		$would_expire = true;
	} else {
		$would_expire = false;
	}

	$return_array = array(
		'end_time'     => edd_all_access_visible_date( 'D, d M Y H:i:s', $would_be_end_time ),
		'would_expire' => $would_expire,
	);

	echo wp_json_encode( $return_array );

	die();
}
add_action( 'wp_ajax_edd_all_access_expiration_check', 'edd_all_access_expiration_check' );
