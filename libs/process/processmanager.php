<?php

class ProcessManager {
  private $processes = 2;
  private $sleep_time = 1;
    
  public function __construct() {
		$this->processes = Enviroment::getProcCount() * 2;
	}
	
  function exec(&$queue) {
    $i = 0;
    $countProcesses = $queue->countProcesses();
    Logger::get()->out(Logger::Debug, 'Count of process in queue: '.$countProcesses);
    for(;;) {
      // Fill up the slots
      while (($queue->getCountRunning() < $this->processes) && ($i < $countProcesses)) {
        ob_flush();
        flush();
        $queue->startProcess();
        $i++;
      }

      // Check if done
      if (($queue->getCountRunning()==0) and ($i >= $countProcesses)) {
        break;
      }
      // sleep, this duration depends on your script execution time, the longer execution time, the longer sleep time
      sleep($this->sleep_time);

      // check what is done
      $queue->checkDone();
    }
  }
}

