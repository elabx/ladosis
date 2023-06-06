<?php

/**
 * ProcessWire Inputfield Form Processor
 *
 * Handles the rendering and processing of forms for Form Builder.
 *
 * Copyright (C) 2015 by Ryan Cramer Design, LLC
 *
 * PLEASE DO NOT DISTRIBUTE
 *
 * @todo: Add a unique "id" attribute to the <form> tag and make the "action" attribute reference it. 
 * <form action='./#my-form'>
 *
 * @property FormBuilder $forms FormBuilder API variable
 * @property int $id Form ID number
 * @property int $saveFlags Flags for saving form submission (default: self::saveFlagsDB)
 * @property bool $skipSessionKey Require a unique session key for each form submission (for security)
 * @property string $formName name of the form
 * @property string $submitText text that appears on the submit button
 * @property string $honeypot name of field that, when populated, invalidates the form submission
 * @property array $turingTest array containing list of field names and required answers
 * @property string $emailTo email address to send form submissions to, may also be multiple (1 per line) or conditional (1 condition per line)
 * @property string $emailFrom email address (or field name where it resides) to make email from
 * @property string $emailFrom2 alternate/backup email address if emailFrom is a field name and doesn't resolve
 * @property string $emailSubject subject of email that gets sent
 * @property string $responderTo field name (not email address) that WILL contain the submittor's email address (where the responder should be sent)
 * @property string $responderFrom Email address that the responder email should be from
 * @property string $responderSubject Subject line for the responder email
 * @property string $responderBody Body for the responder email
 * @property string $successUrl URL to redirect to on successful form submission
 * @property string $successMessage message to display on successful form submission, assuming no successUrl was set
 * @property string $errorMessage message to display when a form error occurred
 * @property string $action2 URL to send duplicate submission to
 * @property array $action2_add array of name=value to add to duplicate submission
 * @property array $action2_remove array of field names to remove from duplicate submission
 * @property array $action2_rename array of field names rename before duplicate submission
 * @property string $akismet CSV string containing field names of: "name,email,content" (in that order)
 * @property bool $allowPreset allow form field values to be pre-set from GET variables?
 *
 * Settings specific to saving pages from submitted forms:
 * @property string $savePageParent path to parent page
 * @property string $savePageTemplate name of template
 * @property array $savePageFields array of 'form_field_name' => 'processwire_field_id (or name)'
 * @property int $savePageStatus status of saved page (0 = don't save page now)
 * @property string $framework form framework, if in use
 * @property FormBuilderForm $fbForm
 * @property FormBuilderRender $fbRender
 * 
 * HOOKABLE METHODS
 * ================
 * @method $this populate(array $data, $entryID)
 * @method string render($id = 0)
 * @method string renderReady($form)
 * @method bool processInput($id = 0)
 * @method processInputReady(InputfieldForm $form)
 * @method processInputDone(InputfieldForm $form)
 * @method int|bool saveForm(InputfieldForm $form, $id = 0)
 * @method Page|null savePage(array $data, $status = null, $onlyFields = null)
 * @method bool savePageField(Page $page, $name, $value)
 * @method savePageReady(Page $page, array $data)
 * @method savePageDone(Page $page, array $data, $isNew, $onlyFields)
 * @method bool emailForm(InputfieldForm $form, array $data)
 * @method bool emailFormResponder(InputfieldForm $form, array $data)
 * @method postAction2(array $data)
 * @method formSubmitSuccess(InputfieldForm $form)
 * @method formSubmitError(InputfieldForm $form, array $errors)
 * @method string renderSuccess($message)
 * @method string renderErrors()
 *
 */

class FormBuilderProcessor extends WireData {

	/**
	 * These flags control what actions occur when a form is submitted. 
	 *
	 */
	const saveFlagDB = 1;		// save entry to database
	const saveFlagEmail = 2; 	// Send entries to email
	const saveFlagAction2 = 4; 	// Send entries to action2 (3rd party service)
	const saveFlagPage = 8; 	// Send entries to new pages
	const saveFlagExternal = 16; 	// Submit the form somewhere else (rendering all other options invalid)
	const saveFlagFilterSpam = 32; 	// Filter for spam
	const saveFlagResponder = 64; 	// Send an auto-responder email

	/**
	 * Instance of InputfieldForm created by this class
 	 *
	 */
	protected $form; 

	/**
	 * Form array that was passed to the constructor
	 *
	 */
	protected $formArray; 

	/**
	 * Keeps track of whether or not the form was successfully submitted (see isSubmitted method)
	 *
	 */
	protected $submitted = false; 

	/**
	 * Cache of our submitKey so we don't ever generate more than one per request
	 *
	 */
	protected $submitKey = '';

	/**
	 * Error messages generated from FormBuilderProcessor
	 *
	 */
	protected $errors = array();

	/**
	 * ID of inserted entry, if entry was saved to entries DB
	 *
	 */
	protected $entryID = 0;

	/**
	 * Construct the FormBuilderProcessor
	 *
	 * @param int $id
	 * @param array $formArray Array that defines the fields for this form, see examples.
	 *
	 */
	public function __construct($id, array $formArray) { 
		// form ID number
		$this->set('id', (int) $id);
		$this->formArray = $formArray; 
		$this->init();
		$this->form = $this->arrayToInputfields($formArray); 
	}

