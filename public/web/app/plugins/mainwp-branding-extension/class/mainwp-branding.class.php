<?php

class MainWP_Branding {
	public static $instance     = null;
	public static $default_opts = array();
	protected $option_handle    = 'mainwp_child_branding_options';
	protected $option;

	public function __construct() {
		$this->option       = get_option( $this->option_handle );
		self::$default_opts = array(
			'textbox_id'    => array(
				'mainwp_branding_plugin_name'           => '',
				'mainwp_branding_plugin_desc'           => '',
				'mainwp_branding_plugin_uri'            => '',
				'mainwp_branding_plugin_author'         => '',
				'mainwp_branding_plugin_author_uri'     => '',
				'mainwp_branding_login_image_link'      => '',
				'mainwp_branding_login_image_title'     => '',
				'mainwp_branding_site_generator'        => '',
				'mainwp_branding_site_generator_link'   => '',
				'mainwp_branding_texts_add_value'       => '',
				'mainwp_branding_texts_add_replace'     => '',
				'mainwp_branding_button_contact_label'  => __( 'Contact Support', 'mainwp-branding-extension' ),
				'mainwp_branding_submit_button_title'   => __( 'Submit', 'mainwp-branding-extension' ),
				'mainwp_branding_send_email_message'    => __( 'Your Message was successfully submitted.', 'mainwp-branding-extension' ),
				'mainwp_branding_message_return_sender' => __( 'Go back to previous page.', 'mainwp-branding-extension' ),
				'mainwp_branding_support_email'         => '',
			),
			'textbox_class' => array(
				'mainwp_branding_texts_value' => '',
			),
			'textareas'     => array(
				'mainwp_branding_admin_css' => '',
				'mainwp_branding_login_css' => '',
			),
			'tinyMCEs'      => array(
				'mainwp_branding_global_footer'    => '',
				'mainwp_branding_dashboard_footer' => '',
				'mainwp_branding_support_message'  => __( 'Welcome to Support!', 'mainwp-branding-extension' ),
			),
			'checkboxes'    => array(
				'mainwp_branding_site_disable_wp_branding' => false,
				'mainwp_branding_site_override'            => false,
				'mainwp_branding_preserve_branding'        => false,
				'mainwp_branding_hide_child_plugin'        => false,
				'mainwp_branding_disable_change'           => false,
				'mainwp_branding_disable_switching_theme'  => false,
				'mainwp_branding_remove_restore_clone'     => false,
				'mainwp_branding_remove_permalink'         => false,
				'mainwp_branding_remove_mainwp_setting'    => false,
				'mainwp_branding_remove_mainwp_server_info' => false,
				'mainwp_branding_remove_wp_tools'          => false,
				'mainwp_branding_remove_wp_setting'        => false,
				'mainwp_branding_delete_login_image'       => false,
				'mainwp_branding_delete_favico_image'      => false,
				'mainwp_branding_remove_widget_welcome'    => false,
				'mainwp_branding_remove_widget_glance'     => false,
				'mainwp_branding_remove_widget_activity'   => false,
				'mainwp_branding_remove_widget_quick'      => false,
				'mainwp_branding_remove_widget_news'       => false,
				'mainwp_branding_hide_nag_update'          => false,
				'mainwp_branding_hide_screen_options'      => false,
				'mainwp_branding_hide_help_box'            => false,
				'mainwp_branding_hide_metabox_post_excerpt' => false,
				'mainwp_branding_hide_metabox_post_slug'   => false,
				'mainwp_branding_hide_metabox_post_tags'   => false,
				'mainwp_branding_hide_metabox_post_author' => false,
				'mainwp_branding_hide_metabox_post_comments' => false,
				'mainwp_branding_hide_metabox_post_revisions' => false,
				'mainwp_branding_hide_metabox_post_discussion' => false,
				'mainwp_branding_hide_metabox_post_categories' => false,
				'mainwp_branding_hide_metabox_post_custom_fields' => false,
				'mainwp_branding_hide_metabox_post_trackbacks' => false,
				'mainwp_branding_hide_metabox_page_custom_fields' => false,
				'mainwp_branding_hide_metabox_page_author' => false,
				'mainwp_branding_hide_metabox_page_discussion' => false,
				'mainwp_branding_hide_metabox_page_revisions' => false,
				'mainwp_branding_hide_metabox_page_attributes' => false,
				'mainwp_branding_hide_metabox_page_slug'   => false,
				'mainwp_branding_show_support_button'      => false,
				'mainwp_branding_button_in_top_admin_bar'  => true,
				'mainwp_branding_button_in_admin_menu'     => true,
			),
		);
	}

	public static function render() {

		$website       = null;
		$is_individual = false;

		if ( self::is_managesites_subpage() ) {
			if ( isset( $_GET['id'] ) && ! empty( $_GET['id'] ) ) {
				global $mainWPBrandingExtensionActivator;
				$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainWPBrandingExtensionActivator->get_child_file(), $mainWPBrandingExtensionActivator->get_child_key(), array( $_GET['id'] ), array() );
				if ( is_array( $dbwebsites ) && ! empty( $dbwebsites ) ) {
					$website = current( $dbwebsites );
				}
			}
			if ( empty( $website ) ) {
				do_action( 'mainwp_pageheader_sites', 'WhiteLabel' );
				echo '<div class="ui message yellow">' . __( 'Site not found.', 'mainwp-branding-extension' ) . '</div>';
				do_action( 'mainwp_pagefooter_sites', 'WhiteLabel' );
				return;
			}
			$is_individual = true;
			do_action( 'mainwp_pageheader_sites', 'WhiteLabel' );
		}

		$general_update = get_option( 'mainwp_branding_need_to_general_update', false );

		?>
			<div class="ui labeled icon inverted menu mainwp-sub-submenu" id="mainwp-branding-menu">
			<a href="#" class="item active" data-tab="braning-extension-settings"><i class="cog icon"></i> <?php _e( 'General Settings', 'mainwp-branding-extension' ); ?></a>
			<a href="#" class="item" data-tab="branding-options-settings"><i class="tags icon"></i> <?php _e( 'White Label', 'mainwp-branding-extension' ); ?></a>
			<a href="#" class="item" data-tab="branding-remove-functions-settings"><i class="ban icon"></i> <?php _e( 'Remove & Disable', 'mainwp-branding-extension' ); ?></a>
			<a href="#" class="item" data-tab="branding-wp-options-settings"><i class="wordpress icon"></i> <?php _e( 'WordPress Options', 'mainwp-branding-extension' ); ?></a>
			<a href="#" class="item" data-tab="branding-support-options-settings"><i class="life ring outline icon"></i> <?php _e( 'Support Form', 'mainwp-branding-extension' ); ?></a>
			</div>
		<div class="ui segment" id="mainwp-branding-settings">

				<?php if ( isset( $_GET['updated'] ) ) : ?>
			<div class="ui message green"><i class="close icon"></i><?php _e( 'White Label settings saved successfully.', 'mainwp-branding-extension' ); ?></div>
			<?php endif; ?>
		<div id="mainwp-branding-message-zone" class="ui message" style="display:none"><</div>
				<?php
				if ( $general_update ) {
					delete_option( 'mainwp_branding_need_to_general_update' );
					$result = self::prepare_save_settings();
					if ( 'NOCHILDSITE' === $result ) {
						?>
						<div class="ui message yellow"><i class="icon close"></i><?php _e( 'No child sites have been found.', 'mainwp-branding-extension' ); ?></div>
						<?php
					}
				}

				?>
				<form method="post" enctype="multipart/form-data" id="mainwp-branding-settings-page-form" class="ui form">
					<?php self::render_settings( $website, $is_individual ); ?>
			<div class="mainwp-form-footer">
					<div class="ui divider"></div>
				<input type="hidden" name="branding_submit_nonce" value="<?php echo wp_create_nonce( 'branding_nonce' ); ?>" />
				<input type="submit" name="submit" id="branding_submit_btn" class="ui big green button" value="<?php esc_attr_e( 'Save Settings', 'mainwp-branding-extension' ); ?>"/>
				<input type="button" name="mwp_branding_reset_btn" id="mwp_branding_reset_btn" class="ui big button" value="<?php esc_attr_e( 'Reset Settings', 'mainwp-branding-extension' ); ?>"/>
			</div>
				</form>

