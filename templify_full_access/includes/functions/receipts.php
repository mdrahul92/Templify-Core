<?php
/**
 * Misc Functions
 *
 * @package     EDD\EDDAllAccess\Functions
 * @since       1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * If no files are attached, remove any related output about that for All Access Products in the receipt.
 *
 * @since       1.0.0
 * @param       bool   $show_download_files Whether to show the downloadable files area on the Purchase Confirmation page.
 * @param       int    $item_id  The ID of the product purchased.
 * @param       array  $receipt_args  Receipt args from the product purchased.
 * @param       object $item EDD Cart item object.
 * @return      bool $show_download_files Whether to show the downloadable files area on the Purchase Confirmation page.
 */
function edd_all_access_receipt_show_download_files( $show_download_files, $item_id, $receipt_args, $item ) {

	if ( ! edd_all_access_download_is_all_access( $item_id ) ) {
		return $show_download_files;
	}

	// If this is a bundle, show the files.
	if ( 'bundle' === edd_get_download_type( $item_id ) ) {
		return $show_download_files;
	}

	$download_files = edd_get_download_files( $item_id );

	// If no files are directly attached to this All Access enabled product,
	// Then don't show the files area - which contains the "No downloadable files" message for All Access products - they likely have access to ALL products.
	if ( empty( $download_files ) ) {
		return false;
	} else {
		return $show_download_files;
	}
}
add_filter( 'edd_receipt_show_download_files', 'edd_all_access_receipt_show_download_files', 11, 4 );

/**
 * For email receipts, remove the "No Downloads Found" message for All Access products.
 *
 * @since       1.0.0
 * @param       string $message  The message to be shown.
 * @param       int    $download_id  The id of the product in question.
 * @param       int    $price_id The id of the variable price in question.
 * @param       int    $payment_id The cart object from the payment used to purchase this.
 * @return      string
 */
function edd_all_access_remove_no_downloads_message( $message, $download_id, $price_id, $payment_id ) {

	// If this product is not an All Access Product, stop here.
	if ( ! edd_all_access_download_is_all_access( $download_id ) ) {
		return $message;
	}

	// All Access enabled products will almost always give access to SOMETHING so it makes sense to remove the "No download attached" message here.
	return '';

}
add_filter( 'edd_email_receipt_no_downloads_message', 'edd_all_access_remove_no_downloads_message', 10, 4 );

/**
 * Show a link in the purchase notes to start using All Access. This affects both receipts and purchase history pages.
 *
 * @since       1.0.0
 * @param       string $notes The notes for this product.
 * @param       int    $download_id  The ID of the product purchased.
 * @return      string
 */
function edd_all_access_add_receipt_link( $notes, $download_id ) {

	// If this product is not an All Access Product, stop here.
	if ( ! edd_all_access_download_is_all_access( $download_id ) ) {
		return $notes;
	}

	// If the current page is the edit product page, don't add the product note or it will be output into the box and saved upon update.
	if ( function_exists( 'get_current_screen' ) && isset( get_current_screen()->base ) && 'post' === get_current_screen()->base ) {
		return $notes;
	}

	$edd_slug = ! defined( 'EDD_SLUG' ) ? 'downloads' : EDD_SLUG;

	// Get the receipt options for this All Access enabled product.
	$receipt_meta                       = get_post_meta( $download_id, '_edd_all_access_receipt_settings', true );
	$show_all_access_link_in_receipt    = isset( $receipt_meta['show_link'] ) ? $receipt_meta['show_link'] : 'show_link';
	$all_access_link_in_receipt_url     = isset( $receipt_meta['link_url'] ) ? $receipt_meta['link_url'] : wp_login_url( home_url() . '/' . $edd_slug . '/' );
	$all_access_link_in_receipt_message = isset( $receipt_meta['link_message'] ) ? $receipt_meta['link_message'] : __( 'Click here to use your All Access Pass', 'edd-all-access' );

	// If this product is set to show the All Access Message/Link in the receipt, add it to the product notes - which are shown in the email receipt.
	if ( 'show_link' === $show_all_access_link_in_receipt ) {
		return $notes . '<a href="' . $all_access_link_in_receipt_url . '">' . $all_access_link_in_receipt_message . '</a>';
	} else {
		return $notes;
	}
}
add_filter( 'edd_product_notes', 'edd_all_access_add_receipt_link', 10, 2 );
