/**
 *
 * Javascript file for the Commercial ProcessWire Module Media Manager (Process)
 *
 * @author Francis Otieno (Kongondo) <kongondo@gmail.com>
 *
 * Copyright (C) 2015 by Francis Otieno
 *
 */
$(document).ready(function() {
	/*************************************************************/
	// GLOBALS
	currentPageID = '';
	currentFieldID = '';
	urlMediaManager = '';
	type = ''; //will hold our (dynamic) action type
	insertAndClose = 0; // whether to close dialog/pop-up after insert media in InputfieldMediaManager ('add mode')
	msg = '';
	action = '';
	media = '';
	err = '';
	n = false;
	mediaVariationsWrapper = 'div.media_variations_wrapper';
	selectedMediaLarge = '';
	// PW System Notifications in use
	if (typeof Notifications == 'object') n = true;
	/*************************************************************/
	// FUNCTIONS
	spinner = function(d) {
		if (d == 'in') $("#page_fields_spinner").fadeIn();
		else $("#page_fields_spinner").fadeOut('slow');
	}
	spinnerOver = function(d) {
		var overlayDiv = '<div id="overlay"><span><i class="fa fa-lg fa-spin fa-spinner"></i><br><br>Scanning. Please wait...</span></div>';
		if (d == 'in') $('body').append(overlayDiv);
		else $('div#overlay').remove();
	}
	statsVariationView = function(v, d) {
		var e = $("div.media_stats, div.media_variations_wrapper, div#bottom_pager");
		if (v == 'hide' && d) $("div.media_stats[data-media-value='" + d + "'], div.media_variations_wrapper[data-media-value='" + d + "']").hide();
		else if (v === 'show') $(e).show();
		else if (v === 'fade' && d) $("div.media_stats[data-media-value='" + d + "'],div.media_variations_wrapper[data-media-value='" + d + "']").fadeIn(3000);
		else if (v === 'hide') {
			$(e).hide();
			// @TODO..REFACTOR!
			// exception, if we are in list view, no need to hide bottom_pager when editing a single media
			if ($('div#media_list_view').not('.hide_view')) $('div#bottom_pager').show();
		}
	}
	editView = function(v) {
		if (v == 'hide') $('div.media_panel, div.media_edit').hide();
		else if (v === 'show') $('div.media_panel, div.media_edit').show();
	};
	// dynamically create editable table of image variations descriptions and tags
	// @code adapted from @Rob Gravelle: http://www.htmlgoodies.com/beyond/css/working_w_tables_using_jquery.html
	makeTable = function(container, data, variationTypes) {
		var table = $("<table/>").addClass('media_variations');
		var j = 0; // to help with variation types
		// create rows
		$.each(data, function(rowIndex, r) {
			var row = $("<tr/>");
			var i = 0;
			if (j !== 0) var type = variationTypes[j]; // if we are not dealing with the <th>
			// create columns
			$.each(r, function(colIndex, c) {
				if (rowIndex == 0) row.append($("<th/>").text(c));
				else {
					if (i == 0) row.append($("<td><img src='" + c + "' class='image'></td>"));
					else if (i == 1) row.append($("<td><textarea name='media_description2[]' class='media_image_variation_description' data-media-variation-type='" + type + "'>" + c + "</textarea></td>"));
					else row.append($("<td><textarea name='media_tags2[]' class='media_image_variation_tags' data-media-variation-type='" + type + "'>" + c + "</textarea></td>"));
				}
				i++;
			});
			table.append(row);
			j++;
		});
		return container.append(table);
	}
	successErrorActions = function(a, cls, msg) {
			// use PW System Notifications if in use
			if (n) {
				if (cls === 'NoticeMessage') Notifications.message(msg);
				else if (cls === 'NoticeError') Notifications.error(msg);
				return;
			}
			var p = $('p#message');
			if (a === 1) {
				$(p).removeClass('NoticeError');
				$(p).removeClass('NoticeMessage');
			} else if (a === 2) {
				$(p).addClass(cls);
				$(p).fadeIn('fast').delay(3000);
				$(p).html(msg);
				$(p).fadeOut('slow');
			}
		}
	// refresh lister
	refreshLister = function() {
			$("#_ProcessListerRefreshTab").click();
		}
	// refresh lister
	resetFilters = function() {
			$("#_ProcessListerResetTab").click();
		}
	// close jquery UI dialog
	closeDialog = function(s=1000) {
			setTimeout(function() {
				//$('div.ui-dialog-titlebar button.ui-dialog-titlebar-close').click();
				parent.jQuery('iframe.ui-dialog-content').dialog('close');
			}, s);
		}
	// reset previously selected media
	resetSelections = function() {
			$("input[name='media']").each(function() {
				$('div.media img').removeClass('highlighted');
				this.checked = false;
			});
			// reset action selections
			$('select#mm_action_select').val('');
			// working on tags here
			$('div#mm_tags_wrapper').hide();
			$("input[name='mm_action_tags']").val('');
			$("input[name='mm_tag_mode']").prop("checked", false);
		}
	// remove 'fixed' css class from 'media_large' panel elements
	resetFixed = function() {
			$("div.media_large.fixed").each(function() {
				$(this).removeClass('fixed');
			});
		}
	// set cookie to remember various options
	setCookie = function(key, value) {
			document.cookie = key + '=' + value + ';expires=0';
		}
	// get cookie to determine various options
	getCookie = function(key) {
			var keyValue = document.cookie.match('(^|;) ?' + key + '=([^;]*)(;|$)');
			return keyValue ? keyValue[2] : null;
		}
	// remove a given previously set cookie
	removeCookie = function(key) {
			document.cookie = key + '=;expires=Thu, 01-Jan-70 00:00:01 GMT;';
		}
	// switch to grid view
	gridViewSwitcher = function() {
			// persist grid-view state
			setCookie('media_manager_view', 0);
			// persist state that we do not need fixed view (@see notes about 'server-side apply fixed class')
			setCookie('media_manager_list_view_fixed', 0);
			if (selectedMediaLarge) selectedMediaLarge.removeClass('fixed');
			var listView = $('div#media_list_view');
			var gridView = $('div#media_grid_view');
			var infoPanel = $('div#media_large_view');
			var mediaGridViewTitle = $('div.media span.thumb-meta');
			if (gridView.hasClass('hide_view')) {
				listView.addClass('hide_view');
				gridView.removeClass('hide_view');
				infoPanel.removeClass('list_view');
				mediaGridViewTitle.removeClass('hide_view');
			}
		}
	// switch to list view
	listViewSwitcher = function() {
			// persist list-view state
			setCookie('media_manager_view', 1);
			var listView = $('div#media_list_view');
			var gridView = $('div#media_grid_view');
			var infoPanel = $('div#media_large_view');
			var mediaGridViewTitle = $('div.media span.thumb-meta');
			if (listView.hasClass('hide_view')) {
				listView.removeClass('hide_view');
				gridView.addClass('hide_view');
				infoPanel.addClass('list_view');
				mediaGridViewTitle.addClass('hide_view');
			}
		}
	//  list view: for longer lists, fix selected media preview panel in sight
	listViewScrollFix = function() {
			// 'scroll to top then fix'
			if ($('table#list_view div.media').length > 3) {
				//var parentHeight = $('div#media_large_view').height();
				var h = 500; // harcoded to min-height of div#media_large_view in CSS (i.e. the parentHeight)
				selectedMediaLarge = $("div.media_large").not('.hidden');
				$(window).bind('scroll', function() {
					// @todo: if doing this math, @see the notes on throttling scroll http://ejohn.org/blog/learning-from-twitter/
					//var h = $(window).height() - parentHeight;
					if ($(window).scrollTop() > h) {
						selectedMediaLarge.addClass('fixed');
						/*
						we also set a cookie to help when we save a media (in list view mode)...
						after save we usually run refreshLister(). However, reloading time varies depending on various factors
						so running removeClass('fixed') does not always work
						using a cookie ensures 'fixed' class will only be applied server side if it is needed (i.e. when scrollTop is > h)
						*/
						setCookie('media_manager_list_view_fixed', 1);
					} else {
						selectedMediaLarge.removeClass('fixed');
						setCookie('media_manager_list_view_fixed', 0);
					}
				});
				// list-view: scroll to top if we navigate to another page
				scrollToTop();
			} // end if we have more than 3 elements
		}
	// list-view: scroll page back to top if we navigate to another page
	scrollToTop = function() {
			$('div#bottom_pager ul.MarkupPagerNav').on('click', 'a', function() {
				var c = getCookie('media_manager_view');
				if (c == 0) return;
				$('html, body').animate({
					scrollTop: 0
				}, 1000);
			});
		}
	// live sort: sort
	liveSort = function(sort) {
			var selectedSort = sort.val();
			if (selectedSort.length) {
				// grab sort order first + set its cookie
				liveSortOrder(1);
				// set sort criteria
				setCookie('media_live_sort', selectedSort);
			} else {
				// remove sort criteria and order
				removeCookie('media_live_sort');
				liveSortOrder(0);
			}
			refreshLister();
		}
	// live sort: sort order
	liveSortOrder = function(mode) {
			var v = "media_live_sort_order";
			var sortOrderChkbx = $('input#' + v);
			if (mode == 1) {
				sortOrderChkbx.parent().removeClass('hide');
				if (sortOrderChkbx.prop('checked')) setCookie(v, 2);
				else setCookie(v, 1)
			} else {
				sortOrderChkbx.parent().addClass('hide');
				removeCookie(v);
			}
		}
	// live sort: sort order
	filtersEditNotices = function() {
			// get success query string: will tell us if successfully saved filter + filter profiles
			var queryString = location.search;
			var success;
			// if we have a query string
			if(queryString.length) {
				// create a <p#message> on the fly first. it will contain our error message
				$('<p/>', {
				id: 'message',
				}).appendTo('div#content');

				// split query string to get to '&success=n'
				var queryStringParts = {};

				$.each(queryString.substr(1).split('&'),function(c,q){
					var i = q.split('=');
					queryStringParts[i[0].toString()] = i[1].toString();
				});
				// get value of query string to determine success/failure
				if(queryStringParts.success) success = queryStringParts.success;
					/*#  success message #*/
					if(success == 1) {
			  			msg = 'Media Manager: Filter settings saved.';
						successErrorActions(2, 'NoticeMessage', msg);
					}
					/*#  error message #*/
			  		else if(success == 0){
			  			err = 'Media Manager: A filter with that title already exists. Please amend and try again.';
						successErrorActions(2, 'NoticeError', err);
			  		}
			}// end if query string

		}

	/*************************************************************/
	// CONFIG
	ajaxMediaManagerConfig = config.ProcessMediaManager;
	if (!jQuery.isEmptyObject(ajaxMediaManagerConfig)) {
		currentPageID = ajaxMediaManagerConfig.config.currentPageID;
		currentFieldID = ajaxMediaManagerConfig.config.currentFieldID;
		urlMediaManager = ajaxMediaManagerConfig.config.ajaxURL;
		insertAndClose = ajaxMediaManagerConfig.config.insertAndClose;
	} //end if ajaxMediaManagerConfig not empty
	/*************************************************************/
	// BULK ACTIONS: (UN)PUBLISH, (UN)LOCK, (UN)TAG, TRASH, DELETE, DELETE VARIATIONS
	//impt: using .on in order to be able to manipulate the inserted html
	//prepare and (ajax) post selected media(via their thumbs) to the server
	$('div#mm_actions').on('click', 'button#mm_action_btn', function() {
		// reset error/success messages
		successErrorActions(1);
		// push selected media in array
		var media = [];
		$.each($("input[name='media']:checked"), function() {
			media.push($(this).val());
		});
		if (media.length === 0) {
			err = 'Media Manager: You need to select at least one media before applying an action.';
			successErrorActions(2, 'NoticeError', err);
			return false;
		}
		var selectedAction = $('select#mm_action_select').val();
		if (selectedAction === '') {
			err = 'Media Manager: You need to select an action to apply.';
			successErrorActions(2, 'NoticeError', err);
			return false;
		}
		var variationsParentID = '';
		if (selectedAction === 'delete-variation') {
			var currentVariationsParent = $("input.media_variations_parent_id[value!='0']");
			if (currentVariationsParent.length === 0) {
				err = 'Media Manager: We could not find a parent image media for your selected image variations.';
				successErrorActions(2, 'NoticeError', err);
				return false;
			} else variationsParentID = $(currentVariationsParent).val();
		}
		var tags = '';
		var tagMode = '';
		if (selectedAction == 'tag') {
			tags = $("input[name='mm_action_tags']").val();
			if (tags == '') {
				err = 'Media Manager: You need to enter at least one tag to apply.';
				successErrorActions(2, 'NoticeError', err);
				return false;
			}
			var tagModeChkbx = $("input[name='mm_tag_mode']");
			if (tagModeChkbx.prop('checked')) tagModeChkbx.val(1);
			else tagModeChkbx.val(0);
			tagMode = tagModeChkbx.val();
		}
		// ajax
		$.ajax({
				url: urlMediaManager,
				type: 'POST',
				data: {
					current_page_id: currentPageID,
					current_field_id: currentFieldID,
					media: media,
					action: selectedAction,
					variationsparentid: variationsParentID,
					tags: tags,
					tag_mode: tagMode,
				},
				dataType: 'json',
				beforeSend: spinner(d = 'in'),
			}).done(function(data) {
				if (data && data.message == 'success') {
					// uncheck previously selected thumbs + reset action selection
					resetSelections();
					msg = data.success;
					// if server returned success
					successErrorActions(2, 'NoticeMessage', msg);
					spinner(d = 'out');
					refreshLister(); // refresh results
				} else {
					// uncheck previously selected thumbs + reset action selection
					resetSelections();
					// if server returned error
					err = data.error;
					successErrorActions(2, 'NoticeError', err);
					spinner(d = 'out');
				}
			}).fail(function() {
				err = 'Media Manager: Error encountered. Request could not be completed.';
				successErrorActions(2, 'NoticeError', err);
			})
			//refresh button (remove 'ui-state-active') (only need for default theme)
		if (!$(this).hasClass('Reno')) $(this).button().button('refresh');
	}); //end div#mm_actions
}) //end document.ready()
// SINGLE MEDIA ACTION: (UN)PUBLISH, (UN)LOCK,
$(document).on('loaded', function() {
		$('div.media_edit').on('click', 'button.mm_edit', function() {
			media = [];
			action = 'edit';
			var subaction = $(this).attr('data-media-action');
			var mediaVariationDescription = '';
			var mediaVariationTags = '';
			var dataMediaID = $(this).attr('data-media-id');
			var pageid = dataMediaID;
			setCookie('media_manager_edited_media', pageid);
			var type = $(this).attr('data-media-type');
			// reset error/success messages
			successErrorActions(1);
			if (subaction === 'save') {
				// set values to publish and lock checkboxes in single media edit
				var pubChkbx = ('input#media_publish_' + dataMediaID);
				var lockChkbx = ('input#media_lock_' + dataMediaID);
				if ($(pubChkbx).prop('checked')) $(pubChkbx).val(1);
				else $(pubChkbx).val(0);
				if ($(lockChkbx).prop('checked')) $(lockChkbx).val(1);
				else $(lockChkbx).val(0);
				// get the data for the original image media
				media = {
					'title': $('input#media_title_' + dataMediaID).val(),
					'description': $('textarea#media_description_' + dataMediaID).val(),
					'tags': $('input#media_tags_' + dataMediaID).val(),
					'publish': $(pubChkbx).val(),
					'lock': $(lockChkbx).val()
				};
				// push image media variations descriptions into this object
				var mediaVariationDescription = {};
				$.each($("textarea.media_image_variation_description"), function() {
					mediaVariationDescription[$(this).attr('data-media-variation-type')] = ($(this).val());
				});
				// push image media variations tags into this object
				var mediaVariationTags = {};
				$.each($("textarea.media_image_variation_tags"), function() {
					mediaVariationTags[$(this).attr('data-media-variation-type')] = ($(this).val());
				});
			} // end if save
			// ajax
			$.ajax({
				url: urlMediaManager, // pwadmin/media-manager/ajax/
				type: 'POST',
				data: {
					'pageid': pageid,
					'type': type,
					'media': media,
					action: action,
					'subaction': subaction,
					'variations_desc': mediaVariationDescription,
					'variations_tags': mediaVariationTags
				},
				dataType: 'json',
				beforeSend: spinner(d = 'in'),
			}).done(function(data) {
				if (data && data.message == 'success') {
					// uncheck previously selected thumbs
					resetSelections();
					msg = data.success;
					// if server returned success
					successErrorActions(2, 'NoticeMessage', msg);
					spinner(d = 'out');
					refreshLister(); // refresh results
				} else {
					// uncheck previously selected thumbs
					resetSelections();
					//refreshLister();// refresh result
					//if server returned error
					err = data.error;
					successErrorActions(2, 'NoticeError', err);
					spinner(d = 'out');
				}
			}).fail(function() {
				err = 'Media Manager: Error encountered. Request could not be completed.';
				successErrorActions(2, 'NoticeError', err);
			})
		}); //end div#save_pages
	}) // end document.ready()
