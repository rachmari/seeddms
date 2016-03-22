<?php
require_once("../inc/inc.ClassSettings.php");

function usage() { /* {{{ */
	echo "Usage:\n";
	echo "  seeddms-xmlimport [-h] [-v] [--config <file>]\n";
	echo "\n";
	echo "Description:\n";
	echo "  This program imports an xml dump into the dms.\n";
	echo "\n";
	echo "Options:\n";
	echo "  -h, --help: print usage information and exit.\n";
	echo "  -v, --version: print version and exit.\n";
	echo "  --config <config file>: set alternative config file.\n";
	echo "  --folder <folder id>: set import folder.\n";
	echo "  --file <file>: file containing the dump.\n";
	echo "  --sections <sections>: comma seperated list of sections to read from dump.\n";
	echo "     can be: users, groups, documents, folders, keywordcategories, or\n";
	echo "     documentcategories\n";
	echo "  --contentdir <dir>: directory where all document versions are stored\n";
	echo "    which are not included in the xml file.\n";
	echo "  --default-user <user id>: use this user if user could not be found.\n";
	echo "  --export-mapping <file>: write object mapping into file\n";
	echo "  --debug: turn debug output on\n";
} /* }}} */

function dateToTimestamp($date, $format='Y-m-d H:i:s') { /* {{{ */
	$p = date_parse_from_format($format, $date);
	return mktime($p['hour'], $p['minute'], $p['second'], $p['month'], $p['day'], $p['year']);
} /* }}} */

function getRevAppLog($reviews) { /* {{{ */
	global $dms, $objmap;

	$newreviews = array();
	foreach($reviews as $i=>$review) {
		$newreview = array('type'=>$review['attributes']['type']);
		if($review['attributes']['type'] == 1) {
			if(isset($objmap['groups'][(int) $review['attributes']['required']]))
				$newreview['required'] = $dms->getGroup($objmap['groups'][(int) $review['attributes']['required']]);
		} else {
			if(isset($objmap['users'][(int) $review['attributes']['required']]))
				$newreview['required'] = $dms->getUser($objmap['users'][(int) $review['attributes']['required']]);
		}
		$newreview['logs'] = array();
		foreach($review['logs'] as $j=>$log) {
			if(!array_key_exists($log['attributes']['user'], $objmap['users'])) {
				echo "Warning: user for review log cannot be mapped\n";
			} else {
				$newlog = array();
				$newlog['user'] = $dms->getUser($objmap['users'][$log['attributes']['user']]);
				$newlog['status'] = $log['attributes']['status'];
				$newlog['comment'] = $log['attributes']['comment'];
				$newlog['date'] = $log['attributes']['date'];
				$newreview['logs'][] = $newlog;
			}
		}
		$newreviews[] = $newreview;
	}
	return $newreviews;
} /* }}} */

function insert_user($user) { /* {{{ */
	global $dms, $debug, $sections, $defaultUser, $objmap;

	if($debug) print_r($user);

	if ($newUser = $dms->getUserByLogin($user['attributes']['login'])) {
		echo "User '".$user['attributes']['login']."' already exists\n";
	} else {
		if(in_array('users', $sections)) {
			$newUser = $dms->addUser(
				$user['attributes']['login'],
				$user['attributes']['pwd'],
				$user['attributes']['fullname'],
				$user['attributes']['email'],
				$user['attributes']['language'],
				$user['attributes']['theme'],
				$user['attributes']['comment'],
				$user['attributes']['role'],
				$user['attributes']['hidden'],
				$user['attributes']['disabled'],
				$user['attributes']['pwdexpiration']);
			if(!$newUser) {
				echo "Error: could not add user\n";
				return false;
			}
		} else {
			$newUser = $defaultUser;
		}
	}
	if($newUser)
		$objmap['users'][$user['id']] = $newUser->getID();
	return $newUser;
} /* }}} */

function set_homefolders() { /* {{{ */
	global $dms, $debug, $defaultUser, $users, $objmap;

	foreach($users as $user) {
		if(isset($user['attributes']['homefolder']) && $user['attributes']['homefolder']) {
			if(array_key_exists($user['id'], $objmap['users'])) {
				$userobj = $dms->getUser($objmap['users'][$user['id']]);
				if(!array_key_exists((int) $user['attributes']['homefolder'], $objmap['folders'])) {
					echo "Warning: homefolder ".$user['attributes']['homefolder']." cannot be found\n";
				} else {
					$userobj->setHomeFolder($objmap['folders'][(int) $user['attributes']['homefolder']]);
				}
			}
		}
	}
} /* }}} */

function insert_group($group) { /* {{{ */
	global $dms, $debug, $objmap, $sections, $users;

	if($debug) print_r($group);

	if ($newGroup = $dms->getGroupByName($group['attributes']['name'])) {
		echo "Group already exists\n";
	} else {
		if(in_array('groups', $sections)) {
			$newGroup = $dms->addGroup($group['attributes']['name'], $group['attributes']['comment']);
			if($newGroup) {
				foreach($group['users'] as $guser) {
					/* Check if user is in array of users which has been previously filled
					 * by the users in the xml file. Alternative, we could check if the
					 * id is a key of $objmap['users'] and use the new id in that array.
					 */
					if(isset($users[$guser])) {
						$user = $users[$guser];
						if($newMember = $dms->getUserByLogin($user['attributes']['login'])) {
							$newGroup->addUser($newMember);
						} else {
							echo "Error: could not find member of group\n";
							return false;
						}
					} else {
						echo "Error: group member is not contained in xml file\n";
						return false;
					}
				}
			} else {
				echo "Error: could not add group\n";
				return false;
			}
		} else {
			$newGroup = null;
		}
	}
	if($newGroup)
		$objmap['groups'][$group['id']] = $newGroup->getID();
	return $newGroup;
} /* }}} */

