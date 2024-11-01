<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 19/08/16
 * Time: 11:20 AM
 *
 * @since      1.2.0
 * @updated    2.0.1
 */

namespace WCP;

use Exception;
use function count;
use function current;
use function defined;

defined( 'ABSPATH' ) || exit;

/**
 * Class Settings Layout
 *
 * Administration page for manipulating layouts
 *
 * @package    WCP
 * @subpackage Admin
 *
 * @since      1.2.0
 * @updated    2.0.1
 */
class Settings_Layout extends Settings {

	//<editor-fold desc="Fields">

	/**
	 * @var array
	 */
	private $layouts;

	/**
	 * @var string
	 */
	private $layoutName;

	/**
	 * @var Layout
	 */
	private $layout;

	/**
	 * @var array
	 */
	private $numberOfLines;
	//</editor-fold>

	/**
	 * Displays the layout operations panel
	 *
	 * @since    1.1.0 Refactored
	 * @updated  1.1.2
	 */
	public function layout_operations_meta_box() {
		$htmlBuild = $this->htmlBuild;
		$htmlEcho  = $this->htmlEcho;

		$htmlEcho->o_div( 'woocommerce_options_panel' );
		$htmlEcho->pushDoEscape( false );

		// TRANSLATORS: Layout selection label on the settings page
		$htmlBuild->sel( 'layouts', $this->layouts, __( 'Choose layout', 'wysiwyg-custom-products' ), $this->layoutName );
		// TRANSLATORS: delete layout prompt text
		$htmlBuild->tag( 'span', __( 'Delete layout', 'wysiwyg-custom-products' ), 'wcp-link' . ( count( $this->layouts ) < 2 ? ' hidden' : '' ),
		                 'wcp_delete' );
		$htmlEcho->tag( 'p', $htmlBuild->get_html(), 'form-field' );

		// TRANSLATORS: label for the new name field when a layout is renamed or copied to a new name
		$htmlBuild->text( 'new_name', __( 'New name', 'wysiwyg-custom-products' ) );
		$htmlEcho->tag( 'p', $htmlBuild->get_html(), 'form-field' );

		$htmlBuild->tag( 'span',
			// TRANSLATORS: copy layout button text
			             __( 'Copy to new name', 'wysiwyg-custom-products' ),
		                 'button button-primary button-large',
		                 'wcp_copy' );

		// TRANSLATORS: rename layout prompt text
		$htmlBuild->tag( 'span', __( 'Change name', 'wysiwyg-custom-products' ), 'wcp-link', 'wcp_rename' );
		$htmlEcho->tag( 'p', $htmlBuild->get_html(), 'form-field' );


		$htmlEcho->popDoEscape();
		$htmlEcho->c_div();
	}

