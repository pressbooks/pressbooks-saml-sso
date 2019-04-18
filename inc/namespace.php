<?php

namespace PressbooksSamlSso;

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
		$login_url = get_site_url( 1, 'wp-login.php', 'login' );
		$login_url = apply_filters( 'login_url', $login_url, '', false );
	}
	$login_url = add_query_arg( 'action', SAML::LOGIN_PREFIX, $login_url );
	$login_url = \Pressbooks\Sanitize\maybe_https( $login_url );
	return $login_url;
}

/**
 * SAML Metadata URL
 *
 * @return string
 */
function metadata_url() {
	return add_query_arg( 'action', SAML::LOGIN_PREFIX . '_metadata', login_url() );
}

/**
 * SAML Assertion Consumer Service URL
 *
 * @return string
 */
function acs_url() {
	return add_query_arg( 'action', SAML::LOGIN_PREFIX . '_acs', login_url() );
}

/**
 * SAML Single Logout Service URL
 *
 * @return string
 */
function sls_url() {
	return add_query_arg( 'action', SAML::LOGIN_PREFIX . '_sls', login_url() );
}
