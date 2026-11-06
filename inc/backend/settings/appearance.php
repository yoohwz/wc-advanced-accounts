<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YOAA_WC_Advanced_Accounts_Appearance_Settings {
	public static function get_personalization_settings() {
		return [
			[ 'title' => __( 'Personalization', 'wc-advanced-accounts' ), 'type' => 'title', 'id' => 'yoaa_wc_personalization_settings' ],
			[ 'title' => __( 'Avatar', 'wc-advanced-accounts' ), 'desc' => __( 'Allow users to upload their own profile avatar', 'wc-advanced-accounts' ), 'desc_tip' => __( 'The avatar is also used in comments and reviews. Customers can replace or remove it from My Account.', 'wc-advanced-accounts' ), 'id' => 'yoaa_wc_enable_avatar', 'default' => 'no', 'type' => 'checkbox' ],
			[ 'title' => __( 'Customer name', 'wc-advanced-accounts' ), 'desc' => __( 'Controls the name below the Avatar profile header. Enable Avatar to change this setting.', 'wc-advanced-accounts' ), 'desc_tip' => __( 'Select the name format displayed under the avatar. For example: Welcome, John Doe.', 'wc-advanced-accounts' ), 'id' => 'yoaa_wc_welcome_name_display', 'default' => 'display_name', 'type' => 'select', 'options' => [ 'display_name' => __( 'Display name', 'wc-advanced-accounts' ), 'first_last' => __( 'First & Last name', 'wc-advanced-accounts' ), 'first' => __( 'First name', 'wc-advanced-accounts' ), 'last' => __( 'Last name', 'wc-advanced-accounts' ) ] ],
			[ 'type' => 'sectionend', 'id' => 'yoaa_wc_personalization_settings' ],
		];
	}

	public static function get_registration_settings() {
		return [
			[ 'title' => __( 'Registration', 'wc-advanced-accounts' ), 'type' => 'title', 'id' => 'yoaa_wc_registration_settings' ],
			[ 'title' => __( 'First & last name', 'wc-advanced-accounts' ), 'desc' => __( 'Require first and last name on the My Account registration form.', 'wc-advanced-accounts' ), 'id' => 'yoaa_wc_enable_first_last_name_register', 'default' => 'no', 'type' => 'checkbox' ],
			[ 'type' => 'yoaa_upgrade_panel', 'title' => __( 'Build richer customer profiles', 'wc-advanced-accounts' ), 'desc' => __( 'Collect company names, tax IDs and more with custom profile fields. Add conditional visibility, birthday and age requirements.', 'wc-advanced-accounts' ), 'button_text' => __( 'Explore custom profile fields', 'wc-advanced-accounts' ), 'id' => 'yoaa_profile_upgrade_panel' ],
			[ 'type' => 'sectionend', 'id' => 'yoaa_wc_registration_settings' ],
		];
	}
}
