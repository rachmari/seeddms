<?php
/**
 * Implementation of UpdateDocument view
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
 * Class which outputs the html page for UpdateDocument view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_UpdateDocument extends SeedDMS_Bootstrap_Style {

	function __takeOverButton($name, $users) { /* {{{ */
?>
	<span id="<?php echo $name; ?>_btn" style="cursor: pointer;" title="<?php printMLText("takeOver".$name); ?>"><i class="icon-arrow-left"></i></span>
<script>
$(document).ready( function() {
	$('#<?php echo $name; ?>_btn').click(function(ev){
		ev.preventDefault();
<?php
		foreach($users as $_id) {
			echo "$(\"#".$name." option[value='".$_id."']\").attr(\"selected\", \"selected\");\n";
		}
?>
		$("#<?php echo $name; ?>").trigger("chosen:updated");
	});
});
</script>
<?php
	} /* }}} */

	function js() { /* {{{ */
		$strictformcheck = $this->params['strictformcheck'];
		$dropfolderdir = $this->params['dropfolderdir'];
		header('Content-Type: application/javascript');
		$this->printDropFolderChooserJs("form1");
?>

$(document).ready( function() {
	$('#form1').submit(function(event) {

		/* Check the form for missing information */
		msg = new Array();
		if ($('#userfile').val() ==='') msg.push("<?php printMLText("js_no_file_attached");?>");

		/* If the form is missing data, display messages
		 * and prevent the form from submitting
		 */
		if (msg != ""){
			event.preventDefault();
			noty({
				text: msg.join('<br />'),
				type: 'error',
				dismissQueue: true,
				layout: 'topRight',
				theme: 'defaultTheme',
				_timeout: 1500,
			});
		} else {
			/* Prevent form from submitting more than once. */
			$('#submit-btn').prop('disabled', true);
			$('#submit-btn').val('Processing ...');
		}
	});
		/**
	 * When a new link is added, the database is checked using AJAX
	 * to ensure that the link exists. Any duplicate or non-existing
	 * links are returned in a message to the user. All existing links
	 * are added as a new readonly input with the document title.
	 */
	$('#add_link').click(function(event) {
		var msg = new Array();
		event.preventDefault();
		var link = $('#link_input').val();
		if(link === "") {return};
		
		/* To allow a comma separated list of links
		 * remove spaces and split around commas */
		link = link.replace(/ /g, "");
		link_array = link.split(',');
		
		$.get('../op/op.Ajax.php', { command: 'searchnumber', query: link_array}, 
			function(data) {
				var missingDocs = data.missing;
				var existsDocs = data.exists;
				missingDocs.forEach(function pushMissing(doc, i) {
					msg.push("Couldn't locate document " + doc);
				});
				existsDocs.forEach(function inputExists(doc, i) {
					var docNum = doc["number"];
					// Remove the period character from doc number for jQuery compatibility
					var docNumId = docNum.replace(/\./g, '-');
					// If the document id already exists, don't add
					if($('#' + docNumId).length > 0) {
						msg.push("You entered a duplicate document " + docNum);
					} else {
						var htmlStr = "<tr class='add_row'><td></td><td><span id='remove_" + docNumId + "'></span><span class='btn no-button-effects no-pointer btn-margin-correction'><i class=\"icon-link\"></i></span><input class='row-list' type='text' value='" + docNum + " - " + doc["title"] + "' id='" + docNumId + "' name='linkInputs[]'' readonly><span class='btn btn-margin-correction no-button-effects'><i class='icon-remove delete-row'></i></span></td></tr>";
						$('#list-group').after(htmlStr);
					}
					
				});
				if (msg != ""){
		  				noty({
				  		text: msg.join('<br />'),
				  		type: 'error',
				      	dismissQueue: true,
				  		layout: 'topRight',
				  		theme: 'defaultTheme',
							_timeout: 1500,
				  	});
				}

			});
		$('#link_input').val("");
	});
		/**
	 * When a new link is added, the database is checked using AJAX
	 * to ensure that the link exists. Any duplicate or non-existing
	 * links are returned in a message to the user. All existing links
	 * are added as a new readonly input with the document title.
	 */
	$('#add_notify').click(function(event) {
		var msg = new Array();
		event.preventDefault();
		var cc = $('#notify_input').val();
		if(cc === "") {return};

		/* To allow a comma separated list of links
		 * remove spaces and split around commas */
		cc = cc.replace(/ /g, "");
		cc_array = cc.split(',');

		$.get('../op/op.Ajax.php', { command: 'searchpeople', query: cc_array}, 
			function(data) {
				var missingPeeps = data.missing;
				var existsPeeps = data.exists;
				missingPeeps.forEach(function pushMissing(emp, i) {
					msg.push("Couldn't locate document " + emp);
				});
				existsPeeps.forEach(function inputExists(emp, i) {
					// Remove the period character from doc number for jQuery compatibility
					var empId = emp.replace(/\./g, '-');
					// If the employee id already exists, don't add
					if($('#' + empId).length > 0) {
						msg.push("You added the same person more than once " + emp);
					} else {
						var htmlStr = "<tr class='add_row'></td><td><td><span id='remove_" + empId + "'></span><span class='btn no-button-effects no-pointer btn-margin-correction'><i class=\"icon-user\"></i></span><input class='row-list' type='text' value='" + emp + "' id='" + empId + "' name='notifyInputsUsers[]'' readonly><span class='btn btn-margin-correction no-button-effects'><i class='icon-remove delete-row'></i></span></td></tr>";
						$('#notify-group').after(htmlStr);
					}
				});
				if (msg != ""){
		  				noty({
				  		text: msg.join('<br />'),
				  		type: 'error',
				      	dismissQueue: true,
				  		layout: 'topRight',
				  		theme: 'defaultTheme',
							_timeout: 1500,
				  	});
				}

			});
		$('#notify_input').val("");
	});
	// Remove an added row when x icon is clicked
	$('body').on('click', '.icon-remove', (function(event) {
		$(this).parents('.add_row').remove();
	}));
});
<?php
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$document = $this->params['document'];
		$strictformcheck = $this->params['strictformcheck'];
		$enablelargefileupload = $this->params['enablelargefileupload'];
		$enableadminrevapp = $this->params['enableadminrevapp'];
		$enableownerrevapp = $this->params['enableownerrevapp'];
		$enableselfrevapp = $this->params['enableselfrevapp'];
		$dropfolderdir = $this->params['dropfolderdir'];
		$workflowmode = $this->params['workflowmode'];
		$presetexpiration = $this->params['presetexpiration'];
		$documentid = $document->getId();
		$sortusersinlist = $this->params['sortusersinlist'];
		$notifyList = $document->getNotifyList();

		$this->htmlStartPage(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))));
		$this->globalNavigation($folder);
		$this->contentHeading(getMLText("revising").$document->getDocNum());
		$this->contentStart();


		if ($document->isLocked()) {

			$lockingUser = $document->getLockingUser();

			print "<div class=\"alert alert-warning\">";
			
			printMLText("update_locked_msg", array("username" => htmlspecialchars($lockingUser->getFullName()), "email" => $lockingUser->getEmail()));
			
			if ($lockingUser->getID() == $user->getID())
				printMLText("unlock_cause_locking_user");
			else if ($document->getAccessMode($user) == M_ALL)
				printMLText("unlock_cause_access_mode_all");
			else
			{
				printMLText("no_update_cause_locked");
				print "</div>";
				$this->htmlEndPage();
				exit;
			}

			print "</div>";
		}

		$latestContent = $document->getLatestContent();
		$reviewStatus = $latestContent->getReviewStatus();
		$approvalStatus = $latestContent->getApprovalStatus();
		if($workflowmode == 'advanced') {
			if($status = $latestContent->getStatus()) {
				if($status["status"] == S_IN_WORKFLOW) {
					$this->warningMsg("The current version of this document is in a workflow. This will be interrupted and cannot be completed if you upload a new version.");
				}
			}
		}

		$msg = getMLText("max_upload_size").": ".ini_get( "upload_max_filesize");
		if($enablelargefileupload) {
			$msg .= "<p>".sprintf(getMLText('link_alt_updatedocument'), "out.AddMultiDocument.php?folderid=".$folder->getID()."&showtree=".showtree())."</p>";
		}
		$this->contentContainerStart();
