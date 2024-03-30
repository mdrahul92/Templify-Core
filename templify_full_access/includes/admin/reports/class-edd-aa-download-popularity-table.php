<?php
/**
 * Download Popularity Table Class Table Class
 *
 * @package     EDD
 * @subpackage  Admin/Reports
 * @copyright   Copyright (c) 2015, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.5
 */

use EDD\Reports;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load WP_List_Table if not loaded.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * EDD_Download_Reports_Table Class
 *
 * Renders the Download Popularity Table Class table
 *
 * @since 1.1.2
 */
class EDD_AA_Download_Popularity_Table extends WP_List_Table {

	/**
	 * The EDD download ID where this AA pass originates.
	 *
	 * @var int $all_access_product_id
	 */
	private $all_access_product_id = null;

	/**
	 * The EDD variable price ID where this AA pass originates.
	 *
	 * @var int $all_access_price_id
	 */
	private $all_access_price_id = 0;

	/**
	 * The EDD variable price ID where this AA pass originates.
	 *
	 * @var int $aa_product_ids
	 */
	private $aa_product_ids = array();

	/**
	 * The date object for the start date for which we want to get the results.
	 *
	 * @var DateTime|string $start_date
	 */
	private $start_date = null;

	/**
	 * The date object for the end date for which we want to get the results.
	 *
	 * @var DateTime|string $end_date
	 */
	private $end_date = null;

	/**
	 * The order in which the results should be shown.
	 *
	 * @var string $orderby
	 */
	private $orderby = 'unique_passes_used';

	/**
	 * Set the values for the Pass in question
	 *
	 * @since 1.1.2
	 *
	 * @param int             $all_access_product_id The id of the AA product in question.
	 * @param int             $all_access_price_id   The variable price id of the AA product in question.
	 * @param array           $aa_product_ids        The All Access products on this site.
	 * @param DateTime|string $start_date            The date object for the start date for which we want to get the
	 *                                               results.
	 * @param DateTime|string $end_date              The date object for the end date for which we want to get the
	 *                                               results.
	 */
	public function set_filter_values( $all_access_product_id, $all_access_price_id, $aa_product_ids, $start_date, $end_date ) {
		$this->all_access_product_id = $all_access_product_id;
		$this->all_access_price_id   = $all_access_price_id;
		$this->aa_product_ids        = $aa_product_ids;
		$this->start_date            = $start_date;
		$this->end_date              = $end_date;
	}

	/**
	 * Define the columns.
	 *
	 * @since 1.1.2
	 */
	public function get_columns() {
		return array(
			'downloaded_product_name' => __( 'Downloaded Product', 'edd-all-access' ),
			'number_of_downloads'     => __( 'Number of downloads', 'edd-all-access' ),
			'unique_passes_used'      => __( 'Unique Passes Used', 'edd-all-access' ),
			'download_rate'           => __( 'Popularity Rate', 'edd-all-access' ),
		);
	}

	/**
	 * Prepare the table items.
	 *
	 * @since 1.1.2
	 */
	public function prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = array();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		if ( function_exists( 'edd_get_order' ) ) {
			$date_filter = Reports\get_filter_value( 'dates' );
			$date_range  = Reports\parse_dates_for_range( $date_filter['range'] );

			$product_id = Reports\get_filter_value( 'products' );
			$price_id   = 0;

			// Parse out price ID.
			if ( ! empty( $product_id ) && is_string( $product_id ) && false !== strpos( $product_id, '_' ) ) {
				$pieces     = explode( '_', $product_id );
				$product_id = isset( $pieces[0] ) ? $pieces[0] : 0;
				$price_id   = isset( $pieces[1] ) ? $pieces[1] : 0;
			}

			if ( empty( $product_id ) || ! is_numeric( $product_id ) ) {
				$product_id = 0;
			}

			$this->set_filter_values(
				$product_id,
				$price_id,
				edd_all_access_get_all_access_downloads(),
				EDD()->utils->date( $date_range['start'], null, false )->startOfDay()->format( 'mysql' ),
				EDD()->utils->date( $date_range['end'], null, false )->startOfDay()->format( 'mysql' )
			);
		}

