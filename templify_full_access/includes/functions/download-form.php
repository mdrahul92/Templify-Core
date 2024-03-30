<?php
/**
 * This file contains all functions the handle/modify the way that the EDD Download Form is displayed to a user with All Access.
 *
 * @package     EDD All Access
 * @since       1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Override the download form for users logged-in with an active All Access pass.
 * This is where the "Buy Now" button is turned into a "Download Now" button.
 *
 * @since       1.0.0
 * @param       string $purchase_form The existing download form HTML.
 * @param       array  $args Arguments passed to the form.
 * @return      string $form The updated download form
 */
function edd_all_access_download_form( $purchase_form, $args ) {

	global $post, $edd_displayed_form_ids;

	// Make sure the user is logged in before changing anything on the download form. For logged out users, nothing changes.
	if ( ! is_user_logged_in() ) {
		return $purchase_form;
	}

	$post_id = is_object( $post ) ? $post->ID : 0;

	/**
	 * Whether the form is being loaded via AJAX. Set to `false` by default.
	 *
	 * @link https://github.com/easydigitaldownloads/edd-all-access/issues/414
	 * @link https://github.com/easydigitaldownloads/edd-all-access/issues/425
	 */
	$is_lazy_load      = false;
	$download_id       = absint( $args['download_id'] );
	$doing_ajax        = defined( 'DOING_AJAX' ) && DOING_AJAX;
	$is_valid_price_id = isset( $args['price_id'] ) && is_numeric( $args['price_id'] );
	// false if not variable.
	$price_id = $doing_ajax && $is_valid_price_id
		? (int) $args['price_id']
		: edd_get_default_variable_price( $download_id );

	if ( $doing_ajax && ! $is_valid_price_id ) {
		$is_lazy_load = true;
	}

	// Check whether the site owner has chosen to hide non relevant price IDs.
	$hide_non_relevant_prices = edd_get_option( 'all_access_hide_non_relevant_variable_prices', 'no' );

	// This filter can be used by extensions to Ignore All Access for products that don't make sense - like products that aren't actually download-able (IE Bookings).
	$allow_all_access = apply_filters( 'edd_all_access_allow', true, $purchase_form, $args, $download_id, $price_id );

	if ( ! $allow_all_access ) {
		return $purchase_form;
	}

	$defaults = apply_filters(
		'edd_purchase_link_defaults',
		array(
			'download_id' => $post_id,
			'price'       => (bool) false,
			'price_id'    => isset( $args['price_id'] ) ? $args['price_id'] : false,
			'style'       => edd_get_option( 'button_style', 'button' ),
			'color'       => edd_get_option( 'checkout_color', 'blue' ),
			'class'       => 'edd-submit',
		)
	);

	$args = wp_parse_args( $args, $defaults );

	// Check if this download is an "All Access" product. If it is, return the normal purchase form - you can't download an All Access product - you can only buy them.
	$all_access_enabled = edd_all_access_enabled_for_download( $download_id );

	if ( $all_access_enabled ) {
		return $purchase_form;
	}

	$all_access_check_args = array(
		'download_id' => $download_id,
		'price_id'    => $price_id,
	);

	$all_access = edd_all_access_check( $all_access_check_args );

	$variable_prices = edd_get_variable_prices( $download_id );

	$variable_pricing_enabled = edd_has_variable_prices( $download_id );

	// If this user doesn't have access to the download+price_id in question.
	if ( ! $all_access['success'] ) {

		edd_debug_log( 'AA - edd_all_access_download_form() failure id: ' . $all_access['failure_id'] );
		edd_debug_log( 'AA - edd_all_access_download_form() failure message: ' . $all_access['failure_message'] );

		// If we are not hiding non-relevant purchase buttons.
		if ( ! $hide_non_relevant_prices ) {
			// Return the normal form so the customer can purchase this.
			return $purchase_form;
		}

		// We need to check if they have access to ANY of the variable prices in this product.
		$relevant_variable_prices = edd_all_access_get_relevant_prices( $download_id );

		// If the customer cannot access at least one of the variable prices with an All Access pass, return the normal purchase form as is.
		if ( empty( $relevant_variable_prices ) ) {
			// Return the normal form.
			return $purchase_form;
		} else {

			// If we got to here, we know that the customer does not have access to the current variable price id but DOES have access to at least one of the variable prices.

			// If we are doing ajax, it means the customer changed one of the price/file radio buttons
			// on the AA purchase form to an option they do not have All Access to.
			if ( $doing_ajax && ! $is_lazy_load ) {
				// Return the normal form.
				return $purchase_form;
			} else {
				// Reset the default price id chosen on the radio button to the first one that will be shown.
				add_filter( 'edd_variable_default_price_id', 'edd_all_access_modify_default_price_id', 10, 2 );

				// Since this is a non relevant price, set the price id on the download button to the first relevant one.
				$price_id = $relevant_variable_prices[0];

			}
		}
	}

	// Get all of the files attached to this product.
	$files_attached_to_product = edd_get_download_files( $download_id );

	// The product is part of the pass, but there are no files to downlaod, so don't display anything.
	if ( ! $files_attached_to_product ) {
		/**
		 * This message displays when a product has no files to download.
		 *
		 * @since 1.2.1
		 * @param $message The message to display.
		 */
		return apply_filters( 'edd_all_access_no_downloadable_files_message', __( 'No downloadable files included.', 'edd-all-access' ) );
	}

	// Count the number of files attached in total, regardless of which price they are attached to.
	$total_number_of_files = count( $files_attached_to_product );

	// If only 1 file is attached to this product and the product is variably priced, set the variable price ID being downloaded to the highest possible one. We'll assume it is the most valuable.
	if ( $variable_pricing_enabled && 1 === $total_number_of_files ) {

		$relevant_variable_prices = edd_all_access_get_relevant_prices( $download_id );
		$highest_price_id         = end( $relevant_variable_prices );

		$assume_highest_for_download_log = apply_filters( 'edd_all_access_assume_highest_price_for_download_log', true, $download_id );

		// Change the price ID to the highest relevant one. We'll assume it's the one that should be attached to the download log.
		if ( $assume_highest_for_download_log ) {
			$price_id = $highest_price_id;
		}
	}

	// We'll hook in a filter here which may hide variable prices which the customer does not have access to.
	add_filter( 'edd_purchase_variable_prices', 'edd_all_access_hide_non_relevant_prices', 10, 2 );

	// Figure out what the "Download Now" button should say.
	if ( isset( $args['all_access_download_now_text'] ) ) {
		$download_label = $args['all_access_download_now_text'];
	} else {
		$download_label = edd_get_option( 'all_access_download_now_text', __( 'Download Now', 'edd-all-access' ) );
	}

	$download_class = implode( ' ', array( $args['style'], $args['color'], trim( $args['class'] ), 'edd-all-access-btn button' ) );

	// Collect any form IDs we've displayed already so we can avoid duplicate IDs.
	if ( isset( $edd_displayed_form_ids[ $download_id ] ) ) {
		$edd_displayed_form_ids[ $download_id ]++;
	} else {
		$edd_displayed_form_ids[ $download_id ] = 1;
	}

	// Get the ID for the download form.
	$form_id = ! empty( $args['form_id'] ) ? $args['form_id'] : 'edd_purchase_' . $download_id;

	// If we've already generated a form ID for this download ID, append -#.
	if ( $edd_displayed_form_ids[ $download_id ] > 1 ) {
		$form_id .= '-' . $edd_displayed_form_ids[ $download_id ];
	}

	// Set the file id.
	if ( $doing_ajax && ! $is_lazy_load ) {
		$file_id = absint( $args['file_id'] );
	} else {
		// Reset the default File ID so it matches the first file attached to the price ID set at this point.
		$file_id = edd_all_access_get_first_file_id_for_price( $download_id, $price_id );
	}

	ob_start();

	?>

	<form id="<?php echo esc_attr( $form_id ); ?>" class="edd_download_purchase_form edd_all_access_download_form">

		<?php

		// We only need this additional output if there is more than 1 file attached to the product. Otherwise, there's only one file to download so we only need the download button itself.
		if ( 1 !== $total_number_of_files ) {

			// Check if we have a single file per price, or whether any prices have more than a single file.
			$single_file_per_price = edd_all_access_single_file_per_price( $download_id );

			// Edge Case workaround - Check if this site wants to hide additional files from the AA download form (even though they _could_ just remove them in wp-admin).
			$hide_file_specific_download_options = apply_filters( 'edd_all_access_download_form_hide_file_specific_download_options', false, $download_id );

			// If there is only one file attached per price, show the purchase form the way it normally appears (only the price ids show, not files).
			if ( $single_file_per_price || $hide_file_specific_download_options ) {
				add_filter( 'edd_price_options_classes', 'edd_all_access_single_file_per_price_css_class' );
			} else {
				// But if there are multiple file per price, we need to show a list of the files available to download beneath each variable/single price.
				$download_link_data = edd_all_access_download_links( $download_id, $args, $hide_non_relevant_prices );

				add_filter( 'wp_kses_allowed_html', 'edd_all_access_kses_for_download_form', 10, 2 );

				// Show the list of file available for download.
				echo wp_kses_post( $download_link_data['output'] );

				remove_filter( 'wp_kses_allowed_html', 'edd_all_access_kses_for_download_form', 10, 2 );

				// If this is the initial pageload, set the default file ID for the "Download Now" button.
				if ( ! $doing_ajax && ! $is_lazy_load ) {
					$file_id = $download_link_data['file_id_for_button'];
				}

				// Unhook the variable price output that is default for EDD.
				edd_all_access_remove_variable_pricing();
			}
		}

		do_action( 'edd_purchase_link_top', $download_id, $args );

		// Output the actual purchase button below the list of files available.
		?>
		<div class="edd_purchase_submit_wrapper">
			<?php
			$button_href = edd_all_access_product_download_url( $download_id, $price_id, $file_id );
			// Output the actual "Download Now" button.
			?>
			<a class="<?php echo esc_attr( $download_class ); ?>" href="<?php echo esc_url( $button_href ); ?>">
				<span><?php echo esc_html( $download_label ); ?></span>
			</a>
		</div>
	</form>

	<?php

	$form = ob_get_clean();

	// Remove the filters we added to this form.
	remove_filter( 'edd_variable_default_price_id', 'edd_all_access_modify_default_price_id', 10, 2 );
	remove_filter( 'edd_purchase_variable_prices', 'edd_all_access_hide_non_relevant_prices', 10, 2 );

	return $form;
}
add_filter( 'edd_purchase_download_form', 'edd_all_access_download_form', 11, 2 );

