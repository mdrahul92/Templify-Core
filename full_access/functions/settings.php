<?php 
function edd_full_access_settings_menu( $sections ) {
	$sections['full-access'] = __( 'Full Access', 'edd-full-access' );
	return $sections;
}
add_filter( 'edd_settings_sections_extensions', 'edd_full_access_settings_menu' );


function edd_sl_register_license_section( $sections ) {
	$sections['software-licensing'] = __( 'Software Licensing', 'edd_sl' );

	return $sections;
}
add_filter( 'edd_settings_sections_extensions', 'edd_sl_register_license_section', 10, 1 );

function edd_full_access_settings( $settings ) {

	$all_access_settings = apply_filters( 'edd_full_access_settings', array() );

	if ( version_compare( '3.2.7' ,2.5, '>=' ) ) {
		$all_access_settings = array( 'full-access' => $all_access_settings );
	}

	$settings = array_merge( $settings, $all_access_settings );

	return $settings;
}
add_filter( 'edd_settings_extensions', 'edd_full_access_settings' );




function edd_full_access_site_wide_settings( $settings ) {

	$settings[] = array(
		'id'   => 'all_access_settings_header',
		'name' => '<strong>' . __( 'Full Access Settings:', 'templify-full-access' ) . '</strong>',
		'desc' => __( 'Configure Full Access Settings', 'templify-full-access' ),
		'type' => 'header',
		'size' => 'regular',
	);

	$settings[] = array(
		'id'   => 'all_access_download_now_text',
		'name' => __( '"View Credential" button text.', 'templify-full-access' ),
		'desc' => __( 'What text should be on the "View Credential" buttons?', 'templify-full-access' ),
		'type' => 'text',
		'size' => 'medium',
		'std'  => __( 'View Credentials', 'templify-full-access' ),
	);

	if ( version_compare( '3.2.7', '3.0', '<' ) ) {
		$settings[] = array(
			'id'       => 'all_access_allow_redownload',
			'name'     => __( 'Allow redownloading', 'templify-full-access' ),
			'desc'     => __( 'Allow pass holders to redownload the same file without it counting towards their download limit. Requires Easy Digital Downloads 3.0 or later.', 'templify-full-access' ),
			'type'     => 'descriptive_text',
		);
	} else {
		$settings[] = array(
			'id'   => 'all_access_allow_redownload',
			'name' => __( 'Allow redownloading', 'templify-full-access' ),
			'desc' => __( 'Allow pass holders to redownload the same file without it counting towards their download limit.', 'templify-full-access' ),
			'type' => 'checkbox',
			'std'  => false,
		);
	}


	$settings[] = array(
		'id'   => 'all_access_settings_expired_header',
		'name' => '<strong>' . __( 'If Full Access Expired:', 'templify-full-access' ) . '</strong>',
		'desc' => __( 'Set up the messages shown to users.', 'templify-full-access' ),
		'type' => 'header',
		'size' => 'regular',
	);

	$settings[] = array(
		'id'   => 'all_access_expired_text',
		'name' => __( 'Message shown to user:', 'templify-full-access' ),
		'desc' => __( 'Enter the text the user should see if their Full Access is expired and they attempt a product download.', 'templify-full-access' ),
		'type' => 'textarea',
		'size' => 'large',
		'std'  => __( 'Your Full Access Pass is expired.', 'templify-full-access' ),
	);

	$settings[] = array(
		'id'   => 'all_access_expired_redirect',
		'name' => __( 'Redirect URL (Optional):', 'templify-full-access' ),
		'desc' => __( 'Instead of seeing the above error message, if you\'d like the customer to be redirected to a specific page when they attempt to download a product using an expired Full Access pass, enter that URL here.', 'templify-full-access' ),
		'type' => 'text',
		'size' => 'large',
		'std'  => '',
	);

	$settings[] = array(
		'id'   => 'all_access_settings_category_not_included_header',
		'name' => '<strong>' . __( 'If category not included:', 'templify-full-access' ) . '</strong>',
		'desc' => __( 'Set up the messages shown to users.', 'templify-full-access' ),
		'type' => 'header',
		'size' => 'regular',
	);

	$settings[] = array(
		'id'   => 'all_access_category_not_included_text',
		'name' => __( 'Message shown to user:', 'templify-full-access' ),
		'desc' => __( 'Enter the text the user should see if they attempt to download a product in a category they don\'t have Full Access for.', 'templify-full-access' ),
		'type' => 'textarea',
		'size' => 'large',
		'std'  => __( 'Your account does not have access to products in this category.', 'templify-full-access' ),
	);

	$settings[] = array(
		'id'   => 'all_access_category_not_included_redirect',
		'name' => __( 'Redirect URL (Optional):', 'templify-full-access' ),
		'desc' => __( 'Instead of seeing the above error message, if you\'d like the customer to be redirected to a specific page when they attempt to download a product using an expired Full Access pass, enter that URL here.', 'templify-full-access' ),
		'type' => 'text',
		'size' => 'large',
		'std'  => '',
	);

	$settings[] = array(
		'id'   => 'all_access_settings_price_id_not_included_header',
		'name' => '<strong>' . __( 'If Product Variation not included:', 'templify-full-access' ) . '</strong>',
		'desc' => __( 'Set up the messages shown to users.', 'templify-full-access' ),
		'type' => 'header',
		'size' => 'regular',
	);

	$settings[] = array(
		'id'   => 'all_access_price_id_not_included_text',
		'name' => __( 'Message shown to user:', 'templify-full-access' ),
		'desc' => __( 'Enter the text the user should see if they attempt to download a price variation they don\'t have Full Access for.', 'templify-full-access' ),
		'type' => 'textarea',
		'size' => 'large',
		'std'  => __( 'Your account does not have access to this product variation.', 'templify-full-access' ),
	);

	$settings[] = array(
		'id'   => 'all_access_price_id_not_included_redirect',
		'name' => __( 'Redirect URL (Optional):', 'templify-full-access' ),
		'desc' => __( 'Instead of seeing the above error message, if you\'d like the customer to be redirected to a specific page when they attempt to download a product using an expired Full Access pass, enter that URL here.', 'templify-full-access' ),
		'type' => 'text',
		'size' => 'large',
		'std'  => '',
	);

	$settings[] = array(
		'id'   => 'all_access_download_limit_reached_header',
		'name' => '<strong>' . __( 'If Download Limit Reached:', 'templify-full-access' ) . '</strong>',
		'desc' => __( 'Set up the messages shown to users when they reach their download limit.', 'templify-full-access' ),
		'type' => 'header',
		'size' => 'regular',
	);

	$settings[] = array(
		'id'   => 'all_access_download_limit_reached_text',
		'name' => __( 'Message shown to user:', 'templify-full-access' ),
		'desc' => __( 'When a customer reaches their download limit, what message should they read?', 'templify-full-access' ),
		'type' => 'textarea',
		'size' => 'large',
		'std'  => __( 'Sorry. You\'ve hit the maximum number of downloads allowed for your Full Access account.', 'templify-full-access' ),
	);

	$settings[] = array(
		'id'   => 'all_access_download_limit_reached_redirect',
		'name' => __( 'Redirect URL (Optional):', 'templify-full-access' ),
		'desc' => __( 'Instead of seeing the above error message, if you\'d like the customer to be redirected to a specific page when they hit their download limit, enter the URL for that page here.', 'templify-full-access' ),
		'type' => 'text',
		'size' => 'large',
		'std'  => '',
	);

	$settings[] = array(
		'id'   => 'all_access_modify_download_now_form',
		'name' => '<strong>' . __( 'The "Download Now" area:', 'templify-full-access' ) . '</strong>',
		'desc' => __( 'These options control how the "Download Now" area appears. .', 'templify-full-access' ),
		'type' => 'header',
		'size' => 'regular',
	);

	$settings[] = array(
		'id'            => 'all_access_hide_non_relevant_variable_prices',
		'name'          => __( 'Hide non-relevant variable prices?', 'templify-full-access' ),
		'desc'          => __( 'If a customer has an Full Access pass but that pass doesn\'t provide access to a specific variable price, should it be hidden? For example, if the Full Access Pass gives access to a "Large" version and thus you want to hide the "Medium" and "Small" versions, choose "Yes" and they will be hidden from those Full Access Pass holders. Note they will still appear to people without an Full Access pass where they normally would.', 'templify-full-access' ),
		'type'          => 'radio',
		'options'       => array(
			'no'  => __( 'No. I want to show all variable prices to customers with an Full Access Pass - even if they don\'t get access to them.', 'templify-full-access' ),
			'yes' => __( 'Yes. Hide non-relevant variable prices from customers with an Full Access Pass.', 'templify-full-access' ),

		),
		'std'           => 'no',
		'tooltip_title' => __( 'Hide non-relevant variable prices', 'templify-full-access' ),
		'tooltip_desc'  => __( 'This is perfect for a scenario where your highest variable price would include whatever is in the lower versions and you don\'t want them to show. Make sure your Full Access product does NOT include the variations you want to hide. However, if you want to show all variable price options simply set this to no. For example, a photo store might want to allow downloading of small, medium, and large photos. ', 'templify-full-access' ),
	);

	$settings[] = array(
		'id'   => 'all_access_purchase_form_display_header',
		'name' => '<strong>' . __( 'Change the way purchase buttons are displayed (optional):', 'templify-full-access' ) . '</strong>',
		'desc' => __( 'If you wan to sell ONLY Full Access Passes and do not wish to sell items individually, you may wish to hide normal purchase buttons and show Full Access purchase buttons in their place. The section gives you the option to change the way the normal purchase button area works. ', 'templify-full-access' ),
		'type' => 'header',
		'size' => 'regular',
	);

	$settings[] = array(
		'id'            => 'all_access_purchase_form_display',
		'name'          => __( '"Add To Cart" Display Mode:', 'templify-full-access' ),
		'desc'          => __( 'When individual products are being viewed, how should "Add To Cart" buttons be handled?', 'templify-full-access' ),
		'type'          => 'radio',
		'options'       => array(
			'normal-mode'         => __( '1. Show normal "Add To Cart" buttons only.', 'templify-full-access' ),
			'aa-only-mode'        => __( '2. Show "Buy Full Access" and "Login" buttons instead of "Add To Cart" (if the product is included in an Full Access Pass).', 'templify-full-access' ),
			'normal-plus-aa-mode' => __( '3. Show both normal "Add To Cart" buttons and "Buy Full Access" and "Login" buttons below.', 'templify-full-access' ),
		),
		'std'           => 'normal-mode',
		'tooltip_title' => __( 'Add To Cart Display Mode', 'templify-full-access' ),
		'tooltip_desc'  => __( 'This setting controls what customers will see if they do not have Full Access to a product. Note that Full Access buy buttons will only be shown if the product is not excluded from Full Access. The Full Access Pass that will be sold is the last-created one which includes the product being viewed.', 'templify-full-access' ),
	);

	$settings[] = array(
		'id'            => 'all_access_show_buy_instructions',
		'name'          => __( 'Show "Buy Full Access" Instructional Text?', 'templify-full-access' ),
		'desc'          => __( 'If your "Add To Cart" Display Mode is set to option 2 or 3, should instructional text be shown above the "Buy Full Access" button?', 'templify-full-access' ),
		'type'          => 'radio',
		'options'       => array(
			'show' => __( 'Yes. Show the instructional text above the "Buy Full Access" button.', 'templify-full-access' ),
			'hide' => __( 'No. Do not show the instructional text above the "Buy Full Access" button.', 'templify-full-access' ),
		),
		'std'           => 'show',
		'tooltip_title' => __( 'Show instructional text', 'templify-full-access' ),
		'tooltip_desc'  => __( 'This allows you to show or hide the instructional text on single product pages if using option 2 or 3 above.', 'templify-full-access' ),
	);

	$settings[] = array(
		'id'            => 'all_access_buy_instructions',
		'name'          => __( '"Buy Full Access" Instructional Text:', 'templify-full-access' ),
		'desc'          => __( 'If your "Add To Cart" Display Mode is set to option 2 or 3, what should the text above the "Buy Full Access" button say? Default: "To get access, purchase an Full Access Pass here."', 'templify-full-access' ),
		'type'          => 'textarea',
		'std'           => __( 'To get access, purchase an Full Access Pass here.', 'templify-full-access' ),
		'tooltip_title' => __( 'Buy Full Access Instructional Text', 'templify-full-access' ),
		'tooltip_desc'  => __( 'Give people instructional text above Full Access purchase buttons. Note: this also affects the text output by the [all_access] shortcode unless overwritten by shortcode args', 'templify-full-access' ),
	);

	$settings[] = array(
		'id'            => 'all_access_show_login_instructions',
		'name'          => __( 'Show "Log In" Instructional Text?', 'templify-full-access' ),
		'desc'          => __( 'If your "Add To Cart" Display Mode is set to option 2 or 3, should instructional text be shown before the "Log In" button?', 'templify-full-access' ),
		'type'          => 'radio',
		'options'       => array(
			'show' => __( 'Yes. Show the instructional text before the "Log In" button.', 'templify-full-access' ),
			'hide' => __( 'No. Do not show the instructional text before the "Log In" button.', 'templify-full-access' ),
		),
		'std'           => 'show',
		'tooltip_title' => __( 'Show instructional text', 'templify-full-access' ),
		'tooltip_desc'  => __( 'This allows you to show or hide the instructional text on single product pages if using option 2 or 3 above.', 'templify-full-access' ),
	);

	$settings[] = array(
		'id'            => 'all_access_login_instructions',
		'name'          => __( '"Log In" Instructional Text:', 'templify-full-access' ),
		'desc'          => __( 'When a "Login" link is shown below the "Buy Full Access" button, what should the text before the link say? Default: "Already purchased?"', 'templify-full-access' ),
		'type'          => 'textarea',
		'std'           => __( 'Already purchased?', 'templify-full-access' ),
		'tooltip_title' => __( 'Login Instructional Text', 'templify-full-access' ), // Radio Buttons don't work for tool tip in EDD core yet.
		'tooltip_desc'  => __( 'Give people instructions to log in in order to use their Full Access Pass.', 'templify-full-access' ),
	);

	$settings[] = array(
		'id'            => 'all_access_replace_aa_btns_with_custom_btn',
		'name'          => __( 'Bonus Option: Replace "Buy Full Access" buttons with a Custom URL button? (Optional)', 'templify-full-access' ),
		'desc'          => __( 'If your "Add To Cart" Display Mode is set to option 2 or 3, instead of showing the "Buy Full Access" buttons it describes, you can choose to show a custom button pointing that that URL will display instead. This is perfect if you have a custom-built "pricing" page you\'d like to direct your potential customers to.', 'templify-full-access' ),
		'type'          => 'radio',
		'options'       => array(
			'normal_aa_btns' => __( 'No. Show the "Buy Full Access" buttons for all relevant Full Access products.', 'templify-full-access' ),
			'custom_btn'     => __( 'Yes. Replace the "Buy Full Access" buttons with a single, custom URL button.', 'templify-full-access' ),
		),
		'std'           => 'show',
		'tooltip_title' => __( 'Replace Buy Full Access buttons?', 'templify-full-access' ),
		'tooltip_desc'  => __( 'If using option 2 or 3 above, you can replace the default Buy Full Access buttons and show a custom button that links to your own custom page instead. Leave this blank if you don\'t wish to use it.', 'templify-full-access' ),
	);

	$settings[] = array(
		'id'   => 'all_access_custom_url_btn_url',
		'name' => __( 'Custom Button URL', 'templify-full-access' ),
		'desc' => __( 'What URL should the Custom button link to when clicked?', 'templify-full-access' ),
		'type' => 'text',
		'size' => 'large',
		'std'  => '',
	);

	$settings[] = array(
		'id'   => 'all_access_custom_url_btn_text',
		'name' => __( 'Custom Button Text', 'templify-full-access' ),
		'desc' => __( 'What should the text on the custom button say? Defaults to "View Pricing" if left blank.', 'templify-full-access' ),
		'type' => 'text',
		'std'  => '',
		'size' => 'large',
	);

	return $settings;
}
add_filter( 'edd_full_access_settings', 'edd_full_access_site_wide_settings' );

