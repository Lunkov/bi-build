<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

include 'task.php';
/**
 * Description of Queue
 *
 * @author MasterZX
 */
class Queue {
  private $queue   = array();
  
  private function setQueue($queue) {
    $this->queue = $queue;
  }
  public function getQueue() {
    return $this->queue;
  }
  
  public function countTasks() {
    return count($this->queue);
  }

  public function countProcesses() {
    $count = 0;
    foreach($this->queue as $task) {
      $count += $task->countProcesses();
    }
    return $count;
  }
  
  public function exists($target) {
    return  is_string($target) &&
            is_array($this->queue) &&
            isset($this->queue[$target]);
  }
  
  public function addTask($target, $order) {
    $this->queue[$target] = new Task($target, $order);
  }

  public function setRequirements($target, $require) {
    $this->queue[$target]->setRequirements($require);
  }

  public function addProcess($task, $proc) {
    if(!$this->exists($task)) {
      return false;
    }
    $this->queue[$task]->addProcess($proc);
    return true;
  }
  
  public function getTask($target) {
    if(!$this->exists($target)) {
      return null;
    }
    return $this->queue[$target];
  }
  
  public function getResult($target) {
    if(!$this->exists($target)) {
      return null;
    }    
    return $this->queue[$target]->getResult();
  }
  
  public function setResult($target, $result) {
    if(!$this->exists($target)) {
      return;
    }    
    $this->queue[$target]->setResult($result);
  }
  
  public function setStatus($target, $status) {
    if(!$this->exists($target)) {
      return;
    }
    $this->queue[$target]->setStatus($status);
  }

  public function getCountRunning() {
    $result = 0;
    foreach ($this->queue as $task) {
      $result += $task->getCountRunning();
    }
    return $result;
  }
  
  public function checkDone() {
    foreach ($this->queue as $task) {
      $task->checkDone();
    }
  }
  
  public function getTaskStatus($task) {
    if(!$this->exists($task)) {
      return Status::UNDEFINED;
    }
    if($this->queue[$task]->getStatus() != Status::DEFINED) {
      return $this->queue[$task]->getStatus();
    }
    foreach($this->queue[$task]->getRequirements() as $key) {
      if($this->exists($key) && 
         $this->queue[$key]->getStatus() <= Status::RUNNING) {
        return $this->queue[$task]->getStatus();
      }
    }
    $this->queue[$task]->setStatus(Status::READY);
    return Status::READY;
  }

  public function startProcess() {
    foreach ($this->queue as $key => $val) {
      $task_status = $this->getTaskStatus($key);
      if($task_status >= Status::READY && $task_status <= Status::RUNNING) {
        if($val->startProcess()) {
          return true;
        }
      }
    }
    return false;  
  }
  
  public function saveState($path) {
    Utils::saveState($path.DIRECTORY_SEPARATOR.'roots.order', $this->queue);
  }
  
  public function loadState($path) {
    $this->queue = Utils::loadState($path.DIRECTORY_SEPARATOR.'roots.order');
  }
}
