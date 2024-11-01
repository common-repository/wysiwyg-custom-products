<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 7/10/16
 * Time: 12:30 PM
 */

namespace WCP;

use function add_action;
use function add_filter;
use function array_merge;
use function count;
use function defined;
use function implode;
use function is_array;
use function is_numeric;
use function str_replace;
use function wp_generate_uuid4;
use const ENT_QUOTES;
use const WCP_COMMON_DIR;

defined( 'ABSPATH' ) || exit;

/**
 * Class Frontend
 *
 * @package    WCP\Frontend
 *
 * @since      1.0.0
 * @updated    2.1.0
 */
class Frontend {

	//<editor-fold desc="Fields">

	/**
	 *
	 */
	const SINGLE_SIZES = 'shop_single;woocommerce_single;';

	/**
	 *
	 */
	const CATALOG_SIZES = 'shop_catalog;woocommerce_thumbnail;';
	/**
	 * @var bool
	 */
	private $single_product = false;

	/**
	 * @var WcpProduct;
	 */
	private $wcpProduct;

	/**
	 * @var
	 */
	private $modifiedImageHtml;

	/**
	 * @var
	 */
	private $height;
	/**
	 * @var
	 */
	private $width;

	/**
	 * @var
	 */
	private $size;

	/**
	 * @var \WCP\Wp_Html_Helper
	 */
	private $wp_html_helper;

	/**
	 * @var bool
	 */
	private $fontFiddle;

	/**
	 * @var bool
	 */
	private $useTspan;

	/**
	 * @var string
	 */
	private $textTag = 'textPath';

	/**
	 * @var string
	 */
	private $dominantBaseline = 'middle';

	/**
	 * @var array
	 */
	private $svgInfo;

	/**
	 * @var int
	 */
	private $postId;
	//</editor-fold>

	/**
	 * Frontend constructor.
	 *
	 * @since   1.0.0
	 * @updated 2.1.0
	 *
	 */
	public function __construct() {

		global $is_edge;
		global $is_IE;
		global $is_safari;

		add_action( 'woocommerce_before_single_product', [ $this, 'start_single' ], 100 );
		add_action( 'woocommerce_before_shop_loop_item', [ $this, 'start_catalog_item' ], 100 );

		add_filter( 'post_thumbnail_html', [ $this, 'product_image' ], 100, 5 );
		add_filter( 'woocommerce_product_get_image', [ $this, 'new_catalog_thumbnail' ], 100, 5 );
		add_filter( 'woocommerce_single_product_image_thumbnail_html', [ $this, 'single_image' ], 100, 2 );
		$this->fontFiddle = $is_edge || $is_IE || $is_safari;
		if ( $this->fontFiddle ) {
			$this->dominantBaseline = 'baseline';
		}

		$this->useTspan = $is_safari;
		if ( $this->useTspan ) {
			add_filter( Wcp_Plugin::OPTION_PREFIX . 'before_line_implode', [ $this, 'correctLineX' ], 5, 1 );
			$this->textTag = 'tspan';
		}

	}

	/**
	 *
	 *
	 * @param array $line
	 *
	 * @return array
	 *
	 * @since   2.1.0
	 * @updated 2.1.0
	 *
	 */
	public function correctLineX( $line ): array {
		$line['X'] = self::getLineX( $line );

		return $line;
	}

	// tSpanRevert

	/**
	 * Modifies textpath position for tspans
	 *
	 * @param array $line
	 *
	 * @return number
	 *
	 * @since   2.1.0
	 * @updated 2.1.0
	 *
	 */
	private static function getLineX( $line ) {
		$x = $line['X'];
		switch ( $line['Align'] ) {
			case 'C' :
				$x += $line['Width'] / 2;
				break;
			case 'R':
				$x += $line['Width'];
				break;
		}

		return max( $x, 0 );
	}

