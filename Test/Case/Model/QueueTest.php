<?php
App::uses('Queue', 'Queue.Model');
App::uses('CakeTestCase','TestSuite');
/**
 * Queue Test Case
 *
 */
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
		$this->Queue = ClassRegistry::init('Queue.Queue');
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
	
	public function test_run(){
		$result = $this->Queue->run('524b0c44-a3a0-4956-8428-dc3ee017215a');
		debug($result);
	}

}
