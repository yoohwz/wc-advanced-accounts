<?php

if (!defined('ABSPATH')) {
	exit;
}

class YOAA_WC_Advanced_Accounts_Register_Phone_Verification {

	const OTP_MIN_LENGTH  = 6;
	const OTP_MAX_LENGTH  = 8;
	const OTP_TTL         = 300;
	const MAX_ATTEMPTS    = 5;
	const RESEND_COOLDOWN = 120;

	public function __construct() {
		if (get_option('yoaa_wc_enable_phone_verification') === 'yes') {
			add_action( 'init', [ $this, 'maybe_init_wc_session' ], 5 );
			add_filter('wp_authenticate_user', [$this, 'check_phone_verification_status'], 10, 1);

			add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
			add_action('woocommerce_register_form', [$this, 'add_verification_field']);
			// AJAX action for sending the verification code
			add_action('wp_ajax_send_phone_verification_code', [$this, 'ajax_send_verification_code']);
			add_action('wp_ajax_nopriv_send_phone_verification_code', [$this, 'ajax_send_verification_code']);
			add_action('wp_ajax_yoaa_verify_phone_code', [$this, 'ajax_yoaa_verify_phone_code']);
			add_action('wp_ajax_nopriv_yoaa_verify_phone_code', [$this, 'ajax_yoaa_verify_phone_code']);

			add_action('woocommerce_register_post', [$this, 'validate_phone_verification'], 10, 3);
			add_action('wp_ajax_check_username_exists', [$this, 'check_username_exists']);
			add_action('wp_ajax_nopriv_check_username_exists', [$this, 'check_username_exists']);

			add_action('woocommerce_created_customer', [$this, 'handle_new_customer_phone_verification']);
			add_action('woocommerce_thankyou', [$this, 'set_logout_session_for_new_user'], 10, 1);
			add_action('wp', [$this, 'force_logout_after_leaving_thankyou']);
		}
	}
	
		public function maybe_init_wc_session() {
			if ( function_exists( 'WC' ) && class_exists('WC_Session_Handler') && is_null( WC()->session ) ) {
				// create and initialize the handler
				WC()->session = new WC_Session_Handler();
				WC()->session->init();
			}
		}

		private function clear_phone_verification_session( $keep_limits = false ) {
			if ( ! function_exists( 'WC' ) || ! WC()->session ) {
				return;
			}

			WC()->session->__unset( 'phone_verification_code' );
			WC()->session->__unset( 'phone_number_to_verify' );
			WC()->session->__unset( 'phone_verification_expires_at' );
			WC()->session->__unset( 'phone_verification_attempts' );
			WC()->session->__unset( 'wc_phone_verified' );

			if ( ! $keep_limits ) {
				WC()->session->__unset( 'phone_verification_last_sent_at' );
				WC()->session->__unset( 'phone_verification_resend_attempts' );
			}
		}

	public function check_phone_verification_status($user) {
		if (is_wp_error($user)) {
			return $user;
		}

		if ( in_array( 'administrator', (array) $user->roles, true ) ) {
			return $user;
		}
	
		$user_id = $user->ID;
		$phone_verification = get_user_meta($user_id, 'phone_verification', true);
	
			if (!$phone_verification) {
				$error_message = __( 'Your account is not verified yet. Please log in with OTP to verify it or contact support for assistance.', 'wc-advanced-accounts' );
					
				return new WP_Error('phone_not_activated', $error_message);
			}
	
		return $user;
	}

