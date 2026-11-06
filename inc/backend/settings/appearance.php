<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YOAA_WC_Advanced_Accounts_Appearance_Settings {
	public static function get_personalization_settings() {
		return [
			[ 'title' => __( 'Profile customization', 'wc-advanced-accounts' ), 'type' => 'title', 'id' => 'yoaa_wc_personalization_settings' ],
			[ 'title' => __( 'Avatar', 'wc-advanced-accounts' ), 'desc' => __( 'Allow customers to upload, replace and remove their profile avatar.', 'wc-advanced-accounts' ), 'id' => 'yoaa_wc_enable_avatar', 'default' => 'no', 'type' => 'checkbox' ],
			[ 'title' => __( 'Customer name', 'wc-advanced-accounts' ), 'desc' => __( 'Name displayed below the avatar on My Account.', 'wc-advanced-accounts' ), 'id' => 'yoaa_wc_welcome_name_display', 'default' => 'display_name', 'type' => 'select', 'options' => [ 'display_name' => __( 'Display name', 'wc-advanced-accounts' ), 'first_last' => __( 'First & Last name', 'wc-advanced-accounts' ), 'first' => __( 'First name', 'wc-advanced-accounts' ), 'last' => __( 'Last name', 'wc-advanced-accounts' ) ] ],
			[ 'type' => 'yoaa_upgrade_panel', 'title' => __( 'More profile fields in Premium', 'wc-advanced-accounts' ), 'desc' => __( 'Add custom profile fields, conditional fields, birth date and age requirements.', 'wc-advanced-accounts' ), 'id' => 'yoaa_profile_upgrade_panel' ],
			[ 'type' => 'sectionend', 'id' => 'yoaa_wc_personalization_settings' ],
		];
	}

	public static function get_registration_settings() {
		return [
			[ 'title' => __( 'Registration', 'wc-advanced-accounts' ), 'type' => 'title', 'id' => 'yoaa_wc_registration_settings' ],
			[ 'title' => __( 'First & last name', 'wc-advanced-accounts' ), 'desc' => __( 'Require first and last name on the My Account registration form.', 'wc-advanced-accounts' ), 'id' => 'yoaa_wc_enable_first_last_name_register', 'default' => 'no', 'type' => 'checkbox' ],
			[ 'type' => 'sectionend', 'id' => 'yoaa_wc_registration_settings' ],
		];
	}
}
