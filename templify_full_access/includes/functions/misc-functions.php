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
 * If a customer is looking at the purchase button for an Full Access Product that they have an active Full Access Pass for,
 * change the word on the button from "Purchase" to "Renew".
 *
 * @since       1.0.0
 * @param       array $args The values relating to displaying the Purchase Button.
 * @return      array $args The values relating to displaying the Purchase Button
 */
function edd_all_access_modify_renew_btn_text( $args ) {

	// First lets check if this is an Full Access Product.
	if ( ! edd_all_access_download_is_all_access( $args['download_id'], $args['price_id'] ) ) {

		return $args;
	}

	$customer = new EDD_Customer( get_current_user_id(), true );

	// Get the Full Access passes saved to this customer meta.
	$customer_all_access_passes = edd_all_access_get_customer_passes( $customer );

	// If this customer has no all access passes return the button as-is.
	if ( empty( $customer_all_access_passes ) ) {
		return $args;
	}

	$download         = new EDD_Download( $args['download_id'] );
	$variable_pricing = $download->has_variable_prices();
	$price_id         = 0;
	// Update the temporary price ID for variable products.
	if ( $variable_pricing ) {
		if ( isset( $args['price_id'] ) && is_numeric( $args['price_id'] ) ) {
			$price_id = $args['price_id'];
		} else {
			$price_id = edd_get_default_variable_price( $download->ID );
		}
	}

	// If this customer has not purchased this pass before, do nothing.
	if ( ! isset( $customer_all_access_passes[ $args['download_id'] . '_' . $price_id ] ) ) {
		return $args;
	}
	$pass_data       = $customer_all_access_passes[ $args['download_id'] . '_' . $price_id ];
	$all_access_pass = edd_all_access_get_pass( $pass_data['payment_id'], $pass_data['download_id'], $pass_data['price_id'] );
	if ( ! empty( $all_access_pass->status ) && 'disabled' === $all_access_pass->status ) {
		return $args;
	}

	// If this customer has purchased this Full Access Pass before, change the button text to "Renew" to provide better context.
	$renew_button_text = apply_filters( 'edd_all_access_renew_btn_text', __( 'Renew Now', 'edd-all-access' ), $args );
	$args['text']      = $renew_button_text;

	return $args;
}
add_filter( 'edd_purchase_link_args', 'edd_all_access_modify_renew_btn_text' );

/**
 * Prevent bad purchases of Full Access.
 * Make sure the user is logged in and has an account.
 * Also prevent an prior (prior-to-upgraded) Full Access Pass from being purchased while the upgrade is still active.
 * This prevents errors relating to upgrades since it would overwrite the is_prior_of value.
 *
 * @since       1.0.0
 * @param       array $user The user in question, as passed from the edd_checkout_user_error_checks filter.
 * @param       array $valid_data The data in question, as passed from the edd_checkout_user_error_checks filter.
 * @param       array $post The post in question, as passed from the edd_checkout_user_error_checks filter.
 * @return      void
 */