/**
 * Add the CSS class "edd_all_access_price_options" to the price option HTML if this product can be downloaded using an All Access pass by this user.
 *
 * @since    1.0.0
 * @param    array $price_options_classes The array of all class names that will be output on the edd-price-options div.
 * @param    int   $download_id The id of the product that is being output..
 * @return   array $price_options_classes The array of all class names that will be output on the edd-price-options div.
 */
function edd_all_access_price_options_classes( $price_options_classes, $download_id ) {

	$all_access = edd_all_access_check( array( 'download_id' => $download_id ) );

	if ( ! $all_access['success'] ) {
		return $price_options_classes;
	}

	// Add new, wanted classes.
	$price_options_classes[] = 'edd_all_access_price_options';

	return $price_options_classes;

}
add_filter( 'edd_price_options_classes', 'edd_all_access_price_options_classes', 10, 2 );

/**
 * If this product can be downloaded using All Access, override the multi price mode to be OFF.
 * Multi Price mode needs to be off for All Access because you can only download 1 file at a time.
 * It won't zip multiple price options together for a single download. This likely will never become supported because tokenization wouldn't be supported.
 *
 * @since       1.0.0
 * @param       string $is_multi_enabled This will be "on" if true and empty if false.
 * @param       int    $download_id the ID of the download in question.
 * @return      bool true if multi price mode should be enabled and false if not.
 */
