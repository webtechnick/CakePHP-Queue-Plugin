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
						'arguments' => array(
							'command' => array(
								'help' => __('The actual command string'),
								'required' => true,
								//'short' => 'c',
							)
						),
						'options' => array(
							'type' => array(
								'help' => __('The type of task command.'),
								'required' => true,
								'short' => 't',
								'default' => 'model',
								'choices' => array_values($this->QueueTask->_types)
							),
							'hour' => array(
								'help' => __('optional: Hour of the day to execute, 0-23 or strtotime parsable. \'11 pm\',\'23\' (default null = no restriction)'),
								'short' => 'h',
								'default' => null
							),
							'day' => array(
								'help' => __('optional: Day of the week to execute, 0-6 or strtotime parsable. \'Sunday\',\'0\' (default null = no restriction)'),
								'short' => 'd',
								'default' => null
							),
							'cpu' => array(
								'help' => __('optional: CPU Percent Limit to run task, 0-100. \'95\',\'10\' (default null = no restriction)'),
								'short' => 'c',
								'default' => null
							),
							'priority' => array(
								'help' => __('optional: Priority of task, lower number the sooner it will run.  \'5\',\'100\''),
								'short' => 'p',
								'default' => 100
							),
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
		$command = array_shift($this->args);
		$defaults = array(
			'hour' => null,
			'day' => null,
			'cpu' => null,
			'cpu_limit' => null,
			'priority' => 100
		);
		$options = array_merge($defaults, (array) $this->params);
		$options['cpu_limit'] = $options['cpu'];
		$options = array_intersect_key($options, $defaults);

		if (Queue::add($command, $this->params['type'], $options)) {
			$this->out('Task succesfully added. ID:' . $this->QueueTask->id);
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
