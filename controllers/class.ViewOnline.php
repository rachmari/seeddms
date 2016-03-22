<?php
/**
 * Implementation of ViewOnline controller
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2013 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class which does the busines logic for downloading a document
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2013 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Controller_ViewOnline extends SeedDMS_Controller_Common {

	public function run() {
		$dms = $this->params['dms'];
		$settings = $this->params['settings'];
		$type = $this->params['type'];
		$content = $this->params['content'];
		$document = $content->getDocument();

		switch($type) {
			case "version":
				if(!$this->callHook('version')) {
					header("Content-Type: " . $content->getMimeType());
					if (!isset($settings->_viewOnlineFileTypes) || !is_array($settings->_viewOnlineFileTypes) || !in_array(strtolower($content->getFileType()), $settings->_viewOnlineFileTypes)) {
						header("Content-Disposition: filename=\"" . $document->getName().$content->getFileType()) . "\"";
					}
					header("Content-Length: " . filesize($dms->contentDir . $content->getPath()));
					header("Expires: 0");
					header("Cache-Control: no-cache, must-revalidate");
					header("Pragma: no-cache");

					ob_clean();
					readfile($dms->contentDir . $content->getPath());
				}
				break;
		}
	}
}
