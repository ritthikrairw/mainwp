<?php

class MainWP_Wordfence_Scan {

  public static function gen_scans_scheduling() {
    
    $current_site_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
    
    $w = MainWP_Wordfence_Setting::get_instance()->load_configs( $current_site_id );
    
    if ( empty( $w ) )
      return;

    if ( $current_site_id ) {
        $is_Paid = MainWP_Wordfence_Config_Site::get( 'isPaid', 0, $current_site_id );
    } else {
        $is_Paid = get_option('mainwp_wordfence_use_premium_general_settings');
    }

    $schedEnabled = $w->get('scheduledScansEnabled', true);
    $schedMode = $w->get('schedMode', 'auto');
    ?>
    <div class="ui dividing header"><?php echo __( 'Scan Scheduling', 'mainwp-wordfence-extension' ); ?></div>

    <div class="ui grid field">
      <label class="six wide column middle aligned"><?php _e( 'Schedule Wordfence scans', 'mainwp-wordfence-extension' ); ?></label>
      <div class="ten wide column ui toggle checkbox">
        <input type="checkbox" id="scheduledScansEnabled"  name="scheduledScansEnabled" onclick="onclick_scheduledScansEnabled(this);" value="1" <?php $w->cb( 'scheduledScansEnabled' ); ?> />
      </div>
    </div>

    <div class="ui grid field">
      <label class="six wide column middle aligned"><?php _e( 'Let Wordfence choose when to scan my site (recommended)', 'mainwp-wordfence-extension' ); ?></label>
      <div class="ten wide column ui radio checkbox">
        <input type="radio" name="schedMode" id="wf-scheduling-mode-automatic" value="auto" <?php echo ($schedMode == 'auto' || !$is_Paid) ? 'checked' : ''; ?> <?php echo !$schedEnabled ? 'disabled' : ''; ?>>
      </div>
    </div>

    <?php if ( $is_Paid ) : ?>
    <div class="ui grid field">
      <label class="six wide column middle aligned"><?php _e( 'Manually schedule scans', 'mainwp-wordfence-extension' ); ?></label>
      <div class="ten wide column ui toggle checkbox">
        <input type="radio" name="schedMode" id="wf-scheduling-mode-manually" value="manual"  <?php echo ($schedMode == 'manual' && $is_Paid) ? 'checked' : ''; ?> <?php echo !$schedEnabled ? 'disabled' : ''; ?> />
      </div>
    </div>
    <?php endif; ?>

    <script type="text/javascript">
      function onclick_scheduledScansEnabled(me) {
        if (jQuery(me).is(":checked")){
          jQuery('#wf-scheduling-mode-automatic').removeAttr('disabled');
          jQuery('#wf-scheduling-mode-manually').removeAttr('disabled');
        } else {
          jQuery('#wf-scheduling-mode-automatic').attr('disabled', 'true');
          jQuery('#wf-scheduling-mode-manually').attr('disabled', 'true');
        }
      };
    </script>
    <?php
  }

