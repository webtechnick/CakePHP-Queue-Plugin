<?php
/**
 * QueueTaskFixture
 *
 */
class QueueTaskFixture extends CakeTestFixture {

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => null, 'length' => 36, 'key' => 'primary', 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'user_id' => array('type' => 'biginteger', 'null' => true, 'default' => null, 'length' => 22, 'comment' => 'user_id of who created/modified this queue. optional'),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => null, 'key' => 'index'),
		'modified' => array('type' => 'datetime', 'null' => true, 'default' => null),
		'executed' => array('type' => 'datetime', 'null' => true, 'default' => null, 'comment' => 'datetime when executed.'),
		'execute' => array('type' => 'datetime', 'null' => true, 'default' => null, 'key' => 'index', 'comment' => 'datetime when to execute, if null do it as soon as possible'),
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
			'priority' => array('column' => 'priority', 'unique' => 0)
		),
		'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'MyISAM')
	);

/**
 * Records
 *
 * @var array
 */
	public $records = array(
		array(
			'id' => '524b0c44-a3a0-4956-8428-dc3ee017215a',
			'user_id' => null,
			'created' => '2013-10-01 11:54:12',
			'modified' => '2013-10-01 11:54:12',
			'executed' => null,
			'execute' => null,
			'priority' => 100,
			'status' => 1,
			'type' => 1, //model
			'command' => 'SomeModel::action("param","param2")',
			'result' => '',
		),
		array(
			'id' => '524b0c44-a3a0-4956-8428-dc3ee017215b',
			'user_id' => null,
			'created' => '2013-10-01 11:54:12',
			'modified' => '2013-10-01 11:54:12',
			'executed' => null,
			'execute' => null,
			'priority' => 100,
			'status' => 1,
			'type' => 2, //shell
			'command' => 'Queue.SomeShell command param1 param2',
			'result' => '',
		),
		array(
			'id' => '524b0c44-a3a0-4956-8428-dc3ee017215c',
			'user_id' => null,
			'created' => '2013-10-01 11:54:12',
			'modified' => '2013-10-01 11:54:12',
			'executed' => null,
			'execute' => null,
			'priority' => 100,
			'status' => 1,
			'type' => 3, //shell
			'command' => '/some/url/to/an/action',
			'result' => '',
		),
		array(
			'id' => '524b0c44-a3a0-4956-8428-dc3ee017215d',
			'user_id' => null,
			'created' => '2013-10-01 11:54:12',
			'modified' => '2013-10-01 11:54:12',
			'executed' => null,
			'execute' => null,
			'priority' => 100,
			'status' => 1,
			'type' => 4, //php_command
			'command' => '2 + 5',
			'result' => '',
		),
		array(
			'id' => '524b0c44-a3a0-4956-8428-dc3ee017215e',
			'user_id' => null,
			'created' => '2013-10-01 11:54:12',
			'modified' => '2013-10-01 11:54:12',
			'executed' => null,
			'execute' => null,
			'priority' => 100,
			'status' => 1,
			'type' => 5, //shell_cmd
			'command' => 'echo "hello" && echo "world"',
			'result' => '',
		),
	);

}
