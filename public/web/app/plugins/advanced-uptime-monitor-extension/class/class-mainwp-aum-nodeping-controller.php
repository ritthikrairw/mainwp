<?php
/**
 * MainWP NodePing Controller
 *
 * Controls the NodePing actions.
 *
 * @package MainWP/Extensions/AUM
 */

namespace MainWP\Extensions\AUM;

/**
 * Class MainWP_AUM_Main_Controller
 *
 * NodePing controller.
 */
class MainWP_AUM_Nodeping_Controller extends MainWP_AUM_Controller {

	/**
	 * Sevice name.
	 *
	 * @var $service_name.
	 */
	public $service_name = 'nodeping';

	/**
	 * Private static instance.
	 *
	 * @static
	 * @var $instance  MainWP_AUM_Main_Controller.
	 */
	private static $instance = null;

	/**
	 * Method instance()
	 *
	 * Get instance.
	 *
	 * @return object Instance.
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Method __construct()
	 *
	 * Contructor.
	 */
	public function __construct() {
	}

	/**
	 * Method: ajax_url_edit_monitor()
	 *
	 * Edits monitors via AJAX request.
	 */
	public function ajax_url_edit_monitor() {

		$this->ajax_check_permissions( 'monitors_page' );

		$this->set( 'url_saved', false );

		$contacts       = false;
		$contacts_items = MainWP_AUM_Nodeping_API::instance()->get_contacts();

		if ( ! empty( $contacts_items ) ) {
			$contacts_items = json_decode( $contacts_items, true );
			if ( is_array( $contacts_items ) ) {
				$cont = current( $contacts_items );
				if ( isset( $cont['addresses'] ) ) { // valid first item.
					$opts = array(
						'contacts' => $contacts_items,
					);
					MainWP_AUM_NodePing_API::instance()->update_options( $opts );
					$contacts = $contacts_items;
				}
			}
		}

		if ( empty( $contacts ) ) {
			$contacts = MainWP_AUM_Nodeping_API::instance()->get_option( 'contacts' );
		}

		if ( ! is_array( $contacts ) ) {
			$contacts = array();
		}

		$this->set( 'contacts', $contacts );

		$dependency_mos = array();
		$urls           = MainWP_AUM_DB::instance()->get_monitor_urls(
			$this->service_name,
			array(
				'default' => true,
				'fields'  => array(
					'dependency',
				),
			)
		);

		if ( ! empty( $urls ) ) {
			foreach ( $urls as $url ) {
				if ( 'active' !== $url->enable ) {
					continue;
				}

				$dependency_mos[ $url->monitor_id ] = $url->url_address;
			}
		}

		$dependency_mos = array_merge( array( 0 => __( 'None', 'advanced-uptime-monitor-extension' ) ), $dependency_mos );
		$this->set( 'dependency_nodeping_monitors', $dependency_mos );

		if ( ! empty( $this->params ) && ! empty( $this->params['data'][ $this->service_name ] ) ) {

			$data = $this->params['data'][ $this->service_name ];

			$update = array(
				'url_name'    => $data['url_name'],
				'url_address' => $data['url_address'],
			);

			$url_id = 0;
			$saved  = false;
			if ( ! isset( $data['url_id'] ) || empty( $data['url_id'] ) ) {
				$update['service'] = $this->service_name;
				$url_id            = MainWP_AUM_DB::instance()->insert_monitor_url( $update );
				$saved             = $url_id ? true : false;
			} else {
				$url_id  = $data['url_id'];
				$url_bak = MainWP_AUM_DB::instance()->get_monitor_by( 'url_id', $url_id, $this->service_name );
				$saved   = MainWP_AUM_DB::instance()->update_monitor_url( $update, $url_id );
			}

			if ( $saved ) {
				$notify_contacts = isset( $data['notify_contacts'] ) && is_array( $data['notify_contacts'] ) ? $data['notify_contacts'] : array();
				$opts_value      = array(
					'monitor_type'     => $data['monitor_type'], // http type.
					'monitor_interval' => $data['check_frequency'],
					'enable'           => isset( $data['enable'] ) && ! empty( $data['enable'] ) ? 'active' : 'inactive',
					'timeout'          => $data['timeout'],
					'sensitivity'      => ! empty( $data['sensitivity'] ) ? intval( $data['sensitivity'] ) : 2,
					'notify_contacts'  => wp_json_encode( $notify_contacts ),
					'description'      => $data['description'],
				);
				MainWP_AUM_DB::instance()->save_monitor_url_field_options_data( $url_id, $opts_value );

				$notifications = array();
				foreach ( $notify_contacts as $noti ) {
					$notifications[] = array(
						$noti['contact'] => array(
							'delay'    => $noti['delay'],
							'schedule' => $noti['schedule'],
						),
					);
				}

				try {

					$post_data = array(
						'label'         => trim( $data['url_name'] ),
						'type'          => ! empty( $data['monitor_type'] ) ? $data['monitor_type'] : 'HTTP',
						'enabled'       => isset( $data['enable'] ) && ! empty( $data['enable'] ) ? 'active' : 'inactive',
						'interval'      => ! empty( $data['check_frequency'] ) ? intval( $data['check_frequency'] ) : 5,
						'sens'          => ! empty( $data['sensitivity'] ) ? intval( $data['sensitivity'] ) : 2,
						'threshold'     => ! empty( $data['timeout'] ) ? intval( $data['timeout'] ) : 5,
						'description'   => ! empty( $data['description'] ) ? $data['description'] : '',
						'notifications' => $notifications,
						'dep'           => ! empty( $data['dependency'] ) ? $data['dependency'] : '',
						'runlocations'  => ! empty( $data['region'] ) ? $data['region'] : '',
						'homeloc'       => ! empty( $data['location'] ) ? $data['location'] : '',
					);

					if ( ! in_array( $post_data['type'], array( 'AGENT', 'DNS', 'PUSH', 'SPEC10DNS', 'SPEC10RDDS' ) ) ) {
						$post_data['target'] = trim( $data['url_address'] );
					}

					try {
						if ( ! isset( $data['url_id'] ) || empty( $data['url_id'] ) ) {
							$result = MainWP_AUM_Nodeping_API::instance()->api_edit_monitor( $post_data );
						} else {
							$monitor_url = MainWP_AUM_DB::instance()->get_monitor_by( 'url_id', $data['url_id'], $this->service_name );
							$result      = MainWP_AUM_Nodeping_API::instance()->api_edit_monitor( $post_data, $monitor_url->monitor_id );
						}
						$result = json_decode( $result, true );
					} catch ( \Exception $ex ) {
						// catch error.
					}

					if ( is_array( $result ) && isset( $result['_id'] ) ) {
						if ( ! isset( $data['url_id'] ) || empty( $data['url_id'] ) ) {
							$this->flash( 'green', __( 'Monitor created successfully!', 'advanced-uptime-monitor-extension' ) );
							do_action( 'mainwp_aum_monitor_created', $data );
						} else {
							$this->flash( 'green', __( 'Monitor updated successfully!', 'advanced-uptime-monitor-extension' ) );
						}

						$update_data = array(
							'monitor_id' => $result['_id'],
						);
						MainWP_AUM_DB::instance()->update_monitor_url( $update_data, $url_id );

						$this->set( 'url_saved', true );
					} else {
						if ( is_array( $result ) && isset( $result['error_code'] ) ) {
							$this->flash( 'red', $result['message'] );
						} else {
							$this->flash( 'red', __( 'Undefined error on the NodePing side. Please try again later.', 'advanced-uptime-monitor-extension' ) );
						}
						if ( ! isset( $data['url_id'] ) || empty( $data['url_id'] ) ) {
							MainWP_AUM_DB::instance()->delete_db_monitor( 'url_id', $url_id );
						} else {
							$update = array(
								'url_name'    => $url_bak->url_name,
								'url_address' => $url_bak->url_address,
							);
							MainWP_AUM_DB::instance()->update_monitor_url( $update, $url_id );
						}
					}
				} catch ( \Exception $ex ) {
					switch ( $ex->getCode() ) {
						case 1:
							echo esc_html( $ex->getMessage() );
							break;
						case 2:
							$this->flash( 'yellow', __( 'Please, specify the API key.', 'advanced-uptime-monitor-extension' ) );
							break;
						case 3:
							$this->flash( 'red', __( 'Undefined error occurred, please try again.', 'advanced-uptime-monitor-extension' ) );
							break;
						default:
							echo esc_html( $ex->getCode() . ': ' . $ex->getMessage() );
					}
				}
			} else {
				$this->flash( 'red', __( 'Undefined error occurred while saving the form. Please, try again.', 'advanced-uptime-monitor-extension' ) );
			}
		} else {

			$monitor_url = MainWP_AUM_DB::instance()->get_monitor_by( 'url_id', $this->params['url_id'], $this->service_name );
			$monitor_url = json_decode( wp_json_encode( $monitor_url ), true );

			if ( isset( $monitor_url['notify_contacts'] ) ) {
				$monitor_url['notify_contacts'] = json_decode( $monitor_url['notify_contacts'], true );
			}

			$this->params['data'][ $this->service_name ] = $monitor_url;
		}

		$this->render_view( 'nodeping_monitor' );
		die();
	}