	/**
	 *
	 *
	 * @since   2.0.1
	 * @updated 2.0.3
	 *
	 */
	private function load_wc_product() {
		$postId = get_the_ID();

		if ( isset( $this->wcpProduct ) ) {
			$this->wcpProduct->loadProduct( $postId );
		} else {
			$this->wcpProduct = new WcpProduct( $postId );
		}
	}

	/**
	 * Handles the woocommerce action hook at the start of displaying a the main product on the buy page
	 *
	 * @since   1.1.7
	 * @updated 2.0.1
	 */
	public function start_single() {
		$this->single_product = true;
		$this->load_wc_product();
//<editor-fold desc="Image Capture">
		//</editor-fold>
		$this->addStyles();
	}

	private function addStyles() {
		static $done = false;

		if ( $done ) {
			return;
		}
		$done = true;
		add_action( 'wp_footer', [ $this, 'footer' ] );

	}

	/**
	 * Handles the woocommerce action hook at the start of displaying a shop product
	 *
	 * @since   2.0.1
	 * @updated 2.0.3
	 */
	public function start_catalog_item() {
		$this->addStyles();
		$this->single_product = false;
		$this->load_wc_product();
	}



	/**
	 * Hook to determine whether the product being output is set up for Wysiwyg Custom Products
	 * Returns modified Html with the appropriate SVG image if it is
	 *
	 * @param string $html              The post thumbnail HTML.
	 * @param int    $post_thumbnail_id The post thumbnail ID.
	 *
	 * @return string Possibly modified $html
	 *
	 * @since   1.1.7
	 * @updated 2.0.0
	 */
	public function single_image(
		$html,
		$post_thumbnail_id
	): string {
		if ( ! ($this->single_product && $this->wcpProduct->isMine()) ) {
			return $html;
		}

		$h   = new Wp_Html_Helper( Html_Helper::BUILD_HTML );
		$div = $h->extract_tags( $html, 'div' );
		$a   = $h->extract_tags( $html, 'a' );

		$img = $this->product_image(
			$a[0]['contents'],
			get_the_ID(),
			$post_thumbnail_id,
			'shop_single',
			'' );

		$h->o_div( '', '', $div[0]['attributes'] );
				$h->o_tag( 'a', '', '', $a[0]['attributes'] );
				$h->suffix_html( $img );
				$h->c_tag( 'a' );
		$h->c_div();
		$html = $h->get_html();
		$this->single_product = false;  // Only want to do the first product image, not the gallery
		return $html;
	}

	/**
	 * Hook to determine whether the product being output is set up for Wysiwyg Custom Products
	 * Returns modified Html with the appropriate SVG image if it is
	 *
	 * @param string       $html              The post thumbnail HTML.
	 * @param \WC_Product  $product           The WC product.
	 * @param string|array $size              The post thumbnail size. Image size or array of width and height
	 *                                        values (in that order). Default 'post-thumbnail'.
	 * @param array        $attr              Image attributes.
	 * @param bool         $placeholder       True to return $placeholder if no image is found, or false to return an empty string.
	 *
	 * @return string Possibly modified $html
	 *
	 * @since   1.1.10
	 * @updated 2.0.0
	 */
	public function new_catalog_thumbnail(
		$html,
		$product,
		$size,
		$attr,
		/** @noinspection PhpUnusedParameterInspection */
		$placeholder
	): string {

		return $this->product_image( $html,
		                             $product->get_id(),
		                             $product->get_image_id( 'info' ),
		                             $size,
		                             $attr );
	}

