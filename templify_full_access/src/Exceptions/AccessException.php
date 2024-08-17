<?php
/**
 * AccessException.php
 *
 * @package   edd-all-access
 * @copyright Copyright (c) 2021, Easy Digital Downloads
 * @license   GPL2+
 * @since     1.2
 */

namespace EDD\AllAccess\Exceptions;

class AccessException extends \Exception {

	/**
	 * Failure reason ID. (For internal use.)
	 *
	 * @var string
	 */
	protected $failureId;

	/**
	 * The pass that triggered the error (if available).
	 *
	 * @var \EDD_All_Access_Pass|null
	 */
	protected $pass;

	/**
	 * Constructor.
	 *
	 * @param string                    $failureId Failure reason ID.
	 * @param string                    $message   Failure message for display.
	 * @param int                       $code      Error code.
	 * @param null|\EDD_All_Access_Pass $pass      Pass that triggered the error, if available.
	 * @param null|\Throwable           $previous  Previous exception.
	 */
	public function __construct( $failureId, $message = "", $code = 403, $pass = null, $previous = null ) {
		$this->failureId = $failureId;
		$this->pass      = $pass;

		parent::__construct( $message, $code, $previous );
	}

	/**
	 * Returns the failure ID.
	 *
	 * @since 1.2
	 *
	 * @return string
	 */
	public function getFailureId() {
		return $this->failureId;
	}

	/**
	 * Returns the pass that triggered the error.
	 *
	 * @since 1.2
	 *
	 * @return \EDD_All_Access_Pass|null
	 */
	public function getPass() {
		return $this->pass;
	}

}
