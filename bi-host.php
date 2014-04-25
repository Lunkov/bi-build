<?php


include __DIR__.'/libs/build.php';

echo Enviroment::toString();

if(Enviroment::getOS() == 'WINNT') {
  include __DIR__.'/config/win.config.php';
}
if(Enviroment::getOS() == 'LINUX') {
  include __DIR__.'/config/linux.config.php';
}

/*
 * Command line parameters
 * 
 * os_type = Win, Linux
 * platform = x32, x64
 * variant = develop, debug, production
 * 
 */

$_GET['os_type'] = array('win');
$_GET['platform'] = array('x64');
$_GET['variant'] = array('production');
$_GET['target'] = 'atlansys-ess:\src\libs\ldap@lib_ldap';

Build::get()->define_params();
				
/*
 * Execute
 * 
 */
 
Build::get()->exec();
