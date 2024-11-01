<?php
/** @noinspection PhpMultipleClassesDeclarationsInOneFile */

namespace WCP;

use Exception;
use function defined;

defined( 'ABSPATH' ) || exit;

/**
 * Class WCP\WCPException
 *
 * @package    WCP
 *
 * @since      1.0.0
 * @updated    1.0.0
 */
class WCPException extends Exception {

}

/** * Class WCP\ObjectException
 *
 * @package    WCP
 * @subpackage Common
 *
 * @since      2.0.0
 * @updated    2.0.1
 */
class ObjectException extends WCPException {

}

/**
 * Class WCP\LayoutException
 *
 * @package    WCP
 *
 * @since      1.0.0
 * @updated    2.0.1
 */
class LayoutException extends WCPException {

}

/** * Class WCP\TextPathException
 *
 * @package    WCP
 * @subpackage Common
 *
 * @since      1.2.0
 * @updated    2.0.1
 */
class TextPathException extends ObjectException {

}

/** * Class WCP\TextPathException
 *
 * @package    WCP
 * @subpackage Common
 *
 * @since      1.2.0
 * @updated    2.0.1
 */
class TextPathInvalidException extends WCPException {

}

