<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 27/11/2019
 * Time: 1:44 PM
 */

namespace WCP;

defined( 'ABSPATH' ) || exit;

use Exception;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function count;
use function defined;
use function gettype;
use function is_array;
use function is_callable;
use function is_string;
use function wp_generate_uuid4;

/** * Class WCP\Object
 *
 * @package    WCP
 * @subpackage Common
 *
 * @since      2.0.0
 */
abstract class WcpObject {

	/**
	 *
	 */
	CONST NEW_OBJECT = 'new';

	/**
	 * @var array
	 */
	protected static $classAttributes = [];

	/**
	 * @var array List of all object names
	 */
	protected static $names;

	/**
	 * @var array List of all objects
	 */
	protected static $objects = [];

	/**
	 * @var string Current object name
	 */
	protected $name;

	/**
	 * @var string The current object's alias
	 */
	protected $alias;

	/**
	 * @var string Current object UUID
	 */
	protected $uuid;

	/**
	 * Object constructor.
	 *
	 * @param string $uuid Optional, id of object to load.
	 *
	 * @throws ObjectException  If object is invalid
	 *
	 * @since   2.0.0
	 * @updated 2.0.0
	 */
	public function __construct( $uuid = '' ) {
		$this->uuid = self::NEW_OBJECT;
		$this->name = self::getNewName();

		if ( ! empty( $uuid ) && ( self::NEW_OBJECT !== $uuid ) ) {
			$this->uuid = $uuid;
			$this->load( $uuid );
		}
	}

	/**
	 *
	 *
	 * @param string|null $field
	 *
	 * @return string
	 * @since   2.0.0
	 * @updated 2.0.0
	 */
	abstract public static function getI8nName( $field = null ): string;

	/**
	 *
	 *
	 * @return mixed
	 *
	 * @since   2.0.0
	 * @updated 2.0.0
	 */
	public static function getOptionName() {
		return static::$classAttributes['OptionName'];
	}

	/**
	 *
	 *
	 * @return array
	 *
	 * @since   2.0.0
	 * @updated 2.0.0
	 */
	public static function getWPFilters(): array {
		$result = [];

		foreach ( static::$classAttributes['Fields'] as $field => $attributes ) {
			$result[ $field ] = maybe_get( $attributes, 'wpFilterName', $field );
		}

		return $result;
	}

	/**
	 * Loads objects option and sanitizes to make sure nothings corrupt
	 *
	 * @param \bool|array $addNew
	 *
	 * @return array
	 * @throws \WCP\ObjectException
	 *
	 * @since    2.0.0
	 * @updated  2.0.0
	 */
	public static function loadNames( $addNew = false ): array {
		if ( null === static::$names ) {
			$objects = (array) get_option( static::$classAttributes['OptionName'] . 's', [] );
			if ( ! is_array( $objects ) ) {
				if ( $addNew ) {
					$objects = [];
					self::setLastUsedUUID( self::NEW_OBJECT );
				} else {
					// TRANSLATORS: Exception error message thrown when the list of object names is invalid
					throw new ObjectException( sprintf( esc_html__( 'Wysiwyg Custom Products %s have been corrupted', 'wysiwyg-custom-products' ), static::getI8nName() ) );
				}
			}

			static::$names = [];
			foreach ( $objects as $uuid => $objectName ) {
				static::$names[ sanitize_text_field( $uuid ) ] = sanitize_text_field( $objectName );
			}
		}

		if ( $addNew ) {
			if ( is_array( $addNew ) ) {
				return array_merge( $addNew, static::$names );
			}

			return array_merge( [ self::NEW_OBJECT => self::getNewName() ], static::$names );
		}

		return static::$names;
	}

	/**
	 * Save object names option
	 *
	 * @throws \WCP\ObjectException
	 * @since         2.0.0
	 * @updated       2.0.0
	 */
	public static function saveNames() {
		update_option( static::$classAttributes['OptionName'] . 's', self::loadNames(), null, false );
	}

	/**
	 * Validator to see if a string is the name of an existing object
	 *
	 * @param string $checkName
	 *
	 * @return bool
	 *
	 * @since   2.0.0
	 * @updated 2.0.0
	 */
	public static function does_objectName_exist( $checkName ): bool {
		try {
			$result = in_arrayi( $checkName, self::loadNames(), true );
		} catch
		( ObjectException $e ) {
			$result = false;
		}

		return $result;
	}

	/**
	 * Validator to see if a string is the uuid of an existing object
	 *
	 * @param string $uuid
	 *
	 * @return bool
	 *
	 * @since   2.0.0
	 * @updated 2.0.0
	 */
	public static function does_objectUUID_exist( $uuid ): bool {
		try {
			$result = array_key_exists( $uuid, self::loadNames() );
		} catch
		( Exception $e ) {
			$result = false;
		}

		return $result;
	}

