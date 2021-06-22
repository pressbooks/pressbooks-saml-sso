<?php

use Aws\S3\S3Client as S3Client;
use Pressbooks\Log;

class SamlTest extends \WP_UnitTestCase {

	const TEST_FILE_PATH = __DIR__ . '/data/saml-log.csv';

	// ------------------------------------------------------------------------
	// Setup
	// ------------------------------------------------------------------------

	/**
	 * @var \PressbooksSamlSso\SAML
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
	 * @return \PressbooksSamlSso\Admin
	 */
	protected function getMockAdmin() {

		$stub1 = $this
			->getMockBuilder( '\PressbooksSamlSso\Admin' )
			->getMock();
		$stub1
			->method( 'getOptions' )
			->willReturn( $this->getTestOptions() );

		return $stub1;
	}

	protected function getMockAuth() {
		$stub1 = $this
			->getMockBuilder( '\OneLogin\Saml2\Auth' )
			->disableOriginalConstructor()
			->getMock();
		$stub1
			->method( 'redirectTo' )
			->willReturn( null );

		return $stub1;
	}

	/**
	 * @return \OneLogin\Saml2\Auth
	 */
	protected function getMockAuthForLogin() {
		$stub1 = $this->getMockAuth();
		$stub1
			->method( 'login' )
			->willThrowException( new \LogicException( 'Mock object was here' ) );

		return $stub1;

	}

	/**
	 * @return \OneLogin\Saml2\Auth
	 */
	protected function getMockAuthForAcs() {
		$stub1 = $this->getMockAuth();
		$stub1
			->method( 'processResponse' )
			->willReturn( null );
		$stub1
			->method( 'isAuthenticated' )
			->willReturn( true );
		$stub1
			->method( 'getAttributes' )
			->willReturn(
				[
					$this->saml::SAML_MAP_FIELDS['uid'] => [ 'uid' ],
					$this->saml::SAML_MAP_FIELDS['mail'] => [ 'uid@pressbooks.test' ],
				]
			);

		return $stub1;
	}

	/**
	 * @param $attributes - Attributes to mock
	 * @return \OneLogin\Saml2\Auth
	 */
	protected function getMockAuthForAttributes( $attributes ) {
		$stub1 = $this->getMockAuth();
		$stub1
			->method( 'getAttributes' )
			->willReturn( $attributes );
		return $stub1;
	}

	/**
	 * @param $attributes - Attributes to mock
	 * @return \OneLogin\Saml2\Auth
	 */
	protected function getMockAuthForFriendlyAttributes( $attributes ) {
		$stub1 = $this->getMockAuth();
		$stub1
			->method( 'getAttributesWithFriendlyName' )
			->willReturn( $attributes );
		return $stub1;
	}

	/**
	 * @return \PressbooksSamlSso\SAML
	 */
	protected function getSaml() {

		// Ignore session warnings
		PHPUnit_Framework_Error_Notice::$enabled = false;
		PHPUnit_Framework_Error_Warning::$enabled = false;
		ini_set( 'error_reporting', 0 );
		ini_set( 'display_errors', 0 );

		$saml = new \PressbooksSamlSso\SAML( $this->getMockAdmin(), $this->setS3ClientMock() );

		PHPUnit_Framework_Error_Notice::$enabled = true;
		PHPUnit_Framework_Error_Warning::$enabled = true;
		ini_set( 'error_reporting', 1 );
		ini_set( 'display_errors', 1 );

		return $saml;
	}

	private function setS3ClientMock() {
		$s3_client_mock = $this
			->getMockBuilder( S3Client::class )
			->disableOriginalConstructor()
			->setMethods([
				'registerStreamWrapper',
			])
			->getMock();
		$s3_provider_mock = new Log\S3StorageProvider( 'tests/data', 'log.csv' );
		$s3_provider_mock->setClient( $s3_client_mock );
		$s3_provider_mock->setFilePath( self::TEST_FILE_PATH );
		return new Log\Log( $s3_provider_mock );
	}

	public function setUp() {
		parent::setUp();
		unset( $_SESSION );
		$this->saml = $this->getSaml();
		if( file_exists( self::TEST_FILE_PATH ) ){
			unlink( self::TEST_FILE_PATH );
		}
	}

