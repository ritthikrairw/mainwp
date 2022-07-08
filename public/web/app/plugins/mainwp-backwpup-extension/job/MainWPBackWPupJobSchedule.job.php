<?php

class MainWPBackWPupJobSchedule extends MainWPBackWPupJob {

	public function save_form( $settings ) {
		if ( $_POST['activetype'] == '' || $_POST['activetype'] == 'wpcron' || $_POST['activetype'] == 'link' ) {
			$settings['activetype'] = $_POST['activetype'];
		}

		$settings['cronselect'] = ( isset( $_POST['cronselect'] ) && $_POST['cronselect'] == 'advanced' ? 'advanced' : 'basic' );
		$settings['cronbtype']  = ( isset( $_POST['cronbtype'] ) && in_array( $_POST['cronbtype'], array(
			'mon',
			'week',
			'day',
			'hour'
		) ) ? $_POST['cronbtype'] : '' );

		if ( $settings['cronselect'] == 'advanced' ) {
			if ( empty( $_POST['cronminutes'] ) || $_POST['cronminutes'][0] == '*' ) {
				if ( ! empty( $_POST['cronminutes'][1] ) ) {
					$_POST['cronminutes'] = array( '*/' . $_POST['cronminutes'][1] );
				} else {
					$_POST['cronminutes'] = array( '*' );
				}
			}
			if ( empty( $_POST['cronhours'] ) || $_POST['cronhours'][0] == '*' ) {
				if ( ! empty( $_POST['cronhours'][1] ) ) {
					$_POST['cronhours'] = array( '*/' . $_POST['cronhours'][1] );
				} else {
					$_POST['cronhours'] = array( '*' );
				}
			}
			if ( empty( $_POST['cronmday'] ) || $_POST['cronmday'][0] == '*' ) {
				if ( ! empty( $_POST['cronmday'][1] ) ) {
					$_POST['cronmday'] = array( '*/' . $_POST['cronmday'][1] );
				} else {
					$_POST['cronmday'] = array( '*' );
				}
			}
			if ( empty( $_POST['cronmon'] ) || $_POST['cronmon'][0] == '*' ) {
				if ( ! empty( $_POST['cronmon'][1] ) ) {
					$_POST['cronmon'] = array( '*/' . $_POST['cronmon'][1] );
				} else {
					$_POST['cronmon'] = array( '*' );
				}
			}
			if ( empty( $_POST['cronwday'] ) || $_POST['cronwday'][0] == '*' ) {
				if ( ! empty( $_POST['cronwday'][1] ) ) {
					$_POST['cronwday'] = array( '*/' . $_POST['cronwday'][1] );
				} else {
					$_POST['cronwday'] = array( '*' );
				}
			}

			$cron = implode( ",", $_POST['cronminutes'] ) . ' ' . implode( ",", $_POST['cronhours'] ) . ' ' . implode( ",", $_POST['cronmday'] ) . ' ' . implode( ",", $_POST['cronmon'] ) . ' ' . implode( ",", $_POST['cronwday'] );

			$settings['cron'] = $cron;

			// we also need $_POST values for saving them in child
			$settings['cronminutes'] = $_POST['cronminutes'];
			$settings['cronhours']   = $_POST['cronhours'];
			$settings['cronmday']    = $_POST['cronmday'];
			$settings['cronmon']     = $_POST['cronmon'];
			$settings['cronwday']    = $_POST['cronwday'];
		} else {
			if ( $settings['cronbtype'] == 'mon' ) {
				$_POST['moncronminutes'] = ( isset( $_POST['moncronminutes'] ) ? $_POST['moncronminutes'] : '1' );
				$_POST['moncronhours']   = ( isset( $_POST['moncronhours'] ) ? $_POST['moncronhours'] : '1' );
				$_POST['moncronmday']    = ( isset( $_POST['moncronmday'] ) ? $_POST['moncronmday'] : '1' );

				$settings['moncronminutes'] = $_POST['moncronminutes'];
				$settings['moncronhours']   = $_POST['moncronhours'];
				$settings['moncronmday']    = $_POST['moncronmday'];

				$settings['cron'] = $_POST['moncronminutes'] . ' ' . $_POST['moncronhours'] . ' ' . $_POST['moncronmday'] . ' * *';
			} else if ( $settings['cronbtype'] == 'week' ) {
				$_POST['weekcronminutes'] = ( isset( $_POST['weekcronminutes'] ) ? $_POST['weekcronminutes'] : '1' );
				$_POST['weekcronhours']   = ( isset( $_POST['weekcronhours'] ) ? $_POST['weekcronhours'] : '1' );
				$_POST['weekcronwday']    = ( isset( $_POST['weekcronwday'] ) ? $_POST['weekcronwday'] : '1' );

				$settings['weekcronminutes'] = $_POST['weekcronminutes'];
				$settings['weekcronhours']   = $_POST['weekcronhours'];
				$settings['weekcronwday']    = $_POST['weekcronwday'];

				$settings['cron'] = $_POST['weekcronminutes'] . ' ' . $_POST['weekcronhours'] . ' * * ' . $_POST['weekcronwday'];
			} else if ( $settings['cronbtype'] == 'day' ) {
				$_POST['daycronminutes'] = ( isset( $_POST['daycronminutes'] ) ? $_POST['daycronminutes'] : '1' );
				$_POST['daycronhours']   = ( isset( $_POST['daycronhours'] ) ? $_POST['daycronhours'] : '1' );

				$settings['daycronminutes'] = $_POST['daycronminutes'];
				$settings['daycronhours']   = $_POST['daycronhours'];

				$settings['cron'] = $_POST['daycronminutes'] . ' ' . $_POST['daycronhours'] . ' * * *';
			} else if ( $settings['cronbtype'] == 'hour' ) {
				$settings['hourcronminutes'] = $_POST['hourcronminutes'];
				$settings['cron']            = $_POST['hourcronminutes'] . ' * * * *';
			}
		}

		return $settings;
	}