	/**
	 * Retrieves and validates the named object from the database
	 *
	 * @param $uuid
	 *
	 * @return bool|mixed
	 *
	 * @since   2.0.0
	 * @updated 2.0.0
	 */
	public static function get_object_array( $uuid ) {
		$object = get_option_entquotes( self::getObjectOptionID( $uuid ) );
		try {
			self::is_valid( $object );
		} catch ( ObjectException $e ) {
			return false;
		}

		return $object;
	}

	/**
	 *  Deletes all WCP object data
	 *
	 * @since   2.0.0
	 * @updated 2.0.0
	 */
	public static function delete_object_data() {
		try {
			self::loadNames();
			foreach ( static::$names as $uuid => $object ) {
				delete_option( self::getObjectOptionID( $uuid ) );
			}
		} catch ( Exception $e ) {
			// Ignore errors
		}
		delete_option( static::$classAttributes['OptionName'] . 's' );
	}

	/**
	 * Gets the wp option name for a given object
	 *
	 * @param  string $uuid
	 *
	 * @return string
	 * @since   2.0.0
	 * @updated 2.0.0
	 */
	public static function getObjectOptionID( $uuid ): string {
		return static::$classAttributes['OptionName'] . $uuid;
	}

	/**
	 * Validator to see if an array has correct object information
	 *
	 * @param array $object
	 * @param bool  $sanitize Indicates that fields should be sanitized as necessary (Optional)
	 *
	 *
	 * @throws \WCP\ObjectException If object is invalid
	 *
	 * @since    2.0.0
	 * @updated  2.0.0
	 */
	public static function is_valid( array &$object, $sanitize = false ) {
		if ( ! is_array( $object ) ) {
			throw new ObjectException( self::getExceptionMsg() . __LINE__ );
		}

		// Check to see that all, but only, the required values are supplied
		if ( array_diff( self::getFieldNames(), array_keys( $object ) ) !== [] ) {
			throw new ObjectException( self::getExceptionMsg() . __LINE__ );
		}

		self::is_string( 'UUID', $object['UUID'] );
		self::is_string( 'Name', $object['Name'] );

		foreach ( static::$classAttributes['Fields'] as $field => $attributes ) {
			$validator = maybe_get( $attributes, 'validator' );

			if ( $validator && is_callable( $validator ) ) {
				$validator( $field, $object );
			} else {
				self::is_string( $field, $object[ $field ] );
			}
		}

		if ( $sanitize ) {
			self::sanitize( $object );
		}

	}

	/**
	 * @param array $object
	 */
	public static function sanitize( array &$object ) {
		$object['UUID'] = sanitize_text_field( $object['UUID'] );
		$object['Name'] = sanitize_text_field( $object['Name'] );
		foreach ( static::$classAttributes['Fields'] as $field => $attributes ) {
			if ( 'text' === $attributes['type'] ) {
				$object[ $field ] = sanitize_text_field( $object[ $field ] );
			} else if ( 'textarea' === $attributes['type'] ) {
				$object[ $field ] = sanitize_textarea_field( $object[ $field ] );
			}
		}
	}

	/**
	 * Deletes specified object
	 *
	 * @param string $uuid
	 *
	 * @throws \WCP\ObjectException
	 *
	 * @since   2.0.0
	 * @updated 2.0.0
	 */
	public static function delete( $uuid ) {
		delete_option( self::getObjectOptionID( $uuid ) );
		self::loadNames();
		unset( static::$names[ $uuid ] );
		self::saveNames();
	}

	/**
	 * Loads objects option and sanitizes to make sure nothings corrupt
	 *
	 * @param array $objects
	 *
	 * @return array
	 *
	 * @throws \WCP\ObjectException
	 *
	 * @since    2.0.0
	 * @updated  2.0.0
	 */
	public static function loadObjects( $objects = null ): array {

		$objectClass = self::getClass();
		$count       = 0;

		static::$objects = [];
		if ( null === $objects ) {
			$objects = self::loadNames();
		}

		foreach ( $objects as $uuid => $name ) {
			try {
				/* @var $object \WCP\WcpObject */
				$object = new $objectClass( $uuid );

				$alias = static::$classAttributes['AliasPrefix'] . ++ $count;

				$object->setAlias( $alias );
				static::$objects[ $uuid ] = $object;

			} catch ( ObjectException $e ) {
				// ignore
			}

		}

		return static::$objects;
	}