function edd_all_access_prevent_bad_purchases( $user, $valid_data, $post ) {

	$is_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;

	if ( $is_ajax ) {
		// Do not create or login the user during the ajax submission (check for errors only).
		return;
	}

	$cart_contents = edd_get_cart_contents();

	if ( ! is_array( $cart_contents ) ) {
		return;
	}

	// Check which mode the site is in for selling Full Access.
	$sell_mode = edd_get_option( 'all_access_purchase_form_display', 'normal-mode' );

	// Get all of the Full Access enabled products.
	$all_access_products = edd_all_access_get_all_access_downloads();
	$all_access_products = array_reverse( $all_access_products );

	// Check each item in the cart.
	foreach ( $cart_contents as $cart_key => $item ) {

		$download_id = $item['id'];
		$price_id    = isset( $item['options']['price_id'] ) ? (int) $item['options']['price_id'] : 0;

		// Check if this product is an Full Access product.
		$enabled = edd_all_access_enabled_for_download( $download_id );

		// If this is not an Full Access Product.
		if ( ! $enabled ) {

			// If the store is in the mode which only allows products to be downloaded using Full Access, and the product 'could' be downloaded through Full Access.
			if ( 'aa-only-mode' === $sell_mode ) {

				// Loop through all of the Full Access Products.
				foreach ( $all_access_products as $all_access_product_id ) {
					// Check if this Full Access Product would contain this download.
					$aa_includes_download_args = array(
						'all_access_product_info' => array(
							'download_id' => $all_access_product_id,
							'price_id'    => 0,
						),
						'desired_product_info'    => array(
							'download_id' => $download_id,
							'price_id'    => $price_id,
						),
					);

					$all_access_includes_download = edd_all_access_includes_download( $aa_includes_download_args );

					// Set the name of the forbidden product.
					$forbidden_product_title = get_the_title( $download_id );
					if ( 0 !== intval( $price_id ) ) {
						$variable_prices          = edd_get_variable_prices( $download_id );
						$forbidden_product_title .= ' - ' . $variable_prices[ $price_id ]['name'];
					}

					if ( $all_access_includes_download['success'] ) {

						// Remove the item from the cart.
						$cart_object = new EDD_Cart();
						$cart_object->remove( $cart_key );

						// Show an error why.
						edd_set_error( 'edd_all_access_purchase_not_possible', '"' . $forbidden_product_title . '": ' . __( 'You can not purchase this product directly. To get access, purchase', 'edd-all-access' ) . ' <a href="' . get_permalink( $all_access_product_id ) . '">"' . get_the_title( $all_access_product_id ) . '"</a>.' );
					}
				}
			} else {
				// This product is not covered by all access. Allow it to be purchased normally.
				continue;
			}

			return false;
		}

		// If this is not an Full Access Product, and no products are in the cart that are included in an Full Access pass.
		if ( ! $enabled ) {
			return false;
		}

		// If we got to here, this is an Full Access enabled product that is in the cart.
		$bypass_login_requirement = apply_filters( 'edd_all_access_bypass_login_requirement', false, $download_id, $price_id );

		// Check if the customer is not logged in.
		if ( ! is_user_logged_in() && ! $bypass_login_requirement ) {
			edd_set_error( 'edd_all_access_purchase_not_possible', __( 'Create an account or log in before purchasing', 'edd-all-access' ) . ' ' . get_the_title( $download_id ) . '.' );
		}

		// Check the quantity of the Full Access product. If it's more than 1, at this time, you can't buy more than 1 quantity of an Full Access Pass in a single purchase.
		// This is because you can only buy an access pass for yourself (your own account). You can't purchase for other accounts at this time.
		if ( isset( $item['quantity'] ) && $item['quantity'] > 1 ) {
			// Translators: The name of the product being purchased.
			edd_set_error( 'edd_all_access_quantity_error', sprintf( __( 'You can only purchase 1 "%s" per user account. If you wish to purchase for another user account, log into that account and purchase it there as well. To complete this purchase, reduce the quantity in your cart to 1.', 'edd-all-access' ), get_the_title( $download_id ) ) );
		}

		// Different than official "quantities", but if there is more than 1 of the same Full Access product in the cart, throw an error.
		foreach ( $cart_contents as $checker_cart_key => $checker_item ) {

			// Skip over this item of course - we only want to check if OTHER products in the cart match this one.
			if ( $checker_cart_key === $cart_key ) {
				continue;
			}

			// If this item is in the cart more than once.
			if ( $checker_item['id'] === $item['id'] ) {
				// Translators: The name of the product being purchased.
				edd_set_error( 'edd_all_access_quantity_error', sprintf( __( 'You can only purchase 1 "%s" per user account. If you wish to purchase for another user account, log into that account and purchase it there as well. To complete this purchase, reduce the quantity in your cart to 1.', 'edd-all-access' ), get_the_title( $download_id ) ) );
				break;
			}
		}

		$has_active_all_access_pass   = edd_all_access_user_has_pass( get_current_user_id(), $download_id, $price_id, 'active' );
		$has_upgraded_all_access_pass = edd_all_access_user_has_pass( get_current_user_id(), $download_id, $price_id, 'upgraded' );

		// Check if the customer already has an Full Access Pass for this product.
		if ( $has_active_all_access_pass ) {

			// Check if this Full Access Pass is set to never expire. If so, there's no need to purchase it again.
			if ( 'never' === $has_active_all_access_pass->expiration_time ) {
				$download = new EDD_Download( $download_id );
				// Translators: The name of the product being purchased.
				$already_own_message = apply_filters( 'edd_all_access_already_own_lifetime_message', sprintf( __( 'You already own %s and it is set to never expire.', 'edd-all-access' ), $download->get_name() ), $has_active_all_access_pass );
				edd_set_error( 'edd_all_access_purchase_not_possible', $already_own_message );
			}

			// If it actually will expire at some point, allow it to be renewed. No errors here!
			continue;
		}

		if ( $has_upgraded_all_access_pass instanceof EDD_All_Access_Pass ) {
			$all_access_pass = $has_upgraded_all_access_pass;

			// If the customer has an active & upgraded version of this pass already.
			if ( edd_all_access_user_has_upgrade_of_prior_pass( $all_access_pass ) ) {
				$download = new EDD_Download( $download_id );
				edd_set_error( 'edd_all_access_purchase_not_possible', sprintf( __( 'You already own the upgraded version of %s', 'edd-all-access' ), $download->get_name() ) );
			}
		}
	}
}
add_action( 'edd_checkout_user_error_checks', 'edd_all_access_prevent_bad_purchases', 10, 3 );

