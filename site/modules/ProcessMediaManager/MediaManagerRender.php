<?php namespace ProcessWire;

/**
* Media Manager: Render
*
* This file forms part of the Media Manager Suite.
* Renders markup for output in various places in the module.
*
* @author Francis Otieno (Kongondo)
* @version 0.0.12
*
* This is a Copyrighted Commercial Module. Please do not distribute or host publicly. The license is not transferable.
*
* ProcessMediaManager for ProcessWire
* Copyright (C) 2015 by Francis Otieno
* Licensed under a Commercial Licence (see README.txt)
*
*/

class MediaManagerRender extends ProcessMediaManager {

	/**
	 * Set some key properties for use throughout the class.
	 *
	 * @access public
	 * @param object $currentPage The page currently being edited if in InputfieldMediaManager, else blank WireData.
	 * @param object $mediaManagerField The current Media Manager field in the page being edited if in InputfieldMediaManager, else blank WireData.
	 * @param integer $context Whether in ProcessMediaManager (1) vs InputfieldMediaManager(2) context.
	 *
	 */
	public function __construct($currentPage, $mediaManagerField, $context=1) {

		// @note: call parent construct first coz it also sets $this->currentPage and $this->mediaManagerField
		parent::__construct();

		// we almost always need these properties, so set them early

		// current page and media manager field
		$this->currentPage = $currentPage;
		$this->mediaManagerField = $mediaManagerField;
		# set context: ProcessMediaManager (1) versus InputfieldMediaManager (2)
		$this->mmContext = $context;

		$this->mmUtilities = new MediaManagerUtilities($currentPage, $mediaManagerField);
		$this->adminURL = $this->wire('config')->urls->admin;
		// get sanitised url segments
		$this->urlSegments = $this->mmUtilities->getURLSegments();
		$this->urlSeg1 = $this->urlSegments[0];
		$this->urlSeg2 = $this->urlSegments[1];
		$this->urlSeg3 = $this->urlSegments[2];

		$this->jfu = new JqueryFileUpload();

	}

	private $currentPageID;
	private $mediaManagerFieldID;
	private $mediaFieldFull;// for Media Manager field's maxFiles, if used
	private $mmContext;// if in ProcessMediaManager versus InputfieldMediaManager (for render purposes)
	private $insertMode;// if ProcessMediaManager is in Insert Mode: 'add' => InputfieldMediaManager
	private $modalMode;// if ProcessMediaManager is in Modal Mode: 'add', 'rte', or 'link'
	private $rteOrLinkMode;// if exclusively in 'rte' or 'link' modal mode
	private $allowEditMedia;// for modal and inputfield use; if image page can be edited.
	private $mmView;// as per cookie, thumbs view: grid vs tabular
	private $showIcons;// show icons vs text for actions strip: trash and insert/add icons
	private $singleMediaManagerField;
	private $singleMediaManagerFieldClass;
	private $showCustomColumns = array();// for custom columns in saved media Thumbs view: Table
	private $addMediaURL;
	private $addMediaLabel;
	private $actionsWrapperClass;
	private $editMediaBaseURL;
	private $mmActive;// for active view icon CSS
	private $totalCustomColumns;// for calculating table column widths
	private $tableColumnStyle;// for equal-width custom columns if present
	private $processInputName;// for mm in repeaters
	private $galleryIndex;// for blueimp gallery use: if initiating gallery ourselves, to set start index.
	protected $uploadAnywhere;// whether upload anywhere allowed or not


	/* ######################### - MARKUP RENDERERS - ######################### */

	/**
	 * Sets various class properties.
	 *
	 * Used by both modal and inputfield views.
	 *
	 * @access private
	 * @param PageArray $results The Lister results (if in ProcessMediaManager).
	 *
	 */
	private function mmSetProperties($results = array()) {

		$this->results = $results;

		$mediaManagerField = $this->mediaManagerField;
		$currentPage = $this->currentPage;
		## - set some properties on the fly for use later - ##
		$this->currentPageID = $this->currentPage->id;
		$this->mediaManagerFieldID = $this->mediaManagerField->id;

		/* check if in ProcessMediaManager normal vs. ProcessMediaManager Insert ('add') or Modal ('rte/link/add') mode
			@note: in both cases, the context = 1 (i.e. we are not in InputifieldMediaManager)
			@note: Insert Mode is always via modal
			@note: Obviously, Modal Mode is also always via modal
			@note: However, we also use a modal when editing a single media (Page Edit context @see: parent::executeEdit())
		*/

		// ADD mode
		$this->insertMode = 'add' == $this->urlSeg1 ? 1 : 0;
		// RTE OR LINK
		$this->rteOrLinkMode = in_array($this->urlSeg1, array('rte','link')) ? $this->urlSeg1 : null;
		// determine media view in ProcessMediaManager: Either 'All'-0, 'Audio'-1, 'Document'-2, 'Image'-3 OR 'Video'-4
		$this->mediaView = $this->mmUtilities->mediaViewCheck();
		// MODAL mode (add,rte or link)
		$this->modalMode = in_array($this->urlSeg1, array('add','rte','link')) ? 1 : 0;
		$this->blueimpGalleryContainerID = 'processmediamanager-blueimp-gallery';

		// @note: we default to icons if setting not yet saved
		$this->showIcons = $mediaManagerField->showIcons ? $mediaManagerField->showIcons: 1;

		// CONTEXT 2: interaction via InputfieldMediaManager
		if(2 == $this->mmContext) {

			$processInputName = 'media_manager_';
			if($mediaManagerField->mmRepeaterName) $processInputName .= "{$mediaManagerField->mmRepeaterName}[]";
			else $processInputName .= "{$mediaManagerField->name}[]";

			$this->processInputName = $processInputName;

			# selectable pages view (thumbs vs lister)
			// add property current field count property on the fly to mediaManagerField
			$this->mediaManagerField->currentFieldCnt = $currentPage->$mediaManagerField->count();
			$this->mediaFieldFull = $this->mmUtilities->checkMediaFieldFull();// boolean
			$this->blueimpGalleryContainerID = "inputfieldmediamanager-blueimp-gallery-{$mediaManagerField->name}";

			// @note: for ProcessMM context (1), already set in ProcessMediaManager::__construct()
			$this->uploadAnywhere = (int) 1 == $this->mediaManagerField->enableUploadAnywhere && !$this->noUpload ? true: false;

			// Show Custom Columns: InputfieldMediaManager context
			$showColumns = (int) $mediaManagerField->showColumns;
			if(1 == $showColumns) $this->showCustomColumns = $this->mmUtilities->getCustomColumns();
			elseif(2 == $showColumns) $this->showCustomColumns = $this->mmUtilities->getCustomColumnsForMediaManagerField($mediaManagerField);

		}
		// Show Custom Columns: ProcessMediaManager context
		// @note: takes account of media context
		else  {
			$this->showCustomColumns = $this->mmUtilities->getCustomColumns();
		}

		# in single vs multi media manager field
		$this->singleMediaManagerField = $mediaManagerField->maxFiles ==  1 ? true : false;
		$this->singleMediaManagerFieldClass = $this->singleMediaManagerField ? ' mm_single_media' : '';

		# allow selected pages edit
		/*
			@note: allowing editing of media in both ProcessMediaManager and InputfieldMediaManager is controlled by $this->noEdit
			@note: if in InputfieldMediaManager we also check for $mediaManagerField->allowEditSelectedMedia
		*/

		// @todo: test some more with non-superusers
		$this->allowEditMedia = 0;
		if(!$this->noEdit) {
			// if in InputfieldMediaManager, check if editing IS allowed (1==$mediaManagerField->allowEditSelectedMedia)
			if((2 == $this->mmContext && 1 == $mediaManagerField->allowEditSelectedMedia) || (1 == $this->mmContext)) $this->allowEditMedia = 1;
		}

		# get user's cookie-saved thumbs view mode (grid vs tabular)
		// 0=thumbs:grid; 1=thumbs:tabular
		$this->mmView = $this->mmUtilities->getCookie('mm_view_'.(int) $mediaManagerField->id);

		$mode = $this->insertMode ? "{$this->urlSeg1}-{$this->urlSeg3}" : '';
		# others
		$this->editMediaBaseURL = $this->mmUtilities->getFormattedMediaModalURL();
		$this->addMediaURL = $this->mmUtilities->getFormattedMediaModalURL('add',2);

		$this->addMediaLabel = $this->mediaManagerField->modalLinkLabel ? $this->wire('sanitizer')->entities($this->mediaManagerField->modalLinkLabel) : $this->_('Add Media');
		$this->actionsWrapperClass = 'mm_actions_wrapper';
		if($this->modalMode) $this->actionsWrapperClass .=  ' mm_modal';
		$this->mmActive = 'mm_active';

		$this->totalCustomColumns = 1 + count($this->showCustomColumns);// @note: 1 here is for meta column

		// @note: we leave like this for now; in case changes in the future (currently we always have meta column so total here > 0)
		if($this->totalCustomColumns) {
			// @note: 82% since first column 2% and thumbs td 16%
			$width = round(82 / $this->totalCustomColumns, 3);
			$this->tableColumnStyle = " style='width:{$width}%'";
		}
		else {
			$this->tableColumnStyle = ' ';
			$this->mmView = 0;
		}

	}

	/* ######################### - INPUTFIELD - ######################### */

	/**
	 * Calls methods to set properties for and render markup for the inputfield.
	 *
	 * Used in the context of InputfieldMediaManager (context=2).
	 * Does not apply to ProcessMediaManager even if opened via a modal.
	 *
	 * @access public
	 * @return string $out Markup of rendered saved pages view.
	 *
	 */
	public function renderInputfield() {
		$results = $this->currentPage->{$this->mediaManagerField->name};
		$this->mmSetProperties($results);
		$out = $this->renderSavedMediaViews();
		return $out;
	}

	/**
	 * Builds the views for saved pages in the given page field.
	 *
	 * There are two views: list (tabular) and grid (thumbs).
	 *
	 * @access public
	 * @return string $out Markup of pages in the page field.
	 *
	 */
	public function renderSavedMediaViews() {

		$out = '';
		$page = $this->currentPage;
		$mediaManagerField = $this->mediaManagerField;

		$media = $this->results;

		##########################

		// actions strip
		$out .= $this->renderActionsStrip();

		// thumbs wrapper
		$out .= "<div class='clearfix mm_thumbs_wrapper' data-current-page='{$this->currentPageID}' data-media-manager-field='{$mediaManagerField->id}'>";
		// if no pages in the field yet
		if(!count($media)) $out .= $this->renderThumbsViewTemplate();
		else $out .= $this->renderSavedMediaThumbsView($media);

		$out .= "</div>";

		// add gallery widget
		$galleryWidget = $this->jfu->renderGalleryWidget(false,$this->blueimpGalleryContainerID);
		// upload anywhere widget: only show if uploadAnywhere is true (@note: various conditions assesed including noUpload and upload anywhere setting
		$uploadAnywhereWidget = $this->uploadAnywhere ? $this->jfu->renderUploadAnywhereWidget() : '';

		// wrap it up (main wrapper)
		$out =
			"<div id='mm_main_wrapper_{$page->id}_{$mediaManagerField->id}' class='mm_main_wrapper{$this->singleMediaManagerFieldClass}' data-current-page='{$this->currentPageID}' data-media-manager-field='{$mediaManagerField->id}'>" .
				$out .
				'<div class="mm_media_stats_template mm_hide">'. $this->renderBlankGalleryMediaDataTemplate() .'</div>' .
				$galleryWidget . // blueimp gallery widget
				$uploadAnywhereWidget . // anywhere upload jfu for inputfield
				$this->renderMediaViewsMessage() .// for notices
			"</div>";


		return $out;

	}

	/* ######################### - THUMBS: GRID - ######################### */

	/**
	 * Renders the markup for thumbs view.
	 *
	 * Used by both Inputfield and modal views.
	 *
	 * @access private
	 * @param object $pages PageArray to display in the thumbs view.
	 * @return string $out Markup of thumbs view.
	 *
	 */
	private function renderThumbsGrid() {


		$out = '';
		$gridViewOut = '';
		$tabularViewtBody = '';
		$media = null;
		$i = 1;

		$templates =  array('audio' => 'media-manager-audio', 'document' => 'media-manager-document', 'image' => 'media-manager-image', 'video' => 'media-manager-video');
		$fnPrefix = 'media_manager_';
		$mediaManagerFieldID = $this->mediaManagerField->id;

		################################# loop through media results #####################################

		// fetch media to build markup
		/*
			@note:
				- if in ProcessMediaManager: $this->results > PageArray; $page > Page object
				- if in InputfieldMediaManager: $this->results > MediaManagerArray; $page > MediaManager object
		*/
		foreach ($this->results as $page) {

			// ProcessMediaManager context
			if(1 == $this->mmContext) {

				// get right media field for the current page media
				// we get via their templates
				// search for given value and return corresponding key if successful
				$mediaTypeStr = array_search($page->template->name, $templates);
				$mediaField = $fnPrefix . $mediaTypeStr;// e.g. 'media_manager_audio'
				// when output formatting is OFF, file/image fields always behave as arrays. So, we need first() 
				if(count($page->$mediaField) && $page->$mediaField->first()) {
					$media = $page->$mediaField->first();// @note: Pageimage or Pagefile
				}

			}
			// InputfieldMediaManager context
			else {
				// as set in FieldtypeMediaManager::wakeupValue()
				$media = $page->media;// @note: Pageimage or Pagefile
				$media->type = $page->type;// @note: overloading!
				$mediaTypeStr = $page->typeLabel;
			}
			// if no media, skip
			if(!$media) continue;


			################################# set extra media properties #####################################
			// @note: we are setting the properties to Pageimage|Pagefile object AND NOT the MediaManager object itself
			$media->mediaTypeStr = $mediaTypeStr;
			$media = $this->mmUtilities->setMediaProperties($media);

			################################# build markup #####################################

			$dataDeleteAttr = 2 == $this->mmContext ?  " data-delete='{$media->id}'" : "";

			$thumbWrapperClass = 'media_thumb_wrapper ImageOuter gridImage ui-widget mm_item_thumb';

			if($this->insertMode && $media->inField) $thumbWrapperClass .= ' mm_in_field';

			$this->galleryIndex = $i - 1;// for start (index ) in blueimp gallery
			$mediaValue = $media->id . '_' . $media->mediaTypeInt;

			//@note: css ID for li here for distinctiveness in media edit mode when media in field getting updated via ajax!
			// this is in the format: $id = "media-media-page-id-mm-field-id-mediaTypeInt";

			$cssID = "media-{$media->id}-{$mediaManagerFieldID}-{$media->mediaTypeInt}";

			$gridViewOut .=
				"<li id='{$cssID}' class='{$thumbWrapperClass}' data-value='{$page->id}-{$media->mediaTypeInt}' data-gallery-index='{$this->galleryIndex}' data-media-value='{$mediaValue}'".$dataDeleteAttr.">" .

					$this->renderThumb($media) .
					# for Inputfield only #
					// @note: $name[] (name_of_field[]) ensures ProcessWire handles the input
					(1 == $this->mmContext ? '' : "<input class='mm_field' type='hidden' name='" . $this->processInputName . "' value='{$mediaValue}'>") .
				"</li>";

			// build Thumbs View: Tabular <table><tr>s
			if($this->totalCustomColumns) $tabularViewtBody .= $this->renderThumbsViewTabularRows($media, $i);

			$i++;


		}// end outer foreach

		// final Thumbs View Grid View
		$thumbViewGridClass = 'mm_thumbs_view_grid_wrapper mm_thumbs';
		if(1 == $this->mmView) $thumbViewGridClass .= ' mm_hide';
		$uploadAnywhereOverlay = '';
		$uploadAnywhereContainer = '';
		// check if upload anywhere active and valid
		if($this->uploadAnywhere) {
			$dropText = $this->_('Drop files to upload them instantly');
			$uploadAnywhereOverlay = "<div class='mm_upload_anywhere_overlay jfu_dropzone'><p><span>{$dropText}</span></p></div>";
			$thumbViewGridClass .= ' jfu_upload_anywhere';
			$mmFieldID = "<input type='hidden' value='{$this->mediaManagerFieldID}' class='mm_mediamanagerfield_id'>";
			$uploadAnywhereContainer = "<div class='jfu-anywhere-container'>{$mmFieldID}</div>";
		}

		$out .=
			"<div class='{$thumbViewGridClass}'>" .
				$uploadAnywhereOverlay .
				$uploadAnywhereContainer .
				"<ul class='gridImages ui-helper-clearfix' data-gridmode='grid' data-gridsize='130'>{$gridViewOut}</ul>" .

			"</div>";

		###################

		// final Thumbs View Tabular View
		if($this->totalCustomColumns) {
			$thumbViewTabularClass = 'mm_thumbs_view_tabular_wrapper uk-overflow-auto uk-width-1-1 mm_thumbs';
			if(0 == $this->mmView) $thumbViewTabularClass .= ' mm_hide';
			$out .=
				"<div class='{$thumbViewTabularClass}'>" .
					$uploadAnywhereOverlay .
					$this->renderThumbsViewTabular($tabularViewtBody) .
				"</div>";
		}

		// hidden markup for helping with shift-selecting media in all contexts
		// stores id of last selected media to use as start index for shift-selection
		$out .= $this->renderShiftSelectMarkup();

		return $out;

	}



	/**
	 * Renders thumbs view of pages saved in a given page field in inputfield/page edit.
	 *
	 * @access private
	 * @param object $pages PageArray of pages to display in thumbs view.
	 * @return string $out Rendered markup showing thumbs view of pages saved in the page field.
	 *
	 */
	private function renderSavedMediaThumbsView($pages) {
		$out = $this->renderThumbsGrid($pages) . $this->renderThumbGridJSTemplate() . $this->renderThumbTabularJSTemplate();
		return $out;
	}

	/**
	 * Renders markup for a single thumb.
	 *
	 * @param object $media The media to use as the thumb in this view.
	 * @param integer $mode Denotes if in thumbs view: grid (1) vs tabular (2).
	 * @return string $thumb Markup of media thumb.
	 *
	 */
	private function renderThumb($media, $mode=1) {
		$thumb =
			# tooltip
			$this->renderThumbTooltip($media) .
			# media thumb source
			$this->renderThumbSource($media, $mode) .
			# media thumb hover
			$this->renderThumbHover($media, $mode);
		return $thumb;
	}

