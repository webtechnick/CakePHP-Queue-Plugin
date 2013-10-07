<?php
/**
* This is simply a model to hold archived QueueTasks making the process quicker.
* This is populated by QueueTask::archive($id = null) function which is executed by the
* Shell command 
*   cake Queue.Queue archive
*/
App::uses('QueueAppModel', 'Queue.Model');
class QueueTaskLog extends QueueAppModel {
	/**
	 * Display field
	 * @var string
	 */
	public $displayField = 'type';
	
	public $virtualFields = array(
		'execution_time' => 'QueueTaskLog.end_time - QueueTaskLog.start_time'
	);
}
