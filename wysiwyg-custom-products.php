<?php
/**
 * Plugin Name: Wysiwyg Custom Products 
 * Plugin URI: https://tazziedave.com/wp-plugins/wysiwyg-custom-products
 * Description: Enables a live WYSIWYG preview of custom products where text is edited in text area or text field in woocommerce.
 * Version: 2.1.0
 * Author: Tazziedave
 * Author URI: https://tazziedave.com
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wysiwyg-custom-products
 * Domain Path: /languages
 * WC requires at least: 3.0.0
 * WC tested up to: 4.5.2
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace WCP;


use Exception;
use function add_action;
use function count;
use function define;
use function defined;
use function function_exists;
use function in_array;
use function is_array;
use function plugin_basename;
use function plugin_dir_url;
use const DIRECTORY_SEPARATOR;
use const WCP_COMMON_DIR;

//<editor-fold desc="Quick exits">
defined( 'ABSPATH' ) || exit;

if ( defined( 'DOING_CRON' ) ) {
	return;
}

if ( defined( 'DOING_AJAX' ) && DOING_AJAX && ! isset( $_REQUEST['wcp-nonce'] ) ) { // Ajaxing and not for us
	$action = $_REQUEST['action'] ?? '';  // should I ignore?
	if ( in_array( $action, [ 'heartbeat', 'wcff_ajax', 'wp_1_wc_privacy_cleanup' ] ) ) {
		return;
	}
}

if ( isset( $wysiwygCP ) ) {
	return; // We've already been created
}
//</editor-fold>

//<editor-fold desc="Defines">
/**
 * Shorthand constant
 */
if ( ! defined( 'DS' ) ) {
	/**
	 *
	 */
	define( 'DS', DIRECTORY_SEPARATOR );
}

if ( ! defined( 'WCP_COMMON_DIR' ) ) {
	/**
	 *
	 */
	define( 'WCP_COMMON_DIR', __DIR__ . DS . 'common' . DS ); // path with trailing slash
}
//</editor-fold>


//<editor-fold desc="Requires">
require_once WCP_COMMON_DIR . 'wp-helpers.php';
require_once WCP_COMMON_DIR . 'utilities.php';
// require_once WCP_COMMON_DIR . 'stubs.php';  No longer necessary as I've made PHP 7.0.0 the minimum
require_once WCP_COMMON_DIR . 'class-wp-html-helper.php';
require_once WCP_COMMON_DIR . 'class-wcp-product.php';
require_once WCP_COMMON_DIR . 'class-layout.php';
require_once WCP_COMMON_DIR . 'class-wcp-object.php';
require_once WCP_COMMON_DIR . 'class-textpath.php';
require_once WCP_COMMON_DIR . 'exception-classes.php';
//</editor-fold>

/**
 * Class Wcp_Plugin
 *
 * @package WCP
 *
 * @since   1.0.0
 * @updated 2.1.0
 */
class Wcp_Plugin {

	//<editor-fold desc="Fields">

	/**
	 * Basic plugin information
	 */
	const PLUGIN_TITLE = 'Wysiwyg Custom Products ';
	/**
	 *
	 */
	const PLUGIN_NAME = 'wysiwyg-custom-products';
	/**
	 *  Plug in version
	 */
	const VER = '2.1.0';
	/**
	 * Database version. Used in class-plugin to run updates as necessary
	 */
	const DB_VER = 2;

	/**
	 * prefix for all option and metadata stored.  Mainly used in wp-helpers.php
	 */
	/**
	 *  Current prefix for the wp_options table
	 */
	const OPTION_PREFIX = 'wcp_';

	/**
	 *  Current prefix for any post meta data
	 */
	const META_PREFIX = '_wcp_';

	/* Prefixes used when swapping versions */
	/**
	 *  Old prefix for the wp_options table
	 */
	const OLD_VERSION_OPTION_PREFIX = 'wcpp_';

	/**
	 *  Old prefix for any post meta data
	 */
	const OLD_VERSION_META_PREFIX = '_wcpp_';


	/**
	 *  Internationalisation data
	 */
	const TRANSLATION_DOMAIN = 'wysiwyg-custom-products';
	/**
	 *
	 */
	const TRANSLATION_SUB_DIRECTORY = 'languages';
	/**
	 *
	 */
	const DEFAULT_LOCALE = '';

	/**
	 * Specifies how many fields can be auto populated per product
	 */
	const MAX_OVERRIDE_FIELDS = 3;

	/**
	 * @var string Localised name of the plugin
	 */
	static public $localePluginTitle;

	/**
	 * @var string Localised settings tab title
	 */
	static public $localeSettingsTab;

	/**
	 * @var string Will point this directory
	 */
	static public $pluginDirectory;
	/**
	 * @var string Provides plugin_basename() information
	 */
	static public $basename;

