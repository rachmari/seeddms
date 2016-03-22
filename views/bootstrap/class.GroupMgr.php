<?php
/**
 * Implementation of GroupMgr view
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
 * Class which outputs the html page for GroupMgr view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_GroupMgr extends SeedDMS_Bootstrap_Style {

	function js() { /* {{{ */
		$selgroup = $this->params['selgroup'];
		$strictformcheck = $this->params['strictformcheck'];

		header("Content-type: text/javascript");
?>
function checkForm1() {
	msg = new Array();
	
	if($("#name").val() == "") msg.push("<?php printMLText("js_no_name");?>");
<?php
	if ($strictformcheck) {
?>
	if($("#comment").val() == "") msg.push("<?php printMLText("js_no_comment");?>");
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
			_timeout: 1500,
  	});
		return false;
	} else
		return true;
}

function checkForm2() {
	msg = "";
	
		if($("#userid").val() == -1) msg += "<?php printMLText("js_select_user");?>\n";

		if (msg != "") {
			noty({
				text: msg,
				type: 'error',
				dismissQueue: true,
				layout: 'topRight',
				theme: 'defaultTheme',
				_timeout: 1500,
			});
			return false;
		} else
			return true;
	}

$(document).ready( function() {
	$('body').on('submit', '#form_1', function(ev){
		if(checkForm1())
			return;
		event.preventDefault();
	});

	$('body').on('submit', '#form_2', function(ev){
		if(checkForm2())
			return;
		event.preventDefault();
	});

	$( "#selector" ).change(function() {
		$('div.ajax').trigger('update', {groupid: $(this).val()});
	});
});
<?php
	} /* }}} */

	function info() { /* {{{ */
		$dms = $this->params['dms'];
		$selgroup = $this->params['selgroup'];
		$cachedir = $this->params['cachedir'];
		$previewwidth = $this->params['previewWidthList'];
		$workflowmode = $this->params['workflowmode'];
		$timeout = $this->params['timeout'];

		if($selgroup) {
			$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout);
			$this->contentHeading(getMLText("group_info"));
			echo "<table class=\"table table-condensed\">\n";
			if($workflowmode == "traditional") {
				$reviewstatus = $selgroup->getReviewStatus();
				$i = 0;
				foreach($reviewstatus as $rv) {
					if($rv['status'] == 0) {
						$i++;
					}
				}
			}
			if($workflowmode == "traditional" || $workflowmode == 'traditional_only_approval') {
				echo "<tr><td>".getMLText('pending_reviews')."</td><td>".$i."</td></tr>";
				$approvalstatus = $selgroup->getApprovalStatus();
				$i = 0;
				foreach($approvalstatus as $rv) {
					if($rv['status'] == 0) {
						$i++;
					}
				}
				echo "<tr><td>".getMLText('pending_approvals')."</td><td>".$i."</td></tr>";
			}
			if($workflowmode == 'advanced') {
				$workflowStatus = $selgroup->getWorkflowStatus();
				if($workflowStatus)
					echo "<tr><td>".getMLText('pending_workflows')."</td><td>".count($workflowStatus)."</td></tr>\n";
			}
			echo "</table>";
		}
	} /* }}} */

	function showGroupForm($group) { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$allUsers = $this->params['allusers'];
		$groups = $this->params['allgroups'];
?>
	<form action="../op/op.GroupMgr.php" name="form_1" id="form_1" method="post">
<?php
		if($group) {
			echo createHiddenFieldWithKey('editgroup');
?>
	<input type="hidden" name="groupid" value="<?php print $group->getID();?>">
	<input type="hidden" name="action" value="editgroup">
<?php
		} else {
			echo createHiddenFieldWithKey('addgroup');
?>
	<input type="hidden" name="action" value="addgroup">
<?php
		}
?>
	<table class="table-condensed">
<?php
		if($group) {
?>
		<tr>
			<td></td>
			<td><a href="../out/out.RemoveGroup.php?groupid=<?php print $group->getID();?>" class="btn"><i class="icon-remove"></i> <?php printMLText("rm_group");?></a></td>
		</tr>
<?php
		}
?>
		<tr>
			<td><?php printMLText("name");?>:</td>
			<td><input type="text" name="name" id="name" value="<?php print $group ? htmlspecialchars($group->getName()) : '';?>"></td>
		</tr>
		<tr>
			<td><?php printMLText("comment");?>:</td>
			<td><textarea name="comment" id="comment" rows="4" cols="50"><?php print $group ? htmlspecialchars($group->getComment()) : '';?></textarea></td>
		</tr>
		<tr>
			<td></td>
			<td><button type="submit" class="btn"><i class="icon-save"></i> <?php printMLText("save")?></button></td>
		</tr>
	</table>
	</form>
<?php
		if($group) {
			$this->contentSubHeading(getMLText("group_members"));
?>
		<table class="table-condensed">
<?php
			$members = $group->getUsers();
			if (count($members) == 0)
				print "<tr><td>".getMLText("no_group_members")."</td></tr>";
			else {
			
				foreach ($members as $member) {
				
					print "<tr>";
					print "<td><i class=\"icon-user\"></i></td>";
					print "<td>" . htmlspecialchars($member->getFullName()) . "</td>";
					print "<td>" . ($group->isMember($member,true)?getMLText("manager"):"&nbsp;") . "</td>";
					print "<td>";
					print "<form action=\"../op/op.GroupMgr.php\" method=\"post\" class=\"form-inline\" style=\"display: inline-block; margin-bottom: 0px;\"><input type=\"hidden\" name=\"action\" value=\"rmmember\" /><input type=\"hidden\" name=\"groupid\" value=\"".$group->getID()."\" /><input type=\"hidden\" name=\"userid\" value=\"".$member->getID()."\" />".createHiddenFieldWithKey('rmmember')."<button type=\"submit\" class=\"btn btn-mini\"><i class=\"icon-remove\"></i> ".getMLText("delete")."</button></form>";
					print "&nbsp;";
					print "<form action=\"../op/op.GroupMgr.php\" method=\"post\" class=\"form-inline\" style=\"display: inline-block; margin-bottom: 0px;\"><input type=\"hidden\" name=\"groupid\" value=\"".$group->getID()."\" /><input type=\"hidden\" name=\"action\" value=\"tmanager\" /><input type=\"hidden\" name=\"userid\" value=\"".$member->getID()."\" />".createHiddenFieldWithKey('tmanager')."<button type=\"submit\" class=\"btn btn-mini\"><i class=\"icon-random\"></i> ".getMLText("toggle_manager")."</button></form>";
					print "</td></tr>";
				}
			}
?>
		</table>
		
<?php
			$this->contentSubHeading(getMLText("add_member"));
?>
		
		<form class="form-inline" action="../op/op.GroupMgr.php" method="POST" name="form_2" id="form_2" _onsubmit="return checkForm2('<?php print $group->getID();?>');">
		<?php echo createHiddenFieldWithKey('addmember'); ?>
		<input type="Hidden" name="action" value="addmember">
		<input type="Hidden" name="groupid" value="<?php print $group->getID();?>">
		<table class="table-condensed">
			<tr>
				<td>
					<select name="userid" id="userid">
						<option value="-1"><?php printMLText("select_one");?>
						<?php
							foreach ($allUsers as $currUser)
								if (!$group->isMember($currUser))
									print "<option value=\"".$currUser->getID()."\">" . htmlspecialchars($currUser->getLogin()." - ".$currUser->getFullName()) . "\n";
						?>
					</select>
				</td>
				<td>
					<label class="checkbox"><input type="checkbox" name="manager" value="1"><?php printMLText("manager");?></label>
				</td>
				<td>
					<input type="submit" class="btn" value="<?php printMLText("add");?>">
				</td>
			</tr>
		</table>
		</form>
<?php
		}
	} /* }}} */

	function form() { /* {{{ */
		$selgroup = $this->params['selgroup'];

		$this->showGroupForm($selgroup);
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$selgroup = $this->params['selgroup'];
		$allUsers = $this->params['allusers'];
		$allGroups = $this->params['allgroups'];
		$strictformcheck = $this->params['strictformcheck'];

		$this->htmlStartPage(getMLText("admin_tools"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");

		$this->contentHeading(getMLText("group_management"));
?>

<div class="row-fluid">
<div class="span4">
<div class="well">
<?php echo getMLText("selection")?>:
<select class="chzn-select" id="selector">
<option value="-1"><?php echo getMLText("choose_group")?>
<option value="0"><?php echo getMLText("add_group")?>
<?php
		foreach ($allGroups as $group) {
			print "<option value=\"".$group->getID()."\" ".($selgroup && $group->getID()==$selgroup->getID() ? 'selected' : '').">" . htmlspecialchars($group->getName());
		}
?>
</select>
</div>
<div class="ajax" data-view="GroupMgr" data-action="info" <?php echo ($selgroup ? "data-query=\"groupid=".$selgroup->getID()."\"" : "") ?>></div>
</div>

<div class="span8">
<div class="well">
<div class="ajax" data-view="GroupMgr" data-action="form" <?php echo ($selgroup ? "data-query=\"groupid=".$selgroup->getID()."\"" : "") ?>></div>
</div>
</div>
</div>

<?php
		$this->contentContainerEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
