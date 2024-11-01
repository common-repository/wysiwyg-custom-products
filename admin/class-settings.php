<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 19/08/16
 * Time: 11:20 AM
 *
 * @since      1.0.0
 * @updated    2.0.0
 */

namespace WCP;

use function defined;

defined( 'ABSPATH' ) || exit;

/**
 * Class Settings
 *
 * Abstract Administration page for settings pages
 *
 * @package    WCP
 * @subpackage Admin
 *
 * @since      1.0.0
 * @updated    2.0.0
 */
abstract class Settings {

	//<editor-fold desc="Fields">

	/**
	 * @var Wp_Html_Helper
	 */
	protected $htmlEcho;

	/**
	 * @var Wp_Html_Helper
	 */
	protected $htmlBuild;

	/**
	 * @var Wp_Html_Helper
	 */
	protected $htmlReturn;
	/**
	 * @var array
	 */
	protected $messages = [];

	/**
	 * @var array
	 */
	protected $dependencies;

	/* @var array $metaBoxes
	 *
	 * format [ id => [ 'title' => title,
	 *                  'function' => function,
	 *                 'screen' => screen,
	 *                 'context' => context,
	 *                 'priority' => priority,
	 *                 'args' => callback_args
	 *                ],
	 */
	protected $metaBoxes = [];

	/** @var array $helpTabs
	 *
	 * format [ id => [ 'title' => title, 'content' => line1, line2],
	 *
	 */
	protected $helpTabs = [];
	/**
	 * @var \WP_Screen
	 */
	protected $screen;

	/**
	 * @var string $html string that outputs the ajax-loading.gif
	 */
	protected $throbber;
	//</editor-fold>

	/**
	 * Settings constructor.
	 *
	 *
	 * @since   2.0.0
	 * @updated 2.0.0
	 *
	 */
	public function __construct() {
		// Setup helpers
		$shortcuts['div']['r']      = 'row';
		$shortcuts['input']['s']    = 'small-text';
		$shortcuts['input']['r']    = 'regular-text';
		$shortcuts['input']['m']    = 'medium-text';
		$shortcuts['checkbox']['c'] = 'checkbox';

		$this->htmlEcho   = new Wp_Html_Helper( Html_Helper::ECHO_ONLY, false, $shortcuts );
		$this->htmlBuild  = new Wp_Html_Helper( Html_Helper::BUILD_HTML, false, $shortcuts );
		$this->htmlReturn = new Wp_Html_Helper( Html_Helper::RETURN_ONLY, false, $shortcuts );
	}


	/**
	 * Prepares page for display
	 *
	 * @since   1.1.0
	 * @updated 1.2.0
	 */
	public function load_page() {
		// Can the user do this?
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->screen = get_current_screen();
		// Make sure it's us
		if ( $this->screen->id !== Admin::$screenId ) {
			return;
		}

		$this->throbber = $this->htmlReturn->img( Wcp_Plugin::$assetsUrl . 'img/ajax-loader.gif', null, null, '', 'wcp-hidden wcp-throbber' );

		$this->add_help();
		$this->add_options();
		$this->add_meta_boxes();
	}

	/**
	 * Displays settings page, invoking sub-class display_tab for details
	 *
	 * @since    1.1.0
	 * @updated  1.2.0
	 */
	public function display_page() {
		global $is_IE;
		global $is_edge;

		$this->initialise();

		$html = $this->htmlEcho;

		$html->o_div( 'wrap' );
		// TRANSLATORS: Plugin settings page title - added to the translated plugin title
		$html->tag( 'h1', Wcp_Plugin::$localePluginTitle . ' ' . __( 'Settings', 'wysiwyg-custom-products' ), 'wp-heading-inline' );

		echo $this->throbber;

		if ( $is_edge || $is_IE ) {
			$html->tag( 'h2',
				// TRANSLATORS: Message about suitable browsers for doing layout.
				        __( 'Due to the lack of proper SVG support, layouts can not be set using Internet Explorer or Edge. Chrome or Firefox are recommended.',
				            'wysiwyg-custom-products' ),
			            'wp-heading-inline' );
		}

		$page = Wcp_Plugin::$settingsPage;

		$html->o_tag( 'h2', 'nav-tab-wrapper' );
		foreach ( Admin::$tabs as $tab => $name ) {
			$html->a( $name, "?page=$page&tab=$tab",
			          [ 'nav-tab', $tab === Admin::$tab ? ' nav-tab-active' : '' ] );
		}
		$html->c_tag( 'h2' );

		$html->o_form( 'wcp_form' );

		/* Used to save closed meta boxes and their order */
		wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
		wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
		// My nonce
		$html->input( 'hidden', 'wcp_nonce', '', wp_create_nonce( 'wcp-settings' ) );

		$html->o_div( '', 'poststuff' );
		/** @noinspection NullPointerExceptionInspection */
		$html->o_div( 'metabox-holder columns-' . get_current_screen()->get_columns(), 'post-body' );

		//do_action( 'before_settings' );
		$html->o_div( '', 'post-body-content' );
		$this->display_tab();
		$html->c_div();

		$html->o_div( 'postbox-container', 'postbox-container-1' );
		do_meta_boxes( '', 'side', null );
		$html->c_div();

		$html->o_div( 'postbox-container', 'postbox-container-2' );
		do_meta_boxes( '', 'normal', null );
		do_meta_boxes( '', 'advanced', null );
		$html->c_div();

		//do_action( 'after_settings' );

		$html->c_div( 2 ); // 'post-body poststuff'
		$html->c_tag( 'form' );
		$html->c_div(); // wrap
	}

