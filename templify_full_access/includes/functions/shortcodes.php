<?php
/**
 * Shortcodes
 *
 * @package     EDD\EDDAllAccess\Functions
 * @since       1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * All Access Shortcodes
 *
 * Adds additional recurring specific shortcodes as well as hooking into existing EDD core shortcodes to add additional subscription functionality
 *
 * @since  1.0.0
 */
class EDD_All_Access_Shortcodes {

	/**
	 * Get things started
	 */
	public function __construct() {

		// Make All Access template files work.
		add_filter( 'edd_template_paths', array( $this, 'add_template_stack' ) );

		// Add the Shortcode [edd_aa_customer_passes].
		add_shortcode( 'edd_aa_customer_passes', array( $this, 'edd_all_access_passes' ) );

		// Add the [edd_aa_all_access] shortcode.
		add_shortcode( 'edd_aa_all_access', array( $this, 'edd_aa_all_access' ) );

		// Add the [edd_aa_no_access_pass] shortcode.
		add_shortcode( 'edd_aa_no_access_pass', array( $this, 'no_all_access' ) );

		// Add the [edd_aa_restrict_content] shortcode.
		add_shortcode( 'edd_aa_restrict_content', array( $this, 'all_access_restrict' ) );

		add_shortcode( 'edd_aa_download_limit', array( $this, 'download_limit' ) );

		// Filter the [downloads] shortcode to only include products the current customer can download using All Access.
		add_filter( 'edd_downloads_query', array( $this, 'all_access_products_only_in_downloads' ), 10, 2 );

		// Allow the all_access_customer_downloads_only attribute to be used in the [downloads] shortcode.
		add_filter( 'shortcode_atts_downloads', array( $this, 'shortcode_atts' ), 10, 3 );

		// Override the [downloads] shortcode output if the user is logged out or doesn't have All Access (and all_access_customer_downloads is in the shortcode atts).
		add_filter( 'downloads_shortcode', array( $this, 'override_downloads_shortcode' ), 10, 11 );

		// Allow the all_access_download_now_text attribute to be used in the [purchase_link] shortcode.
		add_filter( 'shortcode_atts_purchase_link', array( $this, 'edd_purchase_link_shortcode_atts' ), 10, 3 );

	}

	/**
	 * Allow the all_access_customer_downloads_only attribute to be used in the [purchase_link] shortcode.
	 *
	 * @param    array $out The output array of shortcode attributes.
	 * @param    array $pairs The supported attributes and their defaults.
	 * @param    array $atts The user defined shortcode attributes.
	 * @return   array $out The output array of shortcode attributes
	 * @since    1.0.0
	 */
	public function edd_purchase_link_shortcode_atts( $out, $pairs, $atts ) {

		if ( isset( $atts['all_access_download_now_text'] ) ) {
			$out['all_access_download_now_text'] = $atts['all_access_download_now_text'];
		}

		return $out;
	}

	/**
	 * Adds our templates dir to the EDD template stack
	 *
	 * @since    1.0.0
	 * @param    array $paths The paths of all EDD templates.
	 * @return   mixed
	 */
	public function add_template_stack( $paths ) {

		$paths[] = EDD_ALL_ACCESS_DIR . 'templates/';

		return $paths;

	}

