=== Pressbooks Shibboleth Single Sign-On ===
Contributors: conner_bw, greatislander
Donate link: https://opencollective.com/pressbooks/
Tags: pressbooks, saml, saml2, sso, shibboleth
Requires at least: 4.9.8
Tested up to: 4.9.8
Stable tag: 0.0.5
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Shibboleth Single Sign-On integration for Pressbooks.

== Description ==

[![Packagist](https://img.shields.io/packagist/v/pressbooks/pressbooks-shibboleth-sso.svg?style=flat-square)](https://packagist.org/packages/pressbooks/pressbooks-shibboleth-sso) [![GitHub release](https://badgen.net/github/release/pressbooks/pressbooks-shibboleth-sso/stable?style=flat)](https://github.com/pressbooks/pressbooks-shibboleth-sso/releases) [![Travis](https://badgen.net/travis/pressbooks/pressbooks-shibboleth-sso.svg?style=flat)](https://travis-ci.com/pressbooks/pressbooks-shibboleth-sso/) [![Codecov](https://badgen.net/codecov/c/github/pressbooks/pressbooks-shibboleth-sso?style=flat)](https://codecov.io/gh/pressbooks/pressbooks-shibboleth-sso)

Plugin to integrate Pressbooks with a [Shibboleth](https://www.shibboleth.net/) single sign-on service.

Users who attempt to login to Pressbooks are redirected to a Shibboleth or SAML2 Identity Provider. After the user’s credentials are verified, they are redirected back to the
Pressbooks network. If the Shibboleth UID matches the Pressbooks username, the user is recognized as valid and allowed access. If the Shibboleth user does not have an account in
Pressbooks, a new user can be created, or access can be refused, depending on the configuration.

== Installation ==

```
composer require pressbooks/pressbooks-shibboleth-sso
```

Or, download the latest version from the releases page and unzip it into your WordPress plugin directory: https://github.com/pressbooks/pressbooks-shibboleth-sso/releases

Then, create the necessary certificates:

```
cd vendor/onelogin/php-saml/certs
openssl req -newkey rsa:2048 -new -x509 -days 3652 -nodes -out sp.crt -keyout sp.key
```

Then, activate and configure the plugin at the Network level.

= Optional Config =

Generating certificates in `vendor/onelogin/php-saml/certs`, without further changes, will expose them to malicious users (Ie. `https://path/to/vendor/onelogin/php-saml/certs/sp.crt`).
Furthermore, your certificates are at risk of being deleted when updating packages using `composer update` or similar commands. A competent sysadmin must make sure certificates are
not accessible from the internet nor deleted. It is highly recommended that you pass your certificates via configuration variables. Example:

```php
add_filter( 'pb_saml_auth_settings', function( $config ) {
	$config['sp']['x509cert'] = file_get_contents( '/path/to/sp.key' );
	$config['sp']['privateKey'] = file_get_contents( '/path/to/sp.crt' );
	return $config;
} );
```

Or:

```php
define( 'PHP_SAML_SP_KEY_PATH', '/path/to/sp.key' );
define( 'PHP_SAML_SP_CERT_PATH', '/path/to/sp.crt' );
```

Because this plugin uses the fabulous [onelogin/php-saml](https://github.com/onelogin/php-saml/tree/3.0.0) toolkit, [many other configuration variables can be tweaked](https://github.com/onelogin/php-saml/tree/3.0.0#settings).

== Screenshots ==

![Pressbooks Shibboleth Administration.](screenshot-1.png)

== Changelog ==

= 0.0.5 =
**Patches**
* [Security] Bump robrichards/xmlseclibs from 3.0.1 to 3.0.2: [#8](https://github.com/pressbooks/pressbooks/shibboleth-sso/pulls/8)

= 0.0.4 =
 * New `pb_integrations_multidomain_email` filter
 * Associate existing users with either mail or eduPersonPrincipalName

= 0.0.3 =
* Use certificate to set Valid Until
* Interoperable SAML 2.0 Web Browser SSO Profile
* Improve error message when login fails

= 0.0.2 =
* Add feature to auto-config from IdP metadata
* Remove ampersand character from SP entityID

= 0.0.1 =
* Initial Release

== Upgrade Notice ==

= 0.0.4 =
* Pressbooks Shibboleth Single Sign-On requires Pressbooks >= 5.5.2 and WordPress >= 4.9.8
