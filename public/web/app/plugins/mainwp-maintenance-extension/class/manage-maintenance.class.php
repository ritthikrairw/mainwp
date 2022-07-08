<?php

class Manage_Maintenance {
	private static $instance = null;
	private $schedules       = array();
	private $options         = array();
	private $errors          = array();

	static function get_instance() {

		if ( null == self::$instance ) {
			self::$instance = new Manage_Maintenance();
		}
		return self::$instance;
	}

	// Constructor
	function __construct() {

		$this->schedules = array(
			'daily'   => __( 'Day', 'mainwp-maintenance-extension' ),
			'weekly'  => __( 'Week', 'mainwp-maintenance-extension' ),
			'monthly' => __( 'Month', 'mainwp-maintenance-extension' ),
			'yearly'  => __( 'Year', 'mainwp-maintenance-extension' ),
		);

		$this->options = array(
			'revisions'    => __( 'Delete all post revisions', 'mainwp-maintenance-extension' ),
			'autodraft'    => __( 'Delete all auto draft posts', 'mainwp-maintenance-extension' ),
			'trashpost'    => __( 'Delete trash posts', 'mainwp-maintenance-extension' ),
			'spam'         => __( 'Delete spam comments', 'mainwp-maintenance-extension' ),
			'pending'      => __( 'Delete pending comments', 'mainwp-maintenance-extension' ),
			'trashcomment' => __( 'Delete trash comments', 'mainwp-maintenance-extension' ),
			'tags'         => __( 'Delete tags with 0 posts associated', 'mainwp-maintenance-extension' ),
			'categories'   => __( 'Delete categories with 0 posts associated', 'mainwp-maintenance-extension' ),
			'optimize'     => __( 'Optimize database tables', 'mainwp-maintenance-extension' ),
		);

	}

	function gen_maintenance_task( $task = null ) {
		$task_options = array();
		$revisions    = 5;
		if ( null != $task ) {
			$task_options  = explode( ',', $task->options );
				$revisions = $task->revisions;
		}

		?>
		<div class="ui hidden divider"></div>
		<div class="ui form">
			<h3 class="ui dividing header"><?php _e( 'Maintenance options', 'mainwp-maintenance-extension' ); ?></h3>
				<?php
				foreach ( $this->options as $key => $option ) :
					if ( null == $task || in_array( $key, $task_options ) ) {
						$att_checked = 'checked="checked"';
					} else {
						$att_checked = '';
					}
					?>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo $option; ?></label>
					  <div class="ten wide column" data-tooltip="<?php echo ( __( 'If enabled, the extension will ', 'mainwp-maintenance-extension' ) . strtolower( $option ) ); ?>" data-inverted="" data-position="top left">
							<div class="ui toggle checkbox">
							  <input type="checkbox" name="maintenance_options[]" value="<?php echo $key; ?>" <?php echo $att_checked; ?> id="mainte_option_<?php echo $key; ?>">
							  <label for="mainte_option_<?php echo $key; ?>"></label>
							</div>
						</div>
					</div>
					<?php if ( 'revisions' == $key ) : ?>
						<div class="ui grid field">
							<div class="one wide column"></div>
							<label class="five wide column middle aligned"><?php echo __( 'Except for the last', 'mainwp-maintenance-extension' ); ?></label>
							<div class="four wide column" data-tooltip="<?php esc_attr_e( 'Set the number of revisions to keep. Newest revisions will be kept. Set 0 to delete all.', 'mainwp-maintenance-extension' ); ?>" data-inverted="" data-position="top left">
								<input type="text" name="maintenance_options_revisions_count" id="maintenance_options_revisions_count" value="<?php echo $revisions; ?>">
							</div>
						</div>
					<?php endif; ?>
		 <?php endforeach; ?>
		</div>
		<?php
	}

