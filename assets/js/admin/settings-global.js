/**
 * Created by Dave on 24/08/16.
 *
 * @since   1.2.0
 * @updated 1.2.6
 */

/* JSHint/JSLint set up */
/*global ajaxurl      */  // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
/*global postboxes    */
/*global pagenow      */

//noinspection AssignmentResultUsedJS,JSUnusedLocalSymbols,JSLint
(function ($wcpA, $, undefined) { // undefined is declared but not provided so that it IS undefined.
	// undefined can be assigned another value by malicious code.
	"use strict";

	//noinspection MagicNumberJS,LocalVariableNamingConventionJS
	let nonce;
	const tab = "global";

	/**
	 * Ajax call to save the any checkbox options
	 *
	 * @param  event
	 *
	 * @since   1.2.0
	 * @updated 1.2.6
	 */
	function saveCheckboxValue(event) {
		const data = {
			"wcp-nonce": nonce,
			"tab"      : tab,
			"value"    : event.target.checked ? "yes" : "no",
		};

		if ("save_on_delete" === event.target.id) {
			data["action"] = "post_plugin_delete";
		}

		if ("display_ie_msg" === event.target.id) {
			data["action"] = "post_display_ie_msg";
		}
		//noinspection JSCheckFunctionSignatures
		$.post(ajaxurl, data);
	}

	/**
	 * Sets up all of the javascript actions and any other initialisation required
	 *
	 * @since   1.0.0
	 * @updated 1.2.0
	 */
	$wcpA.initialise = function () {
		// Get the magic number for Ajax calls
		nonce = $("#wcp_nonce").val();

		$("#save_on_delete").on("click", saveCheckboxValue);

		$("#display_ie_msg").on("click", saveCheckboxValue);


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
 * @since   1.0.0
 * @updated 1.0.0
 */
jQuery(document).ready(function () {
	"use strict";
	window.$wcpA.initialise();
});