function insert_attributedefinition($attrdef) { /* {{{ */
	global $dms, $debug, $objmap, $sections;

	if($debug)
		print_r($attrdef);
	if($newAttrdef = $dms->getAttributeDefinitionByName($attrdef['attributes']['name'])) {
		echo "Attribute definition already exists\n";
	} else {
		if(in_array('attributedefinitions', $sections)) {
			if(!$newAttrdef = $dms->addAttributeDefinition($attrdef['attributes']['name'], $attrdef['objecttype'], $attrdef['attributes']['type'], $attrdef['attributes']['multiple'], $attrdef['attributes']['minvalues'], $attrdef['attributes']['maxvalues'], $attrdef['attributes']['valueset'], $attrdef['attributes']['regex'])) {
				echo "Error: could not add attribute definition\n";
				return false;
			}
		} else {
			$newAttrdef = null;
		}
	}
	if($newAttrdef)
		$objmap['attributedefs'][$attrdef['id']] = $newAttrdef->getID();
	return $newAttrdef;
} /* }}} */

function insert_documentcategory($documentcat) { /* {{{ */
	global $dms, $debug, $objmap, $sections;

	if($debug) print_r($documentcat);

	if($newCategory = $dms->getDocumentCategoryByName($documentcat['attributes']['name'])) {
		echo "Document category already exists\n";
	} else {
		if(in_array('documentcategories', $sections)) {
			if(!$newCategory = $dms->addDocumentCategory($documentcat['attributes']['name'])) {
				echo "Error: could not add document category\n";
				return false;
			}
		} else {
			$newCategory = null;
		}
	}

	if($newCategory)
		$objmap['documentcategories'][$documentcat['id']] = $newCategory->getID();
	return $newCategory;
} /* }}} */

function insert_keywordcategory($keywordcat) { /* {{{ */
	global $dms, $debug, $objmap, $sections;

	if($debug) print_r($keywordcat);

	if(!array_key_exists((int) $keywordcat['attributes']['owner'], $objmap['users'])) {
		echo "Error: owner of keyword category cannot be found\n";
		return false;
	}
	$owner = $objmap['users'][(int) $keywordcat['attributes']['owner']];

	if($newCategory = $dms->getKeywordCategoryByName($keywordcat['attributes']['name'], $owner)) {
		echo "Document category already exists\n";
	} else {
		if(in_array('keywordcategories', $sections)) {
			if(!$newCategory = $dms->addKeywordCategory($owner, $keywordcat['attributes']['name'])) {
				echo "Error: could not add keyword category\n";
				return false;
			}
			foreach($keywordcat['keywords'] as $keyword) {
				if(!$newCategory->addKeywordList($keyword['attributes']['name'])) {
					echo "Error: could not add keyword to keyword category\n";
					return false;
				}
			}
		} else {
			$newCategory = null;
		}
	}

	if($newCategory)
		$objmap['keywordcategories'][$keywordcat['id']] = $newCategory->getID();
	return $newCategory;
} /* }}} */

