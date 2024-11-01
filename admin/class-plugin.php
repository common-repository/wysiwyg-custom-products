<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 7/10/16
 * Time: 3:53 PM
 *
 * @since   1.0.0
 * @updated 1.2.4
 */

namespace WCP;

use function defined;
use function function_exists;
use function is_callable;

defined( 'ABSPATH' ) || exit;

/**
 * Class Plugin_Maintenance
 *
 * @package    WCP
 * @subpackage Admin
 *
 * @since      1.0.0
 * @updated    1.2.4
 */
class Plugin_Maintenance {

	/**
	 * Array containing the plugin action links
	 *
	 * @var array
	 */
	static protected $actionLinks = [];

	/**
	 * Array containing the plugin meta links
	 *
	 * @var array
	 */
	static protected $metaLinks = [];

	/**
	 * Plugin_Maintenance constructor.
	 *
	 * @since   1.0.0
	 * @updated 1.0.7
	 */
	public function __construct() {
		$this->init_links();
		add_action( 'plugins_loaded', [ $this, 'maybe_self_deactivate' ] );
		add_action( 'plugins_loaded', [ $this, 'maybe_update' ], 1 );
		add_filter( 'plugin_action_links', [ $this, 'action_links' ], 10, 2 );
		add_filter( 'plugin_row_meta', [ $this, 'plugin_row_meta' ], 10, 2 );

	}

	/**
	 * Show row meta on the plugin screen
	 *
	 * @param string[] $links
	 * @param string   $file
	 *
	 * @return string[]
	 *
	 * @since   1.0.0
	 * @updated 1.0.7
	 */
	public function plugin_row_meta( $links, $file ): array {
		if ( Wcp_Plugin::$basename === $file ) {
			foreach ( $this::$metaLinks as $label => $url ) {
				$links[ $label ] = '<a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $label ) . '</a>';
			}
		}

		return $links;
	}

	/**
	 * Show plugin actions on the plugin screen
	 *
	 * @param string[] $links
	 * @param string   $file
	 *
	 * @return string[]
	 *
	 * @since 1.0.7
	 */
	public function action_links( $links, $file ): array {
		if ( Wcp_Plugin::$basename === $file ) {
			foreach ( $this::$actionLinks as $label => $url ) {
				array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>' );
			}
		}

		return $links;
	}

	/**
	 * Checks for required plugins and causes self deactivation if not present
	 *
	 * @since   1.0.0
	 * @updated 1.0.7
	 */
	public function maybe_self_deactivate() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			if ( ! function_exists( 'deactivate_plugins' ) ) {
				// Plugin.php sometimes not loaded, so we'll do it
				$adminDir = get_admin_path();
				require_once $adminDir . 'includes' . DS . 'plugin.php';
			}

			deactivate_plugins( Wcp_Plugin::$basename );
			add_action( 'admin_notices', [ $this, 'self_deactivate_notice' ] );
		}

	}

	/**
	 * Let user know why we shut ourselves down
	 *
	 * @since   1.0.0
	 * @updated 1.2.4
	 */
	public function self_deactivate_notice() {
		$required = '';
		if ( ! class_exists( 'WooCommerce' ) ) {
			$required = 'WooCommerce';
		}

		$error = '<strong>' . Wcp_Plugin::$localePluginTitle . '</strong> ';
		// TRANSLATORS: Error message when plugin dependencies are not available and plugin deactivates itself
		$error .= sprintf( __( 'has deactivated itself because it needs %s to be active.',
		                       'wysiwyg-custom-products' ), $required );
		echo '<div class="error"><p>' . $error . '</p></div>';
	}

	/**
	 * Check if the database version is up to date.
	 * If not, run any incremental updates one by one.
	 *
	 * For example, if the current DB version is 3, and the target DB version is 6,
	 * this function will execute update routines if they exist:
	 *  - db_update_routine_V4()
	 *  - db_update_routine_V5()
	 *  - db_update_routine_V6()
	 *
	 * @since   1.0.0
	 * @updated 1.0.1
	 */
	public function maybe_update() {
		// this is the current database schema version number, default to 0 if none found
		$current_db_ver = get_option( 'db_ver', 0 );

		// Make sure we got a 0+ integer
		if ( ! int_range_check( (int) $current_db_ver, 0 ) ) {
			$current_db_ver = 0;
		}

		// bail if this plugin data doesn't need updating
		if ( $current_db_ver >= Wcp_Plugin::DB_VER ) {
			return;
		}

		if ( 1 === (int) $current_db_ver ) {
			$textPathUpdate = get_option( 'text_path_update', false );
			if ( $textPathUpdate ) { // Previously done DB version 2 update
				$current_db_ver ++;
				update_option( 'db_ver', $current_db_ver );
				delete_option( 'text_path_update' );
			}
		}
		set_time_limit( 0 );

		// run update routines one by one until the current version number
		// reaches the target version number
		while ( $current_db_ver < Wcp_Plugin::DB_VER ) {
			// increment the current db_ver by one
			$current_db_ver ++;

			// each db version will require a separate update function
			// for example, for db_ver 3, the function name should be db_update_routine_V3
			$function = 'db_update_routine_V' . $current_db_ver;
			if ( is_callable( [ $this, $function ] ) ) {
				$this->{$function} ();
			}
			// update the option in the database, so that this process can always
			// pick up where it left off
			update_option( 'db_ver', $current_db_ver );
		}
	}

	/**
	 * Initialize plugin links
	 *
	 * @return void
	 *
	 * @since   1.0.0
	 * @updated 1.0.7
	 */
	protected function init_links() {
		$this::$metaLinks   = [
		];
		$this::$actionLinks = [
			__( 'Settings' ) => admin_url() . 'options-general.php?page=' . Wcp_Plugin::$settingsPage,
		];
	}
	/**
	 * Modify formats to put X at start of line
	 *
	 * @since   2.0.0
	 * @updated 2.0.0
	 */
	private function db_update_routine_V2() {
		$layout = new Layout();
		$layouts = $layout->getLayouts();

		foreach ($layouts as $layoutName) {
			try {
				$layout->load_by_name( $layoutName );
				$layout->updateXPosition();
				$layout->save();
			} catch ( WCPException $e ) {
			}
		}
	}
}

global $pluginMaintenance;
$pluginMaintenance = new Plugin_Maintenance();

