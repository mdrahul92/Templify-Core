<?php
/**
 * Tools added to the "Tools" page in EDD Settings for EDD All Access.
 *
 * @package     EDD\EDDAllAccess\Functions
 * @since       1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the Backfill All Access Pass Tool
 *
 * @since       1.0.0
 * @return      void
 */
function edd_all_access_process_payments_tool_display() {

	if ( ! current_user_can( 'manage_shop_settings' ) ) {
		return;
	}

	?>
	<div class="postbox">
		<h3><span><?php esc_html_e( 'All Access - Backfill All Access Pass data', 'edd-all-access' ); ?></span></h3>
		<div class="inside recount-stats-controls">
			<p><?php echo wp_kses_post( __( 'Use this tool to backfill All Access Pass data for any purchases that took place before the All Access extension was installed. IMPORTANT NOTE: Before you use this tool, make sure that all of your All Access Pass products have been properly set up. To be sure, please review', 'edd-all-access' ) . ' <a href="http://docs.easydigitaldownloads.com/article/1829-all-access-creating-all-access-products" target="_blank">' . __( 'this document.', 'edd-all-access' ) . '</a>' ); ?></p>
			<form method="post" id="edd-tools-all-access-process-form" class="edd-all-access-process-form edd-import-export-form">
				<span>

					<?php wp_nonce_field( 'edd_all_access_ajax_process', 'edd_all_access_ajax_process' ); ?>

					<input type="submit" id="edd-all-access-tool-submit" value="<?php esc_html_e( 'Backfill All Access Pass data', 'edd-all-access' ); ?>" class="button-secondary"/>

					<br />

					<span class="spinner"></span>

				</span>
			</form>
		</div><!-- .inside -->
	</div><!-- .postbox -->
	<?php
}
add_action( 'edd_tools_tab_general', 'edd_all_access_process_payments_tool_display' );

/**
 * Display the Regenerate All Access Pass Tool on the Customer "Tools" page
 *
 * @since   1.0.13
 * @param   EDD_Customer $customer The EDD customer object for the customer being shown.
 * @return  void
 */
function edd_all_access_customer_tools( $customer ) {

	?>
	<div class="info-wrapper customer-section">

		<h3><?php esc_html_e( 'All Access Tools', 'edd-all-access' ); ?></h3>

		<div class="edd-item-info customer-info">
			<h4><?php esc_html_e( 'Regenerate Customer All Access Passes', 'edd-all-access' ); ?></h4>
			<p class="edd-item-description"><?php esc_html_e( 'Use this tool to regenerate the All Access Pass data for this customer.', 'edd-all-access' ); ?></p>
			<form method="post" id="edd-aa-customer-tools-regenerate-passes-form">
				<span>
					<?php wp_nonce_field( 'edd_all_access_ajax_process', 'edd_all_access_ajax_process' ); ?>

					<input type="hidden" name="customer_id" value="<?php echo esc_attr( $customer->id ); ?>" />
					<input type="submit" id="edd-aa-regenerate-customer-passes-submit" value="<?php esc_html_e( 'Regenerate All Access Passes', 'edd-all-access' ); ?>" class="button-secondary"/>
					<span class="spinner"></span>

				</span>
			</form>

		</div>

	</div>
	<?php
}
add_action( 'edd_customer_tools_bottom', 'edd_all_access_customer_tools' );