?>

<form action="../op/op.UpdateDocument.php" enctype="multipart/form-data" method="post" name="form1" id="form1">
	<input type="hidden" name="documentid" value="<?php print $document->getID(); ?>">
	<table class="table-condensed doc-table">
	
		<tr>
			<td><?php printMLText("local_file");?>:</td>
			<td><!-- input type="File" name="userfile" size="60" -->
<?php
	$this->printFileChooser('userfile', false);
?>
			</td>
		</tr>
		<tr>
            <td><?php printMLText("pdf_local_file");?>:</td>
            <td>
<?php
    $this->printFileChooser('userfilePDF', true);
?>
            </td>
        </tr>
		<tr>
            <td><?php printMLText("attach_file");?>:</td>
            <td>
<?php
    $this->printFileChooser('attachfile[]', true);
?>
            </td>
        </tr>
<?php if($dropfolderdir) { ?>
		<tr>
			<td><?php printMLText("dropfolder_file");?>:</td>
			<td><?php $this->printDropFolderChooserHtml("form1");?></td>
		</tr>
<?php } ?>
		<tr>
			<td><?php printMLText("comment_for_current_version");?>:</td>
			<td class="standardText">
				<textarea class='input-block-level' name="comment" rows="4" cols="80"></textarea>
			</td>
		</tr>
