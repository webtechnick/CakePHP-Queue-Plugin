<?php
/**
* QueueTask model, these are tasks that are put into the Queue.
* This model also handles the queue itself but `DO NOT` interface
* with it directly.  Use the Queue.Lib to interface with your queue.
*
* @example
 App::uses('Queue', 'Queue.Lib');
 Queue::add();              //adds a task to the queue.
 Queue::remove();           //safely remove a task from the queue.
 Queue::next('10');         //see next X items in the queue to execute
 Queue::inProgress();       //See what tasks are currently in progress
 Queue::inProgressCount();  //Get count of how many tasks are currently running
 Queue::process();          //Process the Queue.

 //Please refer to Documentation in Queue.Lib for how to add items propery to the queue.
*
* @author Nick
* @since 1.0
* @license MIT
*/
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
		'scheduled' => array(
			'datetime' => array(
				'rule' => array('datetime'),
				'message' => 'Must be a valid date time stamp.',
				'allowEmpty' => true
			)
		),
		'scheduled_end' => array(
			'validWindow' => array(
				'rule' => array('datetime'),
				'message' => 'Must be a valid datetime stamp, or null.',
				'allowEmpty' => true
			)
		),
		'reschedule' => array(
			'validReschedule' => array(
				'rule' => array('validReschedule'),
				'message' => 'Cannot be empty when schedule_end is not null.  Must be a strtotime parsable string.',
			)
		),
		'cpu_limit' => array(
			'validCpu' => array(
				'rule' => array('range', -1, 101),
				'message' => 'Cpu Percent Limit must be between 0 and 100.',
				'allowEmpty' => true
			)
		),
		'is_restricted' => array(
			'boolean' => array(
				'rule' => array('boolean'),
				'message' => 'is_required must be a 0 or 1'
			)
		),
		'command' => array(
			'notBlank' => array(
				'rule' => array('notBlank'),
				'message' => 'No command. Please specify.',
			),
			'validCommand' => array(
				'rule' => array('validCommand'),
				'message' => 'Command not valid for type.'
			)
		),
	);

	public $virtualFields = array(
		'execution_time' => 'QueueTask.end_time - QueueTask.start_time'
	);

	/**
	 * Filter fields
	 * @var array
	 */
	public $searchFields = array(
		'QueueTask.command','QueueTask.id','QueueTask.status','QueueTask.type'
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
	* Cannot be null unless scheduled_end is also null
	* @param arry field
	* @return boolean if valid
	*/
	public function validReschedule($field) {
		if (isset($this->data[$this->alias]['scheduled_end']) && !empty($this->data[$this->alias]['scheduled_end']) && empty($field['reschedule'])) {
			return false;
		}
		return true;
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
		return in_array($field['type'], $allowedTypes);
	}
	/**
	* This converts the admin save to
	*/
	public function adminSave($data = array()) {
		$options = $data['QueueTask'];
		$command = $data['QueueTask']['command'];
		$type = $data['QueueTask']['type'];
		return $this->add($command, $type, $options);
	}
	/**
	* Convience function utilized by Queue::add() library
	* @param string command
	* @param string type
	* @param array of options
	*  - start     = strtotime datetime to execute. Will assume future date. (11 pm Sunday)  (default null)
	*  - end       = strtotime datetime of window allowed to execute (5 am Monday) (default null)
	*  - reschedule = strtotime addition to scheduled to execute. (+1 day | +1 week) (default null)
	*  - cpu_limit = int 0-100 percent threshold for when to execute (95 will execute will less than 95% cpu load (default null).
	*            if left null, as soon as possible will be assumed.
	*  - priority = the priority of the task, a way to Cut in line. (default 100)
	*  - id = if provided, will update the record instead of creating a new one.
	* @return boolean success
	*/
	public function add($command, $type, $options = array()) {
		if (!$command || !$type) {
			return $this->__errorAndExit("Command and Type required to add Task to Queue.");
		}
		$options = array_merge(array(
			'id' => null,
			'start' => null,
			'end' => null,
			'reschedule' => null,
			'cpu_limit' => null,
			'cpu' => null,
			'priority' => 100,
			'scheduled' => null,
			'scheduled_end' => null,
		), (array) $options);
		$options['cpu_limit'] = $options['cpu'];

		if (!$this->isDigit($type)) {
			$type = $this->__findType($type);
		}

		if ($options['start'] !== null) {
			$options['scheduled'] = $this->str2datetime($options['start']);
		}

		if ($options['end'] !== null) {
			$options['scheduled_end'] = $this->str2datetime($options['end'], true);
		}

		$data = array(
			'id' => $options['id'],
			'priority' => $options['priority'],
			'command' => $command,
			'type' => $type,
			'scheduled' => $options['scheduled'],
			'scheduled_end' => $options['scheduled_end'],
			'reschedule' => $options['reschedule'],
			'cpu_limit' => $options['cpu_limit']
		);
		if (!empty($options['scheduled']) || !empty($options['cpu_limit'])) {
			$data['is_restricted'] = true;
		}
		$this->clear();
		return $this->save($data);
	}

	/**
	* Remove is a wrapper for delete that will check in progress status before
	* removing.
	* @param string uuid
	* @param boolean force - if true will bypass in progress check and delete task. (default false)
	* @throws Exception.
	* @return boolean
	*/
	public function remove($id = null, $force = false) {
		if ($id) {
			$this->id = $id;
		}
		if (!$this->exists()) {
			return $this->__errorAndExit("QueueTask {$this->id} not found.");
		}
		if (!$force && $this->field('status') == 2) { //In progress
			return $this->__errorAndExit("QueueTask {$this->id} is currently in progress.");
		}
		return $this->delete($id);
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
	* Find for view. Will search through this
	* table and queueTaskLog as well
	*/
	public function findForView($id = null) {
		$retval = $this->find('first', array(
			'conditions' => array(
				'QueueTask.id' => $id
			)
		));
		if (empty($retval)) {
			$log = ClassRegistry::init('Queue.QueueTaskLog')->find('first', array(
				'conditions' => array(
					'QueueTaskLog.id' => $id
				)
			));
			if (!empty($log)) {
				$retval = array(
					'QueueTask' => $log['QueueTaskLog']
				);
			}
		}
		return $retval;
	}
	/**
	* Generate the list of next 10 in queue.
	* @param int limit of how many to return for next in queue
	* @param boolean minimal fields returned
	* @param processing, if true only return true set of what needs to be executed next NOW
	* @return array of tasks in order of execution next.
	*/
	public function next($limit = 10, $minimal = true, $processing = true) {
		//If we don't have any queued in table just exit with empty set.
		if (!$this->hasAny(array("{$this->alias}.status" => 1))) {
			return array();
		}
		$cpu = QueueUtil::currentCpu();
		$now = $this->str2datetime();
		$fields = $minimal ? array("{$this->alias}.id") : array("{$this->alias}.*");
		//Set of conditions in order
		$conditions = array(
			array( //Look for restricted by scheduled and with a window with cpu
				"{$this->alias}.is_restricted" => true,
				"{$this->alias}.status" => 1,
				"{$this->alias}.scheduled <=" => $now,
				'OR' => array(
					array("{$this->alias}.scheduled_end >=" => $now),
					array("{$this->alias}.scheduled_end" => null),
				),
				"{$this->alias}.cpu_limit >=" => $cpu,
			),
			array( //Look for restricted by scheduled and with a window
				"{$this->alias}.is_restricted" => true,
				"{$this->alias}.status" => 1,
				"{$this->alias}.scheduled <=" => $now,
				'OR' => array(
					array("{$this->alias}.scheduled_end >=" => $now),
					array("{$this->alias}.scheduled_end" => null),
				),
			),
			array( //Look for restricted by cpu
				"{$this->alias}.is_restricted" => true,
				"{$this->alias}.status" => 1,
				"{$this->alias}.cpu_limit >=" => $cpu,
			),
			array( //Unrestricted
				"{$this->alias}.is_restricted" => false,
				"{$this->alias}.status" => 1,
			)
		);

		if (!$processing) {
			$conditions[] = array( //Future scheduled
				"{$this->alias}.is_restricted" => true,
				"{$this->alias}.status" => 1,
				"{$this->alias}.scheduled >=" => $now,
			);
		}

		$retval = array();
		foreach ($conditions as $condition) {
			$current_count = count($retval);
			if ($current_count >= $limit) {
				break;
			}
			$new_limit = $limit - $current_count;
			$result = $this->find('all', array(
				'limit' => $new_limit,
				'order' => array("{$this->alias}.scheduled ASC, {$this->alias}.priority ASC"),
				'fields' => $fields,
				'conditions' => $condition
			));
			if (!empty($result)) {
				$retval = array_merge($retval, $result);
			}
		}
		return $retval;
	}

	/**
	* Returns a list to run. Processing list
	* @return array set of queues to run.
	*/
	public function runList($minimal = true) {
		$limit = QueueUtil::getConfig('limit');
		$in_progress = $this->inProgressCount();
		//If we have them in progress shortcut it.
		if ($in_progress >= $limit) {
			return array();
		}
		return $this->next($limit - $in_progress, $minimal, true);
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
		QueueUtil::writeLog('Running Queue ID: ' . $this->id);
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
		if (!ClassRegistry::init('Queue.QueueTaskLog')->save($data['QueueTask'])) {
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
		if (!class_exists('AuthComponent')) {
			return null;
		}
		App::uses('AuthComponent','Controller/Component');
		return AuthComponent::user($field);
	}

	/**
	* Find tasks that are currently in progress
	* @return array of tasks in progress
	*/
	public function findInProgress() {
		return $this->find('all', array(
			'conditions' => array(
				"{$this->alias}.status" => 2 //in progress
			)
		));
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
		QueueUtil::writeLog('Starting Execution on Task: ' . $this->id);
		$this->saveField('start_time', microtime(true));
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
		QueueUtil::writeLog('Pausing Task: ' . $this->id);
		$this->saveField('end_time', microtime(true));
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
		$save_result = json_encode($result);
		$this->saveField('status', 3);
		$this->saveField('result', $save_result);
		$this->saveField('end_time', microtime(true));
		$this->saveField('executed',$this->str2datetime());
		QueueUtil::writeLog('Finished Execution on Task: ' . $this->id . "\nTook: " . $this->field('execution_time') . "\nResult:\n\n" . $save_result);
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

	/**
	* Reschedule a task based on reschedule
	* @param string uuid
	* @return boolean success
	*/
	private function __reschedule($id = null) {
		if ($id) {
			$this->id = $id;
		}
		if (!$this->exists()) {
			return $this->__errorAndExit("QueueTask {$this->id} not found.");
		}

		QueueUtil::writeLog('Rescheduling ' . $this->id . ' to ');
	}
}