	/**
	 * @var string Url for the plugin page
	 */
	static public $pluginUrl;
	/**
	 * @var string The name of our settings page
	 */
	static public $settingsPage;
	/**
	 * @var string Url for the assets
	 */
	static public $assetsUrl;
	/**
	 * @var string Url for the stylesheet assets
	 */
	static public $cssUrl;
	/**
	 * @var string Url for the javascript assets
	 */
	static public $jsUrl;
	/**
	 * @var string Url for any user assets
	 */
	static public $userUrl;

	/**
	 * @var string global default for whether text lines should be sized together or individually
	 */
	static private $balanceText;
	//</editor-fold>


	/**
	 * Wcp_Plugin constructor.
	 *
	 * @param null $locale
	 *
	 * @since        1.0.0
	 * @updated      1.2.0
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function __construct( $locale = null ) {
		self::$pluginDirectory = plugin_basename( __DIR__ );
		self::$basename        = plugin_basename( __FILE__ );
		self::$pluginUrl       = plugin_dir_url( __FILE__ );
		self::$assetsUrl       = self::$pluginUrl . 'assets/';
		self::$cssUrl          = self::$assetsUrl . 'stylesheets/';
		self::$jsUrl           = self::$assetsUrl . 'js/';
		self::$userUrl         = self::$pluginUrl . 'user/';
		self::$settingsPage    = self::PLUGIN_NAME . '-settings';

		load_plugin_textdomain( self::TRANSLATION_DOMAIN,
		                        false,
		                        trailingslashit( self::$pluginDirectory . DS . self::TRANSLATION_SUB_DIRECTORY ) );
		// Add callback for WP to get our locale
		add_filter( 'plugin_locale', [ $this, 'get_plugin_locale_callback' ], $priority = 10, $accepted_args = 2 );
		add_filter( 'woocommerce_get_image_size_shop_single', [ $this, 'woocommerce_get_image_size' ], $priority =
			10, $accepted_args = 1 );
		add_filter( 'woocommerce_get_image_size_shop_catalog', [ $this, 'woocommerce_get_image_size' ], $priority =
			10, $accepted_args = 1 );
		add_filter( 'woocommerce_get_image_size_single', [ $this, 'woocommerce_get_image_size' ], $priority =
			10, $accepted_args = 1 );
		add_filter( 'woocommerce_get_image_size_thumbnail', [ $this, 'woocommerce_get_image_size' ], $priority =
			10, $accepted_args = 1 );

		add_action( 'wp_head', [ $this, 'user_style_css' ] );
		add_action( 'admin_head', [ $this, 'user_style_css' ] );
		// TRANSLATORS: Name of plugin - free version
		self::$localePluginTitle = __( 'Wysiwyg Custom Products', 'wysiwyg-custom-products' );

		// TRANSLATORS: Tab on the settings menu
		self::$localeSettingsTab = __( 'Wysiwyg Customize', 'wysiwyg-custom-products' );

	}

	/**
	 * Returns array of option names used by plugin
	 *
	 * @return array
	 *
	 * @since   1.0.5
	 * @updated 1.2.6
	 */
	public static function get_reserved_option_names(): array {
		// Reserved option names - also used in admin-settings.js initialise
		return [
			'settings',
			'ver',
			'db_ver',
			'layouts',
			'textPaths',
			'FilterEffects',
		];
	}

	/**
	 * Returns array of option names used by plugin and any layouts [name, name, ...], etc
	 *
	 * @param string $prefix Optional
	 *
	 * @return array
	 *
	 * @since   1.0.5
	 */
	public static function get_option_names( $prefix = Wcp_Plugin::OPTION_PREFIX ): array {
		$result = self::get_reserved_option_names();

		// Now add layout options
		try {
			$layouts = Layout::getLayouts( $prefix );
			foreach ( $layouts as $layout ) {
				$result[] = $layout;
			}
		} catch ( Exception $e ) {
			// Ignore errors
		}

		return $result;
	}

	/**
	 * Returns associative array of metadata keys used by plugin meta_type => [meta_key, meta_key], etc
	 *
	 * @return array
	 *
	 * @since   1.0.5
	 * @updated 1.0.7
	 */
	public static function get_metadata_names(): array {
		$result         = [];
		$result['post'] = [];

		// NB attribute values meta data on for products will not get cleared
		// These field names come from class-products.php
		$result['post'][] = 'layout';
		$result['post'][] = 'catalog_text';
		$result['post'][] = 'specific_lines';
		$result['post'][] = 'product_text';
		$result['post'][] = 'background_image';
		for ( $i = 1; $i <= self::MAX_OVERRIDE_FIELDS; $i ++ ) {
			$result['post'][] = 'field_label_' . $i;
			$result['post'][] = 'field_label_override_' . $i;
			$result['post'][] = 'field_values_' . $i;
		}

		return $result;
	}
	/**
	 * Returns default balance text string.
	 *
	 * @return string
	 *
	 * @since   1.2.7
	 * @updated 1.2.7
	 */
	public static function getBalanceText(): string {
		if (empty(self::$balanceText)) {
			self::$balanceText = get_option( 'settings', 'yes', 'balance' );
		}
		return self::$balanceText;
	}

