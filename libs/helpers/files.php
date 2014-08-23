<?php

class Files {
	private $hashes_files_old = array();
	private $hashes_files_new = array();

	public function fileChanged($filename) {
    //echo 'File1: '.$filename."\n";
		$fn = realpath($filename);
		$h1 = '';
		$h2 = $this->getFileHash($fn);
		if(isset($this->hashes_files_old[$fn])) {
			$h1 = $this->hashes_files_old[$fn];
		}
		return strcmp($h1, $h2) != 0;
	}

  public function saveState($filename) {
    Utils::saveState($filename, $this->hashes_files_new);
  }

  public function loadState($filename) {
    $this->hashes_files_old = Utils::loadState($filename);
  }
  
  private function setHashes($rt) {
    $this->hashes_files_old = $rt;
    var_dump($this->hashes_files_old);
  }

	private function getFileHash($filename) {
		if(!isset($this->hashes_files_new[$filename])) {
			$this->hashes_files_new[$filename] = Utils::calcHash($filename);
		}
		return $this->hashes_files_new[$filename];
	}
}
