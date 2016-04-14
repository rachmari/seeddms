<?php
/**
 * Implementation of AddDocument view
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
 * Class which outputs the html page for AddDocument view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_AddDocument extends SeedDMS_Bootstrap_Style {
	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$enablelargefileupload = $this->params['enablelargefileupload'];
		$enableadminrevapp = $this->params['enableadminrevapp'];
		$enableownerrevapp = $this->params['enableownerrevapp'];
		$enableselfrevapp = $this->params['enableselfrevapp'];
		$strictformcheck = $this->params['strictformcheck'];
		$dropfolderdir = $this->params['dropfolderdir'];
		$workflowmode = $this->params['workflowmode'];
		$presetexpiration = $this->params['presetexpiration'];
		$sortusersinlist = $this->params['sortusersinlist'];
		$orderby = $this->params['orderby'];
		$folderid = $folder->getId();
		$this->htmlStartPage(getMLText("folder_title", array("foldername" => htmlspecialchars($folder->getName()))));
		$this->globalNavigation($folder);
		$this->contentHeading(getMLText("add_document"));
		$this->contentStart();
		
?>
<script language="JavaScript">

$(document).ready(function() {
	$('#new-file').click(function(event) {
			$("#upload-file").clone().appendTo("#upload-files").removeAttr("id").children('div').children('input').val('');
	});

	$('#userfilePDF').change(function(event) {
		msg = new Array();
		var file = this.files[0];
		if(file.type !== 'application/pdf') {
			msg.push("<?php printMLText("pdf_type_error");?>");
		}
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
		}
	});

	$('#title-input').keyup(function(event) {
		var text = $(this).val();
		var length = text.length;
		$("#name-count").text(150 - length);
		if(length === 150) {
			$("#name-count-container").addClass("form-alert").removeClass('form-help');
		} else {
			$("#name-count-container").addClass("form-help").removeClass('form-alert');
		}
	});

	$('#add-doc-form').submit(function(event) {
		/* Check the form for missing information */
		msg = new Array();
		if ($('#title-input').val() === "") msg.push("<?php printMLText("js_no_name");?>");
		if ($('#comment-input').val() === "") msg.push("<?php printMLText("js_no_comment");?>");
		if ($('#userfile').val() ==='') msg.push("<?php printMLText("js_no_file");?>");
		// Get file object from input to check for pdf type
		if($('#userfilePDF').prop("files")[0]) {
			var file = $('#userfilePDF').prop("files")[0];
			if(file.type !== 'application/pdf') msg.push("<?php printMLText("pdf_type_error");?>");
		}

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
					msg.push("<?php printMLText("no_doc"); ?>" + doc);
				});
				existsDocs.forEach(function inputExists(doc, i) {
					var docNum = doc["number"];
					// Remove the period character from doc number for jQuery compatibility
					var docNumId = docNum.replace(/\./g, '-');
					// If the document id already exists, don't add
					if($('#' + docNumId).length > 0) {
						msg.push("You entered a duplicate document " + docNum);
					} else {
						var htmlStr = "<tr class='add_row'><td></td><td><span id='remove_" + docNumId + "'></span><span class='btn no-button-effects no-pointer'><i class=\"icon-link\"></i></span><input class='row-list' type='text' value='" + docNum + " - " + doc["title"] + "' id='" + docNumId + "' name='linkInputs[]'' readonly><span class='btn no-button-effects no-pointer'><i class='icon-remove delete-row'></i></div></td></tr>";
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
					msg.push("<?php printMLText("no_user"); ?>" + emp);
				});
				existsPeeps.forEach(function inputExists(emp, i) {
					// Remove the period character from doc number for jQuery compatibility
					var empId = emp.replace(/\./g, '-');
					// If the employee id already exists, don't add
					if($('#' + empId).length > 0) {
						msg.push("You added the same person more than once " + emp);
					} else {

						var htmlStr = "<tr class='add_row'></td><td><td><span id='remove_" + empId + "'></span><span class='btn no-button-effects no-pointer'><i class=\"icon-user\"></i></span><input class='row-list' type='text' value='" + emp + "' id='" + empId + "' name='notifyInputsUsers[]'' readonly><span class='btn no-button-effects'><i class='icon-remove delete-row'></i></span></td></tr>";
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
</script>

<?php
		$msg = getMLText("max_upload_size").": ".ini_get( "upload_max_filesize");
		if($enablelargefileupload) {
			$msg .= "<p>".sprintf(getMLText('link_alt_updatedocument'), "out.AddMultiDocument.php?folderid=".$folderid."&showtree=".showtree())."</p>";
		}
		$this->contentContainerStart();
		
		// Retrieve a list of all users and groups that have review / approve
		// privileges.
		$docAccess = $folder->getReadAccessList($enableadminrevapp, $enableownerrevapp);
?>
		<form action="../op/op.AddDocument.php" enctype="multipart/form-data" method="post" id='add-doc-form' name="form1">
		<?php echo createHiddenFieldWithKey('adddocument'); ?>
		<input type="hidden" name="folderid" value="<?php print $folderid; ?>">
		<input type="hidden" name="showtree" value="<?php echo showtree();?>">
		<table class="table-condensed doc-table">
		<tr>
			<td>
		<?php $this->contentSubHeading(getMLText("document_infos")); ?>
			</td>
		</tr>
		<tr>
			<td><?php printMLText("number");?>:</td>
			<td><?php echo $user->_login . "&nbsp;-&nbsp;" ?><input type='text' name='setDocNumber' value='<?php 
				echo $dms->getNextMemoNum($user->getID());
			?>'></td>
		</tr>
		<tr>
			<td class='form-title-top'><?php printMLText("name");?>:</td>
			<td><input class='input-block-level' type="text" id='title-input' name="name" maxlength="150">
			<label id='name-count-container' class='form-help'><span id='name-count'>150</span><span>&nbsp;<?php printMLText('chars_left'); ?></span></label></td>
		</tr>
		<tr>
			<td class='form-title-top'><?php printMLText("comment");?>:</td>
			<td><textarea class='input-block-level' name="comment" id='comment-input' rows="5" placeholder="<?php printMLText('comment_placeholder');?>"></textarea></td>
		</tr>
		<tr hidden>
			<td><?php printMLText("keywords");?>:</td>
			<td><?php $this->printKeywordChooser("form1");?></td>
		</tr>
		<tr hidden>
			<td><?php printMLText("categories")?>:</td>
			<td>
        <select name="categories[]" data-placeholder="<?php printMLText('select_category'); ?>" data-no_results_text="<?php printMLText('unknown_document_category'); ?>">
<?php
			$categories = $dms->getDocumentCategories();
			foreach($categories as $category) {
				echo "<option value=\"".$category->getID()."\"";
				echo ">".$category->getName()."</option>";	
			}
?>
				</select>
      </td>
		</tr>
		<tr hidden>
			<td><?php printMLText("sequence");?>:</td>
			<td><?php $this->printSequenceChooser($folder->getDocuments('s')); if($orderby != 's') echo "<br />".getMLText('order_by_sequence_off'); ?></td>
		</tr>
<?php
			$attrdefs = $dms->getAllAttributeDefinitions(array(SeedDMS_Core_AttributeDefinition::objtype_document, SeedDMS_Core_AttributeDefinition::objtype_all));
			if($attrdefs) {
				foreach($attrdefs as $attrdef) {
					$arr = $this->callHook('editDocumentAttribute', null, $attrdef);
					if(is_array($arr)) {
						echo "<tr>";
						echo "<td>".$arr[0].":</td>";
						echo "<td>".$arr[1]."</td>";
						echo "</tr>";
					} else {
?>
		<tr>
			<td><?php echo htmlspecialchars($attrdef->getName()); ?></td>
			<td><?php $this->printAttributeEditField($attrdef, '') ?></td>
		</tr>
<?php
					}
				}
			}
			if($presetexpiration) {
				if(!($expts = strtotime($presetexpiration)))
					$expts = time();
			} else {
				$expts = time();
			}
?>
		<tr hidden>
			<td><?php printMLText("expires");?>:</td>
			<td>
        <span class="input-append date span12" id="expirationdate" data-date="<?php echo date('Y-m-d', $expts); ?>" data-date-format="yyyy-mm-dd" data-date-language="<?php echo str_replace('_', '-', $this->params['session']->getLanguage()); ?>">
          <input class="span3" size="16" name="expdate" type="text" value="<?php echo date('Y-m-d', $expts); ?>">
          <span class="add-on"><i class="icon-calendar"></i></span>
        </span>&nbsp;
        <label class="checkbox inline">
					<input type="checkbox" name="expires" value="false" <?php echo  ($presetexpiration ? "" : "checked");?>><?php printMLText("does_not_expire");?>
        </label>
			</td>
		</tr>

		<tr id='list-group' data-toggle="tooltip" title='<?php printMLText('add_document_link_tooltip');?>'>
			<td><?php printMLText('add_document_link');?>:</td>
			<td>
				<input class='input-with-button' type='text' name='links' autocomplete='off' id='link_input' placeholder="ex: jane.doe-2, john.doe-5">
				<a href='#' role='btn' class='btn' id='add_link' name='add_link'>
					<?php printMLText("add");?>
				</a>
			</td>
		</tr>

		<tr>
			<td>
		<?php $this->contentSubHeading(getMLText("version_info")); ?>
			</td>
		</tr>
		<tr hidden>
			<td><?php printMLText("version");?>:</td>
			<td><input type="text" name="reqversion" value="1"></td>
		</tr>
		<tr>
			<td><?php printMLText("local_file");?>:</td>
			<td>
<!--
			<a href="javascript:addFiles()"><?php printMLtext("add_multiple_files") ?></a>
			<ol id="files">
			<li><input type="file" name="userfile[]" size="60"></li>
			</ol>
-->
<?php
	$this->printFileChooser('userfile', false);
?>
			</td>
		</tr>
      <tr>
            <td><?php printMLText("pdf_local_file");?>:</td>
            <td>
<?php
    $this->printFileChooser('userfilePDF', true, '.pdf');
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
			<td><?php $this->printDropFolderChooser("form1");?></td>
		</tr>
<?php } ?>
		<tr>
			<td class='form-title-top'><?php printMLText("comment_for_current_version");?>:</td>
			<td><textarea class='input-block-level' name="version_comment" rows="3" cols='80' placeholder="<?php printMLText("version_comment_placeholder");?>"></textarea></td>
		</tr>
<?php
			$attrdefs = $dms->getAllAttributeDefinitions(array(SeedDMS_Core_AttributeDefinition::objtype_documentcontent, SeedDMS_Core_AttributeDefinition::objtype_all));
			if($attrdefs) {
				foreach($attrdefs as $attrdef) {
					$arr = $this->callHook('editDocumentAttribute', null, $attrdef);
					if(is_array($arr)) {
						echo "<tr>";
						echo "<td>".$arr[0].":</td>";
						echo "<td>".$arr[1]."</td>";
						echo "</tr>";
					} else {
?>
		<tr>
			<td><?php echo htmlspecialchars($attrdef->getName()); ?></td>
			<td><?php $this->printAttributeEditField($attrdef, '', 'attributes_version') ?></td>
		</tr>
<?php
					}
				}
			}
		if($workflowmode == 'advanced') {
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
					foreach ($mandatoryworkflows as $workflow) {
						print "<option value=\"".$workflow->getID()."\"";
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
		} else {
			if($workflowmode == 'traditional') {
?>
		<tr hidden>
      <td>
		<?php $this->contentSubHeading(getMLText("assign_reviewers")); ?>
      </td>
		</tr>	
		<tr hidden>	
      <td>
			<div class="cbSelectTitle"><?php printMLText("individuals");?>:</div>
      </td>
      <td>
<?php
				$res=$user->getMandatoryReviewers();
?>
        <select class="chzn-select span9" name="indReviewers[]" multiple="multiple" data-placeholder="<?php printMLText('select_ind_reviewers'); ?>">
<?php
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
        <select class="chzn-select span9" name="grpReviewers[]" multiple="multiple" data-placeholder="<?php printMLText('select_grp_reviewers'); ?>">
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
        <td>
		<?php $this->contentSubHeading(getMLText("assign_approvers")); ?>
        </td>
		  </tr>	
		
		  <tr hidden>	
        <td>
			<div class="cbSelectTitle"><?php printMLText("individuals");?>:</div>
        </td>
				<td>
      <select class="chzn-select span9" name="indApprovers[]" multiple="multiple" data-placeholder="<?php printMLText('select_ind_approvers'); ?>">
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
      <select class="chzn-select span9" name="grpApprovers[]" multiple="multiple" data-placeholder="<?php printMLText('select_grp_approvers'); ?>">
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
        <td colspan="2">
			<div class="alert"><?php printMLText("add_doc_reviewer_approver_warning")?></div>
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

		  <tr id='notify-group' data-toggle="tooltip" title='<?php printMLText('notify_link_tooltip');?>'>	
        <td>
			<div class="cbSelectTitle"><?php printMLText("parade_un");?>:</div>
        </td>
        <td>
				<input class='input-with-button' type='text' name="notification_users" autocomplete='off' id='notify_input' placeholder="ex: jane.doe, john.doe">
				<a href='#' role='btn' class='btn' id='add_notify' name='add_notify'>
					<?php printMLText("add");?>
				</a>
				</td>
			</tr>
		  <tr hidden>	
        <td>
			<div class="cbSelectTitle"><?php printMLText("groups");?>:</div>
        </td>
        <td>
				<select class="chzn-select span9" name="notification_groups[]" multiple="multiple" data-placeholder="<?php printMLText('select_grp_notification'); ?>">
<?php
						$allGroups = $dms->getAllGroups();
						foreach ($allGroups as $groupObj) {
							if ($folder->getGroupAccessMode($groupObj) >= M_READ)
								print "<option value=\"".$groupObj->getID()."\">" . htmlspecialchars($groupObj->getName()) . "\n";
						}
?>
				</select>
				</td>
			</tr>
		<tr>
		<td colspan="2"><p class='submit-button-container'><input id='submit-btn' class='submit-button' type="submit" value="<?php printMLText("submit");?>"></p></td>
		</tr>
		</table>

			
		</form>
<?php
		$this->contentContainerEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>