	/**
	 * Method ajax_monitor_urls()
	 *
	 * Ajax handle.
	 */
	public function ajax_monitor_urls() {
		$this->ajax_check_permissions( 'monitor_urls' );
		$this->set_view_monitors();
		$this->render_view( 'nodeping_monitor_urls' );
		die();
	}

	/**
	 * Method: set_view_monitors()
	 *
	 * Sets Monitor view.
	 *
	 * @param int $site_id Child site ID.
	 */
	public function set_view_monitors( $site_id = null ) {

		$aum_code = MainWP_AUM_Nodeping_API::instance()->init_settings();
		if ( empty( $aum_code['api_token'] ) ) {
			$this->flash( 'yellow', __( 'Please, enter your NodePing API Token first', 'advanced-uptime-monitor-extension' ) );
		}

		if ( empty( $site_id ) ) {
			$found_unav_urls = $this->check_unavailable_url_monitors();
			if ( false === $found_unav_urls ) {
				MainWP_AUM_NodePing_Settings_Handle::get_instance()->reload_monitor_urls();
			}
		}

		global $mainwpAUMExtensionActivator;

		$conds = array();
		if ( $site_id ) {
			$website = apply_filters( 'mainwp_getsites', $mainwpAUMExtensionActivator->get_child_file(), $mainwpAUMExtensionActivator->get_child_key(), $site_id );
			if ( $website && is_array( $website ) ) {
				$website = current( $website );
			}
			if ( empty( $website ) || ! isset( $website['url'] ) ) {
				$this->flash( 'red', __( 'Site data could not be loaded. Please, try again.', 'advanced-uptime-monitor-extension' ) );
				return false;
			} else {
				$conds['url_address'] = $website['url']; // pass string value only.
			}
		}

		$get_page = isset( $_REQUEST['get_page'] ) && $_REQUEST['get_page'] > 0 ? $_REQUEST['get_page'] : 1;

		$total = MainWP_AUM_DB::instance()->get_monitor_urls(
			$this->service_name,
			array(
				'conds'      => $conds,
				'count_only' => true,
			)
		);

		if ( 0 == $total && $site_id ) {
			// try to get monitor with url like site url.
			$conds = array( 'url_address_like' => $website['url'] );
			$total = MainWP_AUM_DB::instance()->get_monitor_urls(
				$this->service_name,
				array(
					'conds'      => $conds,
					'count_only' => true,
				)
			);
		}

		// to use datatable js.
		if ( $total > MAINWP_MONITOR_API_LIMIT_PER_PAGE ) {
			$per_page = get_option( 'mainwp_aum_setting_monitors_per_page', 10 );
		} else {
			$per_page = MAINWP_MONITOR_API_LIMIT_PER_PAGE;
		}

		$urls = MainWP_AUM_DB::instance()->get_monitor_urls(
			$this->service_name,
			array(
				'conds'           => $conds,
				'per_page'        => $per_page,
				'page'            => $get_page,
				'with_properties' => true,
			)
		);

		$monitor_urls = array();
		$url_ids      = array();
		if ( $total > 0 ) {
			foreach ( $urls as $url ) {
				$url_ids[ $url->monitor_id ]      = $url->url_id;
				$monitor_urls[ $url->monitor_id ] = $url;
				if ( count( $url_ids ) >= MAINWP_MONITOR_API_LIMIT_PER_PAGE ) {
					break;
				}
			}
		}

		$this->set( 'get_page', $get_page );
		$this->set( 'total', $total );
		$this->set( 'urls', $monitor_urls );
		return true;
	}

