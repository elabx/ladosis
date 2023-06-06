<?php

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
* @version 0.0.9
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
	 * @access private
	 *
	 */
	private function createFields() {

		// 2. ###### We create the fields we will need to add to our templates ######

		/*
				Prepare the array (with properties) we will use to create fields.
				We will modify some properties later for different contexts (templates).

				Additional Settings
					 *	Some fields will need additional settings.
		 */

		//  allowed file extensions
		$audioExts = 'mp3 wav ogg m4a m4p flac wma';// needs string
		$documentExts = 'pdf doc docx xls xlsx';// needs string
		$imageExts = 'gif jpg jpeg png';// needs string
		$videoExts = 'mp4 avi mkv mpeg mov wmv m4v';// needs string

		$fields = array(

			'audio' => array('name'=>'media_manager_audio', 'type'=> 'FieldtypeFile',  'label'=>'Media Manager: Audio',  'entityencodedesc'=>1, 'extensions' => $audioExts),
			'document'  => array('name'=>'media_manager_document',  'type'=> 'FieldtypeFile',  'label'=>'Media Manager: Document',   'entityencodedesc'=>1, 'extensions' => $documentExts),
			'image' => array('name'=>'media_manager_image', 'type'=> 'FieldtypeImage', 'label'=>'Media Manager: Image',  'entityencodedesc'=>1, 'extensions' => $imageExts),
			'video' => array('name'=>'media_manager_video', 'type'=> 'FieldtypeFile',  'label'=>'Media Manager: Video',  'entityencodedesc'=>1, 'extensions' => $videoExts),
			'settings' => array('name'=>'media_manager_settings', 'type'=> 'FieldtypeTextarea', 'label'=>'Media Manager: Settings'),
		);

		foreach ($fields as $field) {

			$f = new Field(); //  create new field object
			$f->type = $this->wire('modules')->get($field['type']); //  get the field type
			$f->name = $field['name'];
			$f->label = $field['label'];

			if($f->name =='media_manager_settings') {
				$f->collapsed = 5;
				$f->rows = 10;
				$f->contentType = 0;
			}

			if($f->name !=='media_manager_settings') {
				$f->maxFiles = $field['name'] == 'media_manager_image' ? 0 : 1;// we only allow 1 media per page for non-image types
				$f->extensions = $field['extensions'];
				$f->outputFormat = $field['name'] == 'media_manager_image' ? 0 : 2;// output as a single item, else null for non-image types. For images, automatic
				$f->entityEncode = $field['entityencodedesc'];
				//$f->useTags = 1;// @todo: currently leads to errors if one access Media Manager without first visiting a media manager field (i.e. media_manager_audio, etc). Something to do with altering DB schema (@see FieldtypeFile.module line #431-439)
			}

			if($f->name == 'media_manager_image') $f->description = 'The original image should remain in the top-most position and should not be sorted.';

			$f->tags = '-mediamanager';
			$f->save(); //

		}// end foreach fields

		// grab our newly created fields, assigning them to variables. We'll later add the fields to our templates
		$f = wire('fields');

		// set some Class properties on the fly. We will use this in createTemplates()
		$this->title = $f->get('title');

		$this->audio = $f->get('media_manager_audio');
		$this->document  = $f->get('media_manager_document');
		$this->image = $f->get('media_manager_image');
		$this->video = $f->get('media_manager_video');
		$this->settings = $f->get('media_manager_settings');

		// lets create some templates and add our fields to them
		return $this->createTemplates();

	}

	/**
	 * Create several Media Manager templates.
	 *
	 * Create templates for each media parent, media type, media settings and a blank template for media template.
	 * @see https://processwire.com/talk/topic/12130-process-module-with-certain-permission-not-showing-up/?p=112674
	 * @access private
	 *
	 */
	private function createTemplates() {

		// 3. ###### We create the templates needed by Media Manager ######

		/*
			The template properties (indices) for the $templates array below
			Leave blank for defaults
				[0]	= label => string
				[1] = useRoles => boolean (0/1)
				[2] = noChildren
				[3] = noParents

			These three template properties are added later [out of preference, rather than creating too complex a $templates array]:
			childTemplates => array;
			parentTemplates => array;
			roles => array;
		 */

		// these are field objects we set earlier. We assign them to variables for simplicity
		$title = $this->title;
		$audio = $this->audio;
		$document  = $this->document;
		$image = $this->image;
		$video = $this->video;
		$settings = $this->settings;

		$pt = $this->wire('templates')->get('admin');
		#$t->parentTemplates = array($pt->id);// needs to be added as array of template IDs

		// array for creating new templates: $k=template name; $v=template properties + fields
		$templates = array(

				// template for media parents (4 parents, 1 for each media type)
				'media-manager' => array('Media Manager', 1, '', '', 'fields' => array($title)),

				// templates for child pages of each media type (audio, document, image, video)
				'media-manager-audio' => array('Media Manager: Audio', 1, 1, '', 'fields' => array($title, $audio)),
				'media-manager-document'  => array('Media Manager: Document', 1, 1, '', 'fields' => array($title, $document)),
				'media-manager-image' => array('Media Manager: Image', 1, 1, '', 'fields' => array($title, $image)),
				'media-manager-video' => array('Media Manager: Video', 1, 1, '', 'fields' => array($title, $video)),
				'media-manager-settings' => array('Media Manager: Settings', 1, 1, 1, 'fields' => array($title, $settings)),

		);

		//  create new fieldgroups and templates and add fields
		foreach ($templates as $k => $v) {

			// new fieldgroup
			$fg = new Fieldgroup();
			$fg->name = $k;

			// we loop through the fields array in each template array and add them to the fieldgroup
			foreach ($v['fields'] as $field) $fg->add($field);

			$fg->save();

			// create a new template to use with this fieldgroup
			$t = new Template();
			$t->name = $k;
			$t->fieldgroup = $fg; // add the fieldgroup

			// allowed parent templates
			 if($k == 'media-manager') $t->parentTemplates = array($pt->id);// needs to be added as array of template IDs
			 else {
			 	$pt = $this->wire('templates')->get('media-manager');
			 	$t->parentTemplates = array($pt->id);
			 }

			// add template settings we need
			$t->label = $v[0];
			$t->useRoles = $v[1];
			$t->noChildren = $v[2];
			$t->noParents = $v[3];
			$t->tags = '-mediamanager';// tag our templates for grouping in admin using the tag set by the user in final install

			// save new template with fields and settings now added
			$t->save();

		}// end templates foreach

		// need to create this blank template file 'media-manager.php' to enable MM to appear in the admin menu (view-access issues)
		$path = $this->wire('config')->paths->templates . 'media-manager.php';
		if(!is_file($path)) {
			$notice = "<?php\n\n#### - Intentionally left blank. Please do not delete this file - ###";
			$mediaTemplateFile = fopen($path, 'a');
			fwrite($mediaTemplateFile, $notice);
			fclose($mediaTemplateFile);
		}

		return $this->extraTemplateSettings();

	}

	/**
	 * Add extra settings for the 4 media specific templates.
	 *
	 * @access private
	 *
	 */
	private function extraTemplateSettings() {

		// 4. ###### post-creating our templates: additional settings for some templates ######
		// prepare arrays for some templates' childTemplates AND parentTemplates

		// *** set allowed child/parent templates as applicable ***

		// for the template 'media-manager' (for our 4 media parents + settings), we only allow 'admin' template for parents
		$pt = wire('templates')->get('media-manager');
		$pt->parentTemplates = array($this->wire('templates')->get('admin')->id);// needs to be added as array of template IDs
		$pt->save();// save the template

		$allowedChildTemplates = array(
								'audio' => 'media-manager-audio',
								'document'  => 'media-manager-document',
								'image' => 'media-manager-image',
								'video' => 'media-manager-video',
		);

		$childTemplateIDs = array();

		foreach ($allowedChildTemplates as $v) {

					$ct = $this->wire('templates')->get($v);

					// for setting child template IDs for 'media-manager' template
					$childTemplateIDs[] = $ct->id;

					// set allowed parent templates for all media ('media-manager', i.e. for the 4 media parents + settings template)
					$ct->parentTemplates = array($pt->id);
					$ct->save();// save the template
		}

		// set allowed child templates for the template 'media-manager' (for the 4 media parents)
		$pt->childTemplates = $childTemplateIDs;// needs to be added as array of template IDs
		$pt->save();

		return $this->createPages();

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