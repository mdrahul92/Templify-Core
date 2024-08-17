<?php
/**
 * Render the All Access Pass Single View in wp-admin
 *
 * @package     EDD All Access
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the All Access Pass Single View
 *
 * @access      public
 * @since       1.0.0
 * @return      void
 */
function edd_all_access_pass_page() {

	// If payment_id, download_id, and price_id exist in the URL, load the single All Access pass we are dealing with.
	if ( ! empty( $_GET['payment_id'] ) && ! empty( $_GET['download_id'] ) && isset( $_GET['price_id'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended

		edd_all_access_display_pass_details( intval( $_GET['payment_id'] ), intval( $_GET['download_id'] ), intval( $_GET['price_id'] ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return;

	}

	echo esc_html( __( 'No All Access Pass defined', 'edd-all-access' ) );
}

/**
 * Updates the EDD settings page title on the All Access page.
 *
 * @since 1.2.4
 * @param string $page_title     The page title.
 * @param string $current_page   The current page.
 * @param bool   $is_single_view Whether the current page is a single view.
 */
add_filter( 'edd_settings_page_title', function( $page_title, $current_page, $is_single_view ) {
	if ( 'edd-all-access-pass' === $current_page ) {
		return __( 'All Access', 'edd-all-access' );
	}

	return $page_title;
}, 10, 3 );

/**
 * All Access Pass Details
 * Outputs the subscriber details
 *
 * @param int $payment_id  The ID of the EDD payment where this All Access Pass was purchased.
 * @param int $download_id The ID of the product where this All Access Pass originated.
 * @param int $price_id    The ID of the price variation where this All Access Pass originated. Use 0 for a non variable priced product.
 * @since       1.0.0
 */
function edd_all_access_display_pass_details( $payment_id, $download_id, $price_id ) {

	$all_access_pass = edd_all_access_get_pass( $payment_id, $download_id, $price_id );

	if ( ! current_user_can( 'view_shop_reports' ) ) {
		edd_set_error( 'edd-no-access', __( 'You are not permitted to view this data.', 'edd-all-access' ) );
	}

	if ( 'invalid' === $all_access_pass->status ) {
		edd_set_error( 'edd-invalid-all-access-pass', __( 'Invalid All Access Pass Provided.', 'edd-all-access' ) );
	}

	?>
	<div class="wrap">
		<h2><?php esc_html_e( 'All Access Pass Details', 'edd-all-access' ); ?></h2>
		<?php
		if ( edd_get_errors() ) {
			?>

			<div class="error settings-error">
				<?php edd_print_errors(); ?>
			</div>

			<?php
			return;
		}

		// Get the customer data and any All Access passes they may already have purchased.
		$customer_all_access_passes = $all_access_pass->customer->get_meta( 'all_access_passes' );

		?>

		<div id="edd-item-card-wrapper">

			<?php do_action( 'edd_all_access_card_top', $payment_id, $all_access_pass->download_id, $all_access_pass->price_id ); ?>

			<div class="info-wrapper item-section">

				<form id="edit-all-access-pass" method="post" action="<?php echo esc_url( admin_url( 'edit.php?post_type=download&page=edd-all-access-pass&payment_id=' . $all_access_pass->payment_id . '&download_id=' . $all_access_pass->download_id . '&price_id=' . $all_access_pass->price_id ) ); ?>">

					<div class="item-info">

						<?php
						// Check if 3.0.  If yes, then link to Add Order.  Else, link to Manual Purchases.
						if ( function_exists( 'edd_get_admin_base_url' ) ) {
							$add_new_url = add_query_arg(
								array(
									'page' => 'edd-payment-history',
									'view' => 'add-order',
								),
								edd_get_admin_base_url()
							);
							// Do not open new tab if staying in EDD.
							$new_tab = '';
						} else {
							$add_new_url = 'https://easydigitaldownloads.com/downloads/manual-purchases/';
							$new_tab     = ' target="_blank" rel="noopener noreferrer"';
						}

						$reactivation_notice =
							sprintf(
							// Translators: The %1$s represents the link to the manually create an order. %2$s is to open a link in a new tab.
								__( 'To be reactivated, it must be repurchased. You can also reactivate it by <a href="%1$s" %2$s>manually creating a payment.</a>', 'edd-all-access' ),
								esc_url( $add_new_url ),
								$new_tab
							);

						// Show a notice at the top depending on the status of this pass.
						if ( 'expired' === $all_access_pass->status ) {

							echo '<p style="margin-top: 0;">';
							echo wp_kses_post( '<strong>' . __( 'Note:', 'edd-all-access' ) . '</strong> ' . __( 'This All Access Pass has expired.', 'edd-all-access' ) . ' ' );
							echo wp_kses_post( $reactivation_notice );
							echo '</p>';
						}

						if ( 'upgraded' === $all_access_pass->status ) {
							echo '<p style="margin-top: 0;">';
							echo wp_kses_post( '<strong>' . __( 'Note:', 'edd-all-access' ) . '</strong> ' . __( 'This All Access Pass has been upgraded.', 'edd-all-access' ) . ' ' );
							echo wp_kses_post( $reactivation_notice );
							echo '</p>';

							// Set up the upgraded All Access Pass object so we can use it's data.
							$upgraded_aa_data         = explode( '_', $all_access_pass->is_prior_of );
							$upgraded_payment_id      = intval( $upgraded_aa_data[0] );
							$upgraded_download_id     = intval( $upgraded_aa_data[1] );
							$upgraded_price_id        = intval( $upgraded_aa_data[2] );
							$upgraded_all_access_pass = edd_all_access_get_pass( $upgraded_payment_id, $upgraded_download_id, $upgraded_price_id );

						}

						if ( 'renewed' === $all_access_pass->status ) {

							// Set up the currently active All Access Pass object so we can use its data.
							$latest_active_payment_id = $customer_all_access_passes[ $all_access_pass->download_id . '_' . $all_access_pass->price_id ]['payment_id'];
							$current_all_access_pass  = edd_all_access_get_pass( $latest_active_payment_id, $all_access_pass->download_id, $all_access_pass->price_id );

							if ( ! is_wp_error( $current_all_access_pass->payment_id ) ) {
								echo wp_kses_post( '<p style="margin-top: 0;"><strong>' . __( 'Note:', 'edd-all-access' ) . '</strong> ' . __( 'This All Access Pass has expired but was renewed by the customer. You can view the currently active All Access Pass', 'edd-all-access' ) . ' <a href="' . esc_url( admin_url( 'edit.php?post_type=download&page=edd-all-access-pass&payment_id=' . $current_all_access_pass->payment_id . '&download_id=' . $all_access_pass->download_id . '&price_id=' . $all_access_pass->price_id ) ) . '">' . __( 'here', 'edd-all-access' ) . '</a>.' );
							}
						}

						if ( 'upcoming' === $all_access_pass->status ) {

							// Set up the currently active All Access Pass object so we can use it's data.
							$latest_active_payment_id = $customer_all_access_passes[ $all_access_pass->download_id . '_' . $all_access_pass->price_id ]['payment_id'];
							$current_all_access_pass  = edd_all_access_get_pass( $latest_active_payment_id, $all_access_pass->download_id, $all_access_pass->price_id );

							if ( ! is_wp_error( $current_all_access_pass->payment_id ) ) {
								echo wp_kses_post( '<p style="margin-top: 0;"><strong>' . __( 'Note:', 'edd-all-access' ) . '</strong> ' . __( 'This All Access Pass is tied to a renewal payment and will "take over" when the current period ends. You can view the currently active All Access Pass', 'edd-all-access' ) . ' <a href="' . esc_url( admin_url( 'edit.php?post_type=download&page=edd-all-access-pass&payment_id=' . $current_all_access_pass->payment_id . '&download_id=' . $all_access_pass->download_id . '&price_id=' . $all_access_pass->price_id ) ) . '">' . __( 'here', 'edd-all-access' ) . '</a>.' );
							}

						}
						?>

						<table class="widefat striped">
							<tbody>
								<tr>
									<td class="row-title">
										<label for="tablecell"><?php esc_html_e( 'Customer:', 'edd-all-access' ); ?></label>
									</td>
									<td>
										<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=download&page=edd-customers&view=overview&id=' . $all_access_pass->customer->id ) ); ?>"><?php echo esc_html( ! empty( $all_access_pass->customer->name ) ? $all_access_pass->customer->name : $all_access_pass->customer->email ); ?></a>
									</td>
								</tr>
								<tr>
									<td class="row-title">
										<label for="tablecell"><?php esc_html_e( 'Payment:', 'edd-all-access' ); ?></label>
									</td>
									<td>
										<?php echo esc_html( $payment_id ); ?> <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=download&page=edd-payment-history&view=view-order-details&id=' . $payment_id ) ); ?>"><?php echo esc_html( __( '(View Order Details)', 'edd-all-access' ) ); ?></a>
									</td>
								</tr>
								<?php
								// If there are renewal payment IDs waiting to take over for this when it expires, list them.
								if ( 'active' === $all_access_pass->status && ! empty( $all_access_pass->renewal_payment_ids ) ) {
									?>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php esc_html_e( 'Early Renewal Payments:', 'edd-all-access' ); ?></label>
										</td>
										<td>
											<?php
											// Loop through each renewal payment and display a link to view it.
											foreach ( $all_access_pass->renewal_payment_ids as $renewal_payment_id ) {
												?>
													<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=download&page=edd-payment-history&view=view-order-details&id=' . $renewal_payment_id ) ); ?>"><?php echo esc_html( $renewal_payment_id ); ?></a>
											<?php } ?>
										</td>
									</tr>
								<?php } ?>
								<tr>
									<td class="row-title">
										<label for="tablecell"><?php esc_html_e( 'Product:', 'edd-all-access' ); ?></label>
									</td>
									<td>
										<a href="<?php echo esc_url( admin_url( 'post.php?action=edit&post=' . $all_access_pass->download_id ) ); ?>"><?php echo esc_html( get_the_title( $all_access_pass->download_id ) ); ?></a>
									</td>
								</tr>
								<?php
								// Show the downloaded products link.
								if ( 'active' === $all_access_pass->status || 'upgraded' === $all_access_pass->status || 'expired' === $all_access_pass->status || 'renewed' === $all_access_pass->status ) {
									?>
								<tr>
									<td class="row-title">
										<label for="tablecell"><?php esc_html_e( 'Downloaded Products:', 'edd-all-access' ); ?></label>
									</td>
									<td>
									<?php
									if ( function_exists( 'edd_get_admin_base_url' ) ) {
										// If in 3.0, link to Tools > Logs.
										$link = add_query_arg(
											array(
												'page'    => 'edd-tools',
												'tab'     => 'logs',
												'payment' => urlencode( $payment_id ),
											),
											edd_get_admin_base_url()
										);
									} else {
										// If in 2.9, link to Reports > Logs.
										$link = add_query_arg(
											array(
												'page'      => 'edd-reports',
												'tab'       => 'logs',
												'payment'   => urlencode( $payment_id ),
												'post_type' => 'download',
											),
											admin_url( 'edit.php' )
										);
									}
									?>
										<a href="<?php echo esc_url( $link ); ?>">
										<?php esc_html_e( 'View file download logs for this All Access pass.', 'edd-all-access' ); ?></a>
									</td>
								</tr>
								<?php } ?>
								<?php
								// Show the start date.
								if ( 'active' === $all_access_pass->status || 'upgraded' === $all_access_pass->status || 'expired' === $all_access_pass->status ) {
									?>
								<tr>
									<td class="row-title">
										<label for="tablecell"><?php esc_html_e( 'Start Date:', 'edd-all-access' ); ?></label>
									</td>
									<td><?php echo esc_html( edd_all_access_visible_date( 'M d, Y, g:i a', $all_access_pass->start_time ) ); ?></td>
								</tr>
									<?php
								}

								// Show the start/expiration dates for an upcoming pass - which is the expiration date of the current pass.
								if ( 'upcoming' === $all_access_pass->status ) {
									?>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php esc_html_e( 'Start Date:', 'edd-all-access' ); ?></label>
										</td>
										<td>
										<?php

										// If this pass is set to never expire, renewal payments will never "take over" because it will never expire.
										if ( 'never' === $all_access_pass->expiration_time ) {
											echo esc_html( 'never' === $all_access_pass->expiration_time ? __( 'The current pass is set to never expire so this renewal payment will not be used.', 'edd-all-access' ) : edd_all_access_visible_date( 'M d, Y, g:i a', $renewal_start_time ) );
										} else {

											// Figure out the Renewal Start and Expiration Times.

											// Get the position of the renewal payment in the line.
											$renewal_payment_position = array_search( $all_access_pass->payment_id, $current_all_access_pass->renewal_payment_ids, true ) + 1;

											$duration_time = strtotime( $all_access_pass->duration_number . ' ' . $all_access_pass->duration_unit, 0 );

											$renewal_start_time      = $all_access_pass->expiration_time + ( $duration_time * $renewal_payment_position - $duration_time );
											$renewal_expiration_time = $all_access_pass->expiration_time + ( $duration_time * ( $renewal_payment_position + 1 ) - $duration_time );

											echo esc_html( 'never' === $all_access_pass->expiration_time ? __( 'The current pass is set to never expire so this renewal payment will not be used.', 'edd-all-access' ) : edd_all_access_visible_date( 'M d, Y, g:i a', $renewal_start_time ) );
										}

										?>
									</td>
									</tr>
									<?php if ( is_int( $duration_time ) ) { ?>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php esc_html_e( 'Expiration Date:', 'edd-all-access' ); ?></label>
										</td>
										<td>
										<?php
										// Expiration Date here is the expiration of the current pass plus its duration period.
										echo esc_html( 'never' === $all_access_pass->expiration_time ? __( 'Never Expires', 'edd-all-access' ) : edd_all_access_visible_date( 'M d, Y, g:i a', $renewal_expiration_time ) );
										?>
										</td>
									</tr>
										<?php
}
								}

								// Show the Upgraded Date - If this All Access Pass has been upgraded, show that information instead of Expiration Date.
								if ( 'upgraded' === $all_access_pass->status ) {
									$date = false;
									foreach ( array( 'completed_date', 'date_completed', 'date' ) as $payment_date ) {
										if ( ! empty( $upgraded_all_access_pass->payment->{$payment_date} ) ) {
											$date = $upgraded_all_access_pass->payment->{$payment_date};
											break;
										}
									}
									if ( $date ) {
										?>
										<tr>
											<td class="row-title">
												<label for="tablecell"><?php esc_html_e( 'Upgraded Date:', 'edd-all-access' ); ?></label>
											</td>
											<td><?php echo esc_html( edd_all_access_visible_date( 'M d, Y, g:i a', strtotime( $date ) ) ); ?></td>
										</tr>
										<?php
									}
								}
								// Show the Expiration Date.
								if ( 'active' === $all_access_pass->status || 'expired' === $all_access_pass->status ) {
									?>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php esc_html_e( 'Expiration Date:', 'edd-all-access' ); ?></label>
										</td>
										<td>
										<?php
										echo esc_html( 'never' === $all_access_pass->expiration_time ? __( 'Never Expires', 'edd-all-access' ) : edd_all_access_visible_date( 'M d, Y, g:i a', $all_access_pass->expiration_time ) );
										?>
										</td>
									</tr>
								<?php } ?>

								<tr>
									<td class="row-title">
										<label for="tablecell"><?php esc_html_e( 'Status:', 'edd-all-access' ); ?></label>
									</td>
									<td><?php echo esc_html( edd_all_access_get_status_label( $all_access_pass->status ) ); ?>

										<?php
										// If this All Access Pass is not yet active but will/should be once the current one expires (upcoming).
										if ( 'upgraded' === $all_access_pass->status && ! is_wp_error( $upgraded_all_access_pass->payment_id ) ) {

											// Show a link to the upgraded-to pass.
											?>
											<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=download&page=edd-all-access-pass&payment_id=' . $upgraded_all_access_pass->payment->ID . '&download_id=' . $upgraded_all_access_pass->download_id . '&price_id=' . $upgraded_all_access_pass->price_id ) ); ?>"> <?php echo esc_html( __( '(View)', 'edd-all-access' ) ); ?></a>
										<?php } ?>
										<?php

										// If this is a renewed All Access Pass.
										if ( 'renewed' === $all_access_pass->status && ! is_wp_error( $current_all_access_pass->payment_id ) ) {

											// Show link to latest active all access pass.
											?>
											<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=download&page=edd-all-access-pass&payment_id=' . $current_all_access_pass->payment_id . '&download_id=' . $all_access_pass->download_id . '&price_id=' . $all_access_pass->price_id ) ); ?>"> <?php echo esc_html( __( '(View Current Pass)', 'edd-all-access' ) ); ?></a>
										<?php } ?>
										<?php
										// If this All Access Pass is not yet active but will/should be once the current one expires (upcoming).
										if ( 'upcoming' === $all_access_pass->status && ! is_wp_error( $current_all_access_pass->payment_id ) ) {

											// Show link to latest active all access pass.
											?>
											<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=download&page=edd-all-access-pass&payment_id=' . $current_all_access_pass->payment_id . '&download_id=' . $all_access_pass->download_id . '&price_id=' . $all_access_pass->price_id ) ); ?>"> <?php echo esc_html( __( '(View Current Pass)', 'edd-all-access' ) ); ?></a>
										<?php } ?>
									</td>
								</tr>

							</tbody>
						</table>

						<?php

						// Only show the meta options if this is active or expired.
						if ( 'active' === $all_access_pass->status || 'expired' === $all_access_pass->status ) {
							$time_of_activation_meta = $customer_all_access_passes[ $all_access_pass->download_id . '_' . $all_access_pass->price_id ]['time_of_activation_meta'];

							$all_access_start_time      = isset( $time_of_activation_meta['all_access_start_time'] ) ? $time_of_activation_meta['all_access_start_time'] : '';
							$all_access_duration_number = isset( $time_of_activation_meta['all_access_duration_number'] ) ? $time_of_activation_meta['all_access_duration_number'] : '';
							$all_access_duration_unit   = isset( $time_of_activation_meta['all_access_duration_unit'] ) ? $time_of_activation_meta['all_access_duration_unit'] : '';
							$download_limit             = isset( $time_of_activation_meta['all_access_download_limit'] ) ? $time_of_activation_meta['all_access_download_limit'] : '';
							$download_limit_time_period = isset( $time_of_activation_meta['all_access_download_limit_time_period'] ) ? $time_of_activation_meta['all_access_download_limit_time_period'] : '';
							$all_access_categories      = isset( $time_of_activation_meta['all_access_categories'] ) ? $time_of_activation_meta['all_access_categories'] : array();
							$number_of_price_ids        = isset( $time_of_activation_meta['all_access_number_of_price_ids'] ) ? $time_of_activation_meta['all_access_number_of_price_ids'] : 0;
							$included_price_ids         = isset( $time_of_activation_meta['all_access_included_price_ids'] ) && ! empty( $time_of_activation_meta['all_access_included_price_ids'] ) ? array_map( 'intval', $time_of_activation_meta['all_access_included_price_ids'] ) : array();
							?>
							<style>
							.edd-aa-time-of-purchase-settings .row-title{
								width:170px;
							}
							</style>
							<h2><?php echo esc_html( __( 'Settings at Time of Purchase', 'edd-all-access' ) ); ?></h2>
							<table class="widefat striped edd-aa-time-of-purchase-settings">
								<tbody>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php esc_html_e( 'Use these settings?', 'edd-all-access' ); ?></label>
										</td>
										<td><input id="time_of_activation_meta" type="radio" name="edd_all_access_meta_to_use" <?php echo esc_attr( checked( 'time_of_activation_meta', $all_access_pass->meta_to_use, false ) ); ?> value="time_of_activation_meta" /></td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php esc_html_e( 'Start Date:', 'edd-all-access' ); ?></label>
										</td>
										<td><?php echo esc_html( edd_all_access_visible_date( 'M d, Y, g:i a', $all_access_start_time ) ); ?></td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php esc_html_e( 'All Access Duration:', 'edd-all-access' ); ?></label>
										</td>
										<?php
										if ( 'never' === $all_access_duration_unit ) {
											?>
											<td><?php echo esc_html( __( 'Never Expires', 'edd-all-access' ) ); ?></td>
											<?php
										} else {
											$unfiltered_all_access_duration_unit_options = array(
												'never' => __( 'Never Expires', 'edd-all-access' ),
												'year'  => __( 'Year(s)', 'edd-all-access' ),
												'month' => __( 'Month(s)', 'edd-all-access' ),
												'week'  => __( 'Week(s)', 'edd-all-access' ),
												'day'   => __( 'Day(s)', 'edd-all-access' ),
											);
											$all_access_duration_unit_options            = apply_filters( 'edd_all_access_duration_unit_customer_options', $unfiltered_all_access_duration_unit_options, $all_access_pass->payment, $all_access_pass->download_id, $all_access_pass->price_id );

											?>
											<td>
											<?php
											// If the duration unit is a normal time unit, show the number before it.
											if ( in_array( $all_access_duration_unit_options[ $all_access_duration_unit ], $unfiltered_all_access_duration_unit_options, true ) ) {
												?>
											<span id="edd-aa-top-duration-number"><?php echo esc_html( $all_access_duration_number ); ?> </span>
												<?php
											}
											?>

											<span id="edd-aa-top-duration-unit"><?php echo esc_html( $all_access_duration_unit_options[ $all_access_duration_unit ] ); ?></span></td>
																						<?php
										}
										?>
									</tr>
									<?php
									if ( empty( $download_limit ) ) {
										$download_limit_per_period = __( 'Unlimited downloads per day', 'edd-all-access' );
									} else {
										$download_limit_per_period = $download_limit . ' ' . __( 'downloads per', 'edd-all-access' ) . ' ' . edd_all_access_download_limit_time_period_to_string( $download_limit_time_period );
									}
									?>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php esc_html_e( 'Download Limit:', 'edd-all-access' ); ?></label>
										</td>
										<td><?php echo esc_html( $download_limit_per_period ); ?></td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php esc_html_e( 'All Access To:', 'edd-all-access' ); ?></label>
										</td>
										<td>
										<?php

										if ( empty( $all_access_categories ) || 'all' === $all_access_categories || ! is_array( $all_access_categories ) ) {
											echo esc_html( __( 'All Products', 'edd-all-access' ) );
										} else {
											foreach ( $all_access_categories as $category_id ) {

												if ( 'all' === $category_id || empty( $category_id ) ) {
													echo esc_html( __( 'All Products', 'edd-all-access' ) );
												} else {
													$term_data = get_term( $category_id, 'download_category' );
													echo esc_html( $term_data->name );
												}

												if ( end( $all_access_categories ) !== $category_id ) {
													echo ', ';
												}
											}
										}

										?>
										</td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php esc_html_e( 'Included Price Variations:', 'edd-all-access' ); ?></label>
										</td>
										<td>
											<?php
											if ( 0 === (int) $number_of_price_ids ) {
												echo esc_html( __( 'All price variations included.', 'edd-all-access' ) );
											} else {
												?>
												<ul>
													<?php
													for ( $included_price_id = 1; $included_price_id <= $number_of_price_ids; $included_price_id++ ) {

														$variation_string = __( 'th Price Variation from each product', 'edd-all-access' );
														$variation_string = 1 === $included_price_id ? __( 'st Price Variation from each product', 'edd-all-access' ) : $variation_string;
														$variation_string = 2 === $included_price_id ? __( 'nd Price Variation from each product', 'edd-all-access' ) : $variation_string;
														$variation_string = 3 === $included_price_id ? __( 'rd Price Variation from each product', 'edd-all-access' ) : $variation_string;

														?>
														<li class="edd_all_access_included_price_id_li <?php echo esc_attr( $included_price_id ); ?>">
															<label>
																<input type="checkbox" disabled name="edd_all_access_meta[included_price_ids][]" class="edd_all_access_included_price_id" value="<?php echo esc_attr( $included_price_id ); ?>" <?php echo esc_attr( in_array( $included_price_id, $included_price_ids, true ) ? ' checked ' : '' ); ?>/>
																<?php echo esc_html( $included_price_id . $variation_string ); ?>
															</label>
														</li>
														<?php
													}
													?>
												</ul>
												<?php
											}
											?>
										</td>
									</tr>

								</tbody>
							</table>

							<?php
							if ( ! isset( $customer_all_access_passes[ $all_access_pass->download_id . '_' . $all_access_pass->price_id ]['customer_specific_meta'] ) ) {
								$customer_specific_meta = $time_of_activation_meta;
							} else {
								$customer_specific_meta = $customer_all_access_passes[ $all_access_pass->download_id . '_' . $all_access_pass->price_id ]['customer_specific_meta'];
							}

							$all_access_start_time      = isset( $customer_specific_meta['all_access_start_time'] ) ? $customer_specific_meta['all_access_start_time'] : '';
							$all_access_duration_number = isset( $customer_specific_meta['all_access_duration_number'] ) ? $customer_specific_meta['all_access_duration_number'] : '';
							$all_access_duration_unit   = isset( $customer_specific_meta['all_access_duration_unit'] ) ? $customer_specific_meta['all_access_duration_unit'] : '';
							$download_limit             = isset( $customer_specific_meta['all_access_download_limit'] ) ? $customer_specific_meta['all_access_download_limit'] : '';
							$download_limit_time_period = isset( $customer_specific_meta['all_access_download_limit_time_period'] ) ? $customer_specific_meta['all_access_download_limit_time_period'] : '';
							$all_access_categories      = isset( $customer_specific_meta['all_access_categories'] ) ? $customer_specific_meta['all_access_categories'] : array();
							$number_of_price_ids        = isset( $customer_specific_meta['all_access_number_of_price_ids'] ) ? $customer_specific_meta['all_access_number_of_price_ids'] : 0;
							$included_price_ids         = isset( $customer_specific_meta['all_access_included_price_ids'] ) && ! empty( $customer_specific_meta['all_access_included_price_ids'] ) ? array_map( 'intval', $customer_specific_meta['all_access_included_price_ids'] ) : array();

							?>

							<h2><?php echo esc_html( __( 'Customer-Specific Settings (overrides above settings)', 'edd-all-access' ) ); ?></h2>
							<table class="widefat striped">
								<tbody>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php esc_html_e( 'Use these settings?', 'edd-all-access' ); ?></label>
										</td>
										<td><input type="radio" id="customer_specific_meta" name="edd_all_access_meta_to_use" <?php echo esc_attr( checked( 'customer_specific_meta', $all_access_pass->meta_to_use, false ) ); ?> value="customer_specific_meta" /></td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php esc_html_e( 'Start Date:', 'edd-all-access' ); ?></label>
										</td>
										<td><input type="datetime-local" id="edd_all_access_meta_all_access_start_date" name="edd_all_access_meta[all_access_start_date]" value="<?php echo esc_html( edd_all_access_visible_date( 'Y-m-d\TH:i:s', $all_access_start_time ) ); ?>" /></td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php esc_html_e( 'All Access Duration:', 'edd-all-access' ); ?></label>
										</td>
										<td>

											<?php

											// Length of All Access duration.
											$all_access_duration_unit_options = array(
												'never' => __( 'Never Expires', 'edd-all-access' ),
												'year'  => __( 'Year(s)', 'edd-all-access' ),
												'month' => __( 'Month(s)', 'edd-all-access' ),
												'week'  => __( 'Week(s)', 'edd-all-access' ),
												'day'   => __( 'Day(s)', 'edd-all-access' ),
											);

											$all_access_duration_unit_options = apply_filters( 'edd_all_access_duration_unit_customer_options', $all_access_duration_unit_options, $all_access_pass->payment, $all_access_pass->download_id, $all_access_pass->price_id );

											// The field containing the number value.
											?>
											<input style="display:none; width:50px;" type="number" id="edd_all_access_meta_all_access_duration_number" placeholder="0" id="edd_all_access_meta_all_access_duration_number" name="edd_all_access_meta[all_access_duration_number]" min="1" value="<?php echo esc_attr( $all_access_duration_number ); ?>" />

											<?php // The field containing the time unit value. ?>
											<select name="edd_all_access_meta[all_access_duration_unit]" id="edd_all_access_meta_all_access_duration_unit">
											<?php
											foreach ( $all_access_duration_unit_options as $time_period_slug => $output_string ) {
												?>
												<option value="<?php echo esc_attr( $time_period_slug ); ?>" <?php echo esc_attr( selected( $time_period_slug, $all_access_duration_unit, false ) ); ?>><?php echo esc_html( $output_string ); ?></option>
												<?php
											}

											echo '</select>';

											?>

										</td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php esc_html_e( 'Download Limit:', 'edd-all-access' ); ?></label>
										</td>
										<td>
										<?php
											$download_limit = empty( $download_limit ) ? 0 : $download_limit;
										?>
											<input type="number" name="edd_all_access_meta[download_limit]" id="edd_all_access_download_limit" value="<?php echo esc_attr( $download_limit ); ?>" min="0" style="width:50px;" />&nbsp;
											<span id="edd_all_access_unlimited_download_limit_note" style="display:none;"><?php echo esc_html( __( '(0 = Unlimited downloads per day)', 'edd-all-access' ) ); ?></span>
											<?php

											// Downloads allowed per All Access Period.
											$download_limit_time_period_options = array(
												'per_day'  => __( 'X downloads per day', 'edd-all-access' ),
												'per_week' => __( 'X downloads per week', 'edd-all-access' ),
												'per_month' => __( 'X downloads per month', 'edd-all-access' ),
												'per_year' => __( 'X downloads per year', 'edd-all-access' ),
												'per_period' => __( 'X downloads total', 'edd-all-access' ),
											);

											$download_limit_time_period_options = apply_filters( 'edd_all_access_download_limit_options', $download_limit_time_period_options );

											?>
											<select name="edd_all_access_meta[download_limit_time_period]" id="edd_all_access_meta_download_limit_time_period">
											<?php
											foreach ( $download_limit_time_period_options as $time_period_slug => $output_string ) {
												?>
												<option value="<?php echo esc_attr( $time_period_slug ); ?>" <?php echo esc_attr( selected( $time_period_slug, $download_limit_time_period, false ) ); ?>><?php echo esc_html( $output_string ); ?></option>
												<?php
											}
											?>
										</select>
										</td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php esc_html_e( 'All Access To:', 'edd-all-access' ); ?></label>
										</td>
										<td>
										<?php

										$categories = get_terms( 'download_category', apply_filters( 'edd_category_dropdown', array() ) );
										$options    = array();

										foreach ( $categories as $category ) {
											$options[ absint( $category->term_id ) ] = esc_html( $category->name );
										}

										// Default to all categories included.
										if ( empty( $all_access_categories ) || 'all' === $all_access_categories || ! is_array( $all_access_categories ) ) {
											$all_access_categories = array( 'all' => __( 'All Products', 'edd-all-access' ) );
										}

										echo EDD()->html->select(
											array(
												'options'  => $options,
												'name'     => 'edd_all_access_meta[all_access_categories][]',
												'selected' => $all_access_categories,
												'id'       => 'edd_all_access_meta_all_access_categories',
												'class'    => 'edd_all_access_meta_all_access_categories',
												'chosen'   => true,
												'placeholder' => __( 'Type to search Categories', 'edd-all-access' ),
												'multiple' => true,
												'show_option_all' => __( 'All Products', 'edd-all-access' ),
												'show_option_none' => false,
												'data'     => array(),
											)
										);

										?>
										</td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php esc_html_e( 'Price Variations:', 'edd-all-access' ); ?></label>
										</td>
										<td>
											<?php
											echo esc_html( __( 'At most, included products have', 'edd-all-access' ) );
											?>
											<input type="number" name="edd_all_access_meta[number_of_price_ids]" id="edd_all_access_number_of_price_ids" value="<?php echo esc_attr( $number_of_price_ids ); ?>" min="0" style="width:40px;" /><?php echo esc_html( __( 'price variations.', 'edd-all-access' ) ); ?>
											<p><strong><?php echo esc_html( __( 'Included Price Variations:', 'edd-all-access' ) ); ?></strong></p>
											<span id="edd_all_access_included_price_ids_note" style="display:none;"><?php echo esc_html( __( 'Because this is set to 0, all price variations are included.', 'edd-all-access' ) ); ?></span>
											<ul id="edd_all_access_included_price_ids" style="display:none;">
												<?php
												for ( $price_id = 1; $price_id <= $number_of_price_ids; $price_id++ ) {

													$variation_string = __( 'th Price Variation from each product', 'edd-all-access' );
													$variation_string = 1 === $price_id ? __( 'st Price Variation from each product', 'edd-all-access' ) : $variation_string;
													$variation_string = 2 === $price_id ? __( 'nd Price Variation from each product', 'edd-all-access' ) : $variation_string;
													$variation_string = 3 === $price_id ? __( 'rd Price Variation from each product', 'edd-all-access' ) : $variation_string;

													?>
													<li class="edd_all_access_included_price_id_li <?php echo absint( $price_id ); ?>" style="display:none;">
														<label><input type="checkbox" name="edd_all_access_meta[included_price_ids][]" class="edd_all_access_included_price_id" value="<?php echo esc_attr( $price_id ); ?>" <?php echo ( in_array( $price_id, $included_price_ids, true ) ? ' checked ' : '' ); ?> /><?php echo esc_html( $price_id . $variation_string ); ?></label>
													</li>
													<?php
												}
												?>
												</ul>
										</td>
									</tr>

								</tbody>
							</table>

						</div>

						<div id="item-edit-actions" class="edit-item" style="display: block; margin-top:10px;">

							<?php
							if ( 'expired' === $all_access_pass->status ) {
								echo '<p style="margin-top: 0;">';
								echo wp_kses_post( '<strong>' . __( 'Note:', 'edd-all-access' ) . '</strong> ' . __( 'This All Access Pass has expired.', 'edd-all-access' ) . ' ' );
								echo wp_kses_post( $reactivation_notice );
								echo '</p>';
							}
							if ( 'upgraded' === $all_access_pass->status ) {
								echo '<p style="margin-top: 0;">';
								echo wp_kses_post( '<strong>' . __( 'Note:', 'edd-all-access' ) . '</strong> ' . __( 'This All Access Pass has been upgraded.', 'edd-all-access' ) . ' ' );
								echo wp_kses_post( $reactivation_notice );
								echo '</p>';
							}
							?>

							<?php wp_nonce_field( 'edd-all-access-update', 'edd-all-access-update-nonce', false, true ); ?>
							<input type="submit" name="edd_update_all_access_pass" id="edd_update_all_access_pass" class="button button-primary" value="<?php echo esc_attr( 'Update All Access Pass', 'edd-all-access' ); ?>"/>
						</div>
					<?php } ?>
				</form>
			</div>

			<?php do_action( 'edd_all_access_card_bottom', $all_access_pass->payment_id, $all_access_pass->download_id, $all_access_pass->price_id ); ?>
		</div>

	</div>
	<?php
}

/**
 * Handles saving/updating a single All Access Pass.
 *
 * @access      public
 * @since       1.0.0
 * @return      void
 */
function edd_all_access_process_update() {

	if ( empty( $_GET['payment_id'] ) || empty( $_GET['download_id'] ) || ! isset( $_GET['price_id'] ) ) {
		return;
	}

	if ( empty( $_POST['edd_update_all_access_pass'] ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_shop_payments' ) ) {
		return;
	}

	if ( ! isset( $_POST['edd-all-access-update-nonce'] ) ) {
		wp_die( __( 'Nonce verification failed', 'edd-all-access' ), __( 'Error', 'edd-all-access' ), array( 'response' => 403 ) );
	}

	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['edd-all-access-update-nonce'] ) ), 'edd-all-access-update' ) ) {
		wp_die( __( 'Nonce verification failed', 'edd-all-access' ), __( 'Error', 'edd-all-access' ), array( 'response' => 403 ) );
	}

	if ( ! isset( $_POST['edd_all_access_meta_to_use'] ) ) {
		wp_die( __( 'Missing paramater', 'edd-all-access' ), __( 'Error', 'edd-all-access' ), array( 'response' => 403 ) );
	}

	$all_access_pass = edd_all_access_get_pass( absint( $_GET['payment_id'] ), absint( $_GET['download_id'] ), absint( $_GET['price_id'] ) );

	if ( 'invalid' === $all_access_pass->status ) {
		wp_die( __( 'That is not a valid All Access Pass.', 'edd-all-access' ), __( 'Error', 'edd-all-access' ), array( 'response' => 403 ) );
	}

	// Get the All Access Pass data saved to this customer.
	$customer_all_access_passes = $all_access_pass->customer->get_meta( 'all_access_passes' );

	// If the All Access Passes array is empty, flush the cache as it's probably wrong if it's empty here.
	if ( empty( $customer_all_access_passes ) ) {

		$all_access_pass = edd_all_access_get_pass( absint( $_GET['payment_id'] ), absint( $_GET['download_id'] ), absint( $_GET['price_id'] ) );

		// Get the All Access Pass data saved to this customer.
		$customer_all_access_passes = $all_access_pass->customer->get_meta( 'all_access_passes' );
	}

	$meta_to_use = sanitize_text_field( wp_unslash( $_POST['edd_all_access_meta_to_use'] ) );

	switch ( $meta_to_use ) {

		case 'time_of_activation_meta':
			$customer_all_access_passes[ $all_access_pass->download_id . '_' . $all_access_pass->price_id ]['meta_to_use'] = 'time_of_activation_meta';
			break;

		case 'customer_specific_meta':
			$customer_all_access_passes[ $all_access_pass->download_id . '_' . $all_access_pass->price_id ]['meta_to_use'] = 'customer_specific_meta';
			break;

	}

	// This is submitted as an array and is sanitized in the switch statement below, which is why we have phpcs:ignore for the sanitization.
	$new = isset( $_POST['edd_all_access_meta'] ) ? wp_unslash( $_POST['edd_all_access_meta'] ) : false; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	if ( $new ) {

		$sanitized_meta = array();

		// Sanitize Values by looping through them (hence the phpcs:ignore call).
		foreach ( $new as $meta_key => $meta_value ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			switch ( $meta_key ) {
				case 'all_access_start_date':
					$date                                    = strtotime( $meta_value, current_time( 'timestamp' ) );
					$sanitized_meta['all_access_start_time'] = $date;

					break;
				case 'all_access_duration_number':
					if ( is_numeric( $meta_value ) ) {
						$sanitized_meta['all_access_duration_number'] = $meta_value;
					}

					break;
				case 'all_access_duration_unit':
					$sanitized_meta['all_access_duration_unit'] = sanitize_text_field( $meta_value );

					break;
				case 'download_limit':
					if ( is_numeric( $meta_value ) ) {
						$sanitized_meta['all_access_download_limit'] = $meta_value;
					}

					break;
				case 'download_limit_time_period':
					$sanitized_meta['all_access_download_limit_time_period'] = sanitize_text_field( $meta_value );

					break;
				case 'all_access_categories':
					$all_access_categories = array();

					foreach ( $meta_value as $all_access_category ) {
						if ( is_numeric( $all_access_category ) || 'all' === $all_access_category ) {
							$all_access_categories[] = $all_access_category;
						}
					}

					$sanitized_meta['all_access_categories'] = $all_access_categories;

					break;
				case 'number_of_price_ids':
					if ( is_numeric( $meta_value ) ) {
						$sanitized_meta['all_access_number_of_price_ids'] = $meta_value;
					}

					break;
				case 'included_price_ids':
					$included_price_ids = array();

					foreach ( $meta_value as $included_price_id ) {
						if ( is_numeric( $included_price_id ) ) {
							$included_price_ids[] = $included_price_id;
						}
					}

					$sanitized_meta['all_access_included_price_ids'] = $included_price_ids;

					break;
			}
		}

		// Do another check for included_price_ids because if none are checked, there is no meta value in $_POST.
		if ( ! isset( $_POST['edd_all_access_meta']['included_price_ids'] ) ) {
			$sanitized_meta['all_access_included_price_ids'] = array();
		}

		// Loop through all sanitized meta and re-add it to the existing settings. We do it this way so that we don't overwrite any settings that aren't editable.
		foreach ( $sanitized_meta as $key => $value ) {
			$customer_all_access_passes[ $all_access_pass->download_id . '_' . $all_access_pass->price_id ]['customer_specific_meta'][ $key ] = $value;
		}
	}

	$all_access_pass->customer->update_meta( 'all_access_passes', $customer_all_access_passes );

	// For testing purposes, reset the last reset date for downloads used to match the new start time.
	// This is a test to check if the downloads used counter resets by changing the date of an all access pass to be older so that more time has passed than actually has.
	// $all_access_pass->downloads_used_last_reset = $all_access_pass->start_time.

	wp_safe_redirect( admin_url( 'edit.php?post_type=download&page=edd-all-access-pass&payment_id=' . $all_access_pass->payment_id . '&download_id=' . $all_access_pass->download_id . '&price_id=' . $all_access_pass->price_id ) );
	exit;

}
add_action( 'admin_init', 'edd_all_access_process_update', 1 );
