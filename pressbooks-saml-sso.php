<?php

/**
 * Plugin Name:         Pressbooks SAML2 Single Sign-On
 * Plugin URI:          https://pressbooks.org
 * Description:         SAML2 Single Sign-On integration for Pressbooks (Shibboleth, Microsoft ADFS, Google Apps, etc.)
 * Version:             2.5.0
 * Requires at least:   6.5
 * Requires PHP:        8.1
 * Requires Plugins:    pressbooks
 * Author:              Pressbooks (Book Oven Inc.)
 * Author URI:          https://pressbooks.org
 * License:             GPL v3 or later
 * License URI:         https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:         pressbooks-saml-sso
 * Domain Path:         /languages
 * Network:             True
 * GitHub Plugin URI:   pressbooks/pressbooks-saml-sso
 * Release Asset:       true
 */

add_action('plugins_loaded', function () {
    \Pressbooks\Container::get('Blade')->addNamespace('PressbooksSamlSso', __DIR__ . '/templates');
});
add_action('plugins_loaded', [ '\PressbooksSamlSso\SAML', 'init' ]);
add_action('plugins_loaded', [ '\PressbooksSamlSso\Admin', 'init' ]);