// INSERT MEDIA IN PAGE (in InputfieldMediaManager)
$(document).ready(function() {
		$('div#save_pages').on('click', 'button#mm_add_btn', function() {
			// reset error/success messages
			successErrorActions(1);
			// push selected media (thumbs) in array
			var media = [];
			$.each($("input[name='media']:checked"), function() {
				media.push($(this).val());
			});
			//set type from data-media-type of button clicked. We are determing action from <button data-media-type='xxx'>
			action = 'insert';
			if (media.length === 0) {
				err = 'Media Manager: You need to select at least one media to insert in page.';
				successErrorActions(2, 'NoticeError', err);
				return false;
			}
			// ajax
			$.ajax({
				url: urlMediaManager, //pwadmin/media-manager/ajax/
				type: 'POST',
				data: {
					'current_page_id': currentPageID,
					'current_field_id': currentFieldID,
					'media': media,
					action: action
				},
				dataType: 'json',
				beforeSend: spinner(d = 'in'),
			}).done(function(data) {
				if (data && data.message == 'success') {
					msg = data.success;
					// if server returned success
					successErrorActions(2, 'NoticeMessage', msg);
					spinner(d = 'out');
					refreshLister(); // refresh results
					// uncheck previously selected thumbs + reset action selection
					resetSelections();
					// if currentField (FieldtypeMediaManager) set to 'insert media in InputfieldMediaManager and close'
					// we close the dialog/pop-up (magnificPopup) immediately after inserting
					if (insertAndClose) parent.document.location.reload(true);
					//if(insertAndClose) parent.jQuery('iframe.ui-dialog-content').dialog('close');
				} else {
					// uncheck previously selected thumbs + reset action selection
					resetSelections();
					// if server returned error
					err = data.error;
					successErrorActions(2, 'NoticeError', err);
					spinner(d = 'out');
				}
			}).fail(function() {
				err = 'Media Manager: Error encountered. Request could not be completed.';
				successErrorActions(2, 'NoticeError', err);
			})
		}); //end div#save_pages
	})
