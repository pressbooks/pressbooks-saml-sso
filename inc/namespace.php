<?php

namespace Pressbooks\Shibboleth;

/**
 * @return \Jenssegers\Blade\Blade
 */
function blade() {
	$views = __DIR__ . '/../templates';
	$cache = \Pressbooks\Utility\get_cache_path();
	$blade = new \Jenssegers\Blade\Blade( $views, $cache, new \Pressbooks\Container() );
	return $blade;
}

/**
 * SAML Login URL
 *
 * @return string
 */
function login_url() {
	if ( is_subdomain_install() ) {
		$login_url = network_site_url( '/wp-login.php' );
	} else {
		$login_url = wp_login_url();
	}
	$login_url = add_query_arg( 'action', 'pb_shibboleth', $login_url );
	$login_url = \Pressbooks\Sanitize\maybe_https( $login_url );
	return $login_url;
}

/**
 * SAML Metadata URL
 *
 * @return string
 */
function metadata_url() {
	return add_query_arg( 'saml', 'metadata', login_url() );
}

/**
 * SAML Assertion Consumer Service URL
 *
 * @return string
 */
function acs_url() {
	return add_query_arg( 'saml', 'acs', login_url() );
}

/**
 * SAML Single Logout Service URL
 *
 * @return string
 */
function sls_url() {
	return add_query_arg( 'saml', 'sls', login_url() );
}
