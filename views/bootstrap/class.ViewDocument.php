<?php
/**
 * Implementation of ViewDocument view
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
 * Class which outputs the html page for ViewDocument view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_ViewDocument extends SeedDMS_Bootstrap_Style {
	protected function getAccessModeText($defMode) { /* {{{ */
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
	protected function printAccessList($obj) { /* {{{ */
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
	/**
	 * Output a single attribute in the document info section
	 *
	 * @param object $attribute attribute
	 */
	protected function printAttribute($attribute) { /* {{{ */
		$attrdef = $attribute->getAttributeDefinition();
?>
		    <tr>
					<td><?php echo htmlspecialchars($attrdef->getName()); ?>:</td>
					<td>
<?php
		switch($attrdef->getType()) {
		case SeedDMS_Core_AttributeDefinition::type_url:
			$attrs = $attribute->getValueAsArray();
			$tmp = array();
			foreach($attrs as $attr) {
				$tmp[] = '<a href="'.htmlspecialchars($attr).'">'.htmlspecialchars($attr).'</a>';
			}
			echo implode('<br />', $tmp);
			break;
		case SeedDMS_Core_AttributeDefinition::type_email:
			$attrs = $attribute->getValueAsArray();
			$tmp = array();
			foreach($attrs as $attr) {
				$tmp[] = '<a mailto="'.htmlspecialchars($attr).'">'.htmlspecialchars($attr).'</a>';
			}
			echo implode('<br />', $tmp);
			break;
		default:
			echo htmlspecialchars(implode(', ', $attribute->getValueAsArray()));
		}
?>
					</td>
		    </tr>
<?php
	} /* }}} */
	function timelinedata() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$document = $this->params['document'];
		$jsondata = array();
		if($user->isAdmin()) {
			$data = $document->getTimeline();
			foreach($data as $i=>$item) {
				switch($item['type']) {
				case 'add_version':
					$msg = getMLText('timeline_'.$item['type'], array('document'=>htmlspecialchars($item['document']->getName()), 'version'=> $item['version']));
					break;
				case 'add_file':
					$msg = getMLText('timeline_'.$item['type'], array('document'=>htmlspecialchars($item['document']->getName())));
					break;
				case 'status_change':
					$msg = getMLText('timeline_'.$item['type'], array('document'=>htmlspecialchars($item['document']->getName()), 'version'=> $item['version'], 'status'=> getOverallStatusText($item['status'])));
					break;
				default:
					$msg = '???';
				}
				$data[$i]['msg'] = $msg;
			}
			foreach($data as $item) {
				if($item['type'] == 'status_change')
					$classname = $item['type']."_".$item['status'];
				else
					$classname = $item['type'];
				$d = makeTsFromLongDate($item['date']);
				$jsondata[] = array('start'=>date('c', $d)/*$item['date']*/, 'content'=>$item['msg'], 'className'=>$classname);
			}
		}
		header('Content-Type: application/json');
		echo json_encode($jsondata);
	} /* }}} */
	function js() { /* {{{ */
		$document = $this->params['document'];
		header('Content-Type: application/javascript');
		$this->printTimelineJs('out.ViewDocument.php?action=timelinedata&documentid='.$document->getID(), 300, '', date('Y-m-d'));
		$this->printDocumentChooserJs("form1");
	} /* }}} */
	function show() { /* {{{ */
		parent::show();
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$document = $this->params['document'];
		$accessop = $this->params['accessobject'];
		$viewonlinefiletypes = $this->params['viewonlinefiletypes'];
		$enableownerrevapp = $this->params['enableownerrevapp'];
		$workflowmode = $this->params['workflowmode'];
		$cachedir = $this->params['cachedir'];
		$previewwidthlist = $this->params['previewWidthList'];
		$previewwidthdetail = $this->params['previewWidthDetail'];
		$documentid = $document->getId();
		$currenttab = $this->params['currenttab'];
		$timeout = $this->params['timeout'];
		$versions = $document->getContent();
		$this->htmlAddHeader('<link href="../styles/'.$this->theme.'/timeline/timeline.css" rel="stylesheet">'."\n", 'css');
		$this->htmlAddHeader('<script type="text/javascript" src="../styles/'.$this->theme.'/timeline/timeline-min.js"></script>'."\n", 'js');
		$this->htmlAddHeader('<script type="text/javascript" src="../styles/'.$this->theme.'/timeline/timeline-locales.js"></script>'."\n", 'js');
		$this->htmlStartPage(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))));
		$this->globalNavigation($folder);
		$this->contentHeading($document->getDocNum());
		$this->contentStart();
		if ($document->isLocked()) {
			$lockingUser = $document->getLockingUser();
			$txt = $this->callHook('documentIsLocked', $document, $lockingUser);
			if(is_string($txt))
				echo $txt;
			else {
?>
		<div class="alert alert-warning">
			<?php printMLText("lock_message", array("email" => $lockingUser->getEmail(), "username" => htmlspecialchars($lockingUser->getFullName())));?>
		</div>
<?php
			}
		}
		/* Retrieve attached files */
		$files = $document->getDocumentFiles();
		/* Retrieve linked documents */
		$links = $document->getDocumentLinks();
		$links = SeedDMS_Core_DMS::filterDocumentLinks($user, $links);
		/* Retrieve reverse linked documents */
		$reverselinks = $document->getReverseDocumentLinks();
		$reverselinks = SeedDMS_Core_DMS::filterDocumentLinks($user, $reverselinks);
		/* Retrieve latest content */
		$latestContent = $document->getLatestContent();
		/* Retrieve latest pdf */
		$latestPDFContent = $document->getPDFByContent($latestContent);
		$needwkflaction = false;
		if($workflowmode == 'traditional' || $workflowmode == 'traditional_only_approval') {
		} else {
			$workflow = $latestContent->getWorkflow();
			if($workflow) {
				$workflowstate = $latestContent->getWorkflowState();
				$transitions = $workflow->getNextTransitions($workflowstate);
				$needwkflaction = $latestContent->needsWorkflowAction($user);
			}
		}
		if($needwkflaction) {
			$this->infoMsg(getMLText('needs_workflow_action'));
		}
		$status = $latestContent->getStatus();
		$reviewStatus = $latestContent->getReviewStatus();
		$approvalStatus = $latestContent->getApprovalStatus();
?>

