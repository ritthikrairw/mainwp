<?php

class MainWPGADB {
	// Config
	private $mainwp_google_analytics_extension_db_version = '1.8';
	// Private
	private $table_prefix;
	// Singleton
	private static $instance = null;

	/**
	 * @static
	 * @return MainWPGADB
	 */
	static function Instance() {
		if ( self::$instance == null ) {
			self::$instance = new MainWPGADB();
		}
		return self::$instance;
	}

	// Constructor
	function __construct() {
		global $wpdb;
		$this->table_prefix = $wpdb->prefix . 'mainwp_';
	}

	private function tableName( $suffix ) {
		return $this->table_prefix . $suffix;
	}

	// Installs new DB
	function install() {
		$currentVersion = get_site_option( 'mainwp_google_analytics_extension_db_version' );
		if ( $currentVersion == $this->mainwp_google_analytics_extension_db_version ) {
			return;
		}
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = array();

		$tbl = 'CREATE TABLE `' . $this->tableName( 'wp_ga' ) . '` (
  `wpid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `statsValues` mediumtext NOT NULL,
  `statsValuesPrev` mediumtext NOT NULL,
  `graphValues` mediumtext NOT NULL,
  `date` int(11) NOT NULL';
		if ( $currentVersion == '' ) {
			$tbl .= ',
   PRIMARY KEY (wpid,userid)  ';
		}
		$tbl  .= ') ' . $charset_collate;
		$sql[] = $tbl;

		$sql[] = 'CREATE TABLE `' . $this->tableName( 'ga' ) . '` (
  `userid` int(11) NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  `oauth_verifier` text NOT NULL,
  `oauth_verifier_token` text NOT NULL,
  `update_interval` text NOT NULL,
  `refreshrate` text NOT NULL,
  `oauth_token` text NOT NULL,
  `oauth_token_secret` text NOT NULL,
  `token_secret` text NOT NULL,
  `lastupdate` int(11) NOT NULL,
  `availableSites` mediumtext NOT NULL,
  `propertyIds` text NOT NULL,
  `auto_assign` tinyint(1) NOT NULL
        );';

		$tbl = 'CREATE TABLE `' . $this->tableName( 'gas' ) . '` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL,
  `ga_name` text NOT NULL,
  `ga_account_name` text NOT NULL,
  `enabled` tinyint(1) NOT NULL,
  `oauth_verifier` text NOT NULL,
  `oauth_verifier_token` text NOT NULL,
  `oauth_token` text NOT NULL,
  `oauth_token_secret` text NOT NULL,
  `token_secret` text NOT NULL,
  `created` int(11) NOT NULL,
  `client_id` text NOT NULL,
  `client_secret` text NOT NULL,
  `refresh_token` text NOT NULL';
		if ( $currentVersion == '' ) {
			$tbl .= ',
  PRIMARY KEY  (`id`)  ';
		}
		  $tbl .= ');';
		$sql[]  = $tbl;

		$tbl = 'CREATE TABLE `' . $this->tableName( 'wp_ga_gas' ) . '` (
  `wpid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `ga_id` text NOT NULL,
  `gas_id` int(11) NOT NULL,
  `ga_item_id` int(11) NOT NULL';
		if ( $currentVersion === false || $currentVersion == '' ) {
			$tbl .= ',
  PRIMARY KEY  (wpid)  ';
		}
		$tbl  .= ')';
		$sql[] = $tbl;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		foreach ( $sql as $query ) {
			dbDelta( $query );
		}

		update_option( 'mainwp_google_analytics_extension_db_version', $this->mainwp_google_analytics_extension_db_version );

		if ( $currentVersion === false || $currentVersion == '' ) {
			// First startup, copy from possible previous main version
			/** @var $wpdb wpdb */
			global $wpdb;
			$websites = $wpdb->get_results( 'SELECT id,ga_id,gas_id,userid FROM ' . $this->tableName( 'wp' ), OBJECT );
			if ( $websites ) {
				foreach ( $websites as $website ) {
					$wpdb->insert(
						$this->tableName( 'wp_ga_gas' ),
						array(
							'wpid'   => $website->id,
							'ga_id'  => $website->ga_id,
							'gas_id' => $website->gas_id,
							'userid' => $website->userid,
						)
					);
				}
			}
		}
	}