  public static function gen_scans_general_settings() {
    
    $current_site_id = isset( $_GET['id'] ) ? $_GET['id'] : 0;
    
    $w = MainWP_Wordfence_Setting::get_instance()->load_configs( $current_site_id );
    
    if ( empty( $w ) )
      return;

    if ($current_site_id) {
      $is_Paid = MainWP_Wordfence_Config_Site::get( 'isPaid', 0 );
    } else {
      $is_Paid = get_option('mainwp_wordfence_use_premium_general_settings');
    }

    ?>
    <div class="ui dividing header"><?php echo __( 'General Options', 'mainwp-wordfence-extension' ); ?></div>

    <?php if ( $is_Paid ) : ?>
    <div class="ui grid field">
      <label class="six wide column middle aligned"><?php _e( 'Check if this website is on a domain blacklist', 'mainwp-wordfence-extension' ); ?></label>
      <div class="ten wide column ui toggle checkbox">
        <input type="checkbox" id="scansEnabled_checkGSB"  name="scansEnabled_checkGSB" value="1" <?php $w->cbp( 'scansEnabled_checkGSB', $is_Paid ); if (!$is_Paid) { ?>onclick="jQuery('#scansEnabled_checkGSB').attr('checked', false); return false;" <?php } ?>>
      </div>
    </div>

    <div class="ui grid field">
      <label class="six wide column middle aligned"><?php _e( 'Check if this website is being "Spamvertised"', 'mainwp-wordfence-extension' ); ?></label>
      <div class="ten wide column ui toggle checkbox">
        <input type="checkbox" id="spamvertizeCheck"  name="spamvertizeCheck" value="1" <?php $w->cbp( 'spamvertizeCheck', $is_Paid ); if ( ! $is_Paid ) {  ?>onclick="jQuery('#spamvertizeCheck').attr('checked', false); return false;" <?php } ?> />
      </div>
    </div>

    <div class="ui grid field">
      <label class="six wide column middle aligned"><?php _e( 'Check if this website IP is generating spam', 'mainwp-wordfence-extension' ); ?></label>
      <div class="ten wide column ui toggle checkbox">
        <input type="checkbox" id="checkSpamIP"  name="checkSpamIP" value="1" <?php $w->cbp( 'checkSpamIP', $is_Paid ); if ( ! $is_Paid ) {  ?>onclick="jQuery('#checkSpamIP').attr('checked', false); return false;" <?php } ?> />
      </div>
    </div>
    <?php endif; ?>

    <div class="ui grid field">
      <label class="six wide column middle aligned"><?php _e( 'Scan for misconfigured How does Wordfence get IPs', 'mainwp-wordfence-extension' ); ?></label>
      <div class="ten wide column ui toggle checkbox">
        <input type="checkbox" id="scansEnabled_checkHowGetIPs"  name="scansEnabled_checkHowGetIPs" value="1" <?php $w->cb( 'scansEnabled_checkHowGetIPs' ); ?> />
      </div>
    </div>

    <div class="ui grid field">
      <label class="six wide column middle aligned"><?php _e( 'Scan for publicly accessible configuration, backup, or log files', 'mainwp-wordfence-extension' ); ?></label>
      <div class="ten wide column ui toggle checkbox">
        <input type="checkbox" id="scansEnabled_checkReadableConfig"  name="scansEnabled_checkReadableConfig" value="1" <?php $w->cb( 'scansEnabled_checkReadableConfig' ); ?> />
      </div>
    </div>

    <div class="ui grid field">
      <label class="six wide column middle aligned"><?php _e( 'Scan for publicly accessible quarantined files', 'mainwp-wordfence-extension' ); ?></label>
      <div class="ten wide column ui toggle checkbox">
        <input type="checkbox" id="scansEnabled_suspectedFiles"  name="scansEnabled_suspectedFiles" value="1" <?php $w->cb( 'scansEnabled_suspectedFiles' ); ?> />
      </div>
    </div>

    <div class="ui grid field">
      <label class="six wide column middle aligned"><?php _e( 'Scan core files against repository versions for changes', 'mainwp-wordfence-extension' ); ?></label>
      <div class="ten wide column ui toggle checkbox">
        <input type="checkbox" id="scansEnabled_core"  name="scansEnabled_core" value="1" <?php $w->cb( 'scansEnabled_core' ); ?>/>
      </div>
    </div>

    <div class="ui grid field">
      <label class="six wide column middle aligned"><?php _e( 'Scan theme files against repository versions for changes', 'mainwp-wordfence-extension' ); ?></label>
      <div class="ten wide column ui toggle checkbox">
        <input type="checkbox" id="scansEnabled_themes"  name="scansEnabled_themes" value="1" <?php $w->cb( 'scansEnabled_themes' ); ?>/>
      </div>
    </div>

    <div class="ui grid field">
      <label class="six wide column middle aligned"><?php _e( 'Scan plugin files against repository versions for changes', 'mainwp-wordfence-extension' ); ?></label>
      <div class="ten wide column ui toggle checkbox">
        <input type="checkbox" id="scansEnabled_plugins"  name="scansEnabled_plugins" value="1" <?php $w->cb( 'scansEnabled_plugins' ); ?>/>
      </div>
    </div>

    <div class="ui grid field">
      <label class="six wide column middle aligned"><?php _e( 'Scan wp-admin and wp-includes for files not bundled with WordPress', 'mainwp-wordfence-extension' ); ?></label>
      <div class="ten wide column ui toggle checkbox">
        <input type="checkbox" id="scansEnabled_coreUnknown"  name="scansEnabled_coreUnknown" value="1" <?php $w->cb( 'scansEnabled_coreUnknown' ); ?>/>
      </div>
    </div>

    <div class="ui grid field">
      <label class="six wide column middle aligned"><?php _e( 'Scan for signatures of known malicious files', 'mainwp-wordfence-extension' ); ?></label>
      <div class="ten wide column ui toggle checkbox">
        <input type="checkbox" id="scansEnabled_malware"  name="scansEnabled_malware" value="1" <?php $w->cb( 'scansEnabled_malware' ); ?>/>
      </div>
    </div>

    <div class="ui grid field">
      <label class="six wide column middle aligned"><?php _e( 'Scan file contents for backdoors, trojans and suspicious code', 'mainwp-wordfence-extension' ); ?></label>
      <div class="ten wide column ui toggle checkbox">
        <input type="checkbox" id="scansEnabled_fileContents"  name="scansEnabled_fileContents" value="1" <?php $w->cb( 'scansEnabled_fileContents' ); ?>/>
      </div>
    </div>

    <div class="ui grid field">
      <label class="six wide column middle aligned"><?php _e( 'Scan file contents for malicious URLs', 'mainwp-wordfence-extension' ); ?></label>
      <div class="ten wide column ui toggle checkbox">
        <input type="checkbox" id="scansEnabled_fileContentsGSB"  name="scansEnabled_fileContentsGSB" value="1" <?php $w->cb( 'scansEnabled_fileContentsGSB' ); ?> />
      </div>
    </div>

    <div class="ui grid field">
      <label class="six wide column middle aligned"><?php _e( 'Scan posts for known dangerous URLs and suspicious content', 'mainwp-wordfence-extension' ); ?></label>
      <div class="ten wide column ui toggle checkbox">
        <input type="checkbox" id="scansEnabled_posts"  name="scansEnabled_posts" value="1" <?php $w->cb( 'scansEnabled_posts' ); ?>/>
      </div>
    </div>

    <div class="ui grid field">
      <label class="six wide column middle aligned"><?php _e( 'Scan comments for known dangerous URLs and suspicious content', 'mainwp-wordfence-extension' ); ?></label>
      <div class="ten wide column ui toggle checkbox">
        <input type="checkbox" id="scansEnabled_comments"  name="scansEnabled_comments" value="1" <?php $w->cb( 'scansEnabled_comments' ); ?>/>
      </div>
    </div>

    <div class="ui grid field">
      <label class="six wide column middle aligned"><?php _e( 'Scan WordPress core, plugin, and theme options for known dangerous URLs and suspicious content', 'mainwp-wordfence-extension' ); ?></label>
      <div class="ten wide column ui toggle checkbox">
        <input type="checkbox" id="scansEnabled_suspiciousOptions"  name="scansEnabled_suspiciousOptions" value="1" <?php $w->cb( 'scansEnabled_suspiciousOptions' ); ?>/>
      </div>
    </div>

    <div class="ui grid field">
      <label class="six wide column middle aligned"><?php _e( 'Scan for out of date, abandoned, and vulnerable plugins, themes, and WordPress versions', 'mainwp-wordfence-extension' ); ?></label>
      <div class="ten wide column ui toggle checkbox">
        <input type="checkbox" id="scansEnabled_oldVersions"  name="scansEnabled_oldVersions" value="1" <?php $w->cb( 'scansEnabled_oldVersions' ); ?>/>
      </div>
    </div>

    <div class="ui grid field">
      <label class="six wide column middle aligned"><?php _e( 'Scan for suspicious admin users created outside of WordPress', 'mainwp-wordfence-extension' ); ?></label>
      <div class="ten wide column ui toggle checkbox">
        <input type="checkbox" id="scansEnabled_suspiciousAdminUsers"  name="scansEnabled_suspiciousAdminUsers" value="1" <?php $w->cb( 'scansEnabled_suspiciousAdminUsers' ); ?>/>
      </div>
    </div>

    <div class="ui grid field">
      <label class="six wide column middle aligned"><?php _e( 'Check the strength of passwords', 'mainwp-wordfence-extension' ); ?></label>
      <div class="ten wide column ui toggle checkbox">
        <input type="checkbox" id="scansEnabled_passwds"  name="scansEnabled_passwds" value="1" <?php $w->cb( 'scansEnabled_passwds' ); ?>/>
      </div>
    </div>

    <div class="ui grid field">
      <label class="six wide column middle aligned"><?php _e( 'Monitor disk space', 'mainwp-wordfence-extension' ); ?></label>
      <div class="ten wide column ui toggle checkbox">
        <input type="checkbox" id="scansEnabled_diskSpace"  name="scansEnabled_diskSpace" value="1" <?php $w->cb( 'scansEnabled_diskSpace' ); ?>/>
      </div>
    </div>

    <div class="ui grid field">
      <label class="six wide column middle aligned"><?php _e( 'Scan files outside your WordPress installation', 'mainwp-wordfence-extension' ); ?></label>
      <div class="ten wide column ui toggle checkbox">
        <input type="checkbox" id="other_scanOutside" name="other_scanOutside" value="1" <?php $w->cb( 'other_scanOutside' ); ?> />
      </div>
    </div>

    <div class="ui grid field">
      <label class="six wide column middle aligned"><?php _e( 'Scan images, binary, and other files as if they were executable', 'mainwp-wordfence-extension' ); ?></label>
      <div class="ten wide column ui toggle checkbox">
        <input type="checkbox" id="scansEnabled_scanImages"  name="scansEnabled_scanImages" value="1" <?php $w->cb( 'scansEnabled_scanImages' ); ?> />
      </div>
    </div>
    <?php
  }

