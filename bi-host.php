<?

include './libs/build.php';

/*
 * Command line parameters
 * 
 * os_type = Win, Linux
 * platform = x32, x64
 * variant = develop, debug, production
 * 
 */


Build::get()->define_params();
Build::get()->setReleasePath('C:/src/_build/release/');
Build::get()->setBuildPath('C:/src/_build/cache/');

/*
 * Init tools
 * 
 */

Build::get()->use_tool('svn', array());
Build::get()->use_tool('ms.cl.2013', array(
		'home_dir.Windows_NT.AMD64' => 'C:\\tools\\Microsoft Visual Studio 12.0\\VC\\bin\\amd64\\',
		'home_dir.Windows_NT.i386' => 'C:\\tools\\Microsoft Visual Studio 12.0\\VC\\bin\\i386\\'
		));
Build::get()->use_tool('ms.link.2013', array(
		'home_dir.Windows_NT.AMD64' => 'C:\\tools\\Microsoft Visual Studio 12.0\\VC\\bin\\amd64\\',
		'home_dir.Windows_NT.i386' => 'C:\\tools\\Microsoft Visual Studio 12.0\\VC\\bin\\i386\\'
		));
Build::get()->use_tool('signtool', array(
		'home_dir.Windows_NT.AMD64' => 'C:\\tools\\SDK\\8.1\\bin\\',
		'home_dir.Windows_NT.i386' => 'C:\\tools\\SDK\\8.1\\bin\\'
		));

/*
 * Read configuration files
 * 
 */

/*
if(file_exists(__DIR__.DIRECTORY_SEPARATOR.BUILD_DIR.'build.php')) {
  rename(__DIR__.DIRECTORY_SEPARATOR.BUILD_DIR.'build.php', __DIR__.DIRECTORY_SEPARATOR.BUILD_DIR.'build_old.php');
  include __DIR__.DIRECTORY_SEPARATOR.BUILD_DIR.'build_old.php';
}
*/
Build::get()->find_roots(array(__DIR__.'/../../projects/'));

//exec($CL_PATH.'cl.exe', $output, $ret);
// cl.exe /GS /GL /analyze- /W3 /Gy /Zc:wchar_t /Zi /Gm- /O2 /sdl /Fd"Release\vc120.pdb" /fp:precise /D "_MBCS" /errorReport:prompt /WX- /Zc:forScope /Gd /Oy- /Oi /MD /Fa"Release\" /EHsc /nologo /Fo"Release\" /Fp"Release\Project1.pch" 
//var_dump($targets);

Build::get()->exec();


//$context = '$src_files_old = json_decode('.json_encode($src_files).');';
//file_put_contents(__DIR__.DIRECTORY_SEPARATOR.BUILD_DIR.'build.php', $context);

Build::get()->printTimers();
