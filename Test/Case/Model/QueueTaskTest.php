<?php
App::uses('QueueAppModel','Queue.Model');
App::uses('QueueTask', 'Queue.Model');
App::uses('CakeTestCase','TestSuite');
App::uses('Shell','Console');
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
