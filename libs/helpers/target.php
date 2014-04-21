<?php

class Target {
  private $name;
  private $short_name;
  private $root_name;
  private $home_dir;
  
  private $defines = array();
  private $includes = array();
  private $include_pathes = array();
  private $links = array();
  private $src = array();
  //private $files = array();
  private $cache;
  private $make_tool;
  private $make_func;
  
  public function __construct($params) {
    Logger::get()->out(Logger::Info, 'Target: '.BuildUtils::makeTargetPath($params['root'], $params['name']));
    
    $this->name = $params['name'];
 	  $this->short_name = $params['short_name'];
    
    if(!isset($params['root'])) {
      Logger::get()->out(Logger::Warning, 'root not set');
    }
    $this->root_name = $params['root'];
    
    if(!isset($params['home_dir'])) {
      Logger::get()->out(Logger::Warning, 'Home dir not set');
    }
    $this->home_dir = (isset($params['home_dir'])?$params['home_dir']:'');

    //search src-files by mask: *.cpp, etc
    if(isset($params['src']) && is_array($params['src'])) {
			$src = array();
			foreach($params['src'] as $file) {
				if(isset($params['home_dir'])) {
					$src = array_merge($src, glob($params['home_dir'].DIRECTORY_SEPARATOR.$file, GLOB_NOCHECK));
				} else {
					$src = array_merge($src, glob($file, GLOB_NOCHECK));
				}
			}
			$this->src = $src;
		}
    
    if(isset($params['includes'])) {
      $this->includes = $params['includes'];
    }

    if(isset($params['linkes'])) {
      $this->linkes = $params['linkes'];
    }
    
    if(isset($params['defines'])) {
      $this->defines = $params['defines'];
    }
    
    if(isset($params['tool'])) {
      $this->make_tool = $params['tool'];
    } else {
      Logger::get()->out(Logger::Critical, 'Tool \''.$params['tool']."' not found");
    }

    if(isset($params['make'])) {
      $this->make_func = $params['make'];
    } else {
      Logger::get()->out(Logger::Critical, 'Action \''. $params['make'].'\' in \''.$params['tool']."' not found");
    }
   
   
  }
  
  public function makeAbsolutePathes() {
    $this->include_pathes = BuildUtils::make_absolute_path($this->includes);
  }
  
  public function getName() {
    return $this->name;
  }
  public function getHomeDir() {
    return $this->home_dir;
  }
  public function getIncludes() {
    return $this->include_pathes;
  }
  public function getSrc() {
    return $this->src;
  }
  public function getLinks() {
    return $this->links;
  }
  public function getDefines() {
    return $this->defines;
  }
  public function getCache() {
    return $this->cache;
  }
  public function make($params) {
    if(class_exists($this->make_tool)) {
      if(method_exists($this->make_tool, $this->make_func)) {
        $count_parameters = BuildUtils::getNumberOfParameters($this->make_tool, $this->make_func);
        if($count_parameters == 1) {
          $m = $this->make_func;
          self::$tools[$this->make_tool]->$m(array(
                                              'buildinfo' => $params['buildinfo'],
                                              'target' => $this,
                                              'queue' => $params['queue']
                                        ));
        } else {
          Logger::get()->out(Logger::Critical, 'Action \''. $this->make_func.'\' in \''.$this->make_tool."' parameters count = ".$count_parameters);
        }
      } else {
        Logger::get()->out(Logger::Critical, 'Action \''. $this->make_func.'\' in \''.$this->make_tool."' not found");
      }
    }
  }
}


