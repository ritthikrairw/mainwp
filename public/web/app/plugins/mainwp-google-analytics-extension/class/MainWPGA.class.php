<?php
class MainWPGA {
	public $security_nonces;
	private static $redirect_uri;

	public static function getClassName() {
		return __CLASS__;
	}

	public function __construct() {
		$url                = admin_url( 'admin.php?page=Extensions-Mainwp-Google-Analytics-Extension&mainwp_ga=1' );
		self::$redirect_uri = apply_filters( 'mainwp_ga_redirect_url', $url );

		add_action( 'mainwp_update_site', array( &$this, 'mainwp_update_site' ) );
		add_action( 'mainwp_delete_site', array( &$this, 'mainwp_delete_site' ) );
		add_action( 'mainwp_ga_delete_site', array( &$this, 'ga_delete_site' ) );

		add_action( 'mainwp_extension_sites_edit_tablerow', array( &$this, 'mainwp_extension_sites_edit_tablerow' ) ); // to do change to: mainwp-manage-sites-edit
		add_filter( 'mainwp_ga_get_data', array( &$this, 'get_ga_data' ), 10, 4 );
		add_action( 'wp_ajax_mainwp_ga_disconnect', array( &$this, 'mainwp_ga_disconnect' ) );
		add_action( 'wp_ajax_mainwp_ga_connect', array( &$this, 'mainwp_ga_connect' ) );
		add_action( 'wp_ajax_mainwp_ga_getstats', array( &$this, 'mainwp_ga_getstats' ) );
	}

	public static function autoloadVendor() {
		require_once MAINWP_GA_PLUGIN_DIR . '/lib/google-api-client-2.2.2/vendor/autoload.php';
	}

	// Renders the top of the widget (name + dropdown)
	public static function getName() {
		$name = __( 'Google Analytics', 'mainwp-google-analytics-extension' );
		return $name;
	}

