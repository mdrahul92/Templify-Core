<?php
/**
 * Utility/Helper Functions
 *
 * @package     EDD\EDDAllAccess\Functions
 * @since       1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if a user has access to a specific EDD Download through an "All Access" purchase.
 * All we need to know for this function is which product/price-variation is being downloaded.
 * From there we can detect the user based on whether they are logged in.
 *
 * Note: in a case where a user has purchased multiple All Access passes, the first one that qualifies will be returned.
 *
 * @since    1.0.0
 * @param    array $args Array of options that determine the return.
 * @return   array - Array of success or failure messages depending on access.
 */
function edd_all_access_check( $args = array() ) {

	$default_args = array(
		'download_id'            => false,
		'price_id'               => false,
		'customer_id'            => false,
		'user_id'                => false,
		'check_download_limit'   => false, // by default we don't check the download limit because this function is used in places other than just downloading.
		'require_login'          => true,
		'aa_download_must_match' => false, // Optional. Pass a download ID here. This is useful if want to know if they have access through a specific AA product, instead of just via any AA product, which is the default.
	);

	$args = wp_parse_args( $args, $default_args );

	// First, if required, lets make sure this user is even logged in.
	if ( $args['require_login'] && ( ! is_user_logged_in() || 0 === intval( get_current_user_id() ) ) ) {

		return array(
			'success'         => false,
			'all_access_pass' => false,
			'failure_id'      => 'user_not_logged_in',
			'failure_message' => __( 'You must be logged in to have access to this file.', 'edd-all-access' ),
		);
	}

	// If no edd customer is given.
	if ( ! $args['customer_id'] ) {
		// If no user id was provided, use the current user.
		if ( ! $args['user_id'] ) {
			$person_id  = get_current_user_id();
		} else {
			$person_id  = $args['user_id'];
		}
		$by_user_id = true;
	} else {
		// If a customer was provided.
		$person_id  = $args['customer_id'];
		$by_user_id = false;
	}

	// If no person ID was found, we can't do a proper check.
	if ( ! $person_id ) {
		$return_data = array(
			'success'         => false,
			'all_access_pass' => null,
			'failure_id'      => 'no_customer_given',
			'failure_message' => __( 'No customer was given in regards to this check.', 'edd-all-access' ),
		);

		edd_debug_log( 'EDD All Access - Issue 311: Customer not found. Passed-in args: ' . wp_json_encode( $args ) );

		return $return_data;
	}

	$customer = new EDD_Customer( $person_id, $by_user_id );

	$access = new \EDD\AllAccess\Helpers\DownloadAccessChecker( $customer, $args['download_id'], $args['price_id'] );
	$access->check_download_limit = $args['check_download_limit'];
	$access->aa_product_id = $args['aa_download_must_match'];

	try {
		$winningPass = $access->check();

		return [
			'success'         => true,
			'all_access_pass' => $winningPass,
			'failure_id'      => 'no_failure',
			'failure_message' => '',
		];
	} catch ( \EDD\AllAccess\Exceptions\AccessException $e ) {
		return [
			'success'         => false,
			'all_access_pass' => $e->getPass(),
			'failure_id'      => $e->getFailureId(),
			'failure_message' => $e->getMessage(),
		];
	}

}

/**
 * Get an array of product/download ids which are "All Access" enabled.
 *
 * @since    1.0.0
 * @since    1.2.1 Added the $force_lookup parameter to allow using the function to force a refresh.
 *
 * @param bool $force_lookup If the function should force possibly refreshing the array of product IDs.
 *
 * @return   array - The post ids of all "All Access" posts
 */
function edd_all_access_get_all_access_downloads( $force_lookup = false ) {

	// Check for the All Access products option.
	$edd_all_access_products = get_option( 'edd_all_access_products' );
	$current_hash            = md5( json_encode( $edd_all_access_products ) );

	// If the option isn't set, query for published downloads which have AA enabled.
	if ( false === $edd_all_access_products || true === $force_lookup ) {
		$query                   = new WP_Query(
			array(
				'post_type'      => 'download',
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'posts_per_page' => -1,
				'meta_query'     => array(
					'relation' => 'OR',
					array(
						'key'     => '_edd_all_access_enabled',
						'value'   => '1',
						'compare' => '=',
					),
					array(
						'key'     => '_edd_product_type',
						'value'   => 'all_access',
						'compare' => '=',
					),
				),
			)
		);
		$edd_all_access_products = $query->posts;
	}

	$edd_all_access_downloads = is_array( $edd_all_access_products ) ? array_unique( $edd_all_access_products ) : array();

	$new_hash = md5( json_encode( $edd_all_access_downloads ) );

	// Only update the database if something has changed.
	if ( ! hash_equals( $current_hash, $new_hash ) ) {
		// Set the products option.
		update_option( 'edd_all_access_products', $edd_all_access_downloads );
	}

	return apply_filters( 'edd_all_access_downloads', $edd_all_access_downloads );
}

/**
 * Check if a download is an All Access enabled product.
 *
 * @since    1.0.0
 * @param    int $download_id The id of the product being checked.
 * @param    int $price_id The price id of the product being checked.
 * @return   int $download_id The id of the product being checked
 */
function edd_all_access_download_is_all_access( $download_id, $price_id = 0 ) {

	// If this purchase is not an "All Access" post.
	if ( ! in_array( $download_id, edd_all_access_get_all_access_downloads() ) ) {
		return false;
	}

	return true;

}

/**
 * Check if an All Access pass (if theoretically purchased and active) would have access to an EDD Download. This is not dependant on any user or payment.
 *
 * @since    1.0.0
 * @param    array $args Array of options that determine the return.
 * @return   array This array contains either a success or failure message why it failed.
 */
