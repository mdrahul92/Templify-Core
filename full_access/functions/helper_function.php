<?php
function edd_full_access_get_full_access_downloads( $force_lookup = false ) {

// Check for the All Access products option.
$edd_full_access_products = get_option( 'edd_full_access_products' );
$current_hash            = md5( json_encode( $edd_full_access_products ) );

// If the option isn't set, query for published downloads which have AA enabled.
if ( false === $edd_full_access_products || true === $force_lookup ) {
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
                    'key'     => '_edd_full_access_enabled',
                    'value'   => '1',
                    'compare' => '=',
                ),
                array(
                    'key'     => '_edd_product_type',
                    'value'   => 'full_access',
                    'compare' => '=',
                ),
            ),
        )
    );
    $edd_full_access_products = $query->posts;
}

$edd_full_access_downloads = is_array( $edd_full_access_products ) ? array_unique( $edd_full_access_products ) : array();

$new_hash = md5( json_encode( $edd_full_access_downloads ) );

// Only update the database if something has changed.
if ( ! hash_equals( $current_hash, $new_hash ) ) {
    // Set the products option.
    update_option( 'edd_full_access_products', $edd_full_access_downloads );
}

return apply_filters( 'edd_full_access_downloads', $edd_full_access_downloads );

}


function edd_full_access_get_download_limit_periods(){
$download_limit_periods = array(
    'per_day'    => __( 'X downloads per day', 'templify-full-access'),
    'per_week'   => __( 'X downloads per week', 'templify-full-access'),
    'per_month'  => __( 'X downloads per month', 'templify-full-access'),
    'per_year'   => __( 'X downloads per year', 'templify-full-access'),
    'per_period' => __( 'X downloads total', 'templify-full-access'),
);

/**
 * Filters the available download limit time periods.
 *
 * @since 1.0
 *
 * @param array $download_limit_periods
 */
return (array) apply_filters( 'edd_full_access_download_limit_options', $download_limit_periods );

}



function edd_full_access_get_duration_unit_options() {
$duration_units = array(
    'never' => __( 'Never Expires', 'templify-full-access' ),
    'year'  => __( 'Year(s)', 'templify-full-access' ),
    'month' => __( 'Month(s)', 'templify-full-access' ),
    'week'  => __( 'Week(s)', 'templify-full-access' ),
    'day'   => __( 'Day(s)', 'templify-full-access' ),
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
 * Check if a specific customer has a valid, specific Full Access License.
 *
 * @since    1.0.0
 * @param int    $user_id The ID of the user.
 * @param int    $download_id The ID of the Full Access product.
 * @param int    $price_id The price_id (price variation) of the Full Access product.
 * @param string $required_pass_status The status of the pass we want to check if the user has.
 * @return EDD_Full_Access_Pass|false The Full Access License if it exists or false if not.
 */
function edd_full_access_user_has_pass( $user_id, $download_id, $price_id = 0, $required_pass_status = 'active' ) {

	$has_pass = false;

	// Get the Customer.
	$customer = new EDD_Customer( $user_id, true );

	// If no customer was found (perhaps not logged in).
	if ( 0 === $customer->id ) {
		return $has_pass;
	}

	// Get the current customer's Full Access Licenses from the customer meta.
	$customer_all_access_passes = edd_full_access_get_customer_pass_objects( $customer );

	// If the customer has no Full Access Licenses in their customer meta.
	if ( empty( $customer_all_access_passes ) || ! is_array( $customer_all_access_passes ) ) {
		return $has_pass;
	}

	// Loop through each Full Access License to see if any match the restricted_to and are active.
	foreach ( $customer_all_access_passes as $all_access_pass ) {

		// If this Full Access License matches the required status, check if it is one of the restricted_to products.
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
 * Gets the pass objects for a specific customer.
 *
 * @since 1.1.11
 * @param \EDD_Customer $customer  The EDD Customer object.
 * @return array
 */
function edd_full_access_get_customer_pass_objects( \EDD_Customer $customer ) {
	static $customer_all_access_passes;
	if ( ! is_null( $customer_all_access_passes ) ) {
		// We've already found passes for this customer and parsed them on this page load, so we can return them.
		return $customer_all_access_passes;
	}

	$passes = $customer->get_meta( 'full_access_licenses' );
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

		// Set up an Full Access License Object for this.
		$pass = edd_all_access_get_pass( $purchased_aa_data['payment_id'], $purchased_aa_data['download_id'], $purchased_aa_data['price_id'] );
		if ( 'invalid' !== $pass->status ) {
			$pass_objects[] = $pass;
		}
	}
	// If a pass expired and was renewed/regenerated, the customer meta will have changed at this point.
	$possibly_new_meta = md5( wp_json_encode( $customer->get_meta( 'full_access_licenses' ) ) );

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

	return edd_full_access_get_customer_pass_objects( $customer );
}

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
