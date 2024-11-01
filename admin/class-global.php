<?php
/**
 * Created by PhpStorm.
 * User: Dave
 * Date: 19/08/16
 * Time: 11:20 AM
 *
 * @since   1.2.0
 * @updated 1.2.6
 */

namespace WCP;

use function defined;

defined( 'ABSPATH' ) || exit;

/**
 * Class Settings Global
 *
 * Administration page for setting global options
 *
 * @package    WCP
 * @subpackage Admin
 *
 * @since      1.2.0
 * @updated    1.2.0
 */
class Settings_Global extends Settings {


	/**
	 * Creates this tab content
	 *
	 * @since   1.0.0
	 * @updated 1.2.6
	 */
	public function display_tab() {
		$htmlEcho  = $this->htmlEcho;
		$htmlBuild = $this->htmlBuild;

		$htmlEcho->pushDoEscape( false );

		$htmlEcho->o_tag( 'table', 'form-table' );
		$htmlEcho->o_tag( 'tbody' );
		$htmlEcho->o_tag( 'tr', '', '', [ 'valign' => 'top' ] );

		$htmlEcho->th(
		// TRANSLATORS: Heading for whether settings should be removed from database when plugin is deleted
			__( 'Clean plugin delete', 'wysiwyg-custom-products' ),
			'', 'titledesc', '', [ 'scope' => 'row' ] );

		$cleanDelete = get_option( 'settings', 'yes', 'clean_delete' );
		$htmlBuild->cbx( 'save_on_delete',
		                 null,
		                 'no' !== $cleanDelete );

		$htmlBuild->nbsp( 5 );
		$htmlBuild->tag( 'span',
			// TRANSLATORS: Description about whether settings should be removed from database when plugin is deleted
			             __( 'Delete all plugin information (including associations with product) when the plugin is deleted. Clear if changing plugin versions.',
			                 'wysiwyg-custom-products' ) );
		$htmlEcho->td( $htmlBuild->get_html(), '', 'forminp forminp-checkbox' );
		$htmlEcho->c_tag( 'tr' );

		$htmlEcho->o_tag( 'tr', '', '', [ 'valign' => 'top' ] );
		$htmlEcho->th(
		// TRANSLATORS: Heading for whether Microsoft browser messages should be displayed to the customers
			__( 'Display IE message', 'wysiwyg-custom-products' ),
			'', 'titledesc', '', [ 'scope' => 'row' ] );

		$displayIeMsg = get_option( 'settings', 'no', 'display_ie_msg' );
		$htmlBuild->cbx( 'display_ie_msg',
		                 null,
		                 'no' !== $displayIeMsg );

		$htmlBuild->nbsp( 5 );
		$htmlBuild->tag( 'span',
			// TRANSLATORS: Description about whether Microsoft browser messages should be displayed to the customers
			             __( 'Display Microsoft browser (IE and Edge) SVG incompatibility messages on the customer entry product page.',
			                 'wysiwyg-custom-products' ) );
		$htmlEcho->td( $htmlBuild->get_html(), '', 'forminp forminp-checkbox' );
		$htmlEcho->c_tag( 'tr' );



		$htmlEcho->o_tag( 'tr', '', '', [ 'valign' => 'top' ] );

		$htmlEcho->th(
		// TRANSLATORS: Heading for the change font link.
			__( 'Edit USER.CSS', 'wysiwyg-custom-products' ),
			'', 'titledesc', '', [ 'scope' => 'row' ] );



		$htmlBuild->a( __( 'Change font', 'wysiwyg-custom-products' ),
		               admin_url() . 'plugin-editor.php'
		               . '?plugin=' . Wcp_Plugin::PLUGIN_NAME . '%2F' . Wcp_Plugin::PLUGIN_NAME . '.php'
		               . '&file=' . Wcp_Plugin::PLUGIN_NAME . '%2Fuser%2Ffonts.css',
		               'button button-primary' );
		$htmlBuild->tag( 'span',
		                 __( ' (affects all layouts and products.)', 'wysiwyg-custom-products' ) );

		$htmlEcho->td( $htmlBuild->get_html(), '', 'forminp' );

		$htmlEcho->c_tag( 'tr tbody table' );
		$htmlEcho->popDoEscape();
	}