	/**
	 * Hook to determine whether the product being output is set up for Wysiwyg Custom Products
	 * Returns modified Html with the appropriate SVG image if it is
	 *
	 * @param string       $html              The post thumbnail HTML.
	 * @param int          $post_id           The post ID.
	 * @param string       $post_thumbnail_id The post thumbnail ID.
	 * @param string|array $size              The post thumbnail size. Image size or array of width and height
	 *                                        values (in that order). Default 'post-thumbnail'.
	 * @param string|array $attr              Query string of attributes.
	 *
	 * @return string Possibly modified $html
	 *
	 * @since   1.0.0
	 * @updated 2.0.4
	 */
	public function product_image(
		$html,
		$post_id,
		$post_thumbnail_id,
		$size,
		$attr
	): string {

		if ( ! isset( $this->wcpProduct ) ) {
			$this->start_single();
		}

		$product = $this->wcpProduct;
		if ( ! $product->isMine() ) {
			return $html;
		}

		if ( is_array( $size )
		     || ( ( false === stripos( self::SINGLE_SIZES, $size . ';' ) )
		          && ( false === stripos( self::CATALOG_SIZES, $size . ';' ) ) ) ) {
			return $html;
		}
		$this->size = $size;

		$layout               = $product->getLayout();
		$this->wp_html_helper = new Wp_Html_Helper( Html_Helper::BUILD_HTML );
		$this->parse_image( $html );
		$layout->set_size( $this->height, $this->width );
		$layout->image = $post_thumbnail_id;

		$lines = get_post_meta( $post_id, 'specific_lines', true );

		/* Add stylesheet for SVGs and fonts */
		register_style( 'frontend', [], '2.0.1' );
		wp_enqueue_script( 'jquery' );

		register_frontend_script( 'catalog', [ 'jquery' ], '2.1.0' ); // for catalog images, whether on product page or in catalog

		if ( ! isset( $this->svgInfo ) ) {
			$this->svgInfo = [
				'fontFiddle'     => $this->fontFiddle,
				'textContainer'  => $this->textTag,
				'textAttributes' => [ 'class', 'transform' ],
			];

			if ( $this->useTspan ) {
				$this->svgInfo['containerAttributes'] = [ 'class', 'font-size', 'text-anchor' ];

			} else {
				$this->svgInfo['containerAttributes'] = [ 'class', 'href', 'font-size', 'text-anchor', 'startOffset' ];
			}
			$script = 'const wcpp_svgInfo = ' . json_encode( $this->svgInfo ) . '; ';
			add_inline_script( 'catalog', $script, 'before' );
		}

		$this->single_product = $this->single_product || ( false !== stripos( self::SINGLE_SIZES, $size . ';' ) );
		if ( $this->single_product ) {
			/* Add live update javascript */
			register_frontend_script( 'attrchange', [ 'jquery' ], '1.0' );
			register_frontend_script( 'line', [ 'jquery', [ 'catalog' ] ], '2.1.0' );
			register_frontend_script( 'line-manager', [ 'jquery', [ 'catalog', 'line' ] ], '2.0.1' );
			register_frontend_script( 'frontend', [ 'jquery', [ 'attrchange', 'catalog', 'line', 'line-manager' ] ], '2.0.4' );


			/* Construct the html - uses modified image to monitor for changes*/
			$newHtml = $this->modifiedImageHtml . $this->product_shop_single_html( $post_id, $lines );

			return apply_filters( 'frontend_shop_single', $newHtml, $post_id, $post_thumbnail_id, $size, $attr );
		}

		if ( false !== stripos( self::CATALOG_SIZES, $size . ';' ) ) {
			$newHtml = $this->product_shop_catalog_html( $html, $post_id );

			return apply_filters( 'frontend_shop_catalog', $newHtml, $post_id, $post_thumbnail_id, $size, $attr );
		}

		return $html;
	}


	/**
	 * Outputs the path and effects <defs> in the footer of the page
	 *
	 * @since   1.2.0
	 * @updated 2.0.0
	 */
	public function footer() {
		echo TextPath::getHtmlPathDefs();
	}

	/**
	 * Sets a catalog line's alignment and path attributes
	 *
	 * @param string $alignment
	 * @param string $textpath
	 *
	 * @return array
	 *
	 * @since   2.0.0
	 * @updated 2.0.0
	 */
	private function getAttributes( $alignment, $textpath = '' ): array {
		switch ( $alignment ) {
			case 'l':
			case 'L':
				$align  = 'start';
				$offset = '0%';
				break;
			case 'r':
			case 'R':
				$align  = 'end';
				$offset = '100%';
				break;
			default:
				$align  = 'middle';
				$offset = '50%';
		}

		if ( $this->useTspan ) {
			return [
				'text-anchor' => $align,
			];
		}

		$attributes = [
			'text-anchor' => $align,
			'startOffset' => $offset,
		];

		if ( '' !== $textpath ) {
			$attributes['href'] = "#$textpath";
		}

		return $attributes;
	}

