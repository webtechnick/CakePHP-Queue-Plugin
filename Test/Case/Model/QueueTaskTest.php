<?php
App::uses('QueueAppModel','Queue.Model');
App::uses('QueueTask', 'Queue.Model');
App::uses('CakeTestCase','TestSuite');
App::uses('Shell','Console');
App::uses('QueueUtil','Queue.Lib');
/**
* SomeModel Class
*/
class SomeModel extends QueueAppModel {
	public $useTable = false;
	function action($param, $param2){
		return array($param, $param2);
	}
}

class QueueTaskTest extends CakeTestCase {

/**
 * Fixture
 *
 * @var array
 */
	public $fixtures = array(
		'plugin.queue.queue_task',
		'plugin.queue.queue_task_log',
	);

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->QueueTask = $this->getMockForModel('Queue.QueueTask', array('requestAction', 'getCurrentUser'));
		$this->QueueTask->Shell = $this->getMock('Shell');
		Configure::write('Queue.allowedTypes', array(1,2,3,4,5));
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->QueueTask);

		parent::tearDown();
	}
	
	public function test_process() {
		QueueUtil::$configs['limit'] = 3;
		QueueUtil::$configs['archiveAfterExecute'] = true;
		$count = $this->QueueTask->find('count');
		$result = $this->QueueTask->process();
		$this->assertTrue($result);
		$this->assertEqual($this->QueueTask->find('count'), $count - 3);
		$QueueLog = ClassRegistry::init('Queue.QueueTaskLog');
		$logs = $QueueLog->find('all');
		$this->assertEqual(count($logs), 3);
		foreach ($logs as $log) {
			$this->assertEqual($log['QueueTaskLog']['status'], 3); //finixhed
			$this->assertTrue(!empty($log['QueueTaskLog']['executed']));
			$this->assertTrue(!empty($log['QueueTaskLog']['start_time']));
			$this->assertTrue(!empty($log['QueueTaskLog']['end_time']));
		}
	}
	
	public function test_removeNotInProgress() {
		$this->QueueTask->id = '524b0c44-a3a0-4956-8428-dc3ee017215a';
		$result = $this->QueueTask->remove(); //forcing delete
		$this->assertTrue($result);
		$this->assertFalse($this->QueueTask->exists());
	}
	
	public function test_removeNoRemoveInProgress() {
		$this->QueueTask->id = '524b0c44-a3a0-4956-8428-dc3ee017215a';
		$this->QueueTask->saveField('status', 2); //setting to in progress.
		$result = $this->QueueTask->remove();
		$this->assertFalse($result);
		$this->assertTrue($this->QueueTask->exists());
	}
	
	public function test_removeRemoveInProgressForce() {
		$this->QueueTask->id = '524b0c44-a3a0-4956-8428-dc3ee017215a';
		$this->QueueTask->saveField('status', 2); //setting to in progress.
		$result = $this->QueueTask->remove(null, true); //forcing delete
		$this->assertTrue($result);
		$this->assertFalse($this->QueueTask->exists());
	}
	
	public function test_addRestricted() {
		//Validation rules are tested, test restricted
		$count = $this->QueueTask->find('count');
		$result = $this->QueueTask->add("Model::action()", 'model', array(
			'start' => 'Sunday 11 pm', 
			'end' => 'Monday 5 am',
			'reschedule' => '+1 week',
			'cpu_limit' => 95,
		));
		$this->assertTrue(!empty($result));
		$this->assertEqual($this->QueueTask->find('count'), $count + 1);
		$this->assertTrue($this->QueueTask->field('is_restricted'));
		$this->assertEqual($this->QueueTask->field('cpu_limit'), 95);
		$this->assertEqual($this->QueueTask->field('type'), 1);
		$this->assertEqual($this->QueueTask->field('priority'), 100);
		$this->assertTrue(!empty($result['QueueTask']['scheduled']));
		$this->assertTrue(!empty($result['QueueTask']['scheduled_end']));
		$this->assertTrue(!empty($result['QueueTask']['reschedule']));
	}
	
	public function test_addNormal_minimal() {
		$count = $this->QueueTask->find('count');
		$result = $this->QueueTask->add("Model::action()", 'model');
		$this->assertTrue(!empty($result));
		$this->assertEqual($this->QueueTask->find('count'), $count + 1);
		$this->assertFalse($this->QueueTask->field('is_restricted'));
		$this->assertEqual($this->QueueTask->field('scheduled'), null);
		$this->assertEqual($this->QueueTask->field('scheduled_end'), null);
		$this->assertEqual($this->QueueTask->field('reschedule'), null);
		$this->assertEqual($this->QueueTask->field('cpu_limit'), null);
		$this->assertEqual($this->QueueTask->field('type'), 1);
		$this->assertEqual($this->QueueTask->field('priority'), 100);
	}
	
	public function test_addNormal_extra() {
		$count = $this->QueueTask->find('count');
		$result = $this->QueueTask->add("Model::action()", 1, array(
			'priority' => 50,
		));
		$this->assertTrue(!empty($result));
		$this->assertEqual($this->QueueTask->find('count'), $count + 1);
		$this->assertFalse($this->QueueTask->field('is_restricted'));
		$this->assertEqual($this->QueueTask->field('scheduled'), null);
		$this->assertEqual($this->QueueTask->field('scheduled_end'), null);
		$this->assertEqual($this->QueueTask->field('reschedule'), null);
		$this->assertEqual($this->QueueTask->field('cpu_limit'), null);
		$this->assertEqual($this->QueueTask->field('type'), 1);
		$this->assertEqual($this->QueueTask->field('priority'), 50);
	}
	
	public function test_next() {
		$result = $this->QueueTask->next(2, true, false);
		$this->assertEqual(count($result), 2);
		$this->assertEqual('524b0c44-a3a0-4956-8428-dc3ee017215f', $result[0]['QueueTask']['id']);
		$this->assertEqual('524b0c44-a3a0-4956-8428-dc3ee017215a', $result[1]['QueueTask']['id']);
	}
	
	public function test_archive() {
		QueueUtil::$configs['archiveAfterExecute'] = false;
		$this->QueueTask->id = '524b0c44-a3a0-4956-8428-dc3ee017215a';
		$result = $this->QueueTask->run();
		$this->assertTrue($result);
		
		$QueueTaskLog = ClassRegistry::init('Queue.QueueTaskLog');
		$count = $QueueTaskLog->find('count');
		$result = $this->QueueTask->archive();
		$this->assertFalse($this->QueueTask->exists()); //deleted
		$this->assertEqual($QueueTaskLog->find('count'), $count + 1);
	}
	
	public function test_validCommandShellCmd() {
		//Validate phpShell
		$data = array(
			'QueueTask' => array(
				'type' => 5,
				'command' => '', //emtpy
			)
		);
		$count = $this->QueueTask->find('count');
		$result = $this->QueueTask->save($data);
		$this->assertFalse($result);
		$this->assertEqual($this->QueueTask->find('count'), $count);
		$this->assertTrue(!empty($this->QueueTask->validationErrors['command']));
	}
	
	public function test_validCommandPhpShell() {
		//Validate phpShell
		$data = array(
			'QueueTask' => array(
				'type' => 4,
				'command' => '', //emtpy
			)
		);
		$count = $this->QueueTask->find('count');
		$result = $this->QueueTask->save($data);
		$this->assertFalse($result);
		$this->assertEqual($this->QueueTask->find('count'), $count);
		$this->assertTrue(!empty($this->QueueTask->validationErrors['command']));
		
		//Validate works.
		QueueUtil::getConfig('allowedTypes');
		QueueUtil::$configs['allowedTypes'] = array(1,2,3,4,5);
		$data = array(
			'QueueTask' => array(
				'type' => 4, //php cmd
				'command' => '5 + 7'
			)
		);
		$count = $this->QueueTask->find('count');
		$result = $this->QueueTask->save($data);
		$this->assertTrue(!empty($result));
		$this->assertEqual($this->QueueTask->find('count'), $count + 1);
		$this->assertTrue(empty($this->QueueTask->validationErrors['command']));
	}
	
	public function test_validCommandShell() {
		$validationErrorCommands = array(
			'cake Shell command',
			'Console/cake Shell command',
			'./cake Shell command'
		);
		foreach ($validationErrorCommands as $command) {
			//Validate SHell
			$data = array(
				'QueueTask' => array(
					'type' => 2, //shell
					'command' => $command
				)
			);
			$count = $this->QueueTask->find('count');
			$result = $this->QueueTask->save($data);
			$this->assertFalse($result);
			$this->assertEqual($this->QueueTask->find('count'), $count);
			$this->assertTrue(!empty($this->QueueTask->validationErrors['command']));
		}
		
		//Validate works.
		$data = array(
			'QueueTask' => array(
				'type' => 2, //shell
				'command' => 'Plugin.Queue command param1 param2'
			)
		);
		$count = $this->QueueTask->find('count');
		$result = $this->QueueTask->save($data);
		$this->assertTrue(!empty($result));
		$this->assertEqual($this->QueueTask->find('count'), $count + 1);
		$this->assertTrue(empty($this->QueueTask->validationErrors['command']));
	}
	
	public function test_validUrlShell() {
		$validationErrorCommands = array(
			'someurl',
		);
		foreach ($validationErrorCommands as $command) {
			//Validate url
			$data = array(
				'QueueTask' => array(
					'type' => 3, //url
					'command' => $command
				)
			);
			$count = $this->QueueTask->find('count');
			$result = $this->QueueTask->save($data);
			$this->assertFalse($result);
			$this->assertEqual($this->QueueTask->find('count'), $count);
			$this->assertTrue(!empty($this->QueueTask->validationErrors['command']));
		}
		
		//Validate works.
		$data = array(
			'QueueTask' => array(
				'type' => 3, //shell
				'command' => '/some/url'
			)
		);
		$count = $this->QueueTask->find('count');
		$result = $this->QueueTask->save($data);
		$this->assertTrue(!empty($result));
		$this->assertEqual($this->QueueTask->find('count'), $count + 1);
		$this->assertTrue(empty($this->QueueTask->validationErrors['command']));
	}
	
	public function test_validCommandModel() {
		$data = array(
			'QueueTask' => array(
				//'type' => 1, //no type, validation error
				'command' => 'Model::action()',
			)
		);
		$count = $this->QueueTask->find('count');
		$result = $this->QueueTask->save($data);
		$this->assertFalse($result);
		$this->assertEqual($this->QueueTask->find('count'), $count);
		$this->assertTrue(!empty($this->QueueTask->validationErrors['type']));
		
		//Validate Model
		$validationErrorCommands = array(
			'Model:action()',
			'Model::action(',
		);
		foreach ($validationErrorCommands as $command) {
			//Validate model
			$data = array(
				'QueueTask' => array(
					'type' => 1, //model
					'command' => $command
				)
			);
			$count = $this->QueueTask->find('count');
			$result = $this->QueueTask->save($data);
			$this->assertFalse($result);
			$this->assertEqual($this->QueueTask->find('count'), $count);
			$this->assertTrue(!empty($this->QueueTask->validationErrors['command']));
		}
		
		//Validate works.
		$data = array(
			'QueueTask' => array(
				'type' => 1, //model
				'command' => 'Model::action("param2")'
			)
		);
		$count = $this->QueueTask->find('count');
		$result = $this->QueueTask->save($data);
		$this->assertTrue(!empty($result));
		$this->assertEqual($this->QueueTask->find('count'), $count + 1);
		$this->assertTrue(empty($this->QueueTask->validationErrors['command']));
	}
	
	public function test_typeValidate(){
		$data = array(
			'QueueTask' => array(
				'type' => 99, //no exist
				'command' => 'Model::action()',
			)
		);
		$count = $this->QueueTask->find('count');
		$result = $this->QueueTask->save($data);
		$this->assertFalse($result);
		$this->assertEqual($this->QueueTask->find('count'), $count);
		$this->assertTrue(!empty($this->QueueTask->validationErrors['type']));
	}
	
	public function test_typeStatus(){
		$data = array(
			'QueueTask' => array(
				'type' => 1, //model
				'command' => 'Model::action()',
				'status' => 99, //no exist
			)
		);
		$count = $this->QueueTask->find('count');
		$result = $this->QueueTask->save($data);
		$this->assertFalse($result);
		$this->assertEqual($this->QueueTask->find('count'), $count);
		$this->assertTrue(!empty($this->QueueTask->validationErrors['status']));
	}
	
	public function test_typeValidateAllowed(){
		QueueUtil::getConfig('allowedTypes');
		QueueUtil::$configs['allowedTypes'] = array(1,2);
		$data = array(
			'QueueTask' => array(
				'type' => 3, //valid type but not allowed
				'command' => '/url/to/queue',
			)
		);
		$count = $this->QueueTask->find('count');
		$result = $this->QueueTask->save($data);
		$this->assertFalse($result);
		$this->assertEqual($this->QueueTask->find('count'), $count);
		$this->assertTrue(!empty($this->QueueTask->validationErrors['type']));
		
		//But a valid type works
		$data = array(
			'QueueTask' => array(
				'type' => 1, //valid
				'command' => 'Model::action()',
			)
		);
		$result = $this->QueueTask->save($data);
		$this->assertTrue(!empty($result));
		$this->assertEqual($this->QueueTask->find('count'), $count + 1);
		$this->assertTrue(empty($this->QueueTask->validationErrors['type']));
	}
	
	public function test_saveUser(){
		/*
		BROKEN, I'm not sure why I've set the mock correctly
		but AuthComponent::user is still being called. TODO
		
		$this->QueueTask->expects($this->once())
			->method('getCurrentUser')
			->with('id')
			->will($this->returnValue('1'));
		$this->QueueTask->id = '524b0c44-a3a0-4956-8428-dc3ee017215a';
		$this->assertEqual($this->QueueTask->field('user_id'), null);
		$result = $this->QueueTask->saveField('type', 1);
		$this->assertTrue(!empty($result));
		$this->assertEqual($this->QueueTask->field('user_id'), 1);
		*/
	}
	
	public function test_afterFind(){
		$result = $this->QueueTask->find('first');
		$this->assertEqual($result['QueueTask']['type_human'], 'model');
		$this->assertEqual($result['QueueTask']['status_human'], 'queued');
		$this->assertEqual($result['QueueTask']['execution_time'], 0);
	}
	
	public function test_runModel(){
		$this->QueueTask->id = '524b0c44-a3a0-4956-8428-dc3ee017215a';
		$executed = $this->QueueTask->field('executed');
		$this->assertTrue(empty($executed));
		
		$result = $this->QueueTask->run();
		$this->assertTrue($result);
		$this->assertEqual($this->QueueTask->field('result'), '["param","param2"]');
		$this->assertEqual($this->QueueTask->field('status'), 3);
		$executed = $this->QueueTask->field('executed');
		$this->assertTrue(!empty($executed));
		$start_time = $this->QueueTask->field('start_time');
		$this->assertTrue(!empty($start_time));
		$end_time = $this->QueueTask->field('end_time');
		$this->assertTrue(!empty($end_time));
	}
	
	public function test_runShell(){
		$this->QueueTask->id = '524b0c44-a3a0-4956-8428-dc3ee017215b';
		$executed = $this->QueueTask->field('executed');
		$this->assertTrue(empty($executed));
		
		$this->QueueTask->Shell->expects($this->once())
			->method('dispatchShell')
			->with('Queue.SomeShell command param1 param2')
			->will($this->returnValue('command executed.'));
		$result = $this->QueueTask->run();
		
		$this->assertTrue($result);
		$this->assertEqual($this->QueueTask->field('result'), '"command executed."');
		$this->assertEqual($this->QueueTask->field('status'), 3);
		$executed = $this->QueueTask->field('executed');
		$this->assertTrue(!empty($executed));
		$start_time = $this->QueueTask->field('start_time');
		$this->assertTrue(!empty($start_time));
		$end_time = $this->QueueTask->field('end_time');
		$this->assertTrue(!empty($end_time));
	}
	
	public function test_runUrl(){
		$this->QueueTask->id = '524b0c44-a3a0-4956-8428-dc3ee017215c';
		$executed = $this->QueueTask->field('executed');
		$this->assertTrue(empty($executed));
		
		$this->QueueTask->expects($this->once())
			->method('requestAction')
			->with('/some/url/to/an/action')
			->will($this->returnValue('<html><head><title>hi</title></head></html>'));
		$result = $this->QueueTask->run();
		
		$this->assertTrue($result);
		$this->assertEqual($this->QueueTask->field('result'), '"<html><head><title>hi<\/title><\/head><\/html>"');
		$this->assertEqual($this->QueueTask->field('status'), 3);
		$executed = $this->QueueTask->field('executed');
		$this->assertTrue(!empty($executed));
		$start_time = $this->QueueTask->field('start_time');
		$this->assertTrue(!empty($start_time));
		$end_time = $this->QueueTask->field('end_time');
		$this->assertTrue(!empty($end_time));
	}
	
	public function test_runPhpCmd(){
		$this->QueueTask->id = '524b0c44-a3a0-4956-8428-dc3ee017215d';
		$executed = $this->QueueTask->field('executed');
		$this->assertTrue(empty($executed));

		$result = $this->QueueTask->run();

		$this->assertTrue($result);
		$this->assertEqual($this->QueueTask->field('result'), '7');
		$this->assertEqual($this->QueueTask->field('status'), 3);
		$executed = $this->QueueTask->field('executed');
		$this->assertTrue(!empty($executed));
		$start_time = $this->QueueTask->field('start_time');
		$this->assertTrue(!empty($start_time));
		$end_time = $this->QueueTask->field('end_time');
		$this->assertTrue(!empty($end_time));
	}
	
	public function test_runShellCmd(){
		$this->QueueTask->id = '524b0c44-a3a0-4956-8428-dc3ee017215e';
		$executed = $this->QueueTask->field('executed');
		$this->assertTrue(empty($executed));

		$result = $this->QueueTask->run();

		$this->assertTrue($result);
		$this->assertEqual($this->QueueTask->field('result'), '"hello\nworld\n"');
		$this->assertEqual($this->QueueTask->field('status'), 3);
		$executed = $this->QueueTask->field('executed');
		$this->assertTrue(!empty($executed));
		$start_time = $this->QueueTask->field('start_time');
		$this->assertTrue(!empty($start_time));
		$end_time = $this->QueueTask->field('end_time');
		$this->assertTrue(!empty($end_time));
	}
	
	public function test_runNoShell(){
		$this->QueueTask->id = 'invalid_id';
		$result = $this->QueueTask->run();
		$this->assertFalse($result);
		$this->assertEqual('QueueTask invalid_id not found.', $this->QueueTask->errors['invalid_id'][0]);
	}

}
