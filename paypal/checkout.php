<?php
/**
 * Checkout Actions
 *
 * @package   edd-paypal-commerce-pro
 * @copyright Copyright (c) 2021, Sandhills Development, LLC
 * @license   GPL2+
 * @since     1.0
 */

namespace EDD_PayPal_Commerce_Pro\Advanced;

/**
 * Adds card fields after the PayPal Smart Buttons.
 *
 * @since 1.0
 */
add_action( 'edd_paypal_after_button_container', function () {
	if ( ! advanced_payments_enabled() ) {
		return;
	}
	?>
	<div id="edd-paypal-pro-cc-fields">
		<div id="edd-paypal-pro-or-divider">
			<?php esc_html_e( 'Or pay by card', 'edd-paypal-commerce-pro' ); ?>
		</div>

		<?php remove_action( 'edd_after_cc_fields', 'edd_default_cc_address_fields' ); ?>
		<div class="card_container">
			<div class="edd-paypal-pro-card-form-group">
				<label for="card-holder-name"><?php esc_html_e( 'Name on Card', 'edd-paypal-commerce-pro' ); ?></label>
				<input type="text" id="card-holder-name" class="edd-paypal-pro-card-field" name="card-holder-name" autocomplete="off" placeholder="<?php esc_attr_e( 'Card holder name', 'edd-paypal-commerce-pro' ); ?>">
			</div>

			<div class="edd-paypal-pro-card-form-group">
				<label for="card-number"><?php esc_html_e( 'Card Number', 'edd-paypal-commerce-pro' ); ?></label>
				<div id="card-number" class="edd-paypal-pro-card-field"></div>
			</div>

			<div class="edd-paypal-pro-card-form-group--half">
				<label for="expiration-date"><?php esc_html_e( 'Expiration Date', 'edd-paypal-commerce-pro' ); ?></label>
				<div id="expiration-date" class="edd-paypal-pro-card-field"></div>
			</div>
			<div class="edd-paypal-pro-card-form-group--half">
				<label for="cvv"><?php esc_html_e( 'CVV', 'edd-paypal-commerce-pro' ); ?></label>
				<div id="cvv" class="edd-paypal-pro-card-field"></div>
			</div>
		</div>

		<div id="edd-paypal-pro-errors-wrap"></div>
		<?php

		remove_all_filters( 'edd_checkout_button_purchase' );
		echo edd_checkout_button_purchase();
		?>
	</div>
	<?php
} );
