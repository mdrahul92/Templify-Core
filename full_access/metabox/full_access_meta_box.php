<?php 


function edd_full_access_enabled_for_download( $download_id ) {
return 'full_access' === edd_get_download_type( $download_id );
}

function edd_full_access_render_full_access_meta_box(){

global $post;
$enabled = edd_full_access_enabled_for_download( $post->ID );

?>

<input type="hidden" name="edd_download_full_access_meta_box_nonce" value="<?php echo esc_attr( wp_create_nonce( basename( __FILE__ ) ) ); ?>" />
<table class="form-table">
    <?php 
$enabled = edd_full_access_enabled_for_download( $post->ID );
//$product = new \EDD\AllAccess\Models\AllAccessProduct( $post->ID );
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
