<?

class ProcessManager {
    private $scripts = array();
    private $processesRunning = 0;
    private $processes = 2;
    private $running = array();
    private $sleep_time = 2;
    
    public function __construct() {
		$this->processes = Enviroment::getProcCount();
	}
	
    function addScript($params) {
        $this->scripts[] = $params;
    }
    
    function exec() {
		$i = 0;
        for(;;) {
			// Fill up the slots
			while (($this->processesRunning<$this->processes) and ($i<count($this->scripts))) {
				//echo 'Running script: '.$this->scripts[$i]['script_name']."\n";
				ob_flush();
				flush();
				$this->running[] = new Process($this->scripts[$i]);
				$this->processesRunning++;
				$i++;
			}
        
			// Check if done
			if (($this->processesRunning==0) and ($i>=count($this->scripts))) {
				break;
			}
			// sleep, this duration depends on your script execution time, the longer execution time, the longer sleep time
			sleep($this->sleep_time);
      
			// check what is done
			foreach ($this->running as $key => $val) {
				if (!$val->isRunning() or $val->isOverExecuted()) {
					if (!$val->isRunning()) {
						echo "Done: ".$val->getScript()."\n";
					}
					else {
						echo "Killed: ".$val->getScript()."\n";
					}
					echo $val->getOutput();
					echo $val->getError();
					$val->close();
					unset($this->running[$key]);
					$this->processesRunning--;
					ob_flush();
					flush();
                }
            }
        }
    }
}

class Process {
	const STDIN = 0;
	const STDOUT = 1;
	const STDERR = 2;
	const BUF_SIZE = 1024;
	
    private $handle = null;
    private $pipes = array();
    private $params;
    private $max_execution_time;
    private $start_time;
    private $log_file;
    private $err_file;
    
    function __construct($params) {
		$this->params = $params;
        $this->max_execution_time = isset($params['max_execution_time']) ? $params['max_execution_time'] : 30;
        $this->log_file = isset($params['log_file']) ? $params['log_file'] : $this->getTempFile('log');
        $this->err_file = isset($params['err_file']) ? $params['err_file'] : $this->getTempFile('err');
        $this->start();
    }
    
    private function start() {
		$this->pipes[self::STDOUT] = fopen($this->log_file, 'w'); 
		$this->pipes[self::STDERR] = fopen($this->err_file, 'w'); 
		$descriptorspec    = array(
            self::STDIN => array('pipe', 'r'),
            self::STDOUT => $this->pipes[self::STDOUT],
            self::STDERR => $this->pipes[self::STDERR],
        );
        //var_dump($this);
        $this->handle = proc_open($this->params['script_name'], $descriptorspec, $this->pipes, $this->params['home_dir'], $this->params['env']);
        //var_dump($this->handle);
        $this->start_time = time();
	}
	
    public function getHandle() {
		return $this->handle;
	}

    public function getScript() {
		return $this->params['script_name'];
	}

	private function getTempFile($ext) {
		return Enviroment::getTemp().DIRECTORY_SEPARATOR.'_bi_'.md5($this->params['script_name']).'.'.$ext;
	}
	
	public function close() {
		if(is_resource($this->pipes[self::STDIN]))
			fclose($this->pipes[self::STDIN]);
		if(is_resource($this->pipes[self::STDOUT]))
			fclose($this->pipes[self::STDOUT]);
		if(is_resource($this->pipes[self::STDERR]))
			fclose($this->pipes[self::STDERR]);
		proc_close($this->handle);
	}
		
    // is still running?
    public function isRunning() {
        $status = proc_get_status($this->handle);
        return $status["running"];
    }

    // long execution time, proccess is going to be killer
    public function isOverExecuted() {
        if ($this->start_time+$this->max_execution_time < time()) return true;
        else return false;
    }

	public function getOutput() {
		$ret = '';
		if(!is_resource($this->pipes[self::STDOUT]))
			$this->pipes[self::STDOUT] = @fopen($this->log_file, 'r'); 
		if(is_resource($this->pipes[self::STDOUT])) {
			$ret = stream_get_contents($this->pipes[self::STDOUT]);
			fclose($this->pipes[self::STDOUT]);
		}
		return $ret;
	}

	public function getError() {
		$ret = '';
		if(!is_resource($this->pipes[self::STDERR]))
			$this->pipes[self::STDERR] = @fopen($this->err_file, 'r'); 
		if(is_resource($this->pipes[self::STDERR])) {
			$ret = stream_get_contents($this->pipes[self::STDERR]);
			fclose($this->pipes[self::STDERR]);
		}
		return $ret;
	}
}
/*
class AsyncOperation extends Thread {
    private $script;
    private $home_dir;
    private $env;
    private $max_execution_time;
    private $start_time;
    private $output;
    private $log_file;
    private $err_file;

    public function __construct($params) {
		$this->script = $params['script_name'];
        $this->max_execution_time = isset($params['max_execution_time']) ? $params['max_execution_time'] : 30;
        $this->home_dir = $params['home_dir'];
        $this->env = $params['env'];
    }

    public function run() {
		$this->start_time = time();
        exec($this->home_dir.$this->script, $this->output);
    }

    public function close() {
	}
	
    function getScript() {
		return $this->script;
	}

	function getTempFile($ext) {
		return Enviroment::getTemp().DIRECTORY_SEPARATOR.md5($this->script).'.'.$ext;
	}
	
    // is still running?
    function isRunning() {
        $status = proc_get_status($this->handle);
        return $status["running"];
    }

    // long execution time, proccess is going to be killer
    function isOverExecuted() {
        if ($this->start_time+$this->max_execution_time < time()) return true;
        else return false;
    }
    
    function getOutput() {
		return $this->output;
	}
}
*/
