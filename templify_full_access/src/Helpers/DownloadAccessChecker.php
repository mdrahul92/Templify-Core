<?php
/**
 * DownloadAccessChecker.php
 *
 * @package   edd-all-access
 * @copyright Copyright (c) 2021, Easy Digital Downloads
 * @license   GPL2+
 * @since     1.2
 */

namespace EDD\AllAccess\Helpers;

use EDD\AllAccess\Exceptions\AccessException;

class DownloadAccessChecker {

	/**
	 * @var \EDD_Customer Customer being checked.
	 */
	public $customer;

	/**
	 * @var int Product we're checking access to.
	 */
	protected $download_id;

	/**
	 * @var int|null Price ID to check.
	 */
	protected $price_id;

	/**
	 * @var bool Whether to check if the pass has exceeded its download limit. This should be set to `true`
	 *           when seeing if the user is allowed to _download_ the particular product.
	 */
	public $check_download_limit = false;

	/**
	 * @var int|null Pass a download ID here if you want to check access _through_ a specific pass product
	 *               ID. Otherwise, by default, we check if they have access via _any_ of their purchased
	 *               passes.
	 */
	public $aa_product_id = null;

	/**
	 * Constructor.
	 *
	 * @since 1.2
	 *
	 * @param \EDD_Customer $customer
	 * @param int           $download_id
	 * @param int|null      $price_id
	 */
	public function __construct( \EDD_Customer $customer, $download_id, $price_id = null ) {
		$this->customer    = $customer;
		$this->download_id = $download_id;
		$this->price_id    = $price_id;
	}

	/**
	 * Checks whether or not the customer has a pass that gives them access to
	 * the specified download + price ID.
	 *
	 * If the customer does have access, the "winning" All Access Pass object
	 * will be returned.
	 *
	 * If the customer does not have access, an AccessException will be thrown.
	 *
	 * @since 1.2
	 *
	 * @return \EDD_All_Access_Pass Pass that ultimately granted them access.
	 * @throws AccessException
	 */
	public function check() {
		if ( get_post_meta( $this->download_id, '_edd_all_access_exclude', true ) ) {
			throw new AccessException(
				'product_is_excluded',
				__( 'The product you are attempting to access is excluded from All Access.', 'edd-all-access' ),
				400
			);
		}

		// Get the All Access passes saved to this customer meta.
		$customer_all_access_passes = edd_all_access_get_customer_passes( $this->customer );

		// If this customer has no all access passes, they don't have access for sure.
		if ( empty( $customer_all_access_passes ) ) {
			throw new AccessException(
				'no_all_access_passes_purchased',
				__( 'You have not purchased any All Access Passes.', 'edd-all-access' ),
				403
			);
		}

		// Save the first error we encounter.
		$exception = null;

		foreach ( $customer_all_access_passes as $purchased_download_id_price_id => $purchased_aa_data ) {
			// In case there happens to be an entry in the array without a numeric key.
			if ( empty( $purchased_download_id_price_id ) ) {
				continue;
			}

			// You must have at least a Payment ID and a Download ID for an AA pass to be valid.
			if ( empty( $purchased_aa_data['payment_id'] ) || empty( $purchased_aa_data['download_id'] ) || ! isset( $purchased_aa_data['price_id'] ) ) {
				continue;
			}

			$all_access_pass = edd_all_access_get_pass( $purchased_aa_data['payment_id'], $purchased_aa_data['download_id'], $purchased_aa_data['price_id'] );

			// This All Access Pass is not valid for use.
			if ( 'invalid' === $all_access_pass->status || 'upcoming' === $all_access_pass->status || is_wp_error( $all_access_pass->status ) ) {
				continue;
			}

			try {
				// Bail as soon as we get a valid pass that grants access.
				if ( $this->isPassValid( $all_access_pass ) ) {
					return $all_access_pass;
				}
			} catch ( AccessException $e ) {
				// Otherwise, save the error for potential future use.
				if ( is_null( $exception ) ) {
					$exception = $e;
				}
			}
		}

		/*
		 * If we're at this point, it means we haven't found any valid passes.
		 * If we have a saved exception, throw that. Otherwise we'll have
		 * to manually make a new one.
		 */
		if ( $exception instanceof AccessException ) {
			throw $exception;
		} else {
			throw new AccessException(
				'failure_by_default',
				__( 'For some unknown reason, this user does not have All Access to this product.', 'edd-all-access' )
			);
		}
	}

	/**
	 * Checks an individual pass to see if it grants access to the specified download + price ID.
	 *
	 * @since 1.2
	 *
	 * @param \EDD_All_Access_Pass $all_access_pass
	 *
	 * @return bool
	 * @throws AccessException
	 */
	private function isPassValid( \EDD_All_Access_Pass $all_access_pass ) {
		$this->validatePassStatus( $all_access_pass );

		// Check the download limit, if requested.
		if ( $this->check_download_limit ) {
			$is_at_limit = $all_access_pass->downloads_used >= $all_access_pass->download_limit;

			// If we are in the middle of trying to download a product, we need to check the specific file being downloaded.
			if ( edd_all_access_allow_redownload() ) {
				$product_id     = isset( $_GET['edd-all-access-download'] ) ? absint( $_GET['edd-all-access-download'] ) : false;
				$file_id        = isset( $_GET['edd-all-access-file-id'] ) ? absint( $_GET['edd-all-access-file-id'] ) : false;
				$has_downloaded = edd_all_access_pass_has_downloaded_file( $all_access_pass, $product_id, $file_id );

				if ( $has_downloaded ) {
					$is_at_limit = false;
					add_filter( 'edd_all_access_download_should_be_counted', '__return_false' );
				}
			}

			if ( $is_at_limit && 0 !== intval( $all_access_pass->download_limit ) ) {
				throw new AccessException(
					'download_limit_reached',
					edd_get_option( 'all_access_download_limit_reached_text', __( 'Sorry. You\'ve hit the maximum number of downloads allowed for your All Access account.', 'edd-all-access' ) ) . ' (' . edd_all_access_download_limit_string( $all_access_pass ) . ')',
					403,
					$all_access_pass
				);
			}
		}

		if ( ! $this->passIncludesDownloadCategory( $all_access_pass ) ) {
			throw new AccessException(
				'category_not_included',
				edd_get_option( 'all_access_category_not_included_text', __( 'Your account does not have access to products in this category.', 'edd-all-access' ) ),
				403,
				$all_access_pass
			);
		}

		$this->validatePassPriceId( $all_access_pass );

		return true;
	}

