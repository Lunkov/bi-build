<?php

class SignTool {
	private static $time_serv = '/t http://timestamp.verisign.com/scripts/timstamp.dll';
	private $tool = '';
	private $params = array();
	
	public function __construct() {
	}
	
	public function init($params) {
		$this->params = $params;
		$this->tool = '"'.$params['home_dir'].'signtool.exe"';
	}
	
	public function sign($file) {
		$flags = 'sign /v ';
		switch(Utils::getFileExtension($file)) {
		case 'sys':
        $flags .= ' /ph '; // /nph /ph /uw 
				break;
		case 'cat':
        $flags .= ' /a ';
				break;
		case 'dll':
        $flags .= ' /a /ph ';
				break;
		case 'exe':
        $flags .= ' /a ';
				break;
		default:
				break;
		}
    if(isset($this->params['sign']['ca'])) {
      $flags .= ' /ac '.$this->params['sign']['ca'].' ';
    }
    if(isset($this->params['sign']['cert'])) {
      $flags .= ' /s my /n "'.$this->params['sign']['cert'].'" ';
    }
    if(isset($this->params['sign']['sha1'])) {
      $flags .= ' /sha1 '.$this->params['sign']['sha1'].' ';
    }
    // /ac C:\tools\cert\After_10-10-10_MSCV-VSClass3.cer /s my /n "Atlansys Software LLC" /t http://timestamp.verisign.com/scripts/timestamp.dll crf.sys
		//exec($this->tool.' '.$flags.' '.self::$time_serv.' "'.$file.'"', $output, $ret);
    Build::get()->execScript($this->tool.' '.$flags.' '.self::$time_serv.' "'.$file.'"', $output, $ret);
		$ret = '0';
		if(isset($output[0])) $ret = $output[0];
		return $ret;
	}
}
