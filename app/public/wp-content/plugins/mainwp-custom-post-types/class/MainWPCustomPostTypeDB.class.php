<?php

class MainWPCustomPostTypeDB {
	/**
	 * @var string
	 */
	private $mainwp_custom_post_type_db_version = '0.2';
	/**
	 * @var null
	 */
	private static $instance = null;
	/**
	 * @var string
	 */
	private $table_prefix;

	/**
	 * @return null
	 */
	public static function Instance() {
		if ( self::$instance == null ) {
			self::$instance = new MainWPCustomPostTypeDB();
		}

		return self::$instance;
	}

	/**
	 *
	 */
	public function __construct() {
		global $wpdb;
		$this->table_prefix = $wpdb->prefix . 'mainwp_';
	}

	/**
	 * @param $suffix
	 *
	 * @return string
	 */
	public function tableName( $suffix ) {
		return $this->table_prefix . $suffix;
	}

	/**
	 * @return bool
	 *
	 * Plugin instalation
	 * Return true on success
	 */
	public function install() {
		global $wpdb;
		$currentVersion = get_site_option( 'mainwp_custom_post_type_db_version' );

		if ( $currentVersion == $this->mainwp_custom_post_type_db_version ) {
			// No migrations right now
			return true;
		}

		$charset_collate = $wpdb->get_charset_collate();

		$sql = array();

		$tbl = 'CREATE TABLE `' . $this->tableName( 'custom_post_type_connections' ) . '` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`dash_post_id` int(11) NOT NULL DEFAULT 0,
			`child_post_id` int(11) NOT NULL DEFAULT 0,
			`website_id` int(11) NOT NULL DEFAULT 0';
		if ( $currentVersion == '' ) {
			$tbl .= ',
			PRIMARY KEY  (`id`)';
		}
		$tbl  .= '	)' . $charset_collate;
		$sql[] = $tbl;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		foreach ( $sql as $query ) {
			dbDelta( $query );
		}

		update_option( 'mainwp_custom_post_type_db_version', $this->mainwp_custom_post_type_db_version );

		return true;
	}

	/**
	 * @return bool
	 */
	public function uninstall() {
		// Nothing here right now
		global $wpdb;

		// $wpdb->query( 'DROP TABLE IF EXISTS ' . $this->tableName( 'custom_post_type_connections' ) );

		return true;
	}

	/**
	 * Our get_post implementation
	 * Sometime this function doesn't return post info
	 **/
	public function get_posts_id_and_title_by_ids( $post_ids ) {
		global $wpdb;

		$post_ids = array_map( 'intval', (array) $post_ids );

		return $wpdb->get_results( 'SELECT `ID`, `post_title` FROM ' . $wpdb->prefix . 'posts WHERE `ID` IN (' . implode( ', ', $post_ids ) . ')', OBJECT );
	}

	/**
	 * Get post info
	 **/
	public function get_post_by_id( $post_id ) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'posts WHERE `ID` = %d', $post_id ), ARRAY_A );
	}

	/**
	 * Get post_meta infos
	 **/
	public function get_post_meta_by_post_id( $post_id ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare( 'SELECT `meta_key`, `meta_value` FROM ' . $wpdb->prefix . 'postmeta WHERE `post_id` = %d', $post_id ), ARRAY_A );
	}

	/**
	 * wp_term_relationships
	 * wp_term_taxonomy      object_id <--- our post ID
	 * wp_terms      term_taxonomy_id <----- term_taxonomy_id
	 *  term_id <--- term_id
	 *  name         taxonomy
	 *  slug         description
	 *               parent
	 *
	 * We don't support wp_term_relationships.term_order and wp_terms.term_group
	 */
	public function get_post_terms_by_post_id( $post_id ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare( 'SELECT `a`.`term_order`, `b`.`taxonomy`, `b`.`description`, `b`.`parent`, `c`.`name`, `c`.`slug`, `c`.`term_group` FROM ' . $wpdb->prefix . 'term_relationships as a INNER JOIN ' . $wpdb->prefix . 'term_taxonomy as b ON `a`.`term_taxonomy_id` = `b`.`term_taxonomy_id` INNER JOIN ' . $wpdb->prefix . 'terms as c ON `b`.`term_id` = `c`.`term_id` WHERE `a`.`object_id` = %d AND b.taxonomy <> "category"', $post_id ), ARRAY_A );
	}

	/**
	 * Support post editing
	 * we need to store what ID this post has on child website
	 */
	public function insert_connection( $dash_post_id, $child_post_id, $website_id ) {
		global $wpdb;

		$insert_time = time();

		return $wpdb->insert(
			$this->tableName( 'custom_post_type_connections' ),
			array(
				'dash_post_id'  => $dash_post_id,
				'child_post_id' => $child_post_id,
				'website_id'    => $website_id,
			),
			array( '%d', '%d', '%d' )
		);
	}

	/**
	 * What post_id this post has on child website
	 **/
	public function get_connection_by_dash_and_website_id( $dash_post_id, $website_id ) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $this->tableName( 'custom_post_type_connections' ) . ' WHERE `dash_post_id` = %d AND `website_id` = %d', $dash_post_id, $website_id ), ARRAY_A );
	}

	/**
	 * When user manually delete post on child website we delete connection with this post on dashboard
	 **/
	public function delete_connection_by_child_post_id_and_website_id( $child_post_id, $website_id ) {
		global $wpdb;

		return $wpdb->delete(
			$this->tableName( 'custom_post_type_connections' ),
			array(
				'child_post_id' => $child_post_id,
				'website_id'    => $website_id,
			),
			array( '%d', '%d' )
		);
	}

	/**
	 * Add support for editing custom post from MainWP PostsSearch_handler
	 **/
	public function get_dash_post_ids_from_connections( $website_id, $child_post_ids ) {
		global $wpdb;
		$child_post_ids = array_map( 'intval', (array) $child_post_ids );
		if ( count( $child_post_ids ) > 0 ) {
			return $wpdb->get_results( $wpdb->prepare( 'SELECT `dash_post_id`, `child_post_id` FROM ' . $this->tableName( 'custom_post_type_connections' ) . ' WHERE `website_id` = %d AND `child_post_id` IN (' . implode( ', ', $child_post_ids ) . ')', $website_id ), OBJECT );
		}
		return null;
	}
}
