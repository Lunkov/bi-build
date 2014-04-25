<?php

abstract class TypeTarget {
  const   UNDEFINED  = 0;
  const   REQUREMENT = 1;
  const   BUILDING   = 2;
}

class Target {
  private $type = TypeTarget::UNDEFINED;
  private $name;
  private $short_name;
  private $root_name;
  private $home_dir;
  private $short_dir;
  
  private $defines = array();
  private $includes = array();
  private $include_pathes = array();
  private $links = array();
  private $src = array();
  //private $files = array();
  private $cache;
  private $make_tool;
  private $make_func;
  private $make_path;
  
  public function __construct($params) {
    Logger::get()->out(Logger::Info, 'Target: '.BuildUtils::makeTargetPath($params['root'], $params['name']));
    
    $this->name = $params['name'];
 	  $this->short_name = $params['short_name'];
    
    if(isset($params['type'])) {
      $this->type = $params['type'];
    }
    
    if(!isset($params['root'])) {
      Logger::get()->out(Logger::Warning, 'root not set');
    } else {
      $this->root_name = $params['root'];
      $this->name = BuildUtils::makeTargetPath($params['root'], $params['name']);
    }
    
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
    
    if($this->type == TypeTarget::BUILDING) {
      if(isset($params['tool'])) {
        $this->make_tool = $params['tool'];
      } else {
        Logger::get()->out(Logger::Critical, 'Tool \''.$params['tool']."' not found in '".$this->name."' ");
      }

      if(isset($params['make'])) {
        $this->make_func = $params['make'];
      } else {
        Logger::get()->out(Logger::Critical, 'Action \''. $params['make'].'\' in \''.$params['tool']."' not found");
      }
    }
  }
  
  public function makeAbsolutePathes() {
    $this->include_pathes = BuildUtils::make_absolute_path($this->includes);
  }
  
  public function getType() {
    return $this->type;
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
  public function getCountSrc() {
    return count($this->src);
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
  public function existsCache() {
    return isset($this->cache);
  }
  public function make($params) {
    Build::useTool($this->make_tool, $this->make_func, array(
                                              'buildinfo' => $params['buildinfo'],
                                              'target' => $this,
                                              'queue' => $params['queue']
                                        ));
  }
  
  public function setBuildPath($root_build_path) {
    $this->make_path = $b_dir = Utils::mkdir($root_build_path.$target['dir']);
    Logger::get()->out(Logger::Info, 'Build folder: '.$this->make_path);
  }
}


