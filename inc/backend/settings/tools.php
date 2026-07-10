<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YOAA_WC_Advanced_Accounts_Tools {

	const TOOL_ACTION = 'yoaa_migrate_usernames_to_phone';
	const FIELD_TYPE  = 'yoaa_phone_username_migration';

	public static function init() {
		add_action(
			'woocommerce_admin_field_' . self::FIELD_TYPE,
			array( __CLASS__, 'render_phone_username_migration_field' )
		);
	}

	public static function get_tools() {
		return array(
			array(
				'title' => __( 'Tools', 'wc-advanced-accounts' ),
				'type'  => 'title',
				'id'    => 'yoaa_wc_tools_section',
				'desc'  => __( 'Maintenance utilities for Advanced Accounts.', 'wc-advanced-accounts' ),
			),
			array(
				'title' => __( 'Migrate usernames to phone numbers', 'wc-advanced-accounts' ),
				'type'  => self::FIELD_TYPE,
				'id'    => 'yoaa_phone_username_migration',
			),
			array(
				'type'        => 'yoaa_upgrade_panel',
				'title'       => esc_html__( 'Need more account maintenance tools?', 'wc-advanced-accounts' ),
				'desc'        => esc_html__( 'Use extra bulk utilities when you are cleaning up imported customers or managing larger account databases.', 'wc-advanced-accounts' ),
				'features'    => array(
					esc_html__( 'Bulk mark existing users as phone or email verified.', 'wc-advanced-accounts' ),
					esc_html__( 'Run maintenance workflows for stores that import customer accounts from another system.', 'wc-advanced-accounts' ),
					esc_html__( 'Keep verification and account cleanup tasks in one WooCommerce settings area.', 'wc-advanced-accounts' ),
				),
				'button_text' => esc_html__( 'View maintenance tools', 'wc-advanced-accounts' ),
				'id'          => 'yoaa_tools_upgrade_panel',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'yoaa_wc_tools_section_end',
			),
		);
	}

	public static function render_phone_username_migration_field( $field ) {
		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
			echo '<tr valign="top"><th></th><td>';
			echo '<p>' . esc_html__( 'You do not have permission to run this tool.', 'wc-advanced-accounts' ) . '</p>';
			echo '</td></tr>';
			return;
		}

		$did_run          = false;
		$results          = array();
		$dry_run          = true;
		$limit            = 200;
		$offset           = 0;
		$only_customers   = true;
		$skip_if_has_dash = false;
		$roles            = array( 'customer' );

		if ( isset( $_POST['yoaa_tool_action'] ) && self::TOOL_ACTION === sanitize_text_field( wp_unslash( $_POST['yoaa_tool_action'] ) ) ) {
			$did_run = true;

			$nonce = isset( $_POST['yoaa_phone_migration_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['yoaa_phone_migration_nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, self::TOOL_ACTION ) ) {
				$results = array(
					'processed' => 0,
					'updated'   => 0,
					'skipped'   => 0,
					'conflicts' => 0,
					'rows'      => array(
						array(
							'user_id'        => 0,
							'email'          => '',
							'old_username'   => '',
							'phone_source'   => '',
							'detected_phone' => '',
							'new_username'   => '',
							'status'         => 'error',
							'reason'         => __( 'Security check failed. Please try again.', 'wc-advanced-accounts' ),
						),
					),
				);
			} else {
				$dry_run          = isset( $_POST['yoaa_dry_run'] ) ? ( '1' === (string) wp_unslash( $_POST['yoaa_dry_run'] ) ) : true;
				$limit            = isset( $_POST['yoaa_limit'] ) ? absint( $_POST['yoaa_limit'] ) : 200;
				$offset           = isset( $_POST['yoaa_offset'] ) ? absint( $_POST['yoaa_offset'] ) : 0;
				$only_customers   = isset( $_POST['yoaa_only_customers'] ) ? ( '1' === (string) wp_unslash( $_POST['yoaa_only_customers'] ) ) : true;
				$skip_if_has_dash = isset( $_POST['yoaa_skip_if_has_dash'] ) ? ( '1' === (string) wp_unslash( $_POST['yoaa_skip_if_has_dash'] ) ) : false;

				$roles = $only_customers ? array( 'customer' ) : array( 'customer', 'subscriber' );

				if ( isset( $_POST['yoaa_run_next_batch_btn'] ) && '1' === (string) wp_unslash( $_POST['yoaa_run_next_batch_btn'] ) ) {
					if ( empty( $_POST['yoaa_offset'] ) ) {
						$offset += max( 1, $limit );
					}
				}

				$results = self::run_migration(
					array(
						'dry_run'          => $dry_run,
						'limit'            => max( 1, min( 2000, $limit ) ),
						'offset'           => max( 0, $offset ),
						'roles'            => $roles,
						'skip_if_has_dash' => $skip_if_has_dash,
					)
				);
			}
		}

		echo '<tr valign="top">';
		echo '<th scope="row" class="titledesc">' . esc_html( $field['title'] ?? '' ) . '</th>';
		echo '<td class="forminp">';

		$skip_country_code = self::should_skip_country_code();
		$format_label      = $skip_country_code
			? __( 'Local only (store sells in 1 specific country)', 'wc-advanced-accounts' )
			: __( 'Dial-local (use billing country dial code when possible)', 'wc-advanced-accounts' );

		echo '<p class="description">' . esc_html__( 'Convert existing customers usernames to match their phone number (billing phone by default). Always run dry-run first to preview changes and detect conflicts.', 'wc-advanced-accounts' ) . '</p>';
		echo '<p class="description"><strong>' . esc_html__( 'Detected username format:', 'wc-advanced-accounts' ) . '</strong> ' . esc_html( $format_label ) . '</p>';
		echo '<p class="description">' . esc_html__( 'Leading "0" will be removed from the local number when building the new username.', 'wc-advanced-accounts' ) . '</p>';

		wp_nonce_field( self::TOOL_ACTION, 'yoaa_phone_migration_nonce' );
		echo '<input type="hidden" name="yoaa_tool_action" value="' . esc_attr( self::TOOL_ACTION ) . '" />';

		echo '<fieldset id="yoaa-tools-box" style="border:1px solid #ccd0d4;padding:12px;border-radius:6px;max-width:920px;">';

		$dry_run_checked = $did_run ? $dry_run : true;
		echo '<input type="hidden" name="yoaa_dry_run" value="0" />';
		echo '<label style="display:block;margin-bottom:10px;">';
		echo '<input type="checkbox" name="yoaa_dry_run" value="1" ' . checked( $dry_run_checked, true, false ) . ' /> ';
		echo '<strong>' . esc_html__( 'Dry-run', 'wc-advanced-accounts' ) . '</strong> ' . esc_html__( '(preview only, do not update usernames)', 'wc-advanced-accounts' );
		echo '</label>';

		$only_customers_checked = $did_run ? (bool) $only_customers : true;
		echo '<input type="hidden" name="yoaa_only_customers" value="0" />';
		echo '<label style="display:block;margin:0 0 6px;">';
		echo '<input type="checkbox" name="yoaa_only_customers" value="1" ' . checked( $only_customers_checked, true, false ) . ' /> ';
		echo esc_html__( 'Only customers (role: customer)', 'wc-advanced-accounts' );
		echo '</label>';

		$skip_dash_checked = $did_run ? (bool) $skip_if_has_dash : false;
		echo '<input type="hidden" name="yoaa_skip_if_has_dash" value="0" />';
		echo '<label style="display:block;margin:0 0 10px;">';
		echo '<input type="checkbox" name="yoaa_skip_if_has_dash" value="1" ' . checked( $skip_dash_checked, true, false ) . ' /> ';
		echo esc_html__( 'Skip users whose username already contains "-"', 'wc-advanced-accounts' );
		echo '</label>';

		echo '<p style="margin:0 0 6px;">';
		echo '<label>';
		echo esc_html__( 'Limit:', 'wc-advanced-accounts' ) . ' ';
		echo '<input type="number" min="1" max="2000" name="yoaa_limit" value="' . esc_attr( (int) $limit ) . '" style="width:120px;" />';
		echo '</label> ';

		echo '<label>';
		echo esc_html__( 'Offset:', 'wc-advanced-accounts' ) . ' ';
		echo '<input type="number" min="0" name="yoaa_offset" value="' . esc_attr( (int) $offset ) . '" style="width:120px;" />';
		echo '</label>';
		echo '</p>';

		echo '<p class="description" style="max-width:900px;margin:0 0 10px;">';
		echo esc_html__( 'Limit controls how many users are processed in this run. Offset skips that many users before starting. These fields allow processing large user databases in batches to avoid server timeouts.', 'wc-advanced-accounts' );
		echo '</p>';

		echo '<p style="margin:0;">';
		echo '<button type="submit" class="button button-primary" name="yoaa_run_tool_btn" value="1">' . esc_html__( 'Run tool', 'wc-advanced-accounts' ) . '</button>';
		echo '</p>';

		$total_users   = 0;
		$next_offset   = 0;
		$progress_done = 0;
		$percent       = 0;

		if ( $did_run && ! empty( $results ) ) {
			$total_users  = (int) ( $results['total'] ?? 0 );
			$batch_offset = (int) ( $results['batch_offset'] ?? $offset );
			$batch_limit  = (int) ( $results['batch_limit'] ?? $limit );
			$batch_count  = (int) ( $results['processed'] ?? 0 );

			$progress_done = ( $total_users > 0 ) ? min( $batch_offset + $batch_count, $total_users ) : ( $batch_offset + $batch_count );
			$next_offset   = $batch_offset + $batch_limit;
		} else {
			$total_users   = self::count_target_users( $roles );
			$progress_done = min( $offset, $total_users );
			$next_offset   = $offset + $limit;
		}

		if ( $total_users > 0 ) {
			$percent = (int) floor( ( $progress_done / $total_users ) * 100 );
			$percent = max( 0, min( 100, $percent ) );
		}

		echo '<div style="margin-top:12px;max-width:920px;">';
		echo '<p style="margin:0 0 6px;"><strong>' . esc_html__( 'Progress', 'wc-advanced-accounts' ) . ':</strong> ' .
			esc_html( $progress_done ) . ' / ' . esc_html( $total_users ) . ' (' . esc_html( $percent ) . '%)</p>';

		echo '<div style="width:100%;height:14px;background:#e5e5e5;border-radius:999px;overflow:hidden;">';
		echo '<div style="height:14px;width:' . esc_attr( $percent ) . '%;background:#2271b1;"></div>';
		echo '</div>';

		echo '<p style="margin:8px 0 0;color:#646970;">' .
			esc_html__( 'Next offset', 'wc-advanced-accounts' ) . ': <code>' . esc_html( $next_offset ) . '</code>' .
			'</p>';

		if ( $total_users > 0 && $next_offset < $total_users ) {
			echo '<input type="hidden" id="yoaa_next_offset_value" value="' . esc_attr( $next_offset ) . '" />';
			echo '<p style="margin:8px 0 0;">';
			echo '<button type="submit" class="button" name="yoaa_run_next_batch_btn" value="1" id="yoaa_run_next_batch_btn">' .
				esc_html__( 'Run Next Batch', 'wc-advanced-accounts' ) .
				'</button>';
			echo '</p>';
			?>
			<script>
			(function(){
				const btn = document.getElementById('yoaa_run_next_batch_btn');
				if (!btn) return;

				btn.addEventListener('click', function(){
					const next = document.getElementById('yoaa_next_offset_value');
					const offsetInput = document.querySelector('input[name="yoaa_offset"]');
					if (next && offsetInput) {
						offsetInput.value = next.value;
					}
					try { window.onbeforeunload = null; } catch(e) {}
				});
			})();
			</script>
			<?php
		}

		echo '</div>';
		echo '</fieldset>';

		echo '<p style="font-weight:600;color:#d63637;"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Please ensure you back up your database before running this tool.', 'wc-advanced-accounts' ) . '</p>';

		if ( $did_run && ! empty( $results ) ) {
			if ( ! $dry_run && ! empty( $results['updated'] ) ) {
				$updated = (int) $results['updated'];

				echo '<div class="notice notice-success inline" style="margin:10px 0;">';
				echo '<p><strong>' . sprintf(
					/* translators: %d: Number of usernames updated by the migration tool. */
					esc_html__( 'Migration completed successfully. %d user(s) were updated.', 'wc-advanced-accounts' ),
					$updated
				) . '</strong></p>';
				echo '</div>';
			}

			self::render_results( $results, $dry_run );
		}

		?>
		<script>
		(function(){
			const box = document.getElementById('yoaa-tools-box');
			if (!box) return;

			box.querySelectorAll('input, select, textarea').forEach(function(el){
				el.classList.add('yoaa-tool-input');
			});

			box.addEventListener('change', function(e){ e.stopPropagation(); }, true);
			box.addEventListener('input', function(e){ e.stopPropagation(); }, true);

			document.addEventListener('click', function(e){
				if (!e.target) return;
				if (e.target.name === 'yoaa_run_tool_btn' || e.target.name === 'yoaa_run_next_batch_btn') {
					try { window.onbeforeunload = null; } catch(err) {}
				}
			});
		})();
		</script>
		<?php

		echo '</td>';
		echo '</tr>';
	}

	private static function should_skip_country_code() {
		$allowed_countries_option = get_option( 'woocommerce_allowed_countries', 'all' );
		$specific_country         = get_option( 'woocommerce_specific_allowed_countries', array() );

		return ( 'specific' === $allowed_countries_option && is_array( $specific_country ) && 1 === count( $specific_country ) );
	}

	private static function render_results( array $results, $dry_run ) {
		$updated_status = $dry_run ? 'would_update' : 'updated';

		self::render_results_table(
			array(
				'wrap_class'  => 'yoaa-tools-results',
				'table_class' => 'yoaa-results-table',
				'dry_run'     => $dry_run,
				'metrics'     => array(
					array( 'filter' => 'all', 'label' => __( 'Processed', 'wc-advanced-accounts' ), 'count' => (int) ( $results['processed'] ?? 0 ) ),
					array( 'filter' => $updated_status, 'label' => $dry_run ? __( 'Would update', 'wc-advanced-accounts' ) : __( 'Updated', 'wc-advanced-accounts' ), 'count' => (int) ( $results['updated'] ?? 0 ) ),
					array( 'filter' => 'skipped', 'label' => __( 'Skipped', 'wc-advanced-accounts' ), 'count' => (int) ( $results['skipped'] ?? 0 ) ),
					array( 'filter' => 'conflict', 'label' => __( 'Conflicts', 'wc-advanced-accounts' ), 'count' => (int) ( $results['conflicts'] ?? 0 ) ),
				),
				'columns'     => array(
					array( 'key' => 'user_id', 'label' => __( 'User ID', 'wc-advanced-accounts' ), 'class' => 'yoaa-col-id' ),
					array( 'key' => 'email', 'label' => __( 'Email', 'wc-advanced-accounts' ), 'class' => 'yoaa-col-email' ),
					array( 'key' => 'old_username', 'label' => __( 'Old username', 'wc-advanced-accounts' ) ),
					array( 'key' => 'phone_source', 'label' => __( 'Phone source', 'wc-advanced-accounts' ) ),
					array( 'key' => 'detected_phone', 'label' => __( 'Detected phone', 'wc-advanced-accounts' ) ),
					array( 'key' => 'new_username', 'label' => __( 'New username', 'wc-advanced-accounts' ) ),
					array( 'key' => 'status', 'label' => __( 'Status', 'wc-advanced-accounts' ), 'class' => 'yoaa-col-status' ),
					array( 'key' => 'reason', 'label' => __( 'Reason', 'wc-advanced-accounts' ), 'class' => 'yoaa-col-reason' ),
				),
				'rows'        => $results['rows'] ?? array(),
			)
		);
	}

	private static function render_results_table( array $args ) {
		$wrap_class  = sanitize_html_class( $args['wrap_class'] ?? 'yoaa-tools-results' );
		$table_class = sanitize_html_class( $args['table_class'] ?? 'yoaa-results-table' );
		$metrics     = isset( $args['metrics'] ) && is_array( $args['metrics'] ) ? $args['metrics'] : array();
		$columns     = isset( $args['columns'] ) && is_array( $args['columns'] ) ? $args['columns'] : array();
		$rows        = isset( $args['rows'] ) && is_array( $args['rows'] ) ? array_values( $args['rows'] ) : array();
		$dry_run     = ! empty( $args['dry_run'] );

		self::render_results_assets();

		echo '<div class="' . esc_attr( $wrap_class ) . ' yoaa-tool-results" data-all-label="' . esc_attr__( 'All', 'wc-advanced-accounts' ) . '" data-no-results="' . esc_attr__( 'No rows match the current filters.', 'wc-advanced-accounts' ) . '">';
		echo '<div class="yoaa-results-header">';
		echo '<div>';
		echo '<h4>' . esc_html__( 'Results', 'wc-advanced-accounts' ) . '</h4>';
		echo '<p>' . esc_html__( 'Showing the current processed batch. Use pagination, filters, and search to inspect rows without overloading the page.', 'wc-advanced-accounts' ) . '</p>';
		echo '</div>';
		echo '<span class="yoaa-results-mode ' . ( $dry_run ? 'is-dry-run' : 'is-live-run' ) . '">' . esc_html( $dry_run ? __( 'Dry-run', 'wc-advanced-accounts' ) : __( 'Live run', 'wc-advanced-accounts' ) ) . '</span>';
		echo '</div>';

		if ( ! empty( $metrics ) ) {
			echo '<div class="yoaa-results-metrics" role="list">';
			foreach ( $metrics as $index => $metric ) {
				$filter = sanitize_key( $metric['filter'] ?? 'all' );
				$label  = (string) ( $metric['label'] ?? '' );
				$count  = (int) ( $metric['count'] ?? 0 );
				echo '<button type="button" class="yoaa-result-filter' . ( 0 === (int) $index ? ' is-active' : '' ) . '" data-filter="' . esc_attr( $filter ) . '" data-label="' . esc_attr( $label ) . '">';
				echo '<span>' . esc_html( $label ) . '</span>';
				echo '<strong>' . esc_html( $count ) . '</strong>';
				echo '</button>';
			}
			echo '</div>';
		}

		if ( empty( $rows ) || empty( $columns ) ) {
			echo '<div class="yoaa-results-empty">' . esc_html__( 'No rows returned for this run.', 'wc-advanced-accounts' ) . '</div>';
			echo '</div>';
			return;
		}

		echo '<div class="yoaa-results-toolbar">';
		echo '<p class="yoaa-filter-label"></p>';
		echo '<label class="yoaa-result-search-label"><span>' . esc_html__( 'Search', 'wc-advanced-accounts' ) . '</span><input type="search" class="yoaa-result-search" placeholder="' . esc_attr__( 'User ID, email, status, reason...', 'wc-advanced-accounts' ) . '" /></label>';
		echo '<label class="yoaa-result-page-size-label"><span>' . esc_html__( 'Rows per page', 'wc-advanced-accounts' ) . '</span><select class="yoaa-result-page-size">';
		foreach ( array( 25, 50, 100, 200 ) as $per_page ) {
			echo '<option value="' . esc_attr( $per_page ) . '"' . selected( $per_page, 50, false ) . '>' . esc_html( $per_page ) . '</option>';
		}
		echo '</select></label>';
		echo '</div>';

		echo '<div class="yoaa-results-table-wrap">';
		echo '<table class="widefat striped ' . esc_attr( $table_class ) . ' yoaa-results-table">';
		echo '<thead><tr>';
		foreach ( $columns as $column ) {
			$column_class = ! empty( $column['class'] ) ? ' class="' . esc_attr( sanitize_html_class( $column['class'] ) ) . '"' : '';
			echo '<th' . $column_class . '>' . esc_html( $column['label'] ?? '' ) . '</th>';
		}
		echo '</tr></thead><tbody>';

		foreach ( $rows as $row ) {
			$row    = is_array( $row ) ? $row : array();
			$status = sanitize_key( (string) ( $row['status'] ?? 'unknown' ) );
			$status = '' !== $status ? $status : 'unknown';

			$search_values = array();
			foreach ( $columns as $column ) {
				$key             = (string) ( $column['key'] ?? '' );
				$search_values[] = isset( $row[ $key ] ) ? (string) $row[ $key ] : '';
			}

			echo '<tr data-status="' . esc_attr( $status ) . '" data-search="' . esc_attr( strtolower( implode( ' ', $search_values ) ) ) . '">';
			foreach ( $columns as $column ) {
				$key        = (string) ( $column['key'] ?? '' );
				$value      = isset( $row[ $key ] ) ? (string) $row[ $key ] : '';
				$cell_class = ! empty( $column['class'] ) ? ' class="' . esc_attr( sanitize_html_class( $column['class'] ) ) . '"' : '';

				echo '<td' . $cell_class . '>';

				if ( 'user_id' === $key ) {
					$user_id   = absint( $value );
					$edit_link = $user_id > 0 ? admin_url( 'user-edit.php?user_id=' . $user_id ) : '';
					if ( $edit_link ) {
						echo '<a href="' . esc_url( $edit_link ) . '" target="_blank" rel="noopener noreferrer" class="yoaa-user-link">' . esc_html( $user_id ) . '</a>';
					} else {
						echo esc_html( $user_id );
					}
				} elseif ( 'status' === $key ) {
					echo '<span class="yoaa-status-badge yoaa-status-' . esc_attr( $status ) . '">' . esc_html( self::get_result_status_label( $status ) ) . '</span>';
				} else {
					echo esc_html( $value );
				}

				echo '</td>';
			}
			echo '</tr>';
		}

		echo '</tbody></table>';
		echo '</div>';
		echo '<div class="yoaa-results-empty yoaa-results-empty-filter" hidden>' . esc_html__( 'No rows match the current filters.', 'wc-advanced-accounts' ) . '</div>';
		echo '<div class="yoaa-results-pagination">';
		echo '<span class="yoaa-page-range"></span>';
		echo '<span class="yoaa-page-buttons">';
		echo '<button type="button" class="button yoaa-page-prev" aria-label="' . esc_attr__( 'Previous page', 'wc-advanced-accounts' ) . '">&lsaquo;</button>';
		echo '<span class="yoaa-page-current"></span>';
		echo '<button type="button" class="button yoaa-page-next" aria-label="' . esc_attr__( 'Next page', 'wc-advanced-accounts' ) . '">&rsaquo;</button>';
		echo '</span>';
		echo '</div>';
		echo '</div>';
	}

	private static function get_result_status_label( $status ) {
		$status = sanitize_key( (string) $status );

		$labels = array(
			'updated'      => __( 'Updated', 'wc-advanced-accounts' ),
			'would_update' => __( 'Would update', 'wc-advanced-accounts' ),
			'skipped'      => __( 'Skipped', 'wc-advanced-accounts' ),
			'conflict'     => __( 'Conflict', 'wc-advanced-accounts' ),
			'error'        => __( 'Error', 'wc-advanced-accounts' ),
			'unknown'      => __( 'Unknown', 'wc-advanced-accounts' ),
		);

		if ( isset( $labels[ $status ] ) ) {
			return $labels[ $status ];
		}

		return ucwords( str_replace( '_', ' ', $status ) );
	}

	private static function render_results_assets() {
		static $rendered = false;

		if ( $rendered ) {
			return;
		}

		$rendered = true;
		?>
		<style>
		.yoaa-tool-results { margin-top: 16px; max-width: 1200px; border: 1px solid #dcdcde; border-radius: 6px; background: #fff; overflow: hidden; }
		.yoaa-results-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; padding: 14px 16px; border-bottom: 1px solid #dcdcde; background: #f6f7f7; }
		.yoaa-results-header h4 { margin: 0 0 4px; font-size: 14px; line-height: 1.4; }
		.yoaa-results-header p { margin: 0; color: #646970; }
		.yoaa-results-mode, .yoaa-status-badge { display: inline-flex; align-items: center; border-radius: 999px; font-size: 12px; font-weight: 600; line-height: 1.4; white-space: nowrap; }
		.yoaa-results-mode { padding: 4px 9px; border: 1px solid #c3c4c7; background: #fff; color: #3c434a; }
		.yoaa-results-mode.is-dry-run { border-color: #72aee6; color: #0a4b78; }
		.yoaa-results-mode.is-live-run { border-color: #00a32a; color: #006b1b; }
		.yoaa-results-metrics { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 8px; padding: 12px 16px; border-bottom: 1px solid #dcdcde; }
		.yoaa-result-filter { display: flex; align-items: center; justify-content: space-between; gap: 10px; min-height: 44px; padding: 8px 10px; border: 1px solid #dcdcde; border-radius: 5px; background: #fff; color: #2c3338; cursor: pointer; text-align: left; }
		.yoaa-result-filter span { color: #646970; }
		.yoaa-result-filter strong { font-size: 16px; }
		.yoaa-result-filter.is-active { border-color: #2271b1; box-shadow: inset 0 0 0 1px #2271b1; }
		.yoaa-results-toolbar, .yoaa-results-pagination { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 16px; }
		.yoaa-results-toolbar { flex-wrap: wrap; }
		.yoaa-filter-label, .yoaa-page-range, .yoaa-page-current { margin: 0; color: #646970; }
		.yoaa-result-search-label, .yoaa-result-page-size-label { display: inline-flex; align-items: center; gap: 6px; color: #3c434a; }
		.yoaa-result-search { min-width: 260px; }
		.yoaa-results-table-wrap { overflow-x: auto; border-top: 1px solid #dcdcde; border-bottom: 1px solid #dcdcde; }
		.yoaa-results-table { min-width: 980px; border: 0; }
		.yoaa-results-table thead th { position: sticky; top: 0; z-index: 1; padding: 10px !important; background: #f6f7f7; white-space: nowrap; }
		.yoaa-results-table tbody td { padding: 10px; vertical-align: top; word-break: break-word; }
		.yoaa-col-id { width: 76px; }
		.yoaa-col-status { width: 120px; }
		.yoaa-col-reason { min-width: 220px; }
		.yoaa-user-link { font-weight: 600; text-decoration: none; }
		.yoaa-status-badge { padding: 3px 8px; background: #f0f0f1; color: #3c434a; }
		.yoaa-status-updated { background: #edfaef; color: #006b1b; }
		.yoaa-status-would_update { background: #eef6fc; color: #0a4b78; }
		.yoaa-status-skipped { background: #f6f7f7; color: #646970; }
		.yoaa-status-conflict, .yoaa-status-error { background: #fcf0f1; color: #8a2424; }
		.yoaa-results-empty { margin: 12px 16px; padding: 12px; border: 1px dashed #c3c4c7; border-radius: 5px; color: #646970; background: #f6f7f7; }
		.yoaa-page-buttons { display: inline-flex; align-items: center; gap: 8px; }
		.yoaa-page-buttons .button { min-width: 32px; padding: 0 8px; }
		@media (max-width: 782px) { .yoaa-results-header, .yoaa-results-toolbar, .yoaa-results-pagination { align-items: stretch; flex-direction: column; } .yoaa-result-search, .yoaa-result-search-label, .yoaa-result-page-size-label { width: 100%; } }
		</style>
		<script>
		(function(){
			function initResults(wrap) {
				if (!wrap || wrap.dataset.yoaaResultsReady === '1') return;
				wrap.dataset.yoaaResultsReady = '1';
				const table = wrap.querySelector('.yoaa-results-table');
				if (!table) return;
				const rows = Array.prototype.slice.call(table.querySelectorAll('tbody tr'));
				const filterButtons = Array.prototype.slice.call(wrap.querySelectorAll('.yoaa-result-filter'));
				const searchInput = wrap.querySelector('.yoaa-result-search');
				const pageSizeSelect = wrap.querySelector('.yoaa-result-page-size');
				const filterLabel = wrap.querySelector('.yoaa-filter-label');
				const emptyState = wrap.querySelector('.yoaa-results-empty-filter');
				const pagination = wrap.querySelector('.yoaa-results-pagination');
				const rangeLabel = wrap.querySelector('.yoaa-page-range');
				const pageLabel = wrap.querySelector('.yoaa-page-current');
				const prevButton = wrap.querySelector('.yoaa-page-prev');
				const nextButton = wrap.querySelector('.yoaa-page-next');
				let activeFilter = 'all';
				let currentPage = 1;
				function normalize(value) { return String(value || '').toLowerCase(); }
				function getPageSize() { const value = pageSizeSelect ? parseInt(pageSizeSelect.value, 10) : 50; return value > 0 ? value : 50; }
				function getFilteredRows() {
					const query = searchInput ? normalize(searchInput.value).trim() : '';
					return rows.filter(function(row){
						const status = normalize(row.getAttribute('data-status'));
						const haystack = normalize(row.getAttribute('data-search') || row.textContent);
						if (activeFilter !== 'all' && status !== activeFilter) return false;
						return !query || haystack.indexOf(query) !== -1;
					});
				}
				function render() {
					const pageSize = getPageSize();
					const filteredRows = getFilteredRows();
					const totalPages = Math.max(1, Math.ceil(filteredRows.length / pageSize));
					if (currentPage > totalPages) currentPage = totalPages;
					const start = (currentPage - 1) * pageSize;
					const end = Math.min(start + pageSize, filteredRows.length);
					const visibleRows = filteredRows.slice(start, end);
					rows.forEach(function(row){ row.hidden = true; });
					visibleRows.forEach(function(row){ row.hidden = false; });
					if (emptyState) emptyState.hidden = filteredRows.length > 0;
					if (pagination) pagination.hidden = filteredRows.length === 0;
					if (rangeLabel) rangeLabel.textContent = filteredRows.length ? ((start + 1) + '-' + end + ' / ' + filteredRows.length) : '0 / 0';
					if (pageLabel) pageLabel.textContent = currentPage + ' / ' + totalPages;
					if (prevButton) prevButton.disabled = currentPage <= 1;
					if (nextButton) nextButton.disabled = currentPage >= totalPages;
					if (filterLabel) {
						const activeButton = filterButtons.find(function(button){ return normalize(button.getAttribute('data-filter')) === activeFilter; });
						const label = activeButton ? activeButton.getAttribute('data-label') : wrap.getAttribute('data-all-label');
						filterLabel.textContent = label ? (label + ' - ' + filteredRows.length) : '';
					}
				}
				filterButtons.forEach(function(button){
					button.addEventListener('click', function(){
						activeFilter = normalize(button.getAttribute('data-filter')) || 'all';
						currentPage = 1;
						filterButtons.forEach(function(item){ item.classList.toggle('is-active', item === button); });
						render();
					});
				});
				if (searchInput) searchInput.addEventListener('input', function(){ currentPage = 1; render(); });
				if (pageSizeSelect) pageSizeSelect.addEventListener('change', function(){ currentPage = 1; render(); });
				if (prevButton) prevButton.addEventListener('click', function(){ if (currentPage > 1) { currentPage--; render(); } });
				if (nextButton) nextButton.addEventListener('click', function(){ currentPage++; render(); });
				render();
			}
			function initAllResults() { document.querySelectorAll('.yoaa-tool-results').forEach(initResults); }
			if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initAllResults); else initAllResults();
		})();
		</script>
		<?php
	}

	private static function run_migration( array $args ) {
		$defaults = array(
			'dry_run'          => true,
			'limit'            => 200,
			'offset'           => 0,
			'roles'            => array( 'customer' ),
			'skip_if_has_dash' => false,
		);
		$args     = wp_parse_args( $args, $defaults );
		$rows     = array();

		$processed = 0;
		$updated   = 0;
		$skipped   = 0;
		$conflicts = 0;

		$skip_country_code = self::should_skip_country_code();
		$total_users       = self::count_target_users( (array) $args['roles'] );

		$user_query = new WP_User_Query(
			array(
				'number'      => (int) $args['limit'],
				'offset'      => (int) $args['offset'],
				'role__in'    => (array) $args['roles'],
				'fields'      => array( 'ID', 'user_login', 'user_email' ),
				'orderby'     => 'ID',
				'order'       => 'ASC',
				'count_total' => false,
			)
		);

		$users = (array) $user_query->get_results();

		foreach ( $users as $u ) {
			$processed++;
			$old_username = (string) $u->user_login;

			if ( ! empty( $args['skip_if_has_dash'] ) && false !== strpos( $old_username, '-' ) ) {
				$skipped++;
				$rows[] = self::row( $u, $old_username, '', '', '', 'skipped', __( 'Username already contains "-".', 'wc-advanced-accounts' ) );
				continue;
			}

			$phone_data = self::get_user_phone_data( (int) $u->ID );

			if ( empty( $phone_data['raw'] ) ) {
				$skipped++;
				$rows[] = self::row( $u, $old_username, $phone_data['source'], '', '', 'skipped', __( 'No phone found.', 'wc-advanced-accounts' ) );
				continue;
			}

			$raw_phone       = $phone_data['raw'];
			$billing_country = get_user_meta( (int) $u->ID, 'billing_country', true );
			$billing_country = is_string( $billing_country ) ? $billing_country : '';

			if ( class_exists( 'YOAA_Phone_Username_Helper' ) ) {
				$parsed_phone = YOAA_Phone_Username_Helper::parse_phone( $raw_phone, '', $billing_country );
				$normalized   = array(
					'dial'  => (string) ( $parsed_phone['dial'] ?? '' ),
					'local' => (string) ( $parsed_phone['local'] ?? '' ),
				);
				$new_username = (string) ( $parsed_phone['username'] ?? '' );
			} else {
				$normalized = self::normalize_phone( $raw_phone );
			}

			if ( empty( $normalized['local'] ) ) {
				$skipped++;
				$rows[] = self::row( $u, $old_username, $phone_data['source'], $raw_phone, '', 'skipped', __( 'Invalid phone after normalization.', 'wc-advanced-accounts' ) );
				continue;
			}

			if ( ! class_exists( 'YOAA_Phone_Username_Helper' ) ) {
				$local = $normalized['local'];
				$dial  = '';

				$from_phone = self::extract_dial_local_from_international_phone( $raw_phone );

				if ( ! empty( $from_phone['dial'] ) && ! empty( $from_phone['local'] ) ) {
					$local = $from_phone['local'];
				}

				if ( ! $skip_country_code ) {
					if ( ! empty( $from_phone['dial'] ) && ! empty( $from_phone['local'] ) ) {
						$dial  = $from_phone['dial'];
						$local = $from_phone['local'];
					} else {
						$dial = self::get_dial_code_from_billing_country_conf( (int) $u->ID );
					}
				}

				$new_username = self::build_username_by_site_rules( $skip_country_code, $dial, $local );
			}

			if ( empty( $new_username ) ) {
				$skipped++;
				$rows[] = self::row( $u, $old_username, $phone_data['source'], $phone_data['raw'], '', 'skipped', __( 'Could not build new username.', 'wc-advanced-accounts' ) );
				continue;
			}

			if ( $new_username === $old_username ) {
				if ( empty( $args['dry_run'] ) && class_exists( 'YOAA_Phone_Username_Helper' ) ) {
					YOAA_Phone_Username_Helper::sync_user_phone_meta( (int) $u->ID, $raw_phone, '', $billing_country );
				}

				$skipped++;
				$rows[] = self::row( $u, $old_username, $phone_data['source'], $phone_data['raw'], $new_username, 'skipped', __( 'Already matches.', 'wc-advanced-accounts' ) );
				continue;
			}

			$existing      = username_exists( $new_username );
			$existing_user = ( ! $existing && class_exists( 'YOAA_Phone_Username_Helper' ) )
				? YOAA_Phone_Username_Helper::find_user_by_identifier( $new_username )
				: false;
			$existing_id   = $existing_user ? (int) $existing_user->ID : (int) $existing;

			if ( $existing_id && (int) $existing_id !== (int) $u->ID ) {
				$conflicts++;
				$rows[] = self::row( $u, $old_username, $phone_data['source'], $phone_data['raw'], $new_username, 'conflict', __( 'Username already exists.', 'wc-advanced-accounts' ) );
				continue;
			}

			if ( ! empty( $args['dry_run'] ) ) {
				$updated++;
				$rows[] = self::row( $u, $old_username, $phone_data['source'], $phone_data['raw'], $new_username, 'would_update', __( 'Dry-run.', 'wc-advanced-accounts' ) );
				continue;
			}

			update_user_meta( (int) $u->ID, '_yoaa_old_username_before_phone_migration', $old_username );

			if ( class_exists( 'YOAA_Phone_Username_Helper' ) ) {
				YOAA_Phone_Username_Helper::add_username_alias( (int) $u->ID, $old_username );
				YOAA_Phone_Username_Helper::sync_user_phone_meta( (int) $u->ID, $raw_phone, '', $billing_country );
			}

			$result = self::update_user_login_direct( (int) $u->ID, $new_username );

			if ( is_wp_error( $result ) ) {
				$skipped++;
				$rows[] = self::row( $u, $old_username, $phone_data['source'], $phone_data['raw'], $new_username, 'error', $result->get_error_message() );
				continue;
			}

			update_user_meta( (int) $u->ID, 'nickname', $new_username );

			$updated++;
			if ( class_exists( 'YOAA_Phone_Username_Helper' ) ) {
				YOAA_Phone_Username_Helper::sync_user_phone_meta( (int) $u->ID, $new_username, '', $billing_country );
			}
			$rows[] = self::row( $u, $old_username, $phone_data['source'], $phone_data['raw'], $new_username, 'updated', __( 'Updated.', 'wc-advanced-accounts' ) );
		}

		return array(
			'processed'    => $processed,
			'updated'      => $updated,
			'skipped'      => $skipped,
			'conflicts'    => $conflicts,
			'rows'         => $rows,
			'total'        => (int) $total_users,
			'batch_offset' => (int) $args['offset'],
			'batch_limit'  => (int) $args['limit'],
		);
	}

	private static function count_target_users( array $roles ) {
		$q = new WP_User_Query(
			array(
				'number'      => 1,
				'offset'      => 0,
				'role__in'    => $roles,
				'fields'      => 'ID',
				'orderby'     => 'ID',
				'order'       => 'ASC',
				'count_total' => true,
			)
		);

		return (int) $q->get_total();
	}

	private static function row( $u, $old_username, $phone_source, $detected_phone, $new_username, $status, $reason ) {
		return array(
			'user_id'        => (int) $u->ID,
			'email'          => (string) $u->user_email,
			'old_username'   => (string) $old_username,
			'phone_source'   => (string) $phone_source,
			'detected_phone' => (string) $detected_phone,
			'new_username'   => (string) $new_username,
			'status'         => (string) $status,
			'reason'         => (string) $reason,
		);
	}

	private static function get_user_phone_data( $user_id ) {
		$keys = apply_filters(
			'yoaa_phone_migration_meta_keys',
			array(
				'billing_phone',
				'shipping_phone',
				'phone',
				'user_phone',
				'account_phone',
				'customer_phone',
			)
		);

		foreach ( (array) $keys as $k ) {
			$raw = get_user_meta( $user_id, $k, true );
			$raw = is_string( $raw ) ? trim( $raw ) : '';

			if ( '' !== $raw ) {
				return array(
					'source' => $k,
					'raw'    => $raw,
				);
			}
		}

		return array(
			'source' => '',
			'raw'    => '',
		);
	}

	private static function normalize_phone( $raw ) {
		$raw = is_string( $raw ) ? trim( $raw ) : '';
		if ( '' === $raw ) {
			return array( 'dial' => '', 'local' => '' );
		}

		$tmp = preg_replace( '/[^0-9\-\+]/', '', $raw );
		$tmp = ltrim( $tmp, '+' );

		$dial  = '';
		$local = '';

		if ( false !== strpos( $tmp, '-' ) ) {
			$parts = explode( '-', $tmp, 2 );
			$dial  = preg_replace( '/\D/', '', $parts[0] );
			$local = preg_replace( '/\D/', '', $parts[1] );
		} else {
			$local = preg_replace( '/\D/', '', $tmp );
		}

		if ( '' !== $local ) {
			$local = ltrim( $local, '0' );
		}

		if ( '' !== $dial && ( strlen( $dial ) < 1 || strlen( $dial ) > 4 ) ) {
			$dial = '';
		}

		if ( '' !== $local && ( strlen( $local ) < 6 || strlen( $local ) > 15 ) ) {
			$local = '';
		}

		return array(
			'dial'  => $dial,
			'local' => $local,
		);
	}

	private static function get_dial_code_from_billing_country_conf( $user_id ) {
		$country = get_user_meta( $user_id, 'billing_country', true );
		$country = is_string( $country ) ? strtoupper( trim( $country ) ) : '';

		if ( '' === $country ) {
			return '';
		}

		$country_code = self::get_woocommerce_country_calling_code( $country );
		if ( '' !== $country_code ) {
			return $country_code;
		}

		$conf_file = plugin_dir_path( __FILE__ ) . '../actions/data/phone_country_codes.conf';

		return self::get_phone_country_code_from_conf_fallback( $country, $conf_file );
	}

	private static function get_woocommerce_country_calling_code( $country ) {
		$country = strtoupper( trim( (string) $country ) );
		if ( '' === $country || ! function_exists( 'WC' ) || ! WC() || empty( WC()->countries ) ) {
			return '';
		}

		$code = WC()->countries->get_country_calling_code( $country );
		$code = preg_replace( '/\D+/', '', (string) $code );

		return is_string( $code ) ? $code : '';
	}

	private static function get_phone_country_code_from_conf_fallback( $country, $conf_file ) {
		$country = trim( (string) $country );
		if ( '' === $country || ! $conf_file || ! file_exists( $conf_file ) ) {
			return '';
		}

		$lines = file( $conf_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		if ( ! is_array( $lines ) ) {
			return '';
		}

		foreach ( $lines as $line ) {
			$parts = explode( ':', $line, 2 );
			if ( 2 !== count( $parts ) ) {
				continue;
			}

			$k = trim( $parts[0] );
			$v = trim( $parts[1] );

			if ( strtoupper( $k ) === strtoupper( $country ) ) {
				return preg_replace( '/\D+/', '', $v );
			}
		}

		return '';
	}

	private static function build_username_by_site_rules( $skip_country_code, $dial, $local ) {
		$local = preg_replace( '/\D/', '', (string) $local );
		$dial  = preg_replace( '/\D/', '', (string) $dial );
		$local = ltrim( $local, '0' );

		if ( '' === $local ) {
			return '';
		}

		if ( $skip_country_code ) {
			return $local;
		}

		if ( '' === $dial ) {
			return '';
		}

		return $dial . '-' . $local;
	}

	private static function update_user_login_direct( $user_id, $new_username ) {
		global $wpdb;

		$user_id      = (int) $user_id;
		$new_username = (string) $new_username;

		if ( $user_id <= 0 || '' === $new_username ) {
			return new WP_Error( 'invalid_data', __( 'Invalid user or username.', 'wc-advanced-accounts' ) );
		}

		$existing = username_exists( $new_username );
		if ( $existing && (int) $existing !== $user_id ) {
			return new WP_Error( 'username_exists', __( 'Username already exists.', 'wc-advanced-accounts' ) );
		}

		$updated = $wpdb->update(
			$wpdb->users,
			array(
				'user_login'    => $new_username,
				'user_nicename' => sanitize_title( $new_username ),
			),
			array( 'ID' => $user_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			return new WP_Error( 'db_update_failed', __( 'Database update failed.', 'wc-advanced-accounts' ) );
		}

		clean_user_cache( $user_id );
		wp_cache_delete( $new_username, 'userlogins' );

		return true;
	}

	private static function get_phone_conf_file() {
		return plugin_dir_path( __FILE__ ) . '../actions/data/phone_country_codes.conf';
	}

	private static function get_dial_codes_from_conf( $conf_file ) {
		static $cache = array();

		$conf_file = (string) $conf_file;
		if ( isset( $cache[ $conf_file ] ) ) {
			return $cache[ $conf_file ];
		}

		$dials = array_fill_keys( self::get_woocommerce_dial_codes(), true );

		if ( '' === $conf_file || ! file_exists( $conf_file ) ) {
			$cache[ $conf_file ] = array_keys( $dials );
			return $cache[ $conf_file ];
		}

		$lines = file( $conf_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		if ( ! is_array( $lines ) ) {
			$cache[ $conf_file ] = array_keys( $dials );
			return $cache[ $conf_file ];
		}

		foreach ( $lines as $line ) {
			$parts = explode( ':', $line, 2 );
			if ( 2 !== count( $parts ) ) {
				continue;
			}

			$v = preg_replace( '/\D+/', '', trim( $parts[1] ) );
			if ( '' !== $v ) {
				$dials[ $v ] = true;
			}
		}

		$dials = array_keys( $dials );

		usort(
			$dials,
			function( $a, $b ) {
				return strlen( $b ) <=> strlen( $a );
			}
		);

		$cache[ $conf_file ] = $dials;

		return $cache[ $conf_file ];
	}

	private static function get_woocommerce_dial_codes() {
		if ( ! function_exists( 'WC' ) || ! WC() || empty( WC()->countries ) ) {
			return array();
		}

		$countries = WC()->countries->get_countries();
		if ( ! is_array( $countries ) ) {
			return array();
		}

		$dials = array();
		foreach ( array_keys( $countries ) as $country ) {
			$code = self::get_woocommerce_country_calling_code( $country );
			if ( '' !== $code ) {
				$dials[ $code ] = true;
			}
		}

		return array_keys( $dials );
	}

	private static function extract_dial_local_from_international_phone( $raw_phone ) {
		$raw_phone = is_string( $raw_phone ) ? trim( $raw_phone ) : '';
		if ( '' === $raw_phone ) {
			return array( 'dial' => '', 'local' => '' );
		}

		$starts_plus = ( 0 === strpos( $raw_phone, '+' ) );
		$starts_00   = ( 0 === strpos( $raw_phone, '00' ) );

		if ( ! $starts_plus && ! $starts_00 ) {
			return array( 'dial' => '', 'local' => '' );
		}

		$digits = preg_replace( '/\D+/', '', $raw_phone );
		if ( $starts_00 && 0 === strpos( $digits, '00' ) ) {
			$digits = substr( $digits, 2 );
		}

		if ( strlen( $digits ) < 7 ) {
			return array( 'dial' => '', 'local' => '' );
		}

		$dials = self::get_dial_codes_from_conf( self::get_phone_conf_file() );

		if ( empty( $dials ) ) {
			return array( 'dial' => '', 'local' => '' );
		}

		foreach ( $dials as $dial ) {
			if ( 0 === strpos( $digits, $dial ) ) {
				$local = substr( $digits, strlen( $dial ) );
				$local = preg_replace( '/\D+/', '', (string) $local );
				$local = ltrim( $local, '0' );

				if ( '' !== $local && strlen( $local ) >= 6 && strlen( $local ) <= 15 ) {
					return array(
						'dial'  => $dial,
						'local' => $local,
					);
				}
			}
		}

		return array( 'dial' => '', 'local' => '' );
	}
}

YOAA_WC_Advanced_Accounts_Tools::init();