	/**
	 * Displays the line formatting panel
	 *
	 * @since    1.1.0 Refactored
	 * @updated  2.0.0
	 */
	public function line_format_meta_box() {

		$htmlBuild  = $this->htmlBuild;
		$htmlEcho   = $this->htmlEcho;
		$htmlReturn = $this->htmlReturn;

		$htmlEcho->o_div( 'woocommerce_options_panel' );
		$htmlEcho->pushDoEscape( false );

		$alignments = [
			// TRANSLATORS: Center aligned text
			'C' => __( 'Center', 'wysiwyg-custom-products' ),
			// TRANSLATORS: Left aligned text
			'L' => __( 'Left', 'wysiwyg-custom-products' ),
			// TRANSLATORS: Right aligned text
			'R' => __( 'Right', 'wysiwyg-custom-products' ),
		];

		$htmlBuild->sel( 'max_lines',
		                 $this->numberOfLines,
			// TRANSLATORS: prompt for maximum number of lines in layout
			             __( 'Maximum possible lines', 'wysiwyg-custom-products' ),
			             $this->layout->maxLines,
			             Html_Helper::VALUE_IS_ONE_BASED
		);

		$htmlBuild->tag( 'span',
			// TRANSLATORS: Update layout button text
			             __( 'Update', 'wysiwyg-custom-products' ),
		                 'button button-primary button-large right disabled',
		                 'wcp_save' );
		$htmlEcho->tag( 'p', $htmlBuild->get_html(), 'form-field' );

		$currentLines = $this->layout->currentLines;
		$htmlBuild->sel( 'current_lines',
		                 $this->numberOfLines,
			// TRANSLATORS: Prompt for choosing how many lines to format
			             __( 'Format for', 'wysiwyg-custom-products' ),
			             $currentLines,
			             Html_Helper::VALUE_IS_ONE_BASED
		);

		$htmlBuild->tag( 'span',
			// TRANSLATORS: cancel modifications link
			             __( 'Cancel changes', 'wysiwyg-custom-products' ),
		                 'wcp-link right disabled',
		                 'wcp_cancel' );
		$htmlEcho->tag( 'p', $htmlBuild->get_html(), 'form-field' );

		// TRANSLATORS: Prompt for choosing the font size to work with
		$htmlBuild->lbl( 'current_font', __( 'Choose font size', 'wysiwyg-custom-products' ) );
		$htmlBuild->rb( 'current_font', [
			// TRANSLATORS: Minimum font label used on settings page
			__( 'Min Font', 'wysiwyg-custom-products' ) => 'MinFont',
			// TRANSLATORS: Maximum font label used on settings page
			__( 'Max Font', 'wysiwyg-custom-products' ) => 'MaxFont',
		],
		                'MaxFont', '', Html_Helper::LABEL_TO_VALUE );
		$htmlEcho->tag( 'p', $htmlBuild->get_html(), 'form-field' );

		$htmlEcho->o_tag( 'table', 'hidden', 'line_formats' );

		$headerAttributes = [ 'width' => '17%' ];
		$htmlEcho->o_tag( 'tr' );

		$htmlEcho->th( 'Y', 'c', '', '', $headerAttributes );
		$htmlEcho->th( 'X', 'c', '', '', $headerAttributes );
		// TRANSLATORS: Text alignment table heading
		$htmlEcho->th( __( 'Align', 'wysiwyg-custom-products' ), 'c', '', '', $headerAttributes );
		// TRANSLATORS: Field width table heading
		$htmlEcho->th( __( 'Width', 'wysiwyg-custom-products' ), 'c', '', '', $headerAttributes );
		$htmlEcho->th( __( 'Min Font', 'wysiwyg-custom-products' ), 'c', '', '', $headerAttributes );
		$htmlEcho->th( __( 'Max Font', 'wysiwyg-custom-products' ), 'c', '', '', $headerAttributes );

		$htmlEcho->c_tag( 'tr' );

		$htmlEcho->pushDoEscape( false ); // Don't want the pre-escaped inner controls to be escaped into html data for the table
		for ( $i = 0; $i < Layout::MAX_LINES; $i ++ ) {
			$hidden = $i < $currentLines ? '' : ' hidden';
			$htmlEcho->tr( [
				               // Y
				               $htmlReturn->number( null, null, 0, 's', [ 'min' => 0, 'max' => $this->layout->height, 'step' => 1 ] ),
				               // X
				               $htmlReturn->number( null, null, 0, 's', [ 'min' => 0, 'max' => $this->layout->width, 'step' => 1 ] ),
				               // Align
				               $htmlReturn->sel( null, $alignments, null, 'C', Html_Helper::VALUE_TO_LABEL ),
				               // Width
				               $htmlReturn->number( null, null, 0, 's', [ 'min' => 0, 'max' => $this->layout->width, 'step' => 1 ] ),
				               // MinFont
				               $htmlReturn->number( null, null, 0, 's', [
					               'min'  => Layout::MIN_FONT_SIZE,
					               'max'  => intdiv( $this->layout->height, 2 ),
					               'step' => 1,
				               ] ),
				               // MaxFont
				               $htmlReturn->number( null, null, 0, 's', [
					               'min'  => Layout::MIN_FONT_SIZE,
					               'max'  => intdiv( $this->layout->height, 2 ),
					               'step' => 1,
				               ] ),
			               ],
			               'd',
			               '',
			               'format-line' . ( 0 === $i ? ' wcp-highlight' : $hidden ) );
		}

		$htmlEcho->tr( [
			               // TRANSLATORS: Heading in table to let user know checkboxes are used to make the fields identical for all lines
			               esc_attr__( 'Make same', 'wysiwyg-custom-products' ),
			               // line heading Not escaped by the wp-html-helper, so do it here
			               $htmlReturn->cbx( 'x-same' ),
			               $htmlReturn->cbx( 'align-same' ),
			               $htmlReturn->cbx( 'width-same' ),
			               $htmlReturn->cbx( 'min-font-same' ),
			               $htmlReturn->cbx( 'max-font-same' ),
		               ],
		               [ 'h' ], // heading for first cell only
		               'c', // center
		               'same-size' );

		$htmlEcho->popDoEscape();
		$htmlEcho->popDoEscape(); // Back to escaping

		$htmlEcho->c_tag( 'table' );
		$htmlEcho->c_div();
	}


