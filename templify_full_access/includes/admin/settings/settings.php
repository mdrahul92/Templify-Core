<?php
/**
 * Admin Settings.
 *
 * @package     EDD All Access
 * @since       1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add plugin section in extension settings
 *
 * @since  1.0.0
 * @param  array $sections The array of extension menu sections for edd.
 * @return array $sections The modified array of extension menu sections for edd
 */
function edd_all_access_settings_menu( $sections ) {
	$sections['all-access'] = __( 'All Access', 'edd-all-access' );
	return $sections;
}
add_filter( 'edd_settings_sections_extensions', 'edd_all_access_settings_menu' );

/**
 * Add settings
 *
 * @since  1.0.0
 * @param  array $settings The existing EDD settings array.
 * @return array The modified EDD settings array.
 */
function edd_all_access_settings( $settings ) {

	$all_access_settings = apply_filters( 'edd_all_access_settings', array() );

	if ( version_compare( EDD_VERSION, 2.5, '>=' ) ) {
		$all_access_settings = array( 'all-access' => $all_access_settings );
	}

	$settings = array_merge( $settings, $all_access_settings );

	return $settings;
}
add_filter( 'edd_settings_extensions', 'edd_all_access_settings' );

/**
 * Add actual site-wide settings for EDD ALl Access.
 *
 * @since  1.0.0
 * @param  array $settings The existing EDD settings array.
 * @return array The modified EDD settings array
 */