	protected function escape( $data ) {
		/** @var $wpdb wpdb */
		global $wpdb;

		if ( function_exists( 'esc_sql' ) ) {
			return esc_sql( $data );
		} else {
			return $wpdb->escape( $data );
		}
	}


	public function removeGAIds( $gasId, $userId = null ) {
		/** @var $wpdb wpdb */
		global $wpdb;

		if ( empty( $gasId ) ) {
			return false;
		}

		if ( ( $userId == null ) && ( apply_filters( 'mainwp_is_multi_user', false ) == true ) ) {
			global $current_user;
			$userId = $current_user->ID;
		}

		if ( $userId == null ) {
			$wpdb->query( 'UPDATE ' . $this->tableName( 'wp_ga_gas' ) . ' SET ga_id="", gas_id=0 WHERE gas_id = ' . $gasId );
		} else {
			$wpdb->query( 'UPDATE ' . $this->tableName( 'wp_ga_gas' ) . ' SET ga_id="", gas_id=0 WHERE userid=' . $userId . ' AND gas_id = ' . $gasId );
		}
		return true;
	}

	public function getGAGASId( $websiteid ) {
		/** @var $wpdb wpdb */
		global $wpdb;
		return $wpdb->get_row( 'SELECT wpid,ga_id,gas_id,ga_item_id FROM ' . $this->tableName( 'wp_ga_gas' ) . ' WHERE wpid=' . $websiteid, OBJECT );
	}

	public function getGAId( $websiteid ) {
		/** @var $wpdb wpdb */
		global $wpdb;

		return $wpdb->get_var( 'SELECT ga_id FROM ' . $this->tableName( 'wp_ga_gas' ) . ' WHERE wpid=' . $websiteid );
	}

	public function getGASId( $websiteid ) {
		/** @var $wpdb wpdb */
		global $wpdb;

		return $wpdb->get_var( 'SELECT gas_id FROM ' . $this->tableName( 'wp_ga_gas' ) . ' WHERE wpid=' . $websiteid );
	}

	public function getGAItemID( $websiteid ) {
		/** @var $wpdb wpdb */
		global $wpdb;

		return $wpdb->get_var( 'SELECT ga_item_id FROM ' . $this->tableName( 'wp_ga_gas' ) . ' WHERE wpid=' . $websiteid );
	}

