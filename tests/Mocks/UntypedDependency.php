<?php

namespace StyleShit\DIContainer\Tests\Mocks;

class UntypedDependency
{
    public function __construct($name)
    {
        $this->name = $name;
    }
}