function edd_all_access_site_wide_settings( $settings ) {

	$settings[] = array(
		'id'   => 'all_access_settings_header',
		'name' => '<strong>' . __( 'All Access Settings:', 'edd-all-access' ) . '</strong>',
		'desc' => __( 'Configure All Access Settings', 'edd-all-access' ),
		'type' => 'header',
		'size' => 'regular',
	);

	$settings[] = array(
		'id'   => 'all_access_download_now_text',
		'name' => __( '"Download Now" button text.', 'edd-all-access' ),
		'desc' => __( 'What text should be on the "Download Now" buttons?', 'edd-all-access' ),
		'type' => 'text',
		'size' => 'medium',
		'std'  => __( 'Download Now', 'edd-all-access' ),
	);

	if ( version_compare( EDD_VERSION, '3.0', '<' ) ) {
		$settings[] = array(
			'id'       => 'all_access_allow_redownload',
			'name'     => __( 'Allow redownloading', 'edd-all-access' ),
			'desc'     => __( 'Allow pass holders to redownload the same file without it counting towards their download limit. Requires Easy Digital Downloads 3.0 or later.', 'edd-all-access' ),
			'type'     => 'descriptive_text',
		);
	} else {
		$settings[] = array(
			'id'   => 'all_access_allow_redownload',
			'name' => __( 'Allow redownloading', 'edd-all-access' ),
			'desc' => __( 'Allow pass holders to redownload the same file without it counting towards their download limit.', 'edd-all-access' ),
			'type' => 'checkbox',
			'std'  => false,
		);
	}


	$settings[] = array(
		'id'   => 'all_access_settings_expired_header',
		'name' => '<strong>' . __( 'If All Access Expired:', 'edd-all-access' ) . '</strong>',
		'desc' => __( 'Set up the messages shown to users.', 'edd-all-access' ),
		'type' => 'header',
		'size' => 'regular',
	);

	$settings[] = array(
		'id'   => 'all_access_expired_text',
		'name' => __( 'Message shown to user:', 'edd-all-access' ),
		'desc' => __( 'Enter the text the user should see if their All Access is expired and they attempt a product download.', 'edd-all-access' ),
		'type' => 'textarea',
		'size' => 'large',
		'std'  => __( 'Your All Access Pass is expired.', 'edd-all-access' ),
	);

	$settings[] = array(
		'id'   => 'all_access_expired_redirect',
		'name' => __( 'Redirect URL (Optional):', 'edd-all-access' ),
		'desc' => __( 'Instead of seeing the above error message, if you\'d like the customer to be redirected to a specific page when they attempt to download a product using an expired All Access pass, enter that URL here.', 'edd-all-access' ),
		'type' => 'text',
		'size' => 'large',
		'std'  => '',
	);

	$settings[] = array(
		'id'   => 'all_access_settings_category_not_included_header',
		'name' => '<strong>' . __( 'If category not included:', 'edd-all-access' ) . '</strong>',
		'desc' => __( 'Set up the messages shown to users.', 'edd-all-access' ),
		'type' => 'header',
		'size' => 'regular',
	);

	$settings[] = array(
		'id'   => 'all_access_category_not_included_text',
		'name' => __( 'Message shown to user:', 'edd-all-access' ),
		'desc' => __( 'Enter the text the user should see if they attempt to download a product in a category they don\'t have All Access for.', 'edd-all-access' ),
		'type' => 'textarea',
		'size' => 'large',
		'std'  => __( 'Your account does not have access to products in this category.', 'edd-all-access' ),
	);

	$settings[] = array(
		'id'   => 'all_access_category_not_included_redirect',
		'name' => __( 'Redirect URL (Optional):', 'edd-all-access' ),
		'desc' => __( 'Instead of seeing the above error message, if you\'d like the customer to be redirected to a specific page when they attempt to download a product using an expired All Access pass, enter that URL here.', 'edd-all-access' ),
		'type' => 'text',
		'size' => 'large',
		'std'  => '',
	);

	$settings[] = array(
		'id'   => 'all_access_settings_price_id_not_included_header',
		'name' => '<strong>' . __( 'If Product Variation not included:', 'edd-all-access' ) . '</strong>',
		'desc' => __( 'Set up the messages shown to users.', 'edd-all-access' ),
		'type' => 'header',
		'size' => 'regular',
	);

	$settings[] = array(
		'id'   => 'all_access_price_id_not_included_text',
		'name' => __( 'Message shown to user:', 'edd-all-access' ),
		'desc' => __( 'Enter the text the user should see if they attempt to download a price variation they don\'t have All Access for.', 'edd-all-access' ),
		'type' => 'textarea',
		'size' => 'large',
		'std'  => __( 'Your account does not have access to this product variation.', 'edd-all-access' ),
	);

	$settings[] = array(
		'id'   => 'all_access_price_id_not_included_redirect',
		'name' => __( 'Redirect URL (Optional):', 'edd-all-access' ),
		'desc' => __( 'Instead of seeing the above error message, if you\'d like the customer to be redirected to a specific page when they attempt to download a product using an expired All Access pass, enter that URL here.', 'edd-all-access' ),
		'type' => 'text',
		'size' => 'large',
		'std'  => '',
	);

	$settings[] = array(
		'id'   => 'all_access_download_limit_reached_header',
		'name' => '<strong>' . __( 'If Download Limit Reached:', 'edd-all-access' ) . '</strong>',
		'desc' => __( 'Set up the messages shown to users when they reach their download limit.', 'edd-all-access' ),
		'type' => 'header',
		'size' => 'regular',
	);

	$settings[] = array(
		'id'   => 'all_access_download_limit_reached_text',
		'name' => __( 'Message shown to user:', 'edd-all-access' ),
		'desc' => __( 'When a customer reaches their download limit, what message should they read?', 'edd-all-access' ),
		'type' => 'textarea',
		'size' => 'large',
		'std'  => __( 'Sorry. You\'ve hit the maximum number of downloads allowed for your All Access account.', 'edd-all-access' ),
	);

	$settings[] = array(
		'id'   => 'all_access_download_limit_reached_redirect',
		'name' => __( 'Redirect URL (Optional):', 'edd-all-access' ),
		'desc' => __( 'Instead of seeing the above error message, if you\'d like the customer to be redirected to a specific page when they hit their download limit, enter the URL for that page here.', 'edd-all-access' ),
		'type' => 'text',
		'size' => 'large',
		'std'  => '',
	);

	$settings[] = array(
		'id'   => 'all_access_modify_download_now_form',
		'name' => '<strong>' . __( 'The "Download Now" area:', 'edd-all-access' ) . '</strong>',
		'desc' => __( 'These options control how the "Download Now" area appears. .', 'edd-all-access' ),
		'type' => 'header',
		'size' => 'regular',
	);

	$settings[] = array(
		'id'            => 'all_access_hide_non_relevant_variable_prices',
		'name'          => __( 'Hide non-relevant variable prices?', 'edd-all-access' ),
		'desc'          => __( 'If a customer has an All Access pass but that pass doesn\'t provide access to a specific variable price, should it be hidden? For example, if the All Access Pass gives access to a "Large" version and thus you want to hide the "Medium" and "Small" versions, choose "Yes" and they will be hidden from those All Access Pass holders. Note they will still appear to people without an All Access pass where they normally would.', 'edd-all-access' ),
		'type'          => 'radio',
		'options'       => array(
			'no'  => __( 'No. I want to show all variable prices to customers with an All Access Pass - even if they don\'t get access to them.', 'edd-all-access' ),
			'yes' => __( 'Yes. Hide non-relevant variable prices from customers with an All Access Pass.', 'edd-all-access' ),

		),
		'std'           => 'no',
		'tooltip_title' => __( 'Hide non-relevant variable prices', 'edd-all-access' ),
		'tooltip_desc'  => __( 'This is perfect for a scenario where your highest variable price would include whatever is in the lower versions and you don\'t want them to show. Make sure your All Access product does NOT include the variations you want to hide. However, if you want to show all variable price options simply set this to no. For example, a photo store might want to allow downloading of small, medium, and large photos. ', 'edd-all-access' ),
	);

	$settings[] = array(
		'id'   => 'all_access_purchase_form_display_header',
		'name' => '<strong>' . __( 'Change the way purchase buttons are displayed (optional):', 'edd-all-access' ) . '</strong>',
		'desc' => __( 'If you wan to sell ONLY All Access Passes and do not wish to sell items individually, you may wish to hide normal purchase buttons and show All Access purchase buttons in their place. The section gives you the option to change the way the normal purchase button area works. ', 'edd-all-access' ),
		'type' => 'header',
		'size' => 'regular',
	);

	$settings[] = array(
		'id'            => 'all_access_purchase_form_display',
		'name'          => __( '"Add To Cart" Display Mode:', 'edd-all-access' ),
		'desc'          => __( 'When individual products are being viewed, how should "Add To Cart" buttons be handled?', 'edd-all-access' ),
		'type'          => 'radio',
		'options'       => array(
			'normal-mode'         => __( '1. Show normal "Add To Cart" buttons only.', 'edd-all-access' ),
			'aa-only-mode'        => __( '2. Show "Buy All Access" and "Login" buttons instead of "Add To Cart" (if the product is included in an All Access Pass).', 'edd-all-access' ),
			'normal-plus-aa-mode' => __( '3. Show both normal "Add To Cart" buttons and "Buy All Access" and "Login" buttons below.', 'edd-all-access' ),
		),
		'std'           => 'normal-mode',
		'tooltip_title' => __( 'Add To Cart Display Mode', 'edd-all-access' ),
		'tooltip_desc'  => __( 'This setting controls what customers will see if they do not have All Access to a product. Note that All Access buy buttons will only be shown if the product is not excluded from All Access. The All Access Pass that will be sold is the last-created one which includes the product being viewed.', 'edd-all-access' ),
	);

	$settings[] = array(
		'id'            => 'all_access_show_buy_instructions',
		'name'          => __( 'Show "Buy All Access" Instructional Text?', 'edd-all-access' ),
		'desc'          => __( 'If your "Add To Cart" Display Mode is set to option 2 or 3, should instructional text be shown above the "Buy All Access" button?', 'edd-all-access' ),
		'type'          => 'radio',
		'options'       => array(
			'show' => __( 'Yes. Show the instructional text above the "Buy All Access" button.', 'edd-all-access' ),
			'hide' => __( 'No. Do not show the instructional text above the "Buy All Access" button.', 'edd-all-access' ),
		),
		'std'           => 'show',
		'tooltip_title' => __( 'Show instructional text', 'edd-all-access' ),
		'tooltip_desc'  => __( 'This allows you to show or hide the instructional text on single product pages if using option 2 or 3 above.', 'edd-all-access' ),
	);

	$settings[] = array(
		'id'            => 'all_access_buy_instructions',
		'name'          => __( '"Buy All Access" Instructional Text:', 'edd-all-access' ),
		'desc'          => __( 'If your "Add To Cart" Display Mode is set to option 2 or 3, what should the text above the "Buy All Access" button say? Default: "To get access, purchase an All Access Pass here."', 'edd-all-access' ),
		'type'          => 'textarea',
		'std'           => __( 'To get access, purchase an All Access Pass here.', 'edd-all-access' ),
		'tooltip_title' => __( 'Buy All Access Instructional Text', 'edd-all-access' ),
		'tooltip_desc'  => __( 'Give people instructional text above All Access purchase buttons. Note: this also affects the text output by the [all_access] shortcode unless overwritten by shortcode args', 'edd-all-access' ),
	);

	$settings[] = array(
		'id'            => 'all_access_show_login_instructions',
		'name'          => __( 'Show "Log In" Instructional Text?', 'edd-all-access' ),
		'desc'          => __( 'If your "Add To Cart" Display Mode is set to option 2 or 3, should instructional text be shown before the "Log In" button?', 'edd-all-access' ),
		'type'          => 'radio',
		'options'       => array(
			'show' => __( 'Yes. Show the instructional text before the "Log In" button.', 'edd-all-access' ),
			'hide' => __( 'No. Do not show the instructional text before the "Log In" button.', 'edd-all-access' ),
		),
		'std'           => 'show',
		'tooltip_title' => __( 'Show instructional text', 'edd-all-access' ),
		'tooltip_desc'  => __( 'This allows you to show or hide the instructional text on single product pages if using option 2 or 3 above.', 'edd-all-access' ),
	);

	$settings[] = array(
		'id'            => 'all_access_login_instructions',
		'name'          => __( '"Log In" Instructional Text:', 'edd-all-access' ),
		'desc'          => __( 'When a "Login" link is shown below the "Buy All Access" button, what should the text before the link say? Default: "Already purchased?"', 'edd-all-access' ),
		'type'          => 'textarea',
		'std'           => __( 'Already purchased?', 'edd-all-access' ),
		'tooltip_title' => __( 'Login Instructional Text', 'edd-all-access' ), // Radio Buttons don't work for tool tip in EDD core yet.
		'tooltip_desc'  => __( 'Give people instructions to log in in order to use their All Access Pass.', 'edd-all-access' ),
	);

	$settings[] = array(
		'id'            => 'all_access_replace_aa_btns_with_custom_btn',
		'name'          => __( 'Bonus Option: Replace "Buy All Access" buttons with a Custom URL button? (Optional)', 'edd-all-access' ),
		'desc'          => __( 'If your "Add To Cart" Display Mode is set to option 2 or 3, instead of showing the "Buy All Access" buttons it describes, you can choose to show a custom button pointing that that URL will display instead. This is perfect if you have a custom-built "pricing" page you\'d like to direct your potential customers to.', 'edd-all-access' ),
		'type'          => 'radio',
		'options'       => array(
			'normal_aa_btns' => __( 'No. Show the "Buy All Access" buttons for all relevant All Access products.', 'edd-all-access' ),
			'custom_btn'     => __( 'Yes. Replace the "Buy All Access" buttons with a single, custom URL button.', 'edd-all-access' ),
		),
		'std'           => 'show',
		'tooltip_title' => __( 'Replace Buy All Access buttons?', 'edd-all-access' ),
		'tooltip_desc'  => __( 'If using option 2 or 3 above, you can replace the default Buy All Access buttons and show a custom button that links to your own custom page instead. Leave this blank if you don\'t wish to use it.', 'edd-all-access' ),
	);

	$settings[] = array(
		'id'   => 'all_access_custom_url_btn_url',
		'name' => __( 'Custom Button URL', 'edd-all-access' ),
		'desc' => __( 'What URL should the Custom button link to when clicked?', 'edd-all-access' ),
		'type' => 'text',
		'size' => 'large',
		'std'  => '',
	);

	$settings[] = array(
		'id'   => 'all_access_custom_url_btn_text',
		'name' => __( 'Custom Button Text', 'edd-all-access' ),
		'desc' => __( 'What should the text on the custom button say? Defaults to "View Pricing" if left blank.', 'edd-all-access' ),
		'type' => 'text',
		'std'  => '',
		'size' => 'large',
	);

	return $settings;

}
add_filter( 'edd_all_access_settings', 'edd_all_access_site_wide_settings' );

