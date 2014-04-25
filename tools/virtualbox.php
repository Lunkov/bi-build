<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of virtualbox
 *
 * @author MasterZX
 */
class virtualbox {
  //put your code here
  private $home_path;
  private $bin_path;
  private $tool_manage;
  
 	function init($params) {
    if(!isset($params['home_path'])) {
      Logger::get()->out(Logger::Critical, 'Undefined home path for VirtualBox');
      return;
    }
    $this->home_path = $params['home_path'];
		$this->bin_path = $params['home_path'].DIRECTORY_SEPARATOR.'bin';
		$this->tool_manage = 'call '.Utils::escapeshellcmd($this->bin_path.DIRECTORY_SEPARATOR.'VBoxManage.exe');
	}

  function start_vm($name) {
    $cmd = $this->tool_manage.' startvm "'.$name.'"';
		Build::get()->addScript(array(  'home_dir' => $this->home_path,
										'script_name' => $cmd
										));    
  }

  function restore_snapshot($name, $snapshot) {
    $cmd = $this->tool_manage.' snapshot "'.$name.'" restore "'.$snapshot.'"';
		Build::get()->addScript(array(  'home_dir' => $this->home_path,
										'script_name' => $cmd
										));
  }
  
}