	/**
	 * Check to see if object is already added
	 *
	 * @param $uuid
	 *
	 * @return bool
	 *
	 * @since    2.0.1
	 * @updated  2.0.1
	 */
	public static function isObjectLoaded( $uuid ) {
		return isset( static::$objects[ $uuid ] );
	}
	/**
	 * Adds a object
	 *
	 * @param $uuid
	 *
	 * @return Mixed
	 *
	 * @throws \WCP\ObjectException
	 *
	 * @since        2.0.0
	 * @updated      2.0.0
	 * @noinspection PhpDocRedundantThrowsInspection
	 */
	public static function addObject( $uuid ) {

		if ( isset( static::$objects[ $uuid ] ) ) {
			return static::$objects[ $uuid ];
		}

		$objectClass = self::getClass();

		/* @var $object \WCP\WcpObject */
		$object = new $objectClass( $uuid );

		$alias = static::$classAttributes['AliasPrefix'] . ( count( static::$objects ) + 1 );

		$object->setAlias( $alias );
		static::$objects[ $uuid ] = $object;

		return $object;
	}

	/**
	 * Retrieves all current object aliases
	 *
	 * @return array
	 *
	 * @since   2.0.0
	 * @updated 2.0.0
	 */
	public static function getAliases(): array {
		$result = [];
		/* @var $object \WCP\WcpObject */
		foreach ( static::$objects as $uuid => $object ) {
			$result[ $uuid ] = $object->getAlias();
		}

		return $result;
	}

	/**
	 * Retrieves the object alias based on the uuid
	 *
	 * @param $uuid
	 *
	 * @return string
	 *
	 * @since   2.0.0
	 * @updated 2.0.0
	 */
	public static function getObjectAlias( $uuid ): string {
		return static::$objects[ $uuid ]->getAlias();
	}

	/**
	 * @param string $default
	 *
	 * @return \string
	 */
	public static function getLastUsedUUID( $default ): string {
		return sanitize_text_field( get_option( 'settings', $default, 'Current' . static::$classAttributes['OptionName'] . 'UUID' ) );
	}

	/**
	 * @param string $uuid
	 */
	public static function setLastUsedUUID( $uuid ) {
		update_option( 'settings', $uuid, 'Current' . static::$classAttributes['OptionName'] . 'UUID' );
	}

	/**
	 *
	 *
	 * @return string
	 *
	 * @since   2.0.0
	 * @updated 2.0.0
	 */
	public static function getNewName(): string {
// TRANSLATORS: Heading used for new item creation on the settings page
		return sprintf( esc_html__( 'New %s', 'wysiwyg-custom-products' ), static::getI8nName() );
	}

	/**
	 *
	 *
	 * @return mixed
	 *
	 * @since   2.0.0
	 * @updated 2.0.0
	 */
	public static function getNew() {
		$class = self::getClass();

		return new $class( self::NEW_OBJECT );
	}

	/**
	 *
	 *
	 * @param $field
	 * @param $value
	 *
	 * @since   2.0.0
	 * @updated 2.0.0
	 */
	public function set( $field, $value ) {
		$function = $this->getFunction( $field, 'set' );
		if ( $function ) {
			$this->$function( $value );
		}
	}

	/**
	 * @param $field
	 *
	 * @return string
	 */
	public function get( $field ): string {
		$function = $this->getFunction( $field, 'get' );
		if ( $function ) {
			return $this->$function();
		}

		return '';
	}

	/**
	 * UUID getter
	 *
	 * @return string
	 *
	 * @since   2.0.0
	 * @updated 2.0.0
	 */
	public function getUuid(): string {
		if ( ( '' === $this->uuid ) || ( 'new' === $this->uuid ) ) {
			$this->uuid = wp_generate_uuid4();
		}

		return $this->uuid;
	}

	/**
	 * UUID setter
	 *
	 * @param string $uuid
	 *
	 * @since   2.0.0
	 * @updated 2.0.0
	 */
	public function setUuid( $uuid ) {
		$this->uuid = $uuid;
	}

	/**
	 * Name getter
	 *
	 * @return string
	 *
	 * @since   2.0.0
	 * @updated 2.0.0
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * Name setter
	 *
	 * @param string $name
	 * @param bool   $noSave
	 *
	 * @throws \WCP\ObjectException
	 * @since   2.0.0
	 * @updated 2.0.0
	 */
	public function setName( $name, $noSave = false ) {
		$this->name = sanitize_text_field( $name );
		if ( $noSave ) {
			return;
		}
		$this->save();

		self::loadNames();
		static::$names[ $this->uuid ] = $this->name;
		self::saveNames();
		self::setLastUsedUUID( $this->uuid );
	}

	/**
	 * Alias getter
	 *
	 * @return string
	 *
	 * @since   2.0.0
	 * @updated 2.0.0
	 */
	public function getAlias(): string {
		return $this->alias;
	}

