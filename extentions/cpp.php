<?

class cpp {
	static function getIncludes($filename) {
		$ret = array();
		return $ret;
	}
	
	private static find($str) {
		preg_match('@^(?:include)?(<|")($|\((?P<inc>(\(.*)))\))(>|");@i',  $str, $matches);
		vasr_dump($matches);
		//$include = $matches[1];
	}
}
