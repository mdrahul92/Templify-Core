<?php
/**
 * Functions that deal with options this plugin adds to the "Prices" metabox for EDD.
 *
 * @package     EDD\EDDAllAccess\Post Meta Functions
 * @since       1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Option to exclude this product from All Access
 *
 * @since     1.0.0
 * @param int $post_id The ID of the EDD Download being managed.
 * @return    void
 */
function edd_all_access_excluded_product( $post_id ) {

	$all_access_exclude = (bool) get_post_meta( $post_id, '_edd_all_access_exclude', true );

	?>
	<p
		id="edd_all_access_exclude_single_option"
		<?php if ( in_array( edd_get_download_type( $post_id ), array( 'bundle', 'all_access' ), true ) ) : ?>
			style="display:none;"
		<?php endif; ?>
	>
		<label for="edd_all_access_exclude">
			<input type="checkbox" name="_edd_all_access_exclude" id="edd_all_access_exclude" value="1" <?php checked( 1, $all_access_exclude ); ?> />
			<?php
			echo esc_html( apply_filters( 'edd_all_access_exclude_toggle_text', __( 'Exclude this product from any All Access passes.', 'edd-all-access' ) ) );

			// Use nonce for verification.
			?>
			<input type="hidden" name="edd_all_access_price_meta_box_nonce" value="<?php echo esc_html( wp_create_nonce( basename( __FILE__ ) ) ); ?>" />
		</label>
	</p>
	<?php
}
add_action( 'edd_after_price_field', 'edd_all_access_excluded_product' );

/**
 * Save All Access data from the prices metabox
 *
 * @access      public
 * @since       1.0.0
 * @param       string $post_id The ID of the post being saved.
 * @return      void
 */
function edd_all_access_price_meta_box_save( $post_id ) {

	// Verify nonce.
	if ( ! isset( $_POST['edd_all_access_price_meta_box_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['edd_all_access_price_meta_box_nonce'] ) ), basename( __FILE__ ) ) ) {
		return;
	}

	// Check for auto save / bulk edit.
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || isset( $_REQUEST['bulk_edit'] ) ) {
		return;
	}

	if ( ! isset( $_POST['post_type'] ) || ( isset( $_POST['post_type'] ) && 'download' !== $_POST['post_type'] ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_product', $post_id ) ) {
		return $post_id;
	}

	if ( isset( $_POST['_edd_all_access_exclude'] ) ) {
		update_post_meta( $post_id, '_edd_all_access_exclude', true );
	} else {
		delete_post_meta( $post_id, '_edd_all_access_exclude' );
	}
}
add_action( 'save_post', 'edd_all_access_price_meta_box_save' );

/**
 * Add "Exclude from All Access" to the variable pricing options
 *
 * @access      public
 * @since       1.0.4
 * @param       int   $download_id The ID of the EDD Product being managed.
 * @param       int   $price_id    The Variable Price ID being managed.
 * @param       array $args        Arguments for the variable price row passed in from the edd_download_price_option_row action hook.
 * @return      void
 */
function edd_all_access_exclude_price_id_option( $download_id, $price_id, $args ) {

	$prices = edd_get_variable_prices( $download_id );

	$price_excluded = ! empty( $prices[ $price_id ]['excluded_price'] );

	?>
	<div
		class="edd-custom-price-option-section edd-all-access-exclude-price-id"
		<?php if ( in_array( edd_get_download_type( $download_id ), array( 'bundle', 'all_access' ), true ) ) : ?>
			style="display:none;"
		<?php endif; ?>
	>
		<span class="edd-custom-price-option-section-title"><?php esc_html_e( 'All Access Settings', 'edd-all-access' ); ?></span>
		<div class="edd-custom-price-option-section-content edd-form-row">
			<div class="edd-form-group is-column">
				<div class="edd-form-group__control">
					<input <?php checked( true, $price_excluded, true ); ?> type="checkbox" class="edd-form-group__input" name="edd_variable_prices[<?php echo esc_attr( $price_id ); ?>][excluded_price]" id="edd_variable_prices[<?php echo esc_attr( $price_id ); ?>][excluded_price]" value="1" />
					<label for="edd_variable_prices[<?php echo esc_attr( $price_id ); ?>][excluded_price]"><?php esc_html_e( 'Exclude from All Access?', 'edd-all-access' ); ?></label><span alt="f223" class="edd-help-tip dashicons dashicons-editor-help" title="<?php printf( '<strong>%s</strong> %s', esc_html__( 'Exclude from All Access:', 'edd-all-access' ), esc_html__( 'Check this setting to exclude this price from every All Access pass. If checked, no one with any All Access pass will be able to download this price\'s files using All Access.', 'edd-all-access' ) ); ?>"></span>
				</div>
			</div>
		</div>
	</div>
	<?php
}
add_action( 'edd_download_price_option_row', 'edd_all_access_exclude_price_id_option', 10, 3 );
