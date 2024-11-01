<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 12/10/2016
 * Time: 9:19 AM
 *
 * @since   1.2.0
 * @updated 1.2.0
 */

namespace WCP;

use function defined;

defined( 'ABSPATH' ) || exit;

/**
 * Class Ajax
 *
 * @package    WCP
 * @subpackage Admin
 *
 * @since      1.0.0
 * @updated    1.2.0
 */
class Ajax {

	/**
	 * @var array
	 */
	protected $getMethods = [];

	/**
	 * @var array
	 */
	protected $postMethods = [];

	/**
	 * Ajax constructor.
	 *
	 * @since   1.0.0
	 * @updated 1.1.2
	 */
	public function __construct() {
		foreach ( $this->getMethods as $method => $routine ) {
			add_action( 'wp_ajax_get_' . $method, [ $this, $routine ] );
		}

		foreach ( $this->postMethods as $method => $routine ) {
			add_action( 'wp_ajax_post_' . $method, [ $this, $routine ] );
		}
	}

	/**
	 * Used to check the nonce
	 *
	 * @since   1.0.0
	 * @updated 1.2.0
	 */
	public function check_nonce() {
		$nonce = maybe_get( $_REQUEST, 'wcp-nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wcp-settings' ) ) {
			wp_die();
		}
	}
}

