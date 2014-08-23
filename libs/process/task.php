<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
include 'process.php';

class Task {

  private $name = '';
  private $requirements = array();
  private $order;
  private $status;
  private $result;
  private $processes = array();
  private $currentProcess = 0;
  private $currentProcessOrder = 0;
  
  public function __construct($name, $order) {
    $this->name   = $name;
    $this->order  = $order;
    $this->status = Status::DEFINED;
  }
  public function setRequirements($requirements) {
    $this->requirements = $requirements;
  }
  public function getRequirements() {
    return $this->requirements;
  }
  public function getStatus() {
    return $this->status;
  }
  public function getName() {
    return $this->name;
  }
  public function setStatus($status) {
    $this->status = $status;
  }
  public function getResult() {
    return $this->result;
  }
  public function setResult($result) {
    $this->result = $result;
  }

  public function countProcesses() {
    return count($this->processes);
  }

  public function addProcess($process) {
    $order = 0;
    if(isset($process['order'])) {
      $order = $process['order'];
    }
    $this->processes[$order][] = new Process($process);
  }
  
  public function getCountRunning() {
    $result = 0;
    foreach($this->processes as $arproc) {
      foreach($arproc as $proc) {
        if($proc->getStatus() == Status::RUNNING) {
          $result++;
        }
      }
    }
    return $result;
  }
  
  public function startProcess() {
    if($this->status > Status::RUNNING) {
      return false;
    }
    if(count($this->processes) < 1) {
      return false;
    }
    $result_err = false;
    $running = false;
    foreach($this->processes as $arproc) {
      if($running) {
        return false;
      }
      $running = false;
      foreach($arproc as $proc) {
        if($proc->getStatus() < Status::RUNNING) {
          $proc->start();
          return true;
        }
        if(!$running) {
          $running = ($proc->getStatus() == Status::RUNNING);
        }
        if(!$result_err) {
          $result_err = ($proc->getStatus() == Status::RESULT_ERR);
        }
      }
      if($result_err) {
        $this->status = Status::RESULT_ERR;
        return false;
      }
    }
    if(!$result_err && !$running) {
      $this->status = Status::RESULT_OK;
    }
    return false;
  }
  
  public function checkDone() {
    foreach($this->processes as $arproc) {
      foreach($arproc as $proc) {
        if($proc->getStatus() == Status::RUNNING) {
          if (!$proc->isRunning() or $proc->isOverExecuted()) {
            if (!$proc->isRunning()) {
              Logger::get()->out(Logger::Info, 'Done: '.$proc->getScript());
            }
            else {
              Logger::get()->out(Logger::Warning, 'Killed: '.$proc->getScript());
            }
            $out = $proc->getOutput();
            Logger::get()->out(Logger::Info, $out);
            $proc->close();
            ob_flush();
            flush();
          }
        }
      }
    }
  }
}
