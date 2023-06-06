/**
*
* Javascript file for the Commercial ProcessWire Module Media Manager (Inputfield)
*
* @author Francis Otieno (Kongondo) <kongondo@gmail.com>
*
* Copyright (C) 2015 by Francis Otieno
*
*/

$(document).ready(function(){

		// global variables
		mediaSelect = 'input[name="sel_media"]';// hidden select media checkbox
		mediaThumb = 'div.page_media img';// a single media's thumb
		mediaDiv = 'div#';
		mediaThumbWrapper = 'div.page_media_wrapper';// wrapper for a single media thumb
		mediaListHeaders = 'div.page_media_headers';// headers visible with media meta in list view
		mediaData = 'data-media';// data attribute to identify media to action
		addMedia = '';


		/*************************************************************/
		// FUNCTIONS

		// function to set cookie to remember thumbs view
		setCookie = function(key, value) {
			document.cookie = key + '=' + value + ';expires=0';
		}

		// function to get cookie to maintain thumbs view
		getCookie = function(key) {
			var keyValue = document.cookie.match('(^|;) ?' + key + '=([^;]*)(;|$)');
			return keyValue ? keyValue[2] : null;
		}

		// close magnificPopup dialog
		closeDialogMF = function() {
			setTimeout(function(){
				var mfp = $.magnificPopup.instance;
				mfp.close();
			},1000);// 1 second delay before closing
		}

		// grid view switcher
		gridViewSwitcherInputfield = function() {
			//$('div.actions_add').on('click', 'a.grid_view', function(e){
			// @note: we use this event delegation to ensure works with repeater ajax inserted elements
			// rather than binding directly to the element
			$(document).on('click', 'div.actions_add a.grid_view', function(e) {
				e.preventDefault();
				var mediaValue = ($(this).attr(mediaData));
				if ($(mediaDiv + mediaValue + ' ' + mediaThumbWrapper).hasClass('list')) {
					$(mediaDiv + mediaValue + ' ' + mediaThumbWrapper).removeClass('list').addClass('grid');
					setCookie(mediaValue + '_' + 'view', 0);
				}

				// hide list view headers
				if (!$(mediaDiv + mediaValue + ' ' + mediaListHeaders).hasClass('hide')) {
					$(mediaDiv + mediaValue + ' ' + mediaListHeaders).addClass('hide');
				}

			});
		}

		// list view switcher
		listViewSwitcherInputfield = function() {
			//$('div.actions_add').on('click', 'a.list_view', function(e){
			// @note: we use this event delegation to ensure works with repeater ajax inserted elements
			// rather than binding directly to the element
			$(document).on('click', 'div.actions_add a.list_view', function(e) {
				e.preventDefault();
				$( ".inner" ).wrap( "<div class='new'></div>" );
				var mediaValue = ($(this).attr(mediaData));
				if ($(mediaDiv + mediaValue + ' ' + mediaThumbWrapper).hasClass('grid')) {
						$(mediaDiv + mediaValue + ' ' + mediaThumbWrapper).removeClass('grid').addClass('list');
						setCookie(mediaValue + '_' + 'view', 1);
				}

				// show list view headers
				if ($(mediaDiv + mediaValue + ' ' + mediaListHeaders).hasClass('hide')) {
						$(mediaDiv + mediaValue + ' ' + mediaListHeaders).removeClass('hide');
				}
			});
		}

		// select all media
		selectAllMedia = function() {
			//$("a.select_all").click(function(e){
			$(document).on('click', 'div.actions_remove a.select_all', function(e) {
			 	e.preventDefault();
			 	var mediaValue = ($(this).attr(mediaData));
				$(mediaDiv + mediaValue + ' ' + mediaSelect).prop("checked", true);
				$(mediaDiv + mediaValue + ' ' + mediaThumb).addClass('highlighted');
		 	});
		}

		// unselect all media
		unselectAllMedia = function() {
			//$("a.unselect_all").click(function(e){
			$(document).on('click', 'div.actions_remove a.unselect_all', function(e) {
		 		e.preventDefault();
				var mediaValue = ($(this).attr(mediaData));
				$(mediaDiv + mediaValue + ' ' + mediaSelect).prop("checked", false);
				$(mediaDiv + mediaValue + ' ' + mediaThumb).removeClass('highlighted');
		 	});
		}

		// trash media @note: only removing them from DOM
		trashMedia = function() {
			//$('a.media_remove').on('click',function(e){
			$(document).on('click', 'div.actions_remove a.media_remove', function(e) {
				e.preventDefault();
				var mediaValue = ($(this).attr(mediaData));
				$(mediaDiv + mediaValue + ' .thumb:checked').each(function(){
					$(this).closest('div.page_media_wrapper').remove();
				});

			});
		}

		// select one media at a time
		selectSingleMedia = function() {
			//$(mediaThumb).click(function() {
			$(document).on('click', mediaThumb, function(e) {
				// Toggle (un)checking of hidden input on page thumb click/select
				var mediaValue = ($(this).attr('data-value'));
				var mediaWrapper = ($(this).attr('data-wrapper'));
				var checkBoxesimage = $(mediaDiv + mediaWrapper + ' #media-'+mediaValue);
				checkBoxesimage.prop('checked', !checkBoxesimage.prop('checked'));

		   	 	// Toggle Highlight the selected media
		    	$(this).toggleClass('highlighted');

			});
		}

		// sort media via drag and drop
		// @todo: mouseenter here a stop-gap measure?
		sortMedia = function() {
			//$(document).on('mouseover', 'div.sortable', function(e) {
			$(document).on('mouseenter', 'div.sortable', function(e) {
				$(this).sortable();
				$(this).disableSelection();
			});
		}

		// reload parent window after inserting media
		// @todo: see notes below where we call the function
		reloadOnModalClose = function() {
			$(document).on('click opened openReady repeateradd', function(){
				$('a.add_media').on('pw-modal-closed', function(evt, ui) {
					window.location.reload(true);// force parent page refresh on modal close [note: adapted for magnific popup]
				});
			});
		}


		/*************************************************************/

		// ### - sortable page image thumbs - ###
		sortMedia();

		// ### - list/grid switcher - ###
		// grid view
		gridViewSwitcherInputfield();

		// list view
		listViewSwitcherInputfield();

		// ### - select/deselect- ###
		// media (De)Selection
		selectSingleMedia();

		// ### - select/unselect all + trash media - ###

		// select all pages(images)
		selectAllMedia();

		 // unselect all pages(images)
		 unselectAllMedia();

		 // trash media
		 trashMedia();

		 // ### - insert media into page via modal - ###http://k.master.pw2.8/site/assets/files/2136/summer_walk_about.100x75.jpg
		// @todo...confirm no double reload? especially where no repeater?
		reloadOnModalClose();

});// end jquery