	/**
	 * Initialize the FormBilderProcessor's configuration variables
	 *
	 */
	protected function init() {

		// flags that indicate what actions should occur at form save time
		$this->set('saveFlags', self::saveFlagDB);

		// require a unique session key for each form submission (for security)
		$this->set('skipSessionKey', false); 

		// name of the form, used for auto generated email subject if needed
		$this->set('formName', ''); 

		// text that appears on the submit button
		$this->set('submitText', 'Submit');

		// name of field that, when populated, invalidates the form submission
		$this->set('honeypot', '');

		// array containing list of field names and required answers
		$this->set('turingTest', array()); 

		// email address to send form submissions to, may also be multiple (1 per line) or conditional (1 condition per line)
		$this->set('emailTo', ''); 		

		// email address (or field name where it resides) to use as the "reply-to" address
		$this->set('emailFrom', '');

		// The email "from" address
		$this->set('emailFrom2', ''); 

		// subject of email that gets sent
		$this->set('emailSubject', 'Form Submission'); 

		// field name (not email address) that WILL contain the submittor's email address (where the responder should be sent)
		$this->set('responderTo', '');

		// Email address that the responder email should be from
		$this->set('responderFrom', '');

		// Subject line for the responder email
		$this->set('responderSubject', '');

		// Body for the responder email
		$this->set('responderBody', '');

		// URL to redirect to on successful form submission
		$this->set('successUrl', ''); 

		// message to display on successful form submission, assuming no successUrl was set
		$this->set('successMessage', 'Thank you, your form has been submitted.'); 

		// message to display when a form error occurred
		$this->set('errorMessage', 'One or more errors prevented submission of the form. Please correct and try again.'); 

		// URL to send duplicate submission to
		$this->set('action2', '');

		// array of name=value to add to duplicate submission
		$this->set('action2_add', array()); 

		// array of field names to remove from duplicate submission
		$this->set('action2_remove', array()); 

		// array of field names rename before duplicate submission
		$this->set('action2_rename', array()); 

		// CSV string containing field names of: "name,email,content" (in that order)
		$this->set('akismet', '');

		// allow form field values to be pre-set from GET variables?
		$this->set('allowPreset', false); 

		// settings specific to saving pages from submitted forms
		$this->set('savePageParent', ''); 	// path to parent page
		$this->set('savePageTemplate', ''); 	// name of template
		$this->set('savePageFields', array()); 	// array of 'form_field_name' => 'processwire_field_id (or name)'
		$this->set('savePageStatus', 0); 	// status of saved page (0 = don't save page now)
	
		// form framework, if in use
		$this->set('framework', ''); 
	
		// FormBuilerForm object, if set
		$this->set('fbForm', null);
		
		// FormBuilderRender object, if set
		$this->set('fbRender', null);

	}
	
	public function setFbForm(FormBuilderForm $fbForm) {
		$this->set('fbForm', $fbForm);
	}
	
	public function setFbRender(FormBuilderRender $renderer) {
		$this->set('fbRender', $renderer);
	}

	/**
	 * Populate the form with the key=value data given in the array
	 * 
	 * @param array $data key=value associative array
	 * @param int $entryID
	 * @return $this
	 *
	 */
	public function ___populate(array $data, $entryID) {

		$entryID = (int) $entryID; 
		$this->wire('session')->set('FormBuilderEntryID', $entryID);

		foreach($data as $key => $value) {

			$field = $this->form->get($key);
			if(!$field || !$field instanceof Inputfield) continue; 
			$field->attr('value', $value);

			if($field instanceof InputfieldFormBuilderInterface) {
				// populate extra values for InputfieldFormBuilder derived Inputfields
				/** @var Inputfield $field */
				if($entryID) $field->set('entryID', $entryID);
				$field->set('formID', $this->id);
			}
		}	
	
		// ensure the _savePage value is retained, but not manipulatable	
		if(isset($data['_savePage'])) {
			$field = $this->wire('modules')->get('InputfieldHidden');
			$field->attr('name', '_savePage'); 
			$field->attr('value', (int) $data['_savePage']); 
			$field->collapsed = Inputfield::collapsedHidden; // makes it non-manipulatable
			$this->form->prepend($field);
		}

		return $this;
	}

