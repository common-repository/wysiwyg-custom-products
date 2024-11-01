<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 7/10/16
 * Time: 12:48 PM
 *
 * @since      1.0.0
 * @updated    2.0.6
 */

namespace WCP;

use Exception;
use function array_walk;
use function count;
use function defined;
use function get_the_title;
use function is_array;
use function is_string;
use function strlen;
use const PHP_INT_MAX;
use const PHP_INT_MIN;

defined( 'ABSPATH' ) || exit;

/**
 * Class Layout
 *
 * @package    WCP
 * @subpackage Common
 *
 * @since      1.0.0
 * @updated    2.0.6
 */
class Layout {

	//<editor-fold desc="Fields">
	/**
	 * Maximum number of text lines for a layout
	 */
	const MAX_LINES = 10;

	// Sanity checks - added 1.0.1
	/**
	 * Minimum width, height of any image specified for a layout
	 */
	const MIN_IMAGE_SIZE = 75;

	/**
	 * Maximum width, height of any image specified for a layout
	 */
	const MAX_IMAGE_SIZE = 2048;

	/**
	 * Maximum width, height of any image specified for a layout
	 */
	const DEFAULT_IMAGE_SIZE = 600;

	/**
	 * Minimum font size
	 */
	const MIN_FONT_SIZE = 6;
	/**
	 * @var int width of output image determined by size string
	 */
	public $width;
	/**
	 * @var int height of output image determined by size string
	 */
	public $height;
	/**
	 * @var int Maximum number of lines that current layout uses
	 */
	public $maxLines;
	/**
	 * @var int Current number of lines being formatted
	 */
	public $currentLines;
	/**
	 * @var array List of line formats
	 */
	public $formats = [];
	/**
	 * @var int Background image attachment Id. Only used on frontend. Set as part of product maintenance
	 */
	public $background;
	/**
	 * @var int Maintenance image attachment Id. Overridden by frontend - basic product image.
	 */
	public $image;
	/**
	 * @var int Foreground image attachment Id. Used at all stages. Set as part of layout maintenance
	 */
	public $overlay;
	/**
	 * @var int Font fill color when working on layout
	 */
	public $inkColor;
	/**
	 * @var int Sizing box color when working on layout
	 */
	public $activeMouseColor;
	/**
	 * @var int nonSizing box color when working on layout
	 */
	public $inactiveMouseColor;

	/**
	 * @var array Starting layout for new installs
	 */
	protected static $defaultLayout =
		[
			'template' => [
				'SetupImage'         => 0,
				'OverlayImage'       => 0,
				'SetupWidth'         => self::DEFAULT_IMAGE_SIZE,
				'SetupHeight'        => self::DEFAULT_IMAGE_SIZE,
				'MaxLines'           => 1,
				'CurrentLines'       => 1,
				'MultilineReformat'  => '',
				'NumberOfLines'      => '',
				'SinglelineReformat' => '',
				'IeMessage'          => '',
				'EffectUUID'         => '',
				'InkColor'           => 0x000000,  // Black
				'ActiveMouseColor'   => 0x00FFFF,  // Aqua
				'InactiveMouseColor' => 0x800080,  // Purple
				'Formats'            => [
					'Lines1' => [
						[
							'Y'            => self::DEFAULT_IMAGE_SIZE / 2,
							'X'            => self::DEFAULT_IMAGE_SIZE / 2,
							'Width'        => self::DEFAULT_IMAGE_SIZE / 2,
							'Align'        => 'C',
							'MinFont'      => 45,
							'MaxFont'      => 60,
							'Attributes'   => '',
							'Css'          => '',
							'TextPathUUID' => '',
							'EffectUUID'   => '',
							'Group'        => 1,
						],
					],
				],
			],
		];

	/**
	 * @var string Localised exception message.
	 */
	protected static $exceptionMsg;

