<?php

require_once __DIR__ . '/stubs/A.php';
require_once __DIR__ . '/stubs/B.php';
require_once __DIR__ . '/stubs/DoubleDependencyConstructor.php';
require_once __DIR__ . '/stubs/MixedConstructor.php';
require_once __DIR__ . '/stubs/NoConstructor.php';
require_once __DIR__ . '/stubs/ScalarConstructor.php';
require_once __DIR__ . '/stubs/TypedConstructor.php';
require_once __DIR__ . '/stubs/Uninstantiable.php';

use LordMonoxide\Phi\Phi;

class PhiTest extends PHPUnit_Framework_TestCase {
  public function testNoConstructor() {
    $instance = Phi::instance()->make('NoConstructor');
    $this->assertInstanceOf('NoConstructor', $instance);
  }
  
  public function testAlias() {
    Phi::instance()->bind('test', 'NoConstructor');
    $instance = Phi::instance()->make('test');
    $this->assertInstanceOf('NoConstructor', $instance);
  }
  
  public function testClosure() {
    Phi::instance()->bind('test', function($parameter1, $parameter2) {
      $this->assertEquals('param1', $parameter1);
      $this->assertEquals('param2', $parameter2);
      
      return new NoConstructor();
    });
    
    $instance = Phi::instance()->make('test', ['param1', 'param2']);
    $this->assertInstanceOf('NoConstructor', $instance);
  }
  
  public function testSingleton() {
    Phi::instance()->bind('test', new NoConstructor());
    $instance = Phi::instance()->make('test');
    $this->assertInstanceOf('NoConstructor', $instance);
  }
  
  public function testUninstantiable() {
    $this->setExpectedException('InvalidArgumentException');
    
    $instance = Phi::instance()->make('Uninstantiable');
  }
  
  public function testScalarParameters() {
    $instance = Phi::instance()->make('ScalarConstructor', ['a', 'b']);
    $this->assertEquals('a', $instance->getVal1());
    $this->assertEquals('b', $instance->getVal2());
  }
  
  public function testAutoInjection() {
    $instance = Phi::instance()->make('TypedConstructor');
    $this->assertInstanceOf('A', $instance->getA());
    $this->assertInstanceOf('B', $instance->getB());
    $this->assertInstanceOf('A', $instance->getB()->getA());
  }
  
  public function testAutoInjectionPartialOverride() {
    $b = Phi::instance()->make('B');
    $instance = Phi::instance()->make('TypedConstructor', [$b]);
    $this->assertEquals($b, $instance->getB());
  }
  
  public function testAutoInjectionFullOverride() {
    $a = Phi::instance()->make('A');
    $b = Phi::instance()->make('B', [$a]);
    $instance = Phi::instance()->make('TypedConstructor', [$a, $b]);
    $this->assertEquals($a, $instance->getA());
    $this->assertEquals($b, $instance->getB());
    $this->assertEquals($a, $instance->getB()->getA());
  }
  
  public function testUnorderedInterleavedInjection() {
    $b = Phi::instance()->make('B');
    $instance = Phi::instance()->make('MixedConstructor', ['test1', 'test2', $b]);
    $this->assertInstanceOf('A', $instance->getA());
    $this->assertEquals($b, $instance->getB());
    $this->assertEquals('test1', $instance->getVal1());
    $this->assertEquals('test2', $instance->getVal2());
  }
  
  public function testMultipleInjectionsOfOneClass() {
    $instance = Phi::instance()->make('DoubleDependencyConstructor');
    $this->assertInstanceOf('A', $instance->getA());
    $this->assertInstanceOf('B', $instance->getB1());
    $this->assertInstanceOf('B', $instance->getB2());
    $this->assertNotEquals(spl_object_hash($instance->getB1()), spl_object_hash($instance->getB2()));
  }
  
  public function testMultipleInjectionsOfOneClassWithOneOverride() {
    $b = Phi::instance()->make('B');
    $instance = Phi::instance()->make('DoubleDependencyConstructor', [$b]);
    $this->assertInstanceOf('A', $instance->getA());
    $this->assertInstanceOf('B', $instance->getB1());
    $this->assertInstanceOf('B', $instance->getB2());
    $this->assertNotEquals(spl_object_hash($instance->getB1()), spl_object_hash($instance->getB2()));
  }
  
  public function testMultipleInjectionsOfOneClassWithOverrides() {
    $b = Phi::instance()->make('B');
    $instance = Phi::instance()->make('DoubleDependencyConstructor', [$b, $b]);
    $this->assertInstanceOf('A', $instance->getA());
    $this->assertInstanceOf('B', $instance->getB1());
    $this->assertInstanceOf('B', $instance->getB2());
    $this->assertEquals(spl_object_hash($instance->getB1()), spl_object_hash($instance->getB2()));
  }
  
  public function testMultipleInjectionsOfOneClassBindings() {
    Phi::instance()->bind('B', Phi::instance()->make('B'));
    $instance = Phi::instance()->make('DoubleDependencyConstructor');
    $this->assertInstanceOf('A', $instance->getA());
    $this->assertInstanceOf('B', $instance->getB1());
    $this->assertInstanceOf('B', $instance->getB2());
    $this->assertEquals(spl_object_hash($instance->getB1()), spl_object_hash($instance->getB2()));
  }
}
