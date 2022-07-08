<?php
require('wfBulkCountries.php');

if ($site_id) {
    $w = new MainWP_Wordfence_Config_Site( $site_id ); // new: to load data    
    $site_settings = MainWP_Wordfence_DB::get_instance()->get_setting_by('site_id', $site_id); 
    $extra_settings = unserialize(base64_decode($site_settings->extra_settings));
    $extra_settings['isPaid'] = $w->getVal('isPaid');
    $extra_settings['firewallEnabled'] = $w->getVal('firewallEnabled');
} else {    
    $w = new MainWP_Wordfence_Config(); // new: to load data    
    $extra_settings['isPaid'] = $w->getVal('isPaid');
    $extra_settings['firewallEnabled'] = $w->getVal('firewallEnabled');
    $extra_settings['cbl_action'] = $w->getVal('cbl_action');
    $extra_settings['cbl_redirURL'] = $w->getVal('cbl_redirURL');
    $extra_settings['cbl_loggedInBlocked'] = $w->getVal('cbl_loggedInBlocked');
    $extra_settings['cbl_loginFormBlocked'] = $w->getVal('cbl_loginFormBlocked');
    $extra_settings['cbl_restOfSiteBlocked'] = $w->getVal('cbl_restOfSiteBlocked');
    $extra_settings['cbl_bypassRedirURL'] = $w->getVal('cbl_bypassRedirURL');
    $extra_settings['cbl_bypassRedirDest'] = $w->getVal('cbl_bypassRedirDest');
    $extra_settings['cbl_bypassViewURL'] = $w->getVal('cbl_bypassViewURL', '');
    $extra_settings['cbl_countries'] = $w->getVal('cbl_countries');    
}

?>
<script type="text/javascript">
MWP_WFAD.countryMap = <?php echo json_encode($mainwp_wfBulkCountries); ?>;
</script>
<div>
	<div class="mwp_wordfenceModeElem" id="wordfenceMode_countryBlocking"></div>
