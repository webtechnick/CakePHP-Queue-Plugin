<?php
/**
* Utility class to preform some basic tasks for the plugin
*
* @author Nick Baker
* @since 1.0
* @license MIT
*/
class QueueUtil extends Object {

	/**
	* Queue configurations stored in
	* app/config/queue.php
	* @var array
	*/
	public static $configs = array();

	/**
	* Return version number
	* @return string version number
	* @access public
	*/
	static function version(){
		return "1.0";
	}

	/**
	* Return description
	* @return string description
	* @access public
	*/
	static function description(){
		return "CakePHP Queue Plugin";
	}

	/**
	* Return author
	* @return string author
	* @access public
	*/
	static function author(){
		return "Nick Baker";
	}

	/**
	* Testing getting a configuration option.
	* @param key to search for
	* @return mixed result of configuration key.
	* @access public
	*/
	static function getConfig($key){
		if (isset(self::$configs[$key])) {
			return self::$configs[$key];
		}
		//try configure setting
		if (self::$configs[$key] = Configure::read("Queue.$key")) {
			return self::$configs[$key];
		}
		//try load configuration file and try again.
		Configure::load('queue');
		self::$configs = Configure::read('Queue');
		if (self::$configs[$key] = Configure::read("Queue.$key")) {
			return self::$configs[$key];
		}

		return null;
	}
}