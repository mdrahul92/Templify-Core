<?php

function edd_recurring_metabox_head( $download_id ) {
	?>
	<th><?php _e( 'Recurring', 'edd-recurring' ); ?></th>
	<th><?php _e( 'Free Trial', 'edd-recurring' ); ?></th>
	<th><?php _e( 'Period', 'edd-recurring' ); ?></th>
	<th><?php echo _x( 'Times', 'Referring to billing period', 'edd-recurring' ); ?></th>
	<th><?php echo _x( 'Signup Fee', 'Referring to subscription signup fee', 'edd-recurring' ); ?></th>
	<?php
}

function edd_recurring_price_row_hook( $download_id, $price_id, $args ) {
	?>
	<div class="edd-custom-price-option-section">
		<?php
		if ( version_compare( '3.2.7', '2.10.999', '>' ) ) {
			printf( '<span class="edd-custom-price-option-section-title">%s</span>', esc_html__( 'Recurring Payments Settings', 'edd-recurring' ) );
		}
		?>
		<div class="edd-custom-price-option-section-content edd-form-row">
		<?php
			do_action( 'edd_recurring_download_price_row', $download_id, $price_id, $args );
		?>
		</div>
	</div>
	<?php
}
add_action( 'edd_download_price_option_row', 'edd_recurring_price_row_hook', 999, 3 );

function edd_recurring_metabox_recurring( $download_id, $price_id, $args ) {

	$recurring = is_price_recurring( $download_id, $price_id );

	?>
	<div class="edd-form-group edd-form-row__column edd-recurring-enabled">
		<label for="edd_variable_prices[<?php echo esc_attr( $price_id ); ?>][recurring]" class="edd-form-group__label"><?php esc_html_e( 'Recurring', 'edd-recurring' ); ?></label>
		<div class="edd-form-group__control">
			<select name="edd_variable_prices[<?php echo esc_attr( $price_id ); ?>][recurring]" id="edd_variable_prices[<?php echo esc_attr( $price_id ); ?>][recurring]" class="edd-form-group__input">
				<option value="no" <?php selected( $recurring, false ); ?>><?php echo esc_attr_e( 'No', 'edd-recurring' ); ?></option>
				<option value="yes" <?php selected( $recurring, true ); ?>><?php echo esc_attr_e( 'Yes', 'edd-recurring' ); ?></option>
			</select>
		</div>
	</div>
	<?php
}
add_action( 'edd_recurring_download_price_row', 'edd_recurring_metabox_recurring', 999, 3 );

