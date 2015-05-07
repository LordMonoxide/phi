<?php

class MixedConstructor {
  private $_a = null;
  private $_val1 = null;
  private $_b = null;
  private $_val2 = null;
  
  public function __construct(A $a, $val1, B $b, $val2) {
    $this->_a = $a;
    $this->_val1 = $val1;
    $this->_b = $b;
    $this->_val2 = $val2;
  }
  
  public function getA() {
    return $this->_a;
  }
  
  public function getVal1() {
    return $this->_val1;
  }
  
  public function getB() {
    return $this->_b;
  }
  
  public function getVal2() {
    return $this->_val2;
  }
}