	/**
	 * Return the rendered form output, whether an actual form or the success message after submitted.
	 *
	 * @param int $id Optional ID of entry, if it already exists
	 * @return string
	 *
	 */
	public function ___render($id = 0) {

		$input = $this->wire('input');
		$config = $this->wire('config');
		$form = $this->wire('forms')->get((int) $this->id);
		if(!$form->hasPermission('form-submit')) return $this->wrapOutput($this->_('This form is not available at your access level.'));
		
		$preview = ($input->get('preview') || $input->post('FormBuilderPreview')) && $this->wire('user')->hasPermission('form-builder');
		$admin = $this->wire('page')->template == 'admin';
		$formFile = $config->paths->templates . "FormBuilder/form-$this->formName.php";
		if($preview || $admin || !is_file($formFile)) $formFile = null;

		$this->errors = array(); // ensure errors are clear
		$this->wire('session')->set('FormBuilderFormID', $this->id);

		if($this->skipSessionKey) $this->form->protectCSRF = false;

		// copyright header precedes output
		$copyright = "\n<!-- " . FormBuilderMain::RCD . " -->\n"; 
		$out = $formFile ? '' : $copyright;

		// check for valid license key
		if(!$this->forms->isValidLicense()) {
			return $this->wrapOutput("<p>Product key not detected for " . htmlentities($config->httpHost) . "</p>");
		}
		
		// load the framework used for this form
		if($this->framework) $form->framework = $this->framework;
		$framework = $this->forms->getFramework($form);
		if($framework) {
			$framework->load();
			$this->form->addClass($framework->className());
		}

		// test if this is the form that was submitted	
		$submitKey = $this->input->post('_submitKey');
		$submitted = false;
		if($submitKey && strpos($submitKey, ":$this->formName:") !== false) {
			// JS looks for this landmark to know when to scroll the parent in an iframe to the form
			$out .= "<div id='FormBuilderSubmitted' data-name='$this->formName'></div>\n";
			// if submission was successful, return with success message
			if($this->processInput($id)) {
				$submitted = true; 
				if($formFile) {
					// control will be passed to the formFile
				} else {
					return $this->wrapOutput($out . $this->renderSuccess($this->successMessage));
				}
			}
		}

		// check if there were any errors produced by processInput or the form
		$errors = $this->getErrors();
		if(count($errors) && !$formFile) $out .= $this->renderErrors();

		// give the form a unique & predictable ID attribute
		$this->form->attr('id', 'FormBuilder_' . $this->form->name);
		$this->form->addClass('FormBuilder'); 
		$this->form->addClass('InputfieldNoFocus');

		if($this->input->get('export_d') && $this->wire('user')->hasPermission('form-builder')) {
			// generate the embed method D file
			$formFile = null;
			$texts = array('labels' => array(), 'descriptions' => array(), 'notes' => array());	
			foreach($this->form->getAll() as $inputfield) {
				$texts['labels'][$inputfield->name] = $inputfield->label;
				$texts['descriptions'][$inputfield->name] = $inputfield->description;
				$texts['notes'][$inputfield->name] = $inputfield->notes;	
				if($inputfield->label) $inputfield->label = "{pwfb:labels:$inputfield->name}";
				if($inputfield->description) $inputfield->description = "{pwfb:descriptions:$inputfield->name}";
				if($inputfield->notes) $inputfield->notes = "{pwfb:notes:$inputfield->name}";
				if($inputfield->className() == 'InputfieldSubmit') {
					$texts['labels'][$inputfield->name] = $inputfield->attr('value');
					$inputfield->attr('value', "{pwfb:labels:$inputfield->name}");
				}
			}
			$out .= $this->renderReady($this->form); 
			include_once(__DIR__ . '/FormBuilderMarkup.php');
			$m = new FormBuilderMarkup($out, $this->form, $framework, $texts);
			$cachePath = $config->paths->cache . 'FormBuilder/';
			$exportFile = $cachePath . "form-$this->formName.php";
			$m->saveTo($exportFile);
			$out = 
				"<div style='text-align:center;font-family:sans-serif;'>" . 	
					"<h3>" . $this->_('Form Markup Exported:') . "</h3>" . 
					"<p>$exportFile</p>" . 
					"<p><small>" . $this->_('You may close this window.') . "</small></p>" . 
				"</div>";
			unset($m);

		} else if(($this->input->get('preview') || $this->input->post('FormBuilderPreview')) && $this->wire('page')->editable()) {
			// we are in preview mode 
			$out .= $this->renderReady($this->form); 
			// add a hidden input for JS detection to add edit links to form fields
			$p = $this->wire('pages')->get("template=admin, name=" . FormBuilderMain::name); 
			if($p->id) $out = str_replace(
				"</form>", 
				"<input type='hidden' name='FormBuilderPreview' id='FormBuilderPreview' value='{$p->url}editField/?id={$this->id}&name=' />" . 
				"\n</form>", $out);
			
		} else if($formFile) {
			// we are rendering from a custom markup file in /site/templates/FormBuilder/	
			foreach($errors as $key => $error) {
				$errors[$key] = $this->wire('sanitizer')->entities($error);
			}
			
			$values = array();
			$labels = array();
			$descriptions = array();
			$notes = array();
			$sanitizer = $this->wire('sanitizer');
			
			foreach($this->form->getAll() as $inputfield) {
				$name = $inputfield->attr('name');
				$value = $inputfield->attr('value');
				if(is_object($value)) $value = (string) $value; 
				$values[$name] = $value;
				$labels[$name] = $sanitizer->entities($inputfield->label);
				$descriptions[$name] = $sanitizer->entities($inputfield->description);
				$notes[$name] = $sanitizer->entities($inputfield->notes);
				if($inputfield->className() == 'InputfieldSubmit') $labels[$name] = $sanitizer->entities($value);
			}
			
			$out .= $this->renderReady($this->form, $formFile, array(
				'submitted' => $submitted, 
				'errors' => $errors, 
				'values' => $values, 
				'labels' => $labels,
				'descriptions' => $descriptions,
				'notes' => $notes,
				'form' => $this->form, 
				'fbForm' => $this->fbForm, 
				'fbRender' => $this->fbRender, 
				'processor' => $this, 
				'framework' => $framework, 
				'successMessage' => $submitted ? $sanitizer->entities($this->successMessage) : '', 
			));
			
		} else {
			// normal form render
			$out .= $this->renderReady($this->form);
		}

		// insert the submitKey at the end of the form
		$out = str_replace('</form>', "\n\t" . $this->renderSubmitKey() . "\n</form>", $out);

		// if honeypot is here, give its wrapper a special class that hides it
		if($this->honeypot) $out = str_replace("wrap_Inputfield_{$this->honeypot}'", "wrap_Inputfield-'", $out);

		return $this->wrapOutput($out); 
	}

