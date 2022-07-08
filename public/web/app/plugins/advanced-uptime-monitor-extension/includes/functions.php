<?php
/**
 * MainWP Base Functions.
 *
 * Grab MainWP Directory and check for permissions.
 *
 * @package MainWP/Extensions/AUM
 */


if ( ! function_exists( 'mainwp_aum_flash' ) ) {

	/**
	 * Detects permission level & display message to end user.
	 *
	 * @return object MainWP_AUM_Flash object
	 */
	function mainwp_aum_flash() {
		return MainWP\Extensions\AUM\MainWP_AUM_Flash::instance();
	}
}
