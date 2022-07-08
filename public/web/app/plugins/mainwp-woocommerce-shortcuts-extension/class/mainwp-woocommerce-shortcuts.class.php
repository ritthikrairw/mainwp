<?php
/**
 * MainWP WooCommerce Shortcuts Extension content.
 *
 * @package MainWP/WooCommerce_Shortcuts
 */

namespace MainWP\WooCommerce_Shortcuts;

/**
 * Class MainWP_WooCommerce_Shortcuts
 *
 * MainWP WooCommerce Shortcuts Extension content.
 */
class MainWP_WooCommerce_Shortcuts {

	/**
	 * Protected variable to hold option value.
	 *
	 * @var string
	 */
	protected $option;

	/**
	 * Protected variable to hold option handle.
	 *
	 * @var string
	 */
	protected $option_handle = 'mainwp_woocommerce_shortcuts_extension';


	/**
	 * MainWP_WooCommerce_Shortcuts class contructor.
	 *
	 * @uses MainWP_WooCommerce_Shortcuts::site_synced()
	 * @uses MainWP_WooCommerce_Shortcuts::delete_site_data()
	 */
	public function __construct() {
		$this->option = get_option( $this->option_handle );
		add_action( 'mainwp_site_synced', array( &$this, 'site_synced' ), 10, 1 );
		add_action( 'mainwp_delete_site', array( &$this, 'delete_site_data' ), 8, 1 );
	}

	/**
	 * Check if the WooCommerce plugin is installed and activated on the child site while syncing. Save result to the database.
	 *
	 * @param object $website Object containing the website data.
	 *
	 * @uses update_option() Updates the value of an option that was already added.
	 * @see https://developer.wordpress.org/reference/functions/update_option/
	 */
	public function site_synced( $website ) {
		$actived = 0;

		if ( is_object( $website ) && '' != $website->plugins ) {
			$plugins = json_decode( $website->plugins, 1 );
			if ( is_array( $plugins ) ) {
				foreach ( $plugins as $plugin ) {
					if ( 'woocommerce/woocommerce.php' == $plugin['slug'] ) {
						if ( $plugin['active'] ) {
							$actived = 1;
						}
						break;
					}
				}
			}
		}
		update_option( 'mainwp_woocom_shortcuts_site_plugin_actived_' . $website->id, $actived );
	}

	/**
	 * Remove WooCommerce plugin install status from the database when removing the child site from the MainWP Dashboard.
	 *
	 * @param object $website Object containing the website data.
	 *
	 * @uses delete_option() Removes option by name. Prevents removal of protected WordPress options.
	 * @see https://developer.wordpress.org/reference/functions/delete_option/
	 */
	public function delete_site_data( $website ) {
		if ( $website ) {
			delete_option( 'mainwp_woocom_shortcuts_site_plugin_actived_' . $website->id );
		}
	}

	/**
	 * Get the extension metabox (widget) title.
	 *
	 * @return string
	 */
	public static function get_name() {
		$name = __( 'Woocommerce Shortcuts', 'mainwp-woocommerce-shortcuts-extension' );
		return $name;
	}


	/**
	 * Render the extension page content.
	 */
	public static function render_settings() {
		?>
		<div class="ui very padded segment">
			<h2 class="ui icon header">
				<i class="compass icon"></i>
				<div class="content">
					<?php esc_html_e( 'Nothing Here', 'mainwp-woocommerce-shortcuts-extension' ); ?>
					<div class="sub header">
						<div><?php esc_html_e( 'This extension does not have the dedicated extension page.', 'mainwp-woocommerce-shortcuts-extension' ); ?></div>
						<div><?php esc_html_e( 'It only adds a widget on individual site Overview page.', 'mainwp-woocommerce-shortcuts-extension' ); ?></div>
					</div>
				</div>
			</h2>
		</div>
		<?php
	}

