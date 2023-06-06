/**
*
* Javascript file for the Commercial ProcessWire Module Media Manager (Inputfield: Image and Links RTE)
*
* @author Kongondo <kongondo@gmail.com>
*
* Copyright (C) 2015, 2017 by Francis Otieno
*
*/

function MediaManagerImageEditor($) {


    /*************************************************************/
	// SCRIPT GLOBAL VARIABLES

	/*	@note:
		- global variables NOT prefixed with '$'.
		- function parameters and variables PREFIXED with '$'
	*/

	//var someVar, anotherVar;

	/*************************************************************/
	// FUNCTIONS

	/**
     * Set cookie to remember various options.
     *
     * @param string key The name to give the cookie.
     * @param mixed value The value to give the cookie.
     *
    */
    function setCookie($key, $value) {
        document.cookie = $key + '=' + $value + ';expires=0';
    }

    /**
     *  Retrieve a cookie.
     *
     * @param string key The name of the cookie to retrieve.
     * @return keyValue or null.
     *
     */
    function getCookie($key) {
        var $keyValue = document.cookie.match('(^|;) ?' + $key + '=([^;]*)(;|$)');
        return $keyValue ? $keyValue[2] : null;
    }

	/**
	 * Get the current/on focus CKEditor in order to grab its name + set to a cookie.
	 *
	 */
	function getCurrentCKEditor () {
		if (typeof CKEDITOR !== 'undefined') {
			CKEDITOR.on('instanceReady', function(event) {
				event.editor.on( 'focus', function() {
					// set value to hidden element tracking current/on focus cke
					$('input#media_manager_current_cke').val(this.name);
				});
			});// end CKEDITOR 'instanceReady'
		}// end if CKEDITOR is defined
	}

	/**
	 * Insert link to a media in Media Manager in CKEditor
	 *
	 * @param object $insertLink Clicked element to insert link.
	 *
	 */
	function insertLinkMediaManager($insertLink) {
		var $mediaRTELink = $('a#media_rte_link');// hidden placeholder link in media-manager/link/
		if($mediaRTELink.attr('href') && $mediaRTELink.attr('href').length) {
			var $currentCKE = $insertLink.val();
			// get the current CKEditor instance
			oEditor = window.parent.CKEDITOR.instances[$currentCKE];
			var $selection = oEditor.getSelection(true);
			var $selectionText = $selection.getSelectedText();

			// @note: fix for cannot do a 'linked image bug'
			var node = $selection.getStartElement();
			var nodeName = node.getName(); // will typically be 'a', 'img' or 'p'
			if(nodeName == 'img') {
				// linked image
				var $img = jQuery(node.$);
				$existingLink = $img.parent('a');
				$selectionText = node.$.outerHTML;
			}


			// if we got a link, lets insert it
			if($mediaRTELink.attr('href') && $mediaRTELink.attr('href').length) {
				$mediaRTELink.html($selectionText);
				var $html = $("<div />").append($mediaRTELink).html();
				oEditor.insertHtml($html);
			}
		}

	}

	/**
	 * We add a hidden element to track current/onfocus CKEditor.
	 *
	 */
	function addHiddenElement() {
		$('body').append('<input id="media_manager_current_cke" type="hidden" value="0">');
	}

    /**
     * Intitialise this script.
     *
     * @note: some code originally from InputfieldImage.js.
     *
     */
	function init() {
		// hidden element to store name of current/onfucus CKEditor
		addHiddenElement();// @note: alternatively, we can store value in ProcessWire.config?
		// keep track of current instance of CKEditor
		getCurrentCKEditor();
		// the media manager pwlink in the modal
		var $mmPwLink = $("a#media_manager_pwlink");
		var $insertLink = parent.jQuery("button.pw_link_submit_insert");


		// if link to Media Manager Library in ProcessPageEditLink is clicked
		// set the name of the current CKEditor as the value of the 'Insert Link' button
		// @note: normally that button does not have a value anyway
		$($mmPwLink).on('click', function (e) {
			var $currentCKEditor = parent.jQuery("input#media_manager_current_cke").val();
			$insertLink.val($currentCKEditor);
		});

		// insert link to a media in Media Manager in the current CKEditor instance
		$($insertLink).on('click',function(){
			insertLinkMediaManager($insertLink);
		});
	}

    // initialise script
    init();

}// END MediaManager()


/*************************************************************/
// READY

jQuery(document).ready(function($) {
	MediaManagerImageEditor($);
});