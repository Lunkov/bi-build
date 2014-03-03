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
	private $tool_signtool = null;
	private $bin_path = '';
	private $sdk_bin_path = '';
	private $wdk_bin_path = '';
	private $qt_bin_path  = '';
	private $include_path = array();
	private $lib = array();
	private $lib_path = array();
	private $cflags = '/nologo /GS /GL /FS /W3 /Gy /Zc:wchar_t /Zi /Gm- /O2 /sdl /D_UNICODE /DUNICODE /D_MSC_VER=1700 ';
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
			if(Enviroment::getPlatform() == 'i386') {
				$bin_path = $this->params['home_path'].DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR;
			}
			if(Enviroment::getPlatform() == 'AMD64') {
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
			
		}
		if(isset($this->params['sdk_path'])) {
			$this->sdk_bin_path = '';
			
			include 'cmd/signtool.php';
			
			if(Enviroment::getPlatform() == 'i386') {
				$this->sdk_bin_path = $this->params['sdk_path'].DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'x86'.DIRECTORY_SEPARATOR;
				$tool_signtool = new SignTool($this->sdk_bin_path);
				
			}
			if(Enviroment::getPlatform() == 'AMD64') {
				$this->sdk_bin_path = $this->params['sdk_path'].DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'x64'.DIRECTORY_SEPARATOR;
				$tool_signtool = new SignTool($this->sdk_bin_path);
			}
			$this->tool_rc  = $this->sdk_bin_path.'rc.exe';
			$this->tool_mt  = $this->sdk_bin_path.'mt.exe';

			$this->include_path[] = $this->params['sdk_path'].DIRECTORY_SEPARATOR.'Include';
			$this->include_path[] = $this->params['sdk_path'].DIRECTORY_SEPARATOR.'Include\api';
			$this->include_path[] = $this->params['sdk_path'].DIRECTORY_SEPARATOR.'Include\shared';
			$this->include_path[] = $this->params['sdk_path'].DIRECTORY_SEPARATOR.'include\um';
			$this->include_path[] = $this->params['sdk_path'].DIRECTORY_SEPARATOR.'include\winrt';
		}
		if(isset($this->params['qt_path'])) {
			$this->qt_bin_path = $this->params['qt_path'].DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR;
			
			$this->include_path[] = $this->params['qt_path'].DIRECTORY_SEPARATOR.'Include';
		}

		$this->env['PATH'] .= self::add_env_path($this->bin_path);
		$this->env['PATH'] .= self::add_env_path($this->sdk_bin_path);
		$this->env['INCLUDE'] .= self::add_env_path($this->include_path);
		//$this->env['LIB'] .= self::add_env_path($this->lib);
		//$this->env['LIBPATH'] .= self::add_env_path($this->lib_path);

		//var_dump($this);
	}
	
	private function initLibs($buildinfo) {
		unset($this->lib);
		$this->lib = array();
		unset($this->lib_path);
		$this->lib_path = array();
		
		//var_dump($buildinfo);
		
		if($buildinfo->getPlatform() == 'x32') {
			$this->lib_path[] = $this->params['home_path'].DIRECTORY_SEPARATOR.'lib';
			$this->lib_path[] = $this->params['home_path'].DIRECTORY_SEPARATOR.'atlmfc\lib';
		}
		if($buildinfo->getPlatform() == 'x64') {
			$this->lib_path[] = $this->params['home_path'].DIRECTORY_SEPARATOR.'lib\amd64';
			$this->lib_path[] = $this->params['home_path'].DIRECTORY_SEPARATOR.'atlmfc\lib\amd64';
		}
		if(isset($this->params['sdk_path'])) {
			if($buildinfo->getPlatform() == 'x32') {
				$this->lib_path[] = $this->params['sdk_path'].DIRECTORY_SEPARATOR.'lib\winv6.3\um\x86';
			}
			if($buildinfo->getPlatform() == 'x64') {
				$this->lib_path[] = $this->params['sdk_path'].DIRECTORY_SEPARATOR.'lib\winv6.3\um\x64';
			}
			$this->lib_path[] = $this->params['sdk_path'].DIRECTORY_SEPARATOR.'References\CommonConfiguration\Neutral';
		}
		$this->env['LIB'] = self::add_env_path(array_keys(array_flip($this->lib_path)));
		$this->env['LIBPATH'] = self::add_env_path(array_keys(array_flip($this->lib_path)));
		
		//var_dump($this->env);
	}
	
	private function initIncludes($target) {
		$inc = array();
		$inc = array_merge($inc, $this->include_path);
		$inc = array_merge($inc, $target['include']);
		//var_dump($target['link']);
		if(is_array($target['link'])) {
			foreach($target['link'] as $link) {
				//var_dump($link);
				if(isset($link['root']) && isset($link['target'])) {
					$tg = Build::get()->getTarget($link['root'], $link['target']);
					if(!is_null($tg)) {
						$inc = array_merge($inc, $this->initIncludes($tg));
					}
					if(is_array($tg['include'])) {
						$inc = array_merge($inc, $tg['include']);
					}
				}
			}
		}
		return array_keys(array_flip($inc));
	}
	
	private function initDepends($target) {
		$inc = array();
		//$inc = array_merge($inc, $this->include_path);
		//$inc = array_merge($inc, $target['include']);
		//var_dump($target['link']);
		if(is_array($target['link'])) {
			foreach($target['link'] as $link) {
				//var_dump($link);
				if(isset($link['root']) && isset($link['target'])) {
					//$tg = Build::get()->getTarget($link['root'], $link['target']);
					$result = Build::get()->getResult(BuildUtils::make_target_path($link['root'], $link['target']));
					//if(!is_null($tg) && isset($tg['result'])) {
					if(!is_null($result)) {
						echo 'RESULT+: '.$result."\n";
						if(is_array($result)) {
							$inc = array_merge($inc, $result);
						}
						if(is_string($result)) {
							$inc[] = $result;
						}
					}
					//if(is_array($tg['include'])) {
					//	$inc = array_merge($inc, $tg['include']);
					//}
				}
			}
		}
		return array_keys(array_flip($inc));
	}
		
	private function compile($buildinfo, $build_dir, $target_name, &$target) {
		$b_dir = $build_dir.$target['dir'];
		echo '+'.$b_dir."\n";
		if(!file_exists($b_dir)) {
			mkdir($b_dir, 0x0777, true);
		}
		
		$inc = $this->initIncludes($target);
		//var_dump($inc);
		$includes = self::make_include($inc);
		echo 'includes: '.$includes."\n";
		
		$flags = $this->cflags. ' /D_USING_V110_SDK71_ /D_CRT_SECURE_NO_WARNINGS /c /D_LIB /D__MSVCRT_VERSION__=0x0601 /DWINVER=0x501  /D_WIN32_WINNT=0x501 ';// /SUBSYSTEM:CONSOLE

		if($buildinfo->getPlatform() == 'x32') {
			$flags .= ' /DWIN32 '; ////MACHINE:X86 
		}
		if($buildinfo->getPlatform() == 'x64') {
			$flags .= ' /D_WIN64 /D_AMD64_ /D_MBCS /D_LIB ';///MACHINE:X64 
		}
		
		if($buildinfo->getVariant() == 'develop') {
			$flags .= ' /MT /analyze /DNDEBUG /DVLOG_ENABLE ';
		}
		if($buildinfo->getVariant() == 'production') {
			$flags .= ' /MT /analyze- /DNDEBUG ';
		}
		if($buildinfo->getVariant() == 'debug') {
			$flags .= ' /MTd /analyze /Od /D_DEBUG /DASSERT_ENABLE /DVLOG_ENABLE ';
		}
		
		$cl_result = array();
		foreach($target['src'] as $file) {
			//$filename_in = $target['home_dir'].DIRECTORY_SEPARATOR.$file;
			$filename_in = $file;
			$extention  = Utils::getFileExtension($file);
			$out = '';
			//echo '++'.$b_dir."\n";
			switch($extention) {
			case 'def':
						$out = $filename_in;
						break;
			case 'ts':
						$this->ts2qm($b_dir, $filename_in, $out);
						break;
			case 'rc':
						$this->rc2res($b_dir, $filename_in, $flags, $includes, $out);
						break;
			case 'h':
						break;
			case 'c':
						$this->c2obj($b_dir, $filename_in, $flags, $includes, $out);
						break;
			case 'cpp':
						$this->cpp2obj($b_dir, $filename_in, $flags, $includes, $out);
						break;
			default:
						$out = $b_dir.DIRECTORY_SEPARATOR.$filename_in;
						break;
			}
			
			if(strlen($out)>0) {
				$cl_result[] = $out;
			}
		}
		$curTime = microtime(true);
		Build::get()->execScripts();
		$this->time_cl += round(microtime(true) - $curTime,3)*1000;
		
		return $cl_result;
	}
	
	public function static_lib($buildinfo, $build_dir, $target_name, &$target) {
		$b_dir = $build_dir.$target['dir'];
		echo '+'.$b_dir."\n";
		if(!file_exists($b_dir)) {
			mkdir($b_dir, 0x0777, true);
		}
		
		$result = $build_dir.$target['dir'].DIRECTORY_SEPARATOR.$target['short_name'].'.lib';
		echo 'result = '.$result."\n";
		//TODO!!!
		if(file_exists($result)) return;
				
		$this->initLibs($buildinfo);
		
		$cl_result = $this->compile($buildinfo, $build_dir, $target_name, $target); 
		
		$this->obj2lib($buildinfo, $build_dir.$target['dir'].DIRECTORY_SEPARATOR, self::file_list($cl_result), $libs, $target, $out);

		$curTime = microtime(true);
		Build::get()->execScripts();
		$this->time_link += round(microtime(true) - $curTime,3)*1000; 
		
		Build::get()->setResult($target_name, $out);
	}

	public function dynamic_lib($buildinfo, $build_dir, $target_name, &$target) {
		$b_dir = $build_dir.$target['dir'];
		echo '+'.$b_dir."\n";
		if(!file_exists($b_dir)) {
			mkdir($b_dir, 0x0777, true);
		}
		
		$result = $build_dir.$target['dir'].DIRECTORY_SEPARATOR.$target['short_name'].'.lib';
		echo 'result = '.$result."\n";
		//TODO!!!
		if(file_exists($result)) return;
				
		$this->initLibs($buildinfo);
		
		$cl_result = $this->compile($buildinfo, $build_dir, $target_name, $target); 
		
		$this->obj2dll($buildinfo, $build_dir.$target['dir'].DIRECTORY_SEPARATOR, self::file_list($cl_result), $libs, $target, $out);

		$curTime = microtime(true);
		Build::get()->execScripts();
		$this->time_link += round(microtime(true) - $curTime,3)*1000; 
		
		Build::get()->setResult($target_name, $out);
	}
	
	public function console_exe($buildinfo, $build_dir, $target_name, &$target) {
		$b_dir = $build_dir.$target['dir'];
		echo '+'.$b_dir."\n";
		if(!file_exists($b_dir)) {
			mkdir($b_dir, 0x0777, true);
		}
		
		$result = $build_dir.$target['dir'].DIRECTORY_SEPARATOR.$target['short_name'].'.lib';
		echo 'result = '.$result."\n";
		//TODO!!!
		if(file_exists($result)) return;
				
		$this->initLibs($buildinfo);
		
		$cl_result = $this->compile($buildinfo, $build_dir, $target_name, $target); 
		
		$this->obj2exe($buildinfo, $build_dir.$target['dir'].DIRECTORY_SEPARATOR, self::file_list($cl_result), $libs, $target, $out);

		$curTime = microtime(true);
		Build::get()->execScripts();
		$this->time_link += round(microtime(true) - $curTime,3)*1000; 
		
		Build::get()->setResult($target_name, $out);
	}

	public function win_exe($buildinfo, $build_dir, $target_name, &$target) {
		$b_dir = $build_dir.$target['dir'];
		echo '+'.$b_dir."\n";
		if(!file_exists($b_dir)) {
			mkdir($b_dir, 0x0777, true);
		}
		
		$result = $build_dir.$target['dir'].DIRECTORY_SEPARATOR.$target['short_name'].'.lib';
		echo 'result = '.$result."\n";
		//TODO!!!
		if(file_exists($result)) return;
				
		$this->initLibs($buildinfo);
		
		$cl_result = $this->compile($buildinfo, $build_dir, $target_name, $target); 
		
		$this->obj2exe($buildinfo, $build_dir.$target['dir'].DIRECTORY_SEPARATOR, self::file_list($cl_result), $libs, $target, $out);

		$curTime = microtime(true);
		Build::get()->execScripts();
		$this->time_link += round(microtime(true) - $curTime,3)*1000; 
	
		Build::get()->setResult($target_name, $out);
	}
		
	public function driver($buildinfo, $build_dir, $target_name, &$target) {
		//VBoxVideo_LDFLAGS.x86   = /Entry:DriverEntry@8
		//VBoxVideo_LDFLAGS.amd64 = /Entry:DriverEntry
		// /kernel

		$b_dir = $build_dir.$target['dir'];
		echo '+'.$b_dir."\n";
		if(!file_exists($b_dir)) {
			mkdir($b_dir, 0x0777, true);
		}
		
		$result = $build_dir.$target['dir'].DIRECTORY_SEPARATOR.$target['short_name'].'.lib';
		echo 'result = '.$result."\n";
		//TODO!!!
		if(file_exists($result)) return;
				
		$this->initLibs($buildinfo);
		
		$cl_result = $this->compile($buildinfo, $build_dir, $target_name, $target); 
		
		$this->obj2sys($buildinfo, $build_dir.$target['dir'].DIRECTORY_SEPARATOR, self::file_list($cl_result), $libs, $target, $out);

		$curTime = microtime(true);
		Build::get()->execScripts();
		$this->time_link += round(microtime(true) - $curTime,3)*1000; 
		
		Build::get()->setResult($target_name, $out);

	}	
	
	private function c2obj($build_dir, $file, $flags, $includes, &$out) {

		$filename_log = $build_dir.DIRECTORY_SEPARATOR.Utils::getFileName($file).'.log';
		$filename_out = $build_dir.DIRECTORY_SEPARATOR.Utils::getFileName($file).'.obj';
		$filename_rsp = $build_dir.DIRECTORY_SEPARATOR.Utils::getFileName($file).'.rsp';
		
		//file_put_contents($filename_rsp, '/Tc"'.$file."\"\n".$flags."\n".$includes."\n".'/Fo:"'.$filename_out.'"');
		file_put_contents($filename_rsp, '"'.$file."\"\n".$flags."\n".$includes."\n".'/Fo:"'.$filename_out.'"');
		
		$cmd = 'cl.exe /nologo @"'.$filename_rsp.'"';
		//echo $cmd."\n";
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
		$flags.=' /EHsc ';
		file_put_contents($filename_rsp, '/Tp"'.$file."\"\n".$flags."\n".$includes."\n".'/Fo:"'.$filename_out.'"');
		
		$cmd = 'cl.exe /nologo @"'.$filename_rsp.'"';
		//echo $cmd."\n";
		Build::get()->addScript(array(  'home_dir' => $this->bin_path,
										'script_name' => $cmd,
										'env' => $this->env,
										'log_file' => $filename_log
										));
		
		$out = $filename_out;
	}

	private function rc2res($build_dir, $file, $flags, $includes, &$out) {

		$filename_log = $build_dir.DIRECTORY_SEPARATOR.Utils::getFileName($file).'.log';
		$filename_out = $build_dir.DIRECTORY_SEPARATOR.Utils::getFileName($file).'.res';
		$filename_rsp = $build_dir.DIRECTORY_SEPARATOR.Utils::getFileName($file).'.rsp';
		$flags.=' /EHsc ';
		file_put_contents($filename_rsp, '/Tp"'.$file."\"\n".$flags."\n".$includes."\n".'/Fo:"'.$filename_out.'"');
		
		$cmd = 'rc.exe /nologo @"'.$filename_rsp.'"';
		//echo $cmd."\n";
		Build::get()->addScript(array(  'home_dir' => $this->sdk_bin_path,
										'script_name' => $cmd,
										'env' => $this->env,
										'log_file' => $filename_log
										));
		
		$out = $filename_out;
	}

	private function ts2qm($build_dir, $file, &$out) {

		$filename_log = $build_dir.DIRECTORY_SEPARATOR.Utils::getFileName($file).'.log';
		$filename_out = $build_dir.DIRECTORY_SEPARATOR.Utils::getFileName($file).'.qm';
		//file_put_contents($filename_rsp, '/Tp"'.$file."\"\n".$flags."\n".$includes."\n".'/Fo:"'.$filename_out.'"');
		
		$cmd = 'lrelease.exe "'.$file.'" -qm "'.$filename_out.'"';
		//echo $cmd."\n";
		Build::get()->addScript(array(  'home_dir' => $this->qt_bin_path,
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
		
		$flags = '/LTCG /SUBSYSTEM:CONSOLE ';///NODEFAULTLIB:LIBCMT 
		// /MACHINE:{ARM|EBC|X64|X86}
		// http://msdn.microsoft.com/ru-ru/library/9a89h429.aspx
		if($buildinfo->getPlatform() == 'x32') $flags .= ' /SAFESEH /MACHINE:X86 ';
		// http://msdn.microsoft.com/ru-ru/library/dn195771.aspx
		if($buildinfo->getPlatform() == 'x64') $flags .= ' /MACHINE:X64 ';	///HIGHENTROPYVA 
		if($buildinfo->getVariant() == 'product') {
			$flags .= ' /MT /RELEASE ';
		}
		if($buildinfo->getVariant() == 'develop') {
			$flags .= ' /MT /RELEASE ';
		}
		$depends = self::file_list($this->initDepends($target));
		file_put_contents($filename_rsp, $files.$flags."\n".$includes."\n".$depends."\n".'/OUT:"'.$filename_out.'"');
		
		$cmd = 'lib.exe /nologo @"'.$filename_rsp.'"';
		echo $cmd."\n";
		//var_dump($this->env);
		Build::get()->addScript(array(  'home_dir' => $this->bin_path,
										'script_name' => $cmd,
										'env' => $this->env,
										'log_file' => $filename_log
										));
												
		$out = $filename_out;
	}

	private function obj2exe($buildinfo, $build_dir, $files, $libs, &$target, &$out) {
		$filename_log = $build_dir.DIRECTORY_SEPARATOR.$target['short_name'].'.log';
		$filename_out = $build_dir.DIRECTORY_SEPARATOR.$target['short_name'].'.exe';
		$filename_rsp = $build_dir.DIRECTORY_SEPARATOR.$target['short_name'].'.rsp';
		
		$flags = '/LTCG ';
		// /MACHINE:{ARM|EBC|X64|X86}
		// http://msdn.microsoft.com/ru-ru/library/9a89h429.aspx
		if($buildinfo->getPlatform() == 'x32') $flags .= ' /SAFESEH /MACHINE:X86 ';
		// http://msdn.microsoft.com/ru-ru/library/dn195771.aspx
		if($buildinfo->getPlatform() == 'x64') $flags .= ' /HIGHENTROPYVA /MACHINE:X64 ';		
		$depends = self::file_list($this->initDepends($target));
		file_put_contents($filename_rsp, $files.$flags."\n".$includes."\n".$depends."\n".'/OUT:"'.$filename_out.'"');
		
		$cmd = 'link.exe /nologo @"'.$filename_rsp.'"';
		echo $cmd."\n";
		Build::get()->addScript(array(  'home_dir' => $this->bin_path,
										'script_name' => $cmd,
										'env' => $this->env,
										'log_file' => $filename_log
										));
												
		$out = $filename_out;
	}

	private function obj2dll($buildinfo, $build_dir, $files, $libs, &$target, &$out) {
		$filename_log = $build_dir.DIRECTORY_SEPARATOR.$target['short_name'].'.log';
		$filename_out = $build_dir.DIRECTORY_SEPARATOR.$target['short_name'].'.dll';
		$filename_rsp = $build_dir.DIRECTORY_SEPARATOR.$target['short_name'].'.rsp';
		
		$flags = '/LTCG ';
		// /MACHINE:{ARM|EBC|X64|X86}
		// http://msdn.microsoft.com/ru-ru/library/9a89h429.aspx
		if($buildinfo->getPlatform() == 'x32') $flags .= ' /SAFESEH /MACHINE:X86 ';
		// http://msdn.microsoft.com/ru-ru/library/dn195771.aspx
		if($buildinfo->getPlatform() == 'x64') $flags .= ' /HIGHENTROPYVA /MACHINE:X64 ';		
		$depends = self::file_list($this->initDepends($target));
		file_put_contents($filename_rsp, $files.$flags."\n".$includes."\n".$depends."\n".'/OUT:"'.$filename_out.'"');
		
		$cmd = 'link.exe /nologo @"'.$filename_rsp.'"';
		echo $cmd."\n";
		Build::get()->addScript(array(  'home_dir' => $this->bin_path,
										'script_name' => $cmd,
										'env' => $this->env,
										'log_file' => $filename_log
										));
												
		$out = $filename_out;
	}
		
	private function obj2sys($buildinfo, $build_dir, $files, $libs, &$target, &$out) {
		$filename_log = $build_dir.DIRECTORY_SEPARATOR.$target['short_name'].'.log';
		$filename_out = $build_dir.DIRECTORY_SEPARATOR.$target['short_name'].'.dll';
		$filename_rsp = $build_dir.DIRECTORY_SEPARATOR.$target['short_name'].'.rsp';
		
		$flags = '/LTCG ';
		// /MACHINE:{ARM|EBC|X64|X86}
		// http://msdn.microsoft.com/ru-ru/library/9a89h429.aspx
		if($buildinfo->getPlatform() == 'x32') $flags .= ' /SAFESEH /MACHINE:X86 ';
		// http://msdn.microsoft.com/ru-ru/library/dn195771.aspx
		if($buildinfo->getPlatform() == 'x64') $flags .= ' /HIGHENTROPYVA /MACHINE:X64 ';		
		$depends = self::file_list($this->initDepends($target));
		file_put_contents($filename_rsp, $files.$flags."\n".$includes."\n".$depends."\n".'/OUT:"'.$filename_out.'"');
		
		$cmd = 'link.exe /nologo @"'.$filename_rsp.'"';
		echo $cmd."\n";
		Build::get()->addScript(array(  'home_dir' => $this->bin_path,
										'script_name' => $cmd,
										'env' => $this->env,
										'log_file' => $filename_log
										));
												
		$out = $filename_out;
	}

	private static function make_include($includes) {
		return BuildUtils::array2string($includes, '/I"', "\" \n");
	}

	private static function add_env_path($path) {
		return BuildUtils::array2string($path, '"', '";');
	}

	private static function file_list($files) {
		return BuildUtils::array2string($files, '"', "\" \n");
	}

	public function printTimers() {
		echo "==========\n";
		echo 'Tool: '.__CLASS__."\n";
		echo 'Compile time: '.$this->time_cl.' ms'."\n";
		echo 'Link time: '.$this->time_link.' ms'."\n";
		echo "==========\n";
	}
}
