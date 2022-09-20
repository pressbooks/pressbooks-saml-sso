<?php
/**
 * Plugin Name:         Pressbooks SAML2 Single Sign-On
 * Plugin URI:          https://pressbooks.org
 * Description:         SAML2 Single Sign-On integration for Pressbooks (Shibboleth, Microsoft ADFS, Google Apps, etc.)
 * Version:             1.7.0
 * Requires at least:   6.0.2
 * Requires PHP:        7.4
 * Author:              Pressbooks (Book Oven Inc.)
 * Author URI:          https://pressbooks.org
 * License:             GPL v3 or later
 * License URI:         https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:         pressbooks-saml-sso
 * Network:             True
 * GitHub Plugin URI:   pressbooks/pressbooks-saml-sso
 * Release Asset:       true
 */

// -------------------------------------------------------------------------------------------------------------------
// Check requirements
// -------------------------------------------------------------------------------------------------------------------

if ( ! function_exists( 'pb_meets_minimum_requirements' ) && ! @include_once( WP_PLUGIN_DIR . '/pressbooks/compatibility.php' ) ) { // @codingStandardsIgnoreLine
	add_action(
		'admin_notices', function () {
			echo '<div id="message" role="alert" class="error fade"><p>' . __( 'Cannot find Pressbooks install.', 'pressbooks-saml-sso' ) . '</p></div>';
		}
	);
	return;
} elseif ( ! pb_meets_minimum_requirements() ) {
	return;
}

// -------------------------------------------------------------------------------------------------------------------
// Class autoloader
// -------------------------------------------------------------------------------------------------------------------

\HM\Autoloader\register_class_path( 'PressbooksSamlSso', __DIR__ . '/inc' );

// -------------------------------------------------------------------------------------------------------------------
// Composer autoloader
// -------------------------------------------------------------------------------------------------------------------

if ( ! class_exists( '\OneLogin\Saml2\Auth' ) ) {
	if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
		require_once __DIR__ . '/vendor/autoload.php';
	} else {
		$title = __( 'Dependencies Missing', 'pressbooks-cas-sso' );
		$body = __( 'Please run <code>composer install</code> from the root of the Pressbooks SAML2 Single Sign-On plugin directory.', 'pressbooks-saml-sso' );
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
add_action( 'plugins_loaded', function() {
	\Pressbooks\Container::get( 'Blade' )->addNamespace( 'PressbooksSamlSso', __DIR__ . '/templates' );
} );
add_action( 'plugins_loaded', [ '\PressbooksSamlSso\SAML', 'init' ] );
add_action( 'plugins_loaded', [ '\PressbooksSamlSso\Admin', 'init' ] );
