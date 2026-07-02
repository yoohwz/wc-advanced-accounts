<?php

if (!defined('ABSPATH')) {
	exit;
}

class YOAA_WC_Advanced_Accounts_Email_Registration_Disabled {
    public function __construct() {
        if (get_option('yoaa_wc_disable_email_on_registration') === 'yes') {
            add_action('wp_enqueue_scripts', [$this, 'enqueue_email_disabled_script']);
			add_action('woocommerce_created_customer', [$this, 'mark_temporary_email_in_user_meta']);
			add_action('wp_enqueue_scripts', [$this, 'enqueue_clear_email_field_script']);
            add_action('woocommerce_save_account_details', [$this, 'remove_temporary_email_if_updated'], 10, 1);
            add_filter('woocommerce_billing_fields', [$this, 'make_email_optional_in_account']);
			add_filter('woocommerce_checkout_fields', [$this, 'make_email_optional_in_checkout']);
            add_action('woocommerce_checkout_order_processed', [$this, 'update_account_email_on_checkout']);
            add_action('woocommerce_customer_save_address', [$this, 'update_account_email_on_billing_save'], 10, 2);
			add_action('wp_enqueue_scripts', [$this, 'add_inline_checkout_email_script']);
            add_action('wp_enqueue_scripts', [$this, 'hide_temporary_email_on_order_pages']);
            add_filter('wp_mail', [$this, 'prevent_emails_to_temporary_addresses'], 10, 1);
		}
    }

    public function enqueue_email_disabled_script() {
        if (!class_exists('WooCommerce') || (!is_user_logged_in() && (is_account_page() || is_checkout()))) {
            wp_enqueue_script('yoaa-email-disabled', plugin_dir_url(__FILE__) . '../../../js/email-registration-disabled.js', array('jquery'), '1.1', true);
    
            $site_url = get_site_url();
            $parsed_url = wp_parse_url($site_url);
            $site_domain = isset($parsed_url['host']) ? $parsed_url['host'] : '';
    
            wp_localize_script('yoaa-email-disabled', 'siteData', array(
                'siteDomain' => $site_domain,
            ));
        }
    }    

    public function mark_temporary_email_in_user_meta($user_id) {
        $user_info = get_userdata($user_id);
        $temporary_email = $user_info->user_email;
        
        $site_url = get_site_url();
        $parsed_url = wp_parse_url($site_url);
        $site_domain = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        
        $temp_email_pattern = '@temp-email-' . $site_domain;
        
        if (strpos($temporary_email, $temp_email_pattern) !== false) {
            update_user_meta($user_id, 'temporary_email', $temporary_email);
        }
    }    