	/**
	 * Wraps all FormBuilder output 
	 * 
	 * @param string $out Output to wrap
	 * @return string
	 * 
	 */
	protected function ___wrapOutput($out) {
		return "<div class='FormBuilder FormBuilder-$this->formName'>\n$out\n</div><!--/.FormBuilder-->";
	}
	
	/**
	 * Hook called for render ready, returns the $form->render();
	 * 
	 * @param InputfieldForm $form
	 * @param string $formFile
	 * @param array $vars
	 * @return string
	 * 
	 */
	protected function ___renderReady($form, $formFile = '', array $vars = array()) { 
		// render the form
		if($formFile) {
			return wireRenderFile($formFile, $vars);
		} 
		$form->columnWidthSpacing = (int) $this->wire('config')->inputfieldColumnWidthSpacing; 
		if(!$form->hasClass('InputfieldFormNoWidths')) {
			$classes = InputfieldWrapper::getClasses();
			$classes = explode(' ', $classes['form']);
			if(!in_array('InputfieldFormNoWidths', $classes)) $form->addClass('InputfieldFormWidths'); 
		}	
		return $form->render();
	}

	/** 
	 * Create a new submitKey containing number of fields, random component and session key
	 *
	 * @return string
	 *
	 */
	public function makeSubmitKey() {
		if($this->submitKey) return $this->submitKey;
		$numFields = count($this->form->children); 
		if(!$this->skipSessionKey) {
			// if we're also using a sessionKey, then append it to the submitKey and remember in session
			$sessionKey = md5(mt_rand() . microtime() . mt_rand()); 
			$this->session->set('FormBuilderSessionKey_' . $this->formName, $sessionKey);
		} else {
			$this->form->protectCSRF = false;
			$sessionKey = '0';
		}
		$submitKey = $numFields . ':' . $this->formName . ':' . $sessionKey; 
		$this->submitKey = $submitKey;
		return $submitKey;
	}

	/** 
	 * Render the submitKey in a hidden form field, ready to be output
	 *
	 * @param string $submitKey Supply existing submitKey to only render the input for it
	 * @return string
	 *
	 */
	public function renderSubmitKey($submitKey = '') {
		if(empty($submitKey)) $submitKey = $this->makeSubmitKey();
		return "<input type='hidden' name='_submitKey' value='$submitKey' />";
	}

	/** 
	 * check whether or not the form is submitted and if it's valid
	 *
	 * @param bool $testOnly Only tests the formName portion of the submitKey. 
	 * @return string|bool Returns the submitKey if valid, or boolean false if not.
	 *	Returns boolean true if valid in $testOnly mode. 
	 *
	 */
	public function validSubmitKey($testOnly = false) {

		// first check if form posted
		$submitKey = $this->input->post('_submitKey'); 
		if(empty($submitKey)) return false; 

		// extract the submitKey to the individual parts
		$parts = explode(':', $submitKey); 
		if(count($parts) !== 3) return false;
		list($numFields, $formName, $sessionKey) = $parts;
		$numFields = (int) $numFields;

		// if formName doesn't match up, it's not valid
		if($formName !== $this->formName) return false;

		// if we're only testing for a valid form name, we can exit now
		if($testOnly) return true; 

		// if session key is required, check that it is also correct
		if(!$this->skipSessionKey) {
			// if number of fields doesn't match up, it's not valid
			if($numFields != count($this->form->children)) return false; 
			$session = $this->wire('session');

			$sessionKeyName = 'FormBuilderSessionKey_' . $this->formName;
			$sessionKey2 = $session->get($sessionKeyName);
			if($sessionKey !== $sessionKey2) {
				// session key is invalid, making the form submission invalid
				// check if its a previous submit key, perhaps they just double submitted? 
				if($sessionKey === $session->get('FormBuilderSessionKeyLast')) {
					// if so, we'll acknowledge it
					$this->errors[] = $this->_('This form was already submitted.'); 
				}
				return false; 
			}
			$session->remove($sessionKeyName);
			$session->set('FormBuilderSessionKeyLast', $sessionKey2); 
		} else {
			if($sessionKey != "0") return false;
			$this->form->protectCSRF = false;
		}

		// reconstruct the submitKey just for added measure
		$submitKey = "$numFields:{$this->formName}:$sessionKey";
		return $submitKey; 
	}


	/**
	 * Process the input for a submitted form
	 *
	 * @param int $id Optional id of entry, if it already exists
	 * @return bool Whether the submission was successful
	 *
	 */
	protected function ___processInput($id = 0) {

		// determine if valid form was submitted and return if not
		if($this->validSubmitKey() === false) {
			if($this->input->post('_submitKey') && !count($this->errors)) $this->errors[] = $this->_('Invalid form submission');
			return false;
		}

		$filterSpam = $this->saveFlags & self::saveFlagFilterSpam;

		// if honeypot was populated, then do nothing but pretend it was successful
		if($filterSpam && $this->honeypot && strlen($this->input->post($this->honeypot))) return true; 

		// let the form process itself
		$this->processInputReady($this->form); 
		$this->form->processInput($this->input->post);

		if($filterSpam) {
			// perform optional turing test
			$this->processInputTuringTest();

			// perform optional Akismet spam filtering
			$this->processInputAkismet();
		}

		$this->processInputDone($this->form);
		// if errors occurred then trigger error hooks and return
		$errors = $this->getErrors(); 
		if(count($errors)) {
			$this->formSubmitError($this->form, $errors);
			return false;
		}

		$entryID = $this->saveForm($this->form, $id);
		if(is_int($entryID)) $this->entryID = $entryID; 

		// trigger the success hook
		$this->formSubmitSuccess($this->form); 

		// if there is a success URL, redirect to it (not typically used)
		if($this->successUrl) $this->session->redirect($this->successUrl);

		return true; 
	}

