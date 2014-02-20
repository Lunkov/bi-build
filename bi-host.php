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

$_GET['os_type'] = array('win');
$_GET['platform'] = array('x64');
$_GET['variant'] = array('production');

Build::get()->define_params();
Build::get()->setReleasePath('C:/src/_build/release/');
Build::get()->setBuildPath('C:/src/_build/cache/');

/*
 * Init tools
 * 
 */

Build::get()->use_tool('svn', array());
Build::get()->use_tool('vcc130', array(
						'home_path' => 'C:\tools\MicrosoftVisualStudio12\VC',
						'sdk_path' => 'C:\tools\SDK\8.1',
						'wdk_path' => 'C:\tools\SDK\8.1',
						));

Build::get()->use_tool('wix', array(
						'home_path' => 'C:\Program Files (x86)\WiX Toolset v3.8',
						));


/*
 * Read configuration files
 * 
 */

Build::get()->find_roots(array(realpath(__DIR__.'/../../projects/')));
Build::get()->save_roots();
//Build::get()->load_roots();

Build::get()->exec();

Build::get()->printTimers();

