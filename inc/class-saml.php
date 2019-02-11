<?php

namespace Pressbooks\Shibboleth;

use function Pressbooks\Utility\empty_space;
use function Pressbooks\Utility\str_remove_prefix;
use function Pressbooks\Utility\str_starts_with;
use PressbooksMix\Assets;

/**
 * SAML: Security Assertion Markup Language
 */
class SAML {

	const META_KEY = 'pressbooks_saml_identity';

	const SIGN_IN_PAGE = 'pb_saml_sign_in_page';

	const USER_DATA = 'pb_saml_user_data';

	const AUTH_N_REQUEST_ID = 'pb_saml_auth_n_request_id';

	/**
	 * @var SAML
	 */
	private static $instance = null;

	/**
	 * @var string
	 */
	private $loginUrl;

	/**
	 * @var int
	 */
	private $currentUserId = 0;

	/**
	 * @var string
	 */
	private $provision = 'refuse';

	/**
	 * @var bool
	 */
	private $bypass = false;

	/**
	 * @var Admin
	 */
	private $admin;

	/**
	 * @var array
	 */
	private $options = [];

	/**
	 * @var bool
	 */
	private $forcedRedirection = false;

	/**
	 * @var bool
	 */
	private $samlClientIsReady = false;

	/**
	 * @var \OneLogin\Saml2\Auth
	 */
	private $auth;

	/**
	 * OneLogin SAML Toolkit Settings
	 *
	 * @var array
	 */
	private $samlSettings = [];

	/**
	 * @return SAML
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			$admin = Admin::init();
			self::$instance = new self( $admin );
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * @param SAML $obj
	 */
	static public function hooks( SAML $obj ) {
		add_filter( 'authenticate', [ $obj, 'authenticate' ], 10, 3 );
		add_action( 'login_enqueue_scripts', [ $obj, 'loginEnqueueScripts' ] );
		add_action( 'login_form', [ $obj, 'loginForm' ] );
		add_filter( 'logout_redirect', [ $obj, 'logoutRedirect' ] );
		add_filter( 'show_password_fields', [ $obj, 'showPasswordFields' ], 10, 2 );
	}

	/**
	 * @param Admin $admin
	 */
	public function __construct( Admin $admin ) {
		$options = $admin->getOptions();

		$this->loginUrl = \Pressbooks\Shibboleth\login_url();
		$this->currentUserId = get_current_user_id();
		$this->provision = $options['provision'];
		$this->bypass = (bool) $options['bypass'];
		$this->forcedRedirection = (bool) $options['forced_redirection'];
		$this->admin = $admin;
		$this->options = $options;
		if ( $this->forcedRedirection ) {
			// TODO:
			// This hijacks the same logic as seen in the saml plugin.
			// If we want to support both Saml & CAS on the same site, then we'll need to handle the 'login_form_saml' action ourselves.
			add_filter( 'login_url', [ $this, 'changeLoginUrl' ], 999 );
		}

		// Set configuration in OneLogin format
		$this->setSamlSettings(
			$options['idp_entity_id'],
			$options['idp_sso_login_url'],
			$options['idp_x509_cert'],
			$options['idp_sso_logout_url'] ?? ''
		);

		// Verify the integrity of the configuration before passing to Auth to avoid things blowing up
		if ( ! $this->verifyPluginSetup( $options ) ) {
			if ( 'pb_shibboleth_admin' !== @$_REQUEST['page'] ) { // @codingStandardsIgnoreLine
				add_action(
					'network_admin_notices', function () {
						echo '<div id="message" class="error fade"><p>' . __( 'The Pressbooks Shibboleth Plugin is not configured correctly.', 'pressbooks-shibboleth-sso' ) . '</p></div>';
					}
				);
			}
		} else {
			try {
				$this->auth = new \OneLogin\Saml2\Auth( $this->getSamlSettings() );
				$this->samlClientIsReady = true;
			} catch ( \Exception $e ) {
				add_action(
					'network_admin_notices', function () use ( $e ) {
						echo '<div id="message" class="error fade"><p>' . __( 'The Pressbooks Shibboleth Plugin failed to initialize. Error: ', 'pressbooks-shibboleth-sso' ) . $e->getMessage() . '</p></div>';
					}
				);
			}
		}
	}

	/**
	 * For testing purposes only! Useful for setting a mock class.
	 *
	 * @param \OneLogin\Saml2\Auth $auth
	 */
	public function setAuth( $auth ) {
		$this->auth = $auth;
	}

