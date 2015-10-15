<?php
/**
* QueueShell to manage your queue via the command line
* @author Nick Baker
* @since 1.0
* @license MIT
*/
App::uses('Shell', 'Console');
App::uses('AppShell', 'Console/Command');
App::uses('Queue', 'Queue.Lib');
/**
 * Class QueueShell
 *
 * @property QueueTask $QueueTask
 */
class QueueShell extends AppShell {
	public function initialize() {
		parent::initialize();
		$this->QueueTask = ClassRegistry::init('Queue.QueueTask');
	}

	/**
	 * {@inheritDoc}
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
							'start' => array(
								'help' => __('optional: strtotime parsable scheduled date to execute. \'Sunday 11 pm\',\'Tuesday 2 am\' (default null = no restriction)'),
								'short' => 's',
								'default' => null
							),
							'end' => array(
								'help' => __('optional: strtotime parsable scheduled end date to execute. \'Monday 4am\' (default null = no restriction)'),
								'short' => 'e',
								'default' => null
							),
							'reschedule' => array(
								'help' => __('optional: string of addition to scheduled if window of start and end are missed. Parsable by strtotime. \'+1 day\', \'+1 week\' (default null, required if end is not null)'),
								'short' => 'r',
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
			->addSubcommand('view', array(
					'help' => __('View a task in the queue.'),
					'parser' => array(
						'description' => array(
							__('Use this command to view a paticular queue. Including results.')
						),
						'arguments' => array(
							'id' => array(
								'help' => __('UUID of queue to view'),
								'required' => true,
							)
						)
					)
				)
			)
			->addSubcommand('next', array(
					'help' => __('Show what is queued.'),
					'parser' => array(
						'description' => array(
							__('Use this command to see what X next in queue.')
						),
						'arguments' => array(
							'limit' => array(
								'help' => __('INT of how many you want to see in the future, \`10\`,\'5\''),
								'required' => true,
								'default' => 10
							)
						)
					)
				)
			)
			->addSubcommand('process', array(
					'help' => __('Process the queue, runs the next limit items on the queue.')
				)
			)
			->addSubcommand('in_progress', array(
					'help' => __('Show the queues in progress.')
				)
			)
			->addSubcommand('in_progress_count', array(
					'help' => __('Show the in progress count.')
				)
			)
			->addSubcommand('remove', array(
					'help' => __('Remove a task from the queue.'),
					'parser' => array(
						'description' => array(
							__('Use this command to remove a paticular task.')
						),
						'arguments' => array(
							'id' => array(
								'help' => __('UUID of task to remove. Will not remove in_process tasks.'),
								'required' => true,
							)
						),
						'options' => array(
							'force' => array(
								'help' => __('if true will force a delete even on in_progress tasks.'),
								'boolean' => true,
								'short' => 'f',
								'default' => false
							)
						)
					)
				)
			);
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
			'start' => null,
			'end' => null,
			'reschedule' => null,
			'cpu' => null,
			'cpu_limit' => null,
			'priority' => 100
		);
		$options = array_merge($defaults, (array) $this->params);
		$options['cpu_limit'] = $options['cpu'];
		$options = array_intersect_key($options, $defaults);

		if (Queue::add($command, $this->params['type'], $options)) {
			$this->out('Task succesfully added.', 1, Shell::QUIET);
			$this->out(Queue::view($this->QueueTask->id));
		} else {
			$this->out('Error adding task.');
			$this->out();
			print_r($this->QueueTask->validationErrors);
		}
	}

	public function remove() {
		$id = array_shift($this->args);
		$this->out('Removing ' . $id);
		if (Queue::remove($id, $this->params['force'])) {
			$this->out('Queue Removed.');
		} else {
			$this->out('Failed to remove Queue.');
			$this->out(Queue::view($id));
		}
	}

	public function view() {
		$id = array_shift($this->args);
		$this->out(Queue::view($id));
	}

	public function run() {
		$id = array_shift($this->args);
		$this->out('Running ' . $id);
		if (Queue::run($id)) {
			$this->out('Success.');
			$this->out(Queue::view($id));
			$this->out();
		} else {
			$this->out('Failed to run task. Check logs.');
		}
	}

	public function process() {
		$this->out('Processing Queue.');
		if (Queue::process()) {
			$this->out('Success.');
		} else {
			$this->out('One or more failed, Check logs.');
		}
	}

	public function next() {
		$limit = array_shift($this->args);
		$this->out('Retrieving Queue List.');
		$queue = Queue::next($limit, false);
		$i = 1;
		foreach ($queue as $task) {
			$this->out($i . ') ' .  Queue::view($task['QueueTask']['id']));
			$i++;
		}
	}

	public function in_progress() {
		$this->out('Retrieving In Progress Queues.');
		$queue = Queue::inProgress();
		if (empty($queues)) {
			$this->out('No Tasks currently running.');
			exit(1);
		}
		$i = 1;
		foreach ($queue as $task) {
			$this->out($i . ') ' .  Queue::view($task['QueueTask']['id']));
			$i++;
		}
	}

	public function in_progress_count() {
		$this->out(Queue::inProgressCount(), 1, Shell::QUIET);
	}
}
