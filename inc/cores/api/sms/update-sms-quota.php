<?php

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('YOAA_SMS_Quota_Update')) {
	class YOAA_SMS_Quota_Update {
		public function __construct() {
			add_action('rest_api_init', array($this, 'register_api_routes'));
		}

			public function register_api_routes() {
				register_rest_route('yoohw-sms/v1', '/update-sms-quota', array(
					'methods'  => 'POST',
					'callback' => array($this, 'update_sms_quota'),
					'permission_callback' => array($this, 'verify_request'),
				));
			}

			public function verify_request(WP_REST_Request $request) {
				$stored_sms_key = (string) get_option('yoohw_phone_verification_sms_key');

				if ( '' === $stored_sms_key ) {
					return new WP_Error(
						'yoaa_sms_quota_key_missing',
						__('SMS key is not configured.', 'wc-advanced-accounts'),
						array('status' => 403)
					);
				}

				$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
				$rate_key = 'yoaa_sms_quota_rate_' . md5( $ip );

				if ( get_transient( $rate_key ) ) {
					return new WP_Error(
						'yoaa_sms_quota_rate_limited',
						__('Too many quota update attempts. Please try again later.', 'wc-advanced-accounts'),
						array('status' => 429)
					);
				}

				set_transient( $rate_key, 1, MINUTE_IN_SECONDS );

				$timestamp = $request->get_header( 'x-yoaa-timestamp' );
				$signature = $request->get_header( 'x-yoaa-signature' );

				$timestamp = is_string( $timestamp ) ? sanitize_text_field( $timestamp ) : '';
				$signature = is_string( $signature ) ? sanitize_text_field( $signature ) : '';

				if ( '' === $timestamp || '' === $signature || ! ctype_digit( $timestamp ) ) {
					return new WP_Error(
						'yoaa_sms_quota_signature_missing',
						__('Missing quota update signature.', 'wc-advanced-accounts'),
						array('status' => 403)
					);
				}

				if ( abs( time() - (int) $timestamp ) > 5 * MINUTE_IN_SECONDS ) {
					return new WP_Error(
						'yoaa_sms_quota_signature_expired',
						__('Expired quota update signature.', 'wc-advanced-accounts'),
						array('status' => 403)
					);
				}

				$body = (string) $request->get_body();
				$expected_signature = hash_hmac( 'sha256', $timestamp . '|' . $body, $stored_sms_key );

				if ( ! hash_equals( $expected_signature, $signature ) ) {
					return new WP_Error(
						'yoaa_sms_quota_signature_invalid',
						__('Invalid quota update signature.', 'wc-advanced-accounts'),
						array('status' => 403)
					);
				}

				return true;
			}

			public function update_sms_quota(WP_REST_Request $request) {
				$new_quota_raw = $request->get_param('new_quota');

				if ( ! is_numeric( $new_quota_raw ) ) {
					return new WP_Error(
						'yoaa_sms_quota_invalid',
						__('Invalid SMS quota value.', 'wc-advanced-accounts'),
						array('status' => 400)
					);
				}

				$new_quota = max( 0, (float) $new_quota_raw );

				update_option('yoohw_phone_verification_sms_quota', $new_quota);

				return rest_ensure_response(array(
					'status'    => 'success',
					'message'   => __('Quota updated successfully.', 'wc-advanced-accounts'),
					'new_quota' => $new_quota,
				));
			}
		}

	new YOAA_SMS_Quota_Update();
}
