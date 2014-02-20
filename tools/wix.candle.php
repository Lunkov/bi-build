<?

//"$(WIX_ROOT)/candle.exe" -dVERSION=$(VERSION) -dProcessorArchitecture=$(PROCESSOR_ARCHITECTURE) -dADDRESS_MODEL=$(ADDRESS_MODEL) -dLANGUAGE=$(LANGUAGE) -ext WixDifxAppExtension -ext WixUtilExtension -nologo -out $(1) $(2)
class candle {
	private $flags = '/nologo -ext WixDifxAppExtension -ext WixUtilExtension ';
	private $params;
	
	function init($params) {
		$this->params = $params;
		var_dump($this->params);
	}

}