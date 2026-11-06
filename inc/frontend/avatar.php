<?php

if (!defined('ABSPATH')) {
	exit;
}

class YOAA_WC_Advanced_Accounts_Avatar {
	const PENDING_META = '_yoaa_avatar_pending_cleanup';

    public function __construct() {
        add_filter( 'wp_privacy_personal_data_erasers', [ $this, 'register_privacy_eraser' ] );
        if (get_option('yoaa_wc_enable_avatar') === 'yes') {
            add_action( 'woocommerce_before_account_navigation', [ $this, 'add_avatar_uploader' ] );
            add_action( 'init',                             [ $this, 'process_avatar_upload' ] );
            add_action( 'init', [ $this, 'process_avatar_removal' ] );
            add_filter( 'get_avatar_data', [ $this, 'use_custom_avatar_data' ], 10, 2 );
        }
    }

    /**
     * 1) Display the avatar uploader + welcome below it
     */
    public function add_avatar_uploader() {
        if ( ! is_user_logged_in() ) {
            return;
        }

        $user_id    = get_current_user_id();
        $custom     = get_user_meta( $user_id, 'custom_avatar', true );
        $upload_to  = wc_get_account_endpoint_url( 'dashboard' );
        ?>
        <div class="yo-avatar-uploader">
            <div class="yo-avatar-wrapper">
                <?php echo get_avatar( $user_id, 120, '', wp_get_current_user()->display_name, [ 'force_display' => true ] ); ?>
                <form method="post" action="<?php echo esc_url( $upload_to ); ?>" enctype="multipart/form-data" class="yo-avatar-upload-form">
                    <?php wp_nonce_field( 'yo_avatar_upload', 'yo_avatar_upload_nonce' ); ?>
                    <input type="hidden" name="yo_avatar_upload" value="1" />
                    <label class="yo-avatar-change">
                        <input type="file" name="yo_account_avatar" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.jpe,.png,.webp" aria-label="<?php esc_attr_e( 'Change profile avatar', 'wc-advanced-accounts' ); ?>" onchange="if(this.files.length){this.form.submit();}" />
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path d="M3 7h4l2-2h6l2 2h4v13H3z"/><circle cx="12" cy="13" r="4"/></svg>
                        <span class="yo-avatar-screen-reader-text"><?php esc_html_e( 'Change profile avatar', 'wc-advanced-accounts' ); ?></span>
                    </label>
                </form>
            </div>
            <?php if ( $custom ) : ?>
                <form method="post" action="<?php echo esc_url( $upload_to ); ?>" class="yo-avatar-remove-form">
                    <?php wp_nonce_field( 'yo_avatar_remove', 'yo_avatar_remove_nonce' ); ?>
                    <button type="submit" name="yo_avatar_remove" value="1" class="button"><?php esc_html_e( 'Remove avatar', 'wc-advanced-accounts' ); ?></button>
                </form>
            <?php endif; ?>
            <div class="yo-welcome-wrapper">
                <h3>
                    <?php
                    $user           = wp_get_current_user();
                    $display_opt    = get_option( 'yoaa_wc_welcome_name_display', 'display_name' );
                    switch ( $display_opt ) {
                        case 'display_name':
                            $name = $user->display_name;
                            break;
                        case 'first_last':
                            $name = trim( $user->first_name . ' ' . $user->last_name );
                            break;
                        case 'first':
                            $name = $user->first_name;
                            break;
                        case 'last':
                            $name = $user->last_name;
                            break;
                        default:
                            $name = $user->display_name;
                    }

                    // Fallback to user_login if no name found
                    if ( empty( $name ) ) {
                        $name = $user->user_login;
                    }

                    /* translators: %s: user name based on setting */
                    printf(
                        esc_html__( 'Welcome, %s', 'wc-advanced-accounts' ),
                        esc_html( $name )
                    );
                    ?>
                </h3>
            </div>
        </div>
        <?php
    }

