<?php
App::uses('QueueAppController', 'Queue.Controller');
/**
 * QueueTasks Controller
 *
 * @property QueueTask $QueueTask
 * @property PaginatorComponent $Paginator
 * @property SessionComponent $Session
 */
class QueueTasksController extends QueueAppController {
	
	public $uses = array('Queue.QueueTask','Queue.QueueTaskLog');

/**
 * Components
 *
 * @var array
 */
	public $components = array('Paginator', 'Session');
	
	public function beforeFilter() {
		parent::beforeFilter();
		$this->set('statuses', $this->QueueTask->_statuses);
		$this->set('types', $this->QueueTask->_types);
	}

/**
 * admin_index method
 *
 * @return void
 */
	public function admin_index($filter = null) {
		if(!empty($this->request->data)){
			$filter = $this->request->data['QueueTask']['filter'];
		}
		$conditions = $this->QueueTask->generateFilterConditions($filter);
		$this->set('queueTasks',$this->paginate('QueueTask',$conditions));
		$this->set('filter', $filter);
	}

/**
 * admin_view method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function admin_view($id = null) {
		if (!$this->QueueTask->exists($id) && !$this->QueueTaskLog->exists($id)) {
			throw new NotFoundException(__('Invalid queue task'));
		}
		$this->set('queueTask', $this->QueueTask->findForView($id));
	}
	
	public function admin_process() {
		//Process the queue
	}
	
	public function admin_run($id = null) {
		if (!$this->QueueTask->exists($id)) {
			throw new NotFoundException(__('Invalid queue task'));
		}
		if ($this->QueueTask->run($id)) {
			$this->Session->setFlash('Queue ' . $id . ' Ran Successfully.');
		} else {
			$this->Session->setFlash('Queue ' . $id . ' Failed to run.');
		}
		return $this->redirect(array('admin' => true, 'action' => 'view', $id));
	}

/**
 * admin_edit method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function admin_edit($id = null) {
		if (!empty($this->request->data)) {
			if ($this->QueueTask->adminSave($this->request->data)) {
				$this->Session->setFlash(__('The queue task has been saved'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The queue task could not be saved. Please, try again.'));
			}
		}

		if ($id && empty($this->request->data)){
			$this->request->data['QueueTask'] = $this->QueueTask->findById($id);
			$this->set('id', $id);
		}

	}

/**
 * admin_delete method
 *
 * @throws NotFoundException
 * @throws MethodNotAllowedException
 * @param string $id
 * @return void
 */
	public function admin_delete($id = null) {
		$this->QueueTask->id = $id;
		if (!$this->QueueTask->exists()) {
			throw new NotFoundException(__('Invalid queue task'));
		}
		$this->request->onlyAllow('post', 'delete');
		if ($this->QueueTask->delete($id)) {
			$this->Session->setFlash(__('Queue task deleted'));
		} else {
			$this->Session->setFlash(__('Queue task was not deleted'));
		}

		$this->redirect(array('action' => 'index'));
	}
}
