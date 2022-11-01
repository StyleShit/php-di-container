# PHP DI Container
Just a simple DI Container for PHP, Inspired by Laravel's API.

## Available Methods:

### `Container::bind($abstract, $concrete = null, $shared = false)`

Binding an interface to an implementation:

```PHP
$container = new Container();

// Simple binding by class string.
$container->bind(InterfaceName::class, Implementation::class);

// Binding with a factory / resolver function.
$container->bind(InterfaceName::class, function (Container $containerInstance, $args) {
    $otherClass = $containerInstance->make(OtherClass::class);

    return new Implementation($args['name'], $otherClass);
});
```


### `Container::singleton($abstract, $concrete = null)`
Binding a class as a singleton, so the Container will always resolve to the same instance:

```PHP
$container = new Container();

// Simple singleton binding.
$container->singleton(Implementation::class);

// Binding an interface to an implementation as a singleton.
$container->singleton(InterfaceName::class, Implementation::class);

// Singleton binding with a factory / resolver function.
$container->bind(InterfaceName::class, function (Container $containerInstance, $args) {
    $otherClass = $containerInstance->make(OtherClass::class);

    return new Implementation($args['name'], $otherClass);
});
```

> **Note**
> Re-binding the same class will remove the currently living singleton instance from the Container (if there is one).


### `Container::make($abstract, $args = [])`
Making an instance of a class / interface:

```PHP
$container = new Container();

$container->bind(InterfaceName::class, Implementation::class);

// Resolves to `new Implementation('StyleShit')`
$container->make(Implementation::class, [
    'name' => 'StyleShit',
]);
```

When trying to make an instance of an unbound class, the Container will try to automatically resolve the given class and its dependencies based on type hints:

```PHP
class A {}

class B {
    public function __construct(A $a, $name) {
        $this->a = $a;
        $this->name = $name;
    }
}

// Resolves to: `new B(new A(), 'StyleShit')`
$container->make(B::class, [
    'name' => 'StyleShit',
]);
```

> **Note**
> The `$args` array must be associative and the keys should be named after the constructor parameters.


### `Container::has($abstract)`
Determine if the Container has a binding for the given abstract:

```PHP
$container = new Container();

$container->bind(InterfaceName::class, Implementation::class);

$container->has(InterfaceName::class); // true
$container->has(Implementation::class); // false
```

___
For more information, check out the [tests](./tests/ContainerTest.php).