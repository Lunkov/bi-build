<?

include 'utils.php';
include 'enviroment.php';
include __DIR__.'/../tasks/winapp.php';

class Build {
	private static $instance;
	private static $release_path;
	private static $build_path;
	private static $variant  = array('develop', 'production', 'debug');
	private static $platform = array('x32', 'x64');
	private static $os_type  = array('win');
	private static $roots   = array();
	private static $current_root = '';
	private static $tools   = array();
	private static $output = array();
	private static $src_files_old = array();
	private static $src_files = array();
	private static $extentions = array();
	private static $cnt_files = 0;
	private static $cnt_lines = 0;

	private static $time_scan = 0;
	private static $time_cl = 0;

	
	private function __construct()
	{
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
		self::$time_scan = round(microtime(true) - $curTime,3)*1000; 
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
	   $params['dir'] = substr($params['home_dir'], strlen(self::$roots[self::$current_root]['home_dir'])-strlen($params['home_dir']));
	   echo 'Target: '.$params['dir'].'//'.$target_name."\n";
	   self::$roots[self::$current_root]['targets'][$params['dir'].'//'.$target_name] = $params;
	}
	
	public function exec() {
		//var_dump(self::$targets);
		$enviroment  = new Enviroment();
		//echo 'Targets: '.count(self::$targets)."\n";
		foreach(self::$os_type as $os) {
			echo 'OS: '.$os."\n";
			foreach(self::$variant as $vardev) {
				echo 'Variant: '.$vardev."\n";
				foreach(self::$platform as $pl) {
					echo 'Platform: '.$pl."\n";
					$enviroment->setEnv($os, $pl, $variant);
					foreach(self::$roots as $key => $root) {
						//var_dump($root);
						foreach($root['targets'] as $key => $target) {
							
						  $cnt_files+=count($target['files']);

						  $build_dir = self::$build_path.DIRECTORY_SEPARATOR.'build'.DIRECTORY_SEPARATOR.$os.DIRECTORY_SEPARATOR.$vardev.DIRECTORY_SEPARATOR.$pl;
						  echo 'Build folder: '.$build_dir.$target['dir']."\n";
						  
						  $wapp = new WinApp();
						  $wapp->static_lib($enviroment, $build_dir, $key, $target);
						  /*
						  if(!file_exists($build_dir)) {
							mkdir($build_dir, 0x0777, true);
						  }
						  
						  $includes='';
						  
						  foreach($target['include'] as $include) {
								$includes.='/I"'.$target['src_path'].DIRECTORY_SEPARATOR.$include.'" ';
						  }

						  foreach($target['src'] as $file) {
							$filename = $target['home_dir'].DIRECTORY_SEPARATOR.$file;
							//echo 'extension:'.Utils::getFileExtension($filename);
							//self::$cnt_lines+=Utils::getFileLines($filename);
							$md = Utils::calcHash($filename);
							$filename_out = $build_dir.$file.'.obj';
							
							$CL_PATH = '"C:\\tools\\Microsoft Visual Studio 12.0\\VC\\bin\\amd64\\';
							$cmd = $CL_PATH.'cl.exe" /MT '. $includes . $filename . ' /Fo:'.$filename_out;
							//echo $cmd."\n";
							$curTime = microtime(true);
							//exec($cmd, $output, $ret);
							self::$time_cl += round(microtime(true) - $curTime,3)*1000; 
						//    var_dump($output);
						//break;
							$src_files[$filename] = array('md5' => $md);

						  }*/
						}
					}
				}
			}
		}
	}
	
	public function printTimers() {
		echo 'Scan time: '.self::$time_scan.' ms'."\n";
		echo 'Cl time: '.self::$time_cl.' ms'."\n";
	}
	
	public function setReleasePath($release_path) {
		self::$release_path = $release_path;
	}
	public function setBuildPath($build_path) {
		self::$build_path = $build_path;
	}

}