	/**
	 * Hook called right before input is processed
	 * 
	 * @param InputfieldForm $form
	 * 
	 */
	protected function ___processInputReady(InputfieldForm $form) { }

	/**
	 * Hook called immediately after input is processed 
	 * 
	 * @param InputfieldForm $form
	 * 
	 */	
	protected function ___processInputDone(InputfieldForm $form) { }
	

	/**
	 * Check the submission against a turing test, when enabled
	 *
	 */
	protected function processInputTuringTest() {
		if(empty($this->turingTest)) return;

		foreach($this->turingTest as $fieldName => $answer) {
			$field = $this->form->get($fieldName); 				
			if(!$field || !$field instanceof Inputfield) continue; 
			if($field->attr('value') != $answer) $field->error($this->_('Incorrect answer')); 
		}
	}

	/**
	 * Check the submission against Akismet, when enabled
	 *
	 * Akismet check is not performed if other errors have already occurred.
	 *
	 */
	protected function processInputAkismet() {

		if(!$this->akismet || count($this->form->getErrors())) return;

		list($author, $email, $content) = explode(',', $this->akismet);

		$author = $this->form->get($author)->attr('value');
		$email = $this->form->get($email)->attr('value');
		$content = $this->form->get($content)->attr('value');

		require_once(dirname(__FILE__) . '/FormBuilderAkismet.php'); 	
		$akismet = new FormBuilderAkismet($this->wire('modules')->get('FormBuilder')->akismetKey); 

		if($akismet->isSpam($author, $email, $content)) {
			$this->errors[] = $this->_('Spam filter has been triggered'); 
		}
	}

	/**
	 * Save the form to the database
	 *
	 * @param InputfieldForm $form
	 * @param int $id Optional id of form, if it already exists
	 * @return int ID of inserted entry (if saving to entries database) or boolean true if not.
	 *
	 */
	protected function ___saveForm(InputfieldForm $form, $id = 0) {

		$data = array();
		$entryID = 0; 

		if(($this->saveFlags & self::saveFlagDB) || ($this->saveFlags & self::saveFlagAction2) || ($this->saveFlags & self::saveFlagPage)) {

			// prepare a $data array that is used by DB or action2 saves
			foreach($form->getAll() as $f) {
				if($f instanceof InputfieldWrapper) continue; 
				$value = $f->attr('value');
				if(is_object($value)) $value = (string) $value; 
				$name = $f->name; 
				$data[$name] = $value; 
			}

			// save the form to a page	
			if($this->saveFlags & self::saveFlagPage) {
				$data['_savePage'] = (int) ((string) $this->savePage($data));
			}

			// save the form to the DB
			if($this->saveFlags & self::saveFlagDB) {
				require_once(dirname(__FILE__) . '/FormBuilderEntries.php'); 
				$entries = new FormBuilderEntries($this->id, $this->wire('database'));
				$data['id'] = $id; 
				$entryID = (int) $entries->save($data); // returns entry ID
			}
		}
		
		$data['entryID'] = $entryID; 

		// Email the form to recipient(s) if applicable
		if($this->saveFlags & self::saveFlagEmail) $this->emailForm($form, $data);

		// Send an auto-responder if applicable
		if($this->saveFlags & self::saveFlagResponder) $this->emailFormResponder($form, $data);
	
		// if there is a secondary action, then initiate a duplicate post
		if(($this->saveFlags & self::saveFlagAction2) && $this->action2) $this->postAction2($data); 

		return $entryID;
	}

