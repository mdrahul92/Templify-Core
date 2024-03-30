<?php
/**
 * Integration functions to make All Access compatible with EDD Software Licenses
 *
 * @package     EDD\EDDAllAccess\Functions
 * @since       1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Integrates EDD All Access with the EDD Software Licensing extension
 *
 * @since 1.0.0
 */
class EDD_All_Access_Software_Licensing {

	/**
	 * Get things started
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function __construct() {

		if ( ! class_exists( 'EDD_Software_Licensing' ) ) {
			return;
		}

		add_filter( 'edd_all_access_duration_unit_metabox_options', array( $this, 'edd_all_access_add_sl_duration_option' ) );
		add_filter( 'edd_all_access_duration_unit_customer_options', array( $this, 'edd_all_access_sl_duration_customer_options' ), 10, 4 );
		add_action( 'admin_notices', array( $this, 'edd_all_access_sl_too_old_notice' ) );
		add_filter( 'edd_sl_check_item_name', array( $this, 'edd_all_access_sl_name_matches' ), 10, 4 );
		add_filter( 'edd_sl_id_license_match', array( $this, 'edd_all_access_sl_id_matches' ), 10, 4 );
		add_filter( 'edd_sl_force_check_by_name', array( $this, 'edd_all_access_sl_force_check_by_name' ) );
		add_action( 'template_redirect', array( $this, 'edd_all_access_sync_renewals' ) );
		add_filter( 'edd_all_access_get_start_time', array( $this, 'edd_all_access_sync_start_time_to_license_start_time' ), 10, 5 );
		add_filter( 'edd_all_access_get_expiration_time', array( $this, 'edd_all_access_sync_expiration_time_with_sl' ), 10, 2 );
		add_action( 'edd_sl_license_upgraded', array( $this, 'edd_all_access_store_sl_upgrade' ), 10, 2 );
		add_action( 'edd_all_access_activated', array( $this, 'edd_all_access_handle_sl_upgrade' ), 10, 3 );
		add_filter( 'edd_sl_allow_bundle_activation', array( $this, 'allow_bundle_activations' ), 10, 2 );
		add_filter( 'edd_all_access_pass_would_be_end_time', array( $this, 'check_if_pass_would_expire' ), 10, 4 );
		add_action( 'edd_all_access_pass_status_validated', array( $this, 'check_license_status_for_valid_pass' ) );
		add_filter( 'edd_all_access_pass_status', array( $this, 'check_license_status_for_pass_status' ), 10, 2 );
	}

	/**
	 * Make sure we are at the minimum version of Software Licensing - which is 3.5.
	 *
	 * @since 1.0.0
	 */
	public function edd_all_access_sl_too_old_notice() {

		if ( defined( 'EDD_SL_VERSION' ) && version_compare( EDD_SL_VERSION, '3.5.18', '<' ) ) {
			?>
			<div class="notice notice-error">
				<p><?php echo esc_html( __( 'EDD All Access: Your version of EDD Software Licensing must be updated to version 3.5.18 or later to use the All Access extension in conjunction with Software Licensing.', 'edd-all-access' ) ); ?></p>
			</div>
			<?php
		}
	}

	/**
	 * Add "Sync with License expiration" as an expiration option for All Access.
	 * Not that because "Sync with Recurring" was originally the only syncing option,
	 * and because Syncing with recurring is a bad idea if the product is also licensed (due to early renewals in SL which cause out-of-sync dates)),
	 * this setting will only be available if EDD Recurring is not enabled/activated.
	 *
	 * @since    1.1.0
	 * @param    array $all_access_length_options Array of expiration options for All Access.
	 * @return   array $all_access_length_options Array (modified) of expiration options for All Access
	 */
	public function edd_all_access_add_sl_duration_option( $all_access_length_options ) {

		// If EDD Recurring Payments exists, "Sync with License" is added by the Recurring Integration. See notes for this method for more information.
		if ( class_exists( 'EDD_Recurring' ) ) {
			return $all_access_length_options;
		}

		$all_access_length_options['edd_software_licensing'] = __( 'Sync with License expiration', 'edd-all-access' );

		return $all_access_length_options;
	}