// RTE: INSERT IMAGE MEDIA IN RTE (for CKEditor 'mmimage' Plugin)
// RTE: INSERT MEDIA LINK IN RTE preparation. We grab last selected media (for CKEditor 'mmlink' Plugin)
// 'on': making sure will still work with ajax posted content
$(document).on('loaded', function() {
		// ** Insert media in RTE **
		// on click image media simulate click on hidden <a> to load 'insert selected image in RTE'
		$('div.media img.rte').click(function() {
			// find corresponding <a> for RTE insertion to click it
			var ThumbID = ($(this).attr('data-media-value'));
			var rteLink = $('div.media').find('a#rte-' + ThumbID);
			window.location.href = rteLink.attr('href');
		});
		// ** Prepare Media Link for RTE **
		$('div.media img.link').click(function() {
			$('div.media img').removeClass('highlighted'); // remove all other highlighted media
			// find corresponding <a> (has identical data-media-value) to get RTE link from
			var ThumbID2 = ($(this).attr('data-media-value'));
			var rteLink2 = $('div.media a[data-media-value="' + ThumbID2 + '"]');
			var href = rteLink2.attr('data-href');
			var title = rteLink2.parent().attr('title');
			var mediaRTELink = $('a#media_rte_link'); // hidden placeholder link
			// update hidden placeholder link with selected media's href and title attributes on the fly
			mediaRTELink.attr('href', href);
			mediaRTELink.attr('title', title);
		});
	})