	/**
	 * Parses supplied image.
	 *
	 * Creates modified image tag so that it does not show. That can be used to monitor for WooCommerce image
	 * changes.
	 *
	 * @param string $html
	 *
	 * @since   1.0.0
	 * @updated 2.0.1
	 */
	private function parse_image( $html ) {
		$h = $this->wp_html_helper;

		$source = $h->extract_tags( $html, 'img' )[0]['attributes'];
			$source['class'] .= ' wcp-hidden';
		// Rebuild image tag with 0 height and width and wcp-hidden class
		$h->img( $source['src'], 0, 0, $source['alt'], $source['class'], 'wcp_product_image', [ 'title' => maybe_get( $source, 'title', '' ) ] );

		$height       = maybe_get( $source, 'height', 600 ); // Default if not set
		$this->height = is_numeric( $height ) ? $height : 600; // Default if non numeric

		$width                   = maybe_get( $source, 'width', 600 );
		$this->width             = is_numeric( $width ) ? $width : 600;
		$this->modifiedImageHtml = $h->get_html();
	}

	/**
	 * Product SVG start HTML
	 *
	 * @param array $data
	 *
	 * @since   2.0.0
	 * @updated 2.0.6
	 *
	 */
	private function svg_start( $data = [] ) {
		$h = $this->wp_html_helper;
		$p = $this->wcpProduct;
		$l = $p->getLayout();

		$classes = 'svg';
		if ( $this->single_product ) {
			$divId  = 'front_svg';
			$textId = 'svg_image_text';
			$svgId  = 'main_svg';
		} else {
			$divId   = '';
			$textId = '';
			$svgId = '';
			$classes .= ' wcp-catalog';
		}
		$h->o_div( $classes, $divId );

		$h->o_svg( $l->width, $l->height, $divId, $svgId, [ 'height' => '180%' ] );
		$h->o_tag( 'g', 'wcp' );
		$h->o_tag( 'g', '', $divId ? 'svg_image_group' : '' );

		$h->suffix_html( $p->getFrontendImage( 'main', $this->size, $this->single_product ? 'svg_image' : '' ) );
		$h->c_tag( 'g' );

		$h->o_tag( 'g',
		           'svg-text',
		           $textId,
		           [
			           'transform'         => $l->getScale(),
			           'dominant-baseline' => $this->dominantBaseline,
			           'data'              => $data,
		           ]
		);
	}

	/**
	 *
	 * Product SVG end HTML
	 *
	 * @since   2.0.0
	 * @updated 2.0.1
	 *
	 */
	private function svg_end() {
		$h = $this->wp_html_helper;
		$h->c_tag( 'g g' );
		if ( $this->single_product ) {
			$textpaths = TextPath::getHtmlPathDefs( false );
			$h->suffix_html( $textpaths );
			$h->c_tag( 'svg' );

		} else {
			$h->c_tag( 'svg' );
		}
		$h->c_div( 0 ); // Close divs as required
	}

	/**
	 * Builds SVG html in $this->h
	 *
	 * @param array      $liveLines
	 * @param array|null $message
	 *
	 * @since    1.0.0
	 * @updated  2.0.3
	 */
	private function svg_html( $liveLines, $message = null ) {
		$h = $this->wp_html_helper;

		$i = 0;
		foreach ( $liveLines as $line ) {
			if ( $this->single_product ) {
				list( $gClass, $gAttributes, $gid ) = $this->get_single_product_g_parameters( $i );
				list( $classes, $attributes, $text, $location ) = $this->get_single_product_parameters( $line, $i );
			} else {
				list( $gClass, $gAttributes, $gid ) = $this->get_catalog_product_g_parameters( $line );
				list( $classes, $attributes, $text, $location ) = $this->get_catalog_product_parameters( $line, $i, $message );
			}

			$h->o_tag( 'g', $gClass, $gid, $gAttributes );
			$this->product_line( $classes, $location, $attributes, $text );
			$h->c_tag( 'g' );
			$i ++;
		}

	}