    public function remove_temporary_email_if_updated($user_id) {
        // Verify nonce to ensure the request is legitimate
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'save_account_details')) {
            return; // Exit if nonce verification fails
        }
    
        // Process account email safely
        $new_email = isset($_POST['account_email']) ? sanitize_email(wp_unslash($_POST['account_email'])) : '';
        $temporary_email = get_user_meta($user_id, 'temporary_email', true);
    
        // Remove temporary email if it has been updated
        if ($temporary_email && $new_email !== $temporary_email) {
            delete_user_meta($user_id, 'temporary_email');
            update_user_meta($user_id, 'billing_email', $new_email);
        }
    }

    public function make_email_optional_in_account($address_fields) {
        if (isset($address_fields['billing_email'])) {
            $address_fields['billing_email']['required'] = false;
        }
        return $address_fields;
    }

	public function make_email_optional_in_checkout($fields) {
        if (isset($fields['billing']['billing_email'])) {
            $fields['billing']['billing_email']['required'] = false;
        }
        return $fields;
    }

    public function update_account_email_on_checkout($order_id) {
        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();
    
        if ($user_id) {
            $temporary_email = get_user_meta($user_id, 'temporary_email', true);
            $billing_email = $order->get_billing_email();
    
            if ($temporary_email && $billing_email !== $temporary_email) {
                if (!email_exists($billing_email)) {
                    $update_result = wp_update_user(array(
                        'ID' => $user_id,
                        'user_email' => $billing_email,
                    ));
    
                    if (!is_wp_error($update_result)) {
                        update_user_meta($user_id, 'billing_email', $billing_email);
                        delete_user_meta($user_id, 'temporary_email');
                    }
                }
            }
        }
    }

    public function update_account_email_on_billing_save($user_id, $load_address) {
        if ($load_address === 'billing') {
            $temporary_email = get_user_meta($user_id, 'temporary_email', true);
            $billing_email = get_user_meta($user_id, 'billing_email', true);
    
            if ($temporary_email && $billing_email !== $temporary_email) {
                if (!email_exists($billing_email)) {
                    $update_result = wp_update_user(array(
                        'ID' => $user_id,
                        'user_email' => $billing_email,
                    ));
    
                    if (!is_wp_error($update_result)) {
                        delete_user_meta($user_id, 'temporary_email');
                    }
                }
            }
        }
    }
    
    public function add_inline_checkout_email_script() {
        if (!class_exists('WooCommerce') || ((is_checkout() || (is_account_page())) && is_user_logged_in())) {
            $user_id = get_current_user_id();
            $temporary_email = get_user_meta($user_id, 'temporary_email', true);
    
            if ($temporary_email) {
                wp_enqueue_script('jquery');
                $inline_script = "
                    document.addEventListener('DOMContentLoaded', function() {
                        var emailField = document.getElementById('billing_email');
                        if (emailField) {
                            emailField.value = '';
                        }
                    });
                ";
                wp_add_inline_script('jquery', $inline_script);
            }
        }
    }

    public function enqueue_clear_email_field_script() {
        if (!class_exists('WooCommerce') || (is_account_page() && is_user_logged_in())) {
            $user_id = get_current_user_id();
            $temporary_email = get_user_meta($user_id, 'temporary_email', true);

            if ($temporary_email) {
                $inline_script = "
                    document.addEventListener('DOMContentLoaded', function() {
                        let emailField = document.getElementById('account_email');
                        if (emailField) {
                            let originalEmail = emailField.value;
                            let hiddenEmailInput = document.createElement('input');
                            hiddenEmailInput.type = 'hidden';
                            hiddenEmailInput.name = 'original_account_email';
                            hiddenEmailInput.value = originalEmail;
                            emailField.form.appendChild(hiddenEmailInput);

                            emailField.value = '';

                            emailField.form.addEventListener('submit', function() {
                                if (emailField.value === '') {
                                    emailField.value = originalEmail;
                                }
                            });

                            let requiredMark = emailField.closest('.woocommerce-form-row').querySelector('label .required');
                            if (requiredMark) {
                                requiredMark.style.display = 'none';
                            }
                        }
                    });
                ";
                wp_add_inline_script('jquery', $inline_script);
            }
        }
    }

    public function hide_temporary_email_on_order_pages() {
        // bail if WooCommerce isn’t present
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }

        // grab the dynamic endpoint slugs
        $received_endpoint  = get_option( 'woocommerce_checkout_order_received_endpoint', 'order-received' );
        $view_order_endpoint = get_option( 'woocommerce_myaccount_view_order_endpoint', 'view-order' );

        // only proceed if we’re on one of those endpoints
        if ( ! ( is_order_received_page() || is_wc_endpoint_url( $view_order_endpoint ) ) ) {
            return;
        }

        // get the current order ID from the right query var
        global $wp;
        $order_id = null;

        if ( is_order_received_page() ) {
            $order_id = isset( $wp->query_vars[ $received_endpoint ] )
                ? intval( $wp->query_vars[ $received_endpoint ] )
                : null;
        } elseif ( is_wc_endpoint_url( $view_order_endpoint ) ) {
            $order_id = get_query_var( $view_order_endpoint )
                ? intval( get_query_var( $view_order_endpoint ) )
                : null;
        }

        if ( ! $order_id ) {
            return;
        }

        // load the order and check billing email
        if ( $order = wc_get_order( $order_id ) ) {
            $site_url           = get_site_url();
            $parsed_url         = wp_parse_url( $site_url );
            $site_domain        = isset( $parsed_url['host'] ) ? $parsed_url['host'] : '';
            $temp_email_pattern = '@temp-email-' . $site_domain;

            $billing_email = $order->get_billing_email();

            if ( strpos( $billing_email, $temp_email_pattern ) !== false ) {
                // hide the email elements
                $inline_css = "
                    .woocommerce-customer-details--email, 
                    .woocommerce-order-overview__email { 
                        display: none !important; 
                    }
                ";

                wp_register_style( 'yoaa-wc-order-hide-email', false, [], '1.0' );
                wp_enqueue_style( 'yoaa-wc-order-hide-email' );
                wp_add_inline_style( 'yoaa-wc-order-hide-email', $inline_css );
            }
        }
    }
        
    public function prevent_emails_to_temporary_addresses($args) {
        $site_url = get_site_url();
        $parsed_url = wp_parse_url($site_url);
        $site_domain = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $temp_email_pattern = '@temp-email-' . $site_domain;
    
        // If $args['to'] is an array, iterate through it.
        if (isset($args['to']) && is_array($args['to'])) {
            foreach ($args['to'] as $recipient) {
                if (strpos($recipient, $temp_email_pattern) !== false) {
                    return false;
                }
            }
        } elseif (isset($args['to']) && strpos($args['to'], $temp_email_pattern) !== false) {
            // If $args['to'] is a string.
            return false;
        }
    
        return $args;
    }    
}

new YOAA_WC_Advanced_Accounts_Email_Registration_Disabled();
