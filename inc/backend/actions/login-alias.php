<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YOAA_WC_Advanced_Accounts_Free_Login_Alias {

	public static function init() {
		add_filter( 'authenticate', array( __CLASS__, 'map_old_username_to_user' ), 19, 3 );
	}

	/**
	 * Allow logging in with old phone usernames and normalized phone aliases.
	 *
	 * @param WP_User|WP_Error|null $user
	 * @param string                $username
	 * @param string                $password
	 * @return WP_User|WP_Error|null
	 */
	public static function map_old_username_to_user( $user, $username, $password ) {
		if ( $user instanceof WP_User ) {
			return $user;
		}

		$username = is_string( $username ) ? trim( $username ) : '';
		if ( '' === $username ) {
			return $user;
		}

		if ( username_exists( $username ) ) {
			return $user;
		}

		$found = false;

		if ( class_exists( 'YOAA_Phone_Username_Helper' ) ) {
			$found = YOAA_Phone_Username_Helper::find_user_by_identifier( $username );
		}

		if ( ! $found ) {
			$found = self::find_user_by_old_username_alias( $username );
		}

		if ( ! $found ) {
			return $user;
		}

		return wp_authenticate_username_password( null, $found->user_login, $password );
	}

	private static function find_user_by_old_username_alias( $old_username ) {
		$old_username = sanitize_user( (string) $old_username, true );
		if ( '' === $old_username ) {
			return false;
		}

		$q = new WP_User_Query(
			array(
				'number'     => 1,
				'fields'     => 'all',
				'meta_key'   => '_yoaa_old_username_before_phone_migration',
				'meta_value' => $old_username,
			)
		);

		$users = (array) $q->get_results();
		if ( empty( $users ) ) {
			return false;
		}

		return $users[0];
	}
}

YOAA_WC_Advanced_Accounts_Free_Login_Alias::init();