/**
 * All Access Product Selector Callback
 *
 * Renders a "chosen" select field containing only All Access enabled products.
 *
 * @since 1.0.0
 * @param array $args Arguments passed by the setting.
 * @return void
 */
function edd_all_access_all_product_dropdown_multiple_callback( $args ) {

	// Get the edd_settings.
	$options = get_option( 'edd_settings' );

	// Extract the value for this setting.
	$edd_option = isset( $options[ $args['id'] ] ) ? $options[ $args['id'] ] : null;

	// Set up a default array which we'll populate with the integer values.
	$selected_products = array();

	// Convert string values to integers as needed by the select function.
	if ( is_array( $edd_option ) ) {
		foreach ( $edd_option as $selected_product ) {
			$selected_products[] = intval( $selected_product );
		}
	}

	if ( $selected_products ) {
		$value = $selected_products;
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$html = EDD()->html->product_dropdown(
		array(
			'name'     => 'edd_settings[' . edd_sanitize_key( $args['id'] ) . '][]',
			'id'       => 'edd_settings[' . edd_sanitize_key( $args['id'] ) . ']',
			'class'    => 'downloads',
			'multiple' => true,
			'chosen'   => true,
			'selected' => $value,
		)
	);

	$html .= '<label for="edd_settings[' . edd_sanitize_key( $args['id'] ) . ']"> ' . wp_kses_post( $args['desc'] ) . '</label>';

	echo wp_kses_post( $html );
}

/**
 * All Access Product Selector Callback
 *
 * Renders a "chosen" select field containing only All Access enabled products.
 *
 * @since 1.0.0
 * @param array $args Arguments passed by the setting.
 * @return void
 */
function edd_all_access_product_dropdown_callback( $args ) {
	$edd_option = edd_get_option( $args['id'] );

	if ( $edd_option ) {
		$value = $edd_option;
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$html = edd_all_access_product_dropdown(
		array(
			'name'     => 'edd_settings[' . edd_sanitize_key( $args['id'] ) . ']',
			'id'       => 'edd_settings[' . edd_sanitize_key( $args['id'] ) . ']',
			'class'    => 'downloads',
			'multiple' => false,
			'chosen'   => true,
			'selected' => $value,
		)
	);

	$html .= '<label for="edd_settings[' . edd_sanitize_key( $args['id'] ) . ']"> ' . wp_kses_post( $args['desc'] ) . '</label>';

	echo wp_kses_post( $html );
}

/**
 * Renders an HTML Dropdown of all the Products (Downloads)
 *
 * @since 1.0.0
 * @param array $args Arguments for the dropdown.
 * @return string $output Product dropdown
 */
function edd_all_access_product_dropdown( $args = array() ) {

	$defaults = array(
		'name'        => 'products',
		'id'          => 'products',
		'class'       => '',
		'multiple'    => false,
		'selected'    => 0,
		'chosen'      => false,
		'number'      => 30,
		'bundles'     => true,
		'placeholder' => sprintf( __( 'Choose a %s', 'edd-all-access' ), edd_get_label_singular() ),
		'data'        => array( 'search-type' => 'download' ),
	);

	$args = wp_parse_args( $args, $defaults );

	$product_args = array(
		'post_type'      => 'download',
		'orderby'        => 'title',
		'order'          => 'ASC',
		'posts_per_page' => $args['number'],
	);

	// Only include All Access Posts.
	$product_args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		'relation' => 'AND',
		array(
			'key'   => '_edd_all_access',
			'value' => '_edd_all_access',
		),
	);

	$products   = get_posts( $product_args );
	$options    = array();
	$options[0] = '';
	if ( $products ) {
		foreach ( $products as $product ) {
			$options[ absint( $product->ID ) ] = esc_html( $product->post_title );
		}
	}

	// This ensures that any selected products are included in the drop down.
	if ( is_array( $args['selected'] ) ) {
		foreach ( $args['selected'] as $item ) {
			if ( ! in_array( $item, $options, true ) ) {
				$options[ $item ] = get_the_title( $item );
			}
		}
	} elseif ( is_numeric( $args['selected'] ) && 0 !== $args['selected'] ) {
		if ( ! in_array( $args['selected'], $options, true ) ) {
			$options[ $args['selected'] ] = get_the_title( $args['selected'] );
		}
	}

	if ( ! $args['bundles'] ) {
		$args['class'] .= ' no-bundles';
	}

	$output = EDD()->html->select(
		array(
			'name'             => $args['name'],
			'selected'         => $args['selected'],
			'id'               => $args['id'],
			'class'            => $args['class'],
			'options'          => $options,
			'chosen'           => $args['chosen'],
			'multiple'         => $args['multiple'],
			'placeholder'      => $args['placeholder'],
			'show_option_all'  => false,
			'show_option_none' => false,
			'data'             => $args['data'],
		)
	);

	return $output;
}