function edd_all_access_includes_download( $args = array() ) {

	$default_args = array(
		'all_access_product_info' => array(
			'download_id' => 0,
			'price_id'    => 0, // This is unused even if variable pricing is enabled for an All Access Pass because the meta options are product-wide (not pricing specific).
		),
		'desired_product_info'    => array(
			'download_id' => 0,
			'price_id'    => 0,
		),
	);

	$args = wp_parse_args( $args, $default_args );

	// Get all posts which count as "All Access" posts.
	$all_access_posts = edd_all_access_get_all_access_downloads();

	// There's no All Access to purchase so definitively, none could access this product.
	if ( empty( $all_access_posts ) ) {
		return array(
			'success'         => false,
			'failure_id'      => 'no_all_access_posts_exist',
			'failure_message' => __( 'No All Access downloads have been configured', 'edd-all-access' ),
		);
	}

	// If the passed in download_id is not an All Access pass.
	if ( ! in_array( $args['all_access_product_info']['download_id'], $all_access_posts, true ) ) {
		return array(
			'success'         => false,
			'failure_id'      => 'not_an_all_access_product',
			'failure_message' => __( 'The ID passed was not an All Access product.', 'edd-all-access' ),
		);
	}

	// Check if this product being viewed has variable pricing.
	$has_variable_prices = edd_has_variable_prices( $args['desired_product_info']['download_id'] );

	// If there are variable prices and the desired product is one of those variable prices (not single price mode).
	if ( $has_variable_prices && 0 !== intval( $args['desired_product_info']['price_id'] ) ) {

		// Check if any of the variable prices exclude All Access.
		$variable_prices = edd_get_variable_prices( $args['desired_product_info']['download_id'] );

		foreach ( $variable_prices as $variable_price_id => $variable_price_settings ) {

			// If this variable price id matches the one we are checking for with this function.
			if ( intval( $variable_price_id ) === intval( $args['desired_product_info']['price_id'] ) ) {

				// If this variable price is excluded from All Access.
				if ( ! empty( $variable_price_settings['excluded_price'] ) ) {
					$return_data = array(
						'success'         => false,
						'failure_id'      => 'product_is_excluded',
						'failure_message' => __( 'The Price ID you are attempting to access is excluded from All Access', 'edd-all-access' ),
					);
					return $return_data;
				}
			}
		}
	} else {
		// If this single-price product is known to be excluded from all All Access passes.
		$product_is_excluded = get_post_meta( $args['desired_product_info']['download_id'], '_edd_all_access_exclude', true );

		if ( $product_is_excluded ) {
			$return_data = array(
				'success'         => false,
				'failure_id'      => 'product_is_excluded',
				'failure_message' => __( 'The product you are attempting to access is excluded from All Access', 'edd-all-access' ),
			);
			return $return_data;
		}
	}

	// Get the all access meta information.
	$product               = new \EDD\AllAccess\Models\AllAccessProduct( $args['all_access_product_info']['download_id'] );
	$all_access_categories = $product->categories;
	$included_price_ids    = $product->included_price_ids;

	// Get the Categories assigned to the desired download.
	$download_categories   = wp_get_post_terms( $args['desired_product_info']['download_id'], 'download_category' );
	$has_required_category = false;

	if ( is_array( $all_access_categories ) ) {
		// Loop through each acceptable/included download category.
		foreach ( $all_access_categories as $all_access_category ) {

			// Loop through each category attached to the download the user wishes to download/access.
			foreach ( $download_categories as $download_category ) {
				if ( intval( $all_access_category ) === intval( $download_category->term_id ) ) {
					$has_required_category = true;
					break;
				}
			}
		}
	}

	// If this All Access Product has access to a category that this download has.
	if ( empty( $all_access_categories ) || in_array( 'all', $all_access_categories, true ) || $has_required_category ) {

		// If this All Access Product has access to the price_id we are hoping to retrieve.
		if ( empty( $args['desired_product_info']['price_id'] ) || empty( $included_price_ids ) || in_array( intval( $args['desired_product_info']['price_id'] ), array_map( 'intval', $included_price_ids ), true ) ) {

			// The All Access post would/does include access to the Download/Price ID requested.
			return array(
				'success'         => true,
				'success_message' => __( 'This All Access post does include access to this Download.', 'edd-all-access' ),
				'failure'         => false,
			);
		} else {
			return array(
				'success'         => false,
				'failure'         => 'price_id_not_included',
				'failure_message' => __( 'This All Access post does not have access to this product variation.', 'edd-all-access' ),
			);
		}
	} else {
		return array(
			'success'         => false,
			'failure'         => 'category_not_included',
			'failure_message' => __( 'This All Access post does not have access to products in this category.', 'edd-all-access' ),
		);
	}

	return array(
		'success'         => false,
		'failure'         => 'unknown_error',
		'failure_message' => __( 'Unknown error with All Access', 'edd-all-access' ),
	);
}

/**
 * Get the URL used to download products using an All Access pass.
 *
 * @since    1.0.0
 * @param    int $download_id The post ID of the product being downloaded.
 * @param    int $price_id The price ID product being downloaded.
 * @param    int $file_id The ID of the file being downloaded.
 * @return   string - The URL used to downlodad the file via All Access
 */
function edd_all_access_product_download_url( $download_id, $price_id = 0, $file_id = 0 ) {

	if ( empty( $price_id ) ) {
		$download = edd_get_download( $download_id );
		if ( ! $download->has_variable_prices() ) {
			$price_id = null;
		}
	}

	$query_args = array(
		'edd-all-access-download' => $download_id,
		'edd-all-access-file-id'  => $file_id,
	);

	if ( ! is_null( $price_id ) ) {
		$query_args['edd-all-access-price-id'] = $price_id;
	}

	$button_href    = add_query_arg(
		$query_args,
		get_bloginfo( 'wpurl' )
	);
	$download_token = edd_get_download_token( $button_href );

	return esc_url( add_query_arg( array( 'token' => $download_token ), $button_href ) );
}

/**
 * Get the status label of an All Access pass.
 *
 * @since    1.0.0
 * @param    string $status The status of the All Access Pass.
 * @return   string "Active" if still active. 'Expired' if expired. 'Invalid' if invalid.
 */
function edd_all_access_get_status_label( $status ) {
	switch ( $status ) {
		case 'active':
			$status = __( 'Active', 'edd-all-access' );
			break;

		case 'expired':
			$status = __( 'Expired', 'edd-all-access' );
			break;

		case 'upgraded':
				$status = __( 'Upgraded', 'edd-all-access' );
			break;

		case 'renewed':
				$status = __( 'Renewed', 'edd-all-access' );
			break;

		case 'upcoming':
				$status = __( 'Upcoming', 'edd-all-access' );
			break;

		case 'invalid':
			$status = __( 'Invalid', 'edd-all-access' );
			break;

		case 'disabled':
			$status = __( 'Disabled', 'edd-all-access' );
			break;
	}

	return $status;
}

/**
 * Get the PHP acceptable time string for the download limit time period (Downloads per X).
 *
 * @since    1.0.0
 * @param    string $download_limit_time_period The saved meta for the download limit time period.
 * @param    bool   $allow_translations Whether to wrap the return value in a localization function, or force english.
 * @return   string The PHP str_to_time acceptable word equivalent.
 */
