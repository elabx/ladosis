/**
 * Form Builder Process Javascript
 *
 * JS used by the ProcessFormBuilder module
 *
 * Copyright (C) 2016 by Ryan Cramer 
 *
 * PLEASE DO NOT DISTRIBUTE
 *
 */

function ProcessFormBuilder($) {

	// may be form editor form or field editor form
	var $formEditor = $('#ProcessFormBuilder'); 

	// the asmSelect holding the list of form fields, present only when editing a form
	var $formFields = $('#form_fields');
	
	// whether or not we are in the form editor
	var isEditForm = $formFields.length > 0;

	/**
	 * Form Editor: Setup modal editing of fields
	 * 
	 */
	function setupModalFieldEditor() {

		var $asmEditItem = null; // item clicked on to edit
		var allowCloseDialog = true;

		$(document).on('mousedown', '#wrap_form_fields .asmListItem a', function() {
			$asmEditItem = $(this).closest('li');
			allowCloseDialog = false;
			// console.log('editing: ' + $li.find('.asmListItemLabel').text());
		});

		// if the window close "x" is clicked on then don't block it
		$(document).on('mousedown', '.ui-dialog-titlebar-close', function() {
			allowCloseDialog = true;
		});

		// determine if any errors occur in follow-up load before allowing dialog to close
		$(document).on("dialogbeforeclose", function(event, ui) {

			var $iframe = $(event.target);
			var $contents = $iframe.contents();

			// abort if no edit item was clicked on 
			if(!$asmEditItem || !$asmEditItem.length) return;

			// abort if in some dialog other than field editor
			if($contents.find("#ProcessFormBuilder").length	< 1) return;

			if(allowCloseDialog) {
				var columnWidth = $contents.find("#columnWidth").val();
				var fieldName = $contents.find("#field_name").val();
				var fieldType = $contents.find("#Inputfield_field_type").val();
				var fieldLabel = $contents.find("#Inputfield_field_label").val();
				if($contents.find("#Inputfield_required").is(":checked")) fieldName += '*';
				$asmEditItem.find(".asmListItemStatus").text(fieldType + " " + columnWidth);
				$asmEditItem.find(".asmListItemDesc > a").text(fieldLabel);
				$asmEditItem.find(".asmListItemLabel > a").text(fieldName);
			}

			$iframe.load(function() {
				// after submit, check for errors
				$contents = $iframe.contents();
				// errors, don't allow close
				if($contents.find(".NoticeError").length) return;
				// if here, then no errors occurred so we can close the dialog
				allowCloseDialog = true;
				$(".ui-dialog-content").dialog("close");
			});

			if(!allowCloseDialog) return false;
		});
	}

	/**
	 * Form Editor: Setup the Preview Viewport
	 * 
	 * Called every time the Preview tab is clicked on
	 * 
 	 * @param id
	 * 
	 */
	function setupPreviewViewport(id) {

		var formName = $("#form_name").val();
		var $wrapper = $('#' + id);
		var viewportID = 'FormBuilderViewport_' + formName;
		var $iframe = $('#' + viewportID);
		var $spinner = $("<p style='text-align:center'><i class='fa fa-spin fa-2x fa-spinner'></i></p>");
		var href = $wrapper.find('a').attr('href');

		if($iframe.length > 0) {
			// if already present, force it to reload
			$iframe.contents().find('body').html('<p>&nbsp;</p>');
			var src = $iframe.attr('src');
			var test = src.indexOf('&rand=');
			if(test > 0) src = src.substring(0, test);
			src += '&rand=' + ((Math.random() * 10) + 1);
			$iframe.attr('src', src); 
			
		} else {
			var $iframe = $("<iframe />")
				.attr('frameborder', '0')
				.attr('class', 'FormBuilderViewport')
				.attr('id', viewportID)
				.attr('data-name', formName)
				.attr('src', href);

			$wrapper.css('margin-top', '1px').prepend($iframe).find(".InputfieldContent, .ui-widget-content").remove();
		}

		$iframe.before($spinner);
		$iframe.width('100%');
		$iframe.load(function() {
			$spinner.remove();
		});
	}
	
	/**
	 * Form Editor: Change event to handle fieldgroup indentation
	 * 
	 */
	function formFieldsChangeEvent() {
		var $ol = $formFields.prev('ol.asmList');
		$ol.find('span.asmFieldsetIndent').remove();
		$ol.children('li').children('span.asmListItemLabel').children("a:contains('_END')").each(function() {
			var label = $(this).text();
			if(label.substring(label.length-4) != '_END') return;
			label = label.substring(0, label.length-4);
			var $li = $(this).parents('li.asmListItem');
			$li.addClass('asmFieldset asmFieldsetEnd');
			while(1) {
				$li = $li.prev('li.asmListItem');
				if($li.size() < 1) break;
				var $span = $li.children('span.asmListItemLabel');
				var label2 = $span.text();
				if(label2 == label) {
					$li.addClass('asmFieldset asmFieldsetStart');
					break;
				}
				$span.prepend($('<span class="asmFieldsetIndent"></span>'));
			}
		});
	}

	/**
	 * Form Editor: Change event handler for save flags on "Actions" tab
	 * 
	 */
	function saveFlagsChangeEvent() {
		if($("#form_saveFlags_1").is(":checked")) $("#wrap_form_listFields").slideDown('fast');
			else $("#wrap_form_listFields").hide();
		if($("#form_saveFlags_2").is(":checked")) $("#fieldsetEmail").slideDown('fast');
			else $("#fieldsetEmail").hide();
		if($("#form_saveFlags_4").is(":checked")) $("#fieldset3rdParty").slideDown('fast');
			else $("#fieldset3rdParty").hide();
		if($("#form_saveFlags_8").is(":checked")) $("#fieldsetSavePage").slideDown('fast');
			else $("#fieldsetSavePage").hide();
		if($("#form_saveFlags_32").is(":checked")) $("#fieldsetSpam").slideDown('fast');
			else $("#fieldsetSpam").hide();
		if($("#form_saveFlags_64").is(":checked")) $("#fieldsetResponder").slideDown('fast');
			else $("#fieldsetResponder").hide();

		if($("#form_saveFlags_16").is(":checked")) {
			$("#fieldsetSubmitTo").slideDown('fast');
			$("#wrap_form_listFields").hide();
			$("#fieldsetEmail").hide();
			$("#fieldsetResponder").hide();
			$("#fieldset3rdParty").hide();
			$("#fieldsetSavePage").hide();
			$("#fieldsetSpam").hide();
			$("#wrap_form_saveFlags").find("input[value!=16]").each(function() {
				$(this).removeAttr('checked').attr('disabled', 'disabled');
			});
		} else {
			$("#fieldsetSubmitTo").hide();
			//$("#fieldsetSpam").show();
			$("#wrap_form_saveFlags").find('input:disabled').removeAttr('disabled');
		}
	}

	/**
	 * Form Editor: Initialize the form editor
	 * 
	 */
	function setupEditForm() {

		$("#_ProcessFormBuilderView").click(function(e) {
			setupPreviewViewport('ProcessFormBuilderView');
			return false;
		});

		$("#_ProcessFormBuilderEntries").attr('href', '../listEntries/?id=' + $('#form_id').attr('value'));

		$formFields.change(formFieldsChangeEvent).bind('init', formFieldsChangeEvent);

		$("#_ProcessFormBuilderEmbed").click(function() {
			$.get("../embedForm?id=" + $("#form_id").val(), function(data) {
				$("#ProcessFormBuilderEmbedMarkup").html(data);
				$(".ProcessFormBuilderAccordion").accordion({autoHeight: false, heightStyle: 'content'});
			});
		});

		$("#_ProcessFormBuilderExport").click(function() {
			$.get("../exportForm?id=" + $("#form_id").val(), function(data) {
				$("#ProcessFormBuilderExportJSON").val(data);
			});
		});

		var $columnWidth = $("#columnWidth");
		if($columnWidth.size() > 0 && parseInt($columnWidth.val()) < 1) $columnWidth.val('100');

		// submit/save settings
		if($("#fieldsetActions").length > 0) {
			$("#wrap_form_saveFlags").find('input').change(saveFlagsChangeEvent);
			saveFlagsChangeEvent();
		}

		setupModalFieldEditor();
	}

	/**
	 * Field Editor: Setup the form field editor
	 * 
	 */
	function setupEditField() {
	}

	/**
	 * Entries: Setup the entries list/editor
	 * 
	 */
	function setupEntries() {
		// listing or editing entries
		$("#check_all").click(function() {
			var $checkboxes = $("input[type=checkbox].delete");
			if($(this).is(":checked")) $checkboxes.attr('checked', 'checked');
			else $checkboxes.removeAttr('checked');
		});

		$("#submit_delete_entries").click(function() {
			return confirm($(this).val());
		});
	}

	/**
	 * Initialize ProcessFormBuilder javascript
	 * 
	 */
	function init() {

		// if editing a form or form field then initialize WireTabs
		if($formEditor.length) {
			$formEditor.find("script").remove();
			$formEditor.WireTabs({items: $('.WireTab')});
		}

		if(isEditForm) {
			// editing a form
			setupEditForm();
		} else if($formEditor.length) {
			// editing a form field 
			setupEditField();
		} else {
			// listing or editing entries
			setupEntries();
		}
	}
	
	init();
}

jQuery(document).ready(function($) {
	ProcessFormBuilder($);	
}); 