function insert_document($document) { /* {{{ */
	global $dms, $debug, $defaultUser, $objmap, $sections, $rootfolder;

	if($debug) print_r($document);

	if(!array_key_exists((int) $document['attributes']['owner'], $objmap['users'])) {
		echo "Warning: owner of document cannot be mapped using default user\n";
		$owner = $defaultUser;
	} else {
		$owner = $dms->getUser($objmap['users'][(int) $document['attributes']['owner']]);
	}

	$attributes = array();
	if(isset($document['user_attributes'])) {
		foreach($document['user_attributes'] as $orgid=>$value) {
			if(array_key_exists((int) $orgid, $objmap['attributedefs'])) {
				$attributes[$objmap['attributedefs'][$orgid]] = $value;
			} else {
				echo "Warning: User attribute ".$orgid." cannot be mapped\n";
			}
		}
	}
	$categories = array();
	if(isset($document['categories'])) {
		foreach($document['categories'] as $catid) {
			if(array_key_exists((int) $catid, $objmap['documentcategories'])) {
				$categories[$objmap['documentcategories'][$catid]] = $dms->getDocumentCategory($objmap['documentcategories'][$catid]);
			} else {
				echo "Warning: Category ".$catid." cannot be mapped\n";
			}
		}
	}

	if(isset($document['folder']) && $document['folder']) {
		if(array_key_exists($document['folder'], $objmap['folders'])) {
			$folder = $dms->getFolder($objmap['folders'][$document['folder']]);
		} else {
			echo "Error: folder ".$document['folder']." cannot be mapped\n";
			return false;
		}
	} else
		$folder = $rootfolder;

	if(in_array('documents', $sections)) {
		$error = false;
		$initversion = array_shift($document['versions']);
		if(!empty($initversion['fileref'])) {
			$filename = tempnam('/tmp', 'FOO');
			copy($initversion['fileref'], $filename);
		} else {
			$filecontents = base64_decode($initversion['data']);
			if(strlen($filecontents) != $initversion['data_length']) {
				echo "Warning: file length (".strlen($filecontents).") doesn't match expected length (".$initversion['data_length'].").\n";
				$newDocument = null;
				$error = true;
			}
			$filename = tempnam('/tmp', 'FOO');
			file_put_contents($filename, $filecontents);
		}
		if(!$error) {
			$reviews = array('i'=>array(), 'g'=>array());
			/*
			if($initversion['reviews']) {
				foreach($initversion['reviews'] as $review) {
					if($review['attributes']['type'] == 1) {
						if(isset($objmap['groups'][(int) $review['attributes']['required']]))
							$reviews['g'][] = $objmap['groups'][(int) $review['attributes']['required']];	
					} else {
						if(isset($objmap['users'][(int) $review['attributes']['required']]))
							$reviews['i'][] = $objmap['users'][(int) $review['attributes']['required']];	
					}
				}
			}
			 */
			$approvals = array('i'=>array(), 'g'=>array());
			/*
			if($initversion['approvals']) {
				foreach($initversion['approvals'] as $approval) {
					if($approval['attributes']['type'] == 1) {
						if(isset($objmap['groups'][(int) $approval['attributes']['required']]))
							$approvals['g'][] = $objmap['groups'][(int) $approval['attributes']['required']];	
					} else {
						if(isset($objmap['users'][(int) $approval['attributes']['required']]))
							$approvals['i'][] = $objmap['users'][(int) $approval['attributes']['required']];	
					}
				}
			}
			 */
			$version_attributes = array();
			if(isset($initversion['user_attributes'])) {
				foreach($initversion['user_attributes'] as $orgid=>$value) {
					if(array_key_exists((int) $orgid, $objmap['attributedefs'])) {
						$version_attributes[$objmap['attributedefs'][$orgid]] = $value;
					} else {
						echo "Warning: User attribute ".$orgid." cannot be mapped\n";
					}
				}
			}
			if(!$result = $folder->addDocument(
				$document['attributes']['name'],
				$document['attributes']['comment'],
				isset($document['attributes']['expires']) ? dateToTimestamp($document['attributes']['expires']) : 0,
				$owner,
				isset($document['attributes']['keywords']) ? $document['attributes']['keywords'] : 0,
				$categories,
				$filename,
				$initversion['attributes']['orgfilename'],
				$initversion['attributes']['filetype'],
				$initversion['attributes']['mimetype'],
				$document['attributes']['sequence'],
				$reviews, //reviewers
				$approvals, //approvers
				$initversion['version'],
				isset($initversion['attributes']['comment']) ? $initversion['attributes']['comment'] : '',
				$attributes,
				$version_attributes,
				null //workflow
				)
			) {
				unlink($filename);
				echo "Error: could not add document\n";
				return false;
			} else {
				$newDocument = $result[0];
				unlink($filename);

				$newVersion = $result[1]->getContent();
				$newVersion->setDate(dateToTimestamp($initversion['attributes']['date']));
				$newlogs = array();
				foreach($initversion['statuslogs'] as $i=>$log) {
					if(!array_key_exists($log['attributes']['user'], $objmap['users'])) {
						unset($initversion['statuslogs'][$i]);
						echo "Warning: user for status log cannot be mapped\n";
					} else {
						$log['attributes']['user'] = $dms->getUser($objmap['users'][$log['attributes']['user']]);
						$newlogs[] = $log['attributes'];
					}
				}
				$newVersion->rewriteStatusLog($newlogs);

				/* Set reviewers and review log */
				if($initversion['reviews']) {
					$newreviews = getRevAppLog($initversion['reviews']);
					$newVersion->rewriteReviewLog($newreviews);
				}
				if($initversion['approvals']) {
					$newapprovals = getRevAppLog($initversion['approvals']);
					$newVersion->rewriteApprovalLog($newapprovals);
				}

				$newDocument->setDate(dateToTimestamp($document['attributes']['date']));
				$newDocument->setDefaultAccess($document['attributes']['defaultaccess']);
				foreach($document['versions'] as $version) {
					if(!array_key_exists((int) $version['attributes']['owner'], $objmap['users'])) {
						echo "Error: owner of document cannot be mapped\n";
						return false;
					}
					$owner = $dms->getUser($objmap['users'][(int) $version['attributes']['owner']]);

					$reviews = array('i'=>array(), 'g'=>array());
					/*
					if($version['reviews']) {
						foreach($version['reviews'] as $review) {
							if($review['attributes']['type'] == 1) {
								if(isset($objmap['groups'][(int) $review['attributes']['required']]))
									$reviews['g'][] = $objmap['groups'][(int) $review['attributes']['required']];	
							} else {
								if(isset($objmap['users'][(int) $review['attributes']['required']]))
									$reviews['i'][] = $objmap['users'][(int) $review['attributes']['required']];	
							}
						}
					}
					 */
					$approvals = array('i'=>array(), 'g'=>array());
					/*
					if($version['approvals']) {
						foreach($version['approvals'] as $approval) {
							if($approval['attributes']['type'] == 1) {
								if(isset($objmap['groups'][(int) $approval['attributes']['required']]))
									$approvals['g'][] = $objmap['groups'][(int) $approval['attributes']['required']];	
							} else {
								if(isset($objmap['users'][(int) $approval['attributes']['required']]))
									$approvals['i'][] = $objmap['users'][(int) $approval['attributes']['required']];	
							}
						}
					}
					 */
					$version_attributes = array();
					if(isset($version['user_attributes'])) {
						foreach($version['user_attributes'] as $orgid=>$value) {
							if(array_key_exists((int) $orgid, $objmap['attributedefs'])) {
								$version_attributes[$objmap['attributedefs'][$orgid]] = $value;
							} else {
								echo "Warning: User attribute ".$orgid." cannot be mapped\n";
							}
						}
					}
					if(!empty($version['fileref'])) {
						$filename = tempnam('/tmp', 'FOO');
						copy($version['fileref'], $filename);
					} else {
						$filecontents = base64_decode($version['data']);
						if(strlen($filecontents) != $version['data_length']) {
							echo "Warning: file length (".strlen($filecontents).") doesn't match expected length (".$version['data_length'].").\n";
						}
						$filename = tempnam('/tmp', 'FOO');
						file_put_contents($filename, $filecontents);
					}
					if(!($result = $newDocument->addContent(
						$version['attributes']['comment'],
						$owner,
						$filename,
						$version['attributes']['orgfilename'],
						$version['attributes']['filetype'],
						$version['attributes']['mimetype'],
						$reviews, //reviewers
						$approvals, //approvers
						$version['version'],	
						$version_attributes,
						null //workflow
					))) {
					}
					$newVersion = $result->getContent();
					$newVersion->setDate(dateToTimestamp($version['attributes']['date']));
					$newlogs = array();
					foreach($version['statuslogs'] as $i=>$log) {
						if(!array_key_exists($log['attributes']['user'], $objmap['users'])) {
							unset($version['statuslogs'][$i]);
							echo "Warning: user for status log cannot be mapped\n";
						} else {
							$log['attributes']['user'] = $dms->getUser($objmap['users'][$log['attributes']['user']]);
							$newlogs[] = $log['attributes'];
						}
					}
					$newVersion->rewriteStatusLog($newlogs);

					if($version['reviews']) {
						$newreviews = getRevAppLog($version['reviews']);
						$newVersion->rewriteReviewLog($newreviews);
					}
					if($version['approvals']) {
						$newapprovals = getRevAppLog($version['approvals']);
						$newVersion->rewriteApprovalLog($newapprovals);
					}

					unlink($filename);
				}	
			}
			if(isset($document['notifications']['users']) && $document['notifications']['users']) {
				foreach($document['notifications']['users'] as $userid) {
					if(!array_key_exists($userid, $objmap['users'])) {
						echo "Warning: user for notification cannot be mapped\n";
					} else {
						$newDocument->addNotify($objmap['users'][$userid], 1);
					}
				}
			}
			if(isset($document['notifications']['groups']) && $document['notifications']['groups']) {
				foreach($document['notifications']['groups'] as $groupid) {
					if(!array_key_exists($groupid, $objmap['groups'])) {
						echo "Warning: user for notification cannot be mapped\n";
					} else {
						$newDocument->addNotify($objmap['groups'][$groupid], 0);
					}
				}
			}
			if(isset($document['acls']) && $document['acls']) {
				$newDocument->setInheritAccess(false);
				foreach($document['acls'] as $acl) {
					if($acl['type'] == 'user') {
						if(!array_key_exists($acl['user'], $objmap['users'])) {
							echo "Warning: user for notification cannot be mapped\n";
						} else {
							$newDocument->addAccess($acl['mode'], $objmap['users'][$acl['user']], 1);
						}
					} elseif($acl['type'] == 'group') {
						if(!array_key_exists($acl['group'], $objmap['groups'])) {
							echo "Warning: group for notification cannot be mapped\n";
						} else {
							$newDocument->addAccess($acl['mode'], $objmap['groups'][$acl['group']], 0);
						}
					}
				}
			}
			if(isset($document['files']) && $document['files']) {
				foreach($document['files'] as $file) {
					if(!array_key_exists($file['attributes']['owner'], $objmap['users'])) {
						echo "Warning: user for file cannot be mapped\n";
						$owner = $defaultUser;
					} else {
						$owner = $dms->getUser($objmap['users'][$file['attributes']['owner']]);
					}
					if(!empty($file['fileref'])) {
						$filename = tempnam('/tmp', 'FOO');
						copy($file['fileref'], $filename);
					} else {
						$filecontents = base64_decode($file['data']);
						if(strlen($filecontents) != $file['data_length']) {
							echo "Warning: file length (".strlen($filecontents).") doesn't match expected length (".$file['data_length'].").\n";
						}
						$filename = tempnam('/tmp', 'FOO');
						file_put_contents($filename, $filecontents);
					}
					$newDocument->addDocumentFile(
						$file['attributes']['name'],
						$file['attributes']['comment'],
						$owner,
						$filename,
						$file['attributes']['orgfilename'],
						$file['attributes']['filetype'],
						$file['attributes']['mimetype']
					);
					unlink($filename);
				}
			}
		}
	} else {
		$newDocument = null;
	}

	if($newDocument)
		$objmap['documents'][$document['id']] = $newDocument->getID();
	return $newDocument;
} /* }}} */

