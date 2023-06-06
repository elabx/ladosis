<?php namespace ProcessWire;

/**
* Media Manager: Cleanup
*
* This file forms part of the Media Manager Suite.
* Utility to remove Media Manager components pre-module uninstall.
* It can only be run by supersuser in the Media Manager Process Module
* The utility will irreversibly delete the following Media Manager Components in case user wishes to afterward uninstall OR reinstall Media Manager:
* 	5 Fields
*	6 Templates
*	1 Template File
*	5 Pages
*
* @author Francis Otieno (Kongondo)
* @version 0.0.12
*
* http://mediamanager.kongondo.com
* Created March 2015
*
*/

class MediaManagerCleanup extends WireData {

	// whether to remove the one 'media-manager.php' template file
	private $removeTplFiles;//

	/**
	 * Prepare cleaning up.
	 *
	 * @access public
	 *
	 */
	public function cleanUp($form) {
		$input = $this->wire('input');
		$form->processInput($input->post);
		$cleanupBtn = $input->post->cleanup_btn;
		$this->removeTplFiles = (int) $input->post->remove_tpl_files;
		// was the right button pressed
		if($cleanupBtn) return $this->cleanUpPages();
	}

	/**
	 * Delete Media Manager pages.
	 *
	 * @access private
	 *
	 */
	private function cleanUpPages() {

		$a = wire('pages')->get($this->wire('config')->adminRootPageID);
		$mediaManagerParentPage = $a->child('name=media-manager');

		$children = $mediaManagerParentPage->children('include=hidden');

		// recursively delete the media manager pages - i.e., including their children
		if(count($children)) foreach ($children as $child) $this->wire('pages')->delete($child, true);

		// also delete any menu pages that may have been left in the trash
		foreach ($this->wire('pages')->find('template=media-manager-audio|media-manager-document|media-manager-image|media-manager-video, status>=' . Page::statusTrash) as $p) $p->delete();

		return $this->cleanUpTemplates();

	}

	/**
	 * Delete Media Manager templates.
	 *
	 * @access private
	 *
	 */
	private function cleanUpTemplates() {

		$templates = array('media-manager', 'media-manager-audio', 'media-manager-document', 'media-manager-image', 'media-manager-video','media-manager-settings',);

		// delete each found template one by one
		foreach ($templates as $template) {
			$t = $this->wire('templates')->get($template);
			if ($t->id) {
				$this->wire('templates')->delete($t);
				$this->wire('fieldgroups')->delete($t->fieldgroup);// delete the associated fieldgroups
			}

		}

		return $this->cleanUpFields();

	}

	/**
	 * Delete Media Manager fields.
	 *
	 * @access private
	 *
	 */
	private function cleanUpFields() {

		// array of Media Manager fields. We'll use this to delete each, one by one as applicable
		$fields = array('media_manager_audio', 'media_manager_document', 'media_manager_image', 'media_manager_video', 'media_manager_settings',);

		// delete each found field
		foreach ($fields as $field) {
			$f = $this->wire('fields')->get($field);
			if($f->id) $this->wire('fields')->delete($f);
		}

		return $this->cleanUpTemplateFiles();

	}

	/**
	 * Delete Media Manager blank template file.
	 *
	 * @access private
	 *
	 */
	private function cleanUpTemplateFiles() {

		$this->deleteTf = false;

		// if user has chosen to also delete template files AND these were installed (blank or demo)
		if ($this->removeTplFiles) {
			$this->deleteTf = true;
			$templateFile = 'media-manager.php';
			$sourcepath = $this->wire('config')->paths->templates;// source: '/site/templates/'
			if(is_file($sourcepath . $templateFile)) unlink($templateFile);// delete the template file
		}

		return $this->cleanupDone();

	}

	/**
	 * Cleanup done message.
	 *
	 * @access private
	 *
	 */
	private function cleanupDone() {

		// true if template files were deleted = only true if checkbox was selected
		$tf = $this->deleteTf == true ? ', Template File' : '';

		// if we made it here return success message!
		$this->message("Media Manager: Components successfully removed. Fields, Templates" .  $tf . " and Pages deleted. You can now uninstall the module.");
		// redirect to admin page
		$this->session->redirect($this->wire('config')->urls->admin);

	}


}