<?php
App::uses('QueueAppModel', 'Queue.Model');
class Queue extends QueueAppModel {

	/**
	 * Display field
	 * @var string
	 */
	public $displayField = 'type';

	/**
	 * Validation rules
	 * @var array
	 */
	public $validate = array(
		'status' => array(
			'validStatus' => array(
				'rule' => array('validStatus'),
				'message' => 'Please select a valid status',
			),
		),
		'type' => array(
			'validType' => array(
				'rule' => array('validType'),
				'message' => 'Please select a valid type',
			),
			'allowedType' => array(
				'rule' => array('allowedType'),
				'message' => 'Specified Type is not allowed by your configuration file. check Config/queue.php'
			)
		),
		'command' => array(
			'notempty' => array(
				'rule' => array('notempty'),
				'message' => 'No command. Please specify.',
			),
			'validCommand' => array(
				'rule' => array('validCommand'),
				'message' => 'Command not valid for type.'
			)
		),
	);

	/**
	 * Filter fields
	 * @var array
	 */
	public $searchFields = array(
		'Queue.command','Queue.id','Queue.status','Queue.type'
	);

	/**
	* Status key to human readable
	* @var array
	* @access protected
	*/
	protected $_statuses = array(
		1 => 'queued',
		2 => 'in progress',
		3 => 'finished',
		4 => 'paused',
	);
	/**
	* type key to human readable
	* @var array
	* @access protected
	*/
	protected $_types = array(
		1 => 'model',
		2 => 'shell',
		3 => 'url',
		4 => 'php_cmd',
		5 => 'shell_cmd'
	);
	
	/**
	* Placeholder for shell
	*/
	public $Shell = null;

	/**
	* Validataion of Type
	* @param array field
	* @return boolean if valid
	*/
	public function validType($field) {
		return isset($this->_types[$field['type']]);
	}

	/**
	* Validataion of Status
	* @param array field
	* @return boolean if valid
	*/
	public function validStatus($field) {
		return isset($this->_statuses[$field['status']]);
	}

	/**
	* Validataion of Command
	* @param array field
	* @return boolean if valid
	*/
	public function validCommand($field) {
		return true; //TODO
	}

	/**
	* Validataion of Type Allowed, based on configuration app/Config/queue.php
	* @param array field
	* @return boolean if valid
	*/
	public function allowedType() {
		$allowedTypes = QueueUtil::getConfig('allowedTypes');
		return isset($allowedTypes[$field['type']]);
	}

	/**
	* Assign the user_id if we have one.
	* @param array options
	* @return boolean success
	*/
	public function beforeSave($options = array()) {
		if ($user_id = $this->getCurrentUser('id')) {
			$this->data[$this->alias]['user_id'] = $user_id;
		}
		return parent::beforeSave($options);
	}

	/**
	* afterFind will add status_human and type_human to the result
	* human readable and understandable type and status.
	* @param array of results
	* @param boolean primary
	* @return array of altered results
	*/
	public function afterFind($results = array(), $primary = false){
		foreach ($results as $key => $val) {
			if (isset($val[$this->alias]['type'])) {
				$results[$key][$this->alias]['type_human'] = $this->_types[$val[$this->alias]['type']];
			}
			if (isset($val[$this->alias]['status'])) {
				$results[$key][$this->alias]['status_human'] = $this->_statuses[$val[$this->alias]['status']];
			}
		}
		return $results;
	}
	/**
	* Return count of in progress queues
	* @return int number of running queues.
	*/
	public function inProgressCount() {
		return $this->find('count', array(
			'conditions' => array(
				"{$this->alias}.status" => 2 //in_progress
			)
		));
	}

	/**
	* Get the run list of what needs to be ran.
	* @param boolean minimal return true
	* @return array set of queues to run.
	*/
	public function runList($minimal = true) {
		//If we have them in progress shortcut it.
		if ($this->inProgressCount() >= QueueUtil::getConfig('limit')) {
			return array();
		}
		$fields = $minimal ? array("{$this->alias}.id") : array("{$this->alias}.*");
		return $this->find('all', array(
			'conditions' => array(
				'OR' => array(
					array(
						'AND' => array(
							"{$this->alias}.execute" => null,
							"{$this->alias}.status" => 1 //queued
						)
					),
					array(
						'AND' => array(
							"{$this->alias}.execute <=" => date('Y-m-d'),
							"{$this->alias}.status" => 1//queued
						)
					)
				)
			),
			'fields' => $fields,
			'limit' => QueueUtil::getConfig('limit'),
			'order' => array("{$this->alias}.priority ASC")
		));
	}

	/**
	* Actually run the queue
	* @param string uuid
	* @return boolean success
	*/
	public function run($id = null) {
		if ($id) {
			$this->id = $id;
		}
		if (!$this->exists()) {
			return $this->__errorAndExit("Queue {$this->id} not found.");
		}
		$this->__setInProgress($this->id);
		$data = $this->read();
		switch ($data[$this->alias]['type']) {
			case 1:
				$retval = $this->__runModelQueue($data);
				break;
			case 2:
				$retval = $this->__runShellQueue($data);
				break;
			case 3:
				$retval = $this->__runUrlQueue($data);
				break;
			case 4:
				$retval = $this->__runPhpCmdQueue($data);
				break;
			case 5:
				$retval = $this->__runShellCmdQueue($data);
				break;
			default:
				$this->__setToPaused($this->id);
				throw new Exception("Unknown Type");
		}
		$this->__setFinished($this->id, $retval['result']);
		return $retval['success'];
	}