	public function test_getInstance() {
		$this->setEnvironmentVariablesForStorageProvider();
		$this->saml = $this->getSaml();
		$saml = $this->saml->init();
		$this->assertInstanceOf( '\PressbooksSamlSso\SAML', $saml );
	}

	private function setEnvironmentVariablesForStorageProvider() {
		putenv( 'LOG_LOGIN_ATTEMPTS=1' );
		putenv( 'AWS_S3_OIDC_BUCKET=fakeBucket' );
		putenv( 'AWS_SECRET_ACCESS_KEY=fakeAccessKey' );
		putenv( 'AWS_ACCESS_KEY_ID=fakeKeyId' );
		putenv( 'AWS_S3_VERSION=fake' );
		putenv( 'AWS_S3_REGION=fakeRegion' );
	}

	// ------------------------------------------------------------------------
	// Tests
	// ------------------------------------------------------------------------

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
		$this->assertEquals( $s['sp']['attributeConsumingService']['serviceName'], 'Pressbooks' );
		$this->assertEquals( $s['sp']['attributeConsumingService']['requestedAttributes'][0]['friendlyName'], 'uid' );
		$this->assertEquals( $s['sp']['attributeConsumingService']['requestedAttributes'][1]['friendlyName'], 'mail' );
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
		$this->saml->setSamlSettings( 1, 2, 3, 4 );
		$s = $this->saml->getSamlSettings();
		$this->assertEquals( $s['idp']['entityId'], 1 );
		$this->assertEquals( $s['idp']['singleSignOnService']['url'], 2 );
		$this->assertEquals( $s['idp']['x509cert'], 3 );
		$this->assertEquals( $s['idp']['singleLogoutService']['url'], 4 );
		$this->assertEquals( $s['sp']['attributeConsumingService']['serviceName'], 'Pressbooks' );
		$this->assertEquals( $s['sp']['attributeConsumingService']['requestedAttributes'][0]['friendlyName'], 'uid' );
		$this->assertEquals( $s['sp']['attributeConsumingService']['requestedAttributes'][1]['friendlyName'], 'mail' );
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
		$this->saml->setAuth( $this->getMockAuthForLogin() );
		$result = $this->saml->authenticate( null, 'test', 'test' );
		$this->assertTrue( $result instanceof \WP_Error );
		$this->assertEquals( $result->get_error_message(), 'Mock object was here' );
	}

	public function test_authenticateFalseNetIdAndMail() {
		$_REQUEST['action'] = 'pb_shibboleth_nothing';
		$_SESSION[ \PressbooksSamlSso\SAML::USER_DATA ] = [ 'nothing' ];
		$result = $this->saml->authenticate( null, 'test22', 'test' );
		$this->assertInstanceOf( '\WP_Error', $result );
	}

	public function test_associateUser() {
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		$user = get_userdata( $user_id );
		$this->assertTrue( $this->saml->associateUser( false, $user->user_email ) );
	}

	public function test_authenticate_session() {
		$_SESSION['pb_saml_user_data'] = [
			$this->saml::SAML_MAP_FIELDS['uid'] => [ 'uid' ],
			$this->saml::SAML_MAP_FIELDS['mail'] => [ 'uid@pressbooks.test' ],
		];
		ob_start();
		$result = $this->saml->authenticate( null, 'test', 'test' );
		$file_content = str_getcsv( file_get_contents( self::TEST_FILE_PATH ) );
		$this->assertEquals( 'email from SAML attributes', $file_content[1] );
		$this->assertContains(
			$_SESSION['pb_saml_user_data'][ $this->saml::SAML_MAP_FIELDS['mail'] ][0],
			$file_content[2]
		);
		$this->assertInstanceOf( '\WP_Error', $result );
	}

	public function test_samlMetadata() {
		ob_start();
		$this->saml->samlMetadata();
		$buffer = ob_get_clean();
		$this->assertTrue( simplexml_load_string( $buffer ) !== false );
		$this->assertContains( 'AssertionConsumerService', $buffer );
		$this->assertContains( 'SingleLogoutService', $buffer );
	}

	public function test_samlAssertionConsumerService() {
		try {
			$_POST['SAMLResponse'] = '<garbage>';
			$this->saml->samlAssertionConsumerService();
		} catch ( Exception $e ) {
			$this->assertContains( 'SAML Response could not be processed', $e->getMessage() );
		}

		unset( $_POST['SAMLResponse'] );
		$this->saml->setAuth( $this->getMockAuthForAcs() );
		$this->saml->samlAssertionConsumerService();
		$this->assertEquals( $_SESSION['pb_saml_user_data'][ $this->saml::SAML_MAP_FIELDS['uid'] ][0], 'uid' );
		$this->assertEquals( $_SESSION['pb_saml_user_data'][ $this->saml::SAML_MAP_FIELDS['mail'] ][0], 'uid@pressbooks.test' );
		$file_content = str_getcsv( file_get_contents( self::TEST_FILE_PATH ) );
		$this->assertEquals( 'NameID of the assertion', $file_content[1] );
		$this->assertEquals( 'NameID SP NameQualifier of the assertion', $file_content[3] );
	}

	public function test_parseAttributeStatement() {
		try {
			$this->saml->parseAttributeStatement();
		} catch ( Exception $e ) {
			$this->assertContains( 'Missing SAML', $e->getMessage() );
		}

		$mock_attributes = [
			$this->saml::SAML_MAP_FIELDS['uid'] => [ 'adfs' ],
			$this->saml::SAML_MAP_FIELDS['mail'] => [ 'adfs@pressbooks.test' ],
		];
		$this->saml->setAuth( $this->getMockAuthForAttributes( $mock_attributes ) );
		$attr = $this->saml->parseAttributeStatement();
		$this->assertEquals( $attr[ $this->saml::SAML_MAP_FIELDS['uid'] ][0], 'adfs' );
		$this->assertEquals( $attr[ $this->saml::SAML_MAP_FIELDS['mail'] ][0], 'adfs@pressbooks.test' );
	}

	public function test_parseAttributeStatementFriendlyAttributes() {
		$mock_attributes = [
			'wrongUid' => [ 'Wrong UID Friendly' ],
			'mail' => [ 'mailFriendly@pressbooks.test' ],
		];
		$this->saml->setAuth( $this->getMockAuthForAttributes( $mock_attributes ) );
		try {
			$this->saml->parseAttributeStatement();
		} catch ( Exception $e ) {
			$this->assertContains( 'Missing SAML', $e->getMessage() );
		}

		$mock_attributes = [
			'uid' => [ 'fake_friendly_uid' ],
		];
		$this->saml->setAuth( $this->getMockAuthForFriendlyAttributes( $mock_attributes ) );
		$attributes = $this->saml->parseAttributeStatement();
		$this->assertEquals( $attributes['friendlyAttributes'][ 'uid' ][0], 'fake_friendly_uid' );

		$mock_attributes = [
			$this->saml::SAML_MAP_FIELDS['eduPersonPrincipalName'] => [ 'fake_eppn@fake.com' ]
		];
		$this->saml->setAuth( $this->getMockAuthForAttributes( $mock_attributes ) );
		$attributes = $this->saml->parseAttributeStatement();
		$this->assertEquals( $attributes[ $this->saml::SAML_MAP_FIELDS['eduPersonPrincipalName'] ][0], 'fake_eppn@fake.com' );
	}

	public function test_usernameAttributeWithAt() {
		$attributes = [
			'urn:oid:0.9.2342.19200300.100.1.1' => ['michael@jackson.com'],
			'urn:oid:0.9.2342.19200300.100.1.3' => ['michaeljackson@jackson5.com']
		];
		$this->assertEquals( $this->saml->getUsernameByAttributes( $attributes ), 'michael' );
	}

	public function test_getUsernameByAttributes() {
		$attributes = [
			'nonuid' => ['novalue'],
			'friendlyAttributes' => [],
			$this->saml::SAML_MAP_FIELDS['eduPersonPrincipalName'] => [ 'fake_eppn@fake.com' ]
		];
		$this->assertEquals( $this->saml->getUsernameByAttributes( $attributes ), 'fake_eppn' );

		$attributes = [
			'nonuid' => ['novalue'],
			'nonEduPersonPrincipalName' => [ 'novalue' ],
			'friendlyAttributes' => [
				'uid' => ['fake_uid'],
			],
		];
		$this->assertEquals( $this->saml->getUsernameByAttributes( $attributes ), 'fake_uid' );

		$attributes = [
			$this->saml::SAML_MAP_FIELDS['uid'] => ['fake_uid'],
			'nonEduPersonPrincipalName' => [ 'eppn@fake.com' ],
			'friendlyAttributes' => [
				'uid' => ['fake_uid_friendly'],
			],
		];
		$this->assertEquals( $this->saml->getUsernameByAttributes( $attributes ), 'fake_uid' );
		$attributes = [
			'nonuid' => ['fake_uid'],
			'nonEduPersonPrincipalName' => [ 'eppn@fake.com' ],
			'friendlyAttributes' => [
				'nonuid' => ['fake_uid_friendly'],
			],
		];
		$this->assertFalse( $this->saml->getUsernameByAttributes( $attributes ) );
	}

	public function test_getEmailByAttributes() {
		$attributes = [
			'friendlyAttributes' => [],
			$this->saml::SAML_MAP_FIELDS['mail'] => [ 'fake_mail@fake.com' ],
		];
		$this->assertEquals( $this->saml->getEmailByAttributes( $attributes, 'fakeuid' ), 'fake_mail@fake.com' );

		$attributes = [
			'friendlyAttributes' => [
				'mail' => [ 'fake_mail@fake.com' ],
			],
		];
		$this->assertEquals( $this->saml->getEmailByAttributes( $attributes, 'fakeuid' ), 'fake_mail@fake.com' );

		$attributes = [
			'nomail' => 'nomail@nomail.com',
			$this->saml::SAML_MAP_FIELDS['eduPersonPrincipalName'] => [ 'fake_eppn@fake.com' ],
			'friendlyAttributes' => [
				'noMail' => [ 'fake_mail@fake.com' ],
			],
		];
		$this->assertEquals( $this->saml->getEmailByAttributes( $attributes, 'fakeuid' ), 'fake_eppn@fake.com' );

		$attributes = [
			'nomail' => 'nomail@nomail.com',
			'friendlyAttributes' => [
				'noMail' => [ 'fake_mail@fake.com' ],
			],
		];
		$this->assertEquals( $this->saml->getEmailByAttributes( $attributes, 'fakeuid' ), 'fakeuid@127.0.0.1' );
	}

	// TODO
	// test_samlSingleLogoutService
	// test_logoutRedirect

	public function test_loginEnqueueScripts() {
		$this->saml->loginEnqueueScripts();
		$this->assertContains( 'pressbooks-saml-sso', get_echo( 'wp_print_scripts' ) );
	}

	public function test_loginForm() {
		ob_start();
		$this->saml->loginForm();
		$buffer = ob_get_clean();
		$this->assertContains( '<div id="pb-saml-wrap">', $buffer );
	}

	public function test_matchUserEmpty() {
		$this->assertFalse( $this->saml->matchUser( false ) );
	}

	public function test_handleLoginAttempt_and_matchUser_and_so_on() {
		$prefix = uniqid( 'test' );
		$email = "{$prefix}@pressbooks.test";
		$_COOKIE['PHPSESSID'] = 'fakeSessionID';
		$_COOKIE['wordpress_sec_123123'] = 'fake wp session';
		$_COOKIE['wordpress_logged_in_123123'] = 'fake wp session login';

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

		$file_content = str_getcsv( file_get_contents( self::TEST_FILE_PATH ) );
		$this->assertEquals( 'User metadata stored', $file_content[1] );
		$this->assertEquals( 'Cookies', $file_content[3] );
		$this->assertContains( 'wordpress_sec_', $file_content[4] );
		$this->assertContains( 'PHPSESSID', $file_content[4] );
		$this->assertContains( 'wordpress_logged_in_', $file_content[4] );
		$this->assertEquals( 'Username associated', $file_content[5] );
		$this->assertContains( $prefix, $file_content[6] );
		$this->assertEquals( 'Session after logged [Associated]', $file_content[7] );

		// User was created
		$user = $this->saml->matchUser( $prefix );
		$this->assertInstanceOf( '\WP_User', $user );

		$this->saml->linkAccount( $user->ID, $email );
		$user_meta = get_user_meta( $user->ID, \PressbooksSamlSso\SAML::META_KEY );
		$this->assertContains( $prefix, $user_meta[0] );
		$this->assertContains( $email, $user_meta[1] );

		// User exists
		try {
			$this->saml->handleLoginAttempt( $prefix, $email );
			$this->assertContains( $_SESSION['pb_notices'][0], 'Logged in!' );
		} catch ( \Exception $e ) {
			$this->fail( $e->getMessage() );
		}
	}

	public function test_findExistingUser() {
		// Create a new user
		$prefix = uniqid( 'test' );
		$email = "{$prefix}@pressbooks.test";
		$user_id = wp_create_user( $prefix, wp_generate_password(), $email );
		$this->assertTrue( is_numeric( $user_id ) && $user_id > 0 );

		// Try to find the user and fail
		$this->assertFalse( $this->saml->findExistingUser( 'nobody@pressbooks.test' ) );

		// Try to find the user and fail again
		$_SESSION[ \PressbooksSamlSso\SAML::USER_DATA ] = [
			'mail' => [ 'one@pressbooks.test', 'two@pressbooks.test' ],
		];
		$this->assertFalse( $this->saml->findExistingUser( 'nobody@pressbooks.test' ) );

		// Try to find the user and succeed thanks to fallback eduPersonPrincipalName info in the session
		$_SESSION[ \PressbooksSamlSso\SAML::USER_DATA ] = [
			$this->saml::SAML_MAP_FIELDS['mail'] => [ 'one@pressbooks.test', 'two@pressbooks.test' ],
			$this->saml::SAML_MAP_FIELDS['eduPersonPrincipalName'] => [ 'three@pressbooks.test', $email ],
		];
		$user = $this->saml->findExistingUser( 'nobody@pressbooks.test' );
		$this->assertInstanceOf( '\WP_User', $user );
		unset( $_SESSION[ \PressbooksSamlSso\SAML::USER_DATA ] );
	}

	public function test_handleLoginAttempt_exceptions() {
		try {
			$bad_net_id = '111111111111111111111111111111111111111111111111111111111111'; // 61 characters
			$bad_email = '1';
			$this->saml->handleLoginAttempt( $bad_net_id, $bad_email );
		} catch ( \Exception $e ) {
			$this->assertContains( 'Please enter a valid email address', $e->getMessage() );
			$this->assertContains( 'Username may not be longer than 60 characters', $e->getMessage() );
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

	public function test_trackRedirectUrl() {
		$home_url = home_url();
		unset( $_SESSION[ $this->saml::SIGN_IN_PAGE ] );
		$this->saml->trackRedirectUrl(); // Initial default
		$this->assertEquals( $home_url, $_SESSION[ $this->saml::SIGN_IN_PAGE ] );

		$redirect_to = home_url( '/some/path' );
		$_REQUEST['redirect_to'] = $redirect_to;
		$this->saml->trackRedirectUrl(); // No reset
		$this->assertNotEquals( $redirect_to, $_SESSION[ $this->saml::SIGN_IN_PAGE ] );

		$this->saml->trackRedirectUrl( true ); // Yes reset
		$this->assertEquals( $redirect_to, $_SESSION[ $this->saml::SIGN_IN_PAGE ] );

		$redirect_to = 'https://google.com';
		$_REQUEST['redirect_to'] = $redirect_to;
		$this->saml->trackRedirectUrl( true ); // Yes reset, invalid redirect URL
		$this->assertEquals( $home_url, $_SESSION[ $this->saml::SIGN_IN_PAGE ] );
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

	public function test_sanitizeUser() {
		$this->assertEquals( 'test', $this->saml->sanitizeUser( 'test' ) );
		$this->assertEquals( 'test', $this->saml->sanitizeUser( '(:test:)' ) );
		$this->assertEquals( 'tst1', $this->saml->sanitizeUser( 'tst' ) );
		$this->assertEquals( 'tst1', $this->saml->sanitizeUser( '(:tst:)' ) );
		$this->assertEquals( 'yo11', $this->saml->sanitizeUser( 'yo' ) );
		$this->assertEquals( 'yo11', $this->saml->sanitizeUser( '(:yo:)' ) );
		$this->assertEquals( '1111a', $this->saml->sanitizeUser( '1111' ) );
		$this->assertEquals( '1a11', $this->saml->sanitizeUser( '1' ) );
	}

}
