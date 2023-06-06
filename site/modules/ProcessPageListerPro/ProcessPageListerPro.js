/**
 * ProcessWire Page Lister Pro
 * 
 * Copyright 2015 by Ryan Cramer
 * 
 * Uses:
 * /wire/modules/ProcessPageLister/ProcessPageLister.js 
 * /wire/modules/Jquery/JqueryUI/modal.js
 * 
 */
var ProcessListerPro = {
	
	refreshLister: false, // true when lister should refresh after a dialog close
	inAjax: false,
	editQueue: [], // queue of page edit requests
	saveQueue: [],  // queue of page save requests
	refreshQueue: [],  // queue of page refresh requests
	
	/**
	 * Initialize ListerPro
	 * 
	 */
	init: function() {
		if(ProcessLister.results == null) return;
		
		var $viewport = $("#wrap_actions_viewport"); 
		$viewport.hide();

		$("#Inputfield_run_action").click(function() {
			var $form = $(this).parents('form'); 
			$form.attr('target', 'actions_viewport'); // change target for this submission
			$viewport.slideDown();
			if($viewport.is(".InputfieldStateCollapsed")) $viewport.find(".InputfieldHeader").click(); 
			setTimeout(function() { $form.attr('target', '_top'); }, 1000);  // restore target
			var $icon = $(this).find("i.fa"); 
			$icon.attr('id', 'actions_spinner').attr('data-icon', $icon.attr('class')).removeClass($icon.attr('class')).addClass("fa fa-lg fa-spinner fa-spin"); 
			return true; 
		}); 

		$("#submit_config").click(function() {
			window.location.href = './config/';
			return false;
		});

		$("#_ProcessListerConfigTab").unbind('click').attr('href', './config/'); 

		$(document).on('click', 'a.PageEdit.modal, a.PageView.modal', ProcessListerPro.modalClick); 
		$(document).on('click', 'a.actions_toggle', ProcessListerPro.pageClick);
		//$(document).on('click', '#ProcessLister a.PageAddNew.modal', ProcessListerPro.modalClick);
		$('#ProcessLister').on('click', 'a.PageAddNew.modal', ProcessListerPro.modalClick); // @soma
		$(document).on('click', "#head_button a.PageAddNew.modal", function(event) {
			$("#ProcessLister a.PageAddNew.modal").click();
			return false;
		}); 
		setTimeout(function() {
			$("#head_button a.PageAddNew.modal").attr('href', '#'); 
			//$("#ProcessLister a.PageAddNew.modal").find("button").unbind('click');
			$("#ProcessLister a.PageAddNew.modal").find("button").on('click', function() {
				$("#ProcessLister a.PageAddNew.modal").click();
				return false;
			});
		}, 500);
		$(document).on('click', 'td.row_editable_toggle, td.row_editable_toggle a', ProcessListerPro.editClickEvent);
		
		ProcessLister.results.on('loaded', function() {
			ProcessListerPro.pageClick(); // refresh counters
			var hasEditable = false;
			ProcessLister.results.find(".col_editable").each(function() {
				hasEditable = true; 
				var $tr = $(this).closest('tr');
				if(!$tr.hasClass('row_editable')) {
					$tr.addClass('row_editable');
					$tr.append("<td class='row_editable_toggle'><a href='#'><i class='fa fa-pencil fa-fw'></i></a></td>");
				}
			}); 
			if(hasEditable) ProcessLister.results.find("#ProcessListerTable > table > thead")
				.children("tr:not(.row_editable)").addClass('row_editable')
				.append("<th>&nbsp;</th>");
			$(document).on("submit", "#table_editable", function() {
				return false;
			});
		}); 

		if($('#ListerProConfigForm').length > 0) $("body").addClass('ListerProConfig');
		
	},
	
	startAjax: function() {
		ProcessListerPro.inAjax = true;
	},
	
	stopAjax: function() {
		ProcessListerPro.inAjax = false;
		var saveQueue = ProcessListerPro.saveQueue;
		var len = saveQueue.length;
		var total = 0;
		for(var n = 0; n < len; n++) {
			if(!saveQueue.length) break;
			var $tr = saveQueue.shift();
			$tr.find(".row_editable_toggle a").click();
			total++;
		}
		if(!total) {
			var editQueue = ProcessListerPro.editQueue;
			var len = editQueue.length;
			for(var n = 0; n < len; n++) {
				if(!editQueue.length) break;
				var $tr = editQueue.shift();
				$tr.find(".row_editable_toggle a").click();
				total++;
			}
		}
		if(!total && ProcessListerPro.refreshQueue.length) {
			ProcessListerPro.startAjax();
			ProcessLister.refreshRows(ProcessListerPro.refreshQueue);
			ProcessListerPro.refreshQueue = [];
			ProcessListerPro.stopAjax();
		}
	}, 

	/**
	 * Click event for a modal window action 
	 * 
	 * @param event
	 * @returns {boolean}
	 * 
	 */
	modalClick: function(event) {

		var $a = $(this);
		var isEditLink = $a.hasClass('PageEdit');
		var isAddLink = $a.hasClass('PageAdd'); 
		var isViewLink = $a.hasClass('PageView'); 
		var isTopAddLink = $a.hasClass('PageAddNew'); 
		var href = $a.attr('href'); 
		var url = href + (isEditLink || isAddLink || isTopAddLink ? '&modal=1' : '');
		var closeOnSave = true;
		var saveClicked = false;
		var modalSettings = {};
		var $iframe;
		var $toggle = $a.closest('td').find('.actions_toggle');
		var pageID = $a.closest('tr').attr('data-pid');
		if(typeof pageID == "undefined" || !pageID) {
			if($toggle.length) pageID = parseInt($toggle.attr('id').substring(4));
		} else {
			pageID = parseInt(pageID);
		}
		
		if(isEditLink || isAddLink || isTopAddLink) modalSettings = {
			close: function(event, ui) {
				if (!ProcessListerPro.refreshLister) return;
				if(isEditLink && pageID > 0) {
					ProcessLister.refreshRow(pageID, '');	
				} else {
					ProcessLister.refreshAll();
				}
				ProcessListerPro.refreshLister = false;
			}
		};
		
		$iframe = pwModalWindow(url, modalSettings, 'large');
		
		if(isEditLink || isAddLink) {
			ProcessLister.clickAfterRefresh = $a.parents('.actions').siblings('.actions_toggle').attr('id');
		}
		
		var loadCnt = 0; // number of times $iframe.load() is called for page-edit (but not page-add)
		
		// modal iframe load
		$iframe.load(function() {

			var buttons = []; 	
			var $icontents = $iframe.contents();
			$icontents.find('.InputfieldFormConfirm').removeClass('InputfieldFormConfirm');
			
			$iframe.setTitle($icontents.find('title').text());

			if(isEditLink || isAddLink || isTopAddLink) {
				// instances that require a Lister refresh after completion
			
				// hide elements we don't want in the modal
				$icontents.find('#breadcrumbs ul.nav, #_ProcessPageEditChildren').hide();
				
				// determine whether or not the window should close when save button pressed
				closeOnSave = $icontents.find('#ProcessPageAdd').length == 0;
				if(closeOnSave) loadCnt++;
				if(closeOnSave && $icontents.find(".NoticeError").length) closeOnSave = false;
				if(closeOnSave && (isAddLink || isTopAddLink) && loadCnt < 2) closeOnSave = false;
				
				if(closeOnSave && saveClicked) {
					ProcessListerPro.refreshLister = true;
					$iframe.dialog('close');
				}
	
				// copy buttons in iframe to dialog
				var n = 0;
				$icontents.find("#content form button.ui-button[type=submit]").each(function () {
					var $button = $(this);
					var text = $button.text();
					var skip = false;
					// avoid duplicate buttons
					for (var i = 0; i < buttons.length; i++) {
						if (buttons[i].text == text || text.length < 1) skip = true;
					}
					if(!skip) {
						buttons[n] = {
							'text': text,
							'class': ($button.is('.ui-priority-secondary') ? 'ui-priority-secondary' : ''),
							'click': function () {
								$button.click();
								saveClicked = true; // only let closeOnSave happen once
							}
						};
						n++;
					}
					$button.hide();
				});

				// delete button closes window and refreshes Lister
				$icontents.find("#submit_delete").click(function () {
					ProcessListerPro.refreshLister = true;
					setTimeout(function() {
						$iframe.dialog('close');
					}, 500);
				});
				
			} else if(isViewLink) {
				// setup modal action for "view" links
				
				buttons = [{
					'html': "<i class='fa fa-external-link'></i> " + config.ProcessPageListerPro.openNewLabel,
					'class': 'ui-priority-secondary ListerProNewWindow',
					'click': function() {
						$iframe.dialog('close'); 
					}
				}, {
					 'html': "<i class='fa fa-times-circle'></i> " + config.ProcessPageListerPro.closeLabel, 
					 'click': function() {
					 	$iframe.dialog('close'); 
					 }
				}];
			}

			// set buttons to the iframe
			if(buttons.length > 0) $iframe.setButtons(buttons); 
			
			// attach "open in new window" action
			parent.jQuery('.ListerProNewWindow').wrap("<a target='_blank' href='" + url + "'></a>"); 
		}); 
		
		return false; 
	},

	/**
	 * Event handler for a.actions_toggle page click action
	 * 
	 * Updates the quantity selected headline, for use with selected items and actions. 
	 * 
	 * @returns {boolean}
	 * 
	 */
	pageClick: function() {

		var $wrap_actions_items = $("#wrap_actions_items"); 
		var $counter = $("#lister_open_cnt"); 
		var $counter2 = $("#lister_open_cnt2"); 
		var $openItems = ProcessLister.results.find("tr.open");
		var cnt = $openItems.size();

		if(!$counter2.size()) {
			$counter2 = $("<span id='lister_open_cnt2'></span>"); 
			$("#actions_items_open").after($counter2); 
		}

		$counter.find('span').text(cnt); 
		$counter2.html(cnt + '&nbsp;'); 

		if(cnt > 0) {
			var ids = []; 
			$openItems.each(function(n) {
				var $a = $(this).find("a.actions_toggle"); 
				ids[n] = $a.attr('id').replace('page', ''); 
			}); 

			$counter.show();
			if($wrap_actions_items.hasClass('InputfieldStateCollapsed')) {
				$wrap_actions_items.removeClass('InputfieldStateCollapsed'); 
			}
			$("#actions_items_all").removeAttr('checked'); 
			$("#actions_items_open")
				.removeAttr('disabled')
				.attr('checked', 'checked')
				.val(ids.join(','))
				.parent('label')
					.removeClass('ui-state-disabled'); 
		} else {
			$counter.hide();
			$("#actions_items_open")
				.removeAttr('checked')
				.attr('disabled', 'disabled')
				.val('')
				.parent('label')
					.addClass('ui-state-disabled'); 
			$("#actions_items_all").attr('checked', 'checked'); 
			if(!$wrap_actions_items.hasClass('InputfieldStateCollapsed')) {
				$wrap_actions_items.addClass('InputfieldStateCollapsed'); 
			}
		}

		return false; 
	},
	
	saveEdits: function($scope) {

		if(typeof $scope == "undefined") {
			var $scope = ProcessLister.results;
		}
		
		if(ProcessListerPro.inAjax) {
			return false;
		}

		var $form = $("#table_editable");
		$form.submit();
		var changes = [];
		var pageIDs = [];
		
		$scope.find(".col_editing").each(function() {
			$(this).find(".Inputfield, .InputfieldCKEditor").trigger('saveReady');
			var $editor = $(this).find('.col_editor');
			var pageID = parseInt($editor.attr('data-pid'));
			pageIDs[pageID] = pageID;
			changes += pageID + '.' + $editor.attr('data-fid') + ',';
			$(this).parent('tr').removeClass('row_editing');
		});

		var data = $scope.find(".col_editing :input").serialize();
		data += '&_changes=' + changes;
		
		var $csrfToken = $form.find("input._post_token");
		data += '&' + $csrfToken.attr('name') + '=' + $csrfToken.attr('value');

		// console.log(data);
		
		var $button = $("button.save_edits");
		var $icon = $button.find('i.fa');
		var saveIconClass = $icon.attr('class');
		$icon.attr('class', 'fa fa-spin fa-spinner');
		ProcessListerPro.startAjax();
		
		$.post("./save", data, function(result) {
			// console.log(result);
			// ProcessLister.extraSubmitData = { editor: 1 }; 
			for(var pageID in pageIDs) {
				ProcessListerPro.refreshQueue[pageID] = pageID;
			}
			ProcessListerPro.stopAjax();
			ProcessLister.results.one('loaded', function() {
				ProcessListerPro.stopAjax();
			});

			$icon.attr('class', '').addClass(saveIconClass);
			if(!result.success) {
				// errors occurred
			}
			if(typeof result.message != "undefined" && result.message.length) {
				alert(result.message);
			}
			$form.removeClass('InputfieldFormSubmitted');
			$button.fadeOut();
		}, 'json');
		
		return false;
			
	},
	
	editClickEvent: function(e) {
		
		var $this = $(this);
	
		if($this.is("a")) {
			var $a = $this;
			var $td = $a.closest('td');
		} else if($this.is('td')) {
			var $td = $this;
			var $a = $this.find('a');
		} else {
			var $td = $this.closest('td');
			var $a = $td.find('a');
		}
		
		var $tr = $td.closest('tr');
		var $form = $tr.closest('.InputfieldForm');
		if($tr.hasClass('row_refreshing')) return false;
		var $icon = $a.children('i');
		var pageID = ProcessLister.getPageID($tr);
		
		if(!$form.hasClass('ListerEditPreload') && (!$tr.hasClass('row_refreshed_' + pageID) || !$tr.hasClass('row_editing'))) {
			$icon.attr('class', 'fa fa-spin fa-spinner');
			if(ProcessListerPro.inAjax) {
				ProcessListerPro.editQueue.push($tr);
				return false;
			}
			ProcessLister.extraSubmitData = { editor: 1 };
			$tr.addClass('row_refreshing');
			ProcessListerPro.startAjax();
			ProcessLister.refreshRows([ pageID ]);
			ProcessLister.results.one('loaded', function() {
				var $tr = $('#ProcessListerResults tr.row_refreshed_' + pageID);
				// this will call editClickEvent again, but this time with a row ready for edits
				ProcessListerPro.stopAjax();
				$tr.addClass('row_editing');
				$tr.find('td.row_editable_toggle a').click(); 
			});
			return false;
		}

		$icon.attr('class', 'fa fa-fw fa-save');
		var toggleClick = function() {
			$icon.attr('class', 'fa fa-spin fa-spinner');
			if(ProcessListerPro.inAjax) {
				ProcessListerPro.saveQueue.push($tr);
				return false;
			}
			ProcessListerPro.saveEdits($tr);
			return false;
		};
		$td.unbind('click').click(toggleClick);
		$a.unbind('click').click(toggleClick);

		if($td.hasClass('row_editable_toggle')) {
			var $tds = $tr.children('td');
		} else {
			var $tds = $this.closest('td');
		}

		$tds.each(function() {
			var $td = $(this);
			var $col_editable = $td.find(".col_editable");
			if($col_editable.length) {
				if(!$td.hasClass('col_editing')) {
					$td.addClass('col_editing');
					$td.parent('tr').addClass('row_editing');
					$col_editable.css('width', '100%');
				}
			}
			$td.find(".Inputfield:not(.reloaded)").addClass('reloaded').trigger('reloaded', [ 'ProcessPageLister' ]);
		});
		
		var $button = $(".save_edits");
		if(!$button.is(":visible")) {
			$button.fadeIn();
			var $buttonMain = $("#save_edits");
			if(!$buttonMain.hasClass('hasClickEvent')) $buttonMain.click(function() {
				ProcessListerPro.saveEdits(ProcessLister.results);
				return false;
			});
			$buttonMain.addClass('hasClickEvent');
		}

		return false;
	}
	
};

$(document).ready(function() {
	ProcessLister.init();
	ProcessListerPro.init();
}); 