	/**
	 * Modify the [downloads] shortcode so that if all_access_customer_downloads_only is added to the shortcode, it removes any products that aren't
	 * covered by this customers All Access Passes.
	 *
	 * @since    1.0.0
	 * @param    string $display The output of the shortcode.
	 * @param    array  $atts The shortcode attributes.
	 * @param    string $buy_button The value of "buy_button" in the $atts.
	 * @param    string $columns The value of "columns" in the $atts.
	 * @param    string $empty An empty string passed by EDD core to the filter.
	 * @param    array  $downloads The array of products being shown.
	 * @param    string $excerpt The value of "excerpt" in the $atts.
	 * @param    string $full_content The value of "full_content" in the $atts.
	 * @param    string $price The value of "price" in the $atts.
	 * @param    string $thumbnails $price The value of "thumbnails" in the $atts.
	 * @param    array  $query The array used to generate the query of product/downloads being shown.
	 * @return   $query
	 */
	public function override_downloads_shortcode( $display, $atts, $buy_button, $columns, $empty, $downloads, $excerpt, $full_content, $price, $thumbnails, $query ) {

		// If all_access_customer_downloads_only isn't set in the atts of the shortcode, don't make any changes.
		if ( ! isset( $atts['all_access_customer_downloads_only'] ) || 'no' === $atts['all_access_customer_downloads_only'] ) {
			return $display;
		}

		$current_user_id = get_current_user_id();

		// If the viewer is not logged in, we know they don't have All Access to anything, show them a login/buy form for the latest All Access Product.
		if ( ! is_user_logged_in() || 0 === $current_user_id ) {

			$default_buy_instructions   = edd_get_option( 'all_access_buy_instructions', __( 'To get access, purchase an All Access Pass here.', 'edd-all-access' ) );
			$default_login_instructions = edd_get_option( 'all_access_login_instructions', __( 'Already purchased?', 'edd-all-access' ) );

			return edd_all_access_buy_or_login_form(
				array(
					'all_access_download_id' => ! empty( $atts['all_access_download_id'] ) ? $atts['all_access_download_id'] : false,
					'all_access_price_id'    => ! empty( $atts['all_access_price_id'] ) ? $atts['all_access_price_id'] : false,
					'all_access_price'       => ! empty( $atts['all_access_price'] ) ? $atts['all_access_price'] : true,
					'all_access_direct'      => ! empty( $atts['all_access_direct'] ) ? $atts['all_access_direct'] : '0',
					'all_access_btn_text'    => ! empty( $atts['all_access_btn_text'] ) ? $atts['all_access_btn_text'] : false,
					'all_access_btn_style'   => ! empty( $atts['all_access_btn_style'] ) ? $atts['all_access_btn_style'] : edd_get_option( 'button_style', 'button' ),
					'all_access_btn_color'   => ! empty( $atts['all_access_btn_color'] ) ? $atts['all_access_btn_color'] : edd_get_option( 'checkout_color', 'blue' ),
					'all_access_btn_class'   => ! empty( $atts['all_access_btn_class'] ) ? $atts['all_access_btn_class'] : 'edd-submit',
					'all_access_form_id'     => ! empty( $atts['all_access_form_id'] ) ? $atts['all_access_form_id'] : '',
					'popup_login'            => ! empty( $atts['popup_login'] ) ? $atts['popup_login'] : true,
					'buy_instructions'       => ! empty( $atts['buy_instructions'] ) ? $atts['buy_instructions'] : $default_buy_instructions,
					'login_instructions'     => ! empty( $atts['login_instructions'] ) ? $atts['login_instructions'] : $default_login_instructions,
					'login_btn_style'        => ! empty( $atts['login_btn_style'] ) ? $atts['login_btn_style'] : 'text',
					'preview_image'          => ! empty( $atts['preview_image'] ) ? $atts['preview_image'] : false,
				)
			);
		}

		// If the viewer is logged in but does not have any All Access Passes.
		$customer = new EDD_Customer( $current_user_id, true );

		// Get the All Access passes saved to this customer meta.
		$customer_all_access_passes = edd_all_access_get_customer_passes( $customer );

		// If this customer has no all access passes, they don't have access for sure.
		if ( empty( $customer_all_access_passes ) ) {

			$default_buy_instructions   = edd_get_option( 'all_access_buy_instructions', __( 'To get access, purchase an All Access Pass here.', 'edd-all-access' ) );
			$default_login_instructions = edd_get_option( 'all_access_login_instructions', __( 'Already purchased?', 'edd-all-access' ) );

			return edd_all_access_buy_or_login_form(
				array(
					'all_access_download_id' => ! empty( $atts['all_access_download_id'] ) ? $atts['all_access_download_id'] : false,
					'all_access_price_id'    => ! empty( $atts['all_access_price_id'] ) ? $atts['all_access_price_id'] : false,
					'all_access_price'       => ! empty( $atts['all_access_price'] ) ? $atts['all_access_price'] : true,
					'all_access_direct'      => ! empty( $atts['all_access_direct'] ) ? $atts['all_access_direct'] : '0',
					'all_access_btn_text'    => ! empty( $atts['all_access_btn_text'] ) ? $atts['all_access_btn_text'] : false,
					'all_access_btn_style'   => ! empty( $atts['all_access_btn_style'] ) ? $atts['all_access_btn_style'] : edd_get_option( 'button_style', 'button' ),
					'all_access_btn_color'   => ! empty( $atts['all_access_btn_color'] ) ? $atts['all_access_btn_color'] : edd_get_option( 'checkout_color', 'blue' ),
					'all_access_btn_class'   => ! empty( $atts['all_access_btn_class'] ) ? $atts['all_access_btn_class'] : 'edd-submit',
					'all_access_form_id'     => ! empty( $atts['all_access_form_id'] ) ? $atts['all_access_form_id'] : '',
					'popup_login'            => ! empty( $atts['popup_login'] ) ? $atts['popup_login'] : true,
					'buy_instructions'       => ! empty( $atts['buy_instructions'] ) ? $atts['buy_instructions'] : $default_buy_instructions,
					'login_instructions'     => ! empty( $atts['login_instructions'] ) ? $atts['login_instructions'] : $default_login_instructions,
					'login_btn_style'        => ! empty( $atts['login_btn_style'] ) ? $atts['login_btn_style'] : 'text',
					'preview_image'          => ! empty( $atts['preview_image'] ) ? $atts['preview_image'] : false,
				)
			);
		}

		// Loop through all of this customer's All Access Passes.
		foreach ( $customer_all_access_passes as $purchased_download_id_price_id => $purchased_aa_data ) {

			// In case there happens to be an entry in the array without a valid key.
			if ( empty( $purchased_aa_data['download_id'] ) ) {
				continue;
			}

			// Set up the All Access Pass object.
			$all_access_pass = edd_all_access_get_pass( $purchased_aa_data['payment_id'], $purchased_aa_data['download_id'], $purchased_aa_data['price_id'] );

			// If this purchased product is an active all access pass.
			if ( 'active' === $all_access_pass->status ) {
				return $display;
			}
		}

		// If we got this far, no passes were active.
		$default_buy_instructions   = edd_get_option( 'all_access_buy_instructions', __( 'To get access, purchase an All Access Pass here.', 'edd-all-access' ) );
		$default_login_instructions = edd_get_option( 'all_access_login_instructions', __( 'Already purchased?', 'edd-all-access' ) );

		return edd_all_access_buy_or_login_form(
			array(
				'all_access_download_id' => ! empty( $atts['all_access_download_id'] ) ? $atts['all_access_download_id'] : false,
				'all_access_price_id'    => ! empty( $atts['all_access_price_id'] ) ? $atts['all_access_price_id'] : false,
				'all_access_price'       => ! empty( $atts['all_access_price'] ) ? $atts['all_access_price'] : true,
				'all_access_direct'      => ! empty( $atts['all_access_direct'] ) ? $atts['all_access_direct'] : '0',
				'all_access_btn_text'    => ! empty( $atts['all_access_btn_text'] ) ? $atts['all_access_btn_text'] : false,
				'all_access_btn_style'   => ! empty( $atts['all_access_btn_style'] ) ? $atts['all_access_btn_style'] : edd_get_option( 'button_style', 'button' ),
				'all_access_btn_color'   => ! empty( $atts['all_access_btn_color'] ) ? $atts['all_access_btn_color'] : edd_get_option( 'checkout_color', 'blue' ),
				'all_access_btn_class'   => ! empty( $atts['all_access_btn_class'] ) ? $atts['all_access_btn_class'] : 'edd-submit',
				'all_access_form_id'     => ! empty( $atts['all_access_form_id'] ) ? $atts['all_access_form_id'] : '',
				'popup_login'            => ! empty( $atts['popup_login'] ) ? $atts['popup_login'] : true,
				'buy_instructions'       => ! empty( $atts['buy_instructions'] ) ? $atts['buy_instructions'] : $default_buy_instructions,
				'login_instructions'     => ! empty( $atts['login_instructions'] ) ? $atts['login_instructions'] : $default_login_instructions,
				'login_btn_style'        => ! empty( $atts['login_btn_style'] ) ? $atts['login_btn_style'] : 'text',
				'preview_image'          => ! empty( $atts['preview_image'] ) ? $atts['preview_image'] : false,
			)
		);

	}

