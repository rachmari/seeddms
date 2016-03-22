<?php
/**
 * Implementation of preview documents
 *
 * @category   DMS
 * @package    SeedDMS_Preview
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010, Uwe Steinmann
 * @version    Release: @package_version@
 */


/**
 * Class for managing creation of preview images for documents.
 *
 * @category   DMS
 * @package    SeedDMS_Preview
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2011, Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Preview_Previewer {
	/**
	 * @var string $cacheDir location in the file system where all the
	 *      cached data like thumbnails are located. This should be an
	 *      absolute path.
	 * @access public
	 */
	public $previewDir;

	/**
	 * @var integer $width maximum width/height of resized image
	 * @access protected
	 */
	protected $width;

	/**
	 * @var integer $timeout maximum time for execution of external commands
	 * @access protected
	 */
	protected $timeout;

	function __construct($previewDir, $width=40, $timeout=5) {
		if(!is_dir($previewDir)) {
			if (!SeedDMS_Core_File::makeDir($previewDir)) {
				$this->previewDir = '';
			} else {
				$this->previewDir = $previewDir;
			}
		} else {
			$this->previewDir = $previewDir;
		}
		$this->width = intval($width);
		$this->timeout = intval($timeout);
	}

	static function execWithTimeout($cmd, $timeout=5) { /* {{{ */
		$descriptorspec = array(
			0 => array("pipe", "r"),
			1 => array("pipe", "w"),
			2 => array("pipe", "w")
		);
		$pipes = array();
	 
	  $timeout += time();
		$process = proc_open($cmd, $descriptorspec, $pipes);
		if (!is_resource($process)) {
			throw new Exception("proc_open failed on: " . $cmd);
		}
			 
		$output = '';
		do {
			$timeleft = $timeout - time();
			$read = array($pipes[1]);
			stream_select($read, $write = NULL, $exeptions = NULL, $timeleft, NULL);
					 
			if (!empty($read)) {
				$output .= fread($pipes[1], 8192);
													}
		} while (!feof($pipes[1]) && $timeleft > 0);
 
		if ($timeleft <= 0) {
			proc_terminate($process);
			throw new Exception("command timeout on: " . $cmd);
		} else {
			return $output;
		}
	} /* }}} */

	/**
	 * Retrieve the physical filename of the preview image on disk
	 *
	 * @param object $object document content or document file
	 * @param integer $width width of preview image
	 * @return string file name of preview image
	 */
	protected function getFileName($object, $width) { /* }}} */
		$document = $object->getDocument();
		$dir = $this->previewDir.'/'.$document->getDir();
		switch(get_class($object)) {
			case "SeedDMS_Core_DocumentContent":
				$target = $dir.'p'.$object->getVersion().'-'.$width;
				break;
			case "SeedDMS_Core_DocumentFile":
				$target = $dir.'f'.$object->getID().'-'.$width;
				break;
			default:
				return false;
		}
		return $target;
	} /* }}} */

	/**
	 * Create a preview image for a given file
	 *
	 * @param string $infile name of input file including full path
	 * @param string $dir directory relative to $this->previewDir
	 * @param string $mimetype MimeType of input file
	 * @param integer $width width of generated preview image
	 * @return boolean true on success, false on failure
	 */
	public function createRawPreview($infile, $dir, $mimetype, $width=0, $target='') { /* {{{ */
		if($width == 0)
			$width = $this->width;
		else
			$width = intval($width);
		if(!$this->previewDir)
			return false;
		if(!is_dir($this->previewDir.'/'.$dir)) {
			if (!SeedDMS_Core_File::makeDir($this->previewDir.'/'.$dir)) {
				return false;
			}
		}
		if(!file_exists($infile))
			return false;
		if(!$target)
			$target = $this->previewDir.$dir.md5($infile).'-'.$width;
		if($target != '' && (!file_exists($target.'.png') || filectime($target.'.png') < filectime($infile))) {
			$cmd = '';
			switch($mimetype) {
				case "image/png":
				case "image/gif":
				case "image/jpeg":
				case "image/jpg":
				case "image/svg+xml":
					$cmd = 'convert -resize '.$width.'x '.$infile.' '.$target.'.png';
					break;
				case "application/pdf":
				case "application/postscript":
					$cmd = 'convert -density 100 -resize '.$width.'x '.$infile.'[0] '.$target.'.png';
					break;
				case "text/plain":
					$cmd = 'convert -resize '.$width.'x '.$infile.'[0] '.$target.'.png';
					break;
				case "application/x-compressed-tar":
					$cmd = 'tar tzvf '.$infile.' | convert -density 100 -resize '.$width.'x text:-[0] '.$target.'.png';
					break;
			}
			if($cmd) {
				//exec($cmd);
				try {
					self::execWithTimeout($cmd, $this->timeout);
				} catch(Exception $e) {
				}
			}
			return true;
		}
		return true;
			
	} /* }}} */

	public function createPreview($object, $width=0) { /* {{{ */
		if($width == 0)
			$width = $this->width;
		else
			$width = intval($width);
		$document = $object->getDocument();
		$file = $document->_dms->contentDir.$object->getPath();
		$target = $this->getFileName($object, $width);
		return $this->createRawPreview($file, $document->getDir(), $object->getMimeType(), $width, $target);

		if($width == 0)
			$width = $this->width;
		else
			$width = intval($width);
		if(!$this->previewDir)
			return false;
		$document = $object->getDocument();
		$dir = $this->previewDir.'/'.$document->getDir();
		if(!is_dir($dir)) {
			if (!SeedDMS_Core_File::makeDir($dir)) {
				return false;
			}
		}
		$file = $document->_dms->contentDir.$object->getPath();
		if(!file_exists($file))
			return false;
		$target = $this->getFileName($object, $width);
		if($target !== false && (!file_exists($target.'.png') || filectime($target.'.png') < $object->getDate())) {
			$cmd = '';
			switch($object->getMimeType()) {
				case "image/png":
				case "image/gif":
				case "image/jpeg":
				case "image/jpg":
				case "image/svg+xml":
					$cmd = 'convert -resize '.$width.'x '.$file.' '.$target.'.png';
					break;
				case "application/pdf":
				case "application/postscript":
					$cmd = 'convert -density 100 -resize '.$width.'x '.$file.'[0] '.$target.'.png';
					break;
				case "text/plain":
					$cmd = 'convert -resize '.$width.'x '.$file.'[0] '.$target.'.png';
					break;
				case "application/x-compressed-tar":
					$cmd = 'tar tzvf '.$file.' | convert -density 100 -resize '.$width.'x text:-[0] '.$target.'.png';
					break;
			}
			if($cmd) {
				//exec($cmd);
				try {
					self::execWithTimeout($cmd, $this->timeout);
				} catch(Exception $e) {
				}
			}
			return true;
		}
		return true;
			
	} /* }}} */

	public function hasRawPreview($infile, $dir, $width=0) { /* {{{ */
		if($width == 0)
			$width = $this->width;
		else
			$width = intval($width);
		if(!$this->previewDir)
			return false;
		$target = $this->previewDir.$dir.md5($infile).'-'.$width;
		if($target !== false && file_exists($target.'.png') && filectime($target.'.png') >= filectime($infile)) {
			return true;
		}
		return false;
	} /* }}} */

	public function hasPreview($object, $width=0) { /* {{{ */
		if($width == 0)
			$width = $this->width;
		else
			$width = intval($width);
		if(!$this->previewDir)
			return false;
		$target = $this->getFileName($object, $width);
		if($target !== false && file_exists($target.'.png') && filectime($target.'.png') >= $object->getDate()) {
			return true;
		}
		return false;
	} /* }}} */

	public function getRawPreview($infile, $dir, $width=0) { /* {{{ */
		if($width == 0)
			$width = $this->width;
		else
			$width = intval($width);
		if(!$this->previewDir)
			return false;

		$target = $this->previewDir.$dir.md5($infile).'-'.$width;
		if($target && file_exists($target.'.png')) {
			readfile($target.'.png');
		}
	} /* }}} */

	public function getPreview($object, $width=0) { /* {{{ */
		if($width == 0)
			$width = $this->width;
		else
			$width = intval($width);
		if(!$this->previewDir)
			return false;

		$target = $this->getFileName($object, $width);
		if($target && file_exists($target.'.png')) {
			readfile($target.'.png');
		}
	} /* }}} */

	public function getFilesize($object, $width=0) { /* {{{ */
		if($width == 0)
			$width = $this->width;
		else
			$width = intval($width);
		$target = $this->getFileName($object, $width);
		if($target && file_exists($target.'.png')) {
			return(filesize($target.'.png'));
		} else {
			return false;
		}

	} /* }}} */


	public function deletePreview($document, $object, $width=0) { /* {{{ */
		if($width == 0)
			$width = $this->width;
		else
			$width = intval($width);
		if(!$this->previewDir)
			return false;

		$target = $this->getFileName($object, $width);
	} /* }}} */
}
?>
