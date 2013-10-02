<?php
App::uses('QueueAppModel', 'Queue.Model');
App::uses('AuthComponent','Controller/Component');
class Queue extends QueueAppModel {

/**
 * Display field
 *
 * @var string
 */
	public $displayField = 'type';

/**
 * Validation rules
 *
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
		),
	);

	/**
	 * Filter fields
	 *
	 * @var array
	 */
	public $searchFields = array(
		'Queue.command','Queue.id','Queue.status','Queue.type'
	);
	
	protected $_statuses = array(
		1 => 'queued',
		2 => 'in progress',
		3 => 'finished',
		4 => 'paused',
	);
	protected $_types = array(
		1 => 'model',
		2 => 'shell',
		3 => 'url',
		4 => 'php_cmd',
		5 => 'shell_cmd'
	);
	
	public function validType($field) {
		return isset($this->_types[$field['type']]);
	}
	
	public function validStatus($field) {
		return isset($this->_statuses[$field['status']]);
	}
	
	public function allowedType() {
		$allowedTypes = QueueUtil::getConfig('allowedTypes');
		return isset($allowedTypes[$field['type']]);
	}
	
	/**
	* Assign the user_id if we have one.
	*/
	public function beforeSave($options = array()) {
		if ($user_id = AuthComponent::user('id')) {
			$this->data[$this->alias]['user_id'] = $user_id;
		}
		return parent::beforeSave($options);
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
		$retval = false;
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
				throw new Exception("Unknown Type");
		}
		$this->__setFinished($this->id, $retval);
		return $retval;
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
	
	private function __errorAndExit($message) {
		$this->errors[] = $message;
		return false;
	}
	
	private function __runModelQueue($data){
		debug($data);
	}
	private function __runShellQueue($data){
	}
	private function __runUrlQueue($data){
	}
	private function __runPhpCmdQueue($data){
	}
	private function __runShellCmdQueue($data){
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
