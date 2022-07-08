<?php

class MainWPBackWPupDB {
	private $mainwp_backwpup_db_version = '1.3';
	private static $instance            = null;
	private $table_prefix;

	public static function Instance() {
		if ( self::$instance == null ) {
			self::$instance = new MainWPBackWPupDB();
		}

		return self::$instance;
	}

	public function __construct() {
		global $wpdb;
		$this->table_prefix = $wpdb->prefix . 'mainwp_';
	}

	public function tableName( $suffix ) {
		return $this->table_prefix . $suffix;
	}

	public function install() {
		global $wpdb;

		$currentVersion = get_site_option( 'mainwp_backwpup_db_version' );

		if ( $currentVersion == $this->mainwp_backwpup_db_version ) {
			return true;
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = array();

		$sql[] = 'CREATE TABLE `' . $this->tableName( 'backwpup_settings' ) . '` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`website_id` int(11) NOT NULL,
			`settings` text NOT NULL,
			`is_premium` tinyint(1) NOT NULL DEFAULT 0,
            `lastbackup` int(11) NOT NULL DEFAULT 0,
			`override` tinyint(1) NOT NULL DEFAULT 0,
			PRIMARY KEY  (`id`),
			CONSTRAINT `website_uniq` UNIQUE (`website_id`),
			KEY `website_id_key` (`website_id`) )' . $charset_collate;

		$sql[] = 'CREATE TABLE `' . $this->tableName( 'backwpup_jobs' ) . '` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`website_id` int(11) NOT NULL,
			`job_id` int(11) NOT NULL,
			`settings` text NOT NULL,
			PRIMARY KEY  (`id`),
			KEY `website_id_key` (`website_id`) )' . $charset_collate;

