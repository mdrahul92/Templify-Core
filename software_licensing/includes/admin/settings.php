<?php

function edd_sl_register_license_section( $sections ) {
	$sections['software-licensing'] = __( 'Software Licensing', 'templify_sl' );

	return $sections;
}
add_filter( 'edd_settings_sections_extensions', 'edd_sl_register_license_section', 10, 1 );
add_filter( 'edd_settings_sections_emails', 'edd_sl_register_license_section' );

/**
 * Registers the Software Licensing license options under the extensions tab.
 * *
 * @access      private
 * @since       1.0
 * @param       $settings array the existing plugin settings
 * @return      array
*/

function edd_sl_license_settings( $settings ) {

	//Set up some of the tooltips differently if EDD Recurring is active.
	if ( class_exists( 'EDD_Recurring' ) ) {
		$edd_sl_renewals_tt_desc = __( 'Checking this will give customers the ability to enter their license key on the checkout page and renew it. They\'ll also get renewal reminders to their email, and can also renew from their account page (if that page uses the [edd_license_keys] shortcode). NOTE: If the product is a Recurring product and the customer\'s subscription is still active, it will automatically renew even if this option is disabled.', 'templify_sl' );

		$edd_sl_renewal_discount_tt_desc = __( 'When the user is on the checkout page renewing their license, this discount will be automatically applied to their renewal purchase. NOTE: If the product is a Recurring product and the customer\'s subscription is still active, it will automatically renew with this discount applied.', 'templify_sl' );
	} else {
		$edd_sl_renewals_tt_desc = __( 'Checking this will give customers the ability to enter their license key on the checkout page and renew it. They\'ll also get renewal reminders to their email, and can also renew from their account page (if that page uses the [edd_license_keys] shortcode).', 'templify_sl' );

		$edd_sl_renewal_discount_tt_desc = __( 'When the user is on the checkout page renewing their license, this discount will be automatically applied to their renewal purchase.', 'templify_sl' );
	}

	$license_settings = array(
		array(
			'id'            => 'edd_sl_force_increase',
			'name'          => __( 'Disable Unique Activations', 'templify_sl' ),
			'desc'          => __( 'Check this if you do not require a unique identifier when activating a license key.', 'templify_sl' ),
			'type'          => 'checkbox',
			'tooltip_title' => __( 'What are Unique Activations?', 'templify_sl' ),
			'tooltip_desc'  => __( 'Software Licensing will typically require the software to pass a URL along with a license to check the license limit. Note that if you sell desktop software, you could use the URL parameter to track the ID of the computer running the license by passing the computer\'s ID in the URL parameter. For more on this, please see the documentation. Checking this will always increase or decrease the activation count when an API request to activate or deactivate the license has been made.', 'templify_sl' ),
		),
		array(
			'id'            => 'edd_sl_bypass_local_hosts',
			'name'          => __( 'Ignore Local Host URLs?', 'templify_sl' ),
			'desc'          => __( 'Allow local development domains and IPs to be activated without counting towards the activation limit totals. The URL will still be logged.', 'templify_sl' ),
			'type'          => 'checkbox',
			'tooltip_title' => __( 'What is a Local Host?', 'templify_sl' ),
			'tooltip_desc'  => __( 'People who are in the developmental stages of their website will often build it offline using their own computer. This is called a Local Host. ', 'templify_sl' )
		),
		array(
			'id'            => 'edd_sl_readme_parsing',
			'name'          => __( 'Selling WordPress Plugins?', 'templify_sl' ),
			'desc'          => __( 'Check this box if you are selling WordPress plugins and wish to enable advanced ReadMe.txt file parsing.', 'templify_sl' ),
			'type'          => 'checkbox',
			'tooltip_title' => __( 'What is ReadMe.txt?', 'templify_sl' ),
			'tooltip_desc'  => __( 'Properly built WordPress plugins will include a ReadMe.txt file which includes things like the version, license, author, description, and more. Checking this will add a metabox to each download which allows for plugin data to be auto filled based on the included ReadMe.txt file in your plugin. Note that this is optional even if you are selling WordPress plugins.', 'templify_sl' )
		),
		array(
			'id'            => 'edd_sl_inline_upgrade_links',
			'name'          => __( 'Display Inline Upgrade Links', 'templify_sl' ),
			'desc'          => __( 'Check this box if you want to display inline upgrade links for customers who have upgradable purchases.', 'templify_sl' ),
			'type'          => 'checkbox',
			'tooltip_title' => __( 'Where are upgrade links displayed?', 'templify_sl' ),
			'tooltip_desc'  => __( 'Inline upgrade links are displayed below the \'Add To Cart\' button in products lists and on on individual product pages.', 'templify_sl' )
		),
		array(
			'id'            => 'edd_sl_proration_method',
			'name'          => __( 'Proration Method', 'templify_sl' ),
			'desc'          => __( 'Specify how to calculate proration for license upgrade.', 'templify_sl' ),
			'type'          => 'select',
			'options'       => array(
				'cost-based' => __( 'Cost-Based Calculation', 'templify_sl' ),
				'time-based' => __( 'Time-Based Calculation', 'templify_sl' )
			),
			'tooltip_title' => __( 'How are prorations calculated?', 'templify_sl' ),
			'tooltip_desc'  => __( 'Cost-based calculation is a type of pseudo-proration where the value of an upgrade is calculated based on the cost difference between the current and new licenses.<br /><br />Time-based calculation is true proration in which the amount of time remaining on the current license is calculated to adjust the cost of the new license.', 'templify_sl' ),
			'std'           => 'cost-based'
		),
		array(
			'id'            => 'edd_sl_renewals',
			'name'          => __( 'Allow Renewals', 'templify_sl' ),
			'desc'          => __( 'Check this box if you want customers to be able to renew their license keys.', 'templify_sl' ),
			'type'          => 'checkbox',
			'tooltip_title' => __( 'What does \'Allow Renewals\' do?', 'templify_sl' ),
			'tooltip_desc'  => $edd_sl_renewals_tt_desc
		),
		array(
			'id'            => 'edd_sl_email_matching',
			'name'          => __( 'Enforce Email Matching', 'templify_sl' ),
			'desc'          => __( 'Check this box if you want to enforce email matching on license renewals.', 'templify_sl' ),
			'type'          => 'checkbox',
			'tooltip_title' => __( 'What does \'Email Matching\' mean?', 'templify_sl' ),
			'tooltip_desc'  => __( 'Email matching restricts renewal of licenses to the email address used to originally purchase the license. This prevents license keys from being renewed by a different customer than purchased it.', 'templify_sl' )
		),
		array(
			'id'            => 'edd_sl_renewal_discount',
			'name'          => __( 'Renewal Discount', 'templify_sl' ),
			'desc'          => __( 'Enter a discount amount as a percentage, such as 10. Or enter 0 for no discount.', 'templify_sl' ),
			'type'          => 'text',
			'size'          => 'small',
			'tooltip_title' => __( 'When is this renewal discount used?', 'templify_sl' ),
			'tooltip_desc'  => $edd_sl_renewal_discount_tt_desc
		),
		array(
			'id' => 'edd_sl_disable_discounts',
			'name' => __( 'Disable Discount Codes on Renewals', 'templify_sl' ),
			'desc' => __( 'Check this box if you want to prevent customers from using non-renewal discounts in conjunction with renewals.', 'templify_sl' ),
			'type' => 'checkbox',
			'tooltip_title' => __( 'Disable Discount Codes', 'templify_sl' ),
			'tooltip_desc'  => __( 'This will disable the option to redeem discount codes when the cart contains a license renewal.', 'templify_sl' )
		),
	);

	return array_merge( $settings, array( 'software-licensing' => $license_settings ) );

}
add_filter( 'edd_settings_extensions', 'edd_sl_license_settings' );