  public static function gen_scans_basic_settings() {
    
    $current_site_id = isset( $_GET['id'] ) ? $_GET['id'] : 0;
    
    $w = MainWP_Wordfence_Setting::get_instance()->load_configs( $current_site_id );
    
    if ( empty( $w ) )
      return;

    $scan_type = $w->get('scanType', MainWP_Wordfence_Config::SCAN_TYPE_STANDARD);

    if ($current_site_id) {
      $is_Paid = MainWP_Wordfence_Config_Site::get( 'isPaid', 0 );
    } else {
      $is_Paid = get_option('mainwp_wordfence_use_premium_general_settings');
    }

    $is_Paid = $is_Paid ? true : false;

    function _wfAllowOnlyBoolean( $value ) {
      return ( $value === false || $value === true );
    }

    $limitedOptions = array_filter(MainWP_wfScanner::limitedScanTypeOptions(), '_wfAllowOnlyBoolean');
    $standardOptions = array_filter(MainWP_wfScanner::standardScanTypeOptions($is_Paid), '_wfAllowOnlyBoolean');
    $highSensitivityOptions = array_filter(MainWP_wfScanner::highSensitivityScanTypeOptions($is_Paid), '_wfAllowOnlyBoolean');

    ?>

    <div class="ui dividing header"><?php echo __( 'Wordfence Basic Scan Type Options', 'mainwp-wordfence-extension' ); ?></div>

    <div class="ui grid field">
      <label class="six wide column"><?php _e( 'Scan type', 'mainwp-wordfence-extension' ); ?></label>
      <div class="ten wide column">
        <div class="ui list">
          <div class="item"><label class="wf-control-label"><input type="radio" class="mwp-wf-scan-type-option" name="scanType" <?php echo ($scan_type  == MainWP_Wordfence_Config::SCAN_TYPE_LIMITED) ? 'checked' : ''; ?>  value="<?php echo MainWP_Wordfence_Config::SCAN_TYPE_LIMITED; ?>" data-selected-options="<?php echo esc_attr(json_encode($limitedOptions)); ?>"> Limited Scan (For entry-level hosting plans. Provides limited detection capability with very low resource utilization.)</label></div>
          <div class="item"><label class="wf-control-label"><input type="radio" class="mwp-wf-scan-type-option" name="scanType" <?php echo ($scan_type  == MainWP_Wordfence_Config::SCAN_TYPE_STANDARD) ? 'checked' : ''; ?> value="<?php echo MainWP_Wordfence_Config::SCAN_TYPE_STANDARD; ?>" data-selected-options="<?php echo esc_attr(json_encode($standardOptions)); ?>"> Standard Scan (Our recommendation for all websites. Provides the best detection capability in the industry.)</label></div>
          <div class="item"><label class="wf-control-label"><input type="radio" class="mwp-wf-scan-type-option" name="scanType" <?php echo ($scan_type  == MainWP_Wordfence_Config::SCAN_TYPE_HIGH_SENSITIVITY) ? 'checked' : ''; ?> value="<?php echo MainWP_Wordfence_Config::SCAN_TYPE_HIGH_SENSITIVITY; ?>" data-selected-options="<?php echo esc_attr(json_encode($highSensitivityOptions)); ?>"> High Sensitivity (For site owners who think they may have been hacked. More thorough but may produce false positives.)</label></div>
          <div class="item"><label class="wf-control-label"><input type="radio" class="mwp-wf-scan-type-option mwp-wf-scan-type-option-custom" name="scanType" <?php echo ($scan_type  == MainWP_Wordfence_Config::SCAN_TYPE_CUSTOM) ? 'checked' : 'disabled'; ?> value="<?php echo MainWP_Wordfence_Config::SCAN_TYPE_CUSTOM; ?>"> Custom Scan (Selected automatically when General Options have been customized for this website.)</label></div>
        </div>
      </div>
    </div>

    <script type="application/javascript">
			(function($) {
				$(function() {
          //Set initial state
          var currentScanType = $('.mwp-wf-scan-type-option:checked');
          if (!currentScanType.hasClass('mwp-wf-scan-type-option-custom')) {
              var selectedOptions = currentScanType.data('selectedOptions');
              var keys = Object.keys(selectedOptions);
              for (var i = 0; i < keys.length; i++) {
                  $('input[name="' + keys[i] + '"]').attr("checked", selectedOptions[keys[i]]); //Currently all checkboxes
              }
          }

                        $('.mwp-wf-scan-type-option').each(function(index, element) {
                               $(element).on('click', function(e) {
                                   if ($(element).hasClass('mwp-wf-scan-type-option-custom')) {
                                       return;
                                   }
                                   $('.mwp-wf-scan-type-option.mwp-wf-scan-type-option-custom').attr('disabled', true);
                                   var selectedOptions = $(this).data('selectedOptions');
                                   var keys = Object.keys(selectedOptions);
                                   for (var i = 0; i < keys.length; i++) {
                                       $('input[name="' + keys[i] + '"]').attr("checked", selectedOptions[keys[i]]); //Currently all checkboxes
                                   }
                               });
                           });

                            //Hook up change events on individual checkboxes
                            var availableOptions = <?php echo json_encode(array_keys($highSensitivityOptions)); ?>;
                            for (var i = 0; i < availableOptions.length; i++) {
                                $('input[name="' + availableOptions[i] + '"]').on('change', function(e) { //Currently all checkboxes
                                    var currentScanType = $('.mwp-wf-scan-type-option:checked');
                                    if (!currentScanType.hasClass('mwp-wf-scan-type-option-custom')) {
                                        $('.mwp-wf-scan-type-option.mwp-wf-scan-type-option-custom').prop('checked', true);
                                        $('.mwp-wf-scan-type-option.mwp-wf-scan-type-option-custom').removeAttr('disabled');
                                    }
                                });
                            }

                });
			})(jQuery);

        </script>
        <?php
    }

