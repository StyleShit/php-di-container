<?php

namespace StyleShit\DIContainer\Tests\Mocks;

class VariadicClass
{
    public function __construct($name = 'mock', D ...$args)
    {
        $this->name = $name;
        $this->args = $args;
    }
}