function edd_all_access_override_multi_price_mode( $is_multi_enabled, $download_id ) {

	$all_access = edd_all_access_check( array( 'download_id' => $download_id ) );

	if ( ! $all_access['success'] ) {
		return $is_multi_enabled;
	}

	return false;

}
add_filter( 'edd_single_price_option_mode', 'edd_all_access_override_multi_price_mode', 10, 2 );

/**
 * When viewing a product with an All Access pass, if quantities are enabled, disable them here.
 * People don't need to download multiple copies of the same file at once.
 *
 * @since       1.0.0
 * @param       string $quantity_input The html for the quantities input field.
 * @param       int    $download_id The ID of the download in question.
 * @param       array  $args Arguements.
 * @return      string $quantity_input an empty string since we do not want the quantities field
 */
function edd_all_access_disable_quantities( $quantity_input, $download_id, $args ) {

	$all_access = edd_all_access_check( array( 'download_id' => $download_id ) );

	if ( ! $all_access['success'] ) {
		return $quantity_input;
	}

	return null;

}
add_filter( 'edd_purchase_form_quantity_input', 'edd_all_access_disable_quantities', 10, 3 );

/**
 * Hide the price amount when showing variable prices to All Access customers.
 * For example, this will hide the "$1.00" on variable prices.
 *
 * @since   1.0.3
 * @param   string $price_output   The HTML output of the variable price.
 * @param   int    $download_id    The ID of the download being viewed.
 * @param   int    $price_id       The key of this variable price in the array of variable prices for this product.
 * @param   array  $price          The array of data about this price.
 * @param   string $form_id        The HTML ID of the form containing these variable prices.
 * @param   string $item_prop      The HTML item prop attribute.
 * @return  string $price_output   The filtered/modified HTML output of the variable price.
 */