	/**
	 * @var array List of all layout names
	 */
	private static $layouts = [];
	/**
	 * @var string Current layout name
	 */
	private $name;
	/**
	 * @var string Message used if a text is too long when user is editing a multi-line product
	 */
	private $multiLineReformatMsg;
	/**
	 * @var string Message used if too many lines (>maxLines) are used when use is editing a multi-line product
	 */
	private $numberOfLinesMsg;
	/**
	 * @var string Message used if a text is too long when user is editing a product that has one or more single line
	 *      fields
	 */
	private $singleLineReformatMsg;
	/**
	 * @var string Message used at the frontend if ie is detected
	 */
	private $ieMsg;
	/**
	 * @var string Layout wide SVG Effect
	 */
	private $effectUUID;
	/**
	 * @var int $setupWidth of layout, Used for scaling
	 */
	public $setupWidth;
	/**
	 * @var int $setupHeight of layout, Used for scaling
	 */
	public $setupHeight;
	/**
	 * @var float X scaling factor
	 */
	private $scaleX;
	/**
	 * @var float Y scaling factor
	 */
	private $scaleY;
	//</editor-fold>

	/**
	 * Layout constructor.
	 *
	 * @param string $layoutName Optional, name of layout to load. Just layout list is loaded otherwise.
	 *
	 * @throws LayoutException  If layout is invalid
	 * @throws \WCP\ObjectException
	 *
	 * @since   1.0.0
	 * @updated 1.1.9
	 */
	public function __construct( $layoutName = '' ) {
		// TRANSLATORS: Exception error message thrown when the layout is invalid
		self::$exceptionMsg = esc_html__( 'Invalid Wysiwyg Custom Products format supplied. Error: ',
		                                  'wysiwyg-custom-products' );

		if ( ! empty( $layoutName ) ) {
			if ( '__default__' === $layoutName ) {
				$this->load_layout( self::$defaultLayout, 'Template' );
			} else {
				$this->load_by_name( $layoutName );
			}
		}

	}

	/**
	 * Loads layouts option and sanitizes to make sure nothings corrupt
	 *
	 * @param  string $prefix Optional, specify option prefix to get layouts for
	 *
	 * @return array
	 *
	 * @throws \WCP\LayoutException
	 *
	 * @since   1.0.1
	 * @updated 2.0.1
	 */
	public static function getLayouts( $prefix = Wcp_Plugin::OPTION_PREFIX ): array {

		if (empty(self::$layouts)) {
			self::$layouts = (array) get_option( 'layouts', [], null, $prefix );
			if ( ! is_array( self::$layouts ) ) {
				// TRANSLATORS: Exception error message thrown when the list of layout names is invalid
				throw new LayoutException( esc_html__( 'Wysiwyg Custom Products layouts have been corrupted',
				                                       'wysiwyg-custom-products' ) );
			}
			array_walk( self::$layouts, 'sanitize_text_array' );
		}
		return self::$layouts;
	}

	/**
	 * Validator to see if a string is the name of an existing layout
	 *
	 * @param string $checkName
	 *
	 * @return bool
	 *
	 * @since   1.0.1
	 * @updated 1.0.5
	 */
	public static function does_layout_exist( $checkName ): bool {
		try {
			$result = in_arrayi( $checkName, self::getLayouts(), true );
		} catch
		( Exception $e ) {
			$result = false;
		}

		return $result;
	}


	/**
	 * Retrieves and validates the named layout from the database
	 *
	 * @param $layoutName
	 *
	 * @return bool|mixed
	 *
	 * @throws \WCP\ObjectException
	 *
	 * @since    1.0.1
	 * @updated  2.0.1
	 */
	public static function get_layout_array( $layoutName ) {
		$layout = get_option_entquotes( $layoutName );
		try {
			self::is_layout_valid( $layout );
		} catch ( LayoutException $e ) {
			return false;
		}

		return $layout;
	}

	/**
	 * Makes array safe for Ajax GET
	 *
	 * @param string $layoutName
	 *
	 * @return array
	 *
	 * @throws \WCP\ObjectException
	 *
	 * @since   1.0.1
	 * @updated 2.0.1
	 */
	public static function ajax_get( $layoutName ): array {
		$result = self::get_layout_array( $layoutName );

		if ( $result ) {
			// Override plain text
			$result['MultilineReformat']  = esc_textarea_json_output( $result['MultilineReformat'] );
			$result['NumberOfLines']      = esc_textarea_json_output( $result['NumberOfLines'] );
			$result['SinglelineReformat'] = esc_textarea_json_output( $result['SinglelineReformat'] );
			$result['IeMessage']          = esc_textarea_json_output( $result['IeMessage'] );
		}

		return $result;
	}

	/**
	 *  Deletes all WCP layout data
	 *
	 * @since   1.0.0
	 * @updated 1.0.4
	 */
	public static function delete_layout_data() {
		try {
			$layouts = self::getLayouts();
			foreach ( $layouts as $layout ) {
				delete_option( $layout );
			}
		} catch ( Exception $e ) {
			// Ignore errors
		}
		delete_option( 'layouts' );
	}

