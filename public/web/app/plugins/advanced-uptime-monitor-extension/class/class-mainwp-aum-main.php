<?php
/**
 * MainWP AUM Main
 *
 * Initiates main extensino functins.
 *
 * @package MainWP/Extensions/AUM
 */

namespace MainWP\Extensions\AUM;

/**
 * Class MainWP_AUM_Main
 *
 * Initiates main extensino functins.
 */
class MainWP_AUM_Main {

	/**
	 * @var object $instance
	 */
	private static $instance = null;

	/**
	 * @var string $plugin_name
	 */
	public $plugin_name = 'Advanced Uptime Monitor Extension';

	/**
	 * @var string $plugin_handle
	 */
	public $plugin_handle = 'advanced-uptime-monitor-extension';

	/**
	 * @var string $plugin_dir
	 */
	public $plugin_dir;

	/**
	 * @var string $plugin_url
	 */
	protected $plugin_url;

	/**
	 * @var string $plugin_admin
	 */
	protected $plugin_admin = '';

	/**
	 * @var string $option
	 */
	protected $option;

	/**
	 * @var array $message_info
	 */
	public $message_info = array();

	/**
	 * @var string $plugin_slug
	 */
	private $plugin_slug;

	/**
	 * @var array $uptime_data_sites
	 */
	public $uptime_data_sites = null;

	/**
	 * @var string $uptime_service
	 */
	public $uptime_service = null;

