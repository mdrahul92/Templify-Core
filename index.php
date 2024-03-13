<?php
/*
Plugin Name: Templify Core
Description: Templify Core Plugin description.
Version: 1.0
Author: Templify
*/

// Check if Easy Digital Downloads is installed and activated
function templify_core_check_edd() {
    $edd_path = 'easy-digital-downloads/easy-digital-downloads.php';
    $edd_active = in_array($edd_path, (array)get_option('active_plugins', array()));

	return $edd_active || is_plugin_active($edd_path);


    //return array('edd_active' => $edd_active);
}

// Enqueue scripts and styles
function templify_core_enqueue_scripts() {
    // Enqueue your plugin scripts
    wp_enqueue_style('templify-core-style', plugins_url('assets/frontend/css/style.css', __FILE__));
    wp_enqueue_script('templify-core-script', plugins_url('assets/frontend/js/script.js', __FILE__), array('jquery'), '1.0', true);
}
add_action('wp_enqueue_scripts', 'templify_core_enqueue_scripts');


// Enqueue scripts and styles
function templify_core_admin_enqueue_scripts() {
    // Enqueue your plugin scripts
    wp_enqueue_style('templify-core-admin-style', plugins_url('assets/admin/css/style.css', __FILE__));
    wp_enqueue_script('templify-core-admin-script', plugins_url('assets/admin/js/script.js', __FILE__), array('jquery'), '1.0', true);
}
add_action('admin_enqueue_scripts', 'templify_core_admin_enqueue_scripts');

// Activation Hook
register_activation_hook(__FILE__, 'templify_core_activation');

function templify_core_activation() {
    //$result = templify_core_check_edd();
    if (templify_core_check_edd() ) {
        // templify_core_add_edd_full_access();
    } else {
        // Deactivate the plugin
        deactivate_plugins(plugin_basename(__FILE__));
        // Set a transient to show the notice
        set_transient('templify_core_edd_notice', true, 5 * MINUTE_IN_SECONDS);
    }
}




function templify_full_access_register_download_type( $types ) {
	$types['full_access'] = __( 'Full Access', 'templify-full-access' );

	return $types;
}
add_filter( 'edd_download_types', 'templify_full_access_register_download_type' );


// Hook into WordPress initialization action
add_action('init', 'edd_test_init');

function edd_test_init() {
    // Autoload classes
    spl_autoload_register('edd_test_autoloader');
}

function edd_test_autoloader($class_name) {
    // Check if the class belongs to edd-plugin
    if (strpos($class_name, 'EDD_') === 0) {
        // Get path to wp-content/plugins directory
        $plugin_dir = WP_PLUGIN_DIR;

        // Construct the file path
        $file_name = 'class-' . strtolower(str_replace('_', '-', $class_name)) . '.php';
        $file = $plugin_dir . '/easy-digital-downloads/includes/' . $file_name;

        // Check if the file exists
        if (file_exists($file)) {
            require_once $file;
        }
    }
}



require_once plugin_dir_path( __FILE__ ) . '/full_access/functions/helper_function.php';
require_once plugin_dir_path( __FILE__ ) . '/full_access/metabox/full_access_meta_box.php';
require_once plugin_dir_path( __FILE__ ) . '/full_access/metabox/price_meta_box.php';
require_once plugin_dir_path( __FILE__ ) . '/full_access/reports/reports.php';
require_once plugin_dir_path( __FILE__ ) . '/full_access/reports/class-edd-fa-download-popularity-table.php';
require_once plugin_dir_path( __FILE__ ) . '/full_access/functions/shortcodes.php';
require_once plugin_dir_path( __FILE__ ) . '/full_access/functions/settings.php';
require_once plugin_dir_path( __FILE__ ) . '/full_access/functions/discount-codes.php';

require_once plugin_dir_path( __FILE__ ) . '/full_access/customers/customers.php';
function edd_full_access_add_meta_box() {

	if ( current_user_can( 'manage_shop_settings' ) ) {
		add_meta_box( 'edd_downloads_full_access', __( 'Full Access', 'templify-full-access' ), 'edd_all_access_render_full_access_meta_box', 'download', 'normal', 'default' );
	}
}
add_action( 'add_meta_boxes', 'edd_full_access_add_meta_box' );