	/**
	 * Validator to see if an array has correct layout information
	 *
	 * @param array &$layout
	 * @param bool  $sanitize Indicates that fields should be sanitized as necessary (Optional)
	 *
	 * @throws \WCP\LayoutException If layout is invalid
	 * @throws \WCP\ObjectException
	 *
	 * @since   1.0.1
	 * @updated 2.0.0
	 */
	public static function is_layout_valid( array &$layout, $sanitize = false ) {
		if ( ! is_array( $layout ) ) {
			throw new LayoutException( self::$exceptionMsg . __LINE__ );
		}

		if ( ! isset( $layout['InkColor'] ) ) { // 1.1.1 Format update
			$layout['InkColor']           = self::$defaultLayout['template']['InkColor'];
			$layout['ActiveMouseColor']   = self::$defaultLayout['template']['ActiveMouseColor'];
			$layout['InactiveMouseColor'] = self::$defaultLayout['template']['InactiveMouseColor'];
		}

		if ( ( ! isset( $layout['IeMessage'] ) || ( $layout['IeMessage'] === '' ) ) ) { // 1.2.5 Format update
			$layout['IeMessage'] = self::get_customer_message( 'IeMessage' );
		}

		if ( ! isset( $layout['EffectUUID'] ) ) { // 2.0.0 Format update
			$layout['EffectUUID'] = '';
		}

		// Check to see that all, but only, the required values are supplied
		if ( array_diff( array_keys( self::$defaultLayout['template'] ), array_keys( $layout ) ) !== [] ) {
			throw new LayoutException( self::$exceptionMsg . __LINE__ );
		}

		self::int_check( $layout['SetupImage'] ); // Assume any int is valid
		self::int_check( $layout['OverlayImage'] ); // Assume any int is valid
		self::int_check( $layout['SetupWidth'], self::MIN_IMAGE_SIZE, self::MAX_IMAGE_SIZE );
		self::int_check( $layout['SetupHeight'], self::MIN_IMAGE_SIZE, self::MAX_IMAGE_SIZE );
		self::int_check( $layout['MaxLines'], 1, self::MAX_LINES );
		$maxLines = $layout['MaxLines'];
		self::int_check( $layout['CurrentLines'], 1, $maxLines );
		self::is_string( $layout['MultilineReformat'] );
		self::is_string( $layout['NumberOfLines'] );
		self::is_string( $layout['SinglelineReformat'] );
		self::is_string( $layout['IeMessage'] );
		self::is_string( $layout['EffectUUID'] );
		self::int_check( $layout['InkColor'], 0, 0xFFFFFF );
		self::int_check( $layout['ActiveMouseColor'], 0, 0xFFFFFF );
		self::int_check( $layout['InactiveMouseColor'], 0, 0xFFFFFF );

		if ( $sanitize ) {
			$layout['MultilineReformat']  = sanitize_textarea_field( $layout['MultilineReformat'] );
			$layout['NumberOfLines']      = sanitize_textarea_field( $layout['NumberOfLines'] );
			$layout['SinglelineReformat'] = sanitize_textarea_field( $layout['SinglelineReformat'] );
			$layout['IeMessage']          = sanitize_textarea_field( $layout['IeMessage'] );
		}

		if ( ! is_array( $layout['Formats'] ) ) {
			throw new LayoutException( self::$exceptionMsg . __LINE__ );
		}

		$formatKeys = [];
		for ( $i = 1; $i <= $maxLines; $i ++ ) {
			$formatKeys[] = 'Lines' . $i;
		}

		// Check to see that all, but only, the required formats are supplied
		$formats = &$layout['Formats'];
		if ( array_diff( $formatKeys, array_keys( $formats ) ) !== [] ) {
			throw new LayoutException( self::$exceptionMsg . __LINE__ );
		}

		// Now make sure each format has the correct information
		$formatKeys  = array_keys( self::$defaultLayout['template']['Formats']['Lines1'][0] );
		$maxFontSize = intdiv( $layout['SetupHeight'], 2 );
		for ( $i = 1; $i <= $maxLines; $i ++ ) {
			self::is_format_valid( $formats, $i, $formatKeys, $maxFontSize, $sanitize );
		}
	}