	/**
	 * Render the extension metabox (widget) HTML content.
	 */
	public static function render_woocommerce_shortcuts_widget() {
		$website_id = isset( $_GET['dashboard'] ) ? $_GET['dashboard'] : 0;
		if ( empty( $website_id ) ) {
			return;
		}
		?>
		<div class="ui grid">
			<div class="twelve wide column">
				<h3 class="ui header handle-drag">
					<?php esc_html_e( 'WooCommerce Shortcuts', 'mainwp-woocommerce-shortcuts-extension' ); ?>
					<div class="sub header"><?php esc_html_e( 'Quickly go to the WooCommerce pages', 'mainwp-woocommerce-shortcuts-extension' ); ?></div>
				</h3>
			</div>
			<div class="four wide column right aligned"></div>
		</div>

		<?php
		$actived = get_option( 'mainwp_woocom_shortcuts_site_plugin_actived_' . $website_id );

		if ( empty( $actived ) ) {
			?>
			<h2 class="ui icon header">
				<i class="info circle icon"></i>
				<div class="content">
					<?php esc_html_e( 'WooCommerce not detected', 'mainwp-woocommerce-shortcuts-extension' ); ?>
					<div class="sub header"><?php esc_html_e( 'First, install and activate the WooCommerce plugin on the child site.', 'mainwp-woocommerce-shortcuts-extension' ); ?></div>
				</div>
			</h2>
			<?php
			return;
		}

		$location1  = 'edit.php?post_type=shop_order';
		$location2  = 'edit.php?post_type=shop_coupon';
		$location3  = 'admin.php?page=wc-reports';
		$location4  = 'admin.php?page=wc-settings';
		$location5  = 'admin.php?page=wc-status';
		$location6  = 'admin.php?page=wc-addons';
		$location7  = 'edit.php?post_type=product';
		$location8  = 'post-new.php?post_type=product';
		$location9  = 'edit-tags.php?taxonomy=product_cat&post_type=product';
		$location10 = 'edit-tags.php?taxonomy=product_tag&post_type=product';

		$location12 = 'edit.php?post_type=product&page=product_attributes';

		$woo_orders_lnk        = '<a href="' . esc_url( 'admin.php?page=SiteOpen&newWindow=yes&websiteid=' . $website_id . '&location=' . base64_encode( $location1 ) ) . '&_opennonce=' . wp_create_nonce( 'mainwp-admin-nonce' ) . '" target="_blank">' . __( 'Orders', 'mainwp-woocommerce-shortcuts-extension' ) . '</a>'; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions -- base64_encode() required to achieve desired results. Pull request solutions appreciated.
		$woo_coupons_lnk       = '<a href="' . esc_url( 'admin.php?page=SiteOpen&newWindow=yes&websiteid=' . $website_id . '&location=' . base64_encode( $location2 ) ) . '&_opennonce=' . wp_create_nonce( 'mainwp-admin-nonce' ) . '" target="_blank">' . __( 'Coupons', 'mainwp-woocommerce-shortcuts-extension' ) . '</a>'; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions -- base64_encode() required to achieve desired results. Pull request solutions appreciated.
		$woo_reports_lnk       = '<a href="' . esc_url( 'admin.php?page=SiteOpen&newWindow=yes&websiteid=' . $website_id . '&location=' . base64_encode( $location3 ) ) . '&_opennonce=' . wp_create_nonce( 'mainwp-admin-nonce' ) . '" target="_blank">' . __( 'Reports', 'mainwp-woocommerce-shortcuts-extension' ) . '</a>'; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions -- base64_encode() required to achieve desired results. Pull request solutions appreciated.
		$woo_settings_lnk      = '<a href="' . esc_url( 'admin.php?page=SiteOpen&newWindow=yes&websiteid=' . $website_id . '&location=' . base64_encode( $location4 ) ) . '&_opennonce=' . wp_create_nonce( 'mainwp-admin-nonce' ) . '" target="_blank">' . __( 'Settings', 'mainwp-woocommerce-shortcuts-extension' ) . '</a>'; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions -- base64_encode() required to achieve desired results. Pull request solutions appreciated.
		$woo_system_status_lnk = '<a href="' . esc_url( 'admin.php?page=SiteOpen&newWindow=yes&websiteid=' . $website_id . '&location=' . base64_encode( $location5 ) ) . '&_opennonce=' . wp_create_nonce( 'mainwp-admin-nonce' ) . '" target="_blank">' . __( 'Status', 'mainwp-woocommerce-shortcuts-extension' ) . '</a>'; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions -- base64_encode() required to achieve desired results. Pull request solutions appreciated.
		$woo_addons_lnk        = '<a href="' . esc_url( 'admin.php?page=SiteOpen&newWindow=yes&websiteid=' . $website_id . '&location=' . base64_encode( $location6 ) ) . '" target="_blank">' . __( 'Extensions', 'mainwp-woocommerce-shortcuts-extension' ) . '</a>'; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions -- base64_encode() required to achieve desired results. Pull request solutions appreciated.

		$woo_products_lnk    = '<a href="' . esc_url( 'admin.php?page=SiteOpen&newWindow=yes&websiteid=' . $website_id . '&location=' . base64_encode( $location7 ) ) . '&_opennonce=' . wp_create_nonce( 'mainwp-admin-nonce' ) . '" target="_blank">' . __( 'Products', 'mainwp-woocommerce-shortcuts-extension' ) . '</a>'; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions -- base64_encode() required to achieve desired results. Pull request solutions appreciated.
		$woo_add_product_lnk = '<a href="' . esc_url( 'admin.php?page=SiteOpen&newWindow=yes&websiteid=' . $website_id . '&location=' . base64_encode( $location8 ) ) . '&_opennonce=' . wp_create_nonce( 'mainwp-admin-nonce' ) . '" target="_blank">' . __( 'Add Product', 'mainwp-woocommerce-shortcuts-extension' ) . '</a>'; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions -- base64_encode() required to achieve desired results. Pull request solutions appreciated.
		$woo_categories_lnk  = '<a href="' . esc_url( 'admin.php?page=SiteOpen&newWindow=yes&websiteid=' . $website_id . '&location=' . base64_encode( $location9 ) ) . '&_opennonce=' . wp_create_nonce( 'mainwp-admin-nonce' ) . '" target="_blank">' . __( 'Categories', 'mainwp-woocommerce-shortcuts-extension' ) . '</a>'; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions -- base64_encode() required to achieve desired results. Pull request solutions appreciated.
		$woo_tags_lnk        = '<a href="' . esc_url( 'admin.php?page=SiteOpen&newWindow=yes&websiteid=' . $website_id . '&location=' . base64_encode( $location10 ) ) . '&_opennonce=' . wp_create_nonce( 'mainwp-admin-nonce' ) . '" target="_blank">' . __( 'Tags', 'mainwp-woocommerce-shortcuts-extension' ) . '</a>'; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions -- base64_encode() required to achieve desired results. Pull request solutions appreciated.
		$woo_attributes_lnk  = '<a href="' . esc_url( 'admin.php?page=SiteOpen&newWindow=yes&websiteid=' . $website_id . '&location=' . base64_encode( $location12 ) ) . '&_opennonce=' . wp_create_nonce( 'mainwp-admin-nonce' ) . '" target="_blank">' . __( 'Attributes', 'mainwp-woocommerce-shortcuts-extension' ) . '</a>'; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions -- base64_encode() required to achieve desired results. Pull request solutions appreciated.
		?>

		<table class="ui single line table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'WooCommerce', 'mainwp-woocommerce-shortcuts-extension' ); ?></th>
					<th><?php esc_html_e( 'Products', 'mainwp-woocommerce-shortcuts-extension' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><?php echo $woo_orders_lnk; ?></td>
					<td><?php echo $woo_products_lnk; ?></td>
				</tr>
				<tr>
					<td><?php echo $woo_coupons_lnk; ?></td>
					<td><?php echo $woo_add_product_lnk; ?></td>
				</tr>
				<tr>
					<td><?php echo $woo_reports_lnk; ?></td>
					<td><?php echo $woo_categories_lnk; ?></td>
				</tr>
				<tr>
					<td><?php echo $woo_settings_lnk; ?></td>
					<td><?php echo $woo_tags_lnk; ?></td>
				</tr>
				<tr>
					<td><?php echo $woo_system_status_lnk; ?></td>
					<td><?php echo $woo_attributes_lnk; ?></td>
				</tr>
				<tr>
					<td><?php echo $woo_addons_lnk; ?></td>
					<td></td>
				</tr>
			</tbody>
		</table>

		<?php
	}
}
