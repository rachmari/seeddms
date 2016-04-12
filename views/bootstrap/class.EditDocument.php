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
			<td><input class='input-block-level' type="text" name="name" id="name" value="<?php print htmlspecialchars($document->getName());?>" size="60"></td>
		</tr>
		<tr>
			<td valign="top" class="inputDescription"><?php printMLText("comment");?>:</td>
			<td><textarea class='input-block-level' name="comment" id="comment" rows="4" cols="80"><?php print htmlspecialchars($document->getComment());?></textarea></td>
		</tr>
		<tr hidden>
			<td valign="top" class="inputDescription"><?php printMLText("keywords");?>:</td>
			<td class="standardText">
<?php
	$this->printKeywordChooserHtml('form1', $document->getKeywords());
?>
			</td>
		</tr>
		<tr hidden>
			<td><?php printMLText("categories")?>:</td>
			<td>
        <select class="chzn-select" name="categories[]" multiple="multiple" data-placeholder="<?php printMLText('select_category'); ?>" data-no_results_text="<?php printMLText('unknown_document_category'); ?>">
<?php
			$categories = $dms->getDocumentCategories();
			foreach($categories as $category) {
				echo "<option value=\"".$category->getID()."\"";
				if(in_array($category, $document->getCategories()))
					echo " selected";
				echo ">".$category->getName()."</option>";	
			}
?>
				</select>
      </td>
		</tr>
		<tr hidden>
			<td><?php printMLText("expires");?>:</td>
			<td>
        <span class="input-append date span12" id="expirationdate" data-date="<?php echo $expdate; ?>" data-date-format="yyyy-mm-dd" data-date-language="<?php echo str_replace('_', '-', $this->params['session']->getLanguage()); ?>">
          <input class="span3" size="16" name="expdate" type="text" value="<?php echo $expdate; ?>">
          <span class="add-on"><i class="icon-calendar"></i></span>
        </span><br />
        <label class="checkbox inline">
				  <input type="checkbox" name="expires" value="false"<?php if (!$document->expires()) print " checked";?>><?php printMLText("does_not_expire");?><br>
        </label>
			</td>
		</tr>
<?php
		if ($folder->getAccessMode($user) > M_READ) {
			print "<tr hidden>";
			print "<td class=\"inputDescription\">" . getMLText("sequence") . ":</td>";
			print "<td>";
			$this->printSequenceChooser($folder->getDocuments('s'), $document->getID());
			if($orderby != 's') echo "<br />".getMLText('order_by_sequence_off'); 
			print "</td></tr>";
		}
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