function edd_all_access_remove_price_amounts( $price_output, $download_id, $price_id, $price, $form_id, $item_prop ) {

	// Check if the customer has All Access to this price ID. If they do, don't show the price.
	$all_access_check_args = array(
		'download_id' => $download_id,
		'price_id'    => $price_id,
	);

	$all_access = edd_all_access_check( $all_access_check_args );

	// If this user does have access to this variable price.
	if ( $all_access['success'] ) {

		// Remove the price amount output.
		$price_output = '<span class="edd_price_option_name"' . $item_prop . '>' . esc_html( $price['name'] ) . '</span>';

		return $price_output;
	}

	// Otherwise, return the price output as-is.
	return $price_output;

}
add_filter( 'edd_price_option_output', 'edd_all_access_remove_price_amounts', 10, 6 );

/**
 * Should we hide non relevant variable prices from customers with All Access?
 * For example, this is useful if you want to hide "Small" and "Medium" price options from customers who have access to the "Large" version.
 *
 * @since       1.0.2
 * @param       array $variable_prices The array of variable prices that would be shown in the download form.
 * @param       array $download_id The ID of the product being viewed.
 * @return      array $variable_prices The modified array of variable prices that will be shown in the download form.
 */
function edd_all_access_hide_non_relevant_prices( $variable_prices, $download_id ) {

	$hide_non_relevant_prices = edd_get_option( 'all_access_hide_non_relevant_variable_prices', 'no' );

	// If we shouldn't hide the non-relevant variable prices, return them as-is.
	if ( 'no' === $hide_non_relevant_prices ) {
		return $variable_prices;
	}

	// Figure out which variable prices the customer has access to - and which ones they do not.
	foreach ( $variable_prices as $variable_price_id => $variable_price_info ) {

		$all_access_check_args = array(
			'download_id' => $download_id,
			'price_id'    => $variable_price_id,
		);

		$all_access = edd_all_access_check( $all_access_check_args );

		// If this user doesn't have access to this variable price, remove it from this list to show.
		if ( ! $all_access['success'] ) {
			unset( $variable_prices[ $variable_price_id ] );
		}
	}

	// If the customer only has access to a single variable price, this makes it so we don't show any variable price radio buttons
	// and only the "Download" button shows, which is more user friendly and cleaner.
	if ( 1 === count( $variable_prices ) ) {
		return array();
	}

	return $variable_prices;

}

/**
 * Modify the default price ID to be the first one that the customer has All Access to. This is in a scenario where the first variable price is not included in All Access.
 *
 * @since  1.0.3
 * @param  int $price_id    The default Price ID to select.
 * @param  int $download_id The ID of the download being viewed or in question.
 * @return int $price_id ID The default Price ID to select
 */
function edd_all_access_modify_default_price_id( $price_id, $download_id ) {

	// Get the variable prices.
	$variable_prices = edd_get_variable_prices( $download_id );

	// Loop through them.
	foreach ( $variable_prices as $variable_price_id => $variable_price_data ) {

		$all_access_check_args = array(
			'download_id' => $download_id,
			'price_id'    => $variable_price_id,
		);

		$all_access = edd_all_access_check( $all_access_check_args );

		// If the customer does have access to at least one of the variable prices, stop looping and set the default variable price to that one.
		if ( $all_access['success'] ) {
			return $variable_price_id;
		}
	}

	return $price_id;
}

