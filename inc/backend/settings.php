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
		$request_text = isset( $field['request_text'] ) ? (string) $field['request_text'] : '';
		$request_url  = isset( $field['request_url'] ) ? (string) $field['request_url'] : '';

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

		echo '<p class="yoaa-upgrade-actions"><a class="button button-secondary" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $button_text ) . '</a>';
		if ( '' !== $request_text && '' !== $request_url ) {
			echo '<a class="button button-secondary" href="' . esc_url( $request_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $request_text ) . '</a>';
		}
		echo '</p>';
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