function edd_all_access_download_limit_time_period_to_string( $download_limit_time_period, $allow_translations = true ) {
	// If the time period is a day - get the string 'day' to show the user.
	if ( 'per_day' === $download_limit_time_period ) {
		$time_period = $allow_translations ? __( 'day', 'edd-all-access' ) : 'day';

		// If the time period is a week - get the string 'week' to show the user.
	} elseif ( 'per_week' === $download_limit_time_period ) {
		$time_period = $allow_translations ? __( 'week', 'edd-all-access' ) : 'week';

		// If the time period is a month - get the string 'month' to show the user.
	} elseif ( 'per_month' === $download_limit_time_period ) {
		$time_period = $allow_translations ? __( 'month', 'edd-all-access' ) : 'month';

		// If the time period is a year - get the string 'year' to show the user.
	} elseif ( 'per_year' === $download_limit_time_period ) {
		$time_period = $allow_translations ? __( 'year', 'edd-all-access' ) : 'year';

		// If the time period is a per period - get the string 'total' to show the user.
	} elseif ( 'per_period' === $download_limit_time_period ) {
		$time_period = $allow_translations ? __( 'total', 'edd-all-access' ) : 'total';
	}

	return $time_period;
}

/**
 * Turn $download_limit per $time_period into a human-readable string like "5 Downloads per Day".
 *
 * @since    1.0.0
 * @param    EDD_All_Access_Pass $all_access_pass An All Access Pass object.
 * @return   string - Easy to read string representing the download limit for an All Access Pass.
 */
function edd_all_access_download_limit_string( $all_access_pass ) {

	if ( 0 === intval( $all_access_pass->download_limit ) ) {
		$assembled_string = __( 'Unlimited downloads', 'edd-all-access' );
	} else {
		$per_string = _x( 'per', 'Translating "per" in "5 Downloads per day"', 'edd-all-access' );

		$time_period_string = edd_all_access_download_limit_time_period_to_string( $all_access_pass->download_limit_time_period );

		$assembled_string = $all_access_pass->download_limit . ' ' . $per_string . ' ' . $time_period_string;
	}

	return apply_filters( 'edd_all_access_download_limit_string', $assembled_string, $all_access_pass );
}

/**
 * Get the string for an All Access Pass's duration. For example, if you want to show "1 year" when referring to the length of an All Access Pass.
 *
 * @since    1.0.0
 * @param    EDD_All_Access_Pass $all_access_pass An All Access Pass object.
 * @return   string - Easy to read string representing the duration for an All Access Pass.
 */
function edd_all_access_duration_string( $all_access_pass ) {

	$duration_string = null;

	switch ( $all_access_pass->duration_unit ) {
		case 'never':
			$duration_string = __( 'Never expires', 'edd-all-access' );
			break;
		case 'year':
			$duration_string = $all_access_pass->duration_number > 1 ? __( 'years', 'edd-all-access' ) : __( 'year', 'edd-all-access' );
			break;
		case 'month':
			$duration_string = $all_access_pass->duration_number > 1 ? __( 'months', 'edd-all-access' ) : __( 'month', 'edd-all-access' );
			break;
		case 'week':
			$duration_string = $all_access_pass->duration_number > 1 ? __( 'weeks', 'edd-all-access' ) : __( 'week', 'edd-all-access' );
			break;
		case 'day':
			$duration_string = $all_access_pass->duration_number > 1 ? __( 'days', 'edd-all-access' ) : __( 'day', 'edd-all-access' );
			break;
	}

	if ( 'never' === $all_access_pass->duration_unit ) {
		$assembled_string = __( 'Never expires', 'edd-all-access' );
	} else {
		$assembled_string = $all_access_pass->duration_number . ' ' . $duration_string;
	}

	return apply_filters( 'edd_all_access_duration_string', $assembled_string, $all_access_pass );
}

/**
 * Get the number of time periods that have passed since the original purchase.
 * For example, if the download limit is 1 download per day, here we find the number of days since the payment took place.
 * If the download limit is 1 download per year, here we find the number of years since the payment took place.
 *
 * @since    1.0.0
 * @param    EDD_All_Access_Pass $all_access_pass An All Access Pass object.
 * @return   int - The number of download-limit-time-periods that have passed since the original payment.
 */
function edd_all_access_get_download_limit_time_periods_since_payment( $all_access_pass ) {

	// Default periods to 0.
	$periods_since_payment = 0;

	$start_date = date_create( '@' . $all_access_pass->start_time );
	$now        = date_create( 'now' );

	$interval = date_diff( $start_date, $now );

	$years_since_payment  = $interval->y;
	$months_since_payment = $interval->m;
	$days_since_payment   = $interval->days;
	$weeks_since_payment  = $days_since_payment / 7;

	switch ( $all_access_pass->download_limit_time_period ) {
		case 'per_day':
			$periods_since_payment = $days_since_payment;
			break;
		case 'per_week':
			$periods_since_payment = $weeks_since_payment;
			break;
		case 'per_month':
			$periods_since_payment = ( 12 * $years_since_payment ) + $months_since_payment;
			break;
		case 'per_year':
			$periods_since_payment = $years_since_payment;
			break;
		case 'per_period':
			$periods_since_payment = 0;
			break;
	}

	// We "floor" this because only need to know the number of *completed* "weeks" or "days" - not half days or fractions.
	return floor( $periods_since_payment );

}

/**
 * Get the period in which the downloads-used count was last reset to 0.
 * For example, if all access was just purchased and it allows 1 download per month, the last reset period is 0 - because it was never reset.
 * If 5 months have passed but the downloads-used count was last reset to 0 in month 1, the period returned here is 1.
 *
 * @since    1.0.0
 * @param    EDD_All_Access_Pass $all_access_pass An All Access Pass object.
 * @return   int - The time period in which the downloads-used counter was last reset to 0.
 */
