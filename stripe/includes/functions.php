<?php
function edds_pro_preapproval_setting( $settings ) {
	if ( empty( $settings['edd-stripe'] ) ) {
		return $settings;
	}

	$setting = array(
		'id'            => 'stripe_preapprove_only',
		'name'          => __( 'Preapproved Payments', 'edds' ),
		'desc'          => __( 'Authorize payments for processing and collection at a future date.', 'edds' ),
		'type'          => 'checkbox',
		'tooltip_title' => __( 'What does checking preapprove do?', 'edds' ),
		'tooltip_desc'  => __( 'If you choose this option, Stripe will not charge the customer right away after checkout, and the payment status will be set to preapproved in Easy Digital Downloads. You (as the admin) can then manually change the status to Complete by going to Payment History and changing the status of the payment to Complete. Once you change it to Complete, the customer will be charged. Note that most typical stores will not need this option.', 'edds' ),
	);

	$position = array_search(
		'stripe_restrict_assets',
		array_keys(
			$settings['edd-stripe']
		),
		true
	);

	array_splice(
		$settings['edd-stripe'],
		$position,
		0,
		array(
			'stripe_preapprove_only' => $setting,
		)
	);

	return $settings;
}
add_filter( 'edd_settings_gateways', 'edds_pro_preapproval_setting', 20 );
