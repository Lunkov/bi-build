<?php

class Info {

	static public function getHostName() {
		return filter_input(INPUT_SERVER, 'COMPUTERNAME');
	}
	static public function getOS() {
		return filter_input(INPUT_SERVER, 'OS');
	}
	static public function getUserName() {
		return filter_input(INPUT_SERVER, 'USERNAME');
	}
	static public function getUserDomain() {
		return filter_input(INPUT_SERVER, 'USERDOMAIN');
	}
	static public function getPlatform() {
		return filter_input(INPUT_SERVER, 'PROCESSOR_ARCHITEW6432');
	}
}