	public function enqueue_scripts() {
		if (is_account_page() || is_checkout() || is_order_received_page()) {
			wp_enqueue_script(
				'phone-verification',
				plugin_dir_url(__FILE__) . '../../../js/phone-verification.js',
				['jquery'],
				'1.1.2',
				true
			);
	
			// Get the countdown value from the option
				$resend_countdown = max( 60, absint( get_option( 'yoaa_wc_phone_verification_resend', self::RESEND_COOLDOWN ) ) );
	
			wp_localize_script('phone-verification', 'wc_advanced_accounts_verification_params', [
				'ajax_url' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('send_phone_code_nonce'),
				'error_message' => __('Please enter your phone number.', 'wc-advanced-accounts'),
				'ajax_error' => __('An error occurred while sending the verification code. Please try again.', 'wc-advanced-accounts'),
				'success_message' => __('Phone number verified successfully!', 'wc-advanced-accounts'),
				'verification_failed' => __('Verification failed. Please check the code and try again.', 'wc-advanced-accounts'),
				'resend_code' => __('Resend', 'wc-advanced-accounts'),
				'resend_limit_reached' => __('Limit reached', 'wc-advanced-accounts'),
				'resend_attempts_reached' => __('You have reached the maximum number of resend attempts.', 'wc-advanced-accounts'),
				'new_code_sent' => __('A new verification code has been sent.', 'wc-advanced-accounts'),
				'enter_code' => __('Please enter the verification code.', 'wc-advanced-accounts'),
				'resend_countdown' => $resend_countdown // Pass the countdown value to JavaScript
			]);
		}
	}
	 
	// AJAX handler for sending the verification code
		public function ajax_send_verification_code() {
			// Verify the nonce
			check_ajax_referer('send_phone_code_nonce', 'security');

			if ( ! function_exists( 'WC' ) || ! WC()->session ) {
				wp_send_json_error( __( 'Session is not available. Please refresh the page and try again.', 'wc-advanced-accounts' ) );
			}

			if ( empty( $_POST['phone_number'] ) ) {
				wp_send_json_error(__('Phone number is required.', 'wc-advanced-accounts'));
			}

			$phone_number = sanitize_text_field(wp_unslash($_POST['phone_number']));

			if ( '' === $phone_number ) {
				wp_send_json_error(__('Phone number is required.', 'wc-advanced-accounts'));
			}

			// Check if the phone number already exists as a username
			if (username_exists($phone_number)) {
				wp_send_json_error(__('This phone number is already registered. Please use a different one.', 'wc-advanced-accounts'));
			}

			$resend_cooldown = max( 60, absint( get_option( 'yoaa_wc_phone_verification_resend', self::RESEND_COOLDOWN ) ) );
			$last_sent_at    = (int) WC()->session->get( 'phone_verification_last_sent_at', 0 );

			if ( $last_sent_at && ( time() - $last_sent_at ) < $resend_cooldown ) {
				$remaining = $resend_cooldown - ( time() - $last_sent_at );
				wp_send_json_error(
					sprintf(
						/* translators: %d: remaining seconds. */
						__( 'Please wait %d seconds before requesting another verification code.', 'wc-advanced-accounts' ),
						$remaining
					)
				);
			}

			$max_resend_attempts = max( 1, absint( get_option( 'yoaa_wc_phone_verification_resend_time', 3 ) ) );
			$resend_attempts     = (int) WC()->session->get( 'phone_verification_resend_attempts', 0 );

			if ( $resend_attempts >= $max_resend_attempts ) {
				wp_send_json_error(__('You have reached the maximum number of resend attempts.', 'wc-advanced-accounts'));
			}

			// Generate the verification code
			$code_length = get_option('yoaa_wc_phone_verification_code_length', self::OTP_MIN_LENGTH);
			$verification_code = (string) $this->generate_verification_code($code_length);

			// Store the code in a session variable
			WC()->session->set_customer_session_cookie( true );
			WC()->session->set('phone_verification_code', $verification_code);
			WC()->session->set('phone_number_to_verify', $phone_number);
			WC()->session->set('phone_verification_expires_at', time() + self::OTP_TTL);
			WC()->session->set('phone_verification_attempts', 0);
			WC()->session->set('phone_verification_last_sent_at', time());
			WC()->session->set('phone_verification_resend_attempts', $resend_attempts + 1);
			WC()->session->__unset( 'wc_phone_verified' );

			// Send the SMS
			$result = $this->send_sms($phone_number, $verification_code);

			if ( is_wp_error( $result ) ) {
				$this->clear_phone_verification_session( true );
				wp_send_json_error( $result->get_error_message() );
			}

			// Return a success response
			wp_send_json_success(__('Verification code sent successfully.', 'wc-advanced-accounts'));
		}