/**
 * Automatically remove deuplciate Full Access products from the cart
 *
 * This has been superceded by `edd_all_access_maybe_prevent_add_to_cart()` and may no
 * longer be needed. It's still in place for now as a fallback.
 * @see edd_all_access_maybe_prevent_add_to_cart()
 *
 * @since       1.0.0
 * @param       array $cart The array of items in the cart.
 * @return      array $cart The array of items in the cart
 */
function edd_all_access_auto_remove_duplicates( $cart ) {

	$all_access_products = edd_all_access_get_all_access_downloads();

	$in_cart_aa_products = array();

	// Loop through all the items in the cart.
	foreach ( $cart as $key => $item ) {
		$download = new EDD_Download( $item['id'] );

		// If the item is an Full Access product.
		if ( in_array( $download->ID, $all_access_products, true ) ) {

			$price_id = isset( $item['options']['price_id'] ) ? $item['options']['price_id'] : 0;

			// If this item is already in the cart.
			if ( in_array( $download->ID . '_' . $price_id, $in_cart_aa_products, true ) ) {

				// Remove it from the cart because it is a duplicate Full Access product.
				unset( $cart[ $key ] );

			} else {

				// Store it in the list of "currently in the cart" all access products.
				$in_cart_aa_products[] = $download->ID . '_' . $price_id;
			}
		}
	}

	return $cart;

}
add_filter( 'edd_cart_contents', 'edd_all_access_auto_remove_duplicates', 10, 1 );

/**
 * Prevents duplicate Full Access Passes from being added to the cart.
 * This is a replacement for `edd_all_access_auto_remove_duplicates()`, which de-dupes
 * after the fact.
 *
 * @since 1.1.6
 *
 * @param array $item
 *
 * @return array|false
 */
function edd_all_access_maybe_prevent_add_to_cart( $item ) {
	// Bail if there's no item ID, or this isn't an Full Access Product.
	if ( empty( $item['id'] ) || ! in_array( $item['id'], edd_all_access_get_all_access_downloads() ) ) {
		return $item;
	}
	$cart = edd_get_cart_contents();

	// Further checks aren't needed if the cart is already empty.
	if ( ! is_array( $cart ) || empty( $cart ) ) {
		return $item;
	}

	$price_id = isset( $item['options']['price_id'] ) ? $item['options']['price_id'] : false;

	foreach ( $cart as $item_in_cart ) {
		if ( empty( $item_in_cart['id'] ) ) {
			continue;
		}

		$this_price_id = isset( $item_in_cart['options']['price_id'] ) ? $item_in_cart['options']['price_id'] : false;

		/*
		 * If the new item being added matches an item already in the cart, return false.
		 * This will prevent the new item from being added.
		 */
		if ( $item['id'] == $item_in_cart['id'] && $price_id == $this_price_id ) {
			return false;
		}
	}

	return $item;
}
add_filter( 'edd_add_to_cart_item', 'edd_all_access_maybe_prevent_add_to_cart' );

/**
 * Modify the purchase form (Add To Cart) to append the Buy/Login form for Full Access.
 * The default here is to make no change to the normal purchase form but if set in the settings, the output will change.
 *
 * @since       1.0.0
 * @param       string $purchase_form The existing download form.
 * @param       array  $args Arguments passed to the form.
 * @return      string $form The updated download form
 */
