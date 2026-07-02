<?php

if (!defined('ABSPATH')) {
	exit;
}

class YOAA_WC_Advanced_Accounts_Endpoints_Settings {

	public static function init() {
		// render our custom field
		add_action( 'woocommerce_admin_field_endpoints_manager',
			[ __CLASS__, 'render_endpoints_manager' ] 
		);

		// save on “Save changes”
		add_action( 'woocommerce_update_options_account',
			[ __CLASS__, 'save_endpoints_settings' ] 
		);

		// register rewrite endpoints on init
		add_action( 'init',
			[ __CLASS__, 'register_rewrite_endpoints' ] 
		);

		// enqueue our FA & picker only on the Endpoints settings screen
		add_action( 'admin_enqueue_scripts',
			[ __CLASS__, 'enqueue_assets' ]
		);

		add_filter( 'woocommerce_get_settings_advanced', [ __CLASS__, 'yoaa_hide_account_endpoints'], 10, 2 );
	}

	/**
	 * Only on WooCommerce → Settings → Accounts → Endpoints,
	 */
	public static function enqueue_assets( $hook ) {
		if ( 'woocommerce_page_wc-settings' !== $hook ) {
			return;
		}

		$tab     = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$section = filter_input( INPUT_GET, 'section', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		$tab     = is_string( $tab ) ? sanitize_key( $tab ) : '';
		$section = is_string( $section ) ? sanitize_key( $section ) : '';

		if ( 'account' !== $tab || 'endpoints' !== $section ) {
			return;
		}

		wp_enqueue_style(
			'yoaa-fontawesome-core',
			plugin_dir_url( __FILE__ ) . '../../../font/fontawesome/css/fontawesome.min.css',
			array(),
			'6.7.2'
		);

		wp_enqueue_style(
			'yoaa-fontawesome-solid',
			plugin_dir_url( __FILE__ ) . '../../../font/fontawesome/css/solid.min.css',
			array( 'yoaa-fontawesome-core' ),
			'6.7.2'
		);

		wp_register_script(
			'yoaa-endpoints-iconpicker',
			plugin_dir_url( __FILE__ ) . '../../../js/endpoints-iconpicker.js',
			array( 'jquery' ),
			'1.0',
			true
		);

		$meta_file = plugin_dir_path( __FILE__ ) . '../../../font/fontawesome/metadata/icons-solid-list.json';

		$icon_map = array();
		if ( file_exists( $meta_file ) ) {
			$decoded = json_decode( (string) file_get_contents( $meta_file ), true );
			$icon_map = is_array( $decoded ) ? $decoded : array();
		}

		$free_icons = array_keys( $icon_map );

		wp_localize_script( 'yoaa-endpoints-iconpicker', 'YOAA_FA_ICONS', $free_icons );
		wp_enqueue_script( 'yoaa-endpoints-iconpicker' );
	}

	/**
	 * 1) The settings array WooCommerce will consume.
	 */
	public static function get_endpoint_settings() {
		return array(
			array(
				'title' => __( 'Account endpoints', 'wc-advanced-accounts' ),
				'type'  => 'title',
				'id'    => 'yoaa_endpoints_manager',
			),
			array(
				'id'   => 'yoaa_endpoints_manager',
				'type' => 'endpoints_manager',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'yoaa_endpoints_manager',
			),

			array(
				'title' => __( 'Customization', 'wc-advanced-accounts' ),
				'type'  => 'title',
				'id'    => 'yoaa_endpoints_customization',
			),
			array(
				'name' => __('Icon position', 'wc-advanced-accounts'),
				'id' => 'yoaa_account_endpoint_icon_position',
				'type' => 'select',
				'options' => array(
					'left' => __('Left', 'wc-advanced-accounts'),
					'right' => __('Right', 'wc-advanced-accounts'),
				),
				'default' => 'left',
				'desc_tip' => true,
				'description' => __('Choose how the endpoint icon behaves.', 'wc-advanced-accounts'),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'yoaa_endpoints_customization',
			),
		);
	}

	/**
	 * 2) Render the sortable list + inputs.
	 */
	public static function render_endpoints_manager( $field ) {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// 1) Your hard-coded defaults (always show these)
		$defaults = [
			'dashboard'       => __( 'Dashboard',        'wc-advanced-accounts' ),
			'orders'          => __( 'Orders',           'wc-advanced-accounts' ),
			'downloads'       => __( 'Downloads',        'wc-advanced-accounts' ),
			'edit-address'    => __( 'Addresses',        'wc-advanced-accounts' ),
			'payment-methods' => __( 'Payment methods',  'wc-advanced-accounts' ),
			'edit-account'    => __( 'Account details',  'wc-advanced-accounts' ),
			'customer-logout' => __( 'Logout',           'wc-advanced-accounts' ),
		];

		// 1a) Only add My points if Loyalty for WooCommerce is active
		if ( is_plugin_active( 'loyalty-for-woocommerce/loyalty-for-woocommerce.php' ) ) {
			$defaults['my-points'] = __( 'My points', 'wc-advanced-accounts' );
		}

		// 2) Load saved options
		$saved_order        = (array) get_option( 'yoaa_account_endpoints_order',        [] );
		$saved_titles       = (array) get_option( 'yoaa_account_endpoints_titles',       [] );
		$saved_slugs        = (array) get_option( 'yoaa_account_endpoints_slugs',        [] );
		$saved_icons        = (array) get_option( 'yoaa_account_endpoints_icons',        [] );
		$saved_manual_order = (array) get_option( 'yoaa_account_endpoints_manual_order',[] );
		$saved_visible      = (array) get_option( 'yoaa_account_endpoints_visible',     [] );

		$raw_visible = get_option( 'yoaa_account_endpoints_visible', false );

		// 3) Build the master ordered list: saved order + any missing defaults
		$all_keys = array_keys( $defaults );
		$ordered  = array_merge(
			array_intersect( $saved_order, $all_keys ),
			array_diff( $all_keys, $saved_order )
		);

		// 4) Build the items array from your defaults
		$items = [];
		foreach ( $ordered as $key ) {
			$items[ $key ] = $defaults[ $key ];
		}

		$total = count( $items );

		// Add nonce field for saving endpoints settings (submitted with the WC settings form).
		wp_nonce_field( 'yoaa_save_endpoints_settings', 'yoaa_endpoints_nonce' );

		echo '<div class="yoaa-upgrade-panel">';
		echo '<p class="yoaa-upgrade-eyebrow">' . esc_html__( 'Available in Premium', 'wc-advanced-accounts' ) . '</p>';
		echo '<h3>' . esc_html__( 'Need custom My Account tabs?', 'wc-advanced-accounts' ) . '</h3>';
		echo '<p>' . esc_html__( 'Add new tabs and richer endpoint content when the default WooCommerce account menu is not enough.', 'wc-advanced-accounts' ) . '</p>';
		echo '<ul>';
		echo '<li>' . esc_html__( 'Add unlimited custom account endpoints.', 'wc-advanced-accounts' ) . '</li>';
		echo '<li>' . esc_html__( 'Edit endpoint content with custom text or shortcodes.', 'wc-advanced-accounts' ) . '</li>';
		echo '<li>' . esc_html__( 'Upload custom endpoint icons.', 'wc-advanced-accounts' ) . '</li>';
		echo '<li>' . esc_html__( 'Restrict account tabs by role or membership level.', 'wc-advanced-accounts' ) . '</li>';
		echo '</ul>';
		echo '<p><a class="button button-secondary" href="https://yoohw.com/product/woocommerce-advanced-accounts-premium/" target="_blank" rel="noopener noreferrer">' . esc_html__( 'View endpoint features', 'wc-advanced-accounts' ) . '</a></p>';
		echo '</div>';

		// 5) Render the table header
		echo '<table id="yoaa-endpoints-table" class="widefat fixed striped" style="margin-bottom: 20px;">';
		echo '<thead><tr>';
		echo '<th style="width:24px;"></th>';
		echo '<th class="column-label">' . esc_html__( 'Label',         'wc-advanced-accounts' ) . '</th>';
		echo '<th class="column-slug">' . esc_html__( 'Endpoint Slug','wc-advanced-accounts' ) . '</th>';
		echo '<th class="column-icon">' . esc_html__( 'Icon',          'wc-advanced-accounts' ) . '</th>';
		echo '<th class="column-order">' . esc_html__( 'Order',         'wc-advanced-accounts' ) . '</th>';
		echo '<th class="column-visible">' . esc_html__( 'Visible',       'wc-advanced-accounts' ) . '</th>';
		echo '</tr></thead><tbody>';

		// 6) Loop & output every endpoint row
		$i = 0;
		foreach ( $items as $endpoint => $label ) {
			$i++;
			// Value overrides or fallbacks
			$user_label  = $saved_titles[ $endpoint ]       ?? $label;
			$user_slug   = $saved_slugs[ $endpoint ]        ?? $endpoint;
			$user_icon   = $saved_icons[ $endpoint ]        ?? '';
			$user_order  = intval( $saved_manual_order[ $endpoint ] ?? $i );
			if ( $raw_visible === false ) {
				$user_vis = true;
			} else {
				$user_vis = ! empty( $saved_visible[ $endpoint ] );
			}

			// Build the Order <select>
			$order_select = '<select name="endpoints_order_num[' . esc_attr( $endpoint ) . ']" class="endpoint-order-select">';
			for ( $n = 1; $n <= $total; $n++ ) {
				$order_select .= sprintf(
					'<option value="%1$d"%2$s>%1$d %3$s %4$d</option>',
					$n,
					selected( $user_order, $n, false ),
					esc_html__( 'of', 'wc-advanced-accounts' ),
					$total
				);
			}
			$order_select .= '</select>';

			// Allowed tags for the select markup.
			$allowed_select_html = array(
				'select' => array(
					'name'  => true,
					'class' => true,
				),
				'option' => array(
					'value'    => true,
					'selected' => true,
				),
			);

			// Output the row
			printf(
				'<tr data-endpoint="%1$s">
					<td class="sort-col"><span class="dashicons dashicons-move"></span></td>

					<td>
					<input type="text"
							name="endpoints_titles[%1$s]"
							value="%2$s"
							class="widefat" />
					</td>

					<td>
					<input type="text"
							name="endpoints_slugs[%1$s]"
							value="%3$s"
							class="widefat" />
					</td>

					<td>
					<div class="endpoint-icon-container" style="position:relative; display:flex; align-items:center;">
						<input type="text"
							name="endpoints_icons[%1$s]"
							value="%4$s"
							class="widefat endpoint-icon-input"
							placeholder="' . esc_attr__( 'e.g. icon-name', 'wc-advanced-accounts' ) . '" />
						<button type="button"
								class="button endpoint-icon-picker"
								style="margin-left:5px;">
						' . esc_html__( 'Select', 'wc-advanced-accounts' ) . '
						</button>
					</div>
					</td>

					<td>%5$s</td>

					<td>
					<label>
						<input type="checkbox"
							name="endpoints_visible[%1$s]"
							value="1" %6$s />
						' . esc_html__( 'Show', 'wc-advanced-accounts' ) . '
					</label>
					</td>

					<input type="hidden"
						name="endpoints_order[]"
						value="%1$s" />
				</tr>',
				esc_attr( $endpoint ),
				esc_attr( $user_label ),
				esc_attr( $user_slug ),
				esc_attr( $user_icon ),
				wp_kses( $order_select, $allowed_select_html ),
				checked( $user_vis, true, false )
			);
		}

		echo '</tbody></table>';

		// 4) Single picker container, now with search and one‐per‐line layout
		?>
		<div id="yoaa-icon-overlay" style="
			display:none;
			position:fixed;
			top:0; left:0;
			width:100%; height:100%;
			background:rgba(0,0,0,0.1);
			z-index:9998;
		"></div>

		<div id="yoaa-icon-picker" style="
			display:none;
			position:absolute;
			background:#fff;
			border:1px solid #ccc;
			padding:10px;
			z-index:9999;
			box-shadow:0 2px 6px rgba(0,0,0,0.2);
			width:300px;           /* or whatever width you need */
		">
		<input type="search"
				id="yoaa-icon-search"
				placeholder="<?php esc_attr_e('Search icons…','wc-advanced-accounts'); ?>"
				style="
				position:sticky;
				top:0;
				background:#fff;
				z-index:1;
				width:100%;
				margin-bottom:8px;
				padding:4px;
				box-sizing:border-box;
				" />

		<div class="yoaa-icon-list" style="
			max-height:250px;    /* total popup height minus input+padding */
			overflow-y:auto;
		"></div>
		</div>

		<script>
		jQuery(function($){
			// sortable + live order update (same as before)
		var $tbody = $('#yoaa-endpoints-table tbody'),
			total  = $tbody.children('tr').length;

		// Helper to sync hidden inputs + select values
		function updateOrder(){
			$tbody.find('input[name="endpoints_order[]"]').remove();
			$tbody.children('tr').each(function(i){
			var key = $(this).data('endpoint');
			// rebuild hidden
			$('<input>').attr({
				type:  'hidden',
				name:  'endpoints_order[]',
				value: key
			}).appendTo(this);
			// reset the select to match new position
			$(this).find('.endpoint-order-select').val(i + 1);
			});
		}

		// 1) Drag‐and‐drop
		$tbody.sortable({
			handle: '.dashicons-move',
			helper: function(e, ui){
			ui.children().each(function(){ $(this).width($(this).width()); });
			return ui;
			},
			update: updateOrder
		}).disableSelection();

		// 2) Manual select‐change
		$tbody.on('change', '.endpoint-order-select', function(){
			var $select   = $(this),
				newIndex  = parseInt( $select.val(), 10 ) - 1,
				$row      = $select.closest('tr');

			// detach & reinsert
			$row.detach();
			var $rows = $tbody.children('tr');
			if ( newIndex >= $rows.length ) {
			$tbody.append( $row );
			} else {
			$rows.eq( newIndex ).before( $row );
			}

			// sync everything
			updateOrder();
		});

		// initial build of hidden inputs
		updateOrder();
		});
		</script>
		<?php
	}

	/**
	 * 3) Save handler on “Save changes”.
	 */
		public static function save_endpoints_settings() {
			$section = isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : '';
			if ( 'endpoints' !== $section ) {
				return;
			}

			if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
				return;
			}

			// Unslash ONCE; do not access $_POST directly again (silences sniffers).
		$post = array();
		if ( ! empty( $_POST ) && is_array( $_POST ) ) {
			$post = wp_unslash( $_POST );
		}

		// Only run on our form submit.
		if ( empty( $post['yoaa_endpoints_nonce'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( $post['yoaa_endpoints_nonce'] );
		if ( ! wp_verify_nonce( $nonce, 'yoaa_save_endpoints_settings' ) ) {
			return;
		}

		// 1) Persist the drag-order sequence.
		$endpoints_order = array();
		if ( ! empty( $post['endpoints_order'] ) && is_array( $post['endpoints_order'] ) ) {
			$endpoints_order = array_map( 'sanitize_key', $post['endpoints_order'] );
			update_option( 'yoaa_account_endpoints_order', $endpoints_order );
		}

		// 2) Titles.
		if ( ! empty( $post['endpoints_titles'] ) && is_array( $post['endpoints_titles'] ) ) {
			$titles = array_map( 'sanitize_text_field', $post['endpoints_titles'] );
			update_option( 'yoaa_account_endpoints_titles', $titles );
		}

		// 3) Slugs.
		if ( ! empty( $post['endpoints_slugs'] ) && is_array( $post['endpoints_slugs'] ) ) {
			$slugs = array_map( 'sanitize_key', $post['endpoints_slugs'] );
			update_option( 'yoaa_account_endpoints_slugs', $slugs );
		}

		// 4) Icons.
		if ( ! empty( $post['endpoints_icons'] ) && is_array( $post['endpoints_icons'] ) ) {
			$icons = array_map( 'sanitize_text_field', $post['endpoints_icons'] );
			update_option( 'yoaa_account_endpoints_icons', $icons );
		}

		// 5) Manual order (dropdown).
		if ( ! empty( $post['endpoints_order_num'] ) && is_array( $post['endpoints_order_num'] ) ) {
			$manual = array();
			foreach ( $post['endpoints_order_num'] as $key => $val ) {
				$manual[ sanitize_key( (string) $key ) ] = absint( $val );
			}
			update_option( 'yoaa_account_endpoints_manual_order', $manual );
		}

		// 6) Visibility toggles.
		$visible_post = array();
		if ( ! empty( $post['endpoints_visible'] ) && is_array( $post['endpoints_visible'] ) ) {
			// Keys are endpoint IDs; values are typically "1".
			$visible_post = $post['endpoints_visible'];
		}

		if ( ! empty( $endpoints_order ) ) {
			$visible = array();
			foreach ( $endpoints_order as $key ) {
				$k = sanitize_key( $key );
				$visible[ $k ] = isset( $visible_post[ $k ] ) ? 1 : 0;
			}
			update_option( 'yoaa_account_endpoints_visible', $visible );
		}

		flush_rewrite_rules();
	}

	/**
	 * 4) On init, re‐register all of your (possibly renamed) endpoints.
	 */
	public static function register_rewrite_endpoints() {
		// 2a) Base set of endpoints
		$endpoints = [
			'dashboard',
			'orders',
			'downloads',
			'edit-address',
			'payment-methods',
			'edit-account',
			'customer-logout',
		];

		// 2b) Conditionally add My points
		if ( function_exists( 'is_plugin_active' ) || include_once ABSPATH . 'wp-admin/includes/plugin.php' ) {
			if ( is_plugin_active( 'loyalty-for-woocommerce/loyalty-for-woocommerce.php' ) ) {
				$endpoints[] = 'my-points';
			}
		}

		// 3) Pull any custom slugs
		$slugs = (array) get_option( 'yoaa_account_endpoints_slugs', [] );

		// 4) Register each as a Woo rewrite endpoint
		foreach ( $endpoints as $key ) {
			$slug = ! empty( $slugs[ $key ] ) ? $slugs[ $key ] : $key;
			add_rewrite_endpoint( $slug, EP_ROOT | EP_PAGES );
		}
	}

	public static function yoaa_hide_account_endpoints( $settings, $current_section ) {
		// Only on the "Page setup" section (default = empty string)
		if ( $current_section !== '' ) {
			return $settings;
		}

		$filtered   = [];
		$skipping   = false;

		foreach ( $settings as $row ) {
			// start skipping at the Account endpoints title
			if ( ! $skipping
			&& isset( $row['type'], $row['id'] )
			&& $row['type'] === 'title'
			&& $row['id']   === 'account_endpoint_options'
			) {
				$skipping = true;
				continue;
			}

			// stop skipping at its sectionend
			if ( $skipping
			&& isset( $row['type'], $row['id'] )
			&& $row['type'] === 'sectionend'
			&& $row['id']   === 'account_endpoint_options'
			) {
				$skipping = false;
				continue;
			}

			if ( ! $skipping ) {
				$filtered[] = $row;
			}
		}

		return $filtered;
	}
}

YOAA_WC_Advanced_Accounts_Endpoints_Settings::init();
