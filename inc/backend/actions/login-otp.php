<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YOAA_WC_Advanced_Accounts_Phone_Login_OTP {

	const OTP_LENGTH       = 6;
	const OTP_TTL          = 300; // 5 minutes.
	const MAX_ATTEMPTS     = 5;
	const RESEND_COOLDOWN  = 120;

	public function __construct() {
		if ( get_option( 'yoaa_wc_enable_phone_login_with_otp' ) === 'yes' ) {
			add_action( 'init', [ $this, 'maybe_init_wc_session' ], 5 );

			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
			add_action( 'wp_ajax_send_login_otp', [ $this, 'ajax_send_login_otp' ] );
			add_action( 'wp_ajax_nopriv_send_login_otp', [ $this, 'ajax_send_login_otp' ] );
			add_action( 'wp_ajax_verify_login_otp', [ $this, 'ajax_verify_login_otp' ] );
			add_action( 'wp_ajax_nopriv_verify_login_otp', [ $this, 'ajax_verify_login_otp' ] );
		}
	}

	public function maybe_init_wc_session() {
		if ( class_exists( 'WC_Session_Handler' ) && function_exists( 'WC' ) && is_null( WC()->session ) ) {
			WC()->session = new WC_Session_Handler();
			WC()->session->init();
		}
	}

	private function clear_login_otp_session(): void {
		if ( function_exists( 'WC' ) && WC()->session ) {
			WC()->session->__unset( 'login_otp_code' );
			WC()->session->__unset( 'login_otp_identifier' );
			WC()->session->__unset( 'login_otp_expires_at' );
			WC()->session->__unset( 'login_otp_attempts' );
			WC()->session->__unset( 'login_otp_last_sent_at' );
		}
	}

	public function enqueue_scripts() {
		wp_enqueue_script(
			'phone-login-otp',
			plugin_dir_url( __FILE__ ) . '../../../js/phone-login-otp.js',
			[ 'jquery' ],
			YOAA_WC_ADVANCED_ACCOUNTS_VERSION,
			true
		);

		$resend_time                 = max( 60, absint( get_option( 'yoaa_wc_phone_verification_resend', self::RESEND_COOLDOWN ) ) );
		$resend_limit                = max( 1, absint( get_option( 'yoaa_wc_phone_verification_resend_time', 3 ) ) );
		$enable_phone_number_account = get_option( 'yoaa_wc_enable_phone_number_account', 'no' ) === 'yes';

		$error_message = $enable_phone_number_account
			? __( 'Please enter your phone number or email.', 'wc-advanced-accounts' )
			: __( 'Please enter your email address.', 'wc-advanced-accounts' );

		$invalid_phone_number = $enable_phone_number_account
			? __( 'Please enter a valid phone or email.', 'wc-advanced-accounts' )
			: __( 'Please enter a valid email address.', 'wc-advanced-accounts' );

		wp_localize_script(
			'phone-login-otp',
			'wc_otp_login_params',
			[
				'ajax_url'                => admin_url( 'admin-ajax.php' ),
				'nonce'                   => wp_create_nonce( 'send_login_otp_nonce' ),
				'otp_button_text'         => __( 'Login with OTP', 'wc-advanced-accounts' ),
				'enter_otp_field'         => __( 'Code', 'wc-advanced-accounts' ),
				'verify_button_text'      => __( 'Verify', 'wc-advanced-accounts' ),
				'resend_button_text'      => __( 'Resend', 'wc-advanced-accounts' ),
				// translators: %s: Remaining seconds before the user can resend an OTP.
				'resend_button_countdown' => __( 'Resend (%s)', 'wc-advanced-accounts' ),
				'resending_text'          => __( 'Resending...', 'wc-advanced-accounts' ),
				'resend_time'             => $resend_time,
				'resend_limit'            => $resend_limit,
				'error_message'           => $error_message,
				'invalid_phone_number'    => $invalid_phone_number,
				'otp_sent'                => __( 'OTP sent successfully to your phone number.', 'wc-advanced-accounts' ),
				'otp_resent'              => __( 'OTP resent successfully.', 'wc-advanced-accounts' ),
				'otp_error'               => __( 'An error occurred while sending the OTP. Please try again.', 'wc-advanced-accounts' ),
				'otp_verification_error'  => __( 'The OTP you entered is incorrect. Please try again.', 'wc-advanced-accounts' ),
				'resend_limit_reached'    => __( 'Limit reached', 'wc-advanced-accounts' ),
			]
		);
	}

	public function ajax_send_login_otp() {
		check_ajax_referer( 'send_login_otp_nonce', 'security' );

		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			wp_send_json_error( __( 'Session is not available. Please refresh the page and try again.', 'wc-advanced-accounts' ) );
		}

		$identifier = isset( $_POST['identifier'] ) ? sanitize_text_field( wp_unslash( $_POST['identifier'] ) ) : '';
		$submitted_identifier = $identifier;

		if ( empty( $identifier ) ) {
			wp_send_json_error( __( 'Phone number or email address is required.', 'wc-advanced-accounts' ) );
		}

		$is_email = is_email( $identifier );

		if ( $is_email ) {
			$user = get_user_by( 'email', $identifier );

			if ( ! $user ) {
				wp_send_json_error( __( 'No account found with this email address.', 'wc-advanced-accounts' ) );
			}
		} else {
			$user = class_exists( 'YOAA_Phone_Username_Helper' )
				? YOAA_Phone_Username_Helper::find_user_by_identifier( $identifier )
				: get_user_by( 'login', $identifier );

			if ( ! $user ) {
				wp_send_json_error( __( 'No account found with this phone number.', 'wc-advanced-accounts' ) );
			}

			$identifier = $user->user_login;
		}

		WC()->session->set_customer_session_cookie( true );

		$last_sent_at = (int) WC()->session->get( 'login_otp_last_sent_at', 0 );

		if ( $last_sent_at && ( time() - $last_sent_at ) < max( 60, absint( get_option( 'yoaa_wc_phone_verification_resend', self::RESEND_COOLDOWN ) ) ) ) {
			$cooldown  = max( 60, absint( get_option( 'yoaa_wc_phone_verification_resend', self::RESEND_COOLDOWN ) ) );
			$remaining = $cooldown - ( time() - $last_sent_at );

			wp_send_json_error(
				sprintf(
					/* translators: %d: remaining seconds. */
					__( 'Please wait %d seconds before requesting another OTP.', 'wc-advanced-accounts' ),
					$remaining
				)
			);
		}

		$otp_code = (string) wp_rand( 100000, 999999 );

		WC()->session->set( 'login_otp_code', $otp_code );
		WC()->session->set( 'login_otp_identifier', $identifier );
		WC()->session->set( 'login_otp_expires_at', time() + self::OTP_TTL );
		WC()->session->set( 'login_otp_attempts', 0 );
		WC()->session->set( 'login_otp_last_sent_at', time() );

		if ( $is_email ) {
			$mailer  = WC()->mailer();
			$subject = __( 'Your login OTP code', 'wc-advanced-accounts' );
			$heading = __( 'Login OTP code', 'wc-advanced-accounts' );
			$message = sprintf(
				/* translators: %s: OTP code. */
				__( 'Your OTP code for login is: <strong>%s</strong><br><br>If you did not request this, please ignore this email.<br><br>Thank you.', 'wc-advanced-accounts' ),
				esc_html( $otp_code )
			);

			$wrapped_message = $mailer->wrap_message( $heading, $message );
			$email           = new WC_Email();
			$styled_message  = $email->style_inline( $wrapped_message );

			$sent = wp_mail(
				$identifier,
				$subject,
				$styled_message,
				[ 'Content-Type: text/html; charset=UTF-8' ]
			);

			if ( ! $sent ) {
				$this->clear_login_otp_session();
				wp_send_json_error( __( 'Failed to send OTP to the email address. Please try again.', 'wc-advanced-accounts' ) );
			}

			wp_send_json_success( __( 'OTP sent successfully. Please check your email.', 'wc-advanced-accounts' ) );
		}

		$phone_number = class_exists( 'YOAA_Phone_Username_Helper' )
			? YOAA_Phone_Username_Helper::get_user_sms_phone( $user, $submitted_identifier )
			: $submitted_identifier;

		$sms_key          = get_option( 'yoohw_phone_verification_sms_key', '' );
		$message_template = get_option( 'yoaa_wc_phone_verification_message', '' );

		$message = str_replace(
			[ '{site_name}', '{code}' ],
			[ get_bloginfo( 'name' ), $otp_code ],
			$message_template
		);

		$data = [
			'sms_key' => $sms_key,
			'domain'  => home_url(),
			'phone'   => $phone_number,
			'message' => $message,
		];

		$response = wp_remote_post(
			'https://bmc.yoohw.com/wp-json/sms/v1/send-sms/',
			[
				'body'    => wp_json_encode( $data ),
				'headers' => [ 'Content-Type' => 'application/json' ],
				'timeout' => 15,
			]
		);

		if ( is_wp_error( $response ) ) {
			$this->clear_login_otp_session();
			wp_send_json_error( __( 'Failed to send OTP via SMS. Please try again.', 'wc-advanced-accounts' ) );
		}

		$response_code = (int) wp_remote_retrieve_response_code( $response );

		if ( $response_code < 200 || $response_code >= 300 ) {
			$this->clear_login_otp_session();
			wp_send_json_error( __( 'Failed to send OTP via SMS. Please try again.', 'wc-advanced-accounts' ) );
		}

		wp_send_json_success( __( 'OTP sent successfully. Please check your phone.', 'wc-advanced-accounts' ) );
	}

	public function ajax_verify_login_otp() {
		check_ajax_referer( 'send_login_otp_nonce', 'security' );

		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			wp_send_json_error( __( 'Session is not available. Please refresh the page and try again.', 'wc-advanced-accounts' ) );
		}

		$otp_code = isset( $_POST['otp_code'] )
			? sanitize_text_field( wp_unslash( $_POST['otp_code'] ) )
			: '';

		$stored_otp   = (string) WC()->session->get( 'login_otp_code' );
		$identifier   = (string) WC()->session->get( 'login_otp_identifier' );
		$expires_at   = (int) WC()->session->get( 'login_otp_expires_at', 0 );
		$attempts     = (int) WC()->session->get( 'login_otp_attempts', 0 );

		if ( '' === $stored_otp || '' === $identifier ) {
			wp_send_json_error( __( 'Please request a new OTP.', 'wc-advanced-accounts' ) );
		}

		if ( ! $expires_at || time() > $expires_at ) {
			$this->clear_login_otp_session();
			wp_send_json_error( __( 'This OTP has expired. Please request a new one.', 'wc-advanced-accounts' ) );
		}

		if ( $attempts >= self::MAX_ATTEMPTS ) {
			$this->clear_login_otp_session();
			wp_send_json_error( __( 'Too many failed attempts. Please request a new OTP.', 'wc-advanced-accounts' ) );
		}

		if ( ! preg_match( '/^\d{6}$/', $otp_code ) || ! hash_equals( $stored_otp, $otp_code ) ) {
			$attempts++;
			WC()->session->set( 'login_otp_attempts', $attempts );

			if ( $attempts >= self::MAX_ATTEMPTS ) {
				$this->clear_login_otp_session();
				wp_send_json_error( __( 'Too many failed attempts. Please request a new OTP.', 'wc-advanced-accounts' ) );
			}

			wp_send_json_error( __( 'The OTP you entered is incorrect. Please try again.', 'wc-advanced-accounts' ) );
		}

		$lookup_value    = '';
		$lookup_type     = '';
		$meta_key        = '';

		if ( is_email( $identifier ) ) {
			$user         = get_user_by( 'email', $identifier );
			$meta_key     = 'email_verification';
			$lookup_type  = 'email';
			$lookup_value = $identifier;
		} else {
			$user        = class_exists( 'YOAA_Phone_Username_Helper' )
				? YOAA_Phone_Username_Helper::find_user_by_identifier( $identifier )
				: get_user_by( 'login', $identifier );
			$meta_key    = 'phone_verification';
			$lookup_type = 'phone';
			$lookup_value = '';
		}

		if ( ! $user ) {
			$this->clear_login_otp_session();
			wp_send_json_error( __( 'User not found.', 'wc-advanced-accounts' ) );
		}

		$user_id = (int) $user->ID;

		if ( ! is_email( $identifier ) ) {
			$lookup_value = class_exists( 'YOAA_Phone_Username_Helper' )
				? YOAA_Phone_Username_Helper::get_user_sms_phone( $user, $identifier )
				: preg_replace( '/\D+/', '', $identifier );
		}

		if ( '1' !== get_user_meta( $user_id, $meta_key, true ) ) {
			update_user_meta( $user_id, $meta_key, '1' );
		}

		delete_user_meta( $user_id, 'email_verification_key' );

		if (
			( function_exists( 'is_plugin_active' ) || require_once ABSPATH . 'wp-admin/includes/plugin.php' ) &&
			is_plugin_active( 'wc-blacklist-manager/wc-blacklist-manager.php' )
		) {
			global $wpdb;

			$table       = $wpdb->prefix . 'wc_whitelist';
			$cache_group = 'yoaa_wc_whitelist';
			$cache_key   = 'exists:' . $lookup_type . ':' . md5( (string) $lookup_value );

			$exists = wp_cache_get( $cache_key, $cache_group );

				if ( false === $exists ) {
					if ( 'email' === $lookup_type ) {
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Blacklist Manager stores verified contacts in a custom integration table.
						$exists = $wpdb->get_var(
							$wpdb->prepare(
								"SELECT email FROM {$wpdb->prefix}wc_whitelist WHERE email = %s LIMIT 1",
							$lookup_value
							)
						);
					} else {
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Blacklist Manager stores verified contacts in a custom integration table.
						$exists = $wpdb->get_var(
							$wpdb->prepare(
								"SELECT phone FROM {$wpdb->prefix}wc_whitelist WHERE phone = %s LIMIT 1",
							$lookup_value
						)
					);
				}

				wp_cache_set( $cache_key, ( $exists ? 1 : 0 ), $cache_group, MINUTE_IN_SECONDS * 10 );
				$exists = ( $exists ? 1 : 0 );
			} else {
				$exists = (int) $exists;
			}

				if ( 'email' === $lookup_type ) {
					if ( $exists ) {
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Blacklist Manager stores verified contacts in a custom integration table.
						$wpdb->update(
							$table,
							[ 'verified_email' => 1 ],
						[ 'email' => $lookup_value ],
						[ '%d' ],
							[ '%s' ]
						);
					} else {
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Blacklist Manager stores verified contacts in a custom integration table.
						$wpdb->insert(
							$table,
							[
							'email'          => $lookup_value,
							'verified_email' => 1,
						],
						[ '%s', '%d' ]
					);
					}
				} else {
					if ( $exists ) {
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Blacklist Manager stores verified contacts in a custom integration table.
						$wpdb->update(
							$table,
							[ 'verified_phone' => 1 ],
						[ 'phone' => $lookup_value ],
						[ '%d' ],
							[ '%s' ]
						);
					} else {
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Blacklist Manager stores verified contacts in a custom integration table.
						$wpdb->insert(
							$table,
							[
							'phone'          => $lookup_value,
							'verified_phone' => 1,
						],
						[ '%s', '%d' ]
					);
				}
			}

			wp_cache_delete( $cache_key, $cache_group );
		}

		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true );

		$this->clear_login_otp_session();

		wp_send_json_success( __( 'You have been logged in successfully!', 'wc-advanced-accounts' ) );
	}
}

new YOAA_WC_Advanced_Accounts_Phone_Login_OTP();
