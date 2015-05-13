<?php namespace LordMonoxide\Phi;

use InvalidArgumentException;
use ReflectionClass;

/**
 * Dependency injection manager
 */
class Phi implements ResolverInterface {
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
   * Adds a custom resolver to the IoC container
   * 
   * @param   ResolverInterface $resolver The resolver to add
   */
  public function addResolver(ResolverInterface $resolver) {
    $this->_resolvers[] = $resolver;
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
    // Iterate over each resolver and see if they have a binding override
    foreach($this->_resolvers as $resolver) {
      // Ask the resolver for the alias' binding
      $binding = $resolver->make($alias, $arguments);
      
      // If it's not null, we got a binding
      if($binding !== null) {
        return $binding;
      }
    }
    
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
    
    // Size array
    for($i = 0; $i < count($parameters); $i++) {
      $values[] = null;
    }
    
    /*
     * The following is a three-step process to fill out the parameters. For example:
     * 
     * ```
     * parameters = [A $p1, B $p2, string $p3, B $p4, string $p5]
     * arguments  = [new B, new B, 'p5' => 'asdf', 'fdsa']
     * values     = [, , , , ]
     * 
     * Iterate over arguments ->
     *   Does argument have key? ->
     *     Iterate over parameters ->
     *       Is argument key == parameter name? ->
     *         values[parameter index] = argument
     *         unset argument[argument key]
     *         break
     * 
     * parameters = [A $p1, B $p2, string $p3, B $p4, string $p5]
     * arguments  = [new B, new B, 'fdsa']
     * values     = [, , , , 'asdf']
     * 
     * Iterate over parameters ->
     *   Does parameter have a class? ->
     *     Iterate over arguments ->
     *       Is argument instance of parameter? ->
     *         values[parameter index] = argument
     *         unset argument[argument index]
     *         break
     * 
     * parameters = [A $p1, B $p2, string $p3 B $p4, string $p5]
     * arguments  = ['fdsa']
     * values     = [, new B, , new B, 'asdf']
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
     * values     = [new A (from Ioc), new B, 'fdsa', new B, 'asdf']
     * ```
     */
    
    // Step 1...
    foreach($arguments as $argIndex => $argument) {
      if(is_string($argIndex)) {
        foreach($parameters as $paramIndex => $parameter) {
          if($argIndex == $parameter->getName()) {
            $values[$paramIndex] = $argument;
            unset($arguments[$argIndex]);
            break;
          }
        }
      }
    }
    
    // Step 2...
    foreach($parameters as $paramIndex => $parameter) {
      if($parameter->getClass()) {
        foreach($arguments as $argIndex => $argument) {
          if(is_object($argument)) {
            if($parameter->getClass()->isInstance($argument)) {
              $values[$paramIndex] = $argument;
              unset($arguments[$argIndex]);
              break;
            }
          }
        }
      }
    }
    
    // Step 3...
    foreach($parameters as $paramIndex => $parameter) {
      if(!isset($values[$paramIndex])) {
        if($parameter->getClass()) {
          $values[$paramIndex] = $this->make($parameter->getClass()->getName());
        } else {
          $values[$paramIndex] = array_shift($arguments);
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
   * @var array   An array of custom resolvers
   */
  private $_resolvers = [];
  
  /**
   * Protected constructor; class cannot be instantiated.
   */
  protected function __construct() { }
}
