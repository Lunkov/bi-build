<?php

class Enviroment {
	
	public static function getOS() {
    return strtoupper(PHP_OS);
	}
  
	public static function getUserName() {
		if (defined('STDIN')) {
			return getenv("username");
		} else {
			return filter_input(INPUT_SERVER, 'PHP_AUTH_USER');
		}
	}
  
	public static function getPlatform() {
    $p = '';
		if(isset($_SERVER['PROCESSOR_ARCHITEW6432'])) {
      $p = $_SERVER['PROCESSOR_ARCHITEW6432'];
    } else {
      $p = php_uname('m');
    }
    $ret = '?';
    switch($p) {
      case 'x86_64':
      case 'AMD64':
        $ret = 'x64';
        break;
      case 'i386':
        $ret = 'x32';
        break;
    }
    return $ret;
	}
  
	public static function getHomeDir() {
    if(($ret = getenv('HOME')) != FALSE) {
      return $ret;
    }
		if(isset($_SERVER['HOME'])) {
      return $_SERVER['HOME'];
    }
    if(isset($_SERVER['HOMEPATH']) && isset($_SERVER['HOMEDRIVE'])) {
      return $_SERVER['HOMEDRIVE'].$_SERVER['HOMEPATH'];
    }
    return '';
	}
  
	public static function getTemp() {
		return sys_get_temp_dir();
	}
  
	public static function getProcCount() {
    return (isset($_SERVER['NUMBER_OF_PROCESSORS'])) ? $_SERVER['NUMBER_OF_PROCESSORS'] : 1 ;
	}

  public static function toString() {
    return  'OS: '.self::getOS()."\n".
            'Platform: '.self::getPlatform()."\n".
            'Number of processors: '.self::getProcCount()."\n".
            'User name: '.self::getUserName()."\n".
            'Home directory: '.self::getHomeDir()."\n".
            'Temporary directory: '.self::getTemp()."\n";
	}

}
