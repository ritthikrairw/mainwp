<?php
class Mainwp_Page_Speed {

	public static $instance = null;
	protected $option_handle = 'mainwp_pagespeed_options';
	protected $option;

	static function get_instance() {
		if ( null == Mainwp_Page_Speed::$instance ) {
			Mainwp_Page_Speed::$instance = new Mainwp_Page_Speed();
		}
		return Mainwp_Page_Speed::$instance;
	}

	public function __construct() {
		$this->option = get_option( $this->option_handle );
    add_filter( 'mainwp_pagespeed_get_data', array( &$this, 'pagespeed_get_data' ), 10, 4 );
	}

	public function init() {
		add_action( 'wp_ajax_mainwp_pagespeed_performsavepagespeedsettings', array( $this, 'ajax_save_page_speed_settings' ) );
		add_action( 'wp_ajax_mainwp_pagespeed_save_ext_setting', array( $this, 'ajax_save_ext_setting' ) );
		add_filter( 'mainwp_sitestable_getcolumns', array( $this, 'manage_sites_column' ), 10 );
		add_filter( 'mainwp_sitestable_item', array( $this, 'manage_sites_item' ), 10 );
        add_filter( 'mainwp_sync_others_data', array( $this, 'sync_others_data' ), 10, 2 );
		add_action( 'mainwp_site_synced', array( &$this, 'site_synced' ), 10, 2 );
	}

  public static function render_tabs() {

    $current_site_id = null;
		$website = null;

    if ( isset( $_GET['id'] ) && ! empty( $_GET['id'] ) ) {
      $current_site_id = $_GET['id'];
			global $mainWPPageSpeedExtensionActivator;

			$option = array(
        'plugin_upgrades' => true,
				'plugins' => true,
			);

			$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainWPPageSpeedExtensionActivator->get_child_file(), $mainWPPageSpeedExtensionActivator->get_child_key(), array( $current_site_id ), array(), $option );

			if ( is_array( $dbwebsites ) && ! empty( $dbwebsites ) ) {
				$website = current( $dbwebsites );
			}
		}

