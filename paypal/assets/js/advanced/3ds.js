/**
 * Whether or not a card was enrolled in 3D secure and can be authorized.
 * This does not mean it *was* authorized, just that it was checked.
 *
 * @param payload
 * @returns {boolean}
 */
export function isThreeDSecureAuthenticated( payload ) {
	return [ 'NO', 'POSSIBLE' ].includes( payload.liabilityShift );
}

/**
 * Whether or not liability has been shifted after successful authentication.
 *
 * @param payload
 * @returns {*|boolean}
 */
export function hasLiabilityShifted( payload ) {
	return payload.liabilityShifted && 'POSSIBLE' === payload.liabilityShift;
}