	/**
	 * Create public static instance.
	 *
	 * @static
	 *
	 * @return object Class instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Class construtor.
	 */
	public function __construct() {

		$this->plugin_dir  = plugin_dir_path( __FILE__ );
		$this->plugin_url  = plugin_dir_url( __FILE__ );
		$this->plugin_slug = plugin_basename( __FILE__ );

		if ( is_admin() ) {
			// Load admin functionality.
			add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 2 );
			add_filter( 'mainwp_sitestable_getcolumns', array( $this, 'sitestable_getcolumns' ), 10 );
			add_filter( 'mainwp_sitestable_item', array( $this, 'sitestable_item' ), 10 );
		}
		// Load global functionality.
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_action( 'init', array( &$this, 'localization' ) );
		MainWP_AUM_DB_Install::instance()->install();
	}

	/**
	 * Hooks into admin init.
	 */
	public function admin_init() {
		MainWP_AUM_Main_Controller::instance()->admin_init();
		MainWP_AUM_UptimeRobot_Controller::instance()->admin_init();
		if ( isset( $_POST['aum_monitors_per_page'] ) ) {
			$per_page = intval( $_POST['aum_monitors_per_page'] );
			if ( ! in_array( $per_page, array( 10, 20, 30, 40, 50 ) ) ) {
				$per_page = 10;
			}
			update_option( 'mainwp_aum_setting_monitors_per_page', $per_page );
		}
	}

	/**
	 * Loads plugin text domain.
	 */
	public function localization() {
		load_plugin_textdomain( 'advanced-uptime-monitor-extension', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Sets custom column in the Manage sites table.
	 *
	 * @param array $columns Columns.
	 *
	 * @return array $columns Columns.
	 */
	public function sitestable_getcolumns( $columns ) {
		$columns['aum_status'] = __( 'Uptime Status', 'advanced-uptime-monitor-extension' );
		return $columns;
	}

	/**
	 * Renders Manage Sites column data.
	 *
	 * @param array $item Column item.
	 *
	 * @return array $item Column item.
	 */
	public function sitestable_item( $item ) {
		$site_url = str_replace( array( 'https://www.', 'http://www.', 'https://', 'http://', 'www.', '/' ), array( '', '', '', '', '', '' ), $item['url'] );
		if ( null === $this->uptime_service ) {
			$this->uptime_service = get_option( 'mainwp_aum_enabled_service', 'uptimerobot' );
		}
		if ( null === $this->uptime_data_sites ) {
			$this->uptime_data_sites = MainWP_AUM_UptimeRobot_Settings_Handle::instance()->get_uptime_data( $this->uptime_service );
		}

		if ( isset( $this->uptime_data_sites[ $site_url ] ) ) {
			$monitor            = $this->uptime_data_sites[ $site_url ];
			$item['aum_status'] = MainWP_AUM_Settings_Page::get_uptime_state( $this->uptime_service, $monitor );
		}
		return $item;
	}

	/**
	 * Renders the plugin row customizations.
	 *
	 * @param array  $plugin_meta Plugin meta data.
	 * @param string $plugin_file plugin file.
	 *
	 * @return array $plugin_meta Plugin meta data.
	 */
	public function plugin_row_meta( $plugin_meta, $plugin_file ) {
		if ( $this->plugin_slug != $plugin_file ) {
			return $plugin_meta;
		}

		$slug     = basename( $plugin_file, '.php' );
		$api_data = get_option( $slug . '_APIManAdder' );
		if ( ! is_array( $api_data ) || ! isset( $api_data['activated_key'] ) || 'Activated' != $api_data['activated_key'] || ! isset( $api_data['api_key'] ) || empty( $api_data['api_key'] ) ) {
			return $plugin_meta;
		}

		$plugin_meta[] = '<a href="?do=checkUpgrade" title="Check for updates.">Check for updates now</a>';
		return $plugin_meta;
	}

	/**
	 * Renders the widget.
	 */
	public function render_metabox() {
		$selected_service = get_option( 'mainwp_aum_enabled_service', 'uptimerobot' );
		?>
		<div class="ui grid">
			<div class="twelve wide column">
				<h3 class="ui header handle-drag">
			<?php esc_html_e( 'Monitors', 'advanced-uptime-monitor-extension' ); ?>
					<div class="sub header"><?php esc_html_e( 'Monitor your child sites', 'advanced-uptime-monitor-extension' ); ?></div>
				</h3>
			</div>
			<div class="four wide column right aligned"></div>
		</div>
		<input type="hidden" id="mainwp-aum-form-field-service" value="<?php echo esc_html( $selected_service ); ?>">
		<?php
			$site_id = isset( $_GET['dashboard'] ) ? intval( $_GET['dashboard'] ) : 0;
		?>
		<div id="aum_mainwp_widget_uptime_monitor_content" class="inside">
		<?php

		$total = 0;
		if ( 'betteruptime' == $selected_service && empty( $site_id ) ) {
			$total    = MainWP_AUM_DB::instance()->get_monitor_urls(
				'betteruptime',
				array(
					'conds'      => array(),
					'count_only' => true,
				)
			);
			$per_page = get_option( 'mainwp_aum_setting_monitors_per_page', 10 );
			?>
			<?php if ( $total > MAINWP_MONITOR_API_LIMIT_PER_PAGE ) { ?>
				<div class="ui stackable grid">						
							<div class="left aligned eight wide column">
								<form method="post" action="">							
									<label><?php _e( 'Show', 'advanced-uptime-monitor-extension' ); ?>
										<div class="aum-min-width" style="display: inline-block;">
											<select class="ui dropdown" id="aum_monitors_per_page" name="aum_monitors_per_page">
													<option value="10" <?php selected( $per_page, 10 ); ?>>10</option>
													<option value="20" <?php selected( $per_page, 20 ); ?>>20</option>
													<option value="30" <?php selected( $per_page, 30 ); ?>>30</option>
													<option value="40" <?php selected( $per_page, 40 ); ?>>40</option>
													<option value="50" <?php selected( $per_page, 50 ); ?>>50</option>
											</select> <?php _e( 'entries', 'advanced-uptime-monitor-extension' ); ?>
										</div>
									</label>				
								</form>
							</div>
							<div class="right aligned eight wide column">
								<div class="ui form">
									<label><?php _e( 'Search:', 'advanced-uptime-monitor-extension' ); ?><span class="ui input">
										<input type="search" id="mainwp-aum-monitors-widget-filter"  class="" placeholder="">
									</span>
								</label>
								</div>
						</div>			
				</div>
				<div class="ui section hidden divider"></div>
				<?php
			}
		}
		?>
		<div id="aum_mainwp_uptime_monitor_loading">
			<i class="fa fa-spinner fa-pulse"></i> <?php _e( 'Loading monitors...', 'advanced-uptime-monitor-extension' ); ?>
		</div>
		<div id="aum_mainwp_widget_uptime_monitor_content_inner" class="monitors"></div>
		</div>
		<script type="text/javascript">
			jQuery(document).ready(function () {
					mainwp_aum_metabox_get_monitors = function( pPage, pFilter ) {
						jQuery.ajax({
							url: ajaxurl,
							type: "POST",
							data: {
								action: 'mainwp_advanced_uptime_meta_box',
								site_id: '<?php echo esc_attr( $site_id ); ?>',
								get_page: pPage,
								searching: ( typeof pFilter !== 'undefined' ) ? pFilter : '',
								service: jQuery('#mainwp-aum-form-field-service').val(),				
								wp_nonce: '<?php echo esc_attr( wp_create_nonce( 'mainwp_aum_nonce_meta_box' ) ); ?>'
							},
							error: function () {
								jQuery('#aum_mainwp_uptime_monitor_loading').hide();
								jQuery('#aum_mainwp_widget_uptime_monitor_content_inner').show();
								jQuery('#aum_mainwp_widget_uptime_monitor_content_inner').html('Request timed out. Please, try again later.').fadeIn(2000);
							},
							success: function (response) {
								jQuery('#aum_mainwp_uptime_monitor_loading').hide();
								jQuery('#aum_mainwp_widget_uptime_monitor_content_inner').show();
								jQuery('#aum_mainwp_widget_uptime_monitor_content_inner').html(response).fadeIn(2000);
								jQuery('#aum_mainwp_widget_uptime_monitor_content .ui.dropdown').dropdown();
								jQuery('#mainwp_aum_monitor_select_page').dropdown();
								jQuery('#mainwp_aum_monitor_select_page').dropdown('setting', 'onChange', function( val ){
									mainwp_aum_metabox_get_monitors(val);
								});
							},
							timeout: 20000
						});
					};
					mainwp_aum_metabox_get_monitors( 1 );

					<?php if ( 'betteruptime' == $selected_service && empty( $site_id ) && $total > MAINWP_MONITOR_API_LIMIT_PER_PAGE ) { ?>
						jQuery( "#mainwp-aum-monitors-widget-filter" ).keyup(function( event ) {							
							setTimeout( function ()							
							{	
								var filter = jQuery('#mainwp-aum-monitors-widget-filter').val();
								jQuery('#aum_mainwp_uptime_monitor_loading').show();
								jQuery('#aum_mainwp_widget_uptime_monitor_content_inner').hide();
								mainwp_aum_metabox_get_monitors( 1, filter );
							}, 100 );							
						});
						jQuery("#aum_monitors_per_page").change(function() {
							jQuery(this).closest("form").submit();
						});
					<?php } ?>
			} );
	  </script>
			<?php
	}

	/**
	 * Widgets screen options.
	 *
	 * @param array $input Input.
	 *
	 * @return array $input Input.
	 */
	public function widgets_screen_options( $input ) {
		$input['advanced-aum-widget'] = __( 'Monitors', 'advanced-uptime-monitor-extension' );
		return $input;
	}

	/**
	 * Formats timestamp.
	 *
	 * @param string $timestamp Timestamp.
	 * @param string $localtime Localtime yes/no.
	 *
	 * @return string $timestamp Timestamp.
	 */
	public static function format_timestamp( $timestamp, $localtime = false ) {
		if ( $localtime ) {
			$offset    = get_option( 'gmt_offset' );
			$timestamp = $timestamp + $offset * 60 * 60;
		}
		return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
	}

	/**
	 * Method: build_params_string()
	 *
	 * Builds params string.
	 *
	 * @param array $params Request parameters.
	 *
	 * @return string Params string.
	 */
	public function build_params_string( $params ) {

		if ( empty( $params ) ) {
			return '';
		}

		$str_params = '?';
		foreach ( $params as $name => $val ) {
			$str_params .= '&' . $name . '=' . $val;
		}
		return rtrim( $str_params, '&' );
	}
}