<?php if(! $extra_settings['isPaid']){ ?>
	<div class="wf-premium-callout wf-add-bottom">
		<h3>Country Blocking is only available to Premium Members</h3>
		<p>Country blocking is a premium feature that lets you block attacks or malicious activity that originates in a specific country</p>
	</div>
<?php } ?>
	<?php if (!$extra_settings['firewallEnabled']) { ?>
		<div class="wf-notice"><p><strong>Rate limiting rules and advanced blocking are disabled.</strong> You can enable it on the <a href="admin.php?page=WordfenceSecOpt">Wordfence Options page</a> at the top.</p></div>
	<?php } ?>
	
	<h3>Country Blocking Options</h3>
	<div class="wf-form wf-form-horizontal wf-add-top wf-add-bottom">
              <table class="wfConfigForm">
                  <tr><th>
			<label for="wfBlockAction" class="wf-col-sm-2 wf-control-label">What to do when we block someone</label>
			</th><td>                        
                        <div class="wf-col-sm-6">
				<select id="wfBlockAction" class="mwp-wf-form-control">
					<option value="block"<?php if($extra_settings['cbl_action'] == 'block'){ echo ' selected'; } ?>>Show the standard Wordfence blocked message</option>
					<option value="redir"<?php if($extra_settings['cbl_action'] == 'redir'){ echo ' selected'; } ?>>Redirect to the URL below</option>
				</select>
			</div>                            
		</td></tr>
                  
		<tr><th>
			<label for="wfRedirURL" class="wf-col-sm-2 wf-control-label">URL to redirect blocked users to</label>
			</th><td>
                        <div class="wf-col-sm-6">
				<input type="text" id="wfRedirURL" name="wfRedirURL" class="mwp-wf-form-control" value="<?php if($extra_settings['cbl_redirURL']){ echo esc_attr($extra_settings['cbl_redirURL']); } ?>">
				<span class="wf-help-block">Must start with http:// for example http://example.com/blocked/</span>
			</div>
		</td></tr>
		<tr><th>
			<label for="wfLoggedInBlocked" class="wf-col-sm-2 wf-control-label">Block countries even if they are logged in</label>
                        </th><td>
                        <div class="wf-col-sm-6">
				<div class="wf-checkbox"><input type="checkbox" id="wfLoggedInBlocked" name="wfLoggedInBlocked" value="1" <?php if($extra_settings['cbl_loggedInBlocked']){ echo 'checked'; } ?>></div>
			</div>
                       </td></tr>
		</div>
		<tr><th>
			<label for="wfLoginFormBlocked" class="wf-col-sm-2 wf-control-label">Block access to the login form</label>
                        </th><td>
			<div class="wf-col-sm-6">
				<div class="wf-checkbox"><input type="checkbox" id="wfLoginFormBlocked" name="wfLoginFormBlocked" value="1" <?php if($extra_settings['cbl_loginFormBlocked']){ echo 'checked'; } ?>></div>
			</div>
		</td></tr>
		<tr><th>
			<label for="wfRestOfSiteBlocked" class="wf-col-sm-2 wf-control-label">Block access to the rest of the site (outside the login form)</label>
                        </th><td>
			<div class="wf-col-sm-6">
				<div class="wf-checkbox"><input type="checkbox" id="wfRestOfSiteBlocked" name="wfRestOfSiteBlocked" value="1" <?php if($extra_settings['cbl_restOfSiteBlocked']){ echo 'checked'; } ?>></div>
				<span class="wf-help-block">If you use Google Adwords, this is not recommended. <a href="https://docs.wordfence.com/en/Country_blocking#Google_Adwords_says_I_can.27t_block_countries._How_do_I_work_around_that.3F" target="_blank">Learn More</a></span>
			</div>
		</td></tr>
              </table>
	</div>

	<h3>Advanced Country Blocking Options</h3>
	<div class="wf-form wf-form-horizontal wf-add-top wf-add-bottom">
             <table class="wfConfigForm">
		<tr><th style="min-width:30%">
			<label for="wfBypassRedirURL" class="wf-col-sm-2 wf-control-label">Bypass Redirect</label>
                        </th><td>
			<div class="wf-col-sm-6">
				<span class="wf-help-block">If user hits the URL</span><br/>
				<input type="text" id="wfBypassRedirURL" name="wfBypassRedirURL" class="mwp-wf-form-control" value="<?php echo esc_attr($extra_settings['cbl_bypassRedirURL'], array()); ?>"><br/>
				<span class="wf-help-block">then redirect that user to</span><br/>
				<input type="text" id="wfBypassRedirDest" name="wfBypassRedirDest" class="mwp-wf-form-control" value="<?php echo esc_attr($extra_settings['cbl_bypassRedirDest'], array()); ?>"><br/>
				<span class="wf-help-block">and set a cookie that will bypass all country blocking.</span>
			</div>
		</td></tr>
                <tr><th>		
			<label for="wfBypassViewURL" class="wf-col-sm-2 wf-control-label">Bypass Cookie</label>
                        </th><td>
			<div class="wf-col-sm-6">
				<span class="wf-help-block">If user who is allowed to access the site views the URL</span><br/>
				<input type="text" id="wfBypassViewURL" name="wfBypassViewURL" class="mwp-wf-form-control" value="<?php echo esc_attr($extra_settings['cbl_bypassViewURL'], array()); ?>"><br/>
				<span class="wf-help-block">then set a cookie that will bypass country blocking in future in case that user hits the site from a blocked country.</span>
			</div>
		</td></tr>
            </table>
	</div>
	
	<h3>Select which countries to block</h3>
	<div class="wf-add-bottom"><button type="button" class="button-primary wf-countries-shortcut" data-shortcut="select">Block All</button> <button type="button" class="button wf-countries-shortcut" data-shortcut="deselect">Unblock All</button></div>
	<?php
	asort($mainwp_wfBulkCountries);
	$letters = '';
	foreach ($mainwp_wfBulkCountries as $name) {
		$l = strtoupper(substr($name, 0, 1));
		$test = strtoupper(substr($letters, -1));
		if ($l != $test) {
			$letters .= $l; 
		}
	}
	$letters = str_split($letters);
	?>
	<?php
	$current = '';
	foreach ($mainwp_wfBulkCountries as $code => $name) {
		$test = strtoupper(substr($name, 0, 1));
		if ($test != $current) {
			if ($current != '') {
				echo '</ul>';
			}
			$current = $test; 
	?>
		<div class="wf-blocked-countries-section wf-add-top wf-add-bottom-small">
			<div class="wf-blocked-countries-section-title" data-letter="<?php echo $current; ?>"><?php echo $current; ?></div>
			<div class="wf-blocked-countries-section-spacer wf-hidden-xs"></div>
			<ul class="wf-blocked-countries-section-options wf-hidden-xs">
				<?php
				foreach ($letters as $l) {
					echo "<li><a href='#' data-letter='{$l}'" . ($l == $current ? " class='active-section'" : '') . ">{$l}</a></li>";
				}
				?>
			</ul>
		</div>
		<ul class="wf-blocked-countries">
	<?php
		}
		
		echo '<li id="wfCountryCheckbox_' . esc_attr($code) . '" data-country="' . esc_attr($code) . '"><a href="#">' . esc_html($name) . '</a></li>';
	}
	
	if ($current != '') {
		echo '</ul>';
	}
	?>
	
	<p>
		<input type="button" name="but4" class="button-primary" value="Save blocking options and country list" onclick="MWP_WFAD.saveCountryBlocking(<?php esc_html_e($site_id); ?>);"><br>
		<div class="wfAjax24"></div><span class="wfSavedMsg">&nbsp;Your changes have been saved!</span>
	</p>
	
	<span style="font-size: 10px;">Note that we use an IP to country database that is 99.5% accurate to identify which country a visitor is from.</span>
