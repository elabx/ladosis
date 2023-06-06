/**
*
* Javascript file for the Commercial ProcessWire Module Media Manager (Process)
*
* @author Kongondo <kongondo@gmail.com>
*
* Copyright (C) 2015 by Francis Otieno
*
*/

function ProcessMediaManager($) {

	/*************************************************************/
	// SCRIPT GLOBAL VARIABLES

	/*	@note:
		- global variables NOT prefixed with '$'.
		- function parameters and variables PREFIXED with '$'
	*/

	var currentPageID, mediaManagerFieldID, ajaxURL, noSelection, noTag, unknownError, jsMediaManagerConfigs, deleteFail, moveToLibraryFail;

	// set values to some variables
	jsMediaManagerConfigs = processMediaManagerConfigs();

	if(jsMediaManagerConfigs) {
		currentPageID = jsMediaManagerConfigs.config.currentPageID;
		mediaManagerFieldID = jsMediaManagerConfigs.config.mediaManagerFieldID;
		ajaxURL = jsMediaManagerConfigs.config.ajaxURL;
		unknownError = jsMediaManagerConfigs.config.unknownError;
		noSelection = jsMediaManagerConfigs.config.noSelection;
		noTag = jsMediaManagerConfigs.config.noTag;
		deleteFail = jsMediaManagerConfigs.config.deleteFail;
		moveToLibraryFail = jsMediaManagerConfigs.config.moveToLibraryFail;
	}

	/*************************************************************/
	// FUNCTIONS

	/**
	 * Spinner for UX.
	 *
	 * @param string $mode mode Whether to show or hide spinner.
	 *
	 */
	function spinner($mode) {
		var $s = $("#insert_into_media_manager_field_spinner");
		if($mode =='in') $s.fadeIn();
		else $s.fadeOut('slow');
	}

	/**
	 * Clear selected items and other inputs after ajax actions.
	 *
	 */
	function resetMediaManagerView() {

		// reset selected media
		$("input[name='mm_selected_media'], input[name='mm_select_all']").each(function() {
			this.checked = false;
			$(this).parents('li.ImageOuter').removeClass('gridImage--select');
			$('a.mm_action_modal').fadeOut('normal');
		});

		// reset action selections
		$('select#mm_action_select').val('');

		// reset tags inputs and elements
		$('div#mm_tags_input_wrapper').hide();
		$("input[name='mm_action_tags']").val('');
		$("input[name='mm_tag_mode']").prop("checked", false);

		// hide action apply button
		$('button#mm_action_btn_copy').addClass('mm_hide');

	}

	/**
	 * Build custom system notification for use in PAGE selection modal.
	 *
	 * @param string $notice The success or error message to display.
	 * @param int $type Determines whether to render a success vs an error message.
	 * @param int $delay Message delay.
	 *
	*/
	function buildNotice($notice, $type, $delay=null) {

		var $elem, $class, $message, $messageIcon, $messageWrapper, $messageText;

		$delay = $delay ? $delay : 3500;

		// grab the element to show notice on
		$elem = $('div#content ul.mm_message_wrapper');
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
		$("#_ProcessListerRefreshTab").click();
	}

	/**
	 * Reset Lister.
	 *
	 */
	function resetFilters() {
		$("#_ProcessListerResetTab").click();
	}

	/**
	 * Process actions on media.
	 *
	 * Bulk actions: un/publish, un/lock, un/tag, trash/delete selected media.
	 *
	*/
	function processAjax($btn) {

		var $parent = $btn.parents('div.mm_main_wrapper');
		var $selectedAction = '';
		var $media = [];
		// @note: to avoid duplicates, just get from one view (grid) rather than also tabular
		var $selectedMedia;
		// insert variations mode
		if ($btn.attr('id') == 'mm_add_variations_btn') {
			$selectedMedia = $parent.find("table#mm_extraimages_and_variations input[name='mm_selected_media']:checked");
		}
		// other modes
		else $selectedMedia = $parent.find("div.mm_thumbs:not(.mm_hide) input[name='mm_selected_media']:checked");

		// push selected pages in array
		$selectedMedia.each(function () {
			$media.push($(this).val());
		});

		if ($media.length === 0) {
			// @note: not too important since they will not be able to see apply button without selecting at least one media
			buildNotice(noSelection, 2);
			spinner('in');
			spinner('out');
			// clear selections
			resetMediaManagerView();
			return false;
		}

		$id = $btn.attr('id');

		// bulk action mode
		if ('mm_action_btn' == $id) {
			var $tags = '';
			var $tagMode = '';
			$selectedAction = $parent.find('select#mm_action_select').val();
			if ($selectedAction === '') {
				$message = noSelection;
				buildNotice($message, 2);
				spinner('in');
				spinner('out');
				return false;
			}

			if ($selectedAction == 'tag') {
				$tags = $parent.find("input[name='mm_action_tags']").val();
				// error, no tags entered
				if ($tags == '') {
					buildNotice(noTag, 2);
					return false;
				}
				// find the value of the radio of the selected tag mode
				$tagMode = $('input[name=mm_tag_mode]:checked', $parent).val();
			}


		}// END if bulk action

		// insert in inputfield mode
		else {
			$selectedAction = 'insert';
		}

		// ajax
		$.ajax({
			url: ajaxURL,// pwadmin/mm/ajax/
			type: 'POST',
			/* data: {'currentPageID': currentPageID, 'mediaManagerFieldID': mediaManagerFieldID, 'selectedPages': $media, action: $action}, */
			data: {
				current_page_id: currentPageID,
				current_media_manager_field_id: mediaManagerFieldID,
				media: $media,
				action: $selectedAction,
				tags: $tags,
				tag_mode: $tagMode,
			},
			dataType: 'json',
			beforeSend: spinner('in'),
		})
			.done(function ($data) {
				if ($data.message == 'success') {
					// success
					buildNotice($data.notice, 1);
					// if also some errors
					if ($data.hasOwnProperty('notice2')) buildNotice($data.notice2, 2, 4500);
					refreshLister();// refresh results
					// clear selections
					resetMediaManagerView();
					// if inserting media, update markup in the parent window (the Inputfield MM)
					if ('insert' == $data.action) {
						window.parent.InputfieldMediaManager(window.parent.jQuery, $data);
					}
				}

				// error
				else {
					buildNotice($data.notice, 2);
				}

			})
			// fail
			.fail(function () { buildNotice(unknownError, 2) });
			// refresh button (remove 'ui-state-active') (only need for default theme)
			// @todo: still needed? we are hiding the button anyway
			// if (!$(this).hasClass('Reno')) $(this).button().button('refresh');

			spinner('out');

	}

	/**
	 * Sends request to dynamically updates parent window after ajax action in modal.
	 *
	 */
	function updateParentWindowMarkupAjax($data) {
		// @note: hald a second wait to allow save page post to be executed first
		// @todo time OK? Any higher and we miss it! So, we leave like this for now
		setTimeout(function () {
			window.parent.InputfieldMediaManager(window.parent.jQuery, $data);
		}, 250);
	}

	/**
	 * Process Single Page Edit for updating InputfieldMediaManager Markup.
	 *
	 * Called when form#ProcessPageEdit is submitted.
	 *
	 * @param object $parent Wrapper div for modal single page edit
	 */
	function singlePageEdit($parent) {

		// get the div wrapper for modal single page edit with MM actions and inputs
		// hidden inputs always available in single page edit modal (i.e., even after save/reload)
		currentPageID = $parent.children('input#currentPageID').val();// page ID of the current page with the MM inputfield
		mediaManagerFieldID = $parent.children('input#mediaManagerFieldID').val();// the ID of the current Media Manager field in the current page
		editMediaID = $parent.children('input#editMediaID').val();// the ID of the media page saved in the current field and currently being edited in the single page edit modal

		// prepare $data object to pass to parent window
		var $data = {};
		$data.ajaxURL = ajaxURL;
		$data.currentPageID = currentPageID;
		$data.mediaManagerFieldID = mediaManagerFieldID;
		// @note: the saved page in this mm field being edited in a modal
		$data.editMediaID = editMediaID;
		$data.action = 'edit'
		updateParentWindowMarkupAjax($data);
	}

	/**
	 * Get the configs sent by the module ProcessMediaManager.
	 *
	 * We use these mainly for our custom notices function.
	 *
	 * @return Object|false jsMediaManagerConfigs Return configurations if found, else false.
	 *
	*/
	function processMediaManagerConfigs(){
		// ProcessMediaManager configs
		var jsMediaManagerConfigs = config.MediaManager;
		if (!jQuery.isEmptyObject(jsMediaManagerConfigs)) return jsMediaManagerConfigs;
		else return false;
	}

	/**
	 * Quick add 'clicked' page and close modal.
	 *
	 * Used only in certain cases such as quick page mode.
	 *
	 * @param String selStr selector string of button to click.
	 *
	 */
	function clickButton($selStr) {
		$($selStr).click();
	}

	/**
	 * Set cookie to remember various options.
	 *
	 * @param string key The name of the cookie.
	 * @param string value The value of the cookie.
	 *
	 */
	function setCookie(key, value) {
		document.cookie = key + '=' + value + ';expires=0';
	}

	/**
	 * Remove a given previously set cookie.
	 *
	 * @param string key The name of the cookie to remove.
	 *
	 */
	function removeCookie(key) {
		document.cookie = key + '=;expires=Thu, 01-Jan-70 00:00:01 GMT;';
	}

	/**
	 * Remove duplicate markup added by ProcessPageLister ajax results.
	 *
	 * @param integer $mode Determines if removing duplicates on normal vs ajax load. 1 = normal page load.
	 */
	function removeDuplicates($mode = 2) {
		var $listerResults = $('div#ProcessListerResults');
		/* Remove ajax-duplicated media manager menu: uploads page (Temporary solution) */
		$listerResults.find('div#mm_top_panel, div#processmediamanager-blueimp-gallery').remove();
		$('div.mm_top_panel').show();
		if($mode == 1) $('div#pw-content-body').children('div#mm_main_wrapper_0_0').attr('id','fake');
	}

	/**
	 * Initialise WireTabs
	 *
	 */
	function initTabs() {
		var $form = $("#fileupload");
		// remove scripts, because they've already been executed since we are manipulating the DOM below (WireTabs)
		// which would cause any scripts to get executed twice
		$form.find("script").remove();
		$form.WireTabs({
			items: $(".WireTab"),
			skipRememberTabIDs: ['MediaManagerTabsHelp'],
			rememberTabs: true
		});

	}

	/**
	 * Display/hide 'tags' input on selection/deselection of 'Tag' action.
	 *
	 */
	function toggleShowTagsInput() {
		$(document).on('change', 'select#mm_action_select', function () {
			var $option = $(this).find('option:selected').val();
			var $tagsWrapper = $('div#mm_tags_input_wrapper')
			if ($option === 'tag') $tagsWrapper.animate({
				'height': 'show'
			}, 500);
			else $tagsWrapper.animate({
				'height': 'hide'
			}, 300);
		});
	}

	/**
	 * Sort lister results as per user input.
	 *
	 */
	function liveSortListerResults() {

		$('select#mm_live_sort_action_select').change(function() {
			liveSort($(this));
		});
		$('input#media_live_sort_order').change(function() {
			// set sort order cookie if checked else remove cookie
			if ($(this).prop('checked')) setCookie($(this).attr('id'), 2);
			else setCookie($(this).attr('id'), 1);
			refreshLister();
		});
	}

	/**
	 * Live sort: sort
	 *
	 * @param string Sort criteria.
	 *
	 */
	function liveSort($sort) {
		var $selectedSort = $sort.val();
		if ($selectedSort.length) {
			// grab sort order first + set its cookie
			liveSortOrder(1);
			// set sort criteria
			setCookie('media_live_sort', $selectedSort);
		} else {
			// remove sort criteria and order
			removeCookie('media_live_sort');
			liveSortOrder(0);
		}
		refreshLister();
	}

	/**
	 * Live sort: sort order
	 *
	 * @param integer $mode Descending vs Ascending sort order.
	 *
	 */
	function liveSortOrder($mode) {
		var $sortOrderStr = "media_live_sort_order";
		var $sortOrderChkbx = $('input#' + $sortOrderStr);
		if ($mode == 1) {
			$sortOrderChkbx.parent().removeClass('hide');
			if ($sortOrderChkbx.prop('checked')) setCookie($sortOrderStr, 2);
			else setCookie($sortOrderStr, 1)
		} else {
			$sortOrderChkbx.parent().addClass('hide');
			removeCookie($sortOrderStr);
		}
	}

	/**
	 * Confirm Overwrite of Media (only in use if 1 or 2. media overwrite setting in use) checkbox.
	 *
	 */
	function confirmOverwrite() {
		/*
			if confirm media overwrite is in effect...
			we hide 'start uploads' & 'add to media library' buttons until confirm checkbox is ticked
		*/
		$('form#fileupload').on('click', 'input#mm_confirm_overwrite_duplicate_media', function(e) {
			if ($(this).prop('checked')) $('form#fileupload').removeClass('mm_confirm_media_overwrite');
			else $('form#fileupload').addClass('mm_confirm_media_overwrite');
		});
	}

	/**
	 * Initialise file uploads delete.
	 *
	 * @note: similar to the one we use in jqueryFileUploads
	 *
	 */
	function deleteUploads($delete, $scanMode =false,) {

		// @todo: need a spinner here?
		var $tbody, $delete;
		if (!$scanMode) {
			$tbody = $('tbody#files');
		}
		else {
			$tbody = $('tbody#scan-files');
		}

		var $selectedMedia = $tbody.find("input.uploaded_file:checked");

		if (!$selectedMedia.length) {
			// @todo: error >>. need to select files to delete + translateable string!
			return;
		}

		var $files = [];
		$selectedMedia.each(function () {
			if (!$scanMode) $fileName = $(this).parent().attr('data-file');
			else  $fileName = $(this).parents('tr').find('td.mm-scan-file-name').attr('data-path');
			// push selected media in array
			$files.push($fileName);
			// remove the table row
			$(this).closest('tr').find('td').fadeOut(500,
				function(){
				$(this).parents('tr:first').remove();
			});

		});

		// ajax
		$.ajax({
			url: ajaxURL,
			type: 'POST',
			data: {'jfu_files':$files, 'jfu_delete': $delete},
			dataType: 'json',
			beforeSend: scanSpinner('in'),
		}).done(function () {
			resetScanTableRows()
			scanSpinner('out');
		}).fail(function() {  alert(deleteFail); })
	}

	/**
	 * Initialise file uploads delete.
	 *
	 * @note: similar to the one we use in jqueryFileUploads
	 *
	 */
	function moveUploadsToMediaManager($moveMode='unpublished', $scanMode=false) {
		var $tbody, $scan;
		if (!$scanMode) {
			$tbody = $('tbody#files');
			$scan = 0;
		}

		else {
			$tbody = $('tbody#scan-files');
			$scan = 1;
		}

		var $selectedMedia = $tbody.find("input.uploaded_file:checked");

		if (!$selectedMedia.length) {
			// @todo: error >>. need to select files to delete + translateable string!
			return;
		}

		var $files = [];
		$selectedMedia.each(function () {
			$fileName = $(this).parent().attr('data-file');
			// push selected media in array
			$files.push($fileName);
			// remove the table row
			$(this).closest('tr').find('td').fadeOut(500,
				function(){
				$(this).parents('tr:first').remove();
			});

		});

		if (!$files.length) {
			// @todo: error out (just in case)
			return
		}

		// ajax
		$.ajax({
			url: ajaxURL,
			type: 'POST',
			data: {'jfu_files':$files, 'jfu_move':$moveMode, 'scan':$scan},
			dataType: 'json',
			beforeSend: scanSpinner('in'),
		}).done(function () {
			resetScanTableRows()
			scanSpinner('out');
		}).fail(function () { alert(moveToLibraryFail); })
	}

	/**
	 * Display a spinner for scanning media ajax actions.
	 *
	 */
	function scanSpinner($mode) {
		if($mode =='in') $("#scan_files_spinner").removeClass('mm_hide');
		else $("#scan_files_spinner").fadeOut('slow').addClass('mm_hide');
	}

	/** Resets the rows count in the first td of thumbs view: tabular.
	 *
	 * Applies only to multi page fields.
	 * @note: need a bit of a delay for ajax and other events to finish
	 */
	function resetScanTableRows() {
		setTimeout(function() {
			var $tableRows = $('table#mm_scan_list tbody').children('tr');
			var $rowsCount = $tableRows.size();
			$('p#mm_scan_total_files span').text($rowsCount);
			var $cnt = 1;
			$.each($tableRows, function () {
				$(this).children('td:first').text($cnt);
				$cnt++;
			});
		}, 500);
	}

	/**
	 * Initialise file uploads list toggle select/deselect all.
	 *
	 */
	function toggleSelectAll() {
		var $table = $('table#mm_scan_list, table#mm_filter_profiles_list');
		$table.on('change', 'input.toggle_all', function () {
			if ($(this).prop('checked')) $table.find('input.toggle').prop('checked', true);
			else $table.find('input.toggle').prop('checked', false);
		});
	}

	/**
	 * select multiple checkboxes in a range use SHIFT+CLick
	 *
	 */
	function shiftClickSelectCheckboxes($table) {
		//@awt2542 PR #867 for PROCESSWIRE
		var $lastChecked = null;
		$($table).on('click', 'input[type=checkbox].mm_table_item', function(e) {
			var $checkboxes = $(this).closest($table).find('input[type=checkbox].mm_table_item');
			if(!$lastChecked) {
				$lastChecked = this;
				return;
			}
			if(e.shiftKey) {
				var $start = $checkboxes.index(this);
				var $end = $checkboxes.index($lastChecked);
				$checkboxes.slice(Math.min($start,$end), Math.max($start,$end)+ 1).attr('checked', $lastChecked.checked);
			}
			$lastChecked = this;
		});
	}

	/** Move previously uploaded files to media manager.
	 *
	 * These are files in the temporary listable uploads directory.
	 *
	 */
	function processMoveUploadsToMediaManager($jfuContainer) {

		var $jfuUploadsAction = $jfuContainer.find('span#jfu_uploads_action');
		var $actionValueInput = $('input#jfu_upload_actions_btns_dropdown_value');
		var $actionValue = $actionValueInput.val()

		// action: start upload
		if (!$actionValue || $actionValue == 0) {
			// click the jfu actions start element
			$jfuUploadsAction.children('span#jfu_uploads_action_start').click();
		}
		// action: cancel/delete/add to media library + publish/unpublished
		else {
			// action: cancel uploads
			if ($actionValue == 'cancel') {
				// click the jfu actions cancel element
				$jfuUploadsAction.children('span#jfu_uploads_action_cancel').click();
			}
			// action: delete uploaded
			else if ($actionValue == 'delete') deleteUploads($actionValue)
			// action: move media to media manager library + publish
			else if ($actionValue == 'publish') moveUploadsToMediaManager($actionValue)
			// action: move media to media manager library + keep unpublished
			else if ($actionValue == 'unpublished') moveUploadsToMediaManager()
		}

		// reset the input value to zero
		$actionValueInput.val('0')
		// reselect 'select all'
		$('input.toggle_all').prop('checked', false);

	}

	/** Move previously ftp'd files to media manager.
	 *
	 * These are files in the temporary private uploads directory for scans.
	 *
	 */
	function processScanToMediaManager() {
		var $actionValueInput = $('input#mm_scan_btns_dropdown_value');
		var $actionValue = $actionValueInput.val()
		// action: scan + publish selected uploads
		if (!$actionValue || $actionValue == 'scan-publish') moveUploadsToMediaManager('publish',true)
		// action: scan + keep unpublished
		else if ($actionValue == 'scan-unpublished') moveUploadsToMediaManager('unpublished',true)
		// action: delete uploaded
		else if ($actionValue == 'scan-delete') deleteUploads($actionValue, true)

		// reset the input value to 'published'
		$actionValueInput.val('scan-publish')
		// reselect 'select all'
		$('input.toggle_all').prop('checked', false);
	}

	/**
	 * Initialise this script.
	 *
	 */
	function init() {

		/*************************************************************/

		removeDuplicates(1);
		initTabs();
		confirmOverwrite()
		toggleShowTagsInput();
		toggleSelectAll();

		// for JFU: custom upload actions
		var $jfuContainer = $('div.jfu_files_container');
		$($jfuContainer).on('click', 'button#jfu_upload_actions_btns', function (e) {
			e.preventDefault();
			processMoveUploadsToMediaManager($jfuContainer)
		});

		// scan in media uploads
		$(document).on('click', 'button#mm_scan_btns', function(e) {
			e.preventDefault();
			processScanToMediaManager()
		});

		// enable shift+click of a range of checkboxes
		// $table = $("table#mm_scan_list,table#mm_filter_profiles_list");
		$("table#mm_scan_list,table#mm_filter_profiles_list").each(function() {
			shiftClickSelectCheckboxes($(this));
		});

		//###################################

		// modal: single page edit
		$('div#mm_modal_single_page_edit').on('click', 'button.mm_action_save_page', function () {
			//################### submit form to save the page IF top/header button clicked #####################
			var $id = $(this).attr('id');
			$id = $id.replace('_copy', '');
			clickButton('form#ProcessPageEdit button#' + $id);

		});

		// modal: single page edit. On page edit form submit, send data to update markup in parent window
		var $singlePageEdit = $('button.mm_action_save_page');
		// modal: single page edit. On page edit form submit, send data to update markup in parent window
		// @note: ONLY listening to media pages already saved in the InputfieldMediaManager
		if ($singlePageEdit.length) {
			$('form#ProcessPageEdit').on('submit', function () {
				// get the div wrapper for modal single page edit with MM actions and inputs
				var $div = $('div#mm_modal_single_page_edit');
				var $mediaInField = $div.children('input#modalMediaEditInField').val();
				var $modalModeEdit = $div.children('input#modalModeEdit').length ? true : false;
				// check and update parent window if in insert mode and the media being edited is in our MM inputfield
				if ($modalModeEdit && $mediaInField == 1) singlePageEdit($div);
			});
		}

		// impt: using .on in order to be able to manipulate the inserted html
		// submit => prepare and (ajax) post selected pages to the server
		$('div.mm_actions').on('click', 'button#mm_action_btn, button#mm_add_btn, button#mm_add_variations_btn', function (e) {
			e.preventDefault();
			e.stopPropagation();
			processAjax($(this))
		});

		/** Filters **/
		$('.mm_create_filter,.mm_edit_filter,.mm_no_filters').on('pw-modal-closed', function(evt, ui) {
			window.location.reload(true);// force parent page refresh on modal close [note: adapted for magnific popup]
		});

		/* @note: doesn't work; precedence issue
		// reset filters button clicked
		$('div#ProcessLister').on('click', 'a#ProcessListerResetTab', function(){
			buildNotice(resetFiltersNotice, 1);
		});
		*/

		//#############################// $(document).on('loaded') ###################################//

		// register some 'on loaded' functions (to work with ajax content)
		$(document).on('loaded', function () {
			// remove ajax-duplicated actions panel: ajax loaded content in Lister (Temporary solution)
			removeDuplicates();
			// live sort
			liveSortListerResults();
			// refresh lister on PW modal closed
            $(document).find('a.add_pages, a.edit_pages').on('pw-modal-closed', function (evt, ui) {
				refreshLister();
			});
		})// END 'on loaded'

	}

	// initialise script
	init();

}// END ProcessMediaManager()


/*************************************************************/
// READY

jQuery(document).ready(function($) {
	ProcessMediaManager($);
});
