<?php
App::uses('Shell', 'Console');
App::uses('AppShell', 'Console/Command');
App::uses('Queue', 'Queue.Lib');
class QueueShell extends AppShell {
	public $uses = array('Queue.QueueTask');
/**
 * get the option parser.
 *
 * @return void
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		return $parser->description(
				'The Queue shell.' .
				'')
			->addSubcommand('add', array(
					'help' => __('Add a task to the queue.'),
					'parser' => array(
						'description' => array(
							__('Use this command to add tasks to the queue')
						),
						'options' => array(
							'type' => array(
								'help' => __('The type of command (model, shell, url, php_cmd, shell_cmd)'),
								'required' => true,
								'short' => 't',
								'default' => 'model',
								'choices' => array_values($this->QueueTask->_types)
							),
							'command' => array(
								'help' => __('The actual command string'),
								'required' => true,
								'short' => 'c',
							)
						)
					)
				)
			)
			->addSubcommand('run', array(
					'help' => __('Run a task in the queue.'),
					'parser' => array(
						'description' => array(
							__('Use this command to run a paticular queue')
						),
						'arguments' => array(
							'id' => array(
								'help' => __('UUID of queue to run'),
								'required' => true,
							)
						)
					)
				)
			)
			->addSubcommand('show', array(
				'help' => __('Show what is queued.')))
			->addSubcommand('process', array(
				'help' => __('Process the queue, run the next thing.')))
			->addSubcommand('archive', array(
				'help' => __('Archive a task in the queue.')));
	}

/**
 * Override main
 *
 * @return void
 */
	public function main() {
		$this->out($this->getOptionParser()->help());
	}
	
	public function add() {
		if (Queue::add($this->params['command'], $this->params['type'])) {
			$this->out('Task succesfully added.');
		} else {
			$this->out('Error adding task.');
			$this->out();
			print_r($this->QueueTask->validationErrors);
		}
	}
	
	public function run() {
		$this->out('Running ' . $this->params['id']);
		if (Queue::run($this->params['id'])) {
			$this->out('Success.');
			//TODO return the result of the QueueTask.result
		} else {
			$this->out('Failed to run task.');
		}
	}
	
	public function process() {
		$this->out('Processing Queue.');
		if (Queue::process()) {
			$this->out('Success.');
		} else {
			$this->out('One or more failed, check log.');
		}
	}
	
	public function show() {
	}
	
	public function archive() {
	}
	
	public function in_progress() {
		
	}
}
