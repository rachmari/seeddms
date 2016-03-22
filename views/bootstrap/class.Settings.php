<?php
/**
 * Implementation of Settings view
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include parent class
 */
require_once("class.Bootstrap.php");

/**
 * Class which outputs the html page for Settings view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_Settings extends SeedDMS_Bootstrap_Style {

	protected function showTextField($name, $value, $type='', $placeholder='') { /* {{{ */
		if($type != 'password' && strlen($value) > 80)
			echo '<textarea class="input-xxlarge" name="'.$name.'">'.$value.'</textarea>';
		else {
			if(strlen($value) > 40)
				$class = 'input-xxlarge';
			elseif(strlen($value) > 30)
				$class = 'input-xlarge';
			elseif(strlen($value) > 18)
				$class = 'input-large';
			elseif(strlen($value) > 12)
				$class = 'input-medium';
			else
				$class = 'input-small';
			echo '<input '.($type=='password' ? 'type="password"' : 'type="text"').'" class="'.$class.'" name="'.$name.'" value="'.$value.'" placeholder="'.$placeholder.'"/>';
		}
	} /* }}} */

	function js() { /* {{{ */

		header('Content-Type: application/javascript');
?>
		$(document).ready( function() {
			$('#settingstab li a').click(function(event) {
				$('#currenttab').val($(event.currentTarget).data('target').substring(1));
			});

			$('a.sendtestmail').click(function(ev){
				ev.preventDefault();
				$.ajax({url: '../op/op.Ajax.php',
					type: 'GET',
					dataType: "json",
					data: {command: 'testmail'},
					success: function(data) {
						console.log(data);
						noty({
							text: data.msg,
							type: (data.error) ? 'error' : 'success',
							dismissQueue: true,
							layout: 'topRight',
							theme: 'defaultTheme',
							timeout: 1500,
						});
					}
				}); 
			});
		});
<?php
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$settings = $this->params['settings'];
		$currenttab = $this->params['currenttab'];

		$this->htmlStartPage(getMLText("admin_tools"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");
		$this->contentHeading(getMLText("settings"));

?>
  <form action="../op/op.Settings.php" method="post" enctype="multipart/form-data" name="form0" >
  <input type="hidden" name="action" value="saveSettings" />
	<input type="hidden" id="currenttab" name="currenttab" value="<?php echo $currenttab ? $currenttab : 'site'; ?>" />
<?php
if(!is_writeable($settings->_configFilePath)) {
	print "<div class=\"alert alert-warning\">";
	echo "<p>".getMLText("settings_notwritable")."</p>";
	print "</div>";
}
?>

  <ul class="nav nav-tabs" id="settingstab">
		<li class="<?php if(!$currenttab || $currenttab == 'site') echo 'active'; ?>"><a data-target="#site" data-toggle="tab"><?php printMLText('settings_Site'); ?></a></li>
	  <li class="<?php if($currenttab == 'system') echo 'active'; ?>"><a data-target="#system" data-toggle="tab"><?php printMLText('settings_System'); ?></a></li>
	  <li class="<?php if($currenttab == 'advanced') echo 'active'; ?>"><a data-target="#advanced" data-toggle="tab"><?php printMLText('settings_Advanced'); ?></a></li>
	  <li class="<?php if($currenttab == 'extensions') echo 'active'; ?>"><a data-target="#extensions" data-toggle="tab"><?php printMLText('settings_Extensions'); ?></a></li>
	</ul>

	<div class="tab-content">
	  <div class="tab-pane <?php if(!$currenttab || $currenttab == 'site') echo 'active'; ?>" id="site">
<?php		$this->contentContainerStart(); ?>
    <table class="table-condensed">
      <!--
        -- SETTINGS - SITE - DISPLAY
      -->
      <tr ><td><b> <?php printMLText("settings_Display");?></b></td> </tr>
      <tr title="<?php printMLText("settings_siteName_desc");?>">
        <td><?php printMLText("settings_siteName");?>:</td>
				<td><?php $this->showTextField('siteName', $settings->_siteName); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_footNote_desc");?>">
        <td><?php printMLText("settings_footNote");?>:</td>
				<td><?php $this->showTextField("footNote", $settings->_footNote); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_printDisclaimer_desc");?>">
        <td><?php printMLText("settings_printDisclaimer");?>:</td>
        <td><input name="printDisclaimer" type="checkbox" <?php if ($settings->_printDisclaimer) echo "checked" ?> /></td>
      </tr>
       <tr title="<?php printMLText("settings_language_desc");?>">
        <td><?php printMLText("settings_language");?>:</td>
        <td>
         <SELECT name="language">
            <?php
              $languages = getLanguages();
              foreach($languages as $language)
              {
                echo '<option value="' . $language . '" ';
                 if ($settings->_language==$language)
                   echo "selected";
                echo '>' . getMLText($language) . '</option>';
             }
            ?>
          </SELECT>
        </td>
      </tr>
      <tr title="<?php printMLText("settings_theme_desc");?>">
        <td><?php printMLText("settings_theme");?>:</td>
        <td>
         <SELECT name="theme">
            <?php
              $themes = UI::getStyles();
              foreach($themes as $theme)
              {
                echo '<option value="' . $theme . '" ';
                 if ($settings->_theme==$theme)
                   echo "selected";
                echo '>' . $theme . '</option>';
             }
            ?>
          </SELECT>
        </td>
      </tr>
      <tr title="<?php printMLText("settings_previewWidthList_desc");?>">
        <td><?php printMLText("settings_previewWidthList");?>:</td>
				<td><?php $this->showTextField("previewWidthList", $settings->_previewWidthList); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_previewWidthDetail_desc");?>">
        <td><?php printMLText("settings_previewWidthDetail");?>:</td>
				<td><?php $this->showTextField("previewWidthDetail", $settings->_previewWidthDetail); ?></td>
      </tr>

      <!--
        -- SETTINGS - SITE - EDITION
      -->
      <tr><td></td></tr><tr ><td><b> <?php printMLText("settings_Edition");?></b></td> </tr>
      <tr title="<?php printMLText("settings_strictFormCheck_desc");?>">
        <td><?php printMLText("settings_strictFormCheck");?>:</td>
        <td><input name="strictFormCheck" type="checkbox" <?php if ($settings->_strictFormCheck) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_viewOnlineFileTypes_desc");?>">
        <td><?php printMLText("settings_viewOnlineFileTypes");?>:</td>
				<td><?php $this->showTextField("viewOnlineFileTypes", $settings->getViewOnlineFileTypesToString()); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_enableConverting_desc");?>">
        <td><?php printMLText("settings_enableConverting");?>:</td>
        <td><input name="enableConverting" type="checkbox" <?php if ($settings->_enableConverting) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_enableEmail_desc");?>">
        <td><?php printMLText("settings_enableEmail");?>:</td>
        <td><input name="enableEmail" type="checkbox" <?php if ($settings->_enableEmail) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_enableUsersView_desc");?>">
        <td><?php printMLText("settings_enableUsersView");?>:</td>
        <td><input name="enableUsersView" type="checkbox" <?php if ($settings->_enableUsersView) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_enableFullSearch_desc");?>">
        <td><?php printMLText("settings_enableFullSearch");?>:</td>
        <td><input name="enableFullSearch" type="checkbox" <?php if ($settings->_enableFullSearch) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_fullSearchEngine_desc");?>">
        <td><?php printMLText("settings_fullSearchEngine");?>:</td>
				<td>
				  <select name="fullSearchEngine">
					  <option value="lucene" <?php if ($settings->_fullSearchEngine=='lucene') echo "selected" ?>><?php printMLText("settings_fullSearchEngine_vallucene");?></option>
						<option value="sqlitefts" <?php if ($settings->_fullSearchEngine=='sqlitefts') echo "selected" ?>><?php printMLText("settings_fullSearchEngine_valsqlitefts");?></option>
					</select>
				</td>
      </tr>
      <tr title="<?php printMLText("settings_defaultSearchMethod_desc");?>">
        <td><?php printMLText("settings_defaultSearchMethod");?>:</td>
				<td>
				  <select name="defaultSearchMethod">
					  <option value="database" <?php if ($settings->_defaultSearchMethod=='database') echo "selected" ?>><?php printMLText("settings_defaultSearchMethod_valdatabase");?></option>
						<option value="fulltext" <?php if ($settings->_defaultSearchMethod=='fulltext') echo "selected" ?>><?php printMLText("settings_defaultSearchMethod_valfulltext");?></option>
					</select>
				</td>
      </tr>
      <tr title="<?php printMLText("settings_stopWordsFile_desc");?>">
        <td><?php printMLText("settings_stopWordsFile");?>:</td>
        <td><?php $this->showTextField("stopWordsFile", $settings->_stopWordsFile); ?></td>
      </tr>
	    <tr title="<?php printMLText("settings_enableClipboard_desc");?>">
        <td><?php printMLText("settings_enableClipboard");?>:</td>
        <td><input name="enableClipboard" type="checkbox" <?php if ($settings->_enableClipboard) echo "checked" ?> /></td>
      </tr>
	    <tr title="<?php printMLText("settings_enableDropUpload_desc");?>">
        <td><?php printMLText("settings_enableDropUpload");?>:</td>
        <td><input name="enableDropUpload" type="checkbox" <?php if ($settings->_enableDropUpload) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_enableFolderTree_desc");?>">
        <td><?php printMLText("settings_enableFolderTree");?>:</td>
        <td><input name="enableFolderTree" type="checkbox" <?php if ($settings->_enableFolderTree) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_expandFolderTree_desc");?>">
        <td><?php printMLText("settings_expandFolderTree");?>:</td>
        <td>
          <SELECT name="expandFolderTree">
            <OPTION VALUE="0" <?php if ($settings->_expandFolderTree==0) echo "SELECTED" ?> ><?php printMLText("settings_expandFolderTree_val0");?></OPTION>
            <OPTION VALUE="1" <?php if ($settings->_expandFolderTree==1) echo "SELECTED" ?> ><?php printMLText("settings_expandFolderTree_val1");?></OPTION>
            <OPTION VALUE="2" <?php if ($settings->_expandFolderTree==2) echo "SELECTED" ?> ><?php printMLText("settings_expandFolderTree_val2");?></OPTION>
          </SELECT>
      </tr>
      <tr title="<?php printMLText("settings_enableRecursiveCount_desc");?>">
        <td><?php printMLText("settings_enableRecursiveCount");?>:</td>
        <td><input name="enableRecursiveCount" type="checkbox" <?php if ($settings->_enableRecursiveCount) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_maxRecursiveCount_desc");?>">
        <td><?php printMLText("settings_maxRecursiveCount");?>:</td>
				<td><?php $this->showTextField("maxRecursiveCount", $settings->_maxRecursiveCount); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_enableLanguageSelector_desc");?>">
        <td><?php printMLText("settings_enableLanguageSelector");?>:</td>
        <td><input name="enableLanguageSelector" type="checkbox" <?php if ($settings->_enableLanguageSelector) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_enableHelp_desc");?>">
        <td><?php printMLText("settings_enableHelp");?>:</td>
        <td><input name="enableHelp" type="checkbox" <?php if ($settings->_enableHelp) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_enableThemeSelector_desc");?>">
        <td><?php printMLText("settings_enableThemeSelector");?>:</td>
        <td><input name="enableThemeSelector" type="checkbox" <?php if ($settings->_enableThemeSelector) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_sortUsersInList_desc");?>">
        <td><?php printMLText("settings_sortUsersInList");?>:</td>
        <td>
          <SELECT name="sortUsersInList">
            <OPTION VALUE="" <?php if ($settings->_sortUsersInList=='') echo "SELECTED" ?> ><?php printMLText("settings_sortUsersInList_val_login");?></OPTION>
            <OPTION VALUE="fullname" <?php if ($settings->_sortUsersInList=='fullname') echo "SELECTED" ?> ><?php printMLText("settings_sortUsersInList_val_fullname");?></OPTION>
          </SELECT>
      </tr>
      <tr title="<?php printMLText("settings_sortFoldersDefault_desc");?>">
        <td><?php printMLText("settings_sortFoldersDefault");?>:</td>
        <td>
          <SELECT name="sortFoldersDefault">
            <OPTION VALUE="u" <?php if ($settings->_sortFoldersDefault=='') echo "SELECTED" ?> ><?php printMLText("settings_sortFoldersDefault_val_unsorted");?></OPTION>
            <OPTION VALUE="s" <?php if ($settings->_sortFoldersDefault=='s') echo "SELECTED" ?> ><?php printMLText("settings_sortFoldersDefault_val_sequence");?></OPTION>
            <OPTION VALUE="n" <?php if ($settings->_sortFoldersDefault=='n') echo "SELECTED" ?> ><?php printMLText("settings_sortFoldersDefault_val_name");?></OPTION>
          </SELECT>
      </tr>

      <!--
        -- SETTINGS - SITE - CALENDAR
      -->
     <tr><td></td></tr><tr ><td><b> <?php printMLText("settings_Calendar");?></b></td> </tr>
      <tr title="<?php printMLText("settings_enableCalendar_desc");?>">
        <td><?php printMLText("settings_enableCalendar");?>:</td>
        <td><input name="enableCalendar" type="checkbox" <?php if ($settings->_enableCalendar) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_calendarDefaultView_desc");?>">
        <td><?php printMLText("settings_calendarDefaultView");?>:</td>
        <td>
          <SELECT name="calendarDefaultView">
            <OPTION VALUE="w" <?php if ($settings->_calendarDefaultView=="w") echo "SELECTED" ?> ><?php printMLText("week_view");?></OPTION>
            <OPTION VALUE="m" <?php if ($settings->_calendarDefaultView=="m") echo "SELECTED" ?> ><?php printMLText("month_view");?></OPTION>
            <OPTION VALUE="y" <?php if ($settings->_calendarDefaultView=="y") echo "SELECTED" ?> ><?php printMLText("year_view");?></OPTION>
          </SELECT>
      </tr>
     <tr title="<?php printMLText("settings_firstDayOfWeek_desc");?>">
        <td><?php printMLText("settings_firstDayOfWeek");?>:</td>
        <td>
          <SELECT name="firstDayOfWeek">
            <OPTION VALUE="0" <?php if ($settings->_firstDayOfWeek=="0") echo "SELECTED" ?> ><?php printMLText("sunday");?></OPTION>
            <OPTION VALUE="1" <?php if ($settings->_firstDayOfWeek=="1") echo "SELECTED" ?> ><?php printMLText("monday");?></OPTION>
            <OPTION VALUE="2" <?php if ($settings->_firstDayOfWeek=="2") echo "SELECTED" ?> ><?php printMLText("tuesday");?></OPTION>
            <OPTION VALUE="3" <?php if ($settings->_firstDayOfWeek=="3") echo "SELECTED" ?> ><?php printMLText("wednesday");?></OPTION>
            <OPTION VALUE="4" <?php if ($settings->_firstDayOfWeek=="4") echo "SELECTED" ?> ><?php printMLText("thursday");?></OPTION>
            <OPTION VALUE="5" <?php if ($settings->_firstDayOfWeek=="5") echo "SELECTED" ?> ><?php printMLText("friday");?></OPTION>
            <OPTION VALUE="6" <?php if ($settings->_firstDayOfWeek=="6") echo "SELECTED" ?> ><?php printMLText("saturday");?></OPTION>
          </SELECT>
      </tr>
    </table>
<?php		$this->contentContainerEnd(); ?>
  </div>

	  <div class="tab-pane <?php if($currenttab == 'system') echo 'active'; ?>" id="system">
<?php		$this->contentContainerStart(); ?>
    <table class="table-condensed">
     <!--
        -- SETTINGS - SYSTEM - SERVER
      -->
      <tr ><td><b> <?php printMLText("settings_Server");?></b></td> </tr>
      <tr title="<?php printMLText("settings_rootDir_desc");?>">
        <td><?php printMLText("settings_rootDir");?>:</td>
        <td><?php $this->showTextField("rootDir", $settings->_rootDir); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_httpRoot_desc");?>">
        <td><?php printMLText("settings_httpRoot");?>:</td>
        <td><?php $this->showTextField("httpRoot", $settings->_httpRoot); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_contentDir_desc");?>">
        <td><?php printMLText("settings_contentDir");?>:</td>
        <td><?php $this->showTextField("contentDir", $settings->_contentDir); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_cacheDir_desc");?>">
        <td><?php printMLText("settings_cacheDir");?>:</td>
        <td><?php $this->showTextField("cacheDir", $settings->_cacheDir); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_stagingDir_desc");?>">
        <td><?php printMLText("settings_stagingDir");?>:</td>
        <td><?php $this->showTextField("stagingDir", $settings->_stagingDir); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_luceneDir_desc");?>">
        <td><?php printMLText("settings_luceneDir");?>:</td>
        <td><?php $this->showTextField("luceneDir", $settings->_luceneDir); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_dropFolderDir_desc");?>">
        <td><?php printMLText("settings_dropFolderDir");?>:</td>
        <td><?php $this->showTextField("dropFolderDir", $settings->_dropFolderDir); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_logFileEnable_desc");?>">
        <td><?php printMLText("settings_logFileEnable");?>:</td>
        <td><input name="logFileEnable" type="checkbox" <?php if ($settings->_logFileEnable) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_logFileRotation_desc");?>">
        <td><?php printMLText("settings_logFileRotation");?>:</td>
        <td>
          <SELECT name="logFileRotation">
            <OPTION VALUE="h" <?php if ($settings->_logFileRotation=="h") echo "SELECTED" ?> ><?php printMLText("hourly");?></OPTION>
            <OPTION VALUE="d" <?php if ($settings->_logFileRotation=="d") echo "SELECTED" ?> ><?php printMLText("daily");?></OPTION>
            <OPTION VALUE="m" <?php if ($settings->_logFileRotation=="m") echo "SELECTED" ?> ><?php printMLText("monthly");?></OPTION>
          </SELECT>
      </tr>
      <tr title="<?php printMLText("settings_enableLargeFileUpload_desc");?>">
        <td><?php printMLText("settings_enableLargeFileUpload");?>:</td>
        <td><input name="enableLargeFileUpload" type="checkbox" <?php if ($settings->_enableLargeFileUpload) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_partitionSize_desc");?>">
        <td><?php printMLText("settings_partitionSize");?>:</td>
        <td><?php $this->showTextField("partitionSize", $settings->_partitionSize); ?></td>
      </tr>
      <!--
        -- SETTINGS - SYSTEM - AUTHENTICATION
      -->
      <tr ><td><b> <?php printMLText("settings_Authentication");?></b></td> </tr>
      <tr title="<?php printMLText("settings_enableGuestLogin_desc");?>">
        <td><?php printMLText("settings_enableGuestLogin");?>:</td>
        <td><input name="enableGuestLogin" type="checkbox" <?php if ($settings->_enableGuestLogin) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_enableGuestAutoLogin_desc");?>">
        <td><?php printMLText("settings_enableGuestAutoLogin");?>:</td>
        <td><input name="enableGuestAutoLogin" type="checkbox" <?php if ($settings->_enableGuestAutoLogin) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_restricted_desc");?>">
        <td><?php printMLText("settings_restricted");?>:</td>
        <td><input name="restricted" type="checkbox" <?php if ($settings->_restricted) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_enableUserImage_desc");?>">
        <td><?php printMLText("settings_enableUserImage");?>:</td>
        <td><input name="enableUserImage" type="checkbox" <?php if ($settings->_enableUserImage) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_disableSelfEdit_desc");?>">
        <td><?php printMLText("settings_disableSelfEdit");?>:</td>
        <td><input name="disableSelfEdit" type="checkbox" <?php if ($settings->_disableSelfEdit) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_enablePasswordForgotten_desc");?>">
        <td><?php printMLText("settings_enablePasswordForgotten");?>:</td>
        <td><input name="enablePasswordForgotten" type="checkbox" <?php if ($settings->_enablePasswordForgotten) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_passwordStrength_desc");?>">
        <td><?php printMLText("settings_passwordStrength");?>:</td>
        <td><?php $this->showTextField("passwordStrength", $settings->_passwordStrength); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_passwordStrengthAlgorithm_desc");?>">
        <td><?php printMLText("settings_passwordStrengthAlgorithm");?>:</td>
        <td>
				  <select name="passwordStrengthAlgorithm">
					  <option value="simple" <?php if ($settings->_passwordStrengthAlgorithm=='simple') echo "selected" ?>><?php printMLText("settings_passwordStrengthAlgorithm_valsimple");?></option>
						<option value="advanced" <?php if ($settings->_passwordStrengthAlgorithm=='advanced') echo "selected" ?>><?php printMLText("settings_passwordStrengthAlgorithm_valadvanced");?></option>
					</select>
				</td>
      </tr>
      <tr title="<?php printMLText("settings_passwordExpiration_desc");?>">
        <td><?php printMLText("settings_passwordExpiration");?>:</td>
        <td><?php $this->showTextField("passwordExpiration", $settings->_passwordExpiration); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_passwordHistory_desc");?>">
        <td><?php printMLText("settings_passwordHistory");?>:</td>
        <td><?php $this->showTextField("passwordHistory", $settings->_passwordHistory); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_loginFailure_desc");?>">
        <td><?php printMLText("settings_loginFailure");?>:</td>
        <td><?php $this->showTextField("loginFailure", $settings->_loginFailure); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_autoLoginUser_desc");?>">
        <td><?php printMLText("settings_autoLoginUser");?>:</td>
        <td><?php $this->showTextField("autoLoginUser", $settings->_autoLoginUser); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_quota_desc");?>">
        <td><?php printMLText("settings_quota");?>:</td>
        <td><?php $this->showTextField("quota", $settings->_quota); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_undelUserIds_desc");?>">
        <td><?php printMLText("settings_undelUserIds");?>:</td>
        <td><?php $this->showTextField("undelUserIds", $settings->_undelUserIds); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_encryptionKey_desc");?>">
        <td><?php printMLText("settings_encryptionKey");?>:</td>
        <td><?php $this->showTextField("encryptionKey", $settings->_encryptionKey); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_cookieLifetime_desc");?>">
        <td><?php printMLText("settings_cookieLifetime");?>:</td>
        <td><?php $this->showTextField("cookieLifetime", $settings->_cookieLifetime); ?></td>
      </tr>

      <!-- TODO Connectors -->

     <!--
        -- SETTINGS - SYSTEM - DATABASE
      -->
      <tr ><td><b> <?php printMLText("settings_Database");?></b></td> </tr>
      <tr title="<?php printMLText("settings_dbDriver_desc");?>">
        <td><?php printMLText("settings_dbDriver");?>:</td>
        <td><?php $this->showTextField("dbDriver", $settings->_dbDriver); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_dbHostname_desc");?>">
        <td><?php printMLText("settings_dbHostname");?>:</td>
        <td><?php $this->showTextField("dbHostname", $settings->_dbHostname); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_dbDatabase_desc");?>">
        <td><?php printMLText("settings_dbDatabase");?>:</td>
        <td><?php $this->showTextField("dbDatabase", $settings->_dbDatabase); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_dbUser_desc");?>">
        <td><?php printMLText("settings_dbUser");?>:</td>
        <td><?php $this->showTextField("dbUser", $settings->_dbUser); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_dbPass_desc");?>">
        <td><?php printMLText("settings_dbPass");?>:</td>
        <td><?php $this->showTextField("dbPass", $settings->_dbPass, 'password'); ?></td>
      </tr>

     <!--
        -- SETTINGS - SYSTEM - SMTP
      -->
			<tr ><td><b> <?php printMLText("settings_SMTP");?></b></td><td><a class="btn sendtestmail"><?php printMLText('send_test_mail'); ?></a></td> </tr>
      <tr title="<?php printMLText("settings_smtpServer_desc");?>">
        <td><?php printMLText("settings_smtpServer");?>:</td>
        <td><?php $this->showTextField("smtpServer", $settings->_smtpServer); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_smtpPort_desc");?>">
        <td><?php printMLText("settings_smtpPort");?>:</td>
        <td><?php $this->showTextField("smtpPort", $settings->_smtpPort); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_smtpSendFrom_desc");?>">
        <td><?php printMLText("settings_smtpSendFrom");?>:</td>
        <td><?php $this->showTextField("smtpSendFrom", $settings->_smtpSendFrom); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_smtpUser_desc");?>">
        <td><?php printMLText("settings_smtpUser");?>:</td>
        <td><?php $this->showTextField("smtpUser", $settings->_smtpUser); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_smtpPassword_desc");?>">
        <td><?php printMLText("settings_smtpPassword");?>:</td>
        <td><input type="password" name="smtpPassword" value="<?php echo $settings->_smtpPassword ?>" /></td>
      </tr>

    </table>
<?php		$this->contentContainerEnd(); ?>
  </div>

	  <div class="tab-pane <?php if($currenttab == 'advanced') echo 'active'; ?>" id="advanced">
<?php		$this->contentContainerStart(); ?>
    <table class="table-condensed">
      <!--
        -- SETTINGS - ADVANCED - DISPLAY
      -->
      <tr ><td><b> <?php printMLText("settings_Display");?></b></td> </tr>
      <tr title="<?php printMLText("settings_siteDefaultPage_desc");?>">
        <td><?php printMLText("settings_siteDefaultPage");?>:</td>
        <td><?php $this->showTextField("siteDefaultPage", $settings->_siteDefaultPage); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_rootFolderID_desc");?>">
        <td><?php printMLText("settings_rootFolderID");?>:</td>
        <td><?php $this->showTextField("rootFolderID", $settings->_rootFolderID); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_titleDisplayHack_desc");?>">
        <td><?php printMLText("settings_titleDisplayHack");?>:</td>
        <td><input name="titleDisplayHack" type="checkbox" <?php if ($settings->_titleDisplayHack) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_showMissingTranslations_desc");?>">
        <td><?php printMLText("settings_showMissingTranslations");?>:</td>
        <td><input name="showMissingTranslations" type="checkbox" <?php if ($settings->_showMissingTranslations) echo "checked" ?> /></td>
      </tr>

      <!--
        -- SETTINGS - ADVANCED - AUTHENTICATION
      -->
      <tr ><td><b> <?php printMLText("settings_Authentication");?></b></td> </tr>
      <tr title="<?php printMLText("settings_guestID_desc");?>">
        <td><?php printMLText("settings_guestID");?>:</td>
        <td><?php $this->showTextField("guestID", $settings->_guestID); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_adminIP_desc");?>">
        <td><?php printMLText("settings_adminIP");?>:</td>
        <td><?php $this->showTextField("adminIP", $settings->_adminIP); ?></td>
      </tr>

      <!--
        -- SETTINGS - ADVANCED - EDITION
      -->
      <tr ><td><b> <?php printMLText("settings_Edition");?></b></td> </tr>
      <tr title="<?php printMLText("settings_workflowMode_desc");?>">
        <td><?php printMLText("settings_workflowMode");?>:</td>
        <td>
				  <select name="workflowMode">
					  <option value="traditional" <?php if ($settings->_workflowMode=='traditional') echo "selected" ?>><?php printMLText("settings_workflowMode_valtraditional");?></option>
					  <option value="traditional_only_approval" <?php if ($settings->_workflowMode=='traditional_only_approval') echo "selected" ?>><?php printMLText("settings_workflowMode_valtraditional_only_approval");?></option>
						<option value="advanced" <?php if ($settings->_workflowMode=='advanced') echo "selected" ?>><?php printMLText("settings_workflowMode_valadvanced");?></option>
					</select>
				</td>
      </tr>
      <tr title="<?php printMLText("settings_versioningFileName_desc");?>">
        <td><?php printMLText("settings_versioningFileName");?>:</td>
        <td><?php $this->showTextField("versioningFileName", $settings->_versioningFileName); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_presetExpirationDate_desc");?>">
        <td><?php printMLText("settings_presetExpirationDate");?>:</td>
        <td><?php $this->showTextField("presetExpirationDate", $settings->_presetExpirationDate); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_enableAdminRevApp_desc");?>">
        <td><?php printMLText("settings_enableAdminRevApp");?>:</td>
        <td><input name="enableAdminRevApp" type="checkbox" <?php if ($settings->_enableAdminRevApp) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_enableOwnerRevApp_desc");?>">
        <td><?php printMLText("settings_enableOwnerRevApp");?>:</td>
        <td><input name="enableOwnerRevApp" type="checkbox" <?php if ($settings->_enableOwnerRevApp) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_enableSelfRevApp_desc");?>">
        <td><?php printMLText("settings_enableSelfRevApp");?>:</td>
        <td><input name="enableSelfRevApp" type="checkbox" <?php if ($settings->_enableSelfRevApp) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_enableVersionDeletion_desc");?>">
        <td><?php printMLText("settings_enableVersionDeletion");?>:</td>
        <td><input name="enableVersionDeletion" type="checkbox" <?php if ($settings->_enableVersionDeletion) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_enableVersionModification_desc");?>">
        <td><?php printMLText("settings_enableVersionModification");?>:</td>
        <td><input name="enableVersionModification" type="checkbox" <?php if ($settings->_enableVersionModification) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_enableDuplicateDocNames_desc");?>">
        <td><?php printMLText("settings_enableDuplicateDocNames");?>:</td>
        <td><input name="enableDuplicateDocNames" type="checkbox" <?php if ($settings->_enableDuplicateDocNames) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_overrideMimeType_desc");?>">
        <td><?php printMLText("settings_overrideMimeType");?>:</td>
        <td><input name="overrideMimeType" type="checkbox" <?php if ($settings->_overrideMimeType) echo "checked" ?> /></td>
      </tr>

      <!--
        -- SETTINGS - ADVANCED - NOTIFICATION
      -->
      <tr ><td><b> <?php printMLText("settings_Notification");?></b></td> </tr>
      <tr title="<?php printMLText("settings_enableOwnerNotification_desc");?>">
        <td><?php printMLText("settings_enableOwnerNotification");?>:</td>
        <td><input name="enableOwnerNotification" type="checkbox" <?php if ($settings->_enableOwnerNotification) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_enableNotificationAppRev_desc");?>">
        <td><?php printMLText("settings_enableNotificationAppRev");?>:</td>
        <td><input name="enableNotificationAppRev" type="checkbox" <?php if ($settings->_enableNotificationAppRev) echo "checked" ?> /></td>
      </tr>
      <tr title="<?php printMLText("settings_enableNotificationWorkflow_desc");?>">
        <td><?php printMLText("settings_enableNotificationWorkflow");?>:</td>
        <td><input name="enableNotificationWorkflow" type="checkbox" <?php if ($settings->_enableNotificationWorkflow) echo "checked" ?> /></td>
      </tr>

      <!--
        -- SETTINGS - ADVANCED - SERVER
      -->
      <tr ><td><b> <?php printMLText("settings_Server");?></b></td> </tr>
      <tr title="<?php printMLText("settings_coreDir_desc");?>">
        <td><?php printMLText("settings_coreDir");?>:</td>
        <td><?php $this->showTextField("coreDir", $settings->_coreDir); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_luceneClassDir_desc");?>">
        <td><?php printMLText("settings_luceneClassDir");?>:</td>
        <td><?php $this->showTextField("luceneClassDir", $settings->_luceneClassDir); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_extraPath_desc");?>">
        <td><?php printMLText("settings_extraPath");?>:</td>
        <td><?php $this->showTextField("extraPath", $settings->_extraPath); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_contentOffsetDir_desc");?>">
        <td><?php printMLText("settings_contentOffsetDir");?>:</td>
        <td><?php $this->showTextField("contentOffsetDir", $settings->_contentOffsetDir); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_maxDirID_desc");?>">
        <td><?php printMLText("settings_maxDirID");?>:</td>
        <td><?php $this->showTextField("maxDirID", $settings->_maxDirID); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_updateNotifyTime_desc");?>">
        <td><?php printMLText("settings_updateNotifyTime");?>:</td>
        <td><?php $this->showTextField("updateNotifyTime", $settings->_updateNotifyTime); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_maxExecutionTime_desc");?>">
        <td><?php printMLText("settings_maxExecutionTime");?>:</td>
        <td><?php $this->showTextField("maxExecutionTime", $settings->_maxExecutionTime); ?></td>
      </tr>
      <tr title="<?php printMLText("settings_cmdTimeout_desc");?>">
        <td><?php printMLText("settings_cmdTimeout");?>:</td>
        <td><?php $this->showTextField("cmdTimeout", $settings->_cmdTimeout); ?></td>
      </tr>

      <tr ><td><b> <?php printMLText("index_converters");?></b></td> </tr>
<?php
	foreach($settings->_converters['fulltext'] as $mimetype=>$cmd) {
?>
      <tr title="<?php echo $mimetype;?>">
        <td><?php echo $mimetype;?>:</td>
        <td><?php $this->showTextField("converters[".$mimetype."]", htmlspecialchars($cmd)); ?></td>
      </tr>
<?php
	}
?>
      <tr title="">
        <td><?php $this->showTextField("converters_newmimetype", ""); ?></td>
        <td><?php $this->showTextField("converters_newcmd", ""); ?></td>
      </tr>
    </table>
<?php		$this->contentContainerEnd(); ?>
  </div>

	  <div class="tab-pane <?php if($currenttab == 'extensions') echo 'active'; ?>" id="extensions">
<?php		$this->contentContainerStart(); ?>
    <table class="table-condensed">
      <!--
        -- SETTINGS - ADVANCED - DISPLAY
      -->
<?php
				foreach($GLOBALS['EXT_CONF'] as $extname=>$extconf) {
?>
      <tr ><td><b><?php echo $extconf['title'];?></b></td></tr>
<?php
					foreach($extconf['config'] as $confkey=>$conf) {
?>
      <tr title="<?php echo $extconf['title'];?>">
        <td><?php echo $conf['title'];?>:</td><td>
<?php
						switch($conf['type']) {
							case 'checkbox':
?>
        <input type="checkbox" name="<?php echo "extensions[".$extname."][".$confkey."]"; ?>" value="1" <?php if(isset($settings->_extensions[$extname][$confkey]) && $settings->_extensions[$extname][$confkey]) echo 'checked'; ?> />
<?php
								break;
							default:
?>
        <input type="text" name="<?php echo "extensions[".$extname."][".$confkey."]"; ?>" title="<?php echo isset($conf['help']) ? $conf['help'] : ''; ?>" value="<?php if(isset($settings->_extensions[$extname][$confkey])) echo $settings->_extensions[$extname][$confkey]; ?>" size="<?php echo $conf['size']; ?>" />
<?php
						}
?>
      </td></tr>
<?php
					}
				}
?>
		</table>
	</div>
  </div>
<?php
if(is_writeable($settings->_configFilePath)) {
?>
  <button type="submit" class="btn"><i class="icon-save"></i> <?php printMLText("save")?></button>
<?php
}
?>
	</form>


<?php
		$this->htmlEndPage();
	} /* }}} */
}
?>
