<?php

class MainWPCloneSite {

  public function init() {
    add_action( 'wp_ajax_mainwp_clone_update_allowed_sites', array( &$this, 'mainwp_clone_update_allowed_sites' ) );
    add_action( 'wp_ajax_mainwp_clone_update_clone_enabled', array( &$this, 'mainwp_clone_update_clone_enabled' ) );
  }

  function mainwp_clone_update_allowed_sites() {
    die( json_encode( array( 'result' => MainWPCloneSite::updateDisallowedSites( $_POST['websiteIds'] ) ) ) );
  }

  function mainwp_clone_update_clone_enabled() {
    die( json_encode( array( 'result' => MainWPCloneSite::updateCloneEnabled( ( $_POST['cloneEnabled'] == 1 ) ) ) ) );
  }

  public static function render() {
    global $mainWPCloneExtensionActivator;

    $dbwebsites = apply_filters( 'mainwp_getsites', $mainWPCloneExtensionActivator->getChildFile(), $mainWPCloneExtensionActivator->getChildKey(), null );
    $cloneEnabled = get_option( 'mainwp_clone_enabled' );
    $disallowedCloneSites = get_option( 'mainwp_clone_disallowedsites' );
    $idx = 0;

    if ( $disallowedCloneSites === false )
      $disallowedCloneSites = array();
    ?>
    <div class="ui alt segment" id="mainwp-clone">
      <div class="mainwp-main-content">
        <div class="ui hidden divider"></div>
        <div class="ui secondary segment">
          <div class="ui form">
            <div class="ui grid field">
							<label class="six wide column middle aligned"><?php esc_html_e( 'Enable the Clone feature', 'mainwp-clone-extension' ); ?></label>
						  <div class="ten wide column ui toggle checkbox">
                <input type="checkbox" name="mainwp_clone_enabled" id="mainwp_clone_enabled" <?php echo ( $cloneEnabled ? 'checked="true"' : '' ); ?> />
							</div>
						</div>
          </div>
        </div>
        <div class="ui hidden divider"></div>
        <div class="ui segment <?php echo ( $cloneEnabled ? '' : 'disabled' ); ?>" id="mainwp-clone-sites-wrapper">
          <table class="ui single line definition selectable table" id="mainwp-clone-sites">
            <thead>
              <tr>
                <th class="collapsing"></th>
                <th><?php esc_html_e( 'Site', 'mainwp-clone-extension' ); ?></th>
                <th class="collapsing"><?php esc_html_e( '', 'mainwp-clone-extension' ); ?></th>
                <th><?php esc_html_e( 'URL', 'mainwp-clone-extension' ); ?></th>
                <th class="collapsing"><?php esc_html_e( 'Size (approximate)', 'mainwp-clone-extension' ); ?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ( $dbwebsites as $website ) : ?>
              <?php  $idx++; ?>
              <tr class="<?php echo ( in_array( $website['id'], $disallowedCloneSites ) ) ? 'disallowed' : 'allowed'; ?> mainwp-clone-item" id="<?php echo $website['id']; ?>" url="<?php echo urlencode( $website['url'] ); ?>" idx="<?php echo $idx; ?>">
                <td>
                  <div class="ui fitted slider checkbox">
                    <input type="checkbox" class="mainwp-allow-disallow-clone" <?php echo ( in_array( $website['id'], $disallowedCloneSites ) ) ? '' : 'checked=""'; ?>> <label></label>
                  </div>
                </td>
                <td><a href="<?php echo 'admin.php?page=managesites&dashboard=' . $website['id']; ?>" data-tooltip="<?php esc_attr_e( 'Open the child site overview', 'mainwp-clone-extension' ); ?>" data-inverted=""><?php echo $website['name']; ?></a></td>
                <td>
                  <?php if ( !mainwp_current_user_can( 'dashboard', 'access_wpadmin_on_child_sites' ) ) : ?>
    									<i class="sign in icon"></i>
    							<?php else : ?>
    									<a href="<?php echo 'admin.php?page=SiteOpen&newWindow=yes&websiteid=' . $website['id']; ?>&_opennonce=<?php echo wp_create_nonce( 'mainwp-admin-nonce' ); ?>" data-tooltip="<?php esc_attr_e( 'Jump to the child site WP Admin', 'mainwp-clone-extension' ); ?>" data-inverted="" target="_blank"><i class="sign in icon"></i></a>
    							<?php endif; ?>
                </td>
                <td><a href="<?php echo $website['url']; ?>" target="_blank"><?php echo $website['url']; ?></a></td>
                <td class="center aligned"><?php echo $website['totalsize'] . "MB"; ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot class="full-width">
              <tr>
                <th></th>
                <th colspan="4">
                  <a href="#" id="save-clone-settings" class="ui right floated green button"><?php esc_html_e( 'Save Selection', 'mainwp-clone-extension' ); ?></a>
                  <a class="ui button" id="mainwp-clone-allow-all"><?php esc_html_e( 'Allow All', 'mainwp-clone-extension' ); ?></a>
                  <a class="ui button" id="mainwp-clone-disallow-all"><?php esc_html_e( 'Disallow All', 'mainwp-clone-extension' ); ?></a>
                </th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
      <div class="mainwp-side-content">
        <?php if ( $cloneEnabled ) : ?>
        <div class="ui green message"><?php _e( 'The Clone feature is enabled.', 'mainwp-clone-extension' ); ?></div>
        <?php else  : ?>
        <div class="ui red message"><?php _e( 'The Clone feature is disabled.', 'mainwp-clone-extension' ); ?></div>
        <?php endif; ?>
        <p><?php _e( 'Select sites you want to display in Child Plugin as an Option to Clone.', 'mainwp-clone-extension' ); ?></p>
        <p><?php _e( 'After pressing "Save Selection" you need to press the Sync Sites button to synchronize the settings.', 'mainwp-clone-extension' ); ?></p>
        <a class="ui basic fluid big green button"  href="https://mainwp.com/help/docs/clone-extension/" target="_blank"><?php _e( 'Help Documentation', 'mainwp-clone-extension' ); ?></a>
      </div>
      <div class="ui clearing hidden divider"></div>
    </div>
    <?php
  }

  public static function updateCloneEnabled( $cloneEnabled ) {
    update_option( 'mainwp_clone_enabled', $cloneEnabled );;
    return true;
  }

  public static function updateDisallowedSites( $websiteIds ) {
    update_option( 'mainwp_clone_disallowedsites', is_array( $websiteIds ) ? $websiteIds : array() );
    return true;
  }

}
