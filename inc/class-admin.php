<?php

namespace Pressbooks\Shibboleth;

class Admin {

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
		add_action( 'network_admin_menu', [ $obj, 'addMenu' ] );
	}

	/**
	 *
	 */
	public function __construct() {

	}

	/**
	 *
	 */
	public function addMenu() {
		$parent_slug = \Pressbooks\Admin\Dashboard\init_network_integrations_menu();

		add_submenu_page(
			$parent_slug,
			__( 'Shibboleth', 'pressbooks-shibboleth-sso' ),
			__( 'Shibboleth', 'pressbooks-shibboleth-sso' ),
			'manage_network',
			'pb_shibboleth_admin',
			[ $this, 'printMenu' ]
		);
	}

	/**
	 *
	 */
	public function printMenu() {
		if ( $this->saveOptions() ) {
			echo '<div id="message" class="updated notice is-dismissible"><p>' . __( 'Settings saved.' ) . '</p></div>';
		}
		$html = blade()->render(
			'admin', [
				'form_url' => network_admin_url( '/admin.php?page=pb_shibboleth_admin' ),
				'options' => $this->getOptions(),
			]
		);
		echo $html;
	}


	/**
	 * @return bool
	 */
	public function saveOptions() {
		if ( ! empty( $_POST ) && check_admin_referer( 'pb-shibboleth-sso' ) ) {
			$fallback = $this->getOptions();
			$update = [
				// TODO
				'provision' => in_array( $_POST['provision'], [ 'refuse', 'create' ], true ) ? $_POST['provision'] : 'refuse',
				'button_text' => isset( $_POST['button_text'] ) ? trim( wp_unslash( wp_kses( $_POST['button_text'], [
					'br' => [],
				] ) ) ) : $fallback['button_text'],
				'bypass' => ! empty( $_POST['bypass'] ) ? 1 : 0,
				'forced_redirection' => ! empty( $_POST['forced_redirection'] ) ? 1 : 0,
			];
			$result = update_site_option( self::OPTION, $update );
			return $result;
		}
		return false;
	}

	/**
	 * @return array{provision: string,  button_text: string, bypass: bool, forced_redirection: bool}
	 */
	public function getOptions() {

		$options = get_site_option( self::OPTION, [] );

		// TODO

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
