<?php

class MainWPCustomPostTypeView {

	public static $plugin_translate = 'mainwp-custom-post-types';


	/**
	 * Render wp-admin/admin.php?page=Extensions-Mainwp-Custom-Post-Types
	 */
	public static function render_view() {
		$post_types = get_post_types( array( '_builtin' => false ) );
		?>

		<div id="mainwp-custom-post-types" class="ui alt segment">
			<div class="mainwp-main-content">
				<div class="ui hidden divider"></div>
				<div class="ui red message" id="custom_post_type_error" style="display: none;"></div>
				<div class="ui green message" id="custom_post_type_message" style="display: none;"></div>
				<div id="ngCustomPostyTypeId" ng-app="ngCustomPostyTypeApp" style="display: none;">
					<div ng-controller="ngCustomPostTypeController">
						<div tasty-table bind-resource-callback="get_key" bind-init="init_get_key" bind-filters="key_filter" bind-theme="key_theme" bind-reload="reload_key_callback">
							<table class="ui single line table">
								<thead tasty-head>
									<tr>
										<th><?php _e( 'Custom Post Type', self::$plugin_translate ); ?></th>
										<th><?php _e( 'Slug', self::$plugin_translate ); ?></th>
										<th><?php _e( 'Description', self::$plugin_translate ); ?></th>
										<th class="collapsing"><?php _e( '', self::$plugin_translate ); ?></th>
									</tr>
								</thead>
								<tr ng-if="rows.length==0">
									<td>
										<div class="ui active inverted dimmer">
										<div class="ui text loader"><?php _e( 'Loading...', self::$plugin_translate ); ?></div>
									  </div>
									</td>
								</tr>
								<tr ng-repeat="d in rows">
									<td>{{ d.name }}</td>
									<td>{{ d.value }}</td>
									<td>{{ d.desc }}</td>
									<td>
										<?php if ( count( $post_types ) > 2 ) : ?>
											<a class="ui mini green button" href="<?php echo admin_url( 'post-new.php?post_type=' ); ?>{{ d.value }}"><?php _e( 'Add New', self::$plugin_translate ); ?></a>
										<?php endif; ?>
									</td>
								</tr>
							</table>
						</div>
						<div class="ui clearing hidden divider"></div>
						<?php
						if ( isset( $_POST['redirect_after_save_post'] ) ) :
							$post_id = ( isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0 );
							if ( wp_verify_nonce( $_POST['_wpnonce'], 'mainwp_custom_post_type_redirect_after_save_post_' . $post_id ) ) :
								$selected_groups     = ( isset( $_POST['selected_groups'] ) ? json_decode( (string) $_POST['selected_groups'], true ) : '' );
								$selected_sites      = ( isset( $_POST['selected_groups'] ) ? json_decode( (string) $_POST['selected_sites'], true ) : '' );
								$selected_categories = ( isset( $_POST['selected_categories'] ) ? json_decode( (string) stripslashes( $_POST['selected_categories'] ), true ) : '' );
								$post_only_existing  = ( isset( $_POST['post_only_existing'] ) && $_POST['post_only_existing'] == '1' ? 1 : 0 );

								if ( $post_id > 0 ) :
									?>
									<div class="ui modal" id="mainwp-cpt-synchronization-modal" ng-init="show_synchronization_window=true;<?php echo 'send_to_child(' . $post_id . ', ' . esc_attr( wp_json_encode( $selected_sites ) ) . ', ' . esc_attr( wp_json_encode( $selected_groups ) ) . ', ' . esc_attr( wp_json_encode( $selected_categories ) ) . ', ' . $post_only_existing . ')'; ?>" ng-show="show_synchronization_window">
										<div class="header"><?php _e( 'Publishing to Child Sites', self::$plugin_translate ); ?></div>
										<div id="mainwp-cpt-syncing-message" class="scrolling content">

										</div>
										<div class="actions">
											<div class="ui cancel button"><?php echo __( 'Close', self::$plugin_translate ); ?></div>
										</div>
									</div>
									<?php
								endif;
							else :
								echo __( 'Nonce token could not be verified.', self::$plugin_translate );
							endif;
						endif;
						?>
					</div>
				</div>

			</div>
			<div class="mainwp-side-content">
				<p><?php _e( 'Custom post types are nothing more than a basic post but have different sets of parameters defined inside your code. Probably, you have been able to see Products, Testimonials, Features, Projects, or similar items in your WordPress Sites and thought that it would be cool if you could manage these from your MainWP Dashboard.', self::$plugin_translate ); ?></p>
				<p><?php _e( 'MainWP Custom Post Types extension allows you to manage custom post types on your child sites. In order to manage custom post type on your child sites, you need to have the matching Custom Post Type (CPT) on your MainWP Dashboard too. Once you register the same CPT on your MainWP Dashboard site, this extension will add the Select Sites Metabox to the Add New Item interface and it will allow you to control where do you want to publish this CPT content.', self::$plugin_translate ); ?></p>
				<p><?php _e( 'There are 3 required arguments for each CPT:', self::$plugin_translate ); ?></p>
				<div class="ui info message">
					<ol class="ui list">
						<li><?php _e( 'CPT Slug', self::$plugin_translate ); ?></li>
						<li><?php _e( 'CPT Plural Label', self::$plugin_translate ); ?></li>
						<li><?php _e( 'CPT Singular Label', self::$plugin_translate ); ?></li>
					</ol>
				</div>
				<h3 class="header"><?php _e( 'WooCommerce Products', self::$plugin_translate ); ?></h3>
				<p><?php _e( 'In case you want to use this extension to publish WooCommerce Products to your child site(s), it is enough to install the WooCommerce plugin on your dashboard site. The extension will automatically create support for Products.', self::$plugin_translate ); ?></p>
				<h3 class="header"><?php _e( 'Important', self::$plugin_translate ); ?></h3>
				<p><?php _e( 'MainWP Custom Post Types extension DOES NOT register CPTs on your child sites. It only allows you to manage existing CPTs. In order to properly use it, you need to have the same CPT on your Dashboard and Child Site(s)!', self::$plugin_translate ); ?></p>
				<p><?php _e( 'It doesn’t matter if a CPT on your child sites has been created by a plugin, registered with a snippet in the functions.php or added by your WordPress Theme, this extension will be able to manage it as long as you are able to create the Matching CPT on your dashboard site.', self::$plugin_translate ); ?></p>
			</div>
			<div class="ui clearing hidden divider"></div>
		</div>






		<?php
	}

}
