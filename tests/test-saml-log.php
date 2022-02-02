<?php

use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Maxbanton\Cwh\Handler\CloudWatch;
use Monolog\Logger;
use Pressbooks\Log;
use PressbooksSamlSso\SAML;
use Aws\S3\S3Client as S3Client;

class LogTest extends \WP_UnitTestCase {

	/**
	 * @var Log\Log
	 */
	private $log;

	/**
	 * @var SAML
	 */
	private $saml;

	const TEST_FILE_PATH = 'tests/data/saml-log.csv';

	/**
	 * Test setup
	 */
	public function set_up() {
		$this->setEnvironmentVariables();
		unset( $_SESSION );
	}

	private function setSaml( string $storage_provider ) {
		switch ( $storage_provider ) {
			case 'S3':
				$this->setS3ClientMock();
				break;
			case 'CloudWatch':
				$this->setLoggerMock();
				break;
			default:
				$this->log = null;
				break;
		}
		$this->saml = new SAML( $this->getMockAdmin(), $this->log );
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

	private function setEnvironmentVariables() {
		putenv( 'LOG_LOGIN_ATTEMPTS=1' );
		putenv( 'AWS_S3_OIDC_BUCKET=fakeBucket' );
		putenv( 'AWS_SECRET_ACCESS_KEY=fakeAccessKey' );
		putenv( 'AWS_ACCESS_KEY_ID=fakeKeyId' );
		putenv( 'AWS_S3_VERSION=fake' );
		putenv( 'AWS_S3_REGION=fakeRegion' );
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
		$this->log = new Log\Log( $s3_provider_mock );
	}

	private function setLoggerMock() {
		$logger_mock = $this
			->getMockBuilder( Logger::class )
			->disableOriginalConstructor()
			->setMethods([
				'debug',
				'pushHandler',
			])
			->getMock();
		$logger_mock->expects( $this->any() )
			->method( 'debug' )
			->will( $this->onConsecutiveCalls( true ) );
		$logger_mock->expects( $this->any() )
			->method( 'pushHandler' )
			->will( $this->onConsecutiveCalls( true ) );
		$cloudwatch_logs_mock = $this
			->getMockBuilder( CloudWatchLogsClient::class )
			->disableOriginalConstructor()
			->getMock();
		$handler = $this
			->getMockBuilder( CloudWatch::class )
			->disableOriginalConstructor()
			->setMethods([
				'setFormatter',
			])
			->getMock();
		$handler->expects( $this->any() )
			->method( 'setFormatter' )
			->will( $this->onConsecutiveCalls( true ) );
		$cloudwatch_provider_mock = new Log\CloudWatchProvider( 90, 'pressbooks-logs', 'pressbooks-plugin', 'saml-logs' );
		$cloudwatch_provider_mock->setLogger( $logger_mock );
		$cloudwatch_provider_mock->setHandler( $handler );
		$cloudwatch_provider_mock->setClient( $cloudwatch_logs_mock );
		$this->log = new Log\Log( $cloudwatch_provider_mock );
	}

	/**
	 * Use Reflexion for private method
	 *
	 * @param $object
	 * @param string $method
	 * @param array $parameters
	 * @return mixed
	 * @throws ReflectionException
	 */
	private function callMethodForReflection($object, string $method , array $parameters = []) {
		try {
			$className = get_class( $object );
			$reflection = new \ReflectionClass( $className );
		} catch ( \ReflectionException $e ) {
			throw new \Exception( $e->getMessage() );
		}

		$method = $reflection->getMethod( $method );
		$method->setAccessible( true );

		return $method->invokeArgs( $object, $parameters );
	}

	/**
	 * @group log
	 */
	public function test_log_in_cloudwatch() {
		$this->setSaml( 'CloudWatch' );
		$this->assertTrue(
			$this->callMethodForReflection( $this->saml, 'logData', [ 'Test key 1', ['Test value'] ] )
		);
		$this->assertTrue(
			$this->callMethodForReflection(
				$this->saml,
				'logData',
				[
					'Test key 2', [
						'Test a' => 'Test b',
						'Test c' => 'Test d',
					],
					true
				]
			)
		);
	}

	/**
	 * @group log
	 */
	public function test_log_in_s3() {
		if( file_exists( self::TEST_FILE_PATH ) ){
			unlink( self::TEST_FILE_PATH );
		}
		$this->setSaml( 'S3' );
		$this->assertTrue(
			$this->callMethodForReflection( $this->saml, 'logData', [ 'Test key 1', ['Test value'] ] )
		);
		$this->assertTrue(
			$this->callMethodForReflection(
				$this->saml,
				'logData',
				[
					'Test key 2', [
						'Test a' => 'Test b',
						'Test c' => 'Test d',
					],
					true
				]
			)
		);
		$file_content = str_getcsv( file_get_contents( self::TEST_FILE_PATH ) );
		unlink( self::TEST_FILE_PATH );
		$this->assertEquals( 'Test key 1', $file_content[1] );
		$this->assertContains( 'Test value', $file_content[2] );
		$this->assertEquals( 'Test key 2', $file_content[3] );
		$this->assertContains( 'Test b', $file_content[4] );
		$this->assertContains( 'Test d', $file_content[4] );
		$this->assertContains( '[Test a] =>', $file_content[4] );
		$this->assertContains( '[Test c] =>', $file_content[4] );
	}

	/**
	 * @group log
	 */
	public function test_avoid_log() {
		if (file_exists(self::TEST_FILE_PATH)) {
			unlink(self::TEST_FILE_PATH);
		}
		$this->setSaml('NoLog');
		$this->assertFalse(
			$this->callMethodForReflection($this->saml, 'logData', ['Test key 1', ['Test value']])
		);
	}

}