function edd_all_access_render_full_access_meta_box(){

	global $post;
	
	
	?>
	<input type="hidden" name="edd_download_full_access_meta_box_nonce" value="<?php echo esc_attr( wp_create_nonce( basename( __FILE__ ) ) ); ?>" />
	<table class="form-table">
		<?
$enabled = edd_full_access_enabled_for_download( $post->ID );
	
	?>
	<tr class="edd_full_access_categories_row edd_full_access_row">
		<td class="edd_field_type_text" colspan="2">
			<p>
				<strong><?php echo esc_html( __( 'Full Access To:', 'templify-full-access' ) ); ?>
					<span alt="f223" class="edd-help-tip dashicons dashicons-editor-help" title="<?php echo wp_kses_post( __( '<strong>Limit by category</strong>: You can choose which products customers can download with this Full Access License. For example, if you want to sell an Full Access License to just a category called Photos, choose that category here. Note that the category must already exist for it to show up here. You can make product categories under Downloads > Categories.', 'templify-full-access' ) ); ?>"></span>
				</strong>
			</p>
			<label for="edd_full_access_meta_full_access_categories">
				<?php echo esc_html( __( 'To which product categories does the customer get "Full Access"', 'templify-full-access' ) ); ?>
			</label>
			<br />
			<?php
					$categories = get_terms( 'download_category', apply_filters( 'edd_category_dropdown', array() ) );
					$options    = array(
						'all' => __( 'All Products', 'templify-full-access' ),
					);
	
					foreach ( $categories as $category ) {
						$options[ absint( $category->term_id ) ] = esc_html( $category->name );
					}
	
					echo EDD()->html->select(
						array(
							'options'          => $options,
							'name'             => 'edd_full_access_meta[all_access_categories][]',
							'selected'         => '',
							'id'               => 'edd_full_access_meta_all_access_categories',
							'class'            => 'edd_full_access_meta_all_access_categories',
							'chosen'           => true,
							'placeholder'      => __( 'Type to search Categories', 'templify-full-access' ),
							'multiple'         => true,
							'show_option_all'  => false,
							'show_option_none' => false,
							'data'             => array( 'search-type' => 'no_ajax' ),
						)
					);
					?>
		</td>
	</tr>
	
	
	<tr class="edd_full_access_row">
		<td class="edd_field_type_text" colspan="2">
			<p><strong><?php echo esc_html( __( '"Full Access" Duration:', 'templify-full-access' ) ); ?></strong>
			<span alt="f223" class="edd-help-tip dashicons dashicons-editor-help" title="<?php echo wp_kses_post( __( '<strong>What is Full Access duration?</strong>: You can set an expiration date for this Full Access license. Once a customer\'s Full Access License expires, they can no longer download products using that license. If you want to make this renewable (like an ongoing membership), you will want to use the EDD Recurring extension so that this Full Access License is automatically repurchased by the customer once it expires.', 'templify-full-access' ) ); ?>"></span>
			</p>
			<label for="edd_full_access_meta_full_access_duration_unit"><?php echo esc_html( __( 'How long should "Full Access" last?', 'templify-full-access' ) ); ?></label><br />
			<input
				type="number"
				class="small-text"
				placeholder="1"
				id="edd_full_access_meta_full_access_duration_number"
				name="edd_full_access_meta[full_access_duration_number]"
				value=""
				min="1"
				style="display:none;"
			/>
			<select name="edd_full_access_meta[full_access_duration_unit]" id="edd_full_access_meta_full_access_duration_unit">
			<?php
					foreach ( edd_full_access_get_duration_unit_options() as $time_period_slug => $output_string ) {
						?>
						<option value="<?php echo esc_attr( $time_period_slug ); ?>" ><?php echo esc_html( $output_string ); ?></option>
						<?php
					}
					?>
			</select>
		</td>
	</tr>
	
	<tr class="edd_full_access_row">
			<td class="edd_field_type_text" colspan="2">
				<p><strong><?php esc_html_e( 'Download Limit:', 'templify-full-access' ); ?></strong></p>
				<label for="edd_full_access_download_limit"><?php echo wp_kses_post( __( 'How many downloads should the customer get? Leave blank or enter "0" for unlimited. Note: If a customer\'s account is expired, they won\'t be able to download - even if they have not hit this limit yet.', 'templify-full-access' ) ); ?></label><br />
				<input type="number" class="small-text" name="edd_full_access_meta[download_limit]" id="edd_full_access_download_limit" value="" min="0" />&nbsp;
				<span
					id="edd_full_access_unlimited_download_limit_note"
					
						style="display:none;"
					
				>
				<?php esc_html_e( '(Unlimited downloads per day)', 'templify-full-access' ); ?>
				</span>
				<select
					name="edd_full_access_meta[download_limit_time_period]"
					id="edd_full_access_meta_download_limit_time_period"
	
						style="display:none;"
				
				>
					<?php
					foreach ( edd_full_access_get_download_limit_periods() as $time_period_slug => $output_string ) {
						?>
						<option value="<?php echo esc_attr( $time_period_slug ); ?>" >
							<?php echo esc_html( str_replace( 'X', "", $output_string ) ); ?>
						</option>
						<?php
					}
					?>
				</select>
			</td>
		</tr>
	
	
		<?php
			// Full Access Price Variations - How many?
			?>
			<tr class="edd_full_access_number_of_price_ids_row edd_full_access_row">
				<td class="edd_field_type_text" colspan="2">
					<p><strong><?php echo esc_html( __( 'Total Price Variations (Optional):', 'templify-full-access' ) ); ?></strong></p>
					<label for="edd_full_access_number_of_price_ids"><?php echo esc_html( __( 'How many price variations are there? Leave blank or enter "0" to include all price variations.', 'templify-full-access' ) ); ?></label><br />
					<input type="number" class="small-text" name="edd_full_access_meta[number_of_price_ids]" id="edd_full_access_number_of_price_ids" value="" min="0" />&nbsp;
					<p
						id="edd_full_access_included_price_ids_note"
						<?php if ( empty( $product->download_limit ) ) : ?>
							style="display:none;"
						<?php endif; ?>
					>
						<?php esc_html_e( 'Because this is set to 0, all price variations are included.', 'templify-full-access' ); ?>
					</p>
				</td>
		</tr>
		<?php
			// Full Access Price Variations - Which are included?.
		?>
		<tr style="display:none;"
	
			class="edd_full_access_included_price_ids_row"
		>
			<td class="edd_field_type_text" colspan="2">
				<p><strong><?php echo esc_html( __( 'Included Price Variations:', 'templify-full-access' ) ); ?></strong></p>
				<?php echo esc_html( __( 'Which price variations should be included in this Full Access?', 'templify-full-access' ) ); ?>
				<ul id="edd_full_access_included_price_ids">
					
				</ul>
			</td>
		</tr>
		<?php
		// Full Access Receipt options.
		?>
		<tr class="edd_full_access_row">
			<td class="edd_field_type_text" colspan="2">
				<p><strong><?php echo esc_html( __( 'Receipts: Show link to Full Access?:', 'templify-full-access' ) ); ?></strong></p>
				<label for="edd_full_access_receipt_meta_show_link"><?php echo esc_html( __( 'Would you like to output a custom link in the receipts your customers receive directing them to use their Full Access License? Note: For email Receipts, you must be using the', 'templify-full-access' ) ); ?>
					<a href="http://docs.easydigitaldownloads.com/article/864-email-settings" target="_blank">{download_list}</a>
					<?php echo esc_html( __( 'email tag.', 'templify-full-access' ) ); ?>
				</label><br />
	
				<select name="edd_full_access_receipt_meta[show_link]" id="edd_full_access_receipt_meta_show_link">
					<option value="show_link" ><?php esc_html_e( 'Show link in receipt', 'templify-full-access' ); ?></option>
					<option value="hide_link" ><?php esc_html_e( 'Hide link in receipt', 'templify-full-access' ); ?></option>
				</select>
			<td>
		</tr>
		<?
		// Full Access Receipt Link Message.
		?>
		<tr class="edd_full_access_row">
			<td class="edd_field_type_text" colspan="2">
				<p>
					<strong><?php echo esc_html( __( 'Receipts: Full Access Link Message:', 'templify-full-access' ) ); ?></strong>
				</p>
				<label for="edd_full_access_receipt_meta_link_message"><?php echo esc_html( __( 'What should the link in the receipt say to the user?', 'templify-full-access' ) ); ?></label>
				<p>
					<textarea name="edd_full_access_receipt_meta[link_message]" id="edd_full_access_receipt_meta_link_message" style="width:100%;"></textarea>
				</p>
			<td>
		</tr>
		<?
		// Full Access Receipt Link URL.
		?>
		<tr class="edd_full_access_row">
			<td class="edd_field_type_text" colspan="2">
				<p><strong><?php echo esc_html( __( 'Receipts: Link URL:', 'templify-full-access' ) ); ?></strong></p>
				<label for="edd_full_access_receipt_meta_link_url"><?php echo esc_html( __( 'Which URL should the customer be directed to in the receipt? If you want to build your own custom page, ', 'templify-full-access' ) ); ?>
					<a href="http://docs.easydigitaldownloads.com/article/1829-all-access-creating-all-access-products#creating-a-custom-page-of-products-the-customer-can-download-via-all-access" target="_blank">
						<?php echo esc_html( __( 'learn how in this document.', 'templify-full-access' ) ); ?>
					</a>
				</label>
				<p>
					<input style="width:100%;" type="url" name="edd_full_access_receipt_meta[link_url]" id="edd_full_access_receipt_meta_link_url" value="" />
				</p>
			<td>
		</tr>
						</table>
	<?php
	}
	
	
