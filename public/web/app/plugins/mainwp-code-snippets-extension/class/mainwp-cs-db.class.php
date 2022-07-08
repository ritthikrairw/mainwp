<?php

class MainWP_CS_DB {

	private $mainwp_code_snippets_db_version = '1.5';
	//Singleton
	private static $instance = null;
	private $table_prefix;

	static function get_instance() {
		if ( null === MainWP_CS_DB::$instance ) {
			MainWP_CS_DB::$instance = new MainWP_CS_DB();
		}
		return MainWP_CS_DB::$instance;
	}

	//Constructor
	function __construct() {
		global $wpdb;
		$this->table_prefix = $wpdb->prefix . 'mainwp_';
	}

	function table_name( $suffix ) {
		return $this->table_prefix . $suffix;
	}

	//Support old & new versions of wordpress (3.9+)
	public static function use_mysqli() {
		/** @var $wpdb wpdb */
		if ( ! function_exists( 'mysqli_connect' ) ) {
			return false;
		}
		global $wpdb;
		return ( $wpdb->dbh instanceof mysqli );
	}


	//Installs new DB
	function install() {
		global $wpdb;
		$currentVersion = get_site_option( 'mainwp_code_snippets_db_version' );
		// Fix data
		if ( '1.3' === $currentVersion ) {
			$snippet = $this->get_codesnippet_by( 'title', 'Count Published Posts' );
			$update = array('id' => $snippet->id,
				'code' => "include_once(ABSPATH . WPINC . '/pluggable.php');
	\$count_posts = wp_count_posts();
	echo get_bloginfo('name').' has '.\$count_posts->publish.' published posts.';"
			);
			$this->update_codesnippet($update);
		}
		if ( $currentVersion == $this->mainwp_code_snippets_db_version ) {
			return; }
		$charset_collate = $wpdb->get_charset_collate();
		$sql = array();

		$tbl = 'CREATE TABLE `' . $this->table_name( 'codesnippet' ) . '` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`userid` int(11) NOT NULL,
`title` text NOT NULL,
`snippet_slug` varchar(32) NOT NULL,
`description` text NOT NULL,
`code` text NOT NULL,
`sites` text NOT NULL,
`groups` text NOT NULL,
`type` varchar(1) NOT NULL,
`date` int(11) NOT NULL';
		if ( '' == $currentVersion ) {
			$tbl .= ',
PRIMARY KEY  (`id`)  '; }
		$tbl .= ') ' . $charset_collate;
		$sql[] = $tbl;

		error_reporting( 0 ); // make sure to disable any error output
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		foreach ( $sql as $query ) {
			dbDelta( $query );
		}

		$default_snippets = array(
				array("title" => "Remove Admin Bar",
					"description" => "Removes the admin bar on the front end. <br/>MainWP is not responsible for this code. <strong>Use at your own risk.</strong> Source: <a href=\"http://www.wpfunction.me/\">WPFunction.Me</a>",
					"type" => "S",
					"code" => "add_filter( 'show_admin_bar', '__return_false' );"
				),
				array("title" => "Customise Admin Footer",
					"description" => "Customise the footer in admin area. <br/>MainWP is not responsible for this code. <strong>Use at your own risk.</strong> Source: <a href=\"http://www.wpfunction.me/\">WPFunction.Me</a>",
					"type" => "S",
					"code" =>
					"function wpfme_footer_admin () {
		  echo 'Site developed and maintained by <a href=\"#\" target=\"_blank\">Your Name Here</a> and powered by <a href=\"http://wordpress.org\" target=\"_blank\">WordPress</a>.';
		}
		add_filter('admin_footer_text', 'wpfme_footer_admin');"
				),
				array("title" => "Remove WordPress Version",
					"description" => "Removes the WordPress version number. <br/>MainWP is not responsible for this code. <strong>Use at your own risk.</strong> Source: <a href=\"http://www.wpfunction.me/\">WPFunction.Me</a>",
					"type" => "S",
					"code" => "remove_action('wp_head', 'wp_generator');"
				),
				array("title" => "Obscure Login Error Messages",
					"description" => "Obscure login screen error messages. <br/>MainWP is not responsible for this code. <strong>Use at your own risk.</strong> Source: <a href=\"http://www.wpfunction.me/\">WPFunction.Me</a>",
					"type" => "S",
					"code" =>
					"function wpfme_login_obscure() {
		  return '<strong>Sorry</strong>: Think you have gone wrong somwhere!';
		}
		add_filter( 'login_errors', 'wpfme_login_obscure' );"
				),
				array("title" => "Login Shake Effect",
					"description" => "Removes Wordpress login shake effect when error occurs. <br/>MainWP is not responsible for this code. <strong>Use at your own risk.</strong> Source: <a href=\"http://www.wpfunction.me/\">WPFunction.Me</a>",
					"type" => "S",
					"code" =>
					"function wps_login_error() {
		  remove_action('login_head', 'wp_shake_js', 12);
		}
		add_action('login_head', 'wps_login_error');"
				),
				array("title" => "Count Published Posts",
					"description" => "Displays the number of published posts on the child site. <br/>MainWP is not responsible for this code. <strong>Use at your own risk.</strong> Source: <a href=\"http://www.wpfunction.me/\">WPFunction.Me</a>",
					"type" => "R",
					"code" =>
					"include_once(ABSPATH . WPINC . '/pluggable.php');
		\$count_posts = wp_count_posts();
		echo get_bloginfo('name').' has '.\$count_posts->publish.' published posts.';"
				),
		);

		if ( ! get_option( 'mainwp_code_snippets_added_default_snippets' ) ) {
			update_option( 'mainwp_code_snippets_added_default_snippets', true );
			foreach ( $default_snippets as $snippet ) {
				$snippet['snippet_slug'] = MainWP_CS_Utility::rand_string( 5 );
				$this->update_codesnippet( $snippet );
			}
		}
		update_option( 'mainwp_code_snippets_db_version', $this->mainwp_code_snippets_db_version );

	}

	public function update_codesnippet( $snippet, $userId = null ) {
		/** @var $wpdb wpdb */
		global $wpdb;
		if ( null == $userId && ( true == apply_filters( 'mainwp_is_multi_user', false ) ) ) {
			global $current_user;
			$userId = $current_user->ID;
		}

		$id = isset( $snippet['id'] ) ? $snippet['id'] : 0 ;

		$snippet['date'] = time();
		//print_r($snippet);
		if ( $id ) {
			if ( $userId ) {
				if ( $wpdb->update( $this->table_name( 'codesnippet' ), $snippet, array( 'id' => intval( $id ), 'userid' => $userId ) ) ) {
					return $this->get_codesnippet_by( 'id', $id );
				}
			} else {
				if ( $wpdb->update( $this->table_name( 'codesnippet' ), $snippet, array( 'id' => intval( $id ) ) ) ) {
					return $this->get_codesnippet_by( 'id', $id );
				}
			}
		} else {
			if ( $userId ) {
				$snippet['userid'] = $userId;
			}
			if ( $wpdb->insert( $this->table_name( 'codesnippet' ), $snippet ) ) {
				return $this->get_codesnippet_by( 'id', $wpdb->insert_id );
			}
		}
		return false;
	}

	public function get_codesnippet_by( $by = 'all', $value = null, $userId = null, $orderby = null ) {
		global $wpdb;

		if ( 'all' !== $by && empty( $value ) ) {
			return false;
		}

		if ( null == $userId && ( true == apply_filters( 'mainwp_is_multi_user', false ) ) ) {
			global $current_user;
			$userId = $current_user->ID;
		}

		if ( 'all' == $by ) {
			if ( null == $orderby ) {
				$orderby = ' ORDER BY date DESC ';
			} else {
				$orderby = " ORDER BY $orderby ";
			}

			$sql = 'SELECT * FROM ' . $this->table_name( 'codesnippet' ) . ' WHERE 1 = 1 ' . ( $userId ? ' AND userid = ' . $userId : '' ) . $orderby;
			return $wpdb->get_results( $sql );
		} else if ( 'title' == $by ) {
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'codesnippet' ) . ' WHERE `title` = %s ' . ($userId ? ' AND userid = ' . $userId : ''), $value );
			$snippet = $wpdb->get_row( $sql );
			return $snippet;
		} else if ( 'id' == $by ) {
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'codesnippet' ) . ' WHERE `id` = %d ' . ($userId ? ' AND userid = ' . $userId : ''), $value );
			$snippet = $wpdb->get_row( $sql );
			return $snippet;
		}
		return false;
	}

	public function remove_codesnippet( $csId, $userId = null ) {
		/** @var $wpdb wpdb */
		global $wpdb;

		if ( ( null == $userId ) && ( true == apply_filters( 'mainwp_is_multi_user', false ) ) ) {
			global $current_user;
			$userId = $current_user->ID;
		}

		if ( null == $userId ) {
			$deleted = $wpdb->query( 'DELETE FROM ' . $this->table_name( 'codesnippet' ) . ' WHERE id = ' . $csId );
		} else {
			$deleted = $wpdb->query( 'DELETE FROM ' . $this->table_name( 'codesnippet' ) . ' WHERE userid=' . $userId . ' AND id = ' . $csId );
		}

		if ( $deleted ) {
			return true;
		}

		return false;
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

	public static function fetch_object( $result ) {
		if ( self::use_mysqli() ) {
			return mysqli_fetch_object( $result );
		} else {
			return mysql_fetch_object( $result );
		}
	}

	public static function free_result( $result ) {
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

	public static function num_rows( $result ) {
		if ( self::use_mysqli() ) {
			return mysqli_num_rows( $result );
		} else {
			return mysql_num_rows( $result );
		}
	}

	public static function is_result( $result ) {
		if ( self::use_mysqli() ) {
			return ( $result instanceof mysqli_result );
		} else {
			return is_resource( $result );
		}
	}

	public function get_results_result( $sql ) {
		if ( null == $sql ) {
			return null;
		}
		/** @var $wpdb wpdb */
		global $wpdb;
		return $wpdb->get_results( $sql, OBJECT_K );
	}
}