	/**
	 * Sets size of image and sets up scaling factors
	 *
	 * @param $height
	 * @param $width
	 *
	 * @since    1.0.0
	 * @updated  1.2.0
	 */
	public function set_size( $height, $width ) {
		$this->height = $height;
		$this->width  = $width;

		$this->scaleY = $this->height / $this->setupHeight;
		$this->scaleX = $this->width / $this->setupWidth;
	}

	/**
	 * Name getter
	 *
	 * @return string
	 *
	 * @since 1.0.1
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * Name setter
	 *
	 * @param string $name
	 *
	 * @since 1.0.1
	 */
	public function setName( $name ) {
		$this->name = sanitize_text_field( $name );
	}

	/**
	 * ID getter
	 *
	 * @return string
	 *
	 * @since 1.2.6
	 */
	public function getID(): string {
		return $this->name;
	}

	/**
	 * Message getter
	 *
	 * @return string
	 *
	 * @since 1.0.1
	 */
	public function getMultiLineReformatMsg(): string {
		return $this->multiLineReformatMsg;
	}

	/**
	 * Message setter
	 *
	 * @param string $msg
	 *
	 * @since 1.0.1
	 */
	public function setMultiLineReformatMsg( $msg ) {
		$this->multiLineReformatMsg = sanitize_textarea_input( $msg );
	}

	/**
	 * Message getter
	 *
	 * @return string
	 *
	 * @since 1.0.1
	 */
	public function getNumberOfLinesMsg(): string {
		return $this->numberOfLinesMsg;
	}

	/**
	 * Message setter
	 *
	 * @param string $msg
	 *
	 * @since 1.0.1
	 */
	public function setNumberOfLinesMsg( $msg ) {
		$this->numberOfLinesMsg = sanitize_textarea_input( $msg );
	}

	/**
	 * Message getter
	 *
	 * @return string
	 *
	 * @since 1.0.1
	 */
	public function getSingleLineReformatMsg(): string {
		return $this->singleLineReformatMsg;
	}

	/**
	 * Message setter
	 *
	 * @param string $msg
	 *
	 * @since 1.0.1
	 */
	public function setSingleLineReformatMsg( $msg ) {
		$this->singleLineReformatMsg = sanitize_textarea_input( $msg );
	}

	/**
	 * Message getter
	 *
	 * @return string
	 *
	 * @since   1.2.5
	 * @updated 1.2.5
	 */
	public function getIeMessage(): string {
		if ( $this->ieMsg === '' ) {
			return self::get_customer_message( 'IeMessage' );
		}
		return $this->ieMsg;
	}

	/**
	 * Message setter
	 *
	 * @param string $msg
	 *
	 * @since   1.2.5
	 * @updated 1.2.5
	 */
	public function setIeMessage( $msg ) {
		$this->ieMsg = sanitize_textarea_input( $msg );
	}

	/**
	 * Does layout have an Effect
	 *
	 * @return bool
	 *
	 * @since   2.0.0
	 * @updated 2.0.0
	 */
	public function hasEffect(): bool {
		return $this->effectUUID !== '';
	}
	/**
	 * Layout wide SVG Effect getter
	 *
	 * @return string
	 *
	 * @since   2.0.0
	 * @updated 2.0.0
	 *
	 *          tdp2 add line parameter and get appropriate effect if line parameter is set  (which format #lines is being used??)
	 */
	public function getEffectUUID(): string {
		return $this->effectUUID;
	}

	/**
	 * Layout wide SVG Effect setter
	 *
	 * @param string $effectUUID
	 *
	 * @since   2.0.0
	 * @updated 2.0.0
	 */
	public function setEffectUUID( $effectUUID ) {
		$this->effectUUID = $effectUUID;
	}
	/**
	 * Gets the a hex string representation of the requested color
	 *
	 * @param string $color Color to return as string hex
	 *
	 * @return string
	 *
	 * @since    1.1.1
	 */
	public function getColorString( $color ): string {
		switch ( $color ) {
		case 'size' :
			$value = $this->activeMouseColor;
			break;
		case 'non-size' :
			$value = $this->inactiveMouseColor;
			break;
		default :
			$value = $this->inkColor;
		}

		return htmlColorHex( $value );
	}

