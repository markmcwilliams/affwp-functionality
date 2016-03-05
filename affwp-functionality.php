<?php
/**
 * Plugin Name: AffiliateWP - Functionality
 * Plugin URI: http://affiliatewp.com
 * Description: Various bits of functionality for the affiliatewp.com site
 * Author: Andrew Munro
 * Author URI: http://affiliatewp.com
 * Version: 1.0
 */

define( 'EDD_DISABLE_ARCHIVE', true );

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

add_filter( 'login_headertitle', 'affwpcf_login_headertitle' );


/**
 * Add rss image
 */
function affwp_rss_featured_image() {
    global $post;

    if ( has_post_thumbnail( $post->ID ) ) {
    	$thumbnail = wp_get_attachment_url( get_post_thumbnail_id( $post->ID ) );
    	$mime_type = get_post_mime_type( get_post_thumbnail_id( $post->ID ) );
    	?>
    	<media:content url="<?php echo $thumbnail; ?>" type="<?php echo $mime_type; ?>" medium="image" width="600" height="300"></media:content>
    <?php }
}
add_filter( 'rss2_item', 'affwp_rss_featured_image' );

/**
 * Add rss namespaces
 */
function affwp_rss_namespace() {
    echo 'xmlns:media="http://search.yahoo.com/mrss/"
    xmlns:georss="http://www.georss.org/georss"';
}
add_filter( 'rss2_ns', 'affwp_rss_namespace' );

/**
 * Get an array of excluded category IDs
 */
function affwp_custom_get_excluded_categories() {

	$excluded_categories = array(
		'exclude-from-rss'
	);

	$ids = array();

	if ( $excluded_categories ) {
		foreach ( $excluded_categories as $category ) {
			$category = get_category_by_slug( $category );
			$ids[] = $category ? $category->cat_ID : '';
		}
	}

	if ( $ids) {
		return $ids;
	}

	return false;
}

/**
 * Hide categories from categories list on site
 */
function affwp_get_object_terms( $terms, $object_ids, $taxonomies ) {

	if ( is_admin() ) {
		return $terms;
	}

    if ( $terms ) {
    	foreach ( $terms as $id => $term ) {

    		$term_id = isset( $term->term_id ) ? $term->term_id : '';

    	    if ( in_array( $term_id, affwp_custom_get_excluded_categories() ) ) {
    	        unset( $terms[$id] );
    	    }
    	}
    }

    return $terms;

}
add_filter( 'wp_get_object_terms', 'affwp_get_object_terms', 10, 3 );

/**
 * Disable jetpack carousel comments
 */
function affwp_custom_remove_comments_on_attachments( $open, $post_id ) {
    $post = get_post( $post_id );
    if( $post->post_type == 'attachment' ) {
        return false;
    }
    return $open;
}
add_filter( 'comments_open', 'affwp_custom_remove_comments_on_attachments', 10 , 2 );

/**
 * Removes styling from Better Click To Tweet plugin
 */
function affwp_remove_stuff() {

	/**
	 * Remove the Discount field
	 * Discounts can only be applied using ?discount=code
	 */
	remove_action( 'edd_checkout_form_top', 'edd_discount_field', -1 );

	/**
	 * Removes styling from Better click to tweet plugin
	 */
	remove_action('wp_enqueue_scripts', 'bctt_scripts');

	/**
	 * Removes styling from EDD Software licensing
	 */
	//remove_action( 'wp_enqueue_scripts', 'edd_sl_scripts' );
}
add_action( 'template_redirect', 'affwp_remove_stuff' );

/**
 * EDD Purchase Rewards
 * Remove discount from appearing on the purchase confirmation page
 */
add_filter( 'edd_purchase_rewards_show_discount_code', '__return_false' );


/**
 * Let the customer know the discount was successfully applied
 */