	/**
	 * Sets up parameters for the <g> element for a single product
	 *
	 * @param $line_number
	 *
	 * @return array  $gClass,, $gAttributes, $gid
	 *
	 * @since   2.0.3
	 * @updated 2.0.3
	 *
	 */
	private function get_single_product_g_parameters( $line_number ) {
		return [ 'wcp-line', [], "wcp-line$line_number" ];

	}

	/**
	 * Sets up parameters for the <text> and <textpath> elements for a single product
	 *
	 * @param $line
	 * @param $line_number
	 *
	 * @return array  $gClass, $classes, $attributes, $text, $location, $gAttributes, $gid
	 *
	 * @since   2.0.3
	 * @updated 2.0.3
	 *
	 */
	private function get_single_product_parameters( $line, $line_number ) {
		$classes   = [];
		$classes[] = "wcp-text-$line_number";
		return [ $classes, [ 'stroke-width' => 0 ], '', [] ];

	}

	/**
	 * Sets up parameters for the <g> element for a catalog product
	 *
	 * @param $line
	 *
	 * @return array  $gClass,  $gAttributes, $gid
	 *
	 * @since   2.0.3
	 * @updated 2.0.3
	 */
	private function get_catalog_product_g_parameters( $line ) {

		$data = [
			'min-font'  => $line['MinFont'],
			'max-font'  => $line['MaxFont'],
			'max-width' => $line['Width'],
			'path'      => $line['Path'],
		];

		if ( $this->fontFiddle ) {
			$data['nominal-x'] = $this->useTspan ? self::getLineX( $line ) : $line['X'];
			$data['nominal-y'] = $line['Y'];
		}

		$gAttributes = [
			'font-size'  => $line['MinFont'],
			'visibility' => 'hidden',
			'data'       => $data,
		];

		return [ 'wcp-catalog-line', $gAttributes, '' ];

	}

	/**
	 * Sets up parameters for the <text> and <textpath> elements for a catalog product
	 *
	 * @param $line
	 * @param $line_number
	 *
	 * @return array  $classes, $attributes, $text, $location
	 *
	 * @since   2.0.3
	 * @updated 2.0.3
	 *
	 */
	private function get_catalog_product_parameters( $line, $line_number, $message ) {
		$classes = [];

		$attributes = $this->getAttributes( $line['Align'], $line['Path'] );
		$text       = isset( $message[ $line_number ] ) ? trim( $message[ $line_number ] ) : '';
		$x          = $this->useTspan ? self::getLineX( $line ) : $line['X'];

		$location['transform'] = sprintf( "translate(%s %s)", $x, $line['Y'] );

		return [ $classes, $attributes, $text, $location ];

	}

	/**
	 * @param array  $classes_array
	 * @param array  $location
	 * @param array  $attributes
	 * @param string $text
	 *
	 * @since   2.0.0
	 * @updated 2.1.0
	 */
	private function product_line( $classes_array, $location, $attributes, $text ) {
		$h = $this->wp_html_helper;

		$classes = implode( ' ', $classes_array );

			$h->o_tag( 'text', "$classes wcp-transform wcp-source-text", '', $location );
			$h->tag( $this->textTag,
			         $text,
			         "$classes wcp-text wcp-alignment wcp-path visible-path",
			         '',
			         $attributes );
			$h->c_tag( 'text' );

		if ( $this->single_product ) {
			$h->o_tag( 'text' );
			// add measurement textpath
			$h->tag( $this->textTag,
			         '',
			         'wcp-path',
			         '',
			         [
				         'visibility' => 'hidden',
			         ]
			);
			$h->c_tag( 'text' );
		}

	}

