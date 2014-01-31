<?
/*
 * http://msdn.microsoft.com/ru-ru/library/6dt9w0ss.aspx
 * http://msdn.microsoft.com/ru-ru/library/9s7c9wdw.aspx
 * 
 * 
 */
 
class WinApp {
	private $time_cl;
	private $time_link;
	
	public function static_lib($enviroment, $build_dir, $target_name, $target) {
		if(!file_exists($build_dir.$target['dir'])) {
			mkdir($build_dir.$target['dir'], 0x0777, true);
		}
		
		foreach($target['include'] as $include) {
			//$target['home_dir'].DIRECTORY_SEPARATOR.
			$includes.='/I"'.$include.'" ';
		}
		
		$cl = new mscl2013();
		$cl->init(array(
			'home_dir.Windows_NT.AMD64' => 'C:\\tools\\Microsoft Visual Studio 12.0\\VC\\bin\\amd64\\',
			'home_dir.Windows_NT.i386' => 'C:\\tools\\Microsoft Visual Studio 12.0\\VC\\bin\\i386\\'
			));
		$link = new mslink2013();
		$link->init(array(
			'home_dir.Windows_NT.AMD64' => 'C:\\tools\\Microsoft Visual Studio 12.0\\VC\\bin\\amd64\\',
			'home_dir.Windows_NT.i386' => 'C:\\tools\\Microsoft Visual Studio 12.0\\VC\\bin\\i386\\'
			));
		 
		
		foreach($target['src'] as $file) {
			$filename_in = $target['home_dir'].DIRECTORY_SEPARATOR.$file;
			$filename_out = $build_dir.DIRECTORY_SEPARATOR.$target['dir'].DIRECTORY_SEPARATOR.Utils::getFileName($file).'.obj';
			$filename_log = $build_dir.DIRECTORY_SEPARATOR.$target['dir'].DIRECTORY_SEPARATOR.Utils::getFileName($file).'.log';
			
			//echo 'extension:'.Utils::getFileExtension($filename);
			//self::$cnt_lines+=Utils::getFileLines($filename);
			
			$md = Utils::calcHash($filename_in);
			
			//$CL_PATH = '"C:\\tools\\Microsoft Visual Studio 12.0\\VC\\bin\\amd64\\';
			//$cmd = $CL_PATH.'cl.exe" /MT '. $includes . $filename . ' /Fo:'.$filename_out;
			//echo $cmd."\n";
			$cmd = $cl->getExec($enviroment, $filename_in, $includes, $filename_out);
			echo $cmd."\n";
			$curTime = microtime(true);
			exec($cmd, $output, $ret);
			$this->time_cl += round(microtime(true) - $curTime,3)*1000; 
			file_put_contents($filename_log, $output);

			$src_files[$filename] = array('md5' => $md);

        }
        
        if(isset($target['make']) && method_exists($ln, $target['make'])) {
			$curTime = microtime(true);
			$cmd = $link->$target['make']($enviroment, $filename_in, $includes, $filename_out);
			exec($cmd, $output, $ret);
			$this->time_link += round(microtime(true) - $curTime,3)*1000; 
			
			$filename_log = $build_dir.DIRECTORY_SEPARATOR.$target['dir'].DIRECTORY_SEPARATOR.$target['make'].'_'.$target_name.'.log';
			file_put_contents($filename_log, $output);
		}
	}
}