<div class="row-fluid">
<?php
		echo "<div class='span9'><h4>".$document->getName()."</h4>";
		echo "<p>" . $document->getComment() . "</p></div>";
		echo "<div class='span3'>";
		//$this->contentHeading(.": ".));
		$txt = $this->callHook('preDocumentInfos', $document);
		if(is_string($txt))
			echo $txt;
		$txt = $this->callHook('documentInfos', $document);
		if(is_string($txt))
			echo $txt;
		else {
?>
		<table class="table-condensed revise-edit-container">
<?php
		if($user->isAdmin()) {
			echo "<tr>";
			echo "<td>".getMLText("id").":\n";
			echo htmlspecialchars($document->getID())."</td>\n";
			echo "</tr>";
		}
		echo "<tr>";
		echo "<td><a class='revise-edit-links' href='../out/out.UpdateDocument.php?documentid=" . htmlspecialchars($document->getID()) . "'><i class='icon-copy'></i>&nbsp;" . getMLText("update_document_version") . "</a></td><tr>";
		echo "<td><a class='revise-edit-links' href='../out/out.EditDocument.php?documentid=" . htmlspecialchars($document->getID()) . "'><i class='icon-edit'></i>&nbsp;" . getMLText("edit_document_info") . "</a></td>";
		echo "</tr>";
?>
		<tr hidden>
		<td><?php printMLText("name");?>:</td>
		<td><?php print htmlspecialchars($document->getName());?></td>
		</tr>
		<tr hidden>
		<td><?php printMLText("owner");?>:</td>
		<td>
<?php
		$owner = $document->getOwner();
		print "<a class=\"infos\" href=\"mailto:".$owner->getEmail()."\">".htmlspecialchars($owner->getFullName())."</a>";
?>
		</td>

<?php
		if($document->getComment()) {
?>
		<tr hidden>
		<td><?php printMLText("comment");?>:</td>
		<td><?php print htmlspecialchars($document->getComment());?></td>
		</tr>
<?php
		}
		
?>
		<tr hidden>
		<td><?php printMLText("used_discspace");?>:</td>
		<td><?php print SeedDMS_Core_File::format_filesize($document->getUsedDiskSpace());?></td>
		</tr>
		<tr hidden>
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
		if($cats = $document->getCategories()) {
?>
		<tr hidden>
		<td><?php printMLText("categories");?>:</td>
		<td>
		<?php
			$ct = array();
			foreach($cats as $cat)
				$ct[] = htmlspecialchars($cat->getName());
			echo implode(', ', $ct);
		?>
		</td>
		</tr></div>
<?php
		}
?>
		<?php
		$attributes = $document->getAttributes();
		if($attributes) {
			foreach($attributes as $attribute) {
				$arr = $this->callHook('showDocumentAttribute', $document, $attribute);
				if(is_array($arr)) {
					echo $txt;
					echo "<tr>";
					echo "<td>".$arr[0].":</td>";
					echo "<td>".$arr[1]."</td>";
					echo "</tr>";
				} else {
					$this->printAttribute($attribute);
				}
			}
		}
?>
		</table>
<?php
		}
		$txt = $this->callHook('postDocumentInfos', $document);
		if(is_string($txt))
			echo $txt;
		$this->contentContainerEnd();
?>
</div>
<div class="row-fluid">
<div class="span12">
    <ul class="nav nav-tabs" id="docinfotab">
		  <li class="<?php if(!$currenttab || $currenttab == 'docinfo') echo 'active'; ?>"><a data-target="#docinfo" data-toggle="tab"><?php printMLText('current_version'); ?></a></li>
			<?php if (count($versions)>1) { ?>
		  <li class="<?php if($currenttab == 'previous') echo 'active'; ?>"><a data-target="#previous" data-toggle="tab"><?php printMLText('previous_versions'); ?></a></li>
<?php
			}
			if($workflowmode == 'traditional' || $workflowmode == 'traditional_only_approval') {
				if((is_array($reviewStatus) && count($reviewStatus)>0) ||
					(is_array($approvalStatus) && count($approvalStatus)>0)) {
?>
		  <li class="<?php if($currenttab == 'revapp') echo 'active'; ?>"><a data-target="#revapp" data-toggle="tab"><?php if($workflowmode == 'traditional') echo getMLText('reviewers')."/"; echo getMLText('approvers'); ?></a></li>
<?php
				}
			} else {
				if($workflow) {
?>
		  <li class="<?php if($currenttab == 'workflow') echo 'active'; ?>"><a data-target="#workflow" data-toggle="tab"><?php echo getMLText('workflow'); ?></a></li>
<?php
				}
			}
?>
		  <li class="<?php if($currenttab == 'links') echo 'active'; ?>"><a data-target="#links" data-toggle="tab"><?php printMLText('linked_documents'); echo (count($links)) ? " (".count($links).")" : ""; ?></a></li>
		</ul>
		<div class="tab-content">
		  <div class="tab-pane <?php if(!$currenttab || $currenttab == 'docinfo') echo 'active'; ?>" id="docinfo">
