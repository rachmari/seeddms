<?php
include("/var/www/seeddms5.0/inc/inc.ClassSettings.php");


/*

$name, $comment, $expires, $owner, $keywords, $categories, $tmpFile, $orgFileName, $fileType, $mimeType, $sequence, $reviewers=array(), $approvers=array(),$reqversion=0,$version_comment="", $attributes=array(), $version_attributes=array(), $workflow=null, $docNumber=null, $paradeDoc=1, $pdfData=null, $attachFileData=null, $access=2

*/

function usage() { /* {{{ */
	echo "Usage:\n";
	echo "  seeddms-adddoc [--config <file>] [-c <comment>] [-k <keywords>] [-n <name>] [-V <version>] [-s <sequence>] [-t <mimetype>] [-a <attribute=value>] [-h] [-v] -F <folder id> -f <filename> -p <filenamePDF> -y <filenameAttach> -P <filenameAttachPDF> -X <access> -N <number> -d <timestamp date>\n";
	echo "\n";
	echo "Description:\n";
	echo "  This program uploads a file into a folder of SeedDMS.\n";
	echo "\n";
	echo "Options:\n";
	echo "  -h, --help: print usage information and exit.\n";
	echo "  -v, --version: print version and exit.\n";
	echo "  --config: set alternative config file.\n";
	echo "  -F <folder id>: id of folder the file is uploaded to\n";
	echo "  -c <comment>: set comment for document\n";
	echo "  -C <comment>: set comment for version\n";
	echo "  -k <keywords>: set keywords for file\n";
	echo "  -K <categories>: set categories for file\n";
	echo "  -n <name>: set name of file\n";
	echo "  -N <number>: document number\n";
	echo "  -V <version>: set version of file (defaults to 1).\n";
	echo "  -u <user>: login name of user\n";
	echo "  -f <filename>: upload this file\n";
	echo "  -b <orig filename>: the name given to the file by the user\n";
	echo "  -p <filenamePDF>: upload a pdf for the main source file\n";
	echo "  -B <orig filenamePDF>: the name given to the file by the user\n";
	echo "  -y <filenameAttach>: upload an attachment file; can occur multiple times\n";
	echo "  -q <orig filenameAttach>: the name given to the attachment file; can occur multiple times\n";
	echo "  -Q <orig filenameAttachPDF: the name given to the attachment file PDF; can occur multiple times\n";
	echo "  -P <filenameAttachPDF>: upload a pdf for an attachment file; can occur multiple times. Must track the filenameAttach parameter. Use 'X' for attachment files that don't have a pdf counterpart.\n";
	echo "  -s <sequence>: set sequence of file\n";
	echo "  -t <mimetype> set mimetype of file manually. Do not do that unless you know\n";
	echo "      what you do. If not set, the mimetype will be determined automatically.\n";
	echo "  -a <attribute=value>: Set a document attribute; can occur multiple times.\n";
	echo "  -A <attribute=value>: Set a version attribute; can occur multiple times.\n";
	echo "  -X <access>\n";
	echo "  -d <timestamp date> A timestamp to set the date of the document to.\n";
	echo "  -Z <content only> Only add a new piece of content.\n";
	echo "  -S If document is obsolete use option -S\n";
} /* }}} */

function getFileInfo($filename) {
	$ret = array();
	$ret['attachFileTmp'] = $filename;
	$finfo = new finfo(FILEINFO_MIME_TYPE);
	$ret['attachFileName'] = basename($filename);
	$ret['fileType'] = "." . pathinfo($filename, PATHINFO_EXTENSION);
	$ret['attachFileType'] = $finfo->file($filename);
	return $ret;
}

$version = "0.0.1";
$shortoptions = "a:A:b:B:c:C:d:f:F:k:K:n:N:q:Q:p:P:s:S:t:u:V:y:X:hvZ";
$longoptions = array('help', 'version', 'config:');
if(false === ($options = getopt($shortoptions, $longoptions))) {
	print "No Options \n";
	usage();
	exit(0);
}

/* Print help and exit */
if(isset($options['h']) || isset($options['help'])) {
	print "Help \n";
	usage();
	exit(0);
}

/* Print version and exit */
if(isset($options['v']) || isset($options['verѕion'])) {
	echo $version."\n";
	exit(0);
}

/* Set alternative config file */
if(isset($options['config'])) {
	$settings = new Settings($options['config']);
} else {
	$settings = new Settings();
}

if(isset($settings->_extraPath))
	ini_set('include_path', $settings->_extraPath. PATH_SEPARATOR .ini_get('include_path'));

require_once("/var/www/seeddms5.0/SeedDMS_core/Core.php");

if(isset($options['F'])) {
	$folderid = (int) $options['F'];
} else {
	$folderid = 1;
	//echo "Missing folder ID\n";
	//usage();
	//exit(1);
}

$access = M_READ;
if(isset($options['X'])) {
	$access = intval($options['X']);
}

$date = null;
if(isset($options['d'])) {
	$date = intval($options['d']);
}

