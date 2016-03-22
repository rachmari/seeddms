<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2013 Uwe Steinmann <uwe@steinmann.cx>
*  All rights reserved
*
*  This script is part of the SeedDMS project. The SeedDMS project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/


/**
 * Example extension
 *
 * @author  Uwe Steinmann <uwe@steinmann.cx>
 * @package SeedDMS
 * @subpackage  example
 */
class SeedDMS_ExtExample extends SeedDMS_ExtBase {

	/**
	 * Initialization
	 *
	 * Use this method to do some initialization like setting up the hooks
	 * You have access to the following global variables:
	 * $GLOBALS['dms'] : object representing dms
	 * $GLOBALS['user'] : currently logged in user
	 * $GLOBALS['session'] : current session
	 * $GLOBALS['settings'] : current global configuration
	 * $GLOBALS['settings']['_extensions']['example'] : configuration of this extension
	 * $GLOBALS['LANG'] : the language array with translations for all languages
	 * $GLOBALS['SEEDDMS_HOOKS'] : all hooks added so far
	 */
	function init() { /* {{{ */
		$GLOBALS['SEEDDMS_HOOKS']['view']['addDocument'][] = new SeedDMS_ExtExample_AddDocument;
		$GLOBALS['SEEDDMS_HOOKS']['view']['viewFolder'][] = new SeedDMS_ExtExample_ViewFolder;
	} /* }}} */

	function main() { /* {{{ */
	} /* }}} */
}

/**
 * Class containing methods for hooks when a document is added
 *
 * @author  Uwe Steinmann <uwe@steinmann.cx>
 * @package SeedDMS
 * @subpackage  example
 */
class SeedDMS_ExtExample_AddDocument {

	/**
	 * Hook before adding a new document
	 */
	function preAddDocument($view) { /* {{{ */
	} /* }}} */

	/**
	 * Hook after successfully adding a new document
	 */
	function postAddDocument($view) { /* {{{ */
	} /* }}} */
}

/**
 * Class containing methods for hooks when a folder view is ѕhown
 *
 * @author  Uwe Steinmann <uwe@steinmann.cx>
 * @package SeedDMS
 * @subpackage  example
 */
class SeedDMS_ExtExample_ViewFolder {

	/**
	 * Hook when showing a folder
	 *
	 * The returned string will be output after the object menu and before
	 * the actual content on the page
	 *
	 * @param object $view the current view object
	 * @return string content to be output
	 */
	function preContent($view) { /* {{{ */
		return $view->infoMsg("Content created by viewFolder::preContent hook.");
	} /* }}} */

	/**
	 * Hook when showing a folder
	 *
	 * The returned string will be output at the end of the content area
	 *
	 * @param object $view the current view object
	 * @return string content to be output
	 */
	function postContent($view) { /* {{{ */
		return $view->infoMsg("Content created by viewFolder::postContent hook");
	} /* }}} */

}

?>
