<?php

class SamlTest extends \WP_UnitTestCase {

	/**
	 * @var \Pressbooks\Shibboleth\SAML
	 */
	protected $saml;

	protected function getTestOptions() {
		return [
			'idp_entity_id' => 'https://idp.testshib.org/idp/shibboleth',
			'idp_sso_login_url' => 'https://idp.testshib.org/idp/profile/SAML2/Redirect/SSO',
			'idp_x509_cert' => 'MIIDAzCCAeugAwIBAgIVAPX0G6LuoXnKS0Muei006mVSBXbvMA0GCSqGSIb3DQEBCwUAMBsxGTAXBgNVBAMMEGlkcC50ZXN0c2hpYi5vcmcwHhcNMTYwODIzMjEyMDU0WhcNMzYwODIzMjEyMDU0WjAbMRkwFwYDVQQDDBBpZHAudGVzdHNoaWIub3JnMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAg9C4J2DiRTEhJAWzPt1S3ryhm3M2P3hPpwJwvt2q948vdTUxhhvNMuc3M3S4WNh6JYBs53R+YmjqJAII4ShMGNEmlGnSVfHorex7IxikpuDPKV3SNf28mCAZbQrX+hWA+ann/uifVzqXktOjs6DdzdBnxoVhniXgC8WCJwKcx6JO/hHsH1rG/0DSDeZFpTTcZHj4S9MlLNUtt5JxRzV/MmmB3ObaX0CMqsSWUOQeE4nylSlp5RWHCnx70cs9kwz5WrflnbnzCeHU2sdbNotBEeTHot6a2cj/pXlRJIgPsrL/4VSicPZcGYMJMPoLTJ8mdy6mpR6nbCmP7dVbCIm/DQIDAQABoz4wPDAdBgNVHQ4EFgQUUfaDa2mPi24x09yWp1OFXmZ2GPswGwYDVR0RBBQwEoIQaWRwLnRlc3RzaGliLm9yZzANBgkqhkiG9w0BAQsFAAOCAQEASKKgqTxhqBzROZ1eVy++si+eTTUQZU4+8UywSKLia2RattaAPMAcXUjO+3cYOQXLVASdlJtt+8QPdRkfp8SiJemHPXC8BES83pogJPYEGJsKo19l4XFJHPnPy+Dsn3mlJyOfAa8RyWBS80u5lrvAcr2TJXt9fXgkYs7BOCigxtZoR8flceGRlAZ4p5FPPxQR6NDYb645jtOTMVr3zgfjP6Wh2dt+2p04LG7ENJn8/gEwtXVuXCsPoSCDx9Y0QmyXTJNdV1aB0AhORkWPlFYwp+zOyOIR+3m1+pqWFpn0eT/HrxpdKa74FA3R2kq4R7dXe4G0kUgXTdqXMLRKhDgdmA==',
			'provision' => 'create',
			'button_text' => '',
			'bypass' => 0,
			'forced_redirection' => 0,
		];
	}

	/**
	 * @return \Pressbooks\Shibboleth\Admin
	 */
	protected function getMockAdmin() {

		$stub1 = $this
			->getMockBuilder( '\Pressbooks\Shibboleth\Admin' )
			->getMock();
		$stub1
			->method( 'getOptions' )
			->willReturn( $this->getTestOptions() );

		return $stub1;
	}

	/**
	 * @return \OneLogin\Saml2\Auth
	 */
	protected function getMockAuth() {

		$stub1 = $this
			->getMockBuilder( '\OneLogin\Saml2\Auth' )
			->disableOriginalConstructor()
			->getMock();

		$stub1
			->method( 'login' )
			->willThrowException( new \LogicException( 'Mock object was here' ) );

		return $stub1;

	}