function insert_folder($folder) { /* {{{ */
	global $dms, $debug, $objmap, $defaultUser, $sections, $rootfolder;

	if($debug) print_r($folder);

	if(in_array('folders', $sections)) {
		if(!array_key_exists($folder['attributes']['owner'], $objmap['users'])) {
			echo "Warning: owner of folder cannot be mapped using default user\n";
			$owner = $defaultuser;
		} else {
			$owner = $dms->getUser($objmap['users'][(int) $folder['attributes']['owner']]);
		}

		$attributes = array();
		if(isset($folder['user_attributes'])) {
			foreach($folder['user_attributes'] as $orgid=>$value) {
				if(array_key_exists((int) $orgid, $objmap['attributedefs'])) {
					$attributes[$objmap['attributedefs'][$orgid]] = $value;
				} else {
					echo "Warning: User attribute ".$orgid." cannot be mapped\n";
				}
			}
		}

		if(isset($folder['folder']) && $folder['folder']) {
			if(array_key_exists($folder['folder'], $objmap['folders'])) {
				$parent = $dms->getFolder($objmap['folders'][$folder['folder']]);
			} else {
				echo "Error: Folder ".$folder['folder']." cannot be mapped\n";
				exit;
			}
		} else
			$parent = $rootfolder;

		if(!$newFolder = $parent->addSubFolder($folder['attributes']['name'], $folder['attributes']['comment'], $owner, $folder['attributes']['sequence'], $attributes)) {
			echo "Error: could not add folder\n";
			return false;
		}

		$newFolder->setDate(dateToTimestamp($folder['attributes']['date']));
		$newFolder->setDefaultAccess($folder['attributes']['defaultaccess']);
		if(isset($folder['notifications']['users']) && $folder['notifications']['users']) {
			foreach($folder['notifications']['users'] as $userid) {
				if(!array_key_exists($userid, $objmap['users'])) {
					echo "Warning: user for notification cannot be mapped\n";
				} else {
					$newFolder->addNotify($objmap['users'][$userid], 1);
				}
			}
		}
		if(isset($folder['notifications']['groups']) && $folder['notifications']['groups']) {
			foreach($folder['notifications']['groups'] as $groupid) {
				if(!array_key_exists($groupid, $objmap['groups'])) {
					echo "Warning: user for notification cannot be mapped\n";
				} else {
					$newFolder->addNotify($objmap['groups'][$groupid], 0);
				}
			}
		}
		if(isset($folder['acls']) && $folder['acls']) {
			$newFolder->setInheritAccess(false);
			foreach($folder['acls'] as $acl) {
				if($acl['type'] == 'user') {
					if(!array_key_exists($acl['user'], $objmap['users'])) {
						echo "Warning: user for notification cannot be mapped\n";
					} else {
						$newFolder->addAccess($acl['mode'], $objmap['users'][$acl['user']], 1);
					}
				} elseif($acl['type'] == 'group') {
					if(!array_key_exists($acl['group'], $objmap['groups'])) {
						echo "Warning: group for notification cannot be mapped\n";
					} else {
						$newFolder->addAccess($acl['mode'], $objmap['groups'][$acl['group']], 0);
					}
				}
			}
		}
	} else {
		$newFolder = null;
	}

	if($newFolder)
		$objmap['folders'][$folder['id']] = $newFolder->getID();
	return $newFolder;
} /* }}} */

