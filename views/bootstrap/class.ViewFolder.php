<?php
/**
 * Implementation of ViewFolder view
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
 * Class which outputs the html page for ViewFolder view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_ViewFolder extends SeedDMS_Bootstrap_Style {

	function getAccessModeText($defMode) { /* {{{ */
		switch($defMode) {
			case M_NONE:
				return getMLText("access_mode_none");
				break;
			case M_READ:
				return getMLText("access_mode_read");
				break;
			case M_READWRITE:
				return getMLText("access_mode_readwrite");
				break;
			case M_ALL:
				return getMLText("access_mode_all");
				break;
		}
	} /* }}} */

	function printAccessList($obj) { /* {{{ */
		$accessList = $obj->getAccessList();
		if (count($accessList["users"]) == 0 && count($accessList["groups"]) == 0)
			return;

		for ($i = 0; $i < count($accessList["groups"]); $i++)
		{
			$group = $accessList["groups"][$i]->getGroup();
			$accesstext = $this->getAccessModeText($accessList["groups"][$i]->getMode());
			print $accesstext.": ".htmlspecialchars($group->getName());
			if ($i+1 < count($accessList["groups"]) || count($accessList["users"]) > 0)
				print "<br />";
		}
		for ($i = 0; $i < count($accessList["users"]); $i++)
		{
			$user = $accessList["users"][$i]->getUser();
			$accesstext = $this->getAccessModeText($accessList["users"][$i]->getMode());
			print $accesstext.": ".htmlspecialchars($user->getFullName());
			if ($i+1 < count($accessList["users"]))
				print "<br />";
		}
	} /* }}} */

	function js() { /* {{{ */
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$orderby = $this->params['orderby'];
		$expandFolderTree = $this->params['expandFolderTree'];
		$enableDropUpload = $this->params['enableDropUpload'];

		header('Content-Type: application/javascript');
?>
		function folderSelected(id, name) {
			window.location = '../out/out.ViewFolder.php?folderid=' + id;
		}
<?php
		$this->printNewTreeNavigationJs($folder->getID(), M_READ, 0, '', $expandFolderTree == 2, $orderby);

		if ($enableDropUpload && $folder->getAccessMode($user) >= M_READWRITE) {
			echo "SeedDMSUpload.setUrl('../op/op.Ajax.php');";
			echo "SeedDMSUpload.setAbortBtnLabel('".getMLText("cancel")."');";
			echo "SeedDMSUpload.setEditBtnLabel('".getMLText("edit_document_props")."');";
			echo "SeedDMSUpload.setMaxFileSize(".SeedDMS_Core_File::parse_filesize(ini_get("upload_max_filesize")).");";
			echo "SeedDMSUpload.setMaxFileSizeMsg('".getMLText("uploading_maxsize")."');";
		}

		$this->printDeleteFolderButtonJs();
		$this->printDeleteDocumentButtonJs();
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$orderby = $this->params['orderby'];
		$enableFolderTree = $this->params['enableFolderTree'];
		$enableClipboard = $this->params['enableclipboard'];
		$enableDropUpload = $this->params['enableDropUpload'];
		$expandFolderTree = $this->params['expandFolderTree'];
		$showtree = $this->params['showtree'];
		$cachedir = $this->params['cachedir'];
		$workflowmode = $this->params['workflowmode'];
		$enableRecursiveCount = $this->params['enableRecursiveCount'];
		$maxRecursiveCount = $this->params['maxRecursiveCount'];
		$previewwidth = $this->params['previewWidthList'];
		$timeout = $this->params['timeout'];

		$folderid = $folder->getId();

		$this->htmlAddHeader('<script type="text/javascript" src="../styles/'.$this->theme.'/bootbox/bootbox.min.js"></script>'."\n", 'js');

		echo $this->callHook('startPage');
		$this->htmlStartPage(getMLText("folder_title", array("foldername" => htmlspecialchars($folder->getName()))));

		$this->globalNavigation($folder);
		$this->contentStart();
		$txt = $this->callHook('folderMenu', $folder);
		if(is_string($txt))
			echo $txt;
		else {
			$this->pageNavigation($this->getFolderPathHTML($folder), "view_folder", $folder);
		}

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout);

		echo $this->callHook('preContent');

		echo "<div class=\"row-fluid\">\n";

		// dynamic columns - left column removed if no content and right column then fills span12.
		if (!($enableFolderTree || $enableClipboard)) {
			$LeftColumnSpan = 0;
			$RightColumnSpan = 12;
		} else {
			$LeftColumnSpan = 4;
			$RightColumnSpan = 8;
		}
		if ($LeftColumnSpan > 0) {
			echo "<div class=\"span".$LeftColumnSpan."\">\n";
			if ($enableFolderTree) {
				if ($showtree==1){
					$this->contentHeading("<a href=\"../out/out.ViewFolder.php?folderid=". $folderid."&showtree=0\"><i class=\"icon-minus-sign\"></i></a>", true);
					$this->contentContainerStart();
					/*
					 * access expandFolderTree with $this->params because it can
					 * be changed by preContent hook.
					 */
					$this->printNewTreeNavigationHtml($folderid, M_READ, 0, '', $this->params['expandFolderTree'] == 2, $orderby);
					$this->contentContainerEnd();
				} else {
					$this->contentHeading("<a href=\"../out/out.ViewFolder.php?folderid=". $folderid."&showtree=1\"><i class=\"icon-plus-sign\"></i></a>", true);
				}
			}

			echo $this->callHook('leftContent');

			if ($enableClipboard) $this->printClipboard($this->params['session']->getClipboard(), $previewer);

			echo "</div>\n";
		}
		echo "<div class=\"span".$RightColumnSpan."\">\n";

		if ($enableDropUpload && $folder->getAccessMode($user) >= M_READWRITE) {
			echo "<div class=\"row-fluid\">";
			echo "<div class=\"span8\">";
		}
		$txt = $this->callHook('folderInfo', $folder);
		if(is_string($txt))
			echo $txt;
		else {

			$owner = $folder->getOwner();
			$this->contentHeading(getMLText("folder_infos"));
			$this->contentContainerStart();
			echo "<table class=\"table-condensed\">\n";
			if($user->isAdmin()) {
				echo "<tr>";
				echo "<td>".getMLText("id").":</td>\n";
				echo "<td>".htmlspecialchars($folder->getID())."</td>\n";
				echo "</tr>";
			}
			echo "<tr>";
			echo "<td>".getMLText("owner").":</td>\n";
			echo "<td><a href=\"mailto:".htmlspecialchars($owner->getEmail())."\">".htmlspecialchars($owner->getFullName())."</a></td>\n";
			echo "</tr>";
			echo "<tr>";
			echo "<td>".getMLText("creation_date").":</td>";
			echo "<td>".getLongReadableDate($folder->getDate())."</td>";
			echo "</tr>";
			if($folder->getComment()) {
				echo "<tr>";
				echo "<td>".getMLText("comment").":</td>\n";
				echo "<td>".htmlspecialchars($folder->getComment())."</td>\n";
				echo "</tr>";
			}

			if($user->isAdmin()) {
				if($folder->inheritsAccess()) {
					echo "<tr>";
					echo "<td>".getMLText("access_mode").":</td>\n";
					echo "<td>";
					echo getMLText("inherited");
					echo "</tr>";
				} else {
					echo "<tr>";
					echo "<td>".getMLText('default_access').":</td>";
					echo "<td>".$this->getAccessModeText($folder->getDefaultAccess())."</td>";
					echo "</tr>";
					echo "<tr>";
					echo "<td>".getMLText('access_mode').":</td>";
					echo "<td>";
					$this->printAccessList($folder);
					echo "</td>";
					echo "</tr>";
				}
			}
			$attributes = $folder->getAttributes();
			if($attributes) {
				foreach($attributes as $attribute) {
					$arr = $this->callHook('showFolderAttribute', $folder, $attribute);
					if(is_array($arr)) {
						echo $txt;
						echo "<tr>";
						echo "<td>".$arr[0].":</td>";
						echo "<td>".$arr[1].":</td>";
						echo "</tr>";
					} else {
						$attrdef = $attribute->getAttributeDefinition();
			?>
					<tr>
					<td><?php echo htmlspecialchars($attrdef->getName()); ?>:</td>
					<td><?php echo htmlspecialchars(implode(', ', $attribute->getValueAsArray())); ?></td>
					</tr>
<?php
					}
				}
			}
			echo "</table>\n";
			$this->contentContainerEnd();
		}
		if ($enableDropUpload && $folder->getAccessMode($user) >= M_READWRITE) {
			echo "</div>";
			echo "<div class=\"span4\">";
			$this->contentHeading(getMLText("dropupload"), true);
//			$this->addFooterJS("SeedDMSUpload.setUrl('../op/op.Ajax.php');");
//			$this->addFooterJS("SeedDMSUpload.setAbortBtnLabel('".getMLText("cancel")."');");
//			$this->addFooterJS("SeedDMSUpload.setEditBtnLabel('".getMLText("edit_document_props")."');");
//			$this->addFooterJS("SeedDMSUpload.setMaxFileSize(".SeedDMS_Core_File::parse_filesize(ini_get("upload_max_filesize")).");");
//			$this->addFooterJS("SeedDMSUpload.setMaxFileSizeMsg('".getMLText("uploading_maxsize")."');");
?>
<div id="dragandrophandler" class="well alert" data-target="<?php echo $folder->getID(); ?>" data-formtoken="<?php echo createFormKey('adddocument'); ?>"><?php printMLText('drop_files_here'); ?></div>
<?php
			echo "</div>";
			echo "</div>";
		}

		$this->contentHeading(getMLText("folder_contents"));

		$subFolders = $folder->getSubFolders($orderby);
		$subFolders = SeedDMS_Core_DMS::filterAccess($subFolders, $user, M_READ);
		$documents = $folder->getDocuments($orderby);
		$documents = SeedDMS_Core_DMS::filterAccess($documents, $user, M_READ);

		if ((count($subFolders) > 0)||(count($documents) > 0)){
			$txt = $this->callHook('folderListHeader', $folder, $orderby);
			if(is_string($txt))
				echo $txt;
			else {
				print "<table id=\"viewfolder-table\" class=\"table table-condensed\">";
				print "<thead>\n<tr>\n";
				print "<th></th>\n";	
				print "<th><a href=\"../out/out.ViewFolder.php?folderid=". $folderid .($orderby=="n"?"&orderby=s":"&orderby=n")."\">".getMLText("name")."</a></th>\n";
	//			print "<th>".getMLText("owner")."</th>\n";
				print "<th>".getMLText("status")."</th>\n";
	//			print "<th>".getMLText("version")."</th>\n";
				print "<th>".getMLText("action")."</th>\n";
				print "</tr>\n</thead>\n<tbody>\n";
			}
		}
		else printMLText("empty_folder_list");


		foreach($subFolders as $subFolder) {
			$txt = $this->callHook('folderListItem', $subFolder);
			if(is_string($txt))
				echo $txt;
			else {
				echo $this->folderListRow($subFolder);
			}
		}

		foreach($documents as $document) {
			$document->verifyLastestContentExpriry();
			$txt = $this->callHook('documentListItem', $document, $previewer);
			if(is_string($txt))
				echo $txt;
			else {
				echo $this->documentListRow($document, $previewer);
			}
		}

		if ((count($subFolders) > 0)||(count($documents) > 0)) {
			$txt = $this->callHook('folderListFooter', $folder);
			if(is_string($txt))
				echo $txt;
			else
				echo "</tbody>\n</table>\n";
		}

		echo "</div>\n"; // End of right column div

		$this->contentEnd();

		echo $this->callHook('postContent');

		$this->htmlEndPage();
	} /* }}} */
}

?>