$docNumber = null;
if(isset($options['N'])) {
	$docNumber = $options['N'];
}

$comment = '';
if(isset($options['c'])) {
	$comment = $options['c'];
}

$version_comment = '';
if(isset($options['C'])) {
	$version_comment = $options['C'];
}

$keywords = '';
if(isset($options['k'])) {
	$keywords = $options['k'];
}

$sequence = 0;
if(isset($options['s'])) {
	$sequence = $options['s'];
}

$name = '';
if(isset($options['n'])) {
	$name = $options['n'];
}

$username = '';
if(isset($options['u'])) {
	$username = $options['u']; 
}

$filename = '';
if(isset($options['f'])) {
	$filename = $options['f']; 
} else {
	print "No File \n";
	usage();
	exit(1);
}
$origFilename = null;
if(isset($options['b'])) {
	$origFilename = $options['b'];
}
$origFilenamePDF = null;
if(isset($options['B'])) {
	$origFilenamePDF = $options['B'];
}

$status = null;
if(isset($options['S'])) {
	$status = -2;
}

$filenamePDF = '';
$pdfInfo = null; 
if(isset($options['p'])) {
	$filenamePDF = $options['p'];
	$fnPDFInfo = getFileInfo($filenamePDF);
	$pdfInfo['name'] = $origFilenamePDF;
	$pdfInfo['pdfFileTmp'] = $fnPDFInfo['attachFileTmp'];
	$pdfInfo['pdfFileName'] = $fnPDFInfo['attachFileName'];
	$pdfInfo['fileType'] = $fnPDFInfo['fileType'];
	$pdfInfo['pdfFileType'] = $fnPDFInfo['attachFileType'];
}

 /*{
	'file': {
		'attachFileTmp': 
		'attachFileName': 
		'fileType': 
		'attachFileType':
		},
	'pdfFile': {
		'attachFileTmp': 
		'attachFileName': 
		'fileType': 
		'attachFileType':
		}
} */

$origAttachName = null;
if(isset($options['q'])) {
	if(is_array($options['q'])) {
		$origAttachName = $options['q'];
	} else {
		$origAttachName = array($options['q']);
	}
}
$origAttachNamePDF = null;
if(isset($options['Q'])) {
	if(is_array($options['Q'])) {
		$origAttachNamePDF = $options['Q'];
	} else {
		$origAttachNamePDF = array($options['Q']);
	}
}

$attachname = array();
if (isset($options['y'])) {
	if(is_array($options['y'])) {
		$attachname = $options['y'];
	} else {
		$attachname = array($options['y']);
	}
}

$attachpdfname = array();
if (isset($options['P'])) {
	if(is_array($options['P'])) {
		$attachpdfname = $options['P'];
	} else {
		$attachpdfname = array($options['P']);
	}
}

$attachFileData = array();
$attachInfo = array();
for ($i = 0; $i < count($attachname); $i++) {
	$attachInfoFile = array();
	$attachInfoPDF = array();
	$attachInfoFile = getFileInfo($attachname[$i]);
	if($origAttachName) {
		if($origAttachName[$i] != 'X') {
			$attachInfoFile['name'] = $origAttachName[$i];
		} else {
			$attachInfoFile['name'] = null;
		}
	}
	if($attachpdfname[$i]) {
		if($attachpdfname[$i] != 'X') {
			$attachInfoPDF = getFileInfo($attachpdfname[$i]);
			if($origAttachNamePDF) {
				if($origAttachNamePDF[$i] != 'X') {
					$attachInfoPDF['name'] = $origAttachNamePDF[$i];
				} else {
					$attachInfoPDF['name'] = null;
				}
			}
		}
	}
	$attachInfo['file'] = $attachInfoFile;
	$attachInfo['pdfFile'] = $attachInfoPDF;
	$attachFileData[$i] = $attachInfo;
}

$mimetype = '';
if(isset($options['t'])) {
	$mimetype = $options['t'];
}

$reqversion = 0;
if(isset($options['V'])) {
	$reqversion = intval($options['V']);
}
//if($reqversion<1)
//	$reqversion=1;

$db = new SeedDMS_Core_DatabaseAccess($settings->_dbDriver, $settings->_dbHostname, $settings->_dbUser, $settings->_dbPass, $settings->_dbDatabase);
$db->connect() or die ("Could not connect to db-server \"" . $settings->_dbHostname . "\"");
//$db->_conn->debug = 1;


$dms = new SeedDMS_Core_DMS($db, $settings->_contentDir.$settings->_contentOffsetDir);
if(!$dms->checkVersion()) {
	echo "Database update needed.";
	exit;
}

$categories = array();
if(isset($options['K'])) {
	$categorynames = explode(',', $options['K']);
	foreach($categorynames as $categoryname) {
		$cat = $dms->getDocumentCategoryByName($categoryname);
		if($cat) {
			$categories[] = $cat;
		} else {
			echo "Category '".$categoryname."' not found\n";
		}
	}
}