	/**
	 * @param string $layoutName
	 *
	 * @throws \WCP\LayoutException If layout does not exist or is invalid
	 * @throws \WCP\ObjectException
	 *
	 * @since   1.0.0
	 * @updated 2.0.0
	 */
	public function load_by_name( $layoutName ) {
		$layout = get_option_entquotes( $layoutName );
		if ( ! is_array( $layout ) ) {
			throw new LayoutException( self::$exceptionMsg . __LINE__ );
		}
		$this->load_layout( $layout, $layoutName );
	}

	/**
	 * @param array  $layout
	 * @param string $layoutName
	 *
	 * @throws \WCP\LayoutException If layout is invalid
	 * @throws \WCP\ObjectException
	 *
	 * @since   1.0.0
	 * @updated 2.0.0
	 */
	public function load_layout( $layout, $layoutName ) {
		self::is_layout_valid( $layout );

		$this->name               = $layoutName;
		$this->setupWidth         = $layout['SetupWidth'];
		$this->setupHeight        = $layout['SetupHeight'];
		$this->width              = $this->setupWidth;  // Assume same for starters
		$this->height             = $this->setupHeight;
		$this->scaleX             = 1.0;
		$this->scaleY             = 1.0;
		$this->maxLines           = $layout['MaxLines'];
		$this->currentLines       = $layout['CurrentLines'];
		$this->image              = $layout['SetupImage'];
		$this->overlay            = $layout['OverlayImage'];
		$this->inkColor           = $layout['InkColor'];
		$this->activeMouseColor   = $layout['ActiveMouseColor'];
		$this->inactiveMouseColor = $layout['InactiveMouseColor'];

		$this->setMultiLineReformatMsg( $layout['MultilineReformat'] );
		$this->setNumberOfLinesMsg( $layout['NumberOfLines'] );
		$this->setSingleLineReformatMsg( $layout['SinglelineReformat'] );
		$this->setIeMessage( $layout['IeMessage'] );
		$this->setEffectUUID( $layout['EffectUUID'] );

		$this->formats = $layout['Formats'];

		do_action( 'load_layout' );
	}

	/**
	 * Converts current values as an array for saving & Ajax GET
	 *
	 * @return array
	 *
	 * @since   1.0.0
	 * @updated 1.2.5
	 */
	public function as_array(): array {
		$result                       = [];
		$result['SetupImage']         = $this->image;
		$result['OverlayImage']       = $this->overlay;
		$result['SetupWidth']         = $this->setupWidth;
		$result['SetupHeight']        = $this->setupHeight;
		$result['MaxLines']           = $this->maxLines;
		$result['CurrentLines']       = $this->currentLines;
		$result['Formats']            = $this->formats;
		$result['InkColor']           = $this->inkColor;
		$result['ActiveMouseColor']   = $this->activeMouseColor;
		$result['InactiveMouseColor'] = $this->inactiveMouseColor;
		$result['MultilineReformat']  = $this->multiLineReformatMsg;
		$result['NumberOfLines']      = $this->numberOfLinesMsg;
		$result['SinglelineReformat'] = $this->singleLineReformatMsg;
		$result['IeMessage']          = $this->ieMsg;
		$result['EffectUUID']         = $this->effectUUID;

		$result = apply_filters( 'layout_as_array', $result );

		return apply_filters( 'layout_as_array_' . $this->name, $result );
	}

	/**
	 * Saves current layout
	 *
	 * @since   1.0.0
	 */
	public function save() {
		$array = $this->as_array();
		array_to_int( $array, false );
		update_option( $this->name, $array );
	}

	/**
	 * Clears any existing layouts (if present and specified) then creates and saves the defaultLayout from above
	 *
	 * @param bool $overwrite If set forces current layouts to be discarded and the default saved
	 *
	 * @return bool If layout is invalid
	 *
	 * @throws \WCP\LayoutException If layout is invalid
	 * @throws \WCP\ObjectException
	 *
	 * @since   1.0.0
	 * @updated 2.0.1
	 */
	public function save_defaults( $overwrite = false ): bool {
		if ( !$overwrite && !empty(self::getLayouts()) ) {  // Should I overwrite existing layouts?
			return false;
		}

		self::delete_layout_data();

		$optionNames = []; // add default layouts
		foreach ( self::$defaultLayout as $name => $option ) {
			if ( '' === $option['MultilineReformat'] ) {
				$option['MultilineReformat'] = self::get_customer_message( 'MultilineReformat' );
			}
			if ( '' === $option['NumberOfLines'] ) {
				$option['NumberOfLines'] = self::get_customer_message( 'NumberOfLines' );
			}
			if ( '' === $option['SinglelineReformat'] ) {
				$option['SinglelineReformat'] = self::get_customer_message( 'SinglelineReformat' );
			}
			if ( '' === $option['IeMessage'] ) {
				$option['IeMessage'] = self::get_customer_message( 'IeMessage' );
			}

			$this->load_layout( $option, $name );

			$array = $this->as_array();
			array_to_int( $array, false );
			$array = apply_filters( 'save_default_array', $array );
			$array = apply_filters( 'save_default_array_' . $name, $array );

			add_option( $name, $array, false );
			$optionNames[] = $name;
		}

		$optionNames = apply_filters( 'save_default_names', $optionNames );
		add_option( 'layouts', $optionNames, false );

		return true;
	}

