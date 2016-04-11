<?php
/**
 * Implementation of the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010, Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include some files
 */
require_once("inc.AccessUtils.php");
require_once("inc.FileUtils.php");
require_once("inc.ClassAccess.php");
require_once("inc.ClassObject.php");
require_once("inc.ClassFolder.php");
require_once("inc.ClassDocument.php");
require_once("inc.ClassGroup.php");
require_once("inc.ClassUser.php");
require_once("inc.ClassKeywords.php");
require_once("inc.ClassNotification.php");
require_once("inc.ClassAttribute.php");

/**
 * Class to represent the complete document management system.
 * This class is needed to do most of the dms operations. It needs
 * an instance of {@link SeedDMS_Core_DatabaseAccess} to access the
 * underlying database. Many methods are factory functions which create
 * objects representing the entities in the dms, like folders, documents,
 * users, or groups.
 *
 * Each dms has its own database for meta data and a data store for document
 * content. Both must be specified when creating a new instance of this class.
 * All folders and documents are organized in a hierachy like
 * a regular file system starting with a {@link $rootFolderID}
 *
 * This class does not enforce any access rights on documents and folders
 * by design. It is up to the calling application to use the methods
 * {@link SeedDMS_Core_Folder::getAccessMode()} and
 * {@link SeedDMS_Core_Document::getAccessMode()} and interpret them as desired.
 * Though, there are two convinient functions to filter a list of
 * documents/folders for which users have access rights for. See
 * {@link SeedDMS_Core_DMS::filterAccess()}
 * and {@link SeedDMS_Core_DMS::filterUsersByAccess()}
 *
 * Though, this class has two methods to set the currently logged in user
 * ({@link setUser} and {@link login}), none of them need to be called, because
 * there is currently no class within the SeedDMS core which needs the logged
 * in user.
 *
 * <code>
 * <?php
 * include("inc/inc.ClassDMS.php");
 * $db = new SeedDMS_Core_DatabaseAccess($type, $hostname, $user, $passwd, $name);
 * $db->connect() or die ("Could not connect to db-server");
 * $dms = new SeedDMS_Core_DMS($db, $contentDir);
 * $dms->setRootFolderID(1);
 * ...
 * ?>
 * </code>
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010, Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Core_DMS {
	/**
	 * @var object $db reference to database object. This must be an instance
	 *      of {@link SeedDMS_Core_DatabaseAccess}.
	 * @access protected
	 */
	protected $db;

	/**
	 * @var array $classnames list of classnames for objects being instanciate
	 *      by the dms
	 * @access protected
	 */
	protected $classnames;

	/**
	 * @var object $user reference to currently logged in user. This must be
	 *      an instance of {@link SeedDMS_Core_User}. This variable is currently not
	 *      used. It is set by {@link setUser}.
	 * @access private
	 */
	private $user;

	/**
	 * @var string $contentDir location in the file system where all the
	 *      document data is located. This should be an absolute path.
	 * @access public
	 */
	public $contentDir;

	/**
	 * @var integer $rootFolderID ID of root folder
	 * @access public
	 */
	public $rootFolderID;

	/**
	 * @var integer $maxDirID maximum number of documents per folder on the
	 *      filesystem. If this variable is set to a value != 0, the content
	 *      directory will have a two level hierarchy for document storage.
	 * @access public
	 */
	public $maxDirID;

	/**
	 * @var boolean $enableConverting set to true if conversion of content
	 *      is desired
	 * @access public
	 */
	public $enableConverting;

	/**
	 * @var boolean $forceRename use renameFile() instead of copyFile() when
	 *      copying the document content into the data store. The default is
	 *      to copy the file. This parameter only affects the methods
	 *      SeedDMS_Core_Document::addDocument() and
	 *      SeedDMS_Core_Document::addDocumentFile(). Setting this to true
	 *      may save resources especially for large files.
	 * @access public
	 */
	public $forceRename;

	/**
	 * @var array $convertFileTypes list of files types that shall be converted
	 * @access public
	 */
	public $convertFileTypes;

	/**
	 * @var array $viewOnlineFileTypes list of files types that can be viewed
	 *      online
	 * @access public
	 */
	public $viewOnlineFileTypes;

	/**
	 * @var string $version version of pear package
	 * @access public
	 */
	public $version;

	/**
	 * @var array $callbacks list of methods called when certain operations,
	 * like removing a document, are executed. Set a callback with
	 * {@link SeedDMS_Core_DMS::setCallback()}.
	 * The key of the array is the internal callback function name. Each
	 * array element is an array with two elements: the function name
	 * and the parameter passed to the function.
	 *
	 * Currently implemented callbacks are:
	 *
	 * onPreRemoveDocument($user_param, $document);
	 *   called before deleting a document. If this function returns false
	 *   the document will not be deleted.
	 *
	 * onPostRemoveDocument($user_param, $document_id);
	 *   called after the successful deletion of a document.
	 *
	 * @access public
	 */
	public $callbacks;


	/**
	 * Checks if two objects are equal by comparing its ID
	 *
	 * The regular php check done by '==' compares all attributes of
	 * two objects, which isn't required. The method will first check
	 * if the objects are instances of the same class.
	 *
	 * @param object $object1 first object to be compared
	 * @param object $object2 second object to be compared
	 * @return boolean true if objects are equal, otherwise false
	 */
	static function checkIfEqual($object1, $object2) { /* {{{ */
		if(get_class($object1) != get_class($object2))
			return false;
		if($object1->getID() != $object2->getID())
			return false;
		return true;
	} /* }}} */

	/**
	 * Checks if a list of objects contains a single object 
	 *
	 * This function is only applicable on list containing objects which have
	 * a method getID() because it is used to check if two objects are equal.
	 * The regular php check on objects done by '==' compares all attributes of
	 * two objects, which isn't required. The method will first check
	 * if the objects are instances of the same class.
	 *
	 * The result of the function can be 0 which happens if the first element
	 * of an indexed array matches.
	 *
	 * @param object $object1 object to look for (needle)
	 * @param array $list list of objects (haystack)
	 * @return boolean/integer index in array if object was found, otherwise false
	 */
	static function inList($object, $list) { /* {{{ */
		foreach($list as $i=>$item) {
			if(get_class($item) == get_class($object) && $item->getID() == $object->getID())
				return $i;
		}
		return false;
	} /* }}} */

	/**
	 * Checks if date conforms to a given format
	 *
	 * @param string $date date to be checked
	 * @return boolean true if date is in propper format, otherwise false
	 */
	static function checkDate($date, $format='Y-m-d H:i:s') { /* {{{ */
		$d = DateTime::createFromFormat($format, $date);
		return $d && $d->format($format) == $date;
	} /* }}} */

	/**
	 * Filter objects out which are not accessible in a given mode by a user.
	 *
	 * @param array $objArr list of objects (either documents or folders)
	 * @param object $user user for which access is checked
	 * @param integer $minMode minimum access mode required
	 * @return array filtered list of objects
	 */
	static function filterAccess($objArr, $user, $minMode) { /* {{{ */
		if (!is_array($objArr)) {
			return array();
		}
		$newArr = array();
		foreach ($objArr as $obj) {
			if ($obj->getAccessMode($user) >= $minMode)
				array_push($newArr, $obj);
		}
		return $newArr;
	} /* }}} */

	/**
	 * Filter users out which cannot access an object in a given mode.
	 *
	 * @param object $obj object that shall be accessed
	 * @param array $users list of users which are to check for sufficient
	 *        access rights
	 * @param integer $minMode minimum access right on the object for each user
	 * @return array filtered list of users
	 */
	static function filterUsersByAccess($obj, $users, $minMode) { /* {{{ */
		$newArr = array();
		foreach ($users as $currUser) {
			if ($obj->getAccessMode($currUser) >= $minMode)
				array_push($newArr, $currUser);
		}
		return $newArr;
	} /* }}} */

	/**
	 * Filter document links
	 *
	 * Returns a filtered list of links which are accessible by the
	 * given user.
	 *
	 * @param array $links list of objects of type SeedDMS_Core_DocumentLink
	 * @param object $user user for which access is being checked
	 * @return array filtered list of links
	 */
	static function filterDocumentLinks($user, $links) { /* {{{ */
		$tmp = array();
		foreach ($links as $link)
			if ($link->isPublic() || ($link->getUser()->getID() == $user->getID()) || $user->isAdmin())
				array_push($tmp, $link);
		return $tmp;
	} /* }}} */

	/**
	 * Create a new instance of the dms
	 *
	 * @param object $db object to access the underlying database
	 * @param string $contentDir path in filesystem containing the data store
	 *        all document contents is stored
	 * @return object instance of {@link SeedDMS_Core_DMS}
	 */
	function __construct($db, $contentDir) { /* {{{ */
		$this->db = $db;
		if(substr($contentDir, -1) == '/')
			$this->contentDir = $contentDir;
		else
			$this->contentDir = $contentDir.'/';
		$this->rootFolderID = 1;
		$this->maxDirID = 0; //31998;
		$this->forceRename = false;
		$this->enableConverting = false;
		$this->convertFileTypes = array();
		$this->classnames = array();
		$this->classnames['folder'] = 'SeedDMS_Core_Folder';
		$this->classnames['document'] = 'SeedDMS_Core_Document';
		$this->classnames['documentcontent'] = 'SeedDMS_Core_DocumentContent';
		$this->classnames['user'] = 'SeedDMS_Core_User';
		$this->classnames['group'] = 'SeedDMS_Core_Group';
		$this->version = '@package_version@';
		if($this->version[0] == '@')
			$this->version = '5.0.1';
	} /* }}} */

	/**
	 * Return class name of instantiated objects
	 *
	 * This method returns the class name of those objects being instatiated
	 * by the dms. Each class has an internal place holder, which must be
	 * passed to function.
	 *
	 * @param string placeholder (can be one of 'folder', 'document',
	 * 'documentcontent', 'user', 'group'
	 *
	 * @return string/boolean name of class or false if placeholder is invalid
	 */
	function getClassname($objectname) { /* {{{ */
		if(isset($this->classnames[$objectname]))
			return $this->classnames[$objectname];
		else
			return false;
	} /* }}} */

	/**
	 * Set class name of instantiated objects
	 *
	 * This method sets the class name of those objects being instatiated
	 * by the dms. It is mainly used to create a new class (possible
	 * inherited from one of the available classes) implementing new
	 * features. The method should be called in the postInitDMS hook.
	 *
	 * @param string placeholder (can be one of 'folder', 'document',
	 * 'documentcontent', 'user', 'group'
	 * @param string name of class
	 *
	 * @return string/boolean name of old class or false if not set
	 */
	function setClassname($objectname, $classname) { /* {{{ */
		if(isset($this->classnames[$objectname]))
			$oldclass =  $this->classnames[$objectname];
		else
			$oldclass = false;
		$this->classnames[$objectname] = $classname;
		return $oldclass;
	} /* }}} */

	/**
	 * Return database where meta data is stored
	 *
	 * This method returns the database object as it was set by the first
	 * parameter of the constructor.
	 *
	 * @return object database
	 */
	function getDB() { /* {{{ */
		return $this->db;
	} /* }}} */

	/**
	 * Return the database version
	 *
	 * @return array array with elements major, minor, subminor, date
	 */
	function getDBVersion() { /* {{{ */
		$tbllist = $this->db->TableList();
		$tbllist = explode(',',strtolower(join(',',$tbllist)));
		if(!array_search('tblversion', $tbllist))
			return false;
		$queryStr = "SELECT * FROM tblVersion order by major,minor,subminor limit 1";
		$resArr = $this->db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		if (count($resArr) != 1)
			return false;
		$resArr = $resArr[0];
		return $resArr;
	} /* }}} */

	/**
	 * Check if the version in the database is the same as of this package
	 * Only the major and minor version number will be checked.
	 *
	 * @return boolean returns false if versions do not match, but returns
	 *         true if version matches or table tblVersion does not exists.
	 */
	function checkVersion() { /* {{{ */
		$tbllist = $this->db->TableList();
		$tbllist = explode(',',strtolower(join(',',$tbllist)));
		if(!array_search('tblversion', $tbllist))
			return true;
		$queryStr = "SELECT * FROM tblVersion order by major,minor,subminor limit 1";
		$resArr = $this->db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		if (count($resArr) != 1)
			return false;
		$resArr = $resArr[0];
		$ver = explode('.', $this->version);
		if(($resArr['major'] != $ver[0]) || ($resArr['minor'] != $ver[1]))
			return false;
		return true;
	} /* }}} */

	/**
	 * Set id of root folder
	 * This function must be called right after creating an instance of
	 * {@link SeedDMS_Core_DMS}
	 *
	 * @param interger $id id of root folder
	 */
	function setRootFolderID($id) { /* {{{ */
		$this->rootFolderID = $id;
	} /* }}} */

	/**
	 * Set maximum number of subdirectories per directory
	 *
	 * The value of maxDirID is quite crucial, because each document is
	 * stored within a directory in the filesystem. Consequently, there can be
	 * a maximum number of documents, because depending on the file system
	 * the maximum number of subdirectories is limited. Since version 3.3.0 of
	 * SeedDMS an additional directory level has been introduced, which
	 * will be created when maxDirID is not 0. All documents
	 * from 1 to maxDirID-1 will be saved in 1/<docid>, documents from maxDirID
	 * to 2*maxDirID-1 are stored in 2/<docid> and so on.
	 *
	 * Modern file systems like ext4 do not have any restrictions on the number
	 * of subdirectories anymore. Therefore it is best if this parameter is
	 * set to 0. Never change this parameter if documents has already been
	 * created.
	 *
	 * This function must be called right after creating an instance of
	 * {@link SeedDMS_Core_DMS}
	 *
	 * @param interger $id id of root folder
	 */
	function setMaxDirID($id) { /* {{{ */
		$this->maxDirID = $id;
	} /* }}} */

	/**
	 * Get root folder
	 *
	 * @return object/boolean return the object of the root folder or false if
	 *        the root folder id was not set before with {@link setRootFolderID}.
	 */
	function getRootFolder() { /* {{{ */
		if(!$this->rootFolderID) return false;
		return $this->getFolder($this->rootFolderID);
	} /* }}} */

	function setEnableConverting($enable) { /* {{{ */
		$this->enableConverting = $enable;
	} /* }}} */

	function setConvertFileTypes($types) { /* {{{ */
		$this->convertFileTypes = $types;
	} /* }}} */

	function setViewOnlineFileTypes($types) { /* {{{ */
		$this->viewOnlineFileTypes = $types;
	} /* }}} */

	function setForceRename($enable) { /* {{{ */
		$this->forceRename = $enable;
	} /* }}} */

	/**
	 * Login as a user
	 *
	 * Checks if the given credentials are valid and returns a user object.
	 * It also sets the property $user for later access on the currently
	 * logged in user
	 *
	 * @param string $username login name of user
	 * @param string $password password of user
	 *
	 * @return object instance of class {@link SeedDMS_Core_User} or false
	 */
	function login($username, $password) { /* {{{ */
	} /* }}} */

	/**
	 * Set the logged in user
	 *
	 * If user authentication was done externally, this function can
	 * be used to tell the dms who is currently logged in.
	 *
	 * @param object $user
	 *
	 */
	function setUser($user) { /* {{{ */
		$this->user = $user;
	} /* }}} */

	/**
	 * Return a document by its id
	 *
	 * This function retrieves a document from the database by its id.
	 *
	 * @param integer $id internal id of document
	 * @return object instance of {@link SeedDMS_Core_Document} or false
	 */
	function getDocument($id) { /* {{{ */
		$classname = $this->classnames['document'];
		return $classname::getInstance($id, $this);
	} /* }}} */

	/**
	 * Return a document ID by its unique document number
	 *
	 * This function retrieves the id of a document
	 * from the database by its document number.
	 *
	 * @param string $number document number
	 * @return integer id of document or false
	 */
	function getDocumentIDByNumber($number) { /* {{{ */
		if(!$number) return false;

		$queryStr = "SELECT `document` AS `myDoc` FROM `tblMemoNumbers` WHERE `number`='".$number."' UNION SELECT `document` AS `myDoc` FROM `tblSpecNumbers` WHERE `number`='".$number."'";
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && !$resArr)
			return false;
		return  $resArr[0]['myDoc'];
	} /* }}} */

	/**
	 * Returns all documents of a given user
	 *
	 * @param object $user
	 * @return array list of documents
	 */
	function getDocumentsByUser($user) { /* {{{ */
		return $user->getDocuments();
	} /* }}} */

	/**
	 * Returns all documents locked by a given user
	 *
	 * @param object $user
	 * @return array list of documents
	 */
	function getDocumentsLockedByUser($user) { /* {{{ */
		return $user->getDocumentsLocked();
	} /* }}} */

	/**
	 * Returns a document by its name
	 *
	 * This function searches a document by its name and restricts the search
	 * to given folder if passed as the second parameter.
	 *
	 * @param string $name
	 * @param object $folder
	 * @return object/boolean found document or false
	 */
	function getDocumentByName($name, $folder=null) { /* {{{ */
		if (!$name) return false;

		$queryStr = "SELECT `tblDocuments`.*, `tblDocumentLocks`.`userID` as `lockUser` ".
			"FROM `tblDocuments` ".
			"LEFT JOIN `tblDocumentLocks` ON `tblDocuments`.`id`=`tblDocumentLocks`.`document` ".
			"WHERE `tblDocuments`.`name` = " . $this->db->qstr($name);
		if($folder)
			$queryStr .= " AND `tblDocuments`.`folder` = ". $folder->getID();
		$queryStr .= " LIMIT 1";

		$resArr = $this->db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		if(!$resArr)
			return false;

		$row = $resArr[0];
		$document = new $this->classnames['document']($row["id"], $row["name"], $row["comment"], $row["date"], $row["expires"], $row["owner"], $row["folder"], $row["inheritAccess"], $row["defaultAccess"], $row["lockUser"], $row["keywords"], $row["sequence"]);
		$document->setDMS($this);
		return $document;
	} /* }}} */

	/**
	 * Return a document content by its id
	 *
	 * This function retrieves a document content from the database by its id.
	 *
	 * @param integer $id internal id of document content
	 * @return object instance of {@link SeedDMS_Core_DocumentContent} or false
	 */
	function getDocumentContent($id) { /* {{{ */
		if (!is_numeric($id)) return false;

		$queryStr = "SELECT * FROM tblDocumentContent WHERE id = ".(int) $id;
		$resArr = $this->db->getResultArray($queryStr);
		if (is_bool($resArr) && $resArr == false)
			return false;
		if (count($resArr) != 1)
			return false;
		$row = $resArr[0];

		$document = $this->getDocument($row['document']);
		$version = new $this->classnames['documentcontent']($row['id'], $document, $row['version'], $row['comment'], $row['date'], $row['createdBy'], $row['dir'], $row['orgFileName'], $row['fileType'], $row['mimeType'], $row['fileSize'], $row['checksum']);
		return $version;
	} /* }}} */

	function makeTimeStamp($hour, $min, $sec, $year, $month, $day) { /* {{{ */
		$thirtyone = array (1, 3, 5, 7, 8, 10, 12);
		$thirty = array (4, 6, 9, 11);

		// Very basic check that the terms are valid. Does not fail for illegal
		// dates such as 31 Feb.
		if (!is_numeric($hour) || !is_numeric($min) || !is_numeric($sec) || !is_numeric($year) || !is_numeric($month) || !is_numeric($day) || $month<1 || $month>12 || $day<1 || $day>31 || $hour<0 || $hour>23 || $min<0 || $min>59 || $sec<0 || $sec>59) {
			return false;
		}
		$year = (int) $year;
		$month = (int) $month;
		$day = (int) $day;

		if (array_search($month, $thirtyone)) {
			$max=31;
		}
		else if (array_search($month, $thirty)) {
			$max=30;
		}
		else {
			$max=(($year % 4 == 0) && ($year % 100 != 0 || $year % 400 == 0)) ? 29 : 28;
		}

		// If the date falls out of bounds, set it to the maximum for the given
		// month. Makes assumption about the user's intention, rather than failing
		// for absolutely everything.
		if ($day>$max) {
			$day=$max;
		}

		return mktime($hour, $min, $sec, $month, $day, $year);
	} /* }}} */

	/*
	 * Search the database for documents
	 *
	 * Note: the creation date will be used to check againts the
	 * date saved with the document
	 * or folder. The modification date will only be used for documents. It
	 * is checked against the creation date of the document content. This
	 * meanѕ that updateѕ of a document will only result in a searchable
	 * modification if a new version is uploaded.
	 *
	 * @param query string seach query with space separated words
	 * @param limit integer number of items in result set
	 * @param offset integer index of first item in result set
	 * @param logicalmode string either AND or OR
	 * @param searchin array() list of fields to search in
	 *        1 = keywords, 2=name, 3=comment, 4=attributes
	 * @param startFolder object search in the folder only (null for root folder)
	 * @param owner object search for documents owned by this user
	 * @param status array list of status
	 * @param creationstartdate array search for documents created after this date
	 * @param creationenddate array search for documents created before this date
	 * @param modificationstartdate array search for documents modified after this date
	 * @param modificationenddate array search for documents modified before this date
	 * @param categories array list of categories the documents must have assigned
	 * @param attributes array list of attributes. The key of this array is the
	 * attribute definition id. The value of the array is the value of the
	 * attribute. If the attribute may have multiple values it must be an array.
	 * @param mode int decide whether to search for documents/folders
	 *        0x1 = documents only
	 *        0x2 = folders only
	 *        0x3 = both
	 * @param expirationstartdate array search for documents expiring after this date
	 * @param expirationenddate array search for documents expiring before this date
	 * @return array containing the elements total and docs
	 */
	function search($query, $limit=0, $offset=0, $logicalmode='AND', $searchin=array(), $startFolder=null, $owner=null, $status = array(), $creationstartdate=array(), $creationenddate=array(), $modificationstartdate=array(), $modificationenddate=array(), $categories=array(), $attributes=array(), $mode=0x3, $expirationstartdate=array(), $expirationenddate=array()) { /* {{{ */
		// Split the search string into constituent keywords.
		$tkeys=array();
		if (strlen($query)>0) {
			$tkeys = preg_split("/[\t\r\n ,]+/", $query);
		}

		// if none is checkd search all
		if (count($searchin)==0)
			$searchin=array(1, 2, 3, 4);

		/*--------- Do it all over again for folders -------------*/
		$totalFolders = 0;
		if($mode & 0x2) {
			$searchKey = "";

			$classname = $this->classnames['folder'];
			$searchFields = $classname::getSearchFields($searchin);

			if (count($searchFields)>0) {
				foreach ($tkeys as $key) {
					$key = trim($key);
					if (strlen($key)>0) {
						$searchKey = (strlen($searchKey)==0 ? "" : $searchKey." ".$logicalmode." ")."(".implode(" like ".$this->db->qstr("%".$key."%")." OR ", $searchFields)." like ".$this->db->qstr("%".$key."%").")";
					}
				}
			}

			// Check to see if the search has been restricted to a particular sub-tree in
			// the folder hierarchy.
			$searchFolder = "";
			if ($startFolder) {
				$searchFolder = "`tblFolders`.`folderList` LIKE '%:".$startFolder->getID().":%'";
			}

			// Check to see if the search has been restricted to a particular
			// document owner.
			$searchOwner = "";
			if ($owner) {
				if(is_array($owner)) {
					$ownerids = array();
					foreach($owner as $o)
						$ownerids[] = $o->getID();
					if($ownerids)
						$searchOwner = "`tblFolders`.`owner` IN (".implode(',', $ownerids).")";
				} else {
					$searchOwner = "`tblFolders`.`owner` = '".$owner->getId()."'";
				}
			}

			// Check to see if the search has been restricted to a particular
			// attribute.
			$searchAttributes = array();
			if ($attributes) {
				foreach($attributes as $attrdefid=>$attribute) {
					if($attribute) {
						$attrdef = $this->getAttributeDefinition($attrdefid);
						if($attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_folder || $attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_all) {
							if($valueset = $attrdef->getValueSet()) {
								if($attrdef->getMultipleValues()) {
									$searchAttributes[] = "EXISTS (SELECT NULL FROM `tblFolderAttributes` WHERE `tblFolderAttributes`.`attrdef`=".$attrdefid." AND (`tblFolderAttributes`.`value` like '%".$valueset[0].implode("%' OR `tblFolderAttributes`.`value` like '%".$valueset[0], $attribute)."%') AND `tblFolderAttributes`.`folder`=`tblFolders`.`id`)";
								} else
									$searchAttributes[] = "EXISTS (SELECT NULL FROM `tblFolderAttributes` WHERE `tblFolderAttributes`.`attrdef`=".$attrdefid." AND `tblFolderAttributes`.`value`='".$attribute."' AND `tblFolderAttributes`.`folder`=`tblFolders`.`id`)";
							} else
								$searchAttributes[] = "EXISTS (SELECT NULL FROM `tblFolderAttributes` WHERE `tblFolderAttributes`.`attrdef`=".$attrdefid." AND `tblFolderAttributes`.`value` like '%".$attribute."%' AND `tblFolderAttributes`.`folder`=`tblFolders`.`id`)";
						}
					}
				}
			}

			// Is the search restricted to documents created between two specific dates?
			$searchCreateDate = "";
			if ($creationstartdate) {
				$startdate = SeedDMS_Core_DMS::makeTimeStamp($creationstartdate['hour'], $creationstartdate['minute'], $creationstartdate['second'], $creationstartdate['year'], $creationstartdate["month"], $creationstartdate["day"]);
				if ($startdate) {
					$searchCreateDate .= "`tblFolders`.`date` >= ".$startdate;
				}
			}
			if ($creationenddate) {
				$stopdate = SeedDMS_Core_DMS::makeTimeStamp($creationenddate['hour'], $creationstartdate['minute'], $creationstartdate['second'], $creationenddate["year"], $creationenddate["month"], $creationenddate["day"]);
				if ($stopdate) {
					if($startdate)
						$searchCreateDate .= " AND ";
					$searchCreateDate .= "`tblFolders`.`date` <= ".$stopdate;
				}
			}

			$searchQuery = "FROM ".$classname::getSearchTables()." WHERE 1=1";

			if (strlen($searchKey)>0) {
				$searchQuery .= " AND (".$searchKey.")";
			}
			if (strlen($searchFolder)>0) {
				$searchQuery .= " AND ".$searchFolder;
			}
			if (strlen($searchOwner)>0) {
				$searchQuery .= " AND (".$searchOwner.")";
			}
			if (strlen($searchCreateDate)>0) {
				$searchQuery .= " AND (".$searchCreateDate.")";
			}
			if ($searchAttributes) {
				$searchQuery .= " AND (".implode(" AND ", $searchAttributes).")";
			}

			/* Do not search for folders if not at least a search for a key,
			 * an owner, or creation date is requested.
			 */
			if($searchKey || $searchOwner || $searchCreateDate || $searchAttributes) {
				// Count the number of rows that the search will produce.
				$resArr = $this->db->getResultArray("SELECT COUNT(*) AS num FROM (SELECT DISTINCT `tblFolders`.id ".$searchQuery.") a");
				if ($resArr && isset($resArr[0]) && is_numeric($resArr[0]["num"]) && $resArr[0]["num"]>0) {
					$totalFolders = (integer)$resArr[0]["num"];
				}

				// If there are no results from the count query, then there is no real need
				// to run the full query. TODO: re-structure code to by-pass additional
				// queries when no initial results are found.

				// Only search if the offset is not beyond the number of folders
				if($totalFolders > $offset) {
					// Prepare the complete search query, including the LIMIT clause.
					$searchQuery = "SELECT DISTINCT `tblFolders`.* ".$searchQuery." GROUP BY `tblFolders`.`id`";

					if($limit) {
						$searchQuery .= " LIMIT ".$offset.",".$limit;
					}

					// Send the complete search query to the database.
					$resArr = $this->db->getResultArray($searchQuery);
				} else {
					$resArr = array();
				}

				// ------------------- Ausgabe der Ergebnisse ----------------------------
				$numResults = count($resArr);
				if ($numResults == 0) {
					$folderresult = array('totalFolders'=>$totalFolders, 'folders'=>array());
				} else {
					foreach ($resArr as $folderArr) {
						$folders[] = $this->getFolder($folderArr['id']);
					}
					$folderresult = array('totalFolders'=>$totalFolders, 'folders'=>$folders);
				}
			} else {
				$folderresult = array('totalFolders'=>0, 'folders'=>array());
			}
		} else {
			$folderresult = array('totalFolders'=>0, 'folders'=>array());
		}

		/*--------- Do it all over again for documents -------------*/

		$totalDocs = 0;
		if($mode & 0x1) {
			$searchKey = "";
			$searchFields = array();
			if (in_array(1, $searchin)) {
				$searchFields[] = "`tblDocuments`.`keywords`";
			}
			if (in_array(2, $searchin)) {
				$searchFields[] = "`tblDocuments`.`name`";
				$searchFields[] = "`tblMemoNumbers`.`number`";
				$searchFields[] = "`tblSpecNumbers`.`number`";
			}
			if (in_array(3, $searchin)) {
				$searchFields[] = "`tblDocuments`.`comment`";
			}
			if (in_array(4, $searchin)) {
				$searchFields[] = "`tblDocumentAttributes`.`value`";
				$searchFields[] = "`tblDocumentContentAttributes`.`value`";
			}

			if (count($searchFields)>0) {
				foreach ($tkeys as $key) {
					$key = trim($key);
					if (strlen($key)>0) {
						$searchKey = (strlen($searchKey)==0 ? "" : $searchKey." ".$logicalmode." ")."(".implode(" like ".$this->db->qstr("%".$key."%")." OR ", $searchFields)." like ".$this->db->qstr("%".$key."%").")";
					}
				}
			}

			// Check to see if the search has been restricted to a particular sub-tree in
			// the folder hierarchy.
			$searchFolder = "";
			if ($startFolder) {
				$searchFolder = "`tblDocuments`.`folderList` LIKE '%:".$startFolder->getID().":%'";
			}

			// Check to see if the search has been restricted to a particular
			// document owner.
			$searchOwner = "";
			if ($owner) {
				if(is_array($owner)) {
					$ownerids = array();
					foreach($owner as $o)
						$ownerids[] = $o->getID();
					if($ownerids)
						$searchOwner = "`tblDocuments`.`owner` IN (".implode(',', $ownerids).")";
				} else {
					$searchOwner = "`tblDocuments`.`owner` = '".$owner->getId()."'";
				}
			}

			// Check to see if the search has been restricted to a particular
			// document category.
			$searchCategories = "";
			if ($categories) {
				$catids = array();
				foreach($categories as $category)
					$catids[] = $category->getId();
				$searchCategories = "`tblDocumentCategory`.`categoryID` in (".implode(',', $catids).")";
			}

			// Check to see if the search has been restricted to a particular
			// attribute.
			$searchAttributes = array();
			if ($attributes) {
				foreach($attributes as $attrdefid=>$attribute) {
					if($attribute) {
						$attrdef = $this->getAttributeDefinition($attrdefid);
						if($attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_document || $attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_all) {
							if($valueset = $attrdef->getValueSet()) {
								if($attrdef->getMultipleValues()) {
									$searchAttributes[] = "EXISTS (SELECT NULL FROM `tblDocumentAttributes` WHERE `tblDocumentAttributes`.`attrdef`=".$attrdefid." AND (`tblDocumentAttributes`.`value` like '%".$valueset[0].implode("%' OR `tblDocumentAttributes`.`value` like '%".$valueset[0], $attribute)."%') AND `tblDocumentAttributes`.`document` = `tblDocuments`.`id`)";
								} else
									$searchAttributes[] = "EXISTS (SELECT NULL FROM `tblDocumentAttributes` WHERE `tblDocumentAttributes`.`attrdef`=".$attrdefid." AND `tblDocumentAttributes`.`value`='".$attribute."' AND `tblDocumentAttributes`.`document` = `tblDocuments`.`id`)";
							} else
								$searchAttributes[] = "EXISTS (SELECT NULL FROM `tblDocumentAttributes` WHERE `tblDocumentAttributes`.`attrdef`=".$attrdefid." AND `tblDocumentAttributes`.`value` like '%".$attribute."%' AND `tblDocumentAttributes`.`document` = `tblDocuments`.`id`)";
						} elseif($attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_documentcontent) {
							if($attrdef->getValueSet()) {
								if($attrdef->getMultipleValues()) {
									$searchAttributes[] = "EXISTS (SELECT NULL FROM `tblDocumentContentAttributes` WHERE `tblDocumentContentAttributes`.`attrdef`=".$attrdefid." AND (`tblDocumentContentAttributes`.`value` like '%".$valueset[0].implode("%' OR `tblDocumentContentAttributes`.`value` like '%".$valueset[0], $attribute)."%') AND `tblDocumentContentAttributes`.`document` = `tblDocumentContent`.`id`)";
								} else {
									$searchAttributes[] = "EXISTS (SELECT NULL FROM `tblDocumentContentAttributes` WHERE `tblDocumentContentAttributes`.`attrdef`=".$attrdefid." AND `tblDocumentContentAttributes`.`value`='".$attribute."' AND `tblDocumentContentAttributes`.content = `tblDocumentContent`.id)";
								}
							} else
								$searchAttributes[] = "EXISTS (SELECT NULL FROM `tblDocumentContentAttributes` WHERE `tblDocumentContentAttributes`.`attrdef`=".$attrdefid." AND `tblDocumentContentAttributes`.`value` like '%".$attribute."%' AND `tblDocumentContentAttributes`.content = `tblDocumentContent`.id)";
						}
					}
				}
			}

			// Is the search restricted to documents created between two specific dates?
			$searchCreateDate = "";
			if ($creationstartdate) {
				$startdate = SeedDMS_Core_DMS::makeTimeStamp($creationstartdate['hour'], $creationstartdate['minute'], $creationstartdate['second'], $creationstartdate['year'], $creationstartdate["month"], $creationstartdate["day"]);
				if ($startdate) {
					$searchCreateDate .= "`tblDocuments`.`date` >= ".$startdate;
				}
			}
			if ($creationenddate) {
				$stopdate = SeedDMS_Core_DMS::makeTimeStamp($creationenddate['hour'], $creationenddate['minute'], $creationenddate['second'], $creationenddate["year"], $creationenddate["month"], $creationenddate["day"]);
				if ($stopdate) {
					if($searchCreateDate)
						$searchCreateDate .= " AND ";
					$searchCreateDate .= "`tblDocuments`.`date` <= ".$stopdate;
				}
			}
			if ($modificationstartdate) {
				$startdate = SeedDMS_Core_DMS::makeTimeStamp($modificationstartdate['hour'], $modificationstartdate['minute'], $modificationstartdate['second'], $modificationstartdate['year'], $modificationstartdate["month"], $modificationstartdate["day"]);
				if ($startdate) {
					if($searchCreateDate)
						$searchCreateDate .= " AND ";
					$searchCreateDate .= "`tblDocumentContent`.`date` >= ".$startdate;
				}
			}
			if ($modificationenddate) {
				$stopdate = SeedDMS_Core_DMS::makeTimeStamp($modificationenddate['hour'], $modificationenddate['minute'], $modificationenddate['second'], $modificationenddate["year"], $modificationenddate["month"], $modificationenddate["day"]);
				if ($stopdate) {
					if($searchCreateDate)
						$searchCreateDate .= " AND ";
					$searchCreateDate .= "`tblDocumentContent`.`date` <= ".$stopdate;
				}
			}
			$searchExpirationDate = '';
			if ($expirationstartdate) {
				$startdate = SeedDMS_Core_DMS::makeTimeStamp($expirationstartdate['hour'], $expirationstartdate['minute'], $expirationstartdate['second'], $expirationstartdate['year'], $expirationstartdate["month"], $expirationstartdate["day"]);
				if ($startdate) {
					if($searchExpirationDate)
						$searchExpirationDate .= " AND ";
					$searchExpirationDate .= "`tblDocuments`.`expires` >= ".$startdate;
				}
			}
			if ($expirationenddate) {
				$stopdate = SeedDMS_Core_DMS::makeTimeStamp($expirationenddate['hour'], $expirationenddate['minute'], $expirationenddate['second'], $expirationenddate["year"], $expirationenddate["month"], $expirationenddate["day"]);
				if ($stopdate) {
					if($searchExpirationDate)
						$searchExpirationDate .= " AND ";
					$searchExpirationDate .= "`tblDocuments`.`expires` <= ".$stopdate;
				}
			}

			// ---------------------- Suche starten ----------------------------------

			//
			// Construct the SQL query that will be used to search the database.
			//

			if (!$this->db->createTemporaryTable("ttcontentid") || !$this->db->createTemporaryTable("ttstatid")) {
				return false;
			}

			$searchQuery = "FROM `tblDocumentContent` ".
				"LEFT JOIN `tblDocuments` ON `tblDocuments`.`id` = `tblDocumentContent`.`document` ".
				"LEFT JOIN `tblMemoNumbers` ON `tblDocuments`.`id` = `tblMemoNumbers`.`documentID` ".
				"LEFT JOIN `tblSpecNumbers` ON `tblDocuments`.`id` = `tblSpecNumbers`.`documentID` ".
				"LEFT JOIN `tblDocumentAttributes` ON `tblDocuments`.`id` = `tblDocumentAttributes`.`document` ".
				"LEFT JOIN `tblDocumentContentAttributes` ON `tblDocumentContent`.`id` = `tblDocumentContentAttributes`.`content` ".
				"LEFT JOIN `tblDocumentStatus` ON `tblDocumentStatus`.`documentID` = `tblDocumentContent`.`document` ".
				"LEFT JOIN `tblDocumentStatusLog` ON `tblDocumentStatusLog`.`statusID` = `tblDocumentStatus`.`statusID` ".
				"LEFT JOIN `ttstatid` ON `ttstatid`.`maxLogID` = `tblDocumentStatusLog`.`statusLogID` ".
				"LEFT JOIN `ttcontentid` ON `ttcontentid`.`maxVersion` = `tblDocumentStatus`.`version` AND `ttcontentid`.`document` = `tblDocumentStatus`.`documentID` ".
				"LEFT JOIN `tblDocumentLocks` ON `tblDocuments`.`id`=`tblDocumentLocks`.`document` ".
				"LEFT JOIN `tblDocumentCategory` ON `tblDocuments`.`id`=`tblDocumentCategory`.`documentID` ".
				"WHERE `ttstatid`.`maxLogID`=`tblDocumentStatusLog`.`statusLogID` ".
				"AND `ttcontentid`.`maxVersion` = `tblDocumentContent`.`version`";

			if (strlen($searchKey)>0) {
				$searchQuery .= " AND (".$searchKey.")";
			}
			if (strlen($searchFolder)>0) {
				$searchQuery .= " AND ".$searchFolder;
			}
			if (strlen($searchOwner)>0) {
				$searchQuery .= " AND (".$searchOwner.")";
			}
			if (strlen($searchCategories)>0) {
				$searchQuery .= " AND (".$searchCategories.")";
			}
			if (strlen($searchCreateDate)>0) {
				$searchQuery .= " AND (".$searchCreateDate.")";
			}
			if (strlen($searchExpirationDate)>0) {
				$searchQuery .= " AND (".$searchExpirationDate.")";
			}
			if ($searchAttributes) {
				$searchQuery .= " AND (".implode(" AND ", $searchAttributes).")";
			}

			// status
			if ($status) {
				$searchQuery .= " AND `tblDocumentStatusLog`.`status` IN (".implode(',', $status).")";
			}

			if($searchKey || $searchOwner || $searchCategories || $searchCreateDate || $searchExpirationDate || $searchAttributes || $status) {
				// Count the number of rows that the search will produce.
				$resArr = $this->db->getResultArray("SELECT COUNT(*) AS num FROM (SELECT DISTINCT `tblDocuments`.id ".$searchQuery.") a");
				$totalDocs = 0;
				if (is_numeric($resArr[0]["num"]) && $resArr[0]["num"]>0) {
					$totalDocs = (integer)$resArr[0]["num"];
				}

				// If there are no results from the count query, then there is no real need
				// to run the full query. TODO: re-structure code to by-pass additional
				// queries when no initial results are found.

				// Prepare the complete search query, including the LIMIT clause.
				$searchQuery = "SELECT DISTINCT `tblDocuments`.*, ".
					"`tblMemoNumbers`.`number`, `tblSpecNumbers`.`number`,".
					"`tblDocumentContent`.`version`, ".
					"`tblDocumentStatusLog`.`status`, `tblDocumentLocks`.`userID` as `lockUser` ".$searchQuery;

				// calculate the remaining entrїes of the current page
				// If page is not full yet, get remaining entries
				if($limit) {
					$remain = $limit - count($folderresult['folders']);
					if($remain) {
						if($remain == $limit)
							$offset -= $totalFolders;
						else
							$offset = 0;
						if($limit)
							$searchQuery .= " LIMIT ".$offset.",".$remain;

						// Send the complete search query to the database.
						$resArr = $this->db->getResultArray($searchQuery);
					} else {
						$resArr = array();
					}
				} else {
					// Send the complete search query to the database.
					$resArr = $this->db->getResultArray($searchQuery);
				}

				// ------------------- Ausgabe der Ergebnisse ----------------------------
				$numResults = count($resArr);
				if ($numResults == 0) {
					$docresult = array('totalDocs'=>$totalDocs, 'docs'=>array());
				} else {
					foreach ($resArr as $docArr) {
						$docs[] = $this->getDocument($docArr['id']);
					}
					$docresult = array('totalDocs'=>$totalDocs, 'docs'=>$docs);
				}
			} else {
				$docresult = array('totalDocs'=>0, 'docs'=>array());
			}
		} else {
			$docresult = array('totalDocs'=>0, 'docs'=>array());
		}

		if($limit) {
			$totalPages = (integer)(($totalDocs+$totalFolders)/$limit);
			if ((($totalDocs+$totalFolders)%$limit) > 0) {
				$totalPages++;
			}
		} else {
			$totalPages = 1;
		}

		return array_merge($docresult, $folderresult, array('totalPages'=>$totalPages));
	} /* }}} */

	/**
	 * Return a folder by its id
	 *
	 * This function retrieves a folder from the database by its id.
	 *
	 * @param integer $id internal id of folder
	 * @return object instance of SeedDMS_Core_Folder or false
	 */
	function getFolder($id) { /* {{{ */
		$classname = $this->classnames['folder'];
		return $classname::getInstance($id, $this);
	} /* }}} */

	/**
	 * Return a folder by its name
	 *
	 * This function retrieves a folder from the database by its name. The
	 * search covers the whole database. If
	 * the parameter $folder is not null, it will search for the name
	 * only within this parent folder. It will not be done recursively.
	 *
	 * @param string $name name of the folder
	 * @param object $folder parent folder
	 * @return object/boolean found folder or false
	 */
	function getFolderByName($name, $folder=null) { /* {{{ */
		if (!$name) return false;

		$queryStr = "SELECT * FROM tblFolders WHERE name = " . $this->db->qstr($name);
		if($folder)
			$queryStr .= " AND `parent` = ". $folder->getID();
		$queryStr .= " LIMIT 1";
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false)
			return false;

		if(!$resArr)
			return false;

		$resArr = $resArr[0];
		$folder = new $this->classnames['folder']($resArr["id"], $resArr["name"], $resArr["parent"], $resArr["comment"], $resArr["date"], $resArr["owner"], $resArr["inheritAccess"], $resArr["defaultAccess"], $resArr["sequence"]);
		$folder->setDMS($this);
		return $folder;
	} /* }}} */

	/**
	 * Returns a list of folders and error message not linked in the tree
	 *
	 * This function checks all folders in the database.
	 *
	 * @return array list of errors
	 */
	function checkFolders() { /* {{{ */
		$queryStr = "SELECT * FROM tblFolders";
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr === false)
			return false;

		$cache = array();
		foreach($resArr as $rec) {
			$cache[$rec['id']] = array('name'=>$rec['name'], 'parent'=>$rec['parent'], 'folderList'=>$rec['folderList']);
		}
		$errors = array();
		foreach($cache as $id=>$rec) {
			if(!array_key_exists($rec['parent'], $cache) && $rec['parent'] != 0) {
				$errors[$id] = array('id'=>$id, 'name'=>$rec['name'], 'parent'=>$rec['parent'], 'msg'=>'Missing parent');
			} else {
				$tmparr = explode(':', $rec['folderList']);
				array_shift($tmparr);
				if(count($tmparr) != count(array_unique($tmparr))) {
					$errors[$id] = array('id'=>$id, 'name'=>$rec['name'], 'parent'=>$rec['parent'], 'msg'=>'Duplicate entry in folder list ('.$rec['folderList'].')');
				}
			}
		}

		return $errors;
	} /* }}} */

	/**
	 * Returns a list of documents and error message not linked in the tree
	 *
	 * This function checks all documents in the database.
	 *
	 * @return array list of errors
	 */
	function checkDocuments() { /* {{{ */
		$queryStr = "SELECT * FROM tblFolders";
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr === false)
			return false;

		$fcache = array();
		foreach($resArr as $rec) {
			$fcache[$rec['id']] = array('name'=>$rec['name'], 'parent'=>$rec['parent'], 'folderList'=>$rec['folderList']);
		}

		$queryStr = "SELECT * FROM tblDocuments";
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr === false)
			return false;

		$dcache = array();
		foreach($resArr as $rec) {
			$dcache[$rec['id']] = array('name'=>$rec['name'], 'parent'=>$rec['folder'], 'folderList'=>$rec['folderList']);
		}
		$errors = array();
		foreach($dcache as $id=>$rec) {
			if(!array_key_exists($rec['parent'], $fcache) && $rec['parent'] != 0) {
				$errors[$id] = array('id'=>$id, 'name'=>$rec['name'], 'parent'=>$rec['parent'], 'msg'=>'Missing parent');
			} else {
				$tmparr = explode(':', $rec['folderList']);
				array_shift($tmparr);
				if(count($tmparr) != count(array_unique($tmparr))) {
					$errors[$id] = array('id'=>$id, 'name'=>$rec['name'], 'parent'=>$rec['parent'], 'msg'=>'Duplicate entry in folder list ('.$rec['folderList'].'');
				}
			}
		}

		return $errors;
	} /* }}} */

	/**
	 * Return a user by its id
	 *
	 * This function retrieves a user from the database by its id.
	 *
	 * @param integer $id internal id of user
	 * @return object instance of {@link SeedDMS_Core_User} or false
	 */
	function getUser($id) { /* {{{ */
		$classname = $this->classnames['user'];
		return $classname::getInstance($id, $this);
	} /* }}} */

	/**
	 * Return a user by its login
	 *
	 * This function retrieves a user from the database by its login.
	 * If the second optional parameter $email is not empty, the user must
	 * also have the given email.
	 *
	 * @param string $login internal login of user
	 * @param string $email email of user
	 * @return object instance of {@link SeedDMS_Core_User} or false
	 */
	function getUserByLogin($login, $email='') { /* {{{ */
		$classname = $this->classnames['user'];
		return $classname::getInstance($login, $this, 'name', $email);
	} /* }}} */

	/**
	 * Return a user by its email
	 *
	 * This function retrieves a user from the database by its email.
	 * It is needed when the user requests a new password.
	 *
	 * @param integer $email email address of user
	 * @return object instance of {@link SeedDMS_Core_User} or false
	 */
	function getUserByEmail($email) { /* {{{ */
		$classname = $this->classnames['user'];
		return $classname::getInstance($email, $this, 'email');
	} /* }}} */

	/**
	 * Return list of all users
	 *
	 * @return array of instances of {@link SeedDMS_Core_User} or false
	 */
	function getAllUsers($orderby = '') { /* {{{ */
		$classname = $this->classnames['user'];
		return $classname::getAllInstances($orderby, $this);
	} /* }}} */

	/**
	 * Add a new user
	 *
	 * @param string $login login name
	 * @param string $pwd password of new user
	 * @param string $email Email of new user
	 * @param string $language language of new user
	 * @param string $comment comment of new user
	 * @param integer $role role of new user (can be 0=normal, 1=admin, 2=guest)
	 * @param integer $isHidden hide user in all lists, if this is set login
	 *        is still allowed
	 * @param integer $isDisabled disable user and prevent login
	 * @return object of {@link SeedDMS_Core_User}
	 */
	function addUser($login, $pwd, $fullName, $email, $language, $theme, $comment, $role='0', $isHidden=0, $isDisabled=0, $pwdexpiration='', $quota=0, $homefolder=null) { /* {{{ */
		$db = $this->db;
		if (is_object($this->getUserByLogin($login))) {
			return false;
		}
		if($role == '')
			$role = '0';
		if(trim($pwdexpiration) == '')
			$pwdexpiration = '0000-00-00 00:00:00';
		$queryStr = "INSERT INTO tblUsers (login, pwd, fullName, email, language, theme, comment, role, hidden, disabled, pwdExpiration, quota, homefolder) VALUES (".$db->qstr($login).", ".$db->qstr($pwd).", ".$db->qstr($fullName).", ".$db->qstr($email).", '".$language."', '".$theme."', ".$db->qstr($comment).", '".intval($role)."', '".intval($isHidden)."', '".intval($isDisabled)."', ".$db->qstr($pwdexpiration).", '".intval($quota)."', ".($homefolder ? intval($homefolder) : "NULL").")";
		$res = $this->db->getResult($queryStr);
		if (!$res)
			return false;

		return $this->getUser($this->db->getInsertID());
	} /* }}} */

	/**
	 * Get a group by its id
	 *
	 * @param integer $id id of group
	 * @return object/boolean group or false if no group was found
	 */
	function getGroup($id) { /* {{{ */
		$classname = $this->classnames['group'];
		return $classname::getInstance($id, $this, '');
	} /* }}} */

	/**
	 * Get a group by its name
	 *
	 * @param string $name name of group
	 * @return object/boolean group or false if no group was found
	 */
	function getGroupByName($name) { /* {{{ */
		$classname = $this->classnames['group'];
		return $classname::getInstance($name, $this, 'name');
	} /* }}} */

	/**
	 * Get a list of all groups
	 *
	 * @return array array of instances of {@link SeedDMS_Core_Group}
	 */
	function getAllGroups() { /* {{{ */
		$classname = $this->classnames['group'];
		return $classname::getAllInstances('name', $this);
	} /* }}} */

	/**
	 * Create a new user group
	 *
	 * @param string $name name of group
	 * @param string $comment comment of group
	 * @return object/boolean instance of {@link SeedDMS_Core_Group} or false in
	 *         case of an error.
	 */
	function addGroup($name, $comment) { /* {{{ */
		if (is_object($this->getGroupByName($name))) {
			return false;
		}

		$queryStr = "INSERT INTO tblGroups (name, comment) VALUES (".$this->db->qstr($name).", ".$this->db->qstr($comment).")";
		if (!$this->db->getResult($queryStr))
			return false;

		return $this->getGroup($this->db->getInsertID());
	} /* }}} */

	function getKeywordCategory($id) { /* {{{ */
		if (!is_numeric($id))
			return false;

		$queryStr = "SELECT * FROM tblKeywordCategories WHERE id = " . (int) $id;
		$resArr = $this->db->getResultArray($queryStr);
		if ((is_bool($resArr) && !$resArr) || (count($resArr) != 1))
			return false;

		$resArr = $resArr[0];
		$cat = new SeedDMS_Core_Keywordcategory($resArr["id"], $resArr["owner"], $resArr["name"]);
		$cat->setDMS($this);
		return $cat;
	} /* }}} */

	function getKeywordCategoryByName($name, $userID) { /* {{{ */
		$queryStr = "SELECT * FROM tblKeywordCategories WHERE name = " . $this->db->qstr($name) . " AND owner = " . (int) $userID;
		$resArr = $this->db->getResultArray($queryStr);
		if ((is_bool($resArr) && !$resArr) || (count($resArr) != 1))
			return false;

		$resArr = $resArr[0];
		$cat = new SeedDMS_Core_Keywordcategory($resArr["id"], $resArr["owner"], $resArr["name"]);
		$cat->setDMS($this);
		return $cat;
	} /* }}} */

	function getAllKeywordCategories($userIDs = array()) { /* {{{ */
		$queryStr = "SELECT * FROM tblKeywordCategories";
		if ($userIDs)
			$queryStr .= " WHERE owner in (".implode(',', $userIDs).")";

		$resArr = $this->db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		$categories = array();
		foreach ($resArr as $row) {
			$cat = new SeedDMS_Core_KeywordCategory($row["id"], $row["owner"], $row["name"]);
			$cat->setDMS($this);
			array_push($categories, $cat);
		}

		return $categories;
	} /* }}} */

	/**
	 * This function should be replaced by getAllKeywordCategories()
	 */
	function getAllUserKeywordCategories($userID) { /* {{{ */
		$queryStr = "SELECT * FROM tblKeywordCategories";
		if ($userID != -1)
			$queryStr .= " WHERE owner = " . (int) $userID;

		$resArr = $this->db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		$categories = array();
		foreach ($resArr as $row) {
			$cat = new SeedDMS_Core_KeywordCategory($row["id"], $row["owner"], $row["name"]);
			$cat->setDMS($this);
			array_push($categories, $cat);
		}

		return $categories;
	} /* }}} */

	function addKeywordCategory($userID, $name) { /* {{{ */
		if (is_object($this->getKeywordCategoryByName($name, $userID))) {
			return false;
		}
		$queryStr = "INSERT INTO tblKeywordCategories (owner, name) VALUES (".(int) $userID.", ".$this->db->qstr($name).")";
		if (!$this->db->getResult($queryStr))
			return false;

		return $this->getKeywordCategory($this->db->getInsertID());
	} /* }}} */

	function getDocumentCategory($id) { /* {{{ */
		if (!is_numeric($id))
			return false;

		$queryStr = "SELECT * FROM tblCategory WHERE id = " . (int) $id;
		$resArr = $this->db->getResultArray($queryStr);
		if ((is_bool($resArr) && !$resArr) || (count($resArr) != 1))
			return false;

		$resArr = $resArr[0];
		$cat = new SeedDMS_Core_DocumentCategory($resArr["id"], $resArr["name"]);
		$cat->setDMS($this);
		return $cat;
	} /* }}} */

	function getDocumentCategories() { /* {{{ */
		$queryStr = "SELECT * FROM tblCategory order by name";

		$resArr = $this->db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		$categories = array();
		foreach ($resArr as $row) {
			$cat = new SeedDMS_Core_DocumentCategory($row["id"], $row["name"]);
			$cat->setDMS($this);
			array_push($categories, $cat);
		}

		return $categories;
	} /* }}} */

	/**
	 * Get a category by its name
	 *
	 * The name of a category is by default unique.
	 *
	 * @param string $name human readable name of category
	 * @return object instance of {@link SeedDMS_Core_DocumentCategory}
	 */
	function getDocumentCategoryByName($name) { /* {{{ */
		if (!$name) return false;

		$queryStr = "SELECT * FROM tblCategory where name=".$this->db->qstr($name);
		$resArr = $this->db->getResultArray($queryStr);
		if (!$resArr)
			return false;

		$row = $resArr[0];
		$cat = new SeedDMS_Core_DocumentCategory($row["id"], $row["name"]);
		$cat->setDMS($this);

		return $cat;
	} /* }}} */

	function addDocumentCategory($name) { /* {{{ */
		if (is_object($this->getDocumentCategoryByName($name))) {
			return false;
		}
		$queryStr = "INSERT INTO tblCategory (name) VALUES (".$this->db->qstr($name).")";
		if (!$this->db->getResult($queryStr))
			return false;

		return $this->getDocumentCategory($this->db->getInsertID());
	} /* }}} */

	/**
	 * Get all notifications for a group
	 *
	 * deprecated: User {@link SeedDMS_Core_Group::getNotifications()}
	 *
	 * @param object $group group for which notifications are to be retrieved
	 * @param integer $type type of item (T_DOCUMENT or T_FOLDER)
	 * @return array array of notifications
	 */
	function getNotificationsByGroup($group, $type=0) { /* {{{ */
		return $group->getNotifications($type);
	} /* }}} */

	/**
	 * Get all notifications for a user
	 *
	 * deprecated: User {@link SeedDMS_Core_User::getNotifications()}
	 *
	 * @param object $user user for which notifications are to be retrieved
	 * @param integer $type type of item (T_DOCUMENT or T_FOLDER)
	 * @return array array of notifications
	 */
	function getNotificationsByUser($user, $type=0) { /* {{{ */
		return $user->getNotifications($type);
	} /* }}} */

	/**
	 * Create a token to request a new password.
	 * This function will not delete the password but just creates an entry
	 * in tblUserRequestPassword indicating a password request.
	 *
	 * @return string hash value of false in case of an error
	 */
	function createPasswordRequest($user) { /* {{{ */
		$hash = md5(uniqid(time()));
		$queryStr = "INSERT INTO tblUserPasswordRequest (userID, hash, `date`) VALUES (" . $user->getId() . ", " . $this->db->qstr($hash) .", ".$this->db->getCurrentDatetime().")";
		$resArr = $this->db->getResult($queryStr);
		if (is_bool($resArr) && !$resArr) return false;
		return $hash;

	} /* }}} */

	/**
	 * Check if hash for a password request is valid.
	 * This function searches a previously create password request and
	 * returns the user.
	 *
	 * @param string $hash
	 */
	function checkPasswordRequest($hash) { /* {{{ */
		/* Get the password request from the database */
		$queryStr = "SELECT * FROM tblUserPasswordRequest where hash=".$this->db->qstr($hash);
		$resArr = $this->db->getResultArray($queryStr);
		if (is_bool($resArr) && !$resArr)
			return false;

		if (count($resArr) != 1)
			return false;
		$resArr = $resArr[0];

		return $this->getUser($resArr['userID']);

	} /* }}} */

	/**
	 * Delete a password request
	 *
	 * @param string $hash
	 */
	function deletePasswordRequest($hash) { /* {{{ */
		/* Delete the request, so nobody can use it a second time */
		$queryStr = "DELETE FROM tblUserPasswordRequest WHERE hash=".$this->db->qstr($hash);
		if (!$this->db->getResult($queryStr))
			return false;
		return true;
	} /* }}} */

	/**
	 * Return a attribute definition by its id
	 *
	 * This function retrieves a attribute definitionr from the database by
	 * its id.
	 *
	 * @param integer $id internal id of attribute defintion
	 * @return object instance of {@link SeedDMS_Core_AttributeDefinition} or false
	 */
	function getAttributeDefinition($id) { /* {{{ */
		if (!is_numeric($id))
			return false;

		$queryStr = "SELECT * FROM tblAttributeDefinitions WHERE id = " . (int) $id;
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false) return false;
		if (count($resArr) != 1) return false;

		$resArr = $resArr[0];

		$attrdef = new SeedDMS_Core_AttributeDefinition($resArr["id"], $resArr["name"], $resArr["objtype"], $resArr["type"], $resArr["multiple"], $resArr["minvalues"], $resArr["maxvalues"], $resArr["valueset"], $resArr["regex"]);
		$attrdef->setDMS($this);
		return $attrdef;
	} /* }}} */

	/**
	 * Return a attribute definition by its name
	 *
	 * This function retrieves an attribute def. from the database by its name.
	 *
	 * @param string $name internal name of attribute def.
	 * @return object instance of {@link SeedDMS_Core_AttributeDefinition} or false
	 */
	function getAttributeDefinitionByName($name) { /* {{{ */
		if (!$name) return false;

		$queryStr = "SELECT * FROM tblAttributeDefinitions WHERE name = " . $this->db->qstr($name);
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false) return false;
		if (count($resArr) != 1) return false;

		$resArr = $resArr[0];

		$attrdef = new SeedDMS_Core_AttributeDefinition($resArr["id"], $resArr["name"], $resArr["objtype"], $resArr["type"], $resArr["multiple"], $resArr["minvalues"], $resArr["maxvalues"], $resArr["valueset"], $resArr["regex"]);
		$attrdef->setDMS($this);
		return $attrdef;
	} /* }}} */

	/**
	 * Return list of all attributes definitions
	 *
	 * @param integer $objtype select those attributes defined for an object type
	 * @return array of instances of {@link SeedDMS_Core_AttributeDefinition} or false
	 */
	function getAllAttributeDefinitions($objtype=0) { /* {{{ */
		$queryStr = "SELECT * FROM tblAttributeDefinitions";
		if($objtype) {
			if(is_array($objtype))
				$queryStr .= ' WHERE objtype in (\''.implode("','", $objtype).'\')';
			else
				$queryStr .= ' WHERE objtype='.intval($objtype);
		}
		$queryStr .= ' ORDER BY name';
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false)
			return false;

		$attrdefs = array();

		for ($i = 0; $i < count($resArr); $i++) {
			$attrdef = new SeedDMS_Core_AttributeDefinition($resArr[$i]["id"], $resArr[$i]["name"], $resArr[$i]["objtype"], $resArr[$i]["type"], $resArr[$i]["multiple"], $resArr[$i]["minvalues"], $resArr[$i]["maxvalues"], $resArr[$i]["valueset"], $resArr[$i]["regex"]);
			$attrdef->setDMS($this);
			$attrdefs[$i] = $attrdef;
		}

		return $attrdefs;
	} /* }}} */

	/**
	 * Add a new attribute definition
	 *
	 * @param string $name name of attribute
	 * @param string $type type of attribute
	 * @param boolean $multiple set to 1 if attribute has multiple attributes
	 * @param integer $minvalues minimum number of values
	 * @param integer $maxvalues maximum number of values if multiple is set
	 * @param string $valueset list of allowed values (csv format)
	 * @return object of {@link SeedDMS_Core_User}
	 */
	function addAttributeDefinition($name, $objtype, $type, $multiple=0, $minvalues=0, $maxvalues=1, $valueset='', $regex='') { /* {{{ */
		if (is_object($this->getAttributeDefinitionByName($name))) {
			return false;
		}
		if(!$type)
			return false;
		$queryStr = "INSERT INTO tblAttributeDefinitions (name, objtype, type, multiple, minvalues, maxvalues, valueset, regex) VALUES (".$this->db->qstr($name).", ".intval($objtype).", ".intval($type).", ".intval($multiple).", ".intval($minvalues).", ".intval($maxvalues).", ".$this->db->qstr($valueset).", ".$this->db->qstr($regex).")";
		$res = $this->db->getResult($queryStr);
		if (!$res)
			return false;

		return $this->getAttributeDefinition($this->db->getInsertID());
	} /* }}} */

	/**
	 * Return list of all workflows
	 *
	 * @return array of instances of {@link SeedDMS_Core_Workflow} or false
	 */
	function getAllWorkflows() { /* {{{ */
		$queryStr = "SELECT * FROM tblWorkflows ORDER BY name";
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false)
			return false;

		$queryStr = "SELECT * FROM tblWorkflowStates ORDER BY name";
		$ressArr = $this->db->getResultArray($queryStr);

		if (is_bool($ressArr) && $ressArr == false)
			return false;

		for ($i = 0; $i < count($ressArr); $i++) {
			$wkfstates[$ressArr[$i]["id"]] = new SeedDMS_Core_Workflow_State($ressArr[$i]["id"], $ressArr[$i]["name"], $ressArr[$i]["maxtime"], $ressArr[$i]["precondfunc"], $ressArr[$i]["documentstatus"]);
		}

		$workflows = array();
		for ($i = 0; $i < count($resArr); $i++) {
			$workflow = new SeedDMS_Core_Workflow($resArr[$i]["id"], $resArr[$i]["name"], $wkfstates[$resArr[$i]["initstate"]]);
			$workflow->setDMS($this);
			$workflows[$i] = $workflow;
		}

		return $workflows;
	} /* }}} */

	/**
	 * Return workflow by its Id
	 *
	 * @param integer $id internal id of workflow
	 * @return object of instances of {@link SeedDMS_Core_Workflow} or false
	 */
	function getWorkflow($id) { /* {{{ */
		$queryStr = "SELECT * FROM tblWorkflows WHERE id=".intval($id);
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false)
			return false;

		if(!$resArr)
			return false;

		$initstate = $this->getWorkflowState($resArr[0]['initstate']);

		$workflow = new SeedDMS_Core_Workflow($resArr[0]["id"], $resArr[0]["name"], $initstate);
		$workflow->setDMS($this);

		return $workflow;
	} /* }}} */

	/**
	 * Return workflow by its name
	 *
	 * @param string $name name of workflow
	 * @return object of instances of {@link SeedDMS_Core_Workflow} or false
	 */
	function getWorkflowByName($name) { /* {{{ */
		if (!$name) return false;

		$queryStr = "SELECT * FROM tblWorkflows WHERE name=".$this->db->qstr($name);
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false)
			return false;

		if(!$resArr)
			return false;

		$initstate = $this->getWorkflowState($resArr[0]['initstate']);

		$workflow = new SeedDMS_Core_Workflow($resArr[0]["id"], $resArr[0]["name"], $initstate);
		$workflow->setDMS($this);

		return $workflow;
	} /* }}} */

	/**
	 * Add a new workflow
	 *
	 * @param string $name name of workflow
	 * @param string $initstate initial state of workflow
	 */
	function addWorkflow($name, $initstate) { /* {{{ */
		$db = $this->db;
		if (is_object($this->getWorkflowByName($name))) {
			return false;
		}
		$queryStr = "INSERT INTO tblWorkflows (name, initstate) VALUES (".$db->qstr($name).", ".$initstate->getID().")";
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		return $this->getWorkflow($db->getInsertID());
	} /* }}} */

	/**
	 * Return a workflow state by its id
	 *
	 * This function retrieves a workflow state from the database by its id.
	 *
	 * @param integer $id internal id of workflow state
	 * @return object instance of {@link SeedDMS_Core_Workflow_State} or false
	 */
	function getWorkflowState($id) { /* {{{ */
		if (!is_numeric($id))
			return false;

		$queryStr = "SELECT * FROM tblWorkflowStates WHERE id = " . (int) $id;
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false) return false;
		if (count($resArr) != 1) return false;

		$resArr = $resArr[0];

		$state = new SeedDMS_Core_Workflow_State($resArr["id"], $resArr["name"], $resArr["maxtime"], $resArr["precondfunc"], $resArr["documentstatus"]);
		$state->setDMS($this);
		return $state;
	} /* }}} */

	/**
	 * Return workflow state by its name
	 *
	 * @param string $name name of workflow state
	 * @return object of instances of {@link SeedDMS_Core_Workflow_State} or false
	 */
	function getWorkflowStateByName($name) { /* {{{ */
		if (!$name) return false;

		$queryStr = "SELECT * FROM tblWorkflowStates WHERE name=".$this->db->qstr($name);
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false)
			return false;

		if(!$resArr)
			return false;

		$resArr = $resArr[0];

		$state = new SeedDMS_Core_Workflow_State($resArr["id"], $resArr["name"], $resArr["maxtime"], $resArr["precondfunc"], $resArr["documentstatus"]);
		$state->setDMS($this);

		return $state;
	} /* }}} */

	/**
	 * Return list of all workflow states
	 *
	 * @return array of instances of {@link SeedDMS_Core_Workflow_State} or false
	 */
	function getAllWorkflowStates() { /* {{{ */
		$queryStr = "SELECT * FROM tblWorkflowStates ORDER BY name";
		$ressArr = $this->db->getResultArray($queryStr);

		if (is_bool($ressArr) && $ressArr == false)
			return false;

		$wkfstates = array();
		for ($i = 0; $i < count($ressArr); $i++) {
			$wkfstate = new SeedDMS_Core_Workflow_State($ressArr[$i]["id"], $ressArr[$i]["name"], $ressArr[$i]["maxtime"], $ressArr[$i]["precondfunc"], $ressArr[$i]["documentstatus"]);
			$wkfstate->setDMS($this);
			$wkfstates[$i] = $wkfstate;
		}

		return $wkfstates;
	} /* }}} */

	/**
	 * Add new workflow state
	 *
	 * @param string $name name of workflow state
	 * @param integer $docstatus document status when this state is reached
	 * @return object instance of new workflow state
	 */
	function addWorkflowState($name, $docstatus) { /* {{{ */
		$db = $this->db;
		if (is_object($this->getWorkflowStateByName($name))) {
			return false;
		}
		$queryStr = "INSERT INTO tblWorkflowStates (name, documentstatus) VALUES (".$db->qstr($name).", ".(int) $docstatus.")";
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		return $this->getWorkflowState($db->getInsertID());
	} /* }}} */

	/**
	 * Return a workflow action by its id
	 *
	 * This function retrieves a workflow action from the database by its id.
	 *
	 * @param integer $id internal id of workflow action
	 * @return object instance of {@link SeedDMS_Core_Workflow_Action} or false
	 */
	function getWorkflowAction($id) { /* {{{ */
		if (!is_numeric($id))
			return false;

		$queryStr = "SELECT * FROM tblWorkflowActions WHERE id = " . (int) $id;
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false) return false;
		if (count($resArr) != 1) return false;

		$resArr = $resArr[0];

		$action = new SeedDMS_Core_Workflow_Action($resArr["id"], $resArr["name"]);
		$action->setDMS($this);
		return $action;
	} /* }}} */

	/**
	 * Return a workflow action by its name
	 *
	 * This function retrieves a workflow action from the database by its name.
	 *
	 * @param string $name name of workflow action
	 * @return object instance of {@link SeedDMS_Core_Workflow_Action} or false
	 */
	function getWorkflowActionByName($name) { /* {{{ */
		if (!$name) return false;

		$queryStr = "SELECT * FROM tblWorkflowActions WHERE name = " . $this->db->qstr($name);
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false) return false;
		if (count($resArr) != 1) return false;

		$resArr = $resArr[0];

		$action = new SeedDMS_Core_Workflow_Action($resArr["id"], $resArr["name"]);
		$action->setDMS($this);
		return $action;
	} /* }}} */

	/**
	 * Return list of workflow action
	 *
	 * @return array list of instances of {@link SeedDMS_Core_Workflow_Action} or false
	 */
	function getAllWorkflowActions() { /* {{{ */
		$queryStr = "SELECT * FROM tblWorkflowActions";
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false)
			return false;

		$wkfactions = array();
		for ($i = 0; $i < count($resArr); $i++) {
			$action = new SeedDMS_Core_Workflow_Action($resArr[$i]["id"], $resArr[$i]["name"]);
			$action->setDMS($this);
			$wkfactions[$i] = $action;
		}

		return $wkfactions;
	} /* }}} */

	/**
	 * Add new workflow action
	 *
	 * @param string $name name of workflow action
	 * @return object instance new workflow action
	 */
	function addWorkflowAction($name) { /* {{{ */
		$db = $this->db;
		if (is_object($this->getWorkflowActionByName($name))) {
			return false;
		}
		$queryStr = "INSERT INTO tblWorkflowActions (name) VALUES (".$db->qstr($name).")";
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		return $this->getWorkflowAction($db->getInsertID());
	} /* }}} */

	/**
	 * Return a workflow transition by its id
	 *
	 * This function retrieves a workflow transition from the database by its id.
	 *
	 * @param integer $id internal id of workflow transition
	 * @return object instance of {@link SeedDMS_Core_Workflow_Transition} or false
	 */
	function getWorkflowTransition($id) { /* {{{ */
		if (!is_numeric($id))
			return false;

		$queryStr = "SELECT * FROM tblWorkflowTransitions WHERE id = " . (int) $id;
		$resArr = $this->db->getResultArray($queryStr);

		if (is_bool($resArr) && $resArr == false) return false;
		if (count($resArr) != 1) return false;

		$resArr = $resArr[0];

		$transition = new SeedDMS_Core_Workflow_Transition($resArr["id"], $this->getWorkflow($resArr["workflow"]), $this->getWorkflowState($resArr["state"]), $this->getWorkflowAction($resArr["action"]), $this->getWorkflowState($resArr["nextstate"]), $resArr["maxtime"]);
		$transition->setDMS($this);
		return $transition;
	} /* }}} */

	/**
	 * Returns document content which is not linked to a document
	 *
	 * This method is for finding straying document content without
	 * a parent document. In normal operation this should not happen
	 * but little checks for database consistency and possible errors
	 * in the application may have left over document content though
	 * the document is gone already.
	 */
	function getUnlinkedDocumentContent() { /* {{{ */
		$queryStr = "SELECT * FROM tblDocumentContent WHERE document NOT IN (SELECT id FROM tblDocuments)";
		$resArr = $this->db->getResultArray($queryStr);
		if (!$resArr)
			return false;

		$versions = array();
		foreach($resArr as $row) {
			$document = new $this->classnames['document']($row['document'], '', '', '', '', '', '', '', '', '', '', '');
			$document->setDMS($this);
			$version = new $this->classnames['documentcontent']($row['id'], $document, $row['version'], $row['comment'], $row['date'], $row['createdBy'], $row['dir'], $row['orgFileName'], $row['fileType'], $row['mimeType'], $row['fileSize'], $row['checksum']);
			$versions[] = $version;
		}
		return $versions;
		
	} /* }}} */

	/**
	 * Returns document content which has no file size set
	 *
	 * This method is for finding document content without a file size
	 * set in the database. The file size of a document content was introduced
	 * in version 4.0.0 of SeedDMS for implementation of user quotas.
	 */
	function getNoFileSizeDocumentContent() { /* {{{ */
		$queryStr = "SELECT * FROM tblDocumentContent WHERE fileSize = 0 OR fileSize is null";
		$resArr = $this->db->getResultArray($queryStr);
		if (!$resArr)
			return false;

		$versions = array();
		foreach($resArr as $row) {
			$document = new $this->classnames['document']($row['document'], '', '', '', '', '', '', '', '', '', '', '');
			$document->setDMS($this);
			$version = new $this->classnames['documentcontent']($row['id'], $document, $row['version'], $row['comment'], $row['date'], $row['createdBy'], $row['dir'], $row['orgFileName'], $row['fileType'], $row['mimeType'], $row['fileSize'], $row['checksum'], $row['fileSize'], $row['checksum']);
			$versions[] = $version;
		}
		return $versions;
		
	} /* }}} */

	/**
	 * Returns document content which has no checksum set
	 *
	 * This method is for finding document content without a checksum
	 * set in the database. The checksum of a document content was introduced
	 * in version 4.0.0 of SeedDMS for finding duplicates.
	 */
	function getNoChecksumDocumentContent() { /* {{{ */
		$queryStr = "SELECT * FROM tblDocumentContent WHERE checksum = '' OR checksum is null";
		$resArr = $this->db->getResultArray($queryStr);
		if (!$resArr)
			return false;

		$versions = array();
		foreach($resArr as $row) {
			$document = new $this->classnames['document']($row['document'], '', '', '', '', '', '', '', '', '', '', '');
			$document->setDMS($this);
			$version = new $this->classnames['documentcontent']($row['id'], $document, $row['version'], $row['comment'], $row['date'], $row['createdBy'], $row['dir'], $row['orgFileName'], $row['fileType'], $row['mimeType'], $row['fileSize'], $row['checksum']);
			$versions[] = $version;
		}
		return $versions;
		
	} /* }}} */

	/**
	 * Returns document content which is duplicated
	 *
	 * This method is for finding document content which is available twice
	 * in the database. The checksum of a document content was introduced
	 * in version 4.0.0 of SeedDMS for finding duplicates.
	 */
	function getDuplicateDocumentContent() { /* {{{ */
		$queryStr = "SELECT a.*, b.id as dupid FROM tblDocumentContent a LEFT JOIN tblDocumentContent b ON a.checksum=b.checksum where a.id!=b.id ORDER by a.id";
		$resArr = $this->db->getResultArray($queryStr);
		if (!$resArr)
			return false;

		$versions = array();
		foreach($resArr as $row) {
			$document = new $this->classnames['document']($row['document'], '', '', '', '', '', '', '', '', '', '', '');
			$document->setDMS($this);
			$version = new $this->classnames['documentcontent']($row['id'], $document, $row['version'], $row['comment'], $row['date'], $row['createdBy'], $row['dir'], $row['orgFileName'], $row['fileType'], $row['mimeType'], $row['fileSize'], $row['checksum']);
			if(!isset($versions[$row['dupid']])) {
				$versions[$row['id']]['content'] = $version;
				$versions[$row['id']]['duplicates'] = array();
			} else
				$versions[$row['dupid']]['duplicates'][] = $version;
		}
		return $versions;
		
	} /* }}} */

	/**
	 * Returns statitical information
	 *
	 * This method returns all kind of statistical information like
	 * documents or used space per user, recent activity, etc.
	 *
	 * @param string $type type of statistic
	 * @return array statistical data
	 */
	function getStatisticalData($type='') { /* {{{ */
		switch($type) {
			case 'docsperuser':
				$queryStr = "select b.fullname as `key`, count(owner) as total from tblDocuments a left join tblUsers b on a.owner=b.id group by owner";
				$resArr = $this->db->getResultArray($queryStr);
				if (!$resArr)
					return false;

				return $resArr;
			case 'docspermimetype':
				$queryStr = "select b.mimeType as `key`, count(mimeType) as total from tblDocuments a left join tblDocumentContent b on a.id=b.document group by b.mimeType";
				$resArr = $this->db->getResultArray($queryStr);
				if (!$resArr)
					return false;

				return $resArr;
			case 'docspercategory':
				$queryStr = "select b.name as `key`, count(a.categoryID) as total from tblDocumentCategory a left join tblCategory b on a.categoryID=b.id group by a.categoryID";
				$resArr = $this->db->getResultArray($queryStr);
				if (!$resArr)
					return false;

				return $resArr;
			case 'docsperstatus':
				$queryStr = "select b.status as `key`, count(b.status) as total from (select a.id, max(b.version), max(c.statusLogId) as maxlog from tblDocuments a left join tblDocumentStatus b on a.id=b.documentid left join tblDocumentStatusLog c on b.statusid=c.statusid group by a.id, b.version order by a.id, b.statusid) a left join tblDocumentStatusLog b on a.maxlog=b.statusLogId group by b.status";
				$queryStr = "select b.status as `key`, count(b.status) as total from (select a.id, max(c.statusLogId) as maxlog from tblDocuments a left join tblDocumentStatus b on a.id=b.documentid left join tblDocumentStatusLog c on b.statusid=c.statusid group by a.id  order by a.id, b.statusid) a left join tblDocumentStatusLog b on a.maxlog=b.statusLogId group by b.status";
				$resArr = $this->db->getResultArray($queryStr);
				if (!$resArr)
					return false;

				return $resArr;
			case 'docspermonth':
				$queryStr = "select *, count(`key`) as total from (select ".$this->db->getDateExtract("date", '%Y-%m')." as `key` from tblDocuments) a group by `key` order by `key`";
				$resArr = $this->db->getResultArray($queryStr);
				if (!$resArr)
					return false;

				return $resArr;
			case 'docsaccumulated':
				$queryStr = "select *, count(`key`) as total from (select ".$this->db->getDateExtract("date")." as `key` from tblDocuments) a group by `key` order by `key`";
				$resArr = $this->db->getResultArray($queryStr);
				if (!$resArr)
					return false;

				$sum = 0;
				foreach($resArr as &$res) {
					$sum += $res['total'];
					/* auxially variable $key is need because sqlite returns
					 * a key '`key`'
					 */
					$res['key'] = mktime(12, 0, 0, substr($res['key'], 5, 2), substr($res['key'], 8, 2), substr($res['key'], 0, 4)) * 1000;
					$res['total'] = $sum;
				}
				return $resArr;
			case 'sizeperuser':
				$queryStr = "select c.fullname as `key`, sum(fileSize) as total from tblDocuments a left join tblDocumentContent b on a.id=b.document left join tblUsers c on a.owner=c.id group by a.owner";
				$resArr = $this->db->getResultArray($queryStr);
				if (!$resArr)
					return false;

				return $resArr;
			default:
				return array();
		}
	} /* }}} */

	/**
	 * Returns changes with a period of time
	 *
	 * This method returns a list of all changes happened in the database
	 * within a given period of time. It currently just checks for
	 * entries in the database tables tblDocumentContent, tblDocumentFiles,
	 * and tblDocumentStatusLog
	 *
	 * @param string $start start date
	 * @param string $end end date
	 * @return array list of changes
	 */
	function getTimeline($startts='', $endts='') { /* {{{ */
		if(!$startts)
			$startts = mktime(0, 0, 0);
		if(!$endts)
			$startts = mktime(24, 0, 0);
		$timeline = array();

		$queryStr = "SELECT document FROM tblDocumentContent WHERE date > ".$startts." AND date < ".$endts;
		$resArr = $this->db->getResultArray($queryStr);
		if ($resArr === false)
			return false;
		foreach($resArr as $rec) {
			$document = $this->getDocument($rec['document']);
			$timeline = array_merge($timeline, $document->getTimeline());
		}
		return $timeline;

	} /* }}} */

	/**
	 * Set a callback function
	 *
	 * @param string $name internal name of callback
	 * @param mixed $func function name as expected by {call_user_method}
	 * @param mixed $params parameter passed as the first argument to the
	 *        callback
	 */
	function setCallback($name, $func, $params=null) { /* {{{ */
		if($name && $func)
			$this->callbacks[$name] = array($func, $params);
	} /* }}} */

	/**
	 * Create an sql dump of the complete database
	 *
	 * @param string $filename name of dump file
	 */
	function createDump($filename) { /* {{{ */
		$h = fopen($filename, "w");
		if(!$h)
			return false;

		$tables = $this->db->TableList('TABLES');
		foreach($tables as $table) {
			$query = "SELECT * FROM `".$table."`";
			$records = $this->db->getResultArray($query);
			fwrite($h,"\n-- TABLE: ".$table."--\n\n");
			foreach($records as $record) {
				$values="";
				$i = 1;
				foreach ($record as $column) {
					if (is_numeric($column)) $values .= $column;
					else $values .= $this->db->qstr($column);
			
					if ($i<(count($record))) $values .= ",";
					$i++;
				}
		
				fwrite($h, "INSERT INTO `".$table."` VALUES (".$values.");\n");
			}
		}

		fclose($h);
		return true;
	} /* }}} */

}
?>
