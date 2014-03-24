<?

//"$(WIX_ROOT)/candle.exe" -dVERSION=$(VERSION) -dProcessorArchitecture=$(PROCESSOR_ARCHITECTURE) -dADDRESS_MODEL=$(ADDRESS_MODEL) -dLANGUAGE=$(LANGUAGE) -ext WixDifxAppExtension -ext WixUtilExtension -nologo -out $(1) $(2)
class WiX {
	const CANDLE_EXE = 'candle.exe';
	private $flags = '/nologo -ext WixDifxAppExtension -ext WixUtilExtension ';
	private $params;
	private $tool_candle = '';
	
	function init($params) {
		$this->params = $params;
		$this->env = $_ENV;
		$this->bin_path = $this->params['home_path'].DIRECTORY_SEPARATOR.'bin';
		$this->tool_candle = $this->bin_path.DIRECTORY_SEPARATOR.CANDLE_EXE;
	}

	public function wix_lib($buildinfo, $build_dir, $target_name, &$target) {
		$b_dir = $build_dir.$target['dir'];
		echo '+'.$b_dir."\n";
		if(!file_exists($b_dir)) {
			mkdir($b_dir, 0x0777, true);
		}

		foreach($target['src'] as $file) {
			//$filename_in = $target['home_dir'].DIRECTORY_SEPARATOR.$file;
			$filename_in = $file;
			$extention  = Utils::getFileExtension($file);
			//echo '++'.$b_dir."\n";
			switch($extention) {
			case 'wxs':
			//case 'wxl':
					$cl_result[] = $this->wxx2wixobj($buildinfo, $b_dir, $filename_in, $flags, $includes);
					break;
			}
		}
		$curTime = microtime(true);
		Build::get()->execScripts();
		$this->time_work += round(microtime(true) - $curTime,3)*1000; 	
		
	}
	
	private function wxx2wixobj($buildinfo, $build_dir, $file, $flags, $includes) {

		$filename_log = $build_dir.DIRECTORY_SEPARATOR.Utils::getFileName($file).'.log';
		$filename_out = $build_dir.DIRECTORY_SEPARATOR.Utils::getFileName($file).'.wix_obj';
		$filename_rsp = $build_dir.DIRECTORY_SEPARATOR.Utils::getFileName($file).'.rsp';
		
		file_put_contents($filename_rsp, '"'.$file."\"\n".$flags."\n");
		
    if($buildinfo->getPlatform() == 'x32') {
      $flags .= ' -dADDRESS_MODEL=$(32)';
    }
    if($buildinfo->getPlatform() == 'x64') {
      $flags .= ' -dADDRESS_MODEL=$(64)';
    }
    $flags .= ' -dProcessorArchitecture=$('.$buildinfo->getPlatform().')';
    $flags .= ' -ext WixIISExtension -ext WixDifxAppExtension -ext WixUtilExtension -nologo';
    
    //-dVERSION=$(VERSION) -dLANGUAGE=$(LANGUAGE)  -out $(1) $(2)
    
		$cmd = 'candle.exe '.$flags.' -out "'.$filename_out.'" "'.$file.'"';
		echo $cmd."\n";
		Build::get()->addScript(array(  'home_dir' => $this->bin_path,
										'script_name' => $cmd,
										'env' => $this->env,
										'log_file' => $filename_log
										));
		
		return $filename_out;
	}
		
	public function wxs($buildinfo, $build_dir, $target_name, &$target) {
		foreach($target['src'] as $file) {
			//$filename_in = $target['home_dir'].DIRECTORY_SEPARATOR.$file;
			$filename_in = $file;
			$extention  = Utils::getFileExtension($file);
			$out = '';
			//echo '++'.$b_dir."\n";
			if($extention == 'wxs') $this->wxx2wixobj($buildinfo, $b_dir, $filename_in, $flags, $includes, $out);
			//if($extention == 'wxl') $this->wxx2wixobj($buildinfo, $b_dir, $filename_in, $flags, $includes, $out);
			
			if(strlen($out)>0) {
				$cl_result[] = $out;
			}
		}
		$curTime = microtime(true);
		Build::get()->execScripts();
		$this->time_work += round(microtime(true) - $curTime,3)*1000; 		
	}
	
}
