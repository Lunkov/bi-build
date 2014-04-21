<?php

class Utils {
	
	/*
	 * Calc lines into file
	 * @param string $filename - file name
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
	 * @param string $filename - full name of file
	 * */
	static public function getFileExtension($filename) {
		return pathinfo($filename, PATHINFO_EXTENSION);
	}

	/*
	 * Get filename of file
	 * @param string $filename - full name of file withot extension
	 * */
	static public function getFileName($filename) {
		return pathinfo($filename, PATHINFO_FILENAME);
	}

	/*
	 * Get filename of file
	 * @param string $filename - full name of file
   * @return string
	 * */
	static public function getBaseName($filename) {
		return pathinfo($filename, PATHINFO_BASENAME);
	}

	/*
	 * Get path of file
	 * @param string $filename - full name of file
   * @return string
	 * */
	static public function getPath($filename) {
		return pathinfo($filename, PATHINFO_DIRNAME);
	}

	/*
	 * Get path of file
	 * @param string $filename - full name of file
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
	 * @param string $filename - full name of file
	 * @param string $new_extention - new extention of file
	 * */
	static public function changeExtention($filename, $new_extention) {
		return self::getPath($filename).DIRECTORY_SEPARATOR.self::getFileName($filename).$new_extention;
	}

	/*
	 * calc hash of file
	 * @param string $filename - full name of file
	 * */
	static public function calcHash($filename) {
    //echo 'FileCalc: \''.$filename."'\n";
		return md5_file($filename);
	}
	
  static public function escapeshellcmd($filename) {
    $cmd = str_replace(array('\\', '%'), array('\\\\', '%%'), $filename); 
    return escapeshellarg($cmd);
  }
  
  static public function mkdir($dir) {
    if(!file_exists($dir)) {
      if (!@mkdir($dir, 0x0777, true)) {
        $error = error_get_last();
        Logger::get()->out(Logger::Error, $error['message']);
      }
		}
    return realpath($dir);
  }
  
  static public function rmdir($dir) {
     if (is_dir($dir)) {
       $objects = scandir($dir);
       foreach ($objects as $object) {
         if ($object != "." && $object != "..") {
           if (filetype($dir."/".$object) == "dir") {
             rmdir($dir."/".$object);
           } else {
             unlink($dir."/".$object);
           }
         }
       }
       reset($objects);
       rmdir($dir);
     }
   }
     
  /**
   * Copy a file, or recursively copy a folder and its contents
   * @param       string   $source    Source path
   * @param       string   $dest      Destination path
   * @param       string   $permissions New folder creation permissions
   * @return      bool     Returns true on success, false on failure
   */
  function xcopy($source, $dest, $permissions = 0755)
  {
      // Check for symlinks
      if (is_link($source)) {
          return symlink(readlink($source), $dest);
      }

      // Simple copy for a file
      if (is_file($source)) {
          return copy($source, $dest);
      }

      // Make destination directory
      if (!is_dir($dest)) {
          mkdir($dest, $permissions);
      }

      // Loop through the folder
      $dir = dir($source);
      while (false !== $entry = $dir->read()) {
          // Skip pointers
          if ($entry == '.' || $entry == '..') {
              continue;
          }

          // Deep copy directories
          xcopy("$source/$entry", "$dest/$entry");
      }

      // Clean up
      $dir->close();
      return true;
  }
  
  static public function saveState($filename, $data) {
    try {
      @file_put_contents($filename, json_encode($data));
    } catch (Exception $e) {
      Logger::get()->out(Logger::Error, $e->getMessage());
    }
  }

  static public function loadState($filename) {
    if(!file_exists($filename)) {
      return null;
    }
    $str = file_get_contents($filename);
    return json_decode($str);
  }  
}
