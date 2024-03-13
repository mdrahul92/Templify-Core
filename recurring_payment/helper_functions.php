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


function get_custom_times( $post_id ) {
	global $post;

	$times = get_post_meta( $post_id, 'edd_custom_times', true );

	if ( $times ) {
		return $times;
	}

	return 0;
}


function get_custom_signup_fee( $post_id ) {
	global $post;

	$signup_fee = get_post_meta( $post_id, 'edd_custom_signup_fee', true );

	if ( $signup_fee ) {
		return $signup_fee;
	}

	return 0;
}

function is_price_recurring( $download_id, $price_id ) {

	global $post;

	if ( empty( $download_id ) && is_object( $post ) ) {
		$download_id = $post->ID;
	}

	$prices = get_post_meta( $download_id, 'edd_variable_prices', true );
	$period = get_period( $price_id, $download_id );

	if ( isset( $prices[ $price_id ]['recurring'] ) && 'never' != $period ) {
		return true;
	}

	return false;

}


function get_period( $price_id, $post_id = null ) {
	global $post;

	$period = 'never';

	if ( ! $post_id && is_object( $post ) ) {
		$post_id = $post->ID;
	}

	$prices = get_post_meta( $post_id, 'edd_variable_prices', true );

	if ( isset( $prices[ $price_id ]['period'] ) ) {
		$period = $prices[ $price_id ]['period'];
	}

	return $period;
}


function get_custom_period( $post_id ) {
	global $post;

	$period = get_post_meta( $post_id, 'edd_custom_period', true );

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


function get_times( $price_id, $post_id = null ) {
	global $post;

	if ( empty( $post_id ) && is_object( $post ) ) {
		$post_id = $post->ID;
	}

	$prices = get_post_meta( $post_id, 'edd_variable_prices', true );

	if ( isset( $prices[ $price_id ]['times'] ) ) {
		return intval( $prices[ $price_id ]['times'] );
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



function get_signup_fee( $price_id, $post_id = null ) {
	global $post;

	if ( empty( $post_id ) && is_object( $post ) ) {
		$post_id = $post->ID;
	}

	$prices = get_post_meta( $post_id, 'edd_variable_prices', true );

	$fee = isset( $prices[ $price_id ]['signup_fee'] ) ? $prices[ $price_id ]['signup_fee'] : 0;
	$fee = apply_filters( 'edd_recurring_signup_fee', $fee, $price_id, $prices );
	if ( $fee ) {
		return floatval( $fee );
	}

	return 0;
}


function is_custom_recurring( $download_id = 0 ) {

	global $post;

	if ( empty( $download_id ) && is_object( $post ) ) {
		$download_id = $post->ID;
	}

	if ( get_post_meta( $download_id, 'edd_custom_recurring', true ) == 'yes' ) {
		return true;
	}

	return false;

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

function get_pretty_singular_subscription_frequency( $period ) {
	$frequency = '';
	//Format period details
	switch ( $period ) {
		case 'day' :
			$frequency = __( 'Day', 'edd-recurring' );
			break;
		case 'week' :
			$frequency = __( 'Week', 'edd-recurring' );
			break;
		case 'month' :
			$frequency = __( 'Month', 'edd-recurring' );
			break;
		case 'quarter' :
			$frequency = __( 'Quarter', 'edd-recurring' );
			break;
		case 'semi-year' :
			$frequency = __( 'Semi-Year', 'edd-recurring' );
			break;
		case 'year' :
			$frequency = __( 'Year', 'edd-recurring' );
			break;
		default :
			$frequency = apply_filters( 'edd_recurring_singular_subscription_frequency', $frequency, $period );
			break;
	}

	return $frequency;

}



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



function edd_all_access_get_customer_passes( \EDD_Customer $customer ) {
	$passes = $customer->get_meta( 'all_access_passes' );

	// If no all access passes have been purchased by this customer and their array of passes is empty, declare the variable as an array.
	if ( empty( $passes ) || ! is_array( $passes ) ) {
		$passes = array();
	}

	return $passes;
}



function get_renewal_payment_ids() {

	// Check if we've already run this getter before.
	if ( ! is_null( $this->renewal_payment_ids ) ) {
		return $this->renewal_payment_ids;
	}

	$renewal_payment_ids = array();

	if ( ! empty( $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['renewal_payment_ids'] ) ) {
		$renewal_payment_ids = $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['renewal_payment_ids'];
	}

	// Get the payment statuses that count as "valid" for All Access.
	$valid_payment_statuses = edd_all_access_valid_order_statuses();

	if ( empty( $renewal_payment_ids ) ) {
		$start_date = strtotime( $this->payment->date ) + 1;
		$args       = array(
			'fields' => 'id',
			'status' => $valid_payment_statuses,
		);
		if ( function_exists( 'edd_get_orders' ) ) {
			$args['customer_id'] = $this->payment->customer_id;
			$args['type']        = 'sale';
			$args['id__not_in']  = array( $this->payment_id );
			$args['date_query']  = array(
				'relation' => 'AND',
				array(
					'column' => 'date_created',
					'after'  => date( 'Y-m-d H:i:s', $start_date ),
				),
			);
			$renewal_payment_ids = edd_get_orders( $args );
		} else {
			$args['customer']    = $this->payment->customer_id;
			$args['start_date']  = date( 'Y-m-d H:i:s', $start_date );
			$args['end_date']    = date( 'Y-m-d H:i:s', strtotime( 'now' ) );
			$renewal_payment_ids = edd_get_payments( $args );
		}
	}

	// Declare the new array which we'll return here.
	$this->renewal_payment_ids = array();

	if ( ! empty( $renewal_payment_ids ) ) {
		// Check the status of each renewal payment to make sure they haven't been refunded, deleted, assigned to another customer, etc.
		foreach ( $renewal_payment_ids as $renewal_payment_id ) {
			if ( $this->is_renewal_payment_valid( $renewal_payment_id, $valid_payment_statuses ) ) {
				$this->renewal_payment_ids[] = $renewal_payment_id;
			}
		}
	}

	return $this->renewal_payment_ids;
}

function set_and_return_status( $status = '' ) {
	$status = trim( $status );

	// If this method is called without a status or a non-string value, set it to invalid and return.
	if ( empty( $status ) || ! is_string( $status ) ) {
		edd_debug_log( 'AA: Set status failed due to empty or non-string value. Setting pass to invalid' );
		$status = 'invalid';
	}

	/**
	 * Filter the status of an All Access Pass.
	 *
	 * @param string              $status The status of the All Access Pass.
	 * @param EDD_All_Access_Pass $this   The EDD_All_Access_Pass object.
	 */
	$status = apply_filters( 'edd_all_access_pass_status', $status, $this );

	if ( $this->status !== $status ) {
		if ( ! empty( $this->id ) && is_string( $this->id ) ) {
			do_action( 'edd_all_access_status_changed', $this );
		}
	}

	$this->status = $status;

	return $this->status;
}



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

function edd_all_access_get_aap_purchase_timestamp( $all_access_pass ) {

	return edd_all_access_get_payment_utc_timestamp( $all_access_pass->payment );
}


function cart_contains_recurring() {

	$contains_recurring = false;

	$cart_contents = edd_get_cart_contents();
	foreach ( $cart_contents as $cart_item ) {

		if ( isset( $cart_item['options'] ) && isset( $cart_item['options']['recurring'] ) ) {

			$contains_recurring = true;
			break;

		}

	}

	return $contains_recurring;
}
