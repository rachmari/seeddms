<?php
//    MyDMS. Document Management System
//    Copyright (C) 2012 Uwe Steinmann
//
//    This program is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program; if not, write to the Free Software
//    Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

include("../inc/inc.Settings.php");
include("../inc/inc.LogInit.php");
include("../inc/inc.Utils.php");
include("../inc/inc.Language.php");
include("../inc/inc.Init.php");
include("../inc/inc.Extension.php");
include("../inc/inc.Init.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.ClassEmailNotify.php");
include("../inc/inc.ClassUI.php");

require_once("../inc/inc.ClassSession.php");
include("../inc/inc.ClassPasswordStrength.php");
include("../inc/inc.ClassPasswordHistoryManager.php");

/* Load session */
if (isset($_COOKIE["mydms_session"])) {
	$dms_session = $_COOKIE["mydms_session"];
	$session = new SeedDMS_Session($db);
	if(!$resArr = $session->load($dms_session)) {
		echo json_encode(array('error'=>1));
		exit;
	}

	/* Update last access time */
	$session->updateAccess($dms_session);

	/* Load user data */
	$user = $dms->getUser($resArr["userID"]);
	if (!is_object($user)) {
		echo json_encode(array('error'=>1));
		exit;
	}
	$dms->setUser($user);
	if($user->isAdmin()) {
		if($resArr["su"]) {
			$user = $dms->getUser($resArr["su"]);
		}
	}
	if($settings->_enableEmail) {
		$notifier = new SeedDMS_EmailNotify();
		$notifier->setSender($user);
	} else {
		$notifier = null;
	}
	include $settings->_rootDir . "languages/" . $resArr["language"] . "/lang.inc";
} else {
	$user = null;
}

