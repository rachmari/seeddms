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
<script id="upload-template" type="text/template">
  <div class="upload ">
  	<span class='myLabel'></span>
    <input class="upload-input" type="file" name="attachfile[]">
    <div class="file hidden">
      <span class="filename"></span>
      <span class='delete btn no-button-effects no-pointer'><i class='icon-remove delete-row'></i>
    </div>
  </div>
</script>
<script language="JavaScript">

$(document).ready(function() {
	var addUploader = function() {
	    var template = $('#upload-template').html();
	    $('#uploads').prepend(template);
	};
	addUploader();

	$('#uploads').on('change', '.upload-input', function() {
	    var fileName = $(this).val().replace(/^.*[\\\/]/, '');
	    $(this).addClass('hidden');
	    $(this).siblings('.file').removeClass('hidden');
	    $(this).siblings('.myLabel').addClass('hidden');
	    $(this).siblings('.file').children('.filename').text(fileName);
	    addUploader();
	  });

	$('#uploads').on('click', '.delete', function() {
	    $(this).parents('.upload').remove();
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

	$('#submit-btn').click(function(event) {
		event.preventDefault();
		msg = new Array();
		setDocNumber = "<?php echo $user->_login; ?>-" + $('#setDocNumber').val();
		$.get('../op/op.Ajax.php', { command: 'memoAvailable', query: setDocNumber}, 
			function(data) {
				if(data === false) {
					msg.push("This document number has already been used.");
					noty({
				  		text: msg.join('<br />'),
				  		type: 'error',
				      	dismissQueue: true,
				  		layout: 'topRight',
				  		theme: 'defaultTheme',
						_timeout: 1500,
				  	});
				} else {
					$('#add-doc-form').submit();
				}
			});

	});

	$('#add-doc-form').submit(function(event) {
		/* Check the form for missing information */
		var acceptedFileTypes = ['application/pdf', 'application/vnd.oasis.opendocument.text', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/vnd.oasis.opendocument.presentation'];

		msg = new Array();
		if ($('#title-input').val() === "") msg.push("<?php printMLText("js_no_name");?>");
		if ($('#comment-input').val() === "") msg.push("<?php printMLText("js_no_comment");?>");
		if ($('#userfile').val() ==='') msg.push("<?php printMLText("js_no_file");?>");
		// Check for file type to be document, pdf, or presentation
		else {
			var file = $('#userfile').prop("files")[0];
			var match, i = 0;
			for(i; i < acceptedFileTypes.length; i++) {
				if(file.type === acceptedFileTypes[i]) match = 1;
			}
			if(!match) msg.push("<?php printMLText("source_type_error");?>");
		}
		// Get file object from input to check for pdf type
		if($('#userfilePDF').prop("files")[0]) {
			var file = $('#userfilePDF').prop("files")[0];
			if(file.type !== 'application/pdf') msg.push("<?php printMLText("pdf_type_error");?>");
		}
		$('input:file').each(function() {
			/*<?php echo ini_get('upload_max_filesize'); ?>*/
			var file = this.files[0];
			if (file) {
				if(file.size > 60*1024*1024) { // Don't allow file size to exceed 60MB
					msg.push(file.name + " <?php printMLText("uploading_maxsize");?>");
				}
			}
		});

		/* 
		 * If the form is missing data, display messages
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
			<td><?php echo $user->_login . "&nbsp;-&nbsp;" ?><input type='text' id='setDocNumber' name='setDocNumber' value='<?php 
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
		<tr id='list-group' data-toggle='tooltip' data-placement='right' title='<?php printMLText('add_document_link_tooltip');?>'>
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
    $this->printFileChooser('userfilePDF', true, 'application/pdf, .pdf');
?>
            </td>
        </tr>
        <tr>
            <td><?php printMLText("attach_file");?>:</td>
            <td><div id='uploads'></div></td>
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
?>
		  <tr>	
        <td>
		<?php $this->contentSubHeading(getMLText("add_document_notify")); ?>
        </td>
			</tr>	

		  <tr id='notify-group' data-toggle='tooltip' data-placement='right' title='<?php printMLText('notify_link_tooltip');?>'>	
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
			<tr><td></td><td><p class='note'><?php printMLText("owner_added");?></p></td></tr>
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