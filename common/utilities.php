<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 19/08/16
 * Time: 11:28 AM
 *
 * @since   1.0.0
 * @updated 2.0.1
 */

namespace {  // Some global functions for array walks
	defined( 'ABSPATH' ) || exit;

	if ( ! function_exists( 'add_quote' ) ) {
		/**
		 * Puts quotes around each value in an array when used as a callback function
		 *
		 * @param $value
		 * @param $key
		 *
		 * @since      1.0.0
		 * @updated    1.0.0
		 */
		function add_quote(
			&$value,
			/** @noinspection PhpUnusedParameterInspection */
			$key
		) {
			$value = "'$value'";
		}
	}

	if ( ! function_exists( 'to_int' ) ) {
		/**
		 * Integer coercion for each value in an array when used as a callback function
		 *
		 * @param mixed $value
		 * @param void  $key
		 * @param bool  $emptyStringToZero
		 *
		 * @since      1.0.0
		 * @updated    1.0.0
		 *
		 */
		function to_int(
			&$value,
			/** @noinspection PhpUnusedParameterInspection */
			$key,
			$emptyStringToZero
		) {
			if ( is_numeric( $value ) ) {
				$value = (int) $value;
			} else if ( $emptyStringToZero && ( '' === $value ) ) {
				$value = 0;
			}
		}
	}

	if ( ! function_exists( 'sanitize_text_array' ) ) {
		/**
		 * Runs sanitize_text_field on each value in an array when used as a callback function
		 *
		 * @param $value
		 * @param $key
		 *
		 * @since      1.0.0
		 * @updated    1.0.0
		 */
		function sanitize_text_array(
			&$value,
			/** @noinspection PhpUnusedParameterInspection */
			$key
		) {
			$value = sanitize_text_field( $value );
		}
	}
}

namespace WCP {

	use function array_column;
	use function count;
	use function in_array;
	use function is_array;
	use function is_int;
	use function is_numeric;
	use function is_string;
	use function trim;
	use function wc_get_image_size;
	use function wp_allowed_protocols;
	use function wp_attachment_is_image;
	use function wp_kses_check_attr_val;
	use function wp_kses_hair;
	use const PHP_INT_MAX;
	use const PHP_INT_MIN;

	/**
	 * Routine to html decode - including any quotes - a string or array of strings
	 *
	 * @param mixed $input
	 *
	 * @return array|mixed|string
	 *
	 * @since   1.0.0
	 * @updated 1.0.1
	 */
	function html_ent_quotes( $input ) {
		if ( is_string( $input ) ) {
			$result = htmlspecialchars_decode( $input, ENT_QUOTES );
		} else if ( is_array( $input ) ) {
			$result = [];
			foreach ( $input as $key => $value ) {
				$result[ $key ] = is_string( $value ) ? htmlspecialchars_decode( $value, ENT_QUOTES ) : $value;
			}
		} else {
			$result = $input;
		}

		return $result;
	}

	/**
	 * Routine to replace the ubiquitous x = isset(array[y]) ? array[y] : defaultX calls
	 * x = maybe_get(array, y, default)
	 *
	 * Works with string or integer keys, returns a false by default if default is not specified.
	 * Can optionally trim any string result
	 *
	 * @param array      $array
	 * @param int|string $field
	 * @param mixed      $default
	 * @param bool       $trim
	 *
	 * @return mixed
	 *
	 * @since   1.0.0
	 * @updated 1.1.7
	 */
	function maybe_get( $array, $field, $default = false, $trim = true ) {
		$result = $default;

		if ( ! is_array( $array ) ) {
			return $result;
		}

		if ( is_string( $field ) ) {
			/** @noinspection NullCoalescingOperatorCanBeUsedInspection */
			$result = isset( $array[ $field ] ) ? $array[ $field ] : $default;
		} else if ( is_int( $field ) && ( $field < count( $array ) ) ) {
			$result = $array[ $field ];
		}

		if ( is_string( $result ) && $trim ) {
			return trim( $result );
		}

		return $result;
	}

	/**
	 * Goes through the passed array and turns any number or number string into an integer
	 * Optionally can treat the empty string as a 0
	 *
	 * @param array $array
	 * @param bool  $emptyStringToZero
	 *
	 * @return array
	 *
	 * @since   1.0.0
	 * @updated 1.2.6
	 */
	function array_to_int( &$array, $emptyStringToZero = true ) {
		array_walk_recursive( $array, 'to_int', $emptyStringToZero );

		return $array;
	}

	/**
	 * Ensures $value is an integer and that it's between min and max
	 *
	 * @param          $value
	 * @param bool|int $min
	 * @param int      $max
	 *
	 * @return bool
	 *
	 * @since   1.0.1
	 * @updated 1.0.1
	 */
	function int_range_check( $value, $min = PHP_INT_MIN, $max = PHP_INT_MAX ) {
		if ( ! is_int( $value ) ) {
			return false;
		}

		return ( $value >= $min && $value <= $max );
	}

