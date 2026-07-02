<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YOAA_WC_Advanced_Accounts_Membership_Shortcodes_Free {

	/**
	 * Boot.
	 */
	public static function init() {
		$membership_roles = get_option( 'yoaa_wc_membership_roles', array() );

		if ( empty( $membership_roles ) || ! is_array( $membership_roles ) ) {
			return;
		}

		add_shortcode( 'yoaa_membership', array( __CLASS__, 'render_membership_shortcode' ) );
	}

	/**
	 * Render [yoaa_membership] shortcode.
	 *
	 * Supported attributes:
	 * - level="silver_member"
	 * - guest="yes"
	 * - logged_in="yes"
	 * - hide="yes"
	 *
	 * @param array       $atts    Shortcode attributes.
	 * @param string|null $content Shortcode content.
	 * @return string
	 */
	public static function render_membership_shortcode( $atts, $content = null ) {
		$atts = shortcode_atts(
			array(
				'level'     => '',
				'guest'     => '',
				'logged_in' => '',
				'hide'      => '',
			),
			(array) $atts,
			'yoaa_membership'
		);

		$required_level = self::parse_level( $atts['level'] );
		$guest_only     = self::is_truthy( $atts['guest'] );
		$logged_in_only = self::is_truthy( $atts['logged_in'] );
		$hide           = self::is_truthy( $atts['hide'] );

		$content = do_shortcode( (string) $content );

		$has_access = self::user_has_access(
			array(
				'level'          => $required_level,
				'guest_only'     => $guest_only,
				'logged_in_only' => $logged_in_only,
			)
		);

		if ( $has_access ) {
			return $content;
		}

		if ( $hide ) {
			return '';
		}

		$message = self::get_default_message( $required_level, $guest_only, $logged_in_only );

		return self::get_restriction_notice_html( $message, self::get_current_url() );
	}

	/**
	 * Check if the current user can access the shortcode content.
	 *
	 * @param array $args Access arguments.
	 * @return bool
	 */
	private static function user_has_access( $args ) {
		$required_level = ! empty( $args['level'] ) ? sanitize_key( $args['level'] ) : '';
		$guest_only     = ! empty( $args['guest_only'] );
		$logged_in_only = ! empty( $args['logged_in_only'] );
		$is_logged_in   = is_user_logged_in();

		if ( $guest_only ) {
			return ! $is_logged_in;
		}

		if ( $logged_in_only && ! $is_logged_in ) {
			return false;
		}

		if ( ! empty( $required_level ) ) {
			if ( ! $is_logged_in ) {
				return false;
			}

			$user_levels = self::get_current_user_membership_roles();

			if ( empty( $user_levels ) ) {
				return false;
			}

			return in_array( $required_level, $user_levels, true );
		}

		if ( $logged_in_only ) {
			return true;
		}

		return true;
	}

	/**
	 * Parse a single level attribute into a valid membership role.
	 *
	 * @param string $level Membership level slug.
	 * @return string
	 */
	private static function parse_level( $level ) {
		if ( empty( $level ) || ! is_string( $level ) ) {
			return '';
		}

		$level = sanitize_key( trim( $level ) );

		if ( empty( $level ) ) {
			return '';
		}

		$membership_roles = self::get_membership_roles();

		if ( empty( $membership_roles ) ) {
			return '';
		}

		return in_array( $level, $membership_roles, true ) ? $level : '';
	}

	/**
	 * Build default restriction message.
	 *
	 * @param string $required_level Required membership level.
	 * @param bool   $guest_only     Guests-only flag.
	 * @param bool   $logged_in_only Logged-in-only flag.
	 * @return string
	 */
	private static function get_default_message( $required_level, $guest_only, $logged_in_only ) {
		if ( $guest_only ) {
			return __( 'This content is available only for guests.', 'wc-advanced-accounts' );
		}

		if ( ! empty( $required_level ) ) {
			return sprintf(
				/* translators: %s: membership role label */
				__( 'This content is available only for the following membership level: %s.', 'wc-advanced-accounts' ),
				self::get_role_label( $required_level )
			);
		}

		if ( $logged_in_only ) {
			return __( 'This content is available only for logged-in users.', 'wc-advanced-accounts' );
		}

		return __( 'You do not have permission to view this content.', 'wc-advanced-accounts' );
	}

	/**
	 * Build restriction notice HTML.
	 *
	 * @param string $message      Notice message.
	 * @param string $redirect_url Redirect after login.
	 * @return string
	 */
	private static function get_restriction_notice_html( $message, $redirect_url = '' ) {
		$login_url = wc_get_page_permalink( 'myaccount' );

		if ( empty( $login_url ) ) {
			$login_url = wp_login_url();
		}

		if ( ! empty( $redirect_url ) ) {
			$login_url = add_query_arg(
				'redirect_to',
				rawurlencode( $redirect_url ),
				$login_url
			);
		}

		$output  = '<div class="woocommerce-info yoaa-membership-shortcode-restricted" style="margin:0 0 24px;">';
		$output .= esc_html( $message );

		if ( ! is_user_logged_in() ) {
			$output .= ' <a class="button yoaa-membership-login-button" href="' . esc_url( $login_url ) . '">';
			$output .= esc_html__( 'Login to continue', 'wc-advanced-accounts' );
			$output .= '</a>';
		}

		$output .= '</div>';

		return $output;
	}

	/**
	 * Get current URL for redirect_after_login usage.
	 *
	 * @return string
	 */
	private static function get_current_url() {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		if ( empty( $request_uri ) ) {
			return home_url( '/' );
		}

		return home_url( $request_uri );
	}

	/**
	 * Get configured membership roles.
	 *
	 * @return array
	 */
	private static function get_membership_roles() {
		$roles = get_option( 'yoaa_wc_membership_roles', array() );

		if ( ! is_array( $roles ) ) {
			return array();
		}

		return array_values( array_filter( array_map( 'sanitize_key', $roles ) ) );
	}

	/**
	 * Get current user's membership roles only.
	 *
	 * @return array
	 */
	private static function get_current_user_membership_roles() {
		if ( ! is_user_logged_in() ) {
			return array();
		}

		$user = wp_get_current_user();

		if ( ! $user || empty( $user->roles ) || ! is_array( $user->roles ) ) {
			return array();
		}

		$membership_roles = self::get_membership_roles();

		if ( empty( $membership_roles ) ) {
			return array();
		}

		return array_values( array_intersect( $membership_roles, $user->roles ) );
	}

	/**
	 * Get readable label for a role slug.
	 *
	 * @param string $role Role slug.
	 * @return string
	 */
	private static function get_role_label( $role ) {
		if ( empty( $role ) || ! is_string( $role ) ) {
			return '';
		}

		global $wp_roles;

		$role = sanitize_key( $role );

		if ( isset( $wp_roles->roles[ $role ]['name'] ) ) {
			return $wp_roles->roles[ $role ]['name'];
		}

		return $role;
	}

	/**
	 * Check yes/no-like attribute values.
	 *
	 * @param mixed $value Attribute value.
	 * @return bool
	 */
	private static function is_truthy( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}

		$value = is_string( $value ) ? strtolower( trim( $value ) ) : '';

		return in_array( $value, array( 'yes', 'true', '1', 'on' ), true );
	}
}

YOAA_WC_Advanced_Accounts_Membership_Shortcodes_Free::init();
