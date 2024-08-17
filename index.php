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
    // Check if Easy Digital Downloads is active
    if (!templify_core_check_edd()) {
        // Set a transient to show the notice
        set_transient('templify_core_edd_notice', true, 5 * MINUTE_IN_SECONDS);
        // Deactivate the plugin
        deactivate_plugins(plugin_basename(__FILE__));
        return; // Make sure to return after deactivating to prevent further execution
    }

    // Initialize default settings if not already set
    if (false === get_option('templify_core_plugin_settings')) {
        $default_settings = array(
            'all_access_enabled' => 0,
            'paypal_enabled' => 0,
            'recurring_payments_enabled' => 0,
            'software_licensing_enabled' => 0,
        );
        update_option('templify_core_plugin_settings', $default_settings);
    }
}

// Add menu item in admin dashboard
function templify_core_add_settings_menu() {
    add_menu_page('Templify Core Settings', 'Templify Settings', 'manage_options', 'templify-core-settings', 'templify_core_settings_page');
}
add_action('admin_menu', 'templify_core_add_settings_menu');

// Define settings and fields
function templify_core_settings_page() {
    ?>
    <div class="wrap">
        <h2>Templify Core Settings</h2>
        <form method="post" action="options.php">
            <?php settings_fields('templify_core_plugin_settings'); ?>
            <?php do_settings_sections('templify_core_settings'); ?>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', 'templify_core_plugin_settings');

function templify_core_plugin_settings() {
    register_setting('templify_core_plugin_settings', 'templify_core_plugin_settings', 'templify_core_plugin_sanitize');

    add_settings_section('templify_core_settings', '', 'templify_core_settings_callback', 'templify_core_settings');

    add_settings_field('all_access_enabled', 'Enable Full Access', 'all_access_enabled_callback', 'templify_core_settings', 'templify_core_settings');
    add_settings_field('paypal_enabled', 'Enable PayPal', 'paypal_enabled_callback', 'templify_core_settings', 'templify_core_settings');
    add_settings_field('recurring_payments_enabled', 'Enable Recurring Payments', 'recurring_payments_enabled_callback', 'templify_core_settings', 'templify_core_settings');
    add_settings_field('software_licensing_enabled', 'Enable Software Licensing', 'software_licensing_enabled_callback', 'templify_core_settings', 'templify_core_settings');
}

function templify_core_settings_callback() {
    echo '<p>Enable or disable addons.</p>';
}

function all_access_enabled_callback() {
    $options = get_option('templify_core_plugin_settings');
    $all_access_enabled = isset($options['all_access_enabled']) ? $options['all_access_enabled'] : '';
    echo '<input type="checkbox" id="all_access_enabled" name="templify_core_plugin_settings[all_access_enabled]" value="1" ' . checked(1, $all_access_enabled, false) . ' />';
}

function paypal_enabled_callback() {
    $options = get_option('templify_core_plugin_settings');
    $paypal_enabled = isset($options['paypal_enabled']) ? $options['paypal_enabled'] : '';
    echo '<input type="checkbox" id="paypal_enabled" name="templify_core_plugin_settings[paypal_enabled]" value="1" ' . checked(1, $paypal_enabled, false) . ' />';
}

function recurring_payments_enabled_callback() {
    $options = get_option('templify_core_plugin_settings');
    $recurring_payments_enabled = isset($options['recurring_payments_enabled']) ? $options['recurring_payments_enabled'] : '';
    echo '<input type="checkbox" id="recurring_payments_enabled" name="templify_core_plugin_settings[recurring_payments_enabled]" value="1" ' . checked(1, $recurring_payments_enabled, false) . ' />';
}

function software_licensing_enabled_callback() {
    $options = get_option('templify_core_plugin_settings');
    $software_licensing_enabled = isset($options['software_licensing_enabled']) ? $options['software_licensing_enabled'] : '';
    echo '<input type="checkbox" id="software_licensing_enabled" name="templify_core_plugin_settings[software_licensing_enabled]" value="1" ' . checked(1, $software_licensing_enabled, false) . ' />';
}


function templify_core_plugin_sanitize($input) {
    $output = array();
    
    // Check if $input is not null
    if (!is_null($input)) {
        foreach ($input as $key => $value) {
            $output[$key] = sanitize_text_field($value);
        }
    }
    
    return $output;
}



// Plugin path.
define( 'TEMPLIFY_CORE_DIR', plugin_dir_path( __FILE__ ) );


define( 'EDD_RECURRING_STORE_API_URL', 'https://easydigitaldownloads.com' );
define( 'EDD_RECURRING_PRODUCT_NAME', 'Recurring Payments' );



if ( ! defined( 'EDD_RECURRING_PLUGIN_FILE' ) ) {
	define( 'EDD_RECURRING_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'EDD_RECURRING_VERSION' ) ) {
	define( 'EDD_RECURRING_VERSION', '2.11.11.1' );
}

define( 'EDD_RECURRING_MINIMUM_PHP', '5.6' );

// Include respective addon files if enabled
function include_addon_files() {
    $options = get_option('templify_core_plugin_settings');
    //var_dump($options); 
    if (isset($options['all_access_enabled']) && $options['all_access_enabled'] == "1") {
        $file_path = plugin_dir_path( __FILE__ ) . '/templify_full_access/templify_full_access.php';
        if (file_exists($file_path)) {
            require_once $file_path;
            //echo "ok";
        } else {
            error_log("Templify Core: Full Access addon file not found at: $file_path");
        }
    }
    if (isset($options['paypal_enabled']) && $options['paypal_enabled'] == "1") {
        $file_path = plugin_dir_path( __FILE__ ) . '/paypal/edd-paypal.php';
<<<<<<< HEAD
        $file_path1 =  plugin_dir_path( __FILE__ ) . '/paypal/includes/payment-method-filters.php';
        $file_path2 =  plugin_dir_path( __FILE__ ) . '/paypal/includes/advanced/admin/settings.php';
        $file_path3 =  plugin_dir_path( __FILE__ ) . '/paypal/includes/advanced/checkout-actions.php';
        $file_path4 =  plugin_dir_path( __FILE__ ) . '/paypal/includes/advanced/functions.php';
        $file_path5 =  plugin_dir_path( __FILE__ ) . '/paypal/includes/advanced/scripts.php';
=======
       $file_path1 =  plugin_dir_path( __FILE__ ) . '/paypal/includes/payment-method-filters.php';
       $file_path2 =  plugin_dir_path( __FILE__ ) . '/paypal/includes/advanced/admin/settings.php';
       $file_path3 =  plugin_dir_path( __FILE__ ) . '/paypal/includes/advanced/checkout-actions.php';
       $file_path4 =  plugin_dir_path( __FILE__ ) . '/paypal/includes/advanced/functions.php';
       $file_path5 =  plugin_dir_path( __FILE__ ) . '/paypal/includes/advanced/scripts.php';
>>>>>>> origin/master
        if (file_exists($file_path)) {
            require_once $file_path;
            require_once $file_path1;
            require_once $file_path2;
            require_once $file_path3;
            require_once $file_path4;
            require_once $file_path5;
        } else {
            error_log("Templify Core: PayPal addon file not found at: $file_path");
        }
    }
    if (isset($options['recurring_payments_enabled']) && $options['recurring_payments_enabled'] == "1") {
        $file_path = plugin_dir_path( __FILE__ ) . '/recurring_payment/edd_recurring.php';
        if (file_exists($file_path)) {
            require_once $file_path;
        } else {
            error_log("Templify Core: Recurring Payments addon file not found at: $file_path");
        }
    }
    if (isset($options['software_licensing_enabled']) && $options['software_licensing_enabled'] == "1") {
        $file_path =    plugin_dir_path( __FILE__ ) . '/software_licensing/software-licenses.php';
        if (file_exists($file_path)) {
            require_once $file_path;
        } else {
            error_log("Templify Core: Software Licensing addon file not found at: $file_path");
        }
    }


}
add_action('plugins_loaded', 'include_addon_files');



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




<<<<<<< HEAD
add_action('rest_api_init', 'register_custom_api_endpoint');

function register_custom_api_endpoint() {
    register_rest_route('custom/v1', '/activate_buyer_license', array(
        'methods' => 'POST',
        'callback' => 'activate_buyer_license_callback',
    ));


    register_rest_route('custom/v1', '/activate_purchaser_license', array(
        'methods' => 'POST',
        'callback' => 'activate_purchaser_license_callback',
    ));

    register_rest_route('custom/v1', '/fetch_library_by_license', array(
        'methods' => 'GET',
        'callback' => 'fetch_library_by_license_callback',
    ));
}

function activate_buyer_license_callback($request) {
    $user_id = $request->get_param('user_id');
    $license_key = $request->get_param('license_key');
    $license_url = $request->get_query('SELECT  * FROM  tb_edd_license WHERE license_key = %s');

global $wpdb;
    

    // Query to check if the license key exists
    $table_name = $wpdb->prefix . 'edd_licenses';
    $license = $wpdb->get_var($wpdb->prepare(
        "SELECT license_key FROM $table_name WHERE license_key = %s",
        $license_key
    ));

    // Format the response based on whether the license key was found
    if ($license) {
        $response = array(
            'status' => 200,
            'data' => 'License key is activated successfully.'
        );
    } else {
        $response = array(
            'status' => 404,
            'data' => 'License key not found'
        );
    }

    // Return the JSON response
    return new WP_REST_Response($response, 200);
}




function activate_purchaser_license_callback($request) {
    $user_id = $request->get_param('user_id');
    $license_key = $request->get_param('license_key');
    
    global $wpdb;

    // Query to check if the license key exists
    $table_name = $wpdb->prefix . 'edd_licenses';
    $license = $wpdb->get_var($wpdb->prepare(
        "SELECT license_key FROM $table_name WHERE license_key = %s",
        $license_key
    ));

    // Format the response based on whether the license key was found
    if ($license) {
        $response = array(
            'status' => 200,
            'data' => 'License key is activated successfully.'
        );
    } else {
        $response = array(
            'status' => 404,
            'data' => 'License key not found'
        );
    }

    // Return the JSON response
    return new WP_REST_Response($response, 200);
}


function fetch_library_by_license_callback($request) {
    $library_url  = $request->get_param('library_url');
    $license_key = $request->get_param('license_key');
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'edd_licenses';
    $license = $wpdb->get_var($wpdb->prepare(
        "SELECT license_key FROM $table_name WHERE license_key = %s",
        $license_key
    ));


    $edd_path = 'easy-digital-downloads/easy-digital-downloads.php';




        $new_download = new EDD_Download(27);

        $id = $new_download->get_ID();

        $files = $new_download->get_files();

        
            $response = array(
                'status' => 200,
                'data' => 'Download File Successfully !',
                'id' => $id,
                'File' => $files
            );
    
      // Return the JSON response
    return new WP_REST_Response($response, 200);

}

=======
>>>>>>> origin/master
require_once plugin_dir_path( __FILE__ ) . 'stripe/includes/functions.php';
