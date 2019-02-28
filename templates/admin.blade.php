<div class="wrap">
    <h1><?php _e( 'SAML2 (Security Assertion Markup Language)', 'pressbooks-saml-sso') ?></h1>
    <p><?php _e('When joining a Shibboleth or SAML2 Identity Provider (IdP) you will be asked for Service Provider (SP) configuration file. Get that here:', 'pressbooks-saml-sso') ?> <a
                href="{!! $metadata_url !!}" target="_blank"><?php _e('Metadata XML Configuration', 'pressbooks-saml-sso') ?></a></p>
    <form method="POST" action="{{ $form_url }}" method="post">
        {!! wp_nonce_field( 'pb-saml-sso' ) !!}
        <h2><?php _e('Automatic Configuration', 'pressbooks-saml-sso') ?></h2>
        <table class="form-table" id="automatic-configuration">
            <tr>
                <th><label for="idp_metadata_url"><?php _e('IdP metadata URL', 'pressbooks-saml-sso') ?></label></th>
                <td>
                    <input name="idp_metadata_url" id="idp_metadata_url" type="url" value="{{ $options['idp_metadata_url'] }}" class="regular-text"/>
                    <p>
                        <em><?php _e('If you have an IdP metadata URL, enter it here and save. The fields below should then auto-fill.', 'pressbooks-saml-sso') ?></em>
                    </p>
                </td>
            </tr>
        </table>
        <h2><?php _e('Manual Configuration', 'pressbooks-saml-sso') ?></h2>
        <table class="form-table" id="manual-configuration">
            <tr>
                <th><label for="idp_entity_id"><?php _e('IdP metadata URL', 'pressbooks-saml-sso') ?></label></th>
                <td>
                    <input name="idp_entity_id" id="idp_entity_id" type="text" value="{{ $options['idp_entity_id'] }}" class="regular-text"/>
                    <p>
                        <em><?php _e('Identifier of the IdP entity (must be a URI.)', 'pressbooks-saml-sso') ?></em>
                    </p>
                </td>
            </tr>
            <tr>
                <th><label for="idp_sso_login_url"><?php _e('SingleSignOnService', 'pressbooks-saml-sso') ?></label></th>
                <td>
                    <input name="idp_sso_login_url" id="idp_sso_login_url" type="url" value="{{ $options['idp_sso_login_url'] }}" class="regular-text"/>
                    <p>
                        <em><?php _e('URL Target of the IdP where the Authentication Request Message will be sent.', 'pressbooks-saml-sso') ?></em>
                    </p>
                </td>
            </tr>
            <tr>
                <th><label for="idp_sso_logout_url"><?php _e('SingleLogoutService', 'pressbooks-saml-sso') ?></label></th>
                <td>
                    <input name="idp_sso_logout_url" id="idp_sso_logout_url" type="url" value="{{ $options['idp_sso_logout_url'] }}" class="regular-text"/>
                    <p>
                        <em><?php _e('URL Location of the IdP where SLO Request will be sent.', 'pressbooks-saml-sso') ?></em>
                    </p>
                </td>
            </tr>
            <tr>
                <th><label for="idp_x509_cert"><?php _e('X509Certificate', 'pressbooks-saml-sso') ?></label></th>
                <td>
                    <textarea name="idp_x509_cert" id="idp_x509_cert" type="text" class="large-text code" rows="5">{{ $options['idp_x509_cert'] }}</textarea>
                    <p>
                        <em><?php _e('Public x509 certificate of the IdP.', 'pressbooks-saml-sso') ?></em>
                    </p>
                </td>
            </tr>
        </table>
        <table class="form-table">
            <tr>
                <th><label for="provision"><?php _e('If the user does not have a Pressbooks account', 'pressbooks-saml-sso') ?></label></th>
                <td><select name="provision" id="provision">
                        <option value="refuse" {!! selected( $options['provision'], 'refuse' ) !!} ><?php _e('Refuse Access', 'pressbooks-saml-sso') ?></option>
                        <option value="create" {!! selected( $options['provision'], 'create' ) !!} ><?php _e('Add New User', 'pressbooks-saml-sso') ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <h2><?php _e('Optional Information', 'pressbooks-saml-sso') ?></h2>
        <table class="form-table">
            <tr>
                <th><?php _e('Bypass', 'pressbooks-saml-sso') ?></th>
                <td><label><input name="bypass" id="bypass" type="checkbox"
                                  value="1" {!! checked( $options['bypass'] ) !!}/> {!!
                                  sprintf( __('Bypass the "Limited Email Registrations" and "Banned Email Domains" lists under <a href="%s">Network Settings</a>.', 'pressbooks-saml-sso') ,'settings.php' )
                                   !!}
                    </label></td>
            </tr>
            <tr>
                <th><?php _e('Forced Redirection', 'pressbooks-saml-sso') ?></th>
                <td>
                    <label><input name="forced_redirection" id="forced_redirection" type="checkbox"
                                  value="1" {!! checked( $options['forced_redirection'] ) !!}/> <?php _e('Hide the Pressbooks login page.', 'pressbooks-saml-sso') ?></label>
                </td>
            </tr>
            <tr>
                <th><label for="button_text"><?php _e('Customize Button Text', 'pressbooks-saml-sso') ?></label></th>
                <td>
                    <input name="button_text" id="button_text" type="text" value="{{ $options['button_text'] }}" class="regular-text"/>
                    <p>
                        <em><?php _e("Change the [ Connect via SAML2 ] button to something more user-friendly.", 'pressbooks-saml-sso') ?></em>
                    </p>
                </td>
            </tr>
        </table>
        {!! get_submit_button() !!}
    </form>
</div>