	/**
	 * Routine called if on any admin page
	 *
	 * @since   1.0.0
	 * @updated 1.0.7
	 */
	public function admin() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			// Plugin.php sometimes not loaded, so we'll do it
			$adminDir = get_admin_path();
			require_once $adminDir . 'includes' . DS . 'plugin.php';
		}

		if ( is_plugin_active( self::$basename ) ) {
			require_once __DIR__ . DS . 'admin' . DS . 'class-admin.php';
		} else {
			register_activation_hook( __FILE__, [ $this, 'activate' ] );
		}
	}

	/**
	 * Called when plugin is activated
	 *
	 * @since   1.0.0
	 * @updated 1.2.4
	 */
	public function activate() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$required = '';

		if ( PHP_VERSION_ID < 70000 ) {
			// TRANSLATORS: Error message for insufficient PHP version
			$required = __( 'PHP Version 7.0.0 or above', 'wysiwyg-custom-products' );
		} else if ( ! class_exists( 'WooCommerce' ) ) {
			$required = 'WooCommerce to be active';
		}

		if ( '' !== $required ) {
			// TRANSLATORS: Error message when plugin dependencies are not available %s is a list of requirements
			$errorMessage = sprintf( __( 'Plugin cannot be activated because it needs %s.',
			                             'wysiwyg-custom-products' ), $required );
			die( self::$localePluginTitle . ' ' . $errorMessage );
		}

		if ( false !== get_option( 'ver', false, null, self::OLD_VERSION_OPTION_PREFIX ) ) {
			require_once __DIR__ . DS . 'admin' . DS . 'version-change.php';

			return;
		}

		try {
			require_once WCP_COMMON_DIR . 'class-layout.php';

			$layout = new Layout();
			$layout->save_defaults();
			$settings = get_option( 'settings', [] );
			if ( count( $settings ) === 0 ) {
				$settings['CurrentLayout'] = $layout->getName();
				add_option( 'settings', $settings, false );
			}

			update_option( 'ver', self::VER );
			add_option( 'db_ver', self::DB_VER );
		} catch ( WCPException $e ) {
			// TRANSLATORS: Error message when plugin cannot load layouts
			$errorMessage = __( 'Plugin cannot be activated because it failed to load the layouts with the following error:',
			                    'wysiwyg-custom-products' );
			die( self::$localePluginTitle . ' ' . $errorMessage . ' ' . $e->getMessage() );
		}

	}

	/**
	 * Called when not in admin pages
	 *
	 * @since   1.0.0
	 */
	public function frontend() {
		require_once __DIR__ . DS . 'frontend' . DS . 'class-frontend.php';
	}

	/**
	 * Called when WP is loading translations and is looking for the locale.
	 * If it's our domain and our locale is set, override it, otherwise just passed back.
	 *
	 * @param $locale
	 * @param $domain
	 *
	 * @return string
	 *
	 * @since   1.0.0
	 */
	public function get_plugin_locale_callback( $locale, $domain ): string {
		if ( null !== self::TRANSLATION_DOMAIN && $domain === self::TRANSLATION_DOMAIN && ( '' !== self::DEFAULT_LOCALE ) ) {
			$locale = self::DEFAULT_LOCALE;
		}

		return $locale;
	}

	/**
	 * Called when WooCommerce is processing wc_get_image_size
	 * Added to make height value sensible again after 3.3.0 update.
	 *
	 * @param array $size
	 *
	 * @return array
	 *
	 * @since   1.1.3
	 * @updated 1.1.5
	 */
	public function woocommerce_get_image_size( array $size ): array {

		if ( is_array( $size ) && ( 9999999999 === $size['height'] ) ) {
			$size['height'] = $size['width'];
		}

		return $size;
	}

	/**
	 * Called when WordPress is processing wp_head
	 * Forces early load of user fonts
	 *
	 * @return void
	 *
	 * @since   1.1.10
	 */
	public function user_style_css() {
		$handle = self::OPTION_PREFIX . 'UserStyle';
		$href   = self::$userUrl . 'fonts.css' . '?ver=' . uniqid( '1.1.', false );
		echo "<link rel='stylesheet' id='$handle-css' href='$href' type='text/css' media='all' />\n";
	}
}


$wysiwygCP = new Wcp_Plugin();

if ( ! defined( 'WP_UNINSTALL_PLUGIN ' ) ) { // Check to see if being instantiated for uninstall purposes
	if ( is_admin() ) {
		$wysiwygCP->admin();
	} else {
		$wysiwygCP->frontend();
	}
}

