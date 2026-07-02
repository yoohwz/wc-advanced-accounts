<?php

if (!defined('ABSPATH')) {
	exit;
}

class YOAA_WC_Advanced_Accounts_Appearance_Settings {
	public static function get_personalization_settings() {
		return array(
			array(
				'title' => esc_html__('Profile customization', 'wc-advanced-accounts'),
				'type'  => 'title',
				'id'    => 'yoaa_wc_personalization_settings'
			),
			array(
				'type'        => 'yoaa_upgrade_panel',
				'title'       => esc_html__( 'Customize customer profiles', 'wc-advanced-accounts' ),
				'desc'        => esc_html__( 'Add optional profile and registration fields when your customer account experience needs more detail.', 'wc-advanced-accounts' ),
				'features'    => array(
					esc_html__( 'Let customers upload profile avatars.', 'wc-advanced-accounts' ),
					esc_html__( 'Show a preferred customer name format under the avatar.', 'wc-advanced-accounts' ),
					esc_html__( 'Add first name, last name, and birth date fields to registration.', 'wc-advanced-accounts' ),
					esc_html__( 'Use birth date requirements and age limit checks.', 'wc-advanced-accounts' ),
				),
				'button_text' => esc_html__( 'View profile features', 'wc-advanced-accounts' ),
				'id'          => 'yoaa_profile_upgrade_panel',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'yoaa_wc_personalization_settings'
			),
		);
	}

	public static function get_registration_settings() {
		return array();
	}
}