	/**
	 * Add "Sync with License expiration" as an expiration option for All Access on the Customer Meta (Single All Access Pass Page).
	 * Not that because "Sync with Recurring" was originally the only syncing option,
	 * and because Syncing with recurring is a bad idea if the product is also licensed (due to early renewals in SL which cause out-of-sync dates)),
	 * this setting will only be available if EDD Recurring is not enabled/activated. If EDD Recurring is enabled, it falls back to the license.
	 *
	 * @since    1.1.0
	 * @param    array       $all_access_length_options Array of expiration options for All Access.
	 * @param    EDD_Payment $payment The EDD Payment where the AA pass originated.
	 * @param    int         $download_id The download ID where the AA pass originated.
	 * @param    int         $price_id The variable price ID where the AA pass originated.
	 * @return   array       $all_access_length_options Array (modified) of expiration options for All Access
	 */
	public function edd_all_access_sl_duration_customer_options( $all_access_length_options, $payment, $download_id, $price_id ) {

		// If EDD Recurring Payments exists, "Sync with License" is added by the Recurring Integration. See notes for this method for more information.
		if ( class_exists( 'EDD_Recurring' ) ) {
			return $all_access_length_options;
		}

		$all_access_pass = edd_all_access_get_pass( $payment->ID, $download_id, $price_id );

		// Check if this AAP has a license attached to it.
		$possibly_attached_license = edd_all_access_software_licensing_get_license_from_aa_pass( $all_access_pass );

		// If there is a license attached, add it as an option for this customer's AA pass.
		if ( $possibly_attached_license ) {
			$all_access_length_options['edd_software_licensing'] = __( 'Sync with License expiration', 'edd-all-access' );
		}

		return $all_access_length_options;
	}


	/**
	 * Software Licensing does not allow bundle activations to happen. In the case where a bundle-license is also an All Access product
	 *
	 * @since    1.0.2
	 * @param    bool           $allow_bundle_activation Whether to allow bundles to be activated with a license.
	 * @param    EDD_SL_License $license The license in question.
	 * @return   bool           $allow_bundle_activation
	 */
	public function allow_bundle_activations( $allow_bundle_activation, $license ) {

		// Make sure we have an instance of the EDD_SL_License class stored in the $license variable. Sometimes it may not be one and we may be getting the license as a string instead.
		if ( ! ( $license instanceof EDD_SL_License ) ) {
			$license = edd_software_licensing()->get_license( $license, true );
		}

		// Get all of the ALl Access products in this store.
		$all_access_products = edd_all_access_get_all_access_downloads();

		// Loop through each All Access product.
		foreach ( $all_access_products as $all_access_product_id ) {

			// If the bundled license is an All Access product.
			if ( $license->download->id === $all_access_product_id ) {

				// Allow this bundle license to be activated.
				return true;
			}
		}

		return $allow_bundle_activation;

	}

	/**
	 * When Software Licensing is checking if the passed-in title matches the title attached to the passed-in license in our Store,
	 * Check if the license's product-title is an All Access product which includes the product trying to be accessed/downloaded. If it does, tell
	 * Software Licensing the titles match up. In this way, we can "trick" Software Licensing into accepting a license other than one for the actual product.
	 * In this case, it allows for a "master" license key to be used for products it isn't actually for (outside of the All Access extension).
	 *
	 * @since    1.0.0
	 * @param    bool           $item_name_matches Whether the name of the item passed matches the name of the item on the license.
	 * @param    int            $download_id The ID of the download in question.
	 * @param    string         $item_name The name of the download in question.
	 * @param    EDD_SL_License $license The license in question.
	 * @return   bool   $item_name_matches
	 */
	public function edd_all_access_sl_name_matches( $item_name_matches, $download_id, $item_name, $license ) {

		// If no license was passed, they might be running an older version of Software Licensing.
		if ( ! isset( $license ) || empty( $license ) ) {
			return $item_name_matches;
		}

		// Decode the item name since it came from a URL.
		$item_name_decode = urldecode( $item_name );

		// Get a WP Post object using that decoded title.
		$post_with_title_passed_in = $this->get_download_by_title( $item_name_decode );

		// If we didn't find a post using the decoded title, double check it for special characters not covered by urldecode.
		if ( ! $post_with_title_passed_in ) {

			// Do a double check using rawurldecode in case the plugn author used a + sign in the Download's title and used rawurlencode to send the title to us here.
			$item_name_raw_decode = rawurldecode( $item_name );

			$post_with_title_passed_in = $this->get_download_by_title( $item_name_raw_decode );

			// If no WP post objects were found using the passed-in item name, return the incoming value which we got from the filter.
			if ( ! $post_with_title_passed_in ) {
				return $item_name_matches;
			}
		}

		// Make sure we have an instance of the EDD_SL_License class stored in the $license variable. Sometimes it may not be one and we may be getting the license as a string instead.
		if ( ! ( $license instanceof EDD_SL_License ) ) {
			$license = edd_software_licensing()->get_license( $license, true );
		}

		$all_access_check = edd_all_access_check(
			array(
				'download_id'            => $post_with_title_passed_in->ID,
				'price_id'               => null,
				'customer_id'            => $license->customer_id,
				'require_login'          => false,
				'aa_download_must_match' => $license->download_id,
			)
		);

		// If the customer attached to the license has an All Access pass which includes the desired product.
		if ( isset( $all_access_check['success'] ) && $all_access_check['success'] ) {
			return true;
		}

		// Otherwise, return the unchanged value that we got from the filter hook.
		return $item_name_matches;

	}

