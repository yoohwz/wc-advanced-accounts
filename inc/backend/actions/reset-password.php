<?php

if (!defined('ABSPATH')) {
	exit;
}

class YOAA_WC_Advanced_Accounts_Reset_Password_OTP {

	const OTP_LENGTH      = 6;
	const OTP_TTL         = 300;
	const MAX_ATTEMPTS    = 5;
	const RESEND_COOLDOWN = 120;

    public function __construct() {
		if (get_option('yoaa_wc_enable_phone_login_with_otp') === 'yes' && get_option('yoaa_wc_enable_phone_number_account') === 'yes') {
			add_action( 'init', [ $this, 'maybe_init_wc_session' ], 5 );
			add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
			add_action('woocommerce_lostpassword_form', [$this, 'add_otp_fields']);
			add_filter('woocommerce_lost_password_message', [$this, 'lost_password_message']);
			
			add_action('wp_ajax_send_reset_otp', [$this, 'ajax_send_reset_otp']);
			add_action('wp_ajax_nopriv_send_reset_otp', [$this, 'ajax_send_reset_otp']);
			add_action('wp_ajax_verify_reset_otp', [$this, 'ajax_verify_reset_otp']);
			add_action('wp_ajax_nopriv_verify_reset_otp', [$this, 'ajax_verify_reset_otp']);

			add_action('wp_head', [ $this, 'inline_hide_reset_fields' ]);
		}
    }

		public function maybe_init_wc_session() {
			if ( function_exists( 'WC' ) && class_exists('WC_Session_Handler') && is_null( WC()->session ) ) {
				// create and initialize the handler
				WC()->session = new WC_Session_Handler();
				WC()->session->init();
			}
		}

		private function clear_reset_otp_session( $keep_limits = false ) {
			if ( ! function_exists( 'WC' ) || ! WC()->session ) {
				return;
			}

			WC()->session->__unset( 'reset_password_otp' );
			WC()->session->__unset( 'reset_password_identifier' );
			WC()->session->__unset( 'reset_password_expires_at' );
			WC()->session->__unset( 'reset_password_attempts' );

			if ( ! $keep_limits ) {
				WC()->session->__unset( 'reset_otp_resend_attempts' );
				WC()->session->__unset( 'reset_otp_last_sent_at' );
			}
		}

    // Enqueue scripts for the lost password form
    public function enqueue_scripts() {
        if (is_account_page()) {
            wp_enqueue_script(
                'yoaa-wc-reset-password-otp',
                plugin_dir_url(__FILE__) . '../../../js/reset-password.js',
                ['jquery'],
                '1.4.3',
                true
            );

				$resend_time  = max( 60, absint( get_option( 'yoaa_wc_phone_verification_resend', self::RESEND_COOLDOWN ) ) );
				$resend_limit = max( 1, absint( get_option( 'yoaa_wc_phone_verification_resend_time', 3 ) ) );

				wp_localize_script('yoaa-wc-reset-password-otp', 'reset_password_otp_params', [
					'ajax_url' => admin_url('admin-ajax.php'),
					'nonce' => wp_create_nonce('reset_password_otp_nonce'),
					'resend_time' => $resend_time,
					'resend_limit' => $resend_limit,
				'resend_button_text' => __('Resend', 'wc-advanced-accounts'),
				'resending_text' => __('Resending...', 'wc-advanced-accounts'),
				'resend_limit_reached' => __('Resend limit reached', 'wc-advanced-accounts'),
				'error_message' => __('Please enter your phone number or email.', 'wc-advanced-accounts'),
				'invalid_identifier' => __('Please enter a valid phone number or email.', 'wc-advanced-accounts'),
				'otp_error_message' => __('Failed to send OTP. Please try again.', 'wc-advanced-accounts'),
				'otp_verification_error' => __('Invalid OTP. Please try again.', 'wc-advanced-accounts'),
				'otp_resend_success' => __('OTP resent successfully.', 'wc-advanced-accounts'),
			]);
        }
    }

