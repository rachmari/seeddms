<?php
/**
 * Implementation of the attribute object in the document management system
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2012 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class to represent an attribute in the document management system
 *
 * Attributes are key/value pairs which can be attachted to documents,
 * folders and document content. The number of attributes is unlimited.
 * Each attribute has a value and is related to an attribute definition,
 * which holds the name and other information about the attribute.
 *
 * @see SeedDMS_Core_AttributeDefinition
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2012-2013 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Core_Attribute { /* {{{ */
	/**
	 * @var integer id of attribute
	 *
	 * @access protected
	 */
	protected $_id;

	/**
	 * @var object SeedDMS_Core_Object folder, document or document content
	 * this attribute belongs to
	 *
	 * @access protected
	 */
	protected $_obj;

	/**
	 * @var object SeedDMS_Core_AttributeDefinition definition of this attribute
	 *
	 * @access protected
	 */
	protected $_attrdef;

	/**
	 * @var mixed value of this attribute
	 *
	 * @access protected
	 */
	protected $_value;

	/**
	 * @var integer validation error
	 *
	 * @access protected
	 */
	protected $_validation_error;

	/**
	 * @var object SeedDMS_Core_DMS reference to the dms instance this attribute belongs to
	 *
	 * @access protected
	 */
	protected $_dms;

	/**
	 * Constructor
	 *
	 * @param integer $id internal id of attribute
	 * @param SeedDMS_Core_Object $obj object this attribute is attached to
	 * @param SeedDMS_Core_AttributeDefinition $attrdef reference to the attribute definition
	 * @param string $value value of the attribute
	 */
	function SeedDMS_Core_Attribute($id, $obj, $attrdef, $value) { /* {{{ */
		$this->_id = $id;
		$this->_obj = $obj;
		$this->_attrdef = $attrdef;
		$this->_value = $value;
		$this->_validation_error = 0;
		$this->_dms = null;
	} /* }}} */

	/**
	 * Set reference to dms
	 *
	 * @param SeedDMS_Core_DMS $dms
	 */
	function setDMS($dms) { /* {{{ */
		$this->_dms = $dms;
	} /* }}} */

	/**
	 * Get internal id of attribute
	 *
	 * @return integer id
	 */
	function getID() { return $this->_id; }

	/**
	 * Return attribute value as stored in database
	 *
	 * This function will return the value of multi value attributes
	 * including the separator char.
	 *
	 * @return string the attribute value as it is stored in the database.
	 */
	function getValue() { return $this->_value; }

	/**
	 * Return attribute values as an array
	 *
	 * This function returns the attribute value as an array. The array
	 * has one element for non multi value attributes and n elements for
	 * multi value attributes.
	 *
	 * @return array the attribute values
	 */
	function getValueAsArray() { /* {{{ */
		if($this->_attrdef->getMultipleValues()) {
			return explode($this->_value[0], substr($this->_value, 1));
		} else {
			return array($this->_value);
		}
	} /* }}} */

	/**
	 * Set a value of an attribute
	 *
	 * The attribute is completely deleted if the value is an empty string
	 * or empty array. An array of values is only allowed if the attribute may
	 * have multiple values. If an array is passed and the attribute may
	 * have only a single value, then the first element of the array will
	 * be taken.
	 *
	 * @param string $values value as string or array to be set
	 * @return boolean true if operation was successfull, otherwise false
	 */
	function setValue($values) { /* {{{*/
		$db = $this->_dms->getDB();

		if($this->_attrdef->getMultipleValues()) {
			/* Multiple values without a value set is not allowed */
			if(!$valuesetstr = $this->_attrdef->getValueSet())
				return false;
			$valueset = $this->_attrdef->getValueSetAsArray();

			if(is_array($values)) {
				if($values) {
					$error = false;
					foreach($values as $v) {
						if(!in_array($v, $valueset)) { $error = true; break; }
					}
					if($error)
						return false;
					$valuesetstr = $this->_attrdef->getValueSet();
					$value = $valuesetstr[0].implode($valuesetstr[0], $values);
				} else {
					$value = '';
				}
			} else {
				if($values) {
					if($valuesetstr[0] != $values[0])
						$values = explode($valuesetstr[0], $values);
					else
						$values = explode($valuesetstr[0], substr($values, 1));

					$error = false;
					foreach($values as $v) {
						if(!in_array($v, $valueset)) { $error = true; break; }
					}
					if($error)
						return false;
					$value = $valuesetstr[0].implode($valuesetstr[0], $values);
				} else {
					$value = $values;
				}
			}
		} else {
			if(is_array($values)) {
				if($values)
					$value = $values[0];
				else
					$value = '';
			} else {
				$value = $values;
			}
		}

		switch(get_class($this->_obj)) {
			case $this->_dms->getClassname('document'):
				if(trim($value) === '')
					$queryStr = "DELETE FROM tblDocumentAttributes WHERE `document` = " . $this->_obj->getID() . " AND `attrdef` = " . $this->_attrdef->getId();
				else
					$queryStr = "UPDATE tblDocumentAttributes SET value = ".$db->qstr($value)." WHERE `document` = " . $this->_obj->getID() .	" AND `attrdef` = " . $this->_attrdef->getId();
				break;
			case $this->_dms->getClassname('documentcontent'):
				if(trim($value) === '')
					$queryStr = "DELETE FROM tblDocumentContentAttributes WHERE `content` = " . $this->_obj->getID() . " AND `attrdef` = " . $this->_attrdef->getId();
				else
					$queryStr = "UPDATE tblDocumentContentAttributes SET value = ".$db->qstr($value)." WHERE `content` = " . $this->_obj->getID() .	" AND `attrdef` = " . $this->_attrdef->getId();
				break;
			case $this->_dms->getClassname('folder'):
				if(trim($value) === '')
					$queryStr = "DELETE FROM tblFolderAttributes WHERE `folder` = " . $this->_obj->getID() .	" AND `attrdef` = " . $this->_attrdef->getId();
				else
					$queryStr = "UPDATE tblFolderAttributes SET value = ".$db->qstr($value)." WHERE `folder` = " . $this->_obj->getID() .	" AND `attrdef` = " . $this->_attrdef->getId();
				break;
			default:
				return false;
		}
		if (!$db->getResult($queryStr))
			return false;

		$this->_value = $value;

		return true;
	} /* }}} */

	/**
	 * Validate attribute value
	 *
	 * This function checks if the attribute values fits the attribute
	 * definition.
	 * If the validation fails the validation error will be set which
	 * can be requested by SeedDMS_Core_Attribute::getValidationError()
	 *
	 * @return boolean true if validation succeds, otherwise false
	 */
	function validate() { /* {{{ */
		$attrdef = $this->_attrdef();
		$result = $attrdef->validate($this->_value);
		$this->_validation_error = $attrdef->getValidationError();
		return $result;
	} /* }}} */

	/**
	 * Get validation error from last validation
	 *
	 * @return integer error code
	 */
	function getValidationError() { return $this->_validation_error; }

	/**
	 * Get definition of attribute
	 *
	 * @return object attribute definition
	 */
	function getAttributeDefinition() { return $this->_attrdef; }

} /* }}} */

