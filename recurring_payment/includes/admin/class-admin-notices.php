<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EDD_Recurring_Admin_Notices {

	public function __construct() {
		$this->init();
	}

	public function init() {
		add_action( 'admin_notices', array( $this, 'notices' ) );
	}

	public function notices() {

		if ( ! edd_is_admin_page( 'edd-subscriptions' ) ) {
			return;
		}

		if ( empty( $_GET['edd-message'] ) ) {
			return;
		}

		$type    = 'updated';
		$message = '';

		switch ( strtolower( $_GET['edd-message'] ) ) {

			case 'updated':
				$message = __( 'Subscription updated successfully.', 'templify-recurring' );

				break;

			case 'created':
				$message = __( 'Subscription created successfully.', 'templify-recurring' );
				break;

			case 'deleted':
				$message = __( 'Subscription deleted successfully.', 'templify-recurring' );

				break;

			case 'cancelled':
				$message = __( 'Subscription cancelled successfully.', 'templify-recurring' );

				break;

			case 'subscription-note-added':
				$message = __( 'Subscription note added successfully.', 'templify-recurring' );

				break;

			case 'subscription-note-not-added':
				$message = __( 'Subscription note could not be added.', 'templify-recurring' );
				$type    = 'error';
				break;

			case 'renewal-added':
				$message = __( 'Renewal payment recorded successfully.', 'templify-recurring' );

				break;

			case 'renewal-not-added':
				$message = __( 'Renewal payment could not be recorded.', 'templify-recurring' );
				$type    = 'error';

				break;

			case 'retry-success':
				$message = __( 'Retry succeeded! The subscription has been renewed successfully.', 'templify-recurring' );

				break;

			case 'retry-failed':
				$message = sprintf( __( 'Retry failed. %s', 'templify-recurring' ), sanitize_text_field( urldecode( $_GET['error-message'] ) ) );
				$type    = 'error';

				break;

			case 'reactivated':
				$message = __( 'Subscription reactivated successfully.', 'templify-recurring' );
				break;
		}

		if ( ! empty( $message ) ) {
			echo '<div class="' . esc_attr( $type ) . '"><p>' . $message . '</p></div>';
		}

	}

}
$edd_recurring_admin_notices = new EDD_Recurring_Admin_Notices();
