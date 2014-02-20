<?

class VCC130 {
	private $tool_cc = '';
	private $tool_cxx = '';
	private $tool_as = '';
	private $tool_ar = '';
	private $tool_ld = '';
	private $tool_dumpbin = '';
	private $tool_editbin = '';
	private $tool_rc = '';
	private $tool_mt = '';
	private $tool_signtool = '';
	private $bin_path = '';
	private $sdk_bin_path = '';
	private $include_path = array();
	private $lib = array();
	private $lib_path = array();
	private $cflags = '/nologo /GS /GL /FS /analyze /W3 /Gy /Zc:wchar_t /Zi /Gm- /O2 /sdl /D "_UNICODE" /D "UNICODE" ';
	private $time_cl = 0;
	private $time_link = 0;
	private $env = array();
	
	private $params;
	
	function init($params) {
		$this->env = $_ENV;
		$this->env['PATH'] = '';
		$this->env['INCLUDE'] = '';
		$this->env['LIB'] = '';
		$this->env['LIBPATH'] = '';
		
		$this->params = $params;
		if(isset($this->params['home_path'])) {
			$this->bin_path = '';
			if(Enviroment::getHostPlatform() == 'i386') {
				$bin_path = $this->params['home_path'].DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR;
			}
			if(Enviroment::getHostPlatform() == 'AMD64') {
				$this->bin_path = $this->params['home_path'].DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'amd64'.DIRECTORY_SEPARATOR;
			}
			$this->tool_cc  = $this->bin_path.'cl.exe';
			$this->tool_cxx = $this->bin_path.'cl.exe';
			$this->tool_as  = $this->bin_path.'ml64.exe';
			$this->tool_ar  = $this->bin_path.'lib.exe';
			$this->tool_ld  = $this->bin_path.'link.exe';
			$this->tool_dumpbin = $this->bin_path.'dumpbin.exe';
			$this->tool_editbin = $this->bin_path.'editbin.exe';
			
			$this->include_path[] = $this->params['home_path'].DIRECTORY_SEPARATOR.'include';
			$this->include_path[] = $this->params['home_path'].DIRECTORY_SEPARATOR.'atlmfc\include';
			
			$this->lib[] = $this->params['home_path'].DIRECTORY_SEPARATOR.'lib';
			$this->lib[] = $this->params['home_path'].DIRECTORY_SEPARATOR.'atlmfc\lib';
		}
		if(isset($this->params['sdk_path'])) {
			$this->sdk_bin_path = '';
			if(Enviroment::getHostPlatform() == 'i386') {
				$this->sdk_bin_path = $this->params['sdk_path'].DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'x86'.DIRECTORY_SEPARATOR;
				
				$this->lib[] = $this->params['sdk_path'].DIRECTORY_SEPARATOR.'lib\winv6.3\um\x86';
			}
			if(Enviroment::getHostPlatform() == 'AMD64') {
				$this->sdk_bin_path = $this->params['sdk_path'].DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'x64'.DIRECTORY_SEPARATOR;
				
				$this->lib[] = $this->params['sdk_path'].DIRECTORY_SEPARATOR.'lib\winv6.3\um\x64';
			}
			$this->tool_rc  = $this->sdk_bin_path.'rc.exe';
			$this->tool_mt  = $this->sdk_bin_path.'mt.exe';

			$this->include_path[] = $this->params['sdk_path'].DIRECTORY_SEPARATOR.'Include';
			$this->include_path[] = $this->params['sdk_path'].DIRECTORY_SEPARATOR.'Include\api';
			$this->include_path[] = $this->params['sdk_path'].DIRECTORY_SEPARATOR.'Include\shared';
			$this->include_path[] = $this->params['sdk_path'].DIRECTORY_SEPARATOR.'include\um';
			$this->include_path[] = $this->params['sdk_path'].DIRECTORY_SEPARATOR.'include\winrt';
			
			$this->lib[] = $this->params['sdk_path'].DIRECTORY_SEPARATOR.'References\CommonConfiguration\Neutral';
			
		}
		$this->env['PATH'] .= self::add_env_path($this->bin_path);
		$this->env['PATH'] .= self::add_env_path($this->sdk_bin_path);
		$this->env['INCLUDE'] .= self::add_env_path($this->include_path);
		$this->env['LIB'] .= self::add_env_path($this->lib);
		$this->env['LIBPATH'] .= self::add_env_path($this->lib_path);

		//var_dump($this);
	}
	