	/**
	 * Displays the error messages panel
	 *
	 * @since    1.1.0 Refactored
	 * @updated  1.2.6
	 */
	public function messages_meta_box() {
		$htmlBuild = $this->htmlBuild;
		$htmlEcho  = $this->htmlEcho;

		$htmlEcho->o_div( 'woocommerce_options_panel' );
		$htmlEcho->pushDoEscape( false );

		// TRANSLATORS: Error message when a customer tries to put too much text on a line when they can use more lines
		$htmlBuild->text_area( 'multiline_msg', 3, 40, __( 'Text too wide - paragraph', 'wysiwyg-custom-products' ), $this->layout->getMultiLineReformatMsg(), 'overflow-message' );
		$htmlEcho->tag( 'p', $htmlBuild->get_html(), 'form-field' );

		// TRANSLATORS: Error message when a customer tries to have too many lines for the current layout
		$htmlBuild->text_area( 'too_many_lines_msg', 3, 40, __( 'Too many lines', 'wysiwyg-custom-products' ), $this->layout->getNumberOfLinesMsg(), 'overflow-message' );
		$htmlEcho->tag( 'p', $htmlBuild->get_html(), 'form-field' );

		// TRANSLATORS: Error message when a customer tries to put too much text on a line when they can only use one line
		$htmlBuild->text_area( 'singleline_msg', 3, 40, __( 'Text too wide - text field', 'wysiwyg-custom-products' ), $this->layout->getSingleLineReformatMsg(),
		                       'overflow-message' );
		$htmlEcho->tag( 'p', $htmlBuild->get_html(), 'form-field' );

		// TRANSLATORS: Informational message when a customer uses a Microsoft internet explorer or edge browser
		$htmlBuild->text_area( 'ie_msg', 3, 40, __( 'Microsoft browsers', 'wysiwyg-custom-products' ), $this->layout->getIeMessage(), 'overflow-message' );
		$htmlEcho->tag( 'p', $htmlBuild->get_html(), 'form-field' );

		$htmlEcho->popDoEscape();
		$htmlEcho->c_div();
	}


	/**
	 * Displays the color selection panel
	 *
	 * @since   1.1.1
	 */
	public function color_picker_meta_box() {
		$htmlBuild = $this->htmlBuild;
		$htmlEcho  = $this->htmlEcho;

		$htmlEcho->o_div( 'woocommerce_options_panel' );
		$htmlEcho->pushDoEscape( false );

		$htmlBuild->text( 'ink_color',
			// TRANSLATORS: Color of font in use
			              __( 'Font Color', 'wysiwyg-custom-products' ),
			              $this->layout->getColorString( 'ink' ),
		                  'color-picker' );
		$htmlEcho->tag( 'p', $htmlBuild->get_html(), 'form-field' );

		$htmlBuild->text( 'sizing_color',
			// TRANSLATORS: Colour of rectangles being used to size min or max font as selected
			              __( 'Current Font Box', 'wysiwyg-custom-products' ),
			              $this->layout->getColorString( 'size' ),
		                  'color-picker' );
		$htmlEcho->tag( 'p', $htmlBuild->get_html(), 'form-field' );

		$htmlBuild->text( 'non_sizing_color',
			// TRANSLATORS: Colour of rectangles being used indicate non selected font
			              __( 'Non Selected Box', 'wysiwyg-custom-products' ),
			              $this->layout->getColorString( 'non-size' ),
		                  'color-picker' );
		$htmlEcho->tag( 'p', $htmlBuild->get_html(), 'form-field' );

		$htmlEcho->popDoEscape();
		$htmlEcho->c_div();
	}

