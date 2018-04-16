<?php
/**
 * Functions that can be used everywhere.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Retrieve pages from the database and cache them as transient.
 *
 * @return array
 */
function wpum_get_pages( $force = false ) {

	$pages = [];

	if ( ( ! isset( $_GET['page'] ) || 'wpum-settings' != $_GET['page'] ) && ! $force ) {
		return $pages;
	}

	$transient =  get_transient( 'wpum_get_pages' );

	if ( $transient ) {
		$pages = $transient;
	} else {
		$available_pages = get_pages();
		if ( ! empty( $available_pages ) ) {
			foreach ( $available_pages as $page ) {
				$pages[] = array(
					'value' => $page->ID,
					'label' => $page->post_title
				);
			}
			set_transient( 'wpum_get_pages', $pages, DAY_IN_SECONDS );
		}
	}
	return $pages;
}

/**
 * Retrieve the options for the available login methods.
 *
 * @return array
 */
function wpum_get_login_methods() {
	return apply_filters( 'wpum_get_login_methods', array(
		'username'       => __( 'Username only', 'wpum' ),
		'email'          => __( 'Email only', 'wpum' ),
		'username_email' => __( 'Username or Email', 'wpum' ),
	) );
}

/**
 * Retrieve a list of all user roles and cache them into a transient.
 *
 * @return array
 */
function wpum_get_roles( $force = false ) {

	$roles = [];

	if ( ( ! isset( $_GET['page'] ) || 'wpum-settings' != $_GET['page'] ) && ! $force ) {
		return $roles;
	}

	$transient =  get_transient( 'wpum_get_roles' );

	if ( $transient ) {
		$roles = $transient;
	} else {

		global $wp_roles;
		$available_roles = $wp_roles->get_names();

		foreach ( $available_roles as $role_id => $role ) {
			if( $role_id == 'administrator' ) {
				continue;
			}
			$roles[] = array(
				'value' => esc_attr( $role_id ),
				'label' => esc_html( $role ),
			);
		}
		set_transient( 'wpum_get_roles', $roles, DAY_IN_SECONDS );

	}

	return $roles;

}

/**
 * Retrieve the ID of a WPUM core page.
 *
 * @param string $page Available core pages are login, register, password, account, profile.
 * @return int $page_id the ID of the requested page.
 */
function wpum_get_core_page_id( $page = null ) {

	if( ! $page ) {
		return;
	}

	$id = null;

	switch( $page ) {
		case 'login':
			$id = wpum_get_option( 'login_page' );
			break;
		case 'register':
			$id = wpum_get_option( 'registration_page' );
			break;
		case 'password':
			$id = wpum_get_option( 'password_recovery_page' );
			break;
		case 'account':
			$id = wpum_get_option( 'account_page' );
			break;
		case 'profile':
			$id = wpum_get_option( 'profile_page' );
			break;
		case 'registration-confirmation':
			$id = wpum_get_option( 'registration_redirect' );
			break;
		case 'login-redirect':
			$id = wpum_get_option( 'login_redirect' );
			break;
		case 'logout-redirect':
			$id = wpum_get_option( 'logout_redirect' );
			break;
	}

	$id = is_array( $id ) ? $id[0] : false;

	return $id;

}

/**
 * Pluck a certain field out of each object in a list.
 *
 * This has the same functionality and prototype of
 * array_column() (PHP 5.5) but also supports objects.
 *
 * @param array      $list      List of objects or arrays
 * @param int|string $field     Field from the object to place instead of the entire object
 * @param int|string $index_key Optional. Field from the object to use as keys for the new array.
 *                              Default null.
 *
 * @return array Array of found values. If `$index_key` is set, an array of found values with keys
 *               corresponding to `$index_key`. If `$index_key` is null, array keys from the original
 *               `$list` will be preserved in the results.
 */
function wpum_list_pluck( $list, $field, $index_key = null ) {
	if ( ! $index_key ) {
		/**
		 * This is simple. Could at some point wrap array_column()
		 * if we knew we had an array of arrays.
		 */
		foreach ( $list as $key => $value ) {
			if ( is_object( $value ) ) {
				if ( isset( $value->$field ) ) {
					$list[ $key ] = $value->$field;
				}
			} else {
				if ( isset( $value[ $field ] ) ) {
					$list[ $key ] = $value[ $field ];
				}
			}
		}
		return $list;
	}
	/*
	 * When index_key is not set for a particular item, push the value
	 * to the end of the stack. This is how array_column() behaves.
	 */
	$newlist = array();
	foreach ( $list as $value ) {
		if ( is_object( $value ) ) {
			if ( isset( $value->$index_key ) ) {
				$newlist[ $value->$index_key ] = $value->$field;
			} else {
				$newlist[] = $value->$field;
			}
		} else {
			if ( isset( $value[ $index_key ] ) ) {
				$newlist[ $value[ $index_key ] ] = $value[ $field ];
			} else {
				$newlist[] = $value[ $field ];
			}
		}
	}
	$list = $newlist;
	return $list;
}