	/**
	 * @param array $options
	 *
	 * @return bool
	 */
	public function verifyPluginSetup( $options ) {
		if ( empty( $options['idp_entity_id'] ) || empty( $options['idp_sso_login_url'] ) || empty( $options['idp_x509_cert'] ) ) {
			return false;
		}
		if ( ! filter_var( $options['idp_sso_login_url'], FILTER_VALIDATE_URL ) ) {
			return false;
		}
		return true;
	}


	/**
	 * @return array
	 */
	public function getSamlSettings() {
		return $this->samlSettings;
	}

	/**
	 * @param string $idp_entity_id
	 * @param string $idp_sso_login_url
	 * @param string $idp_x509_cert
	 * @param string $ipd_sso_logout_url (optional)
	 */
	public function setSamlSettings( $idp_entity_id, $idp_sso_login_url, $idp_x509_cert, $ipd_sso_logout_url = '' ) {
		$config = [
			'strict' => true,
			'debug' => defined( 'WP_DEBUG' ) && WP_DEBUG ? true : false,
			'baseurl' => null,
			'idp' => [
				'entityId' => $idp_entity_id,
				'singleSignOnService' => [
					'url' => $idp_sso_login_url,
					'binding' => \OneLogin\Saml2\Constants::BINDING_HTTP_REDIRECT,
				],
				'x509cert' => $idp_x509_cert,
			],
		];
		if ( ! empty_space( $ipd_sso_logout_url ) ) {
			$config['idp']['singleLogoutService']['url'] = $ipd_sso_logout_url;
			$config['idp']['singleLogoutService']['binding'] = \OneLogin\Saml2\Constants::BINDING_HTTP_REDIRECT;
		}
		if ( defined( 'PHP_SAML_SP_CERT_PATH' ) && is_file( PHP_SAML_SP_CERT_PATH ) ) {
			$config['sp']['x509cert'] = file_get_contents( PHP_SAML_SP_CERT_PATH );
		}
		if ( defined( 'PHP_SAML_SP_KEY_PATH' ) && is_file( PHP_SAML_SP_KEY_PATH ) ) {
			$config['sp']['privateKey'] = file_get_contents( PHP_SAML_SP_KEY_PATH );
		}

		/**
		 * @param array $config
		 *
		 * @since 1.0.0
		 */
		$config = apply_filters( 'pb_saml_auth_settings', $config );

		// This comes after the filter because we don't want others breaking our SP config
		$config['sp']['entityId'] = network_site_url( '/shibboleth', 'https' ); // Doesn't need to resolve
		$config['sp']['assertionConsumerService']['url'] = \Pressbooks\Shibboleth\acs_url();
		$config['sp']['singleLogoutService']['url'] = \Pressbooks\Shibboleth\sls_url();

		if ( ! empty( $config['sp']['x509cert'] ) ) {
			// Interoperable SAML 2.0 Web Browser SSO Profile
			$config['security'] = [
				'authnRequestsSigned' => false,
				'wantAssertionsSigned' => true,
				'wantAssertionsEncrypted' => true,
				'wantNameIdEncrypted' => false,
			];
		}

		$this->samlSettings = $config;
	}

	/**
	 * Change wp_login_url() to include an action param we use to trigger: do_action( "login_form_{$action}" )
	 *
	 * Hooked into filter: 'login_url'
	 *
	 *
	 * @param string $login_url The login URL. Not HTML-encoded.
	 *
	 * @return string
	 */
	public function changeLoginUrl( $login_url ) {
		$login_url = add_query_arg( 'action', 'pb_shibboleth', $login_url );
		return $login_url;
	}

	/**
	 * @param bool $show
	 * @param \WP_User $profileuser
	 *
	 * @return bool
	 */
	public function showPasswordFields( $show, $profileuser ) {
		if ( ! current_user_can( 'manage_network' ) ) {
			$pressbooks_saml_identity = get_user_meta( $profileuser->ID, self::META_KEY, true );
			if ( $pressbooks_saml_identity ) {
				$show = false;
			}
		}
		return $show;
	}

