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
    wp_enqueue_script('templify-core-admin-script', plugins_url('assets/admin/js/script.js', __FILE__), array('jquery'), '1.0', true);
}
add_action('admin_enqueue_scripts', 'templify_core_admin_enqueue_scripts');

// Activation Hook
register_activation_hook(__FILE__, 'templify_core_activation');

function templify_core_activation() {
    //$result = templify_core_check_edd();
    if (templify_core_check_edd() ) {
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
		

            <?php }
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
		'desc' => __( 'Instead of seeing the above error message, if you\'d like the customer to be redirected to a specific page when they attempt to download a product using an expired Full Access License, enter that URL here.', 'templify-full-access' ),
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
		'desc' => __( 'Instead of seeing the above error message, if you\'d like the customer to be redirected to a specific page when they attempt to download a product using an expired Full Access License, enter that URL here.', 'templify-full-access' ),
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
		'desc' => __( 'Instead of seeing the above error message, if you\'d like the customer to be redirected to a specific page when they attempt to download a product using an expired Full Access License, enter that URL here.', 'templify-full-access' ),
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
		'desc'          => __( 'If a customer has an Full Access License but that pass doesn\'t provide access to a specific variable price, should it be hidden? For example, if the Full Access License gives access to a "Large" version and thus you want to hide the "Medium" and "Small" versions, choose "Yes" and they will be hidden from those Full Access License holders. Note they will still appear to people without an Full Access License where they normally would.', 'templify-full-access' ),
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

    echo '<input type="text" class=" large-text" id="templify_core_full_access_settings[full_access_expired_redirect]" name="templify_core_full_access_settings[full_access_expired_redirect]" value="">';
    echo "<br>".$args['desc'];
}


function templify_core_full_access_settings_category_not_included_header_callback($args){

}


function templify_core_full_access_category_not_included_text_callback($args){
    $option = get_option('templify_core_full_access_settings');

    echo '<textarea class="" cols="50" rows="5" id="templify_core_full_access_settings[full_access_category_not_included_text]" name="templify_core_full_access_settings[full_access_category_not_included_text]">Your account does not have access to products in this category.</textarea>';
    echo "<br>".$args['desc'];

}

function templify_core_full_access_category_not_included_redirect_callback($args){
    $option = get_option('templify_core_full_access_settings');

    echo '<input type="text" class=" large-text" id="templify_core_full_access_settings[full_access_category_not_included_redirect]" name="templify_core_full_access_settings[full_access_category_not_included_redirect]" value="">';
    echo "<br>".$args['desc'];
}


function templify_core_full_access_settings_price_id_not_included_header_callback($args){

}


function templify_core_full_access_price_id_not_included_text_callback($args){
    $option = get_option('templify_core_full_access_settings');

    echo '<textarea class="" cols="50" rows="5" id="templify_core_full_access_settings[full_access_price_id_not_included_text]" name="templify_core_full_access_settings[full_access_price_id_not_included_text]">Your account does not have access to this product variation.</textarea>';
    echo "<br>".$args['desc'];
}

function templify_core_full_access_price_id_not_included_redirect_callback($args){
    $option = get_option('templify_core_full_access_settings');

    echo '<input type="text" class=" large-text" id="templify_core_full_access_settings[full_access_price_id_not_included_redirect]" name="templify_core_full_access_settings[full_access_price_id_not_included_redirect]" value="">';
    echo "<br>".$args['desc'];
}

function templify_core_full_access_download_limit_reached_header_callback($args){

}

function templify_core_full_access_download_limit_reached_text_callback($args){
	$option = get_option('templify_core_full_access_settings');

    echo '<textarea class="" cols="50" rows="5" id="templify_core_full_access_settings[full_access_download_limit_reached_text]" name="templify_core_full_access_settings[full_access_download_limit_reached_text]">Sorry. Youve hit the maximum number of downloads allowed for your Full Access account.</textarea>';
    echo "<br>".$args['desc'];
}

