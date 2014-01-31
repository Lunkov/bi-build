<?

class Enviroment {
	private $variant;
	private $platform;
	private $os_type;

	public function setEnv($os_type, $platform, $variant) {
		$this->os_type = $os_type;
		$this->platform = $platform;
		$this->variant = $variant;
	}
	public function getEnvOS() {
		return $os_type;
	}
	public function getEnvPlatform() {
		return $platform;
	}
	public function getEnvVariant() {
		return $variant;
	}
	public function getHostOS() {
		return $_SERVER['OS'];
	}
        public function getHostPlatform() {
		return $_SERVER['PROCESSOR_ARCHITEW6432'];
	}

}
