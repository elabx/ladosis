<?php namespace ProcessWire;

/**
* Media Manager: Utilities
*
* This file forms part of the Media Manager Suite.
* Provides various utility methods (validation, SQL counts, conversions, etc) for use throughout the module.
*
* @author Francis Otieno (Kongondo)
* @version 0.0.9
*
* This is a Copyrighted Commercial Module. Please do not distribute or host publicly. The license is not transferable.
*
* ProcessMediaManager for ProcessWire
* Copyright (C) 2015 by Francis Otieno
* Licensed under a Commercial Licence (see README.txt)
*
*/

class MediaManagerUtilities extends ProcessMediaManager {


/* ######################### - MEDIA UTILITIES - ######################### */

	/**
	 * Allowed audio media extensions.
	 *
	 * These are saved in the field 'media_manager_audio'.
	 *
	 * @access public
	 * @return $validExtsAudio Array of strings of extensions of allowed audio formats.
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
	 * @return $validExtsFile Array of strings of extensions of allowed document formats.
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
	 * @return $validExtsImage Array of strings of extensions of allowed image formats.
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
	 * @return $validExtsVideo Array of strings of extensions of allowed image formats.
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
	 * @return $validExts Array of strings of extensions of all allowed media formats.
	 *
	 */
	public function validExtensions() {
		$validExts = array_merge(array('zip'), $this->validExtensionsAudio(), $this->validExtensionsDocument(), $this->validExtensionsImage(), $this->validExtensionsVideo());
		return $validExts;
	}

	/**
	 * Common extensions of audio, document and video media/formats.
	 *
	 * Use this for applying custom thumbs to known types.
	 *
	 * @access public
	 * @return $commonExts Array of strings of extensions of popular media formats.
	 *
	 */
	public function commonExtensions() {
		$commonExts = array('mp3','wav','ogg','m4a','m4p','flac','wma', 'pdf','doc','docx','xls','xlsx', 'ppt', 'pptx', 'mp4','avi','mkv', 'mpg', 'mov','wmv','m4v');
		return $commonExts;
	}