		if ( $current_site_id ) {
			$error = '';
			if ( empty( $website ) || empty( $website->id ) ) {
				$error = __( 'Undefined site id. Please, try again.', 'mainwp-pagespeed-extension' );
			} else {
				$activated = false;
				if ( $website && $website->plugins != '' ) {
					$plugins = json_decode( $website->plugins, 1 );
					if ( is_array( $plugins ) && count( $plugins ) > 0 ) {
						foreach ( $plugins as $plugin ) {
							if ( ( 'google-pagespeed-insights/google-pagespeed-insights.php' == $plugin['slug'] ) ) {
								if ( $plugin['active'] ) {
									$activated = true; }
								break;
							}
						}
					}
				}
				if ( ! $activated ) {
					$error = __( 'Google Pagespeed Insights plugin not detected on the child site.', 'mainwp-pagespeed-extension' );
				}
			}

      do_action( 'mainwp_pageheader_sites', 'PageSpeed' );
      if ( ! empty( $error ) ) {
				echo '<div class="ui segment">';
				echo '<div class="ui yellow message">' . $error . '</div>';
				echo '</div>';
			} else {
        self::gen_tabs_individual();
      }
      do_action( 'mainwp_pagefooter_sites', 'PageSpeed' );
		} else {
      self::gen_tabs_general();
    }
	}


  public static function is_managesites_page() {
		if ( isset( $_GET['page'] ) && ( 'ManageSitesPageSpeed' == $_GET['page'] ) ) {
			return true;
		}
		return false;
	}

	public function init_cron() {
		add_action( 'mainwp_pagespeed_cron_alert', array( 'Mainwp_Page_Speed', 'pagespeed_cron_alert' ) );
		$useWPCron = ( get_option( 'mainwp_wp_cron' ) === false ) || ( get_option( 'mainwp_wp_cron' ) == 1 );
		if ( ( $sched = wp_next_scheduled( 'mainwp_pagespeed_cron_alert' ) ) == false ) {
			if ( $useWPCron ) {
				wp_schedule_event( time(), 'daily', 'mainwp_pagespeed_cron_alert' );
			}
		} else {
			if ( ! $useWPCron ) {
				wp_unschedule_event( $sched, 'mainwp_pagespeed_cron_alert' );
      }
		}
	}

  function pagespeed_get_data( $input, $site_id ) {
		if ( empty( $site_id ) ) {
      return $input;
    }
		$data = MainWP_Page_Speed_DB::get_instance()->get_page_speed_by( 'site_id', $site_id );
		if ( is_object( $data ) ) {
			$input['pagespeed.average.desktop'] = intval( $data->score_desktop );
			$input['pagespeed.average.mobile'] = intval( $data->score_mobile );
		}
		return $input;
	}

	public function manage_sites_column( $columns ) {
		$columns['score_desktop'] = __( 'Score (Desktop)', 'mainwp-pagespeed-extension' );
		$columns['score_mobile'] = __( 'Score (Mobile)', 'mainwp-pagespeed-extension' );
		return $columns;
	}

	public function manage_sites_item( $item ) {
		$ps = MainWP_Page_Speed_DB::get_instance()->get_page_speed_by( 'site_id', $item['id'] );

		if ( ! empty( $ps ) && $ps->status ) {
			$strategy = $strategy_child = Mainwp_Page_Speed::get_instance()->get_option( 'strategy' );
			$strategy_child = $strategy;
			if ( $ps->override == 1 ) {
				$strategy_child = $ps->strategy;
			}


			if ( 'both' == $strategy || 'desktop' == $strategy ) {
				if ( 'mobile' == $strategy_child ) {
					$item['score_desktop'] = '<div class="ui progress" style="margin-bottom: 0;">
						<div class="bar">
							<div class="progress">' . __( 'Disabled', 'mainwp-pagespeed-extension' ) . '</div>
						</div>
					</div>';
				} else {
					$item['score_desktop'] = '<div class="ui ' . MainWP_Page_Speed_Dashboard::get_score_color( $ps->score_desktop ) .  ' progress" data-total="100" data-value="' . $ps->score_desktop . '" style="margin-bottom: 0;">
						<div class="bar">
							<div class="progress">' . $ps->score_desktop . '</div>
						</div>
					</div>';
				}
			}

			if ( 'both' == $strategy || 'mobile' == $strategy ) {
				if ( 'desktop' == $strategy_child ) {
					$item['score_mobile'] = '<div class="ui progress" style="margin-bottom: 0;">
						<div class="bar">
							<div class="progress">' . __( 'Disabled', 'mainwp-pagespeed-extension' ) . '</div>
						</div>
					</div>';
				} else {
					$item['score_mobile'] = '<div class="ui ' . MainWP_Page_Speed_Dashboard::get_score_color( $ps->score_mobile ) .  ' progress" data-total="100" data-value="' . $ps->score_mobile . '" style="margin-bottom: 0;">
						<div class="bar">
							<div class="progress">' . $ps->score_mobile . '</div>
						</div>
					</div>';
				}
			}

			if ( ! isset( $item['score_desktop'] ) ) {
				$item['score_desktop'] = 'N/A';
            }

			if ( ! isset( $item['score_mobile'] ) ) {
				$item['score_mobile'] = 'N/A';
            }

		} else {
			$item['score_desktop'] = $item['score_mobile'] = 'N/A';
		}
		return $item;
	}

  public function sync_others_data( $data, $pWebsite = null ) {
		if ( ! is_array( $data ) ) {
			$data = array();
        }
            $data['syncPageSpeedData'] = 1;
            return $data;
        }

        public function site_synced( $website, $information = array() ) {
        $update = array(
          'site_id' => $website->id
        );

        if ( is_array( $information ) && isset( $information['syncPageSpeedData'] ) && is_array( $information['syncPageSpeedData'] ) && isset( $information['syncPageSpeedData']['data'] ) ) {
          $data = $information['syncPageSpeedData']['data'];
          $update['score_desktop'] = isset( $data['desktop_score'] ) ? intval( $data['desktop_score'] ) : 0;
          $update['desktop_total_pages'] = isset( $data['desktop_total_pages'] ) ? intval( $data['desktop_total_pages'] ) : 0;
          $update['desktop_last_checked'] = isset( $data['desktop_last_modified'] ) ? intval( $data['desktop_last_modified'] ) : 0;
          $update['score_mobile'] = isset( $data['mobile_score'] ) ? intval( $data['mobile_score'] ) : 0;
          $update['mobile_total_pages'] = isset( $data['mobile_total_pages'] ) ? intval( $data['mobile_total_pages'] ) : 0;
          $update['mobile_last_checked'] = isset( $data['mobile_last_modified'] ) ? intval( $data['mobile_last_modified'] ) : 0;
        }

        if ( $website && $website->plugins != '' ) {
          $status = 0;
          $plugins = json_decode( $website->plugins, 1 );
          if ( is_array( $plugins ) && count( $plugins ) != 0 ) {
            foreach ( $plugins as $plugin ) {
              if ( 'google-pagespeed-insights/google-pagespeed-insights.php' == $plugin['slug'] ) {
                if ( $plugin['active'] ) {
                  $status = 1;
                }
                break;
              }
            }
          }
          $update['status'] = $status;
        }

        MainWP_Page_Speed_DB::get_instance()->update_page_speed( $update );
	}

	public function pagespeed_sync_data( $website ) {
		$pagespeed = MainWP_Page_Speed_DB::get_instance()->get_page_speed_by( 'site_id', $website['id'] );
		$post_data = array( 'mwp_action' => 'sync_data' );
		global $mainWPPageSpeedExtensionActivator;
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPPageSpeedExtensionActivator->get_child_file(), $mainWPPageSpeedExtensionActivator->get_child_key(), $website['id'], 'page_speed', $post_data );
		if ( is_array( $information ) ) {
			if ( (isset( $information['result'] ) && 'RUNNING' == $information['result']) ) {
				$update = array(
                    'id' => ( $pagespeed && $pagespeed->id ) ? $pagespeed->id : 0,
					'site_id' => $website['id'],
				);
				$update['score_desktop'] = 0;
				$update['desktop_total_pages'] = 0;
				$update['desktop_last_checked'] = 0;
				$update['score_mobile'] = 0;
				$update['mobile_total_pages'] = 0;
				$update['mobile_last_checked'] = 0;
				MainWP_Page_Speed_DB::get_instance()->update_page_speed( $update );
			} else if ( isset( $information['data'] ) && is_array( $information['data'] ) ) {
				$data = $information['data'];
				//print_r($data);
				if ( ! empty( $data ) ) {
					$update = array(
                        'id' => ($pagespeed && $pagespeed->id) ? $pagespeed->id : 0,
						'bad_api_key' => isset( $data['bad_api_key'] ) && $data['bad_api_key'] ? 1 : 0,
						'site_id' => $website['id'],
					);
					$update['score_desktop'] = isset( $data['desktop_score'] ) ? intval( $data['desktop_score'] ) : 0;
					$update['desktop_total_pages'] = isset( $data['desktop_total_pages'] ) ? intval( $data['desktop_total_pages'] ) : 0;
					$update['desktop_last_checked'] = isset( $data['desktop_last_modified'] ) ? intval( $data['desktop_last_modified'] ) : 0;
					$update['score_mobile'] = isset( $data['mobile_score'] ) ? intval( $data['mobile_score'] ) : 0;
					$update['mobile_total_pages'] = isset( $data['mobile_total_pages'] ) ? intval( $data['mobile_total_pages'] ) : 0;
					$update['mobile_last_checked'] = isset( $data['mobile_last_modified'] ) ? intval( $data['mobile_last_modified'] ) : 0;
					MainWP_Page_Speed_DB::get_instance()->update_page_speed( $update );
				}
			}
		}
	}

	public static function pagespeed_cron_alert() {
		$option = Mainwp_Page_Speed::get_instance()->option;
		$pagespeeds = MainWP_Page_Speed_DB::get_instance()->get_page_speed_by( 'all' );
		if ( is_array( $pagespeeds ) ) {
			$email = @apply_filters( 'mainwp_getnotificationemail', false);
			if ( empty( $email ) ) {
				return false;
            }
			foreach ( $pagespeeds as $value ) {
				$settings = unserialize( $value->settings );
				$settings = is_array( $settings ) ? $settings : array();

				if ( $value->override_noti ) {
					$alert_score = isset( $settings['score_noti'] ) ? $settings['score_noti'] : 0;
					$schedule_noti = isset( $settings['schedule_noti'] ) ? $settings['schedule_noti'] : 0;
				} else {
					$alert_score = isset( $option['score_noti'] ) ? $option['score_noti'] : 0;
					$schedule_noti = isset( $option['schedule_noti'] ) ? $option['schedule_noti'] : 0;
				}

				if ( ! empty( $settings ) && ! empty( $alert_score ) && ! empty( $schedule_noti ) ) {
					$score = 0;
					if ( $value->override ) {
						$type = $value->strategy;
					} else {
						$type = Mainwp_Page_Speed::get_instance()->get_option( 'strategy' );
					}

					if ( 'desktop' == $type ) {
						$score = $value->score_desktop; } else if ( 'mobile' == $type ) {
						$score = $value->score_mobile; } else if ( 'both' == $type ) {
							$score = $value->score_desktop;
							if ( $score > $value->score_mobile ) {
								$score = $value->score_mobile; }
						}

						$last_alert = $value->last_alert;

						if ( $score <= $alert_score && $last_alert + $schedule_noti * 24 * 3600 < time() ) {
							self::send_alert_mail( $value, $email, 'MainWP - Page Speed Notification', $type );
						}
				}
			}
		}
	}

	public static function pagespeed_cron_sync_data() {
		global $mainWPPageSpeedExtensionActivator;
        $others = array(
            'plugins_slug' => 'google-pagespeed-insights/google-pagespeed-insights.php'
        );
		$websites = apply_filters( 'mainwp_getsites', $mainWPPageSpeedExtensionActivator->get_child_file(), $mainWPPageSpeedExtensionActivator->get_child_key(), null, false, $others );
		foreach ( $websites as $website ) {
			Mainwp_Page_Speed::get_instance()->pagespeed_sync_data( $website );
		}
	}

	public static function send_alert_mail( $pagespeed, $email = '', $subject = '', $type ) {
		if ( empty( $pagespeed ) || empty( $email ) ) {
			return false;
    }
		if ( empty( $pagespeed->site_id ) ) {
			return false;
    }

    $site_id = $pagespeed->site_id;

    global $mainWPPageSpeedExtensionActivator;
		$website = apply_filters( 'mainwp_getsites', $mainWPPageSpeedExtensionActivator->get_child_file(), $mainWPPageSpeedExtensionActivator->get_child_key(), $site_id );
		if ( $website && is_array( $website ) ) {
			$website = current( $website );
		}

		if ( ! $website ) {
			return;
    }

		$site_name = $website['name'];
		$from = 'From: ' . get_option( 'admin_email' );

		if ( 'desktop' == $type ) {
			$score_text = $pagespeed->score_desktop . ' / 100 (Desktop)';
		} else if ( 'mobile' == $type ) {
			$score_text = $pagespeed->score_mobile . ' / 100 (Mobile)';
		} else if ( 'both' == $type ) {
			$score_text = $pagespeed->score_desktop . ' / 100 (Desktop), ' . $pagespeed->score_mobile . ' / 100 (Mobile)';
		}

		$content = 'Hello MainWP User,<br><br>' .
				'Your child site <a href="' . $website['url'] . '">' . $site_name . '</a>  page speed score is ' . $score_text . '.<br><br>' .
				'MainWP<br>' .
				'<a href="www.MainWP.com">www.MainWP.com</a><br><br>';
			if ( wp_mail( $email, stripslashes( $subject ), $content, array( $from, 'content-type: text/html' ) ) ) {
				$update = array( 'site_id' => $site_id, 'last_alert' => time() );
				MainWP_Page_Speed_DB::get_instance()->update_page_speed( $update );
				return true;
			}
			return false;
	}

	public static function render_metabox() {
		if ( ! isset( $_GET['page'] ) || 'managesites' == $_GET['page'] ) {
			self::individual_metabox();
		} else {
			self::global_metabox();
		}
	}

	public static function global_metabox() {
		$pagespeeds = MainWP_Page_Speed_DB::get_instance()->get_page_speed_by( 'all' );

		$pagespeeds = is_array( $pagespeeds ) ? $pagespeeds : array();

		$strategy = Mainwp_Page_Speed::get_instance()->get_option( 'strategy' );

		$count = 0;
		$desktop_total_score = $mobile_total_score = $desktop_avg_score = $mobile_avg_score = 0;

		foreach ( $pagespeeds as $pagespeed ) {
            if (empty( $pagespeed->status))
                continue;
			if ( ( 0 == $pagespeed->override || 'both' == $pagespeed->strategy || 'desktop' == $pagespeed->strategy ) && $pagespeed->score_desktop > 0 ) {
				$desktop_total_score += $pagespeed->score_desktop;
				$count++;
			}
		}

		if ( $count > 0 ) {
			$desktop_avg_score = number_format( $desktop_total_score / $count, 1 );
		}

		$count = 0;

		foreach ( $pagespeeds as $pagespeed ) {
            if (empty( $pagespeed->status))
                continue;
			if ( ( 0 == $pagespeed->override || 'both' == $pagespeed->strategy || 'mobile' == $pagespeed->strategy ) && $pagespeed->score_mobile > 0 ) {
				$mobile_total_score += $pagespeed->score_mobile;
				$count++;
			}
		}

		if ( $count > 0 ) {
			$mobile_avg_score = number_format( $mobile_total_score / $count, 1 );
		}

		$desktop_color = MainWP_Page_Speed_Dashboard::get_score_color( $desktop_avg_score );
		$mobile_color = MainWP_Page_Speed_Dashboard::get_score_color( $mobile_avg_score );

		?>
		<div class="ui grid">
			<div class="twelve wide column">
				<h3 class="ui header handle-drag">
				<?php echo __( 'Page Speed', 'mainwp-pagespeed-extension' ); ?>
				<div class="sub header"><?php echo __( 'Average Page Speed for all child site', 'mainwp-pagespeed-extension' ); ?></div>
				</h3>
			</div>
			<div class="four wide column right aligned"></div>
		</div>
		<div class="ui hidden divider"></div>
		<div class="ui two column grid">
			<div class="center aligned column">
				<?php if ( 'both' == $strategy || 'desktop' == $strategy ) : ?>
				<div class="ui huge <?php echo $desktop_color; ?> statistic">
				  <div class="value">
				    <i class="tachometer alternate icon"></i> <?php echo $desktop_avg_score; ?>
				  </div>
				  <div class="label">
				    <?php echo __( 'Desktop', 'mainwp-pagespeed-extension' ); ?>
				  </div>
				</div>
				<?php endif; ?>
			</div>
			<div class="center aligned  column">
				<?php if ( 'both' == $strategy || 'mobile' == $strategy ) : ?>
				<div class="ui huge <?php echo $mobile_color; ?> statistic">
				  <div class="value">
				    <i class="tachometer alternate icon"></i> <?php echo $mobile_avg_score; ?>
				  </div>
				  <div class="label">
				    <?php echo __( 'Mobile', 'mainwp-pagespeed-extension' ); ?>
				  </div>
				</div>
				<?php endif; ?>
			</div>
		</div>
		<div class="ui hidden divider"></div>
		<div class="ui center aligned segment">
			<a href="admin.php?page=Extensions-Mainwp-Page-Speed-Extension" class="ui big green button"><?php _e( 'Page Speed Dashboard', 'mainwp-pagespeed-extension' ); ?></a>
		</div>
		<?php
	}

	public static function individual_metabox() {
		$site_id = isset( $_GET['dashboard'] ) ? $_GET['dashboard'] : 0;

		if ( empty( $site_id ) ) {
			return;
		}

		$ps = MainWP_Page_Speed_DB::get_instance()->get_page_speed_by( 'site_id', $site_id );

		if ( is_object( $ps ) && $ps->override ) {
			$strategy = $ps->strategy;
		} else {
			$strategy = Mainwp_Page_Speed::get_instance()->get_option( 'strategy' );
		}

		$desktop_score = ! empty( $ps ) ? $ps->score_desktop : 0;
		$mobile_score = ! empty( $ps ) ? $ps->score_mobile : 0;
		$desktop_color = MainWP_Page_Speed_Dashboard::get_score_color( $desktop_score );
		$mobile_color = MainWP_Page_Speed_Dashboard::get_score_color( $mobile_score );
		?>
		<div class="ui grid">
			<div class="twelve wide column">
				<h3 class="ui header handle-drag">
				<?php echo __( 'Page Speed', 'mainwp-pagespeed-extension' ); ?>
				<div class="sub header"><?php echo __( 'Average Page Speed for the child site', 'mainwp-pagespeed-extension' ); ?></div>
				</h3>
			</div>
			<div class="four wide column right aligned"></div>
		</div>
		<div class="ui two column grid">
			<div class="center aligned column">
				<?php if ( 'both' == $strategy || 'desktop' == $strategy ) : ?>
				<div class="ui huge <?php echo $desktop_color; ?> statistic">
				  <div class="value">
				    <i class="tachometer alternate icon"></i> <?php echo $desktop_score; ?>
				  </div>
				  <div class="label">
				    <?php echo __( 'Desktop', 'mainwp-pagespeed-extension' ); ?>
				  </div>
				</div>
				<?php endif; ?>
			</div>
			<div class="center aligned  column">
				<?php if ( 'both' == $strategy || 'mobile' == $strategy ) : ?>
				<div class="ui huge <?php echo $mobile_color; ?> statistic">
				  <div class="value">
				    <i class="tachometer alternate icon"></i> <?php echo $mobile_score; ?>
				  </div>
				  <div class="label">
				    <?php echo __( 'Mobile', 'mainwp-pagespeed-extension' ); ?>
				  </div>
				</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
		$detail_location = 'tools.php?page=google-pagespeed-insights&render=summary';
		$detail_link = '<a href="admin.php?page=SiteOpen&newWindow=yes&websiteid=' . $site_id . '&location=' . base64_encode( $detail_location ) . '&_opennonce=' . wp_create_nonce( 'mainwp-admin-nonce' ) . '" target="_blank" class="button mainwp-upgrade-button">' . __( 'See Details' ) . '</a>';
		?>
		<div class="ui center aligned segment">
			<a href="admin.php?page=Extensions-Mainwp-Page-Speed-Extension" class="ui big green button"><?php _e( 'Page Speed Dashboard', 'mainwp-pagespeed-extension' ); ?></a>
		</div>
		<?php
	}

  public static function get_current_site($site_id) {
    global $mainWPPageSpeedExtensionActivator;
    $dbwebsites = apply_filters( 'mainwp_getdbsites', $mainWPPageSpeedExtensionActivator->get_child_file(), $mainWPPageSpeedExtensionActivator->get_child_key(), array($site_id), array() );
    $current_site = false;
    if ( count( $dbwebsites ) > 0 ) {
      $current_site = current( $dbwebsites );
    }
    return $current_site;
  }

  public static function gen_tabs_individual() {
    $current_site_id = $_GET['id'];
    self::handle_individual_settings_post( $current_site_id );
    ?>
		<div class="ui alt segment">
			<div class="mainwp-main-content">
				<form method="post" action="admin.php?page=ManageSitesPageSpeed&id=<?php echo intval( $current_site_id ); ?>" class="ui form">
					<?php self::gen_pagespeed_settings_box(); ?>
					<?php self::gen_google_pagespeed_settings_box(); ?>
					<div class="ui divider"></div>
					<input type="submit" name="submit" id="submit" class="ui big green right floated button" value="<?php _e( 'Save Settings', 'mainwp-pagespeed-extension' ); ?>">
				</form>
			</div>
			<div class="mainwp-side-content">
				<p><?php echo __( 'The MainWP Page Speed Extension seamlessly integrates with the Google Pagespeed Insights for WordPress plugin enabling you to monitor your child site performances directly from your MainWP Dashboard.', 'mainwp-pagespeed-extension' ); ?></p>
				<p><?php echo __( 'The extension shows you the average speed score for each of your child sites by calculating the average load time of your Pages, Posts, and Categories and showing you tips and tricks to increase that score.', 'mainwp-pagespeed-extension' ); ?></p>
				<p><?php echo __( 'The Speed monitor will be visible on both your Sites list and in your Extension settings page utilizing a widget that is added to your Main and Individual sites Dashboards ensuring you access to the information.', 'mainwp-pagespeed-extension' ); ?></p>
				<p class="ui info message"><?php echo sprintf( __( 'If you are having issues with the Google Pagespeed Insights plugin, help documentation can be %sfound here%s.', 'mainwp-pagespeed-extension' ), '<a href="https://mattkeys.me/documentation/google-pagespeed-insights/" target="_blank">', '</a>' ); ?></p>
				<a class="ui green big fluid button" target="_blank" href="https://mainwp.com/help/docs/pagespeed-extension/"><?php echo __( 'Extension Documentation', 'mainwp-pagespeed-extension' ); ?></a>
			</div>
			<div class="ui clearing hidden divider"></div>
		</div>
    <?php
  }

  public static function gen_tabs_general() {

        if ( self::handle_general_settings_post() ) {
          return;
        }

        if ( isset( $_GET['action'] ) && $_GET['action'] == 'start_reporting' && isset( $_GET['_psnonce'] ) && wp_verify_nonce( $_GET['_psnonce'], '_psnonce' )) {
          return self::load_child_sites_to_prepare( 'start_reporting' );
        }

        $curent_tab = 'dashboard';

        if ( isset( $_GET['tab'] ) ) {
            if ( 'settings' == $_GET['tab'] ) {
                  $curent_tab = 'settings';
            }
        } else if ( isset( $_POST['mwp_pagespeed_setting_plugin_submit'] ) ) {
            $curent_tab = 'settings';
        }

		global $mainWPPageSpeedExtensionActivator;

        $others = array(
            'plugins_slug' => 'google-pagespeed-insights/google-pagespeed-insights.php'
        );
		$websites = apply_filters( 'mainwp_getsites', $mainWPPageSpeedExtensionActivator->get_child_file(), $mainWPPageSpeedExtensionActivator->get_child_key(), null, false, $others );

        $sites_ids = array();
		if ( is_array( $websites ) ) {
			foreach ( $websites as $website ) {
				$sites_ids[] = $website['id'];
			}
		}

		$option = array(
            'plugin_upgrades' => true,
			'plugins' => true,
		);

		$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainWPPageSpeedExtensionActivator->get_child_file(), $mainWPPageSpeedExtensionActivator->get_child_key(), $sites_ids, array(), $option );
		$all_pagespeed_sites = $sites_with_pagespeed = array();

		foreach ( $dbwebsites as $website ) {
			if ( $website && $website->plugins != '' ) {
				$plugins = json_decode( $website->plugins, 1 );
				if ( is_array( $plugins ) && 0 != count( $plugins ) ) {
					foreach ( $plugins as $plugin ) {
						if ( 'google-pagespeed-insights/google-pagespeed-insights.php' == $plugin['slug'] || strpos( $plugin['slug'], '/google-pagespeed-insights.php' ) !== false ) {
							if ( $plugin['active'] ) {
								$all_pagespeed_sites[] = MainWP_Page_Speed_Utility::map_site( $website, array( 'id', 'name' ) );
								$sites_with_pagespeed[] = $website->id;
								break;
							}
						}
					}
				}
			}
		}

		$selected_group = 0;

		if ( isset( $_POST['mainwp_pagespeed_groups_select'] ) ) {
			$selected_group = intval( $_POST['mainwp_pagespeed_groups_select'] );
		}

		$pagespeed_data = array();
		$results = MainWP_Page_Speed_DB::get_instance()->get_page_speed_by( 'all' );

		foreach ( $results as $value ) {
			if ( ! empty( $value->site_id ) ) {
				$pagespeed_data[ $value->site_id ] = MainWP_Page_Speed_Utility::map_site( $value, array( 'bad_api_key', 'desktop_last_checked', 'mobile_last_checked', 'desktop_total_pages', 'mobile_total_pages', 'score_desktop', 'score_mobile', 'strategy', 'hide_plugin', 'override' ) );
			}
		}

		$dbwebsites_pagespeed = MainWP_Page_Speed_Dashboard::get_instance()->get_websites_pagespeed( $dbwebsites, $selected_group, $pagespeed_data );

		unset( $dbwebsites );
		?>

		<div class="ui labeled icon inverted menu mainwp-sub-submenu" id="mainwp-pagespeed-menu">
			<a href="admin.php?page=Extensions-Mainwp-Page-Speed-Extension&tab=dashboard" class="item <?php echo ( $curent_tab == 'dashboard' ? 'active' : '' ); ?>"><i class="tasks icon"></i> <?php _e( 'Dashboard', 'mainwp-pagespeed-extension' ); ?></a>
			<a href="admin.php?page=Extensions-Mainwp-Page-Speed-Extension&tab=settings" class="item <?php echo ( $curent_tab == 'settings' ? 'active' : '' ); ?>"><i class="cog icon"></i> <?php _e( 'Page Speed Settings', 'mainwp-pagespeed-extension' ); ?></a>
		</div>
		<?php if ( $curent_tab == 'dashboard' || $curent_tab == '' ) : ?>
			<div id="mainwp-pagespeed-dashboard-tab">
				<?php MainWP_Page_Speed_Dashboard::render_actions_bar(); ?>
				<div class="ui segment">
					<div class="ui message" id="mainwp-message-zone" style="display:none"></div>
					<?php MainWP_Page_Speed_Dashboard::gen_dashboard_tab( $dbwebsites_pagespeed ); ?>
				</div>
			</div>
		<?php endif; ?>
		<?php if ( $curent_tab == 'settings' ) : ?>
			<div class="ui alt segment" id="mainwp-pagespeed-settings-tab">
				<div class="mainwp-main-content">
					<form id="mainwp-pagespeed-settings-form" method="post" action="admin.php?page=Extensions-Mainwp-Page-Speed-Extension&tab=settings" class="ui form">
						<?php self::gen_google_pagespeed_settings_box(); ?>
					</form>
				</div>
				<div class="mainwp-side-content">
					<p><?php echo __( 'The MainWP Page Speed Extension seamlessly integrates with the Google Pagespeed Insights for WordPress plugin enabling you to monitor your child site performances directly from your MainWP Dashboard.', 'mainwp-pagespeed-extension' ); ?></p>
					<p><?php echo __( 'The extension shows you the average speed score for each of your child sites by calculating the average load time of your Pages, Posts, and Categories and showing you tips and tricks to increase that score.', 'mainwp-pagespeed-extension' ); ?></p>
					<p><?php echo __( 'The Speed monitor will be visible on both your Sites list and in your Extension settings page utilizing a widget that is added to your Main and Individual sites Dashboards ensuring you access to the information.', 'mainwp-pagespeed-extension' ); ?></p>
					<p class="ui info message"><?php echo sprintf( __( 'If you are having issues with the Google Pagespeed Insights plugin, help documentation can be %sfound here%s.', 'mainwp-pagespeed-extension' ), '<a href="https://mattkeys.me/documentation/google-pagespeed-insights/" target="_blank">', '</a>' ); ?></p>
					<a class="ui green big fluid button" target="_blank" href="https://mainwp.com/help/docs/pagespeed-extension/"><?php echo __( 'Extension Documentation', 'mainwp-pagespeed-extension' ); ?></a>
				</div>
				<div class="ui clearing hidden divider"></div>
			</div>
		<?php endif; ?>
		<?php
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

  public static function gen_pagespeed_settings_box() {
    $current_site_id = 0;
    if ( self::is_managesites_page() ) {
      $current_site_id = $_GET['id'];
    }
		if ( $current_site_id ) {
			$site_settings = MainWP_Page_Speed_DB::get_instance()->get_page_speed_by( 'site_id', $current_site_id );
		} else {
			$alertPagespeed = self::get_instance()->get_option( 'score_noti' );
			$alertSchedule = self::get_instance()->get_option( 'schedule_noti' );
		}

		$override = $override_noti = 0;

		if ( $current_site_id && $site_settings ) {
			$settings = unserialize( $site_settings->settings );
			if ( is_array( $settings ) ) {
				$alertPagespeed = $settings['score_noti'];
				$alertSchedule = $settings['schedule_noti'];
			}
			$override = $site_settings->override;
			$override_noti = $site_settings->override_noti;
		}

		$score_noti_values = array(
			100	 => 100,
			90	 => 90,
			80	 => 80,
			70	 => 70,
			60	 => 60,
			50	 => 50,
			40	 => 40,
			30	 => 30,
			20	 => 20,
			10	 => 10,
		);

		$schedule_noti_values = array(
      1  => __( 'Once a Day', 'mainwp-pagespeed-extension' ),
			7  => __( 'Once a Week', 'mainwp-pagespeed-extension' ),
			30 => __( 'Once a Month', 'mainwp-pagespeed-extension' ),
		);
    ?>

		<div class="ui message" id="mainwp-message-zone" style="display:none"></div>

		<h3 class="header"><?php echo __( 'MainWP Pagespeed settings', 'mainwp-pagespeed-extension' ); ?></h3>

		<?php if ( $current_site_id ) : ?>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Overwrite general settings', 'mainwp-pagespeed-extension' ); ?></label>
			<div class="ten wide column ui toggle checkbox">
				<input type="checkbox" name="mainwp_pagespeed_setting_site_override" id="mainwp_pagespeed_setting_site_override" value="1" <?php echo ( 0 == $override ? '' : 'checked="checked"' ); ?>><label></label>
			</div>
		</div>
		<?php endif; ?>

		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Google response language', 'mainwp-pagespeed-extension' ); ?></label>
			<div class="ten wide column">
				<select id="mainwp_pagespeed_score_noti" name="mainwp_pagespeed_score_noti" class="ui dropdown">
					<?php foreach ( $score_noti_values as $key => $value ) : ?>
					<?php
						echo '<option value="" >' . __( 'Off', 'mainwp-pagespeed-extension' ) . '</option>';
						$_select = '';
						if ( $key == $alertPagespeed ) {
							$_select = ' selected ';
						}
						echo '<option value="' . $key . '" ' . $_select . '>' . $value . '</option>';
					?>
					<?php endforeach; ?>
				</select>
			</div>
		</div>

		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Google response language', 'mainwp-pagespeed-extension' ); ?></label>
			<div class="ten wide column">
				<select id="mainwp_pagespeed_score_noti" name="mainwp_pagespeed_score_noti" class="ui dropdown">
					<?php foreach ( $schedule_noti_values as $key => $value ) : ?>
					<?php
						echo '<option value="" >' . __( 'Off', 'mainwp-pagespeed-extension' ) . '</option>';
						$_select = '';
						if ( $key == $alertSchedule ) {
							$_select = ' selected ';
						}
						echo '<option value="' . $key . '" ' . $_select . '>' . $value . '</option>';
					?>
					<?php endforeach; ?>
				</select>
			</div>
		</div>

		<?php if ( ! $current_site_id ) : ?>
			<div class="ui divider"></div>
			<input type="button" value="<?php _e( 'Save Settings', 'mainwp-pagespeed-extension' ); ?>" class="ui green big right floated button" id="mainwp-pagespeed-save-individual-settings-button">
		<?php endif; ?>
    <?php
	}

  public static function gen_google_pagespeed_settings_box() {
    $current_site_id = 0;
    if ( self::is_managesites_page() ) {
      $current_site_id = $_GET['id'];
    }

  	$apiKey = '';
    $delayTime = 0;
    $logException = 0;
    $deleteData = '';
		$use_schedule = 0;
		$store_screenshots = 0;

    if ( $current_site_id ) {
      $site_settings = MainWP_Page_Speed_DB::get_instance()->get_page_speed_by( 'site_id', $current_site_id );
    } else {
      $apiKey 						= self::get_instance()->get_option( 'api_key' );
      $responseLanguage 	= self::get_instance()->get_option( 'response_language' );
      $strategy 					= self::get_instance()->get_option( 'strategy' );
      $store_screenshots  = self::get_instance()->get_option( 'store_screenshots' );
      $use_schedule 			= self::get_instance()->get_option( 'use_schedule' );
      $reportExp 					= self::get_instance()->get_option( 'report_expiration' );
      $checkReport 				= self::get_instance()->get_option( 'check_report' );
      $maxExecutionTime 	= self::get_instance()->get_option( 'max_execution_time' );
      $maxRuntime 				= self::get_instance()->get_option( 'max_run_time' );
      $heartbeatRate  		= self::get_instance()->get_option( 'heartbeat' );
      $delayTime 					= self::get_instance()->get_option( 'delay_time' );
      $logException 			= self::get_instance()->get_option( 'log_exception' );
      $deleteData 				= self::get_instance()->get_option( 'delete_data' );
    }

    $override = $override_noti = 0;

    if ( $current_site_id && $site_settings ) {
      $settings = unserialize( $site_settings->settings );
      if ( is_array( $settings ) ) {
        $apiKey = $settings['api_key'];
        $responseLanguage = $settings['response_language'];
        $reportExp = $settings['report_expiration'];
        $checkReport = $settings['check_report'];
        $maxExecutionTime = $settings['max_execution_time'];
        $maxRuntime = $settings['max_run_time'];
        $heartbeatRate  = $settings['heartbeat'];
        $delayTime = $settings['delay_time'];
        $logException = $settings['log_exception'];
        $deleteData = $settings['delete_data'];
        $store_screenshots = $settings['store_screenshots'];
        $use_schedule = $settings['use_schedule'];
      }
      $strategy = $site_settings->strategy;
      $override = $site_settings->override;
      $override_noti = $site_settings->override_noti;
    }

    $responseLanguage = empty( $responseLanguage ) ? 'en_US' : $responseLanguage;
    $strategy = empty( $strategy ) ? 'desktop' : $strategy;
    $reportExp = empty( $reportExp ) ? 86400 : $reportExp;
    $checkReport = empty( $checkReport ) ? array( 'page', 'post', 'category' ) : $checkReport;
    $checkReport = is_array( $checkReport ) ? $checkReport : array();
    $maxExecutionTime = empty( $maxExecutionTime ) ? 300 : $maxExecutionTime;
    $maxRuntime = empty( $maxRuntime ) ? 0 : $maxRuntime;
    $heartbeatRate  = empty($heartbeatRate) ? 'fast' : $heartbeatRate;
    $scanTechnical = empty( $scanTechnical ) ? 'wp_cron' : $scanTechnical;

    $language = array(
      'ar' 			=> 'Arabic',
      'bg' 			=> 'Bulgarian',
      'ca' 			=> 'Catalan',
      'zh_TW' 	=> 'Traditional Chinese (Taiwan)',
      'zh_CN' 	=> 'Simplified Chinese',
      'hr' 			=> 'Croatian',
      'cs' 			=> 'Czech',
      'da' 			=> 'Danish',
      'nl' 			=> 'Dutch',
      'en_US' 	=> 'English',
      'en_GB' 	=> 'English UK',
      'fil' 		=> 'Filipino',
      'fi' 			=> 'Finnish',
      'fr' 			=> 'French',
      'de' 			=> 'German',
      'el' 			=> 'Greek',
      'iw' 			=> 'Hebrew',
      'hi' 			=> 'Hindi',
      'hu' 			=> 'Hungarian',
      'id' 			=> 'Indonesian',
			'it' 			=> 'Italian',
      'ja' 			=> 'Japanese',
      'ko' 			=> 'Korean',
      'lv' 			=> 'Latvian',
      'lt' 			=> 'Lithuanian',
      'no' 			=> 'Norwegian',
      'pl' 			=> 'Polish',
      'pt_BR' 	=> 'Portuguese (Brazilian)',
      'pt_PT' 	=> 'Portuguese (Portugal)',
      'ro' 			=> 'Romanian',
      'ru' 			=> 'Russian',
      'sr' 			=> 'Serbian',
      'sk' 			=> 'Slovakian',
      'sl' 			=> 'Slovenian',
      'es' 			=> 'Spanish',
      'sv' 			=> 'Swedish',
      'th' 			=> 'Thai',
      'tr' 			=> 'Turkish',
      'uk' 			=> 'Ukrainian',
      'vi' 			=> 'Vietnamese',
    );

    $strategy_values = array(
      'desktop' => __( 'Desktop', 'mainwp-pagespeed-extension' ),
      'mobile' 	=> __( 'Mobile', 'mainwp-pagespeed-extension' ),
      'both' 		=> __( 'Both', 'mainwp-pagespeed-extension' ),
    );

    $report_expiration = array(
      86400 	=> __( '1 Day', 'mainwp-pagespeed-extension' ),
      604800 	=> __( '7 Day', 'mainwp-pagespeed-extension' ),
      1296000 => __( '15 Day', 'mainwp-pagespeed-extension' ),
      2592000 => __( '30 Day', 'mainwp-pagespeed-extension' ),
    );

    $excution_time = array(
      60 		=> __( '1 Minute', 'mainwp-pagespeed-extension' ),
      300 	=> __( '5 Minutes', 'mainwp-pagespeed-extension' ),
      600 	=> __( '10 Minutes', 'mainwp-pagespeed-extension' ),
      900 	=> __( '15 Minutes', 'mainwp-pagespeed-extension' ),
      1800 	=> __( '30 Minutes', 'mainwp-pagespeed-extension' ),
    );

    $max_run_time = array(
      0 	=> __( 'No Limit', 'mainwp-pagespeed-extension' ),
      60 	=> __( '60 Seconds', 'mainwp-pagespeed-extension' ),
      90 	=> __( '90 Seconds', 'mainwp-pagespeed-extension' ),
      120 => __( '120 Seconds', 'mainwp-pagespeed-extension' ),
      150 => __( '150 Seconds', 'mainwp-pagespeed-extension' ),
      180 => __( '180 Seconds', 'mainwp-pagespeed-extension' ),
    );

    $delay_time = array(
      0  => __( '0 Seconds', 'mainwp-pagespeed-extension' ),
      1  => __( '1 Seconds', 'mainwp-pagespeed-extension' ),
      2  => __( '2 Seconds', 'mainwp-pagespeed-extension' ),
      3  => __( '3 Seconds', 'mainwp-pagespeed-extension' ),
      4  => __( '4 Seconds', 'mainwp-pagespeed-extension' ),
      5  => __( '5 Seconds', 'mainwp-pagespeed-extension' ),
      10 => __( '10 Seconds', 'mainwp-pagespeed-extension' ),
    );

    $heartbeat_rate = array(
      'fast' 			=> __( 'Fast', 'mainwp-pagespeed-extension' ),
      'standard'  => __( 'Standard', 'mainwp-pagespeed-extension' ),
      'slow'  		=> __( 'Slow', 'mainwp-pagespeed-extension' ),
      'disabled'  => __( 'Disabled - manually refresh pages to update status', 'mainwp-pagespeed-extension' )
    );

    $delete_data = array(
      'purge_reports' 		=> __( 'Delete Reports Only', 'mainwp-pagespeed-extension' ),
      'purge_everything'  => __( 'Delete EVERYTHING', 'mainwp-pagespeed-extension' ),
    );

    ?>

		<?php if ( $current_site_id ) : ?>
			<?php if ( get_option( 'mainwp_pagespeed_setting_need_to_update_site' ) && $current_site_id ) : ?>
				<?php delete_option( 'mainwp_pagespeed_setting_need_to_update_site' ); ?>
				<script>
					jQuery( document ).ready( function ( $ ) {
						mainwp_pagespeed_update_individual_site( <?php echo $current_site_id; ?> );
					} );
				</script>
			<?php endif; ?>
		<?php endif; ?>

		<div class="ui hidden divider"></div>
		<h3 class="header"><?php echo __( 'Google Pagespeed options', 'mainwp-pagespeed-extension' ); ?></h3>

		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Google API key', 'mainwp-pagespeed-extension' ); ?></label>
		  <div class="ten wide column">
				<input type="text" name="mainwp_pagespeed_plugin_api_key" id="mainwp_pagespeed_plugin_api_key" value="<?php echo stripslashes( $apiKey ); ?>">
			</div>
		</div>

		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Google response language', 'mainwp-pagespeed-extension' ); ?></label>
		  <div class="ten wide column">
				<select id="mainwp_pagespeed_response_language" name="mainwp_pagespeed_response_language" class="ui dropdown">
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
			<label class="six wide column middle aligned"><?php _e( 'Report type', 'mainwp-pagespeed-extension' ); ?></label>
		  <div class="ten wide column">
				<select id="mainwp_pagespeed_strategy" name="mainwp_pagespeed_strategy" class="ui dropdown">
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
			<label class="six wide column middle aligned"><?php _e( 'Store page load screenshots:', 'mainwp-pagespeed-extension' ); ?></label>
		  <div class="ten wide column">
				<select id="mainwp_pagespeed_store_screenshots" name="mainwp_pagespeed_store_screenshots" class="ui dropdown">
					<option value="0" <?php selected( $store_screenshots, 0 ); ?>><?php _e( 'No', 'mainwp-pagespeed-extension' ); ?></option>
					<option value="1" <?php selected( $store_screenshots, 1 ); ?>><?php _e( 'Yes', 'mainwp-pagespeed-extension' ); ?></option>
				</select>
			</div>
		</div>

		<h3 class="header"><?php echo __( 'Scheduling and URL configuration', 'mainwp-pagespeed-extension' ); ?></h3>
		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Automatically re-check Pagespeed Insights scores using a schedule', 'mainwp-pagespeed-extension' ); ?></label>
		  <div class="ten wide column ui toggle checkbox">
				<input type="checkbox" name="mainwp_pagespeed_use_schedule" id="mainwp_pagespeed_use_schedule" value="1" <?php checked( $use_schedule ); ?>><label></label>
			</div>
		</div>

		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Report expiration / Recheck interval', 'mainwp-pagespeed-extension' ); ?></label>
		  <div class="ten wide column">
				<select id="mainwp_pagespeed_report_expiration" name="mainwp_pagespeed_report_expiration" class="ui dropdown">
					<?php foreach ( $report_expiration as $key => $exp ) : ?>
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

		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Types of URLs to be checked ', 'mainwp-pagespeed-extension' ); ?></label>
			<div class="ten wide column ui checkbox">
	  		<div class="ui checkbox"><input type="checkbox" name="mainwp_pagespeed_report_check[]" id="mainwp_pagespeed_report_check_page" value="page" <?php echo in_array( 'page', $checkReport ) ? ' checked ' : ''; ?>> <label for="mainwp_pagespeed_report_check_page"><?php _e( 'Check Wordpress Pages', 'mainwp-page-speed-extension' ); ?></label></div><br />
				<div class="ui checkbox"><input type="checkbox" name="mainwp_pagespeed_report_check[]" id="mainwp_pagespeed_report_check_post" value="post" <?php echo in_array( 'post', $checkReport ) ? ' checked ' : ''; ?>> <label for="mainwp_pagespeed_report_check_post"><?php _e( 'Check Wordpress Posts', 'mainwp-page-speed-extension' ); ?></label></div><br />
				<div class="ui checkbox"><input type="checkbox" name="mainwp_pagespeed_report_check[]" id="mainwp_pagespeed_report_check_category" value="category" <?php echo in_array( 'category', $checkReport ) ? ' checked ' : ''; ?>> <label for="mainwp_pagespeed_report_check_category"><?php _e( 'Check Category Indexes', 'mainwp-page-speed-extension' ); ?></label></div><br />
				<div class="ui checkbox"><input type="checkbox" name="mainwp_pagespeed_report_check[]" id="mainwp_pagespeed_report_check_custom_urls" value="custom_urls" <?php echo in_array( 'custom_urls', $checkReport ) ? ' checked ' : ''; ?>> <label for="mainwp_pagespeed_report_check_custom_urls"><?php _e( 'Check Custom URLs', 'mainwp-page-speed-extension' ); ?></label></div>
			</div>
		</div>

		<h3 class="header"><?php echo __( 'Advanced configuration', 'mainwp-pagespeed-extension' ); ?></h3>

		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Maximum execution time', 'mainwp-pagespeed-extension' ); ?></label>
		  <div class="ten wide column">
				<select id="mainwp_pagespeed_max_execution_time" name="mainwp_pagespeed_max_execution_time" class="ui dropdown">
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
			<label class="six wide column middle aligned"><?php _e( 'Maximum script run time', 'mainwp-pagespeed-extension' ); ?></label>
		  <div class="ten wide column">
				<select id="mainwp_pagespeed_max_run_time" name="mainwp_pagespeed_max_run_time" class="ui dropdown">
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
			<label class="six wide column middle aligned"><?php _e( 'Report throttling delay time', 'mainwp-pagespeed-extension' ); ?></label>
		  <div class="ten wide column">
				<select id="mainwp_pagespeed_delay_time" name="mainwp_pagespeed_delay_time" class="ui dropdown">
					<?php foreach ( $delay_time as $key => $time ) : ?>
					<?php
						$_select = '';
						if ( $key == $delayTime ) {
							$_select = ' selected ';
						}
						echo '<option value="' . $key . '" ' . $_select . '>' . $time . '</option>';
					?>
					<?php endforeach; ?>
				</select>
			</div>
		</div>

		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Report status indicator refresh rate', 'mainwp-pagespeed-extension' ); ?></label>
		  <div class="ten wide column">
				<select id="mainwp_pagespeed_heartbeat" name="mainwp_pagespeed_heartbeat" class="ui dropdown">
					<?php foreach ( $heartbeat_rate as $key => $title ) : ?>
					<?php
						$_select = '';
						if ( $key == $heartbeatRate ) {
							$_select = ' selected ';
						}
						echo '<option value="' . $key . '" ' . $_select . '>' . $title . '</option>';
					?>
					<?php endforeach; ?>
				</select>
			</div>
		</div>

		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Log API exceptions', 'mainwp-pagespeed-extension' ); ?></label>
		  <div class="ten wide column ui toggle checkbox">
				<input type="checkbox" name="mainwp_pagespeed_log_api_exception" id="mainwp_pagespeed_log_api_exception" value="1" <?php echo $logException ? ' checked ' : ''; ?>><label></label>
			</div>
		</div>

		<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Delete data', 'mainwp-pagespeed-extension' ); ?></label>
		  <div class="ten wide column">
				<select id="mainwp_pagespeed_delete_data" name="mainwp_pagespeed_delete_data" class="ui dropdown">
					<?php foreach ( $delete_data as $key => $del ) : ?>
						<option value=""><?php _e( 'Do Nothing', 'mainwp-page-speed-extension' ); ?></option>
						<?php
						echo '<option value="' . $key . '" ' . $_select . '>' . $del . '</option>';
						?>
					<?php endforeach; ?>
				</select>
			</div>
		</div>

		<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'mwp_pagespeed_nonce' ); ?>">
		<input type="hidden" name="mwp_pagespeed_setting_plugin_submit" value="1">
		<?php if ( ! $current_site_id ) : ?>
		<div class="ui divider"></div>
        <input type="submit" value="<?php _e( 'Save Settings', 'mainwp-pagespeed-extension' ); ?>" class="ui green big right floated button" id="mainwp-pagespeed-save-settings-button">
		<a href="admin.php?page=Extensions-Mainwp-Page-Speed-Extension&tab=settings&action=start_reporting&_psnonce=<?php echo wp_create_nonce('_psnonce');?>" id="mainwp-pagespeed-start-scan-button" class="ui big green basic button"><?php _e( 'Start Reporting', 'mainwp-pagespeed-extension' ); ?></a>
		<a href="admin.php?page=Extensions-Mainwp-Page-Speed-Extension&tab=settings&action=start_reporting&recheck_all_pages=1&_psnonce=<?php echo wp_create_nonce('_psnonce');?>" id="mainwp-pagespeed-recheck-all-button" class="ui big button"><?php _e( 'Recheck All Pages', 'mainwp-pagespeed-extension' ); ?></a>
		<?php endif; ?>
  <?php
  }

	public static function handle_individual_settings_post( $websiteId ) {
		if ( isset( $_POST['submit'] ) && $websiteId ) {
			self::handle_settings_post( $websiteId );
		}
	}

  public static function handle_general_settings_post() {
    $save_output = self::handle_settings_post();
		if ( false !== $save_output ) {
			return self::load_child_sites_to_prepare( 'save_options' );
		}
    return false;
  }

  public static function handle_settings_post( $website_id = null ) {
		if ( isset( $_POST['mwp_pagespeed_setting_plugin_submit'] ) ) {
			self::check_security();
		}

		if ( isset( $_POST['mwp_pagespeed_setting_plugin_submit'] ) || $website_id ) {

			$current_pagespeed_settings = array();

			if ( $website_id ) {
				$site_settings = MainWP_Page_Speed_DB::get_instance()->get_page_speed_by( 'site_id', $website_id );
				if ( is_object( $site_settings ) ) {
					$current_pagespeed_settings = unserialize( $site_settings->settings );
        }
			}
			$output = array();

			$api_key = '';
			if ( isset( $_POST['mainwp_pagespeed_plugin_api_key'] ) ) {
				$api_key = sanitize_text_field( $_POST['mainwp_pagespeed_plugin_api_key'] );
			}

			$response_language = 'en_US';
			if ( isset( $_POST['mainwp_pagespeed_response_language'] ) ) {
				$response_language = $_POST['mainwp_pagespeed_response_language'];
			}

			$strategy = 'desktop';
			if ( isset( $_POST['mainwp_pagespeed_strategy'] ) ) {
				$strategy = $_POST['mainwp_pagespeed_strategy'];
			}

      $store_screenshots = 0;
			if ( isset( $_POST['mainwp_pagespeed_store_screenshots'] ) ) {
				$store_screenshots = $_POST['mainwp_pagespeed_store_screenshots'];
			}

      $use_schedule = 0;
			if ( isset( $_POST['mainwp_pagespeed_use_schedule'] ) ) {
				$use_schedule = $_POST['mainwp_pagespeed_use_schedule'];
			}

			$report_expiration = 86400;
			if ( isset( $_POST['mainwp_pagespeed_report_expiration'] ) ) {
				$report_expiration = $_POST['mainwp_pagespeed_report_expiration'];
			}

			$report_check = array();
			if ( isset( $_POST['mainwp_pagespeed_report_check'] ) ) {
				$report_check = $_POST['mainwp_pagespeed_report_check'];
			}

			$exec_time = 300;
			if ( isset( $_POST['mainwp_pagespeed_max_execution_time'] ) ) {
				$exec_time = $_POST['mainwp_pagespeed_max_execution_time'];
			}

      $run_time = 0;
			if ( isset( $_POST['mainwp_pagespeed_max_run_time'] ) ) {
				$run_time = intval( $_POST['mainwp_pagespeed_max_run_time'] );
			}

      $heartbeat_rate = 'fast';
			if ( isset( $_POST['mainwp_pagespeed_heartbeat'] ) ) {
				$heartbeat_rate = $_POST['mainwp_pagespeed_heartbeat'];
			}

			$delay_time = 0;
			if ( isset( $_POST['mainwp_pagespeed_delay_time'] ) ) {
				$delay_time = $_POST['mainwp_pagespeed_delay_time'];
			}

			$log_api_exception = 0;
			if ( isset( $_POST['mainwp_pagespeed_log_api_exception'] ) ) {
				$log_api_exception = $_POST['mainwp_pagespeed_log_api_exception'];
			}

			$delete_data = '';
			if ( isset( $_POST['mainwp_pagespeed_delete_data'] ) ) {
				$delete_data = $_POST['mainwp_pagespeed_delete_data'];
			}

			if ( isset( $_POST['mwp_pagespeed_setting_plugin_submit'] ) && !$website_id ) {
				self::get_instance()->set_option( 'api_key', $api_key );
				self::get_instance()->set_option( 'response_language', $response_language );
				self::get_instance()->set_option( 'strategy', $strategy );
        self::get_instance()->set_option( 'store_screenshots', $store_screenshots );
        self::get_instance()->set_option( 'use_schedule', $use_schedule );
				self::get_instance()->set_option( 'report_expiration', $report_expiration );
				self::get_instance()->set_option( 'check_report', $report_check );
				self::get_instance()->set_option( 'max_execution_time', $exec_time );
        self::get_instance()->set_option( 'max_run_time', $run_time );
        self::get_instance()->set_option( 'heartbeat', $heartbeat_rate );
				self::get_instance()->set_option( 'delay_time', $delay_time );
				self::get_instance()->set_option( 'log_exception', $log_api_exception );
				self::get_instance()->set_option( 'delete_data', $delete_data );
				if ( ! empty( $delete_data ) ) {
					MainWP_Page_Speed_DB::get_instance()->empty_page_speeds_score();
				}
			} else if ( $website_id ) {
				$override 		 = isset( $_POST['mainwp_pagespeed_setting_site_override'] ) ? 1 : 0;
				$override_noti = isset( $_POST['mainwp_pagespeed_setting_site_override_noti'] ) ? 1 : 0;

				$_alert_pagespeed = '';
				if ( isset( $_POST['mainwp_pagespeed_score_noti'] ) ) {
					$_alert_pagespeed = $_POST['mainwp_pagespeed_score_noti'];
				}

				$_schedule_noti = '';
				if ( isset( $_POST['mainwp_pagespeed_schedule_noti'] ) ) {
					$_schedule_noti = $_POST['mainwp_pagespeed_schedule_noti'];
				}

				$settings = array(
          'api_key' 					 => $api_key,
					'response_language'  => $response_language,
					'report_expiration'  => $report_expiration,
					'check_report' 			 => $report_check,
					'max_execution_time' => $exec_time,
          'max_run_time' 			 => $run_time,
          'heartbeat' 				 => $heartbeat_rate,
					'delay_time' 				 => $delay_time,
					'log_exception' 		 => $log_api_exception,
					'delete_data' 			 => $delete_data,
					'score_noti' 				 => $_alert_pagespeed,
					'schedule_noti' 		 => $_schedule_noti,
          'store_screenshots'  => $store_screenshots,
          'use_schedule' 			 => $use_schedule
				);

				$_site_pagespeed = array();
				$_site_pagespeed['settings'] = serialize( $settings );
				$_site_pagespeed['override'] = $override;
				$_site_pagespeed['override_noti'] = $override_noti;
				$_site_pagespeed['strategy'] = $strategy;
				$_site_pagespeed['site_id'] = $website_id;

				$out = MainWP_Page_Speed_DB::get_instance()->update_page_speed( $_site_pagespeed );

				update_option( 'mainwp_pagespeed_setting_need_to_update_site', 1 );

				if ( ! empty( $delete_data ) ) {
					MainWP_Page_Speed_DB::get_instance()->empty_page_speeds_score( $website_id );
				}
			}
			return $output;
		}
		return false;
	}

	public static function check_security() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), 'mwp_pagespeed_nonce' ) ) {
			die( __( 'Invalid request. Please, try again.', 'mainwp-pagespeed-extension' ) );
		}
	}

	public function ajax_save_page_speed_settings( $siteid ) {
		self::check_security();

		$siteid = $_POST['siteId'];

		if ( empty( $siteid ) ) {
			die( json_encode( array( 'error' => __( 'Invalid request. Please, try again.', 'mainwp-pagespeed-extension' ) ) ) );
		}

		$information = $this->perform_save_settings( $siteid );

		die( json_encode( $information ) );
	}

	function mainwp_apply_plugin_settings( $siteid ) {
		$information = $this->perform_save_settings( $siteid, true );
		$result = array();
		if ( is_array( $information ) ) {
			if ( 'SUCCESS' == $information['result'] || 'NOTCHANGE' == $information['result'] ) {
				$result = array( 'result' => 'success' );
			} else if ( $information['error'] ) {
				$result = array( 'error' => $information['error'] );
			} else {
				$result = array( 'result' => 'failed' );
			}
		} else {
			$result = array( 'result' => 'failed' );
		}
		die( json_encode( $result ) );
	}

	public function perform_save_settings( $siteid, $forced_global_setting = false ) {

		global $mainWPPageSpeedExtensionActivator;

		$website = apply_filters( 'mainwp_getsites', $mainWPPageSpeedExtensionActivator->get_child_file(), $mainWPPageSpeedExtensionActivator->get_child_key(), $siteid );
		if ( $website && is_array( $website ) ) {
			$website = current( $website );
		}

		if ( ! is_array( $website ) ) {
			return array( 'error' => __( 'Invalid request. Please, try again.', 'mainwp-pagespeed-extension' ) );
		}

    if ( $forced_global_setting ) {
            $settings = $this->option;
                  $strategy = $this->get_option( 'strategy' );

                  if ( empty( $settings ) ) {
              return array( 'error' => __( 'Empty settings. Please, try again.', 'mainwp-pagespeed-extension' ) );
              }

            $post_data = array( 'mwp_action' => 'save_settings' );
            $post_data['strategy'] = $strategy;
            $post_data['settings'] = base64_encode( serialize( $settings ) );

    } else {

            $individual = ( isset( $_POST['individual'] ) && $_POST['individual'] ) ? true : false;
            $pagespeed = MainWP_Page_Speed_DB::get_instance()->get_page_speed_by( 'site_id', $siteid );

            if ( $individual ) {
              if ( !$pagespeed || !$pagespeed->override ) {
                  return array( 'error' => __( 'Settings could not be saved. Please, set the Overwrite General settings to Yes and try again.', 'mainwp-pagespeed-extension' ) );
              }
            } else { // general update
              if ( $pagespeed && $pagespeed->override ) {
                return array( 'error' => __( 'Settings could not be saved. Please, indivdual site settings are in use. Please, set the Overwrite General settings to No and try again.', 'mainwp-pagespeed-extension' ) );
              }
            }

            if ( $individual ) {
              $settings = unserialize( $pagespeed->settings );
              $strategy = $pagespeed->strategy;
            } else {
              $settings = $this->option;
              $strategy = $this->get_option( 'strategy' );
            }

            $post_data = array( 'mwp_action' => 'save_settings' );
            $post_data['strategy'] = $strategy;
            $post_data['settings'] = base64_encode( serialize( $settings ) );
  	}

    $information = apply_filters( 'mainwp_fetchurlauthed', $mainWPPageSpeedExtensionActivator->get_child_file(), $mainWPPageSpeedExtensionActivator->get_child_key(), $siteid, 'page_speed', $post_data );

		if ( isset( $information['data'] ) && is_array( $information['data'] ) ) {
			$data = $information['data'];
			if ( ! empty( $data ) ) {
				$update = array(
					'bad_api_key' => isset( $data['bad_api_key'] ) && $data['bad_api_key'] ? 1 : 0,
					'site_id' => $siteid,
				);

				if ( 'both' == $strategy || 'desktop' == $strategy ) {
					$update['score_desktop'] = isset( $data['desktop_score'] ) ? intval( $data['desktop_score'] ) : 0;
					$update['desktop_total_pages'] = isset( $data['desktop_total_pages'] ) ? intval( $data['desktop_total_pages'] ) : 0;
					$update['desktop_last_checked'] = isset( $data['desktop_last_modified'] ) ? intval( $data['desktop_last_modified'] ) : 0;
				}

				if ( 'both' == $strategy || 'mobile' == $strategy ) {
					$update['score_mobile'] = isset( $data['mobile_score'] ) ? intval( $data['mobile_score'] ) : 0;
					$update['mobile_total_pages'] = isset( $data['mobile_total_pages'] ) ? intval( $data['mobile_total_pages'] ) : 0;
					$update['mobile_last_checked'] = isset( $data['mobile_last_modified'] ) ? intval( $data['mobile_last_modified'] ) : 0;
				}

				MainWP_Page_Speed_DB::get_instance()->update_page_speed( $update );
			}
		}
		//unset($information['data']);
		return $information;
	}

	public function ajax_save_ext_setting() {
		$score_noti = isset( $_POST['scoreNoti'] ) ? $_POST['scoreNoti'] : 0;
		$schedule_noti = isset( $_POST['scheduleNoti'] ) ? $_POST['scheduleNoti'] : 0;
		self::get_instance()->set_option( 'score_noti', $score_noti );
		self::get_instance()->set_option( 'schedule_noti', $schedule_noti );
		die( json_encode( 'SUCCESS' ) );
	}

	public static function load_child_sites_to_prepare( $doAction ) {
		global $mainWPPageSpeedExtensionActivator;

        $others = array(
            'plugins_slug' => 'google-pagespeed-insights/google-pagespeed-insights.php'
        );
		$websites = apply_filters( 'mainwp_getsites', $mainWPPageSpeedExtensionActivator->get_child_file(), $mainWPPageSpeedExtensionActivator->get_child_key(), null, false, $others );

		$sites_ids = array();

		if ( is_array( $websites ) ) {
			foreach ( $websites as $website ) {
				$sites_ids[] = $website['id'];
			}
		}

		$option = array(
            'plugin_upgrades' => true,
			'plugins' => true,
		);

		$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainWPPageSpeedExtensionActivator->get_child_file(), $mainWPPageSpeedExtensionActivator->get_child_key(), $sites_ids, array(), $option );
		$all_the_plugin_sites = array();

		foreach ( $dbwebsites as $website ) {
			if ( $website && $website->plugins != '' ) {
				$plugins = json_decode( $website->plugins, 1 );
				if ( is_array( $plugins ) && count( $plugins ) != 0 ) {
					foreach ( $plugins as $plugin ) {
						if ( 'google-pagespeed-insights/google-pagespeed-insights.php' == $plugin['slug'] ) {
							if ( $plugin['active'] ) {
								$all_the_plugin_sites[] = MainWP_Page_Speed_Utility::map_site( $website, array( 'id', 'name' ) );
								break;
							}
						}
					}
				}
			}
		}

    if ( $doAction == 'start_reporting' ) {
      if ( isset( $_GET['recheck_all_pages'] ) ) {
				$doAction = 'recheck_all_pages';
			} else {
				$doAction = 'check_new_pages';
			}
    }

    if ( count( $all_the_plugin_sites ) > 0 ) {
      ?>
			<div class="ui modal" id="mainwp-pagespeed-sync-modal">
				<div class="header"><?php _e( 'MainWP PageSpeed Sync', 'mainwp-pagespeed-extension' ); ?></div>
				<div class="scrolling content">
					<div class="ui relaxed divided list">
						<?php foreach ( $all_the_plugin_sites as $website ) : ?>
							<div class="item mainwpProccessSitesItem" siteid="<?php echo $website['id']; ?>" status="queue">
								<?php echo $website['name']; ?>
								<span class="right floated status"><i class="clock outline icon"></i></span>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="actions">
					<div class="ui cancel reload button"><?php _e( 'Close', 'mainwp-pagespeed-extension' ); ?></div>
				</div>
			</div>
            <script>
              jQuery( document ).ready( function ($) {
                  jQuery( '#mainwp-pagespeed-sync-modal' ).modal( 'show' );
                  mainwp_pagespeed_perform_action_start_next( '<?php echo $doAction; ?>' );
              } );
            </script>
      <?php
    	return true;
    } else {
      ?>
      <div class="ui yellow message"><?php _e( 'Google Pagespeed Insights plugin not detected on any of your child sites.', 'mainwp-pagespeed-extension' ); ?></div>
  		<?php
    	return false;
    }
	}
}
