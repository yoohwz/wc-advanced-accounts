<?php

if (!defined('ABSPATH')) {
	exit;
}

class YOAA_WC_Advanced_Accounts_Backend {
	private $version;

	public function __construct() {
		$this->version = YOAA_WC_ADVANCED_ACCOUNTS_VERSION;

		$this->includes();
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
		add_action('admin_init', [$this, 'check_version']);
	}

	public function includes() {
		include_once plugin_dir_path(__FILE__) . '../backend/settings.php';
		include_once plugin_dir_path(__FILE__) . '../backend/users/users-page.php';
		include_once plugin_dir_path(__FILE__) . '../backend/actions/phone-account-username.php';
		include_once plugin_dir_path(__FILE__) . '../backend/actions/email-registration-disabled.php';
		include_once plugin_dir_path(__FILE__) . '../backend/actions/email-verification.php';
		include_once plugin_dir_path(__FILE__) . '../backend/actions/phone-verification.php';
			include_once plugin_dir_path(__FILE__) . '../backend/actions/login-otp.php';
			include_once plugin_dir_path(__FILE__) . '../backend/actions/reset-password.php';
			include_once plugin_dir_path(__FILE__) . '../backend/actions/redirect-wp-login.php';
			include_once plugin_dir_path(__FILE__) . 'api/sms/update-sms-quota.php';
			include_once plugin_dir_path(__FILE__) . 'api/push-subscription.php';
		}

	public function enqueue_scripts() {
		wp_enqueue_style('wc-advanced-accounts-css', plugin_dir_url(__FILE__) . '../../css/backend.css', '1.1.2', true);
	}

	    public function check_version() {
			if ( get_option( 'wc_advanced_accounts_version' ) !== $this->version ) {
				update_option( 'wc_advanced_accounts_version', $this->version );
			}
	    }	
	}

new YOAA_WC_Advanced_Accounts_Backend();
