<?php
/**
 * Implementation of view class
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
 * Parent class for all view classes
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_Common {
	protected $theme;

	protected $params;

	function __construct($params, $theme='blue') {
		$this->theme = $theme;
		$this->params = $params;
	}

	function __invoke($get=array()) {
		if(isset($get['action']) && $get['action']) {
			if(method_exists($this, $get['action'])) {
				$this->{$get['action']}();
			} else {
				echo "Missing action '".$get['action']."'";
			}
		} else
			$this->show();
	}

	function setParams($params) {
		$this->params = $params;
	}

	function setParam($name, $value) {
		$this->params[$name] = $value;
	}

	function getParam($name) {
		if(isset($this->params[$name]))
			return $this->params[$name];
		return null;
	}

	function unsetParam($name) {
		if(isset($this->params[$name]))
			unset($this->params[$name]);
	}

	function show() {
	}

	/**
	 * Call a hook with a given name
	 *
	 * Checks if a hook with the given name and for the current view
	 * exists and executes it. The name of the current view is taken
	 * from the current class name by lower casing the first char.
	 * This function will execute all registered hooks in the order
	 * they were registered.
	 *
	 * Attention: as func_get_arg() cannot handle references passed to the hook,
	 * callHook() should not be called if that is required. In that case get
	 * a list of hook objects with getHookObjects() and call the hooks yourself.
	 *
	 * @params string $hook name of hook
	 * @return string concatenated string of whatever the hook function returns
	 */
	function callHook($hook) { /* {{{ */
		$tmp = explode('_', get_class($this));
		$ret = null;
		if(isset($GLOBALS['SEEDDMS_HOOKS']['view'][lcfirst($tmp[2])])) {
			foreach($GLOBALS['SEEDDMS_HOOKS']['view'][lcfirst($tmp[2])] as $hookObj) {
				if (method_exists($hookObj, $hook)) {
					switch(func_num_args()) {
						case 1:
							$tmpret = $hookObj->$hook($this);
							if(is_string($tmpret))
								$ret .= $tmpret;
							else
								$ret = $tmpret;
							break;
						case 2:
							$tmpret = $hookObj->$hook($this, func_get_arg(1));
							if(is_string($tmpret))
								$ret .= $tmpret;
							else
								$ret = $tmpret;
							break;
						case 3:
						default:
							$tmpret = $hookObj->$hook($this, func_get_arg(1), func_get_arg(2));
							if(is_string($tmpret))
								$ret .= $tmpret;
							else
								$ret = $tmpret;
					}
				}
			}
		}
		return $ret;
	} /* }}} */

	/**
	 * Return all hook objects for the given or calling class
	 *
	 * <code>
	 * <?php
	 * $hookObjs = $this->getHookObjects();
	 * foreach($hookObjs as $hookObj) {
	 *   if (method_exists($hookObj, $hook)) {
	 *     $ret = $hookObj->$hook($this, ...);
	 *     ...
	 *   }
	 * }
	 * ?>
	 * </code>
	 *
	 * @params string $classname name of class (current class if left empty)
	 * @return array list of hook objects registered for the class
	 */
	function getHookObjects($classname='') { /* {{{ */
		if($classname)
			$tmp = explode('_', $classname);
		else
			$tmp = explode('_', get_class($this));
		if(isset($GLOBALS['SEEDDMS_HOOKS']['view'][lcfirst($tmp[2])])) {
			return $GLOBALS['SEEDDMS_HOOKS']['view'][lcfirst($tmp[2])];
		}
		return array();
	} /* }}} */

	/**
	 * Check if a hook is registered
	 *
	 * @param $hook string name of hook
	 * @return mixed false if one of the hooks fails,
	 *               true if all hooks succedded,
	 *               null if no hook was called
	 */
	function hasHook($hook) { /* {{{ */
		$tmp = explode('_', get_class($this));
		if(isset($GLOBALS['SEEDDMS_HOOKS']['view'][lcfirst($tmp[2])])) {
			foreach($GLOBALS['SEEDDMS_HOOKS']['view'][lcfirst($tmp[2])] as $hookObj) {
				if (method_exists($hookObj, $hook)) {
					return true;
				}
			}
		}
		return false;
	} /* }}} */

}
?>