	/**
	 * Modify the [downloads] shortcode so that if all_access_customer_downloads_only is added to the shortcode, it removes any products that aren't
	 * covered by this customers All Access Passes.
	 *
	 * @since    1.0.0
	 * @param    array $query The query that retrieved the products being shown.
	 * @param    array $atts The array that was used to generate the query of products.
	 * @return   $query
	 */
	public function all_access_products_only_in_downloads( $query, $atts ) {

		// If all_access_customer_downloads_only isn't set in the atts of the shortcode, don't make any changes.
		if ( ! isset( $atts['all_access_customer_downloads_only'] ) || 'no' === $atts['all_access_customer_downloads_only'] ) {
			return $query;
		}

		// If the viewer is not logged in, we know they don't have All Access to anything, so show nothing.
		if ( ! is_user_logged_in() || 0 === get_current_user_id() ) {
			return array();
		}

		// Set up the customer object using the currently logged in user.
		$customer = new EDD_Customer( get_current_user_id(), true );

		// Get the All Access passes saved to this customer meta.
		$customer_all_access_passes = edd_all_access_get_customer_passes( $customer );

		// If this customer has no all access passes, they don't have access to any product with All Access so return a blank query.
		if ( empty( $customer_all_access_passes ) ) {

			return array();
		}

		// Rebuild the meta query.
		$query['meta_query'] = $this->update_meta_query( $query );

		// We make sure to only include categories this customer has access to, so lets get all the categories this customer can access.
		$all_included_categories = array();

		// Loop through all of this customer's All Access Passes.
		foreach ( $customer_all_access_passes as $purchased_download_id_price_id => $purchased_aa_data ) {

			// In case there happens to be an entry in the array without a numeric key.
			if ( empty( $purchased_aa_data['download_id'] ) ) {
				continue;
			}

			// Set up the All Access Pass object.
			$all_access_pass = edd_all_access_get_pass( $purchased_aa_data['payment_id'], $purchased_aa_data['download_id'], $purchased_aa_data['price_id'] );

			// If this All Access Pass is not active, skip it.
			if ( 'active' !== $all_access_pass->status ) {
				continue;
			}

			// If any valid All Access Pass includes all categories, we can stop checking everything now. Simply return the query as-is.
			if ( in_array( 'all', $all_access_pass->included_categories, true ) ) {
				return $query;
			}

			// Loop through the included categories in this All Access Pass.
			foreach ( $all_access_pass->included_categories as $included_category_id ) {
				// Add all the included categories to our master list of included categories.
				$all_included_categories[ $included_category_id ] = $included_category_id;
			}
		}

		// If no categories have been set up be included at this point, it's likely no All Access Pass was active. Thus, we'll show nothing.
		if ( empty( $all_included_categories ) ) {
			return array();
		}

		// Now, let's rebuild the tax query so it only includes the right categories.

		// Here we'll remove any tax queries that shouldn't be here - but only if tax queries actually exist.
		if ( ! isset( $query['tax_query'] ) ) {
			$query['tax_query'] = array(); //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			$old_tax_query      = array();
		} else {
			$old_tax_query = $query['tax_query'];
		}

		$all_access_tax_query = array();

		// The All Access query will be "OR" because posts can be in any of the all access categories (EG: this category OR that one).
		$all_access_tax_query['tax_query']['relation'] = 'OR';

		// Now loop through our All Access categories and add them back into the query.
		foreach ( $all_included_categories as $all_access_adjusted_category ) {
			$all_access_tax_query['tax_query'][] = array(
				'taxonomy' => 'download_category',
				'field'    => 'term_id',
				'terms'    => $all_access_adjusted_category,
			);
		}

		// Rebuild the tax query.
		$query['tax_query'] = array(); //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		// First off, we know the tax_query relation must be "AND" so force that right now because the posts MUST be in All Access categories
		// EG: (This query AND that query must match).
		$query['tax_query']['relation'] = 'AND';
		$query['tax_query'][]           = $all_access_tax_query;

		if ( ! empty( $old_tax_query ) ) {
			$query['tax_query'][] = $old_tax_query;
		}

		return $query;
	}

