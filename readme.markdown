# CakePHP Queue Plugin

* Author: Nick Baker
* Version: 1.0.0
* License: MIT
* Website: <http://www.webtechnick.com>

## Features

Complete tool to background and schedule your time consuming tasks.  Queue up almost anything from CakePHP shells, models, and actions to standard php commands or even shell commands.

## Changelog

* 1.0.0 Initial release

## Install

Clone the repository into your `app/Plugin/Queue` directory: 

	git clone https://github.com/webtechnick/CakePHP-Queue-Plugin.git app/Plugin/Queue

Or you can install via <http://getcomposer.org>

Load the plugin in your `app/Config/bootstrap.php` file:

	CakePlugin::load('Queue');

Run the schema into your database to install the required tables.

	cake schema create --plugin Queue

## Setup

Create the file `app/Config/queue.php` with the defaults from `app/Plugin/Queue/Config/queue.php.default`:

	$config = array(
		'Queue' => array(
			'log' => true, //logs every task run in a log file.
			'limit' => 1, //limit how many queues can run at a time. (default 1).
			'allowedTypes' => array( //restrict what type of command can be queued.
				1, //model
				2, //shell
				3, //url
				//4, //php_cmd
				//5, //shell_cmd
			),
			'archiveAfterExecute' => true, //will archive the task once finished executing for quicker queues
			'cache' => '+5 minute', //how long to cache cpu usage and list. (false disables cache)
			'cores' => '8', //number of cpu cores to correctly gauge current CPU load.
		)
	);

## Cron Setup (optional)

Once you start adding things to your queue you need to process it.  You can do it a few ways.

1) By setting up a cron (recommended)

	crontab -e
	*/1 * * * * /path/to/app/Plugin/Queue/scripts/process_queue.sh

**Note:** The cron script assumes Console/cake is executable from the root of your app directory.

2) By processing the Queue via the built in shell

	cake queue.queue process

3) By processing the Queue via the built in library

	App::uses('Queue','Queue.Lib');
	Queue::process();

4) By navigating to the queue plugin admin web interface and processing the Queue (feature not available yet)


## Quick Start Guide (Shell)

Add a Task to the queue.

	$ cake queue.queue add "Queue.QueueTask::find('first')"
	
	Task succesfully added.
	525387a1-2dd0-4100-a48f-4f4be017215a queued model
		Command: Queue.QueueTask::find('first')
		Priority: 100

Process the Queue.

	$ cake queue.queue process
	
	Processing Queue.
	Success.

View the Task added.

	$ cake queue.queue view 525387a1-2dd0-4100-a48f-4f4be017215a
	
	525387a1-2dd0-4100-a48f-4f4be017215a finished model
	Command: Queue.QueueTask::find('first')
	Priority: 100
	Executed on Monday 7th of October 2013 10:19:10 PM. And took 0 ms.
	Result: {"QueueTask":{"id":"525387a1-2dd0-4100-a48f-4f4be017215a","user_id":null,"created":"2013-10-07 22:18:41","modified":"2013-10-07 22:19:10","executed":null,"scheduled":null,"scheduled_end":null,"reschedule":null,"start_time":"1381205950","end_time":null,"cpu_limit":null,"is_restricted":false,"priority":"100","status":"2","type":"1","command":"Queue.QueueTask::find('first')","result":null,"execution_time":null,"type_human":"model","status_human":"in progress"}}

## Quick Start Guide (Library)

Adding a Task to the queue.

	App::uses('Queue', 'Queue.Lib');
	$task = Queue::add("Queue.QueueTask::find('first')");
	/* $task = 
		'QueueTask' => array(
			'priority' => (int) 100,
			'command' => 'Queue.QueueTask::find('first')',
			'type' => (int) 1,
			'scheduled' => null,
			'scheduled_end' => null,
			'reschedule' => null,
			'cpu_limit' => null,
			'modified' => '2013-10-07 22:22:36',
			'created' => '2013-10-07 22:22:36',
			'user_id' => null,
			'id' => '5253888c-ae18-4ffd-991a-4436e017215a'
		) */
	
Process the queue.

	$result = Queue::process();
	/* $result will be boolean true */

