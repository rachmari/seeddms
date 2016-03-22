<?php
/**
 * Implementation of Download controller
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
class SeedDMS_Controller_Download extends SeedDMS_Controller_Common {

	public function run() {
		$dms = $this->params['dms'];
		$type = $this->params['type'];
		$content = $this->params['content'];

		switch($type) {
			case "version":

				if(!$this->callHook('version')) {
					if(file_exists($dms->contentDir . $content->getPath())) {
						header("Content-Transfer-Encoding: binary");
						header("Content-Length: " . filesize($dms->contentDir . $content->getPath() ));
						$efilename = rawurlencode($content->getOriginalFileName());
						header("Content-Disposition: attachment; filename=\"" . $efilename . "\"; filename*=UTF-8''".$efilename);
						header("Content-Type: " . $content->getMimeType());
						header("Cache-Control: must-revalidate");

						readfile($dms->contentDir . $content->getPath());
					}
				}
				break;
		}
	}
}
