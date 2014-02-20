<?

class BuildInfo {
	//private const VARIANT  = array('develop', 'production', 'debug');
	//private const PLATFORM = array('x32', 'x64');
	//private const OS_TYPE  = array('win');
	
	private $variant;
	private $platform;
	private $os_type;

	public function set($os_type, $platform, $variant) {
		$this->os_type = $os_type;
		$this->platform = $platform;
		$this->variant = $variant;
	}
	public function getOS() {
		return $os_type;
	}
	public function getPlatform() {
		return $platform;
	}
	public function getVariant() {
		return $variant;
	}

}
