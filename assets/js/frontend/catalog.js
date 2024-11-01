/**
 * Created by Dave on 28/09/16.
 *
 *
 * @since   1.0.0
 * @updated 2.1.0
 */

/* JSHint/JSLint set up */
/* global wcpp_svgInfo */

//noinspection AssignmentResultUsedJS,JSUnusedLocalSymbols,JSLint
(function ($wcp, $, undefined) {
	"use strict";

	const fontFiddle        = wcpp_svgInfo["fontFiddle"];
	const containerSelector = wcpp_svgInfo["textContainer"];
	const isTextPath        = ("textPath" === containerSelector);

	/**
	 * Increases size of text to fit length of path/tspan
	 *
	 * @param line        {jQuery}   line group
	 *
	 * @since   1.2.0
	 * @updated 2.1.0
	 */
	function growText(line) {
		let length;
		let maxLength;
		const visibleText = getVisible(line);
		let setFont       = parseInt(visibleText.css("font-size"), 10);
		const maxFont     = parseInt(line.data("max-font"), 10);
		const measureText = visibleText[0];

		if (isTextPath) {
			//noinspection JSUnresolvedFunction
			maxLength = document.getElementById(line.data("path")).getTotalLength() * 0.9;
		} else {
			maxLength = parseInt(line.data("max-width"), 10);
		}

		//noinspection JSUnresolvedFunction
		length = measureText.getComputedTextLength();

		while ((setFont < maxFont) && (length < maxLength)) {
			setFont += 0.5;
			setFontSize(line, setFont);
			//noinspection JSUnresolvedFunction
			length = measureText.getComputedTextLength();
		}

		if (length > maxLength) {
			setFont -= 0.5;
			setFontSize(line, setFont);
		}

		return setFont;
	}

	/**
	 * Gets the visible textPath/tspan element in the line group
	 *
	 * @param line        {jQuery}   line group
	 *
	 * @since   2.1.0
	 * @updated 2.1.0
	 */
	function getVisible(line) {
		return line.find(containerSelector + ":last"); // :last Skips mask if it is present
	}

	/**
	 * Calculates the smallest font size across all texts. This is then the max font to use
	 *
	 * @param lines        {jQuery}   texts to be balanced
	 *
	 * @since 1.0.0
	 */
	function setMaxFont(lines) {
		let result           = growText($(lines[0]));
		let maxFont          = result;
		let AdjustmentNeeded = false;
		let i;

		for (i = 1; i < lines.length; i++) {
			result = growText($(lines[i]));
			if (maxFont !== result) {
				AdjustmentNeeded = true;
				maxFont          = Math.min(maxFont, result);
			}
		}

		if (AdjustmentNeeded) {
			return maxFont;
		} else {
			return false;
		}
	}

	/**
	 * Sets the font size and y attributes of the the text.
	 *
	 * @param line         {jQuery}    SVG text element being adjusted
	 * @param desiredSize   numeric     Font size wanted
	 *
	 * @since   1.0.0
	 * @updated 1.0.2
	 */
	function setFontSize(line, desiredSize) {
		const minFont  = parseInt(line.data("min-font"), 10);
		const maxFont  = parseInt(line.data("max-font"), 10);
		const fontSize = Math.max(Math.min(desiredSize, maxFont), minFont); // Make sure desired size doesn't bust
		// this text's font range

		line.attr("font-size", fontSize);

		if (fontFiddle) {
			catalogMiddleFiddle(line, fontSize);
		}
	}

	/**
	 * Adjusts y for middle baseline
	 *
	 * @since   1.2.0
	 * @updated 2.0.0
	 */
	function catalogMiddleFiddle(line, fontSize) {
		const visible = getVisible(line);
		const style   = getComputedStyle(visible[0]);

		let y = parseInt(line.data("nominal-y"), 10);
		let x = parseInt(line.data("nominal-x"), 10);

		const yFixedOffset      = parseFloat(style.getPropertyValue("--y-offset"));
		const xFixedOffset      = parseFloat(style.getPropertyValue("--x-offset"));
		const yOffsetMultiplier = parseFloat(style.getPropertyValue("--y-offset-fontsize-multiplier"));

		y = y + yFixedOffset + (yOffsetMultiplier * fontSize);
		x = x + xFixedOffset;

		line.find("text").attr("transform", "translate(" + x + " " + y + ")");
	}

	/**
	 * Ensure catalog products display correctly. ie no overflow
	 *
	 * @since   1.0.0
	 * @updated 1.1.10
	 */
	$wcp.fixCatalogProducts = function () {
		const catalogProducts = $("div.wcp-catalog");

		if (0 === catalogProducts.length) {
			return; // Nothing to do
		}

		catalogProducts.each(function () {
			let catalogProduct;
			let lines;
			let maxFont;
			let balance;

			catalogProduct = $(this);
			balance        = "no" !== catalogProduct.find("g.svg-text").data("balance");
			lines          = catalogProduct.find("g.wcp-catalog-line");
			maxFont        = setMaxFont(lines);
			if (maxFont && balance) { // If Texts font sizes need to be balanced.
				lines.each(function () {
					const text = $(this);
					if (text.text()) {
						setFontSize(text, maxFont); // Reset each text to the smallest font found.
					}
				});
			}
			lines.each(function () { // Now reveal them
				$(this).removeAttr("visibility");
			});
		});
	};
}(window.$wcp = window.$wcp || {}, jQuery));

/**
 * Loader function
 *
 * @since   1.0.0
 * @updated 1.0.0
 */
jQuery(document).ready(function () {
	"use strict";
	window.$wcp.fixCatalogProducts();
});
