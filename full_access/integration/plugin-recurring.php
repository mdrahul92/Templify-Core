<?php
/**
 * Integration functions to make All Access compatible with EDD Recurring.
 * How it works: If an All Access pass is set to sync with Recurring's expiration, it will expire when the customer's recurring renews.
 * Note that the All Access still actually expires (so expiration functions still fire - like commission payouts).
 * The renewal payment actually starts a new All Access period - though the user wouldn't notice anything changing on their end.
 *
 * Changing a renewal period: If you decide to edit the renewal period of your recurring All Access product, existing customers with existing subscriptions will be "grandfathered".
 * This means their All Access periods will still expire with their subscriptions - as EDD recurring also grandfathers in existing subscriptions. They do not change if you edit the recurring period.
 * Any NEW purchases after editing the product's recurring period will use that new recurring/expiration period for both All Access and recurring.
 *
 * You can use this to sell All Access passes with variable prices that renew at different times. For example, a variable price for monthly or yearly.
 *
 * @package     EDD\EDDAllAccess\Functions
 * @since       1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Integrates EDD All Access with the EDD Recurring extension
 *
 * @since 1.0.0
 */
class EDD_All_Access_Recurring {

	/**
	 * Get things started
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function __construct() {

	

		// Include the "Sync with Recurring expiration" option in the Post Meta for All Access duration.
		add_filter( 'edd_all_access_duration_unit_metabox_options', array( $this, 'edd_all_access_recurring_duration_option' ) );

		// Include the "Sync with Recurring expiration" option in the Customer Meta for an All Access Pass's duration.
		add_filter( 'edd_all_access_duration_unit_customer_options', array( $this, 'edd_all_access_recurring_duration_customer_option' ), 10, 4 );

		// Include 'edd_subscription' in the array of acceptable payment statuses for All Access.
		add_filter( 'edd_all_access_valid_statuses', array( $this, 'edd_all_access_recurring_valid_payment_statuses' ) );

		// Filter the expiration time function so that All Access products set to sync with recurring expire when they should.
		add_filter( 'edd_all_access_get_expiration_time', array( $this, 'sync_expiration_time_with_recurring' ), 10, 2 );

		// Filter the duration String used to represent the duration. For example, this is used in the edd_all_access_passes shortcode to display "1 Year" for All Access Pass Duration.
		add_filter( 'edd_all_access_duration_string', array( $this, 'duration_string' ), 10, 2 );

		// Fire the expiration function for All Access Passes.
		add_action( 'edd_subscription_post_renew', array( $this, 'check_expirations_post_renew' ), 10, 3 );

		add_action( 'edd_all_access_rcp_migrate_subscription', array( $this, 'migrate_rcp_subscriptions' ), 10, 3 );

		// If the site owner has enabled the "Prevent downloads unless active subscription" option in EDD recurring, override that if the AAP allows downloading of the product in question.
		add_filter( 'edd_recurring_download_has_access', array( $this, 'edd_recurring_download_has_access' ), 10, 4 );

		add_filter( 'edd_all_access_pass_would_be_end_time', array( $this, 'check_if_pass_would_expire' ), 10, 4 );

		add_filter( 'edd_purchase_download_form', array( $this, 'renew_button' ), 100, 2 );
	}

	/**
	 * If someone is migrating their users from Restrict Content Pro to EDD All Access, here's we'll migrate their subscription to EDD Recurring
	 *
	 * @since    1.0.0
	 * @param    string $subscription_profile_id The profile id for the subscription being migrated.
	 * @param    string $edd_payment The EDD Payment which will be used as the parent payment.
	 * @param    int    $all_access_product_id The ID of the All Access Pass whose subscriptions are being migrated from RCP to EDD Recurring.
	 * @return   void
	 */
	public function migrate_rcp_subscriptions( $subscription_profile_id, $edd_payment, $all_access_product_id ) {

		$recurring = is_recurring( $all_access_product_id );

		// If recurring is not enabled for the All Access product we are migrating to, don't migrate/create the subscription.
		if ( ! $recurring ) {
			return;
		}

		$times        = get_times_single( $all_access_product_id );
		$period       = get_period_single( $all_access_product_id );
		$has_trial    = has_free_trial( $all_access_product_id );
		$trial_period = get_trial_period( $all_access_product_id );
		$signup_fee   = get_signup_fee_single( $all_access_product_id );

		$subscriber = new EDD_Recurring_Subscriber( $edd_payment->customer_id );

		// Set up the details for the new EDD subscription.
		$args = array(
			'expiration'        => $subscriber->get_new_expiration( $all_access_product_id, 0, $trial_period ),
			'created'           => $edd_payment->date,
			'status'            => 'active',
			'profile_id'        => $subscription_profile_id,
			'transaction_id'    => $edd_payment->transaction_id,
			'initial_amount'    => $edd_payment->total,
			'recurring_amount'  => $edd_payment->total,
			'bill_times'        => $times,
			'period'            => $period,
			'parent_payment_id' => $edd_payment->ID,
			'product_id'        => $all_access_product_id,
			'customer_id'       => $edd_payment->customer_id,
		);

		// Set up the subscription object.
		$new_subscription = new EDD_Subscription();
		$new_subscription->create( $args );

		// Let the edd payment know it is a recurring one.
		$edd_payment->update_meta( '_edd_subscription_payment', true );

	}