function register_templify_core_full_access_settings() {
    register_setting('templify_core_full_access_settings_group', 'templify_core_full_access_settings');
}

add_action('admin_init', 'register_templify_core_full_access_settings');


function edd_full_access_update_download_type( $type, $download_id ) {
	if ( 'full_access' === $type ) {
		return $type;
	}

	// If the download doesn't yet have a type, but does have AA settings, it's probably an AA download.
	if ( ( empty( $type ) || 'default' === $type ) && get_post_meta( $download_id, '_edd_full_access_settings', true ) ) {
		// This request will trigger a debugging notice and update the post meta.
		if ( get_post_meta( $download_id, '_edd_full_access_enabled', true ) ) {
			update_post_meta( $download_id, '_edd_product_type', 'full_access' );
			delete_post_meta( $download_id, '_edd_full_access_enabled' );

			return 'full_access';
		}
	}

	return $type;
}
add_filter( 'edd_get_download_type', 'edd_full_access_update_download_type', 20, 2 );



// Admin Notics
add_action('admin_notices', 'templify_core_admin_notices');

function templify_core_admin_notices() {
    // Check if Easy Digital Downloads is installed and activated
    if (!templify_core_check_edd()) {
        // Check if the transient is set
        $show_notice = get_transient('templify_core_edd_notice');

        if ($show_notice) {
            $plugin_name = 'Easy Digital Downloads'; // Change this to the required plugin name

            ?>
            <div class="error">
                <p><?php
                    printf(
                        esc_html__('%s requires %s. Please install and activate it to use %s.', 'templify-core'),
                        'Templify Core',
                        '<strong>' . esc_html($plugin_name) . '</strong>',
                        'Templify Core'
                    );
                ?></p>
                <?
                $plugin_url = admin_url('plugin-install.php?s=' . urlencode(strtolower($plugin_name)) . '&tab=search&type=term');
                printf('<p><a href="%s" class="button button-primary">%s</a></p>', esc_url($plugin_url), esc_html__('Install ' . $plugin_name, 'templify-core'));
                ?>
            </div>
            <?

            // Remove the transient after showing the notice
            delete_transient('templify_core_edd_notice');
        }
    }
}

