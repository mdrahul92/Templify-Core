<?php
/**
 * All Access Download Functions. This file contains all functions that relate to *actually actually downloading* a file using an All Access pass.
 *
 * @package     EDD All Access
 * @since       1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Used to deliver a download to a user without revealing the actual URL.
 *
 * @since    1.0.0
 * @return   void
 */
function edd_all_access_convert_site_to_download() {

	// If our "download" variable is set in the URL.
	if ( ! isset( $_GET['edd-all-access-download'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	// Get the download ID we will deliver.
	$download_to_deliver = is_numeric( $_GET['edd-all-access-download'] ) ? intval( $_GET['edd-all-access-download'] ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$price_id_to_deliver = isset( $_GET['edd-all-access-price-id'] ) && is_numeric( $_GET['edd-all-access-price-id'] ) ? intval( $_GET['edd-all-access-price-id'] ) : false; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$file_id_to_deliver  = isset( $_GET['edd-all-access-file-id'] ) && is_numeric( $_GET['edd-all-access-file-id'] ) ? intval( $_GET['edd-all-access-file-id'] ) : false; //phpcs:ignore WordPress.Security.NonceVerification.Recommended

	$args = array(
		'download_id'          => $download_to_deliver,
		'price_id'             => $price_id_to_deliver,
		'check_download_limit' => true, // Make sure to check for download limits here since we are actually attempting to download a file.
	);

	// Check if this user has an All Access priveleges for this product.
	$all_access_check = edd_all_access_check( $args );

	/**
	 * Filters the check response, which allows you to grant or revoke access to this download.
	 *
	 * @since 1.1.7
	 *
	 * @param array $all_access_check
	 * @param int   $download_to_deliver
	 * @param int   $price_id_to_deliver
	 * @param int   $file_id_to_deliver
	 */
	$all_access_check = apply_filters( 'edd_all_access_check_response_for_download', $all_access_check, $download_to_deliver, $price_id_to_deliver, $file_id_to_deliver );

	$all_access_pass  = $all_access_check['all_access_pass'];

	// If the user does NOT have All Access priveleges for this product.
	if ( ! $all_access_check['success'] ) {

		if ( 'all_access_pass_expired' === $all_access_check['failure_id'] ) {
			$redirect_url = edd_get_option( 'all_access_expired_redirect' );

			// If a redirect url has been set up for expired passes, redirect them there. This is useful for custom pages with a message on how to renew.
			if ( ! empty( $redirect_url ) ) {
				wp_safe_redirect( esc_url( $redirect_url ) );
				exit();
			}
		}

		if ( 'category_not_included' === $all_access_check['failure_id'] ) {
			$redirect_url = edd_get_option( 'all_access_category_not_included_redirect' );

			// If a redirect url has been set up for category-not-included failure, redirect them there.
			if ( ! empty( $redirect_url ) ) {
				wp_safe_redirect( esc_url( $redirect_url ) );
				exit();
			}
		}

		if ( 'price_id_not_included' === $all_access_check['failure_id'] ) {
			$redirect_url = edd_get_option( 'all_access_price_id_not_included_redirect' );

			// If a redirect url has been set up for category-not-included failure, redirect them there.
			if ( ! empty( $redirect_url ) ) {
				wp_safe_redirect( esc_url( $redirect_url ) );
				exit();
			}
		}

		if ( 'download_limit_reached' === $all_access_check['failure_id'] ) {

			$error_message = edd_get_option( 'all_access_download_limit_reached_text', __( 'Sorry. You\'ve hit the maximum number of downloads allowed for your All Access account.', 'edd-all-access' ) ) . ' (' . edd_all_access_download_limit_string( $all_access_pass ) . ')';

			$redirect_url = edd_get_option( 'all_access_download_limit_reached_redirect' );

			// If a redirect url has been set up for category-not-included failure, redirect them there.
			if ( ! empty( $redirect_url ) ) {

				wp_safe_redirect( esc_url( $redirect_url ) );
				exit();
			} else {

				// Download Limit Reached Error page.
				wp_die( esc_html( apply_filters( 'edd_all_access_download_limit_reached_message', $error_message ) ), esc_html( __( 'Error', 'edd-all-access' ) ), array( 'response' => 403 ) );

				exit();
			}
		}

		// Default error page if none others have prompted a separate action.
		wp_die( esc_html( apply_filters( 'edd_all_access_no_all_access_message', $all_access_check['failure_message'], $all_access_check['failure_id'] ) ), esc_html( __( 'Error', 'edd-all-access' ) ), array( 'response' => 403 ) );

		exit();
	}

	// Get the user ID.
	$user_id = get_current_user_id();

	// Get the customer.
	$customer = new EDD_Customer( $user_id, true );

	// If the currently logged-in customer is not the one attached to this All Access Pass.
	if ( $customer->id !== $all_access_pass->customer->id ) {

		wp_die( esc_html( apply_filters( 'edd_all_access_no_all_access_message', __( 'That All Access Pass does not belong to this account. Try logging in with the correct account information', 'edd-all-access' ) ), 'wrong_account' ), esc_html( __( 'Error', 'edd-all-access' ) ), array( 'response' => 403 ) );

		exit();
	}

	// If the download ID didn't pass sanitization.
	if ( ! $download_to_deliver ) {
		$error_message = __( 'Download ID sanitization failed.', 'edd-all-access' );

		wp_die( esc_html( apply_filters( 'edd_all_access_sanitization_fail_message', $error_message ) ), esc_html( __( 'Error', 'edd-all-access' ) ), array( 'response' => 403 ) );

		exit();
	}

	$hours = absint( edd_get_option( 'download_link_expiration', 24 ) );

	$date = strtotime( '+' . $hours . 'hours', current_time( 'timestamp' ) );
	if ( ! $date ) {
		$date = 2147472000; // Highest possible date, January 19, 2038.
	}

	// Get the array of deliverable files so we can get the array key for the file we want.
	$files = edd_get_download_files( $download_to_deliver, $price_id_to_deliver );

	$backwards_compat_for_files_attached_to_zero = false;

	// If there is no file ID to deliver, deliver the first one.
	if ( ! $file_id_to_deliver ) {
		foreach ( $files as $file_key => $file_url ) {
			$file_id_to_deliver = $file_key;

			// If the file is attached to zero as the key, this is old data.
			if ( 0 === $file_key ) {
				$backwards_compat_for_files_attached_to_zero = true;
			}

			break;
		}
	}

	// Don't deliver if there is no file uploaded to the product.
	if ( ! isset( $file_id_to_deliver ) || 0 === $price_id_to_deliver && ! $file_id_to_deliver && ! $backwards_compat_for_files_attached_to_zero ) {
		$error_message = __( 'Oops! There is no file attached to this product. Please contact the site administrator.', 'edd-all-access' ) . '<br />' . __( 'Download ID:', 'edd-all-access' ) . ' ' . $download_to_deliver;

		wp_die( esc_html( apply_filters( 'edd_all_access_no_file_key_message', $error_message ) ), esc_html( __( 'Error', 'edd-all-access' ) ), array( 'response' => 403 ) );

		exit();
	}

	// Don't deliver if there is no file attached to that Price ID.
	if ( ! isset( $file_id_to_deliver ) || 0 === $price_id_to_deliver && ! $file_id_to_deliver && ! $backwards_compat_for_files_attached_to_zero ) {
		$error_message = __( 'Oops! There is no file attached to that price. Please contact the site administrator.', 'edd-all-access' ) . '<br />' . __( 'Download ID:', 'edd-all-access' ) . ' ' . $download_to_deliver . ' | ' . __( 'Price ID:', 'edd-all-access' ) . ' ' . $price_id_to_deliver;

		wp_die( esc_html( apply_filters( 'edd_all_access_no_file_key_message', $error_message ) ), esc_html( __( 'Error', 'edd-all-access' ) ), array( 'response' => 403 ) );

		exit();
	}

	// Reset/Hotwire some of the variables used to process the download.
	$_GET['download_id']        = $download_to_deliver;
	$_GET['expire']             = $date;
	$_GET['file']               = $file_id_to_deliver;
	$_GET['price_id']           = $price_id_to_deliver;
	$_GET['key']                = null;
	$_GET['eddfile']            = sprintf( '%d:%d:%d:%d', $all_access_pass->payment_id, $download_to_deliver, $file_id_to_deliver, $price_id_to_deliver );
	$_GET['ttl']                = rawurlencode( $date );
	$_GET['all_access_pass_id'] = $all_access_pass->id;

	// Make sure the user will have access.
	add_filter( 'edd_file_download_has_access', '__return_true' );
	add_filter( 'edd_process_download_args', 'edd_all_access_add_payment_to_download_args' );
	add_filter( 'edd_url_token_allowed_params', 'edd_all_access_url_tokens_add_params' );

	// Note that we increment the count of downloads-used by the customer in the edd_process_download_headers hook.

	// Now that we've authenticated the All Access, process the download.
	edd_process_download();

}
add_action( 'init', 'edd_all_access_convert_site_to_download' );

/**
 * After a download is completed by a customer using an All Access pass, add some meta to the log letting it know which All Access download was used.
 *
 * @since    1.0.0
 * @param    string      $requested_file The file being downloaded.
 * @param    array       $download The files attached to the product in question.
 * @param    string      $email The email address of the user downloading the file.
 * @param    EDD_Payment $payment The EDD_Payment object being used to download the file.
 * @return   array $download_args The args being set in the edd_process_download function.
 */
function edd_all_access_add_download_id_to_file_log( $requested_file, $download, $email, $payment ) {

	// If no All Access Pass was used to download this file, get out of here now.
	if ( ! isset( $_GET['all_access_pass_id'] ) || empty( $_GET['all_access_pass_id'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return false;
	}

	// Lets set up the ALl Access Pass object using the id from the url.
	$aa_data         = explode( '_', sanitize_text_field( wp_unslash( $_GET['all_access_pass_id'] ) ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$payment_id      = intval( $aa_data[0] );
	$download_id     = intval( $aa_data[1] );
	$price_id        = intval( $aa_data[2] );
	$all_access_pass = edd_all_access_get_pass( $payment_id, $download_id, $price_id );

	// If this All Access Pass is not valid.
	if ( 'active' !== $all_access_pass->status ) {
		return false;
	}

	$current_user_id = get_current_user_id();

	// If this All Access Pass does not belong to the currently-logged-in customer.
	if ( absint( $all_access_pass->customer->user_id ) !== absint( $current_user_id ) ) {
		return false;
	}

	// EDD 3.0
	if ( function_exists( 'edd_get_file_download_logs' ) ) {
		$logs = edd_get_file_download_logs(
			array(
				'number'  => 1,
				'orderby' => 'id',
				'order'   => 'DESC',
			)
		);
	} else {
		global $edd_logs;
		$log_query = array(
			'log_type'       => 'file_download',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'orderby'        => 'ID',
			'order'          => 'DESC',
		);
		$logs      = $edd_logs->get_connected_logs( $log_query );
	}

	// Since the purchased All Access ID will be different than the ID of the downloaded post, we'll also add that All Access ID to the log.
	foreach ( $logs as $log ) {
		// EDD 3.0
		if ( function_exists( 'edd_update_file_download_log_meta' ) ) {
			$log_id = $log->id;
			edd_update_file_download_log_meta( $log_id, '_edd_log_all_access_pass_id', sanitize_text_field( $all_access_pass->id ) );
		} else {
			$log_id = $log;
			update_post_meta( $log_id, '_edd_log_all_access_pass_id', sanitize_text_field( $all_access_pass->id ) );
		}
	}

	// This filter allows downloads done using All Access to be counted, or to not be counted.
	$download_should_be_counted = apply_filters( 'edd_all_access_download_should_be_counted', true, $all_access_pass, $download, $requested_file, $email, $log_id );

	if ( $download_should_be_counted ) {

		// This action hook allows custom functions to be fired before a download is being counted.
		do_action( 'edd_all_access_download_being_counted_before', $all_access_pass, $download, $requested_file, $email, $log_id );

		// Increment the number of downloads-used by this customer in this time period.
		$all_access_pass->downloads_used = $all_access_pass->downloads_used + 1;

		// This action hook allows custom functions to be fired after a download is being counted.
		do_action( 'edd_all_access_download_being_counted_after', $all_access_pass, $download, $requested_file, $email, $log_id );
	}
}
add_action( 'edd_process_download_headers', 'edd_all_access_add_download_id_to_file_log', 10, 4 );

/**
 * When a customer is downloading a product, add the payment ID containing this All Access pass to the download args.
 * This is hooked to edd_process_download_args when a download using All Access is actually taking place.
 *
 * @since    1.0.0
 * @param    array $download_args The args being set in the edd_process_download function.
 * @return   array $download_args The args being set in the edd_process_download function.
 */
function edd_all_access_add_payment_to_download_args( $download_args ) {

	$edd_all_access_check = edd_all_access_check(
		array(
			'download_id' => $download_args['download'],
			'price_id'    => $download_args['price_id'],
		)
	);

	// If the all access check failed, dont add anything to the download log args.
	if ( ! $edd_all_access_check['success'] ) {
		return $download_args;
	}

	$download_args['payment'] = $edd_all_access_check['all_access_pass']->payment_id;

	$customer               = new EDD_Customer( get_current_user_id(), true );
	$download_args['email'] = $customer->email;

	return $download_args;
}

/**
 * Set the allowed token paramaters to include the edd-all-access-download URL paramater
 * This is hooked to edd_url_token_allowed_params when an All Access download is taking place.
 *
 * @since    1.0.0
 * @param    array $token_params The args being set in the edd_process_download function.
 * @return   array $token_params The args being set in the edd_process_download function.
 */
function edd_all_access_url_tokens_add_params( $token_params ) {
	$token_params[] = 'edd-all-access-download';
	$token_params[] = 'edd-all-access-price-id';
	$token_params[] = 'edd-all-access-file-id';
	return $token_params;
}

/**
 * Get the file download logs for an order ID, grouped by the product ID and file id.
 *
 * @since 1.2.2
 * @param EDD_All_Access_Pass $all_access_pass The All Access Pass to collect unique logs for.
 *
 * @return array The array of unique product/file_ids downloaded by this pass.
 */
function edd_all_access_get_unique_file_downloads( $all_access_pass ) {
	if ( version_compare( EDD_VERSION, '3.0', '<' ) ) {
		// This function is only available for use in EDD 3.0.
		return array();
	}

	global $wpdb;

	static $unique_file_downloads;

	if ( is_null( $unique_file_downloads ) ) {

		// When redownload is enabled, we have to actually check the logs to see how many different files they've downloaded.
		$logs_query = "
			SELECT * FROM {$wpdb->edd_logs_file_downloads} WHERE
			order_id = {$all_access_pass->payment->ID}
			GROUP BY product_id, file_id
		";

		$unique_file_downloads = $wpdb->get_results( $logs_query );
	}

	return $unique_file_downloads;
}

/**
 * Given a pass, download, and file_id, determine if this pass has downloaded this file before.
 *
 * @since 1.2.2
 * @param EDD_All_Access_Pass $all_access_pass  The pass to use for these checks.
 * @param int                 $download         The Download ID to check for logs against.
 * @param int                 $file_id          The File ID being downloaded.
 *
 * @return bool If this pass has downloaded this file before.
 */
function edd_all_access_pass_has_downloaded_file( $all_access_pass, $download, $file_id ) {
	$has_downloaded = false;

	if ( ! $all_access_pass instanceof EDD_All_Access_Pass || ( empty( $download ) || ! is_numeric( $file_id ) ) ) {
		return $has_downloaded;
	}

	$unique_logs = edd_all_access_get_unique_file_downloads( $all_access_pass, $download, $file_id );

	if ( ! empty( $unique_logs ) ) {
		foreach ( $unique_logs as $unique_log ) {
			$product_id = absint( $unique_log->product_id );
			$file_id    = absint( $unique_log->file_id );

			// At this point, the query string is the only indication what the AA File ID Is.
			$requested_file_id = isset( $_GET['edd-all-access-file-id'] ) ? absint( $_GET['edd-all-access-file-id'] ) : false;

			if ( absint( $download ) === $product_id && $requested_file_id === $file_id ) {
				// We identified that this product_id and file_id has been downloaded before.
				$has_downloaded = true;
				break;
			}
		}
	}

	return $has_downloaded;
}
