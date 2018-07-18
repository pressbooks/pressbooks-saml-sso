// Inspired by Shibboleth plugin for WordPress
// @see https://github.com/michaelryanmcneill/shibboleth/blob/master/assets/js/shibboleth_login_form.js
// Originally from Automattic's Jetpack SSO module (v5.3)
// @see https://github.com/Automattic/jetpack/blob/5.3/modules/sso/jetpack-sso-login.js

jQuery( document ).ready( function ( $ ) {
	let body = $( 'body' ),
		ssoWrap = $( '#pb-shibboleth-wrap' ),
		loginForm = $( '#loginform' ),
		overflow = $( '<div class="pb-shibboleth-clear"></div>' );

	loginForm.append( overflow );

	if ( $( '#loginform > p.forgetmenot' ).length === 1 ) {
		// Hasn't been moved by other plugins yet
		overflow.append( $( 'p.forgetmenot' ), $( 'p.submit' ) );
	}

	// We reposition the SSO UI at the bottom of the login form which
	// fixes a tab order issue. Then we override any styles for absolute
	// positioning of the SSO UI.
	loginForm.append( ssoWrap );
	body.addClass( 'pb-shibboleth-repositioned' );
} );
