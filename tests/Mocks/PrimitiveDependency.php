<?php

namespace StyleShit\DIContainer\Tests\Mocks;

class PrimitiveDependency
{
    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