<?php
		if(!$latestContent) {
			$this->contentContainerStart();
			print getMLText('document_content_missing');
			$this->contentContainerEnd();
			$this->htmlEndPage();
			exit;
		}
		// verify if file exists
		$file_exists=file_exists($dms->contentDir . $latestContent->getPath());
		// If a PDF file exists, get the path and check for existence on server
		if($latestPDFContent) {
			$pdf_file_exists=file_exists($dms->contentDir . $latestPDFContent->getPDFPath());
		}
		$this->contentContainerStart();
		print "<table class=\"table\">";
		print "<thead>\n<tr>\n";
		print "<th width='10%'>".getMLText("version")."</th>\n";
		print "<th width='*'>".getMLText("file")."</th>\n";
		print "<th width='20%'>".getMLText("comment")."</th>\n";
		print "<th width='10%'>".getMLText("status")."</th>\n";
		print "<th width='15%'>".getMLText("source")."</th>\n";
		print "<th width='15%'>".getMLText('pdf')."</th>\n";
		print "</tr></thead><tbody>\n";
		print "<tr>\n";
		print "<td>";
		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidthdetail, $timeout);
		$previewer->createPreview($latestContent);
		if ($file_exists) {
			if ($viewonlinefiletypes && in_array(strtolower($latestContent->getFileType()), $viewonlinefiletypes)) {
				print "<a target=\"_blank\" href=\"../op/op.ViewOnline.php?documentid=".$documentid."&version=". $latestContent->getVersion()."\">";
			} else {
				print "<a href=\"../op/op.Download.php?documentid=".$documentid."&version=".$latestContent->getVersion()."\">";
			}
		}
		print $latestContent->getCustomVersion();
		if ($file_exists) {
			print "</a>";
		}
		print "</td>\n";
		print "<td><ul class=\"actions unstyled\">\n";
		print "<li class=\"wordbreak\">".$latestContent->getOriginalFileName() ."</li>\n";
		if ($file_exists)
			print "<li>". SeedDMS_Core_File::format_filesize($latestContent->getFileSize()) .", ".htmlspecialchars($latestContent->getMimeType())."</li>";
		else print "<li><span class=\"warning\">".getMLText("document_deleted")."</span></li>";
		$updatingUser = $latestContent->getUser();
		print "<li>".getMLText("uploaded_by")." <a href=\"mailto:".$updatingUser->getEmail()."\">".htmlspecialchars($updatingUser->getFullName())."</a></li>";
		print "<li>".getLongReadableDate($latestContent->getDate())."</li>";
		print "</ul>\n";
		print "<ul class=\"actions unstyled\">\n";
		$attributes = $latestContent->getAttributes();
		if($attributes) {
			foreach($attributes as $attribute) {
				$arr = $this->callHook('showDocumentContentAttribute', $latestContent, $attribute);
				if(is_array($arr)) {
					print "<li>".$arr[0].": ".$arr[1]."</li>\n";
				} else {
					$attrdef = $attribute->getAttributeDefinition();
					print "<li>".htmlspecialchars($attrdef->getName()).": ".htmlspecialchars(implode(', ', $attribute->getValueAsArray()))."</li>\n";
				}
			}
		}
		print "</ul>\n";
		print "<td>".htmlspecialchars($latestContent->getComment())."</td>";
		print "<td width='10%'>";
		print getOverallStatusText($status["status"]);
		if ( $status["status"]==S_DRAFT_REV || $status["status"]==S_DRAFT_APP || $status["status"]==S_IN_WORKFLOW || $status["status"]==S_EXPIRED ){
			print "<br><span".($document->hasExpired()?" class=\"warning\" ":"").">".(!$document->getExpires() ? getMLText("does_not_expire") : getMLText("expires").": ".getReadableDate($document->getExpires()))."</span>";
		}
		print "</td>";
		print "<td>";
		print "<ul class=\"unstyled actions\">";
		if ($file_exists){
			print "<li><a href=\"../op/op.Download.php?documentid=".$documentid."&version=".$latestContent->getVersion()."\"><i class=\"icon-download\"></i>".getMLText("download_source")."</a></li>";
			if ($viewonlinefiletypes && in_array(strtolower($latestContent->getFileType()), $viewonlinefiletypes))
				print "<li><a target=\"_blank\" href=\"../op/op.ViewOnline.php?documentid=".$documentid."&version=". $latestContent->getVersion()."\"><i class=\"icon-star\"></i>" . getMLText("view_online") . "</a></li>";
		}
		print "<ul class=\"unstyled actions\">";
		/* Only admin has the right to remove version in any case or a regular
		 * user if enableVersionDeletion is on
		 */
		if($accessop->mayRemoveVersion()) {
			print "<li><a href=\"out.RemoveVersion.php?documentid=".$documentid."&version=".$latestContent->getVersion()."\"><i class=\"icon-remove\"></i>".getMLText("rm_version")."</a></li>";
		}
		if($accessop->mayOverwriteStatus()) {
			print "<li><a href='../out/out.OverrideContentStatus.php?documentid=".$documentid."&version=".$latestContent->getVersion()."'><i class=\"icon-align-justify\"></i>".getMLText("change_status")."</a></li>";
		}
		if($workflowmode == 'traditional' || $workflowmode == 'traditional_only_approval') {
			// Allow changing reviewers/approvals only if not reviewed
			if($accessop->maySetReviewersApprovers()) {
				print "<li><a href='../out/out.SetReviewersApprovers.php?documentid=".$documentid."&version=".$latestContent->getVersion()."'><i class=\"icon-edit\"></i>".getMLText("change_assignments")."</a></li>";
			}
		} else {
			if($accessop->maySetWorkflow()) {
				if(!$workflow) {
					print "<li><a href='../out/out.SetWorkflow.php?documentid=".$documentid."&version=".$latestContent->getVersion()."'><i class=\"icon-random\"></i>".getMLText("set_workflow")."</a></li>";
				}
			}
		}
		if($accessop->mayEditComment()) {
			print "<li><a href=\"out.EditComment.php?documentid=".$documentid."&version=".$latestContent->getVersion()."\"><i class=\"icon-comment\"></i>".getMLText("edit_comment")."</a></li>";
		}
		if($accessop->mayEditAttributes()) {
			print "<li><a href=\"out.EditAttributes.php?documentid=".$documentid."&version=".$latestContent->getVersion()."\"><i class=\"icon-edit\"></i>".getMLText("edit_attributes")."</a></li>";
		}
		print "</ul>";
		print "</td>";
		print "<td>";
		print "<ul class=\"unstyled actions\">";
		if ($pdf_file_exists) {
			print "<li><a href=\"../op/op.Download.php?documentid=".$documentid."&pdf=1&version=".$latestPDFContent->getVersion()."\"><i class=\"icon-download\"></i>".getMLText("download_pdf")."</a></li>";
			if ($viewonlinefiletypes && in_array(strtolower($latestPDFContent->getFileType()), $viewonlinefiletypes))
				print "<li><a target=\"_blank\" href=\"../op/op.ViewOnline.php?documentid=".$documentid."&pdf=1&version=". $latestPDFContent->getVersion()."\"><i class=\"icon-star\"></i>" . getMLText("view_pdf") . "</a></li>";
		}
		print "</ul>";
		print "</td>";
		if (count($files) > 0) {
			// Attachment Listing Begin
			print "<tr><td></td><td colspan='4'><b>".getMLText("attach_file")."</b></td></tr>";
			foreach($files as $file) {
				$file_exists=file_exists($dms->contentDir . $file->getPath());
				$responsibleUser = $latestContent->getUser();
				print "<tr>";
				print "<td>";
				$previewer->createPreview($file, $previewwidthdetail);
				if($file_exists) {
					if ($viewonlinefiletypes && in_array(strtolower($file->getFileType()), $viewonlinefiletypes))
						print "<a target=\"_blank\" href=\"../op/op.ViewOnline.php?documentid=".$documentid."&file=". $file->getID()."\">";
					else
						print "<a href=\"../op/op.Download.php?documentid=".$documentid."&file=".$file->getID()."\">";
				}
				if($file_exists) {
					print "</a>";
				}
				print "</td>";
				print "<td><ul class=\"unstyled\">\n";
				print "<li>".htmlspecialchars($file->getName())."</li>\n";
				print "<li>".htmlspecialchars($file->getOriginalFileName())."</li>\n";
				if ($file_exists)
					print "<li>".SeedDMS_Core_File::format_filesize(filesize($dms->contentDir . $file->getPath())) ." bytes, ".htmlspecialchars($file->getMimeType())."</li>";
				else print "<li>".htmlspecialchars($file->getMimeType())." - <span class=\"warning\">".getMLText("document_deleted")."</span></li>";
				print "<li>".getMLText("uploaded_by")." <a href=\"mailto:".$responsibleUser->getEmail()."\">".htmlspecialchars($responsibleUser->getFullName())."</a></li>";
				print "<li>".getLongReadableDate($file->getDate())."</li>";
				print "<td>".htmlspecialchars($file->getComment())."</td>";
				print "<td></td>";
				print "<td><ul class=\"unstyled actions\">";
				if ($file_exists) {
					print "<li><a href=\"../op/op.Download.php?documentid=".$documentid."&file=".$file->getID()."\"><i class=\"icon-download\"></i>".getMLText('download')."</a>";
					if ($viewonlinefiletypes && in_array(strtolower($file->getFileType()), $viewonlinefiletypes))
						print "<li><a target=\"_blank\" href=\"../op/op.ViewOnline.php?documentid=".$documentid."&file=". $file->getID()."\"><i class=\"icon-star\"></i>" . getMLText("view_online") . "</a></li>";
				} else print "<li><img class=\"mimeicon\" src=\"images/icons/".$this->getMimeIcon($file->getFileType())."\" title=\"".htmlspecialchars($file->getMimeType())."\">";
				echo "</ul><ul class=\"unstyled actions\">";
				print "</ul></td>";
				print "</tr>";
			}
		}
		print "</tbody>\n</table>\n";
		$this->contentContainerEnd(); // Attachment Listing End
		if($user->isAdmin()) {
			$this->contentHeading(getMLText("status"));
			$this->contentContainerStart();
			$statuslog = $latestContent->getStatusLog();
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
			$wkflogs = $latestContent->getWorkflowLog();
			if($wkflogs) {
				$this->contentHeading(getMLText("workflow_summary"));
				$this->contentContainerStart();
				echo "<table class=\"table table-condensed\"><thead>";
				echo "<th>".getMLText('date')."</th><th>".getMLText('action')."</th><th>".getMLText('user')."</th><th>".getMLText('comment')."</th></tr>\n";
				echo "</thead><tbody>";
				foreach($wkflogs as $wkflog) {
					echo "<tr>";
					echo "<td>".$wkflog->getDate()."</td>";
					echo "<td>".$wkflog->getTransition()->getAction()->getName()."</td>";
					$loguser = $wkflog->getUser();
					echo "<td>".$loguser->getFullName()."</td>";
					echo "<td>".$wkflog->getComment()."</td>";
					echo "</tr>";
				}
				print "</tbody>\n</table>\n";
				$this->contentContainerEnd();
			}
		}
