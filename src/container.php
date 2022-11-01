<?php

namespace StyleShit;

class Container
{
    protected $bindings = [];

    protected $instances = [];

    public function bind($abstract, $resolver = null, $shared = false)
    {
        if ( $shared ) {
            unset( $this->instances[$abstract] );
        }

        // By default, try auto resolving a concrete from the given abstract.
        if (! $resolver) {
            $resolver = function (Container $container, $args) use ($abstract) {
                return $container->makeWithDependencies($abstract, $args);
            };
        }

        $this->bindings[$abstract] = [
            'resolver' => $resolver,
            'shared' => $shared,
        ];

        return $this;
    }

    public function singleton($abstract, $resolver = null)
    {
        return $this->bind($abstract, $resolver, true);
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
                throw new \Exception("Interface `$abstract::class` is not bound to a concrete");
            }

            if (! interface_exists($abstract) && ! class_exists($abstract)) {
                throw new \Exception("Abstract `$abstract::class` not found");
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

    protected function makeWithDependencies($abstract, $args)
    {
        $dependencies = $this->resolveDependencies($abstract);

        $dependencies = array_map(function (\ReflectionParameter $dep) use ($args) {
            if (isset($args[$dep->getName()])) {
                return $args[$dep->getName()];
            }

            if ($dep->isDefaultValueAvailable()) {
                return $dep->getDefaultValue();
            }

            return $this->make($dep->getType()->getName());
        }, $dependencies);

        return new $abstract(...$dependencies);
    }

    protected function resolveDependencies($abstract)
    {
        if (! interface_exists($abstract) && ! class_exists($abstract)) {
            throw new \Exception("Abstract `$abstract::class` not found");
        }

        $reflection = new \ReflectionClass($abstract);
        $constructor = $reflection->getConstructor();

        if (! $constructor) {
            return [];
        }

        return $constructor->getParameters();
    }
}