	function gen_schedule_options( $task = null ) {
		$perform_number = array( 1, 2, 3, 4, 5, 10, 15 );
		$title          = ! empty( $task ) ? $task->title : '';

		$recurringSchedule = $recurringDate = $recurringMonth = $recurringDay = $recurringTime = '';
		$send_on_style     = $send_on_day_of_week_style = $send_on_day_of_mon_style = $send_on_month_style = $monthly_style = 'style="display:none"';

		$day_of_week = array(
			1 => __( 'Monday', 'mainwp-maintenance-extension' ),
			2 => __( 'Tuesday', 'mainwp-maintenance-extension' ),
			3 => __( 'Wednesday', 'mainwp-maintenance-extension' ),
			4 => __( 'Thursday', 'mainwp-maintenance-extension' ),
			5 => __( 'Friday', 'mainwp-maintenance-extension' ),
			6 => __( 'Saturday', 'mainwp-maintenance-extension' ),
			7 => __( 'Sunday', 'mainwp-maintenance-extension' ),
		);

		if ( ! empty( $task ) ) {
			$title             = $task->title;
			$recurringSchedule = $task->schedule;
			$recurringDay      = $task->recurring_day;
			$recurringTime     = $task->recurring_hour;
			$task_perform      = $task->perform;

			if ( $task_perform == 1 && ( $recurringSchedule == 'weekly' || $recurringSchedule == 'monthly' || $recurringSchedule == 'yearly' ) ) {
				$send_on_style = '';
				if ( $recurringSchedule == 'weekly' ) {
					$send_on_day_of_week_style = '';
				} elseif ( $recurringSchedule == 'monthly' ) {
					$send_on_day_of_mon_style = $monthly_style = '';
					$recurringDate            = $recurringDay;
				} elseif ( $recurringSchedule == 'yearly' ) {
					list( $recurringMonth, $recurringDate ) = explode( '-', $recurringDay );
					$send_on_day_of_mon_style               = $send_on_month_style = '';
				}
			}
		}

		?>
		<div class="ui hidden divider"></div>
		<div class="ui form">
			<h3 class="ui dividing header"><?php _e( 'Schedule options', 'mainwp-maintenance-extension' ); ?></h3>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php _e( 'Schedule title', 'mainwp-maintenance-extension' ); ?></label>
				<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Enter the schedule title.', 'mainwp-maintenance-extension' ); ?>" data-inverted="" data-position="top left">
					<input type="text" name="managemaintenance_title"  value="<?php echo esc_html( $title ); ?>" id="managemaintenance_title">
				</div>
			</div>

			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php _e( 'Schedule frequency', 'mainwp-maintenance-extension' ); ?></label>
				<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set how many times you want to run the task.', 'mainwp-maintenance-extension' ); ?>" data-inverted="" data-position="top left">
					<select id="maintenance_perform_number" class="ui compact dropdown">
						<?php foreach ( $perform_number as $val ) : ?>
							<option value="<?php echo $val; ?>" <?php echo ( ( null != $task && $val == $task->perform ) ? 'selected' : '' ); ?> ><?php echo $val; ?></option>
						<?php endforeach; ?>
					</select> <?php _e( ' time(s) per ', 'mainwp-maintenance-extension' ); ?>
					<select id="maintenance_perform_schedule" class="ui compact dropdown">
					<?php foreach ( $this->schedules as $key => $val ) : ?>
						<option value="<?php echo $key; ?>" <?php echo ( ( null != $task && $key == $task->schedule ) ? 'selected' : '' ); ?> ><?php echo $val; ?></option>
					<?php endforeach; ?>
					</select>
					<span  id="mainwp_maint_send_on_wrap" <?php echo $send_on_style; ?>>
					<?php _e( ' Run on ', 'mainwp-maintenance-extension' ); ?>
						<span id="scheduled_send_on_day_of_week_wrap" <?php echo $send_on_day_of_week_style; ?>>
							<select name='mainwp_maint_schedule_day' id="mainwp_maint_schedule_day" class="ui compact dropdown">
								<?php
								foreach ( $day_of_week as $value => $title ) {
									$_select = '';
									if ( $recurringDay == $value ) {
										$_select = 'selected';
									}
									echo '<option value="' . $value . '" ' . $_select . '>' . $title . '</option>';
								}
								?>
							</select>
						</span>

						<span id="scheduled_send_on_month_wrap" <?php echo $send_on_month_style; ?>>
							<select name='mainwp_maint_schedule_month' id="mainwp_maint_schedule_month" class="ui compact dropdown">
								<?php
								$months_name = array(
									1  => __( 'January', 'mainwp-maintenance-extension' ),
									2  => __( 'February', 'mainwp-maintenance-extension' ),
									3  => __( 'March', 'mainwp-maintenance-extension' ),
									4  => __( 'April', 'mainwp-maintenance-extension' ),
									5  => __( 'May', 'mainwp-maintenance-extension' ),
									6  => __( 'June', 'mainwp-maintenance-extension' ),
									7  => __( 'July', 'mainwp-maintenance-extension' ),
									8  => __( 'August', 'mainwp-maintenance-extension' ),
									9  => __( 'September', 'mainwp-maintenance-extension' ),
									10 => __( 'October', 'mainwp-maintenance-extension' ),
									11 => __( 'November', 'mainwp-maintenance-extension' ),
									12 => __( 'December', 'mainwp-maintenance-extension' ),
								);

								for ( $x = 1; $x <= 12; $x++ ) {
									$_select = '';
									if ( $recurringMonth == $x ) {
										$_select = 'selected';
									}
									echo '<option value="' . $x . '" ' . $_select . '>' . $months_name[ $x ] . '</option>';
								}
								?>
						  </select>
						 </span>

						 <span id="scheduled_send_on_day_of_month_wrap" <?php echo $send_on_day_of_mon_style; ?>>
							 <select name='mainwp_maint_schedule_day_of_month' id="mainwp_maint_schedule_day_of_month" class="ui compact dropdown">
								<?php
								$day_suffix = array(
									1 => 'st',
									2 => 'nd',
									3 => 'rd',
								);
								for ( $x = 1; $x <= 31; $x++ ) {
									$_select = '';

									if ( $recurringDate == $x ) {
										$_select = 'selected';
									}

									$remain = $x % 10;
									$day_sf = isset( $day_suffix[ $remain ] ) ? $day_suffix[ $remain ] : 'th';

									echo '<option value="' . $x . '" ' . $_select . '>' . $x . $day_sf . '</option>';
								}
								?>
							</select>
							<span class="show_if_monthly" <?php echo $monthly_style; ?>><?php _e( ' of the Month ', 'mainwp-maintenance-extension' ); ?></span>
						</span>

						<span id="scheduled_send_at_time">
							<span><?php _e( ' at ', 'mainwp' ); ?></span>
						  <select name='mainwp_maint_schedule_at_time' id="mainwp_maint_schedule_at_time" class="ui compact dropdown">
								<?php
								for ( $x = 1; $x <= 24; $x++ ) {
									$_select = '';
									if ( $recurringTime == $x ) {
											$_select = 'selected';
									}
									echo '<option value="' . $x . '" ' . $_select . '>' . $x . '</option>';
								}
								?>
							</select>
							<span><?php _e( ' o\'clock ', 'mainwp' ); ?></span>
						</span>
					</span>
				</div>
			</div>
		</div>
		<?php
	}

	function gen_404_alerts_options() {

		$settings     = get_option( 'mainwp_maintenance_settings' );
		$email        = '';
		$enable_alert = 0;
		$sites        = $groups = array();

		if ( is_array( $settings ) ) {
			$email        = isset( $settings['email'] ) ? $settings['email'] : '';
			$enable_alert = isset( $settings['enable_alert'] ) ? intval( $settings['enable_alert'] ) : 0;
		}

		?>
		<div class="ui hidden divider"></div>
		<div class="ui form">
			<h3 class="ui dividing header"><?php _e( '404 Email Alerts', 'mainwp-maintenance-extension' ); ?></h3>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php _e( 'Enable 404 email notifications', 'mainwp-maintenance-extension' ); ?></label>
				<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Enable if you want to receive email notification about 404 errors on your child sites. In some cases, this feature can cause large amount of emails to be sent from your child sites.', 'mainwp-maintenance-extension' ); ?>" data-inverted="" data-position="top left">
					<span class="ui toggle checkbox"><input type="checkbox" id="managemaintenance_enable_404_alert" name="managemaintenance_enable_404_alert" <?php echo ( 0 == $enable_alert ? '' : 'checked="checked"' ); ?> value="1" /></span>
				</div>
			</div>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php _e( 'Notification email address', 'mainwp-maintenance-extension' ); ?></label>
				<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Enter email address where you want to receive notifications.', 'mainwp-maintenance-extension' ); ?>" data-inverted="" data-position="top left">
					<input type="text" name="managemaintenance_404_alert_email" id="managemaintenance_404_alert_email" value="<?php echo $email; ?>">
				</div>
			</div>
		</div>
		<?php
	}

