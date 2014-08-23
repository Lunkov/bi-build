<?php

class BuildInfo {
  const DEBUG_ON    = 0x00000001;
  const DEBUG_OFF   = 0xFFFFFFFE;
  const ANALIZE_ON  = 0x00000002;
  const ANALIZE_OFF = 0xFFFFFFFD;
  const WARNING_ERR = 0x00000004;
  const WARNING_OFF = 0xFFFFFFFB;
  const X32         = 0x00000001;
  const X64         = 0x00000001;
  const IA64        = 0x00000001;
  const ARM         = 0x00000001;
  
	//private const VARIANT    = array('develop', 'production', 'debug');
	//private const PLATFORM   = array('x32', 'x64', 'arm');
	//private const OS_TYPE    = array('win', 'android', 'macos');
  //private const OS_VERSION = array('Server2008R2_X86', 'Server2008_X86', 'Server2003_X86', 'XP_X86', 'Vista_X86', '7_X86', '8_X86', 'Server2008R2_X64', 'Server2008_X64', 'Server2003_X64', 'XP_X64', 'Vista_X64', '7_X64', '8_X64', 'Server8_X64');
	private $variant;
	private $platform;
	private $os_type;
  private $os_version;
  // Path to release directory 
	private $release_path;
  // Path to build directory. For .obj, .lib and etc. files
	private $build_path;
  private $calc_build_path;

	public function set($os_type, $platform, $variant) {
		$this->os_type = $os_type;
		$this->platform = $platform;
		$this->variant = $variant;
    $this->build_path = Enviroment::getTemp();
    $this->setCalcBuildPath();
	}
	public function setOSVersion($os_version) {
		$this->os_version = $os_version;
	}
	public function setReleasePath($release_path) {
		$this->release_path = $release_path;
	}
	public function setBuildPath($build_path) {
		$this->build_path = $build_path.DIRECTORY_SEPARATOR.Enviroment::getUserName().DIRECTORY_SEPARATOR;
    $this->setCalcBuildPath();
	}
  public function getBuildPath() {
    return $this->calc_build_path;
  }
  private function setCalcBuildPath() {
    $this->calc_build_path = $this->build_path.
                              DIRECTORY_SEPARATOR.'build'.
                              DIRECTORY_SEPARATOR.$this->variant.
                              DIRECTORY_SEPARATOR.$this->os_type.
                              DIRECTORY_SEPARATOR.$this->platform;
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
	public function toString() {
    //return $this->variant. '.' .$this->os_type. '.' . $this->platform;
    return  'OS: '.$this->getOS()."\n".
            'Platform: '.$this->getPlatform()."\n".
            'Variant: '.$this->getVariant()."\n".
            'Build directory: '.$this->getBuildPath()."\n";
    
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
