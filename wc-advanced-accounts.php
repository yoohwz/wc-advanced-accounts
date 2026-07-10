<?php
/**
 * Plugin Name: Advanced Accounts for WooCommerce
 * Plugin URI: https://yoohw.com/docs/category/woocommerce-advanced-accounts/
 * Description: Upgrade My Account, email/phone verification, and login with OTP.
 * Version: 1.4.4
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
		array_unshift($links, $settings_link);
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
