<?

include 'utils.php';
include 'enviroment.php';
include 'process.php';
include 'buildinfo.php';

class Build {
	private static $instance;
	private static $release_path;
	private static $build_path;
	private static $variant  = array('develop', 'production', 'debug');
	private static $platform = array('x32', 'x64');
	private static $os_type  = array('win');
	private static $roots   = array();
	private static $current_root = '';
	private static $output = array();
	private static $src_files_old = array();
	private static $src_files = array();
	private static $extentions = array();
	private static $manager;
	private static $buildinfo;

	private static $time_scan = 0;
	private static $time_work = 0;
  private static $cnt_targets = 0;
  private static $cnt_files = 0;
	private static $cnt_lines = 0;
	
	private static $tasks = array();
	private static $tools = array();

	
	private function __construct() {
		self::$manager = new ProcessManager();
		self::$buildinfo  = new BuildInfo();
	}

    public static function get()
    {
        if (!isset(self::$instance)) {
            $className = __CLASS__;
            self::$instance = new $className;
        }
        return self::$instance;
    }
	
	function define_params() {
		if (defined('STDIN')) {
			Global $argv;
			foreach ($argv as $arg) {
				$e=explode("=",$arg);
				if(count($e)==2)
					$_GET[$e[0]]=$e[1];
				else    
					$_GET[$e[0]]=0;
			}
		}

		if(isset($_GET['os_type'])) {
			self::$os_type = $_GET['os_type'];
		}
		if(isset($_GET['platform'])) {
			self::$platform = $_GET['platform'];
		}
		if(isset($_GET['variant'])) {
			self::$variant = $_GET['variant'];
		}
		//if(!isset($variant))  $variant  = array('develop', 'production', 'debug');
		//if(!isset($platform)) $platform = array('x32', 'x64');
		//if(!isset($os_type))  $os_type  = array('win');
		
		//print_r(self::$variant);
		//print_r(self::$platform);
		//print_r(self::$os_type);
	}

	public function use_tool($tool, $params) {
		require_once __DIR__.'/../tools/'.$tool.'.php';
		if(class_exists($tool)) {
			self::$tools[$tool] = new $tool();
			if(method_exists($tool, 'init')) {
				self::$tools[$tool]->init($params);
			}
		}
	}
	
	public function get_tool($tool) {
		if(isset(self::$tools[$tool])) {
			return self::$tools[$tool];
		}
		return null;
	}

	public function getTarget($root, $target) {
		if(isset(self::$roots[$root][$target])) {
			return self::$roots[$root][$target];
		}
		return null;
	}
	
  public function link2Target($root, $target) {
		return array('root' =>$root, 'target'=>$target);
	}
  
  public function link2ShareLib($lib) {
		return array('sharelib'=>$target);
	}  
  
	function addScript($params) {
		self::$manager->addScript($params);
	}
	function execScripts() {
		self::$manager->exec();
	}
	
  public function set_roots($rt) {
    self::$roots = $rt;
  }
  	
	public function find_roots($sources) {
		$curTime = microtime(true);
		$display = Array ( 'bi.root', 'bi.config' );
		foreach($sources as $source) {
		  echo 'Scan: '.$source."\n";
			if ($handle = opendir($source)) {
				while (false !== ($entry = readdir($handle))) {
					if ($entry != "." && $entry != "..") {
						if (in_array(basename($entry), $display)) {
						  echo 'include: '.$entry . "\n";
						  include($source.DIRECTORY_SEPARATOR.$entry);
						}			
					}
				}
				closedir($handle);
			}
		}
    self::sort_roots();
		self::$time_scan = round(microtime(true) - $curTime,3)*1000; 
  }
  
  private function sort_roots() {
    $sort_root = array();
    self::$cnt_targets = 0;
    self::$cnt_files = 0;
    foreach(self::$roots as $rkey => $root) {
      foreach($root['targets'] as $tkey => $target) {
        self::$cnt_targets++;
        self::$cnt_files+=count($target['files']);
      }
    }
    echo 'Targets count: '.$cnt_targets."\n";
    /*
    $sort_cnt_targets = 0;
    $order = 0;
    while(self::$cnt_targets > $sort_cnt_targets && self::$cnt_targets >= $order) {
      echo 'Targets sorted count: '.$sort_cnt_targets."\n";
      echo 'Order: '.$order."\n";
      foreach(self::$roots as $rkey => $root) {
        foreach($root['targets'] as $tkey => $target) {
          $req = false;
          echo 'target: '.$tkey."\n";
          if(is_array($target['link'])) {
            foreach($target['link'] as $link) {
              $exists = isset($sort_root[$link['root']][$link['target']]['order']);
              echo 'link:'.$link['root'].'+'.$link['target'].'='.$exists."\n";
              if(isset($link['root']) && isset($link['target']) && !$exists) {
                $req = true;
                break;
              }
            }
          }
          // links not found. build at first stage
          if(!$req) {
            /*
            if(!isset($sort_root[$rkey])) {
              $rt = $root;
              unset($rt['targets']);
              $sort_root[$rkey]=$rt;
            }
            if(!isset($sort_root[$rkey]['targets'][$tkey])) {
              $sort_root[$rkey]['targets'][$tkey] = $target;
              $sort_cnt_targets++;
              $sort_root[$rkey]['targets'][$tkey]['order'] = $order;
            }//
            $sort_cnt_targets++;
            $sort_root[$rkey]['targets'][$tkey]['order'] = $order;
          }
        }
      }
      $order++;
    }
    var_dump(self::$roots);
    * */
  }
  