/**
 * Registers the SL email settings under the emails tab.
 *
 * @since 3.8.5
 * @param array $settings
 * @return array
 */
function edd_sl_renewal_notices_settings_array( $settings ) {

	$edd_sl_send_renewal_reminders_tt_desc = __( 'Renewal Reminders are emails that are automatically sent out to the customer when their license key is about to expire. These emails will remind the customer that they need to renew. You can configure those emails below.', 'templify_sl' );
	if ( class_exists( 'EDD_Recurring' ) ) {
		$edd_sl_send_renewal_reminders_tt_desc .= ' ' . __( 'NOTE: If the product is a Recurring product and the customer\'s subscription is still active, the Renewal Reminders on this page will not be sent. Instead, the emails on the \'Recurring Payments\' page will be used (see \'Recurring Payments\' above). However, if the customer\'s subscription is cancelled or expired, they will be sent these emails.', 'templify_sl' );
	}

	$sl_settings = array(
		array(
			'id'            => 'edd_sl_send_renewal_reminders',
			'name'          => __( 'Send Renewal Reminders', 'templify_sl' ),
			'desc'          => __( 'Check this box if you want customers to receive a renewal reminder when their license key is about to expire.', 'templify_sl' ),
			'type'          => 'checkbox',
			'tooltip_title' => __( 'What are Renewal Reminders?', 'templify_sl' ),
			'tooltip_desc'  => $edd_sl_send_renewal_reminders_tt_desc,
		),
		array(
			'id'   => 'sl_renewal_notices',
			'name' => __( 'Renewal Notices', 'templify_sl' ),
			'desc' => __( 'Configure the renewal notice emails', 'templify_sl' ),
			'type' => 'hook',
		),
	);

	return array_merge( $settings, array( 'software-licensing' => $sl_settings ) );
}
add_filter( 'edd_settings_emails', 'edd_sl_renewal_notices_settings_array' );

