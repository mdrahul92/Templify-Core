<?php
/**
 * Scripts
 *
 * @package     EDD\PluginName\Scripts
 * @since       1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load admin scripts
 *
 * @since       1.0.0
 * @return      void
 */
function edd_all_access_admin_post_meta_scripts() {

	$all_access_js_vars_admin = array(
		'first_variation_string'           => __( 'st Price Variation from each product', 'templify-full-access' ),
		'second_variation_string'          => __( 'nd Price Variation from each product', 'templify-full-access' ),
		'third_variation_string'           => __( 'rd Price Variation from each product', 'templify-full-access' ),
		'variation_string'                 => __( 'th Price Variation from each product', 'templify-full-access' ),
		'manage_all_access_expire_warning' => __( 'NOTICE: This will make this All Access Pass expire. This can not be undone. In order to re-activate, the customer must re-purchase. Are you sure you want to do this?', 'templify-full-access' ),
		'sync_with_recurring'              => __( 'Sync with Recurring expiration', 'templify-full-access' ),
		'sync_with_license'                => __( 'Sync with License expiration', 'templify-full-access' ),
	);

	// If we are viewing a single All Access Pass right now, indicate which one by localizing the values. No nonce required here as it's not a form submission or saving action, but just a page load.
	if ( isset( $_GET['page'] ) && 'edd-all-access-pass' === $_GET['page'] ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( isset( $_GET['payment_id'] ) && isset( $_GET['download_id'] ) && isset( $_GET['price_id'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended

			// Sanitize the url values.
			$payment_id  = intval( $_GET['payment_id'] ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$download_id = intval( $_GET['download_id'] ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$price_id    = intval( $_GET['price_id'] ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended

			// Set up an All Access Pass so we can pass its details to the JS.
			if ( ! empty( $payment_id ) && ! empty( $download_id ) ) {
				$all_accesss_pass                               = edd_all_access_get_pass( $payment_id, $download_id, $price_id );
				$all_access_js_vars_admin['all_access_pass_id'] = $all_accesss_pass->id;
			}
		}
	}

	wp_enqueue_script( 'edd_all_access_admin_js', plugin_dir_url( __FILE__ ) . 'assets/js/admin/build/edd-aa-admin.js', array( 'jquery' ), EDD_ALL_ACCESS_VER, true );
	wp_localize_script( 'edd_all_access_admin_js', 'edd_all_access_vars', $all_access_js_vars_admin );
	wp_enqueue_style( 'edd_all_access_admin_css', plugin_dir_url( __FILE__ ) . 'assets/css/admin/build/admin.css', array(), EDD_ALL_ACCESS_VER );
}
add_action( 'admin_enqueue_scripts', 'edd_all_access_admin_post_meta_scripts', 100 );


/**
 * Load frontend scripts
 *
 * @since       1.0.0
 * @return      void
 */
function edd_all_access_scripts() {

	wp_enqueue_style( 'edd_all_access_css', plugin_dir_url( __FILE__ ) . 'assets/css/frontend/build/styles.css', array(), EDD_ALL_ACCESS_VER );

	// Only enqueue All Access scripts if the user is logged in.
	if ( ! is_user_logged_in() ) {
		return;
	}

	wp_enqueue_script( 'edd_all_access_js', plugin_dir_url( __FILE__ ) . 'assets/js/frontend/build/edd-aa-frontend.js', array( 'jquery' ), EDD_ALL_ACCESS_VER, true );
	wp_localize_script(
		'edd_all_access_js',
		'edd_all_access_vars',
		array(
			'ajaxurl'          => admin_url( 'admin-ajax.php' ),
			'ajax_nonce_value' => wp_create_nonce( 'edd-all-access-nonce-action-name' ),
			'loading_text'     => __( 'Loading', 'templify-full-access' ),
		)
	);

}
add_action( 'wp_enqueue_scripts', 'edd_all_access_scripts' );