/* Parse document attributes.  */
$document_attributes = array();
if (isset($options['a'])) {
	$docattr = array();
	if (is_array($options['a'])) {
		$docattr = $options['a'];
	} else {
		$docattr = array($options['a']);
	}

	foreach ($docattr as $thisAttribute) {
		$attrKey = strstr($thisAttribute, '=', true);
		$attrVal = substr(strstr($thisAttribute, '='), 1);
		if (empty($attrKey) || empty($attrVal)) {
			echo "Document attribute $thisAttribute not understood\n";
			exit(1);
		}
		$attrdef = $dms->getAttributeDefinitionByName($attrKey);
		if (!$attrdef) {
			echo "Document attribute $attrKey unknown\n";
			exit(1);
		}
		$document_attributes[$attrdef->getID()] = $attrVal;
	}
}

/* Parse version attributes.  */
$version_attributes = array();
if (isset($options['A'])) {
	$verattr = array();
	if (is_array($options['A'])) {
		$verattr = $options['A'];
	} else {
		$verattr = array($options['A']);
	}

	foreach ($verattr as $thisAttribute) {
		$attrKey = strstr($thisAttribute, '=', true);
		$attrVal = substr(strstr($thisAttribute, '='), 1);
		if (empty($attrKey) || empty($attrVal)) {
			echo "Version attribute $thisAttribute not understood\n";
			exit(1);
		}
		$attrdef = $dms->getAttributeDefinitionByName($attrKey);
		if (!$attrdef) {
			echo "Version attribute $attrKey unknown\n";
			exit(1);
		}
		$version_attributes[$attrdef->getID()] = $attrVal;
	}
}


$dms->setRootFolderID($settings->_rootFolderID);
$dms->setMaxDirID($settings->_maxDirID);
$dms->setEnableConverting($settings->_enableConverting);
$dms->setViewOnlineFileTypes($settings->_viewOnlineFileTypes);

/* Create a global user object */
if($username) {
	if(!($user = $dms->getUserByLogin($username))) {
		echo "No such user '".$username."'.";
		exit;
	}
} else
	$user = $dms->getUser(1);

if(is_readable($filename)) {
	if(filesize($filename)) {
		$filenameInfo = getFileInfo($filename);
	} else {
		echo "File has zero size\n";
		exit(1);
	}
} else {
	echo "File is not readable\n";
	exit(1);
}

$folder = $dms->getFolder($folderid);

if (!is_object($folder)) {
	echo "Could not find specified folder\n";
	exit(1);
}

if ($folder->getAccessMode($user) < M_READWRITE) {
	echo "Not sufficient access rights\n";
	exit(1);
}

if (!is_numeric($sequence)) {
	echo "Sequence must be numeric\n";
	exit(1);
}

//$expires = ($_POST["expires"] == "true") ? mktime(0,0,0, sanitizeString($_POST["expmonth"]), sanitizeString($_POST["expday"]), sanitizeString($_POST["expyear"])) : false;
$expires = false;

if(!$name)
	$name = basename($filename);

$reviewers = array();
$approvers = array();

$documentID = $dms->getDocumentIDByNumber($docNumber);
$res = false;

if(isset($options['Z'])) {
	$document = $dms->getDocument($documentID);
	$content = $document->getContentByVersion($reqversion);
	if ($content == false) {
		/*$comment, $user, $tmpFile, $orgFileName, $fileType, $mimeType, $reviewers=array(), $approvers=array(), $version=0, $attributes=array(), $workflow=null, $pdfData=null, $attachFileData=null, $date=null, $status*/
		$res = $document->addContent($version_comment, $user, $filenameInfo['attachFileTmp'], $filenameInfo['attachFileName'], $filenameInfo['fileType'], $filenameInfo['attachFileType'], $reviewers, $approvers, $reqversion, $version_attributes, null, $pdfInfo, $attachFileData, $date, $status, $origFilename);
	} else {
		echo $docNumber . " Version " . $reqversion . " already added \n";
	}
} else {
	if($documentID == false) {
		/*$name, $comment, $expires, $owner, $keywords, $categories, $tmpFile, $orgFileName, $fileType, $mimeType, $sequence, $reviewers=array(), $approvers=array(),$reqversion=0,$version_comment="", $attributes=array(), $version_attributes=array(), $workflow=null, $docNumber=null, $paradeDoc=1, $pdfData=null, $attachFileData=null, $access=2, $date, $status*/
		$res = $folder->addDocument($name, $comment, $expires, $user, $keywords,
	             					$categories, $filenameInfo['attachFileTmp'], $filenameInfo['attachFileName'],
	                            	$filenameInfo['fileType'], $filenameInfo['attachFileType'], $sequence, $reviewers,
	                            	$approvers, $reqversion, $version_comment,
	                            	$document_attributes, $version_attributes, null, $docNumber, 1, $pdfInfo, $attachFileData, $access, $date, $status, $origFilename);
	} else {
		echo $docNumber . " already added \n";
	}
}
if (is_bool($res[0]) && !$res[0]) {
	echo $res[1];
	exit(1);
}
?>
