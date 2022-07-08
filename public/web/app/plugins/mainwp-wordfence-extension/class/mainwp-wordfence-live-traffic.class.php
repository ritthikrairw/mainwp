<?php

class MainWP_Wordfence_Live_Traffic {
	private static $option_handle = 'mainwp_wordfence_traffic_option';
	public static $option         = array();
	// Singleton
	private static $instance = null;

	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new MainWP_Wordfence_Live_Traffic();
		}

		return self::$instance;
	}

	public function __construct() {
		self::$option = get_option( self::$option_handle, false );
		if ( ! is_array( self::$option ) ) {
			self::$option = array();
		}
	}


	public static function get( $key = null, $default = '' ) {
		if ( isset( self::$option[ $key ] ) ) {
			return self::$option[ $key ];
		}

		return $default;
	}

	public static function set( $key, $value ) {
		self::$option[ $key ] = $value;

		return update_option( self::$option_handle, self::$option );
	}

	public static function gen_live_traffic_tab( $indi = false ) {
		if ( $indi ) {
			self::gen_individual_traffic_tab();
		} else {
			$count = MainWP_Wordfence_DB::get_instance()->get_count_wfc();
			self::gen_network_live_traffic_tab( $count );
		}
	}

	public static function gen_individual_traffic_tab() {
		$site_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : null;

		if ( empty( $site_id ) ) { ?>
			<div
				class="ui message red"><?php _e( 'Clicking on a "Live Traffic" link at "Wordfence Dashboard" to view Live Traffic on a site.', 'mainwp-wordfence-extension' ); ?>
			</div>
			<?php
			return;
		}

		$w         = new MainWP_Wordfence_Config_Site( $site_id ); // new: to load data
		$cacheType = $w->get_cacheType();
		if ( $w->is_override() ) {
			$liveTrafficEnabled     = MainWP_Wordfence_Config_Site::liveTrafficEnabled( $cacheType );
			$liveTrafficEnabled_opt = MainWP_Wordfence_Config_Site::get( 'liveTrafficEnabled' );
		} else {
			MainWP_Wordfence_Config::load_settings();
			$liveTrafficEnabled     = MainWP_Wordfence_Config::liveTrafficEnabled( $cacheType );
			$liveTrafficEnabled_opt = MainWP_Wordfence_Config::get( 'liveTrafficEnabled' );
		}

		?>
		<div class="mwp_wordfenceModeElem" id="mwp_wordfenceMode_activity"
				 liveTrafficEnabled="<?php echo $liveTrafficEnabled_opt; ?>" cacheType="<?php echo $cacheType; ?>"
				 site-id="<?php echo intval( $site_id ); ?>"></div>
		<?php
		self::gen_live_traffic_scripts( $site_id, $liveTrafficEnabled, $cacheType );
	}


	public static function gen_network_live_traffic_tab( $count ) {
		?>
		<br/>
		<div class="ui dividing header"><?php echo __( 'Live Traffic', 'mainwp-wordfence-extension' ); ?></div>

		<?php
		if ( empty( $count ) ) {
			?>
		<div class="ui yellow message"><?php _e( 'Wordfence pulgin not detected on your sites.', 'mainwp-wordfence-extension' ); ?></div>
			<?php
			return;
		}
		?>
		<div class="mwp_wordfenceModeElem" id="mwp_wordfenceMode_network_activity"></div>
		<?php
		self::gen_live_traffic_scripts();
	}

	public static function gen_live_traffic_options( $is_individual ) {

		$current_site_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
		if ( $is_individual && empty( $current_site_id ) ) {
			return;
		}

		$w = MainWP_Wordfence_Setting::get_instance()->load_configs( $current_site_id );

		if ( empty( $w ) ) {
			return;
		}
		?>

		<div class="ui dividing header"><?php echo __( 'Wordfence Life Traffic Options', 'mainwp-wordfence-extension' ); ?></div>
		<div class="ui grid field">
		  <label class="six wide column middle aligned"><?php _e( 'Enable live traffic logging', 'mainwp-wordfence-extension' ); ?></label>
		  <div class="ten wide column ui">
		   		<select id="liveTrafficEnabled" name="liveTrafficEnabled" class="ui dropdown">
					<option value="0" <?php $w->sel( 'liveTrafficEnabled', 0 ); ?>><?php _e( 'Security Only', 'mainwp-wordfence-extension' ); ?></option>
					<option value="1"<?php $w->sel( 'liveTrafficEnabled', 1 ); ?>><?php _e( 'All Traffic', 'mainwp-wordfence-extension' ); ?></option>
				</select>
		  </div>
		</div>
		<div class="ui grid field">
		  <label class="six wide column middle aligned"><?php _e( 'Don\'t log signed-in users with publishing access', 'mainwp-wordfence-extension' ); ?></label>
		  <div class="ten wide column ui toggle checkbox">
				<input type="checkbox" id="liveTraf_ignorePublishers" name="liveTraf_ignorePublishers" value="1" <?php $w->cb( 'liveTraf_ignorePublishers' ); ?> />
		  </div>
		</div>

		<div class="ui grid field">
		  <label class="six wide column middle aligned"><?php _e( 'Always display expanded Live Traffic records', 'mainwp-wordfence-extension' ); ?></label>
		  <div class="ten wide column ui toggle checkbox">
				<input type="checkbox" id="liveTraf_displayExpandedRecords" name="liveTraf_displayExpandedRecords" value="1" <?php $w->cb( 'liveTraf_displayExpandedRecords' ); ?> />
		  </div>
		</div>

		<div class="ui grid field">
		  <label class="six wide column middle aligned"><?php _e( 'List of comma separated usernames to ignore', 'mainwp-wordfence-extension' ); ?></label>
		  <div class="ten wide column">
				<input type="text" class="mwp-wf-form-control" name="liveTraf_ignoreUsers" id="liveTraf_ignoreUsers" value="<?php echo $w->getHTML( 'liveTraf_ignoreUsers' ); ?>"/>
		  </div>
		</div>

		<div class="ui grid field">
		  <label class="six wide column middle aligned"><?php _e( 'List of comma separated IP addresses to ignore', 'mainwp-wordfence-extension' ); ?></label>
		  <div class="ten wide column">
				<input type="text" class="mwp-wf-form-control" name="liveTraf_ignoreIPs" id="liveTraf_ignoreIPs" value="<?php echo $w->getHTML( 'liveTraf_ignoreIPs' ); ?>"/>
		  </div>
		</div>

		<div class="ui grid field">
		  <label class="six wide column middle aligned"><?php _e( 'Browser user-agent to ignore', 'mainwp-wordfence-extension' ); ?></label>
		  <div class="ten wide column">
				<input type="text" class="mwp-wf-form-control" name="liveTraf_ignoreUA" id="liveTraf_ignoreUA" value="<?php echo $w->getHTML( 'liveTraf_ignoreUA' ); ?>"/>
		  </div>
		</div>

		<div class="ui grid field">
		  <label class="six wide column middle aligned"><?php _e( 'Amount of Live Traffic data to store (number of rows)', 'mainwp-wordfence-extension' ); ?></label>
		  <div class="ten wide column">
				<input type="text" class="mwp-wf-form-control" name="liveTraf_maxRows" id="liveTraf_maxRows" value="<?php $w->f( 'liveTraf_maxRows' ); ?>"/>
		  </div>
		</div>

		<div class="ui grid field">
		  <label class="six wide column middle aligned"><?php _e( 'Maximum days to keep Live Traffic data (minimum: 1)', 'mainwp-wordfence-extension' ); ?></label>
		  <div class="ten wide column">
				<input type="text" class="mwp-wf-form-control" name="liveTraf_maxAge" id="liveTraf_maxAge" value="<?php $w->f( 'liveTraf_maxAge' ); ?>"/>
		  </div>
		</div>

		<div class="ui grid field">
		  <label class="six wide column middle aligned"><?php _e( 'Display top level Live Traffic menu option', 'mainwp-wordfence-extension' ); ?></label>
		  <div class="ten wide column ui toggle checkbox">
				<input type="checkbox" id="displayTopLevelLiveTraffic" name="displayTopLevelLiveTraffic" value="1" <?php $w->cb( 'displayTopLevelLiveTraffic' ); ?> />
		  </div>
		</div>
		<?php if ( $is_individual ) : ?>
	  <div class="ui divider"></div>
	  <input type="submit" value="<?php _e( 'Save Settings', 'mainwp-wordfence-extension' ); ?>" class="ui big green right floated button" id="submit" name="submit">
	<?php else : ?>
		<?php MainWP_Wordfence_Setting::gen_save_general_button(); ?>
	<?php endif; ?>
		<?php
	}

	static function gen_live_traffic_scripts( $site_id = null, $liveTrafficEnabled = false, $cacheType = '' ) {
	?>
<br/>
<div class="wordfenceModeElem" id="wordfenceMode_liveTraffic"></div>

<div id="wf-live-traffic" class="wf-row wf-live-traffic-display-expanded">
	<div class="wf-col-xs-12">
		<div class="wf-block wf-active">
			<div class="wf-block-content">
				<div class="wf-container-fluid">
					<div class="wf-row">
						<div class="wf-col-xs-12">
							<?php
							$overridden = false;
							if ( $site_id && ! $liveTrafficEnabled ) :
								?>
								<div id="wordfenceLiveActivityDisabled"><p>
									<strong><?php _e( 'Live activity is disabled', 'wordfence' ); ?>
									<?php
									if ( $overridden ) {
										_e( ' by the host', 'wordfence' );
									}
									?>
									.</strong> <?php _e( 'Login and firewall activity will still appear below.', 'wordfence' ); ?></p>
								</div>
							<?php endif ?>
							<div class="wf-row wf-add-bottom-small">
								<div class="wf-col-xs-12" id="wf-live-traffic-legend-wrapper">

									<form data-bind="submit: reloadListings">

										<div class="wf-clearfix">
											<div id="wf-live-traffic-legend-placeholder"></div>
											<div id="wf-live-traffic-legend">
												<ul>
													<li class="wfHuman"><?php _e( 'Human', 'wordfence' ); ?></li>
													<li class="wfBot"><?php _e( 'Bot', 'wordfence' ); ?></li>
													<li class="wfNotice"><?php _e( 'Warning', 'wordfence' ); ?></li>
													<li class="wfBlocked"><?php _e( 'Blocked', 'wordfence' ); ?></li>
												</ul>
											</div>

											<div class="wfActEvent wf-live-traffic-filter">
												<select id="wf-lt-preset-filters" data-bind="options: presetFiltersOptions, optionsText: presetFiltersOptionsText, value: selectedPresetFilter">
												</select>
												&nbsp;&nbsp;
												<input id="wf-live-traffic-filter-show-advanced" class="wf-option-checkbox" data-bind="checked: showAdvancedFilters" type="checkbox">
												<label for="wf-live-traffic-filter-show-advanced">
													<?php _e( 'Show Advanced Filters', 'wordfence' ); ?>
												</label>
											</div>
										</div>

										<div data-bind="visible: showAdvancedFilters" id="wf-lt-advanced-filters">
											<div class="wf-live-traffic-filter-detail">
												<div>
													<div data-bind="foreach: filters">
														<div class="wf-live-traffic-filter-item">
															<div class="wf-live-traffic-filter-item-parameters">
																<div>
																	<select name="param[]" class="wf-lt-advanced-filters-param" data-bind="options: filterParamOptions, optionsText: filterParamOptionsText, value: selectedFilterParamOptionValue, optionsCaption: 'Filter...'"></select>
																</div>
																<div data-bind="visible: selectedFilterParamOptionValue() && selectedFilterParamOptionValue().type() != 'bool'">
																	<select name="operator[]" class="wf-lt-advanced-filters-operator" data-bind="options: filterOperatorOptions, optionsText: filterOperatorOptionsText, value: selectedFilterOperatorOptionValue"></select>
																</div>
																<div data-bind="attr: {colSpan: (selectedFilterParamOptionValue() && selectedFilterParamOptionValue().type() == 'bool' ? 2 : 1)}" class="wf-lt-advanced-filters-value-cell">
																	<span data-bind="if: selectedFilterParamOptionValue() && selectedFilterParamOptionValue().type() == 'enum'">
																		<select data-bind="options: selectedFilterParamOptionValue().values, optionsText: selectedFilterParamOptionValue().optionsText, value: value"></select>
																	</span>

																	<span data-bind="if: selectedFilterParamOptionValue() && selectedFilterParamOptionValue().type() == 'text'">
																		<input data-bind="value: value" type="text">
																	</span>

																	<span data-bind="if: selectedFilterParamOptionValue() && selectedFilterParamOptionValue().type() == 'bool'">
																		<label>Yes <input data-bind="checked: value" type="radio" value="1"></label>
																		<label>No <input data-bind="checked: value" type="radio" value="0"></label>
																	</span>
																</div>
															</div>
															<div>
																<a href="#" data-bind="click: $root.removeFilter" class="wf-live-traffic-filter-remove"><i class="wf-ion-trash-a"></i></a>
															</div>
														</div>
													</div>
													<div>
														<div class="wf-pad-small">
															<button type="button" class="wf-btn wf-btn-default" data-bind="click: addFilter">
																Add Filter
															</button>
														</div>
													</div>
												</div>
												<div class="wf-form wf-form-horizontal">
													<div class="wf-form-group">
														<label for="wf-live-traffic-from" class="wf-col-sm-2">From:&nbsp;</label>
														<div class="wf-col-sm-10">
															<input placeholder="Start date" id="wf-live-traffic-from" type="text" class="wf-datetime" data-bind="value: startDate, datetimepicker: null, datepickerOptions: { timeFormat: 'hh:mm tt z' }">
															<button data-bind="click: startDate('')" class="wf-btn wf-btn-default wf-btn-sm" type="button">Clear</button>
														</div>
													</div>
													<div class="wf-form-group">
														<label for="wf-live-traffic-to" class="wf-col-sm-2">To:&nbsp;</label>
														<div class="wf-col-sm-10">
															<input placeholder="End date" id="wf-live-traffic-to" type="text" class="wf-datetime" data-bind="value: endDate, datetimepicker: null, datepickerOptions: { timeFormat: 'hh:mm tt z' }">
															<button data-bind="click: endDate('')" class="wf-btn wf-btn-default wf-btn-sm" type="button">Clear</button>
														</div>
													</div>
													<div class="wf-form-group">
														<label for="wf-live-traffic-group-by" class="wf-col-sm-2">Group&nbsp;By:&nbsp;</label>
														<div class="wf-col-sm-10">
															<select id="wf-live-traffic-group-by" name="groupby" class="wf-lt-advanced-filters-groupby" data-bind="options: filterGroupByOptions, optionsText: filterGroupByOptionsText, value: groupBy, optionsCaption: 'None'"></select>
														</div>
													</div>
												</div>
											</div>
										</div>
									</form>
								</div>
							</div>
							<div class="wf-row">
								<div class="wf-col-xs-12">
									<div id="wf-live-traffic-group-by" class="wf-block" data-bind="if: groupBy(), visible: groupBy()">
										<ul class="wf-filtered-traffic wf-block-list" data-bind="foreach: listings">
											<li class="wf-flex-row wf-padding-add-top wf-padding-add-bottom">
												<div class="wf-flex-row-1">
													<!-- ko if: $root.groupBy().param() == 'ip' -->
													<div data-bind="if: loc()">
														<img data-bind="attr: { src: '<?php echo MainWP_Wordfence_Extension::getBaseURL() . 'images/flags/'; ?>' + loc().countryCode.toLowerCase() + '.png',
																	alt: loc().countryName, title: loc().countryName }" width="16" height="11"
																class="wfFlag"/>
														<a data-bind="text: (loc().city ? loc().city + ', ' : '') + loc().countryName,
																	attr: { href: 'http://maps.google.com/maps?q=' + loc().lat + ',' + loc().lon + '&z=6' }"
																target="_blank" rel="noopener noreferrer"></a>
													</div>
													<div data-bind="if: !loc()">
														An unknown location at IP
														<span data-bind="text: IP" target="_blank" rel="noopener noreferrer"></span>
													</div>

													<div>
														<strong>IP:</strong>
														<span data-bind="text: IP" target="_blank" rel="noopener noreferrer"></span>
														<span data-bind="if: blocked()">
														[<a data-bind="click: $root.unblockIP">unblock</a>]
													</span>
														<span data-bind="if: rangeBlocked()">
														[<a data-bind="click: $root.unblockNetwork">unblock this range</a>]
													</span>
														<span data-bind="if: !blocked() && !rangeBlocked()">
														[<a data-bind="click: $root.blockIP">block</a>]
													</span>
													</div>
													<div>
														<span class="wfReverseLookup"><span data-bind="text: IP" style="display:none;"></span></span>
													</div>
													<!-- /ko -->
													<!-- ko if: $root.groupBy().param() == 'type' -->
													<div>
														<strong>Type:</strong>
														<span data-bind="if: jsRun() == '1'">Human</span>
														<span data-bind="if: jsRun() == '0'">Bot</span>
													</div>
													<!-- /ko -->
													<!-- ko if: $root.groupBy().param() == 'user_login' -->
													<div>
														<strong>Username:</strong>
														<span data-bind="text: username()"></span>
													</div>
													<!-- /ko -->
													<!-- ko if: $root.groupBy().param() == 'statusCode' -->
													<div>
														<strong>HTTP Response Code:</strong>
														<span data-bind="text: statusCode()"></span>
													</div>
													<!-- /ko -->
													<!-- ko if: $root.groupBy().param() == 'action' -->
													<div>
														<strong>Firewall Response:</strong>
														<span data-bind="text: firewallAction()"></span>
													</div>
													<!-- /ko -->
													<!-- ko if: $root.groupBy().param() == 'url' -->
													<div>
														<strong>URL:</strong>
														<span data-bind="text: displayURL()"></span>
													</div>
													<!-- /ko -->
													<div>
														<strong>Last Hit:</strong> <span
																data-bind="attr: { 'data-timestamp': ctime, text: 'Last hit was ' + ctime() + ' ago.' }"
																class="wfTimeAgo wfTimeAgo-timestamp"></span>
													</div>
												</div>
												<div class="wf-flex-row-0 wf-padding-add-left">
													<span class="wf-filtered-traffic-hits" data-bind="text: hitCount"></span> hits
												</div>
											</li>

										</ul>
									</div>

									<div id="wf-live-traffic-no-group-by" data-bind="if: !groupBy()">
										<table class="wf-striped-table">
											<thead>
											<tr>
												<th>Type</th>
												<th>Location</th>
												<th>Page Visited</th>
												<th>Time</th>
												<th>IP Address</th>
												<th>Hostname</th>
												<th>Response</th>
												<th>View</th>
											</tr>
											</thead>
											<tbody id="wf-lt-listings" class="wf-filtered-traffic" data-bind="foreach: listings">
											<tr data-bind="click: toggleDetails, css: { odd: ($index() % 2 == 1), even: ($index() % 2 == 0), 'wf-details-open': showDetails, highlighted: highlighted }" class="wf-summary-row">
												<td class="wf-center">
													<span data-bind="attr: { 'class': cssClasses }"></span>
												</td>
												<td>
													<span class="wf-flex-horizontal" data-bind="if: loc()">
														<img data-bind="attr: { src: '<?php echo MainWP_Wordfence_Extension::getBaseURL() . 'images/flags/'; ?>' + loc().countryCode.toLowerCase() + '.png',
															alt: loc().countryName, title: loc().countryName }" width="16"
																height="11"
																class="wfFlag"/>
														<span class="wf-padding-add-left-small" data-bind="text: (loc().city ? loc().city + ', ' : '') + loc().countryName"></span>
													</span>
													<span class="wf-flex-horizontal" data-bind="if: !loc()">
														<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 64.22 64.37" class="wfFlag wf-flag-unspecified"><path d="M64,28.21a30.32,30.32,0,0,0-5.8-14.73A31.6,31.6,0,0,0,37.43.56C35.7.26,33.94.18,32.2,0h-.35C30.22.18,28.58.3,27,.55A32.14,32.14,0,0,0,.2,35.61,31.4,31.4,0,0,0,10.4,55.87a31.24,31.24,0,0,0,25,8.33,30.5,30.5,0,0,0,18.94-8.79C62,47.94,65.15,38.8,64,28.21ZM57.21,44.68a23.94,23.94,0,0,1-2.3-5.08c-.66-2.45-2.27-.08-2.4,1.52s-1.2,2.8-3.33.4-2.54-1.87-3.2-1.87-1.87,1.6-1.6,9.07c.19,5.33,2.29,6.18,3.67,6.56a27.16,27.16,0,0,1-8.78,4A27.55,27.55,0,0,1,7.85,45.13C2.27,34.4,5,22.26,10.67,15.57c.15,1.21.3,2.29.43,3.37a27.63,27.63,0,0,1-.52,8.79,4.39,4.39,0,0,0,.08,1.94,1.3,1.3,0,0,0,.94.76c.27,0,.75-.41.86-.73a8.27,8.27,0,0,0,.27-1.86c0-.44,0-.89.07-1.58a10.67,10.67,0,0,1,1.06.86c.7.7,1.4,1.4,2,2.15a2.11,2.11,0,0,1,.56,1.21,3.44,3.44,0,0,0,.83,2.13,12.21,12.21,0,0,1,1.07,2.57c.14.37.17.78.33,1.13a2,2,0,0,0,1.8,1.32c1,.07,1.32.44,1.46,1.43l-.74.08c-1.17.11-1.75.65-1.71,1.83a8.43,8.43,0,0,0,2.69,6c.48.45,1,.87,1.46,1.33a3.35,3.35,0,0,1,.92,3.75,12.18,12.18,0,0,0-.69,2.09,6,6,0,0,0,.06,2.23c.18.75.1,2.37.86,2.24,1.36-.24,2.14,0,2.25-1.49a1.22,1.22,0,0,0-.08-.6c-.4-1.42,1.42-5.47,2.52-6.2a27.11,27.11,0,0,0,2.73-2,3.6,3.6,0,0,0,1.26-4,3.22,3.22,0,0,1,1.14-3.59,4.54,4.54,0,0,0,1.71-3.65c-.08-1.53-1.07-2.63-2.37-2.47a9.21,9.21,0,0,0-1.87.59,20.62,20.62,0,0,1-2.72.9c-1.31.23-2.11-.62-2.69-1.66-.47-.83-.63-.9-1.44-.38s-1.37.89-2.08,1.28S22,35.58,21.45,35a5.79,5.79,0,0,0-1.24-.88c-.31-.19-.73-.24-1-.48s-.8-.8-.75-1.15a1.69,1.69,0,0,1,.95-1.1,14.36,14.36,0,0,1,2.29-.51,7.33,7.33,0,0,0,1.22-.33c.52-.21.5-.56.1-.89a3.26,3.26,0,0,0-.69-.37l-3.52-1.39a4.74,4.74,0,0,1-.84-.43c-.74-.49-.83-1-.16-1.61,2.64-2.33,5.72-3,8.45.08.84,1,1.42,2.16,2.22,3.16a12.5,12.5,0,0,0,2.15,2.15,1.62,1.62,0,0,0,1.44.09,1.15,1.15,0,0,0,.29-1.56,8.43,8.43,0,0,0-.86-1.41,5.16,5.16,0,0,1,1.59-7.52,4.38,4.38,0,0,0,2.53-2.58c-.58.16-1,.26-1.42.39-2.3.71-.7-1,.36-1.31.65-.18-.58-.67-.58-.67s.82-.28,1.69-.65a6.85,6.85,0,0,0,1.7-.94,3.79,3.79,0,0,0,.66-1.17l-.16-.18-1.83.24c-1,.11-1.27-.09-1.37-1.14a1,1,0,0,0-1.48-.73c-.45.25-.85.61-1.29.9-1,.66-1.78.39-2.19-.75-.23-.68-.57-.81-1.19-.42-.31.18-.58.47-.89.64a11.53,11.53,0,0,1-1.62.79c-.55.19-1.21.33-1.58-.27a1.25,1.25,0,0,1,.46-1.68A14.78,14.78,0,0,1,27,10c1-.56,2.07-1,3-1.65a1.78,1.78,0,0,0,.79-2.07.88.88,0,0,0-1.37-.65c-.56.28-1.06.72-1.63,1a2.81,2.81,0,0,1-1.41.08c-.17,0-.35-.49-.35-.76s.31-.43.51-.46c1.4-.22,2.81-.41,4.22-.57a.76.76,0,0,1,.58.25,6.84,6.84,0,0,0,3.6,2.15c1.15.34,1.31.18,1.47-1,1.48-.34,3-1,4.46-.09A14.4,14.4,0,0,1,43.14,8c.18.17.07.7,0,1s-.36.87-.48,1.33a1.2,1.2,0,0,0,1.26,1.56c.29,0,.57-.07.86-.08.85,0,1.14.28,1.07,1.13-.11,1.21.09,1.35,1.31,1.15a2.07,2.07,0,0,1,1.67.64c1.14.86,2,.54,2.33-.86,0-.16,0-.32.06-.47.14-.63.49-.79.92-.35.9,1,1.74,2,2.66,3a3,3,0,0,0-.8,3.07,5.19,5.19,0,0,1-.55,3.27A24.63,24.63,0,0,0,52.2,25.5c-.45,1.57.06,2.3,1.66,2.65s1.78.64,1.84,2.14a4.85,4.85,0,0,0,2.92,4.35c.4.19.82.34,1.23.51a25.22,25.22,0,0,1-2.64,9.53Z"/></svg> <span class="wf-padding-add-left-small">Unspecified</span>
													</span>
												</td>
												<td>
													<span class="wf-lt-url wf-split-word-xs"
															data-bind="text: displayURLShort, attr: { title: URL }"></span>
												</td>
												<td class="wf-nowrap" data-bind="text: timestamp"></td>
												<td>
													<span data-bind="attr: { title: IP }, text: $root.trimIP(IP())"></span>
												</td>
												<td>
													<span class="wfReverseLookup" data-reverse-lookup-template="wf-live-traffic-hostname-template">
														<span data-bind="text: IP" style="display:none;"></span>
													</span>
												</td>
												<td data-bind="text: statusCode"></td>
												<td class="wf-live-traffic-show-details">
													<span class="wf-ion-eye"></span>
													<span class="wf-ion-eye-disabled"></span>
												</td>
											</tr>
											<tr data-bind="css: {
												'wf-details-visible': showDetails,
												'wf-details-hidden': !(showDetails()),
												highlighted: highlighted,
												odd: ($index() % 2 == 1), even: ($index() % 2 == 0) }" class="wf-details-row">
												<td colspan="8" data-bind="attr: { id: ('wfActEvent_' + id()) }" class="wf-live-traffic-details">
													<div class="wf-live-traffic-activity-detail-wrapper">
														<div class="wf-live-traffic-activity-type">
															<div data-bind="attr: { 'class': typeIconClass }"></div>
															<div data-bind="text: typeText"></div>
														</div>
														<div class="wf-live-traffic-activity-detail">
															<h2>Activity Detail</h2>
															<div>
																<span data-bind="if: action() != 'loginOK' && action() != 'loginFailValidUsername' && action() != 'loginFailInvalidUsername' && user()">
																	<span data-bind="html: user.avatar" class="wfAvatar"></span>
																	<a data-bind="attr: { href: user.editLink }, text: user().display_name"
																			target="_blank" rel="noopener noreferrer"></a>
																</span>
																<span data-bind="if: loc()">
																	<span data-bind="if: action() != 'loginOK' && action() != 'loginFailValidUsername' && action() != 'loginFailInvalidUsername' && user()"> in</span>
																	<img data-bind="attr: { src: '<?php echo MainWP_Wordfence_Extension::getBaseURL() . 'images/flags/'; ?>' + loc().countryCode.toLowerCase() + '.png',
																		alt: loc().countryName, title: loc().countryName }" width="16"
																			height="11"
																			class="wfFlag"/>
																	<a data-bind="text: (loc().city ? loc().city + ', ' : '') + loc().countryName,
																		attr: { href: 'http://maps.google.com/maps?q=' + loc().lat + ',' + loc().lon + '&z=6' }"
																			target="_blank" rel="noopener noreferrer"></a>
																</span>
																<span data-bind="if: !loc()">
																	<span data-bind="text: action() != 'loginOK' && action() != 'loginFailValidUsername' && action() != 'loginFailInvalidUsername' && user() ? 'at an' : 'An'"></span> unknown location at IP
																	<a data-bind="text: IP, attr: { href: MWP_WFAD.makeIPTrafLink(IP(), site_id()) }"
																			target="_blank" rel="noopener noreferrer"></a>
																</span>
																<span data-bind="if: referer()">
																	<span data-bind="if: extReferer()">
																		arrived from <a data-bind="text: LiveTrafficViewModel.truncateText(referer(), 100), attr: { title: referer, href: referer }"
																				target="_blank" rel="noopener noreferrer"
																				class="wf-split-word-xs"></a> and
																	</span>
																	<span data-bind="if: !extReferer()">
																		left <a data-bind="text: LiveTrafficViewModel.truncateText(referer(), 100), attr: { title: referer, href: referer }"
																				target="_blank" rel="noopener noreferrer"
																				class="wf-split-word-xs"></a> and
																	</span>
																</span>
																<span data-bind="if: statusCode() == 404">
																	tried to access <span style="color: #F00;">non-existent page</span>
																</span>

																<span data-bind="if: statusCode() == 200 && !action()">
																	visited
																</span>
																<span data-bind="if: statusCode() == 403 || statusCode() == 503">
																	was <span data-bind="text: firewallAction" style="color: #F00;"></span> at
																</span>

																<span data-bind="if: action() == 'loginOK'">
																	logged in successfully as "<strong data-bind="text: username"></strong>".
																</span>
																<span data-bind="if: action() == 'logout'">
																	logged out successfully.
																</span>
																<span data-bind="if: action() == 'lostPassword'">
																	requested a password reset.
																</span>
																<span data-bind="if: action() == 'loginFailValidUsername'">
																	attempted a failed login as "<strong data-bind="text: username"></strong>".
																</span>
																<span data-bind="if: action() == 'loginFailInvalidUsername'">
																	attempted a failed login using an invalid username "<strong
																			data-bind="text: username"></strong>".
																</span>
																<span data-bind="if: action() == 'user:passwordReset'">
																	changed their password.
																</span>
																<a class="wf-lt-url wf-split-word-xs"
																		data-bind="text: displayURL, attr: { href: URL, title: URL }"
																		target="_blank" rel="noopener noreferrer"></a>
															</div>
															<div>
																<span data-bind="text: timeAgo, attr: { 'data-timestamp': ctime }"
																		class="wfTimeAgo-timestamp"></span>&nbsp;&nbsp;
															</div>
															<div>
																<strong>IP:</strong> <span data-bind="text: IP"></span>
																<span class="wfReverseLookup">
																	<span data-bind="text: IP" style="display:none;"></span>
																</span>
																<span data-bind="if: blocked()">
																	<a href="#" class="wf-btn wf-btn-default wf-btn-sm wf-block-ip-btn"
																			data-bind="click: unblockIP">
																		Unblock IP
																	</a>
																</span>
																<span data-bind="if: rangeBlocked()">
																	<a href="#" class="wf-btn wf-btn-default wf-btn-sm wf-block-ip-btn"
																			data-bind="click: unblockNetwork">Unblock range
																	</a>
																</span>
																<span data-bind="if: !blocked() && !rangeBlocked()">
																	<a class="wf-btn wf-btn-default wf-btn-sm wf-block-ip-btn"
																			data-bind="click: blockIP">
																		Block IP
																	</a>
																</span>
															</div>
															<div data-bind="visible: (jQuery.inArray(parseInt(statusCode(), 10), [403, 503, 404]) !== -1)">
																<strong>Human/Bot:</strong> <span data-bind="text: (jsRun() === '1' ? 'Human' : 'Bot')"></span>
															</div>
															<div data-bind="if: browser() && browser().browser != 'Default Browser'">
																<strong>Browser:</strong>
																<span data-bind="text: browser().browser +
																(browser().version ? ' version ' + browser().version : '') +
																(browser().platform  && browser().platform != 'unknown' ? ' running on ' + browser().platform : '')
																"></span>
															</div>
															<div class="wf-split-word" data-bind="text: UA"></div>
															<div class="wf-live-traffic-actions">
																<span data-bind="if: blocked()">
																	<a href="#" class="wf-btn wf-btn-default wf-btn-sm"
																			data-bind="click: unblockIP">
																		Unblock IP
																	</a>
																</span>
																<span data-bind="if: rangeBlocked()">
																	<a href="#" class="wf-btn wf-btn-default wf-btn-sm"
																			data-bind="click: unblockNetwork">Unblock range
																	</a>
																</span>
																<span data-bind="if: !blocked() && !rangeBlocked()">
																	<a href="#" class="wf-btn wf-btn-default wf-btn-sm"
																			data-bind="click: blockIP">
																		Block IP
																	</a>
																</span>
																<a class="wf-btn wf-btn-default wf-btn-sm" data-bind="click: showWhoisOverlay,
																attr: { href: 'admin.php?page=WordfenceTools&whoisval=' + IP() + '#top#whois' }"
																		target="_blank" rel="noopener noreferrer">Run Whois</a>
																<a class="wf-btn wf-btn-default wf-btn-sm"
																		data-bind="click: showRecentTraffic, attr: { href: MWP_WFAD.makeIPTrafLink(IP(), site_id()) }" target="_blank" rel="noopener noreferrer">
																	<span class="wf-hidden-xs"><?php _e( 'See recent traffic', 'wordfence' ); ?></span><span class="wf-visible-xs"><?php _e( 'Recent', 'wordfence' ); ?></span>
																</a>
																<span data-bind="if: action() == 'blocked:waf'">
																	<a href="#" class="wf-btn wf-btn-default wf-btn-sm"
																			data-bind="click: function () { $root.whitelistWAFParamKey(actionData().path, actionData().paramKey, actionData().failedRules) }"
																			title="If this is a false positive, you can exclude this parameter from being filtered by the firewall">
																		Whitelist param from Firewall
																	</a>
																	<?php if ( false && WFWAF_DEBUG ) : ?>
																		<a href="#" class="wf-btn wf-btn-default wf-btn-sm"
																				data-bind="attr: { href: '<?php echo esc_js( home_url() ); ?>?_wfsf=debugWAF&nonce=' + WFAD.nonce + '&hitid=' + id() }" target="_blank" rel="noopener noreferrer">
																			Debug this Request
																		</a>
																	<?php endif ?>
																</span>

																 <?php if ( empty( $site_id ) ) { ?>
																	<span data-bind="if: blocked()">
																	<a href="#" class="wf-btn wf-btn-default wf-btn-sm"
																			data-bind="click: unblockIPNetwork">
																		UnBlock IP across your MainWP network
																	</a>
																	</span>
																	<span data-bind="if: !blocked() && !rangeBlocked()">
																		<a href="#" class="wf-btn wf-btn-default wf-btn-sm"
																				data-bind="click: blockIPNetwork">
																			Block IP across your MainWP network
																		</a>
																	</span>
																<?php } ?>


															</div>
														</div>
													</div>
												</td>
											</tr>
											</tbody>
										</table>
									</div>
									<div class="wf-live-traffic-none" data-bind="if: listings().length == 0">
										No requests to report yet.
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div id="wf-live-traffic-util-overlay-wrapper" style="display: none">
	<div class="wf-live-traffic-util-overlay">
		<div class="wf-live-traffic-util-overlay-header"></div>
		<div class="wf-live-traffic-util-overlay-body"></div>
		<span class="wf-live-traffic-util-overlay-close wf-ion-android-close"></span>
	</div>
</div>

<div id="wfrawhtml"></div>

<script type="text/x-jquery-template" id="wf-live-traffic-hostname-template">
	<span title="${ip}">${(ip && ip.length > 22) ? '...' + ip.substring(ip.length - 22) : ip}</span>
</script>


		  <?php
	}

}