	/**
	 * When Software Licensing is checking if the passed-in ID matches the ID attached to the passed-in license in our Store,
	 * Check if the license's ID is an All Access product which includes the product trying to be accessed/downloaded. If it does, tell
	 * Software Licensing the IDs match up. In this way, we can "trick" Software Licensing into accepting a license other than one for the actual product.
	 * In this case, it allows for a "master" license key to be used for products it isn't actually for (outside of the All Access extension).
	 *
	 * @since    1.0.0
	 * @param    bool   $license_match Whether the license matches the product it was submitted for.
	 * @param    int    $download_id   The download ID passed.
	 * @param    int    $license_download The license download.
	 * @param    string $license_key The license key passed.
	 * @return   bool   $license_match
	 */
	public function edd_all_access_sl_id_matches( $license_match, $download_id, $license_download, $license_key ) {

		// Check if the license is an All Access license which includes the desired product.

		// Get the customer attached to the license.
		$license = edd_software_licensing()->get_license( $license_key, true );

		// If an invalid license was passed to edd_sl_id_license_match, return false to give no access.
		if ( false === $license ) {
			return false;
		}

		$all_access_check = edd_all_access_check(
			array(
				'download_id'            => $download_id,
				'price_id'               => null,
				'customer_id'            => $license->customer_id,
				'require_login'          => false,
				'aa_download_must_match' => $license->download_id,
			)
		);

		// If the customer attached to the license has an All Access pass which includes the desired product.
		if ( isset( $all_access_check['success'] ) && $all_access_check['success'] ) {
			return true;
		}

		// Otherwise, return the unchanged value that we got from the filter hook.
		return $license_match;
	}

	/**
	 * Tell Software Licensing if we should check for new version updates using the passed-in license or the passed-in name.
	 * Because the All Access license isn't the product we are hoping to check for updates, we want to force Software Licensing to check using the name.
	 *
	 * @since    1.0.0
	 * @param    bool $check_by_name_first Whether we should fetch update data using the passed-in name or using the passed-in license.
	 * @return   bool $check_by_name_first We want to fetch update data using the passed-in name.
	 */
	public function edd_all_access_sl_force_check_by_name( $check_by_name_first ) {
		return true;
	}

	/**
	 * If a license is attached to an All Access Pass, use the start time of the license instead of the start time of the All Access Pass
	 *
	 * @since    1.0.1
	 * @param    int                 $all_access_start_time The start timestamp of the all access pass.
	 * @param    EDD_Payment         $payment The EDD Payment where the AA pass originated.
	 * @param    int                 $download_id The Download ID where the AA pass originated.
	 * @param    int                 $price_id The variable price ID where the AA pass originated.
	 * @param    EDD_All_Access_Pass $all_access_pass The AA pass in question.
	 * @return   int
	 */
	public function edd_all_access_sync_start_time_to_license_start_time( $all_access_start_time, $payment, $download_id, $price_id, $all_access_pass ) {

		// If this pass is not set to sync with recurring/software-licenseing, don't do anything here.
		if ( 'edd_recurring' !== $all_access_pass->duration_unit && 'edd_software_licensing' !== $all_access_pass->duration_unit ) {
			return $all_access_start_time;
		}

		// Check if this All Access Pass has a license attached to it.
		$possibly_attached_license = edd_all_access_software_licensing_get_license_from_aa_pass( $all_access_pass );

		// If there is a license attached, we use that over the subscription.
		if ( ! $possibly_attached_license ) {
			return $all_access_start_time;
		}

		if ( ! $possibly_attached_license->payment_id ) {
			return $all_access_start_time;
		}

		$license_start_payment_date = false;
		if ( function_exists( 'edd_get_order' ) ) {
			$order = edd_get_order( $possibly_attached_license->payment_id );
			if ( $order instanceof EDD_Order ) {
				$license_start_payment_date = $order->date_created;
			}
		} else {
			$license_start_payment_date = get_the_time( get_option( 'date_format' ), $possibly_attached_license->payment_id );
		}

		if ( ! $license_start_payment_date ) {
			return $all_access_start_time;
		}

		return strtotime( $license_start_payment_date );

	}

