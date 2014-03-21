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
	 * $filename - full name of file
	 * */
	static public function getFileExtension($filename) {
		return pathinfo($filename, PATHINFO_EXTENSION);
	}

	/*
	 * Get filename of file
	 * $filename - full name of file
	 * */
	static public function getFileName($filename) {
		return pathinfo($filename, PATHINFO_FILENAME);
	}

	/*
	 * Get path of file
	 * $filename - full name of file
	 * */
	static public function getPath($filename) {
		return pathinfo($filename, PATHINFO_DIRNAME);
	}

	/*
	 * Get path of file
	 * $filename - full name of file
	 * */
	static public function getRelativePath($home_dir, $filename) {
    $file_path = pathinfo($filename, PATHINFO_DIRNAME);
    $ret = '';
    if(strlen($file_path) > strlen($home_dir)) {
      $ret = substr($file_path, strlen($home_dir)+1, strlen($file_path) - strlen($home_dir));
    }
		return $ret;
	}

	/*
	 * change extention of file
	 * $filename - full name of file
	 * $new_extention - new extention of file
	 * */
	static public function changeExtention($filename, $new_extention) {
		return self::getPath($filename).DIRECTORY_SEPARATOR.self::getFileName($filename).$new_extention;
	}

	/*
	 * calc hash of file
	 * $filename - full name of file
	 * */
	static public function calcHash($filename) {
    echo 'FileCalc: \''.$filename."'\n";
		return md5_file($filename);
	}
	
  static public function escapeshellcmd($filename) {
    $cmd = str_replace(array('\\', '%'), array('\\\\', '%%'), $filename); 
    $cmd = escapeshellarg($cmd);    
    return $cmd;
  }
  
  static public function mkdir($dir) {
    if(!file_exists($dir)) {
			mkdir($dir, 0x0777, true);
		}
    return $dir;
  }
  
  static public function saveState($filename, $data) {
    file_put_contents($filename, json_encode($data));
  }

  static public function loadState($filename) {
    if(!file_exists($filename)) return null;
    $str = file_get_contents($filename);
    return json_decode($str);
  }  
}
