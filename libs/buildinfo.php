<?php

class BuildInfo {
	//private const VARIANT    = array('develop', 'production', 'debug');
	//private const PLATFORM   = array('x32', 'x64', 'arm');
	//private const OS_TYPE    = array('win', 'android', 'macos');
  //private const OS_VERSION = array('Server2008R2_X86', 'Server2008_X86', 'Server2003_X86', 'XP_X86', 'Vista_X86', '7_X86', '8_X86', 'Server2008R2_X64', 'Server2008_X64', 'Server2003_X64', 'XP_X64', 'Vista_X64', '7_X64', '8_X64', 'Server8_X64');
	
	private $variant;
	private $platform;
	private $os_type;
  private $os_version;

	public function set($os_type, $platform, $variant) {
		$this->os_type = $os_type;
		$this->platform = $platform;
		$this->variant = $variant;
	}
	public function setOSVersion($os_version) {
		$this->os_version = $os_version;
	}
	public function getOSVersion() {
		return $this->os_version;
	}
	public function getOS() {
		return $this->os_type;
	}
	public function getPlatform() {
		return $this->platform;
	}
	public function getVariant() {
		return $this->variant;
	}
  /*
   * Check build by mask: build.os.platform 
   * '*' - all
   */
  public function checkBuild($key) {
    $good = 0;
    $ar_key = explode('.', $key);
    if(isset($ar_key[0]) && ($ar_key[0] == '*' || $ar_key[0] == $this->getVariant())) { $good++; }
    if(isset($ar_key[1]) && ($ar_key[1] == '*' || $ar_key[1] == $this->getOS()))  { $good++; }
    if(isset($ar_key[2]) && ($ar_key[2] == '*' || $ar_key[2] == $this->getPlatform())) { $good++; }
    return 3 == $good;
  }
}