function templify_core_full_access_download_limit_reached_redirect_callback($args){
	$option = get_option('templify_core_full_access_settings');

    echo '<input type="text" class=" large-text" id="templify_core_full_access_settings[full_access_download_limit_reached_redirect]" name="templify_core_full_access_settings[full_access_download_limit_reached_redirect]" value="">';
    echo "<br>".$args['desc'];
}

function templify_core_full_access_modify_download_now_form_callback($args){

}

function templify_core_full_access_hide_non_relevant_variable_prices_callback($args){
	$option = get_option('templify_core_full_access_settings');

    echo '<div class="edd-check-wrapper"><input name="templify_core_full_access_settings[full_access_hide_non_relevant_variable_prices]" id="templify_core_full_access_settings[full_access_hide_non_relevant_variable_prices][no]" class="" type="radio" value="no" checked="checked">&nbsp;<label for="templify_core_full_access_settings[full_access_hide_non_relevant_variable_prices][no]">No. I want to show all variable prices to customers with an Full Access License - even if they dont get access to them.</label></div><div class="edd-check-wrapper"><input name="templify_core_full_access_settings[full_access_hide_non_relevant_variable_prices]" id="templify_core_full_access_settings[full_access_hide_non_relevant_variable_prices][yes]" class="" type="radio" value="yes">&nbsp;<label for="templify_core_full_access_settings[full_access_hide_non_relevant_variable_prices][yes]">Yes. Hide non-relevant variable prices from customers with an Full Access License.</label></div>';
    echo "<br>".$args['desc'];
}

function templify_core_full_access_purchase_form_display_header_callback($args){

}


function templify_core_full_access_purchase_form_display_callback($args){
	$option = get_option('templify_core_full_access_settings');

    echo '<div class="edd-check-wrapper"><input name="templify_core_full_access_settings[full_access_purchase_form_display]" id="templify_core_full_access_settings[full_access_purchase_form_display][normal-mode]" class="" type="radio" value="normal-mode" checked="checked">&nbsp;<label for="templify_core_full_access_settings[full_access_purchase_form_display][normal-mode]">1. Show normal "Add To Cart" buttons only.</label></div><div class="edd-check-wrapper"><input name="templify_core_full_access_settings[full_access_purchase_form_display]" id="templify_core_full_access_settings[full_access_purchase_form_display][aa-only-mode]" class="" type="radio" value="aa-only-mode">&nbsp;<label for="templify_core_full_access_settings[full_access_purchase_form_display][aa-only-mode]">2. Show "Buy Full Access" and "Login" buttons instead of "Add To Cart" (if the product is included in an Full Access License).</label></div><div class="edd-check-wrapper"><input name="templify_core_full_access_settings[full_access_purchase_form_display]" id="templify_core_full_access_settings[full_access_purchase_form_display][normal-plus-aa-mode]" class="" type="radio" value="normal-plus-aa-mode">&nbsp;<label for="templify_core_full_access_settings[full_access_purchase_form_display][normal-plus-aa-mode]">3. Show both normal "Add To Cart" buttons and "Buy Full Access" and "Login" buttons below.</label></div>';
    echo "<br>".$args['desc'];
}

function templify_core_full_access_show_buy_instructions_callback($args){
	$option = get_option('templify_core_full_access_settings');

    echo '<div class="edd-check-wrapper"><input name="templify_core_full_access_settings[full_access_show_buy_instructions]" id="templify_core_full_access_settings[full_access_show_buy_instructions][show]" class="" type="radio" value="show" checked="checked">&nbsp;<label for="templify_core_full_access_settings[full_access_show_buy_instructions][show]">Yes. Show the instructional text above the "Buy Full Access" button.</label></div><div class="edd-check-wrapper"><input name="templify_core_full_access_settings[full_access_show_buy_instructions]" id="templify_core_full_access_settings[full_access_show_buy_instructions][hide]" class="" type="radio" value="hide">&nbsp;<label for="templify_core_full_access_settings[full_access_show_buy_instructions][hide]">No. Do not show the instructional text above the "Buy Full Access" button.</label></div>';
    echo "<br>".$args['desc'];
}