	/**
	 * Creates an abbreviated array for JSON purposes for the format specified by the number of lines
	 *
	 * @param $numberOfLines
	 *
	 * @return string
	 *
	 * @since   1.0.0
	 * @updated 2.0.0
	 */
	public function compact_format( $numberOfLines ): string {
		$lines   = maybe_get( $this->formats, 'Lines' . $numberOfLines, [] );
		$formats = [];

		foreach ( $lines as $line ) {
			$line      = apply_filters( 'before_line_implode', $line ); // Allows for modification of any of the following values
			$line      = implode( ',', [
				$line['Y'],
				$line['X'],
				$line['Width'],
				$line['Align'],
				$line['MinFont'],
				$line['MaxFont'],
				$line['Path'],
			] );
			$line      = apply_filters( 'after_line_implode', $line ); // Allows for the addition of extra values
			$formats[] = $line;
		}

		return apply_filters( 'compact_format',
		                      implode( '|', $formats ) ); // Allows for the addition of extra format lines
	}


	/**
	 * Used in scale(X, Y) transform
	 *
	 * @return string
	 *
	 * @since   1.1.0
	 * @updated 1.2.3
	 */
	public function getScale(): string {
		return "scale($this->scaleX $this->scaleY)";
	}
	/**
	 * Modify formats to put X at start of line as paths do there own text-align
	 *
	 * @since   2.0.0
	 * @updated 2.0.0
	 *
	 */
	public function updateXPosition() {
		foreach ( $this->formats as &$format ) {
			foreach ( $format as &$line ) {
				$x = $line['X'];
				switch ( $line['Align'] ) {
				case 'C' :
					$x -= $line['Width'] / 2;
					break;
				case 'R':
					$x -= $line['Width'];
					break;
				}
				$line['X'] = max( $x, 0 );
			}
		}
	}

	/**
	 * Validator to make sure a value is an integer and within a range if specified
	 *
	 * @param          $value
	 * @param bool|int $min
	 * @param int      $max
	 *
	 * @throws \WCP\LayoutException
	 * @since   1.0.1
	 * @updated 1.1.9
	 */
	private static function int_check( $value, $min = PHP_INT_MIN, $max = PHP_INT_MAX ) {
		if ( ! int_range_check( $value, $min, $max ) ) { // Wrong type or out of range
			throw new LayoutException( self::$exceptionMsg . __LINE__ );
		}
	}

	/**
	 * Validator to make sure a value is a string
	 *
	 * @param $value
	 *
	 * @throws LayoutException
	 *
	 * @since   1.0.1
	 * @updated 1.1.9
	 */
	private static function is_string( $value ) {
		if ( ! is_string( $value ) ) { // Wrong type
			throw new LayoutException( self::$exceptionMsg . __LINE__ );
		}
	}