  public static function gen_scans_performace_settings() {
    
    $current_site_id = isset( $_GET['id'] ) ? $_GET['id'] : 0;
    
    $w = MainWP_Wordfence_Setting::get_instance()->load_configs( $current_site_id );
    
    if ( empty( $w ) )
      return;
    ?>
    <div class="ui dividing header"><?php echo __( 'Performance Options', 'mainwp-wordfence-extension' ); ?></div>

    <div class="ui grid field">
      <label class="six wide column middle aligned"><?php _e( 'Use low resource scanning (reduces server load by lengthening the scan duration)', 'mainwp-wordfence-extension' ); ?></label>
      <div class="ten wide column ui toggle checkbox">
        <input type="checkbox" id="lowResourceScansEnabled" name="lowResourceScansEnabled" value="1" <?php $w->cb( 'lowResourceScansEnabled' ); ?> />
      </div>
    </div>

    <div class="ui grid field">
      <label class="six wide column middle aligned"><?php _e( 'Limit the number of issues sent in the scan results email', 'mainwp-wordfence-extension' ); ?></label>
      <div class="ten wide column">
        <input type="text" class="wfConfigElem mwp-wf-form-control" name="scan_maxIssues" id="scan_maxIssues" value="<?php $w->f( 'scan_maxIssues' ); ?>">
      </div>
    </div>

    <div class="ui grid field">
      <label class="six wide column middle aligned"><?php _e( 'Time limit that a scan can run in seconds', 'mainwp-wordfence-extension' ); ?></label>
      <div class="ten wide column">
        <input type="text" class="wfConfigElem mwp-wf-form-control" name="scan_maxDuration" id="scan_maxDuration" value="<?php $w->f( 'scan_maxDuration' ); ?>">
      </div>
    </div>

    <div class="ui grid field">
      <label class="six wide column middle aligned"><?php _e( 'How much memory should Wordfence request when scanning', 'mainwp-wordfence-extension' ); ?><br/><p class="description"><?php _e( 'Memory size in megabytes', 'mainwp-wordfence-extension' ); ?></p></label>
      <div class="ten wide column">
        <input type="text" id="maxMem" name="maxMem" value="<?php $w->f( 'maxMem' ); ?>" />
      </div>
    </div>

    <div class="ui grid field">
      <label class="six wide column middle aligned"><?php _e( 'Maximum execution time for each scan stage', 'mainwp-wordfence-extension' ); ?></label>
      <div class="ten wide column">
        <input type="text" id="maxExecutionTime" name="maxExecutionTime" value="<?php $w->f( 'maxExecutionTime' ); ?>" />
      </div>
    </div>
    <?php
  }