	/**
	 * Method: get_checks_results()
	 *
	 * @param array $url_ids URL IDs.
	 *
	 * @return bool True|False.
	 */
	public function get_checks_results( $url_ids = array() ) {
		$loaded = false;
		foreach ( $url_ids as $monitor_id => $url_id ) {
			$result = MainWP_AUM_Nodeping_API::instance()->get_monitor_results( $monitor_id );
			$result = json_decode( $result, true );
			if ( is_array( $result ) ) {
				foreach ( $result as $mo_status ) {
					if ( ! isset( $mo_status['_id'] ) ) {
						continue;
					}

					$loaded = true;

					$data = array(
						'url_id'          => $url_id,
						'status'          => $mo_status['su'],
						'check_timestamp' => $mo_status['s'], // in milliseconds.
						'response'        => isset( $mo_status['sc'] ) ? $mo_status['sc'] : '',
						'location'        => isset( $mo_status['l'] ) ? wp_json_encode( $mo_status['l'] ) : '',
						'runtime'         => isset( $mo_status['rt'] ) ? $mo_status['rt'] : 0,
						'result'          => ! empty( $mo_status['su'] ) ? 1 : 0,
						'message'         => isset( $mo_status['m'] ) ? $mo_status['m'] : '',
					);
					MainWP_AUM_DB_Events_Nodeping::instance()->insert_event( $data );
				}
			}
		}
		return $loaded;
	}