/**
 * This function is a modified version of the "edd_purchase_variable_pricing" function in EDD Core.
 *
 * It outputs the files available to download beneath each variable price so that All Access members can pick which file they want.
 *
 * @since 1.0.9
 * @param  int   $download_id Download ID.
 * @param  array $args The arguments which control how the download links are displayed.
 * @param  bool  $hide_non_relevant_prices Whether to hide or show prices which the AA Pass does not cover.
 * @return array An array containing the output and the file id to put on the "Download Now" button.
 */
function edd_all_access_download_links( $download_id, $args, $hide_non_relevant_prices ) {
	global $edd_displayed_form_ids;

	// If we've already generated a form ID for this download ID, append -#.
	$form_id = '';
	if ( $edd_displayed_form_ids[ $download_id ] > 1 ) {
		$form_id .= '-' . $edd_displayed_form_ids[ $download_id ];
	}

	$variable_pricing         = edd_has_variable_prices( $download_id );
	$relevant_variable_prices = edd_all_access_get_relevant_prices( $download_id );

	// Filter the class names for the edd_price_options div.
	$css_classes_array = apply_filters(
		'edd_price_options_classes',
		array(
			'edd_price_options',
			'edd_single_mode',
			'edd_aa_multiple_files_per_price',
		),
		$download_id
	);

	// Sanitize those class names and form them into a string.
	$css_classes_string = implode( ' ', array_map( 'sanitize_html_class', $css_classes_array ) );

	if ( edd_item_in_cart( $download_id ) && ! edd_single_price_option_mode( $download_id ) ) {
		return;
	}

	$total_number_of_radio_buttons = 0;

	ob_start();
	?>

	<div class="<?php echo esc_attr( rtrim( $css_classes_string ) ); ?>">
		<ul>
		<?php

		// If variable pricing is enabled.
		if ( $variable_pricing ) {

			$variable_prices = edd_get_variable_prices( $download_id );

			$default_radio_button_already_selected = false;

			// Output each variable price's title and attached files.
			foreach ( $variable_prices as $key => $price ) {

				// If the AA settings are set to hide non-relevant prices, don't show this price if it is also not one the customer has access to.
				if ( 'yes' === $hide_non_relevant_prices && ! in_array( $key, $relevant_variable_prices, true ) ) {
					continue;
				}

				// Reset the selected output.
				$selected = '';

				// Check if the customer has access to this variable price.
				$all_access_check_args = array(
					'download_id' => $download_id,
					'price_id'    => $key,
				);

				$all_access_check = edd_all_access_check( $all_access_check_args );

				?>
				<li id="edd_price_option_<?php echo esc_attr( $download_id . '_' . sanitize_key( $price['name'] ) . $form_id ); ?>">
					<?php

					// Show the name of the price (small, medium, large etc).
					?>
					<span class="edd_price_option_name"><?php echo esc_html( $price['name'] ); ?></span>
					<?php
					// Check if the customer has access to this variable price specifically (they might not have access to all).
					$all_access_check = edd_all_access_check(
						array(
							'download_id' => $download_id,
							'price_id'    => $key,
						)
					);

					// If the customer does not have access to this variable price, add the price to the output so they know they need to purchase it.
					if ( ! $all_access_check['success'] ) {
						?>
						<span class="edd_price_option_sep">&nbsp;&ndash;&nbsp;</span><span class="edd_price_option_price"><?php echo esc_html( edd_currency_filter( edd_format_amount( $price['amount'] ) ) ); ?></span>
						<?php
					}

					$files_attached_to_price = edd_get_download_files( $download_id, $key );

					// Output the unordered list of files available with a radio button beside each file.
					?>
					<ul class="edd_aa_file_options">
						<?php

							// Output a radio button for each file attached to this price.
						foreach ( $files_attached_to_price as $file_id => $file_info ) {

							// If the customer has access to this variable price.
							if ( in_array( $key, $relevant_variable_prices, true ) ) {

									// If we haven't already set the default radio button.
								if ( ! $default_radio_button_already_selected ) {
									$selected                              = 'checked="checked"';
									$default_radio_button_already_selected = true;

									// Set the file ID we will put on the "Download Now" button by default.
									$file_id_for_button = $file_id;
								} else {
									$selected = '';
								}
							}
							?>
							<li><label for="<?php echo esc_attr( 'edd_aa_file_option_' . $download_id . '_' . $key . '_' . $file_id . $form_id ); ?>">
								<?php

								// If the customer has access to this variable price and its files, show those files so they can be downloaded.
								if ( $all_access_check['success'] ) {

									// Set the class name for the input to be "edd_aa_file_option".
									$class_name = 'edd_aa_file_option';
									$value      = $file_id;

								} else {

									// If the customer does not have access to the files, set the class name for the input to be "edd_price_option_1788".
									$class_name = 'edd_price_option_' . $download_id;
									$value      = $key;

								}

								$total_number_of_radio_buttons++;

								?>
								<input type="radio" name="edd_aa_options[file_id][]" id="<?php echo esc_attr( 'edd_aa_file_option_' . $download_id . '_' . $key . '_' . $file_id . $form_id ); ?>" class="<?php echo esc_attr( $class_name ); ?>"
								value="<?php echo esc_attr( $value ); ?>" <?php echo esc_html( $selected ); ?> />&nbsp;
								<span class="edd_aa_file_name"><?php echo esc_html( $file_info['name'] ); ?></span>
							</label></li>
							<?php
						}
						?>
					</ul>
				</li>
				<?php
			}
		} else {

			// Single Mode: Variable prices are not enabled, but we know that multiple files exist. Thus, we will now show the list of files.
			$files_attached_to_price = edd_get_download_files( $download_id );

			$file_id_for_button = false;

			// Output the unordered list of files available with a radio button beside each file.
			?>
			<ul class="edd_aa_file_options">
				<?php

				$selected = 'checked="checked"';

				// Output a radio button for each file attached to this price.
				foreach ( $files_attached_to_price as $file_id => $file_info ) {

					// Set the file ID we willput on the "Download Now" button by default.
					if ( false === $file_id_for_button ) {
						$file_id_for_button = $file_id;
					}

					?>
					<li>
						<label for="<?php echo esc_attr( 'edd_aa_file_option_' . $download_id . '_0_' . $file_id . $form_id ); ?>">
						<?php $total_number_of_radio_buttons++; ?>
						<input type="radio" name="edd_aa_options[file_id][]" id="<?php echo esc_attr( 'edd_aa_file_option_' . $download_id . '_0_' . $file_id . $form_id ); ?>" class="edd_aa_file_option" value="<?php echo esc_attr( $file_id ); ?>" <?php echo esc_html( $selected ); ?> />&nbsp;
						<span class="edd_aa_file_name"><?php echo esc_html( $file_info['name'] ); ?></span>
						</label>
					</li>
					<?php

					$selected = '';
				}
				?>
			</ul>
			<?php
		}
		?>
		</ul>
	</div><!--end .edd_price_options-->
	<?php

	$output = ob_get_clean();

	// If there is only a single radio button being output, kill all of the radio button output.
	if ( 1 === $total_number_of_radio_buttons ) {
		$output = '';
	}

	if ( ! empty( $output ) ) {
		edd_all_access_remove_variable_pricing();
	}

	return array(
		'file_id_for_button' => $file_id_for_button,
		'output'             => $output,
	);
}

