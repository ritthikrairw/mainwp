<?php

/**
 * Class MainWP_ITSEC_Lib_Password_Requirements
 */
class MainWP_ITSEC_Lib_Password_Requirements {

	/** @var array[] */
	private static $requirements;

	/**
	 * Get all registered password requirements.
	 *
	 * @return array
	 */
	public static function get_registered() {
		if ( null === self::$requirements ) {
			self::$requirements = array();

			/**
			 * Fires when password requirements should be registered.
			 */
			do_action( 'mainwp_itsec_register_password_requirements' );
		}

		return self::$requirements;
	}

	/**
	 * Register a password requirement.
	 *
	 * @param string $reason_code
	 * @param array  $opts
	 */
	public static function register( $reason_code, $opts ) {
		$merged = wp_parse_args( $opts, array(
			'defaults'                => null,
			'settings_config'         => null, // Callable returning label, description, render & sanitize callbacks.
			'meta'                    => "_itsec_password_evaluation_{$reason_code}",
			'evaluate_if_not_enabled' => false,
		) );

		if (
			( array_key_exists( 'validate', $opts ) || array_key_exists( 'evaluate', $opts ) ) &&
			( ! is_callable( $merged['validate'] ) || ! is_callable( $merged['evaluate'] ) )
		) {
			return;
		}

		if ( array_key_exists( 'flag_check', $opts ) && ! is_callable( $merged['flag_check'] ) ) {
			return;
		}

		if ( array_key_exists( 'defaults', $opts ) ) {
			if ( ! is_array( $merged['defaults'] ) ) {
				return;
			}

			if ( ! array_key_exists( 'settings_config', $opts ) ) {
				return;
			}
		}

		if ( array_key_exists( 'settings_config', $opts ) && ! is_callable( $merged['settings_config'] ) ) {
			return;
		}

		self::$requirements[ $reason_code ] = $merged;
	}


	/**
	 * Validate a user's password.
	 *
	 * @param WP_User|stdClass|int $user
	 * @param string               $new_password
	 * @param array                $args
	 *
	 * @return WP_Error Error object with new errors.
	 */
	public static function validate_password( $user, $new_password, $args = array() ) {

		$args = wp_parse_args( $args, array(
			'error'   => new WP_Error(),
			'context' => '',
		) );

		/** @var WP_Error $error */
		$error = $args['error'];
		$user  = $user instanceof stdClass ? $user : ITSEC_Lib::get_user( $user );

		if ( ! $user ) {
			$error->add( 'invalid_user', esc_html__( 'Invalid User', 'l10n-mainwp-ithemes-security-extension' ) );

			return $error;
		}

		if ( ! empty( $user->ID ) && wp_check_password( $new_password, get_userdata( $user->ID )->user_pass, $user->ID ) ) {
			$message = wp_kses( __( '<strong>ERROR</strong>: The password you have chosen appears to have been used before. You must choose a new password.', 'l10n-mainwp-ithemes-security-extension' ), array( 'strong' => array() ) );
			$error->add( 'pass', $message );

			return $error;
		}

		require_once( ITSEC_Core::get_core_dir() . '/lib/class-itsec-lib-canonical-roles.php' );

		if ( isset( $args['role'] ) && $user instanceof WP_User ) {
			$canonical = ITSEC_Lib_Canonical_Roles::get_canonical_role_from_role_and_user( $args['role'], $user );
		} elseif ( isset( $args['role'] ) ) {
			$canonical = ITSEC_Lib_Canonical_Roles::get_canonical_role_from_role( $args['role'] );
		} elseif ( empty( $user->ID ) || ! is_numeric( $user->ID ) ) {
			$canonical = ITSEC_Lib_Canonical_Roles::get_canonical_role_from_role( get_option( 'default_role', 'subscriber' ) );
		} else {
			$canonical = ITSEC_Lib_Canonical_Roles::get_user_role( $user );
		}

		$args['canonical'] = $canonical;

		/**
		 * Fires when modules should validate a password according to their rules.
		 *
		 * @since 3.9.0
		 *
		 * @param \WP_Error         $error
		 * @param \WP_User|stdClass $user
		 * @param string            $new_password
		 * @param array             $args
		 */
		do_action( 'itsec_validate_password', $error, $user, $new_password, $args );

		return $error;
	}




}