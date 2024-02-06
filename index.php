<?php
/*
Plugin Name: Templify Core
Description: Templify Core Plugin description.
Version: 1.0
Author: Templify
*/

// Check if Easy Digital Downloads is installed and activated
function templify_core_check_edd() {
    $edd_path = 'easy-digital-downloads/easy-digital-downloads.php';
    $edd_active = in_array($edd_path, (array)get_option('active_plugins', array()));

	return $edd_active || is_plugin_active($edd_path);


    //return array('edd_active' => $edd_active);
}

// Enqueue scripts and styles
function templify_core_enqueue_scripts() {
    // Enqueue your plugin scripts
    wp_enqueue_style('templify-core-style', plugins_url('assets/frontend/css/style.css', __FILE__));
    wp_enqueue_script('templify-core-script', plugins_url('assets/frontend/js/script.js', __FILE__), array('jquery'), '1.0', true);
}
add_action('wp_enqueue_scripts', 'templify_core_enqueue_scripts');


// Enqueue scripts and styles
function templify_core_admin_enqueue_scripts() {
    // Enqueue your plugin scripts
    wp_enqueue_style('templify-core-admin-style', plugins_url('assets/admin/css/style.css', __FILE__));
    wp_enqueue_script('templify-core--admin-script', plugins_url('assets/admin/js/script.js', __FILE__), array('jquery'), '1.0', true);
}
add_action('admin_enqueue_scripts', 'templify_core_admin_enqueue_scripts');

// Activation Hook
register_activation_hook(__FILE__, 'templify_core_activation');

function templify_core_activation() {
    $result = templify_core_check_edd();
    if ($result['edd_active']) {
        // templify_core_add_edd_full_access();
    } else {
        // Deactivate the plugin
        deactivate_plugins(plugin_basename(__FILE__));
        // Set a transient to show the notice
        set_transient('templify_core_edd_notice', true, 5 * MINUTE_IN_SECONDS);
    }
}


function templify_core_menu() {
    add_menu_page(
        'Templify Core',
        'Templify Core',
        'manage_options',
        'templify-core-dashboard',
        'templify_core_dashboard_page'
    );

    add_submenu_page(
        'templify-core-dashboard',
        'Dashboard',
        'Dashboard',
        'manage_options',
        'templify-core-dashboard',
        'templify_core_dashboard_page'
    );

}

add_action('admin_menu', 'templify_core_menu');

function templify_core_dashboard_page() {
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';
    ?>
    <div class="wrap">
        <h2 class="nav-tab-wrapper">
            <a class="nav-tab <?php echo $active_tab === 'dashboard' ? 'nav-tab-active' : ''; ?>" href="?page=templify-core-dashboard&tab=dashboard">Welcome</a>
            <a class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>" href="?page=templify-core-dashboard&tab=general">Settings</a>
        </h2>

        <div class="dashboard-content">
            <?php
            if ($active_tab === 'dashboard') {
                // Content for the Dashboard tab goes here
                echo '<h2>Welcome Content</h2>';
            } elseif ($active_tab === 'general') {
				$general_tab = isset($_GET['sub_tab']) ? sanitize_text_field($_GET['sub_tab']) : 'general'; // Updated line
            ?>
            <div class="wrap">
                <div class="wpt-settings-container">
                    <div class="wpt-settings-menu">
                        <ul class="nav-tab-wrapper">
                            <li><a class="nav-tab <?php echo $general_tab === 'general' ? 'nav-tab-active' : ''; ?>" href="?page=templify-core-dashboard&tab=general&sub_tab=general">General</a></li>
                            <li><a class="nav-tab <?php echo $general_tab === 'full_access' ? 'nav-tab-active' : ''; ?>" href="?page=templify-core-dashboard&tab=general&sub_tab=full_access">Full Access</a></li>
                            <!-- Add other settings as needed -->
                        </ul>
                    </div>
                    <div class="wpt-settings-content">
                        <?php
                        if ($general_tab === 'general') {
                            // Content for the General tab goes here
                            echo '<h2>General Settings Content</h2>';
                        } elseif ($general_tab === 'full_access') {
                            // Content for the Full Access tab goes here
                            render_templify_core_full_access_settings();
                        }
                        // Add content for other settings as needed
                        ?>
                    </div>
                </div>
            </div>
		

            <?}
            ?>
        </div>
    </div>
    <?php
}


