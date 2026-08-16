<?php

if (!defined('ABSPATH')) {
	exit;
}

class YOAA_WC_Advanced_Accounts_Users_Page {
	public function __construct() {
		// 1) inject the “Verified” column before “Username”
		add_filter( 'manage_users_columns', [ $this, 'add_verified_column' ], 10, 1 );
	
		// 2) populate it with our dashicon when email verification is complete
		add_filter( 'manage_users_custom_column', [ $this, 'render_verified_column' ], 10, 3 );
	}
	
	/**
	 * Add a “Verified” column right before the Username column.
	 *
	 * @param array $columns
	 * @return array
	 */
	public function add_verified_column( $columns ) {
		$new = [];
		foreach ( $columns as $key => $label ) {
			if ( 'username' === $key ) {
				// f147 is the “yes‑alt” dashicon
				$new['verified'] = '<span class="dashicons dashicons-yes"></span>';
			}
			$new[ $key ] = $label;
		}
		return $new;
	}
	
	/**
	 * Render our dashicon if email_verification = 1.
	 *
	 * @param string $value
	 * @param string $column_name
	 * @param int    $user_id
	 * @return string
	 */
	public function render_verified_column( $value, $column_name, $user_id ) {
		if ( 'verified' !== $column_name ) {
			return $value;
		}

		$email_verified = get_user_meta( $user_id, 'email_verification', true );

		if ( '1' === $email_verified ) {
			// f12a glyph from Dashicons
			return '<span class="dashicons dashicons-yes-alt"></span>';
		}

		return '';
	}
	
}

new YOAA_WC_Advanced_Accounts_Users_Page();