	public function updateGAId( $websiteid, $GAid, $gas_id, $ga_item_id, $userId = null ) {
		/** @var $wpdb wpdb */
		global $wpdb;

		if ( ( $userId == null ) && ( apply_filters( 'mainwp_is_multi_user', false ) == true ) ) {
			global $current_user;
			$userId = $current_user->ID;
		}

		if ( MainWPGAUtility::ctype_digit( $websiteid ) ) {
			$var = $wpdb->get_row( 'SELECT * FROM ' . $this->tableName( 'wp_ga_gas' ) . ' WHERE wpid = ' . $websiteid, OBJECT );
			if ( ! $var ) {
				if ( $userId == null ) {
					$wpdb->insert(
						$this->tableName( 'wp_ga_gas' ),
						array(
							'wpid'       => $websiteid,
							'ga_id'      => $this->escape( $GAid ),
							'gas_id'     => $gas_id,
							'ga_item_id' => $ga_item_id,
						)
					);
				} else {
					$wpdb->insert(
						$this->tableName( 'wp_ga_gas' ),
						array(
							'wpid'       => $websiteid,
							'ga_id'      => $this->escape( $GAid ),
							'gas_id'     => $gas_id,
							'ga_item_id' => $ga_item_id,
							'userid'     => $userId,
						)
					);
				}
			}

			if ( $userId == null ) {
				// gas_id = ga account id
				$wpdb->query( 'UPDATE ' . $this->tableName( 'wp_ga_gas' ) . ' SET ga_id="' . $this->escape( $GAid ) . '", gas_id=' . $gas_id . ', ga_item_id=' . $ga_item_id . '  WHERE wpid=' . $websiteid );
				// clear cache
				$wpdb->query( 'DELETE FROM ' . $this->tableName( 'wp_ga' ) . ' WHERE wpid=' . $websiteid );
			} else {
				// gas_id = ga account id
				$wpdb->query( 'UPDATE ' . $this->tableName( 'wp_ga_gas' ) . ' SET ga_id="' . $this->escape( $GAid ) . '", gas_id=' . $gas_id . ', ga_item_id=' . $ga_item_id . ' WHERE userid=' . $userId . ' AND wpid=' . $websiteid );
				// clear cache
				$wpdb->query( 'DELETE FROM ' . $this->tableName( 'wp_ga' ) . ' WHERE wpid=' . $websiteid . ' AND userid=' . $userId );
			}
			return $this->getGAGASId( $websiteid );
		}
		return false;
	}

	public function removeGAId( $websiteid, $userId = null ) {
		/** @var $wpdb wpdb */
		global $wpdb;

		if ( ( $userId == null ) && ( apply_filters( 'mainwp_is_multi_user', false ) == true ) ) {
			global $current_user;
			$userId = $current_user->ID;
		}

		if ( MainWPGAUtility::ctype_digit( $websiteid ) ) {
			if ( $userId == null ) {
				$wpdb->query( 'UPDATE ' . $this->tableName( 'wp_ga_gas' ) . ' SET ga_id="" WHERE wpid=' . $websiteid );
				$wpdb->query( 'DELETE FROM ' . $this->tableName( 'wp_ga' ) . ' WHERE wpid=' . $websiteid );
			} else {
				$wpdb->query( 'UPDATE ' . $this->tableName( 'wp_ga_gas' ) . ' SET ga_id="" WHERE userid=' . $userId . ' AND wpid=' . $websiteid );
				$wpdb->query( 'DELETE FROM ' . $this->tableName( 'wp_ga' ) . ' WHERE wpid=' . $websiteid . ' AND userid=' . $userId );
			}
			return true;
		}
		return false;
	}

	public function getGACache( $websiteid, $userId = null ) {
		/** @var $wpdb wpdb */
		global $wpdb;

		if ( ( $userId == null ) && ( apply_filters( 'mainwp_is_multi_user', false ) == true ) ) {
			global $current_user;
			$userId = $current_user->ID;
		}

		if ( MainWPGAUtility::ctype_digit( $websiteid ) ) {
			$date = mktime( 0, 0, 0, date( 'm' ), date( 'd' ), date( 'Y' ) );
			if ( $userId == null ) {
				return $wpdb->get_row( 'SELECT * FROM ' . $this->tableName( 'wp_ga' ) . ' WHERE wpid = ' . $websiteid . ' AND date >= ' . $date, OBJECT );
			} else {
				return $wpdb->get_row( 'SELECT * FROM ' . $this->tableName( 'wp_ga' ) . ' WHERE wpid = ' . $websiteid . ' AND userid=' . $userId . ' AND date >= ' . $date, OBJECT );
			}
		}
		return null;
	}

