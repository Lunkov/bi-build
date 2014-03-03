<?

class Utils {
	
	/*
	 * Calc lines into file
	 * $filename - file name
	 * */
	static public function getFileLines($filename) {
		$lines = 0;
		$f = fopen($filename, 'rb');
		if(is_resource($f)) {
			while (!feof($f)) {
				$lines += substr_count(fread($f, 8192), "\n");
			}
			fclose($f);
		} else {
			echo "ERROR: File '$filename' not found\n";
		}
		return $lines;
	}
	
	/*
	 * Get extention of file
	 * $filename - file name
	 * */
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