function resolve_links() { /* {{{ */
	global $dms, $debug, $defaultUser, $links, $objmap;

	if($debug)
		print_r($links);
	foreach($links as $documentid=>$doclinks) {
		if(array_key_exists($documentid, $objmap['documents'])) {
			if($doc = $dms->getDocument($objmap['documents'][$documentid])) {
				foreach($doclinks as $doclink) {
							if(array_key_exists($doclink['attributes']['target'], $objmap['documents'])) {
								if($target = $dms->getDocument($objmap['documents'][$doclink['attributes']['target']])) {
									if(!array_key_exists($doclink['attributes']['owner'], $objmap['users'])) {
										echo "Warning: user for link cannot be mapped using default user\n";
										$owner = $defaultUser;
									} else {
										$owner = $dms->getUser($objmap['users'][$doclink['attributes']['owner']]);
									}
									if(!$doc->addDocumentLink($target->getID(), $owner->getID(), $doclink['attributes']['public'])) {
										echo "Error: could not add document link from ".$doc->getID()." to ".$target->getID()."\n";
									}
								} else {
									echo "Warning: target document not found in database\n";
								}
							} else {
								echo "Warning: target document not found in object mapping\n";
							}
				}
			} else {
				echo "Warning: document not found in database\n";
			}
		} else {
			echo "Warning: document not found in object mapping\n";
		}
	}
} /* }}} */

