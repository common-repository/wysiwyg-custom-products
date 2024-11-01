<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 12/10/2016
 * Time: 9:19 AM
 *
 * @since   1.2.0  Refactored
 * @updated 1.2.6
 */

namespace WCP;

use function defined;
use function in_array;

defined( 'ABSPATH' ) || exit;

/**
 * Class Ajax_Global
 *
 * @package    WCP
 * @subpackage Admin
 *
 * @since      1.2.0
 * @updated    1.2.6
 */
class Ajax_Global extends Ajax {

	/**
	 * Ajax_Global constructor.
	 *
	 * @since   1.2.0
	 * @updated 1.2.0
	 */
	public function __construct() {
		$this->postMethods = [
			'plugin_delete'  => 'plugin_delete',
			'display_ie_msg' => 'display_ie_msg',

		];

		parent::__construct();
	}

	/**
	 * Saves plugin clean delete option
	 *
	 * @since   1.2.0
	 * @updated 1.2.6
	 */
	public function plugin_delete() {
		$this->check_nonce();

		$value = $_POST['value'];
		if ( ! in_array( $value, [ 'no', 'yes' ], true ) ) {
			wp_die();
		}
		update_option( 'settings', $value, 'clean_delete' );
		wp_die();
	}

	/**
	 * Saves display ie msg option
	 *
	 * @since   1.2.6
	 * @updated 1.2.6
	 */
	public function display_ie_msg() {
		$this->check_nonce();

		$value = $_POST['value'];
		if ( ! in_array( $value, [ 'no', 'yes' ], true ) ) {
			wp_die();
		}
		update_option( 'settings', $value, 'display_ie_msg' );
		wp_die();
	}

}

global $ajax;
$ajax = new Ajax_Global();