/**
 * Displays the renewal notices options
 *
 * @access      public
 * @since       3.0
 * @param 		$args array option arguments
 * @return      void
*/
function edd_sl_renewal_notices_settings( $args ) {

	$notices = edd_sl_get_renewal_notices();
	//echo '<pre>'; print_r( $notices ); echo '</pre>';
	ob_start(); ?>
	<table id="edd_sl_renewal_notices" class="wp-list-table widefat fixed posts">
		<thead>
			<tr>
				<th class="edd-sl-renewal-subject-col" scope="col"><?php _e( 'Subject', 'templify_sl' ); ?></th>
				<th class="edd-sl-renewal-period-col" scope="col"><?php _e( 'Send Period', 'templify_sl' ); ?></th>
				<th scope="col"><?php _e( 'Actions', 'templify_sl' ); ?></th>
			</tr>
		</thead>
		<?php if( ! empty( $notices ) ) : $i = 1; ?>
			<?php foreach( $notices as $key => $notice ) : $notice = edd_sl_get_renewal_notice( $key ); ?>
			<tr <?php if( $i % 2 == 0 ) { echo 'class="alternate"'; } ?>>
				<td><?php echo esc_html( stripslashes( $notice['subject'] ) ); ?></td>
				<td><?php echo esc_html( edd_sl_get_renewal_notice_period_label( $key ) ); ?></td>
				<td>
					<?php
					$actions = array(
						'edit'    => array(
							'url'   => add_query_arg(
								array(
									'post_type'     => 'download',
									'page'          => 'edd-license-renewal-notice',
									'edd_sl_action' => 'edit-renewal-notice',
									'notice'        => urlencode( $key ),
								),
								admin_url( 'edit.php' )
							),
							'label' => __( 'Edit', 'templify_sl' ),
							'class' => 'edd-sl-edit-renewal-notice',
						),
						'clone'   => array(
							'url'   => wp_nonce_url(
								add_query_arg(
									array(
										'edd-action' => 'clone_renewal_notice',
										'notice-id'  => urlencode( $key ),
									)
								)
							),
							'label' => __( 'Clone', 'templify_sl' ),
							'class' => 'edd-sl-clone-renewal-notice',
						),
						'preview' => array(
							'url'   => wp_nonce_url(
								add_query_arg(
									array(
										'edd-action' => 'edd_sl_preview_notice',
										'notice-id' => urlencode( $key )
									),
									home_url()
								)
							),
							'label' => __( 'Preview', 'templify_sl' ),
							'class' => 'edd-sl-preview-renewal-notice',
						),
						'delete'  => array(
							'url'   => wp_nonce_url(
								add_query_arg(
									array(
										'edd_action' => 'delete_renewal_notice',
										'notice-id'  => urlencode( $key ),
									)
								)
							),
							'label' => __( 'Delete', 'templify_sl' ),
							'class' => 'edd-delete',
						),
					);
					$output  = array();
					foreach ( $actions as $key => $action ) {
						$output[] = sprintf(
							'<a href="%1$s" class="%2$s" data-key="%3$s" %5$s>%4$s</a>',
							esc_url( $action['url'] ),
							esc_attr( $action['class'] ),
							esc_attr( $key ),
							esc_html( $action['label'] ),
							'preview' === $key ? 'target="_blank"' : ''
						);
					}
					echo wp_kses_post( implode( ' | ', $output ) );
					?>
				</td>
			</tr>
			<?php $i++; endforeach; ?>
		<?php endif; ?>
	</table>
	<p>
		<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=download&page=edd-license-renewal-notice&edd_sl_action=add-renewal-notice' ) ); ?>" class="button-secondary" id="edd_sl_add_renewal_notice"><?php _e( 'Add Renewal Notice', 'templify_sl' ); ?></a>
	</p>
	<?php
	echo ob_get_clean();
}
add_action( 'edd_sl_renewal_notices', 'edd_sl_renewal_notices_settings' );

