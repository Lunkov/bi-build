<?php

class BuildUtils {
	// Format full target name is "project:target@short_name"
  const PROJECT_SEPARATOR   = ':';
  const TARGET_SEPARATOR = '@';

	/*
	 * Make full name of target
	 * $project - project name of target ( project )
	 * $target  - name of target ( target@short_name )
	 * */	
	public static function makeTargetPath($project, $target) {
		return $project . self::PROJECT_SEPARATOR . $target;
	}
	
	/*
	 * Get project name from target name
	 * $targetname - full name of target ( project:target@short_name )
	 * */	
	public static function getProjectName($targetname) {
		$ret = '';
		$pos = strpos($targetname, self::PROJECT_SEPARATOR);
		if($pos > 1) {
			$ret = substr($targetname, 0, $pos);
		}
		return $ret;
	}
	
	/*
	 * Get target name without project name
	 * $targetname - full name of target ( project:target@short_name )
	 * */	
	public static function getTargetName($targetname) {
		$ret = '';
		$pos = strpos($targetname, self::PROJECT_SEPARATOR);
		if($pos > 1) {
			$ret = substr($targetname, $pos+1, strlen($targetname)-1);
		}
		return $ret;
	}
		
  public static function make_absolute_path($pathes) {
    $ret = array();
    if(is_array($pathes)) {
      foreach($pathes as $include) {
				//var_dump($include);
        $pos = strpos($include, self::PROJECT_SEPARATOR);
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

	/*
	 * Convert array to string
	 * $data - array of strings
	 * $str_begin - separator before value of string
	 * $str_end - separator after value of string
	 * */	
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

	public static function getFileByExt($data, $ext) {
		if(is_array($data)) {
			foreach($data as $file) {
        if(Utils::getFileExtension($file) == $ext) {
          return $file;
        }
			}
		}
		if(is_string($data)) {
        if(Utils::getFileExtension($data) == $ext) {
          return $data;
        }
		}
		return null;
	}

  public static function getNumberOfParameters($cl, $func) {
    $method = new ReflectionMethod($cl, $func);
    return $method->getNumberOfParameters();
  }
}
