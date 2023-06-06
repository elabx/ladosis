/**
*
* Javascript file for the Commercial ProcessWire Module Media Manager (Inputfield)
*
* @author Kongondo <kongondo@gmail.com>
*
* Copyright (C) 2015 by Francis Otieno
*
*/

function InputfieldMediaManager($, $ajaxData = null) {

	/*************************************************************/
	// SCRIPT GLOBAL VARIABLES

	/*	@note:
		- global variables NOT prefixed with '$'.
		- function parameters and variables PREFIXED with '$'
	*/

	var action, ajaxURL, data, currentPageID, editMediaID, markup, mediaManagerFieldID, pageFieldName, media, parent, savedMediaThumbsView, showCustomColumns, singleMediaManagerField, thumbsViewGridList, thumbsViewTabularBody, thumbsViewTabularTemplate;

	data = null;

	// set values to some variables
	if ($ajaxData != null) {
		data = $ajaxData;
		media = data.media;
		action = data.action;
		currentPageID = data.currentPageID;
		mediaManagerFieldID = data.mediaManagerFieldID;
		parent = $('div#mm_main_wrapper_' + currentPageID + '_' + mediaManagerFieldID);
		savedMediaThumbsView = parent.find('div.mm_thumbs_view_grid_wrapper').length ? 1 : 0;// get view
		singleMediaManagerField = parent.hasClass('mm_single_media') ? 1 : 0;// check if in single vs multi-page field
		thumbsViewGridList = parent.find('ul.gridImages');
		// check if showing custom columns and set variables as needed
		showCustomColumns = false;
		thumbsViewTabularTemplate = parent.find('div.mm_tabular_view_template table tbody tr[data-value="0"]');
		if (thumbsViewTabularTemplate.length) {
			showCustomColumns = true;
			thumbsViewTabularBody = parent.find('table.mm_thumbs_view_tabular tbody.mm_thumbs_view_tabular');
		}

		// single page edit stuff
		//editMediaID = data.hasOwnProperty('editMediaID') ? data.editMediaID : null;
		// single page edit stuff
		if (data.hasOwnProperty('editMediaID')) {
			editMediaID = data.editMediaID;
			ajaxURL = data.ajaxURL;
			currentPageID = data.currentPageID;
			action = data.action;
		}

		markup = {};// JS Object
		markup.add = {};
		// @note: no longer using this. we remove trashed and unpublished media before building markup
		//markup.remove = {};
		markup.add = {
			divs: {},
			rows: {},
		};



	}


	/*************************************************************/
	// FUNCTIONS

	/**** For non-Ajax ****/

	/**
	 * Remove a page from Thumbs View.
	 *
	 * @param Int $mode Whether in single vs multi page field.
	 * @param Null|Object $a The clicked anchor/link.
	 *
	*/
	function removeMediaThumbsView($a) {
		var $parent = $a.parents('div.mm_main_wrapper');
		// multi page field
		if (!$parent.hasClass('mm_single_media')) {
			var $relatedRow;
			var $dv;
			var $tabularViewTable = $parent.find('table.mm_thumbs_view_tabular');
			var $checkedThumbs = $parent.find('.mm_thumb:checked');
			$($checkedThumbs).each(function () {
				$(this).closest('li.media_thumb_wrapper').remove();
				// ## also remove related table row in Thumbs View: Tabular sub-view ##
				// in format 'media-1234-1' where 1234=id and 1=media type int
				// for a variation, this would be 1234-31. for instance.
				//  we want the '1234-1'
				// @note: the ID of the checkbox in the tabular view appendix! '-tabular': so 'media-1234-1-tabular'
				// we remove the appendix as well
				$dv = $(this).attr('id').substr(6).replace('-tabular', '');
				$relatedRow = $($tabularViewTable).find('tbody.mm_thumbs_view_tabular tr[data-value="' + $dv + '"]').remove();
			});
		}
		// single page field
		else {
			// remove elements we don't need, e.g. the table media numbering
			$parent.find('ul.gridImages li.media_thumb_wrapper, table.mm_thumbs_view_tabular tbody.mm_thumbs_view_tabular tr:first-child').remove();
		}

		// update the media count label @todo: what if no limits? need to refactor so we don't call this needlessly
		updateMediaCount($parent)

	}

	/**
	 * Updates the media count after media inserted in a field via Ajax.
	 *
	 * This is only relevant to fields with max media allowed after.
	 */
	function updateMediaCount($parent) {
		var $countLabel = $parent.find('span.mm_media_count');
		var $totalAllowedCount = $parent.find('input.mm_max_files').val();
		var $newCount = $parent.find('li.media_thumb_wrapper:not(".mm_grid_view_template_item")').size();
		$countLabel.text($newCount + "/" + $totalAllowedCount);
	}

	/**** For Ajax ****/

	/**
	 * Updates the Media Manager field markup depending on Ajax response from server.
	 *
	 * Will either build and add markup or remove markup depending on Ajax action.
	 *
	 * @param Object data The JSON object sent back from the server in response to Ajax call to update page field.
	 *
	 */
	//function updateMarkupAjax(data) {
	function updateMarkupAjax() {

		if (!data || !data.hasOwnProperty('media')) return;

		/*
			- if media removed (due to delete or unpublish action in modal window)
			- we remove it and any of its siblings (variations) + any of its/their presence in adjacent MM Inputfields!
		*/
		// @todo: maybe a notice here?
		// @note: only one editing can happen at a time, so we know this is at the first index in media object
		if (action == 'removed') {
			$('[data-delete="' + media[0].mediaID + '"]').remove();
			return;
		}

		// Build markup
		markup = buildMarkup();

		// @note: we now implement this outside this method, much earlier.
		// 1. REMOVE: View does not matter here. @note: can co-exist with 'add media' in case of 'delete images'
		//if (!jQuery.isEmptyObject(markup.remove)) removeSavedMediaMarkup(markup.remove);

		// 2. APPPEND or REPLACE
		if (!jQuery.isEmptyObject(markup.add)) {
			// $savedMediaThumbsView
			markupThumbsViewAdd(markup.add);
		}

		// 3. RESET tabular view table row counts + indexes for gallery (tr and li data-gallery-index)
		if (!singleMediaManagerField && savedMediaThumbsView) resetCounts();

		// 4. NOTHING ADDED YET: No media left in Inputfield: APPEND 'nothing added yet'.
		// @todo: need to revisit this. Not sure we still need this?
		if (!mediaExist()) {
			var $nothingAddedYet = parent.find('div.mm_thumbs_wrapper');
			$nothingAddedYet.prepend('<span class="mm_no_media">' + data.nothingAddedYet + '</span>');
		}
		else parent.find('span.mm_no_media').remove();

		// if adding and closing dialog set
		if (action === "insert" && data.insertAndClose) closeDialog();

		// update the media count label @todo: what if no limits?
		updateMediaCount(parent)

	}

	/**
	 * Build markup using Ajax response.
	 *
	 * Alters markup for the parent window.
	 * @returns object $markup Object containing markup to add or pageIDs to remove from DOM.
	 *
	 */
	function buildMarkup() {
		// thumbs view
		markup = buildThumbsViewMarkup();// object
		return markup;
	}

	/**
	 *
	 * Builds markup for page edit thumbs view.
	 *
	 * Also collates pageIDs of elements that should be removed from the Inputfield.
	 *
	 * @param Object data The JSON object sent back from the server in response to Ajax call to update page field.
	 * @return object $markup Multi-dimensional object with values for altering markup.
	 *
	 */
	function buildThumbsViewMarkup() {

		// in single page field, we remove existing page first to avoid duplicates
		markupClearDuplicates();

		if (action == 'insert' || action == 'edit') {
			$.each(media, function ($index, $values) {
				var $mediaID = $values.mediaID;
				var $li = buildThumbsViewGridMarkup($mediaID, $values);
				markup.add.divs[$index] = $li;
				var $li2 = $li.clone();
				var $row = buildThumbsViewTabularMarkup($mediaID, $values, $li2);
				markup.add.rows[$index] = $row;

			});// END .each()
		}
		/* @note: no longer in use: we remove early now before building markup
		// removed
		else {
				markup.remove[$mediaID] = $mediaID;
		}*/
		return markup;

	}

	/**
	 * Builds markup for page edit thumbs view: grid.
	 *
	 * @param integer $mediaID The page ID of the page to be added to the field.
	 * @param object $values Data for the given page to build its markup.
	 * @return String $div Div markup for a given page to add to the page field.
	 *
	 */
	function buildThumbsViewGridMarkup($mediaID, $values) {

		var $template = parent.find('ul.mm_grid_view_template li:first');
		// the <li> </li>: media_thumb_wrapper
		$li = $template.clone();
		// remove the template class from the <li></li> ()
		$li.removeClass('mm_grid_view_template_item');
		// ID and data-value of media_thumb_wrapper
		// @note: ID is in the format: $id = "media-media-page-id-mm-field-id-mediaTypeInt";
		$li.attr('id', 'media-' + $mediaID + '-'+mediaManagerFieldID+'-'+$values.mediaTypeInt);
		// @note: the value here was changed from just $mediaID to $mediaID-$values.mediaTypeInt
		$li.attr('data-value', $mediaID + "-" + $values.mediaTypeInt);
		$li.attr('data-media-value', $mediaID + "_" + $values.mediaTypeInt);
		$li.attr('data-delete', $mediaID);

		var $tooltip = $li.find('div.gridImage__tooltip table tbody');

		// ## BIND ELEMENTS ##

		// media (short) title
		var $t = $tooltip.find('tr:first td:first');
		// media description
		var $d = $tooltip.find('tr:eq(1) td:first');
		// media name
		var $n = $tooltip.find('tr:eq(2) td:first');
		// media (image) dimensions
		var $dm = $tooltip.find('tr:eq(3) td:first');
		// media (image) variations
		var $v = $tooltip.find('tr:eq(5) td:first');
		// media filesize
		var $f = $tooltip.find('tr:eq(4) td:first');
		// media used count
		var $u = $tooltip.find('tr:eq(6) td:first');
		// media status (locked/unlocked ONLY!)
		//@note: this is only for 'locked' in the case of InputfieldMediaManager. This is because unpublished media cannot be added to an InputfieldMM!
		var $s = $tooltip.find('tr:eq(7) td:first');

		// ## MODIFY THE ELEMENTS

		$t.text($values.shortTitle);
		if ($values.noDescription) $d.parent('tr').remove();
		$n.text($values.filename);
		if($values.mediaTypeStr == 'image') {
			$dm.text($values.originalWidth+"&times"+$values.originalHeight);
			$v.text($values.variations);
		}
		// remove dimensions and variations rows for non-image media types
		else {
			$dm.parent('tr').remove()
			$v.parent('tr').remove()
		}

		$f.text($values.filesize);
		$u.text($values.usedCount);
		// if media is NOT locked, remove the locked icon
		if (!$values.locked) $s.parent('tr').remove();

		/*  ## image source ## */

		var $thumbWrapper = $li.find('div.gridImage__overflow');

		// thumb div wrapper
		$thumbWrapper.css({width: $values.thumbWidth})

		// thumb div img
		var $img = $thumbWrapper.find('img');
		$img.attr('alt', $values.description);
		$img.attr('data-original', $values.url);
		$img.attr('data-h', $values.originalHeight);
		$img.attr('data-w', $values.originalWidth);
		$img.attr('src', $values.thumbURL);
		$img.addClass('mm_' + $values.mediaTypeStr);

		/*  ## hover ## */

		/* 	hover label @note: was 'gridImage__trash' in original
			label for the hidden select checkbox needs a unique data-value
			@note: important to distinguish between variations
		*/
		var $label = $li.find('label.gridImage__icon');
		$label.attr('data-value', $mediaID + "-" + $values.mediaTypeInt);

		// hover label checkbox input (.gridImage__selectbox) @note: original 'gridImage__deletebox
		var $checkBox = $label.find('input.gridImage__selectbox');
		// @note: the value here was changed from just $mediaID to $mediaID-$values.mediaTypeInt
		$checkBox.attr('id', 'media-' + $mediaID + "-" + $values.mediaTypeInt);
		$checkBox.attr('data-media', $mediaID);
		$checkBox.val($mediaID + "_" + $values.mediaTypeInt);


		// hover edit image media anchor

		var $a = $li.find('a.gridImage__edit');
		// @note: accounting for uneditable
		if ($values.locked == 1) {
			$a.attr('href', '#');
			$a.removeClass('gridImage__edit pw-modal pw-modal-medium');
			$a.addClass('gridImage__locked');
			var $s = $a.children('span');
			$s.text('');
			$s.append('<span class="fa fa-lock mm_locked"></span>');
		}
		else if ($values.editable == 1) $a.attr('href', $values.editURL);
		else $a.remove();

		/* preview */
		var $li = buildGalleryPreview($li, $values);

		/*  ## hidden input for pw media manager field processing  ## */
		var $i = $li.find('input.mm_field');
		$i.val($mediaID+"_"+$values.mediaTypeInt);

		return $li;

	}

	/**
	 * Builds markup for media tabular in the MM Inputfield.
	 *
	 * @param integer $mediaID The page ID of the media to be added to the MM field.
	 * @param object $values Data for the given media to build its markup.
	 * @return object $li The element in Grid View that has been updated via Ajax whose info we derive here.
	 *
	 */
	function buildThumbsViewTabularMarkup($mediaID, $values, $li) {

		/*  ## REMOVE: hidden input for pw media manager field processing  ## */
		// @note: this was already set in Thumbs view grid: remove from children here to avoid duplicates
		$li.find('input.mm_field').remove();

		// get the children with values already set in grid view template and amend for tabular view as required
		var $children = $li.children();

		// get tabular view temlate
		var $template = parent.find('div.mm_tabular_view_template table tbody tr[data-value="0"]');

		// the <tr> </tr>: media_thumb_wrapper (tabular)
		$tr = $template.clone();
		// ID and data-value of media_thumb_wrapper
		// @note: ID is in the format: $id = "media-media-page-id-mm-field-id-mediaTypeInt-tabular";
		$tr.attr('id', 'media-' + $mediaID + '-'+mediaManagerFieldID+'-'+$values.mediaTypeInt+'-tabular');
		// @note: the value here was changed from just $mediaID to $mediaID-$values.mediaTypeInt
		// data-value of media_thumb_wrapper
		$tr.attr('data-value', $mediaID + "-" + $values.mediaTypeInt);
		$tr.attr('data-media-value', $mediaID + "_" + $values.mediaTypeInt);
		$tr.attr('data-delete', $mediaID);

		/*  ## image source ## */
		// thumb div wrapper - amend inline css width and height
		var $thumbWrapper = $children.filter('div.gridImage__overflow');
		$thumbWrapper.css({ width: '100%', height: 'auto' });

		// thumb div img - amend width attr and inline css max-height and max-width
		var $img = $thumbWrapper.find('img');
		$img.removeAttr('height');
		$img.attr('width', 130);
		$img.css({ "max-height": 'none', "max-width": '100%' });

		// append udated children to $tr
		var $thumbData = $tr.find('td.mm_page_thumb');
		$thumbData.append($children);

		// @todo: in future, needs refactoring!
		// build meta
		var $metaParent = $tr.find('td.mm_thumbs_view_tabular_meta');
		var $metaClasses = getThumbsViewTabularMetaClasses();// @note: array!
		var $text;

		$.each($metaClasses, function ($index, $class) {
			var $child = $metaParent.children('p.' + $class);
			if($class == 'dimensions') {
				// no dimensions for non-image types
				$text = $values.mediaTypeStr == 'image' ? $values.originalWidth + "x" + $values.originalHeight : '';
			}
			else if($class == 'variations') {
				// no variations for non-image types
				$text = $values.mediaTypeStr == 'image' ? $values.variations : '';
			}
			// if unlocked, remove paragraph with fa-lock
			else if($class == 'status') {
				if(!$values.locked) $child.remove();
			}
			// use short title for title
			else if($class == 'title') $text =  $values.shortTitle;
			// all other meta (not above)
			else $text = $values[$class];

			// add texts
			if($class == 'title') $child.text($text);
			else if($class!='status') $child.find('span').after(': ' + $text);
		});

		var $customColumns = $values.customColumns;
		if (!$.isEmptyObject($customColumns)) {
			$.each($customColumns, function ($field, $val) {
				$tr.find('td[data-value="' + $field + '"]').text($val);
			});
		}

		return $tr;

	}

	/**
	 * Returns array of classes to apply to p.mm_thumbs_view_tabular_meta in td.mm_thumbs_view_tabular_meta.
	 *
	 * For use in buildThumbsViewTabularMarkup.
	 *
	 */
	function getThumbsViewTabularMetaClasses() {
		var $metaClasses = ['title', 'description', 'tags', 'filename', 'dimensions', 'filesize', 'variations', 'usedCount','status'];
		return $metaClasses;
	}

	/**
	 * Builds the markup needed for the media gallery.
	 *
	 * @param object $li The li housing the meta and markup we need for the gallery previews.
	 * @param object $values Values returned from Ajax to populate the media details for the preview.
	 *
	 */
	function buildGalleryPreview($li, $values) {
		var $previewWrapper = $li.find('span.mm_media_preview');
		var $aPreview = $previewWrapper.find('a.mm_preview');
		// get data attributes
		var $attributes = getGalleryPreviewAttributes();
		$aPreview.attr('title', $values.title);
		$aPreview.attr('href', $values.previewURL);
		$aPreview.attr('type', $values.type);
		$.each($attributes[0], function ($property, $attribute) {
			$aPreview.attr('data-'+$attribute, $values[$property]);
		});
		return $li;
	}

	/**
	 * Resets the rows count in the first td of thumbs view: tabular.
	 *
	 * We also reset the gallery index attribute of media list and row items.
	 * Applies only to media manager fields with maxFiles (0) or > 1.
	 *
	 */
	function resetCounts() {

		// @note: we always show at least 1 column in MM. But we just future-proof this
		if (!showCustomColumns) return;

		var $thumbsViewGridList = parent.find('ul.gridImages');

		var $tableRows = thumbsViewTabularBody.children('tr.mm_thumb_row');
		var $cnt = 1;
		$.each($tableRows, function () {

			var $tr = $(this);
			$tr.children('td:first').text($cnt);
			var $galleryIndex = $cnt - 1;
			// reset gallery index (data-gallery-index) for the row + related li in grid view
			$tr.attr('data-gallery-index', $galleryIndex);// @note: start at zero

			// find related li and reset its gallery index
			var $dmv = $tr.attr('data-media-value');
			var $li = $thumbsViewGridList.find('li[data-media-value="' + $dmv + '"]');
			$li.attr('data-gallery-index', $galleryIndex);// @note: start at zero

			$cnt++;

		});
	}

	/**
	 * Return array strings to use to build data-attributes used in blueimp gallery previews.
	 *
	 * @returns array $previewAttributes Array of strings to build gallery preview data-attributes.
	 *
	 */
	function getGalleryPreviewAttributes() {
		var $previewAttributes = [{"description":"description", "tags":"tags", "mediaTypeStr":"media-type-str", "dimensions":"dimensions", "filename":"filename", "filesize":"file-size", "variations":"variations", "usedCount":"usedcount"}];
		return $previewAttributes;
	}

	/**
	 * Append or replace markup of media in the page field in thumbs view page edit.
	 *
	 * This is in response to Ajax actions in the inputfield.
	 *
	 * @param Object media Object with values for media to add to the DOM for the page field.
	 *
	 */
	function markupThumbsViewAdd($media) {
		markupThumbsViewGridAdd($media.divs);
		// if custom columns being shown (description, tags, custom fields, etc)
		if(showCustomColumns) markupThumbsViewTabularAdd($media.rows);
	}

	/**
	 * Append or replace markup of media in the page field in thumbs view: grid page edit.
	 *
	 * @param Object media Object with values for media to add to the DOM for the page field.
	 *
	 */
	function markupThumbsViewGridAdd($media) {
		$.each($media, function ($index, $markup) {
			// first: check if replacing or adding
			var $thumbID = $markup.attr('id');
			var $thumb = thumbsViewGridList.children('li#' + $thumbID);
			// updating edited media
			if ($thumb.length) $thumb.replaceWith($markup);
			// appending new media
			else thumbsViewGridList.append($markup);
		});
	}

	/**
	 * Append or replace markup of media in the page field in thumbs view: tabular page edit.
	 *
	 * @param Object media Object with values for media to add to the DOM for the page field.
	 *
	 */
	function markupThumbsViewTabularAdd($media) {
		$.each($media, function ($index, $markup) {
			// first: check if replacing or adding
			var $rowID = $markup.attr('id');
			var $row = thumbsViewTabularBody.children('tr#' + $rowID);
			// updating edited media
			if ($row.length) $row.replaceWith($markup);
			// appending new media
			else thumbsViewTabularBody.append($markup);
		});
	}

	/**
	 * Check for and delete duplicates for media to be added to the DOM as per Ajax action in the Inputfield.
	 *
	 *
	 */
	function markupClearDuplicates() {
		// @todo: if maxfiles is full, users will not be able to add media to the page anyway. So, this could be redundant?
		// @note: we consider an MAXFILES==1 to be a single MM field
		if (singleMediaManagerField) {
			thumbsViewGridList.children('li.ImageOuter').remove();
			if(showCustomColumns) thumbsViewTabularBody.children('tr.mm_thumb_row').remove();
		}
	}

	/**
	 * Check if there are media in the Inputfield (DOM).
	 *
	 * Will determine whether to show a 'nothing found' message if no media found.
	 *
	 */
	function mediaExist() {
		var $check = true;
		// thumbs view check
		if (savedMediaThumbsView) {
			var $thumbs = thumbsViewGridList.children('li.ImageOuter');
			if (!$thumbs.length) $check = false;
		}
		return $check;
	}

	/**
	 * Dynamically update markup on inputfield after a single saved page edit.
	 *
	 * @note: this function is called in a model via ProcessMediaManager.js
	 *
	 * @param Object data Data sent back to the server.
	 *
	*/
	function processSingleMediaEditAjax() {

		// push edited page ID in array
		var $media = [];
		$media.push(editMediaID);

		// ajax
		$.ajax({
			url: ajaxURL,
			type: 'POST',
			data: {'current_page_id': currentPageID, 'current_media_manager_field_id': mediaManagerFieldID, media: $media, action: action},
			dataType: 'json',
		})
			.done(function ($data) {
				// success
				if ($data.message == 'success') {
					// set values to these two globals so as to pick up in updateMarkupAjax()
					data = $data;
					media = $data.media;
					// @note: action could have changed to 'removed' server-side! so, no longer 'edit'
					action = $data.action;
					updateMarkupAjax();
				}
				// error
				else {
					// @todo?
				}

		})
			// fail
			.fail(function() {});// unknown/fail error @todo?

	}

	/**** Other ****/

	/**
	 * Close jQuery UI Modal.
	 *
	 * @param Integer s Number of milliseconds before closing modal.
	 *
	 */
	function closeDialog($s = 1000) {
		setTimeout(function() {
			jQuery('iframe.ui-dialog-content').dialog('close');
		}, $s);
	}

	/**
	 * Apply highlight effect to specified element.
	 *
	 * @param object $elem Element to highlight.
	 */
	function highlight($elem) {
		$elem.fadeOut('normal').effect('highlight', 'fast');
	}

	/**
	 * Initialise this script.
	 *
	 */
	function init() {


		// if ajax action
		if (data != null) {
			// single page edit
			if (editMediaID) processSingleMediaEditAjax();
			// bulk ajax action
			else updateMarkupAjax();
			return;
		}

		// ## - Magnific - ##
		// highlight parent window on modal close
		$(document).find('a.mm_add_media, a.edit_pages').on('pw-modal-closed', function (evt, ui) {
			var $parent = $(this).parents('div.mm_main_wrapper');
			highlight($parent);
		});


		/***************** Remove media from DOM *****************/

		// ## Thumbs View ##
		$(document).on('click', 'div.mm_actions_wrapper a.mm_remove_media:not(.mm_action_modal)', function (e) {
			e.preventDefault();
			removeMediaThumbsView($(this));
		});


	}// end init()

	// initialise script
	init();


}// END InputfieldMediaManager()

/*************************************************************/
// READY

jQuery(document).ready(function($) {
	InputfieldMediaManager($);
});