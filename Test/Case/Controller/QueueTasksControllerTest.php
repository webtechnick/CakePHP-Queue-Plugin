<?php
App::uses('QueueTasksController', 'Queue.Controller');
App::uses('BaseTest', 'Test/Case');

/**
 * QueueTasksController Test Case
 *
 */
class QueueTasksControllerTest extends BaseTest {

/**
 * Additional Fixtures
 *
 * @var array
 */
	public $addFixtures = array(
		'plugin.queue.queue_task',
		'plugin.queue.location',
		'plugin.queue.user',
		'plugin.queue.corp',
		'plugin.queue.content',
		'plugin.queue.tag',
		'plugin.queue.content_tag',
		'plugin.queue.product',
		'plugin.queue.products_content_join',
		'plugin.queue.products_user',
		'plugin.queue.content_location',
		'plugin.queue.content_user',
		'plugin.queue.corps_user',
		'plugin.queue.call_source',
		'plugin.queue.hour',
		'plugin.queue.staff',
		'plugin.queue.review',
		'plugin.queue.zip',
		'plugin.queue.survey_caller',
		'plugin.queue.survey_call',
		'plugin.queue.survey_admin_note',
		'plugin.queue.note',
		'plugin.queue.import_status',
		'plugin.queue.location_user'
	);

/**
 * testAdminIndex method
 *
 * @return void
 */
	public function testAdminIndex() {
	}

/**
 * testAdminView method
 *
 * @return void
 */
	public function testAdminView() {
	}

/**
 * testAdminEdit method
 *
 * @return void
 */
	public function testAdminEdit() {
	}

/**
 * testAdminDelete method
 *
 * @return void
 */
	public function testAdminDelete() {
	}

}