// UPLOADS //
// scanning uploads
$(document).ready(function() {
		//impt: using .on in order to be able to manipulate the inserted html
		$('li#MediaManagerTabsScan').on('click', 'button#mm_scan_btn', function() {
			// reset error/success messages
			successErrorActions(1);
			var pubChkbx = $('input#scan_publish');
			if ($(pubChkbx).prop('checked')) $(pubChkbx).val(1);
			else $(pubChkbx).val(0);
			var scanPublish = pubChkbx.val();
			var scan = 1;
			// ajax
			$.ajax({
					url: urlMediaManager,
					type: 'POST',
					data: {
						action: 'scan',
						'scan': scan,
						'scan_publish': scanPublish
					},
					dataType: 'json',
					beforeSend: spinnerOver(d = 'in'),
					//complete: spinnerOver(d='out'),
				}).done(function(data) {
					if (data && data.message == 'success') {
						$(pubChkbx).prop('checked', false);
						spinnerOver(d = 'out');
						msg = data.success;
						// if server returned success
						successErrorActions(2, 'NoticeMessage', msg);
					} else {
						// if server returned error
						spinnerOver(d = 'out');
						err = data.error;
						successErrorActions(2, 'NoticeError', err);
						$(pubChkbx).prop('checked', false);
					}
				}).fail(function() {
					err = 'Media Manager: Error encountered. Request could not be completed.';
					successErrorActions(2, 'NoticeError', err);
				})
				//refresh button (remove 'ui-state-active') (only need for default theme)
			if (!$(this).hasClass('Reno')) $(this).button().button('refresh');
		}); //end div#mm_actions
	}) //end document.ready() for scan or copy-pasted links uploads
	/* Media selections */
