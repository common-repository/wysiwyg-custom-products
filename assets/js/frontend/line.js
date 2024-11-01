/**
 * Created by Dave on 10/12/18.
 *
 * @since   1.2.0
 * @updated 2.1.0
 */

/* JSHint/JSLint set up */
/* global wcpp_svgInfo */

//noinspection JSLint,AssignmentResultUsedJS
(function ($wcp, $, undefined) {
	"use strict";

	const SHRUNK = 1;
	const GROWN  = 2;

	const fontFiddle        = wcpp_svgInfo["fontFiddle"];
	const containerSelector = wcpp_svgInfo["textContainer"];
	const isTextPath        = ("textPath" === containerSelector);

	/**
	 * Initialisation and association of DOM elements as required
	 *
	 * @param {LineManager}  owner
	 * @param {jQuery}  line  Which line area for output
	 *
	 * @constructor
	 *
	 * @since   1.2.0
	 * @updated 2.1.0
	 *
	 */
	$wcp.Line = function (owner, line) {
		this.owner     = owner;
		this.text      = line.find("text");
		this.svg_texts = line.find(".wcp-text");

		this.display = line.find(containerSelector + ":first");
		this.test    = line.find(containerSelector + ":last");
		this.testDOM = this.test[0];

		if (isTextPath) {
			this.paths = line.find(".wcp-path");
		}

		this.transforms = line.find(".wcp-transform");
		this.alignments = line.find(".wcp-alignment");
	};

	/**
	 * Called when the number of lines being formatted is changed
	 *
	 * @param   Format      Current line format
	 *
	 * @since   1.2.0
	 * @updated 2.1.0
	 */
	$wcp.Line.prototype.setLineFormat = function (Format) {
		let anchor;
		let offset;

		this.Format      = Format;
		this.currentText = "";
		this.textTooLong = false;

		if (isTextPath) {
			//noinspection JSUnresolvedFunction
			this.maxLength = document.getElementById(Format.path).getTotalLength() * 0.9;
			this.paths.attr("href", "#" + Format.path);
		} else {
			this.maxLength = Format.width;
		}

		// Set up text and mask element
		if (!fontFiddle) {
			this.transforms.attr("transform", "translate(" + Format.x + " " + Format.y + ")");
		}

		this.setTestText(this.currentText);
		this.setDisplayText(this.currentText);

		this.fontSize = Format.maxFont;
		this.setFontSize(this.svg_texts, Format.maxFont);
		this.setFontSize(this.test, Format.maxFont);

		switch (Format.align) {
			case "L":
				anchor = "start";
				offset = "0%";
				break;
			case "R":
				anchor = "end";
				offset = "100%";
				break;
			default:
				anchor = "middle";
				offset = "50%";
		}

		// Use DOM because jquery forces lowercase
		this.alignments.each(function () {
			this.setAttribute("text-anchor", anchor);
			if (isTextPath) {
				this.setAttribute("startOffset", offset);
			}
		});

		this.hide(false);
	};

	/**
	 * Sets the text font size
	 *
	 * @param what
	 * @param desiredSize    int
	 *
	 * @since   1.2.0
	 * @updated 2.1.0
	 */
	$wcp.Line.prototype.setFontSize = function (what, desiredSize) {
		// Make sure desired size doesn't bust this lines font range
		const fontSize = Math.max(Math.min(desiredSize, this.Format.maxFont), this.Format.minFont);

		what.attr("font-size", fontSize);
		if (fontFiddle && (this.svg_texts === what)) {
			this.middleFiddle(fontSize);
		}
	};

	/**
	 * Adjusts y for middle baseline
	 *
	 * @since   1.2.0
	 * @updated 2.1.0
	 */
	$wcp.Line.prototype.middleFiddle = function (fontSize) {
		const style = getComputedStyle(this.svg_texts[0]);

		const yFixedOffset      = parseFloat(style.getPropertyValue("--y-offset"));
		const xFixedOffset      = parseFloat(style.getPropertyValue("--x-offset"));
		const yOffsetMultiplier = parseFloat(style.getPropertyValue("--y-offset-fontsize-multiplier"));

		const y = this.Format.y + yFixedOffset + (yOffsetMultiplier * fontSize);
		const x = this.Format.x + xFixedOffset;

		this.transforms.attr("transform", "translate(" + x + " " + y + ")");
	};

	/**
	 * Set the final text on any areas that need it
	 *
	 * @since   2.0.0
	 * @updated 2.0.0
	 */
	$wcp.Line.prototype.setDisplayText = function (text) {
		this.svg_texts.text(text);
	};
	/**
	 * Set the test text
	 *
	 * @since   2.0.0
	 * @updated 2.0.0
	 */
	$wcp.Line.prototype.setTestText = function (text) {
		text                     = text.trim();
		this.testDOM.textContent = text;
		this.testTextLastChar    = text.length - 1;
	};
	/**
	 * Checks the text length of the test text
	 *
	 * @since   2.0.0
	 * @updated 2.1.0
	 */
	$wcp.Line.prototype.isOverflow = function () {
		let point;

		if (undefined === this.testTextLastChar || 2 > this.testTextLastChar) {
			return false;  // No point in doing checks, so no overflow
		}

		// 'Proper' overflow check
		//noinspection JSUnresolvedFunction
		if (this.testDOM.getComputedTextLength() > this.maxLength) {
			return true;
		}

		try { // Now catch situations where text is hidden BEFORE above check can be made
			//noinspection JSUnresolvedFunction
			point = this.testDOM.getEndPositionOfChar(this.testTextLastChar);
		} catch (e) {  // Possible safari bug
			// Lets see what happens
			return false;
		}

		return ((0 === point.x) || (point.x > this.maxLength));  // 0 => Chrome, maxLength => Firefox
	};
	/**
	 * Set the text on a line and see if it needs resizing
	 *
	 * @since   1.2.0
	 * @updated 2.0.0
	 */
	$wcp.Line.prototype.setText = function (text, modified) {
		let currentFontSize = this.fontSize;
		let result          = 0;

		if (true !== modified) {
			if ((text === this.currentText)) { // No change to this text
				return result;
			}

			if (this.textTooLong && text.startsWith(this.currentText)) {  //Already overflowing - why bother?
				return result;
			}
		}

		this.textTooLong = false;
		this.currentText = text;
		this.setTestText(text);

		if ("" === text) {
			this.setFontSize(this.test, this.Format.maxFont);
			currentFontSize = this.Format.maxFont;
			this.setDisplayText(text);
		} else {
			if (this.isOverflow()) {
				currentFontSize = this.shrinkText();
			} else if (this.fontSize < this.Format.maxFont) {
				currentFontSize = this.maybeGrowText();
			} else {
				this.setDisplayText(text);
			}
		}

		if (currentFontSize > this.fontSize) {
			result = SHRUNK;
		}

		if (currentFontSize < this.fontSize) {
			result = GROWN;
		}

		this.fontSize = currentFontSize;

		return result;

	};

	/**
	 * Reduces font size and/or amount of text to fit maxLength
	 *
	 * @since   1.2.0
	 * @updated 2.0.0
	 */
	$wcp.Line.prototype.shrinkText = function () {
		let setFont   = this.fontSize;
		let setText   = this.currentText;
		let doesntFit = true; // Wouldn't be here otherwise

//        Try just reducing font size if we can
		while (doesntFit && (setFont > this.Format.minFont)) {
			setFont -= 1;
			this.setFontSize(this.test, setFont);
			doesntFit = this.isOverflow();
		}

		this.textTooLong = doesntFit;

		while (doesntFit) { // At min font size and it doesn't fit, shorten the text
			setText = setText.substring(0, setText.length - 1);
			this.setTestText(setText);
			//noinspection JSUnresolvedFunction
			doesntFit = this.isOverflow();
		}

		this.setDisplayText(setText);
		return setFont;
	};

	/**
	 * Sees if text can be enlarged after modification to other texts
	 *
	 * @since   1.2.0
	 * @updated 2.0.0
	 */
	$wcp.Line.prototype.maybeGrowText = function () {
		let setFont = this.fontSize;

//        Try growing font size
		while ((setFont < this.Format.maxFont) && (!this.isOverflow())) {
			setFont++;
			this.setFontSize(this.test, setFont);
		}

//        Check for overflow and reduce again if necessary
		if (this.isOverflow()) {
			setFont--;
			this.setFontSize(this.test, setFont);
		}

		this.setDisplayText(this.currentText);

		return setFont;
	};

	/**
	 * Hides or shows appropriate DOM elements
	 *
	 * @param hide
	 *
	 * @since   1.2.0
	 * @updated 1.2.0
	 */
	$wcp.Line.prototype.hide = function (hide) {
		this.svg_texts.toggleClass("wcp-hidden", hide);  // make hidden if not active
		if (hide) {
			this.setDisplayText('');
		}
	};
}(window.$wcp = window.$wcp || {}, jQuery));