    // Add custom fields for OTP to the lost password form
	    public function add_otp_fields() {
			$resend_countdown = max( 60, absint( get_option( 'yoaa_wc_phone_verification_resend', self::RESEND_COOLDOWN ) ) );
			$max_resend_attempts = max( 1, absint( get_option( 'yoaa_wc_phone_verification_resend_time', 3 ) ) );
        ?>
        <p class="woocommerce-form-row woocommerce-form-row--first form-row form-row-first">
			<label for="username_holder"><?php esc_html_e( 'Phone number or email address', 'wc-advanced-accounts' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'wc-advanced-accounts' ); ?></span></label>
			<input type="text" name="username_holder" id="username_holder" class="woocommerce-Input woocommerce-Input--text input-text" />
			<input type="hidden" id="username_holder_dial_code" name="username_holder_dial_code" value="" />
			<div id="reset-password-otp-notice" class="woocommerce-info form-row-first" style="display: none;"></div>
        </p>
		<p class="woocommerce-form-row woocommerce-form-row--first form-row form-row-first">
			<input type="text" name="username" id="username" class="woocommerce-Input woocommerce-Input--text input-text" />
		</p>
		<div class="clear"></div>
        <p class="woocommerce-form-row form-row send-otp">
            <button type="button" id="send-reset-otp" class="woocommerce-button button"><?php esc_html_e('Send code', 'wc-advanced-accounts'); ?></button>
        </p>
        <p class="woocommerce-form-row form-row otp-section" style="display:none;">
            <input type="text" name="reset_otp" id="reset_otp" class="input-text" placeholder="<?php esc_html_e('Code', 'wc-advanced-accounts'); ?>" />
            <button type="button" id="verify-reset-otp" class="woocommerce-button button"><?php esc_html_e('Verify', 'wc-advanced-accounts'); ?></button>
			<button type="button" class="woocommerce-button button" id="resend-reset-otp-code" style="display: none;" disabled><?php esc_html_e('Resend', 'wc-advanced-accounts'); ?> (<span id="resend-timer"><?php echo esc_html($resend_countdown); ?></span>s)</button>
        </p>
        <?php
    }

    // AJAX handler to send the reset OTP
		public function ajax_send_reset_otp() {
			check_ajax_referer('reset_password_otp_nonce', 'security');

			if ( ! function_exists( 'WC' ) || ! WC()->session ) {
				wp_send_json_error(__('Session is not available. Please refresh the page and try again.', 'wc-advanced-accounts'));
			}
		
			$identifier = isset($_POST['identifier']) ? sanitize_text_field(wp_unslash($_POST['identifier'])) : '';

		if (empty($identifier)) {
			wp_send_json_error(__('Phone number or email address is required.', 'wc-advanced-accounts'));
		}
	
		// Check if the identifier is an email
		$is_email = filter_var($identifier, FILTER_VALIDATE_EMAIL);

		if ($is_email) {
			$user = get_user_by('email', $identifier);
			if (!$user) {
				wp_send_json_error(__('No account found with this email address.', 'wc-advanced-accounts'));
			}
		} else {
			$user = get_user_by('login', $identifier);
			if (!$user) {
				wp_send_json_error(__('No account found with this phone number.', 'wc-advanced-accounts'));
			}
		}
	
			$resend_cooldown = max( 60, absint( get_option( 'yoaa_wc_phone_verification_resend', self::RESEND_COOLDOWN ) ) );
			$last_sent_at    = (int) WC()->session->get( 'reset_otp_last_sent_at', 0 );

			if ( $last_sent_at && ( time() - $last_sent_at ) < $resend_cooldown ) {
				$remaining = $resend_cooldown - ( time() - $last_sent_at );
				wp_send_json_error(
					sprintf(
						/* translators: %d: remaining seconds. */
						__( 'Please wait %d seconds before requesting another OTP.', 'wc-advanced-accounts' ),
						$remaining
					)
				);
			}

			$max_resend_attempts = max( 1, absint( get_option( 'yoaa_wc_phone_verification_resend_time', 3 ) ) );
			$resend_attempts     = (int) WC()->session->get('reset_otp_resend_attempts', 0);
			
			if ($resend_attempts >= $max_resend_attempts) {
				wp_send_json_error(__('You have reached the maximum resend attempts. Please try again later.', 'wc-advanced-accounts'));
		}
	
		// Increment resend attempts
		$resend_attempts++;
		WC()->session->set_customer_session_cookie( true );
		WC()->session->set('reset_otp_resend_attempts', $resend_attempts);
	
			$otp_code = (string) wp_rand( 100000, 999999 );

			// Store OTP and identifier in the session
			WC()->session->set('reset_password_otp', $otp_code);
			WC()->session->set('reset_password_identifier', $identifier);
			WC()->session->set('reset_password_expires_at', time() + self::OTP_TTL);
			WC()->session->set('reset_password_attempts', 0);
			WC()->session->set('reset_otp_last_sent_at', time());
		
			// Send OTP
		if ($is_email) {
			$mailer = WC()->mailer();
			$subject = __('Your password reset OTP code', 'wc-advanced-accounts');
			$heading = __('Reset password by OTP', 'wc-advanced-accounts');
			$message = sprintf(
				/* translators: %s: OTP code */
				__('Your OTP code for resetting your password is: <strong>%s</strong><br><br>If you did not request this, please ignore this email.<br><br>Thank you.', 'wc-advanced-accounts'),
				esc_html($otp_code)
			);
	
			// Wrap and style the message
			$wrapped_message = $mailer->wrap_message($heading, $message);
			$email = new WC_Email();
			$styled_message = $email->style_inline($wrapped_message);
	
				// Send the email
				$sent = wp_mail($identifier, $subject, $styled_message, ['Content-Type: text/html; charset=UTF-8']);
				if (!$sent) {
					$this->clear_reset_otp_session( true );
					wp_send_json_error(__('Failed to send OTP to the email address. Please try again.', 'wc-advanced-accounts'));
				}
	
			wp_send_json_success(__('OTP sent successfully. Please check your email.', 'wc-advanced-accounts'));
		} else {
			$phone_number = $identifier;
		
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

			// Send the OTP via SMS
			$sms_key = get_option('yoohw_phone_verification_sms_key', '');
			$message_template = get_option('yoaa_wc_phone_verification_message', '');
		
			// Generate the message content
			$message = str_replace(
				['{site_name}', '{code}'],
				[get_bloginfo('name'), $otp_code],
				$message_template
			);
		
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
			
				// Check for errors in the API response
				if (is_wp_error($response)) {
					$this->clear_reset_otp_session( true );
					wp_send_json_error(__('Failed to send OTP via SMS. Please try again.', 'wc-advanced-accounts'));
				}

				$response_code = (int) wp_remote_retrieve_response_code( $response );
				if ( $response_code < 200 || $response_code >= 300 ) {
					$this->clear_reset_otp_session( true );
					wp_send_json_error(__('Failed to send OTP via SMS. Please try again.', 'wc-advanced-accounts'));
				}

			wp_send_json_success(__('OTP sent successfully. Please check your phone.', 'wc-advanced-accounts'));
		}	
	}
	
