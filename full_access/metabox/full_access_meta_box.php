<?php 


function edd_full_access_enabled_for_download( $download_id ) {
return 'full_access' === edd_get_download_type( $download_id );
}

function edd_full_access_download_meta_box_save( $post_id ) {
global $post;

// Verify nonce.
if ( ! isset( $_POST['edd_download_full_access_meta_box_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['edd_download_full_access_meta_box_nonce'] ) ), basename( __FILE__ ) ) ) {
    return;
}

// Check for auto save / bulk edit.
if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || isset( $_REQUEST['bulk_edit'] ) ) {
    return;
}

if ( isset( $_POST['post_type'] ) && 'download' !== $_POST['post_type'] ) {
    return;
}

if ( ! current_user_can( 'edit_product', $post_id ) ) {
    return;
}

if ( false !== wp_is_post_revision( $post_id ) ) {
    return;
}

if ( isset( $_POST['_edd_product_type'] ) && 'full_access' === $_POST['_edd_product_type'] ) {

    // This is submitted as an array and is sanitized in the switch statement below, which is why we have phpcs:ignore for the sanitization.
    $new_full_access_meta = isset( $_POST['edd_full_access_meta'] ) ? wp_unslash( $_POST['edd_full_access_meta'] ) : false; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

    if ( $new_full_access_meta ) {

        $sanitized_values = array();

        // Sanitize Values.
        foreach ( $new_full_access_meta as $meta_key => $meta_value ) {

            switch ( $meta_key ) {
                case 'full_access_duration_number':
                    if ( is_numeric( $meta_value ) ) {
                        $sanitized_values['full_access_duration_number'] = $meta_value;
                    }

                    break;
                case 'full_access_duration_unit':
                    $sanitized_values['full_access_duration_unit'] = sanitize_text_field( $meta_value );

                    break;
                case 'download_limit':
                    if ( is_numeric( $meta_value ) ) {
                        $sanitized_values['full_access_download_limit'] = $meta_value;
                    }

                    break;
                case 'download_limit_time_period':
                    $sanitized_values['full_access_download_limit_time_period'] = sanitize_text_field( $meta_value );

                    break;
                case 'full_access_categories':
                    $full_access_categories = array();

                    foreach ( $meta_value as $full_access_category ) {
                        if ( is_numeric( $full_access_category ) || 'all' === $full_access_category ) {
                            $full_access_categories[] = $full_access_category;
                        }
                    }

                    $sanitized_values['full_access_categories'] = $full_access_categories;

                    break;
                case 'number_of_price_ids':
                    if ( is_numeric( $meta_value ) ) {
                        $sanitized_values['full_access_number_of_price_ids'] = $meta_value;
                    }

                    break;
                case 'included_price_ids':
                    $included_price_ids = array();

                    foreach ( $meta_value as $included_price_id ) {
                        if ( is_numeric( $included_price_id ) ) {
                            $included_price_ids[] = $included_price_id;
                        }
                    }

                    $sanitized_values['full_access_included_price_ids'] = $included_price_ids;

                    break;
            }
        }

        update_post_meta( $post_id, '_edd_full_access_settings', $sanitized_values );
    }

    // Check the receipt data as well.
    $new_full_access_receipt_meta = isset( $_POST['edd_full_access_receipt_meta'] ) ? wp_unslash( $_POST['edd_full_access_receipt_meta'] ) : false; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

    if ( $new_full_access_receipt_meta ) {

        $sanitized_values = array();

        // Sanitize Values.
        foreach ( $new_full_access_receipt_meta as $meta_key => $meta_value ) {

            switch ( $meta_key ) {
                case 'show_link':
                    $sanitized_values['show_link'] = sanitize_text_field( $meta_value );

                    break;
                case 'link_message':
                    $sanitized_values['link_message'] = sanitize_text_field( $meta_value );

                    break;
                case 'link_url':
                    $sanitized_values['link_url'] = esc_url( $meta_value );

                    break;
            }
        }

        update_post_meta( $post_id, '_edd_full_access_receipt_settings', $sanitized_values );
    }
}

// Run the function to rebuild the list of All Access products.
edd_full_access_get_full_access_downloads( true );
}
add_action( 'save_post', 'edd_full_access_download_meta_box_save' );
?>
