<?php

namespace StyleShit\DIContainer;

use StyleShit\DIContainer\Exceptions\AbstractNotFoundException;
use StyleShit\DIContainer\Exceptions\ConcreteNotFoundException;
use StyleShit\DIContainer\Exceptions\ConcreteNotInstantiableException;
use StyleShit\DIContainer\Exceptions\InterfaceNotBoundException;
use StyleShit\DIContainer\Exceptions\InvalidAbstractException;

class Container
{
    private static $instance;

    protected $bindings = [];

    protected $instances = [];

    public static function getInstance()
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public function bind($abstract, $concrete = null, $shared = false)
    {
        if (! is_string($abstract)) {
            throw InvalidAbstractException::make($abstract);
        }

        if ($shared) {
            unset($this->instances[$abstract]);
        }

        // If no concrete is supplied, use the abstract as the concrete.
        // Mainly used to bind singletons, or just register a class to the container.
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        $concrete = $this->wrapConcrete($concrete);

        $this->bindings[$abstract] = [
            'resolver' => $concrete,
            'shared' => $shared,
        ];

        return $this;
    }

    public function singleton($abstract, $concrete = null)
    {
        return $this->bind($abstract, $concrete, true);
    }

    public function has($abstract)
    {
        return isset($this->bindings[$abstract]);
    }

    public function make($abstract, $args = [])
    {
        // Try to automatically make an abstract even if it's not bound.
        if (! $this->has($abstract)) {
            if (interface_exists($abstract)) {
                throw InterfaceNotBoundException::make($abstract);
            }

            if (! interface_exists($abstract) && ! class_exists($abstract)) {
                throw AbstractNotFoundException::make($abstract);
            }

            return $this->makeWithDependencies($abstract, $args);
        }

        $binding = $this->bindings[$abstract];
        $resolve = $binding['resolver'];

        // Singletons.
        if ($binding['shared']) {
            if (! isset($this->instances[$abstract])) {
                $this->instances[$abstract] = $resolve($this, $args);
            }

            return $this->instances[$abstract];
        }

        // Non-Singletons.
        return $resolve($this, $args);
    }

    public function flush()
    {
        $this->bindings = [];
        $this->instances = [];
    }

    protected function wrapConcrete($concrete)
    {
        if (is_callable($concrete)) {
            return $concrete;
        }

        if (! class_exists($concrete) && ! interface_exists($concrete)) {
            throw ConcreteNotFoundException::make($concrete);
        }

        if (! $this->isInstantiable($concrete)) {
            throw ConcreteNotInstantiableException::make($concrete);
        }

        // By default, try auto resolving a concrete.
        return function (Container $container, $args) use ($concrete) {
            return $container->makeWithDependencies($concrete, $args);
        };
    }

    protected function isInstantiable($concrete)
    {
        $reflection = new \ReflectionClass($concrete);

        return $reflection->isInstantiable();
    }

    protected function makeWithDependencies($concrete, $args)
    {
        $dependencies = $this->resolveDependencies($concrete);

        $dependencies = array_map(function (\ReflectionParameter $dep) use ($args) {
            // User-defined args.
            if (array_key_exists($dep->getName(), $args)) {
                return $args[$dep->getName()];
            }

            // Default constructor args.
            if ($dep->isDefaultValueAvailable()) {
                return $dep->getDefaultValue();
            }

            return $this->make($dep->getType()->getName());
        }, $dependencies);

        $reflection = new \ReflectionClass($concrete);

        return $reflection->newInstanceArgs($dependencies);
    }

    protected function resolveDependencies($concrete)
    {
        $reflection = new \ReflectionClass($concrete);
        $constructor = $reflection->getConstructor();

        if (! $constructor) {
            return [];
        }

        return $constructor->getParameters();
    }
}
