<?php
/**
 * All Access Pass Object
 *
 * @package     EDD All Access
 * @subpackage  Classes/All Access Pass
 * @copyright   Copyright (c) 2016, Phil Johnston
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * EDD_All_Access_Pass Class
 *
 * @since 1.0.0
 */
class EDD_All_Access_Pass {

	/**
	 * The ID of this All Access Pass
	 *
	 * @var string
	 * @since 1.0.0
	 */
	private $id = null;

	/**
	 * The Payment Object
	 *
	 * @var EDD_Payment
	 * @since 1.0.0
	 */
	private $payment = null;

	/**
	 * The Payment ID
	 *
	 * @var int
	 * @since 1.0.0
	 */
	private $payment_id = null;

	/**
	 * The array containing the All Access Passes stored in this customer
	 *
	 * @var array
	 * @since 1.0.0
	 */
	private $customer_all_access_passes = null;

	/**
	 * The download id of the purchased All Access-enabled product
	 *
	 * @var int
	 * @since 1.0.0
	 */
	private $download_id = null;

	/**
	 * The price of this purchased All Access-enabled product
	 *
	 * @var int
	 * @since 1.0.0
	 */
	private $price_id = null;

	/**
	 * The status of this All Access pass
	 *
	 * @var string
	 * @since 1.0.0
	 */
	private $status = null;

	/**
	 * There are two possible meta values we can use. The ones saved at the time of purchase, or customer specific ones.
	 * Which one is used for a customer is set on their Customer > All Access pass settings in wp-admin.
	 *
	 * @var array
	 * @since 1.0.0
	 */
	private $meta_to_use = null;

	/**
	 * The Start Time connected to this All Access pass
	 *
	 * @var string
	 * @since 1.0.0
	 */
	private $start_time = null;

	/**
	 * The Expiration Time connected to this All Access pass
	 *
	 * @var string
	 * @since 1.0.0
	 */
	private $expiration_time = null;

	/**
	 * Boolean that gets whether the time period attached to this All Access pass is still valid.
	 *
	 * @var bool
	 * @since 1.0.0
	 */
	private $time_period_still_valid = null;

	/**
	 * The Duration Number for this All Access pass
	 *
	 * @var int
	 * @since 1.0.0
	 */
	private $duration_number = null;

	/**
	 * The Duration Unit (Days, Months, etc) for this All Access pass
	 *
	 * @var string
	 * @since 1.0.0
	 */
	private $duration_unit = null;

	/**
	 * The Download Limit on this All Access pass
	 *
	 * @var int
	 * @since 1.0.0
	 */
	private $download_limit = null;

	/**
	 * The Download Limit Time Period (Per day, Per month, etc) on this All Access pass
	 *
	 * @var string
	 * @since 1.0.0
	 */
	private $download_limit_time_period = null;

	/**
	 * Included download categories
	 *
	 * @var array
	 * @since 1.0.0
	 */
	private $included_categories = null;

	/**
	 * Number of price variations that exist in total
	 *
	 * @var array
	 * @since 1.0.0
	 */
	private $number_of_price_ids = null;

	/**
	 * Included price variations
	 *
	 * @var array
	 * @since 1.0.0
	 */
	private $included_price_ids = null;

	/**
	 * The timestamp when the downloads-used counter was last reset
	 *
	 * @var int
	 * @since 1.0.0
	 */
	private $downloads_used_last_reset = null;

	/**
	 * The number of downloads this All Access Pass has been used for in this time period (eg per day, year etc).
	 *
	 * @var int
	 * @since 1.0.0
	 */
	private $downloads_used = null;

	/**
	 * If upgraded, the all access pass id that this current one was upgraded too. Otherwise, false.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	private $is_prior_of = null;

	/**
	 * The prior all access passes attached to this one. If these exist, it has been upgraded-to. If not, the value will be false.
	 *
	 * @var array
	 * @since 1.0.0
	 */
	private $prior_all_access_passes = null;

	/**
	 * If any renewal payment ids have been attached, they will be stored here.
	 *
	 * @var array
	 * @since 1.0.0
	 */
	private $renewal_payment_ids = null;

	/**
	 * All of the products that have been all access enabled will be stored here as an array.
	 *
	 * @var array
	 * @since 1.0.0
	 */
	private $all_access_posts = null;

	/**
	 * This variable indicated whether the setup method has run for this instantiation, or not.
	 *
	 * @var bool
	 * @since 1.1.2
	 */
	private $setup_is_completed = false;

	/**
	 * The object containing the data for the All Access enabled product.
	 *
	 * @since 1.2
	 * @var \EDD\AllAccess\Models\AllAccessProduct
	 */
	private $product;

	/**
	 * Set up the pass.
	 *
	 * @param int $payment_id  The payment ID to associate with this pass.
	 * @param int $download_id The download ID to associate with this pass.
	 * @param int $price_id    The variable price ID (if one) to associate with this pass.
	 *
	 * @since      1.0.0
	 */
	public function __construct( $payment_id = 0, $download_id = 0, $price_id = 0 ) {

		// Use a static variable to indicate we are setting up the pass for the first time.
		static $has_run;

		// Make sure we got a payment id.
		if ( empty( $payment_id ) || ( is_numeric( $payment_id ) && absint( $payment_id ) !== (int) $payment_id ) ) {
			return false;
		}

		// Make sure the payment id is an actual payment.
		$payment = edd_get_payment( $payment_id );
		if ( false === $payment ) {
			return false;
		}

		// Make sure we got a download id.
		if ( empty( $download_id ) || ( is_numeric( $download_id ) && absint( $download_id ) !== (int) $download_id ) ) {
			return false;
		}

		$this->setup( $payment, $download_id, $price_id );

		if ( is_null( $has_run ) && $this->setup_is_completed && 'expired' === $this->status ) {
			// Make sure that the maybe_expire method is only called on the initial run.
			$has_run = true;
			$expired = $this->maybe_expire();
			if ( ! empty( $expired['error'] ) ) {
				edd_debug_log( $expired['error'] );
			}
		}
	}

	/**
	 * Magic __get function to dispatch a call to retrieve a private property
	 *
	 * @param    string $key The name of the private variable being fetched.
	 * @since    1.0.0
	 *
	 * @return   mixed
	 */
	public function __get( $key ) {

		// If the product purchased was not an All Access pass.
		if ( 'status' !== $key && 'invalid' === $this->status ) {
			return new WP_Error( 'edd-all-access-pass-invalid', __( 'This is not an All Access Pass', 'edd-all-access' ) );
		}

		if ( method_exists( $this, 'get_' . $key ) ) {

			return call_user_func( array( $this, 'get_' . $key ) );

		} else {

			// Translators: the property that could not be retrieved.
			return new WP_Error( 'edd-all-access-pass-invalid-property', sprintf( __( 'Can\'t get property %s', 'edd-all-access' ), $key ) );

		}

	}

	/**
	 * Magic SET function
	 *
	 * @since    1.0.0
	 * @param    string $key   The property name.
	 * @param    mixed  $value  The value the property is being set to.
	 */
	public function __set( $key, $value ) {

		if ( ! empty( $key ) ) {

			if ( method_exists( $this, 'set_' . $key ) ) {

				return call_user_func( array( $this, 'set_' . $key ), $value );

			} else {
				$this->$key = $value;

			}
		}

	}

	/**
	 * Magic ISSET function, which allows empty checks on protected elements
	 *
	 * @since  1.0.0
	 * @param  string $key The attribute to get.
	 * @return boolean If the item is set or not.
	 */
	public function __isset( $key ) {

		// Check if we can get the value through a getter function.
		if ( method_exists( $this, 'get_' . $key ) ) {

			if ( null === call_user_func( array( $this, 'get_' . $key ) ) ) {
				return false;
			} else {
				return true;
			}
		}

		// If we got this far, no getter function exists for this key so check it via the property alone as-is.
		if ( property_exists( $this, $key ) ) {
			return false === empty( $this->$key );
		} else {
			return null;
		}

	}

