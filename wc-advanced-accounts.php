<?php
/**
 * Plugin Name: Advanced Accounts for WooCommerce
 * Plugin URI: https://yoohw.com/docs/category/woocommerce-advanced-accounts/
 * Description: Customize WooCommerce accounts with customer profiles, CAPTCHA, email verification and OTP, privacy requests, account links, and membership tools.
 * Version: 1.5.0
 * Author: YoOhw.com
 * Author URI: https://yoohw.com
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * Text Domain: wc-advanced-accounts
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires Plugins: woocommerce
 */

if (!defined('ABSPATH')) {
	exit;
}

class YOAA_WC_Advanced_Accounts {
	
	public function __construct() {
		$wcaa_plugin_data = get_file_data(__FILE__, ['Version' => 'Version'], false);
		$wcaa_plugin_version = isset($wcaa_plugin_data['Version']) ? $wcaa_plugin_data['Version'] : '';
		define('YOAA_WC_ADVANCED_ACCOUNTS_VERSION', $wcaa_plugin_version);

		add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_action_links']);
		add_action('before_woocommerce_init', [$this, 'declare_woocommerce_compatibility']);
		$this->includes();
	}

	public function includes() {
		include_once plugin_dir_path(__FILE__) . 'inc/cores/notices.php';
		include_once plugin_dir_path(__FILE__) . 'inc/cores/backend.php';
		include_once plugin_dir_path(__FILE__) . 'inc/cores/frontend.php';
	}

	public function add_action_links($links) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=wc-settings&tab=account&section=advanced' ) ),
			esc_html__( 'Settings', 'wc-advanced-accounts' )
		);
		$custom_links = [ 'settings' => $settings_link ];
		if ( YOAA_WC_Advanced_Accounts_Add_Sub_Tab::show_upgrade_promotions() ) {
			$custom_links['yoaa_upgrade'] = '<a href="https://yoohw.com/product/woocommerce-advanced-accounts-premium/" target="_blank" rel="noopener noreferrer" style="font-weight:700;">' . esc_html__( 'Upgrade Premium', 'wc-advanced-accounts' ) . ' <span aria-hidden="true">↗</span></a>';
		}
		$links = array_merge( $custom_links, $links );
		return $links;
	}

	public function declare_woocommerce_compatibility() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
}

// Initialize the plugin
new YOAA_WC_Advanced_Accounts();