	/**
	 * Creates layout (the only) tab content
	 *
	 * @since   1.0.0
	 * @updated 1.1.0
	 */
	public function display_tab() {
		$this->layout_image_section();
	}

	/**
	 * Define our metaboxes
	 *
	 * @since    1.1.0
	 * @updated  1.2.0
	 */
	public function add_meta_boxes() {
		$this->metaBoxes['wcp_layout_operations'] = [
			// TRANSLATORS: Heading for layout operations panel
			'title'    => __( 'Layout Operations', 'wysiwyg-custom-products' ) . $this->throbber,
			'function' => 'layout_operations_meta_box',
			'context'  => 'side',
		];

		$this->metaBoxes['wcp_line_format'] = [
			// TRANSLATORS: Heading for layout formatting panel
			'title'    => __( 'Format Layout', 'wysiwyg-custom-products' ) . $this->throbber,
			'function' => 'line_format_meta_box',
			'context'  => 'side',
		];

		$this->metaBoxes['wcp_error_messages'] = [
			// TRANSLATORS: Heading for end customer error messages panel
			'title'    => __( 'Customer Error Messages', 'wysiwyg-custom-products' ),
			'function' => 'messages_meta_box',
			'context'  => 'side',
		];

		$this->metaBoxes['wcp_colors'] = [
			// TRANSLATORS: Heading for layout color selection
			'title'    => __( 'Layout Colors', 'wysiwyg-custom-products' ),
			'function' => 'color_picker_meta_box',
			'context'  => 'normal',
		];

		parent::add_meta_boxes();
	}

	/**
	 * Add screen options
	 *
	 * @since        1.1.0
	 * @updated      1.2.0
	 * @noinspection PhpMissingParentCallCommonInspection
	 */
	public function add_options() {
		/* Add screen option: user can choose between 1 or 2 columns (default 2) */
		$this->screen->add_option( 'layout_columns', [ 'max' => 2, 'default' => 2 ] );
	}

	/**
	 * Add help information
	 *
	 * @since    1.1.0
	 * @updated  1.2.4
	 */
	public function add_help() {
		$this->helpTabs['wcp-overview-tab'] =
			[
				// TRANSLATORS: help tab label
				'title'   => __( 'Overview', 'wysiwyg-custom-products' ),
				'content' => [
					// TRANSLATORS: settings screen overview help text
					__( 'This screen enables you to create, modify and copy layouts for any products that need real-time customer previews.',
					    'wysiwyg-custom-products' ),
				],
			];

		$this->helpTabs['wcp-images-tab'] =
			[
				// TRANSLATORS: Image help tab label
				'title'   => __( 'Images', 'wysiwyg-custom-products' ),
				'content' => [
					// TRANSLATORS: help text about use of product image used on the layout
					__( 'The chosen product image is saved with the layout but is not used in the store. The same (or similar) product image should be set on each product that uses the layout.',
					    'wysiwyg-custom-products' ),
				],
			];

		$this->helpTabs['wcp-operations-tab'] =
			[
				// TRANSLATORS: help tab label
				'title'   => __( 'Layout Operations', 'wysiwyg-custom-products' ),
				'content' => [
					// TRANSLATORS: settings screen layout operations meta box help
					__( 'Save, copy, rename or delete layouts here.', 'wysiwyg-custom-products' ),
				],
			];

		$this->helpTabs['wcp-format-tab'] =
			[
				// TRANSLATORS: help tab label
				'title'   => __( 'Format Layout', 'wysiwyg-custom-products' ),
				'content' => [
					// TRANSLATORS: settings screen format layout meta box help - overview
					__( 'Change the number of lines available in a layout. For each line count specify the formatting of the lines.',
					    'wysiwyg-custom-products' ),
					// TRANSLATORS: format - use of font radio buttons
					__( 'Use the Max Font and Min Font radio buttons to change the view.',
					    'wysiwyg-custom-products' ),
					// TRANSLATORS: format - using make same check boxes
					__( 'If the "make same" checkboxes are ticked then that value will be set for all lines for the current number of lines.  When ticking an empty box, all of the lines are made the same as the highlighted line.',
					    'wysiwyg-custom-products' ),
					// TRANSLATORS: format - use of mouse
					__( 'The formats (width, font size, and position) can also be modified using the mouse.',
					    'wysiwyg-custom-products' ),
				],
			];

		$this->helpTabs['wcp-messages-tab'] =
			[
				// TRANSLATORS: Customer error messages help tab label
				'title'   => __( 'Customer Messages', 'wysiwyg-custom-products' ),
				'content' => [
					// TRANSLATORS: Customer error messages overview
					__( 'These are the messages shown to the customer when the text they type cannot be displayed using the applied format.  They can be set on a per layout basis.',
					    'wysiwyg-custom-products' ),
				],
			];
		$this->helpTabs['wcp-color-tab'] =
			[
				// TRANSLATORS: Layout color choices help tab label
				'title'   => __( 'Layout Colors', 'wysiwyg-custom-products' ),
				'content' => [
					// TRANSLATORS: settings screen color selection meta box help
					__( 'Here you can change the formatting colors used to suit the chosen image.',
					    'wysiwyg-custom-products' ),
				],
			];

		parent::add_help();
	}