View the Task.

	$task = Queue::view('5253888c-ae18-4ffd-991a-4436e017215a');
	/* $task is the string representation, same as queue view. Not as useful */
	
	$task = Queue::findById('5253888c-ae18-4ffd-991a-4436e017215a');
	/* $task is now an associative array, much more useful.
	array(
		'id' => '52538a36-df1c-4186-a50a-4076e017215a',
		'user_id' => null,
		'created' => '2013-10-07 22:29:42',
		'modified' => '2013-10-07 22:29:42',
		'executed' => '2013-10-07 22:29:42',
		'scheduled' => null,
		'scheduled_end' => null,
		'reschedule' => null,
		'start_time' => '1381206582',
		'end_time' => '1381206582',
		'cpu_limit' => null,
		'is_restricted' => false,
		'priority' => '100',
		'status' => '3',
		'type' => '1',
		'command' => 'Queue.QueueTask::find('first')',
		'result' => '{"QueueTask":{"id":"524b0c44-a3a0-4956-8428-dc3ee017215a","user_id":null,"created":"2013-10-01 11:54:12","modified":"2013-10-01 11:54:12","executed":null,"scheduled":null,"scheduled_end":null,"reschedule":null,"start_time":null,"end_time":null,"cpu_limit":null,"is_restricted":false,"priority":"100","status":"1","type":"1","command":"SomeModel::action(\"param\",\"param2\")","result":"","execution_time":null,"type_human":"model","status_human":"queued"}}',
		'execution_time' => '0',
		'type_human' => 'model',
		'status_human' => 'finished'
	)	*/
	

## Adding Tasks to Queue Types and Commands

Once you've decided how you're going to process your queue it's time to start adding tasks to your queue.

To add tasks use the built in shell or library.

	Queue::add($command, $type, $options = array());
	cake queue.queue add "command" -t type

There are many `5 types` of commands you can use. You must specify the type when adding the command unless model is used. 
Model type is assumed when no type is specified.

1) **Model** : Model function to execute. Examples:

	#Command strings
	Model::action()
	Plugin.Model::action("param1","pararm2")
	
	#Adding the command to the queue.
	Queue::add("Plugin.Model::action('param1','pararm2')");
	cake queue.queue add "Plugin.Model::action('param1','pararm2')"

2) **Shell** : CakePHP shell to execute. Examples:

	#Command strings
	ClearCache.clear_cache
	shell_command -f flag arg1 arg2
	
	#Adding the command to the queue.
	Queue::add("shell_command -f flag arg1 arg2", 'shell');
	cake queue.queue add "shell_command -f flag arg1 arg2" -t shell

3) **Url** : A URL to requestAction. Example:

	#Command string
	/path/to/url
	
	#Adding the command to the queue.
	Queue::add("/path/to/url", 'url');
	cake queue.queue add "/path/to/url" -t url

4) **php_cmd** : PHP Command, a simple or complex php command. Examples:

	#Command strings
	3 + 5
	mail('nick@example.com','subject','message')
	
	#Adding the command to the queue.
	Queue::add("mail('nick@example.com','subject','message')", 'php_cmd');
	cake queue.queue add "mail('nick@example.com','subject','message')" -t php_cmd

5) **shell_cmd** : Basic Bash shell command. Examples:

	#Command string
	echo 'hello' && echo 'world'
	
	#Adding the command to the queue.
	Queue::add("echo 'hello' && echo 'world'", 'shell_cmd');
	cake queue.queue add "echo 'hello' && echo 'world'" -t shell_cmd

**NOTE** `php_cmd` and `shell_cmd` are not allowed by default. You have to turn them on in `app/Config/queue.php`.

## Viewing Your Queue

You can see what is in the queue at any given time and what is up next by calling `Queue::next($limit);` or the shell

	cake queue.queue next 10
	
	Retrieving Queue List.
	1) 52537d35-95d0-48aa-a48d-416de017215a queued url
		Command: /path/to/url
		Priority: 100
		Restricted By:
			Start: 2013-10-11 03:00:00
			CPU <= 95%
	2) 52537d09-6bf0-4961-94f0-4201e017215a queued model
		Command: Model::action()
		Priority: 100
	3) 52535ab8-6298-4ab3-9fc6-4b49e017215a queued shell_cmd
		Command: echo 'hello' && echo 'world'
		Priority: 100
		Restricted By:
			Start: 2013-10-07 23:00:00
	4) 52537d35-95d0-48aa-a48d-416de017215a queued url
		Command: /path/to/url
		Priority: 100
		Restricted By:
			Start: 2013-10-11 03:00:00
			CPU <= 95%

