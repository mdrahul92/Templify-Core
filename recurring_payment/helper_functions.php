<?php
function periods() {
	$periods = array(
		'day'       => _x( 'Daily', 'Billing period', 'edd-recurring' ),
		'week'      => _x( 'Weekly', 'Billing period', 'edd-recurring' ),
		'month'     => _x( 'Monthly', 'Billing period', 'edd-recurring' ),
		'quarter'   => _x( 'Quarterly', 'Billing period', 'edd-recurring' ),
		'semi-year' => _x( 'Semi-Yearly', 'Billing period', 'edd-recurring' ),
		'year'      => _x( 'Yearly', 'Billing period', 'edd-recurring' ),
	);

	$periods = apply_filters( 'edd_recurring_periods', $periods );

	return $periods;
}


function get_period_single( $post_id ) {
	global $post;

	$period = get_post_meta( $post_id, 'edd_period', true );

	if ( $period ) {
		return $period;
	}

	return 'never';
}


function is_recurring( $download_id = 0 ) {

	global $post;

	if ( empty( $download_id ) && is_object( $post ) ) {
		$download_id = $post->ID;
	}

	if ( get_post_meta( $download_id, 'edd_recurring', true ) == 'yes' ) {
		return true;
	}

	return false;

}


function get_times_single( $post_id ) {
	global $post;

	$times = get_post_meta( $post_id, 'edd_times', true );

	if ( $times ) {
		return $times;
	}

	return 0;
}

function has_free_trial( $download_id = 0, $price_id = null ) {

	global $post;

	if ( empty( $download_id ) && is_object( $post ) ) {
		$download_id = $post->ID;
	}

	$prices = edd_get_variable_prices( $download_id );
	if ( ( ! empty( $price_id ) || 0 === (int) $price_id ) && is_array( $prices ) && ! empty( $prices[ $price_id ]['trial-quantity'] ) ) {
		$trial = array();
		$trial['quantity'] = $prices[ $price_id ]['trial-quantity'];
		$has_trial = ( $trial > 0 ? true : false );

		return apply_filters( 'edd_recurring_download_has_free_trial', (bool) $has_trial, $download_id, $price_id );

	} else {
		$has_trial = get_post_meta( $download_id, 'edd_trial_period', true );

		return apply_filters( 'edd_recurring_download_has_free_trial', (bool) $has_trial, $download_id );
	}
}



function get_signup_fee_single( $post_id ) {
	global $post;

	$signup_fee = get_post_meta( $post_id, 'edd_signup_fee', true );

	if ( $signup_fee ) {
		return $signup_fee;
	}

	return 0;
}



function singular_periods() {
    $periods = array(
        'day'       => _x( 'Day(s)', 'Billing period', 'edd-recurring' ),
        'week'      => _x( 'Week(s)', 'Billing period', 'edd-recurring' ),
        'month'     => _x( 'Month(s)', 'Billing period', 'edd-recurring' ),
        'quarter'   => _x( 'Quarter(s)', 'Billing period', 'edd-recurring' ),
        'semi-year' => _x( 'Semi-Year(s)', 'Billing period', 'edd-recurring' ),
        'year'      => _x( 'Year(s)', 'Billing period', 'edd-recurring' ),
    );

    $periods = apply_filters( 'edd_recurring_singular_periods', $periods );

    return $periods;
}

function get_trial_period( $post_id, $price_id = null ) {
    global $post;

    $period = false;

    if( has_free_trial( $post_id, $price_id ) ) {

        $default = array(
            'quantity' => 1,
            'unit'     => 'month',
        );

        $prices = edd_get_variable_prices( $post_id );
        if ( ( ! empty( $price_id ) || 0 === (int) $price_id ) && is_array( $prices ) && ! empty( $prices[ $price_id ]['trial-quantity'] ) && ! empty( $prices[ $price_id ]['trial-unit'] ) ) {
            $period['quantity'] = $prices[ $price_id ]['trial-quantity'];
            $period['unit'] = $prices[ $price_id ]['trial-unit'];
        } else {
            $period = (array) get_post_meta( $post_id, 'edd_trial_period', true );
            $period = wp_parse_args( $period, $default );
            $period['quantity'] = absint( $period['quantity'] );
            $period['quantity'] = $period['quantity'] < 1 ? 1 : $period['quantity'];
        }
    }

    return $period;

}