	public static function to_ga_auth() {
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'Extensions-Mainwp-Google-Analytics-Extension' ) {
			if ( isset( $_GET['mainwp_ga'] ) && $_GET['mainwp_ga'] == 1 && isset( $_GET['code'] ) ) {
				$gaId = get_user_option( 'mainwp_ga_processing_oauth_ga_id' );
				if ( $gaId ) {
					$verify_result = self::ga_auth( $gaId, $_GET['code'] );
					update_option( 'mainwp_ga_verify_result_status', $verify_result );
					wp_safe_redirect( admin_url( 'admin.php?page=Extensions-Mainwp-Google-Analytics-Extension' ), 303 );
					exit();
				}
			}
		}
	}

	// Renders the content of the widget
	public static function render_metabox() {

		self::handleUpdateGA();

		$gaSites = self::getAvailableSites();

		MainWPGAUtility::array_sort( $gaSites, 'name' );
		?>
		<?php if ( isset( $_GET['page'] ) && $_GET['page'] !== 'Extensions-Mainwp-Google-Analytics-Extension' ) : ?>
	<div class="ui grid">
			<div class="twelve wide column">
				<h3 class="ui header handle-drag">
				<?php esc_html_e( 'Google Analytics', 'mainwp-google-analytics-extension' ); ?>
					<div class="sub header"><?php _e( 'See your visitor\'s search and traffic patterns', 'mainwp-google-analytics-extension' ); ?></div>
				</h3>
			</div>
			<div class="four wide column right aligned"></div>
		</div>
	<?php endif; ?>
		<?php
		if ( count( $gaSites ) > 0 ) {
			$random = random_int( 0, count( $gaSites ) - 1 );

			?>
	  <div class="ui grid">
		<div class="three column row">
		  <div class="column">
			<?php if ( isset( $_GET['tab'] ) && ( $_GET['tab'] == 'data' ) ) : ?>
			  <a href="#" id="mainwp-ga-show-extra-details" class="ui basic green button"><?php _e( 'See All Details', 'mainwp-google-analytics-extension' ); ?></a>
			<?php endif; ?>
		  </div>
		  <div class="column"></div>
		  <div class="column">
			<select name="mainwp_widget_ga_site" id="mainwp_widget_ga_site" class="ui fluid dropdown">
			  <?php
				$i = 0;
				foreach ( $gaSites as $site ) :
					$selected = '';
					if ( $random == $i ) {
						$selected = 'selected="selected"';
					}
					$i++;
					?>
				<option value="<?php echo $site['id']; ?>" <?php echo $selected; ?> ><?php echo $site['name']; ?></option>
				<?php endforeach; ?>
			</select>
		  </div>
		</div>
		<div class="ui hidden divider"></div>
		<div class="ui segment" id="mainwp-ga-data-content"></div>
	  </div>
	  <script type="text/javascript">
		google.charts.load('current',{'packages':['corechart']});
		jQuery( document ).ready(function() {
			mainwp_ga_getstats();
		});
	  </script>
			<?php
		} else {
			?>
	  <div id="mainwp_widget_ga_content">
		<div class="ui hidden divider"></div>
		<div class="ui hidden divider"></div>
		<h2 class="ui icon header">
		  <i class="chart line icon"></i>
		  <div class="content">
			<?php _e( 'No data available!', 'mainwp-google-analytics-extension' ); ?>
			<div class="sub header"><?php _e( 'Make sure you have your Google Analytics account connected.', 'mainwp-google-analytics-extension' ); ?></div>
			<div class="ui hidden divider"></div>
			<a href="admin.php?page=Extensions-Mainwp-Google-Analytics-Extension&tab=new" class="ui big green button"><?php _e( 'Connect Google Account', 'mainwp-google-analytics-extension' ); ?></a>
		  </div>
		</h2>
	  </div>
			<?php
		}
	}

	private static function getStatsInt( $profile_id, $gas_id, $startDate, $endDate, $full = false, $graph = false ) {
		$ga_entry = MainWPGADB::Instance()->getGAEntryBy( 'id', $gas_id );

		if ( empty( $ga_entry ) || empty( $ga_entry->refresh_token ) ) {
			return false;
		}

		self::autoloadVendor();

		try {
			$client = new Google_Client();
			$client->setApplicationName( 'Google Analytics Application' );
			$client->setClientId( $ga_entry->client_id );
			$client->setClientSecret( $ga_entry->client_secret );
			$client->setRedirectUri( self::$redirect_uri );
			$client->setAccessType( 'offline' );

			$results      = array();
			$access_token = array();

			if ( $client->isAccessTokenExpired() ) {
				$access_token = $client->refreshToken( $ga_entry->refresh_token );
			} else {
				$access_token = $client->getAccessToken();
			}

			$service = new Google_Service_Analytics( $client );

			if ( ! empty( $access_token ) ) {
				if ( $graph ) { // Graph stats requested
					$metrics = array( 'ga:sessions' );
				} else { // Normal info requested
					$metrics = array( 'ga:sessions', 'ga:bounceRate', 'ga:pageviews', 'ga:avgSessionDuration', 'ga:pageviewsPerSession', 'ga:percentNewSessions' );
				}

				$metrics = implode( ',', $metrics );

				$dimensions = array();

				if ( $graph ) { // Graph stats requested (link the stats to the visits)
					$dimensions = array(
						'ga:day'   => 'ga:sessions',
						'ga:month' => 'ga:sessions',
						'ga:year'  => 'ga:sessions',
					);
				} else {
					if ( $full ) { // Full stats requested (link the stats to the visits)
						$dimensions = array(
							'ga:source'   => 'ga:sessions',
							'ga:keyword'  => 'ga:sessions',
							'ga:country'  => 'ga:sessions',
							'ga:pagePath' => 'ga:pageviews',
						);
					}
				}

				$options = array();

				if ( count( $dimensions ) > 0 ) {
					$options['dimensions'] = implode( ',', array_keys( $dimensions ) );
				}

				$results = $service->data_ga->get( 'ga:' . $profile_id, $startDate, $endDate, $metrics, $options );
			}
		} catch ( Exception $e ) {
			$error = $e->getMessage();
			die( __( 'An error occured: ', 'mainwp-google-analytics-extension' ) . $error . '<br />' . __( 'Please check your settings, try reconnecting to your Google Analytics account in the settings.', 'mainwp-google-analytics-extension' ) );
		}

		if ( ! is_object( $results ) || ! isset( $results->columnHeaders ) ) {
			die( __( 'An error occured. Please try again.', 'mainwp-google-analytics-extension' ) );
		}

		$outputs = array();

		// for backward compatible
		$convert_index_to_name = $metric_indexs = array();
		foreach ( $results['columnHeaders'] as $index => $column ) {
			$convert_index_to_name[ $index ] = $column['name'];
			if ( $column['columnType'] == 'METRIC' ) {
				$metric_indexs[] = $index;
			}
		}

		// Metrics:
		$aggregates = array();
		if ( isset( $results['totalsForAllResults'] ) && is_array( $results['totalsForAllResults'] ) ) {
			foreach ( $results['totalsForAllResults'] as $mt => $val ) {
				$aggregates[ $mt ] = $val;
			}
		}

		$outputs['aggregates'] = $aggregates;

		if ( $full ) {
			// Dimensions:
			$individual = array();

			if ( isset( $results['rows'] ) && is_array( $results['rows'] ) ) {
				foreach ( $results['rows'] as $row ) {
					$entry_metrics    = array();
					$entry_dimensions = array();

					foreach ( $row as $idx => $value ) {
						$name = $convert_index_to_name[ $idx ];
						if ( in_array( $idx, $metric_indexs ) ) {
							$entry_metrics[ $name ] = $value;
						} else {
							$entry_dimensions[ $name ] = $value;
						}
					}

					// Count the visits/pageviews/... (whatever is set in the dimensions array above)
					foreach ( $dimensions as $dimension => $metric ) {
						if ( $entry_dimensions[ $dimension ] != '(not set)' ) {
							if ( isset( $individual[ $dimension ][ $entry_dimensions[ $dimension ] ] ) ) {
								$individual[ $dimension ][ $entry_dimensions[ $dimension ] ] += $entry_metrics[ $metric ];
							} else {
								$individual[ $dimension ][ $entry_dimensions[ $dimension ] ] = $entry_metrics[ $metric ];
							}
						}
					}
				}
			}

			$outputs['individual'] = $individual;
		}

		if ( $graph ) {
			// Graph dimensions
			$daystats = array();
			foreach ( $results['rows'] as $row ) {
				$entry_metrics    = array();
				$entry_dimensions = array();

				foreach ( $row as $idx => $value ) {
					$name = $convert_index_to_name[ $idx ];
					if ( in_array( $idx, $metric_indexs ) ) {
						$entry_metrics[ $name ] = $value;
					} else {
						$entry_dimensions[ $name ] = $value;
					}
				}

				// Count the visitors per day
				foreach ( $dimensions as $dimension => $metric ) {
					$daystats[ $entry_dimensions['ga:year'] ][ $entry_dimensions['ga:month'] ][ $entry_dimensions['ga:day'] ] = $entry_metrics[ $metric ];
				}
			}

			$outputs['daystats'] = $daystats;
		}
		return $outputs;
	}


	function get_ga_data( $websiteid, $start_date, $end_date, $graph = false ) {
		$website = MainWPGADB::Instance()->getGAGASId( $websiteid );
		if ( $end_date - $start_date <= 24 * 60 * 60 ) { // to fix one day period issue
			$end_date += 24 * 60 * 60;
		}

		$startDate = date( 'Y-m-d', $start_date );
		$endDate   = date( 'Y-m-d', $end_date );
		$return    = array();

		$property_ids = MainWPGADB::Instance()->getGASettingGlobal( 'propertyIds' );

		if ( $property_ids != '' ) {
			$property_ids = json_decode( $property_ids, 1 );
		}

		if ( ! is_array( $property_ids ) ) {
			$property_ids = array();
		}

		if ( $website ) {
			$id         = $website->ga_id;
			$gas_id     = $website->gas_id;
			$profile_id = isset( $property_ids[ $id ] ) ? $property_ids[ $id ] : 0;

			if ( empty( $profile_id ) ) {
				return;
			}

			$return['stats_int'] = self::getStatsInt( $profile_id, $gas_id, $startDate, $endDate, false );

			if ( $graph ) {
				$valuesGraph = self::getStatsInt( $profile_id, $gas_id, $startDate, $endDate, false, true );
				$graphData   = array();

				// ===============================================================
				// enym: modified stepping
				// $step = ($end_date - $start_date) / (10 * 24 * 60 * 60);
				// $step = ($end_date - $start_date) / (31 * 24 * 60 * 60); //31 days is more robust than "1 month" and must match daterange in MainWPCReport.class.php
				// ===============================================================

				for ( $i = $start_date; $i <= $end_date; $i = $i + 24 * 60 * 60 ) {
					$currVal     = (int) $valuesGraph['daystats'][ date( 'Y', $i ) ][ date( 'm', $i ) ][ date( 'd', $i ) ];
					$idate       = date( 'M j', $i );
					$format_date = apply_filters( 'mainwp_ga_visit_chart_date', $i, $websiteid );
					if ( $i !== $format_date && ! empty( $format_date ) ) {
						$graphData[] = array( $idate, $currVal, $format_date );
					} else {
						$graphData[] = array( $idate, $currVal );
					}
				}

				$valuesGraph = json_encode( $graphData );

				// ===============================================================
				// enym: new line to send raw graph data to the client reports extension
				$return['stats_graphdata'] = $graphData;
				// ===============================================================
			}
		}
		return $return;
	}

	// Renders the stats on the widget (from db or fetches them via getStatsInt)
	public static function getStats( $websiteid ) {

		$website      = MainWPGADB::Instance()->getGAGASId( $websiteid );
		$cacheCheck   = MainWPGADB::Instance()->getGACache( $websiteid );
		$interval     = MainWPGADB::Instance()->getGASettingGlobal( 'update_interval' );
		$property_ids = MainWPGADB::Instance()->getGASettingGlobal( 'propertyIds' );

		if ( $property_ids != '' ) {
			$property_ids = json_decode( $property_ids, 1 );
		}

		if ( ! is_array( $property_ids ) ) {
			$property_ids = array();
		}

		// to debug
		$cacheCheck = null;

		if ( $cacheCheck == null ) { // Update Cache
			$id         = $website->ga_id;
			$gas_id     = $website->gas_id;
			$profile_id = isset( $property_ids[ $id ] ) ? $property_ids[ $id ] : 0;

			if ( empty( $profile_id ) ) {
				return;
			}

			$week  = 7;
			$month = 30;
			$days  = $week;

			if ( $interval == 'week' ) {
				$days = $week;
			} else {
				$days = $month;
			}

			$startDate  = date( 'Y-m-d', strtotime( '-' . $days . ' day' ) );
			$endDate    = date( 'Y-m-d', strtotime( '-1 day' ) );
			$start2Date = date( 'Y-m-d', strtotime( '-' . ( 2 * $days ) . ' day' ) );
			$end2Date   = date( 'Y-m-d', strtotime( '-' . ( 1 + $days ) . ' day' ) );

			$values      = self::getStatsInt( $profile_id, $gas_id, $startDate, $endDate, true );
			$valuesPrev  = self::getStatsInt( $profile_id, $gas_id, $start2Date, $end2Date );
			$valuesGraph = self::getStatsInt( $profile_id, $gas_id, $start2Date, $endDate, false, true );

			$end       = strtotime( '-1 day' );
			$start     = strtotime( '-' . $days . ' day' );
			$graphData = array();

			for ( $i = $start; $i <= $end; $i = $i + ( 24 * 60 * 60 ) ) {
				$currVal      = (int) $valuesGraph['daystats'][ date( 'Y', $i ) ][ date( 'm', $i ) ][ date( 'd', $i ) ];
				$previousTime = $i - ( $days * 24 * 60 * 60 );
				$prevVal      = (int) $valuesGraph['daystats'][ date( 'Y', $previousTime ) ][ date( 'm', $previousTime ) ][ date( 'd', $previousTime ) ];

				$idate       = date( 'M j', $i );
				$format_date = apply_filters( 'mainwp_ga_visit_chart_date', $i, $websiteid );

				if ( $i !== $format_date && ! empty( $format_date ) ) {
					$graphData[] = array( $format_date, $currVal, $prevVal );
				} else {
					$graphData[] = array( $idate, $currVal, $prevVal );
				}
			}

			$valuesGraph = json_encode( $graphData );

			MainWPGADB::Instance()->updateGACache( $websiteid, json_encode( $values ), json_encode( $valuesPrev ), $valuesGraph );
		} else {
			$values      = json_decode( $cacheCheck->statsValues, true );
			$valuesPrev  = json_decode( $cacheCheck->statsValuesPrev, true );
			$valuesGraph = $cacheCheck->graphValues;
		}
		// Showing stats:
		echo '
      <div id="chart_div"></div>
      <script>

      function drawGraph() {
        var data = new google.visualization.DataTable();
        data.addColumn( "string", "category" );
        data.addColumn( "number", "Visits" );
        data.addColumn( "number", "Visits" );
        data.addRows(' . $valuesGraph . ');

        var chart = new google.visualization.AreaChart( document.getElementById( "chart_div" ) );
          chart.draw(data, {
            width: "100%",
            height: 400,
            legend: "none",
            pointSize: 5,
            backgroundColor: { fill: "none" },
            chartArea: { top:10, width: "86%", height: "80%" },
            hAxis: { gridlineColor: "red", slantedText: false, maxAlternation: 1' . ( $interval == 'month' ? ', showTextEvery: 3' : '' ) . '},
            format:"#",
            vAxis: { maxValue: 2 },
            series: [{color: "#0078CE"},{color: "#7FB100", areaOpacity: 0}]
          });
        }

        drawGraph();
        </script>';
		?>

	  <div class="ui grid">
		<div class="one column row">
		  <div class="center aligned column"><a class="ui blue empty circular label"></a> <?php echo __( ' this ', 'mainwp-google-analytics-extension' ) . $interval; ?> <a class="ui green empty circular label"></a> <?php echo __( ' last ', 'mainwp-google-analytics-extension' ) . $interval; ?></div>
		</div>
	  </div>

	  <div class="ui hidden divider"></div>

	  <table class="ui single line tablet fixed stackable table" id="mainwp-ga-details-data-table">
		<thead>
		  <tr>
			<th><?php echo __( 'Visits', 'mainwp-google-analytics-extension' ); ?></th>
			<th><?php echo __( 'New Visits', 'mainwp-google-analytics-extension' ); ?></th>
			<th><?php echo __( 'Pages/Visit', 'mainwp-google-analytics-extension' ); ?></th>
			<th><?php echo __( 'Bounce Rate', 'mainwp-google-analytics-extension' ); ?></th>
			<th><?php echo __( 'Pageviews', 'mainwp-google-analytics-extension' ); ?></th>
			<th><?php echo __( 'Avg. Time on Site', 'mainwp-google-analytics-extension' ); ?></th>
		  </tr>
		</thead>
		<tbody>
		  <tr>
			<td><?php self::getStats_print_aggregate( __( 'Visits', 'mainwp-google-analytics-extension' ), $valuesPrev['aggregates']['ga:sessions'], $values['aggregates']['ga:sessions'] ); ?></td>
			<td><?php self::getStats_print_aggregate( __( 'New Visits', 'mainwp-google-analytics-extension' ), $valuesPrev['aggregates']['ga:percentNewSessions'], $values['aggregates']['ga:percentNewSessions'], true, true ); ?></td>
			<td><?php self::getStats_print_aggregate( __( 'Pages/Visit', 'mainwp-google-analytics-extension' ), $valuesPrev['aggregates']['ga:pageviewsPerSession'], $values['aggregates']['ga:pageviewsPerSession'], true ); ?></td>
			<td><?php self::getStats_print_aggregate( __( 'Bounce Rate', 'mainwp-google-analytics-extension' ), $valuesPrev['aggregates']['ga:bounceRate'], $values['aggregates']['ga:bounceRate'], true, true ); ?></td>
			<td><?php self::getStats_print_aggregate( __( 'Pageviews', 'mainwp-google-analytics-extension' ), $valuesPrev['aggregates']['ga:pageviews'], $values['aggregates']['ga:pageviews'] ); ?></td>
			<td><?php self::getStats_print_aggregate( __( 'Avg. Time on Site', 'mainwp-google-analytics-extension' ), $valuesPrev['aggregates']['ga:avgSessionDuration'], $values['aggregates']['ga:avgSessionDuration'], false, false, true ); ?></td>
		  </tr>
		</tbody>
	  </table>

	  <div class="ui grid">
		<div class="two column row">
		  <div class="column">
			<table class="ui single line tablet stackable fixed table mainwp-ga-extra-details-data-table" style="display:none">
			  <thead>
				<tr>
				  <th><?php _e( 'Referrers', 'mainwp-google-analytics-extension' ); ?></th>
				  <th class="right aligned"><?php _e( 'Visits', 'mainwp-google-analytics-extension' ); ?></th>
				</tr>
			  </thead>
			  <tbody><?php self::getStats_print_individual( isset( $values['individual']['ga:source'] ) ? $values['individual']['ga:source'] : null ); ?></tbody>
			</table>
		  </div>
		  <div class="column">
			<table class="ui single line tablet stackable fixed table mainwp-ga-extra-details-data-table" style="display:none">
			  <thead>
				<tr>
				  <th><?php _e( 'Country', 'mainwp-google-analytics-extension' ); ?></th>
				  <th class="right aligned"><?php _e( 'Visits', 'mainwp-google-analytics-extension' ); ?></th>
				</tr>
			  </thead>
			  <tbody><?php self::getStats_print_individual( isset( $values['individual']['ga:country'] ) ? $values['individual']['ga:country'] : null ); ?></tbody>
			</table>
		  </div>
		</div>
		<div class="two column row">
		  <div class="column">
			<table class="ui single line tablet stackable fixed table mainwp-ga-extra-details-data-table" style="display:none">
			  <thead>
				<tr>
				  <th><?php _e( 'Pages', 'mainwp-google-analytics-extension' ); ?></th>
				  <th class="right aligned"><?php _e( 'Visits', 'mainwp-google-analytics-extension' ); ?></th>
				</tr>
			  </thead>
			  <tbody><?php self::getStats_print_individual( isset( $values['individual']['ga:pagePath'] ) ? $values['individual']['ga:pagePath'] : null ); ?></tbody>
			</table>
		  </div>
		  <div class="column">
			<table class="ui single line tablet stackable fixed table mainwp-ga-extra-details-data-table" style="display:none">
			  <thead>
				<tr>
				  <th><?php _e( 'Keywords', 'mainwp-google-analytics-extension' ); ?></th>
				  <th class="right aligned"><?php _e( 'Visits', 'mainwp-google-analytics-extension' ); ?></th>
				</tr>
			  </thead>
			  <tbody><?php self::getStats_print_individual( isset( $values['individual']['ga:keyword'] ) ? $values['individual']['ga:keyword'] : null ); ?></tbody>
			</table>
		  </div>
		</div>
	  </div>

		<?php
		die();
	}

	// Used by getStats
	private static function getStats_print_individual( $items ) {
		if ( $items != null ) {
			foreach ( $items as $key => $value ) {
				?>
		<tr>
		  <td><?php echo htmlentities( $key ); ?></td>
		  <td class="right aligned"><?php echo htmlentities( $value ); ?></td>
		</tr>
				<?php
			}
		}
	}

	// Used by getStats
	private static function getStats_print_aggregate( $name, $old, $new, $round = false, $perc = false, $showAsTime = false ) {
		$newVal = $new;
		$oldVal = $old;

		if ( $showAsTime ) {
			$newVal = MainWPGAUtility::sec2hms( $newVal );
			$oldVal = MainWPGAUtility::sec2hms( $oldVal );
		} else {
			if ( $round ) {
				$newVal = round( $newVal, 2 );
				$oldVal = round( $oldVal, 2 );
			}

			if ( $perc ) {
				$newVal = $newVal . '%';
				$oldVal = $oldVal . '%';
			}
		}

		$difference = MainWPGAUtility::getDifferenceInPerc( $old, $new );

		if ( $difference < 0 ) {
			$class = 'red';
		} else {
			$class      = 'green';
			$difference = '+' . $difference;
		}
		?>

	<div class="mainwp_ga_this">
	  <strong><?php _e( 'This: ', 'mainwp-google-analytics-extension' ); ?><?php echo $newVal; ?></strong>
	</div>
	<div class="mainwp_ga_prev">
		<?php _e( 'Previous: ', 'mainwp-google-analytics-extension' ); ?><?php echo $oldVal; ?>
	</div>
	<div class="mainwp_ga_dif">
	  <span class="ui mini label <?php echo $class; ?>"><?php echo $difference; ?>%</span>
	</div>
		<?php
	}

	public static function getConsumerKey() {
		$key = get_option( 'GA_CONSUMERKEY' );
		return ( $key == '' ) ? 'anonymous' : $key;
	}

	public static function getPrivateKey() {
		$key = get_option( 'GA_PRIVKEY' );
		return ( $key == '' ) ? null : $key;
	}

	// Renders & handles post for settings in menu
	public static function handleSettingsPost() {
		global $current_user;
		if ( isset( $_POST['submit'] ) ) {

			if ( isset( $_POST['mainwp_ga_interval'] ) && $_POST['mainwp_ga_interval'] == 'week' ) {
				$interval = 'week';
			} else {
				$interval = 'month';
			}

			if ( isset( $_POST['mainwp_ga_refreshrate'] ) && MainWPGAUtility::ctype_digit( $_POST['mainwp_ga_refreshrate'] ) ) {
				$ga_refreshrate = $_POST['mainwp_ga_refreshrate'];
			} else {
				$ga_refreshrate = 9;
			}

			$ga_autoassign = 0;

			if ( isset( $_POST['mainwp_ga_automaticAssign'] ) && $_POST['mainwp_ga_automaticAssign'] ) {
				$ga_autoassign = 1;
			}

			if ( isset( $_POST['mainwp_ga_client_id'] ) ) {
				foreach ( $_POST['mainwp_ga_client_id'] as $index => $cid ) {
					$ga_client_id     = sanitize_text_field( $cid );
					$ga_client_secret = sanitize_text_field( $_POST['mainwp_ga_client_secret'][ $index ] );
					$ga_account_name  = sanitize_text_field( $_POST['mainwp_ga_account_name'][ $index ] );
					if ( ! empty( $ga_client_id ) ) {
						$data = array(
							'client_id'       => $ga_client_id,
							'client_secret'   => $ga_client_secret,
							'ga_account_name' => $ga_account_name,
						);
						MainWPGADB::Instance()->updateGAEntry( $data );
					}
				}
			}

			MainWPGADB::Instance()->updateGASettingGlobal( 'update_interval', $interval );
			MainWPGADB::Instance()->updateGASettingGlobal( 'refreshrate', $ga_refreshrate );
			MainWPGADB::Instance()->updateGASettingGlobal( 'auto_assign', $ga_autoassign );

			MainWPGADB::Instance()->removeGACache();

			return true;
		}
		return false;
	}

	public static function handleUpdateGA() {
		if ( isset( $_GET['GAUpdate'] ) && ( $_GET['GAUpdate'] == 1 ) ) {
			if ( self::updateAvailableSites() ) {
				echo '<div class="ui message green"><i class="icon close"></i> ' . __( 'Google analytics statistics have been updated.', 'mainwp-google-analytics-extension' ) . '</div>';
			} else {
				echo '<div class="ui message red"><i class="icon close"></i> ' . __( 'Connection to your Google Analytics account could not be established.', 'mainwp-google-analytics-extension' ) . '</div>';
			}
		}
	}

	public static function renderSettings() {

		$current_tab = '';

		if ( isset( $_GET['tab'] ) ) {
			if ( $_GET['tab'] == 'new' ) {
				$current_tab = 'new';
			} elseif ( $_GET['tab'] == 'settings' ) {
				$current_tab = 'settings';
			} elseif ( $_GET['tab'] == 'accounts' ) {
				$current_tab = 'accounts';
			} elseif ( $_GET['tab'] == 'data' ) {
				$current_tab = 'data';
			}
		}

		$verify_result = get_option( 'mainwp_ga_verify_result_status' );

		if ( $verify_result !== false && is_array( $verify_result ) ) {
			delete_option( 'mainwp_ga_verify_result_status' );
		}

		$updated = self::handleSettingsPost();

		$ga_interval    = MainWPGADB::Instance()->getGASettingGlobal( 'update_interval' );
		$ga_refreshrate = MainWPGADB::Instance()->getGASettingGlobal( 'refreshrate' );

		if ( $ga_refreshrate == '' ) {
			$ga_refreshrate = 9;
		}

		$ga_entries  = MainWPGADB::Instance()->getGAEntries();
		$auto_assign = MainWPGADB::Instance()->getGASettingGlobal( 'auto_assign' );

		?>
	<div class="ui labeled icon inverted menu mainwp-sub-submenu" id="mainwp-google-analytics-menu">
			<a href="admin.php?page=Extensions-Mainwp-Google-Analytics-Extension&tab=data" class="item <?php echo ( $current_tab == 'data' ? 'active' : '' ); ?>"><i class="chart line icon"></i> <?php _e( 'Visitor Data', 'mainwp-google-analytics-extension' ); ?></a>
			<a href="admin.php?page=Extensions-Mainwp-Google-Analytics-Extension&tab=new" class="item <?php echo ( $current_tab == 'new' ? 'active' : '' ); ?>"><i class="plus icon"></i> <?php _e( 'Add Account', 'mainwp-google-analytics-extension' ); ?></a>
			<a href="admin.php?page=Extensions-Mainwp-Google-Analytics-Extension&tab=accounts" class="item <?php echo ( ( $current_tab == 'accounts' ) ? 'active' : '' ); ?>" ><i class="cogs icon"></i> <?php _e( 'Manage Accounts', 'mainwp-google-analytics-extension' ); ?></a>
			<a href="admin.php?page=Extensions-Mainwp-Google-Analytics-Extension&tab=settings" class="item <?php echo ( ( $current_tab == 'settings' ) ? 'active' : '' ); ?>"><i class="cog icon"></i> <?php _e( 'Settings', 'mainwp-google-analytics-extension' ); ?></a>
		</div>

	<!-- Visitor Data -->
		<?php if ( $current_tab == 'data' || $current_tab == '' ) : ?>
	<div class="ui segment" id="mainwp-google-analytics-data">
			<?php self::render_metabox(); ?>
	</div>
	<?php endif; ?>

	<!-- Add New Account -->
		<?php if ( $current_tab == 'new' ) : ?>
	<div class="ui alt segment" id="mainwp-google-analytics">
	  <div class="mainwp-main-content">
			<?php if ( is_array( $verify_result ) && isset( $verify_result['error'] ) ) : ?>
		<div class="ui message red" id="mainwp-message-zone"><?php echo $verify_result['error']; ?></div>
		<?php else : ?>
		<div class="ui message <?php echo $updated ? 'green' : ''; ?>" id="mainwp-message-zone"><?php echo $updated ? __( 'Settings saved successfully.', 'mainwp-google-analytics-extension' ) : ''; ?></div>
		<?php endif; ?>
			<?php self::handleUpdateGA(); ?>
		<div class="ui form">
		  <div class="ui hidden divider"></div>
		  <h3 class="ui dividing header"><?php _e( 'Connect Google Analytics Account', 'mainwp-google-analytics-extension' ); ?></h3>
		  <form method="POST" action="" id="mainwp-ga-settings-page-form">
			<div class="ui grid field">
							<label class="six wide column middle aligned"><?php _e( 'Account name', 'mainwp-google-analytics-extension' ); ?></label>
						  <div class="ten wide column">
				<input type="text" name="mainwp_ga_account_name[]"  id="account_name" value="" data-tooltip="<?php esc_attr_e( 'Enter friendly name for the account for easier management when connecting multiple accounts.', 'mainwp-google-analytics-extension' ); ?>" data-inverted="inverted"/>
							</div>
						</div>
			<div class="ui grid field">
							<label class="six wide column middle aligned"><?php _e( 'Client ID', 'mainwp-google-analytics-extension' ); ?></label>
						  <div class="ten wide column">
				<input type="text" name="mainwp_ga_client_id[]" id="client_id" value=""/>
							</div>
						</div>
			<div class="ui grid field">
							<label class="six wide column middle aligned"><?php _e( 'Client Secret', 'mainwp-google-analytics-extension' ); ?></label>
						  <div class="ten wide column">
				<input type="text" name="mainwp_ga_client_secret[]" id="client_secret" value="" />
							</div>
						</div>
			<div class="ui divider"></div>
			<input type="button" name="mainwp_ga_connect" id="mainwp_ga_connect" class="ui big green right floated button" value="<?php esc_attr_e( 'Connect Account', 'mainwp-google-analytics-extension' ); ?>"/>
		  </form>
		</div>
	  </div>
	  <div class="mainwp-side-content">
		<p><?php _e( 'Click the button below to access your Google API Console, and there, activate the Analytics API and create a Client ID in the API Access section.', 'mainwp-google-analytics-extension' ); ?></p>
		<a href="https://console.developers.google.com/" class="ui button big fluid basic green" target="_blank"><?php _e( 'Google API Console', 'mainwp-google-analytics-extension' ); ?></a>
		<div class="ui divider"></div>
		<p><?php _e( 'When asked for the Authorised Redirect URI, use the following:', 'mainwp-google-analytics-extension' ); ?></p>
		<div class="ui message"><?php echo self::$redirect_uri; ?></div>
		<p><?php echo sprintf( __( 'For the additional help for the extension, please check the %1$sextension documentation%2$s.', 'mainwp-google-analytics-extension' ), '<a href="https://kb.mainwp.com/docs/category/mainwp-extensions/google-analytics/" target="_blank">', '</a>' ); ?></p>
	  </div>
	  <div class="ui clearing hidden divider"></div>
	</div>
	<?php endif; ?>

	<!-- Accounts -->
		<?php if ( $current_tab == 'accounts' ) : ?>
	<div class="ui segment" id="mainwp-google-analytics-data">
	  <div class="ui message" id="mainwp-message-zone" style="display:none"></div>
	  <table class="ui single line tablet stackable table" id="mainwp-ga-accounts-table">
		<thead>
		  <tr>
			<th class="collapsing"><?php _e( 'Status', 'mainwp-google-analytics-extension' ); ?></th>
			<th><?php _e( 'Account Email', 'mainwp-google-analytics-extension' ); ?></th>
			<th><?php _e( 'Account Name', 'mainwp-google-analytics-extension' ); ?></th>
			<th><?php _e( 'Client ID', 'mainwp-google-analytics-extension' ); ?></th>
			<th><?php _e( 'Client Secret', 'mainwp-google-analytics-extension' ); ?></th>
			<th class="no-sort right alligned collapsing"></th>
		  </tr>
		</thead>
			<?php if ( is_array( $ga_entries ) && count( $ga_entries ) > 0 ) : ?>
		<tbody>
				<?php $idx = 0; ?>
				<?php foreach ( $ga_entries as $ga_entry ) : ?>
		  <tr row-id="<?php echo $idx; ?>" client-id="<?php echo $ga_entry->client_id; ?>" client-secret="<?php echo $ga_entry->client_secret; ?>">
			<td><?php echo ( ! empty( $ga_entry->ga_name ) ) ? '<span class="ui green mini fluid label">' . __( 'Connected', 'mainwp-google-analytics-extension' ) . '</span>' : '<span class="ui red fluid mini label">' . __( 'Disonnected', 'mainwp-google-analytics-extension' ) . '</span>'; ?></td>
			<td><?php echo ( ! empty( $ga_entry->ga_name ) ) ? $ga_entry->ga_name : 'n/a'; ?></td>
			<td><?php echo $ga_entry->ga_account_name; ?></td>
			<td><?php echo $ga_entry->client_id; ?></td>
			<td><?php echo $ga_entry->client_secret; ?></td>
			<td><input type="button" name="mainwp_ga_disconnect" mainwp_ga_entry="<?php echo $ga_entry->id; ?>" class="ui mini basic button mainwp_ga_disconnect" value="<?php esc_attr_e( 'Remove Account', 'mainwp-google-analytics-extension' ); ?>"/></td>
		  </tr>
					<?php $idx++; ?>
		  <?php endforeach; ?>
		</tbody>
		<?php else : ?>
		  <tbody>
			<tr>
			  <td><?php _e( 'No connected accounts', 'mainwp-google-analytics-extension' ); ?></td>
			</tr>
		  </tbody>
		<?php endif; ?>
	  </table>
	</div>
	<?php endif; ?>

	<!-- Settings -->
		<?php if ( $current_tab == 'settings' ) : ?>
	<div class="ui alt segment" id="mainwp-google-analytics-settings">
	  <div class="mainwp-main-content">
			<?php self::show_message( $verify_result, $updated ); ?>
			<?php self::handleUpdateGA(); ?>
		<div class="ui form">
		  <div class="ui hidden divider"></div>
		  <h3 class="ui dividing header"><?php _e( 'Google Analytics Extension Settings', 'mainwp-google-analytics-extension' ); ?></h3>
		  <form method="POST" action="" id="mainwp-ga-settings-page-form">
			<div class="ui grid field">
							<label class="six wide column middle aligned"><?php _e( 'Display interval', 'mainwp-google-analytics-extension' ); ?></label>
						  <div class="ten wide column">
				<select name="mainwp_ga_interval" id="mainwp_ga_interval" class="ui dropdown selection">
				  <option value="week" <?php echo ( $ga_interval == 'week' ) ? 'selected' : ''; ?>><?php _e( 'Week', 'mainwp-google-analytics-extension' ); ?></option>
				  <option value="month" <?php echo ( $ga_interval == 'month' ) ? 'selected' : ''; ?>><?php _e( 'Month', 'mainwp-google-analytics-extension' ); ?></option>
								</select>
						  </div>
			</div>
			<div class="ui grid field">
							<label class="six wide column middle aligned"><?php _e( 'Data refresh frequency', 'mainwp-google-analytics-extension' ); ?></label>
						  <div class="ten wide column">
				<select name="mainwp_ga_refreshrate" id="mainwp_ga_refreshrate" class="ui dropdown selection">
				  <option value="1" <?php echo ( $ga_refreshrate == '1' ) ? 'selected' : ''; ?>><?php _e( 'Every hour', 'mainwp-google-analytics-extension' ); ?></option>
				  <option value="3" <?php echo ( $ga_refreshrate == '3' ) ? 'selected' : ''; ?>><?php _e( 'Every 3 hours', 'mainwp-google-analytics-extension' ); ?></option>
				  <option value="9" <?php echo ( $ga_refreshrate == '9' ) ? 'selected' : ''; ?>><?php _e( 'Every 9 hours', 'mainwp-google-analytics-extension' ); ?></option>
				  <option value="15" <?php echo ( $ga_refreshrate == '15' ) ? 'selected' : ''; ?>><?php _e( 'Every 15 hours', 'mainwp-google-analytics-extension' ); ?></option>
				  <option value="18" <?php echo ( $ga_refreshrate == '18' ) ? 'selected' : ''; ?>><?php _e( 'Every 18 hours', 'mainwp-google-analytics-extension' ); ?></option>
				  <option value="24" <?php echo ( $ga_refreshrate == '24' ) ? 'selected' : ''; ?>><?php _e( 'Every 24 hours', 'mainwp-google-analytics-extension' ); ?></option>
								</select>
						  </div>
			</div>
			<div class="ui grid field">
							<label class="six wide column middle aligned"><?php _e( 'Automatically assign sites', 'mainwp-google-analytics-extension' ); ?></label>
						  <div class="ten wide column ui toggle checkbox">
								<input type="checkbox" id="mainwp_ga_automaticAssign" name="mainwp_ga_automaticAssign" <?php echo ( $auto_assign == 0 ? '' : 'checked="checked"' ); ?> value="1"><label></label>
							</div>
						</div>
			<div class="ui divider"></div>
			<input type="button" name="mainwp_ga_updatenow" id="mainwp_ga_updatenow" class="ui green basic big button" value="<?php _e( 'Refresh Data', 'mainwp-google-analytics-extension' ); ?>"/>
			<input type="submit" name="submit" id="submit" class="ui right floated green big button" value="<?php esc_attr_e( 'Save Settings', 'mainwp-google-analytics-extension' ); ?>"/>
		  </form>
		</div>
	  </div>
	  <div class="mainwp-side-content">
		<p><?php _e( 'The MainWP Google Analytics Extension gives you valuable insights into your visitor\'s search and traffic patterns, your marketing campaigns and much more allowing you to optimize your strategy and the online experience of your users.', 'mainwp-google-analytics-extension' ); ?></p>
		<p><?php _e( 'The Google Analytics Extension doesn\'t allow you to insert the Google Analytics tracking code on your child sites. To do that you will need to insert the code manually or to use a 3rd party plugin.', 'mainwp-google-analytics-extension' ); ?></p>
		<a class="ui basic fluid big green button" href="https://kb.mainwp.com/docs/category/mainwp-extensions/google-analytics/" target="_blank"><?php _e( 'Help Documentation', 'mainwp-google-analytics-extension' ); ?></a>
	  </div>
	  <div class="ui clearing hidden divider"></div>
	</div>
	<?php endif; ?>
		<?php
	}

	private static function show_message( $verify_result, $updated ) {
		if ( is_array( $verify_result ) && isset( $verify_result['error'] ) ) :
			?>
			<div class="ui message red" id="mainwp-message-zone"><?php echo $verify_result['error']; ?></div>
			<?php else : ?>
			<div class="ui message <?php echo $updated ? 'green' : ''; ?>" id="mainwp-message-zone"><?php echo $updated ? __( 'Settings saved successfully.', 'mainwp-google-analytics-extension' ) : ''; ?></div>
				<?php
		endif;
	}

	// Gets all the avilable sites (sites with a known GA id)
	private static function getAvailableSites() {
		$current_wpid = MainWPGAUtility::get_current_wpid();
		if ( $current_wpid ) {
			$websites = MainWPGADB::Instance()->getWebsitesByUserIdWithGAId( $current_wpid );
		} else {
			$websites = MainWPGADB::Instance()->getWebsitesByUserIdWithGAId();
		}

		$output = array();

		foreach ( $websites as $website ) {

			$chk = apply_filters( 'mainwp_check_current_user_can', true, 'site', $website->id );
			if ( ! $chk ) {
				continue;
			}

			$output[] = array(
				'id'   => $website->id,
				'name' => $website->name,
			);
		}
		return $output;
	}

	// This function will fetch your google account & check against the WPs added in our database, if one matches, we update the GAid
	public static function updateAvailableSites() {
		// Get available sites from all the GA Entries
		$availableSites = array();
		$availableIds   = array();
		$propertyIds    = array();
		$gaEntries      = MainWPGADB::Instance()->getGAEntries();
		self::autoloadVendor();
		$connect_ok = false;
		foreach ( $gaEntries as $ga_entry ) {
			$client_id     = $ga_entry->client_id;
			$client_secret = $ga_entry->client_secret;
			$refresh_token = $ga_entry->refresh_token;
			if ( empty( $refresh_token ) ) {
				continue;
			}
			try {
				$client = new Google_Client();
				$client->setApplicationName( 'Google Analytics Application' );
				$client->setClientId( $client_id );
				$client->setClientSecret( $client_secret );
				$client->setAccessType( 'offline' );
				$client->setRedirectUri( self::$redirect_uri );

				if ( $client->isAccessTokenExpired() ) {
					$access_token = $client->refreshToken( $refresh_token );
				} else {
					$access_token = $client->getAccessToken();
				}

				$service = new Google_Service_Analytics( $client );
				if ( is_array( $access_token ) ) {
					do_action( 'mainwp_log_action', 'GA :: access_token :: ' . print_r( $access_token, true ), MAINWP_GA_PLUGIN_LOG_PRIORITY_NUMBER );
				}
				if ( ! empty( $access_token ) ) {
					$profs = $service->management_profiles->listManagementProfiles( '~all', '~all' );
					if ( is_object( $profs ) ) {
						$connect_ok = true;
						foreach ( $profs->items as $value ) {
							$valueId     = $value['webPropertyId'];
							$select_this = ( ! isset( $availableSites[ $valueId ] ) || ( stripos( $value['name'], 'mainwp' ) !== false ) ) ? true : false;
							$select_this = apply_filters( 'mainwp_ga_select_web_property', $select_this, $value['name'], $value['webPropertyId'], $value['websiteUrl'] );
							if ( $select_this ) {
								$valueUrl                   = $value['websiteUrl'];
								$ga_item_id                 = $value['id'];
								$propertyIds[ $valueId ]    = $value['id'];
								$availableSites[ $valueId ] = array(
									'url'        => $valueUrl,
									'id'         => $valueId,
									'gas_id'     => $ga_entry->id,
									'ga_item_id' => $ga_item_id,
									'name'       => $value['name'],
								); // to fix double listing
								$availableIds[]             = $ga_item_id . '_' . $valueId . '_' . $ga_entry->id;
							}
						}
					}
				}
			} catch ( Exception $exception ) {
				if ( is_a( $exception, 'Google_Service_Exception' ) ) {
					$err = $exception->getErrors();
				}
				// continue.
			}
		}

		if ( ! $connect_ok ) {
			return false;
		}

		MainWPGADB::Instance()->updateGASettingGlobal( 'availableSites', json_encode( $availableSites ) );
		MainWPGADB::Instance()->updateGASettingGlobal( 'propertyIds', json_encode( $propertyIds ) );

		global $mainWPGoogleAnalyticsExtensionActivator;

		$dbwebsites  = apply_filters( 'mainwp_getsites', $mainWPGoogleAnalyticsExtensionActivator->getChildFile(), $mainWPGoogleAnalyticsExtensionActivator->getChildKey(), null );
		$auto_assign = MainWPGADB::Instance()->getGASettingGlobal( 'auto_assign' );

		if ( $dbwebsites ) {
			foreach ( $dbwebsites as $website ) {
				$website['ga_id']      = MainWPGADB::Instance()->getGAId( $website['id'] );
				$website['gas_id']     = MainWPGADB::Instance()->getGASId( $website['id'] );
				$website['ga_item_id'] = MainWPGADB::Instance()->getGAItemID( $website['id'] );

				// $url = preg_replace('/http(s?):\/\/(.*?)(\/?)$/', '${2}', $website['url']);
				// $url = preg_replace('/(https?:\/\/(.*?))(\/?)$/', '${1}', $website['url']);
				$url = preg_replace( '/^https?:\/\//', '', $website['url'] );
				$url = trim( $url, '/' );
				$url = preg_replace( '/^www\./', '', $url );

				if ( $auto_assign ) {
					foreach ( $availableSites as $availableSite ) {
						$avail_url = preg_replace( '/^https?:\/\//', '', $availableSite['url'] );
						$avail_url = trim( $avail_url, '/' );
						$avail_url = preg_replace( '/^www\./', '', $avail_url );

						if ( $url == $avail_url ) {
							  // update this website to that id!
							if ( $website['ga_id'] == '' ) {
								$ret = MainWPGADB::Instance()->updateGAId( $website['id'], $availableSite['id'], $availableSite['gas_id'], $availableSite['ga_item_id'] );
								if ( is_object( $ret ) && $ret->ga_id && $ret->gas_id ) {
										  $website['ga_id']      = $ret->ga_id; // to fix bug
										  $website['gas_id']     = $ret->gas_id;
										  $website['ga_item_id'] = $ret->ga_item_id;
								}
							}
							  break;
						}
					}
				}

				// Check if the site is still available!
				if ( ! empty( $website['ga_item_id'] ) ) { // to  fix bug
					if ( ! in_array( $website['ga_item_id'] . '_' . $website['ga_id'] . '_' . $website['gas_id'], $availableIds ) ) {
						// remove GA
						MainWPGADB::Instance()->removeGAId( $website['id'] );
					}
				}
			}
		}
		return true;
	}

	// Disconnects the google account
	public static function disconnect( $gaId ) {
		MainWPGADB::Instance()->removeGAIds( $gaId );
		// Config:
		MainWPGADB::Instance()->removeGASettings( $gaId );
		MainWPGADB::Instance()->updateGASettingGlobal( 'lastupdate', 0 );
		// if  (isset($_SESSION['MainWP_GA_Access_Token'])) {
		// unset($_SESSION['MainWP_GA_Access_Token']);
		// }
	}

	public static function connect() {
		$ga_account_name  = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
		$ga_client_id     = sanitize_text_field( $_POST['client_id'] );
		$ga_client_secret = sanitize_text_field( $_POST['client_secret'] );

		if ( empty( $ga_client_id ) ) {
			die( json_encode( array( 'error' => __( 'Client ID is empty!', 'mainwp-google-analytics-extension' ) ) ) );
		}

		$data = array(
			'client_id'       => $ga_client_id,
			'client_secret'   => $ga_client_secret,
			'ga_account_name' => $ga_account_name,
		);

		$ga_entry = MainWPGADB::Instance()->updateGAEntry( $data );

		if ( empty( $ga_entry ) ) {
			die( json_encode( array( 'error' => __( 'Google Analytics entry could not be created.', 'mainwp-google-analytics-extension' ) ) ) );
		}

		self::autoloadVendor();

		try {
			$client = new Google_Client();
			$client->setApplicationName( 'Google Analytics Application' );
			$client->setClientId( $ga_client_id );
			$client->setClientSecret( $ga_client_secret );
			$client->setAccessType( 'offline' );
			$client->setApprovalPrompt( 'force' );
			$client->setPrompt( 'consent' );
			$client->setRedirectUri( self::$redirect_uri );
			$client->setScopes( array( Google_Service_Analytics::ANALYTICS_READONLY ) );

			$output['url'] = $client->createAuthUrl();

			global $current_user;
			$userId = $current_user->ID;

			update_user_option( $userId, 'mainwp_ga_processing_oauth_ga_id', $ga_entry->id );
		} catch ( Exception $e ) {
			$output['error'] = $e->getMessage();
		}

		die( json_encode( $output ) );
	}

	public static function verify( $gaId, $code ) {
		global $current_user;

		$ga_entry = MainWPGADB::Instance()->getGAEntryBy( 'id', $gaId );

		if ( empty( $ga_entry ) ) {
			return array( 'error' => __( 'Google Analytics entry is empty.', 'mainwp-google-analytics-extension' ) );
		}

		$client_id     = $ga_entry->client_id;
		$client_secret = $ga_entry->client_secret;

		self::autoloadVendor();

		try {
			$client = new Google_Client();
			$client->setApplicationName( 'Google Analytics Application' );
			$client->setClientId( $client_id );
			$client->setClientSecret( $client_secret );
			$client->setAccessType( 'offline' );
			$client->setRedirectUri( self::$redirect_uri );

			$service = new Google_Service_Analytics( $client );
			$client->authenticate( $code );
			$authArr = $client->getAccessToken();

			// error_log(print_r($authArr, true));

			$update = array(
				'client_id'     => $client_id,
				'enabled'       => 0,
				'refresh_token' => '',
				'ga_name'       => '',
			);

			if ( ! empty( $authArr ) ) {
				MainWPGADB::Instance()->updateGASettingGlobal( 'lastupdate', 0 );
				// if offline access requested and granted, get refresh token
				if ( isset( $authArr['refresh_token'] ) ) {
					$update['refresh_token'] = $authArr['refresh_token'];
					$update['enabled']       = 1;
				}

				$props = $service->management_webproperties->listManagementWebproperties( '~all' );

				if ( is_object( $props ) && property_exists( $props, 'username' ) ) {
					$update['ga_name'] = $props['username'];
				}
			}

			MainWPGADB::Instance()->updateGAEntry( $update );
			return array( 'connection' => 'success' );
		} catch ( Exception $e ) {
			return array( 'error' => $e->getMessage() );
		}
	}

	public static function ga_auth( $gaId, $code ) {
		global $current_user;

		$ga_entry = MainWPGADB::Instance()->getGAEntryBy( 'id', $gaId );

		if ( empty( $ga_entry ) ) {
			return array( 'error' => __( 'Google Analytics entry is empty.', 'mainwp-google-analytics-extension' ) );
		}

		$client_id     = $ga_entry->client_id;
		$client_secret = $ga_entry->client_secret;

		self::autoloadVendor();

		try {
			$client = new Google_Client();
			$client->setApplicationName( 'Google Analytics Application' );
			$client->setClientId( $client_id );
			$client->setClientSecret( $client_secret );
			$client->setAccessType( 'offline' );
			$client->setRedirectUri( self::$redirect_uri );

			$service = new Google_Service_Analytics( $client );
			$client->authenticate( $code );
			$authArr = $client->getAccessToken();

			$update = array(
				'client_id'     => $client_id,
				'enabled'       => 0,
				'refresh_token' => '',
				'ga_name'       => '',
			);

			if ( ! empty( $authArr ) ) {

				MainWPGADB::Instance()->updateGASettingGlobal( 'lastupdate', 0 );
				// if offline access requested and granted, get refresh token
				if ( isset( $authArr['refresh_token'] ) ) {
					$update['refresh_token'] = $authArr['refresh_token'];
					$update['enabled']       = 1;
				}
				MainWPGADB::Instance()->updateGAEntry( $update ); // save refresh_token token

				if ( property_exists( $service, 'management_webproperties' ) ) {
					$props = $service->management_webproperties->listManagementWebproperties( '~all' );
					if ( is_object( $props ) && property_exists( $props, 'username' ) ) {
						MainWPGADB::Instance()->updateGAEntry(
							array(
								'client_id' => $client_id,
								'ga_name'   => $props['username'],
							)
						);
					}
				}
			}

			return array( 'connection' => 'success' );
		} catch ( Exception $e ) {
			return array( 'error' => $e->getMessage() );
		}
	}
	
	function mainwp_ga_disconnect() {
		self::disconnect( $_POST['gaId'] );
	}

	function mainwp_ga_connect() {
		self::connect();
	}

	function mainwp_ga_getstats() {
		$id = null;
		if ( isset( $_POST['id'] ) ) {
			$id = $_POST['id'];
			$id = $id;

			self::getStats( $id );
		}
		die();
	}

	function mainwp_delete_site( $website ) {
		MainWPGADB::Instance()->removeWebsite( $website->id );
	}

	function ga_delete_site( $websiteId ) {
		MainWPGADB::Instance()->removeWebsite( $websiteId );
	}

	function mainwp_update_site( $websiteId ) {
		// Update GA settings:
		if ( isset( $_POST['ga_id'] ) ) {
			if ( $_POST['ga_id'] == '' ) {
				MainWPGADB::Instance()->removeGAId( $websiteId );
			} else {
				$splitted = explode( '_', $_POST['ga_id'] );
				if ( count( $splitted ) > 1 ) {
					if ( count( $splitted ) == 2 ) {
						$ga_item_id = 0;
						$ga_id      = $splitted[0];
						$gas_id     = $splitted[1];
					} else {
						$ga_item_id = $splitted[0];
						$ga_id      = $splitted[1];
						$gas_id     = $splitted[2];
					}
					MainWPGADB::Instance()->updateGAId( $websiteId, $ga_id, $gas_id, $ga_item_id );
				}
			}
		}
	}

	function mainwp_extension_sites_edit_tablerow( $website ) {
		$gaSites = MainWPGADB::Instance()->getGASettingGlobal( 'availableSites' );

		if ( $gaSites != '' ) {
			$gaSites = json_decode( $gaSites, 1 );
		} else {
			$gaSites = array();
		}

		MainWPGAUtility::array_sort( $gaSites, 'url' );

		$websiteInfo = MainWPGADB::Instance()->getGAGASId( $website->id );

		?>
	<h3 class="ui dividing header"><?php esc_html_e( 'Google Analytics', 'mainwp-google-analytics-extension' ); ?></h3>
	<div class="ui grid field">
			<label class="six wide column middle aligned"><?php _e( 'Google Analytics account property', 'mainwp-google-analytics-extension' ); ?></label>
			<div class="ui six wide column">
				<?php
				$selected = '';
				foreach ( $gaSites as $gaSite ) {
					if ( ! empty( $websiteInfo ) && isset( $websiteInfo->ga_id ) && isset( $gaSite['id'] ) && ! empty( $gaSite['id'] ) ) {
						if ( $websiteInfo->ga_id == $gaSite['id'] ) {
							$selected = $gaSite['ga_item_id'] . '_' . $gaSite['id'] . '_' . $gaSite['gas_id'];
						}
					}
				}
				?>
				<div class="ui search selection dropdown">
					<input type="hidden" name="ga_id" value="<?php echo esc_html( $selected ); ?>">
					<i class="dropdown icon"></i>
					<div class="default text"><?php _e( 'Select property', 'mainwp-google-analytics-extension' ); ?></div>
					<div class="menu">
						<div class="item" data-value=""><?php _e( 'No property', 'mainwp-google-analytics-extension' ); ?></div>
						<?php foreach ( $gaSites as $gaSite ) : ?>
							<div class="item" data-value="<?php echo $gaSite['ga_item_id'] . '_' . $gaSite['id'] . '_' . $gaSite['gas_id']; ?>">
							<?php echo $gaSite['url']; ?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
