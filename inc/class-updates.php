<?php

namespace Pressbooks\Shibboleth;

class Updates {

	/**
	 * @var Updates
	 */
	private static $instance = null;

	/**
	 * @return Updates
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * @param Updates $obj
	 */
	static public function hooks( Updates $obj ) {
		if ( \Pressbooks\Book::isBook() === false ) {
			add_action( 'plugins_loaded', [ $obj, 'gitHubUpdater' ] );
		}
	}

	/**
	 * Constructor.
	 */
	function __construct() {
	}

	/**
	 * GitHub Plugin Update Checker
	 * Hooked into action `plugins_loaded`
	 *
	 * @see https://github.com/YahnisElsts/plugin-update-checker
	 */
	public function gitHubUpdater() {
		$updater = \Puc_v4_Factory::buildUpdateChecker(
			'https://github.com/pressbooks/pressbooks-shibboleth-sso/',
			\Pressbooks\Utility\absolute_path( __DIR__ . '/../pressbooks-shibboleth-sso.php' ), // Fully qualified path to the main plugin file
			'pressbooks-shibboleth-sso',
			24
		);
		$updater->setBranch( 'master' );
		$updater->getVcsApi()->enableReleaseAssets();
	}

}
