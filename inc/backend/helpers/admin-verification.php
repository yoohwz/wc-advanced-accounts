<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Manual admin overrides only; never creates OTP/provider verification evidence. */
final class YOAA_AAC_Admin_Verification {
	public static function mutate( $user_id, array $requested, $dry_run = false ) {
		$user_id = absint( $user_id );
		$result = [ 'status' => 'error', 'reason' => 'Invalid verification request.', 'restored' => null ];
		if ( ! current_user_can( 'edit_users' ) || ! current_user_can( 'edit_user', $user_id ) ) {
			return array_replace( $result, [ 'status' => 'forbidden', 'reason' => 'No authority to edit this user.' ] );
		}
		$user = get_userdata( $user_id );
		if ( ! $user ) { return array_replace( $result, [ 'reason' => 'User does not exist.' ] ); }
		if ( in_array( 'administrator', (array) $user->roles, true ) || is_super_admin( $user_id ) ) {
			return array_replace( $result, [ 'status' => 'skipped', 'reason' => 'Administrator verification enforcement is excluded.' ] );
		}
		if ( ! $requested ) { return array_replace( $result, [ 'status' => 'skipped', 'reason' => 'No verification channel selected.' ] ); }
		foreach ( $requested as $key => $value ) {
			if ( ! in_array( $key, [ 'email_verification' ], true ) || ! in_array( $value, [ 0, 1, '0', '1' ], true ) ) { return $result; }
			$requested[ $key ] = (string) $value;
		}
		$before = []; $started = false;
		try {
			foreach ( $requested as $key => $value ) { $before[ $key ] = self::read( $user_id, $key ); }
			$changed = false;
			foreach ( $requested as $key => $value ) { $changed = $changed || [ $value ] !== $before[ $key ]; }
			if ( ! $changed ) { return array_replace( $result, [ 'status' => 'unchanged', 'reason' => 'Already at the requested state.' ] ); }
			if ( $dry_run ) { return array_replace( $result, [ 'status' => 'would_update', 'reason' => 'Dry-run; no metadata written.' ] ); }
			$started = true;
			foreach ( $requested as $key => $value ) {
				if ( [ $value ] !== $before[ $key ] && false === update_user_meta( $user_id, $key, $value ) ) { throw new RuntimeException( 'Verification write failed.' ); }
				if ( [ $value ] !== self::read( $user_id, $key ) ) { throw new RuntimeException( 'Verification read-back failed.' ); }
			}
			// Check the whole selected set after the last write, including unchanged channels.
			foreach ( $requested as $key => $value ) {
				if ( [ $value ] !== self::read( $user_id, $key ) ) { throw new RuntimeException( 'Verification postcondition failed.' ); }
			}
			return array_replace( $result, [ 'status' => 'updated', 'reason' => 'Requested verification state persisted.' ] );
		} catch ( Throwable $error ) {
			$restored = null;
			if ( $started ) {
				$restored = true;
				foreach ( $before as $key => $values ) {
					try {
						if ( self::read( $user_id, $key ) !== $values ) {
							delete_user_meta( $user_id, $key );
							// Do not append old rows if deletion was suppressed.
							if ( [] !== self::read( $user_id, $key ) ) { throw new RuntimeException( 'Restoration delete failed.' ); }
							foreach ( $values as $value ) { add_user_meta( $user_id, $key, wp_slash( maybe_unserialize( $value ) ) ); }
						}
						if ( self::read( $user_id, $key ) !== $values ) { $restored = false; }
					} catch ( Throwable $restore_error ) { $restored = false; }
				}
			}
			return array_replace( $result, [ 'restored' => $restored, 'reason' => false === $restored ? 'Verification failed; prior state could not be fully restored. Inspect this user.' : 'Verification failed; no successful change reported.' ] );
		} finally {
			wp_cache_delete( $user_id, 'user_meta' );
		}
	}

	private static function read( $user_id, $key ) {
		global $wpdb;
		$values = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = %s ORDER BY umeta_id", $user_id, $key ) );
		if ( $wpdb->last_error || ! is_array( $values ) ) { throw new RuntimeException( 'Verification lookup failed.' ); }
		return array_map( 'strval', $values );
	}
}