		// Function to generate a random verification code
		public function generate_verification_code($length) {
			$length = max(self::OTP_MIN_LENGTH, min(self::OTP_MAX_LENGTH, (int)$length));
			$min = pow(10, $length - 1);
			$max = pow(10, $length) - 1;
			return wp_rand($min, $max);
	}

	// Function to send the SMS
	public function send_sms($phone_number, $verification_code) {
		$sms_key = get_option('yoohw_phone_verification_sms_key', '');
		$message_template = get_option('yoaa_wc_phone_verification_message', '');
	
		// Generate the message content
			$message = str_replace(
				['{site_name}', '{code}'],
				[get_bloginfo('name'), $verification_code],
				$message_template
			);

		// Extract the country code and phone number
		if (strpos($phone_number, '-') !== false) {
			list($country_code, $local_number) = explode('-', $phone_number, 2);

			// Remove any non-numeric characters
			$country_code = preg_replace('/\D/', '', $country_code);
			$local_number = preg_replace('/\D/', '', $local_number);

			// Reformat the phone number as +{country_code}{local_number}
			$phone_number = '+' . $country_code . $local_number;
		} else {
			// No '-' in the phone number, fetch the default country code
			$allowed_countries = get_option('woocommerce_specific_allowed_countries', '');

			if (!empty($allowed_countries)) {
				// Parse the serialized data into an array
				$allowed_countries = maybe_unserialize($allowed_countries);

				// Get the first country in the list
				$default_country = is_array($allowed_countries) && !empty($allowed_countries) ? reset($allowed_countries) : '';

				if (!empty($default_country)) {
					// Load the country codes from the configuration file
					$phone_country_codes_file = plugin_dir_path(__FILE__) . 'data/phone_country_codes.conf';
					$phone_country_codes = [];
					if (file_exists($phone_country_codes_file)) {
						$lines = file($phone_country_codes_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
						foreach ($lines as $line) {
							list($country, $code) = explode(':', $line);
							$phone_country_codes[trim($country)] = trim($code);
						}
					}

					// Get the phone country code for the default country
					$country_code = isset($phone_country_codes[$default_country]) ? $phone_country_codes[$default_country] : '';

					if (!empty($country_code)) {
						// Remove any non-numeric characters from the phone number
						$local_number = preg_replace('/\D/', '', $phone_number);

						// Remove leading '0' from the local number
						$local_number = ltrim($local_number, '0');

						// Reformat the phone number as +{country_code}{local_number}
						$phone_number = '+' . $country_code . $local_number;
					}
				}
			}
		}

			// Prepare the data for the API request
			$data = [
				'sms_key' => $sms_key,
			'domain'  => home_url(),
			'phone'   => $phone_number,
			'message' => $message,
		];
	
		// Send the request to the API
			$response = wp_remote_post('https://bmc.yoohw.com/wp-json/sms/v1/send-sms/', [
				'body'    => wp_json_encode($data),
				'headers' => ['Content-Type' => 'application/json'],
				'timeout' => 15,
			]);

			if ( is_wp_error( $response ) ) {
				return new WP_Error( 'yoaa_sms_failed', __( 'Failed to send OTP via SMS. Please try again.', 'wc-advanced-accounts' ) );
			}

			$response_code = (int) wp_remote_retrieve_response_code( $response );
			if ( $response_code < 200 || $response_code >= 300 ) {
				return new WP_Error( 'yoaa_sms_failed', __( 'Failed to send OTP via SMS. Please try again.', 'wc-advanced-accounts' ) );
			}

			return true;
		}
	
	// Add verification code field to the registration form
	public function add_verification_field() {
		// Get the resend countdown time and maximum resend attempts from options
		$resend_countdown = get_option('yoaa_wc_phone_verification_resend', 60);
		$max_resend_attempts = get_option('yoaa_wc_phone_verification_resend_time', 3);
		?>
		<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide" id="phone-verification-row">
			<label for="phone_verification_code"><?php esc_html_e('Verification code', 'wc-advanced-accounts'); ?> <span class="required">*</span></label>
			<input type="text" class="input-text" name="phone_verification_code" id="phone_verification_code" placeholder="<?php esc_html_e('Code', 'wc-advanced-accounts'); ?>" />
			<button type="button" class="woocommerce-Button woocommerce-button button" id="send-phone-code"><?php esc_html_e('Send code', 'wc-advanced-accounts'); ?></button>
			<button type="button" class="woocommerce-Button woocommerce-button button" id="verify-phone-code" style="display: none;"><?php esc_html_e('Verify', 'wc-advanced-accounts'); ?></button>
			<button type="button" class="woocommerce-Button woocommerce-button button" id="resend-phone-code" style="display: none;" disabled><?php esc_html_e('Resend', 'wc-advanced-accounts'); ?> (<span id="resend-timer"><?php echo esc_html($resend_countdown); ?></span>s)</button>
			<input type="hidden" name="phone_verified" id="phone_verified" value="0" />
			<input type="hidden" name="validate_phone_verification_nonce" value="<?php echo esc_attr(wp_create_nonce('validate_phone_verification_action')); ?>" />
			<input type="hidden" id="max_resend_attempts" value="<?php echo esc_attr($max_resend_attempts); ?>" />
			<div id="phone-verification-notice" class="woocommerce-info" style="display: none;"></div>
		</p>
		<?php
	}
	
	// AJAX handler to verify the phone code
		public function ajax_yoaa_verify_phone_code() {
			check_ajax_referer( 'send_phone_code_nonce', 'security' );

			if ( ! function_exists( 'WC' ) || ! WC()->session ) {
				wp_send_json_error( __( 'Session is not available. Please refresh the page and try again.', 'wc-advanced-accounts' ) );
			}

			if ( empty( $_POST['verification_code'] ) || empty( $_POST['phone_number'] ) ) {
				wp_send_json_error( __( 'Verification code is required.', 'wc-advanced-accounts' ) );
			}

		$entered_code = sanitize_text_field( wp_unslash( $_POST['verification_code'] ) );
		$phone_number = sanitize_text_field( wp_unslash( $_POST['phone_number'] ) );

			$stored_code = (string) WC()->session->get( 'phone_verification_code' );
			$stored_phone = (string) WC()->session->get( 'phone_number_to_verify' );
			$expires_at = (int) WC()->session->get( 'phone_verification_expires_at', 0 );
			$attempts = (int) WC()->session->get( 'phone_verification_attempts', 0 );

			if ( '' === $stored_code || '' === $stored_phone ) {
				wp_send_json_error( __( 'Please request a new verification code.', 'wc-advanced-accounts' ) );
			}

			// Optional: ensure the submitted phone matches the session phone (prevents tampering).
			if ( '' !== $stored_phone && $phone_number !== $stored_phone ) {
				wp_send_json_error( __( 'Verification failed. Please try again.', 'wc-advanced-accounts' ) );
			}

			if ( ! $expires_at || time() > $expires_at ) {
				$this->clear_phone_verification_session();
				wp_send_json_error( __( 'This verification code has expired. Please request a new one.', 'wc-advanced-accounts' ) );
			}

			if ( $attempts >= self::MAX_ATTEMPTS ) {
				$this->clear_phone_verification_session();
				wp_send_json_error( __( 'Too many failed attempts. Please request a new verification code.', 'wc-advanced-accounts' ) );
			}

			// Check if the entered code matches the stored code.
			if ( ! preg_match( '/^\d{6,8}$/', $entered_code ) || ! hash_equals( $stored_code, $entered_code ) ) {
				$attempts++;
				WC()->session->set( 'phone_verification_attempts', $attempts );

				if ( $attempts >= self::MAX_ATTEMPTS ) {
					$this->clear_phone_verification_session();
					wp_send_json_error( __( 'Too many failed attempts. Please request a new verification code.', 'wc-advanced-accounts' ) );
				}

				wp_send_json_error( __( 'Verification failed. Please check the code and try again.', 'wc-advanced-accounts' ) );
			}

			// Verification successful.
			WC()->session->__unset( 'phone_verification_code' );
			WC()->session->__unset( 'phone_number_to_verify' );
			WC()->session->__unset( 'phone_verification_expires_at' );
			WC()->session->__unset( 'phone_verification_attempts' );
			WC()->session->__unset( 'phone_verification_last_sent_at' );
			WC()->session->__unset( 'phone_verification_resend_attempts' );
			WC()->session->set( 'wc_phone_verified', true );

		// Normalize phone to E.164-like format.
		if ( strpos( $phone_number, '-' ) !== false ) {
			list( $country_code, $local_number ) = explode( '-', $phone_number, 2 );

			$country_code = preg_replace( '/\D+/', '', (string) $country_code );
			$local_number = preg_replace( '/\D+/', '', (string) $local_number );

			$phone_number = '+' . $country_code . $local_number;
		} else {
			$allowed_countries = get_option( 'woocommerce_specific_allowed_countries', '' );
			if ( ! empty( $allowed_countries ) ) {
				$allowed_countries = maybe_unserialize( $allowed_countries );
				$default_country   = ( is_array( $allowed_countries ) && ! empty( $allowed_countries ) ) ? (string) reset( $allowed_countries ) : '';

				if ( '' !== $default_country ) {
					$phone_country_codes_file = plugin_dir_path( __FILE__ ) . 'data/phone_country_codes.conf';
					$phone_country_codes      = array();

					if ( file_exists( $phone_country_codes_file ) ) {
						$lines = file( $phone_country_codes_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
						foreach ( (array) $lines as $line ) {
							$parts = explode( ':', $line, 2 );
							if ( 2 !== count( $parts ) ) {
								continue;
							}
							$phone_country_codes[ trim( $parts[0] ) ] = trim( $parts[1] );
						}
					}

					$country_code = isset( $phone_country_codes[ $default_country ] ) ? $phone_country_codes[ $default_country ] : '';
					if ( '' !== $country_code ) {
						$local_number = preg_replace( '/\D+/', '', (string) $phone_number );
						$local_number = ltrim( $local_number, '0' );
						$phone_number = '+' . preg_replace( '/\D+/', '', (string) $country_code ) . $local_number;
					}
				}
			}
		}

		// Add logic for wc-blacklist-manager plugin.
		if (
			( function_exists( 'is_plugin_active' ) || require_once ABSPATH . 'wp-admin/includes/plugin.php' ) &&
			is_plugin_active( 'wc-blacklist-manager/wc-blacklist-manager.php' )
		) {
			global $wpdb;

			$table_name = $wpdb->prefix . 'wc_whitelist';

			// Cache the existence check to avoid repeated direct DB reads.
			$cache_group  = 'yoaa_wcaa';
			$cache_key    = 'wl_phone_exists_' . md5( $table_name . '|' . $phone_number );
			$phone_exists = wp_cache_get( $cache_key, $cache_group );

			if ( false === $phone_exists ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Needed for 3rd-party table existence check; cached via wp_cache_*.
				$phone_exists = $wpdb->get_var(
					$wpdb->prepare(
						// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Prefix/table is controlled by WP; value is prepared.
						"SELECT phone FROM {$wpdb->prefix}wc_whitelist WHERE phone = %s",
						$phone_number
					)
				);

				// Cache for 10 minutes.
				wp_cache_set( $cache_key, $phone_exists, $cache_group, 10 * MINUTE_IN_SECONDS );
			}

			if ( $phone_exists ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Required to update a custom table row.
				$wpdb->update(
					$table_name,
					array(
						'verified_phone' => 1,
					),
					array(
						'phone' => $phone_number,
					),
					array(
						'%d',
					),
					array(
						'%s',
					)
				);
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Required to insert into a custom table.
				$wpdb->insert(
					$table_name,
					array(
						'phone'          => $phone_number,
						'verified_phone' => 1,
					),
					array(
						'%s',
						'%d',
					)
				);

				// Invalidate cache since we inserted.
				wp_cache_delete( $cache_key, $cache_group );
			}
		}

		wp_send_json_success( __( 'Phone number verified successfully!', 'wc-advanced-accounts' ) );
	}

	public function validate_phone_verification($username, $email, $validation_errors) {
		if (!class_exists('WooCommerce') || (is_account_page())) {
			if (!isset($_POST['validate_phone_verification_nonce']) || 
				!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['validate_phone_verification_nonce'])), 'validate_phone_verification_action')) {
				$validation_errors->add('nonce_verification_failed', __('Nonce verification failed. Please try again.', 'wc-advanced-accounts'));
				return;
			}

				$phone_verified = isset($_POST['phone_verified']) ? sanitize_text_field( wp_unslash( $_POST['phone_verified'] ) ) : '';
				$session_verified = function_exists( 'WC' ) && WC()->session && WC()->session->get( 'wc_phone_verified' );

				if ( '1' !== $phone_verified || ! $session_verified ) {
					$validation_errors->add('phone_not_verified', __('You must verify your phone number before registering.', 'wc-advanced-accounts'));
				}
		}
	}

	public function check_username_exists() {
		// Nonce: validate + unslash + sanitize.
		if ( empty( $_POST['security'] ) ) {
			wp_send_json_error( __( 'Invalid nonce.', 'wc-advanced-accounts' ) );
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['security'] ) );
		if ( ! wp_verify_nonce( $nonce, 'wc_advanced_accounts_nonce' ) ) {
			wp_send_json_error( __( 'Invalid nonce.', 'wc-advanced-accounts' ) );
		}

		// Username: validate index exists + unslash + sanitize.
		if ( empty( $_POST['username'] ) ) {
			wp_send_json_error( __( 'Invalid username.', 'wc-advanced-accounts' ) );
		}

		$username = sanitize_text_field( wp_unslash( $_POST['username'] ) );

		// Check if the username exists.
		if ( username_exists( $username ) ) {
			wp_send_json_error(
				__( 'This phone number is already registered. Please use a different one.', 'wc-advanced-accounts' )
			);
		}

		wp_send_json_success();
	}

	public function handle_new_customer_phone_verification( $customer_id ) {
		if ( WC()->session->get( 'wc_phone_verified' ) ) {
			update_user_meta( $customer_id, 'phone_verification', 1 );
			WC()->session->__unset( 'wc_phone_verified' );
			return;
		}

		// Only trust checkout POST if WooCommerce checkout nonce is valid.
		$wc_nonce = isset( $_POST['woocommerce-process-checkout-nonce'] )
			? sanitize_text_field( wp_unslash( $_POST['woocommerce-process-checkout-nonce'] ) )
			: '';

		if ( empty( $wc_nonce ) || ! wp_verify_nonce( $wc_nonce, 'woocommerce-process_checkout' ) ) {
			return;
		}

		$createaccount = isset( $_POST['createaccount'] )
			? sanitize_text_field( wp_unslash( $_POST['createaccount'] ) )
			: '';

		if ( '1' === $createaccount ) {
			update_user_meta( $customer_id, 'wc_create_account_during_checkout', true );
		}
	}

    public function set_logout_session_for_new_user($order_id) {
		if (is_user_logged_in()) {
			$order = wc_get_order($order_id);
	
			if ($order) {
				$user_id = $order->get_user_id();
				if ( get_user_meta( $user_id, 'email_verification', true ) 
					|| get_user_meta( $user_id, 'phone_verification', true ) ) {
					return;
				}
	
				// Check if user_id exists AND if "createaccount" was set at checkout
				if ($user_id && get_user_meta($user_id, 'wc_create_account_during_checkout', true)) {
					WC()->session->set('new_user_logout', true);
					
					delete_user_meta($user_id, 'wc_create_account_during_checkout');
				}
			}
		}
	}

	public function force_logout_after_leaving_thankyou() {
		if (
			! is_user_logged_in() ||
			! function_exists( 'WC' ) ||
			! WC()->session ||
			! method_exists( WC()->session, 'get' )
		) {
			return;
		}

		if ( ! WC()->session->get( 'new_user_logout' ) ) {
			return;
		}

		$user_id = get_current_user_id();
		if ( get_user_meta( $user_id, 'email_verification', true ) 
			|| get_user_meta( $user_id, 'phone_verification', true ) ) {
			return;
		}

		$endpoint = get_option( 'woocommerce_checkout_order_received_endpoint', 'order-received' );

		if ( is_wc_endpoint_url( $endpoint ) ) {
			return;
		}

		WC()->session->__unset( 'new_user_logout' );
		wp_logout();
		wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
		exit;
	}
}

new YOAA_WC_Advanced_Accounts_Register_Phone_Verification();
