<?

class BuildUtils {
  const ROOT_SEPARATOR   = ':';
  const TARGET_SEPARATOR = '@';

  public static function make_target_path($root, $target) {
		return $root . self::ROOT_SEPARATOR . $target;
	}
	public static function get_root_name($path) {
		$ret = '';
		$pos = strpos($path, self::ROOT_SEPARATOR);
		if($pos > 1) {
			$ret = substr($path, 0, $pos);
		}
		return $ret;
	}
	public static function get_target_name($path) {
		$ret = '';
		$pos = strpos($path, self::ROOT_SEPARATOR);
		if($pos > 1) {
			$ret = substr($path, $pos+1, strlen($path)-1);
		}
		return $ret;
	}
		
  public static function make_absolute_path($pathes) {
    $ret = array();
    if(is_array($pathes)) {
      foreach($pathes as $include) {
        $pos = strpos($include, self::ROOT_SEPARATOR);
        if($pos > 1) {
          $in = '';
          $rkey = substr($include, 0, $pos);
          $pth  = substr($include, $pos+1, strlen($include)-1);
          $in = Build::get()->getRootHomeDir($rkey);
          $in = $in.DIRECTORY_SEPARATOR.$pth;
          $inr = realpath($in);
          if(file_exists($inr)) {
            $ret[] = $inr;
          } else {
            echo 'ERROR: Include path not exists: \''.$in."'\n";
            $ret[] = $include;
          }
        } else {
          $in = realpath($include);
          if(file_exists($in)) {
            $ret[] = $in;
          } else {
            echo 'ERROR: Include path not exists: \''.$include."'\n";
            $ret[] = $include;
          }
        }
      }
    }
    return $ret;
  }

	public static function array2string($data, $str_begin, $str_end) {
		$ret = '';
		if(is_array($data)) {
			foreach($data as $p) {
				$ret.=$str_begin.$p.$str_end;
			}
		}
		if(is_string($data)) {
			$ret.=$str_begin.$data.$str_end;
		}
		return $ret;
	}

}