	/**
	 * Gets the updated meta query for the query.
	 *
	 * @since 1.2.5
	 * @param array $original_query
	 * @return array
	 */
	private function update_meta_query( $original_query ) {
		$meta_query = array(
			'relation' => 'AND',
			array(
				'relation' => 'OR',
				array(
					'key'     => '_edd_all_access_enabled',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_edd_product_type',
					'value'   => 'all_access',
					'compare' => '!=',
				),
			),
			array(
				'key'     => '_edd_all_access_exclude',
				'compare' => 'NOT EXISTS',
			),
		);

		if ( ! empty( $original_query['meta_query'] ) ) {
			$meta_query[] = $original_query['meta_query'];
		}

		return $meta_query;
	}

	/**
	 * All Access Passes shortcode callback
	 *
	 * Provides users with the data relating to their All Access passes.
	 *
	 * @since    1.0.0
	 */
	public function edd_all_access_passes() {

		ob_start();
		edd_print_errors();

		// If we are viewing a single All Access Pass's details.
		if ( ! empty( $_GET['action'] ) && 'view_all_access_pass' === $_GET['action'] ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			edd_get_template_part( 'edd-all-access', 'view-single-pass' );
		} else {
			edd_get_template_part( 'shortcode', 'all-access-passes' );
		}

		return ob_get_clean();

	}

