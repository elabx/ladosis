<?php namespace ProcessWire;

/**
* Media Manager: Actions
*
* This file forms part of the Media Manager Suite.
* Executes various runtime CRUD tasks for the module.
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

class MediaManagerActions extends ProcessMediaManager {

	public function __construct() {
		parent::__construct();
		$this->mmUtilities = new MediaManagerUtilities();
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

		## - ajax requests - ##

		// in ProcessMediaManager context
		if($actionType == 'publish')		$data = $this->actionPublish($media, 1);// single or bulk media processing
		elseif($actionType == 'unpublish')	$data = $this->actionPublish($media, 0);// -ditto-
		elseif($actionType == 'lock')		$data = $this->actionLock($media, 1);// -ditto-
		elseif($actionType == 'unlock') 	$data = $this->actionLock($media, 0);// -ditto-

		elseif($actionType == 'tag') 		$data = $this->actionTag($media, 1, $options);// -ditto-
		elseif($actionType == 'untag') 		$data = $this->actionTag($media, 0);// -ditto-
		elseif($actionType == 'trash')		$data = $this->actionDelete($media, 1);// -ditto-
		elseif($actionType == 'delete')		$data = $this->actionDelete($media, 0);// -ditto-
		elseif($actionType == 'edit')		$data = $this->actionEdit($media, $options);// single media edit
		elseif($actionType == 'delete-variation') $data = $this->actionDeleteVariation($media, $options);// single image media variations
		elseif($actionType == 'scan') $data = $this->actionScanMedia($options);

		// in page-edit (inputfield) context)
		elseif($actionType == 'insert')		$data = $this->actionInsert($media, $options);


		## - non-ajax requests - ##
		// in ProcessMediaManager context
		elseif($actionType == 'upload')		$data = $this->actionUploadToMediaLibrary($options);
		elseif($actionType == 'settings')	$data = $this->actionSaveSettings($media);// saving media_manager_settings. Here, $media is $form
		elseif($actionType == 'edit-filter')$data = $this->actionEditFilter($media);// saving single filter profile edit. Here, $media is $form
		elseif($actionType == 'edit-filters')$data = $this->actionEditFilterProfiles($media);// saving multiple filter profiles, creating new, setting active. Here, $media is $form

		// non-ajax request responses: use PW message/error
		if(in_array($actionType, array('upload', 'settings'))) {
			if($data['message'] == 'error') return $this->error($data['error']);
			elseif($data['message'] == 'success') {
					$this->message($data['success']);
					// give feedback to uploader
					if(isset($data['audio'])) $this->message($data['audio']);
					if(isset($data['document'])) $this->message($data['document']);
					if(isset($data['image']))  $this->message($data['image']);
					if(isset($data['video'])) $this->message($data['video']);
					// failed due to naming
					if(isset($data['note'])) $this->error($data['note']);

					return;
			}
			else return $this->error($this->_('There was an error. We could not process your request.'));// catering for the unknowns
		}

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
	 * @param array $media Selected media to publish/unpublish (i.e. the pages themselves).
	 * @param int $action Whether to publish or unpublish. 0=unpublish; 1=publish.
	 * @return array $data To JSON-Encode as a response to the ajax call.
	 *
	 */
	private function actionPublish($media = array(), $action) {

		$data = array();

		// @access-control: media-manager-publish
		if($this->noPublish) {
			$data['message'] = 'error';
			$data['error'] = $this->_('Media Manager: You have no permission to (un)publish media.');
			return $data;
		}

		$pages = $this->wire('pages');
		$actionTaken = '';
		$actionStr = $action ? $this->_('published') : $this->_('unpublished');

		if(count($media)) {

			$i = 0;// count for success actions
			$j = 0;// count for failed actions
			$k = 0;// count for mis-actioned image media variations (i.e. an attempt to (un)publish them)

				foreach ($media as $m) {
					$idType = explode('_', $m);

					// reject attempt to (un)publish an image variation
					if(strlen((int) $idType[1]) > 1) {
						$k++;
						continue;
					}

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
					if($j) $actionTaken = $actionTaken . sprintf(__(' <span>(%1$d media are locked for edits and could not be %2$s).</span>'), $j, $actionStr);
					if($k) $actionTaken = $actionTaken . sprintf(__(' <span>(%1$d media are image variations and need not be %2$s).</span>'), $k, $actionStr);

					$data['message'] = 'success';
					$data['success'] = $actionTaken;

			}

			// if we could not (un)publish any media
			else {

					$lockedEditsStr = $this->_(' Media locked for edits or are image variations.');
					$error = $this->_('Media Manager: Selected media could not be ') . $actionStr . '. ' . $lockedEditsStr;

					$data['message'] = 'error';
					$data['error'] = $error;
			}

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
	 * @param array $media Selected media to unlock/lock (i.e. the pages themselves).
	 * @param int $action Whether to lock or unlock. 0=unlock; 1=lock.
	 * @return array $data To JSON-Encode as a response to the ajax call.
	 *
	 */
	private function actionLock($media = array(), $action) {

		$data = array();

		// @access-control: media-manager-lock
		if($this->noLock) {
			$data['message'] = 'error';
			$data['error'] = $this->_('Media Manager: You have no permission to (un)lock media.');
			return $data;
		}

		$pages = $this->wire('pages');
		$actionTaken = '';
		$actionStr = $action ? $this->_('locked') : $this->_('unlocked');

		if(count($media)) {

			$i = 0;// count for success actions
			$j = 0;// count for failed actions
			$k = 0;// count for mis-actioned image media variations (i.e. an attempt to (un)lock them)

			foreach ($media as $m) {
				$idType = explode('_', $m);

				// reject attempt to (un)lock an image variation
				if(strlen((int) $idType[1]) > 1) {
					$k++;
					continue;
				}

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
					if($j) $actionTaken = $actionTaken . sprintf(__(' <span>(%1$d media could not be %2$s).</span>'), $j, $actionStr);
					if($k) $actionTaken = $actionTaken . sprintf(__(' <span>(%1$d media are image variations and need not be %2$s).</span>'), $k, $actionStr);

					$data['message'] = 'success';
					$data['success'] = $actionTaken;

			}

			// if we could not (un)lock any media
			else {

					$lockedEditsStr = $this->_(' Media locked for edits or are image variations.');
					$error = $this->_('Media Manager: Selected media could not be ') . $actionStr . '. ' . $lockedEditsStr;

					$data['message'] = 'error';
					$data['error'] = $error;
			}

		}// end if count($media)

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
	 * @param array $media Selected media to publish/unpublish (i.e. the pages themselves).
	 * @param int $action Whether to tag or untag. 0=untag; 1=tag.
	 * @return array $data To JSON-Encode as a response to the ajax call.
	 *
	 */
	private function actionTag($media = array(), $action, $options = array()) {

		$data = array();

		// @access-control: media-manager-edit
		if($this->noEdit) {
			$data['message'] = 'error';
			$data['error'] = $this->_('Media Manager: You have no permission to edit.');
			return $data;
		}

		$pages = $this->wire('pages');
		$actionTaken = '';
		$actionStr = $action ? $this->_('tagged') : $this->_('untagged');
		$mediaFields = array(1=>'audio', 2=>'document', 3=>'image', 4=>'video');
		$versionType = '';
		$tags = '';
		$tagMode = '';

		if(count($media)) {

			$i = 0;// count for success actions
			$j = 0;// count for failed actions
			if($action) {
				$tags = $this->wire('sanitizer')->text($options['tags']);
				$tagMode = $options['tag_mode'];
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

				if(strlen($idType[1]) > 1) $versionType = (int) substr($idType[1], 1);

				// if media page locked for edits
				if($p->is(Page::statusLocked)) {
					$j++;
					continue;
				}

				$t = '';

				// dealing with image variations
				if($versionType) {
					$mm = new MediaManager();
					$imgV = $mm->getImageVersion($p, $versionType);
					if(!$imgV) continue;
					$mf = $imgV;
					//$p->$mediaField->$imgV->tags
				}
				// dealing with normal media
				else $mf = $p->$mediaField->first();

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
					if($tagMode == 1) $mf->tags = $t = $tags;
					// appending to existing tags
					else {
						$existingTags = $mf->tags;
						$t =  trim($existingTags . ' ' . $tags);
						$mf->tags = $t;
					}

					$p->save($mediaField);

					// confirm successfully tagged
					if($p->$mediaField->$mf->tags == $t) $i++;
					else $j++;
				}

				else $j++;

			}// end foreach

			/* prepare responses */

			if($i > 0) {

					$actionTaken = sprintf(__('Media Manager: %1$d media %2$s.'), $i, $actionStr);
					if($j) $actionTaken = $actionTaken . sprintf(__(' <span>(%1$d media are locked for edits and could not be %2$s).</span>'), $j, $actionStr);

					$data['message'] = 'success';
					$data['success'] = $actionTaken;

			}

			// if we could not (un)publish any media
			else {

					$lockedEditsStr = $this->_(' Media locked for edits.');
					$error = $this->_('Media Manager: Selected media could not be ') . $actionStr . '. ' . $lockedEditsStr;

					$data['message'] = 'error';
					$data['error'] = $error;
			}

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
	 * @param array $media Selected media to trash/delete (i.e. the pages themselves).
	 * @param int $action Whether to trash or delete. 0=delete; 1=trash.
	 * @return array $data To JSON-Encode as a response to the ajax call.
	 *
	 */
	private function actionDelete($media = array(), $action) {

		$data = array();

		// @access-control: media-manager-delete
		if($this->noDelete) {
			$data['message'] = 'error';
			$data['error'] = $this->_('Media Manager: You have no permission to trash/delete media.');
			return $data;
		}

		$pages = $this->wire('pages');
		$actionTaken = '';
		$actionStr = $action ? $this->_('trashed') : $this->_('deleted');

		if(count($media)) {

			$i = 0;// count for success actions
			$j = 0;// count for failed actions
			$k = 0;// count for mis-actioned image media variations (i.e. an attempt to trash/delete them)

			foreach ($media as $m) {
				$idType = explode('_', $m);

				// reject attempt to trash/delete an image variation
				if(strlen((int) $idType[1]) > 1) {
					$k++;
					continue;
				}

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
					// @todo...acting strangely in PW 3! Although page deleted, it still shows up until reload!
					if($pages->get((int) $idType[0])->id == 0) $i++;// confirm deleted
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

					$deleteVariationsStr = $this->_('delete variations selection');
					$actionTaken = sprintf(__('Media Manager: %1$d media %2$s.'), $i, $actionStr);
					if($j) $actionTaken = $actionTaken . sprintf(__(' <span>(%1$d media are locked for edits and could not be %2$s).</span>'), $j, $actionStr);
					if($k) $actionTaken = $actionTaken . sprintf(__(' <span>(%1$d media are image variations and can only be %2$s using the %3$s).</span>'), $k, $actionStr, $deleteVariationsStr);

					$data['message'] = 'success';
					$data['success'] = $actionTaken;
			}

			// if we could not (un)publish any media
			else {

					$lockedEditsStr = $this->_(' Media locked for edits or are image variations.');
					$error = $this->_('Media Manager: Selected media could not be ') . $actionStr . '. ' . $lockedEditsStr;

					$data['message'] = 'error';
					$data['error'] = $error;
			}

		}// end if count($media)

		return $data;

	}

	/**
	 * Delete image media variations.
	 *
	 * Called by actionMedia on behalf of executeAjax() to trash/delete media.
	 * Only used in ProcessMediaManager context (i.e. in process rather than page-edit (inputfield) context).
	 * Only those with 'media-manager-delete' permission (if present) will be able to select the action.
	 *
	 * @access private
	 * @param array $media Selected media to trash/delete (i.e. the pages themselves).
	 * @param array $options Contains image media variations parent ID.
	 * @return array $data To JSON-Encode as a response to the ajax call.
	 *
	 */
	private function actionDeleteVariation($media = array(), Array $options) {

		$data = array();

		// @access-control: media-manager-delete
		if($this->noDelete) {
			$data['message'] = 'error';
			$data['error'] = $this->_('Media Manager: You have no permission to delete image media variations.');
			return $data;
		}

		$pages = $this->wire('pages');
		$actionTaken = '';

		if(count($media)) {

			$id = (int) $options['variations_parent_id'];
			$p = $pages->get($id);

			if(!$p->id) {
				$data['message'] = 'error';
				$data['error'] = $this->_('Media Manager: We did not find that media item!');
				return $data;
			}

			// @access-control: media locked + media-manager-lock
			// if media is locked but user has some editing rights
			if($p->is(Page::statusLocked)) {

				// media locked BUT user has no permission to (un)lock
				if($this->noLock) {
					$data['message'] = 'error';
					$data['error'] = $this->_('Media Manager: This media is locked for edits.');
				}

				// media locked, user has permission to unlock BUT they attempted to delete variations without first unlocking media
				else {
					$data['message'] = 'error';
					$data['error'] = $this->_('Media Manager: This media is locked for edits. Unlock it first to enable editing.');
				}

				return $data;

			}// end if media locked


			$i = 0;// count for success actions
			$j = 0;// count for failed actions

			foreach ($media as $m) {
				$idType = explode('_', $m);

				// media mis-match
				if($p->id !== (int) $idType[0]) {
					$data['message'] = 'error';
					$data['error'] = $this->_('Media Manager: We could not match those variations to the current media.');
					return $data;
				}

				// reject attempt to apply 'delete variations' to an original media, i.e. non-variation media
				// theoretically should not be possible since prevented client side
				if(strlen((int) $idType[1]) == 1) {
					$j++;
					continue;
				}

				$versionType = (int) substr($idType[1], 1);
				$mm = new MediaManager();
				$imgV = $mm->getImageVersion($p, $versionType);
				if(!$imgV) continue;

				// remove the variation
				$p->media_manager_image->remove($imgV);// @note: browser cache may need clearing to show latest variation in case an older with same name previously existed
				$i++;

			}// end foreach media variation

			$p->save();

			/* prepare responses */

			if($i > 0) {

					$actionTaken = sprintf(_n("Media Manager: Deleted %d image variation.", "Media Manager: Deleted %d image variations.", $i), $i);

					if($j) $actionTaken = $actionTaken . sprintf(__(' <span>(%d media variations locked for edits or are original media and could not be deleted).</span>'), $j);

					$data['message'] = 'success';
					$data['success'] = $actionTaken;
			}

			// if we could not delete media variations
			else {

					$lockedEditsStr = $this->_(' Media locked for edits.');
					$error = $this->_('Media Manager: Selected media could not be ') . $actionStr . '. ' . $lockedEditsStr;

					$data['message'] = 'error';
					$data['error'] = $error;
			}

		}// end if count($media)

		return $data;

	}

	/**
	 * Save actions for a single media.
	 *
	 * Called by actionMedia on behalf of executeAjax() to apply several actions simultaneously to a single media.
	 * Only used in ProcessMediaManager context (i.e. in process rather than page-edit (inputfield) context).
	 * Only those with 'media-manager-edit' permission (if present) will be able to select the action.
	 *
	 * @access private
	 * @param Array $media Information/properties for a single media (title, description, tags, publish, lock).
	 * @param Array $options Options to guide media manipulation.
	 * @return Array $data To JSON-Encode as a response to the ajax call.
	 *
	 */
	private function actionEdit($media = array(), Array $options) {

		// @access-control: media-manager-edit
		// @note: just double checking here since users without media-manager-edit permission will not be able to access the 'edit media page'
		if($this->noEdit) {
			$data['message'] = 'error';
			$data['error'] = $this->_('Media Manager: You have no permission to edit.');
			return $data;
		}

		$data = array();
		$pages = $this->wire('pages');
		$actionTaken = '';
		$sanitizer = $this->wire('sanitizer');


		if(count($media)) {

			$id = (int) $options['pageid'];
			$p = $pages->get($id);

			if(!$p->id) {
				$data['message'] = 'error';
				$data['error'] = $this->_('Media Manager: We did not find that media item!');
				return $data;
			}

			// get media type
			$type = $sanitizer->pageName($options['type']);

			if(!$type) {
				$data['message'] = 'error';
				$data['error'] = $this->_('Media Manager: Media type mismatch.');
				return $data;
			}

			// @access-control: media locked + media-manager-lock
			// if media is locked but user has some editing rights
			if($p->is(Page::statusLocked)) {

				// media locked BUT user has no permission to (un)lock
				if($this->noLock) {
					$data['message'] = 'error';
					$data['error'] = $this->_('Media Manager: This media is locked for edits.');
				}

				// media locked BUT user has permission to unlock and they clicked the save button
				elseif(isset($media['lock']) && (int) $media['lock'] == 0) {
					$p->removeStatus(Page::statusLocked);
					$p->save();
					$data['message'] = 'success';
					$data['success'] = $this->_('Media Manager: Media has been unlocked for editing.');
				}
				// media locked, user has permission to unlock BUT they attempted to save without first unlocking media
				else {
					$data['message'] = 'error';
					$data['error'] = $this->_('Media Manager: This media is locked for edits. Unlock it first to enable editing.');
				}

				return $data;

			}// end if media locked

			/** media not locked so good to go with normal edits **/
			## execute actions ##

			// action: save
			if($options['subaction'] == $sanitizer->pageName('save')) {

				$p->title = $sanitizer->text($media['title']);

				// if no title provided, halt  return error message
				if(!$p->title) {
					$data['message'] = 'error';
					$data['error'] = $this->_('Media Manager: A title is required.');
					return $data;
				}

				// if a title was provided, we sanitize and convert it to a URL friendly page name
				if($p->title) $p->name = $sanitizer->pageName($p->title);
				//if name already exists [i.e. a child under this parent]; don't proceed
				if($p->parent->child("name={$p->name}, id!={$p->id}, include=all")->id) {
					//if name already in use, we tell the user in an error message and stop process
					$data['message'] = 'error';
					$data['error'] = $this->_('Media Manager: A media item with that title already exists. Amend the title and try again.');
					return $data;
				}

				// (un)publish media: media-manager-publish
				// @access-control
				if(!$this->noPublish) {
					if((int) $media['publish'] == 0) $p->addStatus(Page::statusUnpublished);
					elseif((int) $media['publish'] == 1) $p->removeStatus(Page::statusUnpublished);
				}

				// lock media @note: above already checked if media locked so if here, it means media is unlocked so CAN only be 'locking'
				// @access-control: media-manager-lock
				if(!$this->noLock && (int) $media['lock'] == 1) $p->addStatus(Page::statusLocked);

				$mediaField = 'media_manager_' . $type;

				// save media description
				// @note: used $sanitizer->text() in previous versions but that limits text to 255 characters
				$p->$mediaField->first()->description = $sanitizer->purify($media['description']);
				// save media tags
				$p->$mediaField->first()->tags = $sanitizer->text($media['tags']);

				// save image variations descriptions and tags
				if($type == 'image') {
					$variationsDesc = isset($options['variations_description']) ? $options['variations_description'] : '';
					$variationsTags = isset($options['variations_tags']) ? $options['variations_tags'] : '';

					if($variationsDesc && $variationsTags) {

						// we need to include the file just in case FieldtypeMediaManager is not being used/hasn't been installed yet
						$dir = dirname(__FILE__);
						require_once("$dir/MediaManager.php");
						foreach ($variationsDesc as $key => $value) {
							$versionType = (int) substr($key, 1);
							$mm = new MediaManager();
							$imgV = $mm->getImageVersion($p, $versionType);
							if(!$imgV) continue;
							// @note: used $sanitizer->text() in previous versions but that limits text to 255 characters
							$p->$mediaField->$imgV->description = $sanitizer->purify($value);
							$p->$mediaField->$imgV->tags = $sanitizer->text($variationsTags[$key]);
						}
					}// end if variations
				}// end if type == image

				$p->save();

				$actionTaken = $this->_('Media Manager: Saved Media.');

			}// end if subaction = save

			// prep json response
			$data['message'] = 'success';
			$data['success'] = $actionTaken;

		}

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
	 * @param array $media Selected media to trash/delete (i.e. the pages themselves).
	 * @param array $options Array containing current page and current field objects.
	 * @return array $data To JSON-Encode as a response to the ajax call.
	 *
	 */
	private function actionInsert($media = array(), Array $options) {

		$currentPage = $options['current_page'];// page object
		$currentField = $options['current_field'];// field object


		// for checking if the MediaManager field restricts allowed media types (e.g. could only be accepting image media)
		$allowedMedia = $currentField->allowedMedia;
		$allowedMediaTypes = array();
		$limitMediaTypes = false;

		if($allowedMedia != null && is_array($allowedMedia)) {
			$limitMediaTypes = true;
			$allowedMediaTypes = $allowedMedia;
		}

		// check if 'max media files' in place
		$limitMediaCnt = $currentField->maxFiles;
		$maxFiles = false;
		if($limitMediaCnt) {
			$maxFiles = true;
			$currentFieldCnt = $currentPage->$currentField->count();
			$allowable = $limitMediaCnt - $currentFieldCnt;
		}

		$data = array();
		$pages = $this->wire('pages');
		$actionTaken = '';

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
				// skip unpublished pages @todo: OR ONLY IN FRONTEND? MAYBE CONFIGURABLE?
				if($p->is(Page::statusUnpublished)) {
					$j++;
					continue;
				}

				// if same media already in field, skip it
				$type = (int) $idType[1];
				if($currentPage->$currentField->has("id=$p->id, type=$type")) {
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

				// insert media in the current page's inputfield (instance of FiedltypeMediaManager)
				#$currentPage->of(false);// @note: output formatting is always off in module context
				$m = new MediaManager();
				$m->id = $p->id;
				$m->type = $mediaTypeInt;

				$currentPage->$currentField->add($m);
				$currentPage->save($currentField);

				$i++;
				if($maxFiles) $allowable--;

			}// end foreach

			/* prepare responses */

			if($i > 0) {

					$actionTaken = sprintf(__('Media Manager: %d Media Added to the Page.'), $i);
					if($j) $actionTaken = $actionTaken . sprintf(__(' <span>(%d media could not be added to the page. This could be because they are already on the page or not published or maximum allowed media reached or the media type is not allowed for this field.).</span>'), $j);

					$data['message'] = 'success';
					$data['success'] = $actionTaken;

			}

			// if we could not insert any media
			else {

					$error = $this->_('Media Manager: No action taken. Selected media could not be added to the page. This could be because they are already on the page or not published or maximum allowed media reached or the media type is not allowed for this field.');

					$data['message'] = 'error';
					$data['error'] = $error;
			}

		}// end if count($media)

		return $data;

	}

	/**
	 * Upload media.
	 *
	 * Uploads media to a set directory for processing and saving.
	 * Only those with 'media-manager-upload' permission will be able to see the uploads widget.
	 *
	 * @access private
	 * @param array $options File upload to media library options.
	 * @return array $data to feedback via notices as a response to the post.
	 *
	 */
	private function actionUploadToMediaLibrary(Array $options) {

		/*
			 @note:
			 	- method very similar to actionScanMedia() except here we have no form or media to pass in as a parameter
				- we are dealing with previously uploaded, processed and validated data ready to be added to the Media Library
		 */

		// @access-control: media-manager-upload
		// @note: this is not really needed since if this permission is in effect, they will not see the uploads widget
		if($this->noUpload) {
			$data = array();
			$data['message'] = 'error';
			$data['error'] = $this->_('Media Manager: You have no permission to add media to the Media Library.');
			return $data;
		}

		if(!isset($options['dir'])) {
			$data['message'] = 'error';
			$data['error'] = $this->_('Media Manager: You need to specify a directory from which files will be added to the Media Library.');
			return $data;
		}

		// we are good to go
		$data = array();
		$dir = $options['dir'];

		$data = $this->actionUnzipFiles($dir);// unzip then create if no errors
		if($data['message'] == 'error') return $data;

		// need to tell actionCreateMedia() that files uploaded via jfu pass through sanitizer->filename() (WireUpload)
		// so they will need friendlier titles @note: since version 7 there is a setting for title renaming mask. We use that instead
		//$options['rename_title'] = true;
		// if we are good to go, let's create some media
		$data = $this->actionCreateMedia($options);

		return $data;

	}

	/**
	 * Create media (pages) from uploaded media.
	 *
	 * Media pages will be created and given parents and templates according to the four media types + formats specified in the respective fields.
	 * Accepted media extensions are specified in the four fields: 'media_manager_audio', 'media_manager_document', 'media_manager_image' and 'media_manager_video'.
	 *
	 *	@access public
	 *	@param array $options Options to guide manipulation of scanned data.
	 *  @return array $data to feedback as a response to the ajax call.
	 *
	 */
	public function actionScanMedia(Array $options) {

		// @access-control: media-manager-upload
		// @note: this is not really needed since if this permission is in effect, they will not see the uploads widget
		if($this->noUpload) {
			$data = array();
			$data['message'] = 'error';
			$data['error'] = $this->_('Media Manager: You have no permission to add media to the Media Library.');
			return $data;
		}

		$data = array();

		# start with some sanity checks (for internal use) #

		if(!isset($options['dir'])) {
			$data['message'] = 'error';
			$data['error'] = $this->_('Media Manager: You need to specify a directory to scan for media.');
			return $data;
		}

		$scanPublish = (int) $options['scan_publish'];

		// we are good to go
		$dir = $options['dir'];

		$data = $this->actionUnzipFiles($dir);// unzip then create if no errors
		if($data['message'] == 'error') return $data;
		// if we are good to go, let's create some media
		$options['publish'] = $scanPublish;
		$data = $this->actionCreateMedia($options);

		return $data;

	}

	/**
	 * Save media manager upload settings.
	 *
	 * Only those with 'media-manager-settings' permission (if present) will be able to edit and save settings.
	 *
	 *	@access public
	 *	@param Object $form Form with Media Manager settings to process and save.
	 *
	 */
	public function actionSaveSettings($form) {

		// @access-control: media-manager-settings
		if($this->noSettings) {
			$data = array();
			$data['message'] = 'error';
			$data['error'] = $this->_('Media Manager: You have no permission to edit these settings.');
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
		$intSanitize = array('minFileSize', 'setMaxFileSize', 'setMaxFiles', 'limitMultiFileUploads', 'limitMultiFileUploadSize', 'limitConcurrentUploads', 'loadImageMaxFileSize', 'imageMinWidth', 'imageMinHeight', 'previewMinWidth', 'previewMinHeight', 'previewMaxWidth', 'previewMaxHeight', 'loadAudioMaxFileSize', 'loadVideoMaxFileSize', 'mm_settings_after_upload'
		);

		$cdataSanitize = array('imageOrientation', 'imageForceResize', 'previewOrientation', 'char');

		// saved separately in the respective media manager media fields
		$mediaExtensions = array('image' => 'validExtensionsImage', 'audio' => 'validExtensionsAudio', 'video' => 'validExtensionsVideo', 'document' => 'validExtensionsDocument');
		$imageDimensions = array('maxWidth' => 'imageMaxWidth', 'maxHeight' => 'imageMaxHeight');

		// upload settings post
		$mmAfterUpload = $post->mm_settings_after_upload;
		$mmValidation = $post->mm_settings_validation;
		$mmMode = $post->mm_settings_mode;
		$mmImage = $post->mm_settings_image;
		$mmAudio = $post->mm_settings_audio;
		$mmVideo = $post->mm_settings_video;
		$mmDocument = $post->mm_settings_document;
		$mmMediaTitleFormat = $post->mm_settings_title_format;
		// @note: cannot work; @see notes in MediaManagerRender::uploadSettings
		#$mmMediaTitleFormatChar = $mmMediaTitleFormat == 4 ? $post->mm_settings_title_format_char : '';
		$mmDuplicateMedia = $post->mm_settings_duplicates;
		$mmDeleteVariations = $mmDuplicateMedia == 3 ? $post->mm_settings_replace_delete_variations : '';
		// other settings post
		$mmUserMedia = $post->mm_settings_user_media;// display only current user's media
		$mmSortBy = $post->mm_settings_sort;
		$mmSortOrder = $post->mm_settings_sort_order;
		$mmShowFilterProfiles = $post->mm_show_filter_profiles;

		$mmSettings = array();
		// upload settings
		$mmSettings['after'][] = $mmAfterUpload;
		$mmSettings['validation'] = $mmValidation;
		$mmSettings['mode'] = $mmMode;
		$mmSettings['image'] = $mmImage;
		$mmSettings['audio'] = $mmAudio;
		$mmSettings['video'] = $mmVideo;
		$mmSettings['document'] = $mmDocument;
		$mmSettings['title_format'][] = $mmMediaTitleFormat;
		#$mmSettings['title_format_char']['char'] = $mmMediaTitleFormatChar;
		$mmSettings['duplicates'][] = $mmDuplicateMedia;
		$mmSettings['delete_variations'][] = $mmDeleteVariations;
		// other settings post
		$mmSettings['user_media_only'][] = $mmUserMedia;
		$mmSettings['sort_media'][] = $mmSortBy;
		$mmSettings['sort_media_order'][] = $mmSortOrder;
		$mmSettings['show_filter_profiles'][] = $mmShowFilterProfiles;

		##########################

		$mmSettingsFinal = array();

		foreach ($mmSettings as $key => $value) {
			foreach ($value as $k => $v) {

				// first wed deal with values destined for media manager media fields (file extensions for each media type)
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
				// integer values saved to media_manager_settings
				elseif(in_array($k, $intSanitize)) $v = (int) $v;

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
	 * @access private
	 * @param Object $form Form with a single Media Manager filter profile settings to process and save.
	 *
	 */
	private function actionEditFilter($form) {

		$sanitizer = $this->wire('sanitizer');
		$page = $this->wire('page');

		// process form
		$post = $this->wire('input')->post;
		$form->processInput($post);
		$savedSettings = $this->savedSettings;
		$filterSettings = array();

		$oldFilterName =  $sanitizer->pageName($post->mm_edit_filter_name);
		$filterTitle =  $sanitizer->text($post->mm_edit_filter_title);
		$filterName = $sanitizer->pageName($filterTitle);
		$defaultSelector = $post->defaultSelector;// @TODO...SANITIZE?

		// @note: capturing success in JS using querystring 'success'
		if(!$filterTitle) $this->session->redirect($page->url . 'filter/' . $oldFilterName . '/?modal=1');		

		// if editing existing filters title
		if($oldFilterName !== $sanitizer->pageName($filterTitle)) {
			if(isset($savedSettings['filters'][$filterName])) {
				// @note: capturing success in JS using querystring 'success'
				$this->session->redirect($page->url . 'filter/' . $oldFilterName . '/?modal=1&success=0');
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
			// @note: capturing success in JS using querystring 'success'
			$this->session->redirect($page->url . 'filter/' . $filterName . '/?modal=1&success=1');
		}

	}

	/**
	 * Save actions for multiple filter profiles.
	 *
	 * Processes: active filter, new filter, filter profiles list.
	 *
	 * Used by MediaManagerRender::renderFilterConfigEdit().
	 *
	 * @access private
	 * @param Object $form Form with multiple Media Manager filter profiles settings to process and save.
	 *
	 */
	private function actionEditFilterProfiles($form) {

		$sanitizer = $this->wire('sanitizer');
		$page = $this->wire('page');
		$mmSettingsFinal = array();

		// process form
		$post = $this->wire('input')->post;
		$form->processInput($post);
		$savedSettings = $this->savedSettings;

		## 1. saving active filter if applicable ##
		$activeFilter = $sanitizer->pageName($post->mm_active_filter_select);

		// removing active filter or nothing sent
		if(!$activeFilter) {
			if(isset($savedSettings['active_filter'])) unset($savedSettings['active_filter']);
		}
		// setting an active filter
		else $savedSettings['active_filter'] = $activeFilter;

		## 2. create a new filter ##
		// @note: only title sent in this space
		$newFilterTitle = $sanitizer->text($post->mm_create_filter_title);
		if($newFilterTitle) {
			$newFilterName = $sanitizer->pageName($newFilterTitle);
			// check if we have an existing filter with that name
			if(!isset($savedSettings['filters'][$newFilterName])) {
				// @note: we don't return but save the other filter settings
				$savedSettings['filters'][$newFilterName]['title'] = $newFilterTitle;
			}			
		}

		## 3. bulk save or remove filter profiles (in filter profiles table) ##
		$confirmFilterProfilesDelete = $post->mm_delete_filter_profiles_confirm;// checkbox
		foreach ($post->mm_filter_title as $cnt => $filterTitle) {
			$filterName = $sanitizer->pageName($filterTitle);
			// before delete, check that confirmation was sent
			if($confirmFilterProfilesDelete) {
				// check if the item is being deleted
				if($post->mm_filter_del[$cnt]) {
					// if being deleted, we unset it
					unset($savedSettings['filters'][$filterName]);
					// if active filter deleted as well, remove it from filter settings
					if($activeFilter == $filterName) unset($savedSettings['active_filter']);
					continue;
				}
			}

		}// end foreach

		// if all filters deleted, remove parent array
		if(!count($savedSettings['filters'])) unset($savedSettings['filters']);

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
			// @note: capturing success in JS using querystring 'success'
			$this->session->redirect($page->url . 'filter/?modal=1&success=1');
		}

	}

	/**
	 *	Unzips uploaded compressed media.
	 *
	 * Will only unzip if zip files found.
	 *
	 *	@access private
	 *	@param $dir Directory with zip archives to uncompress.
	 *  @return array $data With success or error messages.
	 *
	 */
	private function actionUnzipFiles($dir) {

		$data = array();// we use an array because we need this for both ajax (to finally return json) and non-ajax requests

		/*
			@note:
			 - for files to scan, the $dir is : /site/assets/MediaManager/uploads/
			 - for files uploaded via jQueryFileUpload and awaiting review: /site/assets/MediaManager/jqfu/
		 */

		// if uploads directory not found, about with error
		if(!is_dir($dir)) {
			$data['message'] = 'error';
			$error = 'Media Manager: ' . $this->_('Media Manager: No folder/directory found at the path ') . $dir . '.';
			$data['error'] = $error;
			return $data;
		}

		// recursively iterate our path, skipping system folders
		$directory = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);

		// in this loop, we go deeper into other folders - recursively but start with the parent folder first, then its children, etc..
		foreach (new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::SELF_FIRST) as $path ) {
						set_time_limit(60);// try to avoid timing out
						if($path->isFile() && $path->getExtension() == 'zip'){
								$zipFile = $path->getPath() . '/' . $path->getFilename();
								wireUnzipFile($zipFile, $dir);// use in-built pw method
						}
		}//end recursive foreach

		// return success message to signal to proceed to next step
		$data['message'] = 'success';

		return $data;

	}

	/**
	 * Create media (pages) from uploaded media.
	 *
	 * Media pages will be created and given parents and templates according to the four media types + formats specified in the respective fields.
	 * Accepted media extensions are specified in the four fields: 'media_manager_audio', 'media_manager_document', 'media_manager_image' and 'media_manager_video'.
	 *
	 *	@access private
	 *	@param array $options Options to guide media creation.
	 *  @return array $data to feedback as a response the ajax call.
	 *
	 */
	private function actionCreateMedia(Array $options) {

		$dir = $options['dir'];
		$publish = (int) $options['publish'];
		$thumbsDir = isset($options['thumb']) ? $options['thumb'] : '';
		$pages = $this->wire('pages');

		$data = array();

		// if directory with files not found
		if(!is_dir($dir)) {
			$data['message'] = 'error';
			$data['error'] = $this->_('Media Manager: No media found.');
			return $data;
		}

		// prepare some variables we'll need later
		$a = 0;// for audio media pages count
		$b = 0;// for document media pages count
		$c = 0;// for image media pages count
		$d = 0;// for video media pages count

		$failed = array();
		$adminPage = $pages->get($this->wire('config')->adminRootPageID);
		$mediaParent = $pages->get("parent=$adminPage, template=admin, include=all, name=".self::PAGE_NAME);

		// for validation
		$validExts = $this->mmUtilities->validExtensions();
		$savedSettings = $this->savedSettings;

		$minFileSize = isset($savedSettings['validation']['minFileSize']) ? $savedSettings['validation']['minFileSize'] : '';
		$maxFileSize = isset($savedSettings['validation']['setMaxFileSize']) ? $savedSettings['validation']['setMaxFileSize'] : '';
		$imageMinWidth = isset($savedSettings['image']['imageMinWidth']) ? $savedSettings['image']['imageMinWidth'] : '';
		$imageMinHeight = isset($savedSettings['image']['imageMinHeight']) ? $savedSettings['image']['imageMinHeight'] : '';

		$f = $this->wire('fields')->get('media_manager_image');
		$imageMaxWidth = $f->maxWidth;
		$imageMaxHeight = $f->maxHeight;

		$options['commonImageExts'] = $this->mmUtilities->validExtensionsImage();
		$options['createThumb'] = false;
		$options['allowedImageMimeTypes'] = $this->mmUtilities->allowedImageMimeTypes();
		$options['allowedNonImageMimeTypes'] = $this->mmUtilities->allowedNonImageMimeTypes();
		$options['imageTypeConstants'] = array('gif' => IMAGETYPE_GIF, 'jpeg' => IMAGETYPE_JPEG, 'jpg' => IMAGETYPE_JPEG, 'png' => IMAGETYPE_PNG);

		// check for filenaming + duplicate media settings
		$titleFormat = isset($savedSettings['title_format'][0]) ? $savedSettings['title_format'][0] : 1;
		$duplicateMedia = isset($savedSettings['duplicates'][0]) ? $savedSettings['duplicates'][0] : 1;
		$deleteVariations = isset($savedSettings['delete_variations'][0]) ? $savedSettings['delete_variations'][0] : 0;

		// get jQueryFileUpload to help validate scanned uploads
		$jfu = new JqueryFileUpload();

		//recursively iterate our path, skipping  system folders
		$directory = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);

		//in this foreach, we go deeper into other folders - recursively and start with the parent item first, then its children, etc
		foreach (new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::SELF_FIRST) as $path ) {

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

			set_time_limit(60);// try to avoid timing out

			// skip folders (directories)
			if(!$path->isFile()) continue;

			// skip image files in 'thumbnails' folder (for jfu uploaded files)
			if(basename($path->getPath()) == basename($thumbsDir)) continue;

			/* #### validation #### */

			// validation: file extension
			if($path->isFile() && !in_array($path->getExtension(), $validExts)) {
				unlink($path);// delete invalid file
				continue;
			}

			// validation: file mime_type
			$valid = $jfu->validateFile($path->getPath() . '/' . $path->getFilename(), $options);

			if(!$valid['valid']) {
				unlink($path);
				continue;
			}

			// validation: file minimum-maximum size
			if(($minFileSize && $path->getSize() < $minFileSize) || ($maxFileSize && $path->getSize() > $maxFileSize)) {
				unlink($path);
				continue;
			}

			// validation: image file minimum-maximum-width-height
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

						// if media locked skip it
						if($child->is(Page::statusLocked)) continue;

						// @note: here we change $p!
						$p = $child;
						// get the main media
						$mediaFirst = $p->$mediaField->first();
						// get (to preserve) existing tags and description
						$existingTags = $mediaFirst->tags;
						$existingDesc = $mediaFirst->description;

						// if replacing media + deleting existing variations
						if($deleteVariations) $p->$mediaField->deleteAll();
						// delete only the main file
						else $p->$mediaField->delete($mediaFirst);
						$p->save();
						// add replacement media
						$p->$mediaField->add($path->getPath() . '/' . $path->getFilename());
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
			if(!$child->id || $duplicateMedia == 2) $p->$mediaField->add($path->getPath() . '/' . $path->getFilename());// @note: sanitize here? naah; processwire does it internally!
			$p->save();

			// if media is of type image, create or process (if already created by jfu) its lister thumb
			$thumb = $thumbsDir ? $thumbsDir . $path->getFilename() : '';
			if($type == 3) $this->actionProcessThumbnails($p, $thumb);

			// media counts @note: not in full use currently
			if($type == 1) 		$a++;// audio media count
			elseif($type == 2) 	$b++;// document media count
			elseif($type == 3) 	$c++;// image media count
			elseif($type == 4) 	$d++;// video media count

			// delete the temp media
			unlink($path);

			$pages->uncacheAll(); // free some memory


		}// end RecursiveIteratorIterator foreach


		//we recursively delete the directory /site/MediaManager/uploads/. We'll recreate it on demand in various upload methods as necessary
		wireRmdir($dir, $recursive = true);

		// recreate the directory @note: may not always work but fallback is in parent::construct()
		$dir = $options['dir'];
		if(!is_dir($dir)) wireMkdir($dir);

		// create a string of "failed" media titles to add to error message
		$failedTitles = implode(', ', $failed);

		// give feedback to uploader
		if($a > 0) $data['audio'] = sprintf(__('Media Manager Audio: Added %d to the Media Library.'), $a);
		if($b > 0) $data['document'] = sprintf(__('Media Manager Document: Added %d to the Media Library.'), $b);
		if($c > 0) $data['image'] = sprintf(__('Media Manager Image: Added %d to the Media Library.'), $c);
		if($d > 0) $data['video'] = sprintf(__('Media Manager Video: Added %d to the Media Library.'), $d);

		// failed due to naming
		#if($failedTitles) $this->error($this->_("Some media not added because names already in use. These are: {$failedTitles}."));
		if($failedTitles) $data['note'] = $this->_("Some media not added because names already in use. These are: {$failedTitles}.");

		// if adding media totally failed
		if ($a + $b + $c + $d == 0) {
			$data['message'] = 'error';
			$data['error'] = $this->_('Media Manager: No valid media found to add to Media Library. If replacing media, check that they are not locked.');
		}
		else {
			$data['message'] = 'success';
			$data['success'] = $this->_('Media Manager: Successfully added files to Media Library.');
		}

		$this->resetLister();

		return $data;

	}

	/**
	 * Create or process thumbnails for uploaded image media.
	 *
	 * We do this during upload rather than when displaying media for the first time.
	 *
	 *	@access private
	 *	@param $page Page to create/process a thumb for.
	 *	@param $thumb Path to image thumb to move and rename to this page's /site/assets/files/$page->id.
	 */
	private function actionProcessThumbnails(Page $page, $thumb) {

		if(!count($page) && !$page instanceof Page) return false;


		// if media is of type image, create its lister thumb. When output formatting is OFF, image fields always behave as arrays, so we need first()

		// if files were uploaded using jfu, we already have thumbs; use them instead (after renaming)
		if($thumb) {

			// @note: rename file as bicycle.100x75.ext
			$destinationPath = $page->filesManager()->path();

			$file = new \splFileInfo($thumb);
			if(!$file->isFile()) return false;// if we didn't get that thumb, return

			$newThumbName = $file->getBasename('.' . $file->getExtension()) . '.100x75.' . $file->getExtension();// e.g. my_image.100x75.jpg

			if(is_file($destinationPath . $newThumbName)) return false;// if a thumbnail already exists, return
			// if good to go, copy the file over, renaming it, and delete original
			copy($thumb, $destinationPath . $newThumbName);
			unlink($thumb);
		}
		// otherwise we create a thumb
		else $page->media_manager_image->first()->size(100,75);

	}

	/**
	 * Rename a given filename to be page-title-friendly.
	 *
	 *	@access private
	 *	@param String $name Name of file to generate media title from.
	 *	@param Integer $mask Renaming mask.
	 * 	@return String $title title-friendly string.
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
		@TODO
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
