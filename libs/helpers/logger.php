<?php

class Logger {
  const Emergency  = 0; // Фатальный сбой. Система неработоспособна (не используется)
  const Alert      = 1; // Событие, требующее немедленного внимания администратора
  const Critical   = 2; // Критические ошибки. Ошибки ввода-вывода и т.д.
  const Error      = 3; // Ошибки
  const Warning    = 4; // Предупреждения
  const Notice     = 5; // Предусмотренные события, не являющиеся ошибками, требующие внимания администратора
  const Info       = 6; // Информационные сообщения
  const Debug      = 7; // Информационные сообщения в режиме отладки и настройки системы
  const MAX        = 8;
  private static $strSeverity = array (
                                'EMERGENCY',
                                'ALERT    ',
                                'CRITICAL ',
                                'ERROR    ',
                                'WARNING  ',
                                'NOTICE   ',
                                'INFO     ',
                                'DEBUG    ',
                                'MAX      '
      );
  
	// Instance of singleton class
	private static $instance;
  
  private static $level;
  private static $show_level = false;
  private static $show_time = false;

	private function __construct() {
    self::$level = self::Warning;
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
  
  public function setLevel($level) {
    self::$level = $level;
  }
  public function showLevel($show_level) {
    self::$show_level = $show_level;
  }
  public function showTime($show_time) {
    self::$show_time = $show_time;
  }
  
  public function out($level, $format) {
    if($level > self::$level) { return; }
    if(self::$show_time) {
      echo date('H:i:s').': ';
    }
    if(self::$show_level) {
      echo self::$strSeverity[$level].': ';
    }
    /*
    if(is_array($args) && count($args)>0) {
      vprintf($format, $args);
    } else {
      echo $format;
    }*/
    echo $format;
    echo "\n";
  }
  
}