function edd_all_access_modify_purchase_form( $purchase_form, $args ) {

	// Add a filter which allows us to bypass this for specific situations.
	$modify_purchase_form = apply_filters( 'edd_all_access_modify_purchase_form', true, $purchase_form, $args );

	// If we should not modify the purchase form in this situation, return the purchase form as is.
	if ( ! $modify_purchase_form ) {
		return $purchase_form;
	}

	// If we should only show the Full Access purchase/login form, find which AA pass could contain this product.
	$all_access_products = edd_all_access_get_all_access_downloads();
	$all_access_products = array_reverse( $all_access_products );

	// Set the download and price ids.
	$download_id = absint( $args['download_id'] );
	$price_id    = defined( 'DOING_AJAX' ) && DOING_AJAX ? absint( $args['price_id'] ) : edd_get_default_variable_price( $download_id );

	// If the product is an Full Access Product, check if it never expires. If so, output a message, otherwise, show the purchase button.
	if ( in_array( $download_id, $all_access_products, true ) ) {

		$maybe_all_access_pass = edd_all_access_user_has_pass( get_current_user_id(), $download_id, $price_id );

		if ( ! $maybe_all_access_pass ) {
			return $purchase_form;
		}

		// If the pass will never expire, there's no need for a purchase button.
		if ( 'never' === $maybe_all_access_pass->expiration_time ) {
			// Translators: The name of the product being viewed for purchase.
			$download = new EDD_Download( $download_id );
			return apply_filters( 'edd_all_access_already_own_lifetime_message', sprintf( __( 'You already own %s and it is set to never expire.', 'edd-all-access' ), $download->get_name() ), $maybe_all_access_pass );
		}
	}

	// Set default output for the purchase form.
	$form = '';

	// Don't run this if the customer has access to this product with their current Full Access Pass.
	$all_access_check_args = array(
		'download_id' => $download_id,
		'price_id'    => $price_id,
	);

	$all_access = edd_all_access_check( $all_access_check_args );

	// If the customer has access to this product through an Full Access Pass, return the purchase form as is.
	if ( $all_access['success'] ) {
		return $purchase_form;
	}

	// Check if this product being viewed has variable pricing.
	$has_variable_prices = edd_has_variable_prices( $download_id );

	// Check if any of the variable prices exclude Full Access.
	$variable_prices = edd_get_variable_prices( $download_id );

	// If this product has variable pricing and any of those variable prices are not covered by an Full Access Pass, return the normal purchase form.
	if ( $has_variable_prices ) {

		// Loop through each price id to check if it is excluded from Full Access.
		foreach ( $variable_prices as $variable_price_id => $variable_price_settings ) {
			// If this variable price is excluded from Full Access.
			if ( ! empty( $variable_price_settings['excluded_price'] ) ) {
					return $purchase_form;
			}
		}
	}

	// Get the mode the user has chosen for the Add To Cart purchase form display in the Full Access settings.
	$purchase_form_display_mode = edd_get_option( 'all_access_purchase_form_display', 'normal-mode' );

	// If the site is set to use the normal purchase mode.
	if ( 'normal-mode' === $purchase_form_display_mode ) {

		// Add the normal Buy Now form back.
		return $purchase_form;

	} elseif ( 'normal-plus-aa-mode' === $purchase_form_display_mode || 'aa-only-mode' === $purchase_form_display_mode ) {

		// Set default.
		$all_access_includes_download = false;
		$every_price_id_included      = true;
		$all_access_products_to_show  = array();

		// Loop through each Full Access Product.
		foreach ( $all_access_products as $all_access_product_id ) {

			// If this product has variable pricing and any of those variable prices are not covered by an Full Access Pass, return the normal purchase form.
			if ( $has_variable_prices ) {

				// Loop through each price id to check if it is excluded from this Full Access Product.
				foreach ( $variable_prices as $variable_price_id => $variable_price_settings ) {

					// Check if this Full Access Pass would contain this download/price_id.
					$aa_includes_download_args = array(
						'all_access_product_info' => array(
							'download_id' => $all_access_product_id,
							'price_id'    => 0,
						),
						'desired_product_info'    => array(
							'download_id' => $download_id,
							'price_id'    => $variable_price_id,
						),
					);

					$all_access_includes_download = edd_all_access_includes_download( $aa_includes_download_args );

					// If this Full Access Pass does not cover every price id in the product being viewed.
					if ( ! $all_access_includes_download['success'] ) {

						$every_price_id_included = false;
						// No need to check any more price ids.
						break;
					}
				}

				// If this Full Access pass failed to cover every price id.
				if ( ! $every_price_id_included ) {
					// Reset the value for the next Full Access Pass to check.
					$every_price_id_included = true;
					// And then skip this pass and start checking the next one.
					continue;
				} else {

					// If it would contain this download, we'll output a link to buy the Full Access Pass.
					if ( $all_access_includes_download['success'] ) {

						// Add this Full Access product to the list of Full Access Products we'll show links to.
						$all_access_products_to_show[] = $all_access_product_id;
					}
				}
			} else {

				// Single price mode (not variable).

				// Check if this Full Access Pass would contain this download.
				$aa_includes_download_args = array(
					'all_access_product_info' => array(
						'download_id' => $all_access_product_id,
						'price_id'    => 0,
					),
					'desired_product_info'    => array(
						'download_id' => $download_id,
						'price_id'    => $price_id,
					),
				);

				$all_access_includes_download = edd_all_access_includes_download( $aa_includes_download_args );

				// If it would contain this download, we'll output a link to buy the Full Access Pass.
				if ( $all_access_includes_download['success'] ) {

					// Add this Full Access product to the list of Full Access Products we'll show links to.
					$all_access_products_to_show[] = $all_access_product_id;
				}
			}
		}

		// If no Full Access Passes would contain this download, return the normal purchase form.
		if ( empty( $all_access_products_to_show ) ) {
			return $purchase_form;
		}

		// Override color if color == inherit.
		if ( isset( $args['color'] ) ) {
			$args['color'] = 'inherit' === $args['color'] ? '' : $args['color'];
		}

		// Check if we should show the buy instructions.
		$show_buy_instructions = edd_get_option( 'all_access_show_buy_instructions', 'show' );
		$buy_instructions      = edd_get_option( 'all_access_buy_instructions', __( 'To get access, purchase an Full Access Pass here.', 'edd-all-access' ) );

		// Check if we should show the login instructions.
		$show_login_instructions = edd_get_option( 'all_access_show_login_instructions', 'show' );
		$login_instructions      = edd_get_option( 'all_access_login_instructions', __( 'Already purchased?', 'edd-all-access' ) );

		$all_access_form_args = array(
			'all_access_download_id' => $all_access_products_to_show,
			'all_access_price_id'    => 0,
			'all_access_price'       => true,
			'all_access_style'       => $args['style'],
			'all_access_color'       => $args['color'],
			'all_access_class'       => $args['class'],
			'class'                  => 'edd-aa-login-purchase-' . $purchase_form_display_mode,
			'buy_instructions'       => 'show' === $show_buy_instructions ? $buy_instructions : '',
			'login_instructions'     => 'show' === $show_login_instructions ? $login_instructions : '',
		);

		// Return the Full Access Purchase/Login form only.
		$form = edd_all_access_buy_or_login_form( $all_access_form_args );

		// If we should show both the normal "Add To Cart" button and the Full Access purchase/login form.
		if ( 'normal-plus-aa-mode' === $purchase_form_display_mode ) {

			// Add the purchase back before the newly renovated aa-only style form.
			$form = $purchase_form . $form;
		}
	}

	// If, for any reason, the form is empty at this point, return the normal purchase form untouched.
	if ( empty( $form ) ) {
		return $purchase_form;
	}

	return $form;
}

