<?php

namespace StyleShit\DIContainer\Tests\Mocks;

class A
{
    public function __construct(B $b, $name = 'mock', $age = 10)
    {
        $this->b = $b;
        $this->name = $name;
        $this->age = $age;
    }
}
