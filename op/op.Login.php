<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
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
include("../inc/inc.LogInit.php");
include("../inc/inc.Utils.php");
include("../inc/inc.Language.php");
include("../inc/inc.Init.php");
include("../inc/inc.Extension.php");
include("../inc/inc.ClassSession.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.ClassController.php");

include $settings->_rootDir . "languages/" . $settings->_language . "/lang.inc";

function _printMessage($heading, $message)
{
    
    global $theme;
    $view = UI::factory($theme, 'ErrorDlg');
    $view->exitError($heading, $message, true);
    return;
}

$tmp        = explode('.', basename($_SERVER['SCRIPT_FILENAME']));
$controller = Controller::factory($tmp[1]);

if (isset($_REQUEST["sesstheme"]) && strlen($_REQUEST["sesstheme"]) > 0 && is_numeric(array_search($_REQUEST["sesstheme"], UI::getStyles()))) {
    $theme = $_REQUEST["sesstheme"];
}

if (isset($_REQUEST["login"])) {
    $login = $_REQUEST["login"];
    $login = str_replace("*", "", $login);
}

if (!isset($login) || strlen($login) == 0) {
    _printMessage(getMLText("login_error_title"), getMLText("login_not_given") . "\n");
    exit;
}

$pwd = '';
if (isset($_POST['pwd'])) {
    $pwd = (string) $_POST["pwd"];
    if (get_magic_quotes_gpc()) {
        $pwd = stripslashes($pwd);
    }
}

if ($settings->_enableGuestLogin && (int) $settings->_guestID) {
    $guestUser = $dms->getUser((int) $settings->_guestID);
    if ((!isset($pwd) || strlen($pwd) == 0) && ($login != $guestUser->getLogin())) {
        _printMessage(getMLText("login_error_title"), getMLText("login_error_text") . "\n");
        exit;
    }
}

/* Initialy set $user to false. It will contain a valid user record
 * if authentication against ldap succeeds.
 * _ldapHost will only have a value if the ldap connector has been enabled
 */
$user = false;

if (isset($GLOBALS['SEEDDMS_HOOKS']['authentication'])) {
    foreach ($GLOBALS['SEEDDMS_HOOKS']['authentication'] as $authObj) {
        if (method_exists($authObj, 'authenticate')) {
            $user = $authObj->authenticate($dms, $settings, $login, $pwd);
            if (is_object($user))
                $userid = $user->getID();
        }
    }
}

