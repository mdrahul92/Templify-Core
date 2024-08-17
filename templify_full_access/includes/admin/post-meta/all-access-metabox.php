<?php
/**
 * Functions that deal with outputting/saving the metabox called "Full Access".
 *
 * @package     EDD\EDDAllAccess\Post Meta Functions
 * @since       1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add the Full Access metabox to the product settings in wp-admin.
 *
 * @since       1.0.0
 * @return      void
 */
function edd_all_access_add_meta_box() {

	if ( current_user_can( 'manage_shop_settings' ) ) {
		add_meta_box( 'edd_downloads_all_access', __( 'Full Access', 'edd-all-access' ), 'edd_all_access_render_all_access_meta_box', 'download', 'normal', 'default' );
	}
}
add_action( 'add_meta_boxes', 'edd_all_access_add_meta_box' );

/**
 * Render the download information meta box
 *
 * @since       1.0.0
 * @return      void
 */
function edd_all_access_render_all_access_meta_box() {
	global $post;

	// Use nonce for verification.
	?>
	<input type="hidden" name="edd_download_all_access_meta_box_nonce" value="<?php echo esc_attr( wp_create_nonce( basename( __FILE__ ) ) ); ?>" />
	<table class="form-table">
		<?php
		
		$enabled = edd_all_access_enabled_for_download( $post->ID );
		$product = new \EDD\AllAccess\Models\AllAccessProduct( $post->ID );

		?>
		<tr class="edd_all_access_categories_row edd_all_access_row">
			<td class="edd_field_type_text" colspan="2">
				<p>
					<strong><?php echo esc_html( __( 'Full Access To:', 'edd-all-access' ) ); ?>
						<span alt="f223" class="edd-help-tip dashicons dashicons-editor-help" title="<?php echo wp_kses_post( __( '<strong>Limit by category</strong>: You can choose which products customers can download with this Full Access pass. For example, if you want to sell an Full Access pass to just a category called Photos, choose that category here. Note that the category must already exist for it to show up here. You can make product categories under Downloads > Categories.', 'edd-all-access' ) ); ?>"></span>
					</strong>
				</p>
				<label for="edd_all_access_meta_all_access_categories">
					<?php echo esc_html( __( 'To which product categories does the customer get "Full Access"', 'edd-all-access' ) ); ?>
				</label>
				<br />
				<?php
				$categories = get_terms( 'download_category', apply_filters( 'edd_category_dropdown', array() ) );
				$options    = array(
					'all' => __( 'All Products', 'edd-all-access' ),
				);

				foreach ( $categories as $category ) {
					$options[ absint( $category->term_id ) ] = esc_html( $category->name );
				}

				echo EDD()->html->select(
					array(
						'options'          => $options,
						'name'             => 'edd_all_access_meta[all_access_categories][]',
						'selected'         => $product->categories ?: 'all',
						'id'               => 'edd_all_access_meta_all_access_categories',
						'class'            => 'edd_all_access_meta_all_access_categories',
						'chosen'           => true,
						'placeholder'      => __( 'Type to search Categories', 'edd-all-access' ),
						'multiple'         => true,
						'show_option_all'  => false,
						'show_option_none' => false,
						'data'             => array( 'search-type' => 'no_ajax' ),
					)
				);
				?>
			</td>
		</tr>


	
		<tr class="edd_all_access_row">
			<td class="edd_field_type_text" colspan="2">
				<p><strong><?php echo esc_html( __( '"Full Access" Duration:', 'edd-all-access' ) ); ?></strong>
				<span alt="f223" class="edd-help-tip dashicons dashicons-editor-help" title="<?php echo wp_kses_post( __( '<strong>What is Full Access duration?</strong>: You can set an expiration date for this Full Access pass. Once a customer\'s Full Access pass expires, they can no longer download products using that pass. If you want to make this renewable (like an ongoing membership), you will want to use the EDD Recurring extension so that this Full Access pass is automatically repurchased by the customer once it expires.', 'edd-all-access' ) ); ?>"></span>
				</p>
				<label for="edd_all_access_meta_all_access_duration_unit"><?php echo esc_html( __( 'How long should "Full Access" last?', 'edd-all-access' ) ); ?></label><br />
				<input
					type="number"
					class="small-text"
					placeholder="1"
					id="edd_all_access_meta_all_access_duration_number"
					name="edd_all_access_meta[all_access_duration_number]"
					value="<?php echo esc_attr( $product->duration ); ?>"
					min="1"
					<?php if ( empty( $product->duration_unit ) || 'never' === $product->duration_unit ) : ?>
						style="display:none;"
					<?php endif; ?>
				/>
				<select name="edd_all_access_meta[all_access_duration_unit]" id="edd_all_access_meta_all_access_duration_unit">
					<?php
					foreach ( edd_all_access_get_duration_unit_options() as $time_period_slug => $output_string ) {
						?>
						<option value="<?php echo esc_attr( $time_period_slug ); ?>" <?php echo esc_attr( selected( $time_period_slug, $product->duration_unit, false ) ); ?>><?php echo esc_html( $output_string ); ?></option>
						<?php
					}
					?>
				</select>
			</td>
		</tr>
		<tr class="edd_all_access_row">
			<td class="edd_field_type_text" colspan="2">
				<p><strong><?php esc_html_e( 'Download Limit:', 'edd-all-access' ); ?></strong></p>
				<label for="edd_all_access_download_limit"><?php echo wp_kses_post( __( 'How many downloads should the customer get? Leave blank or enter "0" for unlimited. Note: If a customer\'s account is expired, they won\'t be able to download - even if they have not hit this limit yet.', 'edd-all-access' ) ); ?></label><br />
				<input type="number" class="small-text" name="edd_all_access_meta[download_limit]" id="edd_all_access_download_limit" value="<?php echo esc_attr( $product->download_limit ); ?>" min="0" />&nbsp;
				<span
					id="edd_all_access_unlimited_download_limit_note"
					<?php if ( ! empty( $product->download_limit ) ) : ?>
						style="display:none;"
					<?php endif; ?>
				>
					<?php esc_html_e( '(Unlimited downloads per day)', 'edd-all-access' ); ?>
				</span>
				<select
					name="edd_all_access_meta[download_limit_time_period]"
					id="edd_all_access_meta_download_limit_time_period"
					<?php if ( empty( $product->download_limit ) ) : ?>
						style="display:none;"
					<?php endif; ?>
				>
					<?php
					foreach ( edd_all_access_get_download_limit_periods() as $time_period_slug => $output_string ) {
						?>
						<option value="<?php echo esc_attr( $time_period_slug ); ?>" <?php echo esc_attr( selected( $time_period_slug, $product->download_limit_period, false ) ); ?>>
							<?php echo esc_html( str_replace( 'X', $product->download_limit, $output_string ) ); ?>
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
			<tr class="edd_all_access_number_of_price_ids_row edd_all_access_row">
				<td class="edd_field_type_text" colspan="2">
					<p><strong><?php echo esc_html( __( 'Total Price Variations (Optional):', 'edd-all-access' ) ); ?></strong></p>
					<label for="edd_all_access_number_of_price_ids"><?php echo esc_html( __( 'How many price variations are there? Leave blank or enter "0" to include all price variations.', 'edd-all-access' ) ); ?></label><br />
					<input type="number" class="small-text" name="edd_all_access_meta[number_of_price_ids]" id="edd_all_access_number_of_price_ids" value="<?php echo esc_attr( $product->number_price_ids ); ?>" min="0" />&nbsp;
					<p
						id="edd_all_access_included_price_ids_note"
						<?php if ( empty( $product->download_limit ) ) : ?>
							style="display:none;"
						<?php endif; ?>
					>
						<?php esc_html_e( 'Because this is set to 0, all price variations are included.', 'edd-all-access' ); ?>
					</p>
				</td>
		</tr>
		<?php
			// Full Access Price Variations - Which are included?.
		?>
		<tr
			<?php if ( empty( $product->number_price_ids ) ) : ?>
				style="display:none;"
			<?php endif; ?>
			class="edd_all_access_included_price_ids_row"
		>
			<td class="edd_field_type_text" colspan="2">
				<p><strong><?php echo esc_html( __( 'Included Price Variations:', 'edd-all-access' ) ); ?></strong></p>
				<?php echo esc_html( __( 'Which price variations should be included in this Full Access?', 'edd-all-access' ) ); ?>
				<ul id="edd_all_access_included_price_ids">
					<?php
					for ( $price_id = 1; $price_id <= $product->number_price_ids; $price_id++ ) {

						$variation_string = __( 'th Price Variation from each product', 'edd-all-access' );
						$variation_string = 1 === $price_id ? __( 'st Price Variation from each product', 'edd-all-access' ) : $variation_string;
						$variation_string = 2 === $price_id ? __( 'nd Price Variation from each product', 'edd-all-access' ) : $variation_string;
						$variation_string = 3 === $price_id ? __( 'rd Price Variation from each product', 'edd-all-access' ) : $variation_string;

						?>
						<li class="edd_all_access_included_price_id_li <?php echo esc_html( $price_id ); ?>">
							<label><input type="checkbox" name="edd_all_access_meta[included_price_ids][]" class="edd_all_access_included_price_id" value="<?php echo esc_attr( $price_id ); ?>" <?php echo esc_attr( ( in_array( $price_id, ( $product->included_price_ids ?: array() ), true ) ? ' checked ' : '' ) ); ?>/>
							<?php echo esc_html( $price_id . $variation_string ); ?></label>
						</li>
						<?php
					}
					?>
				</ul>
			</td>
		</tr>
		<?php
		// Full Access Receipt options.
		?>
		<tr class="edd_all_access_row">
			<td class="edd_field_type_text" colspan="2">
				<p><strong><?php echo esc_html( __( 'Receipts: Show link to Full Access?:', 'edd-all-access' ) ); ?></strong></p>
				<label for="edd_all_access_receipt_meta_show_link"><?php echo esc_html( __( 'Would you like to output a custom link in the receipts your customers receive directing them to use their Full Access Pass? Note: For email Receipts, you must be using the', 'edd-all-access' ) ); ?>
					<a href="http://docs.easydigitaldownloads.com/article/864-email-settings" target="_blank">{download_list}</a>
					<?php echo esc_html( __( 'email tag.', 'edd-all-access' ) ); ?>
				</label><br />

				<select name="edd_all_access_receipt_meta[show_link]" id="edd_all_access_receipt_meta_show_link">
					<option value="show_link" <?php selected( $product->show_link_in_receipt ); ?>><?php esc_html_e( 'Show link in receipt', 'edd-all-access' ); ?></option>
					<option value="hide_link" <?php selected( ! $product->show_link_in_receipt ); ?>><?php esc_html_e( 'Hide link in receipt', 'edd-all-access' ); ?></option>
				</select>
			<td>
		</tr>
		<?php
		// Full Access Receipt Link Message.
		?>
		<tr class="edd_all_access_row">
			<td class="edd_field_type_text" colspan="2">
				<p>
					<strong><?php echo esc_html( __( 'Receipts: Full Access Link Message:', 'edd-all-access' ) ); ?></strong>
				</p>
				<label for="edd_all_access_receipt_meta_link_message"><?php echo esc_html( __( 'What should the link in the receipt say to the user?', 'edd-all-access' ) ); ?></label>
				<p>
					<textarea name="edd_all_access_receipt_meta[link_message]" id="edd_all_access_receipt_meta_link_message" style="width:100%;"><?php echo esc_html( $product->receipt_link_message ); ?></textarea>
				</p>
			<td>
		</tr>
		<?php
		// Full Access Receipt Link URL.
		?>
		<tr class="edd_all_access_row">
			<td class="edd_field_type_text" colspan="2">
				<p><strong><?php echo esc_html( __( 'Receipts: Link URL:', 'edd-all-access' ) ); ?></strong></p>
				<label for="edd_all_access_receipt_meta_link_url"><?php echo esc_html( __( 'Which URL should the customer be directed to in the receipt? If you want to build your own custom page, ', 'edd-all-access' ) ); ?>
					<a href="http://docs.easydigitaldownloads.com/article/1829-all-access-creating-all-access-products#creating-a-custom-page-of-products-the-customer-can-download-via-all-access" target="_blank">
						<?php echo esc_html( __( 'learn how in this document.', 'edd-all-access' ) ); ?>
					</a>
				</label>
				<p>
					<input style="width:100%;" type="url" name="edd_all_access_receipt_meta[link_url]" id="edd_all_access_receipt_meta_link_url" value="<?php echo esc_attr( $product->receipt_link_url ); ?>" />
				</p>
			<td>
		</tr>
	</table>
	<?php
}

/**
 * Save data from the Full Access metabox
 *
 * @access      public
 * @since       1.0.0
 * @param       string $post_id The ID of the post being saved.
 * @return      void
 */
function edd_all_access_download_meta_box_save( $post_id ) {
	global $post;

	// Verify nonce.
	if ( ! isset( $_POST['edd_download_all_access_meta_box_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['edd_download_all_access_meta_box_nonce'] ) ), basename( __FILE__ ) ) ) {
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

	if ( isset( $_POST['_edd_product_type'] ) && 'all_access' === $_POST['_edd_product_type'] ) {

		// This is submitted as an array and is sanitized in the switch statement below, which is why we have phpcs:ignore for the sanitization.
		$new_all_access_meta = isset( $_POST['edd_all_access_meta'] ) ? wp_unslash( $_POST['edd_all_access_meta'] ) : false; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( $new_all_access_meta ) {

			$sanitized_values = array();

			// Sanitize Values.
			foreach ( $new_all_access_meta as $meta_key => $meta_value ) {

				switch ( $meta_key ) {
					case 'all_access_duration_number':
						if ( is_numeric( $meta_value ) ) {
							$sanitized_values['all_access_duration_number'] = $meta_value;
						}

						break;
					case 'all_access_duration_unit':
						$sanitized_values['all_access_duration_unit'] = sanitize_text_field( $meta_value );

						break;
					case 'download_limit':
						if ( is_numeric( $meta_value ) ) {
							$sanitized_values['all_access_download_limit'] = $meta_value;
						}

						break;
					case 'download_limit_time_period':
						$sanitized_values['all_access_download_limit_time_period'] = sanitize_text_field( $meta_value );

						break;
					case 'all_access_categories':
						$all_access_categories = array();

						foreach ( $meta_value as $all_access_category ) {
							if ( is_numeric( $all_access_category ) || 'all' === $all_access_category ) {
								$all_access_categories[] = $all_access_category;
							}
						}

						$sanitized_values['all_access_categories'] = $all_access_categories;

						break;
					case 'number_of_price_ids':
						if ( is_numeric( $meta_value ) ) {
							$sanitized_values['all_access_number_of_price_ids'] = $meta_value;
						}

						break;
					case 'included_price_ids':
						$included_price_ids = array();

						foreach ( $meta_value as $included_price_id ) {
							if ( is_numeric( $included_price_id ) ) {
								$included_price_ids[] = $included_price_id;
							}
						}

						$sanitized_values['all_access_included_price_ids'] = $included_price_ids;

						break;
				}
			}

			update_post_meta( $post_id, '_edd_all_access_settings', $sanitized_values );
		}

		// Check the receipt data as well.
		$new_all_access_receipt_meta = isset( $_POST['edd_all_access_receipt_meta'] ) ? wp_unslash( $_POST['edd_all_access_receipt_meta'] ) : false; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( $new_all_access_receipt_meta ) {

			$sanitized_values = array();

			// Sanitize Values.
			foreach ( $new_all_access_receipt_meta as $meta_key => $meta_value ) {

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

			update_post_meta( $post_id, '_edd_all_access_receipt_settings', $sanitized_values );
		}
	}

	// Run the function to rebuild the list of Full Access products.
	edd_all_access_get_all_access_downloads( true );
}
add_action( 'save_post', 'edd_all_access_download_meta_box_save' );

/**
 * Register the Full Access download type.
 *
 * @since  1.2.5
 * @param  array $types The existing download types.
 * @return array        The updated download types.
 */
function edd_all_access_register_download_type( $types ) {
	$types['all_access'] = __( 'Full Access', 'edd-all-access' );

	return $types;
}
add_filter( 'edd_download_types', 'edd_all_access_register_download_type' );

// In EDD 3.2, the product type field was moved to the price fields.
if ( has_action( 'edd_meta_box_files_fields', 'edd_render_product_type_field' ) ) {
	remove_action( 'edd_meta_box_files_fields', 'edd_render_product_type_field', 10 );
	add_action( 'edd_meta_box_price_fields', 'edd_render_product_type_field', 5 );
}
