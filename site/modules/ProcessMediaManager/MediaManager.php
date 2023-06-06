<?php namespace ProcessWire;

/**
 * An individual media-manager item to be part of an MediaManagerArray for a Page
 *
 */
class MediaManager extends WireData {


	/**
	 * We keep a copy of the $page that owns this media-manager so that we can follow
	 * its outputFormatting state and change our output per that state
	 *
	 */
	protected $page;

	/**
	 * Construct a new MediaManager
	 *
	 */
	public function __construct() {
		// define the fields that represent our media-manager's items
		$this->set('id', '');
		$this->set('type', '');
	}

	/**
	 * Set a value to the media-manager: id, type
	 *
	 */
	public function set($key, $value) {

		if($key == 'page') {
			$this->page = $value;
			return $this;
		}
		// sanitize values as integers
		elseif($key == 'id' || $key == 'type') $value = (int) $value;

		return parent::set($key, $value);// back to WireData

	}

	/**
	 * Retrieve a value from the media-manager: row, column, value
	 *
	 */
	public function get($key) {
		$value = parent::get($key);// retrieve from WireData method get()
		// if the page's output formatting is on, then we'll return sanitized values
		if($this->page && $this->page->of()) {
			if($key == 'id' || $key == 'type') $value = (int) $value;
		}
		return $value;
	}

	/**
	 * Get the given version/variation of an original image.
	 *
	 * Versions are images cropped or resized within the media page itself using ProcessWire.
	 *
	 * @access public
	 * @param Page $mediaPage An image media page where the image is saved.
	 * @param integer $version Denoting version number of the image as assigned by ProcessWire.
	 * @return object $image The image object.
	 *
	 */
	public function getImageVersion($mediaPage, $version) {

		$images = $mediaPage->media_manager_image;
		$originalImage = $images->first();

		if(!$originalImage) return false;

		$ext = '.' . $originalImage->ext;
		$originalBasename = $originalImage->basename;

		// get the version image basename (although we expect only 1 image with versions (variations) in this field, you never know)
		// PW names versions as 'original_image_name-vxx.ext' where 'xx' is the $version
		$versionBasenameFilter = str_replace($ext, '', $originalBasename) . '-v';// returns 'original_image_name-v'

		// the full name (basename) of the version image (i.e. basename-v.ext)
		$versionBasename = $versionBasenameFilter . $version . $ext;// returns 'original_image_name-vxx.ext'

		// use WireArray method to get the image (in memory)
		$image = $images->get('basename='. $versionBasename);

		if($image) return $image;
		else return false;

	}

	/**
	 * Provide a default rendering for an audio media.
	 *
	 * Used in toString() if this field is directly echo'ed.
	 *
	 * @access public
	 * @return string $out HTML5 Markup string of audio media.
	 *
	 */
	public function renderMediaAudio($media, $title) {

		$out = '';
		if(!$media) return $out;

		$type = '';

		if($media->ext == 'mp3') $type = 'mpeg';
		elseif($media->ext == 'ogg') $type = 'ogg';
		elseif($media->ext == 'wav') $type = 'wav';

		$noSupport = $this->_('Your browser does not support the audio element.');

		$out =
			"<div class='mm_render_audio_player'>" .
				"<span>" . $title . "</span>" .
				"<audio controls preload='metadata'><source src='" . $media->url . "' type='audio/" . $type . "'>" .  $noSupport . "</audio>" .
			"</div>";

		return $out;

	}

	/**
	 * Provide a default rendering for a document media.
	 *
	 * Used in toString() if this field is directly echo'ed.
	 *
	 * @access public
	 * @return string $out Markup string for document media.
	 *
	 */
	public function renderMediaDocument($media, $title) {
		$out = '';
		if(!$media) return $out;
		$out = "<p><a href='" . $media->url . "'>" . $title . "</a></p>";
		return $out;
	}

	/**
	 * Provide a default rendering for an image media.
	 *
	 * Used in toString() if this field is directly echo'ed.
	 *
	 * @access public
	 * @return string $out Markup string of image media.
	 *
	 */
	public function renderMediaImage($media, $title) {
		$out = '';
		if(!$media) return $out;
		$title = $media->description ? $media->description : $title;
		$out = "<img src='" . $media->url . "' alt='" . $title . "' title='" . $title . "' style='width:400px;height:auto;margin:10px;'>";
		return $out;
	}

	/**
	 * Provide a default rendering for a video media.
	 *
	 * Used in toString() if this field is directly echo'ed.
	 *
	 * @access public
	 * @return string $out HTML5 Markup string of video media.
	 *
	 */
	public function renderMediaVideo($media, $title) {

		$out = '';
		if(!$media) return $out;

		$type = '';

		if($media->ext == 'mp4') $type = 'mp4';
		elseif($media->ext == 'ogg') $type = 'ogg';
		elseif($media->ext == 'webm') $type = 'webm';

		$noSupport = $this->_('Your browser does not support the video tag.');

		$out =
			"<div class='mm_render_video_player'>" .
				"<span>" . $title . "</span>" .
				"<video width='320' height='240' controls preload='metadata'><source src='" . $media->url . "' type='video/" . $type . "'>" .  $noSupport . "</video>" .
			"</div>";

		return $out;

	}

	/**
	 * Provide a default rendering for an media-manager
	 *
	 */
	public function renderMedia() {

		$out = '';

		// remember page's output formatting state
		$of = $this->page->of();
		// turn on output formatting for our rendering (if it's not already on)
		if(!$of) $this->page->of(true);

		if($this->type == 1) $out = $this->renderMediaAudio($this->media, $this->title);
		elseif($this->type == 2) $out = $this->renderMediaDocument($this->media, $this->title);
		elseif(($this->type == 3) || (strlen($this->type) > 1 && (int)substr($this->type, 0, 1) === 3)) $out = $this->renderMediaImage($this->media, $this->title);
		elseif($this->type == 4) $out = $this->renderMediaVideo($this->media, $this->title);

		if(!$of) $this->page->of(false);

		return $out;

	}

	/**
	 * Return a string representing this media-manager
	 *
	 */
	public function __toString() {
		return $this->renderMedia();
	}

}
