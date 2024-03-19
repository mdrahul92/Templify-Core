<?php
/**
 * Functions to make discount codes integrate with Full Access
 *
 * @package     EDD Full Access
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since 1.0.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * EDD Discounts - add a multi-select chosen dropdown to the discount creation screen
 * which makes it possible to restrcit a dicount code to customs who have a specific, valid Full Access Pass.
 *
 * @since       1.0.2
 * @return      void
 */
function edd_all_access_discount_code_restrict_option_add() {

	?>
	<tr>
		<th scope="row" valign="top">
			<label for="edd-use-once"><?php esc_html_e( 'Required Full Access Passes', 'templify-full-access' ); ?></label>
		</th>
		<td>
			<?php
			$all_access_products = edd_all_access_get_all_access_downloads();

			$options = array(
				'all' => __( 'Any Full Access Pass', 'templify-full-access' ),
			);

			foreach ( $all_access_products as $all_access_product_id ) {
				$options[ $all_access_product_id ] = esc_html( get_the_title( $all_access_product_id ) );
			}

			echo EDD()->html->select(
				array(
					'options'          => $options,
					'name'             => 'edd_all_access_discount_restrict',
					'selected'         => '',
					'id'               => 'edd_all_access_discount_restrict',
					'class'            => 'edd_all_access_discount_restrict',
					'chosen'           => true,
					'placeholder'      => __( 'Type to search Full Access Products', 'templify-full-access' ),
					'multiple'         => true,
					'show_option_all'  => false,
					'show_option_none' => false,
					'data'             => array( 'search-type' => 'no_ajax' ),
				)
			);
			?>
			<span class="description"><?php esc_html_e( 'Only allow this discount to be used by customers with valid Full Access pass? Leave blank if none required.', 'templify-full-access' ); ?></span>
		</td>
	</tr>
	<?php

}
add_action( 'edd_add_discount_form_before_products', 'edd_all_access_discount_code_restrict_option_add' );

/**
 * EDD Discounts - add a multi-select chosen dropdown to the discount creation screen
 * which makes it possible to restrcit a dicount code to customs who have a specific, valid Full Access Pass.
 *
 * @since       1.0.2
 * @param       int $discount_id The ID of the discount being created/edited.
 * @param       obj $discount The discount object.
 * @return      void
 */
function edd_all_access_discount_code_restrict_option_edit( $discount_id, $discount ) {

	?>
	<tr>
		<th scope="row" valign="top">
			<label for="edd-use-once"><?php esc_html_e( 'Required Full Access Passes', 'templify-full-access' ); ?></label>
		</th>
		<td>
			<?php

			// Get all of the Full Access products in the store.
			$all_access_products = edd_all_access_get_all_access_downloads();

			$options = array(
				'all' => __( 'Any Full Access Pass', 'templify-full-access' ),
			);

			foreach ( $all_access_products as $all_access_product_id ) {
				$options[ $all_access_product_id ] = esc_html( get_the_title( $all_access_product_id ) );
			}

			// Get the Full Access product previously saved to this option.
			$previously_saved = edd_all_access_get_discount_meta( $discount_id );

			echo EDD()->html->select(
				array(
					'options'          => $options,
					'name'             => 'edd_all_access_discount_restrict[]',
					'selected'         => $previously_saved,
					'id'               => 'edd_all_access_discount_restrict',
					'class'            => 'edd_all_access_discount_restrict',
					'chosen'           => true,
					'placeholder'      => __( 'Type to search Full Access Products', 'templify-full-access' ),
					'multiple'         => true,
					'show_option_all'  => false,
					'show_option_none' => false,
					'data'             => array( 'search-type' => 'no_ajax' ),
				)
			);
			?>
			<span class="description"><?php esc_html_e( 'Only allow this discount to be used by customers with valid Full Access pass? Leave blank if none required.', 'templify-full-access' ); ?></span>
		</td>
	</tr>
	<?php

}
add_action( 'edd_edit_discount_form_before_products', 'edd_all_access_discount_code_restrict_option_edit', 10, 2 );

/**
 * EDD Discounts - Save the Full Access setting for discounts
 *
 * @since       1.0.2
 * @param       array $meta The default meat being saved.
 * @param       int   $discount_id The ID of the discount being saved.
 * @return      void
 */
