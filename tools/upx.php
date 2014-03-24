<?

class Upx {
	private $tool = '';
	private $params = array();
	
	public function __construct() {
	}
	
	public function init($params) {
		$this->params = $params;
    if(isset($params['home_dir'])) {
      $this->tool = '"'.$params['home_dir'].DIRECTORY_SEPARATOR.'upx.exe"';
    } else {
      $this->tool = 'upx.exe';
    }
	}
  
  public function compress($file_in, $file_out) {
    exec($this->tool.' -9 -o "'.$file_out.'" "'.$file_in.'"', $output, $ret);
		$ret = '0';
		if(isset($output[0])) $ret = $output[0];
		return $ret;    
  }
}
