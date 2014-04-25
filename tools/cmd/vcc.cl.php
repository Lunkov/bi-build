<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of vcc_cl
 *
 * @author MasterZX
 */
class vcc_cl extends Process {
 
  private $tool = 'cl.exe';
  //put your code here
  public function prepare($buildinfo, $build_dir, $env_tool, $target_name, &$target) {

    $dir = Utils::mkdir($build_dir.DIRECTORY_SEPARATOR.Utils::getRelativePath($home_dir, $file));// TODO .DIRECTORY_SEPARATOR.Utils::getPath($file));
    $fn = $dir.DIRECTORY_SEPARATOR.Utils::getFileName($file);
		$filename_log = $fn.'.log';
		$filename_out = $fn.'.obj';
		$filename_rsp = $fn.'.rsp';
    
    if(!Build::get()->fileChanged($file) && file_exists($filename_out)) {
      echo 'SKIP: '.$file."\n";
      return $filename_out;
    }
		$flags.=' /EHsc ';
		file_put_contents($filename_rsp, '/Tp"'.$file."\"\n".$flags."\n".$includes."\n".'/Fo:"'.$filename_out.'"');
		
		$cmd = $this->tool_cl.' /nologo @"'.$filename_rsp.'"';
		//echo $cmd."\n";
		Build::get()->addScript(array(
                              'home_dir' => $this->home_path,
                              'script_name' => $cmd,
                              'env' => $env_tool,
                              'log_file' => $filename_log
										));    
  }
  
}
