[![Build Status](https://travis-ci.org/LordMonoxide/phi.svg?branch=1.2.0)](https://travis-ci.org/LordMonoxide/phi)
[![Coverage Status](https://coveralls.io/repos/LordMonoxide/phi/badge.svg?branch=1.2.0)](https://coveralls.io/r/LordMonoxide/phi?branch=1.2.0)
[![License](https://img.shields.io/packagist/l/LordMonoxide/phi.svg)](https://img.shields.io/packagist/l/LordMonoxide/phi.svg)

# φhi
An efficient, easy-to-use, open-source PHP dependency injection container, boasting a tiny footprint, powerful features, 100% unit test coverage, and awesome documentation.  Phi is compatible with PSR-0 and PSR-4 auto-loading standards, and open to collaboration from anyone who feels they can make an improvement.

## Installation

### Composer
[Composer](https://getcomposer.org/) is the recommended method of installation for Phi.

```
composer require lordmonoxide/phi
```

### GitHub

Phi may be downloaded from [GitHub](https://github.com/LordMonoxide/phi/).

## Features
Phi supports several different ways to inject dependencies, which can all be used alone or in conjunction with one another.

### Automatic Injection
Assume you have a class named `Foo` that depends on a second class, `Bar`:

```php
class Foo {
  public $bar = null;
  
  public function __construct(Bar $bar) {
    $this->bar = $bar;
  }
}
```

You can easily get a new instance of `Foo` with all required dependencies by doing the following:

```php
$foo = $phi->make('Foo');
// $foo->bar = new Bar
```

You'll get a new instance of `Foo` with a new instance of `Bar` automatically injected into the constructor.  This, of course, works recursively.  If `Bar` depends on `Baz`, an instance of `Baz` will be injected into `Bar`, and so on.

### Passing Parameters
There will be many cases where you need to pass parameters into the constructors as well.  Consider the following class (note the order of the parameters):

```php
class Foo {
  public $a = null;
  public $b = null;
  public $first_name = null;
  public $last_name  = null;
  
  public function __construct(B $b, A $a, $first_name = null, $last_name = null) {
    $this->a = $a;
    $this->b = $b;
    $this->first_name = $first_name;
    $this->last_name  = $last_name;
  }
}
```

There are several ways you can request this class from Phi:

```php
$foo = $phi->make('Foo');
// $foo->a == new A
// $foo->b == new B
// $foo->first_name == null
// $foo->last_name  == null
```

```php
$foo = $phi->make('Foo', ['John', 'Doe']);
// $foo->a == new A
// $foo->b == new B
// $foo->first_name == 'John'
// $foo->last_name  == 'Doe'
```

```php
$foo = $phi->make('Foo', ['John']);
// $foo->a == new A
// $foo->b == new B
// $foo->first_name == 'John'
// $foo->last_name  == null
```

You may want to override an automatically injected parameter:

```php
$a = new A;
$foo = $phi->make('Foo', ['John', 'Doe', $a]);
// $foo->a == $a
// $foo->b == new B
// $foo->first_name == 'John'
// $foo->last_name  == 'Doe'
```

Note that `$a` was passed in last in the previous example.  Phi is smart enough to figure out the correct order to inject parameters of non-scalar types.

### Multiple Same-Type Dependencies
Consider the following class:

```php
class Foo {
  public function __construct(BarInterface $bar, A $a, BarInterface $baz, B $b) {
    // ...
  }
}
```

```php
$bar = new Bar; // implements BarInterface
$baz = new Baz; // implements BarInterface
$foo = $phi->make('Foo', [$bar, $baz]);
// $foo->bar == $bar
// $foo->baz == $baz
// $foo->a   == new A
// $foo->b   == new B
```

Parameters of the same type will be passed to the constructor in the order they are given to Phi.  If you would like to pass them in a different order, please see the section on named injection.

### Named Injection
In some cases, it is useful to be explicit about which parameters you are passing in.  Phi makes this easy.  Consider the class from the "Passing Parameters" section:

```php
class Foo {
  public function __construct(A $a, B $b, $first_name = null, $last_name = null) {
    // ...
  }
}
```

```php
$a = new A;
$b = new B;
$foo = $phi->make('Foo', [$b, 'last_name' => 'Doe', $a]);
// $foo->a == $a
// $foo->b == $b
// $foo->first_name == null
// $foo->last_name  == 'Doe'
```

### Binding
Many modern applications have pieces that may be swapped out.  This is accomplished by using interfaces.  Phi allows automatic injection of interfaces using binding:

```php
interface BarInterface {

}

class Bar implements BarInterface {

}

class Foo {
  public function __construct(BarInterface $bar) {
    // ...
  }
}

$phi->bind('BarInterface', 'Bar');
```

```php
$foo = $phi->make('Foo');
// $foo->bar == new Bar
```

```php
$bar = $phi->make('BarInterface');
// $bar = new Bar
```

Binding even allows you to swap one concrete instance of a class for another:

```php
$phi->bind('A', 'B');

$a = $phi->make('A');
// $a == new B
```

### Dependencies With Parameters
Sometimes you may have a dependency that has required parameters.  This can be done by binding a class to a callable:

```php
$phi->bind('Person', function() {
  return new Person('John', 'Doe');
});

$person = $phi->make('Person');
// $person->first_name == 'John'
// $person->last_name  == 'Doe'
```

This is also useful if you need to perform logic when instanciating a class:

```php
$id = 0;

$phi->bind('Person', function() use(&$id) {
  return new Person(++$id);
});

$person1 = $phi->make('Person'); // id == 1
$person2 = $phi->make('Person'); // id == 2
```

Any parameters passed to Phi will be passed directly to the callable:

```php
$id = 0;

$phi->bind('Person', function($name, $age) use(&$id) {
  $names = explode(' ', $name);
  return new Person(++$id, $name[0], $name[1], $age);
});

$person = $phi->make('Person', ['John Doe', 21]);
// $person->id == 1
// $person->first_name == 'John'
// $person->last_name  == 'Doe'
// $person->age == 21
```

### Singletons
Phi also allows binding to real instances of classes.  This can be used to create singletons:

```php
$default_pdo = new PDO(...); // The default database
$stats_pdo   = new PDO(...); // PDO pointing to a different database

$phi->bind('PDO', $default_pdo);
```

```php
$pdo = $phi->make('PDO');
// $pdo == $default_pdo
```

```php
class Table {
  public function __construct(PDO $pdo, $table_name) {
    // ...
  }
}

$users_table = $phi->make('Table', ['users']);
// $users_table->pdo   == $default_pdo
// $users_table->table == 'users'

$stats_table = $phi->make('Table', [$stats_pdo, 'stats']);
// $stats_table->pdo   == $stats_pdo
// $stats_table->table == 'stats'
```

### Aliases
Sometimes, a codebase will have very commonly used classes with difficult-to remember names. For example, `Vendor\Package\Core\Logging\Log`.  It may be useful to give such classes shorter and easier to type names:

```php
$phi->bind('core.log', 'Vendor\Package\Core\Logging\Log');

$log = $phi->make('core.log');
// $log == new Vendor\Package\Core\Logging\Log
```

You may also bind aliases to callables or singletons.

### Custom Resolvers
There may be times when you want to match far more than a single alias. Custom resolvers were designed with
this purpose in mind. When a binding is requested from Phi, any custom resolvers that are registered will be
executed one by one in the order they were added, and the first one to return a non-null value is the one that
will be used. If all custom resolvers return null, Phi will resolve the binding normally.

```php
use LordMonoxide\Phi\ResolverInterface;

class CustomResolver implements ResolverInterface {
  public function make($alias, array $arguments = []) {
    if($alias == 'A') {
      return new B(new A());
    }
  }
}

$phi->addResolver(new CustomResolver());
```

```php
$b = $phi->make('A');
//$b == new B
```

Another reason to use custom resolvers is to wrap other IoC containers. For example, if you are using Laravel, you could combine the Laravel container with Phi:

```php
use Illuminate\Support\Facades\App;
use LordMonoxide\Phi\ResolverInterface;

class LaravelResolver implements ResolverInterface {
  public function make($alias, array $arguments = []) {
    if(App::bound($alias)) {
      return App::make($alias, $arguments);
    }
  }
}

$phi->addResolver(new LaravelResolver());
```

This way, any binding that is registered in the Laravel IoC container will be resolved by it.  The rest will be passed on to Phi.