function edd_all_access_save_discount_code_setting( $meta, $discount_id ) {

	$discount = new EDD_Discount( $discount_id );

	if ( ! isset( $_POST['edd-discount-nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['edd-discount-nonce'] ) ), 'edd_discount_nonce' ) ) {
		return;
	}

	// If the option for Full Access is not set, save it to be empty.
	if ( ! isset( $_POST['edd_all_access_discount_restrict'] ) ) {
		$discount->update_meta( 'edd_all_access_discount_restrict', '' );
		return;
	}

	// Declare the sanitized array.
	$sanitized = array();

	if ( is_array( $_POST['edd_all_access_discount_restrict'] ) ) {
		// Loop through each Full Access Product the site admin has chosen to restrict this discount code to.
		foreach ( $_POST['edd_all_access_discount_restrict'] as $all_access_product_id ) {
			//Sanitization of this array happens below.
			$sanitized[] = is_numeric( $all_access_product_id ) ? intval( $all_access_product_id ) : 'all';
		}
	} else {
		$sanitized[] = intval( $_POST['edd_all_access_discount_restrict'] );
	}

	$discount->update_meta( 'edd_all_access_discount_restrict', $sanitized );

}

add_action( 'edd_post_insert_discount', 'edd_all_access_save_discount_code_setting', 10, 2 );
add_action( 'edd_post_update_discount', 'edd_all_access_save_discount_code_setting', 10, 2 );

/**
 * EDD Discounts - Save the Full Access setting for discounts
 *
 * @since        1.0.2
 * @param bool   $is_valid      If the discount is valid or not.
 * @param int    $discount_id   Discount ID.
 * @param string $discount_code Discount code.
 * @param string $user          User info.
 */
function edd_all_access_discount_is_valid( $is_valid, $discount_id, $discount_code, $user ) {

	// Get the Full Access product required for this code to work.
	$required_aa_passes = edd_all_access_get_discount_meta( $discount_id );

	// If none have been set up, allow this discount code to be used.
	if ( empty( $required_aa_passes ) ) {
		return $is_valid;
	}

	$user_id = get_current_user_id();

	// If this customer is not logged in, this discount shouldn't be allowed because we don't know if they have any Full Access passes.
	if ( ! is_user_logged_in() || 0 === get_current_user_id() ) {
		edd_set_error( 'edd-discount-error', apply_filters( 'edd_all_access_not_logged_in_discount_error', __( 'You must be logged in and have a valid Full Access pass to use that discount code.', 'templify-full-access' ), $discount_id, $required_aa_passes ) );
		return false;
	}

	// Get the Customer.
	$customer = new EDD_Customer( $user_id, true );

	// Get the current customer's Full Access Passes from the customer meta.
	$customer_all_access_passes = edd_all_access_get_customer_pass_objects( $customer );

	// If they do not have any Full Access passes, they can't use this discount code.
	if ( empty( $customer_all_access_passes ) ) {
		edd_set_error( 'edd-discount-error', apply_filters( 'edd_all_access_no_passes_discount_error', __( 'You need a valid Full Access pass to use that discount code.', 'templify-full-access' ), $discount_id, $required_aa_passes ) );
		return false;
	}

	// Loop through each Full Access Pass this customer has purchased.
	foreach ( $customer_all_access_passes as $all_access_pass ) {

		// If this Full Access Pass is active, check if it is one of the required products.
		if ( 'active' !== $all_access_pass->status ) {
			continue;
		}

		// "Any AA Pass" could be saved as `0` or `all`, so either means the discount is valid.
		if ( array_intersect( array( 0, 'all' ), $required_aa_passes ) ) {
			return true;
		}

		foreach ( $required_aa_passes as $required_aa_pass ) {

			// The pass matches a download in the discount, so the discount is valid.
			if ( $required_aa_pass === $all_access_pass->download_id ) {
				return true;
			}
		}
	}

	// No valid pass was found.
	edd_set_error( 'edd-discount-error', apply_filters( 'edd_all_access_pass_required_discount_error', __( 'You must have a valid Full Access pass to use that discount code.', 'templify-full-access' ), $discount_id, $required_aa_passes ) );

	return false;
}
add_filter( 'edd_is_discount_valid', 'edd_all_access_discount_is_valid', 10, 4 );

/**
 * Helper function to get the Full Access discount metadata.
 *
 * @param int $discount_id
 * @return void
 */
function edd_all_access_get_discount_meta( $discount_id ) {
	if ( empty( $discount_id ) ) {
		return '';
	}

	if ( function_exists( 'edd_get_adjustment_meta' ) && metadata_exists( 'edd_adjustment', $discount_id, 'edd_all_access_discount_restrict' ) ) {
		return edd_get_adjustment_meta( $discount_id, 'edd_all_access_discount_restrict', true );
	}

	$meta = get_post_meta( $discount_id, '_edd_discount_edd_all_access_discount_restrict', true );

	// If we made it here in EDD 3.0, the metadata didn't migrate and we need to update it.
	if ( function_exists( 'edd_update_adjustment_meta' ) ) {
		edd_update_adjustment_meta( $discount_id, 'edd_all_access_discount_restrict', $meta );
	}

	return $meta;
}
