<?php
/**
 * Upgrade functions for All Access.
 *
 * @package     EDD\EDDAllAccess\Functions
 * @since       1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add the "page" which WordPress will use to handle the upgrades routines.
 *
 * @since       1.0.0
 * @return      void
 */
function edd_all_access_register_upgrades_page() {

	if ( ! function_exists( 'EDD' ) ) {
		return;
	}

	add_submenu_page( null, __( 'EDD All Access Upgrades', 'edd-all-access' ), __( 'EDD Upgrades', 'edd-all-access' ), 'manage_shop_settings', 'edd-aa-upgrades', 'edd_all_access_upgrades_screen' );
}
add_action( 'admin_menu', 'edd_all_access_register_upgrades_page', 10 );

/**
 * This function controls what is shown on the upgrades page for All Access
 *
 * @since       1.0.0
 * @return      void
 */
function edd_all_access_upgrades_screen() {

	// If the nonce was not yet passed, we haven't done anything yet, so set the step to 1.
	if ( ! isset( $_GET['edd_aa_upgrade_nonce'] ) ) {
		$step        = 1;
		$total_steps = __( 'unknown', 'edd-all-access' );
	} else {

		// If the nonce was passed in the URL, check if it passes.
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['edd_aa_upgrade_nonce'] ) ), 'edd_aa_upgrade_nonce' ) ) {
			echo esc_html( 'Nonce failure', 'edd-all-access' );
			exit;
		} else {
			// Nonce was verified. Use the URL values.
			$step        = isset( $_GET['step'] ) ? absint( $_GET['step'] ) : 1;
			$total_steps = isset( $_GET['total_steps'] ) ? absint( $_GET['total_steps'] ) : __( 'unknown', 'edd-all-access' );
		}
	}

	?>
	<div class="wrap">
		<h2><?php esc_html_e( 'All Access - Upgrades', 'edd-all-access' ); ?></h2>
		<div id="edd-upgrade-status">
			<p><?php esc_html_e( 'The upgrade process is running, please be patient.', 'edd-all-access' ); ?></p>
			<?php // Translators: 1: The step number being executed in the upgrade. 2: The total steps in the upgrade. ?>
			<p><strong><?php echo esc_html( sprintf( __( 'Step %1$d of approximately %2$s running', 'edd-all-access' ), $step, $total_steps ) ); ?>
		</div>
		<script type="text/javascript">
			document.location.href = "index.php?edd_action=<?php echo isset( $_GET['edd_upgrade'] ) ? esc_html( sanitize_text_field( wp_unslash( $_GET['edd_upgrade'] ) ) ) : ''; ?>&step=<?php echo absint( $_GET['step'] ); ?>&edd_aa_upgrade_nonce=<?php echo esc_html( wp_create_nonce( 'edd_aa_upgrade_nonce' ) ); ?> ";
		</script>
	</div>
	<?php
}

/**
 * Triggers all upgrade functions
 *
 * @since       1.0.0
 * @return      void
 */
