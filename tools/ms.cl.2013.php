<? 

class mscl2013 {
	private $cflags = '/nologo /GS /GL /analyze /W3 /Gy /Zc:wchar_t /Zi /Gm- /O2 /sdl /D "_UNICODE" /D "UNICODE" ';
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
		if($enviroment->getEnvPlatform() == 'x32') $flags .= ' /MACHINE:X86 /D "WIN32" ';
		if($enviroment->getEnvPlatform() == 'x64') $flags .= ' /MACHINE:X64 ';
		
		if($enviroment->getEnvVariant() == 'develop') $flags .= ' /D "NDEBUG" ';
		if($enviroment->getEnvVariant() == 'production') $flags .= ' /D "NDEBUG" ';
		if($enviroment->getEnvVariant() == 'debug') $flags .= ' /D "_DEBUG" ';
		$inc = '';
		$inc .= ' /I"C:\tools\Microsoft Visual Studio 12.0\VC\include\" ';
		$inc .= ' /I"C:\tools\SDK\8.1\Include\api\" ';
		$inc .= ' /I"C:\tools\SDK\8.1\Include\shared\" ';
		$inc .= ' /I"C:\tools\SDK\8.1\Include\" ';
		return '"'.$start.DIRECTORY_SEPARATOR.'cl.exe" '.$flags.' '.$filename_in.' '.$includes.$inc.' /Fo:"'.$filename_out.'"';
	}
}
