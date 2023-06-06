<?php namespace ProcessWire;

/**
* Media Manager: Actions
*
* This file forms part of the Media Manager Suite.
* Executes various runtime CRUD tasks for the module.
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

class MediaManagerActions extends ProcessMediaManager {

	// for use in case of 'unpload and 'insert' action (actionType2)
	// used by anywhere jfu triggered by an MM inputfield
	// we use this in order not to overwrite $this->media!
	//private $media2 = array();


	public function __construct() {
		parent::__construct();
		$this->mmUtilities = new MediaManagerUtilities($this->currentPage, $this->mediaManagerField);
	}

/* ######################### - PROCESS ACTIONS - ######################### */


	/**
	 * Determine what action to apply to selected/uploaded media.
	 *
	 * Actions in page-edit mode are: 'insert'
	 * Actions in process-module-mode are: publish, unpublish, lock, unlock, trash, delete.
	 * We use this method for convenience rather than directly calling the action type methods in executeAjax().
	 *
	 * @access public
	 * @param string $actionType Type of action to apply.
	 * @param array|string|object $media Selected or Uploaded media|form to action.
	 * @param array $options Options to guide manipulation of media.
	 * @return array $data This array is returned from the respective action type methods. The array will be JSON-Encodde as a response to the ajax call.
	 *
	 */
	public function actionMedia($actionType, $media, $options = array()) {

		$data = array();
		$actionData = array();

		// in case actions did not succeed
		$data['message'] = 'error';
		$data['notice'] = $this->_('Media Manager: Error encountered (possibly an unallowed or unpublished item is the cause). No action taken.');
		$data['nothingAddedYet'] = $this->_('Nothing added yet.');

		$data['message'] = 'success';

		# set values to shared properties
		$this->actionType = $actionType;

		$wireArray = new WireData();
		$this->options = $wireArray->setArray($options);
		$this->currentPage = $this->options->currentPage;
		$this->mediaManagerField = $this->options->mediaManagerField;
		$this->media = $media;

		# add some universal data
		$data['action'] = $actionType;
		// if were are in 'upload_insert' mode (for inputfield MM jfu anywhere), change action to 'insert'
		// we need this for ajax update of inputfield markup
		if($this->options->actionType2 == 'upload_insert' && $actionType == 'upload') $data['action'] = 'insert';

		$data['currentPageID'] = 0;
		$data['mediaManagerFieldID'] = 0;
		$data['insertAndClose'] = 0;
		$data['galleryID'] = "";

		if($this->currentPage && $this->mediaManagerField) {
			$data['currentPageID'] = $this->currentPage->id;
			$data['mediaManagerFieldID'] = $this->mediaManagerField->id;
			$data['insertAndClose'] = 1 == (int) $this->mediaManagerField->insertAndClose ? 1 : 0;
			$data['galleryID'] = "inputfieldmediamanager-blueimp-gallery-{$this->mediaManagerField->name}";
		}


		## - ajax requests - ##

		// in ProcessMediaManager context
		// single or bulk media processing
		if($actionType == 'publish')		$actionData = $this->actionPublish(1);
		elseif($actionType == 'unpublish')	$actionData = $this->actionPublish(0);// -ditto-
		elseif($actionType == 'lock')		$actionData = $this->actionLock(1);// -ditto-
		elseif($actionType == 'unlock') 	$actionData = $this->actionLock(0);// -ditto-

		elseif($actionType == 'tag') 		$actionData = $this->actionTag(1);// -ditto-
		elseif($actionType == 'untag') 		$actionData = $this->actionTag(0);// -ditto-
		elseif($actionType == 'trash')		$actionData = $this->actionDelete(1);// -ditto-
		elseif($actionType == 'delete')		$actionData = $this->actionDelete(0);// -ditto-
		// @note: this is now bundled together with actionType = 'move' + is an ajax request
		//elseif($actionType == 'scan') $actionData = $this->actionScanMedia();

		// in page-edit (inputfield) context)
		elseif($actionType == 'insert')		$actionData = $this->actionInsert();
		elseif($actionType == 'edit')		$actionData = $this->actionEditSingleMedia();

		// in ProcessMediaManager context
		## - ajax requests - ##
		elseif($actionType == 'upload' || $actionType == 'move') $actionData = $this->actionUploadToMediaLibrary();
		elseif($actionType == 'scan-delete') $actionData = $this->actionScanDelete();

		// saving media_manager_settings. Here, $media is $form
		elseif($actionType == 'settings')	$actionData = $this->actionSaveSettings();
		// saving single filter profile edit. Here, $media is $form
		elseif($actionType == 'edit-filter')$actionData = $this->actionCreateEditFilter();
		// action multiple filter profiles: set active, lock/unlock, delete. Here, $media is $form
		elseif($actionType == 'edit-filters')$actionData = $this->actionFilterProfiles();

		// non-ajax request responses: use PW message/error
		if(in_array($actionType, array('settings'))) {
			if($data['message'] == 'error') return $this->error($data['notice']);
			elseif($data['message'] == 'success') {
				$this->message($data['notice']);
				// failed due to naming
				return;
			}
			// catering for the unknowns
			else return $this->error($this->_('Media Manager: There was an error. We could not process your request.'));
		}



		// @note: merge defaults with page specific
		$data = is_array($actionData) && count($actionData) ? array_merge($data, $actionData) : $data;

		// ajax request response: $data will be json_encode'd and echo'ed in parent::executeAjax()
		return $data;

	}

	/**
	 * Publish/Unpublish media.
	 *
	 * Called by actionMedia on behalf of executeAjax() to unpublish/publish media.
	 * Only used in ProcessMediaManager context (i.e. in process rather than page-edit (inputfield) context).
	 * Only those with 'media-manager-publish' permission (if present) will be able to select the action.
	 *
	 * @access private
	 * @param integer $action Whether to publish or unpublish. 0=unpublish; 1=publish.
	 * @return array $data To JSON-Encode as a response to the ajax call.
	 *
	 */
	private function actionPublish($action) {

		$data = array();
		$media = $this->media;

		// @access-control: media-manager-publish
		if($this->noPublish) {
			$data['message'] = 'error';
			$data['notice'] = $this->_('Media Manager: You have no permission to (un)publish media.');
			return $data;
		}

		$pages = $this->wire('pages');
		$actionTaken = '';
		$actionTaken2 = '';// in case some success + some errors
		$actionStr = $action ? $this->_('published') : $this->_('unpublished');

		if(count($media)) {

			$i = 0;// count for success actions
			$j = 0;// count for failed actions

			foreach ($media as $m) {

				$idType = explode('_', $m);

				$p = $pages->get((int) $idType[0]);
				if(!$p->id) continue;

				// if media page locked for edits
				if($p->is(Page::statusLocked)) {
					$j++;
					continue;
				}

				// unpublish media
				if($action == 0) {
					$p->addStatus(Page::statusUnpublished);
					$p->save();
					// confirm successfully unpublished
					if ($p->is(Page::statusUnpublished)) $i++;
					else $j++;
				}

				// publish media
				elseif($action == 1) {
					$p->removeStatus(Page::statusUnpublished);
					$p->save();
					// confirm successfully published
					if (!$p->is(Page::statusUnpublished)) $i++;
					else $j++;
				}

				else $j++;

			}// end foreach

			/* prepare responses */

			if($i > 0) {
				$actionTaken = sprintf(__('Media Manager: %1$d media %2$s.'), $i, $actionStr);
				// @note: for error message even though we have success above
				if($j) $actionTaken2 = sprintf(__('Media Manager: %1$d media locked for edits and could not be %2$s.'), $j, $actionStr);
				$data['message'] = 'success';
			}

			// if we could not (un)publish any media
			else {
				$lockedEditsStr = $this->_(' Media locked for edits.');
				$actionTaken = $this->_('Media Manager: Selected media could not be ') . $actionStr . '. ' . $lockedEditsStr;
				$data['message'] = 'error';
			}

			$data['notice'] = $actionTaken;
			if($actionTaken2) $data['notice2'] = $actionTaken2;

		}// end if count($media)

		return $data;

	}

	/**
	 * Lock/Unlock media.
	 *
	 * Called by actionMedia on behalf of executeAjax() to unlock/lock media.
	 * Only used in ProcessMediaManager context (i.e. in process rather than page-edit (inputfield) context).
	 * Only those with 'media-manager-lock' permission (if present) will be able to select the action.
	 *
	 * @access private
	 * @param integer $action Whether to lock or unlock. 0=unlock; 1=lock.
	 * @return array $data To JSON-Encode as a response to the ajax call.
	 *
	 */
	private function actionLock($action) {

		$data = array();
		$media = $this->media;

		// @access-control: media-manager-lock
		if($this->noLock) {
			$data['message'] = 'error';
			$data['notice'] = $this->_('Media Manager: You have no permission to (un)lock media.');
			return $data;
		}

		$pages = $this->wire('pages');
		$actionTaken = '';
		$actionTaken2 = '';// in case some success + some errors
		$actionStr = $action ? $this->_('locked') : $this->_('unlocked');

		if(count($media)) {

			$i = 0;// count for success actions
			$j = 0;// count for failed actions

			foreach ($media as $m) {

				$idType = explode('_', $m);

				$p = $pages->get((int) $idType[0]);
				if(!$p->id) continue;

				// unlock media
				if($action == 0) {
					$p->removeStatus(Page::statusLocked);
					$p->save();
					// confirm successfully unlocked
					if (!$p->is(Page::statusLocked)) $i++;
					else $j++;
				}

				// lock media
				elseif($action == 1) {
					$p->addStatus(Page::statusLocked);
					$p->save();
					// confirm successfully locked
					if ($p->is(Page::statusLocked)) $i++;
					else $j++;
				}

				else $j++;

			}// end foreach


			/* prepare responses */

			if($i > 0) {
				$actionTaken = sprintf(__('Media Manager: %1$d media %2$s.'), $i, $actionStr);
				// @note: for error message even though we have success above
				if($j) $actionTaken2 = sprintf(__('Media Manager: %1$d media locked for edits and could not be %2$s.'), $j, $actionStr);
				$data['message'] = 'success';
			}

			// if we could not (un)lock any media
			else {
				$lockedEditsStr = $this->_(' Media locked for edits.');
				$actionTaken = $this->_('Media Manager: Selected media could not be ') . $actionStr . '. ' . $lockedEditsStr;
				$data['message'] = 'error';
			}

			$data['notice'] = $actionTaken;
			if($actionTaken2) $data['notice2'] = $actionTaken2;

		}// end if count($media)

		return $data;

	}

	/**
	 * Tag/Untag media.
	 *
	 * Called by actionMedia on behalf of executeAjax() to tag/untag media.
	 * Only used in ProcessMediaManager context (i.e. in process rather than page-edit (inputfield) context).
	 * Only those with 'media-manager-edit' permission (if present) will be able to select the action.
	 *
	 * @access private
	 * @param integer $action Whether to tag or untag. 0=untag; 1=tag.
	 * @return array $data To JSON-Encode as a response to the ajax call.
	 *
	 */
	private function actionTag($action) {

		$data = array();
		$media = $this->media;
		$options = $this->options;

		// @access-control: media-manager-edit
		if($this->noEdit) {
			$data['message'] = 'error';
			$data['notice'] = $this->_('Media Manager: You have no permission to edit.');
			return $data;
		}

		$pages = $this->wire('pages');
		$actionTaken = '';
		$actionTaken2 = '';// in case some success + some errors
		$actionStr = $action ? $this->_('tagged') : $this->_('untagged');
		$mediaFields = array(1=>'audio', 2=>'document', 3=>'image', 4=>'video');
		$versionType = '';
		$tags = '';
		$tagMode = '';

		if(count($media)) {

			$i = 0;// count for success actions
			$j = 0;// count for failed actions
			if($action) {
				$tags = $this->wire('sanitizer')->text($options->tags);
				$tagMode = $options->tagMode;
			}

			// we need to include the file just in case FieldtypeMediaManager is not being used/hasn't been installed yet
			$dir = dirname(__FILE__);
			require_once("$dir/MediaManager.php");

			foreach ($media as $m) {

				$idType = explode('_', $m);

				$p = $pages->get((int) $idType[0]);
				if(!$p->id) continue;

				// determine field we are dealing with
				$type = (int) substr($idType[1], 0, 1);
				$mediaField = 'media_manager_' . $mediaFields[$type];

				// if media page locked for edits
				if($p->is(Page::statusLocked)) {
					$j++;
					continue;
				}

				$t = '';
				// dealing with normal media
				$mf = $p->$mediaField->first();

				// untag media
				if($action == 0) {
					$mf->tags = '';
					$p->save($mediaField);
					// confirm successfully untagged
					if (!$mf->tags) $i++;
					else $j++;
				}

				// tag media
				elseif($action == 1) {
					// overwritting existing tags
					if($tagMode == 2) {
						//$mf->tags = $t = $tags;
						$t = $tags;
						$mf->tags(explode(' ', $tags));
					}
					// appending to existing tags
					else {
						$existingTags = $mf->tags;
						$t =  trim($existingTags . ' ' . $tags);
						/*$mf->tags = $t; */
						$mf->addTag(explode(' ', $tags));
					}

					$p->save($mediaField);
					$i++;

					// @todo: GETTING ERRORS BECAUSE OF COMPARISON HERE; LEAVE OUT FOR NOW
					// confirm successfully tagged
					/*if($p->$mediaField->$mf->tags == $t) $i++;
					else $j++;*/
				}

				else $j++;

			}// end foreach

			/* prepare responses */

			if($i > 0) {
				$actionTaken = sprintf(__('Media Manager: %1$d media %2$s.'), $i, $actionStr);
				// @note: for error message even though we have success above
				if($j) $actionTaken2 = sprintf(__('Media Manager: %1$d media locked for edits and could not be %2$s.'), $j, $actionStr);
				$data['message'] = 'success';
			}

			// if we could not tag any media
			else {
				$lockedEditsStr = $this->_(' Media locked for edits.');
				$actionTaken = $this->_('Media Manager: Selected media could not be ') . $actionStr . '. ' . $lockedEditsStr;
				$data['message'] = 'error';
			}

			$data['notice'] = $actionTaken;
			if($actionTaken2) $data['notice2'] = $actionTaken2;

		}// end if count($media)

		return $data;

	}

	/**
	 * Trash/Delete media.
	 *
	 * Called by actionMedia on behalf of executeAjax() to trash/delete media.
	 * Only used in ProcessMediaManager context (i.e. in process rather than page-edit (inputfield) context).
	 * Only those with 'media-manager-delete' permission (if present) will be able to select the action.
	 *
	 * @access private
	 * @param integer $action Whether to trash or delete. 0=delete; 1=trash.
	 * @return array $data To JSON-Encode as a response to the ajax call.
	 *
	 */
	private function actionDelete($action) {

		$data = array();
		$media = $this->media;

		// @access-control: media-manager-delete
		if($this->noDelete) {
			$data['message'] = 'error';
			$data['notice'] = $this->_('Media Manager: You have no permission to trash/delete media.');
			return $data;
		}

		$pages = $this->wire('pages');
		$actionTaken = '';
		$actionTaken2 = '';// in case some success + some errors
		$actionStr = $action ? $this->_('trashed') : $this->_('deleted');

		if(count($media)) {

			$i = 0;// count for success actions
			$j = 0;// count for failed actions

			foreach ($media as $m) {
				$idType = explode('_', $m);

				$p = $pages->get((int) $idType[0]);
				if(!$p->id) continue;

				// if media page locked for edits
				if($p->is(Page::statusLocked)) {
					$j++;
					continue;
				}

				// delete media
				if($action == 0) {
					$p->delete();// delete the page
					//$pages->delete($p);
					//if($pages->get((int) $idType[0])->id == 0) $i++;// confirm deleted
					// @note: above acting strangely in some earlier versions of PW 3! Although page deleted, it still shows up until reload!
					// doing it this way using find seems to do the trick
					$deletedPage = $pages->find("id=" .(int) $idType[0]);
					if(!$deletedPage->id) $i++;// confirm deleted
					else $j++;// found page but for some reason failed to delete
				}

				// trash media
				elseif($action == 1) {
					$pages->trash($p);// trash the page
					if ($p->is(Page::statusTrash)) $i++;// confirm trashed;
					else $j++;// found page but for some reason failed to trash

				}

				else $j++;

			}// end foreach

			/* prepare responses */
			if($i > 0) {
				$actionTaken = sprintf(__('Media Manager: %1$d media %2$s.'), $i, $actionStr);
				// @note: for error message even though we have success above
				if($j) $actionTaken2 = sprintf(__('Media Manager: %1$d media locked for edits and could not be %2$s.'), $j, $actionStr);
				$data['message'] = 'success';
			}

			// if we could not trash/delete any media
			else {
				$lockedEditsStr = $this->_(' Media locked for edits.');
				$actionTaken = $this->_('Media Manager: Selected media could not be ') . $actionStr . '. ' . $lockedEditsStr;
				$data['message'] = 'error';
			}

			$data['notice'] = $actionTaken;
			if($actionTaken2) $data['notice2'] = $actionTaken2;

		}// end if count($media)

		return $data;

	}

	/**
	 * Delete files uploaded for scanning.
	 *
	 * @access private
	 * @return array $data To JSON-Encode as a response to the ajax call.
	 *
	 */
	private function actionScanDelete() {

		$data = array();
		$files = $this->media;
		$successCount = 0;
		$failCount = 0;

		// @access-control: media-manager-delete
		if($this->noDelete) {
			$data['message'] = 'error';
			$data['notice'] = $this->_('Media Manager: You have no permission to delete files.');
			return $data;
		}

		$actionTaken = '';
		$actionTaken2 = '';// in case some success + some errors

		if(count($files)) {

			foreach ($files as $path) {
				if(is_file($path)) unlink($path);
				// delete failed
				if(is_file($path)) {
					$failCount++;
					$data[$path] = 'Error';
				}
				// delete succeeded
				else {
					$successCount++;
					$data[$path] = true;
				}
			}

		}// end if count($files)

		// we didn't get files @todo: this should be like normal MM messages?
		else $data['message'] = $this->_('error');

		$data['count_total'] = count($files);
		$data['count_success'] = $successCount;
		$data['count_fail'] = $failCount;

		return $data;

	}

	/**
	 * Insert/add media to a page being edited.
	 *
	 * Called by actionMedia on behalf of executeAjax() to insert media.
	 * Only used in InputfieldMediaManager context (i.e. in page-edit rather than process (ProcessMediaManager) context).
	 * Media is not inserted directly on the page but via its FieldtypeMediaManager.
	 *
	 * @access private
	 * @return array $data To JSON-Encode as a response to the ajax call.
	 *
	 */
	private function actionInsert() {

		$data = array();

		$media = $this->media;
		// if in 'upload_insert' mode, we have the property $this->media2 set on the fly by ActionCreate() which then calls this method

		$currentPage = $this->currentPage;// page object
		$mediaManagerField = $this->mediaManagerField;// mm field object

		// for checking if the MediaManager field restricts allowed media types (e.g. could only be accepting image media)
		$allowedMedia = $mediaManagerField->allowedMedia;
		$allowedMediaTypes = array();
		$limitMediaTypes = false;

		if($allowedMedia != null && is_array($allowedMedia)) {
			$limitMediaTypes = true;
			$allowedMediaTypes = $allowedMedia;
		}

		// current field count for both 'max files' (if needed) and gallery preview slides count
		$currentFieldCnt = $currentPage->$mediaManagerField->count();

		// check if 'max media files' in place
		$limitMediaCnt = $mediaManagerField->maxFiles;
		$maxFiles = false;
		if($limitMediaCnt) {
			$maxFiles = true;
			$allowable = $limitMediaCnt - $currentFieldCnt;// calculate allowable (net)
		}

		// check if editable (@note: in MM only from point of view of allowed in field)
		// @note: here, we also need to check $this->noEdit. This is because $mediaManagerField->allowEditSelectedMedia just says, if media is editable, allow it to be edited
		$editable = 1 == (int) $mediaManagerField->allowEditSelectedMedia && !$this->noEdit ? 1 : 0;

		$pages = $this->wire('pages');
		$actionTaken = '';
		$actionTaken2 = '';// in case some success + some errors

		if(count($media)) {

			$i = 0;// count for success actions
			$j = 0;// count for failed actions

			foreach ($media as $m) {

				// if max files allowed is in effect and we've reached the limit, get out
				if($maxFiles && $allowable < 1) break;

				// just making sure media page exists
				$idType = explode('_', $m);
				$p = $pages->get((int) $idType[0]);
				if(!$p->id) continue;
				// skip unpublished pages
				if($p->is(Page::statusUnpublished)) {
					$j++;
					continue;
				}

				// if same media already in field, skip it
				$type = (int) $idType[1];
				if($currentPage->$mediaManagerField->has("id=$p->id, type=$type")) {
					$j++;
					continue;
				}

				// get right media type for the current media
				$mediaTypeInt = (int) $idType[1];

				// check if the MediaManager field restricts allowed media types (e.g. could only be accepting image media)
				// using substr allows us to check image variations as well
				if($limitMediaTypes && !in_array((int)substr($mediaTypeInt, 0, 1), $allowedMediaTypes)) {
					$j++;
					continue;
				}

				$options = array(
					'media_type_int' => $mediaTypeInt,
					'action' => $this->action,
					'editable' => $editable,
				);

				/*
					params:
					1. $currentPage: current page with mm inputfield
					2. $mediaManagerField: the mm field in the current page associated with the inputfield
					3. $p: the media page being added to the inputfield
					4. $options: array of options for building ajax data ($mediaTypeInt, $action)
				*/
				$pageAjaxData = $this->mmUtilities->buildPageAjaxData($currentPage, $mediaManagerField, $p, $options);

				#################################

				// build ajax data for this page
				// @note: this means we cannot insert variations since they share the same $p->id! Let array build naturally instead + we've added a 'mediaID' index in $pageAjaxData array
				// we store $p->id in $pageAjaxData['mediaID']
				//$data['media'][$p->id] = $pageAjaxData;
				$data['media'][] = $pageAjaxData;

				// insert media in the current page's inputfield (instance of FiedltypeMediaManager)
				#$currentPage->of(false);// @note: output formatting is always off in module context
				$m = new MediaManager();
				$m->id = $p->id;
				$m->type = $mediaTypeInt;

				$currentPage->$mediaManagerField->add($m);
				$currentPage->save($mediaManagerField);
				$i++;
				if($maxFiles) $allowable--;


			}// end foreach

			/* prepare responses */

			if($i > 0) {
				$actionTaken = sprintf(__('Media Manager: %d media Added to the Page.'), $i);
				// @note: for error message even though we have success above
				if($j) $actionTaken2 = sprintf(__('Media Manager: %d media could not be added to the page. They might already be on the page or are unpublished or maximum allowed media reached or the media type is not allowed for this field.'), $j);
				$data['message'] = 'success';
			}

			// if we could not insert any media
			else {
				$actionTaken = $this->_('Media Manager: Selected media could not be added to the page. They could be already on the page or unpublished or maximum allowed media reached or the media type is not allowed for this field.');
				$data['message'] = 'error';
			}

			$data['notice'] = $actionTaken;
			if($actionTaken2) $data['notice2'] = $actionTaken2;

		}// end if count($media)

		return $data;

	}

	/**
	 * Return data to update parent page whose media has been updated.
	 *
	 * Called by actionMedia on behalf of executeAjax() to update media.
	 * Only used in InputfieldMediaManager context (i.e. in page-edit rather than process (ProcessMediaManager) context).
	 * @note: no CRUD happens here.
	 *
	 * @access private
	 * @return array $data To JSON-Encode as a response to the ajax call.
	 *
	 */
	private function actionEditSingleMedia() {

		$data = array();

		// @note: there should be only one item here! The ID of the media that was updated
		$media = $this->media;

		if(!count($media)) return;

		$currentPage = $this->currentPage;// page object
		$mediaManagerField = $this->mediaManagerField;// mm field object
		$id = (int) $media[0];

		// just making sure media page exists
		$p = $this->wire('pages')->get($id);
		// @note: we skip media pages in trash AND unpublished
		$p = $this->wire('pages')->get("id=$id,status<" . Page::statusTrash . ",status<" . Page::statusUnpublished);
		//if(!$p->id) return;

		if($p && $p->id > 0) {
			$mediaInField = $currentPage->$mediaManagerField->get("id=$id");
			$mediaTypeInt  = $mediaInField->type;
			// @todo: We don't really need this since the modal has been opened for editing, meanign editable is true. The only we'd need this is if during the course of the editing, a different operation has made the page NOT editable! That's highly unlikely?
			// check if editable (@note: in MM only from point of view of allowed in field)
			// @note: here, we also need to check $this->noEdit. This is because $mediaManagerField->allowEditSelectedMedia just says, if media is editable, allow it to be edited
			$editable = 1 == (int) $mediaManagerField->allowEditSelectedMedia && !$this->noEdit ? 1 : 0;
			$options = array(
				'media_type_int' => $mediaTypeInt,
				'action' => $this->action,
				'editable' => $editable,
			);
			/*
				params:
				1. $currentPage: current page with mm inputfield
				2. $mediaManagerField: the mm field in the current page associated with the inputfield
				3. $p: the edited media page in this mm field inputfield
				4. $options: array of options for building ajax data ($mediaTypeInt, $action)
			*/
			$pageAjaxData = $this->mmUtilities->buildPageAjaxData($currentPage, $mediaManagerField, $p, $options);
			// build ajax data for this edited media page
			// @note: due to issues with inserting variations in actionInsert(), we store $p->id in $pageAjaxData['mediaID']
			//$data['media'][$p->id] = $pageAjaxData;
			$data['media'][] = $pageAjaxData;
			$data['editMediaID'] = $p->id;
		}

		else {
			$data['action'] = 'removed';
			$data['media'][] = array('mediaID' => $id);
		}





		return $data;

	}

	/**
	 * Upload media.
	 *
	 * Uploads media to a set directory for processing and saving.
	 * Only those with 'media-manager-upload' permission will be able to see the uploads widget.
	 * Used by both Scan and Upload media actions.
	 *
	 * @access private
	 * @return array $data to feedback via notices as a response to the post.
	 *
	 */
	private function actionUploadToMediaLibrary() {

		$data = array();
		$options = $this->options;

		/*
			 @note:
			 	- method very similar to actionScanMedia() except here we have no form or media to pass in as a parameter
				- we are dealing with previously uploaded, processed and validated data ready to be added to the Media Library
		 */

		// @access-control: media-manager-upload
		// @note: if in ProcessMediaManager, they will not see the uploads widget. Upload anywhere will also be disabled here and in Inputfield.
		if($this->noUpload) {
			$data['message'] = 'error';
			$data['notice'] = $this->_('Media Manager: You have no permission to add media to the Media Library SUCKER!.');
			return $data;
		}

		if(!$this->options->dir) {
			$data['message'] = 'error';
			$data['notice'] = $this->_('Media Manager: You need to specify a directory from which files will be added to the Media Library.');
			return $data;
		}

		$data = $this->actionCreateMedia();

		return $data;

	}

	/**
	 * Save media manager upload settings.
	 *
	 * Only those with 'media-manager-settings' permission (if present) will be able to edit and save settings.
	 *
	 *	@access public
	 *
	 */
	public function actionSaveSettings() {

		$form = $this->media;

		// @access-control: media-manager-settings
		if($this->noSettings) {
			$data = array();
			$data['message'] = 'error';
			$data['notice'] = $this->_('Media Manager: You have no permission to edit these settings.');
			return $data;
		}

		$savedSettings = $this->savedSettings;

		// process form
		$post = $this->wire('input')->post;
		$form->processInput($post);
		$sanitizer = $this->wire('sanitizer');
		$fields = $this->wire('fields');
		$page = $this->wire('page');

		// @note: media_manager_settings_after_upload ? could have been 'after' but quirky PHP will read '0' as being here!
		$intSanitize = array('minFileSize', 'setMaxFileSize', 'setMaxFiles', 'limitMultiFileUploads', 'limitMultiFileUploadSize', 'limitConcurrentUploads', 'loadImageMaxFileSize', 'imageMinWidth', 'imageMinHeight', 'previewMinWidth', 'previewMinHeight', 'previewMaxWidth', 'previewMaxHeight', 'loadAudioMaxFileSize', 'loadVideoMaxFileSize', 'mm_upload_anywhere', 'unzip_files', 'mm_upload_actions_render'
		);

		$cdataSanitize = array('imageOrientation', 'imageForceResize', 'previewOrientation', 'char');

		$fieldNameArraySanitize = array('media');

		$intArraySanitize = array('image', 'audio', 'document', 'video');

		// saved separately in the respective media manager media fields
		$mediaExtensions = array('image' => 'validExtensionsImage', 'audio' => 'validExtensionsAudio', 'video' => 'validExtensionsVideo', 'document' => 'validExtensionsDocument');
		$imageDimensions = array('maxWidth' => 'imageMaxWidth', 'maxHeight' => 'imageMaxHeight');

		// upload settings post
		$mmAfterUpload = $post->mm_settings_after_upload;
		$mmValidation = $post->mm_settings_validation;
		$mmMode = $post->mm_settings_mode;
		$mmDocument = $post->mm_settings_document;
		$mmMediaTitleFormat = $post->mm_settings_title_format;
		// @note: cannot work; @see notes in MediaManagerRender::uploadSettings
		#$mmMediaTitleFormatChar = $mmMediaTitleFormat == 4 ? $post->mm_settings_title_format_char : '';
		$mmDuplicateMedia = $post->mm_settings_duplicates;
		$mmDeleteVariations = $mmDuplicateMedia == 3 ? $post->mm_settings_replace_delete_variations : '';
		$mmDuplicateMediaReplaceConfirm = $mmDuplicateMedia == 3 ? $post->mm_settings_duplicate_replace_confirm : '';
		// other settings post
		$mmUserMedia = $post->mm_settings_user_media;// display only current user's media
		$mmSortBy = $post->mm_settings_sort;
		$mmSortOrder = $post->mm_settings_sort_order;
		$mmShowFilterProfiles = $post->mm_show_filter_profiles;
		$mmAllowedMedia = $post->mm_allowed_media;
		$mmDisableAllMediaView = $post->mm_disable_all_media_view;
		$mmUploadAnywhere = $post->mm_upload_anywhere;
		$mmUnzipFiles = $post->mm_unzip_files;
		// custom columns post
		$mmCustomColumnsImage = $post->mm_custom_columns_image;
		$mmCustomColumnsAudio = $post->mm_custom_columns_audio;
		$mmCustomColumnsDocument = $post->mm_custom_columns_document;
		$mmCustomColumnsVideo = $post->mm_custom_columns_video;

		$mmSettings = array();
		// upload settings
		$mmSettings['after'][] = $mmAfterUpload;
		$mmSettings['validation'] = $mmValidation;
		$mmSettings['mode'] = $mmMode;

		// save upload settings for each allowed media type
		$media = array('audio', 'document', 'image', 'video');
		foreach($media as $m) {
			$mediaSetting = $post->{"mm_settings_{$m}"};
			if(!is_null($mediaSetting)) $mmSettings[$m] = $mediaSetting;
		}

		$mmSettings['title_format'][] = $mmMediaTitleFormat;
		#$mmSettings['title_format_char']['char'] = $mmMediaTitleFormatChar;
		$mmSettings['duplicates'][] = $mmDuplicateMedia;
		$mmSettings['delete_variations'][] = $mmDeleteVariations;
		$mmSettings['duplicates_replace_confirm'][] = $mmDuplicateMediaReplaceConfirm;
		// other settings post
		$mmSettings['user_media_only'][] = $mmUserMedia;
		$mmSettings['sort_media'][] = $mmSortBy;
		$mmSettings['sort_media_order'][] = $mmSortOrder;
		$mmSettings['show_filter_profiles'][] = $mmShowFilterProfiles;
		$mmSettings['allowed_media']['media'] = $mmAllowedMedia;
		$mmSettings['disable_all_media_view'][] = $mmDisableAllMediaView;
		$mmSettings['upload_anywhere'][] = $mmUploadAnywhere;
		$mmSettings['unzip_files'][] = $mmUnzipFiles;
		// custom columns
		$mmSettings['custom_columns']['image'] = $mmCustomColumnsImage;
		$mmSettings['custom_columns']['audio'] = $mmCustomColumnsAudio;
		$mmSettings['custom_columns']['document'] = $mmCustomColumnsDocument;
		$mmSettings['custom_columns']['video'] = $mmCustomColumnsVideo;

		##########################

		$mmSettingsFinal = array();

		foreach ($mmSettings as $key => $value) {
			foreach ($value as $k => $v) {

				// first we deal with values destined for media manager media fields (file extensions for each media type)
				// the '0' here is not really important but we are are just removing $mmAfterUpload, etc from being checked
				if(in_array($k, $mediaExtensions) && $k !== 0) {
					$fileType = array_search($k, $mediaExtensions);
					$v = $sanitizer->text($v);
					$f = $fields->get('media_manager_' . $fileType);
					$f->extensions = $v;
					$f->save();
					continue;// these are not saved to media_manager_settings
				}
				// for image max width and max height settings
				// we save values to the field media_manager_image maxWidth and maxHeight respectively
				elseif(in_array($k, $imageDimensions) && $k !== 0) {
					$dimensionType = array_search($k, $imageDimensions);
					$v = (int) $v;
					$f = $fields->get('media_manager_image');
					$f->$dimensionType = $v ? $v : '';
					$f->save();
					continue;/// these are not saved to media_manager_settings
				}

				// custom columns asm select arrays
				elseif(in_array($k, $fieldNameArraySanitize, true)) {// @note: strict compare
					if(is_array($v)) $v = $sanitizer->array($v, 'fieldName');
					else $v = '';// null was passed; will not be saved (WireEncode)
				}
				// integer values saved to media_manager_settings
				elseif(in_array($k, $intSanitize)) $v = (int) $v;

				// custom columns asm select arrays
				elseif(in_array($k, $intArraySanitize)) {
					if(is_array($v)) $v = $sanitizer->intArray($v);// @note: if saving $field->id
					//if(is_array($v)) $v = $sanitizer->array($v, 'fieldName');// @note: if saving $field->name
					else $v = '';// null was passed; will not be saved (WireEncode)
				}

				// values that could be either integers or strings saved to media_manager_settings
				elseif(in_array($k, $cdataSanitize)) {
					if(is_integer($v)) $v = (int) $v;
					else $v = $sanitizer->text($v);
				}
				// string values saved to media_manager_settings
				else $v = $sanitizer->text($v);

				$mmSettingsFinal[$key][$k] = $v;
			}// end inner foreach

		}// end foreach

		// extract filter profile settings so as not to overwrite them
		$filterSettings['filters'] = isset($savedSettings['filters']) ? $savedSettings['filters'] : array();
		// merge filter settings if present to final mm settings
		if(count($filterSettings['filters'])) $mmSettingsFinal = array_merge_recursive($mmSettingsFinal, $filterSettings);

		// JSON string of media manager settings to save
		#$mmSettingsJSON = count($mmSettingsFinal) ? json_encode($mmSettingsFinal) : '';
		$mmSettingsJSON = count($mmSettingsFinal) ? wireEncodeJSON($mmSettingsFinal) : '';// wont save empties

		$mmSettingsPage = $page->child('template=media-manager-settings, include=hidden');

		if($mmSettingsPage->id) {
			$mmSettingsPage->media_manager_settings = $mmSettingsJSON;
			$mmSettingsPage->save('media_manager_settings');
			$this->message($this->_('Media Manager: Settings saved.'));

			// redirect. @note: need this to 'properly reload' the page in order to see newly saved settings/values
			//$this->session->redirect($page->url . 'settings/');
			$this->session->redirect('.');// @note: will direct and include any urlSegments as needed
		}

	}

	/**
	 * Save actions for a single filter profile.
	 *
	 * Used by MediaManagerRender::renderFilterConfig().
	 *
	 * @access private
	 *
	 */
	private function actionCreateEditFilter() {

		$sanitizer = $this->wire('sanitizer');
		$page = $this->wire('page');

		$form = $this->media;

		// process form
		$post = $this->wire('input')->post;
		$form->processInput($post);
		$savedSettings = $this->savedSettings;
		$filterSettings = array();

		$oldFilterName =  $sanitizer->pageName($post->mm_edit_filter_name);
		$filterTitle =  $sanitizer->text($post->mm_edit_filter_title);
		$filterName = $sanitizer->pageName($filterTitle);
		$defaultSelector = $post->defaultSelector;

		if(!$filterTitle) $this->session->redirect($page->url . 'filters/' . $oldFilterName . '/?modal=1');

		// if editing existing filters title
		if($oldFilterName !== $sanitizer->pageName($filterTitle)) {
			if(isset($savedSettings['filters'][$filterName])) {
				$this->session->redirect($page->url . 'filters/' . $oldFilterName . '/?modal=1');
			}
			// we are changing the name, remove the old record
			else {
				unset($savedSettings['filters'][$oldFilterName]);
				// if it was the active filter, we also amend that
				if(isset($savedSettings['active_filter'])) {
					$activeFilter = $savedSettings['active_filter'];
					if($activeFilter == $oldFilterName) $savedSettings['active_filter'] = $filterName;
				}
			}
		}// end old filter name check

		// set filter values
		$filterSettings['filters'][$filterName]['defaultSelector'] = $defaultSelector;
		$filterSettings['filters'][$filterName]['title'] = $filterTitle;

		// @note: careful not to delete existing media manager settings!
		$mmSettingsFinal = array_replace_recursive($savedSettings, $filterSettings);

		#################

		// JSON string of media manager settings to save
		$mmSettingsJSON = count($mmSettingsFinal) ? wireEncodeJSON($mmSettingsFinal) : '';// wont save empties
		$mmSettingsPage = $page->child('template=media-manager-settings, include=hidden');

		if($mmSettingsPage->id) {
			$mmSettingsPage->media_manager_settings = $mmSettingsJSON;
			$mmSettingsPage->save('media_manager_settings');
			$this->session->redirect($page->url . 'filters/' . $filterName . '/?modal=1');
		}

	}

	/**
	 * Save actions for multiple filter profiles.
	 *
	 * Processes: active filter, deactivate active filter, lock, unlock and delete filters.
	 *
	 * Used by MediaManagerRender::renderFilterConfigEdit().
	 *
	 * @access private
	 *
	 */
	private function actionFilterProfiles() {

		$sanitizer = $this->wire('sanitizer');
		$page = $this->wire('page');
		$mmSettingsFinal = array();
		$data = array();
		$i = 0;
		$j = 0;

		$form = $this->media;

		// process form
		$post = $this->wire('input')->post;
		$form->processInput($post);
		$savedSettings = $this->savedSettings;

		// @note: to satisfy actionMedia() but nothing sent back in non-ajax post
		if(!is_array($post->mm_filter_title)) {
			$this->error($this->_('You need to select at least one filter to action.'));
			return $data;
		}

		$actionItems = $post->mm_filter_title;
		// 0=set_active;1=deactivate/unset active;2=lock;3=unlock;4=delete
		//$actionValue = (int) $post->_action_value;
		$actionValue = $sanitizer->pageName($post->_action_value);

		$error = false;
		// set/unset active filter: dealing with one item >>> we get the first
		if(!$actionValue || 'deactivate' == $actionValue) {
			$filterTitle = $actionItems[0];
			$filterName = $sanitizer->pageName($filterTitle);
			// set active filter
			if(!$actionValue) {
				$actionValue = 'active';
				// first check if we have this filter. if yes, set it as active
				if(isset($savedSettings['filters'][$filterName])) {
					$savedSettings['active_filter'] = $filterName;
					$i++;
				}
				else {
					$this->error(sprintf(__('The filter %s was not found. Cannot set it as active.'), $filterName));
					$error = true;
				}
			}
			// unset/deactivate filter as active
			else {
				// we make sure we had an active filter set + match the name of the current active filter to requested one
				if(isset($savedSettings['active_filter']) && $savedSettings['active_filter'] == $filterName) {
					unset($savedSettings['active_filter']);
					$i++;
				}
				else {
					$this->error(sprintf(__('The filter %s is not the active filter. Cannot unset.'), $filterName));
					$error = true;
				}
			}
		}
		// bulk actions: lock/unlock/delete
		else {

			foreach ($actionItems as $filterTitle) {
				$filterName = $sanitizer->pageName($filterTitle);
				// we didn't find that filter, skip
				if(!isset($savedSettings['filters'][$filterName])) continue;
				// lock filters
				if('lock' == $actionValue) $savedSettings['filters'][$filterName]['locked'] = 1;
				// unlock filters
				elseif('unlock' == $actionValue) $savedSettings['filters'][$filterName]['locked'] = 0;
				// delete filters
				elseif('delete' == $actionValue) {
					// filter locked; cannot be deleted
					if(isset($savedSettings['filters'][$filterName]['locked']) && $savedSettings['filters'][$filterName]['locked']) {
						$j++;
						continue;
					}
					else unset($savedSettings['filters'][$filterName]);
				}
				else {
					// @todo? ERROR?!
				}

				$i++;

			}// end foreach
		}

		if($error) return $data;

		$actionTakenArray = array(
			'active' => sprintf(__('Active filter set to %s.'), $filterName),
			'deactivate' => sprintf(__('%s unset as active filter.'), $filterName),
			'lock' => sprintf(_n("%d filter locked.", "%d filters locked.", $i), $i),
			'unlock' => sprintf(_n("%d filter unlocked.", "%d filters unlocked.", $i), $i),
			'delete' => sprintf(_n("%d filter deleted.", "%d filters deleted.", $i), $i),
		);

		// if all filters deleted, remove parent array
		if(isset($savedSettings['filters']) && !count($savedSettings['filters'])) unset($savedSettings['filters']);

		###################

		// just for consistency really
		$mmSettingsFinal = $savedSettings;

		// JSON string of media manager settings to save
		$mmSettingsJSON = count($mmSettingsFinal) ? wireEncodeJSON($mmSettingsFinal) : '';// wont save empties
		$mmSettingsPage = $page->child('template=media-manager-settings, include=hidden');

		// save
		if($mmSettingsPage->id) {
			$mmSettingsPage->media_manager_settings = $mmSettingsJSON;
			$mmSettingsPage->save('media_manager_settings');
			$this->message($actionTakenArray[$actionValue]);
			if($j) {
				$filtersLocked = sprintf(_n("%d filter locked for edits", "%d filters locked for edits", $j), $j);
				$filtersLocked .= " " . $this->_('and could not be deleted');
				$this->error($filtersLocked);
			}
		}
		// settings page deleted/unreachable!
		else $this->error('The settings page could not be found! Please ensure that it was not deleted.');

		// redirect. @note: need this to 'properly reload' the page in order to see newly saved settings/values
		//$this->session->redirect($page->url . 'filters/');
		$this->session->redirect('.');// @note: will direct and include any urlSegments as needed

		return $data;// just to stop actionMedia() complaining!

	}


	/**
	 * Create media (pages) from uploaded media.
	 *
	 * Media pages will be created and given parents and templates according to the four media types + formats specified in the respective fields.
	 * Accepted media extensions are specified in the four fields: 'media_manager_audio', 'media_manager_document', 'media_manager_image' and 'media_manager_video'.
	 *
	 *	@access private
	 *  @return array $data To feedback as a response the ajax call.
	 *
	 */
	private function actionCreateMedia() {

		$data = array();
		$data2 = array();

		$publish = (int) $this->options->publish;// @note: this had already been sanitized so this is not necessary, but, hey...
		$dir = $this->options->dir;
		$thumbsDir = $this->options->thumb;

		$pages = $this->wire('pages');

		// if directory with files not found
		if(!is_dir($dir)) {
			$data['message'] = 'error';
			$data['notice'] = $this->_('Media Manager: No media found.');
			return $data;
		}

		// prepare some variables we'll need later
		$a = 0;// for audio media pages count
		$b = 0;// for document media pages count
		$c = 0;// for image media pages count
		$d = 0;// for video media pages count

		$savedSettings = $this->savedSettings;
		$failed = array();
		$validationOptions = array();
		$adminPage = $pages->get($this->wire('config')->adminRootPageID);
		$mediaParent = $pages->get("parent=$adminPage, template=admin, include=all, name=".self::PAGE_NAME);
		$afterSaveSetting = isset($savedSettings['after'][0]) ? $savedSettings['after'][0] : 2;

		// for validation
		$validExts = $this->mmUtilities->validExtensions();


		$minFileSize = isset($savedSettings['validation']['minFileSize']) ? $savedSettings['validation']['minFileSize'] : '';
		$maxFileSize = isset($savedSettings['validation']['setMaxFileSize']) ? $savedSettings['validation']['setMaxFileSize'] : '';
		$imageMinWidth = isset($savedSettings['image']['imageMinWidth']) ? (int)$savedSettings['image']['imageMinWidth'] : 0;
		$imageMinHeight = isset($savedSettings['image']['imageMinHeight']) ? (int)$savedSettings['image']['imageMinHeight'] : 0;

		$f = $this->wire('fields')->get('media_manager_image');
		$imageMaxWidth =(int)$f->maxWidth;
		$imageMaxHeight = (int)$f->maxHeight;

		$validationOptions['commonImageExts'] = $this->mmUtilities->validExtensionsImage();
		$validationOptions['createThumb'] = false;
		$validationOptions['allowedImageMimeTypes'] = $this->mmUtilities->allowedImageMimeTypes();
		$validationOptions['allowedNonImageMimeTypes'] = $this->mmUtilities->allowedNonImageMimeTypes();
		$validationOptions['imageTypeConstants'] = array('gif' => IMAGETYPE_GIF, 'jpeg' => IMAGETYPE_JPEG, 'jpg' => IMAGETYPE_JPEG, 'png' => IMAGETYPE_PNG);

		// check for filenaming + duplicate media settings
		$titleFormat = isset($savedSettings['title_format'][0]) ? $savedSettings['title_format'][0] : 1;
		$duplicateMedia = isset($savedSettings['duplicates'][0]) ? $savedSettings['duplicates'][0] : 1;
		$deleteVariations = isset($savedSettings['delete_variations'][0]) ? $savedSettings['delete_variations'][0] : 0;

		// get jQueryFileUpload to help validate scanned uploads
		$jfu = new JqueryFileUpload();

		//recursively iterate our path, skipping  system folders
		$directory = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);

		// in this foreach, we go deeper into other folders - recursively and start with the parent item first, then its children, etc
		//foreach (new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::SELF_FIRST) as $path ) {
		// in this foreach, we go deeper into other folders - recursively and start with the CHILD item first, then its parent, etc
		// in this way, we can remove empty directories as we go along
		foreach (new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::CHILD_FIRST) as $path ) {

			/*
				NOTES:
				- getPath()
					 * Gets the path without filename
					 * http://uk3.php.net/manual/en/splfileinfo.getpath.php

				- basename()
					 * Returns trailing name component of path
					 * http://uk3.php.net/manual/en/function.basename.php
					 * We use this to trim the getPath() value leaving only the final segment
					 * e.g. abc/def/ghi will return 'ghi'

				- getBasename()
					 * Gets the base name of the file
					 * http://uk3.php.net/manual/en/splfileinfo.getbasename.php
					 * e.g. Album 5 photo 3.txt or Album 1 Sub-album 6 {sub-album}

			 */

			set_time_limit(180);// try to avoid timing out

			// skip folders (directories)
			//if(!$path->isFile()) continue;
			### @note: new approach: WE START WITH CHILDREN, WE THEN REMOVE EMPTY DIRECTORIES ####
			// skip and remove folders (directories)
			if(!$path->isFile()) {
				$this->wire('files')->rmdir($path);
				continue;
			}

			/* @note: all unzipping moved to JFU!*/

			// skip image files in 'thumbnails' folder (for jfu uploaded files)
			if(basename($path->getPath()) == basename($thumbsDir)) continue;

			/* #### validation #### */


			// if in 'move to mm library mode' and file is not one of those to be moved, skip it
			// @note: 'move' includes scanned (ftp'd) files being 'moved' into mm library
			if($path->isFile() && $this->actionType == 'move' && !in_array($path->getFilename(),$this->media)
			) continue;

			// validation: file extension
			if($path->isFile() && !in_array($path->getExtension(), $validExts)) {
				unlink($path);// delete invalid file
				continue;
			}

			// validation: file mime_type
			$valid = $jfu->validateFile($path->getPath() . '/' . $path->getFilename(), $validationOptions);



			if(!$valid['valid']) {
				if($path->isFile()) unlink($path);
				continue;
			}


			// validation: file minimum-maximum size
			if(($minFileSize && $path->getSize() < $minFileSize) || ($maxFileSize && $path->getSize() > $maxFileSize)) {
				unlink($path);
				continue;
			}

			// validation: image file minimum-maximum-width-height
			$imageWidth = '';
			$imageHeight = '';
			if(in_array($path->getExtension(), $this->mmUtilities->validExtensionsImage())) {

				$imageInfo = getimagesize($path->getPath() . '/' . $path->getFilename());

				if($imageInfo) list($imageWidth, $imageHeight) = $imageInfo;

				// dimension too small
				if(($imageMinWidth && $imageWidth < $imageMinWidth) || ($imageMinHeight && $imageHeight < $imageMinHeight)) {
					unlink($path);
					continue;
				}
				// dimensions too big
				elseif(($imageMaxWidth && $imageWidth > $imageMaxWidth) || ($imageMaxHeight && $imageHeight > $imageMaxHeight)) {
					unlink($path);
					continue;
				}

			}


			/* #### create media pages #### */

			$template = '';

			/* determine template + parent for media */
			// audio media
			if(in_array($path->getExtension(), $this->mmUtilities->validExtensionsAudio())) {
				$template = 'media-manager-audio';
				$parent = $mediaParent->child('name=media-manager-audio, include=hidden');
				$mediaField = 'media_manager_audio';
				$type = 1;
			}
			// document media
			elseif(in_array($path->getExtension(), $this->mmUtilities->validExtensionsDocument())) {
				$template = 'media-manager-document';
				$parent = $mediaParent->child('name=media-manager-document, include=hidden');
				$mediaField = 'media_manager_document';
				$type = 2;
			}
			// image media
			elseif(in_array($path->getExtension(), $this->mmUtilities->validExtensionsImage())) {
				$template = 'media-manager-image';
				$parent = $mediaParent->child('name=media-manager-image, include=hidden');
				$mediaField = 'media_manager_image';
				$type = 3;
			}
			// video media
			elseif(in_array($path->getExtension(), $this->mmUtilities->validExtensionsVideo())) {
				$template = 'media-manager-video';
				$parent = $mediaParent->child('name=media-manager-video, include=hidden');
				$mediaField = 'media_manager_video';
				$type = 4;
			}

			// if no template v extension matched, skip
			if(!$template) continue;

			// title for the media page. We also get rid of the file extension + GET RID OF dot as well!
			$title = $path->getBasename('.' . $path->getExtension());


			/*********** we are ready to start creating media pages... ***********/

			$p = new Page();
			// get the template for this media
			$p->template = $this->wire('templates')->get($template);

			// @note: WireUpload -ed file so named as 'my_photo_file.jpg'. We need a friendly title here
			// title renaming masks. @note: we always go through this irrespective of scanned or uploaded media
			$p->title = $this->actionRenamer($title, $titleFormat);// @see @todo in actionRenamer()

			if(!$p->title) continue;// skip if no title provided (just in case, but unlikely in this case)
			$p->parent = $parent;

			// sanitize title and convert to a URL friendly page name
			$name = $this->wire('sanitizer')->pageName($p->title);

			// duplicates check + action
			$child = $p->parent->child("name={$name}, include=all");
			if($child->id) {

				// action 1: skip media
				if($duplicateMedia == 1) {
					// if the same name already exists, add it to the $failed array [to display to user in error later] and skip to next title
					$failed[] = $path->getFilename();
					continue;
				}

				// action 3: replace media. This means we only replace the media itself; no need to create new page
				elseif($duplicateMedia == 3) {

					/*
						@note: client-side, we are currently blocking 'start' uploads if 'confirm overwrite' is in place and its checkbox is not checked.

						@todo: for future version, it is good to create a double-lock on this by also making sure the checkbox setting is sent using JFU formData.
						@note: the confirm replace media setting is stored here: $savedSettings['duplicates_replace_confirm'][0])

					*/

					// if media locked skip it
					if($child->is(Page::statusLocked)) continue;

					// @note: here we change $p!
					$p = $child;
					// get the main media
					$mediaFirst = $p->$mediaField->first();
					// if no media, skip!
					if(!$mediaFirst) continue;
					// get (to preserve) existing tags and description
					$existingTags = $mediaFirst->tags;
					$existingDesc = $mediaFirst->description;

					// if replacing media + deleting existing variations
					if($deleteVariations) $p->$mediaField->deleteAll();
					// else delete only the main file
					else $p->$mediaField->delete($mediaFirst);
					$p->save();
					// add replacement media
					$p->$mediaField->add($path->getPathname());
					// get the media we just added
					$mediaLast = $p->$mediaField->last();
					// add tags and description to it
					$mediaLast->tags = $existingTags;
					$mediaLast->description = $existingDesc;
					// make it main media by pushing it to the top
					$p->$mediaField->prepend($mediaLast);
					#$p->save();// @note: no need to save here since we save below anyway

				}

				// else if here: action 2. rename + upload will kick in. ProcessWire will auto-rename the media if identically-named media found

			}// end if we found a duplicate $child

			// only assign name if creating new media
			if(!$child->id) $p->name = $name;//sanitize and convert to a URL friendly page name

			// if user did not check 'upload and publish' OR they have no publish permission, we save new media page unpublished
			if($publish !== 1 || $this->noPublish) $p->addStatus(Page::statusUnpublished);

			// save first time before adding media (for new media pages [including copy pages])
			$p->save();
			// if creating new media page or we are renaming the media (hence a copy)
			if(!$child->id || $duplicateMedia == 2) {
				// @note: no need for sanitize here; processwire does it internally!
				$p->$mediaField->add($path->getPathname());
			}

			$p->save();

			// if media is of type image, create or process (if already created by jfu) its lister thumb
			$thumb = $thumbsDir ? $thumbsDir . $path->getFilename() : '';
			if($type == 3) $this->actionProcessThumbnails($p, $thumb);

			// @todo: still using these?
			// media counts @note: not in full use currently
			if($type == 1) 		$a++;// audio media count
			elseif($type == 2) 	$b++;// document media count
			elseif($type == 3) 	$c++;// image media count
			elseif($type == 4) 	$d++;// video media count

			// delete the temp media
			unlink($path);

			$pages->uncacheAll(); // free some memory

			if($this->options->actionType2 == 'upload_insert') {
				//$this->media2 = array("{$p->id}_{$type}");;
				// populate array to send to actionInsert(), matching the format it expects
				$data2[] = "{$p->id}_{$type}";
			}


		}// end RecursiveIteratorIterator foreach

		// we recursively delete the directory /site/MediaManager/uploads/. We'll recreate it on demand in various upload methods as necessary
		// we only do this if 'action type is not move'! otherwise, we'll delete remaining media awaiting review!

		$files = $this->wire('files');
		if($this->actionType !='move') $files->rmdir($dir, true);

		// recreate the directory @note: may not always work but fallback is in parent::construct()
		$dir = $this->options->dir;
		if(!is_dir($dir)) $files->mkdir($dir);

		// create a string of "failed" media titles to add to error message
		$failedTitles = implode(', ', $failed);

		// @todo: $data HERE NOT WORKING! NOT GETTING RESPONSES!

		// give feedback to uploader
		$createdMediaTypesArray = array();
		$createdMediaTypes = '';

		if($a > 0) $createdMediaTypesArray[] = sprintf(_n("%d audio.", "%d audio.", $a), $a);
		if($b > 0) $createdMediaTypesArray[] = sprintf(_n("%d document.", "%d documents.", $b), $b);
		if($c > 0) $createdMediaTypesArray[] = sprintf(_n("%d image.", "%d images.", $c), $c);
		if($d > 0) $createdMediaTypesArray[] = sprintf(_n("%d video.", "%d video.", $d), $d);

		if(count($createdMediaTypesArray)) {
			$createdMediaTypes = implode(', ', $createdMediaTypesArray);
		}

		// failed due to naming
		#if($failedTitles) $this->error($this->_("Some media not added because names already in use. These are: {$failedTitles}."));
		if($failedTitles) {
			$notice2 = $this->_("Media Manager: Some media were not added because names already in use");
			$notice2 .= " ({$failedTitles}).";
			$data['notice2'] = $notice2;
		}

		// if adding media totally failed
		if ($a + $b + $c + $d == 0) {
			$data['message'] = 'error';
			$data['notice'] = $this->_('Media Manager: No valid media found to add to Media Library. If replacing media, check that they are not locked.');
		}
		else {
			$data['message'] = 'success';
			//$notice = $this->_('Media Manager: ');
			if($afterSaveSetting == 1) $successNoticeAppend = $this->_('Successfully published files to Media Library');
			else $successNoticeAppend = $this->_('Successfully added files (kept unpublished) to Media Library');
			$notice = sprintf(__('Media Manager: %s '), $successNoticeAppend);
			$notice .= " ({$createdMediaTypes}).";
			$data['notice'] = $notice;
		}

		$this->resetLister();

		// @note: 'uplod_insert: insert bit
		if($this->options->actionType2 == 'upload_insert') {
			// @note: we're done with $ths->media in the loop above, so can populate new values to it
			$this->media = $data2;
			$data2 = $this->actionInsert();
		}

		if(count($data2)) $data = array_merge($data,$data2);


		return $data;

	}

	/**
	 * Create or process thumbnails for uploaded image media.
	 *
	 * We do this during upload rather than when displaying media for the first time.
	 *
	 * @param Page $page Page to create/process a thumb for.
	 * @param string $thumb Path to image thumb to move and rename to this page's /site/assets/files/$page->id.
	 *
	 */
	private function actionProcessThumbnails(Page $page, $thumb) {

		if(!$page && !$page instanceof Page) return false;

		// if media is of type image, create its lister thumb. When output formatting is OFF, image fields always behave as arrays, so we need first()
		// if files were uploaded using jfu, we already have thumbs; use them instead (after renaming)
		if($thumb) {
			// @note: rename file as bicycle.0x260.ext
			$destinationPath = $page->filesManager()->path();
			$file = new \splFileInfo($thumb);
			if(!$file->isFile()) return false;// if we didn't get that thumb, return
			$newThumbName = $file->getBasename('.' . $file->getExtension()) . '.0x260.' . $file->getExtension();// e.g. my_image.0x260.jpg
			if(is_file($destinationPath . $newThumbName)) return false;// if a thumbnail already exists, return
			// if good to go, copy the file over, renaming it, and delete original
			$this->wire('files')->copy($thumb, $destinationPath . $newThumbName);
			unlink($thumb);
		}
		// otherwise we create a thumb
		else $page->media_manager_image->first()->height(260);

	}

	/**
	 * Rename a given filename to be page-title-friendly.
	 *
	 *	@access private
	 *	@param string $name Name of file to generate media title from.
	 *	@param integer $mask Renaming mask.
	 * 	@return string $title title-friendly string.
	 */
	private function actionRenamer($name, $mask) {

		/*
			@note: current masks
			1. first letter of each word uppercase, no hyphens or underscores
			2. first letter of first word uppercase, no hyphens or underscores
			3. exact as validated filename, first letter of each word uppercase {validated via $sanitizer->filename}
			4. exact as validated filename, first letter of first word uppercase
		*/



		$title = '';
		/*
		@todo
		#$name = $this->wire('sanitizer')->filename($name); there are differences in filenaming RE those uploaded versus those scanned
		(i)For scanned ones, $name has to be 'sanitizer-filenamed' here to be consistent with those uploaded;
		(ii) Here and in JqueryFileUpload: will need to add option for checking if Lowercase setting as used in WireUpload is enforced. Currently, we do not use the setting
		*/

		if($mask == 1) $title = ucwords(str_replace(array('_', '-'), ' ', $name));
		elseif($mask == 2) $title = ucfirst(str_replace(array('_', '-'), ' ', $name));
		elseif($mask == 3) {
    		// ucwords with delimiters only works from php 5.4.34
    		// hence, we use a custom replacement strategy so that ucwords can be applied successfully
    		// temporarily replace '-' and '_' with ' *_* ' and ' *-* ' @note: the spaces before and after respective *
    		// that will allow separation of words
    		$name = str_replace(array('-', '_'), array(' *-* ', ' *_* '), $name);
    		// convert each first letter of each word to uppercase then replace temporary ** masks above
    		$name = str_replace(array(' *-* ', ' *_* '), array('-', '_'), ucwords($name));
			$title = $name;
		}
		elseif($mask == 4) $title = ucfirst($name);

		return $title;


	}


}