	/**
	 * Method: check_unavailable_url_monitors()
	 *
	 * Checks for missing monitors.
	 *
	 * @return array Missing monitors.
	 */
	public function check_unavailable_url_monitors() {

		$url_monitors_ids = array();

		$monitors = MainWP_AUM_Nodeping_Settings_Handle::get_instance()->get_api_monitors(); // to check unavailable urls.

		if ( false === $monitors ) {
			$this->flash( 'trf', __( 'Unable to load NodePing monitor data. Please, try again later.', 'advanced-uptime-monitor-extension' ) );
			return -1;
		}

		if ( is_array( $monitors ) ) {
			foreach ( $monitors as $url_monitor ) {
				$url_monitors_ids[] = $url_monitor['_id'];
			}
		}

		$urls = MainWP_AUM_DB::instance()->get_monitor_urls( $this->service_name );

		$unavailable_urls = array();
		foreach ( $urls as $url ) {
			if ( ! in_array( $url->monitor_id, $url_monitors_ids ) ) {
				$unavailable_urls[] = $url->url_name;
				MainWP_AUM_DB::instance()->delete_db_monitor( 'url_id', $url->url_id );
				MainWP_AUM_DB_Events_Nodeping::instance()->delete_events( $url->url_id );
			}
		}

		if ( ! empty( $unavailable_urls ) ) {
			$this->flash( 'yellow', __( 'Following monitors could not be found in your Uptime Robot dashboard so they have been deleted:<br/>', 'advanced-uptime-monitor-extension' ) . implode( ',<br/>', $unavailable_urls ) );
			return true;
		}
		return false;
	}

