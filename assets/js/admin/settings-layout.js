/**
 * Created by Dave on 24/08/16.
 *
 * @since   1.2.0
 * @updated 2.0.0
 */

/* JSHint/JSLint set up */
/*global ajaxurl      */  // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
/*global wcp_messages */  // created in HTML by wp_localize_script()
/*global interact     */  // from interact.js
/*global wp           */  // created by wp_enqueue_media()
/*global postboxes    */
/*global pagenow      */

//noinspection AssignmentResultUsedJS,JSUnusedLocalSymbols,JSLint
(function ($wcpA, $, undefined) { // undefined is declared but not provided so that it IS undefined.
	// undefined can be assigned another value by malicious code.
	"use strict";

	//noinspection MagicNumberJS,LocalVariableNamingConventionJS
	const NEW_LINE_OFFSET = 1.1;  // Would like to be const but avoiding ECMA script 6 for the moment

	let editLayout           = {};
	let originalLayout       = {};
	let originalLayoutJSON;
	let currentImageId       = 0;
	let layoutName           = "";
	let currentLines         = 0;
	let bModified            = true;
	let existingLayoutNames  = [];
	let sameAsRow;
	const keepSameCheckboxes = {};
	let layoutSelect;

	let nonce;
	const tab = "layout";
	let lineManager;
	let throbber;

	/**
	 * Set up page with currently selected layout information
	 *
	 * @since   1.0.0
	 * @updated 2.0.0
	 */
	function reloadLayout() {
		//noinspection JSUnresolvedVariable
		imageChange("wcp_main_image", editLayout.SetupImage);

		$("#max_lines").val(editLayout.MaxLines).trigger("change");
		$("#current_lines").val(editLayout.CurrentLines).trigger("change");
		$("#multiline_msg").val(editLayout.MultilineReformat);
		$("#too_many_lines_msg").val(editLayout.NumberOfLines);
		$("#singleline_msg").val(editLayout.SinglelineReformat);
		$("#ie_msg").val(editLayout.IeMessage);

		$("#ink_color").iris("color", decimalToHex(editLayout.InkColor));
		$("#sizing_color").iris("color", decimalToHex(editLayout.ActiveMouseColor));
		$("#non_sizing_color").iris("color", decimalToHex(editLayout.InactiveMouseColor));

		bModified = true;  // Force modified toggles
		maybeModified();
	}

	/**
	 * Makes sure that the field in the new name text box is based on the currently selected layout and
	 * is unique by addition of the word - copy and a version number if necessary.
	 *
	 * @param currentName
	 *
	 * @since 1.0.0
	 */
	function setNewName(currentName) {
		let newName;

		newName = currentName;
		if (0 > newName.toLowerCase().indexOf("copy")) {
			newName += " - copy";
		}

		while ($.inArray(newName.toLowerCase().trim(), existingLayoutNames) > -1) {
			if ($.isNumeric(newName.slice(-1))) { // If already has a number
				// Increment
				newName = newName.replace(/\d+$/, function (s) {
					return +s + 1;
				});
			} else {
				newName += "1";
			}
		}
		$("input#new_name").val(newName).trigger("change");
	}

	/**
	 * Called when the new name is being edited. Makes sure there's no name clash with an existing layout.
	 * or reserved option name. Disables the copy and rename button if there is.
	 *
	 * @param event
	 *
	 * @since   1.0.0
	 * @updated 1.1.0
	 */
	function changeName(event) {
		const name = $(event.currentTarget).val().toLowerCase().trim();
		let disabled;

		disabled = ((0 === name.length) || ($.inArray(name, existingLayoutNames) > -1)) && !bModified;

		$("#wcp_copy").toggleClass("disabled", disabled);
		$("#wcp_rename").toggleClass("disabled", disabled);
	}

	/**
	 * Ajax call to fetch the selected layout.
	 *
	 *
	 * @since   1.0.0
	 * @updated 1.2.0
	 */
	function loadLayout() {
		let data;

		layoutName = layoutSelect.val();
		data       = {
			"wcp-nonce": nonce,
			"tab"      : tab,
			"action"   : "get_layout",
			"name"     : layoutName
		};

		showThrobber(true);

		$.get(ajaxurl, data, function (response) {
			showThrobber(false);
			editLayout = response;
			originalLayout = $.extend(true,  {}, editLayout); // Deep copy
			originalLayoutJSON = JSON.stringify(originalLayout);  // Save for modification tests
			reloadLayout();
		});

		setNewName(layoutName);
	}

	/**
	 * Chooses which image to load based on the calling element's id
	 *
	 * @param imageId       string
	 * @param attachmentId  int     AttachmentId returned from the media selector
	 *
	 * @since   1.0.0
	 * @updated 1.2.4
	 */
	function imageChange(imageId, attachmentId) {
		switch (imageId) {
			case "wcp_main_image":
				if (currentImageId !== attachmentId) {
					getImage(attachmentId, "SetupImage", "svg_background");
					currentImageId = attachmentId;
				}
				messageOrImage();
				break;
			default: // Do nothing
		}
	}

	/**
	 * Ajax call to get the selected image information
	 *
	 * @param attachmentId  int     AttachmentId returned from the media selector
	 * @param setField      string  Field within current_layout being modified
	 * @param imageSelector string  DOM SVG element id
	 *
	 * @since   1.0.0
	 * @updated 1.2.4
	 */
	function getImage(attachmentId, setField, imageSelector) {
		let image;
		const data = {
			"wcp-nonce" : nonce,
			"tab"       : tab,
			"action"    : "get_image_attr",
			"attachment": attachmentId,
		};

		editLayout[setField] = attachmentId;
		if (attachmentId) {
			showThrobber(true);
			$.get(ajaxurl, data, function (response) {
					editLayout.SetupHeight = response.height;
					editLayout.SetupWidth  = response.width;

					lineManager.setMaxSizes(response.height, response.width);

					// Have to do this using DOM element rather than jQuery because jQuery forces attribute names to lowercase
					// being a "foreign" XML object, SVG is case sensitive, so it has to be "viewBox" not "viewbox"
					document.getElementById("wcp_svg_image").setAttribute("viewBox", "0 0 " + response.width + " " + response.height);
				image = $("#" + imageSelector);
				image.attr("width", editLayout.SetupWidth);
				image.attr("height", editLayout.SetupHeight);
				image.attr("xlink:href", response.url);
				showThrobber(false);
				messageOrImage();  // Also updates 'delete overlay' visibility
			});
		} else {
			$("#" + imageSelector).attr("xlink:href", "");
			messageOrImage();
		}

		maybeModified();
	}

	/**
	 * Displays image if available, message otherwise
	 *
	 * @since   1.2.4
	 * @updated 1.2.4
	 */
	function messageOrImage() {
		//noinspection JSUnresolvedVariable
		let hideImage = (0 === editLayout.SetupImage);

		$("#wcp_image_div").toggleClass("wcp-hidden", hideImage);
		$("#wcp_image_message").toggleClass("wcp-hidden", !hideImage);
	}

	/**
	 * Get's either the selected layout or the list of layouts available
	 *
	 * @param selected
	 * @returns {jQuery}
	 *
	 * @since 1.0.0
	 */
	function getLayoutSelectorOptions(selected) {
		const selector = $("select#layouts");
		if (selected) {
			return selector.children("option:selected");
		}
		return selector.children("option");
	}

	/**
	 * Ajax call to post the modified layout for saving
	 *
	 * @since   1.0.0
	 * @updated 1.2.4
	 */
	function saveLayout() {
		const data = {
			"wcp-nonce": nonce,
			"tab"      : tab,
			"action"   : "post_layout",
			"name"     : layoutName,
			"layout"   : editLayout,
		};

		if (disabled("#wcp_save")) {
			return;
		}

		showThrobber(true);

		//noinspection JSCheckFunctionSignatures
		$.post(ajaxurl, data).done(function (errorMsg) {
			showThrobber(false);
			if (errorMsg.trim()) {
				alert(errorMsg.trim());
			} else {
				originalLayout = $.extend(true,  {}, editLayout); // Deep copy
				originalLayoutJSON = JSON.stringify(originalLayout);  // Save for modification tests
				maybeModified();
			}
		});
	}

	/**
	 * Ajax call to post a name change
	 *
	 * @since   1.0.0
	 * @updated 1.2.0
	 */
	function renameLayout() {
		const newName = $("#new_name").val();
		const data    = {
			"wcp-nonce": nonce,
			"tab"      : tab,
			"action"   : "post_rename",
			"name"     : layoutName,  // Needed because the ajax handler doesn't know where we are at
			"new-name" : newName,
		};

		if (disabled("#wcp_rename")) {
			return;
		}
		showThrobber(true);

		//noinspection JSCheckFunctionSignatures
		$.post(ajaxurl, data).done(function () {
			const currentOption = getLayoutSelectorOptions(true);

			showThrobber(false);

			currentOption.val(newName); // Change name in select list
			currentOption.text(newName);
			existingLayoutNames[existingLayoutNames.indexOf(layoutName.toLowerCase())] = newName.toLowerCase();
			layoutName                                                                 = newName;
			setNewName(newName); // bump new name - Now the current name
		});
	}

	/**
	 * Ajax call to copy the currently selected layout to the new name
	 *
	 * @since   1.0.0
	 * @updated 1.2.0
	 */
	function copyLayout() {
		const newName = $("#new_name").val();
		const data    = {
			"wcp-nonce": nonce,
			"tab"      : tab,
			"action"   : "post_copy",
			"name"     : layoutName,
			"new-name" : newName,
		};

		if (disabled("#wcp_copy")) {
			return;
		}

		showThrobber(true);

		//noinspection JSCheckFunctionSignatures
		$.post(ajaxurl, data).done(function () {
			const currentOption = getLayoutSelectorOptions(true);
			const option        = new Option(newName, newName, true, true);

			showThrobber(false);

			currentOption.after(option); // Add new name to select list and select - set in new Option above
			existingLayoutNames.push(newName.toLowerCase()); // add layout name
			setNewName(newName); // bump new name - Now the current name
			layoutName = newName;
			deleteVisibility(); // Should have at least two now
		});
	}

	/**
	 * Checks to see if the element has the class disabled or not
	 *
	 * @param selector  string jQuery selector for element
	 *
	 * @since   1.2.4
	 * @updated 1.2.4
	 */
	function disabled(selector) {
		return $(selector).hasClass("disabled");
	}
	/**
	 * Ajax call to delete the selected layout. Confirmation is obtained in maybeDelete.
	 *
	 * @since   1.0.0
	 * @updated 1.2.0
	 */
	function deleteLayout() {
		const data = {
			"wcp-nonce": nonce,
			"tab"      : tab,
			"action"   : "post_delete",
			"name"     : layoutName,
		};

		showThrobber(true);

		//noinspection JSCheckFunctionSignatures
		$.post(ajaxurl, data).done(function () {
			const currentOption = getLayoutSelectorOptions(true);
			const lcLayoutName  = layoutName.toLowerCase();
			let newOption;

			showThrobber(false);

			// choose new item select list
			newOption         = currentOption.next();
			if (!newOption.length) {
				newOption = currentOption.prev();
			}
			// delete layout from select list, and name array
			currentOption.remove();
			deleteVisibility(); // might have be last one

			existingLayoutNames = $.grep(existingLayoutNames, function (value) {
				return value !== lcLayoutName;
			});

			// Load new layout
			$("select#layouts").val(newOption.val()).trigger("change");
		});
	}


	/**
	 * Called when the max lines selection is changed. Adds or removes format lines as required.
	 *
	 * @param event
	 *
	 * @since 1.0.0
	 */
	function setMaxLines(event) {
		const maxLines = parseInt($(event.currentTarget).val(), 10);
		let i;
		let j;
		let lastFormat;
		let newFormat;

		if (maxLines < editLayout.MaxLines) { // Reducing lines
			//noinspection JSUnresolvedVariable
			if (!window.confirm(wcp_messages.reducing_max_lines)) {
				$(event.currentTarget).val(editLayout.MaxLines);
				return;  // User doesn't want to lose formatting, bail
			}

			// Definitely reducing lines, remove unwanted formats
			for (i = editLayout.MaxLines; i > maxLines; i--) {
				//noinspection JSUnresolvedVariable
				delete editLayout.Formats["Lines" + i];
			}

		} else { // Increasing lines
			for (i = editLayout.MaxLines; i < maxLines; i++) {
				// Copy previous format
				//noinspection JSUnresolvedVariable
				lastFormat = editLayout.Formats["Lines" + i];
				newFormat  = [];

				for (j = 0; j < i; j++) {
					newFormat.push($.extend({}, lastFormat[j]));
				}
				newFormat.push($.extend({}, lastFormat[j - 1])); // Recopy last format line

				// Move it to the new y position, making sure it doesn't fall off the image
				newFormat[j].Y += Math.floor(NEW_LINE_OFFSET * newFormat[j].MaxFont);
				if (newFormat[j].Y > (editLayout.SetupHeight - (newFormat[j].MaxFont / 2) )) {
					newFormat[j].Y = (editLayout.SetupHeight - (newFormat[j].MaxFont / 2));
				}

				//noinspection JSUnresolvedVariable
				editLayout.Formats["Lines" + (parseInt(i) + 1)] = newFormat;
			}
		}

		editLayout.MaxLines = maxLines;
		$("#current_lines").val(maxLines).trigger("change");

		maybeModified();
	}

	/**
	 * Called when the number of lines being formatted is changed. Reloads the line managers with the appropriate
	 * data.
	 *
	 * @param event
	 *
	 * @since 1.0.0
	 */
	function doFormatLines(event) {
		const formatLines = parseInt($(event.currentTarget).val(), 10);
		let action;

		lineManager.setFormat( editLayout.Formats["Lines" + formatLines], formatLines);

		currentLines               = formatLines;
		editLayout.CurrentLines = formatLines; // No modified, will only get saved if user makes other changes
		// Could save it by ajax, but can't really see the point

		if (currentLines > 1) {
			for (action = $wcpA.actions.X; action <= $wcpA.actions.MaxFont; action++) {
				keepSameCheckboxes[action].checked = lineManager.allSame(action);
			}
	    }

		sameAsRow.toggleClass("hidden", currentLines < 2);
	}

	/**
	 * Called when the font size radio buttons are changed. Causes the line managers to update accordingly.
	 *
	 * @param event
	 *
	 * @since 1.0.0
	 */
	function doChooseSetFont(event) {
		lineManager.setSizing($(event.currentTarget).val());
	}

	/**
	 * Sets the appropriate value to passed line. Checks to see if that particular
	 * value (action) is marked as "Keep Same" using the checkbox. If so, sets it for all lines.
	 *
	 * @param action   One of the actions enumerated in line-manager.js
	 * @param lineNbr  Number of the line to be modified and highlighted if necessary
	 * @param value    Numeric
	 *
	 * @since 1.0.0
	 */
	function setVal(action, lineNbr, value) {
		lineManager.setValue(lineNbr, action, value);
		maybeModified();
	}

	/**
	 * Set a value from a mouse action
	 *
	 * @param action  One of the mouse actions enumerated in line-manager.js
	 * @param event   target is one of the SVG rectangles
	 *
	 * @since 1.0.0
	 */
	function setMouseValue(action, event) {
		const lineNbr = parseInt(event.target.id.substr(-1, 1));
		lineManager.setMouseValue(lineNbr, action, event);
		maybeModified();
	}

	/**
	 * Sets/Resets whether a value should be made the same for all the lines in the current format.
	 * If being set, then it causes all of the lines to be updated to the last used (highlighted) line value.
	 *
	 * @param action       One of the actions enumerated in line-manager.js
	 * @param setKeepSame  bool
	 *
	 * @since 1.0.0
	 */
	function setSameAs(action, setKeepSame) {
		if (lineManager.setKeepSame(action, setKeepSame)) {  // Returns whether any value has been changed
			maybeModified();
		}
	}

	/**
	 * Called when the user error messages are changed
	 *
	 * @param event
	 *
	 * @since   1.0.0
	 * @updated 1.2.6
	 */
	function changeMessage(event) {
		const id   = event.currentTarget.id;
		const text = $(event.currentTarget).val();

		switch (id) {
			case "multiline_msg":
				editLayout.MultilineReformat = text;
				break;
			case "too_many_lines_msg":
				editLayout.NumberOfLines = text;
				break;
			case "singleline_msg":
				editLayout.SinglelineReformat = text;
				break;
			case "ie_msg" :
				editLayout.IeMessage = text;
				break;
			default:
			// Do nothing
		}

		maybeModified();
	}

	/**
	 * Set or clear the modified status. Changes layout and functionality accordingly
	 *
	 * @since   1.0.0
	 * @updated 1.2.4
	 */
	function maybeModified() {
		let layoutSelect;
		const isModified = (JSON.stringify(editLayout) !== originalLayoutJSON);

		if (bModified === isModified) {
			return;
		}

		bModified = isModified;

		$("#wcp_save").toggleClass("disabled", !bModified);
		$("#wcp_cancel").toggleClass("disabled", !bModified);
		$("#wcp_copy").parent().toggleClass("hidden", bModified);
		$("input#new_name").parent().toggleClass("hidden", bModified);
		deleteVisibility();
		layoutSelect = $("select#layouts");
		layoutSelect.prop("disabled", bModified);

		if (bModified) {
			window.onbeforeunload = function () {
				//noinspection JSUnresolvedVariable
				return wcp_messages.modified_leave;
			};
		} else {
			layoutSelect.focus();
			window.onbeforeunload = null;
		}
	}

	/**
	 * Modifies screen based on whether an the currently selected layout can be deleted.
	 * Can only delete a non-modified layout if there's more than one
	 *
	 * @since 1.0.0
	 */
	function deleteVisibility() {
		$("#wcp_delete").toggleClass("hidden", bModified || (2 > getLayoutSelectorOptions().length)); // Can't delete last one!
	}

	/**
	 * Called when the delete layout button is clicked. Checks for confirmation before calling delete function
	 *
	 * @since 1.0.0
	 */
	function maybeDelete() {
		//noinspection JSUnresolvedVariable
		if (window.confirm(wcp_messages.confirm_delete + " " + layoutName)) {
			deleteLayout();
		}
	}

	/**
	 * Called when the cancel layout button is clicked. Checks for confirmation before calling reloading the layout original layout.
	 *
	 * @since   1.2.4
	 * @updated 1.2.4
	 */
	function maybeCancel() {

		if (disabled("#wcp_cancel")) {
			return;
		}

		//noinspection JSUnresolvedVariable
		if (window.confirm(wcp_messages.confirm_cancel)) {
			editLayout = $.extend(true,  {}, originalLayout);
			reloadLayout();
		}
	}

	/**
	 * Handle color change event
	 *
	 * @param  event
	 * @param  ui
	 *
	 * @since  1.1.1
	 */

	function colorChange(event, ui) {
		const id       = event.target.id;
		const color    = ui.color._color;
		const colorHex = decimalToHex(color);
		let selector;

		switch (id) {
			case "ink_color" :
				selector               = "display_text";
				editLayout.InkColor = color;
				break;
			case "sizing_color" :
				selector                       = "size_rects";
				editLayout.ActiveMouseColor = color;
				break;
			case "non_sizing_color" :
				selector                         = "non_size_rects";
				editLayout.InactiveMouseColor = color;
				break;
		}

		$("#" + selector).css("fill", colorHex);
		maybeModified();
	}

	/**
	 * Utility function to get suitable hex string for colors
	 *
	 * @param  d integer
	 *
	 * @return string
	 *
	 * @since  1.1.1
	 */
	function decimalToHex(d) {
		let hex = Number(d).toString(16);
		hex     = "#000000".substr(0, 7 - hex.length) + hex;
		return hex;
	}

	/**
	 * Uses interact.js to handle mouse events. i.e. text resizing and moving
	 *
	 * @since   1.0.0
	 * @updated 1.2.5
	 */
	function initialiseMouseActions() {
		//noinspection JSUnusedGlobalSymbols,JSUnusedGlobalSymbols,JSUnresolvedFunction
		interact(".resize-drag").draggable(
			{
				onmove: function (event) {
					setMouseValue($wcpA.actions.Move, event);
				}
			}).resizable(
			{
				preserveAspectRatio: false,
				edges              : {left: true, right: true, bottom: true, top: true},
				snap               : {
					targets       : [
						interact.createSnapGrid({x: 1, y: 1}),
					],
					range         : Infinity,
					relativePoints: [{x: 0, y: 0}]
				},
				onend              : function (event) {
					setMouseValue($wcpA.actions.ResizeEnd, event);
				}
			}).on("resizemove", function (event) {
			setMouseValue($wcpA.actions.Resize, event);
		});

	}

	/**
	 * Sets up the media browser (added by wp_enqueue_media in php)
	 *
	 * @since   1.0.0
	 * @updated 1.2.0
	 */
	function initialiseImageBrowser() {
		$(".wcp-browse-image").each(function () {
			$(this).on("click", function (event) {
				const self = $(this);
				let fileFrame;

				event.preventDefault();

				// Create the media frame.
				wp.media.frames.file_frame = wp.media({
					title   : self.data("uploader_title"),
					button  : {text: self.data("uploader_button_text")},
					multiple: false,
					library : {type: "image"}
				});

				fileFrame = wp.media.frames.file_frame;

				fileFrame.on("select", function () {
					const attachment = fileFrame.state().get("selection").first().toJSON();
					imageChange(self[0].id, attachment.id);
				});

				// Finally, open the modal
				fileFrame.open();
			});
		});
	}

	/**
	 * Set up color pickers
	 *
	 * @since  1.1.1
	 */
	function initialiseColorPickers() {
		$(".color-picker").wpColorPicker({change: colorChange});

	}

	/**
	 * Shows or reveals the ajax loading gif.
	 *
	 * @since   1.2.0
	 * @updated 1.2.4
	 */
	function showThrobber( show ) {
		if( typeof showThrobber.counter === "undefined" ) {
			showThrobber.counter = 0;
		}

		if (show) {
			showThrobber.counter++;
		} else if (showThrobber.counter > 0) {
			showThrobber.counter--;
		}

		throbber.toggleClass("wcp-hidden", 0 === showThrobber.counter);
	}

	/**
	 * Sets up all of the javascript actions and any other initialisation required
	 *
	 * @since   1.0.0
	 * @updated 2.0.0
	 */
	$wcpA.initialise = function () {
		const table         = $("table#line_formats");

		throbber     = $(".wcp-throbber");
		layoutSelect = $("select#layouts");
		lineManager  = new $wcpA.LineManager();

		// Get the magic number for Ajax calls
		nonce = $("#wcp_nonce").val();

		// Associate each line on table with it's LineManagerLine
		table.find("tr.format-line").each(function (rowIdx, aRow) {
			const row = $(aRow);

			lineManager.addRow(rowIdx, row);
			row.children("td").each(function (colIdx, cell) {
				// Make the input field in each cell call the update with the appropriate:
				// action - colIdx, line number - rowIdx, and the new value
				const input = $(cell.children[0]);
				input.change(function () {
					setVal(colIdx, rowIdx, $(this).val());
				});
			});
		});

		sameAsRow = $(table.find("tr.same-size")[0]);
		sameAsRow.children("td").each(function (colIdx, cell) {
			const input  = cell.children[0];
			const action = colIdx + 1; // + 1 because "Y" is a th header cell

			// Store the object for later setting if necessary
			keepSameCheckboxes[action] = input;
			// Get each checkbox to make the appropriate update
			$(input).change(function () {
				setSameAs(action, this.checked);
			});
		});


		// Change of user error message text
		$(".overflow-message").each(function () {
			$(this).on("change keyup paste cut input", changeMessage);
		});

		// Create list of layouts available
		existingLayoutNames = layoutSelect.find("option").map(function () {
			return $(this).val().toLowerCase();
		}).get();

		// Add reserved option names
		existingLayoutNames.push("settings");
		existingLayoutNames.push("ver");
		existingLayoutNames.push("db_ver");
		existingLayoutNames.push("layout");
		existingLayoutNames.push("textpaths");

		// Allows checking of "new" name to make sure no clashes with an existing layout
		$("input#new_name").on("change keyup paste cut input", changeName);

		// What to do when the selected layout changes
		layoutSelect.on("change", loadLayout).trigger("change");

		// New image selected in media browser
		$(".wcp_attachment_id").each(function () {
			$(this).on("change", imageChange);
		});

		// Format modifications
		$("#max_lines").on("change", setMaxLines);
		$("#current_lines").on("change", doFormatLines);

		// Should mouse actions be on MinFont or MaxFont
		$("[name='current_font']").each(function () {
			$(this).on("click", doChooseSetFont);
		});

		// Basic button actions
		$("#wcp_rename").on("click", renameLayout);
		$("#wcp_copy").on("click", copyLayout);
		$("#wcp_cancel").on("click", maybeCancel);
		$("#wcp_save").on("click", saveLayout);
		$("#wcp_delete").on("click", maybeDelete);
		// Now all parameters are set up, show the table
		table.removeClass("hidden");

		// Final initialisation
		initialiseMouseActions();
		initialiseImageBrowser();
		initialiseColorPickers();

		// Add meta-box handling
		// close postboxes that should be closed
		$(".if-js-closed").removeClass("if-js-closed").addClass("closed");
		// postboxes setup
		postboxes.add_postbox_toggles(pagenow);
	};
}(window.$wcpA = window.$wcpA || {}, jQuery));   // $wcpA is extended or created as needed.
                                                 // jQuery is assigned to $
                                                 // "undefined" is undefined

/**
 * Loader function
 *
 * @since 1.0.0
 */
jQuery(document).ready(function () {
	"use strict";
	window.$wcpA.initialise();
});

