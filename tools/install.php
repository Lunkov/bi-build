<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Install {
  function make($buildinfo, $build_dir, $target_name, &$target) {
    $build_dir = Build::get()->getReleaseDir();
		$b_dir = Utils::mkdir($build_dir.$target['dir']);
	
		$result = array();
    
    //$b_dir = Utils::mkdir($b_dir);
    //copy($sys_file, $b_dir.DIRECTORY_SEPARATOR.Utils::getBaseName($sys_file));
  }
}