/**
 * When modifying the purchase form, the priority matters a lot because EDD Free Downloads can make free products
 * downloadable, even if the AA settings should override it. If AA is set to "aa-only" mode, the priority
 * needs to be higher, so that it is used last, instead of Free Downloads.
 *
 * @since       1.1.0
 * @return      void
 */
function edd_all_access_set_priority_of_purchase_form_modifier() {

	// Check which mode the site is in for selling Full Access.
	$sell_mode = edd_get_option( 'all_access_purchase_form_display', 'normal-mode' );

	if ( 'aa-only-mode' === $sell_mode ) {
		$priority = 11;
	} else {
		$priority = 10;
	}

	// Hook edd_all_access_modify_purchase_form at the priority decided above.
	add_filter( 'edd_purchase_download_form', 'edd_all_access_modify_purchase_form', 10, 2 );
}
add_action( 'init', 'edd_all_access_set_priority_of_purchase_form_modifier' );

/**
 * Exclude Bundles From Full Access. Bundles aren't "real" products with actual downloadable files. Only products WITHIN bundles are.
 *
 * @since       1.0.0
 * @param       bool   $allowed Whether to allow Full Access to change the download form for this product or not.
 * @param       string $purchase_form The actual form output which is being filtered.
 * @param       array  $args The arguments passed to the edd_purchase_download_form filter.
 * @param       int    $download_id The ID of the product in question..
 * @param       int    $price_id The price ID of the product in question.
 * @return      bool $allowed Whether to allow Full Access to change the download form for this product or not.
 */
function edd_all_access_exclude_bundles( $allowed, $purchase_form, $args, $download_id, $price_id ) {

	$download = new EDD_Download( $download_id );
	$type     = $download->get_type();

	// If the product being viewed is a bundle, exclude it from Full Access.
	if ( 'bundle' === $type ) {
		return false;
	}

	return $allowed;

}
add_filter( 'edd_all_access_allow', 'edd_all_access_exclude_bundles', 10, 5 );

/**
 * Add an informational box for Full Access to the View Order Details screen.
 *
 * @since       1.0.0
 * @param       int $payment_id The ID of the Payment being viewed.
 * @return      void
 */
