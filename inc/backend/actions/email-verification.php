<?php

if (!defined('ABSPATH')) {
	exit;
}

class YOAA_WC_Advanced_Accounts_Register_Email_Verification {

	public function __construct() {
		if (get_option('yoaa_wc_enable_email_verification') === 'yes') {
			add_action('woocommerce_created_customer', [$this, 'handle_new_customer_email_verification']);
			add_action('woocommerce_registration_redirect', [$this, 'registration_redirect']);
			add_filter('wp_authenticate_user', [$this, 'check_email_verification_status'], 10, 1);
			add_action('init', [$this, 'handle_email_confirmation']);
			add_action('init', [$this, 'handle_resend_confirmation_email']);
			add_action('woocommerce_before_reset_password_form', [$this, 'show_account_activated_notice']);
			add_action('woocommerce_customer_reset_password', [$this, 'auto_login_after_reset_password'], 10, 1);
			add_action('password_reset', [$this, 'update_email_verification_after_password_reset'], 10, 2);
			add_filter('woocommerce_registration_errors', [$this, 'remove_password_field_requirement'], 10, 3);
			add_action('wp_enqueue_scripts', [$this, 'add_inline_registration_password_script']);

			add_filter('woocommerce_checkout_fields', [$this, 'make_password_field_optional']);
			add_action('wp_enqueue_scripts', [$this, 'remove_create_account_section']);
			add_action('woocommerce_thankyou', [$this, 'set_logout_session_for_new_user'], 10, 1);
			add_action('wp', [$this, 'force_logout_after_leaving_thankyou']);
		}
	}

