<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
abstract class Status {
  const   UNDEFINED  = 0;
  const   DEFINED    = 1;
  const   READY      = 2;
  const   RUNNING    = 3;
  const   RESULT_OK  = 4;
  const   RESULT_ERR = 5;
}

class Process {
	const STDIN = 0;
	const STDOUT = 1;
	const STDERR = 2;
	const BUF_SIZE = 1024;
  /*
   * Type of process
   * 
   */
  const TYPE_UNDEF = 0;
  const TYPE_SIGN = 1;
  const TYPE_MULT = 2;

  private $handle = null;
  private $pipes = array();
  private $max_execution_time;
  private $type = self::TYPE_UNDEF;
  private $start_time;
  private $home_dir;
  private $env;
  private $log_file;
  private $err_file;
  private $script_name;
  private $spec;
  private $status = Status::UNDEFINED;
      
  function __construct($params) {
    $this->script_name = $params['script_name'];
    $this->home_dir = $params['home_dir'];
    $this->env = $params['env'];
    $this->max_execution_time = isset($params['max_execution_time']) ? $params['max_execution_time'] : 30;
    $this->log_file = isset($params['log_file']) ? $params['log_file'] : $this->getTempFile('log');
    $this->err_file = isset($params['err_file']) ? $params['err_file'] : $this->getTempFile('err');
    $this->status = Status::DEFINED;
  }
  
  public function getType() {
    return $this->type;
  }
  
  public function start() {
    $this->status = Status::RUNNING;
		$this->spec = array(
            self::STDIN  => array('pipe', 'r'),
            self::STDOUT => array('pipe', 'w'),//$this->pipes[self::STDOUT],
            self::STDERR => array('pipe', 'w'),//$this->pipes[self::STDERR],
        );
    $this->handle = proc_open($this->script_name, $this->spec, $this->pipes, $this->home_dir, $this->env);
    $this->start_time = time();
    Logger::get()->out(Logger::Debug, 'Running script: '.$this->script_name);
	}
	
  public function getHandle() {
		return $this->handle;
	}

  public function getScript() {
		return $this->script_name;
	}

	private function getTempFile($ext) {
		return Enviroment::getTemp().DIRECTORY_SEPARATOR.'_bi_'.md5($this->script_name).'.'.$ext;
	}
	
	public function close() {
    foreach($this->pipes as $val) {
      if(is_resource($val)) {
        fclose($val);
      }
    }
		proc_close($this->handle);
	}
		
  // is still running?
  public function isRunning() {
    $status = proc_get_status($this->handle);
    return $status['running'];
  }

  // long execution time, proccess is going to be killer
  public function isOverExecuted() {
    return ($this->start_time+$this->max_execution_time < time());
  }

	public function getOutput() {
		$ret = '';
    foreach($this->pipes as $key => $val) {
      if(($key==self::STDOUT || $key==self::STDERR) && is_resource($val)) {
        $out = stream_get_contents($val);
        if(!empty($out)) {
          $ret .= $out;
        }
      }
    }
    $this->calcStat($ret);
		return $ret;
	}

  private function calcStat($out) {
    $ok = true;
    if(is_array($out)) {
      foreach($out as $str) {
        if(stripos($str, ': warning ') > 0) {
          Build::get()->incWarning();
          continue;
        }
        if(stripos($str, ': error ') > 0) {
          $ok = false;
          Build::get()->incError();
        }
      }
    }
    if(is_string($out)) {
      Build::get()->incWarning(substr_count($out, ' warning '));
      $errors = substr_count($out, ' error ');
      if($errors>0) {
        $ok = false;
        Build::get()->incError($errors);
      }
    }
    if($ok) {
      Build::get()->incOK();
      $this->status = Status::RESULT_OK;
    } else {
      $this->status = Status::RESULT_ERR;
    }
  }
}