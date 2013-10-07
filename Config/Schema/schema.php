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
		'executed' => array('type' => 'datetime', 'null' => true, 'default' => null, 'comment' => 'datetime when executed.'),
		'execute' => array('type' => 'datetime', 'null' => true, 'default' => null, 'key' => 'index', 'comment' => 'datetime when to execute, if null do it as soon as possible'),
		'hour' => array('type' => 'integer', 'null' => true, 'default' => null, 'length' => 2, 'key' => 'index', 'comment' => 'hour of the day 0-23'),
		'day' => array('type' => 'integer', 'null' => true, 'default' => null, 'length' => 1, 'key' => 'index', 'comment' => '0-6 (0 = sunday)'),
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
			'execute' => array('column' => 'execute', 'unique' => 0),
			'created' => array('column' => 'created', 'unique' => 0),
			'priority' => array('column' => 'priority', 'unique' => 0),
			'hour' => array('column' => 'hour', 'unique' => 0),
			'day' => array('column' => 'day', 'unique' => 0),
			'is_restricted' => array('column' => 'is_restricted', 'unique' => 0),
			'cpu_limit' => array('column' => 'cpu_limit', 'unique' => 0)
		),
		'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'MyISAM')
	);

	public $queue_tasks = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 36, 'key' => 'primary', 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'user_id' => array('type' => 'biginteger', 'null' => true, 'default' => null, 'length' => 22, 'comment' => 'user_id of who created/modified this queue. optional'),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => null, 'key' => 'index'),
		'modified' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'executed' => array('type' => 'datetime', 'null' => true, 'default' => null, 'comment' => 'datetime when executed.'),
		'execute' => array('type' => 'datetime', 'null' => true, 'default' => null, 'key' => 'index', 'comment' => 'datetime when to execute, if null do it as soon as possible'),
		'hour' => array('type' => 'integer', 'null' => true, 'default' => null, 'length' => 2, 'key' => 'index', 'comment' => 'hour of the day 0-23'),
		'day' => array('type' => 'integer', 'null' => true, 'default' => null, 'length' => 1, 'key' => 'index', 'comment' => '0-6 (0 = sunday)'),
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
			'execute' => array('column' => 'execute', 'unique' => 0),
			'created' => array('column' => 'created', 'unique' => 0),
			'priority' => array('column' => 'priority', 'unique' => 0),
			'hour' => array('column' => 'hour', 'unique' => 0),
			'day' => array('column' => 'day', 'unique' => 0),
			'is_restricted' => array('column' => 'is_restricted', 'unique' => 0),
			'cpu_limit' => array('column' => 'cpu_limit', 'unique' => 0)
		),
		'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'MyISAM')
	);

}