	/**
	 * Renders the tooltip for an media thumb.
	 *
	 * @access private
	 * @param object $media The media whose thumb tooltip to render.
	 * @return string $out Markup of the tooltip.
	 *
	 */
	private function renderThumbTooltip($media) {

		$out = '';
		$rowsOut = '';
		$descriptionsCheck = '<span class="fa fa-check"></span>';

		$rows = array(
			'title' => array('', $media->shortTitle),
			'description' => array($this->_('Description'), ($media->noDescription ? '' : $descriptionsCheck)),
			'filename' => array($this->_('Name'), $media->basename),
			'dimensions' => array($this->_('Dimensions'), $media->dimensions),
			'filesize' => array($this->_('Filesize'), $media->filesizeStr),
			'variations' => array($this->_('Variations'), $media->variationsCnt),
			'usedcount' => array($this->_('Used'), $media->usedCnt),
			'status' => array($this->_('Status'), $media->mediaStatus),
		);

		// remove dimensions and variations meta for non image types
		if($media->mediaTypeInt !=3 && (int)substr($media->mediaTypeInt, 0, 1) !== 3) {
			unset($rows['dimensions']);
			unset($rows['variations']);
		}

		if($media->noDescription) unset($rows['description']);
		if(!$media->mediaStatus) unset($rows['status']);

		foreach ($rows as $header => $value) {
			if('title' === $header) $theader = '';
			else $theader = '<th>' . $value[0] . '</th>';
			$colSpan = " colspan='2'";// @note: trying to fit longer titles
			$rowsOut .= "<tr>{$theader}<td{$colSpan}>" . $value[1] . "</td></tr>";
		}

		$out = "<div class='gridImage__tooltip'><table>{$rowsOut}</table></div>";

		return $out;

	}

	/**
	 * Render the thumb markup of an media.
	 *
	 * @param object $media The media to generate a thumb for.
	 * @param integer $mode Whether in thumbs view: grid vs tabular.
	 * @return string $out Markup of the thumb source.
	 *
	 */
	private function renderThumbSource($media, $mode=1) {

		/*

			@note:
			Thumbs View: Grid vs. Thumbs View: Table

			<div.gridImage__overflow> => proportional image
			Grid: width:image->width/2; height:130px;
			Table: width:100%; height:auto;

			<img> => proportional image
			Grid: max-width:none; max-height:100%; height: 130px;
			Table: max-width:100%; max-height:none; width: 130px;

			## values hardcoded for now ##

		*/

		$rteLinkClass = '';
		$rteMarkup = '';
		$height = 130;

		// thumbs view: grid
		if(1 == $mode ){
			$wrapperHeight = $height . 'px';
			//$wrapperWidth =  $width . 'px';
			$wrapperWidth =  $media->thumbWidth . 'px';
			$imageAttrStyles = "height='{$height}' style='max-height: 100%; max-width: none;'";
		}
		// thumbs view: table
		else {
			$wrapperHeight = 'auto';
			$wrapperWidth =  '100%';
			$imageAttrStyles = "width='{$height}' style='max-height: none; max-width: 100%;'";
		}

		if($this->rteOrLinkMode) {

			$rteLinkClass = ' rtelink ' . $this->rteOrLinkMode;

			/*
			Example:
			<a href="/mm/page/image/edit?file=1510,obama.svg&amp;modal=1&amp;id=1510" id="rte-1510_3" class="rte">Obama</a>
			*/
			if('rte' == $this->rteOrLinkMode) {
				$href = "{$this->adminURL}page/image/edit?file={$media->id},{$media->name}&modal=1&id={$media->id}";
			}
			else $href = $media->url;

			$rteMarkup = "<a class='{$this->rteOrLinkMode} mm_hide' href='{$href}' title='{$media->title}'>{$media->title}</a>";

		}

		$out =
			"<div class='mm_page_thumb gridImage__overflow' style='width: {$wrapperWidth}; height: {$wrapperHeight};'>" .
				"<img alt='{$media->description}' data-original='{$media->url}' data-h='{$media->height}' data-w='{$media->width}' src='{$media->thumbURL}' {$imageAttrStyles} class='mm_{$media->mediaTypeStr}{$rteLinkClass}'>" .
				$rteMarkup .
			"</div>";

		return $out;


	}

	/**
	 * Renders markup for thumb hover.
	 *
	 * @access private
	 * @param object $media Media whose thumb to render.
	 * @param integer $mode Checks if we are in Process vs Inputfield Media Manager context.
	 * @return string $out Markup of the thumb hover.
	 *
	 */
	private function renderThumbHover($media, $mode=1) {

		$editMediaPageStr = '';
		$viewSuffix = 2 == $mode ? '-tabular' : '';

		## set some modal/non-modal specific values ##

		// ProcesMediaManager context
		if(1 == $this->mmContext) {
			$icon = 'check-square-o';// check-circle, check-square, check
		}

		// InputfieldMediaManager context
		else {
			$icon = 'trash-o';
		}

		$mediaValue = $media->page->id . '_' . $media->mediaTypeInt;

		// if allow edit media
		if(1 == (int) $this->allowEditMedia && !$this->rteOrLinkMode) {
			// media locked for edits (show locked status on hover)
			if($media->locked) {
				$url = '#';
				$editClass = 'edit_pages gridImage__locked';
				//$editStr = $this->_('Locked');
				$editStr = '<span class="fa fa-lock mm_locked"></span>';
			}
			// media unlocked and can be edited
			else {
				$url = $this->editMediaBaseURL . $media->page->id;
				$editClass = 'edit_pages gridImage__edit';
				if(!$this->modalMode) $editClass .= ' pw-modal pw-modal-medium';
				$editStr = $this->_('Edit');
			}

			$editMediaPageStr = "<a href='{$url}' class='{$editClass}'><span>{$editStr}</span></a>";
		}

		$checkBoxClass = 'mm_thumb gridImage__selectbox';// @note: was gridImage__deletebox

		if($this->insertMode && $media->inField) $checkBoxClass .= ' mm_in_field';
		// if in inputfield MM context only
		if(!$this->modalMode) $checkBoxClass .= ' mm_inputfield';

		// no gallery, rte and link classes
		$rteLinkClass = $this->rteOrLinkMode ? ' rtelink ' . $this->rteOrLinkMode : '';

		// icons on the top left of a media thumb
		// @note: was 'gridImage__trash' in original
		$gridImageIcon =
			"<label class='gridImage__icon' for='' data-value='{$media->page->id}-{$media->mediaTypeInt}'>" .

				"<input class='{$checkBoxClass}' id='media-{$media->page->id}-{$media->mediaTypeInt}{$viewSuffix}' name='mm_selected_media' type='checkbox' value='{$mediaValue}' data-media='{$media->page->id}'>" .
				"<span class='mm_select fa fa-{$icon}{$rteLinkClass}'></span>" .
				($this->modalMode && 3 == $media->mediaTypeInt ? "<span class='mm_image_media_variations'>".$this->renderExtraImagesLink($media)."</span>" : '') .
			"</label>";

		// @TODO: DELETE: this changed since version 012. We now emulate behaviour of normal PW image field
		// if single page field and in inputfield, we just need a single click delete of item, so no need for this icon
		//if(2 == $this->mmContext && $this->singleMediaManagerField) $gridImageIcon = '';

		$out = "<div class='gridImage__hover'>" .
					"<div class='gridImage__inner{$rteLinkClass}' data-gallery-id='{$this->blueimpGalleryContainerID}'>" .
						$gridImageIcon .
						$editMediaPageStr .
					"</div>" .
					"<span class='mm_media_preview mm_hide'>".$this->renderGalleryPreview($media)."</span>" .
				"</div>";

		return $out;

	}

	/* ######################### - THUMBS: TABULAR - ######################### */

	/**
	 * Renders table for use in thumbs view: tabular sub-view of pages saved in a given page field.
	 *
	 * @access private
	 * @param string $trows Markup of rows for the table for thumbs view: tabular sub-view.
	 * @return string $out Rendered markup for full table for thumbs view: tabular.
	 *
	 */
	private function renderThumbsViewTabular($trows) {
		$class = 'sortable uk-table uk-table-divider uk-table-hover uk-table-justify uk-table uk-table-middle uk-table-responsive mm_thumbs_view_tabular';
		if($this->singleMediaManagerField) $class .= $this->singleMediaManagerFieldClass;
		$out =
			"<table class='{$class}'>" .
				// table headers
				$this->renderThumbsViewTabularHeaders() .
				// table body
				"<tbody class='mm_thumbs_view_tabular'>{$trows}</tbody>" .
			"</table>";
		return $out;
	}

	/**
	 * Renders table headers for use in thumbs view: tabular sub-view of pages saved in a given page field.
	 *
	 * @access private
	 * @return string $out Rendered markup of the table headers.
	 *
	 */
	private function renderThumbsViewTabularHeaders() {

		$th = '';
		$headers = array(
			'number' => '#',
			'thumb' => $this->_('Thumb'),
			'information' => $this->_('Information'),
		);

		// if in single page field, remove first column
		if($this->singleMediaManagerField && 2 == $this->mmContext) unset($headers['number']);

		foreach ($headers as $k => $header) {
			if(in_array($k, array('number','thumb'))) $th .= "<th>{$header}</th>";
			else $th .= "<th{$this->tableColumnStyle}>{$header}</th>";
		}

		$out = "<thead><tr>{$th}{$this->renderThumbsViewTabularCustomColumnsHeaders()}</tr></thead>";
		return $out;

	}

	/**
	 * Renders table headers for specified custom columns.
	 *
	 * @access private
	 * @return string $out Rendered markup of custom column headers.
	 *
	 */
	private function renderThumbsViewTabularCustomColumnsHeaders() {
		$out = '';
		if(count($this->showCustomColumns)) {
			$out = "<th{$this->tableColumnStyle}>" . implode("</th><th{$this->tableColumnStyle}>", $this->showCustomColumns) . '</th>';
		}
		return $out;
	}

	/**
	 * Renders table rows for use in thumbs view: tabular sub-view of media saved in a given media manager field.
	 *
	 * Each table row contains one media and its meta information.
	 *
	 * @access private
	 * @param object $media Media whose meta to render.
	 * @param integer $i Index denoting position of table row.
	 * @return string $out Rendered markup of the table rows.
	 *
	 */
	private function renderThumbsViewTabularRows($media, $i) {

		$mediaManagerFieldID = $this->mediaManagerField->id;
		$descriptionMarkup = '';
		$tagsMarkup = '';
		$firstColumn = $this->singleMediaManagerField && 2 == $this->mmContext ? '' : '<td class="mm_move">' . $i . '</td>';
		$thumbWrapperClass = 'mm_page_thumb gridImage';

		if($this->insertMode && $media->inField) $thumbWrapperClass .= ' mm_in_field';

		$metaMarkup = "<td class='mm_thumbs_view_tabular_meta'>". $this->renderThumbsViewTabularMeta($media) ."</td>";

		$mediaValue = $media->id . '_' . $media->mediaTypeInt;

		//@note: css ID for tr here for distinctiveness in media edit mode when media in field getting updated via ajax!
		// this is in the format: $id = "media-media-page-id-mm-field-id-mediaTypeInt-tabular";
		$cssID = "media-{$media->id}-{$mediaManagerFieldID}-{$media->mediaTypeInt}-tabular";

		$dataDeleteAttr = 2 == $this->mmContext ?  " data-delete='{$media->id}'" : "";

		$out =
			"<tr id='{$cssID}' data-value='{$media->page->id}-{$media->mediaTypeInt}' data-gallery-index='{$this->galleryIndex}' data-media-value='{$mediaValue}' class='mm_thumb_row mm_item_thumb'{$dataDeleteAttr}>" .
				$firstColumn .
				"<td class='{$thumbWrapperClass}'>" .
					$this->renderThumb($media, $mode=2) .
				"</td>" .
				$metaMarkup .
				// get custom columns
				$this->renderThumbsViewTabularCustomColumns($media->page) .
			"</tr>";

		return $out;

	}

	/**
	 * Renders values of specified custom columns (fields) in saved pages Thumbs View: Table.
	 *
	 * Handles both single and multi-fields.
	 *
	 * @access private
	 * @param object $page Page in page field.
	 * @return string $out Rendered table <td> markup of custom columns.
	 *
	 */
	private function renderThumbsViewTabularCustomColumns($page) {
		$out = '';
		if(count($this->showCustomColumns)) {
			$out = $this->mmUtilities->getCustomColumnsValues($page, $this->showCustomColumns);
		}
		return $out;
	}

	/**
	 * Render meta information for a given media.
	 *
	 * Used to build the meta info column in thumbs view tabular.
	 * Contains info: Title, Description, Tags, Dimensions, Filesize, Variations count, Use count and Status.
	 *
	 * @param object $media The mediat to use as the thumb in a given view.
	 * @return string $out Markup of meta information.
	 *
	 */
	private function renderThumbsViewTabularMeta($media) {

		$out = '';

		$infoArray = array(
			'title' => array('', $media->shortTitle),
			'description' => array($this->_('Description'), $media->description),
			'tags' => array($this->_('Tags'), trim($media->tags,",")),
			'filename' => array($this->_('Name'), $media->basename),
			'dimensions' => array($this->_('Dimensions'), $media->dimensions),
			'filesize' => array($this->_('Filesize'), $media->filesizeStr),
			'variations' => array($this->_('Variations'), $media->variationsCnt),
			'usedCount' => array($this->_('Used'), $media->usedCnt),
			'status' => array($this->_('Status'), $media->mediaStatus),
		);

		// remove dimensions and variations meta for non image types
		if($media->mediaTypeInt !=3 && (int)substr($media->mediaTypeInt, 0, 1) !== 3) {
			unset($infoArray['dimensions']);
			unset($infoArray['variations']);
		}

		if(!$media->mediaStatus) unset($infoArray['status']);

		foreach ($infoArray as $key => $value) {
			$metaClass = " " . $key;
			$text = 'title' == $key ? $value[1] : "<span>" . $value[0] . "</span>: " .	$value[1];
			$out .= "<p class='mm_thumbs_view_tabular_meta{$metaClass}'>{$text}</p>";
		}

		return $out;

	}


	/* ######################### - THUMBS: TABULAR FOR IMAGE MEDIA VARIATIONS/VERSIONS - ######################### */

	/**
	 * Render markup of image thumb for variations/versions view.
	 *
	 * @param object $image The image media whose thumb to render.
	 * @return string $out Markup of image version's thumb.
	 *
	 */
	private function renderTabularVariationsThumb($image) {

		// no gallery, rte and link classes
		$rteLinkClass = '';
		$rteMarkup = '';
		$id = $image->page->id;
		$title = $image->page->title;

		if($this->rteOrLinkMode) {

			$rteLinkClass = ' rtelink ' . $this->rteOrLinkMode;

			/*
			Example:
			<a href="/mm/page/image/edit?file=1510,obama.svg&amp;modal=1&amp;id=1510" id="rte-1510_3" class="rte">Obama</a>
			*/
			if('rte' == $this->rteOrLinkMode) {
				$href = "{$this->adminURL}page/image/edit?file={$id},{$image->name}&modal=1&id={$id}";
			}
			else $href = $image->url;

			$rteMarkup = "<a class='{$this->rteOrLinkMode} mm_hide' href='{$href}' title='{$title}'>{$title}</a>";

		}

		$out =
			// image source
			"<div class='mm_page_thumb gridImage__overflow' style='width: 100%; height: auto;'>" .
				"<img alt='{$image->description}' data-original='{$image->url}' data-h='{$image->height}' data-w='{$image->width}' src='{$image->thumbURL}' width='130' style='max-height: none; max-width: 100%;' class='mm_image{$rteLinkClass}'>" .
				$rteMarkup .
			"</div>" .

			// image hover
			"<div class='gridImage__hover'>" .
				"<div class='gridImage__inner{$rteLinkClass}' data-gallery-id='{$this->blueimpGalleryContainerID}'>" .
					"<label class='gridImage__icon' for='' data-value='{$image->inputMediaValue}'>" .
						// value example: 1074_3 or 1074_31 or image eq(1)
						"<input class='mm_thumb gridImage__selectbox' id='media-{$image->inputMediaValue}' name='mm_selected_media' type='checkbox' value='{$image->inputMediaValue}'>" .
						"<span class='mm_select fa fa-check-square-o{$rteLinkClass}'></span>" .
					"</label>" .
				"</div>" .
			"</div>";


		return $out;

	}

	/* ######################### - TEMPLATES - ######################### */

	/**
	 * Render template for building gallery previews meta data using JS.
	 *
	 * The markup is initially hidden.
	 *
	 * @return void
	 *
	 */
	private function renderBlankGalleryMediaDataTemplate() {

		$out =
			'<ul class="mm_media_stats">' .
				'<li class="mm_media_description mm_meta"><span>' . $this->_('Description') . ':</span> </li>' .
				'<li class="mm_media_tags mm_meta"><span>' . $this->_('Tags') . ':</span> </li>' .
				'<li class="mm_dimensions mm_meta"><span>' . $this->_('Dimensions') . ':</span> </li>' .
				'<li class="mm_filename mm_meta"><span>' . $this->_('Filename') . ':</span> </li>' .
				'<li class="mm_filesize mm_meta"><span>' . $this->_('Size') . ':</span> </li>' .
				'<li class="mm_variationscount mm_meta"><span>' . $this->_('Variations') . ':</span> </li>' .
				'<li class="mm_usedcount mm_meta"><span>' . $this->_('Used') . ':</span> </li>' .
			'</ul>';

		return $out;

	}

	/**
	 * Renders a blank template ready to populate with ajax.
	 *
	 * This is only an initial template in case page field is empty.
	 *
	 * @access private
	 * @return string $out Blank template markup to populate with ajax when page(s) added.
	 *
	 */
	private function renderThumbsViewTemplate() {

		$out = '<span class="mm_no_media">' . $this->_('Nothing added yet.') . '</span>';
		if($this->uploadAnywhere) $out .= '<span class="mm_no_media"> ' . $this->_('You can drag and drop files here.') . '</span>';
		$thumbViewGridClass = 'mm_thumbs_view_grid_wrapper mm_thumbs';
		$thumbViewTabularClass = 'mm_thumbs_view_tabular_wrapper mm_thumbs';

		if(1 == $this->mmView) $thumbViewGridClass .= ' mm_hide';
		if(0 == $this->mmView) $thumbViewTabularClass .= ' mm_hide';

		// @note: we need this here as well if upload anywhere is active since normal inputfield render will not be available if not media has been added yet! This ensures we are ready to upload in any case
		$uploadAnywhereOverlay = '';
		$uploadAnywhereContainer = '';
		if($this->uploadAnywhere) {
			$dropText = $this->_('Drop files to upload them instantly');
			$uploadAnywhereOverlay = "<div class='mm_upload_anywhere_overlay jfu_dropzone'><p><span>{$dropText}</span></p></div>";
			$thumbViewGridClass .= ' jfu_upload_anywhere';
			$mmFieldID = $this->mediaManagerFieldID;
			$mmFieldID = "<input type='hidden' value='{$mmFieldID}' class='mm_mediamanagerfield_id'>";
			$uploadAnywhereContainer = "<div class='jfu-anywhere-container'>{$mmFieldID}</div>";
		}

		// blank thumbs view template
		$out .=
			//  mm_thumbs_view_grid_wrapper
			"<div class='{$thumbViewGridClass}'>".
				"<ul class='gridImages ui-helper-clearfix' data-gridmode='grid' data-gridsize='130'></ul>" .
			"</div>" .
			// mm_thumbs_view_tabular_wrapper
			"<div class='{$thumbViewTabularClass}'>" .
				$this->renderThumbsViewTabular('') .
			"</div>" .
			// blank template for JS
			$this->renderThumbGridJSTemplate() .
			$this->renderThumbTabularJSTemplate() .
			$uploadAnywhereOverlay .
			$uploadAnywhereContainer;

		return $out;

	}

