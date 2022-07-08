<?php
/**
 * MainWP Post Dripper
 *
 * Renders the Post Dripper page and handles processes.
 *
 * @package MainWP/Extensions/Post Dripper
 */

define( 'DRIP_MINUTELY_TESTING', false ); // Useed for developer tesing.

/**
 * Class MainWP_Dripper
 *
 * Initiates extension functins.
 */
class MainWP_Dripper {

	/**
	 * Class construtor.
	 */
	public function __construct() {
		add_action( 'mainwp_cron_jobs_list', array( $this, 'cron_job_info' ) );
	}

	/**
	 * Hooks into the init.
	 */
	public function init() {
		add_action( 'mainwp_post_dripper_cronposting', array( 'MainWP_Dripper', 'cron_posting' ) );
		$useWPCron = ( false === get_option( 'mainwp_wp_cron' ) ) || ( 1 == get_option( 'mainwp_wp_cron' ) );
		if ( false == ( $sched = wp_next_scheduled( 'mainwp_post_dripper_cronposting' ) ) ) {
			if ( $useWPCron ) {
				if ( defined( 'DRIP_MINUTELY_TESTING' ) && DRIP_MINUTELY_TESTING == true ) {
					wp_schedule_event( time(), 'minutely', 'mainwp_post_dripper_cronposting' );
				} else {
					wp_schedule_event( time(), 'hourly', 'mainwp_post_dripper_cronposting' );
				}
			}
		} else {
			if ( ! $useWPCron ) {
				wp_unschedule_event( $sched, 'mainwp_post_dripper_cronposting' );
			}
		}
	}

	/**
	 * Hooks into the admin_init.
	 */
	public function admin_init() {
		add_action( 'mainwp_save_bulkpost', array( &$this, 'save_meta_dripper_post' ) );
		add_action( 'mainwp_save_bulkpage', array( &$this, 'save_meta_dripper_page' ) );
		add_action( 'wp_ajax_mainwp_dripper_delete_post', array( &$this, 'dripper_delete_post' ) );
	}

	/**
	 * Hooks into the init_menu.
	 */
	public function init_menu() {
		add_submenu_page( 'mainwp_tab', 'Created Post Dripper', '<div class="mainwp-hidden">Created Post Dripper</div>', 'read', 'CreatedPostDripper', array( $this, 'created_post_dripper' ) );
		add_submenu_page( 'mainwp_tab', 'Created Page Dripper', '<div class="mainwp-hidden">Created Page Dripper</div>', 'read', 'CreatedPageDripper', array( $this, 'created_page_dripper' ) );
	}