	/**
	 * Setup minimum required default data for an All Access Pass.
	 *
	 * @since    1.0.0
	 * @since    1.2.4.2 - Updated to accept the EDD_Payment object, we will look it up if it's an ID still.
	 *
	 * @param    EDD_Payment $payment      The EDD_Payment object attached to the All Access Pass.
	 * @param    int         $download_id  The ID of the download attached to the All Access Pass.
	 * @param    int         $price_id The ID of the price attached to the All Access Pass.
	 * @return   void
	 */
	private function setup( $payment, $download_id, $price_id ) {

		// If a numeric value is passed, look up the payment from it.
		if ( is_numeric( $payment ) ) {
			$payment = edd_get_payment( $payment );
		}

		// Get the Payment Object and set the default variables.
		$this->payment                    = $payment;
		$this->payment_id                 = $this->payment->ID;
		$this->download_id                = absint( $download_id );
		$this->product                    = new \EDD\AllAccess\Models\AllAccessProduct( $this->download_id );
		$this->price_id                   = absint( $price_id );
		$this->customer_all_access_passes = edd_all_access_get_customer_passes( $this->get_customer() );

		// Set the if of this All Access Pass.
		$this->id = $this->payment->ID . '_' . $this->download_id . '_' . $this->price_id;

		// Get all posts which count as "All Access" posts.
		$this->all_access_posts = $this->get_all_access_posts();

		// Get the correct meta for this Customer's All Access pass ($payment tells us which customer).
		$this->all_access_meta = $this->all_access_meta();

		// Get the status.
		$this->status = $this->get_status();

		$this->downloads_used = $this->get_downloads_used();
		$this->download_limit = $this->get_download_limit();

		// Every time an active All Access Pass Object is created, run the check to see if we should reset the downloads-used counter.
		if ( 'active' === $this->status ) {
			$this->maybe_reset_downloads_used_counter();
		}

		$this->setup_is_completed = true;
	}

	/**
	 * Get the status of an All Access pass.
	 *
	 * @since    1.0.0
	 * @return   string - "active" if still active. 'expired' if expired. 'invalid' if no All Access product was purchased.
	 */
	private function get_status() {

		// Check if we've already run this getter before.
		if ( ! is_null( $this->status ) ) {
			return $this->status;
		}

		// If the download id is blank.
		if ( empty( $this->download_id ) ) {
			edd_debug_log( 'AA: Status invalid for ' . $this->ID . ' because the download id was blank' );
			return $this->set_and_return_status( 'invalid' );
		}

		// If no all access posts were returned.
		if ( empty( $this->all_access_posts ) ) {
			edd_debug_log( 'AA: Status invalid for ' . $this->ID . ' because there were no All Access products found in the store.' );
			return $this->set_and_return_status( 'invalid' );
		}

		// If this purchased product is not an "All Access" enabled post.
		if ( ! in_array( $this->download_id, $this->all_access_posts, true ) ) {
			edd_debug_log( 'AA: Status invalid for ' . $this->ID . ' because the download id is not All Access enabled, or the product is not published.' );
			return $this->set_and_return_status( 'invalid' );
		}

		// If the payment passed in does not exist (perhaps it was deleted).
		if ( empty( $this->payment_id ) || 0 === intval( $this->payment_id ) ) {
			edd_debug_log( 'AA: Status invalid for ' . $this->ID . ' because the payment ID does not exist (it may have been deleted).' );
			return $this->set_and_return_status( 'invalid' );
		}

		// If the passed-in payment doesn't have one of those statuses, this is invalid.
		if ( ! in_array( $this->payment->status, edd_all_access_valid_order_statuses(), true ) ) {
			edd_debug_log( 'AA: Status invalid for ' . $this->ID . ' because its payment status is ' . $this->payment->status . '.' );
			return $this->set_and_return_status( 'invalid' );
		}

		$product_is_in_payment = false;

		// Check each item purchased to make sure that the passed-in download_id and price_id are actually in that payment.
		foreach ( $this->payment->downloads as $a_purchased_download_info ) {

			// If the download passed-in is in the payment.
			if ( intval( $a_purchased_download_info['id'] ) === intval( $this->download_id ) ) {
				if ( 0 === $this->price_id ) {
					$product_is_in_payment = true;
				} else {
					if ( isset( $a_purchased_download_info['options']['price_id'] ) && intval( $a_purchased_download_info['options']['price_id'] ) === intval( $this->price_id ) ) {
						$product_is_in_payment = true;
					}
				}
			}
		}

		// If the product is not in the payment, this is an invalid All Access Pass.
		if ( ! $product_is_in_payment ) {
			edd_debug_log( 'AA: Status invalid for ' . $this->ID . ' because the AA product is not in this purchase.' );
			return $this->set_and_return_status( 'invalid' );
		}

		// If this All Access pass has been upgraded, the status is "upgraded".
		if ( $this->get_is_prior_of() ) {
			return $this->set_and_return_status( 'upgraded' );
		}

		// Make sure the All Access Pass we were given is stored in the customer's meta - otherwise it is invalid (it hasn't been properly activated yet).
		if ( ! isset( $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ] ) ) {
			edd_debug_log( 'AA: Status invalid for ' . $this->ID . ' because it is not in the customer\'s All Access meta.' );
			return $this->set_and_return_status( 'invalid' );
		}

		if ( ! isset( $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['payment_id'] ) ) {
			edd_debug_log( 'AA: Status invalid for ' . $this->ID . ' because there is no payment ID attached to the AA meta.' );
			return $this->set_and_return_status( 'invalid' );
		}

		if ( ! isset( $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['download_id'] ) ) {
			edd_debug_log( 'AA: Status invalid for ' . $this->ID . ' because there is no download ID attached to the AA meta.' );
			return $this->set_and_return_status( 'invalid' );
		}

		if ( ! isset( $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['price_id'] ) ) {
			edd_debug_log( 'AA: Status invalid for ' . $this->ID . ' because there is no price ID attached to the AA meta.' );
			return $this->set_and_return_status( 'invalid' );
		}

		// If a newer payment for this All Access Pass exists in the customer than the payment passed to this object, it has been renewed.
		$possibly_renewed_payment_id = $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['payment_id'];

		// If the newest customer-listed All Access Pass payment is not the one attached to this current object.
		if ( $possibly_renewed_payment_id !== $this->payment_id ) {

			$possibly_renewed_payment = edd_get_payment( $possibly_renewed_payment_id );

			// If this payment's ID is 0, a prior payment was deleted, and now is being renewed. So the status is currently "invalid".
			if ( ! $possibly_renewed_payment || ! isset( $possibly_renewed_payment->ID ) || 0 === intval( $possibly_renewed_payment->ID ) ) {
				return $this->set_and_return_status( 'invalid' );
			} elseif ( edd_all_access_get_payment_utc_timestamp( $possibly_renewed_payment ) > edd_all_access_get_aap_purchase_timestamp( $this ) ) {
				// If the newest customer-listed All Access Pass payment is newer than this pass's one (it is more recent).

				// Then this object has been renewed and is no longer active (or valid). Set the status to be "Renewed".
				return $this->set_and_return_status( 'renewed' );
			} else {

				// If the newest customer-listed All Access Pass payment is older than the one attached to this one, it's a renewal payment in waiting.
				return $this->set_and_return_status( 'upcoming' );
			}
		}

		$time_period_valid = $this->get_time_period_still_valid();

		// If the expiration time passes as still active.
		if ( $time_period_valid ) {

			// Get the list of ids in this payment that have previously been set to "expired" (if any).
			$all_access_expired_ids = $this->get_order_meta( '_edd_aa_expired_ids' );
			$all_access_expired_ids = empty( $all_access_expired_ids ) ? array() : $all_access_expired_ids;

			// Get the list of ids in this payment that have previously been set to "active" (if any).
			$all_access_active_ids = $this->get_order_meta( '_edd_aa_active_ids' );
			$all_access_active_ids = empty( $all_access_active_ids ) ? array() : $all_access_active_ids;

			// Active/Incative All Access purchases are stored in the _edd_aa_active_ids and _edd_aa_expired_ids using the Download Id and Price ID combined into a string.
			// For example, if the download id is 5456 and the purchased price id was 3, the array key will be 5456-3.
			$purchased_aa_download_key = $this->download_id . '-' . $this->price_id;

			// Double check to make sure this has never expired and somehow been reactivated. If so, it's still expired. Once expired, always expired.
			if ( is_array( $all_access_expired_ids ) && array_key_exists( $purchased_aa_download_key, $all_access_expired_ids ) ) {
				return $this->set_and_return_status( 'expired' );
			}

			// Double check to make sure this has been activated. If not, it is a renewal waiting to "take over" when the currently active payment expires.
			if ( ! array_key_exists( $purchased_aa_download_key, $all_access_active_ids ) ) {
				return $this->set_and_return_status( 'upcoming' );
			}

			// If the download id passed-in doesn't match the payment saved in the AA customer meta.
			if ( $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['download_id'] !== $this->download_id ) {
				edd_debug_log( 'AA: Status invalid for ' . $this->ID . ' because the download ID given (' . $this->download_id . ') doesn\'t match what is in the customer meta (' . $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['download_id'] . ').' );
				return $this->set_and_return_status( 'invalid' );
			}

			// If the price id passed-in doesn't match the payment saved in the AA customer meta.
			if ( $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['price_id'] !== $this->price_id ) {
				edd_debug_log( 'AA: Status invalid for ' . $this->ID . ' because the price ID given (' . $this->price_id . ') doesn\'t match what is in the customer meta (' . $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['price_id'] . ').' );
				return $this->set_and_return_status( 'invalid' );
			}

			return $this->set_and_return_status( 'active' );
		}

