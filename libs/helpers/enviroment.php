<?php

class Enviroment {
	
	public static function getOS() {
		//return filter_input(INPUT_SERVER, 'OS');
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
		$ret = filter_input(INPUT_SERVER, 'PROCESSOR_ARCHITEW6432');
    if(empty($ret)) {
      $p = php_uname('m');
      switch($p) {
        case 'x86_64':
          $ret = 'x64';
          break;
        case 'i386':
          $ret = 'x32';
          break;
      }
    }
    return $ret;
	}
	public static function getHomeDir() {
		return filter_input(INPUT_SERVER, 'HOME');
	}
	public static function getTemp() {
		return filter_input(INPUT_SERVER, 'TEMP');
	}
	public static function getProcCount() {
    $cnt = filter_input(INPUT_SERVER, 'NUMBER_OF_PROCESSORS');
    return ($cnt < 1) ? 1 : $cnt ;
	}

}
