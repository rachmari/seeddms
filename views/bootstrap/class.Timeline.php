<?php
/**
 * Implementation of Timeline view
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
 * Class which outputs the html page for Timeline view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_Timeline extends SeedDMS_Bootstrap_Style {

	function iteminfo() { /* {{{ */
		$dms = $this->params['dms'];
		$document = $this->params['document'];
		$version = $this->params['version'];
		$cachedir = $this->params['cachedir'];
		$previewwidthlist = $this->params['previewWidthList'];
		$previewwidthdetail = $this->params['previewWidthDetail'];
		$timeout = $this->params['timeout'];

		if($document) {
			$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidthdetail, $timeout);
			$previewer->createPreview($version);

			$this->contentHeading(getMLText("timeline_selected_item"));
			$folder = $document->getFolder();
			$path = $folder->getPath();
			print "<div>";
			print "<a href=\"../out/out.ViewDocument.php?documentid=".$document->getID()."\">/";
			for ($i = 1; $i  < count($path); $i++) {
				print htmlspecialchars($path[$i]->getName())."/";
			}
			echo $document->getName();
			print "</a>";
			print "</div>";

			print "<div>";
			if($previewer->hasPreview($version)) {
				print("<img class=\"mimeicon\" width=\"".$previewwidthdetail."\" src=\"../op/op.Preview.php?documentid=".$document->getID()."&version=".$version->getVersion()."&width=".$previewwidthdetail."\" title=\"".htmlspecialchars($version->getMimeType())."\">");
			} else {
				print "<img class=\"mimeicon\" src=\"".$this->getMimeIcon($version->getFileType())."\" title=\"".htmlspecialchars($version->getMimeType())."\">";
			}
			print "</div>";
		}
	} /* }}} */

	function data() { /* {{{ */
		$dms = $this->params['dms'];
		$skip = $this->params['skip'];
		$fromdate = $this->params['fromdate'];
		$todate = $this->params['todate'];

		if($fromdate) {
			$from = makeTsFromLongDate($fromdate.' 00:00:00');
		} else {
			$from = time()-7*86400;
		}

		if($todate) {
			$to = makeTsFromLongDate($todate.' 23:59:59');
		} else {
			$to = time()-7*86400;
		}

		if($data = $dms->getTimeline($from, $to)) {
			foreach($data as $i=>$item) {
				switch($item['type']) {
				case 'add_version':
					$msg = getMLText('timeline_full_'.$item['type'], array('document'=>htmlspecialchars($item['document']->getName()), 'version'=> $item['version']));
					break;
				case 'add_file':
					$msg = getMLText('timeline_full_'.$item['type'], array('document'=>htmlspecialchars($item['document']->getName())));
					break;
				case 'status_change':
					$msg = getMLText('timeline_full_'.$item['type'], array('document'=>htmlspecialchars($item['document']->getName()), 'version'=> $item['version'], 'status'=> getOverallStatusText($item['status'])));
					break;
				default:
					$msg = '???';
				}
				$data[$i]['msg'] = $msg;
			}
		}

		$jsondata = array();
		foreach($data as $item) {
			if($item['type'] == 'status_change')
				$classname = $item['type']."_".$item['status'];
			else
				$classname = $item['type'];
			if(!$skip || !in_array($classname, $skip)) {
				$d = makeTsFromLongDate($item['date']);
				$jsondata[] = array(
					'start'=>date('c', $d),
					'content'=>$item['msg'],
					'className'=>$classname,
					'docid'=>$item['document']->getID(),
					'version'=>isset($item['version']) ? $item['version'] : '',
					'statusid'=>isset($item['statusid']) ? $item['statusid'] : '',
					'statuslogid'=>isset($item['statuslogid']) ? $item['statuslogid'] : '',
					'fileid'=>isset($item['fileid']) ? $item['fileid'] : ''
				);
			}
		}
		header('Content-Type: application/json');
		echo json_encode($jsondata);
	} /* }}} */

	function js() { /* {{{ */
		$fromdate = $this->params['fromdate'];
		$todate = $this->params['todate'];
		$skip = $this->params['skip'];

		if($fromdate) {
			$from = makeTsFromLongDate($fromdate.' 00:00:00');
		} else {
			$from = time()-7*86400;
		}

		if($todate) {
			$to = makeTsFromLongDate($todate.' 23:59:59');
		} else {
			$to = time();
		}

		header('Content-Type: application/javascript');
?>
$(document).ready(function () {
	$('#update').click(function(ev){
		ev.preventDefault();
		$.getJSON(
			'out.Timeline.php?action=data&' + $('#form1').serialize(), 
			function(data) {
				$.each( data, function( key, val ) {
					val.start = new Date(val.start);
				});
				timeline.setData(data);
				timeline.redraw();
//				timeline.setVisibleChartRange(0,0);
			}
		);
	});
});
<?php
		$timelineurl = 'out.Timeline.php?action=data&fromdate='.date('Y-m-d', $from).'&todate='.date('Y-m-d', $to).'&skip='.urldecode(http_build_query(array('skip'=>$skip)));
		$this->printTimelineJs($timelineurl, 550, ''/*date('Y-m-d', $from)*/, ''/*date('Y-m-d', $to+1)*/, $skip);
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$fromdate = $this->params['fromdate'];
		$todate = $this->params['todate'];
		$skip = $this->params['skip'];

		if($fromdate) {
			$from = makeTsFromLongDate($fromdate.' 00:00:00');
		} else {
			$from = time()-7*86400;
		}

		if($todate) {
			$to = makeTsFromLongDate($todate.' 23:59:59');
		} else {
			$to = time();
		}

		$this->htmlAddHeader('<link href="../styles/'.$this->theme.'/timeline/timeline.css" rel="stylesheet">'."\n", 'css');
		$this->htmlAddHeader('<script type="text/javascript" src="../styles/'.$this->theme.'/timeline/timeline-min.js"></script>'."\n", 'js');
		$this->htmlAddHeader('<script type="text/javascript" src="../styles/'.$this->theme.'/timeline/timeline-locales.js"></script>'."\n", 'js');

		$this->htmlStartPage(getMLText("timeline"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");
?>

<?php
		echo "<div class=\"row-fluid\">\n";

		echo "<div class=\"span3\">\n";
		$this->contentHeading(getMLText("timeline"));
		echo "<div class=\"well\">\n";
?>
<form action="../out/out.Timeline.php" class="form form-inline" name="form1" id="form1">
	<div class="control-group">
		<label class="control-label" for="startdate"><?= getMLText('date') ?></label>
		<div class="controls">
       <span class="input-append date" style="display: inline;" id="fromdate" data-date="<?php echo date('Y-m-d', $from); ?>" data-date-format="yyyy-mm-dd" data-date-language="<?php echo str_replace('_', '-', $this->params['session']->getLanguage()); ?>">
			 <input type="text" class="input-small" name="fromdate" value="<?php echo date('Y-m-d', $from); ?>"/>
				<span class="add-on"><i class="icon-calendar"></i></span>
			</span> -
      <span class="input-append date" style="display: inline;" id="todate" data-date="<?php echo date('Y-m-d', $to); ?>" data-date-format="yyyy-mm-dd" data-date-language="<?php echo str_replace('_', '-', $this->params['session']->getLanguage()); ?>">
			<input type="text" class="input-small" name="todate" value="<?php echo date('Y-m-d', $to); ?>"/>
				<span class="add-on"><i class="icon-calendar"></i></span>
			</span>
		</div>
	</div>
	<div class="control-group">
		<label class="control-label" for="skip"><?= getMLText('exclude_items') ?></label>
		<div class="controls">
			<input type="checkbox" name="skip[]" value="add_file" <?= ($skip &&  in_array('add_file', $skip)) ? 'checked' : '' ?>> <?= getMLText('timeline_skip_add_file') ?><br />
			<input type="checkbox" name="skip[]" value="status_change_0" <?= ($skip && in_array('status_change_0', $skip)) ? 'checked' : '' ?>> <?= getMLText('timeline_skip_status_change_0') ?><br />
			<input type="checkbox" name="skip[]" value="status_change_1" <?= ($skip && in_array('status_change_1', $skip)) ? 'checked' : '' ?>> <?= getMLText('timeline_skip_status_change_1') ?><br />
			<input type="checkbox" name="skip[]" value="status_change_2" <?= ($skip && in_array('status_change_2', $skip)) ? 'checked' : '' ?>> <?= getMLText('timeline_skip_status_change_2') ?><br />
			<input type="checkbox" name="skip[]" value="status_change_3" <?= ($skip && in_array('status_change_3', $skip)) ? 'checked' : '' ?>> <?= getMLText('timeline_skip_status_change_3') ?><br />
			<input type="checkbox" name="skip[]" value="status_change_-1" <?= ($skip && in_array('status_change_-1', $skip)) ? 'checked' : '' ?>> <?= getMLText('timeline_skip_status_change_-1') ?><br />
			<input type="checkbox" name="skip[]" value="status_change_-3" <?= ($skip && in_array('status_change_-3', $skip)) ? 'checked' : '' ?>> <?= getMLText('timeline_skip_status_change_-3') ?><br />
		</div>
	</div>
	<div class="control-group">
		<label class="control-label" for="enddate"></label>
		<div class="controls">
			<button id="update" type="_submit" class="btn"><i class="icon-search"></i> <?php printMLText("update"); ?></button>
		</div>
	</div>
</form>
<?php
		echo "</div>\n";
		echo "<div class=\"ajax\" data-view=\"Timeline\" data-action=\"iteminfo\" ></div>";
		echo "</div>\n";

		echo "<div class=\"span9\">\n";
		$this->contentHeading(getMLText("timeline"));
		$this->printTimelineHtml(550);
		echo "</div>\n";
		echo "</div>\n";

		$this->contentContainerEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
