let mix = require( 'laravel-mix' );
let path = require( 'path' );

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for your application, as well as bundling up your JS files.
 |
 */

mix.setPublicPath( path.join( 'assets', 'dist' ) )
	.version()
	.js( 'assets/src/scripts/pressbooks-saml-sso.js', 'assets/dist/scripts/' )
	.js( 'assets/src/scripts/login-form.js', 'assets/dist/scripts/' )
	.sass( 'assets/src/styles/pressbooks-saml-sso.scss', 'assets/dist/styles/' )
	.sass( 'assets/src/styles/login-form.scss', 'assets/dist/styles/' )
	.copyDirectory( 'assets/src/fonts', 'assets/dist/fonts' )
	.copyDirectory( 'assets/src/images', 'assets/dist/images' )
	.then( () => {
		normalizeNewlines( 'assets/dist/scripts/' );
		normalizeNewlines( 'assets/dist/styles/' );
	} );
