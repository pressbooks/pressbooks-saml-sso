<?php

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	$plugin_dir = dirname( __DIR__ );
	$app_dir = dirname( dirname( __DIR__ ) );
	require  $app_dir . '/pressbooks/pressbooks.php';
	require  $app_dir . '/pressbooks/requires.php';
	require  $app_dir . '/pressbooks/requires-admin.php';
	require  $plugin_dir . '/pressbooks-saml-sso.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