	/**
	 * Ensures $str is a string and that it contains at least one character
	 *
	 * @param $str
	 *
	 * @return bool
	 *
	 * @since   1.0.1
	 * @updated 1.0.1
	 */
	function is_non_empty_string( $str ) {
		return is_string( $str ) && ( trim( $str ) !== '' );
	}

	/**
	 * Case insensitive array search
	 *
	 * @param      $needle
	 * @param      $haystack
	 * @param bool $strict optional - True checks types
	 *
	 * @return bool
	 *
	 * @since   1.0.5
	 * @updated 1.0.5
	 */
	function in_arrayi( $needle, $haystack, $strict = false ) {
		return in_array( strtolower( $needle ), array_map( 'strtolower', $haystack ), $strict );
	}

	/**
	 * Creates a colour string suitable for use in CSS, HTML etc. from an integer value
	 *
	 * @param int $colorValue
	 *
	 * @return string
	 *
	 * @since   1.1.1
	 * @updated 1.1.1
	 */
	function htmlColorHex( $colorValue ) {
		return '#' . substr( '000000' . dechex( $colorValue ), - 6 );
	}

	/**
	 * Get an image size by name or defined dimensions. Or use $id to get actual image size.
	 *
	 * The returned variable is filtered by woocommerce_get_image_size_{image_size} filter to
	 * allow 3rd party customisation.
	 *
	 * Sizes defined by the theme take priority over settings. Settings are hidden when a theme
	 * defines sizes.
	 *
	 * @param array|string $image_size   Name of the image size to get, or an array of dimensions.
	 * @param null|int     $attachmentId image attachment id
	 *
	 * @return array Array of dimensions including width, height, and cropping mode. Cropping mode is 0 for no crop, and 1 for hard crop.
	 *
	 * @since   1.1.4
	 * @updated 1.1.6
	 */
	function wcp_get_image_size( $image_size, $attachmentId = null ) {
		$size = [
			'width'  => 600,
			'height' => 600,
			'crop'   => 0,
		];

		if ( ( null !== $attachmentId ) && wp_attachment_is_image( $attachmentId ) ) {
			$meta           = wp_get_attachment_metadata( $attachmentId );
			$size['width']  = maybe_get( $meta, 'width', $size['width'] );
			$size['height'] = maybe_get( $meta, 'height', $size['height'] );
		} else {
			$wc_size = wc_get_image_size( $image_size );
			if ( is_numeric( $wc_size['width'] ) ) {
				$size['width'] = $wc_size['width'];
			}
			if ( is_numeric( $wc_size['height'] ) ) {
				$size['height'] = $wc_size['height'];
			}
		}

		return $size;
	}

	/**
	 * Self check to make sure text area content is safe before accepting
	 *
	 * @param string $msg
	 *
	 * @return string
	 *
	 * @since   1.0.1
	 * @updated 1.0.1
	 */
	function sanitize_textarea_input( $msg ) {
		return sanitize_textarea_field( stripslashes( $msg ) );
	}

	/**
	 * Escapes text area for JSON output
	 *
	 * @param string $msg
	 *
	 * @return string
	 *
	 * @since   1.0.1
	 * @updated 1.0.1
	 */
	function esc_textarea_json_output( $msg ) {
		return htmlspecialchars_decode( esc_textarea( stripslashes( $msg ) ),
		                                ENT_QUOTES ); // Need to replace any quotes that the esc_textarea
	}

	/**
	 *
	 *
	 * @param string     $attributeString
	 * @param array      $allowedAttributes
	 * @param array|null $allowedProtocols
	 *
	 * @return string
	 *
	 * @since   2.0.0
	 * @updated 2.0.0
	 *
	 */
	function kses_check_attributes( $attributeString, $allowedAttributes, $allowedProtocols = null ) {
		if ( ! is_array( $allowedProtocols ) ) {
			$allowedProtocols = wp_allowed_protocols();
		}

		$attributes = wp_kses_hair( $attributeString, $allowedProtocols );
		foreach ( $attributes as $attribute ) {
			if ( ! isset( $allowedAttributes[ $attribute['name'] ] ) ) {
				return "attribute : {$attribute['name']} not allowed";
			}
			if ( ! wp_kses_check_attr_val( $attribute['value'], $attribute['vless'], 'valueless', 'n' ) ) {
				return "attribute : {$attribute['name']} requires a value";
			}
		}

		$processed = implode( ' ', array_column( $attributes, 'whole' ) );
		if ( $processed !== $attributeString ) {
			return "invalid attributes $attributeString. (Processed: [$processed])";
		}

		return '';
	}

}


