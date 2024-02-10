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
    'per_day'    => __( 'X downloads per day', 'templify-full-access' ),
    'per_week'   => __( 'X downloads per week', 'templify-full-access' ),
    'per_month'  => __( 'X downloads per month', 'templify-full-access' ),
    'per_year'   => __( 'X downloads per year', 'templify-full-access' ),
    'per_period' => __( 'X downloads total', 'templify-full-access' ),
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
return (array) apply_filters( 'edd_full_access_duration_unit_metabox_options', $duration_units );
}
?>
