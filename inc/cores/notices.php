<?php

if (!defined('ABSPATH')) {
	exit;
}

class YOAA_WC_Advanced_Accounts_Notices {

		public function __construct() {
			add_action('admin_notices', [$this, 'display_notices']);
			add_action('wp_ajax_never_show_wc_advanced_accounts_notice', [$this, 'never_show_notice']);
			add_action('admin_enqueue_scripts', [$this, 'enqueue_inline_scripts']);
			add_action('wp_ajax_dismiss_settings_notice', [$this, 'dismiss_settings_notice']);
		}

		public function display_notices() {
			$this->settings_notice();
			$this->admin_notice();
		}

	private function can_manage_plugin() {
		return current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
	}

	public function settings_notice() {
		$user_id = get_current_user_id();

		if (
			$this->can_manage_plugin() &&
			get_user_meta( $user_id, 'wcaa_advabced_account_settings_notice', true ) !== 'yes'
		) {
			$settings_url = admin_url( 'admin.php?page=wc-settings&tab=account&section=advanced' );

			echo '<div class="notice notice-info wcaa-settings is-dismissible">';
			echo '<p><strong>' . esc_html__( 'Before using Advanced Accounts for WooCommerce,', 'wc-advanced-accounts' ) . '</strong> ' . esc_html__( 'please configure your settings.', 'wc-advanced-accounts' ) . '</p>';
			echo '<p>
				<a href="#" onclick="WC_Advanced_Accounts_Notice.dismissSettingsNotice()" class="button-secondary">' . esc_html__( 'Dismiss this notice', 'wc-advanced-accounts' ) . '</a>
				<a href="' . esc_url( $settings_url ) . '" class="button-primary">' . esc_html__( 'Go to the settings', 'wc-advanced-accounts' ) . '</a></p>';
			echo '</div>';
		}
	}

	public function admin_notice() {
		$user_id = get_current_user_id();
		$activation_time = get_user_meta($user_id, 'wc_advanced_accounts_activation_time', true);
		$current_time = current_time('timestamp');
	
		if (get_user_meta($user_id, 'wc_advanced_accounts_never_show_again', true) === 'yes') {
			return;
		}
	
		if (!$activation_time) {
			update_user_meta($user_id, 'wc_advanced_accounts_activation_time', $current_time);
			return;
		}
	
		$time_since_activation = $current_time - $activation_time;
		$days_since_activation = floor($time_since_activation / DAY_IN_SECONDS);
	
		if ($this->can_manage_plugin() && $days_since_activation >= 1) {
			echo '<div class="notice notice-info yoaa-review is-dismissible">
					<p>' . esc_html__( 'Thank you for using Advanced Accounts for WooCommerce. Please support us by', 'wc-advanced-accounts' ) . ' <a href="https://wordpress.org/plugins/wc-advanced-accounts/#reviews/#new-post" target="_blank">' . esc_html__( 'leaving a review', 'wc-advanced-accounts' ) . '</a> <span style="color: #e26f56;">&#9733;&#9733;&#9733;&#9733;&#9733;</span>.</p>
					<p><a href="#" onclick="WC_Advanced_Accounts_Notice.dismissForever()">' . esc_html__( 'Never show this again', 'wc-advanced-accounts' ) . '</a></p>
				  </div>';
		}
	}

		public function enqueue_inline_scripts() {
			$nonce_settings = wp_create_nonce('settings_nonce');
			$nonce_never_show = wp_create_nonce('never_show_wc_advanced_accounts_notice_nonce');

			$script = "
			var WC_Advanced_Accounts_Notice = {
				dismissSettingsNotice: function() {
                    jQuery.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'dismiss_settings_notice',
                            security: '{$nonce_settings}'
                        },
                        success: function() {
                            jQuery('.notice.notice-info.wcaa-settings').hide();
                        }
                    });
                },
				dismissForever: function() {
					jQuery.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'never_show_wc_advanced_accounts_notice',
							security: '{$nonce_never_show}'
						},
						success: function() {
							jQuery('.notice.notice-info.yoaa-review').hide();
							}
						});
					},
				};
			";

		wp_add_inline_script('jquery', $script);
	}

	public function dismiss_settings_notice() {
        check_ajax_referer('settings_nonce', 'security');

		if ( ! $this->can_manage_plugin() ) {
			wp_send_json_error( __( 'You do not have permission to do this.', 'wc-advanced-accounts' ), 403 );
		}

		$user_id = get_current_user_id();

        update_user_meta($user_id, 'wcaa_advabced_account_settings_notice', 'yes');
    }

	public function never_show_notice() {
		check_ajax_referer('never_show_wc_advanced_accounts_notice_nonce', 'security');

		if ( ! $this->can_manage_plugin() ) {
			wp_send_json_error( __( 'You do not have permission to do this.', 'wc-advanced-accounts' ), 403 );
		}

		$user_id = get_current_user_id();
		update_user_meta($user_id, 'wc_advanced_accounts_never_show_again', 'yes');
	}

	}

new YOAA_WC_Advanced_Accounts_Notices();
