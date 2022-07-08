<?php

class MainWP_Wordfence_Config_Site extends MainWP_Wordfence_Config { // aka wfConfig.

	public static $option    = array();
	public static $override  = 0;
	public static $site_id   = 0;
	public static $cacheType = '';
	public static $apiKey    = '';
	public static $isPaid    = 0;

	public function __construct( $site_id = null ) {
		if ( $site_id ) {
			self::$site_id = $site_id;
			$settings      = MainWP_Wordfence_DB::get_instance()->get_setting_by( 'site_id', $site_id );
			if ( $settings ) {
				self::$option    = unserialize( $settings->settings );
				self::$override  = $settings->override;
				self::$cacheType = $settings->cacheType;
				self::$apiKey    = $settings->apiKey;
				self::$isPaid    = $settings->isPaid;
			}
		}
		if ( ! is_array( self::$option ) || empty( self::$option ) ) {
			$genetal_option = get_option( MainWP_Wordfence_Config::$option_handle, false );
			if ( ! empty( $genetal_option ) ) {
				self::$option = $genetal_option;
			} else {
				self::setDefaults();
			}
		}
	}

	public static function load( $site_id = null ) {

		if ( empty( self::$option ) ) {
			if ( $site_id ) {
				self::$site_id = $site_id;
				$settings      = MainWP_Wordfence_DB::get_instance()->get_setting_by( 'site_id', $site_id );
				if ( $settings ) {
					self::$option    = unserialize( $settings->settings );
					self::$override  = $settings->override;
					self::$cacheType = $settings->cacheType;
					self::$apiKey    = $settings->apiKey;
					self::$isPaid    = $settings->isPaid;
				}
			}
			if ( ! is_array( self::$option ) || empty( self::$option ) ) {
				$genetal_option = get_option( MainWP_Wordfence_Config::$option_handle, false );
				if ( ! empty( $genetal_option ) ) {
					self::$option = $genetal_option;
				} else {
					self::setDefaults();
				}
			}
		}

		return self::$option;
	}

	public static function set( $key, $value, $site_id = 0 ) {
		if ( $site_id && empty( self::$option ) ) {
			self::load( $site_id );
		}
		self::$option[ $key ] = $value;
		self::save_settings();
	}

	public static function get( $key = null, $default = false, $site_id = 0 ) {
		if ( empty( self::$option ) ) {
			self::load( $site_id );
		}

		if ( 'isPaid' == $key ) {
			return self::$isPaid;
		} elseif ( 'apiKey' == $key ) {
			return self::$apiKey;
		} elseif ( isset( self::$option[ $key ] ) ) {
			return self::$option[ $key ];
		}

		return $default;
	}

	public function getVal( $key ) {
		return self::get( $key );
	}

	public static function get_ser( $key, $default = false, $site_id = false ) {
		$serialized = self::get( $key, $default, $site_id );
		return unserialize( $serialized );
	}

	public static function set_ser( $key, $val, $site_id = false ) {
		$data = serialize( $val );
		return self::set( $key, $data, $site_id );
	}

	public function is_override() {
		return self::$override ? true : false;
	}

	public function get_cacheType() {
		return self::$cacheType;
	}

	public function get_AlertEmails() {
		return self::getAlertEmails();
	}

	public static function load_settings() {
		return self::$option;
	}

	public static function save_settings() {
		MainWP_Wordfence_DB::get_instance()->update_setting(
			array(
				'site_id'  => self::$site_id,
				'settings' => serialize( self::$option ),
			)
		);
	}

	public static function setDefaults() {
		foreach ( MainWP_Wordfence_Config::$defaultConfig['checkboxes'] as $key => $config ) {
			$val = $config['value'];
			// $autoload = $config['autoload'];
			if ( in_array( $key, MainWP_Wordfence_Config::$options_filter ) ) {
				if ( self::get( $key ) === false ) {
					self::set( $key, $val ? '1' : '0' );
				}
			}
		}
		foreach ( MainWP_Wordfence_Config::$defaultConfig['otherParams'] as $key => $val ) {
			if ( in_array( $key, MainWP_Wordfence_Config::$options_filter ) ) {
				if ( self::get( $key ) === false ) {
					self::set( $key, $val );
				}
			}
		}

		self::set( 'encKey', substr( MainWP_Wordfence_Utility::big_rando_hex(), 0, 16 ) );
		if ( self::get( 'maxMem', false ) === false ) {
			self::set( 'maxMem', '256' );
		}
		if ( self::get( 'other_scanOutside', false ) === false ) {
			self::set( 'other_scanOutside', 0 );
		}

	}

	public static function getHTML( $key ) {
		// return htmlspecialchars( self::get( $key ) );
		return esc_html( self::get( $key ) );
	}

	public static function inc( $key ) {
		$val = self::get( $key, false );
		if ( ! $val ) {
			$val = 0;
		}
		self::set( $key, $val + 1 );
	}

	public static function f( $key ) {
		echo esc_attr( self::get( $key ) );
	}

	public static function p() {
		return self::get( 'isPaid' );
	}

	public static function cbp( $key, $isPaid = false ) {
		if ( $isPaid && self::get( $key ) ) {
			echo ' checked ';
		}
	}

	public static function cb( $key ) {
		if ( self::get( $key ) ) {
			echo ' checked ';
		}
	}

	public static function sel( $key, $val, $isDefault = false ) {
		if ( ( ! self::get( $key ) ) && $isDefault ) {
			echo ' selected ';
		}
		if ( self::get( $key ) == $val ) {
			echo ' selected ';
		}
	}

	public static function haveAlertEmails() {
		$emails = self::getAlertEmails();

		return sizeof( $emails ) > 0 ? true : false;
	}

	public static function getAlertEmails() {
		$dat    = explode( ',', self::get( 'alertEmails' ) );
		$emails = array();
		foreach ( $dat as $email ) {
			if ( preg_match( '/\@/', $email ) ) {
				$emails[] = trim( $email );
			}
		}

		return $emails;
	}

	public static function getAlertLevel() {
		if ( self::get( 'alertOn_warnings' ) ) {
			return 2;
		} elseif ( self::get( 'alertOn_critical' ) ) {
			return 1;
		} else {
			return 0;
		}
	}

	public static function liveTrafficEnabled( $cacheType = null ) {
		if ( ( ! self::get( 'liveTrafficEnabled' ) ) || 'falcon' == $cacheType || 'php' == $cacheType ) {
			return false;
		}

		return true;
	}

}

