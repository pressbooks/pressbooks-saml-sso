<?php

class AdminTest extends \WP_UnitTestCase {

	/**
	 * @var \PressbooksShibbolethSso\Admin
	 */
	protected $admin;

	private static $localWebServerId = null;

	/**
	 * @return string
	 */
	private function launchWebPage() {
		$command = sprintf(
			'php -S %s -t %s >/dev/null 2>&1 & echo $!',
			'127.0.0.1:8888',
			__DIR__ . '/data/'
		);

		$output = [];
		exec( $command, $output );
		self::$localWebServerId = (int) $output[0];
		sleep( 2 );
		return 'http://127.0.0.1:8888/testshib-providers.xml';
	}

	private function killWebPage() {
		if ( self::$localWebServerId ) {
			exec( 'kill ' . self::$localWebServerId );
			self::$localWebServerId = null;
		}
	}

	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
		$this->admin = new \PressbooksShibbolethSso\Admin();
	}

	public function tearDown() {
		$this->killWebPage();
		parent::tearDown();
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

	public function test_parseOptionsFromRemoteXML() {
		$url = $this->launchWebPage();
		$update = $this->admin->parseOptionsFromRemoteXML( $url );
		$this->killWebPage();
		$this->assertContains( 'testshib', $update['idp_entity_id'] );
		$this->assertContains( 'testshib', $update['idp_sso_login_url'] );
		$this->assertNotEmpty( $update['idp_x509_cert'] );

		try {
			$this->admin->parseOptionsFromRemoteXML( 'garbage' );
		} catch ( \Exception $e ) {
			$this->assertTrue( true ); // Expected exception was thrown
			return;
		}
		$this->fail();
	}

	public function test_options() {

		$options = $this->admin->getOptions();

		// idp_metadata_url
		$this->assertEquals( $options['idp_entity_id'], '' );
		$this->assertEquals( $options['idp_sso_login_url'], '' );
		$this->assertEquals( $options['idp_x509_cert'], '' );
		// idp_sso_logout_url
		$this->assertEquals( $options['provision'], 'refuse' );
		$this->assertEquals( $options['button_text'], '' );
		$this->assertEquals( $options['bypass'], 0 );
		$this->assertEquals( $options['forced_redirection'], 0 );

		$_REQUEST['_wpnonce'] = wp_create_nonce( 'pb-shibboleth-sso' );
		$_POST = [
			'idp_entity_id' => 'https://idp.testshib.org/idp/shibboleth',
			'idp_sso_login_url' => 'https://idp.testshib.org/idp/profile/SAML2/Redirect/SSO',
			'idp_x509_cert' => '00bfd57d54f083ce83b773f4332b1258',
			'idp_sso_logout_url' => '',
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