function startElement($parser, $name, $attrs) { /* {{{ */
	global $dms, $noversioncheck, $elementstack, $objmap, $cur_user, $cur_group, $cur_folder, $cur_document, $cur_version, $cur_statuslog, $cur_approval, $cur_approvallog, $cur_review, $cur_reviewlog, $cur_attrdef, $cur_documentcat, $cur_keyword, $cur_keywordcat, $cur_file, $cur_link;

	$parent = end($elementstack);
	array_push($elementstack, array('name'=>$name, 'attributes'=>$attrs));
	switch($name) {
		case "DMS":
			if(!$noversioncheck) {
				$xdbversion = explode('.', $attrs['DBVERSION']);
				$dbversion = $dms->getDBVersion();
				if(($xdbversion[0] != $dbversion['major']) || ($xdbversion[1] != $dbversion['minor'])) {
					echo "Error: Database version (".implode('.', array($dbversion['major'], $dbversion['minor'], $dbversion['subminor'])).") doesn't match version in input file (".implode('.', $xdbversion).").\n";
					exit(1);
				}
			}
			break;
		case "USER":
			/* users can be the users data, the member of a group, a mandatory
			 * reviewer or approver
			 */
			$first = $elementstack[1];
			if($first['name'] == 'USERS') {
				if($parent['name'] == 'MANDATORY_REVIEWERS') {
					$cur_user['individual']['reviewers'][] = (int) $attrs['ID'];
				} elseif($parent['name'] == 'MANDATORY_APPROVERS') {
					$cur_user['individual']['approvers'][] = (int) $attrs['ID'];
				} else {
					$cur_user = array();
					$cur_user['id'] = (int) $attrs['ID'];
					$cur_user['attributes'] = array();
					$cur_user['individual']['reviewers'] = array();
					$cur_user['individual']['approvers'] = array();
				}
			} elseif($first['name'] == 'GROUPS') {
				$cur_group['users'][] = (int) $attrs['USER'];
			} elseif($parent['name'] == 'NOTIFICATIONS') {
				if($first['name'] == 'FOLDER') {
					$cur_folder['notifications']['users'][] = (int) $attrs['ID'];
				} elseif($first['name'] == 'DOCUMENT') {
					$cur_document['notifications']['users'][] = (int) $attrs['ID'];
				}
			}
			break;
		case "GROUP":
			$first = $elementstack[1];
			if($first['name'] == 'GROUPS') {
				$cur_group = array();
				$cur_group['id'] = (int) $attrs['ID'];
				$cur_group['attributes'] = array();
				$cur_group['users'] = array();
			} elseif($first['name'] == 'USERS') {
				if($parent['name'] == 'MANDATORY_REVIEWERS') {
					$cur_user['group']['reviewers'][] = (int) $attrs['ID'];
				} elseif($parent['name'] == 'MANDATORY_APPROVERS') {
					$cur_user['group']['approvers'][] = (int) $attrs['ID'];
				}
			} elseif($parent['name'] == 'NOTIFICATIONS') {
				if($first['name'] == 'FOLDER') {
					$cur_folder['notifications']['groups'][] = (int) $attrs['ID'];
				} elseif($first['name'] == 'DOCUMENT') {
					$cur_document['notifications']['groups'][] = (int) $attrs['ID'];
				}
			}
			break;
		case "DOCUMENT":
			$cur_document = array();
			$cur_document['id'] = (int) $attrs['ID'];
			$cur_document['folder'] = (int) $attrs['FOLDER'];
			$cur_document['attributes'] = array();
			$cur_document['versions'] = array();
			break;
		case "FOLDER":
			$cur_folder = array();
			$cur_folder['id'] = (int) $attrs['ID'];
			if(isset($attrs['PARENT']))
				$cur_folder['folder'] = (int) $attrs['PARENT'];
			$cur_folder['attributes'] = array();
			break;
		case "VERSION":
			$cur_version = array();
			$cur_version['version'] = (int) $attrs['VERSION'];
			$cur_version['attributes'] = array();
			$cur_version['approvals'] = array();
			$cur_version['reviews'] = array();
			break;
		case "STATUSLOG":
			$cur_statuslog = array();
			$cur_statuslog['attributes'] = array();
			break;
		case "APPROVAL":
			$cur_approval = array();
			$cur_approval['attributes'] = array();
			$cur_approval['logs'] = array();
			break;
		case "APPROVALLOG":
			$cur_approvallog = array();
			$cur_approvallog['attributes'] = array();
			break;
		case "REVIEW":
			$cur_review = array();
			$cur_review['attributes'] = array();
			$cur_review['logs'] = array();
			break;
		case "REVIEWLOG":
			$cur_reviewlog = array();
			$cur_reviewlog['attributes'] = array();
			break;
		case 'ATTRIBUTEDEFINITION':
			$cur_attrdef = array();
			$cur_attrdef['id'] = (int) $attrs['ID'];
			$cur_attrdef['attributes'] = array();
			$cur_attrdef['objecttype'] = $attrs['OBJTYPE'];
			break;
		case "ATTR":
			if($parent['name'] == 'DOCUMENT') {
				if(isset($attrs['TYPE']) && $attrs['TYPE'] == 'user') {
					$cur_document['user_attributes'][$attrs['ATTRDEF']] = '';
				} else {
					$cur_document['attributes'][$attrs['NAME']] = '';
				}
			} elseif($parent['name'] == 'VERSION') {
				if(isset($attrs['TYPE']) && $attrs['TYPE'] == 'user') {
					$cur_version['user_attributes'][$attrs['ATTRDEF']] = '';
				} else {
					$cur_version['attributes'][$attrs['NAME']] = '';
				}
			} elseif($parent['name'] == 'STATUSLOG') {
				$cur_statuslog['attributes'][$attrs['NAME']] = '';
			} elseif($parent['name'] == 'APPROVAL') {
				$cur_approval['attributes'][$attrs['NAME']] = '';
			} elseif($parent['name'] == 'APPROVALLOG') {
				$cur_approvallog['attributes'][$attrs['NAME']] = '';
			} elseif($parent['name'] == 'REVIEW') {
				$cur_review['attributes'][$attrs['NAME']] = '';
			} elseif($parent['name'] == 'REVIEWLOG') {
				$cur_reviewlog['attributes'][$attrs['NAME']] = '';
			} elseif($parent['name'] == 'FOLDER') {
				if(isset($attrs['TYPE']) && $attrs['TYPE'] == 'user') {
					$cur_folder['user_attributes'][$attrs['ATTRDEF']] = '';
				} else {
					$cur_folder['attributes'][$attrs['NAME']] = '';
				}
			} elseif($parent['name'] == 'USER') {
				$cur_user['attributes'][$attrs['NAME']] = '';
			} elseif($parent['name'] == 'GROUP') {
				$cur_group['attributes'][$attrs['NAME']] = '';
			} elseif($parent['name'] == 'KEYWORD') {
				$cur_keyword['attributes'][$attrs['NAME']] = '';
			} elseif($parent['name'] == 'ATTRIBUTEDEFINITION') {
				$cur_attrdef['attributes'][$attrs['NAME']] = '';
			} elseif($parent['name'] == 'FILE') {
				$cur_file['attributes'][$attrs['NAME']] = '';
			} elseif($parent['name'] == 'LINK') {
				$cur_link['attributes'][$attrs['NAME']] = '';
			}
			break;
		case "CATEGORIES":
			if($parent['name'] == 'DOCUMENT') {
				$cur_document['categories'] = array();
			}
			break;
		case "CATEGORY":
			$first = $elementstack[1];
			if($first['name'] == 'DOCUMENT') {
				$cur_document['categories'][] = (int) $attrs['ID'];
			}
			break;
		case "ACLS":
			if($parent['name'] == 'DOCUMENT') {
				$cur_document['acls'] = array();
			} elseif($parent['name'] == 'FOLDER') {
				$cur_folder['acls'] = array();
			}
			break;
		case "ACL":
			$first = $elementstack[1];
			if($first['name'] == 'FOLDER') {
				$acl = array('type'=>$attrs['TYPE'], 'mode'=>$attrs['MODE']);
				if($attrs['TYPE'] == 'user') {
					$acl['user'] = $attrs['USER'];
				} elseif($attrs['TYPE'] == 'group') { 
					$acl['group'] = $attrs['GROUP'];
				}
				$cur_folder['acls'][] = $acl;
			} elseif($first['name'] == 'DOCUMENT') {
				$acl = array('type'=>$attrs['TYPE'], 'mode'=>$attrs['MODE']);
				if($attrs['TYPE'] == 'user') {
					$acl['user'] = $attrs['USER'];
				} elseif($attrs['TYPE'] == 'group') { 
					$acl['group'] = $attrs['GROUP'];
				}
				$cur_document['acls'][] = $acl;
			}
			break;
		case "DATA":
			if($parent['name'] == 'IMAGE') {
				$cur_user['image']['id'] = $parent['attributes']['ID'];
				$cur_user['image']['data'] = "";
			} elseif($parent['name'] == 'VERSION') {
				$cur_version['data_length'] = (int) $attrs['LENGTH'];
				if(isset($attrs['FILEREF']))
					$cur_version['fileref'] = $attrs['FILEREF'];
				else
					$cur_version['data'] = "";
			} elseif($parent['name'] == 'FILE') {
				$cur_file['data_length'] = (int) $attrs['LENGTH'];
				if(isset($attrs['FILEREF']))
					$cur_file['fileref'] = $attrs['FILEREF'];
				else
					$cur_file['data'] = "";
			}
			break;
		case "KEYWORD":
			$cur_keyword = array();
			$cur_keyword['id'] = (int) $attrs['ID'];
			$cur_keyword['attributes'] = array();
			break;
		case "KEYWORDCATEGORY":
			$cur_keywordcat = array();
			$cur_keywordcat['id'] = (int) $attrs['ID'];
			$cur_keywordcat['attributes'] = array();
			$cur_keywordcat['keywords'] = array();
			break;
		case "DOCUMENTCATEGORY":
			$cur_documentcat = array();
			$cur_documentcat['id'] = (int) $attrs['ID'];
			$cur_documentcat['attributes'] = array();
			break;
		case "NOTIFICATIONS":
			$first = $elementstack[1];
			if($first['name'] == 'FOLDER') {
				$cur_folder['notifications'] = array('users'=>array(), 'groups'=>array());
			} elseif($first['name'] == 'DOCUMENT') {
				$cur_document['notifications'] = array('users'=>array(), 'groups'=>array());
			}
			break;
		case "FILES":
			$first = $elementstack[1];
			if($first['name'] == 'DOCUMENT') {
				$cur_document['files'] = array();
			}
			break;
		case "FILE":
			$first = $elementstack[1];
			if($first['name'] == 'DOCUMENT') {
				$cur_file = array();
				$cur_file['id'] = (int) $attrs['ID'];
			}
			break;
		case "LINKS":
			$first = $elementstack[1];
			if($first['name'] == 'DOCUMENT') {
				$cur_document['links'] = array();
			}
			break;
		case "LINK":
			$first = $elementstack[1];
			if($first['name'] == 'DOCUMENT') {
				$cur_link = array();
				$cur_link['id'] = (int) $attrs['ID'];
			}
			break;
	}
} /* }}} */