// making sure will still work with ajax posted content
$(document).on('loaded', function() {
		// media thumb (de)selection
		$('div.media img').click(function() {
			//Toggle (un)checking of hidden input on page thumb click/select
			var ThumbID = ($(this).attr('data-media-value'));
			var checkBoxesimage = $('#thumb-' + ThumbID);
			checkBoxesimage.prop('checked', !checkBoxesimage.prop('checked'));
			// Toggle Highlight the selected thumb
			$(this).toggleClass('highlighted');
		});
		// select all media
		$("span#select_all a").click(function(e) {
			e.preventDefault();
			$("div#media_grid_view input[name='media']").prop("checked", true);
			$('div#media_grid_view div.media img, div#media_list_view div.media img').addClass('highlighted');
		});
		// unselect all media
		$("span#unselect_all a").click(function(e) {
			e.preventDefault();
			$("div#media_grid_view input[name='media']").prop("checked", false);
			$('div#media_grid_view div.media img, div#media_list_view div.media img').removeClass('highlighted');
		});
	})
/* Remove ajax-duplicated media manager menu: ajax loaded content (Temporary solution) */
$(document).on('loaded', function() {
		$('div#mm_top_panel:eq(0)').show();
		$('div#img1').hide(); // for descriptions
	})
/* Remove ajax-duplicated media manager menu: uploads page (Temporary solution) */
$(document).ready(function() {
		$('div#mm_top_panel:eq(0)').show();
		$('div#img1').hide(); // for descriptions
	})
	/* Wire Tabs */
