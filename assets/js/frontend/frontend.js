/**
 * Created by Dave on 2/08/16.
 *
 *
 * @since   1.0.0
 * @updated 2.0.6
 */

/* JSHint/JSLint set up */
// /*global attrchange */ // declared in attrchange.js
/* global wcpp_field_overrides */
/* global wcpp_preview_subdirectory */

//noinspection JSUnusedLocalSymbols,AssignmentResultUsedJS,JSLint
(function ($wcp, $, undefined) {
	"use strict";

	let lineManager;
	let lostTextMsg;
	let tooManyLines;
	let productImg;

	const textOperations   = "paste cut input";
	const selectOperations = "change";

	/**
	 * Hides or shows the lost text messages
	 *
	 * @since   1.0.0
	 * @updated 1.2.0
	 */
	function maybeShowLostText() {
		if (undefined !== lostTextMsg) {
			lostTextMsg.toggleClass("wcp-hidden", !lineManager.textTooLong());
			tooManyLines.toggleClass("wcp-hidden", !lineManager.tooManyLines);
		}
	}

	/**
	 * Called when a DOM element with an associated text is changed
	 *
	 * @param event
	 *
	 * @since   1.0.0
	 * @updated 1.2.0
	 */
	function textUpdate(event) {
		const text = $(event.currentTarget).val();
		lineManager.setText(text);
		maybeShowLostText();
	}

	/**
	 * Displays the lines of text in the associated texts
	 *
	 * @param textLines array of strings|false
	 *
	 * @since   1.0.0
	 * @updated 2.0.3
	 */
	function displayMessage(textLines) {
		lineManager.displayMessage(textLines);

		maybeShowLostText(); // Show any user error messages
	}


	/**
	 * Called when content of text area is changed
	 * Displays the text from a text area. Split into an array of lines.
	 *
	 * @param event
	 *
	 * @since 1.0.0
	 */
	function textMultiUpdate(event) {
		const message = $(event.currentTarget).val();
		displayMessage(message.split(/\r\n|\n|\r/g));  // LineBreaks -> array of lines
	}


	function updateImage(event) {
		const svgImageElement = $("#svg_image");
		if ("src" === event.attributeName) {
				svgImageElement.attr("xlink:href", event.newValue);
		}

	}





	/**
	 * Adds the input events to a textarea
	 *
	 * @param textAreas      Textarea or list of textareas
	 *
	 * @since   1.0.0
	 * @updated 1.1.9
	 */
	function setParagraph(textAreas) {
		let first;

		if (0 === textAreas.length) {
			return false;
		} else if (textAreas.length > 1) {
			first = $(textAreas.first());
		} else {
			first = textAreas;
		}

		if (!first.is("textarea")) {
			first = $(first.find("textarea").first());
		}

		first.on(textOperations, textMultiUpdate);
		textMultiUpdate({currentTarget: first[0]});

		return first;
	}
	/**
	 * Adds the input events to a text field
	 *
	 * @param textFields      Field or list of fields
	 *
	 * @since   1.0.0
	 * @updated 1.2.0
	 */
	function setTextField(textFields) {
		var input;

		if (0 === textFields.length) {
			return false;
		}  else if (textFields.length > 1) {
			input = $(textFields.first());
		} else {
			input = textFields;
		}

		if (!input.is("input[type=text]")) {
			input = $(input.find("input[type=text]").first());
		}

		input.on(textOperations, textUpdate);
		textUpdate({currentTarget: input[0]});

		return textFields.last();
	}


	function initialiseInputHandlers() {
		let last;
		let multiline;
		let i;
		let inputInitialiser;

		const cartForm       = $("form.cart");
		const inputSelection = [
			{context: null, selector: ".wcp-paragraph", initialiser: setParagraph, multiline: true},
			{context: null, selector: ".wcp-textarea", initialiser: setParagraph, multiline: true},
			{context: null, selector: ".wcp-single-line", initialiser: setTextField, multiline: false},
			{context: null, selector: ".wcp-textfield", initialiser: setTextField, multiline: false},
			{context: cartForm, selector: "textarea", initialiser: setParagraph, multiline: true},
			{context: cartForm, selector: "input[type=text]", initialiser: setTextField, multiline: false},
		];
		for (i = 0; (i < inputSelection.length) && !last; i++) {
			inputInitialiser = inputSelection[i];
			multiline        = inputInitialiser.multiline;
			if (inputInitialiser.context) {
				last = inputInitialiser.initialiser(inputInitialiser.context.find(inputInitialiser.selector));
			} else {
				last = inputInitialiser.initialiser($(inputInitialiser.selector));
			}
		}

		if (last) {  // Move message paragraphs to the appropriate location
			last = last.closest("div");
			$("p#wcp_too_many_lines").appendTo(last);
			if (multiline) {
				$("p#wcp_multiline").addClass("wcp-too-long").appendTo(last);
			} else {
				$("p#wcp_single").addClass("wcp-too-long").appendTo(last);
			}
		}
		lostTextMsg  = $(".wcp-too-long");
		tooManyLines = $("#wcp_too_many_lines");
	}


	/**
	 * Sets up all of the javascript actions and any other initialisation required
	 *
	 * @since   1.0.0
	 * @updated 2.0.6
	 */
	$wcp.initialise = function () {
		const svgImageText = $("#svg_image_text");

		productImg   = $("#wcp_product_image");

		lineManager = new $wcp.LineManager(svgImageText);

		initialiseInputHandlers();

		//noinspection JSUnresolvedFunction
		$(productImg).attrchange({
			trackValues: true, // enables tracking old and new values
			callback   : updateImage,
		});


	};

}(window["$wcp"] = window["$wcp"] || {}, jQuery));

/**
 * Loader function
 *
 * @since 1.0.0
 */
jQuery(document).ready(function () {
	"use strict";
	window["$wcp"].initialise();
});

