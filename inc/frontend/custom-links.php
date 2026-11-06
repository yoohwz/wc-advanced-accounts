<?php
/** Custom navigation links share the existing AAP option format, without content routes. */
defined( 'ABSPATH' ) || exit;

class YOAA_AAC_Custom_Links {
	public static function init() {
		// AAP already owns endpoint navigation, including restrictions on legacy links.
		if ( class_exists( 'YOAA_WC_Advanced_Accounts_Endpoints_Premium' ) ) {
			return;
		}
		add_filter( 'woocommerce_account_menu_items', array( __CLASS__, 'menu_items' ), 90 );
		add_filter( 'woocommerce_get_endpoint_url', array( __CLASS__, 'endpoint_url' ), 10, 4 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	public static function links() {
		$flags = (array) get_option( 'yoaa_account_endpoints_custom_link_enabled', array() );
		$urls = (array) get_option( 'yoaa_account_endpoints_custom_url', array() );
		$links = array();
		foreach ( (array) get_option( 'yoaa_account_endpoints_custom', array() ) as $key ) {
			// Never turn native/extension endpoint keys or former content pages into links.
			if ( ! is_string( $key ) || ! self::valid_key( $key ) || empty( $flags[ $key ] ) || empty( $urls[ $key ] ) || ! is_string( $urls[ $key ] ) ) {
				continue;
			}
			$url = esc_url_raw( $urls[ $key ], array( 'http', 'https' ) );
			if ( $url && preg_match( '~^https?://~i', $url ) ) {
				$links[ $key ] = $url;
			}
		}
		return $links;
	}

	public static function valid_key( $key ) {
		$reserved = array( 'dashboard', 'orders', 'downloads', 'edit-address', 'payment-methods', 'edit-account', 'customer-logout', 'lost-password', 'view-order', 'order-pay', 'order-received', 'add-payment-method', 'delete-payment-method', 'set-default-payment-method', 'my-points' );
		$wp = new WP();
		$vars = function_exists( 'WC' ) && WC() && isset( WC()->query ) ? WC()->query->get_query_vars() : array();
		return is_string( $key ) && strlen( $key ) <= 200 && preg_match( '/^[a-z0-9-]+$/D', $key ) && ! in_array( $key, $reserved, true ) && ! in_array( $key, $wp->public_query_vars, true ) && ! array_key_exists( $key, $vars );
	}

	public static function menu_items( $items ) {
		$titles = (array) get_option( 'yoaa_account_endpoints_titles', array() );
		foreach ( self::links() as $key => $url ) {
			if ( ! isset( $items[ $key ] ) ) {
				$items[ $key ] = isset( $titles[ $key ] ) && is_string( $titles[ $key ] ) ? $titles[ $key ] : $key;
			}
		}
		return $items;
	}

	public static function endpoint_url( $url, $endpoint, $value = '', $permalink = '' ) {
		$links = self::links();
		return isset( $links[ $endpoint ] ) ? $links[ $endpoint ] : $url;
	}

	public static function enqueue() {
		if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || ! is_user_logged_in() ) {
			return;
		}
		$flags = (array) get_option( 'yoaa_account_endpoints_custom_new_tab', array() );
		$links = array();
		foreach ( self::links() as $key => $url ) {
			if ( ! empty( $flags[ $key ] ) ) {
				$links[ $key ] = array( 'url' => $url );
			}
		}
		wp_enqueue_script( 'yoaa-custom-link-navigation', plugins_url( '../../js/custom-link-navigation.js', __FILE__ ), array(), YOAA_WC_ADVANCED_ACCOUNTS_VERSION, true );
		wp_add_inline_script( 'yoaa-custom-link-navigation', 'window.YoAACustomLinkTabs = ' . wp_json_encode( $links ) . ';', 'before' );
	}
}
add_action( 'plugins_loaded', array( 'YOAA_AAC_Custom_Links', 'init' ), 110 );
