<?php
/**
 * Implementation of DocumentVersionDetail view
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
 * Class which outputs the html page for DocumentVersionDetail view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_DocumentVersionDetail extends SeedDMS_Bootstrap_Style {

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$document = $this->params['document'];
		$version = $this->params['version'];
		$viewonlinefiletypes = $this->params['viewonlinefiletypes'];
		$enableversionmodification = $this->params['enableversionmodification'];
		$cachedir = $this->params['cachedir'];
		$previewwidthdetail = $this->params['previewWidthDetail'];
		$timeout = $this->params['timeout'];

		$latestContent = $document->getLatestContent();
		$status = $version->getStatus();
		$reviewStatus = $version->getReviewStatus();
		$approvalStatus = $version->getApprovalStatus();

		$this->htmlStartPage(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))));
		$this->globalNavigation($folder);
		$this->contentStart();
		$this->pageNavigation($this->getFolderPathHTML($folder, true, $document), "view_document", $document);
		$this->contentHeading(getMLText("document_infos"));
		$this->contentContainerStart();

?>
<table class="table-condensed">
<tr>
<td><?php printMLText("owner");?>:</td>
<td>
<?php
		$owner = $document->getOwner();
		print "<a class=\"infos\" href=\"mailto:".$owner->getEmail()."\">".htmlspecialchars($owner->getFullName())."</a>";
?>
</td>
</tr>
<?php
		if($document->getComment()) {
?>
<tr>
<td><?php printMLText("comment");?>:</td>
<td><?php print htmlspecialchars($document->getComment());?></td>
</tr>
<?php
		}
?>
<tr>
<td><?php printMLText("used_discspace");?>:</td>
<td><?php print SeedDMS_Core_File::format_filesize($document->getUsedDiskSpace());?></td>
</tr>
<tr>
<tr>
<td><?php printMLText("creation_date");?>:</td>
<td><?php print getLongReadableDate($document->getDate()); ?></td>
</tr>
<?php
		if($document->expires()) {
?>
		<tr>
		<td><?php printMLText("expires");?>:</td>
		<td><?php print getReadableDate($document->getExpires()); ?></td>
		</tr>
<?php
		}
		if($document->getKeywords()) {
?>
<tr>
<td><?php printMLText("keywords");?>:</td>
<td><?php print htmlspecialchars($document->getKeywords());?></td>
</tr>
<?php
		}
		if ($document->isLocked()) {
			$lockingUser = $document->getLockingUser();
?>
<tr>
	<td><?php printMLText("lock_status");?>:</td>
	<td><?php printMLText("lock_message", array("email" => $lockingUser->getEmail(), "username" => htmlspecialchars($lockingUser->getFullName())));?></td>
</tr>
<?php
		}
?>
</tr>
<?php
		$attributes = $document->getAttributes();
		if($attributes) {
			foreach($attributes as $attribute) {
				$attrdef = $attribute->getAttributeDefinition();
?>
		    <tr>
					<td><?php echo htmlspecialchars($attrdef->getName()); ?>:</td>
					<td><?php echo htmlspecialchars(implode(', ', $attribute->getValueAsArray())); ?></td>
		    </tr>
<?php
			}
		}
?>
</table>
<?php
		$this->contentContainerEnd();

		// verify if file exists
		$file_exists=file_exists($dms->contentDir . $version->getPath());

		$this->contentHeading(getMLText("details_version", array ("version" => $version->getVersion())));
		$this->contentContainerStart();
		print "<table class=\"table table-condensed\">";
		print "<thead>\n<tr>\n";
		print "<th width='10%'></th>\n";
		print "<th width='30%'>".getMLText("file")."</th>\n";
		print "<th width='25%'>".getMLText("comment")."</th>\n";
		print "<th width='15%'>".getMLText("status")."</th>\n";
		print "<th width='20%'></th>\n";
		print "</tr>\n</thead>\n<tbody>\n";
		print "<tr>\n";
		print "<td><ul class=\"unstyled\">";

		print "</ul>";
		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidthdetail, $timeout);
		$previewer->createPreview($version);
		if($previewer->hasPreview($version)) {
			print("<img class=\"mimeicon\" width=\"".$previewwidthdetail."\" src=\"../op/op.Preview.php?documentid=".$document->getID()."&version=".$version->getVersion()."&width=".$previewwidthdetail."\" title=\"".htmlspecialchars($version->getMimeType())."\">");
		}
		print "</td>\n";

		print "<td><ul class=\"unstyled\">\n";
		print "<li>".$version->getOriginalFileName()."</li>\n";
		print "<li>".getMLText('version').": ".$version->getVersion()."</li>\n";

		if ($file_exists) print "<li>". formatted_size(filesize($dms->contentDir . $version->getPath())) ." ".htmlspecialchars($version->getMimeType())."</li>";
		else print "<li><span class=\"warning\">".getMLText("document_deleted")."</span></li>";

		$updatingUser = $version->getUser();
		print "<li>".getMLText("uploaded_by")." <a href=\"mailto:".$updatingUser->getEmail()."\">".htmlspecialchars($updatingUser->getFullName())."</a></li>";
		print "<li>".getLongReadableDate($version->getDate())."</li>";
		print "</ul></td>\n";

		print "<td>".htmlspecialchars($version->getComment())."</td>";
		print "<td>".getOverallStatusText($status["status"])."</td>";
		print "<td>";

		//if (($document->getAccessMode($user) >= M_READWRITE)) {
		print "<ul class=\"actions unstyled\">";
		if ($file_exists){
			print "<li><a href=\"../op/op.Download.php?documentid=".$document->getID()."&version=".$version->getVersion()."\" title=\"".htmlspecialchars($version->getMimeType())."\"><i class=\"icon-download\"></i> ".getMLText("download")."</a>";
			if ($viewonlinefiletypes && in_array(strtolower($version->getFileType()), $viewonlinefiletypes))
				print "<li><a target=\"_blank\" href=\"../op/op.ViewOnline.php?documentid=".$document->getID()."&version=".$version->getVersion()."\"><i class=\"icon-star\"></i> " . getMLText("view_online") . "</a>";
			print "</ul>";
			print "<ul class=\"actions unstyled\">";
		}

		if (($enableversionmodification && ($document->getAccessMode($user) >= M_READWRITE)) || $user->isAdmin()) {
			print "<li><a href=\"out.RemoveVersion.php?documentid=".$document->getID()."&version=".$version->getVersion()."\"><i class=\"icon-remove\"></i> ".getMLText("rm_version")."</a></li>";
		}
		if (($enableversionmodification && ($document->getAccessMode($user) == M_ALL)) || $user->isAdmin()) {
			if ( $status["status"]==S_RELEASED || $status["status"]==S_OBSOLETE ){
				print "<li><a href='../out/out.OverrideContentStatus.php?documentid=".$document->getID()."&version=".$version->getVersion()."'><i class=\"icon-align-justify\"></i>".getMLText("change_status")."</a></li>";
			}
		}
		if (($enableversionmodification && ($document->getAccessMode($user) >= M_READWRITE)) || $user->isAdmin()) {
			if($status["status"] != S_OBSOLETE)
				print "<li><a href=\"out.EditComment.php?documentid=".$document->getID()."&version=".$version->getVersion()."\"><i class=\"icon-comment\"></i> ".getMLText("edit_comment")."</a></li>";
			if ( $status["status"] == S_DRAFT_REV){
				print "<li><a href=\"out.EditAttributes.php?documentid=".$document->getID()."&version=".$latestContent->getVersion()."\"><i class=\"icon-edit\"></i> ".getMLText("edit_attributes")."</a></li>";
		}
			print "</ul>";
		}
		else {
			print "&nbsp;";
		}

		echo "</td>";
		print "</tr></tbody>\n</table>\n";


		print "<table class=\"table-condensed\">\n";

		if (is_array($reviewStatus) && count($reviewStatus)>0) {

			print "<tr><td colspan=4>\n";
			$this->contentSubHeading(getMLText("reviewers"));
			print "</td></tr>\n";
			
			print "<tr>\n";
			print "<td width='20%'><b>".getMLText("name")."</b></td>\n";
			print "<td width='20%'><b>".getMLText("last_update")."</b></td>\n";
			print "<td width='25%'><b>".getMLText("comment")."</b></td>";
			print "<td width='35%'><b>".getMLText("status")."</b></td>\n";
			print "</tr>\n";

			foreach ($reviewStatus as $r) {
				$required = null;
				switch ($r["type"]) {
					case 0: // Reviewer is an individual.
						$required = $dms->getUser($r["required"]);
						if (!is_object($required)) {
							$reqName = getMLText("unknown_user")." '".$r["required"]."'";
						}
						else {
							$reqName = htmlspecialchars($required->getFullName());
						}
						break;
					case 1: // Reviewer is a group.
						$required = $dms->getGroup($r["required"]);
						if (!is_object($required)) {
							$reqName = getMLText("unknown_group")." '".$r["required"]."'";
						}
						else {
							$reqName = htmlspecialchars($required->getName());
						}
						break;
				}
				print "<tr>\n";
				print "<td>".$reqName."</td>\n";
				print "<td><ul class=\"unstyled\"><li>".$r["date"]."</li>";
				$updateUser = $dms->getUser($r["userID"]);
				print "<li>".(is_object($updateUser) ? $updateUser->getFullName() : "unknown user id '".$r["userID"]."'")."</li></ul></td>";
				print "<td>".$r["comment"]."</td>\n";
				print "<td>".getReviewStatusText($r["status"])."</td>\n";
				print "</tr>\n";
			}
		}

		if (is_array($approvalStatus) && count($approvalStatus)>0) {

			print "<tr><td colspan=4>\n";
			$this->contentSubHeading(getMLText("approvers"));
			print "</td></tr>\n";
				
			print "<tr>\n";
			print "<td width='20%'><b>".getMLText("name")."</b></td>\n";
			print "<td width='20%'><b>".getMLText("last_update")."</b></td>\n";
			print "<td width='25%'><b>".getMLText("comment")."</b></td>";
			print "<td width='35%'><b>".getMLText("status")."</b></td>\n";
			print "</tr>\n";

			foreach ($approvalStatus as $a) {
				$required = null;
				switch ($a["type"]) {
					case 0: // Approver is an individual.
						$required = $dms->getUser($a["required"]);
						if (!is_object($required)) {
							$reqName = getMLText("unknown_user")." '".$r["required"]."'";
						}
						else {
							$reqName = htmlspecialchars($required->getFullName());
						}
						break;
					case 1: // Approver is a group.
						$required = $dms->getGroup($a["required"]);
						if (!is_object($required)) {
							$reqName = getMLText("unknown_group")." '".$r["required"]."'";
						}
						else {
							$reqName = htmlspecialchars($required->getName());
						}
						break;
				}
				print "<tr>\n";
				print "<td>".$reqName."</td>\n";
				print "<td><ul class=\"documentDetail\"><li>".$a["date"]."</li>";
				$updateUser = $dms->getUser($a["userID"]);
				print "<li>".(is_object($updateUser) ? htmlspecialchars($updateUser->getFullName()) : "unknown user id '".$a["userID"]."'")."</li></ul></td>";
				print "<td>".$a["comment"]."</td>\n";
				print "<td>".getApprovalStatusText($a["status"])."</td>\n";
				print "</tr>\n";
			}
		}

		print "</table>\n";

		$this->contentContainerEnd();

		if($user->isAdmin()) {
			$this->contentHeading(getMLText("status"));
			$this->contentContainerStart();
			$statuslog = $version->getStatusLog();
			echo "<table class=\"table table-condensed\"><thead>";
			echo "<th>".getMLText('date')."</th><th>".getMLText('status')."</th><th>".getMLText('user')."</th><th>".getMLText('comment')."</th></tr>\n";
			echo "</thead><tbody>";
			foreach($statuslog as $entry) {
				if($suser = $dms->getUser($entry['userID']))
					$fullname = $suser->getFullName();
				else
					$fullname = "--";
				echo "<tr><td>".$entry['date']."</td><td>".getOverallStatusText($entry['status'])."</td><td>".$fullname."</td><td>".$entry['comment']."</td></tr>\n";
			}
			print "</tbody>\n</table>\n";
			$this->contentContainerEnd();

?>
			<div class="row-fluid">
<?php
			/* Check for an existing review log, even if the workflowmode
			 * is set to traditional_only_approval. There may be old documents
			 * that still have a review log if the workflow mode has been
			 * changed afterwards.
			 */
			if($version->getReviewStatus(10)) {
?>
				<div class="span6">
				<?php $this->printProtocol($version, 'review'); ?>
				</div>
<?php
			}
			if($version->getApprovalStatus(10)) {
?>
				<div class="span6">
				<?php $this->printProtocol($version, 'approval'); ?>
				</div>
<?php
			}
?>
			</div>
<?php
		}
		$this->htmlEndPage();
	} /* }}} */
}
?>