/**
 * Renders the add / edit renewal notice screen
 *
 * @since 3.0
 * @param array $input The value inputted in the field
 * @return string $input Sanitizied value
 */
function edd_sl_license_renewal_notice_edit() {

	$action = isset( $_GET['edd_sl_action'] ) ? sanitize_text_field( $_GET['edd_sl_action'] ) : 'add-renewal-notice';
	if ( ! in_array( $action, array( 'add-renewal-notice', 'edit-renewal-notice' ), true ) ) {
		return;
	}
	if ( 'edit-renewal-notice' === $action ) {
		include EDD_SL_PLUGIN_DIR . 'includes/admin/edit-renewal-notice.php';
	} else {
		include EDD_SL_PLUGIN_DIR . 'includes/admin/add-renewal-notice.php';
	}
}

/**
 * Processes cloning an existing renewal notice
 *
 * @since 3.5
 * @return void
 */
function edd_sl_process_clone_renewal_notice() {

	if( ! is_admin() || ! isset( $_GET['notice-id'] ) ) {
		return;
	}

	if( ! wp_verify_nonce( $_GET['_wpnonce'] ) ) {
		wp_die( __( 'Nonce verification failed', 'templify_sl' ), __( 'Error', 'templify_sl' ), array( 'response' => 401 ) );
	}

	if( ! current_user_can( 'manage_shop_settings' ) ) {
		wp_die( __( 'You do not have permission to add renewal notices', 'templify_sl' ), __( 'Error', 'templify_sl' ), array( 'response' => 401 ) );
	}

	$data = edd_sl_get_renewal_notice( absint( $_GET['notice-id'] ) );

	$notices = edd_sl_get_renewal_notices();
	$key     = is_array( $notices ) ? count( $notices ) : 1;

	$notices[] = array(
		'subject'     => $data['subject'] . ' - ' . __( 'Copy', 'templify_sl' ),
		'message'     => $data['message'],
		'send_period' => $data['send_period']
	);

	update_option( 'edd_sl_renewal_notices', $notices );

	$redirect_url = add_query_arg(
		array(
			'post_type'     => 'download',
			'page'          => 'edd-license-renewal-notice',
			'edd_sl_action' => 'edit-renewal-notice',
			'notice'        => urlencode( $key ),
			'edd-message'   => urlencode( __( 'Renewal Notice cloned successfully. You are editing a new notice.', 'templify_sl' ) ),
			'edd-result'    => 'success',
		),
		admin_url( 'edit.php' )
	);

	wp_safe_redirect( $redirect_url );
	exit;

}
add_action( 'edd_clone_renewal_notice', 'edd_sl_process_clone_renewal_notice' );