$(document).ready(function() {
		var $form = $("#fileupload");
		// remove scripts, because they've already been executed since we are manipulating the DOM below (WireTabs)
		// which would cause any scripts to get executed twice
		$form.find("script").remove();
		$form.WireTabs({
			items: $(".WireTab"),
			skipRememberTabIDs: ['MediaManagerTabsHelp'],
			rememberTabs: true
		});
	})
/* Add to Media Library button (prevent default if no files in temp/draft folder) */
$(document).ready(function() {
		var addToMLButton = $('button#mm_add_to_media_library_btn ');
		$(addToMLButton).click(function(e) {
			var u = $('div.files_container tbody#files').find('p.name a');
			if (u.length === 0) {
				e.preventDefault();
				err = 'Media Manager: You need to upload your media before you try to add them to the Media Library.';
				successErrorActions(2, 'NoticeError', err);
			}
		});
	})
/* Media large panel info preview (2-column layout, right pane) */
$(document).on('loaded', function() {
		// get media whose stats will be displayed initially
		var activeMedia = getCookie('media_manager_active_media');
		var activeMediaInfoPanel = $('#media_large_' + activeMedia);
		// if cookie with media data-media-value, we use it as the active media
		if (activeMediaInfoPanel.length > 0) activeMediaInfoPanel.removeClass('hidden');
		// else we set first media as active media
		else {
			var firstMedia = $('div#media_thumbs_wrapper').children('div.media').eq(0).children('img').eq(0);
			activeMedia = ($(firstMedia).attr('data-media-value'));
			activeMediaInfoPanel = $('#media_large_' + activeMedia);
			activeMediaInfoPanel.removeClass('hidden');
			//setCookie('media_manager_active_media', activeMedia);// save current to cookie
		}
		// in thumbs grid, find the thumb of the active media and highlight its title
		//$("div.media a.media_panel_view[data-media-value='" + activeMedia +"']").addClass('selected');
		$("div#media_wrapper a.media_panel_view[data-media-value='" + activeMedia + "']").addClass('selected');
		// @todo...refactor these! + below + throw into functions?
		$('a.selected').closest('div.media').addClass('selected');
		$('a.selected').parents(':eq(1)').prev().find('div.media').addClass('selected'); // in list view, parent div so that we can target img in css
		// set display-only icons as 'not-clickable'
		$('a.media_locked, a.media_unpublished').click(false);
		// set selected media as active, display in large panel + save to cookie
		// media large view/stats panel trigger
		$('div.media a.media_panel_view, table#list_view a.media_panel_view').click(function(e) {
			e.preventDefault();
			$('a.media_panel_view, div.selected').removeClass('selected');
			var mediaInfoPanelID = ($(this).attr('data-media-value'));
			//alert(mediaInfoPanelID);
			var mediaInfoPanelWrapper = $('#media_large_' + mediaInfoPanelID);
			$('div#media_large_view div.media_large').addClass('hidden'); // hide all initially
			mediaInfoPanelWrapper.removeClass('hidden'); // show active panel
			setCookie('media_manager_active_media', mediaInfoPanelID); // save current to cookie
			$(this).addClass('selected');
			$("div#media_wrapper a.media_panel_view[data-media-value='" + mediaInfoPanelID + "']").addClass('selected');
			$("div#media_wrapper div.media[data-media-value='" + mediaInfoPanelID + "']").addClass('selected');
			selectedMediaLarge = $('div#media_large_' + mediaInfoPanelID);
			//console.log(selectedMediaLarge);
			// if we are in 'fixed mode' we first remove existing 'fixed' classed then apply 'fixed' class to currently selected 'media_large'
			if ($('div.media_large.fixed').length) {
				resetFixed();
				selectedMediaLarge.addClass('fixed');
			}
		});
	})
