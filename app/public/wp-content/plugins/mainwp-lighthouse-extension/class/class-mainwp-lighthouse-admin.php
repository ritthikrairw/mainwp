<?php
/**
 * =======================================
 * MainWP Lighthouse Admin
 * =======================================
 *
 * @copyright Matt Keys <https://profiles.wordpress.org/mattkeys>
 */
namespace MainWP\Extensions\Lighthouse;

class MainWP_Lighthouse_Admin {

	public $version           = '1.2';
	public static $sort_field = '';

	var $strategy;

	/**
	 * Static variable to hold the single instance of the class.
	 *
	 * @static
	 *
	 * @var mixed Default null
	 */
	static $instance = null;

	/**
	 * Get Instance
	 *
	 * Creates public static instance.
	 *
	 * @static
	 *
	 * @return MainWP_Lighthouse_Admin
	 */
	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Method get_class_name()
	 *
	 * Get Class Name.
	 *
	 * @return string __CLASS__
	 */
	public static function get_class_name() {
		return __CLASS__;
	}


	/**
	 * Constructor
	 *
	 * Runs each time the class is called.
	 */
	public function __construct() {

		$this->strategy = ( isset( $_GET['strategy'] ) ) ? sanitize_text_field( $_GET['strategy'] ) : MainWP_Lighthouse_Utility::get_instance()->get_option( 'view_preference' );

		add_action( 'init', array( &$this, 'init' ) );
		add_action( 'init', array( &$this, 'localization' ) );
	}

	/**
	 * Initiate Hooks
	 *
	 * Initiates hooks for the Lighthouse extension.
	 */
	public function init() {
		add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 2 );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_filter( 'mainwp_lighthouse_set_time_limit_disabled', array( $this, 'check_set_time_limit' ), 10, 1 );
		add_action( 'admin_init', array( $this, 'do_lighthouse_actions' ), 9 );
		add_action( 'mainwp_cron_jobs_list', array( $this, 'cron_job_info' ) );
		add_action( 'mainwp_help_sidebar_content', array( $this, 'mainwp_help_content' ) );
		add_filter( 'mainwp_header_actions_right', array( $this, 'screen_options' ), 10, 2 );
		add_action( 'mainwp_manage_sites_table_columns_defs', array( &$this, 'hook_manage_sites_table_columns_defs' ) );
		add_filter( 'mainwp_sitestable_sortable_columns', array( &$this, 'hook_mainwp_sitestable_sortable_columns' ), 10, 2 );

		MainWP_Lighthouse_Core::get_instance()->init();
		MainWP_Lighthouse_DB::get_instance()->init();
		MainWP_Lighthouse_Hooks::get_instance()->init();