	/**
	 * Render hidden markup for creating thumbs view: grid.
	 *
	 * Used by JS to update inputfield via Ajax.
	 *
	 * @access private
	 * @return string $out Markup of template for updating page field via ajax.
	 *
	 */
	private function renderThumbGridJSTemplate() {

		$out =
			"<ul class='mm_grid_view_template mm_hide'>" .
				"<li class='media_thumb_wrapper ImageOuter gridImage ui-widget mm_item_thumb mm_grid_view_template_item' data-value='0' data-gallery-index='-1' data-media-value='0' data-delete='0'>" .
					// tooltip
					$this->renderThumbTooltipJSTemplate() .
					// image source
					$this->renderThumbSourceJSTemplate() .
					// hover
					$this->renderThumbHoverJSTemplate() .
					// for pw mediaManagerField processing
					"<input class='mm_field' type='hidden' name='{$this->processInputName}' value='0'>" .
				"</li>" .
			"</ul>";

		return $out;

	}

	/**
	 * Render hidden markup for creating thumbs view: tabular.
	 *
	 * Used by JS to update inputfield via Ajax.
	 *
	 * @access private
	 * @return string $out Markup of template for updating page field via ajax.
	 *
	 */
	private function renderThumbTabularJSTemplate() {

		$out = '';
		$metaText = '';
		$customColumns = '';
		foreach($this->showCustomColumns as $fieldID => $fieldLabel) $customColumns .= "<td data-value='{$fieldID}'></td>";

		// no custom columns, so no need for thumbs view: tabular
		if(!$this->totalCustomColumns) return $out;

		$firstColumn = $this->singleMediaManagerField ? '' : "<td class='mm_move'>0</td>";

		$infoArray = array(
			'title' => '',
			'description' => $this->_('Description'),
			'tags' => $this->_('Tags'),
			'filename' => $this->_('Name'),
			'dimensions' => $this->_('Dimensions'),
			'filesize' => $this->_('Filesize'),
			'variations' => $this->_('Variations'),
			'usedCount' => $this->_('Used'),
			'status' => $this->_('Status'),
		);

		foreach ($infoArray as $key => $value) {
			$metaClass = " " . $key;
			// @note: removed the ':' from the end of the </span>. Re-adding in JS
			$text = $key == 'title'  ? $value : "<span>" . $value . "</span> ";
			$extraText = $key == 'status' ? "<span class='mm_status fa fa-lock'></span>" : '';
			$metaText .= "<p class='mm_thumbs_view_tabular_meta{$metaClass}'>{$text} {$extraText}</p>";
		}

		$out =
			"<div class='mm_tabular_view_template mm_hide'>" .
				"<table>" .
					"<tbody>" .
						"<tr data-value='0' data-gallery-index='-1' data-media-value='0' class='mm_thumb_row mm_item_thumb'>" .
							$firstColumn .
							// @note: contents here similar to grid template, so sharing (we'll grab from li)
							"<td class='mm_page_thumb gridImage'></td>" .
							// @note: to be populated JS side with <p></p> for various meta data @see: renderThumbsViewTabularMeta()
							"<td class='mm_thumbs_view_tabular_meta'>" .
							 	$metaText .
							"</td>" .
							// @note: although easier in JS, the columns are getting populated wrongly since the JSON indexing is based on the field ID, sorted ascending. This is different from how we retrieve the fields server-side. Hence, we send columns here to match with data-value
							$customColumns .
						"</tr>" .
					"</tbody>" .
				"</table>" .
			"</div>";

		return $out;

	}

	/**
	 * Render hidden markup for template to create thumbs tooltip.
	 *
	 * Used by JS.
	 *
	 * @access private
	 * @return string $out Markup of the tooltip template.
	 *
	 */
	private function renderThumbTooltipJSTemplate() {


		// @todo: could be refactored! repeating code!
		$out =
		"<div class='gridImage__tooltip'>" .
			"<table>" .
				"<tbody>" .
					"<tr>" . // 0 tr
						"<td colspan='2'>0</td>" . // 1. title
					"</tr>" .
					"<tr>" . // 1st tr
						"<th>". $this->_("Description") . "</th>" . // 2. description
						"<td colspan='2'>" .
                       		"<span class='fa fa-check'></span>" .
                    	"</td>" .
					"</tr>" .
					"<tr>" . // 2nd tr
						"<th>". $this->_("Name") . "</th>" .// 3. name/basename
						"<td colspan='2'>0</td>" .
					"</tr>" .
					"<tr>" . // 3rd tr
						"<th>". $this->_("Dimensions") . "</th>" . // 4. dimensions (image media only)
						"<td colspan='2'>0</td>" .
					"</tr>" .
					"<tr>" . // 4th tr
						"<th>". $this->_("Filesize") . "</th>" . // 5. filesize
						"<td colspan='2'>0 kB</td>" .
					"</tr>" .
					"<tr>" . // 5th tr
						"<th>". $this->_("Variations") . "</th>" . // 6. variations (image media only)
						"<td colspan='2'>0</td>" .
					"</tr>" .
					"<tr>" . // 6th tr
						"<th>". $this->_("Used") ."</th>" . // 7. used count
						"<td colspan='2'>0 </td>" .
					"</tr>" .
					"<tr>" . // 7th tr
						"<th>". $this->_("Status") . "</th>" . // 8. locked status
						"<td colspan='2'>" .
						// @note: ONLY needed for locked status since unpublished media can't be added to InputfieldMediaManager!
							"<span class='mm_status fa fa-lock'></span>" .// locked staus
						"</td>" .
					"</tr>" .
				"</tbody>" .
			"</table>" .
		"</div>";



		return $out;

	}

	/**
	 * Render hidden markup for template to create thumbs source.
	 *
	 * Used by JS.
	 *
	 * @access private
	 * @param integer $mode Denotes if in thumbs view: grid vs tabular.
	 * @return string $out Markup of the thumb source template.
	 *
	 */
	private function renderThumbSourceJSTemplate($mode=1) {
		// @todo: could be refactored! repeating code
		$height = 130;// @note: hardcoded for now
		if(1 == $mode) {
			$wrapperHeight = '130px';
			$wrapperWidth =  '0px';
			$imageAttrStyles = "height='{$height}' style='max-height: 100%; max-width: none;'";
		}
		// @note: this part of template not currently in use; amending in JS instead as per values below
		else {
			$wrapperHeight = 'auto';
			$wrapperWidth =  '100%';
			$imageAttrStyles = "width='{$height}' style='max-height: none; max-width: 100%;'";
		}

		$out =
			"<div class='mm_page_thumb gridImage__overflow' style='width: {$wrapperWidth}; height: {$wrapperHeight};'>" .
				"<img alt='0' data-original='0' data-h='0' data-w='0' src='0' {$imageAttrStyles}>" .
			"</div>";

		return $out;

	}

	/**
	 * Render hidden markup for template to create thumbs source.
	 *
	 * Used by JS.
	 *
	 * @access private
	 * @return string $out Markup of the thumb hover template.
	 *
	 */
	private function renderThumbHoverJSTemplate() {
		// @todo: could be refactored! repeating code!

		// class to show we are in inputfield MM context (as opposed to in modal)
		$mmInputfieldClass = !$this->modalMode ? ' mm_inputfield' : '';
		// icon on the top left of an image
		// @note: was 'gridImage__trash' in original
		$gridImageIcon =
			"<label class='gridImage__icon' for='' data-value='0'>" .
				"<input class='mm_thumb gridImage__selectbox{$mmInputfieldClass}' id='media-0' name='mm_selected_media' type='checkbox' value='0'>" .
				"<span class='mm_select fa fa-trash-o'></span>" .
			"</label>";

		// @TODO: DELETE: this changed since version 012. We now emulate behaviour of normal PW image field
		// if single page field and in inputfield, we just need a single click delete of item, so no need for this icon
		//if(2 == $this->mmContext && $this->singleMediaManagerField) $gridImageIcon = '';

		$out =
			"<div class='gridImage__hover'>" .
				"<div class='gridImage__inner' data-gallery-id='{$this->blueimpGalleryContainerID}'>" .
					$gridImageIcon .
					"<a href='{$this->editMediaBaseURL}' class='edit_pages gridImage__edit pw-modal pw-modal-medium'>" .
						// @note: removed in JS if page not editable if added via this template
						"<span>". $this->_("Edit") . "</span>" .
					"</a>" .
				"</div>" .
				$this->renderGalleryPreviewJSTemplate() .
			"</div>";

		return $out;

	}

	/**
	 * Render hidden markup for template to create gallery preview source.
	 *
	 * Used by JS.
	 *
	 * @access private
	 * @return string $out Markup of the gallery preview template.
	 *
	 */
	private function renderGalleryPreviewJSTemplate() {

		$blueimpGalleryContainerID = $this->blueimpGalleryContainerID;

		// @note: we build the data-attributes in js instead!
			$out =
			"<span class='mm_media_preview mm_hide'>" .
				"<a " .
					"href='#'" .
					" class='mm_preview'" .
					" title=''" .
					" type=''" .
				">".
				"</a>" .
			"</span>";

		return $out;

	}

	/* ######################### - ICONS - ######################### */

	/**
	 * Renders the action links in the inputfield/page edit.
	 *
	 * @access public
	 * @return string $actions Rendered markup of action panel.
	 *
	 */
	private function renderActionsStrip() {

		$out = '';

		// actions label wrapper
		$out .= '<label class="mm_actions_panel" for="">';

		# allowed media as applicable (text)
		$out .= $this->renderAllowedMediaMarkup();
		# used media count as applicable (text)
		$out .= $this->renderUsedMediaCountMarkup();

		# thumbs view switchers
		// only show view switchers if custom columns available to show in thumbs view: tabular
		if($this->totalCustomColumns) {
			## tabular switcher
			$out .= $this->renderThumbsViewTabularSwitcher();
			## grid switcher
			$out .= $this->renderThumbsViewGridSwitcher();
		}

		# add media
		$out .= $this->renderAddPagesIcon();

		$out .=	'</label>';

		// wrap it up
		$out = "<div class='{$this->actionsWrapperClass}'>{$out}</div>";

		return $out;

	}

	/**
	 * Render markup for the strip showing allowed media types as applicable.
	 *
	 *
	 * @access private
	 * @return string $out Markup of the allowed media.
	 *
	 */
	private function renderAllowedMediaMarkup() {
		// show list of allowed media types if 'allowed media' is in effect
		$out =
			'<span class="media_allowed">'.
				'<span class="mm_allowed_media_label">' .  $this->_('Allowed media types: ') . '</span>' .
				$this->mmUtilities->allowedMediaTypesStr() .
			'</span>';
		return $out;
	}

	/**
	 * Render markup for the strip showing used media count if applicable.
	 *
	 *
	 * @access private
	 * @return string $out Markup of the used media count.
	 *
	 */
	private function renderUsedMediaCountMarkup() {
		// show used media counts for this field in cases where max files is limited
		$out = '';
		$mediaManagerField = $this->mediaManagerField;

		if((int) $mediaManagerField->maxFiles){
			// show used count
			$out =
				'<span class="mm_media_count_wrapper">' .
					'<span class="mm_media_count_label">' .  $this->_('Used: ') . '</span>' .
					'<span class="mm_media_count">' . $mediaManagerField->currentFieldCnt  . '/' . $mediaManagerField->maxFiles . '</span>' .
				'</span>';
			// add media field full text
			if($this->mediaFieldFull) $out .= '(<span class="mm_media_field_full">' . $this->_('Media field full') . '</span>)';
			// hidden inputs with currentFieldCnt and maxFiles count
			// to help JS update of counts if media removed but page not yet saved, etc
			$out .= "<input type='hidden' value='{$mediaManagerField->currentFieldCnt}' class='mm_current_field_count'>";
			$out .= "<input type='hidden' value='{$mediaManagerField->maxFiles}' class='mm_max_files'>";

		}

		return $out;

	}

	/**
	 * Renders the icon and link for adding media to InputfeldMediaManager.
	 *
	 * @access private
	 * @return string $out Markup of icon and link to add media to the field.
	 *
	 */
	private function renderAddPagesIcon() {

		$out = '';

		// show icons actions
		if($this->showIcons == 1) {
			$actionMarkup = "<i class='fa fa-plus-circle'></i>";
			$actionTitle = "title='{$this->addMediaLabel}'";
		}
		// show text actions
		else {
			$actionMarkup = $this->addMediaLabel;
			$actionTitle = "";
		}

		if(!$this->mediaFieldFull) {
			$class = 'mm_action pw-modal pw-modal-medium mm_add_media';
			$out =
				"<a class='{$class}' data-action='0' href='{$this->addMediaURL}' {$actionTitle}>" .
				$actionMarkup .
				"</a>";
		}

		return $out;

	}

	/**
	 * Renders the icon and link for switching to thumbs view: grid.
	 *
	 * @access private
	 * @return string $out Markup of icon and link to switch to grid view.
	 *
	 */
	private function renderThumbsViewGridSwitcher() {
		$title = $this->_('Grid View');
		$class = 'mm_grid_view mm_views';
		if(0 == $this->mmView) $class .= " {$this->mmActive}";
		$out = "<a class='{$class}' href='#' title='{$title}'><i class='fa fa-th'></i></a>";
		return $out;
	}

	/**
	 * Renders the icon and link for switching to thumbs view: tabular.
	 *
	 * @access private
	 * @return string $out Markup of icon and link to switch to tabular view.
	 *
	 */
	private function renderThumbsViewTabularSwitcher() {
		$title = $this->_('Table View');
		$class = 'mm_tabular_view mm_views';
		if(1 == $this->mmView) $class .= " {$this->mmActive}";
		$out = "<a class='{$class}' href='#' title='{$title}'><i class='fa fa-list'></i></a>";// fa-th-list
		return $out;
	}

	/* ######################### - MODAL  - ######################### */

	/**
	 * Renders action button for modal view.
	 *
	 * @access private
	 * @param array $options Options for the button.
	 * @return string $out String Markup of action button.
	 *
	 */
	private function renderModalActionsButton($options) {
		$f = $this->wire('modules')->get('InputfieldSubmit');
		$f->attr('id+name', $options['idname']);
		$f->attr('value', $options['value']);
		if($options['class']) $f->addClass($options['class']);
		$f->attr('data-delete', $options['data-delete']);
		$out = $f->render();
		return $out;
	}

	/**
	 * Gateway for rendering ProcessMediaManager modal page views.
	 *
	 * Used by some of the executeXXX() methods.
	 * Returns markup of renderMenu() and JFU renderGalleryWidget used in all previews.
	 *
	 * @return $out Markup for the execute methods.
	 *
	 */
	public function renderMediaViewsTopPanel() {

		$galleryWidgetExclusions = array('upload', 'settings', 'cleanup');
		$excludeCnt = $this->mmUtilities->arrayInArrayCheck($this->urlSegments, $galleryWidgetExclusions);
		// @note: filtering for duplicates so can have two galleries in thumbs view (grid and tabular) but using same gallery container
		$galleryWidget = !$excludeCnt ? $this->jfu->renderGalleryWidget(false,$this->blueimpGalleryContainerID) : '';

		$out =
			'<div id="mm_top_panel" class="mm_top_panel">' .
				//  navigation + actions wrapper
				'<div id="mm_menu_actions_wrapper" uk-grid>' .
					// navigation
					'<div class="uk-width-expand">' .
						'<nav class="uk-navbar-container uk-navbar-transparent" uk-navbar>' .
							'<div id="mm_menu" class="uk-navbar-left">'.$this->renderMenu().'</div>' .
						'</nav>' .
					'</div>' .
					// bulk actions
					'<div id="mm_actions" class="mm_actions uk-width-1-3@s">'.
						// if in 'add' [to current page] mode, output 'insert media' button
						$this->renderAddActionMarkup() .
						// if media pages, output bulk actions markup
						$this->renderActions().
					'</div>' .
				'</div>' .

				'<div id="mm_tags_input_wrapper">' .
					$this->renderTagsActionMarkup().
				'</div>' .
				'<div class="mm_media_stats_template mm_hide">'. $this->renderBlankGalleryMediaDataTemplate() .'</div>' .
				'<div id="mm_ul_messages_wrapper">'.$this->renderMediaViewsMessage().'</div>' .
				$galleryWidget .
			'</div>';

		return $out;

	}

