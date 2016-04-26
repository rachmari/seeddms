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

	$('#form1').submit(function(event) {

		/* Check the form for missing information */
		msg = new Array();
		if ($('#userfile').val() ==='') msg.push("<?php printMLText("js_no_file");?>");
		// Get file object from input to check for pdf type
		if($('#userfilePDF').prop("files")[0]) {
			var file = $('#userfilePDF').prop("files")[0];
			if(file.type !== 'application/pdf') msg.push("<?php printMLText("pdf_type_error");?>");
		}
		$('input:file').each(function() {
			var file = this.files[0];
			if (file) {
				if(file.size > 60*1024*1024) { // Don't allow file size to exceed 60MB
					msg.push(file.name + " <?php printMLText("uploading_maxsize");?>");
				}
			}
		});

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
					msg.push("<?php printMLText("no_user"); ?>" + emp);
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
			<td>
<?php
	$this->printFileChooser('userfile', false);
?>
			</td>
		</tr>
		<tr>
            <td><?php printMLText("pdf_local_file");?>:</td>
            <td>
<?php
    $this->printFileChooser('userfilePDF', true, 'application/pdf, .pdf');
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
		<tr id='list-group' data-toggle='tooltip' data-placement='right' title='<?php printMLText('add_document_link_tooltip');?>'>
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
?>
		<tr>
			<td>
				<?php $this->contentSubHeading(getMLText("add_document_notify")); ?>
			</td>
		</tr>	
		<!--
			Add a form to add new users to notification list.
		-->
		<tr id='notify-group' data-toggle='tooltip' data-placement='right' title='<?php printMLText('notify_link_tooltip');?>'>	
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
		<td colspan="2"><p class='submit-button-container'><input class='submit-button' type="submit" id='submit-btn' value="<?php printMLText("submit");?>"></p></td>
		</tr>
	</table>
</form>
<?php			

		$this->contentContainerEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
