<?

class SignTool {
	private static $time_serv = '/t http://timestamp.verisign.com/scripts/timstamp.dll';
	
	public function init($params) {
	}
	
	public function sign($file) {
		$flags = 'sign ';
		switch(Utils::getFileExtension($file)) {
		case 'sys':
				break;
		default:
				break;
		}
		exec('"'.$this->home_dir.'signtool.exe" '.$flags.' '.self::$time_serv.' "'.$file.'"', $output, $ret);
		$ret = '0';
		if(isset($output[0])) $ret = $output[0];
		return $ret;
	}
}