	function render() {

		$maintenanceTask = null;
		if ( isset( $_GET['id'] ) ) {
			if ( ! empty( $_GET['id'] ) ) {
				$maintenanceTaskId = $_GET['id'];
				$maintenanceTask   = Maintenance_Extension_DB::get_instance()->get_maintenance_task_by_id( $maintenanceTaskId );
			} else {
				$this->errors[] = __( 'Incorrect maintenance task ID.', 'mainwp-maintenance-extension' );
			}
		}

		if ( null == $maintenanceTask ) {
			$this->render_tabs();
		} else {
			$this->render_tabs( $maintenanceTask );
		}
	}

	/**
	 * Method render_sidebar_options()
	 *
	 * Render sidebar Options.
	 *
	 * @param bool $with_form Default: True. With form tags.
	 *
	 * @return void  Render sidebar Options html.
	 */
	public static function render_sidebar_options( $with_form = true ) {
		$sidebarPosition = get_user_option( 'mainwp_sidebarPosition' );
		if ( false === $sidebarPosition ) {
			$sidebarPosition = 1;
		}
		?>
		<div class="mainwp-sidebar-options ui fluid accordion mainwp-sidebar-accordion">
			<div class="title active"><i class="cog icon"></i> <?php esc_html_e( 'Sidebar Options', 'mainwp' ); ?></div>
			<div class="content active">
				<div class="ui mini form">
					<?php if ( $with_form ) { ?>
					<form method="post">
					<?php } ?>
					<div class="field">
						<label><?php esc_html_e( 'Sidebar position', 'mainwp' ); ?></label>
						<select name="mainwp_sidebar_position" id="mainwp_sidebar_position" class="ui dropdown" onchange="mainwp_sidebar_position_onchange(this)">
							<option value="1" <?php echo ( 1 == $sidebarPosition ? 'selected' : '' ); ?>><?php esc_html_e( 'Right', 'mainwp' ); ?></option>
							<option value="0" <?php echo ( 0 == $sidebarPosition ? 'selected' : '' ); ?>><?php esc_html_e( 'Left', 'mainwp' ); ?></option>
						</select>
					</div>
					<input type="hidden" name="wp_nonce" value="<?php echo wp_create_nonce( 'onchange_sidebarposition' ); ?>" />
					<?php if ( $with_form ) { ?>
					</form>
					<?php } ?>
				</div>
			</div>
		</div>
		<div class="ui fitted divider"></div>
		<?php
	}