function render_templify_core_full_access_settings() {
    ?>
    <div class="wrap">
        <form method="post" action="options.php">
            <?php
            settings_fields('templify_core_full_access_settings_group');
            do_settings_sections('templify_core_full_access_settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function templify_core_register_settings() {
    
    add_settings_section(
        'templify_core_full_access_settings_section',
        'Full Access Settings:',
        'edd_full_access_general_section_callback', // Placeholder function, you may need to create a new callback function
        'templify_core_full_access_settings'
    );

    $settings = array();

        // Download Now Button Text
        $settings[] = array(
            'id'   => 'full_access_download_now_text',
            'name' => __( '"View Credentials" button text.', 'templify-full-access' ),
            'desc' => __( 'What text should be on the "View Credentials" buttons?', 'templify-full-access' ),
            'type' => 'text',
            'size' => 'medium',
            'std'  => __( 'Download Now', 'templify-full-access' ),
        );

        

    $settings[] = array(
		'id'   => 'full_access_settings_expired_header',
		'name' => '<strong>' . __( 'If Full Access Expired:', 'templify-full-access' ) . '</strong>',
		'desc' => __( 'Set up the messages shown to users.', 'templify-full-access' ),
		'type' => 'header',
		'size' => 'regular',
	);

	$settings[] = array(
		'id'   => 'full_access_expired_text',
		'name' => __( 'Message shown to user:', 'templify-full-access' ),
		'desc' => __( 'Enter the text the user should see if their Full Access is expired and they attempt a product download.', 'templify-full-access' ),
		'type' => 'textarea',
		'size' => 'large',
		'std'  => __( 'Your Full Access License is expired.', 'templify-full-access' ),
	);

	$settings[] = array(
		'id'   => 'full_access_expired_redirect',
		'name' => __( 'Redirect URL (Optional):', 'templify-full-access' ),
		'desc' => __( 'Instead of seeing the above error message, if you\'d like the customer to be redirected to a specific page when they attempt to download a product using an expired Full Access pass, enter that URL here.', 'templify-full-access' ),
		'type' => 'text',
		'size' => 'large',
		'std'  => '',
	);

	$settings[] = array(
		'id'   => 'full_access_settings_category_not_included_header',
		'name' => '<strong>' . __( 'If Template Category not included:', 'templify-full-access' ) . '</strong>',
		'desc' => __( 'Set up the messages shown to users.', 'templify-full-access' ),
		'type' => 'header',
		'size' => 'regular',
	);

	$settings[] = array(
		'id'   => 'full_access_category_not_included_text',
		'name' => __( 'Message shown to user:', 'templify-full-access' ),
		'desc' => __( 'Enter the text the user should see if they attempt to download a product in a category they don\'t have Full Access for.', 'templify-full-access' ),
		'type' => 'textarea',
		'size' => 'large',
		'std'  => __( 'Your account does not have access to products in this category.', 'templify-full-access' ),
	);

	$settings[] = array(
		'id'   => 'full_access_category_not_included_redirect',
		'name' => __( 'Redirect URL (Optional):', 'templify-full-access' ),
		'desc' => __( 'Instead of seeing the above error message, if you\'d like the customer to be redirected to a specific page when they attempt to download a product using an expired Full Access pass, enter that URL here.', 'templify-full-access' ),
		'type' => 'text',
		'size' => 'large',
		'std'  => '',
	);

	$settings[] = array(
		'id'   => 'full_access_settings_price_id_not_included_header',
		'name' => '<strong>' . __( 'If Template Variation not included:', 'templify-full-access' ) . '</strong>',
		'desc' => __( 'Set up the messages shown to users.', 'templify-full-access' ),
		'type' => 'header',
		'size' => 'regular',
	);

	$settings[] = array(
		'id'   => 'full_access_price_id_not_included_text',
		'name' => __( 'Message shown to user:', 'templify-full-access' ),
		'desc' => __( 'Enter the text the user should see if they attempt to download a price variation they don\'t have Full Access for.', 'templify-full-access' ),
		'type' => 'textarea',
		'size' => 'large',
		'std'  => __( 'Your account does not have access to this product variation.', 'templify-full-access' ),
	);

	$settings[] = array(
		'id'   => 'full_access_price_id_not_included_redirect',
		'name' => __( 'Redirect URL (Optional):', 'templify-full-access' ),
		'desc' => __( 'Instead of seeing the above error message, if you\'d like the customer to be redirected to a specific page when they attempt to download a product using an expired Full Access pass, enter that URL here.', 'templify-full-access' ),
		'type' => 'text',
		'size' => 'large',
		'std'  => '',
	);

	$settings[] = array(
		'id'   => 'full_access_download_limit_reached_header',
		'name' => '<strong>' . __( 'If Download Limit Reached:', 'templify-full-access' ) . '</strong>',
		'desc' => __( 'Set up the messages shown to users when they reach their download limit.', 'templify-full-access' ),
		'type' => 'header',
		'size' => 'regular',
	);

	$settings[] = array(
		'id'   => 'full_access_download_limit_reached_text',
		'name' => __( 'Message shown to user:', 'templify-full-access' ),
		'desc' => __( 'When a customer reaches their download limit, what message should they read?', 'templify-full-access' ),
		'type' => 'textarea',
		'size' => 'large',
		'std'  => __( 'Sorry. You\'ve hit the maximum number of downloads allowed for your Full Access account.', 'templify-full-access' ),
	);

	$settings[] = array(
		'id'   => 'full_access_download_limit_reached_redirect',
		'name' => __( 'Redirect URL (Optional):', 'templify-full-access' ),
		'desc' => __( 'Instead of seeing the above error message, if you\'d like the customer to be redirected to a specific page when they hit their download limit, enter the URL for that page here.', 'templify-full-access' ),
		'type' => 'text',
		'size' => 'large',
		'std'  => '',
	);

	$settings[] = array(
		'id'   => 'full_access_modify_download_now_form',
		'name' => '<strong>' . __( 'The "Download Now" area:', 'templify-full-access' ) . '</strong>',
		'desc' => __( 'These options control how the "Download Now" area appears. .', 'templify-full-access' ),
		'type' => 'header',
		'size' => 'regular',
	);

	$settings[] = array(
		'id'            => 'full_access_hide_non_relevant_variable_prices',
		'name'          => __( 'Hide non-relevant variable prices?', 'templify-full-access' ),
		'desc'          => __( 'If a customer has an Full Access pass but that pass doesn\'t provide access to a specific variable price, should it be hidden? For example, if the Full Access License gives access to a "Large" version and thus you want to hide the "Medium" and "Small" versions, choose "Yes" and they will be hidden from those Full Access License holders. Note they will still appear to people without an Full Access pass where they normally would.', 'templify-full-access' ),
		'type'          => 'radio',
		'options'       => array(
			'no'  => __( 'No. I want to show all variable prices to customers with an Full Access License - even if they don\'t get access to them.', 'templify-full-access' ),
			'yes' => __( 'Yes. Hide non-relevant variable prices from customers with an Full Access License.', 'templify-full-access' ),

		),
		'std'           => 'no',
		'tooltip_title' => __( 'Hide non-relevant variable prices', 'templify-full-access' ),
		'tooltip_desc'  => __( 'This is perfect for a scenario where your highest variable price would include whatever is in the lower versions and you don\'t want them to show. Make sure your Full Access product does NOT include the variations you want to hide. However, if you want to show all variable price options simply set this to no. For example, a photo store might want to allow downloading of small, medium, and large photos. ', 'templify-full-access' ),
	);

	$settings[] = array(
		'id'   => 'full_access_purchase_form_display_header',
		'name' => '<strong>' . __( 'Change the way purchase buttons are displayed (optional):', 'templify-full-access' ) . '</strong>',
		'desc' => __( 'If you wan to sell ONLY Full Access Licensees and do not wish to sell items individually, you may wish to hide normal purchase buttons and show Full Access purchase buttons in their place. The section gives you the option to change the way the normal purchase button area works. ', 'templify-full-access' ),
		'type' => 'header',
		'size' => 'regular',
	);

	$settings[] = array(
		'id'            => 'full_access_purchase_form_display',
		'name'          => __( '"Add To Cart" Display Mode:', 'templify-full-access' ),
		'desc'          => __( 'When individual products are being viewed, how should "Add To Cart" buttons be handled?', 'templify-full-access' ),
		'type'          => 'radio',
		'options'       => array(
			'normal-mode'         => __( '1. Show normal "Add To Cart" buttons only.', 'templify-full-access' ),
			'aa-only-mode'        => __( '2. Show "Buy Full Access" and "Login" buttons instead of "Add To Cart" (if the product is included in an Full Access License).', 'templify-full-access' ),
			'normal-plus-aa-mode' => __( '3. Show both normal "Add To Cart" buttons and "Buy Full Access" and "Login" buttons below.', 'templify-full-access' ),
		),
		'std'           => 'normal-mode',
		'tooltip_title' => __( 'Add To Cart Display Mode', 'templify-full-access' ),
		'tooltip_desc'  => __( 'This setting controls what customers will see if they do not have Full Access to a product. Note that Full Access buy buttons will only be shown if the product is not excluded from Full Access. The Full Access License that will be sold is the last-created one which includes the product being viewed.', 'templify-full-access' ),
	);

	$settings[] = array(
		'id'            => 'full_access_show_buy_instructions',
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
		'id'            => 'full_access_buy_instructions',
		'name'          => __( '"Buy Full Access" Instructional Text:', 'templify-full-access' ),
		'desc'          => __( 'If your "Add To Cart" Display Mode is set to option 2 or 3, what should the text above the "Buy Full Access" button say? Default: "To get access, purchase an Full Access License here."', 'templify-full-access' ),
		'type'          => 'textarea',
		'std'           => __( 'To get access, purchase an Full Access License here.', 'templify-full-access' ),
		'tooltip_title' => __( 'Buy Full Access Instructional Text', 'templify-full-access' ),
		'tooltip_desc'  => __( 'Give people instructional text above Full Access purchase buttons. Note: this also affects the text output by the [full_access] shortcode unless overwritten by shortcode args', 'templify-full-access' ),
	);

	$settings[] = array(
		'id'            => 'full_access_show_login_instructions',
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
		'id'            => 'full_access_login_instructions',
		'name'          => __( '"Log In" Instructional Text:', 'templify-full-access' ),
		'desc'          => __( 'When a "Login" link is shown below the "Buy Full Access" button, what should the text before the link say? Default: "Already purchased?"', 'templify-full-access' ),
		'type'          => 'textarea',
		'std'           => __( 'Already purchased?', 'templify-full-access' ),
		'tooltip_title' => __( 'Login Instructional Text', 'templify-full-access' ), // Radio Buttons don't work for tool tip in EDD core yet.
		'tooltip_desc'  => __( 'Give people instructions to log in in order to use their Full Access License.', 'templify-full-access' ),
	);

	$settings[] = array(
		'id'            => 'full_access_replace_aa_btns_with_custom_btn',
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
		'id'   => 'full_access_custom_url_btn_url',
		'name' => __( 'Custom Button URL', 'templify-full-access' ),
		'desc' => __( 'What URL should the Custom button link to when clicked?', 'templify-full-access' ),
		'type' => 'text',
		'size' => 'large',
		'std'  => '',
	);

	$settings[] = array(
		'id'   => 'full_access_custom_url_btn_text',
		'name' => __( 'Custom Button Text', 'templify-full-access' ),
		'desc' => __( 'What should the text on the custom button say? Defaults to "View Pricing" if left blank.', 'templify-full-access' ),
		'type' => 'text',
		'std'  => '',
		'size' => 'large',
	);


    foreach ($settings as $setting) {
        add_settings_field(
            $setting['id'],
            $setting['name'],
            'templify_core_' . $setting['id'] . '_callback', // Adjust the callback function name
            'templify_core_full_access_settings',
            'templify_core_full_access_settings_section',
            $setting
        );
    }

    // This function is needed to register settings
    register_setting('templify_core_full_access_settings_group', 'templify_core_full_access_settings');
}



function edd_full_access_general_section_callback() {
 
}


function templify_core_full_access_settings_header_callback($args) {
    //echo $args['desc'];
}

function templify_core_full_access_download_now_text_callback($args) {
    $option = get_option('templify_core_full_access_settings');

    if (is_array($option) && isset($option['full_access_download_now_text'])) {
        echo "<input type='text' name='templify_core_full_access_settings[full_access_download_now_text]' value='" . esc_attr($option['full_access_download_now_text']) . "' />";
    } else {
        // Provide a default value or handle it accordingly
        echo "<input type='text' name='templify_core_full_access_settings[full_access_download_now_text]' value='' />";
    }
    echo "<br>".$args['desc'];
}



function templify_core_full_access_settings_expired_header_callback($args) {
   
    
}


function templify_core_full_access_expired_text_callback($args){
    $option = get_option('templify_core_full_access_settings');

    echo "<textarea name='templify_core_full_access_settings[full_access_expired_text]' rows='5' cols='50'></textarea>";
    echo "<br>".$args['desc'];
}


function templify_core_full_access_expired_redirect_callback($args){
    $option = get_option('templify_core_full_access_settings');

    echo '<input type="text" class=" large-text" id="edd_settings[full_access_expired_redirect]" name="edd_settings[full_access_expired_redirect]" value="">';
    echo "<br>".$args['desc'];
}


function templify_core_full_access_settings_category_not_included_header_callback($args){

}


function templify_core_full_access_category_not_included_text_callback($args){
    $option = get_option('templify_core_full_access_settings');

    echo '<textarea class="" cols="50" rows="5" id="edd_settings[full_access_category_not_included_text]" name="edd_settings[full_access_category_not_included_text]">Your account does not have access to products in this category.</textarea>';
    echo "<br>".$args['desc'];

}

function templify_core_full_access_category_not_included_redirect_callback($args){
    $option = get_option('templify_core_full_access_settings');

    echo '<input type="text" class=" large-text" id="edd_settings[full_access_category_not_included_redirect]" name="edd_settings[full_access_category_not_included_redirect]" value="">';
    echo "<br>".$args['desc'];
}


function templify_core_full_access_settings_price_id_not_included_header_callback($args){

}


function templify_core_full_access_price_id_not_included_text_callback($args){
    $option = get_option('templify_core_full_access_settings');

    echo '<textarea class="" cols="50" rows="5" id="edd_settings[full_access_price_id_not_included_text]" name="edd_settings[full_access_price_id_not_included_text]">Your account does not have access to this product variation.</textarea>';
    echo "<br>".$args['desc'];
}

function templify_core_full_access_price_id_not_included_redirect_callback($args){
    $option = get_option('templify_core_full_access_settings');

    echo '<input type="text" class=" large-text" id="edd_settings[full_access_price_id_not_included_redirect]" name="edd_settings[full_access_price_id_not_included_redirect]" value="">';
    echo "<br>".$args['desc'];
}

function templify_core_full_access_download_limit_reached_header_callback($args){

}

function templify_core_full_access_download_limit_reached_text_callback($args){
	$option = get_option('templify_core_full_access_settings');

    echo '<textarea class="" cols="50" rows="5" id="edd_settings[full_access_download_limit_reached_text]" name="edd_settings[full_access_download_limit_reached_text]">Sorry. Youve hit the maximum number of downloads allowed for your Full Access account.</textarea>';
    echo "<br>".$args['desc'];
}

function templify_core_full_access_download_limit_reached_redirect_callback($args){
	$option = get_option('templify_core_full_access_settings');

    echo '<input type="text" class=" large-text" id="edd_settings[full_access_download_limit_reached_redirect]" name="edd_settings[full_access_download_limit_reached_redirect]" value="">';
    echo "<br>".$args['desc'];
}

function templify_core_full_access_modify_download_now_form_callback($args){

}

function templify_core_full_access_hide_non_relevant_variable_prices_callback($args){
	$option = get_option('templify_core_full_access_settings');

    echo '<div class="edd-check-wrapper"><input name="edd_settings[full_access_hide_non_relevant_variable_prices]" id="edd_settings[full_access_hide_non_relevant_variable_prices][no]" class="" type="radio" value="no" checked="checked">&nbsp;<label for="edd_settings[full_access_hide_non_relevant_variable_prices][no]">No. I want to show all variable prices to customers with an Full Access License - even if they dont get access to them.</label></div><div class="edd-check-wrapper"><input name="edd_settings[full_access_hide_non_relevant_variable_prices]" id="edd_settings[full_access_hide_non_relevant_variable_prices][yes]" class="" type="radio" value="yes">&nbsp;<label for="edd_settings[full_access_hide_non_relevant_variable_prices][yes]">Yes. Hide non-relevant variable prices from customers with an Full Access License.</label></div>';
    echo "<br>".$args['desc'];
}

function templify_core_full_access_purchase_form_display_header_callback($args){

}


function templify_core_full_access_purchase_form_display_callback($args){
	$option = get_option('templify_core_full_access_settings');

    echo '<div class="edd-check-wrapper"><input name="edd_settings[full_access_purchase_form_display]" id="edd_settings[full_access_purchase_form_display][normal-mode]" class="" type="radio" value="normal-mode" checked="checked">&nbsp;<label for="edd_settings[full_access_purchase_form_display][normal-mode]">1. Show normal "Add To Cart" buttons only.</label></div><div class="edd-check-wrapper"><input name="edd_settings[full_access_purchase_form_display]" id="edd_settings[full_access_purchase_form_display][aa-only-mode]" class="" type="radio" value="aa-only-mode">&nbsp;<label for="edd_settings[full_access_purchase_form_display][aa-only-mode]">2. Show "Buy Full Access" and "Login" buttons instead of "Add To Cart" (if the product is included in an Full Access License).</label></div><div class="edd-check-wrapper"><input name="edd_settings[full_access_purchase_form_display]" id="edd_settings[full_access_purchase_form_display][normal-plus-aa-mode]" class="" type="radio" value="normal-plus-aa-mode">&nbsp;<label for="edd_settings[full_access_purchase_form_display][normal-plus-aa-mode]">3. Show both normal "Add To Cart" buttons and "Buy Full Access" and "Login" buttons below.</label></div>';
    echo "<br>".$args['desc'];
}

function templify_core_full_access_show_buy_instructions_callback($args){
	$option = get_option('templify_core_full_access_settings');

    echo '<div class="edd-check-wrapper"><input name="edd_settings[full_access_show_buy_instructions]" id="edd_settings[full_access_show_buy_instructions][show]" class="" type="radio" value="show" checked="checked">&nbsp;<label for="edd_settings[full_access_show_buy_instructions][show]">Yes. Show the instructional text above the "Buy Full Access" button.</label></div><div class="edd-check-wrapper"><input name="edd_settings[full_access_show_buy_instructions]" id="edd_settings[full_access_show_buy_instructions][hide]" class="" type="radio" value="hide">&nbsp;<label for="edd_settings[full_access_show_buy_instructions][hide]">No. Do not show the instructional text above the "Buy Full Access" button.</label></div>';
    echo "<br>".$args['desc'];
}

function templify_core_full_access_buy_instructions_callback($args){
	$option = get_option('templify_core_full_access_settings');

    echo '<textarea class="" cols="50" rows="5" id="edd_settings[full_access_buy_instructions]" name="edd_settings[full_access_buy_instructions]">To get access, purchase an Full Access License here.</textarea>';
    echo "<br>".$args['desc'];
}

function templify_core_full_access_show_login_instructions_callback($args){
	$option = get_option('templify_core_full_access_settings');

    echo '<div class="edd-check-wrapper"><input name="edd_settings[full_access_show_login_instructions]" id="edd_settings[full_access_show_login_instructions][show]" class="" type="radio" value="show" checked="checked">&nbsp;<label for="edd_settings[full_access_show_login_instructions][show]">Yes. Show the instructional text before the "Log In" button.</label></div><div class="edd-check-wrapper"><input name="edd_settings[full_access_show_login_instructions]" id="edd_settings[full_access_show_login_instructions][hide]" class="" type="radio" value="hide">&nbsp;<label for="edd_settings[full_access_show_login_instructions][hide]">No. Do not show the instructional text before the "Log In" button.</label></div>';
    echo "<br>".$args['desc'];
}

function templify_core_full_access_login_instructions_callback($args){
	$option = get_option('templify_core_full_access_settings');

    echo '<textarea class="" cols="50" rows="5" id="edd_settings[full_access_login_instructions]" name="edd_settings[full_access_login_instructions]">Already purchased?</textarea>';
    echo "<br>".$args['desc'];
}

function templify_core_full_access_replace_aa_btns_with_custom_btn_callback($args){
	$option = get_option('templify_core_full_access_settings');

    echo '<div class="edd-check-wrapper"><input name="edd_settings[full_access_replace_aa_btns_with_custom_btn]" id="edd_settings[full_access_replace_aa_btns_with_custom_btn][normal_aa_btns]" class="" type="radio" value="normal_aa_btns">&nbsp;<label for="edd_settings[full_access_replace_aa_btns_with_custom_btn][normal_aa_btns]">No. Show the "Buy Full Access" buttons for all relevant Full Access products.</label></div><div class="edd-check-wrapper"><input name="edd_settings[full_access_replace_aa_btns_with_custom_btn]" id="edd_settings[full_access_replace_aa_btns_with_custom_btn][custom_btn]" class="" type="radio" value="custom_btn">&nbsp;<label for="edd_settings[full_access_replace_aa_btns_with_custom_btn][custom_btn]">Yes. Replace the "Buy Full Access" buttons with a single, custom URL button.</label></div>';
    echo "<br>".$args['desc'];
}

function templify_core_full_access_custom_url_btn_url_callback($args){
	$option = get_option('templify_core_full_access_settings');

    echo '<input type="text" class=" large-text" id="edd_settings[full_access_custom_url_btn_url]" name="edd_settings[full_access_custom_url_btn_url]" value="">';
    echo "<br>".$args['desc'];
}

function templify_core_full_access_custom_url_btn_text_callback($args){
	$option = get_option('templify_core_full_access_settings');

    echo '<input type="text" class=" large-text" id="edd_settings[full_access_custom_url_btn_text]" name="edd_settings[full_access_custom_url_btn_text]" value="">';
    echo "<br>".$args['desc'];
}


// ... (existing code)

// Hook your functions
add_action('admin_init', 'templify_core_register_settings');




// This function is needed to register settings
function register_templify_core_full_access_settings() {
    register_setting('templify_core_full_access_settings_group', 'templify_core_full_access_settings');
}



// Hook your functions
add_action('admin_init', 'register_templify_core_full_access_settings');



// Admin Notices
add_action('admin_notices', 'templify_core_admin_notices');

function templify_core_admin_notices() {
    // Check if Easy Digital Downloads is installed and activated
    if (!templify_core_check_edd()) {
        // Check if the transient is set
        $show_notice = get_transient('templify_core_edd_notice');

        if ($show_notice) {
            $plugin_name = 'Easy Digital Downloads'; // Change this to the required plugin name

            ?>
            <div class="error">
                <p><?php
                    printf(
                        esc_html__('%s requires %s. Please install and activate it to use %s.', 'templify-core'),
                        'Templify Core',
                        '<strong>' . esc_html($plugin_name) . '</strong>',
                        'Templify Core'
                    );
                ?></p>
                <?php
                $plugin_url = admin_url('plugin-install.php?s=' . urlencode(strtolower($plugin_name)) . '&tab=search&type=term');
                printf('<p><a href="%s" class="button button-primary">%s</a></p>', esc_url($plugin_url), esc_html__('Install ' . $plugin_name, 'templify-core'));
                ?>
            </div>
            <?php

            // Remove the transient after showing the notice
            delete_transient('templify_core_edd_notice');
        }
    }
}

// Deactivation hook
function templify_core_deactivate() {
    // Deactivation code here
}
register_deactivation_hook(__FILE__, 'templify_core_deactivate');



