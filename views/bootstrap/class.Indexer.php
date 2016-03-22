<?php
/**
 * Implementation of Indexer view
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
 * Class which outputs the html page for Indexer view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_Indexer extends SeedDMS_Bootstrap_Style {

	function tree($dms, $index, $indexconf, $folder, $indent='') { /* {{{ */
		set_time_limit(30);
		echo $indent."D ".htmlspecialchars($folder->getName())."\n";
		$subfolders = $folder->getSubFolders();
		foreach($subfolders as $subfolder) {
			$this->tree($dms, $index, $indexconf, $subfolder, $indent.'  ');
		}
		$documents = $folder->getDocuments();
		foreach($documents as $document) {
			echo $indent."  ".$document->getId().":".htmlspecialchars($document->getName())." ";
			/* If the document wasn't indexed before then just add it */
			$lucenesearch = new $indexconf['Search']($index);
			if(!($hit = $lucenesearch->getDocument($document->getId()))) {
				try {
					$index->addDocument(new $indexconf['IndexedDocument']($dms, $document, $this->converters ? $this->converters : null, false, $this->timeout));
					echo "(document added)";
				} catch(Exception $e) {
					echo $indent."(adding document failed '".$e->getMessage()."')";
				}
			} else {
				/* Check if the attribute created is set or has a value older
				 * than the lasted content. Documents without such an attribute
				 * where added when a new document was added to the dms. In such
				 * a case the document content  wasn't indexed.
				 */
				try {
					$created = (int) $hit->getDocument()->getFieldValue('created');
				} catch (/* Zend_Search_Lucene_ */Exception $e) {
					$created = 0;
				}
				$content = $document->getLatestContent();
				if($created >= $content->getDate()) {
					echo $indent."(document unchanged)";
				} else {
					$index->delete($hit->id);
					try {
						$index->addDocument(new $indexconf['IndexedDocument']($dms, $document, $this->converters ? $this->converters : null, false, $this->timeout));
						echo $indent."(document updated)";
					} catch(Exception $e) {
						print_r($e);
						echo $indent."(updating document failed)";
					}
				}
			}
			echo "\n";
		}
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$index = $this->params['index'];
		$indexconf = $this->params['indexconf'];
		$recreate = $this->params['recreate'];
		$folder = $this->params['folder'];
		$this->converters = $this->params['converters'];
		$this->timeout = $this->params['timeout'];

		$this->htmlStartPage(getMLText("admin_tools"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");
		$this->contentHeading(getMLText("update_fulltext_index"));

		echo "<pre>";
		$this->tree($dms, $index, $indexconf, $folder);
		echo "</pre>";

		$index->commit();
		$index->optimize();

		$this->htmlEndPage();
	} /* }}} */
}
?>
