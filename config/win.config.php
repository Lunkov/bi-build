<?php

Build::get()->setReleasePath('C:/src/_build/release/');
Build::get()->setBuildPath('C:/src/_build/cache/');
Build::get()->setProjectsPath(array('c:/src/a-proj/'));

Vars::get()->setVar('Cert.MSCV-VSClass3', 'C:\tools\cert\After_10-10-10_MSCV-VSClass3.cer');
Vars::get()->setVar('Cert.Company',       'Atlansys Software LLC');
Vars::get()->setVar('Cert.SHA256',        'f489fb8717663b4ac2547b60d70b2b2a2405b57b');
Vars::get()->setVar('Cert.SHA1',          '87fc50e1cbfff00bb1ded61db0a2b4f4a2ff8e74');

/*
 * Init tools
 * 
 */

Build::get()->initTool('upx',      array('home_path' => 'C:\tools\bin'));
Build::get()->initTool('sigcheck', array('home_path' => 'C:\tools\bin'));
Build::get()->initTool('svn', array());

Build::get()->initTool('vcc130', array(
						'home_path' => 'C:\tools\Microsoft Visual Studio 12.0\VC',
						'sdk_path' => 'C:\tools\SDK\8.1',
						'wdk_path' => 'C:\tools\SDK\8.1',
						'qt_path'  => 'C:\Qt\5.2.1\5.2.1\msvc2012_64',
						));

Build::get()->initTool('wix', array(
						'home_path' => 'C:\Program Files (x86)\WiX Toolset v3.8',
						));

Build::get()->initTool('docbook', array(
						'home_path' => 'C:\tools\docbook',
						));


Global $link_unit_test;
$link_unit_test  = array( 	'atlansys-ess:\src\libs\test_engine@test_engine',
							'atlansys-ess:\src\libs\test_env@test_env',
				);

Build::get()->setParams('unit_test_links',
					array( 'atlansys-ess:\src\libs\test_engine@test_engine',
						   'atlansys-ess:\src\libs\test_env@test_env',
						)
		);
		
Build::get()->setParams('driver_includes',
					array( 'atlansys-ess:\src\libs\test_engine@test_engine',
						   'atlansys-ess:\src\libs\test_env@test_env',
						)
		);		
