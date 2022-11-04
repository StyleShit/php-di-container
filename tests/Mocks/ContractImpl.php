<?php

namespace StyleShit\DIContainer\Tests\Mocks;

class ContractImpl implements Contract
{
    public function __construct($name = 'mock')
    {
        $this->name = $name;
    }
}