/**
 * This function adds the css class "edd_aa_single_file_per_price" to the selector area in the purchase form.
 *
 * @since 1.0.9
 * @param  array $css_classes The classes already being added to the selector area in the purchase form.
 * @return array $css_classes
 */
function edd_all_access_single_file_per_price_css_class( $css_classes ) {

	// Make sure the multiple_files_per_price didn't somehow get set. If it did...
	if ( in_array( 'edd_aa_multiple_files_per_price', $css_classes, true ) ) {

		// Loop through each CSS class.
		foreach ( $css_classes as $key => $css_class ) {

			// If the class exists for the multiple files per price.
			if ( 'edd_aa_multiple_files_per_price' === $key ) {

				// Remove it.
				unset( $css_classes[ $key ] );
			}
		}
	}

	// Add the single_file_per_price CSS class.
	$css_classes[] = 'edd_aa_single_file_per_price';

	return $css_classes;
}

/**
 * This function removes the variable price options for a download
 * and then adds the action hook back for the next download in the loop.
 *
 * @since 1.2.4
 * @return void
 */
function edd_all_access_remove_variable_pricing() {
	remove_action( 'edd_purchase_link_top', 'edd_purchase_variable_pricing' );
	add_action( 'edd_purchase_link_end', function() {
		add_action( 'edd_purchase_link_top', 'edd_purchase_variable_pricing', 10, 2 );
	} );
}