function edd_recurring_metabox_free_trial( $download_id, $price_id, $args ) {

	$recurring = is_price_recurring( $download_id, $price_id );
	$periods   = singular_periods();
	$trial     = get_trial_period( $download_id, $price_id );
	$quantity  = empty( $trial['quantity'] ) ? '' : $trial['quantity'];
	$unit      = empty( $trial['unit'] ) ? '' : $trial['unit'];
	$disabled  = $recurring ? '' : 'disabled ';
	// Remove non-valid trial periods
	unset( $periods['quarter'] );
	unset( $periods['semi-year'] );

	?>
	<fieldset class="edd-form-group edd-form-row__column edd-recurring-free-trial">
		<legend class="edd-form-group__label"><?php esc_html_e( 'Free Trial', 'edd-recurring' ); ?></legend>
		<div class="edd-form-group__control edd-form-group__control--is-inline">
			<div class="eddrecurring-trial-quantity">
				<label for="edd_variable_prices[<?php echo esc_attr( $price_id ); ?>][trial-quantity]" class="screen-reader-text edd-form-group__label"><?php esc_html_e( 'Trial Quantity', 'edd-recurring' ); ?></label>
				<input <?php echo $disabled; ?> name="edd_variable_prices[<?php echo esc_attr( $price_id ); ?>][trial-quantity]" class="edd-form-group__input small-text trial-quantity" id="edd_variable_prices[<?php echo esc_attr( $price_id ); ?>][trial-quantity]" type="number" min="0" step="1" value="<?php echo esc_attr( $quantity ); ?>" placeholder="0"/>
			</div>
			<div class="eddrecurring-trial-period">
				<label for="edd_variable_prices[<?php echo esc_attr( $price_id ); ?>][trial-unit]" class="screen-reader-text edd-form-group__label"><?php esc_html_e( 'Trial Period', 'edd-recurring' ); ?></label>
				<select <?php echo $disabled; ?> name="edd_variable_prices[<?php echo esc_attr( $price_id ); ?>][trial-unit]" id="edd_variable_prices[<?php echo esc_attr( $price_id ); ?>][trial-unit]">
					<?php foreach ( $periods as $key => $value ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $unit, $key ); ?>><?php echo esc_attr( $value ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
	</fieldset>
	<?php
}
add_action( 'edd_recurring_download_price_row', 'edd_recurring_metabox_free_trial', 999, 3 );

function edd_recurring_metabox_period( $download_id, $price_id, $args ) {

	$recurring = is_price_recurring( $download_id, $price_id );
	$periods   = periods();
	$period    = get_period( $price_id );

	$disabled = $recurring ? '' : 'disabled ';

	?>
	<div class="edd-form-group edd-form-row__column edd-recurring-period">
		<label for="edd_variable_prices[<?php echo esc_attr( $price_id ); ?>][period]" class="edd-form-group__label"><?php esc_html_e( 'Period', 'edd-recurring' ); ?></label>
		<div class="edd-form-group__control">
			<select class="edd-form-group__input" <?php echo $disabled; ?>name="edd_variable_prices[<?php echo esc_attr( $price_id ); ?>][period]" id="edd_variable_prices[<?php echo esc_attr( $price_id ); ?>][period]">
				<?php foreach ( $periods as $key => $value ) : ?>
					<option value="<?php echo $key; ?>" <?php selected( $period, $key ); ?>><?php echo esc_attr( $value ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>
	<?php
}
add_action( 'edd_recurring_download_price_row', 'edd_recurring_metabox_period', 999, 3 );

function edd_recurring_metabox_times( $download_id, $price_id, $args ) {

	$recurring = is_price_recurring( $download_id, $price_id );
	$times     = get_times( $price_id );
	$period    = get_period( $price_id );

	$disabled = $recurring ? '' : 'disabled ';

	?>
	<div class="edd-form-row__column edd-form-group times edd-recurring-times">
		<label for="edd_variable_prices[<?php echo esc_attr( $price_id ); ?>][times]" class="edd-form-group__label"><?php echo esc_html_x( 'Times', 'Referring to billing period', 'edd-recurring' ); ?></label>
		<div class="edd-form-group__control">
			<input class="edd-form-group__input small-text" <?php echo $disabled; ?>type="number" min="0" step="1" name="edd_variable_prices[<?php echo esc_attr( $price_id ); ?>][times]" id="edd_variable_prices[<?php echo esc_attr( $price_id ); ?>][times]" value="<?php echo esc_attr( $times ); ?>" />
		</div>
	</div>
	<?php
}
add_action( 'edd_recurring_download_price_row', 'edd_recurring_metabox_times', 999, 3 );

function edd_recurring_metabox_signup_fee( $download_id, $price_id, $args ) {

	$recurring         = is_price_recurring( $download_id, $price_id );
	$has_trial         = has_free_trial( $download_id, $price_id );
	$signup_fee        = get_signup_fee( $price_id, $download_id );
	$currency_position = edd_get_option( 'currency_position', 'before' );

	$disabled = $recurring && ! $has_trial ? '' : 'disabled ';

	?>
	<div class="edd-form-group edd-form-row__column signup_fee edd-recurring-fee">
		<label for="edd_variable_prices[<?php echo esc_attr( $price_id ); ?>][signup_fee]" class="edd-form-group__label"><?php echo esc_html_x( 'Signup Fee', 'Referring to subscription signup fee', 'edd-recurring' ); ?></label>
		<div class="edd-form-group__control">
			<?php
			if ( 'before' === $currency_position ) {
				?>
				<span class="edd-amount-control__currency is-before"><?php echo esc_html( edd_currency_filter( '' ) ); ?></span>
				<input type="text" name="edd_variable_prices[<?php echo esc_attr( $price_id ); ?>][signup_fee]" id="edd_variable_prices[<?php echo esc_attr( $price_id ); ?>][signup_fee]" class="edd-form-group__input edd-price-field" value="<?php echo esc_attr( $signup_fee ); ?>"<?php echo $disabled;?>/>
				<?php
			} else {
				?>
				<input type="text" name="edd_variable_prices[<?php echo esc_attr( $price_id ); ?>][signup_fee]" id="edd_variable_prices[<?php echo esc_attr( $price_id ); ?>][signup_fee]" class="edd-form-group__input edd-price-field" value="<?php echo esc_attr( $signup_fee ); ?>"<?php echo $disabled;?>/>
				<span class="edd-amount-control__currency is-after"><?php echo esc_html( edd_currency_filter( '' ) ); ?></span>
				<?php
			}
			?>
		</div>
	</div>
	<?php
}
add_action( 'edd_recurring_download_price_row', 'edd_recurring_metabox_signup_fee', 999, 3 );

function edd_recurring_save_single( $fields ) {
	$fields[] = 'edd_period';
	$fields[] = 'edd_times';
	$fields[] = 'edd_recurring';
	$fields[] = 'edd_signup_fee';
	if( defined( 'EDD_CUSTOM_PRICES' ) ) {
		$fields[] = 'edd_custom_signup_fee';
		$fields[] = 'edd_custom_recurring';
		$fields[] = 'edd_custom_times';
		$fields[] = 'edd_custom_period';
	}
	return $fields;
}
add_filter( 'edd_metabox_fields_save', 'edd_recurring_save_single' );

function edd_recurring_save_trial_period( $post_id, $post ) {

	if ( ! current_user_can( 'edit_product', $post_id ) ) {
		return;
	}

	if( ! empty( $_POST['edd_recurring_free_trial'] ) && empty( $_POST['_variable_pricing'] ) ) {

		$default = array(
			'quantity' => 1,
			'unit'     => 'month',
		);
		$period             = array();
		$period['unit']     = sanitize_text_field( $_POST['edd_recurring_trial_unit'] );
		$period['quantity'] = absint( $_POST['edd_recurring_trial_quantity'] );
		$period             = wp_parse_args( $period, $default );

		update_post_meta( $post_id, 'edd_trial_period', $period );
	} else {
		delete_post_meta( $post_id, 'edd_trial_period' );
	}
}
add_action( 'edd_save_download', 'edd_recurring_save_trial_period', 10, 2 );

function edd_recurring_metabox_colspan() {
	echo '<script type="text/javascript">jQuery(function($){ $("#edd_price_fields td.submit").attr("colspan", 7)});</script>';
}
add_action( 'edd_meta_box_fields', 'edd_recurring_metabox_colspan', 20 );

function edd_recurring_metabox_hook( $download_id ) {
	$is_variable = edd_has_variable_prices( $download_id );
	$display     = $is_variable ? ' style="display:none;"' : '';
	?>
	<div class="edd-form-row edd-recurring-single"<?php echo $display; ?>>
		<?php do_action( 'edd_recurring_download_metabox', $download_id ); ?>
	</div>
	<?php
}
add_action( 'edd_after_price_field', 'edd_recurring_metabox_hook', 1 );


function edd_recurring_metabox_single_recurring( $download_id ) {

	$recurring = is_recurring( $download_id );

?>
	<div class="edd-form-group edd-form-row__column">
		<label for="edd_recurring" class="edd-form-group__label"><?php esc_html_e( 'Recurring', 'edd-recurring' ); ?></label>
		<div class="edd-form-group__control">
			<select name="edd_recurring" id="edd_recurring" class="edd-form-group__input">
				<option value="no" <?php selected( $recurring, false ); ?>><?php esc_attr_e( 'No', 'edd-recurring' ); ?></option>
				<option value="yes" <?php selected( $recurring, true ); ?>><?php esc_attr_e( 'Yes', 'edd-recurring' ); ?></option>
			</select>
		</div>
	</div>
	<?php
}
add_action( 'edd_recurring_download_metabox', 'edd_recurring_metabox_single_recurring' );

function edd_recurring_metabox_single_period( $download_id ) {

	$periods = periods();
	$period  = get_period_single( $download_id );
	?>
	<div class="edd-form-group edd-form-row__column">
		<label for="edd_period" class="edd-form-group__label"><?php esc_html_e( 'Period', 'edd-recurring' ); ?></label>
		<div class="edd-form-group__control">
			<select name="edd_period" id="edd_period" class="edd-form-group__input">
				<?php foreach ( $periods as $key => $value ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $period, $key ); ?>><?php echo esc_html( $value ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>
	<?php
}
add_action( 'edd_recurring_download_metabox', 'edd_recurring_metabox_single_period' );

function edd_recurring_metabox_single_times( $download_id ) {

	$times = get_times_single( $download_id );
	?>

	<div class="edd-form-group edd-form-row__column">
		<label for="edd_times" class="edd-form-group__label"><?php esc_html_e( 'Times', 'edd-recurring' ); ?></label>
		<div class="edd-form-group__control">
			<input type="number" min="0" step="1" name="edd_times" id="edd_times" class="edd-form-group__input small-text" value="<?php echo esc_attr( $times ); ?>" />
		</div>
	</div>
	<?php
}
add_action( 'edd_recurring_download_metabox', 'edd_recurring_metabox_single_times' );

function edd_recurring_metabox_single_signup_fee( $download_id ) {

	$has_trial         = has_free_trial( $download_id );
	$signup_fee        = get_signup_fee_single( $download_id );
	$disabled          = $has_trial ? ' disabled="disabled"' : '';
	$currency_position = edd_get_option( 'currency_position', 'before' );
	?>

	<div class="edd-form-group edd-form-row__column">
		<label for="edd_signup_fee" class="edd-form-group__label"><?php esc_html_e( 'Signup Fee', 'edd-recurring' ); ?></label>
		<div class="edd-form-group__control">
			<?php
			if ( 'before' === $currency_position ) {
				?>
				<span class="edd-amount-control__currency is-before"><?php echo esc_html( edd_currency_filter( '' ) ); ?></span>
				<input type="text" name="edd_signup_fee" id="edd_signup_fee" class="edd-form-group__input edd-price-field" value="<?php echo esc_attr( $signup_fee ); ?>"<?php echo $disabled;?>/>
				<?php
			} else {
				?>
				<input type="text" name="edd_signup_fee" id="edd_signup_fee" class="edd-form-group__input edd-price-field" value="<?php echo esc_attr( $signup_fee ); ?>"<?php echo $disabled;?>/>
				<span class="edd-amount-control__currency is-after"><?php echo esc_html( edd_currency_filter( '' ) ); ?></span>
				<?php
			}
			?>
		</div>
	</div>
	<?php
}
add_action( 'edd_recurring_download_metabox', 'edd_recurring_metabox_single_signup_fee' );



function edd_recurring_metabox_trial_options( $download_id ) {

	$has_trial      = has_free_trial( $download_id );
	$periods        = singular_periods();
	$period         = get_trial_period( $download_id );
	$quantity       = empty( $period['quantity'] ) ? '' : $period['quantity'];
	$unit           = empty( $period['unit'] ) ? '' : $period['unit'];
	$option_display = $has_trial ? '' : ' style="display:none;"';

	// Remove non-valid trial periods
	unset( $periods['quarter'] );
	unset( $periods['semi-year'] );

	$one_one_discount_help = '';
	if( edd_get_option( 'recurring_one_time_discounts' ) ) {
		$one_one_discount_help = ' ' . __( '<strong>Additional note</strong>: with free trials, one time discounts are not supported and discount codes for this product will apply to all payments after the trial period.', 'edd-recurring' );
	}

	$variable_pricing   = edd_has_variable_prices( $download_id );
	$variable_display   = $variable_pricing ? ' style="display:none;"' : '';

	?>
	<div id="edd_recurring_free_trial_options_wrap" class="edd-form-group"<?php echo $variable_display; ?>>

		<?php if( edd_is_gateway_active( '2checkout' ) || edd_is_gateway_active( '2checkout_onsite' ) ) : ?>
			<p><strong><?php _e( '2Checkout does not support free trial periods. Subscriptions purchased through 2Checkout cannot include free trials.', 'edd-recurring' ); ?></strong></p>
		<?php endif; ?>

		<p>
			<input type="checkbox" name="edd_recurring_free_trial" id="edd_recurring_free_trial" value="yes"<?php checked( true, $has_trial ); ?>/>
			<label for="edd_recurring_free_trial">
				<?php esc_html_e( 'Enable free trial for subscriptions', 'edd-recurring' ); ?>
				<span alt="f223" class="edd-help-tip dashicons dashicons-editor-help" title="<?php _e( 'Check this box to include a free trial with subscriptions for this product. When signing up for a free trial, the customer\'s payment details will be taken at checkout but the customer will not be charged until the free trial is completed. <strong>Note:</strong> this only applies when purchasing a subscription. If a price option is not set to recurring, this free trial will not be used.', 'edd-recurring' ); echo $one_one_discount_help; ?>"></span>
			</label>
		</p>
		<fieldset id="edd_recurring_free_trial_options" class="edd-form-group"<?php echo $option_display; ?>>
			<legend class="screen-reader-text"><?php esc_html_e( 'Free Trial Options', 'edd-recurring' ); ?></legend>
			<div class="edd-form-group__control edd-form-group__control--is-inline">
				<div class="edd-recurring-trial-quantity">
					<label for="edd_recurring_trial_quantity" class="edd-form-group__label screen-reader-text"><?php esc_html_e( 'Trial Quantity', 'edd-recurring' ); ?></label>
					<input name="edd_recurring_trial_quantity" id="edd_recurring_trial_quantity" class="edd-form-group__input small-text" type="number" min="1" step="1" value="<?php echo esc_attr( $quantity ); ?>" placeholder="1"/>
				</div>
				<div class="edd-recurring-trial-unit">
					<label for="edd_recurring_trial_unit" class="edd-form-group__label screen-reader-text"><?php esc_html_e( 'Trial Period', 'edd-recurring' ); ?></label>
					<select name="edd_recurring_trial_unit" id="edd_recurring_trial_unit" class="edd-form-group__input">
						<?php foreach ( $periods as $key => $value ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $unit, $key ); ?>><?php echo esc_attr( $value ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
		</fieldset>
	</div>
	<?php
}
add_action( 'edd_meta_box_price_fields', 'edd_recurring_metabox_trial_options' );


function edd_recurring_metabox_custom_options( $download_id ) {
	if ( ! defined( 'EDD_CUSTOM_PRICES' ) ) {
		return;
	}
	$custom            = get_post_meta( $download_id, '_edd_cp_custom_pricing', true );
	$recurring         = is_custom_recurring( $download_id );
	$periods           = periods();
	$period            = get_custom_period( $download_id );
	$times             = get_custom_times( $download_id );
	$signup_fee        = get_custom_signup_fee( $download_id );
	$display           = $custom ? '' : ' style="display:none;"';
	$currency_position = edd_get_option( 'currency_position', 'before' );
	$disabled          = $custom && $recurring ? '' : ' disabled';
	?>
	<fieldset id="edd_custom_recurring" class="edd_recurring_custom_wrap edd-form-row"<?php echo $display; ?>>
		<legend><?php esc_html_e( 'Recurring Options for Custom Prices', 'edd-recurring' ); ?></legend>
		<p><?php esc_html_e( 'Select the recurring options for customers that pay with a custom price.', 'edd-recurring' ); ?></p>
		<div class="edd-form-group edd-form-row__column">
			<label for="edd_custom_recurring" class="edd-form-group__label"><?php esc_html_e( 'Recurring', 'edd-recurring' ); ?></label>
			<div class="edd-form-group__control">
				<select name="edd_custom_recurring" id="edd_custom_recurring">
					<option value="no" <?php selected( $recurring, false ); ?>><?php esc_attr_e( 'No', 'edd-recurring' ); ?></option>
					<option value="yes" <?php selected( $recurring, true ); ?>><?php esc_attr_e( 'Yes', 'edd-recurring' ); ?></option>
				</select>
			</div>
		</div>
		<div class="edd-form-group edd-form-row__column">
			<label for="edd_custom_period" class="edd-form-group__label"><?php esc_html_e( 'Period', 'edd-recurring' ); ?></label>
			<div class="edd-form-group__control">
				<select name="edd_custom_period" id="edd_custom_period" class="edd-form-group__input"<?php echo esc_attr( $disabled ); ?>>
					<?php foreach ( $periods as $key => $value ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $period, $key ); ?>><?php echo esc_html( $value ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
		<div class="edd-form-group edd-form-row__column times">
			<label for="edd_custom_times" class="edd-form-group__label"><?php esc_html_e( 'Times', 'edd-recurring' ); ?></label>
			<div class="edd-form-group__control">
				<input type="number" min="0" step="1" name="edd_custom_times" id="edd_custom_times" class="edd-form-group__input small-text" value="<?php echo esc_attr( $times ); ?>"<?php echo esc_attr( $disabled ); ?> />
			</div>
		</div>
		<div class="edd-form-group edd-form-row__column signup_fee">
			<label for="edd_custom_signup_fee" class="edd-form-group__label"><?php esc_html_e( 'Signup Fee', 'edd-recurring' ); ?></label>
			<div class="edd-form-group__control">
				<?php
				if ( 'before' === $currency_position ) {
					?>
					<span class="edd-amount-control__currency is-before"><?php echo esc_html( edd_currency_filter( '' ) ); ?></span>
					<input type="text" name="edd_custom_signup_fee" id="edd_custom_signup_fee" class="edd-form-group__input edd-price-field" value="<?php echo esc_attr( $signup_fee ); ?>"<?php echo esc_attr( $disabled ); ?>/>
					<?php
				} else {
					?>
					<input type="text" name="edd_custom_signup_fee" id="edd_custom_signup_fee" class="edd-form-group__input edd-price-field" value="<?php echo esc_attr( $signup_fee ); ?>"<?php echo esc_attr( $disabled ); ?>/>
					<span class="edd-amount-control__currency is-after"><?php echo esc_html( edd_currency_filter( '' ) ); ?></span>
					<?php
				}
				?>
			</div>
		</div>
	</fieldset><!--close .edd_recurring_custom_wrap-->
	<?php
}
add_action( 'edd_after_price_field', 'edd_recurring_metabox_custom_options', 10 );