    public function process_avatar_removal() {
        if ( ! is_user_logged_in() || empty( $_POST['yo_avatar_remove'] ) ) { return; }
        $nonce = $_POST['yo_avatar_remove_nonce'] ?? '';
        if ( ! is_string( $nonce ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $nonce ) ), 'yo_avatar_remove' ) ) {
            wc_add_notice( __( 'Unable to remove your avatar. Please try again.', 'wc-advanced-accounts' ), 'error' );
        } elseif ( self::remove_current_avatar() ) {
            wc_add_notice( __( 'Avatar removed. Your default avatar has been restored.', 'wc-advanced-accounts' ), 'success' );
        } else {
            wc_add_notice( __( 'Unable to remove your avatar. Please try again.', 'wc-advanced-accounts' ), 'error' );
        }
        wp_safe_redirect( wc_get_account_endpoint_url( 'dashboard' ) );
        exit;
    }

    /** Current-user ownership only; unverified references never authorize file deletion. */
    private static function remove_current_avatar() {
        if ( ! is_user_logged_in() ) { return false; }
        $user_id = get_current_user_id();
        $url = get_user_meta( $user_id, 'custom_avatar', true );
        if ( ! $url ) { return true; }
        if ( self::is_avatar_url_candidate( $url ) && ! self::delete_owned_avatar( $url ) ) { return false; }
        delete_user_meta( $user_id, 'custom_avatar', $url );
        return '' === get_user_meta( $user_id, 'custom_avatar', true );
    }

    /**
     * 2a) Temporarily hijack upload_dir for our avatars
     */
    public function avatar_upload_dir( $dirs ) {
        $subdir = '/advanced_account/avatar';
        $dirs['path']    = $dirs['basedir'] . $subdir;
        $dirs['url']     = $dirs['baseurl'] . $subdir;
        $dirs['subdir']  = $subdir;
        // basedir & baseurl remain unchanged
        return $dirs;
    }

    /**
     * 2b) Process the upload: rename, resize/crop to 180×180, save URL
     */
    public function process_avatar_upload() {
		if (
			! is_user_logged_in()
			|| empty( $_POST['yo_avatar_upload'] )
			|| ! isset( $_FILES['yo_account_avatar'] )
			|| ! isset( $_POST['yo_avatar_upload_nonce'] )
			|| ! is_string( $_POST['yo_avatar_upload_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['yo_avatar_upload_nonce'] ) ), 'yo_avatar_upload' )
		) {
			return;
		}

		$user_id      = get_current_user_id();
		$file         = $_FILES['yo_account_avatar'];
		$max_size     = (int) apply_filters( 'yoaa_wc_avatar_max_file_size', 2 * MB_IN_BYTES );
		$allowed_mimes = array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'png'          => 'image/png',
			'webp'         => 'image/webp',
		);

		if (
			! is_array( $file ) ||
			empty( $file['tmp_name'] ) ||
			! isset( $file['size'], $file['name'] ) ||
			(int) $file['size'] <= 0 ||
			(int) $file['size'] > $max_size
		) {
			wc_add_notice(
				sprintf(
					/* translators: %s: maximum avatar file size. */
					__( 'Please upload a JPG, PNG or WebP image no larger than %s.', 'wc-advanced-accounts' ),
					size_format( $max_size )
				),
				'error'
			);
			wp_safe_redirect( wc_get_account_endpoint_url( 'dashboard' ) );
			exit;
		}

		$checked = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $allowed_mimes );
		if ( empty( $checked['type'] ) || empty( $checked['ext'] ) ) {
			wc_add_notice( __( 'Only valid JPG, PNG and WebP images are allowed.', 'wc-advanced-accounts' ), 'error' );
			wp_safe_redirect( wc_get_account_endpoint_url( 'dashboard' ) );
			exit;
		}

		$ext          = sanitize_file_name( $checked['ext'] );
		$file['name'] = 'avatar-' . wp_generate_uuid4() . '.' . $ext;

        // Redirect only our upload into the custom folder
        require_once ABSPATH . 'wp-admin/includes/file.php';
        add_filter( 'upload_dir', [ $this, 'avatar_upload_dir' ] );
		$movefile = wp_handle_upload( $file, [ 'test_form' => false, 'mimes' => $allowed_mimes ] );
        remove_filter( 'upload_dir', [ $this, 'avatar_upload_dir' ] );

        if ( isset( $movefile['error'] ) ) {
            wc_add_notice( __( 'Upload error: ', 'wc-advanced-accounts' ) . $movefile['error'], 'error' );
            return wp_safe_redirect( wc_get_account_endpoint_url( 'dashboard' ) );
        }

        $file_path = $movefile['file'];
        $url       = $movefile['url'];

        // Resize + crop to 180×180
        $editor = wp_get_image_editor( $file_path );
        if ( ! is_wp_error( $editor ) ) {
            $editor->set_quality( 80 );
            $editor->resize( 180, 180, true );
            $saved = $editor->save( $file_path );
            if ( ! is_wp_error( $saved ) && ! empty( $saved['path'] ) ) {
                $url = str_replace( basename( $movefile['url'] ), basename( $saved['path'] ), $movefile['url'] );
            }
        }

        // Keep the old file until the new reference is verified in storage.
        $old_url = get_user_meta( $user_id, 'custom_avatar', true );
        $new_url = esc_url_raw( $url );
        $old_pending = self::is_avatar_url_candidate( $old_url );
        if ( $old_pending && ! add_user_meta( $user_id, self::PENDING_META, $old_url, false ) ) {
            if ( ! self::delete_owned_avatar( $url ) ) {
                add_user_meta( $user_id, self::PENDING_META, $new_url, false );
            }
            wc_add_notice( __( 'Unable to save your avatar. Please try again.', 'wc-advanced-accounts' ), 'error' );
            return wp_safe_redirect( wc_get_account_endpoint_url( 'dashboard' ) );
        }
        update_user_meta( $user_id, 'custom_avatar', $new_url );
        if ( $new_url !== get_user_meta( $user_id, 'custom_avatar', true ) ) {
            if ( $old_pending ) {
                delete_user_meta( $user_id, self::PENDING_META, $old_url );
            }
            if ( ! self::delete_owned_avatar( $url ) ) {
                add_user_meta( $user_id, self::PENDING_META, $new_url, false );
            }
            wc_add_notice( __( 'Unable to save your avatar. Please try again.', 'wc-advanced-accounts' ), 'error' );
            return wp_safe_redirect( wc_get_account_endpoint_url( 'dashboard' ) );
        }
        if ( $old_pending && $old_url !== $new_url && self::delete_owned_avatar( $old_url ) ) {
            delete_user_meta( $user_id, self::PENDING_META, $old_url );
        }
        wc_add_notice( __( 'Avatar updated successfully!', 'wc-advanced-accounts' ), 'success' );

        wp_safe_redirect( wc_get_account_endpoint_url( 'dashboard' ) );
        exit;
    }

    private static function is_avatar_url_candidate( $url ) {
        $uploads = wp_upload_dir();
        if ( ! is_string( $url ) || ! empty( $uploads['error'] ) || empty( $uploads['baseurl'] ) ) {
            return false;
        }
        $prefix = trailingslashit( $uploads['baseurl'] ) . 'advanced_account/avatar/';
        if ( 0 !== strpos( $url, $prefix ) ) {
            return false;
        }
        $name = substr( $url, strlen( $prefix ) );
        return '' !== $name && '.' !== $name && '..' !== $name &&
            false === strpos( $name, '/' ) && false === strpos( $name, '\\' ) &&
            false === strpos( $name, '?' ) && false === strpos( $name, '#' ) &&
            false === strpos( $name, '%' );
    }

    /** Delete only a regular file directly inside the canonical Advanced Accounts avatar directory. */
    private static function delete_owned_avatar( $url ) {
        $uploads = wp_upload_dir();
        if ( ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) || empty( $uploads['baseurl'] ) ) {
            return false;
        }
        if ( ! self::is_avatar_url_candidate( $url ) ) {
            return false;
        }
        $prefix = trailingslashit( $uploads['baseurl'] ) . 'advanced_account/avatar/';
        $name = substr( $url, strlen( $prefix ) );

        $directory = realpath( trailingslashit( $uploads['basedir'] ) . 'advanced_account/avatar' );
        $base      = realpath( $uploads['basedir'] );
        $parent    = realpath( trailingslashit( $uploads['basedir'] ) . 'advanced_account' );
        if ( false === $directory || false === $parent || false === $base || dirname( $directory ) !== $parent || dirname( $parent ) !== $base ) {
            return false;
        }
        if ( is_link( $uploads['basedir'] . '/advanced_account' ) || is_link( $uploads['basedir'] . '/advanced_account/avatar' ) ) {
            return false;
        }

        $path = $directory . '/' . $name;
        if ( ! file_exists( $path ) && ! is_link( $path ) ) {
            return true;
        }
        if ( is_link( $path ) || ! is_file( $path ) || realpath( $path ) !== $path ) {
            return false;
        }
        return @unlink( $path );
    }

    public function register_privacy_eraser( $erasers ) {
        $erasers['yoaa-avatar'] = [
            'eraser_friendly_name' => __( 'Advanced Accounts avatar', 'wc-advanced-accounts' ),
            'callback'             => [ __CLASS__, 'privacy_eraser' ],
        ];
        return $erasers;
    }

    public static function privacy_eraser( $email_address, $page = 1 ) {
        $user = get_user_by( 'email', $email_address );
        $result = [ 'items_removed' => false, 'items_retained' => false, 'messages' => [], 'done' => true ];
        if ( ! $user ) {
            return $result;
        }
        $url = get_user_meta( $user->ID, 'custom_avatar', true );
        if ( $url ) {
            if ( self::is_avatar_url_candidate( $url ) ) {
                if ( self::delete_owned_avatar( $url ) ) {
                    $result['items_removed'] = (bool) delete_user_meta( $user->ID, 'custom_avatar' );
                } else {
                    $result['items_retained'] = true;
                    $result['messages'][] = __( 'The owned avatar file could not be deleted and remains available for a later erasure attempt.', 'wc-advanced-accounts' );
                }
            } else {
                $result['items_removed'] = (bool) delete_user_meta( $user->ID, 'custom_avatar' );
                $result['items_retained'] = true;
                $result['messages'][] = __( 'The avatar URL was removed, but its file was not verified as Advanced Accounts-owned.', 'wc-advanced-accounts' );
            }
        }
        foreach ( (array) get_user_meta( $user->ID, self::PENDING_META, false ) as $pending_url ) {
            if ( self::is_avatar_url_candidate( $pending_url ) && self::delete_owned_avatar( $pending_url ) ) {
                $result['items_removed'] = (bool) delete_user_meta( $user->ID, self::PENDING_META, $pending_url ) || $result['items_removed'];
            } else {
                $result['items_retained'] = true;
                $result['messages'][] = __( 'An earlier avatar file remains queued for cleanup.', 'wc-advanced-accounts' );
            }
        }
        return $result;
    }

    /** WordPress/theme owns the final image markup and all presentation arguments. */
    public function use_custom_avatar_data( $args, $id_or_email ) {
        if ( isset( $args['url'] ) && false === $args['url'] ) { return $args; }
        $user = false;
        if ( is_numeric( $id_or_email ) ) {
            $user = get_user_by( 'id', (int) $id_or_email );
        } elseif ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
            $user = get_user_by( 'email', $id_or_email );
        } elseif ( $id_or_email instanceof WP_User ) {
            $user = $id_or_email;
        } elseif ( $id_or_email instanceof WP_Post ) {
            $user = get_user_by( 'id', (int) $id_or_email->post_author );
        } elseif ( is_object( $id_or_email ) ) {
            if ( ! empty( $id_or_email->user_id ) ) { $user = get_user_by( 'id', (int) $id_or_email->user_id ); }
            if ( ! $user && ! empty( $id_or_email->comment_author_email ) && is_email( $id_or_email->comment_author_email ) ) { $user = get_user_by( 'email', $id_or_email->comment_author_email ); }
        }
        if ( $user && $custom = get_user_meta( $user->ID, 'custom_avatar', true ) ) {
            $args['url'] = esc_url_raw( $custom );
            $args['found_avatar'] = true;
        }
        return $args;
    }

}

// instantiate
add_action( 'plugins_loaded', static function() {
    if ( class_exists( 'YOAA_WC_Advanced_Accounts_Avatar_Premium' ) && class_exists( 'WCAAP_Activation' ) && WCAAP_Activation::is_premium_active() ) {
        return;
    }
    new YOAA_WC_Advanced_Accounts_Avatar();
}, 110 );