	/**
	 * Creates the SVG html when this is the product being browsed by the customer
	 *
	 * @param int $post_id The post ID.
	 * @param int $lines   specific number of format lines.
	 *
	 * @return string New $html
	 *
	 *
	 * @since   1.0.0
	 * @updated 1.2.6
	 */
	private function product_shop_single_html( $post_id, $lines ): string {
		$h = $this->wp_html_helper;
		$l = $this->wcpProduct->getLayout();

		$formats    = [];
		$liveUpdate = false;
		$wcpLines   = $l->formats;
		if ( $lines ) {
			$liveUpdate = maybe_get( $wcpLines, 'Lines' . $lines );
		}

		if ( $liveUpdate ) {
			$line      = [ 'l' => $lines, 'f' => $l->compact_format( $lines ) ];
			$formats[] = $line;
		} else {
			for ( $i = 1; $i <= $l->maxLines; $i ++ ) {
				$multiLine = maybe_get( $wcpLines, 'Lines' . $i );
				if ( $multiLine ) {
					$line       = [ 'l' => $i, 'f' => $l->compact_format( $i ) ];
					$liveUpdate = $multiLine;
					$formats[]  = $line;
				}
			}
		}

		$productText = htmlspecialchars_decode( get_post_meta( $post_id, 'product_text', true ), ENT_QUOTES );

		$message = $productText ? explode( '|', $productText ) : false;

		$data = [
			'formats' => json_encode( $formats ),
		];
		$this->svg_start( $data );

		$this->svg_html( $liveUpdate, $message );
		$this->svg_end();

		if ( $this->fontFiddle && ( 'yes' === get_option( 'settings', 'no', 'display_ie_msg' ) ) ) {
			$h->tag( 'p', $this->wcpProduct->getLayout()
			                               ->getIeMessage(), 'wcp-lost-text', 'wcp_ie_msg' );
		}

		$h->tag( 'p', $l->getMultiLineReformatMsg(), 'wcp-hidden wcp-lost-text', 'wcp_multiline' );
		$h->tag( 'p', $l->getNumberOfLinesMsg(), 'wcp-hidden wcp-lost-text', 'wcp_too_many_lines' );
		$h->tag( 'p', $l->getSingleLineReformatMsg(), 'wcp-hidden wcp-lost-text', 'wcp_single' );

		return $h->get_html();
	}


	/**
	 * Creates the SVG html for any products being shown in the catalog
	 *
	 * @param string $html    The post thumbnail HTML.
	 * @param int    $post_id The post ID.
	 *
	 * @return string New $html
	 *
	 * @since   1.0.0
	 * @updated 1.2.0
	 */
	private function product_shop_catalog_html( $html, $post_id ): string {
		$catalogText = htmlspecialchars_decode( get_post_meta( $post_id, 'catalog_text', true ), ENT_QUOTES );

		if ( empty( $catalogText ) ) {
			/** @noinspection NullPointerExceptionInspection */
			$catalogText    = wc_get_product( $post_id )->get_title();
			$lineSeparators = ' ';
		} else {
			// Handle separated by \r \r\n \n\r or \n
			str_replace( "\r", "\n", $catalogText );
			str_replace( "\n\n", "\n", $catalogText );
			$lineSeparators = "\n";
		}

		if ( '---' !== $catalogText ) {
			$catalogText = explode( $lineSeparators, $catalogText );

			do {
				$liveUpdate = maybe_get( $this->wcpProduct->getLayout()->formats, 'Lines' . count( $catalogText ) );
				if ( ! $liveUpdate ) {
					array_pop( $catalogText );
				}
			} while ( ! $liveUpdate && ( count( $catalogText ) > 0 ) );

			if ( $liveUpdate ) {
				$this->svg_start();
				$this->svg_html( $liveUpdate, $catalogText );
				$this->svg_end();

				return $this->wp_html_helper->get_html();
			}
		}

		return $html;
	}

}


global $frontend;
$frontend = new Frontend();