/**
 * Processes the creation of a new renewal notice
 *
 * @since 3.0
 * @param array $data The post data
 * @return void
 */
function edd_sl_process_add_renewal_notice( $data ) {

	if( ! is_admin() ) {
		return;
	}

	if( ! current_user_can( 'manage_shop_settings' ) ) {
		wp_die( __( 'You do not have permission to add renewal notices', 'templify_sl' ), __( 'Error', 'templify_sl' ), array( 'response' => 401 ) );
	}

	if( ! wp_verify_nonce( $data['edd-renewal-notice-nonce'], 'edd_renewal_nonce' ) ) {
		wp_die( __( 'Nonce verification failed', 'templify_sl' ), __( 'Error', 'templify_sl' ), array( 'response' => 401 ) );
	}

	$subject = isset( $data['subject'] ) ? sanitize_text_field( $data['subject'] ) : __( 'Your License Key is About to Expire', 'templify_sl' );
	$period  = isset( $data['period'] )  ? sanitize_text_field( $data['period'] )  : '+1month';
	$message = isset( $data['message'] ) ? wp_kses( stripslashes( $data['message'] ), wp_kses_allowed_html( 'post' ) ) : false;
	$result  = 'success';
	$notice  = __( 'Renewal Notice saved successfully.', 'templify_sl' );

	if ( empty( $message ) ) {
		$result  = 'warning';
		$notice  = __( 'Your message was empty and could not be saved. It has been reset to the default.', 'templify_sl' );
		$message = edd_sl_get_default_renewal_notice_message();
	}

	$notices   = edd_sl_get_renewal_notices();
	$key       = is_array( $notices ) ? count( $notices ) : 1;
	$notices[] = array(
		'subject'     => $subject,
		'message'     => $message,
		'send_period' => $period,
	);
	update_option( 'edd_sl_renewal_notices', $notices );

	$redirect_url = add_query_arg(
		array(
			'post_type'     => 'download',
			'page'          => 'edd-license-renewal-notice',
			'edd_sl_action' => 'edit-renewal-notice',
			'notice'        => urlencode( $key ),
			'edd-message'   => urlencode( $notice ),
			'edd-result'    => urlencode( $result ),
		),
		admin_url( 'edit.php' )
	);
	wp_safe_redirect( $redirect_url );
	exit;

}
add_action( 'edd_add_renewal_notice', 'edd_sl_process_add_renewal_notice' );

/**
 * Processes the update of an existing renewal notice
 *
 * @since 3.0
 * @param array $data The post data
 * @return void
 */
