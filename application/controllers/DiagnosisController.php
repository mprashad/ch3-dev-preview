<?php
/*****************************************************************************
*       DiagnosisController.php
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


/**
 * Diagnosis controller
 */
class DiagnosisController extends WebVista_Controller_Action {

	public function editAction() {
		$ormId = $this->_getParam('ormId');
		$enumerationId = (int)$this->_getParam('enumerationId');
		$enumerationsClosure = new EnumerationsClosure();
		//$depth = (int)$enumerationsClosure->getDepthById($enumerationId);
		//if ($depth > 1) {
			$diagnosis = new DiagnosisCodesICD();
			$diagnosis->code = $ormId;
			$diagnosis->populate();
			$form = new WebVista_Form(array('name'=>'diagnosisId'));
			$form->setAction(Zend_Registry::get('baseUrl').'diagnosis.raw/process-edit');
			$form->loadORM($diagnosis,'Diagnosis');
			$form->setWindow('windowEditORMObjectId');
			$this->view->form = $form;
		//}
		//else {
		//	$this->view->message = __('There is nothing to edit on the Diagnosis Sections definition, add diagnosis beneath it');
		//}
		$this->view->enumerationId = $enumerationId;
		$this->render();
	}

	public function processEditAction() {
		$enumerationId = (int)$this->_getParam('enumerationId');
		$params = $this->_getParam('diagnosis');
		$diagnosis = new DiagnosisCodesICD();
		$diagnosis->populateWithArray($params);
		$diagnosis->persist();
		if ($enumerationId > 0) {
			$enumeration = new Enumeration();
			$enumeration->enumerationId = $enumerationId;
			$enumeration->populate();
			$enumeration->ormId = $diagnosis->code;
			$enumeration->persist();
		}
		$data = true;
		$json = Zend_Controller_Action_HelperBroker::getStaticHelper('json');
		$json->suppressExit = true;
		$json->direct($data);
	}

	public function lookupAction() {
		$this->view->jsCallback = $this->_getParam('jsCallback','');
		$this->render();
	}

	public function lookupDiagnosisAction() {
		$q = $this->_getParam('q');
		$q = preg_replace('/[^a-zA-Z0-9\%\.]/','',$q);

		$rows = array();
		if (strlen($q) > 0) {
			$diagnosisCodeIterator = new DiagnosisCodesICDIterator();
			$diagnosisCodeIterator->setFilter($q);
			$icd = $diagnosisCodeIterator->toJsonArray('code',array('textShort','code'));

			$diagnosisCodeSNOMEDIterator = new DiagnosisCodesSNOMEDIterator();
			$diagnosisCodeSNOMEDIterator->setFilter($q);
			$snomed = $diagnosisCodeSNOMEDIterator->toJsonArray('snomedId',array('description','snomedId'));
			$rows = array_merge($icd,$snomed);
		}

		$data = array();
		$data['rows'] = $rows;
		$json = Zend_Controller_Action_HelperBroker::getStaticHelper('json');
		$json->suppressExit = true;
		$json->direct($data);
	}

	public function listPatientDiagnosesAction() {
		$patientId = (int)$this->_getParam('patientId');
		$rows = array();
		if ($patientId > 0) {
			$filters = array();
			$filters['status'] = 'Active';
			$filters['personId'] = $patientId;
			$problemListIterator = new ProblemListIterator();
			$problemListIterator->setFilters($filters);
			$diagnosesSections = array();
			foreach ($problemListIterator as $problem) {
				$diagnosesSections[$problem->code] = $problem->codeTextShort;
			}
			// add to problem list, primary, diagnosis, comment
			$patientDiagnosisIterator = new PatientDiagnosisIterator();
			$patientDiagnosisIterator->setFilters(array('patientId'=>$patientId));
			foreach ($patientDiagnosisIterator as $patientDiagnosis) {
				$tmp = array();
				$tmp['id'] = $patientDiagnosis->code;
				$tmp['data'][] = $patientDiagnosis->addToProblemList;
				$tmp['data'][] = $patientDiagnosis->isPrimary;
				$tmp['data'][] = $patientDiagnosis->diagnosis;
				$tmp['data'][] = $patientDiagnosis->comments;
				$tmp['data'][] = isset($diagnosesSections[$patientDiagnosis->code])?'1':'';
				$rows[] = $tmp;
			}
		}
		$data = array();
		$data['rows'] = $rows;
		$json = Zend_Controller_Action_HelperBroker::getStaticHelper('json');
		$json->suppressExit = true;
		$json->direct($data);
	}

	public function processPatientDiagnosesAction() {
		$patientId = (int)$this->_getParam('patientId');
		$diagnoses = $this->_getParam('diagnoses');
		if ($patientId > 0) {
			$patientDiagnosisIterator = new PatientDiagnosisIterator();
			$patientDiagnosisIterator->setFilters(array('patientId'=>$patientId));
			$existingDiagnoses = $patientDiagnosisIterator->toArray('code','patientId');
			foreach ($diagnoses as $code=>$diagnosis) {
				if (isset($existingDiagnoses[$code])) {
					unset($existingDiagnoses[$code]);
				}
				$diagnosis['code'] = $code;
				$diagnosis['patientId'] = $patientId;
				$patientDiagnosis = new PatientDiagnosis();
				$patientDiagnosis->code = $code;
				$patientDiagnosis->populate();
				if ($patientDiagnosis->dateTime == '0000-00-00 00:00:00') {
					$diagnosis['dateTime'] = date('Y-m-d H:i:s');
				}
				$patientDiagnosis->populateWithArray($diagnosis);
				$patientDiagnosis->persist();
			}
			// delete un-used records
			foreach ($existingDiagnoses as $code=>$patientId) {
				$patientDiagnosis = new PatientDiagnosis();
				$patientDiagnosis->code = $code;
				$patientDiagnosis->patientId = $patientId;
				$patientDiagnosis->setPersistMode(WebVista_Model_ORM::DELETE);
				$patientDiagnosis->persist();
			}
		}
		$data = array();
		$data['msg'] = __('Record saved successfully');
		$json = Zend_Controller_Action_HelperBroker::getStaticHelper('json');
		$json->suppressExit = true;
		$json->direct($data);
	}

	public function listAction() {
		$rows = array();
		$guid = 'fac51e51-95fd-485e-a8f3-62e1228057ad';
		$enumeration = new Enumeration();
		$enumeration->populateByGuid($guid);
		$closure = new EnumerationClosure();
		$enumerationIterator = $closure->getAllDescendants($enumeration->enumerationId,1,true);
		foreach ($enumerationIterator as $enum) {
			$row = array();
			$row['id'] = $enum->enumerationId;
			$row['data'] = array();
			$row['data'][] = $enum->name;
			$rows[] = $row;
		}
		$json = Zend_Controller_Action_HelperBroker::getStaticHelper('json');
		$json->suppressExit = true;
		$json->direct(array('rows'=>$rows),true);
	}

	public function listSectionAction() {
		$rows = array();
		$diagnosis = (int)$this->_getParam('diagnosis');
		$closure = new EnumerationClosure();
		foreach ($closure->getAllDescendants($diagnosis,1,true) as $enum) {
			$row['id'] = $enum->enumerationId;
			$row['data'] = array();
			$row['data'][] = '';
			$row['data'][] = $enum->name;
			$row['data'][] = $enum->enumerationId;
			$rows[] = $row;
		}
		$json = Zend_Controller_Action_HelperBroker::getStaticHelper('json');
		$json->suppressExit = true;
		$json->direct(array('rows'=>$rows),true);
	}

}
