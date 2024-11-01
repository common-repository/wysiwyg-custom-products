/**
 * Created by Dave on 6/10/16.
 *
 * Line manager is used to handle all of the visuals and settings for a single line within a layout format
 *
 * @since   1.0.0
 * @updated 2.0.0
 */

/* JSHint/JSLint set up */
/*global wcp_textpaths */  // created in HTML by wp_localize_script()

//noinspection JSLint,AssignmentResultUsedJS
(function ($wcpA, $, undefined) {
	"use strict";

	$wcpA.actions = {  // First 6 correspond to columns in table. Last 3 are for mouse manipulation
		Y        : 0,
		X        : 1,
		Align    : 2,
		Width    : 3,
		MinFont  : 4,
		MaxFont  : 5,
		Move     : 20,
		Resize   : 21,
		ResizeEnd: 22
	};


	const keepSame = {
		0: false, // Y
		1: true,  // X
		2: true,  // Align
		3: true,  // Width
		4: true,  // MinFont
		5: true  // MaxFont
	};


	/**
	 * Initialisation and association of DOM elements as required
	 *
	 * @param lineIndex  Which line is this line
	 * @param row        {jQuery} object associated with the table row
	 * @constructor
	 *
	 * @since   1.0.0
	 * @updated 1.2.0
	 */
	$wcpA.LineManagerLine = function (lineIndex, row) {
		const cells = row.children("td");

		this.Index    = lineIndex;
		this.path     = $("#path" + this.Index);
		this.pathEl   = this.path[0];
		this.text     = $("#tpath" + this.Index);
		this.textEl   = this.text[0];
		this.Rect        = $("#rect" + this.Index);
		this.NonSizeRect = $("#nonSizeRect" + this.Index);
		this.Row         = row;
		this.yCell       = $(cells[$wcpA.actions.Y].firstChild);
		this.xCell       = $(cells[$wcpA.actions.X].firstChild);
		this.alignCell   = $(cells[$wcpA.actions.Align].firstChild);
		this.widthCell   = $(cells[$wcpA.actions.Width].firstChild);
		this.minFontCell = $(cells[$wcpA.actions.MinFont].firstChild);
		this.maxFontCell = $(cells[$wcpA.actions.MaxFont].firstChild);
		this.setSizing("MaxFont");
		this.resizing = false;
	};


	/**
	 * Called when a layout is loaded or the number of lines being formatted is changed
	 *
	 * @param line        Current line format
	 *
	 * @since   1.0.0
	 * @updated 2.0.0
	 */
	$wcpA.LineManagerLine.prototype.setLine = function (line) {
		this.hide(false);

		this.Line = line;
		this.setX(line.X);
		this.setY(line.Y);
		this.setAlign(line.Align);
		this.setWidth(line.Width);
		this.setMinFont(line.MinFont);
		this.setMaxFont(line.MaxFont);

		this.drawVisuals();
	};
	/**
	 * Sets a value associated with an action
	 *
	 * @param action
	 * @param value
	 *
	 * @since   1.0.0
	 * @updated 2.0.0
	 */

	$wcpA.LineManagerLine.prototype.setValue = function (action, value) {
		let result = true;
		let redraw = true;

		if (this.getVal(action) === value) {
			return false;
		}

		switch (action) {
			case $wcpA.actions.Y:
				this.setY(value);
				break;
			case $wcpA.actions.X:
				this.setX(value);
				break;
			case $wcpA.actions.Align:
				this.setAlign(value);
				break;
			case $wcpA.actions.Width:
				this.setWidth(value);
				break;
			case $wcpA.actions.MinFont:
				this.setMinFont(value);
				break;
			case $wcpA.actions.MaxFont:
				this.setMaxFont(value);
				break;
			default:
				// Do nothing
				result = false;
				redraw = false;
		}

		if (redraw) {
			this.drawVisuals();
		}

		return result;
	};

	$wcpA.LineManagerLine.prototype.setMouseValue = function (action, value, isSource) {
		switch (action) {
		case $wcpA.actions.Move:
				this.doMove(value, isSource);
				break;
			case $wcpA.actions.Resize:
				this.doResize(value, isSource);
				break;
			case $wcpA.actions.ResizeEnd:
				this.doResizeEnd();
				break;
			default:
			// Do nothing
		}
	};

	/**
	 * Sets whether mouse operations are on the min font or the max font
	 *
	 * @param sizingFont string  value obtained from the radio buttons
	 * @param redraw     bool    update screen
	 *
	 * @since   1.0.0
	 * @updated 1.0.0
	 */
	$wcpA.LineManagerLine.prototype.setSizing = function (sizingFont, redraw) {
		this.sizing    = sizingFont;
		this.nonSizing = "MaxFont" === sizingFont ? "MinFont" : "MaxFont";
		if (redraw) {
			this.drawVisuals();
		}
	};

	/**
	 * Sets the text font size and then works out available characters based on width
	 *
	 * @param fontSize    int
	 *
	 * @since   1.0.0
	 * @updated 1.2.0
	 */
	$wcpA.LineManagerLine.prototype.setFontSize = function (fontSize) {
		let text         = "X";
		const textExtend = ["yX", "ii"];
		let char, i, j;
		let length, lastLength, maxLength;

		//noinspection JSUnresolvedFunction
		maxLength = this.pathEl.getTotalLength();

		this.text.attr("font-size", fontSize);
		this.text.text(text);

		// Pad with XyX.... then i
		for (i = 0; i < 2; i++) {
			char   = "";
			j      = 0;
			//noinspection JSUnresolvedFunction
			length = this.textEl.getComputedTextLength();
			do {
				lastLength = length;
				text += char;
				char       = textExtend[i][j % 2];
				j++;

				this.text.text(text + char);
				//noinspection JSUnresolvedFunction
				length = this.textEl.getComputedTextLength();
			} while ((maxLength > length) && (length > lastLength));

			this.text.text(text);
		}

	};
	/**
	 * Sets the size of an SVG Rect
	 *
	 * @param rect    {jQuery}  // SVG rect
	 * @param x       numeric
	 * @param y       numeric
	 * @param width   numeric
	 * @param height  numeric
	 *
	 * @since   1.0.0
	 * @updated 1.0.0
	 */
	$wcpA.LineManagerLine.prototype.setRectSize = function (rect, x, y, width, height) {
		rect.attr("x", x);
		rect.attr("y", y);
		rect.attr("width", width);
		rect.attr("height", height);

	};
	/**
	 * Calculates visual sizes for current line and gets text and rectangles repainted
	 *
	 * @since   1.0.0
	 * @updated 2.0.0
	 */
	$wcpA.LineManagerLine.prototype.drawVisuals = function () {
		let x;
		let y;
		let width;
		let height;
		let anchor;
		let offset;

		if (!this.Line) { // Not set up yet
			return;
		}
		// Set up text element
		x = this.Line.X;
		y = this.Line.Y;
		this.path.attr("transform", "translate(" + x + " " + y + ")");

		switch (this.Line.Align) {
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
		this.textEl.setAttribute("text-anchor", anchor);
		this.textEl.setAttribute("startOffset", offset);

		width  = this.Line.Width;
		height = this.Line[this.sizing];
		this.setFontSize(height, width);
			this.path.attr("d", "M 0 0 H " + width);

			// Set up rectangles
			y = this.Line.Y - (height / 2);
			this.setRectSize(this.Rect, x, y, width, height);

			height = this.Line[this.nonSizing];
			y      = this.Line.Y - (height / 2);
			this.setRectSize(this.NonSizeRect, x, y, width, height);

	};

	/**
	 * Hides or shows appropriate DOM elements
	 *
	 * @param hide
	 *
	 * @since   1.0.0
	 * @updated 1.2.0
	 */
	$wcpA.LineManagerLine.prototype.hide = function (hide) {
		this.text.toggleClass("hidden", hide);
		this.Row.toggleClass("hidden", hide);
		this.hideRects(hide);
	};


	/**
	 * Hides or shows rectangle DOM elements
	 *
	 * @param hide
	 *
	 * @since   1.2.0
	 * @updated 1.2.0
	 */
	$wcpA.LineManagerLine.prototype.hideRects = function (hide) {
		this.Rect.toggleClass("hidden", hide);
		this.NonSizeRect.toggleClass("hidden", hide);
	};

	/**
	 * Makes sure that a value is within the acceptable range, as set for the number input fields.
	 *
	 * @param value        number
	 * @param checkField   numeric input field with min and max attributes set
	 * @returns number
	 *
	 * @since   1.0.0
	 * @updated 1.0.0
	 */
	function limitRange(value, checkField) {
		const minValue = parseInt(checkField.attr("min"), 10);
		const maxValue = parseInt(checkField.attr("max"), 10);
		let limitedValue;

		limitedValue = Math.min(value, maxValue);
		limitedValue = Math.max(limitedValue, minValue);

		return limitedValue;
	}

	/**
	 * Alters the Y (vertical) value for the current line
	 *
	 * For X, Y and Height during a mouse operation, this.resizing is set and float values are allowed for
	 * smooth mouse operation. After the mouse operation, the values are rounded back down to
	 * integers (in doResizeEnd)
	 *
	 * @param value  numeric
	 *
	 * @since   1.0.0
	 * @updated 1.0.0
	 */
	$wcpA.LineManagerLine.prototype.setY = function (value) {
		const rangedValue = limitRange(value, this.yCell);
		const iValue      = Math.floor(rangedValue);
		this.Line.Y       = this.resizing ? rangedValue : iValue;
		this.yCell.val(iValue);
	};
	/**
	 * Alters the X (horizontal) value for the current line
	 *
	 * @param value
	 *
	 * @since   1.0.0
	 * @updated 1.0.0
	 */
	$wcpA.LineManagerLine.prototype.setX = function (value) {
		const rangedValue = limitRange(value, this.xCell);
		const iValue      = Math.floor(rangedValue);
		this.Line.X       = this.resizing ? rangedValue : iValue;
		this.xCell.val(iValue);
	};
	/**
	 * Alters the alignment of the current line.
	 *
	 * @param value
	 *
	 * @since   1.2.0
	 * @updated 1.2.0
	 */
	$wcpA.LineManagerLine.prototype.setAlign = function (value) {
		this.alignCell.val(value);
		this.Line.Align = value;
	};


	/**
	 * Alters the Width value for the current line
	 *
	 * @param value
	 *
	 * @since   1.0.0
	 * @updated 1.0.0
	 */
	$wcpA.LineManagerLine.prototype.setWidth = function (value) {
		const rangedValue = limitRange(value, this.xCell);
		const iValue      = Math.floor(rangedValue);
		this.Line.Width   = this.resizing ? rangedValue : iValue;
		this.widthCell.val(iValue);
	};
	/**
	 * Alters the 'Height' value for the current line. This corresponds to the currently chosen sizing font
	 *
	 * @param value
	 *
	 * @since   1.0.0
	 * @updated 1.0.0
	 */
	$wcpA.LineManagerLine.prototype.setHeight = function (value) {
		if ("MinFont" === this.sizing) {
			this.setMinFont(value);
		} else {
			this.setMaxFont(value);
		}
	};
	/**
	 * Sets the minimum font size. Can't be more than max font size.
	 *
	 * @param value  numeric
	 *
	 * @since   1.0.0
	 * @updated 1.0.0
	 */
	$wcpA.LineManagerLine.prototype.setMinFont = function (value) {
		let iValue;
		let maxValue = limitRange(value, this.minFontCell);

		if (!this.resizing) { // Prevent value going larger than MaxFont
			maxValue = Math.min(value, this.Line.MaxFont);
		}

		iValue            = Math.floor(maxValue);
		this.Line.MinFont = this.resizing ? maxValue : iValue; // Snap back after resizing
		this.minFontCell.val(iValue); // Only show whole numbers
	};
	/**
	 * Sets the maximum font size. Can't be less than min font size.
	 *
	 * @param value  numeric
	 *
	 * @since   1.0.0
	 * @updated 1.0.0
	 */
	$wcpA.LineManagerLine.prototype.setMaxFont = function (value) {
		let iValue;
		let minValue = limitRange(value, this.maxFontCell);

		if (!this.resizing) {  // Prevent value going smaller than MinFont
			minValue = Math.max(value, this.Line.MinFont);
		}

		iValue            = Math.floor(minValue);
		this.Line.MaxFont = this.resizing ? minValue : iValue; // Snap back after resizing
		this.maxFontCell.val(iValue); // Only show whole numbers
	};
	/**
	 * Obtains the appropriate value from the line Manager
	 *
	 * @param action One of the actions enumerated in line-manager.js
	 *
	 * @returns int | string
	 *
	 * @since   1.0.0
	 * @updated 2.0.0
	 */
	$wcpA.LineManagerLine.prototype.getVal = function (action) {
		switch (action) {
			case $wcpA.actions.Y:
				return this.Line.Y;
			case $wcpA.actions.X:
				return this.Line.X;
			case $wcpA.actions.Align:
				return this.Line.Align;
			case $wcpA.actions.Width:
				return this.Line.Width;
			case $wcpA.actions.MinFont:
				return this.Line.MinFont;
			case $wcpA.actions.MaxFont:
				return this.Line.MaxFont;
			default:
				return 0;
		}
	};

	/**
	 * Mouse is being used to change X and/or Y.
	 *
	 * All lines get called for all mouse events. They only act if the current line is the source of
	 * the action. Or the checkboxes indicate that all lines should share a value.
	 *
	 * @param event        Mouse event     dx and dy contains movement information
	 * @param isSource     bool            This line is the source of the mouse event
	 *
	 * @since   1.0.0
	 * @updated 1.2.0
	 */
	$wcpA.LineManagerLine.prototype.doMove = function (event, isSource) {
		this.resizing = true;

		if (isSource || keepSame[$wcpA.actions.X]) {  // Do X move if this is us, or they're keeping sync
			this.setX(this.Line.X + event.dx);
		}
		if (isSource || keepSame[$wcpA.actions.Y]) { // Do Y move if this is us, or they're keeping sync
			this.setY(this.Line.Y + event.dy);
		}
	};

	/**
	 * Mouse is being used to extend/shrink width and/or height.
	 *
	 * @param event        Mouse event     deltaRect.width and deltaRect.height contains sizing information
	 * @param isSource   bool            This line is the source of the mouse event
	 *
	 * @since   1.0.0
	 * @updated 1.2.0
	 */
	$wcpA.LineManagerLine.prototype.doResize = function (event, isSource) {
		//noinspection JSUnresolvedVariable
		const deltaRect = event.deltaRect;
		const width     = deltaRect.width;
		const height    = deltaRect.height;
		const xOffset   = deltaRect.left;

		//noinspection OverlyComplexBooleanExpressionJS
		if (!(isSource || keepSame[$wcpA.actions.X] || keepSame[$wcpA.actions.Width] || keepSame[$wcpA.actions[this.sizing]])) { // Nothing for us, get out
			return;
		}

		this.resizing = true;
		if (width) {
			if (isSource || keepSame[$wcpA.actions.X]) {
				this.setX(this.Line.X + xOffset);
			}
			if (isSource || keepSame[$wcpA.actions.Width]) {
				this.setWidth(this.Line.Width + width);
			}
		}

		if (height && (isSource || keepSame[$wcpA.actions[this.sizing]])) {
			this.setHeight(this.Line[this.sizing] + (height * 2));
		}
	};

	/**
	 * Called after all mouse operations for all lines. Set everything to nearest integer
	 *
	 * @since   1.0.0
	 * @updated 1.0.0
	 */
	$wcpA.LineManagerLine.prototype.doResizeEnd = function () {
		this.resizing = false;
		this.setX(this.Line.X);
		this.setY(this.Line.Y);
		this.setWidth(this.Line.Width);
		this.setHeight(this.Line[this.sizing]);
	};

	/**
	 * Turn highlighting on/off for row.
	 *
	 * @param isActive  bool  True says this is the line being modified, highlight it.
	 *
	 * @since   1.0.0
	 * @updated 1.0.0
	 */
	$wcpA.LineManagerLine.prototype.highlight = function (isActive) {
		this.Row.toggleClass("wcp-highlight", isActive);
	};

	/**
	 * Sets up the numeric input fields with appropriate limits for size of image. Also checks current
	 * values if active.
	 *
	 * @param maxHeight int
	 * @param maxWidth  int
	 *
	 * @since   1.0.0
	 * @updated 1.0.0
	 */
	$wcpA.LineManagerLine.prototype.setMaxSizes = function (maxHeight, maxWidth) {
		this.yCell.attr("max", maxHeight);
		this.xCell.attr("max", maxWidth);
		this.widthCell.attr("max", maxWidth);
		this.minFontCell.attr("max", Math.floor(maxHeight / 2));
		this.maxFontCell.attr("max", Math.floor(maxHeight / 2));

		// If we're currently set make sure all values within new range
		if (this.Line) {
			this.setX(this.Line.X);
			this.setY(this.Line.Y);
			this.setWidth(this.Line.Width);
			this.setMinFont(this.Line.MinFont);
			this.setMaxFont(this.Line.MaxFont);
		}
	};

	/**
	 * Constructor
	 *
	 * @constructor
	 *
	 * @since   1.2.0
	 * @updated 1.2.0
	 */
	$wcpA.LineManager = function () {
		this.lines = [];
	};

	/**
	 * Initialisation and association of DOM elements as required
	 *
	 * @param   format        object  Format of layout currently being used
	 * @param   newLineCount  int     Number of lines being formatted in the current layout
	 *
	 * @since   1.2.0
	 * @updated 1.2.0
	 */
	$wcpA.LineManager.prototype.setFormat = function (format, newLineCount) {
		this.currentLines = this.lines.slice(0, newLineCount);
		this.unusedLines  = this.lines.slice(newLineCount);

		this.currentLines.forEach(function (line, idx) {
			line.setLine(format[idx]);
			line.drawVisuals();
		});

		this.unusedLines.forEach(function (line) {
			line.hide(true);
		});
	};

	/**
	 * Initialisation and association of DOM elements as required
	 *
	 * @param rowIdx  Line index
	 * @param row     {jQuery} object associated with the table row
	 *
	 * @since   1.2.0
	 * @updated 1.2.0
	 */
	$wcpA.LineManager.prototype.addRow = function (rowIdx, row) {
		this.lines.push(new $wcpA.LineManagerLine(rowIdx, row));
	};


	/**
	 * Sets value for appropriate line
	 *
	 * @param lineNbr      Line being acted on
	 * @param action       One of the actions enumerated in line-manager.js - value to set
	 * @param value        Integer or string - depends on action
	 *
	 * @since   1.2.0
	 * @updated 1.2.0
	 */
	$wcpA.LineManager.prototype.setValue = function (lineNbr, action, value) {
		let modified = false;

		if (keepSame[action]) {
			this.currentLines.forEach(function (line) {
				modified |= line.setValue(action, value);
			});
		} else {
			let line = this.lines[lineNbr];
			modified = line.setValue(action, value);
		}

		this.highlight(lineNbr);
		return modified;
	};

	/**
	 * Sets value for appropriate line
	 *
	 * @param lineNbr      Line being acted on
	 * @param action       One of the actions enumerated in line-manager.js - value to set
	 * @param value        Mouse event containing multiple values
	 *
	 * @since   1.2.0
	 * @updated 1.2.0
	 */
	$wcpA.LineManager.prototype.setMouseValue = function (lineNbr, action, value) {
		this.currentLines.forEach(function (line, idx) {
			line.setMouseValue(action, value, idx === lineNbr);
			line.drawVisuals();
		});
		this.highlight(lineNbr);
	};

	/**
	 * Sets whether values for a particular action should be kept the same
	 * Updates the value as appropriate
	 *
	 * @param action       One of the actions enumerated in line-manager.js - value to set
	 * @param setKeepSame  bool
	 *
	 * @since   1.2.0
	 * @updated 1.2.0
	 */
	$wcpA.LineManager.prototype.setKeepSame = function (action, setKeepSame) {
		keepSame[action] = setKeepSame;
		if (setKeepSame) {
			return this.setValue(this.currentLine, action, this.lines[this.currentLine].getVal(action));
		}
		return false;
	};

	/**
	 * Sets up the numeric input fields with appropriate limits for size of image. Also checks current
	 * values if active.
	 *
	 * @param maxHeight
	 * @param maxWidth
	 *
	 * @since   1.2.0
	 * @updated 1.2.0
	 */
	$wcpA.LineManager.prototype.setMaxSizes = function (maxHeight, maxWidth) {
		this.lines.forEach(function (line) {
			line.setMaxSizes(maxHeight, maxWidth);
		});
	};

	/**
	 * Sets whether mouse operations are on the min font or the max font
	 *
	 * @param sizingFont string  value obtained from the radio buttons
	 *
	 * @since   1.2.0
	 * @updated 1.2.0
	 */
	$wcpA.LineManager.prototype.setSizing = function (sizingFont) {
		this.currentLines.forEach(function (line) {
			line.setSizing(sizingFont, true);
		});
		this.unusedLines.forEach(function (line) {
			line.setSizing(sizingFont, false);
		});
	};

	/**
	 * Checks to see if all values are the same
	 *
	 * @param action    value being checked to see if they are all the same
	 *
	 * @since   1.2.0
	 * @updated 1.2.0
	 */
	$wcpA.LineManager.prototype.allSame = function (action) {
		const checkVal = this.lines[0].getVal(action);
		let allSame;
		allSame        = !this.currentLines.some(function (line) {
			return checkVal !== line.getVal(action);
		});

		keepSame[action] = allSame;

		return allSame;
	};


	/**
	 * Highlights appropriate row
	 *
	 * @param lineNbr    row to be highlighted
	 *
	 * @since   1.2.0
	 * @updated 1.2.0
	 */
	$wcpA.LineManager.prototype.highlight = function (lineNbr) {
		this.currentLine = lineNbr;
		this.currentLines.forEach(function (line, idx) {
			line.highlight(lineNbr === idx);
		});
	};
	/**
	 * Updates CSS for current lines
	 *
	 * @since   1.2.0
	 * @updated 1.2.0
	 */
	$wcpA.LineManager.prototype.updateCss = function () {
		this.currentLines.forEach(function (line) {
			line.updateCss();
		});
	};



}(window.$wcpA = window.$wcpA || {}, jQuery));

