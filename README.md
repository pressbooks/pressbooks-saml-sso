# Pressbooks SAML2 Single Sign-On

Contributors: conner_bw, greatislander, richard015ar, steelwagstaff, arzola
Tags: pressbooks, saml, saml2, sso, shibboleth
Requires at least: 6.5
Tested up to: 6.5
<!-- x-release-please-start-version -->
Stable tag: 2.5.1
<!-- x-release-please-end -->
Requires PHP: 8.1
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

SAML2 Single Sign-On integration for Pressbooks.

## Description

[![Packagist](https://img.shields.io/packagist/v/pressbooks/pressbooks-saml-sso.svg?style=flat-square)](https://packagist.org/packages/pressbooks/pressbooks-saml-sso) [![GitHub release](https://badgen.net/github/release/pressbooks/pressbooks-saml-sso/stable?style=flat)](https://github.com/pressbooks/pressbooks-saml-sso/releases) [![Travis](https://badgen.net/travis/pressbooks/pressbooks-saml-sso.svg?style=flat)](https://travis-ci.com/pressbooks/pressbooks-saml-sso/) [![Codecov](https://badgen.net/codecov/c/github/pressbooks/pressbooks-saml-sso?style=flat)](https://codecov.io/gh/pressbooks/pressbooks-saml-sso)

Plugin to integrate Pressbooks with a SAML2 single sign-on
service. ([Shibboleth](https://www.shibboleth.net/), [Microsoft ADFS](https://support.zendesk.com/hc/en-us/articles/203663886-Setting-up-single-sign-on-using-Active-Directory-with-ADFS-and-SAML-Professional-and-Enterprise-), [Google Apps](https://pantheon.io/docs/wordpress-google-sso/),
Etc.)

Users who attempt to login to Pressbooks are redirected to a Shibboleth or SAML2 Identity Provider. After the user’s
credentials are verified, they are redirected back to the Pressbooks network. If we match a Pressbooks user by UID (
stored in user_meta table), the user is recognized as valid and allowed access. If no match, then try to match a
Pressbooks user by email (and store a successful match in user_meta table for next time). If the user does not have an
account in Pressbooks, a new user can be created, or access can be refused, depending on the configuration.

Limitations: This plugin does not enable authentication with multilateral Shibboleth. For use in a non-federated,
bilateral configuration, with a single IdP.

## Installation

```
composer require pressbooks/pressbooks-saml-sso
```

Or, download the latest version from the releases page and unzip it into your WordPress plugin
directory: https://github.com/pressbooks/pressbooks-saml-sso/releases

Then, create the necessary certificates:

```
cd vendor/onelogin/php-saml/certs
openssl req -newkey rsa:2048 -new -x509 -days 3652 -nodes -out sp.crt -keyout sp.key
```

Then, activate and configure the plugin at the Network level.

### Security Considerations

Generating certificates in `vendor/onelogin/php-saml/certs`, without further changes, will expose them to malicious
users (Ie. `https://path/to/vendor/onelogin/php-saml/certs/sp.crt`).
Furthermore, your certificates are at risk of being deleted when updating packages using `composer update` or similar
commands. A competent sysadmin must make sure certificates are not accessible from the internet nor deleted. It is
highly recommended that you pass your certificates via configuration variables. Example:

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

### IdP Setup

Upon activation of the plugin, a submenu item ("SAML2") is added to the Network Admin interface under "Integrations".
This leads to the SAML2 settings page. Your metadata XML can be downloaded from this page.

The plugin requires the Assertion elements of the Response to be signed.

The plugin looks for the following Attributes in the Response:

+ Requires: `urn:oid:0.9.2342.19200300.100.1.1` (samAccountName or equivalent, ideally with FriendlyName `uid`)
+ Strongly recommends: `urn:oid:0.9.2342.19200300.100.1.3` (email-address or equivalent, ideally with
  FriendlyName `mail`). If no value is available we fall back to `uid@127.0.0.1`
+ Optional: `urn:oid:1.3.6.1.4.1.5923.1.1.1.6` (eduPersonPrincipalName or equivalent). Upon the first launch for a given
  user, if mail cannot match an existing person, and this value is present, we'll try to use it.

The email can be filtered,
example: `add_filter( 'pb_integrations_multidomain_email', function( $email, $uid, $plugin ) { /* Custom use case, return $email */ }, 10, 3 );`

Because this plugin uses the fabulous [onelogin/php-saml](https://github.com/onelogin/php-saml/)
toolkit, [many other configuration variables can be tweaked](https://github.com/onelogin/php-saml/#settings).

## Using a test IdP SAML app
If you want to test the plugin with a test IdP, you can use [Auth0](https://auth0.com/).
Here are the steps to create a test IdP application:
1. Create an Auth0 account (or ask the Operation Team for a test account).
2. Create a new application in the Auth0 dashboard.  
3. Select "Regular Web Applications" as the application type.
4. Go to the "Settings" tab of your application.
5. Scroll down to the "Advanced Settings" section and click on "Ednpoints" tab.
6. Copy the "SAML Metadata URL" and paste it on Pressbooks SAML2 settings page, in the "IdP metadata URL" field and save the changes.
7. In Auth0, go to the "Addons" tab of your application, and click on "SAML2 Web App".
8. In the Settings tab, under the Application Callback URL, add: `https://<NETWORK_DOMAIN>/wp/wp-login.php?action=pb_shibboleth_acs`
9. In the PB Admin area, go to Integrations > SAML2 and click under the "Metadata XML Configuration" link displayed at the top of the page.
10. Locate the `EntityID` value inside the `<md:EntityDescriptor>` tag in your metadata XML. You’ll use this value as the audience in the next step.
11. Update your Auth0 Settings JSON to include the following configuration. Be sure to:
  - Replace `<URL_ENTITY_ID_FROM_METADATA_XML>` with the actual `EntityID` you copied.
  - Replace `<NETWORK_DOMAIN>` with the domain of your Pressbooks instance.
  ```json
	{
		"audience": "<URL_ENTITY_ID_FROM_METADATA_XML>",
		"recipient": "https://<NETWORK_DOMAIN>/wp/wp-login.php?action=pb_shibboleth_acs",
		"mappings": {
			"user_id": "urn:oid:0.9.2342.19200300.100.1.1",
			"email": "urn:oid:0.9.2342.19200300.100.1.3",
			"name": "http://schemas.xmlsoap.org/ws/2005/05/identity/claims/name",
			"given_name": "http://schemas.xmlsoap.org/ws/2005/05/identity/claims/givenname",
			"family_name": "http://schemas.xmlsoap.org/ws/2005/05/identity/claims/surname",
			"upn": "http://schemas.xmlsoap.org/ws/2005/05/identity/claims/upn",
			"groups": "http://schemas.xmlsoap.org/claims/Group"
		},
		"createUpnClaim": true,
		"passthroughClaimsWithNoMapping": true,
		"logout": {
			"slo_enabled": true,
			"callback": "https://<NETWORK_DOMAIN>/wp/wp-login.php?action=pb_shibboleth_sls"
		},
		"binding": "urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"
	}
  ```
  Make sure you are using the correct `NETWORK_DOMAIN` for your Pressbooks instance.
12. Save the changes in Auth0.
13. Create a new user in the "User Management" section of the Auth0 dashboard.
14. Now you can log in using the new user you created in Auth0. Go to your Pressbooks instance and click on the "Login with SAML" button.

## Sending logs

If you use AWS and want to log SAML attempts on your server, you will need define some environment variables on the
server which is hosting your Pressbooks instance.

### AWS S3

Define the following environment variables:

 ```
  LOG_LOGIN_ATTEMPTS (setting this value to true will enable this feature at the infrastructure level)
  AWS_ACCESS_KEY_ID
  AWS_SECRET_ACCESS_KEY
  AWS_S3_OIDC_BUCKET
  AWS_S3_REGION
  AWS_S3_VERSION
```

After these variables have been properly defined, basic information about SAML login attempts will be logged to your S3
bucket. A new CSV file will be created each month so that the logs remain readable. Log storage will take place in a
folder structure that looks like
this `S3 Bucket > saml_logs > {ENVIRONMENT} > {Network URL hashed though wp_hash function} > {YYYY-MM} > saml_logs.log`.

### AWS CloudWatch Logs

Define the following envirnoment variables:

 ```
  LOG_LOGIN_ATTEMPTS (setting this value to true will enable this feature at the infrastructure level)
  AWS_ACCESS_KEY_ID
  AWS_SECRET_ACCESS_KEY
  AWS_S3_REGION
  AWS_S3_VERSION
```

After these variables have been properly defined, basic information about SAML login attempts will be logged in your AWS
CloudWatch Logs service in JSON format. You will need to create a new Log group called `pressbooks-logs`.

## Screenshots

![SAML2 Administration.](screenshot-1.png)

![Metadata XML.](screenshot-2.png)

### Changelog
Please see the [CHANGELOG](CHANGELOG.md) file for more information.
