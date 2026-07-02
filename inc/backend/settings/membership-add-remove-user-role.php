<?php

defined('ABSPATH') || exit;

class YOAA_WC_Advanced_Accounts_Membership_Add_Remove_User_Role_Free {

    /** Option key to track plugin-created roles */
    private $registry_option = 'yoswc_loyalty_created_roles';

    /** Capability flag to mark roles as created/managed by this plugin */
    private $flag_cap = 'yoswc_loyalty_role';

    public function __construct() {
        add_action('admin_init', [$this, 'handle_role_actions']);
        add_action('admin_notices', [$this, 'display_admin_notices']);
    }

    /* ---------- Registry Helpers ---------- */

    private function get_registry() : array {
        $reg = get_option($this->registry_option, []);
        return is_array($reg) ? $reg : [];
    }

    private function save_registry(array $reg) : void {
        update_option($this->registry_option, $reg, false);
    }

    private function add_to_registry(string $slug, string $name) : void {
        $reg = $this->get_registry();

        // If it already exists, don't clobber existing metadata.
        if (! isset($reg[$slug])) {
            $reg[$slug] = [
                'name'       => $name,
                'created_at' => current_time('mysql'),
                'created_by' => get_current_user_id(),
                'version'    => defined('YOAA_WC_ADVANCED_ACCOUNTS_VERSION') ? YOAA_WC_ADVANCED_ACCOUNTS_VERSION : '1.0.0',
            ];
            $this->save_registry($reg);
        }
    }

    private function remove_from_registry(string $slug) : void {
        $reg = $this->get_registry();
        if (isset($reg[$slug])) {
            unset($reg[$slug]);
            $this->save_registry($reg);
        }
    }

    /** Public check: is this role created by our plugin? */
    public function is_plugin_created_role(string $slug) : bool {
        // Prefer capability flag; fall back to registry
        $role = get_role($slug);
        if ($role && $role->has_cap($this->flag_cap)) {
            return true;
        }
        $reg = $this->get_registry();
        return isset($reg[$slug]);
    }

    private function resolve_role_name(string $slug) : string {
        global $wp_roles;
        if (isset($wp_roles->role_names[$slug])) {
            return $wp_roles->role_names[$slug];
        }
        // Fallback: prettify the slug
        $pretty = ucwords(str_replace(['_', '-'], ' ', $slug));
        return $pretty !== '' ? $pretty : $slug;
    }

    /* ---------- Existing logic ---------- */

