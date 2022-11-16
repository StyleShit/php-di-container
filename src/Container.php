<?php

namespace StyleShit\DIContainer;

use StyleShit\DIContainer\Exceptions\AbstractNotFoundException;
use StyleShit\DIContainer\Exceptions\ConcreteNotFoundException;
use StyleShit\DIContainer\Exceptions\ConcreteNotInstantiableException;
use StyleShit\DIContainer\Exceptions\InterfaceNotBoundException;
use StyleShit\DIContainer\Exceptions\InvalidAbstractException;
use StyleShit\DIContainer\Exceptions\UnresolvableDependencyException;

class Container
{
    private static $instance;

    protected $bindings = [];

    protected $contextualBindings = [];

    protected $instances = [];

    protected $buildStack = [];

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

    public function when($concrete)
    {
        return new ContextualBindingBuilder($this, $concrete);
    }

    public function addContextualBinding($concrete, $needs, $implementation)
    {
        $this->contextualBindings[$concrete][$needs] = $this->wrapConcrete($implementation);
    }

    public function has($abstract)
    {
        return isset($this->bindings[$abstract]) || $this->needsContextualBinding($abstract);
    }

    public function make($abstract, $args = [])
    {
        $this->buildStack[] = $abstract;

        // Try to automatically make an abstract even if it's not bound.
        if (! $this->has($abstract)) {
            if (interface_exists($abstract)) {
                throw InterfaceNotBoundException::make($abstract);
            }

            if (! class_exists($abstract)) {
                throw AbstractNotFoundException::make($abstract);
            }

            return $this->makeWithDependencies($abstract, $args);
        }

        if ($this->isShared($abstract)) {
            return $this->resolveSharedConcrete($abstract, $args);
        }

        return $this->resolveConcrete($abstract, $args);
    }

    public function forgetInstance($abstract)
    {
        unset($this->instances[$abstract]);
    }

    public function forgetInstances()
    {
        $this->instances = [];
    }

    public function flush()
    {
        $this->bindings = [];
        $this->instances = [];
        $this->contextualBindings = [];
        $this->buildStack = [];
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

    protected function isShared($abstract)
    {
        return ! empty($this->bindings[$abstract]['shared']);
    }

    protected function resolveSharedConcrete($abstract, $args)
    {
        if ($this->needsContextualBinding($abstract)) {
            return $this->resolveContextualConcrete($abstract, $args);
        }

        if (! isset($this->instances[$abstract])) {
            $resolve = $this->bindings[$abstract]['resolver'];

            $this->instances[$abstract] = $resolve($this, $args);
        }

        return $this->instances[$abstract];
    }

    protected function resolveConcrete($abstract, $args)
    {
        if ($this->needsContextualBinding($abstract)) {
            return $this->resolveContextualConcrete($abstract, $args);
        }

        $resolve = $this->bindings[$abstract]['resolver'];

        return $resolve($this, $args);
    }

    protected function resolveContextualConcrete($abstract, $args)
    {
        $resolve = $this->getContextualConcrete($abstract);

        return $resolve($this, $args);
    }

    protected function makeWithDependencies($concrete, $args)
    {
        $dependencies = $this->resolveDependencies($concrete);
        $finalDependencies = [];

        foreach ($dependencies as $dep) {
            // User-defined args.
            if (array_key_exists($dep->getName(), $args)) {
                $finalDependencies[] = $args[$dep->getName()];

                continue;
            }

            // Default constructor args.
            if ($dep->isDefaultValueAvailable()) {
                $finalDependencies[] = $dep->getDefaultValue();

                continue;
            }

            // Variadic args (...$args).
            if ($dep->isVariadic()) {
                $variadicDependency = $this->resolveVariadicDependency($dep);

                if (! is_null($variadicDependency)) {
                    $finalDependencies[] = $variadicDependency;
                }

                continue;
            }

            // Unresolvable dependency.
            if (! $this->isResolveableDependency($dep)) {
                throw UnresolvableDependencyException::make($dep->getName());
            }

            $finalDependencies[] = $this->make($dep->getType()->getName());
        }

        $reflection = new \ReflectionClass($concrete);

        return $reflection->newInstanceArgs($finalDependencies);
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

    protected function resolveVariadicDependency(\ReflectionParameter $dep)
    {
        return $this->isResolveableDependency($dep)
            ? $this->make($dep->getType()->getName())
            : null;
    }

    protected function isResolveableDependency(\ReflectionParameter $dep)
    {
        return $dep->hasType() && ! $dep->getType()->isBuiltin();
    }

    protected function needsContextualBinding($abstract)
    {
        return (bool) $this->getContextualConcrete($abstract);
    }

    protected function getContextualConcrete($abstract)
    {
        $currentConcrete = $this->getCurrentBuiltConcrete();

        return $this->contextualBindings[$currentConcrete][$abstract] ?? null;
    }

    protected function getCurrentBuiltConcrete()
    {
        $last = count($this->buildStack) - 2;

        return $this->buildStack[$last] ?? null;
    }
}
