<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YOAA_WC_Advanced_Accounts_Membership_Shortcodes_Free {

	/**
	 * Boot.
	 */
	public static function init() {
		// Always register: a deselected last role must not expose an old explicit policy.
		add_shortcode( 'yoaa_membership', array( __CLASS__, 'render_membership_shortcode' ) );
		YOAA_Membership_Content_Renderer::init();
	}

	/**
	 * Render [yoaa_membership] shortcode.
	 *
	 * Supported attributes:
	 * - level="silver_member,gold_member"
	 * - guest="yes"
	 * - logged_in="yes"
	 * - hide="yes"
	 *
	 * @param array       $atts    Shortcode attributes.
	 * @param string|null $content Shortcode content.
	 * @return string
	 */
	public static function render_membership_shortcode( $atts, $content = null ) {
		$atts = self::normalize_attributes( $atts );
		$required_levels = self::parse_levels( $atts['level'] );
		$guest_only      = self::is_truthy( $atts['guest'] );
		$logged_in_only  = self::is_truthy( $atts['logged_in'] );
		$hide            = self::is_truthy( $atts['hide'] );

		$has_access = null !== $required_levels && self::user_has_access(
			array(
				'levels'         => $required_levels,
				'guest_only'     => $guest_only,
				'logged_in_only' => $logged_in_only,
			)
		);

		if ( $has_access ) {
			return do_shortcode( (string) $content );
		}

		if ( $hide ) {
			return '';
		}

		$message = self::get_default_message( (array) $required_levels, $guest_only, $logged_in_only );

		return self::get_restriction_notice_html( $message, self::get_current_url() );
	}

	/** Policy-only query for the shared pre-authorizer; never renders the body. */
	public static function allows_membership_shortcode( $atts ) {
		$atts = self::normalize_attributes( $atts );
		$levels = self::parse_levels( $atts['level'] );
		return null !== $levels && self::user_has_access( array(
			'levels' => $levels,
			'guest_only' => self::is_truthy( $atts['guest'] ),
			'logged_in_only' => self::is_truthy( $atts['logged_in'] ),
		) );
	}

	private static function normalize_attributes( $atts ) {
		return shortcode_atts(
			array(
				'level'     => '',
				'guest'     => '',
				'logged_in' => '',
				'hide'      => '',
			),
			(array) $atts,
			'yoaa_membership'
		);

	}

	/**
	 * Check if the current user can access the shortcode content.
	 *
	 * @param array $args Access arguments.
	 * @return bool
	 */
	private static function user_has_access( $args ) {
		$levels         = ! empty( $args['levels'] ) && is_array( $args['levels'] ) ? $args['levels'] : array();
		$guest_only     = ! empty( $args['guest_only'] );
		$logged_in_only = ! empty( $args['logged_in_only'] );
		$is_logged_in   = is_user_logged_in();

		if ( $guest_only ) {
			return ! $is_logged_in;
		}

		if ( $logged_in_only && ! $is_logged_in ) {
			return false;
		}

		if ( ! empty( $levels ) ) {
			if ( ! $is_logged_in ) {
				return false;
			}

			$user_levels = self::get_current_user_membership_roles();

			if ( empty( $user_levels ) ) {
				return false;
			}

			return (bool) array_intersect( $levels, $user_levels );
		}

		if ( $logged_in_only ) {
			return true;
		}

		return true;
	}

	/**
	 * Parse explicit levels; null means an unresolved policy, never public access.
	 *
	 * @param string $levels_csv Levels CSV.
	 * @return array|null
	 */
	private static function parse_levels( $levels_csv ) {
		if ( ! is_string( $levels_csv ) ) {
			return null;
		}
		if ( '' === trim( $levels_csv ) ) {
			return array();
		}

		$membership_roles = self::get_membership_roles();
		$levels = array();
		foreach ( explode( ',', $levels_csv ) as $token ) {
			$token = trim( $token );
			if ( '' === $token ) {
				continue;
			}
			$level = sanitize_key( $token );
			if ( '' === $level || ! in_array( $level, $membership_roles, true ) || ! get_role( $level ) ) {
				return null;
			}
			$levels[] = $level;
		}

		return empty( $levels ) ? null : array_values( array_unique( $levels ) );
	}

	/**
	 * Build default restriction message.
	 *
	 * @param array  $required_levels Required membership levels.
	 * @param bool   $guest_only     Guests-only flag.
	 * @param bool   $logged_in_only Logged-in-only flag.
	 * @return string
	 */
	private static function get_default_message( $required_levels, $guest_only, $logged_in_only ) {
		if ( $guest_only ) {
			return __( 'This content is available only for guests.', 'wc-advanced-accounts' );
		}

		if ( ! empty( $required_levels ) ) {
			return sprintf(
				/* translators: %s: membership role label */
				__( 'This content is available only for the following membership level: %s.', 'wc-advanced-accounts' ),
				implode( ', ', array_map( array( __CLASS__, 'get_role_label' ), $required_levels ) )
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

/** Shared input guard; each plugin carries this contract for standalone/coexisting use. */
if ( ! class_exists( 'YOAA_Membership_Content_Renderer' ) ) {
	class YOAA_Membership_Content_Renderer {
		public static function init() {
			foreach ( array( 'the_content', 'widget_text_content', 'widget_block_content' ) as $hook ) {
				add_filter( $hook, array( __CLASS__, 'preauthorize_content' ), -9998 );
			}
		}

		/**
		 * Strip denied bodies without executing any nested shortcode or block.
		 * Empty denied wrappers let WordPress render the owner's notice in its normal context.
		 * Ambiguous same-tag nesting is denied, not interpreted as a new shortcode language.
		 */
		public static function preauthorize_content( $content ) {
			if ( ! is_string( $content ) || false === strpos( $content, 'yoaa_membership' ) ) {
				return $content;
			}
			global $shortcode_tags;
			$callback = isset( $shortcode_tags['yoaa_membership'] ) ? $shortcode_tags['yoaa_membership'] : null;
			$owner = is_array( $callback ) && isset( $callback[0], $callback[1] )
				&& in_array( $callback[0], array( 'YOAA_WC_Advanced_Accounts_Membership_Shortcodes', 'YOAA_WC_Advanced_Accounts_Membership_Shortcodes_Free' ), true )
				&& 'render_membership_shortcode' === $callback[1]
				&& is_callable( array( $callback[0], 'allows_membership_shortcode' ) ) ? $callback[0] : null;
			$output = '';
			$offset = 0;
			while ( $token = self::next_token( $content, $offset ) ) {
				$output .= substr( $content, $offset, $token['start'] - $offset );
				$end = $token['end'];
				if ( $token['escaped'] ) {
					$output .= substr( $content, $token['start'], $end - $token['start'] );
				} elseif ( $token['closing'] ) {
					$output .= '[yoaa_membership level="!" /]';
				} else {
					$malformed = ! $token['valid'];
					if ( ! $token['self_closing'] ) {
						$depth = 1 + $token['header_openers'];
						while ( $next = self::next_token( $content, $end ) ) {
							$end = $next['end'];
							if ( $next['escaped'] ) {
								continue;
							}
							if ( $next['closing'] && $next['valid'] ) {
								--$depth;
								if ( 0 === $depth ) {
									break;
								}
							} else {
								$malformed = true;
								if ( ! $next['closing'] && ! $next['self_closing'] ) {
									$depth += 1 + $next['header_openers'];
								}
							}
						}
						if ( 0 !== $depth ) {
							$end = strlen( $content );
							$malformed = true;
						}
					}
					if ( $malformed ) {
						$output .= '[yoaa_membership level="!" /]';
					} elseif ( $owner && $owner::allows_membership_shortcode( shortcode_parse_atts( $token['attributes'] ) ) ) {
						$output .= substr( $content, $token['start'], $end - $token['start'] );
					} else {
						$output .= substr( $content, $token['start'], $token['end'] - $token['start'] );
						if ( ! $token['self_closing'] ) {
							$output .= '[/yoaa_membership]';
						}
					}
				}
				$offset = $end;
			}
			return $output . substr( $content, $offset );
		}

		/** Find one wrapper boundary, honoring WordPress's fully escaped syntax. */
		private static function next_token( $content, $offset ) {
			if ( ! preg_match( '/\[(\[?)(\/?)yoaa_membership(?![\w-])/', $content, $match, PREG_OFFSET_CAPTURE, $offset ) ) {
				return null;
			}
			$start = $match[0][1];
			$header_start = $start + strlen( $match[0][0] );
			$close = strpos( $content, ']', $header_start );
			$end = false === $close ? strlen( $content ) : $close + 1;
			$closing = '/' === $match[2][0];
			if ( '[' === $match[1][0] ) {
				// Use WP's escape grammar only; no callbacks or body rendering.
				$pattern = '/\G' . get_shortcode_regex( array( 'yoaa_membership' ) ) . '/s';
				if ( preg_match( $pattern, $content, $escaped, 0, $start ) && '[' === $escaped[1] && ']' === $escaped[6] ) {
					return array( 'start' => $start, 'end' => $start + strlen( $escaped[0] ), 'escaped' => true );
				}
				if ( $closing && false !== $close && isset( $content[ $close + 1 ] ) && ']' === $content[ $close + 1 ] ) {
					return array( 'start' => $start, 'end' => $close + 2, 'escaped' => true );
				}
			}
			$attributes = false === $close ? '' : substr( $content, $header_start, $close - $header_start );
			$self_closing = ! $closing && '/' === substr( rtrim( $attributes ), -1 );
			if ( $self_closing ) {
				$attributes = substr( rtrim( $attributes ), 0, -1 );
			}
			$valid = false !== $close && '' === $match[1][0]
				&& ( $closing ? '' === $attributes : self::valid_attributes( $attributes ) );
			$self_closing = $self_closing && $valid;
			return array( 'start' => $start, 'end' => $end, 'escaped' => false, 'closing' => $closing,
				'self_closing' => $self_closing, 'valid' => $valid, 'attributes' => $attributes,
				'header_openers' => preg_match_all( '/\[\[?yoaa_membership(?![\w-])/', $attributes ) );
		}

		private static function valid_attributes( $attributes ) {
			if ( false !== strpos( $attributes, '[' ) ) {
				return false;
			}
			$attributes = preg_replace( '/[\x{00a0}\x{200b}]+/u', ' ', $attributes );
			if ( null === $attributes ) {
				return false;
			}
			// Fully consume attributes; WP's permissive fallback can lose a malformed level policy.
			$token = '(?:[\w-]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s\'"]+)|"[^"]*"|\'[^\']*\'|[^\s\'"=]+)';
			if ( 1 !== preg_match( '/\A\s*(?:' . $token . '(?:\s+' . $token . ')*)?\s*\z/', $attributes ) ) {
				return false;
			}
			// Native parsing can erase a nonempty level (e.g. an unclosed HTML value).
			// Inspect each explicit level before that loss; it must never become a public policy.
			preg_match_all( get_shortcode_atts_regex(), $attributes, $matches, PREG_SET_ORDER );
			foreach ( $matches as $match ) {
				$key = ! empty( $match[1] ) ? $match[1] : ( ! empty( $match[3] ) ? $match[3] : ( $match[5] ?? '' ) );
				if ( 'level' !== strtolower( $key ) ) {
					continue;
				}
				$value = ! empty( $match[1] ) ? $match[2] : ( ! empty( $match[3] ) ? $match[4] : $match[6] );
				$parsed = shortcode_parse_atts( $match[0] );
				if ( '' !== trim( $value ) && ( ! isset( $parsed['level'] ) || '' === trim( $parsed['level'] ) ) ) {
					return false;
				}
			}
			return true;
		}
	}
}

/**
 * Safely render raw shortcode content with the effective Core/Premium Membership owner.
 * Direct native do_shortcode($raw) bypasses this guarantee; this API does not render blocks.
 *
 * @param string $content Raw shortcode content.
 * @return string Native shortcode output after Membership pre-authorization.
 */
if ( ! function_exists( 'yoaa_render_membership_content' ) ) {
	function yoaa_render_membership_content( $content ) {
		return do_shortcode( YOAA_Membership_Content_Renderer::preauthorize_content( (string) $content ) );
	}
}

YOAA_WC_Advanced_Accounts_Membership_Shortcodes_Free::init();
