<?php

class TypedConstructor {
  private $_a = null;
  private $_b = null;
  
  public function __construct(A $a, B $b) {
    $this->_a = $a;
    $this->_b = $b;
  }
  
  public function getA() {
    return $this->_a;
  }
  
  public function getB() {
    return $this->_b;
  }
}