if (is_bool($user)) {
    if (isset($settings->_ldapHost) && strlen($settings->_ldapHost) > 0) {
        if (isset($settings->_ldapPort) && is_int($settings->_ldapPort)) {
            $ds = ldap_connect($settings->_ldapHost, $settings->_ldapPort);
        } else {
            $ds = ldap_connect($settings->_ldapHost);
        }
        
        if (!is_bool($ds)) {
            /* Check if ldap base dn is set, and use ldap server if it is */
            if (isset($settings->_ldapBaseDN)) {
                $ldapSearchAttribut = "uid=";
                $tmpDN              = "uid=" . $login . "," . $settings->_ldapBaseDN;
            }
            
            /* Active directory has a different base dn */
            if (isset($settings->_ldapType)) {
                if ($settings->_ldapType == 1) {
                    $ldapSearchAttribut = "sAMAccountName=";
                    $tmpDN              = $login . '@' . $settings->_ldapAccountDomainName;
                    // Add the following if authentication with an Active Dir doesn't work
                    // See https://sourceforge.net/p/seeddms/discussion/general/thread/19c70d8d/
                    // and http://stackoverflow.com/questions/6222641/how-to-php-ldap-search-to-get-user-ou-if-i-dont-know-the-ou-for-base-dn
                    ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
                }
            }
            
            // Ensure that the LDAP connection is set to use version 3 protocol.
            // Required for most authentication methods, including SASL.
            ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
            
            // try an authenticated/anonymous bind first.
            // If it succeeds, get the DN for the user and use it for an authentication
            // with the users password.
            $bind = false;
            if (isset($settings->_ldapBindDN)) {
                $bind = @ldap_bind($ds, $settings->_ldapBindDN, $settings->_ldapBindPw);
            } else {
                $bind = @ldap_bind($ds);
            }
            $dn = false;
            /* If bind succeed, then get the dn of for the user */
            if ($bind) {
                if (isset($settings->_ldapFilter) && strlen($settings->_ldapFilter) > 0) {
                    $search = ldap_search($ds, $settings->_ldapBaseDN, "(&(" . $ldapSearchAttribut . $login . ")" . $settings->_ldapFilter . ")");
                } else {
                    $search = ldap_search($ds, $settings->_ldapBaseDN, $ldapSearchAttribut . $login);
                }
                if (!is_bool($search)) {
                    $info = ldap_get_entries($ds, $search);
                    if (!is_bool($info) && $info["count"] > 0) {
                        $dn = $info[0]['dn'];
                    }
                }
            }
            
            /* If the previous bind failed, try it with the users creditionals
             * by simply setting $dn to a default string
             */
            if (is_bool($dn)) {
                $dn = $tmpDN;
            }
            
            /* No do the actual authentication of the user */
            $bind = @ldap_bind($ds, $dn, $pwd);
            if ($bind) {
                // Successfully authenticated. Now check to see if the user exists within
                // the database. If not, add them in if _restricted is not set,
                // but do not add their password.
                $user = $dms->getUserByLogin($login);
                if (is_bool($user) && !$settings->_restricted) {
                    // Retrieve the user's LDAP information.
                    if (isset($settings->_ldapFilter) && strlen($settings->_ldapFilter) > 0) {
                        $search = ldap_search($ds, $settings->_ldapBaseDN, "(&(" . $ldapSearchAttribut . $login . ")" . $settings->_ldapFilter . ")");
                    } else {
                        $search = ldap_search($ds, $settings->_ldapBaseDN, $ldapSearchAttribut . $login);
                    }
                }
                $bind = @ldap_bind($ds, $dn, $pwd);
                if ($bind) {
                    // Successfully authenticated. Now check to see if the user exists within
                    // the database. If not, add them in, but do not add their password.
                    $user = $dms->getUserByLogin($login);
                    if (is_bool($user) && !$settings->_restricted) {
                        // Retrieve the user's LDAP information.
                        
                        
                        /* new code by doudoux  - TO BE TESTED */
                        $search = ldap_search($ds, $settings->_ldapBaseDN, $ldapSearchAttribut . $login);
                        /* old code */
                        //$search = ldap_search($ds, $dn, "uid=".$login);
                        
                        if (!is_bool($search)) {
                            $info = ldap_get_entries($ds, $search);
                            if (!is_bool($info) && $info["count"] == 1 && $info[0]["count"] > 0) {
                                $user = $dms->addUser($login, null, $info[0]['cn'][0], $info[0]['mail'][0], $settings->_language, $settings->_theme, "");
                            }
                        }
                    }
                    if (!is_bool($user)) {
                        $userid = $user->getID();
                    }
                }
                ldap_close($ds);
            }
        }
    }
}

if (is_bool($user)) {
    //
    // LDAP Authentication did not succeed or is not configured. Try internal
    // authentication system.
    //
    
    // Try to find user with given login.
    $user = $dms->getUserByLogin($login);
    if (!$user) {
        _printMessage(getMLText("login_error_title"), getMLText("login_error_text"));
        exit;
    }
    
    $userid = $user->getID();
    
    if (($userid == $settings->_guestID) && (!$settings->_enableGuestLogin)) {
        _printMessage(getMLText("login_error_title"), getMLText("guest_login_disabled"));
        exit;
    }
    
    // Check if password matches (if not a guest user)
    // Assume that the password has been sent via HTTP POST. It would be careless
    // (and dangerous) for passwords to be sent via GET.
    if (($userid != $settings->_guestID) && (md5($pwd) != $user->getPwd())) {
        _printMessage(getMLText("login_error_title"), getMLText("login_error_text"));
        /* if counting of login failures is turned on, then increment its value */
        if ($settings->_loginFailure) {
            $failures = $user->addLoginFailure();
            if ($failures >= $settings->_loginFailure)
                $user->setDisabled(true);
        }
        exit;
    }
    
    // Check if account is disabled
    if ($user->isDisabled()) {
        _printMessage(getMLText("login_disabled_title"), getMLText("login_disabled_text"));
        exit;
    }
    
    // control admin IP address if required
    // TODO: extend control to LDAP autentication
    if ($user->isAdmin() && ($_SERVER['REMOTE_ADDR'] != $settings->_adminIP) && ($settings->_adminIP != "")) {
        _printMessage(getMLText("login_error_title"), getMLText("invalid_user_id"));
        exit;
    }
    
    /* Clear login failures if login was successful */
    $user->clearLoginFailures();
    
}