<?php
			if($presetexpiration) {
				if(!($expts = strtotime($presetexpiration)))
					$expts = time();
			} else {
				$expts = time();
			}
?>
		<tr hidden>
			<td><?php printMLText("expires");?>:</td>
			<td class="standardText">
        <span class="input-append date span12" id="expirationdate" data-date="<?php echo date('Y-m-d', $expts); ?>" data-date-format="yyyy-mm-dd" data-date-language="<?php echo str_replace('_', '-', $this->params['session']->getLanguage()); ?>">
          <input class="span3" size="16" name="expdate" type="text" value="<?php echo date('Y-m-d', $expts); ?>">
          <span class="add-on"><i class="icon-calendar"></i></span>
        </span><br />
        <label class="checkbox inline">
				  <input type="checkbox" name="expires" value="false"<?php if (!$document->expires()) print " checked";?>><?php printMLText("does_not_expire");?><br>
        </label>
			</td>
		</tr>
		<tr id='list-group'>
			<td><?php printMLText('add_document_link');?>:</td>
			<td>
				<input class='input-with-button' type='text' name='links' autocomplete='off' id='link_input'>
				<a href='#' role='btn' class='btn btn-margin-correction' id='add_link' name='add_link'>
					<?php printMLText("add");?>
				</a>
			</td>
		</tr>
		<?php 
			/* Retrieve linked documents and put each
			 * into it's own read-only input 
			 */
			$links = $document->getDocumentLinks();
			foreach($links as $link) {
				$targetDoc = $link->getTarget();
				$targetName = $targetDoc->getName();
				$targetNumber = $targetDoc->getDocNum();
				$targetNumId = str_replace('/\./g', '-', $docNumber);
				echo "<tr class='add_row'><td></td><td><span id='remove_".$targetNumId."'></span><span class='btn no-button-effects no-pointer btn-margin-correction'><i class=\"icon-link\"></i></span><input class='row-list' type='text' value='".$targetNumber." - ".$targetName."' id='".$targetNumId."' name='linkInputs[]'' readonly><span class='btn btn-margin-correction no-button-effects'><i class='icon-remove delete-row'></i></span></td></tr>";
			}
		?>