	/**
	 * Add "Sync with Recurring expiration" as an expiration option for All Access.
	 *
	 * @since    1.0.0
	 * @param    array $all_access_length_options Array of expiration options for All Access.
	 * @return   array $all_access_length_options Array (modified) of expiration options for All Access
	 */
	public function edd_all_access_recurring_duration_option( $all_access_length_options ) {

		// Add an option to sync the expiration with EDD Recurring.
		$all_access_length_options['edd_recurring'] = __( 'Sync with Recurring expiration', 'edd-all-access' );

		return $all_access_length_options;
	}

	/**
	 * Add "Sync with Recurring expiration" as an expiration option for All Access on the Customer Meta (Single All Access Pass Page).
	 *
	 * @since    1.0.0
	 * @param    array       $all_access_length_options Array of expiration options for All Access.
	 * @param    EDD_Payment $payment The EDD Payment where the All Access Pass was purchased.
	 * @param    int         $download_id The product id which was purchased, and is an All Access product.
	 * @param    int         $price_id The variable price id which was purchased, and is an All Access product.
	 * @return   array $all_access_length_options Array (modified) of expiration options for All Access
	 */
	public function edd_all_access_recurring_duration_customer_option( $all_access_length_options, $payment, $download_id, $price_id ) {

		$all_access_pass = edd_all_access_get_pass( $payment->ID, $download_id, $price_id );

		// Check if this AAP has a license attached to it.
		$possibly_attached_license = edd_all_access_software_licensing_get_license_from_aa_pass( $all_access_pass );

		// If there is a license attached, we use that over the subscription.
		if ( $possibly_attached_license ) {
			$all_access_length_options['edd_recurring'] = __( 'Sync with License expiration', 'edd-all-access' );
			return $all_access_length_options;
		}

		// Check if this payment has a subscription attached to it.
		$payment_has_subscription = edd_get_payment_meta( $payment->ID, '_edd_subscription_payment' );

		if ( ! $payment_has_subscription ) {
			// Add an option to sync the expiration with EDD Recurring.
			$all_access_length_options['edd_recurring'] = __( 'Never Expires (Sync with Recurring expiration).', 'edd-all-access' );
		} else {
			// Add an option to sync the expiration with EDD Recurring.
			$all_access_length_options['edd_recurring'] = __( 'Sync with Recurring expiration', 'edd-all-access' );
		}

		return $all_access_length_options;
	}

