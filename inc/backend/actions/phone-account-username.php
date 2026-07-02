<?php

if (!defined('ABSPATH')) {
	exit;
}

class YOAA_WC_Advanced_Accounts_Phone_Account_Username {
	const INTL_TEL_INPUT_VERSION = '29.0.5';

	public function __construct() {
		if (get_option('yoaa_wc_enable_phone_number_account') === 'yes') {
			add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);

			add_filter( 'woocommerce_locate_template', [$this, 'yoaa_overrite_template'], 10, 3 );
			add_filter('login_errors', [$this, 'login_error_message']);
			add_filter('woocommerce_add_error', [$this, 'override_registration_email_exists_error'], 10, 1);
			if (get_option('yoaa_wc_enable_phone_login_with_otp') !== 'yes') {
				add_filter('woocommerce_lost_password_message', [$this, 'lost_password_message']);
			}

			add_action( 'woocommerce_login_form_start', [$this, 'add_username_holder_input'] );
			add_action( 'woocommerce_register_form_start', [$this, 'add_reg_username_holder_input'] );
			add_action('woocommerce_after_checkout_billing_form', [$this, 'add_hidden_country_fields']);
			add_action( 'woocommerce_checkout_create_order', [$this, 'maybe_add_country_code_to_phone'], 10, 2 );
		}
	}

	public function enqueue_scripts() {
		if ( is_account_page() || is_checkout() || is_order_received_page() ) {
			wp_enqueue_style( 'intl-tel-input-css', plugin_dir_url( __FILE__ ) . '../../../css/intl-tel-input/intlTelInput.min.css', array(), self::INTL_TEL_INPUT_VERSION );
			wp_enqueue_script( 'intl-tel-input', plugin_dir_url( __FILE__ ) . '../../../js/intl-tel-input/intlTelInputWithUtils.min.js', array(), self::INTL_TEL_INPUT_VERSION, true );
			wp_enqueue_script( 'intl-tel-input-locale-data', plugin_dir_url( __FILE__ ) . '../../../js/intl-tel-input/locale-data.js', array( 'intl-tel-input' ), self::INTL_TEL_INPUT_VERSION, true );
			wp_enqueue_script('yoaa-phone-username', plugins_url('../../../js/phone-account-username.js', __FILE__), ['jquery', 'intl-tel-input', 'intl-tel-input-locale-data'], '1.3.2', true);
			wp_enqueue_script('yoaa-phone-login', plugins_url('../../../js/phone-login.js', __FILE__), ['jquery', 'yoaa-phone-username'], '1.3.4', true);
		}

			$allowed_countries_option = get_option('woocommerce_allowed_countries', 'all');
			$excluded_countries = [];
			$specific_countries = [];

			$specific_country = get_option('woocommerce_specific_allowed_countries', []);
			$skip_country_code = ($allowed_countries_option === 'specific' && count($specific_country) === 1);

			$country_code = 'us';
			$default_country = get_option( 'woocommerce_default_country', '' );

			if ( is_string( $default_country ) && '' !== $default_country ) {
				$default_country_parts = explode( ':', $default_country, 2 );
				$country_code = strtolower( (string) $default_country_parts[0] );
			}

			if ( $skip_country_code && is_array( $specific_country ) && ! empty( $specific_country ) ) {
				$country_code = strtolower( (string) reset( $specific_country ) );
			}
	
		if ($allowed_countries_option === 'all_except') {
			// Get the excluded countries list
			$excluded_countries = get_option('woocommerce_all_except_countries', []);
		} elseif ($allowed_countries_option === 'specific') {
			// Get the specific allowed countries list
			$specific_countries = get_option('woocommerce_specific_allowed_countries', []);
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		
		$yobm_active = ( is_plugin_active( 'wc-blacklist-manager-premium/wc-blacklist-manager-premium.php' ) && 'activated' === get_option( 'wc_blacklist_manager_premium_license_status' ) );
		$intl_tel_input_locale = self::get_intl_tel_input_locale_settings();

		// Define additional labels
		$yoaa_labels = [
			'is_user_logged_in' => is_user_logged_in(),
			'user_username' => is_user_logged_in() ? wp_get_current_user()->user_login : '',
			'initial_country'      => $country_code,
			'excluded_countries'   => $excluded_countries,
			'specific_countries'   => $specific_countries,
			'skip_country_code'    => $skip_country_code,
			'yobm_active'          => $yobm_active,
			'intl_tel_input_locale' => $intl_tel_input_locale['locale'],
			'intl_tel_input_country_name_locale' => $intl_tel_input_locale['country_name_locale'],
		];
	
		// Pass the combined data to the script
		wp_localize_script('yoaa-phone-username', 'yoaa_labels', $yoaa_labels);
	}

	private static function get_intl_tel_input_locale_settings() {
		$wp_locale         = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		$normalized_locale = self::normalize_locale_code( $wp_locale );
		$supported_locales = self::get_intl_tel_input_supported_locales();
		$locale            = self::match_intl_tel_input_locale( $normalized_locale, $supported_locales );
		$country_locale    = self::normalize_country_name_locale( $wp_locale, $locale );

		/**
		 * Filters the intl-tel-input UI locale.
		 *
		 * Use this to improve a locale, force a specific language, or support custom site language logic.
		 *
		 * @param string $locale            Matched intl-tel-input locale.
		 * @param string $wp_locale         Current WordPress locale.
		 * @param array  $supported_locales Supported intl-tel-input locales bundled with this plugin.
		 */
		$locale = apply_filters( 'yoaa_intl_tel_input_locale', $locale, $wp_locale, $supported_locales );

		if ( ! in_array( $locale, $supported_locales, true ) ) {
			$locale = 'en';
		}

		if ( 'en' === $locale && 0 !== strpos( $normalized_locale, 'en' ) ) {
			$country_locale = 'en';
		}

		/**
		 * Filters the browser Intl.DisplayNames locale used for country names.
		 *
		 * @param string $country_locale Country-name display locale.
		 * @param string $wp_locale      Current WordPress locale.
		 * @param string $locale         Matched intl-tel-input UI locale.
		 */
		$country_locale = apply_filters( 'yoaa_intl_tel_input_country_name_locale', $country_locale, $wp_locale, $locale );

		return array(
			'locale'              => $locale,
			'country_name_locale' => $country_locale,
		);
	}

	private static function normalize_locale_code( $locale ) {
		$locale = strtolower( (string) $locale );
		$locale = str_replace( '_', '-', $locale );
		$locale = preg_replace( '/[^a-z0-9-].*$/', '', $locale );

		return is_string( $locale ) ? trim( $locale, '-' ) : '';
	}

	private static function normalize_country_name_locale( $wp_locale, $matched_locale ) {
		$normalized = self::normalize_locale_code( $wp_locale );

		if ( '' === $normalized ) {
			return $matched_locale;
		}

		$parts = explode( '-', $normalized );
		$lang  = $parts[0] ?? '';
		$region = '';

		foreach ( array_slice( $parts, 1 ) as $part ) {
			if ( 2 === strlen( $part ) ) {
				$region = strtoupper( $part );
				break;
			}
		}

		if ( 'zh-hk' === $matched_locale ) {
			return 'zh-HK';
		}

		if ( '' === $lang ) {
			return $matched_locale;
		}

		return $region ? $lang . '-' . $region : $lang;
	}

	private static function match_intl_tel_input_locale( $normalized_locale, $supported_locales ) {
		$locale_map = array(
			'zh-hant' => 'zh-hk',
			'zh-hk'   => 'zh-hk',
			'zh-mo'   => 'zh-hk',
			'zh-tw'   => 'zh-hk',
			'zh-hans' => 'zh',
			'zh-cn'   => 'zh',
			'zh-sg'   => 'zh',
			'pt-br'   => 'pt',
			'pt-pt'   => 'pt',
			'nb'      => 'no',
			'nb-no'   => 'no',
			'nn'      => 'no',
			'nn-no'   => 'no',
			'iw'      => 'he',
			'iw-il'   => 'he',
			'tl'      => 'fil',
			'tl-ph'   => 'fil',
			'fil-ph'  => 'fil',
			'in'      => 'id',
			'in-id'   => 'id',
		);

		if ( isset( $locale_map[ $normalized_locale ] ) ) {
			return $locale_map[ $normalized_locale ];
		}

		if ( in_array( $normalized_locale, $supported_locales, true ) ) {
			return $normalized_locale;
		}

		$primary_language = strtok( $normalized_locale, '-' );

		if ( isset( $locale_map[ $primary_language ] ) ) {
			return $locale_map[ $primary_language ];
		}

		if ( in_array( $primary_language, $supported_locales, true ) ) {
			return $primary_language;
		}

		return 'en';
	}

	private static function get_intl_tel_input_supported_locales() {
		return array(
			'ar',
			'bg',
			'bn',
			'bs',
			'ca',
			'cs',
			'da',
			'de',
			'el',
			'en',
			'es',
			'et',
			'fa',
			'fi',
			'fil',
			'fr',
			'he',
			'hi',
			'hr',
			'hu',
			'hy',
			'id',
			'is',
			'it',
			'ja',
			'kn',
			'ko',
			'lt',
			'lv',
			'mk',
			'mr',
			'ms',
			'nl',
			'no',
			'pl',
			'pt',
			'ro',
			'ru',
			'sk',
			'sl',
			'sq',
			'sr',
			'sv',
			'sw',
			'ta',
			'te',
			'th',
			'tr',
			'uk',
			'ur',
			'uz',
			'vi',
			'zh',
			'zh-hk',
		);
	}

	public function yoaa_overrite_template( $template, $template_name, $template_path ) {
		$plugin_path = plugin_dir_path( __FILE__ ) . '../../templates/woocommerce/';
	
		$plugin_template = $plugin_path . $template_name;
	
		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}
		
		return $template;
	}

	public function login_error_message($error) {
		// Replace the incorrect password error message
		if (strpos($error, __('The password you entered for the username', 'wc-advanced-accounts')) !== false) {
			$error = str_replace(__('username', 'wc-advanced-accounts'), __('phone number', 'wc-advanced-accounts'), $error);
		}
	
		// Replace the 'username not registered' error message
		if (strpos($error, __('The username', 'wc-advanced-accounts')) !== false && strpos($error, __('is not registered on this site', 'wc-advanced-accounts')) !== false) {
			$error = str_replace(__('The username', 'wc-advanced-accounts'), __('The phone number', 'wc-advanced-accounts'), $error);
			$error = str_replace(__('If you are unsure of your username, try your email address instead.', 'wc-advanced-accounts'), __('If you are unsure of your phone number, try your email address instead.', 'wc-advanced-accounts'), $error);
		}
	
		// Replace unknown email address message
		if (strpos($error, __('Unknown email address', 'wc-advanced-accounts')) !== false && strpos($error, __('try your username', 'wc-advanced-accounts')) !== false) {
			$error = str_replace(__('try your username', 'wc-advanced-accounts'), __('try your phone number', 'wc-advanced-accounts'), $error);
		}
	
		return $error;
	}

	public function override_registration_email_exists_error($message) {
		// Check if the error message corresponds to the "email exists" error
		if (strpos(wp_strip_all_tags($message), __('An account is already registered with', 'wc-advanced-accounts')) !== false) {
			// Replace the message with your custom message
			$message = __('An account is already registered with this phone number or email address. Please log in or use a different one.', 'wc-advanced-accounts');
		}
		return $message;
	}
	
	public function lost_password_message($message) {
		return __('Lost your password? Please enter your phone number or email address. You will receive a link to create a new password via email.', 'wc-advanced-accounts');
	}

	public function add_username_holder_input() { 
		?>
		<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide yoaa-username">
			<label for="username_holder"><?php esc_html_e( 'Phone number or email address', 'wc-advanced-accounts' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'wc-advanced-accounts' ); ?></span></label>
			<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" id="username_holder" name="username_holder" value required aria-required="true" />
			<input type="hidden" id="username_holder_dial_code" name="username_holder_dial_code" value="" />
		</p>
		<?php
	}

	public function add_reg_username_holder_input() { 
		?>
		<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide yoaa-reg-username">
			<label for="reg_username_holder">
			<label for="reg_username_holder"><?php esc_html_e( 'Phone number', 'wc-advanced-accounts' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'wc-advanced-accounts' ); ?></span></label>
			</label>
			<input type="tel" class="woocommerce-Input woocommerce-Input--text input-text" id="reg_username_holder" name="reg_username_holder" value required aria-required="true" />
			<input type="hidden" id="reg_username_holder_dial_code" name="reg_username_holder_dial_code" value="" />
		</p>
		<?php
	}

	public function add_hidden_country_fields() {
		$allowed_countries_option = get_option('woocommerce_allowed_countries', 'all');
		$specific_countries = get_option('woocommerce_specific_allowed_countries', []);
		$skip_country_code = ($allowed_countries_option === 'specific' && count($specific_countries) === 1);
		
		if ($skip_country_code) {
			return;
		}

		?>
		<input type="hidden" id="billing_dial_code" name="billing_dial_code" value="" />
		<input type="hidden" id="shipping_dial_code" name="shipping_dial_code" value="" />
		<?php
	}

	public function maybe_add_country_code_to_phone( $order, $data ) {
		// Verify WooCommerce checkout nonce before using extra POST fields.
		$wc_nonce = isset( $_POST['woocommerce-process-checkout-nonce'] )
			? sanitize_text_field( wp_unslash( $_POST['woocommerce-process-checkout-nonce'] ) )
			: '';

		if ( empty( $wc_nonce ) || ! wp_verify_nonce( $wc_nonce, 'woocommerce-process_checkout' ) ) {
			return;
		}

		// Process billing phone
		$billing_phone = isset( $data['billing_phone'] ) ? (string) $data['billing_phone'] : '';
		if ( '' !== $billing_phone && false === strpos( $billing_phone, '+' ) ) {
			$billing_phone = preg_replace( '/[^0-9]/', '', $billing_phone );

			$billing_dial_code = isset( $_POST['billing_dial_code'] )
				? sanitize_text_field( wp_unslash( $_POST['billing_dial_code'] ) )
				: '';

			if ( '' !== $billing_dial_code ) {
				$billing_phone = ltrim( $billing_phone, '0' );
				$order->set_billing_phone( $billing_dial_code . $billing_phone );
			}
		}

		// Process shipping phone
		$shipping_phone = isset( $data['shipping_phone'] ) ? (string) $data['shipping_phone'] : '';
		if ( '' !== $shipping_phone ) {
			$shipping_phone = preg_replace( '/[^0-9]/', '', $shipping_phone );

			if ( false === strpos( $shipping_phone, '+' ) ) {
				$shipping_dial_code = isset( $_POST['shipping_dial_code'] )
					? sanitize_text_field( wp_unslash( $_POST['shipping_dial_code'] ) )
					: '';

				if ( '' !== $shipping_dial_code ) {
					$shipping_phone = ltrim( $shipping_phone, '0' );
					$order->set_shipping_phone( $shipping_dial_code . $shipping_phone );
				}
			} else {
				$order->set_shipping_phone( $shipping_phone );
			}
		}
	}
}

new YOAA_WC_Advanced_Accounts_Phone_Account_Username();