	/**
	 * Renders the cron jobs info.
	 *
	 * @return void
	 */
	public function cron_job_info() {
		$lastEvent   = $nextEvent = '';
		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );
		$lastEvent   = wp_next_scheduled( 'mainwp_post_dripper_cronposting' ) - 3600;
		$nextEvent   = wp_next_scheduled( 'mainwp_post_dripper_cronposting' )
		?>
		<tr>
			<td><?php echo __( 'Drip posts and pages', 'mainwp-post-dripper-extension' ); ?></td>
			<td><?php echo 'mainwp_post_dripper_cronposting'; ?></td>
			<td><?php echo __( 'Once hourly', 'mainwp-vulnerabilities-checker-extension' ); ?></td>
			<td><?php echo date( $date_format, $lastEvent ) . ' ' . date( $time_format, $lastEvent ); ?></td>
			<td><?php echo date( $date_format, $nextEvent ) . ' ' . date( $time_format, $nextEvent ); ?></td>
		</tr>
		<?php
	}

	/**
	 * Deletes dripper post.
	 *
	 * @return void
	 */
	public function dripper_delete_post() {
		global $wpdb;
		$post_id = intval( $_REQUEST['post_id'] );
		$ret     = array( 'success' => false );
		if ( $post_id && wp_delete_post( $post_id ) ) {
			$ret['success'] = true;
		}
		echo json_encode( $ret );
		exit;
	}

	/**
	 * Renders the extension page.
	 *
	 * @return void
	 */
	public static function render() {
		$bulkpost_args     = array(
			'post_type'      => 'bulkpost',
			'posts_per_page' => -1,
			'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
			'post_parent'    => null,
			'meta_key'       => '_mainwp_post_dripper',
			'meta_value'     => 'yes',
			'orderby'        => 'post_title',
			'order'          => 'asc',
		);
		$bulkpost_drippers = get_posts( $bulkpost_args );

		$bulkpage_args     = array(
			'post_type'      => 'bulkpage',
			'posts_per_page' => -1,
			'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
			'post_parent'    => null,
			'meta_key'       => '_mainwp_post_dripper',
			'meta_value'     => 'yes',
			'orderby'        => 'post_title',
			'order'          => 'asc',
		);
		$bulkpage_drippers = get_posts( $bulkpage_args );

		$current_tab = '';

		if ( isset( $_GET['tab'] ) ) {
			if ( $_GET['tab'] == 'post-drips' ) {
				$current_tab = 'post-drips';
			} elseif ( $_GET['tab'] == 'page-drips' ) {
				$current_tab = 'page-drips';
			}
		}

		?>
		<div class="ui labeled icon inverted menu mainwp-sub-submenu" id="mainwp-post-dripper-menu">
			<a href="admin.php?page=Extensions-Mainwp-Post-Dripper-Extension&tab=post-drips" class="item <?php echo ( $current_tab == 'post-drips' || $current_tab == '' ? 'active' : '' ); ?>"><i class="file outline icon"></i> <?php _e( 'Post Drips', 'mainwp-post-dripper-extension' ); ?></a>
			<a href="admin.php?page=Extensions-Mainwp-Post-Dripper-Extension&tab=page-drips" class="item <?php echo ( $current_tab == 'page-drips' ? 'active' : '' ); ?>"><i class="file icon"></i> <?php _e( 'Page Drips', 'mainwp-post-dripper-extension' ); ?></a>
		</div>

		<?php if ( $current_tab == 'post-drips' || $current_tab == '' ) : ?>
		<div class="ui segment active tab" data-tab="mainwp-post-drips" id="mainwp-post-drips">
			<table class="ui selectable single line table" id="mainwp-post-drips-table" style="width:100%">
				<thead>
					<tr>
						<th><?php echo __( 'Title', 'mainwp-post-dripper-extension' ); ?></th>
						<th><?php echo __( 'Date', 'mainwp-post-dripper-extension' ); ?></th>
						<th><?php echo __( 'Frequency', 'mainwp-post-dripper-extension' ); ?></th>
						<th><?php echo __( 'Status', 'mainwp-post-dripper-extension' ); ?></th>
						<th><?php echo __( 'Last Published', 'mainwp-post-dripper-extension' ); ?></th>
						<th class="collapsing no-sort"></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( is_array( $bulkpost_drippers ) && count( $bulkpost_drippers ) > 0 ) : ?>
						<?php	self::render_drippers_table_content( $bulkpost_drippers, 'bulkpost', 'post_title' ); ?>
					<?php endif; ?>
				</tbody>
				<tfoot class="full-width">
					<tr>
						<th colspan="6">
							<a href="admin.php?page=PostBulkAdd" class="ui green button"><?php echo __( 'Create New', 'mainwp-post-dripper-extension' ); ?></a>
						</th>
					</tr>
				</tfoot>
			</table>
		</div>
		<?php endif; ?>

		<?php if ( $current_tab == 'page-drips' ) : ?>
		<div class="ui segment active tab" data-tab="mainwp-page-drips" id="mainwp-page-drips">
			<table class="ui selectable single line table" id="mainwp-page-drips-table" style="width:100%;">
				<thead>
					<tr>
						<th><?php echo __( 'Title', 'mainwp-post-dripper-extension' ); ?></th>
						<th><?php echo __( 'Date', 'mainwp-post-dripper-extension' ); ?></th>
						<th><?php echo __( 'Frequency', 'mainwp-post-dripper-extension' ); ?></th>
						<th><?php echo __( 'Status', 'mainwp-post-dripper-extension' ); ?></th>
						<th><?php echo __( 'Last Published', 'mainwp-post-dripper-extension' ); ?></th>
						<th class="collapsing no-sort"></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( is_array( $bulkpage_drippers ) && count( $bulkpage_drippers ) > 0 ) : ?>
						<?php	self::render_drippers_table_content( $bulkpage_drippers, 'bulkpage', 'post_title' ); ?>
					<?php endif; ?>
				</tbody>
				<tfoot class="full-width">
					<tr>
						<th colspan="6">
							<a href="admin.php?page=PageBulkAdd" class="ui green button"><?php echo __( 'Create New', 'mainwp-post-dripper-extension' ); ?></a>
						</th>
					</tr>
				</tfoot>
			</table>
		</div>
		<?php endif; ?>
		<script type="text/javascript">
			jQuery( document ).ready( function() {
				jQuery( '#mainwp-post-drips-table' ).DataTable( {
					"searching" : true,
					"stateSave":  true,
					"paging": true,
					"info": true,
					"scrollX" : true,
					"lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
					"columnDefs": [ {
						"targets": 'no-sort',
						"orderable": false
					} ],
					"language" : { "emptyTable": "No active drips found." }
				} );
				jQuery( '#mainwp-page-drips-table' ).DataTable( {
					"searching" : true,
					"stateSave":  true,
					"paging": true,
					"info": true,
					"scrollX" : true,
					"lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
					"columnDefs": [ {
						"targets": 'no-sort',
						"orderable": false
					} ],
					"language" : { "emptyTable": "No active drips found." }
				} );

				mainwp_datatable_fix_menu_overflow();

			} );
		</script>
		<?php
	}

	/**
	 * Renders the drippers tables content.
	 *
	 * @param object $drippers Drippers objects.
	 * @param string $type     Dripper type.
	 * @param string $orderby  Order by parameter.
	 *
	 * @return void
	 */
	public static function render_drippers_table_content( $drippers, $type, $orderby ) {
		global $mainwp_DripperExtensionActivator;
		$websites = apply_filters( 'mainwp_getsites', $mainwp_DripperExtensionActivator->get_child_file(), $mainwp_DripperExtensionActivator->get_child_key(), null );

		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );

		foreach ( $drippers as $dripper ) {
			$total_sites = get_post_meta( $dripper->ID, '_mainwp_post_dripper_total_drip_sites', true );

			if ( empty( $total_sites ) ) {
				$total_sites = count( $websites );
			}

			$last_posted_sites = get_post_meta( $dripper->ID, '_mainwp_post_dripper_last_posted_sites', true );

			$count_posted = 0;

			if ( is_array( $last_posted_sites ) ) {
				$count_posted = count( $last_posted_sites );
			}

			$_status = $count_posted . '/' . $total_sites . ' ' . __( 'Published', 'mainwp-post-dripper-extension' );

			$nb_sites       = get_post_meta( $dripper->ID, '_mainwp_post_dripper_sites_number', true );
			$nb_time        = get_post_meta( $dripper->ID, '_mainwp_post_dripper_time_number', true );
			$select_time    = get_post_meta( $dripper->ID, '_mainwp_post_dripper_select_time', true );
			$_frequecy      = $nb_sites . ' Sites / ' . $nb_time . ' ' . ucfirst( $select_time );
			$last_time      = get_post_meta( $dripper->ID, '_mainwp_post_dripper_last_drip_time', true );
			$last_time      = floor( (float) $last_time );
			$last_published = get_post_meta( $dripper->ID, '_mainwp_post_dripper_last_published', true );

			if ( ! empty( $last_time ) ) {
				$last_published = date( $date_format, $last_time ) . ' ' . date( $time_format, $last_time );
			} else {
				$last_published = __( 'Pending', 'mainwp-post-dripper-extension' );
			}

			$last_drip_info = get_post_meta( $dripper->ID, '_mainwp_post_dripper_drip_info', true );

			$str_drip_info = '';
			if ( is_array( $last_drip_info ) && count( $last_drip_info ) > 0 ) {
				$str_drip_info .= '<div class="ui divided list">';
				usort( $last_drip_info, array( 'MainWP_Dripper', 'drip_info_timesort' ) );
				foreach ( $last_drip_info as $info ) {
					$info_time      = floor( (float) $info['time'] );
					$str_drip_info .= '<div class="item">';

					$str_drip_info .= date( $date_format, $info_time ) . ' ' . date( $time_format, $info_time );
					$str_drip_info .= ' - ' . '<a href="' . $info['site_url'] . '" target="_blank">' . $info['site_url'] . '</a>';
					if ( isset( $info['link'] ) ) {
						$str_drip_info .= '<span class="right floated">' . '<a href="' . $info['link'] . '" class="ui mini green basic button" target="_blank">View Post</a></span>';
					} elseif ( isset( $info['error'] ) ) {
						$str_drip_info .= '<span class="right floated">' . $info['error'] . '</span>';
					}

					$str_drip_info .= '</div>';
				}
				$str_drip_info .= '</div>';
			}

			$post_time = strtotime( $dripper->post_date );
			?>
			<tr id="post-<?php echo $dripper->ID; ?>">
				<td><?php echo $dripper->post_title; ?></td>
				<td><?php echo date( $date_format, $post_time ) . ' ' . date( $time_format, $post_time ); ?></td>
				<td><?php echo $_frequecy; ?></td>
				<td><?php echo $_status; ?></td>
				<td><?php echo $last_published; ?></td>
				<td class="right aligned">
					<div class="ui right pointing dropdown icon mini basic green button" style="z-index: 999">
						<a href="javascript:void(0)"><i class="ellipsis horizontal icon"></i></a>
						<div class="menu">
							<a href="#" class="item drp_posts_list_show_drip" post-id=<?php echo $dripper->ID; ?>><?php _e( 'Details', 'mainwp-post-dripper-extension' ); ?></a>
							<a href="#" class="item drp_posts_list_delete_drip" post-id=<?php echo $dripper->ID; ?>><?php _e( 'Cancel Drip', 'mainwp-post-dripper-extension' ); ?></a>
						</div>
					</div>
					<div class="ui modal" id="mainwp-drip-info-<?php echo $dripper->ID; ?>">
						<div class="header"><?php echo __( 'Drip Details', 'mainwp-post-dripper-extension' ); ?></div>
						<div class="scroling content">
							<?php echo ! empty( $str_drip_info ) ? $str_drip_info : __( 'Drip pending', 'mainwp-post-dripper-extension' ) . '<br />'; ?>
						</div>
						<div class="actions">
							<div class="ui cancel button"><?php echo __( 'Close', 'mainwp-post-dripper-extension' ); ?></div>
						</div>
					</div>
					<input type="hidden" value="<?php echo get_edit_post_link( $dripper->ID, true ); ?>" name="id">
				</td>
			</tr>
			<?php
		}
	}

	/**
	 * Sorts drips.
	 *
	 * @param int $a Timestamp a.
	 * @param int $b Timestamp b.
	 *
	 * @return int
	 */
	public static function drip_info_timesort( $a, $b ) {
		$a = floor( (float) $a['time'] );
		$b = floor( (float) $b['time'] );
		if ( $a == $b ) {
			return 0;
		}
		return ( $a > $b ) ? -1 : 1;
	}

	/**
	 * Handles cron jobs.
	 */
	public static function cron_posting() {
		do_action( 'mainwp_log_action', 'Post Dripper :: INFOR :: CRON :: cron posting', MAINWP_POST_DRIPPER_LOG_PRIORITY_NUMBER );

		self::post_dripper_posting( 'bulkpost' );
		self::post_dripper_posting( 'bulkpage' );
		do_action( 'mainwp_post_dripper_cron_posting' );
	}

	/**
	 * Handles the posting process.
	 *
	 * @param string $post_type Post type.
	 *
	 * @return void
	 */
	public static function post_dripper_posting( $post_type ) {
		if ( 'bulkpost' != $post_type && 'bulkpage' != $post_type ) {
			return;
		}

		do_action( 'mainwp_log_action', 'Post Dripper :: INFOR :: CRON :: post dripper posting', MAINWP_POST_DRIPPER_LOG_PRIORITY_NUMBER );

		$args = array(
			'meta_query'     => array(
				array(
					'key'   => '_mainwp_post_dripper',
					'value' => 'yes',
				),
			),
			'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
			'post_type'      => $post_type,
			'posts_per_page' => -1,
		);

		$posts_dripper = get_posts( $args );

		if ( ! is_array( $posts_dripper ) || 0 == count( $posts_dripper ) ) {
			return;
		}

		global $mainwp_DripperExtensionActivator;

		$websites         = apply_filters( 'mainwp_getsites', $mainwp_DripperExtensionActivator->get_child_file(), $mainwp_DripperExtensionActivator->get_child_key(), null );
		$all_manage_sites = array();
		if ( is_array( $websites ) ) {
			foreach ( $websites as $site ) {
				$all_manage_sites[] = $site['id'];
			}
		}

		do_action( 'mainwp_log_action', 'Post Dripper :: INFOR :: CRON :: post dripper posting count ' . count( $posts_dripper ), MAINWP_POST_DRIPPER_LOG_PRIORITY_NUMBER );

		foreach ( $posts_dripper as $post ) {
			$all_selected_sites = get_post_meta( $post->ID, '_mainwp_post_dripper_selected_drip_sites', true );

			if ( empty( $all_selected_sites ) ) {
				$all_selected_sites = $all_manage_sites;
			} else {
				$all_selected_sites = unserialize( base64_decode( $all_selected_sites ) );
				if ( empty( $all_selected_sites ) ) {
					$all_selected_sites = $all_manage_sites;
					update_post_meta( $post->ID, '_mainwp_post_dripper_selected_drip_sites', $all_selected_sites );
				}
			}

			if ( ! is_array( $all_selected_sites ) || 0 == count( $all_selected_sites ) ) {
				continue;
			}

			$nb_time     = get_post_meta( $post->ID, '_mainwp_post_dripper_time_number', true );
			$select_time = get_post_meta( $post->ID, '_mainwp_post_dripper_select_time', true );
			$last_time   = get_post_meta( $post->ID, '_mainwp_post_dripper_last_drip_time', true );

			$time_step  = 3600;
			$count_time = 1;
			if ( 'days' === $select_time ) {
				$count_time = 24;
			} elseif ( 'weeks' === $select_time ) {
				$count_time = 24 * 7;
			} elseif ( 'months' === $select_time ) {
				$count_time = 24 * 30;
			}

			if ( defined( 'DRIP_MINUTELY_TESTING' ) && true == DRIP_MINUTELY_TESTING ) {
				$time_step  = 60;
				$count_time = 1;
			}

			$next_time = (float) $last_time + $nb_time * $time_step * $count_time;
			if ( time() < $next_time ) {
				continue;
			}
			$sites = self::get_sites_dripper( $post, $all_selected_sites );

			if ( 0 == count( $sites ) ) {
				wp_delete_post( $post->ID, true );
				continue;
			}

			if ( 'bulkpost' == $post_type ) {
				self::post_posting( $post, $sites, $all_selected_sites );
			} else {
				self::page_posting( $post, $sites, $all_selected_sites );
			}
		}
	}

	/**
	 * Gets selected sites for the drip session.
	 *
	 * @param object $post      Post object.
	 * @param array  $all_sites All child sites.
	 *
	 * @return array Selected child sites.
	 */
	public static function get_sites_dripper( $post, $all_sites ) {
		$post_id           = $post->ID;
		$last_posted_sites = get_post_meta( $post->ID, '_mainwp_post_dripper_last_posted_sites', true );
		$nb_sites          = get_post_meta( $post_id, '_mainwp_post_dripper_sites_number', true );

		if ( ! is_array( $last_posted_sites ) ) {
			$last_posted_sites = array();
		}

		$diff_sites = array_diff( $all_sites, $last_posted_sites );

		if ( ! is_array( $diff_sites ) || 0 == count( $diff_sites ) ) {
			return array();
		}

		if ( count( $diff_sites ) <= $nb_sites ) {
			return $diff_sites;
		}

		$return = array();
		shuffle( $diff_sites );
		for ( $i = 0; $i < $nb_sites; $i++ ) {
			$return[] = $diff_sites[ $i ];
		}
		return $return;
	}

	/**
	 * Handles the automated post posting.
	 *
	 * @param object $post      Post object.
	 * @param array  $sites     Child sites array.
	 * @param array  $all_sites All child sites.
	 *
	 * @return void
	 */
	public static function post_posting( $post, $sites, $all_sites ) {
		$post_id       = $post->ID;
		$post_category = base64_decode( get_post_meta( $post_id, '_categories', true ) );
		$post_tags     = base64_decode( get_post_meta( $post_id, '_tags', true ) );
		$post_slug     = base64_decode( get_post_meta( $post_id, '_slug', true ) );
		$post_custom   = get_post_custom( $post_id );
		include_once ABSPATH . 'wp-includes' . DIRECTORY_SEPARATOR . 'post-thumbnail-template.php';
		$post_featured_image = get_post_thumbnail_id( $post_id );
		$mainwp_upload_dir   = wp_upload_dir();

		$new_post = array(
			'post_title'     => $post->post_title,
			'post_content'   => $post->post_content,
			'post_status'    => 'publish',
			'post_tags'      => $post_tags,
			'post_name'      => $post_slug,
			'post_excerpt'   => $post->post_excerpt,
			'comment_status' => $post->comment_status,
			'ping_status'    => $post->ping_status,
			'mainwp_post_id' => $post->ID,
		);

		if ( null != $post_featured_image ) {
			$img                 = wp_get_attachment_image_src( $post_featured_image, 'full' );
			$post_featured_image = $img[0];
		}

		global $mainwp_DripperExtensionActivator;

		$dbwebsites     = apply_filters( 'mainwp_getdbsites', $mainwp_DripperExtensionActivator->get_child_file(), $mainwp_DripperExtensionActivator->get_child_key(), $sites, null );
		$output         = new stdClass();
		$output->ok     = array();
		$output->errors = array();
		$output->link   = array();

		if ( count( $dbwebsites ) > 0 ) {
			$post_data = array(
				'new_post'            => base64_encode( serialize( $new_post ) ),
				'post_custom'         => base64_encode( serialize( $post_custom ) ),
				'post_category'       => base64_encode( $post_category ),
				'post_featured_image' => base64_encode( $post_featured_image ),
				'mainwp_upload_dir'   => base64_encode( serialize( $mainwp_upload_dir ) ),
			);
			add_filter( 'mainwp_response_json_format', '__return_true' );
			do_action( 'mainwp_fetchurlsauthed', $mainwp_DripperExtensionActivator->get_child_file(), $mainwp_DripperExtensionActivator->get_child_key(), $dbwebsites, 'newpost', $post_data, array( 'MainWP_Dripper', 'posting_bulk_handler' ), $output );
		}

		$posted_sites   = array();
		$last_time      = 0;
		$last_published = '';
		$drip_info      = array();
		$time           = time();

		foreach ( $dbwebsites as $website ) {
			if ( isset( $output->ok[ $website->id ] ) && ( 1 == $output->ok[ $website->id ] ) ) {
				$posted_sites[] = $website->id;
				$last_published = $website->url;
				$last_time      = $time;
				$link           = isset( $output->link[ $website->id ] ) ? $output->link[ $website->id ] : '';
				$drip_info[]    = array(
					'site_url' => $website->url,
					'time'     => $time,
					'link'     => $link,
				);
			} else {
				$drip_info[] = array(
					'site_url' => $website->url,
					'time'     => $time,
					'error'    => __( 'Post failed.' ),
				);
			}
		}

		$last_posted_sites = get_post_meta( $post_id, '_mainwp_post_dripper_last_posted_sites', true );

		if ( is_array( $last_posted_sites ) ) {
			$last_posted_sites = array_merge( $last_posted_sites, $posted_sites );
		} else {
			$last_posted_sites = $posted_sites;
		}

		$last_drip_info = get_post_meta( $post_id, '_mainwp_post_dripper_drip_info', true );

		if ( is_array( $last_drip_info ) ) {
			$last_drip_info = array_merge( $last_drip_info, $drip_info );
		} else {
			$last_drip_info = $drip_info;
		}

		update_post_meta( $post_id, '_mainwp_post_dripper_last_posted_sites', $last_posted_sites );
		if ( ! empty( $last_published ) ) {
			update_post_meta( $post_id, '_mainwp_post_dripper_last_published', $last_published );
		}
		if ( ! empty( $last_time ) ) {
			update_post_meta( $post_id, '_mainwp_post_dripper_last_drip_time', $last_time );
		}
		update_post_meta( $post_id, '_mainwp_post_dripper_drip_info', $last_drip_info );
		update_post_meta( $post_id, '_selected_sites', $last_posted_sites );
		update_post_meta( $post_id, '_selected_by', 'site' );

		$diff = array_diff( $all_sites, $last_posted_sites );

		if ( ! is_array( $diff ) || 0 == count( $diff ) ) {
			wp_delete_post( $post_id, true );
		}
	}

	/**
	 * Handles the automated page posting.
	 *
	 * @param object $post      Post object.
	 * @param array  $sites     Child sites array.
	 * @param array  $all_sites All child sites.
	 *
	 * @return void
	 */
	public static function page_posting( $post, $sites, $all_sites ) {
		$post_id     = $post->ID;
		$post_slug   = base64_decode( get_post_meta( $post_id, '_slug', true ) );
		$post_custom = get_post_custom( $post_id );
		include_once ABSPATH . 'wp-includes' . DIRECTORY_SEPARATOR . 'post-thumbnail-template.php';
		$post_featured_image = get_post_thumbnail_id( $post_id );
		$mainwp_upload_dir   = wp_upload_dir();
		$new_post            = array(
			'post_title'     => $post->post_title,
			'post_content'   => $post->post_content,
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'post_name'      => $post_slug,
			'post_excerpt'   => $post->post_excerpt,
			'comment_status' => $post->comment_status,
			'ping_status'    => $post->ping_status,
			'mainwp_post_id' => $post->ID,
		);

		if ( null != $post_featured_image ) {
			$img                 = wp_get_attachment_image_src( $post_featured_image, 'full' );
			$post_featured_image = $img[0];
		}

		global $mainwp_DripperExtensionActivator;

		$dbwebsites     = apply_filters( 'mainwp_getdbsites', $mainwp_DripperExtensionActivator->get_child_file(), $mainwp_DripperExtensionActivator->get_child_key(), $sites, null );
		$output         = new stdClass();
		$output->ok     = array();
		$output->errors = array();
		$output->link   = array();

		if ( count( $dbwebsites ) > 0 ) {
			$post_data = array(
				'new_post'            => base64_encode( serialize( $new_post ) ),
				'post_custom'         => base64_encode( serialize( $post_custom ) ),
				'post_featured_image' => base64_encode( $post_featured_image ),
				'mainwp_upload_dir'   => base64_encode( serialize( $mainwp_upload_dir ) ),
			);
			add_filter( 'mainwp_response_json_format', '__return_true' ); // going to remove
			do_action( 'mainwp_fetchurlsauthed', $mainwp_DripperExtensionActivator->get_child_file(), $mainwp_DripperExtensionActivator->get_child_key(), $dbwebsites, 'newpost', $post_data, array( 'MainWP_Dripper', 'posting_bulk_handler' ), $output );
		}

		$posted_sites   = array();
		$last_time      = 0;
		$last_published = '';
		$drip_info      = array();
		$time           = time();

		foreach ( $dbwebsites as $website ) {
			if ( isset( $output->ok[ $website->id ] ) && ( $output->ok[ $website->id ] == 1 ) ) {
				$posted_sites[] = $website->id;
				$last_published = $website->url;
				$last_time      = $time;
				$link           = isset( $output->link[ $website->id ] ) ? $output->link[ $website->id ] : '';
				$drip_info[]    = array(
					'site_url' => $website->url,
					'time'     => $time,
					'link'     => $link,
				);
			} else {
				$drip_info[] = array(
					'site_url' => $website->url,
					'time'     => $time,
					'error'    => __( 'Post failed.' ),
				);
			}
		}

		$last_posted_sites = get_post_meta( $post_id, '_mainwp_post_dripper_last_posted_sites', true );
		if ( is_array( $last_posted_sites ) ) {
			$last_posted_sites = array_merge( $last_posted_sites, $posted_sites );
		} else {
			$last_posted_sites = $posted_sites;
		}

		$last_drip_info = get_post_meta( $post_id, '_mainwp_post_dripper_drip_info', true );
		if ( is_array( $last_drip_info ) ) {
			$last_drip_info = array_merge( $last_drip_info, $drip_info );
		} else {
			$last_drip_info = $drip_info;
		}

		update_post_meta( $post_id, '_mainwp_post_dripper_last_posted_sites', $last_posted_sites );

		if ( ! empty( $last_published ) ) {
			update_post_meta( $post_id, '_mainwp_post_dripper_last_published', $last_published );
		}

		if ( ! empty( $last_time ) ) {
			update_post_meta( $post_id, '_mainwp_post_dripper_last_drip_time', $last_time );
		}

		update_post_meta( $post_id, '_mainwp_post_dripper_drip_info', $last_drip_info );
		update_post_meta( $post_id, '_selected_sites', $last_posted_sites );
		update_post_meta( $post_id, '_selected_by', 'site' );

		$diff = array_diff( $all_sites, $last_posted_sites );

		if ( ! is_array( $diff ) || 0 == count( $diff ) ) {
			wp_delete_post( $post_id, true );
		}
	}

	/**
	 * Handles the bulk post process.
	 *
	 * @param object $data    Data object.
	 * @param object $website Website object.
	 * @param object $output  Output object.
	 *
	 * @return void
	 */
	public static function posting_bulk_handler( $data, $website, &$output ) {
		if ( preg_match( '/<mainwp>(.*)<\/mainwp>/', $data, $results ) > 0 ) {
			$result      = $results[1];
			$information = json_decode( base64_decode( $result ), true );
			if ( isset( $information['added'] ) ) {
				$output->ok[ $website->id ] = 1;
				if ( isset( $information['link'] ) ) {
					$output->link[ $website->id ] = $information['link'];
				}
			}
		}
	}

	/**
	 * Saves meta dripper post.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public function save_meta_dripper_post( $post_id ) {
		if ( ! isset( $_POST['dripper-nonce'] ) || ! wp_verify_nonce( $_POST['dripper-nonce'], 'dripper_' . $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST['mainwp_dripper_use_post_dripper'] ) || empty( $_POST['mainwp_dripper_use_post_dripper'] ) ) {
			return;
		}

		add_action( 'mainwp_before_redirect_posting_bulkpost', array( &$this, 'redirect_posting_bulkpost' ) );

		$this->dripper_metabox_handle( $post_id, 'bulkpost' );
	}

	/**
	 * Saves meta dripper page.
	 *
	 * @param int $post_id Page ID.
	 *
	 * @return void
	 */
	public function save_meta_dripper_page( $post_id ) {
		if ( ! isset( $_POST['dripper-nonce'] ) || ! wp_verify_nonce( $_POST['dripper-nonce'], 'dripper_' . $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST['mainwp_dripper_use_post_dripper'] ) || empty( $_POST['mainwp_dripper_use_post_dripper'] ) ) {
			return;
		}

		add_action( 'mainwp_before_redirect_posting_bulkpage', array( &$this, 'redirect_posting_bulkpage' ) );

		$this->dripper_metabox_handle( $post_id, 'bulkpage' );
	}

	/**
	 * Redirects to the confirmation screen instead of completting the post process.
	 *
	 * @param object $post Post object.
	 *
	 * @return void
	 */
	public function redirect_posting_bulkpost( $post ) {
		wp_redirect( get_site_url() . '/wp-admin/admin.php?page=CreatedPostDripper&id=' . $post->ID );
		die();
	}

	/**
	 * Redirects to the confirmation screen instead of completting the post process.
	 *
	 * @param object $post Post object.
	 *
	 * @return void
	 */
	public function redirect_posting_bulkpage( $post ) {
		wp_redirect( get_site_url() . '/wp-admin/admin.php?page=CreatedPageDripper&id=' . $post->ID );
		die();
	}

	/**
	 * Checks for numberic values.
	 *
	 * @param string $str String to check.
	 *
	 * @return string Performed a regular expression match.
	 */
	public static function ctype_digit( $str ) {
		return ( is_string( $str ) || is_int( $str ) || is_float( $str ) ) && preg_match( '/^\d+\z/', $str );
	}

	/**
	 * Handles the Post Ripper metabox processes.
	 *
	 * @param array  $post      Array conaining post data.
	 * @param string $post_type Post type.
	 *
	 * @return void
	 */
	public function dripper_metabox_handle( $post_id, $post_type ) {
		if ( ! isset( $_POST['dripper-nonce'] ) || ! wp_verify_nonce( $_POST['dripper-nonce'], 'dripper_' . $post_id ) ) {
			return;
		}

		$post = get_post( $post_id );

		if ( $post->post_type == $post_type ) {

			$nb_sites    = isset( $_POST['mainwp_dripper_sites_number'] ) ? $_POST['mainwp_dripper_sites_number'] : 0;
			$nb_time     = isset( $_POST['mainwp_dripper_time_number'] ) ? $_POST['mainwp_dripper_time_number'] : 0;
			$select_time = isset( $_POST['mainwp_dripper_select_time'] ) ? $_POST['mainwp_dripper_select_time'] : 0;
			$use_dripper = isset( $_POST['mainwp_dripper_use_post_dripper'] ) ? $_POST['mainwp_dripper_use_post_dripper'] : 0;

			update_post_meta( $post_id, '_mainwp_post_dripper_sites_number', $nb_sites );
			update_post_meta( $post_id, '_mainwp_post_dripper_time_number', $nb_time );
			update_post_meta( $post_id, '_mainwp_post_dripper_select_time', $select_time );
			update_post_meta( $post_id, '_mainwp_post_dripper_use_post_dripper', $use_dripper );
			update_post_meta( $post_id, 'mainwp_post_id', $post->ID );

			$selected_by    = get_post_meta( $post_id, '_selected_by', true );
			$selected_sites = $selected_groups = array();

			if ( 'site' == $selected_by ) {
				$selected_sites = get_post_meta( $post_id, '_selected_sites', true );
			} else {
				$selected_groups = get_post_meta( $post_id, '_selected_groups', true );
			}

			$all_selected_sites = array();
			$groups             = null;

			if ( 'site' == $selected_by ) { // Get all selected websites.
				foreach ( $selected_sites as $k ) {
					if ( self::ctype_digit( $k ) ) {
						$all_selected_sites[] = $k;
					}
				}
			} else { // Get all websites from the selected groups.
				foreach ( $selected_groups as $k ) {
					if ( self::ctype_digit( $k ) ) {
						$groups[] = $k;
					}
				}
				global $mainwp_DripperExtensionActivator;
				$dbwebsites = apply_filters( 'mainwp_getdbsites', $mainwp_DripperExtensionActivator->get_child_file(), $mainwp_DripperExtensionActivator->get_child_key(), array(), $groups );
				if ( is_array( $dbwebsites ) ) {
					foreach ( $dbwebsites as $site ) {
						$all_selected_sites[] = $site->id;
					}
				}
			}

			update_post_meta( $post_id, '_mainwp_post_dripper_selected_drip_sites', base64_encode( serialize( $all_selected_sites ) ) );
			update_post_meta( $post_id, '_mainwp_post_dripper_total_drip_sites', count( $all_selected_sites ) );
			update_post_meta( $post_id, '_mainwp_post_dripper', 'yes' );
		}
	}

	/**
	 * Renders the confirmation screen after creating a Post drip session.
	 *
	 * @return void
	 */
	public function created_post_dripper() {
		?>
		<div class="ui segment">
		<?php if ( isset( $_GET['id'] ) ) : ?>
			<?php
			wp_update_post(
				array(
					'ID'          => $_GET['id'],
					'post_status' => 'pending',
				)
			);
			?>
			<div class="ui hidden divider"></div>
			<div class="ui hidden divider"></div>
			<h2 class="ui icon header">
				<i class="green check icon"></i>
				<div class="content">
					<?php echo __( 'Drip created successfully!', 'mainwp-post-dripper-extension' ); ?>
					<div class="sub header"><?php echo __( 'What do you want to do next?', 'mainwp-post-dripper-extension' ); ?></div>
					<div class="ui hidden divider"></div>
					<a href="admin.php?page=PostBulkAdd" class="ui green button"><?php echo __( 'Create New Drip', 'mainwp-post-dripper-extension' ); ?></a> <a href="admin.php?page=Extensions-Mainwp-Post-Dripper-Extension" class="ui green basic button"><?php echo __( 'Manage Drips', 'mainwp-post-dripper-extension' ); ?></a> <a href="admin.php?page=mainwp_tab" class="ui button"><?php echo __( 'Go to Overview', 'mainwp-post-dripper-extension' ); ?></a>
				</div>
			</h2>
		<?php else : ?>
			<h2 class="ui icon header">
				<i class="green times icon"></i>
				<div class="content">
					<?php echo __( 'Drip creation failed!', 'mainwp-post-dripper-extension' ); ?>
					<div class="sub header"><?php echo __( 'What do you want to do next?', 'mainwp-post-dripper-extension' ); ?></div>
					<div class="ui hidden divider"></div>
					<a href="admin.php?page=PostBulkAdd" class="ui green button"><?php echo __( 'Create New Drip', 'mainwp-post-dripper-extension' ); ?></a> <a href="admin.php?page=Extensions-Mainwp-Post-Dripper-Extension" class="ui green basic button"><?php echo __( 'Manage Drips', 'mainwp-post-dripper-extension' ); ?></a> <a href="admin.php?page=mainwp_tab" class="ui button"><?php echo __( 'Go to Overview', 'mainwp-post-dripper-extension' ); ?></a>
				</div>
			</h2>
		<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Renders the confirmation screen after creating a Page drip session.
	 *
	 * @return void
	 */
	public function created_page_dripper() {
		?>
		<div class="ui segment">
		<?php if ( isset( $_GET['id'] ) ) : ?>
			<?php
			wp_update_post(
				array(
					'ID'          => $_GET['id'],
					'post_status' => 'pending',
				)
			);
			?>
			<div class="ui hidden divider"></div>
			<div class="ui hidden divider"></div>
			<h2 class="ui icon header">
				<i class="green check icon"></i>
				<div class="content">
					<?php echo __( 'Drip created successfully!', 'mainwp-post-dripper-extension' ); ?>
					<div class="sub header"><?php echo __( 'What do you want to do next?', 'mainwp-post-dripper-extension' ); ?></div>
					<div class="ui hidden divider"></div>
					<a href="admin.php?page=PageBulkAdd" class="ui green button"><?php echo __( 'Create New Drip', 'mainwp-post-dripper-extension' ); ?></a> <a href="admin.php?page=Extensions-Mainwp-Post-Dripper-Extension" class="ui green basic button"><?php echo __( 'Manage Drips', 'mainwp-post-dripper-extension' ); ?></a> <a href="admin.php?page=mainwp_tab" class="ui button"><?php echo __( 'Go to Overview', 'mainwp-post-dripper-extension' ); ?></a>
				</div>
			</h2>
		<?php else : ?>
			<h2 class="ui icon header">
				<i class="green times icon"></i>
				<div class="content">
					<?php echo __( 'Drip creation failed!', 'mainwp-post-dripper-extension' ); ?>
					<div class="sub header"><?php echo __( 'What do you want to do next?', 'mainwp-post-dripper-extension' ); ?></div>
					<div class="ui hidden divider"></div>
					<a href="admin.php?page=PostBulkAdd" class="ui green button"><?php echo __( 'Create New Drip', 'mainwp-post-dripper-extension' ); ?></a> <a href="admin.php?page=Extensions-Mainwp-Post-Dripper-Extension" class="ui green basic button"><?php echo __( 'Manage Drips', 'mainwp-post-dripper-extension' ); ?></a> <a href="admin.php?page=mainwp_tab" class="ui button"><?php echo __( 'Go to Overview', 'mainwp-post-dripper-extension' ); ?></a>
				</div>
			</h2>
		<?php endif; ?>
		</div>
		<?php
	}
}