	/**
	 * Allow the all_access_customer_downloads_only attribute to be used in the [downloads] shortcode.
	 *
	 * @since    1.0.0
	 * @param    array $out The output array of shortcode attributes.
	 * @param    array $pairs The supported attributes and their defaults.
	 * @param    array $atts The user defined shortcode attributes.
	 * @return   $out
	 */
	public function shortcode_atts( $out, $pairs, $atts ) {

		if ( isset( $atts['all_access_customer_downloads_only'] ) ) {
			$out['all_access_customer_downloads_only'] = $atts['all_access_customer_downloads_only'];
			$out['all_access_download_id']             = isset( $atts['all_access_download_id'] ) ? $atts['all_access_download_id'] : false;
			$out['all_access_price_id']                = isset( $atts['all_access_price_id'] ) ? $atts['all_access_price_id'] : false;
			$out['all_access_price']                   = isset( $atts['all_access_price'] ) ? $atts['all_access_price'] : true;
			$out['all_access_direct']                  = isset( $atts['all_access_direct'] ) ? $atts['all_access_direct'] : false;
			$out['all_access_btn_text']                = isset( $atts['all_access_btn_text'] ) ? $atts['all_access_btn_text'] : false;
			$out['all_access_btn_style']               = isset( $atts['all_access_btn_style'] ) ? $atts['all_access_btn_style'] : false;
			$out['all_access_btn_color']               = isset( $atts['all_access_btn_color'] ) ? $atts['all_access_btn_color'] : false;
			$out['all_access_btn_class']               = isset( $atts['all_access_btn_class'] ) ? $atts['all_access_btn_class'] : false;
			$out['all_access_form_id']                 = isset( $atts['all_access_form_id'] ) ? $atts['all_access_form_id'] : false;
			$out['popup_login']                        = isset( $atts['popup_login'] ) ? $atts['popup_login'] : true;
			$out['buy_instructions']                   = isset( $atts['buy_instructions'] ) ? $atts['buy_instructions'] : false;
			$out['login_instructions']                 = isset( $atts['login_instructions'] ) ? $atts['login_instructions'] : false;
			$out['login_btn_style']                    = isset( $atts['login_btn_style'] ) ? $atts['login_btn_style'] : false;
		} else {
			$out['all_access_customer_downloads_only'] = 'no';
		}

		return $out;
	}

