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

    if (templify_core_check_edd() ) {
     
    } else {
        // Deactivate the plugin
        deactivate_plugins(plugin_basename(__FILE__));
        // Set a transient to show the notice
        set_transient('templify_core_edd_notice', true, 5 * MINUTE_IN_SECONDS);
    }
}


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

//all addon define constant names
define( 'EDD_PAYPAL_PRO_VERSION', '1.0.3' );
define( 'EDD_PAYPAL_PRO_FILE', __FILE__ .'/paypal' );
define( 'EDD_PAYPAL_PRO_DIR', dirname( EDD_PAYPAL_PRO_FILE ) );
define( 'EDD_PAYPAL_PRO_URL', plugin_dir_url( EDD_PAYPAL_PRO_FILE ) );
define( 'EDD_RECURRING_STORE_API_URL', 'https://easydigitaldownloads.com' );
define( 'EDD_RECURRING_PRODUCT_NAME', 'Recurring Payments' );


if ( ! defined( 'EDD_RECURRING_PLUGIN_FILE' ) ) {
	define( 'EDD_RECURRING_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'EDD_RECURRING_VERSION' ) ) {
	define( 'EDD_RECURRING_VERSION', '2.11.11.1' );
}

define( 'EDD_RECURRING_MINIMUM_PHP', '5.6' );


function templify_full_access_register_download_type( $types ) {
	$types['full_access'] = __( 'Full Access', 'templify-full-access' );

	return $types;
}
add_filter( 'edd_download_types', 'templify_full_access_register_download_type' );

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


//full access required file
require_once plugin_dir_path( __FILE__ ) . '/full_access/functions/helper_function.php';
require_once plugin_dir_path( __FILE__ ) . '/full_access/metabox/full_access_meta_box.php';
require_once plugin_dir_path( __FILE__ ) . '/full_access/metabox/price_meta_box.php';
require_once plugin_dir_path( __FILE__ ) . '/full_access/reports/reports.php';
require_once plugin_dir_path( __FILE__ ) . '/full_access/reports/class-edd-fa-download-popularity-table.php';
require_once plugin_dir_path( __FILE__ ) . '/full_access/functions/shortcodes.php';
require_once plugin_dir_path( __FILE__ ) . '/full_access/functions/settings.php';
require_once plugin_dir_path( __FILE__ ) . '/full_access/functions/discount-codes.php';
require_once plugin_dir_path( __FILE__ ) . '/full_access/customers/customers.php';

//stripe required file
require_once plugin_dir_path( __FILE__ ) . 'stripe/includes/functions.php';

// paypal commerce required file
require_once plugin_dir_path( __FILE__ ) . '/paypal/upgrades.php';
require_once plugin_dir_path( __FILE__ ) . '/paypal/main.php';
require_once plugin_dir_path( __FILE__ ) . '/paypal/admin/settings.php';
require_once plugin_dir_path( __FILE__ ).  '/paypal/checkout.php';
require_once plugin_dir_path( __FILE__ ) . '/paypal/script.php';

//recurring payment required file
require_once plugin_dir_path( __FILE__ ) . '/recurring_payment/edd_recurring.php';
require_once plugin_dir_path( __FILE__ ) . '/software_licensing/software-licenses.php';
