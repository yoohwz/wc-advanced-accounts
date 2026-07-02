<?php

if (!defined('ABSPATH')) {
	exit;
}

class YOAA_WC_Advanced_Accounts_Add_Sub_Tab {

private $role_manager;

	public function __construct() {
		require_once plugin_dir_path(__FILE__) . '/settings/membership-add-remove-user-role.php';
		$this->role_manager = new YOAA_WC_Advanced_Accounts_Membership_Add_Remove_User_Role_Free ();

		add_filter('woocommerce_settings_tabs_array', [$this, 'rename_account_settings_tab'], 999);
		add_filter('woocommerce_get_sections_account', [$this, 'add_subsections']);
		add_filter('woocommerce_get_settings_account', [$this, 'add_subsection_settings'], 10, 2);
		add_action('wp_ajax_generate_sms_key', [$this, 'handle_generate_sms_key']);

		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_advanced_settings_inline_js' ] );
		add_action( 'woocommerce_admin_field_yoaa_upgrade_panel', [ __CLASS__, 'render_upgrade_panel_field' ] );

		add_action('admin_head', [$this, 'hide_save_button']);

		$this->includes();
	}

	public static function render_upgrade_panel_field( $field ) {
		$title       = isset( $field['title'] ) ? (string) $field['title'] : __( 'Available in Premium', 'wc-advanced-accounts' );
		$description = isset( $field['desc'] ) ? (string) $field['desc'] : __( 'These features are optional and are not required to use the free plugin.', 'wc-advanced-accounts' );
		$features    = isset( $field['features'] ) && is_array( $field['features'] ) ? $field['features'] : array();
		$button_text = isset( $field['button_text'] ) ? (string) $field['button_text'] : __( 'View Premium features', 'wc-advanced-accounts' );
		$url         = isset( $field['url'] ) ? (string) $field['url'] : 'https://yoohw.com/product/woocommerce-advanced-accounts-premium/';

		echo '<tr valign="top" class="yoaa-upgrade-panel-row"><th scope="row" class="titledesc"></th><td class="forminp">';
		echo '<div class="yoaa-upgrade-panel">';
		echo '<p class="yoaa-upgrade-eyebrow">' . esc_html__( 'Available in Premium', 'wc-advanced-accounts' ) . '</p>';
		echo '<h3>' . esc_html( $title ) . '</h3>';
		echo '<p>' . esc_html( $description ) . '</p>';

		if ( ! empty( $features ) ) {
			echo '<ul>';
			foreach ( $features as $feature ) {
				echo '<li>' . esc_html( (string) $feature ) . '</li>';
			}
			echo '</ul>';
		}

		echo '<p><a class="button button-secondary" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $button_text ) . '</a></p>';
		echo '</div>';
		echo '</td></tr>';
	}

	public function rename_account_settings_tab( $tabs ) {
		if ( isset( $tabs['account'] ) ) {
			$tabs['account'] = __( 'Accounts & Membership', 'wc-advanced-accounts' );
		}

		return $tabs;
	}

	public function add_subsections($sections) {
		$sections['advanced'] = __('Advanced', 'wc-advanced-accounts');
		$sections['appearance'] = __('Profile', 'wc-advanced-accounts');
		$sections['membership'] = __('Membership', 'wc-advanced-accounts');
		$sections['endpoints'] = __( 'Endpoints', 'wc-advanced-accounts' );
		$sections['tools'] = __( 'Tools', 'wc-advanced-accounts' );
		return $sections;
		}

		public function add_subsection_settings($settings, $current_section) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only WooCommerce settings routing.
			$current_subsection = isset($_GET['subsection']) ? sanitize_text_field(wp_unslash($_GET['subsection'])) : '';	