	/**
	 * Renders thumbs view of ProcessMediaManager.
	 *
	 * Used in the context of ProcessMediaManager only.
	 * Whether opened normally or via a modal (InputfieldMediaManager).
	 *
	 * @access public
	 * @param PageArray $results PageArray of selectable pages for the given page field.
	 * @param string $finalSelector String with final lister selector to show if config debug is true.
	 * @return string $out Markup to output via ProcessVisualPageSelector::renderResults().
	 *
	 */
	public function renderMediaViews($results, $finalSelector) {

		// set some shared class properties
		$this->mmSetProperties($results);

		$out ='';

		$results = $this->results;
		$currentPage = $this->currentPage;
		$mediaManagerField = $this->mediaManagerField;
		$currentPageID = (int) $this->currentPage->id;
		$mediaManagerFieldID = (int) $this->mediaManagerField->id;
		$savedSettings = $this->savedSettings;
		// for JFU anywhere in ProcessMM
		$dropFilesHere = '';

		$start = $results->getStart();
		$limit = $results->getLimit();
		$end = $start+$results->count();
		$total = $results->getTotal();

		$uploadAnywhereContainer = '';
		if($this->uploadAnywhere) {
			$uploadAnywhereContainer = "<div class='jfu-anywhere-container'></div>";
		}

		$pgOut = '';
		if(count($results)) {
			$headline = sprintf(__('%1$d to %2$d of %3$d'), $start+1, $end, $total);

			if($total > $limit) {
				$pager = $this->wire('modules')->get('MarkupPagerNav');
				#Solution for pagination when using URL segments
				// get the url segment string.
				$url = $this->wire('page')->url . $this->wire('input')->urlSegmentsStr .'/';
				$pgOut = $results->renderPager(array('baseUrl' => $url));
				 // specifically identify page1, otherwise link doesn't work: @ryancramer
				$pgOut = str_replace($url . "'", $url . "?pageNum=1'", $pgOut);
			}

		}

		else {
			$headline = $this->_('No results');
			if($this->uploadAnywhere) $dropFilesHere = $this->_('Drop files here to upload them instantly.');
		}

		if(isset($savedSettings['active_filter'])) {
			$headline .= ' <small>('. $this->_('active filter applied to results') . ')</small>';
		}

		$thumbsWrapperClass = 'mm_thumbs_wrapper';
		$id = "mm_thumbs_wrapper_{$currentPageID}_{$mediaManagerFieldID}";// @note: for our JFU 'anywhere upload'
		if($this->modalMode) $thumbsWrapperClass .= ' mm_modal';
		$out .= "<div id='{$id}' class='{$thumbsWrapperClass}' data-current-page='{$currentPage->id}' data-media-manager-field='{$mediaManagerField->id}'>";

		// @note: the container is all we need! just pass this as an option to jquery file upload. we don't even need an input type=file #fileupload
		$out .= $uploadAnywhereContainer;

		$out .=
			'<div id="top_pager">' .
				// live sort + sort order switcher (<select>)
				$this->renderMiniActions() .
				'<h2 class="results_headline">' . $headline . '</h2>' .
				$pgOut  .
			'</div>';

		$out .= $this->renderModalThumbsGrid($results) . $dropFilesHere;// markup
		$out .= '</div>';// end div.mm_thumbs_wrapper

		// bottom  final selector if debug=true
		$finalSelector = $this->wire('config')->debug ? "<p id='final_selector' class='notes'>" . $this->wire('sanitizer')->entities($finalSelector) . "</p>" : '';


		$out .= '<div id="bottom_pager">' . $pgOut . $finalSelector .'</div>';

		// upload anywhere widget: only show if uploadAnywhere is true (@note: various conditions assesed including noUpload and upload anywhere setting)
		$out .= $this->uploadAnywhere ? $this->jfu->renderUploadAnywhereWidget() : '';

		# For lister (prevents JS errors {unknown token, etc})
		$out .= $this->renderProcessListerScriptMarkup();

		return $out;

	}

	/**
	 * Renders the modal thumbs view of selectable pages.
	 *
	 * @access public
	 * @param object $pages PageArray to output in modal thumbs view grid.
	 * @return string $out Markup of thumbs view.
	 *
	 */
	public function renderModalThumbsGrid($pages) {
		$out = $this->renderThumbsGrid($pages);
		// if in link mode, add hidden anchor markup needed for inserting MM links into RTE
		if('link' == $this->rteOrLinkMode) $out .= $this->renderRTELinkMarkup();
		return $out;
	}

	/**
	 * Render markup of single media edit.
	 *
	 * Used in 'in-media-edit-mode' in ProcessMediaManager::executeEdit.
	 *
	 * @access public
	 * @param Page $editMedia The Page opened via mm modal for editing.
	 * @return string $out Markup of back link to main landing page for modal.
	 *
	 */
	public function renderModalSinglePageEditActions($editMedia) {

		$currentPage = $this->currentPage;
		$mediaManagerField = $this->mediaManagerField;

		// prepare variables
		$button1 = '';
		$button2 = false;
		$idName = '';
		$value = '';
		$lockedStatus = '';
		$unpublishedStatus = '';
		$pageIDInput = '';
		$mediaManagerFieldIDInput = '';
		$editMediaIDInput = '';
		$modalModeEditInput = '';
		$mediaInFieldInput = '';

		# Backlink from Editing Single Media Page OR view Image Media Variations/Versions
		$backLink = $this->renderBackLink();

		############################

		/* @note:
			- check if media page being edited is in the Media Manager field already
			- that would mean we are in 'direct single media page edit mode' initiated either from the InputfieldMediaManager (saved media (pages) view) or ProcessMediaManager
			- If the media being edited is 'in the MM field', it fires an ajax call whilst the latter shouldn't.
			- This updates the markup in the Inputfield RE the edited media.
			- this also helps against unintentionally adding or replacing media pages in the InputfieldMediaManager just because they have been edited!
		*/

		$mediaInMediaManagerField = $this->mmUtilities->checkMediaInField($editMedia);
		// @note: only in single media page edit do we use 'id' param string.

		$mediaInField = (int) $mediaInMediaManagerField;

		if($editMedia->is(Page::statusLocked)) {
			$mediaLockedWarning = $this->_('This media is locked for edits.');
			$icon = "<i class='fa fa-exclamation-circle pw-nav-icon fa-fw'></i>";
			$lockedStatus = "<p class='mm_locked uk-text-danger'>{$icon} {$mediaLockedWarning}</p>";
		}

		// edit page is unpublished (we need two buttons)
		if($editMedia->is(Page::statusUnpublished)) {
			$idName = 'submit_publish_copy';
			$value = $this->_('Publish');
			$button2 = true;
			$mediaUnpublishedWarning = $this->_('This media is unpublished and cannot be added to pages.');
			$icon = "<i class='fa fa-exclamation-circle pw-nav-icon fa-fw'></i>";
			$unpublishedStatus = "<p class='mm_unpublished uk-text-danger'>{$icon} {$mediaUnpublishedWarning}</p>";
		}

		// edit page is published
		else {
			$idName = 'submit_save_copy';
			$value = $this->_('Save');
		}

		$options = array('idname' => $idName, 'value' => $value, 'class' => "mm_action_save_page", 'data-delete' =>$editMedia->id);
		$button1 = $this->renderModalActionsButton($options);

		if($button2) {
			$options['idname'] = 'submit_save_unpublished_copy';
			$options['value'] = $this->_('Save + Keep Unpublished');
			$options['class'] = $options['class'] . ' ui-priority-secondary';
			//$options['showInHeader'] = 0;
			$button2 = $this->renderModalActionsButton($options);
		}

		// if in modal mode EDIT: (MEANING: in a ProcessMediaManager Modal [RTE/LINK/ADD]), we show a back link to ProcesMediaManager
		if($mediaInField) {
			/*
				@note: There are 3 scenarios here:
				1. We are editing a media page from within normal ProcessMediaManager: We don't need to update anything. Once the page has been edited and modal closed, Lister Results will be refreshed
				2. We are editing a media page, having opened ProcessMediaManager in a modal via InputfieldMediaManager using an ADD/INSERT action: We will need to update the inputfield via Ajax in case the media page being edited is part of the saved media in the Inputfield.

				3. We are editing a media page, having opened it directly for editing whilst in InputfieldMediaManager using the EDIT link on the media in the Inputfield: We will need to update the inputfield via Ajax in case the media page being edited is part of the saved media in the Inputfield. @note: in this case, there is no 'back to media items link'!

				So, here we only need to add these hidden inputs if in 'add' AND 'edit media page' mode (2 and 3)
			*/

			$pageIDInput = '<input name="currentPageID" type="hidden" id="currentPageID" value="'. $currentPage->id .'">';
			$mediaManagerFieldIDInput = '<input name="mediaManagerFieldID" type="hidden" id="mediaManagerFieldID" value="'. $mediaManagerField->id .'">';
			$editMediaIDInput = '<input type="hidden" id="editMediaID" value="'. $editMedia->id .'">';
			// insert mode Editing (to signal ajax updating of InputfielMM)
			$modalModeEditInput = '<input name="modalModeEdit" type="hidden" id="modalModeEdit" value="'. $currentPage->id .'">';
			// insert mode Editing (to signal ajax updating of InputfielMM)
			// for media in field, we need this to signal JavaScript if to send ajax request to update parent window that the media has been updated
			$mediaInFieldInput = '<input name="modalMediaEditInField" type="hidden" id="modalMediaEditInField" value="'. $mediaInField .'">';
		}

		// wrap it up
		$out = '<div id="mm_modal_single_page_edit">' . $lockedStatus . $unpublishedStatus . $pageIDInput . $mediaManagerFieldIDInput . $editMediaIDInput . $modalModeEditInput . $mediaInFieldInput. $backLink . $button2 . $button1 .  '</div>';

		return $out;

	}

	/**
	 * Render page for selecting an image media's variations for inserting into an InputfieldMediaManager.
	 *
	 * Only used in modal context call via the Inputfield.
	 *
	 * @access public
	 * @return string $out Markup of rendered variations selection form.
	 *
	 */
	public function renderModalVariations() {

		$out = '';
		$input = $this->wire('input');
		$backLink = $this->renderBackLink();

		$mediaPage = $this->wire('pages')->get((int) $input->id);

		// @note: if no image media page or if access directly. Otherwise, we normally only show variations link on image media that have variations OR extra images (i.e. eq(1) or >)
		if(!$mediaPage) {
			$out = '<h2>' . $this->_('No variations found') . '</h2>';
			return $out;
		}

		$this->rteOrLinkMode = $this->wire('sanitizer')->pageName($this->wire('input')->get->rtelink);

		$headline =  sprintf(__('Media Manager: Variations for %s.'), $mediaPage->title);
		$this->headline($headline);

		$table = $this->renderModalVariationsTable($mediaPage);

		$form = $this->wire('modules')->get('InputfieldForm');
		$form->attr('id', 'ImageVariations');
		$form->action = "./";

		$form->appendMarkup = $table->render();

		$submit = '';
		$notes = '';
		$topActions =
			"<div id='mm_variations_submit' class='mm_actions'>".
				"{$backLink}";

		if(!$this->rteOrLinkMode) {

			$f = $this->wire('modules')->get('InputfieldSubmit');
			$f->attr('id+name', 'mm_add_variations_btn');
			$f->attr('value', $this->_('Insert Selected'));
			$f->icon = 'plus-circle';
			$submit = $f->render();
			$notes = $this->_('Select image media versions you want to insert in the page');

		}

		$form->description = $notes;
		$topActions .= $submit . $this->renderMediaViewsMessage() . '</div>';
		$form->prependMarkup = $topActions;

		$f = $this->wire('modules')->get('InputfieldHidden');
		$f->attr('id+name', 'currentPage_id');
		$f->attr('value', $this->currentPage->id);
		$form->add($f);

		$f = $this->wire('modules')->get('InputfieldHidden');
		$f->attr('id+name', 'mediaManagerField_id');
		$f->attr('value', $this->mediaManagerField->id);
		$form->add($f);

		$f = $this->wire('modules')->get('InputfieldHidden');
		$f->attr('id+name', 'mediaPage_id');
		$f->attr('value', $mediaPage->id);
		$form->add($f);

		$out = $form->render();
		if('link' == $this->rteOrLinkMode) $out .= $this->renderRTELinkMarkup();

		return $out;

	}

	/**
	 * Prepares a table of variations/versions of images of a given media page.
	 *
	 * Rendered in a form in renderModalVariations().
	 *
	 * @param Page $mediaPage The page with the media manager images whose variations we want to display.
	 * @return object $table The table of variations to render.
	 *
	 */
	private function renderModalVariationsTable($mediaPage) {

		$images = $mediaPage->media_manager_image;

		$variationsText = $this->_('Variations');

		$table = $this->wire('modules')->get('MarkupAdminDataTable');
		$table->setEncodeEntities(false);
		$table->setID('mm_extraimages_and_variations');
		$table->setClass('mm_thumbs_view_tabular');

		$headerOptions = array(
				'#',
				$this->_('Image', 'th'),
				$this->_('File', 'th'),
				$this->_('Size', 'th'),
				$this->_('Modified', 'th'),
		);

		$table->headerRow($headerOptions);


		$imageMediaSuffix = "{$mediaPage->id}_3";

		$eq = 0;// @note: using this because $image->sort does not seem to be working
		$cnt = 1;
		foreach ($images as $image) {

			$imageThumb = $image->height(260);
			$image->thumbURL = $imageThumb->url;
			$image->inputMediaValue = $eq == 0 ? $imageMediaSuffix : "{$imageMediaSuffix}{$eq}";
			$rowOptions = array('class'=>'mm_original_image mm_thumb_row media_thumb_wrapper','attrs' => array('data-value' => $image->page->id));

			$table->row(
				array(
					// count
					$cnt,
					array($this->renderTabularVariationsThumb($image), 'mm_page_thumb gridImage'),
					// image name
					"<a class='mm_preview' href='$image->url'>{$image->name}</a><br><span class='detail'>{$image->width}x{$image->height}</span>",
					// image size
					"$image->filesizeStr",
					// date modified
					date('Y-m-d H:i:s', $image->modified),

				),
				$rowOptions);
				$cnt++;
				$eq++;
		}

		return $table;

	}

	/**
	 * Render wrapper for modal messages.
	 *
	 * @access public
	 * @return string $out Markup of element to use as wrapper for modal messages.
	 *
	 */
	public function renderMediaViewsMessage() {
		// @note: class 'pw-notices' is for AdminThemeUiKit and 'ui-widget' is for AdminThemeDefault and AdminThemeReno
		$out = '<ul class="mm_message_wrapper pw-notices ui-widget NotificationGhosts NotificationGhostsRight"></ul>';
		return $out;
	}

	/**
	 * Render backlink markup for use in rte/link/add modals.
	 *
	 * Used when editing single media or in variations/versions modal.
	 *
	 * @access public
	 * @return string $backLink Markup of back link.
	 *
	 */
	public function renderBackLink() {

		// @note: currently EDIT MEDIA MODE BACKLINK only used with 'add' (insert) and not 'rte' or 'link'

		$url = '';
		$seg = '';
		$mediaContext = '';
		$backLink = '';

		# /edit/1138-118/add-/?modal=1&id=1040 OR  /edit/1138-118/add-image/?modal=1&id=1040
		$segStr = explode('-',$this->urlSeg3);// the 'add-xxx'
		$currentPage = $this->currentPage;
		$mediaManagerField = $this->mediaManagerField;
		$input = $this->wire('input');

		if($segStr[0]) {

			$mode = $segStr[0];

			if(isset($segStr[1]) && $segStr[1]) $mediaContext = "{$segStr[1]}/";
			// wanted URL format: '/add/1234-108/?modal=1' OR '/add/1234-108/document/?modal=1'
			if('add' == $mode) {
				$seg .= "add/{$currentPage->id}-{$mediaManagerField->id}/";
				$seg .= "{$mediaContext}?modal=1";
			}
			// @note: no edit in RTE or LINK mode
			// wanted URL format: 'rte/image/1234/?modal=1&id=5678&edit_page_id=5678
			elseif('rte' == $mode) {
				$seg .= "rte/image/?modal=1";//
				$editPageID = (int) $input->get->id;
				$seg .= "&id={$editPageID}&edit_page_id={$editPageID}";
			}
			// wanted URL format: '/link/?modal=1' OR '/link/audio/?modal=1'
			elseif('link' == $mode) $seg .= "link/{$mediaContext}?modal=1";

		}

		if($seg) {
			$url = "{$this->adminURL}media-manager/" . $seg;

			$title = $this->_('Back to all media');
			$backIcon = '<i class="fa fa-fw fa-arrow-circle-left"></i>';
			$backLink = "<a id='mm_back_to_all_media' title='{$title}' href='{$url}'>" .
							$backIcon .
							$title .
						"</a>";
		}

		return $backLink;

	}

	/**
	 * Render hidden div to keep ProcessPageLister Happy.
	 *
	 * Helps avoid errors of unknown token '=' around lines #262-271 in ProcessPageLister.js
	 * Lister JS looks for a div with the id=ProcessListerScript.
	 *
	 * @access private
	 * @return string $out. Markup of empty div for ProcessPageLister.
	 *
	 */
	private function renderProcessListerScriptMarkup() {
		$out = '<div id="ProcessListerScript"></div>';
		return $out;
	}

	/**
	 * Render hidden div to keep ProcessPageLister Happy.
	 *
	 * Helps avoid errors of unknown token '=' around lines #262-271 in ProcessPageLister.js
	 * Lister JS looks for a div with the id=ProcessListerScript.
	 *
	 * @access private
	 * @return string $out. Markup of empty div for ProcessPageLister.
	 *
	 */
	private function renderRTELinkMarkup() {
		$out = '<div id="mm_rte_link_wrapper" class="mm_hide"><a id="media_rte_link" href="" title="">RTE Link</a></div>';
		return $out;
	}

	/**
	 * Render hidden markup to store last selected media ID.
	 *
	 * Helps with the JS shift-selecting feature.
	 * Using shift, users can select all media between a range.
	 *
	 * @access private
	 * @return string $out. Markup for shift-selection.
	 *
	 */
	private function renderShiftSelectMarkup() {
		$out = '<div id="mm_shift_select_wrapper" class="mm_hide"><input id="mm_previous_selected_media" type="hidden" value="0"></div>';
		return $out;
	}

	/**
	 * Render markup for linking to blueimp gallery.
	 *
	 * Use for previewing all media.
	 *
	 * @access private
	 * @param object $media The media to build preview link for.
	 * @return string $out The markup to link to blueimp gallery.
	 *
	 */
	private function renderGalleryPreview($media) {

		// @todo? might need in the future
		if($media->noPreview) return;

		$dataDimensions = '';
		$dataVariations = '';
		// @note: important for dynamism
		$blueimpGalleryContainerID = $this->blueimpGalleryContainerID;

		$description = $this->wire('sanitizer')->entities($media->description);

		if($media->mediaTypeStr == 'image') {
			$dataVariations = " data-variations='{$media->variationsCnt}'";
			$dataDimensions = " data-dimensions='{$media->dimensions}'";
		}

		$out =
			"<a " .
				"href='{$media->previewURL}'" .
				" class='mm_preview'" .
				" title='{$media->title}'" .
				// @note: no longer in use
				//" data-gallery='#{$blueimpGalleryContainerID}'" .// @note: using a single group for all media for now
				" data-description='{$description}'" .
				" data-tags='{$media->tags}'" .
				" data-media-type-str='{$media->mediaTypeStr}'" .
				"{$dataDimensions}" .
				" data-filename='{$media->basename}'" .
				" data-file-size='{$media->filesizeStr}'" .
				"{$dataVariations}" .
				" data-usedcount='{$media->usedCnt}'" .
				" type='{$media->mimeType}'" .
			">".
			"</a>";

		return $out;

	}

