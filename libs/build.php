<?

include 'utils.php';
include 'enviroment.php';
include 'process.php';
include 'buildinfo.php';
include 'buildutils.php';
include 'files.php';
include 'timers.php';

class Build {
  // Instance of singleton class
	private static $instance;
  // Path to release directory 
	private static $release_path;
  // Path to build directory. For .obj, .lib and etc. files
	private static $build_path;
  // Target for build
  private static $make_target = null;
  // All roots
	private static $roots   = array();
  // All sorted roots
	private static $order_roots   = array();
  // Make Plan
	private static $make_roots   = array();
  
	private static $variant  = array('develop', 'production', 'debug');
	private static $platform = array('x32', 'x64');
	private static $os_type  = array('win');
	private static $projects_path = array();
	private static $current_root = '';
	private static $output = array();
	private static $extentions = array();
  // Files
  private static $files = null;
  //Users configs for projects
  // type:
  // $params['params_name'] = array();
  private static $params = array();
  
	private static $manager;
	private static $buildinfo;
	private static $is_rebuild = true;

	private static $time_scan = 0;
	private static $time_work = 0;
	private static $time_check = 0;
  private static $cnt_targets = 0;
  private static $cnt_files = 0;
	private static $cnt_lines = 0;
	private static $cnt_tasks = 0;
  private static $cnt_errors = 0;
  private static $cnt_warnings = 0;
  private static $cnt_ok = 0;
	
	private static $tasks = array();
	private static $tools = array();

	private function __construct() {
		self::$manager = new ProcessManager();
		self::$buildinfo  = new BuildInfo();
    self::$files = new Files();
	}

  public static function get()
  {
      if (!isset(self::$instance)) {
          $className = __CLASS__;
          self::$instance = new $className;
      }
      return self::$instance;
  }
	
  private function __clone() { }
  private function __sleep() { }
  private function __wakeup() { }
      
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