	/**
	* Process the queue, this is the entry point of the shell and cron
	* @return boolean success
	*/
	public function process() {
		$queues = $this->runList();
		if (empty($queues)) {
			return true;
		}
		$retval = true;
		foreach ($queues as $queue) {
			if (!$this->run($queue[$this->alias]['id'])) {
				$retval = false;
			}
		}
		return $retval;
	}

	/**
	* Generate filter conditions for filter search
	* @param string filter
	* @param string pre character for search (default '') optional '%'
	*/
	public function generateFilterConditions($filter = null, $pre = '') {
		$conditions = parent::generateFilterConditions($filter, $pre);
		foreach ($this->_statuses as $key => $name) {
			if (strtolower($filter) == $name) {
				$conditions['OR']["{$this->alias}.status"] = $key;
				unset($conditions['OR']["{$this->alias}.status LIKE"]);
			}
		}
		foreach ($this->_types as $key => $name) {
			if (strtolower($filter) == $name) {
				$conditions['OR']["{$this->alias}.type"] = $key;
				unset($conditions['OR']["{$this->alias}.type LIKE"]);
			}
		}
	}

	/**
	* Wrapper for getUserId, so we can mock this for testing
	* @return mixed result of AuthComponent::user('id');
	*/
	public function getCurrentUser($field) {
		App::uses('AuthComponent','Controller/Component');
		return AuthComponent::user($field);
	}

	/**
	* Set and error and return false
	* @param string message
	* @return false
	* @access private
	*/
	private function __errorAndExit($message) {
		$this->errors[$this->id][] = $message;
		return false;
	}

	/**
	* Set and error and return false
	* @param string message
	* @return false
	* @access private
	*/
	private function __clearErrors() {
		$this->errors = array();
	}

	/**
	* run the actual model command
	* @param queue data
	* @return array of result and success
	* @access private
	*/
	private function __runModelQueue($data){
		$retval = array(
			'success' => false,
			'result' => null
		);
		if (isset($data[$this->alias]['command'])) {
			list($pluginmodel, $function) = explode('::',$data[$this->alias]['command'], 2);
			list($plugin, $model) = pluginSplit($pluginmodel);
			if (!empty($plugin)) {
				App::uses($model, "$plugin.Model");
			} else {
				App::uses($model, "Model");
			}
			$Model = ClassRegistry::init($pluginmodel);
			$command = "\$retval['result'] = \$Model->$function;";
			@eval($command);
			if ($retval['result'] !== false) {
				$retval['success'] = true;
			}
		}
		return $retval;
	}

	/**
	* run the actual shell command
	* @param queue data
	* @return array of result and success
	* @access private
	*/
	private function __runShellQueue($data){
		$retval = array(
			'success' => false,
			'result' => null
		);
		if (isset($data[$this->alias]['command'])) {
			if (!$this->Shell) {
				App::uses('Shell','Console');
				App::uses('ShellDispatcher','Console');
				$this->Shell = new Shell();
			}
			$retval['result'] = $this->Shell->dispatchShell($data[$this->alias]['command']);
			if ($retval['result'] !== false) {
				$retval['success'] = true;
			}
		}
		return $retval;
	}
	/**
	* run the actual url command
	* @param queue data
	* @return array of result and success
	* @access private
	*/
	private function __runUrlQueue($data){
		$retval = array(
			'success' => false,
			'result' => null
		);
		if (isset($data[$this->alias]['command'])) {
			$retval['result'] = $this->requestAction($data[$this->alias]['command'], array('return' => true));
			if ($retval['result'] !== false) {
				$retval['success'] = true;
			}
		}
		return $retval;
	}
	/**
	* run the actual php_cmd command
	* @param queue data
	* @return array of result and success
	* @access private
	*/
	private function __runPhpCmdQueue($data){
		$retval = array(
			'success' => false,
			'result' => null
		);
		if (isset($data[$this->alias]['command'])) {
			$cmd = $data[$this->alias]['command'];
			$command = "\$retval['result'] = $cmd;";
			@eval($command);
			if ($retval['result'] !== false) {
				$retval['success'] = true;
			}
		}
		return $retval;
	}
	/**
	* run the actual shell_cmd command
	* @param queue data
	* @return array of result and success
	* @access private
	*/
	private function __runShellCmdQueue($data){
		$retval = array(
			'success' => false,
			'result' => null
		);
		if (isset($data[$this->alias]['command'])) {
			$retval['result'] = shell_exec($data[$this->alias]['command']);
			if ($retval['result'] !== false) {
				$retval['success'] = true;
			}
		}
		return $retval;
	}

	/**
	* Set the current queue to inprogress
	* @param string id (optional)
	* @return boolean success
	*/
	private function __setInProgress($id = null) {
		if ($id) {
			$this->id = $id;
		}
		if (!$this->exists()) {
			return $this->__errorAndExit("Queue {$this->id} not found.");
		}
		return $this->saveField('status', 2);
	}

	/**
	* Set the current queue to paused
	* @param string id (optional)
	* @return boolean success
	*/
	private function __setToPaused($id = null) {
		if ($id) {
			$this->id = $id;
		}
		if (!$this->exists()) {
			return $this->__errorAndExit("Queue {$this->id} not found.");
		}
		return $this->saveField('status', 4);
	}

	/**
	* Set the current queue to finished
	* @param string id (optional)
	* @param mixed result to save back
	* @return boolean success
	*/
	private function __setFinished($id = null, $result = null) {
		if ($id) {
			$this->id = $id;
		}
		if (!$this->exists()) {
			return $this->__errorAndExit("Queue {$this->id} not found.");
		}
		$this->saveField('status', 3);
		$this->saveField('result', json_encode($result));
		$this->saveField('executed',$this->str2datetime());
		return true;
	}
}