	/**
	 * Method: ajax_statistics_table()
	 *
	 * Loads statistics table.
	 */
	public function ajax_statistics_table() {
		$this->ajax_check_permissions( 'monitors_page' );

		$url_id = $this->params['url_id'];

		$url = MainWP_AUM_DB::instance()->get_monitor_by( 'url_id', $url_id );
		if ( ! $url ) {
			exit;
		}

		$stats_contditions = array( 'url_id' => $url_id );

		$stats = MainWP_AUM_DB_Events_Nodeping::instance()->get_cond_events(
			array(
				'conds'    => $stats_contditions,
				'order'    => 'check_timestamp DESC',
				'page'     => isset( $this->params['stats_page'] ) && (int) $this->params['stats_page'] > 0 ? $this->params['stats_page'] : 1,
				'per_page' => 10,
			)
		);

		$last_existing_event      = MainWP_AUM_DB_Events_Nodeping::instance()->get_last_events( $url_id );
		$unix_gmt_time_last_event = 0;
		if ( ! empty( $last_existing_event ) ) {
			$unix_gmt_time_last_event = strtotime( $last_existing_event->check_timestamp );
		}

		$cnt = MainWP_AUM_DB_Events_Nodeping::instance()->get_cond_events(
			array(
				'conds'      => $stats_contditions,
				'count_only' => true,
			)
		);

		$this->set( 'stats_page', isset( $this->params['stats_page'] ) ? $this->params['stats_page'] : 1 );
		$this->set( 'url', $url );
		$this->set( 'unixtime_last_event', $unix_gmt_time_last_event );
		$this->set( 'stats', $stats );
		$this->render_view( 'nodeping_statistics_table' );
		die();
	}

	/**
	 * Method ajax_meta_box()
	 *
	 * Ajax handle.
	 */
	public function ajax_meta_box() {
		$this->ajax_check_permissions( 'meta_box' );

		if ( isset( $_POST['site_id'] ) && $_POST['site_id'] ) {
			$site_id = intval( $_POST['site_id'] );
		} else {
			$site_id = null;
		}

		$this->set_view_monitors( $site_id );
		$this->render_view( 'nodeping_meta_box' );
		die();
	}

	/**
	 * Method: render_recent_events()
	 *
	 * Renders recent events.
	 */
	public function render_recent_events() {

		$urls = MainWP_AUM_DB::instance()->get_monitor_urls(
			$this->service_name,
			array(
				'with_properties' => true,
			)
		);

		$urls_info = array();
		$url_ids   = array();

		if ( ! empty( $urls ) ) {
			foreach ( $urls as $url ) {
				if ( ! isset( $urls_info[ $url->url_id ] ) ) {
					$urls_info[ $url->url_id ] = array(
						'url_address' => $url->url_address,
						'url_name'    => $url->url_name,
					);
				}
				$url_ids[ $url->monitor_id ] = $url->url_id;
			}
		}

		$this->get_checks_results( $url_ids );

		$order_by = 'check_timestamp DESC';
		$limit    = 100;
		$stats    = MainWP_AUM_DB_Events_Nodeping::instance()->get_events( '', $limit, $order_by );
		$this->set( 'urls_info', $urls_info );
		$this->set( 'stats', $stats );
		$this->render_view( 'nodeping_recent_events' );
	}


