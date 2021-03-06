<?php
/*****************************************************************************
*       User.php
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


class User extends WebVista_Model_ORM {
	protected $user_id;
	protected $person_id;
	protected $person;
	protected $username;
	protected $password;
	protected $default_location_id;
	protected $defaultBuildingId;
	protected $permissionTemplateId;
	protected $permissionTemplate;
	protected $preferences;

	protected $_table = "user";
	protected $_primaryKeys = array("user_id");
	protected $_legacyORMNaming = true;
	protected $_cascadePersist = false;

	protected $_xmlPreferences = null; // placeholder for simplexml object

	public function __construct() {
		parent::__construct();
		$this->person = new Person();
		$this->permissionTemplate = new PermissionTemplate();
		$this->permissionTemplate->_cascadePersist = false;
	}

	public function setPermissionTemplateId($id) {
		$this->permissionTemplateId = $id;
		$this->permissionTemplate->permissionTemplateId = $this->permissionTemplateId;
	}

	public function setPersonId($id) {
		$this->person_id = (int)$id;
		$this->user_id = $this->person_id;
		$this->person->personId = $this->person_id;
	}

	public function getUserId() {
		return $this->person_id;
	}

	public function setUserId($id) {
		$this->setPersonId($id);
	}

	public function setPerson_id($id) {
		$this->setPersonId($id);
	}

	public function setUser_id($id) {
		$this->setPersonId($id);
	}

	public function populateWithUsername($username = null) {
		if ($username === null) {
			$username = $this->username;
		}
		$db = Zend_Registry::get('dbAdapter');
		$sql = "SELECT * from " . $this->_table . " WHERE 1 "
		  . " and username = " . $db->quote($username);
                $this->populateWithSql($sql);
		$this->person->person_id = $this->person_id;
		$this->person->populate();
	}

	public function populateWithPersonId($personId = null) {
		if ($personId === null) {
			$personId = $this->person_id;
		}
		$db = Zend_Registry::get('dbAdapter');
		$sql = "SELECT * from " . $this->_table . " WHERE 1 "
		  . " and person_id = " . $db->quote($personId);
                $this->populateWithSql($sql);
		$this->person->person_id = $this->person_id;
		$this->person->populate();
	}

	public function __get($key) {
		if (in_array($key,$this->ORMFields())) {
			return $this->$key;
		}
		elseif (in_array($key,$this->person->ORMFields())) {
			return $this->person->__get($key);
		}
		elseif (!is_null(parent::__get($key))) {
			return parent::__get($key);
		}
		elseif (!is_null($this->person->__get($key))) {
			return $this->person->__get($key);
		}
		return parent::__get($key);
	}

	public function get_xmlPreferences() {
		return $this->getXmlPreferences();
	}

	public function getXmlPreferences() {
		if ($this->_xmlPreferences === null && strlen($this->preferences) > 0) {
			$this->_xmlPreferences = new SimpleXMLElement($this->preferences);
		}
		return $this->_xmlPreferences;
	}

	public static function processLogin($username, $password, Zend_Auth_Adapter_Interface $authAdapter = null) {
		if ($authAdapter === null) {
			$authAdapter = new Zend_Auth_Adapter_DbTable(Zend_Registry::get('dbAdapter'));
		}
		$authAdapter->setTableName('user')
			->setIdentityColumn('username')
			->setCredentialColumn('password')
			->setIdentity($username)
			->setCredential($password);

		$auth = Zend_Auth::getInstance();
		$result = $auth->authenticate($authAdapter);

		$audit = new Audit();
		$audit->objectClass = 'Login';
		$audit->objectId = 0;

		if ($result->isValid()) {
			$identity = $auth->getIdentity();
			$user = new User();
			$user->username = $identity;
			$user->populateWithUsername();
			$auth->getStorage()->write($user);
			$audit->userId = $user->userId;
			$message = __('user') . ': ' . $user->username . ' ' . __('login successful');
		} else {
			$auth->clearIdentity();
			$message = __('user') . ': ' . $username . ' ' . __('login failed due to bad password');
		}

		$audit->message = $message;
		$audit->dateTime = date('Y-m-d H:i:s');
		$audit->_ormPersist = true;
		$audit->persist();
		return $result;
	}

}