</div>
<script type="text/javascript">
jQuery(function(){ MWP_WFAD.setOwnCountry('<?php //echo mainwp_wfUtils::IP2Country(wfUtils::getIP()); ?>'); });
<?php
if($extra_settings['cbl_countries']){
?>
jQuery(function(){ MWP_WFAD.loadBlockedCountries('<?php echo $extra_settings['cbl_countries']; ?>'); });
<?php
}
?>

	(function($) {             
		function WFScanScheduleSave() {
			var schedMode = $('.wf-card.active').data('mode');

			var schedule = [];
			$('.schedule-day').each(function() {
				var hours = [];
				$(this).find('.time').each(function() {
					hours[$(this).data('hour')] = $(this).hasClass('active') ? '1' : '0';
				});
				schedule[$(this).data('day')] = hours.join(',');
			});
			var schedTxt = schedule.join('|');

			$('.wf-card-subtitle').html('');
			$('.wf-card.active .wf-card-subtitle').html('Updating scan schedule...');

			MWP_WFAD.ajax('wordfence_saveScanSchedule', {
				schedMode: schedMode,
				schedTxt: schedTxt
			}, function(res) {
				if (res.ok) {
					$('.wf-card.active .wf-card-subtitle').html(res.nextStart);
				}
			});
		}
               
		$('.wf-blocked-countries a').on('click', function(e) {
			e.preventDefault();
			e.stopPropagation();

			var selected = $(this).closest('li').hasClass('active');
			if (selected) {
				$(this).closest('li').removeClass('active');
			}
			else {
				$(this).closest('li').addClass('active');
			}

			//WFScanScheduleSave();
		});

		$('.wf-blocked-countries li').on('click', function(e) {
			e.preventDefault();
			e.stopPropagation();

			var selected = $(this).hasClass('active');
			if (selected) {
				$(this).removeClass('active');
			}
			else {
				$(this).addClass('active');
			}

			//WFScanScheduleSave();
		});

		$('.wf-countries-shortcut').on('click', function(e) {
			e.preventDefault();
			e.stopPropagation();

			var mode = $(this).data('shortcut');
			if (mode == 'select') {
				$('.wf-blocked-countries li').addClass('active');
			} else if (mode == 'deselect') {
				$('.wf-blocked-countries li').removeClass('active');
			}

			//WFScanScheduleSave();
		});
		
		$('.wf-blocked-countries-section-options a').on('click', function(e) {
			e.preventDefault();
			e.stopPropagation();
			
			var letter = $(this).data('letter');
			$('html, body').animate({
				scrollTop: $('.wf-blocked-countries-section-title[data-letter=' + letter + ']').offset().top - 100
			}, 1000);
		});
	})(jQuery);
</script>
<script type="text/x-jquery-template" id="wfWelcomeContentCntBlk">
<div>
<h3>Premium Feature: Block or redirect countries</h3>
<strong><p>Being targeted by hackers in a specific country?</p></strong>
<p>
	The premium version of Wordfence offers country blocking.
	This uses a commercial geolocation database to block hackers, spammers
	or other malicious traffic by country with a 99.5% accuracy rate.
</p>
<p>
<?php
if($extra_settings['isPaid']){
?>
	You have upgraded to the premium version of Wordfence and have full access
	to this feature along with our other premium features and priority support.
<?php
} else {
?>
	If you would like access to this premium feature, please 
	<a href="https://www.wordfence.com/gnl1countryBlock2/wordfence-signup/" target="_blank">upgrade to our premium version</a>.
</p>
<?php
}
?>
</div>
</script>