	/**
	 * Generic thumb for audio, document, image and video media.
	 *
	 * We use these in case a media format is not part of popular/common media formats or for image media, no image found.
	 *
	 * @access public
	 * @param $mediaTypeStr String denoting media type.
	 * @return $thumb String URL of generic thumb.
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
	 * Converts media type string to integer.
	 *
	 * @access public
	 * @param $mediaTypeStr String specifying media type.
	 * @return $mediaTypeInt int Integer denoting media type.
	 *
	 */
	public function mediaTypeInt($mediaTypeStr = null) {

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
	 * @param $mediaTypeInt Integer specifying media type.
	 * @return $mediaTypeStr string String denoting media type.
	 *
	 */
	private function mediaTypeStr($mediaTypeInt = null) {

		if($mediaTypeInt == 1)		$mediaTypeStr = 'audio';
		elseif($mediaTypeInt == 2)	$mediaTypeStr = 'document';
		elseif($mediaTypeInt == 3)	$mediaTypeStr = 'image';
		elseif($mediaTypeInt == 4)	$mediaTypeStr = 'video';
		elseif(strlen($mediaTypeInt) > 1 && (int)substr($mediaTypeInt, 0, 1) === 3) $mediaTypeStr = 'image';// for image variations

		return $mediaTypeStr;

	}

	/**
	 * Build selector and set options for Media Manager ProcessLister.
	 *
	 * Used by ProcessMediaManager::init().
	 * Some of the settings are custom (user-set).
	 *
	 * @access public
	 * @param Array $allowedMedia Checks if InputfieldMediaManager allows only certain media types (insert-in-page context).
	 * @param Array $savedSettings Media Manager settings.
	 * @param Array $urlSegments Sanitized URL segments
	 * @return Array $options Options for ProcessMediaManager
	 *
	 */
	public function buildSelector($allowedMedia, $savedSettings, $urlSegments) {		

		$options = array();

		// get sanitised url segments
		// @note: we already called the method $this->getURLSegments() in parent::construct(); no need to call it here again
		$urlSeg1 =  $urlSegments[0];
		$urlSeg2 =  $urlSegments[1];
		$urlSeg3 =  $urlSegments[2];

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

		$togglesArray = array('disableColumns');// we don't need columns in our renderResults(); we are not using tabulated results
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

		// 1st condition: view mode, single media view (e.g. /media-manager/audio/). 2nd condition: add/insert in inputfield mode (i.e. /media-manager/add/1312-145/image/)
		if((in_array($urlSeg1, $segs)) || (in_array($urlSeg3, $segs))) $seg = $urlSeg1 == 'add' ? $urlSeg3 : $urlSeg1;
		// @note: if in RTE IMAGE mode, we assume inserting image
		elseif($urlSeg1 == 'rte') $seg = 'image';
		// @note: if in RTE LINK mode + we have a urlSeg2 (i.e. /media-manager/link/audio [or 'document', 'image' or 'video'])
		elseif($urlSeg1 == 'link' && $urlSeg2) $seg = $urlSeg2;

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

		// @todo - MAKE CONFIGURABLE IN SETTINGS
		// date format for native properties: created and modified
		/*$this->set('nativeDateFormat', $this->_('rel')); */

		// sort + sort order
		$sortFormat = $this->sortSelector($savedSettings, $seg, $segs);// array

		if(count($sortFormat)) {
			$sort = $sortFormat['sort'];
			$defaultSort = $sortFormat['defaultSort'];
			// @TODO/@NOTE: does not presently work in 'view all context' since we apply to selector and being stripped out in ProcessPageLister
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
		// @TODO...TRY GET THESE IDs programmatically!
		$initSelector = "id!=7, id!=2, id!=27, limit=$limit, $mediaSelector";// @TODO...limit here if also in default sort? i.e. user set filter profile

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
	 * @param Array $savedSettings Saved settings for Media Manager.
	 * @param String $seg The current url segment.
	 * @param Array $segs URL segments to determine sort output.
	 * @return Array $sort Options for sorting.
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
	 * Return various properties of found media results.
	 *
	 * Only used in ProcessMediaManager context (i.e. not in page-edit context).
	 * Here we prepare thumbs for only one media type at a time (matching urlSegment1.
	 *
	 * @access public
	 * @param $results PageArray Array of returned results from findMedia().
	 * @param $mediaTypeStr String denoting media type.
	 * @return $a Array of strings ready to loop and output as media thumbs.
	 *
	 */
	public function prepareThumbs($results, $mediaTypeStr) {

		$currentPage = $this->currentPage;
		$currentField = $this->currentField;// the (specified/called) media manager field on the current page being edited

		// get right field from our four media fields
		$f = 'media_manager_' . $mediaTypeStr;// e.g. 'media_manager_audio'

		$a = array();

		$commonExts = $this->commonExtensions();
		$mediaThumbURL = '';
		#$mediaLargeURL = '';
		$mediaURL = '';
		$config = $this->wire('config');

		// loop through results outputting media image thumbs where found/valid
		foreach ($results as $r) {
			$title = $r->title;
			if(mb_strlen($title, 'UTF-8') > 18) {
				$title2 = mb_substr($title, 0, 14, 'UTF-8') . '&hellip;';
				$a[$r->id]['title2'] = $title2;
			}

			$a[$r->id]['title'] = $r->title;

			if($r->is(Page::statusUnpublished)) $a[$r->id]['published'] = 'unpublished';
			if($r->is(Page::statusLocked)) $a[$r->id]['locked'] = 'locked';

			// $r->of(false);// @@note: not needed in module context; already off
			// When output formatting is OFF, image fields always behave as arrays. So, we need first() 
			if(count($r->$f) && $r->$f->first()) {
				$media = $r->$f->first();

				// if media already in field, apply this CSS class
				$selector = $mediaTypeStr == 'image' ?  "id=$r->id, media.basename=$media->basename" : 'id='. $r->id;
				$class = $currentPage && $currentPage->$currentField->has($selector) ? 'in_field' : '';
				$a[$r->id]['in_field'] = $class;

				set_time_limit(60);// avoid script timing out due to potentially resource intensive thumb creation [assuming function not disabled by host]

				#if($mediaTypeStr == 'image') $mediaURL = $media->size(100,75)->url;// resize images if not already resized (note: we are resizing on upload)
				if($mediaTypeStr == 'image') {
					$mediaThumbURL = $media->size(100,75)->url;// get resized image if present, otherwise resize on the fly
					$mediaLargeURL = $media->url;
					$a[$r->id]['url_large'] = $mediaLargeURL;
				}

				// check for custom thumbs || common extensions
				elseif(in_array($media->ext, $commonExts)) {
					$customUserIcon = $config->paths->assets . 'MediaManager/assets/' . $media->ext . '.png';
					if(is_file($customUserIcon)) $mediaThumbURL = $config->urls->assets . 'MediaManager/assets/' . $media->ext . '.png';// use user's custom thumb for this media format
					else $mediaThumbURL = $config->urls->ProcessMediaManager . 'assets/' . $media->ext . '.png';// use MM's custom thumb for this media format
				}

				else $mediaThumbURL = $this->genericThumb($mediaTypeStr);// use generic icon

				$a[$r->id]['url'] 		= $media->url;
				$a[$r->id]['url_thumb'] = $mediaThumbURL;
				$a[$r->id]['basename'] 	= $media->basename;
				$a[$r->id]['type'] 		= $mediaTypeStr;
				$a[$r->id]['ext'] 		= $media->ext;

				// @note: additional for list-view
				$a[$r->id]['tags'] 		= $media->tags;
				$a[$r->id]['description'] 	= $media->description;
				$a[$r->id]['width'] 	= $mediaTypeStr == 'image' ? $media->width . 'px' : '';
				$a[$r->id]['height'] 	= $mediaTypeStr == 'image' ? $media->height . 'px' : '';
				$a[$r->id]['size'] 		= $media->filesizeStr;


			}// end if count $r->$f


		}// end foreach $results

		return $a;

	}

	/**
	 * Return various properties of found media results.
	 *
	 * Only used in ProcessMediaManager context (i.e. not in page-edit context).
	 * Here we prepare thumbs for all media types.
	 *
	 * @access public
	 * @param $results PageArray Array of returned results from findMedia().
	 * @return $a Array of strings ready to loop and output as media thumbs.
	 *
	 */
	public function prepareThumbsAll($results) {

		$currentPage = $this->currentPage;
		$currentField = $this->currentField;// the (specified/called) media manager field on the current page being edited
		$fnPrefix = 'media_manager_';

		$templates =  array('audio' => 'media-manager-audio', 'document' => 'media-manager-document', 'image' => 'media-manager-image', 'video' => 'media-manager-video');

		$a = array();

		$commonExts = $this->commonExtensions();
		$mediaThumbURL = '';
		$mediaURL = '';
		$config = $this->wire('config');
		$class = '';

		// loop through results outputting media image thumbs where found/valid
		foreach ($results as $r) {

			// get right media field for the current media
			// we get via their templates
			// search for given value and return corresponding key if successful
			$mediaTypeStr = array_search($r->template->name, $templates);

			$f = $fnPrefix . $mediaTypeStr;// e.g. 'media_manager_audio'

			$title = $r->title;
			if(mb_strlen($title, 'UTF-8') > 18) {
				$title2 = mb_substr($title, 0, 14, 'UTF-8') . '&hellip;';
				$a[$r->id]['title2'] = $title2;
			}

			if($r->is(Page::statusUnpublished)) $a[$r->id]['published'] = 'unpublished';
			if($r->is(Page::statusLocked)) $a[$r->id]['locked'] = 'locked';


			$a[$r->id]['title'] = $r->title;

			// $r->of(false);// not needed in module context; already off
			// When output formatting is OFF, image fields always behave as arrays. So, we need first() 
			if(count($r->$f) && $r->$f->first()) {

				$media = $r->$f->first();

				// if media already in field, apply this CSS class
				$selector = $mediaTypeStr == 'image' ?  "id=$r->id, media.basename=$media->basename" : 'id='. $r->id;
				$class = $currentPage && $currentPage->$currentField->has($selector) ? 'in_field' : '';
				$a[$r->id]['in_field'] = $class;

				set_time_limit(60);// avoid script timing out due to potentially resource intensive thumb creation [assuming function not disabled by host]

				if($mediaTypeStr == 'image') {
					$mediaThumbURL = $media->size(100,75)->url;// get resized image if present, otherwise resize on the fly
					$mediaLargeURL = $media->url;
					$a[$r->id]['url_large'] = $mediaLargeURL;
				}

				// check for custom thumbs || common extensions
				elseif(in_array($media->ext, $commonExts)) {
					$customUserIcon = $config->paths->assets . 'MediaManager/assets/' . $media->ext . '.png';
					if(is_file($customUserIcon)) $mediaThumbURL = $config->urls->assets . 'MediaManager/assets/' . $media->ext . '.png';// use user's custom thumb for this media format
					else $mediaThumbURL = $config->urls->ProcessMediaManager . 'assets/' . $media->ext . '.png';// use MM's custom thumb for this media format
				}

				else $mediaThumbURL = $this->genericThumb($mediaTypeStr);// use generic icon

				$a[$r->id]['url'] 		= $media->url;
				$a[$r->id]['url_thumb'] = $mediaThumbURL;
				$a[$r->id]['basename'] 	= $media->basename;
				$a[$r->id]['type'] 		= $mediaTypeStr;
				$a[$r->id]['ext'] 		= $media->ext;

				// @note: additional for list-view
				$a[$r->id]['tags'] 		= $media->tags;
				$a[$r->id]['description'] 	= $media->description;
				$a[$r->id]['width'] 	= $mediaTypeStr == 'image' ? $media->width . 'px' : '';
				$a[$r->id]['height'] 	= $mediaTypeStr == 'image' ? $media->height . 'px' : '';
				$a[$r->id]['size'] 		= $media->filesizeStr;

			}// end if count $r->$f

		}// end foreach $results

		return $a;

	}

	/**
	 * Find image variations for a given image media.
	 *
	 * Variations are images cropped or resized within the media page itself using ProcessWire.
	 *
	 * @access public
	 * @param $mediaPage Page Object An image media page that may have image variations.
	 * @return $variations Array Array with set properties of image variations.
	 *
	 */
	public function prepareImageVariations($mediaPage) {

		$currentPage = $this->currentPage;
		$currentField = $this->currentField;// the (specified/called) media manager field on the current page being edited

		$variations = array();

		$images = $mediaPage->media_manager_image;
		$originalImage = $images->first();

		if(!$originalImage) return $variations;

		$ext = '.' . $originalImage->ext;
		$originalBasename = $originalImage->basename;

		// get the version/variation image basename (although we expect only 1 image with variations in this field, you never know)
		// PW names variations as 'original_image_name-vxx.ext' where 'xx' is the $version
		$versionBasenameFilter = str_replace($ext, '', $originalBasename) . '-v';// returns 'original_image_name-v'

		// making sure we only grab the original images variations, nothing more
		$filtered = $images->filter('sort=basename, basename%='. $versionBasenameFilter);// WireArray of image variations

		if($filtered->count()) {

			$typePrefix = '3';// dealing with images

			// image objects
			foreach ($filtered as $imgV) {
				// grab version integer. We'll add this to type
				$ver = str_replace(array($versionBasenameFilter, $ext), '', $imgV->basename);
				$type = (int) $typePrefix . $ver;
				$mediaThumbURL = $imgV->size(100,75)->url;
				#$mediaLargeURL = $imgV->url;
				$mediaURL = $imgV->url;

				$variations[$ver]['url_thumb'] = $mediaThumbURL;// for thumb
				$variations[$ver]['url'] = $mediaURL;// for pop-up
				$variations[$ver]['basename'] = $imgV->basename;
				$variations[$ver]['version_number'] = $ver;
				$variations[$ver]['type'] = $type;
				$variations[$ver]['description'] = $imgV->description;
				$variations[$ver]['tags'] = $imgV->tags;
				$variations[$ver]['width'] = $imgV->width;
				$variations[$ver]['height'] = $imgV->height;
				$variations[$ver]['file_size'] = $imgV->filesizeStr;

				$class = $currentPage && $currentPage->$currentField->has("id=$mediaPage->id, type=$type") ? "in_field" : '';
				$variations[$ver]['in_field'] = $class;

			}

		}

		return $variations;

	}

	/**
	 * Count the number of variations for a single image media.
	 *
	 * Variations are images cropped or resized within the media page itself using ProcessWire.
	 *
	 * @access public
	 * @param $mediaPage Page Object An image media page that may have image variations.
	 * @return $count Integer Number of found variations, otherwise 0.
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

	/**
	 * Get and sanitize URL Segments ready for various logic/operations.
	 *
	 * @access private
	 * @return $urlSegments Array with URL segments information.
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
	 * Count the number of times a media item has been used across all pages.
	 *
	 * These are non-RTE counts.
	 * We use raw sql for the count.
	 *
	 * @access public
	 * @param $mediaID Int ID of a media item.
	 * @return $usedCnt Int The number of times a media item is in use.
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
	 * Determines and sets if necessary, the active mode (view vs edit) for media.
	 *
	 * Gets cookie set in ProcessMediaManager.js to determine view/edit mode.
	 *
	 * @access public
	 * @return $mode String.
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
	 *Returns allowed mime_types for a media_manager_image field allowed file extensions.
	 *
	 * We pass these to jqueryFileUpload and actionCreateMedia().
	 * These are for validating both uploaded and scanned image files.
	 *
	 * @access public
	 * @return $imageMimeTypes Array of allowed mime_types.
	 *
	 */
	public function allowedImageMimeTypes() {

		$imageMimeTypes = array();

		// grab allowed image media/file extensions (array)
		$imageExts = $this->validExtensionsImage();

		// find corresponding mime_type(s) for each allowed image file extension
		foreach ($imageExts as $ext) $allowedImageMimeTypes[] = $this->mimeTypes($ext);

		foreach ($allowedImageMimeTypes as $key => $value) {
			foreach($value as $v )$imageMimeTypes[] = $v;
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
	 * @return $imageMimeTypes Array of allowed mime_types
	 *
	 */
	public function allowedNonImageMimeTypes() {

		$nonImageMimeTypes = array();

		// grab allowed image media/file extensions (array)
		$nonImageExts = array_merge($this->validExtensionsAudio(), $this->validExtensionsDocument(), $this->validExtensionsVideo());

		// find corresponding mime_type(s) for each allowed non-image file extension
		foreach ($nonImageExts as $ext) $allowedNonImageMimeTypes[] = $this->mimeTypes($ext);

		foreach ($allowedNonImageMimeTypes as $key => $value) {
			foreach($value as $v )$nonImageMimeTypes[] = $v;
		}

		return $nonImageMimeTypes;

	}

	/**
	 * Return mime_types for a given file extension.
	 *
	 * Extensions are the index in the 2-D array of extensions => array('mime_types')
	 *
	 * @access public
	 * @see https://gist.github.com/nimasdj/801b0b1a50112ea6a997 @note we have modified these array with additional types.
	 * @param $index String File extension whose mime_type we wish to return.
	 * @return $mimeType Array The corresponding mime_type(s) of the given file extension.
	 *
	 */
	public 	function mimeTypes($index = null) {

		$mimeType = array();

		$mime_types = array('3dm'=>array('x-world/x-3dmf'),'3dmf'=>array('x-world/x-3dmf'),'3dml'=>array('text/vnd.in3d.3dml'),'3ds'=>array('image/x-3ds'),'3g2'=>array('video/3gpp2'),'3gp'=>array('video/3gpp'),'7z'=>array('application/x-7z-compressed'),'a'=>array('application/octet-stream'),'aab'=>array('application/x-authorware-bin'),'aac'=>array('audio/x-aac'),'aam'=>array('application/x-authorware-map'),'aas'=>array('application/x-authorware-seg'),'abc'=>array('text/vnd.abc'),'abw'=>array('application/x-abiword'),'ac'=>array('application/pkix-attr-cert'),'acc'=>array('application/vnd.americandynamics.acc'),'ace'=>array('application/x-ace-compressed'),'acgi'=>array('text/html'),'acu'=>array('application/vnd.acucobol'),'acutc'=>array('application/vnd.acucorp'),'adp'=>array('audio/adpcm'),'aep'=>array('application/vnd.audiograph'),'afl'=>array('video/animaflex'),'afm'=>array('application/x-font-type1'),'afp'=>array('application/vnd.ibm.modcap'),'ahead'=>array('application/vnd.ahead.space'),'ai'=>array('application/postscript'),'aif'=>array('audio/aiff','audio/x-aiff'),'aifc'=>array('audio/aiff','audio/x-aiff'),'aiff'=>array('audio/aiff','audio/x-aiff'),'aim'=>array('application/x-aim'),'aip'=>array('text/x-audiosoft-intra'),'air'=>array('application/vnd.adobe.air-application-installer-package+zip'),'ait'=>array('application/vnd.dvb.ait'),'ami'=>array('application/vnd.amiga.ami'),'ani'=>array('application/x-navi-animation'),'aos'=>array('application/x-nokia-9000-communicator-add-on-software'),'apk'=>array('application/vnd.android.package-archive'),'appcache'=>array('text/cache-manifest'),'application'=>array('application/x-ms-application'),'apr'=>array('application/vnd.lotus-approach'),'aps'=>array('application/mime'),'arc'=>array('application/x-freearc'),'arj'=>array('application/arj','application/octet-stream'),'art'=>array('image/x-jg'),'asc'=>array('application/pgp-signature'),'asf'=>array('video/x-ms-asf'),'asm'=>array('text/x-asm'),'aso'=>array('application/vnd.accpac.simply.aso'),'asp'=>array('text/asp'),'asx'=>array('application/x-mplayer2','video/x-ms-asf','video/x-ms-asf-plugin'),'atc'=>array('application/vnd.acucorp'),'atom'=>array('application/atom+xml'),'atomcat'=>array('application/atomcat+xml'),'atomsvc'=>array('application/atomsvc+xml'),'atx'=>array('application/vnd.antix.game-component'),'au'=>array('audio/basic'),'avi'=>array('application/x-troff-msvideo','video/avi','video/msvideo','video/x-msvideo'),'avs'=>array('video/avs-video'),'aw'=>array('application/applixware'),'azf'=>array('application/vnd.airzip.filesecure.azf'),'azs'=>array('application/vnd.airzip.filesecure.azs'),'azw'=>array('application/vnd.amazon.ebook'),'bat'=>array('application/x-msdownload'),'bcpio'=>array('application/x-bcpio'),'bdf'=>array('application/x-font-bdf'),'bdm'=>array('application/vnd.syncml.dm+wbxml'),'bed'=>array('application/vnd.realvnc.bed'),'bh2'=>array('application/vnd.fujitsu.oasysprs'),'bin'=>array('application/mac-binary','application/macbinary','application/octet-stream','application/x-binary','application/x-macbinary'),'blb'=>array('application/x-blorb'),'blorb'=>array('application/x-blorb'),'bm'=>array('image/bmp'),'bmi'=>array('application/vnd.bmi'),'bmp'=>array('image/bmp','image/x-windows-bmp'),'boo'=>array('application/book'),'book'=>array('application/vnd.framemaker'),'box'=>array('application/vnd.previewsystems.box'),'boz'=>array('application/x-bzip2'),'bpk'=>array('application/octet-stream'),'bsh'=>array('application/x-bsh'),'btif'=>array('image/prs.btif'),'buffer'=>array('application/octet-stream'),'bz'=>array('application/x-bzip'),'bz2'=>array('application/x-bzip2'),'c'=>array('text/x-c'),'c++'=>array('text/plain'),'c11amc'=>array('application/vnd.cluetrust.cartomobile-config'),'c11amz'=>array('application/vnd.cluetrust.cartomobile-config-pkg'),'c4d'=>array('application/vnd.clonk.c4group'),'c4f'=>array('application/vnd.clonk.c4group'),'c4g'=>array('application/vnd.clonk.c4group'),'c4p'=>array('application/vnd.clonk.c4group'),'c4u'=>array('application/vnd.clonk.c4group'),'cab'=>array('application/vnd.ms-cab-compressed'),'caf'=>array('audio/x-caf'),'cap'=>array('application/vnd.tcpdump.pcap'),'car'=>array('application/vnd.curl.car'),'cat'=>array('application/vnd.ms-pki.seccat'),'cb7'=>array('application/x-cbr'),'cba'=>array('application/x-cbr'),'cbr'=>array('application/x-cbr'),'cbt'=>array('application/x-cbr'),'cbz'=>array('application/x-cbr'),'cc'=>array('text/plain','text/x-c'),'ccad'=>array('application/clariscad'),'cco'=>array('application/x-cocoa'),'cct'=>array('application/x-director'),'ccxml'=>array('application/ccxml+xml'),'cdbcmsg'=>array('application/vnd.contact.cmsg'),'cdf'=>array('application/cdf','application/x-cdf','application/x-netcdf'),'cdkey'=>array('application/vnd.mediastation.cdkey'),'cdmia'=>array('application/cdmi-capability'),'cdmic'=>array('application/cdmi-container'),'cdmid'=>array('application/cdmi-domain'),'cdmio'=>array('application/cdmi-object'),'cdmiq'=>array('application/cdmi-queue'),'cdx'=>array('chemical/x-cdx'),'cdxml'=>array('application/vnd.chemdraw+xml'),'cdy'=>array('application/vnd.cinderella'),'cer'=>array('application/pkix-cert','application/x-x509-ca-cert'),'cfs'=>array('application/x-cfs-compressed'),'cgm'=>array('image/cgm'),'cha'=>array('application/x-chat'),'chat'=>array('application/x-chat'),'chm'=>array('application/vnd.ms-htmlhelp'),'chrt'=>array('application/vnd.kde.kchart'),'cif'=>array('chemical/x-cif'),'cii'=>array('application/vnd.anser-web-certificate-issue-initiation'),'cil'=>array('application/vnd.ms-artgalry'),'cla'=>array('application/vnd.claymore'),'class'=>array('application/java','application/java-byte-code','application/x-java-class'),'clkk'=>array('application/vnd.crick.clicker.keyboard'),'clkp'=>array('application/vnd.crick.clicker.palette'),'clkt'=>array('application/vnd.crick.clicker.template'),'clkw'=>array('application/vnd.crick.clicker.wordbank'),'clkx'=>array('application/vnd.crick.clicker'),'clp'=>array('application/x-msclip'),'cmc'=>array('application/vnd.cosmocaller'),'cmdf'=>array('chemical/x-cmdf'),'cml'=>array('chemical/x-cml'),'cmp'=>array('application/vnd.yellowriver-custom-menu'),'cmx'=>array('image/x-cmx'),'cod'=>array('application/vnd.rim.cod'),'com'=>array('application/octet-stream','text/plain'),'conf'=>array('text/plain'),'cpio'=>array('application/x-cpio'),'cpp'=>array('text/x-c'),'cpt'=>array('application/x-compactpro','application/x-cpt'),'crd'=>array('application/x-mscardfile'),'crl'=>array('application/pkcs-crl','application/pkix-crl'),'crt'=>array('application/pkix-cert','application/x-x509-ca-cert','application/x-x509-user-cert'),'crx'=>array('application/x-chrome-extension'),'cryptonote'=>array('application/vnd.rig.cryptonote'),'csh'=>array('application/x-csh','text/x-script.csh'),'csml'=>array('chemical/x-csml'),'csp'=>array('application/vnd.commonspace'),'css'=>array('application/x-pointplus','text/css'),'cst'=>array('application/x-director'),'csv'=>array('text/csv'),'cu'=>array('application/cu-seeme'),'curl'=>array('text/vnd.curl'),'cww'=>array('application/prs.cww'),'cxt'=>array('application/x-director'),'cxx'=>array('text/x-c'),'dae'=>array('model/vnd.collada+xml'),'daf'=>array('application/vnd.mobius.daf'),'dart'=>array('application/vnd.dart'),'dataless'=>array('application/vnd.fdsn.seed'),'davmount'=>array('application/davmount+xml'),'dbk'=>array('application/docbook+xml'),'dcr'=>array('application/x-director'),'dcurl'=>array('text/vnd.curl.dcurl'),'dd2'=>array('application/vnd.oma.dd2+xml'),'ddd'=>array('application/vnd.fujixerox.ddd'),'deb'=>array('application/x-debian-package'),'deepv'=>array('application/x-deepv'),'def'=>array('text/plain'),'deploy'=>array('application/octet-stream'),'der'=>array('application/x-x509-ca-cert'),'dfac'=>array('application/vnd.dreamfactory'),'dgc'=>array('application/x-dgc-compressed'),'dic'=>array('text/x-c'),'dif'=>array('video/x-dv'),'diff'=>array('text/plain'),'dir'=>array('application/x-director'),'dis'=>array('application/vnd.mobius.dis'),'dist'=>array('application/octet-stream'),'distz'=>array('application/octet-stream'),'djv'=>array('image/vnd.djvu'),'djvu'=>array('image/vnd.djvu'),'dl'=>array('video/dl','video/x-dl'),'dll'=>array('application/x-msdownload'),'dmg'=>array('application/x-apple-diskimage'),'dmp'=>array('application/vnd.tcpdump.pcap'),'dms'=>array('application/octet-stream'),'dna'=>array('application/vnd.dna'),'doc'=>array('application/msword'),'docm'=>array('application/vnd.ms-word.document.macroenabled.12'),'docx'=>array('application/vnd.openxmlformats-officedocument.wordprocessingml.document'),'dot'=>array('application/msword'),'dotm'=>array('application/vnd.ms-word.template.macroenabled.12'),'dotx'=>array('application/vnd.openxmlformats-officedocument.wordprocessingml.template'),'dp'=>array('application/vnd.osgi.dp'),'dpg'=>array('application/vnd.dpgraph'),'dra'=>array('audio/vnd.dra'),'drw'=>array('application/drafting'),'dsc'=>array('text/prs.lines.tag'),'dssc'=>array('application/dssc+der'),'dtb'=>array('application/x-dtbook+xml'),'dtd'=>array('application/xml-dtd'),'dts'=>array('audio/vnd.dts'),'dtshd'=>array('audio/vnd.dts.hd'),'dump'=>array('application/octet-stream'),'dv'=>array('video/x-dv'),'dvb'=>array('video/vnd.dvb.file'),'dvi'=>array('application/x-dvi'),'dwf'=>array('drawing/x-dwf (old)','model/vnd.dwf'),'dwg'=>array('application/acad','image/vnd.dwg','image/x-dwg'),'dxf'=>array('image/vnd.dxf'),'dxp'=>array('application/vnd.spotfire.dxp'),'dxr'=>array('application/x-director'),'ecelp4800'=>array('audio/vnd.nuera.ecelp4800'),'ecelp7470'=>array('audio/vnd.nuera.ecelp7470'),'ecelp9600'=>array('audio/vnd.nuera.ecelp9600'),'ecma'=>array('application/ecmascript'),'edm'=>array('application/vnd.novadigm.edm'),'edx'=>array('application/vnd.novadigm.edx'),'efif'=>array('application/vnd.picsel'),'ei6'=>array('application/vnd.pg.osasli'),'el'=>array('text/x-script.elisp'),'elc'=>array('application/x-bytecode.elisp (compiled elisp)','application/x-elc'),'emf'=>array('application/x-msmetafile'),'eml'=>array('message/rfc822'),'emma'=>array('application/emma+xml'),'emz'=>array('application/x-msmetafile'),'env'=>array('application/x-envoy'),'eol'=>array('audio/vnd.digital-winds'),'eot'=>array('application/vnd.ms-fontobject'),'eps'=>array('application/postscript'),'epub'=>array('application/epub+zip'),'es'=>array('application/x-esrehber'),'es3'=>array('application/vnd.eszigno3+xml'),'esa'=>array('application/vnd.osgi.subsystem'),'esf'=>array('application/vnd.epson.esf'),'et3'=>array('application/vnd.eszigno3+xml'),'etx'=>array('text/x-setext'),'eva'=>array('application/x-eva'),'event-stream'=>array('text/event-stream'),'evy'=>array('application/envoy','application/x-envoy'),'exe'=>array('application/x-msdownload'),'exi'=>array('application/exi'),'ext'=>array('application/vnd.novadigm.ext'),'ez'=>array('application/andrew-inset'),'ez2'=>array('application/vnd.ezpix-album'),'ez3'=>array('application/vnd.ezpix-package'),'f'=>array('text/plain','text/x-fortran'),'f4v'=>array('video/x-f4v'),'f77'=>array('text/x-fortran'),'f90'=>array('text/plain','text/x-fortran'),'fbs'=>array('image/vnd.fastbidsheet'),'fcdt'=>array('application/vnd.adobe.formscentral.fcdt'),'fcs'=>array('application/vnd.isac.fcs'),'fdf'=>array('application/vnd.fdf'),'fe_launch'=>array('application/vnd.denovo.fcselayout-link'),'fg5'=>array('application/vnd.fujitsu.oasysgp'),'fgd'=>array('application/x-director'),'fh'=>array('image/x-freehand'),'fh4'=>array('image/x-freehand'),'fh5'=>array('image/x-freehand'),'fh7'=>array('image/x-freehand'),'fhc'=>array('image/x-freehand'),'fif'=>array('application/fractals','image/fif'),'fig'=>array('application/x-xfig'),'flac'=>array('audio/flac','audio/x-flac'),'fli'=>array('video/fli','video/x-fli'),'flo'=>array('application/vnd.micrografx.flo'),'flv'=>array('video/x-flv'),'flw'=>array('application/vnd.kde.kivio'),'flx'=>array('text/vnd.fmi.flexstor'),'fly'=>array('text/vnd.fly'),'fm'=>array('application/vnd.framemaker'),'fmf'=>array('video/x-atomic3d-feature'),'fnc'=>array('application/vnd.frogans.fnc'),'for'=>array('text/plain','text/x-fortran'),'fpx'=>array('image/vnd.fpx','image/vnd.net-fpx'),'frame'=>array('application/vnd.framemaker'),'frl'=>array('application/freeloader'),'fsc'=>array('application/vnd.fsc.weblaunch'),'fst'=>array('image/vnd.fst'),'ftc'=>array('application/vnd.fluxtime.clip'),'fti'=>array('application/vnd.anser-web-funds-transfer-initiation'),'funk'=>array('audio/make'),'fvt'=>array('video/vnd.fvt'),'fxp'=>array('application/vnd.adobe.fxp'),'fxpl'=>array('application/vnd.adobe.fxp'),'fzs'=>array('application/vnd.fuzzysheet'),'g'=>array('text/plain'),'g2w'=>array('application/vnd.geoplan'),'g3'=>array('image/g3fax'),'g3w'=>array('application/vnd.geospace'),'gac'=>array('application/vnd.groove-account'),'gam'=>array('application/x-tads'),'gbr'=>array('application/rpki-ghostbusters'),'gca'=>array('application/x-gca-compressed'),'gdl'=>array('model/vnd.gdl'),'geo'=>array('application/vnd.dynageo'),'gex'=>array('application/vnd.geometry-explorer'),'ggb'=>array('application/vnd.geogebra.file'),'ggt'=>array('application/vnd.geogebra.tool'),'ghf'=>array('application/vnd.groove-help'),'gif'=>array('image/gif'),'gim'=>array('application/vnd.groove-identity-message'),'gl'=>array('video/gl','video/x-gl'),'gml'=>array('application/gml+xml'),'gmx'=>array('application/vnd.gmx'),'gnumeric'=>array('application/x-gnumeric'),'gph'=>array('application/vnd.flographit'),'gpx'=>array('application/gpx+xml'),'gqf'=>array('application/vnd.grafeq'),'gqs'=>array('application/vnd.grafeq'),'gram'=>array('application/srgs'),'gramps'=>array('application/x-gramps-xml'),'gre'=>array('application/vnd.geometry-explorer'),'grv'=>array('application/vnd.groove-injector'),'grxml'=>array('application/srgs+xml'),'gsd'=>array('audio/x-gsm'),'gsf'=>array('application/x-font-ghostscript'),'gsm'=>array('audio/x-gsm'),'gsp'=>array('application/x-gsp'),'gss'=>array('application/x-gss'),'gtar'=>array('application/x-gtar'),'gtm'=>array('application/vnd.groove-tool-message'),'gtw'=>array('model/vnd.gtw'),'gv'=>array('text/vnd.graphviz'),'gxf'=>array('application/gxf'),'gxt'=>array('application/vnd.geonext'),'gz'=>array('application/x-compressed','application/x-gzip'),'gzip'=>array('application/x-gzip','multipart/x-gzip'),'h'=>array('text/plain','text/x-h'),'h261'=>array('video/h261'),'h263'=>array('video/h263'),'h264'=>array('video/h264'),'hal'=>array('application/vnd.hal+xml'),'hbci'=>array('application/vnd.hbci'),'hdf'=>array('application/x-hdf'),'help'=>array('application/x-helpfile'),'hgl'=>array('application/vnd.hp-hpgl'),'hh'=>array('text/plain','text/x-h'),'hlb'=>array('text/x-script'),'hlp'=>array('application/hlp','application/x-helpfile','application/x-winhelp'),'hpg'=>array('application/vnd.hp-hpgl'),'hpgl'=>array('application/vnd.hp-hpgl'),'hpid'=>array('application/vnd.hp-hpid'),'hps'=>array('application/vnd.hp-hps'),'hqx'=>array('application/binhex','application/binhex4','application/mac-binhex','application/mac-binhex40','application/x-binhex40','application/x-mac-binhex40'),'hta'=>array('application/hta'),'htc'=>array('text/x-component'),'htke'=>array('application/vnd.kenameaapp'),'htm'=>array('text/html'),'html'=>array('text/html'),'htmls'=>array('text/html'),'htt'=>array('text/webviewhtml'),'htx'=>array('text/html'),'hvd'=>array('application/vnd.yamaha.hv-dic'),'hvp'=>array('application/vnd.yamaha.hv-voice'),'hvs'=>array('application/vnd.yamaha.hv-script'),'i2g'=>array('application/vnd.intergeo'),'icc'=>array('application/vnd.iccprofile'),'ice'=>array('x-conference/x-cooltalk'),'icm'=>array('application/vnd.iccprofile'),'ico'=>array('image/x-icon'),'ics'=>array('text/calendar'),'idc'=>array('text/plain'),'ief'=>array('image/ief'),'iefs'=>array('image/ief'),'ifb'=>array('text/calendar'),'ifm'=>array('application/vnd.shana.informed.formdata'),'iges'=>array('application/iges','model/iges'),'igl'=>array('application/vnd.igloader'),'igm'=>array('application/vnd.insors.igm'),'igs'=>array('application/iges','model/iges'),'igx'=>array('application/vnd.micrografx.igx'),'iif'=>array('application/vnd.shana.informed.interchange'),'ima'=>array('application/x-ima'),'imap'=>array('application/x-httpd-imap'),'imp'=>array('application/vnd.accpac.simply.imp'),'ims'=>array('application/vnd.ms-ims'),'in'=>array('text/plain'),'inf'=>array('application/inf'),'ink'=>array('application/inkml+xml'),'inkml'=>array('application/inkml+xml'),'ins'=>array('application/x-internett-signup'),'install'=>array('application/x-install-instructions'),'iota'=>array('application/vnd.astraea-software.iota'),'ip'=>array('application/x-ip2'),'ipfix'=>array('application/ipfix'),'ipk'=>array('application/vnd.shana.informed.package'),'irm'=>array('application/vnd.ibm.rights-management'),'irp'=>array('application/vnd.irepository.package+xml'),'iso'=>array('application/x-iso9660-image'),'isu'=>array('video/x-isvideo'),'it'=>array('audio/it'),'itp'=>array('application/vnd.shana.informed.formtemplate'),'iv'=>array('application/x-inventor'),'ivp'=>array('application/vnd.immervision-ivp'),'ivr'=>array('i-world/i-vrml'),'ivu'=>array('application/vnd.immervision-ivu'),'ivy'=>array('application/x-livescreen'),'jad'=>array('text/vnd.sun.j2me.app-descriptor'),'jam'=>array('application/vnd.jam'),'jar'=>array('application/java-archive'),'jav'=>array('text/plain','text/x-java-source'),'java'=>array('text/plain','text/x-java-source'),'jcm'=>array('application/x-java-commerce'),'jfif'=>array('image/jpeg','image/pjpeg'),'jfif-tbnl'=>array('image/jpeg'),'jisp'=>array('application/vnd.jisp'),'jlt'=>array('application/vnd.hp-jlyt'),'jnlp'=>array('application/x-java-jnlp-file'),'joda'=>array('application/vnd.joost.joda-archive'),'jpe'=>array('image/jpeg','image/pjpeg'),'jpeg'=>array('image/jpeg','image/pjpeg'),'jpg'=>array('image/jpeg','image/pjpeg'),'jpgm'=>array('video/jpm'),'jpgv'=>array('video/jpeg'),'jpm'=>array('video/jpm'),'jps'=>array('image/x-jps'),'js'=>array('application/javascript'),'json'=>array('application/json','text/plain'),'jsonml'=>array('application/jsonml+json'),'jut'=>array('image/jutvision'),'kar'=>array('audio/midi','music/x-karaoke'),'karbon'=>array('application/vnd.kde.karbon'),'kfo'=>array('application/vnd.kde.kformula'),'kia'=>array('application/vnd.kidspiration'),'kil'=>array('application/x-killustrator'),'kml'=>array('application/vnd.google-earth.kml+xml'),'kmz'=>array('application/vnd.google-earth.kmz'),'kne'=>array('application/vnd.kinar'),'knp'=>array('application/vnd.kinar'),'kon'=>array('application/vnd.kde.kontour'),'kpr'=>array('application/vnd.kde.kpresenter'),'kpt'=>array('application/vnd.kde.kpresenter'),'kpxx'=>array('application/vnd.ds-keypoint'),'ksh'=>array('application/x-ksh','text/x-script.ksh'),'ksp'=>array('application/vnd.kde.kspread'),'ktr'=>array('application/vnd.kahootz'),'ktx'=>array('image/ktx'),'ktz'=>array('application/vnd.kahootz'),'kwd'=>array('application/vnd.kde.kword'),'kwt'=>array('application/vnd.kde.kword'),'la'=>array('audio/nspaudio','audio/x-nspaudio'),'lam'=>array('audio/x-liveaudio'),'lasxml'=>array('application/vnd.las.las+xml'),'latex'=>array('application/x-latex'),'lbd'=>array('application/vnd.llamagraphics.life-balance.desktop'),'lbe'=>array('application/vnd.llamagraphics.life-balance.exchange+xml'),'les'=>array('application/vnd.hhe.lesson-player'),'lha'=>array('application/lha','application/octet-stream','application/x-lha'),'lhx'=>array('application/octet-stream'),'link66'=>array('application/vnd.route66.link66+xml'),'list'=>array('text/plain'),'list3820'=>array('application/vnd.ibm.modcap'),'listafp'=>array('application/vnd.ibm.modcap'),'lma'=>array('audio/nspaudio','audio/x-nspaudio'),'lnk'=>array('application/x-ms-shortcut'),'log'=>array('text/plain'),'lostxml'=>array('application/lost+xml'),'lrf'=>array('application/octet-stream'),'lrm'=>array('application/vnd.ms-lrm'),'lsp'=>array('application/x-lisp','text/x-script.lisp'),'lst'=>array('text/plain'),'lsx'=>array('text/x-la-asf'),'ltf'=>array('application/vnd.frogans.ltf'),'ltx'=>array('application/x-latex'),'lua'=>array('text/x-lua'),'luac'=>array('application/x-lua-bytecode'),'lvp'=>array('audio/vnd.lucent.voice'),'lwp'=>array('application/vnd.lotus-wordpro'),'lzh'=>array('application/octet-stream','application/x-lzh'),'lzx'=>array('application/lzx','application/octet-stream','application/x-lzx'),'m'=>array('text/plain','text/x-m'),'m13'=>array('application/x-msmediaview'),'m14'=>array('application/x-msmediaview'),'m1v'=>array('video/mpeg'),'m21'=>array('application/mp21'),'m2a'=>array('audio/mpeg'),'m2v'=>array('video/mpeg'),'m3a'=>array('audio/mpeg'),'m3u'=>array('audio/x-mpegurl'),'m3u8'=>array('application/x-mpegURL'),'m4a'=>array('audio/mp4'),'m4p'=>array('application/mp4'),'m4u'=>array('video/vnd.mpegurl'),'m4v'=>array('video/x-m4v'),'ma'=>array('application/mathematica'),'mads'=>array('application/mads+xml'),'mag'=>array('application/vnd.ecowin.chart'),'maker'=>array('application/vnd.framemaker'),'man'=>array('text/troff'),'manifest'=>array('text/cache-manifest'),'map'=>array('application/x-navimap'),'mar'=>array('application/octet-stream'),'markdown'=>array('text/x-markdown'),'mathml'=>array('application/mathml+xml'),'mb'=>array('application/mathematica'),'mbd'=>array('application/mbedlet'),'mbk'=>array('application/vnd.mobius.mbk'),'mbox'=>array('application/mbox'),'mc'=>array('application/x-magic-cap-package-1.0'),'mc1'=>array('application/vnd.medcalcdata'),'mcd'=>array('application/mcad','application/x-mathcad'),'mcf'=>array('image/vasa','text/mcf'),'mcp'=>array('application/netmc'),'mcurl'=>array('text/vnd.curl.mcurl'),'md'=>array('text/x-markdown'),'mdb'=>array('application/x-msaccess'),'mdi'=>array('image/vnd.ms-modi'),'me'=>array('text/troff'),'mesh'=>array('model/mesh'),'meta4'=>array('application/metalink4+xml'),'metalink'=>array('application/metalink+xml'),'mets'=>array('application/mets+xml'),'mfm'=>array('application/vnd.mfmp'),'mft'=>array('application/rpki-manifest'),'mgp'=>array('application/vnd.osgeo.mapguide.package'),'mgz'=>array('application/vnd.proteus.magazine'),'mht'=>array('message/rfc822'),'mhtml'=>array('message/rfc822'),'mid'=>array('application/x-midi','audio/midi','audio/x-mid','audio/x-midi','music/crescendo','x-music/x-midi'),'midi'=>array('application/x-midi','audio/midi','audio/x-mid','audio/x-midi','music/crescendo','x-music/x-midi'),'mie'=>array('application/x-mie'),'mif'=>array('application/x-frame','application/x-mif'),'mime'=>array('message/rfc822','www/mime'),'mj2'=>array('video/mj2'),'mjf'=>array('audio/x-vnd.audioexplosion.mjuicemediafile'),'mjp2'=>array('video/mj2'),'mjpg'=>array('video/x-motion-jpeg'),'mk3d'=>array('video/x-matroska'),'mka'=>array('audio/x-matroska'),'mkd'=>array('text/x-markdown'),'mks'=>array('video/x-matroska'),'mkv'=>array('video/x-matroska'),'mlp'=>array('application/vnd.dolby.mlp'),'mm'=>array('application/base64','application/x-meme'),'mmd'=>array('application/vnd.chipnuts.karaoke-mmd'),'mme'=>array('application/base64'),'mmf'=>array('application/vnd.smaf'),'mmr'=>array('image/vnd.fujixerox.edmics-mmr'),'mng'=>array('video/x-mng'),'mny'=>array('application/x-msmoney'),'mobi'=>array('application/x-mobipocket-ebook'),'mod'=>array('audio/mod','audio/x-mod'),'mods'=>array('application/mods+xml'),'moov'=>array('video/quicktime'),'mov'=>array('video/quicktime'),'movie'=>array('video/x-sgi-movie'),'mp2'=>array('audio/mpeg','audio/x-mpeg','video/mpeg','video/x-mpeg','video/x-mpeq2a'),'mp21'=>array('application/mp21'),'mp2a'=>array('audio/mpeg'),'mp3'=>array('audio/mpeg', 'audio/mpeg3','audio/x-mpeg-3','video/mpeg','video/x-mpeg'),'mp4'=>array('video/mp4'),'mp4a'=>array('audio/mp4'),'mp4s'=>array('application/mp4'),'mp4v'=>array('video/mp4'),'mpa'=>array('audio/mpeg','video/mpeg'),'mpc'=>array('application/vnd.mophun.certificate'),'mpe'=>array('video/mpeg'),'mpeg'=>array('video/mpeg'),'mpg'=>array('audio/mpeg','video/mpeg'),'mpg4'=>array('video/mp4'),'mpga'=>array('audio/mpeg'),'mpkg'=>array('application/vnd.apple.installer+xml'),'mpm'=>array('application/vnd.blueice.multipass'),'mpn'=>array('application/vnd.mophun.application'),'mpp'=>array('application/vnd.ms-project'),'mpt'=>array('application/vnd.ms-project'),'mpv'=>array('application/x-project'),'mpx'=>array('application/x-project'),'mpy'=>array('application/vnd.ibm.minipay'),'mqy'=>array('application/vnd.mobius.mqy'),'mrc'=>array('application/marc'),'mrcx'=>array('application/marcxml+xml'),'ms'=>array('text/troff'),'mscml'=>array('application/mediaservercontrol+xml'),'mseed'=>array('application/vnd.fdsn.mseed'),'mseq'=>array('application/vnd.mseq'),'msf'=>array('application/vnd.epson.msf'),'msh'=>array('model/mesh'),'msi'=>array('application/x-msdownload'),'msl'=>array('application/vnd.mobius.msl'),'msty'=>array('application/vnd.muvee.style'),'mts'=>array('model/vnd.mts'),'mus'=>array('application/vnd.musician'),'musicxml'=>array('application/vnd.recordare.musicxml+xml'),'mv'=>array('video/x-sgi-movie'),'mvb'=>array('application/x-msmediaview'),'mwf'=>array('application/vnd.mfer'),'mxf'=>array('application/mxf'),'mxl'=>array('application/vnd.recordare.musicxml'),'mxml'=>array('application/xv+xml'),'mxs'=>array('application/vnd.triscape.mxs'),'mxu'=>array('video/vnd.mpegurl'),'my'=>array('audio/make'),'mzz'=>array('application/x-vnd.audioexplosion.mzz'),'n-gage'=>array('application/vnd.nokia.n-gage.symbian.install'),'n3'=>array('text/n3'),'nap'=>array('image/naplps'),'naplps'=>array('image/naplps'),'nb'=>array('application/mathematica'),'nbp'=>array('application/vnd.wolfram.player'),'nc'=>array('application/x-netcdf'),'ncm'=>array('application/vnd.nokia.configuration-message'),'ncx'=>array('application/x-dtbncx+xml'),'nfo'=>array('text/x-nfo'),'ngdat'=>array('application/vnd.nokia.n-gage.data'),'nif'=>array('image/x-niff'),'niff'=>array('image/x-niff'),'nitf'=>array('application/vnd.nitf'),'nix'=>array('application/x-mix-transfer'),'nlu'=>array('application/vnd.neurolanguage.nlu'),'nml'=>array('application/vnd.enliven'),'nnd'=>array('application/vnd.noblenet-directory'),'nns'=>array('application/vnd.noblenet-sealer'),'nnw'=>array('application/vnd.noblenet-web'),'npx'=>array('image/vnd.net-fpx'),'nsc'=>array('application/x-conference'),'nsf'=>array('application/vnd.lotus-notes'),'ntf'=>array('application/vnd.nitf'),'nvd'=>array('application/x-navidoc'),'nws'=>array('message/rfc822'),'nzb'=>array('application/x-nzb'),'o'=>array('application/octet-stream'),'oa2'=>array('application/vnd.fujitsu.oasys2'),'oa3'=>array('application/vnd.fujitsu.oasys3'),'oas'=>array('application/vnd.fujitsu.oasys'),'obd'=>array('application/x-msbinder'),'obj'=>array('application/x-tgif'),'oda'=>array('application/oda'),'odb'=>array('application/vnd.oasis.opendocument.database'),'odc'=>array('application/vnd.oasis.opendocument.chart'),'odf'=>array('application/vnd.oasis.opendocument.formula'),'odft'=>array('application/vnd.oasis.opendocument.formula-template'),'odg'=>array('application/vnd.oasis.opendocument.graphics'),'odi'=>array('application/vnd.oasis.opendocument.image'),'odm'=>array('application/vnd.oasis.opendocument.text-master'),'odp'=>array('application/vnd.oasis.opendocument.presentation'),'ods'=>array('application/vnd.oasis.opendocument.spreadsheet'),'odt'=>array('application/vnd.oasis.opendocument.text'),'oga'=>array('audio/ogg'),'ogg'=>array('audio/ogg'),'ogv'=>array('video/ogg'),'ogx'=>array('application/ogg'),'omc'=>array('application/x-omc'),'omcd'=>array('application/x-omcdatamaker'),'omcr'=>array('application/x-omcregerator'),'omdoc'=>array('application/omdoc+xml'),'onepkg'=>array('application/onenote'),'onetmp'=>array('application/onenote'),'onetoc'=>array('application/onenote'),'onetoc2'=>array('application/onenote'),'opf'=>array('application/oebps-package+xml'),'opml'=>array('text/x-opml'),'oprc'=>array('application/vnd.palm'),'org'=>array('application/vnd.lotus-organizer'),'osf'=>array('application/vnd.yamaha.openscoreformat'),'osfpvg'=>array('application/vnd.yamaha.openscoreformat.osfpvg+xml'),'otc'=>array('application/vnd.oasis.opendocument.chart-template'),'otf'=>array('font/opentype'),'otg'=>array('application/vnd.oasis.opendocument.graphics-template'),'oth'=>array('application/vnd.oasis.opendocument.text-web'),'oti'=>array('application/vnd.oasis.opendocument.image-template'),'otm'=>array('application/vnd.oasis.opendocument.text-master'),'otp'=>array('application/vnd.oasis.opendocument.presentation-template'),'ots'=>array('application/vnd.oasis.opendocument.spreadsheet-template'),'ott'=>array('application/vnd.oasis.opendocument.text-template'),'oxps'=>array('application/oxps'),'oxt'=>array('application/vnd.openofficeorg.extension'),'p'=>array('text/x-pascal'),'p10'=>array('application/pkcs10','application/x-pkcs10'),'p12'=>array('application/pkcs-12','application/x-pkcs12'),'p7a'=>array('application/x-pkcs7-signature'),'p7b'=>array('application/x-pkcs7-certificates'),'p7c'=>array('application/pkcs7-mime','application/x-pkcs7-mime'),'p7m'=>array('application/pkcs7-mime','application/x-pkcs7-mime'),'p7r'=>array('application/x-pkcs7-certreqresp'),'p7s'=>array('application/pkcs7-signature'),'p8'=>array('application/pkcs8'),'part'=>array('application/pro_eng'),'pas'=>array('text/x-pascal'),'paw'=>array('application/vnd.pawaafile'),'pbd'=>array('application/vnd.powerbuilder6'),'pbm'=>array('image/x-portable-bitmap'),'pcap'=>array('application/vnd.tcpdump.pcap'),'pcf'=>array('application/x-font-pcf'),'pcl'=>array('application/vnd.hp-pcl','application/x-pcl'),'pclxl'=>array('application/vnd.hp-pclxl'),'pct'=>array('image/x-pict'),'pcurl'=>array('application/vnd.curl.pcurl'),'pcx'=>array('image/x-pcx'),'pdb'=>array('application/vnd.palm'),'pdf'=>array('application/pdf'),'pfa'=>array('application/x-font-type1'),'pfb'=>array('application/x-font-type1'),'pfm'=>array('application/x-font-type1'),'pfr'=>array('application/font-tdpfr'),'pfunk'=>array('audio/make'),'pfx'=>array('application/x-pkcs12'),'pgm'=>array('image/x-portable-graymap'),'pgn'=>array('application/x-chess-pgn'),'pgp'=>array('application/pgp-encrypted'),'php'=>array('text/x-php'),'pic'=>array('image/x-pict'),'pict'=>array('image/pict'),'pkg'=>array('application/octet-stream'),'pki'=>array('application/pkixcmp'),'pkipath'=>array('application/pkix-pkipath'),'pko'=>array('application/vnd.ms-pki.pko'),'pl'=>array('text/plain','text/x-script.perl'),'plb'=>array('application/vnd.3gpp.pic-bw-large'),'plc'=>array('application/vnd.mobius.plc'),'plf'=>array('application/vnd.pocketlearn'),'pls'=>array('application/pls+xml'),'plx'=>array('application/x-pixclscript'),'pm'=>array('image/x-xpixmap','text/x-script.perl-module'),'pm4'=>array('application/x-pagemaker'),'pm5'=>array('application/x-pagemaker'),'pml'=>array('application/vnd.ctc-posml'),'png'=>array('image/png'),'pnm'=>array('application/x-portable-anymap','image/x-portable-anymap'),'portpkg'=>array('application/vnd.macports.portpkg'),'pot'=>array('application/mspowerpoint','application/vnd.ms-powerpoint'),'potm'=>array('application/vnd.ms-powerpoint.template.macroenabled.12'),'potx'=>array('application/vnd.openxmlformats-officedocument.presentationml.template'),'pov'=>array('model/x-pov'),'ppa'=>array('application/vnd.ms-powerpoint'),'ppam'=>array('application/vnd.ms-powerpoint.addin.macroenabled.12'),'ppd'=>array('application/vnd.cups-ppd'),'ppm'=>array('image/x-portable-pixmap'),'pps'=>array('application/mspowerpoint','application/vnd.ms-powerpoint'),'ppsm'=>array('application/vnd.ms-powerpoint.slideshow.macroenabled.12'),'ppsx'=>array('application/vnd.openxmlformats-officedocument.presentationml.slideshow'),'ppt'=>array('application/mspowerpoint','application/powerpoint','application/vnd.ms-powerpoint','application/x-mspowerpoint'),'pptm'=>array('application/vnd.ms-powerpoint.presentation.macroenabled.12'),'pptx'=>array('application/vnd.openxmlformats-officedocument.presentationml.presentation'),'ppz'=>array('application/mspowerpoint'),'pqa'=>array('application/vnd.palm'),'prc'=>array('application/x-mobipocket-ebook'),'pre'=>array('application/vnd.lotus-freelance'),'prf'=>array('application/pics-rules'),'prt'=>array('application/pro_eng'),'ps'=>array('application/postscript'),'psb'=>array('application/vnd.3gpp.pic-bw-small'),'psd'=>array('image/vnd.adobe.photoshop'),'psf'=>array('application/x-font-linux-psf'),'pskcxml'=>array('application/pskc+xml'),'ptid'=>array('application/vnd.pvi.ptid1'),'pub'=>array('application/x-mspublisher'),'pvb'=>array('application/vnd.3gpp.pic-bw-var'),'pvu'=>array('paleovu/x-pv'),'pwn'=>array('application/vnd.3m.post-it-notes'),'pwz'=>array('application/vnd.ms-powerpoint'),'py'=>array('text/x-script.phyton'),'pya'=>array('audio/vnd.ms-playready.media.pya'),'pyc'=>array('applicaiton/x-bytecode.python'),'pyo'=>array('application/x-python-code'),'pyv'=>array('video/vnd.ms-playready.media.pyv'),'qam'=>array('application/vnd.epson.quickanime'),'qbo'=>array('application/vnd.intu.qbo'),'qcp'=>array('audio/vnd.qcelp'),'qd3'=>array('x-world/x-3dmf'),'qd3d'=>array('x-world/x-3dmf'),'qfx'=>array('application/vnd.intu.qfx'),'qif'=>array('image/x-quicktime'),'qps'=>array('application/vnd.publishare-delta-tree'),'qt'=>array('video/quicktime'),'qtc'=>array('video/x-qtc'),'qti'=>array('image/x-quicktime'),'qtif'=>array('image/x-quicktime'),'qwd'=>array('application/vnd.quark.quarkxpress'),'qwt'=>array('application/vnd.quark.quarkxpress'),'qxb'=>array('application/vnd.quark.quarkxpress'),'qxd'=>array('application/vnd.quark.quarkxpress'),'qxl'=>array('application/vnd.quark.quarkxpress'),'qxt'=>array('application/vnd.quark.quarkxpress'),'ra'=>array('audio/x-pn-realaudio','audio/x-pn-realaudio-plugin','audio/x-realaudio'),'ram'=>array('audio/x-pn-realaudio'),'rar'=>array('application/x-rar-compressed'),'ras'=>array('application/x-cmu-raster','image/cmu-raster','image/x-cmu-raster'),'rast'=>array('image/cmu-raster'),'rcprofile'=>array('application/vnd.ipunplugged.rcprofile'),'rdf'=>array('application/rdf+xml'),'rdz'=>array('application/vnd.data-vision.rdz'),'rep'=>array('application/vnd.businessobjects'),'res'=>array('application/x-dtbresource+xml'),'rexx'=>array('text/x-script.rexx'),'rf'=>array('image/vnd.rn-realflash'),'rgb'=>array('image/x-rgb'),'rif'=>array('application/reginfo+xml'),'rip'=>array('audio/vnd.rip'),'ris'=>array('application/x-research-info-systems'),'rl'=>array('application/resource-lists+xml'),'rlc'=>array('image/vnd.fujixerox.edmics-rlc'),'rld'=>array('application/resource-lists-diff+xml'),'rm'=>array('application/vnd.rn-realmedia','audio/x-pn-realaudio'),'rmi'=>array('audio/midi'),'rmm'=>array('audio/x-pn-realaudio'),'rmp'=>array('audio/x-pn-realaudio','audio/x-pn-realaudio-plugin'),'rms'=>array('application/vnd.jcp.javame.midlet-rms'),'rmvb'=>array('application/vnd.rn-realmedia-vbr'),'rnc'=>array('application/relax-ng-compact-syntax'),'rng'=>array('application/ringing-tones','application/vnd.nokia.ringing-tone'),'rnx'=>array('application/vnd.rn-realplayer'),'roa'=>array('application/rpki-roa'),'roff'=>array('text/troff'),'rp'=>array('image/vnd.rn-realpix'),'rp9'=>array('application/vnd.cloanto.rp9'),'rpm'=>array('audio/x-pn-realaudio-plugin'),'rpss'=>array('application/vnd.nokia.radio-presets'),'rpst'=>array('application/vnd.nokia.radio-preset'),'rq'=>array('application/sparql-query'),'rs'=>array('application/rls-services+xml'),'rsd'=>array('application/rsd+xml'),'rss'=>array('application/rss+xml'),'rt'=>array('text/richtext','text/vnd.rn-realtext'),'rtf'=>array('application/rtf','application/x-rtf','text/richtext'),'rtx'=>array('application/rtf','text/richtext'),'rv'=>array('video/vnd.rn-realvideo'),'s'=>array('text/x-asm'),'s3m'=>array('audio/s3m'),'saf'=>array('application/vnd.yamaha.smaf-audio'),'saveme'=>array('aapplication/octet-stream'),'sbk'=>array('application/x-tbook'),'sbml'=>array('application/sbml+xml'),'sc'=>array('application/vnd.ibm.secure-container'),'scd'=>array('application/x-msschedule'),'scm'=>array('application/x-lotusscreencam','text/x-script.guile','text/x-script.scheme','video/x-scm'),'scq'=>array('application/scvp-cv-request'),'scs'=>array('application/scvp-cv-response'),'scurl'=>array('text/vnd.curl.scurl'),'sda'=>array('application/vnd.stardivision.draw'),'sdc'=>array('application/vnd.stardivision.calc'),'sdd'=>array('application/vnd.stardivision.impress'),'sdkd'=>array('application/vnd.solent.sdkm+xml'),'sdkm'=>array('application/vnd.solent.sdkm+xml'),'sdml'=>array('text/plain'),'sdp'=>array('application/sdp','application/x-sdp'),'sdr'=>array('application/sounder'),'sdw'=>array('application/vnd.stardivision.writer'),'sea'=>array('application/sea','application/x-sea'),'see'=>array('application/vnd.seemail'),'seed'=>array('application/vnd.fdsn.seed'),'sema'=>array('application/vnd.sema'),'semd'=>array('application/vnd.semd'),'semf'=>array('application/vnd.semf'),'ser'=>array('application/java-serialized-object'),'set'=>array('application/set'),'setpay'=>array('application/set-payment-initiation'),'setreg'=>array('application/set-registration-initiation'),'sfd-hdstx'=>array('application/vnd.hydrostatix.sof-data'),'sfs'=>array('application/vnd.spotfire.sfs'),'sfv'=>array('text/x-sfv'),'sgi'=>array('image/sgi'),'sgl'=>array('application/vnd.stardivision.writer-global'),'sgm'=>array('text/sgml','text/x-sgml'),'sgml'=>array('text/sgml','text/x-sgml'),'sh'=>array('application/x-bsh','application/x-sh','application/x-shar','text/x-script.sh'),'shar'=>array('application/x-bsh','application/x-shar'),'shf'=>array('application/shf+xml'),'shtml'=>array('text/html','text/x-server-parsed-html'),'si'=>array('text/vnd.wap.si'),'sic'=>array('application/vnd.wap.sic'),'sid'=>array('image/x-mrsid-image'),'sig'=>array('application/pgp-signature'),'sil'=>array('audio/silk'),'silo'=>array('model/mesh'),'sis'=>array('application/vnd.symbian.install'),'sisx'=>array('application/vnd.symbian.install'),'sit'=>array('application/x-sit','application/x-stuffit'),'sitx'=>array('application/x-stuffitx'),'skd'=>array('application/vnd.koan'),'skm'=>array('application/vnd.koan'),'skp'=>array('application/vnd.koan'),'skt'=>array('application/vnd.koan'),'sl'=>array('application/x-seelogo'),'slc'=>array('application/vnd.wap.slc'),'sldm'=>array('application/vnd.ms-powerpoint.slide.macroenabled.12'),'sldx'=>array('application/vnd.openxmlformats-officedocument.presentationml.slide'),'slt'=>array('application/vnd.epson.salt'),'sm'=>array('application/vnd.stepmania.stepchart'),'smf'=>array('application/vnd.stardivision.math'),'smi'=>array('application/smil+xml'),'smil'=>array('application/smil+xml'),'smv'=>array('video/x-smv'),'smzip'=>array('application/vnd.stepmania.package'),'snd'=>array('audio/basic','audio/x-adpcm'),'snf'=>array('application/x-font-snf'),'so'=>array('application/octet-stream'),'sol'=>array('application/solids'),'spc'=>array('application/x-pkcs7-certificates','text/x-speech'),'spf'=>array('application/vnd.yamaha.smaf-phrase'),'spl'=>array('application/x-futuresplash'),'spot'=>array('text/vnd.in3d.spot'),'spp'=>array('application/scvp-vp-response'),'spq'=>array('application/scvp-vp-request'),'spr'=>array('application/x-sprite'),'sprite'=>array('application/x-sprite'),'spx'=>array('audio/ogg'),'sql'=>array('application/x-sql'),'src'=>array('application/x-wais-source'),'srt'=>array('application/x-subrip'),'sru'=>array('application/sru+xml'),'srx'=>array('application/sparql-results+xml'),'ssdl'=>array('application/ssdl+xml'),'sse'=>array('application/vnd.kodak-descriptor'),'ssf'=>array('application/vnd.epson.ssf'),'ssi'=>array('text/x-server-parsed-html'),'ssm'=>array('application/streamingmedia'),'ssml'=>array('application/ssml+xml'),'sst'=>array('application/vnd.ms-pki.certstore'),'st'=>array('application/vnd.sailingtracker.track'),'stc'=>array('application/vnd.sun.xml.calc.template'),'std'=>array('application/vnd.sun.xml.draw.template'),'step'=>array('application/step'),'stf'=>array('application/vnd.wt.stf'),'sti'=>array('application/vnd.sun.xml.impress.template'),'stk'=>array('application/hyperstudio'),'stl'=>array('application/sla','application/vnd.ms-pki.stl','application/x-navistyle'),'stp'=>array('application/step'),'str'=>array('application/vnd.pg.format'),'stw'=>array('application/vnd.sun.xml.writer.template'),'sub'=>array('text/vnd.dvb.subtitle'),'sus'=>array('application/vnd.sus-calendar'),'susp'=>array('application/vnd.sus-calendar'),'sv4cpio'=>array('application/x-sv4cpio'),'sv4crc'=>array('application/x-sv4crc'),'svc'=>array('application/vnd.dvb.service'),'svd'=>array('application/vnd.svd'),'svf'=>array('image/vnd.dwg','image/x-dwg'),'svg'=>array('image/svg+xml'),'svgz'=>array('image/svg+xml'),'svr'=>array('application/x-world','x-world/x-svr'),'swa'=>array('application/x-director'),'swf'=>array('application/x-shockwave-flash'),'swi'=>array('application/vnd.aristanetworks.swi'),'sxc'=>array('application/vnd.sun.xml.calc'),'sxd'=>array('application/vnd.sun.xml.draw'),'sxg'=>array('application/vnd.sun.xml.writer.global'),'sxi'=>array('application/vnd.sun.xml.impress'),'sxm'=>array('application/vnd.sun.xml.math'),'sxw'=>array('application/vnd.sun.xml.writer'),'t'=>array('text/troff'),'t3'=>array('application/x-t3vm-image'),'taglet'=>array('application/vnd.mynfc'),'talk'=>array('text/x-speech'),'tao'=>array('application/vnd.tao.intent-module-archive'),'tar'=>array('application/x-tar'),'tbk'=>array('application/toolbook','application/x-tbook'),'tcap'=>array('application/vnd.3gpp2.tcap'),'tcl'=>array('application/x-tcl','text/x-script.tcl'),'tcsh'=>array('text/x-script.tcsh'),'teacher'=>array('application/vnd.smart.teacher'),'tei'=>array('application/tei+xml'),'teicorpus'=>array('application/tei+xml'),'tex'=>array('application/x-tex'),'texi'=>array('application/x-texinfo'),'texinfo'=>array('application/x-texinfo'),'text'=>array('application/plain','text/plain'),'tfi'=>array('application/thraud+xml'),'tfm'=>array('application/x-tex-tfm'),'tga'=>array('image/x-tga'),'tgz'=>array('application/gnutar','application/x-compressed'),'thmx'=>array('application/vnd.ms-officetheme'),'tif'=>array('image/tiff','image/x-tiff'),'tiff'=>array('image/tiff','image/x-tiff'),'tmo'=>array('application/vnd.tmobile-livetv'),'torrent'=>array('application/x-bittorrent'),'tpl'=>array('application/vnd.groove-tool-template'),'tpt'=>array('application/vnd.trid.tpt'),'tr'=>array('text/troff'),'tra'=>array('application/vnd.trueapp'),'trm'=>array('application/x-msterminal'),'ts'=>array('video/MP2T'),'tsd'=>array('application/timestamped-data'),'tsi'=>array('audio/tsp-audio'),'tsp'=>array('application/dsptype','audio/tsplayer'),'tsv'=>array('text/tab-separated-values'),'ttc'=>array('application/x-font-ttf'),'ttf'=>array('application/x-font-ttf'),'ttl'=>array('text/turtle'),'turbot'=>array('image/florian'),'twd'=>array('application/vnd.simtech-mindmapper'),'twds'=>array('application/vnd.simtech-mindmapper'),'txd'=>array('application/vnd.genomatix.tuxedo'),'txf'=>array('application/vnd.mobius.txf'),'txt'=>array('text/plain'),'u32'=>array('application/x-authorware-bin'),'udeb'=>array('application/x-debian-package'),'ufd'=>array('application/vnd.ufdl'),'ufdl'=>array('application/vnd.ufdl'),'uil'=>array('text/x-uil'),'ulx'=>array('application/x-glulx'),'umj'=>array('application/vnd.umajin'),'uni'=>array('text/uri-list'),'unis'=>array('text/uri-list'),'unityweb'=>array('application/vnd.unity'),'unv'=>array('application/i-deas'),'uoml'=>array('application/vnd.uoml+xml'),'uri'=>array('text/uri-list'),'uris'=>array('text/uri-list'),'urls'=>array('text/uri-list'),'ustar'=>array('application/x-ustar','multipart/x-ustar'),'utz'=>array('application/vnd.uiq.theme'),'uu'=>array('application/octet-stream','text/x-uuencode'),'uue'=>array('text/x-uuencode'),'uva'=>array('audio/vnd.dece.audio'),'uvd'=>array('application/vnd.dece.data'),'uvf'=>array('application/vnd.dece.data'),'uvg'=>array('image/vnd.dece.graphic'),'uvh'=>array('video/vnd.dece.hd'),'uvi'=>array('image/vnd.dece.graphic'),'uvm'=>array('video/vnd.dece.mobile'),'uvp'=>array('video/vnd.dece.pd'),'uvs'=>array('video/vnd.dece.sd'),'uvt'=>array('application/vnd.dece.ttml+xml'),'uvu'=>array('video/vnd.uvvu.mp4'),'uvv'=>array('video/vnd.dece.video'),'uvva'=>array('audio/vnd.dece.audio'),'uvvd'=>array('application/vnd.dece.data'),'uvvf'=>array('application/vnd.dece.data'),'uvvg'=>array('image/vnd.dece.graphic'),'uvvh'=>array('video/vnd.dece.hd'),'uvvi'=>array('image/vnd.dece.graphic'),'uvvm'=>array('video/vnd.dece.mobile'),'uvvp'=>array('video/vnd.dece.pd'),'uvvs'=>array('video/vnd.dece.sd'),'uvvt'=>array('application/vnd.dece.ttml+xml'),'uvvu'=>array('video/vnd.uvvu.mp4'),'uvvv'=>array('video/vnd.dece.video'),'uvvx'=>array('application/vnd.dece.unspecified'),'uvvz'=>array('application/vnd.dece.zip'),'uvx'=>array('application/vnd.dece.unspecified'),'uvz'=>array('application/vnd.dece.zip'),'vcard'=>array('text/vcard'),'vcd'=>array('application/x-cdlink'),'vcf'=>array('text/x-vcard'),'vcg'=>array('application/vnd.groove-vcard'),'vcs'=>array('text/x-vcalendar'),'vcx'=>array('application/vnd.vcx'),'vda'=>array('application/vda'),'vdo'=>array('video/vdo'),'vew'=>array('application/groupwise'),'vis'=>array('application/vnd.visionary'),'viv'=>array('video/vivo','video/vnd.vivo'),'vivo'=>array('video/vivo','video/vnd.vivo'),'vmd'=>array('application/vocaltec-media-desc'),'vmf'=>array('application/vocaltec-media-file'),'vob'=>array('video/x-ms-vob'),'voc'=>array('audio/voc','audio/x-voc'),'vor'=>array('application/vnd.stardivision.writer'),'vos'=>array('video/vosaic'),'vox'=>array('application/x-authorware-bin'),'vqe'=>array('audio/x-twinvq-plugin'),'vqf'=>array('audio/x-twinvq'),'vql'=>array('audio/x-twinvq-plugin'),'vrml'=>array('application/x-vrml','model/vrml','x-world/x-vrml'),'vrt'=>array('x-world/x-vrt'),'vsd'=>array('application/vnd.visio'),'vsf'=>array('application/vnd.vsf'),'vss'=>array('application/vnd.visio'),'vst'=>array('application/vnd.visio'),'vsw'=>array('application/vnd.visio'),'vtt'=>array('text/vtt'),'vtu'=>array('model/vnd.vtu'),'vxml'=>array('application/voicexml+xml'),'w3d'=>array('application/x-director'),'w60'=>array('application/wordperfect6.0'),'w61'=>array('application/wordperfect6.1'),'w6w'=>array('application/msword'),'wad'=>array('application/x-doom'),'wav'=>array('audio/wav','audio/x-wav'),'wax'=>array('audio/x-ms-wax'),'wb1'=>array('application/x-qpro'),'wbmp'=>array('image/vnd.wap.wbmp'),'wbs'=>array('application/vnd.criticaltools.wbs+xml'),'wbxml'=>array('application/vnd.wap.wbxml'),'wcm'=>array('application/vnd.ms-works'),'wdb'=>array('application/vnd.ms-works'),'wdp'=>array('image/vnd.ms-photo'),'web'=>array('application/vnd.xara'),'weba'=>array('audio/webm'),'webapp'=>array('application/x-web-app-manifest+json'),'webm'=>array('video/webm'),'webp'=>array('image/webp'),'wg'=>array('application/vnd.pmi.widget'),'wgt'=>array('application/widget'),'wiz'=>array('application/msword'),'wk1'=>array('application/x-123'),'wks'=>array('application/vnd.ms-works'),'wm'=>array('video/x-ms-wm'),'wma'=>array('audio/x-ms-wma'),'wmd'=>array('application/x-ms-wmd'),'wmf'=>array('application/x-msmetafile'),'wml'=>array('text/vnd.wap.wml'),'wmlc'=>array('application/vnd.wap.wmlc'),'wmls'=>array('text/vnd.wap.wmlscript'),'wmlsc'=>array('application/vnd.wap.wmlscriptc'),'wmv'=>array('video/x-ms-wmv'),'wmx'=>array('video/x-ms-wmx'),'wmz'=>array('application/x-msmetafile'),'woff'=>array('application/x-font-woff'),'word'=>array('application/msword'),'wp'=>array('application/wordperfect'),'wp5'=>array('application/wordperfect','application/wordperfect6.0'),'wp6'=>array('application/wordperfect'),'wpd'=>array('application/wordperfect','application/x-wpwin'),'wpl'=>array('application/vnd.ms-wpl'),'wps'=>array('application/vnd.ms-works'),'wq1'=>array('application/x-lotus'),'wqd'=>array('application/vnd.wqd'),'wri'=>array('application/mswrite','application/x-wri'),'wrl'=>array('application/x-world','model/vrml','x-world/x-vrml'),'wrz'=>array('model/vrml','x-world/x-vrml'),'wsc'=>array('text/scriplet'),'wsdl'=>array('application/wsdl+xml'),'wspolicy'=>array('application/wspolicy+xml'),'wsrc'=>array('application/x-wais-source'),'wtb'=>array('application/vnd.webturbo'),'wtk'=>array('application/x-wintalk'),'wvx'=>array('video/x-ms-wvx'),'x-png'=>array('image/png'),'x32'=>array('application/x-authorware-bin'),'x3d'=>array('model/x3d+xml'),'x3db'=>array('model/x3d+binary'),'x3dbz'=>array('model/x3d+binary'),'x3dv'=>array('model/x3d+vrml'),'x3dvz'=>array('model/x3d+vrml'),'x3dz'=>array('model/x3d+xml'),'xaml'=>array('application/xaml+xml'),'xap'=>array('application/x-silverlight-app'),'xar'=>array('application/vnd.xara'),'xbap'=>array('application/x-ms-xbap'),'xbd'=>array('application/vnd.fujixerox.docuworks.binder'),'xbm'=>array('image/x-xbitmap','image/x-xbm','image/xbm'),'xdf'=>array('application/xcap-diff+xml'),'xdm'=>array('application/vnd.syncml.dm+xml'),'xdp'=>array('application/vnd.adobe.xdp+xml'),'xdr'=>array('video/x-amt-demorun'),'xdssc'=>array('application/dssc+xml'),'xdw'=>array('application/vnd.fujixerox.docuworks'),'xenc'=>array('application/xenc+xml'),'xer'=>array('application/patch-ops-error+xml'),'xfdf'=>array('application/vnd.adobe.xfdf'),'xfdl'=>array('application/vnd.xfdl'),'xgz'=>array('xgl/drawing'),'xht'=>array('application/xhtml+xml'),'xhtml'=>array('application/xhtml+xml'),'xhvml'=>array('application/xv+xml'),'xif'=>array('image/vnd.xiff'),'xl'=>array('application/excel'),'xla'=>array('application/excel','application/x-excel','application/x-msexcel'),'xlam'=>array('application/vnd.ms-excel.addin.macroenabled.12'),'xlb'=>array('application/excel','application/vnd.ms-excel','application/x-excel'),'xlc'=>array('application/excel','application/vnd.ms-excel','application/x-excel'),'xld'=>array('application/excel','application/x-excel'),'xlf'=>array('application/x-xliff+xml'),'xlk'=>array('application/excel','application/x-excel'),'xll'=>array('application/excel','application/vnd.ms-excel','application/x-excel'),'xlm'=>array('application/excel','application/vnd.ms-excel','application/x-excel'),'xls'=>array('application/excel','application/vnd.ms-excel','application/x-excel','application/x-msexcel'),'xlsb'=>array('application/vnd.ms-excel.sheet.binary.macroenabled.12'),'xlsm'=>array('application/vnd.ms-excel.sheet.macroenabled.12'),'xlsx'=>array('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),'xlt'=>array('application/excel','application/x-excel'),'xltm'=>array('application/vnd.ms-excel.template.macroenabled.12'),'xltx'=>array('application/vnd.openxmlformats-officedocument.spreadsheetml.template'),'xlv'=>array('application/excel','application/x-excel'),'xlw'=>array('application/excel','application/vnd.ms-excel','application/x-excel','application/x-msexcel'),'xm'=>array('audio/xm'),'xml'=>array('application/xml','text/xml'),'xmz'=>array('xgl/movie'),'xo'=>array('application/vnd.olpc-sugar'),'xop'=>array('application/xop+xml'),'xpdl'=>array('application/xml'),'xpi'=>array('application/x-xpinstall'),'xpix'=>array('application/x-vnd.ls-xpix'),'xpl'=>array('application/xproc+xml'),'xpm'=>array('image/x-xpixmap','image/xpm'),'xpr'=>array('application/vnd.is-xpr'),'xps'=>array('application/vnd.ms-xpsdocument'),'xpw'=>array('application/vnd.intercon.formnet'),'xpx'=>array('application/vnd.intercon.formnet'),'xsl'=>array('application/xml'),'xslt'=>array('application/xslt+xml'),'xsm'=>array('application/vnd.syncml+xml'),'xspf'=>array('application/xspf+xml'),'xsr'=>array('video/x-amt-showrun'),'xul'=>array('application/vnd.mozilla.xul+xml'),'xvm'=>array('application/xv+xml'),'xvml'=>array('application/xv+xml'),'xwd'=>array('image/x-xwd','image/x-xwindowdump'),'xyz'=>array('chemical/x-xyz'),'xz'=>array('application/x-xz'),'yang'=>array('application/yang'),'yin'=>array('application/yin+xml'),'z'=>array('application/x-compress','application/x-compressed'),'z1'=>array('application/x-zmachine'),'z2'=>array('application/x-zmachine'),'z3'=>array('application/x-zmachine'),'z4'=>array('application/x-zmachine'),'z5'=>array('application/x-zmachine'),'z6'=>array('application/x-zmachine'),'z7'=>array('application/x-zmachine'),'z8'=>array('application/x-zmachine'),'zaz'=>array('application/vnd.zzazz.deck+xml'),'zip'=>array('application/x-compressed','application/x-zip-compressed','application/zip','multipart/x-zip'),'zir'=>array('application/vnd.zul'),'zirz'=>array('application/vnd.zul'),'zmm'=>array('application/vnd.handheld-entertainment+xml'),'zoo'=>array('application/octet-stream'),'zsh'=>array('text/x-script.zsh'),'123'=>array('application/vnd.lotus-1-2-3'));


		if($index && isset($mime_types[$index])) $mimeType = $mime_types[$index];

		return $mimeType;

	}


}
