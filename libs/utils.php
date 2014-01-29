<?

class Utils {
	static public function getFileLines($file) {
		$lines = 0;
		$f = fopen($file, 'rb');
		while (!feof($f)) {
			$lines += substr_count(fread($f, 8192), "\n");
		}
		fclose($f);
		return $lines;
	}
	
	static public function getFileExtension($filename) {
		return pathinfo($filename, PATHINFO_EXTENSION);
	}

	static public function getFileName($filename) {
		return pathinfo($filename, PATHINFO_FILENAME);
	}

	static public function calcHash($filename) {
		return md5_file($filename);
	}
	
}
