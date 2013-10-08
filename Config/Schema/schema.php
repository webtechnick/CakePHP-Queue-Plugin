<?php 
class QueueSchema extends CakeSchema {

	public function before($event = array()) {
		return true;
	}

	public function after($event = array()) {
	}

	public $queue_task_logs = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 36, 'key' => 'primary', 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'user_id' => array('type' => 'biginteger', 'null' => true, 'default' => null, 'length' => 22, 'comment' => 'user_id of who created/modified this queue. optional'),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => null, 'key' => 'index'),
		'modified' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'executed' => array('type' => 'datetime', 'null' => true, 'default' => null, 'key' => 'index', 'comment' => 'datetime when executed.'),
		'scheduled' => array('type' => 'datetime', 'null' => true, 'default' => null, 'key' => 'index', 'comment' => 'When the task is scheduled. if null as soon as possible. Otherwise it will be first on list if it\'s the highest scheduled.'),
		'scheduled_end' => array('type' => 'datetime', 'null' => true, 'default' => null, 'key' => 'index', 'comment' => 'If we go past this time, don\'t execute. We need to reschedule based on reschedule.'),
		'reschedule' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 50, 'collate' => 'utf8_general_ci', 'comment' => 'strtotime parsable addition to scheduled until in future if window is not null.', 'charset' => 'utf8'),
		'start_time' => array('type' => 'biginteger', 'null' => true, 'default' => null, 'length' => 22, 'comment' => 'microtime start of execution.'),
		'end_time' => array('type' => 'biginteger', 'null' => true, 'default' => null, 'length' => 22, 'comment' => 'microtime end of execution.'),
		'cpu_limit' => array('type' => 'integer', 'null' => true, 'default' => null, 'length' => 3, 'key' => 'index', 'comment' => 'percent limit of cpu to execute. (95 = less than 95% cpu usage)'),
		'is_restricted' => array('type' => 'boolean', 'null' => false, 'default' => '0', 'key' => 'index', 'comment' => 'will be 1 if hour, day, or cpu_limit are not null.'),
		'priority' => array('type' => 'integer', 'null' => false, 'default' => '100', 'length' => 4, 'key' => 'index', 'comment' => 'priorty, lower the number, the higher on the list it will run.'),
		'status' => array('type' => 'integer', 'null' => false, 'default' => '1', 'length' => 2, 'key' => 'index', 'comment' => '1:queued,2:inprogress,3:finished,4:paused'),
		'type' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 2, 'key' => 'index', 'comment' => '1:model,2:shell,3:url,4:php_cmd,5:shell_cmd'),
		'command' => array('type' => 'text', 'null' => false, 'default' => null, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'result' => array('type' => 'text', 'null' => true, 'default' => null, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'indexes' => array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
			'status' => array('column' => 'status', 'unique' => 0),
			'type' => array('column' => 'type', 'unique' => 0),
			'created' => array('column' => 'created', 'unique' => 0),
			'priority' => array('column' => 'priority', 'unique' => 0),
			'is_restricted' => array('column' => 'is_restricted', 'unique' => 0),
			'cpu_limit' => array('column' => 'cpu_limit', 'unique' => 0),
			'executed' => array('column' => 'executed', 'unique' => 0),
			'scheduled' => array('column' => 'scheduled', 'unique' => 0),
			'scheduled_end' => array('column' => 'scheduled_end', 'unique' => 0)
		),
		'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'MyISAM')
	);

	public $queue_tasks = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 36, 'key' => 'primary', 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'user_id' => array('type' => 'biginteger', 'null' => true, 'default' => null, 'length' => 22, 'comment' => 'user_id of who created/modified this queue. optional'),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => null, 'key' => 'index'),
		'modified' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'executed' => array('type' => 'datetime', 'null' => true, 'default' => null, 'key' => 'index', 'comment' => 'datetime when executed.'),
		'scheduled' => array('type' => 'datetime', 'null' => true, 'default' => null, 'key' => 'index', 'comment' => 'When the task is scheduled. if null as soon as possible. Otherwise it will be first on list if it\'s the highest scheduled.'),
		'scheduled_end' => array('type' => 'datetime', 'null' => true, 'default' => null, 'key' => 'index', 'comment' => 'If we go past this time, don\'t execute. We need to reschedule based on reschedule.'),
		'reschedule' => array('type' => 'string', 'null' => true, 'default' => null, 'length' => 50, 'collate' => 'utf8_general_ci', 'comment' => 'strtotime parsable addition to scheduled until in future if window is not null.', 'charset' => 'utf8'),
		'start_time' => array('type' => 'biginteger', 'null' => true, 'default' => null, 'length' => 22, 'comment' => 'microtime start of execution.'),
		'end_time' => array('type' => 'biginteger', 'null' => true, 'default' => null, 'length' => 22, 'comment' => 'microtime end of execution.'),
		'cpu_limit' => array('type' => 'integer', 'null' => true, 'default' => null, 'length' => 3, 'key' => 'index', 'comment' => 'percent limit of cpu to execute. (95 = less than 95% cpu usage)'),
		'is_restricted' => array('type' => 'boolean', 'null' => false, 'default' => '0', 'key' => 'index', 'comment' => 'will be 1 if hour, day, or cpu_limit are not null.'),
		'priority' => array('type' => 'integer', 'null' => false, 'default' => '100', 'length' => 4, 'key' => 'index', 'comment' => 'priorty, lower the number, the higher on the list it will run.'),
		'status' => array('type' => 'integer', 'null' => false, 'default' => '1', 'length' => 2, 'key' => 'index', 'comment' => '1:queued,2:inprogress,3:finished,4:paused'),
		'type' => array('type' => 'integer', 'null' => false, 'default' => null, 'length' => 2, 'key' => 'index', 'comment' => '1:model,2:shell,3:url,4:php_cmd,5:shell_cmd'),
		'command' => array('type' => 'text', 'null' => false, 'default' => null, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'result' => array('type' => 'text', 'null' => true, 'default' => null, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'indexes' => array(
			'PRIMARY' => array('column' => 'id', 'unique' => 1),
			'status' => array('column' => 'status', 'unique' => 0),
			'type' => array('column' => 'type', 'unique' => 0),
			'created' => array('column' => 'created', 'unique' => 0),
			'priority' => array('column' => 'priority', 'unique' => 0),
			'is_restricted' => array('column' => 'is_restricted', 'unique' => 0),
			'cpu_limit' => array('column' => 'cpu_limit', 'unique' => 0),
			'executed' => array('column' => 'executed', 'unique' => 0),
			'scheduled' => array('column' => 'scheduled', 'unique' => 0),
			'scheduled_end' => array('column' => 'scheduled_end', 'unique' => 0)
		),
		'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'MyISAM')
	);

}
