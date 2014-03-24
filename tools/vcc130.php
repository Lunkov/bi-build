<?

class VCC130 {
	private $tool_cl = '';
	private $tool_ml = '';
	private $tool_lib = '';
	private $tool_link = '';
	private $tool_dumpbin = '';
	private $tool_editbin = '';
	private $tool_rc = '';
	private $tool_mt = '';
	private $tool_signtool = null;
	private $bin_path = '';
  private $home_path = '';
	private $sdk_bin_path = '';
	private $wdk_bin_path = '';
	private $qt_bin_path  = '';
	private $include_path = array();
	private $lib = array();
	private $lib_path = array();
  // ignore warnings
  // warning C4290: C++ exception specification ignored except to indicate a function is not __declspec(nothrow)
	private $cflags = '/nologo /wd4290 /GS /GL /FS /W3 /Gy /Zc:wchar_t /Gm- /O2 /sdl /D_UNICODE /DUNICODE /D_MSC_VER=1700 '; // /Zi  - debug?
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
			$this->include_path[] = $this->params['sdk_path'].DIRECTORY_SEPARATOR.'include\ddk';
			//$this->include_path[] = $this->params['sdk_path'].DIRECTORY_SEPARATOR.'include\winrt';
		}
		if(isset($this->params['qt_path'])) {
			$this->qt_bin_path = $this->params['qt_path'].DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR;
			
			$this->include_path[] = $this->params['qt_path'].DIRECTORY_SEPARATOR.'Include';
      $this->include_path[] = $this->params['qt_path'].DIRECTORY_SEPARATOR.'Include'.DIRECTORY_SEPARATOR.'QtANGLE';
      
		}

		$this->env['PATH'] .= self::add_env_path($this->bin_path);
		$this->env['PATH'] .= self::add_env_path($this->sdk_bin_path);
		$this->env['INCLUDE'] .= self::add_env_path($this->include_path);
		//$this->env['LIB'] .= self::add_env_path($this->lib);
		//$this->env['LIBPATH'] .= self::add_env_path($this->lib_path);

		//var_dump($this);
	}

  private function initBin($buildinfo) {
    if(isset($this->params['home_path'])) {
      $this->bin_path = '';
      $this->home_path = '';
      /*
       * http://msdn.microsoft.com/en-us/library/x4d2c09s.aspx
       */
			if(Enviroment::getPlatform() == 'i386' && $buildinfo->getPlatform() == 'x32') {
				$this->bin_path = $this->params['home_path'].DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR;
        $this->home_path = $this->params['home_path'].DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR;
			}
			if(Enviroment::getPlatform() == 'i386' && $buildinfo->getPlatform() == 'x64') {
				$this->bin_path = $this->params['home_path'].DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'x86_amd64'.DIRECTORY_SEPARATOR;
        $this->home_path = $this->params['home_path'].DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR;
			}
			if(Enviroment::getPlatform() == 'i386' && $buildinfo->getPlatform() == 'arm') {
				$this->bin_path = $this->params['home_path'].DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'x86_arm'.DIRECTORY_SEPARATOR;
        $this->home_path = $this->params['home_path'].DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR;
			}
			if(Enviroment::getPlatform() == 'AMD64' && $buildinfo->getPlatform() == 'x32') {
				$this->bin_path = $this->params['home_path'].DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'amd64_x86'.DIRECTORY_SEPARATOR;
        $this->home_path = $this->params['home_path'].DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'amd64'.DIRECTORY_SEPARATOR;
			}
			if(Enviroment::getPlatform() == 'AMD64' && $buildinfo->getPlatform() == 'arm') {
				$this->bin_path = $this->params['home_path'].DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'amd64_arm'.DIRECTORY_SEPARATOR;
        $this->home_path = $this->params['home_path'].DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'amd64'.DIRECTORY_SEPARATOR;
			}
			if(Enviroment::getPlatform() == 'AMD64' && $buildinfo->getPlatform() == 'x64') {
				$this->bin_path = $this->params['home_path'].DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'amd64'.DIRECTORY_SEPARATOR;
        $this->home_path = $this->params['home_path'].DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'amd64'.DIRECTORY_SEPARATOR;
			}
			$this->tool_cl      = 'call '.Utils::escapeshellcmd($this->bin_path.'cl.exe');
			$this->tool_ml      = 'call '.Utils::escapeshellcmd($this->bin_path.'ml64.exe');
			$this->tool_lib     = 'call '.Utils::escapeshellcmd($this->bin_path.'lib.exe');
			$this->tool_link    = 'call '.Utils::escapeshellcmd($this->bin_path.'link.exe');
			$this->tool_dumpbin = 'call '.Utils::escapeshellcmd($this->bin_path.'dumpbin.exe');
			$this->tool_editbin = 'call '.Utils::escapeshellcmd($this->bin_path.'editbin.exe');
    }
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
        $this->lib_path[] = $this->params['sdk_path'].DIRECTORY_SEPARATOR.'lib\winv6.3\km\x86'; // WDK !!!
        $this->lib_path[] = $this->params['sdk_path'].DIRECTORY_SEPARATOR.'lib\win7\km\x86'; // WDK !!!
			}
			if($buildinfo->getPlatform() == 'x64') {
				$this->lib_path[] = $this->params['sdk_path'].DIRECTORY_SEPARATOR.'lib\winv6.3\um\x64';
        $this->lib_path[] = $this->params['sdk_path'].DIRECTORY_SEPARATOR.'lib\winv6.3\km\x64'; // WDK !!!
        $this->lib_path[] = $this->params['sdk_path'].DIRECTORY_SEPARATOR.'lib\win7\km\x64'; // WDK !!!
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
				//if(isset($link['root']) && isset($link['target'])) {
					//$tg = Build::get()->getTarget($link['root'], $link['target']);
          $tg = Build::get()->getTargetByLink($link);
					if(!is_null($tg)) {
						$inc = array_merge($inc, $this->initIncludes($tg));
					}
					if(is_array($tg['include'])) {
						$inc = array_merge($inc, $tg['include']);
					}
				//}
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
				//if(isset($link['root']) && isset($link['target'])) {
					//$tg = Build::get()->getTarget($link['root'], $link['target']);
					//$result = Build::get()->getResult(BuildUtils::makeTargetPath($link['root'], $link['target']));
					$result = Build::get()->getResult($link);
					//var_dump($result);
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
				//}
			}
		}
		return array_keys(array_flip($inc));
	}
		
	private function compile($buildinfo, $build_dir, $target_name, $cflags, &$target) {
		if(!is_array($target['src'])) {
			return array();
		}
		
    Timers::get()->start('vcc.compile');
    
		$b_dir = Utils::mkdir($build_dir.$target['dir']);
		echo '+'.$b_dir."\n";
		
		$inc = $this->initIncludes($target);
		//var_dump($inc);
		$includes = self::make_include($inc);
		//echo 'includes: '.$includes."\n";
		
    $cflags.= ' /DAES_ERR_CHK ';
    $cflags.= ' /DNOT_INCLUDE_ENDIANS ';
    $cflags.= ' /DAES_DECRYPT ';
    $cflags.= ' /INTEGRITYCHECK ';

		$flags = $this->cflags. $cflags. ' /D_USING_V110_SDK71_ /D_CRT_SECURE_NO_WARNINGS /c /D_LIB /D__MSVCRT_VERSION__=0x0601 /DWINVER=0x501  /D_WIN32_WINNT=0x501 ';// /SUBSYSTEM:CONSOLE

		if($buildinfo->getPlatform() == 'x32') {
			$flags .= ' /DWIN32 /D_X86_ '; ////MACHINE:X86 
		}
		if($buildinfo->getPlatform() == 'x64') {
			$flags .= ' /D_WIN64 /D_AMD64_ /D_MBCS /D_LIB ';//:X64 
		}
		if($buildinfo->getPlatform() == 'ia64') {
			$flags .= ' /D_IA64 /D_IA64_ /D_MBCS /D_LIB ';//:IA64 
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
			//echo '++'.$b_dir."\n";
			switch($extention) {
			case 'def':
						$cl_result[] = $filename_in;
						break;
			case 'ts':
						$cl_result[] = $this->ts2qm($b_dir, $target['home_dir'], $filename_in);
						break;
			case 'rc':
						$cl_result[] = $this->rc2res($b_dir, $target['home_dir'], $filename_in, $flags, $includes);
						break;
			case 'h':
						break;
			case 'c':
						$cl_result[] = $this->c2obj($b_dir, $target['home_dir'], $filename_in, $flags, $includes);
						break;
			case 'cpp':
						$cl_result[] = $this->cpp2obj($b_dir, $target['home_dir'], $filename_in, $flags, $includes);
						break;
			default:
						//$cl_result[] = $b_dir.DIRECTORY_SEPARATOR.$filename_in;
						break;
			}
		}
		Build::get()->execScripts();
		Timers::get()->stop('vcc.compile');
		
		return $cl_result;
	}
	
	public function static_lib($buildinfo, $build_dir, $target_name, &$target) {
    
    $this->initBin($buildinfo);
    
		$b_dir = Utils::mkdir($build_dir.$target['dir']);
		echo '+'.$b_dir."\n";
		
		$result = $build_dir.$target['dir'].DIRECTORY_SEPARATOR.$target['short_name'].'.lib';
		echo 'result = '.$result."\n";
		Build::get()->setResult($target_name, $result);
		//TODO!!!
		//if(file_exists($result)) return;
				
		$this->initLibs($buildinfo);
		
		$cl_result = $this->compile($buildinfo, $build_dir, $target_name, '/DSECURITY_WIN32 ', $target); 
    		
    Timers::get()->start('vcc.link');
		$link_result = $this->obj2lib($buildinfo, $build_dir.$target['dir'].DIRECTORY_SEPARATOR, self::file_list(self::link_files($cl_result)), $libs, $target);
		Build::get()->execScripts();
		Timers::get()->stop('vcc.link');
		
		//Build::get()->setResult($target_name, $link_result);
	}

	public function dynamic_lib($buildinfo, $build_dir, $target_name, &$target) {
    $this->initBin($buildinfo);
    
		$b_dir = Utils::mkdir($build_dir.$target['dir']);
		
		$result = $build_dir.$target['dir'].DIRECTORY_SEPARATOR.$target['short_name'].'.dll';
		echo 'result = '.$result."\n";
		Build::get()->setResult($target_name, $result);
		//TODO!!!
		//if(file_exists($result)) return;
				
		$this->initLibs($buildinfo);
		
		$cl_result = $this->compile($buildinfo, $build_dir, $target_name, '/DSECURITY_WIN32 ', $target); 
		
    Timers::get()->start('vcc.link');
		$link_result = $this->obj2dll($buildinfo, $build_dir.$target['dir'].DIRECTORY_SEPARATOR, self::file_list(self::link_files($cl_result)), $libs, $target);
		Build::get()->execScripts();
		Timers::get()->stop('vcc.link');
		
		//Build::get()->setResult($target_name, $link_result);
	}
	
	public function console_exe($buildinfo, $build_dir, $target_name, &$target) {
    $this->initBin($buildinfo);
    
		$b_dir = Utils::mkdir($build_dir.$target['dir']);
		
		$result = $build_dir.$target['dir'].DIRECTORY_SEPARATOR.$target['short_name'].'.exe';
		echo 'result = '.$result."\n";
		Build::get()->setResult($target_name, $result);
		//TODO!!!
		//if(file_exists($result)) return;
				
		$this->initLibs($buildinfo);
		
		$cl_result = $this->compile($buildinfo, $build_dir, $target_name, '', $target); 
		
    Timers::get()->start('vcc.link');
		$link_result = $this->obj2exe($buildinfo, $build_dir.$target['dir'].DIRECTORY_SEPARATOR, self::file_list(self::link_files($cl_result)), $libs, $target);
		Build::get()->execScripts();
		Timers::get()->stop('vcc.link');
		
		//Build::get()->setResult($target_name, $link_result);
	}

	public function win_exe($buildinfo, $build_dir, $target_name, &$target) {
    $this->initBin($buildinfo);
    
		$b_dir = Utils::mkdir($build_dir.$target['dir']);
		
		$result = $build_dir.$target['dir'].DIRECTORY_SEPARATOR.$target['short_name'].'.exe';
		echo 'result = '.$result."\n";
		Build::get()->setResult($target_name, $result);
		//TODO!!!
		//if(file_exists($result)) return;
				
		$this->initLibs($buildinfo);
		
		$cl_result = $this->compile($buildinfo, $build_dir, $target_name, '/DSECURITY_WIN32 ', $target); 
		
    Timers::get()->start('vcc.link');
		$link_result = $this->obj2exe($buildinfo, $build_dir.$target['dir'].DIRECTORY_SEPARATOR, self::file_list(self::link_files($cl_result)), $libs, $target);
		Build::get()->execScripts();
		Timers::get()->stop('vcc.link');
	
		//Build::get()->setResult($target_name, $link_result);
	}
		
	public function driver($buildinfo, $build_dir, $target_name, &$target) {

		$b_dir = Utils::mkdir($build_dir.$target['dir']);
		
		$result = $build_dir.$target['dir'].DIRECTORY_SEPARATOR.$target['short_name'].'.sys';
		//Build::get()->setResult($target_name, $result);
		
		echo 'DRIVER = '.$result."\n";
		//TODO!!!
		//if(file_exists($result)) return;
				
		$this->initLibs($buildinfo);
		
		$cl_result = $this->compile($buildinfo, $build_dir, $target_name, ' /DMSVS_TEST_DRV /DSECURITY_KERNEL /DNTDDI_VERSION=NTDDI_WINXPSP3 /D_WIN2K_COMPAT_SLIST_USAGE ', $target); 
		
    Timers::get()->start('vcc.link');
		$link_result = $this->obj2sys($buildinfo, $build_dir.$target['dir'].DIRECTORY_SEPARATOR, self::file_list($cl_result), $libs, $target);
		Build::get()->execScripts();
		Timers::get()->stop('vcc.link');
		
    
		//Build::get()->setResult($target_name, $link_result);

	}	
	
	private function c2obj($build_dir, $home_dir, $file, $flags, $includes) {

    $dir = Utils::mkdir($build_dir.DIRECTORY_SEPARATOR.Utils::getRelativePath($home_dir, $file));// TODO .DIRECTORY_SEPARATOR.Utils::getPath($file));
    $fn = $dir.DIRECTORY_SEPARATOR.Utils::getFileName($file);
		$filename_log = $fn.'.log';
		$filename_out = $fn.'.obj';
		$filename_rsp = $fn.'.rsp';

    echo 'SKIP?: '.$file.' changed='.Build::get()->fileChanged($file)."\n";
    if(!Build::get()->fileChanged($file) && file_exists($filename_out)) {
      echo 'SKIP: '.$file."\n";
      return $filename_out;
    }
		
		//file_put_contents($filename_rsp, '/Tc"'.$file."\"\n".$flags."\n".$includes."\n".'/Fo:"'.$filename_out.'"');
		file_put_contents($filename_rsp, '/Tc "'.$file."\"\n".$flags."\n".$includes."\n".'/Fo:"'.$filename_out.'"');
		
		$cmd = $this->tool_cl.' /nologo @"'.$filename_rsp.'"';
		//echo $cmd."\n";
		Build::get()->addScript(array(  'home_dir' => $this->home_path,
										'script_name' => $cmd,
										'env' => $this->env,
										'log_file' => $filename_log
										));
		
		return $filename_out;
	}

	private function cpp2obj($build_dir, $home_dir, $file, $flags, $includes) {

    $dir = Utils::mkdir($build_dir.DIRECTORY_SEPARATOR.Utils::getRelativePath($home_dir, $file));// TODO .DIRECTORY_SEPARATOR.Utils::getPath($file));
    $fn = $dir.DIRECTORY_SEPARATOR.Utils::getFileName($file);
		$filename_log = $fn.'.log';
		$filename_out = $fn.'.obj';
		$filename_rsp = $fn.'.rsp';
    
    if(!Build::get()->fileChanged($file) && file_exists($filename_out)) {
      echo 'SKIP: '.$file."\n";
      return $filename_out;
    }
		$flags.=' /EHsc ';
		file_put_contents($filename_rsp, '/Tp"'.$file."\"\n".$flags."\n".$includes."\n".'/Fo:"'.$filename_out.'"');
		
		$cmd = $this->tool_cl.' /nologo @"'.$filename_rsp.'"';
		//echo $cmd."\n";
		Build::get()->addScript(array(
                    'home_dir' => $this->home_path,
										'script_name' => $cmd,
										'env' => $this->env,
										'log_file' => $filename_log
										));
		
		return $filename_out;
	}

	private function rc2res($build_dir, $home_dir, $file, $flags, $includes) {

    $dir = Utils::mkdir($build_dir.DIRECTORY_SEPARATOR.Utils::getRelativePath($home_dir, $file));// TODO .DIRECTORY_SEPARATOR.Utils::getPath($file));
    $fn = $dir.DIRECTORY_SEPARATOR.Utils::getFileName($file);
		$filename_log = $fn.'.log';
		$filename_out = $fn.'.res';
		$filename_rsp = $fn.'.rsp';

		$flags =' /DA_VERSION_MAJOR=7 /DA_VERSION_MINOR=9 /DA_VERSION_BUILD=0 /DA_SVN_REV=0 ';
		//file_put_contents($filename_rsp, '/Tp"'.$file."\"\n".$flags."\n".$includes."\n".'/Fo:"'.$filename_out.'"');
		
		//$cmd = $this->tool_rc.' /nologo @"'.$filename_rsp.'"';
    $cmd = $this->tool_rc.' /nologo -l 0x409 '.$flags.' /I"C:\tools\SDK\8.1\Include\api" /v /fo "'.$filename_out.'" "'.$file.'"';
		//echo $cmd."\n";
		Build::get()->addScript(array(  'home_dir' => $this->sdk_bin_path,
										'script_name' => $cmd,
										'env' => $this->env,
										'log_file' => $filename_log
										));
		
		return $filename_out;
	}

	private function ts2qm($build_dir, $home_dir, $file) {

    $dir = Utils::mkdir($build_dir.DIRECTORY_SEPARATOR.Utils::getRelativePath($home_dir, $file));
    $fn = $dir.DIRECTORY_SEPARATOR.Utils::getFileName($file);
		$filename_log = $fn.'.log';
		$filename_out = $fn.'.qm';

		//file_put_contents($filename_rsp, '/Tp"'.$file."\"\n".$flags."\n".$includes."\n".'/Fo:"'.$filename_out.'"');
		
		$cmd = 'lrelease.exe "'.$file.'" -qm "'.$filename_out.'"';
		//echo $cmd."\n";
		Build::get()->addScript(array(  'home_dir' => $this->qt_bin_path,
										'script_name' => $cmd,
										'env' => $this->env,
										'log_file' => $filename_log
										));
		
		return $filename_out;
	}
		
	private function obj2lib($buildinfo, $build_dir, $files, $libs, &$target) {
		$filename_log = $build_dir.DIRECTORY_SEPARATOR.$target['short_name'].'.log';
		$filename_out = $build_dir.DIRECTORY_SEPARATOR.$target['short_name'].'.lib';
		$filename_rsp = $build_dir.DIRECTORY_SEPARATOR.$target['short_name'].'.rsp';
		
		$flags = '/LTCG /SUBSYSTEM:CONSOLE ';///NODEFAULTLIB:LIBCMT 
		// /MACHINE:{ARM|EBC|X64|X86}
		// http://msdn.microsoft.com/ru-ru/library/9a89h429.aspx
		if($buildinfo->getPlatform() == 'x32') $flags .= ' /SAFESEH /MACHINE:X86 ';
		// http://msdn.microsoft.com/ru-ru/library/dn195771.aspx
		if($buildinfo->getPlatform() == 'x64') $flags .= ' /MACHINE:X64 ';	///HIGHENTROPYVA 
    if($buildinfo->getPlatform() == 'arm') $flags .= ' /MACHINE:ARM ';	///HIGHENTROPYVA 
		if($buildinfo->getVariant() == 'product') {
			$flags .= ' /MT /RELEASE ';
		}
		if($buildinfo->getVariant() == 'develop') {
			$flags .= ' /MT /RELEASE ';
		}
		$depends = self::file_list($this->initDepends($target));
		echo 'dependses: '.$depends."\n";
		file_put_contents($filename_rsp, $files.$flags."\n".$includes."\n".$depends."\n".'/OUT:"'.$filename_out.'"');
		
		$cmd = $this->tool_lib.' /nologo @"'.$filename_rsp.'"';
		echo $cmd."\n";
		//var_dump($this->env);
		Build::get()->addScript(array(  'home_dir' => $this->home_path,
										'script_name' => $cmd,
										'env' => $this->env,
										'log_file' => $filename_log
										));
												
		return $filename_out;
	}

	private function obj2exe($buildinfo, $build_dir, $files, $libs, &$target) {
		$filename_log = $build_dir.DIRECTORY_SEPARATOR.$target['short_name'].'.log';
		$filename_out = $build_dir.DIRECTORY_SEPARATOR.$target['short_name'].'.exe';
		$filename_rsp = $build_dir.DIRECTORY_SEPARATOR.$target['short_name'].'.rsp';
		
		$flags = '/LTCG ';
		// /MACHINE:{ARM|EBC|X64|X86}
		// http://msdn.microsoft.com/ru-ru/library/9a89h429.aspx
		if($buildinfo->getPlatform() == 'x32') $flags .= ' /SAFESEH /MACHINE:X86 ';
		// http://msdn.microsoft.com/ru-ru/library/dn195771.aspx
		if($buildinfo->getPlatform() == 'x64') $flags .= ' /HIGHENTROPYVA /MACHINE:X64 ';		
    if($buildinfo->getPlatform() == 'arm') $flags .= ' /MACHINE:ARM ';
		$depends = self::file_list($this->initDepends($target));
		echo 'dependses: '.$depends."\n";
		file_put_contents($filename_rsp, $files.$flags."\n".$includes."\n".$depends."\n".'/OUT:"'.$filename_out.'"');
		
		$cmd = $this->tool_link.' /nologo @"'.$filename_rsp.'"';
		echo $cmd."\n";
		Build::get()->addScript(array(  'home_dir' => $this->home_path,
										'script_name' => $cmd,
										'env' => $this->env,
										'log_file' => $filename_log
										));
												
		return $filename_out;
	}

	private function obj2dll($buildinfo, $build_dir, $files, $libs, &$target) {
		$filename_log = $build_dir.DIRECTORY_SEPARATOR.$target['short_name'].'.log';
		$filename_out = $build_dir.DIRECTORY_SEPARATOR.$target['short_name'].'.dll';
		$filename_rsp = $build_dir.DIRECTORY_SEPARATOR.$target['short_name'].'.rsp';
		
		$flags = '/LTCG ';
		// /MACHINE:{ARM|EBC|X64|X86}
		// http://msdn.microsoft.com/ru-ru/library/9a89h429.aspx
		if($buildinfo->getPlatform() == 'x32') $flags .= ' /SAFESEH /MACHINE:X86 ';
		// http://msdn.microsoft.com/ru-ru/library/dn195771.aspx
		if($buildinfo->getPlatform() == 'x64') $flags .= ' /HIGHENTROPYVA /MACHINE:X64 ';		
    if($buildinfo->getPlatform() == 'arm') $flags .= ' /MACHINE:ARM ';	
		$depends = self::file_list($this->initDepends($target));
		file_put_contents($filename_rsp, $files.$flags."\n".$includes."\n".$depends."\n".'/OUT:"'.$filename_out.'"');
		
		$cmd = $this->tool_link.' /nologo @"'.$filename_rsp.'"';
		echo $cmd."\n";
		Build::get()->addScript(array(  'home_dir' => $this->home_path,
										'script_name' => $cmd,
										'env' => $this->env,
										'log_file' => $filename_log
										));
												
		return $filename_out;
	}
		
	private function obj2sys($buildinfo, $build_dir, $files, $libs, &$target) {
		$filename_log = $build_dir.DIRECTORY_SEPARATOR.$target['short_name'].'.log';
		$filename_out = $build_dir.DIRECTORY_SEPARATOR.$target['short_name'].'.sys';
		$filename_rsp = $build_dir.DIRECTORY_SEPARATOR.$target['short_name'].'.rsp';
		
		$flags = ' /RELEASE /kernel /OPT:REF /OPT:ICF /MERGE:_PAGE=PAGE /MERGE:_TEXT=.text /SECTION:INIT,d  /INTEGRITYCHECK /LTCG  /SUBSYSTEM:WINDOWS /EHsc fltMgr.lib ntstrsafe.lib kernl32p.lib wdmsec.lib BufferOverflowK.lib ntoskrnl.lib hal.lib wmilib.lib ';
		// /MACHINE:{ARM|EBC|X64|X86}
		// http://msdn.microsoft.com/ru-ru/library/9a89h429.aspx
		if($buildinfo->getPlatform() == 'x32') $flags .= ' /SAFESEH /MACHINE:X86 /align:0x80 /functionpadmin:5 /entry:GsDriverEntry@8 ';
		// http://msdn.microsoft.com/ru-ru/library/dn195771.aspx
		if($buildinfo->getPlatform() == 'x64') $flags .= ' /HIGHENTROPYVA /MACHINE:X64 /functionpadmin:6 /entry:GsDriverEntry ';		
    if($buildinfo->getPlatform() == 'arm') $flags .= ' /MACHINE:ARM ';
		$depends = self::file_list($this->initDepends($target));
		file_put_contents($filename_rsp, $files.$flags."\n".$includes."\n".$depends."\n".'/OUT:"'.$filename_out.'"');
		
		$cmd = $this->tool_link.' /nologo @"'.$filename_rsp.'"';
		echo $cmd."\n";
		Build::get()->addScript(array(  'home_dir' => $this->home_path,
										'script_name' => $cmd,
										'env' => $this->env,
										'log_file' => $filename_log
										));
												
		return $filename_out;
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
  
	private static function link_files($files) {
    $ret = array();
    foreach($files as $file) {
      $extention  = Utils::getFileExtension($file);
      if($extention == 'obj' || $extention == 'res') {
        $ret[] = $file;
      }
    }
    return $ret;
	}
  
}
