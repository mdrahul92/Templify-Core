<?php
/**
 *  EDD Template File which overrides the [edd_aa_customer_passes] shortcode with the details of a single Full Access Pass.
 *
 * @description: Place this template file within your theme directory under /my-theme/edd_templates/ - For more information see: https://easydigitaldownloads.com/videos/template-files/
 *
 * @copyright  http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since      1.0.0
 */

//For logged in users only
if ( is_user_logged_in() ) {

	$payment_id      = absint( $_GET['payment_id'] );
	$all_access_pass = edd_all_access_get_pass( $payment_id, $_GET['download_id'], $_GET['price_id'] );

	// Check if the current user can view this pass.
	if ( empty( $all_access_pass->payment->user_id ) || get_current_user_id() !== (int) $all_access_pass->payment->user_id ) {
		esc_html_e( 'You do not have permimssion to view this Full Access pass.', 'edd-all-access' );
		return;
	}

	// Check if this Full Access Pass has been upgraded
	if ( 'upgraded' == $all_access_pass->status ) {
		$upgraded_aa_data         = explode( '_', $all_access_pass->is_prior_of );
		$upgraded_payment_id      = intval( $upgraded_aa_data[0] );
		$upgraded_download_id     = intval( $upgraded_aa_data[1] );
		$upgraded_price_id        = intval( $upgraded_aa_data[2] );
		$upgraded_all_access_pass = edd_all_access_get_pass( $upgraded_payment_id, $upgraded_download_id, $upgraded_price_id );
	}

	if ( 'invalid' == $all_access_pass->status ) {
		echo __( 'Nothing found for that URL', 'edd-all-access' );
	} else {
		$download = new EDD_Download( $all_access_pass->download_id );

		?>
		<p class="edd-sl-manage-license-details">
			<span class="edd-sl-manage-license-product"><?php _e( 'Product', 'edd-all-access' ); ?>: <span><?php echo $download->get_name(); ?></span></span>
		</p>

		<table id="edd_all_access_pass_details" class="edd_all_access_table">

			<thead>
				<tr class="edd_all_access_pass_details_row">
					<?php do_action( 'edd_all_access_pass_details_header_before' ); ?>
					<th class="edd_all_access_details"><?php _e( 'Details', 'edd-all-access' ); ?></th>
					<th class="edd_all_access_values"><?php _e( 'Value', 'edd-all-access' ); ?></th>
					<?php do_action( 'edd_all_access_pass_details_header_after' ); ?>
				</tr>
			</thead>

			<tbody>
				<?php
				// Only show start and expiration dates for specific statuses
				if ( 'active' == $all_access_pass->status || 'expired' == $all_access_pass->status ) {
					?>
					<tr class="edd_all_access_pass_details_row">
						<?php do_action( 'edd_all_access_pass_details_row_start', $all_access_pass ); ?>
						<td class="row-label">
							<span class="edd-aa-start-date-label"><?php _e( 'Start Date:', 'edd-all-access' ); ?></span>
						</td>
						<td class="row-value">
							<span class="edd-aa-start-date-value"><?php echo edd_all_access_visible_date( 'M d, Y, g:i a', $all_access_pass->start_time ); ?></span>
						</td>
						<?php do_action( 'edd_all_access_pass_details_row_end', $all_access_pass ); ?>
					</tr>
					<tr class="edd_all_access_pass_details_row">
						<?php do_action( 'edd_all_access_pass_details_row_start', $all_access_pass ); ?>
						<td class="row-label">
							<span class="edd-aa-expiration-date-label">
							<?php
							if ( 'upgraded' == $all_access_pass->status ) {
								_e( 'Upgraded Date:', 'edd-all-access' );
							} else {
								_e( 'Expiration Date:', 'edd-all-access' );
							}
							?>
							</span>
						</td>
						<td class="row-value">
							<span class="edd-aa-expiration-date-value">
							<?php

							// If this all access pass has been upgraded, show the upgrade date instead of the expiration.
							if ( 'upgraded' == $all_access_pass->status ) {

								// The "Upgraded Date" is the time the upgrade payment took place.
								echo edd_all_access_visible_date( 'M d, Y, g:i a', strtotime( $upgraded_all_access_pass->payment->date ) );

							} else {

								// If we are here, this pass was not upgraded so show the normal Expiration Time.
								echo $all_access_pass->expiration_time == 'never' ? __( 'Never Expires', 'edd-all-access' ) : edd_all_access_visible_date( 'M d, Y, g:i a', $all_access_pass->expiration_time );
							}
							?>
							</span>
						</td>
						<?php do_action( 'edd_all_access_pass_details_row_end', $all_access_pass ); ?>
					</tr>
					<?php
				}

				// If this Full Access Pass has renewal payments waiting to take over
				if ( ! empty( $all_access_pass->renewal_payment_ids ) && 'active' == $all_access_pass->status && 'never' !== $all_access_pass->expiration_time ) {
					// If we got to here, this Full Access pass has an actual expiration date - it's not infinite.
					$duration_time = strtotime( $all_access_pass->duration_number . ' ' . $all_access_pass->duration_unit, 0 );
					if ( $duration_time ) {
						?>
						<tr class="edd_all_access_pass_details_row">
							<?php do_action( 'edd_all_access_pass_details_row_start', $all_access_pass ); ?>
							<td class="row-label">
								<span class="edd-aa-upcoming-access-label"><?php _e( 'Upcoming Access Periods:', 'edd-all-access' ); ?></span>
							</td>
							<td class="row-value">
								<?php
								$loop_num = 1;

								//Loop through each upcoming renewal payment ID
								foreach ( $all_access_pass->renewal_payment_ids as $renewal_payment_id ) {

									echo '<span class="edd-aa-upcoming-period">';
									echo '<span class="edd-aa-upcoming-start-date">';
									echo __( 'Start Date', 'edd-all-access' ) . ': ' . edd_all_access_visible_date( 'M d, Y', $all_access_pass->start_time + ( $duration_time * $loop_num ) );
									echo '</span>';
									echo '<span class="edd-aa-upcoming-expiration-date">';
									echo __( 'Expiration Date', 'edd-all-access' ) . ': ' . edd_all_access_visible_date( 'M d, Y', $all_access_pass->start_time + ( $duration_time * ( $loop_num + 1 ) ) );
									echo '</span>';
									echo '</span>';

									$loop_num++;

								}
								?>
							</td>
							<?php do_action( 'edd_all_access_pass_details_row_end', $all_access_pass ); ?>
						</tr>
						<?php
					}
				}
				?>

				<tr class="edd_all_access_pass_details_row">
					<?php do_action( 'edd_all_access_pass_details_row_start', $all_access_pass ); ?>
					<td class="row-label">
						<span class="edd-aa-status-label"><?php _e( 'Status:', 'edd-all-access' ); ?></span>
					</td>
					<td class="row-value">
						<span class="edd-aa-status-value">
							<?php
								$aa_status       = $all_access_pass->status;
								$aa_status_label = edd_all_access_get_status_label( $aa_status );

								// If this Full Access Pass has expired and been renewed by a newer payment than the one attached to this pass.
							if ( 'renewed' == $all_access_pass->status ) {

								$customer = new EDD_Customer( get_current_user_id(), true );

								// Get the customer's current Full Access Passes from the customer meta
								$customer_all_access_passes = edd_all_access_get_customer_passes( $customer );

								// Get the payent ID attached to the current/latest Full Access Pass for this product directly from the customer meta
								$latest_payment_id = $customer_all_access_passes[ $all_access_pass->download_id . '_' . $all_access_pass->price_id ]['payment_id'];

								// Get the URL of the upgraded Full Access Pass
								$view_latest_aa_pass_url = add_query_arg(
									array(
										'action'      => 'view_all_access_pass',
										'payment_id'  => $latest_payment_id,
										'download_id' => $all_access_pass->download_id,
										'price_id'    => $all_access_pass->price_id,
									)
								);

								echo '<span class="edd-aa-' . $aa_status . '-status">' . $aa_status_label . ' <a href="' . $view_latest_aa_pass_url . '">' . __( '(View Current)', 'edd-all-access' ) . '</a></span>';

							} elseif ( 'upcoming' == $all_access_pass->status ) {

								// If this Full Access Pass is a renewal payment waiting for expiration of the current pass, link the customer to the current.

								$customer = new EDD_Customer( get_current_user_id(), true );

								// Get the customer's current Full Access Passes from the customer meta
								$customer_all_access_passes = edd_all_access_get_customer_passes( $customer );

								// Get the payent ID attached to the current/latest Full Access Pass for this product directly from the customer meta
								$latest_payment_id = $customer_all_access_passes[ $all_access_pass->download_id . '_' . $all_access_pass->price_id ]['payment_id'];

								// Get the URL of the upgraded Full Access Pass
								$view_latest_aa_pass_url = add_query_arg(
									array(
										'action'      => 'view_all_access_pass',
										'payment_id'  => $latest_payment_id,
										'download_id' => $all_access_pass->download_id,
										'price_id'    => $all_access_pass->price_id,
									)
								);

								echo '<span class="edd-aa-' . $aa_status . '-status">' . $aa_status_label . ' <a href="' . $view_latest_aa_pass_url . '">' . __( '(View Current)', 'edd-all-access' ) . '</a></span>';

							} elseif ( 'upgraded' == $all_access_pass->status ) {

								// If this Full Access Pass has been upgraded to another one - making this one expired before its time, let the customer know.

								// Get the URL of the upgraded Full Access Pass
								$view_upgraded_aa_pass_url = add_query_arg(
									array(
										'action'      => 'view_all_access_pass',
										'payment_id'  => $upgraded_all_access_pass->payment_id,
										'download_id' => $upgraded_all_access_pass->download_id,
										'price_id'    => $upgraded_all_access_pass->price_id,
									)
								);

								echo '<span class="edd-aa-' . $aa_status . '-status"><a href="' . $view_upgraded_aa_pass_url . '">' . $aa_status_label . '</a></span>';
							} elseif ( 'expired' == $aa_status ) {
								// Output a link so the customer can easily renew.
								$args = array(
									'edd_action'  => 'add_to_cart',
									'download_id' => $all_access_pass->download_id,
									'price_id'    => $all_access_pass->price_id,
								);

								$renew_url = add_query_arg( $args, edd_get_checkout_uri() );

								echo '<span class="edd-aa-' . $aa_status . '-status">' . $aa_status_label . '</span> | ' . '<a href="' . $renew_url . '">' . __( 'Renew', 'edd-all-access' ) . '</a>';
							} else {
								echo '<span class="edd-aa-' . $aa_status . '-status">' . $aa_status_label . '</span>';
							}
							?>
						</span>
					</td>
					<?php do_action( 'edd_all_access_pass_details_row_end', $all_access_pass ); ?>
				</tr>
				<?php
				// If this Full Access Pass has been upgraded, renewed, or is awaiting activation, the rest of the data is irrelevant so only show if relevant
				if ( 'renewed' != $all_access_pass->status && 'upgraded' != $all_access_pass->status && 'upcoming' != $all_access_pass->status ) {
					?>
					<tr class="edd_all_access_pass_details_row">
						<?php do_action( 'edd_all_access_pass_details_row_start', $all_access_pass ); ?>
						<td class="row-label">
							<span class="edd-aa-access-to-label"><?php _e( 'Access To:', 'edd-all-access' ); ?></span>
						</td>
						<td class="row-value">
							<span class="edd-aa-access-to-value">
								<?php

									$aa_total_categories_count = count( $all_access_pass->included_categories );
									$aa_current_iteration      = 0;
								foreach ( $all_access_pass->included_categories as $included_category_id ) {
									if ( 'all' == $included_category_id ) {
										echo __( 'All Products', 'edd-all-access' );
										break;
									} else {
										$term_data = get_term( $included_category_id, 'download_category' );
										echo $term_data->name;
									}

									if ( $aa_current_iteration < ( $aa_total_categories_count - 1 ) ) {
										echo ', ';
									}

									$aa_current_iteration = $aa_current_iteration + 1;
								}
								?>
							</span>
						</td>
						<?php do_action( 'edd_all_access_pass_details_row_end', $all_access_pass ); ?>
					</tr>
					<tr class="edd_all_access_pass_details_row">
						<td class="row-label">
							<span class="edd-aa-access-duration-label"><?php _e( 'Full Access Duration:', 'edd-all-access' ); ?></span>
						</td>
						<td class="row-value">
							<span class="edd-aa-access-duration-value"><?php echo edd_all_access_duration_string( $all_access_pass ); ?></span>
						</td>
					</tr>
					<tr class="edd_all_access_pass_details_row">
						<td class="row-label">
							<span class="edd-aa-price-variation-label"><?php _e( 'Included Price Variations:', 'edd-all-access' ); ?></span>
						</td>
						<td class="row-value">
							<span class="edd-aa-price-variation-value">
								<?php
								if ( $all_access_pass->number_of_price_ids == 0 ) {
									echo __( 'All price variations included.', 'edd-all-access' );
								} else {
									echo '<ul>';
									for ( $included_price_id = 1; $included_price_id <= $all_access_pass->number_of_price_ids; $included_price_id++ ) {

										$variation_string = __( 'th Price Variation from each product', 'edd-all-access' );
										$variation_string = $included_price_id == 1 ? __( 'st Price Variation from each product', 'edd-all-access' ) : $variation_string;
										$variation_string = $included_price_id == 2 ? __( 'nd Price Variation from each product', 'edd-all-access' ) : $variation_string;
										$variation_string = $included_price_id == 3 ? __( 'rd Price Variation from each product', 'edd-all-access' ) : $variation_string;

										echo '<li class="edd-aa-included-price-id ' . esc_attr( $included_price_id ) . '"><label><input type="checkbox" disabled name="edd_all_access_meta[included_price_ids][]" class="edd_all_access_included_price_id" value="' . esc_attr( $included_price_id ) . '" ' . esc_attr( ( in_array( $included_price_id, $all_access_pass->included_price_ids ) ? ' checked ' : '' ) ) . '/>' . esc_html( $included_price_id . $variation_string ) . '</label></li>';
									}
									echo '</ul>';
								}
								?>
							</span>
						</td>
					</tr>
					<tr class="edd_all_access_pass_details_row">
						<td class="row-label">
							<span class="edd-aa-download-limit-label"><?php _e( 'Download Limit:', 'edd-all-access' ); ?></span>
						</td>
						<td class="row-value">
							<span class="edd-aa-download-limit-value"><?php echo edd_all_access_download_limit_string( $all_access_pass ); ?></span>
						</td>
					</tr>

					<?php if ( $all_access_pass->download_limit != 0 ) { ?>
						<tr class="edd_all_access_pass_details_row">
							<td class="row-label">
								<span class="edd-aa-downloads-used-label"><?php _e( 'Downloads Used:', 'edd-all-access' ); ?></span>
							</td>
							<td class="row-value">
								<span class="edd-aa-downloads-used-value"><?php echo $all_access_pass->downloads_used; ?></span>
							</td>
						</tr>
						<tr class="edd_all_access_pass_details_row">
							<td class="row-label">
								<span class="edd-aa-downloads-left-label"><?php _e( 'Downloads Left:', 'edd-all-access' ); ?></span>
							</td>
							<td class="row-value">
								<span class="edd-aa-downloads-left-value"><?php echo $downloads_left = $all_access_pass->download_limit - $all_access_pass->downloads_used; ?></span>
							</td>
						</tr>
						<tr class="edd_all_access_pass_details_row">
							<td class="row-label">
								<span class="edd-aa-current-period-start-label"><?php _e( 'Current Download Period started at:', 'edd-all-access' ); ?></span>
							</td>
							<td class="row-value">
								<span class="edd-aa-current-period-start-value"><?php echo edd_all_access_visible_date( 'M d, Y, g:i a', $all_access_pass->downloads_used_last_reset ); ?></span>
							</td>
						</tr>
						<tr class="edd_all_access_pass_details_row">
							<td class="row-label">
								<span class="edd-aa-next-period-begins-label"><?php _e( 'Next Download Period begins at:', 'edd-all-access' ); ?></span>
							</td>
							<td class="row-value">
								<span class="edd-aa-next-period-begins-value"><?php echo edd_all_access_visible_date( 'M d, Y, g:i a', $all_access_pass->downloads_used_last_reset + strtotime( '1 ' . edd_all_access_download_limit_time_period_to_string( $all_access_pass->download_limit_time_period ), 0 ) ); ?></span>
							</td>
						</tr>
						<tr class="edd_all_access_pass_details_row">
							<td class="row-label">
								<span class="edd-aa-current-time-label"><?php _e( 'Current Time (on server):', 'edd-all-access' ); ?></span>
							</td>
							<td class="row-value">
								<span class="edd-aa-current-time-value"><?php echo edd_all_access_visible_date( 'M d, Y, g:i a', strtotime( 'now' ) ); ?></span>
							</td>
						</tr>
						<?php
					}
				}
				?>

			</tbody>
		</table>
		<?php

		$current_page = empty( $_GET['aa_file_downloads_page'] ) ? 1 : absint( $_GET['aa_file_downloads_page'] );
		$number       = get_option( 'posts_per_page' );
		$offset       = 1 === $current_page ? '' : ( $current_page - 1 ) * $number;

		// Show the file downloads done by the customer using this pass.
		if ( function_exists( 'edd_get_file_download_logs' ) ) {
			$log_query = array(
				'meta_query'             => array(
					array(
						'key'   => '_edd_log_all_access_pass_id',
						'value' => $all_access_pass->id,
					),
				),
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'offset'                 => $offset,
				'number'                 => $number,
			);
			$query     = edd_get_file_download_logs( $log_query );
			unset( $log_query['offset'] );
			unset( $log_query['number'] );
			$total = edd_count_file_download_logs( $log_query );
		} else {
			/**
			 * @var EDD_Logging $edd_logs
			 */
			global $edd_logs;
			$log_query = array(
				'meta_query'             => array(
					array(
						'key'   => '_edd_log_all_access_pass_id',
						'value' => $all_access_pass->id,
					),
				),
				'posts_per_page'         => $number,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'offset'                 => $offset,
			);
			$query = $edd_logs->get_connected_logs( $log_query );

			unset( $log_query['offset'] );
			$log_query['fields']         = 'ids';
			$log_query['posts_per_page'] = - 1;
			$total_query                 = $edd_logs->get_connected_logs( $log_query );
			$total                       = is_array( $total_query ) ? count( $total_query ) : 0;
		}

		// Check that we have query results.
		if ( ! empty( $query ) ) {

			?>
			<div class="edd-aa-files-downloaded-title"><?php esc_html_e( 'Files downloaded in this period:', 'edd-all-access' ); ?></div>
			<table id="edd_all_access_pass_details" class="edd_all_access_table">

				<thead>
					<tr class="edd_all_access_pass_details_row">
						<th class="edd_all_access_details"><?php esc_html_e( 'Downloaded Date', 'edd-all-access' ); ?></th>
						<th class="edd_all_access_values"><?php esc_html_e( 'Product', 'edd-all-access' ); ?></th>
					</tr>
				</thead>

				<tbody>
			<?php

			$date_time_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
			// Start looping over the query results.
			foreach ( $query as $log ) {

				if ( $log instanceof EDD\Logs\File_Download_Log ) {
					$this_post_id = $log->id;
					$download_id  = $log->product_id;
					$price_id     = $log->price_id;
					$file_id      = $log->file_id;
					$date         = edd_date_i18n( $log->date_created, $date_time_format );
				} else {
					$this_post_id = $log->ID;
					$download_id  = wp_get_post_parent_id( $this_post_id );
					$price_id     = get_post_meta( $this_post_id, '_edd_log_price_id', true );
					$file_id      = get_post_meta( $this_post_id, '_edd_log_file_id', true );
					$date         = get_post_time( $date_time_format, false, $log, true );
				}
				$price_name    = edd_get_price_option_name( $download_id, $price_id );
				$price_name    = empty( $price_name ) ? '' : ' - ' . $price_name;
				$download_link = edd_all_access_product_download_url( $download_id, $price_id, $file_id );

				?>
				<tr class="edd_all_access_pass_file_download_row">
					<td class="row-label">
						<span class="edd-aa-downloaded-date-label">
							<?php
								// The date the file download took place.
								echo esc_html( $date );
							?>
						</span>
					</td>
					<td class="row-value">
						<a href="<?php echo esc_url( $download_link ); ?>">
							<span class="edd-aa-downloaded-name-value"><?php echo esc_html( get_the_title( $download_id ) . $price_name ); ?></span>
						</a>
					</td>
				</tr>
				<?php
			}
			?>
			</tbody>
		</table>
		<div class="edd-aa-file-download-pagination">
			<?php

			// Create the URL which will link to the previous and next pages.
			$next_page = add_query_arg(
				array(
					'action'                 => 'view_all_access_pass',
					'payment_id'             => $all_access_pass->payment_id,
					'download_id'            => $all_access_pass->download_id,
					'price_id'               => $all_access_pass->price_id,
					'aa_file_downloads_page' => $current_page + 1,
				)
			);

			$prev_page = add_query_arg(
				array(
					'action'                 => 'view_all_access_pass',
					'payment_id'             => $all_access_pass->payment_id,
					'download_id'            => $all_access_pass->download_id,
					'price_id'               => $all_access_pass->price_id,
					'aa_file_downloads_page' => 1 === $current_page ? 1 : $current_page - 1,
				)
			);

			$number_of_pages = ( $total / $number ) < 1 ? 1 : ceil( $total / $number );

			if ( $current_page > 1 ) {
				?>
				<a class="button edd-aa-prev" href="<?php echo esc_url( $prev_page ); ?>"><?php echo esc_html( __( 'Previous Page', 'edd-all-access' ) ); ?></a>
			<?php } ?>
			<span>
				<?php
					// Translators: 1: The current page of Full Access file downloads being viewed. 2: The total number of pages.
					echo esc_html( sprintf( __( 'Page %1$s of %2$s', 'edd-all-access' ), $current_page, $number_of_pages ) );
				?>
			</span>
			<?php
			if ( $current_page < $number_of_pages ) {
				?>
				<a class="button edd-aa-next" href="<?php echo esc_url( $next_page ); ?>"><?php echo esc_html( __( 'Next Page', 'edd-all-access' ) ); ?></a>
				<?php
			}
			?>
		</div>
			<?php
		}
	}
}