function endElement($parser, $name) { /* {{{ */
	global $dms, $sections, $rootfolder, $objmap, $elementstack, $users, $groups, $links,$cur_user, $cur_group, $cur_folder, $cur_document, $cur_version, $cur_statuslog, $cur_approval, $cur_approvallog, $cur_review, $cur_reviewlog, $cur_attrdef, $cur_documentcat, $cur_keyword, $cur_keywordcat, $cur_file, $cur_link;

	array_pop($elementstack);
	$parent = end($elementstack);
	switch($name) {
		case "DOCUMENT":
			insert_document($cur_document);
			if(!empty($cur_document['links']))
			$links[$cur_document['id']] = $cur_document['links'];
			break;
		case "FOLDER":
			insert_folder($cur_folder);
			break;
		case "VERSION":
			$cur_document['versions'][] = $cur_version;
			break;
		case "STATUSLOG":
			$cur_version['statuslogs'][] = $cur_statuslog;
			break;
		case "APPROVAL":
			$cur_version['approvals'][] = $cur_approval;
			break;
		case "APPROVALLOG":
			$cur_approval['logs'][] = $cur_approvallog;
			break;
		case "REVIEW":
			$cur_version['reviews'][] = $cur_review;
			break;
		case "REVIEWLOG":
			$cur_review['logs'][] = $cur_reviewlog;
			break;
		case "USER":
			/* users can be the users data or the member of a group */
			$first = $elementstack[1];
			if($first['name'] == 'USERS' && $parent['name'] == 'USERS') {
				$users[$cur_user['id']] = $cur_user;
				insert_user($cur_user);
			}
			break;
		case "GROUP":
			$first = $elementstack[1];
			if($first['name'] == 'GROUPS') {
				$groups[$cur_group['id']] = $cur_group;
				insert_group($cur_group);
			}
			break;
		case 'ATTRIBUTEDEFINITION':
			insert_attributedefinition($cur_attrdef);
			break;
		case 'KEYWORD':
			$cur_keywordcat['keywords'][] = $cur_keyword;
			break;
		case 'KEYWORDCATEGORY':
			insert_keywordcategory($cur_keywordcat);
			break;
		case 'DOCUMENTCATEGORY':
			insert_documentcategory($cur_documentcat);
			break;
		case "FILE":
			$first = $elementstack[1];
			if($first['name'] == 'DOCUMENT') {
				$cur_document['files'][] = $cur_file;
			}
			break;
		case "LINK":
			$first = $elementstack[1];
			if($first['name'] == 'DOCUMENT') {
				$cur_document['links'][] = $cur_link;
			}
			break;
	}
} /* }}} */

function characterData($parser, $data) { /* {{{ */
	global $elementstack, $objmap, $cur_user, $cur_group, $cur_folder, $cur_document, $cur_version, $cur_statuslog, $cur_approval, $cur_approvallog, $cur_review, $cur_reviewlog, $cur_attrdef, $cur_documentcat, $cur_keyword, $cur_keywordcat, $cur_file, $cur_link;

	$current = end($elementstack);
	$parent = prev($elementstack);
	switch($current['name']) {
		case 'ATTR':
			switch($parent['name']) {
				case 'DOCUMENT':
					if(isset($current['attributes']['TYPE']) && $current['attributes']['TYPE'] == 'user') {
						$cur_document['user_attributes'][$current['attributes']['ATTRDEF']] = $data;
					} else {
						$cur_document['attributes'][$current['attributes']['NAME']] = $data;
					}
					break;
				case 'FOLDER':
					if(isset($current['attributes']['TYPE']) && $current['attributes']['TYPE']  == 'user') {
						$cur_folder['user_attributes'][$current['attributes']['ATTRDEF']] = $data;
					} else {
						$cur_folder['attributes'][$current['attributes']['NAME']] = $data;
					}
					break;
				case 'VERSION':
					if(isset($current['attributes']['TYPE']) && $current['attributes']['TYPE']  == 'user') {
						$cur_version['user_attributes'][$current['attributes']['ATTRDEF']] = $data;
					} else {
						$cur_version['attributes'][$current['attributes']['NAME']] = $data;
					}
					break;
				case 'STATUSLOG':
					$cur_statuslog['attributes'][$current['attributes']['NAME']] = $data;
					break;
				case 'APPROVAL':
					$cur_approval['attributes'][$current['attributes']['NAME']] = $data;
					break;
				case 'APPROVALLOG':
					$cur_approvallog['attributes'][$current['attributes']['NAME']] = $data;
					break;
				case 'REVIEW':
					$cur_review['attributes'][$current['attributes']['NAME']] = $data;
					break;
				case 'REVIEWLOG':
					$cur_reviewlog['attributes'][$current['attributes']['NAME']] = $data;
					break;
				case 'USER':
					if(isset($cur_user['attributes'][$current['attributes']['NAME']]))
						$cur_user['attributes'][$current['attributes']['NAME']] .= $data;
					else
						$cur_user['attributes'][$current['attributes']['NAME']] = $data;
					break;
				case 'GROUP':
					if(isset($cur_group['attributes'][$current['attributes']['NAME']]))
						$cur_group['attributes'][$current['attributes']['NAME']] .= $data;
					else
						$cur_group['attributes'][$current['attributes']['NAME']] = $data;
					break;
				case 'ATTRIBUTEDEFINITION':
					if(isset($cur_attrdef['attributes'][$current['attributes']['NAME']]))
						$cur_attrdef['attributes'][$current['attributes']['NAME']] .= $data;
					else
						$cur_attrdef['attributes'][$current['attributes']['NAME']] = $data;
					break;
				case 'DOCUMENTCATEGORY':
					if(isset($cur_documentcat['attributes'][$current['attributes']['NAME']]))
						$cur_documentcat['attributes'][$current['attributes']['NAME']] .= $data;
					else
						$cur_documentcat['attributes'][$current['attributes']['NAME']] = $data;
					break;
				case 'KEYWORDCATEGORY':
					if(isset($cur_keywordcat['attributes'][$current['attributes']['NAME']]))
						$cur_keywordcat['attributes'][$current['attributes']['NAME']] .= $data;
					else
						$cur_keywordcat['attributes'][$current['attributes']['NAME']] = $data;
					break;
				case 'KEYWORD':
					if(isset($cur_keyword['attributes'][$current['attributes']['NAME']]))
						$cur_keyword['attributes'][$current['attributes']['NAME']] .= $data;
					else
						$cur_keyword['attributes'][$current['attributes']['NAME']] = $data;
					break;
				case 'IMAGE':
					$cur_user['image']['mimetype'] = $data;
					break;
				case 'FILE':
					$cur_file['attributes'][$current['attributes']['NAME']] = $data;
					break;
				case 'LINK':
					$cur_link['attributes'][$current['attributes']['NAME']] = $data;
					break;
			}
			break;
		case 'DATA':
			switch($parent['name']) {
				case 'IMAGE':
					$cur_user['image']['data'] .= $data;
					break;
				case 'VERSION':
					$cur_version['data'] .= $data;
					break;
				case 'FILE':
					$cur_file['data'] .= $data;
					break;
			}
			break;
		case 'USER':
			$first = $elementstack[1];
			if($first['name'] == 'GROUPS') {
				$cur_group['users'][] = $data;
			}
			break;
	}
	
} /* }}} */