	/**
	 * Include recurring payment statuses to the list of valid statuses
	 *
	 * @since    1.0.0
	 * @param    array $valid_payment_statuses The statuses which are valid for All Access Passes.
	 * @return   array $valid_payment_statuses
	 */
	public function edd_all_access_recurring_valid_payment_statuses( $valid_payment_statuses ) {
		$valid_payment_statuses[] = 'edd_subscription';

		return $valid_payment_statuses;
	}

	/**
	 * Make All Access pass payments that are set to sync with Recurring expiration expire when the Recurring does.
	 * Note that EDD Recurring "grandfathers" existing subscriptions if the period for the product is updated.
	 * Thus, we need to get the period from the customer's subscription rather than the product meta - which could be edited by the site owner after purchases have happened.
	 *
	 * @since    1.0.0
	 * @param    int    $expiration_time - The timestamp this All Access Pass should expire.
	 * @param    object $all_access_pass - The All Access Pass object.
	 * @return   int    $expiration_time - The timestamp this All Access Pass should expire.
	 */
	public function sync_expiration_time_with_recurring( $expiration_time, $all_access_pass ) {

		// If this All Access pass is set to expire when Recurring does (Sync with Recurring expiration).
		if ( 'edd_recurring' !== $all_access_pass->duration_unit ) {
			return $expiration_time;
		}

		/*
		 * If Software Licensing is enabled, check if a license is attached to the AAP. Using the license expiration is better than the recurring expiration.
		 * For example: if a customer renews early, like during a sale, the recurring expiration no longer matches the license expiration.
		 * In that scenario, matching the license expiration is the desired result.
		 */
		$possibly_attached_license = edd_all_access_software_licensing_get_license_from_aa_pass( $all_access_pass );

		// If there's a license for this AA Pass, the Software Licensing integration will take it from here.
		if ( $possibly_attached_license ) {
			return $expiration_time;
		}

		return $this->get_expiration_from_sub( $all_access_pass );
	}

	/**
	 * Show the right string for the All Access Pass if the duration is set to "Sync With Recurring"
	 *
	 * @since    1.0.0
	 * @param    string $assembled_string - The All Access duration string shown to the user. For example, "1 year".
	 * @param    object $all_access_pass - The All Access Pass Object.
	 * @return   bool   $is_active - whether this All Access pass is active or not.
	 */
	public function duration_string( $assembled_string, $all_access_pass ) {

		if ( 'edd_recurring' !== $all_access_pass->duration_unit ) {
			return $assembled_string;
		}

		if ( empty( $all_access_pass->payment->parent_payment ) ) {
			$parent_payment_id = $all_access_pass->payment->ID;
		} else {
			$parent_payment_id = $all_access_pass->payment->parent_payment;
		}

		// Get all subscriptions attached to this payment.
		$subs_db = new EDD_Subscriptions_DB();
		$subs    = $subs_db->get_subscriptions(
			array(
				'parent_payment_id' => $parent_payment_id,
				'order'             => 'ASC',
			)
		);

		$all_access_duration_unit = false;
		// Loop through all subscriptions attached to this payment.
		foreach ( $subs as $sub ) {

			// If the product ID attached to the subcription matches the product ID we are checking for expiration.
			if ( $sub->product_id === $all_access_pass->download_id ) {
				// Use the period saved into this subcrition.
				$all_access_duration_unit = $sub->period;
			}
		}

		if ( $all_access_duration_unit ) {
			return '1 ' . $all_access_duration_unit . ' ' . __( '(recurring)', 'edd-all-access' );
		}

		return $assembled_string;

	}