	/**
	 * Render markup for linking to image media variations modal.
	 *
	 * Used only for image media.
	 *
	 *
	 * @access private
	 * @param object $media The media to build preview link for.
	 * @return string $out The markup to link to blueimp gallery.
	 *
	 */
	private function renderExtraImagesLink($media) {

		if($media->noPreview) return;// @todo? might need in the future
		$backLinkSegment = "{$this->urlSeg1}-";
		$backLinkSegment .= $this->rteOrLinkMode ? $this->urlSeg2 : $this->urlSeg3;
		$variationsURL = $this->adminURL . "media-manager/variations/{$this->currentPageID}-{$this->mediaManagerFieldID}/$backLinkSegment/" .
		"?modal=1" .
		"&id={$media->page->id}" .
		"&rtelink={$this->rteOrLinkMode}";
		$out = "<a href='{$variationsURL}' class='mm_insert_extra_images'><i class='fa fa fa-files-o'></i></a>";

		return $out;

	}

	/**
	 * Function to create the Media Manager menu.
	 *
	 * @access protected
	 * @return string $out Markup of navigation menu.
	 *
	 */
	protected function renderMenu() {

		$out = '';
		$modal = '';
		$seg = '';

		$menuItems = $this->mmUtilities->getMenuItems();

		// check for current item in order to apply 'on' (active) css class to i
		$currentMenuItem = $this->mmUtilities->getCurrentMenuItem();

		// URL Param String: Add/Insert mode
 		if($this->urlSeg1 == 'add') {
			$seg = $this->urlSeg1 . '/' . $this->currentPage->id . '-' . $this->mediaManagerField->id . '/';
			$modal = '?modal=1';
		}
		// URL Param String: RTE or LINK mode
		elseif($this->urlSeg1 == 'link' || $this->urlSeg1 == 'rte') {
			$seg = $this->urlSeg1 . '/';
			$modal = '?modal=1';
		}

		$out .= "<ul class='nav uk-navbar-nav pw-primary-nav'>";

		/*
		NEED ABSOLUTE URLS TO DEAL WITH ISSUE OF TRAILING SLASH.
			 - http:// processwire.com/talk/topic/3777-post-empty-on-post-from-different-page/
		 */
		// Using absolute url: see URL segment + trailing slash issue

		foreach ($menuItems as $key => $value) {
			// determine 'active' menu item
			$on = $key == $currentMenuItem ? 'mm_menu_item mm_on uk-active' : 'mm_menu_item';
			// remove 'all' key for final $href but add '/' if required
			$seg2 = 'all' == $key ? '' : $key . '/';
			$href = $this->wire('page')->url . $seg . $seg2 .  $modal;
			$out .= "<li><a class='{$on}' href='{$href}'>{$value}</a></li>";
		}

		$out .= "</ul>";

		return $out;

	}

	/**
	 * Render paginated results.
	 *
	 * @access protected
	 * @param PageArray $results to paginate.
	 * @return array $paginatedResults
	 *
	 */
	protected function renderPagination($results) {

		$paginatedResults = array();

		$start 	= $results->getStart();
		$limit 	= $results->getLimit();
		$end 	= $start+$results->count();
		$total 	= $results->getTotal();

		if(count($results)) {
			$paginatedResults['headline'] = sprintf(__('%1$d to %2$d of %3$d'), $start+1, $end, $total);
			if($total > $limit) {
				$pager = $this->wire('modules')->get('MarkupPagerNav');
				// make sure url segments parameters work with page numbers in the url
				#Solution for pagination when using URL segments
				$url = $this->wire('page')->url . $this->wire('input')->urlSegmentsStr .'/';// get the url segment string.
				$pgOut = $results->renderPager(array('baseUrl' => $url));
				 // specifically identify page1, otherwise link doesn't work: @ryancramer
				$paginatedResults['pages'] = str_replace($url . "'", $url . "?pageNum=1'", $pgOut);
			}

		}

		return $paginatedResults;

	}

	/**
	 * Builds the actions input for the modal/add page.
	 *
	 * This is only used in page-edit mode (i.e. InputfieldMediaManager)
	 *
	 * @access private
	 * @return string $out Markup of actions panel for modal page.
	 *
	 */
	private function renderAddActionMarkup() {

		$out ='';

		if($this->mmUtilities->checkShowInsertButton()) {

			$f = $this->wire('modules')->get('InputfieldButton');
			$f->attr('id+name', 'mm_add_btn');
			$f->icon = 'plus-circle';
			$f->class .= ' mm_hide';
			$f->attr('value', $this->_('Insert Selected'));
			$f->attr('title', $this->_('Insert selected media to your page'));
			$f->attr('data-autoclose', 'close');

			$out .=
				'<span id="insert_into_media_manager_field_spinner">' .
					'<i class="fa fa-lg fa-spin fa-spinner"></i>'.
				'</span>' .
				$f->render();

		}

		return $out;

	}

	/**
	 * Builds the actions input for audio, document, image and video media.
	 *
	 * @access private
	 * @return string $out Markup of actions panel.
	 *
	 */
	private function renderActions() {

		$actions = $this->mmUtilities->getBulkActionItems();

		$out = '';

		if(count($actions)) {

			$modules = $this->wire('modules');

			// select dropdown
			$label = '<label id="mm_action_select_label" for="mm_action_select">' . $this->_("Action") . '</label>';
			$f = $modules->get('InputfieldSelect');
			$f->attr('name+id', 'mm_action_select');
			$f->addOptions($actions);
			$out .= $f->render() . $label;

			// button
			$f = $modules->get('InputfieldButton');
			$f->attr('id+name', 'mm_action_btn');
			$f->icon = 'check-square-o';
			$f->attr('data-media-action', 'save');
			$f->showInHeader();
			$f->class .= ' mm_hide';
			$f->attr('value', $this->_('Apply'));
			$out .=  $f->render();

		}

		return $out;

	}

	/**
	 * Render input markup for tags bulk actions.
	 *
	 * Includes input text and radio.
	 * Used in renderMediaViewsTopPanel().
	 *
	 * @return string $out Markup of tags inputs.
	 *
	 */
	private function renderTagsActionMarkup() {

		// text: tags input
		$tagsInput = $this->wire('modules')->get('InputfieldText');
		$tagsInput->attr('name+id', 'mm_action_tags');

		// radios: tags add mode (append or replace)
		$tagsAppendMode = $this->wire('modules')->get('InputfieldRadios');
		$tagsAppendMode->attr('name+id', 'mm_tag_mode');
		$tagsAppendMode->attr('value', 1);
		$radioOptions = array (
			1 => $this->_('Append'),
			2 => $this->_('Replace'),
		 );
		$tagsAppendMode->addOptions($radioOptions);

		$out =
			'<label for="mm_tags">'.
				$this->_('Specify tags to apply to selected media. Multiple tags should each be separated with a space. Also choose whether to append to or replace existing tags.') .
			'</label>' .
			$tagsInput->render() .
			$tagsAppendMode->render();

		return $out;

	}

	/**
	 * Renders ProcessWire button with dropdown using given options.
	 *
	 * @access private
	 * @param array $options Options to build the button.
	 * @return InputfieldSubmit $b An inputfield submit button to render.
	 *
	 */
	private function renderDropDownSubmit($options = array()) {

		if(!is_array($options) || !count($options)) return;

		$b = $this->wire('modules')->get('InputfieldSubmit');
		$b->attr('id', $options['id']);
		$b->attr('name', $options['name']);
		$b->value = $options['value'];

		foreach($options['add_action_values'] as $key => $value) {
			$b->addActionValue(
				$value['value'],
				$value['label'],
				$value['icon']
			);
		}

		return $b;

	}

	/**
	 * Builds the mini actions input for switching views and live-sorting media.
	 *
	 * @access private
	 * @return string $out Markup of mini actions panel.
	 *
	 */
	private function renderMiniActions() {

		$out = '';
		$modules = $this->wire('modules');
		$input = $this->wire('input');

		// the mini actions select options
		$liveSortActions = array(
			1 => $this->_('Title'),
			2 => $this->_('Tags'),
			3 => $this->_('Modified'),
			4 => $this->_('Created'),
			5 => $this->_('Published'),
			6 => $this->_('Description'),
		);

		$out .=  "<div id='media_view_switcher_sort' class='{$this->actionsWrapperClass}'>";

		# thumbs view switchers
		// only show view switchers if custom columns available to show in thumbs view: tabular
		if($this->totalCustomColumns) {
			$out .=
				## tabular switcher
				$this->renderThumbsViewTabularSwitcher() .
				## grid switcher
				$this->renderThumbsViewGridSwitcher();
		}

		// get live sort value from cookie if any
		$sort = $input->cookie->media_live_sort ? $input->cookie->media_live_sort : '';

		// live sort select
		$f = $modules->get('InputfieldSelect');
		//$f->label = $this->_('Live Sort');// @note: doesn't work
		$f->attr('name+id', 'mm_live_sort_action_select');
		$f->addOptions($liveSortActions);
		$f->attr('value', $sort);
		$sortLabel = '<label id="media_live_sort_label" for="mm_live_sort_action_select">' . $this->_("Sort by") . '</label>';
		$out .= $sortLabel . $f->render();

		// live sort order checkbox
		$hide = $sort ? '' : ' class="mm_hide"';
		$checked = (int) $input->cookie->media_live_sort_order == 2 ? true : false;
		$f = $modules->get('InputfieldCheckbox');
		$f->attr('name+id', 'media_live_sort_order');
		$f->attr('value', 0);
		$f->attr('checked', $checked ? 'checked' : '');
		$f->label2 = $this->_("Descending");

		$out .= "<div id='media_live_sort_order_wrapper'{$hide}>" .	$f->render() . '</div>';
		// close div#media_view_switcher_sort
		$out .= '</div>';

		return $out;

	}

	/**
	 * Renders a form for the creation and editing of filter profiles.
	 *
	 * @access protected
	 * @return string $form Rendered Form for editing filter profiles.
	 *
	 */
	protected function renderFilterConfig() {

		$modules = $this->wire('modules');

		// create a form for saving fitlers input
		$form = $modules->get('InputfieldForm');
		$form->attr('id', 'mm_filter_config');
		$form->action = './';
		$form->method = 'post';

		// table listing saved filter profiles
		$form->add($this->renderFilterProfiles());

		// if saving
		$post = $this->wire('input')->post;
		if($post->mm_filter_bulk_btns || $post->mm_filter_settings_save_btn || $post->mm_filter_settings_save_exit_btn){
			$this->mmActions = new MediaManagerActions();
			// various edit actions: set active; create new; save (non-deleted) profiles (in profiles table)
			$actionType = 'edit-filters';
			$this->mmActions->actionMedia($actionType, $form);
		}

		return $form->render();

	}

	/**
	 * Builds the table that lists saved filter profiles.
	 *
	 * @access private
	 * @return InputfieldWrapper $wrapper InputfieldWrapper to add to final filter profiles form.
	 *
	 */
	private function renderFilterProfiles() {

		$profilesListMarkup = $this->renderFilterProfilesTable();

        $wrapper = new InputfieldWrapper();
        $id = $this->className() . 'FilterProfiles';
		$wrapper->attr('id', $id);

		$modules = $this->wire('modules');

		// profiles found
		if($profilesListMarkup) {

			$dropdownButtonOptions = array(
				// main button: Set Active Filter
				'id' => 'mm_filter_bulk_btns',
				'name' => 'mm_filter_bulk_btns',
				'value' => $this->_('Set Active Filter'),
				'add_action_values' => array(
					// deactivate filter dropdown
					0 => array(
						'value' => 'deactivate',
						'label' => $this->_('Deactivate Active Filter'),
						'icon' => 'times'
					),
					// lock filter dropdown
					1 => array(
						'value' => 'lock',
						'label' => $this->_('Lock'),
						'icon' => 'lock'
					),
					// unlock filter dropdown
					2 => array(
						'value' => 'unlock',
						'label' => $this->_('Unlock'),
						'icon' => 'unlock'
					),
					// delete filter dropdown
					3 => array(
						'value' => 'delete',
						'label' => $this->_('Delete'),
						'icon' => 'trash'
					)
				)
			);

			$b = $this->renderDropDownSubmit($dropdownButtonOptions);

			$saveButtons = $b->render();
			$notes = "<p class='notes'>".$this->_("Click on a filter's title to edit it. Please note that locked filters need to be unlocked first before they can be edited or deleted. Only one filter can be set as active at any one time."). "</p>";

		}
		// no profiles created yet
		else {
			$page = $this->wire('page');
			$createNewFilterText = $this->_('create');
			$createNewFilter = "<a href='{$page->url}filters/new/?modal=1' class='mm_no_filters pw-modal pw-modal-medium'>{$createNewFilterText}</a>";
			$profilesListMarkup = sprintf(__('No filter profiles found. Please %s one.'), $createNewFilter);
			$notes = '';
			$saveButtons = '';
		}

        $m = $modules->get('InputfieldMarkup');
		$m->label = $this->_('Filter Profiles');
		$m->attr('value', $saveButtons .  $notes .$profilesListMarkup);

        $wrapper->add($m);
		return $wrapper;

	}

	/**
	 * Builds the table that lists saved filter profiles.
	 *
	 * The table is also used to: Set active filters, lock and delete filters.
	 *
	 * @access private
	 * @return InputfieldWrapper $wrapper InputfieldWrapper to add to final filter profiles form.
	 *
	 */
	private function renderFilterProfilesTable() {

		$out ='';
		$page = $this->wire('page');

		$createNewFilterText = $this->_('Create filter');
		$createNewFilter = "<a href='{$page->url}filters/new/?modal=1' class='ui-helper-clearfix mm_create_filter pw-modal pw-modal-medium'><i class='fa fa-fw fa-plus-circle'></i>{$createNewFilterText}</a>";

		$savedSettings = $this->savedSettings;
		$savedFilterProfiles = isset($savedSettings['filters']) ? $savedSettings['filters'] : array();
		$activeFilter = isset($savedSettings['active_filter']) ? $savedSettings['active_filter'] : '';

		if(count($savedFilterProfiles)) {

			$active = $this->_('active filter');
			$tbody = '';
			foreach ($savedFilterProfiles as $name => $value) {

				$filterTitle = $value['title'];
				// no title found, skip
				if(!$filterTitle) continue;

				$filterName = $this->wire('sanitizer')->pageName($name);
				$filterDefaultSelector = $value['defaultSelector'];
				// active filter match
				if($filterName == $activeFilter) {
					$filterActive = "<small id='mm_active_filter'>" .$active . "</small>";
					$filterActiveClass = " mm_active_filter";
				}
				else {
					$filterActive = "";
					$filterActiveClass = "";
				}
				// filter locked for edits and deletion
				if(isset($value['locked']) && $value['locked']) {
					$filterLocked = "<span class='fa fa-lock mm_locked'></span>";
					$filterEdit = $filterTitle .'<br>'. $filterActive;
				}
				// unlocked filter
				else {
					$filterLocked = '';
					$filterEdit = "<a href='{$page->url}filters/{$filterName}/?modal=1' class='ui-helper-clearfix mm_edit_filter pw-modal pw-modal-medium'>{$filterTitle}</a>{$filterActive}";
				}

				$tbody .= "
					<tr class='mm_filter_profile{$filterActiveClass}'>" .
						// title
						"<td>{$filterEdit}</td>" .
						// defaultSelector
						"<td><code>{$filterDefaultSelector}</code></td>" .
						// locked profile
						"<td>{$filterLocked}</td>" .
						// select profile
						"<td data-profile='{$filterName}'><input type='checkbox' name='mm_filter_title[]' value='{$filterTitle}' class='mm_filter_profile_sel mm_table_item toggle uk-checkbox uk-form-controls-text'></td>" .
					"</tr>
				";

			}// end foreach

			$out =
			"\n\t<div class='uk-overflow-auto uk-width-1-1'>".
				$createNewFilter .
				"<table id='mm_filter_profiles_list' role='presentation' class='uk-table uk-table-divider uk-table-hover uk-table-justify uk-table uk-table-middle uk-table-responsive'>".
					"<thead>" .
						"<tr>" .
							"<th class='uk-width-medium'>" . $this->_('Title') . "</th>" .
							"<th class='uk-table-expand'>" . $this->_('Default Selector') . "</th>" .
							"<th class='uk-width-small'>" . $this->_('Locked') . "</th>" .
							"<th class='uk-width-small'>" .
								"<input type='checkbox' class='toggle_all uk-checkbox uk-form-controls-text' title='Select All'>".
							"</th>" .
						"</tr>" .
					"</thead>" .
					"\n\t\t<tbody>{$tbody}</tbody>\n".
            "\t</table></div>\n";
		}// end if count($savedFilterProfiles)

		return $out;

	}

	/**
	 * Renders a form for editing a single filter profile.
	 *
	 * @access protected
	 * @return string $form Rendered Form for editing single filter profile.
	 *
	 */
	protected function renderFilterConfigEdit() {

		$modules = $this->wire('modules');
		$filterName =  $this->urlSeg2;
		$savedSettings = $this->savedSettings;

		$filterTitle = isset($savedSettings['filters'][$filterName]) ? $savedSettings['filters'][$filterName]['title'] : '';
		$defaultSelector = isset($savedSettings['filters'][$filterName]['defaultSelector']) ? $savedSettings['filters'][$filterName]['defaultSelector'] : "title%=";

		// create form for filter edit
		$form = $modules->get('InputfieldForm');
		$form->attr('id', 'mm_edit_filter_config');
		$form->action = './';
		$form->method = 'post';
		$form->description = $filterTitle ? '' : $this->_('New Filter');

		// filter: old/current name (hidden input) @note: helps to determine if filter title changing + checks for duplication
		$f = $modules->get('InputfieldHidden');
		$f->attr('name', 'mm_edit_filter_name');
		$f->attr('value', $filterName);

		$form->add($f);

		// filter: title (text)
		$f = $modules->get('InputfieldText');
		$f->label = $this->_('Title');
		$f->required = true;
		$f->attr('name', 'mm_edit_filter_title');
		$f->attr('value', $filterTitle);
		$f->description = $this->_('A title is required.');

		$form->add($f);

		// filter: inputfield selector
		$f = $modules->get('InputfieldSelector');
		$f->attr('name', 'defaultSelector');
		$f->attr('value', $defaultSelector);
		$f->label = $this->_('Configure Filters');
		$f->description = $this->_('The filters you select here will be visible when a user first views Media Manager Library when this filter profile is active. These are the default filters only, as the user can optionally add, change or remove them. We recommend that you select the fields to be used as filters, but leave the values in each row blank/unselected (unless you wish to provide a default value).');
		$f->addLabel = $this->_('Add Filter');
		$f->allowBlankValues = true;
		$f->parseVars = false;
		$f->counter = false;
		$f->icon = 'search-plus';
		$form->add($f);

		// filter: submit button
		$f = $modules->get('InputfieldButton');
		$f->attr('id+name', 'mm_edit_filter_config_btn');
		$f->value = $this->_('Save');
		$f->attr('type', 'submit');

		$form->add($f);

		// single fitler edit
		$post = $this->wire('input')->post;
		if($post->mm_edit_filter_config_btn){
			$this->mmActions = new MediaManagerActions();
			$actionType = 'edit-filter';
			$this->mmActions->actionMedia($actionType, $form);
		}

		return $form->render();

	}