	/**
	 * Add help information
	 *
	 * @since    1.2.0
	 * @updated  1.2.6
	 */
	public function add_help() {
		$this->helpTabs['wcp-global-tab'] =
			[ // TRANSLATORS: Global plugin options help tab title
			  'title'   => __( 'Global Plugin Options', 'wysiwyg-custom-products' ),
			  'content' => [
				  // TRANSLATORS: introduction to help text about global settings section
				  __( 'This section is for any options that are plugin (not layout) wide.', 'wysiwyg-custom-products' ),
				  // TRANSLATORS: help text clean plugin delete checkbox
				  __( 'Clean plugin delete: If this is checked (the default) *ALL* data associated with the plugin is removed when the plugin is deleted. If it is not checked, then the data in the database is retained. Useful if upgrading or re-installing.',
				      'wysiwyg-custom-products'
				  ),
				  // TRANSLATORS: Description about whether Microsoft browser messages should be displayed to the customers
				  __( 'Display IE message: If this is checked the Microsoft browser messages associated with the layouts are shown to the customer. If it is not checked, then the messages are not displayed.',
				      'wysiwyg-custom-products'
				  ),
				  __( 'Change font for all layouts and products by using link to edit stylesheet.',
				      'wysiwyg-custom-products' ),
			  ],
			];

		parent::add_help();
	}
	/**
	 * Define our metaboxes
	 *
	 * @since    1.2.0
	 * @updated  1.2.0
	 */
	public function add_meta_boxes() {
		$this->metaBoxes['wcp_promote_premium'] = [
			// TRANSLATORS: Heading for premium plugin promotion panel
			'title'    => __( 'Want more features?', 'wysiwyg-custom-products' ),
			'function' => 'promote_premium_meta_box',
			'context'  => 'side',
		];

		parent::add_meta_boxes();
	}
	/**
	 * Displays the premium promotion panel
	 *
	 * @since   1.2.0
	 * @updated 1.2.6
	 */
	public function promote_premium_meta_box() {
		$hE  = $this->htmlEcho;
		$hB  = $this->htmlBuild;
		$hE->o_div( 'woocommerce_options_panel' );
		// TRANSLATORS: Premium plugin features heading
		$hE->tag( 'p', __( 'WITH THE PREMIUM VERSION YOU CAN', 'wysiwyg-custom-products' ),'wcp-promo-heading' );
		$hE->o_tag( 'ul' );

		$hE->pushDoEscape( false );

		// TRANSLATORS: Premium plugin feature
		$hB->a( __( 'Use more than one text field', 'wysiwyg-custom-products' ),
		        'https://tazziedave.com/product-category/wysiwyg-demonstration/premium/multiple-fields/' );
		$hE->tag( 'li', $hB->get_html() );

		// TRANSLATORS: Premium plugin feature
		$hB->a( __( 'Use select/dropdown to choose messages', 'wysiwyg-custom-products' ),
		        'https://tazziedave.com/product-category/wysiwyg-demonstration/premium/select-dropdown/' );
		// TRANSLATORS: Premium plugin feature
		$hB->add_text( __( 'Single line or multiple lines in one go. Use multiple single lines choices to offer a range of messages.', 'wysiwyg-custom-products' ),
			' - ');
		$hE->tag( 'li', $hB->get_html() );

		// TRANSLATORS: Premium plugin feature
		$hB->a( __( 'Load different select/dropdown messages', 'wysiwyg-custom-products' ),
		        'https://tazziedave.com/product-category/wysiwyg-demonstration/premium/select-options-override/' );
		// TRANSLATORS: Premium plugin feature
		$hB->add_text( __( 'Use the same select(s) but the choices are defined product by product.',
		                   'wysiwyg-custom-products' ),
		               ' - ');
		$hE->tag( 'li', $hB->get_html() );

		// TRANSLATORS: Premium plugin feature
		$hB->a( __( 'Add overlay images on the product image', 'wysiwyg-custom-products' ),
		        'https://tazziedave.com/product-category/wysiwyg-demonstration/premium/overlays/' );
		// TRANSLATORS: Premium plugin feature
		$hB->add_text( __( 'Can reduce the number of product images you need to make.', 'wysiwyg-custom-products' ),
		               ' - ');
		$hE->tag( 'li', $hB->get_html() );

		// TRANSLATORS: Premium plugin feature
		$hB->a( __( 'Add background images to a product', 'wysiwyg-custom-products' ),
		        'https://tazziedave.com/product-category/wysiwyg-demonstration/premium/backgrounds/' );
		// TRANSLATORS: Premium plugin feature
		$hB->add_text( __( 'Make your products pop with different backgrounds.', 'wysiwyg-custom-products' ),
		               ' - ');
		$hE->tag( 'li', $hB->get_html() );

		// TRANSLATORS: Premium plugin feature
		$hB->a( __( 'Different fonts and/or colors', 'wysiwyg-custom-products' ),
		        'https://tazziedave.com/product-category/wysiwyg-demonstration/premium/styling/' );
		// TRANSLATORS: Premium plugin feature
		$hB->add_text( __( 'On a line by line basis for each layout.', 'wysiwyg-custom-products' ),
		               ' - ');
		$hE->tag( 'li', $hB->get_html() );

		// TRANSLATORS: Premium plugin feature
		$hB->a( __( 'Customer font and color choices', 'wysiwyg-custom-products' ),
		        'https://tazziedave.com/product-category/wysiwyg-demonstration/premium/customer-styling/' );
		// TRANSLATORS: Premium plugin feature
		$hB->add_text( __( 'Offer the choice to your customer as well!', 'wysiwyg-custom-products' ),
		               ' - ');
		$hE->tag( 'li', $hB->get_html() );

			// TRANSLATORS: Premium plugin feature
		$hB->a( __( 'Predefine part of the message', 'wysiwyg-custom-products' ),
		        'https://tazziedave.com/product-category/wysiwyg-demonstration/premium/fixed-text/' );
		// TRANSLATORS: Premium plugin feature
		$hB->add_text( __( 'Use the same layout and product images on totally different product types.', 'wysiwyg-custom-products' ),
		               ' - ');
		$hE->tag( 'li', $hB->get_html() );

		// TRANSLATORS: Premium plugin feature
		$hB->a( __( 'Modify form labels/captions', 'wysiwyg-custom-products' ),
		        'https://tazziedave.com/product-category/wysiwyg-demonstration/premium/modified-captions/' );
		// TRANSLATORS: Premium plugin feature
		$hB->add_text( __( 'Use the same input field(s), but change the captions to reflect the product', 'wysiwyg-custom-products' ),
		               ' - ');
		$hE->tag( 'li', $hB->get_html() );

		// TRANSLATORS: Premium plugin feature
		$hB->a( __( 'Resize text lines individually', 'wysiwyg-custom-products' ),
		        'https://tazziedave.com/product-category/wysiwyg-demonstration/premium/individual-lines/' );
		// TRANSLATORS: Premium plugin feature
		$hB->add_text( __( 'Either resize all text to smallest font or each line so it fits template.', 'wysiwyg-custom-products' ),
		               ' - ');
		$hE->tag( 'li', $hB->get_html() );

		// TRANSLATORS: Premium plugin feature
		$hB->add_text( __( 'Upgrades and support for one year', 'wysiwyg-custom-products' ) );
		$hE->tag( 'li', $hB->get_html() );

		// TRANSLATORS: Premium plugin price prefix
		$hB->a( __( 'All for only', 'wysiwyg-custom-products' ) . ' $50 NZD (New Zealand Dollars)',
		        'https://tazziedave.com/product/wysiwyg-custom-products-premium/' );
		$hE->tag( 'li', $hB->get_html() );

		$hE->c_tag( 'ul' );

		// TRANSLATORS: How to learn about the premium plugin
		$hB->add_text( __( 'Click on the above links to try out the feature or watch this', 'wysiwyg-custom-products' ), '', ' ');
		// TRANSLATORS: Premium plugin video title
		$hB->a( __( 'Demonstration Video', 'wysiwyg-custom-products' ),
		        'https://tazziedave.com/plugins/wysiwyg-custom-products-premium#Demonstration' );
		// TRANSLATORS: Premium plugin - more learning stuff
		$hB->add_text( __( 'to get a quick idea of how easy it is to use.', 'wysiwyg-custom-products' ), ' ' );
		$hE->tag( 'p', $hB->get_html(), 'wcp-promo-line' );

		$hE->popDoEscape();

		// TRANSLATORS: Professional plugin version advanced information
		$hE->tag( 'p', __( '** COMING SOON **', 'wysiwyg-custom-products' ),'wcp-promo-heading' );
		// TRANSLATORS: Professional plugin version advanced information
		$hE->tag( 'p', __( 'A Professional version', 'wysiwyg-custom-products' ),'wcp-promo-heading' );
		// TRANSLATORS: Professional plugin version advanced information
		$hE->tag( 'p', __( 'All of the premium features plus:', 'wysiwyg-custom-products' ), 'wcp-promo-line' );
		// TRANSLATORS: Professional plugin version advanced information
		$hE->tag( 'p', __( 'Added - SVG TextPaths.  Now text can go in any direction you can imagine! And it will still resize as the user types!', 'wysiwyg-custom-products' ), 'wcp-promo-line' );
		// TRANSLATORS: Professional plugin version advanced information
		$hE->tag( 'p', __( 'Added - SVG Filter Effects and Masks. Really make that text stand out! Or drop in to simulate an engraved effect!', 'wysiwyg-custom-products' ), 'wcp-promo-line' );
		// TRANSLATORS: Professional plugin price prefix
		$hE->tag( 'p',__( 'And still only', 'wysiwyg-custom-products' ) . ' $100 NZD (New Zealand Dollars)',
		          'wcp-promo-line' );

		$hE->c_div();
	}

	/**
	 * Add screen options
	 *
	 * @since        1.2.0
	 * @updated      1.2.0
	 * @noinspection PhpMissingParentCallCommonInspection
	 */
	public function add_options( ) {
		/* Add screen option: user can choose between 1 or 2 columns (default 2) */
		$this->screen->add_option( 'layout_columns', [ 'max' => 2, 'default' => 2 ] );
	}


	/**
	 * Sets everything up for use
	 *
	 * @since   1.2.0
	 * @updated 1.2.6
	 */
	public function initialise() {
		parent::initialise();
		$this->dependencies = [ 'jquery' ];
		register_style( 'settings', [], '1.2.4' );
		$this->initialiseFinish( 'global', '1.2.6', false );
	}
}

global $wcpSettings;
$wcpSettings = new Settings_Global();
