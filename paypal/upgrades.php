<?php
add_action( 'admin_init', function() {

if ( ! function_exists( 'edd_get_option' ) ) {
    return;
}

/*
 * Move license data to new option, after product name change.
 */
$license_key = edd_get_option( 'edd_paypal_pro_license_key' );
if ( $license_key ) {
    edd_update_option( 'edd_paypal_commerce_pro_payment_gateway_license_key', sanitize_text_field( $license_key ) );
    edd_delete_option( 'edd_paypal_pro_license_key' );

    $license_status = get_option( 'edd_paypal_pro_license_active' );
    if ( $license_status ) {
        update_option( 'edd_paypal_commerce_pro_payment_gateway_license_active', $license_status );
        delete_option( 'edd_paypal_pro_license_active' );
    }
}
} );
