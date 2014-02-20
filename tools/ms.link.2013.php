<?

class mslink2013 {
	// go to http://msdn.microsoft.com/ru-ru/library/y0zzbyt4.aspx
	
	private $cflags = ' /NOLOGO /NODEFAULTLIB /WX /OPT:REF /INCREMENTAL:NO /RELEASE ';
	private $includes = '';
	private $libs = '';
	private $params;
	
	function init($params) {
		$this->params = $params;
	}
  
	private function getExec($enviroment, $filename_in, $includes, $filename_out) {
		$start = '';
		$home_dir_param = 'home_dir.'.$enviroment->getHostOS().'.'.$enviroment->getHostPlatform();
		echo 'FFF:'.$home_dir_param."\n";
		if(isset($this->params[$home_dir_param])) {
			$start = $this->params[$home_dir_param];
		}
		
		$flags = $this->cflags;
		// /MACHINE:{ARM|EBC|X64|X86}
		// http://msdn.microsoft.com/ru-ru/library/9a89h429.aspx
		if($enviroment->getEnvPlatform() == 'x32') $flags .= ' /SAFESEH /MACHINE:X86 ';
		
		// http://msdn.microsoft.com/ru-ru/library/dn195771.aspx
		if($enviroment->getEnvPlatform() == 'x64') $flags .= ' /HIGHENTROPYVA /MACHINE:X64 ';
		
		return '"'.$start.DIRECTORY_SEPARATOR.'link.exe" '.$flags.' '.$filename_in.' '.$includes.' /OUT:"'.$filename_out.'"';
	}
	
	function static_lib($enviroment, $filename_in, $includes, $filename_out) {
		$flags = '/MANIFEST:NO /PROFILE /LD /NODEFAULTLIB ';
		return $this->getExec($enviroment, $filename_in, $includes, $filename_out).$flags;
	}
	function dynamic_lib() {
		$flags = '/MANIFEST:NO /PROFILE /DLL /NODEFAULTLIB ';
		return $this->getExec($enviroment, $filename_in, $includes, $filename_out).$flags;
	}
	function exe() {
		$flags = '/MANIFEST:NO /PROFILE /Driver /NODEFAULTLIB ';
		return $this->getExec($enviroment, $filename_in, $includes, $filename_out).$flags;
	}
	function driver() {
		 $flags = '/MANIFEST:NO /PROFILE /Driver /NODEFAULTLIB ';
		 return $this->getExec($enviroment, $filename_in, $includes, $filename_out).$flags;
	}
}