  public function save_roots() {
    if(file_exists(self::$build_path.DIRECTORY_SEPARATOR.'build.php')) {
      rename(self::$build_path.DIRECTORY_SEPARATOR.'build.php', self::$build_path.DIRECTORY_SEPARATOR.'build_old.php');
    }
    $context = 'Build::get()->set_roots(json_decode(\''.json_encode(self::$roots).'\'));';
    file_put_contents(self::$build_path.DIRECTORY_SEPARATOR.'build.php', $context);
  }

  public function load_roots() {
    if(file_exists(self::$build_path.DIRECTORY_SEPARATOR.'build.php')) {
      include self::$build_path.DIRECTORY_SEPARATOR.'build.php';
    }
  }
  
	public function reg_root($root_name, $params) {
		self::$current_root = $root_name;
		self::$roots[$root_name] = $params;
		self::$roots[$current_root]['targets'] = array();
		if(isset($params['sources'])) {
			$display = Array ( 'bi.root', 'bi.config' );
			foreach($params['sources'] as $source) {
			  echo 'Scan targets: '.$params['home_dir'].DIRECTORY_SEPARATOR.$source."\n";
			  $it = new RecursiveDirectoryIterator($params['home_dir'].DIRECTORY_SEPARATOR.$source);
			  foreach(new RecursiveIteratorIterator($it) as $file) {
          if (in_array(basename($file), $display)) {
            echo 'include: '.$file . "\n";
            include($file);
          }
			  }
			}
		}
	}
  
	public function getRootHomeDir($root_name) {
		if(isset(self::$roots[$root_name][ 'home_dir'])) {
			return self::$roots[$root_name][ 'home_dir'];
		}
		return '';
	}
	
	public function reg_target($target_name, $params) {
     $rp = realpath($params['home_dir']);
	   $params['dir'] = substr($rp, strlen(self::$roots[self::$current_root]['home_dir'])-strlen($rp));
	   $params['short_name'] = $target_name;
     $src = array();
     foreach($params['src'] as $file) {
       $src = array_merge($src, glob($params['home_dir'].DIRECTORY_SEPARATOR.$file, GLOB_NOCHECK));
     }
     $params['src'] = $src;
	   echo 'Target: '.$params['dir'].'//'.$target_name."\n";
	   self::$roots[self::$current_root]['targets'][$params['dir'].'//'.$target_name] = $params;
	}

	public function exec() {
		//var_dump(self::$os_type);
		//var_dump(self::$variant);
		//var_dump(self::$platform);
		//var_dump(self::$roots);
		//echo 'Targets: '.count(self::$targets)."\n";
		foreach(self::$os_type as $os) {
			echo 'OS: '.$os."\n";
			
			foreach(self::$variant as $vardev) {
				echo 'Variant: '.$vardev."\n";
				
				foreach(self::$platform as $pl) {
					echo 'Platform: '.$pl."\n";
					self::$buildinfo->set($os, $pl, $vardev);
					
					foreach(self::$roots as $rkey => $root) {
						echo 'Root: '.$rkey."\n";
						
						foreach($root['targets'] as $tkey => $target) {
						  echo 'Target: '.$tkey."\n";

						  $build_dir = self::$build_path.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.$os.DIRECTORY_SEPARATOR.$vardev.DIRECTORY_SEPARATOR.$pl;
              //mkdir($build_dir, 0777);
						  echo 'Build folder: '.$build_dir.$target['dir']."\n";
						  
						  if(class_exists($target['tool'])) {
								if(method_exists($target['tool'], $target['make'])) {
                  $m = $target['make'];
									self::$tools[$target['tool']]->$m(self::$buildinfo, $build_dir, $key, $target);
								}
							}
						  
						}
					}
				}
			}
		}
	}

	public function stat() {
    $curTime = microtime(true);
    self::$cnt_targets = 0;
    self::$cnt_files = 0;
    self::$cnt_lines = 0;
    foreach(self::$roots as $rkey => $root) {
      foreach($root['targets'] as $tkey => $target) {
        self::$cnt_targets++;
        self::$cnt_files+=count($target['src']);
        foreach($target['src'] as $file) {
          self::$cnt_lines+=Utils::getFileLines($file);
        }
      }
    }
    self::$time_work = round(microtime(true) - $curTime,3)*1000; 
    echo 'Targets: '.self::$cnt_targets."\n";
    echo 'Files: '.self::$cnt_files."\n";
    echo 'Lines: '.self::$cnt_lines."\n";
  }
  
	public function printTimers() {
		echo 'Scan time: '.self::$time_scan.' ms'."\n";
    echo 'Work time: '.self::$time_work.' ms'."\n";
    foreach(self::$tools as $tkey => $tool) {
      if(class_exists($tkey)) {
        if(method_exists($tkey, 'printTimers')) {
          self::$tools[$tkey]->printTimers();
        }
      }
    }
	}
	
	public function setReleasePath($release_path) {
		self::$release_path = $release_path;
	}
	public function setBuildPath($build_path) {
		self::$build_path = $build_path;
	}

}
