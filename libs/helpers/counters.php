<?php

class Counters {
	// Instance of singleton class
	private static $instance;
	// counters
	private static $counters = array();
	// counters description
	private static $counters_desc = array();

	private function __construct() {
	}

  public static function get()
  {
      if (!isset(self::$instance)) {
          $className = __CLASS__;
          self::$instance = new $className;
      }
      return self::$instance;
  }
	
  private function __clone() { }
  private function __sleep() { }
  private function __wakeup() { }

  public function setDescription($counter, $desc) {
    self::$counters_desc[$counter] = $desc;
  }

  public function add($counter, $num = 1) {
    if(!isset(self::$counters[$counter])) {
      self::$counters[$counter] = 0;
    }
    self::$counters[$counter] += $num;
  }

  public function printAll() {
    foreach(self::$counters as $key => $val) {
      if(!isset(self::$counters[$key])) continue;
      $counter_desc = $key;
      if(isset(self::$counters_desc[$key])) $counter_desc = self::$counters_desc[$key];
      echo $counter_desc. ': '.self::$counters."\n";
    }
  }
}
