<?php
/*
Plugin Name: Pressbooks Plugin Scaffold
Plugin URI: https://pressbooks.org
Description: Scaffolding for a Pressbooks plugin.
Version: 0.3.1
Author: Pressbooks (Book Oven Inc.)
Author URI: https://pressbooks.org
Requires PHP: 7.0
Pressbooks tested up to: 5.4.1
Text Domain: pressbooks-plugin-scaffold
License: GPL v3 or later
Network: True
*/

// -------------------------------------------------------------------------------------------------------------------
// Check requirements
// -------------------------------------------------------------------------------------------------------------------
if ( ! function_exists( 'pb_meets_minimum_requirements' ) && ! @include_once( WP_PLUGIN_DIR . '/pressbooks/compatibility.php' ) ) { // @codingStandardsIgnoreLine
	add_action('admin_notices', function () {
		echo '<div id="message" class="error fade"><p>' . __( 'Cannot find Pressbooks install.', 'pressbooks-plugin-scaffold' ) . '</p></div>';
	});
	return;
} elseif ( ! pb_meets_minimum_requirements() ) {
	return;
}

// -------------------------------------------------------------------------------------------------------------------
// Class autoloader
// -------------------------------------------------------------------------------------------------------------------
\HM\Autoloader\register_class_path( 'PressbooksPluginScaffold', __DIR__ . '/inc' );

// -------------------------------------------------------------------------------------------------------------------
// Composer autoloader
// -------------------------------------------------------------------------------------------------------------------
/* if ( ! class_exists( '\SomeRequiredClass' ) ) {
	if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
		require_once __DIR__ . '/vendor/autoload.php';
	} else {
		$title = __( 'Dependencies Missing', 'pressbooks-plugin-scaffold' );
		$body = __( 'Please run <code>composer install</code> from the root of the Pressbooks Plugin Scaffold plugin directory.', 'pressbooks-plugin-scaffold' );
		$message = "<h1>{$title}</h1><p>{$body}</p>";
		wp_die( $message, $title );
	}
} */

// -------------------------------------------------------------------------------------------------------------------
// Check for updates
// -------------------------------------------------------------------------------------------------------------------
if ( ! \Pressbooks\Book::isBook() ) {
	$updater = new \Puc_v4p2_Vcs_PluginUpdateChecker(
		new \Pressbooks\Updater( 'https://github.com/pressbooks/pressbooks-plugin-scaffold/' ),
		__FILE__, // Fully qualified path to the main plugin file
		'pressbooks-plugin-scaffold',
		24
	);
	$updater->setBranch( 'master' );
}
