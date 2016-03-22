<?php
/**
 * Implementation of Categories view
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
 * Class which outputs the html page for Categories view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_Categories extends SeedDMS_Bootstrap_Style {

	function js() { /* {{{ */
		$selcat = $this->params['selcategory'];
		header('Content-Type: application/javascript');
?>
$(document).ready( function() {
	$( "#selector" ).change(function() {
		$('div.ajax').trigger('update', {categoryid: $(this).val()});
	});
});
<?php
	} /* }}} */

	function info() { /* {{{ */
		$dms = $this->params['dms'];
		$selcat = $this->params['selcategory'];

		if($selcat) {
			$this->contentHeading(getMLText("category_info"));
			$documents = $selcat->getDocumentsByCategory();
			echo "<table class=\"table table-condensed\">\n";
			echo "<tr><td>".getMLText('document_count')."</td><td>".(count($documents))."</td></tr>\n";
			echo "</table>";
		}
	} /* }}} */

	function showCategoryForm($category) { /* {{{ */
?>
			<table class="table-condensed">
				<tr>
					<td></td><td>
<?php
		if($category && !$category->isUsed()) {
?>
						<form style="display: inline-block;" method="post" action="../op/op.Categories.php" >
						<?php echo createHiddenFieldWithKey('removecategory'); ?>
						<input type="Hidden" name="categoryid" value="<?php echo $category->getID()?>">
						<input type="Hidden" name="action" value="removecategory">
						<button class="btn" type="submit"><i class="icon-remove"></i> <?php echo getMLText("rm_document_category")?></button>
						</form>
<?php
		} else {
?>
						<p><?php echo getMLText('category_in_use') ?></p>
<?php
		}
?>
					</td>
				</tr>
				<tr>
					<td><?php echo getMLText("name")?>:</td>
					<td>
						<form class="form-inline" style="margin-bottom: 0px;" action="../op/op.Categories.php" method="post">
						<?php if(!$category) { ?>
							<?php echo createHiddenFieldWithKey('addcategory'); ?>
							<input type="Hidden" name="action" value="addcategory">
						<?php } else { ?>
  		        <?php echo createHiddenFieldWithKey('editcategory'); ?>
							<input type="Hidden" name="action" value="editcategory">
							<input type="Hidden" name="categoryid" value="<?php echo $category->getID()?>">
						<?php } ?>
							<input name="name" type="text" value="<?php echo $category ? htmlspecialchars($category->getName()) : '' ?>">&nbsp;
							<button type="submit" class="btn"><i class="icon-save"></i> <?php printMLText("save");?></button>
						</form>
					</td>
				</tr>
				
			</table>
<?php
	} /* }}} */

	function form() { /* {{{ */
		$selcat = $this->params['selcategory'];

		$this->showCategoryForm($selcat);
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$categories = $this->params['categories'];
		$selcat = $this->params['selcategory'];

		$this->htmlStartPage(getMLText("admin_tools"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");

		$this->contentHeading(getMLText("global_document_categories"));
?>
<div class="row-fluid">
	<div class="span4">
		<div class="well">
<?php echo getMLText("selection")?>:
			<select id="selector" class="span9">
				<option value="-1"><?php echo getMLText("choose_category")?>
				<option value="0"><?php echo getMLText("new_document_category")?>
<?php
				foreach ($categories as $category) {
					print "<option value=\"".$category->getID()."\" ".($selcat && $category->getID()==$selcat->getID() ? 'selected' : '').">" . htmlspecialchars($category->getName());
				}
?>
			</select>
		</div>
		<div class="ajax" data-view="Categories" data-action="info" <?php echo ($selcat ? "data-query=\"categoryid=".$selcat->getID()."\"" : "") ?>></div>
	</div>

	<div class="span8">
		<div class="well">
			<div class="ajax" data-view="Categories" data-action="form" <?php echo ($selcat ? "data-query=\"categoryid=".$selcat->getID()."\"" : "") ?>></div>

		</div>
	</div>
</div>
	
<?php
		$this->htmlEndPage();
	} /* }}} */
}
?>