	/**
	 * Define our metaboxes
	 *
	 * @since    1.1.0
	 * @updated  1.2.0
	 */
	public function add_meta_boxes() {
		foreach ( $this->metaBoxes as $id => $info ) {
			add_meta_box( $id,
			              $info['title'],
			              [ $this, $info['function'] ],
			              maybe_get( $info, 'screen', null ),
			              maybe_get( $info, 'context', 'advanced' ),
			              maybe_get( $info, 'priority', 'default' ),
			              maybe_get( $info, 'args', 'null' ) );
		}
	}

	/**
	 * Add help information
	 *
	 * @since    1.1.0
	 * @updated  2.0.0
	 */
	public function add_help() {
		$this->htmlBuild->pushDoEscape( false );
		foreach ( $this->helpTabs as $id => $info ) {
			foreach ( $info['content'] as $line ) {
				$this->htmlBuild->tag( 'p', $line );
			}
			$this->screen->add_help_tab( [
				                             'id'      => $id,
				                             'title'   => $info['title'],
				                             'content' => $this->htmlBuild->get_html(),
			                             ] );
		}
		$this->htmlBuild->popDoEscape();
	}

	/**
	 * Sets everything up for use
	 *
	 * @since   1.1.0
	 * @updated 1.2.0
	 */
	public function initialise() {
		// Load scripts
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'common' );
		wp_enqueue_script( 'wp-lists' );
		wp_enqueue_script( 'postbox' );

		// Load styles
		wp_enqueue_style( 'woocommerce_admin_styles' ); // Leverage woocommerce formatting
	}

	/**
	 * Finishes setting everything up for use
	 *
	 * @param  string $scriptName
	 * @param  string $ver
	 * @param bool    $doMessages
	 *
	 * @since   1.2.0
	 * @updated 1.2.4
	 */
	public function initialiseFinish( $scriptName, $ver, $doMessages = true ) {
		$scriptName = 'settings-' . $scriptName;
		register_admin_script( $scriptName, $this->dependencies, $ver );

		if ( $doMessages ) {
			$this->messages['modified_leave'] =
				// TRANSLATORS: warning when leaving the layout edit page with unsaved changes
				__( 'Changes will be lost. Are you sure you want to leave?',
				    'wysiwyg-custom-products' );
			// TRANSLATORS: confirmation that the user wants to delete the selected layout
			$this->messages['confirm_delete'] = __( 'Are you sure you want to delete?', 'wysiwyg-custom-products' );
			// TRANSLATORS: confirmation that the user wants to cancel any changes
			$this->messages['confirm_cancel'] = __( 'Are you sure you wish to lose any changes?', 'wysiwyg-custom-products' );

			// Force free prefix to avoid too much mucking around in admin-settings.js for premium version
			localize_script( $scriptName, 'messages', $this->messages, 'wcp_', 'wcp_' );
		}
	}

	/**
	 * Add screen options
	 *
	 * @since   1.1.0
	 * @updated 1.2.0
	 */
	public function add_options() {

	}

	/**
	 * Method to be implemented in each sub class that displays the required screen for that tab.
	 *
	 * @since   1.2.0
	 * @updated 1.2.0
	 */
	abstract public function display_tab();
}
