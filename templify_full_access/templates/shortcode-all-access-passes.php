<?php
/**
 *  EDD Template File for [edd_all_access_passes] shortcode
 *
 * @description: Place this template file within your theme directory under /my-theme/edd_templates/ - For more information see: https://easydigitaldownloads.com/videos/template-files/
 *
 * @copyright   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0.0
 */

//For logged in users only
if ( is_user_logged_in() ):

	//Get the customer
	$customer = new EDD_Customer( get_current_user_id(), true );

	// Get the All Access passes saved to this customer meta
	$customer_all_access_passes = edd_all_access_get_customer_pass_objects( $customer );

	$at_least_one_pass_to_show = false;

	if ( $customer_all_access_passes ) :
		?>

		<table id="edd_user_history">
			<thead>
			<tr class="edd_purchase_row">
				<th><?php _e( 'Product', 'edd-all-access' ); ?></th>
				<th><?php _e( 'Status', 'edd-all-access' ); ?></th>
				<th><?php _e( 'Start Date', 'edd-all-access' ); ?></th>
				<th><?php _e( 'Expiration Date', 'edd-all-access' ); ?></th>
				<th><?php _e( 'Actions', 'edd-all-access' ); ?></th>
			</tr>
			</thead>
			<?php
			foreach ( $customer_all_access_passes as $all_access_pass ) {

				if ( empty( $all_access_pass->payment_id ) || empty( $all_access_pass->download_id ) || ! isset( $all_access_pass->price_id ) ) {
					continue;
				}


				if ( 'invalid' === $all_access_pass->status ) {
					continue;
				}

				$at_least_one_pass_to_show = true;

					?>
					<tr>
						<td>
							<span class="edd_all_access_pass_name"><?php echo get_the_title( $all_access_pass->download_id ); ?></span>
						</td>
						<td>
							<span class="edd_all_access_pass_status"><?php echo edd_all_access_get_status_label( $all_access_pass->status ); ?></span>
						</td>
						<td>
							<span class="edd_all_access_pass_start_date"><?php echo edd_all_access_visible_date( 'M d, Y', $all_access_pass->start_time ); ?></span>
						</td>
						<td>
							<span class="edd_all_access_pass_expiration_date"><?php echo $all_access_pass->expiration_time == 'never' ? __( 'Never Expires', 'edd-all-access' ) : edd_all_access_visible_date( 'M d, Y', $all_access_pass->expiration_time ); ?></span>
						</td>
						<td>
							<span class="edd_all_access_pass_actions"><?php

							// Create the URL which will link to this single pass's details.
							$view_single_aa_pass_url = add_query_arg( array(
								'action' => 'view_all_access_pass',
								'payment_id' => $all_access_pass->payment_id,
								'download_id' => $all_access_pass->download_id,
								'price_id' => $all_access_pass->price_id,
							) );

							echo '<a href="' . esc_url( $view_single_aa_pass_url ) . '">' . __( 'View Full Details', 'edd-all-access' ) . '</a>';

							?></span>
						</td>
					</tr>
				<?php } ?>
		</table>

		<?php endif; //end if customer has all access passes

		// If there was not at least 1 valid pass to show
		if ( ! $at_least_one_pass_to_show ){ ?>
			<p class="edd-no-purchases"><?php _e( 'You have not made any of this type of purchase.', 'edd-all-access' ); ?></p>
		<?php } ?>

<?php endif; //end is_user_logged_in() ?>
