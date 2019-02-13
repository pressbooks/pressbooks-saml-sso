<?php

class UpdatesTest extends \WP_UnitTestCase {

	/**
	 * @var \PressbooksShibbolethSso\Updates
	 */
	protected $updates;

	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
		$this->updates = new \PressbooksShibbolethSso\Updates();
	}

	public function test_gitHubUpdater() {
		$this->updates->gitHubUpdater();
		$this->assertTrue( has_filter( 'puc_is_slug_in_use-pressbooks-shibboleth-sso' ) );
	}

}