<?php

include 'helpers/logger.php';
include 'helpers/utils.php';
include 'helpers/enviroment.php';
include 'helpers/files.php';
include 'helpers/timers.php';
include 'helpers/vars.php';
include 'helpers/target.php';
include 'buildinfo.php';
include 'buildutils.php';
include 'process/queue.php';
include 'process/processmanager.php';


class Build {
  private static $configFiles = Array ( 'bi.root', 'bi.config' );
  // Instance of singleton class
	private static $instance;
  // Target for build
  private static $make_target = null;
  // All roots
	private static $roots   = array();
  // All targets
	private static $targets = array();
  // All sorted tasks
  private static $queue = null;
  // Make Plan
	private static $make_roots   = array();
  
	private static $variant  = array('develop', 'production', 'debug');
	private static $platform = array('x32', 'x64');
	private static $os_type  = array('win');
	private static $projects_path = array();
	private static $current_root = '';
  // Files
  private static $files = null;
  //Users configs for projects
  // type:
  // $params['params_name'] = array();
  private static $params = array();
  
	private static $manager;
	private static $buildinfo;
	private static $is_rebuild = true;

  private static $cnt_targets = 0;
  private static $cnt_files = 0;
	private static $cnt_lines = 0;
	private static $cnt_tasks = 0;
  private static $cnt_errors = 0;
  private static $cnt_warnings = 0;
  private static $cnt_ok = 0;
	
	private static $tools = array();

  private static $args = array();
  
	private function __construct() {
		self::$manager   = new ProcessManager();
		self::$buildinfo = new BuildInfo();
    self::$files = new Files();
    self::$queue = new Queue();
    
    self::regRoot('system', array(
                                'home_dir' => ''
                      ) 
                  );
	}

