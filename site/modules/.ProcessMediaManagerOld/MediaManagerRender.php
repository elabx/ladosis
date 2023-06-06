<?php namespace ProcessWire;

/**
* Media Manager: Render
*
* This file forms part of the Media Manager Suite.
* Renders markup for output in various places in the module.
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

class MediaManagerRender extends ProcessMediaManager {

	/**
	 * Set some key properties for use throughout the class.
	 *
	 * @access public
	 *
	 */
	public function __construct() {
		parent::__construct();
		$this->mmUtilities = new MediaManagerUtilities();
		$this->adminURL = $this->wire('config')->urls->admin;
		// get sanitised url segments
		$urlSegments = $this->mmUtilities->getURLSegments();
		$this->urlSeg1 = $urlSegments[0];
		$this->urlSeg2 = $urlSegments[1];
		$this->urlSeg3 = $urlSegments[2];
	}


/* ######################### - MARKUP RENDERERS - ######################### */


	/**
	 * Render final grid+list views of media in Media Library.
	 *
	 *
	 * @access protected
	 * @param PageArray $results ProcessPageLister results of media found.
	 * @param String $finalSelector Final selector used by ProcessPageLister to return results.
	 * @param String $mediaTypeStr If in single media mode, returns the type (audio|document|image|video).
	 * @return $out String Markup of results.
	 *
	 */
	protected function renderViews($results, $finalSelector, $mediaTypeStr) {

		$viewCookie = $this->wire('input')->cookie->media_manager_view;

		$out = '';
		$pgOut = '';
		$selectAll = '';
		$unSelectAll = '';

		// if no urlSeg1 or in 'add' or 'link' mode, show all media
		if(!$this->urlSeg1 || $this->urlSeg1 == 'add' || $this->urlSeg1 == 'link') $thumbs = $this->mmUtilities->prepareThumbsAll($results);
		else $thumbs = $this->mmUtilities->prepareThumbs($results, $mediaTypeStr);

		// if NOT in RTE mode, show select/unselect all
		if($this->urlSeg1 != 'rte' && $this->urlSeg1 != 'link') {
			$selectAll = '<span id="select_all" class="sel_all"><a href="#">' . $this->_('Select All') . '</a></span> &#47;';
			$unSelectAll = '<span id="unselect_all" class="sel_all"><a href="#">' . $this->_('Unselect All') . '</a></span>';
		}

		// bottom page + final selector if debug=true
		$fs = $finalSelector ? "<p id='final_selector' class='notes'>" . $finalSelector . "</p>" : '';

		if(count($results)) {
			$paginatedResults = $this->renderPagination($results);
			$pgOut = isset($paginatedResults['pages']) ? $paginatedResults['pages'] : '';
			$headline = isset($paginatedResults['headline']) ? $paginatedResults['headline'] : '';
		}

		else $headline = $this->_('No results.');

		// wrapper for media controllers: pagination + single media actions
		$out .= '<div id="media_wrapper" class="block-group">';// @note: wrapper for all (both columns)

		$viewSwitcherLiveSort = $this->renderMiniActions();// live sort + sort order switcher (<select>)

		// top pagination + headline (results count)
		$out .= '<div class="block media_pager">' .
					$viewSwitcherLiveSort .
					'<h2 class="results_headline">' . $headline . '</h2>' .
					$pgOut  .
				'</div>';// end media_pager 100% width

		// list/grid-view cookie
		$hideView = $viewCookie ? 'class="hide_view"' : '';
		// left column wrapper
		$out .= "<div id='media_grid_view' {$hideView}>";

		// grab views (list and grid)
		$views = $this->renderThumbs($thumbs);// markup
		$out .= $views['grid'];
		$listView = $views['list'];

		$out .= '</div>';// end div#media_grid_view: left_column: 50% width

		// media info panel + preview: right column: 50%
		$out .= $this->renderMediaInfoPanel($thumbs);

		// hide list-view table if we are in grid-view
		$hideView = !$viewCookie ? ' hide_view' : '';
		$out .= "<div id='media_list_view' class='block list{$hideView}'>" .
					$listView .
				'</div>';

		// bottom pager
		$out .= '<div id="bottom_pager" class="block media_pager">' .
					$selectAll .
					$unSelectAll .
					$pgOut . $fs .
				'</div>';
		$out .= '</div>';// end div#media_wrapper block-group

		return $out;

	}

	/**
	 * Function to create the Media Manager menu.
	 *
	 * @access protected
	 * @return string $out
	 *
	 */
	protected function renderMenu() {

		$out = '';
		$allMenu = '';
		$modal = '';
		$seg = '';
		$disAllowedMedia = NULL;

		// split $urlSeg2 to get currentPageID and currentFieldID
		$IDs = explode('-', $this->urlSeg2 );
		$currentPageID =  isset($IDs[0]) ? (int) $IDs[0] : '';
		$currentFieldID = isset($IDs[1]) ? (int) $IDs[1] : '';

		// if we have urlSeg1 == add, we are in a modal
		if($this->urlSeg1 == 'add' && $currentPageID && $currentFieldID) {
			$seg = $this->urlSeg1 . '/' . $currentPageID . '-' . $currentFieldID . '/';
			$modal = '?modal=1';

			$f = $this->wire('fields')->get($currentFieldID);

			// array to help skip irrelevant media from display in menu
			$disAllowedMedia = array(1=>'audio', 2=>'document', 3=>'image', 4=>'video');

			if(is_array($f->allowedMedia)) {
					foreach ($f->allowedMedia as $al) {
						unset($disAllowedMedia[$al]);
				}
			}
			else $disAllowedMedia = NULL;

		}
		// if in LINK or RTE mode
		if($this->urlSeg1 == 'link' || $this->urlSeg1 == 'rte') {
			$seg = $this->urlSeg1 . '/';
			$modal = '?modal=1';
		}

		// CSS
		$on = !$this->urlSeg1 || ($this->urlSeg1 == 'add' && !$this->urlSeg3) || ($this->urlSeg1 == 'link' && !$this->urlSeg2) ? 'mm_menu_item mm_on' : 'mm_menu_item';

		//for special CSS class if user using AdminThemeReno
		// in default theme, prevent tabs in upload view from backing up into menu
		$topPanelClass = $this->extraCSSClass ? ' top_panel_'. $this->extraCSSClass : ' top_panel';
		$mediaClass = $this->urlSeg1 == 'cleanup' ? '' : $topPanelClass;

		$out = "<div id='mm_top_panel' class='block-group$mediaClass'>";
		$out .= "<div id='media_message' class='block'><p id='message'></p></div>";// @todo: js string error somewhere here. Don't know why!
		$out .= "<div id='mm_menu' class='block'><ul class='mm_menu'>";

		/*
		NEED ABSOLUTE URLS TO DEAL WITH ISSUE OF TRAILING SLASH.
			 - http:// processwire.com/talk/topic/3777-post-empty-on-post-from-different-page/
		 */
		// Using absolute url: see URL segment + trailing slash issue

		if((is_null($disAllowedMedia)) || (count($disAllowedMedia) < 3)) {
			$allMenu = "<li><a class='$on' href='" . $this->wire('page')->url . $seg . $modal . "'>" . $this->_('All') . "</a></li>";
		}
		// @todo..refactor? ideally, add above!
		if($this->urlSeg1 == 'rte') $allMenu = '';

		$out .= $allMenu;

		$menuItemsOther = array(

							'audio' => $this->_('Audio'),
							'document' => $this->_('Document'),
							'image' => $this->_('Image'),
							'video' => $this->_('Video'),
							'upload' => $this->_('Upload'),
							'settings' => $this->_('Settings'),
							'cleanup' => $this->_('Cleanup'),

						);


		// remove menu item 'upload' if permission exists and user has no rights
		if ($this->noUpload) unset($menuItemsOther['upload']);

		// remove menu item 'cleanup' for non-superusers and in modal views
		if (in_array($this->urlSeg1, array('add', 'link', 'rte')) || !$this->wire('user')->isSuperuser()) unset($menuItemsOther['cleanup']);

		// only 'image media' applies in 'rte' view
		if ($this->urlSeg1 == 'rte') {
			unset($menuItemsOther['audio']);
			unset($menuItemsOther['document']);
			unset($menuItemsOther['video']);
		}

		foreach ($menuItemsOther as $key => $value) {


					$on = $this->urlSeg1 == $key || $this->urlSeg2 == $key || $this->urlSeg3 == $key || ($key == 'image' && $this->urlSeg1 == 'variations') ? 'mm_menu_item mm_on' : 'mm_menu_item';
					/*
						had to change to this because of issue with trailling slash and
						_POST getting converted to _GET
						http:// processwire.com/talk/topic/3777-post-empty-on-post-from-different-page/ AND
						http:// processwire.com/talk/topic/3727-does-input-urlsegments-mess-with-post/
					 */

					if(is_array($disAllowedMedia) && in_array($key, $disAllowedMedia)) continue;

					$out .= "<li><a class='$on' href='" . $this->wire('page')->url . $seg . $key . "/" . $modal . "'>$value</a></li>";
		}

		$out .= "</ul></div>";

		// if in 'add' [to current page] mode, output 'insert media' button
		$out .= $this->renderAddAction();
		$out .= $this->renderActions();

		$out .= "</div>";

		return $out;

	}

	/**
	 * Render paginated results.
	 *
	 * @access protected
	 * @param $results PageArray to paginate.
	 * @return Arry $paginatedResults
	 *
	 */
	protected function renderPagination($results) {

		$paginatedResults = array();

		$start 	= $results->getStart();
		$limit 	= $results->getLimit();
		$end 	= $start+$results->count();
		$total 	= $results->getTotal();

		if(count($results)) {
			$paginatedResults['headline'] = sprintf($this->_('%1$d to %2$d of %3$d'), $start+1, $end, $total);
			if($total > $limit) {
				$pager = $this->wire('modules')->get('MarkupPagerNav');// @todo..do we need this?
				// make sure url segments parameters work with page numbers in the url
				#Solution for pagination when using URL segments
				$url = $this->wire('page')->url . $this->wire('input')->urlSegmentsStr .'/';// get the url segment string.
				$pgOut = $results->renderPager(array('baseUrl' => $url));
				$paginatedResults['pages'] = str_replace($url . "'", $url . "?pageNum=1'", $pgOut); // specifically identify page1, otherwise link doesn't work: @ryancramer
			}

		}

		return $paginatedResults;

	}

	/**
	 * Build the Media Thumbs Grid.
	 *
	 * This is finally rendered by ProcessMediaManager::renderResults() using Ajax.
	 *
	 * @access protected
	 * @param Array $thumbs Page and image data to output in Page Images Grid.
	 * @return Array $views Markup to output via renderViews().
	 *
	 */
	public function renderThumbs($thumbs) {

		$views = array();
		$grid ='';
		$list ='';
		$checkbox = '';
		$panelViewURL = '';

		// RTE Mode : to avoid image being highlighted (js+css)
		$rteClass = '';
		if($this->urlSeg1 == 'rte') $rteClass = ' rte';
		elseif($this->urlSeg1 == 'link') $rteClass = ' link';

		$adminURL = $this->adminURL;
		$mediaRTELink = '';

		$extraCSSClass = $this->extraCSSClass ? ' ' . $this->extraCSSClass : '';
		// for image media only: lightbox gallery of images
		$faZoom = '<i class="fa fa-fw fa-search-plus media_image_zoom"></i>';

		$grid .= '<div id="media_thumbs_wrapper" class="block">';// for grid view
		$list = '<table id="list_view" class="AdminDataTable AdminDataList">';// for list view

		// RTE LINK INSERT
		// @todo: prevent insertion or even viewing of unpublished images/media?
		if($this->urlSeg1 == 'link') $grid .= '<a id="media_rte_link" href="" title="">RTE Link</a>';// @note: hidden link

		// $k => ID of media page, $v => array with media properties
		foreach ($thumbs as $k => $v) {
			$class = isset($v['in_field']) ? $v['in_field'] : '';
			$unPubClass = isset($v['published']) ? ' ' . $v['published'] : '';
			$lockedClass = isset($v['locked']) ? ' ' . $v['locked'] : '';
			$title = $v['title'];
			$title2 =  isset($v['title2']) ? $v['title2'] : $title;// truncated title

			$mediaThumbURL = $v['url_thumb'];
			$mediaURL = $v['url'];
			$mediaTypeInt = $this->mmUtilities->mediaTypeInt($v['type']);
			$dataValue = $k . '_' . $mediaTypeInt;
			$mediaName = $v['basename'];
			$mediaClass = $v['type'];// for checkbox + img
			$class .= ' ' . $mediaClass;
			$class = trim($class, ' ');
			// @note: will change below to wrap in '<a></a>' if in RTE mode
			$mediaThumb = "<img src='$mediaThumbURL' title='$mediaName' data-media-value='$dataValue' class='$class$rteClass'>";

			$imageGalleryIcon = '';
			// @note: only show gallery icon if NOT in rte mode (image or link) {note the && [rather than ||] due to ! [negation]}
			if ($v['type'] == 'image' && ($this->urlSeg1 != 'rte' && $this->urlSeg1 !='link')) {
				$imageGalleryIcon = "<div class='media_image_zoom'>" .
										"<span>" .
											"<a href='" . $mediaURL . "' data-gallery='gallery-grid' class='media_image_zoom' title='" . $title . "'>" . $faZoom . "</a>" .
										"</span>" .
									"</div>";

			}

			$panelViewURL = "<a href='#' data-media-value='" . $dataValue . "' data-href='" . $mediaURL . "' class='media_panel_view'>". $title2 . "</a>";

			// @note: if in RTE mode we won't need this hidden checkbox since no actions
			if($this->urlSeg1 != 'rte') {
				$checkbox = "<input type='checkbox' class='$mediaClass' id='thumb-{$dataValue}' name='media' value='$dataValue'>";
			}

			// in RTE mode @todo: prevent insertion or even viewing of unpublished images/media?
			if ($this->urlSeg1 == 'rte') {
				// @note: hidden link (only for 'insert image in RTE')
				$mediaRTELink =
						"<a href='".
							$adminURL .
							/*
							@note: "page/image/?id={$k}&amp;modal=1'>$mediaThumb" .
							 	- will take us to image selection on page (as per normal PW - images on page '/page/path')
							 	- we don't want that: we want to go straight to insert in RTE modal
							 */
							//"page/image/edit?file={$k},{$mediaName}&amp;modal=1&amp;id={$k}&amp;winwidth=$winwidth'>$mediaThumb" .
							 "page/image/edit?file={$k},{$mediaName}&amp;modal=1&amp;id={$k}' ". // @note: not supplying $winwidth!
							 "id='rte-{$dataValue}' " .
							 "class='" . trim($rteClass) ."'" .
							 ">" .// end opening <a> tag
							$title2 .
						"</a>";
			}

			$thumb =
					"<div class='block media original{$extraCSSClass}{$rteClass}' data-media-value='$dataValue'>" .
					 	$mediaThumb .
					 	$imageGalleryIcon .
						$checkbox .
						$mediaRTELink .// @note: hidden link
						//@todo: not working properly; view source; some weird stuff in here!
						"<span class='thumb-meta{$unPubClass}{$lockedClass}' title='{$title}'>" .
							$panelViewURL .
						"</span>" .
					"</div>";// end div.media

			$grid .= $thumb;

			// @note: for list view
			$panelViewURL2 = "<a href='#' data-media-value='" . $dataValue . "' data-href='" . $mediaURL . "' class='media_panel_view'>". $title . "</a>";
			$panelViewURL2 = "<span class='thumb-meta{$unPubClass}{$lockedClass}' title='{$title}'>" .
							$panelViewURL2 .
						"</span>";

			// @note: for list view
			$by = $v['type'] == 'image' ? ' x ' : '';
			$dimensions = $v['width']. $by . $v['height'];
			$dimensions .= ', ' . $v['size'];

			// we need unique data-gallery value for grid vs list views (to avoid duplication in Blueimp gallery view)
			$thumb = str_replace('gallery-grid', 'gallery-list', $thumb);

			$list .= 	"<tr>" .
							"<td>". $thumb . "</td>" .
							"<td>" .
								$panelViewURL2 .
								"<span class='list_view_mini_titles'>" . $this->_('Description') . "</span>: " .	$v['description'] . "<br>" .
								"<span class='list_view_mini_titles'>" . $this->_('Tags') . "</span>: " .	$v['tags'] . "<br>" .
								"<span class='list_view_mini_titles'>" . $this->_('Name') . "</span>: " .	$mediaName . "<br>" .
								"<span class='list_view_mini_titles'>" . $this->_('Dimensions/Size') . "</span>: ". trim($dimensions, ',') .

							"</td>" .
						"</tr>";

		}// end foreach

		$grid .= '</div>';// end div#media_thumbs_wrapper

		$list .= '</table>';
		$views['grid'] = $grid;
		$views['list'] = $list;

		return $views;

	}

	/**
	 * Build the Media Large Previews.
	 *
	 * This is rendered by ProcessMediaManager::renderResults() using Ajax.
	 *
	 * @access protected
	 * @param $thumbs Array of page and image data to output in Page Images Preview.
	 * @return $out String Markup to output via renderResults().
	 *
	 */
	protected function renderMediaInfoPanel($thumbs) {

		$out ='';

		$config = $this->wire('config');
		$mediaURL = '';
		$dataValue = '';
		$previewURL = '';
		$input = $this->wire('input');

		// on install and no Media Manager field (FieldtypeMediaManager) has been created, this class will not be present, so we require it
		if(!class_exists('MediaManager')) {
			$dir = dirname(__FILE__);
			require_once("$dir/MediaManager.php");
		}

		$mm = new MediaManager();

		$viewCookie = $input->cookie->media_manager_view;
		$resetTotal = $input->post->reset_total;// check if lister refresh was clicked vs. a hard browser refresh
		$fixedListViewCookie = $resetTotal ? $input->cookie->media_manager_list_view_fixed : '';
		$editedPageID = (int) $input->cookie->media_manager_edited_media;

		$listViewClass = $viewCookie ? ' list_view' : '';
		$out .="<div id='media_large_view' class='block{$listViewClass}'>";

		// $k => ID of media page, $v => array with media properties
		foreach ($thumbs as $k => $v) {
			$title = $v['title'];
			$mediaTypeInt = $this->mmUtilities->mediaTypeInt($v['type']);
			$dataValue = 'media_large_' . $k . '_' . $mediaTypeInt;
			$mediaURL = $v['url'];
			$mediaTypeStr = $v['type'];

			$p = $this->wire('pages')->get((int) $k);

			$otherLargeClass = $mediaTypeStr == 'image' ? '' : ' media_large_' . $mediaTypeStr;

			// check if to apply 'fixed class' in list-view mode
			$fixedClass = $editedPageID ==  $k && $fixedListViewCookie ?  ' fixed' : '';

			$out .= "<div id='$dataValue' class='media_large hidden$otherLargeClass$fixedClass'>";

			// display large image media
			if($mediaTypeStr == 'image') {
				$out .= "<img src='$mediaURL' class='media_image_large'>";
			}

			// media overlay stats panel
			$out .= $this->renderMediaStats($p, $mediaTypeStr);
			// media edit form/panel @note: will return empty if 'media-manager-edit' is in force and user has no such permission
			$out .= $this->renderMediaEditInput($p, $mediaTypeStr);

			if($mediaTypeStr == 'image') {
				$out .= $this->renderImageVariations($p);
			}

			// render audio/video player + default rendering for documents (toString())
			else {

				$mediaField = 'media_manager_' .  $mediaTypeStr;
				$m = $p->$mediaField->first();

				if($mediaTypeStr == 'audio') $out .= $mm->renderMediaAudio($m, $p->title);
				elseif($mediaTypeStr == 'video') $out .= $mm->renderMediaVideo($m, $p->title);

			}



			$out .= "</div>";// end div.media_large

		}// end foreach

		$out .='</div>';// end div#media_large_view

		return $out;

	}

	/**
	 * Render output for editing a single media item.
	 *
	 * @access protected
	 * @param $p Page Object containing a single media.
	 * @param $mediaTypeStr String denoting one of four media types (audio|document|image|video).
	 * @return $out String Markup to output via renderResults().
	 *
	 */
	protected function renderMediaEditInput($p, $mediaTypeStr) {

		$out = '';
		$extraCSSClass = $this->extraCSSClass;

		// @access-control: media-manager-edit
		if($this->noEdit) return $out;

		if($p && $p->id > 0)  {

			$mediaID = '_' . $p->id;

			$mediaField = 'media_manager_' .  $mediaTypeStr;

			$m = $p->$mediaField->first();
			$lockChecked = '';
			$lockStr = '';

			$pubChecked = $p->is(Page::statusUnpublished) ? '' : ' checked';

			if($p->is(Page::statusLocked)) {
				$lockChecked = ' checked';
				$lockStr = ' <small class="media_locked">' . $this->_('media locked for edits.') . '</small>';
			}

			// save button
			$btn = $this->wire('modules')->get('InputfieldButton');
			$btn->attr('name', 'mm_edit_save_btn');
			$btn->attr('data-media-action', 'save');
			$btn->attr('data-media-id', $p->id);
			$btn->attr('data-media-type', $mediaTypeStr);
			$btn->class .= ' mm_edit ' . $extraCSSClass;// add a custom class to this button
			$btn->attr('value', $this->_('Save'));
			$btn = $btn->render();

			$dataValue = $p->id . '_' . $this->mmUtilities->mediaTypeInt($mediaTypeStr);

			$out = '<div class="media_panel" data-media-value="' . $dataValue . '">'.
						'<div class="block media_edit">'.
							$btn .
							'<span class="close" data-media-value="' . $dataValue . '">X</span>';// edit panel close

			## inputs  ##
			// main media inputs (i.e. for images, original image, not variations)
			$out .= '<label for="media_title' . $mediaID . '">' . $this->_("Title") . $lockStr . '</label>';
			$out .= '<input id="media_title' . $mediaID . '" type="text" name="media_title" value="' . $p->title . '">';
			$out .= '<label for="media_description' . $mediaID . '">' . $this->_("Description") . '</label>';
			$out .= '<textarea id="media_description' . $mediaID . '" name="media_description">' . $m->description . '</textarea>';
			$out .= '<label for="media_tags' . $mediaID . '">' . $this->_("Tags") . '</label>';
			$out .= '<input id="media_tags' . $mediaID . '" type="text"  name="media_tags" value="' . $m->tags . '">' ;

			// @access-control
			$readOnlyP = $this->noPublish ? ' disabled="disabled"'  : '';
			$readOnlyL = $this->noLock ? ' disabled="disabled"'  : '';

			$pubCheckbox = '<input type="checkbox" name="media_publish" value="' .  ($pubChecked ? 1 : 0)  . '" id="media_publish' . $mediaID . '"' . $pubChecked . $readOnlyP . '>';
			$out .= '<label class="media_status" for="media_publish' . $mediaID . '">' . $this->_("Published") . $pubCheckbox .'</label>';

			$lockCheckbox = '<input type="checkbox" name="media_lock" value="' .  ($lockChecked ? 1 : 0)  . '" id="media_lock' . $mediaID . '"' . $lockChecked . $readOnlyL .'>';
			$out .= '<label class="media_status" for="media_lock' . $mediaID . '">' . $this->_("Locked") . $lockCheckbox . '</label>';

			// for image media with variations: link to open dynamically built modal to edit variations descriptions and tags
			if($mediaTypeStr == 'image' && $this->mmUtilities->imageVariationsCount($p)) {
				$faFiles = '<i class="fa fa-fw fa-files-o"></i>';
				$editVariationsInfo = 	"<a href='#' class='media_image_variations_edit' data-media-value='" . $dataValue . "'>" .
											$this->_('Edit Variations ') . $faFiles . $this->mmUtilities->imageVariationsCount($p) .
										"</a>";
				$out .= $editVariationsInfo;
			}

			$out .= '</div></div>';// end div.media_edit + div.media_panel

		}

		return $out;

	}

	/**
	 * Render media statistics
	 *
	 * Shows media usage count, dimensions, filename, size and variations count.
	 *
	 * @access protected
	 * @param Page $p Object containing a single media.
	 * @param String $mediaTypeStr Denotes one of four media types (audio|document|image|video).
	 * @return String $out Markup of media statistics.
	 *
	 */
	protected function renderMediaStats($p, $mediaTypeStr) {

		$out = '';
		$variations = '';
		$dimensions = '';
		$mediaField = 'media_manager_' .  $mediaTypeStr;

		// media usage count
		$cnt = $this->mmUtilities->mediaUsageCount($p->id);
		$usedCnt = sprintf(_n("%d time", "%d times", $cnt), $cnt);

		$m = $p->$mediaField->first();

		if($mediaTypeStr == 'image') {
			$variationsCnt = $this->mmUtilities->imageVariationsCount($p);
			$dimensions = '<span>' . $this->_('Dimensions') . ':</span> ' . $m->width . 'px x ' . $m->height . 'px<br>';
			$variations = '<span>' . $this->_('Variations') . ':</span> ' . $variationsCnt . '<br>';
		}

		$options = array(
			'id' => $p->id,
			'basename' => $m->basename,
			'type' => $mediaTypeStr,
			'published' => $p->is(Page::statusUnpublished) ? false : true,
			'locked' => $p->is(Page::statusLocked) ? true : false,
			'title' => $p->title,
			'url' => $m->url,
		);

		$dataValue = $p->id . '_' . $this->mmUtilities->mediaTypeInt($mediaTypeStr);
		$mediaStats = '<div class="media_stats" data-media-value="' . $dataValue . '">'.
						'<h2>' . $p->title . '</h2>' .
						$this->renderMediaDescTags($m) .
						$this->renderSingleMediaAction($options) .
						'<p>'.
							$dimensions .
							'<span>' . $this->_('Filename') . ':</span> ' . $m->basename . '<br>' .
							'<span>' . $this->_('Size') . ':</span> ' . $m->filesizeStr . '<br>' .
							$variations .
							'<span>' . $this->_('Used') . ':</span> ' . $usedCnt .
						'</p>'.
					'</div>';

		$out .= $mediaStats;

		return $out;

	}

	/**
	 * Render output showing media description and tags.
	 *
	 * @access protected
	 * @param File|Image $m Object containing a single media.
	 * @return String $out Markup of description and tags.
	 *
	 */
	protected function renderMediaDescTags($m) {

		$mediaDescription = $m->description ? $m->description : $this->_('No description');
		$mediaTags = $m->tags ? $m->tags : $this->_('No tags');

		$out = 	'<div class="media_desc_tags">' .
					'<p><span>' . $this->_('Description: ') .  '</span>'. $mediaDescription .'</p>' .
					'<p><span>' . $this->_('Tags: ') .  '</span>'. $mediaTags .'</p>' .
				'</div>';

		return $out;

	}

	/**
	 * Render output showing variations of a single image media item.
	 *
	 * @access private
	 * @param Page $mediaPage Object The media page where the media image lives.
	 * @return String $out Markup of media variations to output via renderMediaInfoPanel().
	 *
	 */
	private function renderImageVariations($mediaPage) {

		$p = $mediaPage;
		// array holding original image + variations info
		$variations = $this->mmUtilities->prepareImageVariations($p);
		if(!count($variations)) return;// image media doesn't have variations

		$locked = $p->is(Page::statusLocked) ? true : false;

		$mediaClass = 'image variation';
		$cropImageIcon = '';
		$checkbox = '';

		// RTE Mode : to avoid image being highlighted (js+css)
		$rteClass = '';
		if($this->urlSeg1 == 'rte') $rteClass = ' rte';
		elseif($this->urlSeg1 == 'link') $rteClass = ' link';

		$adminURL = $this->adminURL;
		$mediaRTELink = '';// @NOTE: here we'll use this variable for either image or link RTE mode

		#####################
		$faZoom = '<i class="fa fa-fw fa-search-plus media_image_zoom"></i>';

		$dataValue = $p->id . '_3';

		$out = '';
		$out .= '<div class="media_variations_wrapper" data-media-value="' . $dataValue . '">';
		$out .= '<h3 class="media_variations_title">' . $this->_('Variations') . '</h3>';
		$out .= '<input type="hidden" class="media_variations_parent_id"  name="media_variations_parent_id" value="0" data-media-variations-parent-id="' . $p->id . '">';

		// variations: accordion/wrapper
		$out .= '<div class="accordion" data-media-value="' . $dataValue . '">';

		// variations: thumbs
		foreach ($variations as $ver) {
			$dataValue = $p->id . '_' . $ver['type'];// e.g. 1234_31 where '1234' is mediaPageID; '3' is type (i.e. image); and '1' is variation number
			$class2 = isset($ver['in_field']) ? $ver['in_field'] : '';
			$class2 .= ' ' . $mediaClass;
			$class2 = trim($class2, ' ');
			$mediaThumbURL = $ver['url_thumb'];
			$mediaURL = $ver['url'];
			$mediaName = $ver['basename'];
			$mediaDescription = $ver['description'];
			$mediaTags = $ver['tags'];
			$mediaWidth = $ver['width'];
			$mediaHeight = $ver['height'];
			$mediaSize = $ver['file_size'];
			$mediaVersionNumber = $ver['version_number'];

			$mediaTypeVariationInt = $ver['type'];// for images, this is something like 31, 35, 311, where 3 = media type image and the '1' or '11' is the nth variation for the original image media
			$dataVarType = 'data-media-variation-type="' . $mediaTypeVariationInt . '"';

			$mediaThumb = "<img src='$mediaThumbURL' title='$mediaName' data-media-value='$dataValue' data-media-variations-parent-id='{$p->id}' class='$class2$rteClass'>";

			// @access-control: media-manager-lock and media-manager-edit

			$cropImageIcon = $locked || $this->noEdit ? '' : $this->renderImageVariationsCropURL(array($p->id, $mediaName));

			$imageGalleryIcon = '';
			// @note: only show gallery icon if NOT in rte (image OR link) mode {note the && [rather than ||] due to ! [negation]}
			if($this->urlSeg1 != 'rte' && $this->urlSeg1 !='link') {
				$imageGalleryIcon = "<div class='media_image_zoom'>" .
						"<a href='" . $mediaURL . "' data-gallery=gallery-variations-{$p->id} class='media_image_zoom' title='" . $mediaName . "'>" . $faZoom . "</a>" .
				"</div>";
			}

			// @note: if in IMAGE RTE mode we won't need this hidden checkbox since no actions
			if($this->urlSeg1 != 'rte') {
				$checkbox = "<input type='checkbox' class='$mediaClass' id='thumb-{$dataValue}' name='media' value='$dataValue'>";
			}

			// @note: hidden link (IMAGE RTE mode)
			if ($this->urlSeg1 == 'rte') {
				$mediaRTELink =
						"<a href='".
							$adminURL .
							/*
							@note: "page/image/?id={$k}&amp;modal=1'>$mediaThumb" .
							 	- will take us to image selection on page (as per normal PW - images on page '/page/path')
							 	- we don't want that: we want to go straight to insert in RTE modal
							 */
							//"page/image/edit?file={$p->id},{$mediaName}&amp;modal=1&amp;id={$p->id}&amp;winwidth=$winwidth'>$mediaThumb" .
							 "page/image/edit?file={$p->id},{$mediaName}&amp;modal=1&amp;id={$p->id}' ". // @note: not supplying $winwidth!
							 "id='rte-{$dataValue}' " .
							 "class='" . trim($rteClass) ."'" .
							 ">" .// end opening <a> tag
							$p->title .
						"</a>";
			}

			// @note: hidden link (LINK RTE mode)
			elseif($this->urlSeg1 == 'link') {
				$mediaRTELink = "<span class='media_rte_link' title='{$p->title}'>" .
								"<a href='#' data-media-value='" . $dataValue . "' data-href='" . $mediaURL . "' class='media_rte_link'>". $p->title . "</a>" .
								"</span>";
			}

			// variations headers
			$out .= '<h3 class="media_variations">#' . $mediaVersionNumber . '</h3>';

			// list of variations
			$out .= '<ul class="media_variations">';

			// variations column 1: thumb
			$out .= "<li>" .
						"<div class='block media{$rteClass}'>" .
							$mediaThumb .
							$cropImageIcon .
							$imageGalleryIcon .
							$checkbox .
							$mediaRTELink .// @note: hidden link
						"</div>" .
					"</li>";// end div.block media variation


			// variations column 2: media stats
			$dimensions = '<div><span>' . $this->_('Dimensions') . ':</span> ' . $mediaWidth . 'px x ' . $mediaHeight . 'px</div>';
			$filename = '<div><span>' . $this->_('Filename') . ':</span> ' . $mediaName . '</div>';
			$size = '<div><span>' . $this->_('Size') . ':</span> '. $mediaSize  .'</div>';
			$out .=  "<li>" . $dimensions . $filename . $size . "</li>";

			// variations column 3: description and tags
			$mediaDescription = $mediaDescription ? $mediaDescription : '';
			$mediaTags = $mediaTags ? $mediaTags : '';
			$out .= '<li>' .
						'<div><span>' . $this->_('Description') . ': </span><span class="media_image_variation_description"' . $dataVarType . '>' . $mediaDescription . '</span></div>' .
						'<div><span>' . $this->_('Tags') . ': </span><span class="media_image_variation_tags" ' . $dataVarType . '>' . $mediaTags . '</span></div>' .
					'</li>';

			$out .= '</ul>';// end list of variations

		}// end foreach $variations

		$out .='</div></div>';// end div.accordion + div.media_variations_wrapper

		return $out;

	}

	/**
	 * Render image cropping URL markup.
	 *
	 * Helper method.
	 *
	 * @access private
	 * @param $options Array Containing media page and image version information.
	 * @return $cropURL String Markup to output via renderImageVariations().
	 *
	 */
	private function renderImageVariationsCropURL($options) {

		$cropURL = '';
		$adminURL = $this->adminURL;
		$pid = $options[0];
		$mediaName = $options[1];
		$mediaImageField = 'media_manager_image';
		$faCrop = '<i class="fa fa-fw fa-crop media_image_variation_crop"></i>';

		$cropURL = "<div class='media_image_variation_crop'><a href='" .
			$adminURL .
			"page/image/edit/?id={$pid}&file={$pid},$mediaName&rte=0&field=$mediaImageField' " .
			"class='media_image_variation_crop pw-modal-large pw-modal' " .
			"data-media-buttons='#non_rte_dialog_buttons button' " .
			"data-media-autoclose='1' data-media-close='#non_rte_cancel'>".
			$faCrop . "</a></div>";

		return $cropURL;

	}

	/**
	 * Builds the actions input for the modal/add page.
	 *
	 * This is only used in page-edit mode (i.e. InputfieldMediaManager)
	 *
	 * @access private
	 * @return $out String Markup of actions panel for modal page.
	 *
	 */
	private function renderAddAction() {

		$out ='';

		// don't show 'insert media button' in upload|cleanup|settings
		if($this->urlSeg1 == 'add' && ($this->urlSeg3 != 'upload' && $this->urlSeg3 !='cleanup' && $this->urlSeg3 !='settings')) {

			$extraCSSClass = $this->extraCSSClass;

			$btn = $this->wire('modules')->get('InputfieldButton');
			$btn->attr('id+name', 'mm_add_btn');
			$btn->class .= ' mm_add ' . $extraCSSClass;//add a custom class to this button
			$btn->attr('value', $this->_('Insert Media'));
			$btn->attr('title', $this->_('Insert selected media to your page'));
			$btn->attr('data-autoclose', 'close');

			$out .='<div id="insert_wrapper" class="block">';
			$out .= '<span id="page_fields_spinner">' .
					'<i class="fa fa-lg fa-spin fa-spinner"></i></span>';
			$out .= '<div id="save_pages">';
			$out .= $btn->render();
			$out .= '</div></div>';

		}

		return $out;

	}

	/**
	 * Builds the actions input for audio, document, image and video pages.
	 *
	 * @access private
	 * @return $out String Markup of actions panel.
	 *
	 */
	private function renderActions() {

		// no ACTIONS output if in modal (add or link RTE), cleanup or upload or settings mode
		if(	in_array($this->urlSeg1, array('add', 'settings', 'upload', 'cleanup', 'link', 'rte')) ||
			in_array($this->urlSeg3, array('upload', 'cleanup', 'settings')))  {
			$out = '';
		}

		else {

				// the menus bulk actions panel
				$actions = array(
									'publish' => $this->_('Publish'),
									'unpublish' => $this->_('Unpublish'),
									'lock' => $this->_('Lock'),
									'unlock' => $this->_('Unlock'),
									'tag' => $this->_('Tag'),
									'untag' => $this->_('Remove Tags'),
									'trash' => $this->_('Trash'),
									'delete' => $this->_('Delete'),
									'delete-variation' => $this->_('Delete Variations'),
				);

				// @access-control
				// check publish permission
				if($this->noPublish) {
						unset($actions['publish']);
						unset($actions['unpublish']);
				}

				// check lock permission
				if($this->noLock) {
						unset($actions['lock']);
						unset($actions['unlock']);
				}

				// check trash/delete permission
				if($this->noDelete) {
						unset($actions['trash']);
						unset($actions['delete']);
						unset($actions['delete-variation']);
				}

				$buttonValue = $this->_('Apply Action');
				$buttonName = 'mm_action_btn';
				$extraCSSClass = $this->extraCSSClass;

				$modules = $this->wire('modules');

				$is = $modules->get('InputfieldSelect');
				$is->label = $this->_('Action');
				$is->attr('name+id', 'mm_action_select');
				$is->addOptions($actions);
				$is = count($actions) ? $is->render() : '';// only if actions present do we render the input <select>

				$btn1 = $modules->get('InputfieldButton');
				$btn1->attr('id+name', $buttonName);
				$btn1->attr('data-media-action', 'save');
				$btn1->class .= ' ' . $extraCSSClass;//add a custom class to this button
				$btn1->attr('value', $buttonValue);
				$btn1 = count($actions) ? $btn1->render() : '';// only show action button if there's actions to be applied

				// tagging
				$in = $modules->get('InputfieldText');
				$in->label = $this->_('Tags');
				$in->attr('name+id', 'mm_action_tags');
				$in =  '<span id="mm_action_tags_label">' . $this->_('Tags') . '</span>' .
						$in->render() .
						'<p id="mm_action_tags_info" class="notes">' .
							$this->_('Specify tags to apply to selected media. Multiple tags should be space-separated.') .
						'</p>';

				$tagModeWrapper = '<p id="mm_tag_mode_wrapper">';
				$checkbox = '<input type="checkbox" name="mm_tag_mode" value="0">';
				$checkbox = '<label>' . $checkbox . $this->_('Check to replace existing tags or leave unchecked to append instead.') . '</label>';
				$tagModeWrapper .= $checkbox;
				$tagModeWrapper .= '</p>';

				$tagsInput = '<div id="mm_tags_wrapper">' . $in . $tagModeWrapper . '</div>';

				// if no actions, it means we are in edit mode
				$actionsOut = $btn1 . $is . $tagsInput;

				// render the final form
				$extraCSSClass = count($actions) ? '' : ' empty';

				$out = "<div id='mm_actions' class='block{$extraCSSClass}'>" . $actionsOut . "</div>";

		}

		return $out;

	}

	/**
	 * Builds edit and crop actions icons for sinlge audio, document, image and video media.
	 *
	 * @access private
	 * @param $options Array of options to determine media actions to return.
	 * @return $out String Markup of single media actions panel.
	 *
	 */
	protected function renderSingleMediaAction(Array $options) {

		$out = '';
		$out .= '<div class="block media_actions">';

		$adminURL = $this->adminURL;
		$mediaTypeStr = $options['type'];
		$id = $options['id'];
		$mediaURL = $options['url'];
		$mediaTitle = $options['title'];
		$mediaName = $options['basename'];
		$published = $options['published'];
		$locked = $options['locked'];
		$mediaImageField = 'media_manager_image';
		$dataValue = $id . '_' . $this->mmUtilities->mediaTypeInt($mediaTypeStr);

		// for all media: display if media unpublished
		$faPublished = '<i class="fa fa-fw fa-eye-slash"></i>';
		$publishedIcon = $published ? '' : "<span><a href='#' class='media_edit media_unpublished'>" . $faPublished . "</a></span>";

		// for all media: display if media locked for edits
		$faLock = '<i class="fa fa-fw fa-lock"></i>';
		$lockedIcon = $locked ? "<span><a href='#' class='media_edit media_locked'>" . $faLock .	"</a></span>" : '';

		// for all media: link to open media input edit panel
		$faPencil = '<i class="fa fa-fw fa-pencil"></i>';
		$editMediaIcon = "<span><a href='#' class='media_edit_info media_edit' data-media-value='" . $dataValue . "'>" . $faPencil .	"</a></span>";

		// for image media only: crop action icon
		$faCrop = '<i class="fa fa-fw fa-crop media_image_crop"></i>';
		$cropImageIcon = "<span>".
							"<a href='" .
								$adminURL .
								"page/image/edit/?id=$id&file=$id,$mediaName&rte=0&field=$mediaImageField' " .
								"class='media_image_crop media_edit pw-modal-large pw-modal' " .
								"data-media-buttons='#non_rte_dialog_buttons button' " .
								"data-media-autoclose='1' data-media-close='#non_rte_cancel'>".
								$faCrop .
							"</a>" .
					"</span>";

		// if media locked, don't show edit image icon (i.e. for variations)
		// no need to call page again to check; we just check the lockedChecked string
		if($locked || $this->noEdit || $mediaTypeStr !== 'image') $cropImageIcon = '';
		if($mediaTypeStr !== 'image') $imageGalleryIcon = '';
		if($this->noEdit) $editMediaIcon = '';


		#$out .= $imageGalleryIcon;
		$out .= $editMediaIcon;
		$out .= $publishedIcon;
		$out .= $lockedIcon;
		$out .= $cropImageIcon;


		$out .= '</div>';// end div.media_actions

		return $out;

	}

	/**
	 * Builds the mini actions input for switching views and live-sorting media.
	 *
	 * @access private
	 * @return String $out String Markup of mini actions panel.
	 *
	 */
	private function renderMiniActions() {

		$out = '';

		// the menus bulk actions panel
		$liveSortActions = array(
							1 => $this->_('Title'),
							2 => $this->_('Tags'),
							3 => $this->_('Modified'),
							4 => $this->_('Created'),
							5 => $this->_('Published'),
							6 => $this->_('Description'),
		);

		$sortLabel = '<label id="media_live_sort_label" for="mm_live_sort_action_select">' . $this->_("Sort") . '</label>';

		// get live sort value from cookie if any
		$input = $this->wire('input');
		$value = $input->cookie->media_live_sort ? $input->cookie->media_live_sort : '';

		$is = $this->wire('modules')->get('InputfieldSelect');
		//$is->label = $this->_('Live Sort');// @note: doesn't work
		$is->attr('name+id', 'mm_live_sort_action_select');
		$is->addOptions($liveSortActions);
		$is->attr('value', $value);
		$is = $sortLabel . $is->render();

		$hide = $value ? '' : 'class="hide"';
		$checked = (int) $input->cookie->media_live_sort_order == 2 ? 'checked="checked"' : '';

		$checkbox = "<label id='media_live_sort_order_label' for='media_live_sort_order' {$hide}>" .
					"<input type='checkbox' id='media_live_sort_order' name='media_live_sort_order' value='0' {$checked}>" .
					$this->_("Descending") . '</label>';

		$viewSwitcher = '<div id="media_view_switcher_sort">' .
							'<a class="views grid_view" href="#" title="' . $this->_('Grid View') . '"><i class="fa fa-th"></i></a>' .
							'<a class="views list_view" href="#" title="' . $this->_('List View') . '"><i class="fa fa-list"></i></a>' .
							$is . $checkbox .
						'</div>';

		$out .= $viewSwitcher;

		return $out;

	}

	/**
	 * Renders a form for the creation and editing of filter profiles.
	 *
	 * @access protected
	 * @return String $form Form for editing filter profiles.
	 *
	 */
	protected function renderFilterConfig() {

		$modules = $this->wire('modules');

		// create a form for saving fitlers input
		$form = $modules->get('InputfieldForm');
		$form->attr('id', 'mm_filter_config');
		$form->action = './';
		$form->method = 'post';
		$form->description = $this->_('Media Manager: Filter Profiles');

		$form->add($this->renderActiveFilter());// select for setting active filter profile
		$form->add($this->renderCreateFilter());// input for creating new filter profile
		$form->add($this->renderFilterProfilesTable());// table listing saved filter profiles

		// save filter changes: submit button
		$f = $modules->get('InputfieldButton');
		$f->attr('id+name', 'mm_filter_settings_save_btn');
		##$f->class .= ' head_button_clone';// @note: will not work in modal
		$f->value = $this->_('Save');
		$f->attr('type', 'submit');

		$form->add($f);

		// save filter changes: submit button
		$f = $modules->get('InputfieldButton');
		$f->attr('id+name', 'mm_filter_settings_save_view_btn');
		$f->value = $this->_('Save + View');
		$f->attr('type', 'submit');

		$form->add($f);

		// if saving
		$post = $this->wire('input')->post;
		if($post->mm_filter_settings_save_btn || $post->mm_filter_settings_save_view_btn){
			$this->mmActions = new MediaManagerActions();
			$actionType = 'edit-filters';// various edit actions: set active; create new; save (non-deleted) profiles (in profiles table)
			$this->mmActions->actionMedia($actionType, $form);
		}

		return $form->render();

	}

	/**
	 * Builds the select for setting the active filter profile.
	 *
	 * @access private
	 * @return Object $wrapper InputfieldWrapper to add to final filter profiles form.
	 *
	 */
	private function renderActiveFilter() {

		$savedSettings = $this->savedSettings;
		$modules = $this->wire('modules');

		// new inputfieldwrapper
		$wrapper = new InputfieldWrapper();
		$id = $this->className() . 'Active Filter';
		$wrapper->attr('id', $id);

		$fieldset = $modules->get('InputfieldFieldset');
		$fieldset->attr('id', 'mm_active_filter_profile');
		$fieldset->label = $this->_('Active Filter Profile');

		// saved filter profiles to populate active filter select
		$profiles = array();
		$savedFilterProfiles = isset($savedSettings['filters']) ? $savedSettings['filters'] : array();

		foreach ($savedFilterProfiles as $name => $value) {
			if(!$value['title']) continue;
			$profiles[$name] = $value['title'];
		}

		// current active filter
		$active = isset($savedSettings['active_filter']) ? $savedSettings['active_filter'] : '';

		// set active filter: input select
		$f = $modules->get('InputfieldSelect');
		$f->label = $this->_('Set the active filter');
		$f->attr('name+id', 'mm_active_filter_select');
		$f->attr('value', $active);
		$f->addOptions($profiles);

		$fieldset->add($f);

		$wrapper->add($fieldset);

		return $wrapper;

	}

	/**
	 * Builds the text input for creating a new filter profile.
	 *
	 * @access private
	 * @return Object $wrapper InputfieldWrapper to add to final filter profiles form.
	 *
	 */
	private function renderCreateFilter() {

		$modules = $this->wire('modules');

		// new inputfieldwrapper
		$wrapper = new InputfieldWrapper();
		$id = $this->className() . 'Create Filter';
		$wrapper->attr('id', $id);

		$fieldset = $modules->get('InputfieldFieldset');
		$fieldset->attr('id', 'mm_create_filter_profile');
		$fieldset->label = $this->_('Create a Filter Profile');
		//$fieldset->collapsed = Inputfield::collapsedYes;

		// filter: title (text)
		$f = $modules->get('InputfieldText');
		$f->label = $this->_('Title');
		$f->attr('name', 'mm_create_filter_title');
		$f->attr('value', '');
		$f->description = $this->_('A title is required.');

		$fieldset->add($f);

		$wrapper->add($fieldset);

		return $wrapper;

	}

	/**
	 * Builds the table that lists saved filter profiles.
	 *
	 * The table is also used to select and delete single or multiple filters.
	 *
	 * @access private
	 * @return Object $wrapper InputfieldWrapper to add to final filter profiles form.
	 *
	 */
	private function renderFilterProfilesTable() {

		$modules = $this->wire('modules');
		$savedSettings = $this->savedSettings;

		// new inputfieldwrapper
		$wrapper = new InputfieldWrapper();
		$id = $this->className() . 'Active Filter';
		$wrapper->attr('id', $id);

		$fieldset = $modules->get('InputfieldFieldset');
		$fieldset->attr('id', 'mm_upload_settings');
		$fieldset->label = $this->_('Your Filter Profiles');
		$fieldset->description = $this->_("Click on a filter's title to edit it. Click on trash icon to set the filter for deletion");

		$savedFilterProfiles = isset($savedSettings['filters']) ? $savedSettings['filters'] : array();

		$table = '';

		if(count($savedFilterProfiles)) {
			$tbody = '';
			foreach ($savedFilterProfiles as $name => $value){

					$filterName = $this->wire('sanitizer')->pageName($name);
					$filterTitle = $value['title'];
					if(!$filterTitle) continue;
						$tbody .= "
							<tr class='mm_filter_profile'>" .
								"<td>" .
									"<input type='hidden' name='mm_filter_title[]' value='" . $filterTitle . "'>" .
									"<a href='{$this->wire('page')->url}filter/{$filterName}/?modal=1' class='ui-helper-clearfix mm_edit_filter pw-modal pw-modal-medium'>" .
										$filterTitle .
									"</a>" .
								"</td>" .
								"<td>" .
									"<a href='#' class='mm_filter_profile_del ui-helper-clearfix'><span class='ui-icon ui-icon-trash'></span></a>" .
									"<input type='hidden' name='mm_filter_del[]' value='0'>" .
								"</td>" .
							"</tr>
						";

			}// end foreach

			$table = "
				<table id='mm_filter_profiles_list' class='AdminDataTable AdminDataList AdminDataTableResponsive'>" .
					"<thead>" .
					"<tr>" .
						"<th>" . $this->_('Title') . "</th>" .
						"<th class='mm_filter_profile_del'>" .
							"<a title='Delete All' href='#' class='mm_filter_profile_del'><span class='ui-icon ui-icon-trash'></span></a>" .
						"</th>" .
					"</tr>" .
					"</thead>" .
					"<tbody>" .
						$tbody .
					"</tbody>" .
				"</table>
			";
		}

		// markup field for out table
		$m = $modules->get('InputfieldMarkup');
		$m->textFormat = Inputfield::textFormatNone;// make sure ProcessWire renders the HTML

		$confirmDeleteFilters = "
							<p id='mm_filter_profiles_del_confirm_text' class='notes'>" .
								$this->_('Are you sure you want to delete the checked filter profile(s) above?') . ' ' .
								$this->_('Please check this box to confirm:') . ' ' .
								"<label>" .
									"<input type='checkbox' name='mm_delete_filter_profiles_confirm' value='1' />&nbsp;" .
								"</label>" .
							"</p>";

		$m->attr('value', $table ? $table . $confirmDeleteFilters : $this->_('No filter profiles found. Create one'));

		$fieldset->add($m);

		$wrapper->add($fieldset);

		return $wrapper;

	}

	/**
	 * Renders a form for editing a single filter profile.
	 *
	 * @access protected
	 * @return String $form Form for editing single filter profile.
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
		$form->description = $this->_('Configuring Filter') . ': ';
		$form->description .= $filterTitle ? $filterTitle : $this->_('New Filter');

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
		$f->description = $this->_('A title is required.');// @todo...errors if no title given!

		$form->add($f);

		// filter: inputfield selector
		$f = $modules->get('InputfieldSelector');
		$f->attr('name', 'defaultSelector');
		$f->attr('value', $defaultSelector);
		$f->label = $this->_('Configure Filters');
		$f->description = $this->_('The filters you select here will be visible when a user first views Media Manager Library when this filter profile is active. These are the default filters only, as the user can optionally add, change or remove them. We recommend that you select the fields to be used as filters, but leave the values in each row blank/unselected (unless you wish to provide a default value).'); // default filters description
		$f->addLabel = $this->_('Add Filter');
		$f->allowBlankValues = true;
		//$f->allowSystemCustomFields = true;// @TODO? maybe not
		//$f->allowSystemTemplates = true;// ? @TODO...maybe not
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

		// filter: go back to 'filter profiles view' button
		$f = $modules->get('InputfieldButton');
		$f->attr('id+name', 'mm_filter_profiles_go_btn');
		$f->attr('data-filter-profiles-url', $this->wire('page')->url . 'filter/?modal=1');
		$f->value = $this->_('View Filter Profiles');
		$f->attr('type', 'button');

		$form->add($f);

		// if saving
		$post = $this->wire('input')->post;
		if($post->mm_edit_filter_config_btn){
			$this->mmActions = new MediaManagerActions();
			$actionType = 'edit-filter';// single filter edit
			$this->mmActions->actionMedia($actionType, $form);
		}

		return $form->render();

	}

	/**
	 * Builds the form used to save Media Manager settings.
	 *
	 * @access protected
	 * @return String $form Markup of rendered form.
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
	 * @return mixed $inputfield markup.
	 *
	 */
	private function uploadSettings() {

		/* ## UPLOAD SETTINGS ## */

		$modules = $this->wire('modules');

		$validationSettings = $this->uploadSettingsValidation();
		$modeSettings = $this->uploadSettingsMode();
		$imageSettings = $this->uploadSettingsImage();
		$audioSettings = $this->uploadSettingsAudio();
		$videoSettings = $this->uploadSettingsVideo();
		$documentSettings = $this->uploadSettingsDocument();
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
		$settingsDesc = '<ol>' .
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

		/* 5. Upload Settings: image */
		$m = $modules->get('InputfieldMarkup');
		$m->attr('value', $imageSettings);
		$m->label = $this->_('Image');
		$m->collapsed = Inputfield::collapsedYes;
		$fieldset->add($m);

		/* 6. Upload Settings: audio */
		$m = $modules->get('InputfieldMarkup');
		$m->attr('value', $audioSettings);
		$m->label = $this->_('Audio');
		$m->collapsed = Inputfield::collapsedYes;
		$fieldset->add($m);

		/* 7. Upload Settings: video */
		$m = $modules->get('InputfieldMarkup');
		$m->attr('value', $videoSettings);
		$m->label = $this->_('Video');
		$m->collapsed = Inputfield::collapsedYes;
		$fieldset->add($m);

		/* 8. Upload Settings: document */
		$m = $modules->get('InputfieldMarkup');
		$m->attr('value', $documentSettings);
		$m->label = $this->_('Document');
		$m->collapsed = Inputfield::collapsedYes;
		$fieldset->add($m);

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

		$wrapper->add($fieldset);

		return $wrapper;

	}

	/**
	 * Upload settings for validation.
	 *
	 * Some settings shared between WireUpload and jQuery File Upload.
	 *
	 * @access private
	 * @return mixed $table markup.
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
	 * @return mixed $table markup.
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
		foreach ($modeSettings as $key => $value) {
				$settings['mode'][$key][3] = $value;
		}

		$table = $this->buildOptionsTable('mode', $settings['mode']);

		return $table;

	}

	/**
	 * Upload image settings.
	 *
	 * @access private
	 * @return mixed $table markup.
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
		foreach ($imageSettings as $key => $value) {
				$settings['image'][$key][3] = $value;
		}

		$table = $this->buildOptionsTable('image', $settings['image']);

		return $table;

	}

	/**
	 * Upload audio settings.
	 *
	 * @access private
	 * @return mixed $table markup.
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
		foreach ($audioSettings as $key => $value) {
			$settings['audio'][$key][3] = $value;
		}

		$table = $this->buildOptionsTable('audio', $settings['audio']);

		return $table;

	}

	/**
	 * Upload video settings.
	 *
	 * @access private
	 * @return mixed $table markup.
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
		foreach ($videoSettings as $key => $value) {
			$settings['video'][$key][3] = $value;
		}

		$table = $this->buildOptionsTable('video', $settings['video']);

		return $table;

	}

	/**
	 * Upload document settings.
	 *
	 * @access private
	 * @return mixed $table markup.
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
		foreach ($videoSettings as $key => $value) {
			$settings['document'][$key][3] = $value;
		}

		$table = $this->buildOptionsTable('document', $settings['document']);

		return $table;

	}

	/**
	 * Builds a table of settings options.
	 *
	 * @access private
	 * @param $type options type/group (upload- : validation, mode, image, audio, video, document).
	 * @param $options options to create table rows and cells.
	 * @return mixed $table markup.
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
	 * @return mixed $inputfield markup.
	 *
	 */
	private function otherSettings() {

		/* ## OTHER SETTINGS ## */

		$savedSettings = $this->savedSettings;
		$modules = $this->wire('modules');

		// new inputfieldwrapper
		$wrapper = new InputfieldWrapper();
		$wrapper->attr('title', $this->_('Other Settings'));
		$id = $this->className() . 'Other Settings';
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
		$showFilterProfiles = isset($savedSettings['show_filter_profiles'][0]) ? $savedSettings['show_filter_profiles'][0] : 2;// hide use of filter profiles by default
	 	$f = $modules->get('InputfieldRadios');
		$f->attr('name', 'mm_show_filter_profiles');
		$f->attr('value', $showFilterProfiles ? $showFilterProfiles : 2);
		$f->label =  $this->_('Show Filter Profiles');
		$f->collapsed = Inputfield::collapsedYes;
		$f->notes = $this->_("This is the default sort. It affects how media is sorted/displayed in your Media Library. The settings here can be overriden by the 'Live Sort' in the Media Library.");

		$radioOptions = array (
						 1 => $this->_('Yes'),
						 2 => $this->_('No'),
	 	);

	 	$f->addOptions($radioOptions);

	 	$fieldset->add($f);

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
















	#######################################

	/**
	 * Builds the media upload form + info.
	 *
	 * @access protected
	 * @param $savedSettings Array of saved media manager upload settings.
	 * @return $out String Markup of upload form.
	 *
	 */
	protected function renderUploadForm(Array $savedSettings) {

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

		$mmTabs = new MediaManagerTabs();

		$form->add($mmTabs->uploadAddFilesTab());
		$form->add($mmTabs->uploadScanTab());
		$form->add($mmTabs->uploadHelpInfoTab());

		// if saving settings or 'scanned' media or 'adding uploaded media to media library'
		$post = $this->wire('input')->post;
		// settings
		if($post->mm_settings_btn) {
			$this->mmActions = new MediaManagerActions();
			$actionType = 'settings';
			$this->mmActions->actionMedia($actionType, $form);
		}

		// manually adding to Media Library files previousl uploaded and saved in draft/temp folder for review
		elseif($post->mm_add_to_media_library_btn) {
			$this->mmActions = new MediaManagerActions();

			$actionType = 'upload';// add to media library 'manually' (i.e. after previous jfu upload and left in temp directory for review)
			$publish = (int) $post->mm_add_to_media_library_publish;

			// jfu options for processing ajax requests (not configs!).
			// we reuse some options here although this is a normal non-ajax post we are dealing with
			// method comes from parent::processJFUAjaxOptions()
			$processJFUAjaxOptions = $this->processJFUAjaxOptions();

			$options = array();
			$options['dir'] = $processJFUAjaxOptions['uploadsDir'];
			$options['thumb'] = $processJFUAjaxOptions['thumbsDir'];
			$options['publish'] = $publish;
			$options['after'] = 3;// if we are here it means manual add to Media Library is in place

			$this->mmActions->actionMedia($actionType, null, $options);
		}

		return $this->renderMenu() . $form->render();

	}

	/**
	 * Builds the media upload info/instuctions markup.
	 *
	 * @access public
	 * @return $out String Markup of information about uploading media.
	 *
	 */
	public function renderUploadInfo() {

		$docs = "<a href='http://mediamanager.kongondo.com/'>" . $this->_('official documenation.') . '</a>';

		$out =

		'<div id="mm_upload_info" class="block">
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

	/**
	 * Builds the media manager cleanup utility list and form.
	 *
	 * @access protected
	 * @return $form String Markup of form with info and action to cleanup media manager components
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
		$info.= '<div id="components">' .
					'<div id="warning">'.
						'<p>' . $this->_('This utility will irreversibly delete all the Media Manager Components listed below AND ALL your UPLOADED MEDIA. Use this utility in case you wish to completely uninstall Media Manager. You will afterward need to uninstall the module as usual. Before you start, make sure to empty the trash of any Media Manager pages.') .
					 '</p>'.
				'</div>';

		$info .= '<div class="block">' .
					'<h4>' . $this->_('Fields') . '</h4>' .
					'<ol>';

		foreach ($fields as $field) $info .= '<li>' . $field . '</li>';

		$info .=	'</ol>'.
				'</div>';

		$templates = array('media-manager', 'media-manager-audio', 'media-manager-document', 'media-manager-image', 'media-manager-video','media-manager-settings',);


		$info .= '<div class="block"><h4>' . $this->_('Templates') . '</h4><ol>';

		foreach ($templates as $template) $info .= '<li>' . $template . '</li>';

		$info .='</ol></div>';
		$info .='<div class="block"><h4>' . $this->_('Pages') . '</h4><p>' .
					$this->_('All pages using the listed templates') . '</p></div>';

		$templateFile = 'media-manager.php';


		$chx = '';

		$chx = "
		<input type=checkbox id='remove_tpl_files' name='remove_tpl_files' value='1'>
		<label id='remove_tf' for='remove_tpl_files'>" . $this->_("Check box to also remove the Template File") . "</label>";

		$info .= '<div class="block"><h4>' . $this->_('Template Files (optional)') . '</h4><ol>';

		$info .= '<li>' . $templateFile . '</li>';

		$info .='</ol></div>';


		$info .='</div>';

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
