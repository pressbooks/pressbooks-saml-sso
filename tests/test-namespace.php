<?php

class NamespaceTest extends \WP_UnitTestCase {


	/**
	 * Test PB style class initializations
	 */
	public function test_classInitConventions() {
		global $wp_filter;
		$classes = [
			'\PressbooksShibbolethSso\Admin',
			'\PressbooksShibbolethSso\SAML',
			'\PressbooksShibbolethSso\Updates',
		];
		foreach ( $classes as $class ) {
			$result = $class::init();
			$this->assertInstanceOf( $class, $result );
			$class::hooks( $result );
			$this->assertNotEmpty( $wp_filter );
		}
	}

	public function test_blade() {
		$blade = \PressbooksShibbolethSso\blade();
		$this->assertTrue( is_object( $blade ) );
	}

	public function test_login_url() {
		$url = \PressbooksShibbolethSso\login_url();
		$this->assertTrue( filter_var( $url, FILTER_VALIDATE_URL ) !== false );
		$this->assertContains( 'action=pb_shibboleth', $url );
	}

	public function test_metadata_url() {
		$url = \PressbooksShibbolethSso\metadata_url();
		$this->assertTrue( filter_var( $url, FILTER_VALIDATE_URL ) !== false );
		$this->assertContains( 'action=pb_shibboleth_metadata', $url );
	}

	public function test_acs_url() {
		$url = \PressbooksShibbolethSso\acs_url();
		$this->assertTrue( filter_var( $url, FILTER_VALIDATE_URL ) !== false );
		$this->assertContains( 'action=pb_shibboleth_acs', $url );
	}

	public function test_sls_url() {
		$url = \PressbooksShibbolethSso\sls_url();
		$this->assertTrue( filter_var( $url, FILTER_VALIDATE_URL ) !== false );
		$this->assertContains( 'action=pb_shibboleth_sls', $url );
	}

}