	/**
	 * Builds the form used to save Media Manager settings.
	 *
	 * @access protected
	 * @return string $form Markup of rendered form.
	 *
	 */
	protected function renderSettings() {

		// create a multipart form for media uploads
		$form = $this->wire('modules')->get('InputfieldForm');
		$form->attr('id', 'settings');
		$form->action = './';
		$form->method = 'post';

		$form->add($this->uploadSettings());
		$form->add($this->otherSettings());
		$form->add($this->customColumnsSettings());

		$post = $this->wire('input')->post;
		// settings
		if($post->mm_settings_btn) {
			$this->mmActions = new MediaManagerActions();
			$actionType = 'settings';
			$this->mmActions->actionMedia($actionType, $form);
		}

		return $form->render();

	}

	/**
	 * Render settings form.
	 *
	 * @access private
	 * @return InputfieldWrapper $wrapper InputfieldWrapper to add to form.
	 *
	 */
	private function uploadSettings() {

		/* ## UPLOAD SETTINGS ## */

		$modules = $this->wire('modules');

		$validationSettings = $this->uploadSettingsValidation();
		$modeSettings = $this->uploadSettingsMode();
		$audioSettings = $this->uploadSettingsAudio();
		$documentSettings = $this->uploadSettingsDocument();
		$imageSettings = $this->uploadSettingsImage();
		$videoSettings = $this->uploadSettingsVideo();

		$savedSettings = $this->savedSettings;

		// new inputfieldwrapper
		$wrapper = new InputfieldWrapper();
		$wrapper->attr('title', $this->_('Settings'));
		$id = $this->className() . 'Settings';
		$wrapper->attr('id', $id);

		/* ## mainly jquery file upload settings ## */

		$fieldset = $modules->get('InputfieldFieldset');
		$fieldset->attr('id', 'mm_upload_settings');
		$fieldset->label = $this->_('Upload Settings');
		$fieldset->description = $this->_('These settings apply to both \'Added\' and \'Scanned\' media.');

		/* 1. Upload Settings: intro */
		$settingsDesc =
			'<ol>' .
				'<li>' .
					$this->_('Use the form below to specify any custom upload settings. NOTE: If you are happy with the defaults, you do not have to enter any values') .
				'</li>' .
				'<li>' .
					$this->_('Apart from the validation settings, the settings you enter here only apply to media uploaded using the ') .
					'<span class="mm_settings">' . $this->_('Add') . '</span>' .
					$this->_(' wrapper, rather than the ') .
					'<span class="mm_settings">' . $this->_('Scan ') . '</span>' .
					$this->_('wrapper.') .
				'</li>' .
			'</ol>';

		$m = $modules->get('InputfieldMarkup');
		$m->label = $this->_('Media Upload Settings');
		$m->attr('value', '<div>' . $settingsDesc . '</div>');

		$fieldset->add($m);

		/*2. Upload Settings: after upload setting */
		$afterUploadSetting = isset($savedSettings['after'][0]) ? $savedSettings['after'][0] : 2;

		$f = $modules->get('InputfieldRadios');
		$f->attr('name', 'mm_settings_after_upload');
		$f->attr('value', $afterUploadSetting ? $afterUploadSetting : 2);
		$f->label =  $this->_('After Uploading');
		$f->collapsed = Inputfield::collapsedYes;
		$f->notes = $this->_('If you select option 1 or 2 you will not be able to review your uploads once uploading is finished. In that case, please find your newly uploaded media in the Media Library. Option 3 allows you to review and even delete (if you have the right access permissions) media after they get uploaded to a temporary folder. You will then be able to select the uploads that you want to add to your Media Library. In both options 1 and 3, your media may not be published if a publishing permission is in effect and you do not have that permission.');

		$radioOptions = array (
			1 => $this->_('Add uploads to media library and publish them'),
			2 => $this->_('Add uploads to media library but do not publish them'),
			3 => $this->_('Do not add uploads to media library. Keep them in a temporary folder until they have been reviewed'),
	 	);

	 	$f->addOptions($radioOptions);

	 	$fieldset->add($f);

		/* 3. Upload Settings: validation */
		$m = $modules->get('InputfieldMarkup');
		$m->attr('value', $validationSettings);
		$m->label = $this->_('Validation');
		$m->collapsed = Inputfield::collapsedYes;
		$fieldset->add($m);

		/* 4. Upload Settings: mode */
		$m = $modules->get('InputfieldMarkup');
		$m->attr('value', $modeSettings);
		$m->label = $this->_('Upload Mode');
		$m->collapsed = Inputfield::collapsedYes;
		$fieldset->add($m);

		// @note: for the 4 media types, we show their settings in media type allowed in global settings

		/* 5. Upload Settings: audio */
		if(in_array('audio', $this->globalAllowedMedia)) {
			$m = $modules->get('InputfieldMarkup');
			$m->attr('value', $audioSettings);
			$m->label = $this->_('Audio');
			$m->collapsed = Inputfield::collapsedYes;
			$fieldset->add($m);
		}

		/* 6. Upload Settings: document */
		if(in_array('document', $this->globalAllowedMedia)) {
			$m = $modules->get('InputfieldMarkup');
			$m->attr('value', $documentSettings);
			$m->label = $this->_('Document');
			$m->collapsed = Inputfield::collapsedYes;
			$fieldset->add($m);
		}

		/* 7. Upload Settings: video */
		if(in_array('video', $this->globalAllowedMedia)) {
			$m = $modules->get('InputfieldMarkup');
			$m->attr('value', $videoSettings);
			$m->label = $this->_('Video');
			$m->collapsed = Inputfield::collapsedYes;
			$fieldset->add($m);
		}

		/* 8. Upload Settings: image */
		if(in_array('image', $this->globalAllowedMedia)) {
			$m = $modules->get('InputfieldMarkup');
			$m->attr('value', $imageSettings);
			$m->label = $this->_('Image');
			$m->collapsed = Inputfield::collapsedYes;
			$fieldset->add($m);
		}

		/*9. Upload Settings: media title format */
		$titleFormat = isset($savedSettings['title_format'][0]) ? $savedSettings['title_format'][0] : 1;

		$f = $modules->get('InputfieldRadios');
		$f->attr('name', 'mm_settings_title_format');
		$f->attr('value', $titleFormat ? $titleFormat : 1);
		$f->label =  $this->_('Media Title Format');
		$f->collapsed = Inputfield::collapsedYes;
		$notes = $this->_("Media titles are generated according to processing rules applied to the name of the uploaded file.\n");
		$notes .= $this->_("For instance, if you upload a file named 'My Photo.jpg', the required processed filename will be 'my_photo.jpg'. If 'My-Photo.jpg', the processed filename will be 'my-photo.jpg'. However, your media titles need not follow such rules.\n");
		$notes .= $this->_("Here specify how your media titles should be generated.");
		$f->notes = $notes;

		$radioOptions = array (
			1 => $this->_('First letter of each word uppercase, no underscores or hyphens'),
			2 => $this->_('First letter of first word uppercase, no underscores or hyphens'),
			3 => $this->_('Exact as processed filename, first letter of each word uppercase'),
			4 => $this->_('Exact as processed filename, first letter of first word uppercase'),
			#5 => $this->_('Exact as processed filename, all lowercase'),
	 	);

	 	$f->addOptions($radioOptions);

	 	$fieldset->add($f);

		/*10 Upload Settings: duplicate media */
		$duplicateMedia = isset($savedSettings['duplicates'][0]) ? $savedSettings['duplicates'][0] : 1;

		$f = $modules->get('InputfieldRadios');
		$f->attr('name', 'mm_settings_duplicates');
		$f->attr('value', $duplicateMedia ? $duplicateMedia : 1);
		$f->label =  $this->_('Duplicate Media');
		$f->collapsed = Inputfield::collapsedYes;
		$f->description = $this->_('Please specify what action to take if Media Manager detects a duplicate media is being uploaded');
		$f->notes = $this->_('A duplicate media is a file with the exact file name as one already in the Media Library.');

		$radioOptions = array (
			1 => $this->_('Skip the media being uploaded'),
			2 => $this->_('Rename and add the uploaded media to the Media Library'),
			3 => $this->_('Replace the existing media with the one being uploaded'),

	 	);

	 	$f->addOptions($radioOptions);

	 	$fieldset->add($f);

		/*11 Upload Settings: delete variations (handling duplicates) */
		$f = $modules->get('InputfieldCheckbox');
		$f->attr('name', 'mm_settings_replace_delete_variations');
		$f->attr('value', 1);
		$deleteVariations = isset($savedSettings['delete_variations'][0]) ? $savedSettings['delete_variations'][0] : 0;
		$f->attr('checked', $deleteVariations ? 'checked' : '');
		$f->label = $this->_('Replace existing media and delete all its variations');
		$f->label2 = $this->_('Delete variations');
		$f->notes = $this->_('Only applies to image media if above you indicated that you will be replacing media in case duplicate was found.');
		#$f->collapsed = Inputfield::collapsedYes;
		$f->showIf = 'mm_settings_duplicates=3';
		$f->requiredIf = 'mm_settings_duplicates=3';

		$fieldset->add($f);

		/*12 Upload Settings: show confirm overwrite checkbox on uploads interface when overwrite is set to true (handling duplicates option 3) */
		$f = $modules->get('InputfieldCheckbox');
		$f->attr('name', 'mm_settings_duplicate_replace_confirm');
		$f->attr('value', 1);
		$duplicateReplaceConfirm = isset($savedSettings['duplicates_replace_confirm'][0]) ? $savedSettings['duplicates_replace_confirm'][0] : 0;
		$f->attr('checked', $duplicateReplaceConfirm ? 'checked' : '');
		$f->label = $this->_('Always confirm when replacing/overwriting duplicate media');
		$f->label2 = $this->_('Show confirm media replace checkbox');
		$f->notes = $this->_('Only applies if above you indicated that you will be replacing media in case duplicate was found. If this setting is checked, users will only be able to upload media by ticking a checkbox confirming they wish to overwrite duplicate media. This serves as a reminder in case users forget that the setting allowing overwriting duplicates is in effect.');
		#$f->collapsed = Inputfield::collapsedYes;
		$f->showIf = 'mm_settings_duplicates=3';
		$f->requiredIf = 'mm_settings_duplicates=3';

		$fieldset->add($f);

		$wrapper->add($fieldset);

		return $wrapper;

	}

	/**
	 * Upload settings for validation.
	 *
	 * Some settings shared between WireUpload and jQuery File Upload.
	 *
	 * @access private
	 * @return string $table Rendered markup of table.
	 *
	 */
	private function uploadSettingsValidation() {

		$savedSettings = $this->savedSettings;

		$settings = array();

		// translatable notes for each option
		$sofNote = $this->_('Set the filename that may be overwritten (i.e. myphoto.jpg) for single uploads only.');
		$smfNote = $this->_('Maximum allowed number of uploaded files.');
		$mfsNote = $this->_('Minimum allowed uploaded file size (bytes).');
		$smfsNote = $this->_('Maximum allowed uploaded file size (bytes).');
		$soNote = $this->_('Whether or not overwrite is allowed.');
		$slcNote = $this->_('Whether or not lowercase file naming is enforced.');

		// @note: some settings (max files, etc), shared with jfu

		// name, type, default, saved setting, notes
		$settings['validation'] = array(
			'setOverwriteFilename' 	=> array($this->_('Overwrite filename'), 'string', 'false', '', $sofNote),
			'minFileSize'			=> array($this->_('Minimum file size'), 'integer', '', '', $mfsNote),
			'setMaxFileSize' 		=> array($this->_('Maximum file size'), 'integer', '', '', $smfsNote),// @jfu maxFileSize
			'setMaxFiles' 			=> array($this->_('Maximum files'), 'integer', '', '', $smfNote),// @jfu maxNumberOfFiles
			'setOverwrite' 			=> array($this->_('Overwrite'), 'boolean', 'false', '', $soNote),
			'setLowercase' 			=> array($this->_('Lowercase'), 'boolean', 'true', '', $slcNote),
		);

		$validationSettings = isset($savedSettings['validation']) ? $savedSettings['validation'] : array();

		// merge saved settings to option value
		foreach ($validationSettings as $key => $value) {
			$settings['validation'][$key][3] = $value;
		}

		$table = $this->buildOptionsTable('validation', $settings['validation']);

		return $table;

	}

	/**
	 * Upload mode settings.
	 *
	 * @access private
	 * @return string $table Rendered markup of table.
	 *
	 */
	private function uploadSettingsMode() {

		$savedSettings = $this->savedSettings;

		$settings = array();

		// translatable notes for each option
		$sfuNote = $this->_('By default, each file of a selection is uploaded using an individual request for Ajax type uploads. Set this option to false to upload file selections in one request each.');
		$lmfuNote = $this->_('To limit the number of files uploaded with one Ajax request, set an integer greater than 0. This option is ignored, if \'Single file uploads\' is set to true or \'Limit multi-file upload size\' is set and the browser reports file sizes.');
		$lmfusNote = $this->_('Limits the number of files uploaded with one Ajax request to keep the request size under or equal to the defined limit in bytes.');
		$suNote = $this->_('If seet to true, all file upload requests are issued in a sequential order instead of simultaneous requests.');
		$lcuNote = $this->_('To limit the number of concurrent uploads, set this option to an integer value greater than 0. This option is ignored, if \'Sequential uploads\' is set to true.');
		$auNote = $this->_('By default, files added to the widget are uploaded when the user clicks on the start buttons. To enable automatic uploads, set this option to true.');
		$pfNote = $this->_('By default, files are appended to the files widget. Set this option to true, to instead prepend files.');

		// name, type, default, saved setting, notes
		$settings['mode'] = array(
			'singleFileUploads' 		=> array($this->_('Single file uploads'), 'boolean', 'true', '', $sfuNote),
			'limitMultiFileUploads'		=> array($this->_('Limit multi-file uploads'), 'integer', '', '', $lmfuNote),
			'limitMultiFileUploadSize'	=> array($this->_('Limit multi-file upload size'), 'integer', '', '', $lmfusNote),
			'sequentialUploads' 		=> array($this->_('Sequential uploads'), 'boolean', 'false', '', $suNote),
			'limitConcurrentUploads' 	=> array($this->_('Limit concurrent uploads'), 'integer', '', '', $lcuNote),
			'autoUpload' 				=> array($this->_('Auto upload'), 'boolean', 'false', '', $auNote),
			'prependFiles' 				=> array($this->_('Prepend files'), 'boolean', 'false', '', $pfNote),
		);

		$modeSettings = isset($savedSettings['mode']) ? $savedSettings['mode'] : array();

		// merge saved settings to option value
		foreach ($modeSettings as $key => $value) $settings['mode'][$key][3] = $value;

		$table = $this->buildOptionsTable('mode', $settings['mode']);

		return $table;

	}

	/**
	 * Upload image settings.
	 *
	 * @access private
	 * @return string $table Rendered markup of table.
	 *
	 */
	private function uploadSettingsImage() {

		$savedSettings = $this->savedSettings;

		$settings = array();

		// translatable notes for each option
		$vfeNote = $this->_('Valid image file extensions (space separated).');
		$dihNote = $this->_('Disable parsing and storing the image header.');
		$deNote = $this->_('Disable parsing Exif data.');
		$detNote = $this->_('Disable parsing the Exif Thumbnail.');
		$desNote = $this->_('Disable parsing the Exif Sub IFD (additional Exif info).');
		$defgNote = $this->_('Disable parsing Exif GPS data.');
		$dimdlNote = $this->_('Disable parsing image meta-data (image head and Exif data).');
		$dimdsNote = $this->_('Disable saving image meta-data into the resized images.');
		$dilNote = $this->_('Disable loading and therefore processing of images.');
		$dirNote = $this->_('Disable the resize image functionality.');
		$dipNote = $this->_('Disable image previews.');

		$limfsNote = $this->_('Maximum file size of images to load (bytes).');

		$imwNote = $this->_('Minimum width of resized images (pixels).');
		$imhNote = $this->_('Minimum height of resized images (pixels).');
		$imxwNote = $this->_('Maximum width of resized images (pixels).');
		$imxhNote = $this->_('Maximum height of resized images (pixels).');

		$icNote = $this->_('If resized images should be cropped or only scaled.');
		$ioNote = $this->_('Defines the image orientation (1-8) or takes the orientation value from Exif data if set to true.');
		$ifrNote = $this->_('If set to true, forces writing to and saving images from canvas, even if the original image fits the maximum image constraints.');
		$iqNote = $this->_('Sets the quality when saving resized images.');

		$pmwNote = $this->_('Minimum width of preview images (pixels).');
		$pmhNote = $this->_('Minimum height of preview images (pixels).');
		$pmxwNote = $this->_('Maximum width of the preview images (pixels).');
		$pmxhNote = $this->_('Maximum height of the preview images (pixels).');
		$pcNote = $this->_('If preview images should be cropped or only scaled.');
		$poNote = $this->_('Preview orientation (1-8) or takes the orientation value from Exif data if set to true.');
		$ptNote = $this->_('Create the preview using the Exif data thumbnail.');

		// valid image file extensions set in 'media_manager_image'
		// @note: no need to save these twice; so we don't save them to media_manager_settings
		// we always refer (retrieving and saving) to what is in the field settings
		$vfe = $this->wire('fields')->get('media_manager_image')->extensions;
		$imxw = $this->wire('fields')->get('media_manager_image')->maxWidth;
		$imxh = $this->wire('fields')->get('media_manager_image')->maxHeight;

		// name, type, default, saved setting, notes
		$settings['image'] = array(
			'validExtensionsImage' => array($this->_('Valid image file extensions'), 'string', '', $vfe, $vfeNote),
			'disableImageHead' 		=> array($this->_('Disable image header'), 'boolean', 'false', '', $dihNote),
			'disableExif'			=> array($this->_('Disable Exif'), 'boolean', 'false', '', $deNote),
			'disableExifThumbnail'	=> array($this->_('Disable Exif thumbnail'), 'boolean', 'false', '', $detNote),
			'disableExifSub' 		=> array($this->_('Disable Exif sub'), 'boolean', 'false', '', $desNote),
			'disableExifGps' 		=> array($this->_('Disable Exif GPS'), 'boolean', 'false', '', $defgNote),
			'disableImageMetaDataLoad' 	=> array($this->_('Disable image meta-data load'), 'boolean', 'false', '', $dimdlNote),
			'disableImageMetaDataSave' 	=> array($this->_('Disable image meta-data save'), 'boolean', 'false', '', $dimdsNote),
			'disableImageLoad' 		=> array($this->_('Disable image load'), 'boolean', 'false', '', $dilNote),
			'disableImageResize' 	=> array($this->_('Disable image resize'), 'boolean', 'true', '', $dirNote),
			'disableImagePreview' 	=> array($this->_('Disable image preview'), 'boolean', 'false', '', $dipNote),

			'loadImageMaxFileSize' 	=> array($this->_('Load image maximum file size'), 'integer', 10000000, '', $limfsNote),

			'imageMinWidth' 	=> array($this->_('Image minimum width'), 'integer', '', '', $imwNote),
			'imageMinHeight' 	=> array($this->_('Image minimum height'), 'integer', '', '', $imhNote),
			'imageMaxWidth'		=> array($this->_('Image maximum width'), 'integer', 5000000, $imxw, $imxwNote),
			'imageMaxHeight'	=> array($this->_('Image maximum height'), 'integer', 5000000, $imxh, $imxhNote),

			'imageCrop' 		=> array($this->_('Image crop'), 'boolean', 'false', '', $icNote),
			'imageOrientation'	=> array($this->_('Image orientation'), 'integer/boolean', 'false', '', $ioNote),
			'imageForceResize'	=> array($this->_('Image force resize'), 'integer/boolean', '', '', $ifrNote),
			'imageQuality' 		=> array($this->_('Image quality'), 'float', '', '', $iqNote),

			'previewMinWidth' 	=> array($this->_('Preview minimum width'), 'integer', '', '', $pmwNote),
			'previewMinHeight' 	=> array($this->_('Preview minimum height'), 'integer', '', '', $pmhNote),
			'previewMaxWidth' 	=> array($this->_('Preview maximum width'), 'integer', 80, '', $pmxwNote),
			'previewMaxHeight' 	=> array($this->_('Preview maximum height'), 'integer', 80, '', $pmxhNote),
			'previewCrop'		=> array($this->_('Preview crop'), 'boolean', 'false', '', $pcNote),
			'previewOrientation'=> array($this->_('Preview Orientation'), 'integer/boolean', 'true', '', $poNote),
			'previewThumbnail' 	=> array($this->_('Preview thumbnail'), 'boolean', 'true', '', $ptNote),
		);

		$imageSettings = isset($savedSettings['image']) ? $savedSettings['image'] : array();

		// merge saved settings to option value
		foreach ($imageSettings as $key => $value) $settings['image'][$key][3] = $value;

		$table = $this->buildOptionsTable('image', $settings['image']);

		return $table;

	}