// Deactivation hook
function templify_core_deactivate() {
    // Deactivation code here
}
register_deactivation_hook(__FILE__, 'templify_core_deactivate');





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





define( 'EDD_PAYPAL_PRO_VERSION', '1.0.3' );
define( 'EDD_PAYPAL_PRO_FILE', __FILE__ .'/paypal' );
define( 'EDD_PAYPAL_PRO_DIR', dirname( EDD_PAYPAL_PRO_FILE ) );
define( 'EDD_PAYPAL_PRO_URL', plugin_dir_url( EDD_PAYPAL_PRO_FILE ) );

define( 'EDD_RECURRING_VERSION', '2.11.11.1' );



require_once plugin_dir_path( __FILE__ ) . '/paypal/upgrades.php';


require_once plugin_dir_path( __FILE__ ) . '/paypal/main.php';
require_once plugin_dir_path( __FILE__ ) . '/paypal/admin/settings.php';
require_once plugin_dir_path( __FILE__ ). '/paypal/checkout.php';
require_once plugin_dir_path( __FILE__ ) . '/paypal/script.php';
	

require_once plugin_dir_path( __FILE__ ) . '/recurring_payment/edd_recurring.php';
require_once plugin_dir_path( __FILE__ ) . '/recurring_payment/functions.php';
require_once plugin_dir_path( __FILE__ ) . '/recurring_payment/helper_functions.php';
require_once plugin_dir_path( __FILE__ ) . '/recurring_payment/admin_settings.php';
require_once plugin_dir_path( __FILE__ ) . '/recurring_payment/scripts.php';
require_once plugin_dir_path( __FILE__ ) . '/recurring_payment/edd_subscriptions_db.php';
require_once plugin_dir_path( __FILE__ ) . '/recurring_payment/edd_recurring_subscriber.php';
require_once plugin_dir_path( __FILE__ ) . '/recurring_payment/includes/admin/class-subscriptions-list-table.php';
require_once plugin_dir_path( __FILE__ ) . '/recurring_payment/customers.php';
