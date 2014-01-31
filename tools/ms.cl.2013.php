<? 

class mscl2013 {
	private $cflags = '/nologo /GS /GL /analyze /W3 /Gy /Zc:wchar_t /Zi /Gm- /O2 /sdl';
	private $params;
	
	function init($params) {
		$this->params = $params;
		var_dump($this->params);
	}
  
	function getExec($enviroment, $filename_in, $includes, $filename_out) {
		$start = '';
		$home_dir_param = 'home_dir.'.$enviroment->getHostOS().'.'.$enviroment->getHostPlatform();
		echo 'FFF:'.$home_dir_param."\n";
		if(isset($this->params[$home_dir_param])) {
			$start = $this->params[$home_dir_param];
		}
		$flags = $this->cflags;
		if($enviroment->getEnvPlatform() == 'x32') $flags .= ' /MACHINE:X86 ';
		
		if($enviroment->getEnvPlatform() == 'x64') $flags .= ' /MACHINE:X64 ';
		
		return '"'.$start.DIRECTORY_SEPARATOR.'cl.exe" '.$flags.' '.$filename_in.' '.$includes.' /Fo:'.$filename_out;
	}
}
