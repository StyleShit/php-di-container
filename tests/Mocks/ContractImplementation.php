<?php

namespace StyleShit\DIContainer\Tests\Mocks;

class ContractImplementation implements Contract
{
    public function __construct($name = 'mock')
    {
        $this->name = $name;
    }
}
