<?php
/** R5 protocol v1. Identical local implementations; no companion bootstrap required. */
defined( 'ABSPATH' ) || exit;

if ( class_exists( 'wpdb' ) && ! class_exists( 'YOSWC_Role_Ownership_DB' ) ) {
	/** A transaction must never silently reconnect and retry outside its original connection. */
	final class YOSWC_Role_Ownership_DB extends wpdb {
		public function check_connection( $allow_bail = true ) { return false; }
	}
}

if ( ! class_exists( 'YOSWC_Role_Ownership' ) ) {
final class YOSWC_Role_Ownership {
	const VERSION = 1;
	const PREFIX = 'yoswc_role_owner_';
	const LOCK = 'yoswc_role_management_lock';
	const BATCH = 100;
	const WINDOWS = 20;
	private static $owners = array(
		'wc-advanced-accounts-premium' => array( 'yoaa_membership_created_roles', 'yoaa_membership_role' ),
		'wc-advanced-accounts' => array( 'yoswc_loyalty_created_roles', 'yoswc_loyalty_role' ),
		'wc-loyalty' => array( 'yowcl_loyalty_created_roles', 'yowcl_loyalty_role' ),
	);

	public static function protected_role( $slug ) {
		return in_array( $slug, array( 'administrator', 'editor', 'author', 'contributor', 'subscriber', 'customer', 'shop_manager', 'translator' ), true );
	}
	private static function fresh_option( $key, $default = false ) {
		wp_cache_delete( $key, 'options' );
		wp_cache_delete( 'alloptions', 'options' );
		wp_cache_delete( 'notoptions', 'options' );
		global $wpdb;
		$wpdb->last_error = '';
		$value = get_option( $key, $default );
		if ( $wpdb->last_error ) { throw new RuntimeException( 'Role dependency option read failed.' ); }
		return $value;
	}
	private static function save( $key, $value ) {
		update_option( $key, $value, false );
		return self::fresh_option( $key ) === $value;
	}
	private static function role_data( $slug ) {
		try { $roles = self::fresh_option( wp_roles()->role_key, null ); } catch ( Throwable $error ) { return false; }
		return is_array( $roles ) && isset( $roles[ $slug ] ) ? $roles[ $slug ] : null;
	}
	private static function valid( $record, $slug ) {
		return is_array( $record ) && array_keys( $record ) === array( 'version', 'owner', 'slug', 'origin', 'state', 'generation', 'created' )
			&& 1 === $record['version'] && $slug === $record['slug'] && is_string( $record['created'] )
			&& is_string( $record['owner'] ) && is_string( $record['origin'] ) && is_string( $record['state'] )
			&& ( ( isset( self::$owners[ $record['owner'] ] ) && 'plugin-created' === $record['origin']
				&& in_array( $record['state'], array( 'pending', 'active', 'retired', 'deleting' ), true )
				&& is_string( $record['generation'] ) && preg_match( '/^[a-f0-9]{32}$/D', $record['generation'] ) )
				|| ( 'unknown' === $record['owner'] && 'legacy-unknown' === $record['origin'] && 'retired' === $record['state'] && '' === $record['generation'] ) );
	}
	public static function record( $slug ) {
		try { $record = self::fresh_option( self::PREFIX . $slug, null ); } catch ( Throwable $error ) { return false; }
		return null === $record ? null : ( self::valid( $record, $slug ) ? $record : false );
	}
	public static function owns( $owner, $slug ) {
		$record = self::record( $slug );
		$data = self::role_data( $slug );
		return is_array( $record ) && $owner === $record['owner'] && 'plugin-created' === $record['origin']
			&& in_array( $record['state'], array( 'active', 'retired' ), true ) && is_array( $data )
			&& true === ( $data['capabilities'][ self::PREFIX . $record['generation'] ] ?? null );
	}
	private static function locked( $operation ) {
		global $wpdb;
		if ( ! current_user_can( 'manage_woocommerce' ) ) { return 'permission'; }
		try { $old = self::fresh_option( self::LOCK, null ); } catch ( Throwable $error ) { return 'query'; }
		if ( null !== $old ) {
			if ( ! is_array( $old ) || ( $old['until'] ?? PHP_INT_MAX ) >= time() ) { return 'busy'; }
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND BINARY option_value = %s", self::LOCK, maybe_serialize( $old ) ) );
		}
		$lock = array( 'token' => wp_generate_uuid4(), 'until' => time() + 120 );
		if ( ! add_option( self::LOCK, $lock, '', 'no' ) ) { return 'busy'; }
		try { return $operation(); }
		catch ( Throwable $error ) { return 'storage'; }
		finally {
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND BINARY option_value = %s", self::LOCK, maybe_serialize( $lock ) ) );
			wp_cache_delete( self::LOCK, 'options' );
		}
	}
	public static function create( $owner, $slug, $name ) {
		return self::locked( static function () use ( $owner, $slug, $name ) {
			if ( ! isset( self::$owners[ $owner ] ) || ! $slug || sanitize_key( $slug ) !== $slug || self::protected_role( $slug ) ) { return 'protected'; }
			if ( self::role_data( $slug ) || null !== self::record( $slug ) ) { return 'exists'; }
			foreach ( self::$owners as $keys ) {
				$reg = self::fresh_option( $keys[0], array() );
				if ( ! is_array( $reg ) || isset( $reg[ $slug ] ) ) { return 'ownership'; }
			}
			$customer = self::role_data( 'customer' );
			if ( ! is_array( $customer ) || ! is_array( $customer['capabilities'] ?? null ) ) { return 'customer'; }
			$caps = $customer['capabilities'];
			foreach ( array_keys( $caps ) as $cap ) {
				if ( 0 === strpos( $cap, self::PREFIX ) || in_array( $cap, array_column( self::$owners, 1 ), true ) ) { unset( $caps[ $cap ] ); }
			}
			$generation = str_replace( '-', '', wp_generate_uuid4() );
			$record = array( 'version' => 1, 'owner' => $owner, 'slug' => $slug, 'origin' => 'plugin-created', 'state' => 'pending', 'generation' => $generation, 'created' => current_time( 'mysql' ) );
			if ( ! self::save( self::PREFIX . $slug, $record ) ) { return 'storage'; }
			$caps[ self::$owners[ $owner ][1] ] = true;
			$caps[ self::PREFIX . $generation ] = true;
			wp_roles()->for_site();
			if ( ! ( add_role( $slug, $name, $caps ) instanceof WP_Role ) || ( self::role_data( $slug )['capabilities'] ?? null ) !== $caps ) { return 'storage'; }
			$reg_key = self::$owners[ $owner ][0];
			$reg = self::fresh_option( $reg_key, array() );
			$reg[ $slug ] = array( 'name' => $name, 'r5_generation' => $generation );
			if ( ! self::save( $reg_key, $reg ) ) { return 'storage'; }
			$record['state'] = 'active';
			return self::save( self::PREFIX . $slug, $record ) && self::owns( $owner, $slug ) ? true : 'storage';
		} );
	}
	public static function retire( $owner, $slug ) {
		return self::locked( static function () use ( $owner, $slug ) {
			if ( ! isset( self::$owners[ $owner ] ) || self::protected_role( $slug ) || ! self::role_data( $slug ) ) { return 'protected'; }
			$record = self::record( $slug );
			if ( false === $record || ( is_array( $record ) && ! in_array( $record['owner'], array( $owner, 'unknown' ), true ) ) ) { return 'ownership'; }
			if ( null === $record ) {
				$record = array( 'version' => 1, 'owner' => 'unknown', 'slug' => $slug, 'origin' => 'legacy-unknown', 'state' => 'retired', 'generation' => '', 'created' => '' );
			} elseif ( ! in_array( $record['state'], array( 'active', 'retired' ), true ) ) { return 'ownership'; }
			$record['state'] = 'retired';
			return self::save( self::PREFIX . $slug, $record ) ? true : 'storage';
		} );
	}
	private static function companions() {
		if ( ! defined( 'WP_PLUGIN_DIR' ) ) { return false; }
		$hash = hash_file( 'sha256', __FILE__ );
		foreach ( array_keys( self::$owners ) as $owner ) {
			$file = WP_PLUGIN_DIR . '/' . $owner . '/inc/cores/helper/role-ownership.php';
			if ( ! is_readable( $file ) || $hash !== hash_file( 'sha256', $file ) ) { return false; }
		}
		return true;
	}
	private static function option_keys( $slug ) {
		return array_merge( array( wp_roles()->role_key, self::PREFIX . $slug ), array_column( self::$owners, 0 ), array( 'yoaa_wc_membership_roles', 'yoaa_wc_membership_role_settings', 'loyalty_levels_roles', 'loyalty_levels_rules', 'loyalty_levels_discounts_rules' ) );
	}
	private static function dependencies( $owner, $slug ) {
		$record = self::record( $slug );
		if ( ! self::owns( $owner, $slug ) || 'retired' !== $record['state'] ) { return 'ownership'; }
		$data = self::role_data( $slug );
		foreach ( array_keys( $data['capabilities'] ) as $cap ) { if ( 0 === strpos( $cap, self::PREFIX ) && $cap !== self::PREFIX . $record['generation'] ) { return 'ownership'; } }
		foreach ( self::$owners as $other => $keys ) {
			$reg = self::fresh_option( $keys[0], array() );
			if ( ! is_array( $reg ) || ( $other !== $owner && ( isset( $reg[ $slug ] ) || ! empty( $data['capabilities'][ $keys[1] ] ) ) )
				|| ( $other === $owner && ( $reg[ $slug ]['r5_generation'] ?? null ) !== $record['generation'] ) ) { return 'ownership'; }
		}
		foreach ( array( 'yoaa_wc_membership_roles', 'yoaa_wc_membership_role_settings', 'loyalty_levels_roles', 'loyalty_levels_rules', 'loyalty_levels_discounts_rules' ) as $key ) {
			$value = self::fresh_option( $key, array() );
			if ( ! is_array( $value ) ) { return 'unknown'; }
			if ( isset( $value[ $slug ] ) || in_array( $slug, $value, true ) ) { return 'configuration'; }
		}
		return true;
	}
	private static function decoded( $value ) {
		// WordPress serializes arrays. Never instantiate objects from persisted inspection data.
		if ( ! is_string( $value ) ) { return false; }
		if ( 'a:' !== substr( $value, 0, 2 ) ) { return false; }
		$value = @unserialize( $value, array( 'allowed_classes' => false ) );
		return is_array( $value ) ? $value : false;
	}
	private static function claims_terminal( $record, $source = null ) {
		if ( ! is_array( $record ) || 1 !== ( $record['version'] ?? null ) || ! is_array( $record['claims'] ?? null ) || array_diff( array_keys( $record ), array( 'version', 'role_preexisting', 'claims' ) ) || ( isset( $record['role_preexisting'] ) && ! is_bool( $record['role_preexisting'] ) ) ) { return false; }
		if ( null !== $source && ! isset( $record['claims'][ $source ] ) ) { return false; }
		foreach ( $record['claims'] as $key => $claim ) {
			if ( ! is_array( $claim ) || ! isset( $claim['type'], $claim['id'], $claim['status'], $claim['reason'], $claim['start'], $claim['end'], $claim['duration_type'] )
				|| ! is_int( $claim['id'] ) || ! is_string( $claim['start'] ) || ! is_string( $claim['end'] ) || ! in_array( $claim['duration_type'], array( 'unlimited', 'period', 'specific', 'subscription' ), true )
				|| ! in_array( $claim['status'], array( 'active', 'inactive' ), true ) || ! is_string( $claim['reason'] )
				|| ( 'legacy' === $key ? 'legacy' !== $claim['type'] || 0 !== $claim['id'] : ! in_array( $claim['type'], array( 'order', 'subscription' ), true ) || $claim['id'] < 1 || $key !== $claim['type'] . ':' . $claim['id'] ) ) { return false; }
			if ( array_diff( array_keys( $claim ), array( 'type', 'id', 'status', 'reason', 'start', 'end', 'duration_type', 'pending_role_add', 'pending_supersession', 'legacy_unlimited' ) ) ) { return false; }
			foreach ( array( 'pending_role_add', 'pending_supersession', 'legacy_unlimited' ) as $flag ) { if ( isset( $claim[ $flag ] ) && ! is_bool( $claim[ $flag ] ) ) { return false; } }
			if ( null !== $source && $source !== $key ) { continue; }
			if ( ! empty( $claim['pending_supersession'] ) || ! empty( $claim['pending_role_add'] ) ) { return false; }
			if ( 'inactive' === $claim['status'] && in_array( $claim['reason'], array( 'superseded', 'expired_or_invalid', 'legacy_inactive_or_invalid' ), true ) ) { continue; }
			if ( ! in_array( $claim['reason'], array( '', 'order_inactive', 'order_refunded', 'subscription_inactive' ), true ) ) { return false; }
			if ( 'subscription' === $claim['type'] || ! empty( $claim['legacy_unlimited'] ) ) { return false; }
			$end = DateTime::createFromFormat( '!Y-m-d H:i:s', $claim['end'] );
			if ( ! in_array( $claim['duration_type'], array( 'period', 'specific' ), true ) || ! $end || $end->format( 'Y-m-d H:i:s' ) !== $claim['end'] || strtotime( $claim['end'] ) > current_time( 'timestamp' ) ) { return false; }
		}
		return true;
	}
	private static function raw_claims( $value, $slug ) {
		if ( ! is_array( $value ) ) { return false; }
		foreach ( $value as $role => $sources ) {
			if ( ! is_string( $role ) || sanitize_key( $role ) !== $role || ! $role || ! is_array( $sources ) ) { return false; }
			foreach ( $sources as $source => $data ) {
				if ( ! is_string( $source ) || ! $source || sanitize_key( $source ) !== $source || ! is_array( $data ) || array_keys( $data ) !== array( 'updated_at', 'context' ) || ! is_string( $data['updated_at'] ) || ! is_array( $data['context'] ) ) { return false; }
				foreach ( $data['context'] as $key => $value ) { if ( ! is_string( $key ) || ! $key || sanitize_key( $key ) !== $key || ! is_string( $value ) ) { return false; } }
			}
			if ( $role === $slug && $sources ) { return false; }
		}
		return true;
	}
	private static function valid_intent_line( $line ) {
		if ( ! is_array( $line ) || array_keys( $line ) !== array( 'version', 'order_id', 'item_id', 'product_id', 'variation_id', 'quantity', 'roles' )
			|| 1 !== $line['version'] || ! is_int( $line['order_id'] ) || $line['order_id'] < 1
			|| ! is_int( $line['item_id'] ) || $line['item_id'] < 1 || ! is_int( $line['product_id'] ) || $line['product_id'] < 1
			|| ! is_int( $line['variation_id'] ) || $line['variation_id'] < 0
			|| ! is_numeric( $line['quantity'] ) || ! is_finite( (float) $line['quantity'] ) || $line['quantity'] <= 0
			|| ! is_array( $line['roles'] ) || ! $line['roles'] ) {
			return false;
		}
		foreach ( $line['roles'] as $role => $term ) {
			if ( ! is_string( $role ) || '' === $role || sanitize_key( $role ) !== $role || ! is_array( $term ) || ! isset( $term['product_type'] ) ) {
				return false;
			}
			if ( array( 'product_type' => 'subscription' ) === $term ) {
				continue;
			}
			if ( 'product' !== $term['product_type'] || ! isset( $term['duration_type'] ) ) {
				return false;
			}
			$expected = array( 'product_type', 'duration_type' );
			switch ( $term['duration_type'] ) {
				case 'unlimited':
					break;
				case 'period':
					$expected = array_merge( $expected, array( 'duration_value', 'duration_unit' ) );
					if ( ! isset( $term['duration_value'], $term['duration_unit'] ) || ! is_int( $term['duration_value'] ) || $term['duration_value'] < 1
						|| ! in_array( $term['duration_unit'], array( 'day', 'week', 'month', 'year' ), true ) ) {
						return false;
					}
					break;
				case 'specific':
					$expected[] = 'specific_date';
					$date = $term['specific_date'] ?? '';
					$parsed = is_string( $date ) ? DateTime::createFromFormat( '!Y-m-d', $date ) : false;
					if ( ! $parsed || $parsed->format( 'Y-m-d' ) !== $date ) {
						return false;
					}
					break;
				default:
					return false;
			}
			if ( array_keys( $term ) !== $expected ) {
				return false;
			}
		}
		return true;
	}

	private static function source_unused( $row, $slug ) {
		$value = self::decoded( $row->meta_value );
		if ( ! is_array( $value ) || array_keys( $value ) !== array( 'version', 'type', 'id' ) || 1 !== $value['version'] || ! is_int( $value['id'] ) || $value['id'] < 1 || ! in_array( $value['type'], array( 'order', 'subscription' ), true ) ) { return false; }
		if ( ! function_exists( 'wc_get_order' ) || ( 'subscription' === $value['type'] && ! function_exists( 'wcs_get_subscription' ) ) ) { return false; }
		$source = 'subscription' === $value['type'] ? wcs_get_subscription( $value['id'] ) : wc_get_order( $value['id'] );
		if ( ! $source || absint( $source->get_user_id() ) !== absint( $row->user_id ) ) { return false; }
		$order = 'subscription' === $value['type'] ? wc_get_order( $source->get_parent_id() ) : $source;
		if ( ! $order || absint( $order->get_user_id() ) !== absint( $row->user_id ) ) { return false; }
		$order->read_meta_data( true );
		$manifest = $order->get_meta( '_yoaa_membership_intent', true );
		if ( '' === $manifest ) { return true; } // Pre-R4 cannot first-grant from current settings; canonical rows are checked separately.
		if ( ! is_array( $manifest ) || array_keys( $manifest ) !== array( 'version', 'state', 'order_id', 'items', 'lines' ) || 1 !== $manifest['version'] || 'committed' !== $manifest['state'] || $manifest['order_id'] !== absint( $order->get_id() ) || ! is_array( $manifest['items'] ) || ! is_array( $manifest['lines'] ) || ! $manifest['lines'] ) { return false; }
		$items = $order->get_items( 'line_item' );
		$ids = array_map( 'absint', array_keys( $items ) ); sort( $ids );
		if ( $ids !== $manifest['items'] ) { return false; }
		$found = array(); $verified = 0;
		foreach ( $items as $id => $item ) {
			$item->read_meta_data( true ); $line = $item->get_meta( '_yoaa_membership_intent_line', true );
			if ( ! isset( $manifest['lines'][ $id ] ) ) { if ( '' !== $line ) { return false; } continue; }
			if ( ! self::valid_intent_line( $line ) || array_keys( $line ) !== array( 'version', 'order_id', 'item_id', 'product_id', 'variation_id', 'quantity', 'roles' )
				|| 1 !== $line['version'] || $line['order_id'] !== $manifest['order_id'] || $line['item_id'] !== absint( $id ) || $line['product_id'] !== absint( $item->get_product_id() ) || $line['variation_id'] !== absint( $item->get_variation_id() )
				|| ! is_numeric( $line['quantity'] ) || $line['quantity'] <= 0 || (float) $line['quantity'] !== (float) $item->get_quantity() || ! is_array( $line['roles'] ) || ! $line['roles'] || hash( 'sha256', serialize( $line ) ) !== $manifest['lines'][ $id ] ) { return false; }
			++$verified;
			if ( isset( $line['roles'][ $slug ] ) ) { $found[ $id ] = $line; }
		}
		if ( count( $manifest['lines'] ) !== $verified ) { return false; }
		$record = get_user_meta( $row->user_id, '_yoaa_membership_role_' . $slug . '_claims', true );
		$subscription_lines = array();
		foreach ( $found as $id => $line ) {
			$term = $line['roles'][ $slug ];
			if ( 'product' === $term['product_type'] ) {
				if ( ! self::claims_terminal( $record, 'order:' . $order->get_id() ) ) { return false; }
			} else { $subscription_lines[ $id ] = $line; }
		}
		if ( $subscription_lines ) {
			if ( ! function_exists( 'wcs_get_subscriptions_for_order' ) ) { return false; }
			$subs = wcs_get_subscriptions_for_order( $order, array( 'order_type' => 'parent' ) );
			if ( ! is_array( $subs ) || ! $subs ) { return false; }
			$covered = array();
			foreach ( $subs as $sub ) {
				if ( ! $sub || absint( $sub->get_parent_id() ) !== absint( $order->get_id() ) || absint( $sub->get_user_id() ) !== absint( $row->user_id ) ) { return false; }
				foreach ( $sub->get_items( 'line_item' ) as $item ) {
					$item->read_meta_data( true ); $origin = $item->get_meta( '_yoaa_membership_origin', true );
					if ( ! is_array( $origin ) || array_keys( $origin ) !== array( 'version', 'order_id', 'item_id' ) || 1 !== $origin['version'] || $origin['order_id'] !== absint( $order->get_id() ) || ! is_int( $origin['item_id'] ) || ! isset( $items[ $origin['item_id'] ] ) ) { return false; }
					$id = $origin['item_id'];
					if ( ! isset( $subscription_lines[ $id ] ) ) { continue; }
					$line = $subscription_lines[ $id ];
					if ( isset( $covered[ $id ] ) || $line['product_id'] !== absint( $item->get_product_id() ) || $line['variation_id'] !== absint( $item->get_variation_id() ) || (float) $line['quantity'] !== (float) $item->get_quantity() || ! self::claims_terminal( $record, 'subscription:' . $sub->get_id() ) ) { return false; }
					$covered[ $id ] = true;
				}
			}
			if ( count( $covered ) !== count( $subscription_lines ) ) { return false; }
		}
		return true;
	}
	private static function user_dependencies( $slug ) {
		global $wpdb;
		$cursor = 0;
		$prefix = '_yoaa_membership_role_' . $slug;
		for ( $window = 0; $window < self::WINDOWS; ++$window ) {
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT umeta_id, user_id, meta_key, meta_value FROM {$wpdb->usermeta} WHERE umeta_id > %d ORDER BY umeta_id ASC LIMIT %d FOR UPDATE", $cursor, self::BATCH ) );
			if ( $wpdb->last_error || ! is_array( $rows ) ) { return 'query'; }
			foreach ( $rows as $row ) {
				$cursor = absint( $row->umeta_id );
				if ( $wpdb->get_blog_prefix() . 'capabilities' === $row->meta_key ) {
					$value = self::decoded( $row->meta_value );
					if ( ! is_array( $value ) ) { return 'unknown'; }
					if ( array_key_exists( $slug, $value ) ) { return 'user'; }
				} elseif ( '_yoswc_role_claims' === $row->meta_key ) {
					if ( ! self::raw_claims( self::decoded( $row->meta_value ), $slug ) ) { return 'claims'; }
				} elseif ( '_yowcl_loyalty_level' === $row->meta_key ) {
					if ( $row->meta_value === $slug ) { return 'loyalty'; }
					if ( $row->meta_value && sanitize_key( $row->meta_value ) !== $row->meta_value ) { return 'unknown'; }
				} elseif ( '_yoaa_membership_admissions' === $row->meta_key ) {
					$value = self::decoded( $row->meta_value );
					if ( ! is_array( $value ) ) { return 'unknown'; }
					foreach ( $value as $id => $entry ) {
						if ( ! is_array( $entry ) || 1 !== ( $entry['version'] ?? null ) || absint( $id ) !== ( $entry['order_id'] ?? null ) || absint( $row->user_id ) !== ( $entry['user_id'] ?? null ) || ! is_array( $entry['roles'] ?? null ) || ! $entry['roles'] || ! is_int( $entry['created'] ?? null ) || ! in_array( $entry['context'] ?? null, array( 'sale', 'renewal' ), true ) ) { return 'unknown'; }
						foreach ( $entry['roles'] as $role ) { if ( ! is_string( $role ) || ! $role || sanitize_key( $role ) !== $role ) { return 'unknown'; } }
						if ( in_array( $slug, $entry['roles'], true ) ) { return 'admission'; }
					}
				} elseif ( $prefix . '_claims' === $row->meta_key ) {
					if ( ! self::claims_terminal( self::decoded( $row->meta_value ) ) ) { return 'membership'; }
				} elseif ( 0 === strpos( $row->meta_key, $prefix . '_' ) && '' !== $row->meta_value && '0' !== $row->meta_value ) {
					wp_cache_delete( $row->user_id, 'user_meta' );
					if ( ! ( get_user_meta( $row->user_id, $prefix . '_claims', true )['claims'] ?? array() ) || ! self::claims_terminal( get_user_meta( $row->user_id, $prefix . '_claims', true ) ) ) { return 'membership'; }
				} elseif ( '_yoaa_membership_source' === $row->meta_key ) {
					wp_cache_delete( $row->user_id, 'user_meta' );
					if ( ! self::source_unused( $row, $slug ) ) { return 'intent'; }
				}
			}
			if ( count( $rows ) < self::BATCH ) { return true; }
		}
		return 'scan_incomplete';
	}
	/** Inspect Woo storage as well as locators: partial R4 writes may never have indexed. */
	private static function storage_dependencies( $slug ) {
		global $wpdb;
		$stores = array(
			array( $wpdb->postmeta, 'meta_id', 'post_id', array( '_yoaa_membership_intent' ) ),
			array( $wpdb->prefix . 'wc_orders_meta', 'id', 'order_id', array( '_yoaa_membership_intent' ) ),
			array( $wpdb->prefix . 'woocommerce_order_itemmeta', 'meta_id', 'order_item_id', array( '_yoaa_membership_intent_line', '_yoaa_membership_origin' ) ),
		);
		foreach ( $stores as $store ) {
			list( $table, $primary, $object_id, $keys ) = $store;
			$cursor = 0;
			for ( $window = 0; $window < self::WINDOWS; ++$window ) {
				$sql = "SELECT {$primary} AS cursor_id, {$object_id} AS object_id, meta_key, meta_value FROM {$table} WHERE {$primary} > %d AND meta_key IN (" . implode( ',', array_fill( 0, count( $keys ), '%s' ) ) . ") ORDER BY {$primary} ASC LIMIT %d FOR UPDATE";
				$rows = $wpdb->get_results( $wpdb->prepare( $sql, array_merge( array( $cursor ), $keys, array( self::BATCH ) ) ) );
				if ( $wpdb->last_error || ! is_array( $rows ) ) { return 'query'; }
				foreach ( $rows as $row ) {
					$cursor = absint( $row->cursor_id );
					if ( ! function_exists( 'wc_get_order' ) ) { return 'intent'; }
					$value = self::decoded( $row->meta_value );
					if ( ! is_array( $value ) ) { return 'intent'; }
					$item_id = 0;
					if ( 'order_item_id' === $object_id ) {
						if ( ! function_exists( 'wc_get_order_id_by_order_item_id' ) ) { return 'intent'; }
						$item_id = absint( $row->object_id );
						$id = wc_get_order_id_by_order_item_id( $item_id );
					} else { $id = absint( $row->object_id ); }
					wp_cache_delete( $id, 'posts' );
					if ( class_exists( 'WC_Cache_Helper' ) ) { WC_Cache_Helper::invalidate_cache_group( 'orders' ); }
					$source = wc_get_order( $id );
					if ( ! $source || ! $source->get_user_id() ) { return 'intent'; }
					$source->read_meta_data( true );
					if ( ! $item_id ) {
						if ( $source->get_meta( '_yoaa_membership_intent', true ) !== $value ) { return 'intent'; }
					} else {
						$item = $source->get_item( $item_id );
						if ( ! $item ) { return 'intent'; }
						$item->read_meta_data( true );
						if ( $item->get_meta( $row->meta_key, true ) !== $value ) { return 'intent'; }
						if ( '_yoaa_membership_origin' === $row->meta_key ) {
							if ( array_keys( $value ) !== array( 'version', 'order_id', 'item_id' ) || 1 !== $value['version'] || ! is_int( $value['order_id'] ) || ! is_int( $value['item_id'] ) || absint( $source->get_parent_id() ) !== $value['order_id'] ) { return 'intent'; }
							$source = wc_get_order( $source->get_parent_id() );
						}
						if ( ! $source || '' === $source->get_meta( '_yoaa_membership_intent', true ) ) { return 'intent'; }
					}
					wp_cache_delete( $source->get_user_id(), 'user_meta' );
					$locator = (object) array( 'user_id' => absint( $source->get_user_id() ), 'meta_value' => serialize( array( 'version' => 1, 'type' => 'order', 'id' => absint( $source->get_id() ) ) ) );
					if ( ! self::source_unused( $locator, $slug ) ) { return 'intent'; }
				}
				if ( count( $rows ) < self::BATCH ) { break; }
				if ( self::WINDOWS - 1 === $window ) { return 'scan_incomplete'; }
			}
		}
		return true;
	}
	/** Negative evidence is fresh and held through deletion, never a persisted scan PASS. */
	public static function hard_delete( $owner, $slug ) {
		return self::locked( static function () use ( $owner, $slug ) {
			global $wpdb;
			if ( self::protected_role( $slug ) ) { return 'protected'; }
			if ( ! self::companions() ) { return 'companion'; }
			$names = array( $wpdb->options, $wpdb->usermeta, $wpdb->posts, $wpdb->postmeta, $wpdb->prefix . 'wc_orders', $wpdb->prefix . 'wc_orders_meta', $wpdb->prefix . 'woocommerce_order_items', $wpdb->prefix . 'woocommerce_order_itemmeta' );
			$tables = $wpdb->get_results( $wpdb->prepare( 'SHOW TABLE STATUS WHERE Name IN (' . implode( ',', array_fill( 0, count( $names ), '%s' ) ) . ')', $names ) );
			if ( $wpdb->last_error || ! is_array( $tables ) || count( $names ) !== count( $tables ) ) { return 'query'; }
			foreach ( $tables as $table ) { if ( 'InnoDB' !== $table->Engine ) { return 'storage'; } }
			if ( ! class_exists( 'YOSWC_Role_Ownership_DB' ) || 'wpdb' !== get_class( $wpdb ) ) { return 'storage'; }
			$original = $wpdb;
			$database = new YOSWC_Role_Ownership_DB( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
			$database->set_prefix( $original->base_prefix );
			$database->set_blog_id( $original->blogid, $original->siteid );
			// Woo registers these native metadata aliases on the primary wpdb instance.
			$database->woocommerce_order_items = $original->prefix . 'woocommerce_order_items';
			$database->woocommerce_order_itemmeta = $original->prefix . 'woocommerce_order_itemmeta';
			$database->order_itemmeta = $database->woocommerce_order_itemmeta;
			$database->suppress_errors( true );
			$wpdb = $database;
			try {
				if ( false === $wpdb->query( 'SET TRANSACTION ISOLATION LEVEL SERIALIZABLE' ) || false === $wpdb->query( 'START TRANSACTION' ) ) { return 'storage'; }
				$committed = false;
				try {
					$keys = self::option_keys( $slug );
					$sql = "SELECT option_name FROM {$wpdb->options} WHERE option_name IN (" . implode( ',', array_fill( 0, count( $keys ), '%s' ) ) . ') FOR UPDATE';
					$rows = $wpdb->get_results( $wpdb->prepare( $sql, $keys ) );
					if ( $wpdb->last_error || ! is_array( $rows ) ) { return 'query'; }
					$result = self::dependencies( $owner, $slug );
					if ( true !== $result ) { return $result; }
					$result = self::user_dependencies( $slug );
					if ( true !== $result ) { return $result; }
					$result = self::storage_dependencies( $slug );
					if ( true !== $result ) { return $result; }
					$record = self::record( $slug ); $record['state'] = 'deleting';
					if ( ! self::save( self::PREFIX . $slug, $record ) ) { return 'storage'; }
					wp_roles()->for_site(); remove_role( $slug );
					if ( null !== self::role_data( $slug ) ) { return 'storage'; }
					$key = self::$owners[ $owner ][0]; $reg = self::fresh_option( $key, array() ); unset( $reg[ $slug ] );
					if ( ! self::save( $key, $reg ) ) { return 'storage'; }
					delete_option( self::PREFIX . $slug );
					if ( null !== self::fresh_option( self::PREFIX . $slug, null ) || false === $wpdb->query( 'COMMIT' ) ) { return 'storage'; }
					$committed = true; return true;
				} finally {
					if ( ! $committed ) { $wpdb->query( 'ROLLBACK' ); }
					foreach ( self::option_keys( $slug ) as $key ) { wp_cache_delete( $key, 'options' ); }
					wp_cache_delete( 'alloptions', 'options' ); wp_cache_delete( 'notoptions', 'options' );
				}
			} finally { $wpdb = $original; $database->close(); wp_roles()->for_site(); }
		} );
	}
	public static function message( $result ) {
		$messages = array(
			'fields' => 'A role name is required.', 'permission' => 'You do not have permission to manage roles.', 'busy' => 'Another role management action is running. Retry later.',
			'protected' => 'Protected or missing roles cannot be managed here.', 'exists' => 'The role or historical ownership record already exists. It was not adopted.',
			'ownership' => 'Exclusive creator ownership could not be verified. Keep the physical role.', 'customer' => 'The customer role could not be verified.',
			'configuration' => 'Membership or Loyalty configuration still uses this role. Review the saved rules first.',
			'user' => 'A user still carries this role. Retirement preserves existing assignments.', 'claims' => 'Raw role claims require this role or cannot be verified.',
			'loyalty' => 'A saved Loyalty user level still requires this role.', 'membership' => 'Membership source state still requires this role or cannot be verified.',
			'admission' => 'A Membership purchase reservation still requires this role.', 'intent' => 'Purchase intent or source lineage still requires this role or cannot be verified.',
			'companion' => 'All three installed role-management components must support the same safety contract before hard deletion.',
			'scan_incomplete' => 'The bounded dependency scan did not finish. Keep the role retired; no deletion was performed.',
			'query' => 'Dependency data could not be read safely. No deletion was performed.', 'storage' => 'Role management could not be confirmed. Preserve the role and review its ownership recovery state.',
		);
		return $messages[ $result ] ?? $messages['storage'];
	}
}
}
