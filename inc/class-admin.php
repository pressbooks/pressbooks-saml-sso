<?php

namespace PressbooksSamlSso;

use PressbooksMix\Assets;

class Admin {

	// IMPORTANT: Do not rename to `pressbooks_saml_sso` to be compatible with existing integrations
	const OPTION = 'pressbooks_shibboleth_sso';

	/**
	 * @var Admin
	 */
	private static $instance = null;

	/**
	 * @return Admin
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * @param Admin $obj
	 */
	static public function hooks( Admin $obj ) {
		load_plugin_textdomain( 'pressbooks-saml-sso', false, 'pressbooks-saml-sso/languages/' );

		add_action( 'admin_enqueue_scripts', [ $obj, 'adminEnqueueScripts' ] );
		add_action( 'network_admin_menu', [ $obj, 'addMenu' ] );
	}

	/**
	 *
	 */
	public function __construct() {
	}

	/**
	 * @param string $hook
	 */
	public function adminEnqueueScripts( $hook ) {
		if ( $hook !== get_plugin_page_hookname( 'pb_saml_admin', 'pb_network_integrations' ) ) {
			return;
		}
		$assets = new Assets( 'pressbooks-saml-sso', 'plugin' );
		wp_enqueue_script( 'pb-saml-sso', $assets->getPath( 'scripts/pressbooks-saml-sso.js' ), [ 'jquery' ] );
	}

	/**
	 *
	 */
	public function addMenu() {
		$parent_slug = \Pressbooks\Admin\Dashboard\init_network_integrations_menu();

		add_submenu_page(
			$parent_slug,
			__( 'SAML2', 'pressbooks-saml-sso' ),
			__( 'SAML2', 'pressbooks-saml-sso' ),
			'manage_network',
			'pb_saml_admin',
			[ $this, 'printMenu' ]
		);
	}

	/**
	 *
	 */
	public function printMenu() {
		try {
			if ( $this->saveOptions() ) {
				echo '<div id="message" role="status" class="updated notice is-dismissible"><p>' . __( 'Settings saved.' ) . '</p></div>';
			}
		} catch ( \Exception $e ) {
			echo '<div id="message" role="alert" class="error notice is-dismissible"><p>' . $e->getMessage() . '</p></div>';
		}

		echo blade()->render(
			'PressbooksSamlSso::admin', [
				'form_url' => network_admin_url( '/admin.php?page=pb_saml_admin' ),
				'metadata_url' => \PressbooksSamlSso\metadata_url(),
				'options' => $this->getOptions(),
			]
		);
	}

	/**
	 * @throws \Exception
	 * @return bool
	 */
	public function saveOptions() {
		if ( ! empty( $_POST ) && check_admin_referer( 'pb-saml-sso' ) ) {
			$_POST = array_map( 'trim', $_POST );
			$update = [];

			if ( isset( $_POST['idp_entity_id'] ) ) {
				$update['idp_entity_id'] = $_POST['idp_entity_id'];
			}
			if ( isset( $_POST['idp_sso_login_url'] ) ) {
				$update['idp_sso_login_url'] = $_POST['idp_sso_login_url'];
			}
			if ( isset( $_POST['idp_x509_cert'] ) ) {
				$update['idp_x509_cert'] = $_POST['idp_x509_cert'];
			}
			if ( isset( $_POST['idp_sso_logout_url'] ) ) {
				$update['idp_sso_logout_url'] = $_POST['idp_sso_logout_url'];
			}
			if ( isset( $_POST['provision'] ) ) {
				$update['provision'] = in_array( $_POST['provision'], [ 'refuse', 'create' ], true ) ? $_POST['provision'] : 'refuse';
			}
			if ( isset( $_POST['button_text'] ) ) {
				$update['button_text'] = wp_unslash( wp_kses( $_POST['button_text'], [
					'br' => [],
				] ) );
			}
			// Checkboxes
			$update['bypass'] = ! empty( $_POST['bypass'] ) ? 1 : 0;
			$update['forced_redirection'] = ! empty( $_POST['forced_redirection'] ) ? 1 : 0;

			// Auto-config
			if ( ! empty( $_POST['idp_metadata_url'] ) ) {
				$update = array_merge( $update, $this->parseOptionsFromRemoteXML( $_POST['idp_metadata_url'] ) );
			}

			$fallback = $this->getOptions();
			$update = array_merge( $fallback, $update );

			$result = update_site_option( self::OPTION, $update );
			return $result;
		}
		return false;
	}

	/**
	 * @param string $url
	 *
	 * @throws \Exception
	 * @return array
	 */
	public function parseOptionsFromRemoteXML( $url ) {
		$settings = @\OneLogin\Saml2\IdPMetadataParser::parseRemoteXML( $url ); // @codingStandardsIgnoreLine
		if ( ! isset( $settings['idp'] ) ) {
			$error = __( 'Failed to get IdP Metadata from URL.', 'pressbooks-saml-sso' );
			throw new \Exception( $error );
		}

		$update['idp_entity_id'] = $settings['idp']['entityId'];
		$update['idp_sso_login_url'] = $settings['idp']['singleSignOnService']['url'];
		$update['idp_x509_cert'] = $settings['idp']['x509cert'];
		if ( isset( $settings['idp']['singleLogoutService']['url'] ) ) {
			$update['idp_sso_logout_url'] = $settings['idp']['singleLogoutService']['url'];
		}

		return $update;
	}

	/**
	 * @return array{idp_metadata_url: string, idp_entity_id: string, idp_sso_login_url: string, idp_x509_cert: string, idp_sso_logout_url: string, provision: string, button_text: string, bypass: bool, forced_redirection: bool}
	 */
	public function getOptions() {

		$options = get_site_option( self::OPTION, [] );

		if ( empty( $options['idp_metadata_url'] ) ) {
			$options['idp_metadata_url'] = '';
		}
		if ( empty( $options['idp_entity_id'] ) ) {
			$options['idp_entity_id'] = '';
		}
		if ( empty( $options['idp_sso_login_url'] ) ) {
			$options['idp_sso_login_url'] = '';
		}
		if ( empty( $options['idp_x509_cert'] ) ) {
			$options['idp_x509_cert'] = '';
		}
		if ( empty( $options['idp_sso_logout_url'] ) ) {
			$options['idp_sso_logout_url'] = '';
		}
		if ( empty( $options['provision'] ) ) {
			$options['provision'] = 'refuse';
		}
		if ( empty( $options['button_text'] ) ) {
			$options['button_text'] = '';
		}
		if ( empty( $options['bypass'] ) ) {
			$options['bypass'] = false;
		}
		if ( empty( $options['forced_redirection'] ) ) {
			$options['forced_redirection'] = false;
		}

		return $options;
	}

}