	public function handle_new_customer_email_verification( $customer_id ) {
		// If they were already verified in-session, mark them verified.
		if ( WC()->session->get( 'wc_email_verified' ) ) {
			update_user_meta( $customer_id, 'email_verification', 1 );
			WC()->session->__unset( 'wc_email_verified' );
			return;
		}

		// Otherwise send your confirmation email and record checkout flag.
		add_filter( 'woocommerce_email_enabled_customer_new_account', '__return_false' );
		add_user_meta( $customer_id, 'email_verification', '0', true );

		$this->send_email_confirmation( $customer_id );

		// Only trust checkout POST if nonce is valid.
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

	// Function to check email activation status during login
	public function check_email_verification_status( $user ) {
		if ( is_wp_error( $user ) ) {
			return $user;
		}

		if ( in_array( 'administrator', (array) $user->roles, true ) ) {
			return $user;
		}

		$user_id            = absint( $user->ID );
		$email_verification = get_user_meta( $user_id, 'email_verification', true );

		if ( '0' === $email_verification ) {

			$myaccount_url = wc_get_page_permalink( 'myaccount' );
			if ( ! $myaccount_url ) {
				// fallback if wc_get_page_permalink() fails
				$myaccount_page_id = get_option( 'woocommerce_myaccount_page_id' );
				$myaccount_url     = $myaccount_page_id
					? get_permalink( $myaccount_page_id )
					: home_url();
			}

			$resend_link = add_query_arg(
				[
					'action'   => 'resend_confirmation_email',
					'user'     => $user_id,
					'_wpnonce' => wp_create_nonce( 'resend_confirmation_action_' . $user_id ),
				],
				$myaccount_url
			);

			$error_message = sprintf(
				/* translators: %s: URL to resend the confirmation email */
				__( 'Please confirm your registration to activate your account. Check your email for a verification link before logging in.<br>Didn\'t receive the email? <a href="%s"><strong>Click here to resend verification email</strong></a>.', 'wc-advanced-accounts' ),
				esc_url( $resend_link )
			);

			// 4) Store it for display (for 30 seconds) and return a WP_Error
			set_transient( 'email_verification_error', wp_kses_post( $error_message ), 30 );

			return new WP_Error( 'email_not_activated', wp_kses_post( $error_message ) );
		}

		return $user;
	}

	// Function to handle registration redirect
	public function registration_redirect($user_id) {
		// Check if the 'yoaa-registration-success' page exists
		$page = get_page_by_path('yoaa-registration-success');

		// If the page does not exist, create it
		if (!$page) {
			$page_content = __(
				'Thank you for registering! Please check your email inbox and click the confirmation link to activate your account.',
				'wc-advanced-accounts'
			);

			$page_id = wp_insert_post([
				'post_title'   => 'Registration Successful',
				'post_name'    => 'yoaa-registration-success',
				'post_content' => $page_content,
				'post_status'  => 'publish',
				'post_type'    => 'page',
			]);

			// Set the page ID to redirect
			$page = get_post($page_id);
		}

		// Log out the user
		wp_logout();

		// Redirect to the 'yoaa-registration-success' page
		return get_permalink($page->ID);
	}

	// Function to send email confirmation link
	public function send_email_confirmation( $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		$email = $user->user_email;
		$activation_key = sha1( $user_id . time() );

		update_user_meta( $user_id, 'email_verification_key', $activation_key );

		$myaccount_url = wc_get_page_permalink( 'myaccount' );
		if ( ! $myaccount_url ) {
			$page_id       = get_option( 'woocommerce_myaccount_page_id' );
			$myaccount_url = $page_id ? get_permalink( $page_id ) : home_url();
		}

		$confirmation_link = add_query_arg(
			[
				'action'  => 'confirm_email',
				'key'     => $activation_key,
				'user'    => $user_id,
				'_wpnonce'=> wp_create_nonce( 'confirm_email_action_' . $user_id ),
			],
			$myaccount_url
		);

		$subject = __( 'Confirm your registration', 'wc-advanced-accounts' );
		$message = $this->get_email_confirmation_message( $confirmation_link );
		$headers = [ 'Content-Type: text/html; charset=UTF-8' ];

		wp_mail( $email, $subject, $message, $headers );
	}

	public function handle_resend_confirmation_email() {
		if (
			isset( $_GET['action'], $_GET['user'], $_GET['_wpnonce'] ) &&
			'resend_confirmation_email' === $_GET['action']
		) {
			$user_id = intval( $_GET['user'] );
			$nonce   = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );

			if ( ! wp_verify_nonce( $nonce, 'resend_confirmation_action_' . $user_id ) ) {
				wp_die( esc_html__( 'Invalid request.', 'wc-advanced-accounts' ), 403 );
			}

			$user = get_userdata( $user_id );
			if ( ! $user ) {
				wp_die( esc_html__( 'User not found.', 'wc-advanced-accounts' ), 403 );
			}

			$email_verification = get_user_meta( $user_id, 'email_verification', true );
			if ( '0' !== $email_verification ) {
				wp_die( esc_html__( 'Your email is already verified.', 'wc-advanced-accounts' ), 403 );
			}

			$this->send_email_confirmation( $user_id );

			$myaccount_url = wc_get_page_permalink( 'myaccount' );
			if ( ! $myaccount_url ) {
				$page_id       = get_option( 'woocommerce_myaccount_page_id' );
				$myaccount_url = $page_id ? get_permalink( $page_id ) : home_url();
			}

			wp_safe_redirect( add_query_arg( 'resend_success', '1', $myaccount_url ) );
			exit;
		}
	}

	// Function to get email confirmation message using WooCommerce email template
	private function get_email_confirmation_message($confirmation_link) {
		$mailer = WC()->mailer();

		$subject = __('Confirm your registration', 'wc-advanced-accounts');
		$heading = __('Confirm your email address', 'wc-advanced-accounts');

		$message = sprintf(
			/* translators: %s: the email verification link */
			__(
				'Hi there,<br><br>We need to confirm your email address to complete your registration.<br><br>Please click the link below to confirm your email:<br><br><a href="%s">Confirm registration</a><br><br>If you did not request this, please ignore this email.<br><br>Thank you.',
				'wc-advanced-accounts'
			),
			esc_url($confirmation_link),
		);

		$wrapped_message = $mailer->wrap_message($heading, $message);

		$email = new WC_Email();

		$styled_message = $email->style_inline($wrapped_message);

		return $styled_message;
	}