function templify_core_full_access_buy_instructions_callback($args){
	$option = get_option('templify_core_full_access_settings');

    echo '<textarea class="" cols="50" rows="5" id="templify_core_full_access_settings[full_access_buy_instructions]" name="templify_core_full_access_settings[full_access_buy_instructions]">To get access, purchase an Full Access License here.</textarea>';
    echo "<br>".$args['desc'];
}

function templify_core_full_access_show_login_instructions_callback($args){
	$option = get_option('templify_core_full_access_settings');

    echo '<div class="edd-check-wrapper"><input name="templify_core_full_access_settings[full_access_show_login_instructions]" id="templify_core_full_access_settings[full_access_show_login_instructions][show]" class="" type="radio" value="show" checked="checked">&nbsp;<label for="templify_core_full_access_settings[full_access_show_login_instructions][show]">Yes. Show the instructional text before the "Log In" button.</label></div><div class="edd-check-wrapper"><input name="templify_core_full_access_settings[full_access_show_login_instructions]" id="templify_core_full_access_settings[full_access_show_login_instructions][hide]" class="" type="radio" value="hide">&nbsp;<label for="templify_core_full_access_settings[full_access_show_login_instructions][hide]">No. Do not show the instructional text before the "Log In" button.</label></div>';
    echo "<br>".$args['desc'];
}
function templify_core_full_access_login_instructions_callback($args){
	$option = get_option('templify_core_full_access_settings');
    echo '<textarea class="" cols="50" rows="5" id="templify_core_full_access_settings[full_access_login_instructions]" name="templify_core_full_access_settings[full_access_login_instructions]">Already purchased?</textarea>';
    echo "<br>".$args['desc'];
}
function templify_core_full_access_replace_aa_btns_with_custom_btn_callback($args){
	$option = get_option('templify_core_full_access_settings');
    echo '<div class="edd-check-wrapper"><input name="templify_core_full_access_settings[full_access_replace_aa_btns_with_custom_btn]" id="templify_core_full_access_settings[full_access_replace_aa_btns_with_custom_btn][normal_aa_btns]" class="" type="radio" value="normal_aa_btns">&nbsp;<label for="templify_core_full_access_settings[full_access_replace_aa_btns_with_custom_btn][normal_aa_btns]">No. Show the "Buy Full Access" buttons for all relevant Full Access products.</label></div><div class="edd-check-wrapper"><input name="templify_core_full_access_settings[full_access_replace_aa_btns_with_custom_btn]" id="templify_core_full_access_settings[full_access_replace_aa_btns_with_custom_btn][custom_btn]" class="" type="radio" value="custom_btn">&nbsp;<label for="templify_core_full_access_settings[full_access_replace_aa_btns_with_custom_btn][custom_btn]">Yes. Replace the "Buy Full Access" buttons with a single, custom URL button.</label></div>';
    echo "<br>".$args['desc'];
}
function templify_core_full_access_custom_url_btn_url_callback($args){
	$option = get_option('templify_core_full_access_settings');
    echo '<input type="text" class=" large-text" id="templify_core_full_access_settings[full_access_custom_url_btn_url]" name="templify_core_full_access_settings[full_access_custom_url_btn_url]" value="">';
    echo "<br>".$args['desc'];
}
function templify_core_full_access_custom_url_btn_text_callback($args){
	$option = get_option('templify_core_full_access_settings');
    echo '<input type="text" class=" large-text" id="templify_core_full_access_settings[full_access_custom_url_btn_text]" name="templify_core_full_access_settings[full_access_custom_url_btn_text]" value="">';
    echo "<br>".$args['desc'];
}
// Hook your functions
add_action('admin_init', 'templify_core_register_settings');

function templify_full_access_register_download_type( $types ) {
	$types['full_access'] = __( 'Full Access', 'templify-full-access' );

	return $types;
}
add_filter( 'edd_download_types', 'templify_full_access_register_download_type' );


