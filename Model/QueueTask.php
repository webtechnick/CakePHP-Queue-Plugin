<?php
App::uses('QueueAppModel', 'Queue.Model');
class QueueTask extends QueueAppModel {

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
		'QueueTask.command','QueueTask.id','QueueTask.status','QueueTask.type'
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
		5 => 'shell_cmd',
	);
	
	/**
	* Placeholder for shell
	*/
	public $Shell = null;
	
	/**
	* Construct to load config setting if we have cache
	*/
	public function __construct($id = false, $table = null, $ds = null) {
		if (QueueUtil::getConfig('cache')) {
			QueueUtil::configCache();
		}
		return parent::__construct($id, $table, $ds);
	}

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
		if (!isset($this->data[$this->alias]['type'])) {
			$this->invalidate('type', 'type must be present to validate command');
			return false;
		}
		switch ($this->data[$this->alias]['type']) {
			case 1: //Model must have :: and ) as last character.
				if (strpos($field['command'], '::') === false || substr($field['command'], -1) != ')') {
					$this->invalidate('command', 'Please use Model Syntax:  \'SomeModel::action()\'  \'Plugin.SomeModel::action("param","param")\'');
					return false;
				}
				break;
			case 2: //Shell must not have whole word 'cake' in front.
				$nostrings = array('cake','./cake','Console/cake');
				foreach ($nostrings as $string) {
					if (strpos($field['command'], $string) === 0) {
						$this->invalidate('command', 'Specify shell commands as though using dispatchShell string:  \'Plugin.SomeShell command param1 param2\'');
						return false;
					}
				}
				break;
			case 3: //url must have a / in it
				if (strpos($field['command'], '/') === false) {
					$this->invalidate('command', 'Url must contain a /:  \'/path/to/action\' \'http://example.com/path/to/action\'');
					return false;
				}
				break;
			case 4: //php_command basically can't be empty.
				if (empty($field['command'])) {
					$this->invalidate('command', 'PhpCmd must not be empty:  \'5 + 7\'');
					return false;
				}
				break;
			case 5: //shell command basically can't be empty.
				if (empty($field['command'])) {
					$this->invalidate('command', 'ShellCmd must not be empty:  \'echo "hello" && echo "world"\'');
					return false;
				}
				break;
			default: //we shouldn't get here, something went really wrong if we did but definately don't want to return true if we do.
				$this->invalidate('command', 'Unknown Type, cannot validate command');
				return false;
		}
		return true;
	}

	/**
	* Validataion of Type Allowed, based on configuration app/Config/queue.php
	* @param array field
	* @return boolean if valid
	*/
	public function allowedType($field) {
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
	* Convience function utilized by Queue::add() library
	* @param string command
	* @param mixed type (string or int)
	* @param array of options
	*  - start = strtotime parsable string of when the task should be executed. (default null).
	*            if left null, as soon as possible will be assumed.
	*  - priority = the priority of the task, a way to Cut in line. (default 100)
	* @return boolean success
	*/
	public function add($command, $type, $options = array()) {
		$options = array_merge(array(
			'start' => null,
			'priority' => 100
		), (array) $options);

		if (!$this->isDigit($type)) {
			$type = $this->__findType($type);
		}

		$execute = $options['start'];
		if ($options['start'] !== null) {
			$execute = $this->str2datetime(($options['start']));
		}

		$data = array(
			'priority' => $options['priority'],
			'command' => $command,
			'type' => $type,
			'execute' => $execute
		);
		return $this->save($data);
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
	* Generate the list of next 10 in queue.
	*/
	public function next($limit = 10, $minimal = true, $use_cache = true) {
		$cache_key = 'next_' . $limit . '_' . $minimal;
		$cache = QueueUtil::readCache($cache_key);
		if ($use_cache && $cache !== false) { //we might have cached an empty set, which is OK.
			return $cache;
		}
		$cpu = QueueUtil::currentCpu();
		$hour = date('G');
		$day = date('w');
		$fields = $minimal ? array("{$this->alias}.id") : array("{$this->alias}.*");
		//Set of conditions in order
		$conditions = array(
			array( //Look for restricted by hour, day and cpu usage. order by priority with limit - current retval
				"{$this->alias}.is_restricted" => true,
				"{$this->alias}.status" => 1,
				"{$this->alias}.hour" => $hour,
				"{$this->alias}.day" => $day,
				"{$this->alias}.cpu_limit >=" => $cpu,
			),
			array( //Look for restricted by hour OR day and cpu usage. order by priority with limit - current retval
				"{$this->alias}.is_restricted" => true,
				"{$this->alias}.status" => 1,
				'OR' => array(
					"{$this->alias}.hour" => $hour,
					"{$this->alias}.day" => $day,
				),
				"{$this->alias}.cpu_limit >=" => $cpu,
			),
			array( //Look for restricted by hour and day. order by priority with limit - current retval
				"{$this->alias}.is_restricted" => true,
				"{$this->alias}.status" => 1,
				"{$this->alias}.hour" => $hour,
				"{$this->alias}.day" => $day,
			),
			array( //Look for restricted by day and cpu.
				"{$this->alias}.is_restricted" => true,
				"{$this->alias}.status" => 1,
				"{$this->alias}.cpu_limit >=" => $cpu,
				"{$this->alias}.day" => $day,
			),
			array( //Look for restricted by hour and cpu.
				"{$this->alias}.is_restricted" => true,
				"{$this->alias}.status" => 1,
				"{$this->alias}.cpu_limit >=" => $cpu,
				"{$this->alias}.hour" => $hour,
			),
			array( //Look for restricted by cpu.
				"{$this->alias}.is_restricted" => true,
				"{$this->alias}.status" => 1,
				"{$this->alias}.cpu_limit >=" => $cpu,
			),
			array( //Unrestricted
				"{$this->alias}.is_restricted" => false,
				"{$this->alias}.status" => 1,
			)
		);
		
		$retval = array();
		foreach ($conditions as $condition) {
			$current_count = count($retval);
			if ($current_count >= $limit) {
				break;
			}
			$new_limit = $limit - $current_count;
			$result = $this->find('all', array(
				'limit' => $new_limit,
				'order' => array("{$this->alias}.priority ASC"),
				'fields' => $fields,
				'conditions' => $condition
			));
			if (!empty($result)) {
				$retval = array_merge($retval, $result);
			}
		}
		QueueUtil::writeCache($cache_key, $retval);
		return $retval;
	}

	/**
	* Returns a list to run.
	* @return array set of queues to run.
	*/
	public function runList($minimal = true) {
		$limit = QueueUtil::getConfig('limit');
		$in_progress = $this->inProgressCount(); 
		//If we have them in progress shortcut it.
		if ($in_progress >= $limit) {
			return array();
		}
		return $this->next($limit - $in_progress, $minimal);
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
			return $this->__errorAndExit("QueueTask {$this->id} not found.");
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
	* Archive this current QueueTask into QueueTaskLogs table
	* @param string uuid id
	* @return boolean success
	*/
	public function archive($id = null) {
		if ($id) {
			$this->id = $id;
		}
		if (!$this->exists()) {
			return $this->__errorAndExit("QueueTask {$this->id} not found.");
		}
		$data = $this->read();
		if ($data[$this->alias]['status'] != 3) { //Finished
			return false;
		}
		if (!ClassRegistry::init('Queue.QueueTaskLog')->save($data)){
			return false;
		}
		return $this->delete($this->id);
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
	private function __runModelQueue($data) {
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
	private function __runShellQueue($data) {
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
	private function __runUrlQueue($data) {
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
	private function __runPhpCmdQueue($data) {
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
	private function __runShellCmdQueue($data) {
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
			return $this->__errorAndExit("QueueTask {$this->id} not found.");
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
			return $this->__errorAndExit("QueueTask {$this->id} not found.");
		}
		return $this->saveField('status', 4);
	}

	/**
	* Set the current queue to finished
	* Will archive the task after execution
	* @param string id (optional)
	* @param mixed result to save back
	* @return boolean success
	*/
	private function __setFinished($id = null, $result = null) {
		if ($id) {
			$this->id = $id;
		}
		if (!$this->exists()) {
			return $this->__errorAndExit("QueueTask {$this->id} not found.");
		}
		$this->saveField('status', 3);
		$this->saveField('result', json_encode($result));
		$this->saveField('executed',$this->str2datetime());
		if (QueueUtil::getConfig('archiveAfterExecute')) {
			$this->archive($this->id);
		}
		return true;
	}

	/**
	* Find the type int by a string
	* @param string type (model, shell, php_cmd, etc...)
	* @return mixed int of correct type or false if invalid.
	*/
	private function __findType($stringType) {
		$stringType = strtolower($stringType);
		$type = array_search($stringType, $this->_types);
		if ($type !== false) {
			return $type;
		}
		return false;
	}

	/**
	* Find the status int by a string
	* @param string status (queued, in_progress, etc..)
	* @return mixed int of correct status or false if invalid.
	*/
	private function __findStatus($stringStatus) {
		$stringStatus = strtolower($stringStatus);
		$status = array_search($stringStatus, $this->_statuses);
		if ($status !== false) {
			return $status;
		}
		return false;
	}
}
