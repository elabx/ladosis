<?php

class GoogleAnalyticsAPIConfig extends ModuleConfig {
	public function getDefaults() {
		return array(
			"Service Key" => ""
		);
	}

	public function getInputfields() {
		$inputfields = parent::getInputfields();

		// $f = $this->modules->get('InputfieldText'); 
		// $f->attr('name', 'Service Key'); 
		// $f->label = 'Service Key';
		// $inputfields->add($f); 

		return $inputfields; 
	}
}