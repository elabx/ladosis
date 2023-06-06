/**
*
* Javascript file for the Commercial ProcessWire Module Media Manager (Inputfield + Process)
*
* @author Kongondo <kongondo@gmail.com>
*
* Copyright (C) 2015, 2017 by Francis Otieno
* Additional code from InputfieldImage.js (C) ProcessWire
*
*/

function MediaManager($) {

	/*************************************************************/
	// SCRIPT GLOBAL VARIABLES

	/*	@note:
		- global variables NOT prefixed with '$'.
		- function parameters and variables PREFIXED with '$'
	*/

	var mediaManagerContext, nonModal, parent, galleryDataList, galleryCaption, currentPageID, formData, jfuAnywhereUploadFail, jfuAnywhereUploadSuccess, uploadAnywhere;

	var jsMediaManagerConfigs = config.MediaManager;

	uploadAnywhere = false;
	// helps to determine if uploading and inserting. Important for using JFU anywhere in a modal when inserting/adding media to inputfield. In such a case, we only upload but do not insert. Insertion follows the normal select then insert process.
	nonModal = $('div.InputfieldContent div.mm_main_wrapper').length ? true : false;

	if (!jQuery.isEmptyObject(jsMediaManagerConfigs)) {
		mediaManagerContext = jsMediaManagerConfigs.config.mediaManagerContext;
		jfuAnywhereUploadSuccess = jsMediaManagerConfigs.config.jfuAnywhereUploadSuccess;
		jfuAnywhereUploadFail = jsMediaManagerConfigs.config.jfuAnywhereUploadFail;
		uploadAnywhere = jsMediaManagerConfigs.config.uploadAnywhere;
		formData = jsMediaManagerConfigs.config.formData;
		// for repeaters sake, easier to get this from the 'mm_main_wrapper_' data-current-page attribute
		// we use that instead
		//currentPageID = jsMediaManagerConfigs.config.currentPageID;
	}

	/*************************************************************/
	// FUNCTIONS

	/**
	 * Set cookie to remember various options.
	 *
	 * @param String key The name to give the cookie.
	 * @param Mixed value The value to give the cookie.
	 *
	*/
	function setCookie($key, $value) {
		document.cookie = $key + '=' + $value + ';expires=0';
	}

	/**
	 *  Retrieve a cookie.
	 *
	 * @param String key The name of the cookie to retrieve.
	 * @return keyValue or null.
	 *
	 */
	function getCookie($key) {
		var $keyValue = document.cookie.match('(^|;) ?' + $key + '=([^;]*)(;|$)');
		return $keyValue ? $keyValue[2] : null;
	}

	/**
	 * Set checked states for selected media.
	 *
	 * Listens to clicks, double clicks and shift clicks in media selection.
	 * Double click will select all media.
	 * Shift click selects media within a range (start - end).
	 *
	 * @param Object $selected The selected element.
	 * @param Event e A Javascript click event.
	 *
	 */
	function mediaSelection($selected, e) {

		parent = $selected.parents('div.mm_main_wrapper');
		var $label = $selected.parent('label');
		var $input = $label.find("input");
		$input.prop("checked", inverseState).change();
		// @note: @kongondo changed for MM - data-value was moved to the label
		var $dv = $label.attr('data-value');
		// find related in grid/table thumbs view @todo? need this?
		var $relatedInput = parent.find('label[data-value="' + $dv + '"]').not($label).find("input");
		$relatedInput.prop("checked", inverseState).change();

		// @kongondo change for MM
		// if in rte/link mode, we init the pick-up of the link/image instead and return
		if ($selected.hasClass('rtelink')) {
			insertMediaRTE($selected);
			return;
		}

		if (e.type == "dblclick") {
			setSelectedStateOnAllItems($input);
			e.preventDefault();
			e.stopPropagation();
		}


		if ($input.is(":checked")) {
			var $prevChecked = $('input#mm_previous_selected_media');
			var $prevCheckedID = $prevChecked.val();
			// shift select
			if (e.shiftKey) {
				//e.preventDefault();
				preventNormalShiftSelection();
				// @note: prevent shift select of other text; works but there's quick flash of other selection first
				initShiftSelectCheckboxes($prevCheckedID, $input);
			}
			// change value of previous select to current selected
			$prevChecked.val($input.attr('id'));
		}

	}

	/**
	 * Implement shift+click to select range of checkboxes
	 *
	 * @param string $previousChkboxID The ID of the previously selected checkbox.
	 * @param object $currentChkbox The currently selected checkbox.
	 *
	 */
	function initShiftSelectCheckboxes($previousChkboxID, $currentChkbox) {

		var $parent = $("div.mm_thumbs:not(.mm_hide)");
		var $mediaThumbChkboxes = $parent.find("input[type='checkbox'].mm_thumb");
		var $start = $mediaThumbChkboxes.index($currentChkbox);
		var $previousChkbox = $parent.find('input#' + $previousChkboxID);
		var $end = $mediaThumbChkboxes.index($previousChkbox);
		var $shiftChecked = $mediaThumbChkboxes.slice(Math.min($start, $end), Math.max($start, $end) + 1);

		$shiftChecked.each(function () {
			 // skip start and end (already checked)
			if ($(this).is(":checked")) return;
			$(this).parent('label').find("span.mm_select").click();
		});

	}

	/**
	 * Switch between Grid and Tabular media views.
	 *
	 * @param object $a The clicked ancho to switch views.
	 * @param Int mode Whether to switch to grid(1) or tabular(2) sub-view.
	 *
	*/
	function thumbsViewSwitcher($a, $mode) {

		// return if already in the view
		if ($a.hasClass('mm_active')) return;

		parent = $a.parents('div.mm_main_wrapper');
		var $mediaManagerFieldID = parent.attr('data-media-manager-field');
		var $thumbsViewGridWrapper = parent.find('div.mm_thumbs_view_grid_wrapper');
		var $thumbsViewTabularWrapper = parent.find('div.mm_thumbs_view_tabular_wrapper');
		// Thumbs View: Switch to Grid sub-view
		if($mode == 1) {
			$thumbsViewGridWrapper.removeClass('mm_hide');
			$thumbsViewTabularWrapper.addClass('mm_hide');
			setCookie('mm_view_'+$mediaManagerFieldID, 0);
		}
		// Thumbs View: Switch to Tabular sub-view
		else if($mode == 2) {
			$thumbsViewTabularWrapper.removeClass('mm_hide');
			$thumbsViewGridWrapper.addClass('mm_hide');
			setCookie('mm_view_'+$mediaManagerFieldID, 1);
		}

		$a.siblings('a.mm_views').removeClass('mm_active');
		$a.addClass('mm_active');
	}

	/**
	 * Initiate switching views between thumbs and tabular.
	 *
	 */
	function thumbsSwitch() {
		// Thumbs View: Switch to Grid sub-view
		$(document).on('click', 'div.mm_actions_wrapper a.mm_grid_view', function (e) {
			e.preventDefault();
			thumbsViewSwitcher($(this), 1);
		});

		// Thumbs View: Switch to Tabular sub-view
		$(document).on('click', 'div.mm_actions_wrapper a.mm_tabular_view', function (e) {
			e.preventDefault();
			thumbsViewSwitcher($(this), 2);
		});

	}

	/**
	 * Show or Hide bulk actions panel depending on whether media selected.
	 *
	 */
	function showHideActionsPanel() {

		var $actionButton;
		var $items;

		// modal thumbs view: check if any media thumb checkboxes are cheched
		$items = parent.find('input.mm_thumb:checked').first();
		$actionButton = $('button#mm_action_btn_copy, button#mm_add_btn');

		// if current selections, show actions panel
		if ($items.length) {
			$actionButton.removeClass('mm_hide').fadeIn('slow');
		}
		// else hide actions panel
		else $actionButton.fadeOut('slow').addClass('mm_hide');

	}

	/**
	 * Updates outer class of item to match that of its "delete" checkbox
	 *
	 * @note: originally from InputfieldImage.js updateDeleteClass().
	 *
	 * @param $checkbox
	 *
	 */
	function updateSelectClass($checkbox) {
		if($checkbox.is(":checked")) {
			$checkbox.parents('.ImageOuter, td.mm_page_thumb').addClass("gridImage--select");
			if($checkbox.hasClass('mm_inputfield')) toggleMarkForTrashing($checkbox,true);
		} else {
			$checkbox.parents('.ImageOuter, td.mm_page_thumb').removeClass("gridImage--select");
			if($checkbox.hasClass('mm_inputfield')) toggleMarkForTrashing($checkbox,false);
		}
	}

	/**
     * Sets marked media pages in inputfield MM for trashing.
     *
     * Works in conjuction with the "delete" checkbox.
     *
     * @param object $checkbox Changed checkbox.
     * @param bool $trash If true, change the value of the MM inputfield to 0. Otherwise, set to ID_Type of the media page in the field.
     *
     */
    function toggleMarkForTrashing($checkbox, $trash) {
        parent = $checkbox.parents('div.mm_main_wrapper');
        var $dmv = $checkbox.val();
        // find the input for the mm inputfield
        var $mmInput = parent.find('li[data-media-value="' + $dmv + '"]').children('input.mm_field');
        // if removing media from mm field, change its value to 0
        if ($trash) $mmInput.val(0);
        // otherwise revert it back to the ID_Type of the media page in the mm field
        else $mmInput.val($dmv);
    }

	/**
	 * Sets the checkbox delete state of all items to have the same as that of $input
	 *
	 * @note: originally from InputfieldImage.js.
	 *
	 * @param $input
	 *
	 */
	function setSelectedStateOnAllItems($input) {
		// @note: original function name setDeleteStateOnAllItems
		var $checked = $input.is(":checked");
		var $items = parent.find('.gridImages, table.mm_thumbs_view_tabular').find('.gridImage__selectbox');
		if ($checked) $items.prop("checked", "checked").change();
		else $items.removeAttr("checked").change();
	}

	/**
	 * Helper function for inversing state of checkboxes
	 *
	 * @note: originally from InputfieldImage.js.
	 *
	 * @param index
	 * @param old
	 * @returns {boolean}
	 *
	 */
	function inverseState($index, $old) {
		return !$old;
	}

	/**
	 * Calls methods to insert media manager media in CKEditor.
	 *
	 * Inserted as either links or images.
	 *
	 * @param object $s Clicked span for denoting selected image media.
	 * @param integer $mode Whether inserting links to media or images.
	 *
	 */
	function insertMediaRTE($s) {
		$mode = $s.hasClass('rte') ? 1 : 2;
		if (1 == $mode) insertImageMediaRTE($s);
		else if(2 == $mode)	insertMediaLINK($s)
	}

	/**
	 * Initiates image media pickup for RTE in ProcessMediaManager for CKEditor.
	 *
	 * We pass the clicked span.mm_select.rtelink
	 * This is the span for denoting media selection.
	 * Redirects window location on pickup.
	 *
	 * @param object $s Clicked span for denoting selected image media.
	 *
	 */
	function insertImageMediaRTE($s) {
		// @note: .media_thumb_wrapper can be li.media_thumb_wrapper or tr.media_thumb_wrapper (in image variations/versions mode)
		var $mediaThumbWrapper = $s.parents('.media_thumb_wrapper').find('div.mm_page_thumb');
		var $imageRTE = $mediaThumbWrapper.find('a.rte');
		window.location.href = $imageRTE.attr('href');
	}

	/**
	 * Initiates media pickup for LINK in ProcessMediaManager for CKEditor.
	 *
	 * We pass the clicked span.mm_select.rtelink
	 * This is the span for denoting media selection.
	 *
	 * @param object $s Clicked span for denoting selected media.
	 *
	 */
	function insertMediaLINK($s) {
		// in link mode, we only pick one media at a time, so clear previous selection
		var $parent = $s.parents('div.mm_main_wrapper');
		var $inputs = $parent.find('input.gridImage__selectbox:checked');
		var $dataValue = $s.parent('label.gridImage__icon').attr('data-value');// to skip current selected link for clearing

		$inputs.each(function () {
			// skip current input
			if ($(this).attr('id') == 'media-' + $dataValue) return;
			// invert checked status
			$(this).prop("checked", inverseState).change();

		});

		var $mediaThumbWrapper = $s.parents('div.gridImage__hover').prev('div.mm_page_thumb');
		var $mediaLINK = $mediaThumbWrapper.find('a.link');
		var $href = $mediaLINK.attr('href');
		var $title = $mediaLINK.attr('title');

		// hidden placeholder link
		var $placeholderMediaLINK = $('a#media_rte_link');

		// update hidden placeholder link with selected media's href and title attributes on the fly
		$placeholderMediaLINK.attr('href', $href);
		$placeholderMediaLINK.attr('title', $title);

	}

	/**
	 * Initialise Blueimp Gallery.
	 *
	 * @param object $elem The object clicked to initialise gallery start.
	 *
	 */
	function initBlueimp($elem) {

		var $parent = $elem.parents('.mm_thumbs');
		// we want to start with 'clicked' image
		var $startIndex = $elem.parents('.mm_item_thumb').attr('data-gallery-index');
		// get gallery items
		var $galleryItems = $parent.find('a.mm_preview');
		// gallery container
		var $galleryContainer = $('#' + $elem.attr('data-gallery-id'));

		// get caption element
		galleryCaption = $galleryContainer.find('p.caption');

		// gallery options
		var $options = {
			// The Id, element or querySelector of the gallery widget:
			container: $galleryContainer,
			index: $startIndex,

			// callbacks
			onopen: function () {
				// Callback function executed when the Gallery is initialized.
			},
			onopened: function () {
				// Callback function executed when the Gallery has been initialized
				// and the initialization transition has been completed.
			},
			onslide: function (index, slide) {
				// Callback function executed on slide change.
				// Gallery slide event handler
				removeImageTitle(index);// remove on slide hover title
				buildGalleryMeta(index);// build slide caption
			},
			onslideend: function (index, slide) {
				// Callback function executed after the slide change transition.
			},
			onslidecomplete: function (index, slide) {
				// Callback function executed on slide content load.
			},
			onclose: function () {
				// Callback function executed when the Gallery is about to be closed.
			},
			onclosed: function () {
				// Callback function executed when the Gallery has been closed
				// and the closing transition has been completed.
			}

		};// end options

		var $gallery = blueimp.Gallery($galleryItems, $options);
		// get the gallery data list; we'll manipulate it to show captions
		galleryDataList = $gallery.list;

	}

	/**
	 * Build media meta information/caption for a gallery slide.
	 *
	 * Meta info includes: description, tags, used count, size, etc.
	 *
	 * @param integer $index The current slide number.
	 *
	 */
	function buildGalleryMeta($index) {
		// remove previous caption data
		galleryCaption.empty();
		var $templateParent = parent.find('div.mm_media_stats_template');
		// clone the list markup
		var $metaList = $templateParent.find('ul.mm_media_stats').clone();

		// ## populate meta ##

		// media type string (audio, document, image, video)
		var $mediaTypeStr = galleryDataList[$index].getAttribute('data-media-type-str');
		// description
		$metaList.find('li.mm_media_description').append(galleryDataList[$index].getAttribute('data-description'));
		// tags
		$metaList.find('li.mm_media_tags').append(galleryDataList[$index].getAttribute('data-tags'));

		// ### image media only ###
		if ('image' == $mediaTypeStr) {
			// dimensions
			$metaList.find('li.mm_dimensions').append(galleryDataList[$index].getAttribute('data-dimensions'));
			// variations count
			$metaList.find('li.mm_variationscount').append(galleryDataList[$index].getAttribute('data-variations'));
		}
		else {
			// remove dimensions markup
			$metaList.find('li.mm_dimensions').remove();
			// remove variations count
			$metaList.find('li.mm_variationscount').remove();
		}

		// filename
		$metaList.find('li.mm_filename').append(galleryDataList[$index].getAttribute('data-filename'));
		// filesize
		$metaList.find('li.mm_filesize').append(galleryDataList[$index].getAttribute('data-file-size'));
		// use count
		$metaList.find('li.mm_usedcount').append(galleryDataList[$index].getAttribute('data-usedcount'));

		// type
		var $mimeType = galleryDataList[$index].getAttribute('type');
		var $slideTitle = galleryCaption.prev('h3.title');
		// if viewing PDF, we hide the stats list + title. @todo: may make this show on demand?
		if ($mimeType == 'application/pdf') {
			$slideTitle.addClass('mm_hide')
			$metaList.addClass('mm_hide');
		}
		else {
			$slideTitle.removeClass('mm_hide')
			$metaList.removeClass('mm_hide');
		}

		// append the meta data
		galleryCaption.append($metaList);

	}

	/**
	 * Prevent selection of other text when using shift-select media range.
	 */
	function preventNormalShiftSelection() {
		document.getSelection().removeAllRanges();
		/*
		window.onload = function() {
			document.onselectstart = function() {
				return false;
			}
		}
		*/
	}

	/**
	 * Remove the title attribute of an image in the gallery.
	 *
	 * For stylistic purposes only.
	 *
	 * @param integer index The gallery slide whose image's title attribute to remove.
	 *
	 */
	function removeImageTitle(index) {
		var $elem = $("div.slide[data-index='" + index + "']").find(".slide-content");
		$elem.attr("title","");
	}

	/**
	 * Get mm main wrappers to prepare them for sortable and hoverable.
	 *
	 */
	function getWrappers() {
		var $mmWrappers = $('div.mm_main_wrapper');
		$mmWrappers.each(function(){
			var $d = $(this);
			initJFUAnywhere($d);
			// @note: in ProcessMediaManager, this content is duplicated due to the ajax
			// to prevent two bindings, we bind once and 'break'
			if (mediaManagerContext == 'ProcessMediaManager') return false
			// sortable (only in InputfieldMediaManager)
			if (mediaManagerContext == 'InputfieldMediaManager') {
				// sortable
				if ($d.hasClass('mm_single_media')) return;
				prepForSortable($d);
			}
		});
	}

	/**
	 * Prepare elements to initialise sortable on.
	 *
	 * @param object $parent Parent div with elements to make sortable.
	 *
	*/
	function prepForSortable($parent) {

		// ## - Sortable - ##

		// initialise sortable Thumb View GRID in the inputfield for the given Media Manager field
		var $thumbViewGridSort = $parent.find('ul.gridImages');
		var $parentElem = $thumbViewGridSort.parent();
		initSortable($thumbViewGridSort, $parentElem, false, 1);

		// initialise sortable Thumb View TABULAR in inputfield
		var $thumbViewTabularSort = $parent.find('table.mm_thumbs_view_tabular tbody.mm_thumbs_view_tabular');
		initSortable($thumbViewTabularSort, 'parent', 'y', 2);

	}

	/**
	 * Initialise Sortable.
	 * @param Object elem Element to make sortable.
	 * @param String containment Containment value.
	 * @param Bool|String axis Direction of sort.
	 * @param Null|Int mode Specifies whether we are also sorting tabular (1) or grid (2) sub-views.
	 *
	*/
	function initSortable($elem, $containment, $axis, $mode = null) {
		var $parent = $elem.parents('div.mm_main_wrapper');
		var $start;
		var $end;
		$($elem).sortable({
			axis: $axis,
			containment: $containment,
			tolerance: 'pointer',// @note: allows us to sort even a short table (e.g. two rows only)
			helper: fixWidthHelper,
			start: function(event, ui) {
				$start = ui.item.index();
				// placeholder set only if thumbs view: grid (saved pages/inputfield)
				if (!$elem.hasClass('mm_thumbs_view_tabular')) {
					ui.placeholder.append($("<div/>").css({
						display: "block",
						height: ui.item.height() + "px",
						width: ui.item.width() + "px"
					}));
				}
				$elem.addClass('mm_image_page_sorting');
			},
			stop: function(event, ui) {
				if($mode) {
					$end = ui.item.index();
					var $dv = ui.item.attr('data-value');
					var $diff = $start - $end;
					rePositionElement($parent, $end, $dv, $mode, $diff);
				}
				$elem.removeClass('mm_image_page_sorting');
			}

		}).disableSelection();

		// fixed width solution for sortable tables
		// @credits: https://paulund.co.uk/fixed-width-sortable-tables
		function fixWidthHelper(e, ui) {
			ui.children().each(function() {
				$(this).width($(this).width());
			});
			return ui;
		}

	}

	/**
	 * Reposition element matching moved/sorted item.
	 * Ensures when image sorted, matching table row is also sorted in the same position and vice versa.
	 *
	 * @param object $parent Main parent wrapper div whose elements to reposition.
	 * @param int $endIndex Index denoting where element was dropped.
	 * @param string $dv data-value attribute of sorted/dragged element.
	 * @param int $mode Specifies whether we are also sorting tabular (1) or grid (2) sub-views.
	 * @param int $diff Difference: denotes direction of sorted element to determine insert method.
	 *
	*/
	function rePositionElement($parent, $endIndex, $dv, $mode, $diff) {

		var $parentElem;// parent element
		var $elem;// dragged/sorted/moved element
		var $replacedElem;// element whose position is being taken by dragged element

		// in thumb view grid we also sort matching hidden thumb view tabular element
		if($mode == 1) {
			$parentElem =  $parent.find('table.mm_thumbs_view_tabular tbody.mm_thumbs_view_tabular');
			$elem = $parentElem.find('tr[data-value="' + $dv + '"]');
			$replacedElem = $parentElem.children().eq($endIndex);
		}
		// in thumb view tabular we also sort matching hidden thumb view grid element
		else if ($mode == 2) {
			$parentElem =  $parent.find('ul.gridImages');
			$elem = $($parentElem).find('li[data-value="' + $dv + '"]');
			$replacedElem = $($parentElem).children().eq($endIndex);
		}

		// move down/right (negative): insertAfter
		if($diff < 0) $elem.insertAfter($replacedElem);
		// move up/left (positive): insertBefore
		else $elem.insertBefore($replacedElem);
	}

	/* JFU ANYWHERE */
	// @NOTE: shared between ProcessMediaManager.js and InputfieldMediaManager.js

	/**
	 * Initiate JFU Upload anywhere
	 *
	 * @param object $parent The parent of the element to init.
	 */
	function initJFUAnywhere($parent) {

		var $afu = $parent.find('div.mm_thumbs_wrapper');
		currentPageID = $parent.attr('data-current-page');

		// if using jfu Anywhere, init it
		var $jfuAnwhereWrapper = $parent.find('div.jfu_upload_anywhere');
		if ($jfuAnwhereWrapper.length) {
			// send jfu Anywhere options to JFU (JqueryFileUpload.js)
			var $anywhereJfuFileUpload = {};
			$anywhereJfuFileUpload['targetElement'] = $afu;
			$anywhereJfuFileUpload['dropZone'] = $afu;
			$anywhereJfuFileUpload['filesContainer'] = $parent.find('div.jfu-anywhere-container');

			JqueryFileUpload(jQuery, $anywhereJfuFileUpload);

			$afu.fileupload();
			$afu.bind('fileuploadstop', function (e) {
				// in InputfieldMediaManager
				if (mediaManagerContext == 'InputfieldMediaManager') removeAnywhereUploadsWidget($parent)
				// in ProcessMediaManager
				else refreshLister();
			}).bind('fileuploaddone', function (e, data) {
				// process results and build notices
				processJFUAnywhere(data);
				// if in InputfieldMediaManager, update markup
				if (data.result && $.isArray(data.result.files) && currentPageID) {
					// if NOT in modal context (i.e, uploading and inserting is TRUE), update markup
					if(nonModal) InputfieldMediaManager(jQuery, data.result);
				}
			}).bind('fileuploadsubmit', function (e, data) {
				// if in inputfield, we need to send a current page and a current MM field IDs
				if (currentPageID > 0) {
					/*$input = $afu.find('input.mm_mediamanagerfield_id')
					formData['current_media_manager_field_id'] = $input.val()
					formData['current_page_id'] = currentPageID
					*/
					// @note: easier this way for repeaters sake
					formData['current_media_manager_field_id'] = $parent.attr('data-media-manager-field');
					formData['current_page_id'] = currentPageID;
				}
				data.formData = formData;
				//data['formData'] = formData;
				}).bind('fileuploadstart', function (e) {
			});

			jfuDropZoneHighlight($afu)
			// @note: for debugging ONLY
			//jfuCallBacks($afu)
		}

	}

	/**
	 * Highlight the dropzone area on hover.
	 *
	 * This is for JFU.
	 *
	 * @param object $elem The element to highlight.
	 *
	 */
	function jfuDropZoneHighlight($elem) {
		var counter = 0;
		$elem.bind({
			dragenter: function(e) {
				e.preventDefault(); // needed for IE
				counter++;
				$(this).addClass('jfu_anywhere_upload_drop_hover');
			},
			dragleave: function() {
				counter--;
				if (counter === 0) {
					$(this).removeClass('jfu_anywhere_upload_drop_hover');
				}
			},
			drop: function () {
				$(this).removeClass('jfu_anywhere_upload_drop_hover');
			}
		});
	}

	/**
	 * Process actions after JFU Anywhere upload.
	 *
	 * Mainly initiate building of notices.
	 *
	 * @param object $data The data from the Ajax response.
	 *
	 */
	function processJFUAnywhere($data) {

		// @note: FROM JFU SIDE
		var $successUploadsCount = $data.result.count_success;
		var $successNotice = '';
		var $failUploadsCount = $data.result.count_fail;
		var $failNotice = '';
		var $currentPageID = $data.result.currentPageID;
		var $mediaManagerFieldID = $data.result.mediaManagerFieldID;
		// @note: selector should be in the format 'div#mm_main_wrapper_1234_567' where 1234=currentPageID and 567=mediaManagerFieldID
		var $parent = $('div#mm_main_wrapper_' + $currentPageID + '_' + $mediaManagerFieldID);

		// successful uploads
		if ($successUploadsCount) {
			$successNotice += jfuAnywhereUploadSuccess + ' (' + $successUploadsCount + ').';
			buildNotice($parent, $successNotice, 1, 3500);
		}

		// failed uploads
		if($failUploadsCount) {
			var $failedArray = [];
			$.each($data.result.files, function ($index, $arr) {
				if ($arr.hasOwnProperty('error')) {
					$failedArray.push($data.originalFiles[$index]);
				}
			});
			// @todo: error here; 'name not defined'
			$.each($failedArray, function ($index, $arr) {
				//$failNotice += $arr.name + ', ';
			});

			// trim space and ',' at the end
			$failNotice = $failNotice.replace(/,\s*$/, "");
			$failNotice = jfuAnywhereUploadFail + ' (' + $failNotice + ').';
			buildNotice($parent, $failNotice, 2, 4500);

		}

		// if we were upload and creating media pages as well
		if ($data.result.hasOwnProperty('message')) {
			var $message = $data.result.message;
			var $noticeType = 1;
			var $delay = 3500;
			if ($message == 'error') {
				$noticeType = 2;
				$delay = 4500;
			}

			buildNotice($parent, $data.result.notice, $noticeType, $delay);
			// error message in case of mixed success and error message
			if ($data.result.hasOwnProperty('notice2')) {
				buildNotice($parent, $data.result.notice2, 2, 4500);
			}
		}

	}

	/**
	 * Build custom system notification for use with Ajax responses.
	 *
	 * @param object $parent The parent element of the messages wrapper element.
	 * @param string $notice The success or error message to display.
	 * @param int $type Determines whether to render a success vs an error message.
	 * @param int $delay Message delay.
	 *
	*/
	function buildNotice($parent, $notice, $type, $delay = null) {

		// @todo: currently, we duplicate this here and in processmm.js! @refactor later

		var $elem, $class, $message, $messageIcon, $messageWrapper, $messageText;
		$delay = $delay ? $delay : 3500;

		// grab the element to show notice on
		$elem = $parent.find('ul.mm_message_wrapper');
		$class = 'NoticeMessage NotificationGhost uk-alert-primary';
		$iconClass = 'fa fa-fw fa-check-square-o';
		if ($type == 2) {
			$class = 'NoticeError NotificationGhost uk-alert-danger';
			$iconClass = 'fa fa-fw fa-bug';
		}

		$messageWrapper = $('<li class="mm_message"></li>');
		$messageIcon = '<i class="'+$iconClass+'"></i>';
		$messageText = $('<div class="' + $class + '">' + $messageIcon + $notice + '</div>');
		$message = $messageWrapper.append($messageText);

		// append the message + set fade-in/out times
		$($elem).append($message).find('li.mm_message').fadeIn('fast').delay($delay).fadeOut('slow', function(){
			$(this).remove();
		});

	}

	/**
	 * Refresh Lister results.
	 *
	 */
	function refreshLister() {
		// @todo: currently, we duplicate this here and in processmm.js! @refactor later
		$("#_ProcessListerRefreshTab").click();
	}

	/**
	 *  Removes the uploads widget after upload complete.
	 *
	 *  Used by JFU Anywhere.
	 *
	 * @param object $parent The element that is the parent of the widget.
	 *
	 */
	function removeAnywhereUploadsWidget($parent) {
		$parent.find('ul.jfu_upload_anywhere_widget_info_list').children().fadeOut(500).remove()
		$parent.find('div.jfu_upload_anywhere_widget').css({ visibility: "hidden", opacity: "0" });
	}

	/**
	 * Close jQuery UI Modal.
	 *
	 * @param Integer s Number of milliseconds before closing modal.
	 *
	 */
	function closeDialogFromParent($s = 1000) {
		// @todo: we duplicate this here! Also used in InputfieldMM. Consider refactoring
		setTimeout(function() {
			window.parent.jQuery('iframe.ui-dialog-content').dialog('close');
		}, $s);
	}


	/**** DEBUGGING - CALLBACKS****/
	function jfuCallBacks($elem) {

		$elem
			.bind('fileuploadadd', function (e, data) {
			})
			.bind('fileuploadsubmit', function (e, data) {
			})
			.bind('fileuploadsend', function (e, data) {
			})
			.bind('fileuploaddone', function (e, data) {
			})
			.bind('fileuploadfail', function (e, data) {
			})
			.bind('fileuploadalways', function (e, data) {
			})
			.bind('fileuploadprogress', function (e, data) {
			})
			.bind('fileuploadprogressall', function (e, data) {
			})
			.bind('fileuploadstart', function (e) {
			})
			.bind('fileuploadstop', function (e) {
			})
			.bind('fileuploadchange', function (e, data) {
			})
			.bind('fileuploadpaste', function (e, data) {
			})
			.bind('fileuploaddrop', function (e, data) {
			})
			.bind('fileuploaddragover', function (e) {
			})
			.bind('fileuploadchunkbeforesend', function (e, data) {
			})
			.bind('fileuploadchunksend', function (e, data) {
			})
			.bind('fileuploadchunkdone', function (e, data) {
			})
			.bind('fileuploadchunkfail', function (e, data) {
			})
			.bind('fileuploadchunkalways', function (e, data) {
			})
			.bind('fileuploaddestroy', function (e, data) {
			})
			.bind('fileuploaddestroyed', function (e, data) {
			})
			.bind('fileuploadadded', function (e, data) {
			})
			.bind('fileuploadsent', function (e, data) {
			})
			.bind('fileuploadcompleted', function (e, data) {
			})
			.bind('fileuploadfailed', function (e, data) {
			})
			.bind('fileuploadfinished', function (e, data) {
			})
			.bind('fileuploadstarted', function (e) {
			})
			.bind('fileuploadstopped', function (e) {
			});
	}

	/**
	 * Intitialise this script.
	 *
	 * @note: some code originally from InputfieldImage.js.
	 *
	 */
	function init() {

		thumbsSwitch();
		// initJFU Anywhere for InputfieldMediaManager && ProcessMediaManager
		// prepare for multiple jfu file upload elements (in case several MM Inputfields on a page)
		getWrappers()

		if (mediaManagerContext == 'InputfieldMediaManager') {
			// for repeaters (ajax)
			$(document).on('loaded reloaded opened openReady repeateradd', function () {
				// give repeater item time to load
				setTimeout(function(){
					getWrappers();
					nonModal = $('div.InputfieldContent div.mm_main_wrapper').length ? true : false;
				},700);
			});
		}
		// ProcessMediaManager Context
		else {
			$(document).on('loaded', function () {
				getWrappers()
			});
		}

		// change of "delete/selected" status for an item event
		$(document).on("change", ".gridImage__selectbox", function() {
			updateSelectClass($(this));
			parent = $(this).parents('div.mm_main_wrapper');
			showHideActionsPanel();
		});
		// click or double click select/trash event
		// @note: was 'gridImage__trash' in original
		//$(document).on('click dblclick', '.gridImage__icon', function (e) {
		$(document).on('click dblclick', '.mm_select', function (e) {
			e.preventDefault();
			e.stopPropagation();
			mediaSelection($(this),e);
		});

		// stop even bubbling on click variations/versions anchor and edit link to prevent gallery preview
		$(document).on('click', '.mm_insert_extra_images, .edit_pages', function (e) {
			e.stopPropagation();
		});
		/*
			@note: for blueimp gallery
			- listen to div.gridImage_inner click to fire blueimp gallery for media
			- enables us to declutter the thumb icons (no need for preview icon)
			- also note, we make sure on click .mm_select icon above is not interfered with
		*/
		$(document).on('click', 'div.gridImage__inner:not(.rtelink)', function (e) {
			e.stopPropagation();
			parent = $(this).parents('div.mm_main_wrapper');
			// init blueimp gallery
			initBlueimp($(this));
		});

		// trashing a media page in a modal: we need to remove the media from the inputfield as well
		// if not in 'back to all media' context, we also close the modal
		$(document).on('click', 'button#submit_delete', function () {
			var $backToAllMedia = $('a#mm_back_to_all_media')
			var $confirm = $('input#delete_page')
			var $deleteMediaID = $('button#submit_save_copy').attr('data-delete');
			// if media page definitely getting trashed, remove deleted if found in inputfield MM parent window
			if ($confirm.prop('checked')) {
				$('[data-delete="'+$deleteMediaID+'"]', window.parent.document).remove();
				// if not in 'back to all media' context, close modal window quickly (otherwise we are late and modal reloads)
				if($backToAllMedia.length == 0) closeDialogFromParent(100)
			}
		});

	}

	// initialise script
	init();

}// END MediaManager()


/*************************************************************/
// READY

jQuery(document).ready(function($) {
	MediaManager($);
});