<?php

/**
 * ProcessWire Form Builder Form
 *
 * Serves as container form for FormBuilderField objects. 
 * It is an intermediary between the JSON/array form and Inputfields.
 *
 * Copyright (C) 2016 by Ryan Cramer Design, LLC
 * 
 * PLEASE DO NOT DISTRIBUTE
 * 
 * This file is commercially licensed, distributed and supported.
 * 
 * @property string $_styles Runtime property for frameworks to populate inline styles. 
 * @property int $mobilePx Mobile responsive breakpoint.
 * @property int $id
 * @property string $type
 * @property string $action
 * @property string $method
 * @property string $target
 * @property array $roles
 * @property string $theme
 * @property string $framework
 * 
 * @property int $saveFlags Bitwise flags for save actions
 * @property bool|int $allowPreset Preset field values from GET variables?
 * @property bool|int $skipSessionKey Disable session tracking and CSRF protection?
 * 
 * @property string $honeypot Name of honeypot field
 * @property string $turingTest
 * @property string $akismet CSV data for Akismet
 * @property array $listFields Field names to show in entries list
 * 
 * @property string $emailTo Email address, addresses, or format string to send form results to
 * @property string $emailFrom Email reply-to address
 * @property string $emailFrom2 Email from address
 * @property string $emailSubject Email subject line
 * 
 * @property string $responderTo Field that will contain submitters email address
 * @property string $responderFrom Email address that responder is from
 * @property string $responderSubject Subject of auto-responder
 * @property string $responderBody Body of auto-responder
 * 
 * @property string $action2 Duplicate submission URL
 * @property string $action2_add Add fields to duplicate submission (textarea format string)
 * @property string $action2_remove Newline separated field names to remove from duplicate submission
 * @property string $action2_rename Key=value format string of fields to rename in duplicate submission, one per line. 
 * 
 * @property string $submitText Default submit button text
 * @property string $successMessage
 * @property string $errorMessage
 * 
 * @property int $savePageTemplate
 * @property int $savePageParent
 * @property array $savePageFields
 * @property int $savePageStatus
 * 
 */

class FormBuilderForm extends FormBuilderField {

	/**
	 * Reference to FormBuilderMain
	 * 
	 * @var FormBuilderMain|null
	 *
	 */
	protected $forms = null;

	/**
	 * Reference to FormBuilderEntries instance, when cached. 
	 * 
	 * @var null|FormBuilderEntries
	 *
	 */
	protected $entries = null;

	/**
	 * Reference to FormBuilderProcessor instance, when cached. 
	 * 
	 * @var null|FormBuilderProcessor
	 *
	 */
	protected $processor = null;

	/**
	 * Reference to FormBuilderRender, when applicable
	 * 
	 * @var null|FormBuilderRender
	 * 
	 */
	protected $fbRender = null;

	/**	
	 * Form specific permission definitions
	 *
	 */
	protected $defaultRoles = array(
		'form-submit' => array('guest'),
		'form-list' => array(),
		'form-edit' => array(),
		'form-delete' => array(),
		'entries-list' => array(),
		'entries-edit' => array(),
		'entries-delete' => array(),
		'entries-page' => array()
		);

	/**
	 * Construct the form and set initial values
	 * 
	 * @param FormBuilderMain $forms
	 *
	 */
	public function __construct(FormBuilderMain $forms) {
		$this->forms = $forms; 
		parent::__construct();

		$this->set('id', 0); 
		$this->set('type', 'Form'); 
		$this->set('action', './');
		$this->set('method', 'post');
		$this->set('roles', $this->defaultRoles);

		// note that several other values may be set to the Form
		// like submitText, successMessage, etc. 
		// that are ultimately passed through to the FormBuilderProcessor
	}

	/**
	 * Save this form
	 *
	 */
	public function save() {
		return $this->forms->save($this);
	}

	/**
	 * Render this form's output and/or process if it has been posted.
	 *
	 * @return string
	 *
	 */ 
	public function render() {
		return $this->processor()->render();
	}

	/**
	 * Get this form's FormBuilderProcessor instance
	 *
	 * @return FormBuilderProcessor
	 *
	 */
	public function processor() {
		if(!$this->processor) {
			require_once(dirname(__FILE__) . '/FormBuilderProcessor.php');
			$a = $this->getArray();
			$this->processor = new FormBuilderProcessor($this->id, $a);
			$this->processor->setFbForm($this);
			$this->processor->action = $this->action; // @todo this may no longer be in use?
		}
		if($this->fbRender) $this->processor->setFbRender($this->fbRender);
		return $this->processor;
	}

	/**
	 * Get this form's FormBuilderEntries instance
	 *
	 * @return FormBuilderEntries
	 *
	 */
	public function entries() {
		if($this->entries) return $this->entries; 
		require_once(dirname(__FILE__) . '/FormBuilderEntries.php'); 
		$this->entries = new FormBuilderEntries($this->id, $this->forms->getDatabase());
		return $this->entries; 
	}

	/**
	 * Was the form submitted?
	 *
	 * @return bool
	 *
	 */
	public function isSubmitted() {
		return $this->processor()->isSubmitted();
	}

	/**
	 * Return a list of errors that occurred, if submitted.
	 *
	 * @return array
	 *
	 */
	public function getErrors() {
		return $this->processor()->getErrors();
	}

	/**
	 * Ensure that direct access to 'processor' or 'entries' goes to the right place
	 * 
	 * @param string $key
	 * @return mixed
	 *
	 */
	public function get($key) {
		if($key == 'processor') return $this->processor();
			else if($key == 'entries') return $this->entries();
		return parent::get($key);
	}

	public function set($key, $value) {
		if($key == 'roles') {
			if(!is_array($value)) $value = array();	
			$value = array_merge($this->defaultRoles, $value);
		}
		return parent::set($key, $value);
	}

	public function hasPermission($name) {
		$forms = wire('forms');
		/** @var FormBuilder $forms */
		return $forms->hasPermission($name, $this); 
	}
	
	public function getFramework() {
		return $this->forms->getFramework($this);
	}

	/**
	 * Set the FormBuilderRender
	 *
	 * @param FormBuilderRender $fbRender
	 *
	 */
	public function setFbRender(FormBuilderRender $fbRender) {
		$this->fbRender = $fbRender;
	}

	/**
	 * Get the FormBuilderRender used by this form (when available)
	 *
	 * @return FormBuilderRender|null
	 *
	 */
	public function getFbRender() {
		return $this->fbRender;
	}


}