	/**
	 * @return \Pressbooks\Shibboleth\SAML
	 */
	protected function getSaml() {

		// Ignore session warnings
		PHPUnit_Framework_Error_Notice::$enabled = false;
		PHPUnit_Framework_Error_Warning::$enabled = false;
		ini_set( 'error_reporting', 0 );
		ini_set( 'display_errors', 0 );

		$saml = new \Pressbooks\Shibboleth\SAML( $this->getMockAdmin() );

		PHPUnit_Framework_Error_Notice::$enabled = true;
		PHPUnit_Framework_Error_Warning::$enabled = true;
		ini_set( 'error_reporting', 1 );
		ini_set( 'display_errors', 1 );

		return $saml;
	}

	public function setUp() {
		parent::setUp();
		$this->saml = $this->getSaml();
	}

	public function test_verifyPluginSetup() {
		$this->assertFalse( $this->saml->verifyPluginSetup( [] ) );

		$options = [
			'idp_entity_id' => 1,
			'idp_sso_login_url' => 2,
			'idp_x509_cert' => 3,
		];
		$this->assertFalse( $this->saml->verifyPluginSetup( $options ) );

		$options['idp_sso_login_url'] = 'https://pressbooks.test/login';
		$this->assertTrue( $this->saml->verifyPluginSetup( $options ) );
	}

	public function test_getSamlSettings() {
		$this->assertTrue( is_array( $this->saml->getSamlSettings() ) );
	}

	public function test_setSamlSettings() {
		$this->saml->setSamlSettings( 1, 2, 3 );
		$s = $this->saml->getSamlSettings();
		$this->assertEquals( $s['idp']['entityId'], 1 );
		$this->assertEquals( $s['idp']['singleSignOnService']['url'], 2 );
		$this->assertEquals( $s['idp']['x509cert'], 3 );
		$this->assertEmpty( $s['sp']['x509cert'] );
		$this->assertEmpty( $s['sp']['privateKey'] );

		add_filter(
			'pb_saml_auth_settings',
			function ( $options ) {
				$options['sp']['newkey'] = 'hahaha';
				$options['sp']['entityId'] = 'hahaha';
				$config['sp']['assertionConsumerService']['url'] = 'hahaha';
				$config['sp']['singleLogoutService']['url'] = 'hahaha';
				return $options;
			}
		);
		define( 'PHP_SAML_SP_CERT_PATH', __DIR__ . '/data/sp.crt' );
		define( 'PHP_SAML_SP_KEY_PATH', __DIR__ . '/data/sp.key' );
		$this->saml->setSamlSettings( 1, 2, 3 );
		$s = $this->saml->getSamlSettings();
		$this->assertEquals( $s['idp']['entityId'], 1 );
		$this->assertEquals( $s['idp']['singleSignOnService']['url'], 2 );
		$this->assertEquals( $s['idp']['x509cert'], 3 );
		$this->assertEquals( $s['sp']['newkey'], 'hahaha' );
		$this->assertEquals( $s['sp']['x509cert'], file_get_contents( __DIR__ . '/data/sp.crt' ) );
		$this->assertEquals( $s['sp']['privateKey'], file_get_contents( __DIR__ . '/data/sp.key' ) );
		$this->assertNotEquals( [ 'sp' ]['entityId'], 'hahaha' );
		$this->assertNotEquals( $s['sp']['assertionConsumerService']['url'], 'hahaha' );
		$this->assertNotEquals( $s['sp']['singleLogoutService']['url'], 'hahaha' );
	}

	public function test_changeLoginUrl() {
		$url = $this->saml->changeLoginUrl( 'https://pressbooks.test' );
		$this->assertContains( 'action=pb_shibboleth', $url );
	}

	public function test_showPasswordFields() {
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		$user = get_userdata( $user_id );
		$this->assertTrue( is_bool( $this->saml->showPasswordFields( true, $user ) ) );
	}

	public function test_authenticate() {
		$result = $this->saml->authenticate( null, 'test', 'test' );
		$this->assertNull( $result );

		$_REQUEST['action'] = 'pb_shibboleth';
		$this->saml->setAuth( $this->getMockAuth() );
		$result = $this->saml->authenticate( null, 'test', 'test' );
		$this->assertTrue( $result instanceof \WP_Error );
		$this->assertEquals( $result->get_error_message(), 'Mock object was here' );
	}