?>
		</div>
<?php
		if($workflowmode == 'traditional' || $workflowmode == 'traditional_only_approval') {
			if((is_array($reviewStatus) && count($reviewStatus)>0) ||
				(is_array($approvalStatus) && count($approvalStatus)>0)) {
?>
		  <div class="tab-pane <?php if($currenttab == 'revapp') echo 'active'; ?>" id="revapp">
<?php
		$this->contentContainerstart();
		print "<table class=\"table-condensed\">\n";
		/* Just check fo an exting reviewStatus, even workflow mode is set
		 * to traditional_only_approval. There may be old documents which
		 * are still in S_DRAFT_REV.
		 */
		if (/*$workflowmode != 'traditional_only_approval' &&*/ is_array($reviewStatus) && count($reviewStatus)>0) {
			print "<tr><td colspan=5>\n";
			$this->contentSubHeading(getMLText("reviewers"));
			print "</tr>";
			print "<tr>\n";
			print "<td width='20%'><b>".getMLText("name")."</b></td>\n";
			print "<td width='20%'><b>".getMLText("last_update")."</b></td>\n";
			print "<td width='25%'><b>".getMLText("comment")."</b></td>";
			print "<td width='15%'><b>".getMLText("status")."</b></td>\n";
			print "<td width='20%'></td>\n";
			print "</tr>\n";
			foreach ($reviewStatus as $r) {
				$required = null;
				$is_reviewer = false;
				switch ($r["type"]) {
					case 0: // Reviewer is an individual.
						$required = $dms->getUser($r["required"]);
						if (!is_object($required)) {
							$reqName = getMLText("unknown_user")." '".$r["required"]."'";
						}
						else {
							$reqName = htmlspecialchars($required->getFullName()." (".$required->getLogin().")");
						}
						if($r["required"] == $user->getId() && ($user->getId() != $owner->getId() || $enableownerrevapp == 1))
							$is_reviewer = true;
						break;
					case 1: // Reviewer is a group.
						$required = $dms->getGroup($r["required"]);
						if (!is_object($required)) {
							$reqName = getMLText("unknown_group")." '".$r["required"]."'";
						}
						else {
							$reqName = "<i>".htmlspecialchars($required->getName())."</i>";
							if($required->isMember($user) && ($user->getId() != $owner->getId() || $enableownerrevapp == 1))
								$is_reviewer = true;
						}
						break;
				}
				print "<tr>\n";
				print "<td>".$reqName."</td>\n";
				print "<td><ul class=\"unstyled\"><li>".$r["date"]."</li>";
				/* $updateUser is the user who has done the review */
				$updateUser = $dms->getUser($r["userID"]);
				print "<li>".(is_object($updateUser) ? htmlspecialchars($updateUser->getFullName()." (".$updateUser->getLogin().")") : "unknown user id '".$r["userID"]."'")."</li></ul></td>";
				print "<td>".htmlspecialchars($r["comment"]);
				if($r['file']) {
					echo "<br />";
					echo "<a href=\"../op/op.Download.php?documentid=".$documentid."&reviewlogid=".$r['reviewLogID']."\" class=\"btn btn-mini\"><i class=\"icon-download\"></i> ".getMLText('download')."</a>";
				}
				print "</td>\n";
				print "<td>".getReviewStatusText($r["status"])."</td>\n";
				print "<td><ul class=\"unstyled\">";
				if($accessop->mayReview()) {
					if ($is_reviewer && $r["status"]==0) {
						print "<li><a href=\"../out/out.ReviewDocument.php?documentid=".$documentid."&version=".$latestContent->getVersion()."&reviewid=".$r['reviewID']."\" class=\"btn btn-mini\">".getMLText("add_review")."</a></li>";
					}else if (($updateUser==$user)&&(($r["status"]==1)||($r["status"]==-1))&&(!$document->hasExpired())){
						print "<li><a href=\"../out/out.ReviewDocument.php?documentid=".$documentid."&version=".$latestContent->getVersion()."&reviewid=".$r['reviewID']."\" class=\"btn btn-mini\">".getMLText("edit")."</a></li>";
					}
				}
				print "</ul></td>\n";	
				print "</td>\n</tr>\n";
			}
		}
		if (is_array($approvalStatus) && count($approvalStatus)>0) {
			print "<tr><td colspan=5>\n";
			$this->contentSubHeading(getMLText("approvers"));
			print "</tr>";
			print "<tr>\n";
			print "<td width='20%'><b>".getMLText("name")."</b></td>\n";
			print "<td width='20%'><b>".getMLText("last_update")."</b></td>\n";	
			print "<td width='25%'><b>".getMLText("comment")."</b></td>";
			print "<td width='15%'><b>".getMLText("status")."</b></td>\n";
			print "<td width='20%'></td>\n";
			print "</tr>\n";
			foreach ($approvalStatus as $a) {
				$required = null;
				$is_approver = false;
				switch ($a["type"]) {
					case 0: // Approver is an individual.
						$required = $dms->getUser($a["required"]);
						if (!is_object($required)) {
							$reqName = getMLText("unknown_user")." '".$a["required"]."'";
						}
						else {
							$reqName = htmlspecialchars($required->getFullName()." (".$required->getLogin().")");
						}
						if($a["required"] == $user->getId())
							$is_approver = true;
						break;
					case 1: // Approver is a group.
						$required = $dms->getGroup($a["required"]);
						if (!is_object($required)) {
							$reqName = getMLText("unknown_group")." '".$a["required"]."'";
						}
						else {
							$reqName = "<i>".htmlspecialchars($required->getName())."</i>";
						}
						if($required->isMember($user) && ($user->getId() != $owner->getId() || $enableownerrevapp == 1))
							$is_approver = true;
						break;
				}
				print "<tr>\n";
				print "<td>".$reqName."</td>\n";
				print "<td><ul class=\"unstyled\"><li>".$a["date"]."</li>";
				/* $updateUser is the user who has done the approval */
				$updateUser = $dms->getUser($a["userID"]);
				print "<li>".(is_object($updateUser) ? htmlspecialchars($updateUser->getFullName()." (".$updateUser->getLogin().")") : "unknown user id '".$a["userID"]."'")."</li></ul></td>";	
				print "<td>".htmlspecialchars($a["comment"]);
				if($a['file']) {
					echo "<br />";
					echo "<a href=\"../op/op.Download.php?documentid=".$documentid."&approvelogid=".$a['approveLogID']."\" class=\"btn btn-mini\"><i class=\"icon-download\"></i> ".getMLText('download')."</a>";
				}
				echo "</td>\n";
				print "<td>".getApprovalStatusText($a["status"])."</td>\n";
				print "<td><ul class=\"unstyled\">";
				if($accessop->mayApprove()) {
					if ($is_approver && $a['status'] == 0 /*$status["status"]==S_DRAFT_APP*/) {
						print "<li><a class=\"btn btn-mini\" href=\"../out/out.ApproveDocument.php?documentid=".$documentid."&version=".$latestContent->getVersion()."&approveid=".$a['approveID']."\">".getMLText("add_approval")."</a></li>";
					}else if (($updateUser==$user)&&(($a["status"]==1)||($a["status"]==-1))&&(!$document->hasExpired())){
						print "<li><a class=\"btn btn-mini\" href=\"../out/out.ApproveDocument.php?documentid=".$documentid."&version=".$latestContent->getVersion()."&approveid=".$a['approveID']."\">".getMLText("edit")."</a></li>";
					}
				}
				print "</ul>";
				print "</td>\n";	
				print "</td>\n</tr>\n";
			}
		}
		print "</table>\n";
		$this->contentContainerEnd();
		if($user->isAdmin()) {
?>
			<div class="row-fluid">
<?php
			/* Check for an existing review log, even if the workflowmode
			 * is set to traditional_only_approval. There may be old documents
			 * that still have a review log if the workflow mode has been
			 * changed afterwards.
			 */
			if($latestContent->getReviewStatus(10) /*$workflowmode != 'traditional_only_approval'*/) {
?>
				<div class="span6">
				<?php $this->printProtocol($latestContent, 'review'); ?>
				</div>
<?php
			}
?>
				<div class="span6">
				<?php $this->printProtocol($latestContent, 'approval'); ?>
				</div>
			</div>
<?php
		}
?>
		  </div>
<?php
		}
		} else {
			if($workflow) {
?>
		  <div class="tab-pane <?php if($currenttab == 'workflow') echo 'active'; ?>" id="workflow">
<?php
			$this->contentContainerStart();
			if($user->isAdmin()) {
				if(SeedDMS_Core_DMS::checkIfEqual($workflow->getInitState(), $latestContent->getWorkflowState())) {
					print "<form action=\"../out/out.RemoveWorkflowFromDocument.php\" method=\"post\">".createHiddenFieldWithKey('removeworkflowfromdocument')."<input type=\"hidden\" name=\"documentid\" value=\"".$documentid."\" /><input type=\"hidden\" name=\"version\" value=\"".$latestContent->getVersion()."\" /><button type=\"submit\" class=\"btn\"><i class=\"icon-remove\"></i> ".getMLText('rm_workflow')."</button></form>";
				} else {
					print "<form action=\"../out/out.RewindWorkflow.php\" method=\"post\">".createHiddenFieldWithKey('rewindworkflow')."<input type=\"hidden\" name=\"documentid\" value=\"".$documentid."\" /><input type=\"hidden\" name=\"version\" value=\"".$latestContent->getVersion()."\" /><button type=\"submit\" class=\"btn\"><i class=\"icon-refresh\"></i> ".getMLText('rewind_workflow')."</button></form>";
				}
			}
			echo "<h4>".$workflow->getName()."</h4>";
			if($parentworkflow = $latestContent->getParentWorkflow()) {
				echo "<p>Sub workflow of '".$parentworkflow->getName()."'</p>";
			}
			echo "<div class=\"row-fluid\">";
			echo "<div class=\"span8\">";
			echo "<h5>".getMLText('current_state').": ".$workflowstate->getName()."</h5>";
			echo "<table class=\"table table-condensed\">\n";
			echo "<tr>";
			echo "<td>".getMLText('next_state').":</td>";
			foreach($transitions as $transition) {
				$nextstate = $transition->getNextState();
				echo "<td>".$nextstate->getName()."</td>";
			}
			echo "</tr>";
			echo "<tr>";
			echo "<td>".getMLText('action').":</td>";
			foreach($transitions as $transition) {
				$action = $transition->getAction();
				echo "<td>".getMLText('action_'.strtolower($action->getName()), array(), $action->getName())."</td>";
			}
			echo "</tr>";
			echo "<tr>";
			echo "<td>".getMLText('users').":</td>";
			foreach($transitions as $transition) {
				$transusers = $transition->getUsers();
				echo "<td>";
				foreach($transusers as $transuser) {
					$u = $transuser->getUser();
					echo $u->getFullName();
					if($document->getAccessMode($u) < M_READ) {
						echo " (no access)";
					}
					echo "<br />";
				}
				echo "</td>";
			}
			echo "</tr>";
			echo "<tr>";
			echo "<td>".getMLText('groups').":</td>";
			foreach($transitions as $transition) {
				$transgroups = $transition->getGroups();
				echo "<td>";
				foreach($transgroups as $transgroup) {
					$g = $transgroup->getGroup();
					echo getMLText('at_least_n_users_of_group',
						array("number_of_users" => $transgroup->getNumOfUsers(),
							"group" => $g->getName()));
					if ($document->getGroupAccessMode($g) < M_READ) {
						echo " (no access)";
					}
					echo "<br />";
				}
				echo "</td>";
			}
			echo "</tr>";
			echo "<tr class=\"success\">";
			echo "<td>".getMLText('users_done_work').":</td>";
			foreach($transitions as $transition) {
				echo "<td>";
				if($latestContent->executeWorkflowTransitionIsAllowed($transition)) {
					echo "Done";
				}
				$wkflogs = $latestContent->getWorkflowLog($transition);
				foreach($wkflogs as $wkflog) {
					$loguser = $wkflog->getUser();
					echo $loguser->getFullName()." (";
					$names = array();
					foreach($loguser->getGroups() as $loggroup) {
						$names[] =  $loggroup->getName();
					}
					echo implode(", ", $names);
					echo ") - ";
					echo $wkflog->getDate();
					echo "<br />";
				}
				echo "</td>";
			}
			echo "</tr>";
			echo "<tr>";
			echo "<td></td>";
			foreach($transitions as $transition) {
				echo "<td>";
				if($latestContent->triggerWorkflowTransitionIsAllowed($user, $transition)) {
					$action = $transition->getAction();
					print "<form action=\"../out/out.TriggerWorkflow.php\" method=\"post\">".createHiddenFieldWithKey('triggerworkflow')."<input type=\"hidden\" name=\"documentid\" value=\"".$documentid."\" /><input type=\"hidden\" name=\"version\" value=\"".$latestContent->getVersion()."\" /><input type=\"hidden\" name=\"transition\" value=\"".$transition->getID()."\" /><input type=\"submit\" class=\"btn\" value=\"".getMLText('action_'.strtolower($action->getName()), array(), $action->getName())."\" /></form>";
				}
				echo "</td>";
			}
			echo "</tr>";
			echo "</table>";
			$workflows = $dms->getAllWorkflows();
			if($workflows) {
				$subworkflows = array();
				foreach($workflows as $wkf) {
					if($wkf->getInitState()->getID() == $workflowstate->getID()) {
						if($workflow->getID() != $wkf->getID()) {
							$subworkflows[] = $wkf;
						}
					}
				}
				if($subworkflows) {
					echo "<form action=\"../out/out.RunSubWorkflow.php\" method=\"post\">".createHiddenFieldWithKey('runsubworkflow')."<input type=\"hidden\" name=\"documentid\" value=\"".$documentid."\" /><input type=\"hidden\" name=\"version\" value=\"".$latestContent->getVersion()."\" />";
					echo "<select name=\"subworkflow\">";
					foreach($subworkflows as $subworkflow) {
						echo "<option value=\"".$subworkflow->getID()."\">".$subworkflow->getName()."</option>";
					}
					echo "</select>";
					echo "<label class=\"inline\">";
					echo "<input type=\"submit\" class=\"btn\" value=\"".getMLText('run_subworkflow')."\" />";
					echo "</lable>";
					echo "</form>";
				}
			}
			/* If in a sub workflow, the check if return the parent workflow
			 * is possible.
			 */
			if($parentworkflow = $latestContent->getParentWorkflow()) {
				$states = $parentworkflow->getStates();
				foreach($states as $state) {
					/* Check if the current workflow state is also a state in the
					 * parent workflow
					 */
					if($latestContent->getWorkflowState()->getID() == $state->getID()) {
						echo "Switching from sub workflow '".$workflow->getName()."' into state ".$state->getName()." of parent workflow '".$parentworkflow->getName()."' is possible<br />";
						/* Check if the transition from the state where the sub workflow
						 * starts into the current state is also allowed in the parent
						 * workflow. Checking at this point is actually too late, because
						 * the sub workflow shouldn't be entered in the first place,
						 * but that is difficult to check.
						 */
						/* If the init state has not been left, return is always possible */
						if($workflow->getInitState()->getID() == $latestContent->getWorkflowState()->getID()) {
							echo "Initial state of sub workflow has not been left. Return to parent workflow is possible<br />";
							echo "<form action=\"../out/out.ReturnFromSubWorkflow.php\" method=\"post\">".createHiddenFieldWithKey('returnfromsubworkflow')."<input type=\"hidden\" name=\"documentid\" value=\"".$documentid."\" /><input type=\"hidden\" name=\"version\" value=\"".$latestContent->getVersion()."\" />";
							echo "<input type=\"submit\" class=\"btn\" value=\"".getMLText('return_from_subworkflow')."\" />";
							echo "</form>";
						} else {
							/* Get a transition from the last state in the parent workflow
							 * (which is the initial state of the sub workflow) into
							 * current state.
							 */
							echo "Check for transition from ".$workflow->getInitState()->getName()." into ".$latestContent->getWorkflowState()->getName()." is possible in parentworkflow ".$parentworkflow->getID()."<br />";
							$transitions = $parentworkflow->getTransitionsByStates($workflow->getInitState(), $latestContent->getWorkflowState());
							if($transitions) {
								echo "Found transitions in workflow ".$parentworkflow->getID()."<br />";
								foreach($transitions as $transition) {
									if($latestContent->triggerWorkflowTransitionIsAllowed($user, $transition)) {
										echo "Triggering transition is allowed<br />";
										echo "<form action=\"../out/out.ReturnFromSubWorkflow.php\" method=\"post\">".createHiddenFieldWithKey('returnfromsubworkflow')."<input type=\"hidden\" name=\"documentid\" value=\"".$documentid."\" /><input type=\"hidden\" name=\"version\" value=\"".$latestContent->getVersion()."\" /><input type=\"hidden\" name=\"transition\" value=\"".$transition->getID()."\" />";
										echo "<input type=\"submit\" class=\"btn\" value=\"".getMLText('return_from_subworkflow')."\" />";
										echo "</form>";
									}
								}
							}
						}
					}
				}
			}
			echo "</div>";
			echo "</div>";
			$this->contentContainerEnd();
?>
		  </div>
<?php
			}
		}
		if (count($versions)>1) {
?>
		  <div class="tab-pane <?php if($currenttab == 'previous') echo 'active'; ?>" id="previous">
<?php
			$first_loop = 1;
			for ($i = count($versions)-2; $i >= 0; $i--) {
				$this->contentContainerStart();
				print "<table class=\"table\">";
				print "<thead>\n<tr>\n";
				if($first_loop) {
					print "<th width='10%'>".getMLText("version")."</th>\n";
					print "<th width='30%'>".getMLText("file")."</th>\n";
					print "<th width='25%'>".getMLText("comment")."</th>\n";
					print "<th width='15%'>".getMLText("status")."</th>\n";
					print "<th width='20%'></th>\n";
					$first_loop = 0;
				} else {
					print "<th width='10%'></th>\n";
					print "<th width='30%'></th>\n";
					print "<th width='25%'></th>\n";
					print "<th width='15%'></th>\n";
					print "<th width='20%'></th>\n";
				}
				
				print "</tr>\n</thead>\n<tbody>\n";
				$version = $versions[$i];
				$vstat = $version->getStatus();
				$workflow = $version->getWorkflow();
				$workflowstate = $version->getWorkflowState();
				// verify if file exists
				$file_exists=file_exists($dms->contentDir . $version->getPath());
				print "<tr>\n";
				print "<td nowrap>";
				if($file_exists) {
					if ($viewonlinefiletypes && in_array(strtolower($version->getFileType()), $viewonlinefiletypes)) {
							print "<a target=\"_blank\" href=\"../op/op.ViewOnline.php?documentid=".$documentid."&version=".$version->getVersion()."\">";
					} else {
						print "<a href=\"../op/op.Download.php?documentid=".$documentid."&version=".$version->getVersion()."\">";
					}
				}
				$previewer->createPreview($version);
				print $version->getVersion();
				if($file_exists) {
					print "</a>\n";
				}
				print "</td>\n";
				print "<td><ul class=\"unstyled\">\n";
				print "<li>".$version->getOriginalFileName()."</li>\n";
				if ($file_exists) print "<li>". SeedDMS_Core_File::format_filesize($version->getFileSize()) .", ".htmlspecialchars($version->getMimeType())."</li>";
				else print "<li><span class=\"warning\">".getMLText("document_deleted")."</span></li>";
				$updatingUser = $version->getUser();
				print "<li>".getMLText("uploaded_by")." <a href=\"mailto:".$updatingUser->getEmail()."\">".htmlspecialchars($updatingUser->getFullName())."</a></li>";
				print "<li>".getLongReadableDate($version->getDate())."</li>";
				print "</ul>\n";
				print "<ul class=\"actions unstyled\">\n";
				$attributes = $version->getAttributes();
				if($attributes) {
					foreach($attributes as $attribute) {
						$arr = $this->callHook('showDocumentContentAttribute', $version, $attribute);
						if(is_array($arr)) {
							print "<li>".$arr[0].": ".$arr[1]."</li>\n";
						} else {
							$attrdef = $attribute->getAttributeDefinition();
							print "<li>".htmlspecialchars($attrdef->getName()).": ".htmlspecialchars(implode(', ', $attribute->getValueAsArray()))."</li>\n";
						}
					}
				}
				print "</ul>\n";
				print "<td>".htmlspecialchars($version->getComment())."</td>";
				print "<td>".getOverallStatusText($vstat["status"])."</td>";
				print "<td>";
				print "<ul class=\"actions unstyled\">";
				if ($file_exists){
					print "<li><a href=\"../op/op.Download.php?documentid=".$documentid."&version=".$version->getVersion()."\"><i class=\"icon-download\"></i>".getMLText("download")."</a>";
					if ($viewonlinefiletypes && in_array(strtolower($version->getFileType()), $viewonlinefiletypes))
						print "<li><a target=\"_blank\" href=\"../op/op.ViewOnline.php?documentid=".$documentid."&version=".$version->getVersion()."\"><i class=\"icon-star\"></i>" . getMLText("view_online") . "</a>";
					print "</ul>";
					print "<ul class=\"actions unstyled\">";
				}
				/* Only admin has the right to remove version in any case or a regular
				 * user if enableVersionDeletion is on
				 */
				if($accessop->mayRemoveVersion()) {
					print "<li><a href=\"out.RemoveVersion.php?documentid=".$documentid."&version=".$version->getVersion()."\"><i class=\"icon-remove\"></i>".getMLText("rm_version")."</a></li>";
				}
				if($accessop->mayEditComment()) {
					print "<li><a href=\"out.EditComment.php?documentid=".$document->getID()."&version=".$version->getVersion()."\"><i class=\"icon-comment\"></i>".getMLText("edit_comment")."</a></li>";
				}
				if($accessop->mayEditAttributes()) {
					print "<li><a href=\"out.EditAttributes.php?documentid=".$document->getID()."&version=".$latestContent->getVersion()."\"><i class=\"icon-edit\"></i>".getMLText("edit_attributes")."</a></li>";
				}
				print "<li><a href='../out/out.DocumentVersionDetail.php?documentid=".$documentid."&version=".$version->getVersion()."'><i class=\"icon-info-sign\"></i>".getMLText("details")."</a></li>";
				print "</ul>";
				print "</td>\n</tr>\n";
				$ver_files = $document->getFilesByVersion($version);
				if(count($ver_files) > 0) {
					// List attachments for each version
					print "<tr><td></td><td colspan='4'><b>".getMLText("attach_file")."</b></td></tr>";
					foreach($ver_files as $ver_file) {
						$file_exists=file_exists($dms->contentDir . $ver_file->getPath());
						$responsibleUser = $ver_file->getUser();
						print "<tr>";
						print "<td>";
						$previewer->createPreview($ver_file, $previewwidthdetail);
						if($file_exists) {
							if ($viewonlinefiletypes && in_array(strtolower($file->getFileType()), $viewonlinefiletypes))
								print "<a target=\"_blank\" href=\"../op/op.ViewOnline.php?documentid=".$documentid."&file=". $file->getID()."\">";
							else
								print "<a href=\"../op/op.Download.php?documentid=".$documentid."&file=".$file->getID()."\">";
						}
						if($file_exists) {
							print "</a>";
						}
						print "</td>";
						print "<td><ul class=\"unstyled\">\n";
						print "<li>".htmlspecialchars($ver_file->getName())."</li>\n";
						print "<li>".htmlspecialchars($ver_file->getOriginalFileName())."</li>\n";
						if ($file_exists)
							print "<li>".SeedDMS_Core_File::format_filesize(filesize($dms->contentDir . $ver_file->getPath())) ." bytes, ".htmlspecialchars($ver_file->getMimeType())."</li>";
						else print "<li>".htmlspecialchars($ver_file->getMimeType())." - <span class=\"warning\">".getMLText("document_deleted")."</span></li>";
						print "<li>".getMLText("uploaded_by")." <a href=\"mailto:".$responsibleUser->getEmail()."\">".htmlspecialchars($responsibleUser->getFullName())."</a></li>";
						print "<li>".getLongReadableDate($ver_file->getDate())."</li>";
						print "<td>".htmlspecialchars($ver_file->getComment())."</td>";
						print "<td></td><td><ul class=\"unstyled actions\">";
						if ($file_exists) {
							print "<li><a href=\"../op/op.Download.php?documentid=".$documentid."&file=".$ver_file->getID()."\"><i class=\"icon-download\"></i>".getMLText('download')."</a>";
							if ($viewonlinefiletypes && in_array(strtolower($ver_file->getFileType()), $viewonlinefiletypes))
								print "<li><a target=\"_blank\" href=\"../op/op.ViewOnline.php?documentid=".$documentid."&file=". $ver_file->getID()."\"><i class=\"icon-star\"></i>" . getMLText("view_online") . "</a></li>";
						} else print "<li><img class=\"mimeicon\" src=\"images/icons/".$this->getMimeIcon($ver_file->getFileType())."\" title=\"".htmlspecialchars($ver_file->getMimeType())."\">";
						echo "</ul><ul class=\"unstyled actions\">";
						if (($document->getAccessMode($user) == M_ALL)||($ver_file->getUserID()==$user->getID()))
							print "<li><a href=\"out.RemoveDocumentFile.php?documentid=".$documentid."&fileid=".$ver_file->getID()."\"><i class=\"icon-remove\"></i>".getMLText("delete")."</a></li>";
						print "</ul></td>";
						print "</tr>";
					}
				}
			print "</tbody>\n</table>\n";
			$this->contentContainerEnd();
			}
?>
		  </div>
<?php
		}
