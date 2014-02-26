<?

include 'utils.php';
include 'enviroment.php';
include 'process.php';
include 'buildinfo.php';
include 'buildutils.php';

class Build {
	private static $instance;
	private static $release_path;
	private static $build_path;
	private static $variant  = array('develop', 'production', 'debug');
	private static $platform = array('x32', 'x64');
	private static $os_type  = array('win');
	private static $roots   = array();
	private static $projects_path = array();
	private static $order_roots   = array();
	private static $current_root = '';
	private static $output = array();
	private static $src_files_old = array();
	private static $src_files = array();
	private static $extentions = array();
	private static $manager;
	private static $buildinfo;
	private static $is_rebuild = true;

	private static $time_scan = 0;
	private static $time_work = 0;
  private static $cnt_targets = 0;
  private static $cnt_files = 0;
	private static $cnt_lines = 0;
	private static $cnt_tasks = 0;
  private static $cnt_errors = 0;
  private static $cnt_warnings = 0;
	
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
    Echo 'ERROR: Tool not found (tool='.$tool.")\n";
		return null;
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
    self::make_absolute_pathes();
    self::sort_roots();
		self::$time_scan = round(microtime(true) - $curTime,3)*1000; 
  }
  

  private function make_absolute_path_link($target_libs) {
    $lib = array();
    if(is_array($target_libs)) {
      foreach($target_libs as $lib_path) {
        //var_dump($lib_path);
        if(is_string($lib_path)) {
          $pos = strpos($lib_path, BuildUtils::ROOT_SEPARATOR);
          //echo 'lib_path='.$lib_path."\n";
          //echo 'pos='.$pos."\n";
          if($pos > 1) {
            $in = '';
            $rkey = substr($lib_path, 0, $pos);
            $pth  = substr($lib_path, $pos+1, strlen($lib_path)-1);
            //echo 'rkey='.$rkey."\n";
            //echo 'pth='.$pth."\n";
            if(isset(self::$roots[$rkey]['home_dir'])) {
              $in = self::$roots[$rkey]['home_dir'];
            }
            $in .= DIRECTORY_SEPARATOR.$pth;
            //echo '$in='.$in."\n";
            $in = realpath($in);
            //if(file_exists($in)) {
            $lib[] = $in;
            //} else {
            //  echo 'ERROR: Link path not exists: '.$in."\n";
            //  $lib[] = $lib_path;
            //}
          } else {
            $lib[] = $lib_path;
          }
        }
        if(is_array($lib_path)) {
          $lib[] = $lib_path;
        }
      }
    }
    return $lib;
  }
  
  private function make_absolute_pathes() {
    foreach(self::$roots as $rkey => $root) {
      echo '--root: '.$rkey."\n";
      foreach(self::$roots[$rkey]['targets'] as $tkey => $target) {
        echo '--target: '.$tkey."\n";
        self::$roots[$rkey]['targets'][$tkey]['link']    = self::make_absolute_path_link(self::$roots[$rkey]['targets'][$tkey]['link']);
        self::$roots[$rkey]['targets'][$tkey]['include'] = BuildUtils::make_absolute_path(self::$roots[$rkey]['targets'][$tkey]['include']);
      }
    }
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
    
    $sort_cnt_targets = 0;
    $order = 0;
    while(self::$cnt_targets > $sort_cnt_targets && self::$cnt_targets >= $order) {
      //echo 'Targets sorted count: '.$sort_cnt_targets."\n";
      //echo 'Order: '.$order."\n";
      foreach(self::$roots as $rkey => $root) {
        foreach($root['targets'] as $tkey => $target) {
          $req = false;
          //echo 'target: '.$tkey."\n";
          if(is_array($target['link'])) {
            foreach($target['link'] as $link) {
							$path = BuildUtils::make_target_path($link['root'], $link['target']);
              //$exists = isset($sort_root[$link['root']][$link['target']]['order']);
              $exists = isset(self::$order_roots[$path]);
              //echo 'link:'.$link['root'].'+'.$link['target'].' found='.$exists."\n";
              if(isset($link['root']) && isset($link['target']) && !$exists) {
                $req = true;
                break;
              }
            }
          }
          // links not found. build at first stage
          if(!$req) {
						$path = BuildUtils::make_target_path($rkey, $tkey);
						if(!isset(self::$order_roots[$path])) {
							self::$order_roots[$path] = $order;
						}
          }
        }
      }
      $order++;
    }
    var_dump(self::$order_roots);
    if(self::$cnt_targets!=count(self::$order_roots)) {
			echo "===========!!! ERROR !!!==========\n";
      echo 'Scan targets: '.self::$cnt_targets."\n";
      echo 'Sort targets: '.count(self::$order_roots)."\n";
      foreach(self::$roots as $rkey => $root) {
        foreach($root['targets'] as $tkey => $target) {
          if(is_array($target['link'])) {
            foreach($target['link'] as $link) {
							if(!isset(self::$roots[$link['root']]['targets'][$link['target']])) {
								$path1 = BuildUtils::make_target_path($rkey, $tkey);
								$path2 = BuildUtils::make_target_path($link['root'], $link['target']);
								echo 'Target \''.$path2.'\' not found in \''.$path1."'\n";
              }
            }
          }
				}
			}
			echo "==================================\n";
    }
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
		self::$roots[$root_name]['targets'] = array();
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
		if(isset(self::$roots[$root_name]['home_dir'])) {
			return self::$roots[$root_name]['home_dir'];
		}
		return '';
	}
	
	public function reg_target($target_name, $params) {
     $rp = realpath($params['home_dir']);
	   $params['dir'] = substr($rp, strlen(self::$roots[self::$current_root]['home_dir'])-strlen($rp));
	   $params['short_name'] = $target_name;
     $params['root'] = self::$current_root;
     //search src-files by mask: *.cpp, etc
     $src = array();
     foreach($params['src'] as $file) {
       $src = array_merge($src, glob($params['home_dir'].DIRECTORY_SEPARATOR.$file, GLOB_NOCHECK));
     }
     $params['src'] = $src;
     $tname = $params['dir'].BuildUtils::TARGET_SEPARATOR.$target_name;
	   echo 'Target: '.$tname."\n";
	   self::$roots[self::$current_root]['targets'][$tname] = $params;
	}

	public function reg_unit_test($test_name, $params) {
     $params['make'] = 'console_exe';
     $params['utest'] = true;
     self::reg_target($target_name, $params);
	}
  
	public function getTarget($root, $target) {
		if(isset(self::$roots[$root]['targets'][$target])) {
			return self::$roots[$root]['targets'][$target];
		}
    Echo 'ERROR: Target not found (root='.$root.', target='.$target.")\n";
		return null;
	}
	
  public function link2Target($root, $target) {
		return array('root' =>$root, 'target'=>$target);
	}
  
	private function build() {
		//var_dump(self::$os_type);
		//var_dump(self::$variant);
		//var_dump(self::$platform);
		//var_dump(self::$roots);
		//echo 'Targets: '.count(self::$targets)."\n";
    $curTime = microtime(true);
		foreach(self::$os_type as $os) {
			echo 'OS: '.$os."\n";
			
			foreach(self::$variant as $vardev) {
				echo 'Variant: '.$vardev."\n";
				
				foreach(self::$platform as $pl) {
					echo 'Platform: '.$pl."\n";
					self::$buildinfo->set($os, $pl, $vardev);
					
					$build_dir = self::$build_path.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.$os.DIRECTORY_SEPARATOR.$vardev.DIRECTORY_SEPARATOR.$pl;
					
					foreach(self::$order_roots as $key => $order) {
						echo 'Target: '.$key."\n";
						
						$root = BuildUtils::get_root_name($key);
						$target = BuildUtils::get_target_name($key);
						
						$tg = self::$roots[$root]['targets'][$target];

						echo 'Build folder: '.$build_dir.$tg['dir']."\n";
						
						if(class_exists($tg['tool'])) {
							if(method_exists($tg['tool'], $tg['make'])) {
								$m = $tg['make'];
								self::$tools[$tg['tool']]->$m(self::$buildinfo, $build_dir, $key, self::$roots[$root]['targets'][$target]);
							} else {
								echo 'ERROR: Action \''. $tg['make'].'\' in \''.$tg['tool']."' not found\n";
							}
						} else {
							echo 'ERROR: Tool \''.$tg['tool']."' not found\n";
						}
					}
					
					/*
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
                } else {
                  echo 'ERROR: Action \''. $target['make'].'\' in \''.$target['tool']."' not found\n";
                }
							} else {
                echo 'ERROR: Tool \''.$target['tool']."' not found\n";
              }
						  
						}
					}
*/
				}
			}
		}
    self::$time_work = round(microtime(true) - $curTime,3)*1000; 
	}

	public function exec() {
	
		switch($_GET['do']) {
		case 'rebuild':
			self::setRebuild(true);
			self::find_roots(self::$projects_path);
			self::save_roots();
			self::build();
			break;
		case 'build':
			self::setRebuild(false);
			self::load_roots();
			self::build();
			break;
		case 'stat':
			self::find_roots(self::$projects_path);
			self::save_roots();
			self::stat();
			break;
		case 'check':
			self::setRebuild(true);
			self::load_roots();
			self::build();
			self::check();
			break;	
		case 'clear':
			self::clear();
			break;
		}
		self::print_timers();
	}
	
  public function isRebuild() {
    return self::$is_rebuild;
  }

  public function setRebuild($is_rebuild) {
    self::$is_rebuild = $is_rebuild;
  }
  
  public function incTask() {
    self::$cnt_tasks++;
  }

  public function incError($inc = 1) {
    self::$cnt_errors+=$inc;
  }

  public function incWarning($inc = 1) {
    self::$cnt_warnings+=$inc;
  }
  
	private function stat() {
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
  
	private function print_timers() {
    echo "============================================\n";
		echo 'Tasks: '.(self::$cnt_tasks)."\n";
    echo 'Erros: '.(self::$cnt_errors)."\n";
    echo 'Warnings: '.(self::$cnt_warnings)."\n";
    echo "============================================\n";
		echo 'Scan time: '.(self::$time_scan / 1000).' s'."\n";
    echo 'Work time: '.(self::$time_work / 1000).' s'."\n";
    foreach(self::$tools as $tkey => $tool) {
      if(class_exists($tkey)) {
        if(method_exists($tkey, 'printTimers')) {
          self::$tools[$tkey]->printTimers();
        }
      }
    }
	}
	private function clear() {
		rmdir(self::$build_path);
	}
	public function setReleasePath($release_path) {
		self::$release_path = $release_path;
	}
	public function setBuildPath($build_path) {
		self::$build_path = $build_path;
	}
	public function setProjectsPath($projects_path) {
		self::$projects_path = $projects_path;
	}

}
