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

	/**
	* Config the cache if cache is set to config
	*/
	public static function configCache() {
		if ($duration = self::getConfig('cache')) {
			Cache::config('queue', array(
				'engine' => 'File',
				'duration' => $duration,
				'path' => CACHE,
				'prefix' => 'queue_'
			));
		}
	}
	/**
	* Get the cache key config if we have cache setup
	* @param string key
	* @return mixed boolean false or 
	*/
	public static function readCache($key) {
		if (self::getConfig('cache')) {
			return Cache::read($key, 'queue');
		}
		return false;
	}
	/**
	* Write the cache if we have a cache setup
	* @param string key
	* @param mixed value
	* @return boolean success
	*/
	public static function writeCache($key, $value) {
		if (self::getConfig('cache')) {
			return Cache::write($key, $value, 'queue');
		}
		return false;
	}
	
	/**
	* Clears all queue cache.
	* @return boolean success
	*/
	public static function clearCache() {
		if (self::getConfig('cache')) {
			return Cache::clear(false, 'queue');
		}
		return true;
	}
	
	/**
	* Get the current Cpu Usage as a percentages
	* Grabs from cache if we have it.
	* @throws Exception 
	* @return float current cpu percentage.
	*/
	public static function currentCpu() {
		if ($cpu = self::readCache('cpu')) {
			return $cpu;
		}
		$uptime = shell_exec('uptime');
		if (empty($uptime) || strpos($uptime, 'load') === false) {
			throw new Exception('Unable to retrieve load avearge from uptime.');
		}
		$uptime = explode(':', $uptime);
		$averages = trim(array_pop($uptime));
		list($min1, $min5, $min15) = explode(' ', $averages, 3);
		$cores = self::getConfig('cores');
		if (!$cores) {
			$cores = 1;
		}
		$percent = ($min5 / $cores) * 100;
		$percent = round($percent);
		self::writeCache('cpu', $percent);
		
		return $percent;
	}
}