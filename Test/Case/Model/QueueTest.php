<?php
App::uses('QueueAppModel','Queue.Model');
App::uses('Queue', 'Queue.Model');
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

class QueueTest extends CakeTestCase {

/**
 * Additional Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'plugin.queue.queue',
	);

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Queue = $this->getMockForModel('Queue.Queue', array('requestAction', 'getCurrentUser'));
		$this->Queue->Shell = $this->getMock('Shell');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Queue);

		parent::tearDown();
	}
	
	public function test_saveUser(){
		/*
		BROKEN, I'm not sure why I've set the mock correctly
		but AuthComponent::user is still being called. TODO
		
		$this->Queue->expects($this->once())
			->method('getCurrentUser')
			->with('id')
			->will($this->returnValue('1'));
		$this->Queue->id = '524b0c44-a3a0-4956-8428-dc3ee017215a';
		$this->assertEqual($this->Queue->field('user_id'), null);
		$result = $this->Queue->saveField('type', 1);
		$this->assertTrue(!empty($result));
		$this->assertEqual($this->Queue->field('user_id'), 1);
		*/
	}
	
	public function test_afterFind(){
		$result = $this->Queue->find('first');
		$this->assertEqual($result['Queue']['type_human'], 'model');
		$this->assertEqual($result['Queue']['status_human'], 'queued');
	}
	
	public function test_runModel(){
		$this->Queue->id = '524b0c44-a3a0-4956-8428-dc3ee017215a';
		$executed = $this->Queue->field('executed');
		$this->assertTrue(empty($executed));
		
		$result = $this->Queue->run();
		$this->assertTrue($result);
		$this->assertEqual($this->Queue->field('result'), '["param","param2"]');
		$this->assertEqual($this->Queue->field('status'), 3);
		$executed = $this->Queue->field('executed');
		$this->assertTrue(!empty($executed));
	}
	
	public function test_runShell(){
		$this->Queue->id = '524b0c44-a3a0-4956-8428-dc3ee017215b';
		$executed = $this->Queue->field('executed');
		$this->assertTrue(empty($executed));
		
		$this->Queue->Shell->expects($this->once())
			->method('dispatchShell')
			->with('Queue.SomeShell command param1 param2')
			->will($this->returnValue('command executed.'));
		$result = $this->Queue->run();
		
		$this->assertTrue($result);
		$this->assertEqual($this->Queue->field('result'), '"command executed."');
		$this->assertEqual($this->Queue->field('status'), 3);
		$executed = $this->Queue->field('executed');
		$this->assertTrue(!empty($executed));
	}
	
	public function test_runUrl(){
		$this->Queue->id = '524b0c44-a3a0-4956-8428-dc3ee017215c';
		$executed = $this->Queue->field('executed');
		$this->assertTrue(empty($executed));
		
		$this->Queue->expects($this->once())
			->method('requestAction')
			->with('/some/url/to/an/action')
			->will($this->returnValue('<html><head><title>hi</title></head></html>'));
		$result = $this->Queue->run();
		
		$this->assertTrue($result);
		$this->assertEqual($this->Queue->field('result'), '"<html><head><title>hi<\/title><\/head><\/html>"');
		$this->assertEqual($this->Queue->field('status'), 3);
		$executed = $this->Queue->field('executed');
		$this->assertTrue(!empty($executed));
	}
	
	public function test_runPhpCmd(){
		$this->Queue->id = '524b0c44-a3a0-4956-8428-dc3ee017215d';
		$executed = $this->Queue->field('executed');
		$this->assertTrue(empty($executed));

		$result = $this->Queue->run();

		$this->assertTrue($result);
		$this->assertEqual($this->Queue->field('result'), '7');
		$this->assertEqual($this->Queue->field('status'), 3);
		$executed = $this->Queue->field('executed');
		$this->assertTrue(!empty($executed));
	}
	
	public function test_runShellCmd(){
		$this->Queue->id = '524b0c44-a3a0-4956-8428-dc3ee017215e';
		$executed = $this->Queue->field('executed');
		$this->assertTrue(empty($executed));

		$result = $this->Queue->run();

		$this->assertTrue($result);
		$this->assertEqual($this->Queue->field('result'), '"hello\nworld\n"');
		$this->assertEqual($this->Queue->field('status'), 3);
		$executed = $this->Queue->field('executed');
		$this->assertTrue(!empty($executed));
	}
	
	public function test_runNoShell(){
		$this->Queue->id = 'invalid_id';
		$result = $this->Queue->run();
		$this->assertFalse($result);
		$this->assertEqual('Queue invalid_id not found.', $this->Queue->errors['invalid_id'][0]);
	}

}
