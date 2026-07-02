<?php

if (!defined('ABSPATH')) {
	exit;
}

class YOAA_WC_Advanced_Accounts_Frontend {

    public function __construct() {
        // 1) Override the endpoints’ slugs
        add_filter( 'woocommerce_get_query_vars',   [ __CLASS__, 'customize_query_vars' ], 20, 1 );
        // 2) Rename, reorder & hide items in the My Account nav
        add_filter( 'woocommerce_account_menu_items', [ __CLASS__, 'customize_menu_items' ], 99 );
        add_action( 'wp_enqueue_scripts',           [ $this,     'enqueue_scripts' ] );
    }

    /**
     * 1) Replace WooCommerce’s default query-vars with your saved slugs.
     */
    public static function customize_query_vars( $vars ) {
        $slugs = (array) get_option( 'yoaa_account_endpoints_slugs', [] );
        foreach ( $slugs as $key => $slug ) {
            if ( $slug ) {
                $vars[ $key ] = $slug;
            }
        }
        return $vars;
    }

    /**
     * 2) Apply saved order, labels & visibility to the front-end menu.
     */
    public static function customize_menu_items( $items ) {
        $order   = (array) get_option( 'yoaa_account_endpoints_order',   array_keys( $items ) );
        $titles  = (array) get_option( 'yoaa_account_endpoints_titles',  [] );
        $visible = (array) get_option( 'yoaa_account_endpoints_visible', [] );

        // hide
        foreach ( $items as $endpoint => $label ) {
            if ( isset( $visible[ $endpoint ] ) && ! $visible[ $endpoint ] ) {
                unset( $items[ $endpoint ] );
            }
        }
        // rename
        foreach ( $items as $endpoint => &$label ) {
            if ( ! empty( $titles[ $endpoint ] ) ) {
                $label = sanitize_text_field( $titles[ $endpoint ] );
            }
        }
        unset( $label );
        // reorder
        $new = [];
        foreach ( $order as $endpoint ) {
            if ( isset( $items[ $endpoint ] ) ) {
                $new[ $endpoint ] = $items[ $endpoint ];
                unset( $items[ $endpoint ] );
            }
        }
        // append leftovers
        foreach ( $items as $endpoint => $label ) {
            $new[ $endpoint ] = $label;
        }

        return $new;
    }

    /**
     * 3) Load CSS + FontAwesome and inject per-endpoint ::before rules.
     */
    public function enqueue_scripts() {
        // 1) WooCommerce must be loaded
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        // 2) Only proceed on Account, Order Received or Checkout
        $on_account_page        = function_exists( 'is_account_page' ) && is_account_page();
        $on_order_received_page = function_exists( 'is_order_received_page' ) && is_order_received_page();
        $on_checkout_page       = function_exists( 'is_checkout' ) && is_checkout();

        if ( ! ( $on_account_page || $on_order_received_page || $on_checkout_page ) ) {
            return;
        }

        // a) Font Awesome core + solid FROM CDN (instead of self-hosted)
        wp_enqueue_style(
            'yoaa-fa-core',
            plugins_url( '../../font/fontawesome/css/fontawesome.min.css', __FILE__ ),
            [], '6.7.2'
        );
        wp_enqueue_style(
            'yoaa-fa-solid',
            plugins_url( '../../font/fontawesome/css/solid.min.css', __FILE__ ),
            [ 'yoaa-fa-core' ], '6.7.2'
        );

        // b) Your front-end stylesheet
        wp_enqueue_style(
            'yoaa-frontend-css',
            plugins_url( '../../css/frontend.css', __FILE__ ),
            [ 'yoaa-fa-solid' ],
            '1.2'
        );

        // c) Build and inject the icon rules
        $raw_icons = (array) get_option( 'yoaa_account_endpoints_icons', [] );
        if ( empty( $raw_icons ) ) {
            return;
        }

        $position = get_option( 'yoaa_account_endpoint_icon_position', 'left' );

        // Use the trimmed solid-only metadata (name => unicode)
        $meta = plugin_dir_path( __FILE__ ) . '../../font/fontawesome/metadata/icons-solid-list.json';
        if ( ! file_exists( $meta ) ) {
            return;
        }

        $solid_map = json_decode( file_get_contents( $meta ), true );
        if ( ! is_array( $solid_map ) ) {
            return;
        }

        $css = '';
        foreach ( $raw_icons as $endpoint => $icon_class ) {
            // Expect something like "fa-solid fa-house" or "fas fa-user"
            if ( ! preg_match( '/fa[srb]? fa-([\w-]+)/', $icon_class, $m ) ) {
                continue;
            }

            $name = $m[1]; // e.g. "house"
            if ( empty( $solid_map[ $name ] ) ) {
                continue;
            }

            $unicode  = $solid_map[ $name ]; // e.g. "f015"
            $selector = ".woocommerce-MyAccount-navigation-link--{$endpoint} > a::before";

            $css .= "{$selector}{"
                . "font-family:'Font Awesome 6 Free' !important;"
                . "font-weight:900 !important;"
                . "content:\"\\{$unicode}\" !important;";

            // add spacing/float based on setting
            if ( 'right' === $position ) {
                $css .= "margin-left:.5em !important; margin-right:0 !important; float:right !important;";
            } else {
                $css .= "margin-left:0 !important; margin-right:.5em !important; float:none !important;";
            }

            $css .= "}\n";
        }

        if ( $css ) {
            wp_add_inline_style( 'yoaa-frontend-css', $css );
        }
    }
}

new YOAA_WC_Advanced_Accounts_Frontend();
