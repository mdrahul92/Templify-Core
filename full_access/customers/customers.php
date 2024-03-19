<?php
/**
 * Add Full Access Passes to the EDD Customer Interface
 *
 * @package     EDD Full Access
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add the Full Access Passes tab to the customer interface if the customer has Full Access Passes
 *
 * @since  1.0.0
 *
 * @param array $tabs The tabs currently added to the customer view.
 *
 * @return array       Updated tabs array
 */
function edd_all_access_customer_tab( $tabs ) {

	// This makes it so former commission recievers get the tab and new commission users with no sales see it.
	$tabs['all-access-passes'] = array(
		'dashicon' => 'dashicons-welcome-widgets-menus',
		'title'    => __( 'Full Access Passes', 'templify-full-access' ),
	);

	return $tabs;
}

add_filter( 'edd_customer_tabs', 'edd_all_access_customer_tab', 10, 1 );

/**
 * Register the Full Access Passes view for the customer interface
 *
 * @since  1.0.0
 *
 * @param array $views The views currently added to the customer views.
 *
 * @return array       Updated tabs array
 */
function edd_all_access_add_customer_view( $views ) {

	$views['all-access-passes'] = 'edd_all_access_customer_view';

	return $views;
}

add_filter( 'edd_customer_views', 'edd_all_access_add_customer_view', 10, 1 );

/**
 * Display the Full Access Passes area for the customer view
 *
 * @since  1.0.0
 *
 * @param object $customer The Customer being displayed.
 *
 * @return void
 */
function edd_all_access_customer_view( $customer ) {

	if ( function_exists( 'edd_render_customer_details_header' ) ) {
		edd_render_customer_details_header( $customer );
	} else {
		?>
		<div class="customer-notes-header">
			<?php echo get_avatar( $customer->email, 30 ); ?> <span><?php echo esc_html( $customer->name ); ?></span>
		</div>
	<?php } ?>

	<div id="customer-tables-wrapper" class="customer-section">
		<?php

		// Get the Full Access passes saved to this customer meta.
		$customer_all_access_passes = edd_all_access_get_customer_pass_objects( $customer );

		$at_least_one_pass_to_show = false;

		?>
		<h3><?php esc_html_e( 'Full Access Passes', 'templify-full-access' ); ?></h3>
		<table class="wp-list-table widefat striped downloads">
			<thead>
			<tr>
				<th><?php echo esc_html( edd_get_label_singular() ); ?></th>
				<th><?php esc_html_e( 'Start Date', 'templify-full-access' ); ?></th>
				<th><?php esc_html_e( 'Expiration Date', 'templify-full-access' ); ?></th>
				<th><?php esc_html_e( 'Status', 'templify-full-access' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'templify-full-access' ); ?></th>
			</tr>
			</thead>

			<tbody>
			<?php
			if ( is_array( $customer_all_access_passes ) ) {

				foreach ( $customer_all_access_passes as $all_access_pass ) {

					if ( empty( $all_access_pass->payment_id ) || empty( $all_access_pass->download_id ) || ! isset( $all_access_pass->price_id ) ) {
						continue;
					}

					if ( 'invalid' === $all_access_pass->status ) {
						continue;
					}

					$edd_payment = edd_get_payment( $all_access_pass->payment_id );

					// Check if the payment's customer is the same as the customer to-whom this meta belongs. If not, this pass has been transferred to a different EDD Customer.
					if ( $edd_payment->customer_id !== $customer->id ) {
						continue;
					}

					$at_least_one_pass_to_show = true;
					?>
					<tr>
						<td>
							<a href="<?php echo esc_url( admin_url( 'post.php?action=edit&post=' . $all_access_pass->download_id ) ); ?>"><?php echo esc_html( get_the_title( $all_access_pass->download_id ) ); ?></a>
						</td>
						<td><?php echo esc_html( edd_all_access_visible_date( 'M d, Y', $all_access_pass->start_time ) ); ?></td>
						<td><?php echo esc_html( 'never' === $all_access_pass->expiration_time ? __( 'Never Expires', 'templify-full-access' ) : edd_all_access_visible_date( 'M d, Y', $all_access_pass->expiration_time ) ); ?></td>
						<td><?php echo esc_html( edd_all_access_get_status_label( $all_access_pass->status ) ); ?></td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=download&page=edd-all-access-pass&payment_id=' . $all_access_pass->payment->ID . '&download_id=' . $all_access_pass->download_id . '&price_id=' . $all_access_pass->price_id ) ); ?>"><?php esc_html_e( 'View Details', 'templify-full-access' ); ?></a>
					</tr>
					<?php
				}
			}

			// If there was not at least 1 valid pass to show.
			if ( ! $at_least_one_pass_to_show ) {
				?>
				<tr>
					<td colspan="5"><?php esc_html_e( 'No Full Access Passes Found', 'templify-full-access' ); ?></td>
				</tr>
			<?php } ?>
			</tbody>
		</table>

		<?php
		// If the EDD Dev Tools is active, output a little extra here.
		if ( class_exists( 'EDD_Dev_Tools' ) ) {
			?>
			<div class="edd-all-access-dev-tools-area" style="background-color:#fffbb4; padding:10px;">'
				<h2><?php echo esc_html( __( 'Customer Full Access Passes Array:', 'templify-full-access' ) ); ?></h2>
				<p><?php echo esc_html( __( 'You are seeing this because you have EDD Dev Tools Activated', 'templify-full-access' ) ); ?></p>
				<pre>
					<?php print_r( $customer_all_access_passes ); ?>
				</pre>
			</div>
			<?php
		}
		?>
	</div>
	<?php
}