	public function useTool($tool, $params) {
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

	function addScript($params) {
		self::$manager->addScript($params);
	}
	function execScripts() {
		self::$manager->exec();
	}
	function execScript($run) {
		self::$manager->exec();
	}
  	
	public function find_roots($sources) {
		Timers::get()->start('find_roots');
		$display = Array ( 'bi.root', 'bi.config' );
		foreach($sources as $source) {
		  echo 'Scan: '.$source."\n";
			if ($handle = opendir($source)) {
				while (false !== ($entry = readdir($handle))) {
					if ($entry != "." && $entry != "..") {
						if (in_array(basename($entry), $display)) {
						  //echo 'include: '.$entry . "\n";
						  include($source.DIRECTORY_SEPARATOR.$entry);
						}			
					}
				}
				closedir($handle);
			}
		}
    self::make_absolute_pathes();
    self::sort_roots();
		Timers::get()->stop('find_roots');
  }
  

  private function make_absolute_path_link($target_libs) {
    $lib = array();
    if(is_array($target_libs)) {
      foreach($target_libs as $lib_path) {
        //var_dump($lib_path);
        if(is_string($lib_path)) {
          $pos = strpos($lib_path, BuildUtils::PROJECT_SEPARATOR);
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
      //echo '--root: '.$rkey."\n";
      foreach(self::$roots[$rkey]['targets'] as $tkey => $target) {
        //echo '--target: '.$tkey."\n";
        //self::$roots[$rkey]['targets'][$tkey]['link']    = self::make_absolute_path_link(self::$roots[$rkey]['targets'][$tkey]['link']);
        self::$roots[$rkey]['targets'][$tkey]['include'] = BuildUtils::make_absolute_path(self::$roots[$rkey]['targets'][$tkey]['include']);
      }
    }
  }
  
  private function sort_roots() {
    echo "Sort targets...\n";
    $prn = false;
    ob_start();
    
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

    foreach(self::$roots as $rkey => $root) {
      foreach($root['req'] as $tkey => $req) {
        self::$cnt_targets++;
        $path = BuildUtils::makeTargetPath($rkey, $tkey);
        self::$order_roots[$path]['order'] = $order;
      }
    }
    $order++;
    //var_dump(self::$order_roots);
    $added = 1;
    //&& self::$cnt_targets >= $order
    while(self::$cnt_targets > count(self::$order_roots) && $added>0) {
      //echo 'Targets sorted count: '.$sort_cnt_targets."\n";
      //echo 'Order: '.$order."\n";
      $added = 0;
      foreach(self::$roots as $rkey => $root) {
        
        foreach($root['targets'] as $tkey => $target) {
          
          $fullpath = BuildUtils::makeTargetPath($rkey, $tkey);
          if(!isset(self::$order_roots[$fullpath])) {
            
            $depends = true;
            //var_dump($target);
            echo 'CHECK target: '.$rkey.':'.$tkey."\n";
            if(is_array($target['link'])) {
              foreach($target['link'] as $link) {
                //$path = BuildUtils::makeTargetPath($link['root'], $link['target']);
                $exists = isset(self::$order_roots[$link]);
                echo "\t".'link'.($exists?'+':'-').' '.$link."\n";
                if(!$exists) {
                  $depends = false;
                  break;
                }
              }
            }
            // links not found. build at first stage
            if($depends) {
              self::$order_roots[$fullpath]['order'] = $order;
              $added++;
              echo "\t".'ADD:  '.$fullpath."\n";
            }
          }
        }
      }
      //echo 'added='.$added."\n";
      $order++;
    } // end while
    
    if(self::$cnt_targets!=count(self::$order_roots)) {
      $prn = true;
			echo "===========!!! ERROR !!!==========\n";
      echo 'Scan targets: '.self::$cnt_targets."\n";
      echo 'Sort targets: '.count(self::$order_roots)."\n";
      foreach(self::$roots as $rkey => $root) {
        foreach($root['targets'] as $tkey => $target) {
          if(is_array($target['link'])) {
						//var_dump($target['link']);
            foreach($target['link'] as $link) {
							//var_dump($link);
							//$link_root = BuildUtils::getProjectName($link);
							//$link_target = BuildUtils::getTargetName($link);
							//if(!isset(self::$roots[$link_root]['targets'][$link_target])) {
              if(!isset(self::$order_roots[$link])) {
								$path1 = BuildUtils::makeTargetPath($rkey, $tkey);
								//$path2 = BuildUtils::makeTargetPath($link['root'], $link['target']);
								$path2 = $link;
								echo 'Target \''.$path2.'\' not found (by \''.$path1."')\n";
              }
            }
          }
				}
			}
			echo "==================================\n";
      var_dump(self::$order_roots);
    }
    $pngString = ob_get_contents();
    ob_end_clean();
    if($prn) echo $pngString;
  }

  private function build_plan() {
    echo "Build plan...\n";
    if(!isset(self::$make_target)) {
      self::$make_roots = self::$order_roots;
      return;
    }
    $rec = false;
    foreach ( array_reverse(self::$order_roots) as $key => $val ) {
      if(stricmp($key, self::$make_target) == 0) {
        $rec = true;
      }
      if($rec) {
        self::$make_roots[$key] = $val;
      }
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
            //echo 'include: '.$file . "\n";
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

	private function reg($reg_type, $reg_name, $params) {
		if(isset($params['home_dir'])) {
			$rp = realpath($params['home_dir']);
			$params['dir'] = substr($rp, strlen(self::$roots[self::$current_root]['home_dir'])-strlen($rp));
      $tname = $params['dir'].BuildUtils::TARGET_SEPARATOR.$reg_name;
		} else {
      $tname = BuildUtils::TARGET_SEPARATOR.$reg_name;
		}
	  $params['short_name'] = $reg_name;
    $params['root'] = self::$current_root;
    
    //search src-files by mask: *.cpp, etc
    if(is_array($params['src'])) {
			$src = array();
			//var_dump($params['src']);
			foreach($params['src'] as $file) {
				if(isset($params['home_dir'])) {
					$src = array_merge($src, glob($params['home_dir'].DIRECTORY_SEPARATOR.$file, GLOB_NOCHECK));
				} else {
					$src = array_merge($src, glob($file, GLOB_NOCHECK));
				}
			}
			//var_dump($src);
			$params['src'] = $src;
		}
		
	  echo 'Target: '.BuildUtils::makeTargetPath(self::$current_root, $tname)."\n";
	  //var_dump($params);
	  self::$roots[self::$current_root][$reg_type][$tname] = $params;
	}
		
	public function reg_target($target_name, $params) {
		self::reg('targets', $target_name, $params);
	}

	public function reg_unit_test($test_name, $params) {
		//var_dump($params);
    $params['make'] = 'console_exe';
    $params['utest'] = true;
		self::reg('targets', 'unit_test_'.$test_name, $params);
	}
	
	public function reg_requirements($req_name, $params) {
		//var_dump($params);
    //$params['utest'] = true;
    self::reg('req', $req_name, $params);
	}
  
	public function getTarget($root, $target) {
		if(isset(self::$roots[$root]['targets'][$target])) {
			return self::$roots[$root]['targets'][$target];
		}
    Echo 'ERROR: Target not found (root='.$root.', target='.$target.")\n";
		return null;
	}
	
  public function getTargetByLink($fullink) {
    $root = BuildUtils::getProjectName($fullink);
    $target = BuildUtils::getTargetName($fullink);
		if(isset(self::$roots[$root]['targets'][$target])) {
			return self::$roots[$root]['targets'][$target];
		}
		if(isset(self::$roots[$root]['req'][$target])) {
			return self::$roots[$root]['req'][$target];
		}    
    Echo "ERROR: Target not found (root='$root', target='$target')\n";
		return null;
	}
  	
  public function setResult($target, $result) {
		Echo 'NOTICE: setResult (target='.$target.', result='.$result.")\n";
		self::$order_roots[$target]['result'] = $result;
		//var_dump(self::$order_roots);
	}

  public function getResult($target) {
		Echo 'NOTICE: getResult (target='.$target.")\n";
		if(isset(self::$order_roots[$target]['result'])) {
			return self::$order_roots[$target]['result'];
		}
		return null;
	}
	
	private function build() {
		//var_dump(self::$order_roots);
		//var_dump(self::$os_type);
		//var_dump(self::$variant);
		var_dump(self::$platform);
		//var_dump(self::$roots);
		//echo 'Targets: '.count(self::$targets)."\n";
    Timers::get()->start('build');
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
						
						$root = BuildUtils::getProjectName($key);
						$target = BuildUtils::getTargetName($key);
						
						$tg = self::$roots[$root]['targets'][$target];

						echo 'Build folder: '.$build_dir.$tg['dir']."\n";
						/*
             * Check for use cache library
             */
            if(isset($tg['cache'])) {
              $result = self::getCacheBuild(self::$buildinfo, $key, $tg['cache']);
              //echo '>>> FIND??? '.$result."\n";
              var_dump($result);
              if(is_string($result) && file_exists($result)) {
                //echo '>>> FIND: '.$result."\n";
                self::setResult($key, $result);
                continue;
              }
              if(is_array($result) && file_exists($result)) {
                $good = true;
                foreach($result as $r) {
                  if(!file_exists($r)) {
                    $good = false;
                  }
                }
                if($good) {
                  self::setResult($key, $result);
                  //echo '>>> FIND: '.$result."\n";
                  continue;
                }
              }
            }
            //echo 'Build... '.$key."\n";
            /*
             * Run build tool
             * 
             */
            if(isset($tg['tool'])) {
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
					}
					echo 'RESULT='.self::$order_roots[$key]['result']."\n";
				}
			}
		}
    Timers::get()->stop('build');
	}
	
  private function getCacheBuild($buildinfo, $target_name, $target_cache) {
    $tg = self::getTargetByLink($target_cache);
    if(!is_array($tg)) {
      echo "ERROR: Target cache '$target_cache' is not found (in '$target_name')\n";
      var_dump($tg);
      return null;
    }
    if(!isset($tg['result'])) {
      echo 'ERROR: Target cache \''.$target_name."' is not found result\n";
      return null;
    }
    //var_dump($buildinfo);
    //var_dump($tg['result']);
    foreach($tg['result'] as $key => $val) {
      if($buildinfo->checkBuild($key)) {
        echo 'NOTICE: Target cache \''.$target_name."' found sources '$val' \n";
        return $tg['home_dir'].DIRECTORY_SEPARATOR.$val;
      }
    }
  }
  
	private function check() {
		echo "================== CHECK ===================\n";
    Timers::get()->start('check');
		foreach(self::$os_type as $os) {
			echo 'OS: '.$os."\n";
			
			foreach(self::$variant as $vardev) {
				echo 'Variant: '.$vardev."\n";
				
				foreach(self::$platform as $pl) {
					echo 'Platform: '.$pl."\n";
					foreach(self::$order_roots as $key => $order) {
						echo 'Target: '.$key."\n";
						$root = BuildUtils::getProjectName($key);
						$target = BuildUtils::getTargetName($key);
						
						$tg = self::$roots[$root]['targets'][$target];
						
						if(isset($tg['utest']) && $tg['utest'] && 
							 isset(self::$order_roots['result']))	 {
								 if(is_string() &&
										file_exists(self::$order_roots['result'])) {
											$filename_log = Utils::changeExtention(self::$order_roots['result'], '.log');
											
											Build::get()->addScript(array(
														'home_dir' => Utils::getPath(self::$order_roots['result']),
														'script_name' => self::$order_roots['result'],
														'env' => null,
														'log_file' => $filename_log
														));
									}
						}
					}
					$this->execScripts();
				}
			}
		}
		Timers::get()->stop('check');
	}

	public function exec() {
    Timers::get()->start('exec');
		switch($_GET['do']) {
		case 'rebuild':
			self::setRebuild(true);
			self::find_roots(self::$projects_path);
			self::build();
			break;
		case 'build':
			self::setRebuild(false);
			self::loadState();
			self::build();
			break;
		case 'stat':
			self::find_roots(self::$projects_path);
			self::stat();
			break;
		case 'check':
			self::setRebuild(true);
			self::find_roots(self::$projects_path);
			//self::load_roots();
			self::build();
			self::check();
			break;	
		case 'clear':
			self::clear();
			break;
		}
		self::saveState();
    Timers::get()->stop('exec');
		self::printTimers();
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

  public function incOK() {
    self::$cnt_ok++;
  }

  public function incError($inc = 1) {
    self::$cnt_errors+=$inc;
  }

  public function incWarning($inc = 1) {
    self::$cnt_warnings+=$inc;
  }
  
	private function stat() {
    Timers::get()->start('stat');
    self::$cnt_targets = 0;
    self::$cnt_files = 0;
    self::$cnt_lines = 0;
    foreach(self::$roots as $rkey => $root) {
      foreach($root['targets'] as $tkey => $target) {
        self::$cnt_targets++;
        if(is_array($target['src'])) {
					self::$cnt_files+=count($target['src']);
					foreach($target['src'] as $file) {
						self::$cnt_lines+=Utils::getFileLines($file);
					}
				}
      }
    }
    Timers::get()->stop('stat');
    echo "============================================\n";
    echo 'Targets:   '.self::$cnt_targets."\n";
    echo 'Files:     '.self::$cnt_files."\n";
    echo 'Lines:     '.self::$cnt_lines."\n";
  }
  
	private function printTimers() {
    echo "============================================\n";
    echo 'Targets:   '.self::$cnt_targets."\n";
    if(self::$cnt_tasks > 0) {
			echo 'Tasks:     '.self::$cnt_tasks."\n";
			echo 'OK:        '.self::$cnt_ok."\n";
			echo 'Progress:  '.round(self::$cnt_ok*100/self::$cnt_tasks)."%\n";
			echo 'Erros:     '.self::$cnt_errors."\n";
			echo 'Warnings:  '.self::$cnt_warnings."\n";
		}
    echo "--- Timers ---------------------------------\n";
    Timers::get()->printAll();
   
    echo "============================================\n";
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

  public function saveState() {
    Utils::saveState(self::$build_path.DIRECTORY_SEPARATOR.'roots.order', self::$order_roots);
    Utils::saveState(self::$build_path.DIRECTORY_SEPARATOR.'roots.cache', self::$roots);
    
    self::$files->saveState(self::$build_path.DIRECTORY_SEPARATOR.'files.stat');
  }

  public function loadState() {
    self::$roots = Utils::loadState(self::$build_path.DIRECTORY_SEPARATOR.'roots.cache');
    self::$order_roots = Utils::loadState(self::$build_path.DIRECTORY_SEPARATOR.'roots.order');
    
    self::$files->loadState(self::$build_path.DIRECTORY_SEPARATOR.'files.stat');
  }
  
  public function setRoots($rt) {
    self::$roots = $rt;
  }
  public function setTasks($rt) {
    self::$order_roots = $rt;
  }

  public function setParams($name, $param) {
    self::$params[$name] = $param;
  }

  public function getParams($name) {
    if(is_array(self::$params[$name])) {
      return self::$params[$name];
    }
    return array();
  }

  public function fileChanged($filename) {
    return self::$files->fileChanged($filename);
  }  	  
}