function edd_all_access_get_download_limit_last_reset_period( $all_access_pass ) {

	// If the downloads-used counter has never been reset.
	if ( 0 === intval( $all_access_pass->downloads_used_last_reset ) ) {
		return 0;
	}

	// Default to 0 periods.
	$periods_between_payment_and_last_reset = 0;

	$start_date      = date_create( '@' . $all_access_pass->start_time );
	$last_reset_date = date_create( '@' . $all_access_pass->downloads_used_last_reset );

	$interval = date_diff( $start_date, $last_reset_date );

	$years_between_payment_and_last_reset  = $interval->y;
	$months_between_payment_and_last_reset = $interval->m;
	$days_between_payment_and_last_reset   = $interval->days;
	$weeks_between_payment_and_last_reset  = $days_between_payment_and_last_reset / 7;

	switch ( $all_access_pass->download_limit_time_period ) {
		case 'per_day':
			$periods_between_payment_and_last_reset = $days_between_payment_and_last_reset;
			break;
		case 'per_week':
			$periods_between_payment_and_last_reset = $weeks_between_payment_and_last_reset;
			break;
		case 'per_month':
			$periods_between_payment_and_last_reset = $months_between_payment_and_last_reset;
			break;
		case 'per_year':
			$periods_between_payment_and_last_reset = $years_between_payment_and_last_reset;
			break;
		case 'per_period':
			$periods_between_payment_and_last_reset = 0;
			break;
	}

	// We "floor" this because only need to know the number of *completed* "weeks" or "days" - not half days or fractions.
	return floor( $periods_between_payment_and_last_reset );

}

/**
 * Get the timestamp of when the previous period ended.
 * For example, if we are allowed 1 download per week and it's been 5 weeks since purchase, get the timestamp for the end of week 4.
 *
 * @since    1.0.0
 * @param    EDD_All_Access_Pass $all_access_pass An All Access Pass object.
 * @return   int - The timestamp for when the previous period ended.
 */
function edd_all_access_get_current_period_start_timestamp( $all_access_pass ) {

	$periods_since_payment = edd_all_access_get_download_limit_time_periods_since_payment( $all_access_pass );

	switch ( $all_access_pass->download_limit_time_period ) {
		case 'per_day':
			$time_string = 'days';
			break;
		case 'per_week':
			$time_string = 'weeks';
			break;
		case 'per_month':
			$time_string = 'months';
			break;
		case 'per_year':
			$time_string = 'years';
			break;
		case 'per_period':
			$time_string = 0;
			break;
	}

	if ( empty( $time_string ) ) {
		return false;
	}

	$previous_period_timestamp = strtotime( '+' . $periods_since_payment . ' ' . $time_string, $all_access_pass->start_time );

	return $previous_period_timestamp;

}

/**
 * Check if a specific customer has a valid, specific All Access Pass.
 *
 * @since    1.0.0
 * @param int    $user_id The ID of the user.
 * @param int    $download_id The ID of the All Access product.
 * @param int    $price_id The price_id (price variation) of the All Access product.
 * @param string $required_pass_status The status of the pass we want to check if the user has.
 * @return EDD_All_Access_Pass|false The All Access Pass if it exists or false if not.
 */
function edd_all_access_user_has_pass( $user_id, $download_id, $price_id = 0, $required_pass_status = 'active' ) {

	$has_pass = false;

	// Get the Customer.
	$customer = new EDD_Customer( $user_id, true );

	// If no customer was found (perhaps not logged in).
	if ( 0 === $customer->id ) {
		return $has_pass;
	}

	// Get the current customer's All Access Passes from the customer meta.
	$customer_all_access_passes = edd_all_access_get_customer_pass_objects( $customer );

	// If the customer has no All Access Passes in their customer meta.
	if ( empty( $customer_all_access_passes ) || ! is_array( $customer_all_access_passes ) ) {
		return $has_pass;
	}

	// Loop through each All Access Pass to see if any match the restricted_to and are active.
	foreach ( $customer_all_access_passes as $all_access_pass ) {

		// If this All Access Pass matches the required status, check if it is one of the restricted_to products.
		if ( $all_access_pass->status === $required_pass_status ) {

			// If this download is not the one which was purchased, continue.
			if ( intval( $download_id ) !== intval( $all_access_pass->download_id ) ) {
				continue;
			}

			// If the download does not have variable pricing, or the purchased pass is not tied to an ID, return the pass.
			if ( ! edd_has_variable_prices( $download_id ) || 0 === $all_access_pass->price_id || false === $price_id ) {
				return $all_access_pass;
			}

			// If the price ID is 0 (not a variable price) or the price ID matches the purchased price, it's a match.
			if ( intval( $price_id ) === intval( $all_access_pass->price_id ) ) {
				return $all_access_pass;
			}
		}
	}

	return $has_pass;
}

/**
 * By passing an All Access Pass object, check if a, higher, upgraded-to version of that All Access Pass is active for the customer.
 *
 * @since    1.0.0
 * @param    EDD_All_Access_Pass $prior_all_access_pass The All Access Pass Object to check.
 * @return   bool
 */
function edd_all_access_user_has_upgrade_of_prior_pass( $prior_all_access_pass ) {

	// If this pass is not a "prior", it hasn't been upgraded.
	if ( ! $prior_all_access_pass->is_prior_of ) {
		return false;
	}

	if ( 'upgraded' !== $prior_all_access_pass->status ) {
		return false;
	}

	// Get the current customer's All Access Passes from the customer meta.
	$customer_all_access_passes = $prior_all_access_pass->customer->get_meta( 'all_access_passes' );

	// Loop through the customer's All Access Passes to check if the upgraded version is still active.
	foreach ( $customer_all_access_passes as $purchased_download_id_price_id => $purchased_aa_data ) {

		// In case there happens to be an entry in the array without a numeric key.
		if ( empty( $purchased_download_id_price_id ) ) {
			continue;
		}

		if ( ! isset( $purchased_aa_data['payment_id'] ) || ! isset( $purchased_aa_data['download_id'] ) || ! isset( $purchased_aa_data['price_id'] ) ) {
			continue;
		}

		$all_access_pass = edd_all_access_get_pass( $purchased_aa_data['payment_id'], $purchased_aa_data['download_id'], $purchased_aa_data['price_id'] );

		// If this All Access Pass is still active and has been upgraded.
		if ( 'active' === $all_access_pass->status && $all_access_pass->prior_all_access_passes ) {

			// Loop through each of the prior All Access Passes attached to this and see if they match.
			foreach ( $all_access_pass->prior_all_access_passes as $prior_all_access_pass_id ) {

				// If the product being purchased matches one of the prior passes in this still-active/upgraded pass.
				if ( $prior_all_access_pass->id === $prior_all_access_pass_id ) {
					return true;
				}
			}
		}
	}

	return false;

}

/**
 * This function will return different HTML depending on the current state of the viewer.
 * If logged out it will return HTML containing a Buy Button for an All Access Pass and a Login Form below it.
 * If logged in without an active All Access Pass, it will output a Buy Button only.
 * If logged in with a valid All Access Pass, it will simply return false, as no output is needed.
 *
 * @since       1.0.0
 * @param       array $atts The args for the output.
 * @return      mixed String or Boolean. See description above.
 */
