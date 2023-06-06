<?php namespace ProcessWire;

/**
 * Contains multiple MediaManager objects for a single page
 *
 */

class MediaManagerArray extends WireArray {

	protected $page;

	public function __construct(Page $page) {
		$this->page = $page;
	}

	public function isValidItem($item) {
		return $item instanceof MediaManager;// item returned as instance of MediaManager
	}

	public function add($item) {
		$item->page = $this->page;
		return parent::add($item);// back to WireArray
	}

	public function __toString() {
		$out = '';
		foreach($this as $item) $out .= $item;
		return $out;
	}


}

