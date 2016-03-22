<?php
/**
 * Implementation of EditFolder controller
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2013 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class which does the busines logic for editing a folder
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2013 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Controller_EditFolder extends SeedDMS_Controller_Common {

	public function run() {
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$settings = $this->params['settings'];
		$folder = $this->params['folder'];
		$name = $this->params['name'];
		$comment = $this->params['comment'];
		$sequence = $this->params['sequence'];
		$attributes = $this->params['attributes'];

		/* Get the document id and name before removing the document */
		$foldername = $folder->getName();
		$folderid = $folder->getID();

		if(!$this->callHook('preEditFolder')) {
		}

		$result = $this->callHook('editFolder', $folder);
		if($result === null) {
			if(($oldname = $folder->getName()) != $name)
				if(!$folder->setName($name))
					return false;

			if(($oldcomment = $folder->getComment()) != $comment)
				if(!$folder->setComment($comment))
					return false;

			$oldattributes = $folder->getAttributes();
			if($attributes) {
				foreach($attributes as $attrdefid=>$attribute) {
					$attrdef = $dms->getAttributeDefinition($attrdefid);
					if($attribute) {
						if(!$attrdef->validate($attribute)) {
							$this->error = $attrdef->getValidationError();
							switch($attrdef->getValidationError()) {
							case 5:
								$this->errormsg = getMLText("attr_malformed_email", array("attrname"=>$attrdef->getName(), "value"=>$attribute));
								break;
							case 4:
								$this->errormsg = getMLText("attr_malformed_url", array("attrname"=>$attrdef->getName(), "value"=>$attribute));
								break;
							case 3:
								$this->errormsg = getMLText("attr_no_regex_match", array("attrname"=>$attrdef->getName(), "value"=>$attribute, "regex"=>$attrdef->getRegex()));
								break;
							case 2:
								$this->errormsg = getMLText("attr_max_values", array("attrname"=>$attrdef->getName()));
								break;
							case 1:
								$this->errormsg = getMLText("attr_min_values", array("attrname"=>$attrdef->getName()));
								break;
							default:
								$this->errormsg = getMLText("error_occured");
							}
							return false;
						}
							/*
						if($attrdef->getRegex()) {
							if(!preg_match($attrdef->getRegex(), $attribute)) {
								$this->error = 1;
								return false;
							}
						}
						if(is_array($attribute)) {
							if($attrdef->getMinValues() > count($attribute)) {
								$this->error = 2;
								return false;
							}
							if($attrdef->getMaxValues() && $attrdef->getMaxValues() < count($attribute)) {
								$this->error = 3;
								return false;
							}
						}
							 */
						if(!isset($oldattributes[$attrdefid]) || $attribute != $oldattributes[$attrdefid]->getValue()) {
							if(!$folder->setAttributeValue($dms->getAttributeDefinition($attrdefid), $attribute))
								return false;
						}
					} elseif(isset($oldattributes[$attrdefid])) {
						if(!$folder->removeAttribute($dms->getAttributeDefinition($attrdefid)))
							return false;
					}
				}
			}
			foreach($oldattributes as $attrdefid=>$oldattribute) {
				if(!isset($attributes[$attrdefid])) {
					if(!$folder->removeAttribute($dms->getAttributeDefinition($attrdefid)))
						return false;
				}
			}

			if(strcasecmp($sequence, "keep")) {
				if($folder->setSequence($sequence)) {
				} else {
					return false;
				}
			}

			if(!$this->callHook('postEditFolder')) {
			}

		} else
			return $result;

		return true;
	}
}
