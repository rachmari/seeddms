<?php
include("../inc/inc.ClassSettings.php");

function usage() { /* {{{ */
	echo "Usage:\n";
	echo "  seeddms-adddoc [--config <file>] [-c <comment>] [-k <keywords>] [-s <number>] [-n <name>] [-V <version>] [-s <sequence>] [-t <mimetype>] [-a <attribute=value>] [-h] [-v] -F <folder id> -f <filename>\n";
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
	echo "  -s <number>: set sequence for file (used for ordering files within a folder\n";
	echo "  -n <name>: set name of file\n";
	echo "  -V <version>: set version of file (defaults to 1).\n";
	echo "  -u <user>: login name of user\n";
	echo "  -f <filename>: upload this file\n";
	echo "  -s <sequence>: set sequence of file\n";
	echo "  -t <mimetype> set mimetype of file manually. Do not do that unless you know\n";
	echo "      what you do. If not set, the mimetype will be determined automatically.\n";
	echo "  -a <attribute=value>: Set a document attribute; can occur multiple times.\n";
	echo "  -A <attribute=value>: Set a version attribute; can occur multiple times.\n";
} /* }}} */

$version = "0.0.1";
$shortoptions = "F:c:C:k:K:s:V:u:f:n:t:a:A:hv";
$longoptions = array('help', 'version', 'config:');
if(false === ($options = getopt($shortoptions, $longoptions))) {
	usage();
	exit(0);
}

/* Print help and exit */
if(isset($options['h']) || isset($options['help'])) {
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

require_once("SeedDMS/Core.php");

if(isset($options['F'])) {
	$folderid = (int) $options['F'];
} else {
	echo "Missing folder ID\n";
	usage();
	exit(1);
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

$categories = array();
if(isset($options['K'])) {
	$categorynames = explode(',', $options['K']);
	foreach($categorynames as $categoryname) {
		$cat = $dms->getDocumentCategoryByName($categoryname);
		if($cat) {
			$categories[] = $cat->getID();
		} else {
			echo "Category '".$categoryname."' not found\n";
		}
	}
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
	usage();
	exit(1);
}

$mimetype = '';
if(isset($options['t'])) {
	$mimetype = $options['t'];
}

$reqversion = 0;
if(isset($options['V'])) {
	$reqversion = $options['V'];
}
if($reqversion<1)
	$reqversion=1;

$db = new SeedDMS_Core_DatabaseAccess($settings->_dbDriver, $settings->_dbHostname, $settings->_dbUser, $settings->_dbPass, $settings->_dbDatabase);
$db->connect() or die ("Could not connect to db-server \"" . $settings->_dbHostname . "\"");
//$db->_conn->debug = 1;


$dms = new SeedDMS_Core_DMS($db, $settings->_contentDir.$settings->_contentOffsetDir);
if(!$dms->checkVersion()) {
	echo "Database update needed.";
	exit;
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
		$finfo = new finfo(FILEINFO_MIME_TYPE);
		if(!$mimetype) {
			$mimetype = $finfo->file($filename);
		}
		$filetype = "." . pathinfo($filename, PATHINFO_EXTENSION);
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
$filetmp = $filename;

$reviewers = array();
$approvers = array();

$res = $folder->addDocument($name, $comment, $expires, $user, $keywords,
                            $categories, $filetmp, basename($filename),
                            $filetype, $mimetype, $sequence, $reviewers,
                            $approvers, $reqversion, $version_comment,
                            $document_attributes, $version_attributes);

if (is_bool($res) && !$res) {
	echo "Could not add document to folder\n";
	exit(1);
}
?>
