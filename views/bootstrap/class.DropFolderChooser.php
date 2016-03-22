<?php
/**
 * Implementation of CategoryChooser view
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
 * Class which outputs the html page for CategoryChooser view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_DropFolderChooser extends SeedDMS_Bootstrap_Style {

	function js() { /* {{{ */
		header('Content-Type: application/javascript');
?>
$('#fileselect').click(function(ev) {
	attr_filename = $(ev.currentTarget).attr('filename');
	fileSelected(attr_filename);
});
<?php
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$dropfolderfile = $this->params['dropfolderfile'];
		$form = $this->params['form'];
		$dropfolderdir = $this->params['dropfolderdir'];
		$cachedir = $this->params['cachedir'];
		$previewwidth = $this->params['previewWidthList'];
		$timeout = $this->params['timeout'];

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout);

//		$this->htmlStartPage(getMLText("choose_target_file"));
//		$this->globalBanner();
//		$this->pageNavigation(getMLText("choose_target_file"));
?>

<script language="JavaScript">
var targetName = document.<?php echo $form?>.dropfolderfile<?php print $form ?>;
</script>
<?php
//		$this->contentContainerStart();

		$dir = $dropfolderdir.'/'.$user->getLogin();
		/* Check if we are still looking in the configured directory and
		 * not somewhere else, e.g. if the login was '../test'
		 */
		if(dirname($dir) == $dropfolderdir) {
			if(is_dir($dir)) {
				$d = dir($dir);
				echo "<table class=\"table table-condensed\">\n";
				echo "<thead>\n";
				echo "<tr><th></th><th>".getMLText('name')."</th><th align=\"right\">".getMLText('file_size')."</th><th>".getMLText('date')."</th></tr>\n";
				echo "</thead>\n";
				echo "<tbody>\n";
				$finfo = finfo_open(FILEINFO_MIME_TYPE);
				while (false !== ($entry = $d->read())) {
					if($entry != '..' && $entry != '.') {
						if(!is_dir($entry)) {
							$mimetype = finfo_file($finfo, $dir.'/'.$entry);
							$previewer->createRawPreview($dir.'/'.$entry, 'dropfolder/', $mimetype);
							echo "<tr><td style=\"min-width: ".$previewwidth."px;\">";
							if($previewer->hasRawPreview($dir.'/'.$entry, 'dropfolder/')) {
								echo "<img class=\"mimeicon\" width=\"".$previewwidth."\"src=\"../op/op.DropFolderPreview.php?filename=".$entry."&width=".$previewwidth."\" title=\"".htmlspecialchars($mimetype)."\">";
							}
							echo "</td><td><span style=\"cursor: pointer;\" id=\"fileselect\" filename=\"".$entry."\" _onClick=\"fileSelected('".$entry."');\">".$entry."</span></td><td align=\"right\">".SeedDMS_Core_File::format_filesize(filesize($dir.'/'.$entry))."</td><td>".date('Y-m-d H:i:s', filectime($dir.'/'.$entry))."</td></tr>\n";
						}
					}
				}
				echo "</tbody>\n";
				echo "</table>\n";
		echo '<script src="../out/out.DropFolderChooser.php?action=js&'.$_SERVER['QUERY_STRING'].'"></script>'."\n";
			}
		}

//		$this->contentContainerEnd();
//		echo "</body>\n</html>\n";
//		$this->htmlEndPage();
	} /* }}} */
}
?>
