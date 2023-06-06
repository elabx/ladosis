<?php namespace ProcessWire;

/**
 * Contains multiple MediaManager objects for a single MediaManagerArray
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

	/**
	 * Per the WireArray interface, return a blank MediaManager
	 *
	 * @return WireData $media A blank MediaManager.
	 *
	 */
	public function makeBlankItem() {
		$media = new MediaManager();
		return $media;
	}

	/**
	 * API to add add a MediaManager item (object) to a MediaManagerArray.
	 *
	 * @param MediaManager $item A MediaManager object.
	 * @return parent::add()
	 *
	 */
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