	/**
	 * If a license is attached to an All Access Pass, use the expiration time of the license instead of the expiration time of the All Access Pass
	 *
	 * @since    1.1.0
	 * @param    int    $expiration_time - The timestamp this All Access Pass should expire.
	 * @param    object $all_access_pass - The All Access Pass object.
	 * @return   int    $expiration_time - The timestamp this All Access Pass should expire.
	 */
	public function edd_all_access_sync_expiration_time_with_sl( $expiration_time, $all_access_pass ) {

		// If this pass is not set to sync with recurring/software-licenseing, don't do anything here.
		if ( 'edd_recurring' !== $all_access_pass->duration_unit && 'edd_software_licensing' !== $all_access_pass->duration_unit ) {
			return $expiration_time;
		}

		// Check if this AA product is licensed.
		$possibly_attached_license = edd_all_access_software_licensing_get_license_from_aa_pass( $all_access_pass );

		// If there's no a license for this AA Pass.
		if ( ! $possibly_attached_license ) {
			return $expiration_time;
		}

		// If this is a lifetime license, set the AA expiration to be "never" (for "never expires").
		if ( 'lifetime' === $possibly_attached_license->expiration ) {
			return 'never';
		}

		return $possibly_attached_license->expiration;

	}

	/**
	 * Check if an AA pass would expire based on the duration unit being "edd_software_licensing", meaning its set to "sync with license expiration".
	 *
	 * @since    1.1.0
	 * @param    string              $would_be_end_time The would-be end time.
	 * @param    bool                $would_be_duration_number The duration number that would be used in our would-be scenario.
	 * @param    string              $would_be_duration_unit The duration unit (day/week/month/edd_software_licensing) that would be used in our would-be scenario.
	 * @param    EDD_All_Access_Pass $all_access_pass The All Access Pass in question.
	 * @return   string              $would_be_end_time The would-be end time adjusted for Software Licensing's license sync
	 */
	public function check_if_pass_would_expire( $would_be_end_time, $would_be_duration_number, $would_be_duration_unit, $all_access_pass ) {

		// If the would-be scenario is not set to sync with the license, we don't need to do anything here.
		if ( 'edd_recurring' !== $would_be_duration_unit && 'edd_software_licensing' !== $would_be_duration_unit ) {
			return $would_be_end_time;
		}

		// Check if this AA product is licensed.
		$possibly_attached_license = edd_all_access_software_licensing_get_license_from_aa_pass( $all_access_pass );

		// If there's no a license for this AA Pass.
		if ( ! $possibly_attached_license ) {
			return $would_be_end_time;
		}

		// If this is a lifetime license, it won't expire.
		if ( 'lifetime' === $possibly_attached_license->expiration ) {
			return 'never';
		}

		return $possibly_attached_license->expiration;

	}

	/**
	 * Handle SL upgrades pt 1. Because edd_sl_license_upgraded fires before All Access passes get created, and we need the data from that hook,
	 * to solve the race condition we have to store the data from the hook somewhere until after the All Access Pass is created.
	 * We'll store the data as a global variable, and then use-it/clear-it in edd_all_access_check_updated_payment
	 * which is where the All Access Pass gets created.
	 *
	 * @since    1.0.0
	 * @param    string $license_id A timestamp of the time of upgrade payment.
	 * @param    array  $args An array of settings relating to the upgrade.
	 * @return   void
	 */
	public function edd_all_access_store_sl_upgrade( $license_id, $args ) {

		global $edd_aa_sl_upgrade_data;

		$default_args = array(
			'payment_id'       => 0,
			'old_payment_id'   => 0,
			'download_id'      => 0,
			'old_download_id'  => 0,
			'old_price_id'     => false,
			'upgrade_id'       => 0,
			'upgrade_price_id' => false,
		);

		$args = wp_parse_args( $args, $default_args );

		$edd_aa_sl_upgrade_data = $args;

	}

