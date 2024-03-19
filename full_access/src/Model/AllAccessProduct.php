<?php
/**
 * All Access Product
 *
 * This class is used for parsing settings from an All Access-enabled product and putting them
 * in an easy-to-use class. This ensures data remains consistent, especially default values.
 *
 * @package   edd-all-access
 * @copyright Copyright (c) 2022, Easy Digital Downloads
 * @license   GPL2+
 * @since     1.2
 */

namespace EDD\AllAccess\Models;

class AllAccessProduct {

	/**
	 * @var int ID of the product.
	 */
	public $id;

	/**
	 * @var int|null Duration of the pass.
	 */
	public $duration = null;

	/**
	 * @var string Duration unit.
	 * @see edd_full_access_get_duration_unit_options()
	 */
	public $duration_unit = 'never';

	/**
	 * @var int Download limit. Will be `0` for unlimited.
	 */
	public $download_limit = 0;

	/**
	 * @var string Download limit period.
	 * @see edd_all_access_get_download_limit_periods()
	 */
	public $download_limit_period = 'per_day';

	/**
	 * @var array|null ID of categories that this product gives access to.
	 *                 If `null` then all categories are granted.
	 */
	public $categories = null;

	/**
	 * @var int Maximum number of price variations there are on a product.
	 *          If `0` then price variations are not counted and
	 *          `$included_price_ids` will be `null`.
	 */
	public $number_price_ids = 0;

	/**
	 * @var array|null Price IDs that this product gives access to.
	 *                 If `null` then all price IDs are granted.
	 */
	public $included_price_ids = null;

	/**
	 * @var bool Whether to output a custom link in purchase receipts.
	 */
	public $show_link_in_receipt = true;

	/**
	 * @var string Message to use for the receipt link.
	 */
	public $receipt_link_message;

	/**
	 * @var string URL to use in the receipt.
	 */
	public $receipt_link_url;

	/**
	 * Constructor
	 *
	 * @param int $productId ID of the product.
	 */
	public function __construct( $productId ) {
		$this->id = $productId;

		$this->parseAndSetSettings( (array) get_post_meta( $this->id, '_edd_all_access_settings', true ) );
		$this->parseAndSetReceiptSettings( (array) get_post_meta( $this->id, '_edd_all_access_receipt_settings', true ) );
	}

	/**
	 * Parses settings out of the big meta array and sets the relevant properties.
	 *
	 * @since 1.2
	 *
	 * @param array $settings
	 */
	private function parseAndSetSettings( array $settings ) {
		// Duration settings.
		if ( ! empty( $settings['all_access_duration_number'] ) ) {
			$this->duration = intval( $settings['all_access_duration_number'] );
		}
		if ( ! empty( $settings['all_access_duration_unit'] ) && array_key_exists( $settings['all_access_duration_unit'], edd_full_access_get_duration_unit_options() ) ) {
			$this->duration_unit = $settings['all_access_duration_unit'];
		}

		// Download limit.
		if ( ! empty( $settings['all_access_download_limit'] ) ) {
			$this->download_limit = absint( $settings['all_access_download_limit'] );
		}
		if ( ! empty( $settings['all_access_download_limit_time_period'] ) && array_key_exists( $settings['all_access_download_limit_time_period'], edd_all_access_get_download_limit_periods() ) ) {
			$this->download_limit_period = $settings['all_access_download_limit_time_period'];
		}

		// Categories.
		if ( ! empty( $settings['all_access_categories'] ) ) {
			$this->categories = $this->arrayOfIntegersOrNull( $settings['all_access_categories'] );
		}

		/**
		 * Price ID settings. The number of price IDs is only checked/updated if some price IDs
		 * have actually been selected.
		 */
		if ( ! empty( $settings['all_access_included_price_ids'] ) ) {
			$this->included_price_ids = $this->arrayOfIntegersOrNull( $settings['all_access_included_price_ids'] );
		}
		if ( ! empty( $this->included_price_ids ) && ! empty( $settings['all_access_number_of_price_ids'] ) ) {
			$this->number_price_ids = absint( $settings['all_access_number_of_price_ids'] );
		}
	}

	/**
	 * Parses receipt settings out of `_edd_all_access_receipt_settings` meta and sets the
	 * relevant properties.
	 *
	 * @since 1.2
	 *
	 * @param array $settings
	 */
	private function parseAndSetReceiptSettings( array $settings ) {
		if ( ! empty( $settings['show_link'] ) && 'hide_link' === $settings['show_link'] ) {
			$this->show_link_in_receipt = false;
		}

		$this->receipt_link_message = isset( $settings['link_message'] ) ? $settings['link_message'] : __( 'Click here to use your All Access Pass', 'edd-all-access' );
		$edd_slug                   = ! defined( 'EDD_SLUG' ) ? 'downloads' : EDD_SLUG;
		$this->receipt_link_url     = isset( $settings['link_url'] ) ? $settings['link_url'] : home_url() . '/' . $edd_slug . '/';
	}

	/**
	 * Accepts an input (probably an array) and returns an array of integers
	 * or `null` if the array ends up empty.
	 *
	 * @since 1.2
	 *
	 * @param array $data
	 *
	 * @return array|null
	 */
	private function arrayOfIntegersOrNull( $data ) {
		if ( ! is_array( $data ) ) {
			$data = array( $data );
		}

		// Strip out any non-numeric values (like "all").
		$data = array_filter( $data, function ( $value ) {
			return is_numeric( $value ) && $value >= 0;
		} );

		if ( empty( $data ) ) {
			return null;
		}

		return array_map( 'intval', array_values( $data ) );
	}

}