function edd_all_access_buy_or_login_form( $atts ) {

	$default_atts = array(
		'all_access_download_id' => false,
		'all_access_price_id'    => false,
		'all_access_sku'         => '',
		'all_access_price'       => true,
		'all_access_direct'      => '0',
		'all_access_btn_text'    => false,
		'all_access_btn_style'   => edd_get_option( 'button_style', 'button' ),
		'all_access_btn_color'   => edd_get_option( 'checkout_color', 'blue' ),
		'all_access_btn_class'   => 'edd-submit',
		'all_access_form_id'     => '',
		'class'                  => 'edd-aa-login-purchase-normal-mode',
		'popup_login'            => true,
		'buy_instructions'       => '',
		'login_instructions'     => '',
		'login_btn_style'        => 'text',
	);
	$atts         = wp_parse_args( $atts, $default_atts );

	// Set up some defaults.
	$login_purchase_area_already_output = false;
	$purchase_form_display_mode         = edd_get_option( 'all_access_purchase_form_display', 'normal-mode' );

	// If all_access_download_id is not an array, make it one.
	if ( ! is_array( $atts['all_access_download_id'] ) ) {
		$atts['all_access_download_id'] = array( $atts['all_access_download_id'] );
	}

	// If all_access_price_id is not an array, make it one.
	if ( ! is_array( $atts['all_access_price_id'] ) ) {
		$atts['all_access_price_id'] = array( $atts['all_access_price_id'] );
	}

	// Loop through each All Access product that might be output for sale.
	foreach ( $atts['all_access_download_id'] as $all_access_download_id ) {
		foreach ( $atts['all_access_price_id'] as $all_access_price_id ) {

			$customer_has_all_access_pass = edd_all_access_user_has_pass( get_current_user_id(), $all_access_download_id, $all_access_price_id );

			// If the customer has any of the All Access passes in question, there's no output needed so return false.
			if ( $customer_has_all_access_pass ) {
				return false;
			}
		}
	}

	// If we got this far, the customer does not have any valid All Access passes of the ones required. Therefore, output is required. Lets set it up.
	if ( ! $login_purchase_area_already_output ) {
		$login_purchase_area                = '<div class="edd-aa-login-purchase-area ' . esc_attr( $atts['class'] ) . ' ">';
		$login_purchase_area_already_output = true;
	}

		$login_purchase_area .= '<div class="edd-aa-buy-wrapper">';
	if ( ! empty( $atts['buy_instructions'] ) ) {
		$login_purchase_area .= '<span class="edd-aa-buy-instructions">' . $atts['buy_instructions'] . '</span>';
	}

	$login_purchase_area .= '<div class="edd-aa-buy-btns-wrapper">';

	// Check if the site owner has chosen to show a custom link to a custom URL - like a "pricing" page.
	$all_access_replace_aa_btns_with_custom_btn = edd_get_option( 'all_access_replace_aa_btns_with_custom_btn', 'normal_aa_btns' );
	$custom_btn_url                             = apply_filters( 'edd_all_access_custom_url_btn_url', edd_get_option( 'all_access_custom_url_btn_url', get_bloginfo( 'wpurl' ) ) );
	$custom_btn_text                            = apply_filters( 'all_access_custom_url_btn_text', edd_get_option( 'all_access_custom_url_btn_text', __( 'View Pricing', 'edd-all-access' ) ) );

	// If we should show a custom button (think "pricing") instead of the All Access purchase buttons.
	if ( 'custom_btn' === $all_access_replace_aa_btns_with_custom_btn ) {

		$class = implode( ' ', array( 'edd-all-access-btn', 'button', 'edd-button', $atts['all_access_btn_style'], $atts['all_access_btn_color'], trim( $atts['all_access_btn_class'] ) ) );

		$login_purchase_area .= '<div class="edd-aa-custom-btn-wrapper"><a href="' . esc_url( $custom_btn_url ) . '" class="' . esc_attr( $class ) . '">' . $custom_btn_text . '</a></div>';

	} else {

		// When necessary, remove schema data from button to avoid schema structure conflict with Add to Cart button.
		$hide_schema = apply_filters( 'edd_all_access_hide_schema', 'normal-plus-aa-mode' === $purchase_form_display_mode, $atts );
		if ( $hide_schema ) {
			add_filter( 'edd_add_schema_microdata', '__return_false' );
		}

		// Make sure each product is only shown once.
		$atts['all_access_download_id'] = array_unique( $atts['all_access_download_id'] );

		// Loop through each All Access product that might be output for sale.
		foreach ( $atts['all_access_download_id'] as $all_access_download_id ) {

			// Now we will output the purchase button. Logged in or out, we know they don't have All Access at this point.
			$all_access_buy_btn_args = array(
				'id'      => $all_access_download_id,
				'sku'     => $atts['all_access_sku'],
				'price'   => $atts['all_access_price'],
				'direct'  => $atts['all_access_direct'],
				'text'    => empty( $atts['all_access_btn_text'] ) ? get_the_title( $all_access_download_id ) : $atts['all_access_btn_text'],
				'style'   => $atts['all_access_btn_style'],
				'color'   => $atts['all_access_btn_color'],
				'class'   => $atts['all_access_btn_class'],
				'form_id' => $atts['all_access_form_id'],
			);

			// Override text only if not provided / empty.
			if ( ! $all_access_buy_btn_args['text'] ) {
				if ( intval( '1' ) === intval( $all_access_buy_btn_args['direct'] ) || 'true' === $all_access_buy_btn_args['direct'] ) {
					$all_access_buy_btn_args['text'] = edd_get_option( 'buy_now_text', __( 'Buy Now', 'edd-all-access' ) );
				} else {
					$all_access_buy_btn_args['text'] = edd_get_option( 'add_to_cart_text', __( 'Purchase All Access', 'edd-all-access' ) );
				}
			}

			// Override color if color == inherit.
			if ( isset( $all_access_buy_btn_args['color'] ) ) {
				$all_access_buy_btn_args['color'] = ( 'inherit' === $all_access_buy_btn_args['color'] ) ? '' : $all_access_buy_btn_args['color'];
			}

			if ( ! empty( $all_access_buy_btn_args['sku'] ) ) {

				$download = edd_get_download_by( 'sku', $all_access_buy_btn_args['sku'] );

				if ( $download ) {
					$all_access_buy_btn_args['download_id'] = $download->ID;
				}
			} elseif ( isset( $all_access_buy_btn_args['id'] ) ) {

				// Edd_get_purchase_link() expects the ID to be download_id since v1.3.
				$all_access_buy_btn_args['download_id'] = $all_access_buy_btn_args['id'];

				$download = edd_get_download( $all_access_buy_btn_args['download_id'] );

			}

			if ( $download instanceof EDD_Download && 'publish' === $download->post_status ) {
				$login_purchase_area .= '<div class="edd-aa-buy-btn-wrapper">' . edd_get_purchase_link( $all_access_buy_btn_args ) . '</div>';
			}
		}

		// Only add schema data back if we removed it.
		if ( $hide_schema ) {
			remove_filter( 'edd_add_schema_microdata', '__return_false' );
		}
	}

	$login_purchase_area .= '</div>'; // End the edd-aa-buy-btns-wrapper.

	$login_purchase_area .= '</div>'; // End the edd-aa-buy-wrapper.

	// If this user is not logged in, output a login form/button as well.
	if ( ! is_user_logged_in() ) {

		if ( ! $login_purchase_area_already_output ) {
			$login_purchase_area               .= '<div class="edd-aa-login-purchase-area">';
			$login_purchase_area_already_output = true;
		}

		// Show any error messages after login form submission.
		edd_print_errors();

		// Show a button to login which will open the popup modal.
		$login_purchase_area .= '<div class="edd-aa-login-wrapper">';
		if ( ! empty( $atts['login_instructions'] ) ) {
			$login_purchase_area .= '<span class="edd-aa-login-instructions">' . $atts['login_instructions'] . '</span>';
		}

		// If we should use the popup modal for the login.
		if ( filter_var( $atts['popup_login'], FILTER_VALIDATE_BOOLEAN ) ) {

			// Allow other plugins to prevent the loading of the sw modal popup script. Free Downloads will use this to prevent a double loading of jbox.
			$enqueue_from_all_access = apply_filters( 'edd_all_access_enqueue_jbox', true );

			// Get a list of required scripts before enqueuing the login modal scripts.
			$required_scripts = apply_filters( 'edd_all_access_required_before_modal', array( 'jquery', 'edd-all-access-jbox' ) );

			if ( $enqueue_from_all_access ) {
				wp_enqueue_style( 'edd-all-access-jbox', EDD_ALL_ACCESS_URL . 'assets/js/jBox/Source/jBox.css', array(), EDD_ALL_ACCESS_VER );
				wp_enqueue_script( 'edd-all-access-jbox', EDD_ALL_ACCESS_URL . 'assets/js/jBox/Source/jBox.min.js', array( 'jquery' ), EDD_ALL_ACCESS_VER, true );
			}

			// Enqueue the scripts which trigger the jbox to open.
			wp_enqueue_script( 'edd-all-access-login-modal', EDD_ALL_ACCESS_URL . 'assets/js/frontend/build/edd-aa-frontend-modal.js', $required_scripts, EDD_ALL_ACCESS_VER, true );

			// Output the login form into the footer.
			add_action( 'wp_footer', 'edd_all_access_output_login_modal' );

			// Show a button to login which will open the popup modal.
			if ( 'text' === $atts['login_btn_style'] ) {
				$login_purchase_area .= ' <a href="' . apply_filters( 'edd_all_access_login_url', wp_login_url() ) . '" class="edd-aa-login-link">' . __( 'Log In', 'edd-all-access' ) . '</a>';
			} else {
				$login_purchase_area .= ' <a href="' . apply_filters( 'edd_all_access_login_url', wp_login_url() ) . '" class="edd-aa-login-link edd-submit button">' . __( 'Log In', 'edd-all-access' ) . '</a>';
			}
		} else {
				// Show the login form.
				$login_purchase_area .= edd_login_form();
		}

			$login_purchase_area .= '</div>'; // Close edd-aa-login-wrapper.

	}

	if ( $login_purchase_area_already_output ) {
		$login_purchase_area .= '</div>'; // Close edd-aa-login-purchase-area.
	}

	return $login_purchase_area;
}