		return $this->set_and_return_status( 'expired' );
	}

	/**
	 * Sets the status of a pass, handles cache invalidation, and returns the new status.
	 *
	 * @since 1.2.4.2
	 *
	 * @param string $status The new Status of the Pass.
	 *
	 * @return string The new status of the Pass, after being set.
	 */
	private function set_and_return_status( $status = '' ) {
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

	/**
	 * Clear the WordPress cache and get fresh data from the database. This ensures that any object caching does not give us false data.
	 * We especially need to use this when making changes to data or modifying it so we call this at the start of each _set function.
	 *
	 * @since    1.0.0
	 * @return   array The All Access meta attached to the customer in the customer meta table.
	 */
	private function retrieve_fresh_data() {

		$pass_customer = $this->get_customer();

		// If there's no customer, we can't retrieve data from the customer meta.
		if ( ! $this->setup_is_completed || empty( $pass_customer->id ) ) {
			return $this->all_access_meta;
		}

		$current_hash = md5( json_encode( $this->all_access_meta ) );

		// Get a fresh copy of the All Access meta for this customer from the database.
		$this->customer_all_access_passes = edd_all_access_get_customer_passes( $pass_customer );

		// Get the correct meta for this Customer's All Access pass ($payment tells us which customer).
		$this->all_access_meta = $this->all_access_meta();

		$new_hash = md5( json_encode( $this->all_access_meta ) );

		// If the value for all_access_meta has changed, flush the cache.
		if ( ! hash_equals( $current_hash, $new_hash ) ) {
			do_action( 'edd_all_access_data_refreshed', $this );
		}
	}

	/**
	 * Get the payment_id attached to this All Access Pass
	 *
	 * @since    1.0.0
	 * @return   int - The ID of the payment attached to this All Access Pass.
	 */
	private function get_payment_id() {
		if ( ! empty( $this->payment->ID ) && (int) $this->payment->ID !== (int) $this->payment_id ) {
			$this->payment_id = $this->payment->ID;
		}

		return $this->payment_id;
	}

	/**
	 * Get the payment object attached to this All Access Pass
	 *
	 * @since    1.0.0
	 * @return   int - The ID of the payment attached to this All Access Pass.
	 */
	private function get_payment() {
		return $this->payment;
	}

	/**
	 * Get the customer object attached to this All Access Pass
	 *
	 * @since    1.0.0
	 * @since    1.2.4.2 - Returns the live data from the customer, instead of holding it on the pass.
	 * @return   EDD_Customer - The ID of the payment attached to this All Access Pass.
	 */
	private function get_customer() {
		return new EDD_Customer( $this->payment->customer_id );
	}

	/**
	 * Get the download id purchased which created this All Access Pass
	 *
	 * @since    1.0.0

	 * @return   int - The ID of the payment attached to this All Access Pass.
	 */
	private function get_download_id() {
		return $this->download_id;
	}

	/**
	 * Get the price id purchased which created this All Access Pass
	 *
	 * @since    1.0.0
	 * @return   int - The ID of the payment attached to this All Access Pass.
	 */
	private function get_price_id() {
		return $this->price_id;
	}

	/**
	 * Get the id of this All Access Pass
	 *
	 * @since    1.0.0
	 * @return   int - The ID of the payment attached to this All Access Pass.
	 */
	private function get_id() {
		return $this->id;
	}

	/**
	 * Get the all_access_posts set up in this store
	 *
	 * @since    1.0.0
	 * @return   array - An array containing the All Access enabled products in this digital shop.
	 */
	private function get_all_access_posts() {

		// Check if we've already run this getter before.
		if ( ! is_null( $this->all_access_posts ) ) {
			return $this->all_access_posts;
		}

		$this->all_access_posts = edd_all_access_get_all_access_downloads();
		return $this->all_access_posts;
	}

	/**
	 * Get the start time (in seconds) for this All Access Pass.
	 *
	 * @since    1.0.0
	 * @return   mixed int/string- The start time of this All Access Pass in seconds.
	 */
	private function get_start_time() {

		// Check if we've already run this getter before.
		if ( ! is_null( $this->start_time ) ) {
			return $this->start_time;
		}

		// Get the duration variables from the returned All Access meta.
		$all_access_start_time = $this->all_access_meta['all_access_start_time'];

		// If, for some reason start_time has no value, return the payment time.
		if ( empty( $all_access_start_time ) || ! is_numeric( $all_access_start_time ) ) {
			$all_access_start_time = edd_all_access_get_aap_purchase_timestamp( $this );
		}

		$this->start_time = apply_filters( 'edd_all_access_get_start_time', $all_access_start_time, $this->payment, $this->download_id, $this->price_id, $this );

		return $this->start_time;
	}

	/**
	 * Set the start time (in seconds) for this All Access Pass.
	 *
	 * @since   1.0.0
	 * @param   int $start_time The timestamp to which the start time will be set.
	 * @return  mixed int/string- The start time of this All Access Pass in seconds.
	 */
	private function set_start_time( $start_time ) {

		$start_time = absint( $start_time );

		$all_access_pass_key = $this->download_id . '_' . $this->price_id;

		// It is somehow possible that this is an empty string, eitehr by caching or when used out of order from `setup`.
		if ( empty( $this->customer_all_access_passes ) ) {
			$this->customer_all_access_passes = array();
		}

		// For the new All Access Pass, set the "Time of Activation" start time.
		$this->customer_all_access_passes[ $all_access_pass_key ]['time_of_activation_meta']['all_access_start_time'] = $start_time;

		// Also for the new All Access Pass, set the "Customer Specific" start time.
		$this->customer_all_access_passes[ $all_access_pass_key ]['customer_specific_meta']['all_access_start_time'] = $start_time;

		// Update the All Access Pass data in the database.
		$updated = $this->get_customer()->update_meta( 'all_access_passes', $this->customer_all_access_passes );
		$this->retrieve_fresh_data();

		if ( $updated ) {
			$this->start_time = $start_time;
		}

		return $this->start_time;

	}

	/**
	 * Get the expiration time (in seconds) for a Payment containing an All Access download.
	 *
	 * @since    1.0.0

	 * @return   mixed int/string- The number of seconds that an All Access should last based on the string passed-in. (This is essentially the the start and duration time added together).
	 */
	private function get_expiration_time() {

		// Check if we've already run this getter before.
		if ( ! is_null( $this->expiration_time ) ) {
			return $this->expiration_time;
		}

		// Get the duration variables from the returned All Access meta.
		$all_access_start_time      = $this->get_start_time();
		$all_access_duration_number = $this->get_duration_number();
		$all_access_duration_unit   = $this->get_duration_unit();

		// Add the amount of time the payment is valid for to the start time to calculate the expiration time.
		$expiration_time = strtotime( '+' . $all_access_duration_number . ' ' . $all_access_duration_unit, $all_access_start_time );

		// If the expiration time matches the start time exactly, the strtotime was not fed a valid string. An extension may have modified this to happen. Default it to never.
		// For example, if you set it to sync_with_recurring using EDD Recurring, but didn't enable recurring, this will happen.
		if ( $all_access_start_time === $expiration_time || empty( $expiration_time ) ) {
			$expiration_time = 'never';
		}

		// If this All Access account never expires, return the string "never".
		if ( 'never' === $all_access_duration_unit ) {
			$expiration_time = 'never';
		}

		$this->expiration_time = apply_filters( 'edd_all_access_get_expiration_time', $expiration_time, $this );
		return $this->expiration_time;

	}

	/**
	 * Get the value for the duration number. For example X months. The unit (months) is defined separately.
	 *
	 * @since    1.0.0

	 * @return   int - The duration number.
	 */
	private function get_duration_number() {

		// Check if we've already run this getter before.
		if ( ! is_null( $this->duration_number ) ) {
			return $this->duration_number;
		}

		$all_access_duration_number = $this->all_access_meta['all_access_duration_number'];

		// Duration unit is always a minimum of 1.
		if ( empty( $all_access_duration_number ) ) {
			$all_access_duration_number = 1;
		}

		$this->duration_number = $all_access_duration_number;
		return $this->duration_number;
	}

	/**
	 * Get the value for the duration unit. For example 10 (months/days/weeks).
	 *
	 * @since    1.0.0

	 * @return   string - The duration unit.
	 */
	private function get_duration_unit() {

		// Check if we've already run this getter before.
		if ( ! is_null( $this->duration_unit ) ) {
			return $this->duration_unit;
		}

		$this->duration_unit = $this->all_access_meta['all_access_duration_unit'];

		return $this->duration_unit;
	}

	/**
	 * Get the download limit number. For example X downloads per day. We are getting X.
	 *
	 * @since    1.0.0

	 * @return   int - The download limit number.
	 */
	private function get_download_limit() {

		// Check if we've already run this getter before.
		if ( ! is_null( $this->download_limit ) ) {
			return $this->download_limit;
		}

		return isset( $this->all_access_meta['all_access_download_limit'] ) ? intval( $this->all_access_meta['all_access_download_limit'] ) : null;
	}

	/**
	 * Get the download limit number. For example X downloads per day. We are getting X.
	 *
	 * @since    1.0.0

	 * @return   string - The download limit time period.
	 */
	private function get_download_limit_time_period() {

		// Check if we've already run this getter before.
		if ( ! is_null( $this->download_limit_time_period ) ) {
			return $this->download_limit_time_period;
		}

		$this->download_limit_time_period = $this->all_access_meta['all_access_download_limit_time_period'];

		return $this->download_limit_time_period;
	}

	/**
	 * Get the categories included in this All Access pass.
	 *
	 * @since    1.0.0

	 * @return   array - The included categories.
	 */
	private function get_included_categories() {

		// Check if we've already run this getter before.
		if ( ! is_null( $this->included_categories ) ) {
			return $this->included_categories;
		}

		$included_categories = $this->all_access_meta['all_access_categories'];
		if ( ! is_array( $included_categories ) ) {
			$included_categories = array( $included_categories );
		}

		$typecasted_categories = array();

		// Typecast each value to be an integer.
		foreach ( $included_categories as $included_category ) {
			if ( 'all' !== $included_category && ! empty( $included_category ) ) {
				$typecasted_categories[] = intval( $included_category );
			}
		}

		// If Included Categories array is empty - or isn't an array at all, return an array that makes all categories included.
		if ( empty( $typecasted_categories ) || ! is_array( $typecasted_categories ) ) {
			$typecasted_categories = array( 'all' );
		}

		$this->included_categories = apply_filters( 'edd_all_access_included_categories', $typecasted_categories, $this );
		return $this->included_categories;
	}

	/**
	 * Get the total number of price ids/variations to consider for this All Access pass (this includes the total - even price ids that aren't included in All Access).
	 *
	 * @since    1.0.0

	 * @return   int - The number of price ids.
	 */
	private function get_number_of_price_ids() {

		// Check if we've already run this getter before.
		if ( ! is_null( $this->number_of_price_ids ) ) {
			return $this->number_of_price_ids;
		}

		$this->number_of_price_ids = $this->all_access_meta['all_access_number_of_price_ids'];

		return $this->number_of_price_ids;
	}

	/**
	 * Get the price ids/variations included in this All Access pass.
	 *
	 * @since    1.0.0

	 * @return   mixed - Either an array containing the included price ids, or 0 for all.
	 */
	private function get_included_price_ids() {

		// Check if we've already run this getter before.
		if ( ! is_null( $this->included_price_ids ) ) {
			return $this->included_price_ids;
		}

		$included_price_ids = ! is_null( $this->all_access_meta['all_access_included_price_ids'] ) ? $this->all_access_meta['all_access_included_price_ids'] : array();

		$typecasted_price_ids = array();

		// Typecast each value to be an integer.
		if ( is_array( $included_price_ids ) ) {
			foreach ( $included_price_ids as $included_price_id ) {
				$typecasted_price_ids[] = intval( $included_price_id );
			}
		}

		$this->included_price_ids = $typecasted_price_ids;
		return $this->included_price_ids;
	}

	/**
	 * Get the Customer setting for which All Access meta should be used for the All Access pass in question.
	 *
	 * @since    1.0.0
	 * @return   string - The array key which is used to determine which set of meta to use.
	 */
	private function get_meta_to_use() {

		// Check if we've already run this getter before.
		if ( ! is_null( $this->meta_to_use ) ) {
			return $this->meta_to_use;
		}

		if ( ! isset( $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ] ) ) {
			return null;
		}

		// Set the default to time_of_activation_meta if none is set.
		if ( ! isset( $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['meta_to_use'] ) ) {
			return 'time_of_activation_meta';
		}

		$this->meta_to_use = apply_filters( 'edd_all_access_meta_to_use', $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['meta_to_use'], $this->payment, $this->download_id, $this->price_id );
		return $this->meta_to_use;
	}

	/**
	 * Get the metadata for an All Access pass.
	 *
	 * @since    1.0.0

	 * @return   array $meta This will be all of the meta data used by the All Access pass to determine expiration, downloads per day, and more.
	 */
	public function all_access_meta() {

		// If the customer data has not been saved yet (this is a new purchase being saved and checked).
		if ( isset( $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ] ) ) {

			// If the meta_to_use variable is set.
			if ( isset( $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['meta_to_use'] ) ) {
				$meta_to_use = $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['meta_to_use'];
			} else {
				// If it is not set, default to use the "Settings At Time of Activation".
				$meta_to_use = 'time_of_activation_meta';
			}

			// If, for some reason, the customer_specific_meta array does not exist (likely an error or weird deletion behvaiour).
			if ( 'customer_specific_meta' === $meta_to_use && ! isset( $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['customer_specific_meta'] ) ) {
				// Set the blank customer specific array to match the time of purchase array.
				$meta = $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['time_of_activation_meta'];
			} else {
				// Get the right set of meta to use for this All Access pass using the $meta_to_use from the customer's All Access meta.
				$meta = $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ][ $meta_to_use ];
			}
		}

		// If this is a new purchase and the customer meta has not yet been set up, use the meta from the All Access product itself.
		if ( ! isset( $meta ) ) {

			// First through, lets actually make sure All Access is enabled for this product.
			$all_access_enabled = (bool) edd_all_access_enabled_for_download( $this->download_id );

			if ( ! $all_access_enabled ) {
				return false;
			}

			$meta = $this->get_default_pass_meta();
		}

		return wp_parse_args( $meta, $this->get_default_pass_meta() );
	}

	/**
	 * Gets the default pass metadata.
	 *
	 * @since 1.2.2
	 * @param string $start_time Optional: the pass start time.
	 * @return array
	 */
	private function get_default_pass_meta( $start_time = '' ) {
		return array(
			'all_access_start_time'                 => is_numeric( $start_time ) ? $start_time : edd_all_access_get_payment_utc_timestamp( $this->payment ),
			'all_access_duration_number'            => $this->product->duration,
			'all_access_duration_unit'              => $this->product->duration_unit,
			'all_access_download_limit'             => $this->product->download_limit,
			'all_access_download_limit_time_period' => $this->product->download_limit_period,
			'all_access_categories'                 => $this->product->categories,
			'all_access_number_of_price_ids'        => $this->product->number_price_ids,
			'all_access_included_price_ids'         => $this->product->included_price_ids,
		);
	}

	/**
	 * This function will check if an actually-purchased All Access pass's expiration-time is still valid. That is, is it still within its allowed time.
	 * Note that this does not account for All Access passes with a status of "expired". It only checks the time period.
	 * In fact, the get_status method uses this function to figure out the status.
	 * If you have changed an All Access pass to be expired, even though the period might still be valid, that pass itself is expired.
	 * This function just checks the time period - not the status. To get the status of an All Access pass, use EDD_All_Access_Pass->get_status.
	 *
	 * @since       1.0.0
	 * @return      bool
	 */
	private function get_time_period_still_valid() {

		// Check if we've already run this getter before.
		if ( ! is_null( $this->time_period_still_valid ) ) {
			return $this->time_period_still_valid;
		}

		$is_active = false;

		$current_time = current_time( 'timestamp' );

		$expiration_time = $this->get_expiration_time();

		// If this All Access pass's time period is still valid.
		if ( $current_time < $expiration_time ) {
			$is_active = true;
		} else {
			$is_active = false;
		}

		// If this All Access pass is set to never expire.
		if ( 'never' === $expiration_time ) {
			$is_active = true;
		}

		$this->time_period_still_valid = $is_active;
		return $this->time_period_still_valid;
	}

	/**
	 * Get the value for is_prior_of. If this pass has been upgraded, it will be the ID of the All Access Pass, if not, false.
	 *
	 * @since       1.0.0
	 * @return      bool
	 */
	private function get_is_prior_of() {

		// Leaving this here as a note that caching has been considered but because it can be set on-the-fly, we always need fresh data here.
		/* //phpcs:ignore Squiz.PHP.CommentedOutCode.Found
		if ( ! is_null( $this->is_prior_of ) ) {
			return $this->is_prior_of;
		}
		*/

		$is_prior_of = false;

		// If this is an All Access pass that hasn't been saved to the database yet (being purchased at this moment).
		if ( ! isset( $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ] ) ) {
			return false;
		}

		// If the payment ids don't match, something isn't right (perhaps a payment was deleted and there is old leftover data).
		if ( $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['payment_id'] !== $this->payment_id ) {
			return false;
		}

		if ( isset( $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['is_prior_of'] ) && ! empty( $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['is_prior_of'] ) ) {
			$is_prior_of = $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['is_prior_of'];
		}

		return $this->$is_prior_of = $is_prior_of;
	}

	/**
	 * Check if this All Access Pass has been upgraded-to by checking if it has prior all access passes attached to it.
	 *
	 * @since       1.0.0
	 * @return      array
	 */
	private function get_prior_all_access_passes() {

		// Check if we've already run this getter before.
		if ( ! is_null( $this->prior_all_access_passes ) ) {
			return $this->prior_all_access_passes;
		}

		$prior_all_access_passes = array();

		if ( ! isset( $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ] ) ) {
			return $prior_all_access_passes;
		}

		if ( isset( $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['prior_all_access_passes'] ) ) {
			$prior_all_access_passes = $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['prior_all_access_passes'];
		}

		$this->prior_all_access_passes = $prior_all_access_passes;
		return $this->prior_all_access_passes;
	}

	/**
	 * Check if this All Access Pass contains any renewal payment IDs which will "take over" when the current one expires.
	 *
	 * @since       1.0.0
	 * @return      array
	 */
	private function get_renewal_payment_ids() {

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

	/**
	 * Validates an individual renewal payment.
	 *
	 * @since 1.2.5
	 * @param int   $renewal_payment_id     The renewal payment ID.
	 * @param array $valid_payment_statuses The array of valid payment statuses.
	 * @return bool
	 */
	private function is_renewal_payment_valid( $renewal_payment_id, $valid_payment_statuses ) {

		// Check if the payment status is a valid one before including it the returned array.
		$renewal_payment = function_exists( 'edd_get_order' ) ? edd_get_order( $renewal_payment_id ) : edd_get_payment( $renewal_payment_id );

		// If this renewal payment does not have a valid status, the payment isn't valid.
		if ( ! in_array( $renewal_payment->status, $valid_payment_statuses, true ) ) {
			return false;
		}

		// If the payment's customer is not the same as the pass customer, return false.
		if ( $renewal_payment->customer_id !== $this->payment->customer_id ) {
			return false;
		}

		// Check for the AA product within the payment object.
		$download_found = false;
		if ( $renewal_payment instanceof EDD\Orders\Order ) {
			$downloads = edd_get_order_items(
				array(
					'order_id' => $renewal_payment_id,
					'fields'   => 'product_id',
				)
			);
			if ( in_array( $this->download_id, $downloads ) ) {
				return true;
			}
		} else {
			foreach ( $renewal_payment->downloads as $download ) {
				if ( (int) $this->download_id === (int) $download['id'] ) {
					$download_found = true;
					break;
				}
			}
		}

		// If the download wasn't found, the payment is not valid.
		if ( ! $download_found ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the timestamp for when the download-used counter was last reset
	 *
	 * @since       1.0.0
	 * @return      int
	 */
	private function get_downloads_used_last_reset() {

		if ( ! isset( $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ] ) ) {
			$this->downloads_used_last_reset = null;
			return $this->downloads_used_last_reset;
		}

		if ( ! isset( $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['downloads_used_last_reset'] ) ) {
			$this->downloads_used_last_reset = 0;
			return $this->downloads_used_last_reset;
		}

		$downloads_used_last_reset = $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['downloads_used_last_reset'];

		$this->downloads_used_last_reset = intval( $downloads_used_last_reset );
		return $this->downloads_used_last_reset;
	}

	/**
	 * Set and save the date when the downloads-used counter was last reset
	 *
	 * @since       1.0.0
	 * @param       int $time_of_last_download The timestamp of when the last download took place.
	 * @return      int The last date (in PHP time format) this All Access Pass was used to download a product.
	 */
	public function set_downloads_used_last_reset( $time_of_last_download ) {

		// Make sure we have fresh copies of all data and they are old-cache free.
		$this->retrieve_fresh_data();

		// Make sure the value is a time string.
		$time_of_last_download = absint( $time_of_last_download );

		// If the all access data is blank for some reason (aggressive object caching perhaps), don't save the new data as it will save bad, blank data.
		if ( ! empty( $this->customer_all_access_passes ) ) {
			$this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['downloads_used_last_reset'] = $time_of_last_download;
			$this->get_customer()->update_meta( 'all_access_passes', $this->customer_all_access_passes );
			$this->retrieve_fresh_data();
		}

		$this->downloads_used_last_reset = $time_of_last_download;
		return $this->downloads_used_last_reset;
	}

	/**
	 * Get the number of downloads this All Access Pass has been used for.
	 *
	 * @since       1.0.0
	 * @return      int The number of downloads this All Access Pass has been used for during this time period (per day, year etc).
	 */
	private function get_downloads_used() {

		if ( ! isset( $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ] ) ) {
			return 0;
		}

		if ( ! isset( $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['downloads_used'] ) ) {
			return 0;
		}

		$downloads_used = $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['downloads_used'];

		$this->downloads_used = $downloads_used;
		return $this->downloads_used;
	}

	/**
	 * Set the number of downloads this All Access Pass has been used for.
	 *
	 * @since       1.0.0
	 * @param       int $downloads_used The value to which the number of downloads used should be set.
	 * @return      int The number of downloads this All Access Pass has been used for during this time period (per day, year etc).
	 */
	public function set_downloads_used( $downloads_used ) {

		// Make sure we have fresh copies of all data and they are old-cache free.
		$this->retrieve_fresh_data();

		// Make sure the value is an int.
		$downloads_used = absint( $downloads_used );

		// If the all access data is blank for some reason (aggressive object caching perhaps), don't save the new data as it will save bad, blank data.
		if ( empty( $this->customer_all_access_passes ) ) {
			return $downloads_used;
		}

		// If the customer all access passes array is blank (possibly due to aggresive object caching) don't overwrite anything.
		if ( ! empty( $this->customer_all_access_passes ) ) {
			$this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['downloads_used'] = $downloads_used;
			$this->get_customer()->update_meta( 'all_access_passes', $this->customer_all_access_passes );
			$this->retrieve_fresh_data();
		}

		$this->downloads_used = $downloads_used;
		return $this->downloads_used;
	}

	/**
	 * We may need to reset the downloads-used counter. This function can be used to check that and will reset it if needed.
	 *
	 * @since    1.0.0

	 * @return   void
	 */
	public function maybe_reset_downloads_used_counter() {

		if ( 'active' !== $this->status ) {
			return;
		}

		$last_reset_period              = edd_all_access_get_download_limit_last_reset_period( $this );
		$periods_since_payment          = edd_all_access_get_download_limit_time_periods_since_payment( $this );
		$current_period_start_timestamp = edd_all_access_get_current_period_start_timestamp( $this );

		// If the current period start timestamp is false, this should never be reset because it is per period.
		if ( ! $current_period_start_timestamp ) {
			return;
		}

		// If, today, we are 1 completed period ahead of the period for which the download counter was reset, reset it for the last period.
		if ( $periods_since_payment > $last_reset_period ) {

			// Set the last reset date to the previous period's timestamp.
			$this->downloads_used_last_reset = $current_period_start_timestamp;

			// Call set_downloads_used_last_reset which does the actual updating in the database.
			$this->set_downloads_used_last_reset( $current_period_start_timestamp );

			// Set the downloads-used counter back to 0.
			$this->downloads_used = 0;

			// Call set_downloads_used which does the actual updating in the database.
			$this->set_downloads_used( 0 );

		}
	}

	/**
	 * This method relates to upgrades. Use this method to upgrade this All Access Pass to a new All Access Pass.
	 * Upgrading will set the start_date of the new All Access Pass to the start_date of the current one.
	 * It will also add a flag to the current All Access Pass letting it know it is an "prior" one.
	 * Additionally, it will add a new key to the new All Access Pass's meta containing all prior passes.
	 * If the current All Access Pass already contains prior passes (because it has already been upgraded)
	 * those will be passed along to the new one as well. This will happen in a scenario where upgrade paths are more than 2 (Small -> Medium -> Large).
	 *
	 * @since       1.0.0
	 * @param       EDD_All_Access_Pass $new_all_access_pass     The new All Access Pass object which this pass is being upgraded to.
	 * @return      boolean Whether this All Access Pass is an prior one or not.
	 */
	public function do_upgrade( $new_all_access_pass ) {

		// If the setup method was not run, this pass was not correctly instantiated, so don't execute anything.
		if ( ! $this->setup_is_completed ) {
			return false;
		}

		// Make sure we have fresh copies of all data and they are old-cache free.
		$this->retrieve_fresh_data();

		// You can't do an All Access upgrade for something that isn't an All Access pass. Only All Access -> All Access upgrades are handled here.
		if ( 'active' !== $this->status ) {
			return array(
				'error' => __( 'You cannot do an All Access upgrade from a product that is not All Access enabled.', 'edd-all-access' ),
			);
		}

		// Ensure that the new All Access Pass has been activated.
		if ( 'active' !== $new_all_access_pass->status ) {

			$activation_result = $new_all_access_pass->maybe_activate();

			if ( isset( $activation_result['error'] ) ) {
				return $activation_result;
			}
		}

		// If this All Access Pass has already been upgraded, you can't upgrade something twice so fail.
		if ( $this->get_is_prior_of() ) {
			return array(
				'error' => __( 'This All Access Pass has already been upgraded. It cannot be upgraded again.', 'edd-all-access' ),
			);
		}

		// If the customer all access passes array is blank (possibly due to aggresive object caching) don't overwrite anything.
		if ( empty( $new_all_access_pass->customer_all_access_passes ) ) {
			return array(
				'error' => __( 'No data found. Try resetting your object caching (if any).', 'edd-all-access' ),
			);
		}

		// If the new All Access Pass has already been upgraded-to, you can't upgrade-to something twice so fail.
		if ( isset( $new_all_access_pass->customer_all_access_passes[ $new_all_access_pass->download_id . '_' . $new_all_access_pass->price_id ]['prior_all_access_passes'] ) ) {
			return array(
				'error' => __( 'The new All Access Pass has already been upgraded-to. It cannot be upgraded-to again.', 'edd-all-access' ),
			);
		}

		// If this All Access Pass has prior All Access Passes, store those and then remove them.
		if ( isset( $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['prior_all_access_passes'] ) ) {
			$prior_all_access_passes = $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['prior_all_access_passes'];
			unset( $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['prior_all_access_passes'] );
		} else {
			$prior_all_access_passes = array();
		}

		// Add this All Access Pass to the list of inital ones.
		$prior_all_access_passes[ $this->get_id() ] = $this->get_id();

		// For the new All Access Pass, set the "Time of Activation" start time for the time of activation meta to match the start time of the intitial All Access Pass.
		$this->customer_all_access_passes[ $new_all_access_pass->download_id . '_' . $new_all_access_pass->price_id ]['time_of_activation_meta']['all_access_start_time'] = $this->get_start_time();

		// Also for the new All Access Pass, set the "Customer Specific" start time for the time of activation meta to match the start time of the intitial All Access Pass.
		$this->customer_all_access_passes[ $new_all_access_pass->download_id . '_' . $new_all_access_pass->price_id ]['customer_specific_meta']['all_access_start_time'] = $this->get_start_time();

		// Tell this All Access Pass which pass ID it was upgraded to.
		$this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['is_prior_of'] = $new_all_access_pass->id;

		// Add the prior All Access Passes list to the new All Access Pass.
		$this->customer_all_access_passes[ $new_all_access_pass->download_id . '_' . $new_all_access_pass->price_id ]['prior_all_access_passes'] = $prior_all_access_passes;

		// Update the customer meta for this All Access Pass.
		$this->get_customer()->update_meta( 'all_access_passes', $this->customer_all_access_passes );
		$this->retrieve_fresh_data();

		// Save a note in the old prior EDD Payment that the All Access Pass has been upgraded for this product.
		// Translators: The name of the All Access Product to use in the payment note.
		$activation_note_old = sprintf( __( 'All Access "%1$s" has been upgraded to All Access "%2$s".', 'edd-all-access' ), get_the_title( $this->download_id ), get_the_title( $new_all_access_pass->download_id ) );
		edd_insert_payment_note( $this->payment->ID, $activation_note_old );

		// Save a note in the newly upgraded EDD Payment that the All Access Pass has been upgraded for this product.
		// Translators: The name of the All Access Product to use in the payment note.
		$activation_note_new = sprintf( __( 'All Access "%1$s" was an upgrade from All Access "%2$s".', 'edd-all-access' ), get_the_title( $new_all_access_pass->download_id ), get_the_title( $this->download_id ) );
		edd_insert_payment_note( $new_all_access_pass->payment->ID, $activation_note_new );

		// Expire the prior All Access Pass now as it has been upgraded.
		$this->maybe_expire( array( 'override_time_period' => true ) );

		return array(
			'success' => __( 'Successfully upgraded', 'edd-all-access' ),
		);
	}

	/**
	 * Attempt to renew an All Access Pass.
	 *
	 * @since       1.0.0
	 * @return      mixed
	 */
	public function maybe_renew() {

		// If the setup method was not run, this pass was not correctly instantiated, so don't execute anything.
		if ( ! $this->setup_is_completed ) {
			return false;
		}

		// If the status is not expired, do not renew. It must be expired to renew.
		if ( 'expired' !== $this->status ) {

			return array(
				'error' => __( 'This All Access Pass is not expired. Unable to renew.', 'edd-all-access' ),
			);
		}

		$renewal_payment_ids = $this->get_renewal_payment_ids();

		// Check if any renewal payments exist to make it re-activated with a new payment.
		if ( empty( $renewal_payment_ids ) ) {

			return array(
				'error' => __( 'No renewal payments are attached to this All Access pass.', 'edd-all-access' ),
			);
		}

		// Use the first renewal payment ID with an attempt to reactivate.
		$renewal_payment_id = reset( $renewal_payment_ids );

		return edd_all_access_get_and_activate_pass( $renewal_payment_id, $this->download_id, $this->price_id );
	}

	/**
	 * Convert an All Access Payment to be an active one. There are a few different things that make an All Access pass active.
	 * All of those things are carried out by this function.
	 *
	 * @since       1.0.0
	 * @return      mixed
	 */
	public function maybe_activate() {

		// If the setup method was not run, this pass was not correctly instantiated, so don't execute anything.
		if ( ! $this->setup_is_completed ) {
			return false;
		}

		// Make sure we have fresh copies of all data and they are old-cache free.
		$this->retrieve_fresh_data();

		// If this purchased download is not an "All Access" post.
		if ( ! in_array( $this->download_id, $this->all_access_posts, true ) ) {
			return array(
				'error' => __( 'The purchased download is not an All Access enabled product', 'edd-all-access' ),
			);
		}

		if ( ! in_array( $this->payment->status, edd_all_access_valid_order_statuses(), true ) ) {
			return array(
				'error' => __( 'Payment does not have valid payment status for All Access', 'edd-all-access' ),
			);
		}

		// Get the list of ids in this payment that have previously been set to "active" (if any).
		$all_access_active_ids = $this->get_order_meta( '_edd_aa_active_ids' );
		$all_access_active_ids = is_array( $all_access_active_ids ) ? $all_access_active_ids : array();

		// Get the list of ids in this payment that have previously been set to "expired" (if any).
		$all_access_expired_ids = $this->get_order_meta( '_edd_aa_expired_ids', true );
		$all_access_expired_ids = is_array( $all_access_expired_ids ) ? $all_access_expired_ids : array();

		// Active/Inactive All Access purchases are stored in the _edd_aa_active_ids and _edd_aa_expired_ids using the Download Id and Price ID combined into a string.
		// For example, if the download id is 5456 and the purchased price id was 3, the array key will be 5456-3.
		$purchased_aa_download_key = $this->download_id . '-' . $this->price_id;

		/**
		 * If this All Access purchase is already listed as active in this payment's meta, while also being active in this customer's meta, don't attempt a re-creation.
		 * The reason this prevention is here is the following scenario:
		 * - "Person A" buys AA pass.
		 * - "Person A" manually renews AA pass early.
		 * - On the renewal payment, an admin decides to change the customer attached to the payment to "Person B",
		 *     which turns that renewal payment into an initial purchase for "Person B".
		 * - The admin then decides to switch the payment's customer back to the original customer,
		 *       which means this should be a renewal payment again, not an initial payment.
		 * - This check prevents that, allowing the already generated data to be used.
		 * It's also why we don't wipe out the AA data when the payment's customer changes. It allows it to be recovered, with the history in-tact.
		 */
		if (
			// If this payment already has had this AA pass get activated...
			array_key_exists( $purchased_aa_download_key, $all_access_active_ids ) &&
			// And it also exists in the customer meta...
			(
				isset( $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ] ) &&
				intval( $this->payment->ID ) === intval( $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['payment_id'] )
			)
		) {
			// Don't attempt a reactivation.
			return array(
				'error' => __( 'This All Access Pass is already active.', 'edd-all-access' ),
			);
		}

		// If this All Access has already expired (this can happen if an All Access pass expired and then the start date was manually changed to be in the future).
		if ( array_key_exists( $purchased_aa_download_key, $all_access_expired_ids ) ) {

			// NEVER allow an expired All Access to be reactivated. That is when bad things happen.
			return array(
				'error' => __( 'This All Access Pass has already expired. It must be repurchased to be re-activated.', 'edd-all-access' ),
			);
		}

		// Update the customer meta which stores the settings for the customer's All Access Pass.

		// So that we can save the "Settings at Time of Purchase", get the All Access meta that is set in the purchased post's meta at this moment.
		// $product = new \EDD\AllAccess\Models\AllAccessProduct( $this->download_id );

		// A quick note about the start time of an All Access Pass. If never purchased before, use the payment date (includes retroactive payments).
		// For renewals, we use the time of the previous pass's expiration.
		$time_of_payment = edd_all_access_get_payment_utc_timestamp( $this->payment );

		$prior_of                = $this->get_is_prior_of();
		$prior_all_access_passes = $this->get_prior_all_access_passes();

		// If this All Access pass has never been saved/bought by this customer before, or has been upgraded away from (or to), this is brand new.
		if ( ! isset( $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ] ) || $prior_of || ! empty( $prior_all_access_passes ) ) {

			// If no all access passes have been purchased by this customer and their array of passes is empty, declare the variable as an array.
			if ( empty( $this->customer_all_access_passes ) ) {
				$this->customer_all_access_passes = array();
			}

			// Clear out any old All Access Pass data.
			if ( isset( $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ] ) ) {
				unset( $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ] );
			}

			$new_aa_pass = array();

			// Update/Save the customer's All Access pass data using all the default settings for a brand new pass.
			$new_aa_pass[ $this->download_id . '_' . $this->price_id ] = array(
				'download_id'               => $this->download_id,
				'price_id'                  => $this->price_id,
				'payment_id'                => $this->payment_id,
				'renewal_payment_ids'       => array(),
				'downloads_used'            => 0,
				'downloads_used_last_reset' => $time_of_payment,
				'utc'                       => true, // This is a flag added in version 1.1 which lets us know the times were stored in UTC timezone.
				// The metadata is really set elsewhere; defaults are set here.
				'time_of_activation_meta'   => $this->get_default_pass_meta(),
				'customer_specific_meta'    => $this->get_default_pass_meta(),
			);

			// Add this new All Access Pass to the start of the array of customer passes - this is so that it is sorted with the newest first.
			$this->customer_all_access_passes = $new_aa_pass + $this->customer_all_access_passes;

			$this->get_customer()->update_meta( 'all_access_passes', $this->customer_all_access_passes );
			$this->retrieve_fresh_data();

			// Save a note in the EDD Payment that All Access has been activated for this product.
			// Translators: The name of the All Access Product being left in a payment note.
			$activation_note = sprintf( __( 'All Access enabled for the product called "%s"', 'edd-all-access' ), get_the_title( $this->download_id ) );
			edd_insert_payment_note( $this->payment->ID, $activation_note );

			// Add this id to the list of active All Access products in this payment.
			$all_access_active_ids[ $purchased_aa_download_key ] = true;
			edd_update_payment_meta( $this->payment->ID, '_edd_aa_active_ids', $all_access_active_ids );

			// If you need something to happen when an All Access payment activates, this hook is the place to do it.
			do_action( 'edd_all_access_activated', $this->payment_id, $this->download_id, $this->price_id );

		} else {
			// This pass has already been purchased. Now we need to find out if this is a valid renewal. First, lets set up the All Access Pass object for the existing pass.
			$pre_existing_all_access_pass = edd_all_access_get_pass( $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['payment_id'], $this->download_id, $this->price_id );

			// Get the timestamp of the pre-existing pass.
			$pre_existing_all_access_pass_payment_time = edd_all_access_get_aap_purchase_timestamp( $pre_existing_all_access_pass );

			// Add the payment being activated to the list of renewals.
			if ( ! in_array( $this->payment_id, $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['renewal_payment_ids'], true ) ) {
				$payment_was_already_renewal = false;
				$this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['renewal_payment_ids'][] = $this->payment_id;
				$this->get_customer()->update_meta( 'all_access_passes', $this->customer_all_access_passes );
			} else {
				$payment_was_already_renewal = true;
			}

			// If the existing All Access Pass is still active, we can stop processing here as, if it was valid, the renewal has been stored.
			if ( 'active' === $pre_existing_all_access_pass->status ) {

				// If this payment does not exist in the list of renewals, something was wrong with it as it was filtered out.
				if ( ! in_array( $this->payment_id, $this->get_renewal_payment_ids(), true ) ) {
					return array(
						'failure' => __( 'Something was wrong with the payment ID and it was not added as a renewal.', 'edd-all-access' ),
					);
				} else {
					// The renewal payment has been stored for later.

					// Save a note in the EDD Payment that All Access has been stored as a renewal for this product.
					if ( ! $payment_was_already_renewal ) {
						// Translators: The name of the All Access product being left in a payment note.
						$activation_note = sprintf( __( 'This payment will be used once the All Access Pass expires for "%s"', 'edd-all-access' ), get_the_title( $this->download_id ) );
						edd_insert_payment_note( $this->payment->ID, $activation_note );
					}

					// We can leave here now.
					return array(
						'success' => __( 'This payment will be used once the All Access Pass expires.', 'edd-all-access' ),
					);
				}
			} else {

				// The existing All Access Pass is expired or invalid and thus, this activation is a renewal attempt.

				$renewal_payment_ids = $this->get_renewal_payment_ids();

				// If no valid renewal payments exist, stop here and don't activate.
				if ( empty( $renewal_payment_ids ) ) {
					return array(
						'error' => __( 'No valid payments were found to use for activation.', 'edd-all-access' ),
					);
				}

				$renewal_payment_id = $renewal_payment_ids[0];
				$renewal_payment    = edd_get_payment( $renewal_payment_id );

				// Update the payment_id because that needs to reflect this new payment.
				$this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['payment_id'] = $renewal_payment_id;

				// Update the downloads_used because this new All Access Pass has never been used.
				$this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['downloads_used'] = 0;

				$renewal_payment_time = edd_all_access_get_payment_utc_timestamp( $renewal_payment );

				$activation_start_time_note = '';

				// Figure out the correct new $renewal_start_time
				// If there is no expiration time for the previous All Access Pass, its payment might have been deleted so use the time right now.
				if ( empty( $pre_existing_all_access_pass->expiration_time ) ) {

					// Set the renewal start time to be the time of the renewal payment.
					$renewal_start_time = $renewal_payment_time;

				} elseif ( $renewal_payment_time < $pre_existing_all_access_pass->expiration_time ) {

					// If the renewal payment happened before the previous All Access Pass was expired, use the expiration date of the previous pass.
					$renewal_start_time = $pre_existing_all_access_pass->expiration_time;

					// Save a note in the EDD Payment to let us know why the start time was used.
					$activation_start_time_note = sprintf( __( 'Start Time is set to start when the previous expired.', 'edd-all-access' ), get_the_title( $this->download_id ) );

				} elseif ( $renewal_payment_time > $pre_existing_all_access_pass->expiration_time ) {
					// If the renewal payment happened after the previous All Access Pass was expired, use the time of the renewal payment.
					$renewal_start_time = $renewal_payment_time;

					// Save a note in the EDD Payment to let us know why the start time was used.
					$activation_start_time_note = sprintf( __( 'Start Time is set to the time of the payment.', 'edd-all-access' ), get_the_title( $this->download_id ) );
				}

				// Unlikely, but if the renewal start time is still empty, leave a note so we know where to start looking when debugging.
				if ( empty( $renewal_start_time ) ) {
					$renewal_start_time = $renewal_payment_time;

					// Save a note in the EDD Payment to let us know why the start time was used.
					$activation_start_time_note = sprintf( __( 'All Access Error: No renewal start time was found.', 'edd-all-access' ), get_the_title( $this->download_id ) );
				}

				// Update the downloads_used_last_reset because this new All Access Pass has never been used.
				$this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['downloads_used_last_reset'] = $renewal_start_time;

				// Update all the "Time Of Activation" meta to match the settings in the Product's meta.
				$this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['time_of_activation_meta'] = $this->get_default_pass_meta( $renewal_start_time );

				// Update all the Start Time in the "Customer Specific" meta to be the time of the previous expiration as well.
				$this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['customer_specific_meta']['all_access_start_time'] = $renewal_start_time;

				// Because we are using this renewal ID, using array_shift, remove this payment id from the list of renewal payment ids to use in the future.
				array_shift( $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['renewal_payment_ids'] );

				// If this renewal pass was initially the result of an upgrade, we will also remove any information about upgrades now.
				// A renewed All Access Pass was not upgraded - it was renewed. Only the initial one can be upgraded.
				// This prevents poisoned data for possible future upgrades by this customer. For example, if they let everything expire,
				// and then decide to come back, start back at the bottom and upgrade their way back again.
				unset( $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['prior_all_access_passes'] );
				unset( $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ]['is_prior_of'] );

				// So that we can move this item to the top of the array, store it now.
				$temp_item_storer = array(
					$this->download_id . '_' . $this->price_id => $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ],
				);

				// Now lets remove it.
				unset( $this->customer_all_access_passes[ $this->download_id . '_' . $this->price_id ] );

				// And then we'll re-add it to the top.
				$this->customer_all_access_passes = $temp_item_storer + $this->customer_all_access_passes;

				// Save the updated data.
				$this->get_customer()->update_meta( 'all_access_passes', $this->customer_all_access_passes );
				$this->retrieve_fresh_data();

				// Save a note in the EDD Payment that All Access has been activated for this product.
				// Translators: The name of the All Access product being left in a note on an EDD payment to indicate it was renewed.
				$activation_note = sprintf( __( 'All Access enabled for the product called "%s" (Renewal).', 'edd-all-access' ), get_the_title( $this->download_id ) );
				edd_insert_payment_note( $renewal_payment_id, $activation_note . ' ' . $activation_start_time_note );

				// Add this id to the list of active All Access products in this payment.
				$all_access_active_ids[ $purchased_aa_download_key ] = true;
				edd_update_payment_meta( $renewal_payment_id, '_edd_aa_active_ids', $all_access_active_ids );

				// If you need something to happen when an All Access payment activates, this hook is the place to do it.
				do_action( 'edd_all_access_activated', $renewal_payment_id, $this->download_id, $this->price_id );

				return array(
					'success' => __( 'This All Access Pass has been renewed.', 'edd-all-access' ),
				);
			}
		}

	}

	/**
	 * Convert an active All Access Payment to be an expired one. There are a few different things that make an All Access pass expired.
	 * All of those things are carried out by this function.
	 *
	 * @since       1.0.0
	 * @param       array $args The args to control how expiration is handled.
	 * @return      mixed
	 */
	public function maybe_expire( $args = array() ) {

		// If the setup method was not run, this pass was not correctly instantiated, so don't execute anything.
		if ( ! $this->setup_is_completed ) {
			return false;
		}

		// Make sure we have fresh copies of all data and they are old-cache free.
		$this->retrieve_fresh_data();

		$default_args = array(
			'override_time_period'   => false, // By default, passes that still have a valid time period won't expire.
			'override_active_checks' => false, // By default, we check to make sure the pass is active before expiring it (and running deactivation hooks).
		);

		$args = wp_parse_args( $args, $default_args );

		// If this purchased download is not an "All Access" post.
		if ( ! in_array( $this->download_id, $this->all_access_posts, true ) ) {
			return array(
				'error' => __( 'The purchased download is not an All Access enabled product', 'edd-all-access' ),
			);
		}

		// Check the All Access time period it now so we don't accidentally activate an All Access pass that shouldn't be active.
		$time_period_still_valid = $this->get_time_period_still_valid();

		if ( $time_period_still_valid && ! $args['override_time_period'] ) {

			// If this All Access purchase should not be expired yet.
			return array(
				'error' => __( 'This All Access Pass\'s time period is not expired.', 'edd-all-access' ),
			);
		}

		// Get the list of ids in this payment that have previously been set to "active" (if any).
		$all_access_active_ids = $this->get_order_meta( '_edd_aa_active_ids' );

		// Get the list of ids in this payment that have previously been set to "expired" (if any).
		$all_access_expired_ids = $this->get_order_meta( '_edd_aa_expired_ids' );

		// If we should be checking for active/inactive states to prevent double deactivations (in rare cases you could override this to re-deactivate a pass).
		if ( ! $args['override_active_checks'] ) {

			// Set a default in case there aren't any expired All Access ids that already exist in this payment.
			if ( empty( $all_access_expired_ids ) ) {
				$all_access_expired_ids = array();
			}

			// If this Payment has no active All Access products in it (if they exist, they might already be expired).
			if ( ! is_array( $all_access_active_ids ) || empty( $all_access_active_ids ) ) {

				$this->maybe_renew();

				return array(
					'error' => sprintf( __( 'Payment %d does not contain any active All Access purchases.', 'edd-all-access' ), $this->payment->ID ),
				);
			}

			// Active/Incative All Access purchases are stored in the _edd_aa_active_ids and _edd_aa_expired_ids using the Download Id and Price ID combined into a string.
			// For example, if the download id is 5456 and the purchased price id was 3, the array key will be 5456-3.
			$purchased_aa_download_key = $this->download_id . '-' . $this->price_id;

			// If this All Access purchase is not currently listed as active in this payment.
			if ( ! array_key_exists( $purchased_aa_download_key, $all_access_active_ids ) ) {

				// If this All Access purchase is currently listed as expired in this payment.
				if ( array_key_exists( $purchased_aa_download_key, $all_access_expired_ids ) ) {

					$this->maybe_renew();

					return array(
						'error' => __( 'This All Access Pass is already expired.', 'edd-all-access' ),
					);
				} else {
					// If this All Access purchase is not listed in the active OR expired arrays in this payment.
					return array(
						'error' => __( 'That All Access product does not exist within this payment.', 'edd-all-access' ),
					);
				}
			}
		}

		// Remove this payment from the list of active All Access ids in this payment.
		unset( $all_access_active_ids[ $purchased_aa_download_key ] );

		// Update the list of active All Access posts.
		if ( empty( $all_access_active_ids ) ) {
			$this->payment->delete_meta( '_edd_aa_active_ids' );
		} else {
			edd_update_payment_meta( $this->payment->ID, '_edd_aa_active_ids', $all_access_active_ids );
		}

		// Add this to the list of expired All Access ids in this payment.
		$all_access_expired_ids[ $purchased_aa_download_key ] = true;
		edd_update_payment_meta( $this->payment->ID, '_edd_aa_expired_ids', $all_access_expired_ids );

		// If this is a prior pass that has been upgraded, set the status to "upgraded" instead of "expired".
		if ( $this->get_is_prior_of() ) {

			// Save a note in the EDD Payment that All Access has expired for this product.
			// Translators: The name of the All Access product which was upgraded-from and is now expired.
			$expiration_note = sprintf( __( 'All Access upgraded (expired) for the product called "%s"', 'edd-all-access' ), get_the_title( $this->download_id ) );
			edd_insert_payment_note( $this->payment->ID, $expiration_note );

			$this->set_and_return_status( 'upgraded' );
		} else {

			// Save a note in the EDD Payment that All Access has expired for this product.
			// Translators: The name of the All Access product which has just expired.
			$expiration_note = sprintf( __( 'All Access expired for the product called "%s"', 'edd-all-access' ), get_the_title( $this->download_id ) );
			edd_insert_payment_note( $this->payment->ID, $expiration_note );

			$this->set_and_return_status( 'expired' );
		}

		// If you need something to happen when an All Access payment expires, this hook is the place to do it.
		do_action( 'edd_all_access_expired', $this, $args );

		// Now that the pass has been expired, check if any renewal payments exist to make it re-activated with a new payment.
		$this->maybe_renew();
	}

	/**
	 * Gets the order metadata for a specific key on this payment.
	 *
	 * @since 1.2.4.2
	 * @param string $key
	 * @return mixed
	 */
	private function get_order_meta( $key ) {
		return function_exists( 'edd_get_order_meta' ) ? edd_get_order_meta( $this->payment_id, $key, true ) : $this->payment->get_meta( $key );
	}
}
