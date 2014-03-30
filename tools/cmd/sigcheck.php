<?

class SigCheck {
	private $tool = '';
	
	public function __construct() {
	}
	
  public function init($params) {
    if(isset($params['home_dir'])) {
      $this->tool = '"'.$params['home_dir'].DIRECTORY_SEPARATOR.'sigcheck.exe"';
    } else {
      $this->tool = 'sigcheck.exe';
    }
  }
  
  public function check($file_in) {
  }
}
