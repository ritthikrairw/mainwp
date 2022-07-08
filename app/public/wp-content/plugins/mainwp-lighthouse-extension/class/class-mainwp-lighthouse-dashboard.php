<?php

namespace MainWP\Extensions\Lighthouse;

class MainWP_Lighthouse_Dashboard {

	private static $order   = '';
	private static $orderby = '';

	// Singleton
	private static $instance = null;

	/**
	 * Get Instance
	 *
	 * Creates public static instance.
	 *
	 * @static
	 *
	 * @return MainWP_Lighthouse_Dashboard
	 */
	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {

	}

	public function admin_init() {
		add_action( 'wp_ajax_mainwp_lighthouse_perform_check_site', array( $this, 'ajax_perform_check' ) );
	}

	/**
	 * Audit Sites
	 *
	 * Audits sites via AJAX request.
	 */
	public function ajax_perform_check() {
		MainWP_Lighthouse_Admin::check_security();

		$item_id = isset( $_POST['item_id'] ) ? $_POST['item_id'] : null;
		$site_id = isset( $_POST['websiteId'] ) ? $_POST['websiteId'] : null;

		$urls_to_check = null;

		if ( $site_id ) {
			$dbwebsites = MainWP_Lighthouse_Admin::get_db_sites( array( $site_id ) );
			if ( is_array( $dbwebsites ) && ! empty( $dbwebsites ) ) {
				$website       = current( $dbwebsites );
				$urls_to_check = array(
					'urls'            => array(
						array(
							'url'     => $website->url,
							'site_id' => $website->id,
						),
					),
					'total_url_count' => 1,
				);
			}
		} elseif ( $item_id ) {
			$lihouse = MainWP_Lighthouse_DB::get_instance()->get_lighthouse_by( 'id', $item_id, ARRAY_A );
			if ( $lihouse ) {
				$urls_to_check = array(
					'urls'            => array(
						array(
							'url' => $lihouse['URL'],
							'id'  => $lihouse['id'],
						),
					),
					'total_url_count' => 1,
				);
			}
		}

		if ( $urls_to_check ) {
			$checkstatus = apply_filters( 'mainwp_lighthouse_check_status', false );
			if ( $checkstatus ) {
				$message = __( 'The API is busy checking other pages, please try again later.', 'mainwp-lighthouse-extension' );
			} else {
				$empty = empty( $urls_to_check ) || empty( $urls_to_check['urls'] );
				MainWP_Lighthouse_Utility::log_debug( 'total urls to check ::' . ( $empty ? 'empty' : count( $urls_to_check['urls'] ) ) );
				if ( ! $empty ) {
					MainWP_Lighthouse_Core::get_instance()->worker_start( $urls_to_check );
				}
				die( wp_json_encode( array( 'status' => 'success' ) ) ); // This URL has been scheduled for a check.
			}
			die( wp_json_encode( array( 'message' => $message ) ) );
		}

		die( wp_json_encode( array( 'error' => __( 'Not found the item!', 'mainwp-lighthouse-extension' ) ) ) );
	}