	/**
	 * Save the form result to a Page
	 *
	 * @param array $data Form data to send to page
	 * @param int $status Status of created pages
	 * @param array|null $onlyFields Save field names present in this array. If omitted, save all field names. Names are form field names.
	 * @return Page Created page or null on failure
	 *
	 */
	public function ___savePage(array $data, $status = null, $onlyFields = null) {

		if(is_null($status)) $status = (int) $this->savePageStatus; 
		if(!$this->savePageTemplate || !$this->savePageParent) return null; 

		// if savePage contains a value, then we'll move forward with the save in order to update the page
		if(!$status && empty($data['_savePage'])) return null;

		$template = $this->wire('templates')->get($this->savePageTemplate); 
		$parent = $this->wire('pages')->get((int) $this->savePageParent); 
		if(!$template || !$parent->id) return null;

		$page = null;
		$of = false;
		// check if we should send to existing page
		if(!empty($data['_savePage'])) { 
			$page = $this->wire('pages')->get((int) $data['_savePage']); 
			if($page->id) {
				// if existing page doesn't have same template/parent, then we don't use it
				if($page->template !== $template || $page->parent->id !== $parent->id) $page = null;
			} else {
				// if no status defined and page didn't exist, don't create a new one
				if(!$this->savePageStatus) return null;
				$page = null;
			}
		}
	
		// create a new page	
		if(is_null($page)) { 	
			$page = new Page();
			$page->parent = $parent;
			$page->template = $template; 
			$page->status = $status; 
			$isNew = true; 
		} else {
			$isNew = false;
			$of = $page->of();
			if($of) $page->of(false);
		}

		// fields that must be populated after first save
		$fileFields = array();

		// populate field values to the page
		foreach($this->savePageFields as $field_id => $formFieldName) {
		
			if(empty($formFieldName)) continue; 
			if(is_array($onlyFields) && !in_array($formFieldName, $onlyFields)) continue; 

			if(ctype_digit("$field_id")) { 
				// custom field
				$field = $this->wire('fields')->get((int) $field_id); 
				if(!$field) continue; 
				$pageFieldName = $field->name; 

				if($field->type instanceof FieldtypeFile) {
					if($this->savePageField($page, $pageFieldName, $data[$formFieldName])) {
						$fileFields[] = array($formFieldName, $pageFieldName);
					}
					continue; 

				}

			} else if($field_id === 'name') {
				// allowed native field
				$pageFieldName = $field_id; 

			} else {
				// unknown or invalid field
				continue;
			}

			$value = isset($data[$formFieldName]) ? $data[$formFieldName] : null;
			if($pageFieldName === 'name') $value = $this->wire('sanitizer')->pageName($value, true); 
			if($this->savePageField($page, $pageFieldName, $value)) {
				$oldValue = $page->get($pageFieldName); 
				if(is_object($oldValue)) {
					if($oldValue instanceof WireArray) $oldValue->removeAll();
				}
				$page->set($pageFieldName, $value); 
			}
		}

		if(!strlen($page->title)) $page->title = date('Y-m-d H:i:s'); 
		
		try {
			$this->savePageReady($page, $data); 
			$page->save();
		} catch(Exception $e) {
			if($this->wire('config')->debug || $this->wire('user')->isSuperuser()) $this->error($e->getMessage()); 
		}

		// process any fields that can only be set for a page that exists (like file fields)
		if($page->id && count($fileFields)) {
			foreach($fileFields as $item) {
				list($formFieldName, $pageFieldName) = $item;
				$value = isset($data[$formFieldName]) ? $data[$formFieldName] : null;
				if(empty($value)) continue; 
				$pageField = $this->wire('fields')->get($pageFieldName); 
				$pageValue = $page->get($pageFieldName);
				if($pageField->maxFiles == 1 && count($pageValue)) $pageValue->removeAll(); // replace single files
				if(is_array($value)) foreach($value as $file) {
					try {
						$pageValue->add($file);
					} catch(Exception $e) {
						if($this->wire('config')->debug || $this->wire('user')->isSuperuser()) $this->error($e->getMessage()); 
					}
				}
				$page->set($pageFieldName, $pageValue);
				// $this->message("page->set($pageFieldName, " . print_r($value, true) . ")");
			}
			try {
				$page->save();
			} catch(Exception $e) {
				if($this->wire('config')->debug || $this->wire('user')->isSuperuser()) $this->error($e->getMessage()); 
			}
		}

		if($page->id) $this->savePageDone($page, $data, $isNew, $onlyFields); 
		if($of) $page->of(true);

		return $page; 
	}

	/**
	 * Returns true if given value should be saved, false if not
	 * 
	 * @param Page $page
	 * @param string $name
	 * @param string $value
	 * @return bool
	 *
	 */
	protected function ___savePageField(Page $page, $name, $value) { return true; }

	/**
	 * Hook called right before a Page is about to be saved
	 * 
	 * @param Page $page
	 * @param array $data
	 * 
	 */
	protected function ___savePageReady(Page $page, array $data) { }

	/**
	 * Hook called right after a page is saved
	 * 
	 * @param Page $page
	 * @param array $data
	 * @param bool $isNew
	 * @param null|array $onlyFields Save field names present in this array. If omitted, save all field names. Names are form field names.
	 * 
	 */
	protected function ___savePageDone(Page $page, array $data, $isNew, $onlyFields) { }

	/**
	 * Email the form result to the recipient defined by $emailTo
	 *
	 * @param InputfieldForm $form 
	 * @param array $data
	 * @return bool Whether it was successful
	 *
	 */
	protected function ___emailForm(InputfieldForm $form, array $data) {

		if(!strlen($this->emailTo)) return false;		

		require_once(dirname(__FILE__) . '/FormBuilderEmail.php');
		$email = new FormBuilderEmail($form);
		$email->to = $this->emailTo;
		$email->replyTo = $this->emailFrom;
		$email->from = $this->emailFrom2;
		$email->subject = $this->emailSubject;
		$email->setRawFormData($data); 

		if($this->honeypot) $email->setSkipFieldName($this->honeypot); 

		return $email->send('email-administrator');
	}

	/**
	 * Email the form result to the sending (auto-responder)
	 *
	 * @param InputfieldForm $form 
	 * @param array $data
	 * @return bool Whether it was successful
	 *
	 */
	protected function ___emailFormResponder(InputfieldForm $form, array $data) {

		if(!strlen($this->responderTo)) return false;		
		$field = $form->get($this->responderTo);
		if(!$field) return false;
		$responderTo = $this->wire('sanitizer')->email($field->attr('value'));
		if(!strlen($responderTo)) return false;

		require_once(dirname(__FILE__) . '/FormBuilderEmail.php');
		$email = new FormBuilderEmail($form);
		$email->to = $responderTo;
		$email->from = $this->responderFrom;
		$email->subject = $this->responderSubject; 
		$email->body = $this->responderBody; 
		$email->setRawFormData($data); 

		if($this->honeypot) $email->setSkipFieldName($this->honeypot); 

		return $email->send('email-autoresponder');
	}