	public function updateGACache( $websiteid, $statsValues, $statsValuesPrev, $graphValues, $userId = null ) {
		/** @var $wpdb wpdb */
		global $wpdb;

		if ( ( $userId == null ) && ( apply_filters( 'mainwp_is_multi_user', false ) == true ) ) {
			global $current_user;
			$userId = $current_user->ID;
		}

		if ( MainWPGAUtility::ctype_digit( $websiteid ) ) {
			$wpdb->query(
				'INSERT INTO ' . $this->tableName( 'wp_ga' ) . '
          (wpid, userid, statsValues, statsValuesPrev, graphValues, date) VALUES (' . $websiteid . ', ' . ( $userId == null ? 0 : $userId ) . ', "' . $this->escape( $statsValues ) . '", "' . $this->escape( $statsValuesPrev ) . '", "' . $this->escape( $graphValues ) . '", ' . time() . ')
          ON DUPLICATE KEY UPDATE statsValues="' . $this->escape( $statsValues ) . '", statsValuesPrev="' . $this->escape( $statsValuesPrev ) . '", graphValues="' . $this->escape( $graphValues ) . '", date=' . time()
			);
			return true;
		}

		return false;
	}

	public function removeGACache( $userId = null ) {
		/** @var $wpdb wpdb */
		global $wpdb;

		if ( ( $userId == null ) && ( apply_filters( 'mainwp_is_multi_user', false ) == true ) ) {
			global $current_user;
			$userId = $current_user->ID;
		}

		if ( $userId == null ) {
			$wpdb->query( 'DELETE FROM ' . $this->tableName( 'wp_ga' ) );
		} else {
			$wpdb->query( 'DELETE FROM ' . $this->tableName( 'wp_ga' ) . ' WHERE userid=' . $userId );
		}
		return true;
	}

	public function removeGASettings( $gaId, $userId = null ) {
		/** @var $wpdb wpdb */
		global $wpdb;

		if ( ( $userId == null ) && ( apply_filters( 'mainwp_is_multi_user', false ) == true ) ) {
			global $current_user;
			$userId = $current_user->ID;
		}

		if ( $userId == null ) {
			$wpdb->query( 'DELETE FROM ' . $this->tableName( 'gas' ) . ' WHERE id = ' . $gaId );
		} else {
			$wpdb->query( 'DELETE FROM ' . $this->tableName( 'gas' ) . ' WHERE userid=' . $userId . ' AND id = ' . $gaId );
		}
	}

	public function getGASettingGlobal( $field, $userId = null ) {
		/** @var $wpdb wpdb */
		global $wpdb;

		if ( ( $userId == null ) && ( apply_filters( 'mainwp_is_multi_user', false ) == true ) ) {
			global $current_user;
			$userId = $current_user->ID;
		}

		return $wpdb->get_var( 'SELECT ' . $field . ' FROM ' . $this->tableName( 'ga' ) . ' WHERE userid = ' . ( $userId == null ? 0 : $userId ) );
	}

	public function updateGASettingGlobal( $field, $value, $userId = null ) {
		/** @var $wpdb wpdb */
		global $wpdb;

		if ( ( $userId == null ) && ( apply_filters( 'mainwp_is_multi_user', false ) == true ) ) {
			global $current_user;
			$userId = $current_user->ID;
		}

		$var = $this->getGASettingGlobal( 'userid' );

		if ( $var == null ) {
			$wpdb->query( 'INSERT INTO ' . $this->tableName( 'ga' ) . ' (`userid`, `enabled`, `oauth_verifier`, `oauth_verifier_token`, `update_interval`, `refreshrate`, `oauth_token`, `oauth_token_secret`, `token_secret`, `lastupdate`, `availableSites`, `propertyIds`) VALUES ("' . ( $userId == null ? 0 : $userId ) . '", "0", "", "", "", "", "", "", "", 0, "", "");' );
		}

		$wpdb->query( 'UPDATE ' . $this->tableName( 'ga' ) . ' SET ' . $field . '="' . $this->escape( $value ) . '" WHERE userid=' . ( $userId == null ? 0 : $userId ) );
	}