	/**
	 * Render Tabs on Lighthouse Page
	 *
	 * Renders the Lighthouse page tabs.
	 */
	public static function gen_tabs_general() {

		if ( MainWP_Lighthouse_Admin::handle_general_settings_post() ) {
			return;
		}

		if ( isset( $_GET['action'] ) && $_GET['action'] == 'start_audit' && isset( $_GET['lighthouse_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_GET['lighthouse_nonce'] ), 'lighthouse_nonce' ) ) {
			return self::load_child_sites_to_prepare( 'start_audit' );
		}

		$curent_tab = 'dashboard';

		if ( isset( $_GET['tab'] ) ) {
			if ( 'settings' == $_GET['tab'] ) {
				  $curent_tab = 'settings';
			}
		} elseif ( isset( $_POST['mwp_lighthouse_setting_submit'] ) ) {
			$curent_tab = 'settings';
		}

		$websites = MainWP_Lighthouse_Admin::get_websites();

		$sites_ids = array();
		if ( is_array( $websites ) ) {
			foreach ( $websites as $website ) {
				$sites_ids[] = $website['id'];
			}
		}

		$dbwebsites = MainWP_Lighthouse_Admin::get_db_sites( $sites_ids );

		$selected_group = 0;

		if ( isset( $_POST['mainwp_lighthouse_groups_select'] ) ) {
			$selected_group = intval( $_POST['mainwp_lighthouse_groups_select'] );
		}

		  $lighthouse_data = array();
		  $results         = MainWP_Lighthouse_DB::get_instance()->get_lighthouse_by( 'all' );

		foreach ( $results as $value ) {
			if ( ! empty( $value->site_id ) ) {
				$lighthouse_data[ $value->site_id ] = MainWP_Lighthouse_Utility::map_fields( $value, array( 'id', 'desktop_last_modified', 'mobile_last_modified', 'desktop_score', 'mobile_score', 'strategy', 'override' ) );
			}
		}

		  $dbwebsites_lighthouse = self::get_instance()->get_websites_lighthouse( $dbwebsites, $lighthouse_data, $selected_group );

		  unset( $dbwebsites );
		?>
		<div class="ui labeled icon inverted menu mainwp-sub-submenu" id="mainwp-lighthouse-menu">
			<a href="admin.php?page=Extensions-Mainwp-Lighthouse-Extension&tab=dashboard" class="item <?php echo ( $curent_tab == 'dashboard' ? 'active' : '' ); ?>"><i class="tasks icon"></i> <?php _e( 'Dashboard', 'mainwp-lighthouse-extension' ); ?></a>
			<a href="admin.php?page=Extensions-Mainwp-Lighthouse-Extension&tab=settings" class="item <?php echo ( $curent_tab == 'settings' ? 'active' : '' ); ?>"><i class="cog icon"></i> <?php _e( 'Settings', 'mainwp-lighthouse-extension' ); ?></a>
		</div>

		<?php if ( $curent_tab == 'dashboard' || $curent_tab == '' ) : ?>
			<div id="mainwp-lighthouse-dashboard-tab">
				<?php self::render_actions_bar(); ?>
				<div class="ui segment">
					<?php MainWP_Lighthouse_Admin::get_instance()->render_messages(); ?>
					<div class="ui message" id="mainwp-message-zone" style="display:none"></div>
					<?php self::gen_dashboard_tab( $dbwebsites_lighthouse ); ?>
				</div>
			</div>
		<?php endif; ?>
		<?php if ( $curent_tab == 'settings' ) : ?>
			<div class="ui segment" id="mainwp-lighthouse-settings-tab">
				<?php MainWP_Lighthouse_Admin::get_instance()->render_messages(); ?>
				<form id="mainwp-lighthouse-settings-form" method="post" action="admin.php?page=Extensions-Mainwp-Lighthouse-Extension&tab=settings" class="ui form">
					<?php MainWP_Lighthouse_Admin::render_settings(); ?>
				</form>
				<div class="ui clearing hidden divider"></div>
			</div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render Tabs on Site Lighthouse Page
	 *
	 * Renders the individual site Lighthouse page tabs.
	 */
	public static function gen_tabs_individual() {
		$current_site_id = intval( $_GET['id'] );
		$curent_tab      = 'reports';
		if ( isset( $_GET['tab'] ) && 'settings' == $_GET['tab'] ) {
			$curent_tab = 'settings';
		}
		MainWP_Lighthouse_Admin::handle_individual_settings_post( $current_site_id );
		?>
		<div class="ui labeled icon inverted menu mainwp-sub-submenu" id="mainwp-timecapsule-menu">
			<a href="admin.php?page=ManageSitesLighthouse&id=<?php echo intval( $current_site_id ); ?>&tab=reports" class="item <?php echo 'reports' == $curent_tab ? 'active' : ''; ?>"><i class="cog icon"></i><?php _e( 'Lighthouse', 'mainwp-lighthouse-extension' ); ?></a>
			<a href="admin.php?page=ManageSitesLighthouse&id=<?php echo intval( $current_site_id ); ?>&tab=settings" class="item <?php echo 'settings' == $curent_tab ? 'active' : ''; ?>"><i class="cog icon"></i><?php _e( 'Settings', 'mainwp-lighthouse-extension' ); ?></a>
		</div>
		<div class="ui segment">
			<?php if ( 'settings' == $curent_tab ) : ?>
			<form method="post" action="admin.php?page=ManageSitesLighthouse&id=<?php echo intval( $current_site_id ); ?>" class="ui form">
				<?php MainWP_Lighthouse_Admin::gen_individual_lighthouse_settings_box(); ?>
				<?php MainWP_Lighthouse_Admin::render_settings( true ); ?>
				<div class="ui divider"></div>
				<input type="submit" name="submit" id="submit" class="ui big green button" value="<?php _e( 'Save Settings', 'mainwp-lighthouse-extension' ); ?>">
			</form>
			<?php else : ?>
				<?php self::gen_reports_individual( $current_site_id ); ?>
			<?php endif; ?>
			<div class="ui clearing hidden divider"></div>
		</div>
		<?php
	}

	/**
	 * Generate Site Lighthouse Report
	 *
	 * Renders the individual site Lighthouse report.
	 *
	 * @param int $current_site_id Current child site ID.
	 */
	public static function gen_reports_individual( $current_site_id ) {
		$lighthouse_data = MainWP_Lighthouse_DB::get_instance()->get_lighthouse_by( 'site_id', $current_site_id );

		if ( empty( $lighthouse_data ) ) {
			return;
		}

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

		$desktop_others_data = array();
		$mobile_others_data  = array();

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

		if ( ! empty( $lighthouse_data->desktop_others_data ) ) {
			$desktop_others_data = json_decode( $lighthouse_data->desktop_others_data, true );
		}

		if ( ! empty( $lighthouse_data->mobile_others_data ) ) {
			$mobile_others_data = json_decode( $lighthouse_data->mobile_others_data, true );
		}

		$strategy = MainWP_Lighthouse_Utility::get_instance()->get_option( 'strategy' );
		if ( $lighthouse_data->override ) {
			$strategy = $lighthouse_data->strategy;
		}

		$desktop_performance_audit_refs    = isset( $desktop_others_data['categories']['performance']['auditRefs'] ) ? $desktop_others_data['categories']['performance']['auditRefs'] : array();
		$desktop_accessibility_audit_refs  = isset( $desktop_others_data['categories']['accessibility']['auditRefs'] ) ? $desktop_others_data['categories']['accessibility']['auditRefs'] : array();
		$desktop_best_practices_audit_refs = isset( $desktop_others_data['categories']['best-practices']['auditRefs'] ) ? $desktop_others_data['categories']['best-practices']['auditRefs'] : array();
		$desktop_seo_audit_refs            = isset( $desktop_others_data['categories']['seo']['auditRefs'] ) ? $desktop_others_data['categories']['seo']['auditRefs'] : array();
		$mobile_performance_audit_refs     = isset( $mobile_others_data['categories']['performance']['auditRefs'] ) && is_array( $mobile_others_data['categories']['performance']['auditRefs'] ) ? $mobile_others_data['categories']['performance']['auditRefs'] : array();
		$mobile_accessibility_audit_refs   = isset( $mobile_others_data['categories']['accessibility']['auditRefs'] ) && is_array( $mobile_others_data['categories']['accessibility']['auditRefs'] ) ? $mobile_others_data['categories']['accessibility']['auditRefs'] : array();
		$mobile_best_practices_audit_refs  = isset( $mobile_others_data['categories']['best-practices']['auditRefs'] ) && is_array( $mobile_others_data['categories']['best-practices']['auditRefs'] ) ? $mobile_others_data['categories']['best-practices']['auditRefs'] : array();
		$mobile_seo_audit_refs             = isset( $mobile_others_data['categories']['seo']['auditRefs'] ) && is_array( $mobile_others_data['categories']['seo']['auditRefs'] ) ? $mobile_others_data['categories']['seo']['auditRefs'] : array();

		$skip_audits = array(
			'first-contentful-paint',
			'speed-index',
			'largest-contentful-paint-element',
			'interactive',
			'total-blocking-time',
			'cumulative-layout-shift',
			'metrics',
			'main-thread-tasks',
			'network-requests',
			'script-treemap-data',
			'performance-budget',
			'network-server-latency',
			'timing-budget',
			'final-screenshot',
			'screenshot-thumbnails',
			'diagnostics',
		);

		?>
		<div class="ui segment">
			<?php if ( MainWP_Lighthouse_Utility::show_mainwp_message( 'mainwp-lighthouse-report-info-message' ) ) : ?>
				<div class="ui info message">
					<i class="close icon mainwp-notice-dismiss" notice-id="mainwp-lighthouse-report-info-message"></i>
					<div><?php echo __( 'Review the Lighthouse report for the child site.', 'mainwp-lighthouse-extension' ); ?></div>
					<div><?php echo __( 'All repot data is generated by the Google PageSpeed Insights API.', 'mainwp-lighthouse-extension' ); ?></div>
					<div><?php echo __( 'Click the score circles to toggle between category audits.', 'mainwp-lighthouse-extension' ); ?></div>
				</div>
			<?php endif; ?>
			<div class="ui <?php echo 'both' == $strategy ? 'two' : 'one'; ?> columns very relaxed tablet stackable grid">
				<?php if ( 'both' == $strategy || 'desktop' == $strategy ) : ?>
				<div class="top aligned strategy column" strategy="desktop">
					<h3 class="ui header"><i class="desktop icon"></i> <?php echo __( 'Desktop', 'mainwp-lighthouse-extension' ); ?></h3>
					<div class="ui styled fluid audits accordion">
						<div class="title">
							<i class="dropdown icon"></i> <?php echo __( 'Runtime Settings', 'mainwp-lighthouse-extension' ); ?>
						</div>
						<div class="content">
							<table class="ui celled striped table" id="mainwp-lighthouse-runtime-settings">
								<tbody class="content">
								<tr>
									<td><?php echo __( 'URL', 'mainwp-lighthouse-extension' ); ?></td>
									<td><?php echo $lighthouse_data->URL; ?></td>
								  </tr>
									<tr>
									<td><?php echo __( 'Fetch Time', 'mainwp-lighthouse-extension' ); ?></td>
									<td><?php echo MainWP_Lighthouse_Utility::format_timestamp( MainWP_Lighthouse_Utility::get_timestamp( $lighthouse_data->desktop_last_modified ) ); ?></td>
								  </tr>
									<?php if ( $desktop_others_data ) : ?>
									<tr>
									<td><?php echo __( 'User agent (host)', 'mainwp-lighthouse-extension' ); ?></td>
									<td><?php echo $desktop_others_data['hostUserAgent']; ?></td>
								  </tr>
									<tr>
									<td><?php echo __( 'User agent (network)', 'mainwp-lighthouse-extension' ); ?></td>
									<td><?php echo $desktop_others_data['networkUserAgent']; ?></td>
								  </tr>
									<tr>
									<td><?php echo __( 'CPU/Memory Power', 'mainwp-lighthouse-extension' ); ?></td>
									<td><?php echo $desktop_others_data['benchmarkIndex']; ?></td>
								  </tr>
									<tr>
									<td><?php echo __( 'Generated by', 'mainwp-lighthouse-extension' ); ?></td>
									<td><?php echo $desktop_others_data['lighthouseVersion']; ?></td>
								  </tr>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
					<div class="ui hidden divider"></div>
					<div class="ui four columns tablet stackable grid">
						<div class="center aligned middle aligned column">
						  <div data-tab="desktop-performance" class="mainwp-lighthouse-score ui massive <?php echo MainWP_Lighthouse_Utility::score_color_code( $desktop_performance ); ?> circular basic label" id="mainwp-lighthouse-desktop-performance"><?php echo esc_html( $desktop_performance ); ?></div>
						  <h3 class="ui header"><?php echo __( 'Performance', 'mainwp-lighthouse-extension' ); ?></h3>
						</div>
						<div class="center aligned middle aligned column">
						  <div data-tab="desktop-accessibility" class="mainwp-lighthouse-score ui massive <?php echo MainWP_Lighthouse_Utility::score_color_code( $desktop_accessibility_score ); ?> circular basic label" id="mainwp-lighthouse-desktop-accessibility"><?php echo esc_html( $desktop_accessibility_score ); ?></div>
						  <h3 class="ui header"><?php echo __( 'Accessibility', 'mainwp-lighthouse-extension' ); ?></h3>
						</div>
						<div class="center aligned middle aligned column">
							<div data-tab="desktop-best-practices" class="mainwp-lighthouse-score ui massive <?php echo MainWP_Lighthouse_Utility::score_color_code( $desktop_best_practices_score ); ?> circular basic label" id="mainwp-lighthouse-desktop-bestpractices"><?php echo esc_html( $desktop_best_practices_score ); ?></div>
							<h3 class="ui header"><?php echo __( 'Best Practices', 'mainwp-lighthouse-extension' ); ?></h3>
						</div>
						<div class="center aligned middle aligned column">
						  <div data-tab="desktop-seo" class="mainwp-lighthouse-score ui massive <?php echo MainWP_Lighthouse_Utility::score_color_code( $desktop_seo_score ); ?> circular basic label" id="mainwp-lighthouse-desktop-seo"><?php echo esc_html( $desktop_seo_score ); ?></div>
						  <h3 class="ui header"><?php echo __( 'SEO', 'mainwp-lighthouse-extension' ); ?></h3>
					</div>
				</div>
					<div class="ui hidden divider"></div>
					<div class="ui center aligned segment">
						<span class="mainwp-score-scale">
							<div class="ui mini horizontal list">
							  <div class="item">
								<i class="red circle icon"></i> 0 - 49
							  </div>
								<div class="item">
								<i class="yellow circle icon"></i> 50 - 89
							  </div>
								<div class="item">
								<i class="green circle icon"></i> 90 - 100
							  </div>
							</div>
						</span>
					</div>
					<div class="ui hidden divider"></div>
					<?php if ( $desktop_lab_data ) : ?>
						<div class="ui hidden divider"></div>
						<div class="ui hidden divider"></div>
					<div class="ui header"><?php echo __( 'Lab Data', 'mainwp-lighthouse-extension' ); ?></div>
						<div class="ui divider"></div>
						<div class="ui hidden divider"></div>
						<div class="ui middle aligned divided relaxed padded list">
							<?php foreach ( $desktop_lab_data as $item ) : ?>
							<div class="item">
							<div class="right floated content">
							  <div><?php echo esc_html( $item['displayValue'] ); ?></div>
							</div>
						<i class="<?php echo MainWP_Lighthouse_Utility::audit_color_code( $item['id'], $item['displayValue'] ); ?> icon"></i>
							<div class="content">
								<h4 class="header"><?php echo $item['title']; ?> <?php echo ( 'cumulative-layout-shift' == $item['id'] || 'largest-contentful-paint' == $item['id'] ) ? '<span data-tooltip="Core Web Vitals assessment." data-inverted=""><i class="bookmark blue icon"></i></span>' : ''; ?></h4>
				  <div class="description"><?php echo $item['description']; ?></div>
								</div>
								</div>
						<?php endforeach; ?>
								</div>
					<?php endif; ?>
					<?php if ( $desktop_others_data ) : ?>
						<div class="ui hidden divider"></div>
						<div class="ui hidden divider"></div>
						<div class="ui ten columns stackable grid" id="mainwp-lighthouse-desktop-screenshots">
							<?php foreach ( $desktop_others_data['audits_data']['screenshot-thumbnails']['details']['items'] as $screenshot ) : ?>
							<div class="center aligned column" data-tooltip="<?php echo 'Timing: ' . $screenshot['timing']; ?>" data-inverted="" data-position="top center" >
								<img class="ui bordered image" src="<?php echo $screenshot['data']; ?>" alt="<?php echo $lighthouse_data->URL; ?>" />
							</div>
							<?php endforeach; ?>
						</div>
						<div class="ui active tab" data-tab="desktop-performance">
							<div class="ui hidden divider"></div>
							<div class="ui hidden divider"></div>
							<div class="ui header"><?php echo __( 'Performance Audits', 'mainwp-lighthouse-extension' ); ?></div>
							<div class="ui divider"></div>
							<?php echo self::audit_status_legend(); ?>
							<div class="ui styled fluid audits accordion" category="performance">
								<?php foreach ( $desktop_others_data['audits_data'] as $audit ) : ?>
									<?php
									if ( in_array( $audit['id'], $skip_audits ) ) {
										continue;
									}
									?>
									<?php if ( array_search( $audit['id'], array_column( $desktop_performance_audit_refs, 'id' ) ) !== false ) : ?>
									<div class="title" audit-id="<?php echo $audit['id']; ?>" id="<?php echo $audit['id']; ?>" type="<?php echo $audit['scoreDisplayMode']; ?>">
										<i class="dropdown icon"></i>
										<?php echo preg_replace( '/\`(.*?)\`/', '<code>$1</code>', esc_html( $audit['title'] ) ); ?><span><?php echo isset( $audit['displayValue'] ) ? ' - ' . $audit['displayValue'] : ''; ?></span>
										<?php echo MainWP_Lighthouse_Utility::get_audit_status( $audit ); ?>
									</div>
									<div class="content" audit-id="<?php echo $audit['id']; ?>">
										<?php
										$description = esc_html( $audit['description'] );
										$description = preg_replace( '/\[(.*?)\]\s*\(((?:http:\/\/|https:\/\/)(?:.+))\)/', '<a href="$2" target="_blank">$1</a>', $description );
										$description = preg_replace( '/\`(.*?)\`/', '<code>$1</code>', $description );
										echo $description;
										?>
										<?php echo self::render_audit_items( $audit ); ?>
									</div>
									<?php endif; ?>
								<?php endforeach; ?>
							</div>
						</div>
						<div class="ui tab" data-tab="desktop-accessibility">
							<div class="ui hidden divider"></div>
							<div class="ui hidden divider"></div>
							<div class="ui header"><?php echo __( 'Accessibility Audits', 'mainwp-lighthouse-extension' ); ?></div>
							<div class="ui divider"></div>
							<?php echo self::audit_status_legend(); ?>
							<div class="ui styled fluid audits accordion" category="accessibility">
								<?php foreach ( $desktop_others_data['audits_data'] as $audit ) : ?>
									<?php if ( array_search( $audit['id'], array_column( $desktop_accessibility_audit_refs, 'id' ) ) !== false ) : ?>
									<div class="title" audit-id="<?php echo $audit['id']; ?>" id="<?php echo $audit['id']; ?>" type="<?php echo $audit['scoreDisplayMode']; ?>">
										<i class="dropdown icon"></i>
										<?php echo preg_replace( '/\`(.*?)\`/', '<code>$1</code>', esc_html( $audit['title'] ) ); ?><span><?php echo isset( $audit['displayValue'] ) ? ' - ' . $audit['displayValue'] : ''; ?></span>
										<?php echo MainWP_Lighthouse_Utility::get_audit_status( $audit ); ?>
									</div>
									<div class="content" audit-id="<?php echo $audit['id']; ?>">
										<?php
										$description = esc_html( $audit['description'] );
										$description = preg_replace( '/\[(.*?)\]\s*\(((?:http:\/\/|https:\/\/)(?:.+))\)/', '<a href="$2" target="_blank">$1</a>', $description );
										$description = preg_replace( '/\`(.*?)\`/', '<code>$1</code>', $description );
										echo $description;
										?>
										<?php echo self::render_audit_items( $audit ); ?>
									</div>
									<?php endif; ?>
								<?php endforeach; ?>
							</div>
						</div>
						<div class="ui tab" data-tab="desktop-best-practices">
							<div class="ui hidden divider"></div>
							<div class="ui hidden divider"></div>
							<div class="ui header"><?php echo __( 'Best Practices Audits', 'mainwp-lighthouse-extension' ); ?></div>
						<div class="ui divider"></div>
						<?php echo self::audit_status_legend(); ?>
						<div class="ui styled fluid audits accordion" category="best-practices">
							<?php foreach ( $desktop_others_data['audits_data'] as $audit ) : ?>
									<?php if ( array_search( $audit['id'], array_column( $desktop_best_practices_audit_refs, 'id' ) ) !== false ) : ?>
								<div class="title" audit-id="<?php echo $audit['id']; ?>" id="<?php echo $audit['id']; ?>" type="<?php echo $audit['scoreDisplayMode']; ?>">
								<i class="dropdown icon"></i>
										<?php echo preg_replace( '/\`(.*?)\`/', '<code>$1</code>', esc_html( $audit['title'] ) ); ?><span><?php echo isset( $audit['displayValue'] ) ? ' - ' . $audit['displayValue'] : ''; ?></span>
										<?php echo MainWP_Lighthouse_Utility::get_audit_status( $audit ); ?>
							</div>
								<div class="content" audit-id="<?php echo $audit['id']; ?>">
										<?php
										$description = esc_html( $audit['description'] );
										$description = preg_replace( '/\[(.*?)\]\s*\(((?:http:\/\/|https:\/\/)(?:.+))\)/', '<a href="$2" target="_blank">$1</a>', $description );
										$description = preg_replace( '/\`(.*?)\`/', '<code>$1</code>', $description );
										echo $description;
										?>
										<?php echo self::render_audit_items( $audit ); ?>
							</div>
									<?php endif; ?>
							<?php endforeach; ?>
						</div>
						</div>
						<div class="ui tab" data-tab="desktop-seo">
							<div class="ui hidden divider"></div>
							<div class="ui hidden divider"></div>
							<div class="ui header"><?php echo __( 'SEO Audits', 'mainwp-lighthouse-extension' ); ?></div>
							<div class="ui divider"></div>
							<?php echo self::audit_status_legend(); ?>
							<div class="ui styled fluid audits accordion" category="seo">
								<?php foreach ( $desktop_others_data['audits_data'] as $audit ) : ?>
									<?php if ( array_search( $audit['id'], array_column( $desktop_seo_audit_refs, 'id' ) ) !== false ) : ?>
									<div class="title" audit-id="<?php echo $audit['id']; ?>" id="<?php echo $audit['id']; ?>" type="<?php echo $audit['scoreDisplayMode']; ?>">
										<i class="dropdown icon"></i>
										<?php echo preg_replace( '/\`(.*?)\`/', '<code>$1</code>', esc_html( $audit['title'] ) ); ?><span><?php echo isset( $audit['displayValue'] ) ? ' - ' . $audit['displayValue'] : ''; ?></span>
										<?php echo MainWP_Lighthouse_Utility::get_audit_status( $audit ); ?>
									</div>
									<div class="content" audit-id="<?php echo $audit['id']; ?>">
										<?php
										$description = esc_html( $audit['description'] );
										$description = preg_replace( '/\[(.*?)\]\s*\(((?:http:\/\/|https:\/\/)(?:.+))\)/', '<a href="$2" target="_blank">$1</a>', $description );
										$description = preg_replace( '/\`(.*?)\`/', '<code>$1</code>', $description );
										echo $description;
										?>
										<?php echo self::render_audit_items( $audit ); ?>
									</div>
									<?php endif; ?>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>
								</div>
				<?php endif; ?>

				<?php if ( 'both' == $strategy || 'mobile' == $strategy ) : ?>
				<div class="top aligned strategy column" strategy="mobile">
					<div class="ui header"><i class="mobile alternate icon"></i> <?php echo __( 'Mobile', 'mainwp-lighthouse-extension' ); ?></div>
					<div class="ui fluid styled accordion">
						<div class="title">
							<i class="dropdown icon"></i> <?php echo __( 'Runtime Settings', 'mainwp-lighthouse-extension' ); ?>
						</div>
						<div class="content">
							<table class="ui celled striped table" id="mainwp-lighthouse-runtime-settings">
								<tbody class="content">
								<tr>
									<td><?php echo __( 'URL', 'mainwp-lighthouse-extension' ); ?></td>
									<td><?php echo $lighthouse_data->URL; ?></td>
								  </tr>
									<tr>
									<td><?php echo __( 'Fetch Time', 'mainwp-lighthouse-extension' ); ?></td>
										<td><?php echo MainWP_Lighthouse_Utility::format_timestamp( MainWP_Lighthouse_Utility::get_timestamp( $lighthouse_data->mobile_last_modified ) ); ?></td>
								  </tr>
									<?php if ( $mobile_others_data ) : ?>
									<tr>
									<td><?php echo __( 'User agent (host)', 'mainwp-lighthouse-extension' ); ?></td>
									<td><?php echo $mobile_others_data['hostUserAgent']; ?></td>
								  </tr>
									<tr>
									<td><?php echo __( 'User agent (network)', 'mainwp-lighthouse-extension' ); ?></td>
									<td><?php echo $mobile_others_data['networkUserAgent']; ?></td>
								  </tr>
									<tr>
									<td><?php echo __( 'CPU/Memory Power', 'mainwp-lighthouse-extension' ); ?></td>
									<td><?php echo $mobile_others_data['benchmarkIndex']; ?></td>
								  </tr>
									<tr>
									<td><?php echo __( 'Generated by', 'mainwp-lighthouse-extension' ); ?></td>
									<td><?php echo $mobile_others_data['lighthouseVersion']; ?></td>
								  </tr>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
					<div class="ui hidden divider"></div>
					<div class="ui four columns tablet stackable grid">
						<div class="center aligned middle aligned column">
						  <div data-tab="mobile-performance" class="mainwp-lighthouse-score ui massive <?php echo MainWP_Lighthouse_Utility::score_color_code( $mobile_performance ); ?> circular basic label" id="mainwp-lighthouse-mobile-performance"><?php echo esc_html( $mobile_performance ); ?></div>
						  <h3 class="ui header"><?php echo __( 'Performance', 'mainwp-lighthouse-extension' ); ?></h3>
								</div>
						<div class="center aligned middle aligned column">
						  <div data-tab="mobile-accessibility" class="mainwp-lighthouse-score ui massive <?php echo MainWP_Lighthouse_Utility::score_color_code( $mobile_accessibility_score ); ?> circular basic label" id="mainwp-lighthouse-mobile-accessibility"><?php echo esc_html( $mobile_accessibility_score ); ?></div>
						  <h3 class="ui header"><?php echo __( 'Accessibility', 'mainwp-lighthouse-extension' ); ?></h3>
								</div>
						<div class="center aligned middle aligned column">
						  <div data-tab="mobile-best-practices" class="mainwp-lighthouse-score ui massive <?php echo MainWP_Lighthouse_Utility::score_color_code( $mobile_best_practices_score ); ?> circular basic label" id="mainwp-lighthouse-mobile-bestpractices"><?php echo esc_html( $mobile_best_practices_score ); ?></div>
						  <h3 class="ui header"><?php echo __( 'Best Practices', 'mainwp-lighthouse-extension' ); ?></h3>
								</div>
						<div class="center aligned middle aligned column">
						  <div data-tab="mobile-seo" class="mainwp-lighthouse-score ui massive <?php echo MainWP_Lighthouse_Utility::score_color_code( $mobile_seo_score ); ?> circular basic label" id="mainwp-lighthouse-mobile-seo"><?php echo esc_html( $mobile_seo_score ); ?></div>
						  <h3 class="ui header"><?php echo __( 'SEO', 'mainwp-lighthouse-extension' ); ?></h3>
								</div>
										</div>
					<div class="ui hidden divider"></div>
					<div class="ui center aligned segment">
						<span class="mainwp-score-scale">
							<div class="ui mini horizontal list">
							  <div class="item">
								<i class="red circle icon"></i> 0 - 49
							  </div>
								<div class="item">
								<i class="yellow circle icon"></i> 50 - 89
							  </div>
								<div class="item">
								<i class="green circle icon"></i> 90 - 100
							  </div>
							</div>
						</span>
					</div>
					<div class="ui hidden divider"></div>
					<?php if ( $mobile_lab_data ) : ?>
						<div class="ui hidden divider"></div>
						<div class="ui hidden divider"></div>
						<div class="ui header"><?php echo __( 'Lab Data', 'mainwp-lighthouse-extension' ); ?></div>
						<div class="ui divider"></div>
						<div class="ui hidden divider"></div>
						<div class="ui middle aligned divided relaxed list">
							<?php foreach ( $mobile_lab_data as $item ) : ?>
							<div class="item">
							<div class="right floated content">
							  <div><?php echo esc_html( $item['displayValue'] ); ?></div>
										</div>
							<i class="<?php echo MainWP_Lighthouse_Utility::audit_color_code( $item['id'], $item['displayValue'] ); ?> icon"></i>
							<div class="content">
									<h4 class="header"><?php echo $item['title']; ?> <?php echo ( 'cumulative-layout-shift' == $item['id'] || 'largest-contentful-paint' == $item['id'] ) ? '<span data-tooltip="Core Web Vitals assessment." data-inverted=""><i class="bookmark blue icon"></i></span>' : ''; ?></h4>
				  <div class="description"><?php echo $item['description']; ?></div>
						</div>
					</div>
						<?php endforeach; ?>
								</div>
					<?php endif; ?>
					<?php if ( $mobile_others_data ) : ?>
						<div class="ui hidden divider"></div>
						<div class="ui hidden divider"></div>
						<div class="ui ten columns stackable grid" id="mainwp-lighthouse-mobile-screenshots">
							<?php foreach ( $mobile_others_data['audits_data']['screenshot-thumbnails']['details']['items'] as $screenshot ) : ?>
							<div class="column" data-tooltip="<?php echo 'Timing: ' . $screenshot['timing']; ?>" data-inverted="" data-position="top center" >
								<img class="ui bordered image" src="<?php echo $screenshot['data']; ?>" alt="<?php echo $lighthouse_data->URL; ?>" />
							</div>
							<?php endforeach; ?>
						</div>
						<div class="ui active tab" data-tab="mobile-performance">
							<div class="ui hidden divider"></div>
							<div class="ui hidden divider"></div>
							<div class="ui header"><?php echo __( 'Performance Audits', 'mainwp-lighthouse-extension' ); ?></div>
							<div class="ui divider"></div>
							<?php echo self::audit_status_legend(); ?>
							<div class="ui styled fluid audits accordion" category="performance">
								<?php foreach ( $mobile_others_data['audits_data'] as $audit ) : ?>
									<?php if ( array_search( $audit['id'], array_column( $mobile_performance_audit_refs, 'id' ) ) !== false ) : ?>
										<?php
										if ( in_array( $audit['id'], $skip_audits ) ) {
											continue;
										}
										?>
									<div class="title" audit-id="<?php echo $audit['id']; ?>" id="<?php echo $audit['id']; ?>" type="<?php echo $audit['scoreDisplayMode']; ?>">
										<i class="dropdown icon"></i>
										<?php echo preg_replace( '/\`(.*?)\`/', '<code>$1</code>', esc_html( $audit['title'] ) ); ?><span><?php echo isset( $audit['displayValue'] ) ? ' - ' . $audit['displayValue'] : ''; ?></span>
										<?php echo MainWP_Lighthouse_Utility::get_audit_status( $audit ); ?>
									</div>
									<div class="content" audit-id="<?php echo $audit['id']; ?>">
										<?php
										$description = esc_html( $audit['description'] );
										$description = preg_replace( '/\[(.*?)\]\s*\(((?:http:\/\/|https:\/\/)(?:.+))\)/', '<a href="$2" target="_blank">$1</a>', $description );
										$description = preg_replace( '/\`(.*?)\`/', '<code>$1</code>', $description );
										echo $description;
										?>
										<?php echo self::render_audit_items( $audit ); ?>
									</div>
									<?php endif; ?>
								<?php endforeach; ?>
							</div>
						</div>
						<div class="ui tab" data-tab="mobile-accessibility">
							<div class="ui hidden divider"></div>
							<div class="ui hidden divider"></div>
							<div class="ui header"><?php echo __( 'Accessibility Audits', 'mainwp-lighthouse-extension' ); ?></div>
							<div class="ui divider"></div>
							<?php echo self::audit_status_legend(); ?>
							<div class="ui styled fluid audits accordion" category="accessibility">
								<?php foreach ( $mobile_others_data['audits_data'] as $audit ) : ?>
									<?php if ( array_search( $audit['id'], array_column( $mobile_accessibility_audit_refs, 'id' ) ) !== false ) : ?>
									<div class="title" audit-id="<?php echo $audit['id']; ?>" id="<?php echo $audit['id']; ?>" type="<?php echo $audit['scoreDisplayMode']; ?>">
										<i class="dropdown icon"></i>
										<?php echo preg_replace( '/\`(.*?)\`/', '<code>$1</code>', esc_html( $audit['title'] ) ); ?><span><?php echo isset( $audit['displayValue'] ) ? ' - ' . $audit['displayValue'] : ''; ?></span>
										<?php echo MainWP_Lighthouse_Utility::get_audit_status( $audit ); ?>
									</div>
									<div class="content" audit-id="<?php echo $audit['id']; ?>">
										<?php
										$description = esc_html( $audit['description'] );
										$description = preg_replace( '/\[(.*?)\]\s*\(((?:http:\/\/|https:\/\/)(?:.+))\)/', '<a href="$2" target="_blank">$1</a>', $description );
										$description = preg_replace( '/\`(.*?)\`/', '<code>$1</code>', $description );
										echo $description;
										?>
										<?php echo self::render_audit_items( $audit ); ?>
									</div>
									<?php endif; ?>
								<?php endforeach; ?>
							</div>
						</div>
						<div class="ui tab" data-tab="mobile-best-practices">
							<div class="ui hidden divider"></div>
							<div class="ui hidden divider"></div>
							<div class="ui header"><?php echo __( 'Best Practices Audits', 'mainwp-lighthouse-extension' ); ?></div>
							<div class="ui divider"></div>
							<?php echo self::audit_status_legend(); ?>
							<div class="ui styled fluid audits accordion" category="best-practices">
								<?php foreach ( $mobile_others_data['audits_data'] as $audit ) : ?>
									<?php if ( array_search( $audit['id'], array_column( $mobile_best_practices_audit_refs, 'id' ) ) !== false ) : ?>
									<div class="title" audit-id="<?php echo $audit['id']; ?>" id="<?php echo $audit['id']; ?>" type="<?php echo $audit['scoreDisplayMode']; ?>">
										<i class="dropdown icon"></i>
										<?php echo preg_replace( '/\`(.*?)\`/', '<code>$1</code>', esc_html( $audit['title'] ) ); ?><span><?php echo isset( $audit['displayValue'] ) ? ' - ' . $audit['displayValue'] : ''; ?></span>
										<?php echo MainWP_Lighthouse_Utility::get_audit_status( $audit ); ?>
									</div>
									<div class="content" audit-id="<?php echo $audit['id']; ?>">
										<?php
										$description = esc_html( $audit['description'] );
										$description = preg_replace( '/\[(.*?)\]\s*\(((?:http:\/\/|https:\/\/)(?:.+))\)/', '<a href="$2" target="_blank">$1</a>', $description );
										$description = preg_replace( '/\`(.*?)\`/', '<code>$1</code>', $description );
										echo $description;
										?>
										<?php echo self::render_audit_items( $audit ); ?>
									</div>
									<?php endif; ?>
								<?php endforeach; ?>
							</div>
						</div>
						<div class="ui tab" data-tab="mobile-seo">
							<div class="ui hidden divider"></div>
							<div class="ui hidden divider"></div>
							<div class="ui header"><?php echo __( 'SEO Audits', 'mainwp-lighthouse-extension' ); ?></div>
							<div class="ui divider"></div>
							<div class="ui hidden divider"></div>
							<?php echo self::audit_status_legend(); ?>
							<div class="ui styled fluid audits accordion" category="seo">
								<?php foreach ( $mobile_others_data['audits_data'] as $audit ) : ?>
									<?php if ( array_search( $audit['id'], array_column( $mobile_seo_audit_refs, 'id' ) ) !== false ) : ?>
									<div class="title" audit-id="<?php echo $audit['id']; ?>" id="<?php echo $audit['id']; ?>" type="<?php echo $audit['scoreDisplayMode']; ?>">
										<i class="dropdown icon"></i>
										<?php echo preg_replace( '/\`(.*?)\`/', '<code>$1</code>', esc_html( $audit['title'] ) ); ?><span><?php echo isset( $audit['displayValue'] ) ? ' - ' . $audit['displayValue'] : ''; ?></span>
										<?php echo MainWP_Lighthouse_Utility::get_audit_status( $audit ); ?>
									</div>
									<div class="content" audit-id="<?php echo $audit['id']; ?>">
										<?php
										$description = esc_html( $audit['description'] );
										$description = preg_replace( '/\[(.*?)\]\s*\(((?:http:\/\/|https:\/\/)(?:.+))\)/', '<a href="$2" target="_blank">$1</a>', $description );
										$description = preg_replace( '/\`(.*?)\`/', '<code>$1</code>', $description );
										echo $description;
										?>
										<?php echo self::render_audit_items( $audit ); ?>
									</div>
									<?php endif; ?>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>
								</div>
				<?php endif; ?>
								</div>
								</div>
		<script type="text/javascript">
		jQuery( document ).ready( function ($) {
			jQuery( '.ui.accordion' ).accordion();
		} );
		</script>
								<?php
	}

	/**
	 * Audit Status Legend
	 *
	 * Renders the audit status legend
	 */
	public static function audit_status_legend() {
		?>
		<div class="ui secondary segment">
			<div class="item ui three column grid">
				<div class="column">
					<div class="ui list">
						<div class="item">
							<i class="red circle icon"></i>
						  <div class="content"><a href="#" class="mainwp-lighthouse-audits-filter" type="failed" data-tooltip="Click to show only failed audits." data-position="top center" data-inverted=""><?php echo __( 'Failed audit', 'mainwp-lighthouse-extension' ); ?></a></div>
						</div>
						<div class="item">
							<i class="green circle icon"></i>
							<div class="content"><a href="#" class="mainwp-lighthouse-audits-filter" type="passed" data-tooltip="Click to show only passed audits." data-position="top center" data-inverted=""><?php echo __( 'Passed audit', 'mainwp-lighthouse-extension' ); ?></a></div>
						</div>
					</div>
				</div>
				<div class="column">
					<div class="ui list">
						<div class="item">
							<i class="grey circle icon"></i>
						<div class="content"><a href="#" class="mainwp-lighthouse-audits-filter" type="not-applicable" data-tooltip="Click to show only not applicable audits." data-position="top center" data-inverted=""><?php echo __( 'Not applicable audit', 'mainwp-lighthouse-extension' ); ?></a></div>
						</div>
						<div class="item">
							<i class="grey outline circle icon"></i>
						<div class="content"><a href="#" class="mainwp-lighthouse-audits-filter" type="diagnostics" data-tooltip="Click to show only diagnostics audits." data-position="top center" data-inverted=""><?php echo __( 'Diagnostics audit', 'mainwp-lighthouse-extension' ); ?></a></div>
						</div>
					</div>
				</div>
				<div class="column">
					<div class="ui list">
						<div class="item">
							<i class="black circle icon"></i>
						<div class="content"><a href="#" class="mainwp-lighthouse-audits-filter" type="manual" data-tooltip="Click to show only items that should be checked manually." data-position="top center" data-inverted=""><?php echo __( 'Items to manually check', 'mainwp-lighthouse-extension' ); ?></a></div>
						</div>
						<div class="item">
							<i class="grey bullseye icon"></i>
							<div class="content"><a href="#" class="mainwp-lighthouse-audits-filter" type="all" data-tooltip="Click to show all items." data-position="top center" data-inverted=""><?php echo __( 'All audits', 'mainwp-lighthouse-extension' ); ?></a></div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Audit Items
	 *
	 * Renders the audit details items
	 *
	 * @param array $audit Audit data.
	 */
	public static function render_audit_items( $audit ) {
		if ( ! isset( $audit['details'] ) || empty( $audit['details'] ) ) {
			return;
		}
		if ( isset( $audit['details']['items'] ) && ! empty( $audit['details']['items'] ) ) :
			?>
			<div class="ui segment mainwp-lighthouse-audit-details">
				<?php if ( 'table' == $audit['details']['type'] || 'opportunity' == $audit['details']['type'] ) : ?>
				<table class="ui table">
					<thead>
						<tr>
						<?php foreach ( $audit['details']['headings'] as $heading ) : ?>							
							<th>
							<?php
							if ( isset( $heading['text'] ) ) {
								echo esc_html( $heading['text'] );
							} elseif ( isset( $heading['label'] ) ) {
								echo esc_html( $heading['label'] );
							}
							?>
							</th>
						<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $audit['details']['items'] as $item ) : ?>
							<tr>
								<?php foreach ( $audit['details']['headings'] as $heading ) : ?>
									<td><?php echo isset( $heading['key'] ) && isset( $item[ $heading['key'] ] ) && ! is_array( $item[ $heading['key'] ] ) ? $item[ $heading['key'] ] : ''; ?></td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php endif; ?>
			</div>
			<?php
		endif;
	}

	/**
	 * Render Actions Bar
	 *
	 * Renders the progress modal.
	 *
	 * @param string $doAction Action to do.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function load_child_sites_to_prepare( $doAction ) {

		$websites  = MainWP_Lighthouse_Admin::get_websites();
		$sites_ids = array();
		if ( is_array( $websites ) ) {
			foreach ( $websites as $website ) {
				$sites_ids[] = $website['id'];
			}
		}

		$dbwebsites = MainWP_Lighthouse_Admin::get_db_sites( $sites_ids );

		$all_the_plugin_sites = array();

		if ( $dbwebsites ) {
			foreach ( $dbwebsites as $website ) {
				$all_the_plugin_sites[] = MainWP_Lighthouse_Utility::map_fields( $website, array( 'id', 'name' ) );
			}
		}

		if ( $doAction == 'start_audit' ) {
			$doAction = 'audit_pages';
		}

		if ( count( $all_the_plugin_sites ) > 0 ) {
			?>
			<div class="ui modal" id="mainwp-lighthouse-sync-modal">
				<div class="header"><?php _e( 'Lighthouse Audit', 'mainwp-lighthouse-extension' ); ?></div>
				<div class="ui green progress mainwp-modal-progress">
					<div class="bar"><div class="progress"></div></div>
					<div class="label"></div>
				</div>
				<div class="scrolling content">
					<div class="ui message" id="mainwp-lighthouse-modal-progress-feedback" style="display:none"></div>
					<div class="ui relaxed divided list">
						<?php foreach ( $all_the_plugin_sites as $website ) : ?>
							<div class="item mainwpProccessSitesItem" siteid="<?php echo $website['id']; ?>" status="queue">
								<a href="admin.php?page=managesites&dashboard=<?php echo $website['id']; ?>" data-tooltip="<?php esc_attr_e( 'Go to the site Overview page.', 'mainwp-lighthouse-extension' ); ?>" data-inverted="" data-position="right center"><?php echo $website['name']; ?></a>
								<span class="right floated status"><span data-tooltip="<?php esc_attr_e( 'Pending.', 'mainwp-lighthouse-extension' ); ?>" data-inverted="" data-position="left center"><i class="clock outline icon"></i></span></span>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="actions">
					<div class="ui cancel reload button"><?php _e( 'Close', 'mainwp-lighthouse-extension' ); ?></div>
				</div>
			</div>
			<script>
			  jQuery( document ).ready( function($) {
				  jQuery( '#mainwp-lighthouse-sync-modal' ).modal( 'show' );
				  mainwp_lighthouse_action_start_next( '<?php echo $doAction; ?>' );
			  } );
			</script>
			<?php
			return true;
		} else {
			?>
			  <div class="ui yellow message"><?php _e( 'Sites not found.', 'mainwp-lighthouse-extension' ); ?></div>
			<?php
			return false;
		}
	}

	/**
	 * Render Actions Bar
	 *
	 * Renders the actions bar on the Dashboard tab.
	 */
	public static function render_actions_bar() {
		$page  = 'admin.php?page=Extensions-Mainwp-Lighthouse-Extension';
		$nonce = wp_create_nonce( 'lighthouse_nonce' );
		?>
		<div class="mainwp-actions-bar">
			<div class="ui two columns grid">
				<div class="column ui mini form">
					<select class="ui mini dropdown" id="mwp_lighthouse_bulk_action">
							<option value="-1"><?php _e( 'Bulk actions', 'mainwp-lighthouse-extension' ); ?></option>
							<option value="check-pages"><?php _e( 'Run Audit', 'mainwp-lighthouse-extension' ); ?></option>
						<option value="open-wpadmin"><?php _e( 'Go to WP Admin', 'mainwp-lighthouse-extension' ); ?></option>
						<option value="open-frontpage"><?php _e( 'Go to Site', 'mainwp-lighthouse-extension' ); ?></option>
						</select>
						<input type="button" name="mwp_lighthouse_action_btn" id="mwp_lighthouse_action_btn" class="ui basic mini button" value="<?php _e( 'Apply', 'mainwp-lighthouse-extension' ); ?>"/>
					<?php do_action( 'mainwp_lighthouse_actions_bar_left' ); ?>
					</div>
				<div class="right aligned middle aligned column">
					<?php if ( $worker_status = apply_filters( 'mainwp_lighthouse_check_status', false ) ) : ?>
						<?php if ( ! get_option( 'mainwp_lighthouse_abort_scan' ) ) : ?>
							<a href="<?php echo $page; ?>&amp;tab=dashboard&amp;action=abort-scan&amp;none_action=<?php echo $nonce; ?>" class="ui mini red button" data-tooltip="<?php echo __( 'Audit in progress. Click the button to abort it.', 'mainwp-lighthouse-extension' ); ?>" data-inverted="" data-position="bottom right"><?php _e( 'Abort Current Audit', 'mainwp-lighthouse-extension' ); ?></a>
						<?php else : ?>
							<a href="<?php echo $page; ?>&amp;tab=dashboard" class="ui mini red button" data-tooltip="<?php echo __( 'Audit in progress. Click the button to abort it.', 'mainwp-lighthouse-extension' ); ?>" data-inverted="" data-position="bottom right" disabled><?php _e( 'Abort Current Audit', 'mainwp-lighthouse-extension' ); ?></a>
						<?php endif; ?>
					<?php else : ?>
						<a href="admin.php?page=Extensions-Mainwp-Lighthouse-Extension&tab=dashboard&action=start_audit&lighthouse_nonce=<?php echo wp_create_nonce( 'lighthouse_nonce' ); ?>" id="mainwp-lighthouse-start-scan-button" class="ui mini green button" data-tooltip="<?php echo __( 'Click to start Lighthouse audit for all sites.', 'mainwp-lighthouse-extension' ); ?>" data-inverted="" data-position="bottom right"><?php _e( 'Audit All Sites', 'mainwp-lighthouse-extension' ); ?></a>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the Dashbaord tab
	 *
	 * Renders the dashbaord tab content - Lighthouse table
	 *
	 * @param array $websites Child sites.
	 */
	public static function gen_dashboard_tab( $websites ) {
		$_orderby = 'name';
		$_order   = 'desc';

		self::$order   = $_order;
		self::$orderby = $_orderby;

		$strategy = MainWP_Lighthouse_Utility::get_instance()->get_option( 'strategy' );
		?>
		<?php if ( MainWP_Lighthouse_Utility::show_mainwp_message( 'mainwp-lighthouse-settings-info-message' ) ) : ?>
			<div class="ui info message">
				<i class="close icon mainwp-notice-dismiss" notice-id="mainwp-lighthouse-dashbboard-info-message"></i>
				<?php echo sprintf( __( 'Get reports on the performance of your child sites on both mobile and desktop devices. For more information, review %1$shelp documentation%2$s.', 'mainwp-lighthouse-extension' ), '<a href="https://kb.mainwp.com/docs/mainwp-lighthouse-extension/" target="_blank">', '</a>' ); ?>
			</div>
		<?php endif; ?>
		<table class="ui unstackable single line table" id="mainwp-lighthouse-sites-table" style="width:100%" columns="<?php echo 'both' == $strategy ? '14' : '9'; ?>">
			<thead>
				<tr>
					<th class="no-sort collapsing check-column"><span class="ui checkbox"><input type="checkbox"></span></th>
					<th id="site" class="collapsing"><?php _e( 'Site', 'mainwp-lighthouse-extension' ); ?></th>
					<th id="sign-in" class="no-sort collapsing"><i class="sign in icon"></i></th>
					<th id="url" class="collapsing"><?php _e( 'URL', 'mainwp-lighthouse-extension' ); ?></th>
					<?php if ( 'both' == $strategy || 'desktop' == $strategy ) : ?>
					<th id="desktop-last-check" class="collapsing"><i class="desktop icon"></i> <?php _e( 'Last Audit', 'mainwp-lighthouse-extension' ); ?></th>
					<th id="desktop-performance" class="center aligned collapsing"><i class="desktop icon"></i> <?php _e( 'Performance', 'mainwp-lighthouse-extension' ); ?></th>
					<th id="desktop-accessibility" class="center aligned collapsing"><i class="desktop icon"></i> <?php _e( 'Accessibility', 'mainwp-lighthouse-extension' ); ?></th>
					<th id="desktop-practices" class="center aligned collapsing"><i class="desktop icon"></i> <?php _e( 'Best Practices', 'mainwp-lighthouse-extension' ); ?></th>
					<th id="desktop-seo" class="center aligned collapsing"><i class="desktop icon"></i> <?php _e( 'SEO', 'mainwp-lighthouse-extension' ); ?></th>
					<?php endif; ?>
					<?php if ( 'both' == $strategy || 'mobile' == $strategy ) : ?>
					<th id="mobile-last-check" class="collapsing"><i class="mobile alternate  icon"></i> <?php _e( 'Last Audit', 'mainwp-lighthouse-extension' ); ?></th>
					<th id="mobile-performance" class="center aligned collapsing"><i class="mobile alternate icon"></i> <?php _e( 'Performance', 'mainwp-lighthouse-extension' ); ?></th>
					<th id="mobile-accessibility" class="center aligned collapsing"><i class="mobile alternate icon"></i> <?php _e( 'Accessibility', 'mainwp-lighthouse-extension' ); ?></th>
					<th id="mobile-practices" class="center aligned collapsing"><i class="mobile alternate icon"></i> <?php _e( 'Best Practices', 'mainwp-lighthouse-extension' ); ?></th>
					<th id="mobile-seo" class="center aligned collapsing"><i class="mobile alternate icon"></i> <?php _e( 'SEO', 'mainwp-lighthouse-extension' ); ?></th>
					<?php endif; ?>					
					<th id="actions" class="no-sort collapsing right aligned"></th>
				</tr>
			</thead>
			<tbody>
					<?php self::get_dashboard_table_row( $websites ); ?>
			</tbody>
			<tfoot>
				<tr>
					<th class="no-sort collapsing check-column"><span class="ui checkbox"><input type="checkbox"></span></th>
					<th><?php _e( 'Site', 'mainwp-lighthouse-extension' ); ?></th>
					<th class="no-sort collapsing"><i class="sign in icon"></i></th>
					<th><?php _e( 'URL', 'mainwp-lighthouse-extension' ); ?></th>
					<?php if ( 'both' == $strategy || 'desktop' == $strategy ) : ?>
					<th><i class="desktop icon"></i> <?php _e( 'Last Audit', 'mainwp-lighthouse-extension' ); ?></th>
					<th><i class="desktop icon"></i> <?php _e( 'Performance', 'mainwp-lighthouse-extension' ); ?></th>
					<th><i class="desktop icon"></i> <?php _e( 'Accessibility', 'mainwp-lighthouse-extension' ); ?></th>
					<th><i class="desktop icon"></i> <?php _e( 'Best Practices', 'mainwp-lighthouse-extension' ); ?></th>
					<th><i class="desktop icon"></i> <?php _e( 'SEO', 'mainwp-lighthouse-extension' ); ?></th>
					<?php endif; ?>
					<?php if ( 'both' == $strategy || 'mobile' == $strategy ) : ?>
					<th><i class="mobile alternate icon"></i> <?php _e( 'Last Audit', 'mainwp-lighthouse-extension' ); ?></th>
					<th><i class="mobile alternate icon"></i> <?php _e( 'Performance', 'mainwp-lighthouse-extension' ); ?></th>
					<th><i class="mobile alternate icon"></i> <?php _e( 'Accessibility', 'mainwp-lighthouse-extension' ); ?></th>
					<th><i class="mobile alternate icon"></i> <?php _e( 'Best Practices', 'mainwp-lighthouse-extension' ); ?></th>
					<th><i class="mobile alternate icon"></i> <?php _e( 'SEO', 'mainwp-lighthouse-extension' ); ?></th>
					<?php endif; ?>					
					<th class="no-sort collapsing"></th>
				</tr>
			</tfoot>
		</table>
		<?php self::render_screen_options(); ?>
		<script type="text/javascript">
		var responsive = true;
		if( jQuery( window ).width() > 1140 ) {
			responsive = false;
		}
		jQuery( document ).ready( function () {
			$lighthouse_sites_table = jQuery( '#mainwp-lighthouse-sites-table' ).DataTable( {
				"stateSave": true,
				"stateDuration": 0,
				"scrollX": true,
				"colReorder" : {
					fixedColumnsLeft: 1,
					fixedColumnsRight: 1
				},
				"responsive": responsive,
				"lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
				"columnDefs": [ { "targets": 'no-sort', "orderable": false } ],
				"order": [ [ 1, "asc" ] ],
				"language": { "emptyTable": "No websites found." },
				"drawCallback": function( settings ) {
					jQuery('#mainwp-lighthouse-sites-table .ui.checkbox').checkbox();
					jQuery( '#mainwp-lighthouse-sites-table .ui.dropdown').dropdown();
					mainwp_datatable_fix_menu_overflow();
				},
			} );

			_init_lighthouse_sites_screen = function() {
				jQuery( '#mainwp-lighthouse-sites-screen-options-modal input[type=checkbox][id^="mainwp_show_column_"]' ).each( function() {
					var check_id = jQuery( this ).attr( 'id' );
					col_id = check_id.replace( "mainwp_show_column_", "" );
					try {
						$lighthouse_sites_table.column( '#' + col_id ).visible( jQuery(this).is( ':checked' ) );
						if ( check_id.indexOf("mainwp_show_column_desktop") >= 0 ) {							
							col_id = check_id.replace( "mainwp_show_column_desktop", "" );
							$lighthouse_sites_table.column( '#mobile' + col_id ).visible( jQuery(this).is( ':checked' ) ); // to set mobile columns.						
						}
					} catch(err) {
						// to fix js error.
					}
				} );
			};
			_init_lighthouse_sites_screen();

			mainwp_lighthouse_sites_screen_options = function () {
				jQuery( '#mainwp-lighthouse-sites-screen-options-modal' ).modal( {
					allowMultiple: true,
					onHide: function () {
					}
				} ).modal( 'show' );

				jQuery( '#lighthouse-sites-screen-options-form' ).submit( function() {
					if ( jQuery('input[name=reset_lighthousesites_columns_order]').attr('value') == 1 ) {
						$lighthouse_sites_table.colReorder.reset();
					}					
					jQuery( '#mainwp-lighthouse-sites-screen-options-modal' ).modal( 'hide' );
				} );
				return false;
			};

		} );
		</script>

		<?php
	}

	/**
	 * Get Lighthouse Table Row
	 *
	 * Gets the Lighthouse dashbaord table row.
	 *
	 * @param array $websites Child sites.
	 */
	public static function get_dashboard_table_row( $websites ) {

		$strategy       = MainWP_Lighthouse_Utility::get_instance()->get_option( 'strategy' );
		$strategy_child = MainWP_Lighthouse_Utility::get_instance()->get_option( 'strategy' );

		foreach ( $websites as $website ) {
			$strategy_child = $strategy;

			if ( 1 == $website['override'] ) {
				$strategy_child = $website['strategy'];
			}

			$website_id = $website['id'];

			$lighthouse = MainWP_Lighthouse_DB::get_instance()->get_lighthouse_by( 'site_id', $website_id );

			if ( empty( $lighthouse ) ) {
				continue;
			}

			?>
			<tr class="" website-id="<?php echo $website_id; ?>" item-id="<?php echo $website['item_id']; ?>">
				<td class="check-column"><span class="ui checkbox" data-tooltip="<?php esc_attr_e( 'Click to select the site.', 'mainwp-lighthouse-extension' ); ?>" data-inverted="" data-position="right center"><input type="checkbox" name="checked[]"></span></td>
				<td class="mainwp-site-cell"><a href="admin.php?page=managesites&dashboard=<?php echo $website_id; ?>" data-tooltip="<?php esc_attr_e( 'Go to the site overview.', 'mainwp-lighthouse-extension' ); ?>" data-inverted="" data-position="right center" class="mainwp-site-name-link"><?php echo stripslashes( $website['name'] ); ?></a></td>
				<td><a target="_blank" href="admin.php?page=SiteOpen&newWindow=yes&websiteid=<?php echo $website_id; ?>&_opennonce=<?php echo wp_create_nonce( 'mainwp-admin-nonce' ); ?>" class="open_newwindow_wpadmin" data-tooltip="<?php esc_attr_e( 'Jump to the WP Admin.', 'mainwp-lighthouse-extension' ); ?>" data-inverted="" data-position="right center"><i class="sign in icon"></i></a></td>
				<td class="mainwp-url-cell"><a href="<?php echo $website['url']; ?>" target="_blank" class="open_site_url" data-tooltip="<?php esc_attr_e( 'Go to the website.', 'mainwp-lighthouse-extension' ); ?>" data-inverted="" data-position="right center"><?php echo $website['url']; ?></a></td>
				<?php if ( 'both' == $strategy || 'desktop' == $strategy ) : ?>
					<td><?php echo MainWP_Lighthouse_Utility::format_timestamp( MainWP_Lighthouse_Utility::get_timestamp( $lighthouse->desktop_last_modified ) ); ?></td>
					<td class="center aligned"><span class="ui <?php echo MainWP_Lighthouse_Utility::score_color_code( $lighthouse->desktop_score ); ?> label"><?php echo $lighthouse->desktop_score; ?></span></td>
					<td class="center aligned"><span class="ui <?php echo MainWP_Lighthouse_Utility::score_color_code( $lighthouse->desktop_accessibility_score ); ?> label"><?php echo $lighthouse->desktop_accessibility_score; ?></span></td>
					<td class="center aligned"><span class="ui <?php echo MainWP_Lighthouse_Utility::score_color_code( $lighthouse->desktop_best_practices_score ); ?> label"><?php echo $lighthouse->desktop_best_practices_score; ?></span></td>
					<td class="center aligned"><span  class="ui <?php echo MainWP_Lighthouse_Utility::score_color_code( $lighthouse->desktop_seo_score ); ?> label"><?php echo $lighthouse->desktop_seo_score; ?></span></td>
				<?php endif; ?>
				<?php if ( 'both' == $strategy || 'mobile' == $strategy ) : ?>
					<td><?php echo MainWP_Lighthouse_Utility::format_timestamp( MainWP_Lighthouse_Utility::get_timestamp( $lighthouse->mobile_last_modified ) ); ?></td>
					<td class="center aligned"><span class="ui <?php echo MainWP_Lighthouse_Utility::score_color_code( $lighthouse->mobile_score ); ?> label"><?php echo $lighthouse->mobile_score; ?></span></td>
					<td class="center aligned"><span class="ui <?php echo MainWP_Lighthouse_Utility::score_color_code( $lighthouse->mobile_accessibility_score ); ?> label"><?php echo $lighthouse->mobile_accessibility_score; ?></span></td>
					<td class="center aligned"><span class="ui <?php echo MainWP_Lighthouse_Utility::score_color_code( $lighthouse->mobile_best_practices_score ); ?> label"><?php echo $lighthouse->mobile_best_practices_score; ?></span></td>
					<td class="center aligned"><span class="ui <?php echo MainWP_Lighthouse_Utility::score_color_code( $lighthouse->mobile_seo_score ); ?> label"><?php echo $lighthouse->mobile_seo_score; ?></span></td>
				<?php endif; ?>
				<td class="right aligned">
					<div class="ui left pointing dropdown icon mini basic green button" data-tooltip="<?php esc_attr_e( 'See more options', 'mainwp-lighthouse-extension' ); ?>" data-position="left center" data-inverted="">
						<a href="javascript:void(0)"><i class="ellipsis horizontal icon"></i></a>
						<div class="menu">
							<a class="item lighthouse-action-recheck" href="javascript:void(0)"><?php _e( 'Audit Site', 'mainwp-lighthouse-extension' ); ?></a>
							<a class="item" href="admin.php?page=ManageSitesLighthouse&id=<?php echo $website_id; ?>"><?php _e( 'Lighthouse Report', 'mainwp-lighthouse-extension' ); ?></a>
							<a class="item" href="admin.php?page=managesites&dashboard=<?php echo $website_id; ?>"><?php _e( 'Overview', 'mainwp-lighthouse-extension' ); ?></a>
							<a class="item" href="admin.php?page=managesites&id=<?php echo $website_id; ?>"><?php _e( 'Edit', 'mainwp-lighthouse-extension' ); ?></a>
						</div>
					</div>
				</td>
			</tr>
			<?php
		}
	}

	/**
	 * Get Websites
	 *
	 * Gets child sites to display in the Dashboard page.
	 *
	 * @param array $websites         Child sites.
	 * @param array $lighthouse_data  Lighthouse data.
	 * @param array $selected_group   Selected groups.
	 *
	 * @return array Array of child sites to display.
	 */
	public function get_websites_lighthouse( $websites, $lighthouse_data, $selected_group = 0 ) {
		$websites_plugin = array();

		if ( is_array( $websites ) && count( $websites ) ) {
			if ( empty( $selected_group ) ) {
				foreach ( $websites as $website ) {
					$site              = $this->get_lighthouse_site_data( $website, $lighthouse_data );
					$websites_plugin[] = $site;
				}
			} else {

				$group_websites = MainWP_Lighthouse_Admin::get_db_sites( array(), array( $selected_group ) );

				$sites = array();
				foreach ( $group_websites as $site ) {
					$sites[] = $site->id;
				}
				foreach ( $websites as $website ) {
					if ( $website && in_array( $website->id, $sites ) ) {
						$site              = $this->get_lighthouse_site_data( $website, $lighthouse_data );
						$websites_plugin[] = $site;
					}
				}
			}
		}

		// if search action.
		$search_sites = array();

		if ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ) {
			$find = trim( $_GET['s'] );
			foreach ( $websites_plugin as $website ) {
				if ( false !== stripos( $website['name'], $find ) || false !== stripos( $website['url'], $find ) ) {
					$search_sites[] = $website;
				}
			}
			$websites_plugin = $search_sites;
		}
		unset( $search_sites );

		return $websites_plugin;
	}

	/**
	 * Get Site Lighthouse Data
	 *
	 * Gets basic lighthouse data for a site.
	 *
	 * @param object $website         Child site object.
	 * @param array  $lighthouse_data Lighthouse data.
	 *
	 * @return array Child site array containng the Lighthouse data.
	 */
	public function get_lighthouse_site_data( $website, $lighthouse_data ) {
		$site                          = MainWP_Lighthouse_Utility::map_fields( $website, array( 'id', 'name', 'url' ) );
		$site_data                     = isset( $lighthouse_data[ $site['id'] ] ) ? $lighthouse_data[ $site['id'] ] : array();
		$site['desktop_last_modified'] = isset( $site_data['desktop_last_modified'] ) ? $site_data['desktop_last_modified'] : 0;
		$site['mobile_last_modified']  = isset( $site_data['mobile_last_modified'] ) ? $site_data['mobile_last_modified'] : 0;
		$site['desktop_score']         = isset( $site_data['desktop_score'] ) ? $site_data['desktop_score'] : 0;
		$site['mobile_score']          = isset( $site_data['mobile_score'] ) ? $site_data['mobile_score'] : 0;
		$site['strategy']              = isset( $site_data['strategy'] ) ? $site_data['strategy'] : 'both';
		$site['override']              = isset( $site_data['override'] ) ? $site_data['override'] : 0;
		$site['item_id']               = isset( $site_data['id'] ) ? $site_data['id'] : 0;
		return $site;
	}

	/**
	 * Get columns.
	 *
	 * @return array Array of column names.
	 */
	public static function get_columns() {
		return array(
			'site'                  => __( 'Site', 'mainwp-lighthouse-extension' ),
			'sign-in'               => '<i class="sign in icon"></i>',
			'url'                   => __( 'URL', 'mainwp-lighthouse-extension' ),
			'desktop-performance'   => __( 'Performance', 'mainwp-lighthouse-extension' ),
			'desktop-accessibility' => __( 'Accessibility', 'mainwp-lighthouse-extension' ),
			'desktop-practices'     => __( 'Best Practices', 'mainwp-lighthouse-extension' ),
			'desktop-seo'           => __( 'Seo', 'mainwp-lighthouse-extension' ),
			'desktop-last-check'    => __( 'Last Audit', 'mainwp-lighthouse-extension' ),
			'actions'               => __( 'Action', 'mainwp-lighthouse-extension' ),
		);
	}

	/**
	 * Render screen options.
	 *
	 * @return array Array of default column names.
	 */
	public static function render_screen_options() {

		$columns = self::get_columns();

		$show_cols = get_user_option( 'mainwp_settings_show_lighthouse_sites_columns' );

		if ( ! is_array( $show_cols ) ) {
			$show_cols = array();
		}

		?>
		<div class="ui modal" id="mainwp-lighthouse-sites-screen-options-modal">
			<div class="header"><?php esc_html_e( 'Screen Options', 'mainwp' ); ?></div>
			<div class="scrolling content ui form">
				<form method="POST" action="" id="lighthouse-sites-screen-options-form" name="lighthouse_sites_screen_options_form">
					<?php wp_nonce_field( 'mainwp-admin-nonce' ); ?>
					<input type="hidden" name="wp_nonce" value="<?php echo wp_create_nonce( 'LighthouseSitesScrOptions' ); ?>" />
						<div class="ui grid field">
							<label class="six wide column"><?php esc_html_e( 'Show columns', 'mainwp' ); ?></label>
							<div class="ten wide column">
								<ul class="mainwp_hide_wpmenu_checkboxes">
									<?php
									foreach ( $columns as $name => $title ) {
										?>
										<li>
											<div class="ui checkbox">
												<input type="checkbox"
												<?php
												$show_col = ! isset( $show_cols[ $name ] ) || ( 1 == $show_cols[ $name ] );
												if ( $show_col ) {
													echo 'checked="checked"';
												}
												?>
												id="mainwp_show_column_<?php echo esc_attr( $name ); ?>" name="mainwp_show_column_<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $name ); ?>">
												<label for="mainwp_show_column_<?php echo esc_attr( $name ); ?>" ><?php echo $title; ?></label>
												<input type="hidden" value="<?php echo esc_attr( $name ); ?>" name="show_columns_name[]" />
											</div>
										</li>
										<?php
									}
									?>
								</ul>
							</div>
					</div>
				</div>
			<div class="actions">
					<div class="ui two columns grid">
						<div class="left aligned column">
							<span data-tooltip="<?php esc_attr_e( 'Returns this page to the state it was in when installed. The feature also restores any column you have moved through the drag and drop feature on the page.', 'mainwp' ); ?>" data-inverted="" data-position="top center"><input type="button" class="ui button" name="reset" id="reset-lighthousesites-settings" value="<?php esc_attr_e( 'Reset Page', 'mainwp' ); ?>" /></span>
						</div>
						<div class="ui right aligned column">
					<input type="submit" class="ui green button" name="btnSubmit" id="submit-lighthousesites-settings" value="<?php esc_attr_e( 'Save Settings', 'mainwp' ); ?>" />
					<div class="ui cancel button"><?php esc_html_e( 'Close', 'mainwp' ); ?></div>
				</div>
					</div>
				</div>
				<input type="hidden" name="reset_lighthousesites_columns_order" value="0">
			</form>
		</div>
		<div class="ui small modal" id="mainwp-lighthouse-sites-site-preview-screen-options-modal">
			<div class="header"><?php esc_html_e( 'Screen Options', 'mainwp' ); ?></div>
			<div class="scrolling content ui form">
				<span><?php esc_html_e( 'Would you like to turn on home screen previews?  This function queries WordPress.com servers to capture a screenshot of your site the same way comments shows you preview of URLs.', 'mainwp' ); ?>
			</div>
			<div class="actions">
				<div class="ui ok button"><?php esc_html_e( 'Yes', 'mainwp' ); ?></div>
				<div class="ui cancel button"><?php esc_html_e( 'No', 'mainwp' ); ?></div>
			</div>
		</div>
		<script type="text/javascript">
			jQuery( document ).ready( function () {
				jQuery('#reset-lighthousesites-settings').on( 'click', function () {
					mainwp_confirm(__( 'Are you sure.' ), function(){
						jQuery('.mainwp_hide_wpmenu_checkboxes input[id^="mainwp_show_column_"]').prop( 'checked', false );
						//default columns
						var cols = ['site','url','actions','desktop-performance','desktop-accessibility','desktop-practices','desktop-seo'];
						jQuery.each( cols, function ( index, value ) {
							jQuery('.mainwp_hide_wpmenu_checkboxes input[id="mainwp_show_column_' + value + '"]').prop( 'checked', true );
						} );
						jQuery('input[name=reset_lighthousesites_columns_order]').attr('value',1);
						jQuery('#submit-lighthousesites-settings').click();
					}, false, false, true );
					return false;
				});
			} );
		</script>
		<?php
	}
}