function affwp_custom_discount_successful() {

	$discount = isset( $_GET['discount'] ) && $_GET['discount'] ? $_GET['discount'] : '';

	$link  = false;
	$class = '';

	// remove link and change message on account page because they will be upgrading etc
	if ( is_page( 'account' ) ) {
		$text  = 'Woohoo! Your discount was successfully added to checkout.';
	} else {
		$text  = 'Woohoo! Your discount was successfully added to checkout. Purchase AffiliateWP now &rarr;';
		$link  = true;
		$class = ' link';
	}

	if ( ! $discount ) {
		return;
	}

	?>
	<div id="notification-area" class="discount-applied<?php echo $class; ?>">
		<div id="notice-content">

		<?php if ( $link ) : ?>
			<a href="/pricing">
		<?php endif; ?>
			<svg id="announcement" width="32px" height="32px">
			   <use xlink:href="<?php echo get_stylesheet_directory_uri() . '/images/svg-defs.svg#icon-thumbs-up'; ?>"></use>
			</svg>
			<p><strong><?php echo $text; ?></strong></p>
		<?php if ( $link ) : ?>
			</a>
		<?php endif; ?>

		</div>
	</div>
		<?php
}
add_action( 'affwp_site_before', 'affwp_custom_discount_successful' );

/**
 * Prevent Discounts on Renewals
 */
function affwp_check_if_is_renewal( $return ) {

	if ( EDD()->session->get( 'edd_is_renewal' ) ) {
		edd_set_error( 'edd-discount-error', __( 'This discount is not valid with renewals.', 'edd' ) );
		return false;
	}

	return $return;

}
//add_filter( 'edd_is_discount_valid', 'affwp_check_if_is_renewal', 99, 1 );

/**
 * Prevent Auto Register from creating user accounts when customers download free downloads
 */
function affwp_edd_auto_register_disable( $return ) {

    if ( isset( $_POST['edd_action'] ) && 'free_download_process' === $_POST['edd_action'] ) {
        $return = true;
    }

    return $return;

}
add_filter( 'edd_auto_register_disable', 'affwp_edd_auto_register_disable', 10, 1 );

/* ----------------------------------------------------------- *
 * Extensions Feed
 * ----------------------------------------------------------- */

/**
 * Register the feed
 */
function affwp_register_add_ons_feed() {
	add_feed( 'feed-add-ons', 'affwp_addons_feed' );
}
add_action( 'init', 'affwp_register_add_ons_feed' );

/**
 * Initialise the feed when requested
 */
function affwp_addons_feed() {
	load_template( STYLESHEETPATH . '/feed-add-ons.php' );
}
add_action( 'do_feed_addons', 'affwp_addons_feed', 10, 1 );

/**
 * Register the rewrite rule for the feed
 */
function affwp_feed_rewrite( $wp_rewrite ) {

	$feed_rules = array(
		'feed/(.+)' => 'index.php?feed=' . $wp_rewrite->preg_index( 1 ),
		'(.+).xml'  => 'index.php?feed=' . $wp_rewrite->preg_index( 1 )
	);

	$wp_rewrite->rules = $feed_rules + $wp_rewrite->rules;
}
add_filter( 'generate_rewrite_rules', 'affwp_feed_rewrite' );

/**
 * Alter the WordPress Query for the feed
 */
function affwp_feed_request( $request ) {

	if ( isset( $request['feed'] ) && 'feed-add-ons' == $request['feed'] ) {
		$request['post_type'] = 'download';
	}

	return $request;
}
add_filter( 'request', 'affwp_feed_request' );

/**
 * Alter the WordPress Query for the feed
 */
function affwp_feed_query( $query ) {

	if ( $query->is_feed && $query->query_vars['feed'] == 'feed-add-ons' ) {

		if ( isset( $_GET['display'] ) && 'official-free' == $_GET['display'] ) {

			$tax_query = array(
				array(
					'taxonomy' => 'download_category',
					'field'    => 'slug',
					'terms'    => 'official-free'
				)

			);

		} else {
			// pro add-ons
			$tax_query = array(

				array(
					'taxonomy' => 'download_category',
					'field'    => 'slug',
					'terms'    => 'pro-add-ons'
				)
			);
		}

		$query->set( 'posts_per_page', 100 );
		$query->set( 'tax_query', $tax_query );
		$query->set( 'orderby', 'date' );
		$query->set( 'order', 'DESC' );

	}
}
add_action( 'pre_get_posts', 'affwp_feed_query', 99999999 );