/**
 * Retrieve the correct label for the login form.
 *
 * @return string
 */
function wpum_get_login_label() {

	$label        = esc_html__( 'Username' );
	$login_method = wpum_get_option( 'login_method' );

	if( $login_method == 'email' ) {
		$label = esc_html__( 'Email' );
	} elseif( $login_method == 'username_email' ) {
		$label = esc_html__( 'Username or email' );
	}

	return $label;

}

/**
 * Retrieve the url where to redirect the user after login.
 *
 * @return string
 */
function wpum_get_login_redirect() {

	$redirect_to = wpum_get_option( 'login_redirect' );
	$url         = home_url();

	if( ! empty( $redirect_to ) && is_array( $redirect_to ) ) {
		$url = get_permalink( $redirect_to[0] );
	}

	return apply_filters( 'wpum_get_login_redirect', esc_url( $url ) );

}

/**
 * Replace during email parsing characters.
 *
 * @param string $str
 * @return void
 */
function wpum_starmid( $str ) {
    switch ( strlen( $str ) ) {
        case 0: return false;
        case 1: return $str;
        case 2: return $str[0] . "*";
        default: return $str[0] . str_repeat( "*", strlen($str) - 2 ) . substr($str, -1);
    }
}

/**
 * Mask an email address.
 *
 * @param string $email_address
 * @return void
 */
function wpum_mask_email_address( $email_address ) {

	if ( ! filter_var( $email_address, FILTER_VALIDATE_EMAIL ) ) {
        return false;
    }

	list( $u, $d ) = explode( "@", $email_address );

	$d   = explode( ".", $d );
	$tld = array_pop( $d );
	$d   = implode( ".", $d );

    return wpum_starmid( $u ) . "@" . wpum_starmid( $d ) . ".$tld";

}

/**
 * Check if registrations are enabled on the site.
 *
 * @return boolean
 */
function wpum_is_registration_enabled() {

	$enabled = get_option( 'users_can_register' );

	return $enabled;

}

/**
 * Retrieve an array of disabled usernames.
 *
 * @return array
 */
function wpum_get_disabled_usernames() {
	$usernames = array();
	if ( wpum_get_option( 'exclude_usernames' ) ) {
		$list = trim( wpum_get_option( 'exclude_usernames' ) );
		$list = explode( "\n", str_replace( "\r", "", $list ) );
		foreach ( $list as $username ) {
			$usernames[] = $username;
		}
	}
	return array_flip( $usernames );
}

/**
 * Programmatically log a user in given an email address or user id.
 *
 * This function should usually be followed by a redirect.
 *
 * @param mixed $email_or_id
 * @return void
 */
function wpum_log_user_in( $email_or_id ) {

	$get_by = 'id';

	if( is_email( $email_or_id ) ) {
		$get_by = 'email';
	}

	$user     = get_user_by( $get_by, $email_or_id );
	$user_id  = $user->ID;
	$username = $user->user_login;

	wp_set_current_user( $user_id, $username );
	wp_set_auth_cookie( $user_id );
	do_action( 'wp_login', $username );

}

/**
 * Send the registration confirmation email to a given user id.
 * Display the randomly generated password if any is given.
 *
 * @param int $user_id
 * @param mixed $psw
 * @return void
 */
function wpum_send_registration_confirmation_email( $user_id, $psw = false ) {

	$registration_confirmation_email = wpum_get_email( 'registration_confirmation' );

	if( ! $user_id ) {
		return;
	}

	if( is_array( $registration_confirmation_email ) && ! empty( $registration_confirmation_email ) ) {

		$user = get_user_by( 'id', $user_id );

		// The blogname option is escaped with esc_html on the way into the database in sanitize_option
		// we want to reverse this for the plain text arena of emails.
		$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		// Send notification to admin if not disabled.
		if ( ! wpum_get_option( 'disable_admin_register_email' ) ) {
			$message  = sprintf( esc_html__( 'New user registration on your site %s:' ), $blogname ) . "\r\n\r\n";
			$message .= sprintf( esc_html__( 'Username: %s' ), $user->user_login ) . "\r\n\r\n";
			$message .= sprintf( esc_html__( 'E-mail: %s' ), $user->user_email ) . "\r\n";
			wp_mail( get_option( 'admin_email' ), sprintf( esc_html__( '[%s] New User Registration' ), $blogname ), $message );
		}

		if( $user instanceof WP_User ) {

			$emails = new WPUM_Emails;
			$emails->__set( 'user_id', $user_id );
			$emails->__set( 'heading', $registration_confirmation_email['title'] );

			if( ! empty( $psw ) ) {
				$emails->__set( 'plain_text_password', $psw );
			}

			$email   = $user->data->user_email;
			$subject = $registration_confirmation_email['subject'];
			$message = $registration_confirmation_email['content'];
			$emails->send( $email, $subject, $message );
			$emails->__set( 'plain_text_password', null );

		}

	}

}