?>
		  <div class="tab-pane <?php if($currenttab == 'attachments') echo 'active'; ?>" id="attachments">
<?php
		$this->contentContainerStart();
		if (count($files) > 0) {
			print "<table class=\"table\">";
			print "<thead>\n<tr>\n";
			print "<th width='20%'></th>\n";
			print "<th width='20%'>".getMLText("file")."</th>\n";
			print "<th width='40%'>".getMLText("comment")."</th>\n";
			print "<th width='20%'></th>\n";
			print "</tr>\n</thead>\n<tbody>\n";
			foreach($files as $file) {
				$file_exists=file_exists($dms->contentDir . $file->getPath());
				$responsibleUser = $file->getUser();
				print "<tr>";
				print "<td>";
				$previewer->createPreview($file, $previewwidthdetail);
				if($file_exists) {
					if ($viewonlinefiletypes && in_array(strtolower($file->getFileType()), $viewonlinefiletypes))
						print "<a target=\"_blank\" href=\"../op/op.ViewOnline.php?documentid=".$documentid."&file=". $file->getID()."\">";
					else
						print "<a href=\"../op/op.Download.php?documentid=".$documentid."&file=".$file->getID()."\">";
				}
				if($previewer->hasPreview($file)) {
					print("<img class=\"mimeicon\" width=\"".$previewwidthdetail."\" src=\"../op/op.Preview.php?documentid=".$document->getID()."&file=".$file->getID()."&width=".$previewwidthdetail."\" title=\"".htmlspecialchars($file->getMimeType())."\">");
				} else {
					print "<img class=\"mimeicon\" src=\"".$this->getMimeIcon($file->getFileType())."\" title=\"".htmlspecialchars($file->getMimeType())."\">";
				}
				if($file_exists) {
					print "</a>";
				}
				print "</td>";
				print "<td><ul class=\"unstyled\">\n";
				print "<li>".htmlspecialchars($file->getName())."</li>\n";
				print "<li>".htmlspecialchars($file->getOriginalFileName())."</li>\n";
				if ($file_exists)
					print "<li>".SeedDMS_Core_File::format_filesize(filesize($dms->contentDir . $file->getPath())) ." bytes, ".htmlspecialchars($file->getMimeType())."</li>";
				else print "<li>".htmlspecialchars($file->getMimeType())." - <span class=\"warning\">".getMLText("document_deleted")."</span></li>";
				print "<li>".getMLText("uploaded_by")." <a href=\"mailto:".$responsibleUser->getEmail()."\">".htmlspecialchars($responsibleUser->getFullName())."</a></li>";
				print "<li>".getLongReadableDate($file->getDate())."</li>";
				print "<td>".htmlspecialchars($file->getComment())."</td>";
				print "<td><ul class=\"unstyled actions\">";
				if ($file_exists) {
					print "<li><a href=\"../op/op.Download.php?documentid=".$documentid."&file=".$file->getID()."\"><i class=\"icon-download\"></i>".getMLText('download')."</a>";
					if ($viewonlinefiletypes && in_array(strtolower($file->getFileType()), $viewonlinefiletypes))
						print "<li><a target=\"_blank\" href=\"../op/op.ViewOnline.php?documentid=".$documentid."&file=". $file->getID()."\"><i class=\"icon-star\"></i>" . getMLText("view_online") . "</a></li>";
				} else print "<li><img class=\"mimeicon\" src=\"images/icons/".$this->getMimeIcon($file->getFileType())."\" title=\"".htmlspecialchars($file->getMimeType())."\">";
				echo "</ul><ul class=\"unstyled actions\">";
				if (($document->getAccessMode($user) == M_ALL)||($file->getUserID()==$user->getID()))
					print "<li><a href=\"out.RemoveDocumentFile.php?documentid=".$documentid."&fileid=".$file->getID()."\"><i class=\"icon-remove\"></i>".getMLText("delete")."</a></li>";
				print "</ul></td>";
				print "</tr>";
			}
			print "</tbody>\n</table>\n";	
		}
		else printMLText("no_attached_files");
		if ($document->getAccessMode($user) >= M_READWRITE){
			print "<ul class=\"unstyled\"><li><a href=\"../out/out.AddFile.php?documentid=".$documentid."\" class=\"btn\">".getMLText("add")."</a></ul>\n";
		}
		$this->contentContainerEnd();
