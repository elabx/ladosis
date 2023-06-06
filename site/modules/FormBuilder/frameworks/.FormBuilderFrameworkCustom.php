<?php

/**
 * FormBuilder Custom framework initialization file
 *
 */

class FormBuilderFrameworkCustom extends FormBuilderFramework {
	
	protected function arrayToText(array $a) {
		$text = '';
		foreach($a as $key => $value) {
			$text .= "$key: $value\n";
		}
		return $text;
	}
	
	protected function arrayWithFieldsetData($data) {
		$data['InputfieldFieldset'] = array();
		foreach($data as $key => $value) {
			if(strpos($key, 'fieldset_') === 0) {
				$key2 = str_replace('fieldset_', '', $key);
				$data['InputfieldFieldset'][$key2] = $value;
				unset($data[$key]);
			}
		}
		return $data;
	}
	
	public function getCustomMarkup($text = false) {
		$data = array(
			'list' => "<div {attrs}>{out}</div><!-- Wrapper for list/group of Inputfields -->",
			'item' => "<div {attrs}>{out}</div><!-- Wrapper for each Inputfield item -->",
			'item_label' => "<label class='InputfieldHeader' for='{for}'>{out}</label>",
			'item_label_hidden' => "<label class='InputfieldHeader'><span>{out}</span></label>",
			'item_content' => "<div class='InputfieldContent {class}'>{description}{out}{error}{notes}</div>",
			'item_error' => "<div class='error'>{out}</div>",
			'item_description' => "<p class='description'>{out}</p>",
			'item_notes' => "<p class='notes'><small>{out}</small></p>",
			'success' => "<p class='success'>{out}</p>",
			'error' => "<p class='error'>{out}</p>",
			'item_icon' => "",
			'item_toggle' => "",
			// 'InputfieldFieldset' => array(
			'fieldset_item' => "<fieldset {attrs}>{out}</fieldset>",
			'fieldset_item_label' => "<legend>{out}</legend>",
			'fieldset_item_label_hidden' => "<legend style='display:none'>{out}</legend>",
			'fieldset_item_content' => "<div class='InputfieldContent'>{out}</div>",
			'fieldset_item_description' => "<p class='description'>{out}</p>",
			'fieldset_item_notes' => "<p class='notes'><small>{out}</small></p>",
			// )
		);
		if($text) return $this->arrayToText($data);
		return $this->arrayWithFieldsetData($data);
	}
	
	public function getCustomClasses($text = false) {
		$data = array(
			'form' => '', // 'InputfieldFormNoHeights',
			'list' => 'Inputfields',
			'list_clearfix' => 'pw-clearfix',
			'item' => 'Inputfield Inputfield_{name} {class}',
			'item_required' => 'InputfieldStateRequired',
			'item_error' => 'InputfieldStateError',
			'item_collapsed' => 'InputfieldStateCollapsed',
			'item_column_width' => 'InputfieldColumnWidth',
			'item_column_width_first' => 'InputfieldColumnWidthFirst',
			//'InputfieldFieldset' => array(
			'fieldset_item' => 'Inputfield_{name} {class}',
			//)
		);
		if($text) return $this->arrayToText($data);
		return $this->arrayWithFieldsetData($data);
	}

	public function load() {

		$markup = $this->getCustomMarkup();	
		$classes = $this->getCustomClasses();

		InputfieldWrapper::setMarkup($markup);
		InputfieldWrapper::setClasses($classes);

		$config = $this->wire('config');
		$config->styles->append($config->urls->FormBuilder . 'FormBuilder.css');
		$config->styles->append($this->getFrameworkURL() . 'main.css');
		$config->inputfieldColumnWidthSpacing = 0;

		$this->form->theme = 'basic';

		// change markup of submit button
		$this->addHookBefore('InputfieldSubmit::render', $this, 'hookInputfieldSubmitRender');
	}

	public function hookInputfieldSubmitRender($event) {
		$in = $event->object;
		$event->replace = true;
		$event->return = "<button type='submit' name='$in->name' value='$in->value'>$in->value</button>";
	}

	/**
	 * Return Inputfields for configuration of framework
	 *
	 * @return InputfieldWrapper
	 *
	 */
	public function getConfigInputfields() {
		$inputfields = parent::getConfigInputfields();
		return $inputfields;
	}

	public function getConfigDefaults() {
		return array();
	}

	public function getFrameworkURL() {
		return $this->wire('config')->urls->get('FormBuilder') . 'frameworks/basic/';
	}
}