	/**
	 * Method: ajax_add_monitor_for_site()
	 *
	 * Creates monitor for child site.
	 *
	 * @param object $site             Site object.
	 * @param array  $data             Data.
	 * @param array  $just_inserted_id Just inserted IDs.
	 */
	public function ajax_add_monitor_for_site( $site, $data, $just_inserted_id ) {

		$information = array();

		$contacts_items = MainWP_AUM_Nodeping_API::instance()->get_option( 'contacts' );

		$notifications = array();
		if ( is_array( $contacts_items ) ) {
			$cont = current( $contacts_items );
			if ( isset( $cont['addresses'] ) && is_array( $cont['addresses'] ) ) {
				foreach ( $cont['addresses'] as $cont_id => $noti ) {
					$notifications[] = array(
						$cont_id => array(
							'delay'    => 15,
							'schedule' => 'Days',
						),
					);
				}
			}
		}

		$post_data = array(
			'label'         => trim( $data['url_name'] ),
			'target'        => trim( $data['url_address'] ),
			'type'          => 'HTTP',
			'enabled'       => 'active', // enabled.
			'interval'      => 15,
			'threshold'     => 5,
			'description'   => '',
			'notifications' => $notifications,
		);

		try {
			$result = MainWP_AUM_Nodeping_API::instance()->api_edit_monitor( $post_data ); // insert new check.
			$result = json_decode( $result, true );
		} catch ( \Exception $ex ) {
			throw $ex;
		}

		if ( is_array( $result ) && isset( $result['_id'] ) ) {
			$this->flash( 'green', __( 'Monitor created successfully!', 'advanced-uptime-monitor-extension' ) );
			do_action( 'mainwp_aum_monitor_created', $data );
			$update_data = array(
				'monitor_id' => $result['_id'],
				'dashboard'  => 1, // new monitor, set display on dashboard as default.
			);
			MainWP_AUM_DB::instance()->update_monitor_url( $update_data, $just_inserted_id );

			$opts_value = array(
				'monitor_type'     => 'HTTP',
				'monitor_interval' => 5,
				'enable'           => 'active',
				'timeout'          => $result['timeout'],
				'notify_contacts'  => '',
				'description'      => '',
			);
			MainWP_AUM_DB::instance()->save_monitor_url_field_options_data( $just_inserted_id, $opts_value );

			$this->set( 'url_saved', true );
			$information['result'] = 'success';
		} else {
			if ( is_array( $result ) && isset( $result['error'] ) ) {
				$err = $result['error'];
				$this->flash( 'red', $err );
			} else {
				$err = __( 'Undefined error on the NodePing side. Please try again later.', 'advanced-uptime-monitor-extension' );
				$this->flash( 'mainwp-notice mainwp-notice-yellow', $err );
			}
			MainWP_AUM_DB::instance()->delete_db_monitor( 'url_id', $just_inserted_id );
			$information['error'] = $err;
		}
		return $information;
	}

	/**
	 * Method get_nodeping_monitor_state()
	 *
	 * Renders uptime status.
	 *
	 * @param string $monitor Monitor status.
	 *
	 * @return string $sta Uptime status.
	 */
	public static function get_nodeping_monitor_state( $monitor ) {
		$monitor_status = '';
		if ( 'inactive' == $monitor->enable ) {
			$monitor_status = '<span class="ui grey fluid center aligned label">' . __( 'DISABLED', 'advanced-uptime-monitor-extension' ) . '</span>';
		} else {
			if ( 1 == $monitor->state ) {
				$monitor_status = '<span class="ui green fluid center aligned label">' . __( 'PASS', 'advanced-uptime-monitor-extension' ) . '</span>';
			} else {
				$monitor_status = '<span class="ui red fluid center aligned label">' . __( 'FAIL', 'advanced-uptime-monitor-extension' ) . '</span>';
			}
		}
		return $monitor_status;
	}

