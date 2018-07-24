# Pressbooks Shibboleth Single Sign-On 
**Contributors:** conner_bw, greatislander  
**Donate link:** https://opencollective.com/pressbooks/  
**Tags:** pressbooks, sso, shibboleth  
**Requires at least:** 4.9.7  
**Tested up to:** 4.9  
**Stable tag:** 0.0.1  
**License:** GPLv3 or later  
**License URI:** https://www.gnu.org/licenses/gpl-3.0.html  

Shibboleth Single Sign-On integration for Pressbooks.


## Description 

Plugin to integrate Pressbooks with [Shibboleth](https://en.wikipedia.org/wiki/Shibboleth_(Shibboleth_Consortium)) single sign-on architectures.


## Installation 

```
composer require pressbooks/pressbooks-shibboleth-sso
```

Or, download the latest version from the releases page and unzip it into your WordPress plugin directory): https://github.com/pressbooks/pressbooks-shibboleth-sso/releases

Then, create the necessary certificates:

```
cd vendor/onelogin/php-saml/certs
openssl req -newkey rsa:2048 -new -x509 -days 3652 -nodes -out sp.crt -keyout sp.key
openssl req -newkey rsa:2048 -new -x509 -days 3652 -nodes -out metadata.crt -keyout metadata.key
```

Then, activate and configure the plugin at the Network level.

Read the developer documentation for more info: TK


## Screenshots 

TK


## Changelog 


### 0.0.1 
* TK


## Upgrade Notice 


### 0.0.1 
* Pressbooks Shibboleth Single Sign-On requires Pressbooks >= 5.4 and WordPress >= 4.9
