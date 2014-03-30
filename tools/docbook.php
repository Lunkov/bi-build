<?

class DocBook {
	private $FOP_LIBS = array('fop.jar', 'xml-apis-1.3.04.jar',
							'xml-apis-ext-1.3.04.jar', 'xercesImpl-2.7.1.jar',
							'xalan-2.7.0.jar', 'serializer-2.7.0.jar',
							'batik-all-1.7.jar', 'xmlgraphics-commons-1.3.1.jar',
							'avalon-framework-4.2.0.jar', 'commons-io-1.3.1.jar',
							'commons-logging-1.0.4.jar', 'fop-hyph.jar');
	private $FOP_CLASS = 'org.apache.fop.cli.Main';
	private $tool = '';
	private $bin_path = '';
	private $params = array();
	
	public function __construct() {
		
	}
	
	public function init($params) {
		$this->params = $params;
	}
	
}