	/**
	 * Check if an AA pass would expire based on the duration unit being "edd_software_licensing", meaning its set to "sync with license expiration".
	 *
	 * @since    1.1.0
	 * @param    string              $would_be_end_time The would-be end time.
	 * @param    bool                $would_be_duration_number The duration number that would be used in our would-be scenario.
	 * @param    string              $would_be_duration_unit The duration unit (day/week/month/edd_software_licensing) that would be used in our would-be scenario.
	 * @param    EDD_All_Access_Pass $all_access_pass The All Access Pass in question.
	 * @return   string $would_be_end_time The would-be end time adjusted for Software Licensing's license sync
	 */
	public function check_if_pass_would_expire( $would_be_end_time, $would_be_duration_number, $would_be_duration_unit, $all_access_pass ) {

		// If the would-be scenario is not set to sync with the subscription, we don't need to do anything here.
		if ( 'edd_recurring' !== $would_be_duration_unit ) {
			return $would_be_end_time;
		}

		// Check if this AA product is licensed.
		$possibly_attached_license = edd_all_access_software_licensing_get_license_from_aa_pass( $all_access_pass );

		// If there's a license for this AA Pass, Software Licensing's integration code will tae care of this.
		if ( $possibly_attached_license ) {
			return $would_be_end_time;
		}

		return $this->get_expiration_from_sub( $all_access_pass );
	}

	/**
	 * This is fired directly after a subscription has been renewed. Here we hook into that to expire the old All Access Pass and activate the new.
	 *
	 * @since    1.0.0
	 * @param    int    $subscription_id The ID of the subscription that was renewed.
	 * @param    string $subscription_expiration The expiration date of the subscription in format Y-m-d H:i:s'.
	 * @param    object $edd_subscription The Al Access Pass Object.
	 * @return   void
	 */
	public function check_expirations_post_renew( $subscription_id, $subscription_expiration, $edd_subscription ) {

		// Get the original payment id.
		$original_payment_id = $edd_subscription->get_original_payment_id();

		// Set up the payment object from the original payment so we can see what was purchased.
		$parent_payment = edd_get_payment( $original_payment_id );

		// Set default price id.
		$price_id = 0;

		// We need to get the price id in order to find the All Access Pass. Loop through all items in this cart until we find the product being renewed.
		if ( ! empty( $parent_payment->cart_details ) && is_array( $parent_payment->cart_details ) ) {
			foreach ( $parent_payment->cart_details as $cart_item ) {
				if ( (int) $edd_subscription->product_id === (int) $cart_item['id'] ) {
					$price_id = $cart_item['item_number']['options']['price_id'];
					break;
				}
			}
		}

		// Get all renewal payments attached to this renewed subscription.
		$renewal_payments = $edd_subscription->get_child_payments();

		// Get the second-newest renewal payment because we need to expire the All Access Pass attached to it.
		$expired_payment = isset( $renewal_payments[1] ) ? $renewal_payments[1] : false;

		if ( ! $expired_payment ) {
			$expired_payment_id = $edd_subscription->get_original_payment_id();
		} else {
			$expired_payment_id = $expired_payment->ID;
		}

		// Set up the All Access Pass object for the expired payment.
		edd_all_access_get_pass( $expired_payment_id, $edd_subscription->product_id, $price_id );
	}

	/**
	 * Deprecated: Prevent manual early renewal purchases of All Access if there is an active Subscription for it.
	 *
	 * @since       1.0.0
	 * @param       array $valid_data The values relating to displaying the Purchase Button.
	 * @param       array $post_data The values relating to displaying the Purchase Button.
	 * @return      void
	 */
	public function prevent_manual_renewals( $valid_data, $post_data ) {

		_edd_all_access_deprecated_function( 'EDD_All_Access_Recurring->prevent_manual_renewals', '1.1' );

		// Get the currently logged-in customer.
		$customer = new EDD_Recurring_Subscriber( get_current_user_id(), true );

		// Get all of the All Access enabled products.
		$all_access_products = edd_all_access_get_all_access_downloads();

		$cart_contents = edd_get_cart_contents();

		// Loop through each item in the cart to check if it is an All Access with an active Subscription.
		foreach ( $cart_contents as $cart_key => $item ) {

			$download_id = $item['id'];
			$price_id    = isset( $item['options']['price_id'] ) ? (int) $item['options']['price_id'] : 0;

			// If this download is an All Access Product.
			if ( in_array( intval( $download_id ), $all_access_products, true ) ) {

				// If this is an upgrade, don't prevent it. See issue 186: https://github.com/easydigitaldownloads/edd-all-access/issues/186.
				if ( isset( $item['options']['is_upgrade'] ) ) {
					continue;
				}

				// Get the currently logged-in customer.
				$customer = new EDD_Recurring_Subscriber( get_current_user_id(), true );

				// Check if it has an active subscription.
				$subs = $customer->get_subscriptions( $download_id );

				if ( $subs ) {

					foreach ( $subs as $sub ) {

						// If this is not expired and has a status of active or trialling (we allow cancelled ones to be renewed if they are not expired).
						if ( ! $sub->is_expired() && ( 'active' === $sub->status || 'trialling' === $sub->status ) && edd_is_payment_complete( $sub->parent_payment_id ) ) {

							// Prevent the renewal as it will just cause the customer to double pay for no reason.
							// Translators: The name of the product which already has an active All Access Pass.
							edd_set_error( 'edd_all_access_subscription_already_exists', sprintf( __( 'You already have an active subscription for %s.', 'edd-all-access' ), get_the_title( $download_id ) ) );
						}
					}
				}
			}
		}
	}

