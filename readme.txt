=== Pressbooks Shibboleth Single Sign-On ===
Contributors: conner_bw, greatislander
Donate link: https://opencollective.com/pressbooks/
Tags: pressbooks, saml, saml2, sso, shibboleth
Requires at least: 5.0.3
Tested up to: 5.0.3
Requires PHP: 7.1
Stable tag: 1.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Shibboleth Single Sign-On integration for Pressbooks.

== Description ==

[![Packagist](https://img.shields.io/packagist/v/pressbooks/pressbooks-shibboleth-sso.svg?style=flat-square)](https://packagist.org/packages/pressbooks/pressbooks-shibboleth-sso) [![GitHub release](https://badgen.net/github/release/pressbooks/pressbooks-shibboleth-sso/stable?style=flat)](https://github.com/pressbooks/pressbooks-shibboleth-sso/releases) [![Travis](https://badgen.net/travis/pressbooks/pressbooks-shibboleth-sso.svg?style=flat)](https://travis-ci.com/pressbooks/pressbooks-shibboleth-sso/) [![Codecov](https://badgen.net/codecov/c/github/pressbooks/pressbooks-shibboleth-sso?style=flat)](https://codecov.io/gh/pressbooks/pressbooks-shibboleth-sso)

Plugin to integrate Pressbooks with a [Shibboleth](https://www.shibboleth.net/) or SAML2 single sign-on service.

Users who attempt to login to Pressbooks are redirected to a Shibboleth or SAML2 Identity Provider. After the user’s credentials are verified, they are redirected back to the Pressbooks network. If we match a Pressbooks user by Shibboleth UID (stored in user_meta table), the user is recognized as valid and allowed access. If no match, then try to match a Pressbooks user by email (and store a successful match in user_meta table for next time). If the Shibboleth user does not have an account in Pressbooks, a new user can be created, or access can be refused, depending on the configuration.

Limitations: This plugin does not enable authentication with multilateral Shibboleth. For use in a non-federated, bilateral configuration, with a single IdP.

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

= Security Considerations =

Generating certificates in `vendor/onelogin/php-saml/certs`, without further changes, will expose them to malicious users (Ie. `https://path/to/vendor/onelogin/php-saml/certs/sp.crt`).
Furthermore, your certificates are at risk of being deleted when updating packages using `composer update` or similar commands. A competent sysadmin must make sure certificates are not accessible from the internet nor deleted. It is highly recommended that you pass your certificates via configuration variables. Example:

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

= IdP Setup =

Upon activation of the plugin, a submenu item ("Shibboleth") is added to the Network Admin interface under "Integrations". This leads to the Shibboleth settings page. Your metadata XML can be downloaded from this page.

The plugin requires the Assertion elements of the Response to be encrypted.

The plugin requires the Assertion elements of the Response to be signed.

The plugin looks for the following Attributes in the Response: (For compatibility with a broader range of IdPs we use the FriendlyName parameter.)

+ Requires: `uid` (urn:oid:0.9.2342.19200300.100.1.1, samAccountName, or equivalent)
+ Strongly recommends: `mail` (urn:oid:0.9.2342.19200300.100.1.3, email-address, or equivalent) If no value is available we fall back to `uid@127.0.0.1`
+ Optional: `eduPersonPrincipalName` (urn:oid:1.3.6.1.4.1.5923.1.1.1.6, or equivalent) Upon the first launch for a given user, if mail cannot match an existing person, and this value is present, we'll try to use it.

The email can be filtered, example: `add_filter( 'pb_integrations_multidomain_email', function( $email, $uid, $plugin ) { /* Custom use case, return $email */ }, 10, 3 );`

Because this plugin uses the fabulous [onelogin/php-saml](https://github.com/onelogin/php-saml/) toolkit, [many other configuration variables can be tweaked](https://github.com/onelogin/php-saml/#settings).

== Screenshots ==

![Pressbooks Shibboleth Administration.](screenshot-1.png)

== Changelog ==

= 1.0.0 =
+ Bump onelogin/php-saml from dev-branch to 3.1.0
+ Fix infinite redirects when using ADFS
+ Fix GitHub Updater
+ Coding standards, README updates

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