You'll see a list of your upcomming queues including restrictions you set on your tasks.

## Task Restrictions

There are a number of restrictions you can assign to Tasks that will modify their order in the queue and affect when the task executes.
Restricted Tasks gain priority over Non-restriction tasks (basic queued tasks).  The Queue process will look for restricted tasks that match
the current state of the server before it looks for non-restriction tasks as non-restriction tasks are by defination not time sensitive.

#### Task Options

1) **Scheduled Start** Restriction: you can schedule a task to not execute until a certain scheduled time has passed.

	Queue::add($command, $type, $options = array(
		'start' => 'Friday 11pm',
	));
	
	# Queue Shell
	cake queue.queue add "command" -t type --start "Friday 11pm"

2) **Schedule End** Restriciton: you can specify an end time to make a "window" of execution time.  When using this option you must also specify a reschedule option that will go into affect if the "window" is missed.

	Queue::add($command, $type, $options = array(
		'start' => 'Friday 11pm',
		'end' => 'Saturday 5am',
		'reschedule' => '+1 week' //if window is missed, will add '+1 week' to 'Friday 11pm' and 'Saturday 5am'
	));
	
	# Queue Shell
	cake queue.queue add "command" -t type --start "Friday 11pm" --end "Saturday 5am" --reschedule "+1 week"

3) **Cpu Load** Restriction: you can specify a CPU load restriction to only execute task when CPU is below a certain threshold.

	# Task will execute when the current CPU load is less than 95%
	Queue::add($command, $type, $options = array(
		'cpu' => '95',
	));
	
	# Queue Shell
	cake queue.queue add "command" -t type --cpu "95"

4) **Priority** Restriction: by default all Tasks added are given 100 in priority.  You can change this priority when adding and will allow you to jump ahead of the line.  The lower the number the closer to the top of the queue it becomes.

	# This will jump the task to the top of the queue (after restrictions tasks)
	Queue::add($command, $type, $options = array(
		'priority' => '1',
	));
	
	# Queue Shell
	cake queue.queue add "command" -t type -p 1

Mix and match your restrictions to have total control over when your tasks execute and where they're placed in the queue.

## Running A Task Manually

You can bypass the queue process and explicicty run a task manually.  This will bypass any restrictions execute the command and return the result.

	//Queue::run($id);
	#cake queue.queue run <id>
	
	$ cake queue.queue run 52535ab8-6298-4ab3-9fc6-4b49e017215a
	
	Running 52535ab8-6298-4ab3-9fc6-4b49e017215a
	Success.
	52535ab8-6298-4ab3-9fc6-4b49e017215a finished shell_cmd
		Command: echo 'hello' && echo 'world'
		Priority: 100
		Restricted By:
			Start: 2013-10-07 23:00:00
		Executed on Monday 7th of October 2013 10:06:27 PM. And took 0 ms.
		Result: "hello\nworld\n"

## Viewing A Task

You can view any task, in the queue or in the queue log (archive after execution) at anytime.
You'll see all the meta data and if it's a finished task you'll see the result of the task as well as the execution time and how long it took to complete.

	//$task = Queue::view($id);
	#cake queue.queue view <id>
	
	$ cake queue.queue view 52535ab8-6298-4ab3-9fc6-4b49e017215a
	
	52535ab8-6298-4ab3-9fc6-4b49e017215a finished shell_cmd
	Command: echo 'hello' && echo 'world'
	Priority: 100
	Restricted By:
		Start: 2013-10-07 23:00:00
	Executed on Monday 7th of October 2013 10:06:27 PM. And took 0 ms.
	Result: "hello\nworld\n"

## Viewing Tasks in Progress

You can view the current tasks in progress via the library or shell.

	$queue = Queue::inProgress();
	cake queue.queue in_progress

**NOTE:** You can also get just the progress count by running `cake queue.queue in_progress_count` or `Queue::inProgressCount();`

## Removing Tasks from Queue

Remove tasks from the queue.  Note this will not remove tasks that are currently in progress by default.

	//Queue::remove($id);
	cake queue.queue remove <id>

**NOTE:** You can force a task to be removed even if it's in progress by passing `true` into `Queue::remove($id, true);` or `cake queue.queue remove <id> -f`

# Enjoy

Enjoy the plugin!
