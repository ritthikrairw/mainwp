<?php

class Favorites_Extension_DB {
	// Config
	private static $instance = null;
	// Singleton
	private $mainwp_favorites_extension_db_version = '1.4';
	private $table_prefix;

	function __construct() {
		global $wpdb;
		$this->table_prefix = $wpdb->prefix . 'mainwp_';
	}

	// Constructor
	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new Favorites_Extension_DB();
		}
		return self::$instance;
	}

	public static function fetch_object( $result ) {
		if ( self::use_mysqli() ) {
			return mysqli_fetch_object( $result );
		} else {
			return mysql_fetch_object( $result );
		}
	}

	// Installs new DB
	public static function use_mysqli() {
		/** @var $wpdb wpdb */
		if ( ! function_exists( 'mysqli_connect' ) ) {
			return false;
		}
		global $wpdb;
		return ( $wpdb->dbh instanceof mysqli );
	}

	public static function free_result( $result ) {
		if ( empty( $result ) ) {
			return false;
		}
		if ( self::use_mysqli() ) {
			return mysqli_free_result( $result );
		} else {
			return mysql_free_result( $result );
		}
	}

	public static function data_seek( $result, $offset ) {
		if ( self::use_mysqli() ) {
			return mysqli_data_seek( $result, $offset );
		} else {
			return mysql_data_seek( $result, $offset );
		}
	}

	public static function fetch_array( $result, $result_type = null ) {
		if ( self::use_mysqli() ) {
			return mysqli_fetch_array( $result, ( null == $result_type ? MYSQLI_BOTH : $result_type ) );
		} else {
			return mysql_fetch_array( $result, ( null == $result_type ? MYSQL_BOTH : $result_type ) );
		}
	}

	public static function is_result( $result ) {
		if ( self::use_mysqli() ) {
			return ( $result instanceof mysqli_result );
		} else {
			return is_resource( $result );
		}
	}

	function install() {
		$currentVersion = get_site_option( 'mainwp_favorites_extension_db_version' );
		if ( $currentVersion == $this->mainwp_favorites_extension_db_version ) {
			return;
		}
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		// $wpdb->query("DROP TABLE " . $this->table_name('favorites_plugin_theme'));
		$sql = array();
		$tbl = 'CREATE TABLE `' . $this->table_name( 'favorites_plugin_theme' ) . '` (
`id` int NOT NULL AUTO_INCREMENT,
`userid` int(11) NOT NULL,
`type` varchar(1) NOT NULL,
`slug` varchar(200) NOT NULL,
`name` text NOT NULL,
`url` text NOT NULL,
`file` text NOT NULL,
`author` text NOT NULL,
`version` varchar(50) NOT NULL,
`note` text NOT NULL';
		if ( '' == $currentVersion ) {
			$tbl .= ',
PRIMARY KEY  (`id`)  ';
		}
		$tbl .= ') ' . $charset_collate;

		$sql[] = $tbl;

		$tbl = 'CREATE TABLE `' . $this->table_name( 'favorites_group' ) . '` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`userid` int(11) NOT NULL,
`type` varchar(1) NOT NULL,
`name` text NOT NULL,
`note` text NOT NULL';
		if ( '' == $currentVersion ) {
			$tbl .= ',
PRIMARY KEY  (`id`)  ';
		}
		$tbl .= ') ' . $charset_collate;

		$sql[] = $tbl;

		$tbl = 'CREATE TABLE `' . $this->table_name( 'favorites_plugin_theme_group' ) . '` (
`fav_gro_id` int(11) NOT NULL AUTO_INCREMENT,
`favoriteid` int(11) NOT NULL,
`groupid` int(11) NOT NULL';
		if ( '' == $currentVersion || '1.3' == $currentVersion ) {
			$tbl .= ',
PRIMARY KEY  (`fav_gro_id`)  ';
		}
		$tbl  .= ') ' . $charset_collate;
		$sql[] = $tbl;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		foreach ( $sql as $query ) {
			dbDelta( $query );
		}
		// global $wpdb;
		// echo $wpdb->last_error;
		// exit();

		update_option( 'mainwp_favorites_extension_db_version', $this->mainwp_favorites_extension_db_version );
	}

	private function table_name( $suffix ) {
		return $this->table_prefix . $suffix;
	}

	public function remove_favorite_by( $by, $value, $userid = null, $type = null ) {
		global $wpdb;
		if ( 'id' == $by ) {
			$return = $wpdb->query( 'DELETE FROM ' . $this->table_name( 'favorites_plugin_theme' ) . ' WHERE id = ' . $value );
			if ( $return ) {
				$wpdb->query( 'DELETE FROM ' . $this->table_name( 'favorites_plugin_theme_group' ) . ' WHERE favoriteid = ' . $value );
			}
			return $return;
		} elseif ( 'file' == $by ) {
			$fav    = $this->get_favorite_by( $by, $value, $userid, $type );
			$return = false;
			if ( is_object( $fav ) ) {
				$return = $wpdb->query( 'DELETE FROM ' . $this->table_name( 'favorites_plugin_theme' ) . ' WHERE id = ' . $fav->id );
				if ( $return ) {
					$wpdb->query( 'DELETE FROM ' . $this->table_name( 'favorites_plugin_theme_group' ) . ' WHERE favoriteid = ' . $fav->id );
				}
			}
			return $return;
		}
	}

	public function get_favorite_by( $by, $value, $userid = null, $type = null ) {
		/** @var $wpdb wpdb */
		global $wpdb;
		$where_type = $where_user = '';
		if ( null != $type ) {
			$type = self::get_type_char( $type );
			if ( empty( $type ) ) {
				return false;
			}
			$where_type = " AND type = '" . $type . "' ";
		}

		$is_multi_user = apply_filters( 'mainwp_is_multi_user', 10 );
		if ( ( null == $userid ) && $is_multi_user ) {
			global $current_user;
			$userid = $current_user->ID;
		}
		if ( null != $userid ) {
			$where_user = ' AND userid = ' . $userid . ' ';
		}

		if ( 'id' == $by ) {
			return $wpdb->get_row( 'SELECT * FROM ' . $this->table_name( 'favorites_plugin_theme' ) . ' WHERE id = ' . $value );
		} elseif ( 'slug' == $by ) {
			return $wpdb->get_row( 'SELECT * FROM ' . $this->table_name( 'favorites_plugin_theme' ) . " WHERE slug = '" . $value . "' " . $where_user . $where_type );
		} elseif ( 'name' == $by ) {
			return $wpdb->get_row( 'SELECT * FROM ' . $this->table_name( 'favorites_plugin_theme' ) . " WHERE name = '" . $value . "' " . $where_user . $where_type );
		} elseif ( 'file' == $by ) {
			return $wpdb->get_row( 'SELECT * FROM ' . $this->table_name( 'favorites_plugin_theme' ) . " WHERE file = '" . $value . "' " . $where_user . $where_type );
		}

		return false;
	}

	public static function get_type_char( $type ) {
		return $type = ( 'P' == $type || 'T' == $type ) ? $type : ( ( 'plugin' == $type ) ? 'P' : ( ( 'theme' == $type ) ? 'T' : '' ) );
	}

	public function get_group_by( $by, $value, $userid = null, $type = null ) {

		/** @var $wpdb wpdb */
		global $wpdb;

		if ( null != $type ) {
			$type = self::get_type_char( $type );
			if ( empty( $type ) ) {
				return false;
			}
			$where_type = " AND type = '" . $type . "' ";
		}

		$is_multi_user = apply_filters( 'mainwp_is_multi_user', 10 );
		if ( ( null == $userid ) && $is_multi_user ) {
			global $current_user;
			$userid = $current_user->ID;
		}
		if ( null != $userid ) {
			$where_user = ' AND userid = ' . $userid . ' ';
		}

		if ( 'id' == $by ) {
			return $wpdb->get_row( 'SELECT * FROM ' . $this->table_name( 'favorites_group' ) . ' WHERE id = ' . $value );
		} elseif ( 'name' == $by ) {
			return $wpdb->get_row( 'SELECT * FROM ' . $this->table_name( 'favorites_group' ) . " WHERE name = '" . $value . "' " . $where_user . $where_type );
		}

		return false;
	}

	public function add_favorite( $userid, $type, $slug, $name, $author, $version, $file = '', $url = '' ) {

		/** @var $wpdb wpdb */
		global $wpdb;

		$type = self::get_type_char( $type );

		if ( empty( $type ) ) {
			return false;
		}

		if ( Favorites_Extension::ctype_digit( $userid ) ) {
			// check if existed
			if ( ! empty( $slug ) && $current = $this->get_favorite_by( 'slug', $slug, $userid, $type ) ) {
				if ( version_compare( $current->version, $version, '<=' ) ) {
					$this->update_favorite( $current->id, $slug, $name, $author, $version, $file, $url );

					return $this->get_favorite_by( 'id', $current->id );
				} else {
					return 'NEWER_EXISTED';
				}
			} elseif ( ! empty( $name ) && $current = $this->get_favorite_by( 'name', $name, $userid, $type ) ) {
				if ( version_compare( $current->version, $version, '<=' ) ) {
					$this->update_favorite( $current->id, $slug, $name, $author, $version, $file, $url );

					return $this->get_favorite_by( 'id', $current->id );
				} else {
					return 'NEWER_EXISTED';
				}
			} else {
				if ( $wpdb->insert(
					$this->table_name( 'favorites_plugin_theme' ),
					array(
						'userid'  => $userid,
						'type'    => $type,
						'slug'    => $slug,
						'name'    => $name,
						'author'  => $author,
						'version' => $version,
						'note'    => '',
						'file'    => $file,
						'url'     => $url,
					)
				)
				) {
					return $this->get_favorite_by( 'id', $wpdb->insert_id );
				}
				// echo $wpdb->last_error;
			}
		}

		return false;
	}

	public function update_favorite( $id, $slug, $name, $author, $version, $file = '', $url = '' ) {

		/** @var $wpdb wpdb */
		global $wpdb;
		if ( Favorites_Extension::ctype_digit( $id ) ) {
			return $wpdb->update(
				$this->table_name( 'favorites_plugin_theme' ),
				array(
					'slug'    => $slug,
					'name'    => $name,
					'author'  => $author,
					'version' => $version,
					'file'    => $file,
					'url'     => $url,
				),
				array( 'id' => $id )
			);
		}

		return false;
	}

	public function get_groups_and_count( $type = 'plugin', $userid = null ) {

		/** @var $wpdb wpdb */
		global $wpdb;

		$type = self::get_type_char( $type );

		if ( empty( $type ) ) {
			return false;
		}

		$is_multi_user = apply_filters( 'mainwp_is_multi_user', 10 );
		if ( ( null == $userid ) && $is_multi_user ) {
			global $current_user;
			$userid = $current_user->ID;
		}

		return $wpdb->get_results(
			'SELECT gr.*, COUNT(DISTINCT(favgr.favoriteid)) as nrsites
                FROM ' . $this->table_name( 'favorites_group' ) . ' gr
                LEFT JOIN ' . $this->table_name( 'favorites_plugin_theme_group' ) . ' favgr ON gr.id = favgr.groupid
                WHERE gr.type = \'' . $type . '\' ' . ( null != $userid ? ' AND  gr.userid = ' . $userid : '' ) . '
                GROUP BY gr.id
                ORDER BY gr.name',
			OBJECT_K
		);
	}

	public function get_groups( $type = 'plugin', $select_favorites = false ) {

		/** @var $wpdb wpdb */
		global $wpdb;

		$type = self::get_type_char( $type );

		if ( empty( $type ) ) {
			return false;
		}

		$is_multi_user = apply_filters( 'mainwp_is_multi_user', 10 );
		$userid        = null;
		if ( $is_multi_user ) {
			global $current_user;
			$userid = $current_user->ID;
		}

		if ( $select_favorites ) {
			$qry = 'SELECT gr.*, GROUP_CONCAT(fav.name ORDER BY fav.name SEPARATOR ", ") as favorites
                    FROM ' . $this->table_name( 'favorites_group' ) . ' gr
                    LEFT JOIN ' . $this->table_name( 'favorites_plugin_theme_group' ) . ' favgr ON gr.id = favgr.groupid
                    LEFT JOIN ' . $this->table_name( 'favorites_plugin_theme' ) . ' fav ON favgr.favoriteid = fav.id
                    WHERE gr.type = \'' . $type . '\' ' . ( null != $userid ? ' AND  gr.userid = ' . $userid : '' ) . '
                    GROUP BY gr.id
                    ORDER BY gr.name';
		} else {
			$qry = 'SELECT gr.*
                    FROM ' . $this->table_name( 'favorites_group' ) . ' gr
                    WHERE gr.type = \'' . $type . '\' ' . ( null != $userid ? ' AND  gr.userid = ' . $userid : '' ) . '
                    ORDER BY gr.name';
		}

		return $wpdb->get_results( $qry, OBJECT_K );

	}

	public function get_favorites_by_group_id( $id ) {
		return $this->get_results_result( $this->get_sql_favorites_by_group_id( $id ) );
	}

	public function get_results_result( $sql ) {
		if ( null == $sql ) {
			return null;
		}

		/** @var $wpdb wpdb */
		global $wpdb;

		return $wpdb->get_results( $sql, OBJECT_K );
	}

	public function get_sql_favorites_by_group_id( $id, $selectgroups = false ) {

		if ( ! empty( $id ) ) {
			if ( $selectgroups ) {
				return 'SELECT fav.*, GROUP_CONCAT(gr.name ORDER BY gr.name SEPARATOR ", ") as groups
                 FROM ' . $this->table_name( 'favorites_plugin_theme' ) . ' fav
                 JOIN ' . $this->table_name( 'favorites_plugin_theme_group' ) . ' favgroup ON fav.id = favgroup.favoriteid
                 LEFT JOIN ' . $this->table_name( 'favorites_plugin_theme_group' ) . ' favgr ON fav.id = favgr.favoriteid
                 LEFT JOIN ' . $this->table_name( 'favorites_group' ) . ' gr ON favgr.groupid = gr.id
                 WHERE favgroup.groupid = ' . $id . '
                 GROUP BY fav.id';
			} else {
				return 'SELECT * FROM ' . $this->table_name( 'favorites_plugin_theme' ) . ' fav JOIN ' . $this->table_name( 'favorites_plugin_theme_group' ) . ' favgroup ON fav.id = favgroup.favoriteid WHERE favgroup.groupid = ' . $id;
			}
		}

		return null;
	}

	public function get_sql_favorites_for_current_user( $type, $selectgroups = false, $orderBy = 'fav.name' ) {

		$type = self::get_type_char( $type );
		if ( empty( $type ) ) {
			return false;
		}

		$is_multi_user = apply_filters( 'mainwp_is_multi_user', 10 );
		$where         = '';
		if ( $is_multi_user ) {
			global $current_user;
			$where .= ' AND fav.userid = ' . $current_user->ID . ' ';
		}

		if ( $selectgroups ) {
			$qry = 'SELECT fav.*, GROUP_CONCAT(gr.name ORDER BY gr.name SEPARATOR ", ") as groups
            FROM ' . $this->table_name( 'favorites_plugin_theme' ) . ' fav
            LEFT JOIN ' . $this->table_name( 'favorites_plugin_theme_group' ) . ' favgr ON fav.id = favgr.favoriteid
            LEFT JOIN ' . $this->table_name( 'favorites_group' ) . ' gr ON favgr.groupid = gr.id
            WHERE fav.type = \'' . $type . '\' ' . $where . '
            GROUP BY fav.id
            ORDER BY ' . $orderBy;
		} else {
			$qry = 'SELECT fav.*
            FROM ' . $this->table_name( 'favorites_plugin_theme' ) . ' fav
            WHERE fav.type = \'' . $type . '\' ' . $where . '
            ORDER BY ' . $orderBy;
		}

		return $qry;
	}

	public function get_group_by_name_for_user( $type, $name, $userid = null ) {

		/** @var $wpdb wpdb */
		global $wpdb;

		$type = self::get_type_char( $type );
		if ( empty( $type ) ) {
			return false;
		}

		$is_multi_user = apply_filters( 'mainwp_is_multi_user', 10 );

		if ( null == $userid && $is_multi_user ) {
			global $current_user;
			$userid = $current_user->ID;
		}

		return $wpdb->get_row( 'SELECT * FROM ' . $this->table_name( 'favorites_group' ) . ' WHERE ' . ( null != $userid ? ' userid=' . $userid . ' AND ' : '' ) . ' name="' . $this->escape( $name ) . '" AND type = \'' . $type . '\'' );
	}

	protected function escape( $data ) {

		/** @var $wpdb wpdb */
		global $wpdb;

		if ( function_exists( 'esc_sql' ) ) {
			return esc_sql( $data );
		} else {
			return $this->escape( $data );
		}
	}


	// Support old & new versions of WordPress (3.9+)

	public function get_favorites_group_by_id( $id ) {

		/** @var $wpdb wpdb */
		global $wpdb;

		if ( Favorites_Extension::ctype_digit( $id ) ) {
			return $wpdb->get_row( 'SELECT * FROM ' . $this->table_name( 'favorites_group' ) . ' WHERE id=' . $id );
		}

		return null;
	}

	public function add_group( $userid, $name, $type ) {

		/** @var $wpdb wpdb */
		global $wpdb;

		$type = self::get_type_char( $type );

		if ( empty( $type ) ) {
			return false;
		}

		if ( Favorites_Extension::ctype_digit( $userid ) ) {
			if ( $wpdb->insert(
				$this->table_name( 'favorites_group' ),
				array(
					'userid' => $userid,
					'name'   => $this->escape( $name ),
					'type'   => $type,
				)
			)
			) {
				return $wpdb->insert_id;
			}
		}

		return false;
	}

	public function remove_group( $groupid ) {

		/** @var $wpdb wpdb */
		global $wpdb;

		if ( Favorites_Extension::ctype_digit( $groupid ) ) {
			$nr = $wpdb->query( 'DELETE FROM ' . $this->table_name( 'favorites_group' ) . ' WHERE id=' . $groupid );
			$wpdb->query( 'DELETE FROM ' . $this->table_name( 'favorites_plugin_theme_group' ) . ' WHERE groupid=' . $groupid );

			return $nr;
		}

		return false;
	}

	public function update_group( $groupid, $groupname ) {

		/** @var $wpdb wpdb */
		global $wpdb;

		if ( Favorites_Extension::ctype_digit( $groupid ) ) {
			// update groupname
			$wpdb->query( 'UPDATE ' . $this->table_name( 'favorites_group' ) . ' SET name="' . $this->escape( $groupname ) . '" WHERE id=' . $groupid );

			return true;
		}

		return false;
	}

	public function clear_group( $groupId ) {

		/** @var $wpdb wpdb */
		global $wpdb;

		$wpdb->query( 'DELETE FROM ' . $this->table_name( 'favorites_plugin_theme_group' ) . ' WHERE groupid=' . $groupId );
	}

	public function update_group_site( $groupId, $favoriteId ) {

		/** @var $wpdb wpdb */
		global $wpdb;

		$wpdb->insert(
			$this->table_name( 'favorites_plugin_theme_group' ),
			array(
				'favoriteid' => $favoriteId,
				'groupid'    => $groupId,
			)
		);
	}

	public function update_favorite_note( $favoriteid, $note ) {

		/** @var $wpdb wpdb */
		global $wpdb;

		return $wpdb->query( 'UPDATE ' . $this->table_name( 'favorites_plugin_theme' ) . ' SET note="' . $this->escape( $note ) . '" WHERE id=' . $favoriteid );
	}

	public function update_group_note( $groupid, $note ) {

		/** @var $wpdb wpdb */
		global $wpdb;

		return $wpdb->query( 'UPDATE ' . $this->table_name( 'favorites_group' ) . ' SET note="' . $this->escape( $note ) . '" WHERE id=' . $groupid );
	}

	public function query( $sql ) {

		if ( null == $sql ) {
			return false;
		}

		/** @var $wpdb wpdb */
		global $wpdb;
		$result = @self::_query( $sql, $wpdb->dbh );

		if ( ! $result || ( @self::num_rows( $result ) == 0 ) ) {
			return false;
		}

		return $result;
	}

	public static function _query( $query, $link ) {

		if ( self::use_mysqli() ) {
			return mysqli_query( $link, $query );
		} else {
			return mysql_query( $query, $link );
		}
	}

	public static function num_rows( $result ) {
		if ( empty( $result ) ) {
			return false;
		}
		if ( self::use_mysqli() ) {
			return mysqli_num_rows( $result );
		} else {
			return mysql_num_rows( $result );
		}
	}
}


