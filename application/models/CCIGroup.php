<?php
/*****************************************************************************
*       CCIGroup.php
*
*       Author:  ClearHealth Inc. (www.clear-health.com)        2009
*       
*       ClearHealth(TM), HealthCloud(TM), WebVista(TM) and their 
*       respective logos, icons, and terms are registered trademarks 
*       of ClearHealth Inc.
*
*       Though this software is open source you MAY NOT use our 
*       trademarks, graphics, logos and icons without explicit permission. 
*       Derivitive works MUST NOT be primarily identified using our 
*       trademarks, though statements such as "Based on ClearHealth(TM) 
*       Technology" or "incoporating ClearHealth(TM) source code" 
*       are permissible.
*
*       This file is licensed under the GPL V3, you can find
*       a copy of that license by visiting:
*       http://www.fsf.org/licensing/licenses/gpl.html
*       
*****************************************************************************/


class CCIGroup extends WebVista_Model_ORM {

	protected $codeRange;
	protected $code;
	protected $existsPriorTo1996;
	protected $dateEffective;
	protected $dateDeletion;
	protected $modifier; // 0 = not allowed, 1 = allowed, 9 = not applicable

	protected $_table = 'CCIGroups';
	protected $_primaryKeys = array('codeRange','code');

	public function __isset($key) { // this must be defined for parent::audit($obj)
		$ret = false;
		if (method_exists($this,'get'.ucfirst($key)) || isset($this->$key)) {
			$ret = true;
		}
		return $ret;
	}

	public function getCCIGroupId() {
		return $this->codeRange;
	}

}