/* Sliding panel for editable media info (title, description, tags, publish, lock */
$(document).on('loaded', function() {
		// mode to show on load (view vs edit)
		// from saved cookie
		var mode = getCookie('media_manager_mode');
		var dataValue = getCookie('media_manager_active_media');
		if (mode === 'edit') {
			statsVariationView('hide');
			editView('show');
		} else if (mode === 'view') {
			statsVariationView('show');
			editView('hide');
		}
		/* ## Edit Mode: click event ## */
		// going to edit mode
		$('a.media_edit_info').on('click', function(e) {
			e.preventDefault();
			var dataValue = $(this).attr('data-media-value');
			var mediaInfoPanelWrapper = $('#media_large_' + dataValue);
			$('div#media_large_view div.media_large').addClass('hidden'); // hide all initially
			mediaInfoPanelWrapper.removeClass('hidden'); // show active panel
			statsVariationView('hide', dataValue);
			statsVariationView('hide'); // @note: needed to cover other div.media_stats + div.media_variations_wrapper
			var dataMediaPanel = $("div.media_panel[data-media-value='" + dataValue + "']"); // wrapper panel for the exact div with the inputs to edit
			var dataMediaEdit = $(dataMediaPanel).children('div.media_edit'); // the exact div with the inputs to edit
			$(dataMediaPanel).animate({
				'width': 'show'
			}, 500, function() {
				$(dataMediaEdit).fadeIn(500);
			});
			// no animation for the rest of the panel + edit div
			// we need this to be 'shown' ready for editing if their media thumb titles are clicked
			editView('show');
			setCookie('media_manager_mode', 'edit'); // save media manager mode (edit vs view)
		});
		/* ## View Mode: click event ## */
		// going to view mode
		$('span.close').on('click', function(e) {
			e.preventDefault();
			var dataValue = $(this).attr('data-media-value');
			var dataMediaPanel = $("div.media_panel[data-media-value='" + dataValue + "']");
			var dataMediaEdit = $(dataMediaPanel).children('div.media_edit');
			$(dataMediaEdit).fadeOut(500, function() {
				$(dataMediaPanel).animate({
					'width': 'hide'
				}, 500);
			});
			statsVariationView('fade', dataValue); // fade in
			editView('hide'); // hide all other edit panels without effects
			// no animation for the rest of the similar divs
			// we need this to be 'shown' ready for editing if their media thumb titles are clicked
			statsVariationView('show');
			setCookie('media_manager_mode', 'view'); // save media manager mode (edit vs view)
			$('.accordion').accordion('refresh'); // refresh variations' accordion
		});
	})
/* Dyanamic creation of image variations edit table */
$(document).on('loaded', function() {
		$('div.media_edit a.media_image_variations_edit').on('click', function(e) {
			e.preventDefault();
			//  remove existing variations edit table (avoid duplicates on click)
			$('table.media_variations').remove();
			var dataValue = $(this).attr('data-media-value');
			var dataMediaPanel = $("div.media_panel[data-media-value='" + dataValue + "']");
			var dataMediaEdit = $(dataMediaPanel).children('div.media_edit');
			var v = $(mediaVariationsWrapper + "[data-media-value='" + dataValue + "']").find('ul.media_variations');
			var variations = [];
			// add table headers (<th>)
			variations.push({
				thumb: 'Thumb',
				description: 'Description',
				tags: 'Tags'
			});
			var addVariations = function(thumb, description, tags) {
				variations.push({
					thumb: thumb,
					description: description,
					tags: tags
				});
			};
			var variationTypes = [];
			variationTypes.push(0); // dummy data to help skip <th> in variations edit table
			$(v).each(function() {
				// grab each variations image src, description and tags
				var i = $(this).find('img.image').attr('src');
				var desc = $(this).find('span.media_image_variation_description');
				var d = $(desc).text();
				var t = $(this).find('span.media_image_variation_tags').text();
				var type = $(desc).attr('data-media-variation-type');
				addVariations(i, d, t);
				variationTypes.push(type); // for variation types
			});
			// create image variations edit table on the fly
			makeTable($(dataMediaEdit), variations, variationTypes);
		});
	})
/** variations: image media variations in preview panel **/
// variations: accordion panel
// variations: hidden input for setting variations parent id
$(document).on('loaded', function() {
		// initialize variations accordion
		$("div.accordion").accordion({
			collapsible: true
		});
		// inputs to hold variations parents id
		var vp = 'input.media_variations_parent_id[name=media_variations_parent_id]';
		// image variations thumbs
		var iv = 'div.media img.variation';
		// reset the values of all image media variations parent hidden input to zero on load and on click
		// also clear variations checkboxes + remove highlight class
		($(vp).val(0));
		$('div.media a.media_panel_view, div.original img').click(function() {
			($(vp).val(0));
			$("input.variation[type='checkbox']").prop("checked", false);
			$(iv).removeClass('highlighted');
		});
		// set the current variations parent id (replacing zero set above on load + on change click)
		// also reset selections of media in the thumbs/grid panel (to minimise errors. This way variations and original thumbs are never sent together the server)
		$(iv).click(function() {
			$("div.original input[name='media']").prop("checked", false); // clean house; deselect original thumbs selections (i.e. those in thumbs panel)
			$('div.original img').removeClass('highlighted'); // -ditto-
			var variationsParentID = ($(this).attr('data-media-variations-parent-id'));
			var h = ($("input:hidden[data-media-variations-parent-id='" + variationsParentID + "']"));
			if (h.val() == 0) h.val(variationsParentID);
		});
	})
