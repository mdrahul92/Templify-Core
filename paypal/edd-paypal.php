<?php



/**
 * Plugin Name: Easy Digital Downloads - PayPal Commerce Pro
 * Plugin URI: https://easydigitaldownloads.com/downloads/paypal-commerce-pro/
 * Description: Enables additional payment methods through PayPal.
 * Version: 1.0.3
 * Author: Easy Digital Downloads
 * Author URI: https://easydigitaldownloads.com/
 *
 * @package   edd-paypal-commerce-pro
 * @copyright Copyright (c) 2021, Easy Digital Downloads
 * @license   GPL2+
 */


namespace EDD_PayPal_Commerce_Pro;

define( 'EDD_PAYPAL_PRO_VERSION', '1.0.3' );
define( 'EDD_PAYPAL_PRO_FILE', __FILE__ );
define( 'EDD_PAYPAL_PRO_DIR', dirname( EDD_PAYPAL_PRO_FILE ) );
define( 'EDD_PAYPAL_PRO_URL', plugin_dir_url( EDD_PAYPAL_PRO_FILE ) );

add_action( 'plugins_loaded', function () {
	if ( class_exists( '\\EDD\\Extensions\\ExtensionRegistry' ) ) {
		add_action( 'edd_extension_license_init', function( \EDD\Extensions\ExtensionRegistry $registry ) {
			$registry->addExtension( EDD_PAYPAL_PRO_FILE, 'PayPal Commerce Pro Payment Gateway', 1687512, EDD_PAYPAL_PRO_VERSION );
		} );
	} elseif ( class_exists( 'EDD_License' ) ) {
		new \EDD_License( EDD_PAYPAL_PRO_FILE, 'PayPal Commerce Pro Payment Gateway', EDD_PAYPAL_PRO_VERSION, 'Easy Digital Downloads', null, null, 1687512 );
	}

	require_once dirname( __FILE__ ) . '/includes/upgrades.php';

	// if ( class_exists( '\\EDD\\Gateways\\PayPal\\API' ) ) {
		
	//}

} );
