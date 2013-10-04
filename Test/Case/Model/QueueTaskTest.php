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
 * Additional Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'plugin.queue.queue_task',
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
	}
	
	public function test_runNoShell(){
		$this->QueueTask->id = 'invalid_id';
		$result = $this->QueueTask->run();
		$this->assertFalse($result);
		$this->assertEqual('QueueTask invalid_id not found.', $this->QueueTask->errors['invalid_id'][0]);
	}

}
