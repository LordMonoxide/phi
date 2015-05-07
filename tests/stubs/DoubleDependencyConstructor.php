<?php

class DoubleDependencyConstructor {
  private $_a = null;
  private $_b1 = null;
  private $_b2 = null;
  
  public function __construct(A $a, B $b1, B $b2) {
    $this->_a = $a;
    $this->_b1 = $b1;
    $this->_b2 = $b2;
  }
  
  public function getA() {
    return $this->_a;
  }
  
  public function getB1() {
    return $this->_b1;
  }
  
  public function getB2() {
    return $this->_b2;
  }
}
