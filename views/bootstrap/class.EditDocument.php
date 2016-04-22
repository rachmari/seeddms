<?php
/**
 * Implementation of EditDocument view
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
 * Class which outputs the html page for EditDocument view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_EditDocument extends SeedDMS_Bootstrap_Style {

	function js() { /* {{{ */
		$strictformcheck = $this->params['strictformcheck'];
		header('Content-Type: application/javascript');
		$this->printKeywordChooserJs('form1');
?>

$(document).ready( function() {
	var origName = $('#name').val();
	var origComment = $('#comment').val();

	$('#name').keyup(function(event) {
		var text = $(this).val();
		var length = text.length;
		$("#name-count").text(150 - length);
		if(length === 150) {
			$("#name-count-container").addClass("form-alert").removeClass('form-help');
		} else {
			$("#name-count-container").addClass("form-help").removeClass('form-alert');
		}
	});

	$('#form1').submit(function(event) {
		/* Check the form for missing information */
		msg = new Array();
		var newName = $('#name').val();
		var newComment = $('#comment').val();
		if (newName === "") msg.push("<?php printMLText("js_no_name");?>");
		if (newComment === "") msg.push("<?php printMLText("js_no_comment");?>");
		if (newName === origName && newComment === origComment) msg.push("<?php printMLText("js_same_info");?>");

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
});
<?php
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$document = $this->params['document'];
		$attrdefs = $this->params['attrdefs'];
		$strictformcheck = $this->params['strictformcheck'];
		$orderby = $this->params['orderby'];

		$this->htmlStartPage(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))));
		$this->globalNavigation($folder);
		$this->contentHeading(getMLText("edit_document_props"));
		$this->contentStart();

		
		$this->contentContainerStart();

		if($document->expires())
			$expdate = date('Y-m-d', $document->getExpires());
		else
			$expdate = '';
?>
<form action="../op/op.EditDocument.php" name="form1" id="form1" method="post">
	<input type="hidden" name="documentid" value="<?php echo $document->getID() ?>">
	<table class='table-condensed doc-table' cellpadding="3">
		<tr>
			<td class="inputDescription"><?php printMLText("name");?>:</td>
			<td><input class='input-block-level' type="text" name="name" id="name" value="<?php print htmlspecialchars($document->getName()); ?>" size="60" maxlength="150">
			<label id='name-count-container' class='form-help'><span id='name-count'>
			<?php 
				$docName = htmlspecialchars($document->getName());
				print 150 - intval(strlen($docName));
			?></span><span>&nbsp;<?php printMLText('chars_left'); ?></span></label></td>
		</tr>
		<tr>
			<td valign="top" class="inputDescription"><?php printMLText("comment");?>:</td>
			<td><textarea class='input-block-level' name="comment" id="comment" rows="4" cols="80"><?php print htmlspecialchars($document->getComment());?></textarea></td>
		</tr>
<?php
		if($attrdefs) {
			foreach($attrdefs as $attrdef) {
				$arr = $this->callHook('editDocumentAttribute', $document, $attrdef);
				if(is_array($arr)) {
					echo "<tr>";
					echo "<td>".$arr[0].":</td>";
					echo "<td>".$arr[1]."</td>";
					echo "</tr>";
				} else {
?>
		<tr>
			<td><?php echo htmlspecialchars($attrdef->getName()); ?>:</td>
			<td><?php $this->printAttributeEditField($attrdef, $document->getAttribute($attrdef)) ?></td>
		</tr>
<?php
				}
			}
		}
?>
		<tr>
			<td colspan="2"><p class='submit-button-container'><input class='submit-button' type="submit" id='submit-btn' class="btn" value=<?php printMLText("save")?>></p></td>
		</tr>
	</table>
</form>
<?php
		$this->contentContainerEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
