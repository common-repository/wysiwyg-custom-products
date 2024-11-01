<?php /** @noinspection PropertyInitializationFlawsInspection */

/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 7/10/16
 * Time: 12:48 PM
 */

namespace WCP;

use function defined;
use function in_array;
use function strtoupper;

defined( 'ABSPATH' ) || exit;

/**
 * Class TextPath
 *
 * @package    WCP
 * @subpackage Common
 *
 * @since      1.2.0
 * @updated    1.2.0
 */
class TextPath extends WcpObject {

	/**
	 * @var array
	 */
	protected static $classAttributes = [
		'ClassName'   => __CLASS__,
		'OptionName'  => 'textPath',
		'AliasPrefix' => 'wcp_path',
		'Fields'      => [ 'textPath' => [ 'set' => 'setTextPath', 'get' => 'getTextPath', 'type' => 'textarea' ] ],
	];

	/**
	 * @var array List of all object names
	 */
	protected static $names;

	/**
	 * @var array List of all objects
	 */
	protected static $objects = [];

	/**
	 * @var array List of all line textPaths
	 */
	private static $lineTextPaths = [];

	/**
	 * @var array List of all objects already defined
	 */
	protected static $objectsDefined = [];

	/**
	 * @var array List of all line textPaths already defined
	 */
	private static $lineTextPathsDefined = [];

	/**
	 * @var int
	 */
	private static $defRequiredCount = 0;

	/**
	 * @var string The current textpath
	 */
	private $textPath = '';


	/**
	 * @inheritDoc
	 */
	public static function getI8nName( $field = null ): string {
		// TRANSLATORS: User friendly name of an SVG TextPath
		return __( 'TextPath', 'wysiwyg-custom-products' );
	}
	/**
	 * Gets a horizontal line textpath alias based on the length (width)
	 *
	 * @param $length
	 *
	 * @return string
	 *
	 * @since   1.2.0
	 * @updated 1.2.0
	 */
	public static function getHTextPathAlias( $length ): string {
		return "wcp_h$length";
	}
	/**
	 * Creates or retrieves a horizontal line textpath alias based on the length (width)
	 *
	 * @param $length
	 *
	 * @return string
	 *
	 * @since   1.2.0
	 * @updated 2.0.1
	 */
	public static function addHTextPath( $length ): string {
		$alias = self::getHTextPathAlias( $length );

		if ( ! isset( self::$lineTextPaths[ $alias ] ) ) {
			self::$lineTextPaths[ $alias ] = "M0 0H$length";
			self::$defRequiredCount ++;
		}

		return $alias;
	}

	/**
	 * Builds the entire html string for defining all current paths
	 *
	 * @param bool $addSvgDeclaration
	 *
	 * @return string
	 *
	 * @since   1.2.0
	 * @updated 2.0.1
	 */
	public static function getHtmlPathDefs( $addSvgDeclaration = true ): string {
		if ( 0 === self::$defRequiredCount ) {
			return '';
		}
		$htmlBuild = new Wp_Html_Helper( Html_Helper::BUILD_HTML );

		if ( $addSvgDeclaration ) {
			$htmlBuild->o_svg();
		}
		$htmlBuild->o_tag( 'defs' );

		foreach ( self::$lineTextPaths as $alias => $path ) {
			if ( ! in_array( $alias, self::$lineTextPathsDefined, true ) ) {
				self::$lineTextPathsDefined[] = $alias;
				$htmlBuild->o_tag( 'path', '',
				                   $alias,
				                   [
					                   'd'            => $path,
					                   'stroke-width' => 0,
				                   ],
				                   [],
				                   true );
			}
		}
		$htmlBuild->c_tag( 'defs' );

		if ( $addSvgDeclaration ) {
			$htmlBuild->c_tag( 'svg' );
		}

		self::$defRequiredCount = 0;

		return $htmlBuild->get_html();
	}

}