function edd_all_access_view_order_details_sidebar( $payment_id ) {

	$payment = edd_get_payment( $payment_id );

	$at_least_one_all_access_pass_purchased = false;

	?>
	<div id="edd-order-logs" class="postbox edd-order-logs">

		<h3 class="hndle">
			<span><?php esc_html_e( 'Full Access Passes', 'edd-all-access' ); ?></span>
		</h3>
		<div class="inside">
			<div class="edd-admin-box">
				<div class="edd-admin-box-inside">
					<ul class="edd-aa-view-order-details">
					<?php
					// Loop through each Full Access Product that was purchased in this payment and list links to view/manage them.
					foreach ( $payment->cart_details as $cart_key => $cart_item ) {

						$download_id = $cart_item['id'];

						// Check if this product is an Full Access product.
						$all_access_enabled = edd_all_access_enabled_for_download( $download_id );

						// If not an Full Access product, skip it.
						if ( ! $all_access_enabled ) {
							continue;
						}

						// Get the purchased price ID.
						$price_id = isset( $cart_item['item_number']['options']['price_id'] ) ? $cart_item['item_number']['options']['price_id'] : 0;

						$product_title = get_the_title( $download_id );

						if ( edd_has_variable_prices( $download_id ) && isset( $price_id ) ) {
							$product_title .= ' - ' . edd_get_price_option_name( $download_id, $price_id, $payment_id );
						}

						// Set up the Full Access Pass Object for this product.
						$all_access_pass = edd_all_access_get_pass( $payment->ID, $download_id, $price_id );

						$at_least_one_all_access_pass_purchased = true;
						?>
						<li class="edd-aa-single-pass-payment-sidebar">
							<p>
								<span class="label"><?php echo esc_html( $product_title ); ?></span>&nbsp;
								<?php
								// If this Full Access pass is invalid, don't show a link to view it.

								if ( 'pending' === $payment->status ) {
									?>
									<span><?php esc_html_e( '(Payment Pending)', 'edd-all-access' ); ?></span>
								<?php } elseif ( 'invalid' === $all_access_pass->status ) { ?>
									<span>(<?php echo esc_html( edd_all_access_get_status_label( $all_access_pass->status ) ); ?>)</span>
								<?php } else { ?>
									<span><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=download&page=edd-all-access-pass&payment_id=' . $payment->ID . '&download_id=' . $download_id . '&price_id=' . $price_id ) ); ?>"><?php esc_html_e( '(View Details)', 'edd-all-access' ); ?></a></span>
								<?php } ?>
							</p>
						</li>
						<?php
					}

					// If no Full Access Products were purchased, show "none".
					if ( ! $at_least_one_all_access_pass_purchased ) {
						?>
						<li class="edd-aa-single-pass-payment-sidebar">
							<?php echo esc_html( __( 'None in this payment', 'edd-all-access' ) ); ?>
						</li>
					<?php } ?>
					</ul>
				</div>
			</div><!-- /.column-container -->

		</div><!-- /.inside -->

	</div><!-- /#edd-order-logs -->
	<?php
}
add_action( 'edd_view_order_details_sidebar_after', 'edd_all_access_view_order_details_sidebar' );

/**
 * Because of a bug, when using the retroactive Full Access Passes tool we'll do a check to make sure
 * all data that should exist does exist prior to attempting an activation.
 * Otherwise, it's possible that the activation gets falsely triggered as a renewal. See issue #152 on GitHub for more.
 *
 * @since       1.0.7
 * @param       EDD_All_Access_Pass $all_access_pass The Full Access Pass in question.
 * @param       int                 $payment_id The ID of the payment in question.
 * @param       int                 $download_id The ID of the download/product in question.
 * @param       int                 $price_id The ID of the variable price in question.
 * @return      void
 */
