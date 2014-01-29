<?

class Info {

	static public function getHostName() {
		return $_SERVER['COMPUTERNAME'];
	}
	static public function getOS() {
		return $_SERVER['OS'];
	}
	static public function getUserName() {
		return $_SERVER['USERNAME'];
	}
	static public function getUserDomain() {
		return $_SERVER['USERDOMAIN'];
	}
	static public function getPlatform() {
		return $_SERVER['PROCESSOR_ARCHITEW6432'];
	}
}


