<?php namespace ProcessWire;

/**
* Media Manager: Installer
*
* This file forms part of the Media Manager Suite.
* It is an install wizard for Media Manager and is only run once when installing the module.
* It selectively installs 'fields', 'templates', a 'template file' and 'media manager pages'.
* If the above already exist (i.e., same names); this installer aborts wholesale.
* If installer proceeds and if user selected the option, the 'template-fils' is only created if they do not exist at destination, i.e. '/site/templates/'.
* We don't want to overwrite users files!
*
* @author Francis Otieno (Kongondo)
* @version 0.0.12
*
* http://mediamanager.kongondo.com
* Created January 2015
*
*/

class MediaManagerInstaller extends WireData {


	const PAGE_NAME = 'media-manager';// this process' name

	/**
	 * Check if similar fields, templates and media manager page exist before install.
	 *
	 * @access public
	 * @param null|integer $mode Whether to verify install possible (null) or commence install (1).
	 *
	 */
	public function verifyInstall($mode = null) {

		$pageCheck = '';

		// if we have already verified install, proceed directly to first step of installer
		if($mode == 1) return $this->createFields();

		// 1. ###### First we check if Media Manager Admin page, fields and templates already exist.
		// If yes to any of these, we abort installation and return error messages

		// check if media manager page already exists in Admin
		$parent = $this->wire('pages')->get($this->wire('config')->adminRootPageID);
		$page = $this->wire('pages')->get("parent=$parent, template=admin, include=all, name=".self::PAGE_NAME);
		if($page->id && $page->id > 0) $pageCheck = $page->title;

		$pageExist = $pageCheck ? true : false;// we'll use this later + $pageCheck to show error

		// check if fields 'media_manager_images', etc, already exist
		$fields  = array(
			'audio' => 'media_manager_audio',
			'document' 	=> 'media_manager_document',
			'image' => 'media_manager_image',
			'video' => 'media_manager_video',
			'settings' => 'media_manager_settings',

		);

		$fieldsCheck = array();
		foreach ($fields as $key => $value) {if($this->wire('fields')->get($value))	$fieldsCheck [] = $this->wire('fields')->get($value)->name;}
		$fieldsExist = count($fieldsCheck) ? true : false;

		$templates = array(
			'media' => 'media-manager',
			'audio' => 'media-manager-audio',
			'document'  => 'media-manager-document',
			'image' => 'media-manager-image',
			'video' => 'media-manager-video',
			'settings' => 'media-manager-settings',
		);

		$templatesCheck = array();
		foreach ($templates as $template) {if($this->wire('templates')->get($template)) $templatesCheck [] = $this->wire('templates')->get($template)->name;}

		$templatesExist = count($templatesCheck) ? true : false;

		if($pageExist == true){
			$failedPage = $pageCheck;
			$this->error($this->_("Cannot install Media Manager Admin page. A page named 'media-manager' is already in use under Admin. Its title is: {$failedPage}."));
		}

		if($fieldsExist == true){
			$failedFields = implode(', ', $fieldsCheck);
			$this->error($this->_("Cannot install Media Manager fields. Some field names already in use. These are: {$failedFields}."));
		}

		if($templatesExist == true){
			$failedTemplates = implode(', ', $templatesCheck);
			$this->error($this->_("Cannot install Media Manager templates. Some template names already in use. These are: {$failedTemplates}."));
		}

		//if any of our checks returned true, we abort early
		if($pageExist == true || $fieldsExist == true || $templatesExist == true) {
			throw new WireException($this->_('Due to the above errors, Media Manager did not install. Make necessary changes and try again.'));
			//due to above errors, we stop executing install of the following 'templates', 'fields' and 'pages'
		}

		// pass on to first step of install
		// return true to OK first step of install
		return true;

	}

	/**
	 * Create several Media Manager fields.
	 *
	 * @note: We create from JSON using Field::setImportData().
	 *
	 * @access private
	 * @return $this->createTemplates().
	 *
	 */
	private function createFields() {

		// 2. ###### We create the fields we will need to add to our templates ######

		$fields = $this->getFieldData();
		$fieldNames = '';

		foreach ($fields as $fieldName => $fieldData) {
			$f = new Field();
			$f->setImportData($fieldData);
			$f->save();
			$fieldNames .= $fieldName . " ";
		}

		$this->message("Created fields $fieldNames");

		// lets create some templates and add our fields to them
		return $this->createTemplates();

	}