	/**
	 * @param null|\WP_User|\WP_Error $user WP_User if the user is authenticated. WP_Error or null otherwise.
	 * @param string $username Username or email address.
	 * @param string $password User password
	 *
	 * @throws \LogicException (for unit tests! will die() when in website mode)
	 * @return mixed
	 */
	public function authenticate( $user, $username, $password ) {
		$saml_action = '';
		$use_shibboleth = false;
		if ( isset( $_REQUEST['action'] ) && str_starts_with( $_REQUEST['action'], 'pb_shibboleth' ) ) { // @codingStandardsIgnoreLine
			$use_shibboleth = true;
			$saml_action = ltrim( str_remove_prefix( $_REQUEST['action'], 'pb_shibboleth' ), '_' ); // @codingStandardsIgnoreLine
		}

		if ( $saml_action === 'metadata' ) {
			$this->samlMetadata();
			$this->doExit();
		} elseif ( $this->samlClientIsReady && $use_shibboleth ) {
			try {
				$this->trackHomeUrl();
				ob_start();
				switch ( $saml_action ) {
					case 'acs':
						$this->samlAssertionConsumerService();
						$this->doExit();
						break;
					case 'sls':
						$this->samlSingleLogoutService();
						$this->doExit();
						break;
					default:
						if ( empty( $_SESSION[ self::USER_DATA ] ) ) {
							unset( $_SESSION[ self::AUTH_N_REQUEST_ID ] ); // Clear AuthNRequest
							$this->auth->login( $this->loginUrl ); // Redirect user to IdP, set RelayState to $this->loginUrl
							$this->doExit();
						} else {
							ob_end_clean();
							$attributes = $_SESSION[ self::USER_DATA ];
							$net_id = $attributes['uid'][0];
							$email = isset( $attributes['mail'][0] ) ? $attributes['mail'][0] : "{$net_id}@127.0.0.1";
							remove_filter( 'authenticate', [ $this, 'authenticate' ], 10 ); // Fix infinite loop
							/**
							 * @since 0.0.4
							 *
							 * @param string $email
							 * @param string $net_id
							 * @param string $plugin_name
							 */
							$email = apply_filters( 'pb_integrations_multidomain_email', $email, $net_id, 'pressbooks-shibboleth-sso' );
							$this->handleLoginAttempt( $net_id, $email );
						}
				}
			} catch ( \Exception $e ) {
				$buffer = ob_get_clean();
				if ( ! empty( $buffer ) ) {
					if ( defined( 'WP_TESTS_MULTISITE' ) ) {
						throw new \LogicException( $buffer );
					} else {
						die( $buffer );
					}
				} else {
					if ( $this->forcedRedirection ) {
						wp_die( $e->getMessage() );
					} else {
						return new \WP_Error( 'authentication_failed', $e->getMessage() );
					}
				}
			}
			@ob_end_clean(); // @codingStandardsIgnoreLine
			$message = $this->authenticationFailedMessage( $this->options['provision'] );
			if ( $this->forcedRedirection ) {
				wp_die( $message );
			} else {
				return new \WP_Error( 'authentication_failed', $message );
			}
		}
		return null;
	}

	/**
	 *
	 */
	public function samlMetadata() {
		try {
			$settings = new \OneLogin\Saml2\Settings( $this->getSamlSettings(), true );

			$valid_until = null;
			$cert = $settings->getSPcert();
			if ( ! empty( $cert ) ) {
				$parsed_cert = openssl_x509_parse( $cert );
				if ( ! empty( $parsed_cert['validTo_time_t'] ) ) {
					$valid_until = $parsed_cert['validTo_time_t'];
				}
			}

			$metadata = $settings->getSPMetadata( true, $valid_until );
			$errors = $settings->validateMetadata( $metadata );
			if ( empty( $errors ) ) {
				header( 'Content-Type: text/xml' );
				echo $metadata;
			} else {
				wp_die( __( 'Invalid SP metadata: ', 'pressbooks-shibboleth-sso' ) . implode( ', ', $errors ) );
			}
		} catch ( \Exception $e ) {
			wp_die( $e->getMessage() );
		}
	}

