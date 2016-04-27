<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010 Matteo Lucarelli
//    Copyright (C) 2010-2012 Uwe Steinmann
//
//    This program is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program; if not, write to the Free Software
//    Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

include("../inc/inc.Settings.php");
include("../inc/inc.Utils.php");
include("../inc/inc.Language.php");
include("../inc/inc.Init.php");
include("../inc/inc.Extension.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.ClassAccessOperation.php");
include("../inc/inc.Authentication.php");

/**
 * Include class to preview documents
 */
require_once("SeedDMS/Preview.php");

$tmp = explode('.', basename($_SERVER['SCRIPT_FILENAME']));
$view = UI::factory($theme, $tmp[1]);
if(!$view) {
}
$documentID = null;
if (isset($_GET["documentid"]) && is_numeric($_GET["documentid"]) && intval($_GET["documentid"])>0) {
	$documentID = $_GET["documentid"];
}

$documentNumber = null;
if (isset($_GET["docnum"])) {
	$documentNumber = $_GET["docnum"];
}

if($documentID == null && $documentNumber == null) {
	$view->exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

if($documentNumber) {
	$documentID = $dms->getDocIDbyNum($_GET["docnum"]);	
}

$document = $dms->getDocument($documentID);

if (!is_object($document)) {
	$view->exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))), $documentID . " " .getMLText("no_doc"));
}

/* Create object for checking access to certain operations */
$accessop = new SeedDMS_AccessOperation($dms, $document, $user, $settings);

$folder = $document->getFolder();

if ($document->getAccessMode($user) < M_READ) {
	$view->exitError(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))),getMLText("access_denied"));
}

/* Recalculate the status of a document and reload the page if the status
 * has changed. A status change may occur if the document has expired in
 * the mean time
 */
if ($document->verifyLastestContentExpriry()){
	header("Location:../out/out.ViewDocument.php?documentid=".$document->getID());
}

if($view) {
	$view->setParam('dms', $dms);
	$view->setParam('user', $user);
	$view->setParam('folder', $folder);
	$view->setParam('document', $document);
	$view->setParam('accessobject', $accessop);
	$view->setParam('viewonlinefiletypes', $settings->_viewOnlineFileTypes);
	$view->setParam('enableownerrevapp', $settings->_enableOwnerRevApp);
	$view->setParam('cachedir', $settings->_cacheDir);
	$view->setParam('workflowmode', $settings->_workflowMode);
	$view->setParam('previewWidthList', $settings->_previewWidthList);
	$view->setParam('previewWidthDetail', $settings->_previewWidthDetail);
	$view->setParam('currenttab', isset($_GET['currenttab']) ? $_GET['currenttab'] : "");
	$view->setParam('timeout', $settings->_cmdTimeout);
	$view($_GET);
	exit;
}

?>
