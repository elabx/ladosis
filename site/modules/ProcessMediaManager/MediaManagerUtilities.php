<?php namespace ProcessWire;

/**
* Media Manager: Utilities
*
* This file forms part of the Media Manager Suite.
* Provides various utility methods (validation, SQL counts, conversions, etc) for use throughout the module.
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

class MediaManagerUtilities extends ProcessMediaManager {

	/**
	 * Initialize this MediaManagerUtilities Class
	 *
	 * @param object $currentPage The page currently being edited if in InputfieldMediaManager, else blank WireData.
	 * @param object $mediaManagerField The current Media Manager field in the page being edited if in InputfieldMediaManager, else blank WireData.
	 *
	 */
	public function __construct($currentPage, $mediaManagerField) {

		// @note: call parent construct first coz it also sets $ths->currentPage and $this->mediaManagerField
		parent::__construct();

		// we almost always need these properties, so set them early

		// current page and media manager field
		$this->currentPage = $currentPage;
		$this->mediaManagerField = $mediaManagerField;

		// get sanitised url segments
		$this->urlSegments = $this->getURLSegments();
		$this->urlSeg1 = $this->urlSegments[0];
		$this->urlSeg2 = $this->urlSegments[1];
		$this->urlSeg3 = $this->urlSegments[2];

	}