<?php
	$attrdefs = $dms->getAllAttributeDefinitions(array(SeedDMS_Core_AttributeDefinition::objtype_documentcontent, SeedDMS_Core_AttributeDefinition::objtype_all));
	if($attrdefs) {
		foreach($attrdefs as $attrdef) {
			$arr = $this->callHook('editDocumentContentAttribute', null, $attrdef);
			if(is_array($arr)) {
				echo $txt;
				echo "<tr>";
				echo "<td>".$arr[0].":</td>";
				echo "<td>".$arr[1]."</td>";
				echo "</tr>";
			} else {
?>
    <tr>
	    <td><?php echo htmlspecialchars($attrdef->getName()); ?>:</td>
	    <td><?php $this->printAttributeEditField($attrdef, '') ?></td>
    </tr>
<?php
			}
		}
	}
	if($workflowmode == 'traditional' || $workflowmode == 'traditional_only_approval') {
		// Retrieve a list of all users and groups that have review / approve
		// privileges.
		$docAccess = $folder->getReadAccessList($enableadminrevapp, $enableownerrevapp);
		if($workflowmode != 'traditional_only_approval') {
?>
		<tr hidden>
			<td colspan="2">
				<?php $this->contentSubHeading(getMLText("assign_reviewers")); ?>
      </td>
    </tr>
    <tr hidden>
      <td>
				<div class="cbSelectTitle"><?php printMLText("individuals");?>:</div>
      </td>
			<td>
        <select id="IndReviewer" class="chzn-select span9" name="indReviewers[]" multiple="multiple" data-placeholder="<?php printMLText('select_ind_reviewers'); ?>" data-no_results_text="<?php printMLText('unknown_owner'); ?>">
<?php
				$res=$user->getMandatoryReviewers();
				foreach ($docAccess["users"] as $usr) {
					if (!$enableselfrevapp && $usr->getID()==$user->getID()) continue; 
					$mandatory=false;
					foreach ($res as $r) if ($r['reviewerUserID']==$usr->getID()) $mandatory=true;

					if ($mandatory) print "<option disabled=\"disabled\" value=\"".$usr->getID()."\">". htmlspecialchars($usr->getLogin()." - ".$usr->getFullName())."</option>";
					else print "<option value=\"".$usr->getID()."\">". htmlspecialchars($usr->getLogin()." - ".$usr->getFullName())."</option>";
				}
?>
				</select>
<?php
				$tmp = array();
				foreach($reviewStatus as $r) {
					if($r['type'] == 0) {
					 	if($res) {
							$mandatory=false;
							foreach ($res as $rr)
								if ($rr['reviewerUserID']==$r['required']) {
									$mandatory=true;
								}
							if(!$mandatory)
								$tmp[] = $r['required'];
						} else {
							$tmp[] = $r['required'];
						}
					}
				}
				if($tmp) {
					$this->__takeOverButton("IndReviewer", $tmp);
				}
				/* List all mandatory reviewers */
				if($res) {
					$tmp = array();
					foreach ($res as $r) {
						if($r['reviewerUserID'] > 0) {
							$u = $dms->getUser($r['reviewerUserID']);
							$tmp[] =  htmlspecialchars($u->getFullName().' ('.$u->getLogin().')');
						}
					}
					if($tmp) {
						echo '<div class="mandatories"><span>'.getMLText('mandatory_reviewers').':</span> ';
						echo implode(', ', $tmp);
						echo "</div>\n";
					}
				}
				/* Check for mandatory reviewer without access */
				foreach($res as $r) {
					if($r['reviewerUserID']) {
						$hasAccess = false;
						foreach ($docAccess["users"] as $usr) {
							if ($r['reviewerUserID']==$usr->getID())
								$hasAccess = true;
						}
						if(!$hasAccess) {
							$noAccessUser = $dms->getUser($r['reviewerUserID']);
							echo "<div class=\"alert alert-warning\">".getMLText("mandatory_reviewer_no_access", array('user'=>htmlspecialchars($noAccessUser->getFullName()." (".$noAccessUser->getLogin().")")))."</div>";
						}
					}
				}
?>
      </td>
    </tr>
    <tr hidden>
      <td>
				<div class="cbSelectTitle"><?php printMLText("groups");?>:</div>
      </td>
			<td>
        <select id="GrpReviewer" class="chzn-select span9" name="grpReviewers[]" multiple="multiple" data-placeholder="<?php printMLText('select_grp_reviewers'); ?>" data-no_results_text="<?php printMLText('unknown_group'); ?>">
<?php
				foreach ($docAccess["groups"] as $grp) {
				
					$mandatory=false;
					foreach ($res as $r) if ($r['reviewerGroupID']==$grp->getID()) $mandatory=true;	

					if ($mandatory) print "<option value=\"".$grp->getID()."\" disabled=\"disabled\">".htmlspecialchars($grp->getName())."</option>";
					else print "<option value=\"".$grp->getID()."\">".htmlspecialchars($grp->getName())."</option>";
				}
?>
			</select>
<?php
				$tmp = array();
				foreach($reviewStatus as $r) {
					if($r['type'] == 1) {
						if($res) {
							$mandatory=false;
							foreach ($res as $rr)
								if ($rr['reviewerGroupID']==$r['required']) {
									$mandatory=true;
								}
							if(!$mandatory)
								$tmp[] = $r['required'];
						} else {
							$tmp[] = $r['required'];
						}
					}
				}
				if($tmp) {
					$this->__takeOverButton("GrpReviewer", $tmp);
				}
				/* List all mandatory groups of reviewers */
				if($res) {
					$tmp = array();
					foreach ($res as $r) {
						if($r['reviewerGroupID'] > 0) {
							$u = $dms->getGroup($r['reviewerGroupID']);
							$tmp[] =  htmlspecialchars($u->getName());
						}
					}
					if($tmp) {
						echo '<div class="mandatories"><span>'.getMLText('mandatory_reviewergroups').':</span> ';
						echo implode(', ', $tmp);
						echo "</div>\n";
					}
				}

				/* Check for mandatory reviewer group without access */
				foreach($res as $r) {
					if ($r['reviewerGroupID']) {
						$hasAccess = false;
						foreach ($docAccess["groups"] as $grp) {
							if ($r['reviewerGroupID']==$grp->getID())
								$hasAccess = true;
						}
						if(!$hasAccess) {
							$noAccessGroup = $dms->getGroup($r['reviewerGroupID']);
							echo "<div class=\"alert alert-warning\">".getMLText("mandatory_reviewergroup_no_access", array('group'=>htmlspecialchars($noAccessGroup->getName())))."</div>";
						}
					}
				}
?>
      </td>
		</tr>
<?php } ?>
    <tr hidden>
			<td colspan=2>
				<?php $this->contentSubHeading(getMLText("assign_approvers")); ?>	
      </td>
    </tr>
    <tr hidden>
      <td>
				<div class="cbSelectTitle"><?php printMLText("individuals");?>:</div>
      </td>
      <td>
        <select id="IndApprover" class="chzn-select span9" name="indApprovers[]" multiple="multiple" data-placeholder="<?php printMLText('select_ind_approvers'); ?>" data-no_results_text="<?php printMLText('unknown_owner'); ?>">
<?php
				$res=$user->getMandatoryApprovers();
				foreach ($docAccess["users"] as $usr) {
					if (!$enableselfrevapp && $usr->getID()==$user->getID()) continue; 

					$mandatory=false;
					foreach ($res as $r) if ($r['approverUserID']==$usr->getID()) $mandatory=true;
					
					if ($mandatory) print "<option value=\"". $usr->getID() ."\" disabled='disabled'>". htmlspecialchars($usr->getFullName())."</option>";
					else print "<option value=\"". $usr->getID() ."\">". htmlspecialchars($usr->getLogin()." - ".$usr->getFullName())."</option>";
				}
?>
        </select>
<?php
				$tmp = array();
				foreach($approvalStatus as $r) {
					if($r['type'] == 0) {
						if($res) {
							$mandatory=false;
							foreach ($res as $rr)
								if ($rr['approverUserID']==$r['required']) {
									$mandatory=true;
								}
							if(!$mandatory)
								$tmp[] = $r['required'];
						} else {
							$tmp[] = $r['required'];
						}
					}
				}
				if($tmp) {
					$this->__takeOverButton("IndApprover", $tmp);
				}
				/* List all mandatory approvers */
				if($res) {
					$tmp = array();
					foreach ($res as $r) {
						if($r['approverUserID'] > 0) {
							$u = $dms->getUser($r['approverUserID']);
							$tmp[] =  htmlspecialchars($u->getFullName().' ('.$u->getLogin().')');
						}
					}
					if($tmp) {
						echo '<div class="mandatories"><span>'.getMLText('mandatory_approvers').':</span> ';
						echo implode(', ', $tmp);
						echo "</div>\n";
					}
				}

				/* Check for mandatory approvers without access */
				foreach($res as $r) {
					if($r['approverUserID']) {
						$hasAccess = false;
						foreach ($docAccess["users"] as $usr) {
							if ($r['approverUserID']==$usr->getID())
								$hasAccess = true;
						}
						if(!$hasAccess) {
							$noAccessUser = $dms->getUser($r['approverUserID']);
							echo "<div class=\"alert alert-warning\">".getMLText("mandatory_approver_no_access", array('user'=>htmlspecialchars($noAccessUser->getFullName()." (".$noAccessUser->getLogin().")")))."</div>";
						}
					}
				}
?>
      </td>
    </tr>
    <tr hidden>
      <td>
				<div class="cbSelectTitle"><?php printMLText("groups");?>:</div>
      </td>
      <td>
        <select id="GrpApprover" class="chzn-select span9" name="grpApprovers[]" multiple="multiple" data-placeholder="<?php printMLText('select_grp_approvers'); ?>" data-no_results_text="<?php printMLText('unknown_group'); ?>">
<?php
				foreach ($docAccess["groups"] as $grp) {
				
					$mandatory=false;
					foreach ($res as $r) if ($r['approverGroupID']==$grp->getID()) $mandatory=true;	

					if ($mandatory) print "<option value=\"". $grp->getID() ."\" disabled=\"disabled\">".htmlspecialchars($grp->getName())."</option>";
					else print "<option value=\"". $grp->getID() ."\">".htmlspecialchars($grp->getName())."</option>";

				}
?>
        </select>
<?php
				$tmp = array();
				foreach($approvalStatus as $r) {
					if($r['type'] == 1) {
						if($res) {
							$mandatory=false;
							foreach ($res as $rr)
								if ($rr['approverGroupID']==$r['required']) {
									$mandatory=true;
								}
							if(!$mandatory)
								$tmp[] = $r['required'];
						} else {
							$tmp[] = $r['required'];
						}
					}
				}
				if($tmp) {
					$this->__takeOverButton("GrpApprover", $tmp);
				}
				/* List all mandatory groups of approvers */
				if($res) {
					$tmp = array();
					foreach ($res as $r) {
						if($r['approverGroupID'] > 0) {
							$u = $dms->getGroup($r['approverGroupID']);
							$tmp[] =  htmlspecialchars($u->getName());
						}
					}
					if($tmp) {
						echo '<div class="mandatories"><span>'.getMLText('mandatory_approvergroups').':</span> ';
						echo implode(', ', $tmp);
						echo "</div>\n";
					}
				}

				/* Check for mandatory approver groups without access */
				foreach($res as $r) {
					if ($r['approverGroupID']) {
						$hasAccess = false;
						foreach ($docAccess["groups"] as $grp) {
							if ($r['approverGroupID']==$grp->getID())
								$hasAccess = true;
						}
						if(!$hasAccess) {
							$noAccessGroup = $dms->getGroup($r['approverGroupID']);
							echo "<div class=\"alert alert-warning\">".getMLText("mandatory_approvergroup_no_access", array('group'=>htmlspecialchars($noAccessGroup->getName())))."</div>";
						}
					}
				}
?>
			</td>
			</tr>
		<tr hidden>
			<td colspan="2"><div class="alert"><?php printMLText("add_doc_reviewer_approver_warning")?></div></td>
		</tr>
<?php
	} else {
?>
		<tr>	
      <td>
			<div class="cbSelectTitle"><?php printMLText("workflow");?>:</div>
      </td>
      <td>
<?php
				$mandatoryworkflows = $user->getMandatoryWorkflows();
				if($mandatoryworkflows) {
					if(count($mandatoryworkflows) == 1) {
?>
				<?php echo htmlspecialchars($mandatoryworkflows[0]->getName()); ?>
				<input type="hidden" name="workflow" value="<?php echo $mandatoryworkflows[0]->getID(); ?>">
<?php
					} else {
?>
        <select class="_chzn-select-deselect span9" name="workflow" data-placeholder="<?php printMLText('select_workflow'); ?>">
<?php
					$curworkflow = $latestContent->getWorkflow();
					foreach ($mandatoryworkflows as $workflow) {
						print "<option value=\"".$workflow->getID()."\"";
						if($curworkflow && $curworkflow->getID() == $workflow->getID())
							echo " selected=\"selected\"";
						print ">". htmlspecialchars($workflow->getName())."</option>";
					}
?>
        </select>
<?php
					}
				} else {
?>
        <select class="_chzn-select-deselect span9" name="workflow" data-placeholder="<?php printMLText('select_workflow'); ?>">
<?php
					$workflows=$dms->getAllWorkflows();
					print "<option value=\"\">"."</option>";
					foreach ($workflows as $workflow) {
						print "<option value=\"".$workflow->getID()."\"";
						print ">". htmlspecialchars($workflow->getName())."</option>";
					}
?>
        </select>
<?php
				}
?>
      </td>
    </tr>
		<tr>	
      <td colspan="2">
			<?php $this->warningMsg(getMLText("add_doc_workflow_warning")); ?>
      </td>
		</tr>	
<?php
	}