	/**
	 * Method show_mainwp_message()
	 *
	 * Check whenther or not to show the MainWP Message.
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

	function render_tabs( $task = null ) {

		$current_tab = '';

		if ( isset( $_GET['tab'] ) ) {
			if ( $_GET['tab'] == 'new' ) {
				$current_tab = 'new';
			} elseif ( $_GET['tab'] == 'runnow' ) {
				$current_tab = 'runnow';
			} elseif ( $_GET['tab'] == 'schedules' ) {
				$current_tab = 'schedules';
			} elseif ( $_GET['tab'] == '404alerts' ) {
				$current_tab = '404alerts';
			}
		}

		$selected_websites = $selected_groups = array();

		if ( null != $task ) {
			if ( $task->sites != '' ) {
				$selected_websites = explode( ',', $task->sites );
			}
			if ( $task->groups != '' ) {
				$selected_groups = explode( ',', $task->groups );
			}
			$current_tab = 'edit';
		}

		$selected_websites = is_array( $selected_websites ) ? $selected_websites : array();
		$selected_groups   = is_array( $selected_groups ) ? $selected_groups : array();

		if ( $current_tab == '' ) {
			$current_tab = 'runnow'; // hide tab run now
		}

		$settings                   = get_option( 'mainwp_maintenance_settings' );
		$selected_websites_settings = isset( $settings['sites'] ) && is_array( $settings['sites'] ) ? $settings['sites'] : array();
		$selected_groups_settings   = isset( $settings['groups'] ) && is_array( $settings['groups'] ) ? $settings['groups'] : array();

		$email = isset( $settings['email'] ) && ! empty( $settings['email'] ) ? 1 : 0;

		?>

		<div class="ui labeled icon inverted menu mainwp-sub-submenu" id="mainwp-vulnerability-checker-menu">
			<a href="admin.php?page=Extensions-Mainwp-Maintenance-Extension&tab=runnow" class="item <?php echo ( $current_tab == 'runnow' ? 'active' : '' ); ?>"><i class="cogs icon"></i> <?php _e( 'Maintenance', 'mainwp-maintenance-extension' ); ?></a>
			<a href="admin.php?page=Extensions-Mainwp-Maintenance-Extension&tab=schedules" class="item <?php echo ( ( $current_tab == 'schedules' ) ? 'active' : '' ); ?>" ><i class="clock outline icon"></i> <?php _e( 'Schedules', 'mainwp-maintenance-extension' ); ?></a>
			<?php if ( $current_tab == 'new' ) : ?>
				<a href="admin.php?page=Extensions-Mainwp-Maintenance-Extension&tab=new" class="item <?php echo ( $current_tab == 'new' ? 'active' : '' ); ?>"><i class="plus icon"></i> <?php _e( 'Add Schedule', 'mainwp-maintenance-extension' ); ?></a>
			<?php endif; ?>
			<?php if ( $current_tab == 'edit' ) : ?>
				<a href="#" class="item active"><i class="edit outline icon"></i> <?php echo __( 'Edit', 'mainwp-maintenance-extension' ) . ' ' . $task->title; ?></a>
			<?php endif; ?>
			<?php if ( 1 == $email ) : ?>
				<a href="admin.php?page=Extensions-Mainwp-Maintenance-Extension&tab=404alerts" class="item <?php echo ( ( $current_tab == '404alerts' ) ? 'active' : '' ); ?>"><i class="envelope outline icon"></i> <?php _e( '404 Email Alerts', 'mainwp-maintenance-extension' ); ?></a>
			<?php endif; ?>
		</div>

		<!-- Maintenance -->
		<?php if ( $current_tab == 'runnow' || $current_tab == '' ) : ?>
		<div class="ui alt segment" id="mainwp-run-maintenance">
			<div class="mainwp-main-content">
				<div class="ui segment">
					<?php if ( self::show_mainwp_message( 'mainwp_maintanence_runnow' ) ) : ?>
						<div class="ui info message"><i class="close icon mainwp-notice-dismiss" notice-id="mainwp_maintanence_runnow"></i><?php esc_html_e( 'Clean and optimize your child sites databases. Before doing any maintenance jobs, we highly recommend creating your child sites databases backup. ', 'mainwp-maintenance-extension' ); echo sprintf( __( 'Please review the %shelp document%s for detailed information.', 'mainwp-maintenance-extension' ), '<a href="https://kb.mainwp.com/docs/perform-maintenance/" target="_blank">', '</a>' ); ?></div>
					<?php endif; ?>
					<div id="mainwp-message-zone" class="ui message" style="display:none"></div>
					<?php $this->gen_maintenance_task(); ?>
				</div>
			</div>
			<div class="mainwp-side-content mainwp-no-padding">
				<?php self::render_sidebar_options(); ?>
				<div class="mainwp-select-sites">
					<div class="ui header"><?php _e( 'Select Sites', 'mainwp-maintenance-extension' ); ?></div>
					<?php do_action( 'mainwp_select_sites_box' ); ?>
				</div>
				<div class="ui divider"></div>
				<div class="mainwp-search-submit">
					<input id="maintenance_run_btn" type="button" value="<?php esc_attr_e( 'Run Maintenance', 'mainwp-maintenance-extension' ); ?>" class="ui big fluid green button">
				</div>
			</div>
			<div class="ui clearing hidden divider"></div>
		</div>
		<div class="ui modal" id="mainwp-maintanence-process-modal">
			<div class="header"><?php esc_html_e( 'Maintenance', 'mainwp-maintenance-extension' ); ?></div>
			<div class="ui green progress mainwp-modal-progress">
				<div class="bar"><div class="progress"></div></div>
				<div class="label"></div>
			</div>
			<div class="scrolling content"></div>
			<div class="actions">
			<div class="ui close-reload-modal button"><?php _e( 'Close', 'mainwp-maintenance-extension' ); ?></div>
			</div>
		</div>
		<?php endif; ?>

		<!-- Add Schedule -->
		<?php if ( $current_tab == 'new' ) : ?>
		<div class="ui alt segment" id="mainwp-schedule-maintenance">
			<div class="mainwp-main-content">
				<div class="ui segment">
					<?php if ( self::show_mainwp_message( 'mainwp_maintanence_new' ) ) : ?>
						<div class="ui info message"><i class="close icon mainwp-notice-dismiss" notice-id="mainwp_maintanence_new"></i><?php esc_html_e( 'Schedule a new maintenance task.', 'mainwp-maintenance-extension' ); echo sprintf( __( 'Please review the %shelp document%s for detailed information. ', 'mainwp-maintenance-extension' ), '<a href="https://kb.mainwp.com/docs/schedule-maintenance/" target="_blank">', '</a>' ); ?></div>
					<?php endif; ?>
					<div id="mainwp-message-zone" class="ui message" style="display:none"></div>
					<?php $this->gen_schedule_options(); ?>
					<?php $this->gen_maintenance_task(); ?>
				</div>
			</div>
			<div class="mainwp-side-content mainwp-no-padding">
				<?php self::render_sidebar_options(); ?>
				<div class="mainwp-select-sites">
					<div class="ui header"><?php _e( 'Select Sites', 'mainwp-maintenance-extension' ); ?></div>
					<?php do_action( 'mainwp_select_sites_box' ); ?>
				</div>
				<div class="ui divider"></div>
				<div class="mainwp-search-submit">
					<input id="managemaintenance_add" type="button" value="<?php esc_attr_e( 'Schedule Maintenance', 'mainwp-maintenance-extension' ); ?>" class="ui big fluid green button">
				</div>
			</div>
			<div class="ui clearing hidden divider"></div>
		</div>
		<?php endif; ?>

		<!-- Schedules -->
		<?php if ( $current_tab == 'schedules' ) : ?>
		<div  id="mainwp-maintenance-schedules">
			<div class="ui mini form mainwp-actions-bar">
				<div class="ui grid">
					<div class="ui two column row">
						<div class="column">
							<a href="admin.php?page=Extensions-Mainwp-Maintenance-Extension&tab=new" class="ui green mini button" data-tooltip="<?php esc_attr_e( 'Click to open a new tab to createa new maintenance schedule.', 'mainwp-maintenance-extension' ); ?>" data-position="right center" data-inverted=""><?php esc_html_e( 'Create New Schedule', 'mainwp-maintenance-extension' ); ?></a>
						</div>
						<div class="right aligned column"></div>
					</div>
				</div>
			</div>
			<div class="ui segment">
				<?php if ( self::show_mainwp_message( 'mainwp_maintanence_schedules' ) ) : ?>
					<div class="ui info message"><i class="close icon mainwp-notice-dismiss" notice-id="mainwp_maintanence_schedules"></i><?php esc_html_e( 'Manage scheduled maintenance tasks. ', 'mainwp-maintenance-extension' ); ?></div>
				<?php endif; ?>
				<div id="mainwp-message-zone" class="ui message" style="display:none"></div>
				<?php echo $this->gen_schedules_table(); ?>
			</div>
		</div>
		<div class="ui modal" id="mainwp-maintanence-process-modal">
			<div class="header"><?php esc_html_e( 'Maintenance', 'mainwp-maintenance-extension' ); ?></div>
			<div class="ui green progress mainwp-modal-progress">
				<div class="bar"><div class="progress"></div></div>
				<div class="label"></div>
			</div>
			<div class="scrolling content"></div>
			<div class="actions">
			<div class="ui close-reload-modal button"><?php _e( 'Close', 'mainwp-maintenance-extension' ); ?></div>
			</div>
		</div>
		<?php endif; ?>

		<!-- Edit -->
		<?php if ( $current_tab == 'edit' ) : ?>
		<div class="ui alt segment" id="mainwp-edit-maintenance-schedule">
			<div class="mainwp-main-content">
				<div class="ui segment" id="maintenance_edit_daily_box">
					<?php if ( self::show_mainwp_message( 'mainwp_maintanence_edit' ) ) : ?>
						<div class="ui info message"><i class="close icon mainwp-notice-dismiss" notice-id="mainwp_maintanence_edit"></i><?php esc_html_e( 'Edit scheduled maintanence task. ', 'mainwp-maintenance-extension' ); echo sprintf( __( 'Please review the %shelp document%s for detailed information.', 'mainwp-maintenance-extension' ), '<a href="https://kb.mainwp.com/docs/edit-maintenance-schedule/" target="_blank">', '</a>' ); ?></div>
					<?php endif; ?>
					<div id="mainwp-message-zone" class="ui message" style="display:none"></div>
					<?php $this->gen_schedule_options( $task ); ?>
					<?php $this->gen_maintenance_task( $task ); ?>
				</div>
			</div>
			<div class="mainwp-side-content mainwp-no-padding">
				<?php self::render_sidebar_options(); ?>
				<div class="mainwp-select-sites">
					<div class="ui header"><?php _e( 'Select Sites', 'mainwp-maintenance-extension' ); ?></div>
					<?php do_action( 'mainwp_select_sites_box', '', 'checkbox', true, true, '', '', $selected_websites, $selected_groups ); ?>
				</div>
				<div class="ui divider"></div>
				<div class="mainwp-search-submit">
					<input type="submit" id="managemaintenance_update" name="submit" value="<?php _e( 'Save Changes', 'mainwp-maintenance-extension' ); ?>" class="ui big fluid green button">
					<input type="hidden" id="edit_managemaintenance_id" name="edit_managemaintenance_id" value="<?php echo $task->id; ?>">
				</div>
			</div>
			<div class="ui clearing hidden divider"></div>
		</div>
		<?php endif; ?>

		<!-- 404 Alerts -->
		<?php if ( $current_tab == '404alerts' ) : ?>
		<div class="ui alt segment" id="mainwp-404-alerts">
			<div class="mainwp-main-content">
				<div class="ui segment">
					<?php if ( self::show_mainwp_message( 'mainwp_maintanence_404alerts' ) ) : ?>
						<div class="ui info message"><i class="close icon mainwp-notice-dismiss" notice-id="mainwp_maintanence_404alerts"></i><?php esc_html_e( 'Monitor your child sites for 404 errors. It will email you as soon as somebody hits an un-existing page on your child site. ', 'mainwp-maintenance-extension' ); echo sprintf( __( 'Please review the %shelp document%s for detailed information.', 'mainwp-maintenance-extension' ), '<a href="https://kb.mainwp.com/docs/enable-404-email-alerts/" target="_blank">', '</a>' ); ?></div>
					<?php endif; ?>
					<div id="mainwp-message-zone" class="ui message" style="display:none"></div>
					<?php $this->gen_404_alerts_options(); ?>
				</div>
			</div>
			<div class="mainwp-side-content mainwp-no-padding">
				<?php self::render_sidebar_options(); ?>
				<div class="mainwp-select-sites">
					<div class="ui header"><?php _e( 'Select Sites', 'mainwp-maintenance-extension' ); ?></div>
					<?php do_action( 'mainwp_select_sites_box', '', 'checkbox', true, true, '', '', $selected_websites_settings, $selected_groups_settings ); ?>
				</div>
				<div class="ui divider"></div>
				<div class="mainwp-search-submit">
					<input id="maintenance_save_settings" type="button" value="<?php _e( 'Save Settings', 'mainwp-maintenance-extension' ); ?>" class="ui big fluid green button">
				</div>
			</div>
			<div class="ui clearing hidden divider"></div>
		</div>
		<div class="ui modal" id="mainwp-maintanence-process-modal">
			<div class="header"><?php esc_html_e( '404 Alerts', 'mainwp-maintenance-extension' ); ?></div>
			<div class="ui green progress mainwp-modal-progress">
				<div class="bar"><div class="progress"></div></div>
				<div class="label"></div>
			</div>
			<div class="scrolling content"></div>
			<div class="actions">
			<div class="ui close-reload-modal button"><?php _e( 'Close', 'mainwp-maintenance-extension' ); ?></div>
			</div>
		</div>
		<?php endif; ?>
		<?php
	}

	function gen_schedules_table() {
		?>
	  <table id="mainwp-maintenance-schedules-table" class="ui tablet stackable single line table">
			<thead>
			  <tr>
				  <th><?php _e( 'Title', 'mainwp-maintenance-extension' ); ?></th>
				  <th><?php _e( 'Schedule', 'mainwp-maintenance-extension' ); ?></th>
				  <th><?php _e( 'Last Run', 'mainwp-maintenance-extension' ); ?></th>
					<th><?php _e( 'Next Run', 'mainwp-maintenance-extension' ); ?></th>
				  <th class="right aligned collapsing no-sort"></th>
			  </tr>
			</thead>
			<tbody>
				<?php $this->get_table_content(); ?>
			</tbody>
		</table>
		<script type="text/javascript">
		jQuery( document ).ready( function () {
			jQuery( '#mainwp-maintenance-schedules-table' ).DataTable( {
				"columnDefs": [ { "targets": 'no-sort', "orderable": false } ]
			} );
		} );
		</script>
		<?php
	}

	public function get_table_content() {
		$tasks = Maintenance_Extension_DB::get_instance()->get_maintenance_tasks();

		if ( ! self::validate_maintenance_tasks( $tasks ) ) {
			$tasks = Maintenance_Extension_DB::get_instance()->get_maintenance_tasks();
		}

		if ( ! empty( $tasks ) ) {

			foreach ( $tasks as $task ) {

				$sites  = ( $task->sites == '' ? array() : explode( ',', $task->sites ) );
				$groups = ( $task->groups == '' ? array() : explode( ',', $task->groups ) );

				?>
				<tr id="task-<?php echo $task->id; ?>" taskid="<?php echo $task->id; ?>" taskName="<?php echo $task->title; ?>">
					<td scope="row" class="">
						<span data-tooltip="<?php esc_attr_e( 'Click the schedule task to edit it.', 'mainwp-maintenance-extension' ); ?>" data-position="right center" data-inverted=""><a href="admin.php?page=Extensions-Mainwp-Maintenance-Extension&id=<?php echo $task->id; ?>"><?php echo $task->title; ?></a></span>
					</td>
				  <td><span data-tooltip="<?php esc_attr_e( 'Schedule frequency.', 'mainwp-maintenance-extension' ); ?>" data-position="left center" data-inverted=""><?php echo $task->perform . ' / ' . $this->schedules[ $task->schedule ]; ?></span></td>
					<td><span data-tooltip="<?php esc_attr_e( 'Schedule last run.', 'mainwp-maintenance-extension' ); ?>" data-position="left center" data-inverted=""><?php echo ( $task->schedule_lastsend == 0 ? '-' : self::format_TimeStamp( self::get_TimeStamp( $task->schedule_lastsend ) ) ); ?></span></td>
					<td><span data-tooltip="<?php esc_attr_e( 'Schedule next run.', 'mainwp-maintenance-extension' ); ?>" data-position="left center" data-inverted=""><?php echo ( $task->schedule_nextsend == 0 ? '-' : self::format_TimeStamp( self::get_TimeStamp( $task->schedule_nextsend ) ) ); ?></span></td>
					<td>
						<a href="#" class="ui green mini button maintenance_run_now" data-tooltip="<?php esc_attr_e( 'Click to execute the task.', 'mainwp-maintenance-extension' ); ?>" data-position="bottom right" data-inverted="" optimize-db="1"><?php _e( 'Run Now', 'mainwp-maintenance-extension' ); ?></a>
						<a href="admin.php?page=Extensions-Mainwp-Maintenance-Extension&id=<?php echo $task->id; ?>" class="ui green basic mini button" data-tooltip="<?php esc_attr_e( 'Click to edit this task.', 'mainwp-maintenance-extension' ); ?>" data-position="bottom right" data-inverted=""><?php _e( 'Edit', 'mainwp-maintenance-extension' ); ?></a>
						<a href="#" class="ui basic red mini button" onClick="return managemaintenance_remove( <?php echo $task->id; ?> )" data-tooltip="<?php esc_attr_e( 'Click to delete this task.', 'mainwp-maintenance-extension' ); ?>" data-position="bottom right" data-inverted=""><?php _e( 'Delete', 'mainwp-maintenance-extension' ); ?></a>
					</td>
		  	</tr>
				<?php
			}
		} else {
			?>
		<tr><td colspan="5"><?php _e( 'No scheduled tasks.', 'mainwp-maintenance-extension' ); ?></td></tr>
			<?php
		}
	}

	public static function validate_maintenance_tasks( $pMaintenanceTasks ) {

		if ( ! is_array( $pMaintenanceTasks ) ) {
			return true; }
			global $mainWPMaintenance_Extension_Activator;
		$nothingChanged = true;
		foreach ( $pMaintenanceTasks as $maintenanceTask ) {
			if ( $maintenanceTask->groups == '' ) {
				// Check if sites exist
				$newSiteIds        = '';
				$siteIds           = ( $maintenanceTask->sites == '' ? array() : explode( ',', $maintenanceTask->sites ) );
					$dbwebsites    = apply_filters( 'mainwp_getdbsites', $mainWPMaintenance_Extension_Activator->get_child_file(), $mainWPMaintenance_Extension_Activator->get_child_key(), $siteIds, array() );
					$exist_siteids = array();
				foreach ( $dbwebsites as $website ) {
					$exist_siteids[] = $website->id;
				}
				foreach ( $siteIds as $site_id ) {
					if ( ! in_array( $site_id, $exist_siteids ) ) {
						$nothingChanged = false;
					} else {
						$newSiteIds .= ',' . $site_id;
					}
				}
				if ( ! $nothingChanged ) {
					$newSiteIds = trim( $newSiteIds, ',' );
					Maintenance_Extension_DB::get_instance()->update_maintenance_task_values( $maintenanceTask->id, array( 'sites' => $newSiteIds ) );
				}
			} else {
				// Check if groups exist
				$newGroupIds    = '';
				$groupIds       = explode( ',', $maintenanceTask->groups );
				$groups         = apply_filters( 'mainwp_getgroups', $mainWPMaintenance_Extension_Activator->get_child_file(), $mainWPMaintenance_Extension_Activator->get_child_key(), null );
				$exist_groupids = array();
				foreach ( $groups as $group ) {
					$exist_groupids[] = $group['id'];
				}
				foreach ( $groupIds as $groupId ) {
					if ( ! in_array( $groupId, $exist_groupids ) ) {
						$nothingChanged = false;
					} else {
						$newGroupIds .= ',' . $groupId;
					}
				}
				if ( ! $nothingChanged ) {
					$newGroupIds = trim( $newGroupIds, ',' );
					Maintenance_Extension_DB::get_instance()->update_maintenance_task_values( $maintenanceTask->id, array( 'groups' => $newGroupIds ) );
				}
			}
		}

		return $nothingChanged;
	}

	public static function save_maintenance() {
		$task_id = isset( $_POST['taskid'] ) ? $_POST['taskid'] : false;
		$title   = $_POST['title'];

		if ( empty( $title ) ) {
			die( __( 'Please enter a title for the maintenance task.', 'mainwp-maintenance-extension' ) );
		}

		$schedule       = $_POST['schedule'];
		$options        = implode( ',', $_POST['options'] );
		$perform        = $_POST['perform'];
		$revisions      = $_POST['revisions'];
		$recurring_day  = isset( $_POST['recurring_day'] ) ? $_POST['recurring_day'] : '';
		$recurring_hour = isset( $_POST['recurring_hour'] ) ? $_POST['recurring_hour'] : '';

		if ( $recurring_hour > 24 || $recurring_hour < 0 ) {
			$recurring_hour = 0;
		}

		if ( $perform != 1 || ( $perform == 1 && $schedule == 'daily' ) ) {
			$recurring_day = '';
		}

		$sites  = '';
		$groups = '';

		if ( isset( $_POST['sites'] ) ) {
			foreach ( $_POST['sites'] as $site ) {
				if ( '' != $sites ) {
					$sites .= ','; }
					$sites .= $site;
			}
		}

		if ( isset( $_POST['groups'] ) ) {
			foreach ( $_POST['groups'] as $group ) {
				if ( '' != $groups ) {
					$groups .= ',';
				}
				$groups .= $group;
			}
		}

		$update = array(
			'title'          => $title,
			'schedule'       => $schedule,
			'options'        => $options,
			'sites'          => $sites,
			'groups'         => $groups,
			'perform'        => $perform,
			'revisions'      => $revisions,
			'recurring_day'  => $recurring_day,
			'recurring_hour' => $recurring_hour,
		);

		$recal = false;

		if ( $task_id ) {
			$current_sched = Maintenance_Extension_DB::get_instance()->get_maintenance_task_by_id( $task_id );
			if ( $current_sched ) {
				if ( $current_sched->perform != $perform || $current_sched->schedule != $schedule || $current_sched->recurring_day != $recurring_day || $current_sched->recurring_hour != $recurring_hour ) {
					$recal = true;
				}
			} else {
				$recal = true;
			}
		} else {
			$recal = true;
		}

		if ( $recal ) {
			$cal_recurring = Maintenance_Extension::calc_scheduled_date( $schedule, $recurring_day, $recurring_hour, $perform );
			if ( is_array( $cal_recurring ) && isset( $cal_recurring['nextsend'] ) ) {
				$update['schedule_nextsend'] = $cal_recurring['nextsend'];
			}
		}

		$task = Maintenance_Extension_DB::get_instance()->update_task( $task_id, $update );

		if ( $task_id && 0 == $task ) {
			die( 'NOAFFECTED' );
		} elseif ( ! $task ) {
			die( __( 'Undefined error occurred. Please, try again.', 'mainwp-maintenance-extension' ) );
		}
	}

	public static function save_settings() {

		$email  = trim( $_POST['email'] );
		$enable = $_POST['enable'];

		if ( ! empty( $email ) && ! preg_match( '/^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/is', $email ) ) {
			die( __( 'Please enter a valid email address.', 'mainwp-maintenance-extension' ) );
		}

		$sites  = isset( $_POST['sites'] ) ? $_POST['sites'] : array();
		$groups = isset( $_POST['groups'] ) ? $_POST['groups'] : array();

		$settings = array(
			'email'        => $email,
			'enable_alert' => $enable,
			'sites'        => $sites,
			'groups'       => $groups,
		);

		update_option( 'mainwp_maintenance_settings', $settings );

		die( 'SUCCESS' );
	}

	public static function load_sites_to_save_settings() {
		global $mainWPMaintenance_Extension_Activator;

		$settings = get_option( 'mainwp_maintenance_settings' );

		$email        = '';
		$enable_alert = 0;
		$sites        = $groups = $dbwebsites_selected = $selected_sites = array();

		if ( is_array( $settings ) ) {
			$email        = isset( $settings['email'] ) ? $settings['email'] : '';
			$enable_alert = isset( $settings['enable_alert'] ) ? intval( $settings['enable_alert'] ) : 0;
			$sites        = isset( $settings['sites'] ) && is_array( $settings['sites'] ) ? $settings['sites'] : array();
			$groups       = isset( $settings['groups'] ) && $settings['groups'] ? $settings['groups'] : array();

			$dbwebsites_selected = apply_filters( 'mainwp_getdbsites', $mainWPMaintenance_Extension_Activator->get_child_file(), $mainWPMaintenance_Extension_Activator->get_child_key(), $sites, $groups );

			if ( is_array( $dbwebsites_selected ) ) {
				foreach ( $dbwebsites_selected as $website ) {
					$selected_sites[] = $website->id;
				}
			}
		}

		?>
	<input type="hidden" id="mainwp_maintenance_settings_email" value="<?php echo $email; ?>" />
	<input type="hidden" id="mainwp_maintenance_settings_enable_alert" value="<?php echo $enable_alert; ?>" />
		<?php

		$dbwebsites = apply_filters( 'mainwp_getsites', $mainWPMaintenance_Extension_Activator->get_child_file(), $mainWPMaintenance_Extension_Activator->get_child_key(), null );

		if ( is_array( $dbwebsites ) && count( $dbwebsites ) > 0 ) {
			echo '<div class="ui relaxed divided list">';
			foreach ( $dbwebsites as $website ) {
				$_action = ' action="clear_settings" ';

				if ( in_array( $website['id'], $selected_sites ) ) {
					$_action = ' action="save_settings" ';
				}
				?>
				<div class="item">
					<div class="ui grid">
						<div class="two column row">
							<div class="column"><a href="<?php echo 'admin.php?page=managesites&dashboard='. $website['id']; ?>"><?php echo stripslashes( $website['name'] ); ?></a></div>
							<div class="right aligned column mainwpMaintenanceSitesItem" status="queue" siteid="<?php echo $website['id']; ?>" <?php echo $_action; ?>><span class="status"><i class="clock outline icon"></i></span></div>
						</div>
					</div>
				</div>
				<?php
			}
			return true;
		} else {
			return 'FAIL';
		}
	}

	public static function perform_save_settings() {

		$siteid = $_POST['siteId'];

		if ( empty( $siteid ) ) {
			die( json_encode( 'FAIL' ) );
		}

		global $mainWPMaintenance_Extension_Activator;

		$post_data = array(
			'action'       => $_POST['do_action'],
			'email'        => $_POST['email'],
			'enable_alert' => intval( $_POST['enable'] ),
		);

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPMaintenance_Extension_Activator->get_child_file(), $mainWPMaintenance_Extension_Activator->get_child_key(), $siteid, 'maintenance_site', $post_data );

		die( json_encode( $information ) );
	}

	public static function remove_maintenance() {

		if ( isset( $_POST['id'] ) && ! empty( $_POST['id'] ) ) {
			$task = Maintenance_Extension_DB::get_instance()->get_maintenance_task_by_id( $_POST['id'] );
			// Remove from DB
			Maintenance_Extension_DB::get_instance()->remove_maintenance_task( $task->id );
			die( 'SUCCESS' );
		}
		die( 'NOTASK' );
	}

	public static function render_progress_content( $taskid = null ) {
		$dbwebsites     = array();
		$task_options   = '';
		$task_revisions = 0;
		global $mainWPMaintenance_Extension_Activator;
		$dbwebsites = array();

		if ( null != $taskid ) {
			$task = Maintenance_Extension_DB::get_instance()->get_maintenance_task_by_id( $taskid );
			if ( $task ) {
				Maintenance_Extension_DB::get_instance()->update_last_manually_run_time( $taskid );
				$selected_websites = ( $task->sites != '' ) ? explode( ',', $task->sites ) : array();
				$selected_groups   = ( $task->groups != '' ) ? explode( ',', $task->groups ) : array();
				$dbwebsites        = apply_filters( 'mainwp_getdbsites', $mainWPMaintenance_Extension_Activator->get_child_file(), $mainWPMaintenance_Extension_Activator->get_child_key(), $selected_websites, $selected_groups );
				$task_options      = $task->options;
				$task_revisions    = $task->revisions;
			}
		} else {
			$selected_websites = isset( $_POST['sites'] ) ? $_POST['sites'] : array();
			$selected_groups   = isset( $_POST['groups'] ) ? $_POST['groups'] : array();
			$dbwebsites        = apply_filters( 'mainwp_getdbsites', $mainWPMaintenance_Extension_Activator->get_child_file(), $mainWPMaintenance_Extension_Activator->get_child_key(), $selected_websites, $selected_groups );
		}

		if ( count( $dbwebsites ) > 0 ) {
			?>
			<div class="ui divided relaxed list">
				<?php
				$str_ids = '';
				foreach ( $dbwebsites as $website ) {
					?>
					<div class="item">
						<div class="ui grid">
							<div class="two column row">
								<div class="column"><a href="<?php echo 'admin.php?page=managesites&dashboard='. $website->id; ?>"><?php echo stripslashes( $website->name ); ?></a></div>
								<div class="right aligned column maintenance-status-wp" siteid="<?php echo $website->id; ?>"><i class="clock outline icon"></i></div>
							</div>
						</div>
						<?php $str_ids .= '<input type="hidden" name="maintenance_wp_ids[]" value="' . $website->id . '" />'; ?>
					</div>
					<?php
				}
				$str_ids .= '<span id="mainwp_mainten_trigger_data" options="' . $task_options . '" revisions="' . $task_revisions . '"></span>';
				?>
			</div>
			<?php
			echo $str_ids;
			die();
		} else {
			die( 'FAIL' );
		}
	}

	public static function maintenance_site( $siteid, $options, $revisions ) {

		if ( empty( $siteid ) ) {
			return false;
		}

		if ( empty( $siteid ) ) {
			return false;
		}

		if ( ! is_array( $options ) ) {
			$options = explode( ',', $options );
		}

		if ( count( $options ) <= 0 ) {
			return false;
		}

		$post_data = array(
			'options'   => $options,
			'revisions' => $revisions,
		);

		$information = self::perform_maintenance( $siteid, $post_data );

		if ( ! isset( $information['status'] ) || ( 'SUCCESS' != $information['status'] ) ) {
			return false;
		}
		return true;
	}

	public static function maintenance_run_site() {

		$options     = $_POST['options'];
		$websiteId   = $_POST['wp_id'];
		$revisions   = $_POST['revisions'];
		$information = array( 'status' => 'FAIL' );

		if ( empty( $websiteId ) ) {
			die( json_encode( $information ) );
		}

		if ( empty( $options ) ) {
			die( json_encode( $information ) );
		}

		if ( ! is_array( $options ) ) {
			$options = explode( ',', $options );
		}

		$post_data = array(
			'options'   => $options,
			'revisions' => $revisions,
		);

		$information = self::perform_maintenance( $websiteId, $post_data );

		die( json_encode( $information ) );
	}

	public static function perform_maintenance( $websiteId, $post_data ) {
		global $mainWPMaintenance_Extension_Activator;
		return apply_filters( 'mainwp_fetchurlauthed', $mainWPMaintenance_Extension_Activator->get_child_file(), $mainWPMaintenance_Extension_Activator->get_child_key(), $websiteId, 'maintenance_site', $post_data );
	}

	public static function format_TimeStamp( $timestamp ) {
		return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
	}

	public static function get_TimeStamp( $timestamp ) {
		$gmtOffset = get_option( 'gmt_offset' );

		return ( $gmtOffset ? ( $gmtOffset * HOUR_IN_SECONDS ) + $timestamp : $timestamp );
	}

}