    public function handle_role_actions() {
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

            if (isset($_POST['membership_add_new_role_submit'], $_POST['new_role_name'], $_POST['new_role_slug'], $_POST['add_new_role_nonce'])) {
                if ( ! current_user_can( 'manage_options' ) ) {
                    wp_die( esc_html__( 'You do not have permission to perform this action.', 'wc-advanced-accounts' ) );
                }

                if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['add_new_role_nonce'])), 'add_new_role_action')) {
                    wp_die(esc_html__('Nonce verification failed. Please try again.', 'wc-advanced-accounts'));
                }
                $this->add_new_role();
            }

            if (isset($_POST['membership_remove_role_submit'], $_POST['role_to_remove'], $_POST['remove_role_nonce'])) {
                if ( ! current_user_can( 'manage_options' ) ) {
                    wp_die( esc_html__( 'You do not have permission to perform this action.', 'wc-advanced-accounts' ) );
                }

                if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['remove_role_nonce'])), 'remove_role_action')) {
                    wp_die(esc_html__('Nonce verification failed. Please try again.', 'wc-advanced-accounts'));
                }
                $this->remove_role();
            }
        }
    }

    private function add_new_role() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to perform this action.', 'wc-advanced-accounts' ) );
        }

        if ( ! isset( $_POST['add_new_role_nonce'] ) ||
            ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['add_new_role_nonce'] ) ), 'add_new_role_action' ) ) {
            wp_die('Security check failed');
        }

        if ( ! isset( $_POST['new_role_name'] ) ) {
            set_transient('yoswc_membership_admin_notice', 'yoswc_error_fields_empty', 30);
            return;
        }

        $new_role_name    = sanitize_text_field( wp_unslash( $_POST['new_role_name'] ) );
        $new_role_slug_in = isset($_POST['new_role_slug']) ? sanitize_text_field( wp_unslash( $_POST['new_role_slug'] ) ) : '';

        if ( $new_role_name === '' ) {
            set_transient('yoswc_membership_admin_notice', 'yoswc_error_fields_empty', 30);
            return;
        }

        // Auto-generate slug from name when empty
        if ( $new_role_slug_in === '' ) {
            $new_role_slug = $this->generate_role_slug_from_name( $new_role_name );
        } else {
            // Normalize provided slug to our underscore style too
            $new_role_slug = $this->generate_role_slug_from_name( $new_role_slug_in );
        }

        if ( get_role( $new_role_slug ) ) {
            set_transient('yoswc_membership_admin_notice', 'yoswc_error_role_exists', 30);
            return;
        }

        $customer_role = get_role('customer');
        if ( $customer_role ) {
            $caps = $customer_role->capabilities;
            if ( $this->flag_cap ) {
                $caps[ $this->flag_cap ] = true;
            }

            $result = add_role( $new_role_slug, $new_role_name, $caps );
            if ( $result instanceof WP_Role ) {
                $this->add_to_registry( $new_role_slug, $new_role_name );
                set_transient('yoswc_membership_admin_notice', 'yoswc_success_role_added', 30);
            } else {
                set_transient('yoswc_membership_admin_notice', 'yoswc_error_add_role_failed', 30);
            }
        } else {
            set_transient('yoswc_membership_admin_notice', 'yoswc_error_customer_role_not_found', 30);
        }
    }

    private function generate_role_slug_from_name( string $name ) : string {
        if ( function_exists( 'remove_accents' ) ) {
            $name = remove_accents( $name );
        }
        $name = strtolower( trim( $name ) );

        // Replace any non [a-z0-9_] with underscore
        $slug = preg_replace( '/[^a-z0-9_]+/', '_', $name );
        // Collapse multiple underscores and trim
        $slug = preg_replace( '/_+/', '_', $slug );
        $slug = trim( $slug, '_' );

        if ( $slug === '' ) {
            $slug = 'loyalty_member';
        }

        $base = $slug;
        $i = 2;
        while ( get_role( $slug ) ) {
            $slug = $base . '_' . $i;
            $i++;
        }

        return $slug;
    }

    private function remove_role() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to perform this action.', 'wc-advanced-accounts' ) );
        }

        if ( ! isset( $_POST['remove_role_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['remove_role_nonce'] ) ), 'remove_role_action' ) ) {
            wp_die('Security check failed');
        }

        if ( ! isset( $_POST['role_to_remove'] ) ) {
            set_transient('yoswc_membership_admin_notice', 'error_role_not_selected', 30);
            return;
        }

        $role_to_remove = sanitize_text_field( wp_unslash( $_POST['role_to_remove'] ) );

        $protected_roles = ['administrator', 'editor', 'author', 'contributor', 'subscriber', 'customer', 'shop_manager', 'translator'];

        if (in_array($role_to_remove, $protected_roles, true)) {
            set_transient('yoswc_membership_admin_notice', 'yoswc_error_protected_role', 30);
            return;
        }

        if (! $this->is_plugin_created_role($role_to_remove)) {
            set_transient('yoswc_membership_admin_notice', 'yoswc_error_not_ours', 30);
            return;
        }

        remove_role($role_to_remove);
        $this->remove_from_registry($role_to_remove);
        set_transient('yoswc_membership_admin_notice', 'yoswc_success_role_removed', 30);
    }

    public function display_add_remove_role() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'wc-advanced-accounts' ) );
        }

        ?>
        <h2>
            <a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=account&section=membership')); ?>">
                <?php esc_html_e('Membership', 'wc-advanced-accounts'); ?>
            </a> &gt; <?php esc_html_e('Add / Remove role', 'wc-advanced-accounts'); ?>
        </h2>

        <form method="post" action="">
            <h3><?php esc_html_e('Add new', 'wc-advanced-accounts'); ?></h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Role name', 'wc-advanced-accounts'); ?></th>
                    <td><input type="text" name="new_role_name" style="width: 240px;" placeholder="<?php esc_attr_e('Silver Member', 'wc-advanced-accounts'); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Role slug', 'wc-advanced-accounts'); ?></th>
                    <td>
                        <input type="text" name="new_role_slug" style="width: 240px;" placeholder="<?php esc_attr_e('silver-member', 'wc-advanced-accounts'); ?>" />
                        <p class="description">
                            <?php esc_html_e('Leave empty to auto-generate from the name.', 'wc-advanced-accounts'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            <?php wp_nonce_field('add_new_role_action', 'add_new_role_nonce'); ?>
            <?php submit_button(__('Add new role', 'wc-advanced-accounts'), 'primary', 'membership_add_new_role_submit'); ?>

            <h3><?php esc_html_e('Remove', 'wc-advanced-accounts'); ?></h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Remove role', 'wc-advanced-accounts'); ?></th>
                    <td>
                        <?php
                        global $wp_roles;
                        $roles = $wp_roles->role_names;
                        $protected_roles = ['administrator', 'editor', 'author', 'contributor', 'subscriber', 'customer', 'shop_manager', 'translator'];

                        // Check if loyalty plugins are active
                        if ( ! function_exists( 'is_plugin_active' ) ) {
                            require_once ABSPATH . 'wp-admin/includes/plugin.php';
                        }

                        $is_loyalty_active = ( is_plugin_active( 'wc-loyalty/wc-loyalty.php' ) && get_option( 'yowcl_license_status' ) === 'activated' ) || is_plugin_active( 'loyalty-for-woocommerce/loyalty-for-woocommerce.php' );

                        $badge_text = $is_loyalty_active
                            ? esc_html__( 'Membership / Loyalty', 'wc-advanced-accounts' )
                            : esc_html__( 'Membership', 'wc-advanced-accounts' );

                        $registry = $this->get_registry();
                        $removable = [];

                        foreach ($roles as $slug => $name) {
                            if (in_array($slug, $protected_roles, true)) {
                                continue;
                            }

                            if ($this->is_plugin_created_role($slug)) {
                                $badge = ' [' . $badge_text . ']';
                                $label = $name . $badge;
                                $removable[$slug] = $label;
                            }
                        }
                        ?>
                        <select name="role_to_remove" style="width: 320px;">
                            <?php if (empty($removable)) : ?>
                                <option value=""><?php esc_html_e('No membership-created roles found', 'wc-advanced-accounts'); ?></option>
                            <?php else : ?>
                                <?php foreach ($removable as $slug => $label) : ?>
                                    <option value="<?php echo esc_attr($slug); ?>">
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Only roles created by the Advanced Accounts plugin are listed here.', 'wc-advanced-accounts'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            <?php wp_nonce_field('remove_role_action', 'remove_role_nonce'); ?>
            <?php submit_button(__('Remove role', 'wc-advanced-accounts'), 'primary', 'membership_remove_role_submit'); ?>
        </form>
        <?php
    }

    public function display_admin_notices() {
        $notice = get_transient('yoswc_membership_admin_notice');

        if ($notice === 'yoswc_error_fields_empty') {
            echo '<div class="notice notice-error"><p>' . esc_html__('Please at least fill in role name fields for adding a role.', 'wc-advanced-accounts') . '</p></div>';
        } elseif ($notice === 'yoswc_success_role_added') {
            echo '<div class="notice notice-success"><p>' . esc_html__('New role added successfully.', 'wc-advanced-accounts') . '</p></div>';
        } elseif ($notice === 'yoswc_error_customer_role_not_found') {
            echo '<div class="notice notice-error"><p>' . esc_html__('Error: The customer role does not exist.', 'wc-advanced-accounts') . '</p></div>';
        } elseif ($notice === 'yoswc_success_role_removed') {
            echo '<div class="notice notice-success"><p>' . esc_html__('Role removed successfully.', 'wc-advanced-accounts') . '</p></div>';
        } elseif ($notice === 'yoswc_error_protected_role') {
            echo '<div class="notice notice-error"><p>' . esc_html__('Failed to remove role. Certain roles cannot be removed.', 'wc-advanced-accounts') . '</p></div>';
        } elseif ($notice === 'yoswc_error_not_ours') {
            echo '<div class="notice notice-error"><p>' . esc_html__('You can only remove roles that were created by the Advanced Accounts plugin.', 'wc-advanced-accounts') . '</p></div>';
        } elseif ($notice === 'yoswc_error_role_exists') {
            echo '<div class="notice notice-error"><p>' . esc_html__('A role with this slug already exists.', 'wc-advanced-accounts') . '</p></div>';
        } elseif ($notice === 'yoswc_error_add_role_failed') {
            echo '<div class="notice notice-error"><p>' . esc_html__('Failed to add the new role. Please try again.', 'wc-advanced-accounts') . '</p></div>';
        }

        delete_transient('yoswc_membership_admin_notice');
    }
}
