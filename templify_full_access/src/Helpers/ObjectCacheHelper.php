<?php
/**
 * ObjectCacheHelper.php
 *
 * @package   edd-all-access
 * @copyright Copyright (c) 2021, Easy Digital Downloads
 * @license   GPL2+
 * @since     1.2
 */

namespace EDD\AllAccess\Helpers;

/*
 * Integrates EDD All Access with the EDD Auto Register extension
 *
 * @since 1.0.0
 */
class ObjectCacheHelper {

	/**
	 * The cache group to use for passes.
	 *
	 * @var int
	 * @since 1.2.4.2
	 */
	private $cache_group = 'edd-all-access-passes';

	/**
	 * Array of pass IDs processed already.
	 *
	 * @var array
	 * @since 1.2.4.2
	 */
	private $processed_passes = array();

	/**
	 * Get things started
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function __construct() {
		// Set the object cache persistence.
		\add_action( 'init', array( $this, 'set_cache_group_persistence' ) );

		// Listen for pass changes.
		\add_action( 'edd_all_access_expired', array( $this, 'clear_cache_on_pass_expired' ), 10, 1 );
		\add_action( 'edd_all_access_data_refreshed', array( $this, 'clear_cache_on_pass_refresh' ), 10, 1 );
		\add_action( 'edd_all_access_status_changed', array( $this, 'clear_cache_on_pass_status_change' ), 10, 1 );
		\add_action( 'edd_all_access_activated', array( $this, 'clear_cache_on_pass_activation' ), 10, 3 );
	}

	/**
	 * Get a pass from cache, or instantiate a new one and set the cache.
	 *
	 * @since 1.2.4.2
	 *
	 * @param int $payment_id  The Payment ID for the pass.
	 * @param int $download_id The Download ID for the pass.
	 * @param int $price_id    The Price ID of the pass.
	 *
	 * @return EDD_All_Access_Pass
	 */
	public function get_pass( $payment_id = 0, $download_id = 0, $price_id = 0 ) {
		$pass_id     = $payment_id . '_' . $download_id . '_' . $price_id;
		$cached_pass = wp_cache_get( $pass_id, $this->cache_group );

		if ( false !== $cached_pass ) {
			return $cached_pass;
		}

		$all_access_pass = new \EDD_All_Access_Pass( $payment_id, $download_id, $price_id );
		wp_cache_set( $pass_id, $all_access_pass, $this->cache_group, HOUR_IN_SECONDS );

		return $all_access_pass;
	}

	/**
	 * Set the cache group persistence.
	 *
	 * @since 1.2.4.2
	 */
	public function set_cache_group_persistence() {
		/**
		 * Allow All Access to use persistent caching on passes.
		 *
		 * By default, as of 1.2.4.2, we're setting this to `false` while we work towards a persistent implmentation.
		 * This filter allows developers and store owners to swtich this to `true` and test before enabling it by default.
		 *
		 * @since 1.2.4.2
		 *
		 * @param bool $use_persistent_cache Defaults to false
		 */
		$use_persistant_cache = apply_filters( 'edd_all_access_use_persistent_cache', false );

		if ( false === $use_persistant_cache ) {
			wp_cache_add_non_persistent_groups( $this->cache_group );
		}
	}

	/**
	 * Clear pass cache when passes expire.
	 *
	 * @since 1.2.4.2
	 *
	 * @param EDD_All_Access_Pass $pass_object The pass object being expired.
	 */
	public function clear_cache_on_pass_expired( \EDD_All_Access_Pass $pass_object ) {
		if ( ! $pass_object instanceof \EDD_All_Access_Pass || empty( $pass_object->id ) ) {
			return;
		}

		if ( is_wp_error( $pass_object->id ) ) {
			return;
		}

		$deleted_cache = wp_cache_delete( $pass_object->id, $this->cache_group );

		if ( $deleted_cache ) {
			edd_debug_log( 'AA:Expired Pass cache cleared for ' . $pass_object->id );
		}
	}

	/**
	 * Clear pass cache when passes are refreshed.
	 *
	 * @since 1.2.4.2
	 *
	 * @param EDD_All_Access_Pass $pass_object The pass object being refreshed.
	 */
	public function clear_cache_on_pass_refresh( \EDD_All_Access_Pass $pass_object ) {
		if ( ! $pass_object instanceof \EDD_All_Access_Pass || empty( $pass_object->id ) ) {
			return;
		}

		if ( is_wp_error( $pass_object->id ) ) {
			return;
		}

		$deleted_cache = wp_cache_delete( $pass_object->id, $this->cache_group );

		if ( $deleted_cache ) {
			edd_debug_log( 'AA:Refreshed Pass cache cleared for ' . $pass_object->id );
		}
	}

	/**
	 * Clear pass cache when passes are activated
	 *
	 * @since 1.2.4.2
	 *
	 * @param int $payment_id  The Payment ID for the pass.
	 * @param int $download_id The Download ID for the pass.
	 * @param int $price_id    The Price ID of the pass.
	 */
	public function clear_cache_on_pass_activation( $payment_id = 0, $download_id = 0, $price_id = 0 ) {
		if ( empty( $payment_id ) || empty( $download_id ) ) {
			return;
		}

		$cache_key     = $payment_id . '_' . $download_id . '_' . $price_id;
		$deleted_cache = $this->maybe_delete_cache( $cache_key );

		if ( $deleted_cache ) {
			edd_debug_log( 'AA:Activation Pass cache cleared for ' . $cache_key );
		}
	}

	/**
	 * Clear pass cache when a pass status changes.
	 *
	 * @since 1.2.4.2
	 *
	 * @param EDD_All_Access_Pass $pass_object The pass object being refreshed.
	 */
	public function clear_cache_on_pass_status_change( \EDD_All_Access_Pass $pass_object ) {
		if ( ! $pass_object instanceof \EDD_All_Access_Pass || empty( $pass_object->id ) ) {
			return;
		}

		if ( is_wp_error( $pass_object->id ) ) {
			return;
		}

		$deleted_cache = wp_cache_delete( $pass_object->id, $this->cache_group );

		if ( $deleted_cache ) {
			edd_debug_log( 'AA:Status change cache cleared for ' . $pass_object->id );
		}
	}

	/**
	 * Helper function to possible delete cache.
	 *
	 * Sees if we've already processed the cache update for this pass already, if we have don't process it again, so we can avoid
	 * overloading an oject cache for the same pass.
	 *
	 * If it hasn't been processed, the pass cache is cleared.
	 *
	 * @since 1.2.4.2
	 *
	 * @param string $pass_id The Pass ID to clear the cache for. This is in the format of {Payment ID}_{Download ID}_{Price ID}.
	 *
	 * @return boolean If the wp_cache_delete was successful or not.
	 */
	private function maybe_delete_cache( $pass_id ) {
		if ( in_array( $pass_id, $this->processed_passes, true ) ) {
			edd_debug_log( 'AA:Cache Delete skipped, already processed pass ' . $pass_id );
			return false;
		}

		$this->processed_passes[] = $pass_id;

		return wp_cache_delete( $pass_id, $this->cache_group );

	}

}