function edd_all_access_show_upgrade_notice() {

	if ( ! function_exists( 'EDD' ) ) {
		return;
	}

	// Only show the admin notices to users with the manage_shop_settings role.
	if ( ! current_user_can( 'manage_shop_settings' ) ) {
		return;
	}

	$edd_all_access_version = get_option( 'edd_all_access_version' );
	$edd_all_access_version = empty( $edd_all_access_version ) ? EDD_ALL_ACCESS_VER : $edd_all_access_version;

	if ( function_exists( 'edd_has_upgrade_completed' ) && function_exists( 'edd_maybe_resume_upgrade' ) ) {
		$resume_upgrade = edd_maybe_resume_upgrade();
		if ( empty( $resume_upgrade ) ) {

			// If the customer meta data re-organization upgrades for v1 have not yet been completed, show a button in the admin to do them now.
			if ( ! isset( $_GET['edd_upgrade'] ) && version_compare( $edd_all_access_version, '1', '>=' ) && ! edd_has_upgrade_completed( 'aa_v1_reorganize_customer_meta' ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended

				echo wp_kses_post(
					sprintf(
						// Translators: The URL for where the data can be upgraded.
						'<div class="updated"><p>' . __( 'The customer data needs to be upgraded for All Access, click <a href="%s">here</a> to start the upgrade.', 'edd-all-access' ) . '</p></div>',
						esc_url( add_query_arg( array( 'edd_action' => 'aa_v1_reorganize_customer_meta' ), admin_url() ) )
					)
				);
			}

			// If the the UTC timezone upgrade routine has not yet been completed, show a button in the admin to do that now.
			if ( ! isset( $_GET['edd_upgrade'] ) && ! edd_has_upgrade_completed( 'aa_fix_utc_timezones' ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				echo wp_kses_post(
					sprintf(
						// Translators: The URL for where the data can be upgraded.
						'<div class="updated"><p>' . __( 'The All Access data needs to be upgraded. Click <a href="%s">here</a> to start the upgrade.', 'edd-all-access' ) . '</p></div>',
						esc_url( add_query_arg( array( 'edd_action' => 'aa_fix_utc_timezones' ), admin_url() ) )
					)
				);
			}
		}
	}
}
add_action( 'admin_notices', 'edd_all_access_show_upgrade_notice' );

/**
 * Upgrade function which fixes pre-initial release data (bet testers), where the AAP key was only the download_id.
 * Now it uses the download_id + the price_id, even if that id is 0. This allows variably priced AAP products.
 *
 * @since 1.0
 * @return void
 */
function edd_all_access_v1_upgrades_callback() {

	// Only execute if the user has the manage_shop_settings role.
	if ( ! current_user_can( 'manage_shop_settings' ) ) {
		return;
	}

	if (
		! isset( $_GET['edd_aa_upgrade_nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['edd_aa_upgrade_nonce'] ) ), 'edd_aa_upgrade_nonce' )
	) {
		$redirect = add_query_arg(
			array(
				'page'                 => 'edd-aa-upgrades',
				'edd_upgrade'          => 'aa_v1_reorganize_customer_meta',
				'step'                 => 1,
				'total_steps'          => '',
				'edd_aa_upgrade_nonce' => wp_create_nonce( 'edd_aa_upgrade_nonce' ),
			),
			admin_url( 'index.php' )
		);
		wp_safe_redirect( $redirect );
		exit;
	}

	ignore_user_abort( true );

	if ( ! edd_is_func_disabled( 'set_time_limit' ) ) {
		set_time_limit( 0 );
	}

	$step              = isset( $_GET['step'] ) ? absint( $_GET['step'] ) : 1;
	$payments_per_page = 50;

	$args = array(
		'number'   => $payments_per_page,
		'page'     => $step,
		'status'   => 'any',
		'order'    => 'ASC',
		'download' => edd_all_access_get_all_access_downloads(),
	);

	$payments = new EDD_Payments_Query( $args );
	$payments = $payments->get_payments();

	if ( $payments ) {

		foreach ( $payments as $payment ) {

			/**
			 * Prior to initial release, customer all access data was stored using the [download_id][price_id] = array( all aa data here).
			 * Now, the download and price id are combined.
			 * In order to prevent breaking beta tester sites, we will manually re-organize the data here for all customer passes.
			 */
			$customer = new EDD_Customer( $payment->customer_id );

			// Get the All Access passes saved to this customer meta.
			$customer_all_access_passes = edd_all_access_get_customer_passes( $customer );

			// If this customer has no all access data, we don't need to do anything for them so skip this customer.
			if ( empty( $customer_all_access_passes ) ) {
				continue;
			}

			// Loop through each All Access Pass saved to the customer meta.
			foreach ( $customer_all_access_passes as $aa_pass_key => $aa_datas ) {

				// If one of the all access passes does not have a download and price id saved directly them them, they are old data and need to be updated.
				if ( ! isset( $aa_datas['download_id'] ) || ! isset( $aa_datas['price_id'] ) ) {

					foreach ( $aa_datas as $price_id => $aa_data ) {

							// First lets store the data in the new key.
							$customer_all_access_passes[ $aa_pass_key . '_' . $price_id ] = $aa_data;

							// Then lets add the download id.
							$customer_all_access_passes[ $aa_pass_key . '_' . $price_id ]['download_id'] = $aa_pass_key;

							// And the price id.
							$customer_all_access_passes[ $aa_pass_key . '_' . $price_id ]['price_id'] = $price_id;

							// Then we'll strip out the old data.
							unset( $customer_all_access_passes[ $aa_pass_key ] );
					}
				}
			}

			$customer->update_meta( 'all_access_passes', $customer_all_access_passes );

		}

		// Customers with All Access data found so upgrade them.
		$step++;
		$redirect = add_query_arg(
			array(
				'page'                 => 'edd-aa-upgrades',
				'edd_upgrade'          => 'aa_v1_reorganize_customer_meta',
				'step'                 => $step,
				'total_steps'          => count( $payments ), // Note that this total is innacurate because we can't currently do $payments->found_posts/$payments_per_page.
				'edd_aa_upgrade_nonce' => wp_create_nonce( 'edd_aa_upgrade_nonce' ),
			),
			admin_url( 'index.php' )
		);
		wp_safe_redirect( $redirect );
		exit;

	} else {

		// No more customers found, update the DB version and finish up.
		edd_set_upgrade_complete( 'aa_v1_reorganize_customer_meta' );
		wp_safe_redirect( admin_url() );
		exit;
	}

}
add_action( 'edd_aa_v1_reorganize_customer_meta', 'edd_all_access_v1_upgrades_callback' );

/**
 * Upgrade function which resets all timestamps saved in relation to All Access. Originally, timestamps were converted the the timezone
 * of the WordPress store before being saved. This is not a good idea. Timestamps should always be saved in UTC. The only place they should
 * be converted to the WordPress timezone is upon display. This way, the timezone of the WP can be changed as often as needed, without the times
 * being thrown off. For more see https://github.com/easydigitaldownloads/edd-all-access/issues/210
 *
 * @since 1.0
 * @return void
 */
function edd_aa_fix_utc_timezones_callback() {

	// Only execute if the user has the manage_shop_settings role.
	if ( ! current_user_can( 'manage_shop_settings' ) ) {
		return;
	}

	if (
		! isset( $_GET['edd_aa_upgrade_nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['edd_aa_upgrade_nonce'] ) ), 'edd_aa_upgrade_nonce' )
	) {
		$redirect = add_query_arg(
			array(
				'page'                 => 'edd-aa-upgrades',
				'edd_upgrade'          => 'aa_fix_utc_timezones',
				'step'                 => 1,
				'total_steps'          => '',
				'edd_aa_upgrade_nonce' => wp_create_nonce( 'edd_aa_upgrade_nonce' ),
			),
			admin_url( 'index.php' )
		);
		wp_safe_redirect( $redirect );
		exit;
	}

	ignore_user_abort( true );

	if ( ! edd_is_func_disabled( 'set_time_limit' ) ) {
		set_time_limit( 0 );
	}

	// Get the timezone set for the WordPress.
	$wp_timezone  = new DateTimeZone( edd_get_timezone_id() );
	$utc_timezone = new DateTimeZone( 'UTC' );

	$step              = isset( $_GET['step'] ) ? absint( $_GET['step'] ) : 1;
	$payments_per_page = 50;

	$args = array(
		'number'   => $payments_per_page,
		'page'     => $step,
		'status'   => 'any',
		'order'    => 'ASC',
		'download' => edd_all_access_get_all_access_downloads(),
	);

	$payments = new EDD_Payments_Query( $args );
	$payments = $payments->get_payments();

	if ( $payments ) {

		foreach ( $payments as $payment ) {

			// Get the customer attached to this purchase of an All Access pass.
			$customer = new EDD_Customer( $payment->customer_id );

			// Get the All Access passes saved to this customer meta.
			$customer_all_access_passes = edd_all_access_get_customer_passes( $customer );

			// If this customer has no all access data, we don't need to do anything for them so skip this customer.
			if ( empty( $customer_all_access_passes ) ) {
				continue;
			}

			// Loop through each All Access Pass saved to the customer meta.
			foreach ( $customer_all_access_passes as $aa_pass_key => $aa_data ) {

				// If these times are already in UTC, no changes need to be made here.
				if ( isset( $customer_all_access_passes[ $aa_pass_key ]['utc'] ) && $customer_all_access_passes[ $aa_pass_key ]['utc'] ) {
					continue;
				}

				$utc_updated = false;

				// Modify the download_used_last_reset timestamp to be in UTC.
				if ( isset( $aa_data['downloads_used_last_reset'] ) ) {

					// We need to know the offset at the time of this timestamp, because of daylight savings time. We can't simply use today's offset.
					// Set up date objects for the WordPress timezone and the UTC timezone, and then find the difference between them in seconds.
					$wp_dt = new DateTime( '@' . $aa_data['downloads_used_last_reset'] );
					$wp_dt->setTimezone( $wp_timezone );

					$utc_dt = new DateTime( '@' . $aa_data['downloads_used_last_reset'] );
					$wp_dt->setTimezone( $utc_timezone );

					$offset = $wp_timezone->getOffset( $wp_dt ) - $utc_timezone->getOffset( $utc_dt );

					$aa_data['downloads_used_last_reset'] = $aa_data['downloads_used_last_reset'] - $offset;
					$utc_updated                          = true;
				}

				// Modify the time_of_activation_meta all_access_start_time timestamp to be in UTC.
				if ( isset( $aa_data['time_of_activation_meta'] ) ) {
					if ( isset( $aa_data['time_of_activation_meta']['all_access_start_time'] ) ) {

						// We need to know the offset at the time of this timestamp, because of daylight savings time. We can't simply use today's offset.
						// Set up date objects for the WordPress timezone and the UTC timezone, and then find the difference between them in seconds.
						$wp_dt = new DateTime( '@' . $aa_data['time_of_activation_meta']['all_access_start_time'] );
						$wp_dt->setTimezone( $wp_timezone );

						$utc_dt = new DateTime( '@' . $aa_data['time_of_activation_meta']['all_access_start_time'] );
						$wp_dt->setTimezone( $utc_timezone );

						$offset = $wp_timezone->getOffset( $wp_dt ) - $utc_timezone->getOffset( $utc_dt );

						$aa_data['time_of_activation_meta']['all_access_start_time'] = $aa_data['time_of_activation_meta']['all_access_start_time'] - $offset;
						$utc_updated = true;
					}
				}

				// Modify the customer_specific_meta all_access_start_time timestamp to be in UTC.
				if ( isset( $aa_data['customer_specific_meta'] ) ) {
					if ( isset( $aa_data['customer_specific_meta']['all_access_start_time'] ) ) {

						// We need to know the offset at the time of this timestamp, because of daylight savings time. We can't simply use today's offset.
						// Set up date objects for the WordPress timezone and the UTC timezone, and then find the difference between them in seconds.
						$wp_dt = new DateTime( '@' . $aa_data['customer_specific_meta']['all_access_start_time'] );
						$wp_dt->setTimezone( $wp_timezone );

						$utc_dt = new DateTime( '@' . $aa_data['customer_specific_meta']['all_access_start_time'] );
						$wp_dt->setTimezone( $utc_timezone );

						$offset = $wp_timezone->getOffset( $wp_dt ) - $utc_timezone->getOffset( $utc_dt );

						$aa_data['customer_specific_meta']['all_access_start_time'] = $aa_data['customer_specific_meta']['all_access_start_time'] - $offset;
						$utc_updated = true;
					}
				}

				// Add a flag so that this data will never be accidentally re-run by this upgrade again.
				if ( is_array( $aa_data ) && $utc_updated ) {
					$aa_data['utc'] = true;
				}

				// Resave the AA data to the array.
				$customer_all_access_passes[ $aa_pass_key ] = $aa_data;

			}

			// Save the AA meta for this customer.
			$customer->update_meta( 'all_access_passes', $customer_all_access_passes );

		}

		// Customers with All Access data found so upgrade them.
		$step++;
		$redirect = add_query_arg(
			array(
				'page'                 => 'edd-aa-upgrades',
				'edd_upgrade'          => 'aa_fix_utc_timezones',
				'step'                 => $step,
				'total_steps'          => count( $payments ), // Note that this total is innacurate because we can't currently do $payments->found_posts/$payments_per_page.
				'edd_aa_upgrade_nonce' => wp_create_nonce( 'edd_aa_upgrade_nonce' ),
			),
			admin_url( 'index.php' )
		);
		wp_safe_redirect( $redirect );
		exit;

	} else {

		// No more customers found, update the DB version and finish up.
		edd_set_upgrade_complete( 'aa_fix_utc_timezones' );
		wp_safe_redirect( admin_url() );
		exit;
	}

}
add_action( 'edd_aa_fix_utc_timezones', 'edd_aa_fix_utc_timezones_callback' );
