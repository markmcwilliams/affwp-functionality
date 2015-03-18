<?php
/**
 * Plugin Name: AffiliateWP - Functionality
 * Plugin URI: http://affiliatewp.com
 * Description: Various bits of functionality
 * Author: Andrew Munro
 * Author URI: http://affiliatewp.com
 * Version: 1.0
 */


/**
 * Sends an email to myself whenever a customer shares their purchase to receive a discount. Just so I can keep on eye on if it's working.
 */
function affwpcf_notify_when_purchase_shared() {
	$subject = 'Customer shared purchase!';
	$message = 'Looks like a customer has shared their purchase!';

	wp_mail( 'andrew@affiliatewp.com', $subject, $message );
}
add_action( 'edd_purchase_rewards_after_share', 'affwpcf_notify_when_purchase_shared' );

/**
 * Remove linkedin from purchase rewards plugin
 * LinkedIn's share event API is busted as of May 21st 2014
 */
function affwpcf_eddpr_sharing_networks( $networks ) {

	foreach ( $networks as $key => $network ) {
	    if ( $key == 'linkedin' ) {
	        $key = $key;
	    }
	}

	unset( $networks[ $key ] );

	return $networks;
}
add_filter( 'edd_purchase_rewards_sharing_networks', 'affwpcf_eddpr_sharing_networks' );

/**
 * Redirect users when logging in via wp-login.php (aka wp-admin)
 * This also includes /account or /account/affiliates
 */
function affwpcf_login_redirect( $user_login, $user ) {
	$user_id = $user->ID;

	// skip admins
	if ( in_array( 'administrator', $user->roles ) ) {
		return;
	}

	// skip EDD pages or if we came from the checkout
	if ( ( edd_is_checkout() || edd_is_success_page() ) || wp_get_referer() == edd_get_checkout_uri() ) {
		return;
	}

	// Affiliates should go to affiliate area
	if ( function_exists( 'affwp_is_affiliate' ) && affwp_is_affiliate( $user_id ) ) {
		$redirect = affiliate_wp()->login->get_login_url();
	}
	// Customers should go to account page
	else {
		$redirect = site_url( '/account' );
	}

	wp_redirect( $redirect ); exit;
}
add_action( 'wp_login', 'affwpcf_login_redirect', 10, 2 );


/**
 * Redirect affiliates and customers when they log out of WordPress
 * By default, a user is sent to the wp-login.php?loggedout=true page
 * 
 * Affiliates are logged out to the affiliate dashboard login screen
 * Customers (subscribers) are logged out and redirected to the account login page
 */
function affwpcf_logout_redirect( $logout_url, $redirect ) {

	if ( current_user_can( 'manage_options' ) ) {
		// skip admins
		return $logout_url;
	}

	if ( function_exists( 'affwp_is_affiliate' ) && affwp_is_affiliate() ) {
		// redirect affiliates to affiliate login page
		$redirect = affiliate_wp()->login->get_login_url();
	} else {
		// Customers should go to account login page
		$redirect = site_url( '/account' );
	}

	$args = array( 'action' => 'logout' );

	if ( ! empty( $redirect ) ) {
		$args['redirect_to'] = urlencode( $redirect );
	}

    return add_query_arg( $args, $logout_url );
}
add_filter( 'logout_url', 'affwpcf_logout_redirect', 10, 2 );

/**
 * Add AffiliateWP logo to wp-login.php page
 */
function affwpcf_login_logo() {
	echo '<style type="text/css"> .login h1 a { background-size: auto; width: auto; background-image:url('.get_bloginfo( 'stylesheet_directory' ).'/images/admin-logo.png) !important; height: 66px; padding-bottom:0; margin-bottom: 16px; } </style>';
}
add_action( 'login_head', 'affwpcf_login_logo' );

/**
 * Change the login header URL
 */
function affwpcf_login_headerurl() {
	return 'https://affiliatewp.com';
}
add_filter( 'login_headerurl', 'affwpcf_login_headerurl' );

/**
 * Change the login header title
 */
function affwpcf_login_headertitle() {
	return 'AffiliateWP';
}
add_filter( 'login_headertitle', 'affwpcf_login_headertitle' );