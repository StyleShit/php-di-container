<?php

namespace StyleShit\DIContainer\Tests\Mocks;

class VariadicPrimitive
{
    public function __construct($name = 'mock', string ...$args)
    {
        $this->name = $name;
        $this->args = $args;
    }
}