		$this->items = $this->get_popular_products( $this->all_access_product_id, $this->all_access_price_id, $this->start_date, $this->end_date );
	}

	/**
	 * Get the popular products from the database (EDD 3.0+)
	 *
	 * @since 1.1.5
	 * @return array
	 */
	protected function query_popular_products() {
		$popular_products = array();

		$product_id = $this->all_access_product_id;
		$price_id   = $this->all_access_price_id;

		if ( empty( $this->all_access_product_id ) || 'all' === $this->all_access_product_id || ! in_array( $this->all_access_product_id, $this->aa_product_ids ) ) {
			return $popular_products;
		}

		$meta_value = esc_sql( sprintf( '%d_%d', intval( $product_id ), intval( $price_id ) ) );

		global $wpdb;

		// Set the order string.
		if ( 'number_of_downloads' === $this->orderby ) {
			$order = 'COUNT(product_id)';
		} else {
			// Order by unique passes used.
			$order = 'COUNT(DISTINCT meta_value)';
		}

		$popular_products = $wpdb->get_results( $wpdb->prepare(
			"SELECT product_id as downloaded_product_id, COUNT(product_id) as number_of_downloads, COUNT(DISTINCT meta_value) as unique_passes_used
			FROM {$wpdb->edd_logs_file_downloads} l
			INNER JOIN {$wpdb->edd_logs_file_downloadmeta} lm ON l.id = lm.edd_logs_file_download_id
			WHERE meta_key = '_edd_log_all_access_pass_id'
			AND meta_value LIKE '%_$meta_value'
			AND date_created > %s
			AND date_created < %s
			GROUP BY product_id
			ORDER BY {$order} DESC",
			$this->start_date,
			$this->end_date
		), ARRAY_A );

		// Get the total number of unique passes that were used in the time period.
		$total_unique_passes_used = $wpdb->get_results( $wpdb->prepare(
			"SELECT COUNT(DISTINCT meta_value) as unique_passes_used
			FROM {$wpdb->edd_logs_file_downloads} l
			INNER JOIN {$wpdb->edd_logs_file_downloadmeta} lm ON l.id = lm.edd_logs_file_download_id
			WHERE meta_key = '_edd_log_all_access_pass_id'
			AND meta_value LIKE '%_{$product_id}_%'
			AND date_created > %s
			AND date_created < %s",
			$this->start_date,
			$this->end_date
		), ARRAY_A );

		return array(
			'popular_products'    => $popular_products,
			'total_unique_passes' => $total_unique_passes_used
		);
	}

	/**
	 * Queries for popular products in EDD 2.9 and lower.
	 *
	 * @param int    $all_access_product_id
	 * @param int    $all_access_price_id
	 * @param string $start_date
	 * @param string $end_date
	 *
	 * @since 1.1.5
	 * @return array
	 */
	protected function query_popular_products_29( $all_access_product_id, $all_access_price_id, $start_date, $end_date ) {
		global $wpdb;

		/**
		 * Here's an example of the raw query which powers this:
		 * SELECT wp_38_posts.post_parent, count(wp_38_posts.post_parent)  from wp_38_posts LEFT JOIN wp_38_postmeta ON wp_38_postmeta.post_id = wp_38_posts.ID WHERE wp_38_postmeta.meta_key = '_edd_log_all_access_pass_id' AND wp_38_postmeta.meta_value LIKE '%_40_%' group by wp_38_posts.post_parent;
		 */

		// Set the names of setup values for use in $wpdb->prepare.
		$posts_table          = $wpdb->prefix . 'posts';
		$posts_post_id        = $wpdb->prefix . 'posts.ID';
		$posts_post_parent    = $wpdb->prefix . 'posts.post_parent';
		$posts_post_date_gmt  = $wpdb->prefix . 'posts.post_date_gmt';
		$post_meta_table      = $wpdb->prefix . 'postmeta';
		$post_meta_post_id    = $wpdb->prefix . 'postmeta.post_id';
		$post_meta_meta_key   = $wpdb->prefix . 'postmeta.meta_key';
		$post_meta_meta_value = $wpdb->prefix . 'postmeta.meta_value';

		// Set the values for the date range.
		$utc_timezone = new DateTimeZone( 'UTC' );
		$start_date->setTimezone( $utc_timezone );
		$start_date_mysql = $start_date->format( 'Y-m-d H:i:s' );
		$end_date_mysql   = $end_date->format( 'Y-m-d H:i:s' );

		// Set the order string.
		if ( 'unique_passes_used' === $this->orderby ) {
			$order = 'count(distinct ' . $post_meta_meta_value . ')';
		}
		if ( 'number_of_downloads' === $this->orderby ) {
			$order = 'count(' . $posts_post_parent . ')';
		}

		// Get the number of times each product has been downloaded, and the unique number of times each product has been downloaded via this AA pass.
		$popular_products = $wpdb->get_results( // phpcs:ignore
			$wpdb->prepare(
				"SELECT $posts_post_parent as downloaded_product_id, count($posts_post_parent) as number_of_downloads, count(distinct $post_meta_meta_value) as unique_passes_used from $posts_table LEFT JOIN $post_meta_table ON $post_meta_post_id = $posts_post_id WHERE $post_meta_meta_key = %s AND $post_meta_meta_value LIKE '%_{$all_access_product_id}_%' AND $posts_post_date_gmt > %s AND $posts_post_date_gmt < %s group by $posts_post_parent ORDER BY $order DESC;", // phpcs:ignore
				array( '_edd_log_all_access_pass_id', $start_date_mysql, $end_date_mysql )
			),
			ARRAY_A
		);

		// Get the total number of unique passes that were used in the time period.
		$total_unique_passes_used = $wpdb->get_results( // phpcs:ignore
			$wpdb->prepare(
				"SELECT count(distinct $post_meta_meta_value) as unique_passes_used from $posts_table LEFT JOIN $post_meta_table ON $post_meta_post_id = $posts_post_id WHERE $post_meta_meta_key = %s AND $post_meta_meta_value LIKE '%_{$all_access_product_id}_%' AND $posts_post_date_gmt > %s AND $posts_post_date_gmt < %s;", // phpcs:ignore
				array( '_edd_log_all_access_pass_id', $start_date_mysql, $end_date_mysql )
			),
			ARRAY_A
		);

		return array(
			'popular_products'    => $popular_products,
			'total_unique_passes' => $total_unique_passes_used
		);
	}

	/**
	 * Get the popular products from the database.
	 *
	 * @param int      $all_access_product_id The id of the EDD Download which is an All Access Pass, and for which we want to know which products were downloaded.
	 * @param int      $all_access_price_id The variable price id of the EDD Download which is an All Access Pass, and for which we want to know which products were downloaded.
	 * @param DateTime $start_date The date object for the start date for which we want to get the results.
	 * @param DateTime $end_date The date object for the end date for which we want to get the results.
	 * @since 1.1.2
	 */
	public function get_popular_products( $all_access_product_id, $all_access_price_id, $start_date, $end_date ) {

		if ( function_exists( 'edd_get_order' ) ) {
			$query_results = $this->query_popular_products();
		} else {
			$query_results = $this->query_popular_products_29( $all_access_product_id, $all_access_price_id, $start_date, $end_date );
		}

		$popular_products         = ! empty( $query_results['popular_products'] ) ? $query_results['popular_products'] : array();
		$total_unique_passes_used = ! empty( $query_results['total_unique_passes'] ) ? $query_results['total_unique_passes'] : array();

		$total_unique_passes_used = isset( $total_unique_passes_used[0]['unique_passes_used'] ) ? $total_unique_passes_used[0]['unique_passes_used'] : 0;
		$counter                  = 0;

		// Apply additional column information to each row.
		foreach ( $popular_products as $popular_product ) {
			// Add the product name to each product/row.
			$popular_products[ $counter ]['downloaded_product_name'] = get_the_title( $popular_product['downloaded_product_id'] );
			// Calculate the percentage of customers holding a pass and downloaded this product.
			$popular_products[ $counter ]['download_rate'] = round( 100 * ( $popular_product['unique_passes_used'] / $total_unique_passes_used ) ) . '%';

			$counter++;
		}

		return $popular_products;

	}

	/**
	 * Define the output for each column
	 *
	 * @param array  $item The item that represents this row of data.
	 * @param string $column_name The name of the column.
	 * @since 1.1.2
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'downloaded_product_name':
			case 'number_of_downloads':
			case 'unique_passes_used':
			case 'download_rate':
				return $item[ $column_name ];
			default:
				return new WP_Error( 'column_not_found', wp_json_encode( $item ) );
		}
	}

	/**
	 * Gets the product title and row actions.
	 *
	 * @since 1.1.5
	 * @param object $item
	 * @return string
	 */
	public function column_downloaded_product_name( $item ) {
		$edit_link = get_edit_post_link( $item['downloaded_product_id'] );
		$title     = current_user_can( 'edit_products' ) ?
			sprintf(
				'<a class="row-title" href="%s">%s</a>',
				esc_url( $edit_link ),
				esc_html( $item['downloaded_product_name'] )
			) :
			sprintf(
				'<span class="row-title">%s</span>',
				esc_html( $item['downloaded_product_name'] )
			);
		$actions   = array(
			'view' => '<a href="' . esc_url( get_permalink( $item['downloaded_product_id'] ) ) . '">' . esc_html__( 'View', 'edd-all-access' ) . '</a>',
		);
		if ( current_user_can( 'edit_products' ) ) {
			$actions['edit'] = '<a href="' . esc_url( $edit_link ) . '">' . esc_html__( 'Edit', 'edd-all-access' ) . '</a>';
		}

		return $title . $this->row_actions( $actions );
	}

	/**
	 * Output the table navigation
	 *
	 * @param string $which The context of the table navigation being output.
	 * @since 1.1.2
	 */
	public function display_tablenav( $which ) {

		// This UI is no longer needed in EDD 3.0+ because we utilize the built-in filters.
		if ( function_exists( 'edd_get_order' ) ) {
			return;
		}

		$start_date = $this->start_date;
		$end_date   = $this->end_date;

		$wp_timezone = new DateTimeZone( edd_get_timezone_id() );

		// Convert the timezone of the date object so it is the WP timezone equivalent for display.
		$start_date->setTimezone( $wp_timezone );
		$end_date->setTimezone( $wp_timezone );

		// Reformat the AA products into one that works for the select dropdown, allowing the admin to choose which AA product they wish to learn about.
		$aa_product_select_values = array();
		foreach ( $this->aa_product_ids as $aa_product_id ) {
			$aa_product_select_values[ $aa_product_id ] = get_the_title( $aa_product_id );
		}
		?>
		<div id="edd-payment-filters" style="overflow:initial;">
			<span><strong><?php echo esc_html( __( 'All Access Product:', 'edd-all-access' ) ); ?></strong></span>
			<form action="">
				<input type="hidden" name="view" value="edd_aa_popular_products" />
				<input type="hidden" name="page" value="edd-reports" />
				<input type="hidden" name="category" value="all" />
				<input type="hidden" name="post_type" value="download" />
				<span>
					<?php
					echo EDD()->html->select( // phpcs:ignore
						array(
							'options'          => $aa_product_select_values,
							'name'             => 'edd_aa_product_id',
							'selected'         => esc_attr( $this->all_access_product_id ),
							'id'               => 'edd_all_access_products',
							'class'            => 'edd_all_access_products',
							'chosen'           => true,
							'placeholder'      => esc_attr( __( 'Type to search All Access Products', 'edd-all-access' ) ),
							'multiple'         => false,
							'show_option_all'  => false,
							'show_option_none' => false,
							'data'             => array( 'search-type' => 'no_ajax' ),
						)
					);
					?>
				</span>
				<span id="edd-payment-date-filters">
					<span>
						<label for="start-date"><?php echo esc_html( __( 'Start Date:', 'edd-all-access' ) ); ?></label>
						<input type="text" id="start-date" name="start-date" class="edd_datepicker" value="<?php echo esc_attr( $start_date->format( 'm/d/Y' ) ); ?>" placeholder="mm/dd/yyyy"/>
					</span>
					<span>
						<label for="end-date"><?php echo esc_html( __( 'End Date:', 'edd-all-access' ) ); ?></label>
						<input type="text" id="end-date" name="end-date" class="edd_datepicker" value="<?php echo esc_attr( $end_date->format( 'm/d/Y' ) ); ?>" placeholder="mm/dd/yyyy"/>
					</span>
				</span>
				<span id="edd-payment-after-core-filters">
					<input type="submit" class="button-secondary" value="<?php echo esc_attr( __( 'Apply', 'edd-all-access' ) ); ?>"/>
				</span>
			</form>
		</div>
			<?php
	}

	/**
	 * Message to be displayed when there are no items
	 *
	 * @since 1.1.5
	 */
	public function no_items() {
		$message = __( 'No items found.', 'edd-all-access' );

		if ( empty( $this->all_access_product_id ) || 'all' === $this->all_access_product_id ) {
			$message = __( 'Please select an All Access Pass.', 'edd-all-access' );
		} elseif ( ! in_array( $this->all_access_product_id, $this->aa_product_ids ) ) {
			$message = __( 'The selected product is not an All Access Pass.', 'edd-all-access' );
		}

		echo esc_html( $message );
	}
}