/* ######################### - MEDIA UTILITIES - ######################### */

	/**
	 * Allowed audio media extensions.
	 *
	 * These are saved in the field 'media_manager_audio'.
	 *
	 * @access public
	 * @return array $validExtsAudio Array of strings of extensions of allowed audio formats.
	 *
	 */
	public function validExtensionsAudio() {
		$validExtsAudio = array();
		$f = $this->wire('fields')->get('media_manager_audio');
		if($f && $f->id > 0) {
			$exts = $f->extensions;
			$validExtsAudio = explode(' ', $exts);
		}
		return $validExtsAudio;
	}

	/**
	 * Allowed document media extensions.
	 *
	 * These are saved in the field 'media_manager_document'.
	 *
	 * @access public
	 * @return array $validExtsFile Array of strings of extensions of allowed document formats.
	 *
	 */
	public function validExtensionsDocument() {
		$validExtsFile = array();
		$f = $this->wire('fields')->get('media_manager_document');
		if($f && $f->id > 0) {
			$exts = $f->extensions;
			$validExtsFile = explode(' ', $exts);
		}
		return $validExtsFile;
	}

	/**
	 * Allowed image media extensions.
	 *
	 * These are saved in the field 'media_manager_image'.
	 *
	 * @access public
	 * @return array $validExtsImage Array of strings of extensions of allowed image formats.
	 *
	 */
	public function validExtensionsImage() {
		$validExtsImage = array();
		$f = $this->wire('fields')->get('media_manager_image');
		if($f && $f->id > 0) {
			$exts = $f->extensions;
			$validExtsImage = explode(' ', $exts);
		}
		return $validExtsImage;
	}

	/**
	 * Allowed video media extensions.
	 *
	 * These are saved in the field 'media_manager_video'.
	 *
	 * @access public
	 * @return array $validExtsVideo Array of strings of extensions of allowed image formats.
	 *
	 */
	public function validExtensionsVideo() {
		$validExtsVideo = array();
		$f = $this->wire('fields')->get('media_manager_video');
		if($f && $f->id > 0) {
			$exts = $f->extensions;
			$validExtsVideo = explode(' ', $exts);
		}
		return $validExtsVideo;
	}

	/**
	 * Merges allowed media extensions.
	 *
	 * These are all the allowed extension types for our 4 media groups (audio, document, image, video) + zip files.
	 * We only use these for uploads via the uploads page!
	 *
	 * @access public
	 * @return array $validExts Array of strings of extensions of all allowed media formats.
	 *
	 */
	public function validExtensions() {
		// check if unzipping files. If yes, add universally
		$zipFilesExt = $this->savedSettings['unzip_files'][0] == 1 ? array('zip') : array();
		$validExts = array_merge($zipFilesExt, $this->validExtensionsAudio(), $this->validExtensionsDocument(), $this->validExtensionsImage(), $this->validExtensionsVideo());
		return $validExts;
	}

	/**
	 * Common extensions of audio, document and video media/formats.
	 *
	 * Use this for applying custom thumbs to known types.
	 *
	 * @access public
	 * @return array $commonExts Array of strings of extensions of popular media formats.
	 *
	 */
	public function commonExtensions() {
		$commonExts = array('mp3','wav','ogg','m4a','m4p','flac','wma', 'pdf','doc','docx','xls','xlsx', 'ppt', 'pptx', 'mp4','avi','mkv', 'mpg', 'mov','wmv','m4v');
		return $commonExts;
	}

	/**
	 * Common MIME types of audio, document, image and video media/formats.
	 *
	 * Use this for applying custom thumbs to known types.
	 *
	 * @access public
	 * @return array $commonMediaTypeMIMEs MIMEs of common media by type.
	 *
	 */
	public function commonMediaTypeMIMEs() {
		// @note: if cannot preview in browser (e.g. xls document), we default to image/png and show generic background for docs
		$commonMediaTypeMIMEs = array(
			'audio' => array ('mp3'=>'audio/mpeg','wav'=>'audio/wav','ogg'=>'audio/ogg','m4a'=>'audio/mp4','flac'=>'audio/flac', 'wma'=>'audio/x-ms-wma'),
			'document' => array ('pdf'=>'application/pdf'),// @note: this is for PDF preview in gallery
			'image' => array ('gif'=>'image/gif', 'jpg'=>'image/jpeg', 'jpeg'=>'image/jpeg','png'=>'image/png', 'svg'=>'image/svg+xml'),
			'video' => array ('mp4'=>'video/mp4', 'mkv'=>'video/x-matroska', 'mpg'=>'video/mpeg', 'm4v'=>'video/x-m4v'),
		);
		return $commonMediaTypeMIMEs;
	}



	/**
	 * Generic thumb for audio, document, image and video media.
	 *
	 * We use these in case a media format is not part of popular/common media formats or for image media, no image found.
	 *
	 * @access public
	 * @param string $mediaTypeStr Denoting media type.
	 * @return string $thumb URL of generic thumb.
	 *
	 */
	public function genericThumb($mediaTypeStr) {

		$baseURL = $this->wire('config')->urls->ProcessMediaManager . 'assets/';
		$thumb = '';

		if($mediaTypeStr == 'audio')     $thumb = $baseURL . 'no-audio-thumb.png';
		elseif($mediaTypeStr == 'document')  $thumb = $baseURL . 'no-document-thumb.png';
		elseif($mediaTypeStr == 'image') $thumb = $baseURL . 'no-image-thumb.png';
		elseif($mediaTypeStr == 'video') $thumb = $baseURL . 'no-video-thumb.png';

		return $thumb;

	}

	/**
	 * Generic media preview image.
	 *
	 * Used in media Gallery for non-image media that cannot be displayed in the gallery.
	 *
	 * @access public
	 * @param string $mediaTypeStr Denoting media type.
	 * @return string $previewImage URL of generic preview image.
	 *
	 */
	public function genericPreviewImage($mediaTypeStr) {

		$baseURL = $this->wire('config')->urls->ProcessMediaManager . 'assets/';
		$previewImage = '';

		if($mediaTypeStr == 'audio')     $previewImage = $baseURL . 'audio_bg.jpg';
		elseif($mediaTypeStr == 'document')  $previewImage = $baseURL . 'document_bg.jpg';
		elseif($mediaTypeStr == 'video') $previewImage = $baseURL . 'video_bg.jpg';

		return $previewImage;

	}

	/**
	 * User uploaded custom thumb for audio, document and video media.
	 *
	 * If none found, we use generic thumbs.
	 *
	 * @access public
	 * @param string $mediaExt Media extension.
	 * @return string $customThumb URL of generic thumb.
	 *
	 */
	public function customThumb($mediaExt) {
		$config = $this->wire('config');
		// use user's custom thumb for this media format if available
		$customUserIcon = $config->paths->assets . 'MediaManager/assets/' . $mediaExt . '.png';
		if(is_file($customUserIcon)) $customThumb = $config->urls->assets . 'MediaManager/assets/' . $mediaExt . '.png';
		// if no user custom thumb, use MM's custom thumb for this media format
		else $customThumb = $config->urls->ProcessMediaManager . 'assets/' . $mediaExt . '.png';
		return $customThumb;
	}

	/**
	 * Converts media type string to integer.
	 *
	 * @access public
	 * @param string $mediaTypeStr Specifies media type.
	 * @return integer $mediaTypeInt Denotes media type.
	 *
	 */
	public function mediaTypeInt($mediaTypeStr = null) {
		$mediaTypeInt = '';
		if($mediaTypeStr == 'audio')		$mediaTypeInt = 1;
		elseif($mediaTypeStr == 'document')	$mediaTypeInt = 2;
		elseif($mediaTypeStr == 'image')	$mediaTypeInt = 3;
		elseif($mediaTypeStr == 'video')	$mediaTypeInt = 4;
		return $mediaTypeInt;
	}

	/**
	 * Converts media type integer to string.
	 *
	 * @access private
	 * @param integer $mediaTypeInt Specifies media type.
	 * @return string $mediaTypeStr Denotes media type.
	 *
	 */
	private function mediaTypeStr($mediaTypeInt = null) {
		if($mediaTypeInt == 1)		$mediaTypeStr = 'audio';
		elseif($mediaTypeInt == 2)	$mediaTypeStr = 'document';
		elseif($mediaTypeInt == 3)	$mediaTypeStr = 'image';
		elseif($mediaTypeInt == 4)	$mediaTypeStr = 'video';
		// for image variations
		elseif(strlen($mediaTypeInt) > 1 && (int)substr($mediaTypeInt, 0, 1) === 3) $mediaTypeStr = 'image';
		return $mediaTypeStr;
	}

	/**
	 * Truncates a string to a desired length.
	 *
	 * Mainly used for long media titles in tooltip or similar.
	 * We add &hellip to indicate string is longer than what's visible.
	 *
	 * @param string $title Media title to truncate if very long.
	 * @param integer $length Desired string length.
	 * @param integer $maxLength Max length of string before truncating.
	 * @return string $title Truncated (if needed) title.
	 */
	public function truncateString($string, $length, $maxLength) {
		if(mb_strlen($string, 'UTF-8') > $maxLength) $string = mb_substr($string, 0, $length, 'UTF-8') . '&hellip;';
		return $string;
	}

	/**
	 * Build selector and set options for Media Manager ProcessLister.
	 *
	 * Used by ProcessMediaManager::init().
	 * Some of the settings are custom (user-set).
	 *
	 * @access public
	 * @param array $allowedMedia Checks if InputfieldMediaManager allows only certain media types (insert-in-page context).
	 * @param array $savedSettings Media Manager settings.
	 * @return array $options Options for ProcessMediaManager
	 *
	 */
	public function buildSelector($allowedMedia, $savedSettings) {

		$options = array();
		$config = $this->wire('config');

		/******** - SELECTORS FOR LISTER STUFF - *******/

		$mediaTypeStr = '';
		$mediaField = '';
		$seg = '';
		$initSelector = '';
		$mediaFieldSel = '';
		$templateSelector = '';
		$mediaSelector = '';
		$currentUserSel = '';
		$limit = 30;

		// @note: by default, we don't really need to select by template
		$activeFilter = isset($savedSettings['active_filter']) ? $savedSettings['active_filter'] : '';
		$defaultSelector = 	$activeFilter && isset($savedSettings['filters'][$activeFilter]['defaultSelector']) ?
							$savedSettings['filters'][$activeFilter]['defaultSelector'] :
							"title%=";
		// @note: since version > 011, collapsing Lister filters on load
		// @note: we don't need columns in our renderResults(); we are not using tabulated results
		$togglesArray = array('disableColumns', 'collapseFilters');
		$sort = 'title';// field to sort by; default in ProcessPageLister is '-modified'
		$defaultSort = true;
		$bookmarks = false;

		#######################

		// fetching all 4 media types
		$allMedia = array(1=>'audio', 2=>'document', 3=>'image', 4=>'video');

		// build the find media selector
		$templateSelector = 'template=';
		$mediaFieldSel = '';
		foreach ($allMedia as $mediaTypeInt => $mediaTypeStr) {
			// if media not allowed in global allowed media setting, skip it
			if(!in_array($mediaTypeStr, $this->globalAllowedMedia)) continue;
			if(!is_null($allowedMedia) && !in_array($mediaTypeInt, $allowedMedia))  continue;
			$templateSelector .= 'media-manager-' . $mediaTypeStr . '|';
			$mediaFieldSel .= 'media_manager_' . $mediaTypeStr . '|';
		}
		// keep them clean
		$templateSelector = trim($templateSelector, '|');
		$mediaFieldSel = trim($mediaFieldSel, '|') . "!=''";

		// merge template and field selector strings
		$mediaSelector = $templateSelector . ', ' . $mediaFieldSel;

		$segs = array('audio', 'document', 'image', 'video');

		// @todo: refactor? e.g., use $this->arrayInArrayCheck()?

		/*
			1st condition: view mode, single media view >> e.g. /media-manager/audio/
			2nd condition: add/insert in inputfield mode >> i.e. /media-manager/add/1312-145/image/
		*/
		if((in_array($this->urlSeg1, $segs)) || (in_array($this->urlSeg3, $segs))) $seg = $this->urlSeg1 == 'add' ? $this->urlSeg3 : $this->urlSeg1;
		// @note: if in RTE IMAGE mode, we assume inserting image
		elseif($this->urlSeg1 == 'rte') $seg = 'image';
		// @note: if in RTE LINK mode + we have a urlSeg2 (i.e. /media-manager/link/audio [or 'document', 'image' or 'video'])
		elseif($this->urlSeg1 == 'link' && $this->urlSeg2) $seg = $this->urlSeg2;

		// fetching only 1 media type
		if($seg) {
			$templateSelector = 'template=media-manager-' . $seg;
			$mediaField = 'media_manager_' . $seg;
			$mediaFieldSel = "$mediaField!=''";
			$mediaSelector = $templateSelector . ', ' . $mediaFieldSel;
		}


		/******** - LISTER SETTINGS - *******/

		// limit system fields to only the following. We don't need templates since these should be fixed for each media type respectively
		#$limitFields = array('title', 'id', 'name', 'status', 'modified','created','published','modified_users_id','created_users_id', 'limit', 'sort', 'subfields', 'include', '_custom', $mediaField);
		// set fields to show @todo: we leave this out for now since it seems to also affect subfields

		### custom settings, overriding lister defaults ###
		// From parent:: @property array $toggles One or more of: collapseFilters, collapseColumns, noNewFilters, disableColumns, noButtons [empty]
		// toggles: collapseFilters, collapseColumns, noNewFilters, disableColumns, noButtons

		// @todo - make configurable in settings?
		// date format for native properties: created and modified
		/*$this->set('nativeDateFormat', $this->_('rel')); */

		// sort + sort order
		$sortFormat = $this->sortSelector($savedSettings, $seg, $segs);// array

		if(count($sortFormat)) {
			$sort = $sortFormat['sort'];
			$defaultSort = $sortFormat['defaultSort'];
			// @todo/@note: does not presently work in 'view all context' since we apply to selector and being stripped out in ProcessPageLister
			// @see notes in sortSelector()
			if(!$defaultSort) $mediaSelector .= ", " . $sort;
		}

		// show only the current user's media?
		if( isset( $savedSettings['user_media_only'][0] ) && $savedSettings['user_media_only'][0] == 2 ) {
			$currentUserSel = ", created_users_id=" . $this->wire('user')->id;
			$mediaSelector .= $currentUserSel;
		}

		// initial selector ( @note: user cannot change initial selector)
		// Weed out admin pages + the current page => no admin children; no admin; no trash, no current page and no 404 page

		$trashPageID = $config->trashPageID;// trash page
		$adminRootPageID = $config->adminRootPageID;// admin page
		$http404PageID = $config->http404PageID;// 404 page

		// @todo...limit here if also in default sort? i.e. user set filter profile?
		//$initSelector = "id!=7, id!=2, id!=27, limit=$limit, $mediaSelector";
		$initSelector = "id!={$trashPageID}, id!={$adminRootPageID}, id!={$http404PageID}, limit=$limit, $mediaSelector";

		$options['seg'] = $seg;
		#$options['limitFields'] = $limitFields;// not currently in use
		$options['toggles'] = $togglesArray;// array
		$options['defaultSort'] = $defaultSort ? $sort : '';// field to sort by; default is '-modified' @see notes in sortSelector()
		$options['allowBookmarks'] = $bookmarks;
		$options['defaultSelector'] = $defaultSelector;// we don't really need to select by template
		$options['initSelector'] = $initSelector;

		return $options;

	}

	/**
	 * Buildd sort selector for buildSelector() for sorting media.
	 *
	 * Used by ProcessMediaManager::init().
	 * Some of the settings are custom (user-set).
	 *
	 * @access public
	 * @param array $savedSettings Saved settings for Media Manager.
	 * @param string $seg The current url segment.
	 * @param array $segs URL segments to determine sort output.
	 * @return array $sort Options for sorting.
	 *
	 */
	private function sortSelector($savedSettings, $seg, $segs) {

		$sort = array();
		$sortOrder = '';
		$sortIndex = '';
		$sortByTagsDesc = '';
		$defaultSort = true;
		$input = $this->wire('input');

		// media sort order (ascending/descending)
		if((int) $input->cookie->media_live_sort_order == 2) $sortOrder = "-";
		elseif((int) $input->cookie->media_live_sort_order == 1) $sortOrder = '';
		elseif(isset($savedSettings['sort_media_order'][0]) && $savedSettings['sort_media_order'][0] == 2) $sortOrder = "-";
		// media sort
		if($input->cookie->media_live_sort) $sortIndex = $input->cookie->media_live_sort;
		elseif(isset($savedSettings['sort_media'][0])) $sortIndex = $savedSettings['sort_media'][0];

		// if nothing set, return early
		if(!$sortIndex)	 return $sort;

		$sortOptions = array (
			1 => 'title',
			2 => 'tags',
			3 => 'modified',
			4 => 'created',
			5 => 'published',
			6 => 'description',
	 	);

		// if sorting by tags OR description
	 	if($sortIndex == 2 || $sortIndex == 6) {
	 		$sortType = $sortOptions[$sortIndex];
	 		// all media view: no urlSeg so on main landing page
	 		// hence we need to sort by all 4 media types fields
	 		// we also cannot use 'defaultSort' since multiple sorts
	 		/*
	 			@todo/@note:
	 			- this is not working in ProcessPageLister (version since PW 2.7).
	 			- sort in selectors is not allowed; they are stripped out in ProcessPageLister::getSelector()
	 			- (but in one media view works fine since we apply to defaultSort)
	 			- in later versions of ProcessPageLister this has probably been fixed
	 		*/
	 		if(!$seg) {
	 			$defaultSort = false;
	 			foreach ($segs as $mediaType) $sortByTagsDesc .= "sort={$sortOrder}media_manager_{$mediaType}.{$sortType}, ";
	 		}
	 		// one media view
	 		else $sortByTagsDesc .= "{$sortOrder}media_manager_{$seg}.{$sortType}";

	 		$sort['sort'] = trim($sortByTagsDesc, ', ');

	 	}
	 	// else we'll use defaultSort irrespective
	 	else {
	 		$sort['sort'] = $sortOrder . $sortOptions[$sortIndex];
	 	}

	 	$sort['defaultSort'] = $defaultSort;

	 	return $sort;

	}

	/**
	 * Count the number of variations for a single image media.
	 *
	 * Variations are images cropped or resized within the media page itself using ProcessWire.
	 *
	 * @access public
	 * @param Page $mediaPage Image media page that may have image variations.
	 * @return integer $count Number of found variations, otherwise 0.
	 *
	 */
	public function imageVariationsCount($mediaPage) {

		$count = 0;

		$images = $mediaPage->media_manager_image;
		$originalImage = $images->first();
		if(!$originalImage) return false;

		$ext = '.' . $originalImage->ext;
		$originalBasename = $originalImage->basename;
		// get the version image basename (although we expect only 1 image with variations in this field, you never know)
		// PW names variations as 'original_image_name-vxx.ext' where 'xx' is the $version
		$versionBasenameFilter = str_replace($ext, '', $originalBasename) . '-v';// returns 'original_image_name-v'
		// making sure we only find the original images variations, nothing more
		$filtered = $images->find('sort=basename, basename%='. $versionBasenameFilter);// WireArray of image variations

		$count = count($filtered);

		return $count;

	}

	/* ######################### - GETTERS - ######################### */

	## - JFU CONFIGS - ##

	/**
	 * Outputs array of jQueryFileUpload configuration settings.
	 *
	 * This will be eventually sent to the browers using $jfu->configsJFU().
	 *
	 * @access public
	 * @param bool $uploadAnywhere Whether in JFU Upload anywhere mode.
	 * @return array $configs Configuration settings.
	 *
	 */
	public function getConfigsJFUOptions($uploadAnywhere=false, $formData=array(), $setShowUploaded=null) {

		$configs = array();
		$savedSettings = $this->savedSettings;
		$fields = $this->wire('fields');

		$url = $this->wire('config')->urls->admin . 'media-manager/ajax/';
		// pipe separated for jfu
		// @note: not saved to media_manager_settings
		$validExts = implode('|', $this->validExtensions());
		$imageMaxWidth = $fields->get('media_manager_image') ? $fields->get('media_manager_image')->maxWidth : '';// @note: -ditto-
		$imageMaxHeight = $fields->get('media_manager_image') ? $fields->get('media_manager_image')->maxHeight : '';// @note: -ditto-

		// if showUploaded value sent, we use it (@note: useful for InputfieldMM where we ALWAYS WANT showUpload to be false)
		if(!is_null($setShowUploaded)) {
			$showUploaded = $setShowUploaded;
		}
		// else, determine value from global setting
		else {
			// uploads: check if 'add to media library + (un)publish' setting is in place
			$uploadAndCreate = isset($savedSettings['after'][0]) ? (int) $savedSettings['after'][0] : 2;
			if($uploadAndCreate === 3) $showUploaded = 1;// if will manually add uploaded files to Media Library, then we show uploaded files
			else $showUploaded = 0;// else we don't display uploaded files if after upload, files will immediately be added to Media Library
		}

		// @note: for our JFU 'anywhere upload', otherwise we get a 'PW aborted, forged notice'
		$session = $this->wire('session');
		$tokenName = $session->CSRF->getTokenName();
		$tokenValue = $session->CSRF->getTokenValue();

		$defaultFormData = array($tokenName => $tokenValue);
		if($formData != null && is_array($formData)) $formData = array_merge($defaultFormData, $formData);
		else $formData = $defaultFormData;

		$currentPageID = (int) $this->currentPage->id;
		$mediaManagerFieldID = (int) $this->mediaManagerField->id;

		// jfu configs to send to browser. @note: ditto + we merge with $url + acceptFileTypes + other stuff in jqfu that we don't want to change here, e.g. paramName, etc
		$defaultConfigsJFUOptions = array(
			'url' => $url,
			'acceptFileTypes' => $validExts,// combined from our media manager media fields extensions settings
			'imageMaxWidth' => $imageMaxWidth,// from media_manager_image field
			'imageMaxHeight' => $imageMaxHeight,// -ditto-
			// custom option: depends on 'after' upload what to do; if opt 3, then show uploaded is 1 (true), otherwise 0 (false)
			'showUploaded' => $showUploaded,
			'disableUploads' => $this->noUpload,
			// make only 1 ajax call (@todo?: means only 1 cancel button if group of files added together!?)
			'singleFileUploads' => false,
			// own/custom config: tells JFU we'll use own markup for actions buttons (start,delete, etc)
			// @todo: revisit this! still applicable?
			'useCustomJFUActionMarkup' => true,
			// @note: custom form data for JFU to send with uploaded media...
			// used in our JFU 'anywhere upload'
			'formData' => $formData,
		);

		$validationSettings = isset($savedSettings['validation']) ? $savedSettings['validation'] : array();
		$modeSettings = isset($savedSettings['mode']) ? $savedSettings['mode'] : array();
		$imageSettings = isset($savedSettings['image']) ? $savedSettings['image'] : array();
		$audioSettings = isset($savedSettings['audio']) ? $savedSettings['audio'] : array();
		$videoSettings = isset($savedSettings['video']) ? $savedSettings['video'] : array();
		$documentSettings = isset($savedSettings['document']) ? $savedSettings['document'] : array();

		// for unsetting WireUpload specific settings
		$wuSettings = array('setOverwrite', 'setLowercase', 'setOverwriteFilename');

		foreach ($validationSettings as $key => $value) {

			// remove WireUpload specific settings
			if(in_array($key, $wuSettings)) unset($validationSettings[$key]);
			// shared WireUpload and jfu setting
			elseif(isset($validationSettings['setMaxFileSize'])) {
				$validationSettings['maxFileSize'] = $validationSettings['setMaxFileSize'];
				unset($validationSettings['setMaxFileSize']);// remove the WireUploadsetting
			}
			// shared WireUpload and jfu setting
			elseif(isset($validationSettings['setMaxFiles'])) {
				$validationSettings['maxNumberOfFiles'] = $validationSettings['setMaxFiles'];
				unset($validationSettings['setMaxFiles']);// remove the WireUploadsetting
			}

		}

		// merge the various settings
		// @todo: check if array_merge is slowing things down? if so, then 'implode', 'concat' and 'explode' instead?
		$configs = array_merge($defaultConfigsJFUOptions, $validationSettings, $modeSettings, $imageSettings, $audioSettings, $videoSettings, $documentSettings);

		// get back our boolean values that were json_encode'ed as strings
		foreach ($configs as $key => $value) {
			if($value === 'true') $configs[$key] = true;
			elseif($value === 'false') $configs[$key] = false;
		}

		// @note: if upload anywhere is true, we force autoupload to be true irrespective of the value in settings under 'Upload Mode'
		if($uploadAnywhere) $configs['autoUpload'] = true;

		return $configs;

	}

	/**
	 * Fetches a named cookie.
	 *
	 * @note: value of these cookies are always integers.
	 *
	 * @access public
	 * @return int $cookie Value of the cookie.
	 *
	 */
	public function getCookie($cookieName) {
		$cookie = (int) $this->wire('input')->cookie->$cookieName;// js cookie for session
		return $cookie;
	}

	/**
	 * Get and sanitize URL Segments ready for various logic/operations.
	 *
	 * @access public
	 * @return array $urlSegments URL segments information.
	 *
	 */
	public function getURLSegments() {

		$urlSegments = array();

		$input = $this->wire('input');
		$sanitizer = $this->wire('sanitizer');

		// @note: there will be times when we override sanitization type
		$urlSegments[] =  $sanitizer->pageName($input->urlSegment1);
		$urlSegments[] =  $sanitizer->pageName($input->urlSegment2);
		$urlSegments[] =  $sanitizer->pageName($input->urlSegment3);
		$urlSegments[] =  $sanitizer->pageName($input->urlSegment4);

		return $urlSegments;

	}

	/**
	 * Determine menu items to render depending on context.
	 * Context is determined using URL segments.
	 *
	 * @access public
	 * @return array $menuItems Array of menu items to build navigation with.
	 *
	 */
	public function getMenuItems() {

		// check if in modal context
		$modalCheck = $this->modalCheck = $this->arrayInArrayCheck($this->urlSegments, array('add','rte','link'));

		// default menu items
		$menuItems = array(
			'all' => $this->_('All'),
			'audio' => $this->_('Audio'),
			'document' => $this->_('Document'),
			'image' => $this->_('Image'),
			'video' => $this->_('Video'),
			'upload' => $this->_('Upload'),
			'filters' => $this->_('Filters'),
			'settings' => $this->_('Settings'),
			'cleanup' => $this->_('Cleanup'),
		);

		################# START MENU ITEMS PRUNING CHECKS #################

		$savedSettings = $this->savedSettings;


		# modal context checks #

		// if in a modal (add,rte,link)
		if($modalCheck) {

			// here, always unset cleanup and filters
			unset($menuItems['filters'], $menuItems['cleanup']);

			// rte mode
			if('rte' == $this->urlSeg1) {
				// in rte, prune all menu items except for 'image', 'upload', 'settings'
				unset($menuItems['all'], $menuItems['audio'], $menuItems['document'], $menuItems['video']);
			}
			// add/insert mode: here we only
			elseif('add' == $this->urlSeg1) {
				// get this media manager field disallowed media
				$mmFieldDisallowedMedia = $this->getMediaManagerFieldDisallowedMedia();
				// prune them from menu items
				$menuItems = array_diff_key($menuItems, $mmFieldDisallowedMedia);
				// if only 1 media allowed, no need for 'all' menu item
				if(3 == count($mmFieldDisallowedMedia)) unset($menuItems['all']);
			}

		}// END if in modal

		# global checks #

		// get global disallowed media
		$globalMMDisallowedMedia = $this->getMediaManagerGlobalDisallowedMedia();
		// prune global disallowed media from menu items
		$menuItems = array_diff_key($menuItems, array_flip($globalMMDisallowedMedia));
		// if only 1 media allowed globally OR 'all' media view disabled, prune 'all' from menu items
		if((3 == count($globalMMDisallowedMedia)) || ($this->disableAllMediaView)) unset($menuItems['all']);
		// if filter profiles should not be shown, prune 'filters' from menu items
		if(isset($savedSettings['show_filter_profiles'][0]) && $savedSettings['show_filter_profiles'][0] == 2) unset($menuItems['filters']);
		// if user not allowed to upload, prune 'upload' from menu items
		if ($this->noUpload) unset($menuItems['upload']);
		// if user non-superuser, prune 'cleanup' from menu items
		if(!$this->wire('user')->isSuperuser()) unset($menuItems['cleanup']);


		return $menuItems;

	}

	/**
	 * Determines the current menu item for renderMenu().
	 *
	 * Based on url segments.
	 * Using this, we check only once per page view rather than in loop for all menu item.
	 *
	 * @access public
	 * @return string $on The current menu item.
	 *
	 */
	public function getCurrentMenuItem() {

		$on = 'all';

		// url segment 3
		if($this->urlSeg3) {
			# scenario: /media-manager/add/1138-118/audio/, /media-manager/add/1138-118/settings/, etc.
			$on = $this->urlSeg3;
		}
		// url segment 2
		elseif($this->urlSeg2) {
			# scenarios: /media-manager/link/audio/, /media-manager/rte/image/, /media-manager/rte/settings/, etc
			// ignore if in 'add' since currentPageID & mediaManagerFieldID are url segment 2 there
			if('add'!=$this->urlSeg1) $on = $this->urlSeg2;
		}
		// url segment 1
		elseif($this->urlSeg1) {
			# scenarios: /media-manager/link/, /media-manager/document/, /rte/settings/, /media-manager/image/, etc
			// @note: ignore if in 'link'. We don't bother about 'rte' since in that mode we will always have urlSeg2, hence will be caught in condition 2 above
			if('link'!=$this->urlSeg1) $on = $this->urlSeg1;
		}

		// else
		//@note: covered by default meaning: we are either in 'all' (no urlsegment) or urlsegment 2 = link, in which case, default to 'all'

		return $on;

	}

	/**
	 * Get the disallowed media for a specified media manager field.
	 *
	 * A blank array means all media are allowed.
	 *
	 * @access private
	 * @return array $fieldDisAllowedMedia Disallowed media for this media manager field.
	 *
	 */
	private function getMediaManagerFieldDisallowedMedia() {

		$fieldAllowedMedia = array();
		$fieldDisAllowedMedia = array();

		// get the field
		$f = $this->wire('fields')->get((int)$this->mediaManagerField->id);
		if($f && $f->id) {
			if(is_array($f->allowedMedia))  {
				$fieldAllowedMedia = $f->allowedMedia;
				// assoc array with all media in format mediaStr => mediaInt for comparison
				$media = array(1=>'audio', 2=>'document', 3=>'image', 4=>'video');
				/* @note:
					- in the field, allowed values are saved as integer-indexed mediaInts (0=>1,1=>2, etc)
					- we flip those to become mediaInt => index array
					- we then reduce the array to return differences, i.e. return disallowed media
					- for later array_diff_key where the key is mediaStr, we flip the final disallowed media array
				*/
				$fieldDisAllowedMedia = array_flip(array_diff_key($media, array_flip($fieldAllowedMedia)));
			}
		}

		return $fieldDisAllowedMedia;

	}

	/**
	 * Get the global disallowed media.
	 *
	 * A blank array means all media are allowed globally.
	 *
	 * @access private
	 * @return array $globalDisallowedMedia Global disallowed media.
	 *
	 */
	public function getMediaManagerGlobalDisallowedMedia() {
		$media = array('audio', 'document', 'image', 'video');
		$globalDisallowedMedia = array_diff($media, $this->globalAllowedMedia);
		return $globalDisallowedMedia;
	}

	/**
	 * Determine bulk actions items to render depending on context.
	 * Context is determined using URL segments.
	 *
	 * @access public
	 * @return array $actions Array of bulk actions items to build actions select with.
	 *
	 */
	public function getBulkActionItems() {

		// default bulk actions
		$actions = array(
			'publish' => $this->_('Publish'),
			'unpublish' => $this->_('Unpublish'),
			'lock' => $this->_('Lock'),
			'unlock' => $this->_('Unlock'),
			'tag' => $this->_('Tag'),
			'untag' => $this->_('Remove Tags'),
			'trash' => $this->_('Trash'),
			'delete' => $this->_('Delete'),
		);

		################# START BULK ACTIONS PRUNING CHECKS #################

		$showActionsButtonCheck = $this->arrayInArrayCheck($this->urlSegments, array('add', 'link', 'rte', 'filters', 'upload','settings','cleanup'));

		// don't show action button
		if($showActionsButtonCheck) $actions = array();
		// show action button
		else {
			// @access-control
			// check publish permission
			if($this->noPublish) unset($actions['publish'], $actions['unpublish']);
			// check lock permission
			if($this->noLock) unset($actions['lock'], $actions['unlock']);
			// check trash/delete permission
			if($this->noDelete) unset($actions['trash'], $actions['delete']);
		}

		return $actions;

	}

	/**
	 * Get the published and locked status of a given page.
	 *
	 * Use this to return icon representing the status.
	 *
	 * @param Page $page The page whose status to check.
	 * @param integer $mode Whether to check published (1) vs locked (2) status.
	 * @return bool $status Whether media is published/locked.
	 *
	 */
	public function getMediaStatus($page, $mode) {
		$status = false;
		if(1 == $mode && $page->is(Page::statusUnpublished)) $status = true;
		elseif(2 == $mode && $page->is(Page::statusLocked)) $status = true;
		return $status;
	}

	/**
	 * Build URL for modals used in the module.
	 *
	 * @access public
	 * @param string $segMent Whether to format an 'edit' versus 'add' URL.
	 * @param integer $mode Whether to return &id= in URL Parameter String as required if 'editing' media.
	 * @return string $url The formatted modal URL.
	 *
	 */
	public function getFormattedMediaModalURL($segMent = 'edit', $mode=1) {

		/*
			@note:
			- $segMent can be 'edit' or 'add'
			- 'edit' is for editing a media item: this can be opened directly from InputfieldMM; Or from INSERT/ADD modal opened by InputfieldMM; or when in ProcessMM.
			- The only difference between above is that in ProcessMM, $this->currentPage and $this->mediaManagerField will be blank WireData
			- In InputfieldMM, we check if the page being edited is already part of the MM field (in which case the parent window, i.e. the InputfieldMM is updated using Ajax) OR NOT (in which case, we do not send an ajax request to update the parent window)
		*/

		// if in 'add/insert' mode, we need this for the backlink
		$add = 'add' == $this->urlSeg1 && 'edit' == $segMent ?  "/add-{$this->urlSeg3}" : '';

		$url = $this->wire('config')->urls->admin . "media-manager/{$segMent}/";
		// add currentPage->id & mediaManagerField->id segment {combining IDs of page and field ids}
		$url .= (int) $this->currentPage->id . '-' . (int) $this->mediaManagerField->id . $add;
		// add modal context AND optionally id=
		$url .= 1 == $mode ? "/?modal=1&id=" : "/?modal=1";

		return $url;
	}

	/**
	 * Returns array of available custom columns for use in MediaManagerRender::customColumnsSettings().
	 *
	 * @note: This is just an array that will determine what media types will be available for setting their custom columns.
	 * Global disallowed media types will not be rendered for this setting.
	 *
	 * @access public
	 * @return array $defaultCustomColumns Array of allowed media name=>label pairs.
	 *
	 */
	public function getSettingsDefaultCustomColumns() {

		$defaultCustomColumns = array(
			'audio' => $this->_('Audio'),
			'document' => $this->_('Document'),
			'image' => $this->_('Image'),
			'video' => $this->_('Video')
		);

		$defaultCustomColumns = array_intersect_key($defaultCustomColumns, array_flip($this->globalAllowedMedia));

		return $defaultCustomColumns;
	}

	/**
	 * Prepare array of saved global custom columns id=>label pairs.
	 *
	 * Subsequently used for displaying or returning data for additional fields in media pages.
	 * Used in MediaManagerRender for Thumbs View: Table.
	 * Used in MediaManagerUtilities::buildPageAjaxData() for returning data for JSON repsonse to ajax calls for added and edited media.
	 *
	 * @access public
	 * @return array $customColumnsArray Array of field id=>label pairs.
	 *
	 */
	public function getCustomColumns() {
		$customColumns = array();
		$savedCustomColumns = isset($this->savedSettings['custom_columns']) ? $this->savedSettings['custom_columns'] : array();
		// prune global disallowed media
		$savedCustomColumns = array_intersect_key($savedCustomColumns, array_flip($this->globalAllowedMedia));
		if(count($savedCustomColumns)) {
			$mediaContext = $this->getMediaContext();// @note: string
			// @note: 1-D array of field_id => field_label pairs
			$customColumns = $this->getMediaContextCustomColumns($savedCustomColumns, $mediaContext);
		}
		return $customColumns;
	}

	/**
	 * Prepare array of saved custom columns id=>label pairs for a specified Media Manager field.
	 *
	 * Subsequently used for displaying or returning data for additional fields in media pages
	 * @note: This is used when the field is set to show custom fields as specified in the field (i.e., not global!).
	 * Used in MediaManagerRender for Thumbs View: Table.
	 * Used in MediaManagerUtilities::buildPageAjaxData() for returning data for JSON repsonse to ajax calls for added and edited media.
	 *
	 * @access public
	 * @param MediaManagerArray $mediaManagerField Media Manager Field for an Inputfield Media Manager.
	 * @return array $customColumnsArray Array of field id=>label pairs.
	 *
	 */
	public function getCustomColumnsForMediaManagerField($mediaManagerField) {

		$customColumns = array();
		$skipDuplicates = array();
		$media = array(1=>'audio', 2=>'document', 3=>'image', 4=>'video');

		foreach ($media as $name) {
			$customColumn = $mediaManagerField->{"{$name}CustomColumns"};
			if(!is_array($customColumn)) continue;
			foreach($customColumn as $id) {
				if(in_array($id, $skipDuplicates)) continue;
				$field = $this->wire('fields')->get((int) $id);
				if(!$field) continue;
				$customColumns[$field->id] = $field->get('label|name');
				$skipDuplicates[] = $field->id;
			}
		}

		return $customColumns;

	}

	/**
	 * Get values of specified fields for the given media page.
	 *
	 *
	 * @access public
	 * @param array $customColumns Array with IDs of fields to use as custom columns.
	 * @return array $customColumnsArray Array of field id=>label pairs.
	 *
	 */
	public function getCustomColumnsValues($page, $customColumns, $mode=1) {

		$customColumnsValues = array();
		$out = '';

		// @note: for now, we use  $fieldID and it still works! so $page->$fieldID works. We leave it like this for now
		//foreach($customColumns as $fieldName => $fieldLabel) {
		foreach($customColumns as $fieldID => $fieldLabel) {

			// check if items are iterable
			$iterable = WireArray::iterable($page->$fieldID) ? true : false;
			$values = '';

			// implode iterables
			if($iterable) {
				$values .= $page->$fieldID->implode(', ', function($item) {
					return $item->get('title|name');
				  }
				);
			}
			// single page field
			elseif($page->$fieldID instanceof Page) $values .= $page->$fieldID->title;
			// normal single value field
			else $values .= $page->getFormatted($fieldID);


			// inputfield mode
			if(1 == $mode) $out .= '<td>' . rtrim($values, ', ') . '</td>';
			// ajax-response-modal-mode
			else $customColumnsValues[$fieldID] = rtrim($values, ', ');
		}
		// for ajax response
		if(2 == $mode) $out = $customColumnsValues;

		return $out;

	}

	/**
	 * Determines the current media context.
	 *
	 * This can be viewing 'all', 'audio', 'document', 'image' or 'video' media.
	 *
	 * @access private
	 * @return string $mediaContext The media context we are in.
	 *
	 */
	private function getMediaContext() {

		$mediaContextsArray = array('audio','document','image','video');
		$count = $this->arrayInArrayCheck($this->getURLSegments(), $mediaContextsArray);

		// we are in 'all' view
		if(!$count) $mediaContext = 'all';
		// image context
		elseif($this->arrayInArrayCheck($this->getURLSegments(), array('image'))) $mediaContext = 'image';
		// audio context
		elseif($this->arrayInArrayCheck($this->getURLSegments(), array('audio'))) $mediaContext = 'audio';
		// document context
		elseif($this->arrayInArrayCheck($this->getURLSegments(), array('document'))) $mediaContext = 'document';
		// video context
		elseif($this->arrayInArrayCheck($this->getURLSegments(), array('video'))) $mediaContext = 'video';

		return $mediaContext;

	}

	/**
	 * Returns the custom columns depending on the current media context.
	 *
	 * Contexts are: viewing 'all', 'audio', 'document', 'image' or 'video' media.
	 *
	 * @access private
	 * @param array $savedCustomColumns  2D array of saved custom columns to process.
	 * @param string $mediaContext The media context we are in.
	 * @return array $customColumns Array of custom columns to show for the given media context.
	 *
	 */
	private function getMediaContextCustomColumns($savedCustomColumns, $mediaContext) {

		$customColumns = array();
		$index = "{$mediaContext}";
		$skipDuplicates = array();

		// we are in 'all' view
		if($mediaContext == 'all') {
			foreach ($savedCustomColumns as $key => $value) {
				foreach ($value as $v) {
					if(in_array($v, $skipDuplicates)) continue;
					$field = $this->wire('fields')->get((int) $v);
					if(!$field) continue;
					// @todo: for ML? will be tricky?!
					#$templates->get('media-manager-template')->fields->getFieldContext('body')->$labelLang
					$customColumns[$field->id] = $field->get('label|name');
					$skipDuplicates[] = $field->id;
				}
			}
		}
		// either of audio/document/image/video contexts
		elseif(isset($savedCustomColumns[$index])) {
			$values = $savedCustomColumns[$index];
			foreach ($values as $v) {
				if(in_array($v, $skipDuplicates)) continue;
				$field = $this->wire('fields')->get((int) $v);
				if(!$field) continue;
				#$customColumns[$field->id] = $field->getFieldContext('body')->$labelLang;
				$customColumns[$field->id] = $field->get('label|name');
				$skipDuplicates[] = $field->id;
			}
		}

		return $customColumns;

	}

	/**
	 * Return custom fields in a media manager template.
	 *
	 * We skip native media manager fields.
	 *
	 * @access public
	 * @param string $templateName The name of the template to traverse.
	 * @return array $templateFields Array of selectable fields in the given media manager template.
	 *
	 */
	public function getTemplateFields($templateName) {

		// some field types should not selectable in the asmSelect or we don't allow them for security
		$allowedFieldTypes = $this->allowedFieldTypes();
		// we don't need to return these fields even though they are compatible fields
		$disallowedFields = $this->disallowedFields();

		$templateFields = array();
		$templateName = $this->wire('sanitizer')->pageName($templateName);

		if(is_string($templateName)) {
			$template = $this->wire('templates')->get($templateName);
			if($template) {
				$fields = $template->fields->sort('label, name');
				foreach($template->fields as $field) {
					// @note: using strrchr to account for namespaced classes
					$baseClass = substr(strrchr('\\'.get_class($field->type), '\\'), 1);
					if (!in_array($baseClass, $allowedFieldTypes)) continue;
					// @note: includes Media Manager fields, Permissions and reserved names for MM properties (e.g. type)
					if(in_array($field->name, $disallowedFields)) continue;
					$templateFields[$field->id] = $field->get('label|name');
					#$templateFields[$field->name] = $field->get('label|name');// @note: using name instead
				}
			}

		}

		if(!count($templateFields)) $templateFields[0] = $this->_('No Custom Fields Found');
		return $templateFields;

	}

	/**
	 * Check if given results are a Page or PageArray.
	 *
	 * If Page, we add to a PageArray and return that.
	 * If a PageArray, we just return it.
	 *
	 * @access public
	 * @param object $pages The object to check if a PageArray.
	 * @return object $pages PageArray.
	 *
	 */
	public function getPageArray($pages) {
		if(!$pages instanceof PageArray) {
			$page = $pages;
			$pages = new PageArray();
			if($page && $page->id) $pages->add($page);
		}
		return $pages;
	}

	/**
	 * Return or compute the an image media's thumb's width based on desired height;
	 *
	 * Desired height before halving is 260px.
	 * This is halved to 130px later on.
	 * In case the width of am inmage at the height of 260px did not compute correctly.
	 * We compute the proportionate width here.
	 *
	 * @access private
	 * @param Pageimage $media Image object to set thumb width and thumb url properties
	 * @return Pageimage $media Image object with set thumb and url properties.
	 *
	 */
	private function getImageThumb($media) {

		set_time_limit(60);
		$imageThumb = $media->height(260);
		/*
			if for some reason thumb is returning original width, we compute it down
			original width * desired thumb height / original height
			desired thumb height = 130 (harcoded in MediaMangerRender::renderThumbSource())
		*/
		// Width not OK: Compute it
		// @note: we don't want floats
		if((int)$imageThumb->height > 260) $thumbWidth = ceil(($media->width * 130) / $media->height);
		// Width OK: Get it (halve-it to match halved image height (260/2=130))
		else  $thumbWidth = ceil($imageThumb->width / 2);// @note: hardcoded for now
		// remove trailing zeroes
		$media->thumbWidth = (int) $thumbWidth;
		//	 add thumb URL
		$media->thumbURL = $imageThumb->url;


		return $media;

	}
	/**
	 * Set media mime type and optionally media previewURL if using generic preview image.
	 *
	 * @access private
	 * @param Pageimage|PageFile $media The image|file object to set mimetype and previewURL properties to.
	 * @return Pageimage|PageFile $media Object with extra properties set.
	 *
	 */
	private function getMediaMimeType($media) {

		$commonExts = $this->commonExtensions();
		$commonMediaTypeMIMEs = $this->commonMediaTypeMIMEs();// @note: 2D-array

		// media MIME type for gallery (e.g. audio/mpeg)

		$mediaMIMEType = $commonMediaTypeMIMEs[$media->mediaTypeStr];
		$media->mimeType = isset($mediaMIMEType[$media->ext]) ? $mediaMIMEType[$media->ext] : 'image/jpeg/';

		if(isset($mediaMIMEType[$media->ext])) {
			$media->mimeType = $mediaMIMEType[$media->ext];
		}
		else {
			$media->mimeType = 'image/jpeg/';
			// @note: if here, it means we will use a generic preview background
			$media->previewURL = $this->genericPreviewImage($media->mediaTypeStr);
		}

		return $media;

	}


	/* ######################### - SETTERS - ######################### */


	/**
	 * Set extra properties to given object.
	 *
	 * Object can be a pagefile or pageimage representing a Media Manager media.
	 *
	 * @param Pageimage|PageFile $media The image|file object to set extra properties to.
	 * @return Pageimage|PageFile $media Object with extra properties set.
	 *
	 */
	public function setMediaProperties($media) {

		$pages = $this->wire('pages');
		$mediaID = $media->page->id;

		// multilingual environments
		$language = $this->wire('user')->language; // save the current user's language
		if($language != null && method_exists($pages->get($mediaID)->title, 'getLanguageValue')) $mediaTitle = $pages->get($mediaID)->title->getLanguageValue($language);// title of each PW page in this array
		//else $mediaTitle = $pages->get($mediaID)->title;// title of the pw page that houses the media
		else $mediaTitle = $media->page->title;// title of the pw page that houses the media

		$commonExts = $this->commonExtensions();
		$config = $this->wire('config');
		$class = '';

		$mediaTypeStr = $media->mediaTypeStr;

		## set a couple of properties for convenience later ##
		$media->id = $media->page->id;
		// @note: just to make our work easier down the line
		$media->title = $mediaTitle;// @note: just to make our work easier down the line
		// media short title to use in tooltip or under media thumb (@note: if length > 30, shorten to 26)
		$media->shortTitle = $this->truncateString($media->title, 26, 30);
		$media->noDescription = 0;
		if(!$media->description) {
			$media->description = $this->_('No Description');
			$media->noDescription = 1;
		}
		if(!$media->tags) {
			$media->tags = $this->_('No Tags');
			$media->noTags = 1;
		}
		// media status
		// published status
		$media->unpublished = $this->getMediaStatus($media->page, 1);
		// locked status
		$media->locked = $this->getMediaStatus($media->page, 2);
		$media->mediaStatus = $media->unpublished ? '<span class="mm_status fa fa-eye-slash"></span>' : '';
		$media->mediaStatus .= $media->locked ? '<span class="mm_status fa fa-lock"></span>' : '';

		// media type as a integer: audio, document, image, video
		$media->mediaTypeInt = $media->type ? $media->type : $this->mediaTypeInt($mediaTypeStr);

		// check if in field if in insert/add mode
		if('add' == $this->urlSeg1) {
			// if pages already in field, for applying CSS class
			$mediaManagerFieldName = $this->mediaManagerField->name;
			$media->inField = 0;
			if($this->currentPage && $this->currentPage->id > 0) {
				/*
					@note:
					- for images, we also want to catch variations of the image
					- we get the images extension to remove from the final name we pass to the selector
					- we also need to get rid of the . before the extension
					- there's no risk to get wrong results since we are also matching by id (of the page with the media)
				*/
				$ext = "." . $media->ext();
				$name = str_ireplace($ext,'',$media->basename);
				$selector = $mediaTypeStr == 'image' ?  "id=$media->id, media.basename*=$name" : 'id='. $media->id;
				if($this->currentPage->$mediaManagerFieldName->has($selector)) $media->inField = 1;
			}

		}

		// media usage count
		$cnt = $this->mediaUsageCount($media->page->id);
		$media->usedCnt = sprintf(_n("%d time", "%d times", $cnt), $cnt);

		// preview URL for use in Gallery
		// @note: for consistency: used by gallery preview
		// we will override this for some non-image media types below
		$media->previewURL = $media->url;

		// sets $media->mimeType AND amends $media->previewURL if using generic preview image
		$media = $this->getMediaMimeType($media);

		// image media thumb
		if($media->mediaTypeStr == 'image') {
			// get image media thumb at desired height
			$media = $this->getImageThumb($media);
			$media->dimensions = "{$media->width}&times;{$media->height}";
			$media->variationsCnt = $this->imageVariationsCount($media->page);
			$this->wire('pages')->uncacheAll(); // free some memory
		}

		// audio,document,video thumbs
		else {
			// custom thumb
			$media->thumbWidth = 130;// @note: hardcoded for now
			// @todo: test!
			if(in_array($media->ext, $commonExts))$media->thumbURL = $this->customThumb($media->ext);
			// generic thumb
			else $media->thumbURL = $this->genericThumb($media->mediaTypeStr);
		}

		return $media;

	}


	/* ######################### - BUILDERS - ######################### */

	/**
	 * Build data to return as ajax response for MediaManagerActions::actionEditSingleMedia and ::actionInsert.
	 *
	 * @access public
	 * @param Page $currentPage Page that contains the Media Manager field.
	 * @param object $mediaManagerField The media manager field on the currentPage.
	 * @param Page $mediaPage Page where the media lives and is currently being edited in a modal.
	 * @param array  $options Options to determine actions here.
	 * @return array $pageAjaxData Data to pass back as JSON in response to Ajax call.
	 *
	 */
	public function buildPageAjaxData(Page $currentPage, $mediaManagerField, Page $mediaPage, $options) {

		$mediaTypeInt = $options['media_type_int'];
		$action = $options['action'];

		$mediaTypeStr = $this->mediaTypeStr($mediaTypeInt);
		$genericTags = $this->_('No Tags');
		$usedCount = $this->mediaUsageCount($mediaPage->id);

		/*
			@note: we need to reset currentPage->id and mediaManagerField->id here otherwise getFormattedModalURL is picking these up from ProcessMM where, since we are in modal context, the respective values are 0!
		*/
		$this->currentPage->id  = $currentPage->id;
		$this->mediaManagerField->id = $mediaManagerField->id;

		$customColumns = array();

		$showColumns = (int) $mediaManagerField->showColumns;

		// get global columns for allowed media (for both ProcessMM and InputfieldMM)
		if(1 == $showColumns) {
			$showCustomColumns = $this->getCustomColumns();
			$customColumns = $this->getCustomColumnsValues($mediaPage, $showCustomColumns,2);
		}
		// get MM field's columns for allowed media (for InputfieldMM)
		elseif(2 == $showColumns) {
			$showCustomColumns = $this->getCustomColumnsForMediaManagerField($mediaManagerField);
			$customColumns = $this->getCustomColumnsValues($mediaPage, $showCustomColumns,2);
		}


		// similar to use in MediaManagerRender::renderThumbsGrid, set extra properties but for returning as JSON
		$fieldName = "media_manager_{$mediaTypeStr}";
		// @note: if dealing with an image, we could be dealing with its variation (i.e. $mediaTypeInt is 3x [e.g. 31])
		// hence, need to check for that first
		// image (variation of an original)
		if(strlen($mediaTypeInt) > 1 && (int)substr($mediaTypeInt, 0, 1) === 3) {
			//$fieldName = 'media_manager_image';
			$version = (int) substr($mediaTypeInt, 1);
			$media = $mediaPage->media_manager_image->eq($version);
		}
		else $media = $mediaPage->$fieldName->first();

		// @todo if no media, we need to return something? blank array? generic array with properties without values? + set action as 'remove'

		$media->mediaTypeStr = $mediaTypeStr;// @
		$media->type = $mediaTypeInt;
		$media = $this->setMediaProperties($media);

		// @note: although a media may be editable to the current user, it may be locked for edits! i.e, needs unlocking first. In that case, its status should be 'not editable'! Hence, we do not display and 'Edit' label nor an edit URL
		$editable = !$media->locked ? $options['editable'] : 0;
		$selectedMediaEditURL = $editable ? $this->getFormattedMediaModalURL() . $mediaPage->id : '';

		$pageAjaxData = array (
			"mediaID" => $media->id,
			"customColumns" => $customColumns,
			// @note: if no description, we already set a 'No Description' text in setMediaProperties()!
			"description" => $media->description,
			// @note:helps in JS for grid view tooltip rather than check in description since that will always have text
			"noDescription" => $media->noDescription,
			"dimensions" => ('image' == $mediaTypeStr ? "{$media->width}&times;{$media->height}" : ''),
			"editable" => $editable,
			"editURL" => $selectedMediaEditURL,
			"filename" => $media->basename,
			"filesize" => $media->filesizeStr,
			"locked" => ($media->locked ? 1 : 0),
			// @note: moved to actionInsert to set conditionally based on action
			//"editMediaID" => $media->id,
			"mediaTypeInt" => $mediaTypeInt,
			"mediaTypeStr" => $mediaTypeStr,
			"originalHeight" => ('image' == $mediaTypeStr ? $media->height : ''),
			"originalWidth" => ('image' == $mediaTypeStr ? $media->width : ''),
			"previewURL" => $media->previewURL,
			"shortTitle" => $media->shortTitle,
			"tags" => $media->tags,
			"thumbURL" => $media->thumbURL,
			"thumbWidth" => $media->thumbWidth,
			"title" => $media->title,
			"type" => $media->mimeType,
			"unpublished" => ($media->unpublished ? 1 : 0),
			"url" => $media->url,
			//"httpUrl" => $media->httpUrl,
			"usedCount" => sprintf(_n("%d time", "%d times", $usedCount), $usedCount),
			// @todo: all we need is $media->pagefiles->count()? OR?
			"variations" => ('image' == $mediaTypeStr ? $this->imageVariationsCount($media->page) : '')
		);

		return $pageAjaxData;

	}

	/**
	 * Count the number of times a media item has been used across all pages.
	 *
	 * These are non-RTE counts.
	 * We use raw sql for the count.
	 *
	 * @access public
	 * @param integer $mediaID ID of a media item.
	 * @return integer $usedCnt The number of times a media item is in use.
	 *
	 */
	public function mediaUsageCount($mediaID) {

		$mmFields = '';// will hold names of media manager fields in this site
		$fmm = 'FieldtypeMediaManager';
		$name = 'name';// limit results to this column. This will return the names of the fiels (e.g. 'media')
		$cnt = '';// used to count number of times media item used per field type of FieldtypeMediaManager
		$usedCnt = 0;// for count of total times media item used

		$database = $this->wire('database');
		$table = $database->escapeTable('fields');// this is the name of the table which lists all ProcessWire 'fields tables'

		// First we get all the tables of type FieldtypeMediaManager
		$sql = "SELECT $name FROM `$table` WHERE type=:type";// type is the column that holds the $field->type
		$query = $database->prepare($sql);// prepare statement
		$query->bindValue(":type", $fmm, \PDO::PARAM_STR);
		$query->execute();// execute select
		$mmFields = $query->fetchAll();// fetch the assoc array with the names of all found fields of type FieldtypeMediaManager

		// Secondly, we count the number of times the given media is used in ALL the above fields of type FieldtypeMediaManager
		// we now look in the tables of each of our found field names

		// $mmFields is 2D array, the inner of which is assoc and has an index with our column name from above, i.e. 'name'
		foreach ($mmFields as $f) {

			// the names of the tables of our found fields
			// ProcessWire prefixes all field tables with 'field_'
			// @note: $name here is the index we are after, i.e. $f['name']
			$mediaField = 'field_' . $f[$name];

			$table = $database->escapeTable($mediaField);

			// @note: we are oK with 'double counting' since media item could have crops + could be used in several fields
			$sql = "SELECT COUNT(*) FROM `$table` WHERE data=:data";// data is the column that holds $mediaID (i.e. the referenced media page ID)
			$query = $database->prepare($sql);// prepare statement
			$query->bindValue(":data", $mediaID, \PDO::PARAM_INT);
			$query->execute();// execute the count
			$cnt = $query->fetchColumn();//fetch the column count

			$usedCnt += (int) $cnt;// total it up

		}

		return $usedCnt;

	}

	/**
	 * Intersect an array to find out if at least one value in arr1 is present in arr2.
	 *
	 * @param array $arrayNeedle The array whose values we want to check if in $arrayHaystack.
	 * @param array $arrayHaystack The array to check if contains values from $arrayNeedle.
	 * @return integer $count Number of counts found.
	 *
	 */
	public function arrayInArrayCheck($arrayNeedle, $arrayHaystack) {
		$count = count(array_intersect($arrayNeedle, $arrayHaystack));
		return $count;
	}

	/**
	 * Check the media view we are in in ProcessMediaManager.
	 *
	 * There are 5 views we are interested in: 'All', 'Audio', 'Document', 'Image' and 'Video'.
	 * These can be in any segment. It doesn't matter.
	 *
	 * @access public
	 * @return integer $mediaView Denotes media view we are in.
	 *
	 */
	public function mediaViewCheck() {
		$mediaViewCheckArray = array('audio','document','image', 'video');
		$mediaView = array_intersect($this->getURLSegments(), $mediaViewCheckArray);
		$mediaView = implode("", $mediaView);
		return $mediaView;
	}

	/**
	 * Checks if the media being edited is in the given Media Manager field.
	 *
	 * @access public
	 * @param Page $editMedia The page where the media lives (i.e. in admin).
	 * @return bool $mediaInField Whether page is in the page field or no.
	 *
	 */
	public function checkMediaInField($editMedia) {
		$mediaManagerField = $this->mediaManagerField;
		$mediaInField = false;
		if($mediaManagerField->id && $this->currentPage->$mediaManagerField->has("id={$editMedia->id}")) $mediaInField = true;
		return $mediaInField;
	}

	/**
	 * Determines whether the 'insert button' for add media mode should be output.
	 *
	 * Add mode is called via a Media Manager Inputfield.
	 *
	 * @access public
	 * @return bool $showInsertButton. Whether to show button (true) or not (false).
	 *
	 */
	public function checkShowInsertButton() {
		$showInsertButton = false;
		// only show button when in add/insert mode
		$insertModeCheck = $this->arrayInArrayCheck($this->urlSegments, array('add'));
		if($insertModeCheck) {
			$noInsertButtonCheck = $this->arrayInArrayCheck($this->urlSegments, array('upload','settings','cleanup'));
			if(!$noInsertButtonCheck) $showInsertButton = true;
		}

		return $showInsertButton;
	}

	/**
	 * Check if a media manager field with limit on media count is full.
	 *
	 * @access public
	 * @return bool $mediaFieldFull. Whether field full or not.
	 */
	public function checkMediaFieldFull() {
		$mediaManagerField = $this->mediaManagerField;
		$mediaFieldFull = false;
		if((int)$mediaManagerField->maxFiles){
			// if max files of media allowed reached (@note: in order to not display 'add media' link)
			// @note: $mediaManagerField->currentFieldCnt >> overloaded property
			if((int) $mediaManagerField->currentFieldCnt >= $mediaManagerField->maxFiles) $mediaFieldFull = true;
		}
		return $mediaFieldFull;
	}

	/**
	 * Use RecursiveDirectoryIterator with a Generator in case memory is a huge issue.
	 *
	 * Used in by MediaManagerRender::renderFilesToScanList for
     * Generators provide an easy way to implement simple iterators without the overhead or complexity of implementing
     * a class that implements the Iterator interface.
	 * @credits: @Andrei: https://stackoverflow.com/a/46490165
	 *
	 * @access public
     * @see: http://php.net/manual/en/language.generators.overview.php
	 * @param string $path Path to iterate.
	 * @return void
	 *
	 */
	public function recursiveDirectoryIterator($path) {
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path)) as $file) {
            if (!$file->isDir()) {
                $humanReadableFileSize = $this->humanReadableFilesize($file->getSize());
                $filePath = $file->getPathname();
				$filename = $file->getFilename();
                yield "<td class='mm-scan-file-name' data-path='{$filePath}'>{$filename}</td><td>{$humanReadableFileSize}</td><td data-file='{$filename}'><input type='checkbox' class='uploaded_file mm_table_item toggle uk-checkbox uk-form-controls-text'></td>";
            }
        }
    }

    /**
     *  Return a human readable file size.
     *
     * @credits: rommel at rommelsantor dot com http://php.net/manual/en/function.filesize.php#106569
     *
     * @access public
     * @param [type] $bytes
     * @param integer $decimals
     * @return void
     */
    public function humanReadableFilesize($bytes, $decimals = 2) {
        $sz = 'BKMGTP';
        $factor = floor((strlen($bytes) - 1) / 3);
        $filesize = sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) .' '. @$sz[$factor];
        $filesize = str_replace('BB', 'B', $filesize . 'B');// @kongondo
        return $filesize;
    }

	/**
	 * Returns string showing allowed media types for this media manager field.
	 *
	 * @note: Allowed media types at field-level superseded by global allowed media setting.
	 *
	 * @access private
	 * @return string $allowedMediaTypesStr String of allowed media types.
	 *
	 */
	public function allowedMediaTypesStr() {

		$mediaManagerField = $this->mediaManagerField;

		/*
			@note:
				# Media Manager Field Allowed Media
				- in Media Manager Field, allowed media types saved in array like array(0=>1,1=>2), etc.
				- the indexes are meaningless for our purposes
				- hence we array_flip($mediaManagerField->allowedMedia) >>> array(1=>0, 2=>1)
				- we then intersect the keys with all allowed $mediaTypesInt to get only allowed translated media strings

				# Global Media Manager Allowed Media
				- in Global setting, allowed media types saved in array like array(0=>'audio',1=>'document'), etc.
				- the indexes are meaningless for our purposes
				- hence we array_flip($this->globalAllowedMedia) >>> array('audio'=>0, 'document'=>1)
				- we then intersect the keys with all allowed $mediaTypesStr to get only allowed translated media strings
		*/

		// get allowed media from Field
		if($mediaManagerField->allowedMedia != null && count($mediaManagerField->allowedMedia)) {
			// for field allowed media compliance
			$mediaTypesInt = array(
				1 => $this->_('audio'),
				2 => $this->_('document'),
				3 => $this->_('image'),
				4 => $this->_('video')
			);

			$allowedMediaTypesStr = implode(", ", array_intersect_key($mediaTypesInt, array_flip($mediaManagerField->allowedMedia)));

		}
		// get allowed media from Global
		else {
			// for global allowed media compliance
			$mediaTypesStr = array(
				'audio' => $this->_('audio'),
				'document' => $this->_('document'),
				'image' => $this->_('image'),
				'video' => $this->_('video')
			);

			$allowedMediaTypesStr = implode(", ", array_intersect_key($mediaTypesStr, array_flip($this->globalAllowedMedia)));
		}


		return $allowedMediaTypesStr;

	}

	/**
	 * Determines and sets if necessary, the active mode (view vs edit) for media.
	 *
	 * Gets cookie set in ProcessMediaManager.js to determine view/edit mode.
	 *
	 * @access public
	 * @return string $mode.
	 *
	 */
	public function mediaViewEditMode() {
		$mode = 'view';// default view
		$cookieName = 'media_manager_mode';
		$modeCookie = $this->wire('input')->cookie->$cookieName;// js cookie to remember thumbs' view for session
		if('view' === $this->wire('sanitizer')->pageName($modeCookie)) $mode = 'view';
		elseif('edit' === $this->wire('sanitizer')->pageName($modeCookie)) $mode = 'edit';
		return $mode;
	}

	/**
	 * Returns allowed mime_types for a media_manager_image field allowed file extensions.
	 *
	 * We pass these to jqueryFileUpload and actionCreateMedia().
	 * These are for validating both uploaded and scanned image files.
	 *
	 * @access public
	 * @return array $imageMimeTypes Allowed mime_types.
	 *
	 */
	public function allowedImageMimeTypes() {
		// @todo: refactor?
		$imageMimeTypes = array();
		// grab allowed image media/file extensions (array)
		$imageExts = $this->validExtensionsImage();
		// find corresponding mime_type(s) for each allowed image file extension
		foreach ($imageExts as $ext) $allowedImageMimeTypes[] = $this->mimeTypes($ext);
		foreach ($allowedImageMimeTypes as $key => $value) {
			foreach($value as $v ) $imageMimeTypes[] = $v;
		}
		return $imageMimeTypes;
	}

	/**
	 *Returns allowed mime_types for all non image media_manager_xxxx fields allowed file extensions.
	 *
	 * We pass these to jqueryFileUpload and actionCreateMedia().
	 * These are for validating both uploaded and scanned non-image files.
	 *
	 * @access public
	 * @return array $nonImageMimeTypes Allowed non-image mime_types
	 *
	 */
	public function allowedNonImageMimeTypes() {

		$nonImageMimeTypes = array();
		// check if unzipping files. If yes, add universally
		$zipFilesExt = $this->savedSettings['unzip_files'][0] == 1 ? array('zip') : array();

		// grab allowed non-image media/file extensions (array)
		$nonImageExts = array_merge($zipFilesExt, $this->validExtensionsAudio(), $this->validExtensionsDocument(), $this->validExtensionsVideo());

		// find corresponding mime_type(s) for each allowed non-image file extension
		foreach ($nonImageExts as $ext) $allowedNonImageMimeTypes[] = $this->mimeTypes($ext);

		foreach ($allowedNonImageMimeTypes as $key => $value) {
			foreach($value as $v ) $nonImageMimeTypes[] = $v;
		}

		return $nonImageMimeTypes;

	}

	/**
	 * Return array of allowed Fieldtypes for selection to show in media manager (Process and Fieldtype).
	 *
	 * @note: Disallowed fieldtypes can still be added to the templates and accessed from their MM fields via template files for frontend output.
	 *
	 * @access public
	 * @param integer $mode Denotes if to return FieldtypeImage
	 * @return array $allowedFieldTypes Array of allowed Fieldtypes.
	 *
	 */
	public function allowedFieldTypes($mode = 2) {
		$allowedFieldTypes = array ('FieldtypeDatetime', 'FieldtypeEmail', 'FieldtypeFile', 'FieldtypeFloat',  'FieldtypeInteger', 'FieldtypeOptions', 'FieldtypePage', 'FieldtypePageTitle', 'FieldtypePageTitleLanguage', 'FieldtypeText', 'FieldtypeTextLanguage', 'FieldtypeURL', );
		// @note: we might need this e.g. for for a document media, show its cover page
		if(2 == $mode) $allowedFieldTypes[] = 'FieldtypeImage';
		return $allowedFieldTypes;
	}

	/**
	 * Return array of disallowed field names for selection to show in media manager (Process and Fieldtype).
	 *
	 * @access public
	 * @return array $disallowedFields Array of disallowed fields.
	 *
	 */
	public function disallowedFields() {
		$mediaManagerFields = array('title', 'media_manager_audio', 'media_manager_document','media_manager_image','media_manager_video');
		$mediaManagerProperties = array('id', 'type', 'typeLabel', 'media');
		$processWireFields= array('permissions', 'roles');
		$disallowedFields= array_merge($mediaManagerFields, $mediaManagerProperties, $processWireFields);
		return $disallowedFields;
	}

	/**
	 * Return mime_types for a given file extension.
	 *
	 * Extensions are the index in the 2-D array of extensions => array('mime_types')
	 *
	 * @access public
	 * @see https://gist.github.com/nimasdj/801b0b1a50112ea6a997 @note we have modified these array with additional types.
	 * @param string $index File extension whose mime_type we wish to return.
	 * @return array $mimeType The corresponding mime_type(s) of the given file extension.
	 *
	 */
	public 	function mimeTypes($index = null) {

		$mimeType = array();

		$mime_types = array('3dm'=>array('x-world/x-3dmf'),'3dmf'=>array('x-world/x-3dmf'),'3dml'=>array('text/vnd.in3d.3dml'),'3ds'=>array('image/x-3ds'),'3g2'=>array('video/3gpp2'),'3gp'=>array('video/3gpp'),'7z'=>array('application/x-7z-compressed'),'a'=>array('application/octet-stream'),'aab'=>array('application/x-authorware-bin'),'aac'=>array('audio/x-aac'),'aam'=>array('application/x-authorware-map'),'aas'=>array('application/x-authorware-seg'),'abc'=>array('text/vnd.abc'),'abw'=>array('application/x-abiword'),'ac'=>array('application/pkix-attr-cert'),'acc'=>array('application/vnd.americandynamics.acc'),'ace'=>array('application/x-ace-compressed'),'acgi'=>array('text/html'),'acu'=>array('application/vnd.acucobol'),'acutc'=>array('application/vnd.acucorp'),'adp'=>array('audio/adpcm'),'aep'=>array('application/vnd.audiograph'),'afl'=>array('video/animaflex'),'afm'=>array('application/x-font-type1'),'afp'=>array('application/vnd.ibm.modcap'),'ahead'=>array('application/vnd.ahead.space'),'ai'=>array('application/postscript'),'aif'=>array('audio/aiff','audio/x-aiff'),'aifc'=>array('audio/aiff','audio/x-aiff'),'aiff'=>array('audio/aiff','audio/x-aiff'),'aim'=>array('application/x-aim'),'aip'=>array('text/x-audiosoft-intra'),'air'=>array('application/vnd.adobe.air-application-installer-package+zip'),'ait'=>array('application/vnd.dvb.ait'),'ami'=>array('application/vnd.amiga.ami'),'ani'=>array('application/x-navi-animation'),'aos'=>array('application/x-nokia-9000-communicator-add-on-software'),'apk'=>array('application/vnd.android.package-archive'),'appcache'=>array('text/cache-manifest'),'application'=>array('application/x-ms-application'),'apr'=>array('application/vnd.lotus-approach'),'aps'=>array('application/mime'),'arc'=>array('application/x-freearc'),'arj'=>array('application/arj','application/octet-stream'),'art'=>array('image/x-jg'),'asc'=>array('application/pgp-signature'),'asf'=>array('video/x-ms-asf'),'asm'=>array('text/x-asm'),'aso'=>array('application/vnd.accpac.simply.aso'),'asp'=>array('text/asp'),'asx'=>array('application/x-mplayer2','video/x-ms-asf','video/x-ms-asf-plugin'),'atc'=>array('application/vnd.acucorp'),'atom'=>array('application/atom+xml'),'atomcat'=>array('application/atomcat+xml'),'atomsvc'=>array('application/atomsvc+xml'),'atx'=>array('application/vnd.antix.game-component'),'au'=>array('audio/basic'),'avi'=>array('application/x-troff-msvideo','video/avi','video/msvideo','video/x-msvideo'),'avs'=>array('video/avs-video'),'aw'=>array('application/applixware'),'azf'=>array('application/vnd.airzip.filesecure.azf'),'azs'=>array('application/vnd.airzip.filesecure.azs'),'azw'=>array('application/vnd.amazon.ebook'),'bat'=>array('application/x-msdownload'),'bcpio'=>array('application/x-bcpio'),'bdf'=>array('application/x-font-bdf'),'bdm'=>array('application/vnd.syncml.dm+wbxml'),'bed'=>array('application/vnd.realvnc.bed'),'bh2'=>array('application/vnd.fujitsu.oasysprs'),'bin'=>array('application/mac-binary','application/macbinary','application/octet-stream','application/x-binary','application/x-macbinary'),'blb'=>array('application/x-blorb'),'blorb'=>array('application/x-blorb'),'bm'=>array('image/bmp'),'bmi'=>array('application/vnd.bmi'),'bmp'=>array('image/bmp','image/x-windows-bmp'),'boo'=>array('application/book'),'book'=>array('application/vnd.framemaker'),'box'=>array('application/vnd.previewsystems.box'),'boz'=>array('application/x-bzip2'),'bpk'=>array('application/octet-stream'),'bsh'=>array('application/x-bsh'),'btif'=>array('image/prs.btif'),'buffer'=>array('application/octet-stream'),'bz'=>array('application/x-bzip'),'bz2'=>array('application/x-bzip2'),'c'=>array('text/x-c'),'c++'=>array('text/plain'),'c11amc'=>array('application/vnd.cluetrust.cartomobile-config'),'c11amz'=>array('application/vnd.cluetrust.cartomobile-config-pkg'),'c4d'=>array('application/vnd.clonk.c4group'),'c4f'=>array('application/vnd.clonk.c4group'),'c4g'=>array('application/vnd.clonk.c4group'),'c4p'=>array('application/vnd.clonk.c4group'),'c4u'=>array('application/vnd.clonk.c4group'),'cab'=>array('application/vnd.ms-cab-compressed'),'caf'=>array('audio/x-caf'),'cap'=>array('application/vnd.tcpdump.pcap'),'car'=>array('application/vnd.curl.car'),'cat'=>array('application/vnd.ms-pki.seccat'),'cb7'=>array('application/x-cbr'),'cba'=>array('application/x-cbr'),'cbr'=>array('application/x-cbr'),'cbt'=>array('application/x-cbr'),'cbz'=>array('application/x-cbr'),'cc'=>array('text/plain','text/x-c'),'ccad'=>array('application/clariscad'),'cco'=>array('application/x-cocoa'),'cct'=>array('application/x-director'),'ccxml'=>array('application/ccxml+xml'),'cdbcmsg'=>array('application/vnd.contact.cmsg'),'cdf'=>array('application/cdf','application/x-cdf','application/x-netcdf'),'cdkey'=>array('application/vnd.mediastation.cdkey'),'cdmia'=>array('application/cdmi-capability'),'cdmic'=>array('application/cdmi-container'),'cdmid'=>array('application/cdmi-domain'),'cdmio'=>array('application/cdmi-object'),'cdmiq'=>array('application/cdmi-queue'),'cdx'=>array('chemical/x-cdx'),'cdxml'=>array('application/vnd.chemdraw+xml'),'cdy'=>array('application/vnd.cinderella'),'cer'=>array('application/pkix-cert','application/x-x509-ca-cert'),'cfs'=>array('application/x-cfs-compressed'),'cgm'=>array('image/cgm'),'cha'=>array('application/x-chat'),'chat'=>array('application/x-chat'),'chm'=>array('application/vnd.ms-htmlhelp'),'chrt'=>array('application/vnd.kde.kchart'),'cif'=>array('chemical/x-cif'),'cii'=>array('application/vnd.anser-web-certificate-issue-initiation'),'cil'=>array('application/vnd.ms-artgalry'),'cla'=>array('application/vnd.claymore'),'class'=>array('application/java','application/java-byte-code','application/x-java-class'),'clkk'=>array('application/vnd.crick.clicker.keyboard'),'clkp'=>array('application/vnd.crick.clicker.palette'),'clkt'=>array('application/vnd.crick.clicker.template'),'clkw'=>array('application/vnd.crick.clicker.wordbank'),'clkx'=>array('application/vnd.crick.clicker'),'clp'=>array('application/x-msclip'),'cmc'=>array('application/vnd.cosmocaller'),'cmdf'=>array('chemical/x-cmdf'),'cml'=>array('chemical/x-cml'),'cmp'=>array('application/vnd.yellowriver-custom-menu'),'cmx'=>array('image/x-cmx'),'cod'=>array('application/vnd.rim.cod'),'com'=>array('application/octet-stream','text/plain'),'conf'=>array('text/plain'),'cpio'=>array('application/x-cpio'),'cpp'=>array('text/x-c'),'cpt'=>array('application/x-compactpro','application/x-cpt'),'crd'=>array('application/x-mscardfile'),'crl'=>array('application/pkcs-crl','application/pkix-crl'),'crt'=>array('application/pkix-cert','application/x-x509-ca-cert','application/x-x509-user-cert'),'crx'=>array('application/x-chrome-extension'),'cryptonote'=>array('application/vnd.rig.cryptonote'),'csh'=>array('application/x-csh','text/x-script.csh'),'csml'=>array('chemical/x-csml'),'csp'=>array('application/vnd.commonspace'),'css'=>array('application/x-pointplus','text/css'),'cst'=>array('application/x-director'),'csv'=>array('text/csv'),'cu'=>array('application/cu-seeme'),'curl'=>array('text/vnd.curl'),'cww'=>array('application/prs.cww'),'cxt'=>array('application/x-director'),'cxx'=>array('text/x-c'),'dae'=>array('model/vnd.collada+xml'),'daf'=>array('application/vnd.mobius.daf'),'dart'=>array('application/vnd.dart'),'dataless'=>array('application/vnd.fdsn.seed'),'davmount'=>array('application/davmount+xml'),'dbk'=>array('application/docbook+xml'),'dcr'=>array('application/x-director'),'dcurl'=>array('text/vnd.curl.dcurl'),'dd2'=>array('application/vnd.oma.dd2+xml'),'ddd'=>array('application/vnd.fujixerox.ddd'),'deb'=>array('application/x-debian-package'),'deepv'=>array('application/x-deepv'),'def'=>array('text/plain'),'deploy'=>array('application/octet-stream'),'der'=>array('application/x-x509-ca-cert'),'dfac'=>array('application/vnd.dreamfactory'),'dgc'=>array('application/x-dgc-compressed'),'dic'=>array('text/x-c'),'dif'=>array('video/x-dv'),'diff'=>array('text/plain'),'dir'=>array('application/x-director'),'dis'=>array('application/vnd.mobius.dis'),'dist'=>array('application/octet-stream'),'distz'=>array('application/octet-stream'),'djv'=>array('image/vnd.djvu'),'djvu'=>array('image/vnd.djvu'),'dl'=>array('video/dl','video/x-dl'),'dll'=>array('application/x-msdownload'),'dmg'=>array('application/x-apple-diskimage'),'dmp'=>array('application/vnd.tcpdump.pcap'),'dms'=>array('application/octet-stream'),'dna'=>array('application/vnd.dna'),'doc'=>array('application/msword'),'docm'=>array('application/vnd.ms-word.document.macroenabled.12'),'docx'=>array('application/vnd.openxmlformats-officedocument.wordprocessingml.document'),'dot'=>array('application/msword'),'dotm'=>array('application/vnd.ms-word.template.macroenabled.12'),'dotx'=>array('application/vnd.openxmlformats-officedocument.wordprocessingml.template'),'dp'=>array('application/vnd.osgi.dp'),'dpg'=>array('application/vnd.dpgraph'),'dra'=>array('audio/vnd.dra'),'drw'=>array('application/drafting'),'dsc'=>array('text/prs.lines.tag'),'dssc'=>array('application/dssc+der'),'dtb'=>array('application/x-dtbook+xml'),'dtd'=>array('application/xml-dtd'),'dts'=>array('audio/vnd.dts'),'dtshd'=>array('audio/vnd.dts.hd'),'dump'=>array('application/octet-stream'),'dv'=>array('video/x-dv'),'dvb'=>array('video/vnd.dvb.file'),'dvi'=>array('application/x-dvi'),'dwf'=>array('drawing/x-dwf (old)','model/vnd.dwf'),'dwg'=>array('application/acad','image/vnd.dwg','image/x-dwg'),'dxf'=>array('image/vnd.dxf'),'dxp'=>array('application/vnd.spotfire.dxp'),'dxr'=>array('application/x-director'),'ecelp4800'=>array('audio/vnd.nuera.ecelp4800'),'ecelp7470'=>array('audio/vnd.nuera.ecelp7470'),'ecelp9600'=>array('audio/vnd.nuera.ecelp9600'),'ecma'=>array('application/ecmascript'),'edm'=>array('application/vnd.novadigm.edm'),'edx'=>array('application/vnd.novadigm.edx'),'efif'=>array('application/vnd.picsel'),'ei6'=>array('application/vnd.pg.osasli'),'el'=>array('text/x-script.elisp'),'elc'=>array('application/x-bytecode.elisp (compiled elisp)','application/x-elc'),'emf'=>array('application/x-msmetafile'),'eml'=>array('message/rfc822'),'emma'=>array('application/emma+xml'),'emz'=>array('application/x-msmetafile'),'env'=>array('application/x-envoy'),'eol'=>array('audio/vnd.digital-winds'),'eot'=>array('application/vnd.ms-fontobject'),'eps'=>array('application/postscript'),'epub'=>array('application/epub+zip'),'es'=>array('application/x-esrehber'),'es3'=>array('application/vnd.eszigno3+xml'),'esa'=>array('application/vnd.osgi.subsystem'),'esf'=>array('application/vnd.epson.esf'),'et3'=>array('application/vnd.eszigno3+xml'),'etx'=>array('text/x-setext'),'eva'=>array('application/x-eva'),'event-stream'=>array('text/event-stream'),'evy'=>array('application/envoy','application/x-envoy'),'exe'=>array('application/x-msdownload'),'exi'=>array('application/exi'),'ext'=>array('application/vnd.novadigm.ext'),'ez'=>array('application/andrew-inset'),'ez2'=>array('application/vnd.ezpix-album'),'ez3'=>array('application/vnd.ezpix-package'),'f'=>array('text/plain','text/x-fortran'),'f4v'=>array('video/x-f4v'),'f77'=>array('text/x-fortran'),'f90'=>array('text/plain','text/x-fortran'),'fbs'=>array('image/vnd.fastbidsheet'),'fcdt'=>array('application/vnd.adobe.formscentral.fcdt'),'fcs'=>array('application/vnd.isac.fcs'),'fdf'=>array('application/vnd.fdf'),'fe_launch'=>array('application/vnd.denovo.fcselayout-link'),'fg5'=>array('application/vnd.fujitsu.oasysgp'),'fgd'=>array('application/x-director'),'fh'=>array('image/x-freehand'),'fh4'=>array('image/x-freehand'),'fh5'=>array('image/x-freehand'),'fh7'=>array('image/x-freehand'),'fhc'=>array('image/x-freehand'),'fif'=>array('application/fractals','image/fif'),'fig'=>array('application/x-xfig'),'flac'=>array('audio/flac','audio/x-flac'),'fli'=>array('video/fli','video/x-fli'),'flo'=>array('application/vnd.micrografx.flo'),'flv'=>array('video/x-flv'),'flw'=>array('application/vnd.kde.kivio'),'flx'=>array('text/vnd.fmi.flexstor'),'fly'=>array('text/vnd.fly'),'fm'=>array('application/vnd.framemaker'),'fmf'=>array('video/x-atomic3d-feature'),'fnc'=>array('application/vnd.frogans.fnc'),'for'=>array('text/plain','text/x-fortran'),'fpx'=>array('image/vnd.fpx','image/vnd.net-fpx'),'frame'=>array('application/vnd.framemaker'),'frl'=>array('application/freeloader'),'fsc'=>array('application/vnd.fsc.weblaunch'),'fst'=>array('image/vnd.fst'),'ftc'=>array('application/vnd.fluxtime.clip'),'fti'=>array('application/vnd.anser-web-funds-transfer-initiation'),'funk'=>array('audio/make'),'fvt'=>array('video/vnd.fvt'),'fxp'=>array('application/vnd.adobe.fxp'),'fxpl'=>array('application/vnd.adobe.fxp'),'fzs'=>array('application/vnd.fuzzysheet'),'g'=>array('text/plain'),'g2w'=>array('application/vnd.geoplan'),'g3'=>array('image/g3fax'),'g3w'=>array('application/vnd.geospace'),'gac'=>array('application/vnd.groove-account'),'gam'=>array('application/x-tads'),'gbr'=>array('application/rpki-ghostbusters'),'gca'=>array('application/x-gca-compressed'),'gdl'=>array('model/vnd.gdl'),'geo'=>array('application/vnd.dynageo'),'gex'=>array('application/vnd.geometry-explorer'),'ggb'=>array('application/vnd.geogebra.file'),'ggt'=>array('application/vnd.geogebra.tool'),'ghf'=>array('application/vnd.groove-help'),'gif'=>array('image/gif'),'gim'=>array('application/vnd.groove-identity-message'),'gl'=>array('video/gl','video/x-gl'),'gml'=>array('application/gml+xml'),'gmx'=>array('application/vnd.gmx'),'gnumeric'=>array('application/x-gnumeric'),'gph'=>array('application/vnd.flographit'),'gpx'=>array('application/gpx+xml'),'gqf'=>array('application/vnd.grafeq'),'gqs'=>array('application/vnd.grafeq'),'gram'=>array('application/srgs'),'gramps'=>array('application/x-gramps-xml'),'gre'=>array('application/vnd.geometry-explorer'),'grv'=>array('application/vnd.groove-injector'),'grxml'=>array('application/srgs+xml'),'gsd'=>array('audio/x-gsm'),'gsf'=>array('application/x-font-ghostscript'),'gsm'=>array('audio/x-gsm'),'gsp'=>array('application/x-gsp'),'gss'=>array('application/x-gss'),'gtar'=>array('application/x-gtar'),'gtm'=>array('application/vnd.groove-tool-message'),'gtw'=>array('model/vnd.gtw'),'gv'=>array('text/vnd.graphviz'),'gxf'=>array('application/gxf'),'gxt'=>array('application/vnd.geonext'),'gz'=>array('application/x-compressed','application/x-gzip'),'gzip'=>array('application/x-gzip','multipart/x-gzip'),'h'=>array('text/plain','text/x-h'),'h261'=>array('video/h261'),'h263'=>array('video/h263'),'h264'=>array('video/h264'),'hal'=>array('application/vnd.hal+xml'),'hbci'=>array('application/vnd.hbci'),'hdf'=>array('application/x-hdf'),'help'=>array('application/x-helpfile'),'hgl'=>array('application/vnd.hp-hpgl'),'hh'=>array('text/plain','text/x-h'),'hlb'=>array('text/x-script'),'hlp'=>array('application/hlp','application/x-helpfile','application/x-winhelp'),'hpg'=>array('application/vnd.hp-hpgl'),'hpgl'=>array('application/vnd.hp-hpgl'),'hpid'=>array('application/vnd.hp-hpid'),'hps'=>array('application/vnd.hp-hps'),'hqx'=>array('application/binhex','application/binhex4','application/mac-binhex','application/mac-binhex40','application/x-binhex40','application/x-mac-binhex40'),'hta'=>array('application/hta'),'htc'=>array('text/x-component'),'htke'=>array('application/vnd.kenameaapp'),'htm'=>array('text/html'),'html'=>array('text/html'),'htmls'=>array('text/html'),'htt'=>array('text/webviewhtml'),'htx'=>array('text/html'),'hvd'=>array('application/vnd.yamaha.hv-dic'),'hvp'=>array('application/vnd.yamaha.hv-voice'),'hvs'=>array('application/vnd.yamaha.hv-script'),'i2g'=>array('application/vnd.intergeo'),'icc'=>array('application/vnd.iccprofile'),'ice'=>array('x-conference/x-cooltalk'),'icm'=>array('application/vnd.iccprofile'),'ico'=>array('image/x-icon'),'ics'=>array('text/calendar'),'idc'=>array('text/plain'),'ief'=>array('image/ief'),'iefs'=>array('image/ief'),'ifb'=>array('text/calendar'),'ifm'=>array('application/vnd.shana.informed.formdata'),'iges'=>array('application/iges','model/iges'),'igl'=>array('application/vnd.igloader'),'igm'=>array('application/vnd.insors.igm'),'igs'=>array('application/iges','model/iges'),'igx'=>array('application/vnd.micrografx.igx'),'iif'=>array('application/vnd.shana.informed.interchange'),'ima'=>array('application/x-ima'),'imap'=>array('application/x-httpd-imap'),'imp'=>array('application/vnd.accpac.simply.imp'),'ims'=>array('application/vnd.ms-ims'),'in'=>array('text/plain'),'inf'=>array('application/inf'),'ink'=>array('application/inkml+xml'),'inkml'=>array('application/inkml+xml'),'ins'=>array('application/x-internett-signup'),'install'=>array('application/x-install-instructions'),'iota'=>array('application/vnd.astraea-software.iota'),'ip'=>array('application/x-ip2'),'ipfix'=>array('application/ipfix'),'ipk'=>array('application/vnd.shana.informed.package'),'irm'=>array('application/vnd.ibm.rights-management'),'irp'=>array('application/vnd.irepository.package+xml'),'iso'=>array('application/x-iso9660-image'),'isu'=>array('video/x-isvideo'),'it'=>array('audio/it'),'itp'=>array('application/vnd.shana.informed.formtemplate'),'iv'=>array('application/x-inventor'),'ivp'=>array('application/vnd.immervision-ivp'),'ivr'=>array('i-world/i-vrml'),'ivu'=>array('application/vnd.immervision-ivu'),'ivy'=>array('application/x-livescreen'),'jad'=>array('text/vnd.sun.j2me.app-descriptor'),'jam'=>array('application/vnd.jam'),'jar'=>array('application/java-archive'),'jav'=>array('text/plain','text/x-java-source'),'java'=>array('text/plain','text/x-java-source'),'jcm'=>array('application/x-java-commerce'),'jfif'=>array('image/jpeg','image/pjpeg'),'jfif-tbnl'=>array('image/jpeg'),'jisp'=>array('application/vnd.jisp'),'jlt'=>array('application/vnd.hp-jlyt'),'jnlp'=>array('application/x-java-jnlp-file'),'joda'=>array('application/vnd.joost.joda-archive'),'jpe'=>array('image/jpeg','image/pjpeg'),'jpeg'=>array('image/jpeg','image/pjpeg'),'jpg'=>array('image/jpeg','image/pjpeg'),'jpgm'=>array('video/jpm'),'jpgv'=>array('video/jpeg'),'jpm'=>array('video/jpm'),'jps'=>array('image/x-jps'),'js'=>array('application/javascript'),'json'=>array('application/json','text/plain'),'jsonml'=>array('application/jsonml+json'),'jt'=>array('application/octet-stream'),'jut'=>array('image/jutvision'),'kar'=>array('audio/midi','music/x-karaoke'),'karbon'=>array('application/vnd.kde.karbon'),'kfo'=>array('application/vnd.kde.kformula'),'kia'=>array('application/vnd.kidspiration'),'kil'=>array('application/x-killustrator'),'kml'=>array('application/vnd.google-earth.kml+xml'),'kmz'=>array('application/vnd.google-earth.kmz'),'kne'=>array('application/vnd.kinar'),'knp'=>array('application/vnd.kinar'),'kon'=>array('application/vnd.kde.kontour'),'kpr'=>array('application/vnd.kde.kpresenter'),'kpt'=>array('application/vnd.kde.kpresenter'),'kpxx'=>array('application/vnd.ds-keypoint'),'ksh'=>array('application/x-ksh','text/x-script.ksh'),'ksp'=>array('application/vnd.kde.kspread'),'ktr'=>array('application/vnd.kahootz'),'ktx'=>array('image/ktx'),'ktz'=>array('application/vnd.kahootz'),'kwd'=>array('application/vnd.kde.kword'),'kwt'=>array('application/vnd.kde.kword'),'la'=>array('audio/nspaudio','audio/x-nspaudio'),'lam'=>array('audio/x-liveaudio'),'lasxml'=>array('application/vnd.las.las+xml'),'latex'=>array('application/x-latex'),'lbd'=>array('application/vnd.llamagraphics.life-balance.desktop'),'lbe'=>array('application/vnd.llamagraphics.life-balance.exchange+xml'),'les'=>array('application/vnd.hhe.lesson-player'),'lha'=>array('application/lha','application/octet-stream','application/x-lha'),'lhx'=>array('application/octet-stream'),'link66'=>array('application/vnd.route66.link66+xml'),'list'=>array('text/plain'),'list3820'=>array('application/vnd.ibm.modcap'),'listafp'=>array('application/vnd.ibm.modcap'),'lma'=>array('audio/nspaudio','audio/x-nspaudio'),'lnk'=>array('application/x-ms-shortcut'),'log'=>array('text/plain'),'lostxml'=>array('application/lost+xml'),'lrf'=>array('application/octet-stream'),'lrm'=>array('application/vnd.ms-lrm'),'lsp'=>array('application/x-lisp','text/x-script.lisp'),'lst'=>array('text/plain'),'lsx'=>array('text/x-la-asf'),'ltf'=>array('application/vnd.frogans.ltf'),'ltx'=>array('application/x-latex'),'lua'=>array('text/x-lua'),'luac'=>array('application/x-lua-bytecode'),'lvp'=>array('audio/vnd.lucent.voice'),'lwp'=>array('application/vnd.lotus-wordpro'),'lzh'=>array('application/octet-stream','application/x-lzh'),'lzx'=>array('application/lzx','application/octet-stream','application/x-lzx'),'m'=>array('text/plain','text/x-m'),'m13'=>array('application/x-msmediaview'),'m14'=>array('application/x-msmediaview'),'m1v'=>array('video/mpeg'),'m21'=>array('application/mp21'),'m2a'=>array('audio/mpeg'),'m2v'=>array('video/mpeg'),'m3a'=>array('audio/mpeg'),'m3u'=>array('audio/x-mpegurl'),'m3u8'=>array('application/x-mpegURL'),'m4a'=>array('audio/mp4'),'m4p'=>array('application/mp4'),'m4u'=>array('video/vnd.mpegurl'),'m4v'=>array('video/x-m4v'),'ma'=>array('application/mathematica'),'mads'=>array('application/mads+xml'),'mag'=>array('application/vnd.ecowin.chart'),'maker'=>array('application/vnd.framemaker'),'man'=>array('text/troff'),'manifest'=>array('text/cache-manifest'),'map'=>array('application/x-navimap'),'mar'=>array('application/octet-stream'),'markdown'=>array('text/x-markdown'),'mathml'=>array('application/mathml+xml'),'mb'=>array('application/mathematica'),'mbd'=>array('application/mbedlet'),'mbk'=>array('application/vnd.mobius.mbk'),'mbox'=>array('application/mbox'),'mc'=>array('application/x-magic-cap-package-1.0'),'mc1'=>array('application/vnd.medcalcdata'),'mcd'=>array('application/mcad','application/x-mathcad'),'mcf'=>array('image/vasa','text/mcf'),'mcp'=>array('application/netmc'),'mcurl'=>array('text/vnd.curl.mcurl'),'md'=>array('text/x-markdown'),'mdb'=>array('application/x-msaccess'),'mdi'=>array('image/vnd.ms-modi'),'me'=>array('text/troff'),'mesh'=>array('model/mesh'),'meta4'=>array('application/metalink4+xml'),'metalink'=>array('application/metalink+xml'),'mets'=>array('application/mets+xml'),'mfm'=>array('application/vnd.mfmp'),'mft'=>array('application/rpki-manifest'),'mgp'=>array('application/vnd.osgeo.mapguide.package'),'mgz'=>array('application/vnd.proteus.magazine'),'mht'=>array('message/rfc822'),'mhtml'=>array('message/rfc822'),'mid'=>array('application/x-midi','audio/midi','audio/x-mid','audio/x-midi','music/crescendo','x-music/x-midi'),'midi'=>array('application/x-midi','audio/midi','audio/x-mid','audio/x-midi','music/crescendo','x-music/x-midi'),'mie'=>array('application/x-mie'),'mif'=>array('application/x-frame','application/x-mif'),'mime'=>array('message/rfc822','www/mime'),'mj2'=>array('video/mj2'),'mjf'=>array('audio/x-vnd.audioexplosion.mjuicemediafile'),'mjp2'=>array('video/mj2'),'mjpg'=>array('video/x-motion-jpeg'),'mk3d'=>array('video/x-matroska'),'mka'=>array('audio/x-matroska'),'mkd'=>array('text/x-markdown'),'mks'=>array('video/x-matroska'),'mkv'=>array('video/x-matroska'),'mlp'=>array('application/vnd.dolby.mlp'),'mm'=>array('application/base64','application/x-meme'),'mmd'=>array('application/vnd.chipnuts.karaoke-mmd'),'mme'=>array('application/base64'),'mmf'=>array('application/vnd.smaf'),'mmr'=>array('image/vnd.fujixerox.edmics-mmr'),'mng'=>array('video/x-mng'),'mny'=>array('application/x-msmoney'),'mobi'=>array('application/x-mobipocket-ebook'),'mod'=>array('audio/mod','audio/x-mod'),'mods'=>array('application/mods+xml'),'moov'=>array('video/quicktime'),'mov'=>array('video/quicktime'),'movie'=>array('video/x-sgi-movie'),'mp2'=>array('audio/mpeg','audio/x-mpeg','video/mpeg','video/x-mpeg','video/x-mpeq2a'),'mp21'=>array('application/mp21'),'mp2a'=>array('audio/mpeg'),'mp3'=>array('audio/mpeg', 'audio/mpeg3','audio/x-mpeg-3','video/mpeg','video/x-mpeg'),'mp4'=>array('video/mp4'),'mp4a'=>array('audio/mp4'),'mp4s'=>array('application/mp4'),'mp4v'=>array('video/mp4'),'mpa'=>array('audio/mpeg','video/mpeg'),'mpc'=>array('application/vnd.mophun.certificate'),'mpe'=>array('video/mpeg'),'mpeg'=>array('video/mpeg'),'mpg'=>array('audio/mpeg','video/mpeg'),'mpg4'=>array('video/mp4'),'mpga'=>array('audio/mpeg'),'mpkg'=>array('application/vnd.apple.installer+xml'),'mpm'=>array('application/vnd.blueice.multipass'),'mpn'=>array('application/vnd.mophun.application'),'mpp'=>array('application/vnd.ms-project'),'mpt'=>array('application/vnd.ms-project'),'mpv'=>array('application/x-project'),'mpx'=>array('application/x-project'),'mpy'=>array('application/vnd.ibm.minipay'),'mqy'=>array('application/vnd.mobius.mqy'),'mrc'=>array('application/marc'),'mrcx'=>array('application/marcxml+xml'),'ms'=>array('text/troff'),'mscml'=>array('application/mediaservercontrol+xml'),'mseed'=>array('application/vnd.fdsn.mseed'),'mseq'=>array('application/vnd.mseq'),'msf'=>array('application/vnd.epson.msf'),'msh'=>array('model/mesh'),'msi'=>array('application/x-msdownload'),'msl'=>array('application/vnd.mobius.msl'),'msty'=>array('application/vnd.muvee.style'),'mts'=>array('model/vnd.mts'),'mus'=>array('application/vnd.musician'),'musicxml'=>array('application/vnd.recordare.musicxml+xml'),'mv'=>array('video/x-sgi-movie'),'mvb'=>array('application/x-msmediaview'),'mwf'=>array('application/vnd.mfer'),'mxf'=>array('application/mxf'),'mxl'=>array('application/vnd.recordare.musicxml'),'mxml'=>array('application/xv+xml'),'mxs'=>array('application/vnd.triscape.mxs'),'mxu'=>array('video/vnd.mpegurl'),'my'=>array('audio/make'),'mzz'=>array('application/x-vnd.audioexplosion.mzz'),'n-gage'=>array('application/vnd.nokia.n-gage.symbian.install'),'n3'=>array('text/n3'),'nap'=>array('image/naplps'),'naplps'=>array('image/naplps'),'nb'=>array('application/mathematica'),'nbp'=>array('application/vnd.wolfram.player'),'nc'=>array('application/x-netcdf'),'ncm'=>array('application/vnd.nokia.configuration-message'),'ncx'=>array('application/x-dtbncx+xml'),'nfo'=>array('text/x-nfo'),'ngdat'=>array('application/vnd.nokia.n-gage.data'),'nif'=>array('image/x-niff'),'niff'=>array('image/x-niff'),'nitf'=>array('application/vnd.nitf'),'nix'=>array('application/x-mix-transfer'),'nlu'=>array('application/vnd.neurolanguage.nlu'),'nml'=>array('application/vnd.enliven'),'nnd'=>array('application/vnd.noblenet-directory'),'nns'=>array('application/vnd.noblenet-sealer'),'nnw'=>array('application/vnd.noblenet-web'),'npx'=>array('image/vnd.net-fpx'),'nsc'=>array('application/x-conference'),'nsf'=>array('application/vnd.lotus-notes'),'ntf'=>array('application/vnd.nitf'),'nvd'=>array('application/x-navidoc'),'nws'=>array('message/rfc822'),'nzb'=>array('application/x-nzb'),'o'=>array('application/octet-stream'),'oa2'=>array('application/vnd.fujitsu.oasys2'),'oa3'=>array('application/vnd.fujitsu.oasys3'),'oas'=>array('application/vnd.fujitsu.oasys'),'obd'=>array('application/x-msbinder'),'obj'=>array('application/x-tgif'),'oda'=>array('application/oda'),'odb'=>array('application/vnd.oasis.opendocument.database'),'odc'=>array('application/vnd.oasis.opendocument.chart'),'odf'=>array('application/vnd.oasis.opendocument.formula'),'odft'=>array('application/vnd.oasis.opendocument.formula-template'),'odg'=>array('application/vnd.oasis.opendocument.graphics'),'odi'=>array('application/vnd.oasis.opendocument.image'),'odm'=>array('application/vnd.oasis.opendocument.text-master'),'odp'=>array('application/vnd.oasis.opendocument.presentation'),'ods'=>array('application/vnd.oasis.opendocument.spreadsheet'),'odt'=>array('application/vnd.oasis.opendocument.text'),'oga'=>array('audio/ogg'),'ogg'=>array('audio/ogg'),'ogv'=>array('video/ogg'),'ogx'=>array('application/ogg'),'omc'=>array('application/x-omc'),'omcd'=>array('application/x-omcdatamaker'),'omcr'=>array('application/x-omcregerator'),'omdoc'=>array('application/omdoc+xml'),'onepkg'=>array('application/onenote'),'onetmp'=>array('application/onenote'),'onetoc'=>array('application/onenote'),'onetoc2'=>array('application/onenote'),'opf'=>array('application/oebps-package+xml'),'opml'=>array('text/x-opml'),'oprc'=>array('application/vnd.palm'),'org'=>array('application/vnd.lotus-organizer'),'osf'=>array('application/vnd.yamaha.openscoreformat'),'osfpvg'=>array('application/vnd.yamaha.openscoreformat.osfpvg+xml'),'otc'=>array('application/vnd.oasis.opendocument.chart-template'),'otf'=>array('font/opentype'),'otg'=>array('application/vnd.oasis.opendocument.graphics-template'),'oth'=>array('application/vnd.oasis.opendocument.text-web'),'oti'=>array('application/vnd.oasis.opendocument.image-template'),'otm'=>array('application/vnd.oasis.opendocument.text-master'),'otp'=>array('application/vnd.oasis.opendocument.presentation-template'),'ots'=>array('application/vnd.oasis.opendocument.spreadsheet-template'),'ott'=>array('application/vnd.oasis.opendocument.text-template'),'oxps'=>array('application/oxps'),'oxt'=>array('application/vnd.openofficeorg.extension'),'p'=>array('text/x-pascal'),'p10'=>array('application/pkcs10','application/x-pkcs10'),'p12'=>array('application/pkcs-12','application/x-pkcs12'),'p7a'=>array('application/x-pkcs7-signature'),'p7b'=>array('application/x-pkcs7-certificates'),'p7c'=>array('application/pkcs7-mime','application/x-pkcs7-mime'),'p7m'=>array('application/pkcs7-mime','application/x-pkcs7-mime'),'p7r'=>array('application/x-pkcs7-certreqresp'),'p7s'=>array('application/pkcs7-signature'),'p8'=>array('application/pkcs8'),'part'=>array('application/pro_eng'),'pas'=>array('text/x-pascal'),'paw'=>array('application/vnd.pawaafile'),'pbd'=>array('application/vnd.powerbuilder6'),'pbm'=>array('image/x-portable-bitmap'),'pcap'=>array('application/vnd.tcpdump.pcap'),'pcf'=>array('application/x-font-pcf'),'pcl'=>array('application/vnd.hp-pcl','application/x-pcl'),'pclxl'=>array('application/vnd.hp-pclxl'),'pct'=>array('image/x-pict'),'pcurl'=>array('application/vnd.curl.pcurl'),'pcx'=>array('image/x-pcx'),'pdb'=>array('application/vnd.palm'),'pdf'=>array('application/pdf'),'pfa'=>array('application/x-font-type1'),'pfb'=>array('application/x-font-type1'),'pfm'=>array('application/x-font-type1'),'pfr'=>array('application/font-tdpfr'),'pfunk'=>array('audio/make'),'pfx'=>array('application/x-pkcs12'),'pgm'=>array('image/x-portable-graymap'),'pgn'=>array('application/x-chess-pgn'),'pgp'=>array('application/pgp-encrypted'),'php'=>array('text/x-php'),'pic'=>array('image/x-pict'),'pict'=>array('image/pict'),'pkg'=>array('application/octet-stream'),'pki'=>array('application/pkixcmp'),'pkipath'=>array('application/pkix-pkipath'),'pko'=>array('application/vnd.ms-pki.pko'),'pl'=>array('text/plain','text/x-script.perl'),'plb'=>array('application/vnd.3gpp.pic-bw-large'),'plc'=>array('application/vnd.mobius.plc'),'plf'=>array('application/vnd.pocketlearn'),'pls'=>array('application/pls+xml'),'plx'=>array('application/x-pixclscript'),'pm'=>array('image/x-xpixmap','text/x-script.perl-module'),'pm4'=>array('application/x-pagemaker'),'pm5'=>array('application/x-pagemaker'),'pml'=>array('application/vnd.ctc-posml'),'png'=>array('image/png'),'pnm'=>array('application/x-portable-anymap','image/x-portable-anymap'),'portpkg'=>array('application/vnd.macports.portpkg'),'pot'=>array('application/mspowerpoint','application/vnd.ms-powerpoint'),'potm'=>array('application/vnd.ms-powerpoint.template.macroenabled.12'),'potx'=>array('application/vnd.openxmlformats-officedocument.presentationml.template'),'pov'=>array('model/x-pov'),'ppa'=>array('application/vnd.ms-powerpoint'),'ppam'=>array('application/vnd.ms-powerpoint.addin.macroenabled.12'),'ppd'=>array('application/vnd.cups-ppd'),'ppm'=>array('image/x-portable-pixmap'),'pps'=>array('application/mspowerpoint','application/vnd.ms-powerpoint'),'ppsm'=>array('application/vnd.ms-powerpoint.slideshow.macroenabled.12'),'ppsx'=>array('application/vnd.openxmlformats-officedocument.presentationml.slideshow'),'ppt'=>array('application/mspowerpoint','application/powerpoint','application/vnd.ms-powerpoint','application/x-mspowerpoint'),'pptm'=>array('application/vnd.ms-powerpoint.presentation.macroenabled.12'),'pptx'=>array('application/vnd.openxmlformats-officedocument.presentationml.presentation'),'ppz'=>array('application/mspowerpoint'),'pqa'=>array('application/vnd.palm'),'prc'=>array('application/x-mobipocket-ebook'),'pre'=>array('application/vnd.lotus-freelance'),'prf'=>array('application/pics-rules'),'prt'=>array('application/pro_eng'),'ps'=>array('application/postscript'),'psb'=>array('application/vnd.3gpp.pic-bw-small'),'psd'=>array('image/vnd.adobe.photoshop'),'psf'=>array('application/x-font-linux-psf'),'pskcxml'=>array('application/pskc+xml'),'ptid'=>array('application/vnd.pvi.ptid1'),'pub'=>array('application/x-mspublisher'),'pvb'=>array('application/vnd.3gpp.pic-bw-var'),'pvu'=>array('paleovu/x-pv'),'pwn'=>array('application/vnd.3m.post-it-notes'),'pwz'=>array('application/vnd.ms-powerpoint'),'py'=>array('text/x-script.phyton'),'pya'=>array('audio/vnd.ms-playready.media.pya'),'pyc'=>array('applicaiton/x-bytecode.python'),'pyo'=>array('application/x-python-code'),'pyv'=>array('video/vnd.ms-playready.media.pyv'),'qam'=>array('application/vnd.epson.quickanime'),'qbo'=>array('application/vnd.intu.qbo'),'qcp'=>array('audio/vnd.qcelp'),'qd3'=>array('x-world/x-3dmf'),'qd3d'=>array('x-world/x-3dmf'),'qfx'=>array('application/vnd.intu.qfx'),'qif'=>array('image/x-quicktime'),'qps'=>array('application/vnd.publishare-delta-tree'),'qt'=>array('video/quicktime'),'qtc'=>array('video/x-qtc'),'qti'=>array('image/x-quicktime'),'qtif'=>array('image/x-quicktime'),'qwd'=>array('application/vnd.quark.quarkxpress'),'qwt'=>array('application/vnd.quark.quarkxpress'),'qxb'=>array('application/vnd.quark.quarkxpress'),'qxd'=>array('application/vnd.quark.quarkxpress'),'qxl'=>array('application/vnd.quark.quarkxpress'),'qxt'=>array('application/vnd.quark.quarkxpress'),'ra'=>array('audio/x-pn-realaudio','audio/x-pn-realaudio-plugin','audio/x-realaudio'),'ram'=>array('audio/x-pn-realaudio'),'rar'=>array('application/x-rar-compressed'),'ras'=>array('application/x-cmu-raster','image/cmu-raster','image/x-cmu-raster'),'rast'=>array('image/cmu-raster'),'rcprofile'=>array('application/vnd.ipunplugged.rcprofile'),'rdf'=>array('application/rdf+xml'),'rdz'=>array('application/vnd.data-vision.rdz'),'rep'=>array('application/vnd.businessobjects'),'res'=>array('application/x-dtbresource+xml'),'rexx'=>array('text/x-script.rexx'),'rf'=>array('image/vnd.rn-realflash'),'rgb'=>array('image/x-rgb'),'rif'=>array('application/reginfo+xml'),'rip'=>array('audio/vnd.rip'),'ris'=>array('application/x-research-info-systems'),'rl'=>array('application/resource-lists+xml'),'rlc'=>array('image/vnd.fujixerox.edmics-rlc'),'rld'=>array('application/resource-lists-diff+xml'),'rm'=>array('application/vnd.rn-realmedia','audio/x-pn-realaudio'),'rmi'=>array('audio/midi'),'rmm'=>array('audio/x-pn-realaudio'),'rmp'=>array('audio/x-pn-realaudio','audio/x-pn-realaudio-plugin'),'rms'=>array('application/vnd.jcp.javame.midlet-rms'),'rmvb'=>array('application/vnd.rn-realmedia-vbr'),'rnc'=>array('application/relax-ng-compact-syntax'),'rng'=>array('application/ringing-tones','application/vnd.nokia.ringing-tone'),'rnx'=>array('application/vnd.rn-realplayer'),'roa'=>array('application/rpki-roa'),'roff'=>array('text/troff'),'rp'=>array('image/vnd.rn-realpix'),'rp9'=>array('application/vnd.cloanto.rp9'),'rpm'=>array('audio/x-pn-realaudio-plugin'),'rpss'=>array('application/vnd.nokia.radio-presets'),'rpst'=>array('application/vnd.nokia.radio-preset'),'rq'=>array('application/sparql-query'),'rs'=>array('application/rls-services+xml'),'rsd'=>array('application/rsd+xml'),'rss'=>array('application/rss+xml'),'rt'=>array('text/richtext','text/vnd.rn-realtext'),'rtf'=>array('application/rtf','application/x-rtf','text/richtext'),'rtx'=>array('application/rtf','text/richtext'),'rv'=>array('video/vnd.rn-realvideo'),'s'=>array('text/x-asm'),'s3m'=>array('audio/s3m'),'saf'=>array('application/vnd.yamaha.smaf-audio'),'saveme'=>array('aapplication/octet-stream'),'sbk'=>array('application/x-tbook'),'sbml'=>array('application/sbml+xml'),'sc'=>array('application/vnd.ibm.secure-container'),'scd'=>array('application/x-msschedule'),'scm'=>array('application/x-lotusscreencam','text/x-script.guile','text/x-script.scheme','video/x-scm'),'scq'=>array('application/scvp-cv-request'),'scs'=>array('application/scvp-cv-response'),'scurl'=>array('text/vnd.curl.scurl'),'sda'=>array('application/vnd.stardivision.draw'),'sdc'=>array('application/vnd.stardivision.calc'),'sdd'=>array('application/vnd.stardivision.impress'),'sdkd'=>array('application/vnd.solent.sdkm+xml'),'sdkm'=>array('application/vnd.solent.sdkm+xml'),'sdml'=>array('text/plain'),'sdp'=>array('application/sdp','application/x-sdp'),'sdr'=>array('application/sounder'),'sdw'=>array('application/vnd.stardivision.writer'),'sea'=>array('application/sea','application/x-sea'),'see'=>array('application/vnd.seemail'),'seed'=>array('application/vnd.fdsn.seed'),'sema'=>array('application/vnd.sema'),'semd'=>array('application/vnd.semd'),'semf'=>array('application/vnd.semf'),'ser'=>array('application/java-serialized-object'),'set'=>array('application/set'),'setpay'=>array('application/set-payment-initiation'),'setreg'=>array('application/set-registration-initiation'),'sfd-hdstx'=>array('application/vnd.hydrostatix.sof-data'),'sfs'=>array('application/vnd.spotfire.sfs'),'sfv'=>array('text/x-sfv'),'sgi'=>array('image/sgi'),'sgl'=>array('application/vnd.stardivision.writer-global'),'sgm'=>array('text/sgml','text/x-sgml'),'sgml'=>array('text/sgml','text/x-sgml'),'sh'=>array('application/x-bsh','application/x-sh','application/x-shar','text/x-script.sh'),'shar'=>array('application/x-bsh','application/x-shar'),'shf'=>array('application/shf+xml'),'shtml'=>array('text/html','text/x-server-parsed-html'),'si'=>array('text/vnd.wap.si'),'sic'=>array('application/vnd.wap.sic'),'sid'=>array('image/x-mrsid-image'),'sig'=>array('application/pgp-signature'),'sil'=>array('audio/silk'),'silo'=>array('model/mesh'),'sis'=>array('application/vnd.symbian.install'),'sisx'=>array('application/vnd.symbian.install'),'sit'=>array('application/x-sit','application/x-stuffit'),'sitx'=>array('application/x-stuffitx'),'skd'=>array('application/vnd.koan'),'skm'=>array('application/vnd.koan'),'skp'=>array('application/vnd.koan'),'skt'=>array('application/vnd.koan'),'sl'=>array('application/x-seelogo'),'slc'=>array('application/vnd.wap.slc'),'sldm'=>array('application/vnd.ms-powerpoint.slide.macroenabled.12'),'sldx'=>array('application/vnd.openxmlformats-officedocument.presentationml.slide'),'slt'=>array('application/vnd.epson.salt'),'sm'=>array('application/vnd.stepmania.stepchart'),'smf'=>array('application/vnd.stardivision.math'),'smi'=>array('application/smil+xml'),'smil'=>array('application/smil+xml'),'smv'=>array('video/x-smv'),'smzip'=>array('application/vnd.stepmania.package'),'snd'=>array('audio/basic','audio/x-adpcm'),'snf'=>array('application/x-font-snf'),'so'=>array('application/octet-stream'),'sol'=>array('application/solids'),'spc'=>array('application/x-pkcs7-certificates','text/x-speech'),'spf'=>array('application/vnd.yamaha.smaf-phrase'),'spl'=>array('application/x-futuresplash'),'spot'=>array('text/vnd.in3d.spot'),'spp'=>array('application/scvp-vp-response'),'spq'=>array('application/scvp-vp-request'),'spr'=>array('application/x-sprite'),'sprite'=>array('application/x-sprite'),'spx'=>array('audio/ogg'),'sql'=>array('application/x-sql'),'src'=>array('application/x-wais-source'),'srt'=>array('application/x-subrip'),'sru'=>array('application/sru+xml'),'srx'=>array('application/sparql-results+xml'),'ssdl'=>array('application/ssdl+xml'),'sse'=>array('application/vnd.kodak-descriptor'),'ssf'=>array('application/vnd.epson.ssf'),'ssi'=>array('text/x-server-parsed-html'),'ssm'=>array('application/streamingmedia'),'ssml'=>array('application/ssml+xml'),'sst'=>array('application/vnd.ms-pki.certstore'),'st'=>array('application/vnd.sailingtracker.track'),'stc'=>array('application/vnd.sun.xml.calc.template'),'std'=>array('application/vnd.sun.xml.draw.template'),'step'=>array('application/step'),'stf'=>array('application/vnd.wt.stf'),'sti'=>array('application/vnd.sun.xml.impress.template'),'stk'=>array('application/hyperstudio'),'stl'=>array('application/sla','application/vnd.ms-pki.stl','application/x-navistyle'),'stp'=>array('application/step'),'str'=>array('application/vnd.pg.format'),'stw'=>array('application/vnd.sun.xml.writer.template'),'sub'=>array('text/vnd.dvb.subtitle'),'sus'=>array('application/vnd.sus-calendar'),'susp'=>array('application/vnd.sus-calendar'),'sv4cpio'=>array('application/x-sv4cpio'),'sv4crc'=>array('application/x-sv4crc'),'svc'=>array('application/vnd.dvb.service'),'svd'=>array('application/vnd.svd'),'svf'=>array('image/vnd.dwg','image/x-dwg'),'svg'=>array('image/svg+xml'),'svgz'=>array('image/svg+xml'),'svr'=>array('application/x-world','x-world/x-svr'),'swa'=>array('application/x-director'),'swf'=>array('application/x-shockwave-flash'),'swi'=>array('application/vnd.aristanetworks.swi'),'sxc'=>array('application/vnd.sun.xml.calc'),'sxd'=>array('application/vnd.sun.xml.draw'),'sxg'=>array('application/vnd.sun.xml.writer.global'),'sxi'=>array('application/vnd.sun.xml.impress'),'sxm'=>array('application/vnd.sun.xml.math'),'sxw'=>array('application/vnd.sun.xml.writer'),'t'=>array('text/troff'),'t3'=>array('application/x-t3vm-image'),'taglet'=>array('application/vnd.mynfc'),'talk'=>array('text/x-speech'),'tao'=>array('application/vnd.tao.intent-module-archive'),'tar'=>array('application/x-tar'),'tbk'=>array('application/toolbook','application/x-tbook'),'tcap'=>array('application/vnd.3gpp2.tcap'),'tcl'=>array('application/x-tcl','text/x-script.tcl'),'tcsh'=>array('text/x-script.tcsh'),'teacher'=>array('application/vnd.smart.teacher'),'tei'=>array('application/tei+xml'),'teicorpus'=>array('application/tei+xml'),'tex'=>array('application/x-tex'),'texi'=>array('application/x-texinfo'),'texinfo'=>array('application/x-texinfo'),'text'=>array('application/plain','text/plain'),'tfi'=>array('application/thraud+xml'),'tfm'=>array('application/x-tex-tfm'),'tga'=>array('image/x-tga'),'tgz'=>array('application/gnutar','application/x-compressed'),'thmx'=>array('application/vnd.ms-officetheme'),'tif'=>array('image/tiff','image/x-tiff'),'tiff'=>array('image/tiff','image/x-tiff'),'tmo'=>array('application/vnd.tmobile-livetv'),'torrent'=>array('application/x-bittorrent'),'tpl'=>array('application/vnd.groove-tool-template'),'tpt'=>array('application/vnd.trid.tpt'),'tr'=>array('text/troff'),'tra'=>array('application/vnd.trueapp'),'trm'=>array('application/x-msterminal'),'ts'=>array('video/MP2T'),'tsd'=>array('application/timestamped-data'),'tsi'=>array('audio/tsp-audio'),'tsp'=>array('application/dsptype','audio/tsplayer'),'tsv'=>array('text/tab-separated-values'),'ttc'=>array('application/x-font-ttf'),'ttf'=>array('application/x-font-ttf'),'ttl'=>array('text/turtle'),'turbot'=>array('image/florian'),'twd'=>array('application/vnd.simtech-mindmapper'),'twds'=>array('application/vnd.simtech-mindmapper'),'txd'=>array('application/vnd.genomatix.tuxedo'),'txf'=>array('application/vnd.mobius.txf'),'txt'=>array('text/plain'),'u32'=>array('application/x-authorware-bin'),'udeb'=>array('application/x-debian-package'),'ufd'=>array('application/vnd.ufdl'),'ufdl'=>array('application/vnd.ufdl'),'uil'=>array('text/x-uil'),'ulx'=>array('application/x-glulx'),'umj'=>array('application/vnd.umajin'),'uni'=>array('text/uri-list'),'unis'=>array('text/uri-list'),'unityweb'=>array('application/vnd.unity'),'unv'=>array('application/i-deas'),'uoml'=>array('application/vnd.uoml+xml'),'uri'=>array('text/uri-list'),'uris'=>array('text/uri-list'),'urls'=>array('text/uri-list'),'ustar'=>array('application/x-ustar','multipart/x-ustar'),'utz'=>array('application/vnd.uiq.theme'),'uu'=>array('application/octet-stream','text/x-uuencode'),'uue'=>array('text/x-uuencode'),'uva'=>array('audio/vnd.dece.audio'),'uvd'=>array('application/vnd.dece.data'),'uvf'=>array('application/vnd.dece.data'),'uvg'=>array('image/vnd.dece.graphic'),'uvh'=>array('video/vnd.dece.hd'),'uvi'=>array('image/vnd.dece.graphic'),'uvm'=>array('video/vnd.dece.mobile'),'uvp'=>array('video/vnd.dece.pd'),'uvs'=>array('video/vnd.dece.sd'),'uvt'=>array('application/vnd.dece.ttml+xml'),'uvu'=>array('video/vnd.uvvu.mp4'),'uvv'=>array('video/vnd.dece.video'),'uvva'=>array('audio/vnd.dece.audio'),'uvvd'=>array('application/vnd.dece.data'),'uvvf'=>array('application/vnd.dece.data'),'uvvg'=>array('image/vnd.dece.graphic'),'uvvh'=>array('video/vnd.dece.hd'),'uvvi'=>array('image/vnd.dece.graphic'),'uvvm'=>array('video/vnd.dece.mobile'),'uvvp'=>array('video/vnd.dece.pd'),'uvvs'=>array('video/vnd.dece.sd'),'uvvt'=>array('application/vnd.dece.ttml+xml'),'uvvu'=>array('video/vnd.uvvu.mp4'),'uvvv'=>array('video/vnd.dece.video'),'uvvx'=>array('application/vnd.dece.unspecified'),'uvvz'=>array('application/vnd.dece.zip'),'uvx'=>array('application/vnd.dece.unspecified'),'uvz'=>array('application/vnd.dece.zip'),'vcard'=>array('text/vcard'),'vcd'=>array('application/x-cdlink'),'vcf'=>array('text/x-vcard'),'vcg'=>array('application/vnd.groove-vcard'),'vcs'=>array('text/x-vcalendar'),'vcx'=>array('application/vnd.vcx'),'vda'=>array('application/vda'),'vdo'=>array('video/vdo'),'vew'=>array('application/groupwise'),'vis'=>array('application/vnd.visionary'),'viv'=>array('video/vivo','video/vnd.vivo'),'vivo'=>array('video/vivo','video/vnd.vivo'),'vmd'=>array('application/vocaltec-media-desc'),'vmf'=>array('application/vocaltec-media-file'),'vob'=>array('video/x-ms-vob'),'voc'=>array('audio/voc','audio/x-voc'),'vor'=>array('application/vnd.stardivision.writer'),'vos'=>array('video/vosaic'),'vox'=>array('application/x-authorware-bin'),'vqe'=>array('audio/x-twinvq-plugin'),'vqf'=>array('audio/x-twinvq'),'vql'=>array('audio/x-twinvq-plugin'),'vrml'=>array('application/x-vrml','model/vrml','x-world/x-vrml'),'vrt'=>array('x-world/x-vrt'),'vsd'=>array('application/vnd.visio'),'vsf'=>array('application/vnd.vsf'),'vss'=>array('application/vnd.visio'),'vst'=>array('application/vnd.visio'),'vsw'=>array('application/vnd.visio'),'vtt'=>array('text/vtt'),'vtu'=>array('model/vnd.vtu'),'vxml'=>array('application/voicexml+xml'),'w3d'=>array('application/x-director'),'w60'=>array('application/wordperfect6.0'),'w61'=>array('application/wordperfect6.1'),'w6w'=>array('application/msword'),'wad'=>array('application/x-doom'),'wav'=>array('audio/wav','audio/x-wav'),'wax'=>array('audio/x-ms-wax'),'wb1'=>array('application/x-qpro'),'wbmp'=>array('image/vnd.wap.wbmp'),'wbs'=>array('application/vnd.criticaltools.wbs+xml'),'wbxml'=>array('application/vnd.wap.wbxml'),'wcm'=>array('application/vnd.ms-works'),'wdb'=>array('application/vnd.ms-works'),'wdp'=>array('image/vnd.ms-photo'),'web'=>array('application/vnd.xara'),'weba'=>array('audio/webm'),'webapp'=>array('application/x-web-app-manifest+json'),'webm'=>array('video/webm'),'webp'=>array('image/webp'),'wg'=>array('application/vnd.pmi.widget'),'wgt'=>array('application/widget'),'wiz'=>array('application/msword'),'wk1'=>array('application/x-123'),'wks'=>array('application/vnd.ms-works'),'wm'=>array('video/x-ms-wm'),'wma'=>array('audio/x-ms-wma'),'wmd'=>array('application/x-ms-wmd'),'wmf'=>array('application/x-msmetafile'),'wml'=>array('text/vnd.wap.wml'),'wmlc'=>array('application/vnd.wap.wmlc'),'wmls'=>array('text/vnd.wap.wmlscript'),'wmlsc'=>array('application/vnd.wap.wmlscriptc'),'wmv'=>array('video/x-ms-wmv'),'wmx'=>array('video/x-ms-wmx'),'wmz'=>array('application/x-msmetafile'),'woff'=>array('application/x-font-woff'),'word'=>array('application/msword'),'wp'=>array('application/wordperfect'),'wp5'=>array('application/wordperfect','application/wordperfect6.0'),'wp6'=>array('application/wordperfect'),'wpd'=>array('application/wordperfect','application/x-wpwin'),'wpl'=>array('application/vnd.ms-wpl'),'wps'=>array('application/vnd.ms-works'),'wq1'=>array('application/x-lotus'),'wqd'=>array('application/vnd.wqd'),'wri'=>array('application/mswrite','application/x-wri'),'wrl'=>array('application/x-world','model/vrml','x-world/x-vrml'),'wrz'=>array('model/vrml','x-world/x-vrml'),'wsc'=>array('text/scriplet'),'wsdl'=>array('application/wsdl+xml'),'wspolicy'=>array('application/wspolicy+xml'),'wsrc'=>array('application/x-wais-source'),'wtb'=>array('application/vnd.webturbo'),'wtk'=>array('application/x-wintalk'),'wvx'=>array('video/x-ms-wvx'),'x-png'=>array('image/png'),'x32'=>array('application/x-authorware-bin'),'x3d'=>array('model/x3d+xml'),'x3db'=>array('model/x3d+binary'),'x3dbz'=>array('model/x3d+binary'),'x3dv'=>array('model/x3d+vrml'),'x3dvz'=>array('model/x3d+vrml'),'x3dz'=>array('model/x3d+xml'),'xaml'=>array('application/xaml+xml'),'xap'=>array('application/x-silverlight-app'),'xar'=>array('application/vnd.xara'),'xbap'=>array('application/x-ms-xbap'),'xbd'=>array('application/vnd.fujixerox.docuworks.binder'),'xbm'=>array('image/x-xbitmap','image/x-xbm','image/xbm'),'xdf'=>array('application/xcap-diff+xml'),'xdm'=>array('application/vnd.syncml.dm+xml'),'xdp'=>array('application/vnd.adobe.xdp+xml'),'xdr'=>array('video/x-amt-demorun'),'xdssc'=>array('application/dssc+xml'),'xdw'=>array('application/vnd.fujixerox.docuworks'),'xenc'=>array('application/xenc+xml'),'xer'=>array('application/patch-ops-error+xml'),'xfdf'=>array('application/vnd.adobe.xfdf'),'xfdl'=>array('application/vnd.xfdl'),'xgz'=>array('xgl/drawing'),'xht'=>array('application/xhtml+xml'),'xhtml'=>array('application/xhtml+xml'),'xhvml'=>array('application/xv+xml'),'xif'=>array('image/vnd.xiff'),'xl'=>array('application/excel'),'xla'=>array('application/excel','application/x-excel','application/x-msexcel'),'xlam'=>array('application/vnd.ms-excel.addin.macroenabled.12'),'xlb'=>array('application/excel','application/vnd.ms-excel','application/x-excel'),'xlc'=>array('application/excel','application/vnd.ms-excel','application/x-excel'),'xld'=>array('application/excel','application/x-excel'),'xlf'=>array('application/x-xliff+xml'),'xlk'=>array('application/excel','application/x-excel'),'xll'=>array('application/excel','application/vnd.ms-excel','application/x-excel'),'xlm'=>array('application/excel','application/vnd.ms-excel','application/x-excel'),'xls'=>array('application/excel','application/vnd.ms-excel','application/x-excel','application/x-msexcel'),'xlsb'=>array('application/vnd.ms-excel.sheet.binary.macroenabled.12'),'xlsm'=>array('application/vnd.ms-excel.sheet.macroenabled.12'),'xlsx'=>array('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),'xlt'=>array('application/excel','application/x-excel'),'xltm'=>array('application/vnd.ms-excel.template.macroenabled.12'),'xltx'=>array('application/vnd.openxmlformats-officedocument.spreadsheetml.template'),'xlv'=>array('application/excel','application/x-excel'),'xlw'=>array('application/excel','application/vnd.ms-excel','application/x-excel','application/x-msexcel'),'xm'=>array('audio/xm'),'xml'=>array('application/xml','text/xml'),'xmz'=>array('xgl/movie'),'xo'=>array('application/vnd.olpc-sugar'),'xop'=>array('application/xop+xml'),'xpdl'=>array('application/xml'),'xpi'=>array('application/x-xpinstall'),'xpix'=>array('application/x-vnd.ls-xpix'),'xpl'=>array('application/xproc+xml'),'xpm'=>array('image/x-xpixmap','image/xpm'),'xpr'=>array('application/vnd.is-xpr'),'xps'=>array('application/vnd.ms-xpsdocument'),'xpw'=>array('application/vnd.intercon.formnet'),'xpx'=>array('application/vnd.intercon.formnet'),'xsl'=>array('application/xml'),'xslt'=>array('application/xslt+xml'),'xsm'=>array('application/vnd.syncml+xml'),'xspf'=>array('application/xspf+xml'),'xsr'=>array('video/x-amt-showrun'),'xul'=>array('application/vnd.mozilla.xul+xml'),'xvm'=>array('application/xv+xml'),'xvml'=>array('application/xv+xml'),'xwd'=>array('image/x-xwd','image/x-xwindowdump'),'xyz'=>array('chemical/x-xyz'),'xz'=>array('application/x-xz'),'yang'=>array('application/yang'),'yin'=>array('application/yin+xml'),'z'=>array('application/x-compress','application/x-compressed'),'z1'=>array('application/x-zmachine'),'z2'=>array('application/x-zmachine'),'z3'=>array('application/x-zmachine'),'z4'=>array('application/x-zmachine'),'z5'=>array('application/x-zmachine'),'z6'=>array('application/x-zmachine'),'z7'=>array('application/x-zmachine'),'z8'=>array('application/x-zmachine'),'zaz'=>array('application/vnd.zzazz.deck+xml'),'zip'=>array('application/x-compressed','application/x-zip-compressed','application/zip','multipart/x-zip'),'zir'=>array('application/vnd.zul'),'zirz'=>array('application/vnd.zul'),'zmm'=>array('application/vnd.handheld-entertainment+xml'),'zoo'=>array('application/octet-stream'),'zsh'=>array('text/x-script.zsh'),'123'=>array('application/vnd.lotus-1-2-3'));


		if($index && isset($mime_types[$index])) $mimeType = $mime_types[$index];

		return $mimeType;

	}


}