	/**
	 * Sets everything up for use
	 *
	 * @since   1.1.0
	 * @updated 1.2.4
	 */
	public function initialise() {

		parent::initialise();

		$this->load_layouts();

		// Load additional scripts
		wp_enqueue_media();
		register_admin_script( 'interact', [], '1.0' );

				register_admin_script( 'line-manager', [ 'jquery' ], '1.1.10' );
				$this->dependencies = [ 'jquery', 'wp-color-picker', [ 'interact', 'line-manager' ] ];

		// Load additional styles
		register_style( 'frontend', [], '1.1' );
		register_style( 'settings', [], '1.2.4' );

		// Set up message arrays

		// TRANSLATORS: Used when selecting formats for multiple (>1) lines
		$lines               = ' ' . ucfirst( _n( 'line', 'lines', 2, 'wysiwyg-custom-products' ) );
		$this->numberOfLines = [
			// TRANSLATORS: Used when formatting just one line
			__( 'Single Line', 'wysiwyg-custom-products' ),
			// TRANSLATORS: Numbers up to 10
			__( 'Two', 'wysiwyg-custom-products' ) . $lines,
			__( 'Three', 'wysiwyg-custom-products' ) . $lines,
			__( 'Four', 'wysiwyg-custom-products' ) . $lines,
			__( 'Five', 'wysiwyg-custom-products' ) . $lines,
			__( 'Six', 'wysiwyg-custom-products' ) . $lines,
			__( 'Seven', 'wysiwyg-custom-products' ) . $lines,
			__( 'Eight', 'wysiwyg-custom-products' ) . $lines,
			__( 'Nine', 'wysiwyg-custom-products' ) . $lines,
			__( 'Ten', 'wysiwyg-custom-products' ) . $lines,
		];

		$this->messages['reducing_max_lines'] =
			// TRANSLATORS: warning message when making change to layout is potentially damaging
			__( 'Reducing maximum number of lines will cause loss of formatting information. Are you sure?',
			    'wysiwyg-custom-products' );

		$this->initialiseFinish( 'layout', '1.2.4' );


	}

	/**
	 * Image(s) and associated buttons for layout tab - half width
	 *
	 * @since   1.0.0
	 * @updated 1.1.0
	 */
	private function layout_image_section() {
		$htmlEcho = $this->htmlEcho;

		$this->display_image();

		$htmlEcho->o_div( 'image-operations' );
		$htmlEcho->o_tag( 'p', 'center' );
		// TRANSLATORS: prompt for selection of a product image to create a layout for
		$htmlEcho->tag( 'span', __( 'Choose Product Image', 'wysiwyg-custom-products' ), 'wcp-link wcp-browse-image',
		                'wcp_main_image',
		                [
			                'data' => [
				                // TRANSLATORS: media browser title when selecting product image
				                'uploader_title'       => __( 'Select layout product image', 'wysiwyg-custom-products' ),
				                // TRANSLATORS: media browser button text when selecting product image
				                'uploader_button_text' => __( 'Set Layout Image', 'wysiwyg-custom-products' ),
			                ],
		                ]
		);
		$htmlEcho->c_tag( 'p div' );
	}