		$this->handle_sites_screen_settings();
	}

	/**
	 * Hook hook_manage_sites_table_columns_defs.
	 *
	 * Hook manage sites table columns defs.
	 */
	public function hook_manage_sites_table_columns_defs() {
		?>
			{ 
				"targets": [ 'manage-lighthouse_desktop_score-column', 'manage-lighthouse_mobile_score-column' ],
				"createdCell":  function (td, cellData, rowData, row, col) {
					$(td).attr('data-sort', $(td).find('a').text());
				}
			},
		<?php
	}

	/**
	 * Hook hook_mainwp_sitestable_sortable_columns.
	 *
	 * Hook sites table sortable columns.
	 *
	 * @return mixed $sortable_columns.
	 */
	public function hook_mainwp_sitestable_sortable_columns( $sortable_columns ) {
		$sortable_columns['lighthouse_desktop_score'] = array( 'lighthouse_desktop_score', false );
		$sortable_columns['lighthouse_mobile_score']  = array( 'lighthouse_mobile_score', false );
		return $sortable_columns;
	}

	/**
	 * Localization
	 *
	 * Sets the localization domain.
	 */
	public function localization() {
		load_plugin_textdomain( 'mainwp-lighthouse-extension', false, MAINWP_LIGHTHOUSE_PLUGIN_DIR . '/languages/' );
	}

	/**
	 * Sites Page Check
	 *
	 * Checks if the current page is individual site Lighthouse page.
	 *
	 * @return bool True if correct, false if not.
	 */
	public static function is_managesites_page() {
		if ( isset( $_GET['page'] ) && ( 'ManageSitesLighthouse' == $_GET['page'] ) ) {
			return true;
		}
		  return false;
	}


	/**
	 * Lighthouse Alert
	 *
	 * @param bool $debug Debugging or not.
	 *
	 * Sends emaail alerts when Lighthouse score is under the treshold.
	 */
	public static function cron_lighthouse_alert( $debug = false ) {
		$email_settings = apply_filters( 'mainwp_notification_get_settings', array(), 'lighthouse_noti_email' );
		if ( empty( $email_settings ) || ! empty( $email_settings['disable'] ) ) {
			return;
		}
		MainWP_Lighthouse_Utility::log_debug( 'run to start notification' );
		update_option( 'mainwp_lighthouse_cron_lastalert', time() );
		self::start_notification_lighthouse( $email_settings, $debug );
	}

	/**
	 * Start notification lighthouse
	 *
	 * @param array $email_settings email settings.
	 * @param bool  $debug Debugging or not.
	 *
	 *  Sends email alerts when Lighthouse score is under the treshold.
	 */
	public static function start_notification_lighthouse( $email_settings, $debug = false ) {

		$alert_score = MainWP_Lighthouse_Utility::get_instance()->get_option( 'score_noti' );
		$interval    = MainWP_Lighthouse_Utility::get_instance()->get_option( 'recheck_interval' );

		$lighthouses = MainWP_Lighthouse_DB::get_instance()->get_lighthouse_by( 'all' );
		$loc_time    = MainWP_Lighthouse_Utility::get_timestamp();

		if ( is_array( $lighthouses ) ) {

			foreach ( $lighthouses as $value ) {
				$child_alert_score = $alert_score;

				if ( $value->override ) {
					$settings          = json_decode( $value->settings, true );
					$settings          = is_array( $settings ) ? $settings : array();
					$child_alert_score = isset( $settings['score_noti'] ) ? $settings['score_noti'] : 0;
					$interval          = isset( $settings['recheck_interval'] ) ? $settings['recheck_interval'] : $interval;
					if ( $loc_time > $value->last_alert + $interval ) {  // individual interval.
						continue;
					}
				} else {
					if ( $loc_time > $value->last_alert + $interval ) { // general interval.
						continue;
					}
				}

				if ( empty( $child_alert_score ) ) {
					continue;
				}

				if ( $value->override ) {
					$type = $value->strategy;
				} else {
					$type = MainWP_Lighthouse_Utility::get_instance()->get_option( 'strategy' );
				}

				$check_score          = 0;
				$check_accessibility  = 0;
				$check_best_practices = 0;
				$check_seo            = 0;

				if ( 'desktop' == $type || 'both' == $type ) {
					$check_score          = $value->desktop_score;
					$check_accessibility  = $value->desktop_accessibility_score;
					$check_best_practices = $value->desktop_best_practices_score;
					$check_seo            = $value->desktop_seo_score;
				} elseif ( 'mobile' == $type ) {
					$check_score          = $value->mobile_score;
					$check_accessibility  = $value->mobile_accessibility_score;
					$check_best_practices = $value->mobile_best_practices_score;
					$check_seo            = $value->mobile_seo_score;
				} else {
					continue;
				}

				if ( 'both' == $type ) {
					if ( $check_score > $value->mobile_score ) {
						$check_score = $value->mobile_score;
					}
					if ( $check_accessibility > $value->mobile_accessibility_score ) {
						$check_accessibility = $value->mobile_accessibility_score;
					}
					if ( $check_best_practices > $value->mobile_best_practices_score ) {
						$check_best_practices = $value->mobile_best_practices_score;
					}
					if ( $check_seo > $value->desktop_seo_score ) {
						$check_seo = $value->desktop_seo_score;
					}
				}

				$last_alert = $value->last_alert;
				if ( $check_score <= $child_alert_score || $check_accessibility <= $child_alert_score || $check_best_practices <= $child_alert_score || $check_seo <= $child_alert_score ) {
					if ( $debug || $last_alert + 24 * 3600 < $loc_time ) {
						$sent = self::send_alert_mail( $value, $email_settings, $type );
						if ( $sent && ! empty( $value->site_id ) ) {
							$update = array(
								'site_id'    => $value->site_id,
								'last_alert' => $loc_time,
							);
							MainWP_Lighthouse_DB::get_instance()->update_lighthouse( $update );
						}
					}
				}
			}
		}
	}

	/**
	 * Cron Job Info
	 *
	 * Hooks the Lighthouse cron job info to the Cron Schedules table.
	 */
	public function cron_job_info() {
		$startNextRun    = MainWP_Lighthouse_Utility::format_TimeStamp( MainWP_Lighthouse_Utility::get_TimeStamp( wp_next_scheduled( 'mainwp_lighthouse_action_cron_start' ) ) );
		$continueNextRun = MainWP_Lighthouse_Utility::format_TimeStamp( MainWP_Lighthouse_Utility::get_TimeStamp( wp_next_scheduled( 'mainwp_lighthouse_action_cron_continue' ) ) );
		$startLastRun    = MainWP_Lighthouse_Utility::format_TimeStamp( MainWP_Lighthouse_Utility::get_TimeStamp( get_option( 'mainwp_lighthouse_start_cron_lastcheck' ) ) );
		$continueLastRun = MainWP_Lighthouse_Utility::format_TimeStamp( MainWP_Lighthouse_Utility::get_TimeStamp( get_option( 'mainwp_lighthouse_continue_cron_lastcheck' ) ) );

		$notificationsLastRun = MainWP_Lighthouse_Utility::format_TimeStamp( MainWP_Lighthouse_Utility::get_TimeStamp( get_option( 'mainwp_lighthouse_cron_lastalert' ) ) );
		$notificationsNextRun = MainWP_Lighthouse_Utility::format_TimeStamp( MainWP_Lighthouse_Utility::get_TimeStamp( wp_next_scheduled( 'mainwp_lighthouse_cron_alert' ) ) );

		$recheck_interval = MainWP_Lighthouse_Core::get_recheck_intervals();

		$interval = MainWP_Lighthouse_Utility::get_instance()->get_option( 'recheck_interval' );
		$interval = isset( $recheck_interval[ $interval ] ) ? $recheck_interval[ $interval ] : __( 'Once a Day', 'mainwp-lighthouse-extension' );

		$alert_score = MainWP_Lighthouse_Utility::get_instance()->get_option( 'score_noti' );
		?>
		<tr>
			<td><?php echo __( 'Start Lighthouse audits', 'mainwp-lighthouse-extension' ); ?></td>
			<td><?php echo 'mainwp_lighthouse_action_cron_start'; ?></td>
			<td><?php echo $interval; ?></td>
			<td><?php echo $startLastRun; ?></td>
			<td><?php echo $startNextRun; ?></td>
		</tr>
		<tr>
			<td><?php echo __( 'Continue Lighthouse audits', 'mainwp-lighthouse-extension' ); ?></td>
			<td><?php echo 'mainwp_lighthouse_action_cron_continue'; ?></td>
			<td><?php echo __( 'Once every 5 minutes', 'mainwp-lighthouse-extension' ); ?></td>
			<td><?php echo $continueLastRun; ?></td>
			<td><?php echo $continueNextRun; ?></td>
		</tr>
		<?php if ( $alert_score ) { ?>
		<tr>
			<td><?php echo __( 'Lighthouse notifications', 'mainwp-lighthouse-extension' ); ?></td>
			<td><?php echo 'mainwp_lighthouse_cron_alert'; ?></td>
			<td><?php echo $interval; ?></td>
			<td><?php echo $notificationsLastRun; ?></td>
			<td><?php echo $notificationsNextRun; ?></td>
		</tr>
		<?php } ?>
		<?php
	}

	/**
	 * Send Email Alert
	 *
	 * Sends email notifications when score goes below treshold.
	 *
	 * @param array  $lighthouse Lighthouse data for a site.
	 * @param string $email_settings      Email settings.
	 * @param string $type      Lighthouse audit strategy.
	 *
	 * @return bool True on succes, false on failure.
	 */
	public static function send_alert_mail( $lighthouse, $email_settings, $type ) {

		if ( empty( $lighthouse->site_id ) ) {
			return false;
		}

		$site_id = $lighthouse->site_id;

		$website = self::get_websites( $site_id );

		if ( $website && is_array( $website ) ) {
			$website = current( $website );
		}

		if ( ! $website ) {
			return;
		}

		$email = '';

		if ( ! empty( $email_settings['recipients'] ) ) {
			$email .= ',' . $email_settings['recipients']; // send to recipients, individual email settings or general email settings.
		}

		$email = trim( $email, ',' );

		if ( empty( $email ) ) {
			return false;
		}

		$site_name  = $website['name'];
		$site_url   = $website['url'];
		$score_text = '';
		$scan_time  = '';

		if ( 'desktop' == $type ) {
			$score_text  = '<h3>' . __( 'Desktop', 'mainwp-lighthouse-extension' ) . '</h3>';
			$score_text .= '<strong>Performance:</strong> ' . $lighthouse->desktop_score . '<br/>';
			$score_text .= '<strong>Accessibility:</strong> ' . $lighthouse->desktop_accessibility_score . '<br/>';
			$score_text .= '<strong>Best Practicess:</strong> ' . $lighthouse->desktop_best_practices_score . '<br/>';
			$score_text .= '<strong>SEO:</strong> ' . $lighthouse->desktop_seo_score . '<br/>';

			$scan_time = $lighthouse->desktop_last_modified;
		} elseif ( 'mobile' == $type ) {
			$score_text  = '<h3>' . __( 'Mobile', 'mainwp-lighthouse-extension' ) . '</h3>';
			$score_text .= '<strong>Performance:</strong> ' . $lighthouse->mobile_score . '<br/>';
			$score_text .= '<strong>Accessibility:</strong> ' . $lighthouse->mobile_accessibility_score . '<br/>';
			$score_text .= '<strong>Best Practicess:</strong> ' . $lighthouse->mobile_best_practices_score . '<br/>';
			$score_text .= '<strong>SEO:</strong> ' . $lighthouse->mobile_seo_score . '<br/>';

			$scan_time = $lighthouse->mobile_last_modified;
		} elseif ( 'both' == $type ) {
			$score_text  = '<h3>' . __( 'Desktop', 'mainwp-lighthouse-extension' ) . '</h3>';
			$score_text .= '<strong>Performance:</strong> ' . $lighthouse->desktop_score . '<br/>';
			$score_text .= '<strong>Accessibility:</strong> ' . $lighthouse->desktop_accessibility_score . '<br/>';
			$score_text .= '<strong>Best Practicess:</strong> ' . $lighthouse->desktop_best_practices_score . '<br/>';
			$score_text .= '<strong>SEO:</strong> ' . $lighthouse->desktop_seo_score . '<br/>';
			$score_text .= '<h3>' . __( 'Mobile', 'mainwp-lighthouse-extension' ) . '</h3>';
			$score_text .= '<strong>Performance:</strong> ' . $lighthouse->mobile_score . '<br/>';
			$score_text .= '<strong>Accessibility:</strong> ' . $lighthouse->mobile_accessibility_score . '<br/>';
			$score_text .= '<strong>Best Practicess:</strong> ' . $lighthouse->mobile_best_practices_score . '<br/>';
			$score_text .= '<strong>SEO:</strong> ' . $lighthouse->mobile_seo_score . '<br/>';

			$scan_time = $lighthouse->desktop_last_modified;
		} else {
			return false;
		}

		$heading = $email_settings['heading'];

		$args = array(
			'site_url'   => $site_url,
			'site_name'  => $site_name,
			'site_id'    => $site_id,
			'score_text' => $score_text,
			'scan_time'  => $scan_time,
			'heading'    => $heading,
		);

		$formated_content = apply_filters( 'mainwp_notification_get_template_content', '', 'emails/mainwp-lighthouse-noti-email.php', $args );
		$content_type     = "Content-Type: text/html; charset=\"utf-8\"\r\n";
		$subject          = $email_settings['subject'];
		$sent             = apply_filters( 'mainwp_send_wp_mail', null, $email, $subject, $formated_content, $content_type );

		MainWP_Lighthouse_Utility::log_debug( 'send notification mail :: ' . $email . ' :: ' . ( $sent ? 'SUCCESS' : 'FAILED' ) );

		return $sent;
	}

	/**
	 * Plugin Row Meta
	 *
	 * Displays the meta in the plugin row on the WP > Plugins > Installed Plugins page.
	 *
	 * @param array  $plugin_meta Plugin meta data.
	 * @param string $plugin_file Plugin file.
	 *
	 * @return array  $plugin_meta Plugin meta data.
	 */
	public function plugin_row_meta( $plugin_meta, $plugin_file ) {
		if ( MAINWP_LIGHTHOUSE_PLUGIN_SLUG != $plugin_file ) {
			return $plugin_meta;
		}

		$slug     = basename( $plugin_file, '.php' );
		$api_data = get_option( $slug . '_APIManAdder' );

		if ( ! is_array( $api_data ) || ! isset( $api_data['activated_key'] ) || $api_data['activated_key'] != 'Activated' || ! isset( $api_data['api_key'] ) || empty( $api_data['api_key'] ) ) {
			return $plugin_meta;
		}

		$plugin_meta[] = '<a href="?do=checkUpgrade" title="Check for updates.">Check for updates now</a>';

		return $plugin_meta;
	}

	/**
	 * Admin Init
	 *
	 * Initiates admin hooks.
	 */
	public function admin_init() {
		if ( isset( $_GET['page'] ) && ( 'Extensions-Mainwp-Lighthouse-Extension' == $_GET['page'] || 'managesites' == $_GET['page'] || 'ManageSitesLighthouse' == $_GET['page'] ) ) {
			wp_enqueue_style( 'mainwp-lighthouse-extension', MAINWP_LIGHTHOUSE_PLUGIN_URL . 'css/mainwp-lighthouse.css', array(), $this->version );
			wp_enqueue_script( 'mainwp-lighthouse-extension', MAINWP_LIGHTHOUSE_PLUGIN_URL . 'js/mainwp-lighthouse.js', array( 'jquery', 'heartbeat' ), $this->version );
			wp_localize_script(
				'mainwp-lighthouse-extension',
				'mainwpLighthouse',
				array(
					'nonce'     => wp_create_nonce( 'mwp_lighthouse_nonce' ),
					'heartbeat' => MainWP_Lighthouse_Utility::get_instance()->get_option( 'heartbeat' ),
					'progress'  => get_option( 'mainwp_ext_lig_progress' ),
				)
			);
		}
		MainWP_Lighthouse_Dashboard::get_instance()->admin_init();
	}

	/**
	 * Render tabs
	 *
	 * Renders the extension page tabs.
	 */
	public static function render_tabs() {

		$current_site_id = null;
		$website         = null;

		if ( isset( $_GET['id'] ) && ! empty( $_GET['id'] ) ) {
			$current_site_id = $_GET['id'];
			$dbwebsites      = self::get_db_sites( array( $current_site_id ) );
			if ( is_array( $dbwebsites ) && ! empty( $dbwebsites ) ) {
				$website = current( $dbwebsites );
			}
		}

		if ( $current_site_id ) {
			$error = '';
			if ( empty( $website ) || empty( $website->id ) ) {
				$error = __( 'Undefined site id. Please, try again.', 'mainwp-lighthouse-extension' );
			}

			do_action( 'mainwp_pageheader_sites', 'Lighthouse' );
			if ( ! empty( $error ) ) {
				  echo '<div class="ui segment">';
				  echo '<div class="ui yellow message">' . $error . '</div>';
				  echo '</div>';
			} else {
				MainWP_Lighthouse_Dashboard::gen_tabs_individual();
			}
			do_action( 'mainwp_pagefooter_sites', 'Lighthouse' );
		} else {
			MainWP_Lighthouse_Dashboard::gen_tabs_general();
		}
	}

	/**
	 * Render metabox
	 *
	 * Initiates the metabox.
	 */
	public static function render_metabox() {
		if ( ! isset( $_GET['page'] ) || 'managesites' == $_GET['page'] ) {
			self::individual_metabox();
		} else {
			self::global_metabox();
		}
	}

	/**
	 * Scores compare function
	 *
	 * Compare scores.
	 */
	public static function scores_compare_callback( $a, $b ) {
		$field = self::$sort_field;
		if ( $a->{$field} == $b->{$field} ) {
			return 0;
		}
		return ( $a->{$field} > $b->{$field} ) ? 1 : -1;
	}

	/**
	 * Get Best and Worst
	 *
	 * Gets top three and worst three sites by Lighthouse audits.
	 *
	 * @param array $all_scores All scores.
	 *
	 * @return array $result Top and Bottom 3 sites.
	 */
	public static function get_three_best_and_worst( $all_scores ) {
		$count = count( $all_scores );

		$get_count = ( 3 <= $count ) ? 3 : $count;
		usort( $all_scores, array( self::class, 'scores_compare_callback' ) );
		$result = array(
			'best'  => array(),
			'worst' => array(),
		);

		for ( $i = 0; $i < $get_count; $i++ ) {
			$result['best'][]  = $all_scores[ $count - $i - 1 ];
			$result['worst'][] = $all_scores[ $i ];
		}
		return $result;
	}

	/**
	 * Global Metabox
	 *
	 * Renders the Overview page widget content.
	 */
	public static function global_metabox() {

		$lighthouse = MainWP_Lighthouse_DB::get_instance()->get_lighthouse_by( 'all' );
		$lighthouse = is_array( $lighthouse ) ? $lighthouse : array();
		$strategy   = MainWP_Lighthouse_Utility::get_instance()->get_option( 'strategy' );
		$all_scores = array();

		foreach ( $lighthouse as $item ) {
			$obj = new \stdClass();
			if ( 'both' == $strategy ) {
				$obj->score                = $item->desktop_score + $item->mobile_score;
				$obj->accessibility_score  = $item->desktop_accessibility_score + $item->mobile_accessibility_score;
				$obj->best_practices_score = $item->desktop_best_practices_score + $item->mobile_best_practices_score;
				$obj->seo_score            = $item->desktop_seo_score + $item->desktop_seo_score;
			} elseif ( 'desktop' == $strategy ) {
				$obj->score                = $item->desktop_score;
				$obj->accessibility_score  = $item->desktop_accessibility_score;
				$obj->best_practices_score = $item->desktop_best_practices_score;
				$obj->seo_score            = $item->desktop_seo_score;
			} elseif ( 'mobile' == $strategy ) {
				$obj->score                = $item->mobile_score;
				$obj->accessibility_score  = $item->mobile_accessibility_score;
				$obj->best_practices_score = $item->mobile_best_practices_score;
				$obj->seo_score            = $item->mobile_seo_score;
			} else {
				continue;
			}

			$obj->desktop_score                = $item->desktop_score;
			$obj->desktop_accessibility_score  = $item->desktop_accessibility_score;
			$obj->desktop_best_practices_score = $item->desktop_best_practices_score;
			$obj->desktop_seo_score            = $item->desktop_seo_score;

			$obj->mobile_score                = $item->mobile_score;
			$obj->mobile_accessibility_score  = $item->mobile_accessibility_score;
			$obj->mobile_best_practices_score = $item->mobile_best_practices_score;
			$obj->mobile_seo_score            = $item->mobile_seo_score;

			$obj->id      = $item->id;
			$obj->site_id = $item->site_id;
			$obj->URL     = $item->URL;

			$all_scores[] = $obj;
		}

		unset( $lighthouse );

		self::$sort_field     = 'score';
		$three_performance    = self::get_three_best_and_worst( $all_scores );
		self::$sort_field     = 'accessibility_score';
		$three_accessibility  = self::get_three_best_and_worst( $all_scores );
		self::$sort_field     = 'best_practices_score';
		$three_best_practices = self::get_three_best_and_worst( $all_scores );
		self::$sort_field     = 'seo_score';
		$three_seo            = self::get_three_best_and_worst( $all_scores );

		?>
		<div class="ui grid">
			<div class="twelve wide column">
				<h3 class="ui header handle-drag">
			<?php echo __( 'Lighthouse', 'mainwp-lighthouse-extension' ); ?>
					<div class="sub header"><?php echo __( 'See the Lighthouse audit data for the top 3 and worst 3 child sites.', 'mainwp-lighthouse-extension' ); ?></div>
				</h3>
			</div>
			<div class="four wide column right aligned">
				<div class="ui dropdown right pointing mainwp-dropdown-tab">
					<div class="text"><?php esc_html_e( 'Performance', 'mainwp' ); ?></div>
					<i class="dropdown icon"></i>
					<div class="menu">
						<a class="item mainwp-lighthouse-performance" data-tab="performance" data-value="performance" title="<?php esc_attr_e( 'Performance', 'mainwp-lighthouse-extension' ); ?>" href="#"><?php esc_html_e( 'Performance', 'mainwp' ); ?></a>
						<a class="item mainwp-lighthouse-accessibility" data-tab="accessibility" data-value="accessibility" title="<?php esc_attr_e( 'Accessibility', 'mainwp-lighthouse-extension' ); ?>" href="#"><?php esc_html_e( 'Accessibility', 'mainwp' ); ?></a>
						<a class="item mainwp-lighthouse-best-practices" data-tab="best-practices" data-value="best-practices" title="<?php esc_attr_e( 'Best Practices', 'mainwp-lighthouse-extension' ); ?>" href="#"><?php esc_html_e( 'Best Practices', 'mainwp' ); ?></a>
						<a class="item mainwp-lighthouse-seo" data-tab="seo" data-value="seo" title="<?php esc_attr_e( 'SEO', 'mainwp' ); ?>" href="#"><?php esc_html_e( 'SEO', 'mainwp-lighthouse-extension' ); ?></a>
		</div>
				  </div>
				  </div>
				</div>
		<div class="ui hidden divider"></div>
		<div class="mainwp-lighthouse-performance ui tab active" data-tab="performance">
			<table class="ui table">
				<thead>
					<tr>
						<th><?php echo __( 'Site', 'mainwp-lighthouse-extension' ); ?></th>
						<?php if ( 'both' == $strategy || 'desktop' == $strategy ) : ?>
							<th class="center aligned collapsing"><i class="desktop icon"></i> <?php echo __( 'Performance', 'mainwp-lighthouse-extension' ); ?></th>
				<?php endif; ?>
			<?php if ( 'both' == $strategy || 'mobile' == $strategy ) : ?>
							<th class="center aligned collapsing"><i class="mobile alternate icon"></i> <?php echo __( 'Performance', 'mainwp-lighthouse-extension' ); ?></th>
						<?php endif; ?>
					</tr>
				</thead>
				<tbody>
					<tr class="active"><td colspan="<?php echo 'both' == $strategy ? '3' : '2'; ?>"><?php echo __( 'Top 3 Child Sites', 'mainwp-lighthouse-extension' ); ?></td></tr>
					<?php foreach ( $three_performance['best'] as $item ) : ?>
						<?php
						$site_id = $item->site_id;
						$website = self::get_websites( $site_id );

						if ( $website && is_array( $website ) ) {
							$website = current( $website );
						}

						if ( empty( $website ) ) {
							continue;
						}
						?>
					<tr>
						<td><a href="admin.php?page=ManageSitesLighthouse&id=<?php echo $site_id; ?>" data-tooltip="<?php esc_attr_e( 'Go to the Lighthouse report.', 'mainwp-lighthouse-extnsion' ); ?>" data-inverted="" data-position="right center"><?php echo $website['name']; ?></a></td>
						<?php if ( 'desktop' == $strategy || 'both' == $strategy ) : ?>
						<td class="center aligned"><span class="ui <?php echo MainWP_Lighthouse_Utility::score_color_code( $item->desktop_score ); ?> label"><?php echo $item->desktop_score; ?></span></td>
						<?php endif; ?>
						<?php if ( 'mobile' == $strategy || 'both' == $strategy ) : ?>
						<td class="center aligned"><span class="ui <?php echo MainWP_Lighthouse_Utility::score_color_code( $item->mobile_score ); ?> label"><?php echo $item->mobile_score; ?></span></td>
						<?php endif; ?>
					</tr>
					<?php endforeach; ?>
			<tr class="active"><td colspan="<?php echo 'both' == $strategy ? '3' : '2'; ?>"><?php echo __( 'Worst 3 Child Sites', 'mainwp-lighthouse-extension' ); ?></td></tr>
					<?php foreach ( $three_performance['worst'] as $item ) : ?>
						<?php
						$site_id = $item->site_id;
						$website = self::get_websites( $site_id );
						if ( $website && is_array( $website ) ) {
							$website = current( $website );
						}
						if ( empty( $website ) ) {
							continue;
						}
						?>
					<tr>
						<td><a href="admin.php?page=ManageSitesLighthouse&id=<?php echo $site_id; ?>" data-tooltip="<?php esc_attr_e( 'Go to the Lighthouse report.', 'mainwp-lighthouse-extnsion' ); ?>" data-inverted="" data-position="right center"><?php echo $website['name']; ?></a></td>
						<?php if ( 'desktop' == $strategy || 'both' == $strategy ) : ?>
						<td class="center aligned"><span class="ui <?php echo MainWP_Lighthouse_Utility::score_color_code( $item->desktop_score ); ?> label"><?php echo $item->desktop_score; ?></span></td>
						<?php endif; ?>
						<?php if ( 'mobile' == $strategy || 'both' == $strategy ) : ?>
						<td class="center aligned"><span class="ui <?php echo MainWP_Lighthouse_Utility::score_color_code( $item->mobile_score ); ?> label"><?php echo $item->mobile_score; ?></span></td>
						<?php endif; ?>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
				  </div>

		<div class="mainwp-lighthouse-accessibility ui tab" data-tab="accessibility">
			<table class="ui table">
				<thead>
					<tr>
						<th><?php echo __( 'Site', 'mainwp-lighthouse-extension' ); ?></th>
						<?php if ( 'both' == $strategy || 'desktop' == $strategy ) : ?>
							<th class="center aligned collapsing"><i class="desktop icon"></i> <?php echo __( 'Accessibility', 'mainwp-lighthouse-extension' ); ?></th>
						<?php endif; ?>
						<?php if ( 'both' == $strategy || 'mobile' == $strategy ) : ?>
							<th class="center aligned collapsing"><i class="mobile alternate icon"></i> <?php echo __( 'Accessibility', 'mainwp-lighthouse-extension' ); ?></th>
						<?php endif; ?>
					</tr>
				</thead>
				<tbody>
					<tr class="active"><td colspan="<?php echo 'both' == $strategy ? '3' : '2'; ?>"><?php echo __( 'Top 3 Child Sites', 'mainwp-lighthouse-extension' ); ?></td></tr>
					<?php foreach ( $three_accessibility['best'] as $item ) : ?>
						<?php
						$site_id = $item->site_id;
						$website = self::get_websites( $site_id );
						if ( $website && is_array( $website ) ) {
							$website = current( $website );
						}
						if ( empty( $website ) ) {
							continue;
						}
						?>
					<tr>
						<td><a href="admin.php?page=ManageSitesLighthouse&id=<?php echo $site_id; ?>" data-tooltip="<?php esc_attr_e( 'Go to the Lighthouse report.', 'mainwp-lighthouse-extnsion' ); ?>" data-inverted="" data-position="right center"><?php echo $website['name']; ?></a></td>
						<?php if ( 'desktop' == $strategy || 'both' == $strategy ) : ?>
						<td class="center aligned"><span class="ui <?php echo MainWP_Lighthouse_Utility::score_color_code( $item->desktop_accessibility_score ); ?> label"><?php echo $item->desktop_accessibility_score; ?></span></td>
						<?php endif; ?>
						<?php if ( 'mobile' == $strategy || 'both' == $strategy ) : ?>
						<td class="center aligned"><span class="ui <?php echo MainWP_Lighthouse_Utility::score_color_code( $item->mobile_accessibility_score ); ?> label"><?php echo $item->mobile_accessibility_score; ?></span></td>
						<?php endif; ?>
					</tr>
					<?php endforeach; ?>
			<tr class="active"><td colspan="<?php echo 'both' == $strategy ? '3' : '2'; ?>"><?php echo __( 'Worst 3 Child Sites', 'mainwp-lighthouse-extension' ); ?></td></tr>
					<?php foreach ( $three_accessibility['worst'] as $item ) : ?>
						<?php
						$site_id = $item->site_id;
						$website = self::get_websites( $site_id );
						if ( $website && is_array( $website ) ) {
							$website = current( $website );
						}
						if ( empty( $website ) ) {
							continue;
						}
						?>
					<tr>
						<td><a href="admin.php?page=ManageSitesLighthouse&id=<?php echo $site_id; ?>" data-tooltip="<?php esc_attr_e( 'Go to the Lighthouse report.', 'mainwp-lighthouse-extnsion' ); ?>" data-inverted="" data-position="right center"><?php echo $website['name']; ?></a></td>
						<?php if ( 'desktop' == $strategy || 'both' == $strategy ) : ?>
						<td class="center aligned"><span class="ui <?php echo MainWP_Lighthouse_Utility::score_color_code( $item->desktop_accessibility_score ); ?> label"><?php echo $item->desktop_accessibility_score; ?></span></td>
						<?php endif; ?>
						<?php if ( 'mobile' == $strategy || 'both' == $strategy ) : ?>
						<td class="center aligned"><span class="ui <?php echo MainWP_Lighthouse_Utility::score_color_code( $item->mobile_accessibility_score ); ?> label"><?php echo $item->mobile_accessibility_score; ?></span></td>
						<?php endif; ?>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
				  </div>

		<div class="mainwp-lighthouse-best-practices ui tab" data-tab="best-practices">
			<table class="ui table">
				<thead>
					<tr>
						<th><?php echo __( 'Site', 'mainwp-lighthouse-extension' ); ?></th>
						<?php if ( 'both' == $strategy || 'desktop' == $strategy ) : ?>
							<th class="center aligned collapsing"><i class="desktop icon"></i> <?php echo __( 'Best Practices', 'mainwp-lighthouse-extension' ); ?></th>
						<?php endif; ?>
						<?php if ( 'both' == $strategy || 'mobile' == $strategy ) : ?>
							<th class="center aligned collapsing"><i class="mobile alternate icon"></i> <?php echo __( 'Best Practices', 'mainwp-lighthouse-extension' ); ?></th>
						<?php endif; ?>
					</tr>
				</thead>
				<tbody>
					<tr class="active"><td colspan="<?php echo 'both' == $strategy ? '3' : '2'; ?>"><?php echo __( 'Top 3 Child Sites', 'mainwp-lighthouse-extension' ); ?></td></tr>
					<?php foreach ( $three_best_practices['best'] as $item ) : ?>
						<?php
						$site_id = $item->site_id;
						$website = self::get_websites( $site_id );
						if ( $website && is_array( $website ) ) {
							$website = current( $website );
						}
						if ( empty( $website ) ) {
							continue;
						}
						?>
					<tr>
						<td><a href="admin.php?page=ManageSitesLighthouse&id=<?php echo $site_id; ?>" data-tooltip="<?php esc_attr_e( 'Go to the Lighthouse report.', 'mainwp-lighthouse-extnsion' ); ?>" data-inverted="" data-position="right center"><?php echo $website['name']; ?></a></td>
						<?php if ( 'desktop' == $strategy || 'both' == $strategy ) : ?>
						<td class="center aligned"><span class="ui <?php echo MainWP_Lighthouse_Utility::score_color_code( $item->desktop_best_practices_score ); ?> label"><?php echo $item->desktop_best_practices_score; ?></span></td>
						<?php endif; ?>
						<?php if ( 'mobile' == $strategy || 'both' == $strategy ) : ?>
						<td class="center aligned"><span class="ui <?php echo MainWP_Lighthouse_Utility::score_color_code( $item->mobile_best_practices_score ); ?> label"><?php echo $item->mobile_best_practices_score; ?></span></td>
						<?php endif; ?>
					</tr>
					<?php endforeach; ?>
			<tr class="active"><td colspan="<?php echo 'both' == $strategy ? '3' : '2'; ?>"><?php echo __( 'Worst 3 Child Sites', 'mainwp-lighthouse-extension' ); ?></td></tr>
					<?php foreach ( $three_best_practices['worst'] as $item ) : ?>
						<?php
						$site_id = $item->site_id;
						$website = self::get_websites( $site_id );
						if ( $website && is_array( $website ) ) {
							$website = current( $website );
						}
						if ( empty( $website ) ) {
							continue;
						}
						?>
					<tr>
						<td><a href="admin.php?page=ManageSitesLighthouse&id=<?php echo $site_id; ?>" data-tooltip="<?php esc_attr_e( 'Go to the Lighthouse report.', 'mainwp-lighthouse-extnsion' ); ?>" data-inverted="" data-position="right center"><?php echo $website['name']; ?></a></td>
						<?php if ( 'desktop' == $strategy || 'both' == $strategy ) : ?>
						<td class="center aligned"><span class="ui <?php echo MainWP_Lighthouse_Utility::score_color_code( $item->desktop_best_practices_score ); ?> label"><?php echo $item->desktop_best_practices_score; ?></span></td>
						<?php endif; ?>
						<?php if ( 'mobile' == $strategy || 'both' == $strategy ) : ?>
						<td class="center aligned"><span class="ui <?php echo MainWP_Lighthouse_Utility::score_color_code( $item->mobile_best_practices_score ); ?> label"><?php echo $item->mobile_best_practices_score; ?></span></td>
						<?php endif; ?>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
				</div>

		<div class="mainwp-lighthouse-seo ui tab" data-tab="seo">
			<table class="ui table">
				<thead>
					<tr>
						<th><?php echo __( 'Site', 'mainwp-lighthouse-extension' ); ?></th>
						<?php if ( 'both' == $strategy || 'desktop' == $strategy ) : ?>
							<th class="center aligned collapsing"><i class="desktop icon"></i> <?php echo __( 'SEO', 'mainwp-lighthouse-extension' ); ?></th>
						<?php endif; ?>
						<?php if ( 'both' == $strategy || 'mobile' == $strategy ) : ?>
							<th class="center aligned collapsing"><i class="mobile alternate icon"></i> <?php echo __( 'SEO', 'mainwp-lighthouse-extension' ); ?></th>
						<?php endif; ?>
					</tr>
				</thead>
				<tbody>
					<tr class="active"><td colspan="<?php echo 'both' == $strategy ? '3' : '2'; ?>"><?php echo __( 'Top 3 Child Sites', 'mainwp-lighthouse-extension' ); ?></td></tr>
					<?php foreach ( $three_seo['best'] as $item ) : ?>
						<?php
						$site_id = $item->site_id;
						$website = self::get_websites( $site_id );
						if ( $website && is_array( $website ) ) {
							$website = current( $website );
						}
						if ( empty( $website ) ) {
							continue;
						}
						?>
					<tr>
						<td><a href="admin.php?page=ManageSitesLighthouse&id=<?php echo $site_id; ?>" data-tooltip="<?php esc_attr_e( 'Go to the Lighthouse report.', 'mainwp-lighthouse-extnsion' ); ?>" data-inverted="" data-position="right center"><?php echo $website['name']; ?></a></td>
						<?php if ( 'desktop' == $strategy || 'both' == $strategy ) : ?>
						<td class="center aligned"><span class="ui <?php echo MainWP_Lighthouse_Utility::score_color_code( $item->desktop_seo_score ); ?> label"><?php echo $item->desktop_seo_score; ?></span></td>
						<?php endif; ?>
						<?php if ( 'mobile' == $strategy || 'both' == $strategy ) : ?>
						<td class="center aligned"><span class="ui <?php echo MainWP_Lighthouse_Utility::score_color_code( $item->mobile_seo_score ); ?> label"><?php echo $item->mobile_seo_score; ?></span></td>
						<?php endif; ?>
					</tr>
					<?php endforeach; ?>
			<tr class="active"><td colspan="<?php echo 'both' == $strategy ? '3' : '2'; ?>"><?php echo __( 'Worst 3 Child Sites', 'mainwp-lighthouse-extension' ); ?></td></tr>
					<?php foreach ( $three_seo['worst'] as $item ) : ?>
						<?php
						$site_id = $item->site_id;
						$website = self::get_websites( $site_id );
						if ( $website && is_array( $website ) ) {
							$website = current( $website );
						}
						if ( empty( $website ) ) {
							continue;
						}
						?>
					<tr>
						<td><a href="admin.php?page=ManageSitesLighthouse&id=<?php echo $site_id; ?>" data-tooltip="<?php esc_attr_e( 'Go to the Lighthouse report.', 'mainwp-lighthouse-extnsion' ); ?>" data-inverted="" data-position="right center"><?php echo $website['name']; ?></a></td>
						<?php if ( 'desktop' == $strategy || 'both' == $strategy ) : ?>
						<td class="center aligned"><span class="ui <?php echo MainWP_Lighthouse_Utility::score_color_code( $item->desktop_seo_score ); ?> label"><?php echo $item->desktop_seo_score; ?></span></td>
						<?php endif; ?>
						<?php if ( 'mobile' == $strategy || 'both' == $strategy ) : ?>
						<td class="center aligned"><span class="ui <?php echo MainWP_Lighthouse_Utility::score_color_code( $item->mobile_seo_score ); ?> label"><?php echo $item->mobile_seo_score; ?></span></td>
						<?php endif; ?>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			</div>

		<div class="ui hidden divider"></div>
		<div class="ui divider" style="margin-left:-1em;margin-right:-1em;"></div>
		<div class="ui two columns grid">
			<div class="left aligned column">
				<a href="admin.php?page=Extensions-Mainwp-Lighthouse-Extension" class="ui basic green button"><?php esc_html_e( 'Lighthouse Dashboard', 'mainwp-lighthouse-extension' ); ?></a>
		</div>
		</div>
		<?php
	}

	/**
	 * Individual Metabox
	 *
	 * Renders the individual site Overview page widget content.
	 */
	public static function individual_metabox() {
		$site_id = isset( $_GET['dashboard'] ) ? $_GET['dashboard'] : 0;

		if ( empty( $site_id ) ) {
			return;
		}

		$lighthouse = MainWP_Lighthouse_DB::get_instance()->get_lighthouse_by( 'site_id', $site_id );

		if ( is_object( $lighthouse ) && $lighthouse->override ) {
			$strategy = $lighthouse->strategy;
		} else {
			$strategy = MainWP_Lighthouse_Utility::get_instance()->get_option( 'strategy' );
		}

		$desktop_performance          = 'N/A';
		$desktop_accessibility_score  = 'N/A';
		$desktop_best_practices_score = 'N/A';
		$desktop_seo_score            = 'N/A';

		$mobile_performance          = 'N/A';
		$mobile_accessibility_score  = 'N/A';
		$mobile_best_practices_score = 'N/A';
		$mobile_seo_score            = 'N/A';

		if ( ! empty( $lighthouse->desktop_score ) ) {
			$desktop_performance = $lighthouse->desktop_score;
		}
		if ( ! empty( $lighthouse->desktop_accessibility_score ) ) {
			$desktop_accessibility_score = $lighthouse->desktop_accessibility_score;
		}
		if ( ! empty( $lighthouse->desktop_best_practices_score ) ) {
			$desktop_best_practices_score = $lighthouse->desktop_best_practices_score;
		}
		if ( ! empty( $lighthouse->desktop_seo_score ) ) {
			$desktop_seo_score = $lighthouse->desktop_seo_score;
		}

		if ( ! empty( $lighthouse->mobile_score ) ) {
			$mobile_performance = $lighthouse->mobile_score;
		}
		if ( ! empty( $lighthouse->mobile_accessibility_score ) ) {
			$mobile_accessibility_score = $lighthouse->mobile_accessibility_score;
		}
		if ( ! empty( $lighthouse->mobile_best_practices_score ) ) {
			$mobile_best_practices_score = $lighthouse->mobile_best_practices_score;
		}
		if ( ! empty( $lighthouse->mobile_seo_score ) ) {
			$mobile_seo_score = $lighthouse->mobile_seo_score;
		}

		?>
		<?php if ( 'both' == $strategy || 'desktop' == $strategy ) : ?>
		<div class="ui grid">
			<div class="twelve wide column">
				<h3 class="ui header handle-drag">
					<?php echo __( 'Lighthouse - Desktop', 'mainwp-lighthouse-extension' ); ?>
					<div class="sub header"><?php echo __( 'Lighthouse reports for the child site - Strategy Desktop', 'mainwp-lighthouse-extension' ); ?></div>
				</h3>
			</div>
		</div>
		<div class="ui hidden divider"></div>
		<div id="mainwp-lighthouse-strategy-desktop">
			<div class="ui four columns tablet stackable grid">
				<div class="center aligned middle aligned column">
					<div class="mainwp-lighthouse-score ui massive <?php echo MainWP_Lighthouse_Utility::score_color_code( $desktop_performance ); ?> circular basic label" id="mainwp-lighthouse-desktop-performance"><?php echo esc_html( $desktop_performance ); ?></div>
					<h4 class="ui header"><?php echo __( 'Performance', 'mainwp-lighthouse-extension' ); ?></h4>
				</div>
				<div class="center aligned middle aligned column">
					<div class="mainwp-lighthouse-score ui massive <?php echo MainWP_Lighthouse_Utility::score_color_code( $desktop_accessibility_score ); ?> circular basic label" id="mainwp-lighthouse-desktop-accessibility"><?php echo esc_html( $desktop_accessibility_score ); ?></div>
					<h4 class="ui header"><?php echo __( 'Accessibility', 'mainwp-lighthouse-extension' ); ?></h4>
				</div>
				<div class="center aligned middle aligned column">
					<div class="mainwp-lighthouse-score ui massive <?php echo MainWP_Lighthouse_Utility::score_color_code( $desktop_best_practices_score ); ?> circular basic label" id="mainwp-lighthouse-desktop-bestpractices"><?php echo esc_html( $desktop_best_practices_score ); ?></div>
					<h4 class="ui header"><?php echo __( 'Best Practices', 'mainwp-lighthouse-extension' ); ?></h4>
				</div>
				<div class="center aligned middle aligned column">
					<div class="mainwp-lighthouse-score ui massive <?php echo MainWP_Lighthouse_Utility::score_color_code( $desktop_seo_score ); ?> circular basic label" id="mainwp-lighthouse-desktop-seo"><?php echo esc_html( $desktop_seo_score ); ?></div>
					<h4 class="ui header"><?php echo __( 'SEO', 'mainwp-lighthouse-extension' ); ?></h4>
				  </div>
				  </div>
				</div>
				<?php endif; ?>
		<?php if ( 'both' == $strategy ) : ?>
		<div class="ui hidden divider"></div>
		<div class="ui divider" style="margin-left:-1em;margin-right:-1em;"></div>
		<div class="ui hidden divider"></div>
		<?php endif; ?>
		<?php if ( 'both' == $strategy || 'mobile' == $strategy ) : ?>
		<div class="ui grid">
			<div class="twelve wide column">
				<h3 class="ui header handle-drag">
					<?php echo __( 'Lighthouse - Mobile', 'mainwp-lighthouse-extension' ); ?>
					<div class="sub header"><?php echo __( 'Lighthouse reports for the child site - Strategy Mobile', 'mainwp-lighthouse-extension' ); ?></div>
				</h3>
			</div>
				  </div>
		<div class="ui hidden divider"></div>
		<div id="mainwp-lighthouse-strategy-mobile">
			<div class="ui four columns tablet stackable grid">
				<div class="center aligned middle aligned column">
					<div class="mainwp-lighthouse-score ui massive <?php echo MainWP_Lighthouse_Utility::score_color_code( $mobile_performance ); ?> circular basic label" id="mainwp-lighthouse-desktop-performance"><?php echo esc_html( $mobile_performance ); ?></div>
					<h4 class="ui header"><?php echo __( 'Performance', 'mainwp-lighthouse-extension' ); ?></h4>
				</div>
				<div class="center aligned middle aligned column">
					<div class="mainwp-lighthouse-score ui massive <?php echo MainWP_Lighthouse_Utility::score_color_code( $mobile_accessibility_score ); ?> circular basic label" id="mainwp-lighthouse-desktop-accessibility"><?php echo esc_html( $mobile_accessibility_score ); ?></div>
					<h4 class="ui header"><?php echo __( 'Accessibility', 'mainwp-lighthouse-extension' ); ?></h4>
				</div>
				<div class="center aligned middle aligned column">
					<div class="mainwp-lighthouse-score ui massive <?php echo MainWP_Lighthouse_Utility::score_color_code( $mobile_best_practices_score ); ?> circular basic label" id="mainwp-lighthouse-desktop-bestpractices"><?php echo esc_html( $mobile_best_practices_score ); ?></div>
					<h4 class="ui header"><?php echo __( 'Best Practices', 'mainwp-lighthouse-extension' ); ?></h4>
				</div>
				<div class="center aligned middle aligned column">
					<div class="mainwp-lighthouse-score ui massive <?php echo MainWP_Lighthouse_Utility::score_color_code( $mobile_seo_score ); ?> circular basic label" id="mainwp-lighthouse-desktop-seo"><?php echo esc_html( $mobile_seo_score ); ?></div>
					<h4 class="ui header"><?php echo __( 'SEO', 'mainwp-lighthouse-extension' ); ?></h4>
				</div>
				  </div>
				</div>
				<?php endif; ?>
		<div class="ui hidden divider"></div>
		<div class="ui divider" style="margin-left:-1em;margin-right:-1em;"></div>
		<div class="ui two columns grid">
			<div class="left aligned column">
				<a href="admin.php?page=Extensions-Mainwp-Lighthouse-Extension" class="ui basic green button"><?php esc_html_e( 'Lighthouse Dashboard', 'mainwp-lighthouse-extension' ); ?></a>
			</div>
			<div class="right aligned column">
				<a href="admin.php?page=ManageSitesLighthouse&id=<?php echo $site_id; ?>" class="ui green button"><?php esc_html_e( 'Detailed Report', 'mainwp-lighthouse-extension' ); ?></a>
		</div>
		</div>
		<?php
	}

	/**
	 * Individual Settigns Post
	 *
	 * Handles the Individual site save settings post request.
	 *
	 * @param int $websiteId Child site ID.
	 */
	public static function handle_individual_settings_post( $websiteId ) {
		if ( isset( $_POST['submit'] ) && $websiteId ) {
			self::handle_settings_post( $websiteId );
		}
	}

	/**
	 * General Settigns Post
	 *
	 * Handles the general save settings post request.
	 *
	 * @return mixed $save_output Save output.
	 */
	public static function handle_general_settings_post() {
		$save_output = self::handle_settings_post();
		return $save_output;
	}

	/**
	 * Check Security
	 *
	 * Verifies nonce for security reasons.
	 */
	public static function check_security() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), 'mwp_lighthouse_nonce' ) ) {
			die( __( 'Invalid request. Please, try again.', 'mainwp-lighthouse-extension' ) );
		}
	}

	/**
	 * Settigns Post
	 *
	 * Handles the save settings post request.
	 *
	 * @param int $website_id Child site ID.
	 *
	 * @return mixed Save output.
	 */
	public static function handle_settings_post( $website_id = null ) {
		if ( isset( $_POST['mwp_lighthouse_setting_submit'] ) ) {
			self::check_security();
		}

		if ( isset( $_POST['mwp_lighthouse_setting_submit'] ) || $website_id ) {
			$output = array();

			$score_noti = '';
			if ( isset( $_POST['mainwp_lighthouse_score_noti'] ) ) {
				$score_noti = intval( $_POST['mainwp_lighthouse_score_noti'] );
			}

			$api_key = '';
			if ( isset( $_POST['mainwp_lighthouse_google_developer_key'] ) ) {
				$api_key = sanitize_text_field( $_POST['mainwp_lighthouse_google_developer_key'] );
			}

			$response_language = 'en_US';
			if ( isset( $_POST['mainwp_lighthouse_response_language'] ) ) {
				$response_language = $_POST['mainwp_lighthouse_response_language'];
			}

			$strategy = 'desktop';
			if ( isset( $_POST['mainwp_lighthouse_strategy'] ) ) {
				$strategy = $_POST['mainwp_lighthouse_strategy'];
			}

			$use_schedule = 0;
			if ( isset( $_POST['mainwp_lighthouse_use_schedule'] ) ) {
				$use_schedule = $_POST['mainwp_lighthouse_use_schedule'];
			}

			$recheck_interval = 86400;
			if ( isset( $_POST['mainwp_lighthouse_recheck_interval'] ) ) {
				$recheck_interval = $_POST['mainwp_lighthouse_recheck_interval'];
			}

			$exec_time = 300;
			if ( isset( $_POST['mainwp_lighthouse_max_execution_time'] ) ) {
				$exec_time = $_POST['mainwp_lighthouse_max_execution_time'];
			}

			$run_time = 0;
			if ( isset( $_POST['mainwp_lighthouse_max_run_time'] ) ) {
				$run_time = intval( $_POST['mainwp_lighthouse_max_run_time'] );
			}

			$sleep_time = 0;
			if ( isset( $_POST['mainwp_lighthouse_sleep_time'] ) ) {
				$sleep_time = $_POST['mainwp_lighthouse_sleep_time'];
			}

			if ( isset( $_POST['mwp_lighthouse_setting_submit'] ) && ! $website_id ) {
				$old_interval = MainWP_Lighthouse_Utility::get_instance()->get_option( 'recheck_interval' );

				MainWP_Lighthouse_Utility::get_instance()->set_option( 'score_noti', $score_noti );
				MainWP_Lighthouse_Utility::get_instance()->set_option( 'google_developer_key', $api_key );
				MainWP_Lighthouse_Utility::get_instance()->set_option( 'response_language', $response_language );
				MainWP_Lighthouse_Utility::get_instance()->set_option( 'strategy', $strategy );
				MainWP_Lighthouse_Utility::get_instance()->set_option( 'use_schedule', $use_schedule );

				MainWP_Lighthouse_Utility::get_instance()->set_option( 'recheck_interval', $recheck_interval );
				MainWP_Lighthouse_Utility::get_instance()->set_option( 'max_execution_time', $exec_time );
				MainWP_Lighthouse_Utility::get_instance()->set_option( 'max_run_time', $run_time );
				MainWP_Lighthouse_Utility::get_instance()->set_option( 'sleep_time', $sleep_time );
				MainWP_Lighthouse_Utility::get_instance()->set_option( 'heartbeat', isset( $_POST['heartbeat'] ) ? sanitize_text_field( $_POST['heartbeat'] ) : 'standard' );
				MainWP_Lighthouse_Core::get_instance()->init_jobs( $old_interval );
			} elseif ( $website_id ) {
				$override = isset( $_POST['mainwp_lighthouse_setting_site_override'] ) ? 1 : 0;

				$settings = array(
					'google_developer_key' => $api_key,
					'response_language'    => $response_language,
					'recheck_interval'     => $recheck_interval,
					'score_noti'           => $score_noti,
				);

				$update                 = array();
				$update['settings']     = wp_json_encode( $settings );
				$update['strategy']     = $strategy;
				$update['use_schedule'] = $use_schedule ? 1 : 0;
				$update['override']     = $override;
				$update['site_id']      = $website_id;

				$out = MainWP_Lighthouse_DB::get_instance()->update_lighthouse( $update );
			}
			return $output;
		}
		  return false;
	}

	public static function gen_individual_lighthouse_settings_box() {
		$current_site_id = 0;

		if ( self::is_managesites_page() ) {
			$current_site_id = $_GET['id'];
		}

		$alertLighthouse = 89;

		if ( $current_site_id ) {
			$site_settings = MainWP_Lighthouse_DB::get_instance()->get_lighthouse_by( 'site_id', $current_site_id );
		}

		$override = 0;
		$settings = array();

		if ( $current_site_id && $site_settings ) {
			$settings = $site_settings->settings;
			if ( is_array( $settings ) ) {
				$alertLighthouse = isset( $settings['score_noti'] ) ? $settings['score_noti'] : 89;
			}
			$override = $site_settings->override;
		}

		?>

		<?php if ( $current_site_id ) : ?>
			<?php if ( MainWP_Lighthouse_Utility::show_mainwp_message( 'mainwp-lighthouse-settings-info-message' ) ) : ?>
				<div class="ui info message">
					<i class="close icon mainwp-notice-dismiss" notice-id="mainwp-lighthouse-settings-info-message"></i>
					<?php echo sprintf( __( 'Manage the Lighthouse settings. For detailed information, review %1$shelp documentation%2$s.', 'mainwp-lighthouse-extension' ), '<a href="https://kb.mainwp.com/docs/mainwp-lighthouse-extension/" target="_blank">', '</a>' ); ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>

		<div class="ui message" id="mainwp-message-zone" style="display:none"></div>

		<h3 class="header"><?php echo __( 'Individual Site Lighthouse Settings', 'mainwp-lighthouse-extension' ); ?></h3>

		  <?php if ( $current_site_id ) : ?>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Overwrite general settings', 'mainwp-lighthouse-extension' ); ?></label>
			<div class="ten wide column ui toggle checkbox">
				<input type="checkbox" name="mainwp_lighthouse_setting_site_override" id="mainwp_lighthouse_setting_site_override" value="1" <?php echo ( 0 == $override ? '' : 'checked="checked"' ); ?>><label></label>
			</div>
		</div>
		<?php endif; ?>

		<?php if ( $current_site_id ) : ?>
		<div class="mainwp-lighthouse-site-settings-toggle-area" <?php echo 0 == $override ? 'style="display:none"' : ''; ?>>
		<?php endif; ?>
		<?php
	}


	/**
	 * Render settings
	 *
	 * Renders the extension settings page.
	 *
	 * @param int $individual Individual or General settings.
	 */
	public static function render_settings( $individual = false ) {

		if ( ! $individual ) {
			if ( isset( $_GET['test_lighthouse_cron'] ) ) {
				MainWP_Lighthouse_Core::get_instance()->mainwp_lighthouse_cron_start(); // to debug: start new cron check.
			}
			if ( isset( $_GET['test_lighthouse_notification'] ) ) {
				self::cron_lighthouse_alert( true ); // to debug.
			}
		}

		$current_site_id = 0;
		if ( self::is_managesites_page() ) {
			$current_site_id = $_GET['id'];
		}

		$apiKey           = '';
		$sleepTime        = 0;
		$responseLanguage = '';
		$reportExp        = 0;
		$maxExecutionTime = 0;
		$maxRuntime       = 0;

		$use_schedule    = 0;
		$alertLighthouse = 89;

		if ( $current_site_id ) {
			$site_settings = MainWP_Lighthouse_DB::get_instance()->get_lighthouse_by( 'site_id', $current_site_id );
		} else {
			$apiKey           = MainWP_Lighthouse_Utility::get_instance()->get_option( 'google_developer_key' );
			$responseLanguage = MainWP_Lighthouse_Utility::get_instance()->get_option( 'response_language' );
			$strategy         = MainWP_Lighthouse_Utility::get_instance()->get_option( 'strategy' );
			$use_schedule     = MainWP_Lighthouse_Utility::get_instance()->get_option( 'use_schedule' );
			$reportExp        = MainWP_Lighthouse_Utility::get_instance()->get_option( 'recheck_interval' );
			$maxExecutionTime = MainWP_Lighthouse_Utility::get_instance()->get_option( 'max_execution_time' );
			$maxRuntime       = MainWP_Lighthouse_Utility::get_instance()->get_option( 'max_run_time' );
			$sleepTime        = MainWP_Lighthouse_Utility::get_instance()->get_option( 'sleep_time' );
			$alertLighthouse  = MainWP_Lighthouse_Utility::get_instance()->get_option( 'score_noti', 89 );
		}

		$override = 0;

		if ( $current_site_id && $site_settings ) {

			$settings = $site_settings->settings;

			if ( is_array( $settings ) ) {
				$apiKey           = isset( $settings['google_developer_key'] ) ? $settings['google_developer_key'] : '';
				$responseLanguage = isset( $settings['response_language'] ) ? $settings['response_language'] : '';
				$reportExp        = isset( $settings['recheck_interval'] ) ? $settings['recheck_interval'] : 0;
				$maxExecutionTime = isset( $settings['max_execution_time'] ) ? $settings['max_execution_time'] : 0;
				$maxRuntime       = isset( $settings['max_run_time'] ) ? $settings['max_run_time'] : 0;
				$sleepTime        = isset( $settings['sleep_time'] ) ? $settings['sleep_time'] : 0;
				$alertLighthouse  = isset( $settings['score_noti'] ) ? $settings['score_noti'] : 89;
			}

			$strategy     = $site_settings->strategy;
			$use_schedule = $site_settings->use_schedule;
			$override     = $site_settings->override;
		}

		$responseLanguage = empty( $responseLanguage ) ? 'en_US' : $responseLanguage;
		$strategy         = empty( $strategy ) ? 'both' : $strategy;
		$reportExp        = empty( $reportExp ) ? 86400 : $reportExp;
		$maxExecutionTime = empty( $maxExecutionTime ) ? 300 : $maxExecutionTime;
		$maxRuntime       = empty( $maxRuntime ) ? 0 : $maxRuntime;

		$language = array(
			'ar'    => 'Arabic',
			'bg'    => 'Bulgarian',
			'ca'    => 'Catalan',
			'zh_TW' => 'Traditional Chinese (Taiwan)',
			'zh_CN' => 'Simplified Chinese',
			'hr'    => 'Croatian',
			'cs'    => 'Czech',
			'da'    => 'Danish',
			'nl'    => 'Dutch',
			'en_US' => 'English',
			'en_GB' => 'English UK',
			'et'    => 'Estonian',
			'fil'   => 'Filipino',
			'fi'    => 'Finnish',
			'fr'    => 'French',
			'de'    => 'German',
			'el'    => 'Greek',
			'iw'    => 'Hebrew',
			'hi'    => 'Hindi',
			'hu'    => 'Hungarian',
			'id'    => 'Indonesian',
			'it'    => 'Italian',
			'ja'    => 'Japanese',
			'ko'    => 'Korean',
			'lv'    => 'Latvian',
			'lt'    => 'Lithuanian',
			'no'    => 'Norwegian',
			'pl'    => 'Polish',
			'pt_BR' => 'Portuguese (Brazilian)',
			'pt_PT' => 'Portuguese (Portugal)',
			'ro'    => 'Romanian',
			'ru'    => 'Russian',
			'sr'    => 'Serbian',
			'sk'    => 'Slovakian',
			'sl'    => 'Slovenian',
			'es'    => 'Spanish',
			'sv'    => 'Swedish',
			'th'    => 'Thai',
			'tr'    => 'Turkish',
			'uk'    => 'Ukrainian',
			'vi'    => 'Vietnamese',
		);

		$strategy_values = array(
			'desktop' => __( 'Desktop', 'mainwp-lighthouse-extension' ),
			'mobile'  => __( 'Mobile', 'mainwp-lighthouse-extension' ),
			'both'    => __( 'Both', 'mainwp-lighthouse-extension' ),
		);

		$recheck_interval = MainWP_Lighthouse_Core::get_recheck_intervals();

		$excution_time = array(
			60   => __( '1 Minute', 'mainwp-lighthouse-extension' ),
			300  => __( '5 Minutes', 'mainwp-lighthouse-extension' ),
			600  => __( '10 Minutes', 'mainwp-lighthouse-extension' ),
			900  => __( '15 Minutes', 'mainwp-lighthouse-extension' ),
			1800 => __( '30 Minutes', 'mainwp-lighthouse-extension' ),
		);

		$max_run_time = array(
			0   => __( 'No Limit', 'mainwp-lighthouse-extension' ),
			60  => __( '60 Seconds', 'mainwp-lighthouse-extension' ),
			90  => __( '90 Seconds', 'mainwp-lighthouse-extension' ),
			120 => __( '120 Seconds', 'mainwp-lighthouse-extension' ),
			150 => __( '150 Seconds', 'mainwp-lighthouse-extension' ),
			180 => __( '180 Seconds', 'mainwp-lighthouse-extension' ),
		);

		$sleep_times = array(
			0  => __( '0 Seconds', 'mainwp-lighthouse-extension' ),
			1  => __( '1 Seconds', 'mainwp-lighthouse-extension' ),
			2  => __( '2 Seconds', 'mainwp-lighthouse-extension' ),
			3  => __( '3 Seconds', 'mainwp-lighthouse-extension' ),
			4  => __( '4 Seconds', 'mainwp-lighthouse-extension' ),
			5  => __( '5 Seconds', 'mainwp-lighthouse-extension' ),
			10 => __( '10 Seconds', 'mainwp-lighthouse-extension' ),
		);

		?>

		<?php if ( ! $individual ) : ?>
			<?php if ( MainWP_Lighthouse_Utility::show_mainwp_message( 'mainwp-lighthouse-settings-info-message' ) ) : ?>
				<div class="ui info message">
					<i class="close icon mainwp-notice-dismiss" notice-id="mainwp-lighthouse-settings-info-message"></i>
					<?php echo sprintf( __( 'Manage the Lighthouse settings. For detailed information, review %1$shelp documentation%2$s.', 'mainwp-lighthouse-extension' ), '<a href="https://kb.mainwp.com/docs/mainwp-lighthouse-extension/" target="_blank">', '</a>' ); ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>

		<h3 class="header"><?php echo __( 'Lighthouse Settings', 'mainwp-lighthouse-extension' ); ?></h3>

		<?php if ( MainWP_Lighthouse_Utility::show_mainwp_message( 'mainwp-lighthouse-api-info-message' ) ) : ?>
			<div class="ui info message">
				<i class="close icon mainwp-notice-dismiss" notice-id="mainwp-lighthouse-api-info-message"></i>
				<?php echo sprintf( __( 'If you do not have an API key, you can create a new one for free from the %1$sGoogle Cloud Platform%2$s. Read the %3$shelp documentation%4$s for additional information about creating an API key.', 'mainwp-lighthouse-extension' ), '<a href="https://console.developers.google.com/" target="_blank">', '</a>', '<a href="https://kb.mainwp.com/docs/get-the-google-pagespeed-insights-api-key/" target="_blank">', '</a>' ); ?>
			</div>
		<?php endif; ?>

		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Google PageSpeed Insights API key', 'mainwp-lighthouse-extension' ); ?></label>
		  <div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Enter your Google PageSpeed Insigths API Key here.', 'mainwp-lighthouse-extension' ); ?>" data-inverted="" data-position="top left">
				<input type="text" name="mainwp_lighthouse_google_developer_key" value="<?php echo stripslashes( $apiKey ); ?>">
			</div>
		</div>

		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Language', 'mainwp-lighthouse-extension' ); ?></label>
		  <div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Select preferred language for the Lighthouse reports.', 'mainwp-lighthouse-extension' ); ?>" data-inverted="" data-position="top left">
				<select id="mainwp_lighthouse_response_language" name="mainwp_lighthouse_response_language" class="ui dropdown">
				  <?php foreach ( $language as $key => $lang ) : ?>
						<?php
						$_select = '';
						if ( $key == $responseLanguage ) {
							$_select = ' selected ';
						}
						echo '<option value="' . $key . '" ' . $_select . '>' . $lang . '</option>';
						?>
					<?php endforeach; ?>
				</select>
			</div>
		</div>

		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Strategy', 'mainwp-lighthouse-extension' ); ?></label>
		  <div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Select if you want to get Desktop, Mobile or both strategy Lighthouse reports.', 'mainwp-lighthouse-extension' ); ?>" data-inverted="" data-position="top left">
				<select id="mainwp_lighthouse_strategy" name="mainwp_lighthouse_strategy" class="ui dropdown">
						<?php foreach ( $strategy_values as $key => $type ) : ?>
							<?php
							$_select = '';
							if ( $key == $strategy ) {
								$_select = ' selected ';
							}
							echo '<option value="' . $key . '" ' . $_select . '>' . $type . '</option>';
							?>
					<?php endforeach; ?>
				</select>
			</div>
		</div>

		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Notifications threshold', 'mainwp-lighthouse-extension' ); ?></label>
			<div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Set the treshold for notifications.', 'mainwp-lighthouse-extension' ); ?>" data-inverted="" data-position="top left">
				<select id="mainwp_lighthouse_score_noti" name="mainwp_lighthouse_score_noti" class="ui dropdown">
					<?php
					for ( $i = 0; $i < 101; $i++ ) {
						$_select = '';
						if ( $i == $alertLighthouse ) {
							$_select = ' selected ';
						}
						echo '<option value="' . $i . '" ' . $_select . '>' . $i . '</option>';
					}
					?>
				</select>
			</div>
		</div>

		<h3 class="header"><?php echo __( 'Automated Audits', 'mainwp-lighthouse-extension' ); ?></h3>

		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Automatically audit sites', 'mainwp-lighthouse-extension' ); ?></label>
		  <div class="ten wide column ui toggle checkbox" data-tooltip="<?php esc_attr_e( 'Enable if you want the extension to run audits automatically.', 'mainwp-lighthouse-extension' ); ?>" data-inverted="" data-position="top left">
				<input type="checkbox" name="mainwp_lighthouse_use_schedule" id="mainwp_lighthouse_use_schedule" value="1" <?php checked( $use_schedule ); ?>><label></label>
			</div>
		</div>

		<div class="ui grid field" id="mainwp-lighthouse-recheck-toggle-area" <?php echo $use_schedule == 1 ? '' : 'style="display:none"'; ?>>
			<label class="six wide column middle aligned"><?php _e( 'Automated audit frequency', 'mainwp-lighthouse-extension' ); ?></label>
		  <div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Select automated audits frequency.', 'mainwp-lighthouse-extension' ); ?>" data-inverted="" data-position="top left">
				<select id="mainwp_lighthouse_recheck_interval" name="mainwp_lighthouse_recheck_interval" class="ui dropdown">
						<?php foreach ( $recheck_interval as $key => $exp ) : ?>
							<?php
							$_select = '';
							if ( $key == $reportExp ) {
								$_select = ' selected ';
							}
							echo '<option value="' . $key . '" ' . $_select . '>' . $exp . '</option>';
							?>
					<?php endforeach; ?>
				</select>
			</div>
		</div>

		<?php if ( $individual ) : ?>
		</div>
		<?php endif; ?>

		<?php if ( empty( $current_site_id ) && ! $individual ) : ?>

		<h3 class="header"><?php echo __( 'Advanced Settings', 'mainwp-lighthouse-extension' ); ?></h3>

		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Maximum execution time', 'mainwp-lighthouse-extension' ); ?></label>
		  <div class="ten wide column" data-tooltip="<?php esc_attr_e( 'The default value of 5 minutes should be fine for most sites. Increasing this value may help if your Lighthouse reports do not finish completely.', 'mainwp-lighthouse-extension' ); ?>" data-inverted="" data-position="top left">
				<select id="mainwp_lighthouse_max_execution_time" name="mainwp_lighthouse_max_execution_time" class="ui dropdown">
						<?php foreach ( $excution_time as $key => $time ) : ?>
							<?php
							$_select = '';
							if ( $key == $maxExecutionTime ) {
								$_select = ' selected ';
							}
							echo '<option value="' . $key . '" ' . $_select . '>' . $time . '</option>';
							?>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
		
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Maximum script run time', 'mainwp-lighthouse-extension' ); ?></label>
		  <div class="ten wide column" data-tooltip="<?php esc_attr_e( 'If your scans do not finish completely and changing the Maximum Execution Time does not help, increase this value until you find the maximum allowed runtime.', 'mainwp-lighthouse-extension' ); ?>" data-inverted="" data-position="top left">
				<select id="mainwp_lighthouse_max_run_time" name="mainwp_lighthouse_max_run_time" class="ui dropdown">
						<?php foreach ( $max_run_time as $key => $time ) : ?>
							<?php
							$_select = '';
							if ( $key == $maxRuntime ) {
								$_select = ' selected ';
							}
							echo '<option value="' . $key . '" ' . $_select . '>' . $time . '</option>';
							?>
					<?php endforeach; ?>
				</select>
			</div>
		</div>

		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Throttling delay time', 'mainwp-lighthouse-extension' ); ?></label>
		  <div class="ten wide column" data-tooltip="<?php esc_attr_e( 'Increasing this value may slow down Lighthouse reporting, but may help provide more accurate reports on poorly performing web servers.', 'mainwp-lighthouse-extension' ); ?>" data-inverted="" data-position="top left">
				<select id="mainwp_lighthouse_sleep_time" name="mainwp_lighthouse_sleep_time" class="ui dropdown">
						<?php foreach ( $sleep_times as $key => $time ) : ?>
							<?php
							$_select = '';
							if ( $key == $sleepTime ) {
								$_select = ' selected ';
							}
							echo '<option value="' . $key . '" ' . $_select . '>' . $time . '</option>';
							?>
					<?php endforeach; ?>
				</select>
			</div>
		</div>

		<?php endif; ?>

		<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'mwp_lighthouse_nonce' ); ?>">
		<input type="hidden" name="mwp_lighthouse_setting_submit" value="1">
		<?php if ( ! $current_site_id ) : ?>
		<div class="ui divider"></div>
			<input type="submit" value="<?php _e( 'Save Settings', 'mainwp-lighthouse-extension' ); ?>" class="ui green big button" id="mainwp-lighthouse-save-settings-button" <?php echo apply_filters( 'mainwp_lighthouse_check_status', false ) ? 'disabled' : ''; ?>>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render Error Messages
	 *
	 * Renders all error messags.
	 */
	public function render_messages() {
		$worker_status = apply_filters( 'mainwp_lighthouse_check_status', false );
		$page          = 'admin.php?page=Extensions-Mainwp-Lighthouse-Extension';
		$tab           = isset( $_GET['tab'] ) && 'settings' == $_GET['tab'] ? 'settings' : 'dashboard';
		?>

		<?php if ( MainWP_Lighthouse_Utility::get_instance()->get_option( 'google_developer_key' ) == '' && 'settings' !== $tab ) : ?>
			<div class="ui message red">
				<?php _e( 'You must enter your Google API key in the extension settings.', 'mainwp-lighthouse-extension' ); ?>
			</div>
		<?php endif; ?>

		<?php if ( MainWP_Lighthouse_Utility::get_instance()->get_option( 'bad_api_key' ) && 'settings' !== $tab ) : ?>
			<div class="ui message red">
				<?php _e( 'The Google Pagespeed API Key you entered appears to be invalid.', 'mainwp-lighthouse-extension' ); ?>
			</div>
		<?php endif; ?>

		<?php if ( MainWP_Lighthouse_Utility::get_instance()->get_option( 'pagespeed_disabled' ) && 'settings' == $tab ) : ?>
			<div class="ui message red">
				<?php _e( 'The "PageSpeed Insights API" service is not enabled. To enable it, please visit the', 'mainwp-lighthouse-extension' ); ?> <a href="https://code.google.com/apis/console/" target="_blank"><?php _e( 'Google API Console', 'mainwp-lighthouse-extension' ); ?></a>.
			</div>
		<?php endif; ?>

		<?php if ( MainWP_Lighthouse_Utility::get_instance()->get_option( 'api_restriction' ) ) : ?>
			<div class="ui message red">
				<?php _e( 'This referrer or IP address is restricted from using your API Key. To change your API Key restrictions, please visit the', 'mainwp-lighthouse-extension' ); ?> <a href="https://code.google.com/apis/console/" target="_blank"><?php _e( 'Google API Console', 'mainwp-lighthouse-extension' ); ?></a>.
			</div>
		<?php endif; ?>

		<?php if ( $error_message = get_option( 'mainwp_ext_lig_error_message' ) ) : ?>
			<div class="ui message red">
				<?php echo $error_message; ?>
			</div>
		<?php endif; ?>

		<?php if ( MainWP_Lighthouse_Utility::get_instance()->get_option( 'new_ignored_items' ) ) : ?>
			<div class="ui message red">
				<?php _e( 'One or more URLs could not be reached by Lighthouse. Please reload the page and try again.', 'mainwp-lighthouse-extension' ); ?>.
			</div>
		<?php endif; ?>

		<?php if ( MainWP_Lighthouse_Utility::get_instance()->get_option( 'backend_error' ) ) : ?>
			<div class="ui message red">
				<div><?php _e( 'An error has been encountered while checking one or more URLs.', 'mainwp-lighthouse-extension' ); ?></div>
				<div><?php _e( 'Possible causes:', 'mainwp-lighthouse-extension' ); ?></div>
				<div class="ui list">
					<div class="item"><?php _e( 'Daily API limit exceeded. <a href="https://code.google.com/apis/console" target="_blank">Check API usage</a>.', 'mainwp-lighthouse-extension' ); ?></div>
					<div class="item"><?php _e( 'API Key user limit exceeded. <a href="https://code.google.com/apis/console" target="_blank">Check API usage</a>.', 'mainwp-lighthouse-extension' ); ?></div>
					<div class="item"><?php _e( 'The URL is not publicly accessible or is incorrect.', 'mainwp-lighthouse-extension' ); ?></div>
				</div>
			</div>
		<?php endif; ?>
		
		<?php if ( ! $worker_status && ! MainWP_Lighthouse_Utility::get_instance()->get_option( 'last_run_finished' ) ) : ?>
			<div class="ui message red">
				<?php if ( apply_filters( 'mainwp_lighthouse_set_time_limit_disabled', false ) ) : ?>
					<?php _e( 'The last lighthouse report audit failed to finish successfully. We have detected that your server may not allow the maximum execution time to be overridden by this plugin. If you continue to experience problems with Lighthouse report audits failing to complete, try setting the Maximum script run time in the Advanced Settings section on the Extension Settings page.', 'mainwp-lighthouse-extension' ); ?>
				<?php else : ?>
					<?php _e( 'The last Lighthouse report audit failed to finish successfully. If you continue to experience problems with lighthouse report audits failing to complete, try increasing the Maximum execution time, or setting the Maximum script run time in the Advanced Settings section on the Extension Settings page.', 'mainwp-lighthouse-extension' ); ?>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( $worker_status = apply_filters( 'mainwp_lighthouse_check_status', false ) ) : ?>			
			<?php if ( 'settings' == $tab ) : ?>
				<div class="ui message yellow">
					<?php _e( 'MainWP Lighthouse settings cannot be changed while Lighthouse audit is in progress. Please wait until it has finished to make changes.', 'mainwp-lighthouse-extension' ); ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>		

		<?php
		$action_message = MainWP_Lighthouse_Utility::get_instance()->get_option( 'action_message' );
		if ( $action_message ) :
			if ( ! is_array( $action_message ) ) :
				?>
				<div class="ui message yellow">
					<div><?php echo $action_message; ?></div>
					</div>
				<?php elseif ( isset( $action_message['type'] ) && isset( $action_message['message'] ) ) : ?>
				<div class="<?php echo $action_message['type']; ?>">
					<div><?php echo $action_message['message']; ?></div>
					</div>
				<?php endif; ?>
		<?php endif; ?>

		<?php
			// Clear any one-time messages from above
			do_action( 'mainwp_lighthouse_update_option', 'last_run_finished', true );
			do_action( 'mainwp_lighthouse_update_option', 'new_ignored_items', false );
			do_action( 'mainwp_lighthouse_update_option', 'backend_error', false );
			do_action( 'mainwp_lighthouse_update_option', 'action_message', false );
			delete_option( 'mainwp_ext_lig_error_message' );
	}

	/**
	 * Check Time Limit
	 *
	 * Checks if the 'set_time_limit' function has been disabled on the server.
	 *
	 * @return bool True if disabled, false if not.
	 */
	public function check_set_time_limit( $disabled ) {
		$disabled = explode( ',', ini_get( 'disable_functions' ) );

		return in_array( 'set_time_limit', $disabled );
	}

	/**
	 * Recheck Site
	 *
	 * Rechecks a childs site.
	 *
	 * @param array $website Child site.
	 *
	 * @return string Feedback message.
	 */
	public function recheck_site( $website ) {

		if ( ! is_array( $website ) || ! isset( $website['url'] ) || ! isset( $website['id'] ) ) {
			return;
		}

		$urls_to_recheck = array(
			'urls'            => array(
				array(
					'url'     => $website['url'],
					'site_id' => $website['id'],
				),
			),
			'total_url_count' => 1,
		);

		MainWP_Lighthouse_Utility::log_debug( 'recheck site' );
		MainWP_Lighthouse_Utility::log_debug( 'total urls to recheck :: ' . count( $urls_to_recheck['urls'] ) );
		MainWP_Lighthouse_Core::get_instance()->worker_start( $urls_to_recheck );
		$message = __( 'Audit in progress for the site.', 'mainwp-lighthouse-extension' );
		return $message;
	}

	/**
	 * Get Websites
	 *
	 * Gets all child sites through the 'mainwp_getsites' filter.
	 *
	 * @param array $site_ids  Child sites IDs.
	 * @param array $group_ids Groups IDs.
	 *
	 * @return array Child sites array.
	 */
	public static function get_db_sites( $site_ids, $group_ids = array() ) {
		if ( ! is_array( $site_ids ) ) {
			$site_ids = array();
		}

		if ( ! is_array( $group_ids ) ) {
			$group_ids = array();
		}

		if ( ! empty( $site_ids ) || ! empty( $group_ids ) ) {
			global $mainWPLighthouseExtensionActivator;
			return apply_filters( 'mainwp_getdbsites', $mainWPLighthouseExtensionActivator->get_child_file(), $mainWPLighthouseExtensionActivator->get_child_key(), $site_ids, $group_ids );
		}
		return false;
	}

	/**
	 * Get Websites
	 *
	 * Gets all child sites through the 'mainwp_getsites' filter.
	 *
	 * @param array|null $site_id  Child sites ID.
	 *
	 * @return array Child sites array.
	 */
	public static function get_websites( $site_id = null ) {
		global $mainWPLighthouseExtensionActivator;
		return apply_filters( 'mainwp_getsites', $mainWPLighthouseExtensionActivator->get_child_file(), $mainWPLighthouseExtensionActivator->get_child_key(), $site_id, false );
	}


	/**
	 * Get DB Websites
	 *
	 * Gets all child sites through the 'mainwp_getsites' filter.
	 *
	 * @param array $site_ids  Child sites IDs.
	 * @param array $group_ids Groups IDs.
	 *
	 * @return array Child sites array.
	 */
	public static function get_db_websites( $site_ids = null, $group_ids = false ) {
		global $mainWPLighthouseExtensionActivator;
		return apply_filters( 'mainwp_getdbsites', $mainWPLighthouseExtensionActivator->get_child_file(), $mainWPLighthouseExtensionActivator->get_child_key(), $site_ids, $group_ids );
	}

	/**
	 * REST API Reports
	 *
	 * Handles the REST API sites reports.
	 *
	 * @param array $website Child site.
	 */
	public static function handle_wp_cli_sites_reports( $website ) {

		$website_id = $website['id'];

		$lighthouse_data = MainWP_Lighthouse_DB::get_instance()->get_lighthouse_by( 'site_id', $website_id );

		\WP_CLI::line( ' -> ' . $website['name'] . ' (' . $website['url'] . ')' );

		if ( ! empty( $lighthouse_data ) ) {

			$desktop_performance          = 'N/A';
			$desktop_accessibility_score  = 'N/A';
			$desktop_best_practices_score = 'N/A';
			$desktop_seo_score            = 'N/A';
			$desktop_lab_data             = false;

			$mobile_performance          = 'N/A';
			$mobile_accessibility_score  = 'N/A';
			$mobile_best_practices_score = 'N/A';
			$mobile_seo_score            = 'N/A';
			$mobile_lab_data             = false;

			if ( ! empty( $lighthouse_data->desktop_score ) ) {
				$desktop_performance = $lighthouse_data->desktop_score;
			}
			if ( ! empty( $lighthouse_data->desktop_accessibility_score ) ) {
				$desktop_accessibility_score = $lighthouse_data->desktop_accessibility_score;
			}
			if ( ! empty( $lighthouse_data->desktop_best_practices_score ) ) {
				$desktop_best_practices_score = $lighthouse_data->desktop_best_practices_score;
			}
			if ( ! empty( $lighthouse_data->desktop_seo_score ) ) {
				$desktop_seo_score = $lighthouse_data->desktop_seo_score;
			}

			if ( ! empty( $lighthouse_data->desktop_lab_data ) ) {
				$desktop_lab_data = json_decode( $lighthouse_data->desktop_lab_data, true );
			}

			if ( ! empty( $lighthouse_data->mobile_score ) ) {
				$mobile_performance = $lighthouse_data->mobile_score;
			}
			if ( ! empty( $lighthouse_data->mobile_accessibility_score ) ) {
				$mobile_accessibility_score = $lighthouse_data->mobile_accessibility_score;
			}
			if ( ! empty( $lighthouse_data->mobile_best_practices_score ) ) {
				$mobile_best_practices_score = $lighthouse_data->mobile_best_practices_score;
			}
			if ( ! empty( $lighthouse_data->mobile_seo_score ) ) {
				$mobile_seo_score = $lighthouse_data->mobile_seo_score;
			}

			if ( ! empty( $lighthouse_data->mobile_lab_data ) ) {
				$mobile_lab_data = json_decode( $lighthouse_data->mobile_lab_data, true );
			}

			$strategy = MainWP_Lighthouse_Utility::get_instance()->get_option( 'strategy' );
			if ( $lighthouse_data->override ) {
				$strategy = $lighthouse_data->strategy;
			}

			if ( 'both' == $strategy || 'desktop' == $strategy ) {
				\WP_CLI::line( __( 'Desktop results: ', 'mainwp-lighthouse-extension' ) );
				\WP_CLI::line( __( 'Performance: ', 'mainwp-lighthouse-extension' ) . $desktop_performance );
				\WP_CLI::line( __( 'Accessibility: ', 'mainwp-lighthouse-extension' ) . $desktop_accessibility_score );
				\WP_CLI::line( __( 'Best practices: ', 'mainwp-lighthouse-extension' ) . $desktop_best_practices_score );
				\WP_CLI::line( __( 'Seo: ', 'mainwp-lighthouse-extension' ) . $desktop_seo_score );
				if ( $desktop_lab_data ) {
					foreach ( $desktop_lab_data as $item ) {
						\WP_CLI::line( $item['title'] . ' ' . esc_html( $item['displayValue'] ) );
					}
				}
			}

			if ( 'both' == $strategy || 'mobile' == $strategy ) {
				\WP_CLI::line( __( 'Mobile results: ', 'mainwp-lighthouse-extension' ) );
				\WP_CLI::line( __( 'Performance: ', 'mainwp-lighthouse-extension' ) . $mobile_performance );
				\WP_CLI::line( __( 'Accessibility: ', 'mainwp-lighthouse-extension' ) . $mobile_accessibility_score );
				\WP_CLI::line( __( 'Best practices: ', 'mainwp-lighthouse-extension' ) . $mobile_best_practices_score );
				\WP_CLI::line( __( 'Seo: ', 'mainwp-lighthouse-extension' ) . $mobile_seo_score );
				if ( $mobile_lab_data ) {
					foreach ( $mobile_lab_data as $item ) {
						\WP_CLI::line( $item['title'] . ' ' . esc_html( $item['displayValue'] ) );
					}
				}
			}
		} else {
			\WP_CLI::line( __( 'Lighthouse report not found.', 'mainwp-lighthouse-extension' ) );
		}
	}

	/**
	 * REST API Reports
	 *
	 * Handles the REST API sites reports.
	 *
	 * @param array $websites Child sites.
	 *
	 * @return array $data Reports data.
	 */
	public static function handle_rest_api_sites_reports( $websites ) {
		$data = array();
		if ( ! empty( $websites ) ) {
			foreach ( $websites as $website ) {
				$website_id = $website['id'];

				$result = array();

				$lighthouse_data = MainWP_Lighthouse_DB::get_instance()->get_lighthouse_by( 'site_id', $website_id );

				$result['id']   = $website['id'];
				$result['name'] = $website['name'];
				$result['url']  = $website['url'];

				if ( ! empty( $lighthouse_data ) ) {

					$desktop_performance          = 'N/A';
					$desktop_accessibility_score  = 'N/A';
					$desktop_best_practices_score = 'N/A';
					$desktop_seo_score            = 'N/A';
					$desktop_lab_data             = false;

					$mobile_performance          = 'N/A';
					$mobile_accessibility_score  = 'N/A';
					$mobile_best_practices_score = 'N/A';
					$mobile_seo_score            = 'N/A';
					$mobile_lab_data             = false;

					if ( ! empty( $lighthouse_data->desktop_score ) ) {
						$desktop_performance = $lighthouse_data->desktop_score;
					}
					if ( ! empty( $lighthouse_data->desktop_accessibility_score ) ) {
						$desktop_accessibility_score = $lighthouse_data->desktop_accessibility_score;
					}
					if ( ! empty( $lighthouse_data->desktop_best_practices_score ) ) {
						$desktop_best_practices_score = $lighthouse_data->desktop_best_practices_score;
					}
					if ( ! empty( $lighthouse_data->desktop_seo_score ) ) {
						$desktop_seo_score = $lighthouse_data->desktop_seo_score;
					}

					if ( ! empty( $lighthouse_data->desktop_lab_data ) ) {
						$desktop_lab_data = json_decode( $lighthouse_data->desktop_lab_data, true );
					}

					if ( ! empty( $lighthouse_data->mobile_score ) ) {
						$mobile_performance = $lighthouse_data->mobile_score;
					}
					if ( ! empty( $lighthouse_data->mobile_accessibility_score ) ) {
						$mobile_accessibility_score = $lighthouse_data->mobile_accessibility_score;
					}
					if ( ! empty( $lighthouse_data->mobile_best_practices_score ) ) {
						$mobile_best_practices_score = $lighthouse_data->mobile_best_practices_score;
					}
					if ( ! empty( $lighthouse_data->mobile_seo_score ) ) {
						$mobile_seo_score = $lighthouse_data->mobile_seo_score;
					}

					if ( ! empty( $lighthouse_data->mobile_lab_data ) ) {
						$mobile_lab_data = json_decode( $lighthouse_data->mobile_lab_data, true );
					}

					$strategy = MainWP_Lighthouse_Utility::get_instance()->get_option( 'strategy' );
					if ( $lighthouse_data->override ) {
						$strategy = $lighthouse_data->strategy;
					}

					$result['strategy'] = $strategy;

					if ( 'both' == $strategy || 'desktop' == $strategy ) {
						$result['desktop_performance']          = $desktop_performance;
						$result['desktop_accessibility_score']  = $desktop_accessibility_score;
						$result['desktop_best_practices_score'] = $desktop_best_practices_score;
						$result['desktop_seo_score']            = $desktop_seo_score;

						if ( $desktop_lab_data ) {
							foreach ( $desktop_lab_data as $item ) {
								$result['desktop_lab_data'][] = array(
									'title'        => $item['title'],
									'displayValue' => $item['displayValue'],
								);                  }
						}
					}

					if ( 'both' == $strategy || 'mobile' == $strategy ) {

						$result['mobile_performance']          = $mobile_performance;
						$result['mobile_accessibility_score']  = $mobile_accessibility_score;
						$result['mobile_best_practices_score'] = $mobile_best_practices_score;
						$result['mobile_seo_score']            = $mobile_seo_score;

						if ( $mobile_lab_data ) {
							foreach ( $mobile_lab_data as $item ) {
								$result['mobile_lab_data'][] = array(
									'title'        => $item['title'],
									'displayValue' => $item['displayValue'],
								);                  }
						}
					}

					$data['resutls'][ $website['id'] ] = $result;
				}
			}
		} else {
			$data['error'] = __( 'Lighthouse report not found.', 'mainwp-lighthouse-extension' );
		}
		return $data;
	}

	/**
	 * Lighthouse Actions
	 *
	 * Triggers the Lighthouse actions.
	 */
	public function do_lighthouse_actions() {
		$action_message = '';
		if ( isset( $_GET['page'] ) && ( 'Extensions-Mainwp-Lighthouse-Extension' == $_GET['page'] ) ) {
			$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : '';
			switch ( $action ) {
				case 'abort-scan':
					$action_message = $this->abort_scan();
					break;
			}
			if ( $action_message ) {
				do_action( 'mainwp_lighthouse_update_option', 'action_message', $action_message );
				wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce', 'action', 'nonce_action' ), stripslashes( $_SERVER['REQUEST_URI'] ) ) );
				exit;
			}
		}
	}

	/**
	 * Abort Audit
	 *
	 * Aborts currently running audit.
	 *
	 * @return string Feedback message.
	 */
	private function abort_scan() {
		if ( ! isset( $_GET['nonce_action'] ) || ! wp_verify_nonce( sanitize_text_field( $_GET['nonce_action'] ), 'lighthouse_nonce' ) ) {
			return false;
		}

		 add_option( 'mainwp_lighthouse_abort_scan', true, '', false );

		return __( 'Audit abort request received. Please allow a moment for the in-progress page report to complete before the abort request can take effect.', 'mainwp-lighthouse-extension' );
	}

	/**
	 * Widgets screen options.
	 *
	 * @param array $input Input.
	 *
	 * @return array $input Input.
	 */
	public function widgets_screen_options( $input ) {
		$input['advanced-lighthouse-widget'] = __( 'Lighthouse', 'mainwp-lighthouse-extension' );
		return $input;
	}

	/**
	 * Method screen_options()
	 *
	 * Create Screen Options button.
	 *
	 * @param mixed $input Screen options button HTML.
	 *
	 * @return mixed Screen sptions button.
	 */
	public function screen_options( $input ) {
		if ( isset( $_GET['page'] ) && 'Extensions-Mainwp-Lighthouse-Extension' == $_GET['page'] ) {
			if ( ! isset( $_GET['tab'] ) || 'dashboard' == $_GET['tab'] ) {
						$input .= '<a class="ui button basic icon" onclick="mainwp_lighthouse_sites_screen_options(); return false;" data-inverted="" data-position="bottom right" href="#" target="_blank" data-tooltip="' . esc_html__( 'Screen Options', 'mainwp' ) . '">
					<i class="cog icon"></i>
				</a>';
			}
		}
		return $input;
	}

	/**
	 * Method handle_sites_screen_settings()
	 *
	 * Handle sites screen settings
	 */
	public function handle_sites_screen_settings() {
		if ( isset( $_POST['wp_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['wp_nonce'] ), 'LighthouseSitesScrOptions' ) ) {
			$show_cols = array();
			foreach ( array_map( 'sanitize_text_field', wp_unslash( $_POST ) ) as $key => $val ) {
				if ( false !== strpos( $key, 'mainwp_show_column_' ) ) {
					$col               = str_replace( 'mainwp_show_column_', '', $key );
					$show_cols[ $col ] = 1;
				}
			}
			if ( isset( $_POST['show_columns_name'] ) ) {
				foreach ( array_map( 'sanitize_text_field', wp_unslash( $_POST['show_columns_name'] ) ) as $col ) {
					if ( ! isset( $show_cols[ $col ] ) ) {
						$show_cols[ $col ] = 0; // uncheck, hide columns.
					}
				}
			}
			$user = wp_get_current_user();
			if ( $user ) {
				update_user_option( $user->ID, 'mainwp_settings_show_lighthouse_sites_columns', $show_cols, true );
			}
		}
	}

	/**
	 * Hooks the section help content to the Help Sidebar element.
	 */
	public static function mainwp_help_content() {
		if ( isset( $_GET['page'] ) && ( 'Extensions-Mainwp-Lighthouse-Extension' === $_GET['page'] ) ) {
			?>
			<p><?php esc_html_e( 'If you need help with the Lighthouse extension, please review following help documents', 'mainwp' ); ?></p>
			<div class="ui relaxed bulleted list">
				<div class="item"><a href="https://kb.mainwp.com/docs/mainwp-lighthouse-extension/" target="_blank">How to use the Lighthouse Extension</a></div>
				<div class="item"><a href="https://kb.mainwp.com/docs/get-the-google-pagespeed-insights-api-key/" target="_blank">Get the Google PageSpeed Insights API Key</a></div>
				<?php
				/**
				 * Action: mainwp_lighthouse_help_item
				 *
				 * Fires at the bottom of the help articles list in the Help sidebar on the Themes page.
				 *
				 * Suggested HTML markup:
				 *
				 * <div class="item"><a href="Your custom URL">Your custom text</a></div>
				 *
				 * @since 4.1
				 */
				do_action( 'mainwp_lighthouse_help_item' );
				?>
			</div>
			<?php
		}
	}
}
