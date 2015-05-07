<?php namespace LordMonoxide\Ioc;

use InvalidArgumentException;
use ReflectionClass;

/**
 * Dependency injection manager
 */
class Ioc {
  /**
   * @var object  Singleton instance
   */
  private static $_instance = null;
  
  /**
   * Accessor for singleton, instantiates if necessary
   */
  public static function instance() {
    if(self::$_instance == null) {
      self::$_instance = new static;
    }
    
    return self::$_instance;
  }
  
  /**
   * Binds a class to an alias
   * 
   * @param   string                  $alias      An alias (eg. `db.helper`), or a real class or
   *                                              interface name to be replaced by `$binding`
   * @param   string|callable|object  $binding    May be one of the following:
   *                                              <ul>
   *                                                  <li>The fully-qualified name of a class</li>
   *                                                  <li>An instance of a class (creates a singleton)</li>
   *                                                  <li>A callable that returns an instance of a class</li>
   *                                              </ul>
   */
  public function bind($alias, $binding) {
    $this->_map[$alias] = $binding;
  }
  
  /**
   * Gets or creates an instance of an alias
   * 
   * @param   string  $alias      An alias (eg. `db.helper`), or a real class or interface name
   * @param   array   $arguments  The arguments to pass to the binding
   * 
   * @returns object  A new instance of `$alias`'s binding, or a shared instance in the case of singletons
   */
  public function make($alias, array $arguments = []) {
    // Check to see if we have something bound to this alias
    if(array_key_exists($alias, $this->_map)) {
      $binding = $this->_map[$alias];
      
      if(is_callable($binding)) {
        // If it's callable, we call it and pass on our arguments
        return call_user_func_array($binding, $arguments);
      } elseif(is_object($binding)) {
        // If it's an object, simply return it
        return $binding;
      }
    } else {
      // If we don't have a binding, we'll just be `new`ing up the alias
      $binding = $alias;
    }
    
    // This will be used to `new` up the binding
    $reflector = new ReflectionClass($binding);
    
    // Make sure it's instantiable (ie. not abstract/interface)
    if(!$reflector->isInstantiable()) {
      throw new InvalidArgumentException("$binding is not an instantiable class");
    }
    
    // Grab the constructor
    $constructor = $reflector->getConstructor();
    
    // If there's no constructor, it's easy.  Just make a new instance.
    if(empty($constructor)) {
      return $reflector->newInstance();
    }
    
    // Grab all of the constructor's parameters
    $parameters = $constructor->getParameters();
    $values = [];
    
    /*
     * The following is a two-step process to fill out the parameters. For example:
     * 
     * ```
     * parameters = [A, B, string, B, string]
     * arguments  = [new B, new B, 'asdf', 'fdsa']
     * values     = []
     * 
     * Iterate over parameters ->
     *   Does parameter have a class? ->
     *     Iterate over arguments ->
     *       Is argument instance of parameter? ->
     *         values[parameter index] = argument
     *         unset argument[parameter index]
     *         break
     * 
     * parameters = [A, B, string, B, string]
     * arguments  = ['asdf', 'fdsa']
     * values     = [, new B, , new B, ]
     * 
     * Iterate over parameters ->
     *   Is values missing index [parameter index]?
     *     Does parameter have a class?
     *       values[parameter index] = Ioc::make(parameter)
     *     Otherwise,
     *       values[parameter index] = the first argument left in arguments
     *       pop the first element from arguments
     * 
     * parameters = [A, B, string, B, string]
     * arguments  = []
     * values     = [new A (from Ioc), new B, 'asdf', new B, 'fdsa']
     * ```
     */
    
    // Step 1...
    foreach($parameters as $index => $parameter) {
      $values[] = null;
      
      if($parameter->getClass()) {
        foreach($arguments as $argIndex => $argument) {
          if(is_object($argument)) {
            if($parameter->getClass()->isInstance($argument)) {
              $values[$index] = $argument;
              unset($arguments[$argIndex]);
              break;
            }
          }
        }
      }
    }
    
    // Step 2...
    foreach($parameters as $index => $parameter) {
      if(!isset($values[$index])) {
        if($parameter->getClass()) {
          $values[$index] = $this->make($parameter->getClass()->getName());
        } else {
          $values[$index] = array_shift($arguments);
        }
      }
    }
    
    // Done! Create a new instance using the values array
    return $reflector->newInstanceArgs($values);
  }
  
  /**
   * @var array   An assotiative array of aliases and bindings
   */
  private $_map = [];
  
  /**
   * Protected constructor; class cannot be instantiated.
   */
  protected function __construct() { }
}