	/**
	 * Upload audio settings.
	 *
	 * @access private
	 * @return string $table Rendered markup of table.
	 *
	 */
	private function uploadSettingsAudio() {

		$savedSettings = $this->savedSettings;

		$settings = array();

		// translatable notes for each option
		$vfeNote = $this->_('Valid audio file extensions (space separated).');
		$lamfsNote = $this->_('The maximum file size of audio files to load.');
		$dapNote = $this->_('Disable audio previews.');

		// valid audio file extensions set in 'media_manager_audio'
		// @note: no need to save these twice; so we don't save them to media_manager_settings
		// we always refer (retrieving and saving) to what is in the field settings
		$vfe = $this->wire('fields')->get('media_manager_audio')->extensions;

		// name, type, default, saved setting, notes
		$settings['audio'] = array(
			'validExtensionsAudio' => array($this->_('Valid audio file extensions'), 'string', '', $vfe, $vfeNote),
			'loadAudioMaxFileSize' 	=> array($this->_('Load audio maximum file size'), 'integer', '', '', $lamfsNote),
			'disableAudioPreview'	=> array($this->_('Disable audio preview'), 'boolean', 'false', '', $dapNote),
		);

		$audioSettings = isset($savedSettings['audio']) ? $savedSettings['audio'] : array();

		// merge saved settings to option value
		foreach ($audioSettings as $key => $value)$settings['audio'][$key][3] = $value;

		$table = $this->buildOptionsTable('audio', $settings['audio']);

		return $table;

	}

	/**
	 * Upload video settings.
	 *
	 * @access private
	 * @return string $table Rendered markup of table
	 *
	 */
	private function uploadSettingsVideo() {

		$savedSettings = $this->savedSettings;

		$settings = array();

		// translatable notes for each option
		$vfeNote = $this->_('Valid video file extensions (space separated).');
		$lvmfsNote = $this->_('The maximum file size of video files to load.');
		$dvpNote = $this->_('Disable video previews.');

		// valid video file extensions set in 'media_manager_video'
		// @note: no need to save these twice; so we don't save them to media_manager_settings
		// we always refer (retrieving and saving) to what is in the field settings
		$vfe = $this->wire('fields')->get('media_manager_video')->extensions;

		// name, type, default, saved setting, notes
		$settings['video'] = array(
			'validExtensionsVideo' => array($this->_('Valid video file extensions'), 'string', '', $vfe, $vfeNote),
			'loadVideoMaxFileSize' 	=> array($this->_('Load video maximum file size'), 'integer', '', '', $lvmfsNote),
			'disableVideoPreview'	=> array($this->_('Disable video preview'), 'boolean', 'false', '', $dvpNote),
		);

		$videoSettings = isset($savedSettings['video']) ? $savedSettings['video'] : array();

		// merge saved settings to option value
		foreach ($videoSettings as $key => $value)$settings['video'][$key][3] = $value;

		$table = $this->buildOptionsTable('video', $settings['video']);

		return $table;

	}

	/**
	 * Upload document settings.
	 *
	 * @access private
	 * @return string $table Rendered markup of table
	 *
	 */
	private function uploadSettingsDocument() {

		$savedSettings = $this->savedSettings;

		$settings = array();

		// translatable notes for each option
		$vfeNote = $this->_('Valid document file extensions (space separated).');

		// valid document file extensions set in 'media_manager_document'
		// @note: no need to save these twice; so we don't save them to media_manager_settings
		// we always refer (retrieving and saving) to what is in the field settings
		$vfe = $this->wire('fields')->get('media_manager_document')->extensions;

		// name, type, default, saved setting, notes
		$settings['document'] = array(
			'validExtensionsDocument' => array($this->_('Valid document file extensions'), 'string', '', $vfe, $vfeNote),
		);

		$videoSettings = isset($savedSettings['document']) ? $savedSettings['document'] : array();

		// merge saved settings to option value
		foreach ($videoSettings as $key => $value) $settings['document'][$key][3] = $value;

		$table = $this->buildOptionsTable('document', $settings['document']);

		return $table;

	}

	/**
	 * Builds a table of settings options.
	 *
	 * @access private
	 * @param string $type options type/group (upload- : validation, mode, image, audio, video, document).
	 * @param array $options options to create table rows and cells.
	 * @return string $t->render() Rendered markup of table.
	 *
	 */
	private function buildOptionsTable($type, Array $options) {

		$t = $this->wire('modules')->get('MarkupAdminDataTable');
		$t->setEncodeEntities(false);
		$t->setSortable(false);
		$t->setClass('mm_settings');

		$t->headerRow(array(
			$this->_('Name'),
			$this->_('Type'),
			$this->_('Default'),
			$this->_('Setting'),
			$this->_('Notes'),
		));

		// @access-control
		$readOnly = $this->noSettings ? ' readonly="readonly"'  : '';

		foreach ($options as $key => $value) {
			$t->row(array(
				$value[0],// name
				$value[1],// type
				$value[2],// default
				// saved setting - saved in media_manager_settings as JSON
				"<input type='text' class='mm_settings' name='mm_settings_" . $type . "[" . $key . "]' value='" . $value[3] . "'" . $readOnly  . ">",
				$value[4],// notes

			));
		}

		return $t->render();

	}

	/**
	 * Contents of 'Other Settings' form.
	 *
	 * @access private
	 * @return InputfieldWrapper $wrapper InputfieldWrapper to add to form.
	 *
	 */
	private function otherSettings() {

		/* ## OTHER SETTINGS ## */

		$savedSettings = $this->savedSettings;
		$modules = $this->wire('modules');

		// new inputfieldwrapper
		$wrapper = new InputfieldWrapper();
		$wrapper->attr('title', $this->_('Other Settings'));
		$id = $this->className() . 'OtherSettings';
		$wrapper->attr('id', $id);

		$fieldset = $modules->get('InputfieldFieldset');
		$fieldset->attr('id', 'mm_other_settings');
		$fieldset->label = $this->_('Other Settings');

		/*1. Other Settings: display user's media only setting */
		$userMediaOnly = isset($savedSettings['user_media_only'][0]) ? $savedSettings['user_media_only'][0] : 1;

		$f = $modules->get('InputfieldRadios');
		$f->attr('name', 'mm_settings_user_media');
		$f->attr('value', $userMediaOnly ? $userMediaOnly : 1);
		$f->label =  $this->_('Display User Media');
		$f->collapsed = Inputfield::collapsedYes;
		$f->notes = $this->_('Use this setting if you want users to only see the media they uploaded to the Media Library.');

		$radioOptions = array (
			1 => $this->_('Display all media'),
			2 => $this->_('Display only the current users\'s media'),
	 	);

	 	$f->addOptions($radioOptions);

	 	$fieldset->add($f);

		/*2. Other Settings: Sort */
		$sortMedia = isset($savedSettings['sort_media'][0]) ? $savedSettings['sort_media'][0] : 1;
	 	$f = $modules->get('InputfieldRadios');
		$f->attr('name', 'mm_settings_sort');
		$f->attr('value', $sortMedia ? $sortMedia : 1);
		$f->label =  $this->_('Sort Media By');
		$f->collapsed = Inputfield::collapsedYes;
		$f->notes = $this->_("This is the default sort. It affects how media is sorted/displayed in your Media Library. The settings here can be overriden by the 'Live Sort' in the Media Library.");

		$radioOptions = array (
			1 => $this->_('Title'),
			2 => $this->_('Tags'),
			3 => $this->_('Modified'),
			4 => $this->_('Created'),
			5 => $this->_('Published'),
			6 => $this->_('Description'),
	 	);

	 	$f->addOptions($radioOptions);

	 	$fieldset->add($f);

	 	/*3. Other Settings: Sort Order */
		$sortMediaOrder = isset($savedSettings['sort_media_order'][0]) ? $savedSettings['sort_media_order'][0] : 1;
	 	$f = $modules->get('InputfieldRadios');
		$f->attr('name', 'mm_settings_sort_order');
		$f->attr('value', $sortMediaOrder ? $sortMediaOrder : 1);
		$f->label =  $this->_('Sort Media Order');
		$f->collapsed = Inputfield::collapsedYes;

		$radioOptions = array (
			1 => $this->_('Ascending'),
			2 => $this->_('Descending'),
	 	);

	 	$f->addOptions($radioOptions);

	 	$fieldset->add($f);

		 /*4. Other Settings: Show filter profiles */
		// hide use of filter profiles by default
		$showFilterProfiles = isset($savedSettings['show_filter_profiles'][0]) ? $savedSettings['show_filter_profiles'][0] : 2;
	 	$f = $modules->get('InputfieldRadios');
		$f->attr('name', 'mm_show_filter_profiles');
		$f->attr('value', $showFilterProfiles ? $showFilterProfiles : 2);
		$f->label =  $this->_('Show Filter Profiles');
		$f->collapsed = Inputfield::collapsedYes;

		$radioOptions = array (
			1 => $this->_('Yes'),
			2 => $this->_('No'),
		);

	 	$f->addOptions($radioOptions);

		$fieldset->add($f);

		/*5. Other Settings: Managed Media Types */
		// manage all 4 media types by default
		$allowAllMedia = array('audio', 'document', 'image', 'video');
		$allowedMedia = isset($savedSettings['allowed_media']['media']) ? $savedSettings['allowed_media']['media'] : $allowAllMedia;
		$f = $modules->get('InputfieldCheckboxes');
		$f->attr('name', 'mm_allowed_media');
		$f->label = $this->_('Allowed Media Types');
		$f->collapsed = Inputfield::collapsedYes;
		$f->description = $this->_('Check the box next to each media type that will be available. Uncheck the ones not needed.');
		$f->notes = $this->_('Please note that unwanted media types will not show up in the menu.');
		$f->addOption('audio', 'Audio');
		$f->addOption('document', 'Document');
		$f->addOption('image', 'Image');
		$f->addOption('video', 'Video');
		$f->attr('value', $allowedMedia);

		$fieldset->add($f);

		// disable 'all' menu item, hence 'all media' view
        $disableAllMediaView = isset($savedSettings['disable_all_media_view'][0]) ? $savedSettings['disable_all_media_view'][0] : 2;
        $f = $modules->get('InputfieldRadios');
        $f->attr('name', 'mm_disable_all_media_view');
        $f->attr('value', $disableAllMediaView ? $disableAllMediaView : 2);
        $f->label =  $this->_("Disable 'All' Media View");
        $f->collapsed = Inputfield::collapsedYes;
        $f->showIf = 'mm_allowed_media.count>1';
        $f->requiredIf = 'mm_allowed_media.count>1';
        $f->notes = $this->_("Use this if you do not need (paginated) view of all media. Disabling this will remove the 'All' menu item. Please note that in case you only have one only allowed one media type in the setting above, this setting will have no effect. In that case, viewing all media will be disabled by default.");

        $radioOptions = array (
            1 => $this->_('Yes'),
            2 => $this->_('No'),
        );

        $f->addOptions($radioOptions);

		$fieldset->add($f);

		// enable upload anywhere feature
        $uploadAnywhere = isset($savedSettings['upload_anywhere'][0]) ? $savedSettings['upload_anywhere'][0] : 2;
        $f = $modules->get('InputfieldRadios');
        $f->attr('name', 'mm_upload_anywhere');
        $f->attr('value', $uploadAnywhere ? $uploadAnywhere : 2);
        $f->label =  $this->_("Enable Upload Anywhere");
        $f->collapsed = Inputfield::collapsedYes;
        $f->showIf = 'mm_allowed_media.count>1';
        $f->requiredIf = 'mm_allowed_media.count>1';
        $f->notes = $this->_("When enabled, you will be able to instantly upload items to your Media Library via drag and drop in any of the media views without having to visit the upload page. Please note that other relevant settings still apply, for instance, whether media are published on upload, etc.");

        $radioOptions = array (
            1 => $this->_('Yes'),
            2 => $this->_('No'),
        );

        $f->addOptions($radioOptions);

		$fieldset->add($f);

		// unzip files
		// @note: we only unzip 1 level deep; if not unzipping, zip needs to be set as acceptable extension for documents, otherwise they are skipped for media creation
        $unzipFiles = isset($savedSettings['unzip_files'][0]) ? $savedSettings['unzip_files'][0] : 1;
        $f = $modules->get('InputfieldRadios');
        $f->attr('name', 'mm_unzip_files');
        $f->attr('value', $unzipFiles ? $unzipFiles : 1);
        $f->label =  $this->_("Decompress ZIP Files");
        $f->collapsed = Inputfield::collapsedYes;
        $f->notes = $this->_("Select yes to have uploaded ZIP archives (including files for scanning) to be automatically decompressed and added as uploads. If you select no, ZIP files will be treated as documents and will not be decompressed. In that case, you will need to specify 'zip' as a valid file extension in the documents section above. Otherwise, ZIP files will be ignored and deleted.");

        $radioOptions = array (
            1 => $this->_('Yes'),
			2 => $this->_('No'),
        );

        $f->addOptions($radioOptions);

		$fieldset->add($f);

	 	$wrapper->add($fieldset);

		return $wrapper;

	}

	/**
	 * Contents of 'Other Settings' form.
	 *
	 * @access private
	 * @return InputfieldWrapper $wrapper InputfieldWrapper to add to form.
	 *
	 */
	private function customColumnsSettings() {

		/* ## CUSTOM COLUMNS ## */

		/*
			- for specifying fields to show as custom columns for media
		 	- these are for additional info derived from the media page (e.g. summary, biography, date, location, etc)
			- @note: for thumbs view: tabular only
		*/

		$savedSettings = $this->savedSettings;
		$modules = $this->wire('modules');

		// new inputfieldwrapper
		$wrapper = new InputfieldWrapper();
		$wrapper->attr('title', $this->_('Custom Columns Settings'));
		$id = $this->className() . 'CustomColumnsSettings';
		$wrapper->attr('id', $id);

		$fieldset = $modules->get('InputfieldFieldset');
		$fieldset->attr('id', 'mm_custom_columns');
		$fieldset->label = $this->_('Custom Columns');

		/*1. Default Custom Columns: Image/Audio/Document/Video @note: globally disallowed will not be available */
		$defaultCustomColumns = $this->mmUtilities->getSettingsDefaultCustomColumns();

		foreach ($defaultCustomColumns as $name => $label) {

			$key = 'custom_columns';
			$index = "{$name}";

			$customColumnValues = isset($savedSettings[$key][$index]) ? $savedSettings[$key][$index] : array();

			$f = $modules->get('InputfieldAsmSelect');
			$f->attr('name', "mm_custom_columns_{$name}");
			$f->collapsed = Inputfield::collapsedYes;
			$f->label = $label;
			$f->description = $this->_("Select custom fields from the {$name} media page to display.");
			$f->notes = $this->_("Please note this only works when using Thumbs View Tabular. Also note that only supported Fieldtypes are available for selection.");
			$f->attr('value', $customColumnValues);

			$customColumnOptions = $this->mmUtilities->getTemplateFields("media-manager-{$name}");

			$f->addOptions($customColumnOptions);

			$fieldset->add($f);

		}


	 	$wrapper->add($fieldset);


		####################

		/* Settings save button (@access-control) */
		if(!$this->noSettings) {
			$f = $modules->get('InputfieldButton');
			$f->attr('id+name', 'mm_settings_btn');
			$f->class .= ' head_button_clone';
			$f->value = $this->_('Save');
			$f->attr('type', 'submit');

			$wrapper->append($f);

		}

		return $wrapper;

	}

	/* ######################### - TABS - ######################### */