	/**
	 * Handle SL upgrades pt 2. This handles things like bronze -> silver -> gold upgrades
	 * This fires directly after an all access activation takes place (possibly non-licensed, as Sl enables non licensed upgrades).
	 * We use it here to update the All Access Pass customer meta
	 * of both the old payment and the new payment.
	 * The All Access Pass attached to the old_payment will get a flag letting it know it is an old/prior All Access Pass.
	 * The All Access Pass attached to the new_payment will get an array containing all preliminary payments by taking them from the old All Access Pass.
	 *
	 * @since    1.0.0
	 * @param    int $payment_id The payment id in question.
	 * @param    int $download_id The download id in question.
	 * @param    int $price_id The price ID in question.
	 * @return   void
	 */
	public function edd_all_access_handle_sl_upgrade( $payment_id, $download_id, $price_id ) {

		global $edd_aa_sl_upgrade_data;

		// If no upgrade data was saved to the global in the edd_all_access_store_sl_upgrade method (which is hooked to edd_sl_license_upgraded).
		if ( empty( $edd_aa_sl_upgrade_data ) ) {

			// There's no upgrade that just took place. Do nothing here.
			return;
		}

		$args = $edd_aa_sl_upgrade_data;

		$old_all_access_pass = edd_all_access_get_pass( $args['old_payment_id'], $args['old_download_id'], $args['old_price_id'] );
		$new_all_access_pass = edd_all_access_get_pass( $args['payment_id'], $args['download_id'], $args['upgrade_price_id'] );

		// If the upgrading-from product or the upgrading-to product is not an active All Access pass, do nothing. Only AA upgrades are handled here.
		if ( 'active' !== $old_all_access_pass->status || 'active' !== $new_all_access_pass->status ) {
			return false;
		}

		// Upgrade the old All Access Pass to the new All Access Pass.
		$old_all_access_pass->do_upgrade( $new_all_access_pass );

		// Clear the global variable.
		$edd_aa_sl_upgrade_data = null;
	}

	/**
	 * If an All Access Pass is being renewed and it is licensed, make sure the license is "renewed" in Software Licensing - as opposed to creating a new license.
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function edd_all_access_sync_renewals() {

		// If license renewals are not allowed, skip this entirely.
		if ( ! edd_sl_renewals_allowed() ) {
			return;
		}

		// Check everything in the cart to see if any are Licensed All Access Renewals.
		$cart_contents = edd_get_cart_contents();

		if ( empty( $cart_contents ) ) {
			return;
		}

		$current_user_id = get_current_user_id();
		$customer        = new EDD_Customer( $current_user_id, true );

		// If this is not an existing customer, this is definitely not a renewal, so we can leave here now.
		if ( 0 === intval( $customer->id ) ) {
			return;
		}

		// Get this customer's All Access Passes (if any).
		$customers_all_access_passes = edd_all_access_get_customer_passes( $customer );

		if ( empty( $customers_all_access_passes ) ) {
			return;
		}

		foreach ( $cart_contents as $cart_key => $cart_item ) {

			$download_id = $cart_item['id'];
			$price_id    = isset( $cart_item['options']['price_id'] ) ? (int) $cart_item['options']['price_id'] : 0;

			// If this product is licensed.
			$license = new EDD_SL_Download( $download_id );

			// Skip over products without licensing enabled.
			if ( ! $license->licensing_enabled() ) {
				continue;
			}

			// If the customer has purchased this All Access Pass Before, this is a renewal.
			if ( array_key_exists( $download_id . '_' . $price_id, $customers_all_access_passes ) ) {

				$previous_payment_id = $customers_all_access_passes[ $download_id . '_' . $price_id ]['payment_id'];

				// Get the previous license key.
				$license = edd_software_licensing()->get_license_by_purchase( $previous_payment_id, $download_id );

				// If no license was found, skip this cart item.
				if ( empty( $license ) ) {
					continue;
				}

				// If the item in the cart is already set up as a Software Licensing renewal, we don't need to do anything here.
				if ( ! empty( $cart_item['options']['is_renewal'] ) ) {
					continue;
				}

				$added = edd_sl_add_renewal_to_cart( sanitize_text_field( $license->key ), true );

			}
		}
	}

	/**
	 * Checks the license status of an otherwise valid pass.
	 *
	 * @since 1.2.5
	 * @param EDD_All_Access_Pass $all_access_pass
	 * @return void
	 */
	public function check_license_status_for_valid_pass( $all_access_pass ) {
		$license_status = $this->checked_pass_license_status( $all_access_pass );

		// If we have a 'disabled' status, throw an exception.
		if ( 'disabled' === $license_status ) {
			throw new EDD\AllAccess\Exceptions\AccessException(
				'all_access_pass_license_disabled',
				__( 'Your All Access Pass license is disabled.', 'edd-all-access' ),
				403,
				$all_access_pass
			);
		}
	}

