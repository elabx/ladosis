<?php

/**
 * FormBuilder Basic framework initialization file
 * 
 * @property string $cssURL
 *
 */

class FormBuilderFrameworkBasic extends FormBuilderFramework {

	public function load() {

		$markup = array(
			'list' => "<div {attrs}>{out}</div>",
			'item' => "<div {attrs}>{out}</div>",
			'item_label' => "<label class='InputfieldHeader' for='{for}'>{out}</label>",
			'item_label_hidden' => "<label class='InputfieldHeader'><span>{out}</span></label>",
			'item_content' => "<div class='InputfieldContent {class}'>{description}{out}{error}{notes}</div>",
			'item_error' => "<div class='error'><small>{out}</small></div>",
			'item_description' => "<p class='description'>{out}</p>",
			'item_notes' => "<p class='notes'><small>{out}</small></p>",
			'success' => "<p class='success'>{out}</p>",
			'error' => "<p class='error'>{out}</p>",
			'item_icon' => "",
			'item_toggle' => "",
			'InputfieldFieldset' => array(
				'item' => "<fieldset {attrs}>{out}</fieldset>",
				'item_label' => "<legend>{out}</legend>",
				'item_label_hidden' => "<legend style='display:none'>{out}</legend>",
				'item_content' => "<div class='InputfieldContent'>{out}</div>",
				'item_description' => "<p class='description'>{out}</p>",
				'item_notes' => "<p class='notes'><small>{out}</small></p>",
			)
		);

		$classes = array(
			'form' => '', // 'InputfieldFormNoHeights',
			'list' => 'Inputfields',
			'list_clearfix' => 'pw-clearfix',
			'item' => 'Inputfield Inputfield_{name} {class}',
			'item_required' => 'InputfieldStateRequired',
			'item_error' => 'InputfieldStateError',
			'item_collapsed' => 'InputfieldStateCollapsed',
			'item_column_width' => 'InputfieldColumnWidth',
			'item_column_width_first' => 'InputfieldColumnWidthFirst',
			'InputfieldFieldset' => array(
				'item' => 'Inputfield_{name} {class}',
			)
		);

		InputfieldWrapper::setMarkup($markup);
		InputfieldWrapper::setClasses($classes);

		$config = $this->wire('config');
		$cssURL = $this->cssURL;
		if(strlen($cssURL) && strpos($cssURL, '//') === false && strpos($cssURL, $config->urls->root) !== 0) {
			$cssURL = $config->urls->root . ltrim($cssURL, '/');
		}

		$config->styles->append($config->urls->FormBuilder . 'FormBuilder.css');
		if(strlen($cssURL)) $config->styles->append($cssURL);
		$config->inputfieldColumnWidthSpacing = 0;

		if(!$this->form->theme) $this->form->theme = 'basic';

		// change markup of submit button
		$this->addHookBefore('InputfieldSubmit::render', $this, 'hookInputfieldSubmitRender');
	}

	public function hookInputfieldSubmitRender($event) {
		$in = $event->object;
		$event->replace = true;
		$event->return = "<button type='submit' name='$in->name' value='$in->value'>$in->value</button>";
	}
	
	public function set($key, $value) {
		if($key == 'cssURL' && strlen($value)) $value = $this->sanitizeURL($value, 'css');
		return parent::set($key, $value);
	}
	
	/**
	 * Return Inputfields for configuration of framework
	 *
	 * @return InputfieldWrapper
	 *
	 */
	public function getConfigInputfields() {
		$inputfields = parent::getConfigInputfields();
		$defaults = $this->getConfigDefaults();
		$f = $this->wire('modules')->get('InputfieldURL');
		$f->attr('name', 'cssURL');
		$f->label = $this->_('URL to CSS style that styles this form');
		$f->description = $this->_('Specify a URL/path relative to root of ProcessWire installation.');
		$f->attr('value', $this->cssURL);
		$cssURL = $this->wire('config')->urls->root . ltrim($defaults['cssURL'], '/'); 
		$f->notes = $this->_('Default value:') . " [$defaults[cssURL]]($cssURL)";
		$inputfields->add($f);
		return $inputfields;
	}

	public function getConfigDefaults() {
		$defaults = parent::getConfigDefaults();
		$cssURL = $this->getFrameworkURL() . 'main.css';
		$rootURL = $this->wire('config')->urls->root;
		if($rootURL != '/') {
			$cssURL = '/' . ltrim(substr($cssURL, strlen($rootURL)), '/');
		}
		$defaults['cssURL'] = $cssURL;
		return $defaults;
	}
	
	public function getFrameworkURL() {
		return $this->wire('config')->urls->get('FormBuilder') . 'frameworks/basic/';
	}
}
