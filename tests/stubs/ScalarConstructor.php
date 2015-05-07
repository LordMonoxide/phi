<?php

class ScalarConstructor {
  private $_val1 = null;
  private $_val2 = null;
  
  public function __construct($val1, $val2) {
    $this->_val1 = $val1;
    $this->_val2 = $val2;
  }
  
  public function getVal1() {
    return $this->_val1;
  }
  
  public function getVal2() {
    return $this->_val2;
  }
}
