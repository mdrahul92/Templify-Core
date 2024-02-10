<?php 
function edd_full_access_excluded_product( $post_id ) {

$all_access_exclude = (bool) get_post_meta( $post_id, '_edd_all_access_exclude', true );

?>
<p
    id="edd_full_access_exclude_single_option"
    <?php if ( in_array( edd_get_download_type( $post_id ), array( 'bundle', 'full_access' ), true ) ) : ?>
        style="display:none;"
    <?php endif; ?>
>
    <label for="edd_all_access_exclude">
        <input type="checkbox" name="_edd_all_access_exclude" id="edd_all_access_exclude" value="1" <?php checked( 1, $all_access_exclude ); ?> />
        <?php
        echo esc_html( apply_filters( 'edd_all_access_exclude_toggle_text', __( 'Exclude this product from any Full Access Licences.', 'templify-full-access' ) ) );

        // Use nonce for verification.
        ?>
        <input type="hidden" name="edd_all_access_price_meta_box_nonce" value="<?php echo esc_html( wp_create_nonce( basename( __FILE__ ) ) ); ?>" />
    </label>
</p>
<?php
}
add_action( 'edd_after_price_field', 'edd_full_access_excluded_product' );



function edd_all_access_exclude_price_id_option( $download_id, $price_id, $args ) {

$prices = edd_get_variable_prices( $download_id );

$price_excluded = ! empty( $prices[ $price_id ]['excluded_price'] );

?>
<div
    class="edd-custom-price-option-section edd-full-access-exclude-price-id"
    <?php if ( in_array( edd_get_download_type( $download_id ), array( 'bundle', 'full_access' ), true ) ) : ?>
        style="display:none;"
    <?php endif; ?>
>
    <span class="edd-custom-price-option-section-title"><?php esc_html_e( 'Full Access Settings', 'templify-full-access' ); ?></span>
    <div class="edd-custom-price-option-section-content edd-form-row">
        <div class="edd-form-group is-column">
            <div class="edd-form-group__control">
                <input <?php checked( true, $price_excluded, true ); ?> type="checkbox" class="edd-form-group__input" name="edd_variable_prices[<?php echo esc_attr( $price_id ); ?>][excluded_price]" id="edd_variable_prices[<?php echo esc_attr( $price_id ); ?>][excluded_price]" value="1" />
                <label for="edd_variable_prices[<?php echo esc_attr( $price_id ); ?>][excluded_price]"><?php esc_html_e( 'Exclude from Full Access?', 'templify-full-access' ); ?></label><span alt="f223" class="edd-help-tip dashicons dashicons-editor-help" title="<?php printf( '<strong>%s</strong> %s', esc_html__( 'Exclude from Full Access:', 'templify-full-access' ), esc_html__( 'Check this setting to exclude this price from every Full Access license. If checked, no one with any Full Access license will be able to download this price\'s files using Full Access.', 'templify-full-access' ) ); ?>"></span>
            </div>
        </div>
    </div>
</div>
<?php
}
add_action( 'edd_download_price_option_row', 'edd_all_access_exclude_price_id_option', 10, 3 );