	public function render_form( $information ) {
		$default = $information['default'];

		?>
		<div ng-show="is_selected_2('<?php echo $this->tab_name; ?>')">
			<style type="text/css" media="screen">
				#cron-min, #cron-hour, #cron-day, #cron-month, #cron-weekday {
					overflow: auto;
					white-space: nowrap;
					height: 7em;
				}

				#cron-min-box, #cron-hour-box, #cron-day-box, #cron-month-box, #cron-weekday-box {
					border-color: gray;
					border-style: solid;
					border-width: 1px;
					margin: 10px 0px 10px 10px;
					padding: 2px 2px;
					width: 100px;
					float: left;
				}

				#wpcronbasic {
					border-collapse: collapse;
				}

				#wpcronbasic th, #wpcronbasic td {
					width: 80px;
					border-bottom: 1px solid gray;
				}
			</style>
			<div class="ui segment form">


			<form action="<?php echo esc_attr( $this->current_page ); ?>" method="post">
				<input type="hidden" name="our_id" value="<?php echo esc_attr( $this->our_id ); ?>">
				<input type="hidden" name="job_id" value="<?php echo esc_attr( $this->job_id ); ?>">
				<input type="hidden" name="website_id" value="<?php echo esc_attr( $this->website_id ); ?>">
				<input type="hidden" name="job_tab" value="<?php echo esc_attr( $this->original_tab_name ); ?>">
				<?php wp_nonce_field( MainWPBackWPupExtension::$nonce_token . 'update_jobs' ); ?>
				<h3 class="ui dividing header"><?php _e( 'Job Schedule', $this->plugin_translate ) ?></h3>
				<div class="ui grid field">
					<label class="six wide column"><?php _e( 'Start job', $this->plugin_translate ); ?></label>
				  <div class="ten wide column ui list">
						<div class="item ui radio checkbox"><input class="radio" type="radio"
							<?php
							if ( MainWPBackWPUpView::get_value( $default, 'activetype', '' ) == '' ) {
								echo ' ng-checked="1"';
							}
							?> name="activetype" id="idactivetype" value=""
								 ng-model="job_schedule_radio_value"/> <label><?php _e( 'manually only', $this->plugin_translate ); ?></label></div>
						<div class="item ui radio checkbox">
							<input class="radio" type="radio"
								<?php
								if ( MainWPBackWPUpView::get_value( $default, 'activetype', '' ) == 'wpcron' ) {
									echo ' ng-checked="1" ng-init="job_schedule_radio_value=\'wpcron\'"';
								}
								?> name="activetype" id="idactivetype-wpcron" value="wpcron"
									 ng-model="job_schedule_radio_value"/> <label><?php _e( 'with WordPress cron', $this->plugin_translate ); ?></label>
						</div>
						<div class="item ui radio checkbox">
							<input class="radio help-tip" type="radio"
								<?php
								if ( MainWPBackWPUpView::get_value( $default, 'activetype', '' ) == 'link' ) {
									echo ' ng-checked="1" ng-init="job_schedule_radio_value=\'link\'"';
								}
								?> name="activetype" id="idactivetype-link" value="link"
									 title="<?php esc_attr_e( 'Copy the link for an external start. This option has to be activated to make the link work.', $this->plugin_translate ) ?>"
									 ng-model="job_schedule_radio_value"/> <label><?php _e( 'with a link', $this->plugin_translate ); ?></label>
						</div>
					</div>
				</div>

				<div class="wpcron" ng-show="job_schedule_radio_value=='wpcron'" >
					<h3 class="ui dividing header"><?php _e( 'Schedule execution time', $this->plugin_translate ) ?></h3>
					<div class="ui grid field">
						<label class="six wide column"><?php _e( 'Scheduler type', $this->plugin_translate ); ?></label>
					  <div class="ten wide column ui list">
							<div class="item ui radio checkbox"><input class="radio" type="radio"
								<?php
								if ( MainWPBackWPUpView::get_value( $default, 'cronselect', '' ) == 'basic' ) {
									echo ' ng-checked="1" ng-init="scheduler_type_value=\'basic\'"';
								}
								?> name="cronselect" id="idcronselect-basic" value="basic"
									 ng-model="scheduler_type_value"/> <label for="idcronselect-basic"><?php _e( 'basic', $this->plugin_translate ); ?></label></div>
							<div class="item ui radio checkbox"><input class="radio" type="radio"
								<?php
								if ( MainWPBackWPUpView::get_value( $default, 'cronselect', '' ) == 'advanced' ) {
									echo ' ng-checked="1" ng-init="scheduler_type_value=\'advanced\'"';
								}
								?> name="cronselect" id="idcronselect-advanced" value="advanced"
									 ng-model="scheduler_type_value"/> <label for="idcronselect-advanced"><?php _e( 'advanced', $this->plugin_translate ); ?></label></div>
						</div>
					</div>

					<?php

					list( $cronstr['minutes'], $cronstr['hours'], $cronstr['mday'], $cronstr['mon'], $cronstr['wday'] ) = explode( ' ', MainWPBackWPUpView::get_value( $default, 'cron', '* * * * *' ), 5 );
					if ( strstr( $cronstr['minutes'], '*/' ) ) {
						$minutes = explode( '/', $cronstr['minutes'] );
					} else {
						$minutes = explode( ',', $cronstr['minutes'] );
					}

					if ( strstr( $cronstr['hours'], '*/' ) ) {
						$hours = explode( '/', $cronstr['hours'] );
					} else {
						$hours = explode( ',', $cronstr['hours'] );
					}
					if ( strstr( $cronstr['mday'], '*/' ) ) {
						$mday = explode( '/', $cronstr['mday'] );
					} else {
						$mday = explode( ',', $cronstr['mday'] );
					}
					if ( strstr( $cronstr['mon'], '*/' ) ) {
						$mon = explode( '/', $cronstr['mon'] );
					} else {
						$mon = explode( ',', $cronstr['mon'] );
					}
					if ( strstr( $cronstr['wday'], '*/' ) ) {
						$wday = explode( '/', $cronstr['wday'] );
					} else {
						$wday = explode( ',', $cronstr['wday'] );
					}
					?>

					<div class="ui grid field wpcronbasic" ng-show="scheduler_type_value=='basic'">
						<label class="six wide column"><?php _e( 'Scheduler', $this->plugin_translate ); ?></label>
					  <div class="six wide column">
							<table id="wpcronbasic ui table">
								<tr>
									<th>
										<?php _e( 'Type', $this->plugin_translate ); ?>
									</th>
									<th>
									</th>
									<th>
										<?php _e( 'Hour', $this->plugin_translate ); ?>
									</th>
									<th>
										<?php _e( 'Minute', $this->plugin_translate ); ?>
									</th>
								</tr>
								<tr>
									<td><label
											for="idcronbtype-mon"><?php echo '<input class="radio" type="radio"' . checked( true, is_numeric( $mday[0] ), false ) . ' name="cronbtype" id="idcronbtype-mon" value="mon" /> ' . __( 'monthly', $this->plugin_translate ); ?></label>
									</td>
									<td><select name="moncronmday"><?php for ( $i = 1; $i <= 31; $i ++ ) {
												echo '<option ' . selected( in_array( "$i", $mday, true ), true, false ) . '  value="' . $i . '" />' . __( 'on', $this->plugin_translate ) . ' ' . $i . '.</option>';
											} ?></select></td>
									<td><select name="moncronhours"><?php for ( $i = 0; $i < 24; $i ++ ) {
												echo '<option ' . selected( in_array( "$i", $hours, true ), true, false ) . '  value="' . $i . '" />' . $i . '</option>';
											} ?></select></td>
									<td><select
											name="moncronminutes"><?php for ( $i = 0; $i < 60; $i = $i + 5 ) {
												echo '<option ' . selected( in_array( "$i", $minutes, true ), true, false ) . '  value="' . $i . '" />' . $i . '</option>';
											} ?></select></td>
								</tr>
								<tr>
									<td><label
											for="idcronbtype-week"><?php echo '<input class="radio" type="radio"' . checked( true, is_numeric( $wday[0] ), false ) . ' name="cronbtype" id="idcronbtype-week" value="week" /> ' . __( 'weekly', $this->plugin_translate ); ?></label>
									</td>
									<td><select name="weekcronwday">
											<?php echo '<option ' . selected( in_array( "0", $wday, true ), true, false ) . '  value="0" />' . __( 'Sunday', $this->plugin_translate ) . '</option>';
											echo '<option ' . selected( in_array( "1", $wday, true ), true, false ) . '  value="1" />' . __( 'Monday', $this->plugin_translate ) . '</option>';
											echo '<option ' . selected( in_array( "2", $wday, true ), true, false ) . '  value="2" />' . __( 'Tuesday', $this->plugin_translate ) . '</option>';
											echo '<option ' . selected( in_array( "3", $wday, true ), true, false ) . '  value="3" />' . __( 'Wednesday', $this->plugin_translate ) . '</option>';
											echo '<option ' . selected( in_array( "4", $wday, true ), true, false ) . '  value="4" />' . __( 'Thursday', $this->plugin_translate ) . '</option>';
											echo '<option ' . selected( in_array( "5", $wday, true ), true, false ) . '  value="5" />' . __( 'Friday', $this->plugin_translate ) . '</option>';
											echo '<option ' . selected( in_array( "6", $wday, true ), true, false ) . '  value="6" />' . __( 'Saturday', $this->plugin_translate ) . '</option>'; ?>
										</select></td>
									<td><select name="weekcronhours"><?php for ( $i = 0; $i < 24; $i ++ ) {
												echo '<option ' . selected( in_array( "$i", $hours, true ), true, false ) . '  value="' . $i . '" />' . $i . '</option>';
											} ?></select></td>
									<td><select
											name="weekcronminutes"><?php for ( $i = 0; $i < 60; $i = $i + 5 ) {
												echo '<option ' . selected( in_array( "$i", $minutes, true ), true, false ) . '  value="' . $i . '" />' . $i . '</option>';
											} ?></select></td>
								</tr>
								<tr>
									<td><label
											for="idcronbtype-day"><?php echo '<input class="radio" type="radio"' . checked( "**", $mday[0] . $wday[0], false ) . ' name="cronbtype" id="idcronbtype-day" value="day" /> ' . __( 'daily', $this->plugin_translate ); ?></label>
									</td>
									<td></td>
									<td><select name="daycronhours"><?php for ( $i = 0; $i < 24; $i ++ ) {
												echo '<option ' . selected( in_array( "$i", $hours, true ), true, false ) . '  value="' . $i . '" />' . $i . '</option>';
											} ?></select></td>
									<td><select
											name="daycronminutes"><?php for ( $i = 0; $i < 60; $i = $i + 5 ) {
												echo '<option ' . selected( in_array( "$i", $minutes, true ), true, false ) . '  value="' . $i . '" />' . $i . '</option>';
											} ?></select></td>
								</tr>
								<tr>
									<td><label
											for="idcronbtype-hour"><?php echo '<input class="radio" type="radio"' . checked( "*", $hours[0], false, false ) . ' name="cronbtype" id="idcronbtype-hour" value="hour" /> ' . __( 'hourly', $this->plugin_translate ); ?></label>
									</td>
									<td></td>
									<td></td>
									<td><select
											name="hourcronminutes"><?php for ( $i = 0; $i < 60; $i = $i + 5 ) {
												echo '<option ' . selected( in_array( "$i", $minutes, true ), true, false ) . '  value="' . $i . '" />' . $i . '</option>';
											} ?></select></td>
								</tr>
							</table>
						</div>
					</div>

					<div class="ui grid field wpcronadvanced" ng-show="scheduler_type_value=='advanced'">
						<label class="six wide column"><?php _e( 'Scheduler', $this->plugin_translate ); ?></label>
					  <div class="ten wide column">
							<div id="cron-min-box">
								<b><?php _e( 'Minutes:', $this->plugin_translate ); ?></b><br/>
								<?php
								echo '<label for="idcronminutes"><input class="checkbox" type="checkbox"' . checked( in_array( "*", $minutes, true ), true, false ) . ' name="cronminutes[]" id="idcronminutes" value="*" /> ' . __( 'Any (*)', $this->plugin_translate ) . '</label><br />';
								?>
								<div id="cron-min"><?php
									for ( $i = 0; $i < 60; $i = $i + 5 ) {
										echo '<label for="idcronminutes-' . $i . '"><input class="checkbox" type="checkbox"' . checked( in_array( "$i", $minutes, true ), true, false ) . ' name="cronminutes[]" id="idcronminutes-' . $i . '" value="' . $i . '" /> ' . $i . '</label><br />';
									}
									?>
								</div>
							</div>
							<div id="cron-hour-box">
								<b><?php _e( 'Hours:', $this->plugin_translate ); ?></b><br/>
								<?php

								echo '<label for="idcronhours"><input class="checkbox" type="checkbox"' . checked( in_array( "*", $hours, true ), true, false ) . ' name="cronhours[]" for="idcronhours" value="*" /> ' . __( 'Any (*)', $this->plugin_translate ) . '</label><br />';
								?>
								<div id="cron-hour"><?php
									for ( $i = 0; $i < 24; $i ++ ) {
										echo '<label for="idcronhours-' . $i . '"><input class="checkbox" type="checkbox"' . checked( in_array( "$i", $hours, true ), true, false ) . ' name="cronhours[]" id="idcronhours-' . $i . '" value="' . $i . '" /> ' . $i . '</label><br />';
									}
									?>
								</div>
							</div>
							<div id="cron-day-box">
								<b><?php _e( 'Day of Month:', $this->plugin_translate ); ?></b><br/>
								<label for="idcronmday"><input class="checkbox"
																							 type="checkbox"<?php checked( in_array( "*", $mday, true ), true, true ); ?>
																							 name="cronmday[]" id="idcronmday"
																							 value="*"/> <?php _e( 'Any (*)', $this->plugin_translate ); ?>
								</label>
								<br/>

								<div id="cron-day">
									<?php
									for ( $i = 1; $i <= 31; $i ++ ) {
										echo '<label for="idcronmday-' . $i . '"><input class="checkbox" type="checkbox"' . checked( in_array( "$i", $mday, true ), true, false ) . ' name="cronmday[]" id="idcronmday-' . $i . '" value="' . $i . '" /> ' . $i . '</label><br />';
									}
									?>
								</div>
							</div>
							<div id="cron-month-box">
								<b><?php _e( 'Month:', $this->plugin_translate ); ?></b><br/>
								<?php
								echo '<label for="idcronmon"><input class="checkbox" type="checkbox"' . checked( in_array( "*", $mon, true ), true, false ) . ' name="cronmon[]" id="idcronmon" value="*" /> ' . __( 'Any (*)', $this->plugin_translate ) . '</label><br />';
								?>
								<div id="cron-month">
									<?php
									echo '<label for="idcronmon-1"><input class="checkbox" type="checkbox"' . checked( in_array( "1", $mon, true ), true, false ) . ' name="cronmon[]" id="idcronmon-1" value="1" /> ' . __( 'January', $this->plugin_translate ) . '</label><br />';
									echo '<label for="idcronmon-2"><input class="checkbox" type="checkbox"' . checked( in_array( "2", $mon, true ), true, false ) . ' name="cronmon[]" id="idcronmon-2" value="2" /> ' . __( 'February', $this->plugin_translate ) . '</label><br />';
									echo '<label for="idcronmon-3"><input class="checkbox" type="checkbox"' . checked( in_array( "3", $mon, true ), true, false ) . ' name="cronmon[]" id="idcronmon-3" value="3" /> ' . __( 'March', $this->plugin_translate ) . '</label><br />';
									echo '<label for="idcronmon-4"><input class="checkbox" type="checkbox"' . checked( in_array( "4", $mon, true ), true, false ) . ' name="cronmon[]" id="idcronmon-4" value="4" /> ' . __( 'April', $this->plugin_translate ) . '</label><br />';
									echo '<label for="idcronmon-5"><input class="checkbox" type="checkbox"' . checked( in_array( "5", $mon, true ), true, false ) . ' name="cronmon[]" id="idcronmon-5" value="5" /> ' . __( 'May', $this->plugin_translate ) . '</label><br />';
									echo '<label for="idcronmon-6"><input class="checkbox" type="checkbox"' . checked( in_array( "6", $mon, true ), true, false ) . ' name="cronmon[]" id="idcronmon-6" value="6" /> ' . __( 'June', $this->plugin_translate ) . '</label><br />';
									echo '<label for="idcronmon-7"><input class="checkbox" type="checkbox"' . checked( in_array( "7", $mon, true ), true, false ) . ' name="cronmon[]" id="idcronmon-7" value="7" /> ' . __( 'July', $this->plugin_translate ) . '</label><br />';
									echo '<label for="idcronmon-8"><input class="checkbox" type="checkbox"' . checked( in_array( "8", $mon, true ), true, false ) . ' name="cronmon[]" id="idcronmon-8" value="8" /> ' . __( 'August', $this->plugin_translate ) . '</label><br />';
									echo '<label for="idcronmon-9"><input class="checkbox" type="checkbox"' . checked( in_array( "9", $mon, true ), true, false ) . ' name="cronmon[]" id="idcronmon-9" value="9" /> ' . __( 'September', $this->plugin_translate ) . '</label><br />';
									echo '<label for="idcronmon-10"><input class="checkbox" type="checkbox"' . checked( in_array( "10", $mon, true ), true, false ) . ' name="cronmon[]" id="idcronmon-10" value="10" /> ' . __( 'October', $this->plugin_translate ) . '</label><br />';
									echo '<label for="idcronmon-11"><input class="checkbox" type="checkbox"' . checked( in_array( "11", $mon, true ), true, false ) . ' name="cronmon[]" id="idcronmon-11" value="11" /> ' . __( 'November', $this->plugin_translate ) . '</label><br />';
									echo '<label for="idcronmon-12"><input class="checkbox" type="checkbox"' . checked( in_array( "12", $mon, true ), true, false ) . ' name="cronmon[]" id="idcronmon-12" value="12" /> ' . __( 'December', $this->plugin_translate ) . '</label><br />';
									?>
								</div>
							</div>
							<div id="cron-weekday-box">
								<b><?php _e( 'Day of Week:', $this->plugin_translate ); ?></b><br/>
								<?php
								echo '<label for="idcronwday"><input class="checkbox" type="checkbox"' . checked( in_array( "*", $wday, true ), true, false ) . ' name="cronwday[]" id="idcronwday" value="*" /> ' . __( 'Any (*)', $this->plugin_translate ) . '</label><br />';
								?>
								<div id="cron-weekday">
									<?php
									echo '<label for="idcronwday-0"><input class="checkbox" type="checkbox"' . checked( in_array( "0", $wday, true ), true, false ) . ' name="cronwday[]" id="idcronwday-0" value="0" /> ' . __( 'Sunday', $this->plugin_translate ) . '</label><br />';
									echo '<label for="idcronwday-1"><input class="checkbox" type="checkbox"' . checked( in_array( "1", $wday, true ), true, false ) . ' name="cronwday[]" id="idcronwday-1" value="1" /> ' . __( 'Monday', $this->plugin_translate ) . '</label><br />';
									echo '<label for="idcronwday-2"><input class="checkbox" type="checkbox"' . checked( in_array( "2", $wday, true ), true, false ) . ' name="cronwday[]" id="idcronwday-2" value="2" /> ' . __( 'Tuesday', $this->plugin_translate ) . '</label><br />';
									echo '<label for="idcronwday-3"><input class="checkbox" type="checkbox"' . checked( in_array( "3", $wday, true ), true, false ) . ' name="cronwday[]" id="idcronwday-3" value="3" /> ' . __( 'Wednesday', $this->plugin_translate ) . '</label><br />';
									echo '<label for="idcronwday-4"><input class="checkbox" type="checkbox"' . checked( in_array( "4", $wday, true ), true, false ) . ' name="cronwday[]" id="idcronwday-4" value="4" /> ' . __( 'Thursday', $this->plugin_translate ) . '</label><br />';
									echo '<label for="idcronwday-5"><input class="checkbox" type="checkbox"' . checked( in_array( "5", $wday, true ), true, false ) . ' name="cronwday[]" id="idcronwday-5" value="5" /> ' . __( 'Friday', $this->plugin_translate ) . '</label><br />';
									echo '<label for="idcronwday-6"><input class="checkbox" type="checkbox"' . checked( in_array( "6", $wday, true ), true, false ) . ' name="cronwday[]" id="idcronwday-6" value="6" /> ' . __( 'Saturday', $this->plugin_translate ) . '</label><br />';
									?>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="ui divider"></div>
				<input type="submit" name="submit" id="submit" class="ui big green right floated button" value="<?php _e( 'Save Changes', $this->plugin_translate ); ?>"/>
				<div class="ui hidden clearing divider"></div>
			</form>
			</div>
		</div>
		
	<script type="text/javascript">
		jQuery(document).ready(function ($) {
			$('input[name="activetype"]').change(function () {
				if ( $(this).val() == 'wpcron' || $(this).val() == 'easycron') {
					$('.wpcron').show();
				} else {
					$('.wpcron').hide();
				}
				$('.wpcron').removeClass('ng-hide'); // to fix conflict with ng-show
			});

			if ($('input[name="activetype"]:checked').val() == 'wpcron' || $('input[name="activetype"]:checked').val() == 'easycron' ) {
				$('.wpcron').show();
			} else {
				$('.wpcron').hide();
			}

			$('input[name="cronselect"]').change(function () {
				if ('basic' == $('input[name="cronselect"]:checked').val()) {
					$('.wpcronadvanced').hide();
					$('.wpcronbasic').show();
					cronstampbasic();
				} else {
					$('.wpcronadvanced').show();
					$('.wpcronbasic').hide();
					cronstampadvanced();
				}
				$('.wpcronbasic').removeClass('ng-hide');
				$('.wpcronadvanced').removeClass('ng-hide'); 
			});

			function cronstampadvanced() {
				var cronminutes = [];
				var cronhours = [];
				var cronmday = [];
				var cronmon = [];
				var cronwday = [];
				$('input[name="cronminutes[]"]:checked').each(function () {
					cronminutes.push($(this).val());
				});
				$('input[name="cronhours[]"]:checked').each(function () {
					cronhours.push($(this).val());
				});
				$('input[name="cronmday[]"]:checked').each(function () {
					cronmday.push($(this).val());
				});
				$('input[name="cronmon[]"]:checked').each(function () {
					cronmon.push($(this).val());
				});
				$('input[name="cronwday[]"]:checked').each(function () {
					cronwday.push($(this).val());
				});
				var data = {
					action:'backwpup_cron_text',
					cronminutes:cronminutes,
					cronhours:cronhours,
					cronmday:cronmday,
					cronmon:cronmon,
					cronwday:cronwday,
					crontype:'advanced',
					_ajax_nonce:$('#backwpupajaxnonce').val()
				};
				$.post(ajaxurl, data, function (response) {
					$('#schedulecron').replaceWith(response);
				});
			}
			$('input[name="cronminutes[]"]').change(function () {
				cronstampadvanced();
			});
			$('input[name="cronhours[]"]').change(function () {
				cronstampadvanced();
			});
			$('input[name="cronmday[]"]').change(function () {
				cronstampadvanced();
			});
			$('input[name="cronmon[]"]').change(function () {
				cronstampadvanced();
			});
			$('input[name="cronwday[]"]').change(function () {
				cronstampadvanced();
			});

			function cronstampbasic() {
				var cronminutes = [];
				var cronhours = [];
				var cronmday = [];
				var cronmon = [];
				var cronwday = [];
				if ('mon' == $('input[name="cronbtype"]:checked').val()) {
					cronminutes.push($('select[name="moncronminutes"]').val());
					cronhours.push($('select[name="moncronhours"]').val());
					cronmday.push($('select[name="moncronmday"]').val());
					cronmon.push('*');
					cronwday.push('*');
				}
				if ('week' == $('input[name="cronbtype"]:checked').val()) {
					cronminutes.push($('select[name="weekcronminutes"]').val());
					cronhours.push($('select[name="weekcronhours"]').val());
					cronmday.push('*');
					cronmon.push('*');
					cronwday.push($('select[name="weekcronwday"]').val());
				}
				if ('day' == $('input[name="cronbtype"]:checked').val()) {
					cronminutes.push($('select[name="daycronminutes"]').val());
					cronhours.push($('select[name="daycronhours"]').val());
					cronmday.push('*');
					cronmon.push('*');
					cronwday.push('*');
				}
				if ('hour' == $('input[name="cronbtype"]:checked').val()) {
					cronminutes.push($('select[name="hourcronminutes"]').val());
					cronhours.push('*');
					cronmday.push('*');
					cronmon.push('*');
					cronwday.push('*');
				}
				var data = {
					action:'backwpup_cron_text',
					cronminutes:cronminutes,
					cronhours:cronhours,
					cronmday:cronmday,
					cronmon:cronmon,
					cronwday:cronwday,
					crontype:'basic',
					_ajax_nonce:$('#backwpupajaxnonce').val()
				};
				$.post(ajaxurl, data, function (response) {
					$('#schedulecron').replaceWith(response);
				});
			}
			$('input[name="cronbtype"]').change(function () {
				cronstampbasic();
			});
			$('select[name="moncronmday"]').change(function () {
				cronstampbasic();
			});
			$('select[name="moncronhours"]').change(function () {
				cronstampbasic();
			});
			$('select[name="moncronminutes"]').change(function () {
				cronstampbasic();
			});
			$('select[name="weekcronwday"]').change(function () {
				cronstampbasic();
			});
			$('select[name="weekcronhours"]').change(function () {
				cronstampbasic();
			});
			$('select[name="weekcronminutes"]').change(function () {
				cronstampbasic();
			});
			$('select[name="daycronhours"]').change(function () {
				cronstampbasic();
			});
			$('select[name="daycronminutes"]').change(function () {
				cronstampbasic();
			});
			$('select[name="hourcronminutes"]').change(function () {
				cronstampbasic();
			});
		});
	</script>
	<?php
	}
}
