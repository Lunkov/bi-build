<?

class Enviroment {
	
	public static function getOS() {
		return $_SERVER['OS'];
	}
	public static function getPlatform() {
		return $_SERVER['PROCESSOR_ARCHITEW6432'];
	}
	public static function getTemp() {
		return $_SERVER['TEMP'];
	}
	public static function getProcCount() {
		return isset($_SERVER['NUMBER_OF_PROCESSORS']) ? $_SERVER['NUMBER_OF_PROCESSORS'] : 1 ;
	}

}