?>
		  </div>
		  <div class="tab-pane <?php if($currenttab == 'links') echo 'active'; ?>" id="links">
<?php
		$this->contentHeading(getMLText("linked_documents"));
		$this->contentContainerStart();
		if (count($links) > 0) {
			print "<table class=\"table table-condensed\">";
			print "<thead>\n<tr>\n";
			print "<th>".getMLText("doc_number")."</th>\n";
			print "<th>".getMLText("name")."</th>\n";
			print "<th>".getMLText("comment")."</th>\n";
			print "<th></th>\n";
			print "<th></th>\n";
			print "</tr>\n</thead>\n<tbody>\n";
			foreach($links as $link) {
				$responsibleUser = $link->getUser();
				$targetDoc = $link->getTarget();
				$targetlc = $targetDoc->getLatestContent();
				$previewer->createPreview($targetlc, $previewwidthlist);
				print "<tr>";
				print "<td><a href=\"../op/op.Download.php?documentid=".$targetDoc->getID()."&version=".$targetlc->getVersion()."\">";
				print $targetDoc->getDocNum();
				print "</td>";
				print "<td><a href=\"out.ViewDocument.php?documentid=".$targetDoc->getID()."\" class=\"linklist\">".htmlspecialchars($targetDoc->getName())."</a></td>";
				print "<td>".htmlspecialchars($targetDoc->getComment())."</td>";
				print "</td>";
				print "<td><span class=\"actions\">";
				print "</span></td>";
				print "</tr>";
			}
			print "</tbody>\n</table>\n";
		}
		else printMLText("no_linked_files");
		$this->contentContainerEnd();
		if (count($reverselinks) > 0) {
			$this->contentHeading(getMLText("reverse_links"));
			$this->contentContainerStart();
			print "<table class=\"table table-condensed\">";
			print "<thead>\n<tr>\n";
			print "<th>".getMLText("doc_number")."</th>\n";
			print "<th>".getMLText("name")."</th>\n";
			print "<th>".getMLText("comment")."</th>\n";
			print "<th></th>\n";
			print "<th></th>\n";
			print "</tr>\n</thead>\n<tbody>\n";
			foreach($reverselinks as $link) {
				$responsibleUser = $link->getUser();
				$sourceDoc = $link->getDocument();
				$sourcelc = $sourceDoc->getLatestContent();
				$previewer->createPreview($sourcelc, $previewwidthlist);
				print "<tr>";
				print "<td><a href=\"../op/op.Download.php?documentid=".$sourceDoc->getID()."&version=".$sourcelc->getVersion()."\">";
				print $sourceDoc->getDocNum();
				print "</td>";
				print "<td><a href=\"out.ViewDocument.php?documentid=".$sourceDoc->getID()."\" class=\"linklist\">".htmlspecialchars($sourceDoc->getName())."</a></td>";
				print "<td>".htmlspecialchars($sourceDoc->getComment())."</td>";
				print "</td>";
				print "<td><span class=\"actions\">";
				print "</span></td>";
				print "</tr>";
			}
			print "</tbody>\n</table>\n";
			$this->contentContainerEnd();
		}
?>
		  </div>
		</div>
<?php
		if($user->isAdmin()) {
			$timeline = $document->getTimeline();
			if($timeline) {
				$this->contentHeading(getMLText("timeline"));
				foreach($timeline as &$item) {
					switch($item['type']) {
					case 'add_version':
						$msg = getMLText('timeline_'.$item['type'], array('document'=>$item['document']->getName(), 'version'=> $item['version']));
						break;
					case 'add_file':
						$msg = getMLText('timeline_'.$item['type'], array('document'=>$item['document']->getName()));
						break;
					case 'status_change':
						$msg = getMLText('timeline_'.$item['type'], array('document'=>$item['document']->getName(), 'version'=> $item['version'], 'status'=> getOverallStatusText($item['status'])));
						break;
					default:
						$msg = $this->callHook('getTimelineMsg', $document, $item);
						if(!is_string($msg))
							$msg = '???';
					}
					$item['msg'] = $msg;
				}
//				$this->printTimeline('out.ViewDocument.php?action=timelinedata&documentid='.$document->getID(), 300, '', date('Y-m-d'));
				$this->printTimelineHtml(300);
			}
		}
?>
		  </div>
		</div>
<?php
		$this->htmlEndPage();
	} /* }}} */
}
?>