		</div>
		<?php
		if ( $is_individual ) {
			do_action( 'mainwp_pagefooter_sites', 'WhiteLabel' );
		}
	}

	public static function is_managesites_subpage( $tabs = array() ) {
		if ( isset( $_GET['page'] ) && ( 'ManageSitesWhiteLabel' == $_GET['page'] || 'managesites' == $_GET['page'] ) ) {
			return true;
		}
		return false;
	}

	public static function prepare_save_settings() {
		global $mainWPBrandingExtensionActivator;
		$dbwebsites = apply_filters( 'mainwp_getsites', $mainWPBrandingExtensionActivator->get_child_file(), $mainWPBrandingExtensionActivator->get_child_key(), null );
		if ( is_array( $dbwebsites ) && count( $dbwebsites ) > 0 ) {
			?>
			<div class="ui modal" id="mainwp-braning-processing-modal">
				<div class="header"><?php echo __( 'White Label', 'mainwp-branding-extension' ); ?></div>
				<div class="ui green progress mainwp-modal-progress">
					<div class="bar"><div class="progress"></div></div>
					<div class="label"></div>
				</div>
				<div class="scrolling content">
					<div class="ui relaxed divided list">
						<?php foreach ( $dbwebsites as $website ) : ?>
						<div class="item mainwpBrandingSitesItem" siteid="<?php echo $website['id']; ?>" status="queue">
							<div class="ui grid">
								<div class="two column row">
									<div class="column"><a href="<?php echo 'admin.php?page=managesites&dashboard=' . $website['id']; ?>"><?php echo stripslashes( $website['name'] ); ?></a></div>
									<div class="right aligned column"><span class="status"><i class="clock outline icon"></i></span></div>
								</div>
							</div>
						</div>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="actions">
					<div class="ui cancel button"><?php echo __( 'Close', 'mainwp-branding-extension' ); ?></div>
				</div>
			</div>
			<script>
				jQuery( document ).ready( function ($) {
					jQuery( '#mainwp-braning-processing-modal' ).modal( 'show' );
					mainwp_branding_start_next();
				})
			</script>
			<?php
			return true;
		} else {
			return 'NOCHILDSITE';
		}
	}

	public function init() {
		add_action( 'wp_ajax_mainwp_branding_performbrandingchildplugin', array( $this, 'ajax_save_settings' ) );
	}

	public function get_option( $key = null, $default = '' ) {
		if ( isset( $this->option[ $key ] ) ) {
			return $this->option[ $key ];
		}
		return $default;
	}

	public function set_option( $key, $value ) {
		$this->option[ $key ] = $value;
		return update_option( $this->option_handle, $this->option );
	}

	public static function on_load_page() {

	}

	public static function render_settings( $website = null, $is_individual = false ) {
		?>
		<script type="text/javascript">
			var mainwpBrandingDefaultOpts = <?php echo json_encode( self::$default_opts ); ?>;
		</script>
		<?php
		if ( $is_individual ) {
			if ( get_option( 'mainwp_branding_need_to_update_site', false ) ) {
				delete_option( 'mainwp_branding_need_to_update_site' );
				?>
		<script type="text/javascript">
					jQuery( document ).ready( function ($) {
						mainwp_branding_update_specical_site();
					} );
				</script>
				<?php
			}
			?>
			 <input type="hidden" name="branding_individual_settings_site_id" id="branding_individual_settings_site_id" value="<?php echo $website->id; ?>"  />
			<div class="ui tab active" data-tab="braning-extension-settings">
				<?php if ( self::show_mainwp_message( 'white_lbl_indiv_ext_settings' ) ) { ?>
				<div class="ui info message"><i class="close icon mainwp-notice-dismiss" notice-id="white_lbl_indiv_ext_settings"></i><?php esc_html_e( 'Manage the extension general settings. ', 'mainwp-branding-extension' );  echo sprintf( __( 'Please review the %shelp document%s for detailed information. ', 'mainwp-branding-extension' ), '<a href="https://kb.mainwp.com/docs/white-label-extension/" target="_blank">', '</a>' );?></div>
				<?php } ?>
				<?php self::renderBrandingExtensionSettings( $is_individual ); ?>
			</div>
			<div class="ui tab" data-tab="branding-options-settings">
			<?php if ( self::show_mainwp_message( 'white_lbl_indiv_settings' ) ) { ?>
				<div class="ui info message"><i class="close icon mainwp-notice-dismiss" notice-id="white_lbl_indiv_settings"></i><?php esc_html_e( 'Alter how the plugin appears on your client\'s site. You can easily display your company name, along with your explanation of what the plugin does in place of the normal MainWP credits and author information. You can even select not to allow the MainWP Child plugin to appear at all to your clients. ', 'mainwp-branding-extension' ); echo sprintf( __( 'Please review the %shelp document%s for detailed information.', 'mainwp-branding-extension' ), '<a href="https://kb.mainwp.com/docs/white-label-the-mainwp-child-plugin/" target="_blank">', '</a>' ); ?></div>
				<?php } ?>
				<?php self::renderBrandingOptions( $is_individual ); ?>
			</div>
			<div class="ui tab" data-tab="branding-remove-functions-settings">
			<?php if ( self::show_mainwp_message( 'white_lbl_indiv_remove' ) ) { ?>
				<div class="ui info message"><i class="close icon mainwp-notice-dismiss" notice-id="white_lbl_indiv_remove"></i><?php esc_html_e( 'Restrict the ability of your clients to edit and delete plugins and themes. It creates a more secure place for them to work in. You can use this as a way to prevent unwanted changes by clients with less experience. You can also prevent users from accessing the WP Admin Settings and WordPress Tools sections. ', 'mainwp-branding-extension' ); echo sprintf( __( 'Please review the %shelp document%s for detailed information.', 'mainwp-branding-extension' ), '<a href="https://kb.mainwp.com/docs/removedisable-functions-on-your-child-sites/" target="_blank">', '</a>' ); ?></div>
				<?php } ?>
				<?php self::renderRemoveDisable( $is_individual ); ?>
			</div>
			<div class="ui tab" data-tab="branding-wp-options-settings">
			<?php if ( self::show_mainwp_message( 'white_lbl_indiv_wp' ) ) { ?>
				<div class="ui info message"><i class="close icon mainwp-notice-dismiss" notice-id="white_lbl_indiv_wp"></i><?php esc_html_e( 'Easily white label certain WordPress admin sections, from the admin bar to the dashboard. Here, you can replace the WordPress logo with your company logo and completely customize the admin area, favicons, dashboard, and more. ', 'mainwp-branding-extension' ); echo sprintf( __( 'Please review the %shelp document%s for detailed information.', 'mainwp-branding-extension' ), '<a href="https://kb.mainwp.com/docs/wordpress-white-label-options/" target="_blank">', '</a>' ); ?></div>
				<?php } ?>
				<?php self::renderWordPressBranding( $is_individual ); ?>
			</div>
			<div class="ui tab" data-tab="branding-support-options-settings">
			<?php if ( self::show_mainwp_message( 'white_lbl_indiv_support' ) ) { ?>
				<div class="ui info message"><i class="close icon mainwp-notice-dismiss" notice-id="white_lbl_indiv_support"></i><?php esc_html_e( 'If your clients need support, this section will give them the fastest way to reach you. Here, you can create a contact form and they can use this it directly from the WP Admin interface on their child sites. ', 'mainwp-branding-extension' ); echo sprintf( __( 'Please review the %shelp document%s for detailed information.', 'mainwp-branding-extension' ), '<a href="https://kb.mainwp.com/docs/enable-the-contact-support-feature/" target="_blank">', '</a>' ); ?></div>
			<?php } ?>
				<?php self::renderSupportOptions( $is_individual ); ?>
			</div>
			<script type="text/javascript">
				jQuery( '#mainwp-branding-menu .item' ).tab();
			</script>
			<?php
		} else {
			?>
			<div class="ui tab active" data-tab="braning-extension-settings">
			<?php if ( self::show_mainwp_message( 'white_lbl_ext_settings' ) ) { ?>
				<div class="ui info message"><i class="close icon mainwp-notice-dismiss" notice-id="white_lbl_ext_settings"></i><?php esc_html_e( 'Manage the extension general settings. More options are available in the individual Edit Site screen. ', 'mainwp-branding-extension' ); echo sprintf( __( 'Please review the %shelp document%s for detailed information.', 'mainwp-branding-extension' ), '<a href="https://kb.mainwp.com/docs/white-label-extension/" target="_blank">', '</a>' ); ?></div>
				<?php } ?>
				<?php self::renderBrandingExtensionSettings( $is_individual ); ?>
			</div>
			<div class="ui tab" data-tab="branding-options-settings">
			<?php if ( self::show_mainwp_message( 'white_lbl_settings' ) ) { ?>
				<div class="ui info message"><i class="close icon mainwp-notice-dismiss" notice-id="white_lbl_settings"></i><?php esc_html_e( 'Alter how the plugin appears on your client\'s site. You can easily display your company name, along with your explanation of what the plugin does in place of the normal MainWP credits and author information. You can even select not to allow the MainWP Child plugin to appear at all to your clients. ', 'mainwp-branding-extension' ); echo sprintf( __( 'Please review the %shelp document%s for detailed information.', 'mainwp-branding-extension' ), '<a href="https://kb.mainwp.com/docs/white-label-the-mainwp-child-plugin/" target="_blank">', '</a>' ); ?></div>
				<?php } ?>
				<?php self::renderBrandingOptions( $is_individual ); ?>				
			</div>
			<div class="ui tab" data-tab="branding-remove-functions-settings">
			<?php if ( self::show_mainwp_message( 'white_lbl_remove' ) ) { ?>
				<div class="ui info message"><i class="close icon mainwp-notice-dismiss" notice-id="white_lbl_remove"></i><?php esc_html_e( 'Restrict the ability of your clients to edit and delete plugins and themes. It creates a more secure place for them to work in. You can use this as a way to prevent unwanted changes by clients with less experience. You can also prevent users from accessing the WP Admin Settings and WordPress Tools sections. ', 'mainwp-branding-extension' ); echo sprintf( __( 'Please review the %shelp document%s for detailed information.', 'mainwp-branding-extension' ), '<a href="https://kb.mainwp.com/docs/removedisable-functions-on-your-child-sites/" target="_blank">', '</a>' ); ?></div>
				<?php } ?>
				<?php self::renderRemoveDisable( $is_individual ); ?>
			</div>
			<div class="ui tab" data-tab="branding-wp-options-settings">
			<?php if ( self::show_mainwp_message( 'white_lbl_wp' ) ) { ?>
				<div class="ui info message"><i class="close icon mainwp-notice-dismiss" notice-id="white_lbl_wp"></i><?php esc_html_e( 'Easily white label certain WordPress admin sections, from the admin bar to the dashboard. Here, you can replace the WordPress logo with your company logo and completely customize the admin area, favicons, dashboard, and more. ', 'mainwp-branding-extension' ); echo sprintf( __( 'Please review the %shelp document%s for detailed information.', 'mainwp-branding-extension' ), '<a href="https://kb.mainwp.com/docs/wordpress-white-label-options/" target="_blank">', '</a>' ); ?></div>
				<?php } ?>
				<?php self::renderWordPressBranding( $is_individual ); ?>
			</div>
			<div class="ui mini modal" id="mainwp-branding-text-replace-modal">
				<div class="header"><?php echo __( 'Attention', 'mainwp-branding-extension' ); ?></div>
				<div class="content"></div>
				<div class="actions">
					<div class="ui cancel button"><?php echo __( 'OK', 'mainwp-branding-extension' ); ?></div>
				</div>
			</div>
			<div class="ui tab" data-tab="branding-support-options-settings">
			<?php if ( self::show_mainwp_message( 'white_lbl_support' ) ) { ?>
				<div class="ui info message"><i class="close icon mainwp-notice-dismiss" notice-id="white_lbl_support"></i><?php esc_html_e( 'If your clients need support, this section will give them the fastest way to reach you. Here, you can create a contact form and they can use this it directly from the WP Admin interface on their child sites. ', 'mainwp-branding-extension' ); echo sprintf( __( 'Please review the %shelp document%s for detailed information.', 'mainwp-branding-extension' ), '<a href="https://kb.mainwp.com/docs/enable-the-contact-support-feature/" target="_blank">', '</a>' ); ?></div>
				<?php } ?>
				<?php self::renderSupportOptions( $is_individual ); ?>
			</div>
			<script type="text/javascript">
				jQuery( '#mainwp-branding-menu .item' ).tab();
			</script>
			<?php
		}
	}

	/**
	 * Method show_mainwp_message()
	 *
	 * Check whenther or not to show the MainWP Message.
	 *
	 * @param mixed $notice_id Notice ID.
	 *
	 * @return boolean true|false.
	 */
	public static function show_mainwp_message( $notice_id ) {
		$status = get_user_option( 'mainwp_notice_saved_status' );
		if ( ! is_array( $status ) ) {
			$status = array();
		}
		if ( isset( $status[ $notice_id ] ) ) {
			return false;
		}
		return true;
	}

	static function renderBrandingExtensionSettings( $is_individual ) {
		$websiteid = 0;
		if ( $is_individual ) {
			$websiteid = $_GET['id'];
		}

		$site_branding     = null;
		$disableWPBranding = $override = $preserve = 0;

		if ( ! $is_individual ) {
			$preserve = self::get_instance()->get_option( 'child_preserve_branding' );
		} elseif ( $websiteid ) {
			$site_branding = MainWP_Branding_DB::get_instance()->get_branding_by( 'site_id', $websiteid );
		}

		if ( $is_individual && $site_branding ) {
			$override       = $site_branding->override;
			$extra_settings = unserialize( $site_branding->extra_settings );
			if ( is_array( $extra_settings ) ) {
				$disableWPBranding = isset( $extra_settings['disable_wp_branding'] ) ? $extra_settings['disable_wp_branding'] : 0;
				$preserve          = $extra_settings['preserve_branding'];
			}
		}

		?>
		<div class="ui hidden divider"></div>
		<h3 class="ui dividing header"><?php echo __( 'General Settings', 'mainwp-branding-extension' ); ?></h3>
		<?php if ( $is_individual ) : ?>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Override general settings', 'mainwp-branding-extension' ); ?></label>
		  <div class="ten wide column ui toggle checkbox" data-tooltip="<?php esc_attr_e( 'Select if you want to overwrite White Label general settings for this child site.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<input type="checkbox" id="mainwp_branding_site_override" name="mainwp_branding_site_override" <?php echo ( 0 == $override ? '' : 'checked="checked"' ); ?> value="1"/><label></label>
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Disable WordPress options', 'mainwp-branding-extension' ); ?></label>
		  <div class="ten wide column ui toggle checkbox" data-tooltip="<?php esc_attr_e( 'Enable if you want to skip the WordPress Options custom white label settings.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<input type="checkbox" id="mainwp_branding_site_disable_wp_branding" name="mainwp_branding_site_disable_wp_branding" <?php echo ( 0 == $disableWPBranding ? '' : 'checked="checked"' ); ?> value="1"/><label></label>
			</div>
		</div>
		<?php endif; ?>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Keep white label settings if child site is disconnected', 'mainwp-branding-extension' ); ?></label>
			<div class="ten wide column ui toggle checkbox" data-tooltip="<?php esc_attr_e( 'Select if you want to preserve White Label settings if the child site gets disconnected.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<input type="checkbox" id="mainwp_branding_preserve_branding" name="mainwp_branding_preserve_branding" <?php echo ( 0 == $preserve ? '' : 'checked="checked"' ); ?> value="1"/><label></label>
			</div>
		</div>
		<?php
	}

	static function renderBrandingOptions( $is_individual ) {
		$websiteid = 0;
		if ( $is_individual ) {
			$websiteid = $_GET['id'];
		}

		$site_branding   = null;
		$pluginName      = '';
		$pluginDesc      = '';
		$pluginAuthor    = '';
		$pluginAuthorURI = '';
		$pluginURI       = '';
		$pluginHide      = 0;

		if ( ! $is_individual ) {
			$pluginName      = self::get_instance()->get_option( 'child_plugin_name' );
			$pluginURI       = self::get_instance()->get_option( 'child_plugin_uri' );
			$pluginDesc      = self::get_instance()->get_option( 'child_plugin_desc' );
			$pluginAuthor    = self::get_instance()->get_option( 'child_plugin_author' );
			$pluginAuthorURI = self::get_instance()->get_option( 'child_plugin_author_uri' );
			$pluginHide      = self::get_instance()->get_option( 'child_plugin_hide' );
		} elseif ( $websiteid ) {
			$site_branding = MainWP_Branding_DB::get_instance()->get_branding_by( 'site_id', $websiteid );
		}

		if ( $is_individual && $site_branding ) {
			$header = unserialize( $site_branding->plugin_header );
			if ( is_array( $header ) ) {
				$pluginName      = $header['plugin_name'];
				$pluginDesc      = $header['plugin_desc'];
				$pluginAuthor    = $header['plugin_author'];
				$pluginAuthorURI = $header['author_uri'];
				$pluginURI       = $header['plugin_uri'];
			}
			$pluginHide = $site_branding->hide_child_plugin;
		}

		?>
		<div class="ui hidden divider"></div>
		<h3 class="ui dividing header"><?php echo __( 'MainWP Child White Label Settings', 'mainwp-branding-extension' ); ?></h3>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Visually hide the MainWP Child plugin', 'mainwp-branding-extension' ); ?></label>
			<div class="ten wide column ui toggle checkbox" data-tooltip="<?php esc_attr_e( 'Enable if you want to hide the MainWP Child plugin from the Plugins list.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<input type="checkbox" id="mainwp_branding_hide_child_plugin" name="mainwp_branding_hide_child_plugin" <?php echo( 0 == $pluginHide ? '' : 'checked="checked"' ); ?> value="1"/><label></label>
			</div>
		</div>
		<?php if ( self::show_mainwp_message( 'white_lbl_if_hide' ) ) { ?>

		<div class="ui info message"><i class="close icon mainwp-notice-dismiss" notice-id="white_lbl_if_hide"></i><?php _e( 'Even if you hide the Child Plugin on your Child Sites, you should fill out this section if you use a Reports Extension, or the Report will show MainWP.', 'mainwp-branding-extension' ); ?></div>

		<?php } ?>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Plugin name', 'mainwp-branding-extension' ); ?></label>
			<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Enter a custom plugin name.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<input type="text" name="mainwp_branding_plugin_name" id="mainwp_branding_plugin_name" value="<?php echo esc_attr( stripslashes( $pluginName ) ); ?>"/>
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Plugin description', 'mainwp-branding-extension' ); ?></label>
			<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Enter a custom plugin description.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<input type="text" name="mainwp_branding_plugin_desc" id="mainwp_branding_plugin_desc" value="<?php echo esc_attr( stripslashes( $pluginDesc ) ); ?>"/>
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Plugin URI', 'mainwp-branding-extension' ); ?></label>
			<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Enter a custom plugin URI.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<input type="text" name="mainwp_branding_plugin_uri" id="mainwp_branding_plugin_uri" value="<?php echo esc_attr( stripslashes( $pluginURI ) ); ?>"/>
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Plugin author', 'mainwp-branding-extension' ); ?></label>
			<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Enter a custom plugin author name.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<input type="text" name="mainwp_branding_plugin_author" id="mainwp_branding_plugin_author" value="<?php echo esc_attr( stripslashes( $pluginAuthor ) ); ?>"/>
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Author URI', 'mainwp-branding-extension' ); ?></label>
			<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Enter a custom plugin author URI.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<input type="text" name="mainwp_branding_plugin_author_uri" id="mainwp_branding_plugin_author_uri" value="<?php echo esc_attr( stripslashes( $pluginAuthorURI ) ); ?>"/>
			</div>
		</div>
		<?php
	}

	static function renderRemoveDisable( $is_individual ) {
		$websiteid = 0;
		if ( self::is_managesites_subpage() ) {
			$websiteid = $_GET['id'];
		}

		$site_branding         = null;
		$disableSwitchingTheme = 0;
		$disableChanges        = 0;
		$removeSetting         = 0;
		$removeServerInfo      = 0;
		$removeRestore         = 0;
		$removeWPTools         = 0;
		$removeWPSetting       = 0;
		$removePermalinks      = 0;

		if ( ! $is_individual ) {
			$disableChanges        = self::get_instance()->get_option( 'child_disable_change' );
			$removeRestore         = self::get_instance()->get_option( 'child_remove_restore' );
			$removeSetting         = self::get_instance()->get_option( 'child_remove_setting' );
			$removeServerInfo      = self::get_instance()->get_option( 'child_remove_server_info' );
			$removeWPTools         = self::get_instance()->get_option( 'child_remove_wp_tools' );
			$removeWPSetting       = self::get_instance()->get_option( 'child_remove_wp_setting' );
			$disableSwitchingTheme = self::get_instance()->get_option( 'child_disable_switching_theme' );
			$removePermalinks      = self::get_instance()->get_option( 'child_remove_permalink' );
		} elseif ( $websiteid ) {
			$site_branding = MainWP_Branding_DB::get_instance()->get_branding_by( 'site_id', $websiteid );
		}

		if ( $is_individual && $site_branding ) {
			$disableChanges   = $site_branding->disable_theme_plugin_change;
			$removeRestore    = $site_branding->remove_restore;
			$removeSetting    = $site_branding->remove_setting;
			$removeServerInfo = $site_branding->remove_server_info;
			$removeWPTools    = $site_branding->remove_wp_tools;
			$removeWPSetting  = $site_branding->remove_wp_setting;
			$extra_settings   = unserialize( $site_branding->extra_settings );

			if ( is_array( $extra_settings ) ) {
				$removePermalinks = isset( $extra_settings['remove_permalink'] ) ? $extra_settings['remove_permalink'] : 0;
			}
		}

		?>
		<div class="ui hidden divider"></div>
		<h3 class="ui dividing header"><?php echo __( 'Remove & Disable Options', 'mainwp-branding-extension' ); ?></h3>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Disable theme switching', 'mainwp-branding-extension' ); ?></label>
			<div class="ten wide column ui toggle checkbox" data-tooltip="<?php esc_attr_e( 'If Enabled, the current child site theme will be locked, and nobody will be able to switch the theme.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<input type="checkbox" id="mainwp_branding_disable_switching_theme" name="mainwp_branding_disable_switching_theme" <?php echo ( 0 == $disableSwitchingTheme ? '' : 'checked="checked"' ); ?> value="1" /><label></label>
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Disable theme/plugin changes', 'mainwp-branding-extension' ); ?></label>
			<div class="ten wide column ui toggle checkbox" data-tooltip="<?php esc_attr_e( 'If Enabled, the Plugins and Appearance menus will be removed from the WP Admin menu.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<input type="checkbox" id="mainwp_branding_disable_change" name="mainwp_branding_disable_change" <?php echo ( 0 == $disableChanges ? '' : 'checked="checked"' ); ?> value="1" /><label></label>
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Remove the MainWP Child settings page', 'mainwp-branding-extension' ); ?></label>
			<div class="ten wide column ui toggle checkbox" data-tooltip="<?php esc_attr_e( 'If Enabled, the MainWP Child Settings page will be removed.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<input type="checkbox" id="mainwp_branding_remove_mainwp_setting" name="mainwp_branding_remove_mainwp_setting" <?php echo ( 0 == $removeSetting ? '' : 'checked="checked"' ); ?> value="1" /><label></label>
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Remove the MainWP Server Information page', 'mainwp-branding-extension' ); ?></label>
			<div class="ten wide column ui toggle checkbox" data-tooltip="<?php esc_attr_e( 'If Enabled, the MainWP Child Server Information page will be removed.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<input type="checkbox" id="mainwp_branding_remove_mainwp_server_info" name="mainwp_branding_remove_mainwp_server_info" <?php echo ( 0 == $removeServerInfo ? '' : 'checked="checked"' ); ?> value="1" /><label></label>
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Remove the MainWP Restore (Clone) page', 'mainwp-branding-extension' ); ?></label>
			<div class="ten wide column ui toggle checkbox" data-tooltip="<?php esc_attr_e( 'If Enabled, the MainWP Child Restore (Clone) page will be removed.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<input type="checkbox" id="mainwp_branding_remove_restore_clone" name="mainwp_branding_remove_restore_clone" <?php echo ( 0 == $removeRestore ? '' : 'checked="checked"' ); ?> value="1"  /><label></label>
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Remove the WordPress Tools page', 'mainwp-branding-extension' ); ?></label>
			<div class="ten wide column ui toggle checkbox" data-tooltip="<?php esc_attr_e( 'If Enabled, the Tools menu will be removed from the WP Admin menu.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<input type="checkbox" id="mainwp_branding_remove_wp_tools" name="mainwp_branding_remove_wp_tools" <?php echo ( 0 == $removeWPTools ? '' : 'checked="checked"' ); ?> value="1" /><label></label>
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Remove the WordPress General Settings page', 'mainwp-branding-extension' ); ?></label>
			<div class="ten wide column ui toggle checkbox" data-tooltip="<?php esc_attr_e( 'If Enabled, the Settings menu will be removed from the WP Admin menu.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<input type="checkbox" id="mainwp_branding_remove_wp_setting" name="mainwp_branding_remove_wp_setting" <?php echo ( 0 == $removeWPSetting ? '' : 'checked="checked"' ); ?> value="1" /><label></label>
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Remove the Permalinks Settings page', 'mainwp-branding-extension' ); ?></label>
			<div class="ten wide column ui toggle checkbox" data-tooltip="<?php esc_attr_e( 'If Enabled, the Permalinks menu will be removed from the WP Admin menu.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<input type="checkbox" id="mainwp_branding_remove_permalink" name="mainwp_branding_remove_permalink" <?php echo ( 0 == $removePermalinks ? '' : 'checked="checked"' ); ?> value="1" /><label></label>
			</div>
		</div>
		<?php
	}

	static function renderWordPressBranding( $is_individual ) {
		$websiteid = 0;
		if ( self::is_managesites_subpage() ) {
			$websiteid = $_GET['id'];
		}

		$site_branding       = null;
		$imageLogin          = 0;
		$imageFavico         = 0;
		$hideNag             = 0;
		$hideScreenOpts      = 0;
		$hideHelpBox         = 0;
		$removeWidgetWelcome = 0;
		$removeWidgetGlance  = 0;
		$removeWidgetAct     = 0;
		$removeWidgetQuick   = 0;
		$removeWidgetNews    = 0;
		$hidePostExcerpt     = 0;
		$hidePostSlug        = 0;
		$hidePostTags        = 0;
		$hidePostAuthor      = 0;
		$hidePostComments    = 0;
		$hidePostRevisions   = 0;
		$hidePostDiscussion  = 0;
		$loginImageLink      = '';
		$loginImageTitle     = '';
		$hidePostCategories  = 0;
		$hidePostFields      = 0;
		$hidePostTrackbacks  = 0;
		$hidePageFields      = 0;
		$hidePageAuthor      = 0;
		$hidePageDiscussion  = 0;
		$hidePageRevisions   = 0;
		$hidePageAttributes  = 0;
		$hidePageSlug        = 0;
		$siteGenerator       = '';
		$generatorLink       = '';
		$adminCss            = '';
		$loginCss            = '';
		$textsReplace        = array();
		$globalFooter        = '';
		$dashboardFooter     = '';

		if ( ! $is_individual ) {
			$globalFooter        = self::get_instance()->get_option( 'child_global_footer' );
			$dashboardFooter     = self::get_instance()->get_option( 'child_dashboard_footer' );
			$removeWidgetWelcome = self::get_instance()->get_option( 'child_remove_widget_welcome' );
			$removeWidgetGlance  = self::get_instance()->get_option( 'child_remove_widget_glance' );
			$removeWidgetAct     = self::get_instance()->get_option( 'child_remove_widget_activity' );
			$removeWidgetQuick   = self::get_instance()->get_option( 'child_remove_widget_quick' );
			$removeWidgetNews    = self::get_instance()->get_option( 'child_remove_widget_news' );
			$loginImageLink      = self::get_instance()->get_option( 'child_login_image_link' );
			$loginImageTitle     = self::get_instance()->get_option( 'child_login_image_title' );
			$siteGenerator       = self::get_instance()->get_option( 'child_site_generator' );
			$generatorLink       = self::get_instance()->get_option( 'child_generator_link' );
			$adminCss            = self::get_instance()->get_option( 'child_admin_css' );
			$loginCss            = self::get_instance()->get_option( 'child_login_css' );
			$textsReplace        = self::get_instance()->get_option( 'child_texts_replace' );
			$imageLogin          = self::get_instance()->get_option( 'child_login_image' );
			$imageFavico         = self::get_instance()->get_option( 'child_favico_image' );
			$hideNag             = self::get_instance()->get_option( 'child_hide_nag', 0 );
			$hideScreenOpts      = self::get_instance()->get_option( 'child_hide_screen_opts', 0 );
			$hideHelpBox         = self::get_instance()->get_option( 'child_hide_help_box', 0 );
			$hidePostExcerpt     = self::get_instance()->get_option( 'child_hide_metabox_post_excerpt', 0 );
			$hidePostSlug        = self::get_instance()->get_option( 'child_hide_metabox_post_slug', 0 );
			$hidePostTags        = self::get_instance()->get_option( 'child_hide_metabox_post_tags', 0 );
			$hidePostAuthor      = self::get_instance()->get_option( 'child_hide_metabox_post_author', 0 );
			$hidePostComments    = self::get_instance()->get_option( 'child_hide_metabox_post_comments', 0 );
			$hidePostRevisions   = self::get_instance()->get_option( 'child_hide_metabox_post_revisions', 0 );
			$hidePostDiscussion  = self::get_instance()->get_option( 'child_hide_metabox_post_discussion', 0 );
			$hidePostCategories  = self::get_instance()->get_option( 'child_hide_metabox_post_categories', 0 );
			$hidePostFields      = self::get_instance()->get_option( 'child_hide_metabox_post_custom_fields', 0 );
			$hidePostTrackbacks  = self::get_instance()->get_option( 'child_hide_metabox_post_trackbacks', 0 );
			$hidePageFields      = self::get_instance()->get_option( 'child_hide_metabox_page_custom_fields', 0 );
			$hidePageAuthor      = self::get_instance()->get_option( 'child_hide_metabox_page_author', 0 );
			$hidePageDiscussion  = self::get_instance()->get_option( 'child_hide_metabox_page_discussion', 0 );
			$hidePageRevisions   = self::get_instance()->get_option( 'child_hide_metabox_page_revisions', 0 );
			$hidePageAttributes  = self::get_instance()->get_option( 'child_hide_metabox_page_attributes', 0 );
			$hidePageSlug        = self::get_instance()->get_option( 'child_hide_metabox_page_slug', 0 );
		} elseif ( $websiteid ) {
			$site_branding = MainWP_Branding_DB::get_instance()->get_branding_by( 'site_id', $websiteid );
		}

		if ( $is_individual && $site_branding ) {
			$extra_settings = unserialize( $site_branding->extra_settings );
			if ( is_array( $extra_settings ) ) {
				$globalFooter        = isset( $extra_settings['global_footer'] ) ? $extra_settings['global_footer'] : '';
				$dashboardFooter     = isset( $extra_settings['dashboard_footer'] ) ? $extra_settings['dashboard_footer'] : '';
				$removeWidgetWelcome = isset( $extra_settings['remove_widget_welcome'] ) ? $extra_settings['remove_widget_welcome'] : 0;
				$removeWidgetGlance  = isset( $extra_settings['remove_widget_glance'] ) ? $extra_settings['remove_widget_glance'] : 0;
				$removeWidgetAct     = isset( $extra_settings['remove_widget_activity'] ) ? $extra_settings['remove_widget_activity'] : 0;
				$removeWidgetQuick   = isset( $extra_settings['remove_widget_quick'] ) ? $extra_settings['remove_widget_quick'] : 0;
				$removeWidgetNews    = isset( $extra_settings['remove_widget_news'] ) ? $extra_settings['remove_widget_news'] : 0;
				$loginImageLink      = isset( $extra_settings['login_image_link'] ) ? $extra_settings['login_image_link'] : '';
				$loginImageTitle     = isset( $extra_settings['login_image_title'] ) ? $extra_settings['login_image_title'] : '';
				$siteGenerator       = isset( $extra_settings['site_generator'] ) ? $extra_settings['site_generator'] : '';
				$generatorLink       = isset( $extra_settings['generator_link'] ) ? $extra_settings['generator_link'] : '';
				$adminCss            = isset( $extra_settings['admin_css'] ) ? $extra_settings['admin_css'] : '';
				$loginCss            = isset( $extra_settings['login_css'] ) ? $extra_settings['login_css'] : '';
				$textsReplace        = isset( $extra_settings['texts_replace'] ) ? $extra_settings['texts_replace'] : array();
				$imageLogin          = isset( $extra_settings['login_image'] ) ? $extra_settings['login_image'] : '';
				$imageFavico         = isset( $extra_settings['favico_image'] ) ? $extra_settings['favico_image'] : '';
				$hideNag             = isset( $extra_settings['hide_nag'] ) ? $extra_settings['hide_nag'] : 0;
				$hideScreenOpts      = isset( $extra_settings['hide_screen_opts'] ) ? $extra_settings['hide_screen_opts'] : 0;
				$hideHelpBox         = isset( $extra_settings['hide_help_box'] ) ? $extra_settings['hide_help_box'] : 0;
				$hidePostExcerpt     = isset( $extra_settings['hide_metabox_post_excerpt'] ) ? $extra_settings['hide_metabox_post_excerpt'] : 0;
				$hidePostSlug        = isset( $extra_settings['hide_metabox_post_slug'] ) ? $extra_settings['hide_metabox_post_slug'] : 0;
				$hidePostTags        = isset( $extra_settings['hide_metabox_post_tags'] ) ? $extra_settings['hide_metabox_post_tags'] : 0;
				$hidePostAuthor      = isset( $extra_settings['hide_metabox_post_author'] ) ? $extra_settings['hide_metabox_post_author'] : 0;
				$hidePostComments    = isset( $extra_settings['hide_metabox_post_comments'] ) ? $extra_settings['hide_metabox_post_comments'] : 0;
				$hidePostRevisions   = isset( $extra_settings['hide_metabox_post_revisions'] ) ? $extra_settings['hide_metabox_post_revisions'] : 0;
				$hidePostDiscussion  = isset( $extra_settings['hide_metabox_post_discussion'] ) ? $extra_settings['hide_metabox_post_discussion'] : 0;
				$hidePostCategories  = isset( $extra_settings['hide_metabox_post_categories'] ) ? $extra_settings['hide_metabox_post_categories'] : 0;
				$hidePostFields      = isset( $extra_settings['hide_metabox_post_custom_fields'] ) ? $extra_settings['hide_metabox_post_custom_fields'] : 0;
				$hidePostTrackbacks  = isset( $extra_settings['hide_metabox_post_trackbacks'] ) ? $extra_settings['hide_metabox_post_trackbacks'] : 0;
				$hidePageFields      = isset( $extra_settings['hide_metabox_page_custom_fields'] ) ? $extra_settings['hide_metabox_page_custom_fields'] : 0;
				$hidePageAuthor      = isset( $extra_settings['hide_metabox_page_author'] ) ? $extra_settings['hide_metabox_page_author'] : 0;
				$hidePageDiscussion  = isset( $extra_settings['hide_metabox_page_discussion'] ) ? $extra_settings['hide_metabox_page_discussion'] : 0;
				$hidePageRevisions   = isset( $extra_settings['hide_metabox_page_revisions'] ) ? $extra_settings['hide_metabox_page_revisions'] : 0;
				$hidePageAttributes  = isset( $extra_settings['hide_metabox_page_attributes'] ) ? $extra_settings['hide_metabox_page_attributes'] : 0;
				$hidePageSlug        = isset( $extra_settings['hide_metabox_page_slug'] ) ? $extra_settings['hide_metabox_page_slug'] : 0;
			}
		}

		if ( is_array( $textsReplace ) && count( $textsReplace ) > 0 ) {
			ksort( $textsReplace );
		} else {
			$textsReplace = array();
		}
		?>
		<div class="ui hidden divider"></div>
		<h3 class="ui dividing header"><?php echo __( 'WordPress Options', 'mainwp-branding-extension' ); ?></h3>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Custom login image', 'mainwp-branding-extension' ); ?></label>
			<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Upload a custom image to be shown on the login page.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<input type="file" name="mainwp_branding_login_image_file" id="mainwp_branding_login_image_file" accept="image/*" data-inverted="" data-tooltip="<?php esc_attr_e( "Image must be 500KB maximum. It will be cropped to 310px wide and 70px tall. For best results  us an image of this site. Allowed formats: jpeg, gif and png. Note that animated gifs aren't going to be preserved.", 'mainwp-branding-extension' ); ?>" />
			</div>
		</div>
		<?php if ( ! empty( $imageLogin ) ) : ?>
		<div class="ui grid field">
			<label class="six wide column middle aligned"></label>
			<div class="ten wide column">
				<img class="ui medium image" src="<?php echo esc_attr( $imageLogin ); ?>" /><br/>
				<div class="ui checkbox">
					<input type="checkbox" value="1" id="mainwp_branding_delete_login_image" name="mainwp_branding_delete_login_image"><label for="mainwp_branding_delete_login_image"><?php _e( 'Delete image', 'mainwp-branding-extension' ); ?></label>
				</div>
			</div>
		</div>
		<?php endif; ?>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Custom login image link', 'mainwp-branding-extension' ); ?></label>
			<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Enter a custom login image URL.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<input type="text" name="mainwp_branding_login_image_link" id="mainwp_branding_login_image_link" value="<?php echo esc_attr( stripslashes( $loginImageLink ) ); ?>" />
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Custom login image title', 'mainwp-branding-extension' ); ?></label>
			<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Enter a custom login image title.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<input type="text" name="mainwp_branding_login_image_title" id="mainwp_branding_login_image_title" value="<?php echo esc_attr( stripslashes( $loginImageTitle ) ); ?>" />
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Custom favicon', 'mainwp-branding-extension' ); ?></label>
			<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Upload a custom favicon.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<input type="file" name="mainwp_branding_favico_file" id="mainwp_branding_favico_file" accept="image/*" data-inverted="" data-tooltip="<?php esc_attr_e( "Image must be 500KB maximum. It will be cropped to 16px wide and 16px tall. For best results us an image of this site. Allowed formats: jpeg, gif and png. Note that animated gifs aren't going to be preserved.", 'mainwp-branding-extension' ); ?>" />
			</div>
		</div>
		<?php if ( ! empty( $imageFavico ) ) : ?>
		<div class="ui grid field">
			<label class="six wide column middle aligned"></label>
			<div class="ten wide column">
				<img class="ui medium image" src="<?php echo esc_attr( $imageFavico ); ?>" /><br/>
				<div class="ui checkbox">
					<input type="checkbox" value="1" id="mainwp_branding_delete_favico_image" name="mainwp_branding_delete_favico_image"><label for="mainwp_branding_delete_favico_image"><?php _e( 'Delete favicon', 'mainwp-branding-extension' ); ?></label>
				</div>
			</div>
		</div>
		<?php endif; ?>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Remove dashboard widgets', 'mainwp-branding-extension' ); ?></label>
			<div class="ten wide column ui list">
				<div class="ui checkbox item"><input type="checkbox" value="1" <?php echo ( 1 == $removeWidgetWelcome ) ? ' checked="checked" ' : ''; ?> id="mainwp_branding_remove_widget_welcome" name="mainwp_branding_remove_widget_welcome"><label for="mainwp_branding_remove_widget_welcome"><?php _e( 'Welcome', 'mainwp-branding-extension' ); ?></label></div>
				<div class="ui checkbox item"><input type="checkbox" value="1" <?php echo ( 1 == $removeWidgetGlance ) ? ' checked="checked" ' : ''; ?> id="mainwp_branding_remove_widget_glance" name="mainwp_branding_remove_widget_glance"><label for="mainwp_branding_remove_widget_glance"><?php _e( 'At a Glance', 'mainwp-branding-extension' ); ?></label></div>
				<div class="ui checkbox item"><input type="checkbox" value="1" <?php echo ( 1 == $removeWidgetAct ) ? ' checked="checked" ' : ''; ?> id="mainwp_branding_remove_widget_activity" name="mainwp_branding_remove_widget_activity"><label for="mainwp_branding_remove_widget_activity"><?php _e( 'Activity', 'mainwp-branding-extension' ); ?></label></div>
				<div class="ui checkbox item"><input type="checkbox" value="1" <?php echo ( 1 == $removeWidgetQuick ) ? ' checked="checked" ' : ''; ?> id="mainwp_branding_remove_widget_quick" name="mainwp_branding_remove_widget_quick"><label for="mainwp_branding_remove_widget_quick"><?php _e( 'Quick Draft', 'mainwp-branding-extension' ); ?></label></div>
				<div class="ui checkbox item"><input type="checkbox" value="1" <?php echo ( 1 == $removeWidgetNews ) ? ' checked="checked" ' : ''; ?> id="mainwp_branding_remove_widget_news" name="mainwp_branding_remove_widget_news"><label for="mainwp_branding_remove_widget_news"><?php _e( 'WordPress News', 'mainwp-branding-extension' ); ?></label></div>
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Hide nag updates', 'mainwp-branding-extension' ); ?></label>
			<div class="ten wide column ui toggle checkbox" data-tooltip="<?php esc_attr_e( 'Select if you want to hide notifications about WordPress updates.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<input type="checkbox" id="mainwp_branding_hide_nag_update" name="mainwp_branding_hide_nag_update" <?php echo ( 0 == $hideNag ? '' : 'checked="checked"' ); ?> value="1" /><label></label>
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Hide screen options', 'mainwp-branding-extension' ); ?></label>
			<div class="ten wide column ui toggle checkbox" data-tooltip="<?php esc_attr_e( 'Select if you want to hide the Screen Options tab on your child sites.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<input type="checkbox" id="mainwp_branding_hide_screen_options" name="mainwp_branding_hide_screen_options" <?php echo ( 0 == $hideScreenOpts ? '' : 'checked="checked"' ); ?> value="1" /><label></label>
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Hide help box', 'mainwp-branding-extension' ); ?></label>
			<div class="ten wide column ui toggle checkbox" data-tooltip="<?php esc_attr_e( 'Select if you want to hide Help tab on your child sites.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<input type="checkbox" id="mainwp_branding_hide_help_box" name="mainwp_branding_hide_help_box" <?php echo ( 0 == $hideHelpBox ? '' : 'checked="checked"' ); ?> value="1" /><label></label>
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Hide post meta boxes', 'mainwp-branding-extension' ); ?></label>
			<div class="five wide column ui list">
				<div class="ui checkbox item"><input type="checkbox" value="1" <?php echo ( 1 == $hidePostExcerpt ) ? ' checked="checked" ' : ''; ?> id="mainwp_branding_hide_metabox_post_excerpt" name="mainwp_branding_hide_metabox_post_excerpt"><label for="mainwp_branding_hide_metabox_post_excerpt"><?php _e( 'Excerpt', 'mainwp-branding-extension' ); ?></label></div>
				<div class="ui checkbox item"><input type="checkbox" value="1" <?php echo ( 1 == $hidePostSlug ) ? ' checked="checked" ' : ''; ?> id="mainwp_branding_hide_metabox_post_slug" name="mainwp_branding_hide_metabox_post_slug"><label for="mainwp_branding_hide_metabox_post_slug"><?php _e( 'Slug', 'mainwp-branding-extension' ); ?></label></div>
				<div class="ui checkbox item"><input type="checkbox" value="1" <?php echo ( 1 == $hidePostTags ) ? ' checked="checked" ' : ''; ?> id="mainwp_branding_hide_metabox_post_tags" name="mainwp_branding_hide_metabox_post_tags"><label for="mainwp_branding_hide_metabox_post_tags"><?php _e( 'Tags', 'mainwp-branding-extension' ); ?></label></div>
				<div class="ui checkbox item"><input type="checkbox" value="1" <?php echo ( 1 == $hidePostAuthor ) ? ' checked="checked" ' : ''; ?> id="mainwp_branding_hide_metabox_post_author" name="mainwp_branding_hide_metabox_post_author"><label for="mainwp_branding_hide_metabox_post_author"><?php _e( 'Author', 'mainwp-branding-extension' ); ?></label></div>
				<div class="ui checkbox item"><input type="checkbox" value="1" <?php echo ( 1 == $hidePostComments ) ? ' checked="checked" ' : ''; ?> id="mainwp_branding_hide_metabox_post_comments" name="mainwp_branding_hide_metabox_post_comments"><label for="mainwp_branding_hide_metabox_post_comments"><?php _e( 'Comments', 'mainwp-branding-extension' ); ?></label></div>
			</div>
			<div class="five wide column ui list">
				<div class="ui checkbox item"><input type="checkbox" value="1" <?php echo ( 1 == $hidePostRevisions ) ? ' checked="checked" ' : ''; ?> id="mainwp_branding_hide_metabox_post_revisions" name="mainwp_branding_hide_metabox_post_revisions"><label for="mainwp_branding_hide_metabox_post_revisions"><?php _e( 'Revisions', 'mainwp-branding-extension' ); ?></label></div>
				<div class="ui checkbox item"><input type="checkbox" value="1" <?php echo ( 1 == $hidePostDiscussion ) ? ' checked="checked" ' : ''; ?> id="mainwp_branding_hide_metabox_post_discussion" name="mainwp_branding_hide_metabox_post_discussion"><label for="mainwp_branding_hide_metabox_post_discussion"><?php _e( 'Discussion', 'mainwp-branding-extension' ); ?></label></div>
				<div class="ui checkbox item"><input type="checkbox" value="1" <?php echo ( 1 == $hidePostCategories ) ? ' checked="checked" ' : ''; ?> id="mainwp_branding_hide_metabox_post_categories" name="mainwp_branding_hide_metabox_post_categories"><label for="mainwp_branding_hide_metabox_post_categories"><?php _e( 'Categories', 'mainwp-branding-extension' ); ?></label></div>
				<div class="ui checkbox item"><input type="checkbox" value="1" <?php echo ( 1 == $hidePostFields ) ? ' checked="checked" ' : ''; ?> id="mainwp_branding_hide_metabox_post_custom_fields" name="mainwp_branding_hide_metabox_post_custom_fields"><label for="mainwp_branding_hide_metabox_post_custom_fields"><?php _e( 'Custom fields', 'mainwp-branding-extension' ); ?></label></div>
				<div class="ui checkbox item"><input type="checkbox" value="1" <?php echo ( 1 == $hidePostTrackbacks ) ? ' checked="checked" ' : ''; ?> id="mainwp_branding_hide_metabox_post_trackbacks" name="mainwp_branding_hide_metabox_post_trackbacks"><label for="mainwp_branding_hide_metabox_post_trackbacks"><?php _e( 'Send trackbacks', 'mainwp-branding-extension' ); ?></label></div>
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Hide page meta boxes', 'mainwp-branding-extension' ); ?></label>
			<div class="five wide column ui list">
				<div class="ui checkbox item"><input type="checkbox" value="1" <?php echo ( 1 == $hidePageFields ) ? ' checked="checked" ' : ''; ?> id="mainwp_branding_hide_metabox_page_custom_fields" name="mainwp_branding_hide_metabox_page_custom_fields"><label for="mainwp_branding_hide_metabox_page_custom_fields"><?php _e( 'Custom fields', 'mainwp-branding-extension' ); ?></label></div>
				<div class="ui checkbox item"><input type="checkbox" value="1" <?php echo ( 1 == $hidePageAuthor ) ? ' checked="checked" ' : ''; ?> id="mainwp_branding_hide_metabox_page_author" name="mainwp_branding_hide_metabox_page_author"><label for="mainwp_branding_hide_metabox_page_author"><?php _e( 'Author', 'mainwp-branding-extension' ); ?></label></div>
				<div class="ui checkbox item"><input type="checkbox" value="1" <?php echo ( 1 == $hidePageDiscussion ) ? ' checked="checked" ' : ''; ?> id="mainwp_branding_hide_metabox_page_discussion" name="mainwp_branding_hide_metabox_page_discussion"><label for="mainwp_branding_hide_metabox_page_discussion"><?php _e( 'Discussion', 'mainwp-branding-extension' ); ?></label></div>

			</div>
			<div class="five wide column ui list">
				<div class="ui checkbox item"><input type="checkbox" value="1" <?php echo ( 1 == $hidePageRevisions ) ? ' checked="checked" ' : ''; ?> id="mainwp_branding_hide_metabox_page_revisions" name="mainwp_branding_hide_metabox_page_revisions"><label for="mainwp_branding_hide_metabox_page_revisions"><?php _e( 'Revisions', 'mainwp-branding-extension' ); ?></label></div>
				<div class="ui checkbox item"><input type="checkbox" value="1" <?php echo ( 1 == $hidePageAttributes ) ? ' checked="checked" ' : ''; ?> id="mainwp_branding_hide_metabox_page_attributes" name="mainwp_branding_hide_metabox_page_attributes"><label for="mainwp_branding_hide_metabox_page_attributes"><?php _e( 'Page attributes', 'mainwp-branding-extension' ); ?></label></div>
				<div class="ui checkbox item"><input type="checkbox" value="1" <?php echo ( 1 == $hidePageSlug ) ? ' checked="checked" ' : ''; ?> id="mainwp_branding_hide_metabox_page_slug" name="mainwp_branding_hide_metabox_page_slug"><label for="mainwp_branding_hide_metabox_page_slug"><?php _e( 'Slug', 'mainwp-branding-extension' ); ?></label></div>
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Global footer content', 'mainwp-branding-extension' ); ?></label>
			<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Enter a custom content to show in the child site footer.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<?php
				remove_editor_styles();
				wp_editor(
					stripslashes( $globalFooter ),
					'mainwp_branding_global_footer',
					array(
						'textarea_name' => 'mainwp_branding_global_footer',
						'textarea_rows' => 5,
						'teeny'         => true,
						'media_buttons' => false,
					)
				);
				?>
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Dashboard footer content', 'mainwp-branding-extension' ); ?></label>
			<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Enter a custom content to show in the child site WP Admin footer.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<?php
				remove_editor_styles();
				wp_editor(
					stripslashes( $dashboardFooter ),
					'mainwp_branding_dashboard_footer',
					array(
						'textarea_name' => 'mainwp_branding_dashboard_footer',
						'textarea_rows' => 5,
						'teeny'         => true,
						'media_buttons' => false,
					)
				);
				?>
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Site generator options', 'mainwp-branding-extension' ); ?></label>
			<div class="five wide column" data-tooltip="<?php esc_attr_e( 'Enter a custom generator text.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left"><input type="text" placeholder="<?php esc_attr_e( 'Generator text', 'mainwp-branding-extension' ); ?>" name="mainwp_branding_site_generator" id="mainwp_branding_site_generator" value="<?php echo esc_attr( stripslashes( $siteGenerator ) ); ?>" /></div>
			<div class="five wide column" data-tooltip="<?php esc_attr_e( 'Enter a custom generator link.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left"><input type="text" placeholder="<?php esc_attr_e( 'Generator link', 'mainwp-branding-extension' ); ?>" name="mainwp_branding_site_generator_link" id="mainwp_branding_site_generator_link" value="<?php echo esc_attr( stripslashes( $generatorLink ) ); ?>" /></div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Custom admin CSS', 'mainwp-branding-extension' ); ?></label>
			<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Enter a custom CSS code that will be applied to the WP Admin section on your child sites.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<textarea rows="8" cols="48" name="mainwp_branding_admin_css" id="mainwp_branding_admin_css"><?php echo esc_textarea( stripslashes( $adminCss ) ); ?></textarea>
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Custom login CSS', 'mainwp-branding-extension' ); ?></label>
			<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Enter a custom CSS code that will be applied to the Login page on your child sites.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<textarea rows="8" cols="48" name="mainwp_branding_login_css" id="mainwp_branding_login_css"><?php echo esc_textarea( stripslashes( $loginCss ) ); ?></textarea>
			</div>
		</div>
		<div class="ui grid field mainwp-branding-text-replace-row">
			<label class="six wide column middle aligned"><?php _e( 'Text replace', 'mainwp-branding-extension' ); ?></label>
			<div class="four wide column" data-tooltip="<?php esc_attr_e( 'Enter a text that you want to replace.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<input type="text" id="mainwp_branding_texts_add_value" name="mainwp_branding_texts_add_value" value="" class="mainwp_branding_texts_value" placeholder="<?php esc_attr_e( 'Search...', 'mainwp-branding-extension' ); ?>" />
			</div>
			<div class="four wide column" data-tooltip="<?php esc_attr_e( 'Enter a text that show instead.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<input type="text" id="mainwp_branding_texts_add_replace" name="mainwp_branding_texts_add_replace" value="" class="mainwp_branding_texts_replace" placeholder="<?php esc_attr_e( 'Replace...', 'mainwp-branding-extension' ); ?>" />
			</div>
			<div class="two wide column" data-tooltip="<?php esc_attr_e( 'Click to get another search and replace fieldset.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<a href="#" class="add_text_replace ui green icon button"><i class="plus icon"></i></a>
			</div>
		</div>
		<?php foreach ( $textsReplace as $text => $replace ) : ?>
		<div class="ui grid field mainwp-branding-text-replace-row">
			<label class="six wide column middle aligned"><?php _e( '', 'mainwp-branding-extension' ); ?></label>
			<div class="four wide column">
				<input type="text" name="mainwp_branding_texts_value[]" value="<?php echo esc_attr( stripslashes( $text ) ); ?>" class="mainwp_branding_texts_value" />
			</div>
			<div class="four wide column">
				<input type="text" name="mainwp_branding_texts_replace[]" value="<?php echo esc_attr( stripslashes( $replace ) ); ?>" class="mainwp_branding_texts_replace" />
			</div>
			<div class="two wide column">
				<a href="#" class="restore_text_replace ui icon button"><i class="undo icon"></i></a> <a href="#" class="delete_text_replace ui green basic icon button"><i class="trash icon"></i></a>
			</div>
		</div>
		<?php endforeach; ?>
		<div id="mainwp-branding-text-replace-row-copy" style="display:none">
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php _e( ' ', 'mainwp-branding-extension' ); ?></label>
				<div class="four wide column">
					<input type="text" name="mainwp_branding_texts_value[]" value="" class="mainwp_branding_texts_value" placeholder="<?php esc_attr_e( 'Search...', 'mainwp-branding-extension' ); ?>" />
				</div>
				<div class="four wide column">
					<input type="text" name="mainwp_branding_texts_replace[]" value="" class="mainwp_branding_texts_replace" placeholder="<?php esc_attr_e( 'Replace...', 'mainwp-branding-extension' ); ?>" />
				</div>
				<div class="two wide column">
					<a href="#" class="delete_text_replace ui green basic icon button"><i class="trash icon"></i></a>
				</div>
			</div>
		</div>
		<?php
	}

	static function renderSupportOptions( $is_individual ) {
		$websiteid = 0;
		if ( self::is_managesites_subpage() ) {
			$websiteid = $_GET['id'];
		}

		$site_branding     = null;
		$supportEmail      = '';
		$supportMessage    = '';
		$submitButtonTitle = '';
		$returnMessage     = '';
		$showButton        = 0;
		$showButtonIn      = 1;

		if ( ! $is_individual ) {
			$showButton        = self::get_instance()->get_option( 'child_show_support_button' );
			$showButtonIn      = self::get_instance()->get_option( 'child_show_support_button_in' );
			$supportEmail      = self::get_instance()->get_option( 'child_support_email' );
			$supportMessage    = self::get_instance()->get_option( 'child_support_message' );
			$buttonLabel       = self::get_instance()->get_option( 'child_button_contact_label' );
			$sendMessage       = self::get_instance()->get_option( 'child_send_email_message' );
			$submitButtonTitle = self::get_instance()->get_option( 'child_submit_button_title' );
			$returnMessage     = self::get_instance()->get_option( 'child_message_return_sender' );
		} elseif ( $websiteid ) {
			$site_branding = MainWP_Branding_DB::get_instance()->get_branding_by( 'site_id', $websiteid );
		}

		if ( $is_individual && $site_branding ) {
			$showButton     = $site_branding->show_support_button;
			$supportEmail   = $site_branding->support_email;
			$supportMessage = $site_branding->support_message;
			$buttonLabel    = $site_branding->button_contact_label;
			$sendMessage    = $site_branding->send_email_message;
			$extra_settings = unserialize( $site_branding->extra_settings );

			if ( is_array( $extra_settings ) ) {
				$submitButtonTitle = isset( $extra_settings['submit_button_title'] ) ? $extra_settings['submit_button_title'] : '';
				$returnMessage     = isset( $extra_settings['message_return_sender'] ) ? $extra_settings['message_return_sender'] : '';
				$showButtonIn      = isset( $extra_settings['show_button_in'] ) ? $extra_settings['show_button_in'] : 1;
			}
		}

		if ( empty( $buttonLabel ) ) {
			$buttonLabel = __( 'Contact Support', 'mainwp-branding-extension' );
		}

		if ( empty( $supportMessage ) ) {
			$supportMessage = __( 'Welcome to Support', 'mainwp-branding-extension' );
		}

		if ( empty( $submitButtonTitle ) ) {
			$submitButtonTitle = __( 'Submit', 'mainwp-branding-extension' );
		}

		if ( empty( $sendMessage ) ) {
			$sendMessage = __( 'Your message has been submitted successfully.', 'mainwp-branding-extension' );
		}

		if ( empty( $returnMessage ) ) {
			$returnMessage = __( 'Go back to the previous page', 'mainwp-branding-extension' );
		}

		?>
		<div class="ui hidden divider"></div>
		<h3 class="ui dividing header"><?php echo __( 'Support Form Options', 'mainwp-branding-extension' ); ?></h3>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Show the Support button', 'mainwp-branding-extension' ); ?></label>
			<div class="ten wide column ui toggle checkbox" data-tooltip="<?php esc_attr_e( 'Enable the Support Form feature. Support form WILL NOT show without an email address.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<input type="checkbox" id="mainwp_branding_show_support_button" name="mainwp_branding_show_support_button" <?php echo ( 0 == $showButton ? '' : 'checked="checked"' ); ?> value="1"/><label></label>
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Support email address', 'mainwp-branding-extension' ); ?></label>
			<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Enter an email address where you want to receive support requests.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<input type="text" name="mainwp_branding_support_email" id="mainwp_branding_support_email" value="<?php echo esc_attr( $supportEmail ); ?>"/>
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Show button in', 'mainwp-branding-extension' ); ?></label>
			<div class="ten wide column ui list">
				<div class="ui checkbox item"><input type="checkbox" value="1" <?php echo ( 1 == $showButtonIn || 3 == $showButtonIn ) ? ' checked="checked" ' : ''; ?> id="mainwp_branding_button_in_top_admin_bar" name="mainwp_branding_button_in_top_admin_bar"><label for="mainwp_branding_button_in_top_admin_bar"><?php _e( 'Top Admin bar', 'mainwp-branding-extension' ); ?></label></div>
				<div class="ui checkbox item"><input type="checkbox" value="2" <?php echo ( 2 == $showButtonIn || 3 == $showButtonIn ) ? ' checked="checked" ' : ''; ?> id="mainwp_branding_button_in_admin_menu" name="mainwp_branding_button_in_admin_menu"><label for="mainwp_branding_button_in_admin_menu"><?php _e( 'Admin menu', 'mainwp-branding-extension' ); ?></label></div>
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Contact Support label', 'mainwp-branding-extension' ); ?></label>
			<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Customize the Contact Support button label.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<input type="text" name="mainwp_branding_button_contact_label" id="mainwp_branding_button_contact_label" value="<?php echo esc_attr( stripslashes( $buttonLabel ) ); ?>"/>
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Support intro message', 'mainwp-branding-extension' ); ?></label>
			<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Customize the support form intro message.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<?php
				remove_editor_styles();
				wp_editor(
					stripslashes( $supportMessage ),
					'mainwp_branding_support_message',
					array(
						'textarea_name' => 'mainwp_branding_support_message',
						'textarea_rows' => 5,
						'teeny'         => true,
						'media_buttons' => false,
					)
				);
				?>
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Submit button title', 'mainwp-branding-extension' ); ?></label>
			<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Customize the submit button title.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<input type="text" name="mainwp_branding_submit_button_title" id="mainwp_branding_submit_button_title" value="<?php echo esc_attr( stripslashes( $submitButtonTitle ) ); ?>"/>
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Successful submission message', 'mainwp-branding-extension' ); ?></label>
			<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Customize the support form successful submission message.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<input type="text" name="mainwp_branding_send_email_message" id="mainwp_branding_send_email_message" value="<?php echo esc_attr( stripslashes( $sendMessage ) ); ?>"/>
			</div>
		</div>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Message to return sender to page they were on', 'mainwp-branding-extension' ); ?></label>
			<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Customize the "Go Back" message.', 'mainwp-branding-extension' ); ?>" data-inverted="" data-position="top left">
				<input type="text" name="mainwp_branding_message_return_sender" id="mainwp_branding_message_return_sender" value="<?php echo esc_attr( stripslashes( $returnMessage ) ); ?>"/>
			</div>
		</div>
		<?php
	}

	static function get_instance() {

		if ( null == self::$instance ) {
			self::$instance = new MainWP_Branding();
		}

		return self::$instance;
	}

	public static function handle_settings_post() {
		global $mainWPBrandingExtensionActivator;
		$is_individual = false;
		if ( isset( $_POST['branding_submit_nonce'] ) && wp_verify_nonce( $_POST['branding_submit_nonce'], 'branding_nonce' ) ) {
			if ( isset( $_POST['branding_individual_settings_site_id'] ) ) {
				$is_individual = true;
				$websiteId     = $_POST['branding_individual_settings_site_id'];

				if ( empty( $websiteId ) ) {
					return false;
				}
			}

			$current_extra_settings = array();
			if ( $is_individual && $websiteId ) {
				$site_branding = MainWP_Branding_DB::get_instance()->get_branding_by( 'site_id', $websiteId );
				if ( is_object( $site_branding ) ) {
					$current_extra_settings = unserialize( $site_branding->extra_settings );
				}
			}
			$output = array();

			$preserve_branding = ( isset( $_POST['mainwp_branding_preserve_branding'] ) && ! empty( $_POST['mainwp_branding_preserve_branding'] ) ) ? $_POST['mainwp_branding_preserve_branding'] : 0;

			$plugin_name = '';
			if ( isset( $_POST['mainwp_branding_plugin_name'] ) && ! empty( $_POST['mainwp_branding_plugin_name'] ) ) {
				$plugin_name = sanitize_text_field( $_POST['mainwp_branding_plugin_name'] );
			}

			$plugin_desc = '';
			if ( isset( $_POST['mainwp_branding_plugin_desc'] ) && ! empty( $_POST['mainwp_branding_plugin_desc'] ) ) {
				$plugin_desc = sanitize_text_field( $_POST['mainwp_branding_plugin_desc'] );
			}

			$plugin_uri = '';
			if ( isset( $_POST['mainwp_branding_plugin_uri'] ) && ! empty( $_POST['mainwp_branding_plugin_uri'] ) ) {
				$plugin_uri = trim( $_POST['mainwp_branding_plugin_uri'] );
				if ( ! preg_match( '/^https?\:\/\/.*$/i', $plugin_uri ) ) {
					$plugin_uri = 'http://' . $plugin_uri;
				}
			}

			$plugin_author = '';
			if ( isset( $_POST['mainwp_branding_plugin_author'] ) && ! empty( $_POST['mainwp_branding_plugin_author'] ) ) {
				$plugin_author = sanitize_text_field( $_POST['mainwp_branding_plugin_author'] );
			}

			$plugin_author_uri = '';
			if ( isset( $_POST['mainwp_branding_plugin_author_uri'] ) && ! empty( $_POST['mainwp_branding_plugin_author_uri'] ) ) {
				$plugin_author_uri = trim( $_POST['mainwp_branding_plugin_author_uri'] );
				if ( ! preg_match( '/^https?\:\/\/.*$/i', $plugin_author_uri ) ) {
					$plugin_author_uri = 'http://' . $plugin_author_uri;
				}
			}

			$login_image_link  = isset( $_POST['mainwp_branding_login_image_link'] ) ? trim( $_POST['mainwp_branding_login_image_link'] ) : '';
			$login_image_title = isset( $_POST['mainwp_branding_login_image_title'] ) ? trim( $_POST['mainwp_branding_login_image_title'] ) : '';

			$plugin_hide = 0;
			if ( isset( $_POST['mainwp_branding_hide_child_plugin'] ) && ! empty( $_POST['mainwp_branding_hide_child_plugin'] ) ) {
				$plugin_hide = $_POST['mainwp_branding_hide_child_plugin'];
			}

			$disable_change = 0;
			if ( isset( $_POST['mainwp_branding_disable_change'] ) && ! empty( $_POST['mainwp_branding_disable_change'] ) ) {
				$disable_change = $_POST['mainwp_branding_disable_change'];
			}

			$disable_switching_theme = ( isset( $_POST['mainwp_branding_disable_switching_theme'] ) && ! empty( $_POST['mainwp_branding_disable_switching_theme'] ) ) ? intval( $_POST['mainwp_branding_disable_switching_theme'] ) : 0;

			$show_button = 0;
			if ( isset( $_POST['mainwp_branding_show_support_button'] ) && ! empty( $_POST['mainwp_branding_show_support_button'] ) ) {
				$show_button = intval( $_POST['mainwp_branding_show_support_button'] );
			}

			$show_button_in = ( isset( $_POST['mainwp_branding_button_in_top_admin_bar'] ) ? intval( $_POST['mainwp_branding_button_in_top_admin_bar'] ) : 0 ) + ( isset( $_POST['mainwp_branding_button_in_admin_menu'] ) ? intval( $_POST['mainwp_branding_button_in_admin_menu'] ) : 0 );

			$button_contact_label = '';
			if ( isset( $_POST['mainwp_branding_button_contact_label'] ) && ! empty( $_POST['mainwp_branding_button_contact_label'] ) ) {
				$button_contact_label = sanitize_text_field( $_POST['mainwp_branding_button_contact_label'] );
			}

			$send_email_message = '';
			if ( isset( $_POST['mainwp_branding_send_email_message'] ) && ! empty( $_POST['mainwp_branding_send_email_message'] ) ) {
				$send_email_message = sanitize_text_field( $_POST['mainwp_branding_send_email_message'] );
			}

			$support_email = '';
			if ( isset( $_POST['mainwp_branding_support_email'] ) && ! empty( $_POST['mainwp_branding_support_email'] ) ) {
				$support_email = trim( $_POST['mainwp_branding_support_email'] );
				if ( ! preg_match( '/^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/', $support_email ) ) {
					$support_email = '';
				}
			}

			$support_message = '';
			if ( isset( $_POST['mainwp_branding_support_message'] ) && ! empty( $_POST['mainwp_branding_support_message'] ) ) {
				$support_message = $_POST['mainwp_branding_support_message'];
			}

			$remove_restore = 0;
			if ( isset( $_POST['mainwp_branding_remove_restore_clone'] ) && ! empty( $_POST['mainwp_branding_remove_restore_clone'] ) ) {
				$remove_restore = intval( $_POST['mainwp_branding_remove_restore_clone'] );
			}

			$remove_setting = 0;
			if ( isset( $_POST['mainwp_branding_remove_mainwp_setting'] ) && ! empty( $_POST['mainwp_branding_remove_mainwp_setting'] ) ) {
				$remove_setting = intval( $_POST['mainwp_branding_remove_mainwp_setting'] );
			}

			$remove_server_info = 0;
			if ( isset( $_POST['mainwp_branding_remove_mainwp_server_info'] ) && ! empty( $_POST['mainwp_branding_remove_mainwp_server_info'] ) ) {
				$remove_server_info = intval( $_POST['mainwp_branding_remove_mainwp_server_info'] );
			}

			$remove_wp_tools = 0;
			if ( isset( $_POST['mainwp_branding_remove_wp_tools'] ) && ! empty( $_POST['mainwp_branding_remove_wp_tools'] ) ) {
				$remove_wp_tools = intval( $_POST['mainwp_branding_remove_wp_tools'] );
			}

			$remove_wp_setting = 0;
			if ( isset( $_POST['mainwp_branding_remove_wp_setting'] ) && ! empty( $_POST['mainwp_branding_remove_wp_setting'] ) ) {
				$remove_wp_setting = intval( $_POST['mainwp_branding_remove_wp_setting'] );
			}

			$remove_permalink = 0;
			if ( isset( $_POST['mainwp_branding_remove_permalink'] ) && ! empty( $_POST['mainwp_branding_remove_permalink'] ) ) {
				$remove_permalink = intval( $_POST['mainwp_branding_remove_permalink'] );
			}

			$message_return_sender = '';
			if ( isset( $_POST['mainwp_branding_message_return_sender'] ) && ! empty( $_POST['mainwp_branding_message_return_sender'] ) ) {
				$message_return_sender = sanitize_text_field( $_POST['mainwp_branding_message_return_sender'] );
			}

			$submit_button_title = '';
			if ( isset( $_POST['mainwp_branding_submit_button_title'] ) && ! empty( $_POST['mainwp_branding_submit_button_title'] ) ) {
				$submit_button_title = sanitize_text_field( $_POST['mainwp_branding_submit_button_title'] );
			}

			$global_footer = '';
			if ( isset( $_POST['mainwp_branding_global_footer'] ) && ! empty( $_POST['mainwp_branding_global_footer'] ) ) {
				$global_footer = $_POST['mainwp_branding_global_footer'];
			}

			$dashboard_footer = '';
			if ( isset( $_POST['mainwp_branding_dashboard_footer'] ) && ! empty( $_POST['mainwp_branding_dashboard_footer'] ) ) {
				$dashboard_footer = $_POST['mainwp_branding_dashboard_footer'];
			}

			$site_generator = isset( $_POST['mainwp_branding_site_generator'] ) ? trim( $_POST['mainwp_branding_site_generator'] ) : '';
			$generator_link = isset( $_POST['mainwp_branding_site_generator_link'] ) ? trim( $_POST['mainwp_branding_site_generator_link'] ) : '';
			if ( ! empty( $generator_link ) && ! preg_match( '/^https?\:\/\/.*$/i', $generator_link ) ) {
				$generator_link = 'http://' . $generator_link;
			}

			$remove_widget_welcome  = isset( $_POST['mainwp_branding_remove_widget_welcome'] ) ? intval( $_POST['mainwp_branding_remove_widget_welcome'] ) : 0;
			$remove_widget_glance   = isset( $_POST['mainwp_branding_remove_widget_glance'] ) ? intval( $_POST['mainwp_branding_remove_widget_glance'] ) : 0;
			$remove_widget_activity = isset( $_POST['mainwp_branding_remove_widget_activity'] ) ? intval( $_POST['mainwp_branding_remove_widget_activity'] ) : 0;
			$remove_widget_quick    = isset( $_POST['mainwp_branding_remove_widget_quick'] ) ? intval( $_POST['mainwp_branding_remove_widget_quick'] ) : 0;
			$remove_widget_news     = isset( $_POST['mainwp_branding_remove_widget_news'] ) ? intval( $_POST['mainwp_branding_remove_widget_news'] ) : 0;

			$admin_css = isset( $_POST['mainwp_branding_admin_css'] ) ? trim( $_POST['mainwp_branding_admin_css'] ) : '';
			$login_css = isset( $_POST['mainwp_branding_login_css'] ) ? trim( $_POST['mainwp_branding_login_css'] ) : '';

			$texts_replace = array();
			if ( isset( $_POST['mainwp_branding_texts_value'] ) && is_array( $_POST['mainwp_branding_texts_value'] ) && count( $_POST['mainwp_branding_texts_value'] ) > 0 ) {
				foreach ( $_POST['mainwp_branding_texts_value'] as $i => $value ) {
					$value   = trim( $value );
					$replace = isset( $_POST['mainwp_branding_texts_replace'][ $i ] ) ? trim( $_POST['mainwp_branding_texts_replace'][ $i ] ) : '';
					if ( ! empty( $value ) && ! empty( $replace ) ) {
						$texts_replace[ $value ] = $replace;
					}
				}
			}

			$value   = isset( $_POST['mainwp_branding_texts_add_value'] ) ? trim( $_POST['mainwp_branding_texts_add_value'] ) : '';
			$replace = isset( $_POST['mainwp_branding_texts_add_replace'] ) ? trim( $_POST['mainwp_branding_texts_add_replace'] ) : '';
			if ( ! empty( $value ) && ! empty( $replace ) ) {
				$texts_replace[ $value ] = $replace;
			}

			$image_login = 'NOTCHANGE';
			if ( isset( $_POST['mainwp_branding_delete_login_image'] ) && '1' == $_POST['mainwp_branding_delete_login_image'] ) {
				$image_login = '';
			}

			if ( UPLOAD_ERR_OK == $_FILES['mainwp_branding_login_image_file']['error'] ) {
				$output = self::handle_upload_image( $_FILES['mainwp_branding_login_image_file'], 'login', 310, 70 );
				if ( is_array( $output ) && isset( $output['fileurl'] ) && ! empty( $output['fileurl'] ) ) {
					$image_login      = $output['fileurl'];
					$image_login_path = $output['filepath'];
				}
			}

			$image_favico      = 'NOTCHANGE';
			$image_favico_path = '';
			if ( isset( $_POST['mainwp_branding_delete_favico_image'] ) && '1' == $_POST['mainwp_branding_delete_favico_image'] ) {
				$image_favico = '';
			}

			if ( UPLOAD_ERR_OK == $_FILES['mainwp_branding_favico_file']['error'] ) {
				$output = self::handle_upload_image( $_FILES['mainwp_branding_favico_file'], 'favico', 16, 16 );
				if ( is_array( $output ) && isset( $output['fileurl'] ) && ! empty( $output['fileurl'] ) ) {
					$image_favico      = $output['fileurl'];
					$image_favico_path = $output['filepath'];
				}
			}

			$hide_nag_update     = isset( $_POST['mainwp_branding_hide_nag_update'] ) ? intval( $_POST['mainwp_branding_hide_nag_update'] ) : 0;
			$hide_screen_options = isset( $_POST['mainwp_branding_hide_screen_options'] ) ? intval( $_POST['mainwp_branding_hide_screen_options'] ) : 0;
			$hide_help_box       = isset( $_POST['mainwp_branding_hide_help_box'] ) ? intval( $_POST['mainwp_branding_hide_help_box'] ) : 0;

			$hide_metabox_post_excerpt  = isset( $_POST['mainwp_branding_hide_metabox_post_excerpt'] ) ? intval( $_POST['mainwp_branding_hide_metabox_post_excerpt'] ) : 0;
			$hide_metabox_post_slug     = isset( $_POST['mainwp_branding_hide_metabox_post_slug'] ) ? intval( $_POST['mainwp_branding_hide_metabox_post_slug'] ) : 0;
			$hide_metabox_post_tags     = isset( $_POST['mainwp_branding_hide_metabox_post_tags'] ) ? intval( $_POST['mainwp_branding_hide_metabox_post_tags'] ) : 0;
			$hide_metabox_post_author   = isset( $_POST['mainwp_branding_hide_metabox_post_author'] ) ? intval( $_POST['mainwp_branding_hide_metabox_post_author'] ) : 0;
			$hide_metabox_post_comments = isset( $_POST['mainwp_branding_hide_metabox_post_comments'] ) ? intval( $_POST['mainwp_branding_hide_metabox_post_comments'] ) : 0;

			$hide_metabox_post_revisions     = isset( $_POST['mainwp_branding_hide_metabox_post_revisions'] ) ? intval( $_POST['mainwp_branding_hide_metabox_post_revisions'] ) : 0;
			$hide_metabox_post_discussion    = isset( $_POST['mainwp_branding_hide_metabox_post_discussion'] ) ? intval( $_POST['mainwp_branding_hide_metabox_post_discussion'] ) : 0;
			$hide_metabox_post_categories    = isset( $_POST['mainwp_branding_hide_metabox_post_categories'] ) ? intval( $_POST['mainwp_branding_hide_metabox_post_categories'] ) : 0;
			$hide_metabox_post_custom_fields = isset( $_POST['mainwp_branding_hide_metabox_post_custom_fields'] ) ? intval( $_POST['mainwp_branding_hide_metabox_post_custom_fields'] ) : 0;
			$hide_metabox_post_trackbacks    = isset( $_POST['mainwp_branding_hide_metabox_post_trackbacks'] ) ? intval( $_POST['mainwp_branding_hide_metabox_post_trackbacks'] ) : 0;

			$hide_metabox_page_custom_fields = isset( $_POST['mainwp_branding_hide_metabox_page_custom_fields'] ) ? intval( $_POST['mainwp_branding_hide_metabox_page_custom_fields'] ) : 0;
			$hide_metabox_page_author        = isset( $_POST['mainwp_branding_hide_metabox_page_author'] ) ? intval( $_POST['mainwp_branding_hide_metabox_page_author'] ) : 0;
			$hide_metabox_page_discussion    = isset( $_POST['mainwp_branding_hide_metabox_page_discussion'] ) ? intval( $_POST['mainwp_branding_hide_metabox_page_discussion'] ) : 0;
			$hide_metabox_page_revisions     = isset( $_POST['mainwp_branding_hide_metabox_page_revisions'] ) ? intval( $_POST['mainwp_branding_hide_metabox_page_revisions'] ) : 0;
			$hide_metabox_page_attributes    = isset( $_POST['mainwp_branding_hide_metabox_page_attributes'] ) ? intval( $_POST['mainwp_branding_hide_metabox_page_attributes'] ) : 0;
			$hide_metabox_page_slug          = isset( $_POST['mainwp_branding_hide_metabox_page_slug'] ) ? intval( $_POST['mainwp_branding_hide_metabox_page_slug'] ) : 0;

			if ( ! $is_individual ) {
				self::get_instance()->set_option( 'child_plugin_name', $plugin_name );
				self::get_instance()->set_option( 'child_plugin_desc', $plugin_desc );
				self::get_instance()->set_option( 'child_plugin_author', $plugin_author );
				self::get_instance()->set_option( 'child_plugin_author_uri', $plugin_author_uri );
				self::get_instance()->set_option( 'child_plugin_uri', $plugin_uri );
				self::get_instance()->set_option( 'child_plugin_hide', $plugin_hide );
				self::get_instance()->set_option( 'child_disable_change', $disable_change );
				self::get_instance()->set_option( 'child_disable_switching_theme', $disable_switching_theme );
				self::get_instance()->set_option( 'child_show_support_button', $show_button );
				self::get_instance()->set_option( 'child_show_support_button_in', $show_button_in );
				self::get_instance()->set_option( 'child_support_email', $support_email );
				self::get_instance()->set_option( 'child_support_message', $support_message );
				self::get_instance()->set_option( 'child_remove_restore', $remove_restore );
				self::get_instance()->set_option( 'child_remove_setting', $remove_setting );
				self::get_instance()->set_option( 'child_remove_server_info', $remove_server_info );
				self::get_instance()->set_option( 'child_remove_wp_tools', $remove_wp_tools );
				self::get_instance()->set_option( 'child_remove_wp_setting', $remove_wp_setting );
				self::get_instance()->set_option( 'child_remove_permalink', $remove_permalink );
				self::get_instance()->set_option( 'child_button_contact_label', $button_contact_label );
				self::get_instance()->set_option( 'child_send_email_message', $send_email_message );
				self::get_instance()->set_option( 'child_message_return_sender', $message_return_sender );
				self::get_instance()->set_option( 'child_submit_button_title', $submit_button_title );
				self::get_instance()->set_option( 'child_global_footer', $global_footer );
				self::get_instance()->set_option( 'child_dashboard_footer', $dashboard_footer );
				self::get_instance()->set_option( 'child_remove_widget_welcome', $remove_widget_welcome );
				self::get_instance()->set_option( 'child_remove_widget_glance', $remove_widget_glance );
				self::get_instance()->set_option( 'child_remove_widget_activity', $remove_widget_activity );
				self::get_instance()->set_option( 'child_remove_widget_quick', $remove_widget_quick );
				self::get_instance()->set_option( 'child_remove_widget_news', $remove_widget_news );
				self::get_instance()->set_option( 'child_login_image_link', $login_image_link );
				self::get_instance()->set_option( 'child_login_image_title', $login_image_title );
				self::get_instance()->set_option( 'child_site_generator', $site_generator );
				self::get_instance()->set_option( 'child_generator_link', $generator_link );
				self::get_instance()->set_option( 'child_admin_css', $admin_css );
				self::get_instance()->set_option( 'child_login_css', $login_css );
				self::get_instance()->set_option( 'child_texts_replace', $texts_replace );

				if ( 'NOTCHANGE' !== $image_login ) {
					$old_file = self::get_instance()->get_option( 'child_login_image_path' );
					if ( ( $old_file != $image_login_path ) && ( $old_file != $image_favico_path ) ) {
						@unlink( $old_file );
					}
					self::get_instance()->set_option( 'child_login_image', $image_login );
					self::get_instance()->set_option( 'child_login_image_path', $image_login_path );
				}

				if ( 'NOTCHANGE' !== $image_favico ) {
					$old_file = self::get_instance()->get_option( 'child_favico_image_path' );
					if ( ( $old_file != $image_login_path ) && ( $old_file != $image_favico_path ) ) {
						@unlink( $old_file );
					}
					self::get_instance()->set_option( 'child_favico_image', $image_favico );
					self::get_instance()->set_option( 'child_favico_image_path', $image_favico_path );
				}

				self::get_instance()->set_option( 'child_hide_nag', $hide_nag_update );
				self::get_instance()->set_option( 'child_hide_screen_opts', $hide_screen_options );
				self::get_instance()->set_option( 'child_hide_help_box', $hide_help_box );

				self::get_instance()->set_option( 'child_hide_metabox_post_excerpt', $hide_metabox_post_excerpt );
				self::get_instance()->set_option( 'child_hide_metabox_post_slug', $hide_metabox_post_slug );
				self::get_instance()->set_option( 'child_hide_metabox_post_tags', $hide_metabox_post_tags );
				self::get_instance()->set_option( 'child_hide_metabox_post_author', $hide_metabox_post_author );
				self::get_instance()->set_option( 'child_hide_metabox_post_comments', $hide_metabox_post_comments );

				self::get_instance()->set_option( 'child_hide_metabox_post_revisions', $hide_metabox_post_revisions );
				self::get_instance()->set_option( 'child_hide_metabox_post_discussion', $hide_metabox_post_discussion );
				self::get_instance()->set_option( 'child_hide_metabox_post_categories', $hide_metabox_post_categories );
				self::get_instance()->set_option( 'child_hide_metabox_post_custom_fields', $hide_metabox_post_custom_fields );
				self::get_instance()->set_option( 'child_hide_metabox_post_trackbacks', $hide_metabox_post_trackbacks );

				self::get_instance()->set_option( 'child_hide_metabox_page_custom_fields', $hide_metabox_page_custom_fields );
				self::get_instance()->set_option( 'child_hide_metabox_page_author', $hide_metabox_page_author );
				self::get_instance()->set_option( 'child_hide_metabox_page_discussion', $hide_metabox_page_discussion );
				self::get_instance()->set_option( 'child_hide_metabox_page_revisions', $hide_metabox_page_revisions );
				self::get_instance()->set_option( 'child_hide_metabox_page_attributes', $hide_metabox_page_attributes );
				self::get_instance()->set_option( 'child_hide_metabox_page_slug', $hide_metabox_page_slug );
				self::get_instance()->set_option( 'child_preserve_branding', $preserve_branding );
				update_option( 'mainwp_branding_need_to_general_update', 1 );
			} elseif ( $is_individual ) {
				$header   = serialize(
					array(
						'plugin_name'   => $plugin_name,
						'plugin_desc'   => $plugin_desc,
						'plugin_author' => $plugin_author,
						'author_uri'    => $plugin_author_uri,
						'plugin_uri'    => $plugin_uri,
					)
				);
				$branding = array(
					'site_id'                     => $websiteId,
					'plugin_header'               => $header,
					'hide_child_plugin'           => $plugin_hide,
					'disable_theme_plugin_change' => $disable_change,
					'show_support_button'         => $show_button,
					'support_email'               => $support_email,
					'support_message'             => $support_message,
					'remove_restore'              => $remove_restore,
					'remove_setting'              => $remove_setting,
					'remove_server_info'          => $remove_server_info,
					'remove_wp_tools'             => $remove_wp_tools,
					'remove_wp_setting'           => $remove_wp_setting,
					'button_contact_label'        => $button_contact_label,
					'send_email_message'          => $send_email_message,
					'override'                    => isset( $_POST['mainwp_branding_site_override'] ) ? intval( $_POST['mainwp_branding_site_override'] ) : 0,
				);

				if ( $site_branding ) {
					$branding['id'] = $site_branding->id;
				}

				$extra_settings = array(
					'submit_button_title'             => $submit_button_title,
					'message_return_sender'           => $message_return_sender,
					'remove_permalink'                => $remove_permalink,
					'show_button_in'                  => $show_button_in,
					'disable_wp_branding'             => isset( $_POST['mainwp_branding_site_disable_wp_branding'] ) ? intval( $_POST['mainwp_branding_site_disable_wp_branding'] ) : 0,
					'global_footer'                   => $global_footer,
					'dashboard_footer'                => $dashboard_footer,
					'remove_widget_welcome'           => $remove_widget_welcome,
					'remove_widget_glance'            => $remove_widget_glance,
					'remove_widget_activity'          => $remove_widget_activity,
					'remove_widget_quick'             => $remove_widget_quick,
					'remove_widget_news'              => $remove_widget_news,
					'login_image_link'                => $login_image_link,
					'login_image_title'               => $login_image_title,
					'site_generator'                  => $site_generator,
					'generator_link'                  => $generator_link,
					'admin_css'                       => $admin_css,
					'login_css'                       => $login_css,
					'texts_replace'                   => $texts_replace,
					'image_favico'                    => $image_favico,
					'hide_nag'                        => $hide_nag_update,
					'hide_screen_opts'                => $hide_screen_options,
					'hide_help_box'                   => $hide_help_box,
					'hide_metabox_post_excerpt'       => $hide_metabox_post_excerpt,
					'hide_metabox_post_slug'          => $hide_metabox_post_slug,
					'hide_metabox_post_tags'          => $hide_metabox_post_tags,
					'hide_metabox_post_author'        => $hide_metabox_post_author,
					'hide_metabox_post_comments'      => $hide_metabox_post_comments,
					'hide_metabox_post_revisions'     => $hide_metabox_post_revisions,
					'hide_metabox_post_discussion'    => $hide_metabox_post_discussion,
					'hide_metabox_post_categories'    => $hide_metabox_post_categories,
					'hide_metabox_post_custom_fields' => $hide_metabox_post_custom_fields,
					'hide_metabox_post_trackbacks'    => $hide_metabox_post_trackbacks,
					'hide_metabox_page_custom_fields' => $hide_metabox_page_custom_fields,
					'hide_metabox_page_author'        => $hide_metabox_page_author,
					'hide_metabox_page_discussion'    => $hide_metabox_page_discussion,
					'hide_metabox_page_revisions'     => $hide_metabox_page_revisions,
					'hide_metabox_page_attributes'    => $hide_metabox_page_attributes,
					'hide_metabox_page_slug'          => $hide_metabox_page_slug,
					'preserve_branding'               => $preserve_branding,
					'disable_switching_theme'         => $disable_switching_theme,
				);

				if ( 'NOTCHANGE' === $image_login ) {
					$extra_settings['login_image']      = isset( $current_extra_settings['login_image'] ) ? $current_extra_settings['login_image'] : '';
					$extra_settings['login_image_path'] = isset( $current_extra_settings['login_image_path'] ) ? $current_extra_settings['login_image_path'] : '';
				} else {
					$extra_settings['login_image']      = $image_login;
					$extra_settings['login_image_path'] = $image_login_path;
					$old_file                           = isset( $current_extra_settings['login_image_path'] ) ? $current_extra_settings['login_image_path'] : '';
					if ( ( $old_file != $image_login_path ) && ( $old_file != $image_favico_path ) ) {
						@unlink( $old_file );
					}
				}

				if ( 'NOTCHANGE' === $image_favico ) {
					$extra_settings['favico_image']      = isset( $current_extra_settings['favico_image'] ) ? $current_extra_settings['favico_image'] : '';
					$extra_settings['favico_image_path'] = isset( $current_extra_settings['favico_image_path'] ) ? $current_extra_settings['favico_image_path'] : '';
				} else {
					$extra_settings['favico_image']      = $image_favico;
					$extra_settings['favico_image_path'] = $image_favico_path;
					$old_file                            = isset( $current_extra_settings['favico_image_path'] ) ? $current_extra_settings['favico_image_path'] : '';
					if ( ( $old_file != $image_login_path ) && ( $old_file != $image_favico_path ) ) {
						@unlink( $old_file );
					}
				}

				$branding['extra_settings'] = serialize( $extra_settings );

				$result = MainWP_Branding_DB::get_instance()->update_branding( $branding );

				update_option( 'mainwp_branding_need_to_update_site', 1 );
			}

			return $output;
		}

		return false;
	}

	public static function handle_upload_image( $file_input, $what, $max_width, $max_height ) {
		$upload_dir = wp_upload_dir();
		$base_dir   = $upload_dir['basedir'];
		$base_url   = $upload_dir['baseurl'];
		$output     = array();
		$filename   = '';
		$filepath   = '';
		if ( UPLOAD_ERR_OK == $file_input['error'] ) {
			$tmp_file = $file_input['tmp_name'];
			if ( is_uploaded_file( $tmp_file ) ) {
				$file_size      = $file_input['size'];
				$file_type      = $file_input['type'];
				$file_name      = $file_input['name'];
				$file_extension = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );

				if ( ( $file_size > 500 * 1025 ) ) {
					$output['error'][] = ( 'login' === $what ) ? 0 : 3;
				} elseif (
					( 'image/jpeg' != $file_type ) &&
					( 'image/jpg' != $file_type ) &&
					( 'image/gif' != $file_type ) &&
					( 'image/png' != $file_type )
				) {
					$output['error'][] = ( 'login' === $what ) ? 1 : 4;
				} elseif (
					( 'jpeg' != $file_extension ) &&
					( 'jpg' != $file_extension ) &&
					( 'gif' != $file_extension ) &&
					( 'png' != $file_extension )
				) {
					$output['error'][] = ( 'login' === $what ) ? 1 : 4;
				} else {

					$dest_file = $base_dir . '/' . $file_name;
					$dest_file = dirname( $dest_file ) . '/' . wp_unique_filename( dirname( $dest_file ), basename( $dest_file ) );

					if ( move_uploaded_file( $tmp_file, $dest_file ) ) {
						if ( file_exists( $dest_file ) ) {
							list( $width, $height, $type, $attr ) = getimagesize( $dest_file );
						}

						$resize = false;
						if ( $width > $max_width ) {
							$dst_width = $max_width;
							if ( $height > $max_height ) {
								$dst_height = $max_height;
							} else {
								$dst_height = $height;
							}
							$resize = true;
						} elseif ( $height > $max_height ) {
							$dst_width  = $width;
							$dst_height = $max_height;
							$resize     = true;
						}

						if ( $resize ) {
							$src          = $dest_file;
							$cropped_file = wp_crop_image( $src, 0, 0, $width, $height, $dst_width, $dst_height, false );
							if ( ! $cropped_file || is_wp_error( $cropped_file ) ) {
								$output['error'][] = ( 'login' === $what ) ? 8 : 9;
							} else {
								@unlink( $dest_file );
								$filename = basename( $cropped_file );
								$filepath = $cropped_file;
							}
						} else {
							$filename = basename( $dest_file );
							$filepath = $dest_file;
						}
					} else {
						$output['error'][] = ( 'login' === $what ) ? 2 : 5;
					}
				}
			}
		}
		$output['fileurl']  = ! empty( $filename ) ? $base_url . '/' . $filename : '';
		$output['filepath'] = ! empty( $filepath ) ? $filepath : '';

		return $output;
	}

	public function ajax_save_settings() {
		$siteid = $_POST['siteId'];

		if ( empty( $siteid ) ) {
			die( json_encode( array( 'error' => 'Error: site ID empty' ) ) );
		}

		$is_individual            = isset( $_POST['individual'] ) && $_POST['individual'] ? true : false;
		$result                   = MainWP_Branding_DB::get_instance()->get_branding_by( 'site_id', $siteid );
		$save_individual_settings = false;

		if ( $is_individual ) {
			if ( empty( $result ) ) {
				die( json_encode( array( 'error' => __( 'Update failed: Settings empty.', 'mainwp-branding-extension' ) ) ) );
				return;
			}
			if ( $result->override ) {
				$save_individual_settings = true;
			}
		} elseif ( $result && $result->override ) {
			die( json_encode( array( 'result' => 'OVERRIDED' ) ) );
		}

		$information = $this->perform_save_settings( $siteid, $save_individual_settings );
		die( json_encode( $information ) );
	}


	public function mainwp_apply_plugin_settings( $siteid ) {
		$information = $this->perform_save_settings( $siteid );
		$result      = array();
		if ( is_array( $information ) ) {
			if ( 'SUCCESS' == $information['result'] ) {
				$result = array( 'result' => 'success' );
			} elseif ( $information['error'] ) {
				$result = array( 'error' => $information['error'] );
			} else {
				$result = array( 'result' => 'failed' );
			}
		} else {
			$result = array( 'result' => 'failed' );
		}
		die( json_encode( $result ) );
	}

	public function perform_save_settings( $siteid, $save_individual_settings = false ) {

		global $mainWPBrandingExtensionActivator;

		$branding = MainWP_Branding_DB::get_instance()->get_branding_by( 'site_id', $siteid );

		$post_data = array(
			'action' => 'update_branding',
		);

		if ( $save_individual_settings && is_object( $branding ) ) {
			$header                                   = unserialize( $branding->plugin_header );
			$extra_settings                           = unserialize( $branding->extra_settings );
			$settings                                 = array(
				'child_plugin_name'          => $header['plugin_name'],
				'child_plugin_desc'          => $header['plugin_desc'],
				'child_plugin_author'        => $header['plugin_author'],
				'child_plugin_author_uri'    => $header['author_uri'],
				'child_plugin_plugin_uri'    => $header['plugin_uri'],
				'child_plugin_hide'          => $branding->hide_child_plugin,
				'child_disable_change'       => $branding->disable_theme_plugin_change,
				'child_show_support_button'  => $branding->show_support_button,
				'child_support_email'        => $branding->support_email,
				'child_support_message'      => $branding->support_message,
				'child_remove_restore'       => $branding->remove_restore,
				'child_remove_setting'       => $branding->remove_setting,
				'child_remove_server_info'   => $branding->remove_server_info,
				'child_remove_wp_tools'      => $branding->remove_wp_tools,
				'child_remove_wp_setting'    => $branding->remove_wp_setting,
				'child_button_contact_label' => $branding->button_contact_label,
				'child_send_email_message'   => $branding->send_email_message,
			);
			$settings['child_submit_button_title']    = $extra_settings['submit_button_title'];
			$settings['child_message_return_sender']  = $extra_settings['message_return_sender'];
			$settings['child_remove_permalink']       = $extra_settings['remove_permalink'];
			$settings['child_show_support_button_in'] = $extra_settings['show_button_in'];
			$settings['child_global_footer']          = $extra_settings['global_footer'];
			$settings['child_dashboard_footer']       = $extra_settings['dashboard_footer'];
			$settings['child_remove_widget_welcome']  = $extra_settings['remove_widget_welcome'];
			$settings['child_remove_widget_glance']   = $extra_settings['remove_widget_glance'];
			$settings['child_remove_widget_activity'] = $extra_settings['remove_widget_activity'];
			$settings['child_remove_widget_quick']    = $extra_settings['remove_widget_quick'];
			$settings['child_remove_widget_news']     = $extra_settings['remove_widget_news'];
			$settings['child_login_image_link']       = $extra_settings['login_image_link'];
			$settings['child_login_image_title']      = $extra_settings['login_image_title'];
			$settings['child_site_generator']         = $extra_settings['site_generator'];
			$settings['child_generator_link']         = $extra_settings['generator_link'];
			$settings['child_admin_css']              = $extra_settings['admin_css'];
			$settings['child_login_css']              = $extra_settings['login_css'];
			$settings['child_texts_replace']          = $extra_settings['texts_replace'];
			$settings['child_login_image']            = $extra_settings['login_image'];
			$settings['child_favico_image']           = $extra_settings['favico_image'];
			$settings['child_hide_nag']               = $extra_settings['hide_nag'];
			$settings['child_hide_screen_opts']       = $extra_settings['hide_screen_opts'];
			$settings['child_hide_help_box']          = $extra_settings['hide_help_box'];

			$settings['child_hide_metabox_post_excerpt']  = $extra_settings['hide_metabox_post_excerpt'];
			$settings['child_hide_metabox_post_slug']     = $extra_settings['hide_metabox_post_slug'];
			$settings['child_hide_metabox_post_tags']     = $extra_settings['hide_metabox_post_tags'];
			$settings['child_hide_metabox_post_author']   = $extra_settings['hide_metabox_post_author'];
			$settings['child_hide_metabox_post_comments'] = $extra_settings['hide_metabox_post_comments'];

			$settings['child_hide_metabox_post_revisions']     = $extra_settings['hide_metabox_post_revisions'];
			$settings['child_hide_metabox_post_discussion']    = $extra_settings['hide_metabox_post_discussion'];
			$settings['child_hide_metabox_post_categories']    = $extra_settings['hide_metabox_post_categories'];
			$settings['child_hide_metabox_post_custom_fields'] = $extra_settings['hide_metabox_post_custom_fields'];
			$settings['child_hide_metabox_post_trackbacks']    = $extra_settings['hide_metabox_post_trackbacks'];

			$settings['child_hide_metabox_page_custom_fields'] = $extra_settings['hide_metabox_page_custom_fields'];
			$settings['child_hide_metabox_page_author']        = $extra_settings['hide_metabox_page_author'];
			$settings['child_hide_metabox_page_discussion']    = $extra_settings['hide_metabox_page_discussion'];
			$settings['child_hide_metabox_page_revisions']     = $extra_settings['hide_metabox_page_revisions'];
			$settings['child_hide_metabox_page_attributes']    = $extra_settings['hide_metabox_page_attributes'];
			$settings['child_hide_metabox_page_slug']          = $extra_settings['hide_metabox_page_slug'];
			$settings['child_preserve_branding']               = $extra_settings['preserve_branding'];
			$settings['child_disable_switching_theme']         = $extra_settings['disable_switching_theme'];
			$settings['child_disable_wp_branding']             = $extra_settings['disable_wp_branding'] ? 'Y' : 'N';
			$post_data['specical']                             = true;
		} else {
			$settings = $this->option;
		}

		if ( isset( $settings['child_login_image'] ) && ! empty( $settings['child_login_image'] ) ) {
			$fix_site_url                      = ! preg_match( '#^https?://#i', $settings['child_login_image'] ) ? get_site_url() : '';
			$settings['child_login_image_url'] = $fix_site_url . $settings['child_login_image'];
		} else {
			$settings['child_login_image_url'] = '';
		}

		if ( isset( $settings['child_favico_image'] ) && ! empty( $settings['child_favico_image'] ) ) {
			$fix_site_url                       = ! preg_match( '/^https?:/', $settings['child_favico_image'] ) ? get_site_url() : '';
			$settings['child_favico_image_url'] = $fix_site_url . $settings['child_favico_image'];
		} else {
			$settings['child_favico_image_url'] = '';
		}

		if ( isset( $settings['child_admin_css'] ) ) {
			$style                       = stripslashes( $settings['child_admin_css'] );
			$style                       = preg_replace( '|^[\s]*<script>|', '', $style );
			$style                       = preg_replace( '|<\/script>[\s]*$|', '', $style );
			$style                       = trim( $style );
			$settings['child_admin_css'] = $style;
		}

		if ( isset( $settings['child_login_css'] ) ) {
			$style                       = stripslashes( $settings['child_login_css'] );
			$style                       = preg_replace( '/^[\s]*<script>/', '', $style );
			$style                       = preg_replace( '/<\/script>[\s]*$/', '', $style );
			$style                       = trim( $style );
			$settings['child_login_css'] = $style;
		}

		$settings = apply_filters( 'mainwp_branding_settings_before_save_to_sites', $settings, $siteid );

		$post_data['settings'] = base64_encode( serialize( $settings ) );

		add_filter(
			'mainwp_fetchurl_response_json',
			function( $input ) {
				$input[] = 'update_branding';
				return $input;
			}
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPBrandingExtensionActivator->get_child_file(), $mainWPBrandingExtensionActivator->get_child_key(), $siteid, 'branding_child_plugin', $post_data );

		return $information;
	}
}