/**
 * Hook to footer to output login form in a modal popup box in the footer
 *
 * @since       1.0.0
 * @return      void
 */
function edd_all_access_output_login_modal() {

	global $edd_all_access_output_login_modal;

	// Prevent Loading login modal twice.
	if ( $edd_all_access_output_login_modal ) {
		return;
	}

	$edd_all_access_output_login_modal = true;

	// Allow the "display" value in wp_kses so that we can use it when hiding the login form by default.
	add_filter(
		'safe_style_css',
		'edd_all_access_allow_css_display_in_wp_kses'
	);

	echo wp_kses(
		'<div id="edd-aa-login-modal-content" style="display:none;">',
		array(
			'div' => array(
				'id'    => array(),
				'style' => array(),
			),
		)
	);

	// Allow the "display" value in wp_kses so that we can use it when hiding the login form by default.
	remove_filter(
		'safe_style_css',
		'edd_all_access_allow_css_display_in_wp_kses'
	);

		echo wp_kses(
			edd_login_form(),
			array(
				'form'     => array(
					'id'     => array(),
					'class'  => array(),
					'action' => array(),
					'method' => array(),
				),
				'fieldset' => array(),
				'legend'   => array(),
				'p'        => array(
					'class' => array(),
				),
				'label'    => array(
					'for' => array(),
				),
				'input'    => array(
					'name'  => array(),
					'id'    => array(),
					'class' => array(),
					'type'  => array(),
					'value' => array(),
				),
				'a'        => array(
					'href' => array(),
				),
			)
		);
	echo wp_kses_post( '</div>' );

}

/**
 * This function retrieves which variable prices are "relevant" to an All Access pass.
 * That is, which variable prices does the customer have access to because of their All Access pass?
 *
 * @since       1.0.10
 * @param       int $download_id The ID of the downloading being checked.
 * @return      array/boolean $relevant_variable_prices
 */
function edd_all_access_get_relevant_prices( $download_id ) {

	$variable_pricing_enabled = edd_has_variable_prices( $download_id );

	if ( ! $variable_pricing_enabled ) {
		return false;
	}

	$relevant_variable_prices = array();

	$variable_prices = edd_get_variable_prices( $download_id );

	// Loop through each variable price this product has.
	foreach ( $variable_prices as $variable_price_id => $variable_price_data ) {

		$variable_price_all_access_check_args = array(
			'download_id' => $download_id,
			'price_id'    => $variable_price_id,
		);

		$variable_price_all_access = edd_all_access_check( $variable_price_all_access_check_args );

		// If the customer has access to this variable price, add it to the array of relevant variable prices.
		if ( $variable_price_all_access['success'] ) {
			$relevant_variable_prices[] = $variable_price_id;
		}
	}

	return $relevant_variable_prices;
}