    // AJAX handler to verify the reset OTP
		public function ajax_verify_reset_otp() {
			check_ajax_referer('reset_password_otp_nonce', 'security');

			if ( ! function_exists( 'WC' ) || ! WC()->session ) {
				wp_send_json_error(__('Session is not available. Please refresh the page and try again.', 'wc-advanced-accounts'));
			}
		
			$otp_code = isset($_POST['otp_code']) ? sanitize_text_field(wp_unslash($_POST['otp_code'])) : '';
			$stored_otp = (string) WC()->session->get('reset_password_otp');
			$identifier = (string) WC()->session->get('reset_password_identifier');
			$expires_at = (int) WC()->session->get('reset_password_expires_at', 0);
			$attempts   = (int) WC()->session->get('reset_password_attempts', 0);

			if ( '' === $stored_otp || '' === $identifier ) {
				wp_send_json_error(__('Please request a new OTP.', 'wc-advanced-accounts'));
			}

			if ( ! $expires_at || time() > $expires_at ) {
				$this->clear_reset_otp_session();
				wp_send_json_error(__('This OTP has expired. Please request a new one.', 'wc-advanced-accounts'));
			}

			if ( $attempts >= self::MAX_ATTEMPTS ) {
				$this->clear_reset_otp_session();
				wp_send_json_error(__('Too many failed attempts. Please request a new OTP.', 'wc-advanced-accounts'));
			}
		
			// Normalize values and compare
			if ( ! preg_match( '/^\d{6}$/', $otp_code ) || ! hash_equals( $stored_otp, $otp_code ) ) {
				$attempts++;
				WC()->session->set( 'reset_password_attempts', $attempts );

				if ( $attempts >= self::MAX_ATTEMPTS ) {
					$this->clear_reset_otp_session();
					wp_send_json_error(__('Too many failed attempts. Please request a new OTP.', 'wc-advanced-accounts'));
				}

				wp_send_json_error(__('Invalid OTP. Please try again.', 'wc-advanced-accounts'));
			}
		
			$user = filter_var($identifier, FILTER_VALIDATE_EMAIL)
				? get_user_by('email', $identifier)
				: get_user_by('login', $identifier);
		
			if (!$user) {
				$this->clear_reset_otp_session();
				wp_send_json_error(__('User not found.', 'wc-advanced-accounts'));
			}
		
			$reset_key = get_password_reset_key($user);

			if ( is_wp_error( $reset_key ) ) {
				$this->clear_reset_otp_session();
				wp_send_json_error( $reset_key->get_error_message() );
			}
		
			// Clear OTP session and reset resend attempts
			$this->clear_reset_otp_session();
		
			$reset_url = add_query_arg(
				array(
					'key'   => $reset_key,
					'login' => $user->user_login,
				),
				wc_get_account_endpoint_url( 'lost-password' )
			);
			wp_send_json_success(['redirect_url' => $reset_url]);
		}

	public function lost_password_message($message) {
		return __('Forgot your password? Enter your email address or phone number to receive a verification code to reset your password.', 'wc-advanced-accounts');
	}

	/**
     * Echo inline <style> to hide the default ResetPassword fields
     * but only on exactly the lost-password endpoint (no ?query).
     */
    public function inline_hide_reset_fields() {
        if ( function_exists('is_wc_endpoint_url')
          && is_wc_endpoint_url('lost-password')
          && empty( $_SERVER['QUERY_STRING'] ) ) {

            echo '<style>
                /* hide the login input paragraph */
                .woocommerce-ResetPassword p:has(label[for="user_login"]),
                /* hide the reset button paragraph */
                .woocommerce-ResetPassword p:has(input[name="wc_reset_password"]) {
                    display: none !important;
                }
            </style>';
        }
    }
}

new YOAA_WC_Advanced_Accounts_Reset_Password_OTP();