	/**
	 * @throws \Exception
	 * @throws \OneLogin\Saml2\Error
	 */
	public function samlAssertionConsumerService() {
		$request_id = isset( $_SESSION[ self::AUTH_N_REQUEST_ID ] ) ? $_SESSION[ self::AUTH_N_REQUEST_ID ] : null;
		unset( $_SESSION[ self::AUTH_N_REQUEST_ID ] ); // Don't reuse
		$this->auth->processResponse( $request_id );
		$errors = $this->auth->getErrors();
		if ( ! empty( $errors ) ) {
			$message = ' Errors: ' . implode( ', ', $errors );
			if ( $this->auth->getLastErrorReason() ) {
				$message .= '. Reason: ' . $this->auth->getLastErrorReason();
			}
			throw new \Exception( $message );
		}
		if ( ! $this->auth->isAuthenticated() ) {
			/* translators: Saml error reason */
			throw new \Exception( sprintf( __( 'Not authenticated. Reason: %s', 'pressbooks-shibboleth-sso' ), $this->auth->getLastErrorReason() ) );
		}

		// If we made it to here, then no exceptions were thrown, and everything is fine.
		// Now that the user has a session the SP allows the request to proceed.

		$attributes = $this->auth->getAttributesWithFriendlyName();
		if ( ! isset( $attributes['uid'] ) ) {
			$attributes = $this->auth->getAttributes();
			if ( ! isset( $attributes['uid'] ) ) {
				throw new \Exception( __( 'Missing SAML attributes: uid, mail', 'pressbooks-shibboleth-sso' ) );
			}
		}
		$_SESSION[ self::USER_DATA ] = $attributes;

		$redirect_to = filter_input( INPUT_POST, 'RelayState', FILTER_SANITIZE_URL );
		if ( $redirect_to && \OneLogin\Saml2\Utils::getSelfURL() !== $redirect_to ) {
			$this->auth->redirectTo( $redirect_to );
		} else {
			$this->auth->redirectTo( $this->loginUrl );
		}
	}


	/**
	 * @throws \Exception
	 * @throws \OneLogin\Saml2\Error
	 */
	public function samlSingleLogoutService() {
		$this->auth->processSLO();
		$errors = $this->auth->getErrors();
		if ( ! empty( $errors ) ) {
			throw new \Exception( implode( ', ', $errors ) );
		}
		if ( is_user_logged_in() ) {
			wp_logout();
		}
		wp_safe_redirect( add_query_arg( 'loggedout', true, wp_login_url() ) );
	}

	/**
	 * @param string $provision
	 *
	 * @see \Aldine\Helpers\handle_contact_form_submission for $email code
	 *
	 * @return string
	 */
	public function authenticationFailedMessage( $provision ) {
		if ( $provision === 'refuse' ) {
			$email = $this->getAdminEmail();
			$email = ( ! empty( $email ) ? ": {$email}" : '.' );
			/* translators: %s Pressbooks Network Manager email if found. */
			$message = sprintf( __( "Unable to log in: You do not have an account on this Pressbooks network. To request an account, please contact your institution's Pressbooks Network Manager%s", 'pressbooks-shibboleth-sso' ), $email );
		} else {
			$message = __( 'SAML authentication failed.', 'pressbooks-shibboleth-sso' );
		}
		return wp_strip_all_tags( $message );
	}

	/**
	 * @return string
	 */
	public function getAdminEmail() {
		$main_site_id = get_main_site_id();
		$email = get_blog_option( $main_site_id, 'pb_network_contact_email' ); // Aldine
		if ( empty( $email ) ) {
			$email = get_blog_option( $main_site_id, 'admin_email' ); // Main Site
			if ( empty( $email ) ) {
				$email = get_site_option( 'admin_email' ); // Main Network
			}
		}
		return $email ? $email : '';
	}

	/**
	 * @param string $redirect_to
	 *
	 * @throws \OneLogin\Saml2\Error
	 * @return string
	 */
	public function logoutRedirect( $redirect_to ) {
		if ( $this->samlClientIsReady ) {
			if ( $this->forcedRedirection || ! empty( $_SESSION[ self::USER_DATA ] ) || get_user_meta( $this->currentUserId, self::META_KEY, true ) ) {
				if ( ! empty( $this->auth->getSLOurl() ) ) {
					$this->auth->logout( add_query_arg( 'loggedout', true, wp_login_url() ) );
					$this->doExit();
				}
			}
		}
		return $redirect_to;
	}

	/**
	 * Add login CSS and JS
	 */
	public function loginEnqueueScripts() {
		$assets = new Assets( 'pressbooks-shibboleth-sso', 'plugin' );
		wp_enqueue_style( 'pb-saml-login', $assets->getPath( 'styles/login-form.css' ) );
		wp_enqueue_script( 'pb-saml-login', $assets->getPath( 'scripts/login-form.js' ), [ 'jquery' ] );
	}

