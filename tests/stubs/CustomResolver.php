<?php

use LordMonoxide\Phi\ResolverInterface;

class CustomResolver implements ResolverInterface {
  public function make($alias, array $arguments = []) {
    if($alias == 'A') {
      return new B(new A());
    }
  }
}