	/**
	 * Create several Media Manager templates.
	 *
	 * Create templates for each media parent, media type, media settings and a blank template for media template.
	 * @note: We create from JSON using Template::setImportData().
	 * @see https://processwire.com/talk/topic/12130-process-module-with-certain-permission-not-showing-up/?p=112674
	 *
	 * @access private
	 * @return $this->extraTemplateSettings().
	 *
	 */
	private function createTemplates() {

		// 3. ###### We create the templates needed by Media Manager ######

		$templates = $this->getTemplateData();
		$templateNames = '';

		foreach ($templates as $templateName => $templateData) {
			$fg = new Fieldgroup();
			$fg->name = $templateName;
			$templateNames .= $templateName . " ";
			foreach ($templateData['fieldgroupFields'] as $fieldname) $fg->add($fieldname);
			$fg->save();
			$t = new Template();
			$t->setImportData($templateData) ;
			$t->save();
		}

		$this->message("Created templates $templateNames");

		// need to create this blank template file 'media-manager.php' to enable MM to appear in the admin menu (view-access issues)
		$path = $this->wire('config')->paths->templates . 'media-manager.php';
		if(!is_file($path)) {
			$notice = "<?php namespace ProcessWire;\n\n#### - Intentionally left blank. Please do not delete this file - ###";
			$mediaTemplateFile = fopen($path, 'a');
			fwrite($mediaTemplateFile, $notice);
			fclose($mediaTemplateFile);
		}

		// add some extra settings ('allowed templates for children' doesn't seem to work with setImportData()? so we do it ourselves)
		return $this->extraTemplateSettings();

	}

	/**
	 * Add extra settings for the 4 media-specific templates.
	 *
	 * @access private
	 * @return $this->createPages().
	 *
	 */
	private function extraTemplateSettings() {
		// 4. ###### post-creating our templates: additional settings for some templates ######
		// prepare arrays for some child templates for template 'media-manager'
		$prefix = 'media-manager-';
		$allowedChildTemplates = $this->wire('templates')->find("name={$prefix}audio|{$prefix}document|{$prefix}image|{$prefix}video");
		$childTemplateIDs = $allowedChildTemplates->explode('id');
		// set allowed child templates for the template 'media-manager' (for the 4 media parents [audio,document,image,video])
		$pt = wire('templates')->get('media-manager');
		$pt->childTemplates = $childTemplateIDs;// needs to be added as array of template IDs
		$pt->save();
		return $this->createPages();
	}

	/**
	 * Return JSON data for installing fields for the module.
	 *
	 * @access private
	 * @return string $fieldsJSON JSON string containing fields data for use with Field::setImportData().
	 *
	 */
	private function getFieldData() {
		$fieldsJSON = file_get_contents(__DIR__ . "/configs/fields.json");
		return json_decode($fieldsJSON, true);
	}

	/**
	 * Return JSON data for installing templates for the module.
	 *
	 * @access private
	 * @return string $templatesJSON JSON string containing templates data for use with Template::setImportData().
	 *
	 */
	private function getTemplateData() {
		$templatesJSON = file_get_contents(__DIR__ . "/configs/templates.json");
		return json_decode($templatesJSON, true);
	}

	/**
	 * Create media parent pages and a settings page.
	 *
	 * Four media parent pages: audio, document, image, video.
	 * One media setings page: settings.
	 *
	 * @access private
	 *
	 */
	private function createPages() {

		// 5. ###### Create the 4 parent media pages + 1 settings page

		$a = $this->wire('pages')->get($this->wire('config')->adminRootPageID);
		$parent = $a->child('name=media-manager');
		$template = $this->wire('templates')->get('media-manager');
		$template2 = $this->wire('templates')->get('media-manager-settings');

		// $v[0]=title;
		$mediaPages = array(
			'Audio' =>  array('Media Manager: Audio'),
			'Document' =>  array('Media Manager: Document'),
			'Image' =>  array('Media Manager: Image'),
			'Video' =>  array('Media Manager: Video'),
			'Settings' =>  array('Media Manager: Settings'),
		);

		// create the child pages of 'Media Manager': These will be the parent pages of all 4 media types uploaded + a settings page
		foreach ($mediaPages as $k => $v) {
			$p = new Page();
			$p->template = $k == 'Settings' ? $template2 : $template;
			$p->parent = $parent;
			$p->title = $v[0];
			$p->addStatus(Page::statusHidden);// @note: saving as hidden; we don't want to show in AdminThemeReno side menu
			$p->save();
		}

	}

}