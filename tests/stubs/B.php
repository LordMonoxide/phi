<?php

class B {
  private $_a = null;
  
  public function __construct(A $a) {
    $this->_a = $a;
  }
  
  public function getA() {
    return $this->_a;
  }
}
