<? 

class mscl2013 {
	private $cflags = '/GS /GL /analyze- /W3 /Gy /Zc:wchar_t /Zi /Gm- /O2 /sdl';
	private $params;
	
	function init($params) {
		$this->params = $params;
	}
  
	function getExec($params) {
		return '"'.$this->params['home_dir'].'cl.exe" /nologo ';
	}
}
