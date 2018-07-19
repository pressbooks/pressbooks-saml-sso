<?php
/*
Plugin Name: Pressbooks Shibboleth Single Sign-On
Plugin URI: https://pressbooks.org
Description: Shibboleth Single Sign-On integration for Pressbooks.
Version: 0.0.1
Author: Pressbooks (Book Oven Inc.)
Author URI: https://pressbooks.org
Requires PHP: 7.0
Pressbooks tested up to: 5.4.1
Text Domain: pressbooks-shibboleth-sso
License: GPL v3 or later
Network: True
*/

// -------------------------------------------------------------------------------------------------------------------
// Check requirements
// -------------------------------------------------------------------------------------------------------------------

if ( ! function_exists( 'pb_meets_minimum_requirements' ) && ! @include_once( WP_PLUGIN_DIR . '/pressbooks/compatibility.php' ) ) { // @codingStandardsIgnoreLine
	add_action('admin_notices', function () {
		echo '<div id="message" class="error fade"><p>' . __( 'Cannot find Pressbooks install.', 'pressbooks-shibboleth-sso' ) . '</p></div>';
	});
	return;
} elseif ( ! pb_meets_minimum_requirements() ) {
	return;
}

// -------------------------------------------------------------------------------------------------------------------
// Class autoloader
// -------------------------------------------------------------------------------------------------------------------

\HM\Autoloader\register_class_path( 'Pressbooks\Shibboleth', __DIR__ . '/inc' );

// -------------------------------------------------------------------------------------------------------------------
// Composer autoloader
// -------------------------------------------------------------------------------------------------------------------

if ( ! class_exists( '\SimpleSAML\Auth\Simple' ) ) {
	if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
		require_once __DIR__ . '/vendor/autoload.php';
	} else {
		$title = __( 'Dependencies Missing', 'pressbooks-cas-sso' );
		$body = __( 'Please run <code>composer install</code> from the root of the Pressbooks Shibboleth Single Sign-On plugin directory.', 'pressbooks-shibboleth-sso' );
		$message = "<h1>{$title}</h1><p>{$body}</p>";
		wp_die( $message, $title );
	}
}

// -------------------------------------------------------------------------------------------------------------------
// Requires
// -------------------------------------------------------------------------------------------------------------------

require( __DIR__ . '/inc/namespace.php' );

// -------------------------------------------------------------------------------------------------------------------
// Hooks
// -------------------------------------------------------------------------------------------------------------------

add_action( 'plugins_loaded', [ '\Pressbooks\Shibboleth\Updates', 'init' ] );
add_action( 'plugins_loaded', [ '\Pressbooks\Shibboleth\Shibboleth', 'init' ] );
add_action( 'plugins_loaded', [ '\Pressbooks\Shibboleth\Admin', 'init' ] );
