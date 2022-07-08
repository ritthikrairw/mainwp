<?php

class Manage_Favorites {
	private static $instance = null;
	private $errors          = array();

	function __construct() {

	}

	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new Manage_Favorites();
		}
		return self::$instance;
	}

	static function add_favorite() {
		global $current_user;
		if ( intval( $current_user->ID ) && ! empty( $_POST['type'] ) && ! empty( $_POST['slug'] ) ) {
			include_once ABSPATH . '/wp-admin/includes/plugin-install.php';
			if ( 'plugin' == $_POST['type'] ) {
				$api = plugins_api(
					'plugin_information',
					array(
						'slug'   => $_POST['slug'],
						'fields' => array( 'sections' => false ),
					)
				);
			} else {
				$api = themes_api(
					'theme_information',
					array(
						'slug'   => $_POST['slug'],
						'fields' => array( 'sections' => false ),
					)
				);
			}

			if ( ! is_wp_error( $api ) ) {
				$author = $api->author;
				$result = Favorites_Extension_DB::get_instance()->add_favorite( $current_user->ID, $_POST['type'], $_POST['slug'], $api->name, $author, $api->version );
				if ( 'NEWER_EXISTED' == $result ) {
					die( $result );
				} elseif ( $result ) {
					die( 'SUCCESS' );
				}
			} else {
				die( 'FAIL' );
			}
		}
		die( 'FAIL' );
	}

	public static function add_group() {
		global $current_user;
		if ( isset( $_POST['newName'] ) ) {
			$groupId = Favorites_Extension_DB::get_instance()->add_group( $current_user->ID, self::check_group_name( $_POST['type'], $_POST['newName'] ), $_POST['type'] );
			$group   = Favorites_Extension_DB::get_instance()->get_favorites_group_by_id( $groupId );
			self::create_group_item( $_POST['type'], $group );
			die();
		}
		die( 'ERROR' );
	}

	protected static function check_group_name( $type, $groupName, $groupId = null ) {
		if ( '' == $groupName ) {
			$groupName = __( 'New Group', 'mainwp-favorites-extension' );
		}
		$cnt = null;
		if ( preg_match( '/(.*) \(\d\)/', $groupName, $matches ) ) {
			$groupName = $matches[1];
		}
		$group = Favorites_Extension_DB::get_instance()->get_group_by_name_for_user( $type, $groupName );
		while ( $group && ( ( null == $groupId ) || ( $group->id != $groupId ) ) ) {
			if ( null == $cnt ) {
				$cnt = 1;
			} else {
				$cnt ++;
			}
			$group = Favorites_Extension_DB::get_instance()->get_group_by_name_for_user( $type, $groupName . ' (' . $cnt . ')' );
		}
		return $groupName . ( null == $cnt ? '' : ' (' . $cnt . ')' );
	}

	private static function create_group_item( $type, $group ) {
		if ( is_array( $group ) ) {
			$group = json_decode( json_encode( $group ), false );
		}
		?>
		<a class="item" id="<?php echo base64_encode( $group->id ); ?>">
		<div class="ui small label"><?php echo property_exists( $group, 'nrsites' ) ? $group->nrsites : 0; ?></div>
			<input type="hidden" value="<?php echo stripslashes( $group->name ); ?>" id="mainwp-hidden-group-name" />
			<input type="hidden" value="<?php echo base64_encode( $group->id ); ?>" id="mainwp-hidden-group-id" />
		<?php echo stripslashes( $group->name ); ?>
	  </a>
		<?php
	}

	public static function delete_group() {
		if ( isset( $_POST['groupId'] ) && Favorites_Extension::ctype_digit( base64_decode( $_POST['groupId'] ) ) ) {
			$group = Favorites_Extension_DB::get_instance()->get_favorites_group_by_id( base64_decode( $_POST['groupId'] ) );
			if ( Favorites_Extension::can_edit_group( $group ) ) {
				// Remove from DB
				$nr = Favorites_Extension_DB::get_instance()->remove_group( $group->id );

				if ( $nr > 0 ) {
					die( 'OK' );
				}
			}
		}
		die( 'ERROR' );
	}

	public static function rename_group() {
		if ( isset( $_POST['groupId'] ) && Favorites_Extension::ctype_digit( base64_decode( $_POST['groupId'] ) ) ) {
			$group = Favorites_Extension_DB::get_instance()->get_favorites_group_by_id( base64_decode( $_POST['groupId'] ) );
			if ( Favorites_Extension::can_edit_group( $group ) ) {
				$name = $_POST['newName'];
				if ( '' == $name ) {
					$name = $group->name;
				}
				$name = self::check_group_name( $_POST['type'], $name, $group->id );
				// update group
				$nr = Favorites_Extension_DB::get_instance()->update_group( $group->id, $name );

				// Reload group
				$group = Favorites_Extension_DB::get_instance()->get_favorites_group_by_id( $group->id );
				die( $group->name );
			}
		}
	}


	public static function on_load_page() {

	}

	static function render_settings() {
		$settings      = Favorites_Extension::get_instance()->init_settings();
		$custom_folder = isset( $settings['custom_folder'] ) ? $settings['custom_folder'] : '';
		?>
	<form method="post" class="ui form">
			<div class="ui hidden divider"></div>
			<h3 class="ui dividing header"><?php echo __( 'Favorites Settings', 'mainwp-favorites-extension' ); ?></h3>
	  <div class="ui grid field">
		<label class="six wide column middle aligned"><?php _e( 'Custom Favorites folder', 'mainwp-favorites-extension' ); ?></label>
		<div class="ten wide column">
		  <input type="text" name="custom_folder" value="<?php echo esc_attr( $custom_folder ); ?>" placeholder="<?php echo __( 'For example: ', 'mainwp-favorites-extension' ) . get_home_path() . __( 'custom/directory/', 'mainwp-favorites-extension' ); ?>" />
				</div>
	  </div>
			<div class="ui divider"></div>
	  <input type="hidden" name="favor_nonce" value="<?php echo wp_create_nonce( 'favor-nonce' ); ?>"/>
	  <input type="submit" class="ui big green right floated button" name="save_settings" value="<?php esc_attr_e( 'Save Settings', 'mainwp-favorites-extension' ); ?>">
	</form>
		<?php
	}

	public function uploadFile( $file ) {
		header( 'Content-Description: File Transfer' );
		if ( self::endsWith( $file, '.tar.gz' ) ) {
			header( 'Content-Type: application/x-gzip' );
			header( 'Content-Encoding: gzip' );
		} else {
			header( 'Content-Type: application/octet-stream' );
		}
		header( 'Content-Disposition: attachment; filename="' . basename( $file ) . '"' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . filesize( $file ) );
		while ( @ob_get_level() ) {
			@ob_end_clean();
		}
		$this->readfile_chunked( $file );
		exit();
	}

	function readfile_chunked( $filename ) {
		$chunksize = 1024; // how many bytes per chunk
		$handle    = @fopen( $filename, 'rb' );
		if ( $handle === false ) {
			return false;
		}

		while ( ! @feof( $handle ) ) {
			$buffer = @fread( $handle, $chunksize );
			echo $buffer;
			@ob_flush();
			@flush();
			$buffer = null;
		}

		return @fclose( $handle );
	}

	public static function endsWith( $haystack, $needle ) {
		$length = strlen( $needle );

		if ( $length == 0 ) {
			return true;
		}

		return ( substr( $haystack, - $length ) === $needle );
	}

	public static function get_favorites() {
		if ( isset( $_POST['groupId'] ) && Favorites_Extension::ctype_digit( base64_decode( $_POST['groupId'] ) ) ) {
			$group = Favorites_Extension_DB::get_instance()->get_favorites_group_by_id( base64_decode( $_POST['groupId'] ) );

			if ( Favorites_Extension::can_edit_group( $group ) ) {
				$favorites   = Favorites_Extension_DB::get_instance()->get_favorites_by_group_id( $group->id );
				$favoriteIds = array();
				if ( ! empty( $favorites ) ) {
					foreach ( $favorites as $favorite ) {
						$favoriteIds[] = base64_encode( $favorite->id );
					}
				}
				return json_encode( $favoriteIds );
			}
		}
		die( 'ERROR' );
	}

	public static function save_favorite_note() {
		if ( isset( $_POST['favoriteid'] ) && Favorites_Extension::ctype_digit( $_POST['favoriteid'] ) ) {
			$favorite = Favorites_Extension_DB::get_instance()->get_favorite_by( 'id', $_POST['favoriteid'] );
			if ( false !== Favorites_Extension_DB::get_instance()->update_favorite_note( $favorite->id, $_POST['note'] ) ) {
				die( 'SUCCESS' );
			} else {
				die( 'ERROR' );
			}
		}
		die( 'ERROR' );
	}

	public static function save_group_note() {
		if ( isset( $_POST['groupid'] ) && Favorites_Extension::ctype_digit( $_POST['groupid'] ) ) {
			$group = Favorites_Extension_DB::get_instance()->get_group_by( 'id', $_POST['groupid'] );
			if ( false !== Favorites_Extension_DB::get_instance()->update_group_note( $group->id, $_POST['note'] ) ) {
				die( 'SUCCESS' );
			} else {
				die( 'ERROR' );
			}
		}
		die( 'ERROR' );
	}

	public static function update_group() {
		if ( isset( $_POST['groupId'] ) && Favorites_Extension::ctype_digit( base64_decode( $_POST['groupId'] ) ) ) {
			$group = Favorites_Extension_DB::get_instance()->get_favorites_group_by_id( base64_decode( $_POST['groupId'] ) );
			if ( Favorites_Extension::can_edit_group( $group ) ) {
				Favorites_Extension_DB::get_instance()->clear_group( $group->id );
				if ( isset( $_POST['favoriteIds'] ) ) {
					foreach ( $_POST['favoriteIds'] as $favoriteId ) {
						$favorite = Favorites_Extension_DB::get_instance()->get_favorite_by( 'id', base64_decode( $favoriteId ) );
						if ( Favorites_Extension::can_edit_favorite( $favorite ) ) {
							Favorites_Extension_DB::get_instance()->update_group_site( $group->id, $favorite->id );
						}
					}
				}
				die();
			}
		}
	}

	function render_manage() {
		$updated = false;
		if ( isset( $_GET['updated'] ) ) {
			$updated = true;
		}

		$current_tab = 'plugins';

		if ( isset( $_GET['tab'] ) ) {
			if ( $_GET['tab'] == 'plugins' ) {
				$current_tab = 'plugins';
			} elseif ( $_GET['tab'] == 'themes' ) {
				$current_tab = 'themes';
			} elseif ( $_GET['tab'] == 'settings' ) {
				$current_tab = 'settings';
			}
		}

		?>
		<script>
			function createUploaderPlugins() {
				var uploader = new qq.FileUploader( {
					element: document.getElementById( 'favorites-file-uploader-plugin' ),
					action: location.href,
					template: '<div class="qq-uploader">' +
					'<div class="ui secondary center aligned segment">' +
					'<div class="qq-upload-button ui inline button">Add or Upload Favorite Plugins</div>' +
					'<div class="qq-upload-drop-area">Drop here.</div>' +
					'</div>' +
					'</div>' +
					'<ul class="qq-upload-list ui relaxed divided list"></ul>',
					onComplete: function ( id, fileName, result ) {
						favorites_setting_uploadbulk_oncomplete( id, fileName, result )
					},
					params: { favorites_do: 'FavoritesInstallBulk-uploadfile', type: 'plugin' }
				} );
			}
			function createUploaderThemes() {
				var uploader = new qq.FileUploader( {
					element: document.getElementById( 'favorites-file-uploader-theme'),
					action: location.href,
					template: '<div class="qq-uploader">' +
					'<div class="ui secondary center aligned segment">' +
					'<div class="qq-upload-button ui inline button">Add or Upload Favorite Themes</div>' +
					'<div class="qq-upload-drop-area">Drop here.</div>' +
					'</div>' +
					'</div>' +
					'<ul class="qq-upload-list ui relaxed divided list"></ul>',
					onComplete: function ( id, fileName, result ) {
						favorites_setting_uploadbulk_oncomplete( id, fileName, result )
					},
					params: { favorites_do: 'FavoritesInstallBulk-uploadfile', type: 'theme' }
				} );
			}
		</script>
		<div class="ui labeled icon inverted menu mainwp-sub-submenu" id="mainwp-favorites-menu">
			<a href="admin.php?page=Extensions-Mainwp-Favorites-Extension&tab=plugins" class="item <?php echo ( $current_tab == 'plugins' || $current_tab == '' ? 'active' : '' ); ?>"><i class="plug icon"></i> <?php _e( 'Favorite Plugins', 'mainwp-favorites-extension' ); ?></a>
			<a href="admin.php?page=Extensions-Mainwp-Favorites-Extension&tab=themes" class="item <?php echo ( $current_tab == 'themes' ? 'active' : '' ); ?>" ><i class="paint brush icon"></i> <?php _e( 'Favorite Themes', 'mainwp-favorites-extension' ); ?></a>
			<a href="admin.php?page=Extensions-Mainwp-Favorites-Extension&tab=settings" class="item <?php echo ( $current_tab == 'settings' ? 'active' : '' ); ?>" ><i class="cog icon"></i> <?php _e( 'Settings', 'mainwp-favorites-extension' ); ?></a>
		</div>
		<div id="mainwp-favorites">
			<?php if ( $current_tab == 'plugins' || $current_tab == '' ) : ?>
				<div class="ui segment" id="mainwp-favorite-plugins-settings">
					<?php Favorites_Extension::validate_favories( 'plugin' ); ?>
					<div id="mainwp-message-zone" class="ui message" style="display:none"></div>
					<input type="hidden" class="favorite-type" value="plugin"/>
					<div class="ui tablet stackable grid">
						<div class="four wide column">
							<?php echo self::render_groups( 'plugin' ); ?>
						</div>
						<div class="twelve wide column">
							<?php echo self::render_favorites( 'plugin' ); ?>
						</div>
					</div>
					<?php echo self::render_upload( 'plugin' ); ?>
				</div>
			<?php endif; ?>
		<?php if ( $current_tab == 'themes' ) : ?>
				<div class="ui segment" id="mainwp-favorite-themes-settings">
					<?php Favorites_Extension::validate_favories( 'theme' ); ?>
					<div id="mainwp-message-zone" class="ui message" style="display:none"></div>
					<input type="hidden" class="favorite-type" value="theme"/>
					<div class="ui tablet stackable grid">
						<div class="four wide column">
							<?php echo self::render_groups( 'theme' ); ?>
						</div>
						<div class="twelve wide column">
							<?php echo self::render_favorites( 'theme' ); ?>
						</div>
					</div>
					<?php echo self::render_upload( 'theme' ); ?>
				</div>
			<?php endif; ?>
			<?php if ( $current_tab == 'settings' ) : ?>
				<div class="ui alt segment" id="mainwp-favorites-settings">
					<div class="mainwp-main-content">
						<div id="mainwp-message-zone" class="ui message" style="display:none"></div>
						<div class="ui message green" <?php echo $updated ? '' : 'style="display:none"'; ?>><i class="close icon"></i><?php echo $updated ? __( 'Settings saved successfully.', 'mainwp-favorites-extension' ) : ''; ?></div>
						<?php self::render_settings(); ?>
					</div>
					<div class="mainwp-side-content">
						<p><?php echo __( 'The MainWP Favorites Extension allows you to store frequently used plugins and themes for quick and easy installation on child sites. In addition, since those favorites are stored locally on your MainWP Dashboard and does not use the WordPress.org Favorites API, your privacy is maintained.', 'mainwp-favorites-extension' ); ?></p>
						<p><?php echo __( 'he Favorites Extension even allows you to group your plugins and themes based on any criteria you want. Say that you use a different set of plugins or a different theme for sites in the “Blue Widget” niche compared to your sites in the “Yellow Widget” niche you can set your Favorite Groups to easily install the correct plugins for the correct niche.', 'mainwp-favorites-extension' ); ?></p>
						<div class="ui yellow message"><?php echo __( 'Changing the favorites directory will remove the Plugins and Themes that are added from the WordPress.org repository.', 'mainwp-favorites-extension' ); ?></div>
						<a href="https://kb.mainwp.com/docs/favorites-extension/" class="ui big green fluid button" target="_blank"><?php echo __( 'Extension Documentation', 'mainwp-favorites-extension' ); ?></a>
					</div>
					<div class="ui clearing hidden divider"></div>
				</div>
		<?php endif; ?>
		</div>
		<?php
	}

	static function render_groups( $type ) {
		?>
		<div class="ui fluid pointing vertical menu" type="<?php echo $type; ?>" id="mainwp-favorite-groups-menu">
			<h4 class="item ui header"><?php echo ucfirst( $type ) . ' ' . __( 'Groups', 'mainwp-favorites-extension' ); ?></h4>
			<?php echo self::get_group_list_content( $type ); ?>
			<div class="item">
				<a href="#" class="ui green button" id="mainwp-add-new-group" data-inverted="" data-tooltip="<?php echo esc_attr( 'Click here to create a new group.', 'mainwp-favorites-extension' ); ?>"><?php echo __( 'New Group', 'mainwp-favorites-extension' ); ?></a>
				<a href="#" class="ui button right floated disabled" id="mainwp-favorites-delete-group"><?php echo __( 'Delete', 'mainwp-favorites-extension' ); ?></a>
				<a href="#" class="ui green right floated basic button disabled" id="mainwp-favorites-rename-group"><?php echo __( 'Rename', 'mainwp-favorites-extension' ); ?></a>
			</div>
		</div>

		<div class="ui mini modal" type="<?php echo $type; ?>" id="mainwp-favorites-create-group-modal">
			<div class="header"><?php echo __( 'Create Group', 'mainwp-favorites-extension' ); ?></div>
			<div class="content">
				<div class="ui form">
					<div class="field">
						<input type="text" value="" name="mainwp-favorites-group-name" id="mainwp-favorites-group-name" placeholder="<?php esc_attr_e( 'Enter group name', 'mainwp-favorites-extension' ); ?>" >
					</div>
				</div>
			</div>
			<div class="actions">
				<div class="ui cancel button"><?php echo __( 'Close', 'mainwp-favorites-extension' ); ?></div>
				<a class="ui green button" id="managefavgroups-savenew" href="#"><?php echo __( 'Create Group', 'mainwp-favorites-extension' ); ?></a>
			</div>
		</div>

		<div class="ui mini modal" type="<?php echo $type; ?>" id="mainwp-favorites-rename-group-modal">
			<div class="header"><?php echo __( 'Rename Group', 'mainwp-favorites-extension' ); ?></div>
			<div class="content">
				<div class="ui form">
					<div class="field">
						<input type="text" value="" name="mainwp-favorites-group-name" id="mainwp-favorites-group-name" placeholder="<?php esc_attr_e( 'Enter group name', 'mainwp-favorites-extension' ); ?>" >
					</div>
				</div>
			</div>
			<div class="actions">
				<div class="ui cancel button"><?php echo __( 'Close', 'mainwp-favorites-extension' ); ?></div>
				<a class="ui green button" id="mainwp-favorites-save-group-name" href="#"><?php echo __( 'Save Group', 'mainwp-favorites-extension' ); ?></a>
			</div>
		</div>
		<?php
	}

	public static function get_group_list_content( $type ) {
		$groups = Favorites_Extension_DB::get_instance()->get_groups_and_count( $type );
		if ( empty( $groups ) ) {
			?>
			<a class="item">
				<?php echo __( 'No groups created.', 'mainwp-favorites-extension' ); ?>
		  </a>
			<?php
		} else {
			foreach ( $groups as $group ) {
				self::create_group_item( $type, $group );
			}
		}
	}

	static function render_favorites( $type ) {
		?>
		<table class="ui tablet stackable table mainwp-favorites-table" type="<?php echo $type; ?>" id="mainwp-favorites-<?php echo $type; ?>-table">
			<thead>
				<tr>
					<th class="collapsing no-sort"><span class="ui checkbox"><input type="checkbox"></span></th>
					<th><?php echo ucfirst( $type ); ?></th>
					<th><?php echo __( 'Version', 'mainwp-favorites-extension' ); ?></th>
					<th><?php echo __( 'Author', 'mainwp-favorites-extension' ); ?></th>
					<th class="collapsing no-sort"><?php echo __( '', 'mainwp-favorites-extension' ); ?></th>
				</tr>
			</thead>
			<tbody class="mainwp-favorites-list">
				<?php echo self::get_favorites_list_content( $type ); ?>
			</tbody>
			<tfoot>
				<tr class="full-width">
					<th colspan="5">
						<input type="button" class="ui green basic disabled button" name="mainwp-favorites-save-selection" id="mainwp-favorites-save-selection" value="<?php esc_attr_e( 'Save Selection', 'mainwp-favorites-extension' ); ?>">
						<a href="<?php echo admin_url( '/admin.php?page=' . ucfirst( $type ) . 'sFavorite' ); ?>" class="ui labeled icon button">
							<i class="download icon"></i>
							<?php echo __( 'Install Favorites', 'mainwp-favorites-extension' ); ?>
						</a>
						<div class="ui labeled icon green right floated button" id="mainwp-favorites-upload-button">
							<i class="upload icon"></i>
							<?php echo __( 'Add New Favorite', 'mainwp-favorites-extension' ); ?>
						</div>
					</th>
				</tr>
			</tfoot>
		</table>
		<div class="ui inverted dimmer">
			<div class="ui loader"></div>
		</div>
		<script type="text/javascript">
		jQuery( '.mainwp-favorites-table' ).DataTable( {
			"searching": true,
			"paging" : false,
			"info" : true,
			"columnDefs": [ { "orderable": false, "targets": "no-sort" } ],
			"order": [ [ 1, "asc" ] ],
			"language" : { "emptyTable": "No favorites added." }
		} );
		</script>
		<?php
	}

	public static function get_favorites_list_content( $type ) {
		$favorites = Favorites_Extension_DB::get_instance()->query( Favorites_Extension_DB::get_instance()->get_sql_favorites_for_current_user( $type ) );
		while ( $favorites && ( $favorite = @Favorites_Extension_DB::fetch_object( $favorites ) ) ) {
			?>
			<tr class="mainwp-favorite mainwp-favorite-<?php echo $type; ?>" id="<?php echo $favorite->id; ?>" type="<?php echo $type; ?>" file="<?php echo $favorite->file; ?>">
				<td><span class="ui checkbox"><input type="checkbox" favorite="<?php echo $type; ?>" value="<?php echo base64_encode( $favorite->id ); ?>" id="<?php echo $favorite->id; ?>"></span></td>
				<td>
					<?php if ( ! empty( $favorite->url ) ) : ?>
					<a href="<?php echo $favorite->url; ?>" target="_blank">
						<?php echo $favorite->name; ?>
					</a>
					<?php else : ?>
						<?php echo $favorite->name; ?>
					<?php endif; ?>
				</td>
				<td><?php echo $favorite->version; ?></td>
				<td><?php echo $favorite->author; ?></td>
				<td class="right aligned"><a class="ui mini button mainwp-favorite-delete mainwp-favorite-<?php echo $type; ?>-delete" onClick="return managefavorite_remove(<?php echo "'" . $favorite->type . "', '" . $favorite->file . "', " . $favorite->id; ?>, this )"><?php echo __( 'Delete', 'mainwp-favorites-extension' ); ?></a></td>
			</tr>
			<?php
		}
		@Favorites_Extension_DB::free_result( $favorites );
	}

	static function render_upload( $type ) {

		?>
		<div class="ui modal" id="mainwp-favorite-upload-modal">
			<div class="header"><?php echo __( 'Upload Favorite ', 'mainwp-favorites-extension' ) . ucfirst( $type ); ?></div>
			<div class="scrolling content">
				<p><?php echo __( 'Upload your favorite', 'mainwp-favorites-extension' ) . ' ' . $type . '(s) ' . __( 'in the ZIP format here.', 'mainwp-favorites-extension' ); ?></p>
				<div id="favorites-file-uploader-<?php echo $type; ?>"></div>
			</div>
			<div class="actions">
				<div class="ui icon labeled green button" id="mainwp-favorites-complete-upload-button">
					<i class="check icon"></i>
					<?php echo __( 'Done', 'mainwp-favorites-extension' ); ?>
				</div>
			</div>
		</div>

		<script>
			// in your app create uploader as soon as the DOM is ready
			// don't wait for the window to load
			<?php if ( 'plugin' == $type ) { ?>
			createUploaderPlugins();
			<?php } else { ?>
			createUploaderThemes();
			<?php } ?>
		</script>
		<?php
	}
}
