/**
 * Created by Dave on 10/12/18.
 *
 * @since   1.2.0
 * @updated 2.0.0
 */

/* JSHint/JSLint set up */

//noinspection JSLint,AssignmentResultUsedJS
(function ($wcp, $, undefined) {
	"use strict";

	/**
	 *
	 * Breaks the JSON format array into the array of formats and sets up the lines appropriately

	 * @param {jQuery}  displayArea
	 * @constructor
	 *
	 * @since   1.2.0
	 * @updated 2.0.0
	 */
	$wcp.LineManager = function (displayArea) {
		let minLineCount    = "";
		let maxLineCount    = "";  // Gonna be over written with strings
		const parsedFormats = {};
		const formats       = displayArea.data("formats");
		const lines         = displayArea.find("g.wcp-line");
		const me            = this;
		if (formats.length) {
			minLineCount = formats[0].l;
			formats.forEach(function (format) {
				parsedFormats[format.l] = parseFormat(format.f);
				maxLineCount            = format.l;
			});
		}

		this.currentLineCount = 0;
		this.minLineCount     = parseInt(minLineCount, 10);
		this.maxLineCount     = parseInt(maxLineCount, 10);
		this.formats          = parsedFormats;
		this.message          = [];
		this.variations       = {};

		this.lines       = [];
		this.lineOffsets = [];

		lines.each(function () {
			const line = $(this);
			me.lines.push(new $wcp.Line(me, line));
		});

		this.maybeChangeLineCount(this.minLineCount);
	};



	/**
	 * Displays the lines of text in the associated texts
	 *
	 * @param textLines array of strings|false
	 *
	 * @since   1.2.0
	 * @updated 1.2.6
	 */
	$wcp.LineManager.prototype.displayMessage = function (textLines) {
		let formatModified;
		let fontSizeChange = 0;

		if (undefined === textLines) {
			textLines = this.message;
			this.maybeChangeLineCount(textLines.length);
			formatModified = true; // Force refresh
		} else {
			formatModified = this.maybeChangeLineCount(textLines.length);
			this.message   = textLines;
		}

		this.currentLines.forEach(function (line, idx) {
			if (undefined !== textLines[idx]) {
				fontSizeChange |= line.setText(textLines[idx], formatModified); // Force rewrite if format has changed
			}
		});

		//noinspection JSBitwiseOperatorUsage
		if (!formatModified && (fontSizeChange & $wcp.GROWN)) {
			this.currentLines.forEach(function (line) {
				line.maybeGrowText();
			});
		}
			this.balanceTexts();     // Set all text to smallest appropriate size

	};

	/**
	 * Updates (or creates) a line of text in the message and causes it to be redisplayed.
	 *
	 * @param text string
	 *
	 * @since   1.2.0
	 * @updated 1.2.0
	 */
		$wcp.LineManager.prototype.setText = function (text) {
			this.message[0] = text;
			this.displayMessage();
		};

	/**
	 * Adjusts all texts to have the font size of the smallest text. setFontSize checks range of desired size.
	 *
	 * @since   1.2.0
	 * @updated 2.0.0
	 */
	$wcp.LineManager.prototype.balanceTexts = function () {
		let minFont;
		let maxFont;
		let textFont;
		let lines;
						lines = this.currentLines;
			textFont = lines[0].fontSize;
			minFont  = textFont;
			maxFont  = textFont;

			lines.forEach(function (line) {
				textFont = line.fontSize;
				minFont  = Math.min(minFont, textFont);
				maxFont  = Math.max(maxFont, textFont);
			});

			// Set visible text font size once only
			if (minFont === maxFont) { // Make sure they all have there own size
				this.setVisibleTextSize(lines);
			} else { // Set every thing to smallest size
				lines.forEach(function (line) {
					line.setFontSize(line.svg_texts, minFont);
					line.setFontSize(line.test, minFont);
				});
			}

	};
	/**
	 * Applies the calculated font size to the displayed lines.
	 *
	 * @since   1.2.6
	 * @updated 2.0.0
	 */
	$wcp.LineManager.prototype.setVisibleTextSize = function (lines) {
		if (undefined === lines) {
			lines = this.currentLines;
		}
		lines.forEach(function (line) {
			line.setFontSize(line.svg_texts, line.fontSize);
		});
	};
	/**
	 * Changes variation value Css when a variation select is changed
	 *
	 * @param id    string  Variation name
	 * @param value string  New value
	 *
	 * @since   1.2.0
	 * @updated 2.0.0
	 */
	$wcp.LineManager.prototype.setVariation = function (id, value) {
		let repaint = false;

		if (value === this.variations[id]) {
			return false; // No Change
		}

		this.variations[id] = value;

		this.currentLines.forEach(function (line) {
			repaint |= line.maybeSetVariation(id);
		});

		return repaint;
	};

	/**
	 * Checks to see if the number of lines being edited is changed. Updates formatting accordingly.
	 *
	 * @param   targetLineCount  int  Number of lines in the message being displayed
	 *
	 * @returns boolean               Indicates whether number of lines have changed. True if yes.
	 *
	 * @since   1.2.0
	 * @updated 2.0.0
	 */
	$wcp.LineManager.prototype.maybeChangeLineCount = function (targetLineCount) {
		let newLineCount = targetLineCount;
		let format;


		// Check value and make sure it's in range. Flag error if too many lines
		if (newLineCount < this.minLineCount) {
			newLineCount = this.minLineCount;
		}

		this.tooManyLines = ( newLineCount > this.maxLineCount );
		if (this.tooManyLines) {
			newLineCount = this.maxLineCount;
		}

		// Line count has changed, reformat all texts
		if (newLineCount !== this.currentLineCount) {
			this.currentLineCount = newLineCount;
			format                = this.formats[newLineCount];

			this.currentLines = this.lines.slice(0, newLineCount);
			this.unusedLines  = this.lines.slice(newLineCount);


			this.currentLines.forEach(function (line, idx) {
				line.setLineFormat(format[idx]);
			});

			this.unusedLines.forEach(function (line) {
				line.hide(true);
			});

			return true;
		}
		return false;
	};
	/**
	 * Checks to see any of the lines have had to shorten the entered text
	 *
	 * @returns boolean
	 *
	 * @since   1.2.0
	 * @updated 2.0.0
	 */
	$wcp.LineManager.prototype.textTooLong = function () {
		return this.currentLines.some(function (line) {
			return line.textTooLong;
		});
	};

	/**
	 * Splits a .l ("LinesN") format into an array of the individual (parsed) lines.
	 *
	 * @param    format   string  Format lines separated by "|"
	 * @returns  Array
	 *
	 * @since   1.2.0
	 * @updated 1.2.0
	 */
	function parseFormat(format) {
		const result = [];
		const lines  = format.split("|");

		lines.forEach(function (line) {
			result.push(formatLine(line));
		});

		return result;
	}

	/**
	 * Parses a format line into it's component parts
	 *
	 * @param   line   string  Parameters separated by ","
	 *
	 * @returns Object
	 *
	 * @since   1.2.0
	 * @updated 2.0.0
	 */
	function formatLine(line) {
		const result = {};
		let parts;

		parts             = line.split(",");
		result.y          = parseInt(parts[0], 10);
		result.x          = parseInt(parts[1], 10);
		result.width      = parseInt(parts[2], 10);
		result.align      = parts[3];
		result.minFont    = parseInt(parts[4], 10);
		result.maxFont    = parseInt(parts[5], 10);
		result.path       = parts[6];

		return result;
	}

}(window.$wcp = window.$wcp || {}, jQuery));