$version = "0.0.1";
$shortoptions = "hv";
$longoptions = array('help', 'version', 'debug', 'config:', 'sections:', 'folder:', 'file:', 'contentdir:', 'default-user:', 'export-mapping:', 'no-version-check');
if(false === ($options = getopt($shortoptions, $longoptions))) {
	usage();
	exit(0);
}

/* Print help and exit */
if(isset($options['h']) || isset($options['help'])) {
	usage();
	exit(0);
}

/* Print version and exit */
if(isset($options['v']) || isset($options['verѕion'])) {
	echo $version."\n";
	exit(0);
}

/* Check for debug mode */
$debug = false;
if(isset($options['debug'])) {
	$debug = true;
}

/* Set alternative config file */
if(isset($options['config'])) {
	$settings = new Settings($options['config']);
} else {
	$settings = new Settings();
}

if(isset($options['folder'])) {
	$folderid = intval($options['folder']);
} else {
	$folderid = $settings->_rootFolderID;
}

if(isset($options['contentdir'])) {
	if(file_exists($options['contentdir'])) {
		$contentdir = $options['contentdir'];
		if(substr($contentdir, -1, 1) != DIRECTORY_SEPARATOR)
			$contentdir .= DIRECTORY_SEPARATOR;
	} else {
		echo "Directory ".$options['contentdir']." does not exists\n";
		exit(1);
	}
} else {
	$contentdir = '';
}

if(isset($options['default-user'])) {
	$defaultuserid = intval($options['default-user']);
} else {
	$defaultuserid = 0;
}

$filename = '';
if(isset($options['file'])) {
	$filename = $options['file'];
} else {
	usage();
	exit(1);
}

$exportmapping = '';
if(isset($options['export-mapping'])) {
	$exportmapping = $options['export-mapping'];
}

$noversioncheck = false;
if(isset($options['no-version-check'])) {
	$noversioncheck = true;
}

$sections = array('documents', 'folders', 'groups', 'users', 'keywordcategories', 'documentcategories', 'attributedefinitions');
if(isset($options['sections'])) {
	$sections = explode(',', $options['sections']);
}

if(isset($settings->_extraPath))
	ini_set('include_path', $settings->_extraPath. PATH_SEPARATOR .ini_get('include_path'));

require_once("SeedDMS/Core.php");

$db = new SeedDMS_Core_DatabaseAccess($settings->_dbDriver, $settings->_dbHostname, $settings->_dbUser, $settings->_dbPass, $settings->_dbDatabase);
$db->connect() or die ("Could not connect to db-server \"" . $settings->_dbHostname . "\"");

$dms = new SeedDMS_Core_DMS($db, $settings->_contentDir.$settings->_contentOffsetDir);
if(!$settings->_doNotCheckDBVersion && !$dms->checkVersion()) {
	echo "Database update needed.";
	exit;
}
$dms->setRootFolderID($settings->_rootFolderID);

$rootfolder = $dms->getFolder($folderid);
if(!$rootfolder) {
	exit(1);
}

if($defaultuserid) {
	if(!$defaultUser = $dms->getUser($defaultuserid)) {
		echo "Error: Could not find default user with id ".$defaultuserid."\n";
		exit(1);
	}
} else {
	$defaultUser = null;
}

$elementstack = array();
$objmap = array(
	'attributedefs' => array(),
	'keywordcategories' => array(),
	'documentcategories' => array(),
	'users' => array(),
	'groups' => array(),
	'folders' => array(),
	'documents' => array(),
);

$xml_parser = xml_parser_create("UTF-8");
xml_parser_set_option($xml_parser, XML_OPTION_SKIP_WHITE, true);
xml_set_element_handler($xml_parser, "startElement", "endElement");
xml_set_character_data_handler($xml_parser, "characterData");
if (!($fp = fopen($filename, "r"))) {
    die("could not open XML input");
}
while ($data = fread($fp, 65535)) {
	if (!xml_parse($xml_parser, $data, feof($fp))) {
		die(sprintf("XML error: %s at line %d",
			xml_error_string(xml_get_error_code($xml_parser)),
			xml_get_current_line_number($xml_parser)));
	}
}

resolve_links();

set_homefolders();

if($exportmapping) {
	if($fp = fopen($exportmapping, 'w')) {
		fputcsv($fp, array('object type', 'old id', 'new id'));
		foreach($objmap as $section=>$map) {
			foreach($map as $old=>$new) {
				fputcsv($fp, array($section, $old, $new));
			}
		}
		fclose($fp);
	} else {
		echo "Error: could not open mapping file '".$exportmapping."'\n";
	}
}
?>
