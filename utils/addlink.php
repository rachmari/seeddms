<?php
include("/var/www/seeddms5.0/inc/inc.ClassSettings.php");

function usage() { /* {{{ */
    echo "Usage:\n";
    echo "\n";
    echo "Description:\n";
    echo "  This program adds a document link to a Document in the DMS\n";
    echo "\n";
    echo "Options:\n";
    echo "  -h, --help: print usage information and exit.\n";
    echo "  -v, --version: print version and exit.\n";
    echo "  --config: set alternative config file.\n";
    echo "  -n number - required. Document number which cross-refs should be added to.\n";
    echo "  -t target - required. Document number to be linked to. Multiple targets may be provided.\n";
   
} /* }}} */


$shortoptions = "t:n:hv";
$longoptions = array('help', 'version', 'config:');
if(false === ($options = getopt($shortoptions, $longoptions))) {
    usage();
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

$db = new SeedDMS_Core_DatabaseAccess($settings->_dbDriver, $settings->_dbHostname, $settings->_dbUser, $settings->_dbPass, $settings->_dbDatabase);
$db->connect() or die ("Could not connect to db-server \"" . $settings->_dbHostname . "\"");

$dms = new SeedDMS_Core_DMS($db, $settings->_contentDir.$settings->_contentOffsetDir);
if(!$dms->checkVersion()) {
    echo "Database update needed.";
    exit;
}

$docNumber = '';
if(isset($options['n'])) {
    $docNumber = $options['n'];
} else {
    usage();
    exit(1);
}

$targetNumber = '';
if(isset($options['t'])) {
    $targetNumber = $options['t'];
} else {
    usage();
    exit(1);
}


$docID = $dms->getDocIDbyNum($docNumber);
if($docID == false) {
    echo "Cross-reference base document " . $docNumber . " doesn't exist\n";
} else {
    $targetID = $dms->getDocIDbyNum($targetNumber);
    if($targetID == false) {
        echo "Cross-reference for base document " . $docNumber . " to target document " . $targetNumber . " doesn't exist\n";
    } else {
        $document = $dms->getDocument($docID);
        $res = $document->addDocumentLink($targetID, $document->getUser()->getID(), 1);

        if (is_bool($res) && !$res) {
            echo $res;
            exit(1);
        }
    }
}

?>


