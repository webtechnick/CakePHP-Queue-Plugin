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


## Quick Start Usage

Once you've decided how you're going to process your queue it's time to start adding tasks to your queue.

To add tasks use the built in shell or library.

	Queue::add($command, $type, $options = array());
	cake queue.queue add "command" -t type

There are many `5 types` of commands you can use. You must specify the type when adding the command unless model is used. 
Model command is assumed when no type is specified.

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

There are a number of restrictions you can assign to Tasks that will modify their order in the queue and affect the when the task executes.
Restricted Tasks gain priority over Non-restriction tasks (basic queued tasks).  The Queue process will look for restricted tasks that match
the current state of the server before it looks for non-restriction tasks as non-restriction tasks are by defination not time sensitive.

#### Task Options

1) **Scheduled Start** Restriction: you can schedule a task to not execute until a certain the scheduled time has passed.

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


