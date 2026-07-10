<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'YOAA_Phone_Username_Helper' ) ) {

final class YOAA_Phone_Username_Helper {

	const META_USERNAME = '_yoaa_phone_username';
	const META_E164     = '_yoaa_phone_e164';
	const META_LOCAL    = '_yoaa_phone_local';
	const META_DIAL     = '_yoaa_phone_dial_code';
	const META_ALIAS    = '_yoaa_phone_username_alias';

	public static function should_skip_country_code() {
		$allowed_countries_option = get_option( 'woocommerce_allowed_countries', 'all' );
		$specific_countries       = get_option( 'woocommerce_specific_allowed_countries', [] );

		return ( 'specific' === $allowed_countries_option && is_array( $specific_countries ) && count( $specific_countries ) === 1 );
	}

	public static function get_single_allowed_country() {
		$allowed_countries_option = get_option( 'woocommerce_allowed_countries', 'all' );
		$specific_countries       = get_option( 'woocommerce_specific_allowed_countries', [] );

		if ( 'specific' !== $allowed_countries_option || ! is_array( $specific_countries ) || 1 !== count( $specific_countries ) ) {
			return '';
		}

		$country = reset( $specific_countries );

		return is_string( $country ) ? strtoupper( trim( $country ) ) : '';
	}

	public static function get_country_calling_code( $country ) {
		$country = strtoupper( trim( (string) $country ) );
		if ( '' === $country || ! function_exists( 'WC' ) || ! WC() || empty( WC()->countries ) ) {
			return '';
		}

		return self::digits( WC()->countries->get_country_calling_code( $country ) );
	}

	public static function get_default_dial_code() {
		$country = self::get_single_allowed_country();

		return '' !== $country ? self::get_country_calling_code( $country ) : '';
	}

	public static function build_username( $phone, $dial_code = '', $country = '' ) {
		$parsed = self::parse_phone( $phone, $dial_code, $country );

		return (string) ( $parsed['username'] ?? '' );
	}

	public static function parse_phone( $phone, $dial_code = '', $country = '' ) {
		$raw          = trim( (string) $phone );
		$provided_dial = self::digits( $dial_code );
		$country      = strtoupper( trim( (string) $country ) );
		$country_dial = '' !== $country ? self::get_country_calling_code( $country ) : '';
		$default_dial = self::get_default_dial_code();
		$skip_country = self::should_skip_country_code();

		$dial  = $provided_dial;
		$local = '';

		if ( '' === $dial && '' !== $country_dial ) {
			$dial = $country_dial;
		}

		if ( '' === $dial && $skip_country && '' !== $default_dial ) {
			$dial = $default_dial;
		}

		if ( '' === $raw ) {
			return self::parsed_result( '', '', '', false );
		}

		if ( false !== strpos( $raw, '-' ) ) {
			$parts     = explode( '-', $raw, 2 );
			$part_dial = self::digits( $parts[0] ?? '' );
			$part_local = self::digits( $parts[1] ?? '' );

			if ( '' !== $part_dial ) {
				$dial = $part_dial;
			}

			$local = $part_local;
		} elseif ( 0 === strpos( $raw, '+' ) || 0 === strpos( $raw, '00' ) ) {
			$digits = self::digits( $raw );
			if ( 0 === strpos( $digits, '00' ) ) {
				$digits = substr( $digits, 2 );
			}

			$from_international = self::extract_dial_local_from_digits( $digits );
			if ( '' !== $from_international['dial'] ) {
				$dial  = $from_international['dial'];
				$local = $from_international['local'];
			} else {
				$local = $digits;
			}
		} else {
			$digits = self::digits( $raw );

			if ( '' !== $dial && 0 === strpos( $digits, $dial ) && strlen( $digits ) > strlen( $dial ) + 5 ) {
				$local = substr( $digits, strlen( $dial ) );
			} else {
				$from_international = self::extract_dial_local_from_digits( $digits );
				if ( ! $skip_country && '' !== $from_international['dial'] ) {
					$dial  = $from_international['dial'];
					$local = $from_international['local'];
				} else {
					$local = $digits;
				}
			}
		}

		$local = ltrim( self::digits( $local ), '0' );
		$dial  = self::digits( $dial );

		return self::parsed_result( $raw, $dial, $local, $skip_country );
	}

	public static function get_username_candidates( $identifier ) {
		$identifier = trim( (string) $identifier );
		if ( '' === $identifier || is_email( $identifier ) ) {
			return [];
		}

		$candidates = [];
		self::add_candidate( $candidates, sanitize_user( $identifier, true ) );
		self::add_candidate( $candidates, $identifier );

		$parsed = self::parse_phone( $identifier );
		self::add_candidate( $candidates, $parsed['username'] ?? '' );

		if ( ! empty( $parsed['dial'] ) && ! empty( $parsed['local'] ) ) {
			self::add_candidate( $candidates, $parsed['dial'] . '-' . $parsed['local'] );
			self::add_candidate( $candidates, $parsed['local'] );
			self::add_candidate( $candidates, $parsed['dial'] . '-0' . $parsed['local'] );
			self::add_candidate( $candidates, '0' . $parsed['local'] );
		}

		$digits = self::digits( $identifier );
		if ( '' !== $digits ) {
			self::add_candidate( $candidates, ltrim( $digits, '0' ) );

			$from_international = self::extract_dial_local_from_digits( $digits );
			if ( '' !== $from_international['dial'] && '' !== $from_international['local'] ) {
				self::add_candidate( $candidates, $from_international['dial'] . '-' . $from_international['local'] );
				self::add_candidate( $candidates, $from_international['dial'] . '-0' . $from_international['local'] );
				self::add_candidate( $candidates, $from_international['local'] );
				self::add_candidate( $candidates, '0' . $from_international['local'] );
			}

			$default_dial = self::get_default_dial_code();
			if ( '' !== $default_dial ) {
				$local = ltrim( $digits, '0' );
				self::add_candidate( $candidates, $default_dial . '-' . $local );
				self::add_candidate( $candidates, $local );
			}
		}

		return array_values( array_unique( array_filter( $candidates ) ) );
	}

	public static function find_user_by_identifier( $identifier ) {
		$candidates = self::get_username_candidates( $identifier );

		foreach ( $candidates as $candidate ) {
			$user = get_user_by( 'login', $candidate );
			if ( $user ) {
				return $user;
			}
		}

		foreach ( [ self::META_USERNAME, self::META_ALIAS, '_yoaa_old_username_before_phone_migration' ] as $meta_key ) {
			foreach ( $candidates as $candidate ) {
				$user = self::find_user_by_meta( $meta_key, $candidate );
				if ( $user ) {
					return $user;
				}
			}
		}

		$parsed = self::parse_phone( $identifier );
		if ( ! empty( $parsed['e164'] ) ) {
			$user = self::find_user_by_meta( self::META_E164, $parsed['e164'] );
			if ( $user ) {
				return $user;
			}
		}

		return false;
	}

	public static function add_username_alias( $user_id, $alias ) {
		$user_id = absint( $user_id );
		$alias   = sanitize_user( (string) $alias, true );

		if ( $user_id <= 0 || '' === $alias ) {
			return;
		}

		$user = get_userdata( $user_id );
		if ( $user && $alias === $user->user_login ) {
			return;
		}

		$aliases = get_user_meta( $user_id, self::META_ALIAS, false );
		if ( in_array( $alias, array_map( 'strval', (array) $aliases ), true ) ) {
			return;
		}

		add_user_meta( $user_id, self::META_ALIAS, $alias, false );
	}

	public static function sync_user_phone_meta( $user_id, $phone = '', $dial_code = '', $country = '' ) {
		$user_id = absint( $user_id );
		if ( $user_id <= 0 ) {
			return;
		}

		if ( '' === trim( (string) $phone ) ) {
			$user = get_userdata( $user_id );
			$phone = $user ? $user->user_login : '';
		}

		$parsed = self::parse_phone( $phone, $dial_code, $country );

		if ( ! empty( $parsed['username'] ) ) {
			update_user_meta( $user_id, self::META_USERNAME, $parsed['username'] );
		}

		if ( ! empty( $parsed['e164'] ) ) {
			update_user_meta( $user_id, self::META_E164, $parsed['e164'] );
		}

		if ( ! empty( $parsed['local'] ) ) {
			update_user_meta( $user_id, self::META_LOCAL, $parsed['local'] );
		}

		if ( ! empty( $parsed['dial'] ) ) {
			update_user_meta( $user_id, self::META_DIAL, $parsed['dial'] );
		}

		$raw_alias = sanitize_user( (string) $phone, true );
		if ( '' !== $raw_alias && $raw_alias !== (string) ( $parsed['username'] ?? '' ) ) {
			self::add_username_alias( $user_id, $raw_alias );
		}
	}

	public static function get_user_sms_phone( $user, $fallback = '' ) {
		$user_id = $user instanceof WP_User ? (int) $user->ID : absint( $user );

		if ( $user_id > 0 ) {
			$e164 = get_user_meta( $user_id, self::META_E164, true );
			if ( is_string( $e164 ) && '' !== trim( $e164 ) ) {
				return trim( $e164 );
			}

			$user = get_userdata( $user_id );
			if ( $user ) {
				$parsed = self::parse_phone( $user->user_login );
				if ( ! empty( $parsed['e164'] ) ) {
					return $parsed['e164'];
				}
			}

			$country = get_user_meta( $user_id, 'billing_country', true );
			$country = is_string( $country ) ? $country : '';

			foreach ( [ 'account_phone', 'billing_phone', 'shipping_phone', 'phone', 'user_phone', 'customer_phone' ] as $meta_key ) {
				$meta_phone = get_user_meta( $user_id, $meta_key, true );
				$meta_phone = is_string( $meta_phone ) ? trim( $meta_phone ) : '';

				if ( '' === $meta_phone ) {
					continue;
				}

				$parsed = self::parse_phone( $meta_phone, '', $country );
				if ( ! empty( $parsed['e164'] ) ) {
					return $parsed['e164'];
				}
			}
		}

		$parsed = self::parse_phone( $fallback );
		if ( ! empty( $parsed['e164'] ) ) {
			return $parsed['e164'];
		}

		return class_exists( 'YOAA_Verification_Helper' ) ? YOAA_Verification_Helper::normalize_phone( $fallback ) : trim( (string) $fallback );
	}

	private static function parsed_result( $raw, $dial, $local, $skip_country ) {
		$username = '';
		$e164     = '';

		if ( '' !== $local ) {
			$username = $skip_country ? $local : ( '' !== $dial ? $dial . '-' . $local : '' );
			$e164     = '' !== $dial ? '+' . $dial . $local : '';
		}

		return [
			'raw'      => (string) $raw,
			'dial'     => (string) $dial,
			'local'    => (string) $local,
			'e164'     => (string) $e164,
			'username' => (string) $username,
		];
	}

	private static function find_user_by_meta( $meta_key, $meta_value ) {
		$meta_value = (string) $meta_value;
		if ( '' === $meta_value ) {
			return false;
		}

		$q = new WP_User_Query(
			[
				'number'     => 2,
				'fields'     => 'all',
				'meta_key'   => $meta_key,
				'meta_value' => $meta_value,
			]
		);

		$users = (array) $q->get_results();

		return 1 === count( $users ) ? $users[0] : false;
	}

	private static function extract_dial_local_from_digits( $digits ) {
		$digits = self::digits( $digits );
		if ( strlen( $digits ) < 7 ) {
			return [ 'dial' => '', 'local' => '' ];
		}

		foreach ( self::get_dial_codes() as $dial ) {
			if ( 0 === strpos( $digits, $dial ) ) {
				$local = ltrim( substr( $digits, strlen( $dial ) ), '0' );
				if ( strlen( $local ) >= 6 && strlen( $local ) <= 15 ) {
					return [
						'dial'  => $dial,
						'local' => $local,
					];
				}
			}
		}

		return [ 'dial' => '', 'local' => '' ];
	}

	private static function get_dial_codes() {
		static $cache = null;

		if ( null !== $cache ) {
			return $cache;
		}

		$dials = [];

		if ( function_exists( 'WC' ) && WC() && ! empty( WC()->countries ) ) {
			$countries = WC()->countries->get_countries();
			if ( is_array( $countries ) ) {
				foreach ( array_keys( $countries ) as $country ) {
					$code = self::get_country_calling_code( $country );
					if ( '' !== $code ) {
						$dials[] = $code;
					}
				}
			}
		}

		$default_dial = self::get_default_dial_code();
		if ( '' !== $default_dial ) {
			$dials[] = $default_dial;
		}

		$dials = array_values( array_unique( array_filter( $dials ) ) );
		usort(
			$dials,
			function( $a, $b ) {
				return strlen( $b ) <=> strlen( $a );
			}
		);

		$cache = $dials;

		return $cache;
	}

	private static function add_candidate( array &$candidates, $candidate ) {
		$candidate = sanitize_user( (string) $candidate, true );
		if ( '' !== $candidate ) {
			$candidates[] = $candidate;
		}
	}

	private static function digits( $value ) {
		return preg_replace( '/\D+/', '', (string) $value );
	}
}

}