require_once plugin_dir_path( __FILE__ ) . '/full_access/functions/helper_function.php';
require_once plugin_dir_path( __FILE__ ) . '/full_access/metabox/full_access_meta_box.php';
require_once plugin_dir_path( __FILE__ ) . '/full_access/metabox/price_meta_box.php';
require_once plugin_dir_path( __FILE__ ) . '/full_access/reports/reports.php';
require_once plugin_dir_path( __FILE__ ) . '/full_access/reports/class-edd-fa-download-popularity-table.php';
require_once plugin_dir_path( __FILE__ ) . '/full_access/functions/shortcodes.php';

function edd_full_access_add_meta_box() {

	if ( current_user_can( 'manage_shop_settings' ) ) {
		add_meta_box( 'edd_downloads_full_access', __( 'Full Access', 'templify-full-access' ), 'edd_all_access_render_full_access_meta_box', 'download', 'normal', 'default' );
	}
}
add_action( 'add_meta_boxes', 'edd_full_access_add_meta_box' );


function edd_all_access_render_full_access_meta_box(){

	global $post;
	
	
	?>
	<input type="hidden" name="edd_download_full_access_meta_box_nonce" value="<?php echo esc_attr( wp_create_nonce( basename( __FILE__ ) ) ); ?>" />
	<table class="form-table">
		<?php 
$enabled = edd_full_access_enabled_for_download( $post->ID );
	
	?>
	<tr class="edd_full_access_categories_row edd_full_access_row">
		<td class="edd_field_type_text" colspan="2">
			<p>
				<strong><?php echo esc_html( __( 'Full Access To:', 'templify-full-access' ) ); ?>
					<span alt="f223" class="edd-help-tip dashicons dashicons-editor-help" title="<?php echo wp_kses_post( __( '<strong>Limit by category</strong>: You can choose which products customers can download with this Full Access License. For example, if you want to sell an Full Access License to just a category called Photos, choose that category here. Note that the category must already exist for it to show up here. You can make product categories under Downloads > Categories.', 'templify-full-access' ) ); ?>"></span>
				</strong>
			</p>
			<label for="edd_full_access_meta_full_access_categories">
				<?php echo esc_html( __( 'To which product categories does the customer get "Full Access"', 'templify-full-access' ) ); ?>
			</label>
			<br />
			<?php
					$categories = get_terms( 'download_category', apply_filters( 'edd_category_dropdown', array() ) );
					$options    = array(
						'all' => __( 'All Products', 'templify-full-access' ),
					);
	
					foreach ( $categories as $category ) {
						$options[ absint( $category->term_id ) ] = esc_html( $category->name );
					}
	
					echo EDD()->html->select(
						array(
							'options'          => $options,
							'name'             => 'edd_full_access_meta[all_access_categories][]',
							'selected'         => '',
							'id'               => 'edd_full_access_meta_all_access_categories',
							'class'            => 'edd_full_access_meta_all_access_categories',
							'chosen'           => true,
							'placeholder'      => __( 'Type to search Categories', 'templify-full-access' ),
							'multiple'         => true,
							'show_option_all'  => false,
							'show_option_none' => false,
							'data'             => array( 'search-type' => 'no_ajax' ),
						)
					);
					?>
		</td>
	</tr>
	
	
	<tr class="edd_full_access_row">
		<td class="edd_field_type_text" colspan="2">
			<p><strong><?php echo esc_html( __( '"Full Access" Duration:', 'templify-full-access' ) ); ?></strong>
			<span alt="f223" class="edd-help-tip dashicons dashicons-editor-help" title="<?php echo wp_kses_post( __( '<strong>What is Full Access duration?</strong>: You can set an expiration date for this Full Access license. Once a customer\'s Full Access License expires, they can no longer download products using that license. If you want to make this renewable (like an ongoing membership), you will want to use the EDD Recurring extension so that this Full Access License is automatically repurchased by the customer once it expires.', 'templify-full-access' ) ); ?>"></span>
			</p>
			<label for="edd_full_access_meta_full_access_duration_unit"><?php echo esc_html( __( 'How long should "Full Access" last?', 'templify-full-access' ) ); ?></label><br />
			<input
				type="number"
				class="small-text"
				placeholder="1"
				id="edd_full_access_meta_full_access_duration_number"
				name="edd_full_access_meta[full_access_duration_number]"
				value=""
				min="1"
				style="display:none;"
			/>
			<select name="edd_full_access_meta[full_access_duration_unit]" id="edd_full_access_meta_full_access_duration_unit">
			<?php
					foreach ( edd_full_access_get_duration_unit_options() as $time_period_slug => $output_string ) {
						?>
						<option value="<?php echo esc_attr( $time_period_slug ); ?>" ><?php echo esc_html( $output_string ); ?></option>
						<?php
					}
					?>
			</select>
		</td>
	</tr>
	
	<tr class="edd_full_access_row">
			<td class="edd_field_type_text" colspan="2">
				<p><strong><?php esc_html_e( 'Download Limit:', 'templify-full-access' ); ?></strong></p>
				<label for="edd_full_access_download_limit"><?php echo wp_kses_post( __( 'How many downloads should the customer get? Leave blank or enter "0" for unlimited. Note: If a customer\'s account is expired, they won\'t be able to download - even if they have not hit this limit yet.', 'templify-full-access' ) ); ?></label><br />
				<input type="number" class="small-text" name="edd_full_access_meta[download_limit]" id="edd_full_access_download_limit" value="" min="0" />&nbsp;
				<span
					id="edd_full_access_unlimited_download_limit_note"
					
						style="display:none;"
					
				>
				<?php esc_html_e( '(Unlimited downloads per day)', 'templify-full-access' ); ?>
				</span>
				<select
					name="edd_full_access_meta[download_limit_time_period]"
					id="edd_full_access_meta_download_limit_time_period"
	
						style="display:none;"
				
				>
					<?php
					foreach ( edd_full_access_get_download_limit_periods() as $time_period_slug => $output_string ) {
						?>
						<option value="<?php echo esc_attr( $time_period_slug ); ?>" >
							<?php echo esc_html( str_replace( 'X', "", $output_string ) ); ?>
						</option>
						<?php
					}
					?>
				</select>
			</td>
		</tr>
	
	
		<?php
			// Full Access Price Variations - How many?
			?>
			<tr class="edd_full_access_number_of_price_ids_row edd_full_access_row">
				<td class="edd_field_type_text" colspan="2">
					<p><strong><?php echo esc_html( __( 'Total Price Variations (Optional):', 'templify-full-access' ) ); ?></strong></p>
					<label for="edd_full_access_number_of_price_ids"><?php echo esc_html( __( 'How many price variations are there? Leave blank or enter "0" to include all price variations.', 'templify-full-access' ) ); ?></label><br />
					<input type="number" class="small-text" name="edd_full_access_meta[number_of_price_ids]" id="edd_full_access_number_of_price_ids" value="" min="0" />&nbsp;
					<p
						id="edd_full_access_included_price_ids_note"
						<?php if ( empty( $product->download_limit ) ) : ?>
							style="display:none;"
						<?php endif; ?>
					>
						<?php esc_html_e( 'Because this is set to 0, all price variations are included.', 'templify-full-access' ); ?>
					</p>
				</td>
		</tr>
		<?php
			// Full Access Price Variations - Which are included?.
		?>
		<tr style="display:none;"
	
			class="edd_full_access_included_price_ids_row"
		>
			<td class="edd_field_type_text" colspan="2">
				<p><strong><?php echo esc_html( __( 'Included Price Variations:', 'templify-full-access' ) ); ?></strong></p>
				<?php echo esc_html( __( 'Which price variations should be included in this Full Access?', 'templify-full-access' ) ); ?>
				<ul id="edd_full_access_included_price_ids">
					
				</ul>
			</td>
		</tr>
		<?php
		// Full Access Receipt options.
		?>
		<tr class="edd_full_access_row">
			<td class="edd_field_type_text" colspan="2">
				<p><strong><?php echo esc_html( __( 'Receipts: Show link to Full Access?:', 'templify-full-access' ) ); ?></strong></p>
				<label for="edd_full_access_receipt_meta_show_link"><?php echo esc_html( __( 'Would you like to output a custom link in the receipts your customers receive directing them to use their Full Access License? Note: For email Receipts, you must be using the', 'templify-full-access' ) ); ?>
					<a href="http://docs.easydigitaldownloads.com/article/864-email-settings" target="_blank">{download_list}</a>
					<?php echo esc_html( __( 'email tag.', 'templify-full-access' ) ); ?>
				</label><br />
	
				<select name="edd_full_access_receipt_meta[show_link]" id="edd_full_access_receipt_meta_show_link">
					<option value="show_link" ><?php esc_html_e( 'Show link in receipt', 'templify-full-access' ); ?></option>
					<option value="hide_link" ><?php esc_html_e( 'Hide link in receipt', 'templify-full-access' ); ?></option>
				</select>
			<td>
		</tr>
		<?php
		// Full Access Receipt Link Message.
		?>
		<tr class="edd_full_access_row">
			<td class="edd_field_type_text" colspan="2">
				<p>
					<strong><?php echo esc_html( __( 'Receipts: Full Access Link Message:', 'templify-full-access' ) ); ?></strong>
				</p>
				<label for="edd_full_access_receipt_meta_link_message"><?php echo esc_html( __( 'What should the link in the receipt say to the user?', 'templify-full-access' ) ); ?></label>
				<p>
					<textarea name="edd_full_access_receipt_meta[link_message]" id="edd_full_access_receipt_meta_link_message" style="width:100%;"></textarea>
				</p>
			<td>
		</tr>
		<?php
		// Full Access Receipt Link URL.
		?>
		<tr class="edd_full_access_row">
			<td class="edd_field_type_text" colspan="2">
				<p><strong><?php echo esc_html( __( 'Receipts: Link URL:', 'templify-full-access' ) ); ?></strong></p>
				<label for="edd_full_access_receipt_meta_link_url"><?php echo esc_html( __( 'Which URL should the customer be directed to in the receipt? If you want to build your own custom page, ', 'templify-full-access' ) ); ?>
					<a href="http://docs.easydigitaldownloads.com/article/1829-all-access-creating-all-access-products#creating-a-custom-page-of-products-the-customer-can-download-via-all-access" target="_blank">
						<?php echo esc_html( __( 'learn how in this document.', 'templify-full-access' ) ); ?>
					</a>
				</label>
				<p>
					<input style="width:100%;" type="url" name="edd_full_access_receipt_meta[link_url]" id="edd_full_access_receipt_meta_link_url" value="" />
				</p>
			<td>
		</tr>
						</table>
	<?php 
	}
	
	
function register_templify_core_full_access_settings() {
    register_setting('templify_core_full_access_settings_group', 'templify_core_full_access_settings');
}

add_action('admin_init', 'register_templify_core_full_access_settings');


function edd_full_access_update_download_type( $type, $download_id ) {
	if ( 'full_access' === $type ) {
		return $type;
	}

	// If the download doesn't yet have a type, but does have AA settings, it's probably an AA download.
	if ( ( empty( $type ) || 'default' === $type ) && get_post_meta( $download_id, '_edd_full_access_settings', true ) ) {
		// This request will trigger a debugging notice and update the post meta.
		if ( get_post_meta( $download_id, '_edd_full_access_enabled', true ) ) {
			update_post_meta( $download_id, '_edd_product_type', 'full_access' );
			delete_post_meta( $download_id, '_edd_full_access_enabled' );

			return 'full_access';
		}
	}

	return $type;
}
add_filter( 'edd_get_download_type', 'edd_full_access_update_download_type', 20, 2 );



// Admin Notics
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



