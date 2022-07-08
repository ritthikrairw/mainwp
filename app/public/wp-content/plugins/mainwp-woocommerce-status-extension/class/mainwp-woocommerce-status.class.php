<?php

class MainWP_WooCommerce_Status {

	public static $instance = null;
	static $orderby         = '';
	static $order           = '';
	protected $option;
	protected $option_handle = 'mainwp_woocommerce_status_options';

	static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new MainWP_WooCommerce_Status();
		}
		return self::$instance;
	}

	public function __construct() {
		$this->option = get_option( $this->option_handle );
		add_action( 'mainwp_site_synced', array( &$this, 'site_synced' ), 10, 1 );
		add_action( 'mainwp_delete_site', array( &$this, 'delete_site_data' ), 8, 1 );
		add_filter( 'mainwp_woocomstatus_get_data', array( &$this, 'woocomstatus_get_data' ), 10, 3 );
		add_action( 'wp_ajax_mainwp_wcstatus_update_wc_db', array( &$this, 'ajax_wcstatus_update_wc_db' ) );
	}

	public function get_option( $key, $default = '' ) {
		if ( isset( $this->option[ $key ] ) ) {
			return $this->option[ $key ];
		}
		return $default;
	}

	public function set_option( $key, $value ) {
		$this->option[ $key ] = $value;
		return update_option( $this->option_handle, $this->option );
	}

	public function site_synced( $website ) {
		$this->sync_data_site( $website );
	}

	function woocomstatus_get_data( $websiteid, $start_date = null, $end_date = null ) {
		$status = $this->do_get_report_data( $websiteid, $start_date, $end_date );
		$output = array();
		if ( is_array( $status ) ) {
			$output['wcomstatus.sales']              = isset( $status['formated_sales'] ) ? $status['formated_sales'] : 'N/A';
			$output['wcomstatus.topseller']          = isset( $status['top_seller'] ) && ! empty( $status['top_seller'] ) ? $status['top_seller'] : null;
			$output['wcomstatus.awaitingprocessing'] = isset( $status['awaiting'] ) ? $status['awaiting'] : 0;
			$output['wcomstatus.onhold']             = isset( $status['onhold'] ) ? $status['onhold'] : 0;
			$output['wcomstatus.lowonstock']         = isset( $status['lowstock'] ) ? $status['lowstock'] : 0;
			$output['wcomstatus.outofstock']         = isset( $status['outstock'] ) ? $status['outstock'] : 0;
		}
		return $output;
	}

	public function delete_site_data( $website ) {
		if ( $website ) {
			MainWP_WooCommerce_Status_DB::get_instance()->delete_status_by( 'site_id', $website->id );
		}
	}

	function sync_data_site( $website ) {
		if ( is_object( $website ) && $website->plugins != '' ) {
			$plugins = json_decode( $website->plugins, 1 );
			if ( is_array( $plugins ) ) {
				foreach ( $plugins as $plugin ) {
					if ( 'woocommerce/woocommerce.php' == $plugin['slug'] ) {
						$actived = 0;
						if ( $plugin['active'] ) {
							$actived = 1;
						}
						$update = array(
							'site_id' => $website->id,
							'active'  => $actived,
						);
						MainWP_WooCommerce_Status_DB::get_instance()->update_status( $update );
						if ( $actived ) {
							$this->do_sync_data( $website );
						}
						break;
					}
				}
			}
		}
	}

	public function do_sync_data( $website ) {
		$wo_status = MainWP_WooCommerce_Status_DB::get_instance()->get_status_by( 'site_id', $website->id );
		$post_data = array( 'mwp_action' => 'sync_data' );

		global $mainWPWooCommerceStatusExtensionActivator;

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWooCommerceStatusExtensionActivator->get_child_file(), $mainWPWooCommerceStatusExtensionActivator->get_child_key(), $website->id, 'woo_com_status', $post_data );
		$update      = array();

		if ( is_array( $information ) ) {
			if ( isset( $information['data'] ) && is_array( $information['data'] ) ) {
				$status = $information['data'];
				$update = array(
					'id'             => ( ! empty( $wo_status ) && $wo_status->id ) ? $wo_status->id : 0,
					'site_id'        => $website->id,
					'status'         => ! empty( $status ) ? serialize( $status ) : '',
					'need_db_update' => $information['need_db_update'] ? 1 : 0,
				);
				MainWP_WooCommerce_Status_DB::get_instance()->update_status( $update );
			}
		}
	}

	function ajax_wcstatus_update_wc_db() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wcstatus-nonce' ) ) {
			die( json_encode( array( 'error' => 'Invalid request!' ) ) );
		}

		$siteid = isset( $_POST['websiteId'] ) ? $_POST['websiteId'] : null;

		if ( $siteid ) {
			global $mainWPWooCommerceStatusExtensionActivator;
			$post_data = array(
				'mwp_action' => 'update_wc_db',
			);

			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWooCommerceStatusExtensionActivator->get_child_file(), $mainWPWooCommerceStatusExtensionActivator->get_child_key(), $siteid, 'woo_com_status', $post_data );

			if ( is_array( $information ) && isset( $information['result'] ) ) {
				$update = array(
					'site_id'        => $siteid,
					'need_db_update' => 0,
				);
				MainWP_WooCommerce_Status_DB::get_instance()->update_status( $update );
			}
			die( json_encode( $information ) );
		}
		die();
	}

	public function do_get_report_data( $website_id, $start_date, $end_date ) {
		$post_data = array(
			'mwp_action' => 'report_data',
			'start_date' => $start_date,
			'end_date'   => $end_date,
		);

		global $mainWPWooCommerceStatusExtensionActivator;

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPWooCommerceStatusExtensionActivator->get_child_file(), $mainWPWooCommerceStatusExtensionActivator->get_child_key(), $website_id, 'woo_com_status', $post_data );

		$update = array();
		if ( is_array( $information ) ) {
			if ( isset( $information['data'] ) && is_array( $information['data'] ) ) {
				return $information['data'];
			}
		}
		return false;
	}

	// Renders the top of the widget (name + dropdown)
	public static function get_name() {
		$name = __( 'Woocommerce Status (All Stores)', 'mainwp-woocommerce-status-extension' );

		if ( isset( $_GET['page'] ) && 'managesites' == $_GET['page'] ) {
			$name = __( 'Woocommerce Status', 'mainwp-woocommerce-status-extension' );
		}

		return $name;
	}

	public static function render_settings() {

		global $mainWPWooCommerceStatusExtensionActivator;

		$websites = apply_filters( 'mainwp_getsites', $mainWPWooCommerceStatusExtensionActivator->get_child_file(), $mainWPWooCommerceStatusExtensionActivator->get_child_key(), null );

		$all_sites = array();

		if ( is_array( $websites ) ) {
			foreach ( $websites as $website ) {
				$all_sites[ $website['id'] ] = $website;
			}
		}

		unset( $websites );

		$wc_status = MainWP_WooCommerce_Status_DB::get_instance()->get_status_by( 'all' );

		$selected_group = 0;

		if ( isset( $_POST['mainwp_woocommerce_status_groups_select'] ) ) {
			$selected_group = intval( $_POST['mainwp_woocommerce_status_groups_select'] );
		}

		$woo_sites_status = self::get_woo_sites_data( $wc_status, $all_sites, $selected_group );

		unset( $all_sites );

		?>
		<div class="ui segment" id="mainwp-woocommerce-status">
			<table class="ui single tablet stackable line table" id="mainwp-woocommerce-status-table" style="width:100%">
				<thead>
					<tr>
						<th><?php _e( 'Site', 'mainwp-woocommerce-status-extension' ); ?></th>
						<th class="collapsing no-sort"><i class="sign in icon"></i></th>
						<th><?php _e( 'URL', 'mainwp-woocommerce-status-extension' ); ?></th>
						<th><?php _e( 'Sales This Month', 'mainwp-woocommerce-status-extension' ); ?></th>
						<th><?php _e( 'Top Seller', 'mainwp-woocommerce-status-extension' ); ?></th>
						<th><?php _e( 'Awaiting Processing', 'mainwp-woocommerce-status-extension' ); ?></th>
						<th><?php _e( 'On Hold', 'mainwp-woocommerce-status-extension' ); ?></th>
						<th><?php _e( 'Low On Stock', 'mainwp-woocommerce-status-extension' ); ?></th>
						<th><?php _e( 'Out Of Stock', 'mainwp-woocommerce-status-extension' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( is_array( $woo_sites_status ) && count( $woo_sites_status ) > 0 ) : ?>
						<?php self::render_table_status_row( $woo_sites_status ); ?>
					<?php else : ?>
						<tr>
							<td colspan="8"><?php echo esc_html( 'WooCommerce plugin not found on any of the child sites.', 'mainwp-woocommerce-status-extension' ); ?></td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
			<?php
			$table_features = array(
				'searching'     => 'true',
				'paging'        => 'true',
				'info'          => 'true',
				'stateSave'     => 'true',
				'stateDuration' => '0',
				'scrollX'       => 'true',
				'colReorder'    => 'true',
				'lengthMenu'    => '[ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ]',
				'columnDefs'    => '[ { "targets": "no-sort", "orderable": false } ]',
			);
			/**
			 * Filter: mainwp_woocommerce_orders_table_features
			 *
			 * Filters the WooCommerce Orders table features.
			 *
			 * @param array $table_features Table features array.
			 *
			 * @since 4.0.8
			 */
			$table_features = apply_filters( 'mainwp_woocommerce_orders_table_features', $table_features );
			?>
			<script type="text/javascript">
			jQuery( document ).ready( function () {
				jQuery( '#mainwp-woocommerce-status-table' ).DataTable( {
					<?php
					foreach ( $table_features as $feature => $value ) {
						echo "'" . $feature . "' : " . $value . ',';
					};
					?>
				} );
			} );
			</script>
		</div>
		<?php
	}

	public static function get_woo_sites_data( $wc_status, $websites, $selected_group = '' ) {
		$woo_status = array();
		if ( is_array( $wc_status ) ) {
			if ( empty( $selected_group ) ) {
				foreach ( $wc_status as $wc_st ) {
					$site_id = $wc_st->site_id;
					if ( ! isset( $websites[ $site_id ] ) ) {
						MainWP_WooCommerce_Status_DB::get_instance()->delete_status_by( 'site_id', $site_id );
						continue;
					}
					$data = MainWP_WooCommerce_Status_Utility::map_site( $wc_st, array( 'id', 'active', 'site_id' ) );
					if ( $wc_st->active == 1 ) {
						$data['name']           = $websites[ $site_id ]['name'];
						$data['url']            = $websites[ $site_id ]['url'];
						$data['need_db_update'] = $wc_st->need_db_update;

						$status = unserialize( $wc_st->status );

						if ( is_array( $status ) ) {
							$data['sales']          = isset( $status['sales'] ) ? $status['sales'] : 0;
							$data['formated_sales'] = isset( $status['formated_sales'] ) ? $status['formated_sales'] : 'N/A';
							$data['top_seller']     = isset( $status['top_seller'] ) && ! empty( $status['top_seller'] ) ? $status['top_seller'] : null;
							$data['awaiting']       = isset( $status['awaiting'] ) ? $status['awaiting'] : 0;
							$data['onhold']         = isset( $status['onhold'] ) ? $status['onhold'] : 0;
							$data['lowstock']       = isset( $status['lowstock'] ) ? $status['lowstock'] : 0;
							$data['outstock']       = isset( $status['outstock'] ) ? $status['outstock'] : 0;
						}

						$woo_status[] = $data;
					}
				}
			} else {
				global $mainWPWooCommerceStatusExtensionActivator;

				$group_websites = apply_filters( 'mainwp_getdbsites', $mainWPWooCommerceStatusExtensionActivator->get_child_file(), $mainWPWooCommerceStatusExtensionActivator->get_child_key(), array(), array( $selected_group ) );
				$sites          = array();
				foreach ( $group_websites as $site ) {
					$sites[ $site->id ] = 1;
				}

				foreach ( $wc_status as $wc_st ) {
					$site_id = $wc_st->site_id;
					if ( ! isset( $sites[ $site_id ] ) || empty( $sites[ $site_id ] ) ) {
						continue;
					}
					$data = MainWP_WooCommerce_Status_Utility::map_site( $wc_st, array( 'id', 'active', 'site_id' ) );
					if ( $wc_st->active == 1 ) {
						$data['name']           = $websites[ $site_id ]['name'];
						$data['url']            = $websites[ $site_id ]['url'];
						$data['need_db_update'] = $wc_st->need_db_update;

						$status = unserialize( $wc_st->status );

						if ( is_array( $status ) ) {
							$data['sales']          = isset( $status['sales'] ) ? $status['sales'] : 0;
							$data['formated_sales'] = isset( $status['formated_sales'] ) ? $status['formated_sales'] : 'N/A';
							$data['top_seller']     = isset( $status['top_seller'] ) && is_object( $status['top_seller'] ) ? $status['top_seller'] : null;
							$data['awaiting']       = isset( $status['awaiting'] ) ? $status['awaiting'] : 0;
							$data['onhold']         = isset( $status['onhold'] ) ? $status['onhold'] : 0;
							$data['lowstock']       = isset( $status['lowstock'] ) ? $status['lowstock'] : 0;
							$data['outstock']       = isset( $status['outstock'] ) ? $status['outstock'] : 0;
						}

						$woo_status[] = $data;
					}
				}
			}
		}

		return $woo_status;
	}

	public static function render_table_status_row( $websites ) {
		foreach ( $websites as $website ) {

			$website_id = $website['site_id'];
			$location   = 'edit.php?post_type=shop_order';

			$location3 = 'admin.php?page=wc-reports&tab=orders&range=month';
			$sales_lnk = '<a href="admin.php?page=SiteOpen&newWindow=yes&websiteid=' . $website_id . '&location=' . base64_encode( $location3 ) . '&_opennonce=' . wp_create_nonce( 'mainwp-admin-nonce' ) . '" target="_blank">' . ( isset( $website['formated_sales'] ) ? $website['formated_sales'] : 'N/A' ) . '</a>';

			$product_name = 'N/A';
			$product_id   = '';
			if ( isset( $website['top_seller'] ) && ! empty( $website['top_seller'] ) ) {
				if ( is_array( $website['top_seller'] ) ) {
					$product_name = $website['top_seller']['name'];
					$product_id   = $website['top_seller']['product_id'];
				} elseif ( is_object( $website['top_seller'] ) ) {
					$product_name = $website['top_seller']->name;
					$product_id   = $website['top_seller']->product_id;
				}
			}

			$location4 = 'admin.php?page=wc-reports&tab=orders&report=sales_by_product&range=month&product_ids=' . $product_id;

			$seller_lnk = '<a href="admin.php?page=SiteOpen&newWindow=yes&websiteid=' . $website_id . '&location=' . base64_encode( $location4 ) . '&_opennonce=' . wp_create_nonce( 'mainwp-admin-nonce' ) . '" target="_blank">' . $product_name . '</a>';

			$location5     = 'edit.php?post_status=wc-processing&post_type=shop_order';
			$awaiting_text = '0 ' . __( 'Orders', 'mainwp-woocommerce-status-extension' );
			$awaiting_val  = 0;
			if ( isset( $website['awaiting'] ) ) {
				$awaiting_val = $website['awaiting'];
				if ( 1 == $website['awaiting'] ) {
					$awaiting_text = $website['awaiting'] . ' ' . __( 'Order', 'mainwp-woocommerce-status-extension' );
				} elseif ( $website['awaiting'] > 1 ) {
					$awaiting_text = $website['awaiting'] . ' ' . __( 'Orders', 'mainwp-woocommerce-status-extension' );
				}
			}
			$awaiting_lnk = '<a href="admin.php?page=SiteOpen&newWindow=yes&websiteid=' . $website_id . '&location=' . base64_encode( $location5 ) . '&_opennonce=' . wp_create_nonce( 'mainwp-admin-nonce' ) . '" target="_blank">' . $awaiting_text . '</a>';

			$location6   = 'edit.php?post_status=wc-on-hold&post_type=shop_order';
			$onhold_text = '0 ' . __( 'Orders', 'mainwp-woocommerce-status-extension' );
			$onhold_val  = 0;
			if ( isset( $website['onhold'] ) ) {
				$onhold_val = $website['onhold'];
				if ( 1 == $website['onhold'] ) {
					$onhold_text = $website['onhold'] . ' ' . __( 'Order', 'mainwp-woocommerce-status-extension' );
				} elseif ( $website['onhold'] > 1 ) {
					$onhold_text = $website['onhold'] . ' ' . __( 'Orders', 'mainwp-woocommerce-status-extension' );
				}
			}
			$onhold_lnk = '<a href="admin.php?page=SiteOpen&newWindow=yes&websiteid=' . $website_id . '&location=' . base64_encode( $location6 ) . '&_opennonce=' . wp_create_nonce( 'mainwp-admin-nonce' ) . '" target="_blank">' . $onhold_text . '</a>';

			$location7     = 'admin.php?page=wc-reports&tab=stock&report=low_in_stock';
			$lowstock_text = '0 ' . __( 'Products', 'mainwp-woocommerce-status-extension' );
			$lowstock_val  = 0;
			if ( isset( $website['lowstock'] ) ) {
				$lowstock_val = $website['lowstock'];
				if ( 1 == $website['lowstock'] ) {
					$lowstock_text = $website['lowstock'] . ' ' . __( 'Product', 'mainwp-woocommerce-status-extension' );
				} elseif ( $website['lowstock'] > 1 ) {
					$lowstock_text = $website['lowstock'] . ' ' . __( 'Products', 'mainwp-woocommerce-status-extension' );
				}
			}
			$lowstock_lnk = '<a href="admin.php?page=SiteOpen&newWindow=yes&websiteid=' . $website_id . '&location=' . base64_encode( $location7 ) . '&_opennonce=' . wp_create_nonce( 'mainwp-admin-nonce' ) . '" target="_blank">' . $lowstock_text . '</a>';

			$location8     = 'admin.php?page=wc-reports&tab=stock&report=out_of_stock';
			$outstock_text = '0 ' . __( 'Products', 'mainwp-woocommerce-status-extension' );
			$outstock_val  = 0;
			if ( isset( $website['outstock'] ) ) {
				$outstock_val = $website['outstock'];
				if ( 1 == $website['outstock'] ) {
					$outstock_text = $website['outstock'] . ' ' . __( 'Product', 'mainwp-woocommerce-status-extension' );
				} elseif ( $website['outstock'] > 1 ) {
					$outstock_text = $website['outstock'] . ' ' . __( 'Products', 'mainwp-woocommerce-status-extension' );
				}
			}
			$outstock_lnk = '<a href="admin.php?page=SiteOpen&newWindow=yes&websiteid=' . $website_id . '&location=' . base64_encode( $location8 ) . '&_opennonce=' . wp_create_nonce( 'mainwp-admin-nonce' ) . '" target="_blank">' . $outstock_text . '</a>';
			?>
			<tr website-id="<?php echo $website_id; ?>">
				<td><a href="admin.php?page=managesites&dashboard=<?php echo $website_id; ?>"><?php echo $website['name']; ?></a></td>
				<td><a target="_blank" href="admin.php?page=SiteOpen&newWindow=yes&websiteid=<?php echo $website_id; ?>&_opennonce=<?php echo wp_create_nonce( 'mainwp-admin-nonce' ); ?>"><i class="sign in icon"></i></a></td>
				<td><a href="<?php echo $website['url']; ?>" target="_blank"><?php echo $website['url']; ?></a></td>
				<td data-order="<?php echo isset( $website['sales'] ) ? esc_html( $website['sales'] ) : ''; ?>"><?php echo $sales_lnk; ?></td>
				<td data-order="<?php echo esc_html( $product_name ); ?>"><?php echo $seller_lnk; ?></td>
				<td data-order="<?php echo esc_html( $awaiting_val ); ?>"><?php echo $awaiting_lnk; ?></td>
				<td data-order="<?php echo esc_html( $onhold_val ); ?>"><?php echo $onhold_lnk; ?></td>
				<td data-order="<?php echo esc_html( $lowstock_val ); ?>"><?php echo $lowstock_lnk; ?></td>
				<td data-order="<?php echo esc_html( $outstock_val ); ?>"><?php echo $outstock_lnk; ?></td>
			</tr>
			<?php
		}
	}

	// Renders the content of the widget
	public static function render_metabox() {
		$individual_metabox = false;

		if ( isset( $_GET['page'] ) && 'managesites' == $_GET['page'] ) {
			$individual_metabox = true;
		}

		$sales      = 0;
		$awaiting   = 0;
		$onhold     = 0;
		$lowstock   = 0;
		$outstock   = 0;
		$top_seller = array();
		$site_sales = array();
		?>

		<div class="ui grid">
			<div class="twelve wide column">
				<h3 class="ui header handle-drag">
				<?php esc_html_e( 'WooCommerce Status', 'mainwp-woocommerce-status-extension' ); ?>
					<div class="sub header"><?php esc_html_e( 'See the WooCommerce stats', 'mainwp-woocommerce-status-extension' ); ?></div>
				</h3>
			</div>
			<div class="four wide column right aligned"></div>
		</div>

		<?php
		if ( $individual_metabox ) {
			$website_id = isset( $_GET['dashboard'] ) ? $_GET['dashboard'] : 0;
			if ( empty( $website_id ) ) {
				return;
			}
			$db_status     = 0;
			$woocom_status = MainWP_WooCommerce_Status_DB::get_instance()->get_status_by( 'site_id', $website_id );
			if ( empty( $woocom_status ) || ! $woocom_status->active ) {

				?>
				<div class="ui hidden divider"></div>
				<div class="ui hidden divider"></div>
				<h2 class="ui icon header">
					<i class="cart icon"></i>
					<div class="content">
						<?php _e( 'No data available!', 'mainwp-woocommerce-status-extension' ); ?>
						<div class="sub header"><?php _e( 'WooCommerce plugin not detected on the child site.', 'mainwp-woocommerce-status-extension' ); ?></div>
						<div class="ui hidden divider"></div>
					</div>
				</h2>
				<?php
				return;
			} else {
				$status = $woocom_status->status;
				$status = unserialize( $status );
				if ( is_array( $status ) ) {
					$sales    = isset( $status['formated_sales'] ) ? $status['formated_sales'] : 'N/A';
					$awaiting = isset( $status['awaiting'] ) ? $status['awaiting'] : 0;
					$onhold   = isset( $status['onhold'] ) ? $status['onhold'] : 0;
					$lowstock = isset( $status['lowstock'] ) ? $status['lowstock'] : 0;
					$outstock = isset( $status['outstock'] ) ? $status['outstock'] : 0;
					if ( isset( $status['top_seller'] ) && ! empty( $status['top_seller'] ) ) {
						if ( is_array( $status['top_seller'] ) ) {
							$top_seller[] = array(
								'name'       => $status['top_seller']['name'],
								'count'      => $status['top_seller']['qty'],
								'product_id' => $status['top_seller']['product_id'],
							);
						} elseif ( is_object( $status['top_seller'] ) ) {
							$top_seller[] = array(
								'name'       => $status['top_seller']->name,
								'count'      => $status['top_seller']->qty,
								'product_id' => $status['top_seller']->product_id,
							);
						}
					}
				}
				$db_status = $woocom_status->need_db_update;
			}
		} else {
			$result         = self::get_main_dashboard_wc_status();
			$sales          = $result['sales'];
			$awaiting       = $result['awaiting'];
			$onhold         = $result['onhold'];
			$lowstock       = $result['lowstock'];
			$outstock       = $result['outstock'];
			$top_seller     = $result['top_seller'];
			$site_sales     = isset( $result['site_sales'] ) ? $result['site_sales'] : array();
			$formated_sales = isset( $result['formated_sales'] ) ? $result['formated_sales'] : array();
		}
		$top_seller_lnk = '';
		if ( $individual_metabox ) {
			$awaiting_str = ( 1 == $awaiting ) ? $awaiting . ' ' . 'Order' : intval( $awaiting ) . ' ' . 'Orders';
			$onhold_str   = ( 1 == $onhold ) ? $onhold . ' ' . 'Order' : intval( $onhold ) . ' ' . 'Orders';
			$lowstock_str = ( 1 == $lowstock ) ? $lowstock . ' ' . 'Product' : intval( $lowstock ) . ' ' . 'Products';
			$outstock_str = ( 1 == $outstock ) ? $outstock . ' ' . 'Product' : intval( $outstock ) . ' ' . 'Products';

			$product_id    = '';
			$product_name  = 'N/A';
			$product_count = 0;

			if ( ! empty( $top_seller ) && isset( $top_seller[0] ) ) {
				$top_sel = $top_seller[0];
				if ( is_array( $top_sel ) && isset( $top_sel['product_id'] ) ) {
					$product_id    = $top_sel['product_id'];
					$product_name  = $top_sel['name'];
					$product_count = $top_sel['count'];
				} elseif ( is_object( $top_sel ) && property_exists( $top_sel, 'product_id' ) ) {
					$product_id    = $top_sel->product_id;
					$product_name  = $top_sel->name;
					$product_count = $top_sel->count;
				}
			}

			$location3 = 'admin.php?page=wc-reports&tab=orders&range=month';
			$sales_lnk = '<a href="admin.php?page=SiteOpen&newWindow=yes&websiteid=' . $website_id . '&location=' . base64_encode( $location3 ) . '&_opennonce=' . wp_create_nonce( 'mainwp-admin-nonce' ) . '" target="_blank">' . $sales . '</a>';

			if ( ! empty( $product_id ) ) {
				$location4      = 'admin.php?page=wc-reports&tab=orders&report=sales_by_product&range=month&product_ids=' . $product_id;
				$top_seller_lnk = '<a href="admin.php?page=SiteOpen&newWindow=yes&websiteid=' . $website_id . '&location=' . base64_encode( $location4 ) . '&_opennonce=' . wp_create_nonce( 'mainwp-admin-nonce' ) . '" target="_blank">' . $product_name . '</a><br/><span class="mainwp-wcs-downrow">top seller this month (sold ' . $product_count . ')</span>';
			}

			$location5    = 'edit.php?post_status=wc-processing&post_type=shop_order';
			$awaiting_lnk = '<a href="admin.php?page=SiteOpen&newWindow=yes&websiteid=' . $website_id . '&location=' . base64_encode( $location5 ) . '&_opennonce=' . wp_create_nonce( 'mainwp-admin-nonce' ) . '" target="_blank">' . $awaiting_str . '</a>';

			$location6  = 'edit.php?post_status=wc-on-hold&post_type=shop_order';
			$onhold_lnk = '<a href="admin.php?page=SiteOpen&newWindow=yes&websiteid=' . $website_id . '&location=' . base64_encode( $location6 ) . '&_opennonce=' . wp_create_nonce( 'mainwp-admin-nonce' ) . '" target="_blank">' . $onhold_str . '</a>';

			$location7    = 'admin.php?page=wc-reports&tab=stock&report=low_in_stock';
			$lowstock_lnk = '<a href="admin.php?page=SiteOpen&newWindow=yes&websiteid=' . $website_id . '&location=' . base64_encode( $location7 ) . '&_opennonce=' . wp_create_nonce( 'mainwp-admin-nonce' ) . '" target="_blank">' . $lowstock_str . '</a>';

			$location8    = 'admin.php?page=wc-reports&tab=stock&report=out_of_stock';
			$outstock_lnk = '<a href="admin.php?page=SiteOpen&newWindow=yes&websiteid=' . $website_id . '&location=' . base64_encode( $location8 ) . '&_opennonce=' . wp_create_nonce( 'mainwp-admin-nonce' ) . '" target="_blank">' . $outstock_str . '</a>';
		} else {
			$awaiting_str = ( 1 == $awaiting ) ? $awaiting . ' ' . 'Order' : intval( $awaiting ) . ' ' . 'Orders';
			$onhold_str   = ( 1 == $onhold ) ? $onhold . ' ' . 'Order' : intval( $onhold ) . ' ' . 'Orders';
			$lowstock_str = ( 1 == $lowstock ) ? $lowstock . ' ' . 'Product' : intval( $lowstock ) . ' ' . 'Products';
			$outstock_str = ( 1 == $outstock ) ? $outstock . ' ' . 'Product' : intval( $outstock ) . ' ' . 'Products';

			$top_seller_str = '';

			foreach ( $top_seller as $top ) {
				$top_seller_str .= $top['name'] . ', ';
			}

			$sales_lnk = number_format( $sales, 2, ',', '.' );

			if ( ! empty( $top_seller_str ) ) {
				$top_seller_lnk = rtrim( $top_seller_str, ', ' );
			}

			$awaiting_lnk = $awaiting_str;
			$onhold_lnk   = $onhold_str;
			$lowstock_lnk = $lowstock_str;
			$outstock_lnk = $outstock_str;
		}

		global $mainWPWooCommerceStatusExtensionActivator;
		?>
		<table id="mainwp-woocommerce-status-widget-table" class="ui definition table">
			<tbody>
				<?php if ( $individual_metabox ) : ?>
				<tr>
					<td><?php _e( 'Sales This Month', 'mainwp-woocoomerce-status-extension' ); ?></td>
					<td><?php echo $sales_lnk; ?></td>
				</tr>
					<?php if ( ! empty( $top_seller_lnk ) ) : ?>
					<tr>
						<td><?php _e( 'Top Seller', 'mainwp-woocoomerce-status-extension' ); ?></td>
						<td><?php echo $top_seller_lnk; ?></td>
					</tr>
					<?php endif; ?>
				<?php endif; ?>
				<tr>
					<td><?php _e( 'Awaiting Processing', 'mainwp-woocoomerce-status-extension' ); ?></td>
					<td><?php echo $awaiting_lnk; ?></td>
				</tr>
				<tr>
					<td><?php _e( 'On Hold', 'mainwp-woocoomerce-status-extension' ); ?></td>
					<td><?php echo $onhold_lnk; ?></td>
				</tr>
				<tr>
					<td><?php _e( 'Low on Stock', 'mainwp-woocoomerce-status-extension' ); ?></td>
					<td><?php echo $lowstock_lnk; ?></td>
				</tr>
				<tr>
					<td><?php _e( 'Out of Stock', 'mainwp-woocoomerce-status-extension' ); ?></td>
					<td><?php echo $outstock_lnk; ?></td>
				</tr>
			</tbody>
		</table>
		<?php if ( ! $individual_metabox ) : ?>
		<div id="mainwp-woo-status-accordion" class="ui accordion">
			<div class="title"><i class="dropdown icon"></i> <?php _e( 'Top Sellers', 'mainwp-woocoomerce-status-extension' ); ?></div>
			<div class="content">
				<table class="ui celled table mainwp-woocommerce-status-table" id="mainwp-woocommerce-status-top-sellers-table">
					<thead>
						<tr>
							<tr>
								<th><?php echo __( 'Website', 'mainwp-woocoomerce-status-extension' ); ?></th>
								<th><?php echo __( 'Product', 'mainwp-woocoomerce-status-extension' ); ?></th>
								<th><?php echo __( 'Sales', 'mainwp-woocoomerce-status-extension' ); ?></th>
							</tr>
						</tr>
					</thead>
					<tbody>
						<?php
						if ( ! empty( $top_seller ) ) {
							foreach ( $top_seller as $site_id => $top_sel ) :
								$product_id    = $top_sel['product_id'];
								$product_name  = $top_sel['name'];
								$product_count = $top_sel['count'];
								$website       = apply_filters( 'mainwp_getsites', $mainWPWooCommerceStatusExtensionActivator->get_child_file(), $mainWPWooCommerceStatusExtensionActivator->get_child_key(), $site_id );
								if ( $website && is_array( $website ) ) {
									$website = current( $website );
								}
								if ( ! empty( $product_id ) ) {
									$location4 = 'admin.php?page=wc-reports&tab=orders&report=sales_by_product&range=month&product_ids=' . $product_id;
									$top_lnk   = '<a href="admin.php?page=SiteOpen&newWindow=yes&websiteid=' . $site_id . '&location=' . base64_encode( $location4 ) . '&_opennonce=' . wp_create_nonce( 'mainwp-admin-nonce' ) . '" target="_blank">' . $product_name . '</a>';
									?>
									<tr>
										<td><a href="admin.php?page=managesites&dashboard=<?php echo $site_id; ?>"><?php echo $website['name']; ?></a></td>
										<td><?php echo $top_lnk; ?></td>
										<td><?php echo intval( $product_count ); ?></td>
									</tr>
									<?php
								}
							endforeach;
						}
						?>
					</tbody>
				</table>
			</div>
			<div class="title"><i class="dropdown icon"></i> <?php _e( 'Sales', 'mainwp-woocoomerce-status-extension' ); ?></div>
			<div class="content">
				<table class="ui celled  table mainwp-woocommerce-status-table" id="mainwp-woocommerce-status-sales-table">
					<thead>
						<tr>
							<th><?php echo __( 'Website', 'mainwp-woocoomerce-status-extension' ); ?></th>
							<th><?php echo __( 'Sales', 'mainwp-woocoomerce-status-extension' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						if ( ! empty( $formated_sales ) ) {
							foreach ( $formated_sales as $site_id => $site_sale ) :
								$location3 = 'admin.php?page=wc-reports&tab=orders&range=month';
								$sales_lnk = '<a href="admin.php?page=SiteOpen&newWindow=yes&websiteid=' . $site_id . '&location=' . base64_encode( $location3 ) . '&_opennonce=' . wp_create_nonce( 'mainwp-admin-nonce' ) . '" target="_blank">' . $site_sale . '</a>';
								$website   = apply_filters( 'mainwp_getsites', $mainWPWooCommerceStatusExtensionActivator->get_child_file(), $mainWPWooCommerceStatusExtensionActivator->get_child_key(), $site_id );
								if ( $website && is_array( $website ) ) {
									$website = current( $website );
								}
								?>
								<tr>
									<td><a href="admin.php?page=managesites&dashboard=<?php echo $site_id; ?>"><?php echo $website['name']; ?></a></td>
									<td data-order="<?php echo isset( $site_sales[ $site_id ] ) ? esc_html( $site_sales[ $site_id ] ) : 0; ?>"><?php echo $sales_lnk; ?></td>
								</tr>
								<?php
							endforeach;
						}
						?>
					</tbody>
				</table>
			</div>
		</div>
		<script type="text/javascript">
		jQuery( document ).ready( function () {
			jQuery( '#mainwp-woo-status-accordion' ).accordion();
			jQuery( '.mainwp-woocommerce-status-table' ).DataTable( {
				"searching" : false,
				"stateSave":  true,
				"stateDuration": 0,
				"paging": false,
				"info": false,
				"scrollX" : false,
			} );
		} );
		</script>
		<?php endif; ?>
		<?php
	}

	public static function get_main_dashboard_wc_status() {

		$woo_values = MainWP_WooCommerce_Status_DB::get_instance()->get_status_by( 'all' );

		$result = array(
			'sales'            => 0,
			'awaiting'         => 0,
			'onhold'           => 0,
			'lowstock'         => 0,
			'outstock'         => 0,
			'top_seller'       => array(),
			'top_seller_count' => 0,
		);

		if ( is_array( $woo_values ) ) {
			foreach ( $woo_values as $woo ) {
				if ( $woo->active ) {
					$status = $woo->status;
					$status = unserialize( $status );

					if ( is_array( $status ) ) {
						$result['sales']                          += isset( $status['sales'] ) ? $status['sales'] : 0;
						$result['site_sales'][ $woo->site_id ]     = isset( $status['sales'] ) ? $status['sales'] : 0;
						$result['formated_sales'][ $woo->site_id ] = isset( $status['formated_sales'] ) ? $status['formated_sales'] : 0;
						$result['awaiting']                       += isset( $status['awaiting'] ) ? $status['awaiting'] : 0;
						$result['onhold']                         += isset( $status['onhold'] ) ? $status['onhold'] : 0;
						$result['lowstock']                       += isset( $status['lowstock'] ) ? $status['lowstock'] : 0;
						$result['outstock']                       += isset( $status['outstock'] ) ? $status['outstock'] : 0;
						if ( isset( $status['top_seller'] ) && ! empty( $status['top_seller'] ) ) {
							if ( is_array( $status['top_seller'] ) ) {
								$result['top_seller'][ $woo->site_id ] = array(
									'name'       => $status['top_seller']['name'],
									'count'      => $status['top_seller']['qty'],
									'product_id' => $status['top_seller']['product_id'],

								);
							} else {
								$result['top_seller'][ $woo->site_id ] = array(
									'name'       => $status['top_seller']->name,
									'count'      => $status['top_seller']->qty,
									'product_id' => $status['top_seller']->product_id,
								);
							}
						}
					}

					$result['db_status'][ $woo->site_id ] = $woo->need_db_update;
				}
			}
		}
		return $result;
	}
}