	/**
	 * Checks the license status of an All Access Pass to see if it is valid or not.
	 *
	 * @param string $status
	 * @param EDD_All_Access_Pass $all_access_pass
	 * @return string
	 */
	public function check_license_status_for_pass_status( $status, $all_access_pass ) {
		if ( 'active' !== $status ) {
			return $status;
		}

		$license_status = $this->checked_pass_license_status( $all_access_pass );

		if ( 'disabled' === $license_status ) {
			edd_debug_log( 'AA: Pass is invalid because the attached license has been disabled.' );
			return 'disabled';
		}

		return $status;
	}

	/**
	 * Get a download post object by its title.
	 *
	 * @since 1.2.5
	 * @param string $title
	 * @return WP_Post|false
	 */
	private function get_download_by_title( $title ) {
		$download = new WP_Query(
			array(
				'post_type'              => 'download',
				'title'                  => $title,
				'post_status'            => 'all',
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'ignore_sticky_posts'    => true,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
				'orderby'                => 'post_date ID',
				'order'                  => 'ASC',
			)
		);

		return ! empty( $download->post ) ? $download->post : false;
	}

	/**
	 * Checks the license status of an All Access Pass to see if it is valid or not.
	 *
	 * @since 1.2.5
	 *
	 * @param EDD_All_Access_Pass $all_access_pass
	 * @return string|bool
	 */
	private function checked_pass_license_status( $all_access_pass ) {
		global $eddaa_pass_license_statuses;

		if ( is_array( $eddaa_pass_license_statuses ) && array_key_exists( $all_access_pass->id, $eddaa_pass_license_statuses ) ) {
			return $eddaa_pass_license_statuses[ $all_access_pass->id ];
		}

		// This might be our first run, so if this value is null, set it to an array.
		if ( is_null( $eddaa_pass_license_statuses ) ) {
			$eddaa_pass_license_statuses = array();
		}

		$possibly_attached_license = edd_all_access_software_licensing_get_license_from_aa_pass( $all_access_pass );

		if ( ! $possibly_attached_license ) {
			// No license was found, so set this pass's license status to 'false'.
			$license_status = false;
		} else {
			// Define the status from the license.
			$license_status = $possibly_attached_license->status;
		}

		// Now assign it to the global.
		$eddaa_pass_license_statuses[ $all_access_pass->id ] = $license_status;

		return $license_status;
	}
}

/**
 * Software Licensing does not allow bundle activations to happen. In the case where a bundle-license is also an All Access product
 *
 * @since    1.1
 * @param    EDD_All_Access_Pass $all_access_pass The All Access pass in question.
 * @return   mixed                Returns false of no license found. An EDD_License object if one is found.
 */
function edd_all_access_software_licensing_get_license_from_aa_pass( $all_access_pass ) {

	if ( ! defined( 'EDD_SL_VERSION' ) ) {
		return false;
	}

	global $eddaa_existing_licenses;

	if ( is_null( $eddaa_existing_licenses ) ) {
		$eddaa_existing_licenses = edd_software_licensing()->get_licenses_of_purchase( $all_access_pass->payment_id );
	}

	if ( $eddaa_existing_licenses ) {

		// Loop through each license in this purchase to see if it matches the All Access product.
		foreach ( $eddaa_existing_licenses as $existing_license ) {

			if ( absint( $existing_license->download_id ) !== absint( $all_access_pass->download_id ) ) {

				continue;
			}

			$license_price_id = empty( $existing_license->price_id ) ? 0 : $existing_license->price_id;

			if ( absint( $license_price_id ) !== absint( $all_access_pass->price_id ) ) {

				continue;
			}

			return $existing_license;
		}
	}

	return false;
}