	// Function to handle email confirmation link
	public function handle_email_confirmation() {
		if (
			isset( $_GET['action'], $_GET['key'], $_GET['user'], $_GET['_wpnonce'] ) &&
			'confirm_email' === $_GET['action']
		) {
			$user_id         = (int) $_GET['user'];
			$activation_key  = sanitize_text_field( wp_unslash( $_GET['key'] ) );
			$nonce           = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );

			if ( ! wp_verify_nonce( $nonce, 'confirm_email_action_' . $user_id ) ) {
				wp_die( esc_html__( 'Invalid email confirmation link.', 'wc-advanced-accounts' ), 403 );
			}

			$stored_key = get_user_meta( $user_id, 'email_verification_key', true );

			if ( $activation_key === $stored_key ) {
				update_user_meta( $user_id, 'email_verification', '1' );
				delete_user_meta( $user_id, 'email_verification_key' );

				set_transient(
					'account_activated_success',
					__( 'Your account has been activated, please set your password.', 'wc-advanced-accounts' ),
					30
				);

				$user = get_userdata( $user_id );
				if ( ! $user ) {
					wp_die( esc_html__( 'Invalid user.', 'wc-advanced-accounts' ), 403 );
				}

				$reset_key = get_password_reset_key( $user );

				$reset_url = add_query_arg(
					array(
						'action' => 'rp',
						'key'    => $reset_key,
						'login'  => rawurlencode( $user->user_login ),
					),
					wc_get_page_permalink( 'myaccount' )
				);

				wp_safe_redirect( esc_url_raw( $reset_url ) );
				exit;
			}

			wp_die( esc_html__( 'Invalid or expired email confirmation link.', 'wc-advanced-accounts' ), 403 );
		}
	}

	// Function to show success notice on the password reset page
	public function show_account_activated_notice() {
		if ($notice = get_transient('yoaa_account_activated_success')) {
			wc_print_notice($notice, 'success');

			delete_transient('yoaa_account_activated_success');
		}
	}

	// Function to auto login the user after password reset
	public function auto_login_after_reset_password($user) {
		wc_set_customer_auth_cookie($user->ID);
		wp_safe_redirect(wc_get_page_permalink('myaccount'));
	
		exit;
	}

	public function update_email_verification_after_password_reset( $user, $new_password ) {
		// Mark user as verified after successful password reset.
		update_user_meta( $user->ID, 'email_verification', '1' );

		// Sync to Blacklist Manager whitelist if active.
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( is_plugin_active( 'wc-blacklist-manager/wc-blacklist-manager.php' ) ) {
			global $wpdb;

			$user_email = (string) $user->user_email;
			$table_name = $wpdb->prefix . 'wc_whitelist';

			// Cache group + key (store only boolean flag, not PII).
			$cache_group = 'yoaa_wc_whitelist';
			$cache_key   = 'exists:email:' . md5( $user_email );

			$email_exists = wp_cache_get( $cache_key, $cache_group );

			if ( false === $email_exists ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$email_exists = $wpdb->get_var(
					$wpdb->prepare(
						// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Prefix is safe, value is prepared.
						"SELECT email FROM {$wpdb->prefix}wc_whitelist WHERE email = %s LIMIT 1",
						$user_email
					)
				);

				// Cache as 1/0 only.
				wp_cache_set( $cache_key, ( $email_exists ? 1 : 0 ), $cache_group, MINUTE_IN_SECONDS * 10 );
				$email_exists = ( $email_exists ? 1 : 0 );
			} else {
				$email_exists = (int) $email_exists;
			}

			if ( $email_exists ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->update(
					$table_name,
					array( 'verified_email' => 1 ),
					array( 'email' => $user_email ),
					array( '%d' ),
					array( '%s' )
				);
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->insert(
					$table_name,
					array(
						'email'          => $user_email,
						'verified_email' => 1,
					),
					array( '%s', '%d' )
				);
			}

			// Invalidate cache after write.
			wp_cache_delete( $cache_key, $cache_group );
		}

		// Send new account email.
		$this->send_new_account_email( $user );
	}

	public function send_new_account_email($user) {
		$mailer = WC()->mailer();
		$email = $user->user_email;
		$user_login = $user->user_login;

		if ( class_exists( 'YOAA_Phone_Username_Helper' ) ) {
			$phone = YOAA_Phone_Username_Helper::get_user_sms_phone( $user, $user_login );
		} elseif ( preg_match( '/^\d+-\d+$/', $user_login ) ) {
			$phone = '+' . str_replace( '-', '', $user_login );
		} else {
			$phone = $user_login;
		}

		$user_pass = __('(password set during activation)', 'wc-advanced-accounts');
	
		$subject = __('Welcome to our store!', 'wc-advanced-accounts');
		$heading = __('Your account is now active', 'wc-advanced-accounts');

		$enable_phone_number_account = get_option('yoaa_wc_enable_phone_number_account', 'no');
	
		if ($enable_phone_number_account === 'yes') {
			$message = sprintf(
				/* translators: 1: Display name, 2: Phone number, 3: Password */
				__('Hi %1$s,<br><br>Your account has been successfully activated. You can now log in using your email address or phone number.<br><br>Phone number: %2$s<br>Password: %3$s<br><br>Thank you for registering!', 'wc-advanced-accounts'),
				esc_html($user->display_name),
				esc_html($phone),
				esc_html($user_pass)
			);
		} else {
			$message = sprintf(
				/* translators: 1: Display name, 2: Username, 3: Password */
				__('Hi %1$s,<br><br>Your account has been successfully activated. You can now log in using your email address or username.<br><br>Username: %2$s<br>Password: %3$s<br><br>Thank you for registering!', 'wc-advanced-accounts'),
				esc_html($user->display_name),
				esc_html($user_login),
				esc_html($user_pass)
			);
		}		
	
		$wrapped_message = $mailer->wrap_message($heading, $message);
		$email_content = (new WC_Email())->style_inline($wrapped_message);
	
		$mailer->send($email, $subject, $email_content, ['Content-Type: text/html; charset=UTF-8']);
	}

	public function remove_password_field_requirement( $errors, $username, $email ) {
		// Only act on real form POST submissions.
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_key( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
		if ( 'post' !== strtolower( $method ) ) {
			return $errors;
		}

		// Nonce verification (recommended when reading form input).
		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
		if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'woocommerce-register' ) ) {
			// Do not hard-fail here; just don't modify errors.
			// (WooCommerce may already handle nonce validation upstream.)
			return $errors;
		}

		$password = isset( $_POST['password'] ) ? sanitize_text_field( wp_unslash( $_POST['password'] ) ) : '';
		$password = trim( $password );

		// If password is empty, remove WooCommerce's "password required" error.
		if ( '' === $password && is_wp_error( $errors ) ) {
			unset( $errors->errors['registration-error-password'] );
			// Optional: also remove its message array if present.
			unset( $errors->error_data['registration-error-password'] );
		}

		return $errors;
	}

	public function add_inline_registration_password_script() {
		if (is_account_page()) {
			wp_enqueue_script('jquery');
			$inline_script = "
				document.addEventListener('DOMContentLoaded', function() {
					var passwordField = document.getElementById('reg_password');
					if (passwordField) {
						var passwordRow = passwordField.closest('.woocommerce-form-row');
						if (passwordRow) {
							// Hide the password field row
							passwordRow.style.display = 'none';
						}
						// Remove the 'required' attribute from the password field
						passwordField.removeAttribute('required');
					}
				});
			";
			wp_add_inline_script('jquery', $inline_script);
		}
	}

	public function make_password_field_optional($fields) {
		if (isset($fields['account']['account_password'])) {
			$fields['account']['account_password']['required'] = false; // Make it optional
			$fields['account']['account_password']['type'] = 'hidden'; // Hide it
		}
	
		return $fields;
	}
	
	public function remove_create_account_section() {
		if ( is_checkout() && ! is_user_logged_in() ) {

			$inline_css = '
				#account_password_field label,
				.create-account .clear {
					display: none;
				}
			';

			// Attach to an existing registered style to avoid fake handles
			wp_add_inline_style( 'woocommerce-inline', $inline_css );

			// Conditionally run JavaScript if "Generate username from email" is enabled
			if ( 'yes' === get_option( 'woocommerce_registration_generate_username' ) ) {
				wp_enqueue_script( 'jquery' );
				wp_add_inline_script(
					'jquery',
					"jQuery(function($){
						$('.woocommerce-account-fields .create-account')
							.not('p.create-account')
							.remove();
					});"
				);
			}
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
					
					// Optionally, remove the meta to prevent repeated logouts
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

// Initialize the class
new YOAA_WC_Advanced_Accounts_Register_Email_Verification();

// Add nonce to WooCommerce registration form
add_action('woocommerce_register_form', function () {
    wp_nonce_field('woocommerce-register', '_wpnonce');
});

// Add nonce to WooCommerce create an account section at checkout
add_action('woocommerce_after_checkout_registration_form', function () {
    wp_nonce_field('woocommerce-register', '_wpnonce');
});