?>
		<tr>
			<td>
				<?php $this->contentSubHeading(getMLText("add_document_notify")); ?>
			</td>
		</tr>	
		<!--
			Add a form to add new users to notification list.
		-->
		<tr id='notify-group'>	
			<td>
				<div class="cbSelectTitle"><?php printMLText("individuals");?>:</div>
			</td>
				<td>
				<input class='input-with-button' type='text' name="notification_users" autocomplete='off' id='notify_input'>
				<a href='#' role='btn' class='btn btn-margin-correction' id='add_notify' name='add_notify'>
						<?php printMLText("add");?>
				</a>
			</td>
		</tr>

<?php
		/* 
		 * Print the users that are already on the notification list
		 * and add a link to remove the user from the list.
		 */
		$userNotifyIDs = array();
		$groupNotifyIDs = array();


		foreach ($notifyList["users"] as $userNotify) {
			$login = $userNotify->getLogin();
			$loginID = str_replace('/\./g', '-', $login);
			$fullName = $userNotify->getFullName();
			$userID = $userNotify->getID();
			print "<tr class='add_row'><td></td><td><span id='remove_" . $loginID . "'></span><span class='btn no-button-effects no-pointer btn-margin-correction'><i class=\"icon-user\"></span></i><input class='row-list' type='text' value='" . $login . "' id='" . $loginID . "' name='notifyInputsUsers[]'' readonly><span class='btn btn-margin-correction no-button-effects'><i class='icon-remove delete-row'></i></span></td></tr>";
			$userNotifyIDs[] = $userNotify->getID();
		}
?>
		<tr>
		<td colspan="2"><p class='submit-button-container'><input class='submit-button' type="submit" id='submit-btn' value="<?php printMLText("add_document");?>"></p></td>
		</tr>
	</table>
</form>
<?php			

		$this->contentContainerEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