/* Dyanamic creation of hidden div to show an image's variations' gallery */
$(document).ready(function() {
	$('div.accordion a.media_image_zoom').on('click', function(e) {
		e.preventDefault();
		var d = $(this).attr('data-gallery');
		var id = d.substr(1);
		//$('body').append('<div id="'+  id  +'"></div>');
		$('body').append('<div/>').attr('id', id);
	})
});
/** Display/hide 'tags' input on selection/deselection of 'Tag' action **/
$(document).ready(function() {
		$('select#mm_action_select').change(function() {
			var option = $(this).find('option:selected').val();
			var tagsWrapper = $('div#mm_tags_wrapper')
			if (option === 'tag') tagsWrapper.animate({
				'height': 'show'
			}, 500);
			else tagsWrapper.animate({
				'height': 'hide'
			}, 300);
		});
	})
	/** Media display view switcher (grid vs list) **/
$(document).on('loaded', function() {
		// grid view
		$('div#media_view_switcher_sort').on('click', 'a.grid_view', function(e) {
			e.preventDefault();
			gridViewSwitcher();
		});
		// list switcher
		$('div#media_view_switcher_sort').on('click', 'a.list_view', function(e) {
			e.preventDefault();
			listViewSwitcher();
		});
		// list-view: scroll-to-then-fix to keep selected large media panel in sight
		if ($('div#media_list_view').not('.hide_view')) listViewScrollFix();
	})
/** Live Sort **/
$(document).on('loaded', function() {
		$('select#mm_live_sort_action_select').change(function() {
			liveSort($(this));
		});
		$('input#media_live_sort_order').change(function() {
			// set sort order cookie if checked else remove cookie
			if ($(this).prop('checked')) setCookie($(this).attr('id'), 2);
			else setCookie($(this).attr('id'), 1);
			refreshLister();
		});
	})
/** Filters **/
$(document).ready(function() {
	// change link to filter configure tab
	var filtersHref = urlMediaManager.replace("ajax", "filter");// @note: just reusing + amending this existing variable
	var filtersConfigTab = $("#_MediaManagerFiltersConfigTab");
	$(filtersConfigTab).unbind('click').attr('href', filtersHref + '?modal=1');
	$(filtersConfigTab).addClass('pw-modal pw-modal-medium');
	// go to filter profiles view on click dedicated button
	$('form#mm_edit_filter_config').on('click', 'button#mm_filter_profiles_go_btn', function(e) {
		e.preventDefault();
		var href = $(this).attr('data-filter-profiles-url');
		window.location=href;
	});
	// reload parent window on modal close @todo...prevent double reload if closing using save+view
	$('a#_MediaManagerFiltersConfigTab').on('pw-modal-closed', function(evt, ui) {
		//window.location.reload(true);// force parent page refresh on modal close
		resetFilters();
	});
	// close this jquery UI dialog after xxxx seconds delay from within the parent window
	$('form#mm_filter_config').on('click', 'button#mm_filter_settings_save_view_btn', function() {
		parent.closeDialog(2000);
	});
})
/** Filters Profiles Table **/
// @rc (events fieldtype)
$(document).ready(function() {
	var marked;
	var confirmDeleteFiltersText = $('p#mm_filter_profiles_del_confirm_text');

	/* delete table rows */
	$(document).on("click", "table#mm_filter_profiles_list a.mm_filter_profile_del", function(e) {
		// toggle mark rows for deletion
		// toggle show the confirm delete filter profiles text
		var $row = $(this).parents("tr.mm_filter_profile");
		if($row.size() == 0) {
			// delete all
			$(this).parents("thead").next("tbody").find('.mm_filter_profile_del').click();
			return false;
		}
		var $input = $(this).next('input');
		if($input.val() == 1) {
			$input.val(0);
			$row.removeClass("mm_filter_profile_TBD");
			$row.removeClass('ui-state-error');
			if($('.mm_filter_profile_TBD').length == 0) confirmDeleteFiltersText.hide();
		} else {
			$input.val(1);
			$row.addClass("mm_filter_profile_TBD");
			$row.addClass('ui-state-error');
			confirmDeleteFiltersText.show();
		}
		return false;
	});

	// success/error messages in modal of filter profiles config
	filtersEditNotices();
});