	/**
	 * Checks to make sure $Layout['Formats']['Lines' . $lines] is a suitably formed format
	 *
	 * @param array   &$formatArray $Layout['Formats']
	 * @param integer $lines        Number of lines in this format
	 * @param array   $formatKeys   ['Y', 'X', ..., 'MaxFont'] Derived from default layout
	 * @param integer $maxFontSize  Calculated as half image height
	 * @param bool    $sanitize     Indicates that fields should be sanitized as necessary
	 *
	 * @throws \WCP\LayoutException
	 * @throws \WCP\ObjectException
	 *
	 * @since   1.0.1
	 * @updated 2.0.0
	 */
	private static function is_format_valid( array &$formatArray, $lines, array $formatKeys, $maxFontSize, $sanitize ) {
		/* @var array $format */
		$format = &$formatArray[ 'Lines' . $lines ];
		// Does the format contain the required number of line entries
		if ( count( $format ) !== $lines ) {
			throw new LayoutException( self::$exceptionMsg . __LINE__ );
		}

		// Does each line in the format contain the correct information
		/* @var array $line */
		foreach ( $format as &$line ) {
			// 1.1.0 Update - add 'Attributes' and Css as necessary
			if ( ! isset( $line['Attributes'] ) ) {
				$line['Attributes'] = '';
			}

			if ( ! isset( $line['Css'] ) ) {
				$line['Css'] = '';
			}

			// 1.2.0 Update - add TextPathUUID if necessary
			if ( ! isset( $line['TextPathUUID'] ) ) {
				$line['TextPathUUID'] = '';
			}

			if ( ! isset( $line['EffectUUID'] ) ) { // 2.0.0 Format update
				$line['EffectUUID'] = '';
			}

			// 2.0.0 Update
			if ( ! isset( $line['Group'] ) ) {
				$line['Group'] = 1;
			}

			if ( array_diff( $formatKeys, array_keys( $line ) ) !== [] ) {
				throw new LayoutException( self::$exceptionMsg . __LINE__ );
			}

			self::int_check( $line['Y'], 0, self::MAX_IMAGE_SIZE );
			self::int_check( $line['X'], 0, self::MAX_IMAGE_SIZE );
			self::int_check( $line['Width'], 0, self::MAX_IMAGE_SIZE );
			self::int_check( $line['MinFont'], self::MIN_FONT_SIZE, $maxFontSize );
			self::int_check( $line['MaxFont'], $line['MinFont'], $maxFontSize );

			$align = $line['Align'];
			if ( ! is_string( $align ) || ( strlen( $align ) !== 1 ) || ( strpos( 'CLR', $align ) === false ) ) {
				throw new LayoutException( self::$exceptionMsg . __LINE__ );
			}
			self::is_string( $line['Attributes'] );
			self::is_string( $line['Css'] );
			self::is_string( $line['TextPathUUID'] );
			self::is_string( $line['EffectUUID'] );
			self::int_check( $line['Group'], 1, $lines );

			if ( $sanitize ) {
				$line['Attributes']   = sanitize_text_field( $line['Attributes'] );
				$line['Css']          = sanitize_text_field( $line['Css'] );
				$line['Css']          = str_replace( [ ',', '|' ], ' ', $line['Css'] ); // Remove compact line delimiters
				$line['TextPathUUID'] = sanitize_text_field( $line['TextPathUUID'] );
			}

						$line['Path'] = TextPath::addHTextPath( $line['Width'] );


			if ( strpos( $line['Css'], ',' ) || strpos( $line['Css'], '|' ) ) {  // Ensure none have sneaked in
				throw new LayoutException( self::$exceptionMsg . __LINE__ );
			}
		}
	}

	/**
	 * Gets a default  message when one isn't specified. Done this way to allow for translation
	 *
	 * @param string $messageName
	 *
	 * @return string
	 *
	 * @since   1.0.0
	 * @updated 1.2.5
	 */
	private static function get_customer_message( $messageName ): string {
		switch ( $messageName ) {
		case 'MultilineReformat':
			// TRANSLATORS: text used when customer uses too long a text in a text area
			$message = esc_html__( 'Please continue with message. Press [Enter] for new lines - type size will adjust. Tip: Edit line breaks to get desired layout.',
			                       'wysiwyg-custom-products' );
			break;
		case 'NumberOfLines':
			// TRANSLATORS: text used when customer uses too many lines in a text area
			$message = esc_html__( "Sorry, that's too many lines.", 'wysiwyg-custom-products' );
			break;
		case 'SinglelineReformat':
			// TRANSLATORS: text used when customer uses too long a text in a single text input
			$message = esc_html__( 'Text is too long to fit. Please check length of text.',
			                       'wysiwyg-custom-products' );
			break;
		case 'IeMessage':
			// TRANSLATORS: text used when to let customer know that Edge or IE aren't the best
			$message = esc_html__( 'This site uses internet standards (SVG) that are not fully supported by Microsoft browsers (Edge or Internet Explorer). The preview images are best viewed using Chrome or Firefox.',
			                       'wysiwyg-custom-products' );
			break;
		default:
			$message = '';
		}

		return apply_filters( 'get_overflow_msg_' . $messageName, $message );
	}
}