/**
 * Class to represent an attribute definition in the document management system
 *
 * Attribute definitions specify the name, type, object type, minimum and
 * maximum values and a value set. The object type determines the object
 * an attribute may be attached to. If the object type is set to object_all
 * the attribute can be used for documents, document content and folders.
 *
 * The type of an attribute specifies the skalar data type.
 *
 * Attributes for which multiple values are allowed must have the
 * multiple flag set to true and specify a value set. A value set
 * is a string consisting of n separated values. The separator is the
 * first char of the value set. A possible value could be '|REV-A|REV-B'
 * If multiple values are allowed, then minvalues and maxvalues may
 * restrict the allowed number of values.
 *
 * @see SeedDMS_Core_Attribute
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Core_AttributeDefinition { /* {{{ */
	/**
	 * @var integer id of attribute definition
	 *
	 * @access protected
	 */
	protected $_id;

	/**
	 * @var string name of attribute definition
	 *
	 * @access protected
	 */
	protected $_name;

	/**
	 * @var string object type of attribute definition. This can be one of
	 * type_int, type_float, type_string, type_boolean, type_url, or type_email.
	 *
	 * @access protected
	 */
	protected $_type;

	/**
	 * @var string type of attribute definition. This can be one of objtype_all,
	 * objtype_folder, objtype_document, or objtype_documentcontent.
	 *
	 * @access protected
	 */
	protected $_objtype;

	/**
	 * @var boolean whether an attribute can have multiple values
	 *
	 * @access protected
	 */
	protected $_multiple;

	/**
	 * @var integer minimum values of an attribute
	 *
	 * @access protected
	 */
	protected $_minvalues;

	/**
	 * @var integer maximum values of an attribute
	 *
	 * @access protected
	 */
	protected $_maxvalues;

	/**
	 * @var string list of possible values of an attribute
	 *
	 * @access protected
	 */
	protected $_valueset;

	/**
	 * @var string regular expression the value must match
	 *
	 * @access protected
	 */
	protected $_regex;

	/**
	 * @var integer validation error
	 *
	 * @access protected
	 */
	protected $_validation_error;

	/**
	 * @var object SeedDMS_Core_DMS reference to the dms instance this attribute definition belongs to
	 *
	 * @access protected
	 */
	protected $_dms;

	/*
	 * Possible skalar data types of an attribute
	 */
	const type_int = '1';
	const type_float = '2';
	const type_string = '3';
	const type_boolean = '4';
	const type_url = '5';
	const type_email = '6';
	const type_date = '7';

	/*
	 * The object type for which a attribute may be used
	 */
	const objtype_all = '0';
	const objtype_folder = '1';
	const objtype_document = '2';
	const objtype_documentcontent = '3';

	/**
	 * Constructor
	 *
	 * @param integer $id internal id of attribute definition
	 * @param string $name name of attribute
	 * @param integer $objtype type of object for which this attribute definition
	 *        may be used.
	 * @param integer $type skalar type of attribute
	 * @param boolean $multiple set to true if multiple values are allowed
	 * @param integer $minvalues minimum number of values
	 * @param integer $maxvalues maximum number of values
	 * @param string $valueset separated list of allowed values, the first char
	 *        is taken as the separator
	 */
	function SeedDMS_Core_AttributeDefinition($id, $name, $objtype, $type, $multiple, $minvalues, $maxvalues, $valueset, $regex) { /* {{{ */
		$this->_id = $id;
		$this->_name = $name;
		$this->_type = $type;
		$this->_objtype = $objtype;
		$this->_multiple = $multiple;
		$this->_minvalues = $minvalues;
		$this->_maxvalues = $maxvalues;
		$this->_valueset = $valueset;
		$this->_separator = '';
		$this->_regex = $regex;
		$this->_dms = null;
		$this->_validation_error = 0;
	} /* }}} */

	/**
	 * Set reference to dms
	 *
	 * @param SeedDMS_Core_DMS $dms
	 */
	function setDMS($dms) { /* {{{ */
		$this->_dms = $dms;
	} /* }}} */

	/**
	 * Get internal id of attribute definition
	 *
	 * @return integer id
	 */
	function getID() { return $this->_id; }

	/**
	 * Get name of attribute definition
	 *
	 * @return string name
	 */
	function getName() { return $this->_name; }

	function setName($name) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblAttributeDefinitions SET name =".$db->qstr($name)." WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_name = $name;
		return true;
	} /* }}} */

	/**
	 * Get object type of attribute definition
	 * 
	 * This can be one of objtype_all,
	 * objtype_folder, objtype_document, or objtype_documentcontent.
	 *
	 * @return integer type
	 */
	function getObjType() { return $this->_objtype; }

	/**
	 * Set object type of attribute definition
	 * 
	 * This can be one of objtype_all,
	 * objtype_folder, objtype_document, or objtype_documentcontent.
	 *
	 * @param integer $objtype type
	 */
	function setObjType($objtype) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblAttributeDefinitions SET objtype =".intval($objtype)." WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_objtype = $objtype;
		return true;
	} /* }}} */

	/**
	 * Get type of attribute definition
	 * 
	 * This can be one of type_int, type_float, type_string, type_boolean,
	 * type_url, type_email.
	 *
	 * @return integer type
	 */
	function getType() { return $this->_type; }

	/**
	 * Set type of attribute definition
	 * 
	 * This can be one of type_int, type_float, type_string, type_boolean,
	 * type_url, type_email.
	 *
	 * @param integer $type type
	 */
	function setType($type) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblAttributeDefinitions SET type =".intval($type)." WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_type = $type;
		return true;
	} /* }}} */

	/**
	 * Check if attribute definition allows multi values for attribute
	 * 
	 * @return boolean true if attribute may have multiple values
	 */
	function getMultipleValues() { return $this->_multiple; }

	/**
	 * Set if attribute definition allows multi values for attribute
	 * 
	 * @param boolean $mv true if attribute may have multiple values, otherwise
	 * false
	 */
	function setMultipleValues($mv) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblAttributeDefinitions SET multiple =".intval($mv)." WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_multiple = $mv;
		return true;
	} /* }}} */

	/**
	 * Return minimum number of values for attributes
	 * 
	 * Attributes with multiple values may be limited to a range
	 * of values. This functions returns the minimum number of values.
	 *
	 * @return integer minimum number of values
	 */
	function getMinValues() { return $this->_minvalues; }

	function setMinValues($minvalues) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblAttributeDefinitions SET minvalues =".intval($minvalues)." WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_minvalues = $minvalues;
		return true;
	} /* }}} */

	/**
	 * Return maximum number of values for attributes
	 * 
	 * Attributes with multiple values may be limited to a range
	 * of values. This functions returns the maximum number of values.
	 *
	 * @return integer maximum number of values
	 */
	function getMaxValues() { return $this->_maxvalues; }

	function setMaxValues($maxvalues) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblAttributeDefinitions SET maxvalues =".intval($maxvalues)." WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_maxvalues = $maxvalues;
		return true;
	} /* }}} */

	/**
	 * Get the value set as saved in the database
	 *
	 * This is a string containing the list of valueѕ separated by a
	 * delimiter which also precedes the whole string, e.g. '|Yes|No'
	 *
	 * Use {@link SeedDMS_Core_AttributeDefinition::getValueSetAsArray()}
	 * for a list of values returned as an array.
	 *
	 * @return string value set
	 */
	function getValueSet() { /* {{{ */
		return $this->_valueset;
	} /* }}} */

	/**
	 * Get the whole value set as an array
	 *
	 * @return array values of value set or false if the value set has
	 *         less than 2 chars
	 */
	function getValueSetAsArray() { /* {{{ */
		if(strlen($this->_valueset) > 1)
			return explode($this->_valueset[0], substr($this->_valueset, 1));
		else
			return array();
	} /* }}} */

	/**
	 * Get the n'th value of a value set
	 *
	 * @param interger $index
	 * @return string n'th value of value set or false if the index is
	 *         out of range or the value set has less than 2 chars
	 */
	function getValueSetValue($ind) { /* {{{ */
		if(strlen($this->_valueset) > 1) {
			$tmp = explode($this->_valueset[0], substr($this->_valueset, 1));
			if(isset($tmp[$ind]))
				return $tmp[$ind];
			else
				return false;
		} else
			return false;
	} /* }}} */

	/**
	 * Set the value set
	 *
	 * A value set is a list of values allowed for an attribute. The values
	 * are separated by a char which must also be the first char of the
	 * value set string.
	 *
	 * @param string $valueset
	 * @return boolean true if value set could be set, otherwise false
	 */
	function setValueSet($valueset) { /* {{{ */
	/*
		$tmp = array();
		foreach($valueset as $value) {
			$tmp[] = str_replace('"', '""', $value);
		}
		$valuesetstr = implode(",", $tmp);
	*/
		$valuesetstr = $valueset;

		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblAttributeDefinitions SET valueset =".$db->qstr($valuesetstr)." WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_valueset = $valueset;
		$this->_separator = substr($valueset, 0, 1);
		return true;
	} /* }}} */

	/**
	 * Get the regular expression as saved in the database
	 *
	 * @return string regular expression
	 */
	function getRegex() { /* {{{ */
		return $this->_regex;
	} /* }}} */

	/**
	 * Set the regular expression
	 *
	 * A value of the attribute must match this regular expression.
	 *
	 * @param string $regex
	 * @return boolean true if regex could be set, otherwise false
	 */
	function setRegex($regex) { /* {{{ */
		$db = $this->_dms->getDB();

		$queryStr = "UPDATE tblAttributeDefinitions SET regex =".$db->qstr($regex)." WHERE id = " . $this->_id;
		$res = $db->getResult($queryStr);
		if (!$res)
			return false;

		$this->_regex = $regex;
		return true;
	} /* }}} */

	/**
	 * Check if the attribute definition is used
	 *
	 * Checks all documents, folders and document content whether at least
	 * one of them referenceѕ this attribute definition
	 *
	 * @return boolean true if attribute definition is used, otherwise false
	 */
	function isUsed() { /* {{{ */
		$db = $this->_dms->getDB();
		
		$queryStr = "SELECT * FROM tblDocumentAttributes WHERE attrdef=".$this->_id;
		$resArr = $db->getResultArray($queryStr);
		if (is_array($resArr) && count($resArr) == 0) {
			$queryStr = "SELECT * FROM tblFolderAttributes WHERE attrdef=".$this->_id;
			$resArr = $db->getResultArray($queryStr);
			if (is_array($resArr) && count($resArr) == 0) {
				$queryStr = "SELECT * FROM tblDocumentContentAttributes WHERE attrdef=".$this->_id;
				$resArr = $db->getResultArray($queryStr);
				if (is_array($resArr) && count($resArr) == 0) {

					return false;
				}
			}
		}
		return true;
	} /* }}} */

	/**
	 * Return a list of documents, folders, document contents where this
	 * attribute definition is used
	 *
	 * @param integer $limit return not more the n objects of each type
	 * @return boolean true if attribute definition is used, otherwise false
	 */
	function getStatistics($limit=0) { /* {{{ */
		$db = $this->_dms->getDB();

		$result = array('docs'=>array(), 'folders'=>array(), 'contents'=>array());
		if($this->_objtype == SeedDMS_Core_AttributeDefinition::objtype_all ||
		   $this->_objtype == SeedDMS_Core_AttributeDefinition::objtype_document) {
			$queryStr = "SELECT * FROM tblDocumentAttributes WHERE attrdef=".$this->_id;
			if($limit)
				$queryStr .= " limit ".(int) $limit;
			$resArr = $db->getResultArray($queryStr);
			if($resArr) {
				foreach($resArr as $rec) {
					if($doc = $this->_dms->getDocument($rec['document'])) {
						$result['docs'][] = $doc;
					}
				}
			}
			$queryStr = "SELECT count(*) c, value FROM tblDocumentAttributes WHERE attrdef=".$this->_id." GROUP BY value ORDER BY c DESC";
			$resArr = $db->getResultArray($queryStr);
			if($resArr) {
				$result['frequencies']['document'] = $resArr;
			}
		}

		if($this->_objtype == SeedDMS_Core_AttributeDefinition::objtype_all ||
		   $this->_objtype == SeedDMS_Core_AttributeDefinition::objtype_folder) {
			$queryStr = "SELECT * FROM tblFolderAttributes WHERE attrdef=".$this->_id;
			if($limit)
				$queryStr .= " limit ".(int) $limit;
			$resArr = $db->getResultArray($queryStr);
			if($resArr) {
				foreach($resArr as $rec) {
					if($folder = $this->_dms->getFolder($rec['folder'])) {
						$result['folders'][] = $folder;
					}
				}
			}
			$queryStr = "SELECT count(*) c, value FROM tblFolderAttributes WHERE attrdef=".$this->_id." GROUP BY value ORDER BY c DESC";
			$resArr = $db->getResultArray($queryStr);
			if($resArr) {
				$result['frequencies']['folder'] = $resArr;
			}
		}

		if($this->_objtype == SeedDMS_Core_AttributeDefinition::objtype_all ||
		   $this->_objtype == SeedDMS_Core_AttributeDefinition::objtype_documentcontent) {
			$queryStr = "SELECT * FROM tblDocumentContentAttributes WHERE attrdef=".$this->_id;
			if($limit)
				$queryStr .= " limit ".(int) $limit;
			$resArr = $db->getResultArray($queryStr);
			if($resArr) {
				foreach($resArr as $rec) {
					if($content = $this->_dms->getDocumentContent($rec['content'])) {
						$result['contents'][] = $content;
					}
				}
			}
			$queryStr = "SELECT count(*) c, value FROM tblDocumentContentAttributes WHERE attrdef=".$this->_id." GROUP BY value ORDER BY c DESC";
			$resArr = $db->getResultArray($queryStr);
			if($resArr) {
				$result['frequencies']['content'] = $resArr;
			}
		}

		return $result;
	} /* }}} */

	/**
	 * Remove the attribute definition
	 * Removal is only executed when the definition is not used anymore.
	 *
	 * @return boolean true on success or false in case of an error
	 */
	function remove() { /* {{{ */
		$db = $this->_dms->getDB();

		if($this->isUsed())
			return false;

		// Delete user itself
		$queryStr = "DELETE FROM tblAttributeDefinitions WHERE id = " . $this->_id;
		if (!$db->getResult($queryStr)) return false;

		return true;
	} /* }}} */

	/**
	 * Get all documents and folder by a given attribute value
	 *
	 * @param string $attrvalue value of attribute
	 * @param integer $limit limit number of documents/folders
	 * @return array array containing list of documents and folders
	 */
	public function getObjects($attrvalue, $limit) { /* {{{ */
		$db = $this->_dms->getDB();

		$result = array('docs'=>array(), 'folders'=>array(), 'contents'=>array());
		if($this->_objtype == SeedDMS_Core_AttributeDefinition::objtype_all ||
		   $this->_objtype == SeedDMS_Core_AttributeDefinition::objtype_document) {
			$queryStr = "SELECT * FROM tblDocumentAttributes WHERE attrdef=".$this->_id." AND value=".$db->qstr($attrvalue);
			if($limit)
				$queryStr .= " limit ".(int) $limit;
			$resArr = $db->getResultArray($queryStr);
			if($resArr) {
				foreach($resArr as $rec) {
					if($doc = $this->_dms->getDocument($rec['document'])) {
						$result['docs'][] = $doc;
					}
				}
			}
		}

		if($this->_objtype == SeedDMS_Core_AttributeDefinition::objtype_all ||
		   $this->_objtype == SeedDMS_Core_AttributeDefinition::objtype_folder) {
			$queryStr = "SELECT * FROM tblFolderAttributes WHERE attrdef=".$this->_id." AND value=".$db->qstr($attrvalue);
			if($limit)
				$queryStr .= " limit ".(int) $limit;
			$resArr = $db->getResultArray($queryStr);
			if($resArr) {
				foreach($resArr as $rec) {
					if($folder = $this->_dms->getFolder($rec['folder'])) {
						$result['folders'][] = $folder;
					}
				}
			}
		}

		return $result;
	} /* }}} */

	/**
	 * Validate value against attribute definition
	 *
	 * This function checks if the given value fits the attribute
	 * definition.
	 * If the validation fails the validation error will be set which
	 * can be requested by SeedDMS_Core_Attribute::getValidationError()
	 *
	 * @param string|array $attrvalue attribute value
	 * @return boolean true if validation succeds, otherwise false
	 */
	function validate($attrvalue) { /* {{{ */
		if($this->getMultipleValues()) {
			if(is_string($attrvalue))
				$values = explode($attrvalue[0], substr($attrvalue, 1));
			else
				$values = $attrvalue;
		} else {
			$values = array($attrvalue);
		}

		$this->_validation_error = 0;
		if($this->getMinValues() > count($values)) {
			$this->_validation_error = 1;
			return false;
		}
		if($this->getMaxValues() && $this->getMaxValues() < count($values)) {
			$this->_validation_error = 2;
			return false;
		}

		switch((string) $this->getType()) {
		case self::type_int:
			$success = true;
			foreach($values as $value) {
				$success &= preg_match('/^[0-9]*$/', $value) ? true : false;
			}
			break;
		case self::type_float:
			$success = true;
			foreach($values as $value) {
				$success &= is_numeric($value);
			}
			break;
		case self::type_string:
			$success = true;
			if(trim($this->getRegex()) != '') {
				foreach($values as $value) {
					$success &= preg_match($this->getRegex(), $value) ? true : false;
				}
			}
			if(!$success)
				$this->_validation_error = 3;
			break;
		case self::type_boolean:
			$success = true;
			foreach($values as $value) {
				$success &= preg_match('/^[01]$/', $value);
			}
			break;
		case self::type_email:
			$success = true;
			foreach($values as $value) {
			}
			if(!$success)
				$this->_validation_error = 5;
			break;
		case self::type_url:
			$success = true;
			foreach($values as $value) {
				$success &= preg_match('/^http(s)?:\/\/[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(\/.*)?$/i', $value);
			}
			if(!$success)
				$this->_validation_error = 4;
			break;
		}

		/* Check if value is in value set */
		if($valueset = $this->getValueSetAsArray()) {
			foreach($values as $value) {
				if(!in_array($value, $valueset)) {
					$success = false;
					$this->_validation_error = 10;
				}
			}
		}

		return $success;

	} /* }}} */

	/**
	 * Get validation error from last validation
	 *
	 * @return integer error code
	 */
	function getValidationError() { return $this->_validation_error; }

} /* }}} */
?>
