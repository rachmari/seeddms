<?php
/**
 * Implementation of EditFolder view
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
 * Class which outputs the html page for EditFolder view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_EditFolder extends SeedDMS_Bootstrap_Style {

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$attrdefs = $this->params['attrdefs'];
		$rootfolderid = $this->params['rootfolderid'];
		$strictformcheck = $this->params['strictformcheck'];
		$orderby = $this->params['orderby'];

		$this->htmlStartPage(getMLText("folder_title", array("foldername" => htmlspecialchars($folder->getName()))));
		$this->globalNavigation($folder);
		$this->contentStart();
		$this->pageNavigation($this->getFolderPathHTML($folder, true), "view_folder", $folder);
		$this->contentHeading(getMLText("edit_folder_props"));
		$this->contentContainerStart();
?>

<script language="JavaScript">
function checkForm() {
	msg = new Array();
	if (document.form1.name.value == "") msg.push("<?php printMLText("js_no_name");?>");
<?php
	if ($strictformcheck) {
?>
	if (document.form1.comment.value == "") msg.push("<?php printMLText("js_no_comment");?>");
<?php
	}
?>
	if (msg != "") {
  	noty({
  		text: msg.join('<br />'),
  		type: 'error',
      dismissQueue: true,
  		layout: 'topRight',
  		theme: 'defaultTheme',
  	});
		return false;
	}
	else
		return true;
}
</script>
<form action="../op/op.EditFolder.php" name="form1" onsubmit="return checkForm();" method="post">
<input type="hidden" name="folderid" value="<?php print $folder->getID();?>">
<input type="hidden" name="showtree" value="<?php echo showtree();?>">
<table class="table-condensed">
<tr>
<td><?php printMLText("name");?>:</td>
<td><input type="text" name="name" value="<?php print htmlspecialchars($folder->getName());?>" size="60"></td>
</tr>
<tr>
<td><?php printMLText("comment");?>:</td>
<td><textarea name="comment" rows="4" cols="80"><?php print htmlspecialchars($folder->getComment());?></textarea></td>
</tr>
<?php
		$parent = ($folder->getID() == $rootfolderid) ? false : $folder->getParent();
		if ($parent && $parent->getAccessMode($user) > M_READ) {
			print "<tr>";
			print "<td>" . getMLText("sequence") . ":</td>";
			print "<td>";
			$this->printSequenceChooser($parent->getSubFolders('s'), $folder->getID());
			if($orderby != 's') echo "<br />".getMLText('order_by_sequence_off'); 
			print "</td></tr>\n";
		}

		if($attrdefs) {
			foreach($attrdefs as $attrdef) {
				$arr = $this->callHook('folderEditAttribute', $folder, $attrdef);
				if(is_array($arr)) {
					echo $txt;
					echo "<tr>";
					echo "<td>".$arr[0]."</td>";
					echo "<td>".$arr[1]."</td>";
					echo "</tr>";
				} else {
?>
<tr>
	<td><?php echo htmlspecialchars($attrdef->getName()); ?></td>
	<td><?php $this->printAttributeEditField($attrdef, $folder->getAttribute($attrdef)) ?></td>
</tr>
<?php
				}
			}
		}
?>
<tr>
	<td></td>
	<td><button type="submit" class="btn"><i class="icon-save"></i> <?php printMLText("save"); ?></button></td>
</tr>
</table>
</form>
<?php
		$this->contentContainerEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