/**
 * This function returns whether all of the variable prices have a single file attached per price (true), or if any have more than 1 (false.
 *
 * @since       1.0.10
 * @param       int $download_id The ID of the downloading being checked.
 * @return      boolean
 */
function edd_all_access_single_file_per_price( $download_id ) {

	// To start, we'll assume there is a single file per price.
	$single_file_per_price = true;

	$variable_pricing_enabled = edd_has_variable_prices( $download_id );

	// If variable pricing is enabled, check how many files are attached to each price.
	if ( $variable_pricing_enabled ) {

		$variable_prices = edd_get_variable_prices( $download_id );

		foreach ( $variable_prices as $variable_price_id => $variable_price_data ) {

			$files_attached_to_price = edd_get_download_files( $download_id, $variable_price_id );

			$number_of_files_per_price = count( $files_attached_to_price );

			// If the number of files per price is greater than 1 for any of the prices, we'll set single file per price to false.
			if ( $number_of_files_per_price > 1 ) {
				$single_file_per_price = false;
			}
		}
	} else {

		// For non-variable priced products, check the single price to see how many files are attached.
		$files_attached_to_price = edd_get_download_files( $download_id );

		$number_of_files_per_price = count( $files_attached_to_price );

		// If the number of files per price is greater than 1.
		if ( $number_of_files_per_price > 1 ) {
			$single_file_per_price = false;
		}
	}

	return $single_file_per_price;
}

/**
 * This function returns the first available file ID linked to a price variation for a product
 *
 * @since       1.1
 * @param       int $download_id The ID of the downloading being checked.
 * @param       int $price_id The price ID of the downloading being checked.
 * @return      int
 */
function edd_all_access_get_first_file_id_for_price( $download_id, $price_id ) {

	$variable_pricing_enabled = edd_has_variable_prices( $download_id );

	// If this is not a single-priced product.
	if ( $variable_pricing_enabled ) {

		$files = edd_get_download_files( $download_id, $price_id );

	} else {

		$files = edd_get_download_files( $download_id );
	}

	return current( array_keys( $files ) );

}

/**
 * This function will take a UTC timestamp, convert it to the timezone of the WP store, and output it in the PHP date format provided.
 *
 * @since       1.1
 * @param       string $format The PHP date format to show the date in.
 * @param       string $timestamp The UTC timestamp being converted to the WP Timezone for output to the screen.
 * @return      string
 */
function edd_all_access_visible_date( $format, $timestamp ) {

	$date_object = date_create( '@' . $timestamp );

	$timezone = new DateTimeZone( edd_get_timezone_id() );

	if ( ! ( $date_object instanceof DateTime ) ) {
		return __( 'Invalid timestamp', 'edd-all-access' ) . ': ' . $timestamp;
	}

	if ( ! ( $timezone instanceof DateTimeZone ) ) {
		return __( 'Invalid timezone', 'edd-all-access' ) . ': ' . edd_get_timezone_id();
	}

	$date_object->setTimezone( $timezone );

	return $date_object->format( $format );

}

/**
 * This function will take a time in the WordPress timezone and convert it to a UTC time. This is useful
 * for times generated by javascript which are being passed to ajax, so that you can do math on the dates in UTC.
 *
 * @since       1.1
 * @param       string $wp_timestamp The WP timestamp being converted to the UTC Timezone.
 * @return      string
 */
function edd_all_access_wp_timestamp_to_utc_timestamp( $wp_timestamp ) {

	// Get the timezone set for the WordPress.
	$wp_timezone  = new DateTimeZone( edd_get_timezone_id() );
	$utc_timezone = new DateTimeZone( 'UTC' );

	// Set up a date object using the timestamp, and convert it to the WP timezone.
	$wp_dt = new DateTime( '@' . $wp_timestamp );
	$wp_dt->setTimezone( $wp_timezone );

	// Set up a date object using the timestamp, and convert it to the UTC timezone.
	$utc_dt = new DateTime( '@' . $wp_timestamp );
	$wp_dt->setTimezone( $utc_timezone );

	// Find the differences between the timezones at that timestamp.
	$offset = $wp_timezone->getOffset( $wp_dt ) - $utc_timezone->getOffset( $utc_dt );

	// Get the UTC timestamp that corresponds to the visible.
	$correct_utc_timestamp = $wp_timestamp - $offset;

	return $correct_utc_timestamp;

}

/**
 * Get the timestamp of when an All Access Pass was purchased in UTC
 *
 * @since       1.1
 * @param       EDD_All_Access_Pass $all_access_pass The All Access pass in question.
 * @return      int
 */
function edd_all_access_get_aap_purchase_timestamp( $all_access_pass ) {

	return edd_all_access_get_payment_utc_timestamp( $all_access_pass->payment );
}

/**
 * Get the timestamp of when a payment or order in UTC
 *
 * @since       1.1
 * @param       EDD_Payment $payment_object The EDD_Payment object we are getting the UTC timestamp for.
 * @return      int
 */
function edd_all_access_get_payment_utc_timestamp( $payment_object ) {

	if ( ! ( $payment_object instanceof EDD_Payment ) ) {
		return 0;
	}

	if ( function_exists( 'edd_get_order' ) ) {
		// If we are on Easy Digital Downloads version 3.0 or later.
		$edd_order = edd_get_order( $payment_object->ID );

		return strtotime( $edd_order->date_created );
	} else {
		$edd_payment_post = get_post( $payment_object->ID );

		if ( ! ( $edd_payment_post instanceof WP_Post ) ) {
			return 0;
		}

		return strtotime( $edd_payment_post->post_date_gmt );
	}

}

/**
 * Retrieves the payment statuses that count as "valid" for All Access.
 *
 * @since 1.2
 *
 * @return array
 */
function edd_all_access_valid_order_statuses() {
	/**
	 * Filters the statuses.
	 *
	 * @since 1.0
	 */
	return (array) apply_filters( 'edd_all_access_valid_statuses', array( 'publish', 'complete', 'partially_refunded' ) );
}

/**
 * Returns the available duration unit options.
 *
 * @since 1.2
 *
 * @return array
 */
function edd_all_access_get_duration_unit_options() {
	$duration_units = array(
		'never' => __( 'Never Expires', 'edd-all-access' ),
		'year'  => __( 'Year(s)', 'edd-all-access' ),
		'month' => __( 'Month(s)', 'edd-all-access' ),
		'week'  => __( 'Week(s)', 'edd-all-access' ),
		'day'   => __( 'Day(s)', 'edd-all-access' ),
	);

	/**
	 * Filters the available duration options.
	 *
	 * @since 1.0
	 *
	 * @param array $duration_units
	 */
	return (array) apply_filters( 'edd_all_access_duration_unit_metabox_options', $duration_units );
}

