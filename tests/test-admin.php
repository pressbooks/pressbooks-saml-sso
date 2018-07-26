<?php

class AdminTest extends \WP_UnitTestCase {

	/**
	 * @var \Pressbooks\Shibboleth\Admin
	 */
	protected $admin;

	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
		$this->admin = new \Pressbooks\Shibboleth\Admin();
	}

	public function test_addMenu() {
		$this->admin->addMenu();
		$this->assertTrue( true ); // Did not crash
	}

	public function test_printMenu() {
		ob_start();
		$this->admin->printMenu();
		$buffer = ob_get_clean();
		$this->assertContains( '</form>', $buffer );
	}

	public function test_options() {

		$options = $this->admin->getOptions();

		$this->assertEquals( $options['idp_entity_id'], '' );
		$this->assertEquals( $options['idp_sso_login_url'], '' );
		$this->assertEquals( $options['idp_x509_cert'], '' );
		$this->assertEquals( $options['provision'], 'refuse' );
		$this->assertEquals( $options['button_text'], '' );
		$this->assertEquals( $options['bypass'], 0 );
		$this->assertEquals( $options['forced_redirection'], 0 );

		$_REQUEST['_wpnonce'] = wp_create_nonce( 'pb-shibboleth-sso' );
		$_POST = [
			'idp_entity_id' => 'https://idp.testshib.org/idp/shibboleth',
			'idp_sso_login_url' => 'https://idp.testshib.org/idp/profile/SAML2/Redirect/SSO',
			'idp_x509_cert' => '00bfd57d54f083ce83b773f4332b1258',
			'provision' => 'create',
			'button_text' => 'Connect via<br>Some SSO Provider<script src="http://evil-script.com/script.js"></script>',
			'bypass' => '1',
			'forced_redirection' => '1',
		];
		$this->admin->saveOptions();
		$options = $this->admin->getOptions();

		$this->assertEquals( $options['idp_entity_id'], 'https://idp.testshib.org/idp/shibboleth' );
		$this->assertEquals( $options['idp_sso_login_url'], 'https://idp.testshib.org/idp/profile/SAML2/Redirect/SSO' );
		$this->assertEquals( $options['idp_x509_cert'], '00bfd57d54f083ce83b773f4332b1258' );
		$this->assertEquals( $options['provision'], 'create' );
		$this->assertEquals( $options['button_text'], 'Connect via<br>Some SSO Provider' );
		$this->assertEquals( $options['bypass'], 1 );
		$this->assertEquals( $options['forced_redirection'], 1 );
	}

}
