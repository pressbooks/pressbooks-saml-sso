<?php

class UpdatesTest extends \WP_UnitTestCase {

	/**
	 * @var \Pressbooks\Shibboleth\Updates
	 */
	protected $updates;

	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
		$this->updates = new \Pressbooks\Shibboleth\Updates();
	}

	public function test_gitHubUpdater() {
		$this->updates->gitHubUpdater();
		$this->assertTrue( has_filter( 'puc_is_slug_in_use-pressbooks-shibboleth-sso' ) );
	}

}