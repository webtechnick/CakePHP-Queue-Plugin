<?php
/**
* Queue Class to manage The Queue.
* This class holds no real logic, is is more stubs to easily access
* all aspects of managing the queue and plugin through a single Interface.
*
* @author Nick Baker
* @since 1.0
* @license MIT
*/
App::uses('QueueUtil','Queue.Lib');
class Queue extends Object {
	/**
	* Placeholder for Task
	*/
	private static $QueueTask = null;
	/**
	* Placeholder for TaskLog
	*/
	private static $QueueTaskLog = null;

	/**
	* Quick add feature to QueueTask. This is how the majority of queues will be added
	* @param string command
	* @param string type
	* @param array of options
	*  - start     = strtotime datetime to execute. Will assume future date. (11 pm Sunday)  (default null)
	*  - end       = strtotime datetime of window allowed to execute (5 am Monday) (default null)
	*  - reschedule = strtotime addition to scheduled to execute. (+1 day | +1 week) (default null)
	*  - cpu      = int 0-100 percent threshold for when to execute (95 will execute will less than 95% cpu load (default null).
	*            if left null, as soon as possible will be assumed.
	*  - priority = the priority of the task, a way to Cut in line. (default 100)
	* @return boolean success
	*/
	public static function add($command, $type = 'model', $options = array()) {
		self::loadQueueTask();
		return self::$QueueTask->add($command, $type, $options);
	}

	/**
	* Deletes a task from the queue.
	* @param string uuid
	* @param boolean force - if true will bypass in progress check and delete task. (default false)
	* @return boolean success
	*/
	public static function remove($id = null, $force = false) {
		self::loadQueuetask();
		$retval = self::$QueueTask->remove($id, $force);
		QueueUtil::clearCache();
		return $retval;
	}
	
	/**
	* Run a task specifically.
	* @param string uuid
	* @return boolean success
	*/
	public static function run($id = null) {
		self::loadQueueTask();
		return self::$QueueTask->run($id);
	}

	/**
	* Return the queue from QueueTask or QueueTaskLog as an associative array
	* @param string uuid
	* @return mixed array of queue or false if not found.
	*/
	public static function findById($id = null) {
		self::loadQueueTask();
		if (self::$QueueTask->hasAny(array('QueueTask.id' => $id))) {
			return self::$QueueTask->findById($id);
		}
		self::loadQueueTaskLog();
		if (self::$QueueTaskLog->hasAny(array('QueueTaskLog.id' => $id))) {
			return self::$QueueTaskLog->findById($id);
		}
		return false;
	}
	/**
	* View the task as a string representation looks in QueueTask and QueueTaskLog
	* @param string uuid
	* @return string representation of task.
	*/
	public static function view($id = null) {
		self::loadQueueTask();
		if (self::$QueueTask->hasAny(array('QueueTask.id' => $id))) {
			return self::$QueueTask->niceString($id);
		}
		self::loadQueueTaskLog();
		if (self::$QueueTaskLog->hasAny(array('QueueTaskLog.id' => $id))) {
			return self::$QueueTaskLog->niceString($id);
		}
		return false;
	}
	/**
	* List next X upcomming tasks.
	* @param int limit
	*/
	public static function next($limit = 10) {
		self::loadQueueTask();
		return self::$QueueTask->next($limit, false, false);
	}
	
	/**
	* Process the Queue, runs the queue
	* @return boolean success
	*/
	public static function process() {
		self::loadQueueTask();
		return self::$QueueTask->process();
	}

	/**
	* Check the Queue, mark failed tasks
	* @return boolean success
	*/
	public static function check() {
		self::loadQueueTask();
		return self::$QueueTask->check();
	}

	/**
	* Returns the tasks in progress.
	* @return array of tasks currently in progress
	*/
	public static function inProgress() {
		self::loadQueueTask();
		return self::$QueueTask->findInProgress();
	}

	/**
	* Return the in progress count
	* @return int in progress count.
	*/
	public static function inProgressCount() {
		self::loadQueueTask();
		return self::$QueueTask->inProgressCount();
	}

	/**
	* Load the QueueTask Model instance
	*/
	public static function loadQueueTask() {
		if (!self::$QueueTask) {
			App::uses('QueueTask','Queue.Model');
			self::$QueueTask = ClassRegistry::init('Queue.QueueTask');
		}
	}
	/**
	* Load the QueueTask Model instance
	*/
	public static function loadQueueTaskLog() {
		if (!self::$QueueTaskLog) {
			App::uses('QueueTaskLog','Queue.Model');
			self::$QueueTaskLog = ClassRegistry::init('Queue.QueueTaskLog');
		}
	}
}