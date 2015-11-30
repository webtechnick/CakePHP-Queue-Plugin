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
}