$command = $_REQUEST["command"];
switch($command) {
	case 'checkpwstrength': /* {{{ */
		$ps = new Password_Strength();
		$ps->set_password($_REQUEST["pwd"]);
		if($settings->_passwordStrengthAlgorithm == 'simple')
			$ps->simple_calculate();
		else
			$ps->calculate();
		$score = $ps->get_score();
		if($settings->_passwordStrength) {
			if($score >= $settings->_passwordStrength) {
				echo json_encode(array('error'=>0, 'strength'=>$score, 'score'=>$score/$settings->_passwordStrength, 'ok'=>1));
			} else {
				echo json_encode(array('error'=>0, 'strength'=>$score, 'score'=>$score/$settings->_passwordStrength, 'ok'=>0));
			}
		} else {
			echo json_encode(array('error'=>0, 'strength'=>$score));
		}
		break; /* }}} */

	case 'sessioninfo': /* {{{ */
		if($user) {
			echo json_encode($resArr);
		}	
		break; /* }}} */

	case 'searchnumber': /* {{{ */
		$numbers = $_GET['query'];
		$db = $dms->getDB();
		$exists = array();
		$missing = array();

		foreach($numbers as $number) {
			$docID = $dms->getDocumentIDByNumber($number);
			if($docID) {
				$queryStr="SELECT name AS title FROM tblDocuments WHERE id='".$docID."'";
				$resArr = $db->getResultArray($queryStr);
				$docInfo = array("number" => $number, "title" => $resArr[0]['title']);
				array_push($exists, $docInfo);
			} else {
				array_push($missing, $number);
			}
		}
		$results = array("exists"=>$exists, "missing"=>$missing);
		header('Content-Type: application/json');
		echo json_encode($results);
		break; /* }}} */

	case 'searchdocument': /* {{{ */
		if($user) {
			$query = $_GET['query'];

			$hits = $dms->search($query, $limit=0, $offset=0, $logicalmode='AND', $searchin=array(), $startFolder=null, $owner=null, $status = array(), $creationstartdate=array(), $creationenddate=array(), $modificationstartdate=array(), $modificationenddate=array(), $categories=array(), $attributes=array(), $mode=0x1, $expirationstartdate=array(), $expirationenddate=array());
			if($hits) {
				$result = array();
				foreach($hits['docs'] as $hit) {
					$result[] = $hit->getID().'#'.$hit->getName();
				}
				header('Content-Type: application/json');
				echo json_encode($result);
			}
		}
		break; /* }}} */

	case 'searchfolder': /* {{{ */
		if($user) {
			$query = $_GET['query'];

			$hits = $dms->search($query, $limit=0, $offset=0, $logicalmode='AND', $searchin=array(), $startFolder=null, $owner=null, $status = array(), $creationstartdate=array(), $creationenddate=array(), $modificationstartdate=array(), $modificationenddate=array(), $categories=array(), $attributes=array(), $mode=0x2, $expirationstartdate=array(), $expirationenddate=array());
			if($hits) {
				$result = array();
				foreach($hits['folders'] as $hit) {
					$result[] = $hit->getID().'#'.$hit->getName();
				}
				header('Content-Type: application/json');
				echo json_encode($result);
			}
		}
		break; /* }}} */

	case 'subtree': /* {{{ */
		if($user) {
			if(empty($_GET['node']))
				$nodeid = $settings->_rootFolderID;
			else
				$nodeid = (int) $_GET['node'];
			if(empty($_GET['showdocs']))
				$showdocs = false;
			else
				$showdocs = true;
			if(empty($_GET['orderby']))
				$orderby = $settings->_sortFoldersDefault;
			else
				$orderby = $_GET['orderby'];

			$folder = $dms->getFolder($nodeid);
			if (!is_object($folder)) return '';
			
			$subfolders = $folder->getSubFolders($orderby);
			$subfolders = SeedDMS_Core_DMS::filterAccess($subfolders, $user, M_READ);
			$tree = array();
			foreach($subfolders as $subfolder) {
				$loadondemand = $subfolder->hasSubFolders() || ($subfolder->hasDocuments() && $showdocs);
				$level = array('label'=>$subfolder->getName(), 'id'=>$subfolder->getID(), 'load_on_demand'=>$loadondemand, 'is_folder'=>true);
				if(!$subfolder->hasSubFolders())
					$level['children'] = array();
				$tree[] = $level;
			}
			if($showdocs) {
				$documents = $folder->getDocuments($orderby);
				$documents = SeedDMS_Core_DMS::filterAccess($documents, $user, M_READ);
				foreach($documents as $document) {
					$level = array('label'=>$document->getName(), 'id'=>$document->getID(), 'load_on_demand'=>false, 'is_folder'=>false);
					$tree[] = $level;
				}
			}

			echo json_encode($tree);
	//		echo json_encode(array(array('label'=>'test1', 'id'=>1, 'load_on_demand'=> true), array('label'=>'test2', 'id'=>2, 'load_on_demand'=> true)));
		}
		break; /* }}} */

	case 'addtoclipboard': /* {{{ */
		if($user) {
			if (isset($_GET["id"]) && is_numeric($_GET["id"]) && isset($_GET['type'])) {
				switch($_GET['type']) {
					case "folder":
						$session->addToClipboard($dms->getFolder($_GET['id']));
						break;
					case "document":
						$session->addToClipboard($dms->getDocument($_GET['id']));
						break;
				}
				header('Content-Type: application/json');
				echo json_encode(array('success'=>true, 'message'=>getMLText('splash_added_to_clipboard')));
			} else {
				header('Content-Type: application/json');
				echo json_encode(array('success'=>false, 'message'=>getMLText('error')));
			}
		}
		break; /* }}} */

	case 'removefromclipboard': /* {{{ */
		if($user) {
			if (isset($_GET["id"]) && is_numeric($_GET["id"]) && isset($_GET['type'])) {
				switch($_GET['type']) {
					case "folder":
						$session->removeFromClipboard($dms->getFolder($_GET['id']));
						break;
					case "document":
						$session->removeFromClipboard($dms->getDocument($_GET['id']));
						break;
				}
				header('Content-Type: application/json');
				echo json_encode(array('success'=>true));
			} else {
				header('Content-Type: application/json');
				echo json_encode(array('success'=>false, 'message'=>getMLText('error')));
			}
		}
		break; /* }}} */

	case 'testmail': /* {{{ */
		if($user && $user->isAdmin()) {
			if($user->getEmail()) {
				$emailobj = new SeedDMS_EmailNotify($settings->_smtpSendFrom, $settings->_smtpServer, $settings->_smtpPort, $settings->_smtpUser, $settings->_smtpPassword);
				$params = array();

				if($emailobj->toIndividual($settings->_smtpSendFrom, $user, "testmail_subject", "testmail_body", $params)) {
					echo json_encode(array("error"=>0, "msg"=>"Sending email succeded"));
				} else {
					echo json_encode(array("error"=>1, "msg"=>"Sending email failed"));
				}
			} else {
				echo json_encode(array("error"=>1, "msg"=>"No email address"));
			}
		}
		break; /* }}} */

	case 'movefolder': /* {{{ */
		if($user) {
			if(!checkFormKey('movefolder', 'GET')) {
				header('Content-Type: application/json');
				echo json_encode(array('success'=>false, 'message'=>getMLText('invalid_request_token'), 'data'=>''));
			} else {
				$mfolder = $dms->getFolder($_REQUEST['folderid']);
				if($mfolder) {
					if ($mfolder->getAccessMode($user) >= M_READ) {
						if($folder = $dms->getFolder($_REQUEST['targetfolderid'])) {
							if($folder->getAccessMode($user) >= M_READWRITE) {
								if($mfolder->setParent($folder)) {
									header('Content-Type: application/json');
									echo json_encode(array('success'=>true, 'message'=>'Folder moved', 'data'=>''));
								} else {
									header('Content-Type: application/json');
									echo json_encode(array('success'=>false, 'message'=>'Error moving folder', 'data'=>''));
								}
							} else {
								header('Content-Type: application/json');
								echo json_encode(array('success'=>false, 'message'=>'No access on destination folder', 'data'=>''));
							}
						} else {
							header('Content-Type: application/json');
							echo json_encode(array('success'=>false, 'message'=>'No destination folder', 'data'=>''));
						}
					} else {
						header('Content-Type: application/json');
						echo json_encode(array('success'=>false, 'message'=>'No access', 'data'=>''));
					}
				} else {
					header('Content-Type: application/json');
					echo json_encode(array('success'=>false, 'message'=>'No folder', 'data'=>''));
				}
			}
		}
		break; /* }}} */

	case 'movedocument': /* {{{ */
		if($user) {
			if(!checkFormKey('movedocument', 'GET')) {
				header('Content-Type: application/json');
				echo json_encode(array('success'=>false, 'message'=>getMLText('invalid_request_token'), 'data'=>''));
			} else {
				$mdocument = $dms->getDocument($_REQUEST['docid']);
				if($mdocument) {
					if ($mdocument->getAccessMode($user) >= M_READ) {
						if($folder = $dms->getFolder($_REQUEST['targetfolderid'])) {
							if($folder->getAccessMode($user) >= M_READWRITE) {
								if($mdocument->setFolder($folder)) {
									header('Content-Type: application/json');
									echo json_encode(array('success'=>true, 'message'=>'Document moved', 'data'=>''));
								} else {
									header('Content-Type: application/json');
									echo json_encode(array('success'=>false, 'message'=>'Error moving folder', 'data'=>''));
								}
							} else {
								header('Content-Type: application/json');
								echo json_encode(array('success'=>false, 'message'=>'No access on destination folder', 'data'=>''));
							}
						} else {
							header('Content-Type: application/json');
							echo json_encode(array('success'=>false, 'message'=>'No destination folder', 'data'=>''));
						}
					} else {
						header('Content-Type: application/json');
						echo json_encode(array('success'=>false, 'message'=>'No access', 'data'=>''));
					}
				} else {
					header('Content-Type: application/json');
					echo json_encode(array('success'=>false, 'message'=>'No folder', 'data'=>''));
				}
			}
		}
		break; /* }}} */

	case 'deletefolder': /* {{{ */
		if($user) {
			if(!checkFormKey('removefolder', 'GET')) {
				header('Content-Type: application/json');
				echo json_encode(array('success'=>false, 'message'=>getMLText('invalid_request_token'), 'data'=>''));
			} else {
				$folder = $dms->getFolder($_REQUEST['id']);
				if($folder) {
					if ($folder->getAccessMode($user) >= M_READWRITE) {
						if($folder->remove()) {
							header('Content-Type: application/json');
							echo json_encode(array('success'=>true, 'message'=>'', 'data'=>''));
						} else {
							header('Content-Type: application/json');
							echo json_encode(array('success'=>false, 'message'=>'Error removing folder', 'data'=>''));
						}
					} else {
						header('Content-Type: application/json');
						echo json_encode(array('success'=>false, 'message'=>'No access', 'data'=>''));
					}
				} else {
					header('Content-Type: application/json');
					echo json_encode(array('success'=>false, 'message'=>'No folder', 'data'=>''));
				}
			}
		}
		break; /* }}} */

	case 'deletedocument': /* {{{ */
		if($user) {
			if(!checkFormKey('removedocument', 'GET')) {
				header('Content-Type: application/json');
				echo json_encode(array('success'=>false, 'message'=>getMLText('invalid_request_token'), 'data'=>''));
			} else {
				$document = $dms->getDocument($_REQUEST['id']);
				if($document) {
					if ($document->getAccessMode($user) >= M_READWRITE) {
						$folder = $document->getFolder();
						/* Get the notify list before removing the document */
						$dnl =	$document->getNotifyList();
						$fnl =	$folder->getNotifyList();
						$nl = array(
							'users'=>array_merge($dnl['users'], $fnl['users']),
							'groups'=>array_merge($dnl['groups'], $fnl['groups'])
						);
						$docname = $document->getName();
						if($document->remove()) {
							/* Remove the document from the fulltext index */
							if($settings->_enableFullSearch) {
								$index = $indexconf['Indexer']::open($settings->_luceneDir);
								if($index) {
									$lucenesearch = new $indexconf['Search']($index);
									if($hit = $lucenesearch->getDocument($_REQUEST['id'])) {
										$index->delete($hit->id);
										$index->commit();
									}
								}
							}

							if ($notifier){
								$subject = "document_deleted_email_subject";
								$message = "document_deleted_email_body";
								$params = array();
								$params['name'] = $docname;
								$params['folder_path'] = $folder->getFolderPathPlain();
								$params['username'] = $user->getFullName();
								$params['sitename'] = $settings->_siteName;
								$params['http_root'] = $settings->_httpRoot;
								$notifier->toList($user, $nl["users"], $subject, $message, $params);
								foreach ($nl["groups"] as $grp) {
									$notifier->toGroup($user, $grp, $subject, $message, $params);
								}
							}

							header('Content-Type: application/json');
							echo json_encode(array('success'=>true, 'message'=>'', 'data'=>''));
						} else {
							header('Content-Type: application/json');
							echo json_encode(array('success'=>false, 'message'=>'Error removing document', 'data'=>''));
						}
					} else {
						header('Content-Type: application/json');
						echo json_encode(array('success'=>false, 'message'=>'No access', 'data'=>''));
					}
				} else {
					header('Content-Type: application/json');
					echo json_encode(array('success'=>false, 'message'=>'No document', 'data'=>''));
				}
			}
		}
		break; /* }}} */

	case 'tooglelockdocument': /* {{{ */
		if($user) {
			$document = $dms->getDocument($_REQUEST['id']);
			if($document) {
				if ($document->getAccessMode($user) >= M_READWRITE) {
					if ($document->isLocked()) {
						$lockingUser = $document->getLockingUser();
						if (($lockingUser->getID() == $user->getID()) || ($document->getAccessMode($user) == M_ALL)) {
							if (!$document->setLocked(false)) {
								header('Content-Type: application/json');
								echo json_encode(array('success'=>false, 'message'=>'Error unlocking document', 'data'=>''));
							} else {
								header('Content-Type: application/json');
								echo json_encode(array('success'=>true, 'message'=>'', 'data'=>''));
							}
						} else {
							header('Content-Type: application/json');
							echo json_encode(array('success'=>false, 'message'=>'No access', 'data'=>''));
						}
					} else {
						if (!$document->setLocked($user)) {
							header('Content-Type: application/json');
							echo json_encode(array('success'=>false, 'message'=>'Error locking document', 'data'=>''));
						} else {
							header('Content-Type: application/json');
							echo json_encode(array('success'=>true, 'message'=>'', 'data'=>''));
						}
					}
				} else {
					header('Content-Type: application/json');
					echo json_encode(array('success'=>false, 'message'=>'No access', 'data'=>''));
				}
			} else {
				header('Content-Type: application/json');
				echo json_encode(array('success'=>false, 'message'=>'No document', 'data'=>''));
			}
		}
		break; /* }}} */

	case 'submittranslation': /* {{{ */
		if($settings->_showMissingTranslations) {
			if($user && !empty($_POST['phrase'])) {
				if($fp = fopen('/tmp/newtranslations.txt', 'a+')) {
					fputcsv($fp, array(date('Y-m-d H:i:s'), $user->getLogin(), $_POST['key'], $_POST['lang'], $_POST['phrase']));
					fclose($fp);
				}
				header('Content-Type: application/json');
				echo json_encode(array('success'=>true, 'message'=>'Thank you for your contribution', 'data'=>''));
			}	else {
				header('Content-Type: application/json');
				echo json_encode(array('success'=>false, 'message'=>'Missing translation', 'data'=>''));
			}
		}
		break; /* }}} */

	case 'view': /* {{{ */
		require_once("SeedDMS/Preview.php");
		$view = UI::factory($theme, '', array('dms'=>$dms, 'user'=>$user));
		if($view) {
			$view->setParam('refferer', '');
			$view->setParam('cachedir', $settings->_cacheDir);
		}
		$content = '';
		$viewname = $_REQUEST["view"];
		switch($viewname) {
			case 'menuclipboard':
				$content = $view->menuClipboard($session->getClipboard());
				break;
			case 'mainclipboard':
				$previewer = new SeedDMS_Preview_Previewer($settings->_cacheDir, $settings->_previewWidthList);
				$content = $view->mainClipboard($session->getClipboard(), $previewer);
				break;
			case 'documentlistrow':
				$document = $dms->getDocument($_REQUEST['id']);
				if($document) {
					if ($document->getAccessMode($user) >= M_READ) {
						$previewer = new SeedDMS_Preview_Previewer($settings->_cacheDir, $settings->_previewWidthList);
						$view->setParam('previewWidthList', $settings->_previewWidthList);
						$view->setParam('showtree', showtree());
						$content = $view->documentListRow($document, $previewer, true);
					}
				}
				break;
			default:
				$content = '';
		}
		echo $content;

		break; /* }}} */

	case 'uploaddocument': /* {{{ */
		if($user) {
			if(checkFormKey('adddocument')) {
				if (!isset($_POST["folderid"]) || !is_numeric($_POST["folderid"]) || intval($_POST["folderid"])<1) {
					header('Content-Type: application/json');
					echo json_encode(array('success'=>false, 'message'=>getMLText("invalid_folder_id")));
					exit;
				}

				$folderid = $_POST["folderid"];
				$folder = $dms->getFolder($folderid);

				if (!is_object($folder)) {
					header('Content-Type: application/json');
					echo json_encode(array('success'=>false, 'message'=>getMLText("invalid_folder_id")));
					exit;
				}

				if ($folder->getAccessMode($user) < M_READWRITE) {
					echo json_encode(array('success'=>false, 'message'=>getMLText("access_denied")));
					exit;
				}

				if($settings->_quota > 0) {
					$remain = checkQuota($user);
					if ($remain < 0) {
						echo json_encode(array('success'=>false, 'message'=>getMLText("quota_exceeded", array('bytes'=>SeedDMS_Core_File::format_filesize(abs($remain))))));
						exit;
					}
				}

				if (!is_uploaded_file($_FILES["userfile"]["tmp_name"]) || $_FILES['userfile']['error']!=0){
					header('Content-Type: application/json');
					echo json_encode(array('success'=>false, 'message'=>getMLText("uploading_failed")));
					exit;
				}
				if ($_FILES["userfile"]["size"]==0) {
					header('Content-Type: application/json');
					echo json_encode(array('success'=>false, 'message'=>getMLText("uploading_zerosize")));
					exit;
				} 

				$userfiletmp = $_FILES["userfile"]["tmp_name"];
				$userfiletype = $_FILES["userfile"]["type"];
				$userfilename = $_FILES["userfile"]["name"];

				$fileType = ".".pathinfo($userfilename, PATHINFO_EXTENSION);

				if($settings->_overrideMimeType) {
					$finfo = finfo_open(FILEINFO_MIME_TYPE);
					$userfiletype = finfo_file($finfo, $userfiletmp);
				}

				if (!empty($_POST["name"]))
					$name = $_POST["name"];
				else
					$name = basename($userfilename);

				/* Check if name already exists in the folder */
				if(!$settings->_enableDuplicateDocNames) {
					if($folder->hasDocumentByName($name)) {
						header('Content-Type: application/json');
						echo json_encode(array('success'=>false, 'message'=>getMLText("document_duplicate_name")));
						exit;
					}
				}

				// Get the list of reviewers and approvers for this document.
				$reviewers = array();
				$approvers = array();
				$reviewers["i"] = array();
				$reviewers["g"] = array();
				$approvers["i"] = array();
				$approvers["g"] = array();

				// add mandatory reviewers/approvers
				$docAccess = $folder->getReadAccessList($settings->_enableAdminRevApp, $settings->_enableOwnerRevApp);
				$res=$user->getMandatoryReviewers();
				foreach ($res as $r){

					if ($r['reviewerUserID']!=0){
						foreach ($docAccess["users"] as $usr)
							if ($usr->getID()==$r['reviewerUserID']){
								$reviewers["i"][] = $r['reviewerUserID'];
								break;
							}
					}
					else if ($r['reviewerGroupID']!=0){
						foreach ($docAccess["groups"] as $grp)
							if ($grp->getID()==$r['reviewerGroupID']){
								$reviewers["g"][] = $r['reviewerGroupID'];
								break;
							}
					}
				}
				$res=$user->getMandatoryApprovers();
				foreach ($res as $r){

					if ($r['approverUserID']!=0){
						foreach ($docAccess["users"] as $usr)
							if ($usr->getID()==$r['approverUserID']){
								$approvers["i"][] = $r['approverUserID'];
								break;
							}
					}
					else if ($r['approverGroupID']!=0){
						foreach ($docAccess["groups"] as $grp)
							if ($grp->getID()==$r['approverGroupID']){
								$approvers["g"][] = $r['approverGroupID'];
								break;
							}
					}
				}

				$workflow = $user->getMandatoryWorkflow();

				$expires = false;
				if($settings->_presetExpirationDate) {
					$expires = strtotime($settings->_presetExpirationDate);
				}

				$cats = array();

				$res = $folder->addDocument($name, '', $expires, $user, '',
																		array(), $userfiletmp, basename($userfilename),
																		$fileType, $userfiletype, 0,
																		$reviewers, $approvers, 1,
																		'', array(), array(), $workflow);

				if (is_bool($res) && !$res) {
					header('Content-Type: application/json');
					echo json_encode(array('success'=>false, 'message'=>getMLText("error_occured")));
					exit;
				} else {
					$document = $res[0];
					if(isset($GLOBALS['SEEDDMS_HOOKS']['postAddDocument'])) {
						foreach($GLOBALS['SEEDDMS_HOOKS']['postAddDocument'] as $hookObj) {
							if (method_exists($hookObj, 'postAddDocument')) {
								$hookObj->postAddDocument($document);
							}
						}
					}
					if($settings->_enableFullSearch) {
						if(!empty($settings->_luceneClassDir))
							require_once($settings->_luceneClassDir.'/Lucene.php');
						else
							require_once('SeedDMS/Lucene.php');

						$index = SeedDMS_Lucene_Indexer::open($settings->_luceneDir);
						if($index) {
							SeedDMS_Lucene_Indexer::init($settings->_stopWordsFile);
							$index->addDocument(new SeedDMS_Lucene_IndexedDocument($dms, $document, isset($settings->_converters['fulltext']) ? $settings->_converters['fulltext'] : null, true));
						}
					}

					/* Add a default notification for the owner of the document */
					if($settings->_enableOwnerNotification) {
						$res = $document->addNotify($user->getID(), true);
					}
					// Send notification to subscribers of folder.
					if($notifier) {
						$notifyList = $folder->getNotifyList();
						if($settings->_enableNotificationAppRev) {
							/* Reviewers and approvers will be informed about the new document */
							foreach($reviewers['i'] as $reviewerid) {
								$notifyList['users'][] = $dms->getUser($reviewerid);
							}
							foreach($approvers['i'] as $approverid) {
								$notifyList['users'][] = $dms->getUser($approverid);
							}
							foreach($reviewers['g'] as $reviewergrpid) {
								$notifyList['groups'][] = $dms->getGroup($reviewergrpid);
							}
							foreach($approvers['g'] as $approvergrpid) {
								$notifyList['groups'][] = $dms->getGroup($approvergrpid);
							}
						}

						$subject = "new_document_email_subject";
						$message = "new_document_email_body";
						$params = array();
						$params['name'] = $name;
						$params['folder_name'] = $folder->getName();
						$params['folder_path'] = $folder->getFolderPathPlain();
						$params['username'] = $user->getFullName();
						$params['comment'] = '';
						$params['version_comment'] = '';
						$params['url'] = "http".((isset($_SERVER['HTTPS']) && (strcmp($_SERVER['HTTPS'],'off')!=0)) ? "s" : "")."://".$_SERVER['HTTP_HOST'].$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
						$params['sitename'] = $settings->_siteName;
						$params['http_root'] = $settings->_httpRoot;
						$notifier->toList($user, $notifyList["users"], $subject, $message, $params);
						foreach ($notifyList["groups"] as $grp) {
							$notifier->toGroup($user, $grp, $subject, $message, $params);
						}

					}
				}
				header('Content-Type: application/json');
				echo json_encode(array('success'=>true, 'message'=>getMLText('splash_document_added'), 'data'=>$document->getID()));
			} else {
				header('Content-Type: application/json');
				echo json_encode(array('success'=>false, 'message'=>getMLText('invalid_request_token'), 'data'=>''));
			}
		}
		break; /* }}} */

}
add_log_line();
?>
