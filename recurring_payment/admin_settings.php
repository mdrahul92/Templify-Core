<?php 

function edd_recurring_settings_section( $sections ) {

$sections['recurring'] = __( 'Subscriptions', 'edd-recurring' );

return $sections;
}
$settings_tab = version_compare( '3.2.7', '2.11.3', '>=' ) ? 'gateways' : 'extensions';
add_filter( "edd_settings_sections_{$settings_tab}", 'edd_recurring_settings_section' );
add_filter( 'edd_settings_sections_emails', 'edd_recurring_settings_section' );



function edd_recurring_settings( $settings ) {

	$recurring_settings = array(
		'recurring' => array(
			array(
				'id'    => 'recurring_download_limit',
				'name'  => __( 'Limit File Downloads', 'edd-recurring' ),
				'desc'  => __( 'Check this if you\'d like to require users have an active subscription in order to download files associated with a recurring product.', 'edd-recurring' ),
				'type'  => 'checkbox'
			),
			array(
				'id'    => 'recurring_treat_completed_subs_as_active',
				'name'  => __( 'Allow "Completed" subscriptions to download their files', 'edd-recurring' ),
				'desc'  => __( 'When "Limit File Downloads" is enabled, would you like users with "Completed" subscriptions to be able to download their files, despite their subscription technically not being "Active"?', 'edd-recurring' ),
				'type'  => 'checkbox'
			),
			array(
				'id'   => 'recurring_show_terms_notice',
				'name' => __( 'Display Subscription Terms', 'edd-recurring' ),
				'desc' => __( 'When selected, the billing times and frequency will be shown below the purchase link.', 'edd-recurring' ),
				'type' => 'checkbox',
			),
			array(
				'id'   => 'recurring_show_signup_fee_notice',
				'name' => __( 'Display Signup Fee', 'edd-recurring' ),
				'desc' => __( 'When selected, signup fee associated with a subscription will be shown below the purchase link.', 'edd-recurring' ),
				'type' => 'checkbox'
			),
			array(
				'id'   => 'recurring_signup_fee_label',
				'name' => __( 'Signup Fee Label', 'edd-recurring' ),
				'desc' => __( 'The label used for signup fees, if any. This is shown on checkout and on individual purchase options if "Display Signup Fee" above is checked.', 'edd-recurring' ),
				'type' => 'text',
				'std'  => __( 'Signup Fee', 'edd-recurring' )
			),
			array(
				'id'   => 'recurring_cancel_button_text',
				'name' => __( 'Cancel Subscription Text', 'edd-recurring' ),
				'desc' => __( 'The label used for the Cancel action. This text is shown to the customer when managing their subscriptions.', 'edd-recurring' ),
				'type' => 'text',
				'std'  => __( 'Cancel', 'edd-recurring' )
			),
			array(
				'id'    => 'recurring_one_time_discounts',
				'name'  => __( 'One Time Discounts', 'edd-recurring' ),
				'desc'  => __( 'Check this if you\'d like discount codes to apply only to the initial subscription payment and not all payments. <strong>Note</strong>: one-time discount codes will not apply to free trials.', 'edd-recurring' ),
				'type'  => 'checkbox',
				'tooltip_title' => __( 'One Time Discounts', 'edd-recurring' ),
				'tooltip_desc'  => __( 'When one time discounts are enabled, only the first payment in a subscription will be discounted when a discount code is redeemed on checkout. Free trials and one time discounts, however, cannot be combined. If a customer purchases a free trial, discount codes will always apply to <em>all</em> payments made for the subscription.', 'easy-digital-downloads' ),

			),
			array(
				'id'    => 'recurring_one_time_trials',
				'name'  => __( 'One Time Trials', 'edd-recurring' ),
				'desc'  => __( 'Check this if you\'d like customers to be prevented from purchasing a free trial multiple times.', 'edd-recurring' ),
				'type'  => 'checkbox'
			),
		)
	);

	return array_merge( $settings, $recurring_settings );
}
add_filter( "edd_settings_{$settings_tab}", 'edd_recurring_settings' );