	/**
	 * Shortcode which can be used to easily give a user the option to log in or purchase an All Access Pass.
	 * If the user is already logged in but does not have a valid All Access Pass, they will see a buy button.
	 * If the user is both logged in and has a valid All Access Pass, they will be redirected to the page defined by the shortcode args.
	 * Can also be used to restrict content.
	 *
	 * @since     1.0.0
	 * @param     array  $atts Shortcode attributes.
	 * @param     string $content The content that shoukd be shown if the user has the All Access Pass(s) in question.
	 * @return    string Shortcode Output
	 */
	public function edd_aa_all_access( $atts, $content = null ) {

		global $post;

		$post_id = is_object( $post ) ? $post->ID : 0;

		$atts = shortcode_atts(
			array(
				'id'                   => $post_id,
				'price_id'             => false,
				'sku'                  => '',
				'price'                => true,
				'direct'               => '0',
				'text'                 => '',
				'style'                => edd_get_option( 'button_style', 'button' ),
				'color'                => edd_get_option( 'checkout_color', 'blue' ),
				'class'                => 'edd-submit',
				'form_id'              => '',
				'popup_login'          => true,
				'buy_instructions'     => '',
				'login_instructions'   => '',
				'login_btn_style'      => 'text',
				'preview_image'        => '',
				'success_redirect_url' => '',
				'success_text'         => '',
			),
			$atts,
			'all_access'
		);

		$all_access_download_ids = $this->parse_csv_attribute( $atts['id'] );
		$all_access_price_ids    = $this->parse_csv_attribute( $atts['price_id'] );

		$at_least_one_all_access_pass_is_valid = false;

		foreach ( $all_access_download_ids as $all_access_download_id ) {
			if ( false === $all_access_price_ids ) {
				if ( edd_all_access_user_has_pass( get_current_user_id(), $all_access_download_id, false ) ) {
					$at_least_one_all_access_pass_is_valid = true;
					break;
				}
			}
			foreach ( $all_access_price_ids as $all_access_price_id ) {
				$customer_has_all_access_pass = edd_all_access_user_has_pass( get_current_user_id(), $all_access_download_id, $all_access_price_id );

				if ( $customer_has_all_access_pass ) {
					$at_least_one_all_access_pass_is_valid = true;
					break;
				}
			}
		}

		$preview_area_html   = '';
		$login_purchase_area = '';
		$success_html        = '';

		// If the customer does not have access and a preview image has been provided.
		if ( ! $at_least_one_all_access_pass_is_valid && ! empty( $atts['preview_image'] ) ) {
			$preview_area_html .= '<div class="edd-aa-preview-area"><img class="edd-aa-preview-img" src="' . esc_url( $atts['preview_image'] ) . '" /></div>';
		}

		// If this customer has this All Access Pass and it is valid.
		if ( $at_least_one_all_access_pass_is_valid ) {

			// If success content has been passed in use that.
			if ( ! empty( $content ) ) {
				$success_html .= do_shortcode( $content );
			} else {
				// Set up success text if it exists.
				$success_html .= empty( $atts['success_text'] ) ? __( 'You have an All Access Pass for', 'edd-all-access' ) . ' ' . get_the_title( $atts['id'] ) : $atts['success_text'];
			}

			// Redirect the user if shortcode has it set.
			if ( ! empty( $atts['success_redirect_url'] ) ) {
				// Prevent redirect loops.
				if ( ! isset( $_GET['redirect-from-aa'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
					// Redirect the user to the redirection page provided by the shortcode args.
					$success_html .= '<script type="text/javascript">window.location.replace("' . esc_url( add_query_arg( array( 'redirect-from-aa' => true ), $atts['success_redirect_url'] ) ) . '");</script>';
				}
			}
		} else {

			$all_access_buy_or_login_atts = array(
				'all_access_download_id' => $all_access_download_ids,
				'all_access_price_id'    => $all_access_price_ids,
				'all_access_sku'         => $atts['sku'],
				'all_access_price'       => $atts['price'],
				'all_access_direct'      => $atts['direct'],
				'all_access_btn_text'    => $atts['text'],
				'all_access_btn_style'   => $atts['style'],
				'all_access_btn_color'   => $atts['color'],
				'all_access_btn_class'   => $atts['class'],
				'all_access_form_id'     => $atts['form_id'],
				'class'                  => 'edd-aa-login-purchase-aa-only-mode',
				'popup_login'            => $atts['popup_login'],
				'buy_instructions'       => $atts['buy_instructions'],
				'login_instructions'     => $atts['login_instructions'],
				'login_btn_style'        => $atts['login_btn_style'],
				'preview_image'          => $atts['preview_image'],
			);

			// Customer does not have All Access Pass. Output buy / login form.
			$login_purchase_area = edd_all_access_buy_or_login_form( $all_access_buy_or_login_atts );
		}

		$output_array = apply_filters(
			'edd_all_access_shortcode_outputs',
			array(
				'preview_area'        => $preview_area_html,
				'login_purchase_area' => $login_purchase_area,
				'success_output'      => $success_html,
			),
			$atts
		);

		// Set html output wrapper.
		$html_output = '<div class="edd-aa-wrapper">';

		foreach ( $output_array as $chunk_name => $output_chunk ) {

			// Make sure success_output is only shown if the customer has access.
			if ( 'success_output' === $chunk_name && ! $at_least_one_all_access_pass_is_valid ) {
				continue;
			}

			$html_output .= $output_chunk;
		}

		$html_output .= '</div>';

		return $html_output;

	}

	/**
	 * Simple shortcode which can be used to show content only to people without an All Access Pass.
	 *
	 * @since    1.0.0
	 * @param    array  $atts Shortcode attributes.
	 * @param    string $content The content that should be shown if the user does not have the AA pass in question.
	 * @return   string Shortcode Output
	 */
	public function no_all_access( $atts, $content = null ) {

		$atts = shortcode_atts(
			array(
				'id'       => false,
				'price_id' => false,
			),
			$atts,
			'all_access'
		);

		// If no download id entered, return blank.
		if ( empty( $atts['id'] ) ) {
			return '';
		}

		$all_access_download_ids = $this->parse_csv_attribute( $atts['id'] );
		$all_access_price_ids    = $this->parse_csv_attribute( $atts['price_id'] );

		foreach ( $all_access_download_ids as $all_access_download_id ) {
			if ( false === $all_access_price_ids ) {
				if ( edd_all_access_user_has_pass( get_current_user_id(), $all_access_download_id, false ) ) {
					return '';
				}
			} else {
				foreach ( $all_access_price_ids as $all_access_price_id ) {
					if ( edd_all_access_user_has_pass( get_current_user_id(), $all_access_download_id, $all_access_price_id ) ) {
						return '';
					}
				}
			}
		}

		// If they have no All Access Pass, return the content to show them.
		return do_shortcode( $content );
	}

	/**
	 * Simple shortcode which can be used to show content only to people with an All Access Pass.
	 *
	 * @since   1.0.0
	 * @param    array  $atts Shortcode attributes.
	 * @param    string $content The content that should be shown if the user does have the AA pass in question.
	 * @return  string Shortcode Output
	 */
	public function all_access_restrict( $atts, $content = null ) {

		$atts = shortcode_atts(
			array(
				'id'       => false,
				'price_id' => false,
			),
			$atts,
			'all_access'
		);

		// If no download id entered, return blank.
		if ( empty( $atts['id'] ) ) {
			return '';
		}

		$all_access_download_ids = $this->parse_csv_attribute( $atts['id'] );
		$all_access_price_ids    = $this->parse_csv_attribute( $atts['price_id'] );

		$at_least_one_all_access_pass_is_valid = false;

		foreach ( $all_access_download_ids as $all_access_download_id ) {
			if ( false === $all_access_price_ids ) {
				if ( edd_all_access_user_has_pass( get_current_user_id(), $all_access_download_id, false ) ) {
					$at_least_one_all_access_pass_is_valid = true;
					break;
				}
			}
			foreach ( $all_access_price_ids as $all_access_price_id ) {
				$customer_has_all_access_pass = edd_all_access_user_has_pass( get_current_user_id(), $all_access_download_id, $all_access_price_id );

				if ( $customer_has_all_access_pass ) {
					$at_least_one_all_access_pass_is_valid = true;
					break;
				}
			}
		}

		// If the customer does not have the All Access Pass, this shortcode has no output.
		return ! $at_least_one_all_access_pass_is_valid ? '' : do_shortcode( $content );
	}

	/**
	 * Registers the edd_aa_download_limit shortcode, to show a customer how many downloads their pass has left.
	 *
	 * @since 1.2
	 * @param array $atts
	 * @return string
	 */
	public function download_limit( $atts ) {
		if ( ! is_user_logged_in() ) {
			return '';
		}
		$atts = shortcode_atts(
			array(
				'download_id' => '',
				/* translators: 1. the product name; 2. the remaining downloads; 3. the download limit */
				'message'     => __( '%1$s has %2$d of %3$d downloads remaining.', 'edd-all-access' ),
				/* translators: 1. when the pass will expire. */
				'expiration'  => __( 'Any remaining downloads expire %1$s.', 'edd-all-access' ),
			),
			$atts,
			'edd_aa_download_limit'
		);

		$customer                   = new EDD_Customer( get_current_user_id(), true );
		$customer_all_access_passes = edd_all_access_get_customer_pass_objects( $customer );

		if ( empty( $customer_all_access_passes ) ) {

			/**
			 * Filter the output if there are no passes.
			 *
			 * @since 1.2
			 * @param string $content                    The message for the customer.
			 * @param array  $customer_all_access_passes The current customer's all access passes.
			 */
			return apply_filters(
				'edd_all_access_download_limit_no_passes',
				sprintf( '<p class="edd-aa-download-limit edd-aa-download-limit-no-passes">%s</p>', __( 'You have not purchased any passes.', 'edd-all-access' ) ),
				$customer_all_access_passes
			);
		}
		$content = array();
		foreach ( $customer_all_access_passes as $all_access_pass ) {
			// If there isn't a download limit on the pass or the pass has expired, don't include it.
			if ( empty( $all_access_pass->download_limit ) || 'expired' === $all_access_pass->status ) {
				continue;
			}
			// Optionally limit the shortcode to a specific download ID.
			if ( ! empty( $atts['download_id'] ) && (int) $all_access_pass->download_id !== (int) $atts['download_id'] ) {
				continue;
			}
			$downloads_used       = (int) $all_access_pass->downloads_used;
			$total_downloads_pass = (int) $all_access_pass->download_limit;
			$remaining_downloads  = (int) round( $total_downloads_pass - $downloads_used );

			$output  = sprintf(
				'<p class="edd-aa-download-limit edd-aa-download-limit-%s-remaining">',
				$remaining_downloads ? 'some' : 'none'
			);
			$output .= sprintf(
				$atts['message'],
				get_the_title( $all_access_pass->download_id ),
				$remaining_downloads,
				$total_downloads_pass
			);

			// Gets either the download reset date, or the expiration date. Defaults to false.
			$expiration_or_reset = 'never' !== $all_access_pass->expiration_time ? $all_access_pass->expiration_time : false;
			if ( 'per_period' !== $all_access_pass->download_limit_time_period ) {
				$expiration_or_reset = $all_access_pass->downloads_used_last_reset + strtotime( '1 ' . edd_all_access_download_limit_time_period_to_string( $all_access_pass->download_limit_time_period ), false );
			}

			if ( $expiration_or_reset ) {
				$output .= ' ' . sprintf(
					$atts['expiration'],
					edd_all_access_visible_date(
						get_option( 'date_format' ),
						$expiration_or_reset
					)
				);
			}
			$output .= '</p>';

			/**
			 * Filter the output for the pass.
			 *
			 * @since 1.2
			 * @param string $output          The message for the customer.
			 * @param array  $all_access_pass The current pass.
			 */
			$content[] = apply_filters(
				'edd_all_access_download_limit_single_pass',
				$output,
				$all_access_pass
			);
		}

		// If no passes exist with limited downloads, return an empty string.
		if ( empty( $content ) ) {
			return '';
		}

		return do_shortcode( implode( ' ', $content ) );
	}

	/**
	 * Helper function to maybe convert an attribute string, specifically a list
	 * separated by commas, to an array and strip out white spaces.
	 *
	 * @since 1.2.5
	 * @param false|string $attribute
	 * @return false|array
	 */
	private function parse_csv_attribute( $attribute ) {
		if ( false === $attribute ) {
			return false;
		}

		return explode( ',', preg_replace( '/\s+/', '', $attribute ) );
	}
}
new EDD_All_Access_Shortcodes();