	/**
	 * Print [ Connect via Saml ] button
	 */
	public function loginForm() {

		if ( ! $this->samlClientIsReady ) {
			return;
		}

		// Get the url string instead of doing a redirect. Store the AuthNRequest ID in a session in case the user clicks the url.
		$url = $this->auth->login( $this->loginUrl, [], false, false, true );
		$_SESSION[ self::AUTH_N_REQUEST_ID ] = $this->auth->getLastRequestID();

		$button_text = $this->options['button_text'];
		if ( empty( $button_text ) ) {
			$button_text = __( 'Connect via Shibboleth', 'pressbooks-shibboleth-sso' );
		}

		$this->trackHomeUrl( true );

		?>
		<div id="pb-shibboleth-wrap">
			<div class="pb-shibboleth-or">
				<span><?php esc_html_e( 'Or', 'pressbooks-shibboleth-sso' ); ?></span>
			</div>
			<?php
			printf(
				'<div class="shibboleth"><a href="%1$s" class="button button-hero shibboleth">%2$s</a></div>',
				$url,
				$button_text
			);
			?>
		</div>
		<?php
	}

	/**
	 * Login (or register and login) a WordPress user based on their Saml identity.
	 *
	 * @param string $net_id
	 * @param string $email An email
	 *
	 * @throws \Exception
	 */
	public function handleLoginAttempt( $net_id, $email ) {

		// Keep $_SESSION alive, Saml put info in it
		remove_action( 'wp_login', '_pb_session_kill' );

		// Try to find a matching WordPress user for the now-authenticated user's Saml identity
		$user = $this->matchUser( $net_id );

		if ( $user ) {
			// If a matching user was found, log them in
			$logged_in = \Pressbooks\Redirect\programmatic_login( $user->user_login );
			if ( $logged_in === true ) {
				$this->endLogin( __( 'Logged in!', 'pressbooks-shibboleth-sso' ) );
			}
		} else {
			$this->associateUser( $net_id, $email );
		}
	}

	/**
	 * Ends the login request by redirecting to the desired page
	 *
	 * @param string $msg
	 */
	public function endLogin( $msg ) {
		$_SESSION['pb_notices'][] = $msg;
		if ( is_user_logged_in() ) {
			if ( ! empty( $_SESSION[ self::SIGN_IN_PAGE ] ) ) {
				// Default behaviour: Redirect to the page they signed in from (network homepage or book homepage)
				$redirect_to = $_SESSION[ self::SIGN_IN_PAGE ];
				unset( $_SESSION[ self::SIGN_IN_PAGE ] ); // unset on success
				header( 'Location: ' . filter_var( $redirect_to, FILTER_SANITIZE_URL ) ); // Forced, not safe, redirection
				$this->doExit();
			} else {
				// Plan B
				$user = wp_get_current_user();
				$blog = get_active_blog_for_user( $user->ID );
				if ( $blog ) {
					header( 'Location: ' . filter_var( get_admin_url( $blog->blog_id ), FILTER_SANITIZE_URL ) ); // Forced, not safe, redirection
					$this->doExit();
				}
			}
		}
		wp_safe_redirect( wp_registration_url() );
		$this->doExit();
	}

	/**
	 * Attempt to match a WordPress user to the Saml identity.
	 *
	 * @param string $net_id
	 *
	 * @return false|\WP_User
	 */
	public function matchUser( $net_id ) {
		global $wpdb;
		$condition = "{$net_id}|%";
		$query_result = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value LIKE %s", self::META_KEY, $condition ) );
		// attempt to get a WordPress user with the matched id:
		$user = get_user_by( 'id', $query_result );
		return $user;
	}

	/**
	 * Link a user to their Saml identity
	 *
	 * @param int $user_id
	 * @param string $net_id
	 */
	public function linkAccount( $user_id, $net_id ) {
		$condition = "{$net_id}|" . time();
		add_user_meta( $user_id, self::META_KEY, $condition );
	}