  public static function gen_scans_advanced_settings() {
    
    $current_site_id = isset( $_GET['id'] ) ? $_GET['id'] : 0;
    
    $w = MainWP_Wordfence_Setting::get_instance()->load_configs( $current_site_id );
    
    if ( empty( $w ) )
      return;
    ?>
    <div class="ui dividing header"><?php echo __( 'Advanced Scan Options', 'mainwp-wordfence-extension' ); ?></div>

    <div class="ui grid field">
      <label class="six wide column"><?php _e( 'Exclude files from scan that match these wildcard patterns (one per line)', 'mainwp-wordfence-extension' ); ?></label>
      <div class="ten wide column">
        <textarea id="scan_exclude" class="wfConfigElem mwp-wf-form-control" cols="40" rows="4" name="scan_exclude"><?php echo MainWP_Wordfence_Utility::cleanupOneEntryPerLine($w->getHTML( 'scan_exclude' )); ?></textarea>
      </div>
    </div>

    <div class="ui grid field">
      <label class="six wide column"><?php _e( 'Additional scan signatures (one per line)', 'mainwp-wordfence-extension' ); ?></label>
      <div class="ten wide column">
        <textarea class="wfConfigElement mwp-wf-form-control" cols="40" rows="4" name="scan_include_extra"><?php echo $w->getHTML('scan_include_extra'); ?></textarea>
      </div>
    </div>
    <?php
  }

}