	public function getGASetting( $gaId, $field, $userId = null ) {
		/** @var $wpdb wpdb */
		global $wpdb;

		if ( ( $userId == null ) && ( apply_filters( 'mainwp_is_multi_user', false ) == true ) ) {
			global $current_user;
			$userId = $current_user->ID;
		}

		if ( $userId == null ) {
			return $wpdb->get_var( 'SELECT ' . $field . ' FROM ' . $this->tableName( 'gas' ) . ' WHERE id = ' . $gaId );
		} else {
			return $wpdb->get_var( 'SELECT ' . $field . ' FROM ' . $this->tableName( 'gas' ) . ' WHERE userid = ' . $userId . ' AND id = ' . $gaId );
		}
	}

	public function getGASettingIdByField( $field, $value, $userId = null ) {
		/** @var $wpdb wpdb */
		global $wpdb;

		if ( ( $userId == null ) && ( apply_filters( 'mainwp_is_multi_user', false ) == true ) ) {
			global $current_user;
			$userId = $current_user->ID;
		}

		if ( $userId == null ) {
			return $wpdb->get_var( 'SELECT id FROM ' . $this->tableName( 'gas' ) . ' WHERE ' . $field . ' = "' . $this->escape( $value ) . '"' );
		} else {
			return $wpdb->get_var( 'SELECT id FROM ' . $this->tableName( 'gas' ) . ' WHERE userid=' . $userId . ' AND ' . $field . ' = "' . $this->escape( $value ) . '"' );
		}
	}

	public function updateGASetting( $gaId, $field, $value, $userId = null ) {
		/** @var $wpdb wpdb */
		global $wpdb;

		if ( ( $userId == null ) && ( apply_filters( 'mainwp_is_multi_user', false ) == true ) ) {
			global $current_user;
			$userId = $current_user->ID;
		}

		$var = $this->getGASetting( $gaId, 'userid' );

		if ( $var == null ) {
			$wpdb->query( 'INSERT INTO ' . $this->tableName( 'gas' ) . ' (`userid`, `enabled`) VALUES ("' . ( $userId == null ? 0 : $userId ) . '", "0");' );
		}

		if ( $userId == null ) {
			$wpdb->query( 'UPDATE ' . $this->tableName( 'gas' ) . ' SET ' . $field . '="' . $this->escape( $value ) . '" WHERE id = ' . $gaId );
		} else {
			$wpdb->query( 'UPDATE ' . $this->tableName( 'gas' ) . ' SET ' . $field . '="' . $this->escape( $value ) . '" WHERE userid=' . $userId . ' AND id = ' . $gaId );
		}
	}

	public function getGAEntry( $field, $value, $userId = null ) {
		/** @var $wpdb wpdb */
		global $wpdb;

		if ( ( $userId == null ) && ( apply_filters( 'mainwp_is_multi_user', false ) == true ) ) {
			global $current_user;
			$userId = $current_user->ID;
		}

		return $wpdb->get_var( 'SELECT ' . $field . ' FROM ' . $this->tableName( 'gas' ) . ' WHERE userid = ' . ( $userId == null ? 0 : $userId ) . ' AND ' . $field . ' = "' . $this->escape( $value ) . '"' );
	}

