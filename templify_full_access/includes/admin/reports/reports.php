<?php
/**
 * Reports Functions
 *
 * @package     EDD\EDDAllAccess\Functions
 * @since       1.0.0
 */

// Exit if accessed directly.
use EDD\Reports\Data\Report_Registry;
use function EDD\Reports\get_current_report;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( function_exists( 'edd_add_order' ) ) {
	add_action( 'edd_reports_init', 'edd_all_access_register_reports' );
} else {
	add_filter( 'edd_report_views', 'edd_all_access_most_popular_products_report_view' );
	add_action( 'edd_reports_view_edd_aa_popular_products', 'edd_all_access_most_popular_products_report' );
}

/**
 * Add a report to the dropdown in EDD's reports tab in EDD 2.9 and lower.
 *
 * @since       1.1.2
 * @param       array $views The available reports in EDD.
 * @return      array $views The modified list of available reports in EDD, with this report added.
 */
function edd_all_access_most_popular_products_report_view( $views ) {
	$views['edd_aa_popular_products'] = __( 'All Access: Download Popularity', 'edd-all-access' );

	return $views;
}

/**
 * Filter the product dropdown arguments to only show All Access Passes in the list.
 * This only runs when viewing the All Access report.
 *
 * @param array $query_args
 *
 * @since 1.1.5
 * @return array
 */
function edd_all_access_filter_product_dropdown_for_report( $query_args ) {
	if ( ! is_admin() || ! function_exists( '\\EDD\\Reports\\get_current_report' ) ) {
		return $query_args;
	}

	global $edd_reports_page;

	$current_screen = get_current_screen();

	if ( empty( $current_screen ) || $edd_reports_page !== $current_screen->id || 'all_access' !== get_current_report() ) {
		return $query_args;
	}

	$query_args['post__in'] = edd_all_access_get_all_access_downloads();

	return $query_args;
}

/**
 * Registers reports with EDD 3.0+
 *
 * @param Report_Registry $reports
 *
 * @since 1.1.5
 * @return void
 */
function edd_all_access_register_reports( $reports ) {
	// Add our filter on the product dropdown.
	add_filter( 'edd_product_dropdown_args', 'edd_all_access_filter_product_dropdown_for_report' );

	try {
		$reports->add_report( 'all_access', array(
			'label'     => __( 'All Access', 'edd-all-access' ),
			'icon'      => 'welcome-widgets-menus',
			'priority'  => 60,
			'endpoints' => array(
				'tables' => array(
					'all_access_download_popularity'
				)
			),
			'filters'   => array( 'products' ),
		) );

		$reports->register_endpoint( 'all_access_download_popularity', array(
			'label' => __( 'Download Popularity', 'edd-all-access' ),
			'views' => array(
				'table' => array(
					'display_args' => array(
						'class_name' => 'EDD_AA_Download_Popularity_Table',
						'class_file' => EDD_ALL_ACCESS_DIR . 'includes/admin/reports/class-edd-aa-download-popularity-table.php',
					),
				),
			),
		) );
	} catch ( \Exception $e ) {

	}
}

/**
 * Generate the output for the Popular Products report for All Access.
 *
 * @since       1.1.2
 */
function edd_all_access_most_popular_products_report() {

	$all_access_product_id = isset( $_GET['edd_aa_product_id'] ) ? absint( $_GET['edd_aa_product_id'] ) : null; // phpcs:ignore
	$aa_product_ids        = edd_all_access_get_all_access_downloads();

	// Get and format the date filters.
	$start_date = isset( $_GET['start-date'] ) ? sanitize_text_field( wp_unslash( $_GET['start-date'] ) ) : date( 'm/d/Y', strtotime( 'first day of january this year' ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$end_date   = isset( $_GET['end-date'] ) ? sanitize_text_field( wp_unslash( $_GET['end-date'] ) ) : date( 'm/d/Y', time() ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended

	// Get the timezone set for the WordPress, and the UTC timezone as well.
	$wp_timezone  = new DateTimeZone( edd_get_timezone_id() );
	$utc_timezone = new DateTimeZone( 'UTC' );

	// Set up a date object using the timestamp, in the user's local timezone.
	$start_date_time = new DateTime( $start_date, $wp_timezone );
	$end_date_time   = new DateTime( $end_date, $wp_timezone );

	// Convert the timezone of the date object so it is the UTC equivalent.
	$start_date_time->setTimezone( $utc_timezone );
	$end_date_time->setTimezone( $utc_timezone );

	// If no AA product was defined in the URL...
	if ( ! $all_access_product_id ) {

		// Try and see if any exist and use the first one.
		if ( empty( $aa_product_ids ) ) {
			?>
			<p><?php echo esc_html( __( 'No All Access Products were found.', 'edd-all-access' ) ); ?></p>
			<?php
			return;
		} else {
			// Default to the first All Access product available.
			$all_access_product_id = reset( $aa_product_ids );
		}
	}

	// Begin output of page.
	?>
	<div class="tablenav top">
		<div class="alignleft actions"><?php edd_report_views(); ?></div>
	</div>

	<h2><?php echo esc_html( __( 'All Access: Download Popularity', 'edd-all-access' ) ); ?></h2>

	<?php

	$downloads_table = new EDD_AA_Download_Popularity_Table();
	$downloads_table->set_filter_values( $all_access_product_id, 0, $aa_product_ids, $start_date_time, $end_date_time );
	$downloads_table->prepare_items();
	$downloads_table->display();

}
