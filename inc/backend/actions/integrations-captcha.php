<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YOAA_WC_Advanced_Accounts_Captcha_Integration {

	const OPTION_PROVIDER             = 'yoaa_wc_captcha_selected';
	const OPTION_RECAPTCHA_SITE_KEY   = 'yoaa_wc_recaptcha_site_key';
	const OPTION_RECAPTCHA_SECRET_KEY = 'yoaa_wc_recaptcha_secret_key';
	const OPTION_RECAPTCHA_THRESHOLD  = 'yoaa_wc_recaptcha_score_threshold';
	const OPTION_RECAPTCHA_V2_SITE    = 'yoaa_wc_recaptcha_v2_site_key';
	const OPTION_RECAPTCHA_V2_SECRET  = 'yoaa_wc_recaptcha_v2_secret_key';
	const OPTION_HCAPTCHA_SITE        = 'yoaa_wc_hcaptcha_site_key';
	const OPTION_HCAPTCHA_SECRET      = 'yoaa_wc_hcaptcha_secret_key';
	const OPTION_CLOUDFLARE_SITE      = 'yoaa_wc_cloudflare_site_key';
	const OPTION_CLOUDFLARE_SECRET    = 'yoaa_wc_cloudflare_secret_key';
	const OPTION_ENABLE_LOGIN         = 'yoaa_wc_enable_captcha_login';
	const OPTION_ENABLE_REGISTER      = 'yoaa_wc_enable_captcha_register';

	public function __construct() {
		$this->register_hooks();
	}

	private function register_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_captcha_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_dequeue_blacklist_manager_scripts_on_woocommerce_account' ), 100 );
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_captcha_scripts' ) );
		add_action( 'login_enqueue_scripts', array( $this, 'maybe_dequeue_blacklist_manager_scripts_on_wp_auth' ), 100 );
		add_action( 'login_enqueue_scripts', array( $this, 'maybe_add_login_css' ) );
		add_action( 'init', array( $this, 'apply_blacklist_manager_overlap_rule' ), PHP_INT_MAX );

		if ( $this->is_login_enabled() ) {
			add_action( 'woocommerce_login_form', array( $this, 'add_captcha_to_woocommerce_login' ), 20 );
			add_filter( 'woocommerce_process_login_errors', array( $this, 'validate_captcha_woocommerce_login' ), 10, 3 );
			add_action( 'login_form', array( $this, 'add_captcha_to_wp_login_form' ) );
			add_filter( 'authenticate', array( $this, 'validate_captcha_wp_login' ), 30, 3 );
		}

		if ( $this->is_register_enabled() ) {
			add_action( 'woocommerce_register_form', array( $this, 'add_captcha_to_woocommerce_register' ), 20 );
			add_filter( 'woocommerce_registration_errors', array( $this, 'validate_captcha_woocommerce_register' ), 10, 3 );
			add_action( 'register_form', array( $this, 'add_captcha_to_wp_register_form' ) );
			add_filter( 'registration_errors', array( $this, 'validate_captcha_wp_register' ), 10, 3 );
		}
	}

	public function apply_blacklist_manager_overlap_rule() {
		if ( ! $this->is_provider_configured( $this->get_selected_provider() ) || ! class_exists( 'WC_Blacklist_Manager_Premium_Checkout_Captcha' ) ) {
			return;
		}

		if (
			$this->is_login_enabled()
			&& '1' === get_option( 'wc_blacklist_manager_enable_captcha_login', '0' )
		) {
			$this->remove_blacklist_manager_callbacks(
				array(
					'woocommerce_login_form'    => array( 'add_captcha_to_woocommerce_login' ),
					'woocommerce_login_process' => array( 'validate_captcha_woocommerce_login' ),
					'login_form'                => array( 'add_captcha_to_wp_login_form' ),
					'authenticate'              => array( 'validate_captcha_wp_login' ),
				)
			);
		}

		if (
			$this->is_register_enabled()
			&& '1' === get_option( 'wc_blacklist_manager_enable_captcha_register', '0' )
		) {
			$this->remove_blacklist_manager_callbacks(
				array(
					'woocommerce_register_form'       => array( 'add_captcha_to_woocommerce_register' ),
					'woocommerce_registration_errors' => array( 'validate_captcha_woocommerce_register' ),
					'register_form'                   => array( 'add_captcha_to_wp_register_form' ),
					'registration_errors'             => array( 'validate_captcha_wp_register' ),
				)
			);
		}
	}

	private function remove_blacklist_manager_callbacks( $hook_map ) {
		foreach ( $hook_map as $hook_name => $methods ) {
			$this->remove_callbacks_by_class_methods(
				$hook_name,
				'WC_Blacklist_Manager_Premium_Checkout_Captcha',
				$methods
			);
		}
	}

	private function remove_callbacks_by_class_methods( $hook_name, $class_name, $methods ) {
		global $wp_filter;

		if ( empty( $wp_filter[ $hook_name ] ) || ! $wp_filter[ $hook_name ] instanceof WP_Hook ) {
			return;
		}

		foreach ( $wp_filter[ $hook_name ]->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$function = isset( $callback['function'] ) ? $callback['function'] : null;

				if ( ! is_array( $function ) || ! isset( $function[0], $function[1] ) ) {
					continue;
				}

				if ( ! is_object( $function[0] ) || ! is_a( $function[0], $class_name ) ) {
					continue;
				}

				if ( ! in_array( $function[1], $methods, true ) ) {
					continue;
				}

				remove_filter( $hook_name, $function, $priority );
			}
		}
	}

	public function enqueue_captcha_scripts() {
		if ( ! $this->is_any_form_enabled() ) {
			return;
		}

		$provider = $this->get_selected_provider();

		if ( 'recaptcha_v3' === $provider ) {
			$site_key = get_option( self::OPTION_RECAPTCHA_SITE_KEY, '' );
			if ( '' !== $site_key ) {
				wp_enqueue_script(
					'yoaa-google-recaptcha-v3',
					'https://www.google.com/recaptcha/api.js?render=' . rawurlencode( $site_key ),
					array(),
					null,
					true
				);
			}
		} elseif ( 'recaptcha_v2' === $provider ) {
			$site_key = get_option( self::OPTION_RECAPTCHA_V2_SITE, '' );
			if ( '' !== $site_key ) {
				wp_enqueue_script(
					'yoaa-google-recaptcha-v2',
					'https://www.google.com/recaptcha/api.js',
					array(),
					null,
					true
				);
			}
		} elseif ( 'hcaptcha' === $provider ) {
			$site_key = get_option( self::OPTION_HCAPTCHA_SITE, '' );
			if ( '' !== $site_key ) {
				wp_enqueue_script(
					'yoaa-hcaptcha',
					'https://js.hcaptcha.com/1/api.js',
					array(),
					null,
					true
				);
			}
		} elseif ( 'cloudflare' === $provider ) {
			$site_key = get_option( self::OPTION_CLOUDFLARE_SITE, '' );
			if ( '' !== $site_key ) {
				wp_enqueue_script(
					'yoaa-cf-turnstile',
					'https://challenges.cloudflare.com/turnstile/v0/api.js',
					array(),
					null,
					true
				);
			}
		}
	}

	public function maybe_dequeue_blacklist_manager_scripts_on_wp_auth() {
		$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : 'login';

		if ( 'register' === $action ) {
			if ( $this->is_register_enabled() ) {
				$this->dequeue_blacklist_manager_provider_scripts();
			}

			return;
		}

		if ( in_array( $action, array( 'lostpassword', 'retrievepassword', 'rp', 'resetpass' ), true ) ) {
			return;
		}

		if ( $this->is_login_enabled() ) {
			$this->dequeue_blacklist_manager_provider_scripts();
		}
	}

	public function maybe_dequeue_blacklist_manager_scripts_on_woocommerce_account() {
		if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || is_user_logged_in() ) {
			return;
		}

		if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'lost-password' ) ) {
			return;
		}

		$registration_enabled = 'yes' === get_option( 'woocommerce_enable_myaccount_registration', 'no' );
		$advanced_accounts_owns_visible_forms = $registration_enabled
			? ( $this->is_login_enabled() && $this->is_register_enabled() )
			: $this->is_login_enabled();

		if ( $advanced_accounts_owns_visible_forms ) {
			$this->dequeue_blacklist_manager_provider_scripts();
		}
	}

	private function dequeue_blacklist_manager_provider_scripts() {
		if ( ! $this->is_provider_configured( $this->get_selected_provider() ) || ! class_exists( 'WC_Blacklist_Manager_Premium_Checkout_Captcha' ) ) {
			return;
		}

		foreach ( array( 'google-recaptcha-v3', 'google-recaptcha-v2', 'hcaptcha', 'cf-turnstile' ) as $handle ) {
			wp_dequeue_script( $handle );
		}
	}

	public function maybe_add_login_css() {
		$provider = $this->get_selected_provider();

		if ( 'none' !== $provider && 'recaptcha_v3' !== $provider && $this->is_any_form_enabled() ) {
			echo '<style type="text/css">#login { width: 350px !important; }</style>';
		}
	}

	public function add_captcha_to_woocommerce_login() {
		$this->output_captcha_markup( 'login' );
	}

	public function validate_captcha_woocommerce_login( $validation_error, $username, $password ) {
		if ( $validation_error instanceof WP_Error && $validation_error->get_error_code() ) {
			return $validation_error;
		}

		if ( '' === trim( (string) $username ) || '' === (string) $password ) {
			return $validation_error;
		}

		if ( ! $this->validate_current_request( 'login' ) ) {
			$validation_error->add(
				'yoaa_captcha_failed',
				__( 'CAPTCHA verification failed. Please try again.', 'wc-advanced-accounts' )
			);
		}

		return $validation_error;
	}

	public function add_captcha_to_woocommerce_register() {
		$this->output_captcha_markup( 'register' );
	}

	public function validate_captcha_woocommerce_register( $errors, $username, $email ) {
		if ( ! $errors instanceof WP_Error ) {
			$errors = new WP_Error();
		}

		if ( ! $this->validate_current_request( 'register' ) ) {
			$errors->add(
				'yoaa_captcha_failed',
				__( 'CAPTCHA verification failed. Please try again.', 'wc-advanced-accounts' )
			);
		}

		return $errors;
	}

	public function add_captcha_to_wp_login_form() {
		$this->output_captcha_markup( 'login' );
	}

	public function validate_captcha_wp_login( $user, $username, $password ) {
		if ( is_wp_error( $user ) ) {
			return $user;
		}

		if ( ! $this->is_wp_login_post() ) {
			return $user;
		}

		if ( ! $this->validate_current_request( 'login' ) ) {
			return new WP_Error(
				'yoaa_captcha_failed',
				__( 'CAPTCHA verification failed. Please try again.', 'wc-advanced-accounts' )
			);
		}

		return $user;
	}

	public function add_captcha_to_wp_register_form() {
		$this->output_captcha_markup( 'register' );
	}

	public function validate_captcha_wp_register( $errors, $sanitized_user_login, $user_email ) {
		if ( ! $errors instanceof WP_Error ) {
			$errors = new WP_Error();
		}

		if ( ! $this->validate_current_request( 'register' ) ) {
			$errors->add(
				'yoaa_captcha_failed',
				__( 'CAPTCHA verification failed. Please try again.', 'wc-advanced-accounts' )
			);
		}

		return $errors;
	}

	private function output_captcha_markup( $context ) {
		$provider = $this->get_selected_provider();

		if ( 'none' === $provider ) {
			return;
		}

		if ( ! $this->is_provider_configured( $provider ) ) {
			return;
		}

		if ( 'recaptcha_v3' === $provider ) {
			$this->output_recaptcha_v3_markup( $context );
			return;
		}

		if ( 'recaptcha_v2' === $provider ) {
			$site_key = get_option( self::OPTION_RECAPTCHA_V2_SITE, '' );
			echo '<div class="yoaa-captcha-wrapper yoaa-recaptcha-v2" style="margin: 12px 0;">';
			echo '<div class="g-recaptcha" data-sitekey="' . esc_attr( $site_key ) . '"></div>';
			echo '</div>';
			return;
		}

		if ( 'hcaptcha' === $provider ) {
			$site_key = get_option( self::OPTION_HCAPTCHA_SITE, '' );
			echo '<div class="yoaa-captcha-wrapper yoaa-hcaptcha" style="margin: 12px 0;">';
			echo '<div class="h-captcha" data-sitekey="' . esc_attr( $site_key ) . '"></div>';
			echo '</div>';
			return;
		}

		if ( 'cloudflare' === $provider ) {
			$site_key = get_option( self::OPTION_CLOUDFLARE_SITE, '' );
			echo '<div class="yoaa-captcha-wrapper yoaa-cf-turnstile" style="margin: 12px 0;">';
			echo '<div class="cf-turnstile" data-sitekey="' . esc_attr( $site_key ) . '" data-action="' . esc_attr( $context ) . '"></div>';
			echo '</div>';
		}
	}

	private function output_recaptcha_v3_markup( $context ) {
		static $instance = 0;

		$site_key = get_option( self::OPTION_RECAPTCHA_SITE_KEY, '' );
		$instance++;

		$field_id = 'yoaa-g-recaptcha-' . sanitize_key( $context ) . '-' . $instance;

		echo '<input type="hidden" name="g-recaptcha-response" id="' . esc_attr( $field_id ) . '" value="">';
		?>
		<script>
		document.addEventListener('DOMContentLoaded', function() {
			var siteKey = <?php echo wp_json_encode( $site_key ); ?>;
			var context = <?php echo wp_json_encode( $context ); ?>;
			var hidden = document.getElementById(<?php echo wp_json_encode( $field_id ); ?>);

			if (!hidden) {
				return;
			}

			var form = hidden.closest('form');
			if (!form || form.__yoaaRecaptchaV3Bound) {
				return;
			}

			form.__yoaaRecaptchaV3Bound = true;
			form.addEventListener('click', function(event) {
				var target = event.target;
				if (target && target.matches && target.matches('button[type="submit"], input[type="submit"]')) {
					form.__yoaaRecaptchaSubmitter = target;
				}
			}, true);

			form.addEventListener('submit', function(event) {
				if (hidden.value || form.__yoaaRecaptchaV3Submitting) {
					return;
				}

				if (typeof grecaptcha === 'undefined' || !grecaptcha.ready) {
					return;
				}

				event.preventDefault();
				form.__yoaaRecaptchaV3Submitting = true;

				grecaptcha.ready(function() {
					grecaptcha.execute(siteKey, { action: context }).then(function(token) {
						hidden.value = token || '';
						var submitter = event.submitter || form.__yoaaRecaptchaSubmitter;

						if (typeof form.requestSubmit === 'function') {
							form.requestSubmit(submitter || undefined);
						} else {
							form.submit();
						}

						setTimeout(function() {
							form.__yoaaRecaptchaV3Submitting = false;
							hidden.value = '';
						}, 2000);
					}).catch(function() {
						var submitter = event.submitter || form.__yoaaRecaptchaSubmitter;
						hidden.value = '';

						if (typeof form.requestSubmit === 'function') {
							form.requestSubmit(submitter || undefined);
						} else {
							form.submit();
						}

						setTimeout(function() {
							form.__yoaaRecaptchaV3Submitting = false;
						}, 2000);
					});
				});
			}, true);
		});
		</script>
		<?php
	}

	private function validate_current_request( $context ) {
		$provider = $this->get_selected_provider();

		if ( 'none' === $provider ) {
			return true;
		}

		$expected_action = in_array( $provider, array( 'recaptcha_v3', 'cloudflare' ), true ) ? $context : '';

		return $this->run_captcha_validation( $provider, $_POST, $expected_action );
	}

	private function run_captcha_validation( $provider, $data, $expected_action = '' ) {
		if ( ! $this->is_provider_configured( $provider ) ) {
			return false;
		}

		$token = $this->get_token_from_data( $provider, $data );
		if ( '' === $token ) {
			return false;
		}

		if ( $this->is_token_reused( $token ) ) {
			return false;
		}

		if ( 'recaptcha_v3' === $provider ) {
			$is_valid = $this->validate_recaptcha_v3( $token, $expected_action );
		} elseif ( 'recaptcha_v2' === $provider ) {
			$is_valid = $this->validate_recaptcha_v2( $token );
		} elseif ( 'hcaptcha' === $provider ) {
			$is_valid = $this->validate_hcaptcha( $token );
		} elseif ( 'cloudflare' === $provider ) {
			$is_valid = $this->validate_cloudflare( $token, $expected_action );
		} else {
			$is_valid = false;
		}

		if ( $is_valid ) {
			$this->mark_token_used( $token );
		}

		return $is_valid;
	}

	private function validate_recaptcha_v3( $token, $expected_action = '' ) {
		$secret = get_option( self::OPTION_RECAPTCHA_SECRET_KEY, '' );
		if ( '' === $secret ) {
			return false;
		}

		$threshold = (float) get_option( self::OPTION_RECAPTCHA_THRESHOLD, 0.5 );
		$threshold = max( 0.0, min( 1.0, $threshold ) );

		$response = $this->verify_provider_token(
			'https://www.google.com/recaptcha/api/siteverify',
			array(
				'secret'   => $secret,
				'response' => $token,
			)
		);

		if ( ! is_array( $response ) || ( ! isset( $response['success'] ) || true !== $response['success'] ) ) {
			return false;
		}

		if ( ! $this->is_valid_hostname( isset( $response['hostname'] ) ? $response['hostname'] : '' ) ) {
			return false;
		}

		if ( '' !== $expected_action && (string) ( isset( $response['action'] ) ? $response['action'] : '' ) !== $expected_action ) {
			return false;
		}

		return isset( $response['score'] ) && is_numeric( $response['score'] )
			&& (float) $response['score'] >= $threshold && (float) $response['score'] <= 1.0;
	}

	private function validate_recaptcha_v2( $token ) {
		$secret = get_option( self::OPTION_RECAPTCHA_V2_SECRET, '' );
		if ( '' === $secret ) {
			return false;
		}

		$response = $this->verify_provider_token(
			'https://www.google.com/recaptcha/api/siteverify',
			array(
				'secret'   => $secret,
				'response' => $token,
			)
		);

		return is_array( $response )
			&& ( isset( $response['success'] ) && true === $response['success'] )
			&& $this->is_valid_hostname( isset( $response['hostname'] ) ? $response['hostname'] : '' );
	}

	private function validate_hcaptcha( $token ) {
		$secret = get_option( self::OPTION_HCAPTCHA_SECRET, '' );
		if ( '' === $secret ) {
			return false;
		}

		$response = $this->verify_provider_token(
			'https://hcaptcha.com/siteverify',
			array(
				'secret'   => $secret,
				'response' => $token,
				'sitekey'  => (string) get_option( self::OPTION_HCAPTCHA_SITE, '' ),
			)
		);

		return is_array( $response ) && ( isset( $response['success'] ) && true === $response['success'] );
	}

	private function validate_cloudflare( $token, $expected_action = '' ) {
		$secret = get_option( self::OPTION_CLOUDFLARE_SECRET, '' );
		if ( '' === $secret ) {
			return false;
		}

		$response = $this->verify_provider_token(
			'https://challenges.cloudflare.com/turnstile/v0/siteverify',
			array(
				'secret'          => $secret,
				'response'        => $token,
				'idempotency_key' => function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'yoaa_', true ),
			)
		);

		if ( ! is_array( $response ) || ( ! isset( $response['success'] ) || true !== $response['success'] ) ) {
			return false;
		}

		if ( ! $this->is_valid_hostname( isset( $response['hostname'] ) ? $response['hostname'] : '' ) ) {
			return false;
		}

		if ( '' !== $expected_action && (string) ( isset( $response['action'] ) ? $response['action'] : '' ) !== $expected_action ) {
			return false;
		}

		return true;
	}

	private function verify_provider_token( $endpoint, $body ) {
		$remote_ip = $this->get_client_ip();
		if ( '' !== $remote_ip ) {
			$body['remoteip'] = $remote_ip;
		}

		$response = wp_safe_remote_post(
			$endpoint,
			array(
				'body'    => $body,
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$payload = json_decode( wp_remote_retrieve_body( $response ), true );

		return is_array( $payload ) ? $payload : false;
	}

	private function get_token_from_data( $provider, $data ) {
		$key = '';

		if ( 'recaptcha_v3' === $provider || 'recaptcha_v2' === $provider ) {
			$key = 'g-recaptcha-response';
		} elseif ( 'hcaptcha' === $provider ) {
			$key = 'h-captcha-response';
		} elseif ( 'cloudflare' === $provider ) {
			$key = 'cf-turnstile-response';
		}

		if ( '' === $key || ! isset( $data[ $key ] ) ) {
			return '';
		}

		$token = wp_unslash( $data[ $key ] );
		$token = is_string( $token ) ? trim( $token ) : '';

		return sanitize_text_field( $token );
	}

	private function is_token_reused( $token ) {
		if ( function_exists( 'WC' ) && WC() && WC()->session ) {
			$used_tokens = (array) WC()->session->get( 'yoaa_used_captcha_tokens', array() );

			return in_array( $token, $used_tokens, true );
		}

		return false;
	}

	private function mark_token_used( $token ) {
		if ( function_exists( 'WC' ) && WC() && WC()->session ) {
			$used_tokens   = (array) WC()->session->get( 'yoaa_used_captcha_tokens', array() );
			$used_tokens[] = $token;

			WC()->session->set(
				'yoaa_used_captcha_tokens',
				array_slice( array_unique( $used_tokens ), -20 )
			);
		}
	}

	private function get_client_ip() {
		if ( class_exists( 'WC_Geolocation' ) && is_callable( array( 'WC_Geolocation', 'get_ip_address' ) ) ) {
			$ip = WC_Geolocation::get_ip_address();
			if ( is_string( $ip ) && '' !== $ip ) {
				return $ip;
			}
		}

		$keys = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );
		foreach ( $keys as $key ) {
			if ( empty( $_SERVER[ $key ] ) ) {
				continue;
			}

			$raw = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
			if ( 'HTTP_X_FORWARDED_FOR' === $key ) {
				$parts = array_map( 'trim', explode( ',', $raw ) );
				$raw   = (string) reset( $parts );
			}

			if ( filter_var( $raw, FILTER_VALIDATE_IP ) ) {
				return $raw;
			}
		}

		return '';
	}

	private function get_expected_hostname() {
		$host = wp_parse_url( home_url(), PHP_URL_HOST );

		return is_string( $host ) ? strtolower( $host ) : '';
	}

	private function is_valid_hostname( $received_hostname ) {
		$expected = $this->get_expected_hostname();
		if ( '' === $expected ) {
			return true;
		}

		$received = is_string( $received_hostname ) ? strtolower( trim( $received_hostname ) ) : '';
		if ( '' === $received ) {
			return false;
		}

		return $received === $expected;
	}

	private function is_provider_configured( $provider ) {
		if ( 'recaptcha_v3' === $provider ) {
			return (bool) get_option( self::OPTION_RECAPTCHA_SITE_KEY, '' )
				&& (bool) get_option( self::OPTION_RECAPTCHA_SECRET_KEY, '' );
		}

		if ( 'recaptcha_v2' === $provider ) {
			return (bool) get_option( self::OPTION_RECAPTCHA_V2_SITE, '' )
				&& (bool) get_option( self::OPTION_RECAPTCHA_V2_SECRET, '' );
		}

		if ( 'hcaptcha' === $provider ) {
			return (bool) get_option( self::OPTION_HCAPTCHA_SITE, '' )
				&& (bool) get_option( self::OPTION_HCAPTCHA_SECRET, '' );
		}

		if ( 'cloudflare' === $provider ) {
			return (bool) get_option( self::OPTION_CLOUDFLARE_SITE, '' )
				&& (bool) get_option( self::OPTION_CLOUDFLARE_SECRET, '' );
		}

		return false;
	}

	private function get_selected_provider() {
		$provider = get_option( self::OPTION_PROVIDER, 'none' );
		$provider = is_string( $provider ) ? $provider : 'none';

		return in_array( $provider, $this->get_supported_providers(), true ) ? $provider : 'none';
	}

	private function get_supported_providers() {
		return array( 'none', 'cloudflare', 'recaptcha_v3', 'recaptcha_v2', 'hcaptcha' );
	}

	private function is_login_enabled() {
		return 'yes' === get_option( self::OPTION_ENABLE_LOGIN, 'no' );
	}

	private function is_register_enabled() {
		return 'yes' === get_option( self::OPTION_ENABLE_REGISTER, 'no' );
	}

	private function is_any_form_enabled() {
		return $this->is_login_enabled() || $this->is_register_enabled();
	}

	private function is_wp_login_post() {
		return isset( $_POST['log'], $_POST['pwd'] );
	}
}

add_action( 'plugins_loaded', static function() {
	// Premium registers its CAPTCHA integration independently of commercial entitlement.
	if ( class_exists( 'YOAA_WC_Advanced_Accounts_Captcha_Integration_Premium' ) ) {
		return;
	}
	new YOAA_WC_Advanced_Accounts_Captcha_Integration();
}, 110 );