	/**
	 * Validates the status of the pass. It must be `active`.
	 *
	 * @since 1.2
	 *
	 * @param \EDD_All_Access_Pass $all_access_pass
	 *
	 * @throws AccessException
	 */
	private function validatePassStatus( \EDD_All_Access_Pass $all_access_pass ) {
		if ( 'expired' === $all_access_pass->status ) {
			// Run the expiration method to make sure everything is properly set for this expired All Access Pass.
			$all_access_pass->maybe_expire();

			throw new AccessException(
				'all_access_pass_expired',
				edd_get_option( 'all_access_expired_text', __( 'Your All Access Pass is expired.', 'edd-all-access' ) ),
				403,
				$all_access_pass
			);
		}

		if ( 'upgraded' === $all_access_pass->status ) {
			throw new AccessException(
				'all_access_pass_upgraded',
				edd_get_option( 'all_access_upgraded_text', __( 'This All Access Pass was upgraded to another one.', 'edd-all-access' ) ),
				403,
				$all_access_pass
			);
		}

		if ( 'renewed' === $all_access_pass->status ) {
			throw new AccessException(
				'all_access_pass_renewed',
				edd_get_option( 'all_access_renewed_text', __( 'This All Access Pass was renewed. It is no longer active but the newest one is.', 'edd-all-access' ) ),
				403,
				$all_access_pass
			);
		}

		if ( 'active' !== $all_access_pass->status ) {
			throw new AccessException(
				'all_access_pass_not_active',
				__( 'Your All Access Pass is not active.', 'edd-all-access' ),
				403,
				$all_access_pass
			);
		}

		/**
		 * Allow additional status validation checks.
		 *
		 * @since 1.2.5
		 * @param \EDD_All_Access_Pass $all_access_pass
		 */
		do_action( 'edd_all_access_pass_status_validated', $all_access_pass );
	}

	/**
	 * Determines whether the download is in a category that the pass grants access to.
	 *
	 * @since 1.2
	 *
	 * @param \EDD_All_Access_Pass $all_access_pass
	 *
	 * @return bool
	 */
	private function passIncludesDownloadCategory( \EDD_All_Access_Pass $all_access_pass ) {
		// Pass grants access to all categories.
		if ( in_array( 'all', $all_access_pass->included_categories, true ) ) {
			return true;
		}

		$download_category_ids = array_map( 'intval', wp_get_post_terms( $this->download_id, 'download_category', [ 'fields' => 'ids' ] ) );
		$pass_category_ids     = array_map( 'intval', $all_access_pass->included_categories );

		// If there's at least one category in common, we're good to go!
		if ( count( array_intersect( $download_category_ids, $pass_category_ids ) ) > 0 ) {
			return true;
		}

		// If a download is in a child category of a pass category, allow access.
		foreach ( $download_category_ids as $term_id ) {
			$parent_category_ids = array_map( 'intval', get_ancestors( $term_id, 'download_category', 'taxonomy' ) );

			if ( count( array_intersect( $parent_category_ids, $pass_category_ids ) ) > 0 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Confirms that the pass grants access to the specified price  ID.
	 *
	 * @since 1.2
	 *
	 * @param \EDD_All_Access_Pass $all_access_pass
	 *
	 * @throws AccessException
	 */
	private function validatePassPriceId( \EDD_All_Access_Pass $all_access_pass ) {
		/*
		 * Pass includes specific price IDs, and this one is not it.
		 */
		if ( ! empty( $all_access_pass->number_of_price_ids ) && is_numeric( $this->price_id ) && $all_access_pass->included_price_ids && ! in_array( intval( $this->price_id ), $all_access_pass->included_price_ids, true ) ) {
			throw new AccessException(
				'price_id_not_included',
				edd_get_option( 'all_access_price_id_not_included_text', __( 'Your account does not have access to this product variation.', 'edd-all-access' ) ),
				403,
				$all_access_pass
			);
		}

		$variable_prices = edd_get_variable_prices( $this->download_id );

		// If this price id is excluded from all access within the actual post's settings.
		if ( is_numeric( $this->price_id ) && ! empty( $variable_prices[ $this->price_id ]['excluded_price'] ) ) {
			throw new AccessException(
				'price_id_not_included',
				edd_get_option( 'all_access_price_id_not_included_text', __( 'Your account does not have access to this product variation.', 'edd-all-access' ) ),
				403,
				$all_access_pass
			);
		}

		// If the caller of this function requires that the AA pass product ID matches a specific value, make sure it does.
		if ( $this->aa_product_id && absint( $all_access_pass->download_id ) !== absint( $this->aa_product_id ) ) {
			throw new AccessException(
				'aa_download_must_match',
				__( 'Pass Download ID did not match the ID required by the function caller.', 'edd-all-access' ),
				403,
				$all_access_pass
			);
		}

		// If we're at this point, then the price ID is not excluded in any way. Success!
	}

}
