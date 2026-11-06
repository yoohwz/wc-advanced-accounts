<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Basic WooCommerce registration fields; the custom profile builder remains Premium. */
class YOAA_WC_Advanced_Accounts_Profile {
	public function __construct() {
		if ( 'yes' === get_option( 'yoaa_wc_enable_first_last_name_register', 'no' ) ) {
			add_action( 'woocommerce_register_form_start', [ $this, 'render_name_fields' ], 9 );
			add_action( 'woocommerce_register_post', [ $this, 'validate_name_fields' ], 10, 3 );
			add_action( 'woocommerce_created_customer', [ $this, 'save_name_fields' ] );
		}
	}

	private function is_registration_request() {
		$nonce = $_POST['woocommerce-register-nonce'] ?? ( $_POST['_wpnonce'] ?? '' );
		return isset( $_POST['register'] ) && is_string( $nonce ) && wp_verify_nonce( wp_unslash( $nonce ), 'woocommerce-register' );
	}

	private function posted_name( $key ) {
		return isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
	}

	public function render_name_fields() {
		foreach ( [ 'first_name' => __( 'First name', 'wc-advanced-accounts' ), 'last_name' => __( 'Last name', 'wc-advanced-accounts' ) ] as $key => $label ) {
			$class = 'first_name' === $key ? 'form-row-first' : 'form-row-last';
			echo '<p class="form-row ' . esc_attr( $class ) . '"><label for="reg_' . esc_attr( $key ) . '">' . esc_html( $label ) . ' <span class="required">*</span></label><input type="text" class="input-text" name="' . esc_attr( $key ) . '" id="reg_' . esc_attr( $key ) . '" value="' . esc_attr( $this->posted_name( $key ) ) . '" required /></p>';
		}
		echo '<div class="clear"></div>';
	}

	public function validate_name_fields( $username, $email, $errors ) {
		if ( ! $this->is_registration_request() ) {
			return;
		}
		if ( '' === $this->posted_name( 'first_name' ) ) {
			$errors->add( 'first_name_error', __( 'First name is required!', 'wc-advanced-accounts' ) );
		}
		if ( '' === $this->posted_name( 'last_name' ) ) {
			$errors->add( 'last_name_error', __( 'Last name is required!', 'wc-advanced-accounts' ) );
		}
	}

	public function save_name_fields( $customer_id ) {
		// Woo handles this form at wp_loaded, before is_account_page() is available.
		// Use its verified registration intent; leave checkout, REST and admin untouched.
		if ( ! $this->is_registration_request() ) {
			return;
		}
		$first = $this->posted_name( 'first_name' );
		$last  = $this->posted_name( 'last_name' );
		if ( '' !== $first && '' !== $last ) {
			wp_update_user( [ 'ID' => $customer_id, 'first_name' => $first, 'last_name' => $last, 'display_name' => $first . ' ' . $last ] );
		}
	}
}

add_action( 'plugins_loaded', static function() {
	if ( class_exists( 'YOAA_WC_Advanced_Accounts_Resgiter' ) && class_exists( 'WCAAP_Activation' ) && WCAAP_Activation::is_premium_active() ) {
		return;
	}
	new YOAA_WC_Advanced_Accounts_Profile();
}, 110 );
