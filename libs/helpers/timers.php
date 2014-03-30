<?

class Timers {
	// Instance of singleton class
	private static $instance;
	// timers execute time
	private static $timers = array();
  // timers start time
  private static $start_timers = array();
	// timers description
	private static $timers_desc = array();

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

  public function setDescription($timer, $desc) {
    self::$timers_desc[$timer] = $desc;
  }

  public function start($timer) {
    if(!isset(self::$start_timers[$timer]))
      self::$start_timers[$timer] = microtime(true);
  }

  public function stop($timer) {
    if(!isset(self::$start_timers[$timer])) {
      echo 'ERROR: Timer '.$timer." not found\n";
      return;
    }
    if(!isset(self::$timers[$timer])) {
      self::$timers[$timer] = 0;
    }
    //self::$timers[$timer] += round(microtime(true) - self::$start_timers[$timer], 3)*1000;
    self::$timers[$timer] += bcsub(microtime(true), self::$start_timers[$timer], 16);
    unset(self::$start_timers[$timer]);
  }

  public function printAll() {
    foreach(self::$timers as $key => $val) {
      if(!isset(self::$timers[$key])) continue;
      $timer_desc = $key;
      if(isset(self::$timers_desc[$key])) $timer_desc = self::$timers_desc[$key];
      echo $timer_desc. ': '.gmdate("H:i:s", self::$timers[$key])."\n";
    }
  }
}
