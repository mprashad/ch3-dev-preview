<?php
/*****************************************************************************
*       PatientNote.php
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


class PatientNote extends WebVista_Model_ORM {

	protected $patient_note_id;
	protected $patient_id;
	protected $patient;
	protected $user_id;
	protected $user;
	protected $priority;
	protected $note_date;
	protected $note;
	protected $deprecated;
	protected $reason;
	protected $posting;
	protected $active;

	protected $_primaryKeys = array('patient_note_id');
	protected $_table = 'patient_note';
	protected $_legacyORMNaming = true;
	protected $_cascadePersist = false;

	const ENUM_REASON_PARENT_NAME = 'Reason Preferences';

	public function __construct() {
		parent::__construct();
		$this->patient = new Patient();
		$this->patient->_cascadePersist = $this->_cascadePersist;
		$this->user = new User();
		$this->user->_cascadePersist = $this->_cascadePersist;
	}

	public function getIteratorByPatientId($patientId = null) {
		if ($patientId === null) {
			$patientId = $this->patient_id;
		}
		$iterator = $this->getIterator();
		$iterator->setFilters(array('patient_id'=>(int)$patientId,'deprecated'=>0,'posting'=>0));
		return $iterator;
	}

}
