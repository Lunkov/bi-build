<?

class SVN {
	public static function getRevision($dir) {
		exec('svnversion '.$dir, $output, $ret);
		$ret = '0';
		if(isset($output[0])) $ret = $output[0];
		return $ret;
	}
}
