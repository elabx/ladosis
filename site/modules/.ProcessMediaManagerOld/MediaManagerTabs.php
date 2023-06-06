<?php namespace ProcessWire;

/**
* Media Manager: Tabs
*
* This file forms part of the Media Manager Suite.
* Builds tabs used in upload settings.
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

class MediaManagerTabs extends ProcessMediaManager {

	/**
	 * Set some key properties for use throughout the class.
	 *
	 * @access public
	 *
	 */
	public function __construct() {
		parent::__construct();
	}


/* ######################### - TABS - ######################### */

	/**
	 * First tab contents for executeUpload().
	 *
	 * We use this to directly upload files via add files or drag and drop.
	 *
	 * @access public
	 * @return mixed $inputfield markup.
	 *
	 */
	public function uploadAddFilesTab() {

		$tab = new InputfieldWrapper();
		$tab->attr('title', $this->_('Add'));
		$id = $this->className() . 'Add';
		$tab->attr('id', $id);
		$tab->class .= ' WireTab';

		// space separated string of all our medias's valid extensions (as saved in their respective field settings)
		$this->mmUtilities = new MediaManagerUtilities();
		// pipe separated for jfu
		$validExts = implode('|', $this->mmUtilities->validExtensions());

		$modules = $this->wire('modules');

		$jfu = $modules->get('JqueryFileUpload');

		$m = $modules->get('InputfieldMarkup');
		$m->label = $this->_('Upload Files');

		$savedSettings = $this->savedSettings;

		// show a publish button if 'after uploading' setting is: 'do not add uploads to ML; keep them for review' (option 3)
		$addToLibraryButton = '';
		$addToLibraryCheckbox = '';
		if(isset($this->savedSettings['after'][0]) && $this->savedSettings['after'][0] == 3) {
			$b = $modules->get('InputfieldButton');
			$b->attr('id+name', 'mm_add_to_media_library_btn');
			$b->value = $this->_('Add to Media Library');
			$b->attr('type', 'submit');

			$c = $modules->get('InputfieldCheckbox');
			$c->attr('id+name', 'mm_add_to_media_library_publish');
			$c->attr('value', 1);
			$c->label = $this->_('Publish');

			$addToLibraryButton = $b->render();
			$addToLibraryCheckbox = $c->render();
		}

		// for jfu render()
		$afterUploadSetting = isset($savedSettings['after'][0]) ? $savedSettings['after'][0] : 2;
		// we can only delete  if not immediately adding to Media Library or if noDelete is not in effect (@note: the && here is actually OR)
		$uploadsDeletable = $afterUploadSetting == 3 && !$this->noDelete ? 1 : 0;

		$renderJFUOptions = array('uploadsDeletable' => $uploadsDeletable);
		$m->attr('value', $addToLibraryCheckbox . $addToLibraryButton . $jfu->render($renderJFUOptions));

		$tab->add($m);

		return $tab;

	}

	/**
	 * Second tab contents for executeUpload().
	 *
	 * We use this to execute a scan of already uploaded media.
	 *
	 * @access public
	 * @return mixed $inputfield markup.
	 *
	 */
	public function uploadScanTab() {

		$tab = new InputfieldWrapper();
		$tab->attr('title', $this->_('Scan'));
		$id = $this->className() . 'Scan';
		$tab->attr('id', $id);
		$tab->class .= ' WireTab';

		$f = $this->wire('modules')->get('InputfieldButton');
		$f->attr('id+name', 'mm_scan_btn');
		$f->value = $this->_('Scan');

		$scanPublishChkbx = "<label for'scan_publish' id='scan_publish_label'>" . $this->_('Publish') . "<input type='checkbox' id='scan_publish' name='scan_publish' value='1'></label>";

		$m = $this->wire('modules')->get('InputfieldMarkup');
		$m->label = $this->_('Scan Media');
		$m->description = $this->_("Click the 'Scan button' to process media you have already uploaded to the server");
		$m->attr('value', $f->render() . $scanPublishChkbx);

		$tab->add($m);

		return $tab;

	}

	/**
	 * Fifth tab contents for executeUpload().
	 *
	 * Display help information for uploading.
	 *
	 * @access public
	 * @return mixed $inputfield markup.
	 *
	 */
	public function uploadHelpInfoTab() {

		$tab = new InputfieldWrapper();
		$tab->attr('title', $this->_('Help'));
		$id = $this->className() . 'Help';
		$tab->attr('id', $id);
		$tab->class .= ' WireTab';

		$this->mmRender = new MediaManagerRender();

		$m = $this->wire('modules')->get('InputfieldMarkup');
		$m->label = $this->_('Help and Information');
		#$m->attr('value', $this->renderUploadInfo());
		$m->attr('value', $this->mmRender->renderUploadInfo());
		$tab->add($m);

		return $tab;

	}


}
