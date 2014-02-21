<?

//"$(WIX_ROOT)/candle.exe" -dVERSION=$(VERSION) -dProcessorArchitecture=$(PROCESSOR_ARCHITECTURE) -dADDRESS_MODEL=$(ADDRESS_MODEL) -dLANGUAGE=$(LANGUAGE) -ext WixDifxAppExtension -ext WixUtilExtension -nologo -out $(1) $(2)
class WiX {
	const CANDLE_EXE = 'candle.exe';
	private $flags = '/nologo -ext WixDifxAppExtension -ext WixUtilExtension ';
	private $params;
	private $time_work = 0;
	private $tool_candle = '';
	
	function init($params) {
		$this->params = $params;
		$this->env = $_ENV;
		$this->bin_path = $this->params['home_path'].DIRECTORY_SEPARATOR.'bin';
		$this->tool_candle = $this->bin_path.DIRECTORY_SEPARATOR.CANDLE_EXE;
	}

	public function wxs($buildinfo, $build_dir, $target_name, &$target) {
		foreach($target['src'] as $file) {
			//$filename_in = $target['home_dir'].DIRECTORY_SEPARATOR.$file;
			$filename_in = $file;
			$extention  = Utils::getFileExtension($file);
			$out = '';
			//echo '++'.$b_dir."\n";
			if($extention == 'wxs') $this->wxx2wixobj($b_dir, $filename_in, $flags, $includes, $out);
			if($extention == 'wxl') $this->wxx2wixobj($b_dir, $filename_in, $flags, $includes, $out);
			
			if(strlen($out)>0) {
				$cl_result[] = $out;
			}
		}
		$curTime = microtime(true);
		Build::get()->execScripts();
		$this->time_work += round(microtime(true) - $curTime,3)*1000; 		
	}
	
	public function printTimers() {
		echo "==========\n";
		echo 'Tool: '.__CLASS__."\n";
		echo 'Work time: '.$this->time_work.' ms'."\n";
		echo "==========\n";
	}	
}
