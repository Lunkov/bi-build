<?php

class Enviroment {
	
	public static function getOS() {
		return filter_input(INPUT_SERVER, 'OS');
	}
	public static function getUserName() {
		if (defined('STDIN')) {
			return getenv("username");
		} else {
			return filter_input(INPUT_SERVER, 'PHP_AUTH_USER');
		}
	}
	public static function getPlatform() {
		return filter_input(INPUT_SERVER, 'PROCESSOR_ARCHITEW6432');
	}
	public static function getTemp() {
		return filter_input(INPUT_SERVER, 'TEMP');
	}
	public static function getProcCount() {
    $cnt = filter_input(INPUT_SERVER, 'NUMBER_OF_PROCESSORS');
    return ($cnt < 1) ? 1 : $cnt ;
	}

}