	public function getGAEntryBy( $by = 'id', $value = false, $userId = null, $output = OBJECT ) {

		global $wpdb;

		if ( empty( $by ) || empty( $value ) ) {
			return null;
		}

		if ( ( $userId == null ) && ( apply_filters( 'mainwp_is_multi_user', false ) == true ) ) {
			global $current_user;
			$userId = $current_user->ID;
		}

		$sql = '';

		if ( $by == 'id' ) {
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->tableName( 'gas' ) . ' WHERE `id`=%d AND `userid` = %s', $value, $userId );
		} elseif ( $by == 'client_id' ) {
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->tableName( 'gas' ) . ' WHERE `client_id` = %s AND `userid` = %s', $value, $userId );
		}

		if ( ! empty( $sql ) ) {
			return $wpdb->get_row( $sql, $output );
		}

		return null;
	}


	public function updateGAEntry( $gaEntry, $userId = null ) {
		/** @var $wpdb wpdb */
		global $wpdb;

		if ( ! isset( $gaEntry['id'] ) && ! isset( $gaEntry['client_id'] ) ) {
			return false;
		}

		if ( ( $userId == null ) && ( apply_filters( 'mainwp_is_multi_user', false ) == true ) ) {
			global $current_user;
			$userId = $current_user->ID;
		}

		if ( ! isset( $gaEntry['userid'] ) ) {
			$gaEntry['userid'] = $userId;
		}

		$id        = isset( $gaEntry['id'] ) ? $gaEntry['id'] : 0;
		$client_id = isset( $gaEntry['client_id'] ) ? $gaEntry['client_id'] : 0;

		if ( ! empty( $id ) ) {
			$wpdb->update( $this->tableName( 'gas' ), $gaEntry, array( 'id' => intval( $id ) ) );
			return $this->getGAEntryBy( 'id', $id );
		} elseif ( ! empty( $client_id ) ) {
			$current = $this->getGAEntryBy( 'client_id', $client_id );
			if ( $current ) {
				$wpdb->update( $this->tableName( 'gas' ), $gaEntry, array( 'client_id' => $this->escape( $client_id ) ) );
				$out = $this->getGAEntryBy( 'client_id', $client_id );
				return $out;
			} else {
				if ( $insert_id = $this->createGAEntry( $userId ) ) {
					$wpdb->update( $this->tableName( 'gas' ), $gaEntry, array( 'id' => $insert_id ) );
					$out = $this->getGAEntryBy( 'id', $insert_id );
					return $out;
				}
			}
		}
		return false;
	}


	public function createGAEntry( $userId = null ) {
		/** @var $wpdb wpdb */
		global $wpdb;

		if ( ( $userId == null ) && ( apply_filters( 'mainwp_is_multi_user', false ) == true ) ) {
			global $current_user;
			$userId = $current_user->ID;
		}

		$wpdb->insert(
			$this->tableName( 'gas' ),
			array(
				'userid'  => ( $userId == null ? 0 : $userId ),
				'enabled' => 0,
				'created' => time(),
			)
		);
		return $wpdb->insert_id;
	}

	public function getGAEntries( $ga_id = false ) {
		/** @var $wpdb wpdb */
		global $wpdb;

		if ( $ga_id ) {
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->tableName( 'gas' ) . ' WHERE `id`=%d', $ga_id );
		} else {
			$sql = 'SELECT * FROM ' . $this->tableName( 'gas' ) . ' WHERE 1';
		}
		return $wpdb->get_results( $sql, OBJECT );
	}

	public function getWebsitesByUserIdWithGAId( $wpid = null, $userId = null ) {
		/** @var $wpdb wpdb */
		global $wpdb;
		$where = '';
		if ( $wpid !== null ) {
			$where = ' AND wp.id = ' . $wpid;
		}

		if ( $userId !== null ) {
			$where .= ' AND wp.userid = ' . $userId;
		}

		$qry = 'SELECT wp.id, wp.name
        FROM ' . $this->tableName( 'wp' ) . ' wp, ' . $this->tableName( 'wp_ga_gas' ) . ' wp_ga_gas
        WHERE wp.id = wp_ga_gas.wpid AND wp_ga_gas.ga_id IS NOT NULL AND wp_ga_gas.ga_id <> "" ' .
		$where
		. ' ORDER BY wp.url';
		return $wpdb->get_results( $qry, OBJECT_K );
	}


	public function removeWebsite( $websiteid ) {
		/** @var $wpdb wpdb */
		global $wpdb;

		return $wpdb->query( 'DELETE FROM ' . $this->tableName( 'wp_ga' ) . ' WHERE wpid=' . $websiteid );
	}

}