	/**
	 * Post a duplicate copy of the form to another URL
	 *
	 * @param array $data
	 * 
	 */
	protected function ___postAction2(array $data) {

		unset($data['id'], $data[$this->formName . '_submit']); 

		// remove fields
		foreach($this->action2_remove as $name) {
			unset($data[$name]); 
		}	
		// add fields
		foreach($this->action2_add as $name => $value) {
			$data[$name] = $value; 
		}
		// rename fields
		foreach($this->action2_rename as $name => $newName) {
			if(!array_key_exists($name, $data)) continue; 
			$value = $data[$name]; 
			unset($data[$name]); 
			$data[$newName] = $value; 
		}

		$url = $this->action2;
		$method = 'post';

		// allow for specifying the method as part of the URL
		// i.e. GET:http://www.domain.com/ (default is POST)
		if(preg_match('/^(GET|POST):(.+)$/i', $url, $matches)) {
			$url = $matches[2]; 
			$method = strtolower($matches[1]);
		}

		// post the data
		$http = new WireHttp();
		$http->setHeader('referer', $this->wire('page')->httpUrl()); 
		$http->setHeader('User-Agent', 'ProcessWire FormBuilder/2.4 (+http://processwire.com)'); 
		if($method == 'get') $http->get($url, $data);
			else $http->post($url, $data);
	}

	/**
	 * Called upon successful form submission
	 *
	 * Intended for hooks to listen to. 
	 *
	 * @param InputfieldForm $form
	 *
	 */
	protected function ___formSubmitSuccess(InputfieldForm $form) {
		$this->submitted = true; 
	}

	/**
	 * Called upon a form submission error, for hooks to listen to.
	 *
	 * @param InputfieldForm $form
	 * @param array $errors Array of errors that occurred (strings)
	 *
	 */
	protected function ___formSubmitError(InputfieldForm $form, array $errors) {
		$this->submitted = false;
	}

	/**
	 * Render the given success message for output
	 *
	 * @param string $message
	 * @return string
	 *
	 */
	protected function ___renderSuccess($message) {
	
		$message = trim($message);
		$out = 'Success';
		$successUrl = '';

		if(ctype_digit("$message")) {

			$page = $this->pages->get((int) $message); 
			if($page->id) $successUrl = $page->url;

		} else {

			// With the regex below, we are sifting through the success message to determine if it is just text, a URL or a URL:field
			// Variable Positions: 1 ........... 2 . 3 ................. 4 .....
			if(!preg_match('{^(/[-_a-z0-9/]+|\d+)(:?)((?:[_a-zA-Z0-9]+)?)(\?.*)?$}', $message, $matches)) {
				// if not a path then populate a simple text success message
				$markup = InputfieldWrapper::getMarkup();
				return nl2br(str_replace('{out}', $message, $markup['success'])); 
			}

			// we have matched a $message is in the format: /path/to/page/ or /path/to/page/:field or 123:field
			if(strlen($matches[2]) && strlen($matches[3])) {
				// pull the field from /path/to/page
				$page = $this->pages->get($matches[1]); 
				$field = $matches[3]; 
				$value = $page->get($field); 
				if(strlen($value)) $out = "<div class='InputfieldMarkup'><div class='InputfieldContent'>$value</div></div>"; 
				
			} else {
				// just a redirect URL
				$successUrl = $matches[1]; 
				// page path
				if(strpos($successUrl, '?') === false) {
					// attempt to tie the path to page, in case site is running from subdir, path can start non-subdir
					$page = $this->pages->get($successUrl); 
					if($page->id) $successUrl = $page->url; 
				}
				if(isset($matches[4])) $successUrl .= $matches[4]; // opitonal query string
			}

		}

		if($successUrl) {
			// JS redirect required since we will be redirecting the parent window
			$out = 	"<script type='text/javascript'>window.top.location.href='$successUrl';</script>" . 
				"<noscript><a href='$successUrl'>$successUrl</a></noscript>";
		}

		return $out;
	}

	/**
	 * Render the given error messages for output
	 *
	 * @return string
	 *
	 */
	protected function ___renderErrors() {

		$markup = InputfieldWrapper::getMarkup();
		$tpl = $markup['error']; 
		$out = '';
		$debug = InputfieldForm::debug; 

		// prepend our standard error message to the top
		$errors = $this->getErrors($debug);
		array_unshift($errors, $this->errorMessage); 

		foreach($errors as $error) {
			$error = htmlentities($error, ENT_QUOTES, "UTF-8"); 
			$out .= str_replace('{out}', $error, $tpl); 
		}

		if($debug) {
			$tpl = $this->wire('forms')->markup_success;
			foreach($this->form->messages() as $message) {
				$message = htmlentities($message, ENT_QUOTES, "UTF-8"); 
				$out .= str_replace('{out}', $message, $tpl); 
			}
			/*
			foreach(wire('session') as $key => $value) {
				if(is_array($value)) $value = print_r($value, true); 
				$message = htmlentities("$key: $value", ENT_QUOTES, "UTF-8"); 
				$out .= str_replace('{out}', $message, $tpl); 
			}
			*/
		}

		if($out) $out = "<div class='FormBuilderErrors'>$out</div>";

		return $out; 
	}

