<div class="wrap">
    <h1>{{ __( 'Shibboleth', 'pressbooks-shibboleth-sso') }}</h1>
    <p>{{ __('When joining a Shibboleth or SAML2 Identity Provider (IdP) you will be asked for Service Provider (SP) configuration file. Get that here:', 'pressbooks-shibboleth-sso') }} <a
                href="{!! $metadata_url !!}" target="_blank">{{ __('Metadata XML Configuration', 'pressbooks-shibboleth-sso') }}</a></p>
    <form method="POST" action="{{ $form_url }}" method="post">
        {!! wp_nonce_field( 'pb-shibboleth-sso' ) !!}
        <h2>{{ __('Automatic Configuration', 'pb-shibboleth-sso') }}</h2>
        <table class="form-table" id="automatic-configuration">
            <tr>
                <th><label for="idp_metadata_url">IdP metadata URL</label></th>
                <td>
                    <input name="idp_metadata_url" id="idp_metadata_url" type="url" value="{{ $options['idp_metadata_url'] }}" class="regular-text"/>
                    <p>
                        <em>{{ __('If you have an IdP metadata URL enter it here and we will try to configure the app for you.', 'pressbooks-shibboleth-sso') }}</em>
                    </p>
                </td>
            </tr>
        </table>
        <h2>{{ __('Manual Configuration', 'pb-shibboleth-sso') }}</h2>
        <table class="form-table" id="manual-configuration">
            <tr>
                <th><label for="idp_entity_id">EntityID</label></th>
                <td>
                    <input name="idp_entity_id" id="idp_entity_id" type="text" value="{{ $options['idp_entity_id'] }}" class="regular-text"/>
                    <p>
                        <em>{{ __('Identifier of the IdP entity (must be a URI.)', 'pressbooks-shibboleth-sso') }}</em>
                    </p>
                </td>
            </tr>
            <tr>
                <th><label for="idp_sso_login_url">SingleSignOnService</label></th>
                <td>
                    <input name="idp_sso_login_url" id="idp_sso_login_url" type="url" value="{{ $options['idp_sso_login_url'] }}" class="regular-text"/>
                    <p>
                        <em>{{ __('URL Target of the IdP where the Authentication Request Message will be sent.', 'pressbooks-shibboleth-sso') }}</em>
                    </p>
                </td>
            </tr>
            <tr>
                <th><label for="idp_sso_logout_url">SingleLogoutService</label></th>
                <td>
                    <input name="idp_sso_logout_url" id="idp_sso_logout_url" type="url" value="{{ $options['idp_sso_logout_url'] }}" class="regular-text"/>
                    <p>
                        <em>{{ __('URL Location of the IdP where SLO Request will be sent.', 'pressbooks-shibboleth-sso') }}</em>
                    </p>
                </td>
            </tr>
            <tr>
                <th><label for="idp_x509_cert">X509Certificate</label></th>
                <td>
                    <textarea name="idp_x509_cert" id="idp_x509_cert" type="text" class="large-text code" rows="5">{{ $options['idp_x509_cert'] }}</textarea>
                    <p>
                        <em>{{ __('Public x509 certificate of the IdP.', 'pressbooks-shibboleth-sso') }}</em>
                    </p>
                </td>
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