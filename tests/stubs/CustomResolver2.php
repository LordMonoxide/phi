<?php

use LordMonoxide\Phi\ResolverInterface;

class CustomResolver2 implements ResolverInterface {
  public function make($alias, array $arguments = []) {
    if($alias == 'B') {
      return new A();
    }
  }
}