function all_access_issue_152_check( $all_access_pass, $payment_id, $download_id, $price_id = 0 ) {

	$price_id = empty( $price_id ) ? 0 : $price_id;

	$payment  = edd_get_payment( $payment_id );
	$customer = new EDD_Customer( $payment->customer_id );

	// Get customer meta.
	$customer_all_access_passes = edd_all_access_get_customer_passes( $customer );

	// Get payment meta.
	$all_access_active_ids     = $payment->get_meta( '_edd_aa_active_ids', true );
	$all_access_expired_ids    = $payment->get_meta( '_edd_aa_expired_ids', true );
	$purchased_aa_download_key = $download_id . '-' . $price_id;

	// Lets assume the pass was activated correctly and then double check.
	$correctly_activated = true;

	// If this Full Access pass does not exists in the customer meta array.
	if ( ! isset( $customer_all_access_passes[ $download_id . '_' . $price_id ] ) ) {

		// And it's also not set in the payment meta, this Full Access pass was not affected by the issue.
		if ( ! isset( $all_access_active_ids[ $purchased_aa_download_key ] ) && ! isset( $all_access_expired_ids[ $purchased_aa_download_key ] ) ) {
			return;
		}
	}

	// If this Full Access pass exists in either the active or expired payment meta.
	if ( isset( $all_access_active_ids[ $purchased_aa_download_key ] ) || isset( $all_access_expired_ids[ $purchased_aa_download_key ] ) ) {

		// But it's NOT in the customer meta, something is broken with it.
		if ( ! isset( $customer_all_access_passes[ $download_id . '_' . $price_id ] ) ) {
			$correctly_activated = false;
		}
	}

	if ( isset( $customer_all_access_passes[ $download_id . '_' . $price_id ] ) ) {
		$this_pass_meta = $customer_all_access_passes[ $download_id . '_' . $price_id ];

		// Double check that it contains all data it is expected to have.
		if ( ! isset( $this_pass_meta['download_id'] ) ) {
			$correctly_activated = false;
		}
		if ( ! isset( $this_pass_meta['price_id'] ) ) {
			$correctly_activated = false;
		}
		if ( ! isset( $this_pass_meta['payment_id'] ) ) {
			$correctly_activated = false;
		}
	}

	// If this pass was not properly activated, it needs to be deleted so that we can properly re-activate it below.
	if ( ! $correctly_activated ) {

		// Remove the customer meta for this pass.
		if ( isset( $customer_all_access_passes[ $download_id . '_' . $price_id ] ) ) {
			unset( $customer_all_access_passes[ $download_id . '_' . $price_id ] );
		}

		// Save the updated data.
		$customer->update_meta( 'all_access_passes', $customer_all_access_passes );

		// Remove the id of this download from the payment's meta.
		$all_access_active_ids     = $payment->get_meta( '_edd_aa_active_ids', true );
		$all_access_expired_ids    = $payment->get_meta( '_edd_aa_expired_ids', true );
		$purchased_aa_download_key = $download_id . '-' . $price_id;

		if ( isset( $all_access_active_ids[ $purchased_aa_download_key ] ) ) {
			unset( $all_access_active_ids[ $purchased_aa_download_key ] );
			$payment->update_meta( '_edd_aa_active_ids', $all_access_active_ids );
		}

		if ( isset( $all_access_expired_ids[ $purchased_aa_download_key ] ) ) {
			unset( $all_access_expired_ids[ $purchased_aa_download_key ] );
			$payment->update_meta( '_edd_aa_expired_ids', $all_access_expired_ids );
		}
	}

}

/**
 * Marks a function as deprecated and informs when it has been used.
 *
 * There is a hook edd_all_access_deprecated_function_run that will be called that can be used
 * to get the backtrace up to what file and function called the deprecated
 * function.
 *
 * The current behavior is to trigger a user error if WP_DEBUG is true.
 *
 * This function is to be used in every function that is deprecated.
 *
 * @uses do_action() Calls 'edd_all_access_deprecated_function_run' and passes the function name, what to use instead,
 *   and the version the function was deprecated in.
 * @uses apply_filters() Calls 'edd_deprecated_function_trigger_error' and expects boolean value of true to do
 *   trigger or false to not trigger error.
 *
 * @param string $function    The function that was called.
 * @param string $version     The version of Easy Digital Downloads that deprecated the function.
 * @param string $replacement Optional. The function that should have been called.
 * @param array  $backtrace   Optional. Contains stack backtrace of deprecated function.
 */
function _edd_all_access_deprecated_function( $function, $version, $replacement = null, $backtrace = null ) {
	do_action( 'edd_all_access_deprecated_function_run', $function, $replacement, $version );
	$show_errors = current_user_can( 'manage_options' );
	// Allow plugin to filter the output error trigger.
	if ( WP_DEBUG && apply_filters( 'edd_deprecated_function_trigger_error', $show_errors ) ) {
		if ( ! is_null( $replacement ) ) {
			trigger_error( sprintf( __( '%1$s is <strong>deprecated</strong> since Full Access version %2$s! Use %3$s instead.', 'edd-all-access' ), $function, $version, $replacement ) );
			trigger_error( print_r( $backtrace, 1 ) ); // Limited to previous 1028 characters, but since we only need to move back 1 in stack that should be fine.
			// Alternatively we could dump this to a file.
		} else {
			trigger_error( sprintf( __( '%1$s is <strong>deprecated</strong> since Full Access version %2$s with no alternative available.', 'edd-all-access' ), $function, $version ) );
			trigger_error( print_r( $backtrace, 1 ) );// Limited to previous 1028 characters, but since we only need to move back 1 in stack that should be fine.
			// Alternatively we could dump this to a file.
		}
	}
}