	/**
	 * Creates the SVG image for the image portion of layout tab
	 *
	 * @since   1.0.0
	 * @updated 1.2.6
	 */
	private function display_image() {
		$htmlBuild = $this->htmlBuild;
		$layout    = $this->layout;

		$htmlBuild->o_div( 'wcp-image-message wcp-hidden', 'wcp_image_message' );
		// TRANSLATORS: Message shown when no layout image is shown - line 1
		$htmlBuild->tag( 'p',
		                 __( 'Click on the link below to load a product image here to assist with the formatting of the layout.', 'wysiwyg-custom-products' ),
		                 'wcp-message-line' );
		// TRANSLATORS: Message shown when no layout image is shown - line 2
		$htmlBuild->tag( 'p',
		                 __( 'The chosen image is saved with the layout but is not used in the store.', 'wysiwyg-custom-products' ),
		                 'wcp-message-line' );
		// TRANSLATORS: Message shown when no layout image is shown - line 3
		$htmlBuild->tag( 'p',
		                 __( 'The same (or similar) product image needs to be set on each product that uses the layout.', 'wysiwyg-custom-products' ),
		                 'wcp-message-line' );
		$htmlBuild->c_div();
		$htmlBuild->o_div( 'svg wcp', 'wcp_image_div' );
		$htmlBuild->o_svg( $layout->width, $layout->height, 'responsive', 'wcp_svg_image', [ 'height' => '180%' ] );

		$htmlBuild->o_tag( 'g' );
		$htmlBuild->o_tag( 'defs' );
		for ( $i = 0; $i < Layout::MAX_LINES; $i ++ ) {
			$htmlBuild->tag( 'path', '', '',
			                 "path$i" );
		}
		$htmlBuild->c_tag( 'defs' );

		$htmlBuild->svg_img( '', 0, 0, '', 'svg_background' );

		$htmlBuild->o_tag( 'text', '',
		                   'display_text',
		                   [ 'dominant-baseline' => 'middle' ],
		                   [ 'fill' => $layout->getColorString( 'ink' ) ] );
		for ( $i = 0; $i < Layout::MAX_LINES; $i ++ ) {
			$htmlBuild->tag( 'textpath', '', 'hidden wcp-line' . ( $i + 1 ),
			                 "tpath$i", [ 'href' => "#path$i" ] );
		}

		$htmlBuild->c_tag( 'text' );

		$htmlBuild->o_tag( 'g', '', 'non_size_rects', [], [
			'fill'         => $layout->getColorString( 'non-size' ),
			'fill-opacity' => 0.2,
		] );
		for ( $i = 0; $i < Layout::MAX_LINES; $i ++ ) {
			$htmlBuild->rect( 0, 0, 1, 1, [], 'hidden', "nonSizeRect$i" );
		}
		$htmlBuild->c_tag( 'g' );

		$htmlBuild->o_tag( 'g', '', 'size_rects', [], [
			'fill'           => $layout->getColorString( 'size' ),
			'fill-opacity'   => 0.3,
			'stroke-opacity' => 0.7,
		] );
		for ( $i = 0; $i < Layout::MAX_LINES; $i ++ ) {
			$htmlBuild->rect( 0, 0, 1, 1, [], 'resize-drag hidden', "rect$i" );
		}

		$htmlBuild->c_tag( 'g g svg div' );

		echo apply_filters( 'settings_svg_image', $htmlBuild->get_html() );
	}

	/**
	 * Initialise the layout section
	 *
	 * @since   1.1.0
	 * @updated 1.2.0
	 */
	private function load_layouts() {
		try {
			$layouts          = Layout::getLayouts();
			$this->layoutName = sanitize_text_field( get_option( 'settings', current( $layouts ), 'CurrentLayout' ) );
			$key              = array_search( $this->layoutName, $layouts, false );
			if ( false === $key ) {
				$this->layoutName = current( $layouts );
				update_option( 'settings', $this->layoutName, 'CurrentLayout' );
			}
		} catch ( Exception $e ) {
			$this->layoutName = 'template'; // Try the default
			$layouts          = [ 'template' ];
		}
		$this->layouts = $layouts;

		try {
			$this->layout = new Layout( $this->layoutName );
		} catch ( WCPException $e ) {
			$this->layout = new Layout( '__default__' ); // if all else fails, return a new template
		}
	}
}

global $wcpSettings;
$wcpSettings = new Settings_Layout();
