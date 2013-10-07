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
	*  - start = strtotime parsable string of when the task should be executed. (default null).
	*            if left null, as soon as possible will be assumed.
	*  - priority = the priority of the task, a way to Cut in line. (default 100)
	*/
	public static function add($command, $type = 'model', $options = array()) {
		self::loadQueueTask();
		return self::$QueueTask->add($command, $type, $options);
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
	* List upcoming tasks.
	*/
	public static function show() {
		//TODO
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
	* Return the in progress count
	* @return int in progress count.
	*/
	public static function inProgressCount() {
		self::loadQueueTask();
		return self::$QueueTask->inProgressCount();
	}
	
	public static function loadQueueTask() {
		if (!self::$QueueTask) {
			App::uses('QueueTask','Queue.Model');
			self::$QueueTask = ClassRegistry::init('Queue.QueueTask');
		}
	}
}