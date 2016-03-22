<?php
/**
 * Implementation of AttributeMgr view
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
 * Class which outputs the html page for AttributeMgr view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_AttributeMgr extends SeedDMS_Bootstrap_Style {

	function js() { /* {{{ */
		$selattrdef = $this->params['selattrdef'];
		header('Content-Type: application/javascript');
?>

$(document).ready( function() {
	$('body').on('submit', '#form', function(ev){
//		if(checkForm()) return;
//		event.preventDefault();
	});
	$( "#selector" ).change(function() {
		$('div.ajax').trigger('update', {attrdefid: $(this).val()});
	});
});
<?php
		$this->printDeleteFolderButtonJs();
		$this->printDeleteDocumentButtonJs();
	} /* }}} */

	function info() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$attrdefs = $this->params['attrdefs'];
		$selattrdef = $this->params['selattrdef'];
		$cachedir = $this->params['cachedir'];
		$previewwidth = $this->params['previewWidthList'];
		$enableRecursiveCount = $this->params['enableRecursiveCount'];
		$maxRecursiveCount = $this->params['maxRecursiveCount'];
		$timeout = $this->params['timeout'];

		if($selattrdef) {
			$this->contentHeading(getMLText("attrdef_info"));
			$res = $selattrdef->getStatistics(30);
			if(!empty($res['frequencies']['document']) ||!empty($res['frequencies']['folder']) ||!empty($res['frequencies']['content'])) {


?>
    <div class="accordion" id="accordion1">
      <div class="accordion-group">
        <div class="accordion-heading">
          <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion1"       href="#collapseOne">
						<?php printMLText('attribute_value'); ?>
          </a>
        </div>
        <div id="collapseOne" class="accordion-body collapse" style="height: 0px;">
          <div class="accordion-inner">
<?php
			foreach(array('document', 'folder', 'content') as $type) {
				if(isset($res['frequencies'][$type]) && $res['frequencies'][$type]) {
					print "<table class=\"table table-condensed\">";
					print "<thead>\n<tr>\n";
					print "<th>".getMLText("attribute_value")."</th>\n";
					print "<th>".getMLText("attribute_count")."</th>\n";
					print "</tr></thead>\n<tbody>\n";
					foreach($res['frequencies'][$type] as $entry) {
						echo "<tr><td>".$entry['value']."</td><td>".$entry['c']."</td></tr>";
					}
					print "</tbody></table>";
				}
			}
?>
          </div>
        </div>
      </div>
     </div>
<?php
			}
			if($res['folders'] || $res['docs']) {
				print "<table id=\"viewfolder-table\" class=\"table table-condensed\">";
				print "<thead>\n<tr>\n";
				print "<th></th>\n";	
				print "<th>".getMLText("name")."</th>\n";
				print "<th>".getMLText("status")."</th>\n";
				print "<th>".getMLText("action")."</th>\n";
				print "</tr>\n</thead>\n<tbody>\n";
				foreach($res['folders'] as $subFolder) {
					echo $this->folderListRow($subFolder);
				}
				$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout);
				foreach($res['docs'] as $document) {
					echo $this->documentListRow($document, $previewer);
				}

				echo "</tbody>\n</table>\n";
			}

			if($res['contents']) {
				print "<table id=\"viewfolder-table\" class=\"table\">";
				print "<thead>\n<tr>\n";
				print "<th></th>\n";	
				print "<th>".getMLText("name")."</th>\n";
				print "<th>".getMLText("status")."</th>\n";
				print "<th>".getMLText("action")."</th>\n";
				print "</tr>\n</thead>\n<tbody>\n";
				$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout);
				foreach($res['contents'] as $content) {
					$doc = $content->getDocument();
					echo $this->documentListRow($doc, $previewer);
				}
				print "</tbody></table>";
			}
		}
	} /* }}} */

	function showAttributeForm($attrdef) { /* {{{ */
		if($attrdef && !$attrdef->isUsed()) {
?>
			<form style="display: inline-block;" method="post" action="../op/op.AttributeMgr.php" >
				<?php echo createHiddenFieldWithKey('removeattrdef'); ?>
				<input type="hidden" name="attrdefid" value="<?php echo $attrdef->getID()?>">
				<input type="hidden" name="action" value="removeattrdef">
				<button type="submit" class="btn"><i class="icon-remove"></i> <?php echo getMLText("rm_attrdef")?></button>
			</form>
<?php
		}
?>
			<form action="../op/op.AttributeMgr.php" method="post">
<?php
		if($attrdef) {
			echo createHiddenFieldWithKey('editattrdef');
?>
			<input type="hidden" name="action" value="editattrdef">
			<input type="hidden" name="attrdefid" value="<?php echo $attrdef->getID()?>" />
<?php
		} else {
  		echo createHiddenFieldWithKey('addattrdef');
?>
			<input type="hidden" name="action" value="addattrdef">
<?php
		}
?>
				<table class="table-condensed">
					<tr>
						<td>
								<?php printMLText("attrdef_name");?>:
						</td>
						<td>
							<input type="text" name="name" value="<?php echo $attrdef ? htmlspecialchars($attrdef->getName()) : '' ?>">
						</td>
					</tr>
					<tr>
						<td>
							<?php printMLText("attrdef_objtype");?>:
						</td>
						<td>
							<select name="objtype"><option value="<?php echo SeedDMS_Core_AttributeDefinition::objtype_all ?>">All</option><option value="<?php echo SeedDMS_Core_AttributeDefinition::objtype_folder ?>" <?php if($attrdef && $attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_folder) echo "selected"; ?>>Folder</option><option value="<?php echo SeedDMS_Core_AttributeDefinition::objtype_document ?>" <?php if($attrdef && $attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_document) echo "selected"; ?>>Document</option><option value="<?php echo SeedDMS_Core_AttributeDefinition::objtype_documentcontent ?>" <?php if($attrdef && $attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_documentcontent) echo "selected"; ?>>Document content</option></select>
						</td>
					</tr>
					<tr>
						<td>
							<?php printMLText("attrdef_type");?>:
						</td>
						<td>
							<select name="type"><option value="<?php echo SeedDMS_Core_AttributeDefinition::type_int ?>" <?php if($attrdef && $attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_int) echo "selected"; ?>><?php printMLText('attrdef_type_int'); ?></option><option value="<?php echo SeedDMS_Core_AttributeDefinition::type_float ?>" <?php if($attrdef && $attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_float) echo "selected"; ?>><?php printMLText('attrdef_type_float'); ?></option><option value="<?php echo SeedDMS_Core_AttributeDefinition::type_string ?>" <?php if($attrdef && $attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_string) echo "selected"; ?>><?php printMLText('attrdef_type_string'); ?></option><option value="<?php echo SeedDMS_Core_AttributeDefinition::type_boolean ?>" <?php if($attrdef && $attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_boolean) echo "selected"; ?>><?php printMLText('attrdef_type_boolean'); ?></option><option value="<?php echo SeedDMS_Core_AttributeDefinition::type_date ?>" <?php if($attrdef && $attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_date) echo "selected"; ?>><?php printMLText('attrdef_type_date'); ?></option><option value="<?php echo SeedDMS_Core_AttributeDefinition::type_email ?>" <?php if($attrdef && $attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_email) echo "selected"; ?>><?php printMLText('attrdef_type_email'); ?></option><option value="<?php echo SeedDMS_Core_AttributeDefinition::type_url ?>" <?php if($attrdef && $attrdef->getType() == SeedDMS_Core_AttributeDefinition::type_url) echo "selected"; ?>><?php printMLText('attrdef_type_url'); ?></option></select>
						</td>
					</tr>
					<tr>
						<td>
							<?php printMLText("attrdef_multiple");?>:
						</td>
						<td>
							<input type="checkbox" value="1" name="multiple" <?php echo ($attrdef && $attrdef->getMultipleValues()) ? "checked" : "" ?>/>
						</td>
					</tr>
					<tr>
						<td>
							<?php printMLText("attrdef_minvalues");?>:
						</td>
						<td>
							<input type="text" value="<?php echo $attrdef ? $attrdef->getMinValues() : '' ?>" name="minvalues" />
						</td>
					</tr>
					<tr>
						<td>
							<?php printMLText("attrdef_maxvalues");?>:
						</td>
						<td>
							<input type="text" value="<?php echo $attrdef ? $attrdef->getMaxValues() : '' ?>" name="maxvalues" />
						</td>
					</tr>
					<tr>
						<td>
							<?php printMLText("attrdef_valueset");?>:
						</td>
						<td>
							<input type="text" value="<?php echo $attrdef ? $attrdef->getValueSet() : '' ?>" name="valueset" />
						</td>
					</tr>
					<tr>
						<td>
							<?php printMLText("attrdef_regex");?>:
						</td>
						<td>
							<input type="text" value="<?php echo $attrdef ? $attrdef->getRegex() : '' ?>" name="regex" />
						</td>
					</tr>
					<tr>
						<td></td>
						<td>
							<button type="submit" class="btn"><i class="icon-save"></i> <?php printMLText("save");?></button>
						</td>
					</tr>
				</table>
			</form>
<?php
} /* }}} */

	function form() { /* {{{ */
		$selattrdef = $this->params['selattrdef'];

		$this->showAttributeForm($selattrdef);
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$attrdefs = $this->params['attrdefs'];
		$selattrdef = $this->params['selattrdef'];

		$this->htmlAddHeader('<script type="text/javascript" src="../styles/'.$this->theme.'/bootbox/bootbox.min.js"></script>'."\n", 'js');

		$this->htmlStartPage(getMLText("admin_tools"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");
		$this->contentHeading(getMLText("attrdef_management"));
?>

<div class="row-fluid">
<div class="span6">
<div class="well">
<?php echo getMLText("selection")?>:
	<select class="chzn-select" id="selector" class="span9">
		<option value="-1"><?php echo getMLText("choose_attrdef")?>
		<option value="0"><?php echo getMLText("new_attrdef")?>
<?php
		if($attrdefs) {
			foreach ($attrdefs as $attrdef) {
				switch($attrdef->getObjType()) {
					case SeedDMS_Core_AttributeDefinition::objtype_all:
						$ot = getMLText("all");
						break;
					case SeedDMS_Core_AttributeDefinition::objtype_folder:
						$ot = getMLText("folder");
						break;
					case SeedDMS_Core_AttributeDefinition::objtype_document:
						$ot = getMLText("document");
						break;
					case SeedDMS_Core_AttributeDefinition::objtype_documentcontent:
						$ot = getMLText("version");
						break;
				}
				switch($attrdef->getType()) {
					case SeedDMS_Core_AttributeDefinition::type_int:
						$t = getMLText("attrdef_type_int");
						break;
					case SeedDMS_Core_AttributeDefinition::type_float:
						$t = getMLText("attrdef_type_float");
						break;
					case SeedDMS_Core_AttributeDefinition::type_string:
						$t = getMLText("attrdef_type_string");
						break;
					case SeedDMS_Core_AttributeDefinition::type_date:
						$t = getMLText("attrdef_type_date");
						break;
					case SeedDMS_Core_AttributeDefinition::type_boolean:
						$t = getMLText("attrdef_type_boolean");
						break;
				}
				print "<option value=\"".$attrdef->getID()."\" ".($selattrdef && $attrdef->getID()==$selattrdef->getID() ? 'selected' : '').">" . htmlspecialchars($attrdef->getName() ." (".$ot.", ".$t.")");
			}
		}
?>
	</select>
</div>
<div class="ajax" data-view="AttributeMgr" data-action="info" <?php echo ($selattrdef ? "data-query=\"attrdefid=".$selattrdef->getID()."\"" : "") ?>></div>
</div>

<div class="span6">
	<div class="well">
		<div class="ajax" data-view="AttributeMgr" data-action="form" <?php echo ($selattrdef ? "data-query=\"attrdefid=".$selattrdef->getID()."\"" : "") ?>></div>
	</div>
</div>

<?php
		$this->htmlEndPage();

	} /* }}} */
}
?>