  public static function get() {
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
				if(count($e)==2) {
					self::$args[$e[0]]=$e[1];
        }	else {
					self::$args[$e[0]]=0;
        }
			}
		} else {
      self::$args = $_GET;
    }

		if(isset(self::$args['os_type'])) {
			self::$os_type = self::$args['os_type'];
		}
		if(isset(self::$args['platform'])) {
			self::$platform = self::$args['platform'];
		}
		if(isset(self::$args['variant'])) {
			self::$variant = self::$args['variant'];
		}
		if(isset(self::$args['target'])) {
      self::$make_target = self::$args['target'];
		}
		//if(!isset($variant))  $variant  = array('develop', 'production', 'debug');
		//if(!isset($platform)) $platform = array('x32', 'x64');
		//if(!isset($os_type))  $os_type  = array('win');
		
		//print_r(self::$variant);
		//print_r(self::$platform);
		//print_r(self::$os_type);
	}

  public function getQueue() {
    return $this->queue;
  }
  
	public function useTool($tool, $params) {
    if(file_exists(__DIR__.'/../tools/'.$tool.'.php')) {
      require_once __DIR__.'/../tools/'.$tool.'.php';
    }
    if(file_exists(__DIR__.'/../tools/cmd/'.$tool.'.php')) {
      require_once __DIR__.'/../tools/cmd/'.$tool.'.php';
    }
		if(class_exists($tool)) {
			self::$tools[$tool] = new $tool();
			if(method_exists($tool, 'init')) {
				self::$tools[$tool]->init($params);
			}
		}
	}
	
	public function getTool($tool) {
		if(isset(self::$tools[$tool])) {
			return self::$tools[$tool];
		}
    Logger::get()->out(Logger::Alert, "Tool not found (tool=$tool)" );
		return null;
	}

	function addScript($task, $params) {
		self::$queue->addScript($task, $params);
	}
  	
	public function find_roots($sources) {
		Timers::get()->start('find_roots');
		$display = Array ( 'bi.root', 'bi.config' );
		foreach($sources as $source) {
      Logger::get()->out(Logger::Info, "Scan: $source");
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
        $target->makeAbsolutePathes();
        //echo '--target: '.$tkey."\n";
        //self::$roots[$rkey]['targets'][$tkey]['link']    = self::make_absolute_path_link(self::$roots[$rkey]['targets'][$tkey]['link']);
        //self::$roots[$rkey]['targets'][$tkey]['include'] = BuildUtils::make_absolute_path(self::$roots[$rkey]['targets'][$tkey]['include']);
      }
    }
  }
  
  private function sort_roots() {
    Logger::get()->out(Logger::Info, 'Sort targets...');
    $prn = false;
    ob_start();
    
    self::$cnt_targets = 0;
    self::$cnt_files = 0;
    foreach(self::$roots as $rkey => $root) {
      foreach($root['targets'] as $tkey => $target) {
        self::$cnt_targets++;
        self::$cnt_files+=count($target->getSrc());
      }
    }

    $order = 0;

    foreach(self::$roots as $rkey => $root) {
      if(isset($root['req']) && is_array($root['req'])) {
        foreach($root['req'] as $tkey => $req) {
          self::$cnt_targets++;
          $path = BuildUtils::makeTargetPath($rkey, $tkey);
          self::$queue->addTask($path, $order);
        }
      }
    }
    $order++;
    //var_dump(self::$order_roots);
    $added = 1;
    //&& self::$cnt_targets >= $order
    while(self::$cnt_targets > self::$queue->countTasks() && $added>0) {
      //echo 'Targets sorted count: '.$sort_cnt_targets."\n";
      //echo 'Order: '.$order."\n";
      $added = 0;
      foreach(self::$roots as $rkey => $root) {
        
        foreach($root['targets'] as $tkey => $target) {
          
          $fullpath = BuildUtils::makeTargetPath($rkey, $tkey);
          if(!self::$queue->exists($fullpath)) {
            
            $depends = true;
            //var_dump($target);
            Logger::get()->out(Logger::Debug, 'CHECK target: '.$rkey.':'.$tkey);
            if(is_array($target->getLinks())) {
              foreach($target->getLinks() as $link) {
                //$path = BuildUtils::makeTargetPath($link['root'], $link['target']);
                $exists = self::$queue->exists($link);
                Logger::get()->out(Logger::Debug, "\t".'link'.($exists?'+':'-').' '.$link);
                if(!$exists) {
                  $depends = false;
                  break;
                }
              }
            }
            // links not found. build at first stage
            if($depends) {
              self::$queue->addTask($fullpath, $order);
              $added++;
              Logger::get()->out(Logger::Debug, "\t".'ADD:  '.$fullpath);
            }
          }
        }
      }
      //echo 'added='.$added."\n";
      $order++;
    } // end while
    
    if(self::$cnt_targets!=self::$queue->countTasks()) {
      $prn = true;
      Logger::get()->out(Logger::Critical, '===========!!! ERROR !!!==========');
      Logger::get()->out(Logger::Critical, 'Scan targets: '.self::$cnt_targets);
      Logger::get()->out(Logger::Critical, 'Sort targets: '.self::$queue->countTasks());
      foreach(self::$roots as $rkey => $root) {
        foreach($root['targets'] as $tkey => $target) {
          if(is_array($target->getLinks())) {
            foreach($target->getLinks() as $link) {
              if(!self::$queue->exists($link)) {
								$path1 = BuildUtils::makeTargetPath($rkey, $tkey);
								$path2 = $link;
                Logger::get()->out(Logger::Critical, "Target '$path2' not found (by '$path1')");
              }
            }
          }
				}
			}
			Logger::get()->out(Logger::Critical, '==================================');
      Logger::get()->out(Logger::Debug, var_export(self::$queue->getQueue(), true));
    }
    $pngString = ob_get_contents();
    ob_end_clean();
    if($prn) {
      echo $pngString;
    }
  }

  private function build_plan() {
    Logger::get()->out(Logger::Info, 'Build plan...');
    if(!isset(self::$make_target)) {
      self::$make_roots = self::$order_roots;
      return;
    }
    if(is_string(self::$make_target)) {
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
    var_dump(self::$make_roots);
  }
  
	public function regRoot($root_name, $params) {
		self::$current_root = $root_name;
		self::$roots[$root_name] = $params;
		self::$roots[$root_name]['targets'] = array();
		if(isset($params['sources'])) {
			foreach($params['sources'] as $source) {
			  Logger::get()->out(Logger::Info, 'Scan targets: '.$params['home_dir'].DIRECTORY_SEPARATOR.$source);
			  $it = new RecursiveDirectoryIterator($params['home_dir'].DIRECTORY_SEPARATOR.$source);
			  foreach(new RecursiveIteratorIterator($it) as $file) {
          if (in_array(basename($file), self::$configFiles)) {
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
    $params['name'] = $tname;

    self::$targets[$tname] = new Target($params);
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
  
	public function getTarget($target) {
		if(isset(self::$targets[$target])) {
			return self::$targets[$target];
		}
    Logger::get()->out(Logger::Critical, "Target not found (target='$target')");
		return null;
	}
	
  public function getTargetByLink($fullink) {
    $root = BuildUtils::getProjectName($fullink);
    $target = BuildUtils::getTargetName($fullink);
		if(isset(self::$targets[$target])) {
			return self::$targets[$target];
		}
		if(isset(self::$roots[$root]['req'][$target])) {
			return self::$roots[$root]['req'][$target];
		}    
    Logger::get()->out(Logger::Critical, "Target not found (root='$root', target='$target')");
		return null;
	}
  	
  public function setResult($target, $result) {
		Logger::get()->out(Logger::Debug, 'setResult (target='.$target.', result='.$result.')');
		self::$queue->setResult($target, $result);
		//var_dump(self::$order_roots);
	}

  public function getResult($target) {
		Logger::get()->out(Logger::Debug, 'getResult (target='.$target.')');
    return self::$queue->getResult($target);
	}
	
	private function build() {
    //self::build_plan();
    
    Timers::get()->start('build');
		foreach(self::$os_type as $os) {
			Logger::get()->out(Logger::Info, "OS: $os");
			
      foreach(self::$variant as $vardev) {
        Logger::get()->out(Logger::Info, "Variant: $vardev");

        foreach(self::$platform as $pl) {
          Logger::get()->out(Logger::Info, "Platform: $pl");
          
					self::$buildinfo->set($os, $pl, $vardev);
					foreach(self::$tools as $ktool => $vtools) {
            if(method_exists($ktool, 'initEnv')) {
              $vtools->initEnv(self::$buildinfo);
            }
          }
          
					foreach(self::$queue->getQueue() as $key => $order) {
						Logger::get()->out(Logger::Info, "Target: $key");
						
            
						$root = BuildUtils::getProjectName($key);
						$target = BuildUtils::getTargetName($key);
						
						$tg = self::getTarget($root, $target);

						Logger::get()->out(Logger::Info, 'Build folder: '.self::$buildinfo->getBuildPath().$tg['dir']);
						/*
             * Check for use cache library
             */
            if(isset($tg->getCache())) {
              $result = self::getCacheBuild(self::$buildinfo, $key, $tg->getCache());
              //var_dump($result);
              if(is_string($result) && file_exists($result)) {
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
             */
            $tg->make(array('buildinfo' => self::$buildinfo, 'queue' => self::$queue));
					}
          self::$manager->exec(self::$queue);
					//echo 'RESULT='.self::$order_roots[$key]['result']."\n";
				}
			}
		}
    Timers::get()->stop('build');
	}
	
  private function getCacheBuild($buildinfo, $target_name, $target_cache) {
    $tg = self::getTargetByLink($target_cache);
    if(!is_array($tg)) {
      Logger::get()->out(Logger::Error, "Target cache '$target_cache' is not found (in '$target_name')");
      var_dump($tg);
      return null;
    }
    if(!isset($tg['result'])) {
      Logger::get()->out(Logger::Error, "Target cache '$target_name' is not found result");
      return null;
    }
    //var_dump($buildinfo);
    //var_dump($tg['result']);
    foreach($tg['result'] as $key => $val) {
      if($buildinfo->checkBuild($key)) {
        Logger::get()->out(Logger::Notice, "Target cache '$target_name' found sources '$val'");
        return $tg['home_dir'].DIRECTORY_SEPARATOR.$val;
      }
    }
  }
  
	private function check() {
		Logger::get()->out(Logger::Info, '================== CHECK ===================');
    Timers::get()->start('check');
		foreach(self::$os_type as $os) {
      Logger::get()->out(Logger::Info, "OS: $os");
			
			foreach(self::$variant as $vardev) {
        Logger::get()->out(Logger::Info, "Variant: $vardev");
				
				foreach(self::$platform as $pl) {
					Logger::get()->out(Logger::Info, "Platform: $pl");
					foreach(self::$order_roots as $key => $order) {
            Logger::get()->out(Logger::Info, "Target: $key");
						$root = BuildUtils::getProjectName($key);
						$target = BuildUtils::getTargetName($key);
						
						$tg = self::$roots[$root]['targets'][$target];
						
						if(isset($tg['utest']) && $tg['utest'] && 
							 isset(self::$order_roots['result']))	 {
								 if(is_string() &&
										file_exists(self::$order_roots['result'])) {
											$filename_log = Utils::changeExtention(self::$order_roots['result'], '.log');
											
											Build::get()->addScript($tg, array(
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
		switch(self::$args['do']) {
		case 'rebuild':
			self::setRebuild(true);
			self::find_roots(self::$projects_path);
			self::build();
			self::saveState();
  		break;
		case 'build':
			self::setRebuild(false);
			self::loadState();
			self::build();
  		self::saveState();
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
   		self::saveState();
			self::check();
			break;	
		case 'clear':
			self::clear();
			break;
    default:
      Logger::get()->out(Logger::Info, '============================================');
      Logger::get()->out(Logger::Info, 'Command is not defined');
      break;
		}
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
    Logger::get()->out(Logger::Info, '============================================');
    Logger::get()->out(Logger::Info, 'Targets:   '.self::$cnt_targets);
    Logger::get()->out(Logger::Info, 'Files:     '.self::$cnt_files);
    Logger::get()->out(Logger::Info, 'Lines:     '.self::$cnt_lines);
  }
  
	private function printTimers() {
    Logger::get()->out(Logger::Info, '============================================');
    Logger::get()->out(Logger::Info, 'Targets:   '.self::$cnt_targets);
    if(self::$cnt_tasks > 0) {
			Logger::get()->out(Logger::Info, 'Tasks:     '.self::$cnt_tasks);
			Logger::get()->out(Logger::Info, 'OK:        '.self::$cnt_ok);
			Logger::get()->out(Logger::Info, 'Progress:  '.round(self::$cnt_ok*100/self::$cnt_tasks).'%');
			Logger::get()->out(Logger::Info, 'Erros:     '.self::$cnt_errors);
			Logger::get()->out(Logger::Info, 'Warnings:  '.self::$cnt_warnings);
		}
    Logger::get()->out(Logger::Info, '--- Timers ---------------------------------');
    Timers::get()->printAll();
   
    Logger::get()->out(Logger::Info, '============================================');
	}
	private function clear() {
		rmdir(self::$buildinfo->getBuildPath());
	}
	public function setReleasePath($release_path) {
		self::$buildinfo->setReleasePath($release_path);
	}
	public function setBuildPath($build_path) {
		self::$buildinfo->setBuildPath($build_path);
	}
	public function setProjectsPath($projects_path) {
		self::$projects_path = $projects_path;
	}

  public function saveState() {
    Utils::saveState(self::$buildinfo->getBuildPath().DIRECTORY_SEPARATOR.'roots.cache', self::$roots);
    self::$queue->saveState(self::$buildinfo->getBuildPath());
    self::$files->saveState(self::$buildinfo->getBuildPath().DIRECTORY_SEPARATOR.'files.stat');
  }

  public function loadState() {
    self::$roots = Utils::loadState(self::$buildinfo->getBuildPath().DIRECTORY_SEPARATOR.'roots.cache');

    self::$queue->loadState(self::$buildinfo->getBuildPath());
    self::$files->loadState(self::$buildinfo->getBuildPath().DIRECTORY_SEPARATOR.'files.stat');
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