		$sql[] = 'CREATE TABLE `' . $this->tableName( 'backwpup_global_jobs' ) . '` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`global_id` int(11) NOT NULL,
			`job_id` int(11) NOT NULL,
			`website_id` int(11) NOT NULL,
			PRIMARY KEY  (`id`),
			CONSTRAINT `website_job_uniq` UNIQUE (`website_id`, `job_id`),
			KEY `global_id_key` (`global_id`) )' . $charset_collate;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		foreach ( $sql as $query ) {
			dbDelta( $query );
		}

		if ( $this->check_if_tables_exists() !== true ) {
			return false;
		}

		update_option( 'mainwp_backwpup_db_version', $this->mainwp_backwpup_db_version );

		return true;
	}

	public function uninstall() {
		global $wpdb;

		// $wpdb->query( 'DROP TABLE IF EXISTS `' . $this->tableName( 'backwpup_settings' ) . '`' );
		// $wpdb->query( 'DROP TABLE IF EXISTS `' . $this->tableName( 'backwpup_jobs' ) . '`' );
		// $wpdb->query( 'DROP TABLE IF EXISTS `' . $this->tableName( 'backwpup_global_jobs' ) . '`' );
	}

	public function check_if_tables_exists() {
		global $wpdb;
		$tables = array( 'backwpup_settings', 'backwpup_jobs', 'backwpup_global_jobs' );

		foreach ( $tables as $table ) {
			if ( $wpdb->get_var( "SHOW TABLES LIKE '" . $this->tableName( $table ) . "'" ) != $this->tableName( $table ) ) {
				return $this->tableName( $table );
			}
		}

		return true;
	}

	public function get_fields_by_website_ids( $fields, $websiteids ) {
		global $wpdb;

		if ( ! is_array( $fields ) || count( $fields ) == 0 ) {
			return false;
		}

		if ( ! is_array( $websiteids ) || count( $websiteids ) == 0 ) {
			return false;
		}

		if ( ! in_array( 'website_id', $fields ) ) {
			$fields[] = 'website_id';
		}

		$str_fields = '';
		foreach ( $fields as $field ) {
			$str_fields .= '`' . $field . '`,';
		}

		$str_fields = rtrim( $str_fields, ',' );

		$websiteids = implode( ',', $websiteids );

		$return = array();

		$result = $wpdb->get_results( 'SELECT ' . $str_fields . ' FROM ' . $this->tableName( 'backwpup_settings' ) . ' WHERE `website_id` IN (' . $websiteids . ')', ARRAY_A );

		if ( $result ) {
			foreach ( $result as $value ) {
				$return[ $value['website_id'] ] = $value;
			}
		}
		return $return;
	}

	public function get_website_id_by_override( $override ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare( 'SELECT website_id FROM ' . $this->tableName( 'backwpup_settings' ) . ' WHERE `override` = %d', $override ), ARRAY_A );
	}

	public function get_our_id_by_job_id_and_website_id( $website_id, $job_id ) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare( 'SELECT id FROM ' . $this->tableName( 'backwpup_jobs' ) . ' WHERE `website_id` = %d and `job_id` = %d', $website_id, $job_id ), ARRAY_A );
	}

	public function get_child_job_id_by_id( $id ) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare( 'SELECT job_id FROM ' . $this->tableName( 'backwpup_jobs' ) . ' WHERE `id` = %d', $id ), ARRAY_A );
	}

	public function get_child_job_id_by_website_id( $website_id ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare( 'SELECT id, job_id FROM ' . $this->tableName( 'backwpup_jobs' ) . ' WHERE `website_id` = %d', $website_id ), ARRAY_A );
	}

	public function get_settings_by_website_id( $website_id ) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $this->tableName( 'backwpup_settings' ) . ' WHERE `website_id` = %d', $website_id ), ARRAY_A );
	}

	public function insert_or_update_settings_by_website_id( $settings, $is_premium, $override, $website_id ) {
		global $wpdb;
		$is_exist = $this->get_settings_by_website_id( $website_id );
		if ( isset( $is_exist['id'] ) ) {
			return $wpdb->update(
				$this->tableName( 'backwpup_settings' ),
				array(
					'settings'   => $settings,
					'is_premium' => $is_premium,
					'override'   => $override,
				),
				array( 'id' => $is_exist['id'] )
			);
		} else {
			return $wpdb->insert(
				$this->tableName( 'backwpup_settings' ),
				array(
					'settings'   => $settings,
					'is_premium' => $is_premium,
					'override'   => $override,
					'website_id' => $website_id,
				)
			);
		}
	}

	public function insert_or_update_settings_fields_by_website_id( $website_id, $fields ) {
		global $wpdb;
		$is_exist = $this->get_settings_by_website_id( $website_id );
		if ( isset( $is_exist['id'] ) ) {
			return $wpdb->update( $this->tableName( 'backwpup_settings' ), $fields, array( 'id' => $is_exist['id'] ) );
		} else {
			$default = array(
				'settings'   => wp_json_encode( array() ),
				'is_premium' => 0,
				'override'   => 0,
				'lastbackup' => 0,
				'website_id' => $website_id,
			);
			$update  = array_merge( $default, $fields );
			return $wpdb->insert( $this->tableName( 'backwpup_settings' ), $update );
		}
	}


	public function get_job_by_website_id( $website_id ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $this->tableName( 'backwpup_jobs' ) . ' WHERE `website_id` = %d', $website_id ), ARRAY_A );
	}

	public function get_job_by_id( $id ) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $this->tableName( 'backwpup_jobs' ) . ' WHERE `id` = %d', $id ), ARRAY_A );
	}

	public function get_jobs_by_website_id( $website_id ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $this->tableName( 'backwpup_jobs' ) . ' WHERE `website_id` = %d', $website_id ), ARRAY_A );
	}

	public function get_job_by_website_id_and_job_id( $website_id, $job_id ) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $this->tableName( 'backwpup_jobs' ) . ' WHERE `website_id` = %d AND `job_id` = %d', $website_id, $job_id ), ARRAY_A );
	}

	public function get_job_by_website_id_and_id( $website_id, $id ) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $this->tableName( 'backwpup_jobs' ) . ' WHERE `website_id` = %d AND `id` = %d', $website_id, $id ), ARRAY_A );
	}

	public function get_job_id_and_name_by_website_id( $website_id ) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $this->tableName( 'backwpup_jobs' ) . ' WHERE `website_id` = %d', $website_id ), ARRAY_A );
	}

	public function insert_or_update_job_by_id( $id, $website_id, $job_id, $settings ) {
		global $wpdb;
		if ( $id > 0 ) {
			if ( $wpdb->update(
				$this->tableName( 'backwpup_jobs' ),
				array(
					'website_id' => $website_id,
					'job_id'     => $job_id,
					'settings'   => $settings,
				),
				array( 'id' => $id )
			) === false
			) {
				return false;
			} else {
				return $id;
			}
		} else {
			if ( $website_id > 0 ) {
				// Because we don't have there constrain
				$is_exist = $this->get_job_by_website_id_and_job_id( $website_id, $job_id );
				if ( isset( $is_exist['id'] ) ) {
					return $this->insert_or_update_job_by_id( $is_exist['id'], $website_id, $job_id, $settings );
				}
			}

			if ( $wpdb->insert(
				$this->tableName( 'backwpup_jobs' ),
				array(
					'website_id' => $website_id,
					'job_id'     => $job_id,
					'settings'   => $settings,
				),
				array( '%d', '%d', '%s' )
			) === false
			) {
				return false;
			} else {
				return $this->get_insert_id();
			}
		}
	}

	public function update_job_id_in_global_jobs_by_id( $id, $job_id ) {
		global $wpdb;

		return $wpdb->update( $this->tableName( 'backwpup_global_jobs' ), array( 'job_id' => $job_id ), array( 'id' => $id ) );
	}

	public function get_global_job_by_id( $id ) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $this->tableName( 'backwpup_global_jobs' ) . ' WHERE `id` = %d', $id ), ARRAY_A );
	}

	public function insert_global_jobs( $global_id, $job_id, $website_id ) {
		global $wpdb;

		return $wpdb->insert(
			$this->tableName( 'backwpup_global_jobs' ),
			array(
				'global_id'  => $global_id,
				'job_id'     => $job_id,
				'website_id' => $website_id,
			),
			array( '%d', '%d', '%d' )
		);
	}

	public function get_global_job_by_global_id_and_website_id( $global_id, $website_id ) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $this->tableName( 'backwpup_global_jobs' ) . ' WHERE `global_id` = %d AND `website_id` = %d', $global_id, $website_id ), ARRAY_A );
	}

	public function get_global_job_by_global_id( $global_id ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $this->tableName( 'backwpup_global_jobs' ) . ' WHERE `global_id` = %d', $global_id ), ARRAY_A );
	}

	public function get_global_job_by_website_id( $website_id ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $this->tableName( 'backwpup_global_jobs' ) . ' WHERE `website_id` = %d', $website_id ), ARRAY_A );
	}

	public function delete_job_by_website_id_and_id( $website_id, $id ) {
		global $wpdb;

		return $wpdb->delete(
			$this->tableName( 'backwpup_jobs' ),
			array(
				'website_id' => $website_id,
				'id'         => $id,
			),
			array( '%d', '%d' )
		);
	}

	public function delete_global_jobs_by_global_id( $global_id ) {
		global $wpdb;

		return $wpdb->delete( $this->tableName( 'backwpup_global_jobs' ), array( 'global_id' => $global_id ), array( '%d' ) );
	}

	public function get_insert_id() {
		global $wpdb;

		return $wpdb->insert_id;
	}
}