		if ($current_section == 'general') {
			$settings = WC_Admin_Settings::get_settings('accounts');
		} elseif ($current_section === 'membership' && $current_subsection === 'add_remove_role') {
			$this->role_manager->display_add_remove_role();
		} elseif ($current_section == 'advanced') {
			$settings = array_merge(
				YOAA_WC_Advanced_Accounts_Advanced_Settings::get_advanced_account_settings(),
				YOAA_WC_Advanced_Accounts_Advanced_Settings::get_verification_settings(),
				YOAA_WC_Advanced_Accounts_Advanced_Settings::get_additionals_settings()
			);
		} elseif ( $current_section === 'appearance' ) {
			$settings  = array_merge(
				YOAA_WC_Advanced_Accounts_Appearance_Settings::get_personalization_settings(),
				YOAA_WC_Advanced_Accounts_Appearance_Settings::get_registration_settings()
			);
		} elseif ( $current_section === 'membership' ) {
			$settings = YOAA_WC_Advanced_Accounts_Membership_Settings::get_membership_settings();
		} elseif ( $current_section === 'endpoints' ) {
			$settings = YOAA_WC_Advanced_Accounts_Endpoints_Settings::get_endpoint_settings();
		} elseif ( $current_section === 'tools' ) {
			$settings = YOAA_WC_Advanced_Accounts_Tools::get_tools();
		}
		return $settings;
	}

	public static function enqueue_advanced_settings_inline_js( $hook_suffix ) {
		if ( 'woocommerce_page_wc-settings' !== $hook_suffix ) {
			return;
		}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only WooCommerce settings routing.
			$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only WooCommerce settings routing.
			$section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '';

		// Only: Settings > Accounts > Advanced
		if ( 'account' !== $tab || 'advanced' !== $section ) {
			return;
		}

		// Your existing computed values
		$sms_key   = get_option( 'yoohw_phone_verification_sms_key', '' );
		$sms_quota = (float) get_option( 'yoohw_phone_verification_sms_quota', '0.00' );

		if ( $sms_quota > 15 ) {
			$text_color = '#00a32a';
		} elseif ( $sms_quota > 5 ) {
			$text_color = '#dba617';
		} else {
			$text_color = '#d63638';
		}

		if ( ! wp_script_is( 'yoaa-wc-advanced-accounts-admin', 'enqueued' ) ) {
			wp_enqueue_script(
				'yoaa-wc-advanced-accounts-admin',
				plugin_dir_url( __FILE__ ) . '../../js/admin-free-verification.js',
				[ 'jquery' ],
				YOAA_WC_ADVANCED_ACCOUNTS_VERSION,
				true
			);
		}

		$data = [
			'smsQuota'      => number_format_i18n( $sms_quota, 2 ),
			'smsQuotaColor' => $text_color,
			'smsKey'        => $sms_key,
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'nonce'         => wp_create_nonce( 'generate_sms_key_nonce' ),
			'historyBaseUrl'=> 'https://bmc.yoohw.com/sms/smslog/',
			'purchaseUrl'   => 'https://yoohw.com/product/sms-credits/',
			'howItWorksUrl' => 'https://yoohw.com/docs/woocommerce-advanced-accounts/settings/phone-verification/',
			'i18n'          => [
				'historyLogs'          => __( 'History logs', 'wc-advanced-accounts' ),
				'purchaseYoCredits'    => __( 'Purchase Yo Credits', 'wc-advanced-accounts' ),
				'copy'                 => __( 'Copy', 'wc-advanced-accounts' ),
				'copied'               => __( 'Copied!', 'wc-advanced-accounts' ),
				'howItWorks'           => __( 'How it works?', 'wc-advanced-accounts' ),
				'useKeyForYoCredits'   => __( 'Use this key when you purchase Yo Credits.', 'wc-advanced-accounts' ),
				'smsQuota'             => __( 'SMS Quota', 'wc-advanced-accounts' ),
				'usdCreditsRemaining'  => __( 'USD credits remaining.', 'wc-advanced-accounts' ),
				'generateKeyPrompt'    => __( 'Generate a new key to start using SMS Verification.', 'wc-advanced-accounts' ),
				'keyGenerated'         => __( 'Key generated and saved successfully.', 'wc-advanced-accounts' ),
				'keyGenerationFailed'  => __( 'Failed to generate the key. Please try again or contact support.', 'wc-advanced-accounts' ),
			],
		];

		$inline = 'window.YOAA_AA = ' . wp_json_encode( $data ) . ';';
		wp_add_inline_script( 'yoaa-wc-advanced-accounts-admin', $inline, 'before' );
	}

		public function handle_generate_sms_key() {
			if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error([
					'message' => __('You do not have permission to perform this action.', 'wc-advanced-accounts')
				]);
			}

			// Verify nonce before processing
			if (
				!isset($_POST['security']) ||
			!wp_verify_nonce(
				sanitize_text_field(wp_unslash($_POST['security'])),
				'generate_sms_key_nonce'
			)
		) {
			wp_send_json_error([
				'message' => __('Security check failed.', 'wc-advanced-accounts')
			]);
		}
	
		// Unslash and sanitize the sms_key
		$sms_key = isset($_POST['sms_key']) ? sanitize_text_field(wp_unslash($_POST['sms_key'])) : '';
	
		if (empty($sms_key)) {
			wp_send_json_error([
				'message' => __('Invalid or empty key provided.', 'wc-advanced-accounts')
			]);
		}
	
		// Prepare data for API call
		$domain     = get_site_url();
		$site_email = get_option('admin_email');
	
		$api_url = 'https://bmc.yoohw.com/wp-json/sms/v1/sms_key_generate/';
		$body    = array(
			'sms_key'    => $sms_key,
			'domain'     => $domain,
			'site_email' => $site_email,
		);
	
		$response = wp_remote_post($api_url, array(
			'method'  => 'POST',
			'body'    => wp_json_encode($body),
			'headers' => array('Content-Type' => 'application/json'),
		));
	
		// Check for WP errors in API call
		if (is_wp_error($response)) {
			wp_send_json_error([
				'message' => __('API call failed: ', 'wc-advanced-accounts') . $response->get_error_message()
			]);
		}
	
		$response_code = wp_remote_retrieve_response_code($response);
		$response_body = wp_remote_retrieve_body($response);
		$data          = json_decode($response_body, true);
	
		// Ensure response is a success
		if ($response_code !== 200 || !isset($data['status']) || $data['status'] !== 'success') {
			wp_send_json_error([
				'message' => __('API error: ', 'wc-advanced-accounts') . (isset($data['message']) ? $data['message'] : __('Unknown error', 'wc-advanced-accounts'))
			]);
		}
	
		// Only update the option if the API call was successful
		$updated = update_option('yoohw_phone_verification_sms_key', $sms_key);
		if ($updated) {
			wp_send_json_success([
				'message' => __('Key generated and saved successfully.', 'wc-advanced-accounts')
			]);
		} else {
			wp_send_json_error([
				'message' => __('Failed to save the generated key. Please try again.', 'wc-advanced-accounts')
			]);
		}
	}

	public function hide_save_button() {
        global $pagenow;
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only admin screen routing.
		$page       = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		$tab        = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
		$section    = isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : '';
		$subsection = isset( $_GET['subsection'] ) ? sanitize_key( wp_unslash( $_GET['subsection'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

        if (
            $pagenow === 'admin.php'
            && $page === 'wc-settings'
            && $tab === 'account'
        ) {
			$is_tools = 'tools' === $section;

			$is_add_remove_role = (
                $section === 'membership'
                && $subsection === 'add_remove_role'
            );

            if ( $is_tools || $is_add_remove_role ) {
                wp_register_style('yowcaa-admin-css', false, array(), '1.0');
                wp_enqueue_style('yowcaa-admin-css');
                $inline_css = '.woocommerce .woocommerce-save-button { display: none; }';
                wp_add_inline_style('yowcaa-admin-css', $inline_css);
            }
        }
    }

	public function includes() {
		include_once plugin_dir_path(__FILE__) . 'settings/advanced.php';
		include_once plugin_dir_path(__FILE__) . 'settings/endpoints.php';
		include_once plugin_dir_path(__FILE__) . 'settings/appearance.php';
		include_once plugin_dir_path(__FILE__) . 'settings/membership.php';
		include_once plugin_dir_path(__FILE__) . 'settings/tools.php';
	}
}

// Initialize the class
new YOAA_WC_Advanced_Accounts_Add_Sub_Tab();

add_action('admin_init', function() {
	if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if (get_option('yoaa_wc_enable_phone_number_account', 'no') === 'yes') {
		update_option('woocommerce_registration_generate_username', 'no');
	}
	if (get_option('yoaa_wc_disable_email_on_registration', 'no') === 'yes') {
		update_option('woocommerce_registration_generate_username', 'no');
		update_option('woocommerce_registration_generate_password', 'no');
	}
	if (get_option('yoaa_wc_enable_email_verification', 'no') === 'yes') {
		update_option('woocommerce_registration_generate_password', 'no');
	}
});

add_action( 'admin_enqueue_scripts', function( $hook_suffix ) {
	// Only load on WooCommerce settings page.
	if ( 'woocommerce_page_wc-settings' !== $hook_suffix ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( empty( $screen ) || empty( $screen->id ) ) {
		return;
	}

	// On wc-settings, tab/section are part of the request, but don't read $_GET directly.
	// Use filter_input() which the sniffer accepts as validated input.
	$tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
	$tab = is_string( $tab ) ? sanitize_key( $tab ) : '';

	if ( 'account' !== $tab ) {
		return;
	}

	wp_enqueue_script( 'jquery' );

	// Ensure the handle exists; woocommerce_admin is usually registered by WooCommerce.
	// If not, attach to jquery as fallback to avoid missing-handle issues.
	$handle = wp_script_is( 'woocommerce_admin', 'registered' ) ? 'woocommerce_admin' : 'jquery';

	wp_add_inline_script(
		$handle,
		"jQuery(document).ready(function($) {
			var phoneNumberAccountEnabled   = " . wp_json_encode( get_option( 'yoaa_wc_enable_phone_number_account', 'no' ) === 'yes' ) . ";
			var emailOnRegistrationDisabled = " . wp_json_encode( get_option( 'yoaa_wc_disable_email_on_registration', 'no' ) === 'yes' ) . ";
			var emailVerificationEnabled    = " . wp_json_encode( get_option( 'yoaa_wc_enable_email_verification', 'no' ) === 'yes' ) . ";
			var phoneVerificationEnabled    = " . wp_json_encode( get_option( 'yoaa_wc_enable_phone_verification', 'no' ) === 'yes' ) . ";

			function disableFields() {
				if (phoneNumberAccountEnabled || emailOnRegistrationDisabled) {
					$('#woocommerce_registration_generate_username').prop('disabled', true);
				}
				if (emailVerificationEnabled || emailOnRegistrationDisabled) {
					$('#woocommerce_registration_generate_password').prop('disabled', true);
				}
			}

			disableFields();

			var observer = new MutationObserver(function() {
				disableFields();
			});
			observer.observe(document.body, { childList: true, subtree: true });

			$('#woocommerce_enable_signup_and_login_from_checkout, #woocommerce_enable_myaccount_registration')
				.on('change', function() {
					disableFields();
				});
		});"
	);
}, 20 );