	/**
	 * Create user (redirects if there is an error)
	 *
	 * @param string $username
	 * @param string $email
	 *
	 * @throws \Exception
	 *
	 * @return array [ (int) user_id, (string) sanitized username ]
	 */
	public function createUser( $username, $email ) {
		$i = 1;
		$unique_username = $this->sanitizeUser( $username );
		while ( username_exists( $unique_username ) ) {
			$unique_username = $this->sanitizeUser( "{$username}{$i}" );
			++$i;
		}

		// Validate
		if ( ! $this->bypass ) {
			remove_all_filters( 'wpmu_validate_user_signup' );
			$user_result = wpmu_validate_user_signup( $unique_username, $email );
			$username = $user_result['user_name'];
			$email = $user_result['user_email'];
			$errors = $user_result['errors'];
		} else {
			$username = $unique_username;
			$email = sanitize_email( $email );
			$errors = null;
		}

		/** @var \WP_Error $errors */
		if ( ! empty( $errors->errors ) ) {
			$error = '';
			foreach ( $errors->get_error_messages() as $message ) {
				$error .= "{$message} ";
			}
			throw new \Exception( $error );
		}

		// Attempt to generate the user and get the user id
		// we use wp_create_user instead of wp_insert_user so we can handle the error when the user being registered already exists
		$user_id = wp_create_user( $username, wp_generate_password(), $email );

		// Check if the user was actually created:
		if ( is_wp_error( $user_id ) ) {
			// there was an error during registration, redirect and notify the user:
			throw new \Exception( $user_id->get_error_message() );
		}

		remove_user_from_blog( $user_id, 1 );

		return [ $user_id, $username ];
	}

	/**
	 * Multisite has more restrictions on user login character set
	 *
	 * @see https://core.trac.wordpress.org/ticket/17904
	 *
	 * @param string $username
	 *
	 * @return string
	 */
	public function sanitizeUser( $username ) {
		$unique_username = sanitize_user( $username, true );
		$unique_username = strtolower( $unique_username );
		$unique_username = preg_replace( '/[^a-z0-9]/', '', $unique_username );
		return $unique_username;
	}

	/**
	 * Associate user
	 *
	 * @param string $net_id
	 * @param string $email
	 *
	 * @throws \Exception
	 */
	public function associateUser( $net_id, $email ) {

		$user = $this->findExistingUser( $email );
		if ( $user ) {
			// Associate existing users with Saml accounts
			$user_id = $user->ID;
			$username = $user->user_login;
		} else {
			if ( $this->provision === 'create' ) {
				list( $user_id, $username ) = $this->createUser( $net_id, $email );
			} else {
				// Refuse Access
				return;
			}
		}

		// Registration was successful, the user account was created (or associated), proceed to login the user automatically...
		// associate the WordPress user account with the now-authenticated third party account:
		$this->linkAccount( $user_id, $net_id );

		// Attempt to login the new user
		$logged_in = \Pressbooks\Redirect\programmatic_login( $username );
		if ( $logged_in === true ) {
			$this->endLogin( __( 'Registered and logged in!', 'pressbooks-shibboleth-sso' ) );
		}
	}

	/**
	 * Find existing user
	 *
	 * @param string $email
	 *
	 * @return bool|\WP_User
	 */
	public function findExistingUser( $email ) {
		// Plan A
		$user = get_user_by( 'email', $email );
		if ( $user ) {
			return $user;
		}
		// Plan B
		if ( isset( $_SESSION[ self::USER_DATA ] ) ) {
			$attributes = $_SESSION[ self::USER_DATA ];
			if ( isset( $attributes['mail'] ) ) {
				foreach ( $attributes['mail'] as $alt_email ) {
					$user = get_user_by( 'email', $alt_email );
					if ( $user ) {
						return $user;
					}
				}
			}
			// https://wiki.shibboleth.net/confluence/display/SHIB/EduPersonPrincipalName
			// Note: Syntactically, ePPN looks like an email address but is not intended to be a person's published email address or be used as an email address.
			if ( isset( $attributes['eduPersonPrincipalName'] ) ) {
				foreach ( $attributes['eduPersonPrincipalName'] as $alt_email ) {
					$user = get_user_by( 'email', $alt_email );
					if ( $user ) {
						return $user;
					}
				}
			}
		}
		// Could not find user
		return false;
	}

	/**
	 * Default behaviour: User is always redirected to the page they signed in from (network homepage or book homepage).
	 * To accomplish this we track home_url() in $_SESSION
	 * Dev should unset() on success.
	 *
	 * @param bool $overwrite
	 */
	public function trackHomeUrl( $overwrite = false ) {
		if ( empty( $_SESSION[ self::SIGN_IN_PAGE ] ) || $overwrite ) {
			$_SESSION[ self::SIGN_IN_PAGE ] = home_url();
		}
	}

	/**
	 * If not in unit tests, then exit!
	 */
	private function doExit() {
		if ( ! defined( 'WP_TESTS_MULTISITE' ) ) {
			exit;
		}
	}

}