/**
 * Returns the available download limit time periods.
 *
 * @since 1.2
 *
 * @return array
 */
function edd_all_access_get_download_limit_periods() {
	$download_limit_periods = array(
		'per_day'    => __( 'X downloads per day', 'edd-all-access' ),
		'per_week'   => __( 'X downloads per week', 'edd-all-access' ),
		'per_month'  => __( 'X downloads per month', 'edd-all-access' ),
		'per_year'   => __( 'X downloads per year', 'edd-all-access' ),
		'per_period' => __( 'X downloads total', 'edd-all-access' ),
	);

	/**
	 * Filters the available download limit time periods.
	 *
	 * @since 1.0
	 *
	 * @param array $download_limit_periods
	 */
	return (array) apply_filters( 'edd_all_access_download_limit_options', $download_limit_periods );
}

/**
 * Gets the customer's All Access Passes.
 *
 * @since 1.2
 * @param \EDD_Customer $customer
 * @return array
 */
function edd_all_access_get_customer_passes( \EDD_Customer $customer ) {
	$passes = $customer->get_meta( 'all_access_passes' );

	// If no all access passes have been purchased by this customer and their array of passes is empty, declare the variable as an array.
	if ( empty( $passes ) || ! is_array( $passes ) ) {
		$passes = array();
	}

	return $passes;
}

/**
 * Gets the pass objects for a specific customer.
 *
 * @since 1.1.11
 * @param \EDD_Customer $customer  The EDD Customer object.
 * @return array
 */
function edd_all_access_get_customer_pass_objects( \EDD_Customer $customer ) {
	static $customer_all_access_passes;
	if ( ! is_null( $customer_all_access_passes ) ) {
		// We've already found passes for this customer and parsed them on this page load, so we can return them.
		return $customer_all_access_passes;
	}

	$passes = $customer->get_meta( 'all_access_passes' );
	if ( empty( $passes ) || ! is_array( $passes ) ) {
		// We did not find any pass data for the customer, so set the static variable to empty and return it.
		$customer_all_access_passes = array();

		return $customer_all_access_passes;
	}
	// Defines a static variable so we know whether this function has run.
	static $has_run;

	// Sets the current customer metadata as a hashed string.
	$baseline_meta = md5( wp_json_encode( $passes ) );
	$pass_objects  = array();
	foreach ( $passes as $purchased_download_id_price_id => $purchased_aa_data ) {

		if ( ! isset( $purchased_aa_data['payment_id'] ) || ! isset( $purchased_aa_data['download_id'] ) || ! isset( $purchased_aa_data['price_id'] ) ) {
			continue;
		}

		// Set up an All Access Pass Object for this.
		$pass = edd_all_access_get_pass( $purchased_aa_data['payment_id'], $purchased_aa_data['download_id'], $purchased_aa_data['price_id'] );
		if ( 'invalid' !== $pass->status ) {
			$pass_objects[] = $pass;
		}
	}
	// If a pass expired and was renewed/regenerated, the customer meta will have changed at this point.
	$possibly_new_meta = md5( wp_json_encode( $customer->get_meta( 'all_access_passes' ) ) );

	// If this function has already run, or the metadata is unchanged, return the found passes.
	if ( ! empty( $has_run ) || hash_equals( $baseline_meta, $possibly_new_meta ) ) {
		$customer_all_access_passes = $pass_objects;

		return $pass_objects;
	}

	/**
	 * Set the static variable to true so that the function will not run again if the meta values
	 * continue to not match.
	 * @link https://github.com/awesomemotive/edd-all-access/issues/485
	 */
	$has_run = true;

	return edd_all_access_get_customer_pass_objects( $customer );
}

/**
 * Instantiates and activates a pass.
 *
 * @since 1.2
 * @param int      $order_id   The order/payment ID.
 * @param int      $product_id The download/product ID.
 * @param null|int $price_id   The purchased price ID.
 * @return mixed
 */
function edd_all_access_get_and_activate_pass( $order_id, $product_id, $price_id ) {
	$all_access_pass = new EDD_All_Access_Pass( $order_id, $product_id, absint( $price_id ) );

	// If it is required, activate this All Access Pass.
	return $all_access_pass->maybe_activate();
}

/**
 * Returns if redownloads are enabled.
 *
 * @since 1.2.2
 * return bool
 */
function edd_all_access_allow_redownload() {
	if ( version_compare( EDD_VERSION, '3.0', '<' ) ) {
		return false;
	}

	return (bool) edd_get_option( 'all_access_allow_redownload', false );
}

/**
 * Helper function to get a pass based on payment id, download id, and price id.
 *
 * @since 1.2.4.2 - Replaces the oddly named function EDD_All_Access_Pass that is the same as the main class.
 *
 * @param    int $payment_id The ID of the payment attached to the All Access Pass.
 * @param    int $download_id The ID of the download attached to the All Access Pass.
 * @param    int $price_id The ID of the price attached to the All Access Pass.
 *
 * @return   object - an EDD_All_Access_Pass object
 */
function edd_all_access_get_pass( $payment_id = 0, $download_id = 0, $price_id = 0 ) {
	return edd_all_access()->object_cache->get_pass( $payment_id, $download_id, $price_id );
}

/**
 * This function is useful for situations like an archive page where you might instatiate the same All Access Pass object many times in a single page load.
 * It caches the object into a global variable and uses that, rather than completely running the instantiation each time.
 * This name remains in Camel Case format to support backwards compatible calls.
 *
 * @since    1.0.0
 * @since    1.2.4.2 - Moved to the helper-functions.php and is now just a pass through.
 *
 * @param    int $payment_id The ID of the payment attached to the All Access Pass.
 * @param    int $download_id The ID of the download attached to the All Access Pass.
 * @param    int $price_id The ID of the price attached to the All Access Pass.
 * @return   object - an EDD_All_Access_Pass object
 */
function EDD_All_Access_Pass( $payment_id = 0, $download_id = 0, $price_id = 0 ) { //phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	return edd_all_access_get_pass( $payment_id, $download_id, $price_id );
}

/**
 * Helper function to check if All Access is enabled for a download.
 *
 * @since 1.2.5
 * @param int $download_id
 * @return bool
 */
function edd_all_access_enabled_for_download( $download_id ) {
	return 'all_access' === edd_get_download_type( $download_id );
}
