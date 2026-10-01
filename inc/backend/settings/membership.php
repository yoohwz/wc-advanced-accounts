<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YOAA_WC_Advanced_Accounts_Membership_Settings {

	/**
	 * Boot.
	 */
	public static function init() {
		add_action(
			'woocommerce_admin_field_yoaa_membership_roles_table',
			array( __CLASS__, 'output_membership_roles_table_field' )
		);

		self::includes();
	}

	/**
	 * Settings array.
	 */
	public static function get_membership_settings() {
		return array(
			array(
				'name' => __( 'Membership roles', 'wc-advanced-accounts' ),
				'type' => 'title',
				'desc' => '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=account&section=membership&subsection=add_remove_role' ) ) . '" class="button button-secondary">' . esc_html__( 'Add new / Remove', 'wc-advanced-accounts' ) . '</a>',
				'id'   => 'yoaa_wc_membership_roles_section',
			),

			'membership_roles' => array(
				'name'              => __( 'Set membership roles', 'wc-advanced-accounts' ),
				'type'              => 'multiselect',
				'desc'              => __( 'Leave this field empty to disable the membership feature. Select one or more roles and save your changes to enable membership.', 'wc-advanced-accounts' ),
				'desc_tip'          => __( 'Select user roles to set those are membership roles.', 'wc-advanced-accounts' ),
				'id'                => 'yoaa_wc_membership_roles',
				'options'           => self::wc_membership_get_user_roles(),
				'custom_attributes' => array(
					'data-placeholder' => __( 'Select user roles', 'wc-advanced-accounts' ),
				),
				'class'             => 'wc-enhanced-select',
				'css'               => 'min-width:300px;',
			),

			array(
				'name' => __( 'Membership rules', 'wc-advanced-accounts' ),
				'type' => 'yoaa_membership_roles_table',
				'id'   => 'yoaa_wc_membership_roles_table',
			),

			array(
				'type'        => 'yoaa_upgrade_panel',
				'title'       => esc_html__( 'Build paid membership experiences', 'wc-advanced-accounts' ),
				'desc'        => esc_html__( 'Extend role-based membership into products, access rules, and member pricing when your store needs a full membership workflow.', 'wc-advanced-accounts' ),
				'features'    => array(
					esc_html__( 'Restrict posts, pages, and WooCommerce products by membership role.', 'wc-advanced-accounts' ),
					esc_html__( 'Create a membership products page for plans and upgrades.', 'wc-advanced-accounts' ),
					esc_html__( 'Allow customers to hold multiple membership roles.', 'wc-advanced-accounts' ),
					esc_html__( 'Offer member-only discounts for products, categories, or tags.', 'wc-advanced-accounts' ),
				),
				'button_text' => esc_html__( 'View membership features', 'wc-advanced-accounts' ),
				'id'          => 'yoaa_membership_upgrade_panel',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'yoaa_wc_membership_roles_section',
			),
		);
	}

	/**
	 * Custom settings field output: yoaa_membership_roles_table
	 */
	public static function output_membership_roles_table_field( $field ) {
		$selected_roles = get_option( 'yoaa_wc_membership_roles', array() );

		if ( ! is_array( $selected_roles ) ) {
			$selected_roles = array();
		}

		$selected_roles = array_values( array_filter( array_map( 'sanitize_key', $selected_roles ) ) );

		echo '<tr valign="top">';
		echo '<th scope="row" class="titledesc">';
		if ( ! empty( $field['name'] ) ) {
			echo '<label>' . esc_html( $field['name'] ) . '</label>';
		}
		echo '</th>';
		echo '<td class="forminp">';

		if ( empty( $selected_roles ) ) {
			echo '<p class="description">' . esc_html__( 'Set at least one membership role to view the available membership shortcode examples.', 'wc-advanced-accounts' ) . '</p>';
			echo '</td></tr>';
			return;
		}

		$all_roles = wp_roles()->roles;

		echo '<style>
			.yoaa-membership-table thead th {
				padding-left: 10px;
				padding-right: 10px !important;
			}
		</style>';

		echo '<table class="widefat striped yoaa-membership-table" style="max-width: 900px;">';
		echo '<thead><tr>';
		echo '<th style="width:25%;">' . esc_html__( 'Membership', 'wc-advanced-accounts' ) . '</th>';
		echo '<th style="width:25%;">' . esc_html__( 'Membership slug', 'wc-advanced-accounts' ) . '</th>';
		echo '<th style="width:50%;">' . esc_html__( 'Shortcode', 'wc-advanced-accounts' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $selected_roles as $role_slug ) {
			$role_name = isset( $all_roles[ $role_slug ]['name'] ) ? $all_roles[ $role_slug ]['name'] : $role_slug;

			echo '<tr>';
			echo '<td><strong>' . esc_html( $role_name ) . '</strong></td>';
			echo '<td><code>' . esc_html( $role_slug ) . '</code></td>';
			echo '<td><pre>[yoaa_membership level="' . esc_html( $role_slug ) . '"]<br /><span style="margin-left:30px;">Content for ' . esc_html( $role_name ) . ' only.</span><br />[/yoaa_membership]</pre></td>';
			echo '</tr>';
		}

		echo '</tbody></table>';

		echo '<br /><p class="description">' . wp_kses_post(
			sprintf(
				// translators: %s: Membership shortcode documentation URL.
				__( 'To learn more about the shortcodes available for different use cases, please refer to <a href="%s" target="_blank">this article</a>.', 'wc-advanced-accounts' ),
				esc_url( 'https://docs.yoohw.com/use-membership-shortcodes/' )
			)
		) . '</p>';

		echo '</td></tr>';
	}

	/**
	 * Get usable roles independently of creator ownership.
	 */
	public static function wc_membership_get_user_roles() {
		// Selection is usage, independent of creator ownership or retirement.
		$all = wp_roles()->roles;
		$editable = apply_filters( 'editable_roles', $all );
		$selected = (array) get_option( 'yoaa_wc_membership_roles', array() );
		$options = array();
		foreach ( $all as $slug => $data ) {
			$hint = ! empty( $data['capabilities']['yoaa_membership_role'] ) || ! empty( $data['capabilities']['yoswc_loyalty_role'] ) || ! empty( $data['capabilities']['yowcl_loyalty_role'] );
			$protected = in_array( $slug, array( 'administrator', 'editor', 'author', 'contributor', 'subscriber', 'customer', 'shop_manager', 'translator' ), true );
			if ( in_array( $slug, $selected, true ) || ( ! $protected && isset( $editable[ $slug ] ) && ( $hint || current_user_can( 'promote_users' ) ) ) ) { $options[ $slug ] = $data['name']; }
		}
		return $options;
	}

	/**
	 * Load settings files.
	 */
	public static function includes() {
		$base = plugin_dir_path( __FILE__ );
		include_once $base . '../actions/membership/class-shortcodes.php';

		// Helper
		include_once $base . '../helpers/login-redirect.php';
	}
}

YOAA_WC_Advanced_Accounts_Membership_Settings::init();
