<?php
/**
 * MainWP AUM Settings Base
 *
 * @package MainWP/Extensions/AUM
 */

namespace MainWP\Extensions\AUM;

defined( 'ABSPATH' ) || exit;

/**
 * MainWP_AUM_Settings_Base class
 */
abstract class MainWP_AUM_Settings_Base {

	/**
	 * The plugin ID. Used for option names.
	 *
	 * @var string
	 */
	public $setting_id_prefix = 'mainwp_aum_api_';

	/**
	 * Setting values.
	 *
	 * @var array
	 */
	public $settings = null;

	/**
	 * Return the settings id.
	 *
	 * @return string
	 */
	abstract protected static function get_settings_id();

	/**
	 * Return the name of the option in the WP DB.
	 *
	 * @return string
	 */
	protected function get_option_key() {
		return $this->setting_id_prefix . static::get_settings_id() . '_settings';
	}

	/**
	 * Initialise Settings.
	 *
	 * Store all settings in a single database entry
	 * and make sure the $settings array is either the default
	 * or the settings stored in the database.
	 */
	public function init_settings() {
		$this->settings = get_option( $this->get_option_key(), null );
		// If there are no settings defined, use defaults.
		if ( ! is_array( $this->settings ) ) {
			$this->settings = array();
		}
		return $this->settings;
	}


	/**
	 * Update options.
	 *
	 * @param mixed $settings Value to set.
	 * @return bool was anything saved?
	 */
	public function update_options( $settings ) {
		if ( empty( $this->settings ) ) {
			$this->init_settings();
		}

		if ( ! is_array( $settings ) ) {
			return $this->settings;
		}

		foreach ( $settings as $key => $val ) {
			$this->settings[ $key ] = $val;
		}

		return update_option( $this->get_option_key(), $this->settings, 'yes' );
	}

	/**
	 * Update option field.
	 *
	 * @param string $key Option key.
	 * @param mixed  $value Value to set.
	 * @return bool was anything saved?
	 */
	public function update_option_field( $key, $value = '' ) {
		if ( empty( $this->settings ) ) {
			$this->init_settings();
		}

		$this->settings[ $key ] = $value;

		return update_option( $this->get_option_key(), $this->settings, 'yes' );
	}

	/**
	 * Get option value.
	 *
	 * Gets an option value from the settings API.
	 *
	 * @param  string $key Option key.
	 * @param  mixed  $default_value Default value.
	 * @return string The value specified for the option or a default value for the option.
	 */
	public function get_option( $key, $default_value = null ) {
		if ( null === $this->settings ) {
			$this->init_settings();
		}

		// Get option default if unset.
		if ( ! isset( $this->settings[ $key ] ) ) {
			$this->settings[ $key ] = '';
		}

		if ( null !== $default_value && '' === $this->settings[ $key ] ) {
			$this->settings[ $key ] = $default_value;
		}

		return $this->settings[ $key ];
	}

}