function edd_sl_process_update_renewal_notice( $data ) {

	if( ! is_admin() ) {
		return;
	}

	if( ! current_user_can( 'manage_shop_settings' ) ) {
		wp_die( __( 'You do not have permission to add renewal notices', 'templify_sl' ), __( 'Error', 'templify_sl' ), array( 'response' => 401 ) );
	}

	if( ! wp_verify_nonce( $data['edd-renewal-notice-nonce'], 'edd_renewal_nonce' ) ) {
		wp_die( __( 'Nonce verification failed', 'templify_sl' ), __( 'Error', 'templify_sl' ), array( 'response' => 401 ) );
	}

	if( ! isset( $data['notice-id'] ) ) {
		wp_die( __( 'No renewal notice ID was provided', 'templify_sl' ) );
	}

	$subject = isset( $data['subject'] ) ? sanitize_text_field( $data['subject'] ) : __( 'Your License Key is About to Expire', 'templify_sl' );
	$period  = isset( $data['period'] )  ? sanitize_text_field( $data['period'] )  : '1month';
	$message = isset( $data['message'] ) ? wp_kses( stripslashes( $data['message'] ), wp_kses_allowed_html( 'post' ) ) : false;
	$result  = 'success';
	$notice  = __( 'Renewal Notice saved successfully.', 'templify_sl' );

	if ( empty( $message ) ) {
		$result  = 'warning';
		$notice  = __( 'Your message was empty and could not be saved. It has been reset to the default.', 'templify_sl' );
		$message = edd_sl_get_default_renewal_notice_message();
	}

	$notices = edd_sl_get_renewal_notices();
	$notices[ absint( $data['notice-id'] ) ] = array(
		'subject'     => $subject,
		'message'     => $message,
		'send_period' => $period
	);

	update_option( 'edd_sl_renewal_notices', $notices );

	$redirect_url = add_query_arg(
		array(
			'post_type'     => 'download',
			'page'          => 'edd-license-renewal-notice',
			'edd_sl_action' => 'edit-renewal-notice',
			'notice'        => urlencode( $data['notice-id'] ),
			'edd-message'   => urlencode( $notice ),
			'edd-result'    => urlencode( $result ),
		),
		admin_url( 'edit.php' )
	);

	wp_safe_redirect( $redirect_url );

	exit;

}
add_action( 'edd_edit_renewal_notice', 'edd_sl_process_update_renewal_notice' );

/**
 * Processes the deletion of an existing renewal notice
 *
 * @since 3.0
 * @param array $data The post data
 * @return void
 */
function edd_sl_process_delete_renewal_notice( $data ) {

	if( ! is_admin() ) {
		return;
	}

	if( ! current_user_can( 'manage_shop_settings' ) ) {
		wp_die( __( 'You do not have permission to add renewal notices', 'templify_sl' ), __( 'Error', 'templify_sl' ), array( 'response' => 401 ) );
	}

	if( ! wp_verify_nonce( $data['_wpnonce'] ) ) {
		wp_die( __( 'Nonce verification failed', 'templify_sl' ), __( 'Error', 'templify_sl' ), array( 'response' => 401 ) );
	}

	if( empty( $data['notice-id'] ) && 0 !== (int) $data['notice-id'] ) {
		wp_die( __( 'No renewal notice ID was provided', 'templify_sl' ), __( 'Error', 'templify_sl' ), array( 'response' => 409 ) );
	}

	$notices = edd_sl_get_renewal_notices();
	unset( $notices[ absint( $data['notice-id'] ) ] );

	update_option( 'edd_sl_renewal_notices', $notices );

	wp_safe_redirect(
		esc_url_raw(
			add_query_arg(
				array(
					'post_type' => 'download',
					'page'      => 'edd-settings',
					'tab'       => 'emails',
					'section'   => 'software-licensing',
				),
				admin_url( 'edit.php' )
			)
		)
	);
	exit;

}
add_action( 'edd_delete_renewal_notice', 'edd_sl_process_delete_renewal_notice' );

/**
 * Gets the default text for the renewal notices.
 *
 * @since 3.7
 * @return string
 */
function edd_sl_get_default_renewal_notice_message() {
	return 'Hello {name},

Your license key for {product_name} is about to expire.

If you wish to renew your license, simply click the link below and follow the instructions.

Your license expires on: {expiration}.

Your expiring license key is: {license_key}.

Renew now: {renewal_link}.';
}
