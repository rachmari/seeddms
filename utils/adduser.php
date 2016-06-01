<?php
include("../inc/inc.ClassSettings.php");

function usage() { /* {{{ */
    echo "Usage:\n";
    echo "\n";
    echo "Description:\n";
    echo "  This program adds a user to the DMS\n";
    echo "\n";
    echo "Options:\n";
    echo "  -h, --help: print usage information and exit.\n";
    echo "  -v, --version: print version and exit.\n";
    echo "  --config: set alternative config file.\n";
    echo "  -l login - required\n";
    echo "  -n full name - required\n";
    echo "  -e email - required\n";
    echo "Example: php adduser.php -l 'jack.jill' -n 'Jack Jill' -e 'jack.jill@google.com'";

} /* }}} */


$shortoptions = "e:l:n:hv";
$longoptions = array('help', 'version', 'config:');
if(false === ($options = getopt($shortoptions, $longoptions))) {
    usage();
    exit(0);
}

/* Set alternative config file */
if(isset($options['config'])) {
    $settings = new Settings($options['config']);
} elseif(file_exists('../conf/settings.xml')) {
    $settings = new Settings('../conf/settings.xml');
} else {
    $settings = new Settings();
}

if(isset($settings->_extraPath))
    ini_set('include_path', $settings->_extraPath. PATH_SEPARATOR .ini_get('include_path'));

require_once("../SeedDMS_Core/Core.php");

$db = new SeedDMS_Core_DatabaseAccess($settings->_dbDriver, $settings->_dbHostname, $settings->_dbUser, $settings->_dbPass, $settings->_dbDatabase);
$db->connect() or die ("Could not connect to db-server \"" . $settings->_dbHostname . "\"");

$dms = new SeedDMS_Core_DMS($db, $settings->_contentDir.$settings->_contentOffsetDir);
if(!$dms->checkVersion()) {
    echo "Database update needed.";
    exit;
}

$login = '';
if(isset($options['l'])) {
    $login = $options['l'];
} else {
    usage();
    exit(1);
}

$fullname = '';
if(isset($options['n'])) {
    $fullname = $options['n'];
} else {
    usage();
    exit(1);
}

$email = '';
if(isset($options['e'])) {
    $email = $options['e'];
} else {
    usage();
    exit(1);
}

$user = $dms->getUserByLogin($login);
if (is_bool($user)) {
    $res = $dms->addUser($login, null, $fullname, $email, $settings->_language, $settings->_theme, null);

    if (is_bool($res) && !$res) {
        echo $res;
        exit(1);
    }
} else {
    echo "User already exists " . $login . "\n";
    exit(1);
}


?>


