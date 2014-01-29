<?

class SignTool {
	private static $time_serv = '/t http://timestamp.verisign.com/scripts/timstamp.dll';
	public static function signDriver($dir) {
		exec('signtool.exe sign '.$dir, $output, $ret);
		$ret = '0';
		if(isset($output[0])) $ret = $output[0];
		return $ret;
	}
	public static function signCAT($dir) {
		exec('signtool.exe sign '.$dir, $output, $ret);
		$ret = '0';
		if(isset($output[0])) $ret = $output[0];
		return $ret;
	}
	public static function signMSI($dir) {
		exec('signtool.exe sign '.$dir, $output, $ret);
		$ret = '0';
		if(isset($output[0])) $ret = $output[0];
		return $ret;
	}
	public static function signEXE($dir) {
		exec('signtool.exe sign '.$dir, $output, $ret);
		$ret = '0';
		if(isset($output[0])) $ret = $output[0];
		return $ret;
	}
}
