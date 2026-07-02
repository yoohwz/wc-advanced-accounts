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
		$processed = (int) ( $results['processed'] ?? 0 );
		$updated   = (int) ( $results['updated'] ?? 0 );
		$skipped   = (int) ( $results['skipped'] ?? 0 );
		$conflicts = (int) ( $results['conflicts'] ?? 0 );

		$updated_status = $dry_run ? 'would_update' : 'updated';
		$updated_label  = $dry_run ? __( 'Would Update', 'wc-advanced-accounts' ) : __( 'Updated', 'wc-advanced-accounts' );

		echo '<div class="yoaa-tools-results" style="margin-top:14px;">';
		echo '<h4 style="margin:0 0 8px;">' . esc_html__( 'Results', 'wc-advanced-accounts' ) . '</h4>';

		echo '<p style="margin:0 0 10px;">';
		echo '<a href="#" class="yoaa-result-filter" data-filter="all" style="text-decoration:none;font-weight:600;">' .
			esc_html__( 'Processed', 'wc-advanced-accounts' ) . ': ' . esc_html( $processed ) .
		'</a>';
		echo ' | ';
		echo '<a href="#" class="yoaa-result-filter" data-filter="' . esc_attr( $updated_status ) . '" style="text-decoration:none;font-weight:600;">' .
			esc_html( $updated_label ) . ': ' . esc_html( $updated ) .
		'</a>';
		echo ' | ';
		echo '<a href="#" class="yoaa-result-filter" data-filter="skipped" style="text-decoration:none;font-weight:600;">' .
			esc_html__( 'Skipped', 'wc-advanced-accounts' ) . ': ' . esc_html( $skipped ) .
		'</a>';
		echo ' | ';
		echo '<a href="#" class="yoaa-result-filter" data-filter="conflict" style="text-decoration:none;font-weight:600;">' .
			esc_html__( 'Conflicts', 'wc-advanced-accounts' ) . ': ' . esc_html( $conflicts ) .
		'</a>';
		echo '</p>';

		echo '<p class="yoaa-filter-label" style="margin:0 0 10px;color:#646970;"></p>';

		if ( empty( $results['rows'] ) || ! is_array( $results['rows'] ) ) {
			echo '</div>';
			return;
		}

		echo '<table class="widefat striped yoaa-results-table" style="max-width:1200px;">';
		echo '<thead><tr>';
		echo '<th>' . esc_html__( 'User ID', 'wc-advanced-accounts' ) . '</th>';
		echo '<th>' . esc_html__( 'Email', 'wc-advanced-accounts' ) . '</th>';
		echo '<th>' . esc_html__( 'Old username', 'wc-advanced-accounts' ) . '</th>';
		echo '<th>' . esc_html__( 'Phone source', 'wc-advanced-accounts' ) . '</th>';
		echo '<th>' . esc_html__( 'Detected phone', 'wc-advanced-accounts' ) . '</th>';
		echo '<th>' . esc_html__( 'New username', 'wc-advanced-accounts' ) . '</th>';
		echo '<th>' . esc_html__( 'Status', 'wc-advanced-accounts' ) . '</th>';
		echo '<th>' . esc_html__( 'Reason', 'wc-advanced-accounts' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $results['rows'] as $r ) {
			$status      = isset( $r['status'] ) ? (string) $r['status'] : '';
			$status_attr = '' !== $status ? $status : 'unknown';
			$user_id     = isset( $r['user_id'] ) ? (int) $r['user_id'] : 0;
			$edit_link   = $user_id > 0 ? admin_url( 'user-edit.php?user_id=' . $user_id ) : '';

			echo '<tr data-status="' . esc_attr( $status_attr ) . '">';
			echo '<td>';
			if ( $edit_link ) {
				echo '<a href="' . esc_url( $edit_link ) . '" target="_blank" rel="noopener noreferrer" style="font-weight:600;">' . esc_html( $user_id ) . '</a>';
			} else {
				echo esc_html( $user_id );
			}
			echo '</td>';
			echo '<td>' . esc_html( $r['email'] ?? '' ) . '</td>';
			echo '<td>' . esc_html( $r['old_username'] ?? '' ) . '</td>';
			echo '<td>' . esc_html( $r['phone_source'] ?? '' ) . '</td>';
			echo '<td>' . esc_html( $r['detected_phone'] ?? '' ) . '</td>';
			echo '<td>' . esc_html( $r['new_username'] ?? '' ) . '</td>';
			echo '<td>' . esc_html( $status_attr ) . '</td>';
			echo '<td>' . esc_html( $r['reason'] ?? '' ) . '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';

		$filter_labels = array(
			'filter'       => __( 'Filter:', 'wc-advanced-accounts' ),
			'all'          => __( 'All', 'wc-advanced-accounts' ),
			'skipped'      => __( 'Skipped', 'wc-advanced-accounts' ),
			'conflicts'    => __( 'Conflicts', 'wc-advanced-accounts' ),
			'updated'      => __( 'Updated', 'wc-advanced-accounts' ),
			'would_update' => __( 'Would Update', 'wc-advanced-accounts' ),
		);
		?>
		<script>
		(function(){
			const labels = <?php echo wp_json_encode( $filter_labels ); ?>;
			const wrap = document.querySelector('.yoaa-tools-results');
			if (!wrap) return;

			const table = wrap.querySelector('.yoaa-results-table');
			if (!table) return;

			const label = wrap.querySelector('.yoaa-filter-label');
			const links = wrap.querySelectorAll('.yoaa-result-filter');

			function setLabel(text){
				if (!label) return;
				label.textContent = text ? ((labels.filter || 'Filter:') + ' ' + text) : '';
			}

			function applyFilter(filter){
				const rows = table.querySelectorAll('tbody tr');

				rows.forEach(function(tr){
					const st = (tr.getAttribute('data-status') || '').toLowerCase();

					tr.classList.remove('yoaa-hide-row');

					if (filter === 'all') {
						return;
					}

					if (filter === 'conflict' && st !== 'conflict') {
						tr.classList.add('yoaa-hide-row');
						return;
					}

					if (filter === 'skipped' && st !== 'skipped') {
						tr.classList.add('yoaa-hide-row');
						return;
					}

					if (filter === 'updated' && st !== 'updated') {
						tr.classList.add('yoaa-hide-row');
						return;
					}

					if (filter === 'would_update' && st !== 'would_update') {
						tr.classList.add('yoaa-hide-row');
					}
				});

				links.forEach(function(a){
					a.style.opacity = (a.dataset.filter === filter) ? '1' : '0.6';
				});
			}

			links.forEach(function(a){
				a.addEventListener('click', function(e){
					e.preventDefault();
					const filter = (a.dataset.filter || 'all').toLowerCase();
					applyFilter(filter);

					let labelText = labels.all || 'All';
					if (filter === 'skipped') labelText = labels.skipped || 'Skipped';
					else if (filter === 'conflict') labelText = labels.conflicts || 'Conflicts';
					else if (filter === 'updated') labelText = labels.updated || 'Updated';
					else if (filter === 'would_update') labelText = labels.would_update || 'Would Update';

					setLabel(labelText);
				});
			});

			applyFilter('all');
			setLabel(labels.all || 'All');
		})();
		</script>

		<style>
		.yoaa-results-table tbody tr.yoaa-hide-row {
			display: none;
		}
		.yoaa-results-table thead th {
			padding-left: 10px;
			padding-right: 10px !important;
		}
		</style>
		<?php

		echo '</div>';
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

			$raw_phone  = $phone_data['raw'];
			$normalized = self::normalize_phone( $raw_phone );

			if ( empty( $normalized['local'] ) ) {
				$skipped++;
				$rows[] = self::row( $u, $old_username, $phone_data['source'], $raw_phone, '', 'skipped', __( 'Invalid phone after normalization.', 'wc-advanced-accounts' ) );
				continue;
			}

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

			if ( empty( $new_username ) ) {
				$skipped++;
				$rows[] = self::row( $u, $old_username, $phone_data['source'], $phone_data['raw'], '', 'skipped', __( 'Could not build new username.', 'wc-advanced-accounts' ) );
				continue;
			}

			if ( $new_username === $old_username ) {
				$skipped++;
				$rows[] = self::row( $u, $old_username, $phone_data['source'], $phone_data['raw'], $new_username, 'skipped', __( 'Already matches.', 'wc-advanced-accounts' ) );
				continue;
			}

			$existing = username_exists( $new_username );
			if ( $existing && (int) $existing !== (int) $u->ID ) {
				$conflicts++;
				$rows[] = self::row( $u, $old_username, $phone_data['source'], $phone_data['raw'], $new_username, 'conflict', __( 'Username already exists.', 'wc-advanced-accounts' ) );
				continue;
			}

			if ( ! empty( $args['dry_run'] ) ) {
				$updated++;
				$rows[] = self::row( $u, $old_username, $phone_data['source'], $phone_data['raw'], $new_username, 'would_update', __( 'Dry-run.', 'wc-advanced-accounts' ) );
				continue;
			}

			$result = self::update_user_login_direct( (int) $u->ID, $new_username );

			if ( is_wp_error( $result ) ) {
				$skipped++;
				$rows[] = self::row( $u, $old_username, $phone_data['source'], $phone_data['raw'], $new_username, 'error', $result->get_error_message() );
				continue;
			}

			update_user_meta( (int) $u->ID, '_yoaa_old_username_before_phone_migration', $old_username );
			update_user_meta( (int) $u->ID, 'nickname', $new_username );

			$updated++;
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

		$conf_file = plugin_dir_path( __FILE__ ) . '../actions/data/phone_country_codes.conf';

		return self::get_phone_country_code_from_conf_fallback( $country, $conf_file );
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

		if ( '' === $conf_file || ! file_exists( $conf_file ) ) {
			$cache[ $conf_file ] = array();
			return $cache[ $conf_file ];
		}

		$lines = file( $conf_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		if ( ! is_array( $lines ) ) {
			$cache[ $conf_file ] = array();
			return $cache[ $conf_file ];
		}

		$dials = array();

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
