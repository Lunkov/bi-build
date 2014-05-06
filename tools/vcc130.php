<?php

class VCC130 {
  private $tool_inf2cat = '';
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
      $this->tool_inf2cat = $this->params['sdk_path'].DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'x86'.DIRECTORY_SEPARATOR.'inf2cat.exe';

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
    if(!is_object($buildinfo)) {
      Logger::get()->out(Logger::Critical, "BuildInfo is not object\n". var_export($buildinfo));
      return;
    }
		
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
		$inc = array_merge($inc, $target->getIncludes());
		//var_dump($target['link']);
		if(is_array($target->getLinks())) {
			foreach($target->getLinks() as $link) {
				//var_dump($link);
				//if(isset($link['root']) && isset($link['target'])) {
					//$tg = Build::get()->getTarget($link['root'], $link['target']);
          $tg = Build::get()->getTargetByLink($link);
					if(!is_null($tg)) {
						$inc = array_merge($inc, $this->initIncludes($tg));
					}
					if(is_array($tg->getIncludes())) {
						$inc = array_merge($inc, $tg->getIncludes());
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
		if(is_array($target->getLinks())) {
			foreach($target->getLinks() as $link) {
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
		
	private function compile($buildinfo, $build_dir, $cflags, &$target) {
		if(!is_array($target->getSrc())) {
			return array();
		}
		
    Timers::get()->start('vcc.compile');
    
		$b_dir = Utils::mkdir($build_dir.$target->getShortDir());
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
			$flags .= ' /DWIN32 /D_X86_ /Di386 '; ////MACHINE:X86 
		}
		if($buildinfo->getPlatform() == 'x64') {
			$flags .= ' /D_WIN64 /D_AMD64_ /DAMD64 /D_MBCS /D_LIB ';//:X64 
		}
		if($buildinfo->getPlatform() == 'ia64') {
			$flags .= ' /D_IA64 /D_IA64_ /D_MBCS /D_LIB ';//:IA64 
		}
		
		if($buildinfo->getVariant() == 'develop') {
			$flags .= ' /MT /analyze- /DNDEBUG /DVLOG_ENABLE ';
		}
		if($buildinfo->getVariant() == 'production') {
			$flags .= ' /MT /analyze- /DNDEBUG ';
		}
		if($buildinfo->getVariant() == 'debug') {
			$flags .= ' /MTd /analyze- /Od /D_DEBUG /DASSERT_ENABLE /DVLOG_ENABLE ';
		}
		
		$cl_result = array();
		foreach($target->getSrc() as $file) {
			//$filename_in = $target->getHomeDir().DIRECTORY_SEPARATOR.$file;
			$filename_in = $file;
			$extention  = Utils::getFileExtension($file);
			//echo '++'.$b_dir."\n";
			switch($extention) {
			case 'def':
						$cl_result[] = $filename_in;
						break;
			case 'ts':
						$cl_result[] = $this->ts2qm($b_dir, $target, $filename_in);
						break;
			case 'rc':
						$cl_result[] = $this->rc2res($b_dir, $target, $filename_in, $flags, $includes);
						break;
			case 'h':
						break;
			case 'c':
						$cl_result[] = $this->c2obj($b_dir, $target, $filename_in, $flags, $includes);
						break;
			case 'cpp':
						$cl_result[] = $this->cpp2obj($b_dir, $target, $filename_in, $flags, $includes);
						break;
			default:
						//$cl_result[] = $b_dir.DIRECTORY_SEPARATOR.$filename_in;
						break;
			}
		}
		Timers::get()->stop('vcc.compile');
		
		return $cl_result;
	}
	
	public function static_lib($params) {
    
    $target = $params['target'];
    $this->initBin($params['buildinfo']);
    
		$b_dir = Utils::mkdir($params['build_dir'].$target->getShortDir());
		echo '+'.$b_dir."\n";
		
		$result = $build_dir.$target->getShortDir().DIRECTORY_SEPARATOR.$target->getShortName().'.lib';
		echo 'result = '.$result."\n";
		Build::get()->setResult($target_name, $result);
		//TODO!!!
		//if(file_exists($result)) return;
				
		$this->initLibs($params['buildinfo']);
		
		$cl_result = $this->compile($params['buildinfo'], $build_dir, '/DSECURITY_WIN32 ', $target); 
    		
    Timers::get()->start('vcc.link');
		$link_result = $this->obj2lib($params['buildinfo'], $build_dir.$target->getShortDir().DIRECTORY_SEPARATOR, self::file_list(self::link_files($cl_result)), $libs, $target);
		Timers::get()->stop('vcc.link');
		
		//Build::get()->setResult($target_name, $link_result);
	}

	public function dynamic_lib($params) {
        echo 'yuyuyu';
    $this->initBin($params['buildinfo']);
    
		$b_dir = Utils::mkdir($build_dir.$target->getShortDir());
		
		$result = $build_dir.$target->getShortDir().DIRECTORY_SEPARATOR.$target->getShortName().'.dll';
		echo 'result = '.$result."\n";
		Build::get()->setResult($target_name, $result);
		//TODO!!!
		//if(file_exists($result)) return;
				
		$this->initLibs($buildinfo);
		
		$cl_result = $this->compile($params['buildinfo'], $build_dir, '/DSECURITY_WIN32 ', $target); 
		
    Timers::get()->start('vcc.link');
		$link_result = $this->obj2dll($params['buildinfo'], $build_dir.$target->getShortDir().DIRECTORY_SEPARATOR, self::file_list(self::link_files($cl_result)), $libs, $target);
		Timers::get()->stop('vcc.link');
		
		//Build::get()->setResult($target_name, $link_result);
	}
	
	public function console_exe($params) {
    $target = $params['target'];
    $build_dir = $params['dir'];
    $this->initBin($params['buildinfo']);
    
		$b_dir = Utils::mkdir($build_dir.$target->getShortDir());
		
		$result = $build_dir.$target->getShortDir().DIRECTORY_SEPARATOR.$target->getShortName().'.exe';
		echo 'result = '.$result."\n";
		Build::get()->setResult($target_name, $result);
		//TODO!!!
		//if(file_exists($result)) return;
				
		$this->initLibs($buildinfo);
		
		$cl_result = $this->compile($params['buildinfo'], $build_dir, '', $target); 
		
    Timers::get()->start('vcc.link');
		$link_result = $this->obj2exe($params['buildinfo'], $build_dir.$target->getShortDir().DIRECTORY_SEPARATOR, self::file_list(self::link_files($cl_result)), $libs, $target);
		Timers::get()->stop('vcc.link');
		
		//Build::get()->setResult($target_name, $link_result);
	}

	public function win_exe($params) {
    $target = $params['target'];
    $this->initBin($params['buildinfo']);
    
		$b_dir = Utils::mkdir($build_dir.$target->getShortDir());
		
		$result = $build_dir.$target->getShortDir().DIRECTORY_SEPARATOR.$target->getShortName().'.exe';
		echo 'result = '.$result."\n";
		Build::get()->setResult($target_name, $result);
		//TODO!!!
		//if(file_exists($result)) return;
				
		$this->initLibs($params['buildinfo']);
		
		$cl_result = $this->compile($params['buildinfo'], $build_dir, '/DSECURITY_WIN32 ', $target); 
		
    Timers::get()->start('vcc.link');
		$link_result = $this->obj2exe($params['buildinfo'], $build_dir.$target->getShortDir().DIRECTORY_SEPARATOR, self::file_list(self::link_files($cl_result)), $libs, $target);
		Timers::get()->stop('vcc.link');
	
		//Build::get()->setResult($target_name, $link_result);
	}
		
	public function driver($params) {
    $target = $params['target'];
		$b_dir = Utils::mkdir($build_dir.$target->getShortDir());
		
		$result = $build_dir.$target->getShortDir().DIRECTORY_SEPARATOR.$target->getShortName().'.sys';
		//Build::get()->setResult($target_name, $result);
		
		echo 'DRIVER = '.$result."\n";
		//TODO!!!
		//if(file_exists($result)) return;
				
		$this->initLibs($params['buildinfo']);
		
		$cl_result = $this->compile($params['buildinfo'], $build_dir, ' /hotpatch /kernel /DMSVS_TEST_DRV /DSECURITY_KERNEL /DNTDDI_VERSION=NTDDI_WINXPSP3 /D_WIN2K_COMPAT_SLIST_USAGE ', $target); 
		
    Timers::get()->start('vcc.link');
		$link_result = $this->obj2sys($params['buildinfo'], $build_dir.$target->getShortDir().DIRECTORY_SEPARATOR, self::file_list($cl_result), $libs, $target);
		Timers::get()->stop('vcc.link');
		
    $b_dir = $build_dir.$target->getShortDir().DIRECTORY_SEPARATOR.'.signdrv';
    Utils::rmdir($b_dir);
    $b_dir = Utils::mkdir($b_dir);
    copy($result, $b_dir.DIRECTORY_SEPARATOR.Utils::getBaseName($result));
    $inf_file = BuildUtils::getFileByExt($target->getSrc(), 'inf');
    if(isset($inf_file)) {
      copy($inf_file, $b_dir.DIRECTORY_SEPARATOR.Utils::getBaseName($inf_file));
    }
    $sign = new SignTool();
    $sign->init(array(
                  'home_dir' => $this->sdk_bin_path,
                  'sign' => $target->getSign(),
                ));
    $sign->sign($b_dir.DIRECTORY_SEPARATOR.Utils::getBaseName($result));
    $cmd = 'call "'.$this->tool_inf2cat.'" /os:Server2008R2_X64,Server2008_X64,Server2003_X64,XP_X64,Vista_X64,7_X64,8_X64,Server8_X64 /driver:"'.$b_dir.'"';
    Build::get()->addScript($target->getName(), array(  'home_dir' => $this->home_path,
										'script_name' => $cmd,
										'env' => $this->env,
										//'log_file' => $filename_log
										));
    $sign->sign($b_dir.DIRECTORY_SEPARATOR.Utils::getFileName($result).'.cat');
    
		//Build::get()->setResult($target_name, $link_result);

	}	
	
	private function c2obj($build_dir, $target, $file, $flags, $includes) {

    $dir = Utils::mkdir($build_dir.DIRECTORY_SEPARATOR.Utils::getRelativePath($target->getHomeDir(), $file));// TODO .DIRECTORY_SEPARATOR.Utils::getPath($file));
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
		Build::get()->addScript($target->getName(), array(  'home_dir' => $this->home_path,
										'script_name' => $cmd,
										'env' => $this->env,
										'log_file' => $filename_log
										));
		
		return $filename_out;
	}

	private function cpp2obj($build_dir, $target, $file, $flags, $includes) {

    $dir = Utils::mkdir($build_dir.DIRECTORY_SEPARATOR.Utils::getRelativePath($target->getHomeDir(), $file));// TODO .DIRECTORY_SEPARATOR.Utils::getPath($file));
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
		Build::get()->addScript($target->getName(), array(
                    'home_dir' => $this->home_path,
										'script_name' => $cmd,
										'env' => $this->env,
										'log_file' => $filename_log
										));
		
		return $filename_out;
	}

	private function rc2res($build_dir, $target, $file, $flags, $includes) {

    $dir = Utils::mkdir($build_dir.DIRECTORY_SEPARATOR.Utils::getRelativePath($target->getHomeDir(), $file));// TODO .DIRECTORY_SEPARATOR.Utils::getPath($file));
    $fn = $dir.DIRECTORY_SEPARATOR.Utils::getFileName($file);
		$filename_log = $fn.'.log';
		$filename_out = $fn.'.res';
		$filename_rsp = $fn.'.rsp';
    
    //unlink($filename_out);

		$flags =' /dBI_VERSION_MAJOR="'.Vars::get()->getVar('info.version.major').'"';
    $flags .=' /dBI_VERSION_MINOR="'.Vars::get()->getVar('info.version.minor').'"';
    $flags .=' /dBI_VERSION_BUILD="'.Vars::get()->getVar('info.version.build').'"';
    $flags .=' /dBI_SVN_REV="'.Vars::get()->getVar('info.revision').'"';
    $flags .=' /dBI_RC_COPYRIGHT="\"'.Vars::get()->getVar('info.copyright').'\""';
    $flags .=' /dBI_RC_COMPANY_NAME="\"'.Vars::get()->getVar('info.companyname').'\""';
    $flags .=' ';
    
		//file_put_contents($filename_rsp, '/Tp"'.$file."\"\n".$flags."\n".$includes."\n".'/Fo:"'.$filename_out.'"');
		
		//$cmd = $this->tool_rc.' /nologo @"'.$filename_rsp.'"';
    $cmd = $this->tool_rc.' /nologo /r /l 0x409 '.$flags.' /i"C:\tools\SDK\8.1\Include\api" /v /fo "'.$filename_out.'" "'.$file.'"';
		//echo $cmd."\n";
		Build::get()->addScript($target->getName(), array(  'home_dir' => $this->sdk_bin_path,
										'script_name' => $cmd,
										'env' => $this->env,
										'log_file' => $filename_log
										));
		
		return $filename_out;
	}

	private function ts2qm($build_dir, $target, $file) {

    $dir = Utils::mkdir($build_dir.DIRECTORY_SEPARATOR.Utils::getRelativePath($target->getHomeDir(), $file));
    $fn = $dir.DIRECTORY_SEPARATOR.Utils::getFileName($file);
		$filename_log = $fn.'.log';
		$filename_out = $fn.'.qm';

		//file_put_contents($filename_rsp, '/Tp"'.$file."\"\n".$flags."\n".$includes."\n".'/Fo:"'.$filename_out.'"');
		
		$cmd = 'lrelease.exe "'.$file.'" -qm "'.$filename_out.'"';
		//echo $cmd."\n";
		Build::get()->addScript($target->getName(), array(  'home_dir' => $this->qt_bin_path,
										'script_name' => $cmd,
										'env' => $this->env,
										'log_file' => $filename_log
										));
		
		return $filename_out;
	}
		
	private function obj2lib($buildinfo, $build_dir, $files, $libs, &$target) {
		$filename_log = $build_dir.DIRECTORY_SEPARATOR.$target->getShortName().'.log';
		$filename_out = $build_dir.DIRECTORY_SEPARATOR.$target->getShortName().'.lib';
		$filename_rsp = $build_dir.DIRECTORY_SEPARATOR.$target->getShortName().'.rsp';
		
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
		Build::get()->addScript($target->getName(), array(  'home_dir' => $this->home_path,
										'script_name' => $cmd,
										'env' => $this->env,
										'log_file' => $filename_log
										));
												
		return $filename_out;
	}

	private function obj2exe($buildinfo, $build_dir, $files, $libs, &$target) {
		$filename_log = $build_dir.DIRECTORY_SEPARATOR.$target->getShortName().'.log';
		$filename_out = $build_dir.DIRECTORY_SEPARATOR.$target->getShortName().'.exe';
		$filename_rsp = $build_dir.DIRECTORY_SEPARATOR.$target->getShortName().'.rsp';
		
		$flags = '/LTCG /SUBSYSTEM:CONSOLE ';
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
		Build::get()->addScript($target->getName(), array(  'home_dir' => $this->home_path,
										'script_name' => $cmd,
										'env' => $this->env,
										'log_file' => $filename_log
										));
												
		return $filename_out;
	}

	private function obj2dll($buildinfo, $build_dir, $files, $libs, &$target) {
		$filename_log = $build_dir.DIRECTORY_SEPARATOR.$target->getShortName().'.log';
		$filename_out = $build_dir.DIRECTORY_SEPARATOR.$target->getShortName().'.dll';
		$filename_rsp = $build_dir.DIRECTORY_SEPARATOR.$target->getShortName().'.rsp';
		
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
		Build::get()->addScript($target->getName(), array(  'home_dir' => $this->home_path,
										'script_name' => $cmd,
										'env' => $this->env,
										'log_file' => $filename_log
										));
												
		return $filename_out;
	}
		
	private function obj2sys($buildinfo, $build_dir, $files, $libs, &$target) {
		$filename_log = $build_dir.DIRECTORY_SEPARATOR.$target->getShortName().'.log';
		$filename_out = $build_dir.DIRECTORY_SEPARATOR.$target->getShortName().'.sys';
		$filename_rsp = $build_dir.DIRECTORY_SEPARATOR.$target->getShortName().'.rsp';
		
    // /OPT:REF /OPT:ICF   /INTEGRITYCHECK 
		$flags  = ' /RELEASE /kernel /driver /LTCG';
    $flags .= ' /MERGE:_PAGE=PAGE /MERGE:_TEXT=.text /SECTION:INIT,d /STACK:0x40000,0x1000';
    $flags .= ' /base:0x10000 /SUBSYSTEM:NATIVE';
    $flags .= ' /MANIFEST:NO /NODEFAULTLIB';
    $flags .= ' fltMgr.lib ntstrsafe.lib kernl32p.lib wdmsec.lib BufferOverflowK.lib ntoskrnl.lib hal.lib wmilib.lib ';
		// /MACHINE:{ARM|EBC|X64|X86}
		// http://msdn.microsoft.com/ru-ru/library/9a89h429.aspx
		if($buildinfo->getPlatform() == 'x32')  $flags .= ' /MACHINE:X86 /SAFESEH /align:0x80 /functionpadmin:5 /entry:GsDriverEntry@8 ';
		// http://msdn.microsoft.com/ru-ru/library/dn195771.aspx
		if($buildinfo->getPlatform() == 'x64')  $flags .= ' /MACHINE:X64 /HIGHENTROPYVA /functionpadmin:6 /entry:GsDriverEntry ';		// 
    if($buildinfo->getPlatform() == 'arm')  $flags .= ' /MACHINE:ARM /FUNCTIONPADMIN ';
    if($buildinfo->getPlatform() == 'ia64') $flags .= ' /MACHINE:IA64 /FUNCTIONPADMIN:16 ';
		$depends = self::file_list($this->initDepends($target));
		file_put_contents($filename_rsp, $files.$flags."\n".$includes."\n".$depends."\n".'/OUT:"'.$filename_out.'"');
		
		$cmd = $this->tool_link.' /nologo @"'.$filename_rsp.'"';
		echo $cmd."\n";
		Build::get()->addScript($target->getName(), array(  'home_dir' => $this->home_path,
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
