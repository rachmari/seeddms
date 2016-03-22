<?php
/**
 * Implementation of a document in the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @license    GPL2
 * @author     Markus Westphal, Malcolm Cowe, Matteo Lucarelli,
 *             Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal, 2006-2008 Malcolm Cowe,
 *             2010 Matteo Lucarelli, 2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * @uses SeedDMS_DatabaseAccess
 */
define('USE_PDO', 1);
if(defined('USE_PDO'))
	require_once('Core/inc.DBAccessPDO.php');
else
	require_once('Core/inc.DBAccess.php');

/**
 * @uses SeedDMS_DMS
 */
require_once('Core/inc.ClassDMS.php');

/**
 * @uses SeedDMS_Object
 */
require_once('Core/inc.ClassObject.php');

/**
 * @uses SeedDMS_Folder
 */
require_once('Core/inc.ClassFolder.php');

/**
 * @uses SeedDMS_Document
 */
require_once('Core/inc.ClassDocument.php');

/**
 * @uses SeedDMS_Attribute
 */
require_once('Core/inc.ClassAttribute.php');

/**
 * @uses SeedDMS_Group
 */
require_once('Core/inc.ClassGroup.php');

/**
 * @uses SeedDMS_User
 */
require_once('Core/inc.ClassUser.php');

/**
 * @uses SeedDMS_KeywordCategory
 */
require_once('Core/inc.ClassKeywords.php');

/**
 * @uses SeedDMS_DocumentCategory
 */
require_once('Core/inc.ClassDocumentCategory.php');

/**
 * @uses SeedDMS_Notification
 */
require_once('Core/inc.ClassNotification.php');

/**
 * @uses SeedDMS_UserAccess
 * @uses SeedDMS_GroupAccess
 */
require_once('Core/inc.ClassAccess.php');

/**
 * @uses SeedDMS_Workflow
 */
require_once('Core/inc.ClassWorkflow.php');

/**
 */
require_once('Core/inc.AccessUtils.php');

/**
 * @uses SeedDMS_File
 */
require_once('Core/inc.FileUtils.php');

?>