	public function static_lib($buildinfo, $build_dir, $target_name, &$target) {
		$b_dir = $build_dir.$target['dir'];
		echo '+'.$b_dir."\n";
		if(!file_exists($b_dir)) {
			mkdir($b_dir, 0x0777, true);
		}
		
		$result = $build_dir.$target['dir'].DIRECTORY_SEPARATOR.$target['short_name'].'.lib';
		echo 'result = '.$result."\n";
		
		$includes = '';
		$includes.= self::make_include($this->include_path);
		$includes.= self::make_include($target['include']);
		
		$flags = $this->cflags. ' /c /D "_LIB" ';///EHsc /SUBSYSTEM:CONSOLE
		if($buildinfo->getPlatform() == 'x32') $flags .= ' /MACHINE:X86 /D "WIN32" ';
		if($buildinfo->getPlatform() == 'x64') $flags .= ' /MACHINE:X64 /D "_WIN64" ';
		
		if($buildinfo->getVariant() == 'develop') $flags .= ' /WX /D "NDEBUG" ';
		if($buildinfo->getVariant() == 'production') $flags .= ' /WX /D "NDEBUG" ';
		if($buildinfo->getVariant() == 'debug') $flags .= ' /Od /D "_DEBUG" ';
		
		$cl_result = array();
		foreach($target['src'] as $file) {
			//$filename_in = $target['home_dir'].DIRECTORY_SEPARATOR.$file;
			$filename_in = $file;
			$extention  = Utils::getFileExtension($file);
			$out = '';
			//echo '++'.$b_dir."\n";
			if($extention == 'c')   $this->c2obj($b_dir, $filename_in, $flags, $includes, $out);
			if($extention == 'cpp') $this->cpp2obj($b_dir, $filename_in, $flags, $includes, $out);
			
			if(strlen($out)>0) {
				$cl_result[] = $out;
			}
		}
		$curTime = microtime(true);
		Build::get()->execScripts();
		$this->time_cl += round(microtime(true) - $curTime,3)*1000; 
		
		$this->obj2lib($buildinfo, $build_dir, self::file_list($cl_result), $libs, $target, $out);
		$curTime = microtime(true);
		Build::get()->execScripts();
		$this->time_link += round(microtime(true) - $curTime,3)*1000; 
/*
		foreach($target['src'] as $file) {
			$extention  = Utils::getFileExtension($file);
			if($extention == 'obj') obj2lib($enviroment, $build_dir, $includes);
		}*/
		
	}
	
	public function driver($buildinfo, $build_dir, $target_name, &$target) {
		//VBoxVideo_LDFLAGS.x86   = /Entry:DriverEntry@8
		//VBoxVideo_LDFLAGS.amd64 = /Entry:DriverEntry

	}	
	
	private function c2obj($build_dir, $file, $flags, $includes, &$out) {

		$filename_log = $build_dir.DIRECTORY_SEPARATOR.Utils::getFileName($file).'.log';
		$filename_out = $build_dir.DIRECTORY_SEPARATOR.Utils::getFileName($file).'.obj';
		$filename_rsp = $build_dir.DIRECTORY_SEPARATOR.Utils::getFileName($file).'.rsp';
		
		file_put_contents($filename_rsp, '/Tc"'.$file."\"\n".$flags."\n".$includes."\n".'/Fo:"'.$filename_out.'"');
		
		$cmd = 'cl.exe /nologo @"'.$filename_rsp.'"';
		echo $cmd."\n";
		Build::get()->addScript(array(  'home_dir' => $this->bin_path,
										'script_name' => $cmd,
										'env' => $this->env,
										'log_file' => $filename_log
										));
		
		$out = $filename_out;
	}

	private function cpp2obj($build_dir, $file, $flags, $includes, &$out) {

		$filename_log = $build_dir.DIRECTORY_SEPARATOR.Utils::getFileName($file).'.log';
		$filename_out = $build_dir.DIRECTORY_SEPARATOR.Utils::getFileName($file).'.obj';
		$filename_rsp = $build_dir.DIRECTORY_SEPARATOR.Utils::getFileName($file).'.rsp';
		
		file_put_contents($filename_rsp, '/Tp"'.$file."\"\n".$flags."\n".$includes."\n".'/Fo:"'.$filename_out.'"');
		
		$cmd = 'cl.exe /nologo @"'.$filename_rsp.'"';
		echo $cmd."\n";
		Build::get()->addScript(array(  'home_dir' => $this->bin_path,
										'script_name' => $cmd,
										'env' => $this->env,
										'log_file' => $filename_log
										));
		
		$out = $filename_out;
	}
	
	private function obj2lib($buildinfo, $build_dir, $files, $libs, &$target, &$out) {
		$filename_log = $build_dir.DIRECTORY_SEPARATOR.$target['short_name'].'.log';
		$filename_out = $build_dir.DIRECTORY_SEPARATOR.$target['short_name'].'.lib';
		$filename_rsp = $build_dir.DIRECTORY_SEPARATOR.$target['short_name'].'.rsp';
		
		$flags = '/LTCG ';
		// /MACHINE:{ARM|EBC|X64|X86}
		// http://msdn.microsoft.com/ru-ru/library/9a89h429.aspx
		if($buildinfo->getPlatform() == 'x32') $flags .= ' /SAFESEH /MACHINE:X86 ';
		// http://msdn.microsoft.com/ru-ru/library/dn195771.aspx
		if($buildinfo->getPlatform() == 'x64') $flags .= ' /HIGHENTROPYVA /MACHINE:X64 ';		
		
		file_put_contents($filename_rsp, $files.$flags."\n".$includes."\n".'/OUT:"'.$filename_out.'"');
		
		$cmd = 'link.exe /nologo @"'.$filename_rsp.'"';
		echo $cmd."\n";
		Build::get()->addScript(array(  'home_dir' => $this->bin_path,
										'script_name' => $cmd,
										'env' => $this->env,
										'log_file' => $filename_log
										));
												
		$out = $filename_out;
	}

	private function obj2dll($enviroment, $build_dir, $includes, $libs, &$out) {
	}

	private function obj2exe($enviroment, $build_dir, $includes, $libs, &$out) {
	}

	private function obj2sys($enviroment, $build_dir, $includes, $libs, &$out) {
	}

	private static function make_include($includes) {
		return self::array2string($includes, '/I"', "\" \n");
	}

	private static function add_env_path($path) {
		return self::array2string($path, '"', '";');
	}

	private static function file_list($files) {
		return self::array2string($files, '"', "\" \n");
	}

	private static function array2string($data, $str_begin, $str_end) {
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

	public function printTimers() {
		echo "==========\n";
		echo 'Tool: '.__CLASS__."\n";
		echo 'Compile time: '.$this->time_cl.' ms'."\n";
		echo 'Link time: '.$this->time_link.' ms'."\n";
		echo "==========\n";
	}
}