	/**
	 * Given a form configuration array, create an InputfieldForm from it
	 *
	 * @param array $a Form configuration array
	 * @param InputfieldWrapper $inputfields For internal/recursive use only
	 * @return InputfieldForm
	 *
	 */
	protected function arrayToInputfields(array $a, $inputfields = null) {

		$language = null;
		if($this->wire('languages')) {
			$language = $this->wire('user')->language; 
			if($language && $language->isDefault()) $language = null;
		} 

		if(is_null($inputfields)) {
			// start a new form
			$inputfields = $this->wire('modules')->get('InputfieldForm'); 
			$inputfields->attr('method', $a['method']); 
			$inputfields->attr('action', $a['action']); 
			if(!empty($a['target'])) $inputfields->attr('target', $a['target']); 
		
			// make sure it starts where we expect
			if($a['type'] == 'Form') {
				$inputfields->attr('id+name', $a['name']); 
				$this->formName = $a['name'];
				foreach($a as $k => $v) {
					if($this->$k !== null) $this->set($k, $v); 
					if($language) {
						// swap language value with default, when applicable
						if(!empty($a["$k$language"])) $this->set($k, $a["$k$language"]); 
					}
				}
				$a = isset($a['children']) ? $a['children'] : array(); 
			}
			$isForm = true;
		} else $isForm = false;

		foreach($a as $name => $data) {

			if(!is_array($data) || empty($data['type'])) continue; 
			
			/** @var Inputfield|InputfieldWrapper $f */
			$f = $this->wire('modules')->get('Inputfield' . $data['type']); 		
			if(!$f) $f = $this->wire('modules')->get('InputfieldText'); 
			$f->attr('name', $name); 
			$f->attr('id', 'Inputfield_' . $name); 
			$f->set('formBuilder', true); // in case any Inputfields need to know this context
			$f->set('hasFieldtype', false); // in case any Inputfields need to know this context
			$f->setParent($inputfields); 

			if($f instanceof InputfieldFormBuilderInterface) {
				// set extra values to InputfieldFormBuilder derived Inputfields
				$f->set('processor', $this);
				$f->set('formID', $this->id); 
			}

			foreach($data as $key => $value) {
				if(in_array($key, array('type', 'children'))) continue; 
				$f->$key = $data[$key];
			}

			if($language) foreach(array('label', 'description', 'notes') as $key) {
				$langKey = $key . $language->id; 
				$langVal = $f->$langKey;
				if(strlen($langVal)) $f->$key = $langVal;
			}

			if(!empty($data['children']) && $f instanceof InputfieldWrapper) {
				// this field contains children, convert them
				$this->arrayToInputfields($data['children'], $f);	

			} else if($this->allowPreset && !is_null($this->input->get($name))) {
				// a value is being pre-set from a GET var
				$f->processInput($this->input->get); 	
			}

			$inputfields->add($f); 
		}	

		if($isForm) {
			$submit = $this->wire('modules')->get('InputfieldSubmit');	
			$submit->attr('id+name', $this->formName . '_submit'); 
			$submit->attr('value', $this->submitText); 
			if($language) {
				$value = $this->get("submitText$language"); 
				if(strlen($value)) $submit->attr('value', $value); 
			}
			$inputfields->add($submit);
		}

		return $inputfields;
	}

	/**
	 * Get an array of all values from this form
	 *
	 * Should be called only after successful form submission, see isSubmitted() method
	 *
	 * @return array Values indexed by inputfield 'name' attribute
	 *
	 */
	public function getValues() {

		$values = array();
		$skipTypes = array(
			'InputfieldMarkup',
			'InputfieldWrapper',
			'InputfieldSubmit',
			);

		$inputfields = $this->form->getAll();

		foreach($inputfields as $f) {
			$skip = false;
			foreach($skipTypes as $type) if($f instanceof $type) $skip = true; // if(is_a($f, $type)) $skip = true; 
			if($skip) continue; 
			$name = $f->attr('name'); 
			$value = $f->attr('value'); 
			$values[$name] = $value; 
		}

		return $values; 
	}

	/**
	 * Was the form successfully submitted? 
	 *
	 * @return bool
	 *
	 */
	public function isSubmitted() {
		return $this->submitted; 
	}

	/**
	 * Get the constructed form 
	 *
	 * @return InputfieldForm
	 *
	 */
	public function getInputfieldsForm() {
		return $this->form; 
	}

	/**
	 * Get the array upon which this form is based (same as what was passed to constructor)
	 *
	 * @return array
	 *
	 */
	public function getFormArray() {
		return $this->formArray; 
	}

	/**
	 * Get the FormBuilderEntries object for this form
	 * 
	 * @return FormBuilderEntries
	 * 
	 */
	public function getEntries() {
		return $this->wire('forms')->get($this->formName)->entries();
	}

	/**
	 * Get the current entry ID, or 0 if not present
	 * 
	 * @return int
	 * 
	 */
	public function getEntryID() {
		return $this->entryID; 
	}

	/**
	 * Get the current form entry, or null if not present
	 * 
	 * @return array|null
	 * 
	 */
	public function getEntry() {
		return $this->entryID ? $this->getEntries()->get($this->entryID) : null;
	}

	/**
	 * Return an array of errors that occurred (strings)
	 *
	 * @param bool $all When true, all errors are included. When false, field-specific errors (displayed inline) are excluded.
	 * @return array Will be blank if no errors. 
	 *
	 */
	public function getErrors($all = true) {
		if($all) {
			$errors = $this->form->getErrors(); 
		} else {
			$errors = array();
		}
		// prepend any self generated errors
		foreach($this->errors as $error) {
			array_unshift($errors, $error); 
		}
		return $errors;
	}
}

