<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 7/10/16
 * Time: 12:31 PM
 *
 * @since   1.0.0
 * @updated 2.0.1
 */

namespace WCP;

use function define;
use function defined;
use function in_array;
use const WCP_COMMON_DIR;
use const WP_UNINSTALL_PLUGIN;

defined( 'ABSPATH' ) || exit;

/**
 * Class Admin
 *
 * Controls invocation of administration functions
 *
 * @package    WCP
 * @subpackage Admin
 *
 * @since      1.0.0
 * @updated    2.0.1
 */
class Admin {

	/**
	 * @var string
	 */
	public static $screenId;

	/**
	 * @var array
	 */
	public static $tabs;
	/**
	 * @var string
	 */
	public static $tab;

	/**
	 * @var boolean
	 */
	private static $ie;

	/**
	 * Admin constructor.
	 *
	 * @since   1.0.0
	 * @updated 2.0.1
	 */
	public function __construct() {
		global $is_IE;
		global $is_edge;

		$this_path = __DIR__ . DS;



		// Load actions suitable for the request
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) { // Ajax, non visible handling only
			if ( isset( $_REQUEST['wcp-nonce'] ) ) {
				// Ajaxing for us
				$tab = self::setTabs();
				if ( ! $tab ) {
					return;
				}

				require_once "{$this_path}class-ajax.php"; // Load base class
				require_once "{$this_path}class-ajax-$tab.php"; // Load settings tab ajax handlers
			}
		} else {
			add_action( 'admin_menu', [ $this, 'admin_menu' ] );

			require_once "{$this_path}class-plugin.php";

			if ( Wcp_Plugin::$settingsPage === maybe_get( $_REQUEST, 'page' ) ) {
				$this::$ie = $is_edge || $is_IE;
				// Loading one of our settings pages
				$tab = self::setTabs( 'global' );

				require_once "{$this_path}class-settings.php"; // Load base class
				require_once "{$this_path}class-$tab.php";  // Load settings tab content
			} else {
				$action = $_REQUEST['action'] ?? '';
				if ( in_array( $action, [ 'edit', 'editpost' ] ) ) {
					// Maybe editing a product, offer our services
					require_once "{$this_path}class-products.php";
				}
			}
		}
	}

	/**
	 * WP Hooks
	 *
	 * @since   1.1.0
	 * @updated 1.1.0
	 */
	public function admin_menu() {
		global $wcpSettings;

		// Add this page to the menu
		$screenId       = add_options_page( Wcp_Plugin::$localeSettingsTab,
		                                    Wcp_Plugin::$localeSettingsTab,
		                                    'manage_options',
		                                    Wcp_Plugin::$settingsPage );
		self::$screenId = $screenId;

		// Deal with admin header stuff
		add_action( "load-{$screenId}", [ $wcpSettings, 'load_page' ] );

		// Display our page
		add_action( $screenId, [ $wcpSettings, 'display_page' ] );
	}

	/**
	 * Sets up the tabs for the plugin and selects the appropriate tab if applicable
	 *
	 * @param bool|string $default
	 *
	 * @return bool|mixed
	 *
	 * @since   1.2.0
	 * @updated 1.2.6
	 */
	private static function setTabs( $default = false ) {
		self::$tabs = [
			// TRANSLATORS: Templates tab title
			'layout'   => __( 'Layouts', 'wysiwyg-custom-products' ),

			// TRANSLATORS: Settings tab title
			'global'   => __( 'Settings', 'wysiwyg-custom-products' ),
		];

		if ( self::$ie ) {
			unset( self::$tabs['layout'] );
		}

		$tab = maybe_get( $_REQUEST, 'tab', $default );
		if ( false === $tab ) {
			return false;
		}

		$tab = isset ( self::$tabs[ $tab ] ) ? $tab : $default;

		self::$tab = $tab;

		return $tab;
	}
}

global $admin;
$admin = new Admin();
