<?php
/**
* Utility Class to manage The Queue.
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
	* Quick add feature to QueueTask. This is how the majority of queues will be added
	* @param string command
	* @param string type
	* @param array of options
	*  - hour = strtotime hour to execute. (11 pm | 23)  (default null)
	*  - day  = strtotime day to execute. (Sunday | sun | 0) (default null)
	*  - cpu_limit = int 0-100 percent threshold for when to execute (95 will execute will less than 95% cpu load (default null).
	*            if left null, as soon as possible will be assumed.
	*  - priority = the priority of the task, a way to Cut in line. (default 100)
	*/
	public static function add($command, $type = 'model', $options = array()) {
		self::loadQueueTask();
		return self::$QueueTask->add($command, $type, $options);
	}

	/**
	* Deletes a task from the queue.
	* @param string uuid
	* @return boolean success
	*/
	public static function delete($id = null) {
		self::loadQueuetask();
		$retval = self::$QueueTask->delete($id);
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
	* List next X upcomming tasks.
	* @param int limit
	*/
	public static function next($limit = 10) {
		self::loadQueueTask();
		return self::$QueueTask->next($limit, false);
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
	* Returns the tasks in progress.
	* @return array of tasks currently in progress
	*/
	public static function inProgress() {
		self::loadQueueTask();
		return self::$Queuetask->findInProgress();
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
}