	/**
	 * Method aum_get_data()
	 *
	 * Get uptime status.
	 *
	 * @param object $monitor_address Monitor object.
	 * @param string $start_date Start date.
	 * @param string $end_date End date.
	 *
	 * @return mixed array|false $return AUM data.
	 */
	public function aum_get_data( $monitor_address, $start_date = null, $end_date = null ) {
		$uptimes = array(
			0  => 0,
			7  => 0,
			15 => 0,
			30 => 0,
			45 => 0,
			60 => 0,
		);

		foreach ( $uptimes as $d => $val ) {
			$result = MainWP_AUM_Nodeping_API::instance()->get_uptime( $monitor_address->monitor_id, $d );
			$result = json_decode( $result, true );
			if ( is_array( $result ) && isset( $result['total'] ) ) {
				$uptimes[ $d ] = $result['total']['uptime'];
			}
		}

		$return['aum.alltimeuptimeratio'] = MainWP_AUM_Settings_Page::get_instance()->get_ratio_value( $uptimes[0] );
		$return['aum.uptime7']            = MainWP_AUM_Settings_Page::get_instance()->get_ratio_value( $uptimes[7] );
		$return['aum.uptime15']           = MainWP_AUM_Settings_Page::get_instance()->get_ratio_value( $uptimes[15] );
		$return['aum.uptime30']           = MainWP_AUM_Settings_Page::get_instance()->get_ratio_value( $uptimes[30] );
		$return['aum.uptime45']           = MainWP_AUM_Settings_Page::get_instance()->get_ratio_value( $uptimes[45] );
		$return['aum.uptime60']           = MainWP_AUM_Settings_Page::get_instance()->get_ratio_value( $uptimes[60] );

		$order_by = 'check_timestamp DESC';
		$stats    = MainWP_AUM_DB_Events_Nodeping::instance()->get_events( ' ( check_timestamp / 1000 ) >= UNIX_TIMESTAMP("' . gmdate( 'Y-m-d H:i:s', $start_date ) . '") AND ( check_timestamp / 1000 ) <= UNIX_TIMESTAMP("' . date( 'Y-m-d H:i:s', $end_date ) . '") AND url_id = ' . $monitor_address->url_id, false, $order_by );
		ob_start();
		?>
			<table id="advanced-uptime-nodeping-stats-table" class="ui single line table">
				<thead>
					<tr>
					<th><?php _e( 'Monitor', 'advanced-uptime-monitor-extension' ); ?></th>
					<th><?php _e( 'URL', 'advanced-uptime-monitor-extension' ); ?></th>
					<th><?php _e( 'Type', 'advanced-uptime-monitor-extension' ); ?></th>
					<th><?php _e( 'Status', 'advanced-uptime-monitor-extension' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
					<td><?php echo esc_html( $monitor_address->url_name ); ?></td>
					<td><a href="<?php echo $monitor_address->url_address; ?>" target="_blank"><?php echo $monitor_address->url_address; ?></a></td>
					<td><?php echo esc_html( $umonitor_addressrl->monitor_type ); ?></td>
					<td><?php echo ( 'inactive' == $monitor_address->enable || 0 == $monitor_address->enable ) ? 'inactive' : ( 1 == $monitor_address->state ? __( 'PASS', 'advanced-uptime-monitor-extension' ) : __( 'FAIL', 'advanced-uptime-monitor-extension' ) ); ?></td>
					</tr>
				</tbody>
				</table>
				<?php
				if ( ! empty( $stats ) ) {
					?>
					<table class="ui single line table">
					<thead>
						<tr>
							<th><?php _e( 'Time', 'advanced-uptime-monitor-extension' ); ?></th>
							<th><?php _e( 'Location', 'advanced-uptime-monitor-extension' ); ?></th>
							<th><?php _e( 'Run time', 'advanced-uptime-monitor-extension' ); ?></th>
							<th><?php _e( 'Response', 'advanced-uptime-monitor-extension' ); ?></th>
							<th><?php _e( 'Result', 'advanced-uptime-monitor-extension' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php
					foreach ( $stats as $event ) {
						$loc = ( $event->location != '' ) ? json_decode( $event->location, true ) : '';
						if ( is_array( $loc ) ) {
							$loc = array_values( $loc );
							$loc = $loc[0];
						} else {
							$loc = '';
						}
						?>
								<tr>
								<td><?php echo MainWP_AUM_Main::format_timestamp( $event->check_timestamp / 1000 ); ?></td>
								<td><?php echo esc_html( strtoupper( $loc ) ); ?></td>
								<td><?php echo intval( $event->runtime ); ?></td>
								<td><?php echo esc_html( $event->response ); ?></td>
								<td><?php echo $url->result ? __( 'PASS', 'advanced-uptime-monitor-extension' ) : __( 'DISABLED', 'advanced-uptime-monitor-extension' ); ?></td>
								</tr>
							<?php } ?>
						</tbody>
					</table>
					<?php
				}
				$stats_html          = ob_get_clean();
				$stats_html          = preg_replace( "/\r|\n/", '', $stats_html );
				$return['aum.stats'] = $stats_html;

				return $return;
	}
}