	/**
	 * Alias setter
	 *
	 * @param string $alias
	 *
	 * @since   2.0.0
	 * @updated 2.0.0
	 */
	public function setAlias( $alias ) {
		$this->alias = $alias;
	}

	/**
	 * Makes array safe for Ajax GET
	 *
	 * @return array
	 *
	 * @since   2.0.0
	 * @updated 2.0.0
	 */
	public function getJson(): array {
		$result = $this->as_array();

		foreach ( static::$classAttributes['Fields'] as $field => $attributes ) {
			if ( 'textarea' === $attributes['type'] ) {
				$result[ $field ] = esc_textarea_json_output( $result[ $field ] );
			}
		}

		return $result;
	}

	/**
	 * @param string $uuid
	 *
	 * @throws ObjectException If object does not exist or is invalid
	 *
	 * @since   2.0.0
	 * @updated 2.0.0
	 */
	public function load( $uuid ) {
		$object = get_option_entquotes( self::getObjectOptionID( $uuid ), [] );
		self::is_valid( $object );

		$this->uuid = $uuid;
		$this->setName( $object['Name'], true );

		foreach ( static::$classAttributes['Fields'] as $field => $attributes ) {
			$this->set( $field, $object[ $field ] );
		}
		do_action( 'load_' . static::$classAttributes['OptionName'] );
	}

	/**
	 * Converts current values as an array for saving & Ajax GET
	 *
	 * @return array
	 *
	 * @since   2.0.0
	 * @updated 2.0.0
	 */
	public function as_array(): array {
		$result['UUID'] = $this->getUuid();
		$result['Name'] = $this->getName();

		foreach ( static::$classAttributes['Fields'] as $field => $attributes ) {
			$result[ $field ] = $this->get( $field );
		}

		$result = apply_filters( static::$classAttributes['OptionName'] . '_as_array', $result );

		return apply_filters( static::$classAttributes['OptionName'] . '_as_array_' . $this->getName(), $result );
	}

	/**
	 * Saves current object
	 *
	 * @since   2.0.0
	 * @updated 2.0.0
	 */
	public function save() {
		$array = $this->as_array();
		if ( self::NEW_OBJECT === $this->uuid ) { // Create
			add_option( self::getObjectOptionID( $this->getUuid() ), $array, false );
		} else {
			update_option( self::getObjectOptionID( $this->getUuid() ), $array, null, false );
		}
	}

	/**
	 * Clears any existing objects (if present and specified)
	 *
	 * @param bool $overwrite If set forces current objects to be discarded and the default saved
	 *
	 * @return bool
	 *
	 * @since   2.0.0
	 * @updated 2.0.0
	 */
	public function save_defaults( $overwrite = false ): bool {
		if ( static::$names && ! $overwrite ) {
			return false;
		}

		self::delete_object_data();

		return true;
	}

	/**
	 * Validator to make sure a value is a string
	 *
	 * @param string $field
	 * @param        $value
	 *
	 * @throws \WCP\ObjectException
	 * @since   2.0.0
	 * @updated 2.0.0
	 */
	protected static function is_string( $field, $value ) {
		if ( ! is_string( $value ) ) { // Wrong type
			throw new ObjectException( self::getexceptionMsg() . '"' . $field . '" value should be a string. ' . gettype( $value ) . ' was supplied instead.' );
		}
	}

	/**
	 *
	 *
	 * @return string
	 *
	 * @since   2.0.0
	 * @updated 2.0.0
	 */
	private static function getExceptionMsg(): string {
		// TRANSLATORS: Exception error message thrown when the object is invalid
		return sprintf( esc_html__( 'Invalid Wysiwyg Custom Products %s supplied. Error: ', 'wysiwyg-custom-products' ), static::getI8nName() );

	}

	/**
	 * @return array
	 */
	private static function getFieldNames(): array {
		return array_merge( [ 'UUID', 'Name' ], array_keys( static::$classAttributes['Fields'] ) );
	}

	/**
	 * Returns the class name of the child class extending this class
	 *
	 * @return string The class name
	 */
	private static function getClass(): string {
		if ( ! isset( static::$classAttributes['ClassName'] ) ) {
			die( 'You MUST provide a valid <code>classAttributes["ClassName"]</code> in your object-class!' );
		}

		return static::$classAttributes['ClassName'];
	}

	/**
	 *
	 *
	 * @param $field
	 * @param $type
	 *
	 * @return bool|mixed
	 *
	 * @since   2.0.0
	 * @updated 2.0.0
	 */
	private function getFunction( $field, $type ) {
		$attributes = static::$classAttributes['Fields'][ $field ];
		$function   = maybe_get( $attributes, $type, $type . $field );
		if ( is_callable( [ $this, $function ] ) ) {
			return $function;
		}

		return false;
	}
}