	/**
	 * If the site owner has enabled the "Prevent downloads unless active subscription" option in EDD recurring,
	 * override that if the AAP allows downloading of the product in question
	 *
	 * @since       1.1
	 * @param       bool $has_access Whether the customer should be ablw to download this file.
	 * @param       int  $user_id The ID of the user attached to the customer who is attempting to download this file.
	 * @param       int  $download_id The ID of the product which is  attempting to be downloaded.
	 * @param       bool $is_variable Whether or not the product being downloaded has variable pricing.
	 * @return      bool
	 */
	public function edd_recurring_download_has_access( $has_access, $user_id, $download_id, $is_variable ) {

		// Check if this is a variable priced product.
		$is_variable = isset( $_GET['price_id'] ) && false !== (int) $_GET['price_id'] ? true : false; //phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $is_variable ) {
			$price_id = (int) $_GET['price_id']; //phpcs:ignore WordPress.Security.NonceVerification.Recommended

		} else {
			$price_id = 0;
		}

		$all_access_check = edd_all_access_check(
			array(
				'download_id' => $download_id,
				'price_id'    => $price_id,
				'customer_id' => false,
				'user_id'     => $user_id,
			)
		);

		if ( $all_access_check['success'] ) {
			return true;
		} else {
			return $has_access;
		}

	}

	/**
	 * Prevent All Access for showing "Renew Now" on owned AA products if the user has an active subscription.
	 *
	 * @since 1.2.4
	 * @param string $purchase_form The purchase form (already modified by All Access).
	 * @param array  $args          The purchase form parameters.
	 * @return string
	 */
	public function renew_button( $purchase_form, $args ) {
		if ( ! edd_all_access_download_is_all_access( $args['download_id'], $args['price_id'] ) ) {
			return $purchase_form;
		}
		$customer = new EDD_Customer( get_current_user_id(), true );
		if ( empty( $customer->id ) ) {
			return $purchase_form;
		}
		$subs_db = new EDD_Subscriptions_DB();
		$subs    = $subs_db->count(
			array(
				'product_id'  => $args['download_id'],
				'price_id'    => $args['price_id'],
				'customer_id' => $customer->id,
				'status'      => array( 'active', 'completed' ),
			)
		);

		if ( empty( $subs ) ) {
			return $purchase_form;
		}

		$download = new EDD_Download( $args['download_id'] );

		return sprintf(
			/* translators: the product name. */
			__( 'You have an active subscription for %s.', 'edd-all-access' ),
			$download->get_name()
		);
	}

	/**
	 * Gets the pass expiration date from the subscription.
	 *
	 * @since 1.2.5
	 * @param EDD_All_Access_Pass $all_access_pass
	 * @return string
	 */
	private function get_expiration_from_sub( $all_access_pass ) {

		// Get the purchase time for this All Access Pass in UTC.
		$purchase_time = $all_access_pass->start_time;

		// Get the duration unit.
		$all_access_duration_unit = $all_access_pass->duration_unit;

		// Check if this payment has a subscription attached to it.
		$payment_has_subscription = edd_get_payment_meta( $all_access_pass->payment->ID, '_edd_subscription_payment' );

		// Check if this payment is a renewal payment.
		$payment_is_renewal = 'edd_subscription' === $all_access_pass->payment->status && ! empty( $all_access_pass->payment->parent_payment );

		/**
		* If no subscription has been set up with this payment, default it to never expire (as it will never recurr).
		*/
		if ( ! $payment_has_subscription && ! $payment_is_renewal ) {
			return 'never';
		}

		if ( $payment_is_renewal ) {
			$initial_subscription_payment = $all_access_pass->payment->parent_payment;
		} else {
			$initial_subscription_payment = $all_access_pass->payment->ID;
		}

		// Get all subscriptions attached to this payment.
		$subs_db = new EDD_Subscriptions_DB();
		$subs    = $subs_db->get_subscriptions(
			array(
				'parent_payment_id' => $initial_subscription_payment,
				'order'             => 'ASC',
			)
		);

		$date_string = false;

		// Loop through all subscriptions attached to this payment.
		foreach ( $subs as $sub ) {

			// If the product ID attached to the subcription matches the product ID we are checking for expiration.
			if ( intval( $sub->product_id ) === intval( $all_access_pass->download_id ) ) {

				// If this subscription is in trial mode, we'll get the expiration from the trial length.
				if ( 'trialling' === $sub->status ) {
					// Use the trial period saved into this subscription.
					$date_string = $sub->trial_period;
				} elseif ( 'expired' === $sub->status && 0 === $sub->times_billed && ! empty( $sub->trial_period ) ) {
					// If this subscription was a trial that was never renewed, use the trial period saved into this subscription.
					$date_string = $sub->trial_period;
				} elseif ( ! empty( $sub->trial_period ) && empty( $sub->bill_times ) && in_array( $sub->status, array( 'active', 'cancelled' ), true ) ) {
					// This is for a subscription with a trial period, after the trial is done, but before the first renewal has been made.
					$date_string = $this->get_date_string_from_duration_unit( $sub->period ) . ' + ' . $sub->trial_period;
				} else {
					// Use the period saved into this subscription.
					$all_access_duration_unit = $sub->period;
				}
				break;
			}
		}

		// If a subscription was not found that matches this All Access product, something went wrong with the subscription creation - or it was manually modified incorrectly by the site owner.
		if ( 'edd_recurring' === $all_access_duration_unit && empty( $date_string ) ) {
			// Return 'never' for now so the pass does not expire, and can be fixed manually by setting the subscription back to the correct All Access product.
			return 'never';
		}

		if ( empty( $date_string ) ) {
			$date_string = $this->get_date_string_from_duration_unit( $all_access_duration_unit );
		}

		$expiration_time = strtotime( '+' . $date_string, $purchase_time );

		if ( empty( $expiration_time ) ) {
			$expiration_time = 'Failure in recurring hook 2. Expiration was empty. Values were Duration Number:' . $all_access_duration_number . ' Duration Unit: ' . $all_access_duration_unit . 'Purchase Time:' . $purchase_time;
		}

		return $expiration_time;
	}

	/**
	 * Gets the date string from the duration unit.
	 *
	 * @since 1.2.5
	 * @param string $duration_unit The duration unit.
	 * @return string
	 */
	private function get_date_string_from_duration_unit( $duration_unit ) {

		// Recurring doesn't have a duration number so this is always 1 (Example 1 day, 1 month, 1 year etc). If they ever add that, fetch it here.
		$duration_number = 1;
		// If this is set to "quarter", strtotime doesn't understand that, so we'll set it to "3 months".
		if ( 'quarter' === $duration_unit ) {
			$duration_number = 3;
			$duration_unit   = 'months';
		} elseif ( 'semi-year' === $duration_unit ) {
			$duration_number = 6;
			$duration_unit   = 'months';
		}

		return $duration_number . ' ' . $duration_unit;
	}
}
