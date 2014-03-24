<?

class Vars {
	// Instance of singleton class
	private static $instance;
	// vars
	private static $vars = array();

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

  public function setVar($var, $value) {
    self::$vars[$var] = $value;
  }

  public function getVar($var, $value) {
    if(!isset(self::$vars[$var])) return null;
    return self::$vars[$var];
  }

  public function printAll() {
    foreach(self::$vars as $key => $val) {
      echo $key. '='.$val."\n";
    }
  }
}