// Capture the user's language and theme settings.
if (isset($_REQUEST["lang"]) && strlen($_REQUEST["lang"]) > 0 && is_numeric(array_search($_REQUEST["lang"], getLanguages()))) {
    $lang = $_REQUEST["lang"];
    $user->setLanguage($lang);
} else {
    $lang = $user->getLanguage();
    if (strlen($lang) == 0) {
        $lang = $settings->_language;
        $user->setLanguage($lang);
    }
}
if (isset($_REQUEST["sesstheme"]) && strlen($_REQUEST["sesstheme"]) > 0 && is_numeric(array_search($_REQUEST["sesstheme"], UI::getStyles()))) {
    $sesstheme = $_REQUEST["sesstheme"];
    $user->setTheme($sesstheme);
} else {
    $sesstheme = $user->getTheme();
    if (strlen($sesstheme) == 0) {
        $sesstheme = $settings->_theme;
        $user->setTheme($sesstheme);
    }
}

$session = new SeedDMS_Session($db);

// Delete all sessions that are more than 1 week or the configured
// cookie lifetime old. Probably not the most
// reliable place to put this check -- move to inc.Authentication.php?
if ($settings->_cookieLifetime)
    $lifetime = intval($settings->_cookieLifetime);
else
    $lifetime = 7 * 86400;
if (!$session->deleteByTime($lifetime)) {
    _printMessage(getMLText("login_error_title"), getMLText("error_occured") . ": " . $db->getErrorMsg());
    exit;
}

if (isset($_COOKIE["mydms_session"])) {
    /* This part will never be reached unless the session cookie is kept,
     * but op.Logout.php deletes it. Keeping a session could be a good idea
     * for retaining the clipboard data, but the user id in the session should
     * be set to 0 which is not possible due to foreign key constraints.
     * So for now op.Logout.php will delete the cookie as always
     */
    /* Load session */
    $dms_session = $_COOKIE["mydms_session"];
    if (!$resArr = $session->load($dms_session)) {
        /* Turn off http only cookies if jumploader is enabled */
        setcookie("mydms_session", $dms_session, time() - 3600, $settings->_httpRoot, null, null, !$settings->_enableLargeFileUpload); //delete cookie
        header("Location: " . $settings->_httpRoot . "out/out.Login.php?referuri=" . $refer);
        exit;
    } else {
        $session->updateAccess($dms_session);
        $session->setUser($userid);
    }
} else {
    // Create new session in database
    if (!$id = $session->create(array(
        'userid' => $userid,
        'theme' => $sesstheme,
        'lang' => $lang
    ))) {
        _printMessage(getMLText("login_error_title"), getMLText("error_occured") . ": " . $db->getErrorMsg());
        exit;
    }
    
    // Set the session cookie.
    if ($settings->_cookieLifetime)
        $lifetime = time() + intval($settings->_cookieLifetime);
    else
        $lifetime = 0;
    setcookie("mydms_session", $id, $lifetime, $settings->_httpRoot, null, null, !$settings->_enableLargeFileUpload);
}

// TODO: by the PHP manual: The superglobals $_GET and $_REQUEST  are already decoded.
// Using urldecode() on an element in $_GET or $_REQUEST could have unexpected and dangerous results.

if (isset($_POST["referuri"]) && strlen($_POST["referuri"]) > 0) {
    $referuri = trim(urldecode($_POST["referuri"]));
} else if (isset($_GET["referuri"]) && strlen($_GET["referuri"]) > 0) {
    $referuri = trim(urldecode($_GET["referuri"]));
}

$controller->setParam('user', $user);
$controller->setParam('session', $session);
$controller->run();

add_log_line();

if (isset($referuri) && strlen($referuri) > 0) {
    header("Location: http" . ((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'], 'off') != 0)) ? "s" : "") . "://" . $_SERVER['HTTP_HOST'] . $referuri);
} else {
    header("Location: " . $settings->_httpRoot . (isset($settings->_siteDefaultPage) && strlen($settings->_siteDefaultPage) > 0 ? $settings->_siteDefaultPage : "out/out.ViewFolder.php?folderid=" . ($user->getHomeFolder() ? $user->getHomeFolder() : $settings->_rootFolderID)));
}

//_printMessage(getMLText("login_ok"),
//	"<p><a href='".$settings->_httpRoot.(isset($settings->_siteDefaultPage) && strlen($settings->_siteDefaultPage)>0 ? $settings->_siteDefaultPage : "out/out.ViewFolder.php")."'>".getMLText("continue")."</a></p>");

?>
