<div class="wrap">
    <h1>{{ __( 'Shibboleth', 'pressbooks-shibboleth-sso') }}</h1>
    <form method="POST" action="{{ $form_url }}" method="post">
        {!! wp_nonce_field( 'pb-shibboleth-sso' ) !!}
        <table class="form-table">
            <tr>
                <th><label for="idp_entity_id">{{ __('Identifier of the IdP entity (must be a URI)', 'pressbooks-shibboleth-sso') }}</label></th>
                <td><input name="idp_entity_id" id="idp_entity_id" type="text" value="{{ $options['idp_entity_id'] }}" class="regular-text"/></td>
            </tr>
            <tr>
                <th><label for="idp_sso_login_url">{{ __('URL Target of the IdP where the Authentication Request Message will be sent', 'pressbooks-shibboleth-sso') }}</label></th>
                <td><input name="idp_sso_login_url" id="idp_sso_login_url" type="url" value="{{ $options['idp_sso_login_url'] }}" class="regular-text"/></td>
            </tr>
            <tr>
                <th><label for="idp_sso_logout_url">{{ __(' URL Target of the IdP where SLO Request will be sent', 'pressbooks-shibboleth-sso') }}</label></th>
                <td><input name="idp_sso_logout_url" id="idp_sso_logout_url" type="url" value="{{ $options['idp_sso_logout_url'] }}" class="regular-text"/></td>
            </tr>
            <tr>
                <th><label for="idp_x509_cert">{{ __('Public x509 certificate of the IdP', 'pressbooks-shibboleth-sso') }}</label></th>
                <td><input name="idp_x509_cert" id="idp_x509_cert" type="text" value="{{ $options['idp_x509_cert'] }}" class="regular-text"/></td>
            </tr>
        </table>
        <table class="form-table">
            <tr>
                <th><label for="provision">{{ __('If the Shibboleth user does not have a Pressbooks account', 'pressbooks-shibboleth-sso') }}</label></th>
                <td><select name="provision" id="provision">
                        <option value="refuse" {!! selected( $options['provision'], 'refuse' ) !!} >{{ __('Refuse Access', 'pressbooks-shibboleth-sso') }}</option>
                        <option value="create" {!! selected( $options['provision'], 'create' ) !!} >{{ __('Add New User', 'pressbooks-shibboleth-sso') }}</option>
                    </select>
                </td>
            </tr>
        </table>
        <h2>{{ __('Optional Information', 'pb-shibboleth-sso') }}</h2>
        <table class="form-table">
            <tr>
                <th>{{ __(' Bypass', 'pb-shibboleth-sso') }}</th>
                <td><label><input name="bypass" id="bypass" type="checkbox"
                                  value="1" {!! checked( $options['bypass'] ) !!}/> {!!
                                  sprintf( __('Bypass the "Limited Email Registrations" and "Banned Email Domains" lists under <a href="%s">Network Settings</a>.', 'pb-shibboleth-sso') ,'settings.php' )
                                   !!}
                    </label></td>
            </tr>
            <tr>
                <th>{{ __(' Forced Redirection', 'pb-shibboleth-sso') }}</th>
                <td>
                    <label><input name="forced_redirection" id="forced_redirection" type="checkbox"
                                  value="1" {!! checked( $options['forced_redirection'] ) !!}/> {{ __('Hide the Pressbooks login page.', 'pb-shibboleth-sso') }}</label>
                </td>
            </tr>
            <tr>
                <th><label for="button_text">{{ __('Customize Button Text', 'pb-shibboleth-sso') }}</label></th>
                <td>
                    <input name="button_text" id="button_text" type="text" value="{{ $options['button_text'] }}" class="regular-text"/>
                    <p>
                        <em>{{ __("Change the [ Connect via Shibboleth ] button to something more user-friendly.", 'pb-shibboleth-sso') }}</em>
                    </p>
                </td>
            </tr>
        </table>
        {!! get_submit_button() !!}
    </form>
</div>
<script>
	jQuery( function( $ ) {
		var checkbox = $( '#forced_redirection' );
		$( "#button_text" ).prop( "disabled", checkbox.is( ':checked' ) );
		checkbox.on( "change", function() {
			$( "#button_text" ).prop( "disabled", $( this ).is( ':checked' ) )
		} );
	} );
</script>