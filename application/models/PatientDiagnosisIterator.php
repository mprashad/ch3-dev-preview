<?php
/*****************************************************************************
*       PatientDiagnosisIterator.php
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


class PatientDiagnosisIterator extends WebVista_Model_ORMIterator {

	public function __construct($dbSelect = null) {
		parent::__construct('PatientDiagnosis',$dbSelect);
	}

	public function setFilters(Array $filter) {
		$db = Zend_Registry::get('dbAdapter');
		$dbSelect = $db->select()
			       ->from('patientDiagnosis')
			       ->where('patientId = ?',$filter['patientId'])
				->order('isPrimary DESC')
				->order('code');
		$this->_dbSelect = $dbSelect;
		$this->_dbStmt = $db->query($this->_dbSelect);
	}

}
