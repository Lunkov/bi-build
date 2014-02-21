<?

class SignTool {
	private static $time_serv = '/t http://timestamp.verisign.com/scripts/timstamp.dll';
	private $tool = '';
	private $params = array();
	
	public function __construct($home_dir) {
		$this->tool = '"'.$this->home_dir.'signtool.exe"';
	}
	
	public function init($params) {
		$this->params = $params;
	}
	
	public function sign($file) {
		$flags = 'sign ';
		switch(Utils::getFileExtension($file)) {
		case 'sys':
				break;
		case 'cat':
				break;
		case 'dll':
				break;
		case 'exe':
				break;
		default:
				break;
		}
		exec($this->tool.' '.$flags.' '.self::$time_serv.' "'.$file.'"', $output, $ret);
		$ret = '0';
		if(isset($output[0])) $ret = $output[0];
		return $ret;
	}
}
