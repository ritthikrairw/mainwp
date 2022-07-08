<?php

class Favorites_Themes {
	public static $instance = null;

	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new Favorites_Themes();
		}

		return self::$instance;
	}

	function render_page() {

		$current_tab = 'themes';

		if ( isset( $_GET['tab'] ) ) {
			if ( $_GET['tab'] == 'themes' ) {
				$current_tab = 'themes';
			} elseif ( $_GET['tab'] == 'groups' ) {
				$current_tab = 'groups';
			}
		}
		Favorites_Extension::validate_favories( 'theme' );
		?>
		<div class="ui alt segment" id="mainwp-favorite-themes">
			<div class="mainwp-main-content">
				<div class="ui labeled icon inverted menu mainwp-sub-submenu" id="mainwp-favorite-themes-menu">
					<a href="admin.php?page=ThemesFavorite&tab=themes" class="item <?php echo ( $current_tab == 'themes' || $current_tab == '' ? 'active' : '' ); ?>"><i class="plug icon"></i> <?php _e( 'Favorite Themes', 'mainwp-favorites-extension' ); ?></a>
					<a href="admin.php?page=ThemesFavorite&tab=groups" class="item <?php echo ( $current_tab == 'groups' ? 'active' : '' ); ?>"><i class="folder icon"></i> <?php _e( 'Favorite Themes Groups', 'mainwp-favorites-extension' ); ?></a>
				</div>
				<div class="ui message" id="mainwp-message-zone" style="display:none"></div>
				<?php if ( $current_tab == 'themes' || $current_tab == '' ) : ?>
				<div class="ui segment active" id="mainwp-favorite-themes-tab">
					<table class="ui striped table" id="mainwp-favorite-themes-table">
						<thead>
							<tr>
								<th class="check-column collapsing no-sort"><span class="ui checkbox"><input type="checkbox"></span></th>
								<th><?php echo __( 'Theme', 'mainwp-favorites-extension' ); ?></th>
								<th><?php echo __( 'Version', 'mainwp-favorites-extension' ); ?></th>
								<th><?php echo __( 'Author', 'mainwp-favorites-extension' ); ?></th>
								<th class="collapsing no-sort"><i class="sticky note outline icon"></i></th>
							</tr>
						</thead>
						<tbody>
							<?php $this->get_table_content(); ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
			<?php if ( $current_tab == 'groups' ) : ?>
				<div class="ui segment active" id="mainwp-favorite-themes-groups-tab">
					<table class="ui striped table" id="mainwp-favorite-theme-groups-table">
						<thead>
							<tr>
								<th class="check-column collapsing no-sort"><span class="ui checkbox"><input type="checkbox"></span></th>
								<th><?php echo __( 'Themes Group', 'mainwp-favorites-extension' ); ?></th>
								<th><?php echo __( 'Number of Themes', 'mainwp-favorites-extension' ); ?></th>
								<th class="collapsing no-sort"><i class="sticky note outline icon"></i></th>
							</tr>
						</thead>
						<tbody>
							<?php $this->get_table_groups_content(); ?>
						</tbody>
					</table>
				</div>
				<?php endif; ?>
			</div>
			<div class="mainwp-side-content mainwp-no-padding">
				<div class="mainwp-select-sites">
					<h3 class="header"><?php _e( 'Select Sites', 'mainwp-favorites-extension' ); ?></h3>
					<?php do_action( 'mainwp_select_sites_box' ); ?>
				</div>
				<div class="ui divider"></div>
				<div class="mainwp-search-options">
					<div class="ui header"><?php _e( 'Installation Options', 'mainwp-favorites-extension' ); ?></div>
					<div class="ui form">
						<div class="field">
							<div class="ui toggle checkbox" data-tooltip="<?php esc_attr_e( 'If enabled and the theme already installed on the sites, the already installed version will be overwritten.', 'mainwp-favorites-extension' ); ?>" data-position="left center" data-inverted="">
								<input type="checkbox" value="2" checked="checked" id="chk_overwrite">
								<label for="chk_overwrite"><?php _e( 'Overwrite existing version', 'mainwp-favorites-extension' ); ?></label>
							</div>
						</div>
					</div>
				</div>
				<div class="ui divider"></div>
				<div class="mainwp-search-submit">
					<input type="button" value="<?php esc_attr_e( 'Complete Installation', 'mainwp-favorites-extension' ); ?>" class="ui green big fluid button" onclick="mainwp_favorite_bulk_install( 'theme' ); return false;" id="mainwp-favorite-theme-bulk-install" name="mainwp-favorite-theme-bulk-install">
					<div class="ui hidden fitted divider"></div>
					<a href="<?php echo admin_url( 'admin.php?page=Extensions-Mainwp-Favorites-Extension&tab=themes' ); ?>" class="ui big basic green fluid button"><?php esc_attr_e( 'Manage Favorites', 'mainwp-favorites-extension' ); ?></a>
				</div>
			</div>
			<div class="ui clearing hidden divider"></div>
		</div>

		<script type="text/javascript">
		jQuery( '#mainwp-favorite-themes .ui.table' ).DataTable( {
			"searching": true,
			"paging" : false,
			"info" : true,
			"columnDefs": [ { "orderable": false, "targets": "no-sort" } ],
			"order": [ [ 1, "asc" ] ],
			"language" : { "emptyTable": "No favorites added. Go to the extension page to add Favorite Themes." }
		} );
		</script>

		<div id="mainwp-notes" class="ui modal">
			<div class="header"><?php esc_html_e( 'Notes', 'mainwp' ); ?></div>
			<div class="content" id="mainwp-notes-content">
				<div id="mainwp-notes-html"></div>
				<div id="mainwp-notes-editor" class="ui form" style="display:none;">
					<div class="field">
						<label><?php esc_html_e( 'Edit note', 'mainwp' ); ?></label>
						<textarea id="mainwp-notes-note"></textarea>
					</div>
					<div><?php _e( 'Allowed HTML tags:', 'mainwp' ); ?> &lt;p&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;br&gt;, &lt;hr&gt;, &lt;a&gt;, &lt;ul&gt;, &lt;ol&gt;, &lt;li&gt;, &lt;h1&gt;, &lt;h2&gt; </div>
				</div>
			</div>
			<div class="actions">
				<div class="ui grid">
					<div class="eight wide left aligned middle aligned column">
						<div id="mainwp-notes-status" class="left aligned"></div>
					</div>
					<div class="eight wide column">
						<form>
							<input type="button" class="ui green button" id="mainwp-notes-save" value="<?php esc_attr_e( 'Save note', 'mainwp' ); ?>" style="display:none;"/>
							<input type="button" class="ui basic green button" id="mainwp-notes-edit" value="<?php esc_attr_e( 'Edit', 'mainwp' ); ?>"/>
							<input type="button" class="ui red button" id="mainwp-notes-cancel" value="<?php esc_attr_e( 'Close', 'mainwp' ); ?>"/>
							<input type="hidden" id="mainwp-notes-favroriteid" value=""/>
							<input type="hidden" id="mainwp-notes-type" value=""/>
						</form>
					</div>
				</div>
			</div>
		</div>
		<script type="text/javascript">jQuery( '#mainwp-favorite-themes-menu .item' ).tab();</script>
		<script>
			jQuery(document).ready(function () {
//				favorites_install_set_install_links();
//				group_install_set_install_links();
			});
		</script>
				<?php
				Favorites_Plugins::gen_install_modal();
	}

	public function get_table_content() {
		$favorites = Favorites_Extension_DB::get_instance()->query( Favorites_Extension_DB::get_instance()->get_sql_favorites_for_current_user( 'theme', false ) );

		while ( $favorites && ( $favorite = @Favorites_Extension_DB::fetch_object( $favorites ) ) ) {
			$download_url = ! empty( $favorite->file ) ? Favorites_Extension::get_instance()->getFavoritesDownloadUrl( 'themes', $favorite->file ) : '';
			?>
			<tr id="fav-<?php echo $favorite->id; ?>" favorite_id="<?php echo rawurlencode( $favorite->id ); ?>" favorite_name="<?php echo urlencode( $favorite->name ); ?>" download_url="<?php echo $download_url; ?>">
				<td class="check-column"><span class="ui checkbox"><input type="checkbox" value="<?php echo base64_encode( $favorite->id ); ?>" id="<?php echo $favorite->id; ?>"></span></td>
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
				<td class="center aligned">
					<?php if ( $favorite->note == '' ) : ?>
					<a href="#" id="mainwp-favorite-notes-<?php echo $favorite->id; ?>" class="mainwp-edit-favorite-note" type="favorite"><i class="sticky note outline icon"></i></a>
					<?php else : ?>
					<a href="#" id="mainwp-favorite-notes-<?php echo $favorite->id; ?>" class="mainwp-edit-favorite-note" type="favorite"><i class="sticky note icon"></i></a>
					<?php endif; ?>
					<span style="display:none" id="mainwp-favorite-notes-<?php echo $favorite->id; ?>-note"><?php echo $favorite->note; ?></span>
				</td>
			</tr>
			<?php
		}
	}

	public function get_table_groups_content() {
		$groups = Favorites_Extension_DB::get_instance()->get_groups_and_count( 'theme' );
		foreach ( $groups as $group ) {
			if ( is_array( $group ) ) {
				$group = json_decode( json_encode( $group ), false ); // to convert to object.
			}
			$nrfavs = property_exists( $group, 'nrsites' ) ? $group->nrsites : 0;
			?>
			<tr id="group-<?php echo $group->id; ?>" group_id="<?php echo rawurlencode( $group->id ); ?>" group_name="<?php echo urlencode( $group->name ); ?>">
				<td class="check-column"><span class="ui checkbox"><input type="checkbox" value="<?php echo base64_encode( $group->id ); ?>" id="<?php echo $group->id; ?>"></span></td>
				<td><?php echo stripslashes( $group->name ); ?></td>
				<td class="ui mini"><span data-tooltip="<?php echo $group->favorites; ?>" data-inverted=""><?php echo $nrfavs; ?></span></td>
				<td class="center aligned">
					<?php if ( $group->note == '' ) : ?>
					<a href="#" id="mainwp-favorite-notes-<?php echo $group->id; ?>" class="mainwp-edit-favorite-note" type="group"><i class="sticky note outline icon"></i></a>
					<?php else : ?>
					<a href="#" id="mainwp-favorite-notes-<?php echo $group->id; ?>" class="mainwp-edit-favorite-note" type="group"><i class="sticky note icon"></i></a>
					<?php endif; ?>
					<span style="display:none" id="mainwp-favorite-notes-<?php echo $group->id; ?>-note"><?php echo $group->note; ?></span>
				</td>
			</tr>
			<?php
		}
	}
}

?>
