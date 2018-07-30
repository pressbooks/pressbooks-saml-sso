<?php

class NamespaceTest extends \WP_UnitTestCase {


	/**
	 * Test PB style class initializations
	 */
	public function test_classInitConventions() {
		global $wp_filter;
		$classes = [
			'\Pressbooks\Shibboleth\Admin',
			'\Pressbooks\Shibboleth\SAML',
			'\Pressbooks\Shibboleth\Updates',
		];
		foreach ( $classes as $class ) {
			$result = $class::init();
			$this->assertInstanceOf( $class, $result );
			$class::hooks( $result );
			$this->assertNotEmpty( $wp_filter );
		}
	}

	public function test_blade() {
		$blade = \Pressbooks\Shibboleth\blade();
		$this->assertTrue( is_object( $blade ) );
	}

	public function test_login_url() {
		$url = \Pressbooks\Shibboleth\login_url();
		$this->assertTrue( filter_var( $url, FILTER_VALIDATE_URL ) !== false );
		$this->assertContains( 'action=pb_shibboleth', $url );
	}

	public function test_metadata_url() {
		$url = \Pressbooks\Shibboleth\metadata_url();
		$this->assertTrue( filter_var( $url, FILTER_VALIDATE_URL ) !== false );
		$this->assertContains( 'saml=metadata', $url );
	}

	public function test_acs_url() {
		$url = \Pressbooks\Shibboleth\acs_url();
		$this->assertTrue( filter_var( $url, FILTER_VALIDATE_URL ) !== false );
		$this->assertContains( 'saml=acs', $url );
	}

	public function test_sls_url() {
		$url = \Pressbooks\Shibboleth\sls_url();
		$this->assertTrue( filter_var( $url, FILTER_VALIDATE_URL ) !== false );
		$this->assertContains( 'saml=sls', $url );
	}

}