	public function test_samlMetadata() {
		ob_start();
		$this->saml->samlMetadata();
		$buffer = ob_get_clean();
		$this->assertTrue( simplexml_load_string( $buffer ) !== false );
		$this->assertContains( 'AssertionConsumerService', $buffer );
		$this->assertContains( 'SingleLogoutService', $buffer );
	}

	// TODO
	// test_samlAssertionConsumerService
	// test_samlSingleLogoutService
	// test_logoutRedirect

	public function test_loginEnqueueScripts() {
		$this->saml->loginEnqueueScripts();
		$this->assertContains( 'pressbooks-shibboleth-sso', get_echo( 'wp_print_scripts' ) );
	}

	public function test_loginForm() {
		ob_start();
		$this->saml->loginForm();
		$buffer = ob_get_clean();
		$this->assertContains( '<div id="pb-shibboleth-wrap">', $buffer );
	}

	public function test_handleLoginAttempt_and_matchUser_and_so_on() {
		$prefix = uniqid( 'test' );
		$email = "{$prefix}@pressbooks.test";

		// User doesn't exist
		$user = $this->saml->matchUser( $prefix );
		$this->assertFalse( $user );
		try {
			$this->saml->handleLoginAttempt( $prefix, $email );
			$this->assertInstanceOf( '\WP_User', get_user_by( 'email', $email ) );
			$this->assertContains( $_SESSION['pb_notices'][0], 'Registered and logged in!' );
		} catch ( \Exception $e ) {
			$this->fail( $e->getMessage() );
		}

		// User was created
		$user = $this->saml->matchUser( $prefix );
		$this->assertInstanceOf( '\WP_User', $user );

		// User exists
		try {
			$this->saml->handleLoginAttempt( $prefix, $email );
			$this->assertContains( $_SESSION['pb_notices'][0], 'Logged in!' );
		} catch ( \Exception $e ) {
			$this->fail( $e->getMessage() );
		}
	}

	public function test_handleLoginAttempt_exceptions() {
		try {
			$this->saml->handleLoginAttempt( '1', '1' );
		} catch ( \Exception $e ) {
			$this->assertContains( 'Please enter a valid email address', $e->getMessage() );
			$this->assertContains( 'Username must be at least 4 characters', $e->getMessage() );
			$this->assertContains( 'usernames must have letters too', $e->getMessage() );
			return;
		}
		$this->fail();
	}


	public function test_endLogin() {
		// Plan A
		$_SESSION[ $this->saml::SIGN_IN_PAGE ] = 'https://pressbooks.test';
		$this->saml->endLogin( 'My first message' );
		$this->assertTrue( in_array( 'My first message', $_SESSION['pb_notices'] ) );

		// Plan B
		unset( $_SESSION[ $this->saml::SIGN_IN_PAGE ] );
		$this->saml->endLogin( 'My second message' );
		$this->assertTrue( in_array( 'My second message', $_SESSION['pb_notices'] ) );
	}

	public function test_trackHomeUrl() {
		unset( $_SESSION[ $this->saml::SIGN_IN_PAGE ] );
		$this->saml->trackHomeUrl();
		$this->assertNotEmpty( $_SESSION[ $this->saml::SIGN_IN_PAGE ] );
	}

	public function test_authenticationFailedMessage() {
		$msg = $this->saml->authenticationFailedMessage( 'create' );
		$this->assertEquals( 'SAML authentication failed.', $msg );
		$msg = $this->saml->authenticationFailedMessage( 'refuse' );
		$this->assertContains( 'To request an account', $msg );
		$this->assertContains( '@', $msg );
	}

	public function test_getAdminEmail() {
		$email = $this->saml->getAdminEmail();
		$this->assertContains( '@', $email );
	}

}