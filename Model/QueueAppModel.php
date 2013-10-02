<?php
App::uses('AppModel', 'Model');
App::uses('QueueUtil','Queue.Lib');
class QueueAppModel extends AppModel {
	/**
	 * Always use Containable
	 *
	 * var array
	 */
	public $actsAs = array('Containable');

	/**
	 * Always set recursive = 0
	 * (we'd rather use containable for more control)
	 *
	 * var int
	 */
	public $recursive = 0;
	/**
	 * Filter fields
	 *
	 * @var array
	 */
	public $searchFields = array();
	/**
	 * return conditions based on searchable fields and filter
	 *
	 * @param string filter
	 * @return conditions array
	 */
	public function generateFilterConditions($filter = NULL, $pre = '') {
		$retval = array();
		if ($filter) {
			foreach ($this->searchFields as $field) {
				$retval['OR']["$field LIKE"] =  '%' . $filter . '%';
			}
		}
		return $retval;
	}
	/**
  * This is what I want create to do, but without setting defaults.
  */
  public function clear(){
  	$this->id = false;
		$this->data = array();
		$this->validationErrors = array();
		return $this->data;
  }
  /**
  * String to datetime stamp
  * @param string that is parsable by str2time
  * @return date time string for MYSQL
  */
  function str2datetime($str = 'now'){
  	if (is_array($str) && isset($str['month']) && isset($str['day']) && isset($str['year'])) {
  		$str = "{$str['month']}/{$str['day']}/{$str['year']}";
  	}
  	return date("Y-m-d H:i:s", strtotime($str));
  }
}
