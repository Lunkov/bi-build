<?

class Enviroment {
  private $os;
  private $platform;
  private $variant;
  
  public function setBuildParams($os, $platform, $variant) {
    $this->os = os;
    $this->platform = platform;
    $this->variant = variant;
  }
	public function getBuildPlatform() {
    return $this->platform;
  }
  public function getBuildOS() {
		return $this->os;
	}  
  public function getBuildVariant() {
		return $this->variant;
	}  
	public function getPlatform() {
		return $_SERVER['PROCESSOR_ARCHITEW6432'];
	}
  
	public function getOS() {
		return $_SERVER['OS'];
	}
}