/**
 * If the payment used to create this Full Access Pass was an upgrade payment through Software Licensing,
 * check to see if the upgraded-from payment was Full Access enabled. If not, we need to manually adjust the
 * start time of this AA pass to match the start time of the upgraded-from payment.
 * Note that only non-AA to AA upgrades are affected here, as AA to AA upgrades are handled correctly
 *
 * @since  1.1
 * @param  EDD_All_Access_Pass $all_access_pass The Full Access Pass in question.
 * @param  EDD_Payment         $payment The EDD Payment in question.
 * @param  int                 $download_id The id of the product in question.
 * @param  int                 $price_id The id of the variable price in question.
 * @return array
 */
function all_access_issue_229_check( $all_access_pass, $payment, $download_id, $price_id ) {

	// Get the upgraded-from payment id.
	$upgraded_from_payment_id = edd_get_payment_meta( $payment->ID, '_edd_sl_upgraded_payment_id', true );

	if ( ! $upgraded_from_payment_id ) {
		return array(
			'success' => true,
			'code'    => 'no_upgrade_handling_required',
		);
	}

	$upgraded_from_payment = edd_get_payment( $upgraded_from_payment_id );

	// Get the UTC timestamp of the original upgraded-from payment.
	$old_start_time = edd_all_access_get_payment_utc_timestamp( $upgraded_from_payment );

	// Set the start time of this upgraded-to Full Access Pass to match the start time of the upgraded-from payment.
	$all_access_pass->start_time = $old_start_time;

	if ( $all_access_pass->start_time === $old_start_time ) {

		return array(
			'success' => true,
			'code'    => 'upgrade_successfully_handled',
		);

	} else {

		return array(
			'success' => false,
			'code'    => 'upgrade_not_successfully_handled',
		);

	}
}

/**
 * This function makes it easy to allow the CSS property "display" in wp_kses.
 *
 * @since  1.1.2
 * @param  array $styles The allowed styles in wp_kses.
 * @return array
 */
function edd_all_access_allow_css_display_in_wp_kses( $styles ) {
	$styles[] = 'display';
	return $styles;
}

/**
 * This function makes it easy show specific tags required for the Full Access download form.
 *
 * @since  1.1.2
 * @param  array $styles The allowed styles in wp_kses.
 * @return array
 */
function edd_all_access_kses_for_download_form( $allowedposttags, $context ) {
	$allowedposttags['input'] = array(
		'type'     => 'radio',
		'name'     => array(),
		'id'       => array(),
		'class'    => array(),
		'value'    => array(),
		'selected' => array(),
		'checked'  => array(),
	);
	return $allowedposttags;
}

/**
 * Modifies the download type to be "all_access" if the download is enabled for Full Access.
 *
 * @since 1.2.5
 * @param string $type
 * @param int    $download_id
 * @return string
 */
function edd_all_access_update_download_type( $type, $download_id ) {
	if ( 'all_access' === $type ) {
		return $type;
	}

	// If the download doesn't yet have a type, but does have AA settings, it's probably an AA download.
	if ( ( empty( $type ) || 'default' === $type ) && get_post_meta( $download_id, '_edd_all_access_settings', true ) ) {
		// This request will trigger a debugging notice and update the post meta.
		if ( get_post_meta( $download_id, '_edd_all_access_enabled', true ) ) {
			update_post_meta( $download_id, '_edd_product_type', 'all_access' );
			delete_post_meta( $download_id, '_edd_all_access_enabled' );

			return 'all_access';
		}
	}

	return $type;
}
add_filter( 'edd_get_download_type', 'edd_all_access_update_download_type', 20, 2 );

/**
 * Adds a debug log entry when the _edd_all_access_enabled meta key is requested directly.
 *
 * @since 1.2.5
 * @param null  $value
 * @param int   $object_id
 * @param string $meta_key
 * @param bool  $single
 * @return null
 */
function edd_all_access_get_post_metadata( $value, $object_id, $meta_key, $single ) {
	if ( '_edd_all_access_enabled' === $meta_key ) {
		edd_debug_log( sprintf( 'The _edd_all_access_enabled meta key was requested for download ID %d, but the edd_all_access_enabled_for_download function should be used instead.', $object_id ) );
	}

	return $value;
}
add_filter( 'get_post_metadata', 'edd_all_access_get_post_metadata', 10, 4 );
