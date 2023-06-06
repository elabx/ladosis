/**
*
* Javascript file for the Commercial ProcessWire Module Media Manager (Image Editor [cropping/resizing])
*
* @author Francis Otieno (Kongondo) <kongondo@gmail.com>
*
* Copyright (C) 2015 by Francis Otieno
*
*/

// hooking into ProcessPageEditImageSelect and ProcessPageEditLink
$(document).ready(function(){


	/********************* ProcessPageEditImageSelect ***********************/


	/** 1. refresh lister in parent page (Media Manager single image media edit) to show new variation (crop or replace) **/
	$("button").click(function() {
		var name = $(this).attr('name');
		if(name === 'submit_save_copy' || name === 'submit_save_replace') {
			parent.refreshLister();// refresh lister in parent window
			parent.closeDialog();// close this jquery UI dialog after 1 second delay from within the parent window
		}
	});

	/** 2. in modal after click 'Select Another Image' we add a link to select from Media Manager **/
	/* @note: no longer need this
	// we get and clone the 'Upload Image' button (in the modal)
	var b = $("div#ProcessPageEditImageSelect button[name='button']");

	// if we are in replace image context (in PW that would be the modal that opens after selecting 'Select Another Image')
	if(b.length) {
		// a. get the button's parent then clone it
		var bp = b.parent();
		var bpClone = bp.clone();

		// b. in the clone, find the span with the text of the link

				//- change the text to show the link will open Media Manager Library
				//- we get the translatable string set in MediaManagerImageEditor::mmImageEditorConfigs()

		bpClone.find('span.ui-button-text').text(config.MediaManagerImageEditor.config.buttontext);

		// c. in the clone, find the button link to style it
		bpClone.children('button.ui-button').css({"margin-top":"1em"});

		// d. create the link to Media Manager Library

		// we want a link like this one: config.urls.admin + 'media-manager/rte/image/?id=1326&edit_page_id=1108&modal=1';
		var mediaManagerRTELink = config.urls.admin + 'media-manager/rte/image/?';
		// get the cloned 'href' and extract the query string
		var href = bpClone.attr('href');
		var queryString = href.split('?').pop();
		//  change the 'href' and append the query string to point to Media Manager Library
		bpClone.attr('href', mediaManagerRTELink + queryString);
		// insert the cloned and amended button
		bpClone.insertAfter(bp);


	}*/

	/********************* ProcessPageEditLink ***********************/

	/** 1. functions **/

	// set cookie to remember the current CKEditor instance
	setCookie = function(key, value) {
		document.cookie = key + '=' + value + ';expires=0';
	}

	// get cookie we set that has the name of the current CKEditor
	getCookie = function(key) {
		var keyValue = document.cookie.match('(^|;) ?' + key + '=([^;]*)(;|$)');
		return keyValue ? keyValue[2] : null;
	}

	// get the current/on focus CKEditor in order to grab its name + set to a cookie
	getCurrentCKEditor = function() {
		if (typeof CKEDITOR !== 'undefined') {
			CKEDITOR.on('instanceReady', function(event) {
				event.editor.on( 'focus', function() {
					//console.log('focused', this);
					//setCookie('media_manager_current_cke', this.id);
					setCookie('media_manager_current_cke', this.name);
				});
			});// end CKEDITOR 'instanceReady'
		}// end if CKEDITOR is defined
	}

	// insert link to a media in Media Manager in CKEditor
	insertLinkMediaManager = function(insertLink) {
		var mediaRTELink = $('a#media_rte_link');// hidden placeholder link in media-manager/link/
		if(mediaRTELink.attr('href') && mediaRTELink.attr('href').length) {
			var currentCKE = insertLink.val();
			// get the current CKEditor instance
			oEditor = window.parent.CKEDITOR.instances[currentCKE];
			var selection = oEditor.getSelection(true);
			var selectionText = selection.getSelectedText();
			// if we got a link, lets insert it
			if(mediaRTELink.attr('href') && mediaRTELink.attr('href').length) {
				mediaRTELink.html(selectionText);
				var html = $("<div />").append(mediaRTELink).html();
				oEditor.insertHtml(html);
			}
		}

	}

	/** 2. keep track of current instance of CKEditor **/
	getCurrentCKEditor();

	var mmPwLink = $("a#media_manager_pwlink");
	var insertLink = parent.jQuery("button.pw_link_submit_insert");

	/** 3. if link to Media Manager Library in ProcessPageEditLink is clicked **/
	// set the name of the current CKEditor as the value of the 'Insert Link' button
	// @note: normally that button does not have a value anyway
	$(mmPwLink).on('click',function(){
		insertLink.val(parent.getCookie('media_manager_current_cke'));
	});

	/** 4. insert link to a media in Media Manager in the current CKEditor instance **/
	$(insertLink).on('click',function(){
		insertLinkMediaManager(insertLink);
	});

});