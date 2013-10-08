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


## Usage

Once you've decided how you're going to process your queue it's time to start adding tasks to your queue.