	/**
	 * Builds the media upload form + info.
	 *
	 * @access protected
	 * @param array $savedSettings Saved media manager upload settings.
	 * @return string $out Markup of upload form.
	 *
	 */
	protected function renderUploadForm(Array $savedSettings) {

		// redirect if upload not allowed and page accessed directly
		// @access-control
		if($this->noUpload) $this->session->redirect($this->wire('page')->url);

		// sanity check
		if(!is_array($savedSettings)) throw new WireException($this->_('Media Manager settings must be an array'));

		// create a multipart form for media uploads
		$form = $this->wire('modules')->get('InputfieldForm');
		$form->attr('id', 'fileupload');
		$form->action = './';
		$form->method = 'post';
		$form->attr('enctype', 'multipart/form-data');
		// add a custom class to this form if neccessary
		$form->class .= isset($savedSettings['duplicates_replace_confirm'][0]) ? " mm_confirm_media_overwrite" : '';

		$form->add($this->uploadAddFilesTab());
		// @note: if in modal, no need for uploads and help info
		if(!$this->wire('input')->get('modal')){
			$form->add($this->uploadScanTab());
			$form->add($this->uploadHelpInfoTab());
		}

		// if saving settings or 'scanned' media or 'adding uploaded media to media library'
		$post = $this->wire('input')->post;
		// settings
		if($post->mm_settings_btn) {
			$this->mmActions = new MediaManagerActions();
			$actionType = 'settings';
			$this->mmActions->actionMedia($actionType, $form);
		}

		// @note: we now process POST in Ajax

		return $form->render();

	}

	/**
     * Add Files Tab contents for executeUpload().
     *
     * We use this to directly upload files via add files or drag and drop.
     *
     * @access private
     * @return InputfieldWrapper $tab.
     *
     */
    private function uploadAddFilesTab() {

        $tab = new InputfieldWrapper();
        $tab->attr('title', $this->_('Add'));
        $id = $this->className() . 'Add';
        $tab->attr('id', $id);
        $tab->class .= ' WireTab';

        // space separated string of all our medias's valid extensions (as saved in their respective field settings)
        // pipe separated for jfu
        $validExts = implode(', ', $this->mmUtilities->validExtensions());

        $savedSettings = $this->savedSettings;
        $modules = $this->wire('modules');
        $jfu = $modules->get('JqueryFileUpload');

        $m = $modules->get('InputfieldMarkup');

		// @note: if we are not in a modal: we show the 'jfu add files markup'
		if(!$this->wire('input')->get('modal')){
			$m->label = $this->_('Upload Actions');

			if(isset($savedSettings['duplicates_replace_confirm'][0])) {
				$m->notes = $this->_("Please note that the 'Confirm Overwrite of Duplicate Media' setting is in effect. You will need to tick the 'Confirm Overwrite' checkbox above before you can upload media.");
			}

			// show a confirm overwrite checkbox if setting is in place. @note: setting works in conjunction with overwrite duplicates setting. This here is an optional 'double confirm' users setting
			$confirmOverwriteMediaCheckbox = '';
			if(isset($savedSettings['duplicates_replace_confirm'][0])) {
				$c = $modules->get('InputfieldCheckbox');
				$c->attr('id+name', 'mm_confirm_overwrite_duplicate_media');
				$c->attr('value', 1);
				$c->label = $this->_('Confirm Overwrite');
				$confirmOverwriteMediaCheckbox = $c->render();
			}

			$afterUploadSetting = isset($savedSettings['after'][0]) ? $savedSettings['after'][0] : 2;
			// we can only delete  if not immediately adding to Media Library or if noDelete is not in effect (@note: the && here is actually OR)
			$uploadsDeletable = $afterUploadSetting == 3 && !$this->noDelete ? 1 : 0;

			// show a publish button if 'after uploading' setting is: 'do not add uploads to ML; keep them for review' (option 3) + noPublish is uplo
			// @todo: here we do we also need to check for $this->nopublish? do more tests
			$addToLibraryMarkup = false;
			if(isset($this->savedSettings['after'][0]) && $this->savedSettings['after'][0] == 3 && !$this->noPublish) $addToLibraryMarkup = true;

			// main button: Start upload
			$dropdownButtonOptions = array(
				'id' => 'jfu_upload_actions_btns',
				'name' => 'jfu_upload_actions_btns',
				'value' => $this->_('Start'),
			);
			// cancel upload dropdown
			$dropdownButtonOptions['add_action_values'][] = array(
				'value' => 'cancel',
				'label' => $this->_('Cancel'),
				'icon' => 'ban'
			);

			// delete upload dropdown @todo: more tests here
			if($uploadsDeletable) {
				$dropdownButtonOptions['add_action_values'][] =
				array(
					'value' => 'delete',
					'label' => $this->_('Delete'),
					'icon' => 'trash'
				);
			}
			// add published/unpublished upload dropdowns
			if($addToLibraryMarkup) {

				$dropdownButtonOptions['add_action_values'][] =
				array(
					'value' => 'publish',
					'label' => $this->_('Add + Publish to Media Library'),
					'icon' => 'eye'
				);

				$dropdownButtonOptions['add_action_values'][] =
				array(
					'value' => 'unpublished',
					'label' => $this->_('Add Unpublished to Media Library'),
					'icon' => 'eye-slash'
				);
			}

			$b = $this->renderDropDownSubmit($dropdownButtonOptions);

			$customJFUActionMarkup = $b->render();
			$customJFUActionMarkup .=
				"<span id='jfu_uploads_action' class='mm_hide'>" .
					"<span id='jfu_uploads_action_start' class='start'></span>" .
					"<span id='jfu_uploads_action_cancel' class='cancel'></span>" .
					"<span id='jfu_uploads_action_delete' class='delete'></span>" .
				"</span>";


		}
		/*
			- @note: we are in a modal
			- since we got here via 'add/insert' from InputfieldMM, we assume that...
			- we did not find the media we wanted in the library or we didn't want the one we found
			- in this case, overwrite is not an issue then (we either want to replace or there's nothing to replace)
			- it also means we want the media right away, so we let Inputfield's MM 'jfu autoupload' to remain
			- Hence, no need to show start..etc buttons for JFU upload
			- @note: we've also skipped upload's scan and help tabs in renderUploadForm()
		*/
		else {
			$m->label = '';
			// @todo: temporary for now for modal use to satisfy JFU
			$customJFUActionMarkup = '<div></div>';
			$uploadsDeletable = 0;
			$confirmOverwriteMediaCheckbox = '';
		}

        // options to pass to Jquery File Upload
		$renderJFUOptions = array(
			'uploadsDeletable' => $uploadsDeletable,
			'useCustomForm' => 0,
			'customJFUActionMarkup' => $customJFUActionMarkup,
			// a custom setting
			'renderAllowedFileExtensions' => $validExts
		);

        $m->attr('value', $confirmOverwriteMediaCheckbox . $jfu->render($renderJFUOptions));

        $tab->add($m);

        return $tab;

    }

    /**
     * Scan Uploads Tab contents for executeUpload().
     *
     * We use this to execute a scan of already uploaded media.
     *
     * @access public
     * @return InputfieldWrapper $tab.
     *
     */
    public function uploadScanTab() {

        $filesToScanMarkup = $this->renderFilesToScanList();

        $tab = new InputfieldWrapper();
        $tab->attr('title', $this->_('Scan'));
        $id = $this->className() . 'Scan';
        $tab->attr('id', $id);
        $tab->class .= ' WireTab';

        $modules = $this->wire('modules');

		$dropdownButtonOptions = array(
			// main button [scan + publish]
			'id' => 'mm_scan_btns',
			'name' => 'mm_scan_btns',
			'value' => $this->_('Scan + Publish'),
			'add_action_values' => array(
				// scan + keep unpublished
				0 => array(
					'value' => 'scan-unpublished',
					'label' => $this->_('Scan + Keep Unpublished'),
					'icon' => 'eye-slash'
				),
			)
		);
		// if can delete: delete scans dropdown
		if(!$this->noDelete) {
			$dropdownButtonOptions['add_action_values'][] =
			array(
				'value' => 'scan-delete',
				'label' => $this->_('Delete'),
				'icon' => 'trash'
			);
		}

		$b = $this->renderDropDownSubmit($dropdownButtonOptions);

        $notes = "<p class='notes'>".$this->_("Process and move media you have already uploaded to the server to your Media Library."). "</p>";

		/* @note: @todo: not currently using this
		$c = $modules->get('InputfieldCheckbox');
        $c->attr('id+name', 'mm_scan_delete_processed');
        $c->attr('value', 1);
        $c->label = $this->_('Delete Processed');// @todo: better wording?
		$deleteAfterScan = $c->render();
		*/
		$deleteAfterScan = '';

		$scanSpinner =
			'<span id="scan_files_spinner" class="mm_hide"><i class="fa fa-lg fa-spin fa-spinner"></i></span>';


		####################

        $m = $modules->get('InputfieldMarkup');
        $m->label = $this->_('Scan Media');
        $m->attr('value', $b->render() . $scanSpinner .$deleteAfterScan.  $notes .$filesToScanMarkup);

        $tab->add($m);

        return $tab;

	}

	/**
	 * Render list of files ready for scanning into Media Manager Library.
	 *
	 * @note: If unzipping is set to true, ZIP archives will be decompressed prior to listing.
	 *
	 * @access private
	 * @return string $out Markup of list of files to scan.
	 *
	 */
    private function renderFilesToScanList() {

        /*
            @note:
             - for files to scan, the $dir is : /site/assets/MediaManager/uploads/
             - for files uploaded via jQueryFileUpload and awaiting review it is: /site/assets/MediaManager/jqfu/
         */

		$data = array();
        $dir = $this->mediaUploadsDir . "uploads/";

        // if uploads directory not found, exit with error
        if(!is_dir($dir)) {
            $data['message'] = 'error';
            $error = 'Media Manager: ' . $this->_('Media Manager: No folder/directory found at the path ') . $dir . '.';
            $data['notice'] = $error;
            return $data;
		}
		// @todo: add limit here! maybe 100 files? then break?

		$out = '';
		$files = '';
		$processFilesText = '';

		###############

		// first, check if there are zip files to uncompress && unzipping is allowed
		// @note: determined by settings
		if($this->savedSettings['unzip_files'][0] == 1) {
			$zipFilesArray = $this->wire('files')->find($dir,array('extensions'=>array('zip')));
			if(count($zipFilesArray)) {
				// recursively unzip zip files
				foreach($zipFilesArray as $zipFile) {
					// reuse this recursive unzipper in JFU
					$this->jfu->processArchives($zipFile,$dir);
				}
			}
		}


        ####################
		// for debugging only
        //$start = microtime(true);

		############
		// RecursiveDirectoryIterator with a Generator
		$instance = $this->mmUtilities->recursiveDirectoryIterator($dir);
        $totalFiles = 0;
        foreach($instance as $value) {
            $totalFiles++;
            $files .=
                "<tr>" .
                    "<td>" . $totalFiles . "</td>" .
                    $value .
                "</tr>";
        }

		$processFilesText =
			"<p id='mm_scan_total_files'>" . $this->_('Files count') .
				": <span>{$totalFiles}</span>".
			"</p>";
		/*
		// debug
		$processFilesText .= "<p>Mem peak usage: " . (memory_get_peak_usage(true)/1024/1024)." MiB</p>";
        $finished = microtime(true) - $start;
		$processFilesText .= "<p>Completed in: {$finished} seconds</p>";
		*/

		# - small (no change)

        ######################

        $out =
		"\n\t<div class='uploaded-to-scan-files-list uk-overflow-auto uk-width-1-1'>".
			$processFilesText .
            "<table id='mm_scan_list' role='presentation' class='scan_files_list uk-table uk-table-divider uk-table-hover uk-table-justify uk-table uk-table-middle uk-table-responsive'>".
                "<thead>" .
                    "<tr>" .
                        "<th class='uk-width-small mm_scan_list_file_count'>#</th>" .
                        "<th class='uk-table-expand mm_scan_list_file_name'>" . $this->_('Name') . "</th>" .
                        "<th class='uk-width-small mm_scan_list_file_size'>" . $this->_('Size') . "</th>" .
                        "<th class='uk-width-medium mm_scan_list_check_action'>" .
                            "<input type='checkbox' class='toggle_all uk-checkbox uk-form-controls-text' title='Select All'>".
                        "</th>" .
                    "</tr>" .
                "</thead>" .
                "\n\t\t<tbody id='scan-files'>{$files}</tbody>\n".
            "\t</table></div>\n";

		return $out;

	}

	/**
     * Help Information Tab contents for executeUpload().
     *
     * Display help information for uploading.
     *
     * @access public
     * @return InputfieldWrapper $tab.
     *
     */
    public function uploadHelpInfoTab() {

        $tab = new InputfieldWrapper();
        $tab->attr('title', $this->_('Help'));
        $id = $this->className() . 'Help';
        $tab->attr('id', $id);
        $tab->class .= ' WireTab';

        $m = $this->wire('modules')->get('InputfieldMarkup');
        $m->label = $this->_('Help and Information');
        $m->attr('value', $this->renderUploadInfo());
        $tab->add($m);

        return $tab;

    }

	/**
	 * Builds the media upload info/instuctions markup.
	 *
	 * @access public
	 * @return string $out Markup of information about uploading media.
	 *
	 */
	private function renderUploadInfo() {

		$docs = "<a href='http://mediamanager.kongondo.com/'>" . $this->_('official documenation.') . '</a>';

		$out =
			'<div id="mm_upload_info">
				<div id="info">
					<ul>
						<li>' . $this->_("For comprehensive instructions on how to use Media Manager, please refer to the ") . $docs . '</li>
						<li>' . $this->_("You can upload files to your Media Library using either of the two methods specified below.") . '</li>
						<li>' . $this->_("You can upload single or multiple files (both uncompressed and compressed). Compressed files, if your settings allow, must be in zip format. Uncompressed files must be of the types specified in your 'allowed media file extensions' settings.") . '</li>
						<li>' . $this->_("Each media will be given a title based on its file name. It is advisable to give your files sensible names before uploading.") .  '</li>
						<li>' . $this->_("Nested folders are supported, for example /Animals/Cats/Big Cats/. However, the media will not be stored in that sort of hierarchy.") . '</li>
						<li>' . $this->_("Depending on the size of your media, your server settings and internet connection, it may take a while to upload your files. If you have larger files, it is better to transfer them to your server first then use the 'Scan' method below.") . '</li>
						<li>' . $this->_("For images, it is advisable to enable client-size-image-resizing in the settings here.") . '</li>
						<li>' . $this->_("Depending on the Media Manager permissions that have been set up and whether or not your user profile has those permissions, you may or may not be be able to perform certain actions, including uploading, editing, publishing and deleting media in the Media Library.") . '</li>
					</ul>
					<ol>
						<li>' . $this->_("Add: In the 'Add' Tab, use 'Add files' or Drag and Drop files to upload them") . '</li>
						<li>' . $this->_("Scan: If you have already uploaded your files to /assets/MediaManager/uploads/ (for instance using FTP), click the 'Scan' button in the 'Scan' Tab to complete the media creation process.") . '</li>
					</ol>
				</div>
			</div>';

		return $out;

	}


	/* ######################### - CLEANUP - ######################### */

	/**
	 * Builds the media manager cleanup utility list and form.
	 *
	 * @access protected
	 * @return string $form Rendered markup of form with info and action to cleanup media manager components.
	 *
	 */
	protected function renderCleanup() {

		// CREATE A NEW FORM
		$form = new InputfieldForm;
		$form->attr('id', 'mm_cleanup');
		$form->action = './';
		$form->method = 'post';

		// CREATE A NEW WRAPPER
		$w = new InputfieldWrapper;

		$modules = $this->wire('modules');

		// CREATE THE FIRST FIELDSET
		$fs = $modules->get("InputfieldFieldset");
		$fs->label = $this->_('Cleanup');

		// CREATE AN INPUTFIELD MARKUP
		$m = $modules->get('InputfieldMarkup');
		$m->label = $this->_('Warning: Please read carefully');
		$m->collapsed = Inputfield::collapsedYes;

		// array of Media Manager fields. We'll use this to delete each, one by one as applicable
		$fields = array('media_manager_audio', 'media_manager_document', 'media_manager_image', 'media_manager_video', 'media_manager_settings',);

		$info = '';
		$info.=
			'<div id="components">' .
				'<div id="warning">'.
					'<p class="NoticeError uk-alert-danger">' . $this->_('This utility will irreversibly delete all the Media Manager Components listed below AND ALL your UPLOADED MEDIA. Use this utility in case you wish to completely uninstall Media Manager. You will afterward need to uninstall the module as usual. Before you start, make sure to empty the trash of any Media Manager pages.') .
					'</p>'.
			'</div>';

		$info .= '<div>' .
					'<h4>' . $this->_('Fields') . '</h4>' .
					'<ol>';

		foreach ($fields as $field) $info .= '<li>' . $field . '</li>';

		$info .=	'</ol>'.
				'</div>';

		$templates = array('media-manager', 'media-manager-audio', 'media-manager-document', 'media-manager-image', 'media-manager-video','media-manager-settings',);


		$info .= '<div><h4>' . $this->_('Templates') . '</h4><ol>';

		foreach ($templates as $template) $info .= '<li>' . $template . '</li>';

		$info .='</ol></div>';
		$info .='<div><h4>' . $this->_('Pages') . '</h4><p>' .
					$this->_('All pages using the listed templates') . '</p></div>';

		$templateFile = 'media-manager.php';

		$chx = '';

		$chx = "
		<input type=checkbox id='remove_tpl_files' name='remove_tpl_files' value='1'>
		<label id='remove_tf' for='remove_tpl_files'>" . $this->_("Check box to also remove the Template File") . "</label>";

		$info .=
				'<div>'.
					'<h4>' . $this->_('Template Files (optional)') . '</h4>' .
					'<ol>' .
						'<li>' . $templateFile . '</li>' .
					'</ol>'.
				'</div>' .
			'</div>';

		$s = $modules->get('InputfieldSubmit');
		$s->attr('id+name', 'cleanup_btn');
		$s->class .= " cleanup";// add a custom class to this submit button
		$s->attr('value', $this->_('Cleanup'));

		$m->attr('value', $info . $s->render() . $chx);

		$fs->add($m);
		$w->add($fs);// fieldset added to wrapper

		$form->add($w);

		$post = $this->wire('input')->post;

		// send input->post values to execute cleanup of blog components: fields, templates, template files, pages and role;
		if($post->cleanup_btn)  {
			require_once(dirname(__FILE__) . '/MediaManagerCleanup.php');
			$this->cleanup = new MediaManagerCleanup();
			$this->cleanup->cleanUp($form);
		}

		// render the final form
		